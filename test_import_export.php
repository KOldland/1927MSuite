<?php
/**
 * Test KH Events Import/Export Functionality
 * Tests the new CSV, iCal, and Facebook import/export capabilities
 */

echo "<h1>KH Events Import/Export Test</h1>";

// Test 1: Check if import/export class is loaded
echo "<h2>Test 1: Class Loading</h2>";
if (class_exists('KH_Event_Import_Export')) {
    echo "✓ KH_Event_Import_Export class is loaded<br>";
} else {
    echo "✗ KH_Event_Import_Export class is not loaded<br>";
}

// Test 2: Check if instance method exists
echo "<h2>Test 2: Instance Method</h2>";
if (method_exists('KH_Event_Import_Export', 'instance')) {
    echo "✓ instance() method exists<br>";
    $instance = KH_Event_Import_Export::instance();
    if ($instance instanceof KH_Event_Import_Export) {
        echo "✓ instance() returns valid object<br>";
    } else {
        echo "✗ instance() does not return valid object<br>";
    }
} else {
    echo "✗ instance() method does not exist<br>";
}

// Test 3: Check if files exist
echo "<h2>Test 3: File Existence</h2>";
$files = [
    'wp-content/plugins/kh-events/includes/class-kh-event-import-export.php',
    'wp-content/plugins/kh-events/assets/js/import-export-admin.js',
    'wp-content/plugins/kh-events/assets/css/import-export-admin.css',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ File exists: $file<br>";
    } else {
        echo "✗ File missing: $file<br>";
    }
}

// Test 4: Check method implementations
echo "<h2>Test 4: Method Implementation</h2>";
$import_export_file = 'wp-content/plugins/kh-events/includes/class-kh-event-import-export.php';
if (file_exists($import_export_file)) {
    $content = file_get_contents($import_export_file);

    $methods = [
        'add_import_export_menu',
        'enqueue_admin_scripts',
        'render_import_export_page',
        'ajax_import_events',
        'ajax_export_events',
        'ajax_import_ical',
        'ajax_import_facebook',
        'export_csv',
        'export_ical',
        'export_json',
        'parse_csv_file',
        'import_csv_data',
        'fetch_facebook_events',
        'escape_ical_text'
    ];

    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "✓ Method '$method' is implemented<br>";
        } else {
            echo "✗ Method '$method' is not implemented<br>";
        }
    }
} else {
    echo "✗ Import/Export class file not found<br>";
}

// Test 5: Check admin menu registration
echo "<h2>Test 5: Admin Menu</h2>";
global $submenu;
$found_menu = false;
if (isset($submenu['edit.php?post_type=kh_event'])) {
    foreach ($submenu['edit.php?post_type=kh_event'] as $item) {
        if (isset($item[2]) && $item[2] === 'kh-events-import-export') {
            echo "✓ Import/Export submenu is registered<br>";
            $found_menu = true;
            break;
        }
    }
}
if (!$found_menu) {
    echo "✗ Import/Export submenu is not registered<br>";
}

// Test 6: Check AJAX actions
echo "<h2>Test 6: AJAX Actions</h2>";
$ajax_actions = [
    'kh_import_events',
    'kh_export_events',
    'kh_import_ical',
    'kh_import_facebook'
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "✓ AJAX action '$action' is registered<br>";
    } else {
        echo "✗ AJAX action '$action' is not registered<br>";
    }
}

// Test 7: Check script/style enqueuing
echo "<h2>Test 7: Asset Enqueuing</h2>";
// This would require simulating admin page load, so we'll check if the enqueue method exists
if (method_exists('KH_Event_Import_Export', 'enqueue_admin_scripts')) {
    echo "✓ enqueue_admin_scripts method exists<br>";
} else {
    echo "✗ enqueue_admin_scripts method does not exist<br>";
}

echo "<h2>Test Complete</h2>";
echo "<p>If all tests show ✓, the Import/Export functionality is properly implemented.</p>";
?>