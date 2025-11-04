<?php
/*
Plugin Name: KHM Membership
Description: KHM Membership plugin scaffold (development).
Version: 0.1.0
Author: KHM Dev
Text Domain: khm-membership
*/

// Basic bootstrap for plugin - loads composer autoloader if present.
if ( file_exists(__DIR__ . '/vendor/autoload.php') ) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Load marketing suite integration functions
require_once __DIR__ . '/includes/marketing-suite-functions.php';

// Load credit system helper functions
require_once __DIR__ . '/includes/credit-system-helpers.php';

// Minimal init: register activation/deactivation hooks and call plugin initializer if available.
register_activation_hook(__FILE__, function () {
    // Activation tasks (create tables, etc) — use migrations in /db/migrations
    if ( class_exists('KHM\\Services\\DatabaseIdempotencyStore') ) {
        KHM\Services\DatabaseIdempotencyStore::createTable();
    }

    // Schedule cron tasks
    if ( class_exists('KHM\\Scheduled\\Scheduler') ) {
        KHM\Scheduled\Scheduler::activate();
    }

    // Initialize credit system tables
    if ( class_exists('KHM\\Services\\CreditService') ) {
        try {
            $memberships = new KHM\Services\MembershipRepository();
            $levels = new KHM\Services\LevelRepository();
            $credit_service = new KHM\Services\CreditService($memberships, $levels);
            $credit_service->createTables();
            error_log('KHM Credit System tables created successfully');
        } catch (\Exception $e) {
            error_log('Failed to create credit system tables: ' . $e->getMessage());
        }
    }

    // Initialize library system tables
    if ( class_exists('KHM\\Services\\LibraryService') ) {
        try {
            $memberships = new KHM\Services\MembershipRepository();
            $library_service = new KHM\Services\LibraryService($memberships);
            $library_service->create_tables();
            error_log('KHM Library System tables created successfully');
        } catch (\Exception $e) {
            error_log('Failed to create library system tables: ' . $e->getMessage());
        }
    }

    // Initialize eCommerce system tables
    if ( class_exists('KHM\\Services\\ECommerceService') ) {
        try {
            $memberships = new KHM\Services\MembershipRepository();
            $orders = new KHM\Services\OrderRepository();
            $ecommerce_service = new KHM\Services\ECommerceService($memberships, $orders);
            $ecommerce_service->create_tables();
            error_log('KHM eCommerce System tables created successfully');
        } catch (\Exception $e) {
            error_log('Failed to create eCommerce system tables: ' . $e->getMessage());
        }
    }

    // Initialize gift system tables
    if ( class_exists('KHM\\Services\\GiftService') ) {
        try {
            $memberships = new KHM\Services\MembershipRepository();
            $orders = new KHM\Services\OrderRepository();
            $email = new KHM\Services\EmailService(__DIR__);
            $gift_service = new KHM\Services\GiftService($memberships, $orders, $email);
            $gift_service->create_tables();
            error_log('KHM Gift System tables created successfully');
        } catch (\Exception $e) {
            error_log('Failed to create gift system tables: ' . $e->getMessage());
        }
    }

    // Initialize credit system
    do_action('khm_plugin_activated');
});

register_deactivation_hook(__FILE__, function () {
    // Deactivation tasks
    if ( class_exists('KHM\\Scheduled\\Scheduler') ) {
        KHM\Scheduled\Scheduler::deactivate();
    }
});

// If a Plugin class exists, call its init method
if ( class_exists('KHM\\Plugin') ) {
    KHM\Plugin::init(__FILE__);
}

