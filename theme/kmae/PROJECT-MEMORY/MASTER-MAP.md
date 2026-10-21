# MASTER MAP — THEME ONLY

> Read this file first. This is the token-efficient entry point for the Albira **theme** snapshot. Plugin internals are intentionally excluded from this phase.

## 1. Architecture in one screen

This is a classic WordPress theme with a small procedural PHP layer and a large presentation layer.

```text
WordPress request
  ↓
functions.php
  ├─ registers theme support
  ├─ detects page/feature from WP query + slug + shortcode text
  ├─ adds body classes
  ├─ enqueues global / logged-in / guest / feature CSS
  └─ exposes navigation / logo / media helpers
  ↓
WordPress template selection
  ├─ front-page.php → guest `[kmae_browse]` (app shell, 2026-10-11) OR logged-in dashboard bridge
  ├─ page.php       → shortcode-aware page wrapper
  └─ index.php      → fallback content wrapper
  ↓
header.php + page/template content + footer.php
  ↓
assets/js/theme.js
  └─ UI-only browser behavior: header state, drawer, dropdown, footer,
     guest-nav active state, optional sheet tabs, YouTube lightbox
```

The theme **does not define its own PHP classes**. It defines procedural helper functions in `functions.php`.

The theme contains **no verified AJAX handler, REST route, cron/background job, direct `$wpdb` call, SQL write, `fetch()`, XHR, localStorage, or sessionStorage**.

The theme reads WordPress request/user/post state directly and calls several plugin APIs conditionally. Those plugin implementations are not part of this map.

## 2. Primary entry points

| Entry | Role | Read first when |
|---|---|---|
| `functions.php` | global theme bootstrap, detection, body classes, enqueue graph, helper APIs | any route/style/navigation/theme integration problem |
| `header.php` | guest/logged-in header, desktop nav, mobile top bar, drawer shell, app-layout opening | header/nav/badge/drawer/layout problem |
| `footer.php` | closes app layout, collapsible footer, logged-in bottom nav | footer/mobile bottom-nav/layout-closing problem |
| `front-page.php` | switches front page by auth state | home/landing/dashboard problem |
| `page.php` | detects shortcode-page rendering mode | plugin shortcode page wrapper/spacing/wpautop problem |
| `index.php` | WordPress fallback template | unexpected generic fallback rendering |
| `assets/js/theme.js` | global theme UI behavior | drawer/dropdown/footer/lightbox/active-state problem |

## 3. Feature index

| Feature | Feature map | Primary theme files |
|---|---|---|
| Theme bootstrap + asset orchestration | `FEATURES/theme-bootstrap-assets.md` | `functions.php`, `style.css` |
| Route/page detection + body classes | `FEATURES/route-detection-body-classes.md` | `functions.php` |
| Front-page auth split | `FEATURES/front-page-routing.md` | `front-page.php` |
| Logged-in app shell + navigation | `FEATURES/logged-in-app-shell-navigation.md` | `header.php`, `footer.php`, `functions.php`, `template-parts/app-menu-panels.php`, `theme.js` |
| Guest header + marketing shell | `FEATURES/guest-marketing-shell.md` | `header.php`, `footer.php`, `theme.js`, `landing.css` |
| Landing page | `FEATURES/landing-page.md` | `template-parts/simple-mode/landing.php`, `landing.css`, `functions.php` |
| Generic shortcode/page rendering | `FEATURES/page-shortcode-rendering.md` | `page.php`, `functions.php` |
| Logged-in dashboard bridge | `FEATURES/dashboard-home-bridge.md` | `front-page.php`, `template-parts/simple-mode/app-home.php`, `app.css`, `pc-dashboard.css`, `home-cards.css` |
| Listing-create presentation | `FEATURES/listing-create-presentation.md` | `functions.php`, `page.php`, `listing-create.css` |
| Browse/search presentation | `FEATURES/browse-presentation.md` | `functions.php`, `browse.css` |
| Single-listing presentation | `FEATURES/single-listing-presentation.md` | `functions.php`, `header.php`, `single-listing.css` |
| My listings presentation | `FEATURES/my-listings-presentation.md` | `functions.php`, `page.php`, `my-listings.css` |
| Chat / Üzenetek presentation | `FEATURES/offers-chat-presentation.md` | `functions.php`, `header.php`, `offer-thread.css`, `messages.css` |
| Profile presentation | `FEATURES/profile-presentation.md` | `functions.php`, `page.php`, `profile.css` |
| Settings presentation | `FEATURES/settings-presentation.md` | `functions.php`, `page.php`, `settings.css` |
| Google-auth completion presentation | `FEATURES/google-complete-presentation.md` | `functions.php`, `page.php`, `google-complete.css` |
| FAQ + contact presentation | `FEATURES/faq-contact.md` | `functions.php`, `faq.css`, `contact.css` |
| Branding + remote media | `FEATURES/branding-media.md` | `functions.php`, CSS remote URLs |

## 4. Highest-value system connections

### Theme → plugin API boundary

The theme conditionally references these external/plugin symbols:

