<?php
/**
 * Core plugin orchestrator.
 *
 * @package KHImage\Core
 */

namespace KHImage\Core;

use KHImage\Admin\Assets;
use KHImage\Admin\Page;
use KHImage\Admin\Settings;
use KHImage\Rest\Controller as RestController;
use KHImage\Services\DirectoryScanner;
use KHImage\Services\Notifications;
use KHImage\Services\Optimizer;
use KHImage\Services\QueueManager;
use KHImage\Services\StatsRepository;

/**
 * Main plugin singleton.
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '0.1.0';

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	protected static $instance = null;

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Admin assets handler.
	 *
	 * @var Assets
	 */
	protected $assets;

	/**
	 * Admin page.
	 *
	 * @var Page
	 */
	protected $page;

	/**
	 * REST controller.
	 *
	 * @var RestController
	 */
	protected $rest_controller;

	/**
	 * Optimizer service.
	 *
	 * @var Optimizer
	 */
	protected $optimizer;

	/**
	 * Queue manager.
	 *
	 * @var QueueManager
	 */
	protected $queue_manager;

	/**
	 * Stats repository.
	 *
	 * @var StatsRepository
	 */
	protected $stats;

	/**
	 * Directory scanner.
	 *
	 * @var DirectoryScanner
	 */
	protected $directory_scanner;

	/**
	 * Notifications service.
	 *
	 * @var Notifications
	 */
	protected $notifications;

	/**
	 * Retrieve singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	protected function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Include dependencies.
	 *
	 * @return void
	 */
	protected function includes() {
		$this->settings        = new Settings();
		$this->assets          = new Assets();
		$this->page            = new Page();
		$this->stats             = new StatsRepository();
		$this->notifications     = new Notifications();
		$this->optimizer         = new Optimizer( $this->settings, $this->stats );
		$this->queue_manager     = new QueueManager( $this->optimizer, $this->notifications );
		$this->directory_scanner = new DirectoryScanner();
		$this->rest_controller   = new RestController(
			$this->settings,
			$this->queue_manager,
			$this->directory_scanner,
			$this->notifications
		);
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	protected function hooks() {
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Bootstrap services during plugins_loaded.
	 *
	 * @return void
	 */
	public function init() {
		$this->settings->register();
		$this->assets->register();
		$this->page->register();
		$this->queue_manager->register();
		$this->rest_controller->register();
		add_action( 'admin_notices', array( $this, 'render_admin_notices' ) );
	}

	/**
	 * Handle plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		$this->queue_manager->schedule_event();
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		$this->queue_manager->clear_event();
	}

	/**
	 * Output stored notices in standard WP notice area.
	 *
	 * @return void
	 */
	public function render_admin_notices() {
		if ( $this->is_plugin_screen() ) {
			// React dashboard renders/dismisses notices on this screen.
			return;
		}

		$notices = $this->notifications->all();

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			$type    = ! empty( $notice['type'] ) ? $notice['type'] : 'info';
			$message = ! empty( $notice['message'] ) ? $notice['message'] : '';

			if ( empty( $message ) ) {
				continue;
			}

			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $type ),
				esc_html( $message )
			);

			if ( ! empty( $notice['id'] ) ) {
				$this->notifications->remove(
					$notice['id'],
					isset( $notice['global'] ) ? (bool) $notice['global'] : null
				);
			}
		}
	}

	/**
	 * Determine if current admin screen is the React dashboard.
	 *
	 * @return bool
	 */
	protected function is_plugin_screen() {
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen && 'toplevel_page_kh-image' === $screen->id ) {
				return true;
			}
		}

		return isset( $_GET['page'] ) && 'kh-image' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}
