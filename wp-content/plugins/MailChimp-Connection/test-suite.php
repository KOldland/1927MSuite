<?php
/**
 * TouchPoint MailChimp Plugin Test Suite
 * 
 * Comprehensive testing for the MailChimp integration plugin
 */

// Suppress WordPress function warnings for testing
error_reporting(E_ERROR | E_PARSE);

class TouchPoint_MailChimp_Test_Suite {
    
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $test_results = array();
    
    public function run_all_tests() {
        echo "=== TouchPoint MailChimp Plugin Test Suite ===" . PHP_EOL;
        echo "Starting comprehensive plugin tests..." . PHP_EOL . PHP_EOL;
        
        // Core functionality tests
        $this->test_file_structure();
        $this->test_class_loading();
        $this->test_singleton_patterns();
        $this->test_api_methods();
        $this->test_settings_functionality();
        $this->test_logger_functionality();
        $this->test_admin_functionality();
        $this->test_form_template();
        $this->test_module_structure();
        $this->test_asset_files();
        
        // Summary
        $this->print_summary();
    }
    
    private function test_file_structure() {
        $this->test_section("File Structure Tests");
        
        $required_files = array(
            'touchpoint-mailchimp.php' => 'Main plugin file',
            'includes/class-autoloader.php' => 'Autoloader class',
            'includes/class-api.php' => 'API wrapper class',
            'includes/class-settings.php' => 'Settings management',
            'includes/class-logger.php' => 'Logger class',
            'includes/admin/class-admin.php' => 'Admin interface',
            'includes/modules/class-user-sync.php' => 'User sync module',
            'includes/modules/class-ecommerce.php' => 'E-commerce module',
            'templates/subscription-form.php' => 'Form template',
            'assets/css/admin.css' => 'Admin styles',
            'assets/css/frontend.css' => 'Frontend styles',
            'assets/js/admin.js' => 'Admin JavaScript',
            'assets/js/frontend.js' => 'Frontend JavaScript',
            'README.md' => 'Documentation'
        );
        
        foreach ($required_files as $file => $description) {
            $this->assert_file_exists($file, $description);
        }
        
        $this->test_directory_structure();
    }
    
    private function test_directory_structure() {
        $required_dirs = array(
            'includes',
            'includes/admin',
            'includes/modules',
            'assets',
            'assets/css',
            'assets/js',
            'templates'
        );
        
        foreach ($required_dirs as $dir) {
            $this->assert_directory_exists($dir, "Directory: $dir");
        }
    }
    
    private function test_class_loading() {
        $this->test_section("Class Loading Tests");
        
        try {
            require_once 'includes/class-autoloader.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_Autoloader'), 'Autoloader class exists');
            
