# FILE MAP — THEME SNAPSHOT

## Directory tree

```text
kmae/
├── style.css
├── functions.php
├── header.php
├── footer.php
├── front-page.php
├── page.php
├── index.php
├── template-parts/
│   ├── app-menu-panels.php
│   └── simple-mode/
│       ├── app-home.php
│       └── landing.php
└── assets/
    ├── js/
    │   └── theme.js
    ├── images/
    │   ├── .gitkeep
    │   ├── albira-asszisztens-kartya.webp
    │   ├── email-logo.png
    │   └── email-mascot.png
    └── css/
        ├── theme.css
        ├── browse.css
        ├── contact.css
        ├── faq.css
        ├── google-complete.css
        ├── home-cards.css
        ├── my-listings.css
        ├── listing-unavailable.css
        ├── messages.css
        ├── offer-thread.css
        ├── sale-contract.css
        ├── profile.css
        ├── settings.css
        ├── single-listing.css
        └── simple-mode/
            ├── app.css
            ├── app-shell-dark.css
            ├── header.css
            ├── landing.css
            ├── listing-create.css
            ├── listing-simple.css
            └── pc-dashboard.css
```

## PHP / JS files

### `functions.php` — 751 lines

**Purpose:** central theme bootstrap and routing/presentation orchestration.

**Contains:**
- constants: `KMAE_THEME_VERSION`, conditional `KMAE_VERSION`, `KMAE_DIR`, `KMAE_URI`, `KMAE_CSS_BASE`;
- theme support registration;
- plugin-page URL helper;
- page-type detectors;
- body-class filter;
- global and feature-specific asset enqueues;
- remote media/logo resolution;
- logged-in app menu model.

**Called by:** WordPress theme bootstrap; hooks registered in same file; helper calls from templates.

**Calls into plugin:** `KMAE_Pages`, `KMAE_Google_Auth`, `KMAE_Brand`, `KMAE_Seller`, `KMAE_Database`.

**Direct DB access:** none.

### `header.php` — 270 lines

**Purpose:** HTML document opening, common header, guest/logged-in navigation, mobile topbar, drawer shell, logged-in app-layout opening.

**Reads:**
- auth state;
- current WP user display name/login;
- `REQUEST_URI`;
- current listing author when on singular `kmae_listing`;
- plugin unseen-activity count if `KMAE_Database` exists.

**Calls:** `kmae_logo_img()`, `kmae_page_url()`, `KMAE_Icons::render()` if available, `get_template_part('template-parts/app-menu-panels')` for logged-in drawer.

**Outputs JS hooks:** `#site-header`, `#kmae-drawer`, `#kmae-drawer-backdrop`, `#kmae-drawer-open`, `#kmae-drawer-close`, `[data-kmae-dropdown]`, `[data-kmae-guest-nav]`.

### `footer.php` — 73 lines

**Purpose:** closes `<main>` and logged-in app layout, renders collapsible footer, renders logged-in mobile bottom nav, calls `wp_footer()`.

**Calls:** `kmae_page_url()` and `home_url()`.

**Outputs JS hooks:** `#site-footer`, `#kmae-footer-toggle`, `#kmae-footer-panel`, `.kmae-bottom-nav__item`.

### `front-page.php` — 16 lines

**Purpose:** authentication-based home switch.

```text
is_user_logged_in()
  ├─ true  → template-parts/simple-mode/app-home.php
  └─ false → template-parts/simple-mode/landing.php
```

Always wraps with `get_header()` / `get_footer()`.

### `page.php` — 100 lines

**Purpose:** shortcode-aware WordPress page wrapper.

First pass reads the first page post content + slug and sets four rendering flags:
- hub menu;
- listing create;
- my listings;
- raw-shortcode page.

Then rewinds and renders using one of four wrapper paths. Direct `do_shortcode()` is intentionally used for listing-create and raw-shortcode pages to bypass `the_content()` filter effects such as `wpautop`.

See `FEATURES/page-shortcode-rendering.md`.

### `index.php` — 29 lines

**Purpose:** fallback WordPress loop. Standard `the_content()` rendering in `.kmae-page > .kmae-container`.

### `template-parts/app-menu-panels.php` — 44 lines

**Purpose:** logged-in phone drawer menu.

**Input model:** `kmae_app_menu_groups()`.

**Plugin dependency:** `KMAE_Icons::render()` if available.

**Guard:** exits unless `ABSPATH` and logged-in user.

### `template-parts/simple-mode/app-home.php` — 34 lines

**Purpose:** bridge from theme front page to plugin dashboard.

**Path A:** if `KMAE_Dashboard` absent, render a simple “update plugin” fallback greeting.

**Path B:**
1. `$dash = KMAE_Dashboard::get_data(current_user_id)`;
2. direct include of `KMAE_PLUGIN_DIR . 'templates/partials/dashboard-home.php'`.

The included plugin template is `PLUGIN-OWNED / UNVERIFIED` here.

### `template-parts/simple-mode/landing.php`

**Purpose:** complete guest landing markup (rebuilt 2026-10-11, prefix `.kmae-lp-`).

**Sections:** hero + animated product demo → 3 price modes → 6 seller steps → buyer panel → trust tiles → final CTA. Style `assets/css/landing-home.css`, reveal `assets/js/landing-home.js`.

**Data sources:** `kmae_media_url( 'landing_car_*' )`; `kmae_page_url()` for browse/create/how-it-works URLs.

### `assets/js/theme.js` — 271 lines

**Purpose:** UI-only global theme JavaScript.

**Functions:** scroll state, drawer state, desktop dropdowns, footer collapse, optional sheet tabs, bottom-nav current-path class, guest nav active state, YouTube no-cookie lightbox.

