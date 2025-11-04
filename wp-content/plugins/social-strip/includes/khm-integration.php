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
        add_action('wp_ajax_kss_direct_pdf_download', 'kss_handle_direct_pdf_download');
        
        // Add PDF download handler for non-logged in users with tokens
        add_action('wp_ajax_nopriv_khm_download_pdf', 'kss_handle_secure_pdf_download');
        add_action('wp_ajax_khm_download_pdf', 'kss_handle_secure_pdf_download');
    }
}

/**
 * Enqueue JavaScript for KHM integration
 */
function kss_enqueue_khm_integration_scripts() {
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
 * Handle credit download via AJAX (Enhanced)
 */
function kss_handle_credit_download() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$user_id || !$post_id) {
        wp_send_json_error('Invalid parameters');
    }

    // Use the enhanced download with credits service
    $result = khm_download_with_credits($post_id, $user_id);
    
    if ($result['success']) {
        // Fire custom hook
        do_action('kss_credit_used', $user_id, $post_id, 'download');
        
        wp_send_json_success([
            'message' => 'Download ready! Credit used successfully.',
            'download_url' => $result['download_url'],
            'remaining_credits' => $result['credits_remaining'],
            'filename' => get_the_title($post_id) . '.pdf'
        ]);
    } else {
        wp_send_json_error($result['error'] ?? 'Failed to process download');
    }
}

/**
 * Handle direct PDF download (for testing/admin)
 */
function kss_handle_direct_pdf_download() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$user_id || !$post_id) {
        wp_send_json_error('Invalid parameters');
    }

    // Generate PDF directly (for purchased articles or admin testing)
    $result = khm_generate_article_pdf($post_id, $user_id);
    
    if ($result['success']) {
        $download_url = khm_create_download_url($post_id, $user_id, 1); // 1 hour expiry
        
        wp_send_json_success([
            'message' => 'PDF generated successfully!',
            'download_url' => $download_url,
            'filename' => $result['filename']
        ]);
    } else {
        wp_send_json_error($result['error'] ?? 'Failed to generate PDF');
    }
}

/**
 * Handle save to library AJAX
 */
add_action('wp_ajax_kss_save_to_library', 'kss_handle_save_to_library');
function kss_handle_save_to_library() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$user_id || !$post_id) {
        wp_send_json_error('Invalid parameters');
    }

    // Use LibraryService if available
    if (function_exists('khm_call_service')) {
        $result = khm_call_service('save_to_library', $user_id, $post_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Article saved to library!',
                'library_count' => khm_call_service('get_library_count', $user_id)
            ]);
        }
    }
    
    wp_send_json_error('Failed to save to library');
}

/**
 * Handle remove from library AJAX
 */
add_action('wp_ajax_kss_remove_from_library', 'kss_handle_remove_from_library');
function kss_handle_remove_from_library() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $post_id = intval($_POST['post_id'] ?? 0);

    if (!$user_id || !$post_id) {
        wp_send_json_error('Invalid parameters');
    }

    // Use LibraryService if available
    if (function_exists('khm_call_service')) {
        $result = khm_call_service('remove_from_library', $user_id, $post_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => 'Article removed from library!',
                'library_count' => khm_call_service('get_library_count', $user_id)
            ]);
        }
    }
    
    wp_send_json_error('Failed to remove from library');
}

/**
 * Handle add to cart AJAX
 */
add_action('wp_ajax_kss_add_to_cart', 'kss_handle_add_to_cart');
function kss_handle_add_to_cart() {
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
add_action('wp_ajax_kss_track_download', 'kss_handle_track_download');
function kss_handle_track_download() {
    check_ajax_referer('kss_khm_integration', 'nonce');

    $user_id = get_current_user_id();
    $download_url = sanitize_url($_POST['download_url'] ?? '');

    if (!$user_id || !$download_url) {
        wp_send_json_error('Invalid parameters');
    }

    // Track the download
    do_action('khm_track_download', $user_id, $download_url);
    
    wp_send_json_success(['message' => 'Download tracked']);
}

/**
 * Handle secure PDF downloads with tokens
 */
function kss_handle_secure_pdf_download() {
    // This uses the PDFService download handler
    $pdf_service = new \KHM\Services\PDFService();
    $pdf_service->handleDownloadRequest($_REQUEST);
}

/**
 * Get enhanced data for Social Strip widget (Updated)
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

    // Enhanced data with credit history
    return array_merge($original_data, [
        'user_membership' => $membership,
        'user_credits' => $credits,
        'member_price' => $pricing['discounted_price'],
        'discount_percent' => $pricing['discount_percent'],
        'can_use_credit' => $credits > 0,
        'has_membership' => !is_null($membership),
        'credit_history' => khm_get_credit_history($user_id, 5) // Last 5 transactions
    ]);
}