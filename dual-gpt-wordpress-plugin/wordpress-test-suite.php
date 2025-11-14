<?php
/**
 * Dual-GPT WordPress Plugin - WordPress Environment Test Suite
 *
 * This script validates the plugin works correctly in a real WordPress environment.
 * Run this after plugin activation to ensure everything is working.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('This script must be run within WordPress environment');
}

class Dual_GPT_WordPress_Test_Suite {

    private $results = array();
    private $errors = array();

    public function __construct() {
        $this->results = array(
            'environment' => array(),
            'database' => array(),
            'plugin_structure' => array(),
            'wordpress_integration' => array(),
            'api_integration' => array(),
            'admin_interface' => array(),
            'gutenberg_integration' => array(),
        );
    }

    public function run_all_tests() {
        echo "<h1>Dual-GPT WordPress Plugin - Environment Test Suite</h1>";
        echo "<p>Testing plugin functionality in WordPress environment...</p>";

        $this->test_environment();
        $this->test_database();
        $this->test_plugin_structure();
        $this->test_wordpress_integration();
        $this->test_api_integration();
        $this->test_admin_interface();
        $this->test_gutenberg_integration();

        $this->display_results();
    }

    private function test_environment() {
        echo "<h2>1. Environment Tests</h2>";

        // PHP Version
        $php_version = phpversion();
        $php_ok = version_compare($php_version, '7.4', '>=');
        $this->results['environment']['php_version'] = array(
            'status' => $php_ok ? 'pass' : 'fail',
            'message' => "PHP Version: $php_version" . ($php_ok ? ' ✓' : ' ✗ (requires 7.4+)'),
            'details' => $php_ok ? '' : 'Please upgrade PHP to version 7.4 or higher'
        );

        // WordPress Version
        global $wp_version;
        $wp_ok = version_compare($wp_version, '5.8', '>=');
        $this->results['environment']['wp_version'] = array(
            'status' => $wp_ok ? 'pass' : 'fail',
            'message' => "WordPress Version: $wp_version" . ($wp_ok ? ' ✓' : ' ✗ (requires 5.8+)'),
            'details' => $wp_ok ? '' : 'Please upgrade WordPress to version 5.8 or higher'
        );

        // Required Extensions
        $extensions = array('curl', 'json', 'openssl');
        foreach ($extensions as $ext) {
            $loaded = extension_loaded($ext);
            $this->results['environment']["ext_$ext"] = array(
                'status' => $loaded ? 'pass' : 'fail',
                'message' => "PHP Extension '$ext': " . ($loaded ? 'Loaded ✓' : 'Not loaded ✗'),
                'details' => $loaded ? '' : "Please install/enable PHP $ext extension"
            );
        }

        // Memory Limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = wp_convert_hr_to_bytes($memory_limit);
        $memory_ok = $memory_bytes >= 128 * 1024 * 1024; // 128MB
        $this->results['environment']['memory_limit'] = array(
            'status' => $memory_ok ? 'pass' : 'warning',
            'message' => "Memory Limit: $memory_limit" . ($memory_ok ? ' ✓' : ' ⚠ (recommended 128MB+)'),
            'details' => $memory_ok ? '' : 'Consider increasing memory_limit for better AI processing'
        );
    }

    private function test_database() {
        echo "<h2>2. Database Tests</h2>";

        global $wpdb;

        $tables = array(
            'ai_sessions' => $wpdb->prefix . 'ai_sessions',
            'ai_jobs' => $wpdb->prefix . 'ai_jobs',
            'ai_presets' => $wpdb->prefix . 'ai_presets',
            'ai_audit' => $wpdb->prefix . 'ai_audit',
            'ai_budgets' => $wpdb->prefix . 'ai_budgets',
        );

        foreach ($tables as $key => $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $this->results['database']["table_$key"] = array(
                'status' => $exists ? 'pass' : 'fail',
                'message' => "Table '$table': " . ($exists ? 'Exists ✓' : 'Missing ✗'),
                'details' => $exists ? '' : 'Plugin may not have activated properly'
            );
        }

        // Test database connection
        $db_ok = $wpdb->check_connection();
        $this->results['database']['connection'] = array(
            'status' => $db_ok ? 'pass' : 'fail',
            'message' => 'Database Connection: ' . ($db_ok ? 'OK ✓' : 'Failed ✗'),
            'details' => $db_ok ? '' : 'Check database credentials in wp-config.php'
        );
    }

    private function test_plugin_structure() {
        echo "<h2>3. Plugin Structure Tests</h2>";

        $plugin_dir = plugin_dir_path(__FILE__);

        $required_files = array(
            'dual-gpt-wordpress-plugin.php',
            'includes/class-dual-gpt-plugin.php',
            'includes/class-db-handler.php',
            'includes/class-openai-connector.php',
            'admin/class-dual-gpt-admin.php',
            'assets/js/sidebar.js',
            'admin/js/admin.js',
        );

        foreach ($required_files as $file) {
            $exists = file_exists($plugin_dir . $file);
            $this->results['plugin_structure']["file_" . basename($file, '.php')] = array(
                'status' => $exists ? 'pass' : 'fail',
                'message' => "File '$file': " . ($exists ? 'Exists ✓' : 'Missing ✗'),
                'details' => $exists ? '' : 'Plugin files may be corrupted or incomplete'
            );
        }

        // Test class loading
        $classes = array(
            'Dual_GPT_Plugin',
            'Dual_GPT_DB_Handler',
            'Dual_GPT_OpenAI_Connector',
        );

        foreach ($classes as $class) {
            $exists = class_exists($class);
            $this->results['plugin_structure']["class_$class"] = array(
                'status' => $exists ? 'pass' : 'fail',
                'message' => "Class '$class': " . ($exists ? 'Loaded ✓' : 'Not loaded ✗'),
                'details' => $exists ? '' : 'Check file permissions and PHP include paths'
            );
        }
    }

    private function test_wordpress_integration() {
        echo "<h2>4. WordPress Integration Tests</h2>";

        // Test hooks
        $hooks = array(
            'plugins_loaded' => 'dual_gpt_plugin_init',
            'admin_menu' => array('Dual_GPT_Plugin', 'add_admin_menu'),
            'enqueue_block_editor_assets' => array('Dual_GPT_Plugin', 'enqueue_block_editor_assets'),
        );

        foreach ($hooks as $hook => $callback) {
            $has_hook = has_action($hook, $callback);
            $this->results['wordpress_integration']["hook_$hook"] = array(
                'status' => $has_hook ? 'pass' : 'fail',
                'message' => "Hook '$hook': " . ($has_hook ? 'Registered ✓' : 'Not registered ✗'),
                'details' => $has_hook ? '' : 'Plugin initialization may have failed'
            );
        }

        // Test REST routes
        global $wp_rest_server;
        if ($wp_rest_server) {
            $routes = $wp_rest_server->get_routes();
            $has_routes = isset($routes['dual-gpt/v1']);
            $this->results['wordpress_integration']['rest_routes'] = array(
                'status' => $has_routes ? 'pass' : 'fail',
                'message' => 'REST API Routes: ' . ($has_routes ? 'Registered ✓' : 'Not registered ✗'),
                'details' => $has_routes ? '' : 'REST API endpoints not available'
            );
        }

        // Test capabilities
        $user = wp_get_current_user();
        $can_edit = user_can($user, 'edit_posts');
        $this->results['wordpress_integration']['capabilities'] = array(
            'status' => $can_edit ? 'pass' : 'warning',
            'message' => 'User Capabilities: ' . ($can_edit ? 'Can edit posts ✓' : 'Cannot edit posts ⚠'),
            'details' => $can_edit ? '' : 'User may not be able to use plugin features'
        );
    }

    private function test_api_integration() {
        echo "<h2>5. API Integration Tests</h2>";

        // Test API key configuration
        $api_key_methods = array(
            'constant' => defined('DUAL_GPT_OPENAI_API_KEY'),
            'option' => get_option('dual_gpt_openai_api_key'),
            'environment' => getenv('OPENAI_API_KEY'),
        );

        $has_api_key = $api_key_methods['constant'] || $api_key_methods['option'] || $api_key_methods['environment'];
        $this->results['api_integration']['api_key_config'] = array(
            'status' => $has_api_key ? 'pass' : 'warning',
            'message' => 'API Key Configuration: ' . ($has_api_key ? 'Configured ✓' : 'Not configured ⚠'),
            'details' => $has_api_key ? '' : 'Configure API key in wp-config.php for full functionality'
        );

        // Test OpenAI connector
        if (class_exists('Dual_GPT_OpenAI_Connector')) {
            $connector = new Dual_GPT_OpenAI_Connector();
            $has_key = $connector->validate_api_key();
            $this->results['api_integration']['api_key_validation'] = array(
                'status' => $has_key ? 'pass' : 'warning',
                'message' => 'API Key Validation: ' . ($has_key ? 'Valid ✓' : 'Invalid ⚠'),
                'details' => $has_key ? '' : 'Check API key configuration'
            );
        }
    }

    private function test_admin_interface() {
        echo "<h2>6. Admin Interface Tests</h2>";

        // Test admin menu
        global $menu, $submenu;
        $has_menu = false;
        if (isset($submenu['options-general.php'])) {
            foreach ($submenu['options-general.php'] as $item) {
                if (strpos($item[2], 'dual-gpt') !== false) {
                    $has_menu = true;
                    break;
                }
            }
        }

        $this->results['admin_interface']['admin_menu'] = array(
            'status' => $has_menu ? 'pass' : 'fail',
            'message' => 'Admin Menu: ' . ($has_menu ? 'Present ✓' : 'Missing ✗'),
            'details' => $has_menu ? '' : 'Admin interface may not be accessible'
        );

        // Test admin scripts/styles
        $scripts = array('dual-gpt-admin', 'dual-gpt-admin-css');
        foreach ($scripts as $script) {
            $registered = wp_script_is($script, 'registered') || wp_style_is($script, 'registered');
            $this->results['admin_interface']["asset_$script"] = array(
                'status' => $registered ? 'pass' : 'warning',
                'message' => "Asset '$script': " . ($registered ? 'Registered ✓' : 'Not registered ⚠'),
                'details' => $registered ? '' : 'Admin interface may have styling/functionality issues'
            );
        }
    }

    private function test_gutenberg_integration() {
        echo "<h2>7. Gutenberg Integration Tests</h2>";

        // Test block editor assets
        $assets = array('dual-gpt-sidebar', 'dual-gpt-sidebar-css');
        foreach ($assets as $asset) {
            $registered = wp_script_is($asset, 'registered') || wp_style_is($asset, 'registered');
            $this->results['gutenberg_integration']["asset_$asset"] = array(
                'status' => $registered ? 'pass' : 'warning',
                'message' => "Asset '$asset': " . ($registered ? 'Registered ✓' : 'Not registered ⚠'),
                'details' => $registered ? '' : 'Gutenberg sidebar may not load properly'
            );
        }

        // Test if Gutenberg is available
        $gutenberg_available = function_exists('register_block_type') && function_exists('wp_enqueue_script');
        $this->results['gutenberg_integration']['gutenberg_available'] = array(
            'status' => $gutenberg_available ? 'pass' : 'fail',
            'message' => 'Gutenberg Available: ' . ($gutenberg_available ? 'Yes ✓' : 'No ✗'),
            'details' => $gutenberg_available ? '' : 'WordPress version may not support Gutenberg'
        );
    }

    private function display_results() {
        echo "<h2>Test Results Summary</h2>";

        $total_tests = 0;
        $passed_tests = 0;
        $failed_tests = 0;
        $warning_tests = 0;

        foreach ($this->results as $category => $tests) {
            echo "<h3>" . ucfirst(str_replace('_', ' ', $category)) . "</h3>";
            echo "<table style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Test</th><th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Status</th><th style='border: 1px solid #ddd; padding: 8px; text-align: left;'>Details</th></tr>";

            foreach ($tests as $test => $result) {
                $total_tests++;
                $status_color = '';
                switch ($result['status']) {
                    case 'pass': $status_color = 'green'; $passed_tests++; break;
                    case 'fail': $status_color = 'red'; $failed_tests++; break;
                    case 'warning': $status_color = 'orange'; $warning_tests++; break;
                }

                echo "<tr>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$result['message']}</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px; color: $status_color; font-weight: bold;'>" . strtoupper($result['status']) . "</td>";
                echo "<td style='border: 1px solid #ddd; padding: 8px;'>{$result['details']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }

        echo "<h3>Overall Summary</h3>";
        echo "<p>Total Tests: $total_tests</p>";
        echo "<p style='color: green;'>Passed: $passed_tests</p>";
        echo "<p style='color: orange;'>Warnings: $warning_tests</p>";
        echo "<p style='color: red;'>Failed: $failed_tests</p>";

        if ($failed_tests > 0) {
            echo "<div style='background: #ffe6e6; border: 1px solid #ff9999; padding: 10px; margin: 10px 0;'>";
            echo "<strong>❌ Critical Issues Found</strong><br>";
            echo "The plugin may not function properly. Please address the failed tests before proceeding.";
            echo "</div>";
        } elseif ($warning_tests > 0) {
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0;'>";
            echo "<strong>⚠️ Warnings Present</strong><br>";
            echo "The plugin should work but some features may be limited. Consider addressing warnings for optimal performance.";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0;'>";
            echo "<strong>✅ All Tests Passed</strong><br>";
            echo "The plugin is ready for production use!";
            echo "</div>";
        }

        echo "<p><em>Test completed at: " . current_time('mysql') . "</em></p>";
    }
}

// Run the test suite if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'wordpress-test-suite.php') {
    $test_suite = new Dual_GPT_WordPress_Test_Suite();
    $test_suite->run_all_tests();
}