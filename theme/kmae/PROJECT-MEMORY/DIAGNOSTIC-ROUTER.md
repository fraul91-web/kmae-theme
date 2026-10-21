# DIAGNOSTIC ROUTER — THEME V2

**MAP-TYPE:** ROUTER  
**SOURCE OF TRUTH:** `SOURCE CODE > PROJECT MEMORY`  
**VERIFICATION BASE:** theme source archive SHA-256 `1c7119a191094d9a40816a28e0d142349599006d63050bdcba50b65163d78fdf`.

## Operating rule — minimal context first

Default investigation budget:

```text
1 ROUTER
+ 1 FEATURE MAP
+ 1–5 SOURCE SYMBOLS / TEMPLATE BLOCKS / CSS SCOPES
```

Do **not** load the complete repository, complete `SYMBOL-MAP.md`, every feature map, every CSS file, every JS file, or the plugin by default.

Expand context only when a checkpoint fails to explain the symptom.

### Verification vocabulary

- **VERIFIED** = the theme-side relation was re-checked against the exact source snapshot.
- **UNVERIFIED PLUGIN TARGET** = the theme proves that ownership crosses into a plugin, but this V2 pass did not inspect plugin source.
- **UNVERIFIED EXTERNAL MARKUP** = JS expects markup that is not present in this theme snapshot.

---

## R01 — Page has the wrong global visual shell / theme mode

**SYMPTOM:** guest page looks like logged-in app, or logged-in page looks like marketing shell; global fonts/base colors are wrong.  
**FEATURE:** Theme bootstrap + route/body classes.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_assets()` (`319–371`) and `kmae_body_classes()` (`247–288`)  
**EXPECTED:** guest → `kmae-marketing` + `theme.css` + `landing.css`; logged-in → `kmae-app kmae-theme-dark` + `theme.css` + `app.css` + `pc-dashboard.css`, then app shell at priority 99.  
**NEXT IF TRUE:** if body class and enqueued handles are correct, inspect the active CSS cascade only.  
**NEXT IF FALSE:** inspect auth state and the exact detector/enqueue symbol that failed.  
**SEARCH TERMS:** `kmae-marketing`, `kmae-app`, `kmae-theme-dark`, `kmae-theme`, `kmae-landing`, `kmae-app-shell-dark`.  
**DO NOT LOAD:** feature-specific plugin PHP, AJAX, DB code.  
**PLUGIN BOUNDARY:** none required for the global auth split.  
**VERIFY:** guest + logged-in render; `wp_enqueue_scripts` ordering.  
**STATUS:** VERIFIED.

## R02 — Feature CSS is not loaded / expected body class is missing

**SYMPTOM:** browse/listing-create/offers/profile/settings/FAQ/contact/how-it-works page renders with base styles only.  
**FEATURE:** Route detection + feature presentation.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** matching `kmae_is_*_page()` → `kmae_body_classes()` → matching `kmae_enqueue_*()` / `kmae_enqueue_app_shell()` branch.  
**EXPECTED:** the same logical route must produce the expected detector result, body class, and feature CSS enqueue.  
**NEXT IF TRUE:** open only the feature stylesheet and the actual DOM selector it expects.  
**NEXT IF FALSE:** stay in `functions.php`; compare slug / shortcode / query type with detector conditions.  
**SEARCH TERMS:** route slug, shortcode name, `kmae-*-page`, `wp_enqueue_style`.  
**DO NOT LOAD:** entire CSS directory; plugin backend.  
**PLUGIN BOUNDARY:** only if route/content itself comes from plugin and theme detector receives unexpected markup/query state.  
**VERIFY:** detector → body class → style handle chain.  
**STATUS:** VERIFIED.

## R03 — A shortcode page has broken wrappers / unexpected `<p>` / spacing / grid children

**SYMPTOM:** profile/settings/login/register/listing-create markup is structurally altered; plugin grid receives extra WordPress formatting.  
**FEATURE:** Generic page/shortcode rendering.  
**START HERE:**  
**FILE:** `page.php`  
**SYMBOL:** classification block `10–52`; render branches `64–97`, especially raw shortcode `86–89`.  
**EXPECTED:** listing-create and raw-shortcode pages use direct `do_shortcode(raw post_content)`; ordinary pages use `the_content()`.  
**NEXT IF TRUE:** if correct branch is selected and HTML is already wrong, cross plugin boundary to shortcode output.  
**NEXT IF FALSE:** fix/inspect only the classification predicate and branch.  
**SEARCH TERMS:** `$is_raw_shortcode`, `$is_listing_create`, `do_shortcode`, `the_content`, `kmae_profile`, `kmae_settings`, `kmae_login`, `kmae_register`.  
**DO NOT LOAD:** global JS, unrelated feature CSS.  
**PLUGIN BOUNDARY:** shortcode implementation is plugin-owned.  
**VERIFY:** inspect rendered HTML source before CSS.  
**STATUS:** VERIFIED.

## R04 — Mobile/tablet hamburger button does nothing

**SYMPTOM:** drawer does not open, close, backdrop-close, or Escape-close.  
**FEATURE:** Logged-in app shell + guest shell navigation.  
**START HERE:**  
**FILE:** `assets/js/theme.js`  
**SYMBOL:** `setDrawerOpen()` (`22–36`) and listeners `38–52`, Escape `154–160`.  
**EXPECTED:** markup IDs from `header.php` exist; click toggles `body.kmae-drawer-open`, `hidden`, ARIA state, focus.  
**NEXT IF TRUE:** if JS state changes but drawer is invisible/wrong, inspect `header.css` / `landing.css` responsive drawer rules.  
**NEXT IF FALSE:** verify `header.php` IDs `#kmae-drawer-open`, `#kmae-drawer-close`, `#kmae-drawer`, `#kmae-drawer-backdrop` (`99–105`, `242–263`).  
**SEARCH TERMS:** `kmae-drawer-open`, `kmae-drawer-close`, `kmae-drawer-backdrop`, `kmae-drawer-open` body class.  
**DO NOT LOAD:** plugin feature code; chat/listing backend.  
**PLUGIN BOUNDARY:** drawer shell itself is theme-owned; logged-in drawer *content icons/counts* can cross plugin boundary.  
**VERIFY:** open, close button, backdrop, Escape, ARIA, focus.  
**STATUS:** VERIFIED.

