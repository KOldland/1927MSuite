<?php

namespace {
    if (!isset($GLOBALS['wp_options'])) {
        $GLOBALS['wp_options'] = [];
    }

    if (!function_exists('update_option')) {
        function update_option($name, $value) {
            $GLOBALS['wp_options'][$name] = $value;
        }
    }

    if (!function_exists('get_option')) {
        function get_option($name, $default = '') {
            if ($name === 'khm_stripe_webhook_secret' && !isset($GLOBALS['wp_options'][$name])) {
                return 'whsec_test';
            }
            return $GLOBALS['wp_options'][$name] ?? $default;
        }
    }

    if (!class_exists('WP_Error')) {
        class WP_Error extends \Exception {
            public $data;

            public function __construct($code = '', $message = '', $data = []) {
                parent::__construct($message);
                $this->data = $data;
            }
        }
    }

    if (!class_exists('WP_REST_Response')) {
        class WP_REST_Response {
            public $data;
            public $status;

            public function __construct($data, $status = 200) {
                $this->data   = $data;
                $this->status = $status;
            }
        }
    }

    if (!function_exists('do_action')) {
        function do_action($hook, ...$args) {
            // no-op for unit tests
        }
    }

    if (!function_exists('apply_filters')) {
        function apply_filters($tag, $value) {
            return $value;
        }
    }

    if (!function_exists('current_time')) {
        function current_time($type, $gmt = 0) {
            $ts = time();
            if ('mysql' === $type) {
                return gmdate('Y-m-d H:i:s', $ts);
            }
            return $ts;
        }
    }
}

namespace KHM\Tests {

use KHM\Contracts\IdempotencyStoreInterface;
use KHM\Contracts\MembershipRepositoryInterface;
use KHM\Contracts\OrderRepositoryInterface;
use KHM\Contracts\WebhookVerifierInterface;
use KHM\Rest\WebhooksController;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Response;

class FakeRequest
{
    private string $body;
    private array $headers;

    public function __construct(string $body, array $headers = [])
    {
        $this->body    = $body;
        $this->headers = array_change_key_case($headers, CASE_LOWER);
    }

    public function get_body(): string
    {
        return $this->body;
    }

    public function get_header(string $name)
    {
        $lname = strtolower($name);
        return $this->headers[$lname] ?? null;
    }
}

class StubVerifier implements WebhookVerifierInterface
{
    public bool $shouldVerify = true;
    public object $event;

    public function __construct()
    {
        $this->event = (object) [
            'id'   => 'evt_test_123',
            'type' => 'invoice.payment_succeeded',
            'data' => (object) [],
        ];
    }

    public function verify(string $payload, array $headers, string $secret): bool
    {
        return $this->shouldVerify;
    }

    public function parseEvent(string $payload): object
    {
        return $this->event;
    }

    public function getEventId(object $event): string
    {
        return $event->id;
    }

    public function getEventType(object $event): string
    {
        return $event->type;
    }
}

class StubIdempotency implements IdempotencyStoreInterface
{
    public array $marked = [];
    public array $existing = [];

    public function hasProcessed(string $eventId): bool
    {
        return in_array($eventId, $this->existing, true);
    }

    public function markProcessed(string $eventId, string $gateway, array $metadata = []): void
    {
        $this->marked[] = [$eventId, $gateway, $metadata];
    }

    public function getProcessedEvent(string $eventId): ?array
    {
        return null;
    }

    public function cleanup(int $daysOld = 90): int
    {
        return 0;
    }
}

class StubOrders implements OrderRepositoryInterface
{
    public array $created = [];
    public array $updated = [];
    public array $statusLog = [];
    public array $store = [];
    private int $nextId = 1;
    public array $notes = [];

    public function create(array $data): object
    {
        $id = $this->nextId++;
        $record = (object) array_merge(['id' => $id], $data);
        $this->created[]       = $data;
        $this->store[$id] = $record;
        return $record;
    }

    public function update(int $orderId, array $data): object
    {
        $existing = $this->store[$orderId] ?? (object) ['id' => $orderId];
        foreach ($data as $key => $value) {
            $existing->$key = $value;
        }
        $this->store[$orderId] = $existing;
        $this->updated[]       = [$orderId, $data];
        return $existing;
    }

