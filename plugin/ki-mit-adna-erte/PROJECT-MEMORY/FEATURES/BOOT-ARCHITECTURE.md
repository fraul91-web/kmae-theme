# FEATURE — Bootstrapping, lifecycle and architecture

## PURPOSE
Plugin indulása, class betöltés, inicializálási sorrend, aktiválás/frissítés/deaktiválás, globális konstansok.

## READ FIRST
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`

## THEN IF NEEDED
- `includes/class-kmae-settings.php`

## DO NOT LOAD UNLESS RELEVANT
- `public/class-kmae-shortcodes.php`
- `public/class-kmae-public.php`
- `templates/form-login.php`
- `templates/form-register.php`
- `templates/partials/google-button.php`


## ENTRY POINTS
- WordPress loads `ki-mit-adna-erte.php`.
- Activation: `register_activation_hook(... KMAE_Activator::activate)`.
- Deactivation: `register_deactivation_hook(... KMAE_Deactivator::deactivate)`.
- Normal runtime: `kmae_run()` → `KMAE_Plugin::run()`.

## IMPORTANT SYMBOLS
- `kmae_run`
- `KMAE_Loader::load_dependencies`
- `KMAE_Plugin::run`
- `KMAE_Activator::{activate,maybe_upgrade,create_tables,schedule_cron,migrate_price_modes}`
- `KMAE_Deactivator::deactivate`

## DATA USED
- option `kmae_db_version`
- option `kmae_price_modes_migrated`
- settings defaults
- seven custom DB tables

## FLOW
See `../FLOWS.md` sections 1–2.

## DEPENDENCIES
Every domain class is loaded through the loader before runtime initialization. `KMAE_Plugin::run()` defines the meaningful initialization order.

## SIDE EFFECTS
Activation creates/updates schema, pages, taxonomy terms, capabilities, rewrite rules and cron.

## CHANGE RISKS
Changing bootstrap order can break class availability or hook registration. Schema version changes must stay aligned with `maybe_upgrade()`.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-auth.php`
- `includes/class-kmae-brand.php`
- `includes/class-kmae-dashboard.php`
- `includes/class-kmae-google-auth.php`
- `includes/class-kmae-images.php`
- `includes/class-kmae-listing-drafts.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-notifications.php`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-post-types.php`
- `includes/class-kmae-taxonomies.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Boot/architecture  
**COMMON SYMPTOMS:** load/init/hook registration.  
**READ FIRST (symbol-level):** `ki-mit-adna-erte.php::kmae_run()` → `KMAE_Plugin::run()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `kmae_run KMAE_Plugin run loader`.  
**DO NOT LOAD UNLESS:** feature implementation once hook/init exists.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## Asset versioning (2026-10-03)
`KMAE_Public::asset_version_src()` (style/script_loader_src filters) sets `ver=filemtime` for every plugin asset under `KMAE_PLUGIN_URL` — no stale JS/CSS after a zip upload (Cloudflare + browser cache). `KMAE_VERSION` is no longer what busts caches.
