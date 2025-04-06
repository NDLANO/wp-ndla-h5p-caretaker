<?php
/**
 * Handle the custom page display logic.
 *
 * @package ndla-h5p-caretaker
 */

namespace NDLAH5PCARETAKER;

use Mustache_Engine;

use Ndlano\H5PCaretaker\H5PCaretaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the page index.
 */
function render_page_index() {
	// Capability is registered in ndla-h5p-caretaker.php.
  // phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( Options::get_visibility() !== 'public' && ! current_user_can( 'use-h5p-caretaker' ) ) {
		// Redirect to the dashboard or display an error message.
		wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'ndla-h5p-caretaker' ) ) );
	}

	$http_accept_language = get_http_accept_language();
	$get_locale           = get_locale_from_query();

	// Prevent snooping on H5P content.
	// Capability is registered in ndla-h5p-caretaker.php.
  // phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( current_user_can( 'use-h5p-caretaker' ) ) {
		$h5p_id = get_id_from_query();
	}

	$export_needs_to_be_removed = false;
	$path                       = null;

	if ( ! empty( $h5p_id ) ) {
		try {
			$export_needs_to_be_removed = ensure_h5p_export( $h5p_id );

			$content = \H5P_Plugin::get_instance()->get_content( $h5p_id );

			// Try to get H5P export file for H5P ID.
			if ( is_array( $content ) ) {
				$path = wp_upload_dir()['baseurl'] . DIRECTORY_SEPARATOR .
					'h5p' . DIRECTORY_SEPARATOR .
					'exports' . DIRECTORY_SEPARATOR .
					( $content['slug'] ? $content['slug'] . '-' : '' ) .
					$content['id'] .
					'.h5p';
			}
		} catch ( \Exception $e ) {
			unset( $e );
			$path = null;
		}
	}

	// Set the language based on the browser's language.
	$locale = LocaleUtils::request_translation(
		$get_locale ?? locale_accept_from_http( $http_accept_language )
	);

	if ( get_query_var( 'custom_page' ) ) {
		$base_dir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
		// NOTE: This could simply be used from within node_modules, but some automated services may mistake this for dev.
		$dist_dir = $base_dir . 'js' . DIRECTORY_SEPARATOR . 'h5p-caretaker-client' . DIRECTORY_SEPARATOR . 'dist' . DIRECTORY_SEPARATOR . '@explorendla';
		$dist_url = plugin_dir_url( __FILE__ ) . '../js/h5p-caretaker-client/dist/@explorendla';

		$render_data = array(
			'h5p_caretaker_handlers' => plugin_dir_url( __FILE__ ) . '../js/h5p-caretaker-handlers.js',
			'file_js'                => $dist_url . '/' . get_file_by_pattern( $dist_dir, 'h5p-caretaker-client-*.js' ),
			'file_css'               => $dist_url . '/' . get_file_by_pattern( $dist_dir, 'h5p-caretaker-client-*.css' ),
			'locale'                 => $locale,
			'preloadedurl'           => $path ?? '',
			'preloadedid'            => $export_needs_to_be_removed ? $h5p_id : false,
		);

		render_html( $render_data );
	}
}

/**
 * Ensure the H5P export file exists.
 *
 * @param int $h5p_id ID of H5P content to ensure export for.
 *
 * @return boolean True, if the export file needs to be removed later.
 */
function ensure_h5p_export( $h5p_id ) {
	$core    = \H5P_Plugin::get_instance()->get_h5p_instance( 'core' );
	$content = $core->loadContent( $h5p_id );

	$export_file_name = $content['slug'] . '-' . $content['id'] . '.h5p';

	if ( $core->fs->hasExport( $export_file_name ) ) {
		return false;
	}

	if ( ! create_h5p_export( $content ) ) {
		return false;
	}

	return true;
}

/**
 * Create H5P export.
 * Part of filterParameters function taken from H5P core. We cannot use that
 * function, because the `h5p_export` option could be set to false in order to
 * prevent downloading the H5P files - we need it temporarily though.
 *
 * @param array $content Object with content data.
 *
 * @return bool Whether the export was created successfully.
 */
