<?php
namespace AIOSEO\Plugin\Addon\Redirects\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Main;
use AIOSEO\Plugin\Addon\Redirects\Models;
use AIOSEO\Plugin\Addon\Redirects\Utils;
use AIOSEO\Plugin\Common\Models as CommonModels;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route class for the API.
 *
 * @since 1.0.0
 */
class Redirects {
	/**
	 * The search term we are using to lookup page or posts.
	 *
	 * @since 1.0.1
	 *
	 * @var string
	 */
	public static $searchTerm = '';

	/**
	 * Get the redirect options.
	 *
	 * @since 1.0.1
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function getOptions() {
		return new \WP_REST_Response( [
			'success'   => true,
			'options'   => aioseoRedirects()->options->all(),
			'importers' => aioseoRedirects()->importExport->plugins()
		], 200 );
	}

	/**
	 * Navigates between the all/enabled/disabled filters in the table.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function filter( $request ) {
		$rows       = [];
		$total      = 0;
		$filter     = $request['filter'];
		$body       = $request->get_json_params();
		$additional = ! empty( $body['additional'] ) ? $body['additional'] : [];
		$page       = ! empty( $body['page'] ) ? $body['page'] : 1;
		$offset     = 1 === $page ? 0 : ( $page - 1 ) * 20;
		$query      = aioseo()->db->start( 'aioseo_redirects' );
		$totalQuery = aioseo()->db->noConflict()->start( 'aioseo_redirects' );

		if ( ! empty( $additional ) ) {
			$group = ! empty( $additional['group'] ) ? sanitize_text_field( $additional['group'] ) : null;
			if ( $group && 'all' !== $group ) {
				$query->where( 'group', $group );
				$totalQuery->where( 'group', $group );
			}
		}

		switch ( $filter ) {
			case 'all':
				$total = $totalQuery->count();
				$rows = array_values(
					$query->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case 'enabled':
				$total = $totalQuery->where( 'enabled', 1 )->count();
				$rows  = array_values(
					$query->where( 'enabled', 1 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case 'disabled':
				$total = $totalQuery->where( 'enabled', 0 )->count();
				$rows  = array_values(
					$query->where( 'enabled', 0 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case '404':
				$total = aioseo()->db->start( 'aioseo_redirects_404_logs' )->groupBy( 'url' )->count();
				$rows  = Models\Redirects404Log::getFiltered( $offset );
				break;
			case 'logs':
				$total = aioseo()->db->start( 'aioseo_redirects_logs' )->groupBy( 'url' )->count();
				$rows  = Models\RedirectsLog::getFiltered( $offset );
				break;
			default:
				return new \WP_REST_Response( [
					'success' => false
				], 404 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'rows'    => $rows,
			'totals'  => [
				'total' => $total,
				'pages' => 0 === $total ? 1 : ceil( $total / 20 ),
				'page'  => $page
			],
			'filters' => Models\Redirect::getFilters( $filter )
		], 200 );
	}

	/**
	 * Paginates to the passed in page.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function paginate( $request ) {
		$filter     = $request['filter'];
		$page       = (int) $request['page'];
		$offset     = 1 === $page ? 0 : ( $page - 1 ) * 20;
		$body       = $request->get_json_params();
		$orderBy    = ! empty( $body['orderBy'] ) ? $body['orderBy'] : [];
		$sortDir    = 'DESC';
		$column     = 'last_accessed';
		$additional = ! empty( $body['additional'] ) ? $body['additional'] : [];
		$query      = aioseo()->db->start( 'aioseo_redirects' );
		$totalQuery = aioseo()->db->noConflict()->start( 'aioseo_redirects' );

		if ( ! empty( $additional ) ) {
			$group = ! empty( $additional['group'] ) ? sanitize_text_field( $additional['group'] ) : null;
			if ( $group && 'all' !== $group ) {
				$query->where( 'group', $group );
				$totalQuery->where( 'group', $group );
			}
		}

		if ( ! empty( $orderBy ) ) {
			$column  = $orderBy['column'];
			$sortDir = strtoupper( $orderBy['sortDir'] );
		}

		switch ( $filter ) {
			case 'all':
				$total = $totalQuery->count();
				$rows  = array_values(
					$query->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case 'enabled':
				$total = $totalQuery->where( 'enabled', 1 )->count();
				$rows  = array_values(
					$query->where( 'enabled', 1 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case 'disabled':
				$total = $totalQuery->where( 'enabled', 0 )->count();
				$rows  = array_values(
					$query->where( 'enabled', 0 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case '404':
				$total = aioseo()->db->start( 'aioseo_redirects_404_logs' )->groupBy( 'url' )->count();
				$rows  = Models\Redirects404Log::getFiltered( $offset, $column, $sortDir );
				break;
			case 'logs':
				$total = aioseo()->db->start( 'aioseo_redirects_logs' )->groupBy( 'url' )->count();
				$rows  = Models\RedirectsLog::getFiltered( $offset, $column, $sortDir );
				break;
			default:
				return new \WP_REST_Response( [
					'success' => false
				], 404 );
		}

		return new \WP_REST_Response( [
			'success' => true,
			'rows'    => $rows,
			'totals'  => [
				'total' => $total,
				'pages' => 0 === $total ? 1 : ceil( $total / 20 ),
				'page'  => $page
			]
		], 200 );
	}

	/**
	 * Process bulk actions.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function bulk( $request ) {
		$action      = $request['action'];
		$body        = $request->get_json_params();
		$currentSlug = ! empty( $body['currentSlug'] ) ? $body['currentSlug'] : 'all';
		$currentPage = ! empty( $body['currentPage'] ) ? (int) $body['currentPage'] : 1;
		$offset      = 1 === $currentPage ? 0 : ( $currentPage - 1 ) * 20;
		$rowIds      = ! empty( $body['rowIds'] ) ? $body['rowIds'] : [];
		$additional  = ! empty( $body['additional'] ) ? $body['additional'] : [];
		$searchTerm  = ! empty( $body['searchTerm'] ) ? $body['searchTerm'] : null;
		$where       = self::getSearchWhere( $searchTerm );

		if ( empty( $rowIds ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		switch ( $action ) {
			case 'enable':
			case 'disable':
				aioseo()->db->update( 'aioseo_redirects' )
					->whereIn( 'id', array_values( $rowIds ) )
					->set(
						[
							'enabled' => 'enable' === $action ? 1 : 0
						]
					)
					->run();

				// Clear the redirects cache.
				aioseoRedirects()->cache->clearRedirects();
				break;
			case 'reset-hits':
				aioseo()->db->update( 'aioseo_redirects_hits' )
					->whereIn( 'redirect_id', $rowIds )
					->set( [ 'count' => 0 ] )
					->run();
				break;
		}

		$query      = aioseo()->db->start( 'aioseo_redirects' );
		$totalQuery = aioseo()->db->noConflict()->start( 'aioseo_redirects' );
		if ( ! empty( $additional ) ) {
			$group = ! empty( $additional['group'] ) ? sanitize_text_field( $additional['group'] ) : null;
			if ( $group && 'all' !== $group ) {
				$query->where( 'group', $group );
				$totalQuery->where( 'group', $group );
			}
		}

		if ( ! empty( $searchTerm ) ) {
			$query->whereRaw( $where );
			$totalQuery->whereRaw( $where );
		}

		switch ( $currentSlug ) {
			case 'enabled':
				$total = $totalQuery->where( 'enabled', 1 )->count();
				$rows  = array_values(
					$query->where( 'enabled', 1 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case 'disabled':
				$total = $totalQuery->where( 'enabled', 0 )->count();
				$rows  = array_values(
					$query->where( 'enabled', 0 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
			case 'all':
			default:
				$total = $totalQuery->count();
				$rows  = array_values(
					$query->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				break;
		}

		aioseoRedirects()->server->rewrite();

		return new \WP_REST_Response( [
			'success' => true,
			'rows'    => $rows,
			'totals'  => [
				'total' => $total,
				'pages' => 0 === $total ? 1 : ceil( $total / 20 ),
				'page'  => $currentPage
			],
			'filters' => Models\Redirect::getFilters( $currentSlug )
		], 200 );
	}

	/**
	 * Search for a specific url or set of urls.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function search( $request ) {
		$searchTerm = urldecode( $request['searchTerm'] );
		$page       = (int) $request['page'];
		$offset     = 1 === $page ? 0 : ( $page - 1 ) * 20;
		$where      = self::getSearchWhere( $searchTerm );
		$rows       = array_values(
			aioseo()->db->start( 'aioseo_redirects' )
				->whereRaw( $where )
				->limit( 20, $offset )
				->run()
				->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
		);

		$total = aioseo()->db->start( 'aioseo_redirects' )->whereRaw( $where )->count();

		return new \WP_REST_Response( [
			'success' => true,
			'rows'    => $rows,
			'totals'  => [
				'total' => $total,
				'pages' => 0 === $total ? 1 : ceil( $total / 20 ),
				'page'  => $page
			],
			'filters' => Models\Redirect::getFilters( null )
		], 200 );
	}

	/**
	 * Get a where clause for the search term.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $searchTerm The search term.
	 * @return string             The search where clause.
	 */
	private static function getSearchWhere( $searchTerm ) {
		$searchTerm = esc_sql( $searchTerm );
		if ( ! $searchTerm ) {
			return '';
		}

		$where = '';
		if ( is_int( $searchTerm ) ) {
			$where .= '
				id = ' . (int) $searchTerm . ' OR
				type = ' . (int) $searchTerm . ' OR
			';
		}
		$where .= '
			source_url LIKE \'%' . $searchTerm . '%\' OR
			target_url LIKE \'%' . $searchTerm . '%\' OR
			source_url LIKE \'%' . str_replace( '%20', '-', $searchTerm ) . '%\' OR
			target_url LIKE \'%' . str_replace( '%20', '-', $searchTerm ) . '%\' OR
			source_url LIKE \'%' . str_replace( '%20', '+', $searchTerm ) . '%\' OR
			target_url LIKE \'%' . str_replace( '%20', '+', $searchTerm ) . '%\'
		';

		return $where;
	}

