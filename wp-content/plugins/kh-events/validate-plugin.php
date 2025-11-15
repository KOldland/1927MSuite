<?php
/**
 * Simple KH Events Plugin Validation
 */

echo "KH Events Plugin Validation\n";
echo "===========================\n\n";

// Check file existence
$files_to_check = array(
    'kh-events.php',
    'includes/class-kh-events.php',
    'includes/class-kh-event-meta.php',
    'includes/class-kh-location-meta.php',
    'includes/class-kh-events-views.php',
    'includes/class-kh-event-tickets.php',
    'includes/class-kh-event-bookings.php',
    'includes/class-kh-recurring-events.php',
    'includes/class-kh-event-filters-widget.php',
    'assets/css/kh-events.css',
    'assets/js/kh-events.js',
    'README.md'
);

echo "Checking file structure...\n";
foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

echo "\nChecking PHP syntax...\n";
$php_files = array(
    'kh-events.php',
    'includes/class-kh-events.php',
    'includes/class-kh-event-meta.php',
    'includes/class-kh-location-meta.php',
    'includes/class-kh-events-views.php',
    'includes/class-kh-event-tickets.php',
    'includes/class-kh-event-bookings.php',
    'includes/class-kh-recurring-events.php',
    'includes/class-kh-event-filters-widget.php'
);

foreach ($php_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $output = shell_exec("php -l \"$path\" 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✓ $file syntax OK\n";
        } else {
            echo "✗ $file syntax error: $output\n";
        }
    }
}

echo "\nChecking for key features...\n";

// Check for post types
$content = file_get_contents(__DIR__ . '/includes/class-kh-events.php');
if (strpos($content, "register_post_type('kh_event'") !== false) {
    echo "✓ Event post type registration found\n";
} else {
    echo "✗ Event post type registration missing\n";
}

if (strpos($content, "register_post_type('kh_location'") !== false) {
    echo "✓ Location post type registration found\n";
} else {
    echo "✗ Location post type registration missing\n";
}

if (strpos($content, "register_post_type('kh_booking'") !== false) {
    echo "✓ Booking post type registration found\n";
} else {
    echo "✗ Booking post type registration missing\n";
}

// Check for taxonomies
if (strpos($content, "register_taxonomy('kh_event_category'") !== false) {
    echo "✓ Event category taxonomy found\n";
} else {
    echo "✗ Event category taxonomy missing\n";
}

if (strpos($content, "register_taxonomy('kh_event_tag'") !== false) {
    echo "✓ Event tag taxonomy found\n";
} else {
    echo "✗ Event tag taxonomy missing\n";
}

// Check for shortcodes
$views_content = file_get_contents(__DIR__ . '/includes/class-kh-events-views.php');
if (strpos($views_content, 'calendar_shortcode') !== false) {
    echo "✓ Calendar shortcode found\n";
} else {
    echo "✗ Calendar shortcode missing\n";
}

if (strpos($views_content, 'list_shortcode') !== false) {
    echo "✓ List shortcode found\n";
} else {
    echo "✗ List shortcode missing\n";
}

if (strpos($views_content, 'day_shortcode') !== false) {
    echo "✓ Day shortcode found\n";
} else {
    echo "✗ Day shortcode missing\n";
}

// Check for widget
$widget_content = file_get_contents(__DIR__ . '/includes/class-kh-event-filters-widget.php');
if (strpos($widget_content, 'class KH_Event_Filters_Widget') !== false) {
    echo "✓ Event filters widget found\n";
} else {
    echo "✗ Event filters widget missing\n";
}

// Check for recurring events
$recurring_content = file_get_contents(__DIR__ . '/includes/class-kh-recurring-events.php');
if (strpos($recurring_content, 'class KH_Recurring_Events') !== false) {
    echo "✓ Recurring events class found\n";
} else {
    echo "✗ Recurring events class missing\n";
}

echo "\nValidation complete!\n";
echo "\nNext recommended steps:\n";
echo "1. Test plugin activation in WordPress environment\n";
echo "2. Create sample events and test shortcodes\n";
echo "3. Test AJAX calendar navigation\n";
echo "4. Test booking system functionality\n";
echo "5. Add admin settings page\n";
echo "6. Implement payment integration\n";
echo "7. Add email notifications\n";
echo "8. Test integration with other 1927MSuite plugins\n";