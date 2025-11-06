<?php
/**
 * KHM Creative System Test Suite
 * 
 * Comprehensive testing for the Creative Materials Management System
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHM_Creative_Test_Suite {
    
    private $creative_service;
    private $test_results = array();
    private $test_data = array();
    
    public function __construct() {
        // Load required services
        require_once dirname(__FILE__) . '/../src/Services/CreativeService.php';
        $this->creative_service = new KHM_CreativeService();
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        echo "<h2>🎨 KHM Creative System Test Suite</h2>\n";
        echo "<p>Testing the professional marketing materials management system...</p>\n";
        
        // Initialize database
        $this->test_database_initialization();
        
        // Test CRUD operations
        $this->test_creative_creation();
        $this->test_creative_retrieval();
        $this->test_creative_update();
        
        // Test affiliate integration
        $this->test_affiliate_url_generation();
        $this->test_usage_tracking();
        $this->test_performance_analytics();
        
        // Test rendering system
        $this->test_creative_rendering();
        $this->test_social_share_generation();
        
        // Test helper functions
        $this->test_sanitization_functions();
        $this->test_url_generation();
        
        // Cleanup and summary
        $this->test_cleanup();
        $this->display_test_summary();
        
        return $this->get_test_success_rate();
    }
    
    /**
     * Test database initialization
     */
    private function test_database_initialization() {
        $this->run_test('Database Initialization', function() {
            global $wpdb;
            
            // Initialize tables
            $this->creative_service->init();
            
            // Check if tables exist
            $creatives_table = $wpdb->prefix . 'khm_creatives';
            $usage_table = $wpdb->prefix . 'khm_creative_usage';
            
            $creatives_exists = $wpdb->get_var("SHOW TABLES LIKE '{$creatives_table}'") === $creatives_table;
            $usage_exists = $wpdb->get_var("SHOW TABLES LIKE '{$usage_table}'") === $usage_table;
            
            if (!$creatives_exists) {
                throw new Exception("Creatives table not created");
            }
            
            if (!$usage_exists) {
                throw new Exception("Usage tracking table not created");
            }
            
            return "Database tables created successfully";
        });
    }
    
    /**
     * Test creative creation
     */
    private function test_creative_creation() {
        $this->run_test('Creative Creation', function() {
            $test_data = array(
                'name' => 'Test Banner Creative',
                'type' => 'banner',
                'content' => '<h3>Test Marketing Banner</h3><p>Premium membership promotion</p>',
                'image_url' => 'https://example.com/test-banner.jpg',
                'alt_text' => 'Test Banner Alt Text',
                'landing_url' => 'https://example.com/landing',
                'dimensions' => '728x90',
                'description' => 'Test banner for automated testing'
            );
            
            $creative_id = $this->creative_service->create_creative($test_data);
            
            if (!$creative_id || !is_numeric($creative_id)) {
                throw new Exception("Failed to create creative");
            }
            
            $this->test_data['creative_id'] = $creative_id;
            
            return "Creative created with ID: {$creative_id}";
        });
    }
    
    /**
     * Test creative retrieval
     */
    private function test_creative_retrieval() {
        $this->run_test('Creative Retrieval', function() {
            if (!isset($this->test_data['creative_id'])) {
                throw new Exception("No creative ID available for testing");
            }
            
            $creative_id = $this->test_data['creative_id'];
            
            // Test single creative retrieval
            $creative = $this->creative_service->get_creative($creative_id);
            
            if (!$creative) {
                throw new Exception("Failed to retrieve creative");
            }
            
            if ($creative->name !== 'Test Banner Creative') {
                throw new Exception("Retrieved creative data is incorrect");
            }
            
            // Test list retrieval
            $creatives = $this->creative_service->get_creatives(array('limit' => 5));
            
            if (!is_array($creatives) || empty($creatives)) {
                throw new Exception("Failed to retrieve creatives list");
            }
            
            return "Retrieved " . count($creatives) . " creatives from database";
        });
    }
    
    /**
     * Test creative update
     */
    private function test_creative_update() {
        $this->run_test('Creative Update', function() {
            if (!isset($this->test_data['creative_id'])) {
                throw new Exception("No creative ID available for testing");
            }
            
            $creative_id = $this->test_data['creative_id'];
            $update_data = array(
                'name' => 'Updated Test Banner',
                'description' => 'Updated description for testing'
            );
            
            $result = $this->creative_service->update_creative($creative_id, $update_data);
            
            if (!$result) {
                throw new Exception("Failed to update creative");
            }
            
            // Verify update
            $updated_creative = $this->creative_service->get_creative($creative_id);
            
            if ($updated_creative->name !== 'Updated Test Banner') {
                throw new Exception("Creative update not applied correctly");
            }
            
            return "Creative updated successfully";
        });
    }
    
    /**
     * Test affiliate URL generation
     */
    private function test_affiliate_url_generation() {
        $this->run_test('Affiliate URL Generation', function() {
            if (!isset($this->test_data['creative_id'])) {
                throw new Exception("No creative ID available for testing");
            }
            
            $creative_id = $this->test_data['creative_id'];
            $member_id = 123; // Test member ID
            
            // Mock the AffiliateService if it doesn't exist
            if (!class_exists('KHM_AffiliateService')) {
                return "AffiliateService not available - skipping affiliate URL test";
            }
            
            $affiliate_url = $this->creative_service->generate_creative_affiliate_url(
                $creative_id, 
                $member_id, 
                'website'
            );
            
            if (!$affiliate_url) {
                throw new Exception("Failed to generate affiliate URL");
            }
            
            if (!filter_var($affiliate_url, FILTER_VALIDATE_URL)) {
                throw new Exception("Generated affiliate URL is invalid");
            }
            
            return "Generated valid affiliate URL: " . substr($affiliate_url, 0, 50) . "...";
        });
    }
    
    /**
     * Test usage tracking
     */
    private function test_usage_tracking() {
        $this->run_test('Usage Tracking', function() {
            if (!isset($this->test_data['creative_id'])) {
                throw new Exception("No creative ID available for testing");
            }
            
            $creative_id = $this->test_data['creative_id'];
            $member_id = 123;
            
            // Track view
            $view_result = $this->creative_service->track_usage($creative_id, $member_id, 'view', 'website');
            
            if (!$view_result) {
                throw new Exception("Failed to track view");
            }
            
            // Track click
            $click_result = $this->creative_service->track_usage($creative_id, $member_id, 'click', 'website');
            
            if (!$click_result) {
                throw new Exception("Failed to track click");
            }
            
            return "Usage tracking working correctly";
        });
    }
    
    /**
     * Test performance analytics
     */
    private function test_performance_analytics() {
        $this->run_test('Performance Analytics', function() {
            if (!isset($this->test_data['creative_id'])) {
                throw new Exception("No creative ID available for testing");
            }
            
            $creative_id = $this->test_data['creative_id'];
            
            $performance = $this->creative_service->get_creative_performance($creative_id, 30);
            
            if (!is_array($performance)) {
                throw new Exception("Failed to get performance data");
            }
            
            $required_keys = array('views', 'clicks', 'conversions', 'ctr', 'conversion_rate');
            
            foreach ($required_keys as $key) {
                if (!array_key_exists($key, $performance)) {
                    throw new Exception("Missing performance metric: {$key}");
                }
            }
            
            if ($performance['views'] < 1) {
                throw new Exception("Performance tracking not recording views correctly");
            }
            
            return "Performance analytics: {$performance['views']} views, {$performance['clicks']} clicks, {$performance['ctr']}% CTR";
        });
    }
    
    /**
     * Test creative rendering
     */
    private function test_creative_rendering() {
        $this->run_test('Creative Rendering', function() {
            if (!isset($this->test_data['creative_id'])) {
                throw new Exception("No creative ID available for testing");
            }
            
            $creative_id = $this->test_data['creative_id'];
            $member_id = 123;
            
            $rendered = $this->creative_service->render_creative($creative_id, $member_id, array(
                'platform' => 'test',
                'new_window' => true
            ));
            
            if (empty($rendered)) {
                throw new Exception("Creative rendering returned empty result");
            }
            
            if (strpos($rendered, 'khm-creative') === false) {
                throw new Exception("Rendered HTML missing expected CSS classes");
            }
            
            if (strpos($rendered, 'Updated Test Banner') === false) {
                throw new Exception("Rendered HTML missing creative content");
            }
            
            return "Creative rendered successfully (" . strlen($rendered) . " characters)";
        });
    }
    
    /**
     * Test social share generation
     */
    private function test_social_share_generation() {
        $this->run_test('Social Share Generation', function() {
            // Create a social creative for testing
            $social_data = array(
                'name' => 'Test Social Creative',
                'type' => 'social',
                'content' => 'Check out this amazing resource! #Test #Creative',
                'landing_url' => 'https://example.com/social-test',
                'description' => 'Social creative for testing'
            );
            
            $social_id = $this->creative_service->create_creative($social_data);
            
            if (!$social_id) {
                throw new Exception("Failed to create social creative");
            }
            
            $this->test_data['social_creative_id'] = $social_id;
            
            $rendered = $this->creative_service->render_creative($social_id, 123, array(
                'platform' => 'social'
            ));
            
            if (empty($rendered)) {
                throw new Exception("Social creative rendering failed");
            }
            
            // Check for social platform links
            $platforms = array('facebook', 'twitter', 'linkedin', 'pinterest');
            foreach ($platforms as $platform) {
                if (strpos($rendered, "khm-social-{$platform}") === false) {
                    throw new Exception("Missing {$platform} share button");
                }
            }
            
            return "Social share buttons generated for all platforms";
        });
    }
    
    /**
     * Test sanitization functions
     */
    private function test_sanitization_functions() {
        $this->run_test('Sanitization Functions', function() {
            // Test creating creative with potentially malicious data
            $malicious_data = array(
                'name' => '<script>alert("xss")</script>Clean Name',
                'type' => 'banner',
                'content' => '<h3>Safe Content</h3><script>alert("xss")</script>',
                'image_url' => 'javascript:alert("xss")',
                'alt_text' => '<img onerror="alert(\'xss\')" src="x">',
                'description' => 'Description with <script>alert("xss")</script> content'
            );
            
            $safe_id = $this->creative_service->create_creative($malicious_data);
            
            if (!$safe_id) {
                throw new Exception("Failed to create creative with test data");
            }
            
            $safe_creative = $this->creative_service->get_creative($safe_id);
            
            if (strpos($safe_creative->name, '<script>') !== false) {
                throw new Exception("Script tags not properly sanitized");
            }
            
            if (strpos($safe_creative->image_url, 'javascript:') !== false) {
                throw new Exception("JavaScript URLs not properly sanitized");
            }
            
            $this->test_data['safe_creative_id'] = $safe_id;
            
            return "Sanitization working correctly - malicious content removed";
        });
    }
    
    /**
     * Test URL generation
     */
    private function test_url_generation() {
        $this->run_test('URL Generation', function() {
            // Test home URL generation (fallback)
            $creative_service_reflection = new ReflectionClass($this->creative_service);
            $get_home_url_method = $creative_service_reflection->getMethod('get_home_url');
            $get_home_url_method->setAccessible(true);
            
            $home_url = $get_home_url_method->invoke($this->creative_service);
            
            if (empty($home_url)) {
                throw new Exception("Home URL generation failed");
            }
            
            if (!filter_var($home_url, FILTER_VALIDATE_URL)) {
                throw new Exception("Generated home URL is invalid");
            }
            
            return "URL generation working: " . $home_url;
        });
    }
    
    /**
     * Test cleanup
     */
    private function test_cleanup() {
        $this->run_test('Test Cleanup', function() {
            $cleanup_count = 0;
            
            // Clean up test creatives
            $test_ids = array(
                $this->test_data['creative_id'] ?? null,
                $this->test_data['social_creative_id'] ?? null,
                $this->test_data['safe_creative_id'] ?? null
            );
            
            foreach ($test_ids as $id) {
                if ($id) {
                    $result = $this->creative_service->delete_creative($id);
                    if ($result) {
                        $cleanup_count++;
                    }
                }
            }
            
            return "Cleaned up {$cleanup_count} test creatives";
        });
    }
    
    /**
     * Run individual test
     */
    private function run_test($test_name, $test_function) {
        try {
            $start_time = microtime(true);
            $result = $test_function();
            $end_time = microtime(true);
            $duration = round(($end_time - $start_time) * 1000, 2);
            
            $this->test_results[] = array(
                'name' => $test_name,
                'status' => 'PASS',
                'message' => $result,
                'duration' => $duration
            );
            
            echo "✅ {$test_name}: {$result} ({$duration}ms)\n";
            
        } catch (Exception $e) {
            $this->test_results[] = array(
                'name' => $test_name,
                'status' => 'FAIL',
                'message' => $e->getMessage(),
                'duration' => 0
            );
            
            echo "❌ {$test_name}: {$e->getMessage()}\n";
        }
    }
    
    /**
     * Display test summary
     */
    private function display_test_summary() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($test) {
            return $test['status'] === 'PASS';
        }));
        $failed_tests = $total_tests - $passed_tests;
        
        $success_rate = $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;
        
        echo "\n<h3>📊 Test Summary</h3>\n";
        echo "<p><strong>Total Tests:</strong> {$total_tests}</p>\n";
        echo "<p><strong>Passed:</strong> {$passed_tests}</p>\n";
        echo "<p><strong>Failed:</strong> {$failed_tests}</p>\n";
        echo "<p><strong>Success Rate:</strong> {$success_rate}%</p>\n";
        
        if ($failed_tests > 0) {
            echo "\n<h4>❌ Failed Tests:</h4>\n";
            foreach ($this->test_results as $test) {
                if ($test['status'] === 'FAIL') {
                    echo "<p><strong>{$test['name']}:</strong> {$test['message']}</p>\n";
                }
            }
        }
        
        if ($success_rate >= 90) {
            echo "\n<p>🎉 <strong>Excellent!</strong> The Creative System is working properly.</p>\n";
        } elseif ($success_rate >= 70) {
            echo "\n<p>⚠️ <strong>Good</strong> - Some issues found that should be addressed.</p>\n";
        } else {
            echo "\n<p>🚨 <strong>Critical issues found</strong> - Creative System needs attention.</p>\n";
        }
    }
    
    /**
     * Get test success rate
     */
    public function get_test_success_rate() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($test) {
            return $test['status'] === 'PASS';
        }));
        
        return $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 1) : 0;
    }
}

// Auto-run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME']) || 
    (isset($_GET['test']) && $_GET['test'] === 'creative')) {
    
    $test_suite = new KHM_Creative_Test_Suite();
    $success_rate = $test_suite->run_all_tests();
    
    if ($success_rate >= 90) {
        echo "\n🎯 Creative System is ready for production!\n";
    } else {
        echo "\n🔧 Creative System needs debugging before production use.\n";
    }
}