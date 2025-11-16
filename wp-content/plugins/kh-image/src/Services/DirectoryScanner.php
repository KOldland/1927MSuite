<?php
/**
 * Directory scanning helper for Directory Smush.
 *
 * @package KHImage\Services
 */

namespace KHImage\Services;

/**
 * Class DirectoryScanner.
 */
class DirectoryScanner {

	/**
	 * Scan uploads directory.
	 *
	 * @param string|null $relative_path Path relative to uploads dir.
	 *
	 * @return array
	 */
	public function scan( $relative_path = null ) {
		$uploads = wp_get_upload_dir();
		$root    = trailingslashit( $uploads['basedir'] );

		$path = $root;
		if ( ! empty( $relative_path ) ) {
			$test = realpath( $root . $relative_path );
			if ( false === $test || strpos( $test, $root ) !== 0 ) {
				return array( 'directories' => array(), 'files' => array() );
			}
			$path = trailingslashit( $test );
		}

		$directories = array();
		$files       = array();

		$items = glob( $path . '*', GLOB_NOSORT );
		if ( false === $items ) {
			return array( 'directories' => $directories, 'files' => $files );
		}

		$supported_exts = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif' );

		foreach ( $items as $item ) {
			$name     = basename( $item );
			$is_dir   = is_dir( $item );
			$relative = ltrim( str_replace( $root, '', $item ), '/' );

			if ( $is_dir ) {
				$directories[] = array(
					'name' => $name,
					'path' => $relative,
				);
			} else {
				$ext = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, $supported_exts, true ) ) {
					continue;
				}

				$files[] = array(
					'name' => $name,
					'path' => $relative,
					'size' => filesize( $item ),
				);
			}
		}

		return array(
			'directories' => $directories,
			'files'       => $files,
			'base'        => $relative_path ? $relative_path : '',
		);
	}
}
