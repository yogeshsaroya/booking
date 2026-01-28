# SmartStayz BTCPay Server + Lightning Integration

## Overview

This guide covers integrating BTCPay Server with Lightning Network for zero-fee Bitcoin payments on SmartStayz.

**Stack:** PHP backend, jQuery/Bootstrap frontend

---

## 1. BTCPay Server Setup

### Option A: Hosted (Easiest)
Use a third-party BTCPay host:
- **Voltage.cloud** — managed Lightning + BTCPay (~$20/month)
- **LunaNode** — one-click BTCPay deploy

### Option B: Self-Hosted (More Control)
Deploy on your own VPS (DigitalOcean, Linode, etc.):

```bash
# On a fresh Ubuntu 22.04 server with 2GB+ RAM
# Requires a domain pointing to server IP

# Install BTCPay Server
wget -O btcpay-setup.sh https://raw.githubusercontent.com/btcpayserver/btcpayserver-docker/master/btcpay-setup.sh
chmod +x btcpay-setup.sh

export BTCPAY_HOST="btcpay.smartstayz.com"
export NBITCOIN_NETWORK="mainnet"
export BTCPAYGEN_CRYPTO1="btc"
export BTCPAYGEN_LIGHTNING="lnd"
export BTCPAYGEN_REVERSEPROXY="nginx"
export BTCPAYGEN_ADDITIONAL_FRAGMENTS="opt-save-storage-s"

./btcpay-setup.sh -i
```

### After Setup
1. Access BTCPay admin at `https://btcpay.smartstayz.com`
2. Create account → Create Store → Name it "SmartStayz"
3. Set up Lightning wallet (BTCPay guides you through this)
4. Go to **Store Settings → Access Tokens → Create Token**
   - Label: "SmartStayz API"
   - Permissions: `btcpay.store.cancreateinvoice`, `btcpay.store.canviewinvoices`
   - Save the API key (starts with `token_`)

---

## 2. Backend Integration (PHP)

### Config File
Create `config/btcpay.php`:

```php
<?php
return [
    'host' => 'https://btcpay.smartstayz.com',  // Your BTCPay URL
    'api_key' => 'token_xxxxxxxxxxxxxxxxxxxxxxxx',  // Your API key
    'store_id' => 'xxxxxxxxxxxxxxxxxxxxxxxx',  // From Store Settings
    'webhook_secret' => 'your-webhook-secret-here',  // Set in BTCPay webhooks
];
```

### BTCPay API Class
Create `includes/BTCPayClient.php`:

```php
<?php
class BTCPayClient {
    private $host;
    private $apiKey;
    private $storeId;

    public function __construct($config) {
        $this->host = rtrim($config['host'], '/');
        $this->apiKey = $config['api_key'];
        $this->storeId = $config['store_id'];
    }

    /**
     * Create a payment invoice
     * 
     * @param float $amount Amount in USD
     * @param string $bookingId Your internal booking reference
     * @param array $metadata Additional data to store with invoice
     * @return array Invoice data including checkout URL
     */
    public function createInvoice($amount, $bookingId, $metadata = []) {
        $data = [
            'amount' => $amount,
            'currency' => 'USD',
            'metadata' => array_merge([
                'booking_id' => $bookingId,
                'orderId' => $bookingId,
            ], $metadata),
            'checkout' => [
                'speedPolicy' => 'MediumSpeed',  // 1 confirmation
                'paymentMethods' => ['BTC', 'BTC-LightningNetwork'],
                'defaultPaymentMethod' => 'BTC-LightningNetwork',
                'expirationMinutes' => 30,
                'redirectURL' => 'https://smartstayz.com/booking/confirmed?booking=' . $bookingId,
                'redirectAutomatically' => true,
            ],
        ];

        return $this->request('POST', "/api/v1/stores/{$this->storeId}/invoices", $data);
    }

    /**
     * Get invoice status
     */
    public function getInvoice($invoiceId) {
        return $this->request('GET', "/api/v1/stores/{$this->storeId}/invoices/{$invoiceId}");
    }

    /**
     * Get invoice payment methods (for embedding checkout)
     */
    public function getPaymentMethods($invoiceId) {
        return $this->request('GET', "/api/v1/stores/{$this->storeId}/invoices/{$invoiceId}/payment-methods");
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->host . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: token ' . $this->apiKey,
            'Content-Type: application/json',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("BTCPay API error: $httpCode - $response");
        }

        return json_decode($response, true);
    }
}
```

