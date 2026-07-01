# 1st Davenham Scout Group — Design System

> **Authoritative reference.** All colours, typography, spacing, components and
> patterns in this project must follow this file. Do not introduce any value not
> listed here without updating this file first.
>
> Brand foundation: Scouts UK national brand (scouts.org.uk), faithfully applied.
> Davenham-specific sections cover the visual page builder and admin UI only.

---

## 1. Brand Identity

### Logo

The group logo is the standard Scouts UK wordmark:
- **"Scouts"** — bold, Scouts purple (`#590FA9`)
- **"1st Davenham"** — bold, smaller, Scouts purple
- **Fleur-de-lis** — Scouts purple

Always shown in full purple on white/light backgrounds, or reversed white on
dark/coloured backgrounds. No custom colours or additional marks.

### Colour hierarchy

Purple (`#590FA9`) is the primary brand colour — hero backgrounds, footer, key
sections, nav accents. Blue (`#006DDF`) drives primary CTAs, links and buttons.
Teal (`#088486`) accents the news section exclusively. This matches the national
scouts.org.uk design language exactly.

---

## 2. Colour Palette

### 2.1 Primary Brand Colours

| Token | Hex | RGB | Usage |
|---|---|---|---|
| `purple` | `#590FA9` | `rgb(89, 15, 169)` | Hero bg, footer, CTA sections, nav hover |
| `blue` | `#006DDF` | `rgb(0, 109, 223)` | Primary button, links, Donate button |
| `pink` | `#FFB4E5` | `rgb(255, 180, 229)` | H1 heading text on purple backgrounds |
| `orange` | `#FF912A` | `rgb(255, 145, 42)` | Search/CTA button accent |
| `teal` | `#088486` | `rgb(8, 132, 134)` | News section background only |
| `yellow` | `#FFE627` | `rgb(255, 230, 39)` | Safeguarding/alert section background |

### 2.2 Secondary Colours

| Token | Hex | RGB | Usage |
|---|---|---|---|
| `dark-green` | `#205B41` | `rgb(32, 91, 65)` | Feature icon circles |
| `light-green` | `#25B755` | `rgb(37, 183, 85)` | Success states, notice buttons |
| `green` | `#008A1C` | `rgb(0, 138, 28)` | Success / positive status |
| `navy` | `#003982` | `rgb(0, 57, 130)` | Deep navy accent, gradient start, dark panels |
| `dark-grape` | `#490499` | `rgb(73, 4, 153)` | Deeper purple variant, gradient end |
| `red` | `#ED3F23` | `rgb(237, 63, 35)` | Error states, danger alerts, destructive actions |

### 2.3 Grays & Neutrals

| Token | Hex | Usage |
|---|---|---|
| `text-primary` | `#404040` | Primary body text (`rgb(64,64,64)`) |
| `text-dark` | `#333333` | Dark text variant, headings on white |
| `text-secondary` | `#6E6E6E` | Secondary / muted text |
| `text-muted` | `#999999` | Placeholder, disabled text |
| `border` | `#CCCCCC` | Borders, dividers |
| `bg-light` | `#F1F1F1` | Light section backgrounds, age groups, inputs |
| `bg-off-white` | `#F6F6F6` | News card content background |
| `white` | `#FFFFFF` | Page background, cards |

### 2.4 Builder-only UI Tints (admin interface — not for frontend output)

| Token | Hex | Usage |
|---|---|---|
| `purple-tint` | `#f3eaff` | Hover backgrounds, icon badges, step circles |
| `purple-hover` | `#faf4ff` | Card hover background, add-section hover |
| `purple-subtle` | `#f6f0ff` | Image upload buttons, library switcher bg |
| `bg-canvas` | `#f0f1f2` | Builder canvas background |
| `bg-surface` | `#f8fafc` | Tab list background |
| `bg-panel` | `#fafafa` | Advanced panels |
| `dark-bg` | `#0f172a` | Dark section backgrounds |
| `dark-bg-alt` | `#1f2f55` | Dark gradient end |
| `border-ui` | `#e5e7eb` | Builder card borders, tab borders |
| `border-input` | `#ddd` | Input borders, sidebar border |