// Register REST routes (webhooks, etc.)
add_action('rest_api_init', function () {
    if ( class_exists('KHM\\Rest\\WebhooksController') &&
        class_exists('KHM\\Gateways\\StripeWebhookVerifier') &&
        class_exists('KHM\\Services\\DatabaseIdempotencyStore') ) {
        $controller = new KHM\Rest\WebhooksController(
            new KHM\Gateways\StripeWebhookVerifier(),
            new KHM\Services\DatabaseIdempotencyStore(),
            new KHM\Services\OrderRepository(),
            new KHM\Services\MembershipRepository()
        );
        $controller->register_routes();
    }
    // Register subscription management routes
    if ( class_exists('KHM\\Rest\\SubscriptionController') ) {
        ( new KHM\Rest\SubscriptionController() )->register();
    }
    // Register payment method routes
    if ( class_exists('KHM\\Rest\\PaymentMethodController') ) {
        ( new KHM\Rest\PaymentMethodController() )->register();
    }
    // Register invoice routes
    if ( class_exists('KHM\\Rest\\InvoiceController') ) {
        ( new KHM\Rest\InvoiceController() )->register();
    }
});

// Register webhook email notifications
add_action('init', function () {
    if ( class_exists('KHM\\Services\\WebhookEmailNotifications') ) {
        $webhook_emails = new KHM\Services\WebhookEmailNotifications(
            new KHM\Services\EmailService(__DIR__),
            new KHM\Services\OrderRepository()
        );
        $webhook_emails->register();
    }
});

// Load helper functions
require_once __DIR__ . '/includes/functions.php';

// Register checkout shortcode
add_action('init', function () {
    if ( class_exists('KHM\\Public\\CheckoutShortcode') ) {
        $checkout = new KHM\Public\CheckoutShortcode(
            new KHM\Services\MembershipRepository(),
            new KHM\Services\OrderRepository(),
            new KHM\Services\EmailService(__DIR__),
            new KHM\Services\LevelRepository()
        );
        $checkout->register();
    }
});

// Register content protection
add_action('init', function () {
    if ( class_exists('KHM\\Services\\AccessControlService') &&
        class_exists('KHM\\PublicFrontend\\ContentFilter') ) {
        $membership_repo = new KHM\Services\MembershipRepository();
        $level_repo      = new KHM\Services\LevelRepository();

        $access_control = new KHM\Services\AccessControlService(
            $membership_repo,
            $level_repo
        );

        $content_filter = new KHM\PublicFrontend\ContentFilter($access_control);
        $content_filter->register();

        // Register member shortcode
        if ( class_exists('KHM\\PublicFrontend\\MemberShortcode') ) {
            $member_shortcode = new KHM\PublicFrontend\MemberShortcode($access_control);
            $member_shortcode->register();
        }

        // Register account shortcode
        if ( class_exists('KHM\\PublicFrontend\\AccountShortcode') ) {
            $account_shortcode = new KHM\PublicFrontend\AccountShortcode(
                $membership_repo,
                new KHM\Services\OrderRepository(),
                $level_repo
            );
            $account_shortcode->register();
            add_action('wp_enqueue_scripts', [ $account_shortcode, 'enqueue_assets' ]);
        }

        // Register library frontend
        if ( class_exists('KHM\\PublicFrontend\\LibraryFrontend') ) {
            $library_service = new KHM\Services\LibraryService($membership_repo);
            $library_frontend = new KHM\PublicFrontend\LibraryFrontend($library_service, $membership_repo);
        }

        // Register commerce frontend (unified modal for cart/checkout)
        if ( class_exists('KHM\\Frontend\\CommerceFrontend') ) {
            $commerce_frontend = new KHM\Frontend\CommerceFrontend();
        }
    }
});

