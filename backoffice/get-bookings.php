<?php
/**
 * SmartStayz Admin API - Get All Bookings
 * Returns all bookings from database
 */

require_once '../php/config.php';

header('Content-Type: application/json');

try {
    // Connect to database
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME,
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get all bookings, ordered by newest first
    $stmt = $pdo->prepare("
        SELECT 
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
            status,
            stripe_payment_intent,
            bitcoin_invoice_url,
            created_at,
            updated_at
        FROM bookings 
        ORDER BY created_at DESC
    ");
    
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings,
        'count' => count($bookings)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
