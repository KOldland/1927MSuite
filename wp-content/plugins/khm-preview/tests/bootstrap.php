<?php

require_once dirname( __DIR__ ) . '/src/Autoloader.php';
\KHM\Preview\Autoloader::init();

$GLOBALS['khm_preview_test_options'] = [];
$GLOBALS['khm_preview_filters']      = [];
$GLOBALS['khm_preview_posts']        = [];

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $name ) {
        return $GLOBALS['khm_preview_test_options'][ $name ] ?? null;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $name, $value ) {
        $GLOBALS['khm_preview_test_options'][ $name ] = $value;
        return true;
    }
}

if ( ! function_exists( 'wp_generate_password' ) ) {
    function wp_generate_password( $length = 64 ) {
        return str_repeat( 'a', $length );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) {
        return is_scalar( $value ) ? preg_replace( '/[<>]/', '', (string) $value ) : '';
    }
}

if ( ! function_exists( 'wp_unslash' ) ) {
    function wp_unslash( $value ) {
        return $value;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type = 'timestamp' ) {
        return $type === 'mysql' ? gmdate( 'Y-m-d H:i:s' ) : time();
    }
}

if ( ! function_exists( 'wp_timezone' ) ) {
    function wp_timezone() {
        return new DateTimeZone( 'UTC' );
    }
}

if ( ! function_exists( 'wp_die' ) ) {
    class WPDieException extends RuntimeException {}
    function wp_die( $message, $code = 0 ) {
        throw new WPDieException( (string) $message, (int) $code );
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $tag, $callback ) {
        $GLOBALS['khm_preview_filters'][ $tag ][] = $callback;
    }
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post_id ) {
        return $GLOBALS['khm_preview_posts'][ $post_id ] ?? null;
    }
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can() {
        return true;
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() {
        return 1;
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '/' ) {
        return 'https://example.com' . $path;
    }
}

if ( ! function_exists( 'add_query_arg' ) ) {
    function add_query_arg( array $args, $url ) {
        $query = http_build_query( $args );
        return rtrim( $url, '?' ) . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $query;
    }
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
    function wp_nonce_field() {}
}

if ( ! function_exists( 'wp_verify_nonce' ) ) {
    function wp_verify_nonce() {
        return true;
    }
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
    function wp_safe_redirect() {
        return true;
    }
}

if ( ! function_exists( 'wp_get_referer' ) ) {
    function wp_get_referer() {
        return null;
    }
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        protected $params = [];

        public function __construct( array $params = [] ) {
            $this->params = $params;
        }

        public function get_param( $key ) {
            return $this->params[ $key ] ?? null;
        }
    }
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        protected $data;
        protected $status;

        public function __construct( $data = null, int $status = 200 ) {
            $this->data   = $data;
            $this->status = $status;
        }

        public function get_data() {
            return $this->data;
        }

        public function get_status(): int {
            return $this->status;
        }
    }
}
