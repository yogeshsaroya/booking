/**
 * SmartStayz - Calendar Sync
 * Handles one-way iCal sync from Airbnb
 */

// Property iCal URLs (these will be loaded from PHP config in production)
const PROPERTY_ICAL_URLS = {
    stone: 'php/calendar-sync.php?property=stone',
    copper: 'php/calendar-sync.php?property=copper',
    cedar: 'php/calendar-sync.php?property=cedar'
};

// Store for calendar data
let calendarData = {
    stone: [],
    copper: [],
    cedar: []
};

// Current month display for each calendar
let currentMonth = {
    stone: new Date(),
    copper: new Date(),
    cedar: new Date()
};

// Selected dates for each property
let selectedDates = {
    stone: { checkIn: null, checkOut: null },
    copper: { checkIn: null, checkOut: null },
    cedar: { checkIn: null, checkOut: null }
};

/**
 * Initialize calendars on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Load calendar data for each property
    Object.keys(PROPERTY_ICAL_URLS).forEach(propertyId => {
        loadCalendarData(propertyId);
    });
});

/**
 * Load calendar data from backend
 */
async function loadCalendarData(propertyId) {
    const calendarContainer = document.querySelector(`#calendar-${propertyId} .calendar-container`);
    
    if (!calendarContainer) return;
    
    try {
        const response = await fetch(PROPERTY_ICAL_URLS[propertyId]);
        const data = await response.json();
        
        if (data.success) {
            calendarData[propertyId] = data.blockedDates || [];
            renderCalendar(propertyId);
        } else {
            showCalendarError(calendarContainer, 'Unable to load calendar. Please try again later.');
        }
    } catch (error) {
        console.error('Error loading calendar:', error);
        showCalendarError(calendarContainer, 'Unable to load calendar. Please try again later.');
    }
}

/**
 * Show calendar error message
 */
function showCalendarError(container, message) {
    container.innerHTML = `
        <div class="calendar-error" style="text-align: center; padding: 2rem; color: var(--stone-gray);">
            <p>${message}</p>
            <p style="margin-top: 1rem; font-size: 0.9rem;">
                <a href="index.html#contact" style="color: var(--copper-accent);">Contact us</a> for availability
            </p>
        </div>
    `;
}

/**
 * Render calendar for a property
 */
function renderCalendar(propertyId) {
    const calendarContainer = document.querySelector(`#calendar-${propertyId} .calendar-container`);
    if (!calendarContainer) return;
    
    const month = currentMonth[propertyId];
    const year = month.getFullYear();
    const monthIndex = month.getMonth();
    
    // Get month details
    const firstDay = new Date(year, monthIndex, 1);
    const lastDay = new Date(year, monthIndex + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startDayOfWeek = firstDay.getDay();
    
    // Month names
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];
    
    // Build calendar HTML
    let calendarHTML = `
        <div class="calendar">
            <div class="calendar-header">
                <div class="calendar-month">${monthNames[monthIndex]} ${year}</div>
                <div class="calendar-nav">
                    <button onclick="changeMonth('${propertyId}', -1)">‹ Prev</button>
                    <button onclick="changeMonth('${propertyId}', 1)">Next ›</button>
                </div>
            </div>
            <div class="calendar-grid">
                <div class="calendar-day-header">Sun</div>
                <div class="calendar-day-header">Mon</div>
                <div class="calendar-day-header">Tue</div>
                <div class="calendar-day-header">Wed</div>
                <div class="calendar-day-header">Thu</div>
                <div class="calendar-day-header">Fri</div>
                <div class="calendar-day-header">Sat</div>
    `;
    
    // Add empty cells for days before month starts
    for (let i = 0; i < startDayOfWeek; i++) {
        calendarHTML += '<div class="calendar-day empty"></div>';
    }
    
    // Add days of the month
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    for (let day = 1; day <= daysInMonth; day++) {
        const currentDate = new Date(year, monthIndex, day);
        const dateString = formatDate(currentDate);
        
        let dayClass = 'calendar-day';
        
        // Check if date is in the past
        if (currentDate < today) {
            dayClass += ' past';
        }
        // Check if date is blocked
        else if (isDateBlocked(propertyId, dateString)) {
            dayClass += ' unavailable';
        }
        
        calendarHTML += `<div class="${dayClass}" data-date="${dateString}">${day}</div>`;
    }
    
    calendarHTML += `
            </div>
            <div class="calendar-legend">
                <div class="legend-item">
                    <div class="legend-box available"></div>
                    <span>Available</span>
                </div>
                <div class="legend-item">
                    <div class="legend-box unavailable"></div>
                    <span>Booked</span>
                </div>
            </div>
        </div>
    `;
    
    calendarContainer.innerHTML = calendarHTML;
    
    // Add click handlers to available days
    const days = calendarContainer.querySelectorAll('.calendar-day:not(.unavailable):not(.past):not(.empty)');
    days.forEach(day => {
        day.addEventListener('click', function() {
            handleDayClick(propertyId, this.dataset.date);
        });
    });
}

