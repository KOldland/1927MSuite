<?php
/**
 * Elementor Integration Manager
 *
 * Integrates KHM SEO GEO functionality with Elementor page builder.
 * Provides entity autocomplete and custom widgets for GEO features.
 *
 * @package KHM_SEO\Elementor
 * @since 2.0.0
 */

namespace KHM_SEO\Elementor;

use KHM_SEO\GEO\GEOManager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Elementor Integration Class
 * Manages Elementor integration for GEO features
 */
class ElementorIntegration {

    /**
     * @var GEOManager GEO manager instance
     */
    private $geo_manager;

    /**
     * Constructor
     */
    public function __construct( GEOManager $geo_manager ) {
        $this->geo_manager = $geo_manager;
        $this->init();
    }

    /**
     * Initialize Elementor integration
     */
    public function init() {
        // Check if Elementor is active
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Register widget category
        add_action( 'elementor/elements/categories_registered', array( $this, 'register_widget_category' ) );

        // Register widgets
        add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );

        // Register controls
        add_action( 'elementor/controls/register', array( $this, 'register_controls' ) );

        // Enqueue scripts and styles
        add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'enqueue_editor_scripts' ) );
        add_action( 'elementor/frontend/after_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );

        // Register AJAX handlers for entity autocomplete
        add_action( 'wp_ajax_khm_geo_entity_search', array( $this, 'ajax_entity_search' ) );
    }

    /**
     * Register widget category
     */
    public function register_widget_category( $elements_manager ) {
        $elements_manager->add_category(
            'khm-seo',
            array(
                'title' => __( 'KHM SEO', 'khm-seo' ),
                'icon' => 'fa fa-search',
            )
        );
    }

    /**
     * Register Elementor widgets
     */
    public function register_widgets( $widgets_manager ) {
        // Register AnswerCard widget with entity autocomplete
        require_once __DIR__ . '/widgets/AnswerCard.php';
        $widgets_manager->register( new \KHM_SEO\Elementor\Widgets\AnswerCard() );

        // Register Client Badge widget
        require_once __DIR__ . '/widgets/ClientBadge.php';
        $widgets_manager->register( new \KHM_SEO\Elementor\Widgets\ClientBadge() );
    }

    /**
     * Register custom controls
     */
    public function register_controls( $controls_manager ) {
        // Register entity autocomplete control
        require_once __DIR__ . '/controls/EntityAutocomplete.php';
        $controls_manager->register( new \KHM_SEO\Elementor\Controls\EntityAutocomplete() );
    }

    /**
     * Enqueue editor scripts
     */
    public function enqueue_editor_scripts() {
        wp_enqueue_script(
            'khm-geo-elementor-editor',
            KHM_SEO_PLUGIN_URL . 'assets/js/elementor-editor.js',
            array( 'jquery', 'elementor-editor' ),
            KHM_SEO_VERSION,
            true
        );

        wp_localize_script( 'khm-geo-elementor-editor', 'khmGeoElementor', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'khm_seo_ajax' ),
            'strings' => array(
                'search_entities' => __( 'Search entities...', 'khm-seo' ),
                'no_entities_found' => __( 'No entities found', 'khm-seo' ),
                'select_entity' => __( 'Select entity', 'khm-seo' ),
            ),
        ) );
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'khm-geo-elementor-frontend',
            KHM_SEO_PLUGIN_URL . 'assets/css/elementor-frontend.css',
            array(),
            KHM_SEO_VERSION
        );
    }

    /**
     * AJAX handler for entity search
     */
    public function ajax_entity_search() {
        check_ajax_referer( 'khm_seo_ajax', 'nonce' );

        $search = sanitize_text_field( $_POST['search'] ?? '' );
        $limit = intval( $_POST['limit'] ?? 10 );

        if ( empty( $search ) ) {
            wp_send_json_error( 'Search term required' );
        }

        $entities = $this->geo_manager->get_entity_manager()->search_entities( array(
            'search' => $search,
            'status' => 'active',
            'limit' => $limit,
        ) );

        $results = array();
        foreach ( $entities as $entity ) {
            $results[] = array(
                'id' => $entity->id,
                'text' => $entity->canonical,
                'type' => $entity->type,
                'url' => get_permalink( $entity->id ),
            );
        }

        wp_send_json_success( $results );
    }
}