## R05 — Desktop profile dropdown does not open/close

**SYMPTOM:** avatar/profile menu does not toggle or stays open.  
**FEATURE:** Logged-in app shell navigation.  
**START HERE:**  
**FILE:** `assets/js/theme.js`  
**SYMBOL:** `closeDropdown()` (`57–63`), `closeAllDropdowns()` (`65–70`), `openDropdown()` (`72–79`), listeners `81–102`.  
**EXPECTED:** `header.php` emits `[data-kmae-dropdown]`, a `button[aria-expanded]`, and `.kmae-header__panel` (`213–225`).  
**NEXT IF TRUE:** if hidden/is-open state is correct, inspect `header.css`.  
**NEXT IF FALSE:** inspect only markup-selector agreement and JS execution.  
**SEARCH TERMS:** `data-kmae-dropdown`, `kmae-header__panel`, `aria-expanded`, `is-open`.  
**DO NOT LOAD:** plugin, footer, feature CSS.  
**PLUGIN BOUNDARY:** none for toggle behavior.  
**VERIFY:** open, second click, outside click, Escape.  
**STATUS:** VERIFIED.

## R06 — Footer panel toggle does not work

**SYMPTOM:** footer cannot be expanded/collapsed or ARIA label is stale.  
**FEATURE:** Shared footer shell.  
**START HERE:**  
**FILE:** `assets/js/theme.js`  
**SYMBOL:** `setFooterOpen()` (`109–118`) and click listener `120–124`.  
**EXPECTED:** `footer.php` emits `#site-footer`, `#kmae-footer-toggle`, `#kmae-footer-panel` (`17–48`).  
**NEXT IF TRUE:** if state toggles but appearance fails, inspect footer rules in the active base stylesheet.  
**NEXT IF FALSE:** inspect IDs and script enqueue `kmae_assets()`.  
**SEARCH TERMS:** `kmae-footer-toggle`, `kmae-footer-panel`, `data-label-open`, `is-open`.  
**DO NOT LOAD:** plugin internals.  
**PLUGIN BOUNDARY:** none.  
**VERIFY:** click + Escape + hidden/ARIA label.  
**STATUS:** VERIFIED.

