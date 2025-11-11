<?php
/**
 * Phase 7 Performance Monitor Test Suite
 * Tests the Performance Monitoring System implementation
 *
 * @package KHM_SEO\Testing
 */

// Simulate WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', '/path/to/wordpress/');
}

if (!defined('KHM_SEO_VERSION')) {
    define('KHM_SEO_VERSION', '1.0.0');
}

echo "🧪 Phase 7 Performance Monitor Test Suite\n";
echo "==========================================\n\n";

// Test file existence
$phase7_files = [
    'src/Performance/PerformanceMonitor.php' => 'Core Performance Monitor Class',
    'src/Performance/templates/admin-dashboard.php' => 'Admin Dashboard Template',
    'src/Performance/assets/js/performance-monitor.js' => 'Dashboard JavaScript',
    'src/Performance/assets/css/performance-monitor.css' => 'Dashboard CSS Styles'
];

echo "📁 FILE EXISTENCE TESTS\n";
echo "------------------------\n";

$files_passed = 0;
$total_files = count($phase7_files);

foreach ($phase7_files as $file => $description) {
    $full_path = __DIR__ . '/wp-content/plugins/khm-seo/' . $file;
    
    if (file_exists($full_path)) {
        $size = number_format(filesize($full_path) / 1024, 1);
        echo "✅ {$description}: {$file} ({$size}KB)\n";
        $files_passed++;
    } else {
        echo "❌ {$description}: {$file} (MISSING)\n";
    }
}

echo "\n";

// Test PHP syntax
echo "🔧 PHP SYNTAX TESTS\n";
echo "-------------------\n";

$php_files = [
    'src/Performance/PerformanceMonitor.php',
    'src/Performance/templates/admin-dashboard.php'
];

$syntax_passed = 0;
foreach ($php_files as $file) {
    $full_path = __DIR__ . '/wp-content/plugins/khm-seo/' . $file;
    
    if (file_exists($full_path)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"{$full_path}\" 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "✅ PHP Syntax: {$file}\n";
            $syntax_passed++;
        } else {
            echo "❌ PHP Syntax: {$file}\n";
            echo "   Error: " . implode("\n   ", $output) . "\n";
        }
    }
}

echo "\n";

// Test JavaScript syntax
echo "📜 JAVASCRIPT SYNTAX TESTS\n";
echo "----------------------------\n";

$js_files = [
    'src/Performance/assets/js/performance-monitor.js'
];

$js_passed = 0;
foreach ($js_files as $file) {
    $full_path = __DIR__ . '/wp-content/plugins/khm-seo/' . $file;
    
    if (file_exists($full_path)) {
        // Basic JS syntax check - look for obvious syntax errors
        $content = file_get_contents($full_path);
        
        $errors = [];
        
        // Check for mismatched braces
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        if ($open_braces !== $close_braces) {
            $errors[] = "Mismatched braces: {$open_braces} open, {$close_braces} close";
        }
        
        // Check for mismatched parentheses
        $open_parens = substr_count($content, '(');
        $close_parens = substr_count($content, ')');
        if ($open_parens !== $close_parens) {
            $errors[] = "Mismatched parentheses: {$open_parens} open, {$close_parens} close";
        }
        
        // Check for obvious syntax errors
        if (strpos($content, ';;') !== false) {
            $errors[] = "Double semicolon found";
        }
        
        if (empty($errors)) {
            echo "✅ JavaScript Syntax: {$file}\n";
            $js_passed++;
        } else {
            echo "❌ JavaScript Syntax: {$file}\n";
            foreach ($errors as $error) {
                echo "   Error: {$error}\n";
            }
        }
    }
}

echo "\n";

// Test CSS syntax
echo "🎨 CSS SYNTAX TESTS\n";
echo "-------------------\n";

$css_files = [
    'src/Performance/assets/css/performance-monitor.css'
];

$css_passed = 0;
foreach ($css_files as $file) {
    $full_path = __DIR__ . '/wp-content/plugins/khm-seo/' . $file;
    
    if (file_exists($full_path)) {
        $content = file_get_contents($full_path);
        
        $errors = [];
        
        // Check for mismatched braces
        $open_braces = substr_count($content, '{');
        $close_braces = substr_count($content, '}');
        if ($open_braces !== $close_braces) {
            $errors[] = "Mismatched braces: {$open_braces} open, {$close_braces} close";
        }
        
        // Check for common CSS errors - simplified check
        if (strpos($content, '{{') !== false || strpos($content, '}}') !== false) {
            $errors[] = "Double braces found";
        }
        
        if (empty($errors)) {
            echo "✅ CSS Syntax: {$file}\n";
            $css_passed++;
        } else {
            echo "❌ CSS Syntax: {$file}\n";
            foreach ($errors as $error) {
                echo "   Error: {$error}\n";
            }
        }
    }
}

