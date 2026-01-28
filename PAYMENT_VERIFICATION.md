# Payment Verification System

This document explains how the 3-tier payment verification system works.

## Overview

Payment confirmation uses a 3-tier system to ensure bookings are always confirmed, even if one method fails:

```
┌─────────────────────────────────────────────────────────────┐
│ User Completes Payment                                      │
└─────────────┬───────────────────────────────────────────────┘
              │
              ├─→ TIER 1: Client-Side Confirmation
              │   └─ Browser calls confirm_payment endpoint
              │   └ Updates status to 'confirmed' immediately
              │
              ├─→ TIER 2: Stripe Webhook (Real-time)
              │   └─ Stripe sends payment_intent.succeeded event
              │   └ Server automatically updates booking
              │   └ Catches if client-side fails
              │
              └─→ TIER 3: Scheduled Verification (Backup)
                  └─ Cron job runs every 5-10 minutes
                  └─ Verifies all pending payments against Stripe
                  └─ Catches if webhook delivery failed
```

## Implementation

### 1. Client-Side Confirmation (Tier 1)
**File:** `js/booking.js` → `processStripePayment()`

When Stripe confirms payment succeeded:
```javascript
if (paymentIntent.status === 'succeeded') {
    // Call confirm_payment endpoint
    await fetch('php/payment-handler.php', {
        body: JSON.stringify({
            action: 'confirm_payment',
            bookingId: result.bookingId,
            paymentIntentId: paymentIntent.id
        })
    });
}
```

**Pros:** Instant confirmation, better UX
**Cons:** Can fail if browser closes before request completes

### 2. Stripe Webhook (Tier 2)
**File:** `php/webhook-stripe.php`

Stripe sends webhook events to: `https://yourdomain.com/php/webhook-stripe.php`

Handles events:
- `payment_intent.succeeded` → Updates booking to 'confirmed'
- `payment_intent.payment_failed` → Updates booking to 'failed'
- `payment_intent.canceled` → Updates booking to 'canceled'

**Setup Instructions:**

1. **Get Webhook Signing Secret:**
   - Go to Stripe Dashboard → Developers → Webhooks
   - Click "Add Endpoint"
   - Enter URL: `https://yourdomain.com/php/webhook-stripe.php`
   - Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled`
   - Copy the signing secret (starts with `whsec_`)

2. **Add to .env file:**
   ```
   STRIPE_WEBHOOK_SECRET=whsec_test_xxxxxxxxxxxxx
   ```

3. **Test Webhook (in development):**
   ```bash
   # Using Stripe CLI
   stripe listen --forward-to localhost:8888/joseph/booking/php/webhook-stripe.php
   ```

**Pros:** Real-time, server-side, most reliable
**Cons:** Requires Stripe webhook delivery (usually very reliable)

### 3. Scheduled Verification (Tier 3)
**File:** `php/verify-pending-payments.php`

A background cron job that:
- Runs every 5-10 minutes
- Finds all pending bookings with Stripe PaymentIntent ID
- Checks status against Stripe API
- Confirms any that actually succeeded
- Updates failed bookings appropriately

**Setup Instructions:**

1. **Set up cron job:**
   ```bash
   # Edit crontab
   crontab -e
   
   # Add this line to run every 5 minutes:
   */5 * * * * php /Applications/MAMP/htdocs/joseph/booking/php/verify-pending-payments.php
   
   # Or every 10 minutes:
   */10 * * * * php /Applications/MAMP/htdocs/joseph/booking/php/verify-pending-payments.php
   ```

   php /home/smartstayz/booking/php/verify-pending-payments.php

2. **Or use a web-based scheduler** (if you don't have cron access):
   - Set up an external service like Easycron or EasyCron
   - Configure it to call: `https://yourdomain.com/php/verify-pending-payments.php` every 5-10 minutes

**Logs:** Check `php/logs/verify-payments.log` to see verification results

## Verification Flow

When a Stripe payment is made:

### Success Scenario:
1. ✅ **Tier 1 succeeds** → Booking status = 'confirmed' (immediate)
2. ✅ **Tier 2 webhook arrives** → Updates booking, sends email
3. ✅ **Tier 3 cron runs** → Verifies and confirms (backup)

### Webhook Failure Scenario:
1. ✅ **Tier 1 succeeds** → Booking status = 'confirmed' (immediate)
2. ❌ **Tier 2 webhook fails** → Never arrives
3. ✅ **Tier 3 cron runs** → Detects mismatch, confirms booking (catches it!)

### Network Issue Scenario:
1. ❌ **Tier 1 fails** → Browser closes, request lost
2. ✅ **Tier 2 webhook succeeds** → Confirms booking
3. ✅ **Tier 3 cron verifies** → Double-checks confirmation

## Database Updates

All methods update the same booking record:

```sql
UPDATE bookings SET 
    status = 'confirmed',
    stripe_payment_intent = 'pi_xxxxx',
    confirmed_at = NOW(),
    updated_at = NOW()
WHERE booking_id = ?
```

## Manual Verification (Admin)

You can manually verify a specific payment by calling:

```bash
curl -X POST https://yourdomain.com/php/payment-handler.php \
  -H "Content-Type: application/json" \
  -d '{
    "action": "confirm_payment",
    "bookingId": "BOOKING_ID",
    "paymentIntentId": "pi_xxxxx"
  }'
```

## Monitoring

Check logs for verification status:

- **Webhook logs:** `php/logs/webhook.log`
- **Verification logs:** `php/logs/verify-payments.log`
- **General logs:** `php/logs/app.log`

## Edge Cases Handled

| Scenario | Tier 1 | Tier 2 | Tier 3 | Result |
|----------|--------|--------|--------|--------|
| Normal payment | ✅ | ✅ | ✅ | Confirmed |
| Browser closes | ❌ | ✅ | ✅ | Confirmed (webhook) |
| Webhook fails | ✅ | ❌ | ✅ | Confirmed (cron) |
| All fail initially | ❌ | ❌ | ✅ | Confirmed (cron catches up) |
| Network timeout | ❌ | ✅ | ✅ | Confirmed (webhook) |

## Troubleshooting

### Bookings stuck in 'pending':
1. Check webhook logs: `php/logs/webhook.log`
2. Check verification logs: `php/logs/verify-payments.log`
3. Manually run verification: `php php/verify-pending-payments.php`
4. Check Stripe Dashboard to verify payment actually succeeded

### Webhook not working:
1. Verify webhook secret is correct in .env
2. Check webhook endpoint URL in Stripe Dashboard
3. Test with Stripe CLI: `stripe listen --forward-to localhost:8888/...`
4. Verify payment-handler.php is accessible

### Cron not running:
1. Check crontab: `crontab -l`
2. Verify file permissions: `chmod +x php/verify-pending-payments.php`
3. Check system logs: `log show --predicate 'process == "cron"'` (macOS)
4. Use web-based scheduler as alternative