### Create Invoice Endpoint
Create `api/create-btc-invoice.php`:

```php
<?php
require_once '../config/btcpay.php';
require_once '../includes/BTCPayClient.php';
require_once '../includes/db.php';  // Your database connection

header('Content-Type: application/json');

// Get booking details from request
$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? null;

if (!$bookingId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing booking_id']);
    exit;
}

// Fetch booking from your database
$booking = getBookingById($bookingId);  // Your function

if (!$booking) {
    http_response_code(404);
    echo json_encode(['error' => 'Booking not found']);
    exit;
}

// Calculate total (your logic)
$totalAmount = $booking['total_price'];

// Apply Bitcoin discount (optional - incentivize BTC payments)
$btcDiscount = 0.05;  // 5% discount
$discountedAmount = $totalAmount * (1 - $btcDiscount);

try {
    $btcpay = new BTCPayClient($config);
    
    $invoice = $btcpay->createInvoice(
        $discountedAmount,
        $bookingId,
        [
            'property_id' => $booking['property_id'],
            'guest_email' => $booking['guest_email'],
            'check_in' => $booking['check_in'],
            'check_out' => $booking['check_out'],
            'original_amount' => $totalAmount,
            'discount_applied' => $btcDiscount * 100 . '%',
        ]
    );

    // Save invoice ID to your database
    saveInvoiceToBooking($bookingId, $invoice['id'], 'btcpay');

    echo json_encode([
        'success' => true,
        'invoice_id' => $invoice['id'],
        'checkout_url' => $invoice['checkoutLink'],
        'amount_usd' => $discountedAmount,
        'original_amount' => $totalAmount,
        'discount' => $btcDiscount * 100 . '%',
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
```

### Webhook Handler
Create `api/btcpay-webhook.php`:

```php
<?php
require_once '../config/btcpay.php';
require_once '../includes/db.php';

// Get raw POST body
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_BTCPAY_SIG'] ?? '';

// Verify webhook signature
$expectedSig = 'sha256=' . hash_hmac('sha256', $payload, $config['webhook_secret']);
if (!hash_equals($expectedSig, $signature)) {
    http_response_code(401);
    exit('Invalid signature');
}

$event = json_decode($payload, true);

// Log all webhooks for debugging
file_put_contents('../logs/btcpay-webhooks.log', 
    date('Y-m-d H:i:s') . ' - ' . json_encode($event) . "\n", 
    FILE_APPEND
);

$invoiceId = $event['invoiceId'] ?? null;
$type = $event['type'] ?? null;

if (!$invoiceId) {
    http_response_code(400);
    exit('Missing invoiceId');
}

switch ($type) {
    case 'InvoiceSettled':
        // Payment confirmed! Mark booking as paid
        $booking = getBookingByInvoiceId($invoiceId);
        if ($booking) {
            updateBookingStatus($booking['id'], 'paid');
            sendConfirmationEmail($booking['guest_email'], $booking);
            
            // Optional: notify yourself
            sendAdminNotification("New BTC booking: {$booking['id']}");
        }
        break;

    case 'InvoiceExpired':
        // Invoice expired without payment
        $booking = getBookingByInvoiceId($invoiceId);
        if ($booking && $booking['status'] === 'pending') {
            updateBookingStatus($booking['id'], 'expired');
        }
        break;

    case 'InvoiceInvalid':
        // Payment failed or was invalid
        $booking = getBookingByInvoiceId($invoiceId);
        if ($booking) {
            updateBookingStatus($booking['id'], 'payment_failed');
        }
        break;

    case 'InvoiceProcessing':
        // Payment received, waiting for confirmations
        // Optional: update status to show payment is in progress
        break;
}

http_response_code(200);
echo 'OK';
```

