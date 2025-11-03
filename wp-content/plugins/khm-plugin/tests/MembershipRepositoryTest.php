<?php
namespace KHM\Tests;

use DateTime;
use KHM\Services\MembershipRepository;
use PHPUnit\Framework\TestCase;

class WPDBMembershipStub {
	public string $prefix = 'wp_';
	public string $users  = 'wp_users';
	public int $insert_id = 0;

	/** @var array<int,array<string,mixed>> */
	public array $memberships = [];

	/** @var array<int,string> */
	public array $levels = [];

	/** @var array<int,array<string,string>> */
	public array $usersData = [];

	public function __construct() {
		$this->levels = [
			5 => 'Gold',
		];

		$this->usersData = [
			10 => [
				'user_login'    => 'golduser',
				'user_email'    => 'gold@example.com',
				'display_name'  => 'Gold Member',
			],
		];

		$this->memberships = [
			1 => [
				'id'              => 1,
				'user_id'         => 10,
				'membership_id'   => 5,
				'status'          => 'active',
				'status_reason'   => null,
				'startdate'       => '2025-01-01 00:00:00',
				'enddate'         => null,
				'grace_enddate'   => null,
				'paused_at'       => null,
				'pause_until'     => null,
				'initial_payment' => 99.00,
				'billing_amount'  => 19.00,
				'cycle_number'    => 1,
				'cycle_period'    => 'Month',
				'billing_limit'   => 0,
				'trial_amount'    => 0.00,
				'trial_limit'     => 0,
			],
		];
	}

	public function prepare( $query, ...$args ) {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		// Process %d and %s placeholders in order, matching args to placeholders by position
		$placeholders = [];
		$offset = 0;
		while ( true ) {
			$pos_d = strpos( $query, '%d', $offset );
			$pos_s = strpos( $query, '%s', $offset );
			
			if ( $pos_d === false && $pos_s === false ) {
				break;
			}
			
			if ( $pos_d !== false && ( $pos_s === false || $pos_d < $pos_s ) ) {
				$placeholders[] = ['pos' => $pos_d, 'type' => 'd'];
				$offset = $pos_d + 2;
			} elseif ( $pos_s !== false ) {
				$placeholders[] = ['pos' => $pos_s, 'type' => 's'];
				$offset = $pos_s + 2;
			}
		}

		// Replace placeholders from right to left to avoid position shifts
		for ( $i = count( $placeholders ) - 1; $i >= 0; $i-- ) {
			if ( ! isset( $args[ $i ] ) ) {
				break;
			}
			
			$placeholder = $placeholders[ $i ];
			$arg = $args[ $i ];
			
			if ( $placeholder['type'] === 'd' ) {
				$replacement = (string) (int) $arg;
			} else { // 's'
				$replacement = "'" . addslashes( (string) $arg ) . "'";
			}
			
			$query = substr_replace( $query, $replacement, $placeholder['pos'], 2 );
		}

		return $query;
	}

	public function get_row( $query ) {
		if ( preg_match( '/WHERE m\\.id = (\\d+)/', $query, $match ) ) {
			$id = (int) $match[1];
			if ( isset( $this->memberships[ $id ] ) ) {
				return (object) $this->hydrate_row( $this->memberships[ $id ] );
			}
		}

		if ( preg_match( '/WHERE m\\.user_id = (\\d+) AND m\\.membership_id = (\\d+)/', $query, $match ) ) {
			$user_id  = (int) $match[1];
			$level_id = (int) $match[2];

			foreach ( array_reverse( $this->memberships, true ) as $membership ) {
				if ( (int) $membership['user_id'] === $user_id && (int) $membership['membership_id'] === $level_id ) {
					return (object) $this->hydrate_row( $membership );
				}
			}
		}

		return null;
	}

	public function get_results( $query, $output = OBJECT ) {
		if ( preg_match( '/WHERE m\\.id IN \\(([^)]+)\\)/', $query, $match ) ) {
			$ids = array_map( 'intval', explode( ',', $match[1] ) );
			$rows = [];

			foreach ( $ids as $id ) {
				if ( isset( $this->memberships[ $id ] ) ) {
					$row = $this->hydrate_row( $this->memberships[ $id ] );
					$rows[] = ARRAY_A === $output ? $row : (object) $row;
				}
			}

			return $rows;
		}

		return [];
	}

