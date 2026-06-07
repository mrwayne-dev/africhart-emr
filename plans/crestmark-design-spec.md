# Crestmark — Complete UI/Design Specification

> Extracted from live site: `crestmark.framer.website` · Date: 2026-05-31 · Viewports analysed: 1440 px desktop (rendered container: 1041 px), 768 px tablet, 375 px mobile.

---

## A. Global Design Tokens

### A.1 Color Palette

| Token | Hex | Role |
|---|---|---|
| `--color-bg-page` | `#FFFFFF` | Page background (most sections), card backgrounds |
| `--color-bg-dark` | `#0D0D0D` | Primary dark background (hero, dark nav), hero video overlay base |
| `--color-bg-dark-40` | `rgba(13,13,13,0.40)` | Navbar frosted glass tint |
| `--color-surface-warm` | `#F8F7F5` | Section alternating background (logo marquee, testimonial, approach, FAQ, feature card bg) |
| `--color-surface-warm-alt` | `#FAF9F5` | FAQ item row background (very close to above) |
| `--color-surface-card` | `#FFFFFF` | Stat cards, FAQ accordion items, result cards, form |
| `--color-ink-primary` | `#1A1A1A` | Primary headings, dark text on light backgrounds |
| `--color-ink-body` | `#0D0D0D` | Body text (near-black), form labels, nav links on scrolled state |
| `--color-ink-muted` | `#636363` | Secondary/muted text — eyebrow labels, card descriptions, image captions, attribution roles |
| `--color-ink-white` | `#FFFFFF` | All text on dark/video backgrounds |
| `--color-accent-dark` | `#1A1A1A` | CTA buttons (dark pill), carousel arrows, approach dots (active) |
| `--color-accent-red` | `#C2001D` | Defined in token sheet — not visually prominent on surface; likely brand accent/alert |

**Background cadence (section alternation, top → bottom):**
* Hero: transparent (video) / dark `#0D0D0D`
* Logo marquee label strip: `#F8F7F5`
* Testimonial + Stats: `#F8F7F5`
* Sectors: `#FFFFFF`
* Approach: `#F8F7F5`
* Why Crestmark: `#FFFFFF`
* Results: `#F8F7F5`
* About/Team: `#FFFFFF`
* FAQs: `#F8F7F5`
* Footer: `#FFFFFF`

The core rhythm is an alternation between pure white `#FFFFFF` and warm off-white `#F8F7F5`, with near-black `#1A1A1A` reserved for ink and CTAs.

---

### A.2 Typography

**Font families loaded:**
* **Primary (display + body):** `"Google Sans"` (also served as `"General Sans"` per font stack — variable font) · weights 400, 500, 600
* **Fallback stack:** `sans-serif`
* Loaded by the Framer runtime but unused visually: `Super Sans VF`, `Super Serif VF`, `Inter`, `Inter var`

#### Type Scale (desktop)

| Role | Element | Size | Weight | Line-height | Letter-spacing | Color |
|---|---|---|---|---|---|---|
| Hero marquee / scroll text | `h1` | 58 px | 400 | 68 px | −1.1 px | `#FFFFFF` |
| Section heading | `h2` | 32 px | 500 | 40 px | −1 px | `#1A1A1A` |
| Metric / stat counter | `p` (large) | 56 px | 500 | 61.6 px | normal | `#1A1A1A` |
| Result card metric | `h3` | 28 px | 600 | 32 px | normal | `#FFFFFF` |
| Card title (sector/feature/approach) | `p` | 20 px | 400 | 24 px | normal | `#1A1A1A` |
| Contact / 404 page headline | `h1` | 58 px | 400 | 68 px | −1.1 px | `#FFFFFF` |
| Hero subtext / contact subtext | `p` | 20 px | 400 | 24 px | normal | `#FFFFFF` |
| Contact / footer callout subtext | `p` | 20 px | 400 | 24 px | normal | `#636363` |
| Testimonial quote | `h2` | 32 px | 500 | 40 px | −1 px | `#1A1A1A` |
| Body / card description | `p` | 16 px | 400 | 22.4 px | normal | `#636363` |
| Nav links / button labels / footer links | `p` | 16 px | 400 | 22.4 px | normal | `#FFFFFF` / `#0D0D0D` |
| Eyebrow label (section label) | `p` | 16 px | 400 | 22.4 px | normal | `#636363` |
| Attribution name | `p` | 14 px | 400 | 16.8 px | normal | `#1A1A1A` |
| Attribution role / logo marquee label | `p` | 14 px | 400 | 16.8 px | normal | `#636363` |
| Team member name | `p` | 18 px | 500 | 22 px | normal | `#1A1A1A` |
| Team member role | `p` | 16 px | 400 | 22.4 px | normal | `#636363` |

