# SYMBOL MAP — THEME

Format: FILE / SYMBOL / PURPOSE / CALLED BY / CALLS / READS / WRITES / RELATED FEATURE.

## `functions.php`

### `kmae_setup()`
- **FILE:** `functions.php:25`
- **PURPOSE:** registers WordPress theme support: title tag, post thumbnails, HTML5 features, custom logo.
- **CALLED BY:** WP `after_setup_theme` hook.
- **CALLS:** `add_theme_support()`.
- **READS:** none project-specific.
- **WRITES:** WordPress runtime theme-support registry; no DB write in this function.
- **RELATED FEATURE:** theme bootstrap.

### `kmae_page_url($slug)`
- **FILE:** `functions.php:58`
- **PURPOSE:** resolve an Albira logical page slug to URL.
- **CALLED BY:** header, footer, landing, menu model, possibly external code.
- **CALLS:** `KMAE_Pages::url($slug)` if class/method exists; fallback `home_url('/slug/')`.
- **READS:** plugin class availability.
- **WRITES:** none.
- **RELATED FEATURE:** navigation / plugin page boundary.

### `kmae_is_listing_create_page()`
- **FILE:** `functions.php:65`
- **PURPOSE:** detect listing-create page.
- **CALLED BY:** `kmae_body_classes()`, `kmae_enqueue_listing_create_style()`.
- **CALLS:** WP query/post functions, `has_shortcode()`.
- **READS:** page slug `hirdetes-feladasa`; post content for `kmae_create_listing`.
- **WRITES:** none.
- **RELATED FEATURE:** listing create.

### `kmae_is_browse_page()`
- **FILE:** `functions.php:82`
- **PURPOSE:** detect browse page or `kmae_listing` archive.
- **CALLED BY:** body class + browse enqueue.
- **READS:** archive state, page slug `hirdetesek`, post content `kmae_browse`.
- **WRITES:** none.
- **RELATED FEATURE:** browse/search.

### `kmae_is_my_listings_page()`
- **FILE:** `functions.php:104`
- **PURPOSE:** detect own-listings page.
- **READS:** slug `sajat-hirdetesek`, shortcode `kmae_my_listings`.
- **CALLED BY:** body class + app-shell conditional CSS.
- **WRITES:** none.

### `kmae_is_how_it_works_page()`
- **FILE:** `functions.php:138`
- **PURPOSE:** detect how-it-works page.
- **READS:** slug `hogyan-mukodik`, shortcode `kmae_how_it_works`.
- **CALLED BY:** body class + feature CSS enqueue.

### `kmae_is_faq_page()`
- **FILE:** `functions.php:152`
- **PURPOSE:** detect FAQ page.
- **READS:** slug `gyik`, shortcode `kmae_faq`.
- **CALLED BY:** body class + feature CSS enqueue.

### `kmae_is_contact_page()`
- **FILE:** `functions.php:166`
- **PURPOSE:** detect contact page.
- **READS:** slug `kapcsolat`, shortcode `kmae_contact`.
- **CALLED BY:** body class + feature CSS enqueue.

### `kmae_is_profile_page()`
- **FILE:** `functions.php:180`
- **PURPOSE:** detect profile page.
- **READS:** slug `profil`, shortcode `kmae_profile`.
- **CALLED BY:** body class + logged-in conditional CSS enqueue.

### `kmae_is_google_complete_page()`
- **FILE:** `functions.php:201`
- **PURPOSE:** gate the post-Google-login account completion screen.
- **CALLED BY:** body class + Google-complete CSS enqueue.
- **CALLS:** `is_user_logged_in()`, `KMAE_Google_Auth::needs_completion(current_user_id)`, WP page/shortcode detection.
- **READS:** `$_GET['kmae_complete']`, current user ID, slug/content (`regisztracio` / `kmae_register`).
- **WRITES:** none.
- **PLUGIN BOUNDARY:** `KMAE_Google_Auth` implementation unverified.

### `kmae_is_settings_page()`
- **FILE:** `functions.php:223`
- **PURPOSE:** detect settings page.
- **READS:** slug `beallitasok`, shortcode `kmae_settings`.
- **CALLED BY:** body class + logged-in feature CSS.

