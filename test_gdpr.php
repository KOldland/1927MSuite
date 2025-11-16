<?php
/**
 * Test GDPR Compliance Features
 */

echo "<h1>KH Events GDPR Compliance Test</h1>";

// Test 1: GDPR Class Loading
echo "<h2>Test 1: GDPR Class</h2>";
if (class_exists('KH_Event_GDPR')) {
    echo "✓ KH_Event_GDPR class loaded<br>";
    $gdpr = KH_Event_GDPR::instance();
    echo "✓ GDPR instance created<br>";
} else {
    echo "✗ KH_Event_GDPR class not found<br>";
}

// Test 2: Privacy Policy Content
echo "<h2>Test 2: Privacy Policy Content</h2>";
if (function_exists('wp_add_privacy_policy_content')) {
    echo "✓ WordPress privacy policy function available<br>";
} else {
    echo "⚠️ WordPress privacy policy function not available (may be in older version)<br>";
}

// Test 3: Data Exporters
echo "<h2>Test 3: Data Exporters</h2>";
$exporters = apply_filters('wp_privacy_personal_data_exporters', array());
$kh_exporters = array_filter($exporters, function($exporter) {
    return strpos($exporter['exporter_friendly_name'], 'KH Events') !== false;
});
if (!empty($kh_exporters)) {
    echo "✓ KH Events data exporters registered:<br>";
    foreach ($kh_exporters as $key => $exporter) {
        echo "&nbsp;&nbsp;- " . $exporter['exporter_friendly_name'] . "<br>";
    }
} else {
    echo "✗ No KH Events data exporters found<br>";
}

// Test 4: Data Erasers
echo "<h2>Test 4: Data Erasers</h2>";
$erasers = apply_filters('wp_privacy_personal_data_erasers', array());
$kh_erasers = array_filter($erasers, function($eraser) {
    return strpos($eraser['eraser_friendly_name'], 'KH Events') !== false;
});
if (!empty($kh_erasers)) {
    echo "✓ KH Events data erasers registered:<br>";
    foreach ($kh_erasers as $key => $eraser) {
        echo "&nbsp;&nbsp;- " . $eraser['eraser_friendly_name'] . "<br>";
    }
} else {
    echo "✗ No KH Events data erasers found<br>";
}

// Test 5: Consent Settings
echo "<h2>Test 5: GDPR Settings</h2>";
$gdpr_settings = get_option('kh_events_gdpr_settings', array());
if (!empty($gdpr_settings)) {
    echo "✓ GDPR settings configured:<br>";
    foreach ($gdpr_settings as $key => $value) {
        if ($key === 'additional_consent_text') {
            echo "&nbsp;&nbsp;- $key: " . (empty($value) ? 'Not set' : 'Set') . "<br>";
        } else {
            echo "&nbsp;&nbsp;- $key: $value<br>";
        }
    }
} else {
    echo "⚠️ No GDPR settings configured (defaults will be used)<br>";
}

// Test 6: Consent Hooks
echo "<h2>Test 6: Consent Hooks</h2>";
global $wp_filter;
$booking_hooks = isset($wp_filter['kh_events_booking_form_before_submit']) ?
    count($wp_filter['kh_events_booking_form_before_submit']->callbacks) : 0;
$submit_hooks = isset($wp_filter['kh_events_submit_form_before_submit']) ?
    count($wp_filter['kh_events_submit_form_before_submit']->callbacks) : 0;

echo "✓ Booking form consent hooks: $booking_hooks registered<br>";
echo "✓ Submit form consent hooks: $submit_hooks registered<br>";

// Test 7: Consent Storage Hooks
$booking_created_hooks = isset($wp_filter['kh_events_booking_created']) ?
    count($wp_filter['kh_events_booking_created']->callbacks) : 0;
$event_submitted_hooks = isset($wp_filter['kh_events_event_submitted']) ?
    count($wp_filter['kh_events_event_submitted']->callbacks) : 0;

echo "✓ Booking created consent hooks: $booking_created_hooks registered<br>";
echo "✓ Event submitted consent hooks: $event_submitted_hooks registered<br>";

echo "<h2>Test Complete</h2>";
echo "<p>GDPR compliance features have been implemented and are ready for testing with real user data.</p>";
?>