    public function find(int $orderId): ?object
    {
        return $this->store[$orderId] ?? null;
    }

    public function findByCode(string $code): ?object
    {
        foreach ($this->store as $order) {
            if (isset($order->code) && $order->code === $code) {
                return $order;
            }
        }
        return null;
    }

    public function findByPaymentTransactionId(string $txnId): ?object
    {
        foreach ($this->store as $order) {
            if (isset($order->payment_transaction_id) && $order->payment_transaction_id === $txnId) {
                return $order;
            }
        }
        return null;
    }

    public function findLastBySubscriptionId(string $subscriptionId): ?object
    {
        $matches = array_filter(
            $this->store,
            static fn($order) => isset($order->subscription_transaction_id) && $order->subscription_transaction_id === $subscriptionId
        );

        if (empty($matches)) {
            return null;
        }

        usort($matches, static fn($a, $b) => $b->id <=> $a->id);

        return $matches[0] ?? null;
    }

    public function findByUser(int $userId, array $filters = []): array
    {
        return array_values(
            array_filter(
                $this->store,
                static function ($order) use ($userId, $filters) {
                    if (!isset($order->user_id) || (int) $order->user_id !== $userId) {
                        return false;
                    }
                    if (!empty($filters['membership_id']) && (int) ($order->membership_id ?? 0) !== (int) $filters['membership_id']) {
                        return false;
                    }
                    return true;
                }
            )
        );
    }

    public function getWithRelations( int $orderId ): ?array
    {
        $order = $this->store[ $orderId ] ?? null;
        if ( ! $order ) {
            return null;
        }

        return [
            'id'           => $order->id,
            'user_id'      => $order->user_id ?? 0,
            'membership_id'=> $order->membership_id ?? 0,
            'user_email'   => $order->user_email ?? '',
            'user_login'   => $order->user_login ?? '',
            'display_name' => $order->display_name ?? '',
            'level_name'   => $order->level_name ?? '',
            'total'        => $order->total ?? 0,
            'gateway'      => $order->gateway ?? '',
            'payment_transaction_id' => $order->payment_transaction_id ?? '',
            'notes'        => $order->notes ?? '',
        ];
    }