---

## 3. Frontend Integration

### Checkout Button
Add to your booking confirmation page:

```html
<!-- Payment Method Selection -->
<div class="payment-methods">
    <h3>Select Payment Method</h3>
    
    <div class="payment-option" id="pay-card">
        <input type="radio" name="payment" value="card" checked>
        <label>Credit Card <span class="fee">(2.9% + $0.30 fee)</span></label>
    </div>
    
    <div class="payment-option" id="pay-btc">
        <input type="radio" name="payment" value="bitcoin">
        <label>Bitcoin (Lightning) <span class="discount">5% discount!</span></label>
    </div>
    
    <div id="btc-info" style="display: none;">
        <p>✓ Zero fees for us = savings for you</p>
        <p>✓ Instant confirmation via Lightning</p>
        <p>✓ Works with Cash App, Strike, or any Lightning wallet</p>
    </div>
    
    <button id="proceed-payment" class="btn btn-primary">
        Proceed to Payment
    </button>
</div>

<script>
$(document).ready(function() {
    const bookingId = '<?= $booking_id ?>';  // From PHP
    const totalAmount = <?= $total_amount ?>;
    
    // Show BTC info when selected
    $('input[name="payment"]').change(function() {
        if ($(this).val() === 'bitcoin') {
            $('#btc-info').slideDown();
        } else {
            $('#btc-info').slideUp();
        }
    });
    
    // Handle payment
    $('#proceed-payment').click(function() {
        const method = $('input[name="payment"]:checked').val();
        
        if (method === 'bitcoin') {
            payWithBitcoin(bookingId);
        } else {
            payWithCard(bookingId);  // Your existing Stripe flow
        }
    });
});

function payWithBitcoin(bookingId) {
    $('#proceed-payment').prop('disabled', true).text('Creating invoice...');
    
    $.ajax({
        url: '/api/create-btc-invoice.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ booking_id: bookingId }),
        success: function(response) {
            if (response.success) {
                // Option 1: Redirect to BTCPay checkout (recommended)
                window.location.href = response.checkout_url;
                
                // Option 2: Open in modal (see below for embedded checkout)
                // openBTCPayModal(response.checkout_url);
            } else {
                alert('Error creating invoice: ' + response.error);
                $('#proceed-payment').prop('disabled', false).text('Proceed to Payment');
            }
        },
        error: function(xhr) {
            alert('Error: ' + xhr.responseText);
            $('#proceed-payment').prop('disabled', false).text('Proceed to Payment');
        }
    });
}

// Optional: Embedded checkout in modal
function openBTCPayModal(checkoutUrl) {
    // BTCPay provides a modal script
    window.btcpay.showInvoice(checkoutUrl);
}
</script>

<!-- Optional: BTCPay modal script (add to head) -->
<script src="https://btcpay.smartstayz.com/modal/btcpay.js"></script>
```

### Embedded Checkout (Alternative)
For a fully embedded experience without redirect:

