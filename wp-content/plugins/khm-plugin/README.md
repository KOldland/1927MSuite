# KHM Membership Plugin

A modern, extensible WordPress membership management plugin built on clean architecture principles.

## Features

- **Multiple Payment Gateways**: Stripe (more coming soon)
- **Recurring Subscriptions**: Automatic billing with subscription management
- **Webhook Idempotency**: Prevents duplicate event processing
- **Content Protection**: Restrict posts/pages by membership level
- **Email System**: Template-based emails with theme overrides
- **Reports & Analytics**: Revenue dashboards, MRR tracking, churn analysis
- **Data Export**: CSV export for members and orders
- **Migration Tools**: CLI tools for database migrations
- **Extensible**: Rich action/filter hooks for customization

## Installation

### Requirements

- PHP 7.4 or higher
- WordPress 5.8 or higher
- Composer (for development)

### Install Dependencies

```bash
cd khm-plugin
composer install
```

### Activate Plugin

1. Upload `khm-plugin` directory to `/wp-content/plugins/`
2. Activate through WordPress admin
3. Configure gateway credentials in Settings > KHM Membership

## Quick Start

### 1. Process a Payment with Stripe

```php
use KHM\Gateways\StripeGateway;

$gateway = new StripeGateway([
    'secret_key' => 'sk_test_...',
    'publishable_key' => 'pk_test_...',
    'environment' => 'sandbox',
]);

$order = (object)[
    'total' => 29.99,
    'currency' => 'usd',
    'payment_method_id' => 'pm_card_visa',
    'user_id' => 123,
    'membership_id' => 1,
];

$result = $gateway->charge($order);

if ($result->isSuccess()) {
    echo "Payment successful: " . $result->get('transaction_id');
} else {
    echo "Error: " . $result->getMessage();
}
```

### 2. Assign Membership to User

```php
use KHM\Services\MembershipRepository;

$repo = new MembershipRepository();
$membership = $repo->assign(
    userId: 123,
    levelId: 1,
    options: [
        'start_date' => new DateTime(),
        'end_date' => new DateTime('+1 year'),
        'status' => 'active',
    ]
);

// Check access
if ($repo->hasAccess(userId: 123, levelId: 1)) {
    // Show premium content
}
```

### 3. Display Checkout Form

Add the checkout shortcode to any page:

```
[khm_checkout level_id="1"]
```

**Shortcode Attributes:**
- `level_id` (required) - The membership level ID to purchase

**Features:**
- Secure payment processing with Stripe Elements
- PCI-compliant card collection
- CSRF protection with WordPress nonces
- Automatic order creation and membership assignment
- Customizable email notifications
- Theme override support via `khm/checkout.php`

**Theme Override:**
Create a custom checkout template in your theme:
```
your-theme/
  khm/
    checkout.php
```

### 4. Send Email

```php
use KHM\Services\EmailService;

$email = new EmailService(plugin_dir_path(__FILE__));
$email->setFrom('support@example.com', 'Support Team')
      ->setSubject('Welcome!')
      ->send('checkout_paid', 'user@example.com', [
          'name' => 'John Doe',
          'membership_level' => 'Premium',
      ]);
```

### 5. Content Protection

**Protect Content with Shortcodes:**
```
[khm_member]This content is for all members[/khm_member]
[khm_member levels="1,2,3"]This is for specific levels[/khm_member]
[khm_nonmember]Sign up to see premium content![/khm_nonmember]
```

**Check Access in PHP:**
```php
// Check if user has access to a post
if (khm_has_access(0, $post_id)) {
    // Show premium content
}

// Check if user has specific membership level
if (khm_user_has_membership($level_id)) {
    // User has this level
}

// Protect a post programmatically
khm_protect_post($post_id, [1, 2]); // Require levels 1 or 2
```

**Account Management:**
```
[khm_account] - Full account page with memberships, orders, profile
[khm_account section="memberships"] - Just show memberships
[khm_account section="orders"] - Just show order history
```

### 6. View Reports & Analytics

Navigate to **Reports** in the WordPress admin to access:

