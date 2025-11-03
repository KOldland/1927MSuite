# Manual Stripe Test Checklist (Test Mode)

This checklist covers the key scenarios: free trial, paid trial, first-payment-only discounts, and recurring discounts. Use Stripe test mode and your local/dev WordPress site.

Prerequisites
- Plugin installed and activated; checkout page with [khm_checkout]
- Stripe keys configured (test mode) and Webhook secret saved (Settings → Stripe)
- Discount codes configured in KHM admin to reflect scenarios below
- Optional: Run Stripe CLI forwarding per `tests/stripe/README.md`

General Tips
- Tag created Stripe objects with metadata: `user_id`, `membership_id` for easy cross-referencing.
- Watch the “Order Summary” block on checkout for “Due today”, “Trial”, and “First payment only” labels.
- Verify emails via the Admin Email Preview page for quick iteration (Memberships → Emails) or via actual checkout/webhooks.

## 1) Free Trial
Goal: No charge today, trial days set, first invoice after trial.

Setup
- Level: initial_payment = 0 (or discount reduces to 0), billing_amount > 0 with cycle
- Discount (optional): Trial Days = e.g. 14, Trial Amount = 0; no recurring discount

Steps
1. Go to checkout with this level.
2. Apply the free-trial discount code (if using discount to set trial).
3. Confirm Order Summary shows:
   - Due today: $0.00
   - Trial: “Free trial: 14 days”
4. Complete checkout (Stripe subscription should be created with trial_end set).
5. Verify in Stripe Dashboard:
   - Customer has a subscription with trial period
   - No initial payment intent/invoice charged
6. Verify WordPress:
   - Order stored with trial_days, trial_amount = 0
7. Verify Emails:
   - Checkout email shows trial summary and due today $0
   - No invoice email until trial ends (webhook-driven). Use Stripe CLI to trigger `invoice.paid` after trial to test renewal email.

## 2) Paid Trial
Goal: Small charge today for trial, then regular amounts recur.

Setup
- Level: initial_payment > 0 or discount sets Trial Amount = e.g. $5, Trial Days = e.g. 7

Steps
1. Checkout and apply the paid-trial code.
2. Confirm Order Summary shows:
   - Due today: $5.00
   - Trial: “Paid trial: 7 days ($5.00 due today)”
3. Complete checkout: Stripe subscription should start trial with a one-time invoice/charge.
4. Verify Stripe Dashboard:
   - Initial invoice paid for $5
   - Subscription shows trial_end
5. Verify WordPress:
   - Order has trial_days and trial_amount = 5
6. Verify Emails:
   - Checkout email shows trial summary and due today $5
   - Stripe `invoice.payment_succeeded` sends member/admin invoice emails with correct amounts and discount summary if applicable

## 3) First Payment Only Discount
Goal: Discount applies to the first (initial) charge only; renewals are full price.

Setup
- Level: recurring plan
- Discount: First Payment Only = true; discount amount set (percent or fixed)

Steps
1. Checkout and apply the first-only code.
2. Confirm Order Summary shows:
   - Due today reflects discount applied
   - First-payment-only label visible
3. Complete checkout: initial charge discounted; no recurring coupon attached to subscription
4. Verify Stripe Dashboard:
   - First invoice shows a one-time coupon/discount line (or adjusted amount)
   - Future invoices (or forecast) show full price
5. Verify WordPress:
   - Order stores discount_code, discount_amount, and first_payment_only = 1
6. Verify Emails:
   - Checkout email shows “Discount CODE applied: -$X” and “Due today” reflects the discount
   - Invoice email for the first payment includes the discount summary; renewal emails do not

## 4) Recurring Discounts
Goal: Discount applies to every renewal (percent or fixed).

Setup
- Level: recurring plan
- Discount: Recurring type = percent or amount; Recurring amount set; First Payment Only = false

Steps
1. Checkout and apply the recurring discount code.
2. Confirm Order Summary shows discounted “Due today” and trial (if present).
3. Complete checkout: subscription should have a recurring coupon attached.
4. Verify Stripe Dashboard:
   - Subscription has a coupon/promotion code
   - Invoices show a discount line each cycle
5. Verify WordPress:
   - Order stores recurring_discount_type and recurring_discount_amount
6. Verify Emails:
   - Checkout email shows recurring summary (e.g., “Recurring discount: 10% off each renewal”)
   - Renewal emails include discount summary on each webhook invoice

## Troubleshooting and Tools
- Use the Emails admin page to preview/send templates without waiting for webhooks.
- Use Stripe CLI to simulate events:
  - `stripe trigger invoice.paid`
  - `stripe trigger customer.subscription.deleted`
- Check `wp_debug.log` for email send failures (From settings or mail transport).
- Ensure Webhook Secret is correct; 400s indicate signature mismatch.
