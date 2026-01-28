<?php
/**
 * SmartStayz Payment Verification Cron Job
 * Verifies pending payments against Stripe to catch any missed webhooks
 * Run this via cron job every 5-10 minutes: * /5 * * * * php /path/to/verify-pending-payments.php
 */

require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

logMessage("=== Starting Payment Verification ===", 'INFO');

try {
    // Get all pending bookings with Stripe payment intent
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    
    $stmt = $pdo->prepare("
        SELECT booking_id, stripe_payment_intent 
        FROM bookings 
        WHERE status = 'pending' 
        AND payment_status = 'pending'
        AND payment_method = 'stripe'
        AND stripe_payment_intent IS NOT NULL
        AND stripe_payment_intent != ''
        LIMIT 50
    ");
    $stmt->execute();
    $pendingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    logMessage("Found " . count($pendingBookings) . " pending bookings to verify", 'INFO');
    
    foreach ($pendingBookings as $booking) {
        $bookingId = $booking['booking_id'];
        $paymentIntentId = $booking['stripe_payment_intent'];
        
        try {
            // Fetch payment intent from Stripe
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            logMessage("Checking booking $bookingId - PaymentIntent: $paymentIntentId - Status: {$paymentIntent->status}", 'INFO');
            
            // If payment succeeded, update booking
            if ($paymentIntent->status === 'succeeded') {
                logMessage("Payment verified for booking $bookingId - Updating status to confirmed", 'INFO');
                
                updateBooking($bookingId, [
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'stripe_payment_intent' => $paymentIntentId,
                    'confirmed_at' => date('Y-m-d H:i:s'),
                    'verification_method' => 'cron_job'
                ]);
                
                // Send confirmation email if not already sent
                $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
                if (file_exists($bookingFile)) {
                    $bookingData = json_decode(file_get_contents($bookingFile), true);
                    if ($bookingData['email']) {
                        sendConfirmationEmail($bookingData);
                        logMessage("Confirmation email sent for booking $bookingId", 'INFO');
                    }
                }
            }
            // If payment failed, update booking status
            elseif ($paymentIntent->status === 'requires_payment_method') {
                logMessage("Payment requires action for booking $bookingId", 'WARNING');
                updateBooking($bookingId, [
                    'status' => 'failed',
                    'payment_status' => 'failed',
                    'payment_error' => 'Payment requires payment method'
                ]);
            }
            
        } catch (Exception $e) {
            logMessage("Error verifying booking $bookingId: " . $e->getMessage(), 'ERROR');
        }
    }
    
    logMessage("=== Payment Verification Complete ===", 'INFO');
    
} catch (Exception $e) {
    logMessage("Cron job error: " . $e->getMessage(), 'ERROR');
}

/**
 * Update booking record
 */
function updateBooking($bookingId, $updates) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
            DB_USER,
            DB_PASS
        );
        
        $setParts = [];
        foreach (array_keys($updates) as $key) {
            $setParts[] = "$key = :$key";
        }
        $setClause = implode(', ', $setParts);
        
        $stmt = $pdo->prepare("UPDATE bookings SET $setClause, updated_at = NOW() WHERE booking_id = :booking_id");
        $updates['booking_id'] = $bookingId;
        $stmt->execute($updates);
        
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
    
    $mailHandler = new MailHandler();
    $property = $bookingData['property'] ?? 'Unknown';
    
    $emailBody = "
        <h2>Booking Confirmed!</h2>
        <p>Hello {$bookingData['firstName']} {$bookingData['lastName']},</p>
        <p>Your booking for <strong>{$property}</strong> has been confirmed.</p>
        
        <h3>Booking Details</h3>
        <ul>
            <li><strong>Check-in:</strong> {$bookingData['checkIn']}</li>
            <li><strong>Check-out:</strong> {$bookingData['checkOut']}</li>
            <li><strong>Guests:</strong> {$bookingData['guests']}</li>
            <li><strong>Total Amount:</strong> \${$bookingData['amount']}</li>
        </ul>
        
        <p>Further instructions will be sent shortly.</p>
        <p>Best regards,<br>SmartStayz Team</p>
    ";
    
    $mailHandler->sendEmail(
        $bookingData['email'],
        "{$bookingData['firstName']} {$bookingData['lastName']}",
        "Booking Confirmed - {$property}",
        $emailBody
    );
}

/**
 * Log messages
 */
function logMessage($message, $level = 'INFO') {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/verify-payments.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND);
}
