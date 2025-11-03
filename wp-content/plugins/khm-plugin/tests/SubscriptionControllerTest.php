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

use KHM\Rest\SubscriptionController;
use KHM\Services\SubscriptionManagementService;
use PHPUnit\Framework\TestCase;
use WP_REST_Response;

class SubscriptionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();
        \khm_tests_set_logged_in_user( null );
    }

    public function testCancelReturnsErrorWhenNotLoggedIn(): void
    {
        $controller = new SubscriptionController( new SubscriptionServiceStub() );
        $request    = new \WP_REST_Request( [ 'level_id' => 12, 'at_period_end' => true ] );

        $response = $controller->cancel( $request );

        $this->assertInstanceOf( WP_REST_Response::class, $response );
        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Invalid request.', $response->data['message'] );
    }

    public function testCancelDelegatesToService(): void
    {
        $service = new SubscriptionServiceStub();
        $service->cancelResult = [
            'success' => true,
            'message' => 'OK',
        ];
        \khm_tests_set_logged_in_user( 42 );

        $controller = new SubscriptionController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 5, 'at_period_end' => true ] );

        $response = $controller->cancel( $request );

        $this->assertSame( [ 42, 5, true ], $service->cancelCalls[0] );
        $this->assertSame( 200, $response->status );
        $this->assertTrue( $response->data['success'] );
    }

    public function testCancelImmediatePassesFlagAndReturnsErrorOnFailure(): void
    {
        $service = new SubscriptionServiceStub();
        $service->cancelResult = [
            'success' => false,
            'message' => 'Gateway failed',
        ];
        \khm_tests_set_logged_in_user( 11 );

        $controller = new SubscriptionController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 3, 'at_period_end' => false ] );

        $response = $controller->cancel( $request );

        $this->assertSame( [ 11, 3, false ], $service->cancelCalls[0] );
        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Gateway failed', $response->data['message'] );
    }

    public function testReactivateRequiresLogin(): void
    {
        $controller = new SubscriptionController( new SubscriptionServiceStub() );
        $request    = new \WP_REST_Request( [ 'level_id' => 2 ] );

        $response = $controller->reactivate( $request );

        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Invalid request.', $response->data['message'] );
    }

    public function testReactivateDelegatesToService(): void
    {
        $service = new SubscriptionServiceStub();
        $service->reactivateResult = [
            'success' => true,
            'message' => 'Reactivated',
        ];
        \khm_tests_set_logged_in_user( 77 );

        $controller = new SubscriptionController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 9 ] );

        $response = $controller->reactivate( $request );

        $this->assertSame( [ 77, 9 ], $service->reactivateCalls[0] );
        $this->assertSame( 200, $response->status );
        $this->assertSame( 'Reactivated', $response->data['message'] );
    }

    public function testReactivateReturnsErrorOnFailure(): void
    {
        $service = new SubscriptionServiceStub();
        $service->reactivateResult = [
            'success' => false,
            'message' => 'Cannot reactivate',
        ];
        \khm_tests_set_logged_in_user( 88 );

        $controller = new SubscriptionController( $service );
        $request    = new \WP_REST_Request( [ 'level_id' => 10 ] );

        $response = $controller->reactivate( $request );

        $this->assertSame( 400, $response->status );
        $this->assertSame( 'Cannot reactivate', $response->data['message'] );
    }
}

class SubscriptionServiceStub extends SubscriptionManagementService
{
    public array $cancelCalls = [];
    public array $reactivateCalls = [];
    public array $cancelResult = [ 'success' => true, 'message' => '' ];
    public array $reactivateResult = [ 'success' => true, 'message' => '' ];

    public function __construct()
    {
        // No parent init; this stub is manually controlled.
    }

    public function cancel( int $userId, int $levelId, bool $atPeriodEnd = true ): array
    {
        $this->cancelCalls[] = [ $userId, $levelId, $atPeriodEnd ];
        return $this->cancelResult;
    }

    public function reactivate( int $userId, int $levelId ): array
    {
        $this->reactivateCalls[] = [ $userId, $levelId ];
        return $this->reactivateResult;
    }
}

}
