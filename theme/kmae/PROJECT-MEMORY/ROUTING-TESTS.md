# ROUTING TESTS — THEME V2

**MAP-TYPE:** VERIFICATION  
These are simulated localization tests. They test whether the map reaches the smallest likely source area; they do not modify source.

## TEST 01 — Mobile hamburger does not open

**USER SYMPTOM:** “Rányomok a hamburgerre, semmi nem történik.”  
**ROUTE:** DIAGNOSTIC R04 → logged-in/guest shell feature → `theme.js::setDrawerOpen()` → header IDs.  
**MAPS READ:** `DIAGNOSTIC-ROUTER.md`, one shell feature map.  
**SOURCE TARGETS:** `assets/js/theme.js::setDrawerOpen()` + listeners; `header.php` drawer/open/close markup.  
**EXPECTED SOURCE FILE COUNT:** 2.  
**WHY:** both event owner and required DOM hooks are theme-owned; plugin is irrelevant until proven otherwise.  
**RESULT:** PASS.

## TEST 02 — Listing wizard is broken only below 640px

**USER SYMPTOM:** “PC-n jó, mobilon szétesik a hirdetésfeladás.”  
**ROUTE:** R18 → listing-create feature → exact `.kmae-*` selector + relevant `@media` in `listing-create.css`; detector only if body class/style missing.  
**MAPS READ:** router + `listing-create-presentation.md`.  
**SOURCE TARGETS:** `assets/css/listing-create.css` first; optionally `functions.php::kmae_body_classes()/kmae_enqueue_listing_create_style()`.  
**EXPECTED SOURCE FILE COUNT:** 1–2.  
**WHY:** viewport-only failure with same DOM is CSS-first; no backend/plugin load.  
**RESULT:** PASS.

## TEST 03 — Profile form displays correctly but Save does nothing

**USER SYMPTOM:** “A profil szép, de a Mentés gomb nem ment.”  
**ROUTE:** R17 → confirm `page.php` raw shortcode path → plugin boundary.  
**MAPS READ:** router + `profile-presentation.md` + `PLUGIN-BOUNDARIES.md`.  
**SOURCE TARGETS:** `page.php::$is_raw_shortcode` / direct `do_shortcode()` only to confirm theme handoff.  
**EXPECTED SOURCE FILE COUNT:** 1 theme source file before plugin handoff.  
**WHY:** theme contains no save/AJAX engine; continuing theme search would waste context.  
**RESULT:** PASS.

## TEST 04 — Üzenetek badge says 3 but should say 1

**USER SYMPTOM:** “Az Üzenetek mellett rossz a kis számláló.”  
**ROUTE:** R10 → `header.php::$kmae_unseen` → plugin-return checkpoint; drawer cross-check via `kmae_app_menu_groups()`.  
**MAPS READ:** router + chat feature + plugin boundaries.  
**SOURCE TARGETS:** `header.php:21–23,200–205`; `functions.php::kmae_app_menu_groups()` only if drawer comparison needed.  
**EXPECTED SOURCE FILE COUNT:** 1–2.  
**WHY:** theme only casts/displays plugin count; wrong producer value ends theme debugging.  
**RESULT:** PASS.

## TEST 05 — Browse page has no browse-specific styling

**USER SYMPTOM:** “A /hirdetesek/ sima oldalnak néz ki.”  
**ROUTE:** R13 → detector → body class → enqueue → `browse.css`.  
**MAPS READ:** router + browse feature.  
**SOURCE TARGETS:** `functions.php::kmae_is_browse_page()`, `kmae_body_classes()`, `kmae_enqueue_browse()`; `assets/css/browse.css` only after gate passes.  
**EXPECTED SOURCE FILE COUNT:** 1–2.  
**WHY:** three checkpoints are concentrated in `functions.php`; no need for full repo.  
**RESULT:** PASS.

## TEST 06 — Logged-in home says “A dashboardhoz frissítsd a bővítményt.”

**USER SYMPTOM:** fallback message appears unexpectedly.  
**ROUTE:** R11 → `front-page.php` auth branch → `app-home.php` class gate → plugin boundary.  
**MAPS READ:** router + dashboard feature + boundary map.  
**SOURCE TARGETS:** `front-page.php`; `template-parts/simple-mode/app-home.php`.  
**EXPECTED SOURCE FILE COUNT:** 2.  
**WHY:** exact fallback is emitted only when `KMAE_Dashboard` is absent.  
**RESULT:** PASS.

## TEST 07 — Own listing highlights Browse instead of Hirdetéseim

**USER SYMPTOM:** “A saját autóm adatlapján a Keresés/Browse aktív.”  
**ROUTE:** R08 → `header.php::$kmae_own_listing` → desktop nav conditions.  
**MAPS READ:** router + logged-in navigation feature.  
**SOURCE TARGETS:** `header.php:24–34` + desktop nav array.  
**EXPECTED SOURCE FILE COUNT:** 1.  
**WHY:** source has an explicit own-listing exception; no plugin/business search needed.  
**RESULT:** PASS.

## TEST 08 — Footer arrow works visually nowhere

