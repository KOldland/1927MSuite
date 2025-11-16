<?php
/**
 * Handles storage of optimization stats.
 *
 * @package KHImage\Services
 */

namespace KHImage\Services;

/**
 * Class StatsRepository.
 */
class StatsRepository {

	/**
	 * Meta key.
	 *
	 * @var string
	 */
	const META_KEY = '_kh_image_stats';

	/**
	 * Store stats for attachment.
	 *
	 * @param int   $attachment_id Attachment ID.
	 * @param array $data          Stat data.
	 *
	 * @return void
	 */
	public function store( $attachment_id, array $data ) {
		$current = $this->get( $attachment_id );

		$data = array_merge(
			$current,
			array(
				'bytes_saved' => isset( $data['bytes_saved'] ) ? absint( $data['bytes_saved'] ) : 0,
				'last_run'    => time(),
				'context'     => $data['context'],
				'variants'    => isset( $data['variants'] ) ? $data['variants'] : array(),
			)
		);

		update_post_meta( $attachment_id, self::META_KEY, $data );
	}

	/**
	 * Retrieve stats.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	public function get( $attachment_id ) {
		return (array) get_post_meta(
			$attachment_id,
			self::META_KEY,
			true
		);
	}
}
