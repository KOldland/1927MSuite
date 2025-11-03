<?php
/**
 * Test case for StripeWebhookVerifier
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Gateways\StripeWebhookVerifier;

class StripeWebhookVerifierTest extends TestCase
{
    private $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new StripeWebhookVerifier();
    }

    public function testVerifyReturnsFalseWithoutSignature()
    {
        $payload = '{"id": "evt_test", "type": "charge.succeeded"}';
        $headers = [];
        $secret = 'whsec_test_secret';

        $result = $this->verifier->verify($payload, $headers, $secret);

        $this->assertFalse($result);
    }

    public function testParseEventReturnsObjectForValidJson()
    {
        $payload = '{"id": "evt_test_12345", "type": "charge.succeeded", "data": {"object": {}}}';

        $event = $this->verifier->parseEvent($payload);

        $this->assertIsObject($event);
        $this->assertEquals('evt_test_12345', $event->id);
        $this->assertEquals('charge.succeeded', $event->type);
    }

    public function testParseEventThrowsExceptionForInvalidJson()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        $payload = 'not valid json';
        $this->verifier->parseEvent($payload);
    }

    public function testParseEventThrowsExceptionForMissingRequiredFields()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required event fields');

        $payload = '{"data": {}}';
        $this->verifier->parseEvent($payload);
    }

    public function testGetEventIdReturnsEventId()
    {
        $event = (object)['id' => 'evt_test_12345', 'type' => 'charge.succeeded'];

        $eventId = $this->verifier->getEventId($event);

        $this->assertEquals('evt_test_12345', $eventId);
    }

    public function testGetEventTypeReturnsEventType()
    {
        $event = (object)['id' => 'evt_test_12345', 'type' => 'customer.subscription.created'];

        $eventType = $this->verifier->getEventType($event);

        $this->assertEquals('customer.subscription.created', $eventType);
    }

    public function testGetEventIdReturnsEmptyStringWhenMissing()
    {
        $event = (object)[];

        $eventId = $this->verifier->getEventId($event);

        $this->assertEquals('', $eventId);
    }
}
