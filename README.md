# SmartStayz Booking Website - Implementation Guide

## Overview
This is a complete, production-ready booking website for your three Hyde Park short-term rental properties. The site features:
- **One-way calendar sync** from Airbnb (pulls their bookings, updates every hour)
- **Multiple payment options**: Stripe (credit cards), Bitcoin (BTCPay Server), Venmo/CashApp
- **Responsive design** optimized for mobile, tablet, and desktop
- **Boutique aesthetic** matching your artisanal properties

## 🚨 Important: Manual Calendar Management
**This system uses ONE-WAY sync from Airbnb.** When someone books on SmartStayz.com:
1. You'll receive an email notification
2. **YOU MUST manually block those dates on Airbnb** to prevent double bookings
3. Set a calendar reminder or use the Airbnb app immediately after receiving booking emails

## File Structure
```
smartstayz-site/
├── index.html              # Homepage
├── properties.html         # Property listings with calendars
├── booking.html           # Booking/payment page
├── css/
│   ├── styles.css         # Main stylesheet
│   ├── calendar.css       # Calendar styling
│   └── booking.css        # Booking page styling
├── js/
│   ├── main.js           # General functionality
│   ├── calendar.js       # Calendar sync
│   └── booking.js        # Booking/payment processing
├── php/
│   ├── config.php        # Configuration (EDIT THIS!)
│   ├── calendar-sync.php # Airbnb iCal sync
│   ├── payment-handler.php # Payment processing
│   └── contact.php       # Contact form handler
├── images/               # Your property photos go here
└── docs/
    ├── database.sql      # Database schema
    └── IMAGES.md         # Recommended stock photos
```

## Setup Instructions

### Step 1: Get Your Airbnb iCal URLs
For each property:
1. Log into your Airbnb host account
2. Go to **Calendar** → **Availability Settings**
3. Click **"Export Calendar"**
4. Copy the iCal link (looks like: `https://www.airbnb.com/calendar/ical/42680597.ics?s=abc123...`)
5. Save these URLs - you'll need them in Step 4

