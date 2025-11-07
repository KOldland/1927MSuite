<?php
/**
 * Phase 2 Optimization Validation Test
 * Tests the PerformanceUpdates component after OOP pattern conversion
 */

// Test configuration
$test_config = array(
    'test_name' => 'Phase 2 Optimization Validation',
    'target_component' => 'PerformanceUpdates.php',
    'target_success_rate' => 85.0
);

echo "🔧 {$test_config['test_name']}\n";
echo "=" . str_repeat("=", strlen($test_config['test_name']) + 3) . "\n\n";

// Component to test
$component_path = '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-plugin/src/Attribution/PerformanceUpdates.php';

if (!file_exists($component_path)) {
    echo "❌ Component file not found: $component_path\n";
    exit(1);
}

$component_content = file_get_contents($component_path);
$component_lines = explode("\n", $component_content);
$total_lines = count($component_lines);

echo "📁 Testing Component: " . basename($component_path) . "\n";
echo "📏 Total Lines: $total_lines\n\n";

// Test Suite
$tests = array(
    'class_structure' => array(
        'name' => 'Class Structure & Definition',
        'weight' => 15
    ),
    'constructor_implementation' => array(
        'name' => 'Constructor Implementation',
        'weight' => 20
    ),
    'instance_methods' => array(
        'name' => 'Instance Method Patterns',
        'weight' => 25
    ),
    'static_method_elimination' => array(
        'name' => 'Static Method Elimination',
        'weight' => 20
    ),
    'dependency_injection' => array(
        'name' => 'Dependency Injection Pattern',
        'weight' => 20
    )
);

$test_results = array();

// 1. Class Structure Test
echo "🏗️  Testing Class Structure...\n";
$class_score = 0;

if (preg_match('/class\s+KHM_Attribution_Performance_Updates\s*{/', $component_content)) {
    $class_score += 30;
    echo "   ✅ Class properly defined\n";
} else {
    echo "   ❌ Class definition missing or malformed\n";
}

if (preg_match('/private\s+\$\w+/', $component_content)) {
    $class_score += 35;
    echo "   ✅ Private properties defined\n";
} else {
    echo "   ❌ No private properties found\n";
}

if (preg_match('/public\s+function\s+__construct/', $component_content)) {
    $class_score += 35;
    echo "   ✅ Constructor method exists\n";
} else {
    echo "   ❌ Constructor missing\n";
}

$test_results['class_structure'] = $class_score;

// 2. Constructor Implementation Test
echo "\n🚀 Testing Constructor Implementation...\n";
$constructor_score = 0;

if (preg_match('/public\s+function\s+__construct\(\)\s*{/', $component_content)) {
    $constructor_score += 25;
    echo "   ✅ Constructor signature correct\n";
} else {
    echo "   ❌ Constructor signature issues\n";
}

if (preg_match('/\$this->init_performance_components\(\)/', $component_content)) {
    $constructor_score += 25;
    echo "   ✅ Performance components initialized\n";
} else {
    echo "   ❌ Performance components not initialized\n";
}

if (preg_match('/\$this->setup_optimization_config\(\)/', $component_content)) {
    $constructor_score += 25;
    echo "   ✅ Optimization config setup called\n";
} else {
    echo "   ❌ Optimization config not setup\n";
}

if (preg_match('/\$this->register_performance_hooks\(\)/', $component_content)) {
    $constructor_score += 25;
    echo "   ✅ Performance hooks registered\n";
} else {
    echo "   ❌ Performance hooks not registered\n";
}

$test_results['constructor_implementation'] = $constructor_score;

// 3. Instance Methods Test
echo "\n🔧 Testing Instance Method Patterns...\n";
$instance_score = 0;

// Count instance methods vs static methods
$instance_methods = preg_match_all('/public\s+function\s+(?!__construct)\w+\([^)]*\)\s*{/', $component_content);
$static_methods = preg_match_all('/public\s+static\s+function\s+\w+/', $component_content);

echo "   📊 Instance methods found: $instance_methods\n";
echo "   📊 Static methods found: $static_methods\n";

if ($instance_methods >= 3) {
    $instance_score += 40;
    echo "   ✅ Sufficient instance methods implemented\n";
} else {
    echo "   ❌ Insufficient instance methods\n";
}

if (preg_match('/\$this->performance_manager/', $component_content)) {
    $instance_score += 30;
    echo "   ✅ Instance property usage detected\n";
} else {
    echo "   ❌ Instance properties not used\n";
}

