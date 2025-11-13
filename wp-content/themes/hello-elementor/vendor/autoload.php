<?php

/**
 * Minimal stubs so the Hello Elementor theme does not fatal when its Composer
 * dependencies are absent in CLI-only environments.
 */

namespace Elementor\WPNotificationsPackage\V110;

if ( ! class_exists( Notifications::class ) ) {
	class Notifications {
		public function __construct( ...$args ) {
			// no-op
		}
	}
}
