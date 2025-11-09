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
class Redirect extends CommonModels\Model {
	/**
	 * The name of the table in the database, without the prefix.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $table = 'aioseo_redirects';

	/**
	 * Fields that should be numeric values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $numericFields = [ 'id', 'type', 'hits' ];

	/**
	 * Fields that should be boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $booleanFields = [
		'regex',
		'ignore_slash',
		'ignore_case',
		'enabled'
	];

	/**
	 * Fields that should be boolean values.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $appends = [
		'hits'
	];

	/**
	 * Fields that should be encoded/decoded on save/get.
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	protected $jsonFields = [ 'custom_rules' ];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $var This can be the primary key of the resource, or it could be an array of data to manufacture a resource without a database query.
	 */
	public function __construct( $var = null ) {
		parent::__construct( $var );

		// Add some additional data here.
		$hits = 0;
		if ( isset( $this->id ) ) {
			$results = aioseo()->db->start( 'aioseo_redirects_hits' )
				->where( 'redirect_id', $this->id )
				->select( 'count' )
				->run()
				->result();

			if ( ! empty( $results ) ) {
				$hits = $results[0];
			}
		}

		$this->hits  = ! empty( $hits->count ) ? (int) $hits->count : 0;
		$this->group = $this->getGroupName( $this->group );
	}

	/**
	 * Transforms some of the hashs we need.
	 *
	 * @since 1.1.0
	 *
	 * @param  array $data The data array to transform.
	 * @return array       The transformed data.
	 */
	protected function transform( $data, $set = false ) {
		$data = parent::transform( $data, $set );

		if ( empty( $data['source_url_hash'] ) && ! empty( $data['source_url'] ) ) {
			$data['source_url_hash'] = sha1( $data['source_url'] );
			if ( ! empty( $data['custom_rules'] ) ) {
				$data['source_url_hash'] = sha1( $data['source_url'] . wp_json_encode( $data['custom_rules'] ) );
			}
		}

		if ( empty( $data['source_url_match_hash'] ) && ! empty( $data['source_url_match'] ) ) {
			$data['source_url_match_hash'] = sha1( $data['source_url_match'] );
		}

		if ( empty( $data['target_url_hash'] ) && ! empty( $data['target_url'] ) ) {
			$data['target_url_hash'] = sha1( $data['target_url'] );
		}

		return $data;
	}

	/**
	 * Retrieve a list of filters for the table.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $filter The filter to set active.
	 * @return array          An array of filters.
	 */
	public static function getFilters( $filter = 'all' ) {
		return [
			[
				'slug'   => 'all',
				'name'   => __( 'All', 'aioseo-redirects' ),
				'count'  => aioseo()->db->start( 'aioseo_redirects' )->count(),
				'active' => 'all' === $filter
			],
			[
				'slug'   => 'enabled',
				'name'   => __( 'Enabled', 'aioseo-redirects' ),
				'count'  => aioseo()->db->start( 'aioseo_redirects' )->where( 'enabled', 1 )->count(),
				'active' => 'enabled' === $filter
			],
			[
				'slug'   => 'disabled',
				'name'   => __( 'Disabled', 'aioseo-redirects' ),
				'count'  => aioseo()->db->start( 'aioseo_redirects' )->where( 'enabled', 0 )->count(),
				'active' => 'disabled' === $filter
			]
		];
	}

	/**
	 * Retrieves a pretty name for our built-in groups.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $group The original group name.
	 * @return string        The pretty group name.
	 */
	private function getGroupName( $group ) {
		switch ( $group ) {
			case 'external':
				return __( 'External', 'aioseo-redirects' );
			case 'modified':
				return __( 'Modified Post', 'aioseo-redirects' );
			case '404':
				return $group;
			case 'manual':
			default:
				return __( 'Manual Redirect', 'aioseo-redirects' );
		}
	}

	/**
	 * Lookup a redirect by source url.
	 *
	 * @since 1.0.0
	 *
	 * @param  string          $url       The source URL.
	 * @param  int|null        $excludeId The ID to exclude.
	 * @return Models\Redirect            The redirect object.
	 */
	public static function getRedirectBySourceUrl( $url, $excludeId = null ) {
		$query = aioseo()->db
			->start( 'aioseo_redirects' )
			->where( 'source_url_hash', sha1( $url ) );

		if ( $excludeId ) {
			$query->where( 'id !=', $excludeId );
		}

		return $query->run()
			->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect' );
	}

	/**
	 * Lookup a redirect by ID.
	 *
	 * @since 1.1.4
	 *
	 * @param  int             $redirectId The redirect ID.
	 * @return Models\Redirect             The redirect object.
	 */
	public static function getRedirectById( $redirectId ) {
		return aioseo()->db
			->start( 'aioseo_redirects' )
			->where( 'id', $redirectId )
			->run()
			->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect' );
	}

	/**
	 * Overrides the parent's save() function to clear the Redirects cache.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function save() {
		parent::save();
		aioseoRedirects()->cache->clearRedirects();
	}

	/**
	 * Overrides the parent's delete() function to clear the Redirects cache.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function delete() {
		parent::delete();
		aioseoRedirects()->cache->clearRedirects();
	}
}