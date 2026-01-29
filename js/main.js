/**
 * SmartStayz - Main JavaScript
 * Handles navigation, forms, and general UI interactions
 */

// Mobile Navigation Toggle
document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (navToggle) {
        navToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            
            // Animate toggle button
            this.classList.toggle('active');
        });
    }
    
    // Close mobile menu when clicking menu items
    const navLinks = document.querySelectorAll('.nav-menu a');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            navMenu.classList.remove('active');
            if (navToggle) {
                navToggle.classList.remove('active');
            }
        });
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // Contact Form Handler
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }
    
    // Set minimum date for date inputs to today
    setMinDateForInputs();
});

/**
 * Handle contact form submission
 */
function handleContactForm(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Get or create message div
    let messageDiv = document.getElementById('contactFormMessage');
    if (!messageDiv) {
        messageDiv = document.createElement('div');
        messageDiv.id = 'contactFormMessage';
        const submitBtn = e.target.querySelector('button[type="submit"]');
        e.target.insertBefore(messageDiv, submitBtn);
    }
    
    // Clear previous state
    messageDiv.className = '';
    messageDiv.style.display = 'none';
    messageDiv.innerHTML = '';
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;
    
    // Send to backend
    fetch('php/contact.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        messageDiv.style.display = 'block';
        
        if (result.success) {
            messageDiv.className = 'success';
            messageDiv.innerHTML = '<p>✓ Message sent successfully! We\'ll respond within an hour.</p>';
            e.target.reset();
        } else {
            messageDiv.className = 'error';
            messageDiv.innerHTML = '<p>Error: ' + (result.error || 'Failed to send message. Please try again or email us directly.') + '</p>';
        }
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    })
    .catch(error => {
        console.error('Error:', error);
        messageDiv.style.display = 'block';
        messageDiv.className = 'error';
        messageDiv.innerHTML = '<p>Error sending message. Please try again or email us directly.</p>';
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

/**
 * Set minimum date for date inputs to today
 */
function setMinDateForInputs() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        input.setAttribute('min', today);
    });
}

/**
 * Navigate to booking page for specific property
 */
function bookProperty(propertyId) {
    const checkIn = document.getElementById('checkIn')?.value;
    const checkOut = document.getElementById('checkOut')?.value;
    const guests = document.getElementById('guests')?.value || '2';
    
    // Build URL with parameters
    let url = `booking.html?property=${propertyId}&guests=${guests}`;
    
    if (checkIn && checkOut) {
        // Validate dates
        const checkInDate = new Date(checkIn);
        const checkOutDate = new Date(checkOut);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Check if check-in is in past
        if (checkInDate < today) {
            alert('Check-in date cannot be in the past');
            return;
        }
        
        // Check if checkout is after checkin
        if (checkOutDate <= checkInDate) {
            alert('Check-out date must be after check-in date');
            return;
        }
        
        // Check if property is available
        if (window.calendarManager && window.calendarManager.isDateRangeAvailable) {
            const isAvailable = window.calendarManager.isDateRangeAvailable(propertyId, checkIn, checkOut);
            if (!isAvailable) {
                alert('This property is not available for the selected dates');
                return;
            }
        }
        
        url += `&checkIn=${checkIn}&checkOut=${checkOut}`;
    }
    
    window.location.href = url;
}

/**
 * Filter properties by availability for selected dates
 */
/**
 * Filter properties by availability for selected dates
 */
