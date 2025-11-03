# Phase 3: Frontend Member Experience & Advanced Features

**Start Date**: October 29, 2025  
**Status**: 🚧 In Progress  
**Priority**: High (Core membership features)

---

## 🎯 Objectives

Build out the complete member-facing experience with account management, subscription controls, and advanced features including proration and multiple memberships.

---

## 📋 Part A: Frontend Member Experience

### 1. Enhanced Account Dashboard ✅ (Base exists, needs enhancement)

**Current Status**: Basic AccountShortcode with overview/memberships/orders/profile tabs

**Enhancements Needed**:
- [ ] Real-time subscription status display (active/expiring/canceled)
- [ ] Payment method on file display
- [ ] Next billing date and amount
- [ ] Cancel subscription UI with "at period end" option
- [ ] Reactivate canceled subscription (before period end)
- [ ] Download invoices as PDF
- [ ] Membership benefits/features list per level

**Files to Create/Update**:
- `src/Public/AccountShortcode.php` (enhance existing)
- `public/css/account.css` (enhance styling)
- `public/js/account.js` (add interactivity)

---

### 2. Subscription Management UI 🆕

**Features**:
- [ ] **Cancel Subscription**
  - Cancel immediately vs. at period end
  - Confirmation modal with impact explanation
  - Retention offers/discounts (optional)
  - Success/error messaging
  
- [ ] **Reactivate Subscription**
  - Only if canceled but still in billing period
  - One-click reactivation
  - Restore original billing date
  
- [ ] **Pause Subscription** (Optional - Stripe feature)
  - Pause for 1-3 months
  - Auto-resume on date
  
- [ ] **Change Billing Frequency** (If supported by gateway)
  - Switch between monthly/quarterly/annual
  - Proration handling

**Implementation**:
```php
// REST endpoints
POST /wp-json/khm/v1/subscription/cancel
POST /wp-json/khm/v1/subscription/reactivate
POST /wp-json/khm/v1/subscription/pause
```

**Files to Create**:
- `src/Rest/SubscriptionController.php` - AJAX/REST handlers
- `src/Services/SubscriptionManagementService.php` - Business logic
- Enhanced `public/js/account.js` - Frontend interactions

---

### 3. Payment Method Updates 🆕

**Features**:
- [ ] Display current payment method (last 4 digits, card brand, expiry)
- [ ] Update payment method form (Stripe Elements)
- [ ] Handle SCA (Strong Customer Authentication) for EU
- [ ] Success confirmation with updated card details
- [ ] Email notification of payment method change

**Implementation**:
```php
// Stripe Setup Intent flow
1. Create Setup Intent on backend
2. Collect payment method with Stripe.js
3. Attach payment method to customer
4. Update default payment method on subscription
```

**Files to Create**:
- `src/Public/PaymentMethodForm.php` - Shortcode/widget for form
- `src/Rest/PaymentMethodController.php` - AJAX handlers
- `public/js/payment-method-update.js` - Stripe.js integration
- Email template: `email/payment_method_updated.html`

**Security**:
- Nonce verification
- User ownership validation
- Rate limiting (prevent abuse)
- Audit logging

---

### 4. Invoice History & Downloads 🆕

**Features**:
- [ ] Paginated list of all orders/invoices
- [ ] Status indicators (paid/pending/failed/refunded)
- [ ] Amount, date, membership level
- [ ] **Download PDF Invoice** (generate on-the-fly)
- [ ] **Resend Email Invoice** button
- [ ] Filter by status and date range

**Implementation**:
```php
// PDF Generation Options:
// Option 1: mPDF library (via Composer)
// Option 2: TCPDF
// Option 3: Dompdf
// Recommendation: mPDF for best WordPress compatibility
```

**Files to Create**:
- `src/Services/InvoicePDFGenerator.php` - PDF generation
- `templates/invoice-pdf.php` - PDF template
- Enhanced `AccountShortcode::render_orders()` - Add PDF download links
- `src/Rest/InvoiceController.php` - Download endpoint

**Route**:
```
GET /wp-json/khm/v1/invoice/{order_id}/pdf
GET /wp-json/khm/v1/invoice/{order_id}/resend
```

---

## 📋 Part B: Advanced Features

### 5. Proration Handling (Mid-Cycle Upgrades/Downgrades) 🆕

**Use Cases**:
- Member upgrades from Basic ($10/mo) to Pro ($50/mo) mid-month
- Member downgrades from Pro to Basic
- Billing cycle changes (monthly → annual)

**Proration Logic**:
```
Upgrade Example:
- Current plan: Basic $10/month, paid on Oct 1
- Upgrade to Pro $50/month on Oct 15 (halfway through month)
- Unused credit: $5 (50% of $10)
- Pro charge: $25 (50% of $50)
- Immediate charge: $25 - $5 = $20
- Next billing: Nov 15 at full $50

Downgrade Example:
- Current plan: Pro $50/month, paid on Oct 1
- Downgrade to Basic $10/month on Oct 15
- Unused credit: $25 (50% of $50)
- Basic charge: $5 (50% of $10)
- Credit applied: $20 (credit > charge)
- Next billing: Nov 15 at $10, with $20 credit remaining
```