	public function get_var( $query ) {
		return count( $this->memberships );
	}

	public function get_col( $query ) {
		// This is used by recalculateUserCapabilities to get level IDs for active memberships
		// Match the query pattern from recalculateUserCapabilities
		if ( preg_match("/WHERE user_id = '?(\\d+)'?\\s+AND status IN \\('active','grace'\\)/", $query, $match ) ) {
			$user_id = (int) $match[1];
			$level_ids = [];
			
			foreach ( $this->memberships as $membership ) {
				if ( (int) $membership['user_id'] === $user_id 
					&& in_array( $membership['status'], ['active', 'grace'] ) 
					&& ( empty( $membership['enddate'] ) || $membership['enddate'] > date( 'Y-m-d H:i:s' ) ) ) {
					$level_ids[] = (int) $membership['membership_id'];
				}
			}
			
			return $level_ids;
		}
		
		return [];
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( $table !== $this->prefix . 'khm_memberships_users' ) {
			return false;
		}

		$membership = $this->locate_membership( $where );
		if ( ! $membership ) {
			return false;
		}

		$id = (int) $membership['id'];

		foreach ( $data as $key => $value ) {
			$this->memberships[ $id ][ $key ] = $value;
		}

		return true;
	}

	public function delete( $table, $where, $format = null ) {
		if ( $table !== $this->prefix . 'khm_memberships_users' || ! isset( $where['id'] ) ) {
			return false;
		}

		$id = (int) $where['id'];
		if ( isset( $this->memberships[ $id ] ) ) {
			unset( $this->memberships[ $id ] );
			return true;
		}

		return false;
	}

	public function insert( $table, $data ) {
		if ( $table !== $this->prefix . 'khm_memberships_users' ) {
			return false;
		}

		$this->insert_id++;
		$data['id'] = $this->insert_id;
		$this->memberships[ $this->insert_id ] = $data;
		return true;
	}

	private function locate_membership( array $where ): ?array {
		if ( isset( $where['id'] ) && isset( $this->memberships[ $where['id'] ] ) ) {
			return $this->memberships[ $where['id'] ];
		}

		if ( isset( $where['user_id'], $where['membership_id'] ) ) {
			foreach ( $this->memberships as $membership ) {
				if ( (int) $membership['user_id'] === (int) $where['user_id']
					&& (int) $membership['membership_id'] === (int) $where['membership_id'] ) {
					return $membership;
				}
			}
		}

		return null;
	}

	private function hydrate_row( array $membership ): array {
		$level_id = (int) $membership['membership_id'];
		$user_id  = (int) $membership['user_id'];

		$level_name = $this->levels[ $level_id ] ?? 'Unknown';
		$user       = $this->usersData[ $user_id ] ?? [
			'user_login'   => '',
			'user_email'   => '',
			'display_name' => '',
		];

		$row = $membership;
		$row['level_name']   = $level_name;
		$row['user_login']   = $user['user_login'];
		$row['user_email']   = $user['user_email'];
		$row['display_name'] = $user['display_name'];
		$row['status_reason'] = $row['status_reason'] ?? null;
		$row['grace_enddate'] = $row['grace_enddate'] ?? null;
		$row['paused_at']     = $row['paused_at'] ?? null;
		$row['pause_until']   = $row['pause_until'] ?? null;
		$row['start_date']    = $row['startdate'] ?? null;
		$row['end_date']      = $row['enddate'] ?? null;

		return $row;
	}
}

class MembershipRepositoryTest extends TestCase {
	private MembershipRepository $repository;

	protected function setUp(): void {
		parent::setUp();
		\khm_tests_reset_environment();
		$this->setup_database();
		$this->repository = new MembershipRepository();
		$this->replaceLevelRepositoryWithStub( [] );
		$this->seedUserStub( 10 );
	}

	public function testGetByIdReturnsMembership(): void {
		$membership = $this->repository->getById( 1 );
		$this->assertNotNull( $membership );
		$this->assertSame( 'Gold', $membership->level_name );
		$this->assertSame( 'golduser', $membership->user_login );
	}

