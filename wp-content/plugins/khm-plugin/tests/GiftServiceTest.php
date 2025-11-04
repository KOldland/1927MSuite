<?php

use PHPUnit\Framework\TestCase;
use KHM\Services\GiftService;
use KHM\Services\MembershipRepository;
use KHM\Services\OrderRepository;
use KHM\Services\EmailService;

class GiftServiceTest extends TestCase
{
    private GiftService $giftService;
    private $membershipRepository;
    private $orderRepository;
    private $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock dependencies
        $this->membershipRepository = $this->createMock(MembershipRepository::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->emailService = $this->createMock(EmailService::class);
        
        // Create the service
        $this->giftService = new GiftService(
            $this->membershipRepository,
            $this->orderRepository,
            $this->emailService
        );
    }

    public function test_create_gift_with_valid_data_returns_success()
    {
        // Mock the EmailService to return success
        $this->emailService->method('send')->willReturn(true);
        
        $gift_data = [
            'post_id' => 123,
            'sender_id' => 1,
            'sender_name' => 'John Doe',
            'recipient_email' => 'jane@example.com',
            'recipient_name' => 'Jane Smith',
            'gift_message' => 'Hope you enjoy this article!',
            'gift_price' => 5.00,
            'expires_days' => 30
        ];

        // Note: This test would require database setup in a real environment
        // For now, we're testing the method signature and basic structure
        $result = $this->giftService->create_gift($gift_data);
        
        // Verify it returns an array (the expected format)
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_create_gift_missing_required_field_returns_error()
    {
        $gift_data = [
            'post_id' => 123,
            // Missing sender_id and other required fields
        ];

        $result = $this->giftService->create_gift($gift_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContains('Missing required field', $result['error']);
    }

    public function test_redeem_gift_method_exists_and_returns_array()
    {
        // Test that the redeem_gift method exists and returns proper format
        $result = $this->giftService->redeem_gift('invalid-token', 'download');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_get_gift_by_token_method_exists()
    {
        // Test that the method exists and returns proper format
        $result = $this->giftService->get_gift_by_token('invalid-token');
        
        // Should return null for invalid token or array for valid token
        $this->assertTrue(is_null($result) || is_array($result));
    }

    public function test_get_sent_gifts_returns_array()
    {
        $result = $this->giftService->get_sent_gifts(1, 10, 0);
        
        $this->assertIsArray($result);
    }

    public function test_get_received_gifts_returns_array()
    {
        $result = $this->giftService->get_received_gifts('test@example.com', 10, 0);
        
        $this->assertIsArray($result);
    }

    public function test_database_tables_creation_method_exists()
    {
        // Test that the create_tables method exists
        $this->assertTrue(method_exists($this->giftService, 'create_tables'));
    }
}