function create_h5p_export( $content ) {
	if ( ! ( isset( $content['library'] ) && isset( $content['params'] ) ) ) {
		return false;
	}

	$params = (object) array(
		'library' => \H5PCore::libraryToString( $content['library'] ),
		'params'  => json_decode( $content['params'] ),
	);

	if ( ! $params->params ) {
		return false;
	}

	$core = \H5P_Plugin::get_instance()->get_h5p_instance( 'core' );

	// Validate and filter against main library semantics. Nothing we can do about H5P Group's naming (h5pF).
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$validator = new \H5PContentValidator( $core->h5pF, $core );
	$validator->validateLibrary(
		$params,
		(object) array( 'options' => array( $params->library ) )
	);

	// Handle addons. Nothing we can do about H5P Group's naming (h5pF).
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$addons = $core->h5pF->loadAddons();
	foreach ( $addons as $addon ) {
		$add_to = json_decode( $addon['addTo'] );

		if ( isset( $add_to->content->types ) ) {
			foreach ( $add_to->content->types as $type ) {

				if ( isset( $type->text->regex ) &&
						$this->textAddonMatches( $params->params, $type->text->regex )
				) {
					$validator->addon( $addon );

					// An addon shall only be added once.
					break;
				}
			}
		}
	}

	$params = wp_json_encode( $params->params );

	// Update content dependencies.
	$content['dependencies'] = $validator->getDependencies();

	// Sometimes the parameters are filtered before content has been created.
	if ( ! isset( $content['id'] ) ) {
		return false;
	}

	// Nothing we can do about H5P Group's naming (h5pF).
  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$core->h5pF->deleteLibraryUsage( $content['id'] );
	// Nothing we can do about H5P Group's naming (h5pF).
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$core->h5pF->saveLibraryUsage( $content['id'], $content['dependencies'] );

	if ( ! $content['slug'] ) {
		$content['slug'] = $this->generateContentSlug( $content );

		// Remove old export file.
		$core->fs->deleteExport( $content['id'] . '.h5p' );
	}

	// Nothing we can do about H5P Group's naming (h5pF).
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$exporter            = new \H5PExport( $core->h5pF, $core );
	$content['filtered'] = $params;

	$exporter->createExportFile( $content );

	// Cache. Nothing we can do about H5P Group's naming (h5pF).
  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$core->h5pF->updateContentFields(
		$content['id'],
		array(
			'filtered' => $params,
			'slug'     => $content['slug'],
		)
	);

	return true;
}

/**
 * Get the locale from the HTTP Accept-Language header.
 *
 * @return string The locale from the HTTP Accept-Language header.
 */
function get_http_accept_language() {
	return isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) : '';
}

/**
 * Get the locale from the query.
 *
 * @return string The locale from the query.
 */
function get_locale_from_query() {
	if ( ! isset( $_GET['nonce'] ) ) {
		// IMPORTANT NOTE: It's okay to not have it as the user may have entered the Caretaker URL directly.
		$_GET['nonce'] = wp_create_nonce( 'h5p-caretaker-show' );
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'h5p-caretaker-show' ) ) {
		wp_die( esc_html( __( 'Invalid nonce verification.', 'ndla-h5p-caretaker' ) ) );
	}

	return isset( $_GET['locale'] ) ? sanitize_text_field( wp_unslash( $_GET['locale'] ) ) : '';
}

/**
 * Get the ID from the query.
 *
 * @return string The ID from the query.
 */
function get_id_from_query() {
	if ( ! isset( $_GET['nonce'] ) ) {
		// IMPORTANT NOTE: It's okay to not have it as the user may have entered the Caretaker URL directly.
		$_GET['nonce'] = wp_create_nonce( 'h5p-caretaker-show' );
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'h5p-caretaker-show' ) ) {
		wp_die( esc_html( __( 'Invalid nonce verification.', 'ndla-h5p-caretaker' ) ) );
	}

	return isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
}

/**
 * Get file by pattern.
 *
 * @param string $dir The directory to search in.
 * @param string $pattern The pattern to match.
 *
 * @return string The filename that matches the pattern.
 */
function get_file_by_pattern( $dir, $pattern ) {
	$files = glob( $dir . DIRECTORY_SEPARATOR . $pattern );
	return basename( $files[0] ?? '' );
}

/**
 * Render the HTML for the page.
 *
 * @param array $params The parameters to render the HTML with.
 */
