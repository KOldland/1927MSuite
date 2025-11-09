<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;
use AIOSEO\Plugin\Addon\Redirects\Utils;

/**
 * Monitors changes to posts.
 *
 * @since 1.0.0
 */
class Monitor {
	/**
	 * Holds posts that have been updated.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $updatedPosts = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// We can't monitor changes without permalinks enabled.
		if ( ! get_option( 'permalink_structure' ) ) {
			return;
		}

		$all      = aioseoRedirects()->options->monitor->postTypes->all;
		$included = aioseoRedirects()->options->monitor->postTypes->included;
		if ( ! $all && empty( $included ) ) {
			return;
		}

		add_action( 'pre_post_update', [ $this, 'prePostUpdate' ], 10, 2 );
		add_action( 'post_updated', [ $this, 'postUpdated' ], 11, 3 );

		if ( aioseoRedirects()->options->monitor->trash ) {
			add_action( 'wp_trash_post', [ $this, 'postTrashed' ] );
		}
	}

	/**
	 * Remember the previous post permalink.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer $postId The post ID.
	 * @return void
	 */
	public function prePostUpdate( $postId ) {
		$this->updatedPosts[ $postId ] = get_permalink( $postId );
	}

	/**
	 * Called when a post has been updated - check if the slug has changed.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer  $postId     The post ID.
	 * @param  \WP_Post $post       The post object.
	 * @param  \WP_Post $postBefore The post object before changes were made.
	 * @return void
	 */
	public function postUpdated( $postId, $post, $postBefore ) {
		if ( isset( $this->updatedPosts[ $postId ] ) && $this->canMonitorPost( $post, $postBefore ) ) {
			$this->checkForModifiedSlug( $postId, $this->updatedPosts[ $postId ] );
		}
	}

	/**
	 * Checks if this is a post we can monitor.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_Post $post       The post object.
	 * @param  \WP_Post $postBefore The post object before changes were made.
	 * @return boolean              True if we can monitor this post.
	 */
	private function canMonitorPost( $post, $postBefore ) {
		// Check that this is for the expected post.
		if ( ! isset( $post->ID ) || ! isset( $this->updatedPosts[ $post->ID ] ) ) {
			return false;
		}

		// Don't do anything if we're not published.
		if ( 'publish' !== $post->post_status || 'publish' !== $postBefore->post_status ) {
			return false;
		}

		$type = get_post_type( $post->ID );
		if (
			! in_array( $type, aioseoRedirects()->options->monitor->postTypes->included, true ) &&
			! aioseoRedirects()->options->monitor->postTypes->all
		) {
			return false;
		}

		return true;
	}

	/**
	 * Checks for a modified slug on a monitored post.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer  $postId The post ID.
	 * @param  \WP_Post $before The post object before changes were made.
	 * @return void
	 */
	private function checkForModifiedSlug( $postId, $before ) {
		$after  = wp_parse_url( get_permalink( $postId ), PHP_URL_PATH );
		$before = wp_parse_url( esc_url( $before ), PHP_URL_PATH );

		if ( $this->hasPermalinkChanged( $before, $after ) ) {
			// Disable all redirects that match the new URL.
			aioseo()->db
				->update( 'aioseo_redirects' )
				->where( 'source_url_match_hash', sha1( Utils\Request::getMatchedUrl( $after ) ) )
				->set( 'enabled', 0 )
				->run();

			$redirect   = Models\Redirect::getRedirectBySourceUrl( $before );
			$matchedUrl = Utils\Request::getMatchedUrl( $before );
			$redirect->set( [
				'source_url'       => $before,
				'source_url_match' => $matchedUrl,
				'target_url'       => Utils\Request::getTargetUrl( $after ),
				'type'             => 301,
				'query_param'      => json_decode( aioseoRedirects()->options->redirectDefaults->queryParam )->value,
				'group'            => 'modified',
				'regex'            => false,
				'ignore_slash'     => aioseoRedirects()->options->redirectDefaults->ignoreSlash,
				'ignore_case'      => aioseoRedirects()->options->redirectDefaults->ignoreCase,
				'enabled'          => true
			] );
			$redirect->save();

			return true;
		}

		return false;
	}

	/**
	 * Create a redirect if we are monitoring the trash.
	 *
	 * @since 1.0.0
	 *
	 * @param  integer $postId The post ID.
	 * @return void
	 */
	public function postTrashed( $postId ) {
		$url = wp_parse_url( get_permalink( $postId ), PHP_URL_PATH );
		if ( '/' === $url ) {
			return;
		}

		$redirect   = Models\Redirect::getRedirectBySourceUrl( $url );
		$matchedUrl = Utils\Request::getMatchedUrl( $url );
		$redirect->set( [
			'source_url'       => $url,
			'source_url_match' => $matchedUrl,
			'target_url'       => '/',
			'type'             => 301,
			'query_param'      => json_decode( aioseoRedirects()->options->redirectDefaults->queryParam )->value,
			'group'            => 'modified',
			'regex'            => false,
			'ignore_slash'     => aioseoRedirects()->options->redirectDefaults->ignoreSlash,
			'ignore_case'      => aioseoRedirects()->options->redirectDefaults->ignoreCase,
			'enabled'          => false
		] );
		$redirect->save();
	}

	/**
	 * Changed if permalinks are different and the before wasn't
	 * the site url (we don't want to redirect the site URL).
	 *
	 * @since 1.0.0
	 *
	 * @param  string  $before The URL before the change.
	 * @param  string  $after  The URL after the change.
	 * @return boolean         True if the permalink has changed.
	 */
	private function hasPermalinkChanged( $before, $after ) {
		// Check it's not redirecting from the root
		if ( $this->getSitePath() === $before || '/' === $before ) {
			return false;
		}

		// Are the URLs the same?
		return ( $before !== $after );
	}

	/**
	 * Retrieve the site path.
	 *
	 * @since 1.0.0
	 *
	 * @return string The site path.
	 */
	private function getSitePath() {
		$path = wp_parse_url( get_site_url(), PHP_URL_PATH );

		return $path ? rtrim( $path, '/' ) . '/' : '/';
	}
}