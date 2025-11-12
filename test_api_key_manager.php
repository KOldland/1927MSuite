<?php
/**
 * Test API Key Manager Integration
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

if (!function_exists('wp_hash')) {
    function wp_hash($data) {
        return hash('sha256', $data);
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
            'body' => '{"success": true}'
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

// Include the autoloader
require_once ABSPATH . 'wp-content/plugins/khm-seo/src/Core/Autoloader.php';

// Test API Key Manager instantiation
try {
    $api_manager = new KHM_SEO\API\APIKeyManager();
    echo "✓ APIKeyManager instantiated successfully\n";

    // Test setting and getting an API key
    $test_key = 'test-openai-key-12345';
    $api_manager->set_api_key('openai', $test_key);
    $retrieved_key = $api_manager->get_api_key('openai');

    if ($retrieved_key === $test_key) {
        echo "✓ API key storage and retrieval working correctly\n";
    } else {
        echo "✗ API key storage/retrieval failed\n";
    }

    // Test getting all API keys
    $all_keys = $api_manager->get_all_api_keys();
    echo "✓ Retrieved " . count($all_keys) . " API keys\n";

    echo "API Key Manager integration test completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}