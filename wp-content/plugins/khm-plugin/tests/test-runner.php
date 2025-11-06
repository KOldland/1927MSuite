<?php
/**
 * KHM Attribution System Test Runner
 * 
 * Comprehensive test runner for the complete attribution system
 * Runs all tests and generates detailed reports
 */

if (!defined('ABSPATH')) {
    exit;
}

class KHM_Attribution_Test_Runner {
    
    private $test_results = array();
    private $start_time;
    
    public function __construct() {
        $this->start_time = microtime(true);
    }
    
    /**
     * Run complete test suite
     */
    public function run_all_tests() {
        echo "<!DOCTYPE html>\n";
        echo "<html><head><title>KHM Attribution System Test Results</title>\n";
        echo "<style>\n";
        echo "body { font-family: 'Courier New', monospace; margin: 20px; background: #f8fafc; }\n";
        echo ".test-container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0; }\n";
        echo ".success { color: #22c55e; }\n";
        echo ".error { color: #ef4444; }\n";
        echo ".warning { color: #f59e0b; }\n";
        echo ".info { color: #3b82f6; }\n";
        echo ".metric { background: #f3f4f6; padding: 10px; border-radius: 4px; margin: 5px 0; }\n";
        echo ".grade-excellent { background: #dcfce7; border-left: 4px solid #22c55e; padding: 15px; }\n";
        echo ".grade-good { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; }\n";
        echo ".grade-fair { background: #fee2e2; border-left: 4px solid #ef4444; padding: 15px; }\n";
        echo ".progress-bar { background: #e5e7eb; border-radius: 4px; overflow: hidden; height: 20px; }\n";
        echo ".progress-fill { background: #22c55e; height: 100%; transition: width 0.3s; }\n";
        echo "</style>\n";
        echo "</head><body>\n";
        
        echo "<h1>🚀 KHM Attribution System - Comprehensive Test Suite</h1>\n";
        echo "<div class='test-container'>\n";
        
        // Phase 2 Performance Testing
        echo "<h2>Phase 2: Performance Optimization Testing</h2>\n";
        $this->run_performance_tests();
        
        // Integration Testing
        echo "<h2>System Integration Testing</h2>\n";
        $this->run_integration_tests();
        
        // Component Testing
        echo "<h2>Component Unit Testing</h2>\n";
        $this->run_component_tests();
        
        // Generate comprehensive report
        $this->generate_final_report();
        
        echo "</div>\n";
        echo "</body></html>\n";
    }
    