**Dashboard Tab:**
- Sales & revenue metrics (today, this month, this year)
- Membership signups and cancellations
- Key financial metrics (MRR, Churn, ARPU)
- Active members count

**Revenue Report Tab:**
- Interactive Chart.js line chart
- Custom date range filtering
- Revenue and sales count visualization
- Group by day, month, or year

**MRR & Churn Tab:**
- Monthly Recurring Revenue calculation
- Churn rate analysis
- Active member tracking
- Average Revenue Per User (ARPU)

**Programmatic Access:**
```php
use KHM\Services\ReportsService;

$reports = new ReportsService();

// Get this month's revenue
$revenue = $reports->get_revenue('this_month');

// Calculate MRR with billing cycle normalization
$mrr = $reports->calculate_mrr();

// Get churn rate
$churn = $reports->get_churn_rate('this_month');

// Revenue time-series data for charts
$data = $reports->get_revenue_by_date('2025-10-01', '2025-10-31', 'day');
```

### 7. Export Member Data

From the **Members** page:
1. Select members using checkboxes
2. Choose "Export to CSV" from bulk actions
3. Click "Apply" to download CSV file

**CSV includes:** Member ID, User ID, Username, Email, Display Name, Level, Start/End dates, Status

## Architecture

```
khm-plugin/
├── src/
│   ├── Admin/              # Admin pages & UI (Dashboard, Reports, Members, Orders)
│   ├── Contracts/          # Interfaces
│   ├── Gateways/           # Payment gateway implementations
│   ├── Services/           # Business logic & repositories
│   └── Models/             # Data models
├── tests/                  # PHPUnit tests (59 tests)
├── db/migrations/          # SQL migration scripts
├── email/                  # Email templates
├── docs/                   # Documentation
└── bin/                    # CLI tools (migrate.php)
```

## Webhooks

- Stripe endpoint: POST `/wp-json/khm/v1/webhooks/stripe`
- Configure the signing secret in the option `khm_stripe_webhook_secret`.
- The endpoint verifies signatures and enforces idempotency via the `khm_webhook_events` table.
- Hooks you can use:
    - `khm_webhook_stripe` runs for any Stripe event
    - `khm_webhook_stripe_{type}` runs per event type (dots replaced with underscores), e.g. `khm_webhook_stripe_invoice_payment_succeeded`

Built-in handlers currently cover:
- `invoice.payment_succeeded`: creates/updates a successful order and (re)activates membership
- `invoice.payment_failed`: records a failed order
- `customer.subscription.deleted`: cancels membership and marks last order as cancelled
- `charge.refunded`: marks order as refunded when matched by charge ID

Mapping the event to your user and membership level:
- By default, the controller checks Stripe `metadata` on the event object (`user_id`, `membership_id`).
- You can implement mapping via filters when no metadata is present:
    - `khm_stripe_map_customer_to_user` — return a WP user ID based on the event’s customer
    - `khm_stripe_map_plan_to_level` — return a membership level ID based on plan/price/product

Example handler:

```php
add_action('khm_webhook_stripe_invoice_payment_succeeded', function($event) {
        // $event->data->object contains the invoice object
});
```

## Database Setup

Run migrations to create required tables:

```bash
php bin/migrate.php --dry-run   # Preview changes
php bin/migrate.php --apply     # Execute migrations
```

## Testing

### Running Tests (Recommended)

Use the test runner script that handles both regular and Brain Monkey tests:

```bash
./bin/run-tests.sh
```

### Running Tests Manually

Regular tests (without Brain Monkey):
```bash
composer install
./vendor/bin/phpunit --exclude-group brain-monkey --testdox
```

Brain Monkey tests:
```bash
BRAIN_MONKEY_TEST=1 ./vendor/bin/phpunit --group brain-monkey --testdox
```

Run specific test file:
```bash
./vendor/bin/phpunit tests/StripeGatewayTest.php --testdox
```

### Test Coverage

