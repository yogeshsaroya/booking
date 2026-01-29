<?php
/**
 * SmartStayz - Availability Checker
 * Common PHP functions for checking property availability
 * 
 * Checks both iCal feeds and database bookings
 * Can be included in any PHP file that needs availability checking
 * 
 * Usage:
 *   require_once 'AvailabilityChecker.php';
 *   $checker = new AvailabilityChecker();
 *   $isAvailable = $checker->isDateRangeAvailable('cedar', '2026-02-01', '2026-02-05');
 */

require_once 'config.php';

class AvailabilityChecker {
    
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    /**
     * Check if a date range is available for booking
     * 
     * @param string $propertyId Property ID (stone, copper, cedar)
     * @param string $checkIn Check-in date (YYYY-MM-DD)
     * @param string $checkOut Check-out date (YYYY-MM-DD)
     * @return bool True if available, false if blocked
     */
    public function isDateRangeAvailable($propertyId, $checkIn, $checkOut) {
        // Validate inputs
        if (!$this->isValidProperty($propertyId)) {
            throw new Exception("Invalid property ID: $propertyId");
        }
        
        if (!$this->isValidDateFormat($checkIn) || !$this->isValidDateFormat($checkOut)) {
            throw new Exception("Invalid date format. Use YYYY-MM-DD");
        }
        
        // Method 1: Check database directly (most accurate, real-time)
        if (!$this->checkDatabaseAvailability($propertyId, $checkIn, $checkOut)) {
            return false;
        }
        
        // Method 2: Check iCal blocked dates (Airbnb bookings)
        if (!$this->checkICalAvailability($propertyId, $checkIn, $checkOut)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if a specific date is blocked
     * 
     * @param string $propertyId Property ID
     * @param string $date Date to check (YYYY-MM-DD)
     * @return bool True if blocked, false if available
     */
    public function isDateBlocked($propertyId, $date) {
        $blockedDates = $this->getBlockedDates($propertyId);
        return in_array($date, $blockedDates);
    }
    
    /**
     * Get all blocked dates for a property
     * 
     * @param string $propertyId Property ID
     * @return array Array of blocked dates (YYYY-MM-DD format)
     */
    public function getBlockedDates($propertyId) {
        // Get from database bookings
        $dbBlockedDates = $this->getBlockedDatesFromDatabase($propertyId);
        
        // Get from iCal feeds
        $icalBlockedDates = $this->getBlockedDatesFromICal($propertyId);
        
        // Merge and remove duplicates
        $allBlockedDates = array_unique(array_merge($dbBlockedDates, $icalBlockedDates));
        sort($allBlockedDates);
        
        return $allBlockedDates;
    }
    
    /**
     * Get blocked dates in a specific range
     * 
     * @param string $propertyId Property ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Array of blocked dates in the range
     */
    public function getBlockedDatesInRange($propertyId, $startDate, $endDate) {
        $allBlockedDates = $this->getBlockedDates($propertyId);
        $blockedInRange = [];
        
        $current = new DateTime($startDate);
        $end = new DateTime($endDate);
        
        while ($current < $end) {
            $dateString = $current->format('Y-m-d');
            if (in_array($dateString, $allBlockedDates)) {
                $blockedInRange[] = $dateString;
            }
            $current->modify('+1 day');
        }
        
        return $blockedInRange;
    }
    
    /**
     * Clear calendar cache for a property
     * Call this after creating/updating a booking
     * 
     * @param string $propertyId Property ID
     */
    public function clearCache($propertyId) {
        $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
            logMessage("Cleared calendar cache for $propertyId", 'INFO');
        }
    }
    
    /**
     * Get next available date after a given date
     * 
     * @param string $propertyId Property ID
     * @param string $fromDate Start searching from this date (YYYY-MM-DD)
     * @param int $maxDays Maximum days to search (default 365)
     * @return string|null Next available date or null if none found
     */
    public function getNextAvailableDate($propertyId, $fromDate, $maxDays = 365) {
        $blockedDates = $this->getBlockedDates($propertyId);
        $current = new DateTime($fromDate);
        $daysChecked = 0;
        
        while ($daysChecked < $maxDays) {
            $dateString = $current->format('Y-m-d');
            
            if (!in_array($dateString, $blockedDates)) {
                return $dateString;
            }
            
            $current->modify('+1 day');
            $daysChecked++;
        }
        
        return null;
    }
    
    /**
     * Check availability for multiple date ranges at once
     * 
     * @param string $propertyId Property ID
     * @param array $dateRanges Array of ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
     * @return array Array of available ranges
     */
    public function checkMultipleRanges($propertyId, $dateRanges) {
        $available = [];
        
        foreach ($dateRanges as $range) {
            if ($this->isDateRangeAvailable($propertyId, $range['start'], $range['end'])) {
                $available[] = $range;
            }
        }
        
        return $available;
    }
    
    // ========== Private Helper Methods ==========
    
    /**
     * Check database for overlapping bookings
     */
    private function checkDatabaseAvailability($propertyId, $checkIn, $checkOut) {
        if (!$this->pdo) {
            logMessage("Database not available for availability check", 'WARNING');
            return true; // Allow booking if DB unavailable
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count, booking_id, check_in, check_out
                FROM bookings
                WHERE property = :property
                AND status IN ('pending', 'pending_bitcoin', 'confirmed', 'paid')
                AND (
                    (check_in <= :check_in AND check_out > :check_in)
                    OR (check_in < :check_out AND check_out >= :check_out)
                    OR (check_in >= :check_in AND check_out <= :check_out)
                )
            ");
            
            $stmt->execute([
                'property' => $propertyId,
                'check_in' => $checkIn,
                'check_out' => $checkOut
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['count'] > 0) {
                logMessage("Database conflict for $propertyId: $checkIn to $checkOut (Booking: {$result['booking_id']})", 'INFO');
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            logMessage("Database availability check error: " . $e->getMessage(), 'ERROR');
            return true; // Allow booking on error, but log it
        }
    }
    
    /**
     * Check iCal blocked dates
     */
    private function checkICalAvailability($propertyId, $checkIn, $checkOut) {
        $blockedDates = $this->getBlockedDatesFromICal($propertyId);
        
        $current = new DateTime($checkIn);
        $end = new DateTime($checkOut);
        
        while ($current < $end) {
            $dateString = $current->format('Y-m-d');
            if (in_array($dateString, $blockedDates)) {
                logMessage("iCal conflict for $propertyId: $dateString", 'INFO');
                return false;
            }
            $current->modify('+1 day');
        }
        
        return true;
    }
    
    /**
     * Get blocked dates from database bookings
     */
    private function getBlockedDatesFromDatabase($propertyId) {
        $blockedDates = [];
        
        if (!$this->pdo) {
            return $blockedDates;
        }
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT check_in, check_out 
                FROM bookings 
                WHERE property = :property 
                AND status IN ('pending', 'pending_bitcoin', 'confirmed', 'paid')
                AND check_out >= CURDATE()
                ORDER BY check_in
            ");
            
            $stmt->execute(['property' => $propertyId]);
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($bookings as $booking) {
                $checkIn = new DateTime($booking['check_in']);
                $checkOut = new DateTime($booking['check_out']);
                
                $current = clone $checkIn;
                while ($current <= $checkOut) {
                    $blockedDates[] = $current->format('Y-m-d');
                    $current->modify('+1 day');
                }
            }
            
        } catch (Exception $e) {
            logMessage("Failed to get database blocked dates: " . $e->getMessage(), 'ERROR');
        }
        
        return $blockedDates;
    }
    
    /**
     * Get blocked dates from iCal feed (cached)
     */
    private function getBlockedDatesFromICal($propertyId) {
        // Check cache first
        $cacheFile = CACHE_DIR . "/calendar_{$propertyId}.json";
        
        if (file_exists($cacheFile)) {
            $cacheAge = time() - filemtime($cacheFile);
            
            // Use cache if less than 30 minutes old
            if ($cacheAge < 1800) {
                $cachedData = json_decode(file_get_contents($cacheFile), true);
                if (isset($cachedData['blockedDates'])) {
                    return $cachedData['blockedDates'];
                }
            }
        }
        
        // Fetch fresh data using internal request to calendar-sync.php
        try {
            $url = "http://{$_SERVER['HTTP_HOST']}/php/calendar-sync.php?property=$propertyId";
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($result && $result['success']) {
                return $result['blockedDates'] ?? [];
            }
            
        } catch (Exception $e) {
            logMessage("Failed to get iCal blocked dates: " . $e->getMessage(), 'ERROR');
        }
        
        return [];
    }
    
    /**
     * Validate property ID
     */
    private function isValidProperty($propertyId) {
        return in_array($propertyId, ['stone', 'copper', 'cedar']);
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     */
    private function isValidDateFormat($date) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
    }
}

// ========== Standalone Functions (for backward compatibility) ==========

/**
 * Check if date range is available
 * 
 * @param string $propertyId Property ID
 * @param string $checkIn Check-in date
 * @param string $checkOut Check-out date
 * @return bool
 */
function checkAvailability($propertyId, $checkIn, $checkOut) {
    $checker = new AvailabilityChecker();
    return $checker->isDateRangeAvailable($propertyId, $checkIn, $checkOut);
}

/**
 * Get all blocked dates for a property
 * 
 * @param string $propertyId Property ID
 * @return array
 */
function getAllBlockedDates($propertyId) {
    $checker = new AvailabilityChecker();
    return $checker->getBlockedDates($propertyId);
}

/**
 * Clear availability cache
 * 
 * @param string $propertyId Property ID
 */
function clearAvailabilityCache($propertyId) {
    $checker = new AvailabilityChecker();
    $checker->clearCache($propertyId);
}
