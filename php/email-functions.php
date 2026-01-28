<?php
/**
 * Common Email Functions
 * Shared email functions for payment confirmations, failures, and admin notifications
 */

require_once 'MailHandler.php';
require_once 'config.php';

/**
 * Send payment confirmation email to user
 */
function sendPaymentConfirmationEmail($bookingData) {
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
 * Send payment failed email to user
 */
function sendPaymentFailedEmail($bookingData, $errorMessage) {
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
 * Send payment confirmation notification to admin
 */
function sendAdminPaymentConfirmationEmail($bookingData) {
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL');
    
    if (empty($adminEmail)) {
        logMessage("Admin email not configured - skipping admin notification", 'WARNING');
        return;
    }
    
    $mailHandler = new MailHandler();
    $property = PROPERTIES[$bookingData['property']] ?? ['name' => 'Unknown Property'];
    $propertyName = is_array($property) ? $property['name'] : $property;
    $firstName = $bookingData['first_name'] ?? '';
    $lastName = $bookingData['last_name'] ?? '';
    $bookingId = $bookingData['booking_id'] ?? '';
    $checkIn = $bookingData['check_in'] ?? '';
    $checkOut = $bookingData['check_out'] ?? '';
    $email = $bookingData['email'] ?? '';
    $phone = $bookingData['phone'] ?? '';
    $guests = $bookingData['guests'] ?? 0;
    $hasPets = isset($bookingData['has_pets']) && $bookingData['has_pets'] ? 'Yes' : 'No';
    $specialRequests = $bookingData['special_requests'] ?? 'None';
    
    $emailBody = "
        <h2>✅ Payment Confirmed - New Booking</h2>
        <p><strong>A booking payment has been confirmed!</strong></p>
        
        <h3>Booking Details</h3>
        <ul>
            <li><strong>Booking ID:</strong> {$bookingId}</li>
            <li><strong>Property:</strong> {$propertyName}</li>
            <li><strong>Check-in:</strong> {$checkIn}</li>
            <li><strong>Check-out:</strong> {$checkOut}</li>
            <li><strong>Guests:</strong> {$guests}</li>
            <li><strong>Pets:</strong> {$hasPets}</li>
            <li><strong>Total Amount:</strong> \${$bookingData['amount']}</li>
        </ul>
        
        <h3>Guest Information</h3>
        <ul>
            <li><strong>Name:</strong> {$firstName} {$lastName}</li>
            <li><strong>Email:</strong> {$email}</li>
            <li><strong>Phone:</strong> {$phone}</li>
        </ul>
        
        <h3>Special Requests</h3>
        <p>{$specialRequests}</p>
        
        <h3>⚠️ Important Action Required</h3>
        <p style='color: red; font-weight: bold;'>
            Remember to manually block these dates on Airbnb to prevent double bookings!
        </p>
        
        <p>Best regards,<br>SmartStayz Booking System</p>
    ";
    
    $mailHandler->send(
        $adminEmail,
        "Payment Confirmed - {$bookingId} - {$propertyName}",
        $emailBody
    );
    
    logMessage("Admin notification sent for booking {$bookingId}", 'INFO');
}

/**
 * Send payment failed notification to admin
 */
function sendAdminPaymentFailedEmail($bookingData, $errorMessage) {
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? getenv('ADMIN_EMAIL');
    
    if (empty($adminEmail)) {
        logMessage("Admin email not configured - skipping admin notification", 'WARNING');
        return;
    }
    
    $mailHandler = new MailHandler();
    $property = PROPERTIES[$bookingData['property']] ?? ['name' => 'Unknown Property'];
    $propertyName = is_array($property) ? $property['name'] : $property;
    $firstName = $bookingData['first_name'] ?? '';
    $lastName = $bookingData['last_name'] ?? '';
    $bookingId = $bookingData['booking_id'] ?? '';
    $checkIn = $bookingData['check_in'] ?? '';
    $checkOut = $bookingData['check_out'] ?? '';
    $email = $bookingData['email'] ?? '';
    $phone = $bookingData['phone'] ?? '';
    
    $emailBody = "
        <h2>❌ Payment Failed - Booking Incomplete</h2>
        <p><strong>A booking payment has failed.</strong></p>
        
        <h3>Booking Details</h3>
        <ul>
            <li><strong>Booking ID:</strong> {$bookingId}</li>
            <li><strong>Property:</strong> {$propertyName}</li>
            <li><strong>Check-in:</strong> {$checkIn}</li>
            <li><strong>Check-out:</strong> {$checkOut}</li>
            <li><strong>Amount:</strong> \${$bookingData['amount']}</li>
        </ul>
        
        <h3>Guest Information</h3>
        <ul>
            <li><strong>Name:</strong> {$firstName} {$lastName}</li>
            <li><strong>Email:</strong> {$email}</li>
            <li><strong>Phone:</strong> {$phone}</li>
        </ul>
        
        <h3>Error Details</h3>
        <p><strong>Error:</strong> {$errorMessage}</p>
        
        <p>You may want to follow up with the guest if they need assistance.</p>
        
        <p>Best regards,<br>SmartStayz Booking System</p>
    ";
    
    $mailHandler->send(
        $adminEmail,
        "Payment Failed - {$bookingId} - {$propertyName}",
        $emailBody
    );
    
    logMessage("Admin notification sent for failed payment - booking {$bookingId}", 'INFO');
}

/**
 * Log messages (if not already defined)
 */
if (!function_exists('logMessage')) {
    function logMessage($message, $level = 'INFO') {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/email.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] [$level] $message\n", FILE_APPEND);
    }
}