            require_once 'includes/class-settings.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_Settings'), 'Settings class exists');
            
            require_once 'includes/class-logger.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_Logger'), 'Logger class exists');
            
            require_once 'includes/class-api.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_API'), 'API class exists');
            
            require_once 'includes/admin/class-admin.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_Admin'), 'Admin class exists');
            
            require_once 'includes/modules/class-user-sync.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_User_Sync'), 'User Sync class exists');
            
            require_once 'includes/modules/class-ecommerce.php';
            $this->assert_true(class_exists('TouchPoint_MailChimp_Ecommerce'), 'E-commerce class exists');
            
        } catch (Exception $e) {
            $this->assert_false(true, 'Exception during class loading: ' . $e->getMessage());
        }
    }
    
    private function test_singleton_patterns() {
        $this->test_section("Singleton Pattern Tests");
        
        // Test Settings singleton
        $settings1 = TouchPoint_MailChimp_Settings::instance();
        $settings2 = TouchPoint_MailChimp_Settings::instance();
        $this->assert_true($settings1 === $settings2, 'Settings singleton pattern working');
        
        // Test Logger singleton
        $logger1 = TouchPoint_MailChimp_Logger::instance();
        $logger2 = TouchPoint_MailChimp_Logger::instance();
        $this->assert_true($logger1 === $logger2, 'Logger singleton pattern working');
        
        // Test API singleton
        $api1 = TouchPoint_MailChimp_API::instance();
        $api2 = TouchPoint_MailChimp_API::instance();
        $this->assert_true($api1 === $api2, 'API singleton pattern working');
    }
    
    private function test_api_methods() {
        $this->test_section("API Methods Tests");
        
        $api = TouchPoint_MailChimp_API::instance();
        
        $required_methods = array(
            'set_api_key',
            'test_connection',
            'get_lists',
            'subscribe_to_list',
            'get_list_member',
            'update_list_member',
            'delete_list_member',
            'batch_subscribe',
            'get_list_interest_categories',
            'get_category_interests',
            'add_order',
            'add_customer',
            'create_store'
        );
        
        foreach ($required_methods as $method) {
            $this->assert_true(method_exists($api, $method), "API method exists: $method");
        }
        
        // Test API key validation
        $result = $api->set_api_key('test-key-123');
        $this->assert_true(is_bool($result), 'API key setting returns boolean');
    }
    
    private function test_settings_functionality() {
        $this->test_section("Settings Functionality Tests");
        
        $settings = TouchPoint_MailChimp_Settings::instance();
        
        $required_methods = array(
            'get',
            'set',
            'get_default_list',
            'get_field_mappings',
            'validate_settings'
        );
        
        foreach ($required_methods as $method) {
            $this->assert_true(method_exists($settings, $method), "Settings method exists: $method");
        }
        
        // Test setting and getting values
        $settings->set('test_key', 'test_value');
        $value = $settings->get('test_key');
        $this->assert_equals('test_value', $value, 'Settings set/get functionality');
        
        // Test default values
        $default = $settings->get('non_existent_key', 'default_value');
        $this->assert_equals('default_value', $default, 'Settings default values');
    }
    
    private function test_logger_functionality() {
        $this->test_section("Logger Functionality Tests");
        
        $logger = TouchPoint_MailChimp_Logger::instance();
        
        $required_methods = array(
            'log',
            'get_logs',
            'clear_logs'
        );
        
        foreach ($required_methods as $method) {
            $this->assert_true(method_exists($logger, $method), "Logger method exists: $method");
        }
        
        // Test logging
        $logger->clear_logs();
        $logger->log('Test message', 'info');
        $logs = $logger->get_logs();
        $this->assert_true(strpos($logs, 'Test message') !== false, 'Logger stores messages');
    }
    
    private function test_admin_functionality() {
        $this->test_section("Admin Functionality Tests");
        
        $admin = TouchPoint_MailChimp_Admin::instance();
        
        $required_methods = array(
            'init',
            'admin_menu',
            'render_main_page',
            'settings_sections',
            'test_api_connection',
            'sync_users'
        );
        
        foreach ($required_methods as $method) {
            $this->assert_true(method_exists($admin, $method), "Admin method exists: $method");
        }
    }
    
    private function test_form_template() {
        $this->test_section("Form Template Tests");
        
        $template_content = file_get_contents('templates/subscription-form.php');
        
        $this->assert_true(!empty($template_content), 'Form template has content');
        $this->assert_true(strpos($template_content, 'tmc-subscription-form') !== false, 'Form has correct CSS class');
        $this->assert_true(strpos($template_content, 'email') !== false, 'Form includes email field');
        $this->assert_true(strpos($template_content, 'submit') !== false, 'Form includes submit button');
    }
    
    private function test_module_structure() {
        $this->test_section("Module Structure Tests");
        
        // Test User Sync module
        $user_sync = TouchPoint_MailChimp_User_Sync::instance();
        $sync_methods = array(
            'sync_user_to_mailchimp',
            'bulk_sync_users',
            'handle_user_registration',
            'handle_user_update'
        );
        
        foreach ($sync_methods as $method) {
            $this->assert_true(method_exists($user_sync, $method), "User Sync method exists: $method");
        }
        
        // Test E-commerce module
        $ecommerce = TouchPoint_MailChimp_Ecommerce::instance();
        $ecommerce_methods = array(
            'track_woo_order',
            'track_edd_order',
            'track_woo_customer',
            'track_cart_update'
        );
        
        foreach ($ecommerce_methods as $method) {
            $this->assert_true(method_exists($ecommerce, $method), "E-commerce method exists: $method");
        }
    }
    
    private function test_asset_files() {
        $this->test_section("Asset Files Tests");
        
        // Test CSS files
        $admin_css = file_get_contents('assets/css/admin.css');
        $this->assert_true(!empty($admin_css), 'Admin CSS file has content');
        $this->assert_true(strpos($admin_css, 'tmc-admin') !== false, 'Admin CSS has correct classes');
        
        $frontend_css = file_get_contents('assets/css/frontend.css');
        $this->assert_true(!empty($frontend_css), 'Frontend CSS file has content');
        $this->assert_true(strpos($frontend_css, 'tmc-subscription-form') !== false, 'Frontend CSS has form styles');
        
        // Test JavaScript files
        $admin_js = file_get_contents('assets/js/admin.js');
        $this->assert_true(!empty($admin_js), 'Admin JS file has content');
        $this->assert_true(strpos($admin_js, 'TMCAdmin') !== false, 'Admin JS has main object');
        
        $frontend_js = file_get_contents('assets/js/frontend.js');
        $this->assert_true(!empty($frontend_js), 'Frontend JS file has content');
        $this->assert_true(strpos($frontend_js, 'handleFormSubmission') !== false, 'Frontend JS has form handling');
    }
    
    // Test utility methods
    
    private function test_section($title) {
        echo "--- $title ---" . PHP_EOL;
    }
    
    private function assert_true($condition, $message) {
        if ($condition) {
            $this->tests_passed++;
            echo "✓ PASS: $message" . PHP_EOL;
            $this->test_results[] = array('status' => 'PASS', 'message' => $message);
        } else {
            $this->tests_failed++;
            echo "✗ FAIL: $message" . PHP_EOL;
            $this->test_results[] = array('status' => 'FAIL', 'message' => $message);
        }
    }
    
    private function assert_false($condition, $message) {
        $this->assert_true(!$condition, $message);
    }
    
    private function assert_equals($expected, $actual, $message) {
        $this->assert_true($expected === $actual, "$message (expected: $expected, actual: $actual)");
    }
    
    private function assert_file_exists($file, $description) {
        $this->assert_true(file_exists($file), "$description file exists: $file");
    }
    
    private function assert_directory_exists($dir, $description) {
        $this->assert_true(is_dir($dir), "$description exists: $dir");
    }
    
    private function print_summary() {
        echo PHP_EOL . "=== Test Summary ===" . PHP_EOL;
        echo "Tests Passed: {$this->tests_passed}" . PHP_EOL;
        echo "Tests Failed: {$this->tests_failed}" . PHP_EOL;
        echo "Total Tests: " . ($this->tests_passed + $this->tests_failed) . PHP_EOL;
        
        $success_rate = $this->tests_passed / ($this->tests_passed + $this->tests_failed) * 100;
        echo "Success Rate: " . number_format($success_rate, 1) . "%" . PHP_EOL;
        
        if ($this->tests_failed === 0) {
            echo PHP_EOL . "🎉 ALL TESTS PASSED! Plugin is ready for production." . PHP_EOL;
        } else {
            echo PHP_EOL . "⚠️  Some tests failed. Review the issues above." . PHP_EOL;
        }
    }
}

// Run the test suite
$test_suite = new TouchPoint_MailChimp_Test_Suite();
$test_suite->run_all_tests();
?>