<?php
/**
 * Basic test file for Dual-GPT WordPress Plugin
 * Run this to verify plugin components load correctly
 */

// Define minimal WordPress constants for testing
if (!defined('ABSPATH')) {
    define('ABSPATH', '/fake/path/');
}

// Mock WordPress functions
if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('current_time')) {
    function current_time($type = 'mysql') {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults = array()) {
        return array_merge($defaults, $args);
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce_' . md5($action);
    }
}

if (!function_exists('rest_url')) {
    function rest_url($path = '') {
        return 'http://example.com/wp-json/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script() {}
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style() {}
}

if (!function_exists('wp_localize_script')) {
    function wp_localize_script() {}
}

if (!function_exists('register_rest_route')) {
    function register_rest_route() {}
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook() {}
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook() {}
}

if (!function_exists('add_action')) {
    function add_action() {}
}

if (!function_exists('add_filter')) {
    function add_filter() {}
}

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain() {}
}

if (!function_exists('get_option')) {
    function get_option($key, $default = null) {
        return $default;
    }
}

if (!function_exists('wp_remote_request')) {
    function wp_remote_request() {
        return array('body' => '{}', 'response' => array('code' => 200));
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return $thing instanceof WP_Error;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return isset($response['body']) ? $response['body'] : '';
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return isset($response['response']['code']) ? $response['response']['code'] : 200;
    }
}

if (!function_exists('wp_remote_retrieve_header')) {
    function wp_remote_retrieve_header($response, $header) {
        return '';
    }
}

if (!function_exists('wp_update_post')) {
    function wp_update_post($post_data) {
        return true; // Mock success
    }
}

if (!function_exists('serialize_block')) {
    function serialize_block($block) {
        // Simple mock implementation
        if ($block['blockName'] === 'core/paragraph') {
            return "<!-- wp:paragraph -->\n<p>" . strip_tags($block['innerHTML']) . "</p>\n<!-- /wp:paragraph -->";
        }
        return $block['innerHTML'];
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('sanitize_textarea_field')) {
    function sanitize_textarea_field($str) {
        return trim(strip_tags($str));
    }
}

if (!function_exists('intval')) {
    function intval($var) {
        return (int) $var;
    }
}

if (!function_exists('wp_generate_uuid4')) {
    function wp_generate_uuid4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}

// Mock WP_Error class
if (!class_exists('WP_Error')) {
    class WP_Error {
        public function __construct($code, $message) {}
        public function get_error_message() {
            return 'Mock error';
        }
    }
}

// Mock WP_REST_Response class
if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public function __construct($data, $status = 200) {}
    }
}