**Network/API behavior:** only assigns a YouTube iframe URL; no `fetch`, XHR, REST, AJAX, storage APIs.

## CSS files

### `assets/css/theme.css` — 2028 lines

Global base/theme stylesheet. Contains shared marketing/app primitives, header/drawer/footer/bottom-nav/page/auth/button/notice and some legacy/generic wizard/browse selectors. Loaded for all visitors after Google fonts.

### `assets/css/simple-mode/landing.css`

Guest visual system, header, auth/login/register form styling, marketing footer (landing sections live in `landing-home.css`). Loaded for all logged-out visitors, not only front page.

### `assets/css/simple-mode/app.css` — 1294 lines

Logged-in application base, app layout/sidebar/dashboard/hub structures and mobile bottom-nav interactions. Loaded for all logged-in visitors.

### `assets/css/simple-mode/pc-dashboard.css` — 870 lines

Logged-in PC dashboard and sidebar/info rail styling. Loaded for all logged-in visitors after `app.css`.

### `assets/css/simple-mode/app-shell-dark.css` — 415 lines

Late logged-in dark shell/canvas overrides. Loaded at enqueue priority 99 after `pc-dashboard.css`.

### `assets/css/simple-mode/header.css` — 651 lines

Late logged-in header/topbar styling; depends on `app-shell-dark`. Contains desktop and <=1024 behavior (no header background image).

### `assets/css/home-cards.css` — 441 lines

Logged-in home/dashboard card/greeting visuals. Loaded for all logged-in pages, although most selectors appear to target dashboard/plugin markup.

### `assets/css/simple-mode/listing-create.css` — 5127 lines

Largest stylesheet. Presentation contract for listing-create/wizard DOM: stage/steps, cards, progress road, validation feedback, photo workflow, catalog selection, mileage/year controls, assistant, extras, sales modes, editing variants, responsive behavior. Markup/behavior generation is not in this theme snapshot.

### `assets/css/browse.css` — 637 lines

Browse/search page presentation: wizard/map/county/mode cards/result/swipe-style listing selectors. Loaded when `kmae_is_browse_page()` is true.

### `assets/css/single-listing.css` — 1117 lines

Single `kmae_listing` presentation: gallery, vehicle metadata, price/offer area, verification gate, trust/pledge, tabs, action buttons. Theme does not render the listing body in this archive. End of file: bejelentés gomb + `dialog.kmae-report` ablak (`.kmae-report*`).

### `assets/css/my-listings.css` — 333 lines

“My listings” cards, draft progress, actions, delete-confirm presentation. CSS comment references `my-listings.js`, but no such JS exists in theme snapshot.

### `assets/css/messages.css`

Üzenetek conversation list (`.kmae-inbox*`), loaded on the `uzenetek` page without `?kmae_conversation`. Törölt hirdetés sora: `.kmae-inbox__gone` címke.

### `assets/css/listing-unavailable.css`

Lejárt hirdetés linkje a nem-tulajdonosnak: „Ez az autó már nem elérhető” panel + hasonló autók rács. Enqueue: `kmae_enqueue_listing_unavailable()` (vendégnek is).

### `assets/css/offer-thread.css`

Chat thread presentation (single source, one column): listing header, role bubbles (buyer blue left / seller orange right), offer bubble states + [Elfogadom] + vevő [Visszavonom] (`.kmae-chat-offer-retract`) + system notice + accepted-offer strip (`.kmae-chat-deal`) + törölt hirdetés sáv (`.kmae-chat-deleted`), composer, lightbox, jobb oszlop `.kmae-chat-side` (állapot, Foglalt kártya + szerződés-link/[Lezárom] (eladó), ellenőrzőlista + „Mire figyelj” kép és `.kmae-tips-viewer` nagyító (vevő)). Loaded on `uzenetek` + `?kmae_conversation`. End of file: fejléc „Bejelentés” gomb + `dialog.kmae-report` ablak (`.kmae-report*`).

### `assets/css/sale-contract.css`

Adásvételi szerződés aloldal (`?kmae_conversation=ID&kmae_contract=1`, body `kmae-sale-contract-active`): toolbar + papír űrlap + `@media print` (csak a papír). Ezen az oldalon az offer-thread.css NEM töltődik.

### `assets/css/profile.css` — 958 lines

Desktop profile page presentation. Plugin owns the profile shortcode markup.

### `assets/css/settings.css` — 753 lines

Desktop settings page presentation. Plugin owns settings markup/behavior.

### `assets/css/google-complete.css` — 430 lines

Desktop Google account-completion screen override. Enqueued at priority 101 and declared dependent on plugin style handle `kmae-public` and theme `kmae-header`.

### `assets/css/how-it-works.css` — 484 lines

Desktop how-it-works page plus `.kmae-video-lightbox` presentation. Markup likely comes from shortcode/plugin; theme JS owns lightbox behavior if expected hooks exist.

### `assets/css/faq.css` — 314 lines

Desktop FAQ page visual layer.

### `assets/css/contact.css` — 235 lines

Desktop contact page visual layer.

## Image files in archive

- `assets/images/albira-asszisztens-kartya.webp`
- `assets/images/email-logo.png`
- `assets/images/email-mascot.png`

No in-theme reference to these three files was found in PHP/JS/CSS. Status: `UNREFERENCED-IN-THEME` (they may be consumed externally or by plugin code; not verified).

## V2 READ POLICY

This inventory is **not** a default context pack.

Default source loading is limited to the files/symbols selected by:
`DIAGNOSTIC-ROUTER.md` → one feature map → 1–5 source targets.

Open `FILE-MAP.md` only when the first route cannot resolve ownership/location or when validating inventory/snapshot drift.
