# SmartStayz Color Theme - Implementation Guide

## Overview
This website now uses the professional SmartStayz color theme with a warm gold accent color (#D4A574) replacing the previous cedar/copper tones.

---

## Primary Color Palette

| Element | Color Code | Color Name | Usage |
|---------|-----------|-----------|-------|
| **Primary Gold (CTA)** | `#D4A574` | Warm Gold/Mustard | Buttons, links, accents, hover states |
| **Gold Hover** | `#A67352` | Copper/Darker Gold | Button hover, active states |
| **Primary Text** | `#2B2724` | Charcoal | All body text, headings |
| **Secondary Text** | `#666666` | Medium Gray | Secondary information, muted text |
| **Background Primary** | `#FFFCF7` | Cream/Off-White | Main page background |
| **Background Secondary** | `#F5F3EF` | Light Cream | Section backgrounds, cards |
| **Light Gray** | `#999999` | Light Gray | Borders, muted elements |
| **Border Color** | `#CCCCCC` | Border Gray | Subtle borders |

---

## CSS Variables (in `:root`)

```css
--primary-gold: #D4A574;
--gold-hover: #A67352;
--charcoal: #2B2724;
--text-primary: #2B2724;
--text-secondary: #666666;
--bg-primary: #FFFCF7;
--bg-secondary: #F5F3EF;
--light-gray: #999999;
--border-light: #CCCCCC;
```

---

## Header Styles

### Type 1: Transparent Header (Home/Hero Pages)
**Used on:** `home2.html`, `home.html`

| Property | Value |
|----------|-------|
| Background | Transparent or `rgba(255, 252, 247, 0.05)` |
| Text Color | White (`#FFFFFF`) |
| Logo | White with drop-shadow |
| Nav Links | White |
| **On Scroll** (`.scrolled` class) | |
| Background | `rgba(255, 252, 247, 0.4)` with `backdrop-filter: blur(10px)` |
| Text Color | Charcoal (`#2B2724`) |
| Hamburger | White → Charcoal on scroll |

### Type 2: Solid Header (Content Pages)
**Used on:** `index.html`, `properties.html`, `booking.html`, `confirmation.html`, and all property detail pages

| Property | Value |
|----------|-------|
| Background | `#F5F3EF` (soft-cream) |
| Text Color | `#2B2724` (charcoal) |
| Class | `.nav-light` |
| Border Bottom | `1px solid rgba(0, 0, 0, 0.08)` |

---

## Footer

**All Pages**
- **Background:** `#2B2724` (charcoal/dark)
- **Text Color:** `#F5F3EF` (light cream)
- **Section Headings:** `#F5F3EF` (light cream), font-weight: 600
- **Links:** `rgba(245, 243, 239, 0.7)` (light cream with transparency)
- **Links Hover:** `#D4A574` (primary gold)
- **Bottom Border:** `1px solid rgba(245, 243, 239, 0.15)` (subtle divider)

---

## Button Styles

### Primary Button (`.btn-primary`)
```
Background: #D4A574 (primary gold)
Text: #FFFFFF (white)
Hover:
  - Background: #A67352 (gold-hover)
  - Shadow: 0 10px 30px rgba(212, 165, 116, 0.3)
  - Transform: translateY(-2px)
```

### Secondary Button (`.btn-secondary`)
```
Background: #FFFFFF (white)
Text: #D4A574 (primary gold)
Border: 2px solid #D4A574
Hover:
  - Background: #F5F3EF (soft-cream)
  - Color: #A67352 (gold-hover)
  - Border: 2px solid #A67352
```

### Outline Button (`.btn-outline`)
```
Background: Transparent
Border: 1px solid #D4A574
Text: #D4A574 (primary gold)
Hover:
  - Background: #D4A574 (primary gold)
  - Text: #FFFFFF (white)
```

### Sign-Up Button (`.btn-signup`)
```
Background: #FFFFFF (white)
Text: #D4A574 (primary gold)
Hover:
  - Background: #F5F3EF (soft-cream)
  - Shadow: 0 4px 12px rgba(0, 0, 0, 0.15)
  
On Scrolled Header:
  - Background: #D4A574 (primary gold)
  - Text: #FFFFFF (white)
  - Hover Background: #A67352 (gold-hover)
```

---

## Form & Interactive Elements

### Form Inputs (`.form-group input`, `select`, `textarea`)
```
Border: 1px solid rgba(107, 100, 89, 0.2)
Background: #FFFCF7 (warm-white)
Focus:
  - Border Color: #D4A574 (primary gold)
  - Shadow: 0 0 0 3px rgba(212, 165, 116, 0.15)
```

### Search Box (`.search-box`)
```
Background: #FFFFFF (white)
Border-Radius: 12px
Shadow: 0 20px 60px rgba(0, 0, 0, 0.3)
```

### Search Input Focus
```
Border: 1px solid #D4A574
Background: #FFFFFF (white)
Shadow: 0 0 0 3px rgba(212, 165, 116, 0.15)
```

### Search Button (`.search-btn`)
```
Background: #D4A574 (primary gold)
Text: #FFFFFF (white)
Shadow: 0 4px 12px rgba(212, 165, 116, 0.3)
Hover:
  - Background: #A67352 (gold-hover)
  - Shadow: 0 8px 20px rgba(212, 165, 116, 0.4)
  - Transform: translateY(-2px)
```

### Payment Options (`.payment-option`)
```
Border: 2px solid rgba(107, 100, 89, 0.2)
Hover:
  - Border: #D4A574 (primary gold)
  - Background: #F5F3EF (soft-cream)
  
Checked:
  - Border: #D4A574 (primary gold)
  - Background: rgba(212, 165, 116, 0.08)
  - Text: #D4A574 (primary gold)
```

---

## Text & Links

### Highlight Text (`.highlight`)
```
Color: #D4A574 (primary gold)
Font-Weight: 600
```

### Link Hover
```
Default: Charcoal (#2B2724)
Hover: Primary Gold (#D4A574)
Footer Links Hover: Primary Gold (#D4A574)
```

### Underline Animation (`.nav-menu a::after`)
```
Background: #D4A574 (primary gold)
Height: 2px
Transition: width 0.3s ease
```

---

## Stripe Elements

### Stripe Focus State (`.stripe-element.StripeElement--focus`)
```
Border: 1px solid #D4A574
Shadow: 0 0 0 3px rgba(212, 165, 116, 0.15)
```

---

## Typography

### Fonts
- **Display Font:** Poppins (300-700 weights)
- **Body Font:** Inter (300-700 weights)

### Heading Colors
```
H1, H2, H3, H4, H5, H6: #2B2724 (charcoal)
Font-Family: Poppins
```

### Body Text
```
Color: #2B2724 (charcoal)
Font-Family: Inter
Font-Size: 16px base
Line-Height: 1.7
```

---

## Property Cards & Sections

### Property Card (`.property-card`)
```
Background: #F5F3EF (soft-cream)
Border: 1px solid rgba(107, 100, 89, 0.1)
Hover:
  - Shadow: 0 20px 60px rgba(61, 53, 48, 0.15)
  - Transform: translateY(-8px)
```

### Property Tagline
```
Color: #666666 (text-secondary)
Font-Style: italic
```

---

## Shadows & Effects

### Box Shadows
```
Card Shadow: 0 20px 60px rgba(61, 53, 48, 0.15)
Button Shadow: 0 10px 30px rgba(212, 165, 116, 0.3)
Small Shadow: 0 5px 20px rgba(61, 53, 48, 0.1)
Header Shadow: 0 2px 4px rgba(0, 0, 0, 0.2) [logo drop-shadow]
```

### Backdrop Effects
```
Navigation: backdrop-filter: blur(10px)
```

---

## Responsive Breakpoints

| Breakpoint | Grid Adjustment |
|-----------|-----------------|
| 1200px+ | Desktop full layout |
| 1024px | Tablet layout, single column for some sections |
| 768px | Mobile navigation menu shown, stacked layouts |
| 480px | Small mobile, full-width elements |

---

## Implementation Summary

✅ **Updated Files:**
- `css/styles.css` - Main stylesheet with all theme colors
- `css/booking.css` - Form & payment colors
- `css/confirmation.css` - Confirmation page styling
- `home2.html` - Search box colors (inline styles)
- All HTML pages - Using consistent color theme

✅ **Color Changes Made:**
- Replaced `#8B6F47` (warm-cedar) with `#D4A574` (primary-gold)
- Replaced `#A67352` (copper-accent) with `#A67352` (gold-hover) for consistency
- Updated all button hover states to use gold theme
- Updated form focus states to use gold border/shadow
- Updated footer link hover to use primary gold
- Updated navigation underline to use primary gold

✅ **No Errors:** All CSS and HTML files validated successfully

---

## Color Test Checklist

- [ ] Primary buttons show gold (#D4A574)
- [ ] Button hover shows darker gold (#A67352)
- [ ] Form inputs focus with gold border
- [ ] Search button is gold
- [ ] Navigation underlines are gold
- [ ] Footer links hover to gold
- [ ] Payment options checked border is gold
- [ ] All text is charcoal (#2B2724)
- [ ] Background colors are cream/white
- [ ] Header transparent on home pages
- [ ] Header cream background on other pages

---

*Last Updated: January 31, 2026*
*Theme: SmartStayz Professional Gold*
