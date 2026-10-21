# PLUGIN ↔ THEME BOUNDARIES — THEME V2

**MAP-TYPE:** ON-DEMAND  
This file documents only boundaries provable from the theme source. Plugin implementations were not inspected in this V2 theme pass. Therefore plugin-side targets are deliberately marked **UNVERIFIED PLUGIN TARGET**.

## B01 — Logical page URL resolution

**THEME FILE:** `functions.php`  
**THEME SYMBOL:** `kmae_page_url()` (`58–63`)  
**DATA/ACTION:** logical slug → URL.  
**PLUGIN RESPONSIBILITY:** optional canonical resolver `KMAE_Pages::url($slug)`.  
**BOUNDARY CHECK:** call theme helper; compare returned URL with expected slug route.  
**WHEN TO STOP THEME DEBUGGING:** `kmae_page_url()` correctly delegates and plugin result itself is wrong.  
**PLUGIN SEARCH TARGET:** `KMAE_Pages::url` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B02 — Branding/logo provider

**THEME FILE:** `functions.php`  
**THEME SYMBOL:** `kmae_media_url()`, `kmae_logo_url()`, `kmae_logo_img()` (`559–619`)  
**DATA/ACTION:** logo URL selection.  
**PLUGIN RESPONSIBILITY:** optional `KMAE_Brand::logo_guest()`, `logo_app()`, `logo_url()`.  
**BOUNDARY CHECK:** inspect helper return before emitted `<img src>`.  
**WHEN TO STOP THEME DEBUGGING:** wrong URL originates from `KMAE_Brand` return.  
**PLUGIN SEARCH TARGET:** `KMAE_Brand::*` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B03 — Unread chat count

**THEME FILE:** `header.php`, `functions.php`  
**THEME SYMBOL:** `$kmae_unseen` (`header.php:21–23`); `kmae_app_menu_groups()` (`functions.php:641–677`)  
**DATA/ACTION:** integer unseen count.  
**PLUGIN RESPONSIBILITY:** produce count.  
**BOUNDARY CHECK:** capture integer returned by `KMAE_Database::count_unread_conversations(uid)` before theme display.  
**WHEN TO STOP THEME DEBUGGING:** producer returns wrong number.  
**PLUGIN SEARCH TARGET:** `KMAE_Database::count_unread_conversations` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B04 — Active listing count in drawer

**THEME FILE:** `functions.php`, `template-parts/app-menu-panels.php`  
**THEME SYMBOL:** `kmae_app_menu_groups()` (`638–640`, `657–664`); `$render` (`14–33`)  
**DATA/ACTION:** active-cycle listing count → menu badge.  
**PLUGIN RESPONSIBILITY:** produce active listing count.  
**BOUNDARY CHECK:** compare plugin return with `$listings`, returned menu model, rendered badge.  
**WHEN TO STOP THEME DEBUGGING:** `$listings` is already wrong immediately after plugin call.  
**PLUGIN SEARCH TARGET:** `KMAE_Seller::count_active_cycle_listings` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B05 — Menu icon renderer

**THEME FILE:** `header.php`, `template-parts/app-menu-panels.php`  
**THEME SYMBOL:** desktop nav icon call `header.php:192–197`; drawer icon call `app-menu-panels.php:20–24`  
**DATA/ACTION:** icon key + size → SVG/HTML.  
**PLUGIN RESPONSIBILITY:** render icon via `KMAE_Icons::render()`.  
**BOUNDARY CHECK:** class exists? returned markup non-empty/correct?  
**WHEN TO STOP THEME DEBUGGING:** theme passes correct icon key but plugin render is wrong.  
**PLUGIN SEARCH TARGET:** `KMAE_Icons::render` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B06 — Logged-in dashboard bridge

**THEME FILE:** `template-parts/simple-mode/app-home.php`  
**THEME SYMBOL:** class gate `12–31`; `KMAE_Dashboard::get_data()` + direct include `33–34`  
**DATA/ACTION:** current user id → dashboard data; plugin template render.  
**PLUGIN RESPONSIBILITY:** dashboard data model and `templates/partials/dashboard-home.php`.  
**BOUNDARY CHECK:** verify class exists; inspect `$dash` after `get_data`; verify included file constant/path.  
**WHEN TO STOP THEME DEBUGGING:** theme branch/include is correct but `$dash` or plugin template output is wrong.  
**PLUGIN SEARCH TARGET:** `KMAE_Dashboard::get_data`; `KMAE_PLUGIN_DIR/templates/partials/dashboard-home.php` — **UNVERIFIED PLUGIN TARGETS**.  
**THEME STATUS:** VERIFIED.

## B07 — Google account completion gate

