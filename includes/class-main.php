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
		add_rewrite_rule( '^' . $url . '-clean-up/?$', 'index.php?custom_page=' . $url . '-clean-up', 'top' );
		flush_rewrite_rules();

		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';
		$task = filter_input( INPUT_GET, 'task', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';
		$id   = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';
		if ( 'h5p' === $page && 'show' === $task ) {
			add_action(
				'admin_enqueue_scripts',
				function () use ( $id ) {
					$this->inject_caretaker_button( $id );
				}
			);
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
	 * Inject the caretaker button into the H5P content editor.
	 *
	 * @param int $h5p_id The ID of the H5P content.
	 */
	public function inject_caretaker_button( $h5p_id ) {
		if ( ! isset( $h5p_id ) ) {
			return;
		}

		$caretaker_url = $this->build_url( array( 'id' => $h5p_id ) );

		?>
		<script type="text/javascript">
			document.addEventListener('DOMContentLoaded', function() {
				const lastButton = document.querySelector('.wrap > h2 > a:last-of-type');
				if (!lastButton) {
					return;
				}

				const caretakerButton = document.createElement('a');
				caretakerButton.href = '<?php echo esc_url( $caretaker_url, null, 'not_display' ); ?>';
				// The margin is not consistent for some reason, temporary workaround.
				caretakerButton.style.marginLeft = '10px';
				caretakerButton.target = '_blank';
				caretakerButton.classList.add('add-new-h2');
				caretakerButton.textContent = '<?php echo esc_html( __( 'H5P Caretaker', 'ndla-h5p-caretaker' ) ); ?>';
				lastButton.parentNode.insertBefore(caretakerButton, lastButton.nextSibling);
			});
		</script>
		<?php
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
