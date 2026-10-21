# FEATURE — GUEST LANDING PAGE

> **2026-10-11: NEM HASZNÁLT.** A vendég főoldal már a böngészés (`front-page.php` → `[kmae_browse]`), a `kmae_enqueue_landing_home()` törölve. A `landing.php`, `landing-home.css/js` fájlok csak megmaradtak (nincs betöltve). Lásd `FEATURES/front-page-routing.md`.


## PURPOSE
Render the complete public front-page marketing content and route CTAs to browse/listing-create.

## READ FIRST
- `template-parts/simple-mode/landing.php`
- `assets/css/landing-home.css` (all landing section styles, scoped under `.kmae-lp`)
- `assets/js/landing-home.js` (scroll reveal: `[data-lp-reveal]` → `is-in` → `is-done`)

`assets/css/simple-mode/landing.css` now holds ONLY the shared guest shell (body background, guest header, footer, auth pages) — no landing sections. The header Belépés/Regisztráció pills moved to `assets/css/simple-mode/header-auth.css` (2026-10-11), loaded for every logged-out page, so they look the same on the front page and on guest app pages.

## THEN IF NEEDED
- `functions.php::kmae_media_url()`
- `functions.php::kmae_page_url()`
- `front-page.php`

## DO NOT LOAD UNLESS RELEVANT
- plugin listing-create/browse implementation;
- logged-in dashboard files.

## ENTRY POINT
`front-page.php` when logged out.

## PRIMARY SECTIONS (rebuilt 2026-10-11, no mascot by owner's rule)
`.kmae-lp-hero` (copy + animated product demo `.kmae-lp-stage`: listing card → chat offers → „Foglalt” stamp → contract toast, CSS 12s loop `kmae-lp-seq-*`); `.kmae-lp-modes` (3 price modes, copy mirrors plugin `KMAE_Seller::price_mode_defs()`); `.kmae-lp-journey` (6 seller steps); `.kmae-lp-buyer`; `.kmae-lp-trust`. (A záró „Nézzük meg, ki mit adna érte” kártya 2026-10-11 törölve.) Root keeps class `kmae-landing` for the desktop `zoom` hook in `landing.css`.

## DATA USED
Car cut-out images from `kmae_media_url( 'landing_car_*' )` and routes from `kmae_page_url()` (`hirdetesek`, `hirdetes-feladasa`, `hogyan-mukodik`). Enqueue: `functions.php::kmae_enqueue_landing_home()` (guest + `is_front_page()`, priority 100).

## FLOW
Front-page guest branch → landing variables → static translated markup → CTA links.

## DEPENDENCIES
Remote production uploads; plugin URL provider optional through `KMAE_Pages`.

## SIDE EFFECTS
None.

## CHANGE RISKS
The page background image (`landing-hatter.webp`) lives in `landing.css` body rule and is shared with guest inner/auth pages — do not change it for landing-only work. Since 2026-10-11 the same image is also the background of ALL pages via `assets/css/site-background.css` (loaded last) — keep both in sync when the image changes.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** LANDING PAGE  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** guest hero/CTA/link/media wrong; landing section visually wrong.

**READ FIRST:** `front-page.php:8–16` → `template-parts/simple-mode/landing.php` affected section → `landing-home.css`.

**EXPECTED FLOW:** guest front page → landing template variables/helpers → section markup → guest CSS.

**CHECKPOINTS:** correct branch? href/src correct in HTML? selector matches? responsive rule?.

**IF X → GO TO:** URL/logo helper wrong → branding/boundary; markup correct visual wrong → landing CSS.

**SEARCH TERMS:** visible section text, `kmae_page_url`, `kmae_media_url`, section class, media key.

**DO NOT LOAD UNLESS:** logged-in app/dashboard.

**PLUGIN BOUNDARY:** URL/logo helpers can delegate to plugin; landing composition itself is theme-owned.

**CHANGE RISKS:** landing.css affects all guest pages; landing-only styles go in `landing-home.css`.

**REGRESSION CHECKS:** front-page guest section + shared guest header/footer + relevant breakpoint.

**VERIFICATION STATUS:** VERIFIED.
