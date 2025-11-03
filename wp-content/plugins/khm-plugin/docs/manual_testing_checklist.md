# Manual Testing Checklist (Stripe Webhooks & Membership States)

Use this list once automated tests pass to ensure real webhooks and emails behave correctly.

## Prerequisites
- WordPress instance running locally with KHM plugin activated
- Stripe test keys configured; webhook secret saved in settings
- Stripe CLI installed and authenticated (`stripe login`)
- Terminal at project root

## Webhook Verification
1. **Start webhook forwarder**
   ```bash
   stripe listen --forward-to https://your-site.com/wp-json/khm/v1/webhooks/stripe
   ```
2. **Trigger payment failure**
   ```bash
   stripe trigger invoice.payment_failed
   ```
   - Check `wp_khm_membership_orders` for `status = failed`, `failure_code/message` populated
   - Confirm membership status flips to `past_due` in `wp_khm_memberships_users`
   - Verify `billing_failure` and `billing_failure_admin` emails received
3. **Trigger charge refund**
   ```bash
   stripe trigger charge.refunded
   ```
   - Order status becomes `refunded`, `refund_amount/reason` set
   - Membership cancelled when refund covers full total
   - Admin receives `charge_refunded_admin` email
4. **Trigger subscription update/cancel**
   ```bash
   stripe trigger customer.subscription.updated
   stripe trigger customer.subscription.deleted
   ```
   - Billing profile reflects new plan/intervals
   - Membership status updates (`active`, `cancelled`, `past_due`) accordingly
   - Admin notification for deletions

## Checkout / Trial Scenarios (Optional)
- Follow `docs/MANUAL_STRIPE_TESTS.md` for free trial, paid trial, and first-payment-only discount flows.
- After each checkout, run relevant webhook triggers to simulate renewals/failures.

## Admin Order Management
1. Load the **Orders › Detail** screen for a Stripe-backed order and trigger the “Record Refund” admin action.
   - Confirm the UI surfaces discount/trial summaries without notices.
   - Verify the refund adds a timeline note and fires webhook-driven status changes after Stripe processes it.

## Admin Discount Codes
1. Create or edit a discount code with a multi-select level assignment.
   - Ensure previously saved level selections are pre-populated on edit.
   - Save changes and confirm the list table reflects the updated levels and status without manual database edits.
2. Use the search box to locate the new code by name and verify pagination still lists other codes.

## Admin Members
1. View a member record and add/delete admin notes; confirm saved notes persist after reload.
2. Export a CSV subset from the members list and confirm the downloaded data contains expected columns.

## Admin Dashboard
1. Load the Membership Dashboard and verify active members/new members/revenue/failed payments metrics render without errors.

## Account Subscription Actions
_Automated coverage now exercises the core cancel/reactivate/payment-method flows (see `npm run test:e2e`), but keep these manual checks for real Stripe/WordPress interactions._
1. From the member account page, cancel a Stripe-backed subscription via the cancel button in both “end of period” and “immediate” modes.
   - Confirm the API response succeeds and admin logs record the status change.
   - Verify the subscription reactivation button clears the cancel_at_period_end flag.
2. Update the saved payment method through the account UI:
   - Generate a Setup Intent and complete the Stripe Elements flow.
   - Ensure the card metadata (brand/last4/expiry) refreshes on the most recent order.
3. Run the account page in a real browser session (or Stripe test mode) to exercise cancel/reactivate and card update flows end-to-end, noting any console/network errors for follow-up automation.

## Logging & Cleanup
- Monitor `wp-content/debug.log` (if WP_DEBUG enabled) for errors
- Reset orders/memberships between passes if needed via admin tools or database
