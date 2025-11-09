<?php
/**
 * WordPress Function Mocks for Integration Testing
 * 
 * Provides mock implementations of WordPress functions for testing
 */

// Core WordPress functions
if ( ! function_exists( 'wp_strip_all_tags' ) ) {
    function wp_strip_all_tags( $content ) {
        return strip_tags( $content );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'apply_filters' ) ) {
    function apply_filters( $hook, $value, ...$args ) {
        return $value;
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $_wp_actions;
        if ( ! isset( $_wp_actions[ $hook ] ) ) {
            $_wp_actions[ $hook ] = array();
        }
        $_wp_actions[ $hook ][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'args' => $accepted_args
        );
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        global $_wp_filters;
        if ( ! isset( $_wp_filters[ $hook ] ) ) {
            $_wp_filters[ $hook ] = array();
        }
        $_wp_filters[ $hook ][] = array(
            'callback' => $callback,
            'priority' => $priority,
            'args' => $accepted_args
        );
        return true;
    }
}

if ( ! function_exists( 'has_action' ) ) {
    function has_action( $hook, $callback = null ) {
        global $_wp_actions;
        if ( ! isset( $_wp_actions[ $hook ] ) ) {
            return false;
        }
        if ( $callback === null ) {
            return count( $_wp_actions[ $hook ] ) > 0;
        }
        foreach ( $_wp_actions[ $hook ] as $action ) {
            if ( $action['callback'] === $callback ) {
                return $action['priority'];
            }
        }
        return false;
    }
}