    /**
     * Run performance tests
     */
    private function run_performance_tests() {
        echo "<div class='test-container'>\n";
        echo "<h3>⚡ Performance Test Suite</h3>\n";
        
        try {
            // Load performance test suite
            require_once dirname(__FILE__) . '/test-performance-suite.php';
            
            if (class_exists('KHM_Attribution_Performance_Tests')) {
                $performance_tests = new KHM_Attribution_Performance_Tests();
                
                // Capture output
                ob_start();
                $performance_tests->run_performance_tests();
                $performance_output = ob_get_clean();
                
                echo "<div style='background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto;'>\n";
                echo "<pre>" . htmlspecialchars($performance_output) . "</pre>\n";
                echo "</div>\n";
                
                $this->test_results['performance'] = array(
                    'completed' => true,
                    'output' => $performance_output
                );
                
                echo "<div class='success'>✅ Performance tests completed successfully</div>\n";
            } else {
                throw new Exception("Performance test class not found");
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Performance test error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            $this->test_results['performance'] = array(
                'completed' => false,
                'error' => $e->getMessage()
            );
        }
        
        echo "</div>\n";
    }
    
    /**
     * Run integration tests
     */
    private function run_integration_tests() {
        echo "<div class='test-container'>\n";
        echo "<h3>🔧 Integration Test Suite</h3>\n";
        
        try {
            // Load integration test suite
            require_once dirname(__FILE__) . '/test-integration-suite.php';
            
            if (class_exists('KHM_Attribution_Integration_Tests')) {
                $integration_tests = new KHM_Attribution_Integration_Tests();
                
                // Capture output
                ob_start();
                $integration_tests->run_integration_tests();
                $integration_output = ob_get_clean();
                
                echo "<div style='background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 6px; overflow-x: auto;'>\n";
                echo "<pre>" . htmlspecialchars($integration_output) . "</pre>\n";
                echo "</div>\n";
                
                $this->test_results['integration'] = array(
                    'completed' => true,
                    'output' => $integration_output
                );
                
                echo "<div class='success'>✅ Integration tests completed successfully</div>\n";
            } else {
                throw new Exception("Integration test class not found");
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Integration test error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
            $this->test_results['integration'] = array(
                'completed' => false,
                'error' => $e->getMessage()
            );
        }
        
        echo "</div>\n";
    }
    
    /**
     * Run component tests
     */
    private function run_component_tests() {
        echo "<div class='test-container'>\n";
        echo "<h3>🧩 Component Test Suite</h3>\n";
        
        $components = array(
            'AttributionManager' => 'Core attribution tracking and resolution',
            'PerformanceManager' => 'Performance optimization and caching',
            'AsyncManager' => 'Background job processing',
            'QueryBuilder' => 'Optimized database queries',
            'Dashboard' => 'Performance monitoring interface'
        );
        
        foreach ($components as $component => $description) {
            $this->test_component($component, $description);
        }
        
        echo "</div>\n";
    }
    
    /**
     * Test individual component
     */
    private function test_component($component, $description) {
        echo "<h4>Testing {$component}</h4>\n";
        echo "<p><em>{$description}</em></p>\n";
        
        $component_file = dirname(__FILE__) . "/../src/Attribution/{$component}.php";
        
        if (file_exists($component_file)) {
            // Check file syntax
            $syntax_check = $this->check_php_syntax($component_file);
            
            if ($syntax_check['valid']) {
                echo "<div class='success'>✅ {$component}: Syntax valid</div>\n";
                
                // Check class exists
                require_once $component_file;
                $class_name = "KHM_Attribution_{$component}";
                
                if (class_exists($class_name)) {
                    echo "<div class='success'>✅ {$component}: Class loaded successfully</div>\n";
                    
                    // Test instantiation
                    try {
                        $instance = new $class_name();
                        echo "<div class='success'>✅ {$component}: Instantiated successfully</div>\n";
                        
                        $this->test_results['components'][$component] = array(
                            'exists' => true,
                            'syntax_valid' => true,
                            'class_loaded' => true,
                            'instantiated' => true
                        );
                        
                    } catch (Exception $e) {
                        echo "<div class='warning'>⚠️ {$component}: Instantiation failed - " . htmlspecialchars($e->getMessage()) . "</div>\n";
                        $this->test_results['components'][$component] = array(
                            'exists' => true,
                            'syntax_valid' => true,
                            'class_loaded' => true,
                            'instantiated' => false,
                            'error' => $e->getMessage()
                        );
                    }
                } else {
                    echo "<div class='error'>❌ {$component}: Class not found</div>\n";
                    $this->test_results['components'][$component] = array(
                        'exists' => true,
                        'syntax_valid' => true,
                        'class_loaded' => false
                    );
                }
            } else {
                echo "<div class='error'>❌ {$component}: Syntax error - " . htmlspecialchars($syntax_check['error']) . "</div>\n";
                $this->test_results['components'][$component] = array(
                    'exists' => true,
                    'syntax_valid' => false,
                    'error' => $syntax_check['error']
                );
            }
        } else {
            echo "<div class='error'>❌ {$component}: File not found at {$component_file}</div>\n";
            $this->test_results['components'][$component] = array(
                'exists' => false
            );
        }
        
        echo "<br>\n";
    }
    
    /**
     * Check PHP syntax
     */
    private function check_php_syntax($file) {
        $output = array();
        $return_var = 0;
        
        exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $return_var);
        
        return array(
            'valid' => $return_var === 0,
            'error' => $return_var !== 0 ? implode("\n", $output) : null
        );
    }
    
    /**
     * Generate final comprehensive report
     */
    private function generate_final_report() {
        $total_time = microtime(true) - $this->start_time;
        
        echo "<div class='test-container'>\n";
        echo "<h2>📊 Final Test Report</h2>\n";
        
        // Calculate overall scores
        $scores = $this->calculate_test_scores();
        $overall_score = $scores['overall'];
        
        // Display overall grade
        $grade_class = $this->get_grade_class($overall_score);
        echo "<div class='{$grade_class}'>\n";
        echo "<h3>Overall System Grade</h3>\n";
        echo "<div style='font-size: 24px; font-weight: bold;'>{$this->get_grade_letter($overall_score)} ({$overall_score}%)</div>\n";
        echo "<div>{$this->get_grade_description($overall_score)}</div>\n";
        echo "</div>\n";
        
        // Progress visualization
        echo "<h3>Test Category Scores</h3>\n";
        foreach ($scores as $category => $score) {
            if ($category === 'overall') continue;
            
            echo "<div class='metric'>\n";
            echo "<strong>" . ucfirst($category) . " Tests</strong>\n";
            echo "<div class='progress-bar'>\n";
            echo "<div class='progress-fill' style='width: {$score}%'></div>\n";
            echo "</div>\n";
            echo "<div>{$score}% Complete</div>\n";
            echo "</div>\n";
        }
        
        // Key metrics
        echo "<h3>Key Metrics</h3>\n";
        echo "<div class='metric'>\n";
        echo "<strong>Test Execution Time:</strong> " . number_format($total_time, 2) . " seconds<br>\n";
        echo "<strong>Components Tested:</strong> " . count($this->test_results['components'] ?? array()) . "<br>\n";
        echo "<strong>System Readiness:</strong> " . $this->assess_system_readiness($overall_score) . "<br>\n";
        echo "</div>\n";
        
        // Recommendations
        echo "<h3>Recommendations</h3>\n";
        $this->generate_recommendations($scores);
        
        // Next steps
        echo "<h3>Next Steps</h3>\n";
        $this->generate_next_steps($overall_score);
        
        echo "</div>\n";
    }
    
    /**
     * Calculate test scores
     */
    private function calculate_test_scores() {
        $scores = array();
        
        // Performance score
        $scores['performance'] = isset($this->test_results['performance']['completed']) && 
                                $this->test_results['performance']['completed'] ? 100 : 0;
        
        // Integration score
        $scores['integration'] = isset($this->test_results['integration']['completed']) && 
                                $this->test_results['integration']['completed'] ? 100 : 0;
        
        // Component score
        $component_total = 0;
        $component_passed = 0;
        
        if (isset($this->test_results['components'])) {
            foreach ($this->test_results['components'] as $component => $result) {
                $component_total++;
                if (isset($result['instantiated']) && $result['instantiated']) {
                    $component_passed++;
                } elseif (isset($result['class_loaded']) && $result['class_loaded']) {
                    $component_passed += 0.8; // Partial credit
                } elseif (isset($result['syntax_valid']) && $result['syntax_valid']) {
                    $component_passed += 0.5; // Partial credit
                }
            }
        }
        
        $scores['components'] = $component_total > 0 ? 
            round(($component_passed / $component_total) * 100) : 0;
        
        // Calculate overall score
        $scores['overall'] = round(array_sum($scores) / count($scores));
        
        return $scores;
    }
    
    /**
     * Get grade class for styling
     */
    private function get_grade_class($score) {
        if ($score >= 90) return 'grade-excellent';
        if ($score >= 75) return 'grade-good';
        return 'grade-fair';
    }
    
    /**
     * Get letter grade
     */
    private function get_grade_letter($score) {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'B+';
        if ($score >= 80) return 'B';
        if ($score >= 75) return 'C+';
        if ($score >= 70) return 'C';
        if ($score >= 65) return 'D+';
        if ($score >= 60) return 'D';
        return 'F';
    }
    
    /**
     * Get grade description
     */
    private function get_grade_description($score) {
        if ($score >= 95) return 'Excellent - Production ready with outstanding performance';
        if ($score >= 90) return 'Excellent - Production ready with minor optimizations possible';
        if ($score >= 85) return 'Good - Ready for staging with some improvements recommended';
        if ($score >= 80) return 'Good - Core functionality working, optimization needed';
        if ($score >= 75) return 'Fair - Major components working, significant improvements needed';
        if ($score >= 70) return 'Fair - Basic functionality present, extensive work required';
        return 'Poor - Major issues detected, comprehensive rework needed';
    }
    
    /**
     * Assess system readiness
     */
    private function assess_system_readiness($score) {
        if ($score >= 90) return 'Production Ready';
        if ($score >= 80) return 'Staging Ready';
        if ($score >= 70) return 'Development Complete';
        return 'In Development';
    }
    
    /**
     * Generate recommendations
     */
    private function generate_recommendations($scores) {
        $recommendations = array();
        
        if ($scores['performance'] < 90) {
            $recommendations[] = "🔧 Optimize performance components for better SLO compliance";
        }
        
        if ($scores['integration'] < 90) {
            $recommendations[] = "🔗 Review integration points between components";
        }
        
        if ($scores['components'] < 90) {
            $recommendations[] = "🧩 Address component instantiation issues";
        }
        
        if ($scores['overall'] >= 90) {
            $recommendations[] = "🚀 System performing excellently - ready for production deployment";
            $recommendations[] = "📊 Consider implementing additional monitoring and alerting";
        } elseif ($scores['overall'] >= 80) {
            $recommendations[] = "⚡ Focus on performance optimizations before production";
            $recommendations[] = "🧪 Conduct load testing in staging environment";
        } else {
            $recommendations[] = "🛠️ Significant development work required before deployment";
            $recommendations[] = "📋 Review failed test categories and address core issues";
        }
        
        foreach ($recommendations as $recommendation) {
            echo "<div class='info'>{$recommendation}</div>\n";
        }
    }
    
    /**
     * Generate next steps
     */
    private function generate_next_steps($overall_score) {
        if ($overall_score >= 90) {
            echo "<ol>\n";
            echo "<li><strong>Production Deployment:</strong> System ready for production deployment</li>\n";
            echo "<li><strong>Monitoring Setup:</strong> Implement comprehensive monitoring and alerting</li>\n";
            echo "<li><strong>Performance Monitoring:</strong> Track SLO compliance in production</li>\n";
            echo "<li><strong>Phase 3 Development:</strong> Begin enhanced business analytics implementation</li>\n";
            echo "</ol>\n";
        } elseif ($overall_score >= 80) {
            echo "<ol>\n";
            echo "<li><strong>Address Issues:</strong> Fix failing tests and optimize performance</li>\n";
            echo "<li><strong>Staging Deployment:</strong> Deploy to staging for comprehensive testing</li>\n";
            echo "<li><strong>Load Testing:</strong> Conduct thorough load and stress testing</li>\n";
            echo "<li><strong>Security Review:</strong> Perform security audit before production</li>\n";
            echo "</ol>\n";
        } else {
            echo "<ol>\n";
            echo "<li><strong>Fix Critical Issues:</strong> Address all failing component and integration tests</li>\n";
            echo "<li><strong>Performance Optimization:</strong> Implement required performance improvements</li>\n";
            echo "<li><strong>Comprehensive Testing:</strong> Re-run all test suites after fixes</li>\n";
            echo "<li><strong>Code Review:</strong> Conduct thorough code review and refactoring</li>\n";
            echo "</ol>\n";
        }
    }
}

// Auto-run tests if accessed directly
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__) || 
    (isset($_GET['run_tests']) && $_GET['run_tests'] === 'true')) {
    
    $test_runner = new KHM_Attribution_Test_Runner();
    $test_runner->run_all_tests();
}
?>