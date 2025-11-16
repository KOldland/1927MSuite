<?php
/**
 * Plugin Name: KH-Image Optimizer
 * Plugin URI:  https://example.com/kh-image
 * Description: Modern image optimization engine for the KH marketing stack.
 * Version:     0.1.0
 * Author:      KH Engineering
 * Author URI:  https://example.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kh-image
 *
 * @package KHImage
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'KH_IMAGE_FILE' ) ) {
	define( 'KH_IMAGE_FILE', __FILE__ );
}

if ( ! defined( 'KH_IMAGE_DIR' ) ) {
	define( 'KH_IMAGE_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'KH_IMAGE_URL' ) ) {
	define( 'KH_IMAGE_URL', plugin_dir_url( __FILE__ ) );
}

require_once KH_IMAGE_DIR . 'src/Autoloader.php';

\KHImage\Autoloader::register();
$kh_image_plugin = \KHImage\Core\Plugin::instance();

register_activation_hook( KH_IMAGE_FILE, array( $kh_image_plugin, 'activate' ) );
register_deactivation_hook( KH_IMAGE_FILE, array( $kh_image_plugin, 'deactivate' ) );
