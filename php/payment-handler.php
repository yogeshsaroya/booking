<?php
/**
 * SmartStayz Payment Handler
 * Processes payments via Stripe, Bitcoin (BTCPay), and manual methods
 */

require_once 'config.php';
require_once 'MailHandler.php';
require_once __DIR__ . '/../vendor/autoload.php'; // Composer autoload for Stripe SDK

use Stripe\Stripe;
use Stripe\PaymentIntent;

header('Content-Type: application/json');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$action = $data['action'] ?? null;

try {
    // Check if Stripe key is configured
    if (!STRIPE_SECRET_KEY) {
        throw new Exception('Stripe API key not configured. Please check your .env file.');
    }
    
    switch ($action) {
        case 'create_payment_intent':
            handleStripePayment($data);
            break;
            
        case 'confirm_payment':
            handlePaymentConfirmation($data);
            break;
            
        case 'create_bitcoin_booking':
            handleBitcoinBooking($data);
            break;
            
        case 'create_manual_booking':
            handleManualBooking($data);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logMessage("Payment error: " . $e->getMessage(), 'ERROR');
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle Stripe payment
 */
function handleStripePayment($data) {
    Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Validate booking data
    validateBookingData($data);
    
    // Check availability
    if (!isDateRangeAvailable($data['property'], $data['checkIn'], $data['checkOut'])) {
        throw new Exception('Selected dates are no longer available');
    }
    
    // Create booking record
    $bookingId = createBooking($data, 'pending');
    
    // Create Stripe PaymentIntent
    $paymentIntent = PaymentIntent::create([
        'amount' => $data['amount'] * 100, // Convert to cents
        'currency' => 'usd',
        'payment_method_types' => ['card'],
        'description' => "SmartStayz - {$data['property']} - " . 
                        "{$data['checkIn']} to {$data['checkOut']}",
        'metadata' => [
            'booking_id' => $bookingId,
            'property' => $data['property'],
            'check_in' => $data['checkIn'],
            'check_out' => $data['checkOut'],
            'guest_name' => "{$data['firstName']} {$data['lastName']}",
            'guest_email' => $data['email']
        ],
        'receipt_email' => $data['email']
    ]);
    
    // Update booking with payment intent ID
    updateBooking($bookingId, ['stripe_payment_intent' => $paymentIntent->id]);
    
    echo json_encode([
        'success' => true,
        'clientSecret' => $paymentIntent->client_secret,
        'bookingId' => $bookingId
    ]);
}

/**
 * Handle payment confirmation (after successful Stripe payment)
 */
function handlePaymentConfirmation($data) {
    $bookingId = $data['bookingId'] ?? null;
    $paymentIntentId = $data['paymentIntentId'] ?? null;
    
    if (!$bookingId) {
        throw new Exception('Booking ID is required');
    }
    
    // Update booking status to confirmed
    updateBooking($bookingId, [
        'status' => 'confirmed',
        'stripe_payment_intent' => $paymentIntentId
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking confirmed'
    ]);
}

/**
 * Handle Bitcoin booking
 */
function handleBitcoinBooking($data) {
    // Validate booking data
    validateBookingData($data);
    
    // Check availability
    if (!isDateRangeAvailable($data['property'], $data['checkIn'], $data['checkOut'])) {
        throw new Exception('Selected dates are no longer available');
    }
    
    // Create booking record
    $bookingId = createBooking($data, 'pending_bitcoin');
    
    // Create BTCPay invoice (if BTCPay is configured)
    $invoiceUrl = null;
    if (defined('BTCPAY_SERVER_URL') && BTCPAY_SERVER_URL) {
        try {
            $invoiceUrl = createBTCPayInvoice($data, $bookingId);
            updateBooking($bookingId, ['bitcoin_invoice_url' => $invoiceUrl]);
        } catch (Exception $e) {
            logMessage("BTCPay invoice creation failed: " . $e->getMessage(), 'WARNING');
        }
    }
    
    // Send email with payment instructions
    sendBitcoinPaymentEmail($data, $bookingId, $invoiceUrl);
    
    echo json_encode([
        'success' => true,
        'bookingId' => $bookingId,
        'invoiceUrl' => $invoiceUrl
    ]);
}

/**
 * Handle manual payment booking (Venmo/CashApp)
 */
function handleManualBooking($data) {
    // Validate booking data
    validateBookingData($data);
    
    // Check availability
    if (!isDateRangeAvailable($data['property'], $data['checkIn'], $data['checkOut'])) {
        throw new Exception('Selected dates are no longer available');
    }
    
    // Create booking record
    $bookingId = createBooking($data, 'pending_manual');
    
    // Send email with payment instructions
    sendManualPaymentEmail($data, $bookingId);
    
    echo json_encode([
        'success' => true,
        'bookingId' => $bookingId
    ]);
}

/**
 * Create BTCPay invoice
 */
function createBTCPayInvoice($data, $bookingId) {
    $invoiceData = [
        'storeId' => BTCPAY_STORE_ID,
        'amount' => $data['amount'],
        'currency' => 'USD',
        'checkout' => [
            'redirectURL' => "https://smartstayz.com/confirmation.html?booking=$bookingId",
            'redirectAutomatically' => false
        ],
        'metadata' => [
            'orderId' => $bookingId,
            'itemDesc' => "SmartStayz - {$data['property']}",
            'buyerName' => "{$data['firstName']} {$data['lastName']}",
            'buyerEmail' => $data['email']
        ]
    ];
    
    $ch = curl_init(BTCPAY_SERVER_URL . '/api/v1/invoices');
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($invoiceData),
        CURLOPT_HTTPHEADER => [
            'Authorization: token ' . BTCPAY_API_KEY,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('BTCPay invoice creation failed');
    }
    
    $result = json_decode($response, true);
    return $result['checkoutLink'] ?? null;
}

/**
 * Validate booking data
 */
function validateBookingData($data) {
    $required = ['property', 'checkIn', 'checkOut', 'firstName', 'lastName', 'email', 'phone', 'amount'];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate property
    if (!isset(PROPERTIES[$data['property']])) {
        throw new Exception('Invalid property');
    }
    
    // Validate dates
    $checkIn = strtotime($data['checkIn']);
    $checkOut = strtotime($data['checkOut']);
    
    if ($checkIn >= $checkOut) {
        throw new Exception('Invalid date range');
    }
    
    if ($checkIn < strtotime('today')) {
        throw new Exception('Check-in date must be in the future');
    }
}

/**
 * Check if date range is available
 */
function isDateRangeAvailable($propertyId, $checkIn, $checkOut) {
    // Get blocked dates from calendar
    $ch = curl_init("http://{$_SERVER['HTTP_HOST']}/php/calendar-sync.php?property=$propertyId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (!$result || !$result['success']) {
        // If we can't check availability, allow booking but notify admin
        logMessage("Could not verify availability for $propertyId", 'WARNING');
        return true;
    }
    
    $blockedDates = $result['blockedDates'];
    
    // Check each date in range
    $current = new DateTime($checkIn);
    $end = new DateTime($checkOut);
    
    while ($current < $end) {
        $dateString = $current->format('Y-m-d');
        if (in_array($dateString, $blockedDates)) {
            return false;
        }
        $current->modify('+1 day');
    }
    
    return true;
}

/**
 * Create booking record
 */
function createBooking($data, $status) {
    $bookingId = uniqid('BOOK-', true);
    
    $bookingData = [
        'booking_id' => $bookingId,
        'property' => $data['property'],
        'check_in' => $data['checkIn'],
        'check_out' => $data['checkOut'],
        'nights' => $data['nights'],
        'guests' => $data['guests'],
        'first_name' => $data['firstName'],
        'last_name' => $data['lastName'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'has_pets' => $data['hasPets'] ? 1 : 0,
        'special_requests' => $data['specialRequests'] ?? '',
        'payment_method' => $data['paymentMethod'],
        'amount' => $data['amount'],
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Try to save to database if available
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $columns = implode(', ', array_keys($bookingData));
            $placeholders = ':' . implode(', :', array_keys($bookingData));
            
            $stmt = $pdo->prepare("INSERT INTO bookings ($columns) VALUES ($placeholders)");
            $stmt->execute($bookingData);
        }
    } catch (Exception $e) {
        logMessage("Database insert failed: " . $e->getMessage(), 'WARNING');
    }
    
    // Also save to file as backup
    $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
    $bookingDir = dirname($bookingFile);
    
    if (!file_exists($bookingDir)) {
        mkdir($bookingDir, 0755, true);
    }
    
    file_put_contents($bookingFile, json_encode($bookingData, JSON_PRETTY_PRINT));
    
    // Send notification to admin
    sendAdminNotification($bookingData);
    
    return $bookingId;
}

/**
 * Update booking record
 */
function updateBooking($bookingId, $updates) {
    // Update database
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $setParts = [];
            foreach (array_keys($updates) as $key) {
                $setParts[] = "$key = :$key";
            }
            $setClause = implode(', ', $setParts);
            
            $stmt = $pdo->prepare("UPDATE bookings SET $setClause WHERE booking_id = :booking_id");
            $updates['booking_id'] = $bookingId;
            $stmt->execute($updates);
        }
    } catch (Exception $e) {
        logMessage("Database update failed: " . $e->getMessage(), 'WARNING');
    }
    
    // Update file
    $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
    if (file_exists($bookingFile)) {
        $bookingData = json_decode(file_get_contents($bookingFile), true);
        $bookingData = array_merge($bookingData, $updates);
        file_put_contents($bookingFile, json_encode($bookingData, JSON_PRETTY_PRINT));
    }
}

