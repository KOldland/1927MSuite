<?php
/**
 * Plugin Name: Internal Analytics
 * Description: A MonsterInsights-style analytics plugin for internal use.
 * Version: 1.0.0
 * Author: Gemini
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'INTERNAL_ANALYTICS_PATH', plugin_dir_path( __FILE__ ) );
define( 'INTERNAL_ANALYTICS_URL', plugin_dir_url( __FILE__ ) );

// For now, let's just include the existing files and see if we can instantiate the classes.
// We will need to handle autoloading and namespaces properly later.

// require_once INTERNAL_ANALYTICS_PATH . '../../wp-content/plugins/khm-seo/src/GoogleAnalytics4/GA4Manager.php';
// require_once INTERNAL_ANALYTICS_PATH . '../../wp-content/plugins/khm-plugin/src/Rest/FourAIngestionController.php';
// require_once INTERNAL_ANALYTICS_PATH . '../../wp-content/plugins/khm-seo/src/Analytics/DataAnalysisEngine.php';
// require_once INTERNAL_ANALYTICS_PATH . '../../wp-content/plugins/khm-seo/src/Analytics/DataAnalysisDashboard.php';
// require_once INTERNAL_ANALYTICS_PATH . '../../wp-content/plugins/khm-seo/src/OAuth/OAuthManager.php';
// require_once INTERNAL_ANALYTICS_PATH . '../../wp-content/plugins/khm-plugin/src/Services/CpEventIngestionService.php';


spl_autoload_register(function ( $class ) {
    $namespace_map = [
        'KHM\\' => __DIR__ . '/../../wp-content/plugins/khm-plugin/src/',
        'KHM_SEO\\' => __DIR__ . '/../../wp-content/plugins/khm-seo/src/',
    ];

    foreach ($namespace_map as $namespace => $base_dir) {
        if (strpos($class, $namespace) === 0) {
            $relative_class = substr($class, strlen($namespace));
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});


class InternalAnalytics {

    private $dashboard;

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        add_filter( 'option_khm_4a_ingest_token', [ $this, 'get_ingest_token' ] );
        add_filter( 'option_khm_seo_ga4_client_id', [ $this, 'get_ga4_client_id' ] );
        add_filter( 'option_khm_seo_ga4_client_secret', [ $this, 'get_ga4_client_secret' ] );

        $this->dashboard = new KHM_SEO\Analytics\DataAnalysisDashboard();
        $this->ingestion_controller = new KHM\Rest\FourAIngestionController();
        $this->ga4_manager = new KHM_SEO\GoogleAnalytics4\GA4Manager();
    }

    public function get_ingest_token( $value ) {
        return get_option( 'internal_analytics_ingest_token', $value );
    }

    public function get_ga4_client_id( $value ) {
        $credentials = json_decode( get_option( 'internal_analytics_ga4_credentials' ), true );
        return $credentials['web']['client_id'] ?? $value;
    }

    public function get_ga4_client_secret( $value ) {
        $credentials = json_decode( get_option( 'internal_analytics_ga4_credentials' ), true );
        return $credentials['web']['client_secret'] ?? $value;
    }

    public function add_admin_menu() {
        add_menu_page(
            'Internal Analytics',
            'Internal Analytics',
            'manage_options',
            'internal-analytics',
            [ $this, 'render_dashboard' ],
            'dashicons-chart-area',
            20
        );

        add_submenu_page(
            'internal-analytics',
            'Settings',
            'Settings',
            'manage_options',
            'internal-analytics-settings',
            [ $this, 'render_settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'internal_analytics_settings', 'internal_analytics_ga4_credentials' );
        register_setting( 'internal_analytics_settings', 'internal_analytics_ingest_token' );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Internal Analytics Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'internal_analytics_settings' );
                do_settings_sections( 'internal_analytics_settings' );
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">GA4 Credentials (JSON)</th>
                        <td><textarea name="internal_analytics_ga4_credentials" rows="10" cols="50"><?php echo esc_attr( get_option( 'internal_analytics_ga4_credentials' ) ); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Ingestion Token</th>
                        <td><input type="text" name="internal_analytics_ingest_token" value="<?php echo esc_attr( get_option( 'internal_analytics_ingest_token' ) ); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_dashboard() {
        $this->dashboard->render_dashboard();
    }

}

new InternalAnalytics();
