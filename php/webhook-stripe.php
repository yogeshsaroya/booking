<?php
/**
 * SmartStayz Stripe Webhook Handler
 * Listens for Stripe events and updates booking status
 */

require_once 'config.php';
require_once 'email-functions.php';
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
    
    // Send confirmation emails to user and admin
    $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
    if (file_exists($bookingFile)) {
        $bookingData = json_decode(file_get_contents($bookingFile), true);
        sendPaymentConfirmationEmail($bookingData);
        sendAdminPaymentConfirmationEmail($bookingData);
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
        'status' => 'failed' ]);
    
    // Send payment failed emails to user and admin
    $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
    if (file_exists($bookingFile)) {
        $bookingData = json_decode(file_get_contents($bookingFile), true);
        $errorMessage = $paymentIntent->last_payment_error['message'] ?? 'Payment failed';
        sendPaymentFailedEmail($bookingData, $errorMessage);
        sendAdminPaymentFailedEmail($bookingData, $errorMessage);
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
