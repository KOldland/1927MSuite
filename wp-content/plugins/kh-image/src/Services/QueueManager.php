<?php
/**
 * Background queue manager.
 *
 * @package KHImage\Services
 */

namespace KHImage\Services;

use KHImage\Services\Optimizer;
use KHImage\Services\Notifications;

/**
 * Simple WP-Cron based queue processor.
 */
class QueueManager {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	const CRON_HOOK = 'kh_image_process_queue';

	/**
	 * Option used to persist queued jobs.
	 *
	 * @var string
	 */
	const OPTION_QUEUE = 'kh_image_queue';

	/**
	 * Optimizer dependency.
	 *
	 * @var Optimizer
	 */
	protected $optimizer;

	/**
	 * Notifications service.
	 *
	 * @var Notifications
	 */
	protected $notifications;

	/**
	 * QueueManager constructor.
	 *
	 * @param Optimizer      $optimizer     Optimizer instance.
	 * @param Notifications  $notifications Notification service.
	 */
	public function __construct( Optimizer $optimizer, Notifications $notifications ) {
		$this->optimizer     = $optimizer;
		$this->notifications = $notifications;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( self::CRON_HOOK, array( $this, 'process_queue' ) );
		add_action( 'add_attachment', array( $this, 'enqueue_upload' ) );
		add_filter( 'cron_schedules', array( $this, 'add_schedule' ) );
	}

	/**
	 * Add a minutely schedule for our queue.
	 *
	 * @param array $schedules Existing schedules.
	 *
	 * @return array
	 */
	public function add_schedule( $schedules ) {
		if ( ! isset( $schedules['kh_image_minutely'] ) ) {
			$schedules['kh_image_minutely'] = array(
				'interval' => MINUTE_IN_SECONDS,
				'display'  => __( 'Every Minute (KH Image)', 'kh-image' ),
			);
		}

		return $schedules;
	}