**Mobile / tablet scaling:** No font-size reflow detected at 375–768 px. Framer renders all type at the same px values; the fixed ~1041 px container is scaled to fit the viewport via a CSS transform. The hero marquee H1 (58 px) and section headings (32 px) stay constant across breakpoints at the design level.

---

### A.3 Spacing System

**Base unit:** 4 px (implied — observed values are multiples of 4/8/10/12/16/20/24/40/60/120)

| Context | Value |
|---|---|
| Page container max-width | 1041 px (rendered) · designed for ~1200–1440 px viewport |
| Container horizontal padding | 40 px left + right |
| Section vertical padding (standard) | 60 px top + bottom |
| Hero top padding | 120 px |
| Hero bottom padding | 32 px |
| Logo marquee section vertical padding | 20 px top + bottom |
| Testimonial section padding | 40 px top, 60 px bottom |
| Footer top padding | 40 px |
| Card internal padding (standard) | 20 px all sides |
| Stat card padding | 24 px all sides |
| Form padding | 24 px all sides |
| Gap between 2-column card grids | 20 px |
| Gap between approach tab items | 12 px |
| Gap between eyebrow icon + label | 5 px |
| Gap between hero marquee items | 10 px |
| Gap between main content blocks (footer column) | 40 px |
| Gap between form label + input | 10 px |
| Gap between form fields | 20 px |

---

### A.4 Radii, Borders, Shadows

| Element | Border-radius | Border | Shadow |
|---|---|---|---|
| Primary CTA buttons (dark pill) | 30 px | none | none |
| "Partner with us" nav button (white pill) | 30 px | none | none |
| "Connect with our team" / FAQ CTA | 30 px | none | none |
| Carousel nav arrows | 40 px (full circle, 40×40 px) | none | none |
| FAQ accordion + icon circle | 50% (26×26 px dark circle) | none | none |
| Approach tab bullet (active) | 8 px (10×10 px dot) | none | none |
| Sector/feature/team/FAQ cards | 8 px | none | none |
| Result cards | 8 px | none | none |
| Approach images | 8 px | none | none |
| Form container | 8 px | none | none |
| Form inputs (wrapper) | 4 px | none | none |
| Submit button (contact form) | 4 px | none | none |
| Testimonial portrait | 4 px | none | none |

**No shadows are used anywhere on the site.** Depth is created entirely through background-color contrast (white cards on warm surfaces) and the radial gradient on result cards.

---

### A.5 Button Variants

| Variant | Background | Text color | Padding | Radius | Font | Usage |
|---|---|---|---|---|---|---|
| **Dark pill (primary CTA)** | `#1A1A1A` | `#FFFFFF` | 10 px / 16 px | 30 px | 16 px / 400 | "Start a conversation", "Discuss an opportunity", "Submit an inquiry", footer CTA |
| **White pill (nav CTA)** | `#FFFFFF` | `#0D0D0D` | 10 px / 16 px | 30 px | 16 px / 400 | "Partner with us" nav button |
| **Dark rounded (form submit)** | `#636363` | `#FFFFFF` | 12 px / 28 px | 4 px | 16 px / 400 | "Send message" on contact page |
| **White rounded (404 CTA)** | `#FFFFFF` | `#0D0D0D` | 10 px / 16 px | 30 px | 16 px / 400 | "Return to Homepage" on 404 page |
| **Dark circle (carousel arrow)** | `#1A1A1A` | white SVG | 0 (40×40 px) | 40 px | — | Prev/Next arrows |

**Hover states:** No CSS `transition` rules in the stylesheets — Framer handles hover via JS/inline style. Observed: the white nav button lightens slightly on hover; dark pills appear to darken. Exact easing not measurable (estimate `ease-out` ~150–200 ms).

---

### A.6 Motion & Animation

| Element | Behaviour | Notes |
|---|---|---|
| Hero word marquee | Continuous horizontal scroll (`overflow: clip` container, flex-row of duplicated items) | No CSS `animation` found on DOM — driven by Framer Motion at runtime |
| Stats counter | Count-up from 0 to target on scroll-enter | Observed counting toward `15+` / `750M+` after entering viewport |
| Scroll-triggered fade-in | Sections/cards fade up as they enter the viewport | Cards appear partially faded when just entering view |
| FAQ accordion | Click toggles height; `+` icon (dark circle, white SVG) swaps to `×` on open | Row 60 px closed, expands to reveal answer |
| Approach tabs | Click on a tab updates the right-panel image + text | Active dot `#1A1A1A`, inactive `#636363`; crossfade assumed |
| Results carousel | ← / → arrows slide cards horizontally (overflow-hidden container) | 3 visible cards at desktop |
| Logo marquee | Horizontal auto-scroll of partner logos | Flex-row + `overflow: clip` + Framer animation |
| Navbar background | Hero: `rgba(13,13,13,0.40)` + `backdrop-filter: blur(6px)`; after scroll: `#FFFFFF` solid | Dark-glass → white on scroll |