## R07 — Logged-in bottom navigation highlights the wrong item

**SYMPTOM:** bottom item active state missing/wrong after page load.  
**FEATURE:** Logged-in app shell navigation.  
**START HERE:**  
**FILE:** `assets/js/theme.js`  
**SYMBOL:** bottom nav active-state block `162–177`.  
**EXPECTED:** exact normalized pathname equality between current path and each `.kmae-bottom-nav__item` href.  
**NEXT IF TRUE:** if JS logic matches but URL is wrong, inspect `footer.php` links and `kmae_page_url()`.  
**NEXT IF FALSE:** inspect actual current pathname / href normalization only.  
**SEARCH TERMS:** `.kmae-bottom-nav__item`, `itemPath === path`, `eladni`, `venni`, `statisztikak`.  
**DO NOT LOAD:** desktop nav, plugin DB, feature CSS unless active styling itself is wrong.  
**PLUGIN BOUNDARY:** `kmae_page_url()` may delegate URL resolution to `KMAE_Pages::url()`; target implementation is UNVERIFIED.  
**VERIFY:** `/`, `/eladni/`, `/venni/`, `/statisztikak/`; nested routes do not match by design.  
**STATUS:** VERIFIED theme side.

## R08 — Desktop logged-in navigation highlights the wrong item

**SYMPTOM:** Browse/Hirdetéseim active state is wrong.  
**FEATURE:** Logged-in app shell navigation.  
**START HERE:**  
**FILE:** `header.php`  
**SYMBOL:** `$kmae_nav_active` (`24–26`), `$kmae_own_listing` (`30–34`), desktop nav model (`153–208`).  
**EXPECTED:** own `kmae_listing` excludes Browse and activates Hirdetéseim; Üzenetek activates via path.  
**NEXT IF TRUE:** if desktop is correct but drawer differs, inspect `kmae_app_menu_groups()` because drawer uses a separate active-state model.  
**NEXT IF FALSE:** stay in `header.php`; do not open plugin backend.  
**SEARCH TERMS:** `$kmae_own_listing`, `sajat-hirdetesek`, `hirdetesek`, `uzenetek`, `kmae-header__nav-item`.  
**DO NOT LOAD:** all CSS if class itself is wrong.  
**PLUGIN BOUNDARY:** icon rendering only (`KMAE_Icons::render`); active-state ownership is theme.  
**VERIFY:** own listing vs other listing, Üzenetek path, browse path.  
**STATUS:** VERIFIED.

## R09 — Drawer menu active state/badges differ from desktop header