**Implementation Steps**:
1. [ ] **Create ProrationService**
   - Calculate unused time in current cycle
   - Calculate prorated charge for new level
   - Handle credit notes and adjustments
   
2. [ ] **Extend MembershipRepository**
   - `changeMembershipLevel(user_id, new_level_id, prorate: bool)`
   - Store proration metadata in order
   
3. [ ] **Stripe Integration**
   - Use `proration_behavior: 'create_prorations'`
   - Handle `invoice.created` with proration line items
   - Apply credits to customer balance
   
4. [ ] **Admin UI**
   - Manual upgrade/downgrade button in admin
   - Show proration preview before confirming
   - Proration history in order details
   
5. [ ] **Frontend UI**
   - "Upgrade" buttons on account page
   - Proration preview modal
   - Immediate vs. end-of-cycle option

**Files to Create**:
- `src/Services/ProrationService.php`
- `src/Rest/MembershipChangeController.php`
- `src/Admin/MembershipChangeModal.php` (admin side)
- `public/js/membership-upgrade.js`
- Enhanced webhook handlers for proration events

**Webhook Events to Handle**:
- `invoice.created` (with proration line items)
- `customer.balance_transaction.created` (credits)

---

### 6. Multiple Membership Levels Per User 🆕

**Current State**: User can only have ONE active membership  
**New Capability**: User can hold MULTIPLE concurrent memberships

**Use Cases**:
- Magazine subscription + online course access
- Basic membership + add-on features
- Multiple publication subscriptions

**Schema Changes**:
```sql
-- khm_memberships_users already supports multiple rows per user
-- NO schema change needed, just update business logic

-- Add unique constraint to prevent duplicate level per user (optional)
-- Or allow duplicates if use case requires (e.g., gift + personal subscription)
```

**Implementation**:
1. [ ] **Update MembershipRepository**
   - `findActive(user_id)` already returns array ✅
   - Add `hasLevel(user_id, level_id): bool`
   - Add `addLevel(user_id, level_id)` (in addition to existing)
   - Ensure checkout doesn't replace, but adds
   
2. [ ] **Content Protection Updates**
   - Change: "Does user have level X?" → "Does user have ANY of [X, Y, Z]?"
   - Meta box: Select multiple required levels (OR logic)
   - Shortcode: `[khm_restricted levels="1,2,3"]` (any of)
   
3. [ ] **Account Page**
   - List all active memberships in table
   - Each with own cancel/manage button
   - Show combined benefits
   
4. [ ] **Checkout Flow**
   - Detect if user already has A membership
   - "You already have Basic. Add Pro for $X?" message
   - Or bundle pricing (optional)
   
5. [ ] **Order Management**
   - Each order tied to ONE membership level (existing ✅)
   - Can have multiple active orders (one per level)
   
6. [ ] **Email Updates**
   - Reflect multiple memberships in account emails
   - "Your Pro Membership" vs. "Your Memberships"

**Files to Update**:
- `src/Services/MembershipRepository.php` (logic)
- `src/Public/AccountShortcode.php` (display)
- `src/Hooks/ContentRestriction.php` (protection logic)
- `src/Public/CheckoutShortcode.php` (additive flow)

**Backwards Compatibility**:
- Existing single-membership sites: No change in behavior
- Multi-membership enabled: Opt-in via settings or filter

---

### 7. Group/Corporate Accounts (Optional/Future) 🔵

**Features** (Future Phase):
- [ ] Create "group" membership level type
- [ ] Admin can add multiple users to a group
- [ ] Billing goes to group admin/company
- [ ] Usage limits (e.g., 10 seats)
- [ ] Invitation system for team members
- [ ] Group admin dashboard

**Deferred to Phase 4** - Noted for roadmap

---

### 8. Affiliate Tracking (Optional/Future) 🔵

**Features** (Future Phase):
- [ ] Referral links with tracking codes
- [ ] Commission calculation
- [ ] Payout management
- [ ] Affiliate dashboard

**Deferred to Phase 4** - Could integrate with existing plugins (e.g., AffiliateWP)

---

## 🧪 Testing Strategy

### Unit Tests
- [ ] `ProrationServiceTest.php` - Proration calculations
- [ ] `SubscriptionManagementServiceTest.php` - Cancel/reactivate logic
- [ ] `MultiMembershipTest.php` - Multiple level assignment
- [ ] `PaymentMethodControllerTest.php` - Update flows

### Integration Tests
- [ ] Stripe proration API calls (requires test mode)
- [ ] Payment method update with Setup Intents
- [ ] PDF generation (verify output)

