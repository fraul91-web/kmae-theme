# FEATURE — LISTING CREATE / WIZARD PRESENTATION

## PURPOSE
Detect listing-create route, choose direct shortcode rendering and apply the large wizard presentation layer.

## READ FIRST
- `functions.php::kmae_is_listing_create_page()`
- `functions.php::kmae_enqueue_listing_create_style()`
- `page.php` listing-create branch
- `assets/css/simple-mode/listing-create.css` (wizard + shared base)
- `assets/css/simple-mode/listing-simple.css` (simple editor `.is-simple` — single source)

## THEN IF NEEDED
- plugin feature map for `kmae_create_listing` markup/JS/data flow.

## DO NOT LOAD UNLESS RELEVANT
- other feature CSS; landing content.

## ENTRY POINTS
Slug `hirdetes-feladasa` or shortcode `kmae_create_listing`.

## DATA USED
Theme: only page slug/content/auth state. All form/listing data is plugin-owned.

## FLOW
Detector → body class → CSS enqueue → direct shortcode execution → plugin DOM → theme CSS.

## DEPENDENCIES
Strong DOM-class contract with plugin wizard markup.

## SIDE EFFECTS
Theme has none. No upload/save/validation handler exists in theme JS/PHP.

## CHANGE RISKS
This 5k-line CSS file styles many states generated elsewhere. Never infer wizard behavior from CSS alone. For logic bugs, go directly to plugin map after confirming wrapper/body class/CSS load.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** LISTING CREATE PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** wizard presentation wrong; feature CSS missing; wizard button/data/save behavior broken.

**READ FIRST:** visual route: `kmae_is_listing_create_page()` → `kmae_enqueue_listing_create_style()`; render: `page.php:26–28,71–74`; CSS: exact `.kmae-create-page/.kmae-wizard-*` selector.

**EXPECTED FLOW:** slug/shortcode → body class + CSS → direct raw shortcode output → plugin wizard DOM → theme presentation.

**CHECKPOINTS:** route/class/style? raw shortcode output? DOM selector? visual vs behavior/business?.

**IF X → GO TO:** visual → CSS; button/validation/catalog/upload/save/AJAX → plugin boundary after raw output check.

**SEARCH TERMS:** `hirdetes-feladasa`, `kmae_create_listing`, `kmae-listing-create-page`, `.kmae-create-page`, `.kmae-wizard-*`.

**DO NOT LOAD UNLESS:** full theme or all CSS; theme contains no verified wizard engine/AJAX/fetch.

**PLUGIN BOUNDARY:** wizard behavior/data/save is UNVERIFIED PLUGIN TARGET.

**CHANGE RISKS:** large stylesheet has many plugin-DOM selectors; do not edit generic `.kmae-wizard-*` without scope check.

**REGRESSION CHECKS:** route/body/style; raw rendering; failing breakpoint; one adjacent wizard step if generic selector changed.

**VERIFICATION STATUS:** VERIFIED theme presentation boundary.
