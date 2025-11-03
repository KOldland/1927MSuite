<?php
namespace KHM\Tests;

use KHM\Contracts\OrderRepositoryInterface;
use KHM\Contracts\Result;
use KHM\Services\PaymentMethodService;
use KHM\Services\Stripe\PaymentMethodStripeAdapterInterface;
use PHPUnit\Framework\TestCase;

class PaymentMethodServiceTest extends TestCase
{
    private PaymentOrdersStub $orders;
    private PaymentMethodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        $this->orders  = new PaymentOrdersStub();
        $this->service = new PaymentMethodService( $this->orders );
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        \remove_all_filters( 'khm_payment_method_stripe_adapter' );
    }

    public function testCreateSetupIntentReturnsClientSecret(): void
    {
        update_option( 'khm_stripe_secret_key', 'sk_test' );
        update_option( 'khm_stripe_publishable_key', 'pk_test' );

        $this->orders->addSubscriptionOrder( 5, 3, 'stripe', 'sub_123', 42 );

        $adapter = new PaymentMethodStripeAdapterStub();
        $adapter->subscription = (object) [
            'id'       => 'sub_123',
            'customer' => 'cus_456',
        ];
        $adapter->setupIntent = (object) [
            'client_secret' => 'si_secret_abc',
        ];

        \add_filter(
            'khm_payment_method_stripe_adapter',
            static function () use ( $adapter ) {
                return $adapter;
            }
        );

        $response = $this->service->createSetupIntent( 5, 3 );

        $this->assertTrue( $response['success'] );
        $this->assertSame( 'si_secret_abc', $response['client_secret'] );
        $this->assertSame( 'pk_test', $response['publishable_key'] );
        $this->assertSame( [ 'sk_test' ], $adapter->apiKeys );
        $this->assertSame( [ 'sub_123' ], $adapter->retrievedSubscriptions );
        $this->assertEquals(
            [
                [
                    'customer' => 'cus_456',
                    'usage' => 'off_session',
                    'payment_method_types' => [ 'card' ],
                    'metadata' => [
                        'user_id' => 5,
                        'membership_id' => 3,
                    ],
                ],
            ],
            $adapter->setupPayloads
        );
    }

    public function testCreateSetupIntentHandlesAdapterException(): void
    {
        update_option( 'khm_stripe_secret_key', 'sk_test' );
        update_option( 'khm_stripe_publishable_key', 'pk_test' );

        $this->orders->addSubscriptionOrder( 8, 6, 'stripe', 'sub_fail', 77 );

        $adapter = new PaymentMethodStripeAdapterStub();
        $adapter->subscription = (object) [
            'id'       => 'sub_fail',
            'customer' => 'cus_999',
        ];
        $adapter->throwOnSetup = true;

        \add_filter(
            'khm_payment_method_stripe_adapter',
            static function () use ( $adapter ) {
                return $adapter;
            }
        );

        $response = $this->service->createSetupIntent( 8, 6 );

        $this->assertFalse( $response['success'] );
        $this->assertSame( 'Failed to create Setup Intent.', $response['message'] );
    }

    public function testApplyPaymentMethodUpdatesOrderCardDetails(): void
    {
        update_option( 'khm_stripe_secret_key', 'sk_live' );

        $order = $this->orders->addSubscriptionOrder( 11, 9, 'stripe', 'sub_update', 101 );

        $adapter = new PaymentMethodStripeAdapterStub();
        $adapter->subscription = (object) [
            'id'       => 'sub_update',
            'customer' => 'cus_card',
        ];
        $adapter->paymentMethod = [
            'id'   => 'pm_123',
            'card' => [
                'brand'    => 'visa',
                'last4'    => '4242',
                'exp_month'=> 12,
                'exp_year' => 2030,
            ],
        ];

        \add_filter(
            'khm_payment_method_stripe_adapter',
            static function () use ( $adapter ) {
                return $adapter;
            }
        );

        $response = $this->service->applyPaymentMethod( 11, 9, 'pm_123' );

        $this->assertTrue( $response['success'] );
        $this->assertSame( 'Payment method updated.', $response['message'] );

        $this->assertSame( [ 'sk_live' ], $adapter->apiKeys );
        $this->assertSame( [ [ 'pm_123', 'cus_card' ] ], $adapter->attached );
        $this->assertSame( [ [ 'cus_card', [ 'invoice_settings' => [ 'default_payment_method' => 'pm_123' ] ] ] ], $adapter->customerUpdates );
        $this->assertSame( [ [ 'sub_update', [ 'default_payment_method' => 'pm_123' ] ] ], $adapter->subscriptionUpdates );

        $this->assertCount( 1, $this->orders->updates );
        $this->assertSame(
            [
                $order->id,
                [
                    'cardtype'        => 'visa',
                    'accountnumber'   => '************4242',
                    'expirationmonth' => 12,
                    'expirationyear'  => 2030,
                ],
            ],
            $this->orders->updates[0]
        );
    }

    public function testApplyPaymentMethodHandlesExceptions(): void
    {
        update_option( 'khm_stripe_secret_key', 'sk_live' );

        $this->orders->addSubscriptionOrder( 15, 4, 'stripe', 'sub_err', 500 );

        $adapter = new PaymentMethodStripeAdapterStub();
        $adapter->subscription = (object) [
            'id'       => 'sub_err',
            'customer' => 'cus_err',
        ];
        $adapter->throwOnAttach = true;

        \add_filter(
            'khm_payment_method_stripe_adapter',
            static function () use ( $adapter ) {
                return $adapter;
            }
        );

        $response = $this->service->applyPaymentMethod( 15, 4, 'pm_fail' );

        $this->assertFalse( $response['success'] );
        $this->assertSame( 'Failed to update payment method.', $response['message'] );
        $this->assertSame( [], $this->orders->updates );
    }
}

