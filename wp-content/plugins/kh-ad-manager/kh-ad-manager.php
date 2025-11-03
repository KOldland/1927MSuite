<?php
/*
Plugin Name: KH Ad Manager
Description: Custom Ad Manager for Touchpoint theme. Modular, clean, and no monkeys allowed.
Version: 0.1
Author: Kirsty Hennah
*/

defined('ABSPATH') || exit;

// Define paths
define('AM_PATH', plugin_dir_path(__FILE__));
define('AM_URL', plugin_dir_url(__FILE__));
define('AM_FIELD_AD_SLOT', 'field_68652678baa37');


// Core includes
require_once AM_PATH . 'includes/cpt-ad-unit.php';
require_once AM_PATH . 'includes/ad-helper.php';
require_once AM_PATH . 'includes/tracking.php';
require_once AM_PATH . 'includes/admin-columns.php';
require_once AM_PATH . 'includes/render-ad.php';
require_once AM_PATH . 'includes/ad-slot-tax.php';
require_once AM_PATH . 'includes/ad-preview.php';

// Elementor widget registration — safely and lazily
add_action('elementor/widgets/register', function($widgets_manager) {
    if (!did_action('elementor/loaded') || !class_exists('\Elementor\Widget_Base')) {
        return;
    }

    require_once AM_PATH . 'widgets/class-ad-widget.php';

    if (class_exists('KH_Elementor_Ad_Widget')) {
        $widgets_manager->register(new \KH_Elementor_Ad_Widget());
    }
});

// Admin UI cleanup
add_action('add_meta_boxes', function() {
    remove_meta_box('ad-slotdiv', 'ad_unit', 'side');
    remove_meta_box('categorydiv', 'ad_unit', 'side');
}, 99);

add_action('admin_head', function() {
    echo '<style>
        #ad-slot-ad_unit .category-add, 
        #ad-slot-ad_unit .tagcloud {
            display: none !important;
        }
    </style>';
});

// ACF: Validate image dimensions based on slot rules
    add_filter('acf/validate_value/name=ad_image', function($valid, $value, $field, $input) {
        if (!$valid || !$value) return $valid;
        
        $acf_field_key = AM_FIELD_AD_SLOT;
        $ad_slots = $_POST['acf'][$acf_field_key] ?? [];
        error_log('ACF POST dump: ' . print_r($_POST['acf'], true));
        
        if (empty($ad_slots)) return 'Please select at least one Ad Slot.';
        
        $img = wp_get_attachment_metadata($value);
        if (!$img || empty($img['width']) || empty($img['height'])) return $valid;
        
        $rules = kh_get_slot_exact_dimensions();
        $imageSlots = array_keys($rules); // <- Safe zone
        
        foreach ($ad_slots as $slot_id) {
            $term = get_term($slot_id, 'ad-slot');
            if (!$term || is_wp_error($term)) continue;
            
            $slug = $term->slug;
            if (!in_array($slug, $imageSlots)) continue;
            
            $expected = $rules[$slug];
            $w = $img['width'];
            $h = $img['height'];
            
            if ($w != $expected['width'] || $h != $expected['height']) {
                return "Image must be exactly {$expected['width']}×{$expected['height']} pixels for slot: {$slug}. Yours is {$w}×{$h}. Do better.";
            }
        }
        
        return $valid;
    }, 10, 4);



// Enqueue frontend assets
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('ad-manager-frontend', AM_URL . 'assets/ad-manager-frontend.css', [], '0.1');
    wp_enqueue_script('ad-manager-frontend', AM_URL . 'assets/ad-manager-frontend.js', [], '0.1', true);
});

// Enqueue admin assets on ad_unit screen only
add_action('admin_enqueue_scripts', function() {
    $screen = get_current_screen();
    if ($screen && $screen->post_type === 'ad_unit') {
        wp_enqueue_style('ad-manager-admin', AM_URL . 'assets/ad-manager-admin.css', [], '0.1');
        wp_enqueue_script('ad-manager-admin', AM_URL . 'assets/ad-manager-admin.js', ['jquery'], '0.1', true);
    }
});

// Optional public footer injection
add_action('wp_footer', function() {
    include AM_PATH . 'partials/ads-popups.php';
});

// Ad options page menu
    add_action('acf/init', function() {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page([
                'page_title' => 'KH Ad Settings',
                'menu_title' => 'Ad Settings',
                'menu_slug'  => 'kh-ad-settings',
                'capability' => 'edit_posts',
                'redirect'   => false
            ]);
        }
    });

// Move Ad Slot Overrides field group to the editor sidebar
    add_filter('acf/render_field/key=field_68652678baa37', function($field) {
        if (!empty($field['value']) && is_numeric($field['value'])) {
            $term = get_term($field['value'], 'ad-slot');
            if ($term && !is_wp_error($term)) {
                echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    const slotField = document.querySelector('#acf-field_{$field['key']}');
                    if (slotField) slotField.setAttribute('data-selected-slot', '{$term->slug}');
                });
            </script>";
            }
        }
        return $field;
    });


add_filter('acf/location/screen', function($screen) {
    if (!empty($screen['post_type']) && $screen['post_type'] === 'post') {
        // Tell ACF to use metabox position in the block editor
        $screen['block_editor'] = true;
    }
    return $screen;
});
    
    
    add_filter('acf/location/rule_match', function($match, $rule, $screen) {
        if ($rule['param'] !== 'post_type' || $rule['value'] !== 'ad_unit') return $match;
        
        $post_id = $screen['post_id'] ?? get_the_ID();
        
        // Allow if new post (so ACF UI doesn't break)
        if (!$post_id || str_starts_with($post_id, 'new_')) {
            return true;
        }
        
        // Only show if one of the special slots is selected
        $terms = wp_get_post_terms($post_id, 'ad-slot', ['fields' => 'slugs']);
        return in_array('slide-in', $terms) || in_array('pop-up', $terms);
    }, 10, 3);
