<?php
// phpcs:disable Generic.Arrays.DisallowLongArraySyntax.Found
// If the class exists already don't redeclare.
if ( ! class_exists( 'AIOSEOExtend' ) ) {
	/**
	 * This class checks for compatibility for this plugin to load.
	 *
	 * @since 1.0.0
	 */
	class AIOSEOExtend {
		/**
		 * Whether or not to disable other notices.
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		public $disableNotices = false;

		/**
		 * The construct function.
		 *
		 * @since 1.0.0
		 *
		 * @param $name           The name of the addon.
		 * @param $function       The name of the function to call once we pass compatibility checks.
		 * @param $file           The addon file to deactivate if checks fail.
		 * @param $minimumVersion The minimum version of our plugin we can activate against.
		 * @param $levels         The levels that this addon support.
		 */
		public function __construct( $name, $function, $file, $minimumVersion, $levels = array() ) {
			$this->name           = $name;
			$this->function       = $function;
			$this->file           = $file;
			$this->minimumVersion = $minimumVersion;
			$this->levels         = $levels;

			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		/**
		 * Check addon requirements.
		 * We do it on `plugins_loaded` hook. If earlier the core constants are still not defined.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function init() {
			if ( version_compare( PHP_VERSION, '5.4', '<' ) ) {
				$this->failPhp();

				return;
			}

			// Since the version numbers may vary, we only want to compare the first 3 numbers.
			$version = defined( 'AIOSEO_VERSION' ) ? explode( '-', AIOSEO_VERSION ) : null;

			if (
				empty( $version ) ||
				version_compare( $version[0], $this->minimumVersion, '<' )
			) {
				$this->requiresPro();
				add_filter( 'auto_update_plugin', array( $this, 'disableAutoUpdate' ), 10, 2 );
				add_filter( 'plugin_auto_update_setting_html', array( $this, 'modifyAutoupdaterSettingHtml' ), 11, 2 );

				return;
			}

			add_action( 'aioseo_loaded', $this->function, 10 );
		}

		/**
		 * Throws a notice if PHP version is too low.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function failPhp() {
			global $aioseoGlobalNotices;
			$output = false;
			if ( empty( $aioseoGlobalNotices ) ) {
				$aioseoGlobalNotices = array();
				$output              = true;
			}
			if ( empty( $aioseoGlobalNotices['aioseoAddonFailPhpVersion'] ) ) {
				$aioseoGlobalNotices['aioseoAddonFailPhpVersion'] = array();
			}

			$aioseoGlobalNotices['aioseoAddonFailPhpVersion'][] = $this->name;
			if ( $output ) {
				add_action( 'admin_notices', array( $this, 'aioseoAddonFailPhpVersion' ) );
			}
		}

		/**
		 * A secondary function to call if Pro is not active.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function requiresPro() {
			if ( $this->disableNotices ) {
				return;
			}

			global $aioseoGlobalNotices;
			$output = false;
			if ( empty( $aioseoGlobalNotices ) ) {
				$aioseoGlobalNotices = array();
				$output              = true;
			}
			if ( empty( $aioseoGlobalNotices['aioseoFailActiveVersion'] ) ) {
				$aioseoGlobalNotices['aioseoFailActiveVersion'] = array();
			}

			$aioseoGlobalNotices['aioseoFailActiveVersion'][] = $this->name;
			if ( $output ) {
				add_action( 'admin_notices', array( $this, 'aioseoFailActiveVersion' ) );
			}
		}

		/**
		 * A secondary function to call if an active license is not found.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function requiresActiveLicense() {
			if ( $this->disableNotices ) {
				return;
			}

			global $aioseoGlobalNotices;
			$output = false;
			if ( empty( $aioseoGlobalNotices ) ) {
				$aioseoGlobalNotices = [];
				$output              = true;
			}
			if ( empty( $aioseoGlobalNotices['aioseoFailActiveLicense'] ) ) {
				$aioseoGlobalNotices['aioseoFailActiveLicense'] = [];
			}

			$aioseoGlobalNotices['aioseoFailActiveLicense'][] = $this->name;
			if ( $output ) {
				add_action( 'admin_notices', array( $this, 'aioseoFailActiveLicense' ) );
			}
		}

		/**
		 * A secondary function to call if an expired license is found.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function requiresUnexpiredLicense() {
			if ( $this->disableNotices ) {
				return;
			}

			global $aioseoGlobalNotices;
			$output = false;
			if ( empty( $aioseoGlobalNotices ) ) {
				$aioseoGlobalNotices = array();
				$output              = true;
			}
			if ( empty( $aioseoGlobalNotices['aioseoFailPlanExpired'] ) ) {
				$aioseoGlobalNotices['aioseoFailPlanExpired'] = array();
			}

			$aioseoGlobalNotices['aioseoFailPlanExpired'][] = $this->name;
			if ( $output ) {
				add_action( 'admin_notices', array( $this, 'aioseoFailPlanExpired' ) );
			}
		}

		/**
		 * A secondary function to call if an active license is not found.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function requiresPlanLevel() {
			if ( $this->disableNotices ) {
				return;
			}

			global $aioseoGlobalNotices;
			$output = false;
			if ( empty( $aioseoGlobalNotices ) ) {
				$aioseoGlobalNotices = array();
				$output              = true;
			}
			if ( empty( $aioseoGlobalNotices['aioseoFailPlanLevel'] ) ) {
				$aioseoGlobalNotices['aioseoFailPlanLevel'] = array();
			}

			$aioseoGlobalNotices['aioseoFailPlanLevel'][] = $this->name;
			if ( $output ) {
				add_action( 'admin_notices', array( $this, 'aioseoFailPlanLevel' ) );
			}
		}

		/**
		 * Admin notice for minimum PHP version.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function aioseoAddonFailPhpVersion() {
			global $aioseoGlobalNotices;
			echo '<div class="notice notice-error"><p>';
			printf(
				// Translators: 1 - Opening link tag, 2 - Closing link tag.
				esc_html__( 'Your site is running an outdated version of PHP that is no longer supported and is not compatible with the following addons (%1$sRead more%2$s for additional information):', 'aioseo-addon' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
				'<a href="https://aioseo.com/docs/supported-php-version/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			echo '</p><ul><li><strong>' . implode( '</strong></li><li><strong>', wp_kses_post( $aioseoGlobalNotices['aioseoAddonFailPhpVersion'] ) ) . '</strong></li></ul></div>';

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}

		/**
		 * Admin notice for minimum AIOSEO version.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function aioseoFailActiveVersion() {
			global $aioseoGlobalNotices;
			echo '<div class="notice notice-error"><p>';
			printf(
				// Translators: 1 - "All in One SEO 4.0.0".
				esc_html__( 'The following addons cannot be used, because they require %1$s or later to work:', 'aioseo-addon' ),
				'<strong>All in One SEO Pro ' . wp_kses_post( $this->minimumVersion ) . '</strong>' // We need to put the name here in since the plugin is most likely not active.
			);
			$notice = implode( '</strong></li><li><strong>', $aioseoGlobalNotices['aioseoFailActiveVersion'] );
			echo '</p><ul><li><strong>' . wp_kses_post( $notice ) . '</strong></li></ul></div>';

			if ( isset( $_GET['activate'] ) ) {
				unset( $_GET['activate'] );
			}
		}

		/**
		 * Admin notice for licensed AIOSEO version.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function aioseoFailActiveLicense() {
			global $aioseoGlobalNotices;
			echo '<div class="notice notice-error"><p>';
			printf(
				// Translators: 1 - "All in One SEO", 2 - Opening HTML link tag, 3 - Closing HTML link tag.
				esc_html__( 'The following addons cannot be used, because they require an active license for %1$s. Your license is missing or has expired. To verify your subscription, please %2$svisit our website%3$s.', 'aioseo-addon' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
				esc_html( AIOSEO_PLUGIN_NAME ),
				'<a target="_blank" href="' . aioseo()->helpers->utmUrl( AIOSEO_MARKETING_URL . 'account/', $this->name, 'fail-valid-license' ) . '">', // phpcs:ignore WordPress.Security.EscapeOutput
				'</a>'
			);
			$notice = implode( '</strong></li><li><strong>', $aioseoGlobalNotices['aioseoFailActiveLicense'] );
			echo '</p><ul><li><strong>' . wp_kses_post( $notice ) . '</strong></li></ul></div>';
		}

		/**
		 * Admin notices for incorrect or expired AIOSEO plan.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function aioseoFailPlanExpired() {
			global $aioseoGlobalNotices;
			echo '<div class="notice notice-error"><p>';
			printf(
				// Translators: 1 - Opening HTML link tag, 2 - Closing HTML link tag.
				esc_html__( 'The following addons cannot be used, because your plan has expired. To renew your subscription, please %1$svisit our website%2$s.', 'aioseo-addon' ),
				'<a target="_blank" href="' . aioseo()->helpers->utmUrl( AIOSEO_MARKETING_URL . 'account/subscriptions/', $this->name, 'fail-plan-expired' ) . '">', // phpcs:ignore WordPress.Security.EscapeOutput, Generic.Files.LineLength.MaxExceeded
				'</a>'
			);
			$notice = implode( '</strong></li><li><strong>', $aioseoGlobalNotices['aioseoFailPlanExpired'] );
			echo '</p><ul><li><strong>' . wp_kses_post( $notice ) . '</strong></li></ul></div>';
		}

		/**
		 * Admin notice for incorrect AIOSEO plan.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function aioseoFailPlanLevel() {
			global $aioseoGlobalNotices;
			$level = aioseo()->internalOptions->internal->license->level;
			if ( ! $level ) {
				if ( aioseo()->cache->get( 'failed_update' ) ) {
					return;
				}
				$level = esc_html__( 'Unlicensed', 'aioseo-addon' );
			}
			echo '<div class="notice notice-error"><p>';
			printf(
				// Translators: 1 - The current plan name, 2 - Opening HTML link tag, 3 - Closing HTML link tag.
				esc_html__( 'The following addons cannot be used, because your plan level %1$s does not include access to these addons. To upgrade your subscription, please %2$svisit our website%3$s.', 'aioseo-addon' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
				'<strong>(' . wp_kses_post( ucfirst( $level ) ) . ')</strong>',
				'<a target="_blank" href="' . aioseo()->helpers->utmUrl( AIOSEO_MARKETING_URL . 'pro-upgrade/', $this->name, 'fail-plan-level' ) . '">', // phpcs:ignore WordPress.Security.EscapeOutput, Generic.Files.LineLength.MaxExceeded
				'</a>'
			);
			$notice = implode( '</strong></li><li><strong>', $aioseoGlobalNotices['aioseoFailPlanLevel'] );
			echo '</p><ul><li><strong>' . wp_kses_post( $notice ) . '</strong></li></ul></div>';
		}

		/**
		 * Disable auto-update.
		 *
		 * @since 1.0.0
		 *
		 * @param  bool   $update
		 * @param  object $item
		 * @return bool           Whether or not to auto update.
		 */
		public function disableAutoUpdate( $update, $item ) {
			// If this is multisite and is not on the main site, return early.
			if ( is_multisite() && ! is_main_site() ) {
				return $update;
			}

			if ( plugin_basename( $this->file ) === $item->plugin ) {
				return false;
			}

			return $update;
		}

		/**
		 * Display MonsterInsights Pro CTA on Plugins -> autoupdater setting column
		 *
		 * @since 1.0.0
		 *
		 * @param  string $html
		 * @param  string $pluginFile
		 * @return string             The HTML.
		 */
		public function modifyAutoupdaterSettingHtml( $html, $pluginFile ) {
			if ( plugin_basename( $this->file ) === $pluginFile &&
				// If main plugin (free) happens to be enabled and already takes care of this, then bail
				! apply_filters( "aioseo_is_autoupdate_setting_html_filtered_$pluginFile", false )
			) {
				$html = sprintf(
					'<a href="%s" target="_blank">%s</a>',
					"https://aioseo.com/docs/how-to-upgrade-from-all-in-one-seo-lite-to-pro/?utm_source=liteplugin&utm_medium=plugins-autoupdate&utm_campaign=upgrade-to-autoupdate&utm_content={$this->name}", // phpcs:ignore Generic.Files.LineLength.MaxExceeded
					// Translators: 1 - "AIOSEO Pro"
					sprintf( esc_html__( 'Enable the %1$s plugin to manage auto-updates', 'aioseo-addon' ), 'AIOSEO Pro' )
				);
			}

			return $html;
		}
	}
}

if ( ! function_exists( 'aioseoAddonIsDisabled' ) ) {
	/**
	 * Disable the current addon if triggered externally.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $slug The addon slug.
	 * @return bool         True if the addon should be disabled.
	 */
	function aioseoAddonIsDisabled( $slug ) {
		if ( ! defined( 'AIOSEO_DEV_VERSION' ) && ! isset( $_REQUEST['aioseo-dev'] ) ) { // phpcs:ignore HM.Security.NonceVerification.Recommended
			return false;
		}

		if ( ! isset( $_REQUEST['aioseo-disable-addon'] ) ) { // phpcs:ignore HM.Security.NonceVerification.Recommended
			return false;
		}

		$request = wp_unslash( $_REQUEST['aioseo-disable-addon'] ); // phpcs:ignore HM.Security
		$request = explode( ',', $request );
		foreach ( $request as $r ) {
			if ( 0 !== strpos( $r, 'aioseo' ) ) {
				$r = 'aioseo-' . $r;
			}

			if ( $slug === $r ) {
				return true;
			}
		}

		return false;
	}
}