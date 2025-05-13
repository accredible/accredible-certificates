<?php
/**
 * Accredible Academy Theme
 *
 * @package Accredible_Certificates
 */

if ( ! class_exists( 'Accredible_Academy_Theme' ) ) {
	/**
	 * Accredible Academy Theme
	 */
	class Accredible_Academy_Theme {
		/**
		 * Sync Data between Academy theme and Accredible
		 */
		public static function sync_with_accredible() {
			global $wpdb;

			// Start by making sure our groups line up with the courses.
			self::sync_course_with_group();

			// For each course, check if we have any graduates on WP.
			$relations = get_comments(
				array(
					'type' => 'user_certificate',
				)
			);

			foreach ( $relations as $key => $completion ) {

				// Get the course group mapping.
				$mapping = self::get_mapping( $completion->comment_post_ID );

				if ( ! empty( $mapping ) ) {
					$user = get_user_by( 'id', $completion->user_id );

					if ( $user->first_name && $user->last_name ) {
						$recipient_name = $user->first_name . ' ' . $user->last_name;
					} else {
						$recipient_name = $user->display_name;
					}

					// Create a credential.
					$credential = @Accredible_Certificate::create_credential( $recipient_name, $user->user_email, $mapping[0]->group_id );
				}
			}
		}

		/**
		 * Get course IDs for Academy Theme's courses that a user has access to
		 *
		 * @return array $courses_ids
		 */
		public static function get_course_ids() {
			$courses = get_posts(
				array(
					'post_type'      => 'course',
					'post_status'    => 'publish',
					'orderby'        => 'date',
					'order'          => 'DESC',
					'posts_per_page' => -1,
					'fields'         => 'ids',
				)
			);

			return $courses;
		}

		/**
		 * Syncs academy theme courses with Accreidble
		 */
		public static function sync_course_with_group() {
			global $wpdb;
			$course_ids       = self::get_course_ids();
			$course_ids_count = count( $course_ids );
			for ( $i = 0; $i < $course_ids_count; $i++ ) {
				$course = ThemexCourse::getCourse( $course_ids[ $i ], true );

				// Check if we have an existing mapping.
				$mapping = self::get_mapping( $course_ids[ $i ] );
				if ( ! empty( $mapping ) ) {
					// Then update details.
					$post_obj = get_post( $course_ids[ $i ] );
					$group    = @Accredible_Certificate::update_group(
						$mapping[0]->group_id,
						get_the_title( $post_obj ),
						get_the_excerpt( $post_obj ),
						get_permalink( $post_obj )
					);
				} else {
					// Else create a new group and mapping.
					$post_obj   = get_post( $course_ids[ $i ] );
					$group_name = rawurlencode( get_the_title( $post_obj ) . wp_rand() );
					// Then make a new group on accredible.
					$group = @Accredible_Certificate::create_group(
						$group_name,
						get_the_title( $post_obj ),
						get_the_excerpt( $post_obj ),
						get_permalink( $post_obj )
					);

					// Save to db.
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->insert(
						$wpdb->prefix . 'accredible_mapping',
						array(
							'course_id' => $course_ids[ $i ],
							'group_id'  => $group->id,
						)
					);
				}
			}
		}

		/**
		 * Get the course mapping for a particular course id
		 *
		 * @param int $course_id Course ID.
		 * @return array $relations
		 */
		public static function get_mapping( $course_id ) {
			global $wpdb;

			$relations = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}accredible_mapping WHERE course_id = %d LIMIT 1", $course_id ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			return $relations;
		}

		/**
		 * On the scheduled action hook, run the function.
		 */
		public static function issue_certificates_automatically() {

			global $wpdb;

			// If WP_DEBUG is enabled, log the error.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Issuing certificates' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			$relations = get_comments(
				array(
					'type' => 'user_certificate',
				)
			);

			foreach ( $relations as $key => $completion ) {

				$course = ThemexCourse::getCourse( $completion->comment_post_ID, true );

				$user  = get_user_by( 'id', $completion->user_id );
				$grade = ThemexCourse::getGrade( $completion->comment_post_ID, $completion->user_id );

				if ( $user->first_name && $user->last_name ) {
					$recipient_name = $user->first_name . ' ' . $user->last_name;
				} else {
					$recipient_name = $user->display_name;
				}

				$existing              = Accredible_Certificate::certificates( $completion->comment_post_ID );
				$existing_certificates = $existing->credentials;

				$issue = true;
				foreach ( $existing_certificates as $key => $certificate ) {
					if ( strtolower( $user->user_email ) === strtolower( $certificate->recipient->email ) ) {
						$issue = false;
					}
				}

				if ( $issue ) {
					Accredible_Certificate::create_certificate( $recipient_name, $user->user_email, get_the_title( $completion->comment_post_ID ), $completion->comment_post_ID, get_the_excerpt(), get_permalink( $completion->comment_post_ID ), $grade );
				}
			}
		}
	}
}