---

## B. Layout System

### Grid / Container Strategy

* **Single centred column** at 961–1041 px wide with 40 px gutters each side.
* **2-column grid** (equal halves: `470.5 px + 470.5 px`, gap 20 px) used for: sector cards (2-up × 3 rows = 6), Why Crestmark cards (2-up × 3 = 6), team cards (2-up, 3rd in left column only), stat cards (2-up).
* **Approach section:** asymmetric 2-column flex row (354 px tabs | 16 px gap | 591 px content panel).
* **Results carousel:** single `overflow: hidden` row, 3 cards visible (310 px each, gap ~16 px).
* **Footer credits bar:** `justify-content: space-between` flex row.

### Breakpoints Observed

| Breakpoint | Nav behaviour | Grid behaviour |
|---|---|---|
| ≥ 769 px (desktop) | Full horizontal nav + "Partner with us" pill | 2-column grids |
| ≤ 768 px (tablet) | Hamburger (≡) replaces nav links | Remains 2-column (scaled) |
| ≤ 375 px (mobile) | Hamburger; content scales via CSS transform | Same 2-column layout (scaled) |

> **Note:** The Framer build renders content in a fixed-width container (~1041 px) scaled down via `transform: scale()` to fit narrower viewports. True fluid responsive reflow (font-size changes, column stacking) is not present at the element level. A rebuild on another stack should implement genuine responsive CSS breakpoints to reproduce the visual result.

---

## C. Page & Section Breakdown

### C.1 Navbar

**Purpose:** Global navigation, persistent across all pages.

**Layout:** Full-width flex row — `[Logo] … [Impact | Sectors | Approach | Results | About] … [Partner with us]`.

* `nav` height 66 px, padding 12 px all sides.
* Background: `rgba(13,13,13,0.40)` + `backdrop-filter: blur(6px)` on the hero page; transitions to solid `#FFFFFF` on contact/404 and after scrolling past the hero.
* Logo: SVG wordmark, white on dark / dark on light, ~90 px wide.
* Nav links: 16 px / 400, `#FFFFFF` on dark, `#0D0D0D` on white, no underline.
* CTA: white pill (`#FFFFFF` bg, `#0D0D0D` text, 10/16 px padding, 30 px radius, ~42 px tall).
* **Mobile (≤768 px):** links hidden, hamburger top-right.

**Rebuild:** Sticky bar, `justify-content: space-between`, centered links. Use `backdrop-filter: blur(6px)` with the dark tint over the hero; switch to white solid on scroll.

---

### C.2 Hero

**Purpose:** Full-screen cinematic opener with brand identity and rotating marquee.

**Layout:** Full-viewport-height section (`height: 989 px`), `position: relative`, three layered zones:

1. **Background video** (`position: absolute`, `z-index: 1`) — looping, muted, autoplaying (`object-fit: cover`, fills 1041×989 px). Source: NYC skyline at dusk, Pexels id 5601649. No dark overlay div; the video's natural darkness gives contrast.
2. **Scrolling word marquee** (`position: relative`, `z-index: 5`, height 80 px) — horizontal `overflow: clip` row of `h1` words: `Capital • Returns • Execution • Strategy • Trust` (duplicated for a seamless loop). Each word 58 px / 400 / `#FFFFFF`; bullets `•` same style; item gap 10 px; scrolls left continuously.
3. **Bottom content strip** (`position: relative`, background `#FFFFFF`, height 122 px) — centered subtext (20 px / 400 / `#FFFFFF`) + small down-arrow SVG (31×30 px). The white strip creates a sharp cut between the video and the sections below.

**Padding:** 120 px top, 32 px bottom; gap between marquee and text strip 16 px.

**Rebuild:** `position: relative` section with `overflow: hidden`; absolutely positioned `<video autoplay muted loop playsinline>` behind content; marquee via duplicated word list in a flex row with `overflow: clip` animating `translateX(-50%)` (`@keyframes scroll`); white `<div>` at the bottom for subtext + arrow.

---

### C.3 Logo Marquee ("Companies we've partnered with")

**Purpose:** Social-proof partner-logo strip.

**Layout:** Full-width, `background: #F8F7F5`, 20 px vertical / 40 px horizontal padding. Two rows: a label (`"Companies we've partnered with"`, 14 px / 400 / `#636363`) and a horizontal `overflow: clip` flex row (gap 24 px) of monochrome logos (~91–101 px × 24 px, `object-fit: contain`). Outer container gap 40 px between label and logo rows.

