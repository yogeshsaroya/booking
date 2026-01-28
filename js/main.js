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
        
        if (checkOutDate <= checkInDate) {
            alert('Check-out date must be after check-in date');
            return;
        }
        
        url += `&checkIn=${checkIn}&checkOut=${checkOut}`;
    }
    
    window.location.href = url;
}

/**
 * Check availability for selected dates
 */
document.getElementById('searchBtn')?.addEventListener('click', function() {
    const checkIn = document.getElementById('checkIn')?.value;
    const checkOut = document.getElementById('checkOut')?.value;
    
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
    
    // Scroll to first property
    const firstProperty = document.querySelector('.property-detail-card');
    if (firstProperty) {
        firstProperty.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    
    // Highlight dates in calendars
    if (window.highlightDateRange) {
        window.highlightDateRange(checkIn, checkOut);
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