**SYMPTOM:** mobile drawer highlights/counts do not match desktop header.  
**FEATURE:** Logged-in app shell navigation.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_app_menu_groups()` (`626–737`), especially path matcher `645–647` and nav entries `649–697`.  
**EXPECTED:** drawer model is built independently of desktop header; counts come from plugin methods, then `app-menu-panels.php::$render` prints them.  
**NEXT IF TRUE:** if model values are correct but markup wrong, inspect `template-parts/app-menu-panels.php::$render` (`14–33`).  
**NEXT IF FALSE:** determine whether wrong field is theme path logic or plugin-provided count.  
**SEARCH TERMS:** `kmae_app_menu_groups`, `.kmae-sheet__badge`, `count_active_cycle_listings`, `count_unread_conversations`.  
**DO NOT LOAD:** all app CSS unless values/classes are already correct.  
**PLUGIN BOUNDARY:** counts/icons are plugin-provided; exact producer implementation UNVERIFIED.  
**VERIFY:** drawer model vs header model intentionally separate.  
**STATUS:** VERIFIED theme side / UNVERIFIED plugin producers.

## R10 — Unread chat badge count is wrong

**SYMPTOM:** “Üzenetek” number or mobile chat dot is wrong, stale, or inconsistent.  
**FEATURE:** Chat presentation + app navigation.  
**START HERE:**  
**FILE:** `header.php`  
**SYMBOL:** `$kmae_unseen` (`21–23`) and badge markup (`200–205`); also `functions.php::kmae_app_menu_groups()` (`641–677`) for drawer.  
**EXPECTED:** theme casts plugin-returned unread chat count (`count_unread_conversations`) to int and only displays it; header badge is hidden at `0`, drawer omits empty badge.  
**NEXT IF TRUE:** if returned `$kmae_unseen` is already wrong, **STOP THEME DEBUGGING** → plugin producer.  
**NEXT IF FALSE:** if value is right but rendered/hidden state is wrong, stay in header/menu markup + CSS.  
**SEARCH TERMS:** `data-kmae-unseen`, `kmae-pc-badge`, `kmae-phone-header__dot`, `count_unread_conversations`.  
**DO NOT LOAD:** listing wizard, page.php, theme backend (none exists).  
**PLUGIN BOUNDARY:** `KMAE_Database::count_unread_conversations()` → UNVERIFIED PLUGIN TARGET.  
**VERIFY:** producer value → `$kmae_unseen` → desktop badge / mobile dot / drawer badge.  
**STATUS:** VERIFIED theme consumer.

## R11 — Logged-in front page/dashboard is blank or shows “frissítsd a bővítményt”

**SYMPTOM:** front page does not show dashboard for logged-in user.  
**FEATURE:** Front-page routing + dashboard bridge.  
**START HERE:**  
**FILE:** `front-page.php`  
**SYMBOL:** auth branch `8–16`; then `template-parts/simple-mode/app-home.php` `12–34`.  
**EXPECTED:** logged in → app-home; if `KMAE_Dashboard` absent, explicit fallback message; if present, `get_data()` then plugin partial include.  
**NEXT IF TRUE:** if bridge reaches plugin data/include and output is wrong, **STOP THEME DEBUGGING**.  
**NEXT IF FALSE:** inspect template selection/auth branch only.  
**SEARCH TERMS:** `KMAE_Dashboard`, `KMAE_PLUGIN_DIR`, `dashboard-home.php`, `kmae-app-home`.  
**DO NOT LOAD:** landing template for logged-in-only failure; all CSS before confirming HTML.  
**PLUGIN BOUNDARY:** dashboard data + partial implementation UNVERIFIED PLUGIN TARGET.  
**VERIFY:** guest branch separately remains landing.  
**STATUS:** VERIFIED theme bridge.

## R12 — Guest landing content/link/media/logo is wrong

**SYMPTOM:** wrong landing CTA URL, wrong hero/image, wrong logo variant.  
**FEATURE:** Landing page + branding/media.  
**START HERE:**  
**FILE:** `template-parts/simple-mode/landing.php`  
**SYMBOL:** top-level URL/media variable block near `13–22`; then exact section emitting the affected element.  
**EXPECTED:** navigation/page URLs resolve through `kmae_page_url()`; hosted media through `kmae_media_url()`; logo through header helper.  
**NEXT IF TRUE:** if helper return is wrong, inspect `functions.php::kmae_page_url()` or `kmae_media_url()/kmae_logo_url()`.  
**NEXT IF FALSE:** inspect only the affected landing section.  
**SEARCH TERMS:** visible CTA text, `kmae_page_url`, `kmae_media_url`, `logo_guest`, media key.  
**DO NOT LOAD:** app dashboard, logged-in CSS, plugin business logic.  
**PLUGIN BOUNDARY:** URL/logo helpers may delegate to `KMAE_Pages` / `KMAE_Brand`; implementations UNVERIFIED.  
**VERIFY:** emitted href/src before CSS.  
**STATUS:** VERIFIED theme side.

## R13 — Browse/search page looks wrong

**SYMPTOM:** `/hirdetesek/` or `[kmae_browse]` layout/appearance is wrong.  
**FEATURE:** Browse presentation.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_is_browse_page()` (`82–102`) → `kmae_enqueue_browse()` (`478–489`) → `assets/css/browse.css`.  
**EXPECTED:** archive/page/shortcode detector true; `kmae-browse-page` body class; `kmae-browse` loads after `kmae-header` logged in or `kmae-landing` as guest.  
**NEXT IF TRUE:** if expected DOM values are wrong/missing, move to plugin/archive/shortcode boundary; if DOM correct but visual wrong, CSS only.  
**NEXT IF FALSE:** repair/inspect route detection/enqueue first.  
**SEARCH TERMS:** `kmae_listing`, `hirdetesek`, `kmae_browse`, `kmae-browse-page`, `body.kmae-browse-page`.  
**DO NOT LOAD:** complete plugin unless DOM/data producer is proven wrong.  
**PLUGIN BOUNDARY:** browse markup/search/filter behavior is not implemented by theme.  
**VERIFY:** guest + logged-in dependency chain.  
**STATUS:** VERIFIED theme routing.