### 2.5 CSS Custom Properties (national brand tokens)

```css
:root {
  --primary:       #006ddf;
  --secondary:     #590FA9;
  --success:       #008A1C;
  --info:          #17a2b8;
  --warning:       #ffe627;
  --danger:        #ed3f23;
  --light:         #f1f1f1;
  --dark:          #000000;

  --blue:          #006ddf;
  --purple:        #590FA9;
  --grape:         #590FA9;
  --dark-grape:    #490499;
  --pink:          #ffb4e5;
  --navy:          #003982;
  --green:         #008A1C;
  --light-green:   #25b755;
  --dark-green:    #205b41;
  --orange:        #ff912a;
  --teal:          #088486;
  --yellow:        #ffe627;
  --red:           #ed3f23;
  --cyan:          #17a2b8;

  --gray-light:    #cccccc;
  --gray:          #999999;
  --gray-dark:     #6e6e6e;
  --gray-black:    #333333;

  --white:         #ffffff;
  --transparent:   rgba(0,0,0,0);
}
```

### 2.6 Section Stripe Colours (builder canvas only)

Decorative 3px top-stripes on builder canvas cards — never used in frontend output.

| Block | Stripe colour |
|---|---|
| `davenham/hero`, `davenham/page-hero`, `davenham/faq`, `davenham/contact-info` | `#003982` |
| `davenham/site-notice`, `davenham/sponsors`, `davenham/gallery` | `#088486` |
| `davenham/welcome-section`, `davenham/cta-button-row` | `#008A1C` |
| `davenham/leaders`, `davenham/age-section` | `#590FA9` |
| `davenham/news-feed`, `davenham/icon-feature-row`, `davenham/video-embed` | `#ED3F23` |
| `davenham/events-list` | `#FFE627` |
| `davenham/text-image`, `davenham/rich-text` | `#555` / `#888` |

---

## 2.7 Implemented Token Layer (source of truth)

Every value in §2–§7 and §13 is implemented as CSS custom properties in
`wp-content/themes/the-scouts-skills-for-life/tokens.css`, namespace `--scout-*`,
enqueued **before all other CSS** on the front end and across wp-admin.

> **This token file is the single source of truth. All new or edited CSS MUST
> consume values via `var(--scout-…)` — never hardcode a value that has a token.**
> Plugins use `var(--scout-…, <fallback>)` so they degrade safely if the theme
> is inactive.

Representative tokens:

| Token | Value | | Token | Value |
|---|---|---|---|---|
| `--scout-purple` | `#590FA9` | | `--scout-text` | `#404040` |
| `--scout-blue` | `#006DDF` | | `--scout-text-2` | `#6E6E6E` |
| `--scout-teal` | `#088486` | | `--scout-text-muted` | `#999999` |
| `--scout-navy` | `#003982` | | `--scout-border` | `#CCCCCC` |
| `--scout-green` | `#008A1C` | | `--scout-card-border` | `#E5E7EB` |
| `--scout-red` | `#ED3F23` | | `--scout-bg` | `#F1F1F1` |
| `--scout-radius-card` | `10px` | | `--scout-radius-button` | `0` |
| `--scout-shadow-card` | see §7 | | `--scout-focus` | see §7 |
| `--scout-space-xl` | `24px` | | `--scout-container` | `1180px` |

## 2.8 UI State Tints & File-Type Badges

These close the two gaps that previously drove off-palette colour. No new solid
hexes — state tints are low-alpha of the brand semantic colours.

**Message / state blocks:**

| State | Border + text | Background tint |
|---|---|---|
| Success | `--scout-success` (`#008A1C`) | `--scout-success-tint` (`rgba(0,138,28,0.08)`) |
| Error | `--scout-danger` (`#ED3F23`) | `--scout-danger-tint` (`rgba(237,63,35,0.08)`) |

