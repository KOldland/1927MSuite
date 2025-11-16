<?php
/**
 * Imagick based optimizer.
 *
 * @package KHImage\Services\Adapters
 */

namespace KHImage\Services\Adapters;

use Imagick;

/**
 * Class ImagickAdapter.
 */
class ImagickAdapter implements AdapterInterface {

	/**
	 * Optimize file via Imagick.
	 *
	 * @inheritDoc
	 */
	public function optimize( $file_path, array $settings ) {
		$result = array(
			'bytes_saved' => 0,
			'variants'    => array(),
		);

		try {
			$image    = new Imagick( $file_path );
			$format   = strtolower( $image->getImageFormat() );
			$quality  = 'lossy' === $settings['mode'] ? 82 : 95;
			$original = filesize( $file_path );

			$image->stripImage();
			$image->setImageCompressionQuality( $quality );
			$image->writeImage( $file_path );

			clearstatcache( true, $file_path );
			$result['bytes_saved'] = max( 0, $original - filesize( $file_path ) );

			if ( ! empty( $settings['auto_convert_webp'] ) && in_array( $format, array( 'jpeg', 'jpg', 'png' ), true ) ) {
				$this->generate_variant( $image, $file_path, $settings, 'webp', $result );
				$this->generate_variant( $image, $file_path, $settings, 'avif', $result );
			}

			$image->destroy();
		} catch ( \Exception $e ) {
			error_log( sprintf( 'KH-Image Imagick error: %s', $e->getMessage() ) );
		}

		return $result;
	}

	/**
	 * Generate additional formats if supported.
	 *
	 * @param Imagick $image     Imagick instance.
	 * @param string  $file_path File path.
	 * @param array   $settings  Settings.
	 * @param string  $format    Target format.
	 * @param array   $result    Result reference.
	 *
	 * @return void
	 */
	protected function generate_variant( Imagick $image, $file_path, array $settings, $format, array &$result ) {
		$supported = array_map( 'strtolower', Imagick::queryFormats() );
		if ( ! in_array( strtoupper( $format ), $supported, true ) ) {
			return;
		}

		$variant_path = $file_path . '.' . $format;

		$clone = clone $image;
		$clone->setImageFormat( $format );

		if ( 'avif' === $format ) {
			$clone->setOption( 'avif:lossless', ( 'lossless' === $settings['mode'] ) ? 'true' : 'false' );
		}

		if ( 'webp' === $format ) {
			$clone->setOption( 'webp:lossless', ( 'lossless' === $settings['mode'] ) ? 'true' : 'false' );
		}

		try {
			$clone->writeImage( $variant_path );
			$result['variants'][ $format ] = $variant_path;
		} catch ( \Exception $e ) {
			error_log( sprintf( 'KH-Image %s variant error: %s', strtoupper( $format ), $e->getMessage() ) );
		} finally {
			$clone->destroy();
		}
	}

	/**
	 * Check support.
	 *
	 * @return bool
	 */
	public static function is_supported() {
		return class_exists( '\Imagick' );
	}
}