**THEME FILE:** `functions.php`  
**THEME SYMBOL:** `kmae_is_google_complete_page()` (`201–221`)  
**DATA/ACTION:** current user → needs-completion boolean.  
**PLUGIN RESPONSIBILITY:** `KMAE_Google_Auth::needs_completion(uid)`.  
**BOUNDARY CHECK:** separately verify login, query flag, plugin boolean, registration route.  
**WHEN TO STOP THEME DEBUGGING:** only plugin boolean is incorrect.  
**PLUGIN SEARCH TARGET:** `KMAE_Google_Auth::needs_completion` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B08 — Google completion base stylesheet dependency

**THEME FILE:** `functions.php`  
**THEME SYMBOL:** `kmae_enqueue_google_complete()` (`459–472`)  
**DATA/ACTION:** CSS dependency handle `kmae-public`.  
**PLUGIN RESPONSIBILITY:** register/enqueue the `kmae-public` stylesheet handle.  
**BOUNDARY CHECK:** inspect WP style registry for handle/dependency availability.  
**WHEN TO STOP THEME DEBUGGING:** theme declares dependency correctly but plugin does not register expected handle.  
**PLUGIN SEARCH TARGET:** registration of style handle `kmae-public` — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED.

## B09 — Theme page shortcodes

**THEME FILE:** `page.php`, `functions.php`  
**THEME SYMBOL:** shortcode detectors/classifiers (`page.php:19–48`; detector functions in `functions.php`)  
**DATA/ACTION:** shortcode text → rendering route/body class/CSS; shortcode itself → final HTML.  
**PLUGIN RESPONSIBILITY:** registration and implementation of `kmae_create_listing`, `kmae_browse`, `kmae_my_listings`, `kmae_how_it_works`, `kmae_faq`, `kmae_contact`, `kmae_profile`, `kmae_register`, `kmae_settings`, `kmae_login`, `kmae_hub_sell`, `kmae_hub_buy`, `kmae_hub_stats`.  
**BOUNDARY CHECK:** theme branch selected? `do_shortcode/the_content` emits expected plugin HTML?  
**WHEN TO STOP THEME DEBUGGING:** branch is correct but shortcode output/business behavior is wrong.  
**PLUGIN SEARCH TARGET:** shortcode registration/handler matching exact shortcode name — **UNVERIFIED PLUGIN TARGET**.  
**THEME STATUS:** VERIFIED consumer/detector.

## B10 — Listing/browse/single DOM ownership

**THEME FILE:** CSS + route detection; no theme business template for plugin listing internals.  
**THEME SYMBOL:** `kmae_is_listing_create_page()`, `kmae_is_browse_page()`, `is_singular('kmae_listing')` gates.  
**DATA/ACTION:** externally produced DOM is styled by theme selectors.  
**PLUGIN RESPONSIBILITY:** listing-create wizard markup/behavior, browse/search markup/data, single/archive template behavior where not provided by WP theme snapshot.  
**BOUNDARY CHECK:** if route/body/enqueue correct, compare actual DOM against theme selectors.  
**WHEN TO STOP THEME DEBUGGING:** expected selector has no corresponding DOM because producer markup differs, or business action/data is wrong.  
**PLUGIN SEARCH TARGET:** exact shortcode/template/JS producer — **UNVERIFIED PLUGIN TARGET**; do not invent class/method names.  
**THEME STATUS:** VERIFIED ownership boundary.

## B11 — Video lightbox markup

**THEME FILE:** `assets/js/theme.js`  
**THEME SYMBOL:** `214–270`  
**DATA/ACTION:** DOM hooks → YouTube lightbox interaction.  
**PLUGIN RESPONSIBILITY:** unknown; theme source contains no producer for `#kmae-video-lightbox`, `#kmae-video-iframe`, `[data-kmae-video-id]`.  
**BOUNDARY CHECK:** search runtime DOM / owning page/shortcode for exact hooks.  
**WHEN TO STOP THEME DEBUGGING:** required hooks are absent.  
**PLUGIN SEARCH TARGET:** **UNVERIFIED EXTERNAL MARKUP TARGET**; search exact hook strings first.  
**THEME STATUS:** VERIFIED absence within theme.

## B12 — Optional sheet-tab markup

**THEME FILE:** `assets/js/theme.js`  
**THEME SYMBOL:** `initSheetTabs()` `127–152`  
**DATA/ACTION:** `[data-kmae-sheet]` root and tab/panel data attributes.  
**PLUGIN RESPONSIBILITY:** unknown/external markup if feature still used.  
**BOUNDARY CHECK:** exact `[data-kmae-sheet]` root existence at runtime.  
**WHEN TO STOP THEME DEBUGGING:** absent root; theme's `.kmae-sheet` class markup is not equivalent.  
**PLUGIN SEARCH TARGET:** **UNVERIFIED EXTERNAL MARKUP TARGET**.  
**THEME STATUS:** VERIFIED absence within theme.