// Register admin menu and pages
add_action('init', function () {
    if ( is_admin() && class_exists('KHM\\Admin\\AdminMenu') ) {
        $admin_menu = new KHM\Admin\AdminMenu();
        $admin_menu->register();
    }

    // Register reports page
    if ( is_admin() && class_exists('KHM\\Admin\\ReportsPage') ) {
        $reports_page = new KHM\Admin\ReportsPage(
            new KHM\Services\ReportsService()
        );
        $reports_page->register();
    }

    // Register members page
    if ( is_admin() && class_exists('KHM\\Admin\\MembersPage') ) {
        $members_page = new KHM\Admin\MembersPage();
        $members_page->register();
        $GLOBALS['khm_members_page'] = $members_page;
    }

    // Register orders page
    if ( is_admin() && class_exists('KHM\\Admin\\OrdersPage') ) {
        $orders_page = new KHM\Admin\OrdersPage();
        $orders_page->register();
        $GLOBALS['khm_orders_page'] = $orders_page;
    }

    // Register admin order action handlers (resend receipts, manual refunds).
    if ( is_admin() && class_exists('KHM\\Services\\AdminOrderActions') ) {
        ( new KHM\Services\AdminOrderActions(
            new KHM\Services\OrderRepository(),
            new KHM\Services\EmailService(__DIR__)
        ) )->register();
    }

    // Register levels page
    if ( is_admin() && class_exists('KHM\\Admin\\LevelsPage') ) {
        $levels_page = new KHM\Admin\LevelsPage();
        $levels_page->register();
        $GLOBALS['khm_levels_page'] = $levels_page;
    }

    // Register discount codes page
    if ( is_admin() && class_exists('KHM\\Admin\\DiscountCodesPage') ) {
        $discount_codes_page = new KHM\Admin\DiscountCodesPage();
        $discount_codes_page->register();
    }

    // Register discount code hooks for checkout integration
    if ( class_exists('KHM\\Hooks\\DiscountCodeHooks') && class_exists('KHM\\Services\\DiscountCodeService') ) {
        $discount_service = new KHM\Services\DiscountCodeService();
        $discount_levels  = new KHM\Services\LevelRepository();
        
        $discount_hooks = new KHM\Hooks\DiscountCodeHooks( $discount_service, $discount_levels );
        $discount_hooks->register();

        // Register discount code widget for checkout page
        if ( class_exists('KHM\\Public\\DiscountCodeWidget') ) {
            $discount_widget = new KHM\Public\DiscountCodeWidget( $discount_service );
            $discount_widget->register();
        }
    }
});

// Register scheduler and daily tasks
add_action('init', function () {
    if ( class_exists('KHM\\Scheduled\\Scheduler') ) {
        ( new KHM\Scheduled\Scheduler() )->register();
    }

    if ( class_exists('KHM\\Scheduled\\Scheduler') && class_exists('KHM\\Scheduled\\Tasks') ) {
        add_action(KHM\Scheduled\Scheduler::HOOK_DAILY, [ new KHM\Scheduled\Tasks(), 'run_daily' ]);
    }
});

// Add custom capabilities
add_action('admin_init', function () {
    $admin_role = get_role('administrator');
    if ( $admin_role ) {
        $admin_role->add_cap('manage_khm');
        $admin_role->add_cap('edit_khm_members');
        $admin_role->add_cap('edit_khm_orders');
        $admin_role->add_cap('read_khm_orders');
        $admin_role->add_cap('export_khm_reports');
    }
});

// Clear reports cache when orders or memberships change
add_action('khm_order_created', function () {
    if ( class_exists('KHM\\Services\\ReportsService') ) {
        ( new KHM\Services\ReportsService() )->clear_cache();
    }
});

add_action('khm_order_updated', function () {
    if ( class_exists('KHM\\Services\\ReportsService') ) {
        ( new KHM\Services\ReportsService() )->clear_cache();
    }
});

add_action('khm_membership_assigned', function () {
    if ( class_exists('KHM\\Services\\ReportsService') ) {
        ( new KHM\Services\ReportsService() )->clear_cache();
    }
});

add_action('khm_membership_cancelled', function () {
    if ( class_exists('KHM\\Services\\ReportsService') ) {
        ( new KHM\Services\ReportsService() )->clear_cache();
    }
});

// Initialize Marketing Suite Integration
add_action('plugins_loaded', function () {
    if ( class_exists('KHM\\Services\\PluginRegistry') && 
         class_exists('KHM\\Services\\MarketingSuiteServices') ) {
        
        // Initialize services
        $marketing_services = new KHM\Services\MarketingSuiteServices(
            new KHM\Services\MembershipRepository(),
            new KHM\Services\OrderRepository(),
            new KHM\Services\LevelRepository()
        );
        
        // Register all services
        $marketing_services->register_services();
        
        // Fire hook to let other plugins know KHM is ready
        do_action('khm_marketing_suite_ready');
    }
});

