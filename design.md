# Davenham Scout Group — Design System

> **Authoritative reference.** All colours, typography, spacing, components and patterns in this project must follow this file. Do not introduce any value not listed here without updating this file first.
>
> Source: Scouts UK brand (scouts.org.uk) as implemented in `plugins/davenham-builder/assets/blocks.css` and `builder.css`.

---

## 1. Colour Palette

### Brand colours

| Token | Hex | Usage |
|---|---|---|
| `purple` | `#7413dc` | Primary brand colour — links, active states, focus rings, stat values, timeline accents, primary buttons |
| `navy` | `#003f87` | Secondary — hero sections, FAQ, contact, gradient start, dark panels |
| `navy-alt` | `#003982` | Gradient variant (used interchangeably with `navy` in gradients) |
| `teal` | `#00a794` | Tertiary — sponsors, gallery, site-notice banners |
| `green` | `#37a03c` | Success / CTA — save buttons, notice buttons, welcome section, toggle-on state |
| `red` | `#e22b1a` | Destructive / alert — errors, delete actions, news feed, video, icon-feature-row |
| `yellow` | `#ffbe00` | Events section accent |

### Tints & UI surfaces

| Token | Hex | Usage |
|---|---|---|
| `purple-tint` | `#f3eaff` | Hover backgrounds, icon badges, step number circles |
| `purple-hover` | `#faf4ff` | Card hover background, add-section hover |
| `purple-subtle` | `#f6f0ff` | Image upload buttons, library switcher bg |
| `dark-bg` | `#0f172a` | Dark section backgrounds |
| `dark-bg-alt` | `#1f2f55` | Dark gradient end |

### Text

| Token | Hex | Usage |
|---|---|---|
| `text-primary` | `#222` | Headings, card names |
| `text-base` | `#333` | Body copy |
| `text-dark` | `#1f2937` | Popup content, modal body |
| `text-secondary` | `#5b6470` | Card detail text, muted body |
| `text-muted` | `#6b7280` | Labels, meta, captions |
| `text-subtle` | `#888` | Gallery captions, video captions, placeholder |
| `text-faint` | `#999` | Deemphasised labels |
| `text-disabled` | `#aaa` | Empty states |

### Borders & backgrounds

| Token | Hex | Usage |
|---|---|---|
| `border` | `#e5e7eb` | Card borders, tab borders, download items |
| `border-light` | `#eee` | Dividers, photo borders |
| `border-muted` | `#ddd` | Input borders, sidebar border, canvas empty |
| `bg-canvas` | `#f0f1f2` | Builder canvas background |
| `bg-surface` | `#f8fafc` | Tab list background |
| `bg-light` | `#fafafa` | Advanced panels, input background |
| `white` | `#fff` | Cards, modals, inputs |

### Section stripe colours (visual builder only)

These are decorative 3px top-stripes on builder canvas cards — not used in frontend output.

| Block | Colour |
|---|---|
| `davenham/hero`, `davenham/page-hero`, `davenham/faq`, `davenham/contact-info` | `#003f87` |
| `davenham/site-notice`, `davenham/sponsors`, `davenham/gallery` | `#00a794` |
| `davenham/welcome-section`, `davenham/cta-button-row` | `#37a03c` |
| `davenham/leaders`, `davenham/age-section` | `#7413dc` |
| `davenham/news-feed`, `davenham/icon-feature-row`, `davenham/video-embed` | `#e22b1a` |
| `davenham/events-list` | `#ffbe00` |
| `davenham/text-image`, `davenham/rich-text` | `#555` / `#888` |

---

## 2. Gradients

Always use 135° angle. Never invent new gradients.

| Name | Value | Usage |
|---|---|---|
| Brand | `linear-gradient(135deg, #003f87 0%, #7413dc 100%)` | Top bar, modal headers, quote banners |
| Purple | `linear-gradient(135deg, #7413dc 0%, #003982 100%)` | Promo banners, split CTA alt card |
| Dark | `linear-gradient(135deg, #0f172a 0%, #1f2f55 100%)` | Split CTA default, newsletter, popup promo |
| Purple UI | `linear-gradient(135deg, #7413dc 0%, #5b2ee6 100%)` | Active tab in library switcher (admin UI only) |
| Purple card tint | `linear-gradient(180deg, #fff 0%, #faf7ff 100%)` | Guide & preset list backgrounds (admin UI only) |