class PaymentOrdersStub implements OrderRepositoryInterface
{
    /** @var array<int,object> */
    public array $orders = [];
    /** @var array<int,array{0:int,1:array<string,mixed>}> */
    public array $updates = [];
    private int $nextId = 1;

    public function addSubscriptionOrder( int $userId, int $levelId, string $gateway, string $subscriptionId, int $id = null ): object
    {
        $order = (object) [
            'id'                        => $id ?? $this->nextId++,
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
        $data['id'] = $this->nextId++;
        $order      = (object) $data;
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
        $this->updates[] = [ $orderId, $data ];
        return $order;
    }

    public function find( int $orderId ): ?object
    {
        return $this->orders[ $orderId ] ?? null;
    }

    public function findByCode( string $code ): ?object
    {
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
        foreach ( $this->orders as $order ) {
            if ( $order->subscription_transaction_id === $subscriptionId ) {
                return $order;
            }
        }
        return null;
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
        return true;
    }

    public function updateNotes( int $orderId, string $notes ): bool
    {
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
        return 'CODE';
    }

    public function calculateTax( object $order ): float
    {
        return 0.0;
    }
}

class PaymentMethodStripeAdapterStub implements PaymentMethodStripeAdapterInterface
{
    /** @var array<int,string> */
    public array $apiKeys = [];
    /** @var array<int,string> */
    public array $retrievedSubscriptions = [];
    /** @var array<int,array<string,mixed>> */
    public array $setupPayloads = [];
    /** @var array<int,array{0:string,1:string}> */
    public array $attached = [];
    /** @var array<int,array{0:string,1:array<string,mixed>}> */
    public array $customerUpdates = [];
    /** @var array<int,array{0:string,1:array<string,mixed>}> */
    public array $subscriptionUpdates = [];
    public array $retrievedPaymentMethods = [];

    public $subscription;
    public $setupIntent;
    public $paymentMethod;

    public bool $throwOnSetup = false;
    public bool $throwOnAttach = false;

    public function setApiKey( string $secret ): void
    {
        $this->apiKeys[] = $secret;
    }

    public function createSetupIntent( array $payload )
    {
        if ( $this->throwOnSetup ) {
            throw new \Exception( 'adapter failure' );
        }

        $this->setupPayloads[] = $payload;
        return $this->setupIntent;
    }

    public function retrieveSubscription( string $subscriptionId )
    {
        $this->retrievedSubscriptions[] = $subscriptionId;
        return $this->subscription;
    }

    public function attachPaymentMethod( string $paymentMethodId, string $customerId ): void
    {
        if ( $this->throwOnAttach ) {
            throw new \Exception( 'attach failure' );
        }

        $this->attached[] = [ $paymentMethodId, $customerId ];
    }

    public function updateCustomer( string $customerId, array $attributes )
    {
        $this->customerUpdates[] = [ $customerId, $attributes ];
        return Result::success();
    }

    public function updateSubscription( string $subscriptionId, array $attributes )
    {
        $this->subscriptionUpdates[] = [ $subscriptionId, $attributes ];
        return Result::success();
    }

    public function retrievePaymentMethod( string $paymentMethodId )
    {
        $this->retrievedPaymentMethods[] = $paymentMethodId;
        return $this->paymentMethod;
    }
}