// Social Strip Widget Data Function
if (!function_exists('kss_get_enhanced_widget_data')) {
    /**
     * Get enhanced data for social strip widget
     * Provides data for all 5 buttons: Download, Save, Buy, Gift, Share
     *
     * @param int $post_id
     * @return array
     */
    function kss_get_enhanced_widget_data(int $post_id): array {
        $user_id = get_current_user_id();
        $post = get_post($post_id);
        
        if (!$post) {
            return [];
        }
        
        // Base data
        $data = [
            'post_id' => $post_id,
            'post_title' => $post->post_title,
            'post_url' => get_permalink($post_id),
            'user_id' => $user_id,
            'is_logged_in' => $user_id > 0,
        ];
        
        // Only proceed with KHM integration if user is logged in and KHM is available
        if ($user_id > 0 && function_exists('khm_get_user_membership')) {
            
            // Get user membership and credits
            $membership = khm_get_user_membership($user_id);
            $credits = khm_get_user_credits($user_id);
            
            // Download functionality (credits)
            $data['credits'] = [
                'available' => $credits,
                'required' => 1,
                'can_download' => $credits >= 1
            ];
            
            // Save to Library functionality
            $data['library'] = [
                'is_saved' => false, // TODO: Check if article is already saved
                'can_save' => true
            ];
            
            // Buy functionality (pricing)
            $base_price = get_post_meta($post_id, '_article_price', true) ?: 5.99;
            $discount_info = khm_get_member_discount($user_id, $base_price, 'article');
            
            $data['pricing'] = [
                'base_price' => $base_price,
                'member_price' => $discount_info['discounted_price'],
                'discount_percent' => $discount_info['discount_percent'],
                'currency' => '£'
            ];
            
            // Gift functionality
            $data['gift'] = [
                'can_gift' => true,
                'price' => $data['pricing']['member_price']
            ];
            
            // Member status
            $data['membership'] = [
                'is_member' => !empty($membership),
                'level' => $membership ? $membership->level_name : null
            ];
        } else {
            // Guest user defaults
            $base_price = get_post_meta($post_id, '_article_price', true) ?: 5.99;
            
            $data['credits'] = [
                'available' => 0,
                'required' => 1,
                'can_download' => false
            ];
            
            $data['library'] = [
                'is_saved' => false,
                'can_save' => false
            ];
            
            $data['pricing'] = [
                'base_price' => $base_price,
                'member_price' => $base_price,
                'discount_percent' => 0,
                'currency' => '£'
            ];
            
            $data['gift'] = [
                'can_gift' => true,
                'price' => $base_price
            ];
            
            $data['membership'] = [
                'is_member' => false,
                'level' => null
            ];
        }
        
        // Share functionality (always available)
        $data['share'] = [
            'title' => $post->post_title,
            'url' => get_permalink($post_id),
            'excerpt' => wp_trim_words($post->post_excerpt ?: $post->post_content, 30)
        ];
        
        return apply_filters('kss_enhanced_widget_data', $data, $post_id, $user_id);
    }
}

// Initialize Enhanced Email System
add_action('plugins_loaded', function () {
    if ( class_exists('KHM\\Services\\EnhancedEmailService') && 
         class_exists('KHM\\Admin\\EnhancedEmailAdmin') &&
         class_exists('KHM\\Migrations\\EnhancedEmailMigration') ) {
        
        // Initialize enhanced email service
        $enhanced_email_service = new KHM\Services\EnhancedEmailService(
            plugin_dir_path(__FILE__)
        );
        
        // Initialize admin interface
        if (is_admin()) {
            new KHM\Admin\EnhancedEmailAdmin($enhanced_email_service);
        }
        
        // Register enhanced email service globally
        $GLOBALS['khm_enhanced_email'] = $enhanced_email_service;
        
        // Fire hook to let other plugins know enhanced email is ready
        do_action('khm_enhanced_email_ready', $enhanced_email_service);
    }
});

// Enhanced Email Cron Schedule
add_filter('cron_schedules', function($schedules) {
    $schedules['every_five_minutes'] = array(
        'interval' => 300, // 5 minutes in seconds
        'display'  => __('Every 5 Minutes', 'khm-plugin')
    );
    return $schedules;
});
