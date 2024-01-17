<?php
if(!class_exists('Accredible_Certificates_Settings'))
{
	class Accredible_Certificates_Settings
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
			// register actions
            add_action('admin_init', array(&$this, 'admin_init'));
        	add_action('admin_menu', array(&$this, 'add_menu'));
		} // END public function __construct
		
        /**
         * hook into WP's admin_init action hook
         */
        public function admin_init()
        {
        	// register your plugin's settings
        	register_setting('accredible_certificates-group', 'api_key');

            // add your settings section
            add_settings_section(
                'accredible_certificates-section', 
                'Accredible Credentials API Settings', 
                array(&$this, 'settings_section_accredible_certificates'), 
                'accredible_certificates'
            );

            // add your setting's fields
            add_settings_field(
                'accredible_certificates-api_key', 
                'API Key', 
                array(&$this, 'settings_field_input_text'), 
                'accredible_certificates', 
                'accredible_certificates-section',
                array(
                    'field' => 'api_key'
                )
            );

            $auto_sync = @Accredible_Certificate::auto_sync_available();

            if($auto_sync){
                register_setting('accredible_certificates-group', 'automatically_issue_certificates');

                 add_settings_field(
                    'accredible_certificates-automatically_issue_certificates', 
                    'Automatically Issue Credential upon Course Completition', 
                    array(&$this, 'settings_field_checkbox'), 
                    'accredible_certificates', 
                    'accredible_certificates-section',
                    array(
                        'field' => 'automatically_issue_certificates'
                    )
                );
            }
           
            // Possibly do additional admin_init tasks
        } // END public static function activate
        
        public function settings_section_accredible_certificates()
        {
            // Think of this as help text for the section.
            echo 'Enter your API key below:';
        }
        
        /**
         * This function provides text inputs for settings fields
         */
        public function settings_field_input_text($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting and escape it to prevent xss
            $value = esc_attr(get_option($field));
            // echo a proper input type="text"
            echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
        } // END public function settings_field_input_text($args)

        /**
         * This function provides checkbox inputs for settings fields
         */
        public function settings_field_checkbox($args)
        {
            // Get the field name from the $args array
            $field = $args['field'];
            // Get the value of this setting
            $value = get_option($field);
            
            $checkbox = sprintf('<input type="checkbox" name="%s" id="%s" value="1"', $field, $field);

            echo $checkbox . checked( 1, $value, false ) .  '/>';

        } // END public function settings_field_input_text($args)
        
        /**
         * add a menu
         */		
        public function add_menu()
        {
            // Add a page to manage this plugin's settings
        	add_options_page(
        	    'Accredible Certificates Settings', 
        	    'Accredible Certificates', 
        	    'manage_options', 
        	    'accredible_certificates', 
        	    array(&$this, 'plugin_settings_page')
        	);
        } // END public function add_menu()
    
        /**
         * Menu Callback
         */		
        public function plugin_settings_page()
        {
        	if(!current_user_can('manage_options'))
        	{
        		wp_die(__('You do not have sufficient permissions to access this page.'));
        	}
	
        	// Render the settings template
        	include(sprintf("%s/templates/settings.php", dirname(__FILE__)));
        } // END public function plugin_settings_page()
    } // END class accredible_certificates_Settings
} // END if(!class_exists('accredible_certificates_Settings'))
