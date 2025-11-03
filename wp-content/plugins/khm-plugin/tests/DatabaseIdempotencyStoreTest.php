<?php
/**
 * Database idempotency store tests.
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use KHM\Services\DatabaseIdempotencyStore;
use PHPUnit\Framework\TestCase;

class WPDBIdempotencyStub
{
    public string $prefix = 'wp_';
    private array $rows = [];
    private int $autoInc = 1;

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

    public function get_var( $query ) {
        if ( preg_match( "#WHERE event_id = '([^']+)'#", $query, $matches ) ) {
            $eventId = $matches[1];
            foreach ( $this->rows as $row ) {
                if ( $row['event_id'] === $eventId ) {
                    return 1;
                }
            }
            return 0;
        }
        return 0;
    }

    public function insert( $table, $data, $formats ) {
        $data['id'] = $this->autoInc++;
        $this->rows[] = $data;
        return true;
    }

    public function get_row( $query, $output = OBJECT ) {
        if ( preg_match( "#WHERE event_id = '([^']+)'#", $query, $matches ) ) {
            $eventId = $matches[1];
            foreach ( $this->rows as $row ) {
                if ( $row['event_id'] === $eventId ) {
                    return $output === ARRAY_A ? $row : (object) $row;
                }
            }
        }
        return null;
    }

    public function query( $query ) {
        if ( preg_match( "#processed_at < '([^']+)'#", $query, $matches ) ) {
            $cutoff = $matches[1];
            $before = count( $this->rows );
            $this->rows = array_values( array_filter(
                $this->rows,
                static fn( $row ) => $row['processed_at'] >= $cutoff
            ) );
            return $before - count( $this->rows );
        }
        return 0;
    }
}

class DatabaseIdempotencyStoreTest extends TestCase
{
    private DatabaseIdempotencyStore $store;

    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        $this->setupTestDatabase();
        $this->store = new DatabaseIdempotencyStore();
    }

    public function testHasProcessedReturnsFalseForNewEvent(): void
    {
        $result = $this->store->hasProcessed( 'evt_new_12345' );
        $this->assertFalse( $result );
    }

    public function testMarkProcessedStoresEvent(): void
    {
        $eventId = 'evt_test_12345';
        $gateway = 'stripe';
        $metadata = ['type' => 'charge.succeeded'];

        $this->store->markProcessed( $eventId, $gateway, $metadata );

        $this->assertTrue( $this->store->hasProcessed( $eventId ) );
    }

    public function testGetProcessedEventReturnsEventDetails(): void
    {
        $eventId = 'evt_test_67890';
        $gateway = 'stripe';
        $metadata = ['type' => 'customer.subscription.created'];

        $this->store->markProcessed( $eventId, $gateway, $metadata );

        $event = $this->store->getProcessedEvent( $eventId );

        $this->assertIsArray( $event );
        $this->assertEquals( $eventId, $event['event_id'] );
        $this->assertEquals( $gateway, $event['gateway'] );
        $this->assertEquals( $metadata, $event['metadata'] );
    }

    public function testGetProcessedEventReturnsNullForUnknownEvent(): void
    {
        $event = $this->store->getProcessedEvent( 'evt_unknown_12345' );
        $this->assertNull( $event );
    }

    public function testCleanupRemovesOldEvents(): void
    {
        \khm_tests_set_current_time( '2020-01-01 00:00:00' );
        $this->store->markProcessed( 'evt_old', 'stripe', [] );

        \khm_tests_set_current_time( '2025-01-01 00:00:00' );
        $deleted = $this->store->cleanup( 90 );

        \khm_tests_set_current_time( null );
        $this->assertEquals( 1, $deleted );
    }

    private function setupTestDatabase(): void
    {
        global $wpdb;
        $wpdb = new WPDBIdempotencyStub();
    }
}