## R14 — Listing-create wizard appearance is wrong

**SYMPTOM:** wizard cards, fields, progress, responsive layout are visually wrong.  
**FEATURE:** Listing-create presentation.  
**START HERE:**  
**FILE:** `assets/css/listing-create.css`  
**SYMBOL:** selector scope rooted in `.kmae-create-page` / `.kmae-wizard-*`; first confirm `functions.php::kmae_is_listing_create_page()` and `kmae_enqueue_listing_create_style()` (`740–750`) only if stylesheet/body class may be absent.  
**EXPECTED:** CSS loads on detected listing-create route; `page.php` direct-shortcodes raw content.  
**NEXT IF TRUE:** visual-only issue → remain in CSS / DOM selector.  
**NEXT IF FALSE:** if button behavior, validation, catalog data, upload, save, or AJAX is broken → **STOP THEME DEBUGGING** after confirming theme is not intercepting it.  
**SEARCH TERMS:** `kmae-listing-create-page`, `.kmae-create-page`, `.kmae-wizard-*`, `kmae_create_listing`.  
**DO NOT LOAD:** all theme files; theme has no listing-submit/AJAX engine.  
**PLUGIN BOUNDARY:** wizard behavior/data/submit is plugin-owned, implementation UNVERIFIED here.  
**VERIFY:** route → body class → stylesheet → DOM selector.  
**STATUS:** VERIFIED theme presentation boundary.

## R15 — Listing-create wizard button/validation/catalog/upload/save does not work

**SYMPTOM:** “Tovább”, back, catalog search, validation, upload, autosave, submit or other wizard behavior fails.  
**FEATURE:** Listing-create presentation boundary.  
**START HERE:**  
**FILE:** `page.php`  
**SYMBOL:** listing-create direct-render branch `71–74`; optionally `functions.php::kmae_enqueue_listing_create_style()`.  
**EXPECTED:** theme only renders shortcode output and styles it. No theme AJAX/REST/fetch/listing JS is present.  
**NEXT IF TRUE:** once plugin shortcode markup exists and theme does not block interaction, **STOP THEME DEBUGGING → PLUGIN MAP/SOURCE**.  
**NEXT IF FALSE:** if markup is not emitted because wrong page branch selected, remain in `page.php`.  
**SEARCH TERMS:** `kmae_create_listing`, `$is_listing_create`, `.kmae-wizard-*`.  
**DO NOT LOAD:** entire theme repository; there is no theme-side wizard engine to find.  
**PLUGIN BOUNDARY:** exact plugin JS/PHP target UNVERIFIED in this theme-only pass.  
**VERIFY:** raw shortcode output exists; then hand off.  
**STATUS:** VERIFIED ownership boundary.

## R16 — Chat page looks wrong vs chat action is broken