/**
 * Send Bitcoin payment email
 */
function sendBitcoinPaymentEmail($data, $bookingId, $invoiceUrl = null) {
    $property = PROPERTIES[$data['property']];
    
    $message = "
    <h2>Bitcoin Payment Instructions</h2>
    <p>Dear {$data['firstName']},</p>
    <p>Thank you for booking {$property['name']}!</p>
    
    <h3>Booking Details:</h3>
    <ul>
        <li>Booking ID: $bookingId</li>
        <li>Property: {$property['name']}</li>
        <li>Check-in: {$data['checkIn']}</li>
        <li>Check-out: {$data['checkOut']}</li>
        <li>Total Amount: \${$data['amount']}</li>
    </ul>
    
    <h3>Payment Instructions:</h3>
    ";
    
    if ($invoiceUrl) {
        $message .= "<p><a href='$invoiceUrl'>Click here to pay with Bitcoin</a></p>";
    } else {
        $message .= "<p>We will send you Bitcoin payment details within 1 hour.</p>";
    }
    
    $message .= "
    <p>Your booking will be confirmed once payment is received.</p>
    <p>If you have any questions, please contact us.</p>
    <p>Best regards,<br>SmartStayz Team</p>
    ";
    
    $mailHandler = new MailHandler();
    $mailHandler->send($data['email'], "Bitcoin Payment Instructions - $bookingId", $message);
}

