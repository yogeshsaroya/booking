<?php
/**
 * Quick Calendar Sync Test
 * Tests if calendar-sync.php is accessible and working
 * Access: http://localhost/joseph/booking/php/test-calendar-sync.php?property=stone
 */

require_once 'config.php';

$propertyId = $_GET['property'] ?? null;

if (!$propertyId) {
    die('Please specify property: ?property=stone');
}

echo "Testing calendar-sync.php for property: $propertyId\n\n";

// Test 1: Check if file exists
$filePath = __DIR__ . '/calendar-sync.php';
echo "1. File exists: " . (file_exists($filePath) ? "✅ YES" : "❌ NO") . "\n";

// Test 2: Try to include and run it
echo "\n2. Testing calendar-sync.php directly:\n";
echo str_repeat("-", 50) . "\n";

// Simulate the request
$_GET['property'] = $propertyId;

// Start output buffering to capture the response
ob_start();

// Include the file
include 'calendar-sync.php';

// Get the output
$response = ob_get_clean();

echo "Response:\n";
echo $response;
echo "\n";

// Try to parse as JSON
$data = json_decode($response, true);

if ($data) {
    echo "\n3. JSON Parse: ✅ Valid JSON\n";
    echo "   Success: " . ($data['success'] ? "YES" : "NO") . "\n";
    echo "   Blocked dates: " . count($data['blockedDates'] ?? []) . "\n";
} else {
    echo "\n3. JSON Parse: ❌ Invalid JSON\n";
}
