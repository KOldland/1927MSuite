<?php
/**
 * Admin asset loader for React bundle.
 *
 * @package KHImage\Admin
 */

namespace KHImage\Admin;

/**
 * Responsible for enqueuing compiled Vite assets.
 */
class Assets {

	/**
	 * Hook into admin.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue compiled assets if present.
	 *
	 * @return void
	 */
	public function enqueue() {
		$asset_dir = KH_IMAGE_DIR . 'admin/dist/';
		$asset_url = KH_IMAGE_URL . 'admin/dist/';
		$manifest  = $asset_dir . '.vite/manifest.json';

		if ( ! file_exists( $manifest ) ) {
			return;
		}

		$manifest_data = json_decode( file_get_contents( $manifest ), true );
		$entry         = reset( $manifest_data );

		if ( empty( $entry['file'] ) ) {
			return;
		}

		$js_file = $asset_dir . $entry['file'];

		wp_enqueue_script(
			'kh-image-admin',
			$asset_url . $entry['file'],
			array( 'wp-element' ),
			filemtime( $js_file ),
			true
		);

		wp_localize_script(
			'kh-image-admin',
			'KHImageSettings',
			array(
				'restUrl' => esc_url_raw( rest_url( 'kh-image/v1' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			)
		);

		if ( ! empty( $entry['css'] ) && is_array( $entry['css'] ) ) {
			foreach ( $entry['css'] as $css_file ) {
				$full_path = $asset_dir . $css_file;
				wp_enqueue_style(
					'kh-image-admin',
					$asset_url . $css_file,
					array(),
					filemtime( $full_path )
				);
			}
		}
	}
}
