# Contact Form - Testing & Verification Checklist

## ✅ Setup Complete

### 1. Dependencies Installed
- [x] PHPMailer 6.12.0 installed via Composer
- [x] Stripe PHP SDK installed
- [x] Vendor autoload working

### 2. Configuration Files
- [x] `.env` file with SMTP settings configured
  - SMTP_HOST: mail.smartstayz.com
  - SMTP_PORT: 465
  - SMTP_SECURE: ssl ✓ (fixed from tls)
  - SMTP credentials set
- [x] `config.php` loads environment variables
- [x] Old `sendEmail()` function removed

### 3. Email Handler
- [x] `MailHandler.php` created with PHPMailer support
- [x] Reply-To functionality added
- [x] Fallback to PHP mail() if PHPMailer unavailable
- [x] Error logging implemented

### 4. Contact Form Handler
- [x] `php/contact.php` validates and sanitizes input
- [x] Sends email to admin with Reply-To header
- [x] Sends confirmation email to sender
- [x] Returns JSON response
- [x] Error handling with proper HTTP status codes

### 5. Frontend (HTML/CSS/JS)
- [x] Contact form in `index.html`
- [x] Form has name, email, message fields
- [x] `#contactFormMessage` div added for inline messages
- [x] CSS styling for success/error messages
- [x] JavaScript handles form submission via fetch()
- [x] Loading state on submit button
- [x] Form clears on success
- [x] Smooth scroll to message

## 🧪 Testing

### Automated Tests
- [x] PHP syntax validation passed (MailHandler.php, contact.php)
- [x] SMTP connection test successful (`php/test-email.php`)
- [x] Test email sent to admin successfully

### Manual Testing Required
Use the test page: `test-contact-form.html`

1. **Basic Submission**
   - [ ] Fill out form with valid data
   - [ ] Submit and verify green success message
   - [ ] Verify form fields clear after success
   - [ ] Check admin email inbox for notification
   - [ ] Check sender email inbox for confirmation

2. **Validation Testing**
   - [ ] Try submitting empty form (should show browser validation)
   - [ ] Try invalid email format (should show browser validation)
   - [ ] Try submitting with network disabled (should show error message)

3. **Email Verification**
   - [ ] Admin email should have Reply-To set to sender's email
   - [ ] Clicking "Reply" should address the sender directly
   - [ ] Both emails should be HTML formatted
   - [ ] Sender name and message should display correctly

4. **Browser Compatibility**
   - [ ] Test in Chrome/Edge
   - [ ] Test in Firefox
   - [ ] Test in Safari
   - [ ] Test on mobile device

## 🔍 Debugging Tips

### If emails don't send:
1. Check `php/logs/app.log` for errors
2. Verify SMTP credentials in `.env`
3. Test SMTP connection: `php php/test-email.php`
4. Check if firewall blocks port 465
5. Verify SMTP server allows connection from your IP

### If form doesn't submit:
1. Open browser console (F12) and check for JavaScript errors
2. Check Network tab to see if `contact.php` returns 200 status
3. Look at the JSON response from the server
4. Verify file paths in `main.js` (fetch URL)

### If message doesn't display:
1. Check if `#contactFormMessage` div exists in HTML
2. Verify CSS for `.success` and `.error` classes
3. Check browser console for JavaScript errors

## 📁 Files Modified/Created

### Created:
- `php/MailHandler.php` - SMTP email handler with PHPMailer
- `php/test-email.php` - SMTP connection test script
- `test-contact-form.html` - Standalone form test page
- `CONTACT_FORM_CHECKLIST.md` - This file

### Modified:
- `.env` - Added SMTP configuration, fixed port 465 to use ssl
- `composer.json` - Added phpmailer/phpmailer dependency
- `php/config.php` - Removed old sendEmail() function
- `php/contact.php` - Updated to use MailHandler with Reply-To
- `js/main.js` - Improved form handler with CSS classes
- `css/styles.css` - Added #contactFormMessage styling
- `index.html` - Added #contactFormMessage div

## 🚀 Production Deployment

Before going live:
1. [ ] Update `.env` with production SMTP credentials
2. [ ] Set correct ADMIN_EMAIL in `.env`
3. [ ] Test form on production server
4. [ ] Verify emails are received
5. [ ] Remove or secure test files:
   - `php/test-email.php`
   - `test-contact-form.html`
6. [ ] Enable error logging but disable debug output
7. [ ] Add rate limiting to prevent spam (optional)
8. [ ] Consider adding reCAPTCHA (optional)

## ✨ Features Implemented

- ✅ SMTP email delivery (more reliable than PHP mail())
- ✅ HTML formatted emails
- ✅ Reply-To header for easy admin replies
- ✅ Automatic confirmation email to sender
- ✅ Inline success/error messages (no browser alerts)
- ✅ Loading state during submission
- ✅ Form validation (required fields)
- ✅ Email sanitization and XSS protection
- ✅ Error logging for debugging
- ✅ Graceful fallback to PHP mail() if PHPMailer unavailable
- ✅ Smooth scroll to messages
- ✅ Mobile-responsive design

## 📧 Email Flow

1. **User submits form** → `index.html`
2. **JavaScript intercepts** → `js/main.js`
3. **Sends JSON to backend** → `php/contact.php`
4. **Backend validates** → Sanitizes and checks required fields
5. **MailHandler sends emails** → Via SMTP using PHPMailer
   - Email #1: To admin with visitor's message (Reply-To: visitor)
   - Email #2: To visitor with confirmation
6. **Backend returns JSON** → Success/error response
7. **JavaScript displays message** → Green success or red error
8. **Form clears** (on success) → Ready for next submission

---

**Status:** ✅ Contact form is fully functional and ready for testing!
