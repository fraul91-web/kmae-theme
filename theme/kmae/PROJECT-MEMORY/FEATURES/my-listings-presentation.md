# FEATURE — MY LISTINGS PRESENTATION

## PURPOSE
Detect and style the user’s listing-management page.

## READ FIRST
- `functions.php::kmae_is_my_listings_page()`
- `functions.php::kmae_enqueue_app_shell()` my-listings branch
- `page.php` my-listings branch
- `assets/css/my-listings.css`

## THEN IF NEEDED
- plugin map for `kmae_my_listings` and any `my-listings.js` behavior.

## DO NOT LOAD UNLESS RELEVANT
- generic landing/dashboard files.

## ENTRY POINT
Slug `sajat-hirdetesek` or shortcode `kmae_my_listings`.

## FLOW
Detector → body class → CSS → `page.php` uses `the_content()` → plugin shortcode output.

## DEPENDENCIES
CSS references delete-confirm/draft/card markup and a comment names `my-listings.js`, absent from theme.

## SIDE EFFECTS
Theme has none.

## CHANGE RISKS
Delete/edit behavior must not be inferred from CSS. Theme owns only wrapper/style/nav state.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** MY LISTINGS PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** my-listings style/body class missing; page wrapper wrong; listing data/action wrong.

**READ FIRST:** `kmae_is_my_listings_page()` (`104–119`) → `kmae_body_classes()` → app-shell enqueue `416–423`; `page.php:29–31,75–78`.

**EXPECTED FLOW:** route detector → body class/CSS → `the_content()` → plugin shortcode/listing output.

**CHECKPOINTS:** detector/style? page branch? actual data/DOM correct?.

**IF X → GO TO:** data/action wrong → plugin boundary; DOM correct visual wrong → my-listings.css.

**SEARCH TERMS:** `sajat-hirdetesek`, `kmae_my_listings`, `kmae-my-listings-page`, `kmae-my-listings`.

**DO NOT LOAD UNLESS:** listing-create wizard, chat backend.

**PLUGIN BOUNDARY:** my-listings data/actions are UNVERIFIED plugin implementation.

**CHANGE RISKS:** page.php intentionally uses `the_content()` here, unlike listing-create/raw profile paths.

**REGRESSION CHECKS:** route/body/style + page branch + desktop/mobile selector changed.

**VERIFICATION STATUS:** VERIFIED theme route/presentation.
