<?php
/**
 * Options page for the plugin.
 *
 * @package ndla-h5p-caretaker
 */

namespace NDLAH5PCARETAKER;

/**
 * Options page for the plugin.
 *
 * @package ndla-h5p-caretaker
 */
class Options {

	const DEFAULT_URL             = 'h5p-caretaker';
	const DEFAULT_VISIBILITY      = 'capability';
	const DEFAULT_INTRO_HEIGHT_PX = 320;
	const DEFAULT_OUTRO_HEIGHT_PX = 160;

	/**
	 * Option slug.
	 *
	 * @var string
	 */
	private static $option_slug = 'ndlah5pcaretaker_option';

	/**
	 * Options.
	 *
	 * @var array
	 */
	private static $options;

	/**
	 * Start up
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
	}

	/**
	 * Set defaults.
	 *
	 * @since 0.1.0
	 */
	public static function set_defaults() {
		update_option( 'ndlah5pcaretaker_version', NDLAH5PCARETAKER_VERSION );

		if ( get_option( 'ndlah5pcaretaker_defaults_set' ) ) {
			return; // No need to set defaults.
		}

		update_option( 'ndlah5pcaretaker_defaults_set', true );

		update_option(
			self::$option_slug,
			array(
				'url'        => self::DEFAULT_URL,
				'visibility' => self::DEFAULT_VISIBILITY,
				'intro'      => '',
				'outro'      => '',
			)
		);
	}

	/**
	 * Delete options.
	 */
	public static function delete_options() {
		delete_option( self::$option_slug );
		delete_site_option( self::$option_slug );
		delete_option( 'ndlah5pcaretaker_defaults_set' );
		delete_option( 'ndlah5pcaretaker_version' );
	}

	/**
	 * Add options page.
	 */
	public function add_plugin_page() {
		// This page will be under "Settings".
		add_options_page(
			'Settings Admin',
			'H5P Caretaker',
			'manage_options',
			'ndlah5pcaretaker-admin',
			array( $this, 'create_admin_page' )
		);
	}

	/**
	 * Options page callback.
	 */
	public function create_admin_page() {
		?>
		<div class="wrap">
			<h2><?php echo esc_html( __( 'H5P Caretaker', 'ndla-h5p-caretaker' ) ); ?></h2>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'ndlah5pcaretaker_option_group' );
				do_settings_sections( 'ndlah5pcaretaker-admin' );
				submit_button();
				?>
			</form>
		</div>
		<?php

		wp_register_script(
			'update_url_preview',
			plugins_url( '/../js/update-url-preview.js', __FILE__ ),
			array(),
			NDLAH5PCARETAKER_VERSION,
			true
		);
		wp_enqueue_script( 'update_url_preview' );