/**
 * Send manual payment email
 */
function sendManualPaymentEmail($data, $bookingId) {
    $property = PROPERTIES[$data['property']];
    
    $message = "
    <h2>Payment Instructions</h2>
    <p>Dear {$data['firstName']},</p>
    <p>Thank you for booking {$property['name']}!</p>
    
    <h3>Booking Details:</h3>
    <ul>
        <li>Booking ID: $bookingId</li>
        <li>Property: {$property['name']}</li>
        <li>Check-in: {$data['checkIn']}</li>
        <li>Check-out: {$data['checkOut']}</li>
        <li>Total Amount: \${$data['amount']}</li>
    </ul>
    
    <h3>Payment Methods:</h3>
    <p><strong>Venmo:</strong> " . VENMO_USERNAME . "</p>
    <p><strong>CashApp:</strong> " . CASHAPP_USERNAME . "</p>
    
    <p><strong>Important:</strong> Please include your booking ID ($bookingId) in the payment note.</p>
    
    <p>Your booking will be confirmed once we receive and verify your payment.</p>
    <p>If you have any questions, please contact us.</p>
    <p>Best regards,<br>SmartStayz Team</p>
    ";
    
    sendEmail($data['email'], "Payment Instructions - $bookingId", $message);
}

/**
 * Send admin notification
 */
function sendAdminNotification($bookingData) {
    $property = PROPERTIES[$bookingData['property']];
    
    $message = "
    <h2>New Booking Received</h2>
    <ul>
        <li>Booking ID: {$bookingData['booking_id']}</li>
        <li>Property: {$property['name']}</li>
        <li>Guest: {$bookingData['first_name']} {$bookingData['last_name']}</li>
        <li>Email: {$bookingData['email']}</li>
        <li>Phone: {$bookingData['phone']}</li>
        <li>Check-in: {$bookingData['check_in']}</li>
        <li>Check-out: {$bookingData['check_out']}</li>
        <li>Guests: {$bookingData['guests']}</li>
        <li>Pets: " . ($bookingData['has_pets'] ? 'Yes' : 'No') . "</li>
        <li>Payment Method: {$bookingData['payment_method']}</li>
        <li>Amount: \${$bookingData['amount']}</li>
        <li>Status: {$bookingData['status']}</li>
    </ul>
    
    <h3>Special Requests:</h3>
    <p>{$bookingData['special_requests']}</p>
    
    <h3>Important:</h3>
    <p style='color: red; font-weight: bold;'>
        Remember to manually block these dates on Airbnb to prevent double bookings!
    </p>
    ";
    
    $mailHandler = new MailHandler();
    $mailHandler->send($_ENV['ADMIN_EMAIL'], "New Booking: {$bookingData['booking_id']}", $message);
}
