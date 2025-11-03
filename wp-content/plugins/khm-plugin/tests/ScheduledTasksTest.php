<?php

declare(strict_types=1);

namespace KHM\Tests;

use KHM\Scheduled\Tasks;
use KHM\Services\MembershipRepository;
use KHM\Services\EmailService;
use Mockery;
use PHPUnit\Framework\TestCase;

final class ScheduledTasksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
    }

    protected function tearDown(): void
    {
        \khm_tests_set_current_time(null);
        Mockery::close();
        parent::tearDown();
    }

    private function stubWpDefaults(): void
    {
        add_filter('khm_run_daily_tasks', static fn($value) => true);

        update_option('khm_expiry_warning_days', 7);
        update_option('khm_email_from_address', 'admin@example.com');
        update_option('khm_email_from_name', 'Site');

        \khm_tests_set_current_time('2025-10-26 00:00:00');
    }

    public function test_run_daily_expires_and_warns_and_returns_counts(): void
    {
        $this->stubWpDefaults();

        // Two rows for expirations window (end_date <= now)
        $expiredRows = [
            (object) ['id' => 1, 'user_id' => 10, 'membership_id' => 2, 'end_date' => '2025-10-25 23:59:00'],
            (object) ['id' => 2, 'user_id' => 11, 'membership_id' => 3, 'end_date' => '2025-10-20 00:00:00'],
        ];

        // For warnings, we will return one row in the 7-day window
        $warningRows = [
            (object) ['id' => 3, 'user_id' => 12, 'membership_id' => 4, 'end_date' => '2025-11-01 12:00:00'],
        ];

        global $wpdb;
        $wpdb = new class($expiredRows, $warningRows) {
            public $prefix = 'wp_';
            private array $expired;
            private array $warn;
            private int $call = 0;
            public function __construct(array $expired, array $warn) {
                $this->expired = $expired;
                $this->warn    = $warn;
            }
            public function prepare($query, ...$args) {
                return $query;
            }
            public function get_results($query) {
                if (strpos($query, 'khm_membership_levelmeta') !== false) {
                    return [];
                }
                $this->call++;
                return $this->call === 1 ? $this->expired : $this->warn;
            }
            public function get_row($query) {
                return (object) ['id' => 2, 'name' => 'Test Level'];
            }
        };

        $repo = Mockery::mock(MembershipRepository::class);
        $repo->shouldReceive('expire')->with(10, 2)->once();
        $repo->shouldReceive('expire')->with(11, 3)->once();

        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('setFrom')->andReturnSelf()->atLeast()->once();
        $email->shouldReceive('setSubject')->andReturnSelf()->atLeast()->once();
        $email->shouldReceive('send')->times(3)->andReturn(true);

        $tasks  = new Tasks($repo, $email);
        $result = $tasks->run_daily();

        $this->assertSame(['expired' => 2, 'warned' => 1], $result);
    }

    public function test_send_expiration_warnings_dedupes_by_usermeta(): void
    {
        $this->stubWpDefaults();

        $rows = [ (object) ['id' => 9, 'user_id' => 21, 'membership_id' => 7, 'end_date' => '2025-11-01 09:00:00'] ];
        global $wpdb;
        $wpdb = new class($rows) {
            public $prefix = 'wp_';
            private array $rows;
            public function __construct(array $rows) { $this->rows = $rows; }
            public function prepare($query, ...$args) { return $query; }
            public function get_results($query) {
                if (strpos($query, 'khm_membership_levelmeta') !== false) {
                    return [];
                }
                return $this->rows;
            }
            public function get_row($query) { return (object) ['id' => 7, 'name' => 'Test Level']; }
        };

        update_user_meta(21, 'khm_notified_expiring_9', 'already-notified');

        $email = Mockery::mock(EmailService::class);
        $email->shouldReceive('setFrom')->andReturnSelf();
        $email->shouldReceive('setSubject')->andReturnSelf();
        $email->shouldReceive('send')->never();

        $repo = Mockery::mock(MembershipRepository::class);

        $tasks = new Tasks($repo, $email);
        $result = $tasks->send_expiration_warnings();

        $this->assertSame(0, $result);
    }
}