    public function getManyWithRelations( array $ids ): array
    {
        $rows = [];
        foreach ( $ids as $id ) {
            $row = $this->getWithRelations( (int) $id );
            if ( $row ) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    public function paginate( array $args = [] ): array
    {
        $items = array_map(
            fn( $order ) => (array) $order,
            array_values( $this->store )
        );

        return [
            'items' => $items,
            'total' => count( $items ),
        ];
    }

    public function updateStatus(int $orderId, string $status, string $notes = ''): bool
    {
        $this->statusLog[] = [$orderId, $status, $notes];
        if (isset($this->store[$orderId])) {
            $this->store[$orderId]->status = $status;
            if ($notes) {
                $this->store[$orderId]->notes = $notes;
            }
        }
        return true;
    }

    public function updateNotes( int $orderId, string $notes ): bool
    {
        if ( isset( $this->store[ $orderId ] ) ) {
            $this->store[ $orderId ]->notes = $notes;
        }
        $this->notes[ $orderId ] = $notes;
        return true;
    }

    public function recordRefund( int $orderId, float $amount, string $reason = '', ?string $refundedAt = null ): bool
    {
        if ( isset( $this->store[ $orderId ] ) ) {
            $this->store[ $orderId ]->status = 'refunded';
            $this->store[ $orderId ]->refund_amount = $amount;
            $this->store[ $orderId ]->refund_reason = $reason;
        }
        do_action( 'khm_order_refund_recorded', $orderId, $amount, $reason, $this->getWithRelations( $orderId ) ?? [] );
        return true;
    }

    public function delete(int $orderId): bool
    {
        unset($this->store[$orderId]);
        return true;
    }

    public function generateCode(): string
    {
        return 'TESTCODE';
    }

    public function calculateTax(object $order): float
    {
        return 0.0;
    }
}

class StubMemberships implements MembershipRepositoryInterface
{
    public array $assigned = [];
    public array $cancelled = [];
    public array $expired = [];
    public array $pastDue = [];
    public array $statusChanges = [];
    public array $billingUpdates = [];
    private array $store = [];

    private function key(int $userId, int $levelId): string
    {
        return $userId . ':' . $levelId;
    }

    private function ensureMembership(int $userId, int $levelId): object
    {
        $key = $this->key($userId, $levelId);
        if (!isset($this->store[$key])) {
            $this->store[$key] = (object) [
                'user_id'       => $userId,
                'membership_id' => $levelId,
                'status'        => 'active',
            ];
        }
        return $this->store[$key];
    }

    public function assign(int $userId, int $levelId, array $options = []): object
    {
        $membership = $this->ensureMembership($userId, $levelId);
        if (isset($options['status'])) {
            $membership->status = $options['status'];
        }
        $this->store[$this->key($userId, $levelId)] = $membership;
        $this->assigned[] = [$userId, $levelId, $options];
        return $membership;
    }

    public function cancel(int $userId, int $levelId, string $reason = ''): bool
    {
        $membership = $this->ensureMembership($userId, $levelId);
        $membership->status = 'cancelled';
        $this->store[$this->key($userId, $levelId)] = $membership;
        $this->cancelled[] = [$userId, $levelId, $reason];
        return true;
    }

    public function expire(int $userId, int $levelId): bool
    {
        $membership = $this->ensureMembership($userId, $levelId);
        $membership->status = 'expired';
        $this->store[$this->key($userId, $levelId)] = $membership;
        $this->expired[] = [$userId, $levelId];
        return true;
    }

    public function findActive(int $userId): array
    {
        return array_values(
            array_filter(
                $this->store,
                static fn($membership) => $membership->user_id === $userId && $membership->status === 'active'
            )
        );
    }

    public function findByLevel(int $levelId, array $filters = []): array
    {
        return array_values(
            array_filter(
                $this->store,
                static fn($membership) => $membership->membership_id === $levelId
            )
        );
    }

    public function findExpiring(int $days = 7): array
    {
        return [];
    }

    public function hasAccess(int $userId, int $levelId): bool
    {
        $membership = $this->find($userId, $levelId);
        return $membership ? $membership->status === 'active' : false;
    }

    public function updateEndDate(int $userId, int $levelId, ?\DateTime $endDate): bool
    {
        $membership = $this->ensureMembership($userId, $levelId);
        $membership->enddate = $endDate ? $endDate->format('Y-m-d H:i:s') : null;
        $this->store[$this->key($userId, $levelId)] = $membership;
        return true;
    }

    public function find(int $userId, int $levelId): ?object
    {
        return $this->store[$this->key($userId, $levelId)] ?? null;
    }

    public function markPastDue(int $userId, int $levelId, string $reason = ''): bool
    {
        $membership = $this->ensureMembership($userId, $levelId);
        $membership->status = 'past_due';
        $this->store[$this->key($userId, $levelId)] = $membership;
        $this->pastDue[] = [$userId, $levelId, $reason];
        return true;
    }

    public function updateBillingProfile(int $userId, int $levelId, array $attributes = []): bool
    {
        $membership = $this->ensureMembership($userId, $levelId);
        foreach ($attributes as $key => $value) {
            $membership->$key = $value;
        }
        $this->store[$this->key($userId, $levelId)] = $membership;
        $this->billingUpdates[] = [$userId, $levelId, $attributes];
        return true;
    }

    public function setStatus(int $userId, int $levelId, string $status, string $reason = ''): bool
    {
        $membership = $this->ensureMembership($userId, $levelId);
        $membership->status = $status;
        $this->store[$this->key($userId, $levelId)] = $membership;
        $this->statusChanges[] = [$userId, $levelId, $status, $reason];
        return true;
    }
}

final class WebhooksControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['wp_options']['khm_stripe_webhook_secret'] = 'whsec_test';
    }

    public function test_invalid_signature_returns_error(): void
    {
        $verifier = new StubVerifier();
        $verifier->shouldVerify = false;
        $idempo = new StubIdempotency();
        $orders = new StubOrders();
        $members = new StubMemberships();

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $req = new FakeRequest('{"id":"x","type":"t"}', ['Stripe-Signature' => 'sig']);

        $res = $controller->handle_stripe($req);

        $this->assertInstanceOf(WP_Error::class, $res);
        $this->assertEquals(400, $res->data['status']);
    }

