/**
 * SmartStayz - Booking Page
 * Handles booking form, payment processing, and price calculation
 */

// Property data
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

// Stripe configuration
let stripe;
let cardElement;
const STRIPE_PUBLIC_KEY = 'pk_test_51SsksaCm3m1aQwAMQWOohMVFKgeNOJ5cbAXJKxC8yrIoLf0ciWkDNN7d4HuzA4Wv7uHroLnsC6E4e5SzzkQ0DxtD00cVn1SGau'; // Replace with actual key

// Booking data
let bookingData = {
    property: null,
    checkIn: null,
    checkOut: null,
    guests: 2,
    nights: 0,
    nightlyRate: 0,
    cleaningFee: 0,
    serviceFee: 25,
    total: 0
};

/**
 * Initialize booking page
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const propertyId = urlParams.get('property');
    const checkIn = urlParams.get('checkIn');
    const checkOut = urlParams.get('checkOut');
    const guests = urlParams.get('guests') || '2';
    
    // Validate property
    if (!propertyId || !PROPERTIES[propertyId]) {
        alert('Invalid property selection');
        window.location.href = 'properties.html';
        return;
    }
    
    // Set booking data
    bookingData.property = propertyId;
    bookingData.checkIn = checkIn;
    bookingData.checkOut = checkOut;
    bookingData.guests = parseInt(guests);
    bookingData.nightlyRate = PROPERTIES[propertyId].nightlyRate;
    bookingData.cleaningFee = PROPERTIES[propertyId].cleaningFee;
    
    // Calculate nights
    if (checkIn && checkOut) {
        const checkInDate = new Date(checkIn);
        const checkOutDate = new Date(checkOut);
        bookingData.nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
    }
    
    // Display property and booking info
    displayPropertyInfo();
    displayBookingSummary();
    
    // Initialize Stripe
    initializeStripe();
    
    // Setup payment method toggle
    setupPaymentMethodToggle();
    
    // Setup form submission
    setupFormSubmission();
    
    // Setup guests dropdown listener
    document.getElementById('guests').addEventListener('change', function(e) {
        bookingData.guests = parseInt(e.target.value);
        displayBookingSummary();
    });
});

/**
 * Display property information
 */
function displayPropertyInfo() {
    const property = PROPERTIES[bookingData.property];
    
    document.getElementById('propertySummary').innerHTML = `
        <h3>${property.name}</h3>
        <p>${property.subtitle}</p>
    `;
    
    document.getElementById('propertyInfo').innerHTML = `
        <strong>${property.name}</strong>
        <p style="margin-top: 0.25rem; margin-bottom: 0;">${property.subtitle}</p>
    `;
}

/**
 * Display booking summary
 */
function displayBookingSummary() {
    // Set dates
    document.getElementById('summaryCheckIn').textContent = bookingData.checkIn || 'Select dates';
    document.getElementById('summaryCheckOut').textContent = bookingData.checkOut || 'Select dates';
    document.getElementById('summaryNights').textContent = bookingData.nights || '-';
    document.getElementById('summaryGuests').textContent = bookingData.guests;
    
    // Set pricing
    document.getElementById('nightlyRate').textContent = `$${bookingData.nightlyRate}`;
    document.getElementById('cleaningFee').textContent = `$${bookingData.cleaningFee}`;
    document.getElementById('serviceFee').textContent = `$${bookingData.serviceFee}`;
    
    if (bookingData.nights > 0) {
        const nightsTotal = bookingData.nightlyRate * bookingData.nights;
        document.getElementById('nightsLabel').textContent = `${bookingData.nights} nights`;
        document.getElementById('nightsTotal').textContent = `$${nightsTotal}`;
        
        const total = nightsTotal + bookingData.cleaningFee + bookingData.serviceFee;
        bookingData.total = total;
        document.getElementById('totalPrice').textContent = `$${total}`;
    } else {
        document.getElementById('nightsLabel').textContent = '0 nights';
        document.getElementById('nightsTotal').textContent = '$0';
        document.getElementById('totalPrice').textContent = '$0';
    }
}

/**
 * Initialize Stripe
 */
function initializeStripe() {
    if (typeof Stripe === 'undefined') {
        console.error('Stripe.js not loaded');
        return;
    }
    
    stripe = Stripe(STRIPE_PUBLIC_KEY);
    const elements = stripe.elements();
    
    cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#3D3530',
                fontFamily: "'Karla', sans-serif",
                '::placeholder': {
                    color: '#6B6459',
                }
            }
        }
    });
    
    cardElement.mount('#card-element');
    
    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });
}