**SYMPTOM:** Üzenetek/chat page visually broken OR message send / file upload fails.  
**FEATURE:** Chat presentation.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_is_messages_page()`, app-shell CSS branch (`messages.css` / `offer-thread.css`).  
**EXPECTED:** theme owns detection/presentation; no theme AJAX/REST/business-action handler exists.  
**NEXT IF TRUE:** visual selectors wrong → CSS; count wrong → R10; action/refresh/data wrong → plugin boundary.  
**NEXT IF FALSE:** fix detector/enqueue.  
**SEARCH TERMS:** `kmae_is_messages_page`, `kmae_conversation`, `kmae-offer-thread-active`, `messages.css`; ajánlat/Foglalt UI: `kmae-chat-offer-bubble`, `kmae-chat-offer-accept`, `kmae-chat-system`, `kmae-chat-deal`, `kmae-single-sale-state`, `kmae-listing-seller__sold` (logika: plugin `KMAE_Chat_Offers`).    
**SZERZŐDÉS / MIRE FIGYELJ:** szerződés aloldal kinézet/nyomtatás → `sale-contract.css` (`kmae-sale-contract-active`, enqueue `kmae_contract` ág a `functions.php`-ban); chat jobb oszlop (állapot, Foglalt kártya, ellenőrzőlista, Mire figyelj, nagyító) → `offer-thread.css` `.kmae-chat-side`, `.kmae-chat-deal__contract`, `.kmae-chat-checklist`, `.kmae-chat-tips`, `.kmae-tips-viewer`; logika → plugin `KMAE_Sale_Contract`.
**VISSZAVONÁS / TÖRÖLT HIRDETÉS:** [Visszavonom] → `offer-thread.css` `.kmae-chat-offer-retract`; „Az eladó törölte a hirdetést.” sáv → `.kmae-chat-deleted`; Üzenetek lista címke → `messages.css` `.kmae-inbox__gone`; logika → plugin `FEATURES/CHAT.md`.  
**LEJÁRT HIRDETÉS OLDAL:** „Ez az autó már nem elérhető” kinézet → `listing-unavailable.css` (enqueue `kmae_enqueue_listing_unavailable`, vendégnek is; NEM a `single-listing.css`); markup/útválasztás → plugin `templates/listing-unavailable.php`, `KMAE_Post_Types::is_unavailable_for_viewer`.  
**BEJELENTÉS:** gomb/ablak kinézet → `offer-thread.css` / `single-listing.css` vége (`.kmae-report-open`, `dialog.kmae-report`); markup/mentés/admin → plugin `FEATURES/REPORTS.md`.  
**DO NOT LOAD:** unrelated listing-create CSS/JS.  
**PLUGIN BOUNDARY:** chat data/send/refresh → plugin `FEATURES/CHAT.md`.  
**VERIFY:** distinguish visual failure from data/action failure before loading source.  
**STATUS:** VERIFIED theme boundary.

## R17 — Profile/settings page looks wrong vs save action fails

**SYMPTOM:** profile/settings layout broken OR field/save behavior fails.  
**FEATURE:** Profile/settings presentation + raw shortcode rendering.  
**START HERE:**  
**FILE:** `page.php`  
**SYMBOL:** raw shortcode classifier `37–48` and direct render `86–89`; then relevant detector/enqueue in `functions.php`.  
**EXPECTED:** raw plugin markup is rendered without `the_content()` formatting; logged-in CSS loads via app shell.  
**NEXT IF TRUE:** if DOM is correct and only appearance is wrong → `profile.css` / `settings.css`; if save/data behavior fails → **STOP THEME DEBUGGING**.  
**NEXT IF FALSE:** inspect page classification / route detector.  
**SEARCH TERMS:** `kmae_profile`, `kmae_settings`, `profil`, `beallitasok`, `$is_raw_shortcode`.  
**DO NOT LOAD:** global JS for a server-side save failure; plugin DB before boundary confirmation.  
**PLUGIN BOUNDARY:** shortcode/form/save implementation UNVERIFIED.  
**VERIFY:** HTML source first, then CSS, then plugin only if behavior/data is wrong.  
**STATUS:** VERIFIED theme rendering.

## R18 — Mobile-only or desktop-only layout failure

**SYMPTOM:** same content is correct at one viewport and wrong at another.  
**FEATURE:** Any presentation feature.  
**START HERE:**  
**FILE:** the single active feature CSS file.  
**SYMBOL:** nearest selector + `@media` branch matching failing viewport.  
**EXPECTED:** HTML/data should be identical enough that breakpoint-specific cascade explains the difference.  
**NEXT IF TRUE:** if DOM/classes are already correct at both widths, **DO NOT** inspect backend/data; continue CSS specificity/order/media only.  
**NEXT IF FALSE:** if DOM actually changes by auth/route rather than viewport, route to shell/template instead.  
**SEARCH TERMS:** affected class + `@media`, `min-width`, `max-width`, body feature class.  
**DO NOT LOAD:** full backend, full plugin, every CSS file.  
**PLUGIN BOUNDARY:** only if plugin itself conditionally changes markup; not assumed.  
**VERIFY:** compare HTML/class list at good vs bad viewport before expanding context.  
**STATUS:** VERIFIED routing rule; exact CSS target depends on symptom.

## R19 — CSS selector exists but has no visible effect

**SYMPTOM:** expected class is in HTML, declaration exists, but UI is unchanged.  
**FEATURE:** CSS cascade.  
**START HERE:**  
**FILE:** active feature stylesheet.  
**SYMBOL:** exact selector; then its dependency/predecessor style handles from `functions.php`.  
**EXPECTED:** selector matches the DOM and wins cascade/order/specificity at current breakpoint.  
**NEXT IF TRUE:** if computed style shows declaration overridden, inspect only later relevant styles.  
**NEXT IF FALSE:** if selector does not match, return to template/plugin DOM producer; do not widen CSS search.  
**SEARCH TERMS:** exact class/ID, style handle, body scope.  
**DO NOT LOAD:** PHP data producer if HTML/classes are already correct.  
**PLUGIN BOUNDARY:** only for DOM ownership mismatch.  
**VERIFY:** DOM match → computed rule → cascade source → breakpoint.  
**STATUS:** VERIFIED negative-routing rule.

## R20 — Google completion screen style/gate is wrong

**SYMPTOM:** post-Google registration completion screen not detected/styled or appears when it should not.  
**FEATURE:** Google completion presentation.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_is_google_complete_page()` (`201–221`) → body class (`284–285`) → `kmae_enqueue_google_complete()` (`459–472`).  
**EXPECTED:** logged in + `?kmae_complete` + plugin `needs_completion(uid)` + registration page/shortcode; CSS depends on `kmae-public` and `kmae-header`.  
**NEXT IF TRUE:** if theme gate result follows plugin return but that return is wrong, **STOP THEME DEBUGGING**.  
**NEXT IF FALSE:** inspect request flag/page slug/shortcode and style dependencies.  
**SEARCH TERMS:** `kmae_complete`, `KMAE_Google_Auth`, `kmae-complete-page`, `kmae-google-complete`, `kmae-public`.  
**DO NOT LOAD:** chat/listings code.  
**PLUGIN BOUNDARY:** `KMAE_Google_Auth::needs_completion()` and `kmae-public` registration are UNVERIFIED PLUGIN TARGETS.  
**VERIFY:** all four gate predicates individually.  
**STATUS:** VERIFIED theme gate.

