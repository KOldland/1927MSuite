<?php
/**
 * KHM Marketing Suite Integration for Social Strip
 *
 * This file provides class-based integration between Social Strip and KHM Marketing Suite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * KSS KHM Integration Class
 * 
 * Handles the integration between Social Strip and KHM Marketing Suite
 */
class KSS_KHM_Integration {

    /**
     * Constructor - Initialize the integration
     */
    public function __construct() {
        // Wait for KHM to be ready
        add_action('khm_marketing_suite_ready', [$this, 'register_with_khm']);
    }

    /**
     * Register Social Strip with KHM Marketing Suite
     */
    public function register_with_khm() {
        if (!function_exists('khm_register_plugin')) {
            return;
        }

        $success = khm_register_plugin('social-strip', [
            'name' => 'Social Strip',
            'version' => '1.1',
            'description' => 'Primary member interface for article downloads and purchases',
            'capabilities' => [
                'article_purchases',
                'credit_downloads', 
                'member_pricing',
                'frontend_ui'
            ],
            'services_used' => [
                'get_user_membership',
                'get_member_discount', 
                'get_user_credits',
                'use_credit',
                'create_order'
            ],
            'hooks_provided' => [
                'kss_article_downloaded',
                'kss_article_purchased',
                'kss_credit_used'
            ]
        ]);

        if ($success) {
            error_log('Social Strip successfully registered with KHM Marketing Suite');
            
            // Initialize Social Strip features that require KHM
            add_action('wp_enqueue_scripts', [$this, 'enqueue_khm_integration_scripts']);
            add_action('wp_ajax_kss_purchase_article', [$this, 'handle_article_purchase']);
            add_action('wp_ajax_kss_download_with_credit', [$this, 'handle_credit_download']);
            add_action('wp_ajax_kss_direct_pdf_download', [$this, 'handle_direct_pdf_download']);
            
            // Gift functionality AJAX handlers
            add_action('wp_ajax_kss_send_gift', [$this, 'handle_send_gift']);
            add_action('wp_ajax_kss_get_gift_data', [$this, 'handle_get_gift_data']);
            
            // Add PDF download handler for non-logged in users with tokens
            add_action('wp_ajax_nopriv_khm_download_pdf', [$this, 'handle_secure_pdf_download']);
            add_action('wp_ajax_khm_download_pdf', [$this, 'handle_secure_pdf_download']);
            
            // Gift redemption handlers (both logged in and anonymous)
            add_action('wp_ajax_kss_redeem_gift', [$this, 'handle_gift_redemption']);
            add_action('wp_ajax_nopriv_kss_redeem_gift', [$this, 'handle_gift_redemption']);
        }
    }

