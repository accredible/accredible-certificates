<?php
/**
 * Accredible Certificates & Open Badges
 *
 * @package Accredible_Certificates
 *
 * Plugin Name: Accredible Certificates & Open Badges
 * Plugin URI: https://github.com/accredible/accredible-certificates
 * Description: Certificates, open badges and blockchain credentials. Create, update and manage them on your WordPress site.
 * Version: 1.4.9
 * Author: Accredible
 * Author URI: https://www.accredible.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || die;

define( 'ACCREDIBLE_CERTIFICATES_VERSION', '1.4.9' );
define( 'ACCREDIBLE_CERTIFICATES_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACCREDIBLE_CERTIFICATES_SCRIPT_VERSION_TOKEN', ACCREDIBLE_CERTIFICATES_VERSION );

if ( ! defined( 'ACCREDIBLE_CERTIFICATES_PLUGIN_URL' ) ) {
	$accredible_certificates_plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . basename( __DIR__ ) );
	$accredible_certificates_plugin_url = str_replace( array( 'https://', 'http://' ), array( '//', '//' ), $accredible_certificates_plugin_url );

	/**
	 * Define Accredible - Set the plugin relative URL.
	 *
	 * Will be set based on the WordPress define `WP_PLUGIN_URL`.
	 *
	 * @uses WP_PLUGIN_URL
	 *
	 * @var string $accredible_certificates_plugin_url URL to plugin install directory.
	 */
	define( 'ACCREDIBLE_CERTIFICATES_PLUGIN_URL', $accredible_certificates_plugin_url );
}

// For composer dependencies.
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use ACMS\Api;

require_once ACCREDIBLE_CERTIFICATES_PLUGIN_PATH . 'class-accredible-widget.php'; // Require Widget for credential display.
require_once ACCREDIBLE_CERTIFICATES_PLUGIN_PATH . 'class-accredible-certificates-settings.php'; // Require Settings.

if ( ! class_exists( 'Accredible_Certificates' ) ) {
	/**
	 * Accredible Certificate class
	 */
	class Accredible_Certificates {
		/**
		 * Accredible DB version
		 *
		 * @var string
		 */
		public static $accredible_db_version = '1.0.0';

		/**
		 * Construct the plugin object
		 */
		public function __construct() {
			// Initialize Settings.
			new Accredible_Certificates_Settings();

			$plugin = plugin_basename( __FILE__ );
			add_filter( "plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ) );

			add_action( 'admin_menu', array( $this, 'register_certificates_admin_menu_page' ) );

			// Require accredible admin styles.
			add_action( 'admin_enqueue_scripts', array( &$this, 'acc_load_plugin_css' ) );

			add_action( 'hourly_certificate_issuance', array( &$this, 'sync_with_accredible' ) );

			register_activation_hook( __FILE__, array( &$this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );
		} // END public function __construct

		/**
		 * Activate the plugin
		 */
		public static function activate() {
			// Update the DB.
			self::accredible_db_install();

			// Set auto issue to false by default.
			add_option( 'automatically_issue_certificates', 0 );

			// Cron job for automatic certificate creation.
			wp_schedule_event( time(), 'hourly', 'hourly_certificate_issuance' );
		} // END public static function activate

		/**
		 * Deactivate the plugin.
		 */
		public static function deactivate() {
			// Remove job for automatic certificate creation.
			wp_clear_scheduled_hook( 'hourly_certificate_issuance' );
		} // END public static function deactivate


		/**
		 * Create the database table for course and group mapping
		 */
		public static function accredible_db_install() {
			global $wpdb;

			$table_name = $wpdb->prefix . 'accredible_mapping';

			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				course_id mediumint(9) NOT NULL,
				group_id mediumint(9) NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			add_option( 'accredible_db_version', self::$accredible_db_version );
		}

		/**
		 * Add the settings link to the plugins page
		 *
		 * @param array $links links.
		 * @return array $links
		 */
		public function plugin_settings_link( $links ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=accredible_certificates' ) . '">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 * Get an array of credentials for a particular email address
		 *
		 * @param string $email email.
		 * @return \ACMS\stdObject $credentials
		 */
		public static function get_credentials_for_email( $email ) {
			$api = new Api( get_option( 'api_key' ) );

			$credentials = $api->get_credentials( null, $email );

			return $credentials;
		}

		/**
		 * Create a credential
		 *
		 * @param string $name name.
		 * @param string $email email.
		 * @param int    $group_id group id.
		 * @return \ACMS\stdObject $response
		 */
		public static function create_credential( $name, $email, $group_id ) {
			$api = new Api( get_option( 'api_key' ) );

			return $api->create_credential( $name, $email, $group_id );
		}

		/**
		 * Get all credential groups
		 *
		 * @return array $groups
		 */
		public static function get_groups() {
			$api = new Api( get_option( 'api_key' ) );

			$response = $api->get_groups( 1000, 1 );

			return $response->groups;
		}
		/**
		 * Register the admin menu item
		 */
		public function register_certificates_admin_menu_page() {
			add_menu_page( 'Certificates & Badges', 'Certificates & Badges', 'list_users', 'certificates-admin', array( $this, 'render_certificates_admin_page' ), 'dashicons-tablet', 40 );
		}

		/**
		 * Render the certificates admin page
		 */
		public function render_certificates_admin_page() {
			require_once ACCREDIBLE_CERTIFICATES_PLUGIN_PATH . 'certificates-admin.php';
		}

		/**
		 * Load the admin styles
		 */
		public static function acc_load_plugin_css() {
			$version = defined( 'WP_DEBUG' ) && WP_DEBUG ? time() : ACCREDIBLE_CERTIFICATES_SCRIPT_VERSION_TOKEN;
			wp_register_style( 'accredible-admin-style', plugins_url( '/css/style.css', __FILE__ ), array(), $version );
			wp_enqueue_style( 'accredible-admin-style' );
		}

		/**
		 * Send batch requests via the ACMS API
		 *
		 * @param Array $requests requests.
		 * @return mixed $response
		 */
		public static function batch_requests( $requests ) {
			$api = new Api( get_option( 'api_key' ) );

			$request_count = count( $requests );
			for ( $i = 0; $i < $request_count; $i++ ) {
				$requests[ $i ]['url'] = 'v1/' . $requests[ $i ]['url'];
			}

			$response = $api->send_batch_requests( $requests );

			return $response;
		}
	} // END class Accredible_Certificates.
}

if ( class_exists( 'Accredible_Certificates' ) ) {
	// Installation and uninstallation hooks.
	register_activation_hook( __FILE__, array( 'Accredible_Certificates', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Accredible_Certificates', 'deactivate' ) );

	// Instantiate the plugin class.
	$accredible_certificate = new Accredible_Certificates();
}
