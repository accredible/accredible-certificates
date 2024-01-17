<?php
/*
Plugin Name: Accredible Certificates & Open Badges
Plugin URI: https://github.com/accredible/accredible-certificates
Description: Certificates, open badges and blockchain credentials. Create, update and manage them on your Wordpress site.
Version: 1.4.9
Author: Accredible
Author URI: https://www.accredible.com
License: GPL2
*/
/*
Copyright 2016 Accredible

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// For composer dependencies
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

use ACMS\Api;

// Require Academy Theme logic
if ( ! class_exists( 'Accredible_Acadmey_Theme' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'accredible-academy-theme.php' );
}

// Require Widget for credential display
if ( ! class_exists( 'Accredible_Widget' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'accredible_widget.php' );
}

if(!class_exists('Accredible_Certificate'))
{
	class Accredible_Certificate
	{
		
		public static $accredible_db_version = '1.0.0';

		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{

			// Initialize Settings
			require_once( plugin_dir_path( __FILE__ ) . 'settings.php' );
			$Accredible_Certificates_Settings = new Accredible_Certificates_Settings();

			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ));

			add_action( 'admin_menu', array( $this, 'register_certificates_admin_menu_page' ));

 			//require accredible admin styles
 			add_action( 'admin_enqueue_scripts', array( &$this, 'acc_load_plugin_css' ) );	

 			add_action( 'hourly_certificate_issuance', array( &$this, 'sync_with_accredible') );

 			register_activation_hook( __FILE__, array( &$this, 'activate' ));
			register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ));

 			
		} // END public function __construct

		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			// Update the DB
			self::accredible_db_install();

			// Set auto issue to false by default
			add_option( 'automatically_issue_certificates', 0 );

			//cron job for automatic certificate creation
 			wp_schedule_event( time(), 'hourly', 'hourly_certificate_issuance' );



		} // END public static function activate

		/**
		 * Deactivate the plugin
		 */
		public static function deactivate()
		{
			//remove job for automatic certificate creation
			wp_clear_scheduled_hook( 'hourly_certificate_issuance' );
		} // END public static function deactivate


		/**
		 * Create the database table for course and group mapping
		 * @return null
		 */
		public static function accredible_db_install() {
			global $wpdb;
			self::$accredible_db_version;

			$table_name = $wpdb->prefix . 'accredible_mapping';
			
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				course_id mediumint(9) NOT NULL,
				group_id mediumint(9) NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			add_option( 'accredible_db_version', $accredible_db_version );
		}

		/**
		 * Add the settings link to the plugins page
		 * @param type $links 
		 * @return type
		 */
		public function plugin_settings_link($links)
		{
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=accredible_certificates' ) . '">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		/**
		 * Get an array of credentials for a particular email address
		 * @param String $email 
		 * @return Array $credentials
		 */
		public static function get_credentials_for_email($email){
			$api = new Api(get_option('api_key'));
			
			$credentials = $api->get_credentials(null, $email);

			return $credentials;
		}

		/**
		 * Create a credential
		 * @param String $name 
		 * @param String $email 
		 * @param int $group_id 
		 * @return mixed $response
		 */
		public static function create_credential($name, $email, $group_id){
			$api = new Api(get_option('api_key'));

			$response = $api->create_credential($name, $email, $group_id);

			return $response;
		}

		/**
		 * Get all credential groups
		 * @return Array $groups
		 */
		public static function get_groups(){
			$api = new Api(get_option('api_key'));

			$response = $api->get_groups(1000, 1);

			return $response->groups;
		}

		/**
		 * Create a group on Accredible
		 * @return mixed $response
		 */
		public static function create_group($name, $course_name, $course_description, $course_link){
			$api = new Api(get_option('api_key'));

			$response = $api->create_group($name, $course_name, $course_description, $course_link);

			return $response->group;	
		}

		/**
		 * Update a group on Accredible
		 * @param int $id 
		 * @param String $course_name 
		 * @param String $course_description 
		 * @param String $course_link 
		 * @return mixed $response
		 */
		public static function update_group($id, $course_name, $course_description, $course_link){
			$api = new Api(get_option('api_key'));

			$response = $api->update_group($id, $course_name, $course_description, $course_link);

			return $response->group;	
		}

		/**
		 * Register the admin menu item
		 * @return null
		 */
		public function register_certificates_admin_menu_page(){
		    add_menu_page( 'Certificates & Badges', 'Certificates & Badges', 'list_users', 'accredible-certificates/certificates-admin.php', '', 'dashicons-tablet', 40 );
		}

		/**
		 * Load the admin styles
		 * @return type
		 */
		public static function acc_load_plugin_css() {
			wp_register_style( 'accredible-admin-style', plugins_url( '/css/style.css', __FILE__ ) );
			wp_enqueue_style('accredible-admin-style'); 
		}

		/**
		 * Should we show the issuer an option to auto create credentials?
		 * @return boolean
		 */
		public static function auto_sync_available(){
			$theme = wp_get_theme(); // gets the current theme
			if ('Academy' == $theme->name || 'Academy' == $theme->parent_theme) {
		  		return true;
			} else {
				return false;
			}
		}

		/**
		 * Function called hourly to sync with Accredible
		 * @return null
		 */
		public static function sync_with_accredible(){
			if(get_option('automatically_issue_certificates') == 1){
				Accredible_Acadmey_Theme::sync_with_accredible();
			}
		}

		/**
		 * Send batch requests via the ACMS API
		 * @param Array $requests 
		 * @return mixed $response
		 */
		public static function batch_requests($requests){
			$api = new Api(get_option('api_key'));

			for ($i=0; $i < count($requests); $i++) { 
				$requests[$i]['url'] = "v1/" . $requests[$i]['url'];
			}

			$response = $api->send_batch_requests($requests);

			return $response;	
		}

		// Deprecated below here

		

		/*
		 * Get existing certificates for a course
		 */
		public static function certificates($course_id)
		{
			$client = new GuzzleHttp\Client();
			$params = array(  'headers' => array( 'Authorization' => 'Token token="'.get_option('api_key').'"' ));
			$res = $client->get('https://api.accredible.com/v1/credentials?achievement_id=' . $course_id . '&full_view=true', $params);
			$result = json_decode($res->getBody());
			return $result;
		}


		public static function hasCertificate($course_id, $user_id){
         
          $user = get_user_by("id", $user_id);
          $all_certificates = Accredible_Certificate::certificates($course_id);
          $all_certificates = $all_certificates->credentials;
          $cert_exit = False;
          if(is_array($all_certificates)){
			foreach ($all_certificates as $key => $cert) {
			  $user_email = strtolower( $user->user_email );
			  if($cert->recipient->email == $user_email){
			    $cert_exit = True;
			    $cert_id = $cert->id;
			    $approve = $cert->approve;
			    if($approve){
			      return $cert_id;
			    }else{
                  return $approve;
			    }
			  }
		    }
		  }
	      return $cert_exit;
		}

	} // END class accredible_certificates
} // END if(!class_exists('accredible_certificates'))

if(class_exists('Accredible_Certificate'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('Accredible_Certificate', 'activate'));
	register_deactivation_hook(__FILE__, array('Accredible_Certificate', 'deactivate'));

	// instantiate the plugin class
	$accredible_certificate = new Accredible_Certificate();

}
