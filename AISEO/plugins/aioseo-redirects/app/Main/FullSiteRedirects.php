<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Utils;

/**
 * Main class to run our full site redirects.
 *
 * @since 1.1.0
 */
class FullSiteRedirects {
	/**
	 * Class constructor.
	 *
	 * @since 1.1.0
	 */
	public function __construct() {
		// HTTP headers for the whole site.
		add_filter( 'wp_headers', [ $this, 'siteHttpHeaders' ], 50 );

		// HTTP headers for redirections.
		add_filter( 'wp_redirect', [ $this, 'redirectHttpHeaders' ], 0 );

		// If we are using server level redirects, return early.
		if ( aioseoRedirects()->server->valid() ) {
			return;
		}

		add_action( 'init', [ $this, 'relocate' ], 0 );
		add_action( 'init', [ $this, 'aliases' ], 2 );
		add_action( 'init', [ $this, 'canonical' ], 4 );
	}

	/**
	 * Runs if relocation is enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function relocate() {
		$newDomain = $this->shouldRelocate();
		if ( ! $newDomain || ! $this->shouldRun() ) {
			return;
		}

		wp_redirect( $this->makeUrl( [ $newDomain, Utils\Request::getRequestUrl() ] ), 301, 'AIOSEO' );
		exit;
	}

	/**
	 * Returns the relocation address if the website should be relocated.
	 *
	 * @since 1.1.0
	 *
	 * @return false|string The relocation address.
	 */
	public function shouldRelocate() {
		$newDomain = $this->getRelocateAddress();
		if ( ! aioseoRedirects()->options->fullSite->relocate->enabled || empty( $newDomain ) ) {
			return false;
		}

		return $newDomain;
	}

	/**
	 * Returns the relocation address validating if it's an actual URL.
	 *
	 * @since 1.1.0
	 *
	 * @return void|string The relocation address.
	 */
	public function getRelocateAddress() {
		$newDomain = aioseoRedirects()->options->fullSite->relocate->newDomain;

		if ( empty( $newDomain ) || ! aioseo()->helpers->isUrl( $newDomain ) ) {
			return;
		}

		return $newDomain;
	}

	/**
	 * Runs an alias redirect if it matches the server's current host.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function aliases() {
		$aliases = $this->getAliases();

		if ( empty( $aliases ) ) {
			return;
		}

		$aliases = array_map( 'untrailingslashit', $aliases );

		if ( in_array( $this->getCurrentHost(), $aliases, true ) ) {
			wp_redirect( $this->makeUrl( [ get_home_url(), Utils\Request::getRequestUrl() ] ), 301, 'AIOSEO' );
			exit;
		}
	}

	/**
	 * Returns if the canonical redirect can happen.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean|string The canonical url if the redirect can happen.
	 */
	public function shouldCanonical() {
		if ( ! aioseoRedirects()->options->fullSite->canonical->enabled ) {
			return false;
		}

		$canonicalUrl = $this->getCanonicalHostUrl();

		// If the canonical url is NOT configured in WP it'll create a redirect loop and we need to prevent that.
		if ( untrailingslashit( get_home_url() ) === $canonicalUrl ) {
			return $canonicalUrl;
		}

		return false;
	}

	/**
	 * Apply canonical settings if they are enabled.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public function canonical() {
		$canonicalUrl = $this->shouldCanonical();

		// If the canonical URL is different from the current URL then we redirect.
		if ( $canonicalUrl && $canonicalUrl !== $this->getCurrentHostUrl() ) {
			wp_redirect( $this->makeUrl( [ $canonicalUrl, Utils\Request::getRequestUrl() ] ), 301, 'AIOSEO' );
			exit;
		}
	}

	/**
	 * Add configured HTTP Headers to all pages.
	 *
	 * @since 1.1.0
	 *
	 * @param  array $wpHeaders The WP headers.
	 * @return array            Our added headers.
	 */
	public function siteHttpHeaders( $wpHeaders ) {
		if ( ! $this->shouldRun() ) {
			return;
		}

		$httpHeaders = $this->getHttpHeaders( [ 'site' ] );

		foreach ( $httpHeaders as $header ) {
			$wpHeaders = array_merge( $wpHeaders, $this->makeHttpHeader( $header ) );
		}

		return $wpHeaders;
	}

	/**
	 * Add configured HTTP Headers to redirects.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $url The target URL.
	 * @return string      The target URL.
	 */
	public function redirectHttpHeaders( $url ) {
		$headers = $this->getHttpHeaders( [ 'site', 'redirect' ] );
		if ( empty( $headers ) ) {
			return $url;
		}

		foreach ( $headers as $header ) {
			$header = $this->makeHttpHeader( $header );
			header( sprintf( '%s: %s', key( $header ), current( $header ) ) );
		}

		return $url;
	}

