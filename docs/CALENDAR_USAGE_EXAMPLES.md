# Calendar Common Functions - Usage Examples

## For Developers: Using Calendar Functions Across All Properties

The calendar system now provides **common functions** that work identically for all properties (Stone, Copper, Cedar). You no longer need to write property-specific code!

---

## 🚀 Quick Start

### Include the calendar script in your HTML:
```html
<link rel="stylesheet" href="css/calendar.css">
<script src="js/calendar.js"></script>
```

That's it! The `CalendarManager` is automatically initialized.

---

## 📅 Common Functions Available

### 1. Check if a Date is Blocked

```javascript
// Works for any property: 'stone', 'copper', or 'cedar'
const isBlocked = calendarManager.isDateBlocked('cedar', '2026-02-15');

if (isBlocked) {
    console.log('Date is already booked');
} else {
    console.log('Date is available');
}
```

### 2. Check if Date Range is Available

```javascript
// Check entire range - perfect before showing booking form
const startDate = '2026-02-01';
const endDate = '2026-02-05';

if (calendarManager.isDateRangeAvailable('stone', startDate, endDate)) {
    // Show booking button
    document.getElementById('bookBtn').disabled = false;
} else {
    // Show error message
    alert('Some dates in this range are already booked');
}
```

### 3. Get List of Blocked Dates in Range

```javascript
// Find exactly which dates are blocked
const blockedDates = calendarManager.getBlockedDatesInRange(
    'copper', 
    '2026-02-01', 
    '2026-02-10'
);

console.log('These dates are booked:', blockedDates);
// Output: ['2026-02-03', '2026-02-04', '2026-02-07']
```

### 4. Format Dates Consistently

```javascript
// Convert JavaScript Date to YYYY-MM-DD format
const today = new Date();
const formatted = calendarManager.formatDate(today);
console.log(formatted); // '2026-01-28'
```

### 5. Refresh Calendar Data

```javascript
// Refresh one property
await calendarManager.loadBlockedDates('cedar');

// Or refresh all properties at once
await calendarManager.refreshAll();
```

---

## 💡 Real-World Examples

### Example 1: Validate User Selection Before Booking

```javascript
function validateAndBook(propertyId) {
    const checkIn = document.getElementById('check-in-input').value;
    const checkOut = document.getElementById('check-out-input').value;
    
    // Use common function - works for ANY property
    if (!calendarManager.isDateRangeAvailable(propertyId, checkIn, checkOut)) {
        const blocked = calendarManager.getBlockedDatesInRange(propertyId, checkIn, checkOut);
        alert(`Sorry, these dates are unavailable: ${blocked.join(', ')}`);
        return;
    }
    
    // Proceed to booking page
    window.location.href = `booking.html?property=${propertyId}&checkIn=${checkIn}&checkOut=${checkOut}`;
}
```

### Example 2: Show Dynamic Availability Message

```javascript
function updateAvailabilityMessage(propertyId, startDate, endDate) {
    const messageDiv = document.getElementById('availability-message');
    
    // Common function works for all properties
    if (calendarManager.isDateRangeAvailable(propertyId, startDate, endDate)) {
        messageDiv.innerHTML = '✅ These dates are available!';
        messageDiv.className = 'success';
    } else {
        const blockedDates = calendarManager.getBlockedDatesInRange(propertyId, startDate, endDate);
        messageDiv.innerHTML = `❌ Unavailable dates: ${blockedDates.join(', ')}`;
        messageDiv.className = 'error';
    }
}
```

### Example 3: Prevent Overlapping Bookings

```javascript
// Before submitting booking form
document.getElementById('booking-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const property = this.querySelector('[name="property"]').value;
    const checkIn = this.querySelector('[name="checkIn"]').value;
    const checkOut = this.querySelector('[name="checkOut"]').value;
    
    // Refresh data to ensure we have latest bookings
    await calendarManager.loadBlockedDates(property);
    
    // Double-check availability with common function
    if (!calendarManager.isDateRangeAvailable(property, checkIn, checkOut)) {
        alert('Sorry, these dates were just booked by another user. Please select different dates.');
        return;
    }
    
    // Safe to submit
    this.submit();
});
```

---

## 🎯 Usage in Different Pages

