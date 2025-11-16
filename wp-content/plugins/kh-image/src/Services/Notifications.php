<?php
/**
 * Simple notification manager for admin UI.
 *
 * @package KHImage\Services
 */

namespace KHImage\Services;

/**
 * Class Notifications.
 */
class Notifications {

	/**
	 * Base option keys.
	 */
	const OPTION_GLOBAL = 'kh_image_notices_global';
	const OPTION_USER   = 'kh_image_notices_user_';

	/**
	 * Add notice.
	 *
	 * @param string $message Message text.
	 * @param string $type    Type (success|error|info|warning).
	 * @param bool   $global  Whether notice is global.
	 * @param int    $user_id Target user ID (defaults current user).
	 *
	 * @return void
	 */
	public function add( $message, $type = 'success', $global = false, $user_id = 0 ) {
		$key = $this->get_option_key( $global, $user_id );

		$notices   = get_option( $key, array() );
		$notices[] = array(
			'id'      => uniqid( 'kh_notice_', true ),
			'message' => $message,
			'type'    => $type,
			'time'    => time(),
			'global'  => $global,
		);

		update_option( $key, $notices );
	}

	/**
	 * Retrieve notices for current user and optionally clear them.
	 *
	 * @param bool $clear Clear after fetching.
	 *
	 * @return array
	 */
	public function all( $clear = false ) {
		$user_key   = $this->get_option_key( false );
		$global     = get_option( self::OPTION_GLOBAL, array() );
		$user       = get_option( $user_key, array() );
		$all        = array_merge( $global, $user );

		if ( $clear ) {
			if ( ! empty( $user ) ) {
				delete_option( $user_key );
			}
			if ( ! empty( $global ) ) {
				delete_option( self::OPTION_GLOBAL );
			}
		}

		return $all;
	}

	/**
	 * Build option key.
	 *
	 * @param bool $global  Global flag.
	 * @param int  $user_id Optional user id.
	 *
	 * @return string
	 */
	protected function get_option_key( $global = false, $user_id = 0 ) {
		if ( $global ) {
			return self::OPTION_GLOBAL;
		}

		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();

		// If no user detected fallback to global.
		if ( ! $user_id ) {
			return self::OPTION_GLOBAL;
		}

		return self::OPTION_USER . $user_id;
	}

	/**
	 * Remove a notice by id.
	 *
	 * @param string     $id      Notice ID.
	 * @param bool|null  $global  Optional flag (null to search both).
	 * @param int        $user_id Optional user.
	 *
	 * @return bool
	 */
	public function remove( $id, $global = null, $user_id = 0 ) {
		$removed = false;

		if ( null === $global || false === $global ) {
			$key     = $this->get_option_key( false, $user_id );
			$notices = get_option( $key, array() );

			$updated = array_filter(
				$notices,
				static function ( $notice ) use ( $id ) {
					return isset( $notice['id'] ) && $notice['id'] !== $id;
				}
			);

			if ( count( $updated ) !== count( $notices ) ) {
				update_option( $key, array_values( $updated ) );
				$removed = true;
			}
		}

		if ( null === $global || true === $global ) {
			$notices = get_option( self::OPTION_GLOBAL, array() );
			$updated = array_filter(
				$notices,
				static function ( $notice ) use ( $id ) {
					return isset( $notice['id'] ) && $notice['id'] !== $id;
				}
			);

			if ( count( $updated ) !== count( $notices ) ) {
				update_option( self::OPTION_GLOBAL, array_values( $updated ) );
				$removed = true;
			}
		}

		return $removed;
	}
}
