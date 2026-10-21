# FEATURE — SINGLE LISTING PRESENTATION

## PURPOSE
Apply single-listing body class, logged-in styling and correct navigation active state for owned listings.

## READ FIRST
- `functions.php` body class + `kmae_enqueue_app_shell()` singular branch
- `header.php::$kmae_own_listing`
- `assets/css/single-listing.css` = CSAK telefon (egész fájl `@media (max-width: 1024px)` alatt)
- `assets/css/single-listing-pc.css` = CSAK PC (1025px+, 2026-10-20 nulláról újraírva, `--lp-*` tokenek, minden szabály `.kmae-single-listing.kmae-listing-sheet` előtaggal); handle `kmae-single-listing-pc`, a `kmae-single-listing` után töltődik; a nagyító gomb PC-n a `.kmae-gallery`-t viszi natív teljes képernyőre (plugin `public.js` zoom-kezelő, CSS „2b. Teljes képernyős galéria” = a böngészés `browse-v2.css` fullscreen-blokkjának mása)

## THEN IF NEEDED
- plugin map for singular listing template and contact CTA/verification markup.

## DO NOT LOAD UNLESS RELEVANT
- page.php (singular CPT is not a normal page branch here).

## ENTRY POINT
`is_singular('kmae_listing')`.

## DATA USED
Theme reads post author/current user for nav state.

## FLOW
Singular query → body class → logged-in shell conditional stylesheet → plugin listing body → CSS.

## DEPENDENCIES
Listing DOM is external to theme.

## SIDE EFFECTS
None.

## CHANGE RISKS
In this snapshot `single-listing.css` is enqueued inside a logged-in-only callback. Verify intended guest behavior before assuming this stylesheet exists for logged-out listing views.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** SINGLE LISTING PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** single listing style missing; guest vs logged-in mismatch; own listing nav active wrong.

**READ FIRST:** `kmae_body_classes()` `257–259`; `kmae_enqueue_app_shell()` auth gate `378–381` + single enqueue `407–414`; own-listing nav `header.php:30–34`.

**EXPECTED FLOW:** singular query → body class; logged-in only → single-listing.css; header ownership read affects nav.

**CHECKPOINTS:** auth? body class? style handle? DOM selector? own author comparison?.

**IF X → GO TO:** guest-only missing style → enqueue gate explains; wrong listing content → plugin/template boundary; nav only → header.

**SEARCH TERMS:** `kmae_listing`, `kmae-single-listing-page`, `kmae-single-listing`, `$kmae_own_listing`.

**DO NOT LOAD UNLESS:** full plugin unless single DOM/content itself is wrong.

**PLUGIN BOUNDARY:** single/archive template behavior UNVERIFIED plugin/WP boundary.

**CHANGE RISKS:** comment says shared by guests/signed-in near enqueue function, but actual call lives behind logged-in app-shell early return; source behavior wins.

**REGRESSION CHECKS:** logged-in single; guest single; own vs non-own author nav.

**VERIFICATION STATUS:** VERIFIED including guest enqueue caveat.
