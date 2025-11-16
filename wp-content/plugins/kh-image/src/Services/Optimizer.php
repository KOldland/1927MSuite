<?php
/**
 * Handles optimization logic (placeholder).
 *
 * @package KHImage\Services
 */

namespace KHImage\Services;

use KHImage\Admin\Settings;
use KHImage\Services\Adapters\AdapterInterface;
use KHImage\Services\Adapters\GDAdapter;
use KHImage\Services\Adapters\ImagickAdapter;

/**
 * Optimizer service orchestrates adapters + stats.
 */
class Optimizer {

	/**
	 * Settings dependency.
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Stats repository.
	 *
	 * @var StatsRepository
	 */
	protected $stats;

	/**
	 * Active adapter.
	 *
	 * @var AdapterInterface
	 */
	protected $adapter;

	/**
	 * Constructor.
	 *
	 * @param Settings        $settings Settings service.
	 * @param StatsRepository $stats    Stats repository.
	 */
	public function __construct( Settings $settings, StatsRepository $stats ) {
		$this->settings = $settings;
		$this->stats    = $stats;
		$this->adapter  = $this->resolve_adapter();
	}

	/**
	 * Optimize a given attachment.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $context       Context label.
	 *
	 * @return void
	 */
	public function optimize_attachment( $attachment_id, $context = 'manual' ) {
		$file_path = get_attached_file( $attachment_id );

		if ( ! $file_path || ! file_exists( $file_path ) || ! $this->adapter ) {
			return false;
		}

		do_action( 'kh_image_before_optimize', $attachment_id, $context, $file_path );

		$result = $this->adapter->optimize( $file_path, $this->settings->get_settings() );

		$this->stats->store(
			$attachment_id,
			array(
				'bytes_saved' => $result['bytes_saved'],
				'context'     => $context,
				'variants'    => $result['variants'],
			)
		);

		do_action( 'kh_image_after_optimize', $attachment_id, $context, $result );

		return true;
	}

	/**
	 * Optimize arbitrary file path (Directory Smush).
	 *
	 * @param string $file_path File path.
	 * @param string $context   Context label.
	 *
	 * @return array
	 */
	public function optimize_file( $file_path, $context = 'directory' ) {
		if ( ! $file_path || ! file_exists( $file_path ) || ! $this->adapter ) {
			return array();
		}

		do_action( 'kh_image_before_optimize_file', $file_path, $context );

		$result = $this->adapter->optimize( $file_path, $this->settings->get_settings() );

		do_action( 'kh_image_after_optimize_file', $file_path, $context, $result );

		return $result;
	}

	/**
	 * Resolve adapter based on environment.
	 *
	 * @return AdapterInterface|null
	 */
	protected function resolve_adapter() {
		if ( ImagickAdapter::is_supported() ) {
			return new ImagickAdapter();
		}

		if ( GDAdapter::is_supported() ) {
			return new GDAdapter();
		}

		return null;
	}
}
