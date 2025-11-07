<?php

defined('ABSPATH') or exit;

/**
 * TouchPoint MailChimp User Sync Module
 * 
 * Based on MC4WP User Sync functionality
 * Handles automatic synchronization of WordPress users to MailChimp
 */

// Load User Sync classes
require_once __DIR__ . '/includes/class-user-sync.php';
require_once __DIR__ . '/includes/class-sync-queue.php';
require_once __DIR__ . '/includes/class-user-handler.php';

// Initialize User Sync if enabled
$settings = TouchPoint_MailChimp_Settings::instance();

if ($settings->is_user_sync_enabled()) {
    $user_sync = new TouchPoint_MailChimp_User_Sync();
    $user_sync->init();
    
    // Load admin if in admin area
    if (is_admin()) {
        require_once __DIR__ . '/includes/class-admin.php';
        $admin = new TouchPoint_MailChimp_User_Sync_Admin();
        $admin->init();
    }
    
    // Load WP-CLI commands if WP-CLI is available
    if (defined('WP_CLI') && WP_CLI) {
        require_once __DIR__ . '/includes/class-cli.php';
        WP_CLI::add_command('tmc user-sync', 'TouchPoint_MailChimp_User_Sync_CLI');
    }
}