<?php
/**
 * Simple Plugin Validation Test
 */

echo "=== TouchPoint MailChimp Plugin Validation ===" . PHP_EOL;

// Test 1: File Structure
echo "1. File Structure: ";
$required_files = [
    'touchpoint-mailchimp.php',
    'includes/class-api.php',
    'includes/class-settings.php', 
    'includes/class-logger.php',
    'includes/admin/class-admin.php',
    'assets/css/frontend.css',
    'assets/js/frontend.js',
    'templates/subscription-form.php'
];

$files_exist = true;
foreach ($required_files as $file) {
    if (!file_exists($file)) {
        echo "FAIL (missing: $file)" . PHP_EOL;
        $files_exist = false;
        break;
    }
}
if ($files_exist) echo "PASS" . PHP_EOL;

// Test 2: PHP Syntax
echo "2. PHP Syntax: ";
$php_files = array_merge(
    glob("*.php"),
    glob("includes/*.php"),
    glob("includes/*/*.php")
);

$syntax_ok = true;
foreach ($php_files as $file) {
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (strpos($output, "No syntax errors") === false) {
        echo "FAIL (syntax error in: $file)" . PHP_EOL;
        $syntax_ok = false;
        break;
    }
}
if ($syntax_ok) echo "PASS" . PHP_EOL;

// Test 3: Core Classes
echo "3. Core Classes: ";
try {
    require_once 'includes/class-settings.php';
    require_once 'includes/class-logger.php';
    require_once 'includes/class-api.php';
    
    $classes_ok = 
        class_exists('TouchPoint_MailChimp_Settings') &&
        class_exists('TouchPoint_MailChimp_Logger') &&
        class_exists('TouchPoint_MailChimp_API');
        
    echo $classes_ok ? "PASS" : "FAIL";
} catch (Exception $e) {
    echo "FAIL (exception: " . $e->getMessage() . ")";
}
echo PHP_EOL;

// Test 4: Singleton Patterns
echo "4. Singleton Patterns: ";
try {
    $settings1 = TouchPoint_MailChimp_Settings::instance();
    $settings2 = TouchPoint_MailChimp_Settings::instance();
    $singleton_ok = ($settings1 === $settings2);
    echo $singleton_ok ? "PASS" : "FAIL";
} catch (Exception $e) {
    echo "FAIL";
}
echo PHP_EOL;

// Test 5: Asset Files
echo "5. Asset Files: ";
$css_size = filesize('assets/css/frontend.css');
$js_size = filesize('assets/js/frontend.js');
$admin_css_size = filesize('assets/css/admin.css');
$admin_js_size = filesize('assets/js/admin.js');

$assets_ok = ($css_size > 5000 && $js_size > 3000 && $admin_css_size > 10000 && $admin_js_size > 8000);
echo $assets_ok ? "PASS" : "FAIL";
echo PHP_EOL;

// Test 6: Plugin Header
echo "6. WordPress Plugin Header: ";
$main_file = file_get_contents('touchpoint-mailchimp.php');
$has_header = 
    strpos($main_file, 'Plugin Name:') !== false &&
    strpos($main_file, 'Version:') !== false &&
    strpos($main_file, 'Author:') !== false;
echo $has_header ? "PASS" : "FAIL";
echo PHP_EOL;

// Test 7: Template Functionality
echo "7. Form Template: ";
$template = file_get_contents('templates/subscription-form.php');
$template_ok = 
    strpos($template, 'tmc-subscription-form') !== false &&
    strpos($template, 'email') !== false &&
    strpos($template, 'submit') !== false;
echo $template_ok ? "PASS" : "FAIL";
echo PHP_EOL;

echo PHP_EOL . "Plugin validation complete!" . PHP_EOL;
?>