<?php
/**
 * Post Type Template
 *
 * @package Accredible_Certificates
 */

if ( ! class_exists( 'Post_Type_Template' ) ) {
	/**
	 * A PostTypeTemplate class that provides 3 additional meta-fields
	 */
	class Post_Type_Template {

		const POST_TYPE = 'post-type-template';
		private $_meta  = array(
			'meta_a',
			'meta_b',
			'meta_c',
		);

		/**
		 * The Constructor
		 */
		public function __construct() {
			// register actions
			add_action( 'init', array( &$this, 'init' ) );
			add_action( 'admin_init', array( &$this, 'admin_init' ) );
		}

		/**
		 * hook into WP's init action hook
		 */
		public function init() {
			// Initialize Post Type
			$this->create_post_type();
			add_action( 'save_post', array( &$this, 'save_post' ) );
		}

		/**
		 * Create the post type
		 */
		public function create_post_type() {
			register_post_type(
				self::POST_TYPE,
				array(
					'labels'      => array(
						'name'          => __( 'Post Type Templates', 'accredible-certificates' ),
						'singular_name' => __( 'Post Type Template', 'accredible-certificates' ),
					),
					'public'      => true,
					'has_archive' => true,
					'description' => __( 'This is a sample post type meant only to illustrate a preferred structure of plugin development', 'accredible-certificates' ),
					'supports'    => array(
						'title',
						'editor',
						'excerpt',
					),
				)
			);
		}

		/**
		 * Save the metaboxes for this custom post type
		 */
		public function save_post( $post_id ) {
			// verify if this is an auto save routine.
			// If it is our form has not been submitted, so we dont want to do anything.
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			$post_type = get_post_type( $post_id );
			if ( $post_type === self::POST_TYPE && current_user_can( 'edit_post', $post_id ) ) {
				foreach ( $this->_meta as $field_name ) {
					$field_value = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
					update_post_meta( $post_id, $field_name, $field_value );
				}
			}
		}

		/**
		 * hook into WP's admin_init action hook
		 */
		public function admin_init() {
			// Add metaboxes
			add_action( 'add_meta_boxes', array( &$this, 'add_meta_boxes' ) );
		}

		/**
		 * hook into WP's add_meta_boxes action hook
		 */
		public function add_meta_boxes() {
			// Add this metabox to every selected post
			add_meta_box(
				sprintf( 'wp_plugin_template_%s_section', self::POST_TYPE ),
				sprintf( '%s Information', ucwords( str_replace( '_', ' ', self::POST_TYPE ) ) ),
				array( &$this, 'add_inner_meta_boxes' ),
				self::POST_TYPE
			);
		}

		/**
		 * called off of the add meta box
		 */
		public function add_inner_meta_boxes( $post ) {
			// Render the job order metabox.
			include sprintf( '%s/../templates/%s_metabox.php', __DIR__, self::POST_TYPE );
		}
	}
}
