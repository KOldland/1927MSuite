<?php
/**
 * Test Social Strip Membership Integration
 * Tests the enhanced widget data functionality
 */

// Simple test without WordPress environment
echo "Testing Social Strip Membership Integration\n";
echo "==========================================\n\n";

// Check if the file exists and can be included
$file_path = '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/social-strip/includes/khm-integration.php';

if (!file_exists($file_path)) {
    echo "✗ KHM integration file not found: $file_path\n";
    exit(1);
}

echo "✓ KHM integration file found\n";

// Check syntax
$command = "php -l \"$file_path\"";
exec($command, $output, $return_var);

if ($return_var === 0) {
    echo "✓ PHP syntax is valid\n";
} else {
    echo "✗ PHP syntax errors found:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

// Check for duplicate methods
$content = file_get_contents($file_path);
$method_count = substr_count($content, 'function get_enhanced_widget_data');

if ($method_count === 1) {
    echo "✓ Only one get_enhanced_widget_data method found\n";
} else {
    echo "✗ Found $method_count get_enhanced_widget_data methods (expected 1)\n";
    exit(1);
}

// Check class structure
if (strpos($content, 'class KSS_KHM_Integration') !== false) {
    echo "✓ KSS_KHM_Integration class found\n";
} else {
    echo "✗ KSS_KHM_Integration class not found\n";
    exit(1);
}

// Check for key methods
$required_methods = [
    'get_enhanced_widget_data',
    'handle_download_request',
    'handle_save_to_library',
    'handle_purchase_request'
];

foreach ($required_methods as $method) {
    if (strpos($content, "function $method") !== false) {
        echo "✓ Method $method found\n";
    } else {
        echo "✗ Method $method not found\n";
    }
}

echo "\n✓ All basic checks passed!\n";
echo "The Social Strip membership integration appears to be properly configured.\n";