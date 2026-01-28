<?php
/**
 * Mail Handler - SMTP Email Service
 * Uses PHPMailer for secure SMTP email delivery
 */

class MailHandler {
    private $smtp_host;
    private $smtp_port;
    private $smtp_secure;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;

    public function __construct() {
        $this->smtp_host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtp_port = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtp_secure = $_ENV['SMTP_SECURE'] ?? 'tls';
        $this->smtp_username = $_ENV['SMTP_USERNAME'] ?? '';
        $this->smtp_password = $_ENV['SMTP_PASSWORD'] ?? '';
        $this->from_email = $_ENV['SMTP_FROM_EMAIL'] ?? $_ENV['FROM_EMAIL'] ?? 'noreply@smartstayz.com';
        $this->from_name = $_ENV['SMTP_FROM_NAME'] ?? $_ENV['FROM_NAME'] ?? 'SmartStayz';
    }

    /**
     * Send email using SMTP
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $htmlBody HTML email body
     * @param string $textBody Plain text email body (optional)
     * @param array $cc CC addresses (optional)
     * @param array $bcc BCC addresses (optional)
     * @return bool Success/failure
     */
    public function send($to, $subject, $htmlBody, $textBody = '', $cc = [], $bcc = []) {
        try {
            // Use PHPMailer if available, otherwise use PHP mail as fallback
            if ($this->isPhpMailerAvailable()) {
                return $this->sendWithPhpMailer($to, $subject, $htmlBody, $textBody, $cc, $bcc);
            } else {
                return $this->sendWithPhpMail($to, $subject, $htmlBody, $cc, $bcc);
            }
        } catch (Exception $e) {
            logMessage("Mail sending error: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Check if PHPMailer is available via Composer
     */
    private function isPhpMailerAvailable() {
        return file_exists(__DIR__ . '/../vendor/autoload.php');
    }

    /**
     * Send email via PHPMailer (Recommended - SMTP)
     */
    private function sendWithPhpMailer($to, $subject, $htmlBody, $textBody, $cc, $bcc) {
        require __DIR__ . '/../vendor/autoload.php';
        
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host = $this->smtp_host;
            $mail->Port = $this->smtp_port;
            $mail->SMTPSecure = $this->smtp_secure;
            $mail->SMTPAuth = true;
            $mail->Username = $this->smtp_username;
            $mail->Password = $this->smtp_password;

            // Set from address
            $mail->setFrom($this->from_email, $this->from_name);

            // Add recipient
            $mail->addAddress($to);

            // Add CC recipients
            if (!empty($cc) && is_array($cc)) {
                foreach ($cc as $ccEmail) {
                    $mail->addCC($ccEmail);
                }
            }

            // Add BCC recipients
            if (!empty($bcc) && is_array($bcc)) {
                foreach ($bcc as $bccEmail) {
                    $mail->addBCC($bccEmail);
                }
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            
            // Add plain text alternative
            if (!empty($textBody)) {
                $mail->AltBody = $textBody;
            }

            // Send email
            $result = $mail->send();
            
            logMessage("Email sent successfully to $to via SMTP", 'DEBUG');
            return $result;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            logMessage("PHPMailer error: " . $mail->ErrorInfo, 'ERROR');
            return false;
        }
    }

    /**
     * Send email via PHP mail() function (Fallback)
     */
    private function sendWithPhpMail($to, $subject, $htmlBody, $cc, $bcc) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";

        // Add CC headers
        if (!empty($cc) && is_array($cc)) {
            $headers .= "Cc: " . implode(',', $cc) . "\r\n";
        }

        // Add BCC headers
        if (!empty($bcc) && is_array($bcc)) {
            $headers .= "Bcc: " . implode(',', $bcc) . "\r\n";
        }

        $result = mail($to, $subject, $htmlBody, $headers);
        
        if ($result) {
            logMessage("Email sent via PHP mail() to $to (fallback mode)", 'DEBUG');
        } else {
            logMessage("PHP mail() failed for $to", 'ERROR');
        }
        
        return $result;
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($bookingData) {
        $to = $bookingData['email'];
        $subject = "Your SmartStayz Booking Confirmation - Booking ID: {$bookingData['bookingId']}";
        
        $htmlBody = $this->getBookingConfirmationHtml($bookingData);
        $textBody = $this->getBookingConfirmationText($bookingData);
        
        $cc = []; // Add admin email if needed
        if (!empty($_ENV['ADMIN_EMAIL'])) {
            $cc[] = $_ENV['ADMIN_EMAIL'];
        }
        
        return $this->send($to, $subject, $htmlBody, $textBody, $cc);
    }

    /**
     * Send payment instructions email
     */
    public function sendPaymentInstructions($email, $bookingId, $paymentMethod, $amount) {
        $subject = "Payment Instructions for Your SmartStayz Booking";
        
        if ($paymentMethod === 'bitcoin') {
            $htmlBody = $this->getBitcoinPaymentHtml($bookingId, $amount);
        } else if ($paymentMethod === 'venmo') {
            $htmlBody = $this->getVenmoPaymentHtml($bookingId, $amount);
        } else {
            return false;
        }
        
        return $this->send($email, $subject, $htmlBody);
    }

    /**
     * Get booking confirmation HTML
     */
    private function getBookingConfirmationHtml($data) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #2e7d32;'>✓ Booking Confirmed!</h2>
                <p>Thank you for booking with SmartStayz. Your booking has been confirmed.</p>
                <h3>Booking Details</h3>
                <p>
                    <strong>Booking ID:</strong> {$data['bookingId']}<br>
                    <strong>Property:</strong> {$data['property']}<br>
                    <strong>Check-in:</strong> {$data['checkIn']}<br>
                    <strong>Check-out:</strong> {$data['checkOut']}<br>
                    <strong>Nights:</strong> {$data['nights']}<br>
                    <strong>Total Amount:</strong> \${$data['total']}
                </p>
                <p>We'll send you check-in instructions 7 days before your arrival.</p>
                <p>If you have any questions, please don't hesitate to contact us.</p>
                <hr>
                <p style='font-size: 0.9em; color: #666;'>© 2026 SmartStayz, LLC. All rights reserved.</p>
            </body>
            </html>
        ";
    }

    /**
     * Get booking confirmation plain text
     */
    private function getBookingConfirmationText($data) {
        return "
Booking Confirmed!

Thank you for booking with SmartStayz. Your booking has been confirmed.

BOOKING DETAILS
Booking ID: {$data['bookingId']}
Property: {$data['property']}
Check-in: {$data['checkIn']}
Check-out: {$data['checkOut']}
Nights: {$data['nights']}
Total Amount: \${$data['total']}

We'll send you check-in instructions 7 days before your arrival.

If you have any questions, please don't hesitate to contact us.

© 2026 SmartStayz, LLC. All rights reserved.
        ";
    }

    /**
     * Get Bitcoin payment HTML
     */
    private function getBitcoinPaymentHtml($bookingId, $amount) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2>Bitcoin Payment Instructions</h2>
                <p>Thank you for choosing Bitcoin for your payment.</p>
                <p><strong>Booking ID:</strong> $bookingId</p>
                <p><strong>Amount Due:</strong> \$$amount</p>
                <p>Payment details will be provided separately. Please complete payment within 48 hours to confirm your booking.</p>
            </body>
            </html>
        ";
    }

    /**
     * Get Venmo payment HTML
     */
    private function getVenmoPaymentHtml($bookingId, $amount) {
        return "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2>Payment Instructions</h2>
                <p>Thank you for your booking.</p>
                <p><strong>Booking ID:</strong> $bookingId</p>
                <p><strong>Amount Due:</strong> \$$amount</p>
                <p>Payment details for Venmo/CashApp will be provided separately. Please complete payment within 24 hours to confirm your booking.</p>
            </body>
            </html>
        ";
    }
}

// Convenient function to send emails
function sendEmail($to, $subject, $htmlBody, $textBody = '', $cc = [], $bcc = []) {
    $mailer = new MailHandler();
    return $mailer->send($to, $subject, $htmlBody, $textBody, $cc, $bcc);
}
?>