**Rebuild:** Flex column with label + scrolling logo row. Duplicate the logo list, animate with `animation: scroll 20s linear infinite`, `overflow: hidden` on the wrapper.

---

### C.4 Testimonial

**Purpose:** Quote from a portfolio-company executive.

**Layout:** `background: #F8F7F5`, padding 40 px top / 60 px bottom / 40 px sides.

* Left ~60%: pull-quote `<h2>` — 32 px / 500 / `#1A1A1A` / lh 40 / −1 px.
* Right ~40%: attribution — portrait 40×40 px (`border-radius: 4px`, `object-fit: cover`) + name (14 px / 400 / `#1A1A1A`) + title/company (14 px / 400 / `#636363`), arranged in a horizontal flex row.

**Rebuild:** 2-column flex layout; left block quote, right flex row with small portrait + stacked name/role.

---

### C.5 Stats / Counters

**Purpose:** Key quantitative impact metrics.

**Layout:** 2-column grid (`470.5px 470.5px`, gap 20 px), inside the same `#F8F7F5` section as the testimonial.

**Card anatomy** (`#FFFFFF`, radius 8 px, padding 24 px, ~234 px tall, flex column):
* Top-right SVG icon (~24×35 px, `#0D0D0D`)
* Description (16 px / 400 / `#636363` / lh 22.4) top-left
* Counter number (56 px / 500 / `#1A1A1A` / lh 61.6) bottom-left; suffix ("M+", "+") part of the same node.

**Stats shown:** "15+" (combined leadership experience), "750M+" (capital deployed), plus "1+" portfolio companies at tablet.

**Animation:** count-up on scroll-enter (0 → target, ~1–2 s).

**Rebuild:** 2-column grid; cards `flex-direction: column`, `justify-content: space-between`; `IntersectionObserver` + `requestAnimationFrame` counter.

---

### C.6 Sectors (6-Card Image Grid)

**Purpose:** Showcase six investment sectors.

**Layout:** `background: #FFFFFF`, 60 px vertical / 40 px horizontal padding.

**Header** (flex column, gap 40 px to grid): eyebrow (SVG 20×20 px `#636363` + text "Sectors") → H2 (32 px / 500 / `#1A1A1A`) → body (16 px / 400 / `#636363`) → dark pill CTA "Start a conversation".

**Grid:** 2-column (`470.5px 470.5px`, gap 20 px), 3 rows = 6 cards.

**Card anatomy** (flex column, `overflow: clip`, no outer bg/radius):
* Image 470.5 × 250 px, `object-fit: cover`, no radius.
* Info box: `background: #F8F7F5`, radius 8 px, padding 20 px, ~164 px tall — icon SVG (20×20 px `#1A1A1A`) + title (20 px / 400 / `#1A1A1A` / lh 24) + description (16 px / 400 / `#636363` / lh 22.4).

**Sectors:** Healthcare, Industrial, Business services, Technology-enabled services, Financial services, Consumer essentials.

**Rebuild:** 2-column grid; each card = stacked image + warm-surface info card.

---

### C.7 Approach (Tabbed Stacked Sections)

**Purpose:** Explain the three-stage investment philosophy.

**Layout:** `background: #F8F7F5`, 60 px vertical / 40 px horizontal. Flex column (gap 40 px):

1. **Header** — same eyebrow + H2 + body + CTA pattern as Sectors.
2. **Two-column flex row** (gap 16 px):
   * **Left (354 px):** vertical tab list. Each tab = flex row, `align-items: center`, gap 12 px — bullet dot 10×10 px radius 8 px (active `#1A1A1A`, inactive `#636363`) + tab text 16 px / 400 (active `#1A1A1A`, inactive `#636363`). Tabs: Acquisition, Excellence, Partnership.
   * **Right (591 px):** stacked content blocks (one per tab, all in DOM). Each = image (550 × 360 px, `object-fit: cover`, radius 8 px) + info (icon SVG 20×20 px + title 20 px / 400 / `#1A1A1A` + description 16 px / 400 / `#636363` / lh 22.4).

**Rebuild:** Left nav as `<ul>` with bullet dots; right panel via JS tab switching or scroll-spy with height animation.

---

### C.8 Why Crestmark (6-Item Feature Grid)

**Purpose:** Six investment differentiators.

**Layout:** `background: #FFFFFF`, 60 px vertical / 40 px horizontal. Same header pattern.

**Grid:** 2-column (`470.5px 470.5px`, gap 20 px), 3 rows.

**Card anatomy** (`background: #F8F7F5`, radius 8 px, padding 20 px, ~164 px tall, flex column): icon SVG (20×20 px `#1A1A1A`) + title (20 px / 400 / `#1A1A1A` / lh 24) + description (16 px / 400 / `#636363` / lh 22.4).

