<?php

if(!class_exists('Accredible_Acadmey_Theme'))
{
	class Accredible_Acadmey_Theme
	{

		/**
		 * Sync Data between Academy theme and Accredible
		 * @return null
		 */
		public static function sync_with_accredible(){
			global $wpdb;

			// start by making sure our groups line up with the courses
			self::sync_course_with_group();

			// for each course check if we have any graduates on WP
			$query="
				SELECT * FROM ".$wpdb->comments." 
				WHERE comment_type = 'user_certificate'
			";
			$relations=$wpdb->get_results($query);

			foreach ($relations as $key => $completion) {
			
				// get the course group mapping
				$mapping = self::get_mapping($completion->comment_post_ID);

				if($mapping !== null && count($mapping) > 0){
					$user = get_user_by("id", $completion->user_id);

					if($user->first_name && $user->last_name ){
	    				$recipient_name = $user->first_name . ' ' . $user->last_name;
	    			} else {
	    				$recipient_name = $user->display_name;
	    			}

					// create a credential
					$credential = @Accredible_Certificate::create_credential($recipient_name, $user->user_email, $mapping[0]->group_id);
				}

			}
		}

		/**
		 * Get course IDs for Academy Theme's courses that a user has access to
		 * @param type $user 
		 * @return array $courses_ids
		 */
		public static function get_course_ids($user){
			global $wpdb;

			$courses=$wpdb->get_results("
				SELECT ID FROM ".$wpdb->posts." 
				WHERE post_status = 'publish' 
				AND post_type = 'course' 
				ORDER BY post_date DESC
			");
			
			$courses_ids=wp_list_pluck($courses, 'ID');	
			return $courses_ids;
		}

		/**
		 * Syncs academy theme courses with Accreidble 
		 * @return null
		 */
		public static function sync_course_with_group(){
			global $wpdb;
			$course_ids = self::get_course_ids();

			for ($i=0; $i < count($course_ids); $i++) { 
				$course = ThemexCourse::getCourse($course_ids[$i], true);

				//check if we have an existing mapping
				$mapping = self::get_mapping($course_ids[$i]);
				if($mapping !== null && count($mapping) > 0){
					// then update details
					global $post;
					$post = get_post($course_ids[$i]);
					setup_postdata( $post, $more_link_text, $stripteaser );

					$group = @Accredible_Certificate::update_group($mapping[0]->group_id, get_the_title($course_ids[$i]), get_the_excerpt(), get_permalink($course_ids[$i]) );
				} else {
					// else create a new group and mapping
					global $post;
					$post = get_post($course_ids[$i]);
					setup_postdata( $post, $more_link_text, $stripteaser );

					$group_name = urlencode(get_the_title($course_ids[$i]).mt_rand());
					// them make a new group on accredible
					$group = @Accredible_Certificate::create_group($group_name, get_the_title($course_ids[$i]), get_the_excerpt(), get_permalink($course_ids[$i]) );
					
					// save to db
					$wpdb->insert( 
						$wpdb->prefix . 'accredible_mapping', 
						array( 
							'course_id' => $course_ids[$i], 
							'group_id' => $group->id 
						)
					);
				}
			}
		}

		/**
		 * Get the course mapping for a particular course id
		 * @param type $course_id 
		 * @return type
		 */
		public static function get_mapping($course_id){
			global $wpdb;

			$query="
				SELECT * FROM " . $wpdb->prefix . "accredible_mapping 
				WHERE course_id = '".$course_id."'
				LIMIT 1
			";

			$relations = $wpdb->get_results($query);

			return $relations;
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
					if($certificate->recipient->email == strtolower( $user->user_email )){
						$issue = false;
					}
				}
               
				if($issue){
					Accredible_Certificate::create_certificate($recipient_name, $user->user_email, get_the_title($completion->comment_post_ID), $completion->comment_post_ID, get_the_excerpt(), get_permalink($completion->comment_post_ID), $grade);				
				}    
				
				wp_reset_postdata( $post );
			}
		}
	}
}

?>