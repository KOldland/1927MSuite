<?php
/**
 * Admin page scaffold.
 *
 * @package KHImage\Admin
 */

namespace KHImage\Admin;

/**
 * Provides a menu entry + React mount point.
 */
class Page {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add menu page.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'KH Image', 'kh-image' ),
			__( 'KH Image', 'kh-image' ),
			'manage_options',
			'kh-image',
			array( $this, 'render' ),
			'dashicons-format-image',
			58
		);
	}

	/**
	 * Render mount container.
	 *
	 * @return void
	 */
	public function render() {
		echo '<div class="wrap"><div id="kh-image-admin-root"></div></div>';
	}
}