- ✅ 55/59 tests passing (93%)
- DatabaseIdempotencyStore: 5 tests
- Migration: 7 tests  
- OrderRepository: 5 tests
- ReportsService: 19 tests ⭐ NEW
- ScheduledTasks: 2 tests
- StripeGateway: 6 tests
- StripeWebhookVerifier: 7 tests
- WebhooksController: 7 tests (4 Phase 1 + 3 original)

**Phase 2 Test Highlights:**
- MRR calculation with billing cycle normalization (Day/Week/Month/Year)
- Churn rate analysis with edge cases
- Revenue time-series grouping for charts
- Sales/revenue filtering by period and level
- Mock wpdb infrastructure for fast, isolated tests

See [TESTS_FIXED.md](TESTS_FIXED.md) and [PHASE_2_REPORTS_ANALYTICS.md](PHASE_2_REPORTS_ANALYTICS.md) for detailed documentation.

## Extension Hooks

### Key Actions

```php
do_action('khm_gateway_before_charge', $order, $gateway);
do_action('khm_membership_assigned', $userId, $levelId, $membershipId);
do_action('khm_order_status_changed', $orderId, $status, $notes);
```

### Key Filters

```php
add_filter('khm_email_body', function($body, $templateKey, $data) {
    return $body;
}, 10, 3);

add_filter('khm_stripe_charge_params', function($params, $order) {
    return $params;
}, 10, 2);
```

See [docs/extension_points.md](docs/extension_points.md) for complete list.

## Documentation

### Feature Documentation
- [Phase 2: Reports & Analytics](PHASE_2_REPORTS_ANALYTICS.md) - Complete implementation guide (NEW)
- [PMP Parity Checklist](PMP_PARITY_CHECKLIST.md) - Feature comparison (~68% complete)
- [Tests Fixed](TESTS_FIXED.md) - Test suite documentation

### Technical Documentation
- [Reusable Components](docs/reusable_components.md) - Component analysis and interfaces
- [Architecture](docs/architecture.md) - High-level modules, data flow, and hooks
- [Data Model Mapping](docs/data_model_mapping.md) - PMPro → KHM tables, migration plan, acceptance criteria
- [Contracts README](src/Contracts/README.md) - Interface usage guide
- [Extension Points](docs/extension_points.md) - Top 40 hooks
- [Admin Flows](docs/admin_flows.md) - CRUD operations
- [Database Lifecycle](docs/db_lifecycle.md) - Table structure
- [Webhook Flow](webhook_ipn_flow.md) - Webhook handling

## Development Status

✅ **Completed**:
- Core contracts/interfaces
- Stripe gateway implementation
- Order & Membership repositories
- Email service
- Idempotency store
- PHPUnit test suite
- Database migration CLI

🚧 **In Progress**:
- Scheduled jobs for expiration & warnings
- Testing & CI

✅ **Recently Completed - Phase 2** (October 2025):
- **Reports & Analytics Dashboard**: Revenue tracking, MRR calculation, churn analysis
- **Chart.js Visualizations**: Interactive revenue charts with date filtering
- **Member CSV Export**: Bulk export functionality with 9 data columns
- **ReportsService**: Comprehensive data calculation engine with 9 methods
- **Cache Optimization**: Transient-based caching with automatic invalidation
- **Test Suite Expansion**: 19 new tests for report calculations (55 total tests)

✅ **Previously Completed**:
- Checkout UI with Stripe Elements
- AJAX payment processing
- Responsive checkout form styling
- Content protection API (`khm_has_access()`)
- Member shortcodes (`[khm_member]`, `[khm_nonmember]`)
- Account management shortcode (`[khm_account]`)
- Automatic content filtering for protected posts
- Admin UI with dashboard, members list, orders list, settings
- WordPress WP_List_Table integration with filters and bulk actions
- CSV export functionality for orders

📋 **Planned - Phase 3+**:
- Memberships report chart (in progress)
- Additional gateways (PayPal, Braintree)
- Discount codes system
- Advanced reporting features (LTV, cohort analysis)

## License

MIT License

## Credits

Built with inspiration from Paid Memberships Pro, modernized with clean architecture.

- This is a lightweight starting point. We will add migrations, tests, and CI as we implement features.
