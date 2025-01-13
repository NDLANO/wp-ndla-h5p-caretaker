<?php
/**
 * Handle the custom page display logic.
 *
 * @package NDLAH5PCARETAKER
 */

namespace NDLAH5PCARETAKER;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the page index.
 */
function render_page_index() {
  // phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( Options::get_visibility() !== 'public' && ! current_user_can( 'use-h5p-caretaker' ) ) {
		// Redirect to the dashboard or display an error message.
		wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'NDLAH5PCARETAKER' ) ) );
	}

	$http_accept_language = get_http_accept_language();
	$get_locale           = get_locale_from_query();

	// Prevent snooping on H5P content.
  // phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( current_user_can( 'use-h5p-caretaker' ) ) {
		$h5p_id = get_id_from_query();
	}

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
		$dist_dir = $base_dir . 'node_modules' . DIRECTORY_SEPARATOR . 'h5p-caretaker-client' . DIRECTORY_SEPARATOR . 'dist';
		$dist_url = plugin_dir_url( __FILE__ ) . '../node_modules/h5p-caretaker-client/dist';

		render_html(
			$dist_url . '/' . get_file_by_pattern( $dist_dir, 'h5p-caretaker-client-*.js' ),
			$dist_url . '/' . get_file_by_pattern( $dist_dir, 'h5p-caretaker-client-*.css' ),
			$locale,
			$path,
			$export_needs_to_be_removed ? $h5p_id : false,
		);
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

	// Validate and filter against main library semantics.
	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$validator = new \H5PContentValidator( $core->h5pF, $core );
	$validator->validateLibrary(
		$params,
		(object) array( 'options' => array( $params->library ) )
	);

	// Handle addons.
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

  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$core->h5pF->deleteLibraryUsage( $content['id'] );
  // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$core->h5pF->saveLibraryUsage( $content['id'], $content['dependencies'] );

	if ( ! $content['slug'] ) {
		$content['slug'] = $this->generateContentSlug( $content );

		// Remove old export file.
		$core->fs->deleteExport( $content['id'] . '.h5p' );
	}

	// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	$exporter            = new \H5PExport( $core->h5pF, $core );
	$content['filtered'] = $params;

	$exporter->createExportFile( $content );

	// Cache.
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
  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['locale'] ) ? sanitize_text_field( wp_unslash( $_GET['locale'] ) ) : '';
}

/**
 * Get the ID from the query.
 *
 * @return string The ID from the query.
 */
function get_id_from_query() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
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
 * TODO: Think about a templating engine. Mustache? Simple, but we do not need much here - and moodle uses it, too.
 *
 * @param string $file_js The filename of the JavaScript file.
 * @param string $file_css The filename of the CSS file.
 * @param string $locale The locale to use.
 * @param string $path The path to the H5P file if preset.
 * @param string $export_remove_id The ID of the H5P content to remove the export for.
 */
