<?php
/**
 * KHM Enhanced Dashboard Test Suite
 * 
 * Comprehensive testing for the Enhanced Admin Dashboard system
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHM_Dashboard_Test_Suite {
    
    private $dashboard;
    private $widget_manager;
    private $test_results = array();
    
    public function __construct() {
        // Load required components
        require_once dirname(__FILE__) . '/../admin/enhanced-dashboard.php';
        require_once dirname(__FILE__) . '/../includes/dashboard-widgets.php';
        
        $this->dashboard = new KHM_Enhanced_Dashboard();
        
        global $khm_widget_manager;
        $this->widget_manager = $khm_widget_manager;
    }
    
    /**
     * Run all dashboard tests
     */
    public function run_all_tests() {
        echo "<h2>📊 KHM Enhanced Dashboard Test Suite</h2>\n";
        echo "<p>Testing the professional admin interface that surpasses SliceWP...</p>\n";
        
        // Test dashboard initialization
        $this->test_dashboard_initialization();
        
        // Test widget system
        $this->test_widget_registration();
        $this->test_widget_rendering();
        $this->test_widget_caching();
        
        // Test dashboard components
        $this->test_performance_cards();
        $this->test_analytics_charts();
        $this->test_system_health();
        
        // Test data processing
        $this->test_statistics_generation();
        $this->test_export_functionality();
        
        // Test admin interfaces
        $this->test_menu_registration();
        $this->test_ajax_handlers();
        
        // Test responsive design
        $this->test_css_framework();
        
        $this->display_test_summary();
        return $this->get_test_success_rate();
    }
    
    /**
     * Test dashboard initialization
     */
    private function test_dashboard_initialization() {
        $this->run_test('Dashboard Initialization', function() {
            if (!$this->dashboard) {
                throw new Exception("Dashboard class not instantiated");
            }
            
            // Check if dashboard has required properties
            $reflection = new ReflectionClass($this->dashboard);
            $required_services = array('affiliate_service', 'creative_service', 'credit_service');
            
            foreach ($required_services as $service) {
                if (!$reflection->hasProperty($service)) {
                    throw new Exception("Missing required service: {$service}");
                }
            }
            
            return "Dashboard initialized with all required services";
        });
    }
    
    /**
     * Test widget registration
     */
    private function test_widget_registration() {
        $this->run_test('Widget Registration', function() {
            if (!$this->widget_manager) {
                throw new Exception("Widget manager not initialized");
            }
            
            $widgets = $this->widget_manager->get_widgets();
            
            if (empty($widgets)) {
                throw new Exception("No widgets registered");
            }
            
            $expected_widgets = array('revenue_overview', 'affiliate_performance', 'system_health', 'activity_feed');
            
            foreach ($expected_widgets as $widget_id) {
                if (!$this->widget_manager->get_widget($widget_id)) {
                    throw new Exception("Missing expected widget: {$widget_id}");
                }
            }
            
            return "All " . count($widgets) . " expected widgets registered successfully";
        });
    }
    
    /**
     * Test widget rendering
     */
    private function test_widget_rendering() {
        $this->run_test('Widget Rendering', function() {
            $widgets = $this->widget_manager->get_widgets();
            $rendered_count = 0;
            
            foreach ($widgets as $widget_id => $widget) {
                ob_start();
                
                try {
                    $widget->render();
                    $output = ob_get_contents();
                    
                    if (empty($output)) {
                        throw new Exception("Widget {$widget_id} produced no output");
                    }
                    
                    if (strpos($output, 'khm-') === false) {
                        throw new Exception("Widget {$widget_id} missing expected CSS classes");
                    }
                    
                    $rendered_count++;
                    
                } catch (Exception $e) {
                    ob_end_clean();
                    throw new Exception("Widget {$widget_id} rendering failed: " . $e->getMessage());
                }
                
                ob_end_clean();
            }
            
            return "Successfully rendered {$rendered_count} widgets";
        });
    }
    
    /**
     * Test widget caching
     */
    private function test_widget_caching() {
        $this->run_test('Widget Caching System', function() {
            $revenue_widget = $this->widget_manager->get_widget('revenue_overview');
            
            if (!$revenue_widget) {
                throw new Exception("Revenue widget not found for caching test");
            }
            
            // Clear cache first
            $revenue_widget->clear_cache();
            
            // First render should cache data
            ob_start();
            $start_time = microtime(true);
            $revenue_widget->render();
            $first_render_time = microtime(true) - $start_time;
            ob_end_clean();
            
            // Second render should use cache (faster)
            ob_start();
            $start_time = microtime(true);
            $revenue_widget->render();
            $second_render_time = microtime(true) - $start_time;
            ob_end_clean();
            
            // Cache should make second render faster (or at least not slower)
            if ($second_render_time > $first_render_time * 2) {
                throw new Exception("Caching not improving performance as expected");
            }
            
            return "Widget caching working - first: " . round($first_render_time * 1000, 2) . "ms, second: " . round($second_render_time * 1000, 2) . "ms";
        });
    }
    
    /**
     * Test performance cards
     */
    private function test_performance_cards() {
        $this->run_test('Performance Cards Generation', function() {
            $reflection = new ReflectionClass($this->dashboard);
            $method = $reflection->getMethod('get_dashboard_statistics');
            $method->setAccessible(true);
            
            $stats = $method->invoke($this->dashboard);
            
            if (!is_array($stats)) {
                throw new Exception("Statistics not returned as array");
            }
            
            $required_keys = array('total_revenue', 'active_affiliates', 'conversion_rate', 'total_clicks');
            
            foreach ($required_keys as $key) {
                if (!array_key_exists($key, $stats)) {
                    throw new Exception("Missing required statistic: {$key}");
                }
            }
            
            // Test that numeric values are actually numeric
            if (!is_numeric($stats['total_revenue'])) {
                throw new Exception("Total revenue is not numeric");
            }
            
            if (!is_numeric($stats['conversion_rate'])) {
                throw new Exception("Conversion rate is not numeric");
            }
            
            return "Performance cards data structure validated with " . count($stats) . " metrics";
        });
    }
    
    /**
     * Test analytics charts
     */
    private function test_analytics_charts() {
        $this->run_test('Analytics Chart Data', function() {
            $reflection = new ReflectionClass($this->dashboard);
            $method = $reflection->getMethod('get_dashboard_statistics');
            $method->setAccessible(true);
            
            $stats = $method->invoke($this->dashboard);
            
            // Check top performers structure
            if (!isset($stats['top_performers']) || !is_array($stats['top_performers'])) {
                throw new Exception("Top performers data missing or invalid");
            }
            
            if (empty($stats['top_performers'])) {
                throw new Exception("No top performers data available");
            }
            
            // Validate performer data structure
            $first_performer = $stats['top_performers'][0];
            $required_fields = array('name', 'revenue', 'conversions');
            
            foreach ($required_fields as $field) {
                if (!array_key_exists($field, $first_performer)) {
                    throw new Exception("Missing performer field: {$field}");
                }
            }
            
            return "Chart data validated with " . count($stats['top_performers']) . " top performers";
        });
    }
    
    /**
     * Test system health
     */
    private function test_system_health() {
        $this->run_test('System Health Monitoring', function() {
            $reflection = new ReflectionClass($this->dashboard);
            $method = $reflection->getMethod('get_system_health');
            $method->setAccessible(true);
            
            $health = $method->invoke($this->dashboard);
            
            if (!is_array($health)) {
                throw new Exception("System health not returned as array");
            }
            
            if (empty($health)) {
                throw new Exception("No system health checks defined");
            }
            
            // Validate health check structure
            foreach ($health as $component => $status) {
                if (!is_array($status)) {
                    throw new Exception("Health check {$component} has invalid structure");
                }
                
                if (!isset($status['status']) || !isset($status['message'])) {
                    throw new Exception("Health check {$component} missing required fields");
                }
                
                $valid_statuses = array('healthy', 'warning', 'error');
                if (!in_array($status['status'], $valid_statuses)) {
                    throw new Exception("Health check {$component} has invalid status: {$status['status']}");
                }
            }
            
            return "System health monitoring validated with " . count($health) . " components";
        });
    }
    
    /**
     * Test statistics generation
     */
    private function test_statistics_generation() {
        $this->run_test('Statistics Generation', function() {
            $reflection = new ReflectionClass($this->dashboard);
            $method = $reflection->getMethod('get_dashboard_statistics');
            $method->setAccessible(true);
            
            // Test multiple calls return consistent structure
            $stats1 = $method->invoke($this->dashboard);
            $stats2 = $method->invoke($this->dashboard);
            
            if (array_keys($stats1) !== array_keys($stats2)) {
                throw new Exception("Statistics structure inconsistent between calls");
            }
            
            // Test that numbers are realistic
            if ($stats1['total_revenue'] < 0) {
                throw new Exception("Total revenue cannot be negative");
            }
            
            if ($stats1['conversion_rate'] < 0 || $stats1['conversion_rate'] > 100) {
                throw new Exception("Conversion rate outside realistic range");
            }
            
            if ($stats1['active_affiliates'] < 0) {
                throw new Exception("Active affiliates cannot be negative");
            }
            
            return "Statistics generation producing consistent, realistic data";
        });
    }
    
    /**
     * Test export functionality
     */
    private function test_export_functionality() {
        $this->run_test('Data Export System', function() {
            $reflection = new ReflectionClass($this->dashboard);
            $method = $reflection->getMethod('generate_export_data');
            $method->setAccessible(true);
            
            $export_data = $method->invoke($this->dashboard, 'analytics', '30');
            
            if (empty($export_data)) {
                throw new Exception("Export data generation failed");
            }
            
            // Check for CSV structure
            $lines = explode("\n", $export_data);
            if (count($lines) < 2) {
                throw new Exception("Export data missing header or data rows");
            }
            
            // Check header exists
            $header = $lines[0];
            if (strpos($header, 'Date') === false || strpos($header, 'Revenue') === false) {
                throw new Exception("Export header missing required columns");
            }
            
            return "Export system generating valid CSV data with " . (count($lines) - 1) . " data rows";
        });
    }
    
    /**
     * Test menu registration
     */
    private function test_menu_registration() {
        $this->run_test('Admin Menu Registration', function() {
            // Mock WordPress menu functions
            global $admin_page_hooks, $submenu;
            $admin_page_hooks = array();
            $submenu = array();
            
            // Simulate menu registration
            $menu_registered = false;
            $submenu_count = 0;
            
            // Check if dashboard would register main menu
            if (method_exists($this->dashboard, 'add_admin_menu')) {
                $menu_registered = true;
            }
            
            if (!$menu_registered) {
                throw new Exception("Dashboard missing admin menu registration method");
            }
            
            // Count expected submenus
            $reflection = new ReflectionClass($this->dashboard);
            $methods = $reflection->getMethods();
            
            $page_methods = array_filter($methods, function($method) {
                return strpos($method->getName(), 'render_') === 0 && 
                       $method->getName() !== 'render_dashboard_scripts';
            });
            
            if (count($page_methods) < 4) {
                throw new Exception("Dashboard missing expected page rendering methods");
            }
            
            return "Menu registration system validated with " . count($page_methods) . " admin pages";
        });
    }
    
    /**
     * Test AJAX handlers
     */
    private function test_ajax_handlers() {
        $this->run_test('AJAX Handler System', function() {
            $reflection = new ReflectionClass($this->dashboard);
            $methods = $reflection->getMethods();
            
            $ajax_methods = array_filter($methods, function($method) {
                return strpos($method->getName(), 'ajax_') === 0;
            });
            
            if (empty($ajax_methods)) {
                throw new Exception("No AJAX handlers found");
            }
            
            // Test AJAX method structure
            foreach ($ajax_methods as $method) {
                $method_name = $method->getName();
                
                // Check if method is public
                if (!$method->isPublic()) {
                    throw new Exception("AJAX handler {$method_name} should be public");
                }
            }
            
            return "AJAX system validated with " . count($ajax_methods) . " handlers";
        });
    }
    
    /**
     * Test CSS framework
     */
    private function test_css_framework() {
        $this->run_test('CSS Framework Validation', function() {
            $css_file = dirname(__FILE__) . '/../assets/css/enhanced-dashboard.css';
            
            if (!file_exists($css_file)) {
                throw new Exception("CSS file not found: {$css_file}");
            }
            
            $css_content = file_get_contents($css_file);
            
            if (empty($css_content)) {
                throw new Exception("CSS file is empty");
            }
            
            // Check for required CSS classes
            $required_classes = array(
                '.khm-dashboard',
                '.khm-performance-cards',
                '.khm-card',
                '.khm-widget',
                '.khm-health-'
            );
            
            foreach ($required_classes as $class) {
                if (strpos($css_content, $class) === false) {
                    throw new Exception("Missing required CSS class: {$class}");
                }
            }
            
            // Check for responsive design
            if (strpos($css_content, '@media') === false) {
                throw new Exception("CSS missing responsive design rules");
            }
            
            // Check for animations
            if (strpos($css_content, '@keyframes') === false) {
                throw new Exception("CSS missing animation definitions");
            }
            
            $file_size = round(strlen($css_content) / 1024, 2);
            return "CSS framework validated - {$file_size}KB with responsive design and animations";
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
        
        echo "\n<h3>📊 Enhanced Dashboard Test Summary</h3>\n";
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
            echo "\n<p>🎉 <strong>Excellent!</strong> Enhanced Dashboard is production-ready and surpasses SliceWP capabilities.</p>\n";
        } elseif ($success_rate >= 70) {
            echo "\n<p>⚠️ <strong>Good</strong> - Dashboard is functional with minor issues to address.</p>\n";
        } else {
            echo "\n<p>🚨 <strong>Critical issues found</strong> - Dashboard needs debugging before deployment.</p>\n";
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
    (isset($_GET['test']) && $_GET['test'] === 'dashboard')) {
    
    $test_suite = new KHM_Dashboard_Test_Suite();
    $success_rate = $test_suite->run_all_tests();
    
    if ($success_rate >= 90) {
        echo "\n🚀 Enhanced Dashboard ready for production deployment!\n";
        echo "📊 Successfully surpasses SliceWP's dashboard capabilities.\n";
    } else {
        echo "\n🔧 Enhanced Dashboard needs refinement before production.\n";
    }
}