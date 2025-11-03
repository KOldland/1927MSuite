<?php
/**
 * Plugin Name: Membership Manager
 * Description: Manages site access based on membership level.
 * Version: 1.0
 * Author: Kirsty Hennah
 */

if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/includes/class-membership-handler.php';
require_once __DIR__ . '/admin/membership-admin.php';


function membership_plugin_init() {
    new Membership_Handler();
}

    add_action('plugins_loaded', 'membership_plugin_init');

    // Add custom schedule interval (monthly)
    add_filter('cron_schedules', function($schedules) {
        $schedules['monthly'] = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __('Once Monthly'),
        ];
        return $schedules;
    });

    register_activation_hook(__FILE__, function() {
        if (!wp_next_scheduled('kss_reset_article_counts')) {
            wp_schedule_event(strtotime('first day of next month midnight'), 'monthly', 'kss_reset_article_counts');
        }
    });

    add_action('kss_reset_article_counts', function() {
        $users = get_users([
            'meta_key' => 'kss_articles_read',
            'meta_compare' => 'EXISTS',
            'fields' => ['ID']
        ]);
        
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'kss_articles_read');
        }
    });
    
    register_deactivation_hook(__FILE__, function() {
        wp_clear_scheduled_hook('kss_reset_article_counts');
    });
