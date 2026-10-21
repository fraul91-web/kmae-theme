# FEATURE — Post type, taxonomy and launch mode

## PURPOSE
kmae_listing CPT, kmae_category taxonomy, seed kategóriák és single-category jármű launch korlátozás.

## READ FIRST
- `includes/class-kmae-post-types.php`
- `includes/class-kmae-taxonomies.php`
- `includes/class-kmae-launch.php`

## THEN IF NEEDED
- `ki-mit-adna-erte.php`
- `includes/class-kmae-vehicle.php`

## DO NOT LOAD UNLESS RELEVANT
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`
- `includes/class-kmae-pages.php`


## ENTRY POINTS
`init` registers CPT/taxonomy; activation seeds categories; launch helpers shape forms/browse.

## IMPORTANT SYMBOLS
`KMAE_Post_Types::{register,register_post_type,template_loader}`, `KMAE_Taxonomies::{register,register_taxonomy,seed_categories}`, `KMAE_Launch`, `KMAE_Vehicle::is_vehicle_launch`.

## DATA USED
CPT `kmae_listing`; taxonomy `kmae_category`; launch constants `KMAE_LAUNCH_MODE=single`, `KMAE_LAUNCH_CATEGORY=jarmu`.

## FLOW
Boot → init registration → single launch exposes only launch category to relevant helpers → vehicle feature becomes active → listing save forces launch category.

## DEPENDENCIES
Browse/listing/wizard behavior branches on launch state.

## SIDE EFFECTS
Taxonomy seeding and rewrite registration.

## CHANGE RISKS
Changing launch mode/category has broad effects across browse, wizard, vehicle validation and category selection.


## V2 DIAGNOSTIC ROUTING

**FEATURE:** CPT/taxonomy/launch  
**COMMON SYMPTOMS:** registration, seed, launch mode.  
**READ FIRST (symbol-level):** `KMAE_Post_Types`; `KMAE_Taxonomies`; `KMAE_Launch`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `register_post_type register_taxonomy launch`.  
**DO NOT LOAD UNLESS:** business flows.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
