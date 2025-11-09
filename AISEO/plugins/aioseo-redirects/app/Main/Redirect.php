<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;
use AIOSEO\Plugin\Addon\Redirects\Utils;

/**
 * Main class to run our redirects.
 *
 * @since 1.0.0
 */
class Redirect {
	/**
	 * Matched redirect.
	 *
	 * @since 1.0.0
	 *
	 * @var boolean
	 */
	private $matched = false;

	/**
	 * The target redirect URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string|null
	 */
	private $redirectUrl = null;

	/**
	 * The target redirect code.
	 *
	 * @since 1.0.0
	 *
	 * @var integer
	 */
	private $redirectCode = 0;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Schedule clearing of logs.
		add_action( 'init', [ $this, 'scheduleClearing' ], 2 );

		if ( is_admin() ) {
			return;
		}

		// Log 404's.
		add_action( 'template_redirect', [ $this, 'templateRedirect' ], 1000 );

		// Log external redirect agents.
		add_filter( 'x_redirect_by', [ $this, 'logExternalRedirect' ], 1000 );

		// If we are using server level redirects, return early.
		if ( aioseoRedirects()->server->valid() ) {
			return;
		}

		// The main redirect loop.
		add_action( 'init', [ $this, 'init' ] );

		// Redirect HTTP headers and server-specific overrides.
		add_filter( 'wp_redirect', [ $this, 'wpRedirect' ], 1, 2 );
	}

	/**
	 * Redirection 'main loop'. Checks the currently requested URL against the database and perform a redirect, if necessary.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() || $this->matched || aioseo()->helpers->isAjaxCronRest() ) {
			return;
		}

		$requestUrl = Utils\Request::getRequestUrl();
		if ( ! $requestUrl || Utils\Request::isProtectedPath( $requestUrl ) ) {
			return;
		}

		$newRequestUrl     = wp_parse_url( $requestUrl );
		$queryString       = ! empty( $newRequestUrl['query'] ) ? $newRequestUrl['query'] : false;
		$requestUrlNoQuery = ! empty( $newRequestUrl['path'] ) ? $newRequestUrl['path'] : '/';

		// Do we have a cache for this specific request URL?
		$redirects = aioseoRedirects()->cache->getRedirects( $requestUrl );
		if ( null === $redirects ) {
			// Get all possible redirects.
			$redirects = aioseo()->db->start( 'aioseo_redirects' )
				->whereRaw( 'source_url_match_hash = "' . Utils\Request::getMatchedUrlHash( $requestUrlNoQuery ) . '"' )
				->where( 'enabled', 1 )
				->orderBy( 'id DESC' )
				->run()
				->result();

			// If we don't have direct redirects, let's check our regex ones.
			// TODO: should we directly regex the database to avoid pulling many regexes and trying all of them?
			if ( empty( $redirects ) ) {
				$redirects = aioseo()->db->start( 'aioseo_redirects' )
					->whereRaw( 'source_url_match_hash = "' . Utils\Request::getRegexHash() . '"' )
					->where( 'enabled', 1 )
					->orderBy( 'id DESC' )
					->run()
					->result();
			}

			// Skip/remove redirects that don't match.
			foreach ( $redirects as $redirectKey => $maybeRedirect ) {
				// Regex first.
				if ( $maybeRedirect->regex ) {
					$matches = $this->matchRegexRedirect( $requestUrl, trim( $maybeRedirect->source_url ), $maybeRedirect->ignore_case );
					if ( empty( $matches ) ) {
						unset( $redirects[ $redirectKey ] );
						continue;
					}

					$redirects[ $redirectKey ]->regexMatches = $matches;
					continue;
				}

				// Prevent redirect loop.
				if ( $this->isRedirectLoop( $maybeRedirect ) ) {
					unset( $redirects[ $redirectKey ] );
					continue;
				}

				// Try matching the source.
				if ( ! $this->matchSourceRedirect( $maybeRedirect, $requestUrlNoQuery ) ) {
					unset( $redirects[ $redirectKey ] );
					continue;
				}

				// Try matching the query string parameters.
				if ( ! $this->matchQueryStringRedirect( $queryString, $maybeRedirect ) ) {
					unset( $redirects[ $redirectKey ] );
				}
			}

			// Cache redirects that actually match the full request URL.
			aioseoRedirects()->cache->setRedirects( $requestUrl, $redirects );
		}

		// Run through the list until one fires.
		foreach ( $redirects as $redirect ) {
			// Custom rules.
			if ( ! $this->matchCustomRules( $redirect, $requestUrl ) ) {
				continue;
			}

			// Format the target URL accounting for relative URLs that may have a trailing slash.
			$targetUrl = Utils\Request::formatTargetUrl( trim( $redirect->target_url ) );

			if ( $redirect->regex ) {
				// Replace the target placeholders.
				if ( isset( $redirect->regexMatches ) ) {
					for ( $i = 1; $i < count( $redirect->regexMatches ); $i ++ ) {
						$targetUrl = @str_replace( '$' . $i, $redirect->regexMatches[ $i ], $targetUrl );
					}
				}

				// Space encode the target after placeholders are replaced.
				$split     = explode( '?', $targetUrl );
				$targetUrl = str_replace( ' ', '%20', $targetUrl );
				if ( count( $split ) === 2 ) {
					$targetUrl = implode( '?', [
						str_replace( ' ', '%20', $split[0] ),
						str_replace( ' ', '+', $split[1] )
					] );
				}
			}

			// Let's add the query string if necessary.
			if ( $queryString && ( 'pass' === $redirect->query_param || 'utm' === $redirect->query_param ) ) {
				parse_str( $queryString, $queryStringArray );

				// If we're only passing utm_ let's remove all other params.
				if ( 'utm' === $redirect->query_param ) {
					foreach ( $queryStringArray as $queryStringArrayKey => $queryStringArrayValue ) {
						if ( ! preg_match( '/^utm_/', $queryStringArrayKey ) ) {
							unset( $queryStringArray[ $queryStringArrayKey ] );
						}
					}
				}

				// Add encoded params to the target URL.
				$targetUrl = add_query_arg( array_map( 'urlencode', $queryStringArray ), $targetUrl );
			}

			$this->matched = $redirect->id;

			// Save a hit.
			$hits = aioseo()->db->start( 'aioseo_redirects_hits' )
				->where( 'redirect_id', $redirect->id )
				->run()
				->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\RedirectsHit' );

			if ( ! $hits->exists() ) {
				$hits->redirect_id = $redirect->id;
				$hits->count       = 0;
			}

			$hits->count += 1;
			$hits->save();

			$this->redirectUrl = $targetUrl;
			// Cast to int before using.
			$this->redirectCode = (int) $redirect->type;

			$this->logRedirect( $this->redirectCode, 'aioseo', $redirect->id );

			// Pass through.
			if ( 0 === $this->redirectCode ) {
				$this->processPassThrough( $targetUrl );

				return;
			}

			// wp_redirect will stop the execution if our redirect code is below 300 or over 399.
			if ( 300 <= $this->redirectCode && 399 >= $this->redirectCode ) {
				if ( wp_redirect( $targetUrl, $this->redirectCode, 'AIOSEO' ) ) {
					global $wp_version;
					if ( version_compare( $wp_version, '5.1', '<' ) ) {
						header( 'X-Redirect-Agent: AIOSEO' );
					}
					die;
				}
			} else {
				status_header( $redirect->type );
				exit( 0 );
			}
		}
	}

	/**
	 * Returns if the query string matches for a redirect.
	 *
	 * @since 1.1.4
	 *
	 * @param  string  $queryString The query string to match against.
	 * @param  object  $redirect    The redirect data.
	 * @return boolean              Query string match.
	 */
	private function matchQueryStringRedirect( $queryString, $redirect ) {
		$sourceQueryString = wp_parse_url( trim( $redirect->source_url ), PHP_URL_QUERY );

		// If no query string is present in the source we can return early.
		if ( empty( $sourceQueryString ) ) {
			return true;
		}

		parse_str( $sourceQueryString, $sourceQueryStringArray );
		parse_str( $queryString, $queryStringArray );

		if ( 'exact' === $redirect->query_param ) {
			// If we can't find this query string inside the source query, we need to skip this redirect.
			foreach ( $queryStringArray as $key => $value ) {
				if ( ! isset( $sourceQueryStringArray[ $key ] ) || $sourceQueryStringArray[ $key ] !== $value ) {
					return false;
				}
			}

			// Same needs to happen the other way around.
			foreach ( $sourceQueryStringArray as $keySource => $valueSource ) {
				if ( ! isset( $queryStringArray[ $keySource ] ) || $queryStringArray[ $keySource ] !== $valueSource ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Is this source->target a redirect loop?
	 *
	 * @since 1.1.4
	 *
	 * @param  object  $redirect The redirect data.
	 * @return boolean           True if it's a loop.
	 */
	private function isRedirectLoop( $redirect ) {
		// Parse final source and target url.
		$parsedSourceUrl = wp_parse_url( Utils\Request::formatSourceUrl( trim( $redirect->source_url ) ) );
		$parsedTargetUrl = wp_parse_url( Utils\Request::formatTargetUrl( trim( $redirect->target_url ) ) );

		// Build comparable urls.
		$sourceUrl = Utils\Request::buildUrl( $parsedSourceUrl, [ 'scheme', 'host', 'path' ] );
		$targetUrl = Utils\Request::buildUrl( $parsedTargetUrl, [ 'scheme', 'host', 'path' ] );
		if ( $sourceUrl === $targetUrl ) {
			return true;
		}

		// If we're ignoring the slash and this is a non-slash to slash redirect it could trigger a redirect loop.
		if (
			$redirect->ignore_slash &&
			untrailingslashit( $sourceUrl ) === untrailingslashit( $targetUrl )
		) {
			return true;
		}

		return false;
	}

	/**
	 * Returns if the path matches for a redirect.
	 *
	 * @since 1.1.4
	 *
	 * @param  object  $redirect          The redirect data.
	 * @param  string  $requestUrlNoQuery The URL to match against.
	 * @return boolean                    Paths match.
	 */
	private function matchSourceRedirect( $redirect, $requestUrlNoQuery ) {
		$sourceUrlPath = wp_parse_url( trim( $redirect->source_url ), PHP_URL_PATH );
		// If we're ignoring the slash we already matched the redirect in que DB query.
		// Here we'll figure out the ignore_slash rule.
		if ( ! $redirect->ignore_slash ) {
			$sourceHasSlash  = Utils\Request::urlHasTrailingSlash( $sourceUrlPath );
			$requestHasSlash = Utils\Request::urlHasTrailingSlash( $requestUrlNoQuery );

			if ( ( $sourceHasSlash && ! $requestHasSlash ) || ( ! $sourceHasSlash && $requestHasSlash ) ) {
				return false;
			}
		}

		// If we're ignoring the case we already matched the redirect in que DB query.
		// Here we'll figure out the ignore_case rule.
		if (
			! $redirect->ignore_case &&
			untrailingslashit( $sourceUrlPath ) !== untrailingslashit( $requestUrlNoQuery )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Returns matches for a regex.
	 *
	 * @since 1.1.4
	 *
	 * @param  string $requestUrl The URL to match against.
	 * @param  string $regex      The regex.
	 * @param  bool   $ignoreCase Ignore the case.
	 * @return array              An array of matches.
	 */
	private function matchRegexRedirect( $requestUrl, $regex, $ignoreCase = true ) {
		$pattern = $this->formatPattern( $regex, $ignoreCase );

		preg_match( $pattern, $requestUrl, $matches );

		return $matches;
	}

	/**
	 * Process a pass through.
	 *
	 * @param  string $to The url to.
	 * @return void
	 */
	private function processPassThrough( $to ) {
		if ( Utils\Request::isUrlExternal( $to ) ) {
			echo wp_remote_fopen( $to ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit();
		}

		$parsedTo = wp_parse_url( $to );

		$_SERVER['REQUEST_URI'] = ! empty( $parsedTo['path'] ) ? $parsedTo['path'] : '';

		if ( ! empty( $parsedTo['query'] ) ) {
			$_SERVER['QUERY_STRING'] = $parsedTo['query'];
			parse_str( $parsedTo['query'], $_GET );
		}
	}

	/**
	 * Match the custom rules.
	 *
	 * @since 1.1.0
	 *
	 * @param  Models\Redirect $redirect          A redirect object model.
	 * @param  string          $compareUrlRequest The url being redirected.
	 * @return boolean                            True if all the custom rules apply.
	 */
	private function matchCustomRules( $redirect, $compareUrlRequest = '' ) {
		$customRules = ! empty( $redirect->custom_rules ) ? json_decode( $redirect->custom_rules ) : [];
		if ( empty( $redirect->custom_rules ) ) {
			return true;
		}

		foreach ( $customRules as $customRule ) {
			// Let's skip rules without values.
			if ( empty( $customRule->value ) ) {
				continue;
			}

			// Small sanitization.
			$customRule->value = is_array( $customRule->value ) ? array_map( 'trim', $customRule->value ) : trim( $customRule->value );

			// This is used for the default matching rule.
			$valueToMatch = null;

			// Define value to match for regex and straight matches.
			switch ( $customRule->type ) {
				case 'agent':
					$valueToMatch = Utils\Request::getUserAgent();
					break;
				case 'referrer':
					$valueToMatch = Utils\Request::getReferrer();
					break;
				case 'cookie':
					$valueToMatch = Utils\Request::getCookie( $customRule->key );
					break;
				case 'header':
					$valueToMatch = Utils\Request::getRequestHeader( $customRule->key );
					break;
				case 'ip':
					$valueToMatch = Utils\Request::getIp();
					break;
				case 'server':
					$valueToMatch = Utils\Request::getServerName();
					break;
				case 'locale':
					$valueToMatch = get_locale();
					break;
			}

			// Custom matches + default match for $valueToMatch.
			switch ( $customRule->type ) {
				case 'login':
					if ( 'loggedin' === $customRule->value && ! is_user_logged_in() ) {
						return false;
					}
					if ( 'loggedout' === $customRule->value && is_user_logged_in() ) {
						return false;
					}
					break;
				case 'role':
					$matchRole = false;
					foreach ( $customRule->value as $role ) {
						if ( current_user_can( $role ) ) {
							$matchRole = true;
							break;
						}
					}
					if ( ! $matchRole ) {
						return false;
					}
					break;
				case 'wp_filter':
					$matchFilter = false;
					foreach ( $customRule->value as $filter ) {
						if ( apply_filters( $filter, false, $compareUrlRequest, $redirect ) ) {
							$matchFilter = true;
							break;
						}
					}
					if ( ! $matchFilter ) {
						return false;
					}
					break;
				case 'agent':
					$matchAgent = false;
					// Custom values for matching.
					foreach ( $customRule->value as $agentItem ) {
						$regex = ! empty( $customRule->regex );
						switch ( $agentItem ) {
							case 'mobile':
								$agentItem = 'iPad|iPod|iPhone|Android|BlackBerry|SymbianOS|SCH-Md+|Opera Mini|Windows CE|Nokia|SonyEricsson|webOS|PalmOS';
								$regex     = true;
								break;
							case 'feeds':
								$agentItem = 'Bloglines|feed|rss';
								$regex     = true;
								break;
							case 'libraries':
								$agentItem = 'cURL|Java|libwww-perl|PHP|urllib';
								$regex     = true;
								break;
						}

						$matchAgent = $agentItem === $customRule->value;
						if ( $regex ) {
							$matchAgent = $this->matchRegexRule( $agentItem, $valueToMatch );
						}

						if ( $matchAgent ) {
							break;
						}
					}

					if ( ! $matchAgent ) {
						return false;
					}
					break;
				default:
					$match = is_array( $customRule->value ) ? in_array( $valueToMatch, $customRule->value, true ) : $valueToMatch === $customRule->value;
					if ( ! empty( $customRule->regex ) ) {
						$match = $this->matchRegexRule( $customRule->value, $valueToMatch );
					}

					if ( ! $match ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Helper to match a regex rule against a value.
	 *
	 * @since 1.1.0
	 *
	 * @param  array|string $regex The regex.
	 * @param  string       $value The value to match against.
	 * @return false|int           The match result.
	 */
	private function matchRegexRule( $regex, $value ) {
		if ( ! is_array( $regex ) ) {
			$regex = [ $regex ];
		}
		// Try each regex and return on the first one that is true.
		foreach ( $regex as $ex ) {
			if ( preg_match( sprintf( '/%s/', $ex ), $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Schedule clearing of the logs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function scheduleClearing() {
		$actions = [];
		$preActions = [
			'log404'    => [
				'action' => 'aioseo_redirects_clear_404_logs',
				'method' => 'clear404Logs'
			],
			'redirects' => [
				'action' => 'aioseo_redirects_clear_logs',
				'method' => 'clearRedirectLogs'
			]
		];

		foreach ( $preActions as $key => $data ) {
			$optionLength = json_decode( aioseoRedirects()->options->logs->{ $key }->length )->value;
			if (
				aioseoRedirects()->options->logs->{ $key }->enabled &&
				'forever' !== $optionLength
			) {
				$length = WEEK_IN_SECONDS;
				if ( 'hour' === $optionLength ) {
					$length = HOUR_IN_SECONDS;
				} elseif ( 'day' === $optionLength ) {
					$length = DAY_IN_SECONDS;
				}
				$actions[ $data['action'] ] = [
					'length' => $length,
					'method' => $data['method']
				];

				continue;
			}

			aioseo()->helpers->unscheduleAction( $data['action'] );
		}

		foreach ( $actions as $action => $data ) {
			try {
				// Register the action handler.
				add_action( $action, [ $this, $data['method'] ] );

				if ( ! as_next_scheduled_action( $action ) ) {
					as_schedule_recurring_action( time() + 60, $data['length'], $action, [], 'aioseo' );
				}
			} catch ( \Exception $e ) {
				// Do nothing.
			}
		}
	}

	/**
	 * Clears the 404 logs.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear404Logs() {
		$optionLength = json_decode( aioseoRedirects()->options->logs->log404->length )->value;
		if ( 'forever' === $optionLength ) {
			return;
		}

		$date = date( 'Y-m-d H:i:s', strtotime( '-1 ' . $optionLength ) );
		aioseo()->db
			->delete( 'aioseo_redirects_404_logs' )
			->where( 'created <', $date )
			->run();
	}

	/**
	 * Clears the redirect logs.
	 *
	 * @return void
	 */
	public function clearRedirectLogs() {
		$optionLength = json_decode( aioseoRedirects()->options->logs->redirects->length )->value;
		if ( 'forever' === $optionLength ) {
			return;
		}

		$date = date( 'Y-m-d H:i:s', strtotime( '-1 ' . $optionLength ) );
		aioseo()->db
			->delete( 'aioseo_redirects_logs' )
			->where( 'created <', $date )
			->run();
	}

	/**
	 * Perform any pre-redirect processing, such as logging and header fixing.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $url    The target URL.
	 * @param  integer $status HTTP status.
	 * @return string          The target URL.
	 */
	public function wpRedirect( $url, $status = 302 ) {
		// These are set early on so when other filters run, we have access to it.
		$this->redirectUrl  = $url;
		$this->redirectCode = $status;

		global $is_IIS;

		if ( $is_IIS ) {
			header( "Refresh: 0;url=$url" );
		}

		if ( 301 === $status && php_sapi_name() === 'cgi-fcgi' ) {
			$serversToCheck = [ 'lighttpd', 'nginx' ];

			foreach ( $serversToCheck as $name ) {
				if (
					isset( $_SERVER['SERVER_SOFTWARE'] ) &&
					false !== stripos( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ), $name ) // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized
				) {
					status_header( $status );
					header( "Location: $url" );
					exit( 0 );
				}
			}
		}

		if ( 307 === intval( $status, 10 ) ) {
			status_header( $status );
			nocache_headers();

			return $url;
		}

		if ( ! aioseoRedirects()->options->cache->httpHeader->enabled ) {
			// No cache - just use WP function.
			nocache_headers();
		} else {
			if (
				! headers_sent() &&
				'forever' !== aioseoRedirects()->options->cache->httpHeader->length &&
				301 === intval( $status, 10 )
			) {
				// Custom cache.
				$cacheTime = 1;
				switch ( aioseoRedirects()->options->cache->httpHeader->length ) {
					case 'day':
						$cacheTime = 24;
					case 'week':
						$cacheTime = 168;
					case 'hour':
					default:
						break;
				}
				header( 'Expires: ' . gmdate( 'D, d M Y H:i:s T', time() + $cacheTime * 60 * 60 ) );
				header( 'Cache-Control: max-age=' . $cacheTime * 60 * 60 );
			}
		}

		status_header( $status );

		return $url;
	}

	/**
	 * Logs a redirect.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $agent Redirect agent.
	 * @return string        Redirect agent.
	 */
	public function logExternalRedirect( $agent ) {
		// Have we already redirected?
		if ( $this->matched || 'aioseo' === $agent ) {
			return $agent;
		}

		if (
			! aioseoRedirects()->options->logs->external ||
			! aioseoRedirects()->options->logs->redirects->enabled
		) {
			return $agent;
		}

		$redirectBy = $agent ? strtolower( substr( $agent, 0, 50 ) ) : 'wordpress'; // phpcs:ignore WordPress.WP.CapitalPDangit.Misspelled
		$this->logRedirect( $this->redirectCode, $redirectBy );

		return $agent;
	}

	/**
	 * Intercept the request and parse if needed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function templateRedirect() {
		global $wp;

		// Don't log the testRedirect as a 404.
		$testRedirect = aioseoRedirects()->server->test->getTestRedirect();
		if ( $testRedirect === $wp->request ) {
			return;
		}

		$this->log404();
	}

	/**
	 * WordPress 'template_redirect' hook. Used to check for 404s.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function log404() {
		if ( ! is_404() || $this->matched ) {
			return;
		}

		if ( ! aioseoRedirects()->options->logs->log404->enabled ) {
			return;
		}

		$this->logRedirect( 404 );
	}

	/**
	 * Logs the redirects.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer $status     The HTTP status code.
	 * @param  string  $redirectBy The redirect agent.
	 * @param  integer $redirectId The redirect ID.
	 * @return void
	 */
	private function logRedirect( $status, $redirectBy = null, $redirectId = null ) {
		$ip = null;
		if ( aioseoRedirects()->options->logs->ipAddress->enabled ) {
			$ip      = Utils\Request::getIp();
			$ipLevel = json_decode( aioseoRedirects()->options->logs->ipAddress->level )->value;
			if ( 'full' !== $ipLevel ) {
				$ip = Utils\Request::maskIp( $ip );
			}
		}

		$data = [
			'url'            => Utils\Request::getRequestUrl(),
			'domain'         => Utils\Request::getServer(),
			'sent_to'        => $this->redirectUrl,
			'agent'          => Utils\Request::getUserAgent(),
			'referrer'       => Utils\Request::getReferrer(),
			'http_code'      => $status,
			'request_method' => Utils\Request::getRequestMethod(),
			'ip'             => $ip,
			'request_data'   => [
				'source' => array_values( $this->getRedirectSource() )
			]
		];

		if ( $redirectId ) {
			$data['redirect_id'] = $redirectId;
		}

		if ( $redirectBy ) {
			$data['redirect_by'] = $redirectBy;
		}

		if ( aioseoRedirects()->options->logs->httpHeader ) {
			$data['request_data']['headers'] = Utils\Request::getRequestHeaders();
		}

		$log = 404 === $status ? new Models\Redirects404Log() : new Models\RedirectsLog();
		$log->set( $data );
		$log->save();
	}

	/**
	 * Get a 'source' for a redirect by digging through the backtrace.
	 *
	 * @since 1.0.0
	 *
	 * @return string The redirect source.
	 */
	private function getRedirectSource() {
		$ignore = [
			'WP_Hook',
			'template-loader.php',
			'wp-blog-header.php',
		];

		// phpcs:ignore
		$source = wp_debug_backtrace_summary( null, 5, false );

		return array_filter( $source, function( $item ) use ( $ignore ) {
			foreach ( $ignore as $ignore_item ) {
				if ( strpos( $item, $ignore_item ) !== false ) {
					return false;
				}
			}

			return true;
		} );
	}

	/**
	 * Formats the regex pattern that we are using.
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $pattern    The pattern to format.
	 * @param  boolean $ignoreCase Whether to ignore case.
	 * @return string              The formatted pattern.
	 */
	private function formatPattern( $pattern, $ignoreCase ) {
		$pattern = str_replace( '.*', '*', $pattern );
		$pattern = preg_replace_callback( '/[\\\\^$.[\\]|()?*+{}\\-\\/]/', function ( $matches ) {
			switch ( $matches[0] ) {
				case '*':
					return '.*';
				case '/':
					return '\/';
				default:
					return $matches[0];
			}
		}, $pattern );

		$pattern = str_replace( '\/^', '^', $pattern );
		$pattern = str_replace( '\\\'', '\'', $pattern );

		if ( preg_match( '/^\^/', $pattern ) && ! preg_match( '/^\^\\\\\//', $pattern ) ) {
			$pattern = str_replace( '^', '^\/', $pattern );
		}

		$pattern = "/$pattern/";
		if ( $ignoreCase ) {
			$pattern .= 'i';
		};

		return $pattern;
	}
}