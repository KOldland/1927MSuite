<?php
/**
 * Tools Manager for SEO utilities.
 *
 * @package KHM_SEO
 * @version 1.0.0
 */

namespace KHM_SEO\Tools;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Tools manager class.
 */
class ToolsManager {

    /**
     * Initialize the tools manager.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_filter( 'robots_txt', array( $this, 'customize_robots_txt' ), 10, 2 );
        add_action( 'wp_head', array( $this, 'add_verification_tags' ) );
        add_action( 'wp_ajax_khm_seo_analyze_content', array( $this, 'analyze_content_ajax' ) );
    }

    /**
     * Customize robots.txt content.
     *
     * @param string $output Robots.txt content.
     * @param bool   $public Whether site is public.
     * @return string Modified robots.txt content.
     */
    public function customize_robots_txt( $output, $public ) {
        $options = get_option( 'khm_seo_tools', array() );
        
        if ( ! empty( $options['enable_robots_txt'] ) && ! empty( $options['robots_txt_content'] ) ) {
            $output = $options['robots_txt_content'];
        }

        // Add sitemap reference
        $sitemap_options = get_option( 'khm_seo_sitemap', array() );
        if ( ! empty( $sitemap_options['enable_xml_sitemap'] ) ) {
            $output .= "\nSitemap: " . home_url( 'sitemap.xml' );
        }

        return $output;
    }

    /**
     * Add verification tags to head.
     */
    public function add_verification_tags() {
        $options = get_option( 'khm_seo_tools', array() );

        if ( ! empty( $options['google_verification'] ) ) {
            echo '<meta name="google-site-verification" content="' . esc_attr( $options['google_verification'] ) . '">' . "\n";
        }

        if ( ! empty( $options['bing_verification'] ) ) {
            echo '<meta name="msvalidate.01" content="' . esc_attr( $options['bing_verification'] ) . '">' . "\n";
        }

        if ( ! empty( $options['pinterest_verification'] ) ) {
            echo '<meta name="p:domain_verify" content="' . esc_attr( $options['pinterest_verification'] ) . '">' . "\n";
        }
    }