	/**
	 * Creates a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function create( $request ) {
		return self::createOrUpdate( $request );
	}

	/**
	 * Updates a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function update( $request ) {
		$redirectId = (int) $request['id'];
		if ( empty( $redirectId ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		return self::createOrUpdate( $request, $redirectId );
	}

	/**
	 * Create or update a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request $request    The Rest request.
	 * @param  int|null         $redirectId The redirect ID.
	 * @return WP_REST_Response             The Rest Response.
	 */
	private static function createOrUpdate( $request, $redirectId = null ) {
		$body                  = $request->get_json_params();
		$sourceUrls            = ! empty( $body['sourceUrls'] ) ? $body['sourceUrls'] : [];
		$targetUrl             = ! empty( $body['targetUrl'] ) ? sanitize_text_field( $body['targetUrl'] ) : '';
		$defaultRedirectType   = json_decode( aioseoRedirects()->options->redirectDefaults->redirectType )->value;
		$redirectType          = isset( $body['redirectType'] ) ? (int) $body['redirectType'] : $defaultRedirectType;
		$redirectTypeHasTarget = isset( $body['redirectTypeHasTarget'] ) ? (bool) $body['redirectTypeHasTarget'] : true;
		$defaultQueryParam     = json_decode( aioseoRedirects()->options->redirectDefaults->queryParam )->value;
		$queryParam            = ! empty( $body['queryParam'] ) ? sanitize_text_field( $body['queryParam'] ) : $defaultQueryParam;
		$customRules           = ! empty( $body['customRules'] ) ? $body['customRules'] : null;
		$group                 = ! empty( $body['group'] ) ? sanitize_text_field( $body['group'] ) : 'manual';

		if ( empty( $sourceUrls ) || ( $redirectTypeHasTarget && empty( $targetUrl ) ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		// Sanitize custom rule values.
		if ( $customRules ) {
			foreach ( $customRules as $customRuleKey => &$customRule ) {
				if ( empty( $customRule['value'] ) ) {
					unset( $customRules[ $customRuleKey ] );
				}

				$customRule['value'] = is_array( $customRule['value'] ) ? array_map( 'trim', $customRule['value'] ) : trim( $customRule['value'] );
			}
		}

		$failed = [];
		foreach ( $sourceUrls as $sourceUrl ) {
			$urlForDuplicates = ! empty( $customRules ) ? $sourceUrl['url'] . wp_json_encode( $customRules ) : $sourceUrl['url'];

			$redirect = empty( $redirectId )
				? Models\Redirect::getRedirectBySourceUrl( $urlForDuplicates )
				: aioseo()->db
					->start( 'aioseo_redirects' )
					->where( 'id', $redirectId )
					->run()
					->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect' );

			if ( $redirectId ) {
				if ( ! $redirect->exists() ) {
					return new \WP_REST_Response( [
						'success' => false
					], 404 );
				}

				$duplicate = Models\Redirect::getRedirectBySourceUrl( $urlForDuplicates, $redirectId );
				if ( $duplicate->exists() ) {
					$failed[] = $sourceUrl['url'];
					continue;
				}
			}

			if ( ! $redirectId && $redirect->exists() ) {
				$failed[] = $sourceUrl['url'];
				continue;
			}

			$matchedUrl = ! empty( $sourceUrl['regex'] ) ? 'regex' : Utils\Request::getMatchedUrl( $sourceUrl['url'] );
			$redirect->set( [
				'source_url'       => $sourceUrl['url'],
				'source_url_match' => $matchedUrl,
				'target_url'       => Utils\Request::getTargetUrl( $targetUrl ),
				'type'             => $redirectType,
				'query_param'      => $queryParam,
				'custom_rules'     => $customRules,
				'group'            => $group,
				'regex'            => ! empty( $sourceUrl['regex'] ) ? true : false,
				'ignore_slash'     => ! empty( $sourceUrl['ignoreSlash'] ) ? true : false,
				'ignore_case'      => ! empty( $sourceUrl['ignoreCase'] ) ? true : false
			] );
			$redirect->save();

			if ( ! $redirectId ) {
				// If this is a 404 redirect, let's delete the 404's that match.
				if ( '404' === $group ) {
					aioseo()->db
						->delete( 'aioseo_redirects_404_logs' )
						->where( 'url', $sourceUrl['url'] )
						->run();
				}
			}
		}

		aioseoRedirects()->server->rewrite();

		if ( ! empty( $failed ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'failed'  => $failed
			], 409 );
		}

		return new \WP_REST_Response( [
			'success'  => true,
			'redirect' => $redirect
		], 200 );
	}

	/**
	 * Deletes a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function delete( $request ) {
		$redirectId = (int) $request['id'];
		if ( empty( $redirectId ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		aioseo()->db
			->delete( 'aioseo_redirects_hits' )
			->where( 'redirect_id', $redirectId )
			->run();

		$redirect = aioseo()->db
			->start( 'aioseo_redirects' )
			->where( 'id', $redirectId )
			->run()
			->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect' );

		if ( $redirect->exists() ) {
			$redirect->delete();
		}

		aioseoRedirects()->server->rewrite();

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Tests a redirect.
	 *
	 * @since 1.1.4
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function test( $request ) {
		$redirectId = (int) $request['id'];
		$redirect   = Models\Redirect::getRedirectById( $redirectId );
		if ( empty( $redirectId ) || ! $redirect->exists() ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$sourceUrl = $request['sourceUrl'] ?: $redirect->source_url;
		$sourceUrl = Utils\Request::formatSourceUrl( $sourceUrl );
		$response = wp_remote_get( $sourceUrl, [
			'redirection' => 0,
			'timeout'     => 10,
			'sslverify'   => false
		] );

		$errors       = [];
		$responseCode = wp_remote_retrieve_response_code( $response );
		if ( $responseCode !== $redirect->type ) {
			$errors[] = sprintf(
				// Translators: 1 - HTTP status code expected, 2 - HTTP status code received.
				__( 'Response code was not the same. Expected %s and received %s', 'aioseo-redirects' ),
				$redirect->type,
				$responseCode
			);
		}

		// Only test the target URL if we're expecting a redirect.
		if ( 300 <= $redirect->type && 399 >= $redirect->type ) {
			$location  = wp_remote_retrieve_header( $response, 'location' );
			$targetUrl = Utils\Request::formatTargetUrl( $redirect->target_url );

			$locationParse  = wp_parse_url( $location );
			$targetUrlParse = wp_parse_url( $targetUrl );
			if (
				404 !== $responseCode && (
					$locationParse['scheme'] !== $targetUrlParse['scheme']
					|| $locationParse['host'] !== $targetUrlParse['host']
					|| $locationParse['path'] !== $targetUrlParse['path']
				)
			) {
				$errors[] = sprintf(
					// Translators: 1 - URL expected, 2 - URL found.
					__( 'Target url was not the same. Expected %s and found %s', 'aioseo-redirects' ),
					'<strong>' . Utils\Request::buildUrl( $targetUrlParse, [], [ 'query' ] ) . '</strong>',
					'<strong>' . Utils\Request::buildUrl( $locationParse, [], [ 'query' ] ) . '</strong>'
				);
			}
		}

		// Do the x-redirect-by test only if we're not using server level redirects with Apache.
		if ( 'apache' !== aioseoRedirects()->server->getName() ) {
			$xRedirectBy = wp_remote_retrieve_header( $response, 'x-redirect-by' );
			if ( ! empty( $xRedirectBy ) && 'AIOSEO' !== $xRedirectBy ) {
				$errors[] = sprintf(
					// Translators: 1 - HTTP header 'x-redirect-by'.
					__( 'This redirect seems not to be done by AIOSEO. Expected header \'x-redirect-by\' to be \'AIOSEO\' but found \'%s\' instead', 'aioseo-redirects' ),
					$xRedirectBy
				);
			}
		}

		return new \WP_REST_Response( [
			'success'  => true,
			'errors'   => $errors,
			'redirect' => [
				'responseCode' => $responseCode,
				'sourceUrl'    => $sourceUrl,
				'targetUrl'    => $targetUrl,
				'location'     => $location,
				'xRedirectBy'  => $xRedirectBy
			]
		], 200 );
	}

	/**
	 * Deletes redirects in bulk.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function bulkDelete( $request ) {
		$body        = $request->get_json_params();
		$currentSlug = ! empty( $body['currentSlug'] ) ? $body['currentSlug'] : 'all';
		$currentPage = ! empty( $body['currentPage'] ) ? (int) $body['currentPage'] : 1;
		$offset      = 1 === $currentPage ? 0 : ( $currentPage - 1 ) * 20;
		$rowIds      = ! empty( $body['rowIds'] ) ? $body['rowIds'] : [];
		$additional  = ! empty( $body['additional'] ) ? $body['additional'] : [];

		if ( empty( $rowIds ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		aioseo()->db
			->delete( 'aioseo_redirects_hits' )
			->whereIn( 'redirect_id', $rowIds )
			->run();

		aioseo()->db
			->delete( 'aioseo_redirects' )
			->whereIn( 'id', $rowIds )
			->run();

		$query = aioseo()->db->start( 'aioseo_redirects' );
		if ( ! empty( $additional ) ) {
			$group = ! empty( $additional['group'] ) ? sanitize_text_field( $additional['group'] ) : null;
			if ( $group && 'all' !== $group ) {
				$query->where( 'group', $group );
			}
		}

		switch ( $currentSlug ) {
			case 'enabled':
				$rows = array_values(
					$query->where( 'enabled', 1 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				$total = aioseo()->db->start( 'aioseo_redirects' )->where( 'enabled', 1 )->count();
				break;
			case 'disabled':
				$rows = array_values(
					$query->where( 'enabled', 0 )
						->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				$total = aioseo()->db->start( 'aioseo_redirects' )->where( 'enabled', 0 )->count();
				break;
			case 'all':
			default:
				$rows = array_values(
					$query->orderBy( 'id DESC' )
						->limit( 20, $offset )
						->run()
						->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
				);
				$total = aioseo()->db->start( 'aioseo_redirects' )->count();
				break;
		}

		aioseoRedirects()->server->rewrite();

		return new \WP_REST_Response( [
			'success' => true,
			'rows'    => $rows,
			'totals'  => [
				'total' => $total,
				'pages' => 0 === $total ? 1 : ceil( $total / 20 ),
				'page'  => $currentPage
			],
			'filters' => Models\Redirect::getFilters( $currentSlug )
		], 200 );
	}

	/**
	 * Deletes a 404 log entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function delete404( $request ) {
		$body = $request->get_json_params();
		$urls = ! empty( $body['urls'] ) ? array_map( 'sanitize_text_field', $body['urls'] ) : [];
		if ( empty( $urls ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		aioseo()->db
			->delete( 'aioseo_redirects_404_logs' )
			->whereIn( 'url', $urls )
			->run();

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Deletes a log entry.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function deleteLog( $request ) {
		$body = $request->get_json_params();
		$urls = ! empty( $body['urls'] ) ? array_map( 'sanitize_text_field', $body['urls'] ) : [];
		if ( empty( $urls ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		aioseo()->db
			->delete( 'aioseo_redirects_logs' )
			->whereIn( 'url', $urls )
			->run();

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Exports server redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function export( $request ) {
		$body   = $request->get_json_params();
		$groups = ! empty( $body['groups'] ) ? array_map( 'sanitize_text_field', $body['groups'] ) : [];
		$type   = ! empty( $body['type'] ) ? array_map( 'sanitize_text_field', $body['type'] ) : 'json';
		$type   = sanitize_text_field( $request['type'] );
		if ( empty( $groups ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$redirects    = '';
		$allRedirects = aioseo()->db->start( 'aioseo_redirects' )
			->select( '`id`, `source_url`, `source_url_match`, `target_url`, `type`, `query_param`, `custom_rules`, `group`, `regex`, `ignore_slash`, `ignore_case`, `enabled`, `created`, `updated`' )
			->whereIn( '`group`', $groups )
			->run()
			->result();

		switch ( $type ) {
			case 'htaccess':
				$server = new Main\Server\Apache();
				foreach ( $allRedirects as $redirect ) {
					$redirects .= $server->format( $redirect ) . PHP_EOL;
				}
				break;
			case 'nginx':
				$server = new Main\Server\Nginx();
				foreach ( $allRedirects as $redirect ) {
					$redirects .= $server->format( $redirect ) . PHP_EOL;
				}
				break;
			case 'json':
			default:
				$redirects = $allRedirects;
				break;
		}

		return new \WP_REST_Response( [
			'success'   => true,
			'redirects' => $redirects
		], 200 );
	}

	/**
	 * Exports server redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function exportServer( $request ) {
		$server     = null;
		$serverType = sanitize_text_field( $request['server'] );
		if ( 'apache' === $serverType ) {
			$server = new Main\Server\Apache();
		} elseif ( 'nginx' === $serverType ) {
			$server = new Main\Server\Nginx();
		}

		if ( empty( $server ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		return new \WP_REST_Response( [
			'success'   => true,
			'redirects' => $server->getConfigFileContent()
		], 200 );
	}

	/**
	 * Exports logs.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function exportLogs( $request ) {
		$type = sanitize_text_field( $request['type'] );
		if ( empty( $type ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$tableName = '404' === $type ? 'aioseo_redirects_404_logs' : 'aioseo_redirects_logs';

		$allLogs = aioseo()->db->start( $tableName )
			->run()
			->result();

		$content = '404' === $type ? 'date,source,ip,referrer,useragent' : 'date,source,target,ip,referrer,useragent';
		$content = $content . PHP_EOL;
		foreach ( $allLogs as $log ) {
			if ( ! isset( $log->sent_to ) ) {
				$log->sent_to = null;
			}

			$data = [
				$log->created,
				$log->url,
				$log->sent_to,
				$log->ip,
				$log->referrer,
				$log->agent
			];

			$content .= implode( ',', $data ) . PHP_EOL;
		}

		return new \WP_REST_Response( [
			'success'   => true,
			'redirects' => $content
		], 200 );
	}

	/**
	 * Import settings from external file.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function import( $request ) {
		$file     = $request->get_file_params()['file'];
		$wpfs     = aioseo()->helpers->wpfs();
		$contents = @$wpfs->get_contents( $file['tmp_name'] );
		if ( ! empty( $file['type'] ) && 'application/json' === $file['type'] ) {
			// Since this could be any file, we need to pretend like every variable here is missing.
			$contents = json_decode( $contents, true );
			if ( empty( $contents ) || ! is_array( $contents ) ) {
				return new \WP_REST_Response( [
					'success' => false
				], 400 );
			}

			foreach ( $contents as $redirectData ) {
				$redirect = new Models\Redirect( $redirectData['id'] );

				if (
					empty( $redirectData['source_url'] ) ||
					empty( $redirectData['source_url_match'] ) ||
					empty( $redirectData['target_url'] )
				) {
					continue;
				}

				$redirectData['source_url_match_hash'] = sha1( Utils\Request::getMatchedUrl( $redirectData['source_url_match'] ) );
				$redirectData['target_url_hash']       = sha1( Utils\Request::getTargetUrl( $redirectData['target_url'] ) );

				$redirect->set( $redirectData );
				$redirect->save();
			}
		}

		$total = aioseo()->db->start( 'aioseo_redirects' )->count();
		$rows  = array_values(
			aioseo()->db->start( 'aioseo_redirects' )
				->orderBy( 'id DESC' )
				->limit( 20, 0 )
				->run()
				->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
		);

		return new \WP_REST_Response( [
			'success' => true,
			'rows'    => $rows,
			'totals'  => [
				'total' => $total,
				'pages' => 0 === $total ? 1 : ceil( $total / 20 ),
				'page'  => 1
			],
			'filters' => Models\Redirect::getFilters( 'all' )
		], 200 );
	}

	/**
	 * Searches for posts by ID/name.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function getPosts( $request ) {
		$body = $request->get_json_params();

		if ( empty( $body['query'] ) ) {
			return new \WP_REST_Response( [
				'success' => false,
				'message' => 'No search term was provided.'
			], 400 );
		}

		$args = [
			's'                => $body['query'],
			'numberposts'      => 20,
			'post_status'      => [ 'publish', 'draft', 'future', 'pending' ],
			'post_type'        => aioseo()->helpers->getPublicPostTypes( true ),
			'orderby'          => 'post_title',
			'suppress_filters' => false
		];

		if ( is_numeric( $body['query'] ) && (int) $body['query'] ) {
			unset( $args['s'] );
			$args['include'] = (int) $body['query'];
		}

		self::$searchTerm = $body['query'];
		add_filter( 'posts_search', [ get_called_class(), 'filterSearch' ], 10, 2 );
		$posts = get_posts( $args );
		remove_filter( 'posts_search', [ get_called_class(), 'filterSearch' ] );

		if ( empty( $posts ) ) {
			return new \WP_REST_Response( [
				'success' => true,
				'objects' => []
			], 200 );
		}

		$parsed = [];
		foreach ( $posts as $post ) {
			// We need to clone the post here so we can get a real permalink for the post even if it is not published already.
			$clonedPost              = clone $post;
			$clonedPost->post_status = 'publish';
			$clonedPost->post_name   = sanitize_title(
				$clonedPost->post_name ? $clonedPost->post_name : $clonedPost->post_title,
				$clonedPost->ID
			);

			$parsed[] = [
				'type'   => $post->post_type,
				'value'  => $post->ID,
				'label'  => $post->post_title,
				'link'   => get_permalink( $clonedPost ),
				'status' => $post->post_status
			];
		}

		return new \WP_REST_Response( [
			'success' => true,
			'objects' => $parsed
		], 200 );
	}

	/**
	 * Filter the where clause when searching for pages or posts.
	 *
	 * @since 1.0.1
	 *
	 * @param  string $search The where clause.
	 * @return string         The where clause.
	 */
	public static function filterSearch( $search ) {
		$column     = aioseo()->db->db->prefix . 'posts.post_name';
		$searchTerm = self::$searchTerm;
		$searchTerm = aioseo()->db->db->prepare( "/* %d = %d */ %%$searchTerm%%", 1, 1 );
		$searchTerm = str_replace( '/* 1 = 1 */ ', '', $searchTerm );

		return preg_replace( '/\)\)\)/', ") OR ($column LIKE '$searchTerm')))", $search );
	}

	/**
	 * Tests the server redirects.
	 *
	 * @since 1.1.4
	 *
	 * @return \WP_REST_Response The response.
	 */
	public static function serverTest() {
		$test          = aioseoRedirects()->server->test->runRedirectsTest();
		$notifications = CommonModels\Notification::getNotifications();

		return new \WP_REST_Response( [
			'success'       => $test,
			'notifications' => $notifications
		], 200 );
	}
}