/**
 * Change displayed month
 */
function changeMonth(propertyId, direction) {
    const month = currentMonth[propertyId];
    month.setMonth(month.getMonth() + direction);
    
    // Don't allow going back before current month
    const now = new Date();
    if (month < new Date(now.getFullYear(), now.getMonth(), 1)) {
        month.setMonth(now.getMonth());
        month.setFullYear(now.getFullYear());
    }
    
    renderCalendar(propertyId);
}

/**
 * Check if a date is blocked
 */
function isDateBlocked(propertyId, dateString) {
    return calendarData[propertyId].includes(dateString);
}

/**
 * Format date as YYYY-MM-DD
 */
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

/**
 * Handle day click for date selection
 */
function handleDayClick(propertyId, dateString) {
    console.log('Day clicked:', propertyId, dateString);
    const dates = selectedDates[propertyId];
    console.log('Current dates:', dates);
    const clickedDate = new Date(dateString);
    
    // If no check-in selected, set it
    if (!dates.checkIn) {
        dates.checkIn = dateString;
        updateCalendarSelection(propertyId);
    }
    // If check-in selected but no check-out, set check-out
    else if (!dates.checkOut) {
        const checkInDate = new Date(dates.checkIn);
        
        // Ensure check-out is after check-in
        if (clickedDate > checkInDate) {
            // Check if there are any blocked dates in between
            if (!hasBlockedDatesBetween(propertyId, dates.checkIn, dateString)) {
                dates.checkOut = dateString;
                // Hide error message if dates are now valid
                const errorMsg = document.getElementById(`errorMessage-${propertyId}`);
                if (errorMsg) errorMsg.style.display = 'none';
                updateCalendarSelection(propertyId);
            } else {
                // Show error message instead of alert
                const errorMsg = document.getElementById(`errorMessage-${propertyId}`);
                if (errorMsg) {
                    errorMsg.style.display = 'block';
                }
                // Reset dates after showing error
                dates.checkIn = null;
                dates.checkOut = null;
                updateCalendarSelection(propertyId);
            }
        } else {
            // Show error message - check-out must be after check-in
            const errorMsg = document.getElementById(`errorMessage-${propertyId}`);
            if (errorMsg) {
                errorMsg.style.display = 'block';
                // Update error message text
                errorMsg.querySelector('p').textContent = 'Check-out date must be after check-in date. Please choose different dates.';
            }
            // Reset dates
            dates.checkIn = null;
            dates.checkOut = null;
            updateCalendarSelection(propertyId);
        }
    }
    // Reset if both are selected
    else {
        dates.checkIn = dateString;
        dates.checkOut = null;
        updateCalendarSelection(propertyId);
    }
}

/**
 * Check if there are blocked dates between two dates
 */
function hasBlockedDatesBetween(propertyId, startDate, endDate) {
    const start = new Date(startDate);
    const end = new Date(endDate);
    const current = new Date(start);
    current.setDate(current.getDate() + 1); // Start checking from day after check-in
    
    while (current < end) {
        if (isDateBlocked(propertyId, formatDate(current))) {
            return true;
        }
        current.setDate(current.getDate() + 1);
    }
    
    return false;
}

/**
 * Update calendar visual selection
 */
