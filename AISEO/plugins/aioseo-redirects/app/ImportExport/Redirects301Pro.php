<?php
namespace AIOSEO\Plugin\Addon\Redirects\ImportExport;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\Plugin\Addon\Redirects\Models;
use AIOSEO\Plugin\Addon\Redirects\Utils;

class Redirects301Pro extends Importer {
	/**
	 * A list of plugins to look for to import.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $plugins = [
		[
			'name'     => '301 Redirects Pro',
			'version'  => '5.69',
			'basename' => '301-redirects/301-redirects.php',
			'slug'     => '301-redirects-pro'
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
		if ( ! aioseo()->db->tableExists( 'wf301_redirect_rules' ) ) {
			return;
		}

		$rules = aioseo()->db->start( 'wf301_redirect_rules' )
			->run()
			->result();
		foreach ( $rules as $rule ) {
			if ( 'cloaking' === $rule->type ) {
				$rule->type = 301;
			}

			if ( ! $this->validateStatusCode( $rule->type ) ) {
				continue;
			}

			if ( empty( $rule->url_to ) ) {
				$rule->url_to = '/';
			}

			$urlFrom    = 'enabled' === $rule->regex ? ltrim( $rule->url_from, '/' ) : $rule->url_from;
			$urlTo      = 'enabled' === $rule->regex ? preg_replace( '/\[([0-9]+)\]/', '\$$1', $rule->url_to ) : $rule->url_to;
			$redirect   = Models\Redirect::getRedirectBySourceUrl( $urlFrom );
			$matchedUrl = Utils\Request::getMatchedUrl( urlFrom );
			$redirect->set( [
				'source_url'       => $urlFrom,
				'source_url_match' => $matchedUrl,
				'target_url'       => Utils\Request::getTargetUrl( $urlTo ),
				'type'             => $rule->type,
				'query_param'      => $rule->query_parameters,
				'group'            => 'manual',
				'regex'            => 'enabled' === $rule->regex,
				'ignore_slash'     => aioseoRedirects()->options->redirectDefaults->ignoreSlash,
				'ignore_case'      => 'enabled' === $rule->case_insensitive,
				'enabled'          => 'enabled' === $rule->status
			] );
			$redirect->save();

			// Save hits.
			if ( $rule->last_count ) {
				$hits = aioseo()->db->start( 'aioseo_redirects_hits' )
					->where( 'redirect_id', $redirect->id )
					->run()
					->model( 'AIOSEO\\Plugin\\Addon\\Redirects\\Models\\RedirectsHit' );

				if ( ! $hits->exists() ) {
					$hits->redirect_id = $redirect->id;
				}

				$hits->count = (int) $rule->last_count;
				$hits->save();
			}
		}
	}
}