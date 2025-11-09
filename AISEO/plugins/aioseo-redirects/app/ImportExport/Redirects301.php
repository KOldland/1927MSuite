<?php
namespace AIOSEO\Plugin\Addon\Redirects\ImportExport;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;
use AIOSEO\Plugin\Addon\Redirects\Utils;

class Redirects301 extends Importer {
	/**
	 * A list of plugins to look for to import.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $plugins = [
		[
			'name'     => '301 Redirects',
			'version'  => '2.67',
			'basename' => 'eps-301-redirects/eps-301-redirects.php',
			'slug'     => '301-redirects'
		]
	];

	/**
	 * Import.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function doImport() {
		if ( ! aioseo()->db->tableExists( 'redirects' ) ) {
			return;
		}

		$rules = aioseo()->db->start( 'redirects' )
			->run()
			->result();
		foreach ( $rules as $rule ) {
			if ( ! $this->validateStatusCode( $rule->status ) ) {
				continue;
			}

			if ( empty( $rule->url_to ) ) {
				$rule->url_to = '/';
			}

			if ( is_numeric( $rule->url_to ) && 'post' === $rule->type ) {
				$rule->url_to = str_replace( aioseo()->helpers->getSiteUrl(), '', get_permalink( $rule->url_to ) );
			}

			$fromUrl    = '/' . $rule->url_from;
			$redirect   = Models\Redirect::getRedirectBySourceUrl( $fromUrl );
			$matchedUrl = Utils\Request::getMatchedUrl( $fromUrl );
			$redirect->set( [
				'source_url'       => $fromUrl,
				'source_url_match' => $matchedUrl,
				'target_url'       => Utils\Request::getTargetUrl( $rule->url_to ),
				'type'             => $rule->status,
				'query_param'      => json_decode( aioseoRedirects()->options->redirectDefaults->queryParam )->value,
				'group'            => 'manual',
				'regex'            => false,
				'ignore_slash'     => aioseoRedirects()->options->redirectDefaults->ignoreSlash,
				'ignore_case'      => aioseoRedirects()->options->redirectDefaults->ignoreCase,
				'enabled'          => true
			] );
			$redirect->save();

			// Save hits.
			if ( $rule->count ) {
				$hits = aioseo()->db->start( 'aioseo_redirects_hits' )
					->where( 'redirect_id', $redirect->id )
					->run()
					->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\RedirectsHit' );

				if ( ! $hits->exists() ) {
					$hits->redirect_id = $redirect->id;
				}

				$hits->count = (int) $rule->count;
				$hits->save();
			}
		}
	}
}