# Calendar Availability API - Common Functions

## Overview
The calendar system now uses a centralized `CalendarManager` class that provides common functions for checking availability and blocking dates across all properties (Stone, Copper, Cedar).

## Key Features
✅ **Unified API** - One set of functions for all properties  
✅ **iCal + Database Integration** - Checks both Airbnb iCal and local database bookings  
✅ **Auto-refresh** - Updates every 30 minutes  
✅ **Real-time Availability** - Immediate blocking when bookings are created  

---

## Usage Examples

### Check if a Date is Blocked
```javascript
// Check if a specific date is booked
const isBlocked = calendarManager.isDateBlocked('cedar', '2026-02-15');
console.log(isBlocked); // true or false
```

### Check if Date Range is Available
```javascript
// Check if entire date range is available
const isAvailable = calendarManager.isDateRangeAvailable('stone', '2026-02-01', '2026-02-05');
if (isAvailable) {
    console.log('Dates are available!');
} else {
    console.log('Some dates are blocked');
}
```

### Get Blocked Dates in Range
```javascript
// Get all blocked dates between two dates
const blockedDates = calendarManager.getBlockedDatesInRange('copper', '2026-02-01', '2026-02-10');
console.log('Blocked dates:', blockedDates); // ['2026-02-03', '2026-02-04']
```

### Load/Refresh Calendar Data
```javascript
// Load blocked dates for a property
await calendarManager.loadBlockedDates('cedar');

// Refresh all properties
await calendarManager.refreshAll();
```

### Format Dates
```javascript
// Format JavaScript Date to YYYY-MM-DD
const date = new Date('2026-02-15');
const formatted = calendarManager.formatDate(date);
console.log(formatted); // '2026-02-15'
```

---

## Properties Available

All functions work with these property IDs:
- `stone` - The Stone
- `copper` - The Copper  
- `cedar` - The Cedar

---

## Global Access

The CalendarManager instance is available globally:

```javascript
// Access the manager
window.calendarManager

// Check current blocked dates
console.log(calendarManager.blockedDates.cedar);

// Check selected dates
console.log(calendarManager.selectedDates.stone);
```

---

## Legacy Compatibility

Old function names still work for backwards compatibility:

```javascript
// These still work (but use CalendarManager internally)
isDateBlocked('cedar', '2026-02-15');
formatDate(new Date());
loadCalendarData('stone');
```

---

## API Reference

### CalendarManager Class

#### Properties
- `apiUrls` - API endpoints for each property
- `blockedDates` - Object containing blocked dates for each property
- `currentMonth` - Current displayed month for each calendar
- `selectedDates` - Currently selected check-in/check-out dates
- `cacheTimeout` - Cache refresh interval (30 minutes)

#### Methods

##### `async loadBlockedDates(propertyId)`
Loads blocked dates from backend (iCal + Database)
- **Parameters:** `propertyId` (string) - 'stone', 'copper', or 'cedar'
- **Returns:** Promise<boolean> - true if successful

##### `isDateBlocked(propertyId, dateString)`
Check if a specific date is blocked
- **Parameters:** 
  - `propertyId` (string)
  - `dateString` (string) - Format: 'YYYY-MM-DD'
- **Returns:** boolean

##### `isDateRangeAvailable(propertyId, startDate, endDate)`
Check if entire date range is available
- **Parameters:**
  - `propertyId` (string)
  - `startDate` (string) - Format: 'YYYY-MM-DD'
  - `endDate` (string) - Format: 'YYYY-MM-DD'
- **Returns:** boolean

##### `getBlockedDatesInRange(propertyId, startDate, endDate)`
Get all blocked dates within a range
- **Parameters:**
  - `propertyId` (string)
  - `startDate` (string)
  - `endDate` (string)
- **Returns:** Array<string> - Array of blocked dates

##### `formatDate(date)`
Format JavaScript Date to YYYY-MM-DD
- **Parameters:** `date` (Date)
- **Returns:** string

##### `async refreshAll()`
Refresh calendar data for all properties
- **Returns:** Promise<void>

---

## Integration with Payment System

The calendar automatically updates when:
1. ✅ New booking is created → Cache cleared → Dates blocked immediately
2. ✅ Booking status changes → Cache cleared if status is blocking (pending, confirmed, paid)
3. ✅ Webhook receives payment → Database updated → Next calendar load shows new booking

---

## Example: Custom Availability Check

```javascript
// Check if user's selected dates are available before booking
function validateBookingDates(propertyId, checkIn, checkOut) {
    // Check if dates are available
    if (!calendarManager.isDateRangeAvailable(propertyId, checkIn, checkOut)) {
        // Get specific blocked dates to show user
        const blockedDates = calendarManager.getBlockedDatesInRange(propertyId, checkIn, checkOut);
        alert(`Sorry, these dates are blocked: ${blockedDates.join(', ')}`);
        return false;
    }
    
    // All dates available
    return true;
}

// Usage
if (validateBookingDates('cedar', '2026-02-01', '2026-02-05')) {
    // Proceed to booking
    window.location.href = 'booking.html?property=cedar&checkIn=2026-02-01&checkOut=2026-02-05';
}
```

---

## Files Modified

1. **[js/calendar.js](js/calendar.js)** - Main calendar manager with common functions
2. **[php/calendar-sync.php](php/calendar-sync.php)** - Backend API (iCal + Database)
3. **[php/payment-handler.php](php/payment-handler.php)** - Cache clearing on booking

---

## Benefits

### Before (Old System)
- ❌ Functions scattered throughout code
- ❌ Only checked iCal dates
- ❌ Manual cache management
- ❌ Duplicate code across pages

### After (New System)
- ✅ Centralized CalendarManager class
- ✅ Checks iCal + Database bookings
- ✅ Automatic cache invalidation
- ✅ Reusable functions for all properties
- ✅ Real-time availability updates
