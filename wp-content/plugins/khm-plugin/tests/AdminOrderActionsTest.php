<?php
namespace KHM\Tests;

use KHM\Contracts\EmailServiceInterface;
use KHM\Contracts\OrderRepositoryInterface;
use KHM\Contracts\Result;
use KHM\Gateways\StripeGateway;
use KHM\Services\AdminOrderActions;
use PHPUnit\Framework\TestCase;

class AdminOrderActionsTest extends TestCase
{
    private StubRepo $orders;
    private StubEmail $email;
    private AdminOrderActions $actions;

    protected function setUp(): void
    {
        parent::setUp();
        \khm_tests_reset_environment();

        update_option( 'admin_email', 'admin@example.com' );

        $this->orders  = new StubRepo();
        $this->email   = new StubEmail();
        $this->actions = new AdminOrderActions( $this->orders, $this->email );
    }

    public function testHandleResendReceiptSendsMemberAndAdminEmail(): void
    {
        $this->orders->order = [
            'id'                     => 42,
            'user_id'                => 7,
            'user_email'             => 'member@example.com',
            'user_login'             => 'member-login',
            'display_name'           => 'Member Name',
            'membership_id'          => 5,
            'level_name'             => 'Gold',
            'total'                  => 49.99,
            'gateway'                => 'stripe',
            'payment_transaction_id' => 'pi_123',
            'discount_code'          => 'SAVE10',
            'discount_amount'        => 10.0,
            'trial_days'             => 7,
            'trial_amount'           => 0.0,
            'recurring_discount_type'   => 'percent',
            'recurring_discount_amount' => 10.0,
        ];

        \khm_tests_set_userdata( 7, [
            'ID'           => 7,
            'user_email'   => 'member@example.com',
            'user_login'   => 'member-login',
            'display_name' => 'Member Name',
        ] );

        $this->actions->handle_resend_receipt( 42 );

        $this->assertCount( 2, $this->email->sent );

        $memberMail = $this->email->sent[0];
        $this->assertSame( 'invoice', $memberMail['template'] );
        $this->assertSame( 'member@example.com', $memberMail['recipient'] );
        $this->assertSame( 'Gold', $memberMail['data']['level_name'] );
        $this->assertSame( 'Discount SAVE10 applied: -$10.00', $memberMail['data']['discount_summary'] );
        $this->assertSame( 'Free trial: 7 days', $memberMail['data']['trial_summary'] );
        $this->assertSame( 'Recurring discount: 10.00% off each renewal', $memberMail['data']['recurring_summary'] );

        $adminMail = $this->email->sent[1];
        $this->assertSame( 'invoice_admin', $adminMail['template'] );
        $this->assertSame( 'admin@example.com', $adminMail['recipient'] );
    }

    public function testHandleRefundRecordedUpdatesNotesAndFiresSuccessAction(): void
    {
        $this->orders->order = [
            'id'                     => 99,
            'gateway'                => 'stripe',
            'payment_transaction_id' => 'pi_789',
            'notes'                  => 'Existing note',
        ];

        $result  = Result::success('ok', ['refund_id' => 're_123']);
        $gateway = new StubStripeGateway($result);

        add_filter('khm_admin_order_actions_stripe_gateway', static function () use ($gateway) {
            return $gateway;
        });

        $captured = [];
        add_action('khm_order_gateway_refunded', static function (...$args) use (&$captured) {
            $captured[] = $args;
        });

        $this->actions->handle_refund_recorded(99, 20.00, 'Customer request', $this->orders->order);

        $this->assertCount(1, $gateway->refundCalls);
        $this->assertArrayHasKey(99, $this->orders->notes);
        $this->assertStringContainsString('Stripe refund processed: $20.00 (Refund ID: re_123)', $this->orders->notes[99]);
        $this->assertSame('Existing note', explode("\n\n", $this->orders->notes[99])[0]);
        $this->assertCount(1, $captured);
        $this->assertSame([99, 're_123', 20.00, 'Customer request'], $captured[0]);
    }

    public function testHandleRefundRecordedTriggersFailureActionWhenGatewayFails(): void
    {
        $this->orders->order = [
            'id'                     => 77,
            'gateway'                => 'stripe',
            'payment_transaction_id' => 'pi_fail',
        ];

        $result  = Result::failure('Nope', 'gateway_error');
        $gateway = new StubStripeGateway($result);

        add_filter('khm_admin_order_actions_stripe_gateway', static function () use ($gateway) {
            return $gateway;
        });

        $failures = [];
        add_action('khm_order_gateway_refund_failed', static function (...$args) use (&$failures) {
            $failures[] = $args;
        });

        $this->actions->handle_refund_recorded(77, 15.50, 'Test fail', $this->orders->order);

        $this->assertCount(1, $gateway->refundCalls);
        $this->assertArrayNotHasKey(77, $this->orders->notes);
        $this->assertCount(1, $failures);
        $this->assertSame(77, $failures[0][0]);
        $this->assertSame($result, $failures[0][1]);
    }

}

class StubRepo implements OrderRepositoryInterface
{
    public array $order = [];
    public array $notes = [];

    public function create(array $data): object { return (object) $data; }
    public function update(int $orderId, array $data): object { return (object) $data; }
    public function find(int $orderId): ?object { return (object) $this->order; }
    public function findByCode(string $code): ?object { return null; }
    public function findByPaymentTransactionId(string $txnId): ?object { return null; }
    public function findLastBySubscriptionId(string $subscriptionId): ?object { return null; }
    public function findByUser(int $userId, array $filters = []): array { return []; }
    public function getWithRelations(int $orderId): ?array { return $this->order; }
    public function getManyWithRelations(array $ids): array { return [$this->order]; }
    public function paginate(array $args = []): array { return ['items' => [], 'total' => 0]; }
    public function updateStatus(int $orderId, string $status, string $notes = ''): bool { return true; }
    public function updateNotes(int $orderId, string $notes): bool { $this->notes[$orderId] = $notes; return true; }
    public function recordRefund(int $orderId, float $amount, string $reason = '', ?string $refundedAt = null ): bool { return true; }
    public function delete(int $orderId): bool { return true; }
    public function generateCode(): string { return 'TEST'; }
    public function calculateTax(object $order): float { return 0.0; }
}

class StubEmail implements EmailServiceInterface
{
    public array $sent = [];
    private string $subject = '';

    public function send(string $templateKey, string $recipient, array $data = []): bool
    {
        $this->sent[] = [
            'template'  => $templateKey,
            'recipient' => $recipient,
            'data'      => $data,
            'subject'   => $this->subject,
        ];
        return true;
    }

    public function render(string $templateKey, array $data = []): string
    {
        return '';
    }

    public function setFrom(string $email, string $name): self
    {
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        return $this;
    }

    public function addAttachment(string $filePath): self
    {
        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    public function getTemplatePath(string $templateKey): ?string
    {
        return null;
    }
}

class StubStripeGateway extends StripeGateway
{
    /**
     * @var array<int, array{0: object, 1: float|null}>
     */
    public array $refundCalls = [];

    private Result $result;

    public function __construct(Result $result)
    {
        $this->result = $result;
    }

    public function refund($order, ?float $amount = null): Result
    {
        $this->refundCalls[] = [$order, $amount];
        return $this->result;
    }
}