**Features:** Strategy, Discipline, Partnership, Capital, Governance, Performance.

**Rebuild:** Identical to the Sectors card pattern — swap image-top for icon-top.

---

### C.9 Results (Carousel of Stat Cards)

**Purpose:** Portfolio performance metrics with company branding.

**Layout:** `background: #F8F7F5`, 60 px vertical / 40 px horizontal. Same header pattern ("Submit an inquiry").

**Carousel:** `width: 961px`, `overflow: hidden`, height 300 px, scrollable flex row of cards.

**Result card anatomy** (`background: #FFFFFF`, radius 8 px, padding 20 px, 310 × 300 px, flex column, `overflow: clip`, `position: relative`):
* Company logo top-left (120 × 30 px, `object-fit: contain`, white/inverted).
* Metric block bottom-left: `h3` value (28 px / 600 / `#FFFFFF`) + label (16 px / 400 / `#FFFFFF`).
* Background image fills the card (absolute, `object-fit: cover`).
* Gradient overlay (absolute, full card, `z-index: 1`): `radial-gradient(100% 71% at 100% 47.2%, rgba(13,13,13,0.40) 0%, rgba(0,0,0,0.50) 100%)` — dark vignette for text legibility.

**Arrows:** Prev/Next `<button>`s, `background: #1A1A1A`, radius 40 px, 40×40 px, absolutely positioned in a `<fieldset>` (`justify-content: space-between`) at vertical center; white SVG arrows inside.

**Cards (6):** Lightbox (+65% Operational Efficiency), FeatherDev (2.5x Enterprise Value), Spherule (+120% Customer Growth), Nietzsche (+48% EBITDA Improvement), Boltshift (14 Strategic Acquisitions), and one more (3.2x Revenue Growth).

**Rebuild:** Hidden `overflow-x` container with translate animation on arrow click; cards `flex-shrink: 0`; position-relative wrapper for absolutely positioned arrows; radial-gradient overlay div above the image.

---

### C.10 About / Team

**Purpose:** Introduce the leadership team.

**Layout:** `background: #FFFFFF`, 60 px vertical / 40 px horizontal.

**Header:** centered flex column (gap 40 px) — eyebrow + centered H2 + centered body (16 px / 400 / `#636363`).

**Grid:** 2-column (`470.5px 470.5px`, gap 20 px); 3 cards, card 3 in the left column only.

**Card anatomy** (`<a href="linkedin.com/feed/">`, `background: #F8F7F5`, radius 8 px, padding 20 px, ~354 px tall, flex column, gap 16 px, `overflow: clip`): portrait 430.5 × 250 px (`object-fit: cover`, no radius) + info row (`justify-content: space-between`) — name (18 px / 500 / `#1A1A1A` / lh 22) + role (16 px / 400 / `#636363`) + LinkedIn SVG (~22×28 px, `#1A1A1A`). The whole card is the `<a>`.

**Members:** Nowmy Su (Chief Tech Officer), Liam Rodriguez (Head of Sales), Ayesha Rahman (Founder & CEO).

**Rebuild:** 2-column grid; each card an `<a>` wrapping image + info; `overflow: clip` so the portrait sits flush.

---

### C.11 FAQs (Accordion)

**Purpose:** Expandable common questions.

**Layout:** `background: #F8F7F5`, 60 px vertical / 40 px horizontal.

**Header:** centered column (gap 16 px) — eyebrow "FAQs" + H2 "Frequently asked questions" + centered body (`#636363`) + dark pill CTA "Connect with our team".

**Accordion:** max-width ~640 px centered, flex column.

**Each item** (`background: #FFFFFF`, radius 8 px, 60 px tall closed, flex row, padding 20 px, `overflow: hidden`): question (16 px / 400 / `#1A1A1A`, left) + toggle circle (26 px, radius 50%, `#1A1A1A`) with white `+` SVG (20×20 px) that swaps to `×` on open. Open state expands height and reveals the answer (inferred 16 px / 400 / `#636363`).

**Questions (6):** What companies do you invest in? · What is your investment size? · Do you take majority stakes? · How involved are you after investing? · Which sectors do you focus on? · What is your investment horizon?

**Rebuild:** Wrap question + answer in an `overflow: hidden` container with a `max-height` transition (0 → auto via JS); toggle a class to switch `+` ↔ `×`.

---

### C.12 Footer

**Purpose:** Brand close, navigation, socials, credits.

**Layout:** `<footer>`, `background: #FFFFFF`, padding 40 px top / 0 bottom / 40 px sides. Single centered column (961 px, `align-items: center`, gap 40 px).

