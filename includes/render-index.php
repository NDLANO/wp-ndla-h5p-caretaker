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
			$locale
		);
	}
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
 * @param string $file_js The filename of the JavaScript file.
 * @param string $file_css The filename of the CSS file.
 * @param string $locale The locale to use.
 */
function render_html( $file_js, $file_css, $locale ) {
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
		window.H5P_CARETAKER_L10N = {
		orDragTheFileHere: "<?php echo esc_js( __( 'or drag the file here', 'NDLAH5PCARETAKER' ) ); ?>",
		removeFile: "<?php echo esc_js( __( 'Remove file', 'NDLAH5PCARETAKER' ) ); ?>",
		selectYourLanguage: "<?php echo esc_js( __( 'Select your language', 'NDLAH5PCARETAKER' ) ); ?>",
		uploadProgress: "<?php echo esc_js( __( 'Upload progress', 'NDLAH5PCARETAKER' ) ); ?>",
		uploadYourH5Pfile: "<?php echo esc_js( __( 'Upload your H5P file', 'NDLAH5PCARETAKER' ) ); ?>",
		yourFileIsBeingChecked: "<?php echo esc_js( __( 'Your file is being checked', 'NDLAH5PCARETAKER' ) ); ?>",
		yourFileWasCheckedSuccessfully: "<?php echo esc_js( __( 'Your file was checked successfully', 'NDLAH5PCARETAKER' ) ); ?>",
		totalMessages: "<?php echo esc_js( __( 'Total messages', 'NDLAH5PCARETAKER' ) ); ?>",
		issues: "<?php echo esc_js( __( 'issues', 'NDLAH5PCARETAKER' ) ); ?>",
		results: "<?php echo esc_js( __( 'results', 'NDLAH5PCARETAKER' ) ); ?>",
		filterBy: "<?php echo esc_js( __( 'Filter by', 'NDLAH5PCARETAKER' ) ); ?>",
		groupBy: "<?php echo esc_js( __( 'Group by', 'NDLAH5PCARETAKER' ) ); ?>",
		download: "<?php echo esc_js( __( 'Download', 'NDLAH5PCARETAKER' ) ); ?>",
		expandAllMessages: "<?php echo esc_js( __( 'Expand all messages', 'NDLAH5PCARETAKER' ) ); ?>",
		collapseAllMessages: "<?php echo esc_js( __( 'Collapse all messages', 'NDLAH5PCARETAKER' ) ); ?>",
		allFilteredOut: "<?php echo esc_js( __( 'All messages have been filtered out by content.', 'NDLAH5PCARETAKER' ) ); ?>",
		reportTitleTemplate: "<?php echo esc_js( __( 'H5P Caretaker report for @title', 'NDLAH5PCARETAKER' ) ); ?>",
		contentFilter: "<?php echo esc_js( __( 'Content type filter', 'NDLAH5PCARETAKER' ) ); ?>",
		showAll: "<?php echo esc_js( __( 'Show all', 'NDLAH5PCARETAKER' ) ); ?>",
		showSelected: "<?php echo esc_js( __( 'Various selected contents', 'NDLAH5PCARETAKER' ) ); ?>",
		showNone: "<?php echo esc_js( __( 'Show none', 'NDLAH5PCARETAKER' ) ); ?>",
		filterByContent: "<?php echo esc_js( __( 'Filter by content:', 'NDLAH5PCARETAKER' ) ); ?>",
		reset: "<?php echo esc_js( __( 'Reset', 'NDLAH5PCARETAKER' ) ); ?>",
		}
	</script>
	</head>

	<body class="h5p-caretaker" data-upload-endpoint="<?php echo esc_url( home_url( '/' . Options::get_url() . '-upload' ) ); ?>">
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
			<?php echo esc_html( __( 'Upload your H5P file and uncover accessibility issues, missing information and best practices that can help you improve your H5P content.', 'NDLAH5PCARETAKER' ) ); ?>
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

	exit; // Ensure no other content is loaded.
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
