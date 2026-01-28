<?php
/**
 * Get Booking Details
 * Retrieves booking information by booking ID
 */

// Set JSON response header
header('Content-Type: application/json');

// Include config
require_once 'config.php';

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Get booking ID from query parameter (from URL: ?booking=BOOK-xxx)
$bookingId = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

// Debug log
logMessage("GET booking parameter: " . json_encode($_GET), 'DEBUG');
logMessage("Sanitized bookingId: " . $bookingId, 'DEBUG');

if (!$bookingId || empty(trim($bookingId))) {
    logMessage("Booking ID validation failed. POST data: " . json_encode($_POST) . ", GET data: " . json_encode($_GET), 'DEBUG');
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Booking ID is required'
    ]);
    exit;
}

try {
    // Connect to database
    $pdo = new PDO(
        "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'],
        $_ENV['DB_USER'],
        $_ENV['DB_PASS']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Query booking details
    $query = "SELECT 
                id,
                booking_id,
                property,
                check_in,
                check_out,
                nights,
                guests,
                first_name,
                last_name,
                email,
                phone,
                has_pets,
                special_requests,
                payment_method,
                amount,
                created_at
            FROM bookings 
            WHERE booking_id = :id
            LIMIT 1";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Booking not found'
        ]);
        exit;
    }
    
    // Format response
    echo json_encode([
        'success' => true,
        'booking' => [
            'id' => $booking['id'],
            'bookingId' => $booking['booking_id'],
            'property' => $booking['property'],
            'checkIn' => $booking['check_in'],
            'checkOut' => $booking['check_out'],
            'nights' => (int)$booking['nights'],
            'guests' => (int)$booking['guests'],
            'firstName' => $booking['first_name'],
            'lastName' => $booking['last_name'],
            'email' => $booking['email'],
            'phone' => $booking['phone'],
            'hasPets' => (bool)$booking['has_pets'],
            'specialRequests' => $booking['special_requests'],
            'paymentMethod' => $booking['payment_method'],
            'total' => (float)$booking['amount'],
            'createdAt' => $booking['created_at']
        ]
    ]);
    
} catch (PDOException $e) {
    logMessage("Database error in get-booking.php: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to retrieve booking details. Please try again later.'
    ]);
}

?>
