<?php
// If the class exists already don't redeclare.
if ( ! class_exists( 'AIOSEOUpdates' ) ) {
	/**
	 * This class pulls in translations for the current addon.
	 *
	 * @since 1.0.0
	 */
	class AIOSEOUpdates {
		/**
		 * Class Constructor
		 *
		 * @param string $type   Project type. Either plugin or theme.
		 * @param string $slug   Project directory slug.
		 * @param string $apiUrl Full GlotPress API URL for the project.
		 */
		public function __construct( $slug, $version, $basename ) {
			$this->slug     = $slug;
			$this->version  = $version;
			$this->basename = $basename;
			add_action( 'init', [ $this, 'init' ] );
		}

		/**
		 * Adds a new project to load translations for.
		 *
		 * @since 1.0.0
		 *
		 * @param  string $type   Project type. Either plugin or theme.
		 * @param  string $slug   Project directory slug.
		 * @param  string $apiUrl Full GlotPress API URL for the project.
		 * @return void
		 */
		public function init() {
			new \AIOSEO\Plugin\Pro\Admin\Updates( [
				'pluginSlug' => $this->slug,
				'pluginPath' => $this->basename,
				'version'    => $this->version,
				'key'        => aioseo()->options->general->licenseKey
			] );
		}
	}
}