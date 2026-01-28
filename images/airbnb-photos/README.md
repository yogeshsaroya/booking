# SmartStayz Property Images Package

## What's in This Package

This package contains everything you need to download and use your actual Airbnb property photos on your SmartStayz website.

### Files Included

- **download-images.sh** - Automated script to download all images
- **IMAGE_URLS.md** - Complete list of all image URLs with usage guide
- **README.md** - This file

## Quick Start

### Option 1: Automatic Download (Recommended)

Run the download script on any computer with internet:

```bash
chmod +x download-images.sh
./download-images.sh
```

This will:
1. Create `airbnb-photos/` directory
2. Download all images from your Airbnb listings
3. Organize them by property (stone, cedar, copper)
4. Show you a summary when complete

### Option 2: Manual Download

If the script doesn't work:
1. Open `IMAGE_URLS.md`
2. Copy each image URL
3. Paste into browser and save with suggested filename

## What Images You're Getting

### The Stone (42680597)
- ★ 4.98 rating · 631 reviews
- 7 high-quality images
- Includes: hero shot, interiors, both bedrooms

### The Cedar (40961787)
- ★ 4.97 rating · 592 reviews
- 6 high-quality images
- Includes: hero shot, kitchen, bathroom, bedrooms

### The Copper (melville-copper)
- Custom Airbnb URL
- 6-10 images (extracted from page)
- Includes: hero shot, various interiors

## After Downloading

### 1. Optimize Images (Recommended)

Use TinyPNG.com or Squoosh.app to compress images:
- Target size: 150-300KB per image
- Quality: 85% JPEG
- This makes your website load faster!

### 2. Upload to Website

Copy images to your website's images folder:

```bash
cp -r airbnb-photos/* /path/to/website/images/properties/
```

Or via FTP:
- Upload to `/images/properties/stone/`
- Upload to `/images/properties/cedar/`
- Upload to `/images/properties/copper/`

### 3. Update HTML Files

Replace placeholders in your HTML files:

**Find:**
```html
<div class="image-placeholder"></div>
```

**Replace with:**
```html
<img src="images/properties/stone/01-hero.jpg" alt="The Stone - Hyde Park Vacation Rental">
```

Do this for all property pages:
- `property-stone.html`
- `property-cedar.html`
- `property-copper.html`
- `index.html` (homepage property cards)

## Image Organization

Your downloaded images will be organized like this:

```
airbnb-photos/
├── stone/
│   ├── 01-hero.jpg         ← Main photo for homepage/property page
│   ├── 02-interior.jpg     ← Gallery thumbnail 1
│   ├── 03-interior.jpg     ← Gallery thumbnail 2
│   ├── 04-living.jpg       ← Gallery thumbnail 3
│   ├── 05-kitchen.jpg      ← Gallery thumbnail 4
│   ├── 06-bedroom1.jpg     ← Bedroom 1 (king bed)
│   └── 07-bedroom2.jpg     ← Bedroom 2 (queen bed)
│
├── cedar/
│   ├── 01-hero.jpg         ← Main photo for homepage/property page
│   ├── 02-interior.jpg     ← Gallery thumbnail 1
│   ├── 03-interior.jpg     ← Gallery thumbnail 2
│   ├── 04-living.jpg       ← Gallery thumbnail 3
│   ├── 05-kitchen.jpg      ← Gallery thumbnail 4
│   ├── 06-bedroom1.jpg     ← Bedroom 1 (king bed)
│   └── 07-bedroom2.jpg     ← Bedroom 2 (queen bed)
│
└── copper/
│   ├── 01-hero.jpg         ← Main photo for homepage/property page
│   ├── 02-interior.jpg     ← Gallery thumbnail 1
│   ├── 03-interior.jpg     ← Gallery thumbnail 2
│   ├── 04-living.jpg       ← Gallery thumbnail 3
│   ├── 05-kitchen.jpg      ← Gallery thumbnail 4
│   ├── 06-bedroom1.jpg     ← Bedroom 1 (king bed)
│   └── 07-bedroom2.jpg     ← Bedroom 2 (queen bed)
```

## Why These Images?

✅ **They're YOUR photos** - You own full rights
✅ **Professional quality** - Already taken by Airbnb photographers
✅ **Guests trust them** - Same photos they see on Airbnb
✅ **SEO optimized** - High resolution, properly sized
✅ **No cost** - You already have them!

## Image Sizes

All images download at **1440px width** (high resolution).

For best website performance, resize to:
- **Hero images:** 1200x800px
- **Gallery thumbnails:** 800x600px  
- **Bedroom photos:** 600x400px

## Troubleshooting

### Script Won't Run
```bash
# Make it executable first
chmod +x download-images.sh

# Then run it
./download-images.sh
```

### "Command not found: wget"
Install wget:
- **Mac:** `brew install wget`
- **Linux:** `sudo apt-get install wget`
- **Or:** The script will use curl instead

### No Images Downloaded
1. Check internet connection
2. Try manual download from IMAGE_URLS.md
3. Visit Airbnb listings directly and right-click → Save Image

### Images Too Large
Use TinyPNG.com or Squoosh.app to compress them before uploading to your website.

## Support

If you need help:
1. Check IMAGE_URLS.md for detailed instructions
2. Try manual download method
3. Contact your web developer with this package

## Next Steps

After downloading images:

1. ✅ Run `download-images.sh`
2. ✅ Optimize images for web
3. ✅ Upload to website server
4. ✅ Update HTML files
5. ✅ Test that images load correctly
6. ✅ Launch your beautiful new website!

---

**You're just a few steps away from having a gorgeous website with your actual property photos!**
