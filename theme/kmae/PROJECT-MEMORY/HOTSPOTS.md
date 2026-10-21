# HOTSPOTS — THEME V2

**MAP-TYPE:** ON-DEMAND  
A hotspot is listed because multiple real flows converge on it or a change has broad regression reach — **not** because the file is large.

## H01 — Global asset split

**FILE:** `functions.php`  
**SYMBOL:** `kmae_assets()` (`319–371`)  
**USED BY:** every front-end request.  
**WHY SENSITIVE:** selects guest vs logged-in font/base CSS stack and always loads global theme JS. A dependency/order change can affect nearly every page.  
**VERIFY AFTER CHANGE:** guest landing + guest inner page; logged-in home + one feature page; theme.js presence; style dependency order.  
**STATUS:** VERIFIED.

## H02 — Global body-class router

**FILE:** `functions.php`  
**SYMBOL:** `kmae_body_classes()` (`247–288`)  
**USED BY:** global auth shell plus listing-create, single-listing, my-listings, browse, offers, how-it-works, FAQ, contact, profile, settings, Google-complete CSS scopes.  
**WHY SENSITIVE:** CSS routing relies on these classes; changing a detector can silently disable many selectors.  
**VERIFY AFTER CHANGE:** affected detector + class + stylesheet on target route; auth base classes unchanged.  
**STATUS:** VERIFIED.

## H03 — Canonical page URL helper

**FILE:** `functions.php`  
**SYMBOL:** `kmae_page_url()` (`58–63`)  
**USED BY:** header, footer, landing, bottom nav, drawer/menu groups.  
**WHY SENSITIVE:** one resolver fans out to most navigation surfaces and optionally delegates to plugin.  
**VERIFY AFTER CHANGE:** guest nav, logged-in desktop nav, drawer, footer links, bottom nav, landing CTAs.  
**STATUS:** VERIFIED.

## H04 — Logged-in app CSS orchestrator

**FILE:** `functions.php`  
**SYMBOL:** `kmae_enqueue_app_shell()` (`378–452`)  
**USED BY:** all logged-in pages; conditionally single-listing, my-listings, offers, profile, settings.  
**WHY SENSITIVE:** priority-99 shell and feature CSS dependencies define late cascade; early auth return also explains guest behavior.  
**VERIFY AFTER CHANGE:** logged-in base shell plus each changed conditional route; guest pages receive none of these handles.  
**STATUS:** VERIFIED.

## H05 — `page.php` classifier/render switch

**FILE:** `page.php`  
**SYMBOL:** classifier `10–52`; render switch `54–97`  
**USED BY:** hub pages, listing-create, my-listings, profile, settings, login, registration, generic WP pages.  
**WHY SENSITIVE:** chooses `the_content()` vs direct `do_shortcode(raw content)` and wrapper structure. A small predicate change can alter plugin DOM shape across multiple pages.  
**VERIFY AFTER CHANGE:** one route from every branch: hub, listing-create, my-listings, raw profile/settings/auth, ordinary page.  
**STATUS:** VERIFIED.

## H06 — Header navigation/state model

**FILE:** `header.php`  
**SYMBOL:** state block `8–48`; desktop logged-in nav `153–208`; profile dropdown `213–225`; drawer shell `242–263`  
**USED BY:** every request via `get_header()`.  
**WHY SENSITIVE:** auth state, user identity, unseen count, active-state logic, own-listing exception, guest nav, mobile drawer and profile controls converge here.  
**VERIFY AFTER CHANGE:** guest header; logged-in desktop; logged-in mobile; own listing; Üzenetek path; profile dropdown; drawer IDs.  
**STATUS:** VERIFIED.

## H07 — Shared drawer menu model

**FILE:** `functions.php`  
**SYMBOL:** `kmae_app_menu_groups()` (`626–737`)  
**USED BY:** `template-parts/app-menu-panels.php`; plugin counts and URL resolver.  
**WHY SENSITIVE:** one model drives many drawer links, active states and two count sources; its active-state logic is independent from desktop header.  
**VERIFY AFTER CHANGE:** each nav/info URL; Hirdetéseim/Üzenetek badges; active paths; icons.  
**STATUS:** VERIFIED.

## H08 — Header/footer app-layout tag pairing

**FILE:** `header.php` + `footer.php`  
**SYMBOL:** header opens `.kmae-app-layout/.kmae-app-content` at `265–268`; footer closes at `12–15`; `<main>` opens `header.php:270` and closes `footer.php:10`.  
**USED BY:** every logged-in theme-rendered page.  
**WHY SENSITIVE:** unbalanced structure breaks the entire app canvas, not one feature.  
**VERIFY AFTER CHANGE:** DOM nesting on logged-in home, generic page, plugin shortcode page; guest DOM has no app wrappers.  
**STATUS:** VERIFIED.

## H09 — Global UI JavaScript bundle

**FILE:** `assets/js/theme.js`  
**SYMBOL:** whole IIFE, but route by named handlers: `setDrawerOpen`, dropdown handlers, `setFooterOpen`, bottom-nav block, guest-nav block, lightbox block.  
**USED BY:** script is loaded on every front-end request.  
**WHY SENSITIVE:** a top-level runtime error can disable unrelated UI controls after the failing point; lightbox has an early `return` at `216–218` only after all prior handlers have registered.  
**VERIFY AFTER CHANGE:** scroll header, drawer, dropdown, footer, bottom nav, guest nav; lightbox only where hooks exist.  
**STATUS:** VERIFIED.

## H10 — Global CSS base

**FILE:** `assets/css/theme.css`  
**SYMBOL:** global `:root`, `body`, container/button/form/footer/bottom-nav base rules.  
**USED BY:** every request through handle `kmae-theme`.  
**WHY SENSITIVE:** all guest/app layers depend on it; broad selectors can alter multiple features.  
**VERIFY AFTER CHANGE:** guest + logged-in typography/container/forms/controls; one mobile and desktop viewport.  
**STATUS:** VERIFIED.

## H11 — Guest-wide CSS layer

**FILE:** `assets/css/simple-mode/landing.css`  
**SYMBOL:** `body.kmae-marketing` and shared guest header/main/page rules.  
**USED BY:** **all guest requests**, not only the front landing, because `kmae_assets()` enqueues it whenever logged out.  
**WHY SENSITIVE:** a “landing-only” broad selector can affect guest login/register/contact/browse/how-it-works pages.  
**VERIFY AFTER CHANGE:** guest front page + at least one guest inner page affected by shared selector.  
**STATUS:** VERIFIED.

## H12 — Dashboard direct plugin include bridge

**FILE:** `template-parts/simple-mode/app-home.php`  
**SYMBOL:** `12–34`  
**USED BY:** logged-in front page.  
**WHY SENSITIVE:** this is the theme's only explicit PHP include/require boundary and directly depends on plugin class/constant/template contract.  
**VERIFY AFTER CHANGE:** class-absent fallback, class-present data call, include path/output.  
**STATUS:** VERIFIED.

## page.php rendering (2026-10-03)
- `kmae_my_listings` page now renders the shortcode directly (`do_shortcode`, no `the_content()`/wpautop), like listing-create and the `$is_raw_shortcode` pages — wpautop `<p>/<br>` broke the card grid and the delete dialog. Any new grid-heavy shortcode page should use the same raw path.
