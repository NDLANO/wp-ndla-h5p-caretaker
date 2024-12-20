<?php
/**
 * Plugin Name: NDLA's H5P Caretaker
 * Description: A plugin to allow checking H5P content for issues.
 * Text Domain: NDLAH5PCARETAKER
 * Domain Path: /languages
 * Version: 1.0
 * Author: NDLA, Oliver Tacke
 * License: MIT
 *
 * @package NDLAH5PCARETAKER
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
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'fetch-analysis.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clean-up.php';

if ( ! defined( 'NDLAH5PCARETAKER_VERSION' ) ) {
	define( 'NDLAH5PCARETAKER_VERSION', '1.0.0' );
}

/**
 * Main plugin class.
 *
 * @return object NDLAH5PCARETAKER
 */
function ndla_h5p_caretaker() {
	return new Main();
}

ndla_h5p_caretaker();
