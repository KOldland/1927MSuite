<?php

namespace Elementor\Core\Experiments;

/**
 * Lightweight stand-in so `wp elementor experiments` does not fatal in local CLI
 * environments where Elementor's optional CLI package is absent.
 */
class WP_CLI {

	/**
	 * No-op handler that keeps WP-CLI happy when the command is executed.
	 *
	 * @return void
	 */
	public function __invoke() {
		\WP_CLI::log( 'Elementor experiments command is unavailable in this environment.' );
	}
}
