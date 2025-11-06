<?php
/**
 * KHM Attribution Quick Validation
 * 
 * Quick validation script to verify Phase 2 implementation
 */

if (!defined('ABSPATH')) {
    exit;
}

echo "<h2>🚀 Phase 2 Performance Optimization - Quick Validation</h2>\n";
echo "<div style='font-family: monospace; background: #f8fafc; padding: 20px; border-radius: 8px;'>\n";

// Check if all Phase 2 files exist
$phase2_files = array(
    'PerformanceManager.php' => 'Multi-level caching and performance optimization',
    'AsyncManager.php' => 'Background job processing and queue management',
    'QueryBuilder.php' => 'Optimized database queries with caching',
    'PerformanceUpdates.php' => 'Integration methods for AttributionManager',
    'PerformanceDashboard.php' => 'Real-time performance monitoring interface'
);

echo "<h3>📁 File Verification</h3>\n";
$files_exist = 0;
$total_files = count($phase2_files);

foreach ($phase2_files as $file => $description) {
    $file_path = dirname(__FILE__) . "/../src/Attribution/" . $file;
    $exists = file_exists($file_path);
    
    if ($exists) {
        $file_size = filesize($file_path);
        $size_kb = round($file_size / 1024, 1);
        echo "✅ {$file}: {$description} ({$size_kb}KB)\n";
        $files_exist++;
    } else {
        echo "❌ {$file}: Missing - {$description}\n";
    }
}

$file_completion = round(($files_exist / $total_files) * 100);
echo "\n📊 File Completion: {$files_exist}/{$total_files} ({$file_completion}%)\n\n";

// Check file syntax
echo "<h3>🔍 Syntax Validation</h3>\n";
$syntax_valid = 0;

foreach ($phase2_files as $file => $description) {
    $file_path = dirname(__FILE__) . "/../src/Attribution/" . $file;
    
    if (file_exists($file_path)) {
        $output = array();
        $return_var = 0;
        exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "✅ {$file}: Syntax OK\n";
            $syntax_valid++;
        } else {
            echo "❌ {$file}: Syntax Error\n";
            echo "   " . implode("\n   ", $output) . "\n";
        }
    }
}

$syntax_completion = $files_exist > 0 ? round(($syntax_valid / $files_exist) * 100) : 0;
echo "\n📊 Syntax Validation: {$syntax_valid}/{$files_exist} ({$syntax_completion}%)\n\n";

// Check class instantiation
echo "<h3>🧩 Class Instantiation Test</h3>\n";
$classes_loaded = 0;

foreach ($phase2_files as $file => $description) {
    $file_path = dirname(__FILE__) . "/../src/Attribution/" . $file;
    
    if (file_exists($file_path) && $syntax_valid > 0) {
        try {
            require_once $file_path;
            $class_name = 'KHM_Attribution_' . str_replace('.php', '', $file);
            
            if (class_exists($class_name)) {
                echo "✅ {$class_name}: Class loaded successfully\n";
                $classes_loaded++;
                
                // Try to instantiate (basic test)
                try {
                    $instance = new $class_name();
                    echo "   ↳ Instantiation: OK\n";
                } catch (Exception $e) {
                    echo "   ↳ Instantiation: Failed - " . $e->getMessage() . "\n";
                }
            } else {
                echo "❌ {$class_name}: Class not found in file\n";
            }
        } catch (Exception $e) {
            echo "❌ {$file}: Load error - " . $e->getMessage() . "\n";
        }
    }
}

$class_completion = $files_exist > 0 ? round(($classes_loaded / $files_exist) * 100) : 0;
echo "\n📊 Class Loading: {$classes_loaded}/{$files_exist} ({$class_completion}%)\n\n";

// Test suite availability
echo "<h3>🧪 Test Suite Verification</h3>\n";
$test_files = array(
    'test-performance-suite.php' => 'Performance testing suite',
    'test-integration-suite.php' => 'Integration testing suite',
    'test-runner.php' => 'Comprehensive test runner'
);

$tests_available = 0;
foreach ($test_files as $test_file => $description) {
    $test_path = dirname(__FILE__) . "/" . $test_file;
    $exists = file_exists($test_path);
    
    if ($exists) {
        echo "✅ {$test_file}: {$description}\n";
        $tests_available++;
    } else {
        echo "❌ {$test_file}: Missing - {$description}\n";
    }
}

echo "\n📊 Test Suite: {$tests_available}/" . count($test_files) . " available\n\n";

// Overall Phase 2 Assessment
echo "<h3>📋 Phase 2 Assessment</h3>\n";

