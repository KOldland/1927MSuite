<?php
/**
 * Admin Manager for handling admin interface.
 *
 * @package KHM_SEO
 * @version 1.0.0
 */

namespace KHM_SEO\Admin;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin manager class.
 */
class AdminManager {

    /**
     * Initialize the admin manager.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_post_meta' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'KHM SEO', 'khm-seo' ),
            __( 'KHM SEO', 'khm-seo' ),
            'manage_options',
            'khm-seo',
            array( $this, 'admin_page' ),
            'dashicons-search',
            80
        );

        add_submenu_page(
            'khm-seo',
            __( 'General Settings', 'khm-seo' ),
            __( 'General', 'khm-seo' ),
            'manage_options',
            'khm-seo',
            array( $this, 'admin_page' )
        );

        add_submenu_page(
            'khm-seo',
            __( 'Titles & Meta', 'khm-seo' ),
            __( 'Titles & Meta', 'khm-seo' ),
            'manage_options',
            'khm-seo-titles',
            array( $this, 'titles_page' )
        );

        add_submenu_page(
            'khm-seo',
            __( 'XML Sitemaps', 'khm-seo' ),
            __( 'Sitemaps', 'khm-seo' ),
            'manage_options',
            'khm-seo-sitemaps',
            array( $this, 'sitemaps_page' )
        );

        add_submenu_page(
            'khm-seo',
            __( 'Schema Markup', 'khm-seo' ),
            __( 'Schema', 'khm-seo' ),
            'manage_options',
            'khm-seo-schema',
            array( $this, 'schema_page' )
        );

        add_submenu_page(
            'khm-seo',
            __( 'SEO Tools', 'khm-seo' ),
            __( 'Tools', 'khm-seo' ),
            'manage_options',
            'khm-seo-tools',
            array( $this, 'tools_page' )
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'khm_seo_general', 'khm_seo_general' );
        register_setting( 'khm_seo_titles', 'khm_seo_titles' );
        register_setting( 'khm_seo_meta', 'khm_seo_meta' );
        register_setting( 'khm_seo_sitemap', 'khm_seo_sitemap' );
        register_setting( 'khm_seo_schema', 'khm_seo_schema' );
        register_setting( 'khm_seo_tools', 'khm_seo_tools' );
    }

    /**
     * Add meta boxes to post edit screens.
     */
    public function add_meta_boxes() {
        $post_types = get_post_types( array( 'public' => true ) );
        
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'khm-seo-meta',
                __( 'KHM SEO', 'khm-seo' ),
                array( $this, 'meta_box_callback' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Meta box callback.
     *
     * @param object $post Post object.
     */
    public function meta_box_callback( $post ) {
        wp_nonce_field( 'khm_seo_meta_box', 'khm_seo_meta_box_nonce' );
        
        // Get current values
        $title = get_post_meta( $post->ID, '_khm_seo_title', true );
        $description = get_post_meta( $post->ID, '_khm_seo_description', true );
        $keywords = get_post_meta( $post->ID, '_khm_seo_keywords', true );
        $robots = get_post_meta( $post->ID, '_khm_seo_robots', true );
        $canonical = get_post_meta( $post->ID, '_khm_seo_canonical', true );
        $focus_keyword = get_post_meta( $post->ID, '_khm_seo_focus_keyword', true );

        echo '<div id="khm-seo-meta-box">';
        
        // SEO Title
        echo '<div class="khm-seo-field">';
        echo '<label for="khm_seo_title"><strong>' . __( 'SEO Title', 'khm-seo' ) . '</strong></label>';
        echo '<input type="text" id="khm_seo_title" name="khm_seo_title" value="' . esc_attr( $title ) . '" class="widefat" />';
        echo '<p class="description">' . __( 'Recommended length: 50-60 characters', 'khm-seo' ) . '</p>';
        echo '</div>';

        // Meta Description
        echo '<div class="khm-seo-field">';
        echo '<label for="khm_seo_description"><strong>' . __( 'Meta Description', 'khm-seo' ) . '</strong></label>';
        echo '<textarea id="khm_seo_description" name="khm_seo_description" rows="3" class="widefat">' . esc_textarea( $description ) . '</textarea>';
        echo '<p class="description">' . __( 'Recommended length: 150-160 characters', 'khm-seo' ) . '</p>';
        echo '</div>';

        // Focus Keyword
        echo '<div class="khm-seo-field">';
        echo '<label for="khm_seo_focus_keyword"><strong>' . __( 'Focus Keyword', 'khm-seo' ) . '</strong></label>';
        echo '<input type="text" id="khm_seo_focus_keyword" name="khm_seo_focus_keyword" value="' . esc_attr( $focus_keyword ) . '" class="widefat" />';
        echo '<p class="description">' . __( 'The main keyword you want to rank for with this content', 'khm-seo' ) . '</p>';
        echo '</div>';

        // Keywords
        echo '<div class="khm-seo-field">';
        echo '<label for="khm_seo_keywords"><strong>' . __( 'Keywords', 'khm-seo' ) . '</strong></label>';
        echo '<input type="text" id="khm_seo_keywords" name="khm_seo_keywords" value="' . esc_attr( $keywords ) . '" class="widefat" />';
        echo '<p class="description">' . __( 'Comma-separated list of keywords', 'khm-seo' ) . '</p>';
        echo '</div>';

        // Robots
        echo '<div class="khm-seo-field">';
        echo '<label for="khm_seo_robots"><strong>' . __( 'Robots Meta', 'khm-seo' ) . '</strong></label>';
        echo '<select id="khm_seo_robots" name="khm_seo_robots" class="widefat">';
        echo '<option value=""' . selected( $robots, '', false ) . '>' . __( 'Default', 'khm-seo' ) . '</option>';
        echo '<option value="noindex"' . selected( $robots, 'noindex', false ) . '>' . __( 'No Index', 'khm-seo' ) . '</option>';
        echo '<option value="nofollow"' . selected( $robots, 'nofollow', false ) . '>' . __( 'No Follow', 'khm-seo' ) . '</option>';
        echo '<option value="noindex,nofollow"' . selected( $robots, 'noindex,nofollow', false ) . '>' . __( 'No Index, No Follow', 'khm-seo' ) . '</option>';
        echo '</select>';
        echo '</div>';

        // Canonical URL
        echo '<div class="khm-seo-field">';
        echo '<label for="khm_seo_canonical"><strong>' . __( 'Canonical URL', 'khm-seo' ) . '</strong></label>';
        echo '<input type="url" id="khm_seo_canonical" name="khm_seo_canonical" value="' . esc_attr( $canonical ) . '" class="widefat" />';
        echo '<p class="description">' . __( 'Leave blank to use default permalink', 'khm-seo' ) . '</p>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Save post meta.
     *
     * @param int $post_id Post ID.
     */
    public function save_post_meta( $post_id ) {
        // Verify nonce
        if ( ! isset( $_POST['khm_seo_meta_box_nonce'] ) || 
             ! wp_verify_nonce( $_POST['khm_seo_meta_box_nonce'], 'khm_seo_meta_box' ) ) {
            return;
        }

        // Check if user has permission
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save meta fields
        $fields = array( 'title', 'description', 'keywords', 'robots', 'canonical', 'focus_keyword' );
        
        foreach ( $fields as $field ) {
            $meta_key = '_khm_seo_' . $field;
            $value = isset( $_POST[ 'khm_seo_' . $field ] ) ? sanitize_text_field( $_POST[ 'khm_seo_' . $field ] ) : '';
            
            if ( 'description' === $field ) {
                $value = sanitize_textarea_field( $_POST[ 'khm_seo_' . $field ] );
            }
            
            update_post_meta( $post_id, $meta_key, $value );
        }
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook_suffix Admin page hook suffix.
     */
    public function enqueue_admin_scripts( $hook_suffix ) {
        // Only load on KHM SEO pages and post edit screens
        if ( strpos( $hook_suffix, 'khm-seo' ) !== false || 
             in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
            
            wp_enqueue_style( 
                'khm-seo-admin', 
                KHM_SEO_PLUGIN_URL . 'assets/css/admin.css', 
                array(), 
                KHM_SEO_VERSION 
            );
            
            wp_enqueue_script( 
                'khm-seo-admin', 
                KHM_SEO_PLUGIN_URL . 'assets/js/admin.js', 
                array( 'jquery' ), 
                KHM_SEO_VERSION, 
                true 
            );
            
            // Localize script
            wp_localize_script( 'khm-seo-admin', 'khmSeo', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'khm_seo_ajax' ),
                'strings'  => array(
                    'analyzing' => __( 'Analyzing...', 'khm-seo' ),
                    'good'      => __( 'Good', 'khm-seo' ),
                    'needs_improvement' => __( 'Needs Improvement', 'khm-seo' ),
                    'poor'      => __( 'Poor', 'khm-seo' )
                )
            ) );
        }
    }

    /**
     * Main admin page.
     */
    public function admin_page() {
        include KHM_SEO_PLUGIN_DIR . 'templates/admin/general.php';
    }

    /**
     * Titles & Meta admin page.
     */
    public function titles_page() {
        include KHM_SEO_PLUGIN_DIR . 'templates/admin/titles.php';
    }

    /**
     * Sitemaps admin page.
     */
    public function sitemaps_page() {
        include KHM_SEO_PLUGIN_DIR . 'templates/admin/sitemaps.php';
    }

    /**
     * Schema admin page.
     */
    public function schema_page() {
        include KHM_SEO_PLUGIN_DIR . 'templates/admin/schema.php';
    }

    /**
     * Tools admin page.
     */
    public function tools_page() {
        include KHM_SEO_PLUGIN_DIR . 'templates/admin/tools.php';
    }
}