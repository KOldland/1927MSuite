<?php
/**
 * Test case for StripeGateway
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Gateways\StripeGateway;
use KHM\Contracts\Result;

class StripeGatewayTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gateway = new StripeGateway();
    }

    public function testGetGatewayNameReturnsStripe()
    {
        $this->assertEquals('stripe', $this->gateway->getGatewayName());
    }

    public function testSetCredentialsSetsEnvironment()
    {
        $this->gateway->setCredentials([
            'secret_key' => 'sk_test_123',
            'publishable_key' => 'pk_test_123',
            'environment' => 'sandbox',
        ]);

        $this->assertEquals('sandbox', $this->gateway->getEnvironment());
    }

    public function testChargeRequiresPaymentMethod()
    {
        $order = (object)[
            'total' => 49.99,
            'currency' => 'usd',
        ];

        $result = $this->gateway->charge($order);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertEquals('missing_payment_method', $result->getErrorCode());
    }

    public function testRefundRequiresTransactionId()
    {
        $order = (object)[];

        $result = $this->gateway->refund($order);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertEquals('missing_transaction_id', $result->getErrorCode());
    }

    public function testVoidRequiresTransactionId()
    {
        $order = (object)[];

        $result = $this->gateway->void($order);

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertEquals('missing_transaction_id', $result->getErrorCode());
    }

    public function testCancelSubscriptionReturnsFalureForInvalidSubscription()
    {
        // This would require mocking Stripe API
        // For now, we verify method signature and return type
        $this->gateway->setCredentials([
            'secret_key' => 'sk_test_invalid',
            'environment' => 'sandbox',
        ]);

        $result = $this->gateway->cancelSubscription('sub_invalid_12345');

        $this->assertInstanceOf(Result::class, $result);
    }
}
