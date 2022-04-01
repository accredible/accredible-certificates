<?php

// Require the list table class
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

// Require the Accredible API
if ( ! class_exists( 'Accredible_Certificate' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'accredible_certificates.php' );
}

class Users_List extends WP_List_Table {

	public $no_groups = false;

	/** Class constructor */
	public function __construct() {

		parent::__construct( 
			array(
				'singular' => __( 'Recipient', 'sp' ), //singular name of the listed records
				'plural'   => __( 'Recipients', 'sp' ), //plural name of the listed records
				'ajax'     => false //does this table support ajax?
			)
		);

	}

	/**
	 * Retrieve users data from the database
	 *
	 * @param int $per_page
	 * @param int $page_number
	 *
	 * @return mixed
	 */
	public static function get_users( $per_page = 20, $page_number = 1 ) {

		$accredible_certificates = new Accredible_Certificate();

		global $wpdb;

		if ( empty( $_REQUEST['orderby'] ) ) {
			$_REQUEST['orderby'] = "id";
			$_REQUEST['order'] = "desc";
		}
		$orderby = ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
		$orderby .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';

		$offset = ( $page_number - 1 ) * $per_page;

		if ( ! empty( $_REQUEST['s'] ) ) {
			$query = $wpdb->prepare( 
				"
					SELECT id, user_login, user_nicename, user_email FROM {$wpdb->prefix}users
					WHERE ( user_email LIKE %s ) OR ( user_email LIKE %s ) OR ( user_login LIKE %s )
				" . $orderby . "
					LIMIT %d
					OFFSET %d
				", 
			        array(
					"%" . $_REQUEST['s'] . "%", 
					"%" . $_REQUEST['s'] . "%", 
					"%" . $_REQUEST['s'] . "%",
					$per_page,
					$offset
				) 
			);
		} else {
			$query = $wpdb->prepare( 
				"
					SELECT id, user_login, user_nicename, user_email FROM {$wpdb->prefix}users
				" . $orderby . "
					LIMIT %d
					OFFSET %d
				", 
			        array(
					$per_page,
					$offset
				) 
			);
		}

		$result = $wpdb->get_results($query, 'ARRAY_A');

        // Don't attempt this query if there are no users
        if(count($result) > 0){
        	// batch request to get user credentials
			$requests = [];
	        for ($x=0; $x < count($result); $x++) { 
	        	array_push($requests, ["method" => "get", "url" => "all_credentials", "params" => ["email" =>  strtolower( $result[$x]["user_email"] )] ]);
	        }

        	try {
	        	$response = @Accredible_Certificate::batch_requests($requests);
	        } catch (Exception $e) {
	        //dump response here using try catch
	        	echo '<pre>'; print_r($requests); echo '</pre>';
	        	echo $e->getMessage();
			}

	        for ($i=0; $i < count($response->results); $i++) { 
	        	if($response->results[$i]->body != "Not Found") {
	        		$credentials = json_decode($response->results[$i]->body);
	        		$result[$i]["credentials"] = $credentials->credentials;
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
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}users";

		return $wpdb->get_var( $sql );
	}


	/** Text displayed when no user data is available */
	public function no_items() {
		_e( 'No users avaliable.', 'sp' );
	}

	/**
	 * Render a column when no column specific method exist.
	 *
	 * @param array $item
	 * @param string $column_name
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
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	/**
	 * Render the bulk edit checkbox
	 *
	 * @param array $item
	 *
	 * @return string
	 */
	function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="credential_users[]" value="%s" />', $item['id']
		);
	}

	/**
	 * Render items in the credential column
	 * @param array $item 
	 * @return string
	 */
	function column_credentials ($item) {
		$string = "";
		foreach ($item['credentials'] as $credential) {
			$string = $string . "<a href='" . $credential->url . "' target='_blank'>" . $credential->url . "</a><br>";
		}
		return $string;
	}

	/**
	 *  Associative array of columns
	 *
	 * @return array
	 */
	function get_columns() {
		$columns = array(
			'cb'			=> '<input type="checkbox" />',
			'user_login'    => 'Login',
			'user_nicename' => 'Username',
			'user_email'    => 'Email',
			'credentials' 	=> 'Credentials'
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
			'user_login' => array( 'user_login', true ),
			'user_nicename' => array( 'user_nicename', false ),
			'user_email' => array( 'user_email', false )
		);

		return $sortable_columns;
	}

