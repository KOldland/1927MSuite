<?php
/**
 * Database Idempotency Store
 *
 * Tracks processed webhook events in WordPress database to prevent duplicate processing.
 *
 * @package KHM\Services
 */

namespace KHM\Services;

use KHM\Contracts\IdempotencyStoreInterface;

class DatabaseIdempotencyStore implements IdempotencyStoreInterface {

    private string $tableName;

    public function __construct() {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'khm_webhook_events';
    }

    /**
     * Check if an event has already been processed.
     */
    public function hasProcessed( string $eventId ): bool {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a known safe, plugin-owned table string.
                "SELECT COUNT(*) FROM {$this->tableName} WHERE event_id = %s",
                $eventId
            )
        );

        return (int) $count > 0;
    }

    /**
     * Mark an event as processed.
     */
    public function markProcessed( string $eventId, string $gateway, array $metadata = [] ): void {
        global $wpdb;

        $wpdb->insert(
            $this->tableName,
            [
                'event_id' => $eventId,
                'gateway' => $gateway,
                'metadata' => wp_json_encode($metadata),
                'processed_at' => current_time('mysql', true),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
    }

    /**
     * Retrieve details of a processed event.
     */
    public function getProcessedEvent( string $eventId ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a known safe, plugin-owned table string.
                "SELECT * FROM {$this->tableName} WHERE event_id = %s LIMIT 1",
                $eventId
            ),
            ARRAY_A
        );

        if ( ! $row ) {
            return null;
        }

        // Decode metadata JSON
        $row['metadata'] = json_decode($row['metadata'], true) ?? [];

        return $row;
    }

    /**
     * Clean up old processed event records.
     */
    public function cleanup( int $daysOld = 90 ): int {
        global $wpdb;

        $cutoffDate = gmdate('Y-m-d H:i:s', strtotime("-{$daysOld} days"));

        $deleted = $wpdb->query(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a known safe, plugin-owned table string.
                "DELETE FROM {$this->tableName} WHERE processed_at < %s",
                $cutoffDate
            )
        );

        return (int) $deleted;
    }

    /**
     * Create the webhook events table.
     *
     * Should be called during plugin activation.
     */
    public static function createTable(): void {
        global $wpdb;
        $tableName = $wpdb->prefix . 'khm_webhook_events';
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_id varchar(255) NOT NULL,
            gateway varchar(50) NOT NULL,
            metadata text,
            processed_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_id (event_id),
            KEY gateway (gateway),
            KEY processed_at (processed_at)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