		$data = array(
			'prefix'      => esc_url( home_url( '/' ) ),
			'placeholder' => esc_attr( self::DEFAULT_URL ),
		);
		wp_localize_script( 'update_url_preview', 'H5PCaretakerOptions', $data );
	}

	/**
	 * Register and add settings.
	 */
	public function page_init() {
		// The `sanitize` function properly sanitizes all input.
		// phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
		register_setting(
			'ndlah5pcaretaker_option_group',
			'ndlah5pcaretaker_option',
			array( $this, 'sanitize' )
		);

		add_settings_section(
			'general_settings',
			__( 'General', 'ndla-h5p-caretaker' ),
			array( $this, 'print_general_section_info' ),
			'ndlah5pcaretaker-admin'
		);

		add_settings_field(
			'url',
			__( 'URL', 'ndla-h5p-caretaker' ),
			array( $this, 'url_callback' ),
			'ndlah5pcaretaker-admin',
			'general_settings'
		);

		add_settings_field(
			'visibility',
			__( 'Visibility', 'ndla-h5p-caretaker' ),
			array( $this, 'visibility_callback' ),
			'ndlah5pcaretaker-admin',
			'general_settings'
		);

		add_settings_field(
			'intro',
			__( 'Additional intro', 'ndla-h5p-caretaker' ),
			array( $this, 'intro_callback' ),
			'ndlah5pcaretaker-admin',
			'general_settings'
		);

		add_settings_field(
			'outro',
			__( 'Additional footer', 'ndla-h5p-caretaker' ),
			array( $this, 'outro_callback' ),
			'ndlah5pcaretaker-admin',
			'general_settings'
		);

		add_settings_field(
			'no_branding',
			__( 'Turn off branding', 'ndla-h5p-caretaker' ),
			array( $this, 'no_branding_callback' ),
			'ndlah5pcaretaker-admin',
			'general_settings'
		);
	}

	/**
	 * Sanitize each setting field as needed.
	 *
	 * @since 0.1.0
	 * @param array $input Contains all settings fields as array keys.
	 * @return array Output.
	 */
	public function sanitize( $input ) {
		$input = (array) $input;

		$new_input = array();

		$new_input['url'] = empty( $input['url'] ) ?
			self::DEFAULT_URL :
			sanitize_text_field( $input['url'] );

		$new_input['visibility'] = ( ! in_array( $input['visibility'] ?? '', array( 'public', 'capability' ), true ) ) ?
			'capability' :
			$input['visibility'];

		$new_input['intro'] = ! empty( $input['intro'] ) ?
			wp_kses_post( $input['intro'] ) :
			'';

		$new_input['outro'] = ! empty( $input['outro'] ) ?
			wp_kses_post( $input['outro'] ) :
			'';

		$new_input['no_branding'] = ! empty( $input['no_branding'] ) ?
			absint( $input['no_branding'] ) :
			0;

		return $new_input;
	}

	/**
	 * Print section text for general settings.
	 */
	public function print_general_section_info() {
	}

	/**
	 * Get url option.
	 */
	public function url_callback() {
		// I don't like this mixing of HTML and PHP, but it seems to be WordPress custom.
		?>
		<input
			name="ndlah5pcaretaker_option[url]"
			type="text"
			id="url"
			minlength="1"
			placeholder="<?php echo esc_attr( self::DEFAULT_URL ); ?>"
			value="<?php echo esc_attr( self::get_url() ); ?>"
		/>
		<p id="output-url" class="description">
			<?php
				echo esc_html(
					sprintf(
						// translators: %s: Will contain the URL that the H5P Caretaker page will be available at.
						__( 'Set the desired URL for the H5P Caretaker page. With the current value it will be available at %s.', 'ndla-h5p-caretaker' ),
						esc_url( home_url( '/' . self::get_url() ) )
					)
				);
			?>
		</p>
		<?php
	}

	/**
	 * Get visibility option.
	 */
	public function visibility_callback() {
		// I don't like this mixing of HTML and PHP, but it seems to be WordPress custom.
		?>
			<select
				name="ndlah5pcaretaker_option[visibility]"
				id="visibility"
			>
				<option value="public"<?php echo( 'public' === self::get_visibility() ? ' selected' : '' ); ?>><?php echo esc_html( __( 'Public', 'ndla-h5p-caretaker' ) ); ?></option>
				<option value="capability"<?php echo( 'capability' === self::get_visibility() ? ' selected' : '' ); ?>><?php echo esc_html( __( 'Needs capability', 'ndla-h5p-caretaker' ) ); ?></option>
			</select>
			<p class="description">
			<?php
				echo esc_html( __( 'Select whether the H5P Caretaker page should be publicly available or only to those logged in users that have the capability based on their user role.', 'ndla-h5p-caretaker' ) );
			?>
			</p>
		<?php
	}

	/**
	 * Get intro option.
	 */
	public function intro_callback() {
		wp_editor(
			self::get_intro(),
			'intro',
			array(
				'textarea_name' => 'ndlah5pcaretaker_option[intro]',
				'editor_height' => self::DEFAULT_INTRO_HEIGHT_PX,
				'media_buttons' => false,
				'teeny'         => true,
			)
		);
	}

	/**
	 * Get outro option.
	 */
	public function outro_callback() {
		wp_editor(
			self::get_outro(),
			'outro',
			array(
				'textarea_name' => 'ndlah5pcaretaker_option[outro]',
				'editor_height' => self::DEFAULT_OUTRO_HEIGHT_PX,
				'media_buttons' => false,
				'teeny'         => true,
			)
		);
	}

	/**
	 * Show the option for removing branding
	 */
	public function no_branding_callback() {
		?>
		<label for="no_branding">
		<input
			type="checkbox"
			name="ndlah5pcaretaker_option[no_branding]"
			id="no_branding"
			value="1"
			<?php
				echo isset( self::$options['no_branding'] ) ?
					checked( '1', self::$options['no_branding'], false ) :
					''
			?>
		/>
		<?php echo esc_html__( 'Turn NDLA branding off.', 'ndla-h5p-caretaker' ); ?>
		</label>
		<?php
	}

	/**
	 * Get caretaker page URL.
	 *
	 * @return string Caretaker page URL.
	 */
	public static function get_url() {
		return ( isset( self::$options['url'] ) ) ?
			self::$options['url'] :
			self::DEFAULT_URL;
	}

	/**
	 * Get caretaker page visibility.
	 *
	 * @return string Caretaker page visibility.
	 */
	public static function get_visibility() {
		return ( isset( self::$options['visibility'] ) ) ?
			self::$options['visibility'] :
			self::DEFAULT_VISIBILITY;
	}

	/**
	 * Get caretaker page intro.
	 *
	 * @return string Caretaker page intro.
	 */
	public static function get_intro() {
		return ( isset( self::$options['intro'] ) ) ?
			self::$options['intro'] :
			'';
	}

	/**
	 * Get caretaker page outro.
	 *
	 * @return string Caretaker page outro.
	 */
	public static function get_outro() {
		return ( isset( self::$options['outro'] ) ) ?
			self::$options['outro'] :
			'';
	}

	/**
	 * Get caretaker no branding setting.
	 *
	 * @return int Caretaker no branding setting.
	 */
	public static function get_no_branding() {
		return ( isset( self::$options['no_branding'] ) ) ?
			self::$options['no_branding'] :
			0;
	}

	/**
	 * Init function for the class.
	 *
	 * @since 0.1.0
	 */
	public static function init() {
		self::$options = get_option( self::$option_slug, false );
	}
}
Options::init();
