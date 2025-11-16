<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main\Server;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;

/**
 * Main class to work with server redirects.
 *
 * @since 1.0.0
 */
class Nginx extends Server {
	/**
	 * %1$s is the origin
	 * %2$s is the target
	 * %3$s is the redirect type
	 * %4$s is the optional x-redirect-by filter.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $urlFormat = 'location %1$s { %4$s return %3$s %2$s; }';

	/**
	 * %1$s is the origin
	 * %2$s is the target
	 * %3$s is the redirect type
	 * %4$s is the optional x-redirect-by filter.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $regexFormat = 'location ~ %1$s { %4$s return %3$s %2$s; }';

	/**
	 * The relocation format.
	 *
	 * %1$s is the target
	 * %2$s are protected paths
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $relocationFormat = 'if ($request_uri !~* (%2$s)) { return 301 %1$s$request_uri; }';

	/**
	 * The format for aliases.
	 *
	 * %1$s is the origin host
	 * %2$s is the target host
	 *
	 * @since 1.1.0
	 *
	 * @var string
	 */
	protected $aliasFormat = 'if ($host ~* ^%1$s) {  return 301 %2$s$request_uri; }';

	/**
	 * Formats a redirect for use in the export.
	 *
	 * @param  object $redirect The redirect to format.
	 * @return string           The formatted redirect.
	 */
	public function format( $redirect ) {
		return sprintf(
			$this->getFormat( $redirect->regex ),
			$redirect->source_url,
			$redirect->target_url,
			$redirect->type,
			$this->addRedirectHeader()
		);
	}

	/**
	 * Formats a relocation.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $relocationAddress The redirect to format.
	 * @return string                    The formatted redirect.
	 */
	public function formatRelocation( $relocationAddress, $protectedPaths = '' ) {
		return sprintf(
			$this->relocationFormat,
			$relocationAddress,
			$protectedPaths
		);
	}

	/**
	 * Formats an alias.
	 *
	 * @since 1.1.0
	 *
	 * @param  string $alias The alias to format.
	 * @return string        The formatted alias.
	 */
	public function formatAlias( $alias ) {
		return sprintf(
			$this->aliasFormat,
			preg_quote( $alias ),
			get_home_url()
		);
	}

	/**
	 * Formats a canonical redirect.
	 *
	 * @since 1.1.0
	 *
	 * @return void|string The formatted redirect.
	 */
	public function formatCanonical( $url, $https = false, $preferredDomain = '' ) {
		// Do not redirect canonical unless we are forcing https or a preferredDomain.
		if ( ! $https && ! $preferredDomain ) {
			return;
		}

		$conditions = 'set $aioseoCanonical 0;' . PHP_EOL;
		if ( $https ) {
			$conditions .= ' if ($scheme = http) { set $aioseoCanonical 1; } ' . PHP_EOL;
		}
		switch ( $preferredDomain ) {
			case 'add-www':
				$conditions .= 'if ($host !~* ^www) { set $aioseoCanonical 1; }' . PHP_EOL;
				break;
			case 'remove-www':
				$conditions .= 'if ($host ~* ^www) { set $aioseoCanonical 1; }' . PHP_EOL;
		}

		$canonical = 'if ($aioseoCanonical) { return 301 %1$s$request_uri; }' . PHP_EOL;

		return $conditions . sprintf( $canonical, $url );
	}

	/**
	 * Adds an X-Redirect-By header if allowed by the filter.
	 *
	 * @since 1.0.0
	 *
	 * @return string The redirect header.
	 */
	private function addRedirectHeader() {
		if ( apply_filters( 'aioseo_redirects_nginx_add_redirect_header', true ) ) {
			return 'add_header X-Redirect-By "AIOSEO";';
		}

		return '';
	}

	/**
	 * Get the test redirect.
	 *
	 * @since 1.1.4
	 *
	 * @return string The test redirect.
	 */
	protected function getAioseoRedirectTest() {
		return 'location /' . $this->test->getTestRedirect() . ' { add_header X-Redirect-By "AIOSEO Server Test"; return 301 /' . $this->test->getTestInterceptedRedirect() . '; }' . PHP_EOL;
	}
}