    /**
     * Analyze content via AJAX.
     */
    public function analyze_content_ajax() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'khm_seo_ajax' ) ) {
            wp_die( 'Security check failed' );
        }

        $content = sanitize_textarea_field( $_POST['content'] );
        $title = sanitize_text_field( $_POST['title'] );
        $focus_keyword = sanitize_text_field( $_POST['focus_keyword'] );

        $analysis = $this->perform_content_analysis( $content, $title, $focus_keyword );

        wp_send_json_success( $analysis );
    }

    /**
     * Perform content analysis.
     *
     * @param string $content       The content to analyze.
     * @param string $title         The title.
     * @param string $focus_keyword The focus keyword.
     * @return array Analysis results.
     */
    public function perform_content_analysis( $content, $title = '', $focus_keyword = '' ) {
        $analysis = array(
            'score' => 0,
            'checks' => array(),
            'recommendations' => array()
        );

        // Content length check
        $word_count = $this->get_word_count( $content );
        if ( $word_count >= 300 ) {
            $analysis['checks']['content_length'] = array(
                'status' => 'good',
                'message' => sprintf( __( 'Content length is good (%d words)', 'khm-seo' ), $word_count )
            );
            $analysis['score'] += 20;
        } else {
            $analysis['checks']['content_length'] = array(
                'status' => 'poor',
                'message' => sprintf( __( 'Content is too short (%d words). Aim for at least 300 words.', 'khm-seo' ), $word_count )
            );
            $analysis['recommendations'][] = __( 'Add more content to reach at least 300 words', 'khm-seo' );
        }

        // Title length check
        $title_length = strlen( $title );
        if ( $title_length >= 30 && $title_length <= 60 ) {
            $analysis['checks']['title_length'] = array(
                'status' => 'good',
                'message' => sprintf( __( 'Title length is optimal (%d characters)', 'khm-seo' ), $title_length )
            );
            $analysis['score'] += 15;
        } elseif ( $title_length > 0 ) {
            $analysis['checks']['title_length'] = array(
                'status' => 'needs_improvement',
                'message' => sprintf( __( 'Title length could be better (%d characters). Aim for 30-60 characters.', 'khm-seo' ), $title_length )
            );
            $analysis['score'] += 5;
            $analysis['recommendations'][] = __( 'Optimize title length to 30-60 characters', 'khm-seo' );
        }

        // Focus keyword checks
        if ( ! empty( $focus_keyword ) ) {
            // Keyword in title
            if ( false !== stripos( $title, $focus_keyword ) ) {
                $analysis['checks']['keyword_in_title'] = array(
                    'status' => 'good',
                    'message' => __( 'Focus keyword appears in title', 'khm-seo' )
                );
                $analysis['score'] += 15;
            } else {
                $analysis['checks']['keyword_in_title'] = array(
                    'status' => 'poor',
                    'message' => __( 'Focus keyword does not appear in title', 'khm-seo' )
                );
                $analysis['recommendations'][] = __( 'Include focus keyword in the title', 'khm-seo' );
            }

            // Keyword density
            $keyword_density = $this->calculate_keyword_density( $content, $focus_keyword );
            if ( $keyword_density >= 0.5 && $keyword_density <= 3.0 ) {
                $analysis['checks']['keyword_density'] = array(
                    'status' => 'good',
                    'message' => sprintf( __( 'Keyword density is good (%.1f%%)', 'khm-seo' ), $keyword_density )
                );
                $analysis['score'] += 15;
            } else {
                $analysis['checks']['keyword_density'] = array(
                    'status' => 'needs_improvement',
                    'message' => sprintf( __( 'Keyword density could be optimized (%.1f%%). Aim for 0.5-3%%.', 'khm-seo' ), $keyword_density )
                );
                $analysis['score'] += 5;
                $analysis['recommendations'][] = __( 'Optimize keyword density to 0.5-3%', 'khm-seo' );
            }
        } else {
            $analysis['checks']['focus_keyword'] = array(
                'status' => 'poor',
                'message' => __( 'No focus keyword set', 'khm-seo' )
            );
            $analysis['recommendations'][] = __( 'Set a focus keyword for this content', 'khm-seo' );
        }

        // Heading structure check
        $headings = $this->analyze_headings( $content );
        if ( $headings['h1_count'] == 1 ) {
            $analysis['checks']['h1_usage'] = array(
                'status' => 'good',
                'message' => __( 'Content has exactly one H1 tag', 'khm-seo' )
            );
            $analysis['score'] += 10;
        } else {
            $analysis['checks']['h1_usage'] = array(
                'status' => 'needs_improvement',
                'message' => sprintf( __( 'Content has %d H1 tags. Use exactly one H1 tag.', 'khm-seo' ), $headings['h1_count'] )
            );
            $analysis['recommendations'][] = __( 'Use exactly one H1 tag in your content', 'khm-seo' );
        }

        if ( $headings['subheading_count'] > 0 ) {
            $analysis['checks']['subheadings'] = array(
                'status' => 'good',
                'message' => sprintf( __( 'Content uses %d subheadings', 'khm-seo' ), $headings['subheading_count'] )
            );
            $analysis['score'] += 10;
        } else {
            $analysis['checks']['subheadings'] = array(
                'status' => 'needs_improvement',
                'message' => __( 'Content has no subheadings (H2-H6)', 'khm-seo' )
            );
            $analysis['recommendations'][] = __( 'Add subheadings (H2, H3, etc.) to improve content structure', 'khm-seo' );
        }

        // Images check
        $images = $this->analyze_images( $content );
        if ( $images['total'] > 0 ) {
            if ( $images['with_alt'] == $images['total'] ) {
                $analysis['checks']['image_alt'] = array(
                    'status' => 'good',
                    'message' => sprintf( __( 'All %d images have alt text', 'khm-seo' ), $images['total'] )
                );
                $analysis['score'] += 15;
            } else {
                $analysis['checks']['image_alt'] = array(
                    'status' => 'needs_improvement',
                    'message' => sprintf( __( '%d of %d images have alt text', 'khm-seo' ), $images['with_alt'], $images['total'] )
                );
                $analysis['score'] += 5;
                $analysis['recommendations'][] = __( 'Add alt text to all images', 'khm-seo' );
            }
        }

        // Calculate final score
        $analysis['score'] = min( 100, $analysis['score'] );

        // Set overall status
        if ( $analysis['score'] >= 80 ) {
            $analysis['status'] = 'good';
        } elseif ( $analysis['score'] >= 60 ) {
            $analysis['status'] = 'needs_improvement';
        } else {
            $analysis['status'] = 'poor';
        }

        return $analysis;
    }

    /**
     * Get word count from content.
     *
     * @param string $content The content.
     * @return int Word count.
     */
    private function get_word_count( $content ) {
        $text = strip_tags( $content );
        $text = preg_replace( '/\s+/', ' ', trim( $text ) );
        $words = explode( ' ', $text );
        return count( array_filter( $words ) );
    }

    /**
     * Calculate keyword density.
     *
     * @param string $content The content.
     * @param string $keyword The keyword.
     * @return float Keyword density percentage.
     */
    private function calculate_keyword_density( $content, $keyword ) {
        $text = strip_tags( $content );
        $text = strtolower( $text );
        $keyword = strtolower( $keyword );
        
        $word_count = $this->get_word_count( $content );
        $keyword_count = substr_count( $text, $keyword );
        
        if ( $word_count == 0 ) {
            return 0;
        }
        
        return ( $keyword_count / $word_count ) * 100;
    }

    /**
     * Analyze heading structure.
     *
     * @param string $content The content.
     * @return array Heading analysis.
     */
    private function analyze_headings( $content ) {
        $h1_count = preg_match_all( '/<h1[^>]*>/i', $content );
        $h2_count = preg_match_all( '/<h2[^>]*>/i', $content );
        $h3_count = preg_match_all( '/<h3[^>]*>/i', $content );
        $h4_count = preg_match_all( '/<h4[^>]*>/i', $content );
        $h5_count = preg_match_all( '/<h5[^>]*>/i', $content );
        $h6_count = preg_match_all( '/<h6[^>]*>/i', $content );
        
        return array(
            'h1_count' => $h1_count,
            'subheading_count' => $h2_count + $h3_count + $h4_count + $h5_count + $h6_count
        );
    }

    /**
     * Analyze images.
     *
     * @param string $content The content.
     * @return array Image analysis.
     */
    private function analyze_images( $content ) {
        preg_match_all( '/<img[^>]*>/i', $content, $images );
        $total_images = count( $images[0] );
        
        $images_with_alt = 0;
        foreach ( $images[0] as $img ) {
            if ( preg_match( '/alt=["\']([^"\']*)["\']/', $img ) ) {
                $images_with_alt++;
            }
        }
        
        return array(
            'total' => $total_images,
            'with_alt' => $images_with_alt
        );
    }

    /**
     * Get system status information.
     *
     * @return array System status data.
     */
    public function get_system_status() {
        global $wpdb, $wp_version;
        
        return array(
            'wordpress' => array(
                'version' => $wp_version,
                'multisite' => is_multisite(),
                'permalink_structure' => get_option( 'permalink_structure' ),
            ),
            'server' => array(
                'php_version' => PHP_VERSION,
                'mysql_version' => $wpdb->db_version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'],
                'max_execution_time' => ini_get( 'max_execution_time' ),
                'memory_limit' => ini_get( 'memory_limit' ),
            ),
            'plugin' => array(
                'version' => KHM_SEO_VERSION,
                'database_version' => get_option( 'khm_seo_db_version' ),
                'active_modules' => $this->get_active_modules(),
            )
        );
    }

    /**
     * Get active modules.
     *
     * @return array Active modules.
     */
    private function get_active_modules() {
        $modules = array();
        
        $options = array(
            'Meta Tags' => get_option( 'khm_seo_meta', array() ),
            'XML Sitemaps' => get_option( 'khm_seo_sitemap', array() ),
            'Schema Markup' => get_option( 'khm_seo_schema', array() ),
        );
        
        foreach ( $options as $module => $option ) {
            $modules[ $module ] = ! empty( $option['enable_' . strtolower( str_replace( ' ', '_', $module ) ) ] );
        }
        
        return $modules;
    }
}