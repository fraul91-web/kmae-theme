# FEATURE — Listing wizard and autosaved drafts

## PURPOSE
Többlépéses hirdetés-wizard frontend állapot, validáció, előnézet, autosave draft és folytatás.

## READ FIRST
- `assets/js/listing-wizard.js`
- `includes/class-kmae-listing-wizard.php`
- `includes/class-kmae-listing-drafts.php`
- `templates/partials/listing-wizard.php`

## THEN IF NEEDED
- `assets/js/listing-wizard-preview.js`
- `assets/js/listing-field-guards.js`
- `assets/js/listing-form-numbers.js`
- `templates/partials/listing-form-layout.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
Create-listing page JS boot; AJAX `kmae_save_listing_draft`; AJAX `kmae_locate` (location button under Megye/Város, wizard only → `KMAE_Counties::ajax_locate`, OpenStreetMap — see `../EXTERNAL-INTEGRATIONS.md`).

Existing listing edit and `kmae_mode=simple` drafts render `.kmae-create-page.is-simple` from `listing-form-layout.php`. Edit query `kmae_mode=full` keeps the previous stacked form. New listings stay on the wizard (`is-wizard-active`, no `is-simple`). Simple-editor chrome is theme `listing-create.css` under `.is-simple`. On that page the photo uploader lives in the vehicle section (`kmae-simple-photos`); section 05 is not rendered.

Optional forgalmi scan (`KMAE_Registration_Scan`, see `../EXTERNAL-INTEGRATIONS.md`): wizard emits `kmae:wizard-ready {fresh}` from `activate()`; exposes `window.kmaeWizard.applyVehicleData(data)`; modal markup `templates/partials/registration-scan.php` (included at the end of `listing-wizard.php`), styles `assets/css/registration-scan.css`.

## IMPORTANT SYMBOLS
- `KMAE_Public::enqueue_listing_wizard`
- `KMAE_Listing_Drafts::{ajax_save,get_user_draft,get_latest_draft,to_client,discard_submitted}`
- `KMAE_Listing_Wizard` assistant/phase configuration
- named functions in `assets/js/listing-wizard.js` (see `../FRONTEND-MAP.md`).

## DATA USED
Draft `kmae_listing` post + `_kmae_status=draft` + `_kmae_wizard_draft` payload.

## FLOW
See `../FLOWS.md` section 7.

## DEPENDENCIES
Vehicle catalog, numeric guards, photo motor, create-listing final PHP handler.

## SIDE EFFECTS
Creates/updates user-owned draft posts; final submit discards submitted draft.

## CHANGE RISKS
Client-side field/stage names must remain compatible with server parsing and draft payload restoration. UI-only validation is not authoritative.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-dashboard.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Wizard/drafts  
**COMMON SYMPTOMS:** autosave/restore/stage state.  
**READ FIRST (symbol-level):** `assets/js/listing-wizard.js`; `KMAE_Listing_Drafts::ajax_save()/to_client()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `kmae_save_listing_draft ajax_save to_client`.  
**DO NOT LOAD UNLESS:** final listing pipeline unless autosave succeeds and final submit fails.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## Step order (2026-10-03)
Auto phase order: brand, model, year, transmission, power, engine_cc, fuel, **mot_has, mot_date**, mileage, body, condition, traits. Everything the forgalmi scan can fill (PREFILL_STEPS in listing-wizard.js) sits contiguously at the start, so after `applyVehicleData` the counter/car jump once to the first missing step (km állás) and manual fill uses the same order. Keep prefillable steps before non-prefillable ones. Sidebar rows (`listing-wizard.php` $preview_rows + `listing-wizard-preview.js` rows[]) must follow the same order.

Draft payload also carries `scanned` (set by applyVehicleData): when true, the sidebar „Kitöltés a forgalmiból” button and the auto-offer stay hidden (wizard-ready detail.scanned). Skipping/discarding the modal keeps the button for manual flow; using it mid-way jumps to the first missing step like at the start.
