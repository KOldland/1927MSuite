<?php
use KH\XAPI\Services\LearningDataService;

class LearningDataServiceTest extends WP_UnitTestCase {
    private $table;

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->table = $wpdb->prefix . 'kh_xapi_completions';
        $wpdb->query( "TRUNCATE TABLE {$this->table}" );
    }

    public function test_aggregate_by_content_returns_counts() {
        global $wpdb;

        $wpdb->insert( $this->table, [
            'content_id'  => 42,
            'user_id'     => 1,
            'status'      => 'completed',
            'percentage'  => 100,
            'score'       => 90,
            'timespent'   => 600,
            'statement'   => '{}',
            'registration'=> 'abc-123',
        ] );

        $wpdb->insert( $this->table, [
            'content_id'  => 42,
            'user_id'     => 2,
            'status'      => 'in-progress',
            'percentage'  => 20,
            'score'       => 10,
            'timespent'   => 200,
            'statement'   => '{}',
            'registration'=> 'def-456',
        ] );

        $service = new LearningDataService();
        $result  = $service->aggregate_by( 'content', [] );

        $this->assertEquals( [ 'content_id', 'total', 'completed', 'avg_percent' ], $result['headers'] );
        $this->assertCount( 1, $result['rows'] );
        $row = $result['rows'][0];
        $this->assertEquals( 42, (int) $row['content_id'] );
        $this->assertEquals( 2, (int) $row['total'] );
        $this->assertEquals( 1, (int) $row['completed'] );
        $this->assertGreaterThan( 50, (float) $row['avg_percent'] );
    }
}
