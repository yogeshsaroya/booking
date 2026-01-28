<?php
/**
 * SmartStayz Payment Verification Cron Job
 * Verifies pending payments against Stripe to catch any missed webhooks
 * Run this via cron job every 5-10 minutes: */5 * * * * php /path/to/verify-pending-payments.php
 */

require_once 'config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

$output = [];
$output[] = "=== Starting Payment Verification ===";
$output[] = "Timestamp: " . date('Y-m-d H:i:s');
$output[] = "";

echo implode("\n", $output);
logMessage("=== Starting Payment Verification ===", 'INFO');

try {
    // Get all pending bookings with Stripe payment intent
    echo "Connecting to database...\n";
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    echo "✓ Database connected\n";
    
    echo "Fetching pending bookings...\n";
    $stmt = $pdo->prepare("
        SELECT booking_id, stripe_payment_intent 
        FROM bookings 
        WHERE status = 'pending' 
        AND payment_method = 'stripe'
        AND stripe_payment_intent IS NOT NULL
        AND stripe_payment_intent != ''
        LIMIT 50
    ");
    $stmt->execute();
    $pendingBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $message = "Found " . count($pendingBookings) . " pending bookings to verify";
    echo "✓ $message\n";
    logMessage($message, 'INFO');
    
    if (empty($pendingBookings)) {
        echo "No pending bookings found. Exiting.\n";
        echo "\n=== Payment Verification Complete ===\n";
        logMessage("=== Payment Verification Complete ===", 'INFO');
        exit;
    }
    
    $successCount = 0;
    $failureCount = 0;
    $errorCount = 0;
    
    echo "\n--- Processing Bookings ---\n";
    
    foreach ($pendingBookings as $booking) {
        $bookingId = $booking['booking_id'];
        $paymentIntentId = $booking['stripe_payment_intent'];
        
        try {
            // Fetch payment intent from Stripe
            echo "\nBooking ID: $bookingId\n";
            echo "  Payment Intent: $paymentIntentId\n";
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            echo "  Stripe Status: {$paymentIntent->status}\n";
            logMessage("Checking booking $bookingId - PaymentIntent: $paymentIntentId - Status: {$paymentIntent->status}", 'INFO');
            
            // If payment succeeded, update booking
            if ($paymentIntent->status === 'succeeded') {
                echo "  ✓ Payment SUCCEEDED - Updating booking to confirmed...\n";
                logMessage("Payment verified for booking $bookingId - Updating status to confirmed", 'INFO');
                
                updateBooking($bookingId, [
                    'status' => 'confirmed',
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
                        echo "  ✓ Confirmation email sent to {$bookingData['email']}\n";
                        logMessage("Confirmation email sent for booking $bookingId", 'INFO');
                    }
                }
                $successCount++;
            }
            // If payment failed, update booking status
            elseif ($paymentIntent->status === 'requires_payment_method') {
                echo "  ⚠ Payment REQUIRES ACTION - Marking as failed...\n";
                logMessage("Payment requires action for booking $bookingId", 'WARNING');
                updateBooking($bookingId, [
                    'status' => 'failed',
                    'payment_error' => 'Payment requires payment method'
                ]);
                $failureCount++;
            } else {
                echo "  ℹ Payment status: {$paymentIntent->status} (no action needed)\n";
            }
            
        } catch (Exception $e) {
            echo "  ✗ ERROR: " . $e->getMessage() . "\n";
            logMessage("Error verifying booking $bookingId: " . $e->getMessage(), 'ERROR');
            $errorCount++;
        }
    }
    
    echo "\n--- Summary ---\n";
    echo "Bookings Processed: " . count($pendingBookings) . "\n";
    echo "✓ Confirmed: $successCount\n";
    echo "⚠ Failed: $failureCount\n";
    echo "✗ Errors: $errorCount\n";
    echo "\n=== Payment Verification Complete ===\n";
    logMessage("=== Payment Verification Complete ===", 'INFO');
    
} catch (Exception $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
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
