# PHP Availability Checker - Common Functions Guide

## Overview
The PHP availability system now uses a centralized `AvailabilityChecker` class that provides common functions for server-side availability checking across all booking flows.

---

## 📁 Files Created

### 1. **AvailabilityChecker.php** - Main Class
Common PHP class for all availability checking operations.

### 2. **check-availability.php** - API Endpoint  
Standalone API for AJAX availability checks from frontend.

---

## 🚀 Quick Start

### Include in Your PHP File
```php
<?php
require_once 'php/AvailabilityChecker.php';

// Create instance
$checker = new AvailabilityChecker();
```

---

## 📚 Common Functions

### 1. Check if Date Range is Available

```php
// Check if dates are available for booking
$isAvailable = $checker->isDateRangeAvailable('cedar', '2026-02-01', '2026-02-05');

if ($isAvailable) {
    echo "Dates are available!";
} else {
    echo "Dates are blocked";
}
```

### 2. Check if Specific Date is Blocked

```php
// Check if a single date is blocked
$isBlocked = $checker->isDateBlocked('stone', '2026-02-15');

if ($isBlocked) {
    echo "Date is already booked";
}
```

### 3. Get All Blocked Dates

```php
// Get all blocked dates for a property (iCal + Database)
$blockedDates = $checker->getBlockedDates('copper');

echo "Blocked dates: " . implode(', ', $blockedDates);
// Output: 2026-02-01, 2026-02-03, 2026-02-05, ...
```

### 4. Get Blocked Dates in Range

```php
// Find which dates are blocked within a specific range
$blockedInRange = $checker->getBlockedDatesInRange('cedar', '2026-02-01', '2026-02-10');

if (!empty($blockedInRange)) {
    echo "These dates are unavailable: " . implode(', ', $blockedInRange);
}
```

### 5. Clear Cache After Booking

```php
// Clear cache after creating/updating a booking
$checker->clearCache('stone');

// This forces next availability check to fetch fresh data
```

### 6. Find Next Available Date

```php
// Get the next available date after a specific date
$nextAvailable = $checker->getNextAvailableDate('cedar', '2026-02-01', 365);

if ($nextAvailable) {
    echo "Next available date: $nextAvailable";
} else {
    echo "No availability in next 365 days";
}
```

### 7. Check Multiple Ranges

```php
// Check multiple date ranges at once
$ranges = [
    ['start' => '2026-02-01', 'end' => '2026-02-05'],
    ['start' => '2026-02-10', 'end' => '2026-02-14'],
    ['start' => '2026-02-20', 'end' => '2026-02-24']
];

$availableRanges = $checker->checkMultipleRanges('stone', $ranges);

foreach ($availableRanges as $range) {
    echo "Available: {$range['start']} to {$range['end']}\n";
}
```

---

## 💻 Usage Examples

### Example 1: Booking Form Validation

```php
<?php
require_once 'php/AvailabilityChecker.php';

// Get form data
$property = $_POST['property'];
$checkIn = $_POST['checkIn'];
$checkOut = $_POST['checkOut'];

// Check availability
$checker = new AvailabilityChecker();

if (!$checker->isDateRangeAvailable($property, $checkIn, $checkOut)) {
    // Show which dates are blocked
    $blocked = $checker->getBlockedDatesInRange($property, $checkIn, $checkOut);
    
    echo json_encode([
        'success' => false,
        'error' => 'Selected dates are not available',
        'blockedDates' => $blocked
    ]);
    exit;
}

// Proceed with booking...
echo json_encode(['success' => true]);
?>
```

### Example 2: Loading booking.html

```php
<?php
require_once 'php/AvailabilityChecker.php';

// Get query parameters
$property = $_GET['property'] ?? 'cedar';
$checkIn = $_GET['checkIn'] ?? date('Y-m-d');
$checkOut = $_GET['checkOut'] ?? date('Y-m-d', strtotime('+3 days'));

// Verify availability before showing booking form
$checker = new AvailabilityChecker();

if (!$checker->isDateRangeAvailable($property, $checkIn, $checkOut)) {
    // Redirect back with error
    header('Location: properties.html?error=unavailable');
    exit;
}

// Show booking form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book <?php echo ucfirst($property); ?></title>
</head>
<body>
    <h1>Booking Confirmation</h1>
    <p>Property: <?php echo $property; ?></p>
    <p>Check-in: <?php echo $checkIn; ?></p>
    <p>Check-out: <?php echo $checkOut; ?></p>
    <!-- Booking form here -->
</body>
</html>
```

### Example 3: Processing Booking Confirmation

```php
<?php
require_once 'php/AvailabilityChecker.php';

// Get booking data
$bookingData = json_decode(file_get_contents('php://input'), true);

$checker = new AvailabilityChecker();

try {
    // Double-check availability (in case another user just booked)
    if (!$checker->isDateRangeAvailable(
        $bookingData['property'],
        $bookingData['checkIn'],
        $bookingData['checkOut']
    )) {
        throw new Exception('Dates no longer available. Another user just booked these dates.');
    }
    
    // Process payment...
    processPayment($bookingData);
    
    // Create booking in database...
    $bookingId = createBooking($bookingData);
    
    // Clear cache so calendar updates immediately
    $checker->clearCache($bookingData['property']);
    
    echo json_encode([
        'success' => true,
        'bookingId' => $bookingId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

### Example 4: AJAX Availability Check

Frontend JavaScript:
```javascript
// Check availability before submitting form
async function checkAvailability(property, checkIn, checkOut) {
    const response = await fetch('php/check-availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'check_availability',
            property: property,
            checkIn: checkIn,
            checkOut: checkOut
        })
    });
    
    const result = await response.json();
    
    if (result.success && result.available) {
        console.log('Dates are available!');
        return true;
    } else {
        console.log('Dates blocked:', result.blockedDates);
        return false;
    }
}

