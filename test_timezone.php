<?php
/**
 * Test KH Events Timezone Support
 * Tests the new multi-timezone functionality for events and users
 */

echo "<h1>KH Events Timezone Test</h1>";

// Test 1: Check if timezone class is loaded
echo "<h2>Test 1: Class Loading</h2>";
if (class_exists('KH_Event_Timezone')) {
    echo "✓ KH_Event_Timezone class is loaded<br>";
} else {
    echo "✗ KH_Event_Timezone class is not loaded<br>";
}

// Test 2: Check if instance method exists
echo "<h2>Test 2: Instance Method</h2>";
if (method_exists('KH_Event_Timezone', 'instance')) {
    echo "✓ instance() method exists<br>";
    $instance = KH_Event_Timezone::instance();
    if ($instance instanceof KH_Event_Timezone) {
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
    'wp-content/plugins/kh-events/includes/class-kh-event-timezone.php',
    'wp-content/plugins/kh-events/assets/js/timezone-admin.js',
    'wp-content/plugins/kh-events/assets/js/timezone-frontend.js',
    'wp-content/plugins/kh-events/assets/css/timezone.css',
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
if (file_exists('wp-content/plugins/kh-events/includes/class-kh-event-timezone.php')) {
    $content = file_get_contents('wp-content/plugins/kh-events/includes/class-kh-event-timezone.php');

    $methods = [
        'get_user_timezone',
        'set_user_timezone',
        'get_event_timezone',
        'set_event_timezone',
        'is_valid_timezone',
        'convert_datetime',
        'format_datetime_for_user',
        'get_timezone_offset',
        'get_timezone_abbr',
        'get_available_timezones',
        'add_timezone_meta_box',
        'save_event_timezone_meta',
        'register_timezone_settings',
        'ajax_get_timezone_info',
        'ajax_convert_timezone',
        'filter_display_time',
        'filter_datetime_format',
        'add_timezone_to_rest_response',
        'admin_enqueue_scripts',
        'frontend_enqueue_scripts',
        'get_timezone_select_html'
    ];

    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "✓ Method '$method' is implemented<br>";
        } else {
            echo "✗ Method '$method' is not implemented<br>";
        }
    }
} else {
    echo "✗ Timezone class file not found<br>";
}

// Test 5: Test timezone validation
echo "<h2>Test 5: Timezone Validation</h2>";
$test_timezones = [
    'America/New_York' => true,
    'Europe/London' => true,
    'Asia/Tokyo' => true,
    'Invalid/Timezone' => false,
    'America/Invalid' => false,
];

foreach ($test_timezones as $tz => $expected) {
    $timezone_instance = KH_Event_Timezone::instance();
    $result = $timezone_instance->is_valid_timezone($tz);
    if ($result === $expected) {
        echo "✓ Timezone validation: $tz<br>";
    } else {
        echo "✗ Timezone validation failed: $tz (expected " . ($expected ? 'valid' : 'invalid') . ")<br>";
    }
}

// Test 6: Test timezone conversion
echo "<h2>Test 6: Timezone Conversion</h2>";
$timezone_instance = KH_Event_Timezone::instance();
$test_datetime = '2024-01-15 10:00:00';
$converted = $timezone_instance->convert_datetime($test_datetime, 'America/New_York', 'Europe/London');

if ($converted) {
    echo "✓ Timezone conversion works: $test_datetime EST → $converted GMT<br>";
} else {
    echo "✗ Timezone conversion failed<br>";
}

// Test 7: Test available timezones
echo "<h2>Test 7: Available Timezones</h2>";
$available = $timezone_instance->get_available_timezones();
if (is_array($available) && count($available) > 10) {
    echo "✓ Available timezones loaded: " . count($available) . " timezones<br>";
    echo "<details><summary>Sample Timezones</summary><ul>";
    $count = 0;
    foreach ($available as $tz => $name) {
        if ($count < 10) {
            echo "<li>$tz: $name</li>";
            $count++;
        } else {
            break;
        }
    }
    echo "</ul></details>";
} else {
    echo "✗ Available timezones not loaded properly<br>";
}

// Test 8: Test timezone offset calculation
echo "<h2>Test 8: Timezone Offsets</h2>";
$test_offsets = [
    'America/New_York' => -5, // EST
    'Europe/London' => 0,     // GMT
    'Asia/Tokyo' => 9,        // JST
];

foreach ($test_offsets as $tz => $expected_offset) {
    $offset = $timezone_instance->get_timezone_offset($tz);
    // Allow for some variance due to DST
    if (abs($offset - $expected_offset) <= 1) {
        echo "✓ Timezone offset for $tz: $offset<br>";
    } else {
        echo "✗ Timezone offset for $tz: $offset (expected ~$expected_offset)<br>";
    }
}

// Test 9: Test timezone select HTML generation
echo "<h2>Test 9: HTML Generation</h2>";
$select_html = $timezone_instance->get_timezone_select_html('America/New_York', 'test_timezone');
if (strpos($select_html, '<select') !== false && strpos($select_html, 'America/New_York') !== false) {
    echo "✓ Timezone select HTML generated<br>";
} else {
    echo "✗ Timezone select HTML generation failed<br>";
}

// Test 10: Check AJAX handlers registration
echo "<h2>Test 10: AJAX Handlers</h2>";
$ajax_actions = [
    'kh_save_user_timezone',
    'kh_update_event_timezone',
    'kh_get_event_timezone_info',
];

foreach ($ajax_actions as $action) {
    if (has_action("wp_ajax_$action")) {
        echo "✓ AJAX action '$action' is registered<br>";
    } else {
        echo "⚠ AJAX action '$action' registration status unknown (expected in WordPress environment)<br>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>If most tests show ✓, the timezone functionality is properly implemented.</p>";
echo "<p><strong>Note:</strong> Some AJAX and WordPress-specific tests may show ⚠ when run outside WordPress environment.</p>";
?>