**File-type badges (documents library)** — mapped to brand palette, not vendor colours:

| Type | Token | Value |
|---|---|---|
| PDF | `--scout-file-pdf` | `#ED3F23` (red) |
| Word | `--scout-file-doc` | `#003982` (navy) |
| Excel | `--scout-file-xls` | `#008A1C` (green) |
| PowerPoint | `--scout-file-ppt` | `#FF912A` (orange) |
| Image | `--scout-file-img` | `#590FA9` (purple) |

---

## 3. Gradients

Always use 135° angle. Never invent new gradients.

| Name | Value | Usage |
|---|---|---|
| Brand | `linear-gradient(135deg, #003982 0%, #590FA9 100%)` | Modal headers, quote banners, hero overlay |
| Purple | `linear-gradient(135deg, #590FA9 0%, #490499 100%)` | Promo banners, split CTA alt card |
| Dark | `linear-gradient(135deg, #0f172a 0%, #1f2f55 100%)` | Split CTA default, newsletter, popup promo |
| Purple UI | `linear-gradient(135deg, #590FA9 0%, #490499 100%)` | Active tab in library switcher (admin UI only) |
| Purple card tint | `linear-gradient(180deg, #fff 0%, #faf7ff 100%)` | Guide & preset list backgrounds (admin UI only) |

---

## 4. Typography

### Font stack

```css
/* Frontend (theme-provided) */
font-family: "Nunito Sans", -apple-system, "system-ui", "Segoe UI", Roboto,
             Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;

/* Admin UI only */
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
```

**Nunito Sans** is the sole Scouts UK typeface — a rounded, friendly sans-serif.
Note: the frontend theme must load **Nunito Sans** (not plain Nunito — they are
different typefaces).

### Font weights

| Weight | Value | Usage |
|---|---|---|
| Regular | `400` | Body copy |
| Medium | `500` | Toggle labels (builder) |
| Semi-bold | `600` | Notice links, field labels in builder |
| Bold | `700` | H3, H6, nav links, card titles, all button text, meta labels |
| Extra-bold | `800` | Stat values, quote banner text, hero text (Davenham blocks) |
| Black | `900` | H1, H2, H4 display headings (national standard) |

### Type scale

| Element | Size | Weight | Line height | Colour (context) |
|---|---|---|---|---|
| H1 (page hero) | `64px` | `900` | `64px` (1:1) | `#FFB4E5` pink on purple bg |
| H2 (section heading) | `48px` | `900` | `57.6px` | `rgb(64,64,64)` / white on dark |
| H3 | `20px` | `700` | `24px` | `rgb(0,0,0)` / contextual |
| H4 | `24px` | `900` | — | `rgb(64,64,64)` |
| H6 (footer column head) | `16px` | `700` | — | `#FFFFFF` |
| Body | `16px` | `400` | `22.4px` | `rgb(64,64,64)` |
| News card title | `32px` | `700` | — | `rgb(64,64,64)` |
| Small / meta | `12px` | — | — | Builder sidebar |
| Caps label | `11px` | `700` | — | Uppercase + `letter-spacing: 0.8px` |
| Fluid stat | `clamp(2rem, 4vw, 3rem)` | `800` | — | Davenham stats block |
| Fluid quote | `clamp(1.6rem, 4vw, 2.6rem)` | `800` | — | Quote banner |

### Heading margins

- `h1`: `margin-bottom: 8px`
- `h2`: `margin-bottom: 44px`

### Text transforms

Uppercase exclusively for caps labels: field labels, category headers, eyebrows,
section tags. Always pair with `letter-spacing: 0.8px` and `font-weight: 700`.

---

## 5. Spacing

| Size | Value | Common usage |
|---|---|---|
| 2xs | `3px` | Tag padding, chevron offsets |
| xs | `4–6px` | Inline gaps, badge padding |
| s | `8–10px` | Button padding (vertical), small gaps |
| m | `12–14px` | Icon gap, field margin, card header padding |
| l | `16–20px` | Button padding (horizontal), FAQ padding, tab padding |
| xl | `24px` | Card padding, grid gap |
| 2xl | `32px` | Leaders gap, promo padding |
| 3xl | `40–44px` | Section header margin, video/quote padding |
| 4xl | `60–64px` | Default section vertical padding |
| 5xl | `70–80px` | Hero and search block padding |

