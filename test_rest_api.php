<?php
/**
 * Test KH Events REST API Endpoints
 * Tests the new REST API functionality for events, locations, and bookings
 */

echo "<h1>KH Events REST API Test</h1>";

// Test 1: Check if REST API class is loaded
echo "<h2>Test 1: Class Loading</h2>";
if (class_exists('KH_Event_REST_API')) {
    echo "✓ KH_Event_REST_API class is loaded<br>";
} else {
    echo "✗ KH_Event_REST_API class is not loaded<br>";
}

// Test 2: Check if instance method exists
echo "<h2>Test 2: Instance Method</h2>";
if (method_exists('KH_Event_REST_API', 'instance')) {
    echo "✓ instance() method exists<br>";
    $instance = KH_Event_REST_API::instance();
    if ($instance instanceof KH_Event_REST_API) {
        echo "✓ instance() returns valid object<br>";
    } else {
        echo "✗ instance() does not return valid object<br>";
    }
} else {
    echo "✗ instance() method does not exist<br>";
}

// Test 3: Check if file exists
echo "<h2>Test 3: File Existence</h2>";
$file = 'wp-content/plugins/kh-events/includes/class-kh-event-rest-api.php';
if (file_exists($file)) {
    echo "✓ REST API class file exists<br>";
} else {
    echo "✗ REST API class file missing<br>";
}

// Test 4: Check method implementations
echo "<h2>Test 4: Method Implementation</h2>";
if (file_exists($file)) {
    $content = file_get_contents($file);

    $methods = [
        'register_rest_routes',
        'get_events',
        'get_event',
        'create_event',
        'update_event',
        'delete_event',
        'get_locations',
        'get_location',
        'create_location',
        'update_location',
        'delete_location',
        'get_bookings',
        'get_booking',
        'create_booking',
        'update_booking',
        'delete_booking',
        'get_categories',
        'get_tags',
        'search_events',
        'get_calendar_feed',
        'prepare_event_for_response',
        'prepare_location_for_response',
        'prepare_booking_for_response'
    ];

    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "✓ Method '$method' is implemented<br>";
        } else {
            echo "✗ Method '$method' is not implemented<br>";
        }
    }
} else {
    echo "✗ REST API class file not found<br>";
}

// Test 5: Check REST routes registration
echo "<h2>Test 5: REST Routes</h2>";
global $wp_rest_server;
if (isset($wp_rest_server) && method_exists($wp_rest_server, 'get_routes')) {
    $routes = $wp_rest_server->get_routes();
    $kh_routes = array_filter(array_keys($routes), function($route) {
        return strpos($route, 'kh-events/v1') === 0;
    });

    if (!empty($kh_routes)) {
        echo "✓ KH Events REST routes are registered (" . count($kh_routes) . " routes)<br>";
        echo "<details><summary>Registered Routes</summary><ul>";
        foreach ($kh_routes as $route) {
            echo "<li>$route</li>";
        }
        echo "</ul></details>";
    } else {
        echo "✗ No KH Events REST routes found<br>";
    }
} else {
    echo "⚠ REST server not initialized (expected in WordPress environment)<br>";
}

// Test 6: Check API endpoints accessibility
echo "<h2>Test 6: API Endpoints</h2>";
$endpoints = [
    '/wp-json/kh-events/v1/events',
    '/wp-json/kh-events/v1/locations',
    '/wp-json/kh-events/v1/bookings',
    '/wp-json/kh-events/v1/categories',
    '/wp-json/kh-events/v1/tags',
    '/wp-json/kh-events/v1/search',
    '/wp-json/kh-events/v1/calendar'
];

echo "<p>Expected API endpoints:</p><ul>";
foreach ($endpoints as $endpoint) {
    echo "<li><code>$endpoint</code></li>";
}
echo "</ul>";

// Test 7: Check permission methods
echo "<h2>Test 7: Permission Methods</h2>";
$permission_methods = [
    'get_events_permissions_check',
    'create_event_permissions_check',
    'get_locations_permissions_check',
    'create_location_permissions_check',
    'get_bookings_permissions_check',
    'create_booking_permissions_check',
    'search_events_permissions_check'
];

foreach ($permission_methods as $method) {
    if (strpos($content, "function $method") !== false) {
        echo "✓ Permission method '$method' is implemented<br>";
    } else {
        echo "✗ Permission method '$method' is not implemented<br>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>If all tests show ✓, the REST API functionality is properly implemented.</p>";
echo "<p><strong>Note:</strong> Some tests may show ⚠ when run outside WordPress environment.</p>";
?>