<?php
/**
 * SmartStayz Calendar Sync
 * Fetches and parses iCal feeds from Airbnb
 * Returns blocked dates as JSON
 */

require_once 'config.php';

header('Content-Type: application/json');

// Get property ID from request
$propertyId = $_GET['property'] ?? null;
$clearCache = $_GET['clear_cache'] ?? false;

if (!$propertyId || !isset(PROPERTY_ICAL_URLS[$propertyId])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid property ID'
    ]);
    exit;
}

// Clear cache if requested
if ($clearCache) {
    $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        logMessage("Cache cleared for $propertyId", 'INFO');
    }
}

try {
    $blockedDates = getBlockedDates($propertyId);
    
    echo json_encode([
        'success' => true,
        'blockedDates' => $blockedDates,
        'lastUpdated' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    logMessage("Calendar sync error for $propertyId: " . $e->getMessage(), 'ERROR');
    
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch calendar data'
    ]);
}

/**
 * Get blocked dates for a property
 */
function getBlockedDates($propertyId) {
    // Check cache first
    $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
    
    if (file_exists($cacheFile)) {
        $cacheAge = time() - filemtime($cacheFile);
        
        // Use cache if less than CACHE_DURATION old
        if ($cacheAge < CACHE_DURATION) {
            $cachedData = json_decode(file_get_contents($cacheFile), true);
            return $cachedData['blockedDates'] ?? [];
        }
    }
    
    // Fetch fresh data from iCal
    $icalUrl = PROPERTY_ICAL_URLS[$propertyId];
    $icalData = fetchICalData($icalUrl);
    
    $icalBlockedDates = [];
    if ($icalData) {
        $icalBlockedDates = parseICalData($icalData);
    } else {
        logMessage("Failed to fetch iCal data for $propertyId, continuing with database only", 'WARNING');
    }
    
    // Get blocked dates from database bookings
    $dbBlockedDates = getBlockedDatesFromDatabase($propertyId);
    
    // Merge both sources and remove duplicates
    $blockedDates = array_unique(array_merge($icalBlockedDates, $dbBlockedDates));
    sort($blockedDates);
    
    // Cache the results
    $cacheData = [
        'blockedDates' => $blockedDates,
        'timestamp' => time()
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
    
    return $blockedDates;
}

/**
 * Get blocked dates from database bookings
 */
function getBlockedDatesFromDatabase($propertyId) {
    $blockedDates = [];
    
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            logMessage("Database connection failed for $propertyId", 'WARNING');
            return $blockedDates;
        }
        
        // Get all confirmed and pending bookings for this property
        // We block dates for: pending, pending_bitcoin, confirmed, and paid statuses
        // We don't block for: cancelled, failed, expired
        $stmt = $pdo->prepare("
            SELECT check_in, check_out, booking_id, status
            FROM bookings 
            WHERE property = :property 
            AND status IN ('pending', 'pending_bitcoin', 'confirmed', 'paid')
            AND check_out >= CURDATE()
            ORDER BY check_in
        ");
        
        $stmt->execute(['property' => $propertyId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logMessage("Database query for $propertyId returned " . count($bookings) . " bookings", 'DEBUG');
        
        // Convert each booking to blocked date range
        foreach ($bookings as $booking) {
            logMessage("  Processing booking {$booking['booking_id']}: {$booking['check_in']} to {$booking['check_out']} (Status: {$booking['status']})", 'DEBUG');
            
            $checkIn = new DateTime($booking['check_in']);
            $checkOut = new DateTime($booking['check_out']);
            
            // Block all dates from check-in to check-out (INCLUDING checkout day)
            $current = clone $checkIn;
            while ($current <= $checkOut) {
                $blockedDates[] = $current->format('Y-m-d');
                $current->modify('+1 day');
            }
        }
        
        logMessage("Found " . count($bookings) . " active bookings for $propertyId with " . count($blockedDates) . " blocked dates", 'INFO');
        
    } catch (Exception $e) {
        logMessage("Failed to fetch database bookings for $propertyId: " . $e->getMessage(), 'ERROR');
    }
    
    return $blockedDates;
}

/**
 * Fetch iCal data from URL
 */
function fetchICalData($url) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'SmartStayz Calendar Sync/1.0'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        logMessage("Failed to fetch iCal data. HTTP Code: $httpCode", 'ERROR');
        return false;
    }
    
    return $response;
}

/**
 * Parse iCal data and extract blocked dates
 */
function parseICalData($icalData) {
    $blockedDates = [];
    
    // Split into lines
    $lines = explode("\n", str_replace("\r\n", "\n", $icalData));
    
    $inEvent = false;
    $currentEvent = [];
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if ($line === 'BEGIN:VEVENT') {
            $inEvent = true;
            $currentEvent = [];
        } elseif ($line === 'END:VEVENT') {
            if ($inEvent) {
                $dates = extractDatesFromEvent($currentEvent);
                $blockedDates = array_merge($blockedDates, $dates);
            }
            $inEvent = false;
        } elseif ($inEvent) {
            // Parse event properties
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                
                // Handle properties with parameters (e.g., DTSTART;VALUE=DATE:20260123)
                if (strpos($key, ';') !== false) {
                    $key = explode(';', $key)[0];
                }
                
                $currentEvent[$key] = $value;
            }
        }
    }
    
    // Remove duplicates and sort
    $blockedDates = array_unique($blockedDates);
    sort($blockedDates);
    
    return $blockedDates;
}

/**
 * Extract date range from event
 */
function extractDatesFromEvent($event) {
    $dates = [];
    
    if (!isset($event['DTSTART']) || !isset($event['DTEND'])) {
        return $dates;
    }
    
    // Parse dates (format: YYYYMMDD or YYYYMMDDTHHMMSSZ)
    $startDate = parseICalDate($event['DTSTART']);
    $endDate = parseICalDate($event['DTEND']);
    
    if (!$startDate || !$endDate) {
        return $dates;
    }
    
    // Generate all dates in range (excluding checkout day as it's available)
    $current = $startDate;
    while ($current < $endDate) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }
    
    return $dates;
}

/**
 * Parse iCal date format
 */
function parseICalDate($dateString) {
    // Remove any timezone info
    $dateString = preg_replace('/[TZ].*$/', '', $dateString);
    
    // Parse YYYYMMDD format
    if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $dateString, $matches)) {
        try {
            return new DateTime("{$matches[1]}-{$matches[2]}-{$matches[3]}");
        } catch (Exception $e) {
            logMessage("Failed to parse date: $dateString", 'ERROR');
            return null;
        }
    }
    
    return null;
}

/**
 * Manual sync endpoint (called via cron job)
 */
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Syncing calendars...\n";
    
    foreach (array_keys(PROPERTY_ICAL_URLS) as $propertyId) {
        echo "Syncing $propertyId...";
        
        try {
            // Clear cache to force fresh fetch
            $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            
            $blockedDates = getBlockedDates($propertyId);
            echo " Done! (" . count($blockedDates) . " blocked dates)\n";
            
        } catch (Exception $e) {
            echo " Error: " . $e->getMessage() . "\n";
        }
    }
    
    echo "Sync complete!\n";
}