## R21 — How-it-works video click does nothing

**SYMPTOM:** video trigger does not open lightbox.  
**FEATURE:** How-it-works + video lightbox.  
**START HERE:**  
**FILE:** `assets/js/theme.js`  
**SYMBOL:** lightbox guard `214–218`, `embedUrl()` `223–229`, `openVideo()` `231–239`, delegated trigger `251–257`.  
**EXPECTED:** JS requires `#kmae-video-lightbox` and `#kmae-video-iframe` at startup; trigger requires `[data-kmae-video-id]`. These markup hooks are **not present in theme PHP**.  
**NEXT IF TRUE:** if all hooks exist in runtime DOM, inspect JS/event; if hook markup is absent, **STOP THEME DEBUGGING** and find external/plugin/page markup producer.  
**NEXT IF FALSE:** do not change JS to compensate for nonexistent markup without verifying owner.  
**SEARCH TERMS:** `kmae-video-lightbox`, `kmae-video-iframe`, `data-kmae-video-id`, `data-kmae-video-close`.  
**DO NOT LOAD:** complete CSS or plugin before confirming missing hook.  
**PLUGIN BOUNDARY:** markup producer UNVERIFIED EXTERNAL/PLUGIN TARGET.  
**VERIFY:** three required DOM hooks before JS debugging.  
**STATUS:** VERIFIED JS / UNVERIFIED markup owner.

## R22 — Phone sheet tabs do nothing