// Usage
const isAvailable = await checkAvailability('cedar', '2026-02-01', '2026-02-05');
```

---

## 🔌 API Endpoint Usage

### Check Availability
```javascript
fetch('php/check-availability.php', {
    method: 'POST',
    body: JSON.stringify({
        action: 'check_availability',
        property: 'cedar',
        checkIn: '2026-02-01',
        checkOut: '2026-02-05'
    })
})
```

Response:
```json
{
    "success": true,
    "available": true,
    "property": "cedar",
    "checkIn": "2026-02-01",
    "checkOut": "2026-02-05"
}
```

If unavailable:
```json
{
    "success": true,
    "available": false,
    "property": "cedar",
    "checkIn": "2026-02-01",
    "checkOut": "2026-02-05",
    "blockedDates": ["2026-02-02", "2026-02-03"]
}
```

### Get All Blocked Dates
```javascript
fetch('php/check-availability.php', {
    method: 'POST',
    body: JSON.stringify({
        action: 'get_blocked_dates',
        property: 'stone'
    })
})
```

Response:
```json
{
    "success": true,
    "property": "stone",
    "blockedDates": ["2026-02-01", "2026-02-05", "2026-02-10"],
    "count": 3
}
```

### Get Next Available Date
```javascript
fetch('php/check-availability.php', {
    method: 'POST',
    body: JSON.stringify({
        action: 'get_next_available',
        property: 'copper',
        fromDate: '2026-02-01',
        maxDays: 30
    })
})
```

Response:
```json
{
    "success": true,
    "property": "copper",
    "fromDate": "2026-02-01",
    "nextAvailable": "2026-02-07"
}
```

---

## 🔄 How It Works

```
PHP Script
    ↓
new AvailabilityChecker()
    ↓
Checks Database Bookings
    ↓
Checks iCal Feed (cached)
    ↓
Returns: Available / Blocked
```

### Data Sources Checked:
1. **Database** - Active bookings (pending, confirmed, paid)
2. **iCal Feed** - Airbnb bookings (cached for 30 min)

### Booking Statuses That Block Dates:
- `pending` - Payment processing
- `pending_bitcoin` - Waiting for Bitcoin payment
- `confirmed` - Payment confirmed
- `paid` - Fully paid

---

## 🛠️ Integration Points

### 1. booking.html
Add at the top:
```php
<?php
require_once 'php/AvailabilityChecker.php';

// Verify availability before showing form
$property = $_GET['property'] ?? null;
$checkIn = $_GET['checkIn'] ?? null;
$checkOut = $_GET['checkOut'] ?? null;

if ($property && $checkIn && $checkOut) {
    $checker = new AvailabilityChecker();
    if (!$checker->isDateRangeAvailable($property, $checkIn, $checkOut)) {
        // Redirect or show error
        die("These dates are no longer available");
    }
}
?>
```

### 2. Payment Processing
Already integrated in `payment-handler.php`:
```php
// Automatically checks availability before creating booking
$availabilityChecker = new AvailabilityChecker();
if (!$availabilityChecker->isDateRangeAvailable($property, $checkIn, $checkOut)) {
    throw new Exception('Selected dates are no longer available');
}
```

### 3. Booking Confirmation
Automatically clears cache after booking:
```php
// After successful booking
$checker->clearCache($propertyId);
// Calendar immediately shows updated availability
```

---

## ✅ Benefits

### Before (Old System)
- ❌ Duplicate code in multiple files
- ❌ Inconsistent availability checking
- ❌ Hard to maintain

### After (New System)
- ✅ One AvailabilityChecker class for everything
- ✅ Consistent behavior everywhere
- ✅ Easy to use: just 1-2 lines of code
- ✅ Works across all pages (booking.html, payment-handler.php, etc.)
- ✅ Real-time database + iCal checking

---

## 📝 Complete API Reference

### AvailabilityChecker Class Methods

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `isDateRangeAvailable()` | property, checkIn, checkOut | bool | Check if range is available |
| `isDateBlocked()` | property, date | bool | Check if specific date is blocked |
| `getBlockedDates()` | property | array | Get all blocked dates |
| `getBlockedDatesInRange()` | property, start, end | array | Get blocked dates in range |
| `clearCache()` | property | void | Clear calendar cache |
| `getNextAvailableDate()` | property, fromDate, maxDays | string\|null | Find next available date |
| `checkMultipleRanges()` | property, ranges | array | Check multiple ranges |

### Standalone Functions (Backward Compatibility)

| Function | Description |
|----------|-------------|
| `checkAvailability()` | Check date range availability |
| `getAllBlockedDates()` | Get all blocked dates |
| `clearAvailabilityCache()` | Clear cache |

---

## 🎯 Summary

**One Class, All Pages, All Properties**

```php
// Simple, consistent, works everywhere
$checker = new AvailabilityChecker();
$isAvailable = $checker->isDateRangeAvailable('cedar', '2026-02-01', '2026-02-05');
```

Use this in:
- ✅ booking.html (verify before showing form)
- ✅ payment-handler.php (verify before payment)
- ✅ confirm-booking.php (final verification)
- ✅ Any custom booking flow

All functions automatically check **both database bookings AND iCal feeds**! 🎉
