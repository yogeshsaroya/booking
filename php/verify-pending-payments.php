<?php
/**
 * SmartStayz Payment Verification Cron Job
 * Verifies pending payments against Stripe to catch any missed webhooks
 * Run this via cron job every 5-10 minutes: * /5 * * * * php /path/to/verify-pending-payments.php
 */

require_once 'config.php';
require_once 'email-functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\PaymentIntent;

// Set Stripe API key
Stripe::setApiKey(STRIPE_SECRET_KEY);

// Check if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

$output = [];
$output[] = "=== Starting Payment Verification ===";
$output[] = "Timestamp: " . date('Y-m-d H:i:s');
$output[] = "";

$separator = $isCLI ? "\n" : "<br>\n";
echo implode($separator, $output);
logMessage("=== Starting Payment Verification ===", 'INFO');

try {
    // Get all pending bookings with Stripe payment intent
    echo "Connecting to database..." . ($isCLI ? "\n" : "<br>\n");
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    echo "✓ Database connected" . ($isCLI ? "\n" : "<br>\n");
    
    echo "Fetching pending bookings..." . ($isCLI ? "\n" : "<br>\n");
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
    echo "✓ $message" . ($isCLI ? "\n" : "<br>\n");
    logMessage($message, 'INFO');
    
    if (empty($pendingBookings)) {
        echo "No pending bookings found. Exiting." . ($isCLI ? "\n" : "<br>\n");
        echo ($isCLI ? "\n" : "<br>\n") . "=== Payment Verification Complete ===" . ($isCLI ? "\n" : "<br>\n");
        logMessage("=== Payment Verification Complete ===", 'INFO');
        exit;
    }
    
    $successCount = 0;
    $failureCount = 0;
    $errorCount = 0;
    
    echo ($isCLI ? "\n" : "<br>\n") . "--- Processing Bookings ---" . ($isCLI ? "\n" : "<br>\n");
    
    foreach ($pendingBookings as $booking) {
        $bookingId = $booking['booking_id'];
        $paymentIntentId = $booking['stripe_payment_intent'];
        
        try {
            // Fetch payment intent from Stripe
            echo ($isCLI ? "\n" : "<br>\n") . "Booking ID: $bookingId" . ($isCLI ? "\n" : "<br>\n");
            echo "  Payment Intent: $paymentIntentId" . ($isCLI ? "\n" : "<br>\n");
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            echo "  Stripe Status: {$paymentIntent->status}" . ($isCLI ? "\n" : "<br>\n");
            logMessage("Checking booking $bookingId - PaymentIntent: $paymentIntentId - Status: {$paymentIntent->status}", 'INFO');
            
            // If payment succeeded, update booking
            if ($paymentIntent->status === 'succeeded') {
                echo "  ✓ Payment SUCCEEDED - Updating booking to confirmed..." . ($isCLI ? "\n" : "<br>\n");
                logMessage("Payment verified for booking $bookingId - Updating status to confirmed", 'INFO');
                
                updateBooking($bookingId, [
                    'status' => 'confirmed',
                    'stripe_payment_intent' => $paymentIntentId,
                    
                ]);
                
                // Send confirmation emails to user and admin
                $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
                if (file_exists($bookingFile)) {
                    $bookingData = json_decode(file_get_contents($bookingFile), true);
                    if ($bookingData['email']) {
                        sendPaymentConfirmationEmail($bookingData);
                        sendAdminPaymentConfirmationEmail($bookingData);
                        echo "  ✓ Confirmation emails sent to {$bookingData['email']} and admin" . ($isCLI ? "\n" : "<br>\n");
                        logMessage("Confirmation emails sent for booking $bookingId", 'INFO');
                    }
                }
                $successCount++;
            }
            // If payment failed, update booking status
            elseif ($paymentIntent->status === 'requires_payment_method') {
                echo "  ⚠ Payment REQUIRES ACTION - Marking as failed..." . ($isCLI ? "\n" : "<br>\n");
                logMessage("Payment requires action for booking $bookingId", 'WARNING');
                updateBooking($bookingId, [
                    'status' => 'failed',
                    'payment_error' => 'Payment requires payment method'
                ]);
                
                // Send failed payment emails to user and admin
                $bookingFile = __DIR__ . "/bookings/{$bookingId}.json";
                if (file_exists($bookingFile)) {
                    $bookingData = json_decode(file_get_contents($bookingFile), true);
                    require_once 'email-functions.php';
                    sendAdminPaymentFailedEmail($bookingData, 'Payment requires payment method');
                }
                
                $failureCount++;
            } else {
                echo "  ℹ Payment status: {$paymentIntent->status} (no action needed)" . ($isCLI ? "\n" : "<br>\n");
            }
            
        } catch (Exception $e) {
            echo "  ✗ ERROR: " . $e->getMessage() . ($isCLI ? "\n" : "<br>\n");
            logMessage("Error verifying booking $bookingId: " . $e->getMessage(), 'ERROR');
            $errorCount++;
        }
    }
    
    echo ($isCLI ? "\n" : "<br>\n") . "--- Summary ---" . ($isCLI ? "\n" : "<br>\n");
    echo "Bookings Processed: " . count($pendingBookings) . ($isCLI ? "\n" : "<br>\n");
    echo "✓ Confirmed: $successCount" . ($isCLI ? "\n" : "<br>\n");
    echo "⚠ Failed: $failureCount" . ($isCLI ? "\n" : "<br>\n");
    echo "✗ Errors: $errorCount" . ($isCLI ? "\n" : "<br>\n");
    echo ($isCLI ? "\n" : "<br>\n") . "=== Payment Verification Complete ===" . ($isCLI ? "\n" : "<br>\n");
    logMessage("=== Payment Verification Complete ===", 'INFO');
    
} catch (Exception $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . ($isCLI ? "\n" : "<br>\n");
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
