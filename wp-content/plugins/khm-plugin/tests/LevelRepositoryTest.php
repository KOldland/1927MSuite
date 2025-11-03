<?php
namespace KHM\Tests;

use KHM\Services\LevelRepository;
use PHPUnit\Framework\TestCase;

class WPDBLevelStub {
	public string $prefix = 'wp_';
	public int $insert_id = 0;

	/** @var array<int,array<string,mixed>> */
	public array $levels = [];

	public function insert( $table, $data, $format = null ) {
		if ( $table === $this->prefix . 'khm_membership_levels' ) {
			$this->insert_id++;
			$data['id'] = $this->insert_id;
			$this->levels[ $this->insert_id ] = $data;
			return true;
		}

		return false;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( $table === $this->prefix . 'khm_membership_levelmeta' ) {
			return true;
		}

		if ( $table !== $this->prefix . 'khm_membership_levels' ) {
			return false;
		}

		$id = $where['id'] ?? null;
		if ( ! $id || ! isset( $this->levels[ $id ] ) ) {
			return false;
		}

		$this->levels[ $id ] = array_merge( $this->levels[ $id ], $data );
		return true;
	}

	public function delete( $table, $where, $where_format = null ) {
		if ( $table === $this->prefix . 'khm_membership_levels' ) {
			$id = $where['id'] ?? null;
			if ( $id && isset( $this->levels[ $id ] ) ) {
				unset( $this->levels[ $id ] );
				return true;
			}
		} elseif ( $table === $this->prefix . 'khm_membership_levelmeta' ) {
			return true;
		}

		return false;
	}

	public function prepare( $query, ...$args ) {
		if ( count( $args ) === 1 && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		foreach ( $args as $arg ) {
			if ( false !== ( $pos = strpos( $query, '%s' ) ) ) {
				$safe  = "'" . addslashes( (string) $arg ) . "'";
				$query = substr_replace( $query, $safe, $pos, 2 );
				continue;
			}
			if ( false !== ( $pos = strpos( $query, '%d' ) ) ) {
				$query = substr_replace( $query, (string) (int) $arg, $pos, 2 );
			}
		}

		return $query;
	}

	public function get_row( $query ) {
		if ( preg_match( '#FROM ' . $this->prefix . 'khm_membership_levels WHERE id = (\d+)#', $query, $matches ) ) {
			$id = (int) $matches[1];
			return isset( $this->levels[ $id ] ) ? (object) $this->levels[ $id ] : null;
		}

		return null;
	}

	public function get_results( $query, $output = OBJECT ) {
		if ( false !== strpos( $query, 'FROM ' . $this->prefix . 'khm_membership_levels' ) ) {
			$rows = array_values( $this->levels );
			usort(
				$rows,
				static function ( $a, $b ) {
					return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
				}
			);

			if ( ARRAY_A === $output ) {
				return array_map(
					static fn( $row ) => $row,
					$rows
				);
			}

			return array_map(
				static fn( $row ) => (object) $row,
				$rows
			);
		}

		return [];
	}

	public function get_var( $query ) {
		return null;
	}
}

class LevelRepositoryTest extends TestCase {
	private LevelRepository $repository;

	protected function setUp(): void {
		parent::setUp();
		\khm_tests_reset_environment();
		$this->setup_database();
		$this->repository = new LevelRepository();
	}

	public function testCreatePersistsLevel(): void {
		$level = $this->repository->create(
			[
				'name'            => 'Gold',
				'description'     => 'Gold membership',
				'confirmation'    => 'Thanks!',
				'initial_payment' => 99.99,
				'billing_amount'  => 19.99,
				'cycle_number'    => 1,
				'cycle_period'    => 'Month',
			]
		);

		$this->assertNotNull( $level );
		$this->assertSame( 'Gold', $level->name );

		global $wpdb;
		$this->assertArrayHasKey( $level->id, $wpdb->levels );
		$this->assertSame( 'Gold', $wpdb->levels[ $level->id ]['name'] );
	}

	public function testUpdatePersistsChanges(): void {
		$level = $this->repository->create(
			[
				'name'            => 'Silver',
				'description'     => 'Silver membership',
				'initial_payment' => 49.00,
			]
		);

		$this->assertNotNull( $level );

		$result = $this->repository->update(
			$level->id,
			[
				'name'           => 'Silver Plus',
				'billing_amount' => 10.00,
				'cycle_number'   => 1,
				'cycle_period'   => 'Month',
			]
		);

		$this->assertTrue( $result );

		$updated = $this->repository->get( $level->id );
		$this->assertNotNull( $updated );
		$this->assertSame( 'Silver Plus', $updated->name );
		$this->assertEquals( 10.0, (float) $updated->billing_amount );
	}

	public function testDeleteRemovesLevel(): void {
		$level = $this->repository->create(
			[
				'name'        => 'Bronze',
				'description' => 'Bronze membership',
			]
		);

		$this->assertNotNull( $level );

		$result = $this->repository->delete( $level->id );
		$this->assertTrue( $result );
		$this->assertNull( $this->repository->get( $level->id ) );
	}

	private function setup_database(): void {
		global $wpdb;
		$wpdb = new WPDBLevelStub();
	}
}
