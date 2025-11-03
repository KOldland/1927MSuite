<?php
/**
 * Order repository tests.
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use KHM\Services\OrderRepository;
use PHPUnit\Framework\TestCase;

class WPDBOrderStub
{
    public string $prefix = 'wp_';
    public int $insert_id = 0;
    /** @var array<int,array<string,mixed>> */
    public array $data = [];
    public string $users = 'wp_users';
    /** @var array<int,array<string,string>> */
    public array $userData = [];
    /** @var array<int,string> */
    public array $levelNames = [];

    public function insert( $table, $data ) {
        $this->insert_id++;
        $data['id'] = $this->insert_id;
        $this->data[ $this->insert_id ] = $data;
        return true;
    }

    public function update( $table, $data, $where, $format = null, $where_format = null ) {
        $id = $where['id'] ?? null;
        if ( ! $id ) {
            return false;
        }
        if ( ! isset( $this->data[ $id ] ) ) {
            $this->data[ $id ] = [];
        }
        $this->data[ $id ] = array_merge( $this->data[ $id ], $data );
        return true;
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

    public function get_row( $query, $output = OBJECT ) {
        if ( preg_match( '/FROM .*khm_membership_orders o/i', $query ) && preg_match( '/o\.id = (\d+)/', $query, $matches ) ) {
            $id = (int) $matches[1];
            if ( isset( $this->data[ $id ] ) ) {
                $row = $this->hydrate_row( $this->data[ $id ] );
                return ARRAY_A === $output ? $row : (object) $row;
            }
            return null;
        }

        if ( preg_match( '/WHERE id = (\d+)/', $query, $matches ) ) {
            $id = (int) $matches[1];
            if ( isset( $this->data[ $id ] ) ) {
                $row = $this->data[ $id ];
                return ARRAY_A === $output ? $row : (object) $row;
            }
            return null;
        }
        if ( preg_match( "#payment_transaction_id = '([^']+)'#", $query, $matches ) ) {
            $value = $matches[1];
            foreach ( $this->data as $row ) {
                if ( isset( $row['payment_transaction_id'] ) && $row['payment_transaction_id'] === $value ) {
                    return (object) $row;
                }
            }
        }
        if ( preg_match( "#subscription_transaction_id = '([^']+)'#", $query, $matches ) ) {
            $value = $matches[1];
            foreach ( $this->data as $row ) {
                if ( isset( $row['subscription_transaction_id'] ) && $row['subscription_transaction_id'] === $value ) {
                    return (object) $row;
                }
            }
        }
        if ( preg_match( "#code = '([^']+)'#", $query, $matches ) ) {
            $value = $matches[1];
            foreach ( $this->data as $row ) {
                if ( isset( $row['code'] ) && $row['code'] === $value ) {
                    return (object) $row;
                }
            }
        }
        return null;
    }

    public function query( $query ) {
        return 0;
    }

    public function get_results( $query, $output = OBJECT ) {
        if ( preg_match( '/FROM .*khm_membership_orders o/i', $query ) ) {
            $rows = [];
            foreach ( $this->data as $row ) {
                $rows[] = $this->hydrate_row( $row );
            }

            if ( preg_match( '/IN \(([^)]+)\)/', $query, $matches ) ) {
                $ids = array_map( 'intval', explode( ',', $matches[1] ) );
                $rows = array_values(
                    array_filter(
                        $rows,
                        static fn( $row ) => in_array( (int) $row['id'], $ids, true )
                    )
                );
            }

            if ( preg_match( '/LIMIT (\d+) OFFSET (\d+)/', $query, $matches ) ) {
                $limit  = (int) $matches[1];
                $offset = (int) $matches[2];
                $rows   = array_slice( $rows, $offset, $limit );
            }

            if ( ARRAY_A === $output ) {
                return $rows;
            }

            return array_map(
                static fn( $row ) => (object) $row,
                $rows
            );
        }

        if ( preg_match( '/WHERE id = (\d+)/', $query, $matches ) ) {
            $id = (int) $matches[1];
            if ( isset( $this->data[ $id ] ) ) {
                return [ (object) $this->data[ $id ] ];
            }
        }
        return [];
    }

    public function get_var( $query ) {
        if ( preg_match( '/COUNT\(\*\)/', $query ) ) {
            return count( $this->data );
        }
        return 0;
    }

    private function hydrate_row( array $row ): array {
        $user_id  = (int) ( $row['user_id'] ?? 0 );
        $level_id = (int) ( $row['membership_id'] ?? 0 );

        $user = $this->userData[ $user_id ] ?? [
            'user_login'   => '',
            'user_email'   => '',
            'display_name' => '',
        ];

        $row['user_login']   = $user['user_login'];
        $row['user_email']   = $user['user_email'];
        $row['display_name'] = $user['display_name'];
        $row['level_name']   = $this->levelNames[ $level_id ] ?? null;

        return $row;
    }
}

