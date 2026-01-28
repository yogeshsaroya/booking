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

if (!$propertyId || !isset(PROPERTY_ICAL_URLS[$propertyId])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid property ID'
    ]);
    exit;
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
    
    // Fetch fresh data
    $icalUrl = PROPERTY_ICAL_URLS[$propertyId];
    $icalData = fetchICalData($icalUrl);
    
    if (!$icalData) {
        throw new Exception('Failed to fetch iCal data');
    }
    
    $blockedDates = parseICalData($icalData);
    
    // Cache the results
    $cacheData = [
        'blockedDates' => $blockedDates,
        'timestamp' => time()
    ];
    file_put_contents($cacheFile, json_encode($cacheData));
    
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
