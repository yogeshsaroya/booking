<?php
/**
 * Test Email Configuration
 * Simple script to test SMTP email delivery
 */

require_once 'config.php';
require_once 'MailHandler.php';

header('Content-Type: text/plain');

echo "=== SmartStayz Email Test ===\n\n";

// Check environment variables
echo "SMTP Configuration:\n";
echo "- Host: " . ($_ENV['SMTP_HOST'] ?? 'NOT SET') . "\n";
echo "- Port: " . ($_ENV['SMTP_PORT'] ?? 'NOT SET') . "\n";
echo "- Secure: " . ($_ENV['SMTP_SECURE'] ?? 'NOT SET') . "\n";
echo "- Username: " . ($_ENV['SMTP_USERNAME'] ?? 'NOT SET') . "\n";
echo "- Password: " . (isset($_ENV['SMTP_PASSWORD']) && !empty($_ENV['SMTP_PASSWORD']) ? '***SET***' : 'NOT SET') . "\n";
echo "- From Email: " . ($_ENV['SMTP_FROM_EMAIL'] ?? $_ENV['FROM_EMAIL'] ?? 'NOT SET') . "\n";
echo "- Admin Email: " . ($_ENV['ADMIN_EMAIL'] ?? 'NOT SET') . "\n\n";

// Check PHPMailer
echo "PHPMailer Status:\n";
$phpMailerPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($phpMailerPath)) {
    echo "✓ PHPMailer installed\n\n";
} else {
    echo "✗ PHPMailer NOT installed (run: composer install)\n\n";
    exit;
}

// Test email sending
echo "Sending test email...\n";

$mailHandler = new MailHandler();
$testEmail = $_ENV['ADMIN_EMAIL'] ?? 'test@example.com';

$subject = "SmartStayz Email Test - " . date('Y-m-d H:i:s');
$message = "
<h2>Test Email from SmartStayz</h2>
<p>This is a test email to verify SMTP configuration.</p>
<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
<p>If you received this email, your SMTP configuration is working correctly!</p>
";

try {
    $result = $mailHandler->send($testEmail, $subject, $message);
    
    if ($result) {
        echo "✓ Test email sent successfully to $testEmail\n";
        echo "\nCheck your inbox to confirm delivery.\n";
    } else {
        echo "✗ Failed to send test email\n";
        echo "Check php/logs directory for error details.\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
