<?php
/*
Plugin Name: Accredible Certificates
Plugin URI: https://github.com/accredible/wp_plugin
Description: Issue Accredible course certificates for Academy Theme.
Version: 0.1.4
Author: Accredible
Author URI: https://www.accredible.com
License: GPL2
*/
/*
Copyright 2014 Accredible

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

			//form request
			add_action('admin_action_wpse10500', array(&$this, 'wpse10500_action') );

 			//require accredible styles
 			add_action( 'wp_enqueue_scripts', array( $this, 'acc_load_plugin_css' ) );		

 			add_action( 'hourly_certificate_issuance', array( $this, 'issue_certificates_automatically') );
 			
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

		// Add the settings link to the plugins page
		function plugin_settings_link($links)
		{
			$settings_link = '<a href="options-general.php?page=accredible-certificates">Settings</a>';
			array_unshift($links, $settings_link);
			return $links;
		}

		
		/**
		 * On the scheduled action hook, run the function.
		 */
		public static function issue_certificates_automatically() {

			global $wpdb;

			error_log("Issuing certificates");
			$query="
				SELECT * FROM ".$wpdb->comments." 
				WHERE comment_type = 'user_certificate'
			";

			$relations=$wpdb->get_results($query);

			foreach ($relations as $key => $completion) {

				$course = ThemexCourse::getCourse($completion->comment_post_ID, true);

				$user = get_user_by("id", $completion->user_id);

				if($user->first_name && $user->last_name ){
    				$recipient_name = $user->first_name . ' ' . $user->last_name;
    			} else {
    				$recipient_name = $user->display_name;
    			}
				
				global $post;
				$post = get_post($completion->comment_post_ID);
				setup_postdata( $post, $more_link_text, $stripteaser );

				$existing = Accredible_Certificate::certificates($completion->comment_post_ID);
				$existing_certificates = $existing->credentials;

				$issue = true;
				foreach ($existing_certificates as $key => $certificate) {
					if($certificate->recipient->email == $user->user_email){
						$issue = false;
					}
				}

				if($issue){
					Accredible_Certificate::create_certificate($recipient_name, $user->user_email, get_the_title($completion->comment_post_ID), $completion->comment_post_ID, get_the_excerpt(), get_permalink($completion->comment_post_ID));				
				}    
				
				wp_reset_postdata( $post );
			}
		}

		function acc_load_plugin_css() {
			wp_enqueue_style( 'accredible_certificates-style', plugins_url( '/css/style.css', __FILE__ ) );
		}

		/*
		 * Create Accredible certificate
		 */
		public static function create_certificate($recipient_name, $recipient_email, $course_name, $course_id, $course_description, $course_link)
		{
			$curl = curl_init('https://api.accredible.com/v1/credentials');
			$data = array(  
			    "credential" => array( 
			        "recipient" => array( 
			            "name" => $recipient_name,
			            "email" => $recipient_email
			        ),
			        "name" => $course_name,
			        "description" => $course_description,
			        "course_link" => $course_link,
			        "achievement_id" => $course_id//,
			        // "evidence_items" => array(  
			        //     array(
			        //         "description" => "Report card including all grades", 
			        //         "url" => "http://www.awesomelearningexample.com/johndoe/reportcard"
			        //     )
			        // ),
			        // "references" => array(  
			        //     array(
			        //         "description" => "John worked hard on this course and provided exemplary understanding of the core concepts", 
			        //         "referee" => array( 
			        //             "name" => "Jane Doe",
			        //             "email" => "person2@example.com"
			        //         ), 
			        //         "relationship" => "managed"
			        //     )
			        // )
			    ) 
			);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="' . get_option('api_key') . '"' ) );
			$result = json_decode( curl_exec($curl) );
			curl_close($curl);
			$result_string = print_r($result, true);
			//print_r($result);
		}

		/*
		 * Get existing certificates for a course
		 */
		public static function certificates($course_id)
		{
			$curl = curl_init('https://api.accredible.com/v1/credentials?achievement_id=' . $course_id . '&full_view=true');
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt( $curl, CURLOPT_HTTPHEADER, array( 'Authorization: Token token="' . get_option('api_key') . '"' ) );
			$result = json_decode( curl_exec($curl) );
			curl_close($curl);
			return $result;
		}

		function register_certificates_admin_menu_page(){
		    add_menu_page( 'Certificates', 'Certificates', 'edit_post', 'accredible-certificates/certificates-admin.php', '', 'dashicons-tablet', 40 );
		}

		public static function wpse10500_action() {

			$recipient_name = $_POST['recipient_name'];
			$recipient_email = $_POST['recipient_email'];
			$course_name = $_POST['course_name'];
			$course_id = $_POST['course_id'];
			$course_description = $_POST['course_description'];
			$course_link = $_POST['course_link'];
			$issue_certificate = $_POST['issue_certificate'];

			if(is_array($recipient_name)){
				foreach( $recipient_name as $key => $name ) {
			        if(isset($issue_certificate[$key])){
			        	$result = self::create_certificate($name, $recipient_email[$key], $course_name[$key], $course_id[$key], $course_description[$key], $course_link[$key]);
			        }
				}

			} else {
				//handle the case where PHP doesn't post as an Array
				if(isset($issue_certificate)){
		        	$result = self::create_certificate($name, $recipient_email, $course_name, $course_id, $course_description, $course_link);
		        }
			}			

			wp_redirect(admin_url('admin.php?page=accredible-certificates/certificates-admin.php'));
		}

		public static function get_courses($user){
			$themexCourse = new ThemexCourse($user);

			$courses = ThemexCourse::getCourses($user);
			return $courses;
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
