<?php
/**
 * Users List Table for Accredible Certificates
 *
 * @package Accredible_Certificates
 */

defined( 'ABSPATH' ) || die;

if ( ! class_exists( 'WP_List_Table' ) ) {
	// Require the list table class.
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'Accredible_Certificate' ) ) {
	require_once ACCREDIBLE_CERTIFICATES_PLUGIN_PATH . 'accredible_certificates.php';
}

/**
 * Class Users_List
 *
 * Handles the display and management of users list table for Accredible certificates.
 * Extends WP_List_Table to create a custom table for managing certificate recipients.
 *
 * @package    Accredible_Certificates
 */
class Users_List extends WP_List_Table {
	/**
	 * Flag to indicate if there are no groups available.
	 *
	 * @var boolean
	 */
	public $no_groups = false;

	/** Class constructor */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __( 'Recipient', 'accredible-certificates' ), // Singular name of the listed records.
				'plural'   => __( 'Recipients', 'accredible-certificates' ), // Plural name of the listed records.
				'ajax'     => false, // Does this table support ajax?
			)
		);
	}

	/**
	 * Retrieve users' data from the database.
	 *
	 * @param int $per_page    Number of users to retrieve per page.
	 * @param int $page_number Current page number.
	 *
	 * @return mixed
	 */
	public static function get_users( $per_page = 20, $page_number = 1 ) {
		$accredible_certificates = new Accredible_Certificate();

		global $wpdb;

		// Define allowed columns for ordering.
		$allowed_columns = array(
			'id',
			'user_login',
			'user_nicename',
			'user_email',
		);

		// Get and validate orderby parameter.
		$order_by = self::sanitize_request_parameter( 'orderby' );
		if ( ! in_array( $order_by, $allowed_columns, true ) ) {
			$order_by = 'id';
		}

		// Get and validate order parameter.
		$order = self::sanitize_request_parameter( 'order' );
		$order = strtoupper( $order );
		if ( ! in_array( $order, array( 'ASC', 'DESC' ), true ) ) {
			$order = 'DESC';
		}

		$offset = ( $page_number - 1 ) * $per_page;

		$search_term = self::sanitize_request_parameter( 's' );
		if ( ! empty( $search_term ) ) {
			$like  = '%' . $wpdb->esc_like( $search_term ) . '%';
			$query = $wpdb->prepare(
				"SELECT id, user_login, user_nicename, user_email 
				FROM {$wpdb->prefix}users
				WHERE user_email LIKE %s 
				OR user_login LIKE %s
				ORDER BY {$order_by} {$order}
				LIMIT %d
				OFFSET %d",
				array(
					$like,
					$like,
					$per_page,
					$offset,
				)
			);
		} else {
			$query = $wpdb->prepare(
				"SELECT id, user_login, user_nicename, user_email 
				FROM {$wpdb->prefix}users
				ORDER BY {$order_by} {$order}
				LIMIT %d
				OFFSET %d",
				array(
					$per_page,
					$offset,
				)
			);
		}

		$result = $wpdb->get_results( $query, 'ARRAY_A' ); // phpcs:ignore WordPress.DB

		// Don't attempt this query if there are no users.
		$result_count = count( $result );
		if ( $result_count > 0 ) {
			// Batch request to get user credentials.
			$requests = array();
			for ( $x = 0; $x < $result_count; $x++ ) {
				$requests[] = array(
					'method' => 'get',
					'url'    => 'all_credentials',
					'params' => array( 'email' => strtolower( $result[ $x ]['user_email'] ) ),
				);
			}

			try {
				$response = Accredible_Certificate::batch_requests( $requests );
			} catch ( Exception $e ) {
				// Create a WP_Error object for proper error handling.
				$error = new WP_Error(
					'accredible_certificate_error',
					$e->getMessage(),
					array(
						'requests' => $requests,
						'status'   => 'error',
					)
				);

				// Display user-friendly error message.
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>' . esc_html__( 'Error fetching credentials. Please try again later.', 'accredible-certificates' ) . '</p>';
				echo '</div>';

				// If WP_DEBUG is enabled, log the error.
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					error_log( $error->get_error_message() );
				}
			}

			$response_results_count = count( $response->results );
			for ( $i = 0; $i < $response_results_count; $i++ ) {
				if ( 'Not Found' !== $response->results[ $i ]->body ) {
					$credentials                 = json_decode( $response->results[ $i ]->body );
					$result[ $i ]['credentials'] = $credentials->credentials;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns the count of users in the database.
	 *
	 * @return null|string
	 */
	public static function record_count() {
		$user_count = count_users();
		return $user_count['total_users'];
	}


	/** Text displayed when no user data is available */
	public function no_items() {
		esc_html_e( 'No users available.', 'accredible-certificates' );
	}

	/**
	 * Render a column when no column-specific method exists.
	 *
	 * @param array  $item item.
	 * @param string $column_name column name.
	 *
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user_login':
			case 'user_nicename':
			case 'user_email':
				return $item[ $column_name ];
			case 'credentials':
				return $this->column_credentials( $item );
			default:
				// Return a formatted display of the column value if it exists.
				if ( isset( $item[ $column_name ] ) ) {
					return esc_html( $item[ $column_name ] );
				}
				// Return a dash for empty or undefined columns.
				return '—';
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="credential_users[]" value="%s" />',
			$item['id']
		);
	}

	/**
	 * Render items in the credential column
	 *
	 * @param array $item item.
	 * @return string
	 */
	public function column_credentials( $item ) {
		$string = '';
		foreach ( $item['credentials'] as $credential ) {
			$string = $string . "<a href='" . $credential->url . "' target='_blank'>" . $credential->url . '</a><br>';
		}
		return $string;
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'user_login'    => 'Login',
			'user_nicename' => 'Username',
			'user_email'    => 'Email',
			'credentials'   => 'Credentials',
		);

		return $columns;
	}

	/**
	 * Columns to make sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'user_login'    => array( 'user_login', true ),
			'user_nicename' => array( 'user_nicename', false ),
			'user_email'    => array( 'user_email', false ),
		);

		return $sortable_columns;
	}

	/**
	 * Get the select options for the group dropdown.
	 *
	 * @return string
	 */
	public function get_group_select_options() {
		$accredible_certificates = new Accredible_Certificate();
		$groups                  = @Accredible_Certificate::get_groups();

		$options      = '';
		$groups_count = count( $groups );
		for ( $i = 0; $i < $groups_count; $i++ ) {
			$options .= "\n\t<option value='" . esc_attr( $groups[ $i ]->id ) . "'>" . esc_html( $groups[ $i ]->name ) . '</option>';
		}

		// set the flag to show there are no groups.
		if ( 0 === $groups_count ) {
			$this->no_groups = true;
		}

		return $options;
	}

	/**
	 * Method to override the header nav and add our groups dropdown and button - https://github.com/WordPress/WordPress/blob/eeefec932f3d4f3b50369f6523c2cd8fad3d467f/wp-admin/includes/class-wp-users-list-table.php#L259
	 *
	 * @param type $which which.
	 */
	public function extra_tablenav( $which ) {

		$id = 'bottom' === $which ? 'group_id2' : 'group_id';
		?>
	<div class="alignleft actions">
		<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Select Group', 'accredible-certificates' ); ?></label>
		<select name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>">
			<option value=""><?php esc_html_e( 'Select Group', 'accredible-certificates' ); ?></option>
			<?php echo wp_kses_post( $this->get_group_select_options() ); ?>
		</select>
		<?php
		submit_button( __( 'Create Credentials', 'accredible-certificates' ), '', 'create-credentials', false, 'onclick="setTimeout(disableCertificateSubmitButton, 1)"' );
		echo '</div>';
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		/** Process bulk action */
		$this->process_bulk_action();

		// Avoid bulk request overflows by limiting the page size.
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // WE have to calculate the total number of items.
				'per_page'    => 20, // WE have to determine how many items to show on a page.
			)
		);

		$this->items = self::get_users( 20, $current_page );
	}

	/**
	 * Capture the bulk action required, and return it.
	 *
	 * Overridden from the base class implementation to capture
	 * the role change drop-down.
	 *
	 * @since  3.1.0
	 * @access public
	 *
	 * @return string The bulk action required.
	 */
	public function current_action() {
		$create_credentials = self::sanitize_request_parameter( 'create-credentials' );
		if ( ! $create_credentials ) {
			return parent::current_action();
		}

		$group_id  = self::sanitize_request_parameter( 'group_id' );
		$group_id2 = self::sanitize_request_parameter( 'group_id2' );

		if ( ! empty( $group_id ) || ! empty( $group_id2 ) ) {
			return 'create-credentials';
		}

		if ( empty( $group_id ) && empty( $group_id2 ) ) {
			// Let the user know they need to select a group.
			echo '<div class="notice notice-error is-dismissible">';
			echo '<p>You need to select a Group to create Credentials.</p>';
			echo '</div>';
		}

		return parent::current_action();
	}

	/**
	 * When the action is submitted, we should do what the user suggested - make credentials
	 */
	public function process_bulk_action() {

		// Detect when a bulk action is being triggered...
		if ( 'create-credentials' === $this->current_action() ) {
			$accredible_certificates = new Accredible_Certificate();

			// Verify nonce.
			$nonce = self::sanitize_post_parameter( 'accredible_certificates_nonce' );
			self::verify_nonce( $nonce, 'accredible_certificates_bulk_action' );

			// Get and validate group_id from either group_id or group_id2.
			$group_id = self::sanitize_post_parameter( 'group_id' );
			if ( empty( $group_id ) ) {
				$group_id = self::sanitize_post_parameter( 'group_id2' );
			}

			// Get and validate credential_users array.
			$users = self::get_credential_users();

			// Only proceed if we have both a group_id and users.
			if ( ! empty( $group_id ) && ! empty( $users ) ) {
				// Create credentials for each user.
				foreach ( $users as $user_id ) {
					// Find the user.
					$userdata = WP_User::get_data_by( 'id', $user_id );
					if ( ! $userdata ) {
						continue;
					}

					$user_firstname = get_user_meta( $user_id, 'first_name', true );
					$user_lastname  = get_user_meta( $user_id, 'last_name', true );

					$recipient_name = ( $user_firstname && $user_lastname )
						? $user_firstname . ' ' . $user_lastname
						: $userdata->display_name;

					// Create a credential.
					$credential = @Accredible_Certificate::create_credential(
						$recipient_name,
						$userdata->user_email,
						$group_id
					);
				}

				// Let the user know that the creation was successful.
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p>Credentials created!</p>';
				echo '</div>';
			} else {
				// Let the user know that the creation failed.
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>Failed to create credentials. Please ensure you have selected both a group and users.</p>';
				echo '</div>';
			}
		}
	}

	/**
	 * Process and sanitize the credential users array from POST data.
	 *
	 * @return array Array of sanitized user IDs.
	 */
	private static function get_credential_users() {
		$users = array();
		// nonce verification is handled in the process_bulk_action method.
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$credential_users = isset( $_POST['credential_users'] ) ? wp_unslash( $_POST['credential_users'] ) : array();
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( is_array( $credential_users ) ) {
			$users = array_map( 'absint', array_keys( $credential_users ) );
		}

		return $users;
	}

	/**
	 * Verify WP nonce for the action.
	 *
	 * @param string $nonce Nonce value that was used for verification.
	 * @param string $action Should give context to what is taking place and be the same when nonce was created.
	 */
	private static function verify_nonce( $nonce, $action ) {
		if ( ! ( isset( $nonce ) && wp_verify_nonce( $nonce, $action ) ) ) {
			wp_die( 'Invalid nonce.' );
		}
	}

	/**
	 * Sanitize a string parameter.
	 *
	 * @param string      $key The key of the parameter.
	 * @param string|null $default_value The default value if the parameter is not set.
	 */
	private static function sanitize_request_parameter( $key, $default_value = null ) {
		// nonce verification is handled in the current_action method.
		return isset( $_REQUEST[ $key ] ) ? sanitize_key( wp_unslash( $_REQUEST[ $key ] ) ) : $default_value; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Sanitize a post parameter.
	 *
	 * @param string      $key The key of the parameter.
	 * @param string|null $default_value The default value if the parameter is not set.
	 */
	private static function sanitize_post_parameter( $key, $default_value = null ) {
		// nonce verification is handled in the process_bulk_action method.
		return isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default_value; // phpcs:ignore WordPress.Security.NonceVerification
	}
}

?>