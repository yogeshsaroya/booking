#!/bin/bash

# SmartStayz Airbnb Image Downloader
# This script downloads all property images from your Airbnb listings
# Run this on a machine with internet access

echo "================================================"
echo "SmartStayz Airbnb Image Downloader"
echo "================================================"
echo ""

# Check for wget or curl
if command -v wget &> /dev/null; then
    DOWNLOADER="wget -q --show-progress"
    DL_FLAG="-O"
elif command -v curl &> /dev/null; then
    DOWNLOADER="curl -s -L"
    DL_FLAG="-o"
else
    echo "Error: Neither wget nor curl found. Please install one of them."
    exit 1
fi

# Create directories
mkdir -p airbnb-photos/{stone,cedar,copper}

# THE STONE
echo "Downloading The Stone images..."
cd airbnb-photos/stone

$DOWNLOADER "https://a0.muscache.com/im/pictures/hosting/Hosting-42680597/original/3841f7a5-c763-457e-84f7-30f2caac1c23.jpeg?im_w=1440" $DL_FLAG "01-hero.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-42680597/original/8d75f15f-c8a1-4d38-ad35-69afda6e1004.jpeg?im_w=1440" $DL_FLAG "02-interior.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-42680597/original/5d56d693-7f0c-41d0-91d6-bd40f43d63ca.jpeg?im_w=1440" $DL_FLAG "03-interior.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-42680597/original/2862f31e-62e4-43d9-a160-fbc1a6535c32.jpeg?im_w=1440" $DL_FLAG "04-living.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-42680597/original/6524c711-272c-43b6-b221-62fc77515e90.jpeg?im_w=1440" $DL_FLAG "05-kitchen.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/3dbf4477-6a59-4429-a237-cf005a4caf4d.jpg?im_w=1440" $DL_FLAG "06-bedroom1.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-42680597/original/2ad9a804-818b-4227-9acf-c34aa36a476a.jpeg?im_w=1440" $DL_FLAG "07-bedroom2.jpg"

stone_count=$(ls -1 | wc -l)
stone_size=$(du -sh . | cut -f1)
echo "  ✓ Downloaded $stone_count images ($stone_size)"

cd ../..

# THE CEDAR
echo "Downloading The Cedar images..."
cd airbnb-photos/cedar

$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-40961787/original/f7b22195-73c0-4b4e-b40e-4b4c4432f8ec.jpeg?im_w=1440" $DL_FLAG "01-hero.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-40961787/original/517aa180-cc2c-4b13-8548-351afb600241.jpeg?im_w=1440" $DL_FLAG "02-interior.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/hosting/Hosting-U3RheVN1cHBseUxpc3Rpbmc6NDA5NjE3ODc%3D/original/e246b6a0-6ef8-48f5-89db-ac424026bb9f.jpeg?im_w=1440" $DL_FLAG "03-kitchen.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/b88adc8c-98d7-4430-857d-d8f5f53260f2.jpg?im_w=1440" $DL_FLAG "04-bedroom1.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/e98d89a3-68db-41a9-9947-2e2cfca43c52.jpg?im_w=1440" $DL_FLAG "05-bathroom.jpg"
$DOWNLOADER "https://a0.muscache.com/im/pictures/miso/Hosting-40961787/original/1dbace91-c262-47ae-a7c5-8fc698e4d940.jpeg?im_w=1440" $DL_FLAG "06-bedroom2.jpg"

cedar_count=$(ls -1 | wc -l)
cedar_size=$(du -sh . | cut -f1)
echo "  ✓ Downloaded $cedar_count images ($cedar_size)"

cd ../..

# THE COPPER - Fetch page first
echo "Fetching The Copper listing page..."
if command -v curl &> /dev/null; then
    curl -s "https://www.airbnb.com/h/melville-copper" > copper-page.html
else
    wget -q -O copper-page.html "https://www.airbnb.com/h/melville-copper"
fi

echo "Downloading The Copper images..."
cd airbnb-photos/copper

# Extract image URLs from the page
image_urls=$(grep -oP 'https://a0\.muscache\.com/im/pictures/[^"]*\.(jpeg|jpg)' ../copper-page.html | grep -v 'im_w=240' | head -10)

counter=1
while IFS= read -r url; do
    # Add high-res parameter if not present
    if [[ ! $url =~ im_w= ]]; then
        url="${url}?im_w=1440"
    fi
    
    filename=$(printf "copper-%02d.jpg" $counter)
    
    if [[ $DOWNLOADER == "wget"* ]]; then
        wget -q --show-progress "$url" -O "$filename"
    else
        curl -s -L "$url" -o "$filename"
    fi
    
    ((counter++))
done <<< "$image_urls"

copper_count=$(ls -1 *.jpg 2>/dev/null | wc -l)
copper_size=$(du -sh . 2>/dev/null | cut -f1)
echo "  ✓ Downloaded $copper_count images ($copper_size)"

cd ../..

# Summary
echo ""
echo "================================================"
echo "Download Complete!"
echo "================================================"
echo ""
echo "Summary:"
echo "  The Stone:  $stone_count images"
echo "  The Cedar:  $cedar_count images"
echo "  The Copper: $copper_count images"
echo ""
echo "Images saved to:"
echo "  ./airbnb-photos/stone/"
echo "  ./airbnb-photos/cedar/"
echo "  ./airbnb-photos/copper/"
echo ""
echo "Next steps:"
echo "  1. Optimize images with TinyPNG.com or Squoosh.app"
echo "  2. Upload to website: /images/properties/"
echo "  3. Update HTML files to use real images"
echo ""
echo "See IMAGE_URLS.md for detailed instructions!"