if ( ! function_exists( 'has_filter' ) ) {
    function has_filter( $hook, $callback = null ) {
        global $_wp_filters;
        if ( ! isset( $_wp_filters[ $hook ] ) ) {
            return false;
        }
        if ( $callback === null ) {
            return count( $_wp_filters[ $hook ] ) > 0;
        }
        foreach ( $_wp_filters[ $hook ] as $filter ) {
            if ( $filter['callback'] === $callback ) {
                return $filter['priority'];
            }
        }
        return false;
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( $type = 'mysql', $gmt = 0 ) {
        return time();
    }
}

if ( ! function_exists( 'wp_parse_args' ) ) {
    function wp_parse_args( $args, $defaults = array() ) {
        if ( is_array( $args ) ) {
            return array_merge( $defaults, $args );
        }
        return $defaults;
    }
}

if ( ! function_exists( 'home_url' ) ) {
    function home_url( $path = '', $scheme = null ) {
        return 'https://example.com' . $path;
    }
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( $show = '' ) {
        switch ( $show ) {
            case 'name':
                return 'Test Site';
            case 'description':
                return 'Just another WordPress site';
            case 'version':
                return '6.4';
            default:
                return 'Test Site';
        }
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        // Mock options for testing
        $mock_options = array(
            'khm_seo_general' => array(
                'home_title' => 'Test Site - WordPress SEO',
                'home_description' => 'Test description for WordPress SEO',
                'separator' => '|'
            ),
            'khm_seo_titles' => array(
                'enable_title_rewrite' => true,
                'home_title_format' => '%sitename% %sep% %tagline%',
                'post_title_format' => '%title% %sep% %sitename%'
            ),
            'khm_seo_meta' => array(
                'enable_keywords' => true,
                'enable_og_tags' => true,
                'enable_twitter_cards' => true
            )
        );
        
        return isset( $mock_options[ $option ] ) ? $mock_options[ $option ] : $default;
    }
}

if ( ! function_exists( 'get_post' ) ) {
    function get_post( $post_id = null ) {
        return (object) array(
            'ID' => 1,
            'post_title' => 'Test Post Title',
            'post_content' => 'Test post content for WordPress SEO analysis.',
            'post_excerpt' => 'Test excerpt',
            'post_type' => 'post'
        );
    }
}

if ( ! function_exists( 'get_post_meta' ) ) {
    function get_post_meta( $post_id, $key = '', $single = false ) {
        // Mock post meta
        $meta_data = array(
            '_khm_seo_title' => 'Custom SEO Title',
            '_khm_seo_description' => 'Custom SEO Description',
            '_khm_seo_keywords' => 'wordpress, seo, optimization'
        );
        
        if ( $key ) {
            $value = isset( $meta_data[ $key ] ) ? $meta_data[ $key ] : '';
            return $single ? $value : array( $value );
        }
        
        return $meta_data;
    }
}

if ( ! function_exists( 'get_term' ) ) {
    function get_term( $term_id ) {
        return (object) array(
            'term_id' => $term_id,
            'name' => 'Test Category',
            'description' => 'Test category description',
            'taxonomy' => 'category'
        );
    }
}

if ( ! function_exists( 'get_term_meta' ) ) {
    function get_term_meta( $term_id, $key = '', $single = false ) {
        return $single ? '' : array();
    }
}

if ( ! function_exists( 'is_singular' ) ) {
    function is_singular() {
        return true;
    }
}

if ( ! function_exists( 'is_home' ) ) {
    function is_home() {
        return false;
    }
}

if ( ! function_exists( 'is_front_page' ) ) {
    function is_front_page() {
        return false;
    }
}

if ( ! function_exists( 'is_category' ) ) {
    function is_category() {
        return false;
    }
}

if ( ! function_exists( 'is_tag' ) ) {
    function is_tag() {
        return false;
    }
}

if ( ! function_exists( 'is_tax' ) ) {
    function is_tax() {
        return false;
    }
}

if ( ! function_exists( 'is_author' ) ) {
    function is_author() {
        return false;
    }
}

if ( ! function_exists( 'is_search' ) ) {
    function is_search() {
        return false;
    }
}

if ( ! function_exists( 'is_404' ) ) {
    function is_404() {
        return false;
    }
}

if ( ! function_exists( 'is_archive' ) ) {
    function is_archive() {
        return false;
    }
}

if ( ! function_exists( 'is_date' ) ) {
    function is_date() {
        return false;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin() {
        return true;
    }
}

if ( ! function_exists( 'wp_trim_words' ) ) {
    function wp_trim_words( $text, $num_words = 55, $more = null ) {
        $words = explode( ' ', $text );
        if ( count( $words ) > $num_words ) {
            $words = array_slice( $words, 0, $num_words );
            $text = implode( ' ', $words ) . ( $more ?: '...' );
        }
        return $text;
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'https://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( dirname( $file ) ) . '/' . basename( $file );
    }
}

if ( ! function_exists( 'set_current_screen' ) ) {
    function set_current_screen( $screen_id ) {
        global $current_screen;
        $current_screen = (object) array(
            'id' => $screen_id,
            'base' => $screen_id
        );
    }
}

// Initialize global variables
global $_wp_actions, $_wp_filters, $current_screen;
$_wp_actions = array();
$_wp_filters = array();
$current_screen = null;

// Mock constants
if ( ! defined( 'KHM_SEO_VERSION' ) ) {
    define( 'KHM_SEO_VERSION', '1.0.0' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_DIR' ) ) {
    define( 'KHM_SEO_PLUGIN_DIR', __DIR__ . '/wp-content/plugins/khm-seo/' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_URL' ) ) {
    define( 'KHM_SEO_PLUGIN_URL', 'https://example.com/wp-content/plugins/khm-seo/' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_BASENAME' ) ) {
    define( 'KHM_SEO_PLUGIN_BASENAME', 'khm-seo/khm-seo.php' );
}

// Global WordPress variables
global $wpdb;
$wpdb = new stdClass();
$wpdb->prefix = 'wp_';
$wpdb->get_charset_collate = function() {
    return 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
};

// Create a method for get_charset_collate
class MockWPDB {
    public $prefix = 'wp_';
    
    public function get_charset_collate() {
        return 'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    }
    
    public function query( $query ) {
        return true;
    }
    
    public function prepare( $query, ...$args ) {
        return vsprintf( str_replace( '%s', "'%s'", $query ), $args );
    }
    
    public function get_results( $query ) {
        return array();
    }
}

$wpdb = new MockWPDB();

// WordPress database functions
if ( ! function_exists( 'dbDelta' ) ) {
    function dbDelta( $queries ) {
        return array(); // Mock dbDelta
    }
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
    function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) {
        return true;
    }
}

if ( ! function_exists( 'do_action' ) ) {
    function do_action( $hook, ...$args ) {
        return true;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}

if ( ! function_exists( '_e' ) ) {
    function _e( $text, $domain = 'default' ) {
        echo $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'get_permalink' ) ) {
    function get_permalink( $post_id = null ) {
        return 'https://example.com/test-post/';
    }
}

if ( ! function_exists( 'get_term_link' ) ) {
    function get_term_link( $term ) {
        return 'https://example.com/category/test/';
    }
}

if ( ! function_exists( 'get_author_posts_url' ) ) {
    function get_author_posts_url( $author_id ) {
        return 'https://example.com/author/test/';
    }
}

if ( ! function_exists( 'get_queried_object' ) ) {
    function get_queried_object() {
        return (object) array(
            'ID' => 1,
            'name' => 'Test Object',
            'display_name' => 'Test Author'
        );
    }
}

if ( ! function_exists( 'has_post_thumbnail' ) ) {
    function has_post_thumbnail( $post_id = null ) {
        return false;
    }
}

if ( ! function_exists( 'get_the_author' ) ) {
    function get_the_author() {
        return 'Test Author';
    }
}

if ( ! function_exists( 'get_search_query' ) ) {
    function get_search_query() {
        return 'test search';
    }
}

if ( ! function_exists( 'get_the_date' ) ) {
    function get_the_date() {
        return '2024-01-01';
    }
}