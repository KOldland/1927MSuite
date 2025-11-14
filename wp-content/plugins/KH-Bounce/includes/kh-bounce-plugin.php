<?php
/**
 * Core plugin loader.
 */
class KH_Bounce_Plugin {

    /**
     * Cached settings array.
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Frontend controller.
     *
     * @var KH_Bounce_Frontend_Renderer
     */
    protected $frontend;

    /**
     * Admin controller.
     *
     * @var KH_Bounce_Admin_Settings
     */
    protected $admin;

    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

        $this->settings = $this->get_settings();

        if ( is_admin() ) {
            $this->admin = new KH_Bounce_Admin_Settings( $this );
        }

        $this->frontend = new KH_Bounce_Frontend_Renderer( $this );

        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'kh-bounce', false, basename( dirname( KH_BOUNCE_PLUGIN_FILE ) ) . '/languages' );
    }

    public function get_settings() {
        $defaults = array(
            'status'          => 'on',
            'template'        => 'classic',
            'title'           => __( 'Wait! Before you go...', 'kh-bounce' ),
            'text'            => __( 'Join our marketing insiders newsletter and get instant access to playbooks.', 'kh-bounce' ),
            'cta_label'       => __( 'Get the Playbook', 'kh-bounce' ),
            'cta_url'         => home_url( '/newsletter/' ),
            'dismiss_label'   => __( 'No thanks', 'kh-bounce' ),
            'display_on_home' => '1',
            'show_on_mobile'  => '0',
            'test_mode'       => '0',
            'telemetry_mode'  => 'none',
        );

        $settings = get_option( 'kh_bounce_settings', array() );

        return wp_parse_args( $settings, $defaults );
    }

    public function save_settings( array $settings ) {
        $settings = wp_parse_args( $settings, $this->get_settings() );
        update_option( 'kh_bounce_settings', $settings );
        $this->settings = $settings;
    }

    public function refresh_settings() {
        $this->settings = $this->get_settings();
        return $this->settings;
    }

    public function setting( $key, $default = '' ) {
        if ( empty( $this->settings ) ) {
            $this->settings = $this->get_settings();
        }
        return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
    }

    public function register_rest_routes() {
        register_rest_route( 'kh-bounce/v1', '/event', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'handle_rest_event' ),
            'permission_callback' => '__return_true',
        ) );
    }

    public function handle_rest_event( WP_REST_Request $request ) {
        if ( 'rest' !== $this->setting( 'telemetry_mode', 'none' ) ) {
            return new WP_REST_Response( array( 'status' => 'skipped' ), 202 );
        }

        $event = sanitize_text_field( $request->get_param( 'event' ) );
        $template = sanitize_text_field( $request->get_param( 'template' ) );

        do_action( 'kh_bounce_telemetry', array(
            'event'    => $event,
            'template' => $template,
            'user'     => get_current_user_id(),
            'time'     => current_time( 'mysql' ),
        ) );

        return array( 'status' => 'ok' );
    }
}
