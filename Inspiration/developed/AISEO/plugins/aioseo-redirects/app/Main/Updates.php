<?php
namespace AIOSEO\Plugin\Addon\Redirects\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater class.
 *
 * @since 1.0.0
 */
class Updates {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		add_action( 'aioseo_run_updates', [ $this, 'runUpdates' ], 1000 );
		add_action( 'aioseo_run_updates', [ $this, 'updateLatestVersion' ], 3000 );
	}

	/**
	 * Runs our migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function runUpdates() {
		$lastActiveVersion = aioseoRedirects()->internalOptions->internal->lastActiveVersion;
		if ( version_compare( $lastActiveVersion, '1.0.0', '<' ) ) {
			$this->addRedirectsTables();
		}

		if ( version_compare( $lastActiveVersion, '1.1.0', '<' ) ) {
			$this->migrateRedirectDefaults();
			$this->addCustomRuleColumn();
			$this->allowNullTargetUrl();
		}
	}

	/**
	 * Updates the latest version after all migrations and updates have run.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updateLatestVersion() {
		if ( aioseoRedirects()->internalOptions->internal->lastActiveVersion === aioseoRedirects()->version ) {
			return;
		}

		aioseoRedirects()->internalOptions->internal->lastActiveVersion = aioseoRedirects()->version;

		// Let's empty the cache on version changes.
		aioseoRedirects()->cache->clear();

		// Bust the DB cache so we can make sure that everything is fresh.
		aioseo()->db->bustCache();
	}

	/**
	 * Adds a column for custom redirect matching rules.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function addCustomRuleColumn() {
		if ( ! function_exists( 'aioseo' ) ) {
			return;
		}

		if ( ! aioseo()->db->columnExists( 'aioseo_redirects', 'custom_rules' ) ) {
			$tableName = aioseo()->db->db->prefix . 'aioseo_redirects';
			aioseo()->db->execute(
				"ALTER TABLE {$tableName}
				ADD custom_rules text DEFAULT NULL AFTER query_param"
			);
		}
	}

	/**
	 * Adds a column for custom redirect matching rules.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function allowNullTargetUrl() {
		if ( ! function_exists( 'aioseo' ) ) {
			return;
		}

		$tableName = aioseo()->db->db->prefix . 'aioseo_redirects';
		aioseo()->db->execute(
			"ALTER TABLE {$tableName}
			MODIFY `target_url_hash` varchar(40) DEFAULT NULL"
		);
	}

	/**
	 * Add MySQL tables for redirects.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function addRedirectsTables() {
		// This update requires V4 to be active and since this could run on an activation hook, we need an extra sanity check.
		if ( ! function_exists( 'aioseo' ) ) {
			return;
		}

		$db             = aioseo()->db->db;
		$charsetCollate = '';

		if ( ! empty( $db->charset ) ) {
			$charsetCollate .= "DEFAULT CHARACTER SET {$db->charset}";
		}
		if ( ! empty( $db->collate ) ) {
			$charsetCollate .= " COLLATE {$db->collate}";
		}

		// Check for redirects table.
		if ( ! aioseo()->db->tableExists( 'aioseo_redirects' ) ) {
			$tableName = $db->prefix . 'aioseo_redirects';

			aioseo()->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`source_url` text NOT NULL,
					`source_url_hash` varchar(40) NOT NULL,
					`source_url_match` text NOT NULL,
					`source_url_match_hash` varchar(40) NOT NULL,
					`target_url` text NOT NULL,
					`target_url_hash` varchar(40) NOT NULL,
					`type` int(11) unsigned NOT NULL DEFAULT 301,
					`query_param` varchar(40) NOT NULL DEFAULT 'ignore',
					`group` varchar(256) NOT NULL DEFAULT 'manual',
					`regex` tinyint(1) unsigned NOT NULL DEFAULT 0,
					`ignore_slash` tinyint(1) unsigned NOT NULL DEFAULT 1,
					`ignore_case` tinyint(1) unsigned NOT NULL DEFAULT 1,
					`enabled` tinyint(1) unsigned NOT NULL DEFAULT 1,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY ndx_aioseo_redirects_source_url_hash (source_url_hash),
					KEY ndx_aioseo_redirects_source_url_match_hash (source_url_match_hash),
					KEY ndx_aioseo_redirects_target_url_hash (target_url_hash),
					KEY ndx_aioseo_redirects_type (type),
					KEY ndx_aioseo_redirects_enabled (enabled)
				) {$charsetCollate};"
			);
		}

		if ( ! aioseo()->db->tableExists( 'aioseo_redirects_hits' ) ) {
			$tableName = $db->prefix . 'aioseo_redirects_hits';

			aioseo()->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`redirect_id` bigint(20) unsigned NOT NULL,
					`count` bigint(20) unsigned NOT NULL DEFAULT 0,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY ndx_aioseo_redirects_hits_redirect_id (redirect_id),
					KEY ndx_aioseo_redirects_hits_count (count)
				) {$charsetCollate};"
			);
		}

		if ( ! aioseo()->db->tableExists( 'aioseo_redirects_logs' ) ) {
			$tableName = $db->prefix . 'aioseo_redirects_logs';

			aioseo()->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`url` mediumtext NOT NULL,
					`domain` varchar(255) DEFAULT NULL,
					`sent_to` mediumtext DEFAULT NULL,
					`agent` mediumtext,
					`referrer` mediumtext DEFAULT NULL,
					`http_code` int(11) unsigned NOT NULL DEFAULT 0,
					`request_method` varchar(10) DEFAULT NULL,
					`request_data` mediumtext DEFAULT NULL,
					`redirect_by` varchar(50) DEFAULT NULL,
					`redirect_id` bigint(20) unsigned DEFAULT NULL,
					`ip` varchar(45) DEFAULT NULL,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (`id`),
					KEY ndx_aioseo_redirects_logs_created (`created`),
					KEY ndx_aioseo_redirects_logs_redirect_id (`redirect_id`),
					KEY ndx_aioseo_redirects_logs_ip (`ip`)
				) {$charsetCollate};"
			);
		}

		if ( ! aioseo()->db->tableExists( 'aioseo_redirects_404_logs' ) ) {
			$tableName = $db->prefix . 'aioseo_redirects_404_logs';

			aioseo()->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`url` mediumtext NOT NULL,
					`domain` varchar(255) DEFAULT NULL,
					`agent` mediumtext,
					`referrer` mediumtext DEFAULT NULL,
					`http_code` int(11) unsigned NOT NULL DEFAULT 0,
					`request_method` varchar(10) DEFAULT NULL,
					`request_data` mediumtext DEFAULT NULL,
					`ip` varchar(45) DEFAULT NULL,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (`id`),
					KEY ndx_aioseo_redirects_404_logs_created (`created`),
					KEY ndx_aioseo_redirects_404_logs_ip (`ip`)
				) {$charsetCollate};"
			);
		}
	}

	/**
	 * Migrate redirect default options.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	private function migrateRedirectDefaults() {
		$dbOptions = json_decode( get_option( aioseoRedirects()->options->optionsName ), true );
		if ( empty( $dbOptions['defaults'] ) ) {
			return;
		}

		if ( isset( $dbOptions['defaults']['ignoreCase'] ) ) {
			aioseoRedirects()->options->redirectDefaults->ignoreCase = $dbOptions['defaults']['ignoreCase'];
		}

		if ( isset( $dbOptions['defaults']['ignoreSlash'] ) ) {
			aioseoRedirects()->options->redirectDefaults->ignoreSlash = $dbOptions['defaults']['ignoreSlash'];
		}

		if ( isset( $dbOptions['defaults']['redirectType'] ) ) {
			aioseoRedirects()->options->redirectDefaults->redirectType = $dbOptions['defaults']['redirectType'];
		}

		if ( isset( $dbOptions['defaults']['queryParam'] ) ) {
			aioseoRedirects()->options->redirectDefaults->queryParam = $dbOptions['defaults']['queryParam'];
		}

		return;
	}
}