#!/usr/bin/env php
<?php
/**
 * TouchPoint Marketing Suite - Standalone Test Runner
 * Tests what we can without WordPress environment
 */

class TouchPoint_Standalone_Test_Runner {
    
    private $results;
    private $base_path;
    
    public function __construct() {
        $this->base_path = dirname(__DIR__, 4); // Go up from tests to 1927MSuite root
        $this->results = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'categories' => []
        ];
        
        echo "🧪 TouchPoint Marketing Suite - Standalone Test Runner\n";
        echo "===================================================\n";
        echo "Testing without WordPress environment\n";
        echo "Base path: " . $this->base_path . "\n";
        echo "PHP: " . PHP_VERSION . "\n\n";
    }
    
    public function run_all_tests() {
        try {
            // File structure tests
            $this->test_file_structure();
            $this->test_frontend_assets();
            $this->test_database_migrations();
            $this->test_php_syntax();
            $this->test_javascript_syntax();
            $this->test_css_structure();
            $this->test_configuration_files();
            $this->test_file_permissions();
            $this->test_code_quality();
            
        } catch (Exception $e) {
            $this->test_fail("Critical test failure: " . $e->getMessage(), 'system');
        }
        
        $this->print_final_report();
    }
    
    private function test_file_structure() {
        $this->start_category("File Structure");
        
        $required_directories = [
            'wp-content/plugins/khm-plugin',
            'wp-content/plugins/social-strip',
            'wp-content/plugins/khm-plugin/src/Services',
            'wp-content/plugins/khm-plugin/src/Utils',
            'wp-content/plugins/khm-plugin/db/migrations',
            'wp-content/plugins/khm-plugin/tests',
            'wp-content/plugins/social-strip/assets/css',
            'wp-content/plugins/social-strip/assets/js'
        ];
        
        foreach ($required_directories as $dir) {
            $full_path = $this->base_path . '/' . $dir;
            if (is_dir($full_path)) {
                $this->test_pass("Directory exists: {$dir}");
            } else {
                $this->test_fail("Missing directory: {$dir}");
            }
        }
    }
    
    private function test_frontend_assets() {
        $this->start_category("Frontend Assets");
        
        $asset_files = [
            'wp-content/plugins/social-strip/assets/css/ecommerce.css' => ['min_size' => 10000, 'type' => 'CSS'],
            'wp-content/plugins/social-strip/assets/css/modal.css' => ['min_size' => 8000, 'type' => 'CSS'],
            'wp-content/plugins/social-strip/assets/js/ecommerce.js' => ['min_size' => 15000, 'type' => 'JavaScript'],
            'wp-content/plugins/social-strip/assets/js/integration-test.js' => ['min_size' => 1000, 'type' => 'JavaScript']
        ];
        
        foreach ($asset_files as $asset => $requirements) {
            $file_path = $this->base_path . '/' . $asset;
            if (file_exists($file_path)) {
                $size = filesize($file_path);
                $this->test_pass("Asset exists: " . basename($asset) . " (" . $this->format_file_size($size) . ")");
                
                if ($size >= $requirements['min_size']) {
                    $this->test_pass("  → Size check passed for " . basename($asset));
                } else {
                    $this->test_warning("  → File may be incomplete: " . basename($asset) . " (expected > " . $this->format_file_size($requirements['min_size']) . ")");
                }
            } else {
                $this->test_fail("Missing asset: " . basename($asset));
            }
        }
    }
    
    private function test_database_migrations() {
        $this->start_category("Database Migrations");
        
        $migrations_dir = $this->base_path . '/wp-content/plugins/khm-plugin/db/migrations';
        if (is_dir($migrations_dir)) {
            $migration_files = glob($migrations_dir . '/*.sql');
            
            if (count($migration_files) >= 2) {
                $this->test_pass("Migration files found: " . count($migration_files) . " files");
                
                foreach ($migration_files as $migration) {
                    $content = file_get_contents($migration);
                    $filename = basename($migration);
                    
                    // Check for SQL keywords
                    if (preg_match('/CREATE TABLE|ALTER TABLE|INSERT INTO/i', $content)) {
                        $this->test_pass("  → Valid SQL content in: {$filename}");
                    } else {
                        $this->test_warning("  → Questionable SQL content in: {$filename}");
                    }
                    
                    // Check file size
                    if (filesize($migration) > 1000) {
                        $this->test_pass("  → Substantial migration: {$filename}");
                    } else {
                        $this->test_warning("  → Small migration file: {$filename}");
                    }
                }
            } else {
                $this->test_warning("Few migration files found: " . count($migration_files));
            }
        } else {
            $this->test_fail("Migrations directory not found");
        }
        
        // Test migration manager
        $migration_manager = $this->base_path . '/wp-content/plugins/khm-plugin/src/Utils/MigrationManager.php';
        if (file_exists($migration_manager)) {
            $this->test_pass("Migration Manager exists");
        } else {
            $this->test_fail("Migration Manager missing");
        }
    }
    
    private function test_php_syntax() {
        $this->start_category("PHP Syntax");
        
        $php_files = [
            'wp-content/plugins/khm-plugin/src/Utils/MigrationManager.php',
            'wp-content/plugins/khm-plugin/src/Services/CreditService.php',
            'wp-content/plugins/khm-plugin/src/Services/ECommerceService.php',
            'wp-content/plugins/khm-plugin/src/Services/LibraryService.php',
            'wp-content/plugins/khm-plugin/tests/backup-manager.php',
            'wp-content/plugins/khm-plugin/tests/test-database-validation.php'
        ];
        
        foreach ($php_files as $file) {
            $full_path = $this->base_path . '/' . $file;
            if (file_exists($full_path)) {
                $output = [];
                $return_code = 0;
                exec("php -l " . escapeshellarg($full_path) . " 2>&1", $output, $return_code);
                
                if ($return_code === 0) {
                    $this->test_pass("PHP syntax valid: " . basename($file));
                } else {
                    $this->test_fail("PHP syntax error in: " . basename($file) . " - " . implode('; ', $output));
                }
            } else {
                $this->test_warning("PHP file not found: " . basename($file));
            }
        }
    }
    
    private function test_javascript_syntax() {
        $this->start_category("JavaScript Syntax");
        
        $js_files = [
            'wp-content/plugins/social-strip/assets/js/ecommerce.js',
            'wp-content/plugins/social-strip/assets/js/integration-test.js'
        ];
        
        foreach ($js_files as $file) {
            $full_path = $this->base_path . '/' . $file;
            if (file_exists($full_path)) {
                // Check if node is available for syntax checking
                $output = [];
                $return_code = 0;
                exec("which node 2>&1", $output, $return_code);
                
                if ($return_code === 0) {
                    $output = [];
                    exec("node -c " . escapeshellarg($full_path) . " 2>&1", $output, $return_code);
                    
                    if ($return_code === 0) {
                        $this->test_pass("JavaScript syntax valid: " . basename($file));
                    } else {
                        $this->test_fail("JavaScript syntax error in: " . basename($file) . " - " . implode('; ', $output));
                    }
                } else {
                    // Manual basic syntax check
                    $content = file_get_contents($full_path);
                    $bracket_balance = substr_count($content, '{') - substr_count($content, '}');
                    $paren_balance = substr_count($content, '(') - substr_count($content, ')');
                    
                    if ($bracket_balance === 0 && $paren_balance === 0) {
                        $this->test_pass("JavaScript basic syntax check passed: " . basename($file));
                    } else {
                        $this->test_warning("JavaScript may have syntax issues: " . basename($file) . " (brackets: {$bracket_balance}, parens: {$paren_balance})");
                    }
                }
            } else {
                $this->test_fail("JavaScript file not found: " . basename($file));
            }
        }
    }
    
    private function test_css_structure() {
        $this->start_category("CSS Structure");
        
        $css_files = [
            'wp-content/plugins/social-strip/assets/css/ecommerce.css',
            'wp-content/plugins/social-strip/assets/css/modal.css'
        ];
        
        foreach ($css_files as $file) {
            $full_path = $this->base_path . '/' . $file;
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
                
                // Check for key CSS patterns
                $rule_count = substr_count($content, '{');
                $property_count = substr_count($content, ':');
                $media_queries = substr_count($content, '@media');
                
                if ($rule_count >= 10) {
                    $this->test_pass("CSS has good structure: " . basename($file) . " ({$rule_count} rules, {$media_queries} media queries)");
                } else {
                    $this->test_warning("CSS may be incomplete: " . basename($file) . " ({$rule_count} rules)");
                }
                
                // Check for specific classes
                $filename = basename($file);
                if ($filename === 'ecommerce.css') {
                    $ecommerce_classes = ['.khm-cart', '.khm-checkout', '.khm-product'];
                    $found = 0;
                    foreach ($ecommerce_classes as $class) {
                        if (strpos($content, $class) !== false) $found++;
                    }
                    
                    if ($found >= 2) {
                        $this->test_pass("  → eCommerce CSS classes present ({$found}/3)");
                    } else {
                        $this->test_warning("  → Missing eCommerce CSS classes ({$found}/3)");
                    }
                }
                
                if ($filename === 'modal.css') {
                    $modal_classes = ['.khm-modal', '.khm-modal-backdrop', '.khm-modal-content'];
                    $found = 0;
                    foreach ($modal_classes as $class) {
                        if (strpos($content, $class) !== false) $found++;
                    }
                    
                    if ($found >= 2) {
                        $this->test_pass("  → Modal CSS classes present ({$found}/3)");
                    } else {
                        $this->test_warning("  → Missing modal CSS classes ({$found}/3)");
                    }
                }
            } else {
                $this->test_fail("CSS file not found: " . basename($file));
            }
        }
    }
    
    private function test_configuration_files() {
        $this->start_category("Configuration Files");
        
        $config_files = [
            'wp-content/plugins/khm-plugin/tests/comprehensive-test-suite.php',
            'wp-content/plugins/khm-plugin/tests/frontend-test-suite.html',
            'wp-content/plugins/khm-plugin/tests/backup-manager.php'
        ];
        
        foreach ($config_files as $file) {
            $full_path = $this->base_path . '/' . $file;
            if (file_exists($full_path)) {
                $this->test_pass("Configuration file exists: " . basename($file));
            } else {
                $this->test_warning("Configuration file missing: " . basename($file));
            }
        }
    }
    
    private function test_file_permissions() {
        $this->start_category("File Permissions");
        
        $files_to_check = [
            'wp-content/plugins/khm-plugin/src/Services/CreditService.php',
            'wp-content/plugins/social-strip/assets/css/ecommerce.css',
            'wp-content/plugins/social-strip/assets/js/ecommerce.js'
        ];
        
        foreach ($files_to_check as $file) {
            $full_path = $this->base_path . '/' . $file;
            if (file_exists($full_path)) {
                if (is_readable($full_path)) {
                    $this->test_pass("File readable: " . basename($file));
                } else {
                    $this->test_fail("File not readable: " . basename($file));
                }
            }
        }
    }
    
    private function test_code_quality() {
        $this->start_category("Code Quality");
        
        // Test service files for proper class structure
        $service_files = glob($this->base_path . '/wp-content/plugins/khm-plugin/src/Services/*.php');
        
        foreach ($service_files as $service_file) {
            if (file_exists($service_file)) {
                $content = file_get_contents($service_file);
                $filename = basename($service_file);
                
                // Check for namespace
                if (strpos($content, 'namespace KHM\\Services') !== false) {
                    $this->test_pass("Namespace correct in: {$filename}");
                } else {
                    $this->test_warning("Namespace missing/incorrect in: {$filename}");
                }
                
                // Check for class definition
                if (preg_match('/class\s+\w+/', $content)) {
                    $this->test_pass("Class definition found in: {$filename}");
                } else {
                    $this->test_fail("No class definition in: {$filename}");
                }
                
                // Check for proper DocBlocks
                if (strpos($content, '/**') !== false) {
                    $this->test_pass("Documentation blocks present in: {$filename}");
                } else {
                    $this->test_warning("Missing documentation in: {$filename}");
                }
            }
        }
        
        // Test frontend JavaScript quality
        $ecommerce_js = $this->base_path . '/wp-content/plugins/social-strip/assets/js/ecommerce.js';
        if (file_exists($ecommerce_js)) {
            $content = file_get_contents($ecommerce_js);
            
            // Check for modern JavaScript features
            if (strpos($content, 'const ') !== false || strpos($content, 'let ') !== false) {
                $this->test_pass("Modern JavaScript syntax used in ecommerce.js");
            } else {
                $this->test_warning("Consider using modern JavaScript syntax in ecommerce.js");
            }
            
            // Check for error handling
            if (strpos($content, 'try {') !== false || strpos($content, 'catch') !== false) {
                $this->test_pass("Error handling present in ecommerce.js");
            } else {
                $this->test_warning("Consider adding error handling to ecommerce.js");
            }
        }
    }
    
    private function start_category($category_name) {
        echo "\n📋 {$category_name}\n" . str_repeat("-", strlen($category_name) + 4) . "\n";
        $this->results['categories'][$category_name] = ['passed' => 0, 'failed' => 0, 'warnings' => 0];
    }
    
    private function test_pass($message, $category = null) {
        echo "✅ {$message}\n";
        $this->results['total_tests']++;
        $this->results['passed']++;
        
        $current_category = $category ?: array_key_last($this->results['categories']);
        if ($current_category && isset($this->results['categories'][$current_category])) {
            $this->results['categories'][$current_category]['passed']++;
        }
    }
    
    private function test_fail($message, $category = null) {
        echo "❌ {$message}\n";
        $this->results['total_tests']++;
        $this->results['failed']++;
        
        $current_category = $category ?: array_key_last($this->results['categories']);
        if ($current_category && isset($this->results['categories'][$current_category])) {
            $this->results['categories'][$current_category]['failed']++;
        }
    }
    
    private function test_warning($message, $category = null) {
        echo "⚠️  {$message}\n";
        $this->results['total_tests']++;
        $this->results['warnings']++;
        
        $current_category = $category ?: array_key_last($this->results['categories']);
        if ($current_category && isset($this->results['categories'][$current_category])) {
            $this->results['categories'][$current_category]['warnings']++;
        }
    }
    
    private function print_final_report() {
        echo "\n📊 TouchPoint Test Report\n";
        echo "========================\n";
        
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
            echo "🎉 Excellent! TouchPoint suite looks ready for WordPress testing!\n";
        } elseif ($health_score >= 75) {
            echo "✨ Good! Minor issues to address.\n";
        } elseif ($health_score >= 50) {
            echo "⚡ Fair. Several issues need attention.\n";
        } else {
            echo "🔧 Poor. Major issues require immediate attention.\n";
        }
        
        echo "\n💡 Next Steps:\n";
        echo "  • Run frontend test suite in browser: open tests/frontend-test-suite.html\n";
        echo "  • Test in WordPress environment for full integration\n";
        echo "  • Address any failed tests before production\n";
        echo "  • Consider performance testing under load\n";
        
        // Return exit code based on results
        if ($this->results['failed'] > 0) {
            exit(1);
        } elseif ($this->results['warnings'] > 3) {
            exit(2);
        } else {
            exit(0);
        }
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
$test_runner = new TouchPoint_Standalone_Test_Runner();
$test_runner->run_all_tests();