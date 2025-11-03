<?php
namespace KHM\Tests;

use KHM\Contracts\OrderRepositoryInterface;
use KHM\Contracts\MembershipRepositoryInterface;
use KHM\Contracts\Result;
use KHM\Gateways\StripeGateway;
use KHM\Services\SubscriptionManagementService;
use PHPUnit\Framework\TestCase;

class SubscriptionManagementServiceTest extends TestCase
{
    private SubscriptionOrdersStub $orders;
    private SubscriptionMembershipsStub $memberships;
    private SubscriptionManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        $this->orders       = new SubscriptionOrdersStub();
        $this->memberships  = new SubscriptionMembershipsStub();
        $this->service      = new SubscriptionManagementService( $this->orders, $this->memberships );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \remove_all_filters( 'khm_subscription_management_stripe_gateway' );
        \remove_all_filters( 'khm_subscription_management_stripe_gateway_created' );
    }

    public function testCancelAtPeriodEndDelegatesToGatewayOnly(): void
    {
        $order = $this->orders->addSubscriptionOrder( 42, 7, 'stripe', 'sub_123' );
        $gateway = new SubscriptionGatewayStub( Result::success( 'ok' ) );

        \add_filter(
            'khm_subscription_management_stripe_gateway',
            static function () use ( $gateway ) {
                return $gateway;
            }
        );

        $response = $this->service->cancel( 42, 7, true );

        $this->assertTrue( $response['success'] );
        $this->assertStringContainsString( 'will be cancelled at the end', $response['message'] );
        $this->assertCount( 1, $gateway->cancelCalls );
        $this->assertSame( [ 'sub_123', true ], $gateway->cancelCalls[0] );
        $this->assertSame( [], $this->memberships->cancelled );
        $this->assertSame( [], $this->orders->statusLog );
    }

    public function testCancelImmediateUpdatesMembershipAndOrder(): void
    {
        $order = $this->orders->addSubscriptionOrder( 99, 5, 'stripe', 'sub_abc' );
        $gateway = new SubscriptionGatewayStub( Result::success( 'ok' ) );

        \add_filter(
            'khm_subscription_management_stripe_gateway',
            static function () use ( $gateway ) {
                return $gateway;
            }
        );

        $response = $this->service->cancel( 99, 5, false );

        $this->assertTrue( $response['success'] );
        $this->assertStringContainsString( 'has been cancelled', $response['message'] );
        $this->assertCount( 1, $gateway->cancelCalls );
        $this->assertSame( [ 'sub_abc', false ], $gateway->cancelCalls[0] );

        $this->assertCount( 1, $this->memberships->cancelled );
        $this->assertSame( [ 99, 5, 'User-initiated immediate cancel' ], $this->memberships->cancelled[0] );

        $this->assertCount( 1, $this->orders->statusLog );
        $this->assertSame(
            [ $order->id, 'cancelled', 'User-initiated immediate cancel' ],
            $this->orders->statusLog[0]
        );
    }

    public function testCancelFailsWhenGatewayReturnsError(): void
    {
        $this->orders->addSubscriptionOrder( 21, 3, 'stripe', 'sub_fail' );
        $gateway = new SubscriptionGatewayStub( Result::failure( 'Gateway down' ) );

        \add_filter(
            'khm_subscription_management_stripe_gateway',
            static function () use ( $gateway ) {
                return $gateway;
            }
        );

        $response = $this->service->cancel( 21, 3, false );

        $this->assertFalse( $response['success'] );
        $this->assertStringContainsString( 'Gateway down', $response['message'] );
        $this->assertSame( [], $this->memberships->cancelled );
        $this->assertSame( [], $this->orders->statusLog );
    }

    public function testCancelFailsWhenGatewayUnavailable(): void
    {
        $this->orders->addSubscriptionOrder( 11, 2, 'stripe', 'sub_missing' );
        // No filter and no Stripe keys configured.

        $response = $this->service->cancel( 11, 2, true );

        $this->assertFalse( $response['success'] );
        $this->assertSame( 'Stripe gateway is not configured.', $response['message'] );
    }

    public function testCancelFailsWithUnsupportedGateway(): void
    {
        $this->orders->addSubscriptionOrder( 55, 8, 'manual', 'sub_manual' );

        $response = $this->service->cancel( 55, 8, true );

        $this->assertFalse( $response['success'] );
        $this->assertSame( 'Unsupported gateway for subscription management.', $response['message'] );
    }

    public function testCancelFailsWhenNoSubscriptionOrderFound(): void
    {
        $response = $this->service->cancel( 555, 1, true );

        $this->assertFalse( $response['success'] );
        $this->assertSame( 'No active subscription found for this membership.', $response['message'] );
    }

    public function testReactivateDelegatesToGateway(): void
    {
        $this->orders->addSubscriptionOrder( 7, 2, 'stripe', 'sub_react' );
        $gateway = new SubscriptionGatewayStub( Result::success( 'ok' ), Result::success( 'updated' ) );

        \add_filter(
            'khm_subscription_management_stripe_gateway',
            static function () use ( $gateway ) {
                return $gateway;
            }
        );

        $response = $this->service->reactivate( 7, 2 );

        $this->assertTrue( $response['success'] );
        $this->assertSame( 'Your subscription has been reactivated.', $response['message'] );
        $this->assertCount( 1, $gateway->updateCalls );
        $this->assertSame(
            [ 'sub_react', [ 'cancel_at_period_end' => false ] ],
            $gateway->updateCalls[0]
        );
    }

    public function testReactivateFailsWhenGatewayReturnsError(): void
    {
        $this->orders->addSubscriptionOrder( 9, 4, 'stripe', 'sub_err' );
        $gateway = new SubscriptionGatewayStub( Result::success(), Result::failure( 'Nope' ) );

        \add_filter(
            'khm_subscription_management_stripe_gateway',
            static function () use ( $gateway ) {
                return $gateway;
            }
        );

        $response = $this->service->reactivate( 9, 4 );

        $this->assertFalse( $response['success'] );
        $this->assertSame( 'Nope', $response['message'] );
    }
}