```html
<div id="btcpay-checkout-container"></div>

<script>
function showEmbeddedCheckout(invoiceId) {
    // Fetch payment details
    $.get('/api/get-btc-payment-info.php?invoice=' + invoiceId, function(data) {
        const lightning = data.paymentMethods.find(m => m.paymentMethod === 'BTC-LightningNetwork');
        
        $('#btcpay-checkout-container').html(`
            <div class="btc-checkout">
                <h4>Pay with Bitcoin Lightning</h4>
                <p class="amount">$${data.amount} USD</p>
                <p class="btc-amount">${lightning.amount} BTC</p>
                
                <div class="qr-code">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(lightning.destination)}" />
                </div>
                
                <p class="lightning-address">${lightning.destination.substring(0, 30)}...</p>
                
                <button onclick="copyToClipboard('${lightning.destination}')" class="btn btn-secondary">
                    Copy Lightning Invoice
                </button>
                
                <p class="expires">Expires in <span id="countdown">30:00</span></p>
                
                <div class="wallet-buttons">
                    <a href="lightning:${lightning.destination}" class="btn">Open Wallet</a>
                </div>
            </div>
        `);
        
        // Start polling for payment
        pollPaymentStatus(invoiceId);
    });
}

function pollPaymentStatus(invoiceId) {
    const poll = setInterval(function() {
        $.get('/api/check-invoice-status.php?invoice=' + invoiceId, function(data) {
            if (data.status === 'Settled' || data.status === 'Processing') {
                clearInterval(poll);
                window.location.href = '/booking/confirmed?booking=' + data.bookingId;
            } else if (data.status === 'Expired' || data.status === 'Invalid') {
                clearInterval(poll);
                alert('Payment expired or failed. Please try again.');
            }
        });
    }, 3000);  // Check every 3 seconds
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
    alert('Lightning invoice copied!');
}
</script>
```

---

## 4. Database Schema

Add these columns to your bookings table (or create a separate payments table):

```sql
ALTER TABLE bookings ADD COLUMN payment_method VARCHAR(20) DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN payment_invoice_id VARCHAR(100) DEFAULT NULL;
<!-- Database column already exists in main schema -->
<!-- ALTER TABLE bookings ADD COLUMN payment_status VARCHAR(20) DEFAULT 'pending'; -->
ALTER TABLE bookings ADD COLUMN btc_discount_applied DECIMAL(5,2) DEFAULT NULL;

-- Or create separate payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    payment_method ENUM('stripe', 'btcpay', 'manual') NOT NULL,
    invoice_id VARCHAR(100),
    amount_usd DECIMAL(10,2) NOT NULL,
    amount_btc DECIMAL(16,8) DEFAULT NULL,
    status ENUM('pending', 'processing', 'paid', 'expired', 'failed') DEFAULT 'pending',
    discount_percent DECIMAL(5,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    webhook_data JSON,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
```

---

## 5. BTCPay Webhook Setup

In BTCPay Server admin:

1. Go to **Store Settings → Webhooks**
2. Click **Create Webhook**
3. Configure:
   - Payload URL: `https://smartstayz.com/api/btcpay-webhook.php`
   - Secret: Generate a random string, save it in your config
   - Events: Select all invoice events
4. Save

---

## 6. Testing

### Test Mode
1. In BTCPay, create a test store on **testnet**
2. Use testnet Lightning wallets (e.g., Phoenix testnet, or BTCPay's internal wallet)
3. Get testnet BTC from faucets

### Test Checklist
- [ ] Invoice creates successfully
- [ ] QR code displays and is scannable
- [ ] Lightning payment completes in < 5 seconds
- [ ] Webhook fires and updates booking status
- [ ] Confirmation email sends
- [ ] Expired invoices handled correctly
- [ ] Edge case: partial payment (BTCPay handles this)

---

## 7. Going Live

1. Switch BTCPay to **mainnet**
2. Set up Lightning liquidity (open channels or use LSP)
3. Update config to production API key
4. Test with small real payment
5. Monitor webhook logs for first few bookings

---

## Summary

| Component | File |
|-----------|------|
| Config | `config/btcpay.php` |
| API Client | `includes/BTCPayClient.php` |
| Create Invoice | `api/create-btc-invoice.php` |
| Webhook Handler | `api/btcpay-webhook.php` |
| Frontend | Checkout page JS |

**Questions for your developer:**
- Where is the current checkout flow? (to integrate payment selection)
- What's the database structure for bookings?
- Any existing payment processing to reference?

---

## Resources

- [BTCPay Server Docs](https://docs.btcpayserver.org/)
- [BTCPay API Reference](https://docs.btcpayserver.org/API/Greenfield/v1/)
- [Lightning Network Basics](https://lightning.network/)