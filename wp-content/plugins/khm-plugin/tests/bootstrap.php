<?php

require __DIR__ . '/../vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'ARRAY_A' ) ) {
    define( 'ARRAY_A', 'ARRAY_A' );
}

if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
    define( 'DAY_IN_SECONDS', 24 * 60 * 60 );
}

// Define WP_User for instanceof checks in tests
if ( ! class_exists( 'WP_User' ) ) {
    class WP_User {
        public int $ID;
        public array $caps = [];

        public function __construct( int $id ) {
            $this->ID = $id;
        }

        public function add_cap( string $cap ): void {
            $this->caps[ $cap ] = true;
        }

        public function remove_cap( string $cap ): void {
            unset( $this->caps[ $cap ] );
        }
    }
}

global $wp_options, $khm_test_filters, $khm_test_actions, $khm_test_userdata, $khm_test_user_meta, $khm_test_strings, $khm_test_now, $khm_test_logged_in_user;

function khm_tests_reset_environment(): void {
    global $wp_options, $khm_test_filters, $khm_test_actions, $khm_test_userdata, $khm_test_user_meta, $khm_test_strings, $khm_test_now, $khm_test_logged_in_user;

    $wp_options         = [];
    $khm_test_filters   = [];
    $khm_test_actions   = [];
    $khm_test_userdata  = [];
    $khm_test_user_meta = [];
    $khm_test_strings   = [
        'blogname'    => 'Test Site',
        'home_url'    => 'https://example.com',
        'account_url' => 'https://example.com/account',
    ];
    $khm_test_now       = null;
    $khm_test_logged_in_user = null;
}

function khm_tests_set_current_time( ?string $datetime ): void {
    global $khm_test_now;
    $khm_test_now = $datetime;
}

function khm_tests_set_userdata( int $user_id, array $data ): void {
    global $khm_test_userdata;
    $khm_test_userdata[ $user_id ] = (object) $data;
}

khm_tests_reset_environment();

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value ) {
        global $wp_options;
        $wp_options[ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        global $wp_options;
        return $wp_options[ $option ] ?? $default;
    }
}

if ( ! function_exists( 'wp_generate_password' ) ) {
    function wp_generate_password( $length = 12, $special_chars = false ) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        return substr( str_shuffle( $alphabet ), 0, $length );
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type, $gmt = 0 ) {
        global $khm_test_now;
        $timestamp = $khm_test_now ? strtotime( $khm_test_now ) : time();
        if ( $type === 'mysql' ) {
            return gmdate( 'Y-m-d H:i:s', $timestamp );
        }
        if ( $type === 'timestamp' ) {
            return $timestamp;
        }
        return gmdate( 'Y-m-d H:i:s', $timestamp );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) {
        return is_scalar( $value ) ? trim( (string) $value ) : $value;
    }
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $value ) {
        return is_scalar( $value ) ? trim( (string) $value ) : $value;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( 'maybe_serialize' ) ) {
    function maybe_serialize( $data ) {
        if ( is_array( $data ) || is_object( $data ) ) {
            return serialize( $data );
        }
        if ( is_serialized( $data ) ) {
            return serialize( $data );
        }
        return $data;
    }
}

if ( ! function_exists( 'maybe_unserialize' ) ) {
    function maybe_unserialize( $data ) {
        if ( is_serialized( $data ) ) {
            return unserialize( $data );
        }
        return $data;
    }
}

if ( ! function_exists( 'is_serialized' ) ) {
    function is_serialized( $data ) {
        if ( ! is_string( $data ) ) {
            return false;
        }
        $data = trim( $data );
        if ( $data === 'N;' ) {
            return true;
        }
        if ( ! preg_match( '/^([adObis]):/', $data, $badges ) ) {
            return false;
        }
        return @unserialize( $data ) !== false;
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $tag, $value ) {
        global $khm_test_filters;
        $args  = func_get_args();
        $value = $args[1] ?? null;

        if ( isset( $khm_test_filters[ $tag ] ) ) {
            foreach ( (array) $khm_test_filters[ $tag ] as $callback ) {
                $value = $callback( $value, ...array_slice( $args, 2 ) );
            }
        }

        return $value;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, callable $callback ): void {
        global $khm_test_filters;
        $khm_test_filters[ $tag ][] = $callback;
    }
}

if ( ! function_exists( 'remove_all_filters' ) ) {
    function remove_all_filters( $tag ): void {
        global $khm_test_filters;
        unset( $khm_test_filters[ $tag ] );
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $tag, ...$args ): void {
        global $khm_test_actions;
        if ( isset( $khm_test_actions[ $tag ] ) ) {
            foreach ( (array) $khm_test_actions[ $tag ] as $callback ) {
                $callback( ...$args );
            }
        }
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $tag, callable $callback ): void {
        global $khm_test_actions;
        $khm_test_actions[ $tag ][] = $callback;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = null ) {
        return $text;
    }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( $show = '', $filter = 'raw' ) {
        global $khm_test_strings;
        return $khm_test_strings['blogname'];
    }
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
    function is_user_logged_in(): bool {
        global $khm_test_logged_in_user;
        return ! empty( $khm_test_logged_in_user );
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id(): int {
        global $khm_test_logged_in_user;
        return $khm_test_logged_in_user ? (int) $khm_test_logged_in_user : 0;
    }
}

if ( ! function_exists( 'khm_tests_set_logged_in_user' ) ) {
    function khm_tests_set_logged_in_user( ?int $user_id ): void {
        global $khm_test_logged_in_user;
        $khm_test_logged_in_user = $user_id;
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '', $scheme = 'admin' ) {
        $path = ltrim( (string) $path, '/' );
        return 'https://example.com/wp-admin/' . $path;
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '', $scheme = null ) {
        global $khm_test_strings;
        return $khm_test_strings['home_url'];
    }
}

if ( ! function_exists( 'khm_get_account_url' ) ) {
    function khm_get_account_url(): string {
        global $khm_test_strings;
        return $khm_test_strings['account_url'];
    }
}

if ( ! function_exists( 'get_locale' ) ) {
    function get_locale(): string {
        return 'en_US';
    }
}

if ( ! function_exists( 'get_userdata' ) ) {
    function get_userdata( $user_id ) {
        global $khm_test_userdata;
        return $khm_test_userdata[ $user_id ] ?? (object) [
            'ID'           => $user_id,
            'display_name' => 'User ' . $user_id,
            'user_email'   => 'user' . $user_id . '@example.com',
        ];
    }
}

if ( ! function_exists( 'get_user_meta' ) ) {
    function get_user_meta( $user_id, $key, $single = false ) {
        global $khm_test_user_meta;
        if ( ! isset( $khm_test_user_meta[ $user_id ][ $key ] ) ) {
            return $single ? '' : [];
        }
        return $khm_test_user_meta[ $user_id ][ $key ];
    }
}

if ( ! function_exists( 'update_user_meta' ) ) {
    function update_user_meta( $user_id, $key, $value ) {
        global $khm_test_user_meta;
        $khm_test_user_meta[ $user_id ][ $key ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_user_meta' ) ) {
    function delete_user_meta( $user_id, $key ) {
        global $khm_test_user_meta;
        unset( $khm_test_user_meta[ $user_id ][ $key ] );
        return true;
    }
}

if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta( $sql ) {
        return true;
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'sanitize_key' ) ) {
    function sanitize_key( $key ) {
        return preg_replace( '/[^a-z0-9_]/', '', strtolower( $key ) );
    }
}
