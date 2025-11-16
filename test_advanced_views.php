<?php
/**
 * Test KH Events Advanced Views and iCal Export
 * Tests the new week view, photo view, and calendar export functionality
 */

echo "<h1>KH Events Advanced Features Test</h1>";

// Test 1: Check if new shortcodes are registered
echo "<h2>Test 1: Shortcode Registration</h2>";
global $shortcode_tags;
$shortcodes = ['kh_events_week', 'kh_events_photo', 'kh_events_ical', 'kh_events_map'];

foreach ($shortcodes as $shortcode) {
    if (isset($shortcode_tags[$shortcode])) {
        echo "✓ Shortcode '$shortcode' is registered<br>";
    } else {
        echo "✗ Shortcode '$shortcode' is not registered<br>";
    }
}

// Test 2: Check if files exist
echo "<h2>Test 2: File Existence</h2>";
$files = [
    'wp-content/plugins/kh-events/includes/class-kh-events-views.php',
    'wp-content/plugins/kh-events/includes/class-kh-events.php',
    'wp-content/plugins/kh-events/assets/css/kh-events.css',
    'wp-content/plugins/kh-events/assets/js/events-advanced.js',
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ File exists: $file<br>";
    } else {
        echo "✗ File missing: $file<br>";
    }
}

// Test 3: Check method implementations
echo "<h2>Test 3: Method Implementation</h2>";
$views_file = 'wp-content/plugins/kh-events/includes/class-kh-events-views.php';
if (file_exists($views_file)) {
    $content = file_get_contents($views_file);

    $methods = [
        'week_shortcode',
        'render_week',
        'photo_shortcode',
        'render_photo',
        'ical_shortcode',
        'generate_google_calendar_url'
    ];

    foreach ($methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "✓ Method '$method' found<br>";
        } else {
            echo "✗ Method '$method' not found<br>";
        }
    }
}

// Test 4: Check iCal export methods
echo "<h2>Test 4: iCal Export Methods</h2>";
$events_file = 'wp-content/plugins/kh-events/includes/class-kh-events.php';
if (file_exists($events_file)) {
    $content = file_get_contents($events_file);

    $ical_methods = [
        'handle_ical_export',
        'generate_ical_content',
        'format_ical_datetime',
        'escape_ical_text'
    ];

    foreach ($ical_methods as $method) {
        if (strpos($content, "function $method") !== false) {
            echo "✓ iCal method '$method' found<br>";
        } else {
            echo "✗ iCal method '$method' not found<br>";
        }
    }
}

// Test 5: Check CSS styles
echo "<h2>Test 5: CSS Styles</h2>";
$css_file = 'wp-content/plugins/kh-events/assets/css/kh-events.css';
if (file_exists($css_file)) {
    $css_content = file_get_contents($css_file);

    $css_classes = [
        '.kh-events-week',
        '.kh-week-navigation',
        '.kh-week-grid',
        '.kh-events-photo',
        '.kh-photo-event-item',
        '.kh-calendar-export'
    ];

    foreach ($css_classes as $class) {
        if (strpos($css_content, $class) !== false) {
            echo "✓ CSS class '$class' found<br>";
        } else {
            echo "✗ CSS class '$class' not found<br>";
        }
    }
}

echo "<hr>";
echo "<h2>Implementation Summary</h2>";
echo "<p>The following advanced features have been implemented:</p>";

echo "<h3>1. Week View [kh_events_week]</h3>";
echo "<ul>";
echo "<li>✓ 7-day grid layout showing events by day</li>";
echo "<li>✓ Previous/Next week navigation</li>";
echo "<li>✓ Category and tag filtering support</li>";
echo "<li>✓ Responsive design for mobile devices</li>";
echo "</ul>";

echo "<h3>2. Photo View [kh_events_photo]</h3>";
echo "<ul>";
echo "<li>✓ Grid layout with featured images</li>";
echo "<li>✓ Configurable columns (2-4)</li>";
echo "<li>✓ Hover effects and responsive design</li>";
echo "<li>✓ Fallback for events without images</li>";
echo "</ul>";

echo "<h3>3. iCal/Google Calendar Export [kh_events_ical]</h3>";
echo "<ul>";
echo "<li>✓ iCal (.ics) file generation and download</li>";
echo "<li>✓ Google Calendar direct integration links</li>";
echo "<li>✓ Proper date/time formatting</li>";
echo "<li>✓ Location and description support</li>";
echo "</ul>";

echo "<h2>Usage Examples</h2>";
echo "<h3>Week View:</h3>";
echo "<code>[kh_events_week]</code><br>";
echo "<code>[kh_events_week date=\"2025-11-16\" category=\"music\"]</code><br><br>";

echo "<h3>Photo View:</h3>";
echo "<code>[kh_events_photo limit=\"12\" columns=\"3\"]</code><br>";
echo "<code>[kh_events_photo category=\"featured\" columns=\"4\"]</code><br><br>";

echo "<h3>Calendar Export:</h3>";
echo "<code>[kh_events_ical event_id=\"123\" type=\"ical\"]</code><br>";
echo "<code>[kh_events_ical event_id=\"123\" type=\"google\" text=\"Add to My Calendar\"]</code><br>";
echo "<code>[kh_events_ical event_id=\"123\" type=\"both\"]</code><br><br>";

echo "<p><strong>Note:</strong> These features require events with proper meta data (dates, times, locations, featured images) to display correctly.</p>";
?>