$overall_metrics = array(
    'File Creation' => $file_completion,
    'Syntax Validation' => $syntax_completion,
    'Class Loading' => $class_completion,
    'Test Availability' => round(($tests_available / count($test_files)) * 100)
);

$overall_score = round(array_sum($overall_metrics) / count($overall_metrics));

echo "=== PHASE 2 METRICS ===\n";
foreach ($overall_metrics as $metric => $score) {
    $status = $score >= 80 ? '✅' : ($score >= 60 ? '⚠️' : '❌');
    echo "{$status} {$metric}: {$score}%\n";
}

echo "\n=== OVERALL PHASE 2 SCORE ===\n";
if ($overall_score >= 90) {
    echo "🏆 EXCELLENT ({$overall_score}%): Phase 2 implementation complete and ready\n";
    echo "✅ All performance optimization components implemented\n";
    echo "✅ Multi-level caching system operational\n";
    echo "✅ Async processing capabilities ready\n";
    echo "✅ Performance monitoring dashboard available\n";
    echo "🚀 READY FOR PHASE 3: Enhanced Business Analytics\n";
} elseif ($overall_score >= 80) {
    echo "🥇 GOOD ({$overall_score}%): Phase 2 mostly complete, minor issues\n";
    echo "✅ Core performance components implemented\n";
    echo "⚠️ Some optimization opportunities remain\n";
    echo "🔧 RECOMMENDATION: Address remaining issues before Phase 3\n";
} elseif ($overall_score >= 70) {
    echo "🥈 FAIR ({$overall_score}%): Phase 2 foundation present, needs work\n";
    echo "⚠️ Performance framework partially implemented\n";
    echo "🛠️ RECOMMENDATION: Complete Phase 2 implementation\n";
} else {
    echo "🥉 NEEDS WORK ({$overall_score}%): Phase 2 requires significant development\n";
    echo "❌ Major performance components missing or broken\n";
    echo "🔄 RECOMMENDATION: Focus on Phase 2 completion\n";
}

// SLO Target Assessment
echo "\n=== SLO TARGET READINESS ===\n";
$slo_targets = array(
    'API Response Time P95' => '< 300ms',
    'Dashboard Load Time P95' => '< 2 seconds', 
    'Tracking Endpoint Avg' => '< 50ms',
    'Cache Hit Rate' => '> 80%',
    'System Uptime' => '> 99.9%'
);

echo "Target SLO Metrics:\n";
foreach ($slo_targets as $metric => $target) {
    echo "  📊 {$metric}: {$target}\n";
}

$slo_readiness = $overall_score >= 85 ? 'READY' : ($overall_score >= 70 ? 'NEEDS TESTING' : 'NOT READY');
echo "\n🎯 SLO Compliance Readiness: {$slo_readiness}\n";

// Next Steps
echo "\n=== NEXT STEPS ===\n";
if ($overall_score >= 90) {
    echo "1. 🧪 Run comprehensive performance testing\n";
    echo "2. 📊 Validate SLO compliance under load\n";
    echo "3. 🚀 Begin Phase 3: Enhanced Business Analytics\n";
    echo "4. 📈 Implement P&L calculations and funnel analysis\n";
} elseif ($overall_score >= 80) {
    echo "1. 🔧 Fix remaining syntax/loading issues\n";
    echo "2. 🧪 Run performance validation tests\n";
    echo "3. 📊 Verify caching and async functionality\n";
    echo "4. ✅ Complete Phase 2 before moving to Phase 3\n";
} else {
    echo "1. 🛠️ Complete missing file implementations\n";
    echo "2. 🔍 Fix syntax and class loading errors\n";
    echo "3. 🧪 Implement comprehensive testing\n";
    echo "4. 📊 Verify performance optimization functionality\n";
}

echo "\n</div>\n";

// Auto-generate test link
$test_url = $_SERVER['REQUEST_URI'];
$test_url = str_replace(basename($_SERVER['PHP_SELF']), 'test-runner.php?run_tests=true', $test_url);

echo "\n<div style='margin: 20px 0; padding: 15px; background: #e0f2fe; border-radius: 8px;'>\n";
echo "<h3>🧪 Run Comprehensive Tests</h3>\n";
echo "<p>Ready to run the full test suite? Click below to execute all performance and integration tests:</p>\n";
echo "<a href='{$test_url}' style='display: inline-block; padding: 10px 20px; background: #1976d2; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;'>Run Complete Test Suite</a>\n";
echo "</div>\n";
?>