### `kmae_body_classes($classes)`
- **FILE:** `functions.php:247`
- **PURPOSE:** attach global auth mode and feature route body classes.
- **CALLED BY:** WP `body_class` filter.
- **CALLS:** all major page detectors above, `is_user_logged_in()`, `is_singular('kmae_listing')`.
- **WRITES:** returned class array only.
- **OUTPUT CLASSES:** `kmae-app`, `kmae-theme-dark`, `kmae-marketing`, `kmae-listing-create-page`, `kmae-single-listing-page`, `kmae-my-listings-page`, `kmae-browse-page`, `kmae-how-it-works-page`, `kmae-faq-page`, `kmae-contact-page-view`, `kmae-profile-page`, `kmae-settings-page`, `kmae-complete-page`.

### `kmae_is_guest_simple_marketing_shell()`
- **FILE:** `functions.php:296`
- **PURPOSE:** returns true for logged-out front page or `regisztracio`, `belepes`, `kapcsolat` pages.
- **CALLED BY:** no in-theme caller found.
- **STATUS:** `UNREFERENCED-IN-THEME`.

### `kmae_assets()`
- **FILE:** `functions.php:319`
- **PURPOSE:** enqueue global fonts/base theme and select guest vs logged-in baseline CSS; enqueue `theme.js`.
- **CALLED BY:** WP `wp_enqueue_scripts` normal priority.
- **READS:** auth state, constants.
- **WRITES:** WordPress enqueue queue.
- **CALLS:** `wp_enqueue_style()`, `wp_enqueue_script()`.

### `kmae_enqueue_app_shell()`
- **FILE:** `functions.php:378`
- **PURPOSE:** late logged-in shell and conditional feature CSS.
- **CALLED BY:** WP `wp_enqueue_scripts`, priority 99.
- **GUARD:** logged-in only.
- **ALWAYS LOGGED-IN:** `app-shell-dark.css`, `header.css`, `home-cards.css`.
- **CONDITIONAL:** `single-listing.css`, `my-listings.css`, `messages.css`, `offer-thread.css`, `sale-contract.css` (`kmae_contract`), `profile.css`, `settings.css`.
- **NOTE:** `single-listing.css` és `offer-thread.css` vége hordozza a bejelentés gombot + `dialog.kmae-report` ablakot (`.kmae-report*`, plugin `REPORTS`).

### `kmae_enqueue_listing_unavailable()`
- **FILE:** `functions.php`
- **PURPOSE:** `listing-unavailable.css` a lejárt hirdetés „nem elérhető” oldalához (vendég + belépett).
- **CALLED BY:** WP `wp_enqueue_scripts`, priority 101.
- **GUARD:** `is_singular('kmae_listing')` + plugin `KMAE_Post_Types::is_unavailable_for_viewer()`.
- **CALLS:** `wp_enqueue_style()`.

### `kmae_enqueue_google_complete()`
- **FILE:** `functions.php:459`
- **PURPOSE:** enqueue account-completion CSS last.
- **CALLED BY:** `wp_enqueue_scripts`, priority 101.
- **DEPENDENCIES:** handles `kmae-public`, `kmae-header`.
- **PLUGIN BOUNDARY:** `kmae-public` not registered in theme.

### `kmae_enqueue_browse()`
- **FILE:** `functions.php:478`
- **PURPOSE:** conditional `browse.css` enqueue.
- **CALLED BY:** `wp_enqueue_scripts`, priority 100.
- **DEPENDENCY:** logged-in `kmae-header`; guest `kmae-landing`.

### `kmae_enqueue_how_it_works()`
- **FILE:** `functions.php:495`
- **PURPOSE:** conditional `how-it-works.css` enqueue at priority 100.

### `kmae_enqueue_faq()`
- **FILE:** `functions.php:512`
- **PURPOSE:** conditional `faq.css` enqueue at priority 100.

### `kmae_enqueue_contact()`
- **FILE:** `functions.php:529`
- **PURPOSE:** conditional `contact.css` enqueue at priority 100.

### `kmae_asset($path)`
- **FILE:** `functions.php:549`
- **PURPOSE:** build a URL under theme `/assets/`.
- **CALLED BY:** no in-theme caller found.
- **STATUS:** `UNREFERENCED-IN-THEME`.

### `kmae_media_url($key)`
- **FILE:** `functions.php:559`
- **PURPOSE:** central map of production-upload URLs for logos and landing imagery.
- **CALLED BY:** landing template, `kmae_logo_url()` fallback.
- **CALLS:** `KMAE_Brand::logo_guest()` / `logo_app()` if available.
- **READS:** hardcoded remote URLs.
- **WRITES:** static in-process `$urls` cache only.

### `kmae_logo_url($variant='auto')`
- **FILE:** `functions.php:595`
- **PURPOSE:** choose current logo URL.
- **CALLED BY:** `kmae_logo_img()`.
- **CALLS:** `KMAE_Brand` methods if available, otherwise `kmae_media_url()`.
- **NOTE:** auto path always resolves guest logo in this theme snapshot.

