/**
 * SmartStayz - Property Detail Page
 * Handles date selection, price calculation, and booking actions
 */

// Pricing constants
const NIGHTLY_RATE = 150;
const CLEANING_FEE = 75;
const SERVICE_FEE = 25;

/**
 * Initialize property detail page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Set minimum date to today
    const today = new Date().toISOString().split('T')[0];
    const checkInInput = document.getElementById('checkInDate');
    const checkOutInput = document.getElementById('checkOutDate');
    
    if (checkInInput) {
        checkInInput.setAttribute('min', today);
        checkInInput.addEventListener('change', handleCheckInChange);
    }
    
    if (checkOutInput) {
        checkOutInput.setAttribute('min', today);
        checkOutInput.addEventListener('change', calculatePrice);
    }
    
    // Handle guests selection
    const guestsSelect = document.getElementById('guestsSelect');
    if (guestsSelect) {
        guestsSelect.addEventListener('change', updateGuestsDisplay);
    }
    
    // Initialize pricing display
    updatePricing(0, 0);
});

/**
 * Handle check-in date change
 */
function handleCheckInChange(event) {
    const checkInDate = new Date(event.target.value);
    const checkOutInput = document.getElementById('checkOutDate');
    
    // Set minimum check-out to day after check-in
    const minCheckOut = new Date(checkInDate);
    minCheckOut.setDate(minCheckOut.getDate() + 1);
    checkOutInput.setAttribute('min', minCheckOut.toISOString().split('T')[0]);
    
    // Clear check-out if it's before new minimum
    if (checkOutInput.value) {
        const checkOutDate = new Date(checkOutInput.value);
        if (checkOutDate <= checkInDate) {
            checkOutInput.value = '';
        }
    }
    
    calculatePrice();
}

/**
 * Calculate and update pricing
 */
function calculatePrice() {
    const checkInInput = document.getElementById('checkInDate');
    const checkOutInput = document.getElementById('checkOutDate');
    
    if (!checkInInput.value || !checkOutInput.value) {
        updatePricing(0, 0);
        return;
    }
    
    const checkInDate = new Date(checkInInput.value);
    const checkOutDate = new Date(checkOutInput.value);
    
    // Validate dates
    if (checkOutDate <= checkInDate) {
        updatePricing(0, 0);
        return;
    }
    
    // Calculate nights
    const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
    
    // Calculate total
    const nightsTotal = NIGHTLY_RATE * nights;
    const total = nightsTotal + CLEANING_FEE + SERVICE_FEE;
    
    updatePricing(nights, nightsTotal, total);
}

/**
 * Update pricing display
 */
function updatePricing(nights, nightsTotal, total = 0) {
    const nightsCountEl = document.getElementById('nightsCount');
    const nightsTotalEl = document.getElementById('nightsTotal');
    const totalPriceEl = document.getElementById('totalPrice');
    
    if (nightsCountEl) nightsCountEl.textContent = nights;
    if (nightsTotalEl) nightsTotalEl.textContent = `$${nightsTotal}`;
    
    if (totalPriceEl) {
        if (nights === 0) {
            totalPriceEl.textContent = '$' + (CLEANING_FEE + SERVICE_FEE);
        } else {
            totalPriceEl.textContent = `$${total}`;
        }
    }
}

/**
 * Update pricing from calendar date selection
 */
window.updatePropertyPricingFromDates = function(propertyId, checkIn, checkOut) {
    if (!checkIn || !checkOut) {
        updatePricing(0, 0);
        return;
    }

    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);

    if (isNaN(checkInDate.getTime()) || isNaN(checkOutDate.getTime())) {
        updatePricing(0, 0);
        return;
    }

    if (checkOutDate <= checkInDate) {
        updatePricing(0, 0);
        return;
    }

    const nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
    const nightsTotal = NIGHTLY_RATE * nights;
    const total = nightsTotal + CLEANING_FEE + SERVICE_FEE;

    updatePricing(nights, nightsTotal, total);
};

/**
 * Update guests display (for future use if needed)
 */
function updateGuestsDisplay(event) {
    // Currently just for tracking, could be used for pricing adjustments
    console.log('Guests selected:', event.target.value);
}

/**
 * Book property function (called from HTML onclick)
 * This function is referenced in the HTML files
 */
window.bookProperty = function(propertyId) {
    const checkInInput = document.getElementById('checkInDate');
    const checkOutInput = document.getElementById('checkOutDate');
    const guestsSelect = document.getElementById('guestsSelect');
    
    const checkIn = checkInInput ? checkInInput.value : null;
    const checkOut = checkOutInput ? checkOutInput.value : null;
    const guests = guestsSelect ? guestsSelect.value : '2';
    
    // Validate dates
    if (!checkIn || !checkOut) {
        alert('Please select both check-in and check-out dates');
        return;
    }
    
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    
    if (checkOutDate <= checkInDate) {
        alert('Check-out date must be after check-in date');
        return;
    }
    
    // Build URL with parameters
    const url = `booking.html?property=${propertyId}&checkIn=${checkIn}&checkOut=${checkOut}&guests=${guests}`;
    
    // Navigate to booking page
    window.location.href = url;
};

/**
 * Smooth scroll to calendar section when clicked
 */
const calendarSection = document.querySelector('.calendar-section');
if (calendarSection) {
    // Add click handler to "Check Availability" button that scrolls to calendar
    // if dates aren't selected yet
    const checkAvailBtn = document.querySelector('.booking-card .btn-primary');
    if (checkAvailBtn) {
        const originalOnClick = checkAvailBtn.onclick;
        checkAvailBtn.onclick = function() {
            const checkInInput = document.getElementById('checkInDate');
            const checkOutInput = document.getElementById('checkOutDate');
            
            // If dates not selected, scroll to calendar first
            if (!checkInInput.value || !checkOutInput.value) {
                calendarSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                return false;
            }
            
            // Otherwise proceed with original function
            if (originalOnClick) {
                return originalOnClick.call(this);
            }
        };
    }
}

/**
 * Image gallery interaction (for future enhancement)
 */
document.querySelectorAll('.hero-grid-image').forEach((img, index) => {
    img.addEventListener('click', function() {
        // Future: Could implement lightbox gallery here
        console.log('Image clicked:', index);
    });
});

/**
 * Auto-calculate price when page loads with URL parameters
 */
window.addEventListener('load', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const checkIn = urlParams.get('checkIn');
    const checkOut = urlParams.get('checkOut');
    const guests = urlParams.get('guests');
    
    if (checkIn && checkOut) {
        const checkInInput = document.getElementById('checkInDate');
        const checkOutInput = document.getElementById('checkOutDate');
        
        if (checkInInput) checkInInput.value = checkIn;
        if (checkOutInput) checkOutInput.value = checkOut;
        
        calculatePrice();
    }
    
    if (guests) {
        const guestsSelect = document.getElementById('guestsSelect');
        if (guestsSelect) guestsSelect.value = guests;
    }
});
