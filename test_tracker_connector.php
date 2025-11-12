<?php
/**
 * Test Tracker Connector Integration
 */

// Define WordPress constants for testing
define('ABSPATH', '/Users/krisoldland/Documents/GitHub/1927MSuite/');
define('WPINC', 'wp-includes');
define('KHM_SEO_PLUGIN_DIR', '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-seo/');
define('KHM_SEO_PLUGIN_URL', 'https://example.com/wp-content/plugins/khm-seo/');
define('KHM_SEO_VERSION', '1.0.0');

// Mock WordPress functions
$mock_options = [];

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $mock_options;
        return isset($mock_options[$option]) ? $mock_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $mock_options;
        $mock_options[$option] = $value;
        return true;
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12) {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback) {
        echo "Hook registered: {$hook}\n";
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback) {
        echo "Filter registered: {$hook}\n";
    }
}

if (!function_exists('wp_ajax_')) {
    function wp_ajax_($action) {
        // Mock AJAX registration
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'mock_nonce_' . $action;
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return strpos($nonce, 'mock_nonce_') === 0;
    }
}

if (!function_exists('wp_die')) {
    function wp_die($message) {
        die($message);
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability) {
        return true; // Mock admin user
    }
}

if (!function_exists('admin_url')) {
    function admin_url($path = '') {
        return 'http://example.com/wp-admin/' . $path;
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script($handle, $src = '', $deps = array(), $ver = false, $in_footer = false) {
        echo "Script enqueued: {$handle}\n";
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        echo "Style enqueued: {$handle}\n";
    }
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script($handle, $object_name, $l10n) {
        echo "Script localized: {$handle}\n";
    }
}

if (!function_exists('wp_remote_request')) {
    function wp_remote_request($url, $args = array()) {
        return [
            'response' => ['code' => 200],
            'body' => '{"keys":[{"kid":"test","kty":"RSA","use":"sig","n":"test","e":"AQAB"}]}'
        ];
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url, $args = array()) {
        return [
            'response' => ['code' => 200],
            'body' => '{"keys":[{"kid":"test","kty":"RSA","use":"sig","n":"test","e":"AQAB"}]}'
        ];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'];
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = false) {
        return time();
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw($url) {
        return filter_var($url, FILTER_SANITIZE_URL);
    }
}

// Include the autoloader
require_once ABSPATH . 'wp-content/plugins/khm-seo/src/Core/Autoloader.php';

// Test Tracker Connector instantiation
try {
    $tracker = new KHM_SEO\API\TrackerConnector();
    echo "✓ TrackerConnector instantiated successfully\n";

    // Test setting and getting tracker settings
    $tracker->set_tracker_setting('tracker_url', 'https://tracker.example.com');
    $tracker->set_tracker_setting('client_id', 'test-client-123');

    $url = $tracker->get_tracker_setting('tracker_url');
    $client_id = $tracker->get_tracker_setting('client_id');

    if ($url === 'https://tracker.example.com' && $client_id === 'test-client-123') {
        echo "✓ Tracker settings storage and retrieval working correctly\n";
    } else {
        echo "✗ Tracker settings storage/retrieval failed\n";
    }

    // Test RSA key generation
    $keys = $tracker->generate_rsa_keys();
    if (isset($keys['public_key']) && isset($keys['private_key'])) {
        echo "✓ RSA keypair generation working correctly\n";

        // Test setting keys
        $tracker->set_tracker_setting('jwt_public_key', $keys['public_key']);
        $tracker->set_tracker_setting('jwt_private_key', $keys['private_key']);

        $retrieved_public = $tracker->get_tracker_setting('jwt_public_key');
        $retrieved_private = $tracker->get_tracker_setting('jwt_private_key');

        if ($retrieved_public === $keys['public_key'] && $retrieved_private === $keys['private_key']) {
            echo "✓ JWT key encryption/decryption working correctly\n";
        } else {
            echo "✗ JWT key encryption/decryption failed\n";
        }
    } else {
        echo "✗ RSA keypair generation failed\n";
    }

    echo "Tracker Connector integration test completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}