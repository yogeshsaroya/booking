<?php
/**
 * SmartStayz Stripe Webhook Handler
 * Listens for Stripe events and updates booking status
 */

require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\WebhookEndpoint;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

// Get webhook signing secret
$webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET');

if (!$webhookSecret) {
    http_response_code(400);
    echo json_encode(['error' => 'Webhook secret not configured']);
    exit;
}

// Get raw request body
$input = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Verify webhook signature
try {
    $event = \Stripe\Webhook::constructEvent($input, $sigHeader, $webhookSecret);
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    logMessage("Webhook signature verification failed: " . $e->getMessage(), 'ERROR');
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    logMessage("Webhook signature verification failed: " . $e->getMessage(), 'ERROR');
    exit;
}

// Handle the event
switch ($event->type) {
    case 'payment_intent.succeeded':
        handlePaymentIntentSucceeded($event->data->object);
        break;
        
    case 'payment_intent.payment_failed':
        handlePaymentIntentFailed($event->data->object);
        break;
        
    case 'payment_intent.canceled':
        handlePaymentIntentCanceled($event->data->object);
        break;
        
    default:
        // Ignore other events
        break;
}

// Return success response
http_response_code(200);
echo json_encode(['received' => true]);

/**
 * Handle successful payment
 */
function handlePaymentIntentSucceeded($paymentIntent) {
    $bookingId = $paymentIntent->metadata['booking_id'] ?? null;
    
    if (!$bookingId) {
        logMessage("Webhook: No booking_id in payment intent metadata", 'WARNING');
        return;
    }
    
    logMessage("Webhook: Payment succeeded for booking $bookingId", 'INFO');
    
    // Update booking status
    updateBooking($bookingId, [
        'status' => 'confirmed',
        'stripe_payment_intent' => $paymentIntent->id
    ]);
    
    // Send confirmation email
    $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
    if (file_exists($bookingFile)) {
        $bookingData = json_decode(file_get_contents($bookingFile), true);
        sendConfirmationEmail($bookingData);
    }
}

/**
 * Handle failed payment
 */
function handlePaymentIntentFailed($paymentIntent) {
    $bookingId = $paymentIntent->metadata['booking_id'] ?? null;
    
    if (!$bookingId) {
        logMessage("Webhook: No booking_id in payment intent metadata", 'WARNING');
        return;
    }
    
    logMessage("Webhook: Payment failed for booking $bookingId", 'WARNING');
    
    // Update booking status
    updateBooking($bookingId, [
        'status' => 'failed',
        'payment_error' => $paymentIntent->last_payment_error['message'] ?? 'Payment failed'
    ]);
    
    // Send payment failed email
    $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
    if (file_exists($bookingFile)) {
        $bookingData = json_decode(file_get_contents($bookingFile), true);
        sendPaymentFailedEmail($bookingData, $paymentIntent->last_payment_error['message'] ?? 'Payment failed');
    }
}

/**
 * Handle canceled payment
 */
function handlePaymentIntentCanceled($paymentIntent) {
    $bookingId = $paymentIntent->metadata['booking_id'] ?? null;
    
    if (!$bookingId) {
        logMessage("Webhook: No booking_id in payment intent metadata", 'WARNING');
        return;
    }
    
    logMessage("Webhook: Payment canceled for booking $bookingId", 'INFO');
    
    // Update booking status
    updateBooking($bookingId, [
        'status' => 'canceled'
    ]);
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
            
            $stmt = $pdo->prepare("UPDATE bookings SET $setClause, updated_at = NOW() WHERE booking_id = :booking_id");
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
        $bookingData['updated_at'] = date('Y-m-d H:i:s');
        file_put_contents($bookingFile, json_encode($bookingData, JSON_PRETTY_PRINT));
    }
}

/**
 * Send confirmation email
 */
