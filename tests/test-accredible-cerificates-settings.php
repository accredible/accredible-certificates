<?php
/**
 * Test cases for Accredible Certificates Settings class
 *
 * @package Accredible_Certificates
 */

/**
 * Test class for settings functionality
 */
class Test_Accredible_Certificates_Settings extends WP_UnitTestCase {

	/**
	 * Instance of the settings class
	 *
	 * @var Accredible_Certificates_Settings
	 */
	private $settings;

	/**
	 * Set up test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a new instance of the settings class.
		$this->settings = new Accredible_Certificates_Settings();

		// Clean up any existing options.
		delete_option( 'api_key' );
	}

	/**
	 * Clean up after tests
	 */
	public function tearDown(): void {
		// Clean up options.
		delete_option( 'api_key' );

		// Remove any added actions.
		remove_action( 'admin_init', array( $this->settings, 'admin_init' ) );
		remove_action( 'admin_menu', array( $this->settings, 'add_menu' ) );

		parent::tearDown();
	}

	/**
	 * Test that the constructor adds the required actions
	 */
	public function test_constructor_adds_actions() {
		// Create a new instance to trigger constructor.
		$settings = new Accredible_Certificates_Settings();

		// Check that the actions were added.
		$this->assertGreaterThan( 0, has_action( 'admin_init', array( $settings, 'admin_init' ) ) );
		$this->assertGreaterThan( 0, has_action( 'admin_menu', array( $settings, 'add_menu' ) ) );
	}

	/**
	 * Test that admin_init registers the settings correctly
	 */
	public function test_admin_init_registers_settings() {
		// Call admin_init.
		$this->settings->admin_init();

		// Check that the option is registered.
		global $wp_settings_sections, $wp_settings_fields;

		// Verify the section was added.
		$this->assertArrayHasKey( 'accredible_certificates', $wp_settings_sections );
		$this->assertArrayHasKey( 'accredible_certificates-section', $wp_settings_sections['accredible_certificates'] );

		// Verify the field was added.
		$this->assertArrayHasKey( 'accredible_certificates', $wp_settings_fields );
		$this->assertArrayHasKey( 'accredible_certificates-section', $wp_settings_fields['accredible_certificates'] );
		$this->assertArrayHasKey( 'accredible_certificates-api_key', $wp_settings_fields['accredible_certificates']['accredible_certificates-section'] );
	}

	/**
	 * Test that add_menu adds the options page
	 */
	public function test_add_menu_adds_options_page() {
		// Call add_menu.
		$this->settings->add_menu();

		// Check that the action was registered for the menu callback.
		$this->assertGreaterThan( 0, has_action( 'admin_menu', array( $this->settings, 'add_menu' ) ) );
	}
}