function filterProperties() {
    console.log('filterProperties called');
    const checkIn = document.getElementById('checkIn')?.value;
    const checkOut = document.getElementById('checkOut')?.value;
    const filterError = document.getElementById('filterError');
    const noPropertiesMessage = document.getElementById('noPropertiesMessage');
    
    console.log('Check-in:', checkIn, 'Check-out:', checkOut);
    
    // Clear previous error
    if (filterError) {
        filterError.innerHTML = '';
        filterError.style.display = 'none';
    }
    
    // Get all property cards
    const propertyCards = document.querySelectorAll('.property-detail-card');
    console.log('Found', propertyCards.length, 'property cards');
    
    // If no dates selected, hide all properties
    if (!checkIn || !checkOut) {
        console.log('No dates selected, hiding all properties');
        const propertyIds = ['stone', 'copper', 'cedar'];
        propertyIds.forEach(id => {
            const card = document.getElementById(`property-${id}`);
            if (card) card.style.display = 'none';
        });
        if (noPropertiesMessage) {
            noPropertiesMessage.style.display = 'block';
        }
        return;
    }
    
    // Validate dates
    const checkInDate = new Date(checkIn);
    const checkOutDate = new Date(checkOut);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    // Check if dates are in the past
    if (checkInDate < today) {
        if (filterError) {
            filterError.innerHTML = '<p>✗ Check-in date cannot be in the past</p>';
            filterError.style.display = 'block';
        }
        const propertyIds = ['stone', 'copper', 'cedar'];
        propertyIds.forEach(id => {
            const card = document.getElementById(`property-${id}`);
            if (card) card.style.display = 'none';
        });
        if (noPropertiesMessage) {
            noPropertiesMessage.style.display = 'block';
        }
        return;
    }
    
    // Check if checkout is after checkin (minimum 1 night)
    if (checkOutDate <= checkInDate) {
        if (filterError) {
            filterError.innerHTML = '<p>✗ Check-out date must be at least 1 night after check-in</p>';
            filterError.style.display = 'block';
        }
        const propertyIds = ['stone', 'copper', 'cedar'];
        propertyIds.forEach(id => {
            const card = document.getElementById(`property-${id}`);
            if (card) card.style.display = 'none';
        });
        if (noPropertiesMessage) {
            noPropertiesMessage.style.display = 'block';
        }
        return;
    }
    
    // Check availability for each property using calendarManager
    let availableCount = 0;
    console.log('calendarManager available:', !!window.calendarManager);
    console.log('isDateRangeAvailable method exists:', !!(window.calendarManager && window.calendarManager.isDateRangeAvailable));
    
    // Show calendar data status
    if (window.calendarManager) {
        console.log('Blocked dates loaded:', {
            stone: window.calendarManager.blockedDates.stone.length,
            copper: window.calendarManager.blockedDates.copper.length,
            cedar: window.calendarManager.blockedDates.cedar.length
        });
    }
    
    propertyCards.forEach(card => {
        const propertyId = card.getAttribute('data-property-id');
        console.log('Checking property:', propertyId);
        
        // Check if property is available for the date range
        if (window.calendarManager && window.calendarManager.isDateRangeAvailable) {
            try {
                const isAvailable = window.calendarManager.isDateRangeAvailable(propertyId, checkIn, checkOut);
                console.log('  Property', propertyId, 'available:', isAvailable);
                
                if (isAvailable) {
                    document.getElementById(`property-${propertyId}`).style.display = 'flex';
                    availableCount++;
                } else {
                    document.getElementById(`property-${propertyId}`).style.display = 'none';
                }
            } catch (error) {
                console.error('Error checking availability for', propertyId, ':', error);
                // If there's an error, show the property anyway
                document.getElementById(`property-${propertyId}`).style.display = 'flex';
                availableCount++;
            }
        } else {
            // If calendarManager not available, show all properties
            console.log('  calendarManager not available, showing all');
            document.getElementById(`property-${propertyId}`).style.display = 'flex';
            availableCount++;
        }
    });
    
    // Show/hide no properties message
    console.log('Available count:', availableCount);
    if (availableCount === 0) {
        console.log('No properties available, showing message');
        if (noPropertiesMessage) {
            noPropertiesMessage.innerHTML = '<p>No properties available for the selected dates. Please try different dates.</p>';
            noPropertiesMessage.style.display = 'block';
        }
    } else {
        console.log('Available properties found, hiding message');
        if (noPropertiesMessage) {
            noPropertiesMessage.style.display = 'none';
        }
    }
    
    // Scroll to first available property
    const propertyIds = ['stone', 'copper', 'cedar'];
    let firstAvailable = null;
    for (let id of propertyIds) {
        const card = document.getElementById(`property-${id}`);
        if (card && card.style.display !== 'none') {
            firstAvailable = card;
            break;
        }
    }
    
    console.log('First available property:', firstAvailable);
    if (firstAvailable) {
        setTimeout(() => {
            console.log('Scrolling to first available property');
            firstAvailable.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    }
    
    // Highlight dates in calendars
    if (window.highlightDateRange) {
        console.log('Calling highlightDateRange');
        window.highlightDateRange(checkIn, checkOut);
    }
}

/**
 * Add real-time filtering on date input change
 */
document.addEventListener('DOMContentLoaded', function() {
    const checkInInput = document.getElementById('checkIn');
    const checkOutInput = document.getElementById('checkOut');
    const searchBtn = document.getElementById('searchBtn');
    
    // Add event listener for search button
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            console.log('Search button clicked - calling filterProperties');
            filterProperties();
        });
    }
    
    if (checkInInput) {
        checkInInput.addEventListener('change', filterProperties);
    }
    if (checkOutInput) {
        checkOutInput.addEventListener('change', filterProperties);
    }
    
    // Hide all properties by default on page load - show only when searched
    const propertyIds = ['stone', 'copper', 'cedar'];
    propertyIds.forEach(id => {
        const card = document.getElementById(`property-${id}`);
        if (card) card.style.display = 'none';
    });
    
    // Show initial message
    const noPropertiesMessage = document.getElementById('noPropertiesMessage');
    if (noPropertiesMessage) {
        noPropertiesMessage.style.display = 'block';
    }
});

/**
 * Animate elements on scroll
 */
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', function() {
    const animatedElements = document.querySelectorAll('.property-card, .amenity-item');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

/**
 * Property image gallery functionality
 */
document.querySelectorAll('.thumb-image').forEach(thumb => {
    thumb.addEventListener('click', function() {
        const mainImage = this.closest('.property-images').querySelector('.main-image .image-placeholder');
        const thumbPlaceholder = this.querySelector('.image-placeholder');
        
        // In production, you would swap actual image sources here
        // For now, just add a visual effect
        mainImage.style.transform = 'scale(0.95)';
        setTimeout(() => {
            mainImage.style.transform = 'scale(1)';
        }, 200);
    });
});
