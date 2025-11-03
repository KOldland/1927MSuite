<?php
/**
 * KHM Marketing Suite Integration for Social Strip
 *
 * This file shows how Social Strip registers with KHM and uses its services
 */

// Wait for KHM to be ready
add_action('khm_marketing_suite_ready', 'kss_register_with_khm');

/**
 * Register Social Strip with KHM Marketing Suite
 */
function kss_register_with_khm() {
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
        add_action('wp_enqueue_scripts', 'kss_enqueue_khm_integration_scripts');
        add_action('wp_ajax_kss_purchase_article', 'kss_handle_article_purchase');
        add_action('wp_ajax_kss_download_with_credit', 'kss_handle_credit_download');
    }
}

/**
 * Enqueue JavaScript for KHM integration
 */
function kss_enqueue_khm_integration_scripts() {
    if (!khm_is_marketing_suite_ready()) {
        return;
    }

    wp_enqueue_script(
        'kss-khm-integration',
        plugin_dir_url(__FILE__) . '../assets/js/khm-integration.js',
        ['jquery', 'kss-social-strip'],
        '1.1',
        true
    );

    // Pass data to JavaScript
    wp_localize_script('kss-khm-integration', 'kssKhm', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('kss_khm_integration'),
        'currentUserId' => get_current_user_id(),
        'messages' => [
            'creditUsed' => __('Credit used successfully!', 'social-strip'),
            'purchaseComplete' => __('Purchase completed!', 'social-strip'),
            'error' => __('Sorry, something went wrong.', 'social-strip')
        ]
    ]);
}

/**
 * Handle article purchase via AJAX
 */
function kss_handle_article_purchase() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);
    $price = floatval($_POST['price'] ?? 0);

    if (!$user_id || !$post_id || $price <= 0) {
        wp_send_json_error('Invalid parameters');
    }

    // Get member pricing
    $pricing = khm_get_member_discount($user_id, $price, 'article');
    $final_price = $pricing['discounted_price'];

    // Create order in KHM
    $order = khm_create_external_order([
        'user_id' => $user_id,
        'total' => $final_price,
        'item_type' => 'article_purchase',
        'item_id' => $post_id,
        'notes' => "Article purchase: " . get_the_title($post_id),
        'meta' => [
            'post_id' => $post_id,
            'original_price' => $price,
            'member_discount' => $pricing['discount_percent']
        ]
    ]);

    if ($order) {
        // Fire custom hook
        do_action('kss_article_purchased', $user_id, $post_id, $order, $final_price);
        
        wp_send_json_success([
            'message' => 'Purchase completed successfully!',
            'order_id' => $order->id,
            'final_price' => $final_price
        ]);
    } else {
        wp_send_json_error('Failed to create order');
    }
}

/**
 * Handle credit download via AJAX
 */
function kss_handle_credit_download() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$user_id || !$post_id) {
        wp_send_json_error('Invalid parameters');
    }

    // Check if user has credits
    $credits = khm_get_user_credits($user_id);
    if ($credits <= 0) {
        wp_send_json_error('No credits available');
    }

    // Use a credit
    $success = khm_use_credit($user_id, 'article_download_' . $post_id);
    
    if ($success) {
        // Fire custom hook
        do_action('kss_credit_used', $user_id, $post_id, 'download');
        
        wp_send_json_success([
            'message' => 'Credit used successfully!',
            'remaining_credits' => $credits - 1
        ]);
    } else {
        wp_send_json_error('Failed to use credit');
    }
}

/**
 * Get enhanced data for Social Strip widget
 */
function kss_get_enhanced_widget_data($post_id, $original_data) {
    $user_id = get_current_user_id();
    
    if (!$user_id || !khm_is_marketing_suite_ready()) {
        return $original_data;
    }

    // Get membership info
    $membership = khm_get_user_membership($user_id);
    $credits = khm_get_user_credits($user_id);
    
    // Get member pricing
    $pricing = khm_get_member_discount($user_id, $original_data['price'], 'article');

    // Enhanced data
    return array_merge($original_data, [
        'user_membership' => $membership,
        'user_credits' => $credits,
        'member_price' => $pricing['discounted_price'],
        'discount_percent' => $pricing['discount_percent'],
        'can_use_credit' => $credits > 0,
        'has_membership' => !is_null($membership)
    ]);
}