function updateCalendarSelection(propertyId) {
    console.log('updateCalendarSelection called for:', propertyId);
    const calendarContainer = document.querySelector(`#calendar-${propertyId} .calendar-container`);
    console.log('Calendar container:', calendarContainer);
    if (!calendarContainer) return;
    
    const dates = selectedDates[propertyId];
    console.log('Dates for display:', dates);
    const days = calendarContainer.querySelectorAll('.calendar-day');
    
    // Remove all selection classes
    days.forEach(day => {
        day.classList.remove('selected', 'in-range');
    });
    
    // Update message and button
    const messageDiv = document.getElementById(`selectedDates-${propertyId}`);
    const bookBtn = document.getElementById(`bookBtn-${propertyId}`);
    console.log('Message div:', messageDiv);
    console.log('Book button:', bookBtn);
    
    // For property detail pages, check sidebar elements if main ones don't exist
    const sidebarMessage = document.getElementById(`selectedDates-${propertyId}-sidebar`);
    const sidebarBookBtn = document.getElementById(`bookBtn-${propertyId}-sidebar`);
    const sidebarCheckInDisplay = document.getElementById(`checkInDisplay-${propertyId}-sidebar`);
    const sidebarCheckOutDisplay = document.getElementById(`checkOutDisplay-${propertyId}-sidebar`);
    const sidebarError = document.getElementById(`errorMessage-${propertyId}-sidebar`);
    
    const pricingBreakdown = document.getElementById(`pricingBreakdown-${propertyId}`);
    const noChargeNote = document.getElementById(`noCharge-${propertyId}`);

    if (!dates.checkIn) {
        // Hide success message and disable button
        // Keep error message visible if it's showing
        if (messageDiv) messageDiv.style.display = 'none';
        if (bookBtn) bookBtn.disabled = true;
        if (sidebarMessage) sidebarMessage.style.display = 'none';
        if (sidebarBookBtn) sidebarBookBtn.disabled = true;

        if (pricingBreakdown) pricingBreakdown.style.display = 'none';
        if (noChargeNote) noChargeNote.style.display = 'none';
        return;
    }
    
    // Highlight check-in
    days.forEach(day => {
        if (day.dataset.date === dates.checkIn) {
            day.classList.add('selected');
        }
        
        // Highlight range if check-out is selected
        if (dates.checkOut) {
            const dayDate = new Date(day.dataset.date);
            const checkIn = new Date(dates.checkIn);
            const checkOut = new Date(dates.checkOut);
            
            if (dayDate > checkIn && dayDate < checkOut) {
                day.classList.add('in-range');
            } else if (day.dataset.date === dates.checkOut) {
                day.classList.add('selected');
            }
        }
    });
    
    // Show message and update button when dates are selected
    if (dates.checkIn && dates.checkOut) {
        // Update main calendar elements if they exist (properties.html)
        if (messageDiv && bookBtn) {
            const checkInDisplay = document.getElementById(`checkInDisplay-${propertyId}`);
            const checkOutDisplay = document.getElementById(`checkOutDisplay-${propertyId}`);
            if (checkInDisplay) checkInDisplay.textContent = dates.checkIn;
            if (checkOutDisplay) checkOutDisplay.textContent = dates.checkOut;
            messageDiv.style.display = 'block';
            bookBtn.disabled = false;
            // Hide error message when valid dates are selected
            const errorMsg = document.getElementById(`errorMessage-${propertyId}`);
            if (errorMsg) errorMsg.style.display = 'none';
        }
        
        // Update sidebar elements (property detail pages)
        if (sidebarMessage && sidebarBookBtn) {
            if (sidebarCheckInDisplay) sidebarCheckInDisplay.textContent = dates.checkIn;
            if (sidebarCheckOutDisplay) sidebarCheckOutDisplay.textContent = dates.checkOut;
            sidebarMessage.style.display = 'block';
            sidebarBookBtn.disabled = false;
            if (sidebarError) sidebarError.style.display = 'none';
        }

        if (pricingBreakdown) pricingBreakdown.style.display = 'block';
        if (noChargeNote) noChargeNote.style.display = 'block';

        if (typeof window.updatePropertyPricingFromDates === 'function') {
            window.updatePropertyPricingFromDates(propertyId, dates.checkIn, dates.checkOut);
        }
    } else if (dates.checkIn) {
        // Only check-in selected, hide message but keep button disabled
        if (messageDiv) messageDiv.style.display = 'none';
        if (bookBtn) bookBtn.disabled = true;
        if (sidebarMessage) sidebarMessage.style.display = 'none';
        if (sidebarBookBtn) sidebarBookBtn.disabled = true;

        if (pricingBreakdown) pricingBreakdown.style.display = 'none';
        if (noChargeNote) noChargeNote.style.display = 'none';

        if (typeof window.updatePropertyPricingFromDates === 'function') {
            window.updatePropertyPricingFromDates(propertyId, null, null);
        }
    } else {
        if (typeof window.updatePropertyPricingFromDates === 'function') {
            window.updatePropertyPricingFromDates(propertyId, null, null);
        }

        if (pricingBreakdown) pricingBreakdown.style.display = 'none';
        if (noChargeNote) noChargeNote.style.display = 'none';
    }
}

/**
 * Highlight date range (called from main.js)
 */
window.highlightDateRange = function(checkIn, checkOut) {
    Object.keys(selectedDates).forEach(propertyId => {
        selectedDates[propertyId] = { checkIn, checkOut };
        updateCalendarSelection(propertyId);
    });
};

/**
 * Book property with specific dates from calendar selection
 */
function bookPropertyWithDates(propertyId, checkIn, checkOut, guests) {
    // Use passed guests value or get from dropdown with fallback to 1
    const guestCount = guests || document.getElementById(`guests-${propertyId}`)?.value || '1';
    const url = `booking.html?property=${propertyId}&checkIn=${checkIn}&checkOut=${checkOut}&guests=${guestCount}`;
    window.location.href = url;
}

/**
 * Refresh calendar data every 30 minutes
 */
setInterval(() => {
    Object.keys(PROPERTY_ICAL_URLS).forEach(propertyId => {
        loadCalendarData(propertyId);
    });
}, 30 * 60 * 1000); // 30 minutes