### Step 2: Set Up Stripe (for Credit Cards)
1. Go to [stripe.com](https://stripe.com) and create an account
2. Get your API keys:
   - Go to **Developers** → **API Keys**
   - Copy your **Publishable key** (starts with `pk_test_...`)
   - Copy your **Secret key** (starts with `sk_test_...`)
3. You'll enter these in `php/config.php` and `js/booking.js`

**Note:** Stripe charges 2.9% + $0.29 per transaction. This is unavoidable for credit card processing.

### Step 3: Set Up Bitcoin (Optional - Zero Fees!)
For zero-fee Bitcoin payments, set up BTCPay Server:

**Option A: Self-Hosted (Free)**
1. Follow BTCPay Server setup: https://docs.btcpayserver.org/
2. Get your Store ID and API key
3. Enter in `php/config.php`

**Option B: Third-Party Host (~$10/month)**
Use a hosted BTCPay provider like btcpay.com or lunanode.com

**Option C: Skip Bitcoin**
Just leave the BTCPay config empty - Bitcoin option will show "Manual payment" instead

### Step 4: Configure the Website

Edit `php/config.php`:

```php
// 1. Database (optional - site works without it)
define('DB_HOST', 'localhost');
define('DB_NAME', 'smartstayz_bookings');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');

// 2. Stripe Keys (from Step 2)
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_KEY_HERE');
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_KEY_HERE');

// 3. Your Email
define('ADMIN_EMAIL', 'your-email@gmail.com');
define('FROM_EMAIL', 'bookings@smartstayz.com');

// 4. Venmo/CashApp
define('VENMO_USERNAME', '@YourVenmo');
define('CASHAPP_USERNAME', '$YourCashApp');

// 5. Airbnb iCal URLs (from Step 1)
define('PROPERTY_ICAL_URLS', [
    'stone' => 'YOUR_ICAL_URL_HERE',
    'copper' => 'YOUR_ICAL_URL_HERE',
    'cedar' => 'YOUR_ICAL_URL_HERE'
]);
```

Also edit `js/booking.js` line 15:
```javascript
const STRIPE_PUBLIC_KEY = 'pk_test_YOUR_KEY_HERE';
```

### Step 5: Install on GoDaddy

**Via File Manager:**
1. Log into GoDaddy cPanel
2. Go to **File Manager**
3. Navigate to `public_html` (or `www`)
4. Upload all files maintaining the folder structure
5. Set permissions:
   - `php/cache` → 755
   - `php/logs` → 755
   - `php/bookings` → 755

**Via FTP:**
1. Use FileZilla or your preferred FTP client
2. Connect to your GoDaddy server
3. Upload all files to `public_html`
4. Create folders: `php/cache`, `php/logs`, `php/bookings`
5. Set folder permissions to 755

### Step 6: Set Up Database (Optional but Recommended)

In GoDaddy cPanel:
1. Go to **MySQL Databases**
2. Create new database: `smartstayz_bookings`
3. Create new user with password
4. Assign user to database with ALL PRIVILEGES
5. In **phpMyAdmin**, run the SQL from `docs/database.sql`

If you skip this step, bookings will only save to JSON files (which still works fine).

### Step 7: Install Stripe PHP Library

Via SSH (if available):
```bash
cd /path/to/your/site
composer require stripe/stripe-php
```

Via cPanel:
1. Upload the Stripe PHP library to `/vendor` folder
2. Download from: https://github.com/stripe/stripe-php/releases

### Step 8: Add Your Property Photos

Replace the placeholder images:
1. Take high-quality photos of each property
2. Resize to:
   - Main images: 1200x800px
   - Thumbnails: 400x300px
3. Save as JPG (optimize for web)
4. Upload to `/images` folder
5. Update HTML files with actual image paths

Alternatively, use the stock photos listed in `docs/IMAGES.md`

### Step 9: Test Everything

1. Visit your site: `https://smartstayz.com`
2. Click through to Properties page
3. Check that Airbnb calendars are loading
4. Try a test booking with Stripe test card:
   - Card: `4242 4242 4242 4242`
   - Expiry: Any future date
   - CVC: Any 3 digits
5. Verify you receive the booking email

### Step 10: Set Up Automatic Calendar Sync

**Option A: Cron Job (Recommended)**
In GoDaddy cPanel → Cron Jobs:
```
0 * * * * /usr/bin/php /home/username/public_html/php/calendar-sync.php
```
This syncs every hour.

**Option B: Manual Sync**
Just visit: `https://smartstayz.com/php/calendar-sync.php` to update calendars

## Daily Workflow

### When Someone Books on SmartStayz.com:
1. ✅ You receive email with booking details
2. ✅ Payment is processed automatically (Stripe) or instructions sent (Bitcoin/Venmo)
3. ⚠️ **YOU MUST: Immediately block those dates on Airbnb manually**
4. ✅ Guest receives confirmation email
5. ✅ Booking saved to database/files

### When Someone Books on Airbnb:
1. ✅ Calendar syncs automatically (within 1 hour)
2. ✅ Those dates become unavailable on SmartStayz.com
3. ✅ No action needed from you

## Payment Processing Fees

- **Credit Cards (Stripe):** 2.9% + $0.29 per transaction
- **Bitcoin (BTCPay):** $0 (zero fees!)
- **Venmo/CashApp:** $0 (manual, peer-to-peer)

Example: $1,000 booking
- Stripe: You receive $970.71
- Bitcoin: You receive $1,000.00
- Venmo: You receive $1,000.00

## Customization

### Change Colors
Edit `css/styles.css` CSS variables:
```css
:root {
    --warm-cedar: #8B6F47;      /* Main accent color */
    --copper-accent: #A67352;   /* Buttons, links */
    --stone-gray: #6B6459;      /* Text color */
}
```

### Change Pricing
Edit `php/config.php`:
```php
'nightly_rate' => 150,  // Change per property
'cleaning_fee' => 75
```

Also update `js/booking.js` with same rates.

### Add/Remove Properties
1. Add new entry in `php/config.php` PROPERTIES array
2. Add section in `properties.html`
3. Get Airbnb iCal URL
4. Add to PROPERTY_ICAL_URLS array

## Troubleshooting

### Calendar Not Loading
- Check `php/config.php` has correct iCal URLs
- Verify `php/cache` folder has write permissions (755)
- Test URL directly: `smartstayz.com/php/calendar-sync.php?property=stone`

### Payments Not Working
- Verify Stripe keys are correct in BOTH `php/config.php` and `js/booking.js`
- Check PHP error log: `php/logs/php-errors.log`
- Test with Stripe test card: 4242 4242 4242 4242

### Emails Not Sending
- Some hosts block mail() function
- Use SMTP plugin or service like SendGrid
- Check GoDaddy email settings

### Double Bookings
- **ALWAYS manually block SmartStayz bookings on Airbnb immediately**
- Set up calendar reminders
- Consider keeping a buffer day between bookings

## Security Notes

1. **Never commit `config.php` with real keys to public repos**
2. Set restrictive file permissions on `/php` folder
3. Keep Stripe keys secure
4. Use HTTPS (SSL certificate - GoDaddy provides free ones)
5. Regularly update PHP and dependencies

## Support & Maintenance

### Regular Tasks:
- Weekly: Check booking emails
- Weekly: Verify calendar sync is working
- Monthly: Review transaction fees
- Monthly: Back up booking data

### Where Things Are Stored:
- Bookings: `php/bookings/*.json` + database (if configured)
- Calendar cache: `php/cache/calendar_*.json`
- Logs: `php/logs/app.log`

## Going Live Checklist

- [ ] All Airbnb iCal URLs configured
- [ ] Stripe keys updated (use LIVE keys, not test)
- [ ] Your email address in config
- [ ] Property photos uploaded
- [ ] Test booking completed successfully
- [ ] SSL certificate installed (HTTPS)
- [ ] Cron job set up for hourly sync
- [ ] Database created and configured
- [ ] Contact form tested
- [ ] Mobile responsiveness checked
- [ ] Set up calendar reminder to check bookings daily

## Getting Help

If you need assistance with setup:
1. Check GoDaddy's support docs
2. Verify all config values are correct
3. Check PHP error logs
4. Test each component individually

## Cost Summary

**One-Time:**
- Domain + Hosting: ~$10/month (GoDaddy)
- SSL Certificate: Free (from GoDaddy)
- Development: Complete (this package)

**Ongoing:**
- Stripe fees: 2.9% + $0.29 per credit card transaction
- BTCPay hosting (optional): $0-10/month
- Everything else: $0

**vs. Using a Channel Manager:**
- Typical channel manager: $50-200/month = $600-2,400/year
- This solution: $120/year hosting + transaction fees only

You're saving $500-2,000+ per year!

---

**Questions?** Review this README carefully - everything you need is here!