    public function test_duplicate_event_short_circuits(): void
    {
        $verifier = new StubVerifier();
        $idempo = new StubIdempotency();
        $idempo->existing[] = 'evt_test_123';
        $orders = new StubOrders();
        $members = new StubMemberships();

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $req = new FakeRequest('{"id":"evt_test_123"}', ['Stripe-Signature' => 'sig']);

        $res = $controller->handle_stripe($req);

        $this->assertInstanceOf(WP_REST_Response::class, $res);
        $this->assertEquals('duplicate', $res->data['status']);
        $this->assertEquals(200, $res->status);
    }

    public function test_happy_path_marks_processed(): void
    {
        $verifier = new StubVerifier();
        $idempo = new StubIdempotency();
        $orders = new StubOrders();
        $members = new StubMemberships();

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $req = new FakeRequest('{"id":"evt_test_123"}', ['Stripe-Signature' => 'sig']);

        $res = $controller->handle_stripe($req);

        $this->assertInstanceOf(WP_REST_Response::class, $res);
        $this->assertEquals('processed', $res->data['status']);
        $this->assertEquals(200, $res->status);
        $this->assertCount(1, $idempo->marked);
        $this->assertEquals(['evt_test_123', 'stripe', ['type' => 'invoice.payment_succeeded']], $idempo->marked[0]);
    }

    public function test_invoice_payment_failed_marks_membership_past_due(): void
    {
        $verifier = new StubVerifier();
        $invoice = (object) [
            'id' => 'in_001',
            'amount_due' => 4999,
            'subscription' => 'sub_001',
            'metadata' => (object) ['user_id' => 42, 'membership_id' => 7],
            'last_payment_error' => (object) ['code' => 'card_declined', 'message' => 'Card declined'],
        ];
        $verifier->event = (object) [
            'id' => 'evt_fail',
            'type' => 'invoice.payment_failed',
            'data' => (object) ['object' => $invoice],
        ];

        $idempo = new StubIdempotency();
        $orders = new StubOrders();
        $members = new StubMemberships();

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $res = $controller->handle_stripe(new FakeRequest('{}', ['Stripe-Signature' => 'sig']));

        $this->assertInstanceOf(WP_REST_Response::class, $res);
        $order = $orders->findByPaymentTransactionId('in_001');
        $this->assertNotNull($order);
        $this->assertEquals('failed', $order->status);
        $this->assertEquals('card_declined', $order->failure_code);
        $this->assertEquals(49.99, $order->total);

        $this->assertCount(1, $members->pastDue);
        $this->assertSame([42, 7, 'Stripe invoice payment failed'], $members->pastDue[0]);
    }

    public function test_charge_failed_creates_failed_order(): void
    {
        $verifier = new StubVerifier();
        $charge = (object) [
            'id' => 'ch_001',
            'amount' => 5999,
            'subscription' => 'sub_ABC',
            'failure_message' => 'Insufficient funds',
            'metadata' => (object) ['user_id' => 101, 'membership_id' => 8],
        ];
        $verifier->event = (object) [
            'id' => 'evt_charge_failed',
            'type' => 'charge.failed',
            'data' => (object) ['object' => $charge],
        ];

        $idempo = new StubIdempotency();
        $orders = new StubOrders();
        $members = new StubMemberships();

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $res = $controller->handle_stripe(new FakeRequest('{}', ['Stripe-Signature' => 'sig']));

        $this->assertInstanceOf(WP_REST_Response::class, $res);

        $order = $orders->findByPaymentTransactionId('ch_001');
        $this->assertNotNull($order);
        $this->assertEquals('failed', $order->status);
        $this->assertEquals('Insufficient funds', $order->failure_message);
        $this->assertEquals(59.99, $order->total);

        $this->assertCount(1, $members->pastDue);
        $this->assertSame([101, 8, 'Stripe charge failed'], $members->pastDue[0]);
    }

