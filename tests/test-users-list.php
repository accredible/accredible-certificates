<?php
/**
 * Test cases for Users_List class
 *
 * @package Accredible_Certificates
 */

/**
 * Test class for Users_List functionality
 */
class Test_Users_List extends WP_UnitTestCase {
	/**
	 * Users_List instance for testing
	 *
	 * @var Users_List
	 */
	private $users_list;

	/**
	 * Accredible_Certificates instance for testing
	 *
	 * @var \PHPUnit\Framework\MockObject\MockObject&Accredible_Certificates
	 */
	private $accredible_certificates;

	/**
	 * Test users data
	 *
	 * @var array
	 */
	private $test_users;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		$this->accredible_certificates = $this->createMock( Accredible_Certificates::class );

		// Load the Users_List class.
		require_once ACCREDIBLE_CERTIFICATES_PLUGIN_PATH . 'class-users-list.php';

		// Create Users_List instance.
		$this->users_list = new Users_List( $this->accredible_certificates );

		// Create test users.
		$this->create_test_users();
	}

	/**
	 * Clean up after tests.
	 */
	public function tearDown(): void {
		// Clean up test users.
		$this->cleanup_test_users();

		// Reset any global state.
		unset( $_REQUEST );
		unset( $_POST );

		parent::tearDown();
	}

	/**
	 * Test constructor.
	 */
	public function test_constructor() {
		$users_list = new Users_List();

		$this->assertInstanceOf( 'Users_List', $users_list );
		$this->assertInstanceOf( 'WP_List_Table', $users_list );
		$this->assertFalse( $users_list->no_groups );
	}

	/**
	 * Test get_users method with default parameters
	 */
	public function test_get_users_default_parameters() {
		// Create mock response.
		$mock_response = $this->get_mock_response( 'all-credentials-response' );
		$this->accredible_certificates->method( 'batch_requests' )->willReturn( $mock_response );

		$users = $this->users_list->get_users();

		// Check if users are returned.
		$this->assertIsArray( $users );
		$this->assertLessThanOrEqual( 20, count( $users ) );

		// Check structure of returned data.
		if ( ! empty( $users ) ) {
			$first_user = $users[0];
			$this->assertArrayHasKey( 'id', $first_user );
			$this->assertArrayHasKey( 'user_login', $first_user );
			$this->assertArrayHasKey( 'user_nicename', $first_user );
			$this->assertArrayHasKey( 'user_email', $first_user );
		}
	}

	/**
	 * Test get_users method with search parameter
	 */
	public function test_get_users_with_search() {
		// Set search query.
		$_REQUEST['s'] = 'testuser1';

		// Create mock response.
		$mock_response = $this->get_mock_response( 'all-credentials-response' );
		$this->accredible_certificates->method( 'batch_requests' )->willReturn( $mock_response );

		// Expect batch_requests to be called with the correct params.
		$this->accredible_certificates->expects( $this->once() )
			->method( 'batch_requests' )
			->with(
				$this->callback(
					function ( $requests ) {
						return 'all_credentials' === $requests[0]['url'] &&
								'testuser1@example.com' === $requests[0]['params']['email'];
					}
				)
			);

		$this->users_list->get_users();
	}

	/**
	 * Test get_group_select_options method.
	 */
	public function test_get_group_select_options() {
		// Create mock response.
		$mock_response = $this->get_mock_response( 'all-groups-response' );
		$this->accredible_certificates->method( 'get_groups' )->willReturn( $mock_response->groups );

		$options = $this->users_list->get_group_select_options();

		// Check if the options are returned.
		$this->assertStringContainsString( "<option value='1'>Group 1</option>", $options );
		$this->assertStringContainsString( "<option value='2'>Group 2</option>", $options );
	}

	/**
	 * Test process_bulk_action method with invalid nonce.
	 */
	public function test_process_bulk_action_with_invalid_nonce() {
		// Set action.
		$_REQUEST['action']                        = 'create-credentials';
		$_REQUEST['accredible_certificates_nonce'] = 'invalid-nonce';
		$_REQUEST['group_id']                      = 1;

		// Expect wp_die to be called with 'Invalid nonce.'.
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Invalid nonce.' );

		$this->users_list->process_bulk_action();
	}

	/**
	 * Test process_bulk_action method with valid nonce.
	 */
	public function test_process_bulk_action_with_valid_nonce() {
		// Set action.
		$_REQUEST['action']                     = 'create-credentials';
		$_POST['accredible_certificates_nonce'] = wp_create_nonce( 'accredible_certificates_bulk_action' );
		$_POST['credential_users']              = array( $this->test_users[0] );
		$_POST['group_id']                      = 1;

		// Stub the create_credential method.
		$this->accredible_certificates->method( 'create_credential' )->willReturn( array() );

		// Expect create_credential to be called with the correct params.
		$this->accredible_certificates->expects( $this->once() )
			->method( 'create_credential' )
			->with(
				$this->equalTo( 'Test User1' ),  // recipient_name.
				$this->equalTo( 'testuser1@example.com' ),  // email.
				$this->equalTo( 1 )  // group_id.
			);

		ob_start();
		$this->users_list->process_bulk_action();
		$output = ob_get_clean();

		// Check if the admin notice is displayed.
		$this->assertStringContainsString( 'Credentials created!', $output );
	}

		/**
		 * Create test users for testing.
		 */
	private function create_test_users() {
		$this->test_users = array();

		// Create test users.
		$user_id_1 = $this->insert_test_user( 1 );
		$user_id_2 = $this->insert_test_user( 2 );
		$user_id_3 = $this->insert_test_user( 3 );

		$this->test_users = array( $user_id_1, $user_id_2, $user_id_3 );
	}

	/**
	 * Clean up test users.
	 */
	private function cleanup_test_users() {
		foreach ( $this->test_users as $user_id ) {
			wp_delete_user( $user_id );
		}
		$this->test_users = array();
	}

	/**
	 * Get mock response from fixture.
	 *
	 * @param string $fixture_name fixture name.
	 * @return object
	 */
	private function get_mock_response( $fixture_name ) {
		$fixture_path = ACCREDIBLE_CERTIFICATES_FIXTURES_PATH . $fixture_name . '.json';
		return json_decode( file_get_contents( $fixture_path ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	}

	/**
	 * Insert test user.
	 *
	 * @param int $index index.
	 * @return int
	 */
	private function insert_test_user( $index ) {
		return wp_insert_user(
			array(
				'user_login'    => 'testuser' . $index,
				'user_nicename' => 'testuser' . $index,
				'user_email'    => 'testuser' . $index . '@example.com',
				'user_pass'     => 'password123',
				'first_name'    => 'Test',
				'last_name'     => 'User' . $index,
				'display_name'  => 'Test User' . $index,
			)
		);
	}
}
