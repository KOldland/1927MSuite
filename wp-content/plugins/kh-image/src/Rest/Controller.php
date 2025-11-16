<?php
/**
 * REST API scaffold for KH-Image.
 *
 * @package KHImage\Rest
 */

namespace KHImage\Rest;

use KHImage\Admin\Settings;
use KHImage\Services\DirectoryScanner;
use KHImage\Services\Notifications;
use KHImage\Services\QueueManager;
use WP_Error;
use WP_REST_Request;

/**
 * Primary REST controller placeholder.
 */
class Controller {

	/**
	 * Namespace for custom routes.
	 *
	 * @var string
	 */
	const NAMESPACE = 'kh-image/v1';

	/**
	 * Settings dependency.
	 *
	 * @var Settings
	 */
	protected $settings;

	/**
	 * Queue manager.
	 *
	 * @var QueueManager
	 */
	protected $queue;

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
	 * Constructor.
	 *
	 * @param Settings          $settings      Settings service.
	 * @param QueueManager      $queue         Queue manager.
	 * @param DirectoryScanner  $scanner       Directory scanner.
	 * @param Notifications     $notifications Notification service.
	 */
	public function __construct( Settings $settings, QueueManager $queue, DirectoryScanner $scanner, Notifications $notifications ) {
		$this->settings          = $settings;
		$this->queue             = $queue;
		$this->directory_scanner = $scanner;
		$this->notifications     = $notifications;
	}

	/**
	 * Register hooks for REST routes.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/quick-setup',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_quick_setup' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'mode' => array(
						'type'    => 'string',
						'enum'    => array( 'lossless', 'lossy' ),
						'required'=> false,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/bulk/enqueue',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'enqueue_bulk' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/queue',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_queue' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/directory',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'scan_directory' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'path' => array(
						'type'     => 'string',
						'required' => false,
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/directory/enqueue',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'enqueue_directory_files' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/notices',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_notices' ),
				'permission_callback' => array( $this, 'permissions_check' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/notices/dismiss',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'dismiss_notice' ),
				'permission_callback' => array( $this, 'permissions_check' ),
				'args'                => array(
					'id' => array(
						'type'     => 'string',
						'required' => true,
					),
					'global' => array(
						'type'     => 'boolean',
						'required' => false,
					),
				),
			)
		);
	}

	/**
	 * Permission check for admin-only endpoints.
	 *
	 * @return bool
	 */
	public function permissions_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Temporary status endpoint placeholder.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status() {
		$queue_status = $this->queue->get_status();

		return rest_ensure_response(
			array(
				'version' => \KHImage\Core\Plugin::VERSION,
				'status'  => 'bootstrapped',
				'settings'=> $this->settings->get_settings(),
				'queue'   => $queue_status,
			)
		);
	}

	/**
	 * Handle quick setup submission.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function handle_quick_setup( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			return new WP_Error( 'kh_image_invalid_payload', __( 'Missing payload', 'kh-image' ), array( 'status' => 400 ) );
		}

		$settings = $this->settings->apply_quick_setup( $params );

		return rest_ensure_response(
			array(
				'settings' => $settings,
				'message'  => __( 'Quick setup saved.', 'kh-image' ),
			)
		);
	}

	/**
	 * Enqueue attachments for bulk processing.
	 *
	 * @param WP_REST_Request $request Request instance.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function enqueue_bulk( WP_REST_Request $request ) {
		$ids = $request->get_param( 'attachments' );

		if ( empty( $ids ) || ! is_array( $ids ) ) {
			return new WP_Error( 'kh_image_invalid_attachments', __( 'No attachments provided.', 'kh-image' ), array( 'status' => 400 ) );
		}

		$count = 0;
		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( $id > 0 ) {
				$this->queue->enqueue( $id, 'bulk' );
				$count ++;
			}
		}

		$this->settings->mark_bulk_run();

		return rest_ensure_response(
			array(
				'enqueued' => $count,
			)
		);
	}

	/**
	 * Return queue snapshot.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_queue( WP_REST_Request $request ) {
		$context = $request->get_param( 'context' );
		return rest_ensure_response(
			array(
				'jobs'   => $this->queue->get_jobs( 50, $context ),
				'status' => $this->queue->get_status(),
			)
		);
	}

	/**
	 * Scan directories for Directory Smush.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function scan_directory( WP_REST_Request $request ) {
		$path = $request->get_param( 'path' );
		return rest_ensure_response( $this->directory_scanner->scan( $path ) );
	}

	/**
	 * Enqueue selected files from directory scan.
	 *
	 * @param WP_REST_Request $request Request instance.
	 *
	 * @return \WP_REST_Response|WP_Error
	 */
	public function enqueue_directory_files( WP_REST_Request $request ) {
		$files = $request->get_param( 'files' );

		if ( empty( $files ) || ! is_array( $files ) ) {
			return new WP_Error( 'kh_image_invalid_files', __( 'No files provided.', 'kh-image' ), array( 'status' => 400 ) );
		}

		$uploads = wp_get_upload_dir();
		$root    = trailingslashit( $uploads['basedir'] );
		$count   = 0;

		foreach ( $files as $relative ) {
			$full = realpath( $root . $relative );
			if ( false === $full || strpos( $full, $root ) !== 0 ) {
				continue;
			}

			$this->queue->enqueue( 0, 'directory', array( 'file_path' => $full ) );
			$count ++;
		}

		$this->notifications->add(
			sprintf(
				/* translators: %d number of files */
				__( 'Directory Smush queued %d files.', 'kh-image' ),
				$count
			),
			'success'
		);

		return rest_ensure_response(
			array(
				'enqueued' => $count,
			)
		);
	}

	/**
	 * Fetch notices for frontend.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_notices() {
		return rest_ensure_response(
			array(
				'notices' => $this->notifications->all(),
			)
		);
	}

	/**
	 * Dismiss notice by id.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return \WP_REST_Response
	 */
	public function dismiss_notice( WP_REST_Request $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$id     = isset( $params['id'] ) ? $params['id'] : $request->get_param( 'id' );
		$global = isset( $params['global'] ) ? $params['global'] : $request->get_param( 'global' );

		$result = $this->notifications->remove( $id, $global );

		return rest_ensure_response(
			array(
				'dismissed' => (bool) $result,
			)
		);
	}
}