### `kmae_logo_img($class='kmae-logo__img')`
- **FILE:** `functions.php:612`
- **PURPOSE:** print escaped `<img>` for Albira logo.
- **CALLED BY:** `header.php` multiple variants.
- **CALLS:** `kmae_logo_url()`.

### `kmae_app_menu_groups()`
- **FILE:** `functions.php:626`
- **PURPOSE:** build logged-in mobile/full app menu data model and badges.
- **CALLED BY:** `template-parts/app-menu-panels.php`.
- **CALLS:** `KMAE_Seller::count_active_cycle_listings()`, `KMAE_Database::count_unread_conversations()`, `kmae_page_url()`.
- **READS:** current user ID, `REQUEST_URI`.
- **WRITES:** none.
- **OUTPUT GROUPS:** `nav` and `info` arrays with label/url/active/icon and optional badge.

### `kmae_enqueue_listing_create_style()`
- **FILE:** `functions.php:740`
- **PURPOSE:** conditional listing-create CSS enqueue.
- **CALLED BY:** `wp_enqueue_scripts`, priority 100.
- **DEPENDENCY:** logged-in `kmae-header`; guest `kmae-landing`.

## Template-local PHP symbols

### `header.php::$kmae_nav_active`
- checks whether current `REQUEST_URI` contains `/<slug>`.
- used to build active desktop navigation.

### `header.php::$kmae_guest_nav_active`
- resolves guest nav active item from front-page/page/path state.

### `header.php::$kmae_own_listing`
- true when viewing singular `kmae_listing`, logged in, and current post author equals current user.
- prevents “Browse” from becoming active and makes “My listings” active instead.

### `app-menu-panels.php::$render`
- closure that renders one menu item from `kmae_app_menu_groups()` including optional plugin icon and badge.

## JavaScript symbols — `assets/js/theme.js`

### `onScroll()`
- toggles `#site-header.is-scrolled` when `window.scrollY > 8`.

### `setDrawerOpen(open)`
- toggles `body.kmae-drawer-open`, drawer/backdrop `hidden`, ARIA state and focus.
- called by drawer open/close/backdrop and Escape handling.

### `closeDropdown(wrap)` / `closeAllDropdowns(except)` / `openDropdown(wrap)`
- manage `[data-kmae-dropdown]` desktop panels, `aria-expanded`, `hidden`, `.is-open`.

### `setFooterOpen(open)`
- controls collapsible footer classes, panel visibility and toggle labels.

### `initSheetTabs()` + inner `showTab(id)`
- looks for `[data-kmae-sheet]`, `[data-sheet-tab]`, `[data-sheet-panel]` and switches active/hidden state.
- no matching `data-kmae-sheet` root exists in this theme PHP snapshot: `UNVERIFIED` external/legacy hook.

### bottom-nav path synchronizer
- compares each `.kmae-bottom-nav__item` URL pathname to `window.location.pathname` and adds `.is-active` on exact match.

### `setGuestActive(key)` / `syncGuestFromHash()`
- controls guest desktop nav `.is-active` classes via `[data-nav]`.
- on home, an empty hash selects `home`; clicking a nav link sets clicked key active.

### `embedUrl(videoId)`
- builds `https://www.youtube-nocookie.com/embed/<id>?autoplay=1&rel=0&modestbranding=1&playsinline=1`.

### `openVideo(videoId, trigger)` / `closeVideo()`
- controls `#kmae-video-lightbox` and `#kmae-video-iframe`, body `kmae-video-open`, focus restore.
- triggers are delegated `[data-kmae-video-id]`; close hooks `[data-kmae-video-close]`.
- matching lightbox markup is not in theme PHP snapshot; likely shortcode/plugin output: `PLUGIN-OWNED / UNVERIFIED`.

## Hook registry

| Hook | Callback | Priority |
|---|---|---:|
| `after_setup_theme` | `kmae_setup` | default |
| `body_class` filter | `kmae_body_classes` | default |
| `wp_enqueue_scripts` | `kmae_assets` | default |
| `wp_enqueue_scripts` | `kmae_enqueue_app_shell` | 99 |
| `wp_enqueue_scripts` | `kmae_enqueue_browse` | 100 |
| `wp_enqueue_scripts` | `kmae_enqueue_how_it_works` | 100 |
| `wp_enqueue_scripts` | `kmae_enqueue_faq` | 100 |
| `wp_enqueue_scripts` | `kmae_enqueue_contact` | 100 |
| `wp_enqueue_scripts` | `kmae_enqueue_listing_create_style` | 100 |
| `wp_enqueue_scripts` | `kmae_enqueue_google_complete` | 101 |

