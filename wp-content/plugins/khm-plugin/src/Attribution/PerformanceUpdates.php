<?php
/**
 * Performance Optimization Updates for KHM Attribution Manager
 * 
 * This file contains performance-optimized methods that should be integrated
 * into the main Attribution Manager class.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Performance-optimized methods for the KHM_Advanced_Attribution_Manager class
 */
class KHM_Attribution_Performance_Updates {
    
    /**
     * Initialize performance components
     * Add this to the constructor of KHM_Advanced_Attribution_Manager
     */
    public static function init_performance_components($manager) {
        // Load performance manager
        require_once dirname(__FILE__) . '/PerformanceManager.php';
        $manager->performance_manager = new KHM_Attribution_Performance_Manager();
        
        // Load async manager
        require_once dirname(__FILE__) . '/AsyncManager.php';
        $manager->async_manager = new KHM_Attribution_Async_Manager();
        
        // Load query builder
        require_once dirname(__FILE__) . '/QueryBuilder.php';
        $manager->query_builder = new KHM_Attribution_Query_Builder();
    }
    
    /**
     * Optimized attribution event storage
     * Replace store_attribution_event method
     */
    public static function store_attribution_event_optimized($manager, $attribution_data) {
        // Queue for batch processing if high volume
        if (self::should_use_batch_processing($attribution_data)) {
            return $manager->performance_manager->queue_attribution_event($attribution_data);
        }
        
        // Store immediately for high-priority events
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'khm_attribution_events';
        
        $result = $wpdb->insert(
            $table_events,
            array(
                'click_id' => $attribution_data['click_id'],
                'affiliate_id' => $attribution_data['affiliate_id'],
                'session_id' => $attribution_data['session_id'] ?? session_id(),
                'user_id' => $attribution_data['user_id'] ?? get_current_user_id(),
                'utm_source' => $attribution_data['utm_params']['utm_source'] ?? null,
                'utm_medium' => $attribution_data['utm_params']['utm_medium'] ?? null,
                'utm_campaign' => $attribution_data['utm_params']['utm_campaign'] ?? null,
                'utm_content' => $attribution_data['utm_params']['utm_content'] ?? null,
                'utm_term' => $attribution_data['utm_params']['utm_term'] ?? null,
                'ip_address' => $attribution_data['ip_address'],
                'user_agent' => $attribution_data['user_agent'],
                'referrer_url' => $attribution_data['referrer'] ?? null,
                'landing_page' => $attribution_data['target_url'] ?? null,
                'screen_resolution' => $attribution_data['client_data']['screen_resolution'] ?? null,
                'browser_language' => $attribution_data['client_data']['browser_language'] ?? null,
                'timezone' => $attribution_data['client_data']['timezone'] ?? null,
                'fingerprint_hash' => $attribution_data['client_data']['fingerprint_hash'] ?? null,
                'created_at' => $attribution_data['timestamp'],
                'expires_at' => $attribution_data['attribution_window_expires'],
                'attribution_method' => 'server_side_event'
            ),
            array('%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        // Clear attribution cache
        if (isset($manager->performance_manager)) {
            $cache_key = 'attribution_' . ($attribution_data['session_id'] ?? session_id());
            $manager->performance_manager->delete_cache($cache_key);
        }
        
        return $result !== false;
    }
    
    /**
     * Optimized conversion attribution resolution
     * Replace resolve_conversion_attribution method
     */
    public static function resolve_conversion_attribution_optimized($manager, $conversion_id, $additional_data = array()) {
        // Use cached attribution lookup first
        if (isset($manager->performance_manager)) {
            $cache_key = 'conversion_attribution_' . $conversion_id;
            $cached_attribution = $manager->performance_manager->get_cache($cache_key);
            
            if ($cached_attribution !== false) {
                return $cached_attribution;
            }
        }
        
        // Get attribution data using optimized query
        $session_id = session_id();
        $user_id = get_current_user_id();
        
        if (isset($manager->query_builder)) {
            $attribution_events = $manager->query_builder->get_attribution_events(array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'date_from' => date('Y-m-d H:i:s', strtotime('-' . $manager->attribution_window . ' days')),
                'limit' => 50,
                'order_by' => 'created_at DESC'
            ));
        } else {
            // Fallback to original method
            $attribution_events = self::get_attribution_events_fallback($session_id, $user_id, $manager->attribution_window);
        }
        
        if (empty($attribution_events)) {
            return false;
        }
        
        // Try attribution methods in order of preference
        $attribution_methods = array(
            'server_side_event' => 0.95,
            'first_party_cookie' => 0.90,
            'url_parameter' => 0.85,
            'session_storage' => 0.75,
            'fingerprint_match' => 0.60
        );
        
        $best_attribution = null;
        $highest_confidence = 0;
        
        foreach ($attribution_events as $event) {
            $method = $event->attribution_method ?? 'server_side_event';
            $base_confidence = $attribution_methods[$method] ?? 0.50;
            
            // Calculate time decay factor
            $event_age = time() - strtotime($event->created_at);
            $time_decay = max(0.1, 1 - ($event_age / (30 * 24 * 3600))); // 30 day decay
            
            $final_confidence = $base_confidence * $time_decay;
            
            if ($final_confidence > $highest_confidence) {
                $highest_confidence = $final_confidence;
                $best_attribution = array(
                    'click_id' => $event->click_id,
                    'affiliate_id' => $event->affiliate_id,
                    'utm_source' => $event->utm_source,
                    'utm_medium' => $event->utm_medium,
                    'utm_campaign' => $event->utm_campaign,
                    'method' => $method,
                    'confidence' => $final_confidence,
                    'event_timestamp' => $event->created_at,
                    'attribution_explanation' => self::generate_attribution_explanation($event, $method, $final_confidence)
                );
            }
        }
        
        // Cache the result
        if ($best_attribution && isset($manager->performance_manager)) {
            $cache_key = 'conversion_attribution_' . $conversion_id;
            $manager->performance_manager->set_cache($cache_key, $best_attribution, 3600);
        }
        
        return $best_attribution;
    }
    
    /**
     * Optimized conversion storage with attribution
     * Replace store_conversion_with_attribution method
     */
    public static function store_conversion_with_attribution_optimized($manager, $conversion_data) {
        global $wpdb;
        
        $table_conversions = $wpdb->prefix . 'khm_conversion_tracking';
        
        $attribution = $conversion_data['attribution'] ?? null;
        
        $result = $wpdb->insert(
            $table_conversions,
            array(
                'order_id' => $conversion_data['conversion_id'],
                'click_id' => $attribution['click_id'] ?? null,
                'affiliate_id' => $attribution['affiliate_id'] ?? null,
                'order_value' => $conversion_data['value'],
                'commission_amount' => $conversion_data['commission']['amount'] ?? 0,
                'commission_rate' => $conversion_data['commission']['rate'] ?? 0,
                'attribution_method' => $attribution['method'] ?? null,
                'attribution_confidence' => $attribution['confidence'] ?? null,
                'attribution_explanation' => $attribution['attribution_explanation'] ?? null,
                'multi_touch_data' => json_encode($attribution['multi_touch'] ?? array()),
                'created_at' => $conversion_data['timestamp'],
                'status' => $attribution ? 'attributed' : 'pending'
            ),
            array('%s', '%s', '%d', '%f', '%f', '%f', '%s', '%f', '%s', '%s', '%s', '%s')
        );
        
        // Clear relevant caches
        if (isset($manager->performance_manager)) {
            $affiliate_id = $attribution['affiliate_id'] ?? null;
            if ($affiliate_id) {
                $manager->performance_manager->delete_cache('affiliate_performance_' . $affiliate_id);
            }
        }
        
        // Queue analytics aggregation
        if (isset($manager->async_manager)) {
            $manager->async_manager->queue_job('analytics_aggregation', array(
                'date_range' => array(
                    'start' => date('Y-m-d'),
                    'end' => date('Y-m-d')
                )
            ), 5); // Low priority
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Optimized attribution lookup with multi-level caching
     * Replace get_conversion_attribution method  
     */
    public static function get_conversion_attribution_optimized($manager, $conversion_id) {
        // Try memory cache first
        static $memory_cache = array();
        if (isset($memory_cache[$conversion_id])) {
            return $memory_cache[$conversion_id];
        }
        
        // Try persistent cache
        if (isset($manager->performance_manager)) {
            $cache_key = 'conversion_lookup_' . $conversion_id;
            $cached_result = $manager->performance_manager->get_cache($cache_key);
            
            if ($cached_result !== false) {
                $memory_cache[$conversion_id] = $cached_result;
                return $cached_result;
            }
        }
        
        // Query database
        global $wpdb;
        $table_conversions = $wpdb->prefix . 'khm_conversion_tracking';
        
        $sql = "SELECT 
                    c.*,
                    e.utm_source, e.utm_medium, e.utm_campaign, e.utm_content, e.utm_term,
                    e.referrer_url, e.landing_page, e.ip_address, e.user_agent, e.created_at as click_timestamp
                FROM {$table_conversions} c
                LEFT JOIN {$wpdb->prefix}khm_attribution_events e ON c.click_id = e.click_id
                WHERE c.order_id = %s
                LIMIT 1";
        
        $result = $wpdb->get_row($wpdb->prepare($sql, $conversion_id), ARRAY_A);
        
        // Cache the result
        if ($result) {
            $memory_cache[$conversion_id] = $result;
            
            if (isset($manager->performance_manager)) {
                $cache_key = 'conversion_lookup_' . $conversion_id;
                $manager->performance_manager->set_cache($cache_key, $result, 7200); // 2 hour cache
            }
        }
        
        return $result;
    }
    
    /**
     * Generate comprehensive attribution explanation
     */
    private static function generate_attribution_explanation($event, $method, $confidence) {
        $explanations = array(
            'server_side_event' => 'Server-side event correlation with high confidence',
            'first_party_cookie' => 'First-party cookie matching with good reliability',
            'url_parameter' => 'URL parameter tracking with moderate confidence',
            'session_storage' => 'Session storage fallback with reduced confidence',
            'fingerprint_match' => 'Device fingerprint matching with lower confidence'
        );
        
        $base_explanation = $explanations[$method] ?? 'Unknown attribution method';
        
        $confidence_level = '';
        if ($confidence >= 0.9) {
            $confidence_level = 'very high';
        } elseif ($confidence >= 0.8) {
            $confidence_level = 'high';
        } elseif ($confidence >= 0.7) {
            $confidence_level = 'moderate';
        } elseif ($confidence >= 0.6) {
            $confidence_level = 'low';
        } else {
            $confidence_level = 'very low';
        }
        
        return sprintf(
            '%s (%s confidence: %.1f%%). Click occurred %s via %s/%s.',
            $base_explanation,
            $confidence_level,
            $confidence * 100,
            human_time_diff(strtotime($event->created_at), time()) . ' ago',
            $event->utm_source ?: 'direct',
            $event->utm_medium ?: 'unknown'
        );
    }
    
    /**
     * Check if batch processing should be used
     */
    private static function should_use_batch_processing($data) {
        // Use batch processing for regular traffic, immediate for high-priority
        $priority_indicators = array(
            'high_value_affiliate' => isset($data['affiliate_id']) && in_array($data['affiliate_id'], self::get_high_value_affiliates()),
            'vip_customer' => isset($data['user_id']) && self::is_vip_customer($data['user_id']),
            'large_order' => isset($data['value']) && $data['value'] > 1000
        );
        
        return !array_filter($priority_indicators);
    }
    
    /**
     * Get high-value affiliates (cached)
     */
    private static function get_high_value_affiliates() {
        static $high_value_affiliates = null;
        
        if ($high_value_affiliates === null) {
            // This would be configurable in admin settings
            $high_value_affiliates = get_option('khm_high_value_affiliates', array());
        }
        
        return $high_value_affiliates;
    }
    
    /**
     * Check if customer is VIP
     */
    private static function is_vip_customer($user_id) {
        if (!$user_id) return false;
        
        // Check user meta or other VIP indicators
        return get_user_meta($user_id, 'khm_vip_customer', true) === 'yes';
    }
    
    /**
     * Fallback attribution events query
     */
    private static function get_attribution_events_fallback($session_id, $user_id, $window_days) {
        global $wpdb;
        
        $table_events = $wpdb->prefix . 'khm_attribution_events';
        
        $sql = "SELECT * FROM {$table_events}
                WHERE (session_id = %s OR user_id = %d)
                AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 50";
        
        return $wpdb->get_results($wpdb->prepare($sql, $session_id, $user_id ?: 0, $window_days));
    }
    
    /**
     * Create missing database tables if needed
     */
    public static function maybe_create_attribution_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Attribution analytics table for performance
        $table_analytics = $wpdb->prefix . 'khm_attribution_analytics';
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table_analytics} (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            date DATE NOT NULL,
            affiliate_id BIGINT NOT NULL,
            utm_source VARCHAR(100),
            utm_medium VARCHAR(100),
            clicks INT DEFAULT 0,
            conversions INT DEFAULT 0,
            commission_total DECIMAL(10,2) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,
            
            UNIQUE KEY unique_daily_attribution (date, affiliate_id, utm_source, utm_medium),
            INDEX idx_date_affiliate (date, affiliate_id),
            INDEX idx_performance_lookup (affiliate_id, date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Hook to initialize performance updates when the main class is loaded
add_action('khm_attribution_manager_loaded', function($manager) {
    KHM_Attribution_Performance_Updates::init_performance_components($manager);
    KHM_Attribution_Performance_Updates::maybe_create_attribution_tables();
});
?>