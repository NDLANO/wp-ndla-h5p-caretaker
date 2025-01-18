<?php
/**
 * Fetch analysis for given file from H5P caretaker.
 *
 * @package NDLAH5PCARETAKER
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
  // phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( Options::get_visibility() !== 'public' && ! current_user_can( 'use-h5p-caretaker' ) ) {
		// Redirect to the dashboard or display an error message.
		wp_die( esc_html( __( 'You do not have sufficient permissions to access this page.', 'NDLAH5PCARETAKER' ) ) );
	}

	header( 'Content-Type: application/json; charset=utf-8' );

	$max_file_size = convert_to_bytes( min( ini_get( 'post_max_size' ), ini_get( 'upload_max_filesize' ) ) );

	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		done( 405, __( 'Method Not Allowed', 'NDLAH5PCARETAKER' ) );
	}

	// We're not in a WordPress context, so we can't use the nonce verification.
  // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! isset( $_FILES['file'] ) ) {
		done(
			422,
			sprintf(
				// translators: %s: The maximum file size in kilobytes.
				__( 'It seems that no file was provided or it exceeds the file upload size limit of %s KB.', 'NDLAH5PCARETAKER' ),
				$max_file_size / 1024
			)
		);
	}

	// We're not in a WordPress context, so we can't use the nonce verification.
  // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$file = array_map( 'sanitize_text_field', $_FILES['file'] );

	if ( strval( UPLOAD_ERR_OK ) !== $file['error'] ) {
		done( 500, __( 'Something went wrong with the file upload, but I dunno what.', 'NDLAH5PCARETAKER' ) );
	}

	if ( intval( $file['size'] ) > $max_file_size ) {
		done(
			413,
			sprintf(
			// translators: %s: The maximum file size in kilobytes.
				__( 'The file is larger than the allowed maximum file size of %s KB.', 'NDLAH5PCARETAKER' ),
				$max_file_size / 1024
			)
		);
	}

	$upload_dir = wp_upload_dir( null, true )['basedir'] . DIRECTORY_SEPARATOR . 'h5p-caretaker';
	global $wp_filesystem;
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

	// We're not in a WordPress context, so we can't use the nonce verification.
  // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
	if ( isset( $code ) ) {
		http_response_code( $code );
	}
	
	if ( isset( $message ) ) {
		// WordPress does not trust $message which is already a json encoded string.
		echo wp_json_encode( json_decode( $message ) );
	}

	exit();
}