function sendConfirmationEmail($bookingData) {
    require_once 'MailHandler.php';
    require_once 'config.php';
    
    $mailHandler = new MailHandler();
    $property = PROPERTIES[$bookingData['property']] ?? ['name' => 'Unknown Property'];
    $propertyName = is_array($property) ? $property['name'] : $property;
    $firstName = $bookingData['first_name'] ?? '';
    $lastName = $bookingData['last_name'] ?? '';
    $bookingId = $bookingData['booking_id'] ?? '';
    $checkIn = $bookingData['check_in'] ?? '';
    $checkOut = $bookingData['check_out'] ?? '';
    
    $emailBody = "
        <h2>✅ Payment Confirmed - Booking Complete!</h2>
        <p>Hello {$firstName} {$lastName},</p>
        <p><strong>Great news!</strong> Your payment has been successfully processed and your booking is now confirmed.</p>
        
        <h3>Booking Details</h3>
        <ul>
            <li><strong>Booking ID:</strong> {$bookingId}</li>
            <li><strong>Property:</strong> {$propertyName}</li>
            <li><strong>Check-in:</strong> {$checkIn}</li>
            <li><strong>Check-out:</strong> {$checkOut}</li>
            <li><strong>Guests:</strong> {$bookingData['guests']}</li>
            <li><strong>Total Amount Paid:</strong> \${$bookingData['amount']}</li>
        </ul>
        
        <p><strong>What's Next?</strong></p>
        <p>You will receive check-in instructions 24-48 hours before your arrival. If you have any questions or special requests, please contact us.</p>
        
        <p>We look forward to hosting you!</p>
        <p>Best regards,<br>SmartStayz Team</p>
    ";
    
    $mailHandler->send(
        $bookingData['email'],
        "Payment Confirmed - {$propertyName}",
        $emailBody
    );
}

/**
 * Send payment failed email
 */
function sendPaymentFailedEmail($bookingData, $errorMessage) {
    require_once 'MailHandler.php';
    require_once 'config.php';
    
    $mailHandler = new MailHandler();
    $property = PROPERTIES[$bookingData['property']] ?? ['name' => 'Unknown Property'];
    $propertyName = is_array($property) ? $property['name'] : $property;
    $firstName = $bookingData['first_name'] ?? '';
    $lastName = $bookingData['last_name'] ?? '';
    $bookingId = $bookingData['booking_id'] ?? '';
    $checkIn = $bookingData['check_in'] ?? '';
    $checkOut = $bookingData['check_out'] ?? '';
    
    $emailBody = "
        <h2>❌ Payment Failed</h2>
        <p>Hello {$firstName} {$lastName},</p>
        <p>Unfortunately, your payment for <strong>{$propertyName}</strong> could not be processed.</p>
        
        <h3>Booking Details</h3>
        <ul>
            <li><strong>Booking ID:</strong> {$bookingId}</li>
            <li><strong>Property:</strong> {$propertyName}</li>
            <li><strong>Check-in:</strong> {$checkIn}</li>
            <li><strong>Check-out:</strong> {$checkOut}</li>
            <li><strong>Amount:</strong> \${$bookingData['amount']}</li>
        </ul>
        
        <p><strong>Error:</strong> {$errorMessage}</p>
        
        <p><strong>What Should You Do?</strong></p>
        <ul>
            <li>Check your payment method details and try again</li>
            <li>Try a different payment method</li>
            <li>Contact your bank if the issue persists</li>
            <li>Contact us for assistance</li>
        </ul>
        
        <p>Please try booking again or contact us if you need help.</p>
        <p>Best regards,<br>SmartStayz Team</p>
    ";
    
    $mailHandler->send(
        $bookingData['email'],
        "Payment Failed - {$propertyName}",
        $emailBody
    );
}

/**
 * Get database connection
 */
function getDBConnection() {
    try {
        $pdo = new \PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (\PDOException $e) {
        logMessage("Database connection failed: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * Log messages
 */
function logMessage($message, $level = 'INFO') {
    $logFile = __DIR__ . '/logs/webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND);
}
