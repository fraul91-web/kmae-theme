# DATA MAP — THEME

## 1. Direct WordPress runtime reads

| Data | Reader | Use |
|---|---|---|
| logged-in state | many theme functions/templates | guest vs app shell, CSS selection, front-page routing |
| current user object | `header.php`, app-home fallback | display name/login, avatar initials |
| current user ID | header, menu model, Google complete, dashboard bridge | ownership/plugin API calls |
| `$_SERVER['REQUEST_URI']` | `header.php`, `kmae_app_menu_groups()` | active navigation detection |
| queried post ID | page detectors, header own-listing logic | slug/content/author reads |
| post `post_name` | page detectors, `page.php` | route/feature classification |
| post `post_content` | page detectors, `page.php` | shortcode presence and direct shortcode execution |
| post `post_author` | `header.php` | own-listing active-nav behavior |
| `$_GET['kmae_complete']` | `kmae_is_google_complete_page()` | completion-screen gate |
| front-page/query state | `front-page.php`, detectors, header | template and nav selection |

## 2. Plugin API reads/calls

These are verified references from the theme, but their internals/storage are **not** verified in this phase.

| External symbol | Theme call site | Theme purpose | Storage/side effects |
|---|---|---|---|
| `KMAE_Pages::url($slug)` | `kmae_page_url()` | canonical app page URL | `UNVERIFIED` |
| `KMAE_Google_Auth::needs_completion($uid)` | Google-complete detector | decide whether completion UI applies | `UNVERIFIED` |
| `KMAE_Brand::logo_guest()` | media/logo helpers | logo URL | `UNVERIFIED` |
| `KMAE_Brand::logo_app()` | media helper | app logo candidate | `UNVERIFIED` |
| `KMAE_Brand::logo_url($variant)` | logo helper | explicit logo variant | `UNVERIFIED` |
| `KMAE_Seller::count_active_cycle_listings($uid)` | menu model | “Hirdetéseim” badge | `UNVERIFIED` |
| `KMAE_Database::count_unread_conversations($uid)` | header + menu model | „Üzenetek” unread chat badge | `UNVERIFIED` |
| `KMAE_Icons::render($icon, ...)` | header/menu partial | SVG/icon HTML | `UNVERIFIED` |
| `KMAE_Dashboard::get_data($uid)` | app-home bridge | dashboard data | `UNVERIFIED` |
| `KMAE_PLUGIN_DIR` | app-home bridge | direct plugin template include | plugin constant definition `UNVERIFIED` |
| enqueue handle `kmae-public` | Google-complete CSS | stylesheet dependency/order | registration source `UNVERIFIED` |

## 3. Shortcode contracts known by the theme

The theme only detects/renders these names; it does not register them.

| Shortcode | Theme role |
|---|---|
| `kmae_create_listing` | route detection + raw direct shortcode rendering + listing-create CSS |
| `kmae_browse` | browse route detection + browse CSS |
| `kmae_my_listings` | own-listings detection + page wrapper + CSS |
| `kmae_faq` | FAQ detection + CSS |
| `kmae_contact` | contact detection + CSS |
| `kmae_profile` | profile detection + raw direct shortcode rendering + CSS |
| `kmae_settings` | settings detection + raw direct shortcode rendering + CSS |
| `kmae_login` | raw direct shortcode rendering |
| `kmae_register` | raw direct shortcode rendering; Google-complete detector also checks it |
| `kmae_hub_sell` | hub wrapper selection |
| `kmae_hub_buy` | hub wrapper selection |
| `kmae_hub_stats` | hub wrapper selection |

## 4. Direct database map

### Reads

No direct `$wpdb`, SQL, metadata write/read helper specific to application data, or custom table access appears in the theme.

Theme calls to `KMAE_Seller`, `KMAE_Database`, and `KMAE_Dashboard` may read plugin storage; this is `PLUGIN-OWNED / UNVERIFIED`.

### Writes

No direct database write is present in the theme snapshot.

## 5. Browser-side state

### Theme-owned DOM state

- `body.kmae-drawer-open`
- header `.is-scrolled`
- dropdown `.is-open`
- footer `.is-open`
- bottom nav `.is-active`
- guest nav `.is-active`
- optional sheet tab/panel `.is-active`
- `body.kmae-video-open`
- ARIA attributes and `hidden` properties for drawers/dropdowns/footer/lightbox.

### Persistent browser storage

No `localStorage` or `sessionStorage` use in theme JS.

## 6. Remote resources

### Google Fonts

`functions.php` loads `fonts.googleapis.com` dynamically:
- logged-in: Outfit + Space Grotesk;
- logged-out: Plus Jakarta Sans.

### Production upload assets hardcoded in PHP

`kmae_media_url()` maps:
- logo guest/app;
- hero landscape/portrait;
- path price/offer cards;
- value graph landscape/portrait;
- final CTA landscape/portrait.

All are under `https://albira.hu/wp-content/uploads/...` in this snapshot.

Exact PHP media map in this snapshot:

| Key | URL |
|---|---|
| `logo_guest` fallback | `https://albira.hu/wp-content/uploads/2026/09/logo.webp` |
| `logo_app` fallback | `https://albira.hu/wp-content/uploads/2026/09/logo.webp` |
| `landing_car_main` | `https://albira.hu/wp-content/uploads/2026/09/suv.webp` |
| `landing_car_a` | `https://albira.hu/wp-content/uploads/2026/09/sedan3.webp` |
| `landing_car_b` | `https://albira.hu/wp-content/uploads/2026/09/kombi.webp` |
| `landing_car_c` | `https://albira.hu/wp-content/uploads/2026/09/terepjaro.webp` |

### Remote CSS images

Additional CSS hardcoded URLs include:
- landing background;
- logged-in background landscape/portrait;
- header background;
- two home-card backgrounds;
- covered-car image for my-listings.

### YouTube

`theme.js` uses `www.youtube-nocookie.com/embed/` for lightbox playback.

## 7. Theme-local images

Three non-empty images ship in the archive but no theme reference was found:
- `albira-asszisztens-kartya.webp`
- `email-logo.png`
- `email-mascot.png`

Status: `UNREFERENCED-IN-THEME`.

## V2 DATA BOUNDARY STOP RULES

- If `KMAE_Database::count_unread_conversations()` returns the wrong integer before theme rendering, stop theme debugging.
- If `KMAE_Seller::count_active_cycle_listings()` returns the wrong integer before `kmae_app_menu_groups()` builds its array, stop theme debugging.
- If `KMAE_Dashboard::get_data()` produces wrong dashboard data, the theme bridge is complete and plugin ownership begins.
- If a shortcode's raw output is already wrong after the correct `page.php` branch calls it, plugin shortcode ownership begins.
- Theme owns **display/routing/presentation** of these values, not their persistence/query semantics.
- Plugin implementation targets remain `UNVERIFIED PLUGIN TARGET` in this theme-only V2.