class SubscriptionOrdersStub implements OrderRepositoryInterface
{
    public array $orders = [];
    public array $statusLog = [];
    private int $nextId = 1;

    public function addSubscriptionOrder( int $userId, int $levelId, string $gateway, string $subscriptionId ): object
    {
        $order = (object) [
            'id'                        => $this->nextId++,
            'user_id'                   => $userId,
            'membership_id'             => $levelId,
            'gateway'                   => $gateway,
            'subscription_transaction_id' => $subscriptionId,
        ];
        $this->orders[ $order->id ] = $order;
        return $order;
    }

    public function create( array $data ): object
    {
        $order = (object) array_merge( [ 'id' => $this->nextId++ ], $data );
        $this->orders[ $order->id ] = $order;
        return $order;
    }

    public function update( int $orderId, array $data ): object
    {
        $order = $this->orders[ $orderId ] ?? (object) [ 'id' => $orderId ];
        foreach ( $data as $key => $value ) {
            $order->$key = $value;
        }
        $this->orders[ $orderId ] = $order;
        return $order;
    }

    public function find( int $orderId ): ?object
    {
        return $this->orders[ $orderId ] ?? null;
    }

    public function findByCode( string $code ): ?object
    {
        foreach ( $this->orders as $order ) {
            if ( isset( $order->code ) && $order->code === $code ) {
                return $order;
            }
        }
        return null;
    }

    public function findByPaymentTransactionId( string $txnId ): ?object
    {
        foreach ( $this->orders as $order ) {
            if ( isset( $order->payment_transaction_id ) && $order->payment_transaction_id === $txnId ) {
                return $order;
            }
        }
        return null;
    }

    public function findLastBySubscriptionId( string $subscriptionId ): ?object
    {
        $matches = array_filter(
            $this->orders,
            static fn( $order ) => isset( $order->subscription_transaction_id ) && $order->subscription_transaction_id === $subscriptionId
        );

        if ( empty( $matches ) ) {
            return null;
        }

        usort( $matches, static fn( $a, $b ) => $b->id <=> $a->id );

        return $matches[0] ?? null;
    }

    public function findByUser( int $userId, array $filters = [] ): array
    {
        return array_values(
            array_filter(
                $this->orders,
                static function ( $order ) use ( $userId, $filters ) {
                    if ( (int) ( $order->user_id ?? 0 ) !== $userId ) {
                        return false;
                    }
                    if ( isset( $filters['membership_id'] ) && (int) ( $order->membership_id ?? 0 ) !== (int) $filters['membership_id'] ) {
                        return false;
                    }
                    return true;
                }
            )
        );
    }

    public function getWithRelations( int $orderId ): ?array
    {
        return null;
    }

    public function getManyWithRelations( array $ids ): array
    {
        return [];
    }

    public function paginate( array $args = [] ): array
    {
        return [ 'items' => [], 'total' => 0 ];
    }

    public function updateStatus( int $orderId, string $status, string $notes = '' ): bool
    {
        $this->statusLog[] = [ $orderId, $status, $notes ];
        if ( isset( $this->orders[ $orderId ] ) ) {
            $this->orders[ $orderId ]->status = $status;
            $this->orders[ $orderId ]->status_notes = $notes;
        }
        return true;
    }

