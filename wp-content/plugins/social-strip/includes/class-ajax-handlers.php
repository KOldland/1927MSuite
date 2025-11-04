<?php
/**
 * AJAX Handlers for Social Strip
 *
 * This class handles all AJAX requests for the Social Strip plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class KSS_Ajax_Handlers {

    private $khm_integration;

    /**
     * Constructor
     */
    public function __construct($khm_integration = null) {
        $this->khm_integration = $khm_integration;
        $this->init_ajax_handlers();
    }

    /**
     * Initialize AJAX handlers
     */
    private function init_ajax_handlers() {
        // Only add handlers if KHM integration is not already handling them
        if (!$this->khm_integration) {
            // Basic AJAX handlers for when KHM integration is not available
            add_action('wp_ajax_kss_basic_action', [$this, 'handle_basic_action']);
            add_action('wp_ajax_nopriv_kss_basic_action', [$this, 'handle_basic_action']);
        }

        // General plugin AJAX handlers (not KHM specific)
        add_action('wp_ajax_kss_get_post_data', [$this, 'handle_get_post_data']);
        add_action('wp_ajax_nopriv_kss_get_post_data', [$this, 'handle_get_post_data']);
        
        add_action('wp_ajax_kss_update_settings', [$this, 'handle_update_settings']);
        
        // Affiliate tracking handlers
        add_action('wp_ajax_kss_track_affiliate_click', [$this, 'handle_affiliate_click']);
        add_action('wp_ajax_nopriv_kss_track_affiliate_click', [$this, 'handle_affiliate_click']);
    }

    /**
     * Handle basic action AJAX (fallback when KHM not available)
     */
    public function handle_basic_action() {
        check_ajax_referer('kss_basic_nonce', 'nonce');
        
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');
        
        switch ($action_type) {
            case 'share':
                $this->handle_basic_share();
                break;
            case 'info':
                $this->handle_basic_info();
                break;
            default:
                wp_send_json_error('Unknown action type');
        }
    }

    /**
     * Handle basic share functionality
     */
    private function handle_basic_share() {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        wp_send_json_success([
            'title' => $post->post_title,
            'url' => get_permalink($post_id),
            'excerpt' => wp_trim_words($post->post_content, 20)
        ]);
    }

    /**
     * Handle basic info request
     */
    private function handle_basic_info() {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        wp_send_json_success([
            'khm_available' => function_exists('khm_is_marketing_suite_ready') && khm_is_marketing_suite_ready(),
            'user_logged_in' => is_user_logged_in(),
            'post_id' => $post_id
        ]);
    }

    /**
     * Handle get post data AJAX
     */
    public function handle_get_post_data() {
        $post_id = intval($_POST['post_id'] ?? $_GET['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $response_data = [
            'id' => $post_id,
            'title' => $post->post_title,
            'excerpt' => wp_trim_words($post->post_content, 30),
            'url' => get_permalink($post_id),
            'author' => get_the_author_meta('display_name', $post->post_author),
            'date' => get_the_date('F j, Y', $post_id),
            'featured_image' => get_the_post_thumbnail_url($post_id, 'medium')
        ];

        // Add KHM-specific data if available
        if (function_exists('kss_get_enhanced_widget_data')) {
            $enhanced_data = kss_get_enhanced_widget_data($post_id);
            $response_data = array_merge($response_data, $enhanced_data);
        }

        wp_send_json_success($response_data);
    }

    /**
     * Handle settings update AJAX
     */
    public function handle_update_settings() {
        check_ajax_referer('kss_settings_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $settings = $_POST['settings'] ?? [];
        
        // Sanitize settings
        $sanitized_settings = [];
        foreach ($settings as $key => $value) {
            $sanitized_key = sanitize_key($key);
            $sanitized_value = sanitize_text_field($value);
            $sanitized_settings[$sanitized_key] = $sanitized_value;
        }

        // Save settings
        $saved = update_option('kss_settings', $sanitized_settings);
        
        if ($saved) {
            wp_send_json_success(['message' => 'Settings saved successfully']);
        } else {
            wp_send_json_error('Failed to save settings');
        }
    }

    /**
     * Handle affiliate click tracking
     */
    public function handle_affiliate_click() {
        $post_id = intval($_POST['post_id'] ?? 0);
        $affiliate_id = sanitize_text_field($_POST['affiliate_id'] ?? '');
        $click_type = sanitize_text_field($_POST['click_type'] ?? 'general');
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        // Track the affiliate click
        $tracking_data = [
            'post_id' => $post_id,
            'affiliate_id' => $affiliate_id,
            'click_type' => $click_type,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => current_time('mysql'),
            'referrer' => wp_get_referer()
        ];

        // Fire action for affiliate tracking systems
        do_action('kss_affiliate_click_tracked', $tracking_data);

        // Save to database if tracking table exists
        global $wpdb;
        $table_name = $wpdb->prefix . 'kss_affiliate_clicks';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $wpdb->insert($table_name, $tracking_data);
        }

        wp_send_json_success(['message' => 'Click tracked']);
    }

    /**
     * Get settings for frontend
     */
    public static function get_frontend_settings() {
        $settings = get_option('kss_settings', []);
        
        return [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kss_basic_nonce'),
            'settings_nonce' => wp_create_nonce('kss_settings_nonce'),
            'user_logged_in' => is_user_logged_in(),
            'current_user_id' => get_current_user_id(),
            'khm_available' => function_exists('khm_is_marketing_suite_ready') && khm_is_marketing_suite_ready(),
            'plugin_settings' => $settings
        ];
    }
}

// Enqueue settings for JavaScript
add_action('wp_enqueue_scripts', function() {
    if (is_admin()) return;
    
    wp_localize_script('kss-social-strip', 'kss_ajax', KSS_Ajax_Handlers::get_frontend_settings());
});