    public function test_charge_refunded_updates_order_and_cancels_membership(): void
    {
        $verifier = new StubVerifier();
        $charge = (object) [
            'id' => 'ch_ref_001',
            'amount_refunded' => 1299,
            'created' => time(),
            'refunds' => (object) ['data' => [(object) ['reason' => 'requested_by_customer']]],
        ];
        $verifier->event = (object) [
            'id' => 'evt_charge_refunded',
            'type' => 'charge.refunded',
            'data' => (object) ['object' => $charge],
        ];

        $idempo = new StubIdempotency();
        $orders = new StubOrders();
        $order = $orders->create([
            'payment_transaction_id' => 'ch_ref_001',
            'user_id' => 55,
            'membership_id' => 3,
            'total' => 12.99,
            'status' => 'success',
        ]);
        $members = new StubMemberships();
        $members->assign(55, 3, []);

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $res = $controller->handle_stripe(new FakeRequest('{}', ['Stripe-Signature' => 'sig']));

        $this->assertInstanceOf(WP_REST_Response::class, $res);

        $updated = $orders->find($order->id);
        $this->assertEquals('refunded', $updated->status);
        $this->assertEquals(12.99, $updated->refund_amount);
        $this->assertEquals('requested_by_customer', $updated->refund_reason);

        $this->assertCount(1, $members->cancelled);
        $this->assertSame([55, 3, 'Stripe refund processed'], $members->cancelled[0]);
    }

    public function test_subscription_deleted_cancels_membership_and_marks_order(): void
    {
        $verifier = new StubVerifier();
        $subscription = (object) [
            'id' => 'sub_del_001',
            'status' => 'canceled',
            'metadata' => (object) ['user_id' => 88, 'membership_id' => 12],
        ];
        $verifier->event = (object) [
            'id'   => 'evt_sub_deleted',
            'type' => 'customer.subscription.deleted',
            'data' => (object) ['object' => $subscription],
        ];

        $idempo  = new StubIdempotency();
        $orders  = new StubOrders();
        $order   = $orders->create([
            'payment_transaction_id'      => 'ch_keep',
            'subscription_transaction_id' => 'sub_del_001',
            'user_id'                     => 88,
            'membership_id'               => 12,
            'status'                      => 'success',
        ]);
        $members = new StubMemberships();
        $members->assign(88, 12, []);

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $res        = $controller->handle_stripe(new FakeRequest('{}', ['Stripe-Signature' => 'sig']));

        $this->assertInstanceOf(WP_REST_Response::class, $res);
        $this->assertCount(1, $members->cancelled);
        $this->assertSame([88, 12, 'Stripe subscription deleted'], $members->cancelled[0]);

        $this->assertNotEmpty($orders->statusLog);
        $this->assertEquals([$order->id, 'cancelled', 'Subscription cancelled'], $orders->statusLog[0]);
    }

    public function test_subscription_updated_syncs_membership(): void
    {
        $verifier = new StubVerifier();
        $subscription = (object) [
            'id' => 'sub_sync_001',
            'status' => 'past_due',
            'current_period_end' => time() + 86400,
            'discount' => (object) ['coupon' => (object) ['id' => 'SAVE10', 'percent_off' => 10, 'duration' => 'repeating']],
            'trial_start' => time() - 86400,
            'trial_end' => time() + 86400,
            'items' => (object) [
                'data' => [
                    (object) [
                        'plan' => (object) [
                            'amount' => 9900,
                            'interval_count' => 1,
                            'interval' => 'month',
                        ],
                    ],
                ],
            ],
            'metadata' => (object) ['user_id' => 75, 'membership_id' => 9],
        ];
        $verifier->event = (object) [
            'id' => 'evt_subscription_update',
            'type' => 'customer.subscription.updated',
            'data' => (object) ['object' => $subscription],
        ];

        $idempo = new StubIdempotency();
        $orders = new StubOrders();
        $members = new StubMemberships();
        $members->assign(75, 9, []);

        $controller = new WebhooksController($verifier, $idempo, $orders, $members);
        $res = $controller->handle_stripe(new FakeRequest('{}', ['Stripe-Signature' => 'sig']));

        $this->assertInstanceOf(WP_REST_Response::class, $res);
        $membership = $members->find(75, 9);
        $this->assertNotNull($membership);
        $this->assertEquals('past_due', $membership->status);
        $this->assertCount(1, $members->billingUpdates);
        $update = $members->billingUpdates[0][2];
        $this->assertEquals(99.00, $update['billing_amount']);
        $this->assertEquals(1, $update['cycle_number']);
        $this->assertEquals('Month', $update['cycle_period']);
    }
}
}
