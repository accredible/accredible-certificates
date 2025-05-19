<?php
/**
 * The widget class.
 *
 * @package Accredible_Certificates
 */

defined( 'ABSPATH' ) || die;
if ( ! class_exists( 'Accredible_Widget' ) ) {
	/**
	 * Accredible Widget class
	 */
	class Accredible_Widget extends WP_Widget {
		/**
		 * Main constructor.
		 */
		public function __construct() {
			parent::__construct(
				'accredible_widget',
				__( 'Accredible Widget', 'accredible-certificates' ),
				array(
					'customize_selective_refresh' => true,
				)
			);
		}

		/**
		 * The widget form (for the backend).
		 *
		 * @param array $instance The widget instance.
		 */
		public function form( $instance ) {
			// Set widget defaults.
			$defaults = array(
				'title' => '',
			);

			// Parse current settings with defaults.
			$instance = wp_parse_args( (array) $instance, $defaults );
			$title    = $instance['title'];
			?>

			<?php // Widget Title. ?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Widget Title', 'accredible-certificates' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<?php }

		/**
		 * Update widget settings.
		 *
		 * @param array $new_instance The new instance.
		 * @param array $old_instance The old instance.
		 * @return array The updated instance.
		 */
		public function update( $new_instance, $old_instance ) {
			$instance             = $old_instance;
			$instance['title']    = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
			$instance['text']     = isset( $new_instance['text'] ) ? wp_strip_all_tags( $new_instance['text'] ) : '';
			$instance['textarea'] = isset( $new_instance['textarea'] ) ? wp_kses_post( $new_instance['textarea'] ) : '';
			$instance['checkbox'] = isset( $new_instance['checkbox'] ) ? 1 : false;
			$instance['select']   = isset( $new_instance['select'] ) ? wp_strip_all_tags( $new_instance['select'] ) : '';
			return $instance;
		}

		/**
		 * Display the widget.
		 *
		 * @param array $args The arguments.
		 * @param array $instance The instance.
		 */
		public function widget( $args, $instance ) {
			// Get widget arguments.
			$before_widget = $args['before_widget'];
			$after_widget  = $args['after_widget'];
			$before_title  = $args['before_title'];
			$after_title   = $args['after_title'];

			// Check the widget options.
			$title    = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
			$text     = isset( $instance['text'] ) ? $instance['text'] : '';
			$textarea = isset( $instance['textarea'] ) ? $instance['textarea'] : '';
			$select   = isset( $instance['select'] ) ? $instance['select'] : '';
			$checkbox = ! empty( $instance['checkbox'] ) ? $instance['checkbox'] : false;

			// WordPress core before_widget hook (always include).
			echo esc_html( $before_widget );

			// Display the widget.
			echo '<div class="widget-text wp_widget_plugin_box">';

			// Display widget title if defined.
			if ( $title ) {
				echo esc_html( $before_title . $title . $after_title );
			}

			$current_user = wp_get_current_user();

			if ( 0 !== $current_user->ID ) {
				$accredible  = new Accredible_Certificates();
				$credentials = $accredible->get_credentials_for_email( $current_user->user_email );
				if ( $credentials->credentials ) {
					echo '<ul>';
					foreach ( $credentials->credentials as $key => $credential ) {
						echo '<li>';
						echo '<a href="' . esc_url( $credential->url ) . '" target="_blank">' . esc_html( $credential->name ) . '</a>';
						echo '</li>';
					}
					echo '</ul>';
				}
			}
			echo '</div>';
			// WordPress core after_widget hook (always include).
			echo esc_html( $after_widget );
		}

		/**
		 * Register the widget.
		 */
		public static function register_widget() {
			register_widget( 'Accredible_Widget' );
		}

		/**
		 * The shortcode.
		 *
		 * @param array  $atts The attributes.
		 * @param string $content The content.
		 * @param string $tag The tag.
		 */
		public static function credential_shortcode( $atts = array(), $content = null, $tag = '' ) {
			$output = '';

			// Normalize attribute keys, lowercase.
			$atts = array_change_key_case( (array) $atts, CASE_LOWER );

			// Override default attributes with user attributes.
			$atts_to_consume = shortcode_atts(
				array(
					'image' => 'true',
					'limit' => '10',
					'style' => 'true',
				),
				$atts,
				$tag
			);

			$current_user = wp_get_current_user();

			if ( 0 !== $current_user->ID ) {
				$accredible  = new Accredible_Certificates();
				$credentials = $accredible->get_credentials_for_email( $current_user->user_email );
				if ( $credentials->credentials ) {
					foreach ( $credentials->credentials as $key => $credential ) {

						// The user can set a limit on the number of credentials displayed.
						if ( $key >= (int) $atts_to_consume['limit'] ) {
							break;
						}

						// The user can choose between image or link.
						if ( false === $atts_to_consume['image'] ) {
							$output .= '<a href="' . esc_url( $credential->url ) . '" target="_blank">' . esc_html( $credential->name ) . '</a>';
						} else {
							// The user can choose to remove the default styling.
							if ( false === $atts_to_consume['style'] ) {
								$output .= '<div class="accredible_credential">';
							} else {
								$output .= '<div style="width: 300px; height: 200px; margin: 0 30px 30px 0; text-align: center; display: inline-block;" class="accredible_credential">';
							}
							$output .= '<a href="' . esc_url( $credential->url ) . '">';
							$output .= '<img src="' . esc_url( $credential->seo_image ) . '" style="max-width:100%; max-height:100%; margin: 0 auto;">'; // phpcs:disable PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
							$output .= '</a>';
							$output .= '</div>';
						}
					}
				}
			}
			return $output;
		}
	}

	// Register the widget.
	add_action( 'widgets_init', array( 'Accredible_Widget', 'register_widget' ) );

	// Register the shortcode.
	add_shortcode( 'accredible_credential', array( 'Accredible_Widget', 'credential_shortcode' ) );
}
?>