	public function testCancelByIdUpdatesStatus(): void {
		$this->replaceLevelRepositoryWithStub( [ 'edit_pages' ] );
		$this->repository->assign( 10, 5, [] );
		$result = $this->repository->cancelById( 1, 'Admin cancel' );
		$this->assertTrue( $result );

		$membership = $this->repository->getById( 1 );
		$this->assertSame( 'cancelled', $membership->status );
		$this->assertNotEmpty( $membership->enddate );
		$user = $this->getUserStub( 10 );
		$this->assertArrayNotHasKey( 'khm_level_5', $user->caps );
	}

	public function testReactivateByIdClearsEndDate(): void {
		$this->repository->cancelById( 1 );

		$result = $this->repository->reactivateById( 1 );
		$this->assertTrue( $result );

		$membership = $this->repository->getById( 1 );
		$this->assertSame( 'active', $membership->status );
		$this->assertNull( $membership->enddate );
	}

	public function testCancelAppliesGracePeriodWhenFilterProvided(): void {
		add_filter( 'khm_membership_grace_period_days', static function() {
			return 3;
		} );

		$this->repository->cancel( 10, 5, 'Grace request' );
		$membership = $this->repository->getById( 1 );
		$this->assertSame( 'grace', $membership->status );
		$this->assertNotEmpty( $membership->grace_enddate );

		remove_all_filters( 'khm_membership_grace_period_days' );
	}

	public function testPauseAndResumeAdjustCapabilities(): void {
		$this->replaceLevelRepositoryWithStub( [ 'edit_pages' ] );
		$this->seedUserStub( 10 );
		
		$membership = $this->repository->assign( 10, 5, [] );

		$user = $this->getUserStub( 10 );
		$this->assertArrayHasKey( 'khm_level_5', $user->caps );
		$this->assertArrayHasKey( 'edit_pages', $user->caps );

		$this->repository->pause( 10, 5, null, 'Pausing' );
		$membership = $this->repository->getById( 1 );
		$this->assertSame( 'paused', $membership->status );
		$this->assertArrayNotHasKey( 'khm_level_5', $user->caps );

		$this->repository->resume( 10, 5, 'Resume' );
		$membership = $this->repository->getById( 1 );
		$this->assertSame( 'active', $membership->status );
		$this->assertArrayHasKey( 'khm_level_5', $user->caps );
		$this->assertArrayHasKey( 'edit_pages', $user->caps );
	}

	public function testDeleteByIdRemovesMembership(): void {
		$this->assertTrue( $this->repository->deleteById( 1 ) );
		$this->assertNull( $this->repository->getById( 1 ) );
	}

	public function testGetManyReturnsRows(): void {
		$rows = $this->repository->getMany( [ 1 ] );
		$this->assertCount( 1, $rows );
		$this->assertSame( 'Gold Member', $rows[0]['display_name'] );
	}

	private function setup_database(): void {
		global $wpdb;
		$wpdb = new WPDBMembershipStub();
	}

	private function replaceLevelRepositoryWithStub( array $caps ): void {
		$levelsStub = new class( $caps ) extends \KHM\Services\LevelRepository {
			private array $caps;

			public function __construct( array $caps ) {
				$this->caps = $caps;
				// Don't call parent constructor to avoid database setup
			}

			public function getMeta( int $levelId, string $key, $default = null ) {
				if ( 'custom_capabilities' === $key ) {
					return $this->caps;
				}
				return $default;
			}
		};

		$ref  = new \ReflectionClass( $this->repository );
		$prop = $ref->getProperty( 'levels' );
		$prop->setAccessible( true );
		$prop->setValue( $this->repository, $levelsStub );
	}

	private function seedUserStub( int $userId ): void {
		global $khm_test_userdata;
		$khm_test_userdata[ $userId ] = new WPUserCapabilityStub( $userId );
	}

	private function getUserStub( int $userId ): WPUserCapabilityStub {
		global $khm_test_userdata;
		return $khm_test_userdata[ $userId ];
	}
}

class WPUserCapabilityStub extends \WP_User {
	public function __construct( int $id ) {
		parent::__construct( $id );
	}
}