### Container

```css
width: min(calc(100% - 32px), 1180px);
max-width: 1180px;
margin-inline: auto;
```

16px gutter each side at all viewport widths.

---

## 6. Border Radius

### 6.1 Frontend components (national standard)

The Scouts UK brand uses **sharp square corners** on interactive elements. This
is an intentional brand aesthetic — bold and graphic.

| Element | Radius |
|---|---|
| Primary buttons, outline buttons | `0px` |
| Search input (left side) | `5px 0 0 5px` |
| Search button (right side) | `0 5px 5px 0` |
| News / content cards | `10px` |
| Form inputs | `5px` |

### 6.2 Builder admin components (internal UI — not frontend)

The builder admin uses rounded corners for usability. These values apply only
inside `.db-app` and never appear in frontend output.

| Size | Value | Usage |
|---|---|---|
| xs | `3–4px` | Dismiss button, notice button, small badges |
| s | `6px` | Builder buttons, select fields |
| m | `8px` | Gallery items, toast notifications |
| l | `10–12px` | Section cards, modals, tabs container |
| xl | `14px` | Library cards, guide panels |
| 2xl | `16px` | Timeline content, download items |
| 3xl | `18px` | Stats / testimonial / card-grid / step cards |
| 4xl | `22px` | Promo cards, split-CTA, popup |
| 5xl | `24px` | Quote banner |
| pill | `999px` | Badges, tags, leader badge, toggle, tab pills |
| circle | `50%` | Leader photo, toggle knob |

---

## 7. Shadows

| Name | Value | Usage |
|---|---|---|
| National card | `rgba(0,0,0,0.1) 0px 0px 9px 2px` | News cards, content cards (national style) |
| Card | `0 4px 16px rgba(15, 23, 42, 0.06)` | Stats, testimonial, card-grid, key-facts |
| Card hover | `0 8px 28px rgba(15, 23, 42, 0.12)` | Card hover state |
| Purple hover | `0 8px 24px rgba(89, 15, 169, 0.12)` | Preset card hover, purple-context hover |
| Selected | `0 4px 16px rgba(89, 15, 169, 0.18)` | Builder section selected state |
| Timeline | `0 6px 20px rgba(15, 23, 42, 0.05)` | Timeline content cards |
| Focus ring | `0 0 0 3px rgba(89, 15, 169, 0.15)` | Input focus, search focus |
| Topbar | `0 2px 8px rgba(0, 0, 0, 0.35)` | Builder top bar |
| Modal | `0 20px 60px rgba(0, 0, 0, 0.3)` | Modal overlay |
| Library active | `0 10px 20px rgba(89, 15, 169, 0.22)` | Active library switcher tab |

---

## 8. Breakpoints

| Name | Value | Behaviour |
|---|---|---|
| Mobile | `≤ 480px` | 2-col gallery, 2-col leaders, 2-col age groups |
| Tablet | `≤ 700px` | Single-col news, leaders, reduced section padding |
| Desktop small | `≤ 768px` | Timeline and split-CTA collapse to single col |
| Desktop | `≤ 900px` | Contact map stacks, age groups 3-col |
| Nav | `≤ 992px` | Full desktop nav shown; mobile hamburger hidden |
| XL | `≥ 1200px` | Max container, full multi-column grids |

---

## 9. Buttons

Buttons are owned by the theme. Block CSS must **not** override button colours
or border-radius.

### Frontend button variants