if (preg_match('/\$this->query_builder/', $component_content)) {
    $instance_score += 30;
    echo "   ✅ Query builder accessed via instance\n";
} else {
    echo "   ❌ Query builder not accessed via instance\n";
}

$test_results['instance_methods'] = $instance_score;

// 4. Static Method Elimination Test
echo "\n🚫 Testing Static Method Elimination...\n";
$static_elimination_score = 0;

// Check for problematic static methods (excluding backward compatibility)
$problematic_static = preg_match_all('/public\s+static\s+function\s+(?!init_performance_for_manager|create_attribution_tables)/', $component_content);

echo "   📊 Problematic static methods: $problematic_static\n";

if ($problematic_static <= 1) {
    $static_elimination_score += 50;
    echo "   ✅ Static methods properly eliminated\n";
} else {
    echo "   ⚠️  Some static methods remain unconverted\n";
}

// Check that key methods are now instance methods
$key_instance_methods = array(
    'store_attribution_event_optimized',
    'resolve_conversion_attribution_optimized',
    'store_conversion_with_attribution_optimized',
    'get_conversion_attribution_optimized'
);

$converted_methods = 0;
foreach ($key_instance_methods as $method) {
    if (preg_match("/public\\s+function\\s+{$method}\\(/", $component_content)) {
        $converted_methods++;
    }
}

if ($converted_methods >= 3) {
    $static_elimination_score += 50;
    echo "   ✅ Key methods converted to instance methods ($converted_methods/4)\n";
} else {
    echo "   ❌ Key methods not fully converted ($converted_methods/4)\n";
}

$test_results['static_method_elimination'] = $static_elimination_score;

// 5. Dependency Injection Test
echo "\n💉 Testing Dependency Injection Pattern...\n";
$dependency_score = 0;

$dependencies = array(
    'performance_manager' => 'PerformanceManager',
    'async_manager' => 'AsyncManager', 
    'query_builder' => 'QueryBuilder'
);

$injected_deps = 0;
foreach ($dependencies as $property => $class) {
    if (preg_match("/\\\$this->{$property}\\s*=/", $component_content)) {
        $injected_deps++;
        echo "   ✅ {$class} dependency injected\n";
    } else {
        echo "   ❌ {$class} dependency not injected\n";
    }
}

if ($injected_deps >= 2) {
    $dependency_score += 60;
} else {
    $dependency_score += 20;
}

if (preg_match('/private\s+function\s+init_performance_components/', $component_content)) {
    $dependency_score += 40;
    echo "   ✅ Dependency initialization method exists\n";
} else {
    echo "   ❌ Dependency initialization missing\n";
}

$test_results['dependency_injection'] = $dependency_score;

// Calculate Overall Score
echo "\n📊 Test Results Summary\n";
echo "=" . str_repeat("=", 23) . "\n";

$weighted_score = 0;
$total_weight = 0;

foreach ($tests as $test_key => $test_info) {
    $score = $test_results[$test_key];
    $weighted_contribution = ($score / 100) * $test_info['weight'];
    $weighted_score += $weighted_contribution;
    $total_weight += $test_info['weight'];
    
    $status = $score >= 70 ? '✅' : ($score >= 50 ? '⚠️' : '❌');
    echo sprintf("   %s %-30s %3d%% (Weight: %2d%%)\n", 
        $status, $test_info['name'], $score, $test_info['weight']);
}

$overall_score = ($weighted_score / $total_weight) * 100;

echo "\n🎯 Overall Score: " . number_format($overall_score, 1) . "%\n";

if ($overall_score >= $test_config['target_success_rate']) {
    echo "🎉 SUCCESS: Phase 2 optimization target achieved!\n";
    $exit_code = 0;
} else {
    echo "⚠️  NEEDS IMPROVEMENT: Below target success rate\n";
    $exit_code = 1;
}

echo "\n📈 Score Breakdown:\n";
echo "   Target: {$test_config['target_success_rate']}%\n";
echo "   Achieved: " . number_format($overall_score, 1) . "%\n";
echo "   Improvement: " . number_format($overall_score - 42.9, 1) . "% (from original 42.9%)\n";

// Component health metrics
echo "\n🏥 Component Health Metrics:\n";
echo "   📁 File Size: " . number_format(strlen($component_content) / 1024, 1) . " KB\n";
echo "   📏 Total Lines: $total_lines\n";
echo "   🔧 Functions: " . substr_count($component_content, 'function ') . "\n";
echo "   🏗️  Classes: " . substr_count($component_content, 'class ') . "\n";

echo "\n✨ Phase 2 Optimization Test Complete!\n";
exit($exit_code);
?>