# Reusable Components Analysis

**Purpose**: Extract and abstract core membership management components from PMPro into clean, testable, extensible interfaces for KHM.

---

## 1. Gateway Abstraction Layer

**Source Files**:
- `classes/gateways/class.pmprogateway.php` (base gateway)
- `classes/gateways/class.pmprogateway_stripe.php`
- `classes/gateways/class.pmprogateway_braintree.php`
- `classes/gateways/class.pmprogateway_paypalexpress.php`
- `classes/gateways/class.pmprogateway_authorizenet.php`
- `classes/gateways/class.pmprogateway_twocheckout.php`

**Responsibilities**:
- Process one-time charges and recurring subscriptions
- Authorize, capture, void, and refund transactions
- Create, update, and cancel subscriptions
- Handle tokenization and customer creation
- Manage API credentials and sandbox/production modes
- Return success/failure states and detailed error messages

**Proposed Interface**: `GatewayInterface`

**Methods**:
```php
authorize(Order $order): Result
charge(Order $order): Result
void(Order $order): Result
refund(Order $order, float $amount = null): Result
createSubscription(Order $order): Result
updateSubscription(string $subscriptionId, array $params): Result
cancelSubscription(string $subscriptionId): Result
getCustomer(string $customerId): ?Customer
createCustomer(User $user, array $paymentMethod): Result
```

**Failure Modes**:
- Invalid credentials → API error with code
- Network timeout → retryable error
- Card declined → user-friendly message
- Subscription not found → gateway-specific error

**Extension Points**:
- `khm_gateway_before_charge`, `khm_gateway_after_charge`
- `khm_gateway_error` (for logging/alerting)
- `khm_gateway_api_params` (modify request before sending)

---

## 2. Webhook Verification & Idempotency

**Source Files**:
- `services/stripe-webhook.php`
- `services/braintree-webhook.php`
- `services/paypal-webhook.php`
- `services/authnet-webhook.php`
- `services/twocheckout-webhook.php`

**Responsibilities**:
- Verify webhook signatures (HMAC-SHA256, MD5, etc.)
- Parse event payloads into normalized Event objects
- Check idempotency (have we seen this event ID before?)
- Record processed event IDs with timestamps
- Route events to appropriate handlers

**Proposed Interfaces**: `WebhookVerifierInterface`, `IdempotencyStoreInterface`

**Methods**:
```php
// WebhookVerifierInterface
verify(string $payload, array $headers, string $secret): bool
parseEvent(string $payload): Event

// IdempotencyStoreInterface
hasProcessed(string $eventId): bool
markProcessed(string $eventId, string $gateway, array $metadata = []): void
getProcessedEvent(string $eventId): ?array
```

**Failure Modes**:
- Invalid signature → reject with 401
- Malformed JSON → reject with 400
- Event already processed → log duplicate, return 200
- Database unavailable → fallback to temporary in-memory store or retry queue

**Extension Points**:
- `khm_webhook_before_verify`
- `khm_webhook_event_parsed`
- `khm_webhook_duplicate_detected`

---

## 3. Order Repository

**Source Files**:
- `classes/class.memberorder.php` (lines 1-771)
- Database: `wp_pmpro_membership_orders`

**Responsibilities**:
- CRUD operations for orders
- Query orders by user, status, gateway, transaction ID, subscription ID
- Save billing details and payment metadata
- Update order status and timestamps
- Calculate tax, subtotal, total
- Generate random order codes

**Proposed Interface**: `OrderRepositoryInterface`

**Methods**:
```php
create(array $data): Order
update(int $orderId, array $data): Order
find(int $orderId): ?Order
findByCode(string $code): ?Order
findByPaymentTransactionId(string $txnId): ?Order
findLastBySubscriptionId(string $subId): ?Order
findByUser(int $userId, array $filters = []): array
updateStatus(int $orderId, string $status, string $notes = ''): bool
delete(int $orderId): bool
```

**Failure Modes**:
- Order not found → return null
- Duplicate code → retry code generation
- Invalid data → validation exception with field errors
- Database write failure → rollback transaction, log error

**Extension Points**:
- `khm_order_before_save`, `khm_order_after_save`
- `khm_order_status_changed`
- `khm_order_deleted`

---

## 4. Membership Repository

**Source Files**:
- Database: `wp_pmpro_memberships_users`
- `includes/functions.php` (pmpro_changeMembershipLevel, pmpro_hasMembershipLevel, etc.)

**Responsibilities**:
- Assign membership levels to users
- Track start dates, end dates, and statuses (active, expired, cancelled)
- Handle level changes (upgrades, downgrades, cancellations)
- Query active memberships, check expiration
- Support multiple concurrent memberships per user

**Proposed Interface**: `MembershipRepositoryInterface`

**Methods**:
```php
assign(int $userId, int $levelId, array $options = []): Membership
cancel(int $userId, int $levelId, string $reason = ''): bool
expire(int $userId, int $levelId): bool
findActive(int $userId): array
findByLevel(int $levelId): array
findExpiring(int $days = 7): array
hasAccess(int $userId, int $levelId): bool
updateEndDate(int $userId, int $levelId, ?DateTime $endDate): bool
```

**Failure Modes**:
- User already has level → handle via upgrade/downgrade logic
- Level not found → throw exception
- Database constraint violation → rollback