	/**
	 * Get the select options for the gr
	 * @return type
	 */
	public function get_group_select_options() {
		$accredible_certificates = new Accredible_Certificate();
	 	$groups = @Accredible_Certificate::get_groups();

	 	$options = '';

	 	for ($i=0; $i < count($groups); $i++) { 
	 		$options .= "\n\t<option value='" . esc_attr($groups[$i]->id) . "'>" . esc_attr($groups[$i]->name) . "</option>";
	 	}

	 	// set the flag to show there are no groups
	 	if(count($groups) == 0){
	 		$this->no_groups = true;
	 	}

		return $options;
	}

	/**
	 * Method to ovveride the header nav and add our groups dropdown and button - https://github.com/WordPress/WordPress/blob/eeefec932f3d4f3b50369f6523c2cd8fad3d467f/wp-admin/includes/class-wp-users-list-table.php#L259
	 * @param type $which 
	 * @return type
	 */
    public function extra_tablenav( $which ) {

		$id = 'bottom' === $which ? 'group_id2' : 'group_id';
	?>
	<div class="alignleft actions">
		<label class="screen-reader-text" for="<?php echo $id ?>"><?php _e( 'Select Group' ) ?></label>
		<select name="<?php echo $id ?>" id="<?php echo $id ?>">
			<option value=""><?php _e( 'Select Group' ) ?></option>
			<?php echo $this->get_group_select_options(); ?>
		</select>
	<?php
		submit_button( __( 'Create Credentials' ), '', 'create-credentials', false , 'onclick="setTimeout(disableCertificateSubmitButton, 1)"');
		echo '</div>';
	}

	/**
	 * Handles data query and filter, sorting, and pagination.
	 */
	public function prepare_items() {

		$columns = $this->get_columns();
		$hidden = array();
  		$sortable = $this->get_sortable_columns();
  		$this->_column_headers = array($columns, $hidden, $sortable);
		//$this->_column_headers = $this->get_column_info();

		/** Process bulk action */
		$this->process_bulk_action();

		// Avoid bulk request overflows by limiting the page size
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args( array(
			'total_items' => $total_items, //WE have to calculate the total number of items
			'per_page'    => 20 //WE have to determine how many items to show on a page
		) );

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
		if ( isset( $_REQUEST['create-credentials'] ) && ( ! empty( $_REQUEST['group_id'] ) || ! empty( $_REQUEST['group_id2'] ) ) ) {
			return 'create-credentials';
		} else if ( isset( $_REQUEST['create-credentials'] ) && ( empty( $_REQUEST['group_id'] ) && empty( $_REQUEST['group_id2'] ) ) ){
			// let the user know they need to select a group
			echo '<div class="notice notice-error is-dismissible">';
    		echo '<p>You need to select a Group to create Credentials.</p>';
			echo '</div>';
		}
		return parent::current_action();
	}

	/**
	 * When the action is submitted we should do what the user suggested - make credentials
	 * @return type
	 */
	public function process_bulk_action() {
		//Detect when a bulk action is being triggered...
		if ( 'create-credentials' === $this->current_action() ) {

			$accredible_certificates = new Accredible_Certificate();

			if ( isset( $_POST['group_id'] ) && ( ! empty( $_POST['group_id'] ) ) ) {
				$group_id = esc_sql( $_POST['group_id'] );
			} else {
				$group_id = esc_sql( $_POST['group_id2'] );
			}

			$users = $_POST['credential_users'];

			// create credentials for each user
			for ($i=0; $i < count($users); $i++) { 
				// find the user
				$userdata = WP_User::get_data_by( 'id', $users[$i] );

				$user_firstname = get_user_meta( $users[$i], 'first_name', true );
				$user_lastname = get_user_meta( $users[$i], 'last_name', true );

				if($user_firstname && $user_lastname ){
    				$recipient_name = $user_firstname . ' ' . $user_lastname;
    			} else {
    				$recipient_name = $userdata->display_name;
    			}

				// create a credential
				$credential = @Accredible_Certificate::create_credential($recipient_name, $userdata->user_email, $group_id);
			}

			// let the user know that the creation was successful
			echo '<div class="notice notice-success is-dismissible">';
    		echo '<p>Credentials created!</p>';
			echo '</div>';

		}
	}

}

?>