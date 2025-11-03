# Testing KHM Membership Plugin

## Prerequisites

To run the test suite, you need PHP and Composer installed on your system.

### Installing PHP on macOS

Choose one of the following methods:

#### Option 1: Using Homebrew (Recommended)
```bash
# Install Homebrew if not already installed
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install PHP
brew install php@8.1

# Verify installation
php --version
```

#### Option 2: Using macOS System PHP
macOS may have PHP pre-installed. Check with:
```bash
/usr/bin/php --version
```

If available, you can create a symlink:
```bash
ln -s /usr/bin/php /usr/local/bin/php
```

### Installing Composer

Once PHP is available:
```bash
# Download and install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# Verify installation
composer --version
```

## Running Tests

### Install Dependencies
```bash
cd /Users/krisoldland/Documents/GitHub/Membership-Manager/khm-plugin
composer install
```

### Run All Tests
```bash
./vendor/bin/phpunit
```

### Run Specific Test Suites
```bash
# Run only scheduled tasks tests
./vendor/bin/phpunit tests/ScheduledTasksTest.php

# Run migration tests
./vendor/bin/phpunit tests/MigrationTest.php

# Run webhook tests
./vendor/bin/phpunit tests/WebhooksControllerTest.php
```

### Browser (Playwright) End-to-End Tests
```bash
npm install
npm run playwright:install
npm run test:e2e
```

These tests exercise the account page JavaScript against mocked REST responses, confirming cancel/reactivate/payment-method flows stay wired to the service layer. The harness stubs Stripe.js and uses the bundled `public/js/account.js` script.

### CI-Friendly Aggregate Runner
```bash
./bin/run-ci-suite.sh
```

This script runs the full PHPUnit suite and the Playwright E2E tests, ensuring `tests/reports/` is populated with `*.xml` JUnit artifacts and the latest log transcripts for CI uploads.

### Generate Coverage Report
```bash
./vendor/bin/phpunit --coverage-html coverage
open coverage/index.html
```

## Test Coverage

### ScheduledTasksTest.php (NEW)
Tests for scheduled membership expiration and warning email tasks:

1. **test_run_daily_expires_and_warns_and_returns_counts**
   - Verifies daily tasks process expirations and warnings
   - Checks correct counts are returned
   - Mocks wpdb, repository, and email service

2. **test_send_expiration_warnings_dedupes_by_usermeta**
   - Verifies warning emails are sent only once per membership
   - Tests deduplication via usermeta tracking
   - Ensures second run doesn't resend to same user

### Existing Tests
- **DatabaseIdempotencyStoreTest.php** - Webhook idempotency storage
- **MigrationTest.php** - Database migration scenarios
- **OrderRepositoryTest.php** - Order CRUD operations
- **StripeGatewayTest.php** - Stripe payment integration
- **StripeWebhookVerifierTest.php** - Webhook signature verification
- **WebhooksControllerTest.php** - REST webhook endpoint handling

## Manual Testing

### Test Scheduled Tasks via Admin UI
1. Navigate to WP Admin → KHM Membership → Settings
2. Scroll to "Scheduled Tasks" section
3. Configure settings:
   - Enable Daily Tasks: ☑
   - Daily Run Time: 02:00 (or your preferred time)
   - Expiration Warning: 7 days
4. Click "Save Settings"
5. Click "Run Now" to execute tasks immediately
6. Review the result message showing expired/warned counts

### Test via WP-CLI (if available)
```bash
# List scheduled events
wp cron event list

# Run the daily task manually
wp cron event run khm_daily_tasks

# Check next scheduled time
wp cron event list | grep khm_daily_tasks
```

### Test Expirations
1. Create a test membership with end_date in the past
2. Run daily tasks (via "Run Now" button or WP-CLI)
3. Verify:
   - Membership status changed to 'expired'
   - User received "membership_expired" email
   - Admin notice shows: "Expired: 1"

### Test Warnings
1. Create a test membership with end_date 7 days from now
2. Run daily tasks
3. Verify:
   - User received "membership_expiring" email
   - Admin notice shows: "Warnings sent: 1"
4. Run again immediately
5. Verify no duplicate email (dedupe via usermeta)

## Troubleshooting

### Composer not found
Ensure Composer is in your PATH:
```bash
which composer
echo $PATH
```

### PHP not found
Verify PHP installation:
```bash
which php
php --version
```

### Tests failing with "Class not found"
Run composer install:
```bash
composer install --no-interaction
```

### Brain Monkey errors
Ensure dev dependencies are installed:
```bash
composer install --dev
```

## CI/CD Integration

The test suite is designed to run in CI environments. Example GitHub Actions workflow:

```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: ./vendor/bin/phpunit
```

## Next Steps

- [ ] Install PHP and Composer using instructions above
- [ ] Run `composer install` to fetch dependencies
- [ ] Execute `./vendor/bin/phpunit` to run all tests
- [ ] Review coverage report if needed
- [ ] Add more test scenarios as features grow