    public function updateNotes( int $orderId, string $notes ): bool
    {
        if ( isset( $this->orders[ $orderId ] ) ) {
            $this->orders[ $orderId ]->notes = $notes;
        }
        return true;
    }

    public function recordRefund( int $orderId, float $amount, string $reason = '', ?string $refundedAt = null ): bool
    {
        return true;
    }

    public function delete( int $orderId ): bool
    {
        unset( $this->orders[ $orderId ] );
        return true;
    }

    public function generateCode(): string
    {
        return 'TEST';
    }

    public function calculateTax( object $order ): float
    {
        return 0.0;
    }
}

class SubscriptionMembershipsStub implements MembershipRepositoryInterface
{
    public array $cancelled = [];
    private array $store = [];

    private function key( int $userId, int $levelId ): string
    {
        return $userId . ':' . $levelId;
    }

    public function assign( int $userId, int $levelId, array $options = [] ): object
    {
        $membership = (object) array_merge(
            [
                'user_id'       => $userId,
                'membership_id' => $levelId,
                'status'        => $options['status'] ?? 'active',
            ],
            $options
        );
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        return $membership;
    }

    public function cancel( int $userId, int $levelId, string $reason = '' ): bool
    {
        $membership = $this->findOrCreate( $userId, $levelId );
        $membership->status = 'cancelled';
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        $this->cancelled[] = [ $userId, $levelId, $reason ];
        return true;
    }

    public function expire( int $userId, int $levelId ): bool
    {
        $membership = $this->findOrCreate( $userId, $levelId );
        $membership->status = 'expired';
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        return true;
    }

    public function findActive( int $userId ): array
    {
        return array_values(
            array_filter(
                $this->store,
                static fn( $membership ) => $membership->user_id === $userId && $membership->status === 'active'
            )
        );
    }

    public function findByLevel( int $levelId, array $filters = [] ): array
    {
        return array_values(
            array_filter(
                $this->store,
                static fn( $membership ) => $membership->membership_id === $levelId
            )
        );
    }

    public function findExpiring( int $days = 7 ): array
    {
        return [];
    }

    public function hasAccess( int $userId, int $levelId ): bool
    {
        $membership = $this->find( $userId, $levelId );
        return $membership ? $membership->status === 'active' : false;
    }

    public function updateEndDate( int $userId, int $levelId, ?\DateTime $endDate ): bool
    {
        $membership = $this->findOrCreate( $userId, $levelId );
        $membership->enddate = $endDate ? $endDate->format( 'Y-m-d H:i:s' ) : null;
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        return true;
    }

    public function find( int $userId, int $levelId ): ?object
    {
        return $this->store[ $this->key( $userId, $levelId ) ] ?? null;
    }

    public function markPastDue( int $userId, int $levelId, string $reason = '' ): bool
    {
        $membership = $this->findOrCreate( $userId, $levelId );
        $membership->status = 'past_due';
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        return true;
    }

    public function updateBillingProfile( int $userId, int $levelId, array $attributes = [] ): bool
    {
        $membership = $this->findOrCreate( $userId, $levelId );
        foreach ( $attributes as $key => $value ) {
            $membership->$key = $value;
        }
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        return true;
    }

    public function setStatus( int $userId, int $levelId, string $status, string $reason = '' ): bool
    {
        $membership = $this->findOrCreate( $userId, $levelId );
        $membership->status = $status;
        $membership->status_reason = $reason;
        $this->store[ $this->key( $userId, $levelId ) ] = $membership;
        return true;
    }

    private function findOrCreate( int $userId, int $levelId ): object
    {
        $key = $this->key( $userId, $levelId );
        if ( ! isset( $this->store[ $key ] ) ) {
            $this->store[ $key ] = (object) [
                'user_id'       => $userId,
                'membership_id' => $levelId,
                'status'        => 'active',
            ];
        }

        return $this->store[ $key ];
    }
}

class SubscriptionGatewayStub extends StripeGateway
{
    /** @var array<int,array{0:string,1:bool}> */
    public array $cancelCalls = [];

    /** @var array<int,array{0:string,1:array}> */
    public array $updateCalls = [];

    private Result $cancelResult;
    private Result $updateResult;

    public function __construct( ?Result $cancelResult = null, ?Result $updateResult = null )
    {
        $this->cancelResult = $cancelResult ?? Result::success();
        $this->updateResult = $updateResult ?? Result::success();
    }

    public function cancelSubscription( $subscriptionId, bool $atPeriodEnd = true ): Result
    {
        $this->cancelCalls[] = [ (string) $subscriptionId, $atPeriodEnd ];
        return $this->cancelResult;
    }

    public function updateSubscription( $subscriptionId, $args = [] ): Result
    {
        $this->updateCalls[] = [ (string) $subscriptionId, (array) $args ];
        return $this->updateResult;
    }
}