**USER SYMPTOM:** “A footer gomb nem nyitja le a láblécet.”  
**ROUTE:** R06 → `theme.js::setFooterOpen()` → `footer.php` IDs.  
**MAPS READ:** router + shell feature.  
**SOURCE TARGETS:** `assets/js/theme.js:105–124`; `footer.php:17–48`.  
**EXPECTED SOURCE FILE COUNT:** 2.  
**WHY:** exact theme-owned event/markup pair.  
**RESULT:** PASS.

## TEST 09 — Listing wizard “Tovább” button no-op

**USER SYMPTOM:** “A wizardban a Tovább gomb nem reagál.”  
**ROUTE:** R15 → verify shortcode output exists → immediate plugin handoff.  
**MAPS READ:** router + listing-create feature + boundary map.  
**SOURCE TARGETS:** `page.php:71–74`; optionally `functions.php::kmae_enqueue_listing_create_style()` only if UI overlay is suspected.  
**EXPECTED SOURCE FILE COUNT:** 1–2 theme files before plugin handoff.  
**WHY:** verified theme snapshot contains no wizard behavior engine/AJAX/fetch path.  
**RESULT:** PASS.

## TEST 10 — How-it-works video click no-op

**USER SYMPTOM:** “A videóra kattintok, semmi.”  
**ROUTE:** R21 → check runtime `#kmae-video-lightbox`, `#kmae-video-iframe`, `[data-kmae-video-id]` → if missing, external/plugin markup owner; if present, JS handler.  
**MAPS READ:** router + how-it-works feature.  
**SOURCE TARGETS:** `assets/js/theme.js:214–270`; `functions.php::kmae_is_how_it_works_page()/kmae_enqueue_how_it_works()` only if route/style is also wrong.  
**EXPECTED SOURCE FILE COUNT:** 1–2.  
**WHY:** project-wide theme search already proves the required markup is not emitted by theme source.  
**RESULT:** PASS.

## TEST 11 — Login/register output has strange `<p>` wrappers

**USER SYMPTOM:** “A belépés/regisztráció rácsa szétesett, mintha WordPress bekezdéseket rakna bele.”  
**ROUTE:** R03 → `page.php::$is_raw_shortcode` → direct `do_shortcode`.  
**MAPS READ:** router + page-shortcode feature.  
**SOURCE TARGETS:** `page.php:37–48,79–95`.  
**EXPECTED SOURCE FILE COUNT:** 1.  
**WHY:** render mode, not global CSS/backend, is the first discriminating checkpoint.  
**RESULT:** PASS.

## TEST 12 — Guest single listing has no single-listing theme

**USER SYMPTOM:** “Belépve szép az autó adatlap, kijelentkezve nem.”  
**ROUTE:** R23 → `kmae_enqueue_app_shell()` auth guard → single-listing enqueue.  
**MAPS READ:** router + single-listing feature.  
**SOURCE TARGETS:** `functions.php:378–381,407–414`.  
**EXPECTED SOURCE FILE COUNT:** 1.  
**WHY:** the source explicitly returns before `single-listing.css` for guests; no selector hunt needed first.  
**RESULT:** PASS.

## TEST 13 — Entire logged-in shell collapses on several unrelated pages

**USER SYMPTOM:** header/content/footer alignment is broken everywhere after a shell edit.  
**ROUTE:** HOTSPOT H08 → cross-file tag pairing → shell CSS if DOM nesting is valid.  
**MAPS READ:** router + hotspot map + logged-in shell feature.  
**SOURCE TARGETS:** `header.php:265–270`, `footer.php:10–15`; then `app-shell-dark.css` / `app.css` if nesting correct.  
**EXPECTED SOURCE FILE COUNT:** 2–4.  
**WHY:** multi-feature symptom points to shared structural hotspot, not each feature separately.  
**RESULT:** PASS.

## TEST 14 — Logo is wrong everywhere but links/navigation work

**USER SYMPTOM:** “Mindenhol rossz Albira logó jelenik meg.”  
**ROUTE:** R24 → `kmae_logo_img()` → `kmae_logo_url()` → `KMAE_Brand` / fallback.  
**MAPS READ:** router + branding feature + boundary map if plugin return involved.  
**SOURCE TARGETS:** `functions.php:559–619`.  
**EXPECTED SOURCE FILE COUNT:** 1.  
**WHY:** centralized helper explains multiple surfaces with one source target.  
**RESULT:** PASS.

## TEST 15 — Drawer badge differs from desktop badge

**USER SYMPTOM:** “Fent 2 olvasatlan üzenet van, a hamburger menüben 0.”  
**ROUTE:** R09/R10 → compare two independently built theme consumers of same plugin unseen-count method.  
**MAPS READ:** router + logged-in navigation feature.  
**SOURCE TARGETS:** `header.php:21–23,200–205`; `functions.php:641–677`; `app-menu-panels.php:28–30` only if model is right.  
**EXPECTED SOURCE FILE COUNT:** 2–3.  
**WHY:** this is a real dual-render path; comparison localizes theme-vs-producer quickly.  
**RESULT:** PASS.

## Summary

- **ROUTING TESTS RUN:** 15 simulated cases.
- **PASS:** 15.
- **FAIL:** 0.
- **MAX INITIAL THEME SOURCE FILES:** 4 in the cross-shell structural case; normal cases stay at 1–3.
- **QUALITY CHECK:** no test requires default full repository, full symbol map, all CSS, all JS, or full plugin load.