function render_html( $params ) {
	header( 'Content-Type: text/html; charset=utf-8' );

	// We're fetching from the local file system, not from a remote server!
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$template = file_get_contents( __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'h5pcaretaker.mustache' );

	$render_data = array(
		'intro'                              => esc_html( Options::get_intro() ),
		'outro'                              => esc_html( Options::get_outro() ),
		'locale'                             => esc_attr( str_replace( '_', '-', $params['locale'] ) ),
		'title'                              => esc_html( __( 'H5P Caretaker Reference Implementation', 'ndla-h5p-caretaker' ) ),
		'h5pcaretakerhandlers'               => esc_url( $params['h5p_caretaker_handlers'] ),
		'filecss'                            => esc_url( $params['file_css'] ),
		'filejs'                             => esc_url( $params['file_js'] ),
		'sessionkeyname'                     => 'h5pCaretakerNonce',
		'sessionkeyvalue'                    => wp_create_nonce( 'h5p-caretaker-upload' ),
		'preloadedurl'                       => esc_url( $params['preloadedurl'] ),
		'preloadedid'                        => esc_attr( $params['preloadedid'] ),
		'h5pcaretaker'                       => esc_html( __( 'H5P Caretaker', 'ndla-h5p-caretaker' ) ),
		// The get_select_language_locales function escapes all the values.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		'locales'                            => get_select_language_locales( $params['locale'] ),
		'takecareofyourh5p'                  => esc_html( __( 'Take care of your H5P', 'ndla-h5p-caretaker' ) ),
		'checkyourh5pfileforimprovements'    => esc_html( __( 'Check your H5P file for improvements', 'ndla-h5p-caretaker' ) ),
		'uncoveraccessibilityissues'         => esc_html( __( 'Uncover accessibility issues, missing information and best practices that can help you improve your H5P content.', 'ndla-h5p-caretaker' ) ),
		'ajaxcleanup'                        => esc_url( home_url( '/' . Options::get_url() . '-clean-up' ) ),
		'ajaxupload'                         => esc_url( home_url( '/' . Options::get_url() . '-upload' ) ),
		'l10nordragthefilehere'              => esc_html( __( 'or drag the file here', 'ndla-h5p-caretaker' ) ),
		'l10nremovefile'                     => esc_html( __( 'Remove file', 'ndla-h5p-caretaker' ) ),
		'l10nselectyourlanguage'             => esc_html( __( 'Select your language', 'ndla-h5p-caretaker' ) ),
		'l10nuploadprogress'                 => esc_html( __( 'Upload progress', 'ndla-h5p-caretaker' ) ),
		'l10nuploadyourh5pfile'              => esc_html( __( 'Upload your H5P file', 'ndla-h5p-caretaker' ) ),
		'l10nyourfileisbeingchecked'         => esc_html( __( 'Your file is being checked', 'ndla-h5p-caretaker' ) ),
		'l10nyourfilewascheckedsuccessfully' => esc_html( __( 'Your file check was completed', 'ndla-h5p-caretaker' ) ),
		'l10ntotalmessages'                  => esc_html( __( 'Total messages', 'ndla-h5p-caretaker' ) ),
		'l10nissues'                         => esc_html( __( 'issues', 'ndla-h5p-caretaker' ) ),
		'l10nresults'                        => esc_html( __( 'results', 'ndla-h5p-caretaker' ) ),
		'l10nfilterby'                       => esc_html( __( 'Filter by', 'ndla-h5p-caretaker' ) ),
		'l10ngroupby'                        => esc_html( __( 'Group by', 'ndla-h5p-caretaker' ) ),
		'l10ndownload'                       => esc_html( __( 'Download', 'ndla-h5p-caretaker' ) ),
		'l10nshowdetails'                    => esc_html( __( 'Show details', 'ndla-h5p-caretaker' ) ),
		'l10nhidedetails'                    => esc_html( __( 'Hide details', 'ndla-h5p-caretaker' ) ),
		'l10nexpandallmessages'              => esc_html( __( 'Expand all messages', 'ndla-h5p-caretaker' ) ),
		'l10ncollapseallmessages'            => esc_html( __( 'Collapse all messages', 'ndla-h5p-caretaker' ) ),
		'l10nallfilteredout'                 => esc_html( __( 'All messages have been filtered out by content.', 'ndla-h5p-caretaker' ) ),
		'l10nreporttitletemplate'            => esc_html( __( 'H5P Caretaker report for @title', 'ndla-h5p-caretaker' ) ),
		'l10ncontentfilter'                  => esc_html( __( 'Content type filter', 'ndla-h5p-caretaker' ) ),
		'l10nshowall'                        => esc_html( __( 'Show all', 'ndla-h5p-caretaker' ) ),
		'l10nshowselected'                   => esc_html( __( 'Various selected contents', 'ndla-h5p-caretaker' ) ),
		'l10nshownone'                       => esc_html( __( 'Show none', 'ndla-h5p-caretaker' ) ),
		'l10nfilterbycontent'                => esc_html( __( 'Filter by content:', 'ndla-h5p-caretaker' ) ),
		'l10nreset'                          => esc_html( __( 'Reset', 'ndla-h5p-caretaker' ) ),
		'l10nunknownerror'                   => esc_html( __( 'Something went wrong, but I dunno what, sorry!', 'ndla-h5p-caretaker' ) ),
		'l10ncheckserverlog'                 => esc_html( __( 'Please check the server log.', 'ndla-h5p-caretaker' ) ),
	);

	$mustache = new Mustache_Engine();

	/*
	 * We have escaped every value passed to mustache and the template does not contain JavaScript except for setting
	 * variables that have been escaped. We can't use wp_enqueue_script/wp_localize_script here, because we're not
	 * in a WordPress context but merely rendering the template that is then used by the H5P Caretaker client.
	 */
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo $mustache->render(
		$template,
		// Every single value in the render data has been escaped.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		$render_data
	);

	exit(); // Ensure no other content is loaded.
}

/**
 * Get the available locales for the select language dropdown.
 * Note that this escapes all the values, so they can be safely used in the template.
 *
 * @param string $locale The current locale to set selected.
 */
function get_select_language_locales( $locale ) {
	$available_locales = LocaleUtils::get_available_locales();
	$locales_lookup    = array_combine(
		$available_locales,
		array_map( '\Locale::getDisplayLanguage', $available_locales, $available_locales )
	);
	asort( $locales_lookup );

	return array_map(
		function ( $available_locale ) use ( $locale, $locales_lookup ) {
			return array(
				'locale'   => esc_attr( $available_locale ),
				'name'     => esc_html( ucfirst( $locales_lookup[ $available_locale ] ) ),
				'selected' => $available_locale === $locale,
			);
		},
		$available_locales
	);
}