class OrderRepositoryTest extends TestCase
{
    private OrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        $this->setupTestDatabase();
        $this->repository = new OrderRepository();
    }

    public function testGenerateCodeReturnsString(): void
    {
        $code = $this->repository->generateCode();

        $this->assertIsString( $code );
        $this->assertEquals( 10, strlen( $code ) );
        $this->assertMatchesRegularExpression( '/^[A-Z0-9]+$/', $code );
    }

    public function testGenerateCodeReturnsUniqueValues(): void
    {
        $code1 = $this->repository->generateCode();
        $code2 = $this->repository->generateCode();

        $this->assertNotEquals( $code1, $code2 );
    }

    public function testCalculateTaxReturnsZeroWhenNoTaxConfigured(): void
    {
        $order = (object) [
            'subtotal'      => 100.00,
            'billing_state' => 'CA',
        ];

        $tax = $this->repository->calculateTax( $order );
        $this->assertEquals( 0.0, $tax );
    }

    public function testCalculateTaxAppliesRateWhenStateMatches(): void
    {
        update_option( 'khm_tax_state', 'CA' );
        update_option( 'khm_tax_rate', 0.0725 );

        $order = (object) [
            'subtotal'      => 100.00,
            'billing_state' => 'CA',
        ];

        $tax = $this->repository->calculateTax( $order );
        $this->assertEquals( 7.25, $tax );
    }

    public function testCalculateTaxReturnsZeroWhenStateDoesNotMatch(): void
    {
        update_option( 'khm_tax_state', 'CA' );
        update_option( 'khm_tax_rate', 0.0725 );

        $order = (object) [
            'subtotal'      => 100.00,
            'billing_state' => 'NY',
        ];

        $tax = $this->repository->calculateTax( $order );
        $this->assertEquals( 0.0, $tax );
    }

    public function testCreatePersistsFailureMetadata(): void
    {
        global $wpdb;

        $order = $this->repository->create( [
            'user_id'        => 12,
            'membership_id'  => 34,
            'total'          => 19.99,
            'gateway'        => 'stripe',
            'failure_code'   => 'card_declined',
            'failure_message'=> 'Card was declined',
            'failure_at'     => '2025-01-01 10:00:00',
        ] );

        $this->assertEquals( 'card_declined', $order->failure_code );
        $this->assertEquals( 'Card was declined', $order->failure_message );
        $this->assertNotEmpty( $order->failure_at );

        $stored = $wpdb->data[ $order->id ];
        $this->assertEquals( 'card_declined', $stored['failure_code'] );
        $this->assertEquals( 'Card was declined', $stored['failure_message'] );
        $this->assertEquals( '2025-01-01 10:00:00', $stored['failure_at'] );
    }

    public function testUpdatePersistsRefundMetadata(): void
    {
        global $wpdb;

        $order = $this->repository->create( [
            'user_id'       => 99,
            'membership_id' => 1,
            'total'         => 49.50,
            'gateway'       => 'stripe',
        ] );

        $this->repository->update( $order->id, [
            'status'        => 'refunded',
            'refund_amount' => 49.50,
            'refund_reason' => 'requested_by_customer',
            'refunded_at'   => '2025-02-02 12:00:00',
        ] );

        $updated = $wpdb->data[ $order->id ];
        $this->assertEquals( 'refunded', $updated['status'] );
        $this->assertEquals( 49.50, $updated['refund_amount'] );
        $this->assertEquals( 'requested_by_customer', $updated['refund_reason'] );
        $this->assertEquals( '2025-02-02 12:00:00', $updated['refunded_at'] );
    }

    public function testGetWithRelationsReturnsJoinedData(): void
    {
        $order = $this->repository->create( [
            'user_id'       => 10,
            'membership_id' => 3,
            'total'         => 10.00,
            'gateway'       => 'stripe',
            'code'          => 'ABC123',
        ] );

        $row = $this->repository->getWithRelations( $order->id );
        $this->assertNotNull( $row );
        $this->assertSame( 'member', $row['user_login'] );
        $this->assertSame( 'Gold', $row['level_name'] );
    }

    public function testPaginateReturnsItemsAndTotal(): void
    {
        $this->repository->create( [
            'user_id'       => 10,
            'membership_id' => 3,
            'total'         => 25.00,
            'gateway'       => 'stripe',
            'code'          => 'ORD001',
        ] );

        $result = $this->repository->paginate( [
            'per_page' => 10,
            'offset'   => 0,
        ] );

        $this->assertArrayHasKey( 'items', $result );
        $this->assertArrayHasKey( 'total', $result );
        $this->assertGreaterThanOrEqual( 1, $result['total'] );
        $this->assertNotEmpty( $result['items'] );
        $first = $result['items'][0];
        $this->assertSame( 'member', $first['user_login'] );
    }

    private function setupTestDatabase(): void
    {
        global $wpdb;
        $wpdb = new WPDBOrderStub();
        $wpdb->userData = [
            10 => [
                'user_login'   => 'member',
                'user_email'   => 'user@example.com',
                'display_name' => 'Test Member',
            ],
        ];
        $wpdb->levelNames = [
            3 => 'Gold',
        ];
    }
}
