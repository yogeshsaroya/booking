<?php
/**
 * Check Availability Endpoint
 * Standalone API for checking property availability
 * 
 * Usage from JavaScript:
 *   fetch('php/check-availability.php', {
 *     method: 'POST',
 *     body: JSON.stringify({
 *       property: 'cedar',
 *       checkIn: '2026-02-01',
 *       checkOut: '2026-02-05'
 *     })
 *   })
 */

require_once 'AvailabilityChecker.php';

header('Content-Type: application/json');

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$action = $data['action'] ?? 'check_availability';

try {
    $checker = new AvailabilityChecker();
    
    switch ($action) {
        case 'check_availability':
            handleCheckAvailability($checker, $data);
            break;
            
        case 'get_blocked_dates':
            handleGetBlockedDates($checker, $data);
            break;
            
        case 'get_blocked_in_range':
            handleGetBlockedInRange($checker, $data);
            break;
            
        case 'get_next_available':
            handleGetNextAvailable($checker, $data);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    logMessage("Availability check error: " . $e->getMessage(), 'ERROR');
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Check if date range is available
 */
function handleCheckAvailability($checker, $data) {
    $propertyId = $data['property'] ?? null;
    $checkIn = $data['checkIn'] ?? null;
    $checkOut = $data['checkOut'] ?? null;
    
    if (!$propertyId || !$checkIn || !$checkOut) {
        throw new Exception('Missing required fields: property, checkIn, checkOut');
    }
    
    $isAvailable = $checker->isDateRangeAvailable($propertyId, $checkIn, $checkOut);
    
    $response = [
        'success' => true,
        'available' => $isAvailable,
        'property' => $propertyId,
        'checkIn' => $checkIn,
        'checkOut' => $checkOut
    ];
    
    // If not available, include blocked dates
    if (!$isAvailable) {
        $response['blockedDates'] = $checker->getBlockedDatesInRange($propertyId, $checkIn, $checkOut);
    }
    
    echo json_encode($response);
}

/**
 * Get all blocked dates for a property
 */
function handleGetBlockedDates($checker, $data) {
    $propertyId = $data['property'] ?? null;
    
    if (!$propertyId) {
        throw new Exception('Missing required field: property');
    }
    
    $blockedDates = $checker->getBlockedDates($propertyId);
    
    echo json_encode([
        'success' => true,
        'property' => $propertyId,
        'blockedDates' => $blockedDates,
        'count' => count($blockedDates)
    ]);
}

/**
 * Get blocked dates in a specific range
 */
function handleGetBlockedInRange($checker, $data) {
    $propertyId = $data['property'] ?? null;
    $startDate = $data['startDate'] ?? null;
    $endDate = $data['endDate'] ?? null;
    
    if (!$propertyId || !$startDate || !$endDate) {
        throw new Exception('Missing required fields: property, startDate, endDate');
    }
    
    $blockedDates = $checker->getBlockedDatesInRange($propertyId, $startDate, $endDate);
    
    echo json_encode([
        'success' => true,
        'property' => $propertyId,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'blockedDates' => $blockedDates,
        'count' => count($blockedDates)
    ]);
}

/**
 * Get next available date
 */
function handleGetNextAvailable($checker, $data) {
    $propertyId = $data['property'] ?? null;
    $fromDate = $data['fromDate'] ?? date('Y-m-d');
    $maxDays = $data['maxDays'] ?? 365;
    
    if (!$propertyId) {
        throw new Exception('Missing required field: property');
    }
    
    $nextAvailable = $checker->getNextAvailableDate($propertyId, $fromDate, $maxDays);
    
    echo json_encode([
        'success' => true,
        'property' => $propertyId,
        'fromDate' => $fromDate,
        'nextAvailable' => $nextAvailable
    ]);
}