    /**
     * Enqueue JavaScript for KHM integration
     */
    public function enqueue_khm_integration_scripts() {
        if (!khm_is_marketing_suite_ready()) {
            return;
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'kss-khm-integration',
            plugin_dir_url(__FILE__) . '../assets/js/khm-integration.js',
            ['jquery', 'kss-social-strip'],
            '1.1',
            true
        );

        // Enqueue CSS
        wp_enqueue_style(
            'kss-khm-integration',
            plugin_dir_url(__FILE__) . '../assets/css/khm-integration.css',
            ['kss-social-strip'],
            '1.1'
        );

        // Pass data to JavaScript
        wp_localize_script('kss-khm-integration', 'khm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kss_khm_integration'),
            'current_user_id' => get_current_user_id(),
            'gift_url' => home_url('/gift/'),
            'checkout_url' => home_url('/checkout/'),
            'messages' => [
                'credit_used' => __('Credit used successfully!', 'social-strip'),
                'purchase_complete' => __('Purchase completed!', 'social-strip'),
                'saved_to_library' => __('Saved to library!', 'social-strip'),
                'removed_from_library' => __('Removed from library!', 'social-strip'),
                'added_to_cart' => __('Added to cart!', 'social-strip'),
                'error' => __('Sorry, something went wrong.', 'social-strip'),
                'login_required' => __('Please log in to use this feature.', 'social-strip'),
                'insufficient_credits' => __('You don\'t have enough credits.', 'social-strip')
            ]
        ]);
    }

    /**
     * Handle article purchase AJAX
     */
    public function handle_article_purchase() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$user_id || !$post_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Use ECommerceService if available
        if (function_exists('khm_call_service')) {
            $order_data = [
                'user_id' => $user_id,
                'items' => [
                    [
                        'type' => 'article',
                        'id' => $post_id,
                        'quantity' => 1
                    ]
                ],
                'notes' => "Article purchase: " . get_the_title($post_id),
                'source' => 'social_strip_button'
            ];

            $result = khm_call_service('create_order', $order_data);
            
            if ($result && isset($result['order_id'])) {
                // Fire custom hook for tracking
                do_action('kss_article_purchased', $post_id, $user_id, $result['order_id']);
                
                wp_send_json_success([
                    'message' => 'Purchase completed!',
                    'order_id' => $result['order_id'],
                    'download_url' => $result['download_url'] ?? null
                ]);
            }
        }
        
        wp_send_json_error('Failed to create order');
    }

    /**
     * Handle credit-based download AJAX
     */
    public function handle_credit_download() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$user_id || !$post_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Use download with credits function
        if (function_exists('khm_download_with_credits')) {
            $result = khm_download_with_credits($post_id, $user_id);
            
            if ($result['success']) {
                // Fire custom hook for tracking
                do_action('kss_credit_used', $post_id, $user_id);
                
                wp_send_json_success([
                    'message' => 'Credit used successfully!',
                    'download_url' => $result['download_url'],
                    'credits_remaining' => $result['credits_remaining'],
                    'filename' => get_the_title($post_id) . '.pdf'
                ]);
            }
        }
        
        wp_send_json_error($result['error'] ?? 'Failed to process download');
    }

    /**
     * Handle direct PDF download AJAX
     */
    public function handle_direct_pdf_download() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$user_id || !$post_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Generate PDF for purchased articles
        if (function_exists('khm_generate_article_pdf')) {
            $result = khm_generate_article_pdf($post_id, $user_id);
            
            if ($result['success']) {
                wp_send_json_success([
                    'message' => 'PDF generated successfully!',
                    'download_url' => $result['download_url'],
                    'filename' => $result['filename']
                ]);
            }
        }
        
        wp_send_json_error($result['error'] ?? 'Failed to generate PDF');
    }

    /**
     * Handle save to library AJAX
     */
    public function handle_save_to_library() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$user_id || !$post_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Use library service if available
        if (function_exists('khm_call_service')) {
            $result = khm_call_service('save_to_library', $user_id, $post_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Saved to library!',
                    'is_saved' => true
                ]);
            }
        }
        
        wp_send_json_error('Failed to save to library');
    }

    /**
     * Handle remove from library AJAX
     */
    public function handle_remove_from_library() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$user_id || !$post_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Use library service if available
        if (function_exists('khm_call_service')) {
            $result = khm_call_service('remove_from_library', $user_id, $post_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Removed from library!',
                    'is_saved' => false
                ]);
            }
        }
        
        wp_send_json_error('Failed to remove from library');
    }

    /**
     * Handle add to cart AJAX
     */
    public function handle_add_to_cart() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$user_id || !$post_id) {
            wp_send_json_error('Invalid parameters');
        }

        // Use ECommerceService if available
        if (function_exists('khm_call_service')) {
            $result = khm_call_service('add_to_cart', $user_id, $post_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Article added to cart!',
                    'cart_count' => khm_call_service('get_cart_count', $user_id),
                    'redirect_url' => home_url('/checkout/')
                ]);
            }
        }
        
        wp_send_json_error('Failed to add to cart');
    }

    /**
     * Handle download tracking AJAX
     */
    public function handle_track_download() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        $download_url = sanitize_url($_POST['download_url'] ?? '');

        if (!$user_id || !$download_url) {
            wp_send_json_error('Invalid parameters');
        }

        // Track the download (you can implement analytics here)
        do_action('kss_article_downloaded', $user_id, $download_url);
        
        wp_send_json_success(['message' => 'Download tracked']);
    }

    /**
     * Get enhanced widget data for social strip
     * This method provides data for all 5 buttons: Download, Save, Buy, Gift, Share
     */
    public function get_enhanced_widget_data($post_id, $original_data = []) {
        $user_id = get_current_user_id();
        $post = get_post($post_id);
        
        if (!$post) {
            return $original_data;
        }

        $enhanced_data = [
            'post_id' => $post_id,
            'is_logged_in' => $user_id > 0,
            'icon_base' => plugin_dir_url(__FILE__) . '../assets/img/',
        ];

        // Get user membership info
        if ($user_id && function_exists('khm_get_user_membership')) {
            $membership = khm_get_user_membership($user_id);
            $enhanced_data['membership'] = [
                'is_member' => $membership ? true : false,
                'level' => $membership->level_name ?? 'Guest',
                'expires' => $membership->expires_at ?? null
            ];
        } else {
            $enhanced_data['membership'] = [
                'is_member' => false,
                'level' => 'Guest',
                'expires' => null
            ];
        }

        // Get credits info
        if ($user_id && function_exists('khm_get_user_credits')) {
            $credits = khm_get_user_credits($user_id);
            $enhanced_data['credits'] = [
                'available' => $credits,
                'can_download' => $credits > 0
            ];
        } else {
            $enhanced_data['credits'] = [
                'available' => 0,
                'can_download' => false
            ];
        }

        // Get library status
        if ($user_id && function_exists('khm_call_service')) {
            $is_saved = khm_call_service('is_saved_to_library', $user_id, $post_id);
            $enhanced_data['library'] = [
                'is_saved' => $is_saved
            ];
        } else {
            $enhanced_data['library'] = [
                'is_saved' => false
            ];
        }

        // Get pricing info
        if (function_exists('khm_call_service')) {
            $pricing = khm_call_service('get_article_pricing', $post_id, $user_id);
            $enhanced_data['pricing'] = [
                'regular_price' => $pricing['regular_price'] ?? 5.99,
                'member_price' => $pricing['member_price'] ?? null,
                'currency' => '$',
                'is_purchasable' => $pricing['is_purchasable'] ?? true
            ];
        } else {
            $enhanced_data['pricing'] = [
                'regular_price' => 5.99,
                'member_price' => null,
                'currency' => '$',
                'is_purchasable' => true
            ];
        }

        // Share data
        $enhanced_data['share'] = [
            'title' => $post->post_title,
            'url' => get_permalink($post_id),
            'excerpt' => wp_trim_words($post->post_content, 20)
        ];

        return array_merge($original_data, $enhanced_data);
    }

    /**
     * Handle send gift AJAX
     */
    public function handle_send_gift() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Login required to send gifts');
        }

        // Validate required fields
        $required_fields = ['post_id', 'recipient_name', 'recipient_email', 'personal_message'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("Missing required field: {$field}");
            }
        }

        try {
            if (function_exists('khm_call_service')) {
                // Create gift data
                $gift_data = [
                    'post_id' => intval($_POST['post_id']),
                    'sender_id' => $user_id,
                    'recipient_name' => sanitize_text_field($_POST['recipient_name']),
                    'recipient_email' => sanitize_email($_POST['recipient_email']),
                    'personal_message' => sanitize_textarea_field($_POST['personal_message']),
                    'sender_name' => wp_get_current_user()->display_name,
                    'sender_email' => wp_get_current_user()->user_email,
                    'gift_type' => sanitize_text_field($_POST['gift_type'] ?? 'article_access'),
                    'include_pdf' => isset($_POST['include_pdf']) ? (bool)$_POST['include_pdf'] : true,
                    'save_to_library' => isset($_POST['save_to_library']) ? (bool)$_POST['save_to_library'] : true
                ];

                $result = khm_call_service('create_gift', $gift_data);
                
                if ($result && $result['success']) {
                    // Send gift email
                    $email_result = khm_call_service('send_gift_email', $result['gift_token'], $gift_data);
                    
                    if ($email_result && $email_result['success']) {
                        do_action('kss_gift_sent', $gift_data['post_id'], $user_id, $result['gift_token']);
                        
                        wp_send_json_success([
                            'message' => 'Gift sent successfully!',
                            'gift_token' => $result['gift_token']
                        ]);
                    } else {
                        wp_send_json_error('Gift created but email failed: ' . $email_result['error']);
                    }
                } else {
                    wp_send_json_error($result['error'] ?? 'Failed to create gift');
                }
            } else {
                wp_send_json_error('Failed to process gift request');
            }
        } catch (Exception $e) {
            wp_send_json_error('Gift service not available');
        }
    }

    /**
     * Handle get gift data AJAX
     */
    public function handle_get_gift_data() {
        check_ajax_referer('kss_khm_integration', 'nonce');

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Login required');
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        // Get pricing for gift
        if (function_exists('khm_call_service')) {
            $pricing = khm_call_service('get_article_pricing', $post_id, $user_id);
            
            wp_send_json_success([
                'post_title' => $post->post_title,
                'post_excerpt' => wp_trim_words($post->post_content, 30),
                'post_url' => get_permalink($post_id),
                'pricing' => $pricing,
                'sender_name' => wp_get_current_user()->display_name,
                'sender_email' => wp_get_current_user()->user_email
            ]);
        } else {
            wp_send_json_error('Pricing service not available');
        }
    }

    /**
     * Handle secure PDF download with token
     */
    public function handle_secure_pdf_download() {
        $token = sanitize_text_field($_GET['token'] ?? '');
        
        if (!$token) {
            wp_die('Invalid download token');
        }

        // Verify and process the download token
        if (function_exists('khm_call_service')) {
            $result = khm_call_service('process_download_token', $token);
            
            if ($result && $result['success']) {
                // Redirect to the actual PDF file
                wp_redirect($result['file_url']);
                exit;
            }
        }
        
        wp_die('Invalid or expired download link');
    }

    /**
     * Handle gift redemption AJAX
     */
    public function handle_gift_redemption() {
        $token = sanitize_text_field($_POST['token'] ?? $_GET['token'] ?? '');
        $redemption_type = sanitize_text_field($_POST['redemption_type'] ?? 'view_online');
        $user_id = get_current_user_id(); // May be 0 for anonymous users

        if (!$token) {
            wp_send_json_error('Invalid gift token');
        }

        try {
            $gift_service = new KHM\Services\GiftService(
                new KHM\Services\MembershipRepository(),
                new KHM\Services\OrderRepository(),
                new KHM\Services\EmailService(__DIR__ . '/../../khm-plugin/')
            );

            $result = $gift_service->redeem_gift($token, $redemption_type, $user_id);

            if ($result['success']) {
                wp_send_json_success([
                    'message' => 'Gift redeemed successfully!',
                    'download_url' => $result['download_url'] ?? null,
                    'filename' => $result['filename'] ?? null,
                    'saved_to_library' => $result['saved_to_library'] ?? false,
                    'redemption_type' => $result['redemption_type']
                ]);
            } else {
                wp_send_json_error($result['error'] ?? 'Failed to redeem gift');
            }

        } catch (Exception $e) {
            error_log('Gift redemption error: ' . $e->getMessage());
            wp_send_json_error('Failed to process redemption');
        }
    }
}