**SYMPTOM:** tab UI using `data-sheet-tab` does not switch.  
**FEATURE:** Optional global UI controller.  
**START HERE:**  
**FILE:** `assets/js/theme.js`  
**SYMBOL:** `initSheetTabs()` IIFE `127–152`, nested `showTab()` `133–144`.  
**EXPECTED:** root `[data-kmae-sheet]` must exist; no matching root exists in this theme snapshot.  
**NEXT IF TRUE:** runtime root exists → inspect data IDs/panels; root absent → **STOP THEME DEBUGGING**, locate external markup.  
**NEXT IF FALSE:** do not infer that `app-menu-panels.php` is this tab root: it emits class `.kmae-sheet`, not `data-kmae-sheet`.  
**SEARCH TERMS:** `data-kmae-sheet`, `data-sheet-tab`, `data-sheet-panel`.  
**DO NOT LOAD:** all theme templates after project-wide absence is established.  
**PLUGIN BOUNDARY:** UNVERIFIED EXTERNAL MARKUP.  
**VERIFY:** exact attribute, not class-name similarity.  
**STATUS:** VERIFIED JS / UNVERIFIED markup owner.

## R23 — Single listing styling is missing, especially for guest

**SYMPTOM:** `kmae_listing` single view does not receive expected glass/dark listing presentation.  
**FEATURE:** Single-listing presentation.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_body_classes()` single-listing branch `257–259`; `kmae_enqueue_app_shell()` early auth guard `378–381` and single-listing enqueue `407–414`.  
**EXPECTED:** (2026-10-11) `single-listing.css` is enqueued in `kmae_enqueue_app_shell()`, which runs when `kmae_uses_app_shell()` = logged in OR `kmae_is_guest_app_view()` (guest on singular listing / browse / front page). Guests there get the full logged-in shell: `kmae-app kmae-theme-dark kmae-guest-app` body, app CSS stack, `header.php` `$kmae_app_shell` topbar with Belépés/Regisztráció.  
**NEXT IF TRUE:** if symptom is guest-only, do not search CSS selectors first; the enqueue gate explains it.  
**NEXT IF FALSE:** for logged-in failure, verify handle/selector/cascade.  
**SEARCH TERMS:** `kmae-single-listing-page`, `kmae-single-listing`, `is_singular('kmae_listing')`.  
**DO NOT LOAD:** plugin business logic unless listing HTML/content is itself wrong.  
**PLUGIN BOUNDARY:** single template/markup owner is unverified.  
**VERIFY:** auth state + style handle in rendered page.  
**STATUS:** VERIFIED.

## R24 — Wrong Albira logo/media URL

**SYMPTOM:** logo variant or hosted landing image is wrong/missing.  
**FEATURE:** Branding + remote media.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_media_url()` (`559–586`) → `kmae_logo_url()` (`595–605`) → `kmae_logo_img()` (`612–619`).  
**EXPECTED:** `kmae_media_url()` uses hosted production URLs; `logo` maps to guest logo even after login; `KMAE_Brand` can override logo providers.  
**NEXT IF TRUE:** if theme helper return is correct but remote asset fails, investigate external URL/network asset, not template logic.  
**NEXT IF FALSE:** if `KMAE_Brand` return is wrong, cross plugin boundary.  
**SEARCH TERMS:** media key, `logo_guest`, `logo_app`, `KMAE_Brand`, `kmae_logo_img`.  
**DO NOT LOAD:** navigation/business logic.  
**PLUGIN BOUNDARY:** `KMAE_Brand::*` UNVERIFIED PLUGIN TARGET.  
**VERIFY:** helper return → emitted `src` → remote response.  
**STATUS:** VERIFIED theme helper.

## R25 — Theme JS appears not to run anywhere

**SYMPTOM:** drawer, dropdown, footer and other global interactions all fail together.  
**FEATURE:** Theme bootstrap/assets.  
**START HERE:**  
**FILE:** `functions.php`  
**SYMBOL:** `kmae_assets()` script enqueue `363–369`; then `assets/js/theme.js`.  
**EXPECTED:** `kmae-theme` script is enqueued for both guest and logged-in visitors in footer.  
**NEXT IF TRUE:** if script loads without syntax/runtime error, inspect first failing selector/handler; do not open plugin.  
**NEXT IF FALSE:** resolve enqueue/cache/script-loading issue.  
**SEARCH TERMS:** script handle `kmae-theme`, `/assets/js/theme.js`, `wp_footer`.  
**DO NOT LOAD:** feature plugin code.  
**PLUGIN BOUNDARY:** none for script enqueue.  
**VERIFY:** script tag, HTTP load, console exception, then handler-specific route.  
**STATUS:** VERIFIED.
