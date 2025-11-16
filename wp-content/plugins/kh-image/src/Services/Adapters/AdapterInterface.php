<?php
/**
 * Adapter contract for image optimization.
 *
 * @package KHImage\Services\Adapters
 */

namespace KHImage\Services\Adapters;

/**
 * Interface AdapterInterface.
 */
interface AdapterInterface {

	/**
	 * Optimize file in place.
	 *
	 * @param string $file_path Absolute file path.
	 * @param array  $settings  Plugin settings.
	 *
	 * @return array {
	 *     @type int $bytes_saved Bytes saved after optimization.
	 *     @type array $variants  Generated variants (webp/avif etc).
	 * }
	 */
	public function optimize( $file_path, array $settings );

	/**
	 * Whether adapter is supported in current environment.
	 *
	 * @return bool
	 */
	public static function is_supported();
}