// Backward compatibility - maintain the original functions
if (!function_exists('kss_get_enhanced_widget_data')) {
    function kss_get_enhanced_widget_data($post_id, $original_data = []) {
        static $integration_instance = null;
        
        if ($integration_instance === null) {
            $integration_instance = new KSS_KHM_Integration();
        }
        
        return $integration_instance->get_enhanced_widget_data($post_id, $original_data);
    }
}

// Add AJAX handlers for backward compatibility
add_action('wp_ajax_kss_save_to_library', function() {
    static $integration_instance = null;
    if ($integration_instance === null) {
        $integration_instance = new KSS_KHM_Integration();
    }
    $integration_instance->handle_save_to_library();
});

add_action('wp_ajax_kss_remove_from_library', function() {
    static $integration_instance = null;
    if ($integration_instance === null) {
        $integration_instance = new KSS_KHM_Integration();
    }
    $integration_instance->handle_remove_from_library();
});

add_action('wp_ajax_kss_add_to_cart', function() {
    static $integration_instance = null;
    if ($integration_instance === null) {
        $integration_instance = new KSS_KHM_Integration();
    }
    $integration_instance->handle_add_to_cart();
});

add_action('wp_ajax_kss_track_download', function() {
    static $integration_instance = null;
    if ($integration_instance === null) {
        $integration_instance = new KSS_KHM_Integration();
    }
    $integration_instance->handle_track_download();
});