### E2E Tests (Playwright)
- [ ] `subscription-cancel.spec.ts` - Cancel flow
- [ ] `payment-method-update.spec.ts` - Card update
- [ ] `membership-upgrade.spec.ts` - Proration preview
- [ ] `invoice-download.spec.ts` - PDF download

---

## 📦 Dependencies

### PHP Libraries (add to composer.json)
```json
{
  "require": {
    "mpdf/mpdf": "^8.2",
    "stripe/stripe-php": "^10.0" // (already installed)
  }
}
```

### JavaScript Libraries
- Stripe.js (already used in checkout)
- No new dependencies for account page

---

## 🗂️ File Structure

```
khm-plugin/
├── src/
│   ├── Public/
│   │   ├── AccountShortcode.php ✅ (enhance)
│   │   ├── PaymentMethodForm.php 🆕
│   │   └── SubscriptionWidget.php 🆕
│   ├── Rest/
│   │   ├── SubscriptionController.php 🆕
│   │   ├── PaymentMethodController.php 🆕
│   │   ├── InvoiceController.php 🆕
│   │   └── MembershipChangeController.php 🆕
│   ├── Services/
│   │   ├── SubscriptionManagementService.php 🆕
│   │   ├── ProrationService.php 🆕
│   │   ├── InvoicePDFGenerator.php 🆕
│   │   └── MembershipRepository.php ✅ (enhance)
│   └── Webhooks/
│       └── ProrationHandler.php 🆕
├── public/
│   ├── css/
│   │   └── account.css ✅ (enhance)
│   └── js/
│       ├── account.js ✅ (enhance)
│       ├── payment-method-update.js 🆕
│       └── membership-upgrade.js 🆕
├── templates/
│   └── invoice-pdf.php 🆕
├── email/
│   ├── payment_method_updated.html 🆕
│   ├── subscription_canceled.html 🆕
│   ├── subscription_reactivated.html 🆕
│   └── membership_upgraded.html 🆕
└── tests/
    ├── ProrationServiceTest.php 🆕
    ├── SubscriptionManagementServiceTest.php 🆕
    ├── MultiMembershipTest.php 🆕
    └── e2e/specs/
        ├── subscription-cancel.spec.ts 🆕
        ├── payment-method-update.spec.ts 🆕
        └── membership-upgrade.spec.ts 🆕
```

---

## 📅 Implementation Phases

### Phase 3A: Subscription Management (Days 1-3)
**Priority: Critical**
1. SubscriptionController + AJAX endpoints
2. Cancel subscription (immediate + at period end)
3. Reactivate subscription
4. Enhanced account page UI
5. Tests + E2E

**Deliverable**: Members can cancel/reactivate their subscriptions

---

### Phase 3B: Payment Method Updates (Days 4-5)
**Priority: High**
1. Stripe Setup Intent integration
2. Payment method form with Stripe Elements
3. Update default payment method on subscription
4. Email notifications
5. Tests + E2E

**Deliverable**: Members can update their payment cards

---

### Phase 3C: Invoice PDFs (Days 6-7)
**Priority: Medium**
1. Install & configure mPDF
2. PDF template design
3. Invoice download endpoint
4. Resend invoice email
5. Tests

**Deliverable**: Members can download invoice PDFs

---

### Phase 3D: Multiple Memberships (Days 8-10)
**Priority: High**
1. Update MembershipRepository logic
2. Content protection multi-level support
3. Account page display all memberships
4. Checkout additive flow
5. Tests

**Deliverable**: Users can hold multiple membership levels

---

### Phase 3E: Proration (Days 11-14)
**Priority: Medium-High**
1. ProrationService calculations
2. Stripe proration integration
3. Upgrade/downgrade UI (admin + frontend)
4. Webhook handlers for proration events
5. Tests + E2E

**Deliverable**: Members can upgrade/downgrade with prorated billing

---

## ✅ Success Criteria

- [ ] Members can cancel subscriptions with clear UI
- [ ] Members can update payment methods securely
- [ ] Members can download invoice PDFs
- [ ] Members can hold multiple membership levels
- [ ] Upgrades/downgrades are prorated correctly
- [ ] All changes sync with Stripe
- [ ] Comprehensive test coverage (>85%)
- [ ] E2E tests pass for all user flows
- [ ] Email notifications for all account changes
- [ ] Admin can manually perform all member actions

---

## 🚀 Next Steps

**Ready to start Phase 3A: Subscription Management**

Which sub-phase would you like to tackle first?

1. **Subscription Management** (cancel/reactivate) - Most impactful
2. **Payment Method Updates** - High user demand
3. **Multiple Memberships** - Architectural foundation
4. **Proration** - Complex but valuable
5. **Invoice PDFs** - Nice-to-have polish

Or we can proceed in the order listed (3A → 3B → 3C → 3D → 3E)?
