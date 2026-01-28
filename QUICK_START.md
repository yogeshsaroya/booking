# SmartStayz - Quick Start Guide

## For Your Developer

Hi! This package contains a complete booking website for SmartStayz. Here's what you need to know:

### What This Package Includes
✅ Complete HTML/CSS/JavaScript website
✅ PHP backend for calendar sync and payments
✅ Database schema (optional)
✅ Integration with Airbnb calendars (one-way sync)
✅ Payment processing (Stripe, Bitcoin, Venmo/CashApp)
✅ Responsive design for all devices
✅ Complete documentation

### 5-Minute Setup Overview

1. **Upload Files**
   - Extract ZIP to GoDaddy hosting
   - Set folder permissions: `php/cache`, `php/logs`, `php/bookings` = 755

2. **Configure**
   - Edit `php/config.php` with:
     - Airbnb iCal URLs
     - Stripe API keys
     - Email settings
     - Venmo/CashApp info
   - Edit `js/booking.js` line 15 with Stripe public key

3. **Set Up Database** (optional but recommended)
   - Create MySQL database
   - Run `docs/database.sql`
   - Update config with credentials

4. **Install Dependencies**
   ```bash
   composer require stripe/stripe-php
   ```
   Or upload Stripe PHP library to `/vendor`

5. **Test**
   - Visit site and test booking with test card
   - Verify calendar sync works
   - Check email notifications

### Critical Configuration Files

| File | What to Edit |
|------|-------------|
| `php/config.php` | ALL settings (Stripe, Airbnb URLs, emails) |
| `js/booking.js` | Line 15: Stripe public key |

### Required External Services

| Service | Purpose | Cost | Sign-up |
|---------|---------|------|---------|
| Stripe | Credit card payments | 2.9% + $0.29 | stripe.com |
| BTCPay (optional) | Bitcoin payments | $0-10/month | btcpayserver.org |
| Airbnb iCal | Calendar sync | $0 | Already have |

### Important Notes

⚠️ **ONE-WAY SYNC:** Site pulls from Airbnb. When someone books on SmartStayz, client must manually block dates on Airbnb.

⚠️ **HTTPS Required:** Must have SSL certificate (GoDaddy provides free ones)

⚠️ **Email Configuration:** Verify PHP `mail()` works or set up SMTP

### File Structure
```
/ (root)
├── index.html          # Homepage
├── properties.html     # Property listings
├── booking.html        # Booking page
├── css/               # Stylesheets
├── js/                # JavaScript
├── php/               # Backend (edit config.php!)
├── images/            # Property photos
└── docs/              # Documentation
```

### Testing Checklist

- [ ] Site loads correctly
- [ ] Navigation works
- [ ] Calendars display Airbnb blocked dates
- [ ] Test booking with Stripe test card (4242 4242 4242 4242)
- [ ] Confirmation email received
- [ ] Contact form works
- [ ] Mobile responsive
- [ ] All property photos displaying

### Common Issues & Solutions

**Calendar not loading:**
- Check iCal URLs in config.php
- Verify `php/cache` has write permissions
- Test: `yoursite.com/php/calendar-sync.php?property=stone`

**Payments failing:**
- Verify Stripe keys in both config.php AND booking.js
- Check PHP error log: `php/logs/php-errors.log`
- Ensure SSL/HTTPS is enabled

**Emails not sending:**
- Some hosts block mail() - may need SMTP plugin
- Check GoDaddy email configuration
- Verify FROM_EMAIL is valid

### Going Live Steps

1. Get Airbnb iCal URLs for all 3 properties
2. Create Stripe account (use LIVE keys, not test)
3. Configure all values in config.php
4. Upload property photos
5. Run test booking
6. Set up cron job for hourly calendar sync
7. Enable HTTPS

### Maintenance

**Automated:**
- Calendar syncs every hour (via cron)
- Emails sent automatically

**Manual:**
- Block SmartStayz bookings on Airbnb immediately
- Check booking emails daily
- Monthly backup of booking data

### Support Resources

- Full documentation: `README.md`
- Database setup: `docs/database.sql`
- Image recommendations: `docs/IMAGES.md`
- GoDaddy support: GoDaddy cPanel docs

### Estimated Setup Time

- Basic setup: 30-60 minutes
- With database: +15 minutes
- Image optimization: +30 minutes
- Testing: +30 minutes

**Total: 2-3 hours**

### Contact for Questions

If you run into issues:
1. Check README.md thoroughly
2. Review PHP error logs
3. Test each component individually
4. Check GoDaddy documentation

---

**This is a complete, production-ready solution.** Everything needed is included in this package.
