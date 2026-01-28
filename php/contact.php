<?php
/**
 * SmartStayz Contact Form Handler
 */

require_once 'config.php';
require_once 'MailHandler.php';

header('Content-Type: application/json');

// Get form data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

try {
    // Validate input
    if (empty($data['name']) || empty($data['email']) || empty($data['message'])) {
        throw new Exception('All fields are required');
    }
    
    // Validate email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email address');
    }
    
    // Sanitize data
    $name = htmlspecialchars($data['name']);
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $message = htmlspecialchars($data['message']);
    
    // Send email to admin with Reply-To set to the sender
    $subject = "New Contact Form Submission from $name";
    $emailMessage = "
    <h2>New Contact Form Submission</h2>
    <p><strong>Name:</strong> $name</p>
    <p><strong>Email:</strong> $email</p>
    <p><strong>Message:</strong></p>
    <p>$message</p>
    <hr>
    <p><em>Sent from SmartStayz contact form</em></p>
    ";
    
    $mailHandler = new MailHandler();
    // Last parameter is replyTo - when you reply to this email, it goes to the sender
    $sent = $mailHandler->send($_ENV['ADMIN_EMAIL'], $subject, $emailMessage, '', [], [], $email);
    
    if (!$sent) {
        throw new Exception('Failed to send email');
    }
    
    // Send confirmation to user
    $confirmationMessage = "
    <h2>Thank you for contacting SmartStayz!</h2>
    <p>Hi $name,</p>
    <p>We've received your message and will respond within an hour.</p>
    <p><strong>Your message:</strong></p>
    <p>$message</p>
    <p>Best regards,<br>SmartStayz Team</p>
    ";
    
    $mailHandler->send($email, "We received your message", $confirmationMessage);
    
    // Log the contact
    logMessage("Contact form submission from $name ($email)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully'
    ]);
    
} catch (Exception $e) {
    logMessage("Contact form error: " . $e->getMessage(), 'ERROR');
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
