<?php
/**
 * Plugin Name: NDLA's H5P Caretaker
 * Description: A plugin to allow checking H5P content for issues.
 * Text Domain: ndla-h5p-caretaker
 * Domain Path: /languages
 * Version: 1.1.1
 * Author: NDLA, Oliver Tacke
 * License: MIT
 *
 * @package ndla-h5p-caretaker
 */

namespace NDLAH5PCARETAKER;

// as suggested by the WordPress community.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-localeutils.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-options.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'class-main.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'render-index.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'upload.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clean-up.php';

if ( ! function_exists( 'WP_Filesystem' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

WP_Filesystem();

if ( ! defined( 'NDLAH5PCARETAKER_VERSION' ) ) {
	define( 'NDLAH5PCARETAKER_VERSION', '1.1.1' );
}

/**
 * Main plugin class.
 *
 * @return object NDLAH5PCARETAKER
 */
function ndla_h5p_caretaker() {
	register_activation_hook( __FILE__, 'NDLAH5PCARETAKER\on_activation' );
	register_deactivation_hook( __FILE__, 'NDLAH5PCARETAKER\on_deactivation' );
	register_uninstall_hook( __FILE__, 'NDLAH5PCARETAKER\on_uninstall' );

	add_action( 'plugins_loaded', 'NDLAH5PCARETAKER\update' );

	return new Main();
}

ndla_h5p_caretaker();

/**
 * Handle plugin activation.
 */
function on_activation() {
	Options::set_defaults();
	add_capabilities();
}

	/**
	 * Handle plugin deactivation.
	 */
function on_deactivation() {
	flush_rewrite_rules();
}

	/**
	 * Handle plugin uninstallation.
	 */
function on_uninstall() {
	Options::delete_options();
	flush_rewrite_rules();

	remove_capabilities();
	remove_directories();
}

/**
 * Update plugin.
 */
function update() {
	if ( NDLAH5PCARETAKER_VERSION === get_option( 'ndlah5pcaretaker_version' ) ) {
		return;
	}

	clean_up_js_dir();

	update_option( 'ndlah5pcaretaker_version', NDLAH5PCARETAKER_VERSION );
}

/**
 * Clean up JS directory.
 */
function clean_up_js_dir() {
	$js_dir = plugin_dir_path( __FILE__ ) . implode(
		DIRECTORY_SEPARATOR,
		array( 'js', 'h5p-caretaker-client', 'dist', '@explorendla', '' )
	);

	foreach ( array( 'h5p-caretaker-client-*.js', 'h5p-caretaker-client-*.css' ) as $pattern ) {
		clean_up_by_pattern( $js_dir, $pattern );
	}
}

/**
 * Clean up files by pattern.
 * Assumes a pattern placeholder * that represents a semantic version.
 *
 * @param string $dir Directory to clean up.
 * @param string $pattern Pattern to match files.
 */
function clean_up_by_pattern( $dir, $pattern ) {
	$files = glob( $dir . $pattern );
	if ( ! $files ) {
		return;
	}

	$latest_file    = '';
	$latest_version = 0;

	foreach ( $files as $file ) {
		$version = preg_replace( '/[^0-9]/', '', basename( $file ) );
		if ( $version > $latest_version ) {
			$latest_version = $version;
			$latest_file    = $file;
		}
	}

	foreach ( $files as $file ) {
		if ( $file !== $latest_file ) {
			wp_delete_file( $file );
		}
	}
}

/**
 * Add default capabilities.
 */
function add_capabilities() {
	// Add capabilities.
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	foreach ( $all_roles as $role_name => $role_info ) {
		$role = get_role( $role_name );

		// Use the capability to edit H5P contents as a base.
		map_capability( $role, $role_info, 'edit_h5p_contents', 'use-h5p-caretaker' );
	}
}

/**
 * Remove default capabilities.
 */
function remove_capabilities() {
	// Remove capabilities.
	global $wp_roles;

	$all_roles = $wp_roles->roles;
	foreach ( $all_roles as $role_name => $role_info ) {
		$role = get_role( $role_name );

		if ( isset( $role_info['capabilities']['use-h5p-caretaker'] ) ) {
			$role->remove_cap( 'use-h5p-caretaker' );
		}
	}
}

/**
 * Make sure that a role has or hasn't the provided capability depending on existing roles.
 *
 * @param stdClass     $role Role object.
 * @param array        $role_info Role information.
 * @param string|array $existing_cap Existing capability.
 * @param string       $new_cap New capability.
 */
function map_capability( $role, $role_info, $existing_cap, $new_cap ) {
	if ( isset( $role_info['capabilities'][ $new_cap ] ) ) {
		// Already has new cap.
		if ( ! has_capability( $role_info['capabilities'], $existing_cap ) ) {
			// But shouldn't have it!
			$role->remove_cap( $new_cap );
		}
	} elseif ( has_capability( $role_info['capabilities'], $existing_cap ) ) {
		// Should have new cap.
		$role->add_cap( $new_cap );
	}
}

/**
 * Check that role has the needed capabilities.
 *
 * @param array        $role_capabilities Role capabilities.
 * @param string|array $capability Capabilities to check for.
 *
 * @return bool True, if role has capability, else false.
 */
function has_capability( $role_capabilities, $capability ) {
	$capabilities = (array) $capability;

	foreach ( $capabilities as $cap ) {
		if ( ! isset( $role_capabilities[ $cap ] ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Remove tmp and cache directories.
 */
function remove_directories() {
	global $wp_filesystem;

	$upload_dir = wp_upload_dir( null, true )['basedir'] . DIRECTORY_SEPARATOR . 'h5p-caretaker';
	$wp_filesystem->delete( $upload_dir, true );
}
