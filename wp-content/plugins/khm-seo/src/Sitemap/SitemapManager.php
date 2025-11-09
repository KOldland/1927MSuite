<?php
/**
 * Sitemap Manager for XML sitemaps.
 *
 * @package KHM_SEO
 * @version 1.0.0
 */

namespace KHM_SEO\Sitemap;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sitemap manager class.
 */
class SitemapManager {

    /**
     * Initialize the sitemap manager.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'add_rewrite_rules' ) );
        add_action( 'template_redirect', array( $this, 'handle_sitemap_request' ) );
        add_action( 'khm_seo_generate_sitemap', array( $this, 'generate_sitemap' ) );
    }

    /**
     * Add rewrite rules for sitemap.
     */
    public function add_rewrite_rules() {
        add_rewrite_rule( 'sitemap\.xml$', 'index.php?khm_sitemap=index', 'top' );
        add_rewrite_rule( 'sitemap-([^/]+?)\.xml$', 'index.php?khm_sitemap=$matches[1]', 'top' );
        
        // Add query vars
        add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
    }

    /**
     * Add custom query vars.
     *
     * @param array $vars Query vars.
     * @return array Modified query vars.
     */
    public function add_query_vars( $vars ) {
        $vars[] = 'khm_sitemap';
        return $vars;
    }

    /**
     * Handle sitemap requests.
     */
    public function handle_sitemap_request() {
        $sitemap = get_query_var( 'khm_sitemap' );
        
        if ( ! $sitemap ) {
            return;
        }

        $options = get_option( 'khm_seo_sitemap', array() );
        
        if ( empty( $options['enable_xml_sitemap'] ) ) {
            return;
        }

        // Set headers
        header( 'Content-Type: text/xml; charset=UTF-8' );
        
        if ( 'index' === $sitemap ) {
            echo $this->generate_sitemap_index();
        } else {
            echo $this->generate_sitemap_type( $sitemap );
        }
        
        exit;
    }

    /**
     * Generate sitemap index.
     *
     * @return string Sitemap index XML.
     */
    private function generate_sitemap_index() {
        $options = get_option( 'khm_seo_sitemap', array() );
        $sitemaps = array();

        if ( ! empty( $options['include_posts'] ) ) {
            $sitemaps[] = array(
                'loc'     => home_url( 'sitemap-posts.xml' ),
                'lastmod' => $this->get_last_modified_date( 'post' )
            );
        }

        if ( ! empty( $options['include_pages'] ) ) {
            $sitemaps[] = array(
                'loc'     => home_url( 'sitemap-pages.xml' ),
                'lastmod' => $this->get_last_modified_date( 'page' )
            );
        }

        if ( ! empty( $options['include_categories'] ) ) {
            $sitemaps[] = array(
                'loc'     => home_url( 'sitemap-categories.xml' ),
                'lastmod' => $this->get_last_modified_date( 'category' )
            );
        }

        if ( ! empty( $options['include_tags'] ) ) {
            $sitemaps[] = array(
                'loc'     => home_url( 'sitemap-tags.xml' ),
                'lastmod' => $this->get_last_modified_date( 'post_tag' )
            );
        }

        return $this->build_sitemap_index_xml( $sitemaps );
    }

    /**
     * Generate sitemap for specific type.
     *
     * @param string $type Sitemap type.
     * @return string Sitemap XML.
     */
    private function generate_sitemap_type( $type ) {
        switch ( $type ) {
            case 'posts':
                return $this->generate_posts_sitemap();
            case 'pages':
                return $this->generate_pages_sitemap();
            case 'categories':
                return $this->generate_categories_sitemap();
            case 'tags':
                return $this->generate_tags_sitemap();
            default:
                return '';
        }
    }

    /**
     * Generate posts sitemap.
     *
     * @return string Posts sitemap XML.
     */
    private function generate_posts_sitemap() {
        $options = get_option( 'khm_seo_sitemap', array() );
        $limit = isset( $options['sitemap_posts_limit'] ) ? $options['sitemap_posts_limit'] : 50000;

        $posts = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );

