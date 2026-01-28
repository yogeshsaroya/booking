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
let STRIPE_PUBLIC_KEY = null;

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
 * Display error message below the form
 */
function showErrorMessage(message) {
    const errorDiv = document.getElementById('bookingErrorMessage');
    const errorText = document.getElementById('bookingErrorText');
    errorText.textContent = message;
    errorDiv.style.display = 'block';
    // Scroll to error message
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * Hide error message
 */
function hideErrorMessage() {
    const errorDiv = document.getElementById('bookingErrorMessage');
    errorDiv.style.display = 'none';
}

/**
 * Hide booking form when invalid parameters
 */
function hideBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    const bookingSidebar = document.querySelector('.booking-sidebar');
    if (bookingForm) bookingForm.style.display = 'none';
    if (bookingSidebar) bookingSidebar.style.display = 'none';
}

/**
 * Initialize booking page
 */
document.addEventListener('DOMContentLoaded', async function() {
    // Get URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const propertyId = urlParams.get('property');
    const checkIn = urlParams.get('checkIn');
    const checkOut = urlParams.get('checkOut');
    const guests = urlParams.get('guests');
    
    // Validate all required parameters
    if (!propertyId || !checkIn || !checkOut || !guests) {
        hideBookingForm();
        showErrorMessage('Missing booking information. Please select your dates and property to book.');
        setTimeout(() => {
            window.location.href = 'properties.html';
        }, 3000);
        return;
    }
    
    // Validate property exists
    if (!PROPERTIES[propertyId]) {
        hideBookingForm();
        showErrorMessage('Invalid property selection. Redirecting to properties...');
        setTimeout(() => {
            window.location.href = 'properties.html';
        }, 2000);
        return;
    }
    
    // Validate dates are valid
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    
    if (isNaN(checkInDate.getTime()) || isNaN(checkOutDate.getTime())) {
        hideBookingForm();
        showErrorMessage('Invalid dates provided. Please select valid check-in and check-out dates.');
        setTimeout(() => {
            window.location.href = 'properties.html';
        }, 3000);
        return;
    }
    
    // Validate check-out is after check-in
    if (checkOutDate <= checkInDate) {
        hideBookingForm();
        showErrorMessage('Check-out date must be after check-in date. Please select valid dates.');
        setTimeout(() => {
            window.location.href = 'properties.html';
        }, 3000);
        return;
    }
    
    // Validate dates are not in the past
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (checkInDate < today) {
        hideBookingForm();
        showErrorMessage('Cannot book dates in the past. Please select future dates.');
        setTimeout(() => {
            window.location.href = 'properties.html';
        }, 3000);
        return;
    }
    
    // Validate guests is a valid number
    const guestsNum = parseInt(guests);
    if (isNaN(guestsNum) || guestsNum < 1 || guestsNum > 6) {
        hideBookingForm();
        showErrorMessage('Invalid number of guests. Please select between 1 and 6 guests.');
        setTimeout(() => {
            window.location.href = 'properties.html';
        }, 3000);
        return;
    }
    
    // Set booking data
    bookingData.property = propertyId;
    bookingData.checkIn = checkIn;
    bookingData.checkOut = checkOut;
    bookingData.guests = guestsNum;
    bookingData.nightlyRate = PROPERTIES[propertyId].nightlyRate;
    bookingData.cleaningFee = PROPERTIES[propertyId].cleaningFee;
    
    // Calculate nights
    bookingData.nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
    
    // Display property and booking info
    displayPropertyInfo();
    displayBookingSummary();
    
    // Load Stripe public key and initialize
    await loadStripeKey();
    
    // Load and filter payment methods based on configuration
    await loadPaymentMethods();
    
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
 * Load Stripe public key from server
 */
async function loadStripeKey() {
    try {
        const response = await fetch('php/get-stripe-key.php');
        const data = await response.json();
        STRIPE_PUBLIC_KEY = data.publicKey;
        
        // Initialize Stripe after key is loaded
        initializeStripe();
    } catch (error) {
        console.error('Failed to load Stripe key:', error);
        showErrorMessage('Unable to initialize payment system. Please refresh the page.');
    }
}

/**
 * Load available payment methods from server
 */
async function loadPaymentMethods() {
    try {
        const response = await fetch('php/get-payment-methods.php');
        const data = await response.json();
        
        if (data.success && data.paymentMethods) {
            // Hide payment options that are not configured
            if (!data.paymentMethods.stripe) {
                const stripeOption = document.querySelector('input[value="stripe"]')?.closest('.payment-option');
                if (stripeOption) stripeOption.style.display = 'none';
            }
            
            if (!data.paymentMethods.bitcoin) {
                const bitcoinOption = document.querySelector('input[value="bitcoin"]')?.closest('.payment-option');
                if (bitcoinOption) bitcoinOption.style.display = 'none';
            }
            
            if (!data.paymentMethods.venmo) {
                const venmoOption = document.querySelector('input[value="venmo"]')?.closest('.payment-option');
                if (venmoOption) venmoOption.style.display = 'none';
            }
            
            // Ensure at least one payment method is available and selected
            const availableOptions = document.querySelectorAll('.payment-option:not([style*="display: none"]) input[type="radio"]');
            if (availableOptions.length > 0) {
                availableOptions[0].checked = true;
                // Trigger change event to show correct payment section
                availableOptions[0].dispatchEvent(new Event('change'));
            } else {
                hideBookingForm();
                showErrorMessage('No payment methods are currently available. Please contact us to complete your booking.');
            }
        }
    } catch (error) {
        console.error('Failed to load payment methods:', error);
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
        hidePostalCode: false,
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
            showErrorMessage('Please select check-in and check-out dates before completing your booking.');
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
            showErrorMessage('Booking error: ' + (error.message || 'Please try again or contact us.'));
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
            // Update booking status to confirmed
            await fetch('php/payment-handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'confirm_payment',
                    bookingId: result.bookingId,
                    paymentIntentId: paymentIntent.id
                })
            });
            
            // Send confirmation email
            await sendBookingConfirmation({...bookingData, paymentIntentId: paymentIntent.id});
            
            // Disable submit button to prevent duplicate submissions
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Redirecting...';
            
            // Show success message with custom styling
            const errorDiv = document.getElementById('bookingErrorMessage');
            errorDiv.style.background = '#e8f4e8';
            errorDiv.style.borderColor = '#4caf50';
            const errorText = document.getElementById('bookingErrorText');
            errorText.textContent = '✓ Booking confirmed! Check your email for details.';
            errorDiv.style.display = 'block';
            
            // Redirect after 2 seconds
            setTimeout(() => {
                // Store booking data for confirmation page
                sessionStorage.setItem('bookingData', JSON.stringify(bookingData));
                window.location.href = 'confirmation.html?booking=' + result.bookingId;
            }, 2000);
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
            // Disable submit button to prevent duplicate submissions
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Redirecting...';
            
            // Show success message with custom styling
            const errorDiv = document.getElementById('bookingErrorMessage');
            errorDiv.style.background = '#e8f4e8';
            errorDiv.style.borderColor = '#4caf50';
            const errorText = document.getElementById('bookingErrorText');
            errorText.textContent = '✓ Booking request received! Check your email for Bitcoin payment instructions.';
            errorDiv.style.display = 'block';
            
            // Redirect after 2 seconds
            setTimeout(() => {
                // Store booking data for confirmation page
                sessionStorage.setItem('bookingData', JSON.stringify(bookingData));
                window.location.href = 'confirmation.html?booking=' + result.bookingId;
            }, 2000);
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
            // Disable submit button to prevent duplicate submissions
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.textContent = 'Redirecting...';
            
            // Show success message with custom styling
            const errorDiv = document.getElementById('bookingErrorMessage');
            errorDiv.style.background = '#e8f4e8';
            errorDiv.style.borderColor = '#4caf50';
            const errorText = document.getElementById('bookingErrorText');
            errorText.textContent = '✓ Booking request received! Check your email for payment instructions.';
            errorDiv.style.display = 'block';
            
            // Redirect after 2 seconds
            setTimeout(() => {
                // Store booking data for confirmation page
                sessionStorage.setItem('bookingData', JSON.stringify(bookingData));
                window.location.href = 'confirmation.html?booking=' + result.bookingId;
            }, 2000);
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
