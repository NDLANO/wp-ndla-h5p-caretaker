<?php
/**
 * Fetch analysis for given file from H5P caretaker.
 *
 * @package ndla-h5p-caretaker
 */

namespace NDLAH5PCARETAKER;

use Ndlano\H5PCaretaker\H5PCaretaker;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetch analysis for given file from H5P caretaker.
 */
function fetch_analysis() {
	global $wp_filesystem;

	ob_start();

	// Capability is registered in ndla-h5p-caretaker.php.
  // phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( Options::get_visibility() !== 'public' && ! current_user_can( 'use-h5p-caretaker' ) ) {
		// Redirect to the dashboard or display an error message.
		wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'ndla-h5p-caretaker' ) ) );
	}

	header( 'Content-Type: application/json; charset=utf-8' );

	$max_file_size = convert_to_bytes( min( ini_get( 'post_max_size' ), ini_get( 'upload_max_filesize' ) ) );

	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		done( 405, __( 'Method Not Allowed', 'ndla-h5p-caretaker' ) );
	}

	if (
		! isset( $_POST['h5pCaretakerNonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['h5pCaretakerNonce'] ) ), 'h5p-caretaker-upload' )
	) {
		done( 403, __( 'Invalid nonce verification.', 'ndla-h5p-caretaker' ) );
	}

	if ( ! isset( $_FILES['file'] ) ) {
		done(
			422,
			sprintf(
				// translators: %s: The maximum file size in kilobytes.
				__( 'It seems that no file was provided or it exceeds the file upload size limit of %s KB.', 'ndla-h5p-caretaker' ),
				$max_file_size / 1024
			)
		);
	}

	$file = array_map( 'sanitize_text_field', $_FILES['file'] );

	if ( strval( UPLOAD_ERR_OK ) !== $file['error'] ) {
		done( 500, __( 'Something went wrong with the file upload, but I dunno what.', 'ndla-h5p-caretaker' ) );
	}

	if ( intval( $file['size'] ) > $max_file_size ) {
		done(
			413,
			sprintf(
			// translators: %s: The maximum file size in kilobytes.
				__( 'The file is larger than the allowed maximum file size of %s KB.', 'ndla-h5p-caretaker' ),
				$max_file_size / 1024
			)
		);
	}

	$upload_dir = wp_upload_dir( null, true )['basedir'] . DIRECTORY_SEPARATOR . 'h5p-caretaker';
	if ( ! file_exists( $upload_dir ) ) {
		$wp_filesystem->mkdir( $upload_dir );
	}

	$tmp_extract_dir = $upload_dir . DIRECTORY_SEPARATOR . 'uploads';
	if ( ! file_exists( $tmp_extract_dir ) ) {
		$wp_filesystem->mkdir( $tmp_extract_dir );
	}

	$cache_dir = $upload_dir . DIRECTORY_SEPARATOR . 'cache';
	if ( ! file_exists( $cache_dir ) ) {
		$wp_filesystem->mkdir( $cache_dir );
	}

	$config = array(
		'uploadsPath' => $tmp_extract_dir,
		'cachePath'   => $cache_dir,
	);

	$locale = isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : null;
	if ( isset( $locale ) ) {
		$config['locale'] = $locale;
	}

	$h5p_caretaker = new H5PCaretaker( $config );

	$analysis = $h5p_caretaker->analyze( array( 'file' => $file['tmp_name'] ) );

	if ( isset( $analysis['error'] ) ) {
		done( 422, $analysis['error'] );
	}

	done( 200, $analysis['result'] );
}

/**
 * Convert a human-readable size to bytes.
 *
 * @param string $size The human-readable size.
 */
function convert_to_bytes( $size ) {
	$unit  = substr( $size, -1 );
	$value = (int) $size;

	switch ( strtoupper( $unit ) ) {
		case 'G':
			return $value * 1024 * 1024 * 1024;
		case 'M':
			return $value * 1024 * 1024;
		case 'K':
			return $value * 1024;
		default:
			return $value;
	}
}

/**
 * Exit the script with an optional HTTP status code.
 *
 * @param int    $code    The HTTP status code to send.
 * @param string $message The message to display.
 *
 * @return void
 */
function done( $code, $message ) {
	ob_end_clean();

	if ( isset( $code ) ) {
		http_response_code( $code );
	}

	if ( isset( $message ) ) {
		// WordPress does not trust $message which is already a json encoded string.
		$decoded = json_decode( $message );
		if ( null !== $decoded ) {
			echo wp_json_encode( $decoded );
		} else {
			echo wp_json_encode( array( 'error' => esc_html( $message ) ) );
		}
	}

	exit();
}
