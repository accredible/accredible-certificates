<?php
/**
 * Test cases for Accredible Certificates activation and deactivation
 *
 * @package Accredible_Certificates
 */

/**
 * Test class for plugin activation and deactivation functionality
 */
class Test_Accredible_Certificates_Activation extends WP_UnitTestCase {
    /**
	 * Clean up plugin data (options, hooks, and database table)
	 */
	private function cleanup_plugin_data() {
		// Clear any existing options and scheduled hooks
		delete_option( 'automatically_issue_certificates' );
		delete_option( 'accredible_db_version' );
		wp_clear_scheduled_hook( 'hourly_certificate_issuance' );
		
		// Clear any existing database table
		global $wpdb;
		$table_name = $wpdb->prefix . 'accredible_mapping';
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
	}

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();
		$this->cleanup_plugin_data();
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		$this->cleanup_plugin_data();
		parent::tearDown();
	}

	/**
	 * Test that the activate method creates the database table
	 */
	public function test_activate_creates_database_table() {
        global $wpdb;
		$table_name = $wpdb->prefix . 'accredible_mapping';

        // Verify table doesn't exist before activation
        $this->assertFalse( $this->table_exists( $table_name ) );

		// Activate the plugin
		Accredible_Certificates::activate();
		
        // Verify table exists after activation
		$columns = $wpdb->get_results( "DESCRIBE $table_name" );
        $this->assertCount( 3, $columns );
	}

	/**
	 * Test that the activate method adds the automatically_issue_certificates option
	 */
	public function test_activate_adds_automatically_issue_certificates_option() {
		// Verify option doesn't exist before activation
		$this->assertFalse( get_option( 'automatically_issue_certificates' ) );
		
		// Activate the plugin
		Accredible_Certificates::activate();
		
		// Verify option exists and has correct default value
		$this->assertEquals( 0, get_option( 'automatically_issue_certificates' ) );
	}

	/**
	 * Test that the activate method adds the accredible_db_version option
	 */
	public function test_activate_adds_accredible_db_version_option() {
		// Verify option doesn't exist before activation
		$this->assertFalse( get_option( 'accredible_db_version' ) );
		
		// Activate the plugin
		Accredible_Certificates::activate();
		
		// Verify option exists and has correct value
		$this->assertEquals( '1.0.0', get_option( 'accredible_db_version' ) );
	}

	/**
	 * Test that the activate method schedules the hourly cron job
	 */
	public function test_activate_schedules_hourly_cron_job() {
		// Verify no scheduled hook exists before activation
		$this->assertFalse( wp_next_scheduled( 'hourly_certificate_issuance' ) );
		
		// Activate the plugin
		Accredible_Certificates::activate();
		
		// Verify scheduled hook exists after activation
		$this->assertNotFalse( wp_next_scheduled( 'hourly_certificate_issuance' ) );
		
		// Verify it's scheduled for hourly frequency
		$scheduled_time = wp_next_scheduled( 'hourly_certificate_issuance' );
		$current_time = time();
		
		// The scheduled time should be within the next hour
		$this->assertGreaterThanOrEqual( $current_time, $scheduled_time );
		$this->assertLessThanOrEqual( $current_time + 3600, $scheduled_time );
	}

	/**
	 * Test that the deactivate method clears the scheduled hook
	 */
	public function test_deactivate_clears_scheduled_hook() {
		// First activate to create the scheduled hook
		Accredible_Certificates::activate();
		
		// Verify scheduled hook exists
		$this->assertNotFalse( wp_next_scheduled( 'hourly_certificate_issuance' ) );
		
		// Deactivate the plugin
		Accredible_Certificates::deactivate();
		
		// Verify scheduled hook is cleared
		$this->assertFalse( wp_next_scheduled( 'hourly_certificate_issuance' ) );
	}

	/**
	 * Test that the deactivate method doesn't remove database table or options
	 */
	public function test_deactivate_preserves_database_and_options() {
		// First activate to create table and options
		Accredible_Certificates::activate();
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'accredible_mapping';
        $columns = $wpdb->get_results( "DESCRIBE $table_name" );
		
		// Verify table and options exist after activation
        $this->assertCount( 3, $columns );
		$this->assertEquals( 0, get_option( 'automatically_issue_certificates' ) );
		$this->assertEquals( '1.0.0', get_option( 'accredible_db_version' ) );
		
		// Deactivate the plugin
		Accredible_Certificates::deactivate();
		
		// Verify table and options still exist after deactivation
        $this->assertCount( 3, $columns );
		$this->assertEquals( 0, get_option( 'automatically_issue_certificates' ) );
		$this->assertEquals( '1.0.0', get_option( 'accredible_db_version' ) );
	}

	/**
	 * Test database table structure in detail
	 */
	public function test_database_table_structure() {
		// Activate the plugin
		Accredible_Certificates::activate();
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'accredible_mapping';
		
		// Get table structure
		$columns = $wpdb->get_results( "DESCRIBE $table_name" );
		
		// Verify we have exactly 3 columns
		$this->assertCount( 3, $columns );
		
		// Verify column details
		$column_map = array();
		foreach ( $columns as $column ) {
			$column_map[ $column->Field ] = $column;
		}
		
		// Test id column
		$this->assertArrayHasKey( 'id', $column_map );
		$this->assertEquals( 'mediumint', $column_map['id']->Type );
		$this->assertEquals( 'NO', $column_map['id']->Null );
		$this->assertEquals( 'auto_increment', $column_map['id']->Extra );
		
		// Test course_id column
		$this->assertArrayHasKey( 'course_id', $column_map );
		$this->assertEquals( 'mediumint', $column_map['course_id']->Type );
		$this->assertEquals( 'NO', $column_map['course_id']->Null );
		
		// Test group_id column
		$this->assertArrayHasKey( 'group_id', $column_map );
		$this->assertEquals( 'mediumint', $column_map['group_id']->Type );
		$this->assertEquals( 'NO', $column_map['group_id']->Null );
	}


	/**
	 * Check if the provided table name exists in the DB.
	 *
	 * @param string $table_name Table name.
	 */
	private function table_exists( $table_name ) {
		global $wpdb;
		$table_name = $wpdb->prefix . $table_name;
		// Disable `PreparedSQL` since there are no inputs from users.
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		$sql = "SHOW TABLES LIKE '$table_name'";
		return $wpdb->get_var( $sql ) === $table_name;
		// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared
	}
} 