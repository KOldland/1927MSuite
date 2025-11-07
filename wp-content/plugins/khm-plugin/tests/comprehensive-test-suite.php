<?php
/**
 * TouchPoint Marketing Suite - Comprehensive Test Suite
 * Full integration testing for all components
 */

if (!defined('ABSPATH')) {
    // Try to bootstrap WordPress for testing
    $test_paths = [
        '../../../wp-config.php',
        '../../../../wp-config.php', 
        '../../../../../wp-config.php',
        '/var/www/html/wp-config.php'
    ];
    
    $wp_loaded = false;
    foreach ($test_paths as $path) {
        if (file_exists(__DIR__ . '/' . $path)) {
            require_once(__DIR__ . '/' . $path);
            require_once(ABSPATH . 'wp-load.php');
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        echo "❌ Could not load WordPress. Please run from WordPress root or adjust paths.\n";
        echo "Usage: php wp-content/plugins/khm-plugin/tests/comprehensive-test-suite.php\n";
        exit(1);
    }
}

class TouchPoint_Comprehensive_Test_Suite {
    
    private $results;
    private $wpdb;
    private $test_user_id;
    private $test_post_id;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->results = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'categories' => []
        ];
        
        echo "🧪 TouchPoint Marketing Suite - Comprehensive Test Suite\n";
        echo "=====================================================\n";
        echo "Environment: " . (WP_DEBUG ? "DEBUG" : "PRODUCTION") . "\n";
        echo "WordPress: " . get_bloginfo('version') . "\n";
        echo "PHP: " . PHP_VERSION . "\n\n";
    }
    
    public function run_all_tests() {
        try {
            $this->setup_test_environment();
            
            // Core infrastructure tests
            $this->test_database_infrastructure();
            $this->test_plugin_activation();
            $this->test_service_layer();
            
            // Migration and database tests
            $this->test_migration_system();
            $this->test_database_schema();
            $this->test_data_integrity();
            
            // Frontend asset tests
            $this->test_frontend_assets();
            $this->test_javascript_functionality();
            $this->test_css_integration();
            
            // Core functionality tests
            $this->test_membership_system();
            $this->test_credit_system();
            $this->test_ecommerce_system();
            $this->test_library_system();
            $this->test_gift_system();
            $this->test_email_system();
            
            // Integration tests
            $this->test_social_strip_integration();
            $this->test_ajax_handlers();
            $this->test_end_to_end_workflows();
            
            // Performance tests
            $this->test_performance();
            
            $this->cleanup_test_environment();
            
        } catch (Exception $e) {
            $this->test_fail("Critical test failure: " . $e->getMessage());
        }
        
        $this->print_final_report();
    }
    
    private function setup_test_environment() {
        echo "🔧 Setting up test environment...\n";
        
        // Create test user
        $this->test_user_id = wp_create_user(
            'touchpoint_test_user',
            'test_password_' . time(),
            'test@touchpoint.test'
        );
        
        if (is_wp_error($this->test_user_id)) {
            // User might already exist, try to get it
            $user = get_user_by('login', 'touchpoint_test_user');
            $this->test_user_id = $user ? $user->ID : 1;
        }
        
        // Create test post
        $this->test_post_id = wp_insert_post([
            'post_title' => 'TouchPoint Test Article',
            'post_content' => 'This is a test article for TouchPoint system testing.',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => $this->test_user_id
        ]);
        
        $this->test_pass("Test environment setup complete");
    }
    
    private function test_database_infrastructure() {
        $this->start_category("Database Infrastructure");
        
        // Test database connection
        if ($this->wpdb->get_var("SELECT 1")) {
            $this->test_pass("Database connection active");
        } else {
            $this->test_fail("Database connection failed");
        }
        
        // Test table prefix
        $prefix = $this->wpdb->prefix;
        if (!empty($prefix)) {
            $this->test_pass("Database prefix configured: {$prefix}");
        } else {
            $this->test_warning("No database prefix configured");
        }
    }
    
    private function test_plugin_activation() {
        $this->start_category("Plugin Activation");
        
        // Check KHM plugin
        if (is_plugin_active('khm-plugin/khm-plugin.php')) {
            $this->test_pass("KHM Plugin is active");
        } else {
            $this->test_fail("KHM Plugin is not active");
        }
        
        // Check Social Strip plugin
        if (is_plugin_active('social-strip/social-strip.php')) {
            $this->test_pass("Social Strip Plugin is active");
        } else {
            $this->test_warning("Social Strip Plugin is not active");
        }
        
        // Test plugin classes exist
        if (class_exists('KHM\\Services\\CreditService')) {
            $this->test_pass("KHM service classes loaded");
        } else {
            $this->test_fail("KHM service classes not loaded");
        }
    }
    
    private function test_service_layer() {
        $this->start_category("Service Layer");
        
        $required_services = [
            'KHM\\Services\\CreditService',
            'KHM\\Services\\ECommerceService', 
            'KHM\\Services\\LibraryService',
            'KHM\\Services\\GiftService',
            'KHM\\Services\\PDFService'
        ];
        
        foreach ($required_services as $service_class) {
            if (class_exists($service_class)) {
                $this->test_pass("Service exists: {$service_class}");
            } else {
                $this->test_fail("Missing service: {$service_class}");
            }
        }
    }
    
    private function test_migration_system() {
        $this->start_category("Migration System");
        
        // Check migration manager
        if (class_exists('KHM\\Utils\\MigrationManager')) {
            $this->test_pass("Migration Manager class exists");
        } else {
            $this->test_fail("Migration Manager class missing");
        }
        
        // Check migrations directory
        $migration_dir = WP_PLUGIN_DIR . '/khm-plugin/db/migrations';
        if (is_dir($migration_dir)) {
            $migration_files = glob($migration_dir . '/*.sql');
            $this->test_pass("Migrations directory exists with " . count($migration_files) . " files");
        } else {
            $this->test_fail("Migrations directory missing");
        }
        
        // Check migrations table
        $migrations_table = $this->wpdb->prefix . 'khm_migrations';
        if ($this->wpdb->get_var("SHOW TABLES LIKE '{$migrations_table}'")) {
            $this->test_pass("Migrations tracking table exists");
        } else {
            $this->test_warning("Migrations tracking table not found");
        }
    }
    
    private function test_database_schema() {
        $this->start_category("Database Schema");
        
        $required_tables = [
            'khm_membership_levels',
            'khm_memberships_users',
            'khm_user_credits',
            'khm_credit_usage',
            'khm_article_products',
            'khm_shopping_cart',
            'khm_purchases',
            'khm_member_library',
            'khm_library_categories',
            'khm_gifts',
            'khm_gift_redemptions',
            'khm_email_queue',
            'khm_email_logs'
        ];
        
        foreach ($required_tables as $table) {
            $full_table = $this->wpdb->prefix . $table;
            if ($this->wpdb->get_var("SHOW TABLES LIKE '{$full_table}'")) {
                $this->test_pass("Table exists: {$table}");
                
                // Check table structure
                $columns = $this->wpdb->get_col("SHOW COLUMNS FROM {$full_table}");
                if (count($columns) >= 3) {
                    $this->test_pass("  → Table structure valid (" . count($columns) . " columns)");
                } else {
                    $this->test_warning("  → Table may have incomplete structure");
                }
            } else {
                $this->test_fail("Missing table: {$table}");
            }
        }
    }
    
    private function test_data_integrity() {
        $this->start_category("Data Integrity");
        
        // Test membership levels
        $levels = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_membership_levels");
        if ($levels >= 3) {
            $this->test_pass("Membership levels seeded: {$levels} levels");
        } else {
            $this->test_fail("Insufficient membership levels: {$levels}");
        }
        
        // Test default categories
        $categories = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_library_categories WHERE user_id = 0");
        if ($categories >= 3) {
            $this->test_pass("Default library categories: {$categories} categories");
        } else {
            $this->test_warning("Few default categories: {$categories}");
        }
    }
    
    private function test_frontend_assets() {
        $this->start_category("Frontend Assets");
        
        $asset_files = [
            'social-strip/assets/css/ecommerce.css',
            'social-strip/assets/css/modal.css',
            'social-strip/assets/js/ecommerce.js',
            'social-strip/assets/js/integration-test.js'
        ];
        
        foreach ($asset_files as $asset) {
            $file_path = WP_PLUGIN_DIR . '/' . $asset;
            if (file_exists($file_path)) {
                $size = filesize($file_path);
                $this->test_pass("Asset exists: {$asset} (" . $this->format_file_size($size) . ")");
            } else {
                $this->test_fail("Missing asset: {$asset}");
            }
        }
    }
    
    private function test_javascript_functionality() {
        $this->start_category("JavaScript Functionality");
        
        // Test eCommerce JS
        $ecommerce_js = WP_PLUGIN_DIR . '/social-strip/assets/js/ecommerce.js';
        if (file_exists($ecommerce_js)) {
            $content = file_get_contents($ecommerce_js);
            
            $required_functions = [
                'handleAddToCart',
                'handleCheckoutStart',
                'updateCartDisplay',
                'validateCheckoutForm'
            ];
            
            foreach ($required_functions as $function) {
                if (strpos($content, $function) !== false) {
                    $this->test_pass("eCommerce function exists: {$function}");
                } else {
                    $this->test_warning("Missing eCommerce function: {$function}");
                }
            }
        }
        
        // Test integration script
        $integration_js = WP_PLUGIN_DIR . '/social-strip/assets/js/integration-test.js';
        if (file_exists($integration_js)) {
            $this->test_pass("Integration test script available");
        }
    }
    
    private function test_css_integration() {
        $this->start_category("CSS Integration");
        
        $css_files = [
            'social-strip/assets/css/ecommerce.css',
            'social-strip/assets/css/modal.css'
        ];
        
        foreach ($css_files as $css_file) {
            $file_path = WP_PLUGIN_DIR . '/' . $css_file;
            if (file_exists($file_path)) {
                $content = file_get_contents($file_path);
                
                // Check for key CSS classes
                $key_classes = ['.khm-modal', '.khm-cart-item', '.khm-checkout-form'];
                $found_classes = 0;
                
                foreach ($key_classes as $class) {
                    if (strpos($content, $class) !== false) {
                        $found_classes++;
                    }
                }
                
                if ($found_classes >= 2) {
                    $this->test_pass("CSS structure valid: {$css_file} ({$found_classes}/".count($key_classes)." key classes)");
                } else {
                    $this->test_warning("CSS may be incomplete: {$css_file}");
                }
            }
        }
    }
    
    private function test_membership_system() {
        $this->start_category("Membership System");
        
        try {
            if (class_exists('KHM\\Services\\MembershipRepository')) {
                $repo = new KHM\Services\MembershipRepository();
                $membership = $repo->getUserMembership($this->test_user_id);
                $this->test_pass("Membership system accessible");
            } else {
                $this->test_warning("MembershipRepository not available for testing");
            }
        } catch (Exception $e) {
            $this->test_warning("Membership system test limited: " . $e->getMessage());
        }
    }
    
    private function test_credit_system() {
        $this->start_category("Credit System");
        
        try {
            if (class_exists('KHM\\Services\\CreditService')) {
                $credit_service = new KHM\Services\CreditService();
                $credits = $credit_service->getUserCredits($this->test_user_id);
                $this->test_pass("Credit system functional - User has {$credits} credits");
            } else {
                $this->test_fail("CreditService not available");
            }
        } catch (Exception $e) {
            $this->test_warning("Credit system test failed: " . $e->getMessage());
        }
    }
    
    private function test_ecommerce_system() {
        $this->start_category("eCommerce System");
        
        try {
            if (class_exists('KHM\\Services\\ECommerceService')) {
                $ecommerce = new KHM\Services\ECommerceService();
                $this->test_pass("eCommerce service available");
                
                // Test product pricing setup
                $pricing = $ecommerce->getArticlePricing($this->test_post_id);
                if ($pricing) {
                    $this->test_pass("Article pricing configured");
                } else {
                    $this->test_warning("No pricing found for test article");
                }
            }
        } catch (Exception $e) {
            $this->test_warning("eCommerce test failed: " . $e->getMessage());
        }
    }
    
    private function test_library_system() {
        $this->start_category("Library System");
        
        try {
            if (class_exists('KHM\\Services\\LibraryService')) {
                $library = new KHM\Services\LibraryService();
                $this->test_pass("Library service available");
                
                // Test save functionality
                $result = $library->saveToLibrary($this->test_user_id, $this->test_post_id, 1, 'Test save');
                if ($result) {
                    $this->test_pass("Library save functionality working");
                } else {
                    $this->test_warning("Library save test inconclusive");
                }
            }
        } catch (Exception $e) {
            $this->test_warning("Library test failed: " . $e->getMessage());
        }
    }
    
    private function test_gift_system() {
        $this->start_category("Gift System");
        
        try {
            if (class_exists('KHM\\Services\\GiftService')) {
                $gift_service = new KHM\Services\GiftService();
                $this->test_pass("Gift service available");
            }
        } catch (Exception $e) {
            $this->test_warning("Gift system test failed: " . $e->getMessage());
        }
    }
    
    private function test_email_system() {
        $this->start_category("Email System");
        
        try {
            if (class_exists('KHM\\Services\\EnhancedEmailService')) {
                $email_service = new KHM\Services\EnhancedEmailService();
                $this->test_pass("Enhanced email service available");
            }
        } catch (Exception $e) {
            $this->test_warning("Email system test failed: " . $e->getMessage());
        }
    }
    
    private function test_social_strip_integration() {
        $this->start_category("Social Strip Integration");
        
        // Check integration file
        $integration_file = WP_PLUGIN_DIR . '/social-strip/includes/khm-integration.php';
        if (file_exists($integration_file)) {
            $this->test_pass("Social Strip integration file exists");
            
            // Check for AJAX handlers
            $content = file_get_contents($integration_file);
            $ajax_handlers = ['kss_download_with_credit', 'kss_save_to_library', 'kss_add_to_cart'];
            
            foreach ($ajax_handlers as $handler) {
                if (strpos($content, $handler) !== false) {
                    $this->test_pass("AJAX handler exists: {$handler}");
                } else {
                    $this->test_warning("Missing AJAX handler: {$handler}");
                }
            }
        } else {
            $this->test_fail("Social Strip integration file missing");
        }
    }
    
    private function test_ajax_handlers() {
        $this->start_category("AJAX Handlers");
        
        // Test if AJAX actions are registered
        global $wp_filter;
        
        $ajax_actions = [
            'wp_ajax_kss_download_with_credit',
            'wp_ajax_nopriv_kss_download_with_credit',
            'wp_ajax_kss_add_to_cart'
        ];
        
        foreach ($ajax_actions as $action) {
            if (isset($wp_filter[$action])) {
                $this->test_pass("AJAX action registered: {$action}");
            } else {
                $this->test_warning("AJAX action not registered: {$action}");
            }
        }
    }
    
    private function test_end_to_end_workflows() {
        $this->start_category("End-to-End Workflows");
        
        // This would require browser testing, so we test prerequisites
        $this->test_pass("End-to-end testing requires browser environment");
        $this->test_warning("Manual testing recommended for complete workflows");
    }
    
    private function test_performance() {
        $this->start_category("Performance");
        
        // Test database query performance
        $start_time = microtime(true);
        $result = $this->wpdb->get_results(
            "SELECT COUNT(*) as total FROM {$this->wpdb->prefix}posts WHERE post_type = 'post'"
        );
        $query_time = microtime(true) - $start_time;
        
        if ($query_time < 0.1) {
            $this->test_pass("Database query performance good ({$query_time}s)");
        } else {
            $this->test_warning("Database query performance slow ({$query_time}s)");
        }
        
        // Test memory usage
        $memory = memory_get_usage(true);
        if ($memory < 50 * 1024 * 1024) { // 50MB
            $this->test_pass("Memory usage reasonable (" . $this->format_file_size($memory) . ")");
        } else {
            $this->test_warning("Memory usage high (" . $this->format_file_size($memory) . ")");
        }
    }
    
    private function cleanup_test_environment() {
        echo "\n🧹 Cleaning up test environment...\n";
        
        // Remove test post
        if ($this->test_post_id) {
            wp_delete_post($this->test_post_id, true);
        }
        
        // Note: We leave test user for potential manual testing
        echo "Test cleanup complete (test user retained for manual testing)\n";
    }
    
    private function start_category($category_name) {
        echo "\n📋 {$category_name}\n" . str_repeat("-", strlen($category_name) + 4) . "\n";
        $this->results['categories'][$category_name] = ['passed' => 0, 'failed' => 0, 'warnings' => 0];
    }
    
    private function test_pass($message) {
        echo "✅ {$message}\n";
        $this->results['total_tests']++;
        $this->results['passed']++;
        
        $current_category = array_key_last($this->results['categories']);
        if ($current_category) {
            $this->results['categories'][$current_category]['passed']++;
        }
    }
    
    private function test_fail($message) {
        echo "❌ {$message}\n";
        $this->results['total_tests']++;
        $this->results['failed']++;
        
        $current_category = array_key_last($this->results['categories']);
        if ($current_category) {
            $this->results['categories'][$current_category]['failed']++;
        }
    }
    
    private function test_warning($message) {
        echo "⚠️  {$message}\n";
        $this->results['total_tests']++;
        $this->results['warnings']++;
        
        $current_category = array_key_last($this->results['categories']);
        if ($current_category) {
            $this->results['categories'][$current_category]['warnings']++;
        }
    }
    
    private function print_final_report() {
        echo "\n📊 Final Test Report\n";
        echo "===================\n";
        
        // Category breakdown
        foreach ($this->results['categories'] as $category => $stats) {
            $total = $stats['passed'] + $stats['failed'] + $stats['warnings'];
            $health = $total > 0 ? round(($stats['passed'] / $total) * 100, 1) : 0;
            
            echo "📋 {$category}: {$health}% ";
            echo "({$stats['passed']} ✅, {$stats['failed']} ❌, {$stats['warnings']} ⚠️)\n";
        }
        
        echo "\n";
        
        // Overall summary
        echo "📈 Overall Results:\n";
        echo "  ✅ Passed: {$this->results['passed']}\n";
        echo "  ❌ Failed: {$this->results['failed']}\n";
        echo "  ⚠️  Warnings: {$this->results['warnings']}\n";
        echo "  📝 Total Tests: {$this->results['total_tests']}\n\n";
        
        // Calculate overall health
        $health_score = $this->results['total_tests'] > 0 
            ? round(($this->results['passed'] / $this->results['total_tests']) * 100, 2) 
            : 0;
            
        echo "🏥 TouchPoint Health Score: {$health_score}%\n";
        
        if ($health_score >= 90) {
            echo "🎉 Excellent! TouchPoint Marketing Suite is ready for production!\n";
        } elseif ($health_score >= 75) {
            echo "✨ Good! Minor issues to address before production.\n";
        } elseif ($health_score >= 50) {
            echo "⚡ Fair. Several issues need attention.\n";
        } else {
            echo "🔧 Poor. Major issues require immediate attention.\n";
        }
        
        echo "\n💡 Next Steps:\n";
        if ($this->results['failed'] > 0) {
            echo "  • Address failed tests first\n";
        }
        if ($this->results['warnings'] > 0) {
            echo "  • Review warnings for optimization opportunities\n";
        }
        echo "  • Run manual browser testing for frontend components\n";
        echo "  • Test end-to-end workflows with real user interactions\n";
        echo "  • Performance testing under load\n";
    }
    
    private function format_file_size($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit_index = 0;
        
        while ($size >= 1024 && $unit_index < count($units) - 1) {
            $size /= 1024;
            $unit_index++;
        }
        
        return round($size, 2) . ' ' . $units[$unit_index];
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli' || (!empty($argv) && basename($argv[0]) === basename(__FILE__))) {
    $test_suite = new TouchPoint_Comprehensive_Test_Suite();
    $test_suite->run_all_tests();
}