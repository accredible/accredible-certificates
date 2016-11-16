<?php
/*
Plugin Name: Accredible Certificates
Plugin URI: https://github.com/accredible/wp_plugin
Description: Issue Accredible course certificates for Academy Theme.
Version: 0.2.0
Author: Accredible
Author URI: https://www.accredible.com
License: GPL2
*/
/*
Copyright 2015 Accredible

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
require 'vendor/autoload.php';

use ACMS\Api;

// Require Academy Theme logic
if ( ! class_exists( 'Accredible_Acadmey_Theme' ) ) {
	require_once('accredible-academy-theme.php');
}

if(!class_exists('Accredible_Certificate'))
{
	class Accredible_Certificate
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{

			// Initialize Settings
			require_once(sprintf("%s/settings.php", dirname(__FILE__)));
			$Accredible_Certificates_Settings = new Accredible_Certificates_Settings();

			$plugin = plugin_basename(__FILE__);
			add_filter("plugin_action_links_$plugin", array( $this, 'plugin_settings_link' ));

			add_action( 'admin_menu', array( $this, 'register_certificates_admin_menu_page' ));

 			//require accredible admin styles
 			add_action( 'admin_enqueue_scripts', array( &$this, 'acc_load_plugin_css' ) );	

 			//add_action( 'hourly_certificate_issuance', array( $this, 'issue_certificates_automatically') );

 			
		} // END public function __construct

		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
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
		 * Add the settings link to the plugins page
		 * @param type $links 
		 * @return type
		 */
		public function plugin_settings_link($links)
		{
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=accredible-certificates' ) . '">Settings</a>';
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
		 * Register the admin menu item
		 * @return null
		 */
		public function register_certificates_admin_menu_page(){
		    add_menu_page( 'Certificates & Badges', 'Certificates & Badges', 'edit_posts', 'accredible-certificates/certificates-admin.php', '', 'dashicons-tablet', 40 );
		}

		/**
		 * Load the admin styles
		 * @return type
		 */
		public static function acc_load_plugin_css() {
			wp_register_style( 'accredible-admin-style', plugins_url( '/css/style.css', __FILE__ ) );
			wp_enqueue_style('accredible-admin-style'); 
		}

		// Deprecated below here

		

		/*
		 * Get existing certificates for a course
		 */
		public static function certificates($course_id)
		{
			$client = new GuzzleHttp\Client();
			$res = $client->get('https://api.accredible.com/v1/credentials?achievement_id=' . $course_id . '&full_view=true', ['headers' =>  ['Authorization' => 'Token token="'.get_option('api_key').'"']]);
			$result = json_decode($res->getBody());
			return $result;
		}


		public static function hasCertificate($course_id, $user_id){
         
          $user = get_user_by("id", $user_id);
          $all_certificates = Accredible_Certificate::certificates($course_id);
          //$all_certificates = certificates($course_id);
          $all_certificates = $all_certificates->credentials;
          $cert_exit = False;
          if(is_array($all_certificates)){
			foreach ($all_certificates as $key => $cert) {
			  if($cert->recipient->email == $user->user_email){
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