1. **Upper area** (centered flex column, gap 16 px): logo → H2 tagline (32 px / 500 / `#1A1A1A`, centered, −1 px) → body (16 px / 400 / `#636363`, centered) → dark pill CTA "Start a conversation".
2. **Nav link row** (centered flex row, `•` separators at 16 px / `#0D0D0D`): Impact · Sectors · Approach · Results · Partner with us · 404.
3. **Credits bar** (flex row, `justify-content: space-between`, padding-top 20 px, ~74 px tall): "By Shahrukh Qureshi" (left) · 3 social icons (LinkedIn, Facebook, X — 22×28 px SVG, `#1A1A1A`, center) · "Powered by Framer" (right).

**Rebuild:** Centered column with a clear divider between the upper CTA area and the nav/credits; nav links as a flex row with dot-separators.

---

## D. Component Inventory

### D.1 Eyebrow Label
`flex-row`, `align-items: center`, gap 5 px — `[SVG 20×20 px, #636363]` + `[<p> 16px/400/#636363]`. Use any Phosphor/Lucide/Heroicons SVG at 20 px.

### D.2 Section Header Block
Flex column (gap 16–24 px): eyebrow → `<h2>` (32 px / 500 / `#1A1A1A` / lh 40 / −1 px) → body `<p>` (16 px / 400 / `#636363`) → optional dark pill CTA. Left-aligned (Sectors, Approach, Why Crestmark, Results); centered (About, FAQ).

### D.3 Dark Pill Button (Primary CTA)
```
background: #1A1A1A; color: #FFFFFF;
padding: 10px 16px; border-radius: 30px;
font: 16px/400 "Google Sans";
display: inline-flex; align-items: center; height: ~42px;
```

### D.4 White Pill Button (Nav CTA)
```
background: #FFFFFF; color: #0D0D0D;
padding: 10px 16px; border-radius: 30px;
font: 16px/400 "Google Sans"; height: ~42px;
```