| Variant | Background | Text | Border | Radius | Padding |
|---|---|---|---|---|---|
| Primary (filled) | `#006DDF` | `#FFFFFF` | `1px solid #006DDF` | `0px` | `12px 14px` |
| Outline white | `transparent` | `#FFFFFF` | `1px solid #FFFFFF` | `0px` | `12px 14px` |
| Donate | `#006DDF` | `#FFFFFF` | `1px solid #006DDF` | `0px` | `4px 12px` |
| Search / orange | `#FF912A` | `#000000` | none | `0 5px 5px 0` | `16px` |

All buttons: `font-weight: 700`, `font-size: 16px`, `transition: 0.15s ease`.

### News section button

```css
.news_section__btn {
  background: transparent;
  border: 1px solid #fff;
  border-radius: 0px;
  color: #fff;
  padding: 12px 14px;
  font-weight: 700;
}
.news_section__btn:hover {
  background: #fff;
  color: #088486;
}
```

---

## 10. Frontend Page Sections

Canonical section patterns for the Davenham site. All colours follow national brand.

### Hero (homepage)

Davenham adaptation: full-width background image with a branded colour overlay
box (the national site uses a solid purple bg; Davenham uses local photography
with a purple gradient overlay).

```
Section:    Full-width background image, object-fit: cover
Box:        background: linear-gradient(135deg, rgba(0,57,130,0.92) 0%, rgba(89,15,169,0.82) 100%)
            border-radius: 22px (desktop) / 0 (mobile)
            padding: 40px
Heading:    font-size: 64px, weight 900, color: #FFB4E5 (pink) — or #FFFFFF
Body text:  16px, #FFFFFF
Buttons:    outline white + filled
```

### Age Groups

6-column grid of compact logo cards on light grey.

```
Section bg:   #F1F1F1, padding: 60px 0
Heading:      "Explore our age groups", 48px, 900, rgb(64,64,64), centred, mb: 44px
Grid:         repeat(6, 1fr), gap: 16px
Card:         #FFFFFF, border-radius: 0px, padding: 20px 12px 18px
              flex column, align-items: center, gap: 10px
Logo img:     max-width: 80px, max-height: 52px, object-fit: contain
Label:        13px, 700, #555, centred
Responsive:   3 columns ≤ 900px, 2 columns ≤ 480px
```

### News / What's Happening

Matches the national scouts.org.uk news section exactly.

```
Section bg:   #088486 (teal), padding: 60px 0
Heading:      "What's happening", 48px, 900, #FFFFFF, centred
Subtitle:     16px, rgba(255,255,255,0.9), centred, mb: 44px
Grid:         repeat(3, 1fr), gap: 20px  [1 column ≤ 900px]
Cards:        #FFFFFF, border-radius: 10px, overflow: hidden
  Image:      full-bleed top, height: 240px, object-fit: cover
  Body:       background: #F6F6F6, padding: 16px 30px
  Title:      32px, 700, rgb(64,64,64)
  Shadow:     rgba(0,0,0,0.1) 0px 0px 9px 2px
Button:       .news_section__btn — white outline, 0px radius, fills white/teal on hover
```

### Welcome Section

```
Section bg:   #FFFFFF, padding: 60px 0, margin: 0
Container:    max-width: 740px, centred
Body:         16px, line-height: 1.4, rgb(64,64,64)
```

### FAQ

```
Background:   #FFFFFF
Question hover/open: #590FA9
Answer: 15px, line-height: 1.65, #555
```

### Contact

```
Section bg:   #590FA9 or brand gradient (navy → purple)
Text:         #FFFFFF
Map:          border-radius: 0px or 5px
```

### Footer

```
Background:   #590FA9 (purple)
Text:         #FFFFFF
Logo:         Scouts UK white mark + wordmark
Social icons: X, TikTok, Facebook, Instagram, YouTube, LinkedIn
Link columns: 4-column grid, H6 headings (16px, 700, white)
Legal bar:    Terms, Privacy, Cookies, Site Map, Copyright
```

---

## 11. Builder Block Components

### Plugin / block boundary for events

Events span two systems on this site:

- **`davenham-events-fundraising` plugin** owns the dedicated `/events/`
  archive and `/events/{slug}` single pages via the `event` custom post
  type and `template_include` filter. This is where editors manage the
  list of events, ticket links, fundraising totals and event metadata.
