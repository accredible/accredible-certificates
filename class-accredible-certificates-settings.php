<?php
/**
 * Settings page for the Accredible Certificates plugin.
 *
 * @package    Accredible_Certificates
 */

defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'Accredible_Certificates_Settings' ) ) {
	/**
	 * Class for managing the plugin's settings page.
	 *
	 * @package    Accredible_Certificates
	 * @subpackage Accredible_Certificates/admin
	 */
	class Accredible_Certificates_Settings {

		/**
		 * Construct the plugin object.
		 */
		public function __construct() {
			// Register actions.
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
			add_action( 'admin_menu', array( &$this, 'add_menu' ) );
		} // END public function __construct

		/**
		 * Hook into WP's admin_init action hook.
		 */
		public function admin_init() {
			// Register your plugin's settings.
			$args = array(
				'type'              => 'string',
				'description'       => 'API Key for Accredible Credentials API',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			);
			register_setting( 'accredible_certificates-group', 'api_key', $args );

			// Add your settings section.
			add_settings_section(
				'accredible_certificates-section',
				'Accredible Credentials API Settings',
				array( &$this, 'settings_section_accredible_certificates' ),
				'accredible_certificates'
			);

			// Add your setting's fields.
			add_settings_field(
				'accredible_certificates-api_key',
				'API Key',
				array( &$this, 'settings_field_input_text' ),
				'accredible_certificates',
				'accredible_certificates-section',
				array(
					'field' => 'api_key',
				)
			);

			// Possibly do additional admin_init tasks.
		} // END public static function activate

		/**
		 * Settings section callback.
		 */
		public function settings_section_accredible_certificates() {
			// Think of this as help text for the section.
			echo 'Enter your API key below:';
		}

		/**
		 * This function provides text inputs for settings fields.
		 *
		 * @param array $args The arguments for the settings field.
		 */
		public function settings_field_input_text( $args ) {
			// Get the field name from the $args array.
			$field = $args['field'];
			// Get the value of this setting and escape it to prevent xss.
			$value = get_option( $field );
			// Echo a proper input type="text".
			printf(
				'<input type="text" name="%s" id="%s" value="%s" />',
				esc_attr( $field ),
				esc_attr( $field ),
				esc_attr( $value )
			);
		}

		/**
		 * This function provides checkbox inputs for settings fields.
		 *
		 * @param array $args The arguments for the settings field.
		 */
		public function settings_field_checkbox( $args ) {
			// Get the field name from the $args array.
			$field = $args['field'];
			// Get the value of this setting.
			$value = get_option( $field );

			printf(
				'<input type="checkbox" name="%s" id="%s" value="1"%s />',
				esc_attr( $field ),
				esc_attr( $field ),
				checked( 1, $value, false )
			);
		}

		/**
		 * Add a menu for the plugin's settings page.
		 */
		public function add_menu() {
			// Add a page to manage this plugin's settings.
			add_options_page(
				'Accredible Certificates Settings',
				'Accredible Certificates',
				'manage_options',
				'accredible_certificates',
				array( &$this, 'plugin_settings_page' )
			);
		}

		/**
		 * Menu Callback.
		 */
		public function plugin_settings_page() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html( 'You do not have sufficient permissions to access this page.' ) );
			}

			// Render the settings template.
			include sprintf( '%s/templates/settings.php', __DIR__ );
		}
	}
}
