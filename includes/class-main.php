<?php
/**
 * Main plugin class file.
 *
 * @package NDLAH5PCARETAKER
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
		 * Constructor.
		 */
	public function __construct() {

		register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );
		register_uninstall_hook( __FILE__, array( $this, 'on_uninstall' ) );

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
			'NDLAH5PCARETAKER',
			false,
			basename( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . 'languages'
		);
	}

		/**
		 * Method to add a submenu item to the Tools menu
		 */
	public function add_tools_menu_entry() {
		$user          = wp_get_current_user();
		$user_locale   = get_user_meta( $user->ID, 'locale', true );
		$locale        = $user_locale ? $user_locale : get_locale();
		$caretaker_url = site_url( Options::get_url() ) . '?locale=' . $locale;

		add_submenu_page(
			'tools.php',      // Parent menu slug.
			'H5P Caretaker',  // Page title.
			'H5P Caretaker',  // Menu title.
			'manage_options', // Capability required to access this menu.
			$caretaker_url,   // Menu slug.
		);

		// Hook into admin_head to add a custom script for setting the target attribute.
		add_action( 'admin_head', array( $this, 'add_target_blank_to_menu_entry' ) );
	}

		/**
		 * Add a script to set the target attribute to _blank for the H5P Caretaker menu item.
		 */
	public function add_target_blank_to_menu_entry() {
		?>
			<script type="text/javascript">
				document.addEventListener('DOMContentLoaded', function() {
				<?php //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				const menuItem = document.querySelector('a[href*="<?php echo Options::get_url(); ?>"]');
				if (menuItem) {
					menuItem.setAttribute('target', '_blank');
				}
			});
			</script>
		<?php
	}

		/**
		 * Add the rewrite rule for the custom page.
		 */
	public function initialize() {
		new Options();

		$url = Options::get_url();

		add_rewrite_rule( '^' . $url . '/?$', 'index.php?custom_page=' . $url, 'top' );
		add_rewrite_rule( '^' . $url . '-upload/?$', 'index.php?custom_page=' . $url . '-upload', 'top' );
		flush_rewrite_rules();
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
		}
	}

		/**
		 * Handle plugin activation.
		 */
	public function on_activation() {
		Options::set_defaults();
		$this->initialize();
	}

		/**
		 * Handle plugin deactivation.
		 */
	public function on_deactivation() {
		flush_rewrite_rules();
	}

		/**
		 * Handle plugin uninstallation.
		 */
	public function on_uninstall() {
		Options::delete_options();
		flush_rewrite_rules();
	}
}
