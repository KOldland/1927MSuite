/**
 * KHM Integration JavaScript for Social Strip
 * Handles AJAX calls for Download, Save, Buy, and Gift functionality
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initKHMIntegration();
    });
    
    function initKHMIntegration() {
        // Download with Credits functionality
        $('.kss-download-credit').on('click', function(e) {
            e.preventDefault();
            handleCreditDownload($(this));
        });
        
        // Save to Library functionality
        $('.kss-save-button').on('click', function(e) {
            e.preventDefault();
            handleSaveToLibrary($(this));
        });
        
        // Buy PDF functionality
        $('.kss-buy-button.kss-add-to-cart').on('click', function(e) {
            e.preventDefault();
            handleBuyArticle($(this));
        });
        
        // Gift functionality
        $('.kss-gift-button').on('click', function(e) {
            e.preventDefault();
            handleGiftArticle($(this));
        });
        
        // Direct PDF download (for purchased articles)
        $('.kss-direct-download').on('click', function(e) {
            e.preventDefault();
            handleDirectDownload($(this));
        });
    }
    
    /**
     * Handle credit-based download
     */
    function handleCreditDownload($button) {
        const postId = $button.data('post-id');
        
        if (!postId) {
            showMessage('Error: Invalid article ID', 'error');
            return;
        }
        
        // Show loading state
        setButtonLoading($button, true);
        
        $.ajax({
            url: khm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kss_download_with_credit',
                post_id: postId,
                nonce: khm_ajax.nonce
            },
            success: function(response) {
                setButtonLoading($button, false);
                
                if (response.success) {
                    showMessage('Download started! Credits remaining: ' + response.data.credits_remaining, 'success');
                    
                    // Create download link
                    if (response.data.download_url) {
                        window.location.href = response.data.download_url;
                    }
                    
                    // Update credits display
                    updateCreditsDisplay(response.data.credits_remaining);
                    
                } else {
                    showMessage(response.data.error || 'Download failed', 'error');
                }
            },
            error: function() {
                setButtonLoading($button, false);
                showMessage('Network error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Handle save to library
     */
    function handleSaveToLibrary($button) {
        const postId = $button.data('post-id');
        const isSaved = $button.hasClass('saved');
        
        if (!postId) {
            showMessage('Error: Invalid article ID', 'error');
            return;
        }
        
        // Show loading state
        setButtonLoading($button, true);
        
        $.ajax({
            url: khm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: isSaved ? 'kss_remove_from_library' : 'kss_save_to_library',
                post_id: postId,
                nonce: khm_ajax.nonce
            },
            success: function(response) {
                setButtonLoading($button, false);
                
                if (response.success) {
                    // Toggle saved state
                    $button.toggleClass('saved');
                    
                    const message = isSaved ? 'Removed from library' : 'Saved to library';
                    showMessage(message, 'success');
                    
                    // Update button appearance
                    updateSaveButton($button, !isSaved);
                    
                } else {
                    showMessage(response.data.error || 'Save failed', 'error');
                }
            },
            error: function() {
                setButtonLoading($button, false);
                showMessage('Network error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Handle buy article - now opens unified modal
     */
    function handleBuyArticle($button) {
        const postId = $button.data('post-id');
        
        if (!postId) {
            showMessage('Error: Invalid article ID', 'error');
            return;
        }

        // Check if the commerce modal is available
        if (typeof window.KHMCommerce !== 'undefined' && window.KHMCommerce.openQuickBuy) {
            // Open the unified commerce modal for quick buy
            window.KHMCommerce.openQuickBuy(postId);
        } else {
            // Fallback to old cart behavior
            handleBuyArticleFallback($button);
        }
    }

    /**
     * Fallback buy article handler (legacy behavior)
     */
    function handleBuyArticleFallback($button) {
        const postId = $button.data('post-id');
        
        // Show loading state
        setButtonLoading($button, true);
        
        $.ajax({
            url: khm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kss_add_to_cart',
                post_id: postId,
                nonce: khm_ajax.nonce
            },
            success: function(response) {
                setButtonLoading($button, false);
                
                if (response.success) {
                    showMessage('Added to cart!', 'success');
                    
                    // Update cart count
                    updateCartCount(response.data.cart_count);
                    
                    // Offer to open cart modal if available
                    if (typeof window.KHMCommerce !== 'undefined' && window.KHMCommerce.openCart) {
                        setTimeout(() => {
                            if (confirm('Article added to cart. Would you like to review your cart?')) {
                                window.KHMCommerce.openCart();
                            }
                        }, 1000);
                    } else if (response.data.redirect_url) {
                        // Fallback to redirect
                        window.location.href = response.data.redirect_url;
                    }
                    
                } else {
                    showMessage(response.data.error || 'Failed to add to cart', 'error');
                }
            },
            error: function() {
                setButtonLoading($button, false);
                showMessage('Network error. Please try again.', 'error');
            }
        });
    }
    
    /**
     * Handle gift article
     */
    function handleGiftArticle($button) {
        const postId = $button.data('post-id');
        
        if (!postId) {
            showMessage('Error: Invalid article ID', 'error');
            return;
        }
        
        // Open gift modal or redirect to gift page
        if (typeof openGiftModal === 'function') {
            openGiftModal(postId);
        } else {
            // Fallback to direct purchase
            window.location.href = khm_ajax.gift_url + '?post_id=' + postId;
        }
    }
    
    /**
     * Handle direct download (for purchased articles)
     */
    function handleDirectDownload($button) {
        const downloadUrl = $button.data('download-url') || $button.attr('href');
        
        if (!downloadUrl) {
            showMessage('Error: No download URL available', 'error');
            return;
        }
        
        // Track download
        $.ajax({
            url: khm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'kss_track_download',
                download_url: downloadUrl,
                nonce: khm_ajax.nonce
            }
        });
        
        // Start download
        window.location.href = downloadUrl;
        showMessage('Download started!', 'success');
    }
    
    /**
     * Set button loading state
     */
    function setButtonLoading($button, loading) {
        if (loading) {
            $button.addClass('loading').prop('disabled', true);
            
            // Add spinner if not exists
            if (!$button.find('.spinner').length) {
                $button.append('<span class="spinner"></span>');
            }
        } else {
            $button.removeClass('loading').prop('disabled', false);
            $button.find('.spinner').remove();
        }
    }
    
    /**
     * Update save button appearance
     */
    function updateSaveButton($button, isSaved) {
        const $img = $button.find('img');
        
        if (isSaved) {
            $button.attr('title', 'Saved to Library');
            $img.attr('alt', 'Saved');
            // Could change icon to filled bookmark
        } else {
            $button.attr('title', 'Save to Library');
            $img.attr('alt', 'Save');
            // Could change icon to empty bookmark
        }
    }
    
    /**
     * Update credits display
     */
    function updateCreditsDisplay(credits) {
        $('.credits-count').text(credits);
        $('.kss-download-credit').each(function() {
            const $this = $(this);
            if (credits < 1) {
                $this.addClass('disabled').prop('disabled', true);
                $this.attr('title', 'Insufficient credits');
            } else {
                $this.removeClass('disabled').prop('disabled', false);
                $this.attr('title', 'Download (1 credit)');
            }
        });
    }
    
    /**
     * Update cart count display
     */
    function updateCartCount(count) {
        $('.cart-count').text(count);
        
        // Show/hide cart indicator
        if (count > 0) {
            $('.cart-indicator').show();
        } else {
            $('.cart-indicator').hide();
        }
    }
    
    /**
     * Show user message
     */
    function showMessage(message, type) {
        type = type || 'info';
        
        // Remove existing messages
        $('.kss-message').remove();
        
        // Create message element
        const $message = $('<div class="kss-message kss-message-' + type + '">' + message + '</div>');
        
        // Add to page
        $('body').prepend($message);
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Initialize on AJAX page loads (for SPA themes)
     */
    $(document).on('ajaxComplete', function() {
        initKHMIntegration();
    });
    
})(jQuery);