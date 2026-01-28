# SmartStayz Deployment Checklist

## Pre-Deployment (Do This First)

### 1. Gather Required Information
- [ ] Airbnb iCal URL for The Stone property
- [ ] Airbnb iCal URL for The Copper property
- [ ] Airbnb iCal URL for The Cedar property
- [ ] Stripe account created → Save API keys
- [ ] Your email address for receiving bookings
- [ ] Venmo username (if using)
- [ ] CashApp username (if using)
- [ ] High-quality photos of each property

### 2. GoDaddy Account Setup
- [ ] Hosting account active
- [ ] cPanel access confirmed
- [ ] SSL certificate installed (HTTPS enabled)
- [ ] MySQL database available (if using)
- [ ] Domain DNS configured

## Configuration

### 3. Edit php/config.php
- [ ] Line 7-10: Database credentials (or skip if not using database)
- [ ] Line 13-14: Stripe API keys (SECRET and PUBLIC)
- [ ] Line 17-25: BTCPay Server (or leave blank to skip Bitcoin)
- [ ] Line 28-30: Your email addresses
- [ ] Line 33-34: Venmo/CashApp usernames
- [ ] Line 37-43: All three Airbnb iCal URLs
- [ ] Line 46-66: Verify property information is correct

### 4. Edit js/booking.js
- [ ] Line 15: Update Stripe PUBLIC key

### 5. Upload Property Photos
- [ ] Replace placeholder images in HTML files
- [ ] Upload photos to /images folder
- [ ] Optimize images (use TinyPNG.com)
- [ ] Test that all images load correctly

## Deployment

### 6. Upload Files to GoDaddy
- [ ] Upload all files to public_html folder
- [ ] Maintain folder structure exactly
- [ ] Verify all files uploaded successfully

### 7. Set Permissions
- [ ] php/cache folder → 755
- [ ] php/logs folder → 755
- [ ] php/bookings folder → 755
- [ ] .htaccess file → 644

### 8. Install Dependencies
- [ ] SSH into server OR upload Stripe PHP library manually
- [ ] Run: `composer install` OR upload vendor folder
- [ ] Verify /vendor/stripe folder exists

### 9. Database Setup (If Using)
- [ ] Create MySQL database in cPanel
- [ ] Create database user
- [ ] Assign user to database with ALL PRIVILEGES
- [ ] Import docs/database.sql via phpMyAdmin
- [ ] Test database connection

### 10. Configure Cron Job
- [ ] cPanel → Cron Jobs
- [ ] Add: `0 * * * * /usr/bin/php /home/username/public_html/php/calendar-sync.php`
- [ ] Verify cron runs successfully

## Testing

### 11. Test Calendar Sync
- [ ] Visit: yourdomain.com/php/calendar-sync.php?property=stone
- [ ] Should return JSON with success: true
- [ ] Check php/cache folder for calendar JSON files
- [ ] Repeat for copper and cedar properties

### 12. Test Website Navigation
- [ ] Homepage loads correctly
- [ ] Click through to Properties page
- [ ] All three properties display
- [ ] Navigation menu works
- [ ] Contact form visible

### 13. Test Calendar Display
- [ ] Properties page shows calendars for all 3 properties
- [ ] Blocked dates show correctly from Airbnb
- [ ] Can click dates and select range
- [ ] "Book Now" button works

### 14. Test Booking Flow - Stripe
- [ ] Select dates and click "Book Now"
- [ ] Booking page loads with correct details
- [ ] Fill in guest information
- [ ] Select "Credit/Debit Card" payment
- [ ] Enter test card: 4242 4242 4242 4242
- [ ] Complete booking
- [ ] Verify confirmation page shows
- [ ] Check if booking email received

### 15. Test Booking Flow - Bitcoin
- [ ] Start new booking
- [ ] Select "Bitcoin" payment
- [ ] Complete booking
- [ ] Verify email with payment instructions received

### 16. Test Booking Flow - Venmo/CashApp
- [ ] Start new booking
- [ ] Select "Venmo/CashApp" payment
- [ ] Complete booking
- [ ] Verify email with payment details received

### 17. Test Contact Form
- [ ] Fill in contact form
- [ ] Submit
- [ ] Verify email received

### 18. Test Mobile Responsiveness
- [ ] Test on actual phone or use Chrome DevTools
- [ ] Check all pages display correctly
- [ ] Navigation menu works on mobile
- [ ] Forms are easy to fill out
- [ ] Images load properly

### 19. Test Email Deliverability
- [ ] Send test booking
- [ ] Check if emails arrive promptly
- [ ] Check spam folder if not in inbox
- [ ] Verify all links in emails work

## Go Live

### 20. Switch to Live Mode
- [ ] Replace ALL Stripe TEST keys with LIVE keys in:
  - [ ] php/config.php (both lines 13-14)
  - [ ] js/booking.js (line 15)
- [ ] Remove any test bookings from database/files
- [ ] Clear php/cache folder

### 21. Final Checks
- [ ] Verify HTTPS (green padlock in browser)
- [ ] Test real credit card (small amount)
- [ ] Confirm real booking email received
- [ ] All property photos displaying
- [ ] No console errors in browser (F12 → Console)
- [ ] No PHP errors in logs

### 22. Set Up Monitoring
- [ ] Add calendar reminder to check bookings daily
- [ ] Set reminder to manually update Airbnb when SmartStayz bookings come in
- [ ] Bookmark admin email to check for notifications
- [ ] Save cPanel login for quick access

## Post-Launch

### 23. Ongoing Maintenance
- [ ] Check booking emails daily
- [ ] Block SmartStayz bookings on Airbnb immediately
- [ ] Monitor php/logs for errors weekly
- [ ] Backup booking data monthly
- [ ] Update photos seasonally

### 24. Performance Monitoring
- [ ] Track conversion rate (visits → bookings)
- [ ] Monitor which payment methods are preferred
- [ ] Review calendar sync is working (check logs)
- [ ] Test site speed (use GTmetrix or PageSpeed Insights)

## Troubleshooting

If something doesn't work:
1. ✅ Check php/logs/php-errors.log
2. ✅ Verify ALL config values are correct
3. ✅ Check browser console for JavaScript errors (F12)
4. ✅ Test calendar sync URL directly
5. ✅ Verify file permissions
6. ✅ Check HTTPS is enabled
7. ✅ Review README.md for detailed solutions

## Emergency Contacts

**Host Support:**
- GoDaddy: 1-480-505-8877 or help.godaddy.com

**Payment Issues:**
- Stripe Support: stripe.com/support

**Development Issues:**
- Refer to README.md
- Check error logs first

---

**Print this checklist and check off items as you complete them!**

Estimated total time: 3-4 hours for complete deployment and testing.
