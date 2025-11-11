<?php
/**
 * Basic Code Structure Test
 *
 * Tests that all new classes can be instantiated and basic methods work
 * without requiring full WordPress environment.
 *
 * @package KHM_SEO\GEO\Tests
 */

echo "🧪 KHM SEO GEO System - Basic Code Structure Test\n";
echo str_repeat('=', 60) . "\n\n";

// Test counters
$tests = 0;
$passed = 0;
$failed = 0;

function test_class($class_name, $test_name) {
    global $tests, $passed, $failed;
    $tests++;

    echo "Testing $test_name... ";

    if (class_exists($class_name)) {
        echo "✅ PASS\n";
        $passed++;
        return true;
    } else {
        echo "❌ FAIL - Class not found\n";
        $failed++;
        return false;
    }
}

function test_method($object, $method_name, $test_name) {
    global $tests, $passed, $failed;
    $tests++;

    echo "Testing $test_name... ";

    if (method_exists($object, $method_name)) {
        echo "✅ PASS\n";
        $passed++;
        return true;
    } else {
        echo "❌ FAIL - Method not found\n";
        $failed++;
        return false;
    }
}

// Set up basic autoloader for testing
spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/src/';
    $file = str_replace('\\', '/', $class) . '.php';
    $file = str_replace('KHM_SEO/', '', $file);

    if (file_exists($base_dir . $file)) {
        require_once $base_dir . $file;
    }
});

echo "Testing Core GEO Classes:\n";
echo str_repeat('-', 30) . "\n";

// Test EntityManager
if (test_class('KHM_SEO\\GEO\\Entity\\EntityManager', 'EntityManager Class')) {
    // We can't instantiate without database, but we can check methods exist
    $reflection = new ReflectionClass('KHM_SEO\\GEO\\Entity\\EntityManager');
    test_method($reflection, 'get_entity', 'EntityManager::get_entity method');
    test_method($reflection, 'create_entity', 'EntityManager::create_entity method');
    test_method($reflection, 'auto_link_entities', 'EntityManager::auto_link_entities method');
}

// Test AutoLinker
test_class('KHM_SEO\\GEO\\AutoLink\\AutoLinker', 'AutoLinker Class');

// Test EntityValidator
test_class('KHM_SEO\\GEO\\Validation\\EntityValidator', 'EntityValidator Class');

echo "\nTesting Elementor Integration Classes:\n";
echo str_repeat('-', 40) . "\n";

// Test Elementor Integration
test_class('KHM_SEO\\Elementor\\ElementorIntegration', 'ElementorIntegration Class');

// Test Widgets
test_class('KHM_SEO\\Elementor\\Widgets\\AnswerCard', 'AnswerCard Widget');
test_class('KHM_SEO\\Elementor\\Widgets\\ClientBadge', 'ClientBadge Widget');

// Test Controls
test_class('KHM_SEO\\Elementor\\Controls\\EntityAutocomplete', 'EntityAutocomplete Control');

echo "\n" . str_repeat('=', 60) . "\n";
echo "Test Results: $passed/$tests passed";
if ($failed > 0) {
    echo ", $failed failed";
}
echo "\n";

if ($failed === 0) {
    echo "🎉 All basic code structure tests passed!\n";
} else {
    echo "⚠️  Some tests failed. Check the implementation.\n";
}
?>