	/**
	 * Return a header string from a header object.
	 *
	 * @since 1.1.0
	 *
	 * @param  string|object $httpHeader The header object.
	 * @return array                     A http header array.
	 */
	public function makeHttpHeader( $httpHeader ) {
		$httpHeader  = is_string( $httpHeader ) ? json_decode( $httpHeader ) : $httpHeader;
		$headerValue = ( is_array( $httpHeader->value ) ? implode( ',', $httpHeader->value ) : $httpHeader->value );

		// Custom header.
		if ( 'custom' === $httpHeader->header ) {
			$httpHeader->header = $httpHeader->customHeader;
		}

		// Custom value.
		if ( ! empty( $httpHeader->customValue ) ) {
			$headerValue = preg_replace( '/\[[a-zA-Z0-9_-]+\]/', $httpHeader->customValue, $headerValue );
		}

		return [ trim( $httpHeader->header ) => trim( $headerValue ) ];
	}

	/**
	 * Get the configured http headers optionally filtered by type.
	 *
	 * @since 1.1.0
	 *
	 * @param  array $types Types to filter (site, redirect).
	 * @return array        HTTP header array.
	 */
	public function getHttpHeaders( $types = [] ) {
		$headers = aioseoRedirects()->options->fullSite->httpHeaders;
		if ( empty( $headers ) ) {
			return [];
		}

		foreach ( $headers as $key => &$httpHeader ) {
			$httpHeader = json_decode( $httpHeader );

			// Let's make sure the headers are usable or it may crash Apache.
			if (
				! $httpHeader ||
				empty( $httpHeader->header ) ||
				empty( $httpHeader->value ) ||
				(
					! empty( $types ) &&
					! empty( $httpHeader->location ) &&
					! in_array( $httpHeader->location, $types, true )
				)
			) {
				unset( $headers[ $key ] );
			}
		}

		return $headers;
	}

	/**
	 * Returns our aliases.
	 *
	 * @since 1.1.0
	 *
	 * @return array An array of aliases.
	 */
	public function getAliases() {
		$aliases = aioseoRedirects()->options->fullSite->aliases;
		foreach ( $aliases as &$alias ) {
			$alias = json_decode( $alias );
			if ( ! empty( $alias->aliasedDomain ) ) {
				// Replace or add double forward slashes so we can safely extract the host.
				$alias->aliasedDomain = preg_replace( '/^http(|s):\/\/|^(?!\/\/)/i', '//', trim( $alias->aliasedDomain ) );
			}
			$alias = wp_parse_url( $alias->aliasedDomain, PHP_URL_HOST );
		}

		return array_filter( $aliases );
	}

	/**
	 * Gets our canonical configured host url.
	 *
	 * @since 1.1.0
	 *
	 * @return string The canonical host url.
	 */
	public function getCanonicalHostUrl() {
		// Start canonical url with the current url scheme.
		$canonicalUrl = $this->getCurrentScheme() . '://';

		if ( aioseoRedirects()->options->fullSite->canonical->httpToHttps ) {
			$canonicalUrl = 'https://';
		}

		$canonicalHost = $this->getCurrentHost();

		$preferredDomain = aioseoRedirects()->options->fullSite->canonical->preferredDomain;
		if ( 'add-www' === $preferredDomain && ! preg_match( '/^www\./', $canonicalHost ) ) {
			$canonicalHost = 'www.' . $canonicalHost;
		} elseif ( 'remove-www' === $preferredDomain && preg_match( '/^www\./', $canonicalHost ) ) {
			$canonicalHost = preg_replace( '/^www\./', '', $canonicalHost );
		}

		$canonicalUrl .= $canonicalHost;

		return untrailingslashit( $canonicalUrl );
	}

	/**
	 * Returns the current url being accessed.
	 *
	 * @since 1.1.0
	 *
	 * @return string The current URL.
	 */
	private function getCurrentHostUrl() {
		return untrailingslashit( $this->getCurrentScheme() . '://' . $this->getCurrentHost() );
	}

	/**
	 * Returns the current scheme.
	 *
	 * @since 1.1.0
	 *
	 * @return string The scheme.
	 */
	private function getCurrentScheme() {
		return is_ssl() ? 'https' : 'http';
	}

	/**
	 * Returns the current host.
	 *
	 * @since 1.1.0
	 *
	 * @return string The host address.
	 */
	private function getCurrentHost() {
		return untrailingslashit( Utils\Request::getRequestServerName() );
	}

	/**
	 * Returns if we should run a redirection.
	 *
	 * @since 1.1.0
	 *
	 * @return bool Should run.
	 */
	private function shouldRun() {
		if ( is_admin() || wp_doing_ajax() || aioseo()->helpers->isRestApiRequest() || Utils\Request::isProtectedPath() ) {
			return false;
		}

		return true;
	}

	/**
	 * Takes an array of url parts and puts them together with proper slashes.
	 *
	 * @since 1.1.0
	 *
	 * @param  array  $parts URL parts in order.
	 * @return string        A built URL.
	 */
	private function makeUrl( $parts ) {
		if ( ! is_array( $parts ) ) {
			$parts = [ $parts ];
		}

		$url = implode( '', array_map( 'trailingslashit', $parts ) );

		// Fix duplicated forward slashes.
		return preg_replace( '/(?<!http:|https:)\/{2,}/', '/', $url );
	}
}