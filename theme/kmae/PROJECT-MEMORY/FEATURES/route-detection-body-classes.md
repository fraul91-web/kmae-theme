# FEATURE — ROUTE DETECTION + BODY CLASSES

## PURPOSE
Translate WordPress query state, slugs and known shortcode text into feature flags/body classes used by CSS and enqueue logic.

## READ FIRST
- `functions.php:65-289`.

## THEN IF NEEDED
- `page.php` for separate rendering classification;
- relevant feature CSS.

## DO NOT LOAD UNLESS RELEVANT
- `landing.php`;
- dashboard CSS;
- plugin source until a shortcode/route contract must be verified.

## ENTRY POINTS
- `body_class` filter → `kmae_body_classes()`.
- each enqueue callback calls one detector again.

## IMPORTANT SYMBOLS
`kmae_is_listing_create_page`, `kmae_is_browse_page`, `kmae_is_my_listings_page`, `kmae_is_how_it_works_page`, `kmae_is_faq_page`, `kmae_is_contact_page`, `kmae_is_profile_page`, `kmae_is_google_complete_page`, `kmae_is_settings_page`.

## DATA USED
WP page/archive/singular state, `post_name`, `post_content`, shortcodes, auth, `kmae_complete` query flag.

## FLOW
WP query → detector → body class + feature stylesheet decision.

## DEPENDENCIES
Known plugin shortcode names are hardcoded contracts.

## SIDE EFFECTS
None beyond returned CSS classes / enqueue decisions.

## CHANGE RISKS
A slug or shortcode rename can silently stop body classes and feature CSS. `page.php` has parallel classification logic, so route-contract changes may need updates in both files.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** ROUTE DETECTION BODY CLASSES  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** wrong feature recognized; wrong body class; style loads on wrong page or not at all.

**READ FIRST:** exact `kmae_is_*_page()` in `functions.php` + `kmae_body_classes()` (`247–288`).

**EXPECTED FLOW:** WP query + slug + raw content/shortcode → boolean detector → body class + related enqueue.

**CHECKPOINTS:** query type? slug? shortcode? detector? class? enqueue?.

**IF X → GO TO:** detector false unexpectedly → exact predicate; detector true/class missing → body filter; class right/style missing → enqueue.

**SEARCH TERMS:** route slug, shortcode, `kmae_is_`, `body_class`, `kmae-*-page`.

**DO NOT LOAD UNLESS:** templates/CSS/plugin until routing checkpoint is correct.

**PLUGIN BOUNDARY:** some query/template origin can be plugin-owned but detectors are theme-owned.

**CHANGE RISKS:** same detector is reused by body/enqueue; page.php duplicates some classification separately.

**REGRESSION CHECKS:** positive + negative route for changed detector; corresponding body and handle.

**VERIFICATION STATUS:** VERIFIED.