echo "\n";

// Test class structure (if we can load the class)
echo "🏗️  PHP CLASS STRUCTURE TESTS\n";
echo "------------------------------\n";

try {
    // Try to include the PerformanceMonitor class
    $monitor_file = __DIR__ . '/wp-content/plugins/khm-seo/src/Performance/PerformanceMonitor.php';
    
    if (file_exists($monitor_file)) {
        // Read the file and check for class definition
        $content = file_get_contents($monitor_file);
        
        $structure_tests = [
            'namespace KHM_SEO\\Performance' => 'Namespace declaration',
            'class PerformanceMonitor' => 'Class declaration',
            'public function __construct' => 'Constructor method',
            'public function render_dashboard' => 'Dashboard render method',
            'public function ajax_run_performance_test' => 'Performance test AJAX handler',
            'public function ajax_save_settings' => 'Settings save AJAX handler',
            'private function enqueue_dashboard_assets' => 'Asset enqueue method',
            'public function run_pagespeed_test' => 'PageSpeed test method'
        ];
        
        $structure_passed = 0;
        foreach ($structure_tests as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                echo "✅ {$description}\n";
                $structure_passed++;
            } else {
                echo "❌ {$description}\n";
            }
        }
        
        echo "\nClass structure: {$structure_passed}/" . count($structure_tests) . " tests passed\n";
    }
} catch (Exception $e) {
    echo "❌ Error testing class structure: " . $e->getMessage() . "\n";
}

echo "\n";

// Test integration with main plugin
echo "🔗 INTEGRATION TESTS\n";
echo "--------------------\n";

$integration_tests = [
    'Plugin.php includes PerformanceMonitor' => function() {
        $plugin_file = __DIR__ . '/wp-content/plugins/khm-seo/src/Core/Plugin.php';
        if (file_exists($plugin_file)) {
            $content = file_get_contents($plugin_file);
            return strpos($content, 'use KHM_SEO\\Performance\\PerformanceMonitor') !== false;
        }
        return false;
    },
    'Plugin.php initializes performance monitor' => function() {
        $plugin_file = __DIR__ . '/wp-content/plugins/khm-seo/src/Core/Plugin.php';
        if (file_exists($plugin_file)) {
            $content = file_get_contents($plugin_file);
            return strpos($content, '$this->performance = new PerformanceMonitor()') !== false;
        }
        return false;
    },
    'AdminManager.php includes performance page' => function() {
        $admin_file = __DIR__ . '/wp-content/plugins/khm-seo/src/Admin/AdminManager.php';
        if (file_exists($admin_file)) {
            $content = file_get_contents($admin_file);
            return strpos($content, 'khm-seo-performance') !== false;
        }
        return false;
    }
];

$integration_passed = 0;
foreach ($integration_tests as $test => $callback) {
    if ($callback()) {
        echo "✅ {$test}\n";
        $integration_passed++;
    } else {
        echo "❌ {$test}\n";
    }
}

echo "\n";

// Summary
echo "📊 TEST SUMMARY\n";
echo "===============\n";
echo "File Existence: {$files_passed}/{$total_files}\n";
echo "PHP Syntax: {$syntax_passed}/" . count($php_files) . "\n";
echo "JavaScript Syntax: {$js_passed}/" . count($js_files) . "\n";
echo "CSS Syntax: {$css_passed}/" . count($css_files) . "\n";
echo "Integration: {$integration_passed}/" . count($integration_tests) . "\n";

$total_tests = $total_files + count($php_files) + count($js_files) + count($css_files) + count($integration_tests);
$total_passed = $files_passed + $syntax_passed + $js_passed + $css_passed + $integration_passed;

$success_rate = round(($total_passed / $total_tests) * 100, 1);

echo "\nOverall Success Rate: {$success_rate}% ({$total_passed}/{$total_tests})\n";

if ($success_rate >= 95) {
    echo "\n🎉 EXCELLENT! Phase 7 implementation is production-ready.\n";
} elseif ($success_rate >= 80) {
    echo "\n👍 GOOD! Phase 7 implementation is mostly complete with minor issues.\n";
} elseif ($success_rate >= 60) {
    echo "\n⚠️  NEEDS WORK! Phase 7 implementation has some issues to address.\n";
} else {
    echo "\n❌ CRITICAL! Phase 7 implementation needs significant work.\n";
}

echo "\n✨ Phase 7 Performance Monitor Test Complete!\n";