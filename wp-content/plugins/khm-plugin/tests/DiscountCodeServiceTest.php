<?php
namespace KHM\Tests;

use KHM\Services\DiscountCodeService;
use PHPUnit\Framework\TestCase;

class WPDBDiscountStub {
	public string $prefix = 'wp_';
	public int $insert_id = 0;

	/** @var array<int,array<string,mixed>> */
	public array $codes = array();
	/** @var array<int,array<string,int>> */
	public array $levels = array();

	public function esc_like( $text ) {
		return addcslashes( $text, '_%\\' );
	}

	public function insert( $table, $data, $format = null ) {
		if ( $table === $this->prefix . 'khm_discount_codes' ) {
			$this->insert_id++;
			$data['id'] = $this->insert_id;
			$this->codes[ $data['id'] ] = $data;
			return true;
		}

		return false;
	}

	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		if ( $table !== $this->prefix . 'khm_discount_codes' ) {
			return false;
		}

		$id = $where['id'] ?? null;
		if ( ! $id || ! isset( $this->codes[ $id ] ) ) {
			return false;
		}

		$this->codes[ $id ] = array_merge( $this->codes[ $id ], $data );
		return true;
	}

	public function delete( $table, $where, $where_format = null ) {
		if ( $table === $this->prefix . 'khm_discount_codes_levels' ) {
			if ( isset( $where['discount_code_id'] ) ) {
				$code_id      = (int) $where['discount_code_id'];
				$this->levels = array_values(
					array_filter(
						$this->levels,
						static fn( $row ) => (int) $row['discount_code_id'] !== $code_id
					)
				);
				return true;
			}
		}

		if ( $table === $this->prefix . 'khm_discount_codes' ) {
			if ( isset( $where['id'] ) && isset( $this->codes[ $where['id'] ] ) ) {
				unset( $this->codes[ $where['id'] ] );
				return true;
			}
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
		if ( preg_match( '#FROM ' . $this->prefix . 'khm_discount_codes WHERE id = (\d+)#', $query, $matches ) ) {
			$id = (int) $matches[1];
			return isset( $this->codes[ $id ] ) ? (object) $this->codes[ $id ] : null;
		}

		if ( preg_match( "#FROM {$this->prefix}khm_discount_codes WHERE code = '([^']+)'#", $query, $matches ) ) {
			$code = $matches[1];
			foreach ( $this->codes as $row ) {
				if ( isset( $row['code'] ) && $row['code'] === $code ) {
					return (object) $row;
				}
			}
		}

		return null;
	}

	public function get_results( $query, $output = OBJECT ) {
		if ( false !== strpos( $query, 'FROM ' . $this->prefix . 'khm_discount_codes_levels' ) ) {
			if ( preg_match( '/IN \((.+)\)/', $query, $matches ) ) {
				$ids = array_map( 'intval', explode( ',', $matches[1] ) );
				$rows = array_filter(
					$this->levels,
					static fn( $row ) => in_array( (int) $row['discount_code_id'], $ids, true )
				);
			} else {
				$rows = $this->levels;
			}

			return array_map(
				static fn( $row ) => (object) $row,
				$rows
			);
		}

		if ( false !== strpos( $query, 'FROM ' . $this->prefix . 'khm_discount_codes' ) ) {
			$rows = $this->filter_codes_for_query( $query );

			usort(
				$rows,
				static function ( $a, $b ) {
					$time_a = $a['created_at'] ?? '';
					$time_b = $b['created_at'] ?? '';
					return strcmp( $time_b, $time_a );
				}
			);

			if ( preg_match( '/LIMIT\s+(\d+)\s+OFFSET\s+(\d+)/i', $query, $limit_matches ) ) {
				$limit  = (int) $limit_matches[1];
				$offset = (int) $limit_matches[2];
				$rows   = array_slice( $rows, $offset, $limit );
			}

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

		return array();
	}

	public function get_var( $query ) {
		if ( false !== strpos( $query, 'FROM ' . $this->prefix . 'khm_discount_codes' ) ) {
			$rows = $this->filter_codes_for_query( $query );
			return count( $rows );
		}

		return null;
	}

	public function query( $query ) {
		$trimmed = ltrim( $query );
		if ( 0 === strpos( $trimmed, 'START TRANSACTION' ) || 0 === strpos( $trimmed, 'COMMIT' ) || 0 === strpos( $trimmed, 'ROLLBACK' ) ) {
			return true;
		}

		if ( 0 === strpos( $trimmed, 'INSERT INTO ' . $this->prefix . 'khm_discount_codes_levels' ) ) {
			preg_match_all( '/\((\d+),\s*(\d+)\)/', $query, $matches, PREG_SET_ORDER );
			foreach ( $matches as $match ) {
				$this->levels[] = array(
					'discount_code_id' => (int) $match[1],
					'level_id'         => (int) $match[2],
				);
			}
			return count( $matches );
		}

		return true;
	}

	/**
	 * Filter stored codes based on simple WHERE conditions in the query.
	 *
	 * @param string $query SQL-like string.
	 * @return array<int,array<string,mixed>>
	 */
	private function filter_codes_for_query( string $query ): array {
		$rows = array_values( $this->codes );

		if ( preg_match( "/status\s*=\s*'([^']+)'/i", $query, $status_match ) ) {
			$status = $status_match[1];
			$rows   = array_filter(
				$rows,
				static fn( $row ) => isset( $row['status'] ) && $row['status'] === $status
			);
		}

		if ( preg_match( "/code\s+LIKE\s+'([^']+)'/i", $query, $like_match ) ) {
			$needle = str_replace( array( '%', '\\' ), '', $like_match[1] );
			$needle = strtolower( $needle );
			$rows   = array_filter(
				$rows,
				static fn( $row ) => isset( $row['code'] ) && false !== strpos( strtolower( $row['code'] ), $needle )
			);
		}

		return array_values( $rows );
	}
}

class DiscountCodeServiceTest extends TestCase {
	private DiscountCodeService $service;

	protected function setUp(): void {
		parent::setUp();
		\khm_tests_reset_environment();
		$this->setup_database();
		$this->service = new DiscountCodeService();
	}

	public function testCreateCodePersistsLevels(): void {
		$code = $this->service->create_code(
			array(
				'code'      => 'SAVE10',
				'type'      => 'amount',
				'value'     => 10.00,
				'status'    => 'active',
				'level_ids' => array( 1, 2 ),
			)
		);

		$this->assertNotNull( $code );
		$this->assertSame( array( 1, 2 ), $code->level_ids );

		global $wpdb;
		$this->assertArrayHasKey( $code->id, $wpdb->codes );
		$this->assertSame( '1,2', $wpdb->codes[ $code->id ]['levels'] );

		$level_rows = array_filter(
			$wpdb->levels,
			static fn( $row ) => (int) $row['discount_code_id'] === (int) $code->id
		);
		$this->assertCount( 2, $level_rows );
	}

	public function testUpdateCodeReplacesLevelMappings(): void {
		$code = $this->service->create_code(
			array(
				'code'      => 'SAVE20',
				'type'      => 'percent',
				'value'     => 20.0,
				'status'    => 'active',
				'level_ids' => array( 1, 4 ),
			)
		);

		$result = $this->service->update_code(
			$code->id,
			array(
				'status'    => 'inactive',
				'level_ids' => array( 5 ),
			)
		);

		$this->assertTrue( $result );

		$updated = $this->service->get_code( $code->id );
		$this->assertSame( array( 5 ), $updated->level_ids );
		$this->assertSame( 'inactive', $updated->status );

		global $wpdb;
		$this->assertSame( '5', $wpdb->codes[ $code->id ]['levels'] );

		$level_rows = array_filter(
			$wpdb->levels,
			static fn( $row ) => (int) $row['discount_code_id'] === (int) $code->id
		);
		$this->assertCount( 1, $level_rows );
		$level_row = array_shift( $level_rows );
		$this->assertSame( 5, $level_row['level_id'] );
	}

	public function testDeleteCodeRemovesMappings(): void {
		$code = $this->service->create_code(
			array(
				'code'      => 'REMOVE',
				'type'      => 'amount',
				'value'     => 5.0,
				'status'    => 'active',
				'level_ids' => array( 3 ),
			)
		);

		$deleted = $this->service->delete_code( $code->id );
		$this->assertTrue( $deleted );

		$this->assertNull( $this->service->get_code( $code->id ) );

		global $wpdb;
		$this->assertArrayNotHasKey( $code->id, $wpdb->codes );
		$level_rows = array_filter(
			$wpdb->levels,
			static fn( $row ) => (int) $row['discount_code_id'] === (int) $code->id
		);
		$this->assertCount( 0, $level_rows );
	}

	public function testPaginateCodesSupportsSearchStatusAndLimit(): void {
		\khm_tests_set_current_time( '2025-01-01 00:00:00' );
		$this->service->create_code(
			array(
				'code'      => 'ALPHA',
				'type'      => 'amount',
				'value'     => 5.0,
				'status'    => 'active',
				'level_ids' => array(),
			)
		);

		\khm_tests_set_current_time( '2025-01-02 00:00:00' );
		$this->service->create_code(
			array(
				'code'      => 'BETA',
				'type'      => 'amount',
				'value'     => 7.5,
				'status'    => 'inactive',
				'level_ids' => array( 2 ),
			)
		);

		\khm_tests_set_current_time( '2025-01-03 00:00:00' );
		$this->service->create_code(
			array(
				'code'      => 'BETA20',
				'type'      => 'percent',
				'value'     => 20,
				'status'    => 'inactive',
				'level_ids' => array( 3 ),
			)
		);

		$page_one = $this->service->paginate_codes(
			array(
				'search' => 'BETA',
				'status' => 'inactive',
				'limit'  => 1,
				'offset' => 0,
			)
		);

		$this->assertSame( 2, $page_one['total'] );
		$this->assertCount( 1, $page_one['items'] );
		$this->assertSame( 'BETA20', $page_one['items'][0]->code );

		$page_two = $this->service->paginate_codes(
			array(
				'search' => 'BETA',
				'status' => 'inactive',
				'limit'  => 1,
				'offset' => 1,
			)
		);

		$this->assertCount( 1, $page_two['items'] );
		$this->assertSame( 'BETA', $page_two['items'][0]->code );
	}

	private function setup_database(): void {
		global $wpdb;
		$wpdb = new WPDBDiscountStub();
	}
}
