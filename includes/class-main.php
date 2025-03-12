<?php
/**
 * Main plugin class file.
 *
 * @package ndla-h5p-caretaker
 */

namespace NDLAH5PCARETAKER;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class Main {
	/**
	 * The current H5P ID.
	 *
	 * @var string $h5p_id The H5P ID.
	 */
	private $h5p_id;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_requests' ) );
		add_action( 'admin_menu', array( $this, 'add_tools_menu_entry' ) );
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
		add_action( 'init', array( $this, 'initialize' ) );
	}

		/**
		 * Load the text domain for internationalization.
		 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'ndla-h5p-caretaker',
			false,
			basename( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'languages'
		);
	}

		/**
		 * Method to add a submenu item to the Tools menu
		 */
	public function add_tools_menu_entry() {
		$caretaker_url = $this->build_url();

		add_submenu_page(
			'tools.php',      // Parent menu slug.
			'H5P Caretaker',  // Page title.
			'H5P Caretaker',  // Menu title.
			'manage_options', // Capability required to access this menu.
			$caretaker_url,   // Menu slug.
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'override_tool_menu_item' ) );
	}

		/**
		 * Add the rewrite rule for the custom page.
		 */
	public function initialize() {
		new Options();

		$url = Options::get_url();

		add_rewrite_rule( '^' . $url . '/?$', 'index.php?custom_page=' . $url, 'top' );
		add_rewrite_rule( '^' . $url . '-upload/?$', 'index.php?custom_page=' . $url . '-upload', 'top' );
		add_rewrite_rule( '^' . $url . '-clean-up/?$', 'index.php?custom_page=' . $url . '-clean-up', 'top' );
		flush_rewrite_rules();

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';
		$task = filter_input( INPUT_GET, 'task', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';

		$this->h5p_id = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';

		if ( 'h5p' === $page && 'show' === $task ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'inject_caretaker_button' ) );
		}
	}

	/**
	 * Build the URL for the caretaker page.
	 *
	 * @param array $gets Array of GET parameters to set.
	 * @return string The URL.
	 */
	private function build_url( $gets = array() ) {
		if ( ! isset( $gets['locale'] ) ) {
			$user           = wp_get_current_user();
			$user_locale    = get_user_meta( $user->ID, 'locale', true );
			$gets['locale'] = $user_locale ? $user_locale : get_locale();
		}

		$query_string = array_map(
			function ( $key, $value ) {
				return $key . '=' . $value;
			},
			array_keys( $gets ),
			$gets
		);

		return site_url( Options::get_url() ) . '?' . implode( '&', $query_string );
	}

	/**
	 * Enqueue the custom script for the H5P Caretaker button.
	 */
	public function inject_caretaker_button() {
		wp_register_script(
			'inject_caretaker_button',
			plugins_url( '/../js/inject-caretaker-button.js', __FILE__ ),
			array(),
			NDLAH5PCARETAKER_VERSION,
			true
		);
		wp_enqueue_script( 'inject_caretaker_button' );

		$data = array(
			'url'   => esc_url( $this->build_url( array( 'id' => $this->h5p_id ) ), null, 'not_display' ),
			'label' => esc_html( __( 'H5P Caretaker', 'ndla-h5p-caretaker' ) ),
		);
		wp_localize_script( 'inject_caretaker_button', 'H5PCaretakerButton', $data );
	}

	/**
	 * Enqueue the custom script for the H5P Caretaker tool menu item.
	 */
	public function override_tool_menu_item() {
		wp_register_script(
			'override_tool_menu_item',
			plugins_url( '/../js/override-tool-menu-item.js', __FILE__ ),
			array(),
			NDLAH5PCARETAKER_VERSION,
			true
		);
		wp_enqueue_script( 'override_tool_menu_item' );

		$data = array(
			'url' => esc_attr( Options::get_url() ),
		);
		wp_localize_script( 'override_tool_menu_item', 'H5PCaretakerToolMenuItem', $data );
	}

	/**
	 * Register the custom query variable.
	 *
	 * @param array $vars Existing query variables.
	 * @return array Updated query variables.
	 */
	public function register_query_vars( $vars ) {
		$vars[] = 'custom_page';
		return $vars;
	}

	/**
	 * Render the content for each custom page based on the query variable.
	 */
	public function handle_requests() {
		$custom_page = get_query_var( 'custom_page' );
		if ( Options::get_url() === $custom_page ) {
			render_page_index();
		} elseif ( Options::get_url() . '-upload' === $custom_page ) {
			fetch_analysis();
		} elseif ( Options::get_url() . '-clean-up' === $custom_page ) {
			clean_up_export_file();
		}
	}
}