- **`davenham/events-list` + `davenham/event-ticket-card` builder blocks**
  remain for *promotional* placement of events on other pages — homepage
  hero, news articles, shop landing, etc. Both blocks read from the same
  `event` CPT and from linked WooCommerce ticket products.

Templates and blocks share the visual language (purple gradient hero,
pink `#FFB4E5` H1, sharp-corner buttons, `.event_*` BEM classes). Theme
templates can override either plugin template by placing `single-event.php`
or `archive-event.php` in the active theme.

### Block shell

All blocks that render full-width sections use `.db-block-shell` with CSS custom
properties for per-instance overrides:

```css
--db-bg-color
--db-text-color
--db-heading-color
--db-link-color
--db-text-align
--db-padding-top    /* default: 64px */
--db-padding-bottom /* default: 64px */
--db-max-width      /* default: 1180px */
--db-min-width      /* default: 0px */
```

### Cards

Standard card treatment used by stats-grid, testimonial-grid, card-grid,
donation-cards, key-facts, step-cards:

```css
background: #fff;
border: 1px solid #e5e7eb;
border-radius: 10px;
padding: 24px;
box-shadow: rgba(0,0,0,0.1) 0px 0px 9px 2px;
```

### Card with image

Image bleeds to top edge:

```css
.card_grid__image {
  margin: -24px -24px 18px;
  overflow: hidden;
  border-radius: 10px 10px 0 0;
}
.card_grid__image img { width: 100%; height: 210px; object-fit: cover; }
```

### Page Hero (`.hero.standard`)

Inner-page banner, no background image. Uses `.wrapper.alt > .inner`. H2 heading
+ optional `<p>` subtext. Background: `#590FA9`.

### FAQ accordion

- Container: `.faq_list`
- Item: `.faq_item` + `.faq_item--open` (JS-toggled)
- Question: `.faq_question` — hover/open colour `#590FA9`
- Chevron: `.faq_chevron` — CSS-only arrow, rotates on open
- Answer: `.faq_answer` — `font-size: 15px`, `line-height: 1.65`, `color: #555`

### Quote banner

Gradient background (`#003982 → #590FA9`), white text, `border-radius: 24px`.
Heading `clamp(1.6rem, 4vw, 2.6rem)`, weight `900`.

### Timeline

Two-column grid (110px year + 1fr content). Content card `border-left: 4px solid
#590FA9`. Year label: `font-weight: 900`, `color: #590FA9`.

### Leaders grid

`auto-fill, minmax(160px, 1fr)`. Circular photo 120×120, `border-radius: 50%`.
Section badge: `background: #003982`, `border-radius: 0px`.

### Gallery

`auto-fill, minmax(220px, 1fr)`, item `height: 200px`, `border-radius: 6px`.
Hover: `transform: scale(1.04)`.

### Video embed

16:9 ratio (`padding-bottom: 56.25%`), `border-radius: 8px`, `background: #000`.

### Tabs (`.db-tabs`)

White container, `border-radius: 10px`, `border: 1px solid #CCCCCC`. Tab list:
`background: #F1F1F1`. Active tab: `background: #590FA9`, `color: #fff`,
`border-radius: 0px`.

### Dark / gradient sections

Split-CTA, Newsletter, Promo Banner, Popup: `border-radius: 22px`, `padding: 28px`.
Default: `linear-gradient(135deg, #0f172a 0%, #1f2f55 100%)`.
Purple: `linear-gradient(135deg, #590FA9 0%, #490499 100%)`.

### Download list

`background: #fff`, `border: 1px solid #CCCCCC`, `border-radius: 10px`,
`padding: 18px 20px`. Flex row, space-between.

### Steps grid

`auto-fit, minmax(210px, 1fr)`. Step number: `42×42px`, `background: #F1F1F1`,
`color: #590FA9`, `font-weight: 900`, `border-radius: 50%`.

### Site notice

