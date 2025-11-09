<?php
/**
 * Schema Manager for handling structured data.
 *
 * @package KHM_SEO
 * @version 1.0.0
 */

namespace KHM_SEO\Schema;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Schema manager class.
 */
class SchemaManager {

    /**
     * Initialize the schema manager.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        add_action( 'wp_head', array( $this, 'output_schema' ), 25 );
    }

    /**
     * Output schema markup.
     */
    public function output_schema() {
        $options = get_option( 'khm_seo_schema', array() );
        
        if ( empty( $options['enable_schema'] ) ) {
            return;
        }

        $schema_data = $this->get_schema_data();
        
        if ( ! empty( $schema_data ) ) {
            echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        }
    }

    /**
     * Get schema data for current page.
     *
     * @return array Schema data.
     */
    private function get_schema_data() {
        $schema_data = array(
            '@context' => 'https://schema.org',
            '@graph'   => array()
        );

        // Add website schema
        if ( $this->should_output_website_schema() ) {
            $schema_data['@graph'][] = $this->get_website_schema();
        }

        // Add organization schema
        if ( $this->should_output_organization_schema() ) {
            $schema_data['@graph'][] = $this->get_organization_schema();
        }

        // Add page-specific schema
        if ( is_singular() ) {
            $schema_data['@graph'][] = $this->get_article_schema();
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $schema_data['@graph'][] = $this->get_collection_page_schema();
        }

        // Add breadcrumb schema
        if ( $this->should_output_breadcrumb_schema() ) {
            $schema_data['@graph'][] = $this->get_breadcrumb_schema();
        }

        return $schema_data;
    }

    /**
     * Get website schema.
     *
     * @return array Website schema.
     */
    private function get_website_schema() {
        return array(
            '@type'         => 'WebSite',
            '@id'           => home_url( '/#website' ),
            'url'           => home_url( '/' ),
            'name'          => get_bloginfo( 'name' ),
            'description'   => get_bloginfo( 'description' ),
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => home_url( '/?s={search_term_string}' ),
                'query-input' => 'required name=search_term_string'
            )
        );
    }

    /**
     * Get organization schema.
     *
     * @return array Organization schema.
     */
    private function get_organization_schema() {
        $options = get_option( 'khm_seo_general', array() );
        
        $schema = array(
            '@type' => 'Organization',
            '@id'   => home_url( '/#organization' ),
            'name'  => isset( $options['company_name'] ) ? $options['company_name'] : get_bloginfo( 'name' ),
            'url'   => home_url( '/' )
        );

        if ( ! empty( $options['company_logo'] ) ) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url'   => $options['company_logo']
            );
        }

        if ( ! empty( $options['social_profiles'] ) && is_array( $options['social_profiles'] ) ) {
            $schema['sameAs'] = array_values( $options['social_profiles'] );
        }

        return $schema;
    }

    /**
     * Get article schema for posts.
     *
     * @return array Article schema.
     */
    private function get_article_schema() {
        global $post;
        
        if ( ! $post ) {
            return array();
        }

        $options = get_option( 'khm_seo_schema', array() );
        $article_type = isset( $options['default_article_type'] ) ? $options['default_article_type'] : 'Article';

        $schema = array(
            '@type'           => $article_type,
            '@id'             => get_permalink( $post ) . '#article',
            'headline'        => get_the_title( $post ),
            'datePublished'   => get_the_date( 'c', $post ),
            'dateModified'    => get_the_modified_date( 'c', $post ),
            'author'          => array(
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $post->post_author ),
                'url'   => get_author_posts_url( $post->post_author )
            ),
            'publisher'       => array(
                '@id' => home_url( '/#organization' )
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id'   => get_permalink( $post )
            )
        );

        // Add featured image if available
        if ( has_post_thumbnail( $post ) ) {
            $image_id = get_post_thumbnail_id( $post );
            $image_data = wp_get_attachment_image_src( $image_id, 'full' );
            
            if ( $image_data ) {
                $schema['image'] = array(
                    '@type' => 'ImageObject',
                    'url'   => $image_data[0],
                    'width' => $image_data[1],
                    'height'=> $image_data[2]
                );
            }
        }

        return $schema;
    }

    /**
     * Get collection page schema for archives.
     *
     * @return array Collection page schema.
     */
    private function get_collection_page_schema() {
        $term = get_queried_object();
        
        if ( ! $term ) {
            return array();
        }

        return array(
            '@type'       => 'CollectionPage',
            '@id'         => get_term_link( $term ) . '#webpage',
            'url'         => get_term_link( $term ),
            'name'        => $term->name,
            'description' => $term->description ?: null,
            'isPartOf'    => array(
                '@id' => home_url( '/#website' )
            )
        );
    }

    /**
     * Get breadcrumb schema.
     *
     * @return array Breadcrumb schema.
     */
    private function get_breadcrumb_schema() {
        $breadcrumbs = $this->get_breadcrumb_trail();
        
        if ( empty( $breadcrumbs ) ) {
            return array();
        }

        $breadcrumb_list = array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array()
        );

        foreach ( $breadcrumbs as $index => $breadcrumb ) {
            $breadcrumb_list['itemListElement'][] = array(
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'item'     => array(
                    '@type' => 'WebPage',
                    '@id'   => $breadcrumb['url'],
                    'name'  => $breadcrumb['title']
                )
            );
        }

        return $breadcrumb_list;
    }

    /**
     * Get breadcrumb trail for current page.
     *
     * @return array Breadcrumb items.
     */
    private function get_breadcrumb_trail() {
        $breadcrumbs = array();

        // Home page
        $breadcrumbs[] = array(
            'title' => get_bloginfo( 'name' ),
            'url'   => home_url( '/' )
        );

        // Add current page
        if ( is_singular() ) {
            global $post;
            
            // Add parent pages for hierarchical post types
            if ( $post->post_parent ) {
                $parent_ids = array_reverse( get_ancestors( $post->ID, $post->post_type ) );
                foreach ( $parent_ids as $parent_id ) {
                    $breadcrumbs[] = array(
                        'title' => get_the_title( $parent_id ),
                        'url'   => get_permalink( $parent_id )
                    );
                }
            }

            $breadcrumbs[] = array(
                'title' => get_the_title( $post ),
                'url'   => get_permalink( $post )
            );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            $breadcrumbs[] = array(
                'title' => $term->name,
                'url'   => get_term_link( $term )
            );
        }

        return $breadcrumbs;
    }

    /**
     * Check if website schema should be output.
     *
     * @return bool
     */
    private function should_output_website_schema() {
        $options = get_option( 'khm_seo_schema', array() );
        return ! empty( $options['enable_website'] );
    }

    /**
     * Check if organization schema should be output.
     *
     * @return bool
     */
    private function should_output_organization_schema() {
        $options = get_option( 'khm_seo_schema', array() );
        return ! empty( $options['enable_organization'] );
    }

    /**
     * Check if breadcrumb schema should be output.
     *
     * @return bool
     */
    private function should_output_breadcrumb_schema() {
        $options = get_option( 'khm_seo_schema', array() );
        return ! empty( $options['enable_breadcrumbs'] );
    }
}