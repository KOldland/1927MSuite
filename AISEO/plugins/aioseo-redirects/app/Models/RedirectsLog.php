<?php
namespace AIOSEO\Plugin\Addon\Redirects\Models;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Common\Models as CommonModels;

/**
 * The Redirects DB Model.
 *
 * @since 1.0.0
 */
class RedirectsLog extends CommonModels\Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_redirects_logs';

	/**
	 * Fields that should be hidden when serialized.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $hidden = [ 'id' ];

	/**
	 * Fields that should be json encoded on save and decoded on get.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'request_data' ];

	/**
	 * Return filtered logs.
	 *
	 * @since 1.1.0
	 *
	 * @param  int   $offset The offset to look for.
	 * @return array         The DB results.
	 */
	public static function getFiltered( $offset = 0, $orderBy = 'last_accessed', $orderDir = 'DESC' ) {
		return aioseo()->db->start( 'aioseo_redirects_logs as `l1`' )
			->select( 'l1.url as id, l1.url, l2.hits, l1.created as last_accessed, l1.request_data, l1.ip' )
			->join(
				'(SELECT MAX(id) as id, count(*) as hits FROM ' . aioseo()->db->db->prefix . 'aioseo_redirects_logs GROUP BY `url`) as `l2`',
				'`l2`.`id` = `l1`.`id`',
				'',
				true
			)
			->limit( 20, $offset )
			->orderBy( $orderBy . ' ' . $orderDir )
			->run()
			->result();
	}
}