	/**
	 * Schedule cron processing event.
	 *
	 * @return void
	 */
	public function schedule_event() {
		add_filter( 'cron_schedules', array( $this, 'add_schedule' ) );

		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, 'kh_image_minutely', self::CRON_HOOK );
		}
	}

	/**
	 * Clear cron event on deactivation.
	 *
	 * @return void
	 */
	public function clear_event() {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Enqueue a newly uploaded attachment for optimization.
	 *
	 * @param int $attachment_id Attachment ID.
	 *
	 * @return void
	 */
	public function enqueue_upload( $attachment_id ) {
		$this->enqueue( $attachment_id, 'upload' );
	}

	/**
	 * Add job to queue.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $context       Context label.
	 *
	 * @return void
	 */
	public function enqueue( $attachment_id, $context = 'manual', $data = array() ) {
		$queue   = $this->get_queue();
		$queue[] = array(
			'attachment_id' => absint( $attachment_id ),
			'context'       => $context,
			'enqueued_at'   => time(),
			'file_path'     => isset( $data['file_path'] ) ? $data['file_path'] : null,
		);
		update_option( self::OPTION_QUEUE, $queue );
	}

	/**
	 * Process a small batch of queued jobs.
	 *
	 * @return void
	 */
	public function process_queue() {
		$queue = $this->get_queue();

		if ( empty( $queue ) ) {
			return;
		}

		$batch            = array_splice( $queue, 0, 5 );
		$success          = 0;
		$failure          = 0;
		$context_success  = array();
		$context_failure  = array();

		foreach ( $batch as $job ) {
			$result = false;

			if ( ! empty( $job['file_path'] ) ) {
				$output = $this->optimizer->optimize_file( $job['file_path'], $job['context'] );
				$result = ! empty( $output );
			} elseif ( ! empty( $job['attachment_id'] ) ) {
				$result = $this->optimizer->optimize_attachment( $job['attachment_id'], $job['context'] );
			}

			if ( $result ) {
				$success ++;
				$context = isset( $job['context'] ) ? $job['context'] : 'queued';
				if ( ! isset( $context_success[ $context ] ) ) {
					$context_success[ $context ] = 0;
				}
				$context_success[ $context ] ++;
			} else {
				$failure ++;
				$context = isset( $job['context'] ) ? $job['context'] : 'queued';
				if ( ! isset( $context_failure[ $context ] ) ) {
					$context_failure[ $context ] = 0;
				}
				$context_failure[ $context ] ++;
			}
		}

		update_option( self::OPTION_QUEUE, $queue );

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			foreach ( $context_success as $context => $count ) {
				$this->notifications->add(
					sprintf(
						/* translators: 1: number of files 2: context */
						__( 'KH Image optimized %1$d %2$s files.', 'kh-image' ),
						$count,
						$this->human_context( $context )
					),
					'success',
					true
				);
			}

			foreach ( $context_failure as $context => $count ) {
				$this->notifications->add(
					sprintf(
						/* translators: 1: number of files 2: context */
						__( 'KH Image failed to optimize %1$d %2$s files.', 'kh-image' ),
						$count,
						$this->human_context( $context )
					),
					'error',
					true
				);
			}

			$this->emit_completion_notices( $context_success, $context_failure, $queue );
		}
	}

	/**
	 * Return readable context.
	 *
	 * @param string $context Context slug.
	 *
	 * @return string
	 */
	protected function human_context( $context ) {
		switch ( $context ) {
			case 'upload':
				return __( 'upload', 'kh-image' );
			case 'bulk':
				return __( 'bulk', 'kh-image' );
			case 'directory':
				return __( 'directory', 'kh-image' );
			default:
				return __( 'queued', 'kh-image' );
		}
	}

	/**
	 * Return queued jobs.
	 *
	 * @return array
	 */
	public function get_queue() {
		return get_option( self::OPTION_QUEUE, array() );
	}

	/**
	 * Provide queue summary for REST/diagnostics.
	 *
	 * @return array
	 */
	public function get_status() {
		$queue = $this->get_queue();

		return array(
			'count'      => count( $queue ),
			'next_event' => wp_next_scheduled( self::CRON_HOOK ),
		);
	}

	/**
	 * Return limited queue snapshot.
	 *
	 * @param int    $limit   Limit.
	 * @param string $context Optional context filter.
	 *
	 * @return array
	 */
	public function get_jobs( $limit = 50, $context = '' ) {
		$jobs = $this->get_queue();

		if ( $context && 'all' !== $context ) {
			$jobs = array_filter(
				$jobs,
				static function ( $job ) use ( $context ) {
					return isset( $job['context'] ) && $job['context'] === $context;
				}
			);
		}

		return array_slice( array_values( $jobs ), 0, $limit );
	}

	/**
	 * Emit notices when a context-specific batch completes (e.g., directory jobs).
	 *
	 * @param array $context_success Successful job counts keyed by context.
	 * @param array $context_failure Failed job counts keyed by context.
	 * @param array $remaining_queue Remaining queue data.
	 *
	 * @return void
	 */
	protected function emit_completion_notices( $context_success, $context_failure, $remaining_queue ) {
		$remaining_contexts = $this->count_contexts( $remaining_queue );
		$processed_contexts = array_unique(
			array_merge(
				array_keys( $context_success ),
				array_keys( $context_failure )
			)
		);

		foreach ( $processed_contexts as $context ) {
			if ( isset( $remaining_contexts[ $context ] ) && $remaining_contexts[ $context ] > 0 ) {
				continue;
			}

			$success = isset( $context_success[ $context ] ) ? $context_success[ $context ] : 0;
			$failure = isset( $context_failure[ $context ] ) ? $context_failure[ $context ] : 0;

			if ( ! $success && ! $failure ) {
				continue;
			}

			if ( $failure && $success ) {
				$message = sprintf(
					/* translators: 1: context, 2: successes, 3: failures */
					__( '%1$s jobs finished: %2$d optimized, %3$d failed.', 'kh-image' ),
					ucfirst( $this->human_context( $context ) ),
					$success,
					$failure
				);
				$type = 'warning';
			} elseif ( $failure ) {
				$message = sprintf(
					/* translators: 1: context, 2: failures */
					__( '%1$s jobs finished with %2$d failures.', 'kh-image' ),
					ucfirst( $this->human_context( $context ) ),
					$failure
				);
				$type = 'error';
			} else {
				$message = sprintf(
					/* translators: 1: context, 2: successes */
					__( '%1$s jobs finished processing %2$d files.', 'kh-image' ),
					ucfirst( $this->human_context( $context ) ),
					$success
				);
				$type = 'success';
			}

			$this->notifications->add( $message, $type, true );
		}
	}

	/**
	 * Count remaining jobs per context.
	 *
	 * @param array $queue Remaining queue.
	 *
	 * @return array
	 */
	protected function count_contexts( $queue ) {
		$counts = array();

		foreach ( $queue as $job ) {
			$context = isset( $job['context'] ) ? $job['context'] : 'queued';

			if ( ! isset( $counts[ $context ] ) ) {
				$counts[ $context ] = 0;
			}

			$counts[ $context ] ++;
		}

		return $counts;
	}
}