### D.5 Sector / Feature Card
```
background: #F8F7F5; border-radius: 8px; padding: 20px;
overflow: clip; flex-direction: column; gap: 12px;
width: 470.5px (in 2-col grid);
```
Contents: SVG icon → title (20px/400/#1A1A1A) → desc (16px/400/#636363/lh22.4).

### D.6 Sector Image + Card (Full Card)
Image (470.5×250 px, `object-fit: cover`) stacked on top of the D.5 card.

### D.7 Stat Counter Card
```
background: #FFFFFF; border-radius: 8px; padding: 24px;
flex-direction: column; justify-content: flex-start;
width: 470.5px; height: ~234px;
```
Contents: top-right SVG (24×35px, #0D0D0D) + desc (16px/400/#636363) + counter (56px/500/#1A1A1A/lh61.6).

### D.8 Result Carousel Card
```
background: #FFFFFF; border-radius: 8px; padding: 20px;
overflow: clip; flex-direction: column;
width: 310px; height: 300px; position: relative;
```
Layers: full-bleed bg image (absolute) + radial-gradient overlay (`radial-gradient(100% 71% at 100% 47.2%, rgba(13,13,13,0.40) 0%, rgba(0,0,0,0.50) 100%)`) + content (logo 120×30px + metric h3 28px/600/#FFF + label 16px/400/#FFF).

### D.9 Team Card
```
<a href="...">
background: #F8F7F5; border-radius: 8px; padding: 20px;
overflow: clip; flex-direction: column; gap: 16px;
width: 470.5px; height: ~354px;
```
Contents: portrait (430.5×250px, cover) + info row (space-between: name 18px/500/#1A1A1A + role 16px/400/#636363 + LinkedIn SVG).

### D.10 FAQ Accordion Item
```
background: #FFFFFF; border-radius: 8px; padding: 20px;
overflow: hidden; height: 60px (closed → expands on click);
flex-direction: row; justify-content: space-between; align-items: center;
width: ~640px;
```
Contents: question (16px/400/#1A1A1A) + toggle circle (26px, #1A1A1A, radius 50%) with white `+`/`×` SVG (20px).

### D.11 Carousel Navigation Button
```
background: #1A1A1A; border-radius: 40px;
width: 40px; height: 40px; border: none;
position: absolute (within fieldset overlay);
```
Contains a white chevron SVG.

### D.12 Navbar
```
<nav>
background: rgba(13,13,13,0.40) [hero] | #FFFFFF [scrolled/other pages];
backdrop-filter: blur(6px) [hero only];
padding: 12px; height: 66px; position: sticky; top: 0; z-index: high;
```
Internal: flex row `space-between` — logo (SVG) · center links (flex, gap ~24px) · right white-pill CTA.

### D.13 Testimonial Block
Flex row (2 cols): quote `<h2>` (32px/500/#1A1A1A/lh40/−1px) left + attribution stack (portrait 40×40px/radius4px + name 14px/400/#1A1A1A + role 14px/400/#636363) right.

### D.14 Contact Form
```
<form>
background: #FFFFFF; border-radius: 8px; padding: 24px;
width: 873px (inner); flex-direction: column; gap: 20px;
```
**Labels:** `<label>` flex column, gap 10 px · label text 16px/400/#0D0D0D.
**Text input wrapper:** `background: #F8F7F5`, radius 4 px, padding `8px 8px 8px 20px`, height 48 px.
**Text input:** `background: transparent`, `border: none`, `font: 16px "Google Sans"`, `color: #0D0D0D`.
**Textarea (Message):** wrapper `background: #F8F7F5`, radius 4 px, height 100 px, padding 0; textarea `background: transparent`, `border: none`, `16px "Google Sans"`, `#0D0D0D`.
**Submit button:**
```
background: #636363; color: #FFFFFF; border-radius: 4px;
padding: 12px 28px; font: 16px/400 "Google Sans";
width: full (873px); height: ~46px;
```
> The submit button uses `#636363` (muted grey) rather than the standard `#1A1A1A` dark pill — the only instance of this style.

**Fields (5):** Name, Email, Company, Phone, Message — single flex column, 20 px gaps, each label a flex column with 10 px gap to its input.

### D.15 Approach Image + Content Block
```
image: 550.625px × 360px, object-fit cover, border-radius 8px;
info area below: flex column, gap ~12px;
  icon: SVG 20×20px, #1A1A1A;
  title: 20px / 400 / #1A1A1A;
  description: 16px / 400 / #636363 / lh 22.4px;
```
Blocks stack vertically in the 591 px right column; scrolling reveals each.

---

## E. Imagery & Iconography

### E.1 Background Video
All three pages (home, contact, 404) use the **same looping NYC skyline video** (Pexels id 5601649), `autoplay muted loop playsinline`, rendered `100vw × 100vh` (1041×989 px), `object-fit: cover`. No explicit dark overlay div — the video's darkness provides contrast; on the homepage the nav adds a `rgba(13,13,13,0.40)` blurred glass tint over the video edge.

### E.2 Sector & Approach Photography
* **Aspect ratio:** sector images 470.5×250 px ≈ **1.88:1** (~16:9); approach images 550×360 px ≈ **1.53:1** (~3:2).
* **Treatment:** full colour, `object-fit: cover`, no radius on the image (the card clips it). No grayscale/tint.
* **Content:** editorial real-world photography (healthcare, industrial, business meetings, tech, retail/payments).

### E.3 Team / Portrait Photography
* **Aspect ratio:** 430.5×250 px ≈ **1.72:1** landscape crop; occupies the top ~70% of a portrait-orientation card (354 px tall).
* **Treatment:** full colour, `object-fit: cover`, no radius on the image (clipped by card radius 8 px + `overflow: clip`).
* **Testimonial portrait:** 40×40 px square, radius 4 px (rounded square, not circle), full colour.

### E.4 Result Card Images
Full card background, 310×300 px, `object-fit: cover`, absolute. Overlay: `radial-gradient(100% 71% at 100% 47.2%, rgba(13,13,13,0.40) 0%, rgba(0,0,0,0.50) 100%)` — darkening radial stronger toward right/center. Text sits above at `z-index > 1`.

### E.5 Partner / Company Logos
* **Homepage marquee:** ~88–101 px × 24 px, `object-fit: contain`, monochrome/dark-on-light.
* **Contact page grid:** 2-column (`206.25px 206.25px`, gap `16px 8px`), 8 logos.
* **Result card logos:** white/inverted, 120×30 px, `object-fit: contain`, top-left above the dark overlay.

### E.6 Icons
* **Style:** outlined/linear SVG from **Phosphor Icons** (`viewBox="0 0 256 256"`).
* **Size:** 20×20 px (eyebrow, section, card icons); 24×35 px (stat card icon); 20×20 px (FAQ icon inside the 26 px circle).
* **Color:** `#636363` (eyebrow icons); `#1A1A1A` / `#0D0D0D` (card, social, stat icons); white (carousel arrows, FAQ toggle).
* **No icon fonts** — all inline SVG.

### E.7 Logo (Crestmark Wordmark)
SVG wordmark: small abstract chain/link icon + "Crestmark" text. Two color contexts: white (nav on hero/404) and dark `#1A1A1A` (white-bg nav, footer). Rendered ~130–140 px wide.

---

## F. /contact Page

**Nav:** same sticky nav; **solid white** `#FFFFFF` (no glass tint, since this page doesn't open with full-screen video behind the nav). Full links visible.

**Hero:** full-screen (989 px), `background: #F8F7F5`, NYC video behind content (`z-index: 1` beneath content `z-index: 9`). Centered column: `<h1>` "Contact us" (58 px / 400 / `#FFFFFF`, centered, −1.1 px) + body (20 px / 400 / `#FFFFFF`, centered), overlaid on the video without a dedicated overlay div.

**Form block:** inside a `background: #F8F7F5`, radius 8 px, padding 20 px wrapper; form within a `#FFFFFF` radius 8 px card, padding 24 px. Full block 921 × 938 px:
1. The form (flex column, gap 20 px) — Name, Email, Company, Phone, Message + Submit (see D.14).
2. **Below-form row** (flex row, gap 80 px, two 420.5 px columns): **Left** — testimonial (quote-mark SVG + body 16 px / 400 / `#1A1A1A` + attribution name/role 14 px / `#636363` + portrait 40×40 px / radius 4 px); **Right** — "Trusted by leading companies" heading (20 px / 400 / `#1A1A1A`) + 2-column logo grid (`206.25px 206.25px`, gap `16px 8px`, 8 logos).

**Footer:** identical to homepage.

**Rebuild:** single `<section>` with absolute background video, content at higher z-index; form card + below-form testimonial/logos nested inside one warm-surface rounded container.

---

## G. /404 Page

**Nav:** same frosted-glass dark nav as the homepage. Full links visible.

**Hero:** full-screen (989 px), `background: #FFFFFF`, `display: flex; align-items: center; justify-content: center`. Same NYC video behind (`z-index: 1`).

**Content block** (centered flex column, gap 16 px): no large "404" numeral — `<h1>` "Not Found" (58 px / 400 / `#FFFFFF`, centered, −1.1 px) + subtext (20 px / 400 / `#636363`, centered) + CTA "Return to Homepage" (**white pill** variant).

**No footer** on this page.

**Rebuild:** full-viewport centered flex column, same video background, white-pill CTA, 8–16 px gap between H1, subtext, and button.

---

## H. Responsive Behaviour Summary

| Element | Desktop (1440 / 1041 container) | Tablet (768 px) | Mobile (375 px) |
|---|---|---|---|
| Nav | Full links + CTA pill | Hamburger (≡) | Hamburger (≡) |
| Nav background (hero) | `rgba(13,13,13,0.40)` blur glass | Same | Same |
| Hero marquee font | 58 px H1 | 58 px (scaled) | 58 px (scaled) |
| Hero subtext | 20 px | 20 px | 20 px |
| Section H2 | 32 px | 32 px | 32 px |
| Stat counter | 56 px | 56 px | 56 px |
| Card grids | 2-column (470.5 + 470.5) | 2-column (scaled) | 2-column (scaled) |
| Approach tabs | Side-by-side 2-col | 2-col (scaled) | 2-col (scaled) |
| FAQ accordion | 640 px centered | Scaled | Scaled |
| Footer | Single centered column | Single centered column | Single centered column |

> **Implementation note:** Framer scales a fixed ~1041 px container via `transform: scale()` rather than reflowing. A rebuild should use real responsive breakpoints.

**Recommended breakpoints for a rebuild:**

| Name | Min-width | Key changes |
|---|---|---|
| Mobile | 0 | Single-column grids, hamburger nav, smaller type (headings 48→32 px), 16 px horizontal padding |
| Tablet | 768 px | 2-column grids begin, still hamburger nav |
| Desktop | 1024 px | Full horizontal nav, max-width container (1200–1440 px) with 40 px gutters |

---

## I. Could Not Verify

| Item | Reason |
|---|---|
| Exact CSS easing curves (button hover) | No CSS `transition` rules; Framer Motion handles hover. Likely `ease-out` ~150–200 ms. |
| Marquee scroll speed | Runtime-driven, no `@keyframes`. Visually ~40–60 s per cycle. |
| Counter animation easing/duration | Framer component props. Visually ~1.5–2 s, `ease-out`. |
| FAQ expand animation | Height transition managed by Framer. Visually ~200–300 ms, `ease-out`. |
| Scroll-fade details | Framer scroll triggers. Likely Y offset 20–40 px, opacity 0→1, ~400–600 ms. |
| Approach tab transition | Crossfade timing Framer-driven, not measurable. |
| "Google Sans" / "General Sans" licensing | "Google Sans" is proprietary. For a non-Google rebuild, **General Sans** (Fontshare, free) is the closest match — same geometric humanist sans character. |
| Logo asset files | Partner logos are Framer template placeholders; real replacements needed. |
| Exact video file | Pexels video 5601649 is public and reusable. |
| Nav scroll transition timing | Dark-glass → white threshold and duration not measurable. |
| Mobile menu open state | Hamburger overlay open state not captured. |
| FAQ answer body text | Rendered with `overflow: hidden` at 60 px; not retrievable. Style inferred 16 px / 400 / `#636363`. |

---

*End of specification.*
