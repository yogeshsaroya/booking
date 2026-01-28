/**
 * SmartStayz - Booking Confirmation Page
 * Displays booking confirmation details
 */

// Property data for reference
const PROPERTIES = {
    stone: {
        name: 'The Stone',
        subtitle: 'Exquisite Urban-Industrial Stay',
        nightlyRate: 150,
        cleaningFee: 75
    },
    copper: {
        name: 'The Copper',
        subtitle: 'Vibrant Maximalist Retreat',
        nightlyRate: 150,
        cleaningFee: 75
    },
    cedar: {
        name: 'The Cedar',
        subtitle: 'Rustic-Chic Hyde Park Haven',
        nightlyRate: 150,
        cleaningFee: 75
    }
};

/**
 * Initialize confirmation page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get booking ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const bookingId = urlParams.get('booking');
    
    if (bookingId) {
        // In a real application, fetch booking data from server
        // For now, we'll load from sessionStorage if available
        const bookingData = sessionStorage.getItem('bookingData');
        if (bookingData) {
            const data = JSON.parse(bookingData);
            displayConfirmationDetails(bookingId, data);
            sessionStorage.removeItem('bookingData');
        } else {
            // Fallback: Try to fetch from server
            fetchBookingDetails(bookingId);
        }
    } else {
        // No booking ID provided
        document.querySelector('.confirmation-card').innerHTML = `
            <div style="text-align: center; padding: 2rem;">
                <h2>Booking Not Found</h2>
                <p>We couldn't find your booking confirmation.</p>
                <a href="properties.html" class="btn btn-primary">Continue Shopping</a>
            </div>
        `;
    }
});

/**
 * Display confirmation details
 */
function displayConfirmationDetails(bookingId, data) {
    // Booking Details
    document.getElementById('bookingId').textContent = bookingId;
    document.getElementById('bookingIdNext').textContent = bookingId;
    
    // Property info
    const propertyData = PROPERTIES[data.property] || {};
    document.getElementById('propertyName').textContent = propertyData.name || data.property;
    
    // Dates
    const checkInDate = new Date(data.checkIn);
    const checkOutDate = new Date(data.checkOut);
    document.getElementById('checkInDate').textContent = formatDate(checkInDate);
    document.getElementById('checkOutDate').textContent = formatDate(checkOutDate);
    document.getElementById('nightsCount').textContent = data.nights || '-';
    
    // Guests
    document.getElementById('guestCount').textContent = data.guests || '2';
    
    // Guest Information
    document.getElementById('guestName').textContent = `${data.firstName || ''} ${data.lastName || ''}`.trim();
    document.getElementById('guestEmail').textContent = data.email || '-';
    document.getElementById('guestPhone').textContent = data.phone || '-';
    
    // Pets
    if (data.hasPets) {
        document.getElementById('petRowContainer').style.display = 'flex';
        document.getElementById('petStatus').textContent = 'Yes, pets will be accompanying';
    }
    
    // Payment Summary
    const nightlyRate = propertyData.nightlyRate || 150;
    const cleaningFee = propertyData.cleaningFee || 75;
    const nights = data.nights || 0;
    const serviceFee = 25;
    const nightsTotal = nightlyRate * nights;
    const total = nightsTotal + cleaningFee + serviceFee;
    
    document.getElementById('nightlyRate').textContent = `$${nightlyRate}`;
    document.getElementById('nightsLabel').textContent = `${nights} night${nights !== 1 ? 's' : ''}`;
    document.getElementById('nightsTotal').textContent = `$${nightsTotal}`;
    document.getElementById('cleaningFee').textContent = `$${cleaningFee}`;
    document.getElementById('serviceFee').textContent = `$${serviceFee}`;
    document.getElementById('totalAmount').textContent = `$${total}`;
    
    // Payment Method
    const paymentMethodText = getPaymentMethodText(data.paymentMethod);
    document.getElementById('paymentMethodText').textContent = paymentMethodText;
}

/**
 * Get payment method display text
 */
function getPaymentMethodText(method) {
    const methods = {
        'stripe': 'Credit/Debit Card (Stripe) - Your card will be charged immediately upon confirmation.',
        'bitcoin': 'Bitcoin (₿) - Payment instructions have been sent to your email. Payment must be received within 48 hours to confirm booking.',
        'venmo': 'Venmo / CashApp - Payment instructions have been sent to your email. Payment must be received within 24 hours to confirm booking.'
    };
    return methods[method] || 'Payment pending';
}

/**
 * Format date to readable format
 */
function formatDate(date) {
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('en-US', options);
}

/**
 * Fetch booking details from server (fallback)
 */
async function fetchBookingDetails(bookingId) {
    try {
        const response = await fetch(`php/get-booking.php?id=${bookingId}`);
        const data = await response.json();
        
        if (data.success) {
            displayConfirmationDetails(bookingId, data.booking);
        } else {
            showError('Booking not found. Please check your booking ID.');
        }
    } catch (error) {
        console.error('Error fetching booking:', error);
        showError('Unable to load booking details. Please try again later.');
    }
}

/**
 * Show error message
 */
function showError(message) {
    document.querySelector('.confirmation-card').innerHTML = `
        <div style="text-align: center; padding: 2rem;">
            <h2>Error</h2>
            <p>${message}</p>
            <a href="properties.html" class="btn btn-primary">Continue Shopping</a>
        </div>
    `;
}

// Store booking data for display if redirected directly from booking form
function storeBookingData(data) {
    sessionStorage.setItem('bookingData', JSON.stringify(data));
}
