<?php

namespace {
    if ( ! class_exists( 'WP_REST_Response' ) ) {
        class WP_REST_Response {
            public $data;
            public int $status;

            public function __construct( $data, int $status = 200 ) {
                $this->data   = $data;
                $this->status = $status;
            }
        }
    }
    if ( ! class_exists( 'WP_REST_Request' ) ) {
        class WP_REST_Request {
            private array $params;

            public function __construct( array $params = [] ) {
                $this->params = $params;
            }

            public function get_param( string $key ) {
                return $this->params[ $key ] ?? null;
            }
        }
    }
}

namespace KHM\Tests {

use KHM\Rest\PaymentMethodController;
use KHM\Services\PaymentMethodService;
use PHPUnit\Framework\TestCase;
use WP_REST_Response;

class PaymentMethodControllerRestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        \khm_tests_set_logged_in_user( null );
    }

    public function testSetupIntentRequiresLogin(): void
    {
        $controller = new PaymentMethodController( new PaymentMethodServiceStub() );
        $request    = new \WP_REST_Request( [ 'level_id' => 4 ] );

        $response = $controller->setup_intent( $request );

        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Invalid request.', $response->data['message'] );
    }

    public function testSetupIntentDelegatesToService(): void
    {
        $service = new PaymentMethodServiceStub();
        $service->setupIntentResult = [
            'success'       => true,
            'client_secret' => 'cs_test',
        ];

        \khm_tests_set_logged_in_user( 15 );

        $controller = new PaymentMethodController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 9 ] );

        $response = $controller->setup_intent( $request );

        $this->assertSame( [ 15, 9 ], $service->setupIntentCalls[0] );
        $this->assertSame( 200, $response->status );
        $this->assertSame( 'cs_test', $response->data['client_secret'] );
    }

    public function testSetupIntentFailureReturns400(): void
    {
        $service = new PaymentMethodServiceStub();
        $service->setupIntentResult = [
            'success' => false,
            'message' => 'No subscription',
        ];
        \khm_tests_set_logged_in_user( 20 );

        $controller = new PaymentMethodController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 3 ] );

        $response = $controller->setup_intent( $request );

        $this->assertSame( 400, $response->status );
        $this->assertSame( 'No subscription', $response->data['message'] );
    }

    public function testUpdateRequiresLogin(): void
    {
        $controller = new PaymentMethodController( new PaymentMethodServiceStub() );
        $request    = new \WP_REST_Request( [ 'level_id' => 4, 'payment_method_id' => 'pm_1' ] );

        $response = $controller->update( $request );

        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Invalid request.', $response->data['message'] );
    }

    public function testUpdateDelegatesToService(): void
    {
        $service = new PaymentMethodServiceStub();
        $service->updateResult = [
            'success' => true,
            'message' => 'Updated',
        ];

        \khm_tests_set_logged_in_user( 33 );

        $controller = new PaymentMethodController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 6, 'payment_method_id' => 'pm_999' ] );

        $response = $controller->update( $request );

        $this->assertSame( [ 33, 6, 'pm_999' ], $service->updateCalls[0] );
        $this->assertSame( 200, $response->status );
        $this->assertSame( 'Updated', $response->data['message'] );
    }

    public function testUpdateFailureReturns400(): void
    {
        $service = new PaymentMethodServiceStub();
        $service->updateResult = [
            'success' => false,
            'message' => 'Failed to update',
        ];

        \khm_tests_set_logged_in_user( 18 );

        $controller = new PaymentMethodController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 7, 'payment_method_id' => 'pm_fail' ] );

        $response = $controller->update( $request );

        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Failed to update', $response->data['message'] );
    }
}

class PaymentMethodServiceStub extends PaymentMethodService
{
    public array $setupIntentCalls = [];
    public array $updateCalls = [];
    public array $setupIntentResult = [ 'success' => true ];
    public array $updateResult = [ 'success' => true ];

    public function __construct()
    {
        // Intentionally empty; we won't call parent constructor.
    }

    public function createSetupIntent( int $userId, int $levelId ): array
    {
        $this->setupIntentCalls[] = [ $userId, $levelId ];
        return $this->setupIntentResult;
    }

    public function applyPaymentMethod( int $userId, int $levelId, string $paymentMethodId ): array
    {
        $this->updateCalls[] = [ $userId, $levelId, $paymentMethodId ];
        return $this->updateResult;
    }
}

}
