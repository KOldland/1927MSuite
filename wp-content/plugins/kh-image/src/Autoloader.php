<?php
/**
 * Lightweight PSR-4 autoloader for KH-Image.
 *
 * @package KHImage
 */

namespace KHImage;

/**
 * Class Autoloader.
 */
class Autoloader {

	/**
	 * Root namespace prefix.
	 *
	 * @var string
	 */
	const PREFIX = __NAMESPACE__ . '\\';

	/**
	 * Register autoloader with SPL.
	 *
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Load classes using PSR-4 convention.
	 *
	 * @param string $class Fully qualified class name.
	 *
	 * @return void
	 */
	protected static function load( $class ) {
		if ( 0 !== strpos( $class, self::PREFIX ) ) {
			return;
		}

		$relative = substr( $class, strlen( self::PREFIX ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = KH_IMAGE_DIR . 'src/' . $relative . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