- `KMAE_Pages::url()` — canonical page URLs.
- `KMAE_Google_Auth::needs_completion()` — Google account completion gate.
- `KMAE_Brand::logo_guest()`, `logo_app()`, `logo_url()` — brand logo provider.
- `KMAE_Seller::count_active_cycle_listings()` — menu badge source.
- `KMAE_Database::count_unread_conversations()` — „Üzenetek” unread chat badge source.
- `KMAE_Icons::render()` — desktop/menu icon renderer.
- `KMAE_Dashboard::get_data()` — logged-in dashboard data.
- `KMAE_PLUGIN_DIR` — used to include `templates/partials/dashboard-home.php` directly.
- stylesheet handle `kmae-public` — expected as a dependency by Google-complete CSS enqueue.

Implementations: `PLUGIN-OWNED / UNVERIFIED` in this phase.

### Theme → plugin shortcode boundary

Theme page detection or rendering explicitly knows these shortcode names:

`kmae_create_listing`, `kmae_browse`, `kmae_my_listings`, `kmae_how_it_works`, `kmae_faq`, `kmae_contact`, `kmae_profile`, `kmae_register`, `kmae_settings`, `kmae_login`, `kmae_hub_sell`, `kmae_hub_buy`, `kmae_hub_stats`.

The theme does not register any of them.

### CSS cascade boundary

Global layers:

```text
kmae-fonts
  ↓
kmae-theme (assets/css/theme.css)
  ↓
GUEST: kmae-landing (simple-mode/landing.css)
OR
LOGGED-IN: kmae-app → kmae-pc-dashboard
                         ↓
                 kmae-app-shell-dark
                         ↓
                    kmae-header
                         ↓
                  kmae-home-cards
```

Feature CSS then loads conditionally. See `CSS-MAP.md` for the route matrix.

## 5. Data ownership summary

**Theme direct reads:** auth state, current user name/id, request URI, current page slug/content, listing post author, query state, two query-string flags.

**Theme direct writes:** none verified.

**Database reads/writes:** no direct database access. Two values called from plugin methods (`active listings`, `unread chat count`) likely originate from plugin data, but storage/queries are `UNVERIFIED` until plugin mapping.

## 6. Fast workflow for a future coding agent

```text
Problem arrives
  ↓
MASTER-MAP.md
  ↓
Choose one FEATURE file
  ↓
If visual/cascade issue → CSS-MAP.md
If function/caller issue → SYMBOL-MAP.md
If plugin boundary/data issue → DATA-MAP.md + DEPENDENCY-MAP.md
If request-flow issue → FLOWS.md
  ↓
Load only the 2–8 source files/symbols listed in READ FIRST / THEN IF NEEDED
```

### Examples

- Header active state wrong → `logged-in-app-shell-navigation.md` → `header.php` + `kmae_page_url()` / request-path logic + `header.css`.
- Listing wizard visual issue → `listing-create-presentation.md` → `listing-create.css`, then `page.php`/detection only if wrapper/body class is wrong. Plugin wizard PHP/JS is outside this theme map.
- Profile layout broken → `profile-presentation.md` → `profile.css` + `page.php`; plugin profile shortcode markup only if selector/DOM mismatch is suspected.
- Üzenetek badge wrong → theme only displays the value; inspect `header.php` / `kmae_app_menu_groups()`, then move to plugin map for `KMAE_Database::count_unread_conversations()`.

## 7. Important caveats

1. Theme runtime version (`3.25.112`) differs from `style.css` metadata version (`3.25.8`).
2. `kmae_is_guest_simple_marketing_shell()` exists but no in-theme caller was found.
3. `kmae_asset()` exists but no in-theme caller was found.
4. `theme.js` contains an optional `[data-kmae-sheet]` tab controller, but no matching `data-kmae-sheet` markup was found in this theme snapshot. It may be plugin/legacy markup: `UNVERIFIED`.
5. `my-listings.css` references `my-listings.js` in a comment, but this theme archive contains no such JS. That behavior is outside the theme snapshot.
6. The theme has no dedicated single/archive template for `kmae_listing`; it detects and styles those routes. The plugin-side template behavior is not verified here.

## 8. V2 diagnostic-first operating mode

The V2 layer changes the **entry point**, not the underlying architecture map.

```text
USER SYMPTOM
  ↓
DIAGNOSTIC-ROUTER.md
  ↓
ONE matching FEATURES/*.md
  ↓
1–5 exact source symbols / blocks / selectors
  ↓
checkpoint: theme-owned or plugin-owned?
  ↓
localized change
  ↓
REGRESSION-MAP.md
```

### V2 maps

- `DIAGNOSTIC-ROUTER.md` — symptom → source routing; default first read.
- `DECISION-TREES.md` — short branch/STOP/SKIP trees.
- `PLUGIN-BOUNDARIES.md` — exact theme consumers and handoff checkpoints.
- `HOTSPOTS.md` — high fan-out/high regression source areas.
- `SEARCH-ALIASES.md` — user wording/UI text → technical hooks.
- `REGRESSION-MAP.md` — changed symbol → concrete verification targets.
- `ROUTING-TESTS.md` — 15 simulated routing tests.

### Context budget

Default:

`1 ROUTER + 1 FEATURE MAP + 1–5 SOURCE SYMBOLS`

Forbidden as a default first step:

- full repository;
- full `SYMBOL-MAP.md`;
- all feature maps;
- all CSS;
- all JS;
- full plugin.

### Source truth / verification

`SOURCE CODE > PROJECT MEMORY`.

If source and map disagree:
1. inspect source;
2. decide from source;
3. correct project memory.

`VERIFIED` means source-checked against the archive SHA in `SCOPE.md`; plugin implementation claims remain `UNVERIFIED PLUGIN TARGET` unless plugin source is separately mapped.
