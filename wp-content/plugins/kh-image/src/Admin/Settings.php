<?php
/**
 * Admin settings scaffold.
 *
 * @package KHImage\Admin
 */

namespace KHImage\Admin;

/**
 * Settings manager responsible for options + quick setup.
 */
class Settings {

	/**
	 * Option name used to persist global settings.
	 *
	 * @var string
	 */
	const OPTION = 'kh_image_settings';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register options via Settings API.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'kh_image',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => $this->defaults(),
			)
		);
	}

	/**
	 * Default settings for the optimizer.
	 *
	 * @return array
	 */
	protected function defaults() {
		return array(
			'mode'              => 'lossless', // lossless|lossy.
			'auto_resize'       => false,
			'bulk_threshold'    => 50,
			'quick_setup_done'  => false,
			'auto_convert_webp' => true,
			'enable_cdn'        => false,
			'bulk_last_run'     => 0,
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $value Submitted values.
	 *
	 * @return array
	 */
	public function sanitize( $value ) {
		$defaults = $this->defaults();

		$value = wp_parse_args( (array) $value, $defaults );
		$value['mode']        = in_array( $value['mode'], array( 'lossless', 'lossy' ), true ) ? $value['mode'] : $defaults['mode'];
		$value['auto_resize'] = ! empty( $value['auto_resize'] );
		$value['bulk_threshold'] = max( 10, absint( $value['bulk_threshold'] ) );
		$value['quick_setup_done'] = ! empty( $value['quick_setup_done'] );
		$value['auto_convert_webp'] = ! empty( $value['auto_convert_webp'] );
		$value['enable_cdn']        = ! empty( $value['enable_cdn'] );
		$value['bulk_last_run']     = absint( $value['bulk_last_run'] );

		return $value;
	}

	/**
	 * Retrieve settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return get_option( self::OPTION, $this->defaults() );
	}

	/**
	 * Update settings programmatically.
	 *
	 * @param array $value New values.
	 *
	 * @return array
	 */
	public function update_settings( $value ) {
		$sanitized = $this->sanitize( $value );
		update_option( self::OPTION, $sanitized );

		return $sanitized;
	}

	/**
	 * Apply quick setup payload.
	 *
	 * @param array $payload Payload from REST request.
	 *
	 * @return array
	 */
	public function apply_quick_setup( $payload ) {
		$current = $this->get_settings();
		$updated = array_merge(
			$current,
			array(
				'mode'              => isset( $payload['mode'] ) ? $payload['mode'] : $current['mode'],
				'auto_resize'       => ! empty( $payload['auto_resize'] ),
				'auto_convert_webp' => ! empty( $payload['auto_convert_webp'] ),
				'enable_cdn'        => ! empty( $payload['enable_cdn'] ),
				'quick_setup_done'  => true,
			)
		);

		return $this->update_settings( $updated );
	}

	/**
	 * Update timestamp of last bulk run.
	 *
	 * @return void
	 */
	public function mark_bulk_run() {
		$current                   = $this->get_settings();
		$current['bulk_last_run'] = time();
		$this->update_settings( $current );
	}
}