/**
 * Setup payment method toggle
 */
function setupPaymentMethodToggle() {
    const paymentMethods = document.querySelectorAll('input[name="paymentMethod"]');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Hide all payment sections
            document.getElementById('stripePayment').style.display = 'none';
            document.getElementById('bitcoinPayment').style.display = 'none';
            document.getElementById('venmoPayment').style.display = 'none';
            
            // Show selected payment section
            if (this.value === 'stripe') {
                document.getElementById('stripePayment').style.display = 'block';
            } else if (this.value === 'bitcoin') {
                document.getElementById('bitcoinPayment').style.display = 'block';
            } else if (this.value === 'venmo') {
                document.getElementById('venmoPayment').style.display = 'block';
            }
        });
    });
}

/**
 * Setup form submission
 */
function setupFormSubmission() {
    const form = document.getElementById('bookingForm');
    
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validate dates
        if (!bookingData.checkIn || !bookingData.checkOut) {
            alert('Please select check-in and check-out dates');
            window.location.href = `properties.html`;
            return;
        }
        
        const submitBtn = document.getElementById('submitBtn');
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        
        // Get form data
        const formData = new FormData(form);
        const paymentMethod = formData.get('paymentMethod');
       
        
        // Prepare booking data
        const bookingPayload = {
            property: bookingData.property,
            checkIn: bookingData.checkIn,
            checkOut: bookingData.checkOut,
            nights: bookingData.nights,
            guests: bookingData.guests,
            firstName: formData.get('firstName'),
            lastName: formData.get('lastName'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            hasPets: formData.get('hasPets') === 'on',
            specialRequests: formData.get('specialRequests'),
            paymentMethod: paymentMethod,
            amount: bookingData.total
        };

        //console.log(bookingPayload);
        
        try {
            if (paymentMethod === 'stripe') {
                await processStripePayment(bookingPayload);
            } else if (paymentMethod === 'bitcoin') {
                await processBitcoinBooking(bookingPayload);
            } else if (paymentMethod === 'venmo') {
                await processManualBooking(bookingPayload);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Booking error. Please try again or contact us.');
        } finally {
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
    });
}

/**
 * Process Stripe payment
 */
async function processStripePayment(bookingData) {
    try {
        // Create payment intent on server
        const response = await fetch('php/payment-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create_payment_intent',
                ...bookingData
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Payment failed');
        }
        
        // Confirm payment with Stripe
        const { error, paymentIntent } = await stripe.confirmCardPayment(
            result.clientSecret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: `${bookingData.firstName} ${bookingData.lastName}`,
                        email: bookingData.email
                    }
                }
            }
        );
        
        if (error) {
            throw new Error(error.message);
        }
        
        if (paymentIntent.status === 'succeeded') {
            // Send confirmation email
            await sendBookingConfirmation({...bookingData, paymentIntentId: paymentIntent.id});
            
            // Show success and redirect
            alert('Booking confirmed! Check your email for details.');
            window.location.href = 'confirmation.html?booking=' + result.bookingId;
        }
        
    } catch (error) {
        throw error;
    }
}

/**
 * Process Bitcoin booking
 */
async function processBitcoinBooking(bookingData) {
    try {
        const response = await fetch('php/payment-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create_bitcoin_booking',
                ...bookingData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Booking request received! Check your email for Bitcoin payment instructions.');
            window.location.href = 'confirmation.html?booking=' + result.bookingId;
        } else {
            throw new Error(result.error || 'Booking failed');
        }
    } catch (error) {
        throw error;
    }
}

/**
 * Process manual payment booking (Venmo/CashApp)
 */
async function processManualBooking(bookingData) {
    try {
        const response = await fetch('php/payment-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create_manual_booking',
                ...bookingData
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Booking request received! Check your email for payment instructions.');
            window.location.href = 'confirmation.html?booking=' + result.bookingId;
        } else {
            throw new Error(result.error || 'Booking failed');
        }
    } catch (error) {
        throw error;
    }
}

/**
 * Send booking confirmation email
 */
async function sendBookingConfirmation(bookingData) {
    await fetch('php/send-confirmation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(bookingData)
    });
}
