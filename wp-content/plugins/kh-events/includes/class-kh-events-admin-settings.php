<?php
/**
 * KH Events Admin Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Events_Admin_Settings {

    private static $instance = null;
    private $settings_page = 'kh-events-settings';

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function add_settings_page() {
        add_submenu_page(
            'kh-events',
            __('Settings', 'kh-events'),
            __('Settings', 'kh-events'),
            'manage_options',
            $this->settings_page,
            array($this, 'settings_page_callback')
        );
    }

    public function register_settings() {
        // General Settings
        register_setting('kh_events_general', 'kh_events_general_settings', array($this, 'sanitize_general_settings'));

        add_settings_section(
            'kh_events_general_section',
            __('General Settings', 'kh-events'),
            array($this, 'general_section_callback'),
            'kh_events_general'
        );

        add_settings_field(
            'kh_events_currency',
            __('Currency', 'kh-events'),
            array($this, 'currency_field_callback'),
            'kh_events_general',
            'kh_events_general_section'
        );

        add_settings_field(
            'kh_events_date_format',
            __('Date Format', 'kh-events'),
            array($this, 'date_format_field_callback'),
            'kh_events_general',
            'kh_events_general_section'
        );

        add_settings_field(
            'kh_events_time_format',
            __('Time Format', 'kh-events'),
            array($this, 'time_format_field_callback'),
            'kh_events_general',
            'kh_events_general_section'
        );

        // Google Maps Settings
        register_setting('kh_events_maps', 'kh_events_maps_settings', array($this, 'sanitize_maps_settings'));

        add_settings_section(
            'kh_events_maps_section',
            __('Google Maps Settings', 'kh-events'),
            array($this, 'maps_section_callback'),
            'kh_events_maps'
        );

        add_settings_field(
            'kh_events_google_maps_api_key',
            __('Google Maps API Key', 'kh-events'),
            array($this, 'google_maps_api_key_field_callback'),
            'kh_events_maps',
            'kh_events_maps_section'
        );

        add_settings_field(
            'kh_events_default_map_zoom',
            __('Default Map Zoom', 'kh-events'),
            array($this, 'default_map_zoom_field_callback'),
            'kh_events_maps',
            'kh_events_maps_section'
        );

        // Email Settings
        register_setting('kh_events_email', 'kh_events_email_settings', array($this, 'sanitize_email_settings'));

        add_settings_section(
            'kh_events_email_section',
            __('Email Settings', 'kh-events'),
            array($this, 'email_section_callback'),
            'kh_events_email'
        );

        add_settings_field(
            'kh_events_from_email',
            __('From Email', 'kh-events'),
            array($this, 'from_email_field_callback'),
            'kh_events_email',
            'kh_events_email_section'
        );

        add_settings_field(
            'kh_events_from_name',
            __('From Name', 'kh-events'),
            array($this, 'from_name_field_callback'),
            'kh_events_email',
            'kh_events_email_section'
        );

        add_settings_field(
            'kh_events_booking_confirmation',
            __('Booking Confirmation Email', 'kh-events'),
            array($this, 'booking_confirmation_field_callback'),
            'kh_events_email',
            'kh_events_email_section'
        );

        // Booking Settings
        register_setting('kh_events_booking', 'kh_events_booking_settings', array($this, 'sanitize_booking_settings'));

        add_settings_section(
            'kh_events_booking_section',
            __('Booking Settings', 'kh-events'),
            array($this, 'booking_section_callback'),
            'kh_events_booking'
        );

        add_settings_field(
            'kh_events_allow_guest_bookings',
            __('Allow Guest Bookings', 'kh-events'),
            array($this, 'allow_guest_bookings_field_callback'),
            'kh_events_booking',
            'kh_events_booking_section'
        );

        add_settings_field(
            'kh_events_booking_cutoff_hours',
            __('Booking Cutoff (hours before event)', 'kh-events'),
            array($this, 'booking_cutoff_field_callback'),
            'kh_events_booking',
            'kh_events_booking_section'
        );

        // Display Settings
        register_setting('kh_events_display', 'kh_events_display_settings', array($this, 'sanitize_display_settings'));

        add_settings_section(
            'kh_events_display_section',
            __('Display Settings', 'kh-events'),
            array($this, 'display_section_callback'),
            'kh_events_display'
        );

        add_settings_field(
            'kh_events_events_per_page',
            __('Events Per Page (List View)', 'kh-events'),
            array($this, 'events_per_page_field_callback'),
            'kh_events_display',
            'kh_events_display_section'
        );

        // Payment Settings
        register_setting('kh_events_payment', 'kh_events_payment_settings', array($this, 'sanitize_payment_settings'));

        add_settings_section(
            'kh_events_payment_section',
            __('Payment Settings', 'kh-events'),
            array($this, 'payment_section_callback'),
            'kh_events_payment'
        );

        add_settings_field(
            'kh_events_enable_payments',
            __('Enable Payments', 'kh-events'),
            array($this, 'enable_payments_field_callback'),
            'kh_events_payment',
            'kh_events_payment_section'
        );

        add_settings_field(
            'kh_events_default_gateway',
            __('Default Gateway', 'kh-events'),
            array($this, 'default_gateway_field_callback'),
            'kh_events_payment',
            'kh_events_payment_section'
        );

        add_settings_field(
            'kh_events_payment_description',
            __('Payment Description', 'kh-events'),
            array($this, 'payment_description_field_callback'),
            'kh_events_payment',
            'kh_events_payment_section'
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, $this->settings_page) !== false) {
            wp_enqueue_script('kh-events-admin', KH_EVENTS_URL . 'assets/js/kh-events-admin.js', array('jquery'), KH_EVENTS_VERSION, true);
            wp_enqueue_style('kh-events-admin', KH_EVENTS_URL . 'assets/css/kh-events-admin.css', array(), KH_EVENTS_VERSION);
        }
    }

    public function settings_page_callback() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'general';
        $tabs = array(
            'general' => __('General', 'kh-events'),
            'maps' => __('Google Maps', 'kh-events'),
            'email' => __('Email', 'kh-events'),
            'booking' => __('Booking', 'kh-events'),
            'display' => __('Display', 'kh-events'),
            'payment' => __('Payment', 'kh-events'),
        );

        ?>
        <div class="wrap">
            <h1><?php _e('KH Events Settings', 'kh-events'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_key => $tab_caption) : ?>
                    <a href="?page=<?php echo $this->settings_page; ?>&tab=<?php echo $tab_key; ?>" class="nav-tab <?php echo $active_tab == $tab_key ? 'nav-tab-active' : ''; ?>"><?php echo $tab_caption; ?></a>
                <?php endforeach; ?>
            </h2>

            <form method="post" action="options.php">
                <?php
                switch ($active_tab) {
                    case 'maps':
                        settings_fields('kh_events_maps');
                        do_settings_sections('kh_events_maps');
                        break;
                    case 'email':
                        settings_fields('kh_events_email');
                        do_settings_sections('kh_events_email');
                        break;
                    case 'booking':
                        settings_fields('kh_events_booking');
                        do_settings_sections('kh_events_booking');
                        break;
                    case 'display':
                        settings_fields('kh_events_display');
                        do_settings_sections('kh_events_display');
                        break;
                    case 'payment':
                        settings_fields('kh_events_payment');
                        do_settings_sections('kh_events_payment');
                        break;
                    default:
                        settings_fields('kh_events_general');
                        do_settings_sections('kh_events_general');
                        break;
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // Section Callbacks
    public function general_section_callback() {
        echo '<p>' . __('Configure general settings for the KH Events plugin.', 'kh-events') . '</p>';
    }

    public function maps_section_callback() {
        echo '<p>' . __('Configure Google Maps integration settings.', 'kh-events') . '</p>';
        echo '<p>' . __('Get your API key from the <a href="https://console.developers.google.com/" target="_blank">Google Cloud Console</a>.', 'kh-events') . '</p>';
    }

    public function email_section_callback() {
        echo '<p>' . __('Configure email settings for notifications and confirmations.', 'kh-events') . '</p>';
    }

    public function booking_section_callback() {
        echo '<p>' . __('Configure booking and registration settings.', 'kh-events') . '</p>';
    }

    public function display_section_callback() {
        echo '<p>' . __('Configure how events are displayed on your site.', 'kh-events') . '</p>';
    }

    // Field Callbacks
    public function currency_field_callback() {
        $options = get_option('kh_events_general_settings');
        $currency = isset($options['currency']) ? $options['currency'] : 'USD';
        $currencies = array(
            'USD' => __('US Dollar ($)', 'kh-events'),
            'EUR' => __('Euro (€)', 'kh-events'),
            'GBP' => __('British Pound (£)', 'kh-events'),
            'JPY' => __('Japanese Yen (¥)', 'kh-events'),
            'CAD' => __('Canadian Dollar (C$)', 'kh-events'),
            'AUD' => __('Australian Dollar (A$)', 'kh-events'),
        );
        echo '<select name="kh_events_general_settings[currency]" id="kh_events_currency">';
        foreach ($currencies as $code => $name) {
            echo '<option value="' . $code . '" ' . selected($currency, $code, false) . '>' . $name . '</option>';
        }
        echo '</select>';
    }

    public function date_format_field_callback() {
        $options = get_option('kh_events_general_settings');
        $date_format = isset($options['date_format']) ? $options['date_format'] : 'Y-m-d';
        $formats = array(
            'Y-m-d' => '2025-11-15',
            'm/d/Y' => '11/15/2025',
            'd/m/Y' => '15/11/2025',
            'F j, Y' => 'November 15, 2025',
        );
        echo '<select name="kh_events_general_settings[date_format]" id="kh_events_date_format">';
        foreach ($formats as $format => $example) {
            echo '<option value="' . $format . '" ' . selected($date_format, $format, false) . '>' . $example . '</option>';
        }
        echo '</select>';
    }

    public function time_format_field_callback() {
        $options = get_option('kh_events_general_settings');
        $time_format = isset($options['time_format']) ? $options['time_format'] : 'H:i';
        $formats = array(
            'H:i' => '14:30',
            'h:i A' => '2:30 PM',
            'h:i a' => '2:30 pm',
            'g:i A' => '2:30 PM',
        );
        echo '<select name="kh_events_general_settings[time_format]" id="kh_events_time_format">';
        foreach ($formats as $format => $example) {
            echo '<option value="' . $format . '" ' . selected($time_format, $format, false) . '>' . $example . '</option>';
        }
        echo '</select>';
    }

    public function google_maps_api_key_field_callback() {
        $options = get_option('kh_events_maps_settings');
        $api_key = isset($options['google_maps_api_key']) ? $options['google_maps_api_key'] : '';
        echo '<input type="password" name="kh_events_maps_settings[google_maps_api_key]" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Your Google Maps API key. Required for location maps.', 'kh-events') . '</p>';
    }

    public function default_map_zoom_field_callback() {
        $options = get_option('kh_events_maps_settings');
        $zoom = isset($options['default_map_zoom']) ? $options['default_map_zoom'] : '14';
        echo '<input type="number" name="kh_events_maps_settings[default_map_zoom]" value="' . esc_attr($zoom) . '" min="1" max="20" class="small-text" />';
        echo '<p class="description">' . __('Default zoom level for location maps (1-20).', 'kh-events') . '</p>';
    }

    public function from_email_field_callback() {
        $options = get_option('kh_events_email_settings');
        $from_email = isset($options['from_email']) ? $options['from_email'] : get_option('admin_email');
        echo '<input type="email" name="kh_events_email_settings[from_email]" value="' . esc_attr($from_email) . '" class="regular-text" />';
        echo '<p class="description">' . __('Email address for sending notifications.', 'kh-events') . '</p>';
    }

    public function from_name_field_callback() {
        $options = get_option('kh_events_email_settings');
        $from_name = isset($options['from_name']) ? $options['from_name'] : get_option('blogname');
        echo '<input type="text" name="kh_events_email_settings[from_name]" value="' . esc_attr($from_name) . '" class="regular-text" />';
        echo '<p class="description">' . __('Name for sending notifications.', 'kh-events') . '</p>';
    }

    public function booking_confirmation_field_callback() {
        $options = get_option('kh_events_email_settings');
        $enabled = isset($options['booking_confirmation']) ? $options['booking_confirmation'] : '1';
        echo '<input type="checkbox" name="kh_events_email_settings[booking_confirmation]" value="1" ' . checked($enabled, '1', false) . ' />';
        echo '<label>' . __('Send booking confirmation emails to attendees.', 'kh-events') . '</label>';
    }

    public function allow_guest_bookings_field_callback() {
        $options = get_option('kh_events_booking_settings');
        $enabled = isset($options['allow_guest_bookings']) ? $options['allow_guest_bookings'] : '0';
        echo '<input type="checkbox" name="kh_events_booking_settings[allow_guest_bookings]" value="1" ' . checked($enabled, '1', false) . ' />';
        echo '<label>' . __('Allow non-registered users to make bookings.', 'kh-events') . '</label>';
    }

    public function booking_cutoff_field_callback() {
        $options = get_option('kh_events_booking_settings');
        $cutoff = isset($options['booking_cutoff_hours']) ? $options['booking_cutoff_hours'] : '24';
        echo '<input type="number" name="kh_events_booking_settings[booking_cutoff_hours]" value="' . esc_attr($cutoff) . '" min="0" class="small-text" />';
        echo '<p class="description">' . __('Hours before event when booking closes. Set to 0 for no cutoff.', 'kh-events') . '</p>';
    }

    public function events_per_page_field_callback() {
        $options = get_option('kh_events_display_settings');
        $per_page = isset($options['events_per_page']) ? $options['events_per_page'] : '10';
        echo '<input type="number" name="kh_events_display_settings[events_per_page]" value="' . esc_attr($per_page) . '" min="1" max="100" class="small-text" />';
        echo '<p class="description">' . __('Number of events to show per page in list view.', 'kh-events') . '</p>';
    }

    public function show_past_events_field_callback() {
        $options = get_option('kh_events_display_settings');
        $enabled = isset($options['show_past_events']) ? $options['show_past_events'] : '0';
        echo '<input type="checkbox" name="kh_events_display_settings[show_past_events]" value="1" ' . checked($enabled, '1', false) . ' />';
        echo '<label>' . __('Include past events in list and calendar views.', 'kh-events') . '</label>';
    }

    // Sanitization Callbacks
    public function sanitize_general_settings($input) {
        $sanitized = array();
        $sanitized['currency'] = sanitize_text_field($input['currency'] ?? 'USD');
        $sanitized['date_format'] = sanitize_text_field($input['date_format'] ?? 'Y-m-d');
        $sanitized['time_format'] = sanitize_text_field($input['time_format'] ?? 'H:i');
        return $sanitized;
    }

    public function sanitize_maps_settings($input) {
        $sanitized = array();
        $sanitized['google_maps_api_key'] = sanitize_text_field($input['google_maps_api_key'] ?? '');
        $sanitized['default_map_zoom'] = absint($input['default_map_zoom'] ?? 14);
        return $sanitized;
    }

    public function sanitize_email_settings($input) {
        $sanitized = array();
        $sanitized['from_email'] = sanitize_email($input['from_email'] ?? '');
        $sanitized['from_name'] = sanitize_text_field($input['from_name'] ?? '');
        $sanitized['booking_confirmation'] = isset($input['booking_confirmation']) ? '1' : '0';
        return $sanitized;
    }

    public function sanitize_booking_settings($input) {
        $sanitized = array();
        $sanitized['allow_guest_bookings'] = isset($input['allow_guest_bookings']) ? '1' : '0';
        $sanitized['booking_cutoff_hours'] = absint($input['booking_cutoff_hours'] ?? 24);
        return $sanitized;
    }

    public function sanitize_display_settings($input) {
        $sanitized = array();
        $sanitized['events_per_page'] = absint($input['events_per_page'] ?? 10);
        $sanitized['show_past_events'] = isset($input['show_past_events']) ? '1' : '0';
        return $sanitized;
    }

    public function payment_section_callback() {
        echo '<p>' . __('Configure payment processing settings for event bookings.', 'kh-events') . '</p>';
    }

    public function enable_payments_field_callback() {
        $options = get_option('kh_events_payment_settings');
        $enabled = isset($options['enable_payments']) ? $options['enable_payments'] : '0';
        echo '<input type="checkbox" name="kh_events_payment_settings[enable_payments]" value="1" ' . checked($enabled, '1', false) . ' />';
        echo '<label>' . __('Enable payment processing for event bookings.', 'kh-events') . '</label>';
    }

    public function default_gateway_field_callback() {
        $options = get_option('kh_events_payment_settings');
        $default_gateway = isset($options['default_gateway']) ? $options['default_gateway'] : 'stripe';

        if (function_exists('KH_Payment_Handler')) {
            $handler = KH_Payment_Handler::instance();
            $gateways = $handler->get_available_gateways();

            echo '<select name="kh_events_payment_settings[default_gateway]" id="kh_events_default_gateway">';
            echo '<option value="">' . __('Select Default Gateway', 'kh-events') . '</option>';

            foreach ($gateways as $gateway_id => $gateway) {
                echo '<option value="' . $gateway_id . '" ' . selected($default_gateway, $gateway_id, false) . '>' . $gateway->get_gateway_name() . '</option>';
            }
            echo '</select>';
        } else {
            echo '<p>' . __('Payment handler not available.', 'kh-events') . '</p>';
        }
    }

    public function payment_description_field_callback() {
        $options = get_option('kh_events_payment_settings');
        $description = isset($options['payment_description']) ? $options['payment_description'] : __('Event booking payment', 'kh-events');
        echo '<input type="text" name="kh_events_payment_settings[payment_description]" value="' . esc_attr($description) . '" class="regular-text" />';
        echo '<p class="description">' . __('Default description for payment transactions.', 'kh-events') . '</p>';
    }

    public function sanitize_payment_settings($input) {
        $sanitized = array();
        $sanitized['enable_payments'] = isset($input['enable_payments']) ? '1' : '0';
        $sanitized['default_gateway'] = sanitize_text_field($input['default_gateway'] ?? '');
        $sanitized['payment_description'] = sanitize_text_field($input['payment_description'] ?? '');
        return $sanitized;
    }

    // Helper methods
    public static function get_option($key, $default = '') {
        $option_name = 'kh_events_' . $key . '_settings';
        $options = get_option($option_name, array());
        return $options[$key] ?? $default;
    }

    public static function get_general_option($key, $default = '') {
        return self::get_option('general', $key, $default);
    }

    public static function get_maps_option($key, $default = '') {
        return self::get_option('maps', $key, $default);
    }

    public static function get_email_option($key, $default = '') {
        return self::get_option('email', $key, $default);
    }

    public static function get_booking_option($key, $default = '') {
        return self::get_option('booking', $key, $default);
    }

    public static function get_display_option($key, $default = '') {
        return self::get_option('display', $key, $default);
    }
}