---

## 3. Typography

### Font stack

The frontend inherits from the active WordPress theme. The Scouts UK brand uses **Nunito** (Google Fonts). All admin UI falls back to the system stack.

```css
/* Frontend (theme-provided) */
font-family: 'Nunito', sans-serif;

/* Admin UI only */
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
```

### Font weights

| Weight | Value | Usage |
|---|---|---|
| Regular | `400` | Body copy |
| Medium | `500` | Toggle labels |
| Semi-bold | `600` | Buttons, notice links, field labels in builder |
| Bold | `700` | Sub-headings, card titles, nav, meta labels |
| Extra-bold | `800` | Stat values, quote banner text, timeline years |

### Font sizes

| Role | Size | Notes |
|---|---|---|
| Caps label | `11px` | Uppercase + `letter-spacing: 0.5–0.8px` |
| Small / meta | `12px` | Builder sidebar descriptions |
| Secondary | `13px` | Builder body text, buttons |
| Body small | `14px` | Icon text, toast, section names |
| Body | `15px` | FAQ answers, leader role, secondary copy |
| Body base | `16px` | FAQ question |
| Sub-heading | `22px` | CTA headings, empty state h3 |
| Fluid stat | `clamp(2rem, 4vw, 3rem)` | Stats grid values |
| Fluid quote | `clamp(1.6rem, 4vw, 2.6rem)` | Quote banner text |
| Fluid donation | `clamp(1.5rem, 3vw, 2.2rem)` | Donation card title |

### Line heights

| Usage | Value |
|---|---|
| Headings / display | `1.15` |
| Tight UI | `1.35` |
| Buttons / tight body | `1.5` |
| Icon text | `1.55` |
| Body copy | `1.6` |
| Long-form / answers | `1.65` |
| Testimonial quotes | `1.75` |

### Text transforms

Uppercase is used exclusively for caps labels: field labels, category headers, eyebrows, section tags. Always pair with `letter-spacing: 0.5px` or `0.8px`.

---

## 4. Spacing

The spacing scale is not strictly a multiplier — use these exact values:

| Size | Value | Common usage |
|---|---|---|
| 2xs | `3px` | Tag padding, chevron offsets |
| xs | `4–6px` | Inline gaps, badge padding |
| s | `8–10px` | Button padding (vertical), small gaps |
| m | `12–14px` | Icon gap, field margin, card header padding |
| l | `16–18px` | Button padding (horizontal), FAQ question padding, tab padding |
| xl | `20–24px` | Card padding (inner), grid gap |
| 2xl | `28–32px` | Leaders gap, promo padding |
| 3xl | `36–40px` | Video section, quote banner padding |
| 4xl | `64px` | Default block shell vertical padding (`--db-padding-top/bottom`) |

### Container

```css
width: min(calc(100% - 32px), 1180px);
max-width: 1180px;
margin-left: auto;
margin-right: auto;
```

The `32px` gutter (16px each side) applies at all viewport widths.

---

## 5. Border Radius

| Size | Value | Usage |
|---|---|---|
| xs | `3–4px` | Dismiss button, notice button, small badges |
| s | `6px` | Inputs, select fields, builder buttons |
| m | `8px` | Video embed, contact map, gallery items, toast |
| l | `10–12px` | Section cards (builder), modals, tabs container |
| xl | `14px` | Library cards, guide panels |
| 2xl | `16px` | Timeline content, download items |
| 3xl | `18px` | Stats/testimonial/card-grid/step cards |
| 4xl | `20–22px` | Tabs container, popup, promo cards, split-cta |
| 5xl | `24px` | Quote banner |
| pill | `999px` | Leader section badge, toggle, tab pills, step number |
| circle | `50%` | Leader photo, toggle knob |

---

## 6. Shadows