Flex row, `padding: 12px 20px`, `font-size: 14px`.
- `white` — `background: #fff`, `border-bottom: 1px solid #CCCCCC`
- `dark` — `background: #590FA9`, `color: #fff`

Notice button: `background: #008A1C`, `border-radius: 0px`, `padding: 5px 14px`.

### Logo strip

Flex row, `gap: 24px`, `align-items: center`, `justify-content: center`.
Images: `max-width: 160px`, `max-height: 80px`, `object-fit: contain`.

---

## 12. Age Section Colours

The six Scout sections each have a distinct branded colour identity from Scouts UK.
Applied via `.head.{section}` in the age-section block. Theme-owned — do not
override in plugin CSS.

| Section | Age range | Colour |
|---|---|---|
| `squirrels` | 4–6 years | Red/orange `#ED3F23` |
| `beavers` | 6–8 years | Teal `#088486` |
| `cubs` | 8–10½ years | Green `#008A1C` |
| `scouts` | 10½–14 years | Dark green `#205B41` |
| `explorers` | 14–18 years | Navy `#003982` |
| `network` | 18–25 years | Purple `#590FA9` |

---

## 13. Motion & Transitions

| Element | Property | Duration | Easing |
|---|---|---|---|
| Links, buttons | `color`, `background`, `opacity` | `0.15s` | `ease` |
| Cards | `transform`, `box-shadow` | `0.15s` | `ease` |
| Gallery image | `transform` (scale hover) | `0.3s` | `ease` |
| FAQ chevron | `transform` (rotate) | `0.22s` | `ease` |
| Builder card hover | `border-color`, `box-shadow`, `transform` | `0.15s` | `ease` |
| Library switcher tab | all | `0.18s` | `ease` |
| Modal entrance | `opacity`, `transform` | `0.2s` | `cubic-bezier(.34,1.56,.64,1)` |
| Toast entrance | `opacity`, `transform` | `0.25s` | `ease` |
| Spinner | `transform` (rotate) | `0.7s` | `linear infinite` |

---

## 14. Z-index Layers

| Layer | Value | Element |
|---|---|---|
| Builder root | `99999` | `.db-app` |
| Builder modal | `999999` | `.db-modal-overlay` |
| Toast | `9999999` | `.db-toast` |

---

## 15. Design Rules

1. **Follow national brand.** All colours, button styles and typography follow
   scouts.org.uk exactly. Never introduce values not in §2.
2. **Purple leads brand sections.** `#590FA9` drives hero backgrounds, footer,
   key CTA sections and nav accents.
3. **Blue leads CTAs.** `#006DDF` is the primary interactive colour — links,
   primary buttons, donate. Not purple.
4. **Teal is news only.** `#088486` is reserved for the "What's Happening" /
   news section. Do not use it as a general accent.
5. **Pink is H1 on purple only.** `#FFB4E5` is used exclusively for H1 heading
   text on purple section backgrounds.
6. **Sharp corners on frontend buttons.** `border-radius: 0px` on all frontend
   buttons — this is the intentional national brand aesthetic.
7. **No new hex codes.** Only values from §2. Any new colour requires a design
   decision and an update to this file first.
8. **No new gradients.** Only the five in §3, always at 135°.
9. **Buttons belong to the theme.** Block CSS never sets button background,
   colour or border-radius for frontend buttons.
10. **White space is intentional.** Default section padding is 60–64px vertical.
    Do not compress sections without a clear layout reason.
11. **Focus ring always.** `0 0 0 3px rgba(89, 15, 169, 0.15)` — never use bare
    `outline` in custom CSS.
12. **PHP 7.0 compatibility.** No arrow functions, no spread in function calls
    in any `.php` file.
13. **Uppercase sparingly.** Only for caps labels (tags, eyebrows, section labels),
    always with `letter-spacing: 0.8px` and `font-weight: 700`.
14. **Images never distort.** Always `object-fit: cover` or `object-fit: contain`.
    Never rely on natural image sizing in grids.
