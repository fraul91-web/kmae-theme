# FEATURE — Automated listing moderation

## PURPOSE
Kódalapú publish-gate: kötelező mezők, minimum leírás, fotók, kontaktadat tiltás és hiba-visszaadás.

## READ FIRST
- `includes/class-kmae-moderation.php`
- `public/class-kmae-public.php`

## THEN IF NEEDED
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-photo-motor.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
`KMAE_Public::process_create_listing()` and `admin_update_listing()`.

## IMPORTANT SYMBOLS
`KMAE_Moderation::{validate,contains_contact_info,count_uploaded_images,stash_errors_for_user,pull_errors_for_user}`.

## DATA USED
Option `kmae_auto_moderation`; transient `kmae_mod_errors_<user>`; posted listing/vehicle fields; current upload count.

## FLOW
When enabled, validates vehicle required fields, description ≥ 80 chars, required photo count when requested, no detected phone/e-mail in title/description, non-empty title → returns true or `WP_Error` with reasons.

## DEPENDENCIES
`KMAE_Vehicle::get_missing_required()` and photo motor required count.

## SIDE EFFECTS
On handler failure, reasons can be stashed for 300 seconds and pulled back into form.

## CHANGE RISKS
Moderation is server-authoritative. Client wizard may have friendlier validation but cannot replace this gate.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-listing-drafts.php`
- `includes/class-kmae-listing-wizard.php`
- `includes/class-kmae-meta.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-database.php`
- `includes/class-kmae-live-page.php`
- `includes/class-kmae-icons.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-brand.php`
- `includes/class-kmae-settings.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-query.php`
- `includes/class-kmae-seller.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Moderation  
**COMMON SYMPTOMS:** auto moderation result affects listing creation.  
**READ FIRST (symbol-level):** `KMAE_Moderation`; `KMAE_Public::process_create_listing()` moderation checkpoint.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `moderation auto_moderation process_create_listing`.  
**DO NOT LOAD UNLESS:** photo unless status specifically awaiting_photos.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
