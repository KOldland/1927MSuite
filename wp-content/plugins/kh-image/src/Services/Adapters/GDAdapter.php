<?php
/**
 * GD fallback optimizer.
 *
 * @package KHImage\Services\Adapters
 */

namespace KHImage\Services\Adapters;

/**
 * Class GDAdapter.
 */
class GDAdapter implements AdapterInterface {

	/**
	 * Optimize file using GD.
	 *
	 * @inheritDoc
	 */
	public function optimize( $file_path, array $settings ) {
		$info = wp_check_filetype( $file_path );
		if ( empty( $info['ext'] ) ) {
			return array( 'bytes_saved' => 0, 'variants' => array() );
		}

		$ext      = strtolower( $info['ext'] );
		$original = filesize( $file_path );
		$quality  = 'lossy' === $settings['mode'] ? 80 : 95;

		$image = null;
		try {
			switch ( $ext ) {
				case 'jpg':
				case 'jpeg':
					$image = imagecreatefromjpeg( $file_path );
					imagejpeg( $image, $file_path, $quality );
					break;
				case 'png':
					$image = imagecreatefrompng( $file_path );
					imagealphablending( $image, false );
					imagesavealpha( $image, true );
					$compression = (int) round( ( 100 - $quality ) / 10 );
					imagepng( $image, $file_path, $compression );
					break;
				default:
					return array( 'bytes_saved' => 0, 'variants' => array() );
			}
		} catch ( \Exception $e ) {
			error_log( sprintf( 'KH-Image GD error: %s', $e->getMessage() ) );
		} finally {
			if ( $image ) {
				imagedestroy( $image );
			}
		}

		clearstatcache( true, $file_path );
		$bytes_saved = max( 0, $original - filesize( $file_path ) );

		return array(
			'bytes_saved' => $bytes_saved,
			'variants'    => array(),
		);
	}

	/**
	 * Check support.
	 *
	 * @return bool
	 */
	public static function is_supported() {
		return function_exists( 'gd_info' );
	}
}
