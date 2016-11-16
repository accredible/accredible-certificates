<?php

if(!class_exists('Accredible_Acadmey_Theme'))
{
	class Accredible_Acadmey_Theme
	{

		/**
		 * Get course IDs for Academy Theme's courses that a user has access to
		 * @param type $user 
		 * @return array $courses_ids
		 */
		public static function get_course_ids($user){
			$themexCourse = new ThemexCourse($user);

			$courses_ids = ThemexCourse::getCourses($user);
			return $courses_ids;
		}

		public static function sync_course_with_group(){
			
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
				$grade = ThemexCourse::getGrade($completion->comment_post_ID, $completion->user_id);

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
					@Accredible_Certificate::create_credential($userdata->user_nicename, $user->user_email, $group_id);

					Accredible_Certificate::create_certificate($recipient_name, $user->user_email, get_the_title($completion->comment_post_ID), $completion->comment_post_ID, get_the_excerpt(), get_permalink($completion->comment_post_ID), $grade);				
				}    
				
				wp_reset_postdata( $post );
			}
		}
	}
}

?>