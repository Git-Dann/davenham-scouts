# Davenham Scout Group Website — Claude Code Guide

> **New session?** Read this file before doing anything.

---

## 1. Project Overview

WordPress website for **Davenham Scout Group** at `https://davenhamscouts.org.uk`. Built with two custom plugins:
- **davenham-builder** v1.4.0 — visual page builder + 33 custom Gutenberg blocks
- **davenham-admin-suite** v1.1.2 — white-label admin customisation and editorial polish

**Hosting:** Krystal shared hosting (PHP 7.3 server, code written for PHP 7.0+ compatibility)  
**WP Admin:** `https://davenhamscouts.org.uk/wp-admin`  
**No SSH access** — all installs are via WP Admin → Plugins → Add New → Upload Plugin

---

## 2. Repo & Source

**No git repository.** Source files live in this directory.

| Location | Contents |
|---|---|
| `~/Desktop/Scouting-Website/plugins/davenham-builder/` | Plugin source — canonical source of truth |
| `~/Desktop/Scouting-Website/plugins/davenham-admin-suite/` | Admin suite source |
| `~/Desktop/davenham-builder.zip` | Last built zip (v1.2.0 — older than source) |
| `~/Desktop/Scouting-Website/website-core/` | Mirror of live site content + Python tooling |

**The zip on the Desktop is stale.** The source in `plugins/davenham-builder/` is at v1.4.0.

---

## 3. File Structure

```
plugins/
  davenham-builder/
    davenham-builder.php          ← Plugin bootstrap (v1.4.0), shared helpers
    src/
      index.js                    ← Gutenberg editor registrations for all blocks
    assets/
      builder.js                  ← Visual builder admin UI (React, no JSX, no build step)
      builder.css                 ← Visual builder admin styles
      blocks.css                  ← Frontend CSS for block-specific elements
    blocks/                       ← 33 blocks, each with block.json + render.php
      age-section/
      card-grid/
      contact-info/
      cta-button-row/
      donation-cards/
      downloads-list/
      events-list/
      faq/
      gallery/
      hero/
      icon-feature-row/
      key-facts/
      leaders/
      logo-strip/
      news-feed/
      newsletter-signup/
      page-hero/
      popup-promo/
      promo-banner/
      quote-banner/
      ... (33 total)

  davenham-admin-suite/
    davenham-admin-suite.php      ← Plugin bootstrap (v1.1.2)
    assets/                       ← Admin styles

website-core/                     ← Live site mirror + build tooling
  mirror/                         ← Mirror of live site files
  scripts/                        ← Utility scripts
  docs/                           ← Documentation
  *.py                            ← Python tooling (sitemap, word docs, image organiser)
  backup.sh                       ← Backup script
```

---

## 4. Block Architecture

Each block in `blocks/{name}/` has:
- `block.json` — block registration metadata (name, attributes, title)
- `render.php` — server-side PHP render function

**All blocks are server-side rendered.** No client-side React in the frontend.

The Gutenberg editor uses `src/index.js` to register all blocks using `wp.*` globals — no build step, no webpack, no Node.

---

## 5. Visual Builder

`assets/builder.js` is the admin page builder — a React app using `wp.element` (WordPress's React wrapper). **No JSX, no build step.** It writes standard WP block comment markup to pages via the `/wp/v2/pages` REST endpoint (standard WP endpoint — no custom REST routes, as they caused fatal errors on Krystal hosting).

---

## 6. Key Constraints (MUST READ)

| Constraint | Why |
|---|---|
| PHP 7.0+ compatibility | Krystal hosting runs PHP 7.3. **No arrow functions (`=>`), no `[...$arr]` spread in function calls** |
| No custom REST routes | They caused fatal errors on Krystal shared hosting. Use `/wp/v2/pages` only |
| No build step | Vanilla `wp.*` globals only. No Node, webpack, or transpilation |
| No SSH | Install via WP Admin upload only |
| Krystal shared hosting | Memory limits apply. Keep PHP lightweight |
| **Design system** | **All design decisions — colours, spacing, typography, components — must follow `design.md`. Never introduce a colour hex, border-radius, shadow, or gradient not already listed there.** |

---

## 7. Design System

**All design work in this project is governed by [`design.md`](design.md).** That file is the single source of truth for:

- Colour palette (hex values, tokens, and usage)
- Gradients
- Typography (font, weights, sizes, line-heights)
- Spacing scale
- Border radius scale
- Shadow definitions
- Component patterns (cards, heroes, buttons, tabs, accordions, etc.)
- Breakpoints and responsive rules
- Motion / transitions
- Design rules and constraints

**Rules:**
- Never write a colour hex that is not in `design.md §1`.
- Never create a gradient not in `design.md §2`.
- Never use an arbitrary border-radius — match the scale in `design.md §5`.
- Never define button colours in block CSS — the theme owns buttons.
- When adding a new block, add its section-stripe colour to `design.md §1` (Section stripe colours table).
- When any design token changes, update `design.md` first, then apply it.

---

## 8. Deployment Process

Since there's no CI/CD, the deploy process is manual:

1. Make edits to `plugins/davenham-builder/` or `plugins/davenham-admin-suite/`
2. Update the version number in the plugin header comment (e.g. `Version: 1.4.1`)
3. Zip the plugin folder: `zip -r davenham-builder.zip davenham-builder/`
4. Go to WP Admin → Plugins → Add New → Upload Plugin
5. Upload the zip
6. If updating: Deactivate + Delete the old version first, then upload and activate new

**Never activate both old and new version simultaneously.**

---

## 9. davenham-admin-suite Plugin

Handles:
- White-label WP admin (removes clutter, rebrands admin for non-technical editors)
- Menu cleanup (hides unused WP admin menu items)
- Editorial polish (custom columns, notices, etc.)

Version: **1.1.2**

---

## 10. Python Tooling (website-core/)

| Script | Purpose |
|---|---|
| `build_mirror_inventory.py` | Build an inventory of the mirrored live site |
| `build_rebuild_payload.py` | Generate a rebuild payload |
| `create_word_docs.py` | Generate Word docs from site content |
| `extract_sitemap_urls.py` | Pull all URLs from the sitemap |
| `generate_index.py` | Generate a content index |
| `organise_images.py` | Sort/organise image assets |
| `backup.sh` | Shell backup script |

---

## 11. What's Done

- 33 custom Gutenberg blocks, all server-side rendered
- Visual page builder admin UI (no-code page editing for scouts staff)
- davenham-admin-suite for admin white-labelling
- Stable on Krystal PHP 7.3 shared hosting
- No custom REST routes (compatibility fix)

---

## 12. What's Next

- **Create a git repo** for this project — currently no version control
- Build fresh zips from current v1.4.0 source (desktop zip is stale at v1.2.0)
- Any future blocks: add to `blocks/` following the existing `block.json` + `render.php` pattern
- Keep PHP 7.0 compatible — no modern PHP features