| Name | Value | Usage |
|---|---|---|
| Card | `0 8px 24px rgba(15, 23, 42, 0.06)` | Stats, testimonial, card-grid, donation, key-facts cards |
| Card hover | `0 4px 12px rgba(116, 19, 220, 0.08)` | Preset card hover |
| Card hover strong | `0 4px 12px rgba(116, 19, 220, 0.12)` | Library card hover |
| Selected | `0 4px 16px rgba(116, 19, 220, 0.18)` | Builder section selected state |
| Timeline | `0 6px 20px rgba(15, 23, 42, 0.05)` | Timeline content cards |
| Section hover | `0 3px 12px rgba(116, 19, 220, 0.10)` | Builder section hover |
| Focus ring | `0 0 0 3px rgba(116, 19, 220, 0.08)` | Input focus, search focus |
| Topbar | `0 2px 8px rgba(0, 0, 0, 0.35)` | Builder top bar |
| Modal | `0 20px 60px rgba(0, 0, 0, 0.3)` | Modal overlay |
| Library active | `0 10px 20px rgba(116, 19, 220, 0.22)` | Active library switcher tab |

---

## 7. Breakpoints

| Name | Max-width | Notes |
|---|---|---|
| Mobile | `480px` | 2-col gallery, 2-col leaders grid |
| Tablet | `700px` | Single-col leaders (minmax 140px), gallery item height 160px |
| Desktop small | `768px` | Timeline and split-CTA collapse to single col |
| Desktop | `900px` | Contact map stacks below contact details |

---

## 8. Components

### Block shell

All blocks that render full-width sections use `.db-block-shell` with CSS custom properties for per-instance overrides:

```css
/* Properties that can be set per-block */
--db-bg-color       /* background colour */
--db-text-color     /* text colour */
--db-heading-color  /* heading colour override */
--db-link-color     /* link / button colour override */
--db-text-align     /* text alignment */
--db-padding-top    /* default: 64px */
--db-padding-bottom /* default: 64px */
--db-max-width      /* default: 1180px */
--db-min-width      /* default: 0px */
```

### Cards

Standard card treatment:

```css
background: #fff;
border: 1px solid #e5e7eb;
border-radius: 18px;
padding: 24px;
box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
```

Used by: stats-grid, testimonial-grid, card-grid, donation-cards, key-facts, step-cards.

### Card with image

Image bleeds to the top edge, negative-margin technique:

```css
.card_grid__image {
  margin: -24px -24px 18px;
  overflow: hidden;
  border-radius: 18px 18px 0 0;
}
.card_grid__image img {
  width: 100%;
  height: 210px;
  object-fit: cover;
}
```

### Buttons

Button classes come from the active theme. The builder writes `class="btn {style}"` where `style` is one of the theme's button variants (e.g. `outline`, `filled`, `ghost`). Do not define button colours in block CSS — the theme owns them.

Exception: admin UI uses `.db-btn` variants (ghost, preview, save) defined in `builder.css`.

### Hero (`.hero`)

Full-width section with background image. Background image is a `<img class="bg">` tag (positioned absolutely by the theme). Content sits in `.wrapper > .box > .wrap`. Buttons in `.btn_row`.

### Page Hero (`.hero.standard`)

Inner-page banner. No background image. Uses `.wrapper.alt > .inner`. H2 heading + optional `<p>` subtext.

### FAQ accordion

- Container: `.faq_list`
- Item: `.faq_item` + `.faq_item--open` (JS-toggled)
- Question button: `.faq_question` — hover/open colour `#7413dc`
- Chevron: `.faq_chevron` — CSS-only arrow, rotates on open
- Answer: `.faq_answer` — `font-size: 15px`, `line-height: 1.65`, `color: #555`

### Quote banner

Gradient background (`#003982 → #7413dc`), white text, `border-radius: 24px`. Heading is `clamp(1.6rem, 4vw, 2.6rem)`, weight `800`.

### Timeline

Two-column grid (110px year + 1fr content). Content card has `border-left: 4px solid #7413dc`. Year label: `font-weight: 800`, `color: #7413dc`.

### Leaders grid

`auto-fill, minmax(160px, 1fr)`. Circular photo (120×120, `border-radius: 50%`). Section badge: `background: #003f87`, pill shape.

### Gallery

`auto-fill, minmax(220px, 1fr)`, item `height: 200px`, `border-radius: 6px`. Hover: `transform: scale(1.04)`.

### Video embed

16:9 ratio (`padding-bottom: 56.25%`), `border-radius: 8px`, `background: #000`.

### Tabs (`.db-tabs`)