        $urls = array();
        foreach ( $posts as $post ) {
            if ( $this->should_include_in_sitemap( $post ) ) {
                $urls[] = array(
                    'loc'     => get_permalink( $post ),
                    'lastmod' => get_the_modified_date( 'c', $post ),
                    'priority'=> $this->get_post_priority( $post ),
                    'changefreq' => $this->get_post_changefreq( $post )
                );
            }
        }

        return $this->build_sitemap_xml( $urls );
    }

    /**
     * Generate pages sitemap.
     *
     * @return string Pages sitemap XML.
     */
    private function generate_pages_sitemap() {
        $pages = get_pages( array(
            'post_status' => 'publish',
            'number'      => 0
        ) );

        $urls = array();
        foreach ( $pages as $page ) {
            if ( $this->should_include_in_sitemap( $page ) ) {
                $urls[] = array(
                    'loc'     => get_permalink( $page ),
                    'lastmod' => get_the_modified_date( 'c', $page ),
                    'priority'=> $this->get_page_priority( $page ),
                    'changefreq' => 'monthly'
                );
            }
        }

        return $this->build_sitemap_xml( $urls );
    }

    /**
     * Generate categories sitemap.
     *
     * @return string Categories sitemap XML.
     */
    private function generate_categories_sitemap() {
        $categories = get_categories( array(
            'hide_empty' => true,
            'number'     => 0
        ) );

        $urls = array();
        foreach ( $categories as $category ) {
            $urls[] = array(
                'loc'     => get_category_link( $category ),
                'lastmod' => $this->get_category_last_modified( $category ),
                'priority'=> '0.6',
                'changefreq' => 'weekly'
            );
        }

        return $this->build_sitemap_xml( $urls );
    }

    /**
     * Generate tags sitemap.
     *
     * @return string Tags sitemap XML.
     */
    private function generate_tags_sitemap() {
        $tags = get_tags( array(
            'hide_empty' => true,
            'number'     => 0
        ) );

        $urls = array();
        foreach ( $tags as $tag ) {
            $urls[] = array(
                'loc'     => get_tag_link( $tag ),
                'lastmod' => $this->get_tag_last_modified( $tag ),
                'priority'=> '0.4',
                'changefreq' => 'weekly'
            );
        }

        return $this->build_sitemap_xml( $urls );
    }

    /**
     * Build sitemap index XML.
     *
     * @param array $sitemaps Array of sitemaps.
     * @return string Sitemap index XML.
     */
    private function build_sitemap_index_xml( $sitemaps ) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $sitemaps as $sitemap ) {
            $xml .= "\t<sitemap>\n";
            $xml .= "\t\t<loc>" . esc_url( $sitemap['loc'] ) . "</loc>\n";
            if ( ! empty( $sitemap['lastmod'] ) ) {
                $xml .= "\t\t<lastmod>" . esc_xml( $sitemap['lastmod'] ) . "</lastmod>\n";
            }
            $xml .= "\t</sitemap>\n";
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Build sitemap XML.
     *
     * @param array $urls Array of URLs.
     * @return string Sitemap XML.
     */
    private function build_sitemap_xml( $urls ) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ( $urls as $url ) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url( $url['loc'] ) . "</loc>\n";
            
            if ( ! empty( $url['lastmod'] ) ) {
                $xml .= "\t\t<lastmod>" . esc_xml( $url['lastmod'] ) . "</lastmod>\n";
            }
            
            if ( ! empty( $url['changefreq'] ) ) {
                $xml .= "\t\t<changefreq>" . esc_xml( $url['changefreq'] ) . "</changefreq>\n";
            }
            
            if ( ! empty( $url['priority'] ) ) {
                $xml .= "\t\t<priority>" . esc_xml( $url['priority'] ) . "</priority>\n";
            }
            
            $xml .= "\t</url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Check if post should be included in sitemap.
     *
     * @param object $post Post object.
     * @return bool Whether to include in sitemap.
     */
    private function should_include_in_sitemap( $post ) {
        // Check if post is excluded via meta
        $exclude = get_post_meta( $post->ID, '_khm_seo_exclude_sitemap', true );
        
        if ( $exclude ) {
            return false;
        }

        // Check robots meta
        $robots = get_post_meta( $post->ID, '_khm_seo_robots', true );
        
        if ( false !== strpos( $robots, 'noindex' ) ) {
            return false;
        }

        return true;
    }

    /**
     * Get post priority for sitemap.
     *
     * @param object $post Post object.
     * @return string Priority value.
     */
    private function get_post_priority( $post ) {
        // Higher priority for newer posts
        $days_old = ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS;
        
        if ( $days_old < 7 ) {
            return '1.0';
        } elseif ( $days_old < 30 ) {
            return '0.8';
        } elseif ( $days_old < 90 ) {
            return '0.6';
        } else {
            return '0.4';
        }
    }

    /**
     * Get page priority for sitemap.
     *
     * @param object $page Page object.
     * @return string Priority value.
     */
    private function get_page_priority( $page ) {
        // Home page gets highest priority
        if ( $page->ID == get_option( 'page_on_front' ) ) {
            return '1.0';
        }
        
        // Parent pages get higher priority
        if ( $page->post_parent == 0 ) {
            return '0.8';
        }
        
        return '0.6';
    }

    /**
     * Get post change frequency.
     *
     * @param object $post Post object.
     * @return string Change frequency.
     */
    private function get_post_changefreq( $post ) {
        $days_old = ( time() - strtotime( $post->post_modified ) ) / DAY_IN_SECONDS;
        
        if ( $days_old < 7 ) {
            return 'daily';
        } elseif ( $days_old < 30 ) {
            return 'weekly';
        } else {
            return 'monthly';
        }
    }

    /**
     * Get last modified date for post type.
     *
     * @param string $type Post type or taxonomy.
     * @return string Last modified date.
     */
    private function get_last_modified_date( $type ) {
        global $wpdb;
        
        switch ( $type ) {
            case 'post':
            case 'page':
                $result = $wpdb->get_var( $wpdb->prepare(
                    "SELECT post_modified_gmt FROM {$wpdb->posts} 
                     WHERE post_type = %s AND post_status = 'publish' 
                     ORDER BY post_modified_gmt DESC LIMIT 1",
                    $type
                ) );
                break;
            case 'category':
            case 'post_tag':
                $result = $wpdb->get_var( $wpdb->prepare(
                    "SELECT p.post_modified_gmt FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                     INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                     WHERE tt.taxonomy = %s AND p.post_status = 'publish'
                     ORDER BY p.post_modified_gmt DESC LIMIT 1",
                    $type
                ) );
                break;
            default:
                $result = current_time( 'mysql', true );
        }
        
        return $result ? date( 'c', strtotime( $result ) ) : date( 'c' );
    }

    /**
     * Get category last modified date.
     *
     * @param object $category Category object.
     * @return string Last modified date.
     */
    private function get_category_last_modified( $category ) {
        global $wpdb;
        
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT p.post_modified_gmt FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             WHERE tr.term_taxonomy_id = %d AND p.post_status = 'publish'
             ORDER BY p.post_modified_gmt DESC LIMIT 1",
            $category->term_id
        ) );
        
        return $result ? date( 'c', strtotime( $result ) ) : date( 'c' );
    }

    /**
     * Get tag last modified date.
     *
     * @param object $tag Tag object.
     * @return string Last modified date.
     */
    private function get_tag_last_modified( $tag ) {
        return $this->get_category_last_modified( $tag );
    }

    /**
     * Generate sitemap (for scheduled task).
     */
    public function generate_sitemap() {
        // This can be used for generating static sitemap files if needed
        do_action( 'khm_seo_sitemap_generated' );
    }
}