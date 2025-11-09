<?php
/**
 * Main plugin class for KHM SEO.
 *
 * @package KHM_SEO
 * @version 1.0.0
 */

namespace KHM_SEO\Core;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use KHM_SEO\Meta\MetaManager;
use KHM_SEO\Schema\SchemaManager;
use KHM_SEO\Sitemap\SitemapManager;
use KHM_SEO\Admin\AdminManager;
use KHM_SEO\Tools\ToolsManager;
use KHM_SEO\Utils\DatabaseManager;

/**
 * Main plugin class.
 */
final class Plugin {

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = KHM_SEO_VERSION;

    /**
     * Meta manager instance.
     *
     * @var MetaManager|null
     */
    public $meta = null;

    /**
     * Schema manager instance.
     *
     * @var SchemaManager|null
     */
    public $schema = null;

    /**
     * Sitemap manager instance.
     *
     * @var SitemapManager|null
     */
    public $sitemap = null;

    /**
     * Admin manager instance.
     *
     * @var AdminManager|null
     */
    public $admin = null;

    /**
     * Tools manager instance.
     *
     * @var ToolsManager|null
     */
    public $tools = null;

    /**
     * Database manager instance.
     *
     * @var DatabaseManager|null
     */
    public $database = null;

    /**
     * Get plugin instance.
     *
     * @return Plugin
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Initialize the plugin.
     */
    private function init() {
        // Load text domain
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        // Initialize core components
        add_action( 'init', array( $this, 'init_components' ) );
        
        // Initialize database
        $this->database = new DatabaseManager();
        
        // Hook into WordPress
        add_action( 'wp_head', array( $this, 'output_head_tags' ), 1 );
        add_filter( 'wp_title', array( $this, 'filter_title' ), 10, 2 );
        add_action( 'wp_footer', array( $this, 'output_footer_tags' ) );
    }

    /**
     * Load plugin text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'khm-seo',
            false,
            dirname( plugin_basename( KHM_SEO_PLUGIN_FILE ) ) . '/languages'
        );
    }

    /**
     * Initialize plugin components.
     */
    public function init_components() {
        // Initialize managers
        $this->meta = new MetaManager();
        $this->schema = new SchemaManager();
        $this->sitemap = new SitemapManager();
        $this->admin = new AdminManager();
        $this->tools = new ToolsManager();
    }

    /**
     * Output SEO tags in the head section.
     */
    public function output_head_tags() {
        if ( ! $this->meta ) {
            return;
        }
        
        // Output meta tags
        $this->meta->output_meta_tags();
        
        // Output schema markup
        if ( $this->schema ) {
            $this->schema->output_schema();
        }
    }

    /**
     * Filter the WordPress title.
     *
     * @param string $title The current title.
     * @param string $sep   The separator.
     * @return string Modified title.
     */
    public function filter_title( $title, $sep = '' ) {
        if ( ! $this->meta ) {
            return $title;
        }
        
        return $this->meta->get_title() ?: $title;
    }

    /**
     * Output footer tags if needed.
     */
    public function output_footer_tags() {
        // Reserved for future footer-specific SEO elements
        do_action( 'khm_seo_footer_output' );
    }

    /**
     * Get plugin information.
     *
     * @return array Plugin information.
     */
    public function get_plugin_info() {
        return array(
            'name'        => 'KHM SEO',
            'version'     => $this->version,
            'description' => __( 'Complete SEO solution for content marketing and publishing.', 'khm-seo' ),
            'author'      => 'KHM Development Team',
            'url'         => 'https://1927magazine.com/',
        );
    }

    /**
     * Check if plugin is properly initialized.
     *
     * @return bool True if initialized, false otherwise.
     */
    public function is_initialized() {
        return null !== $this->meta && 
               null !== $this->schema && 
               null !== $this->sitemap && 
               null !== $this->admin && 
               null !== $this->tools;
    }

    /**
     * Get meta manager instance.
     *
     * @return MetaManager|null
     */
    public function get_meta_manager() {
        return $this->meta;
    }

    /**
     * Get schema manager instance.
     *
     * @return SchemaManager|null
     */
    public function get_schema_manager() {
        return $this->schema;
    }

    /**
     * Get sitemap manager instance.
     *
     * @return SitemapManager|null
     */
    public function get_sitemap_manager() {
        return $this->sitemap;
    }

    /**
     * Get admin manager instance.
     *
     * @return AdminManager|null
     */
    public function get_admin_manager() {
        return $this->admin;
    }

    /**
     * Get tools manager instance.
     *
     * @return ToolsManager|null
     */
    public function get_tools_manager() {
        return $this->tools;
    }
}