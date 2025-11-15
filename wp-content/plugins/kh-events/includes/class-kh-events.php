<?php
/**
 * Main KH Events class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Events {

    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));

        // Include meta classes
        require_once KH_EVENTS_DIR . 'includes/class-kh-event-meta.php';
        require_once KH_EVENTS_DIR . 'includes/class-kh-location-meta.php';
        require_once KH_EVENTS_DIR . 'includes/class-kh-events-views.php';
        require_once KH_EVENTS_DIR . 'includes/class-kh-event-tickets.php';
        require_once KH_EVENTS_DIR . 'includes/class-kh-event-bookings.php';
        require_once KH_EVENTS_DIR . 'includes/class-kh-recurring-events.php';

        new KH_Event_Meta();
        new KH_Location_Meta();
        new KH_Events_Views();
        new KH_Event_Tickets();
        new KH_Event_Bookings();
        new KH_Recurring_Events();
    }

    public function init() {
        // Register custom post types
        $this->register_post_types();

        // Load textdomain
        load_plugin_textdomain('kh-events', false, dirname(KH_EVENTS_BASENAME) . '/languages/');
    }

    private function register_post_types() {
        // Event post type
        register_post_type('kh_event', array(
            'labels' => array(
                'name' => __('Events', 'kh-events'),
                'singular_name' => __('Event', 'kh-events'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
        ));

        // Location post type
        register_post_type('kh_location', array(
            'labels' => array(
                'name' => __('Locations', 'kh-events'),
                'singular_name' => __('Location', 'kh-events'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true,
        ));

        // Booking post type
        register_post_type('kh_booking', array(
            'labels' => array(
                'name' => __('Bookings', 'kh-events'),
                'singular_name' => __('Booking', 'kh-events'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'kh-events',
            'supports' => array('title'),
            'capability_type' => 'post',
        ));
    }

    public function admin_menu() {
        add_menu_page(
            __('KH Events', 'kh-events'),
            __('KH Events', 'kh-events'),
            'manage_options',
            'kh-events',
            array($this, 'admin_page'),
            'dashicons-calendar-alt',
            30
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('KH Events', 'kh-events'); ?></h1>
            <p><?php _e('Welcome to KH Events - your comprehensive event management solution.', 'kh-events'); ?></p>
        </div>
        <?php
    }

    public static function activate() {
        // Activation tasks
        flush_rewrite_rules();
    }

    public static function deactivate() {
        // Deactivation tasks
        flush_rewrite_rules();
    }
}