**Extension Points**:
- `khm_membership_assigned`
- `khm_membership_cancelled`
- `khm_membership_expired`
- `khm_membership_level_changed`

---

## 5. Email Service

**Source Files**:
- `classes/class.pmproemail.php` (lines 1-877)
- `email/*.html` templates

**Responsibilities**:
- Load templates from theme overrides or plugin defaults
- Support locale-specific templates (e.g., `email/en_US/checkout_paid.html`)
- Replace variables (`!!name!!`, `!!sitename!!`, `!!membership_level!!`)
- Apply filters for recipient, sender, subject, body, headers, attachments
- Send HTML emails via wp_mail

**Proposed Interface**: `EmailServiceInterface`

**Methods**:
```php
send(string $templateKey, string $recipient, array $data = []): bool
render(string $templateKey, array $data = []): string
setFrom(string $email, string $name): self
setHeaders(array $headers): self
addAttachment(string $filePath): self
```

**Failure Modes**:
- Template not found → fallback to default.html
- Variable not replaced → log warning, leave placeholder
- wp_mail failure → retry once, log error

**Extension Points**:
- `khm_email_template` (filter)
- `khm_email_data` (filter)
- `khm_email_subject`, `khm_email_body`, `khm_email_headers`
- `khm_email_sent` (action)

---

## 6. Content Protection API

**Source Files**:
- `includes/content.php` (pmpro_has_membership_access function)
- `includes/filters.php` (the_content, the_excerpt filters)

**Responsibilities**:
- Check if a user has access to a post, page, or custom post type
- Support level-based protection (requires level X)
- Support custom rules (date-based, tag-based, etc.)
- Provide shortcode for inline protection (`[pmpro_member level="2"]...[/pmpro_member]`)
- Display excerpts or custom messages for non-members

**Proposed Interface**: `AccessControlInterface`

**Methods**:
```php
hasAccess(int $userId, int $postId): bool
getRequiredLevels(int $postId): array
filterContent(string $content, int $postId, int $userId): string
getAccessDeniedMessage(int $postId, int $userId): string
```

**Failure Modes**:
- Post meta corrupted → default to no restriction
- Circular dependency (level A requires level B requires level A) → log error, deny access

**Extension Points**:
- `khm_has_membership_access_filter`
- `khm_has_membership_access_filter_{post_type}`
- `khm_non_member_text` (filter for denied message)

---

## 7. Export Service

**Source Files**:
- `adminpages/memberslist-csv.php`
- `adminpages/orders-csv.php`

**Responsibilities**:
- Stream CSV exports for large datasets (avoid memory limits)
- Support custom column selection
- Allow filtering by date range, status, level
- Provide hooks to extend exported fields

**Proposed Interface**: `ExportServiceInterface`

**Methods**:
```php
exportMembers(array $filters = [], array $columns = []): void
exportOrders(array $filters = [], array $columns = []): void
streamCSV(array $data, array $headers): void
```

**Failure Modes**:
- Query timeout → implement pagination or chunking
- Large datasets → stream output, flush buffers

**Extension Points**:
- `khm_members_list_csv_heading`
- `khm_members_list_csv_columns`
- `khm_orders_csv_heading`
- `khm_orders_csv_columns`

---

## 8. Discount Code Service

**Source Files**:
- Database: `wp_pmpro_discount_codes`, `wp_pmpro_discount_codes_levels`, `wp_pmpro_discount_codes_uses`
- `adminpages/discountcodes.php`

**Responsibilities**:
- Validate discount codes (active dates, usage limits)
- Apply discounts to order totals (percentage or fixed amount)
- Track code usage per user
- Support level-specific codes
- Handle one-time vs. recurring discounts

**Proposed Interface**: `DiscountCodeInterface`

**Methods**:
```php
validate(string $code, int $levelId, int $userId): Result
apply(string $code, Order $order): Order
recordUsage(string $code, int $userId, int $orderId): void
findByCode(string $code): ?DiscountCode
findActive(): array
```

**Failure Modes**:
- Code expired → return validation error
- Usage limit reached → deny with message
- Code not applicable to level → deny with message

**Extension Points**:
- `khm_discount_code_validation`
- `khm_discount_code_applied`

---

## Summary & Priority

| Component              | Priority | Complexity | Testability |
|------------------------|----------|------------|-------------|
| Gateway Abstraction    | High     | High       | Medium      |
| Webhook Verification   | High     | Medium     | High        |
| Order Repository       | High     | Medium     | High        |
| Membership Repository  | High     | Medium     | High        |
| Email Service          | Medium   | Low        | High        |
| Content Protection     | Medium   | Medium     | Medium      |
| Export Service         | Low      | Low        | High        |
| Discount Code Service  | Medium   | Medium     | High        |

**Next Steps**:
1. Implement contracts (interfaces) under `src/Contracts/`
2. Create minimal service implementations under `src/Services/`
3. Write unit tests for each service
4. Document integration patterns and extension hooks

**Trade-offs**:
- **Abstraction overhead**: More interfaces → more boilerplate, but better testability and swappability
- **Backward compatibility**: KHM won't be drop-in PMPro replacement; prioritize clean design over full parity
- **Performance**: Repository pattern adds slight query overhead; mitigate with caching layer
