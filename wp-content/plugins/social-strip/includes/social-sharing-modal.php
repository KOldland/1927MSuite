<?php
/**
 * Social Sharing Modal functionality
 *
 * This file provides the unified social sharing modal for the Social Strip plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add unified modal to footer
 */
function kss_add_unified_modal_to_footer() {
    add_action('wp_footer', 'kss_render_unified_modal');
}

/**
 * Render the unified social sharing modal
 */
function kss_render_unified_modal() {
    if (is_admin()) {
        return;
    }
    ?>
    <div id="kss-unified-modal" class="kss-modal-overlay" style="display: none;">
        <div class="kss-modal-container">
            <div class="kss-modal-header">
                <h3 class="kss-modal-title">Share This Article</h3>
                <button class="kss-modal-close" type="button">&times;</button>
            </div>
            
            <div class="kss-modal-content">
                <div class="kss-modal-section">
                    <h4>Social Media</h4>
                    <div class="kss-social-buttons">
                        <button class="kss-share-btn kss-facebook" data-platform="facebook">
                            <span class="kss-icon">📘</span> Facebook
                        </button>
                        <button class="kss-share-btn kss-twitter" data-platform="twitter">
                            <span class="kss-icon">🐦</span> Twitter
                        </button>
                        <button class="kss-share-btn kss-linkedin" data-platform="linkedin">
                            <span class="kss-icon">💼</span> LinkedIn
                        </button>
                        <button class="kss-share-btn kss-pinterest" data-platform="pinterest">
                            <span class="kss-icon">📌</span> Pinterest
                        </button>
                    </div>
                </div>

                <div class="kss-modal-section">
                    <h4>Direct Sharing</h4>
                    <div class="kss-direct-share">
                        <button class="kss-share-btn kss-email" data-platform="email">
                            <span class="kss-icon">✉️</span> Email
                        </button>
                        <button class="kss-share-btn kss-copy-link" data-platform="copy">
                            <span class="kss-icon">🔗</span> Copy Link
                        </button>
                        <button class="kss-share-btn kss-whatsapp" data-platform="whatsapp">
                            <span class="kss-icon">💬</span> WhatsApp
                        </button>
                    </div>
                </div>

                <div class="kss-modal-section kss-gift-section" style="display: none;">
                    <h4>Send as Gift</h4>
                    <form class="kss-gift-form">
                        <div class="kss-form-group">
                            <label for="kss-gift-recipient-name">Recipient Name</label>
                            <input type="text" id="kss-gift-recipient-name" name="recipient_name" required>
                        </div>
                        <div class="kss-form-group">
                            <label for="kss-gift-recipient-email">Recipient Email</label>
                            <input type="email" id="kss-gift-recipient-email" name="recipient_email" required>
                        </div>
                        <div class="kss-form-group">
                            <label for="kss-gift-message">Personal Message</label>
                            <textarea id="kss-gift-message" name="personal_message" rows="3" placeholder="Add a personal message..."></textarea>
                        </div>
                        <div class="kss-form-group kss-gift-options">
                            <label>
                                <input type="checkbox" name="include_pdf" checked> Include PDF download
                            </label>
                            <label>
                                <input type="checkbox" name="save_to_library" checked> Save to recipient's library
                            </label>
                        </div>
                        <button type="submit" class="kss-btn kss-btn-primary">Send Gift</button>
                    </form>
                </div>

                <div class="kss-modal-section kss-url-section">
                    <h4>Article URL</h4>
                    <div class="kss-url-container">
                        <input type="text" class="kss-article-url" readonly>
                        <button class="kss-copy-url-btn" type="button">Copy</button>
                    </div>
                </div>
            </div>

            <div class="kss-modal-footer">
                <div class="kss-article-info">
                    <h5 class="kss-article-title"></h5>
                    <p class="kss-article-excerpt"></p>
                </div>
            </div>
        </div>
    </div>

    <style>
    .kss-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .kss-modal-container {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    }

    .kss-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid #eee;
    }

    .kss-modal-title {
        margin: 0;
        font-size: 24px;
        color: #333;
    }

    .kss-modal-close {
        background: none;
        border: none;
        font-size: 28px;
        color: #999;
        cursor: pointer;
        padding: 0;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .kss-modal-close:hover {
        color: #333;
    }

    .kss-modal-content {
        padding: 24px;
    }

    .kss-modal-section {
        margin-bottom: 24px;
    }

    .kss-modal-section h4 {
        margin: 0 0 12px 0;
        font-size: 18px;
        color: #333;
    }

    .kss-social-buttons,
    .kss-direct-share {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
    }

    .kss-share-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 12px 16px;
        border: 1px solid #ddd;
        border-radius: 8px;
        background: white;
        color: #333;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 14px;
    }

    .kss-share-btn:hover {
        background: #f8f9fa;
        border-color: #007cba;
        color: #007cba;
    }

    .kss-share-btn.kss-facebook:hover { background: #1877f2; color: white; }
    .kss-share-btn.kss-twitter:hover { background: #1da1f2; color: white; }
    .kss-share-btn.kss-linkedin:hover { background: #0077b5; color: white; }
    .kss-share-btn.kss-pinterest:hover { background: #bd081c; color: white; }
    .kss-share-btn.kss-whatsapp:hover { background: #25d366; color: white; }

    .kss-form-group {
        margin-bottom: 16px;
    }

    .kss-form-group label {
        display: block;
        margin-bottom: 4px;
        font-weight: 500;
        color: #333;
    }

    .kss-form-group input,
    .kss-form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .kss-gift-options {
        display: flex;
        gap: 16px;
    }

    .kss-gift-options label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 0;
    }

    .kss-btn {
        padding: 10px 20px;
        border: 1px solid #007cba;
        border-radius: 4px;
        background: #007cba;
        color: white;
        cursor: pointer;
        font-size: 14px;
        transition: background 0.2s ease;
    }

    .kss-btn:hover {
        background: #005a87;
    }

    .kss-url-container {
        display: flex;
        gap: 8px;
    }

    .kss-article-url {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #f8f9fa;
        font-size: 14px;
    }

    .kss-copy-url-btn {
        padding: 8px 16px;
        border: 1px solid #007cba;
        border-radius: 4px;
        background: #007cba;
        color: white;
        cursor: pointer;
        font-size: 14px;
    }

    .kss-modal-footer {
        padding: 20px 24px;
        border-top: 1px solid #eee;
        background: #f8f9fa;
    }

    .kss-article-title {
        margin: 0 0 8px 0;
        font-size: 16px;
        color: #333;
    }

    .kss-article-excerpt {
        margin: 0;
        color: #666;
        font-size: 14px;
        line-height: 1.4;
    }

    @media (max-width: 768px) {
        .kss-modal-container {
            width: 95%;
            margin: 20px;
        }
        
        .kss-social-buttons,
        .kss-direct-share {
            grid-template-columns: 1fr;
        }
        
        .kss-gift-options {
            flex-direction: column;
            gap: 8px;
        }
    }
    </style>

    <script>
    (function($) {
        'use strict';
        
        // Initialize modal functionality
        $(document).ready(function() {
            initUnifiedModal();
        });
        
        function initUnifiedModal() {
            const $modal = $('#kss-unified-modal');
            
            // Close modal handlers
            $modal.find('.kss-modal-close').on('click', closeModal);
            $modal.on('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Share button handlers
            $modal.find('.kss-share-btn').on('click', function(e) {
                e.preventDefault();
                const platform = $(this).data('platform');
                handleShare(platform);
            });
            
            // Copy link handler
            $modal.find('.kss-copy-url-btn').on('click', function() {
                const $urlInput = $modal.find('.kss-article-url');
                $urlInput.select();
                document.execCommand('copy');
                
                const $btn = $(this);
                const originalText = $btn.text();
                $btn.text('Copied!');
                setTimeout(() => $btn.text(originalText), 2000);
            });
            
            // Gift form handler
            $modal.find('.kss-gift-form').on('submit', function(e) {
                e.preventDefault();
                handleGiftSubmission($(this));
            });
        }
        
        function openModal(data) {
            const $modal = $('#kss-unified-modal');
            
            // Populate modal with data
            $modal.find('.kss-article-title').text(data.title || '');
            $modal.find('.kss-article-excerpt').text(data.excerpt || '');
            $modal.find('.kss-article-url').val(data.url || '');
            
            // Show/hide gift section based on KHM availability
            if (data.khm_available && data.is_logged_in) {
                $modal.find('.kss-gift-section').show();
            } else {
                $modal.find('.kss-gift-section').hide();
            }
            
            // Store current data
            $modal.data('current-article', data);
            
            // Show modal
            $modal.show();
        }
        
        function closeModal() {
            $('#kss-unified-modal').hide();
        }
        
        function handleShare(platform) {
            const $modal = $('#kss-unified-modal');
            const data = $modal.data('current-article');
            
            if (!data) return;
            
            const shareData = {
                url: encodeURIComponent(data.url),
                title: encodeURIComponent(data.title),
                text: encodeURIComponent(data.excerpt)
            };
            
            let shareUrl = '';
            
            switch (platform) {
                case 'facebook':
                    shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${shareData.url}`;
                    break;
                case 'twitter':
                    shareUrl = `https://twitter.com/intent/tweet?url=${shareData.url}&text=${shareData.title}`;
                    break;
                case 'linkedin':
                    shareUrl = `https://www.linkedin.com/sharing/share-offsite/?url=${shareData.url}`;
                    break;
                case 'pinterest':
                    shareUrl = `https://pinterest.com/pin/create/button/?url=${shareData.url}&description=${shareData.title}`;
                    break;
                case 'whatsapp':
                    shareUrl = `https://wa.me/?text=${shareData.title}%20${shareData.url}`;
                    break;
                case 'email':
                    shareUrl = `mailto:?subject=${shareData.title}&body=${shareData.text}%0A%0A${shareData.url}`;
                    break;
                case 'copy':
                    navigator.clipboard.writeText(data.url).then(() => {
                        showMessage('Link copied to clipboard!');
                    });
                    return;
            }
            
            if (shareUrl) {
                window.open(shareUrl, '_blank', 'width=600,height=400');
            }
        }
        
        function handleGiftSubmission($form) {
            const $modal = $('#kss-unified-modal');
            const data = $modal.data('current-article');
            
            if (!data || !data.post_id) {
                showMessage('Error: No article selected', 'error');
                return;
            }
            
            const formData = new FormData($form[0]);
            formData.append('action', 'kss_send_gift');
            formData.append('post_id', data.post_id);
            formData.append('nonce', window.khm_ajax?.nonce || '');
            
            // Show loading
            const $submitBtn = $form.find('[type="submit"]');
            const originalText = $submitBtn.text();
            $submitBtn.text('Sending...').prop('disabled', true);
            
            $.ajax({
                url: window.khm_ajax?.ajax_url || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showMessage('Gift sent successfully!', 'success');
                        $form[0].reset();
                        setTimeout(closeModal, 2000);
                    } else {
                        showMessage(response.data?.message || 'Failed to send gift', 'error');
                    }
                },
                error: function() {
                    showMessage('Network error. Please try again.', 'error');
                },
                complete: function() {
                    $submitBtn.text(originalText).prop('disabled', false);
                }
            });
        }
        
        function showMessage(message, type = 'info') {
            // Simple message display - you can enhance this
            console.log(`${type.toUpperCase()}: ${message}`);
            
            // Create a simple notification
            const $notification = $('<div>', {
                class: `kss-notification kss-${type}`,
                text: message,
                css: {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    padding: '12px 20px',
                    background: type === 'error' ? '#dc3545' : '#28a745',
                    color: 'white',
                    borderRadius: '4px',
                    zIndex: '10001',
                    fontSize: '14px'
                }
            });
            
            $('body').append($notification);
            
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, 4000);
        }
        
        // Expose global function for other scripts
        window.kssOpenModal = openModal;
        
    })(jQuery);
    </script>
    <?php
}

/**
 * Handle modal AJAX requests
 */
add_action('wp_ajax_kss_get_modal_data', 'kss_handle_get_modal_data');
add_action('wp_ajax_nopriv_kss_get_modal_data', 'kss_handle_get_modal_data');

function kss_handle_get_modal_data() {
    $post_id = intval($_POST['post_id'] ?? 0);
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    $post = get_post($post_id);
    if (!$post) {
        wp_send_json_error('Post not found');
    }
    
    $data = [
        'post_id' => $post_id,
        'title' => $post->post_title,
        'excerpt' => wp_trim_words($post->post_content, 30),
        'url' => get_permalink($post_id),
        'khm_available' => function_exists('khm_is_marketing_suite_ready') && khm_is_marketing_suite_ready(),
        'is_logged_in' => is_user_logged_in()
    ];
    
    // Add enhanced data if available
    if (function_exists('kss_get_enhanced_widget_data')) {
        $enhanced_data = kss_get_enhanced_widget_data($post_id);
        $data = array_merge($data, $enhanced_data);
    }
    
    wp_send_json_success($data);
}