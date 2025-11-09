<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main;

use AIOSEO\Plugin\Addon\Redirects\Utils;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main class for redirects cache.
 *
 * @since 1.1.4
 */
class Cache extends \AIOSEO\Plugin\Common\Utils\Cache {
	/**
	 * The redirect addon cache prefix.
	 *
	 * @since 1.1.4
	 *
	 * @var string
	 */
	protected $prefix = 'aioseo_redirects_';

	/**
	 * The redirect URL cache prefix.
	 *
	 * @since 1.1.4
	 *
	 * @var string
	 */
	private $redirectUrlPrefix = 'url_';

	/**
	 * Gets redirects from cache for a URL path.
	 *
	 * @since 1.1.4
	 *
	 * @param  string $path The path.
	 * @return array        An array of redirect results.
	 */
	public function getRedirects( $path ) {
		return $this->get( $this->getUrlCacheName( $path ) );
	}

	/**
	 * Adds redirects to a URL path's cache.
	 *
	 * @since 1.1.4
	 *
	 * @param  string $path The path.
	 * @param  string $data Data to cache.
	 * @return void
	 */
	public function setRedirects( $path, $data ) {
		$this->update( $this->getUrlCacheName( $path ), $data, WEEK_IN_SECONDS );
	}

	/**
	 * Deletes all redirects from cache.
	 *
	 * @since 1.1.4
	 *
	 * @return void
	 */
	public function clearRedirects() {
		$this->clearPrefix( $this->redirectUrlPrefix );
	}

	/**
	 * Adds redirects to a URL path's cache.
	 *
	 * @since 1.1.4
	 *
	 * @param  string $path The path.
	 * @return string       The cache name.
	 */
	public function getUrlCacheName( $path ) {
		return $this->redirectUrlPrefix . Utils\Request::getUrlHash( $path );
	}
}