No theme-defined shortcode, AJAX handler, REST endpoint, cron callback, `do_action`, or `apply_filters` call was found.

## V2 EXACT SOURCE LOCATORS

Use this section **only after** the router selects a symbol. It is intentionally a locator, not a duplicate description.

### `functions.php` — function ranges

| Symbol | Range |
|---|---:|
| `kmae_setup()` | `25–49` |
| `kmae_page_url()` | `58–63` |
| `kmae_is_listing_create_page()` | `65–80` |
| `kmae_is_browse_page()` | `82–102` |
| `kmae_is_my_listings_page()` | `104–119` |
| `kmae_is_how_it_works_page()` | `138–150` |
| `kmae_is_faq_page()` | `152–164` |
| `kmae_is_contact_page()` | `166–178` |
| `kmae_is_profile_page()` | `180–195` |
| `kmae_is_google_complete_page()` | `201–221` |
| `kmae_is_settings_page()` | `223–238` |
| `kmae_body_classes()` | `247–288` |
| `kmae_is_guest_simple_marketing_shell()` | `296–314` |
| `kmae_assets()` | `319–371` |
| `kmae_enqueue_app_shell()` | `378–452` |
| `kmae_enqueue_google_complete()` | `459–472` |
| `kmae_enqueue_browse()` | `478–489` |
| `kmae_enqueue_how_it_works()` | `495–506` |
| `kmae_enqueue_faq()` | `512–523` |
| `kmae_enqueue_contact()` | `529–540` |
| `kmae_asset()` | `549–551` |
| `kmae_media_url()` | `559–586` |
| `kmae_logo_url()` | `595–605` |
| `kmae_logo_img()` | `612–619` |
| `kmae_app_menu_groups()` | `626–737` |
| `kmae_enqueue_listing_create_style()` | `740–750` |

### `assets/js/theme.js` — exact handlers/blocks

| Symbol/block | Range / start |
|---|---:|
| `onScroll()` | `13–16` |
| `setDrawerOpen()` | `22–36` |
| drawer listeners | `38–52` |
| `closeDropdown()` | `57–63` |
| `closeAllDropdowns()` | `65–70` |
| `openDropdown()` | `72–79` |
| dropdown listener block | `81–102` |
| `setFooterOpen()` | `109–118` |
| footer click | `120–124` |
| `initSheetTabs()` | `127–152` |
| nested `showTab()` | `133–144` |
| global Escape handler | `154–160` |
| bottom-nav active state | `162–177` |
| guest-nav controller | `179–211` |
| lightbox DOM guard | `214–218` |
| `embedUrl()` | `223–229` |
| `openVideo()` | `231–239` |
| `closeVideo()` | `241–249` |
| video delegated click/close/Escape | `251–270` |

### Template/block locators

| File | Block | Range |
|---|---|---:|
| `front-page.php` | auth template split | `8–16` |
| `page.php` | page classification | `10–52` |
| `page.php` | modifier selection | `54–61` |
| `page.php` | render switch | `64–97` |
| `header.php` | auth/user/unseen/nav state | `8–48` |
| `header.php` | drawer open button | `99–105` |
| `header.php` | desktop logged-in nav | `153–208` |
| `header.php` | profile dropdown | `213–225` |
| `header.php` | guest desktop nav | `227–238` |
| `header.php` | drawer shell | `242–263` |
| `header.php` | logged-in app layout open + main | `265–270` |
| `footer.php` | main/app-layout close | `10–15` |
| `footer.php` | collapsible footer | `17–48` |
| `footer.php` | logged-in bottom nav | `50–69` |
| `app-menu-panels.php` | menu model fetch | `12` |
| `app-menu-panels.php` | local `$render` closure | `14–33` |
| `app-menu-panels.php` | nav/info panels | `35–43` |
| `template-parts/simple-mode/app-home.php` | dashboard class fallback | `12–31` |
| `template-parts/simple-mode/app-home.php` | plugin data/include bridge | `33–34` |

### Verification notes

- No theme producer exists for `[data-kmae-sheet]`; the JS controller is verified, its markup owner is not.
- No theme producer exists for `#kmae-video-lightbox`, `#kmae-video-iframe`, `[data-kmae-video-id]`; the JS controller is verified, its markup owner is not.
- `kmae_asset()` and `kmae_is_guest_simple_marketing_shell()` have no verified in-theme callers.