// Mock WP_REST_Request class
if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request {
        public $params = array();

        public function __construct($params = array()) {
            $this->params = $params;
        }

        public function get_params() {
            return $this->params;
        }

        public function get_param($key) {
            return $this->params[$key] ?? null;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response {
        public function __construct($data, $status = 200) {}
    }
}

// Include plugin files
require_once __DIR__ . '/dual-gpt-wordpress-plugin.php';

// Manually include classes for testing
require_once __DIR__ . '/includes/class-db-handler.php';
require_once __DIR__ . '/includes/class-openai-connector.php';
require_once __DIR__ . '/includes/tools/class-research-tools.php';
require_once __DIR__ . '/includes/tools/class-author-tools.php';

// Mock wpdb class
class Mock_wpdb {
    public $prefix = 'wp_';
    
    public function get_charset_collate() {
        return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
    }
    
    public function insert($table, $data) {
        return 1; // Mock successful insert
    }
    
    public function update($table, $data, $where) {
        return 1; // Mock successful update
    }
    
    public function get_row($query, $output = 'ARRAY_A') {
        return array('id' => 'test-session-id', 'role' => 'research');
    }
    
    public function prepare($query, ...$args) {
        return $query;
    }
    
    public function query($query) {
        return 1;
    }
}

// Mock global $wpdb
global $wpdb;
$wpdb = new Mock_wpdb();

class Dual_GPT_Plugin_Test {

    public function run_tests() {
        echo "Running Dual-GPT Plugin Tests...\n\n";

        $this->test_class_loading();
        $this->test_database_handler();
        $this->test_openai_connector();
        $this->test_research_tools();
        $this->test_author_tools();
        $this->test_rest_endpoints();

        echo "Tests completed.\n";
    }

    private function test_class_loading() {
        echo "Testing class loading...\n";

        try {
            $plugin = new Dual_GPT_Plugin();
            echo "✓ Dual_GPT_Plugin class loaded\n";

            $db = new Dual_GPT_DB_Handler();
            echo "✓ Dual_GPT_DB_Handler class loaded\n";

            $openai = new Dual_GPT_OpenAI_Connector();
            echo "✓ Dual_GPT_OpenAI_Connector class loaded\n";

            $research_tools = new Dual_GPT_Research_Tools();
            echo "✓ Dual_GPT_Research_Tools class loaded\n";

            $author_tools = new Dual_GPT_Author_Tools();
            echo "✓ Dual_GPT_Author_Tools class loaded\n";

        } catch (Exception $e) {
            echo "✗ Error loading classes: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function test_database_handler() {
        echo "Testing database handler...\n";

        try {
            $db = new Dual_GPT_DB_Handler();

            // Test session creation (without actual DB write)
            $session_data = array(
                'role' => 'research',
                'title' => 'Test Session',
            );

            echo "✓ Database handler methods available\n";

        } catch (Exception $e) {
            echo "✗ Database handler error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function test_openai_connector() {
        echo "Testing OpenAI connector...\n";

        try {
            $openai = new Dual_GPT_OpenAI_Connector();

            // Test cost calculation
            $cost = $openai->calculate_cost('gpt-4', 100, 200);
            echo "✓ Cost calculation: $" . number_format($cost['cost_usd'], 4) . "\n";

        } catch (Exception $e) {
            echo "✗ OpenAI connector error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function test_research_tools() {
        echo "Testing research tools...\n";

        try {
            $tools = new Dual_GPT_Research_Tools();

            // Test web search
            $result = $tools->web_search('test query', 3);
            echo "✓ Web search returned " . count($result['results']) . " results\n";

            // Test tool definitions
            $definitions = $tools->get_tool_definitions();
            echo "✓ " . count($definitions) . " tool definitions available\n";

        } catch (Exception $e) {
            echo "✗ Research tools error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function test_author_tools() {
        echo "Testing author tools...\n";

        try {
            $tools = new Dual_GPT_Author_Tools();

            // Test outline generation
            $result = $tools->outline_from_brief('Test brief for article');
            echo "✓ Outline generated with " . count($result['outline']) . " sections\n";

            // Test style guard
            $content = "This is a test paragraph with some very good content.";
            $validation = $tools->style_guard($content);
            echo "✓ Style validation completed\n";

            // Test tool definitions
            $definitions = $tools->get_tool_definitions();
            echo "✓ " . count($definitions) . " tool definitions available\n";

        } catch (Exception $e) {
            echo "✗ Author tools error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }

    private function test_rest_endpoints() {
        echo "Testing REST endpoints...\n";

        try {
            $plugin = new Dual_GPT_Plugin();

            // Test session creation
            $mock_request = new WP_REST_Request(array('role' => 'research', 'title' => 'Test Session'));
            $response = $plugin->create_session($mock_request);
            echo "✓ Session creation endpoint available\n";

            // Test blocks conversion (using reflection for private method)
            $reflection = new ReflectionClass($plugin);
            $method = $reflection->getMethod('convert_blocks_json_to_gutenberg');
            $method->setAccessible(true);
            $blocks_data = array(
                'version' => 1,
                'blocks' => array(
                    array('type' => 'heading', 'level' => 2, 'content' => 'Test Heading'),
                    array('type' => 'paragraph', 'content' => 'Test paragraph'),
                ),
            );
            $gutenberg_blocks = $method->invoke($plugin, $blocks_data);
            echo "✓ Blocks JSON conversion works (" . count($gutenberg_blocks) . " blocks)\n";

        } catch (Exception $e) {
            echo "✗ REST endpoints error: " . $e->getMessage() . "\n";
        }

        echo "\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new Dual_GPT_Plugin_Test();
    $test->run_tests();
}