White container, `border-radius: 20px`, `border: 1px solid #e5e7eb`. Tab list has `background: #f8fafc`. Active tab: `background: #7413dc`, `color: #fff`, `border-color: #7413dc`, `border-radius: 999px`.

### Dark / gradient sections

Split-CTA, Newsletter Signup, Promo Banner, Popup Promo all use `border-radius: 22px`, `padding: 28px`. Default dark variant: `linear-gradient(135deg, #0f172a 0%, #1f2f55 100%)`. Purple variant: `linear-gradient(135deg, #7413dc 0%, #003982 100%)`.

### Download list items

`background: #fff`, `border: 1px solid #e5e7eb`, `border-radius: 16px`, `padding: 18px 20px`. Flex row with space-between.

### Steps grid

`auto-fit, minmax(210px, 1fr)`. Step number circle: `42×42px`, `background: #f3eaff`, `color: #7413dc`, `font-weight: 800`.

### Site notice

Flex row, `padding: 12px 20px`, `font-size: 14px`. Two variants:
- `white` — `background: #fff`, `border-bottom: 1px solid #eee`
- `dark` — `background: #003f87`, `color: #fff`

Notice button: `background: #37a03c`, `border-radius: 4px`, `padding: 5px 14px`.

### Logo strip

Flex row, `gap: 24px`, `align-items: center`, `justify-content: center`. Images: `max-width: 160px`, `max-height: 80px`, `object-fit: contain`.

---

## 9. Age Section colours

The six Scout sections each have a distinct branded colour identity (from the Scouts UK brand). These classes are applied to `.head.{section}` in the age-section block and styled by the theme:

| Section | Age range | Colour identity |
|---|---|---|
| `squirrels` | 4–6 years | Russet red |
| `beavers` | 6–8 years | Beaver orange/brown |
| `cubs` | 8–10½ years | Golden yellow |
| `scouts` | 10½–14 years | Scout green |
| `explorers` | 14–18 years | Navy blue |
| `network` | 18–25 years | Scout purple |

These colours are theme-owned — do not override them in plugin CSS.

---

## 10. Motion & Transitions

| Element | Property | Duration | Easing |
|---|---|---|---|
| Links, buttons | `color`, `background`, `opacity` | `0.15s` | `ease` |
| Gallery image | `transform` (scale hover) | `0.3s` | `ease` |
| FAQ chevron | `transform` (rotate) | `0.22s` | `ease` |
| Builder card hover | `border-color`, `box-shadow`, `transform` | `0.15s` | `ease` |
| Library switcher tab | all | `0.18s` | `ease` |
| Modal entrance | `opacity`, `transform` | `0.2s` | `cubic-bezier(.34,1.56,.64,1)` |
| Toast entrance | `opacity`, `transform` | `0.25s` | `ease` |
| Spinner | `transform` (rotate) | `0.7s` | `linear infinite` |

---

## 11. Z-index layers

| Layer | Value | Element |
|---|---|---|
| Builder root | `99999` | `.db-app` |
| Builder modal | `999999` | `.db-modal-overlay` |
| Toast | `9999999` | `.db-toast` |

---

## 12. Design rules

1. **Colours:** Only use hex values defined in §1. Never introduce new hex codes without adding them to this file.
2. **Gradients:** Only use the five gradients in §2 at 135°.
3. **Fonts:** Use Nunito on the frontend (theme provides it). Never import fonts in plugin CSS.
4. **Border radius:** Match the scale in §5. Never use arbitrary values like `5px`, `7px`, `15px`.
5. **Buttons:** Theme owns button styles. Block CSS must not define button background/colour rules.
6. **PHP 7.0 compatibility:** No arrow functions, no spread in function calls — see CLAUDE.md §6.
7. **Dark sections:** Use the dark or purple gradient only. Never use solid dark colours for full-width section backgrounds.
8. **Purple tint hover:** Hover states on interactive elements in purple context always use `#faf4ff` background and `#7413dc` text/border.
9. **Focus ring:** Always `0 0 0 3px rgba(116, 19, 220, 0.08)` — never use `outline` in custom CSS.
10. **Spacing:** Use the exact values in §4. Do not interpolate (e.g. do not use `22px` if `24px` is the scale value).
