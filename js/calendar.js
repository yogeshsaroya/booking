/**
 * SmartStayz - Calendar Sync
 * Handles one-way iCal sync from Airbnb
 */

// Property iCal URLs (these will be loaded from PHP config in production)
const PROPERTY_ICAL_URLS = {
    stone: '/php/calendar-sync.php?property=stone',
    copper: '/php/calendar-sync.php?property=copper',
    cedar: '/php/calendar-sync.php?property=cedar'
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
let selectedDates = {
    stone: { checkIn: null, checkOut: null },
    copper: { checkIn: null, checkOut: null },
    cedar: { checkIn: null, checkOut: null }
};

function handleDayClick(propertyId, dateString) {
    const dates = selectedDates[propertyId];
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
                updateCalendarSelection(propertyId);
                
                // Ask if user wants to book
                if (confirm(`Book from ${dates.checkIn} to ${dates.checkOut}?`)) {
                    bookPropertyWithDates(propertyId, dates.checkIn, dates.checkOut);
                }
            } else {
                alert('There are booked dates in your selected range. Please choose different dates.');
                dates.checkIn = null;
                updateCalendarSelection(propertyId);
            }
        } else {
            alert('Check-out must be after check-in');
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
    const calendarContainer = document.querySelector(`#calendar-${propertyId} .calendar-container`);
    if (!calendarContainer) return;
    
    const dates = selectedDates[propertyId];
    const days = calendarContainer.querySelectorAll('.calendar-day');
    
    // Remove all selection classes
    days.forEach(day => {
        day.classList.remove('selected', 'in-range');
    });
    
    if (!dates.checkIn) return;
    
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
function bookPropertyWithDates(propertyId, checkIn, checkOut) {
    const guests = document.getElementById('guests')?.value || '1';
    const url = `booking.html?property=${propertyId}&checkIn=${checkIn}&checkOut=${checkOut}&guests=${guests}`;
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
