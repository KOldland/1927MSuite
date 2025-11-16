<?php
namespace AIOSEO\Plugin\Addon\Redirects\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;

/**
 * Contains helper functions
 *
 * @since 1.0.0
 */
class Helpers {
	/**
	 * Gets the data for vue.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $page The current page.
	 * @return array        An array of data.
	 */
	public function getVueData( $data = [], $page = null ) {
		if ( 'redirects' === $page ) {
			return $this->getRedirectsPageData( $data );
		}

		if ( 'tools' === $page ) {
			return $this->getToolsPageData( $data );
		}

		return $data;
	}

	/**
	 * Get redirects page data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getRedirectsPageData( $data ) {
		// Get the total number of results.
		$total     = aioseo()->db->start( 'aioseo_redirects' )->count();
		$total404  = aioseo()->db->start( 'aioseo_redirects_404_logs' )->groupBy( 'url' )->count();
		$totalLogs = aioseo()->db->start( 'aioseo_redirects_logs' )->groupBy( 'url' )->count();

		// Inject our vue data into this page.
		$wpUploadDir       = wp_upload_dir();
		$data['redirects'] = [
			'options'   => aioseoRedirects()->options->all(),
			'rows'      => array_values(
				aioseo()->db->start( 'aioseo_redirects' )
					->orderBy( 'id DESC' )
					->limit( 20, 0 )
					->run()
					->models( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\Redirect', null, true )
			),
			'logs404'   => Models\Redirects404Log::getFiltered(),
			'logs'      => Models\RedirectsLog::getFiltered(),
			'filters'   => [
				[
					'slug'   => 'all',
					'name'   => __( 'All', 'aioseo-redirects' ),
					'count'  => $total,
					'active' => true
				],
				[
					'slug'   => 'enabled',
					'name'   => __( 'Enabled', 'aioseo-redirects' ),
					'count'  => aioseo()->db->start( 'aioseo_redirects' )->where( 'enabled', 1 )->count(),
					'active' => false
				],
				[
					'slug'   => 'disabled',
					'name'   => __( 'Disabled', 'aioseo-redirects' ),
					'count'  => aioseo()->db->start( 'aioseo_redirects' )->where( 'enabled', 0 )->count(),
					'active' => false
				]
			],
			'totals'    => [
				'total404' => [
					'total' => $total404,
					'pages' => ceil( $total404 / 20 ),
					'page'  => 1
				],
				'logs'     => [
					'total' => $totalLogs,
					'pages' => ceil( $totalLogs / 20 ),
					'page'  => 1
				],
				'main'     => [
					'total' => $total,
					'pages' => ceil( $total / 20 ),
					'page'  => 1
				]
			],
			'path'      => $wpUploadDir['basedir'] . '/aioseo/redirects/.redirects',
			'importers' => aioseoRedirects()->importExport->plugins(),
			'server'    => [
				'redirectTest' => [
					'testing' => false,
					'failed'  => aioseoRedirects()->cache->get( 'server-redirects-failed' )
				]
			]
		];

		$data['data']['server'] = [
			'apache' => aioseo()->helpers->isApache(),
			'nginx'  => aioseo()->helpers->isNginx()
		];

		return $data;
	}

	/**
	 * Get tools page data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data The data array.
	 * @return array       The modified data array.
	 */
	private function getToolsPageData( $data ) {
		$data['data']['logSizes']['logs404']      = aioseo()->helpers->convertFileSize( aioseo()->db->getTableSize( 'aioseo_redirects_404_logs' ) );
		$data['data']['logSizes']['redirectLogs'] = aioseo()->helpers->convertFileSize( aioseo()->db->getTableSize( 'aioseo_redirects_logs' ) );

		return $data;
	}
}