### properties.html
```javascript
// Show availability for multiple properties at once
['stone', 'copper', 'cedar'].forEach(async propertyId => {
    await calendarManager.loadBlockedDates(propertyId);
    
    const container = document.getElementById(`calendar-${propertyId}`);
    const blockedCount = calendarManager.blockedDates[propertyId].length;
    
    container.querySelector('.info').textContent = `${blockedCount} dates booked`;
});
```

### property-cedar.html (or any individual property page)
```javascript
// Load calendar for specific property
document.addEventListener('DOMContentLoaded', async function() {
    const propertyId = 'cedar'; // Or 'stone', 'copper'
    
    // Common function loads iCal + Database bookings
    await calendarManager.loadBlockedDates(propertyId);
    
    // Calendar automatically renders with blocked dates
});
```

### booking.html
```javascript
// Verify availability before payment
async function processPayment(propertyId, checkIn, checkOut) {
    // Refresh to get latest data
    await calendarManager.loadBlockedDates(propertyId);
    
    // Final availability check using common function
    if (!calendarManager.isDateRangeAvailable(propertyId, checkIn, checkOut)) {
        throw new Error('Dates no longer available');
    }
    
    // Process payment...
}
```

---

## 🔄 How It Works Behind the Scenes

```
User selects dates
       ↓
calendarManager.isDateRangeAvailable()
       ↓
Checks blockedDates array
       ↓
blockedDates comes from:
  - Airbnb iCal feed
  - Database bookings (pending, confirmed, paid)
       ↓
Returns true/false
       ↓
UI updates accordingly
```

---

## ⚡ Key Benefits

### ✅ Works for ALL Properties
```javascript
// Same function, different property - that's it!
calendarManager.isDateBlocked('stone', '2026-02-15');
calendarManager.isDateBlocked('copper', '2026-02-15');
calendarManager.isDateBlocked('cedar', '2026-02-15');
```

### ✅ Real-Time Updates
```javascript
// When a booking is created:
// 1. Database updated
// 2. Cache automatically cleared
// 3. Next calendar load shows new blocked dates
// 4. No manual intervention needed!
```

### ✅ Single Source of Truth
```javascript
// All pages use the same CalendarManager
// No duplicate code
// Consistent behavior everywhere
```

---

## 🛠️ Advanced Usage

### Custom Availability Widget

```javascript
class AvailabilityChecker {
    constructor(propertyId) {
        this.propertyId = propertyId;
    }
    
    async checkNextAvailableDate(fromDate) {
        await calendarManager.loadBlockedDates(this.propertyId);
        
        let current = new Date(fromDate);
        const maxDays = 365; // Check up to 1 year
        let daysChecked = 0;
        
        while (daysChecked < maxDays) {
            const dateString = calendarManager.formatDate(current);
            
            // Use common function
            if (!calendarManager.isDateBlocked(this.propertyId, dateString)) {
                return dateString;
            }
            
            current.setDate(current.getDate() + 1);
            daysChecked++;
        }
        
        return null; // No availability found
    }
}

// Usage
const checker = new AvailabilityChecker('cedar');
const nextAvailable = await checker.checkNextAvailableDate('2026-02-01');
console.log('Next available date:', nextAvailable);
```

### Batch Availability Check

```javascript
// Check multiple date ranges at once
async function findBestAvailability(propertyId, dateRanges) {
    await calendarManager.loadBlockedDates(propertyId);
    
    const available = dateRanges.filter(range => 
        calendarManager.isDateRangeAvailable(propertyId, range.start, range.end)
    );
    
    return available;
}

// Usage
const ranges = [
    { start: '2026-02-01', end: '2026-02-05' },
    { start: '2026-02-10', end: '2026-02-14' },
    { start: '2026-02-20', end: '2026-02-24' }
];

const availableRanges = await findBestAvailability('stone', ranges);
console.log('Available date ranges:', availableRanges);
```

---

## 📝 Summary

**One CalendarManager, All Properties**

```javascript
// Everything you need:
calendarManager.isDateBlocked(propertyId, date)
calendarManager.isDateRangeAvailable(propertyId, start, end)
calendarManager.getBlockedDatesInRange(propertyId, start, end)
calendarManager.formatDate(date)
calendarManager.loadBlockedDates(propertyId)
calendarManager.refreshAll()
```

No more writing property-specific code. Just use these common functions everywhere! 🎉