function render_html( $file_js, $file_css, $locale, $path, $export_remove_id = false ) {
	header( 'Content-Type: text/html; charset=utf-8' );
	?>
	<!DOCTYPE html>
	<html lang="<?php echo esc_attr( str_replace( '_', '-', $locale ) ); ?>">
	<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php esc_html_e( 'H5P Caretaker Reference Implementation', 'NDLAH5PCARETAKER' ); ?></title>
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet ?>
	<link rel="stylesheet" href="<?php echo esc_url( $file_css ); ?>" />
    <?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
	<script type="module" src="<?php echo esc_url( $file_js ); ?>"></script>
	<script>
		window.H5P_CARETAKER_PATH = <?php echo wp_json_encode( $path ); ?>;
	</script>
	</head>

	<body class="h5p-caretaker">
	<header class="header">
		<h1 class="title main-color"><?php echo esc_html( __( 'H5P Caretaker', 'NDLAH5PCARETAKER' ) ); ?></h1>
		<?php render_select_language( $locale ); ?>
	</header>

	<main class="page">
		<div class="block background-dark">
		<div class="centered-row block-visible">
			<p class="main-color"><?php echo esc_html( __( 'Take care of your H5P', 'NDLAH5PCARETAKER' ) ); ?></p>
			<h2 class="title"><?php echo esc_html( __( 'Check your H5P file for improvements', 'NDLAH5PCARETAKER' ) ); ?></h2>
			<p>
			<?php echo esc_html( __( 'Uncover accessibility issues, missing information and best practices that can help you improve your H5P content.', 'NDLAH5PCARETAKER' ) ); ?>
			</p>
			<?php
			if ( ! empty( Options::get_intro() ) ) {
				echo '<p>';
				echo wp_kses_post( Options::get_intro() );
				echo '<p>';
			}
			?>

			<div class="dropzone">
			<!-- Will be filled by dropzone.js -->
			</div>

		</div>
		</div>

		<div class="block background-dark">
		<div class="centered-row">
			<div class="filter-tree">
			<!-- Will be filled by content-filter.js -->
			</div>
		</div>
		</div>

		<div class="block background-light">
		<div class="output centered-row">
			<!-- <div class="output">
			<!-- Will be filled by main.js -->
			<!-- </div> -->
		</div>
		</div>

		<script>
			// If there's a file to upload, do so after initialization.
			const handleInitialized = () => {
				const url = '<?php echo esc_url( $path ); ?>';
				if (!url) {
					return;
				}
				window.h5pcaretaker.uploadByURL(url);
			};

			// If export file needs to be removed, do so after upload has ended.
			const handleUploadEnded = () => {
				const exportRemoveId = '<?php echo esc_js( $export_remove_id ); ?>';
				console.log(exportRemoveId);

				if (!exportRemoveId) {
					return;
				}

				const formData = new FormData();
				formData.set('id', exportRemoveId);

				const xhr = new XMLHttpRequest();
				xhr.open('POST', '<?php echo esc_url( home_url( '/' . Options::get_url() . '-clean-up' ) ); ?>', true);
				xhr.send(formData);
			};

			document.addEventListener('DOMContentLoaded', () => {
				window.h5pcaretaker = new window.H5PCaretaker(
					{
						endpoint: '<?php echo esc_url( home_url( '/' . Options::get_url() . '-upload' ) ); ?>',
						l10n: {
							orDragTheFileHere: '<?php echo esc_html( __( 'or drag the file here' ) ); ?>',
							removeFile: '<?php echo esc_html( __( 'Remove file' ) ); ?>',
							selectYourLanguage: '<?php echo esc_html( __( 'Select your language' ) ); ?>',
							uploadProgress: '<?php echo esc_html( __( 'Upload progress' ) ); ?>',
							uploadYourH5Pfile: '<?php echo esc_html( __( 'Upload your H5P file' ) ); ?>',
							yourFileIsBeingChecked: '<?php echo esc_html( __( 'Your file is being checked' ) ); ?>',
							yourFileWasCheckedSuccessfully: '<?php echo esc_html( __( 'Your file was checked successfully' ) ); ?>',
							totalMessages: '<?php echo esc_html( __( 'Total messages' ) ); ?>',
							issues: '<?php echo esc_html( __( 'issues' ) ); ?>',
							results: '<?php echo esc_html( __( 'results' ) ); ?>',
							filterBy: '<?php echo esc_html( __( 'Filter by' ) ); ?>',
							groupBy: '<?php echo esc_html( __( 'Group by' ) ); ?>',
							download: '<?php echo esc_html( __( 'Download' ) ); ?>',
							expandAllMessages: '<?php echo esc_html( __( 'Expand all messages' ) ); ?>',
							collapseAllMessages: '<?php echo esc_html( __( 'Collapse all messages' ) ); ?>',
							allFilteredOut: '<?php echo esc_html( __( 'All messages have been filtered out by content.' ) ); ?>',
							reportTitleTemplate: '<?php echo esc_html( __( 'H5P Caretaker report for @title' ) ); ?>',
							contentFilter: '<?php echo esc_html( __( 'Content type filter' ) ); ?>',
							showAll: '<?php echo esc_html( __( 'Show all' ) ); ?>',
							showSelected: '<?php echo esc_html( __( 'Various selected contents' ) ); ?>',
							showNone: '<?php echo esc_html( __( 'Show none' ) ); ?>',
							filterByContent: '<?php echo esc_html( __( 'Filter by content:' ) ); ?>',
							reset: '<?php echo esc_html( __( 'Reset' ) ); ?>',
						},
					},
					{
						onInitialized: () => {
							handleInitialized();
						},
						onUploadEnded: () => {
							handleUploadEnded();
						}
					}
				);
			});
		</script>
	</main>

	<?php
	if ( ! empty( Options::get_outro() ) ) {
		?>
		<footer class="footer">
		<?php echo wp_kses_post( Options::get_outro() ); ?>
		</footer>
		<?php
	}
	?>
	</body>
	</html>
	<?php

	exit(); // Ensure no other content is loaded.
}

/**
 * Render the language selection dropdown.
 *
 * @param string $locale The current locale to set selected.
 */
function render_select_language( $locale ) {
	echo '<select class="select-language" name="language" id="select-language" data-locale-key="locale">';
	$available_locales = LocaleUtils::get_available_locales();
	$locales_lookup    = array_combine(
		$available_locales,
		array_map( '\Locale::getDisplayLanguage', $available_locales, $available_locales )
	);
	asort( $locales_lookup );

	foreach ( $locales_lookup as $available_locale => $native_locale_name ) {
		$selected = ( $available_locale === $locale ) ? 'selected' : '';
		echo '<option value="' . esc_attr( $available_locale ) . '" ' . esc_attr( $selected ) . '>'
			. esc_html( $native_locale_name )
			. '</option>';
	}
	echo '</select>';
}
