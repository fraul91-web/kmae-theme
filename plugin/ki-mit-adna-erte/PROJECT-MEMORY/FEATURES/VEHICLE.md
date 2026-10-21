# FEATURE — Vehicle-specific data and catalog

## PURPOSE
Autó-specifikus mezők, lokális márka/modell katalógus, címképzés, browse módok és meta mentés. A wizard kártyarajzai (`KMAE_Vehicle_Icons`) jelennek meg mindenhol, ahol ugyanaz az adat látszik.

## READ FIRST
- `includes/class-kmae-vehicle.php`
- `assets/data/vehicle-catalog.json`
- `assets/js/vehicle-catalog.js`
- `templates/partials/vehicle-form-fields.php`

## THEN IF NEEDED
- `templates/partials/vehicle-specs-display.php`
- `includes/class-kmae-launch.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
Vehicle listing form fields, catalog-localized JS, browse wizard filters.

## IMPORTANT SYMBOLS
- `KMAE_Vehicle::{parse_post_data,save_from_post,get_missing_required,catalog_payload,get_specs,build_title_from_specs,get_vehicle_value}`
- `KMAE_Launch::{is_single_category,get_launch_category_id}`

## DATA USED
Vehicle postmeta constants; static `assets/data/vehicle-catalog.json`; hardcoded logo-base URLs; launch category taxonomy.

## FLOW
POST fields → `parse_post_data()` → moderation required-field validation → listing created/updated → `save_from_post()` sanitizes and persists → launch category applied. On the wizard the popular-brand grid stays visible; “Összes márka/modell megtekintése” sits under the field, right-aligned, and a click outside closes the opened full list. Typing filters both brand and model by prefix and still leaves the view-all link; no match shows the manual-entry action. A custom brand keeps the model field as free text.

## DEPENDENCIES
Moderation and browse call vehicle validators/value/body-mode helpers.

## SIDE EFFECTS
Writes vehicle postmeta and taxonomy term; traits capped at 3.

## CHANGE RISKS
Field keys are shared by PHP, templates, wizard JS, catalog JS and browse filters. A rename is multi-file. Browse, wizard and the simple-edit select share `body_types()`, which is the photo-card set: sedan, estate, hatchback, suv, coupe, van, cabrio, mpv, pickup, offroad, other. Retired slugs (crossover, roadster, limousine, sport, classic, caravan, micro) normalize to `other`.


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Vehicle/catalog  
**COMMON SYMPTOMS:** brand/model/spec values and catalog.  
**READ FIRST (symbol-level):** `assets/js/vehicle-catalog.js`; `KMAE_Vehicle`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `vehicle catalog brand model traits`.  
**DO NOT LOAD UNLESS:** chat.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
