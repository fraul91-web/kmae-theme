# FEATURE — Archive lifecycle and scheduled purge

## PURPOSE
Archivált hirdetések `_kmae_archived_at` órájától 3 napig archiváltak, utána a napi `kmae_purge_archived_listings` cron `force_delete_listing()`-gel végleg törli őket (admin „Végleges törlés” megmarad).

## READ FIRST
- `includes/class-kmae-listings.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`

## THEN IF NEEDED

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `public/class-kmae-shortcodes.php`
- `public/class-kmae-public.php`


## ENTRY POINTS
Activation and `KMAE_Listings::init()` schedule daily `kmae_purge_archived_listings`; hook calls `purge_expired_archived()`; deactivation clears cron. The same `KMAE_Activator::schedule_cron()` also registers hourly `kmae_purge_chat_images` → `KMAE_Conversations::purge_expired_images()` and hourly `kmae_remind_unfinished_listings` → `KMAE_Notifications::remind_unfinished_listings()`, and hourly `kmae_finalize_chat_sales` → `KMAE_Chat_Offers::finalize_sales()` (7 napos lezárás, nem eladás, lásd `CHAT.md`; hook a `KMAE_Chat_Offers::init()`-ben), and hourly `kmae_expire_listings` → `KMAE_Listings::expire_listings()` (7. kör, 2026-10-10: 60 napos lejárat, lásd `LISTINGS.md` „Lejárat”).

2026-10-17: `kmae_cards_backfill` (hourly, `KMAE_Image_Cards::init` ütemezi `init`-en, Deactivator törli) = kártyaképek utólagos köteg, lásd `PHOTO-MOTOR.md` „Kártyaképek”.

A Rendszerfigyelő (`KMAE_Cron_Monitor`) 2026-10-21-én TÖRÖLVE (option `kmae_cron_monitor` egyszer törölve: `KMAE_Activator::maybe_upgrade`, jelző `kmae_cron_monitor_removed`). A tárhely cronja (`wp-cron.php?kmae_server_cron=1`, 5 perc) maradjon: a nyilvános oldalak cache-ből jönnek, a látogatás nem indít WP-Cront; a `kmae_server_cron` paramétert már semmi nem olvassa.

## IMPORTANT SYMBOLS
`KMAE_Activator::schedule_cron`, `KMAE_Listings::{get_archive_lifecycle,purge_expired_archived,force_delete_listing,expire_listings,due_for_expiry}`, `KMAE_Notifications::remind_unfinished_listings`, `KMAE_Deactivator::deactivate`.

## DATA USED
`_kmae_status=archived`, `_kmae_archived_at`, `_kmae_reactivated_at`; lifecycle constants from main plugin file.

## FLOW
See `../FLOWS.md` section 18.

## DEPENDENCIES
Bilateral COMPLETED `project_terminal_status` creates archived state; a single `confirm_success()` does not. Forced deletion performs multi-domain cleanup.

## SIDE EFFECTS
Permanent listing/attachment/custom-row deletion after lifecycle expiration.

## CHANGE RISKS
Changing lifecycle days alters both UI eligibility and destructive cron timing. Verify all constants and `get_archive_lifecycle()` consumers together.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-meta.php`
- `includes/class-kmae-photo-motor.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-seller.php`
- `includes/class-kmae-taxonomies.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-images.php`
- `includes/class-kmae-database.php`
- `includes/class-kmae-notifications.php`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-post-types.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Cron/archive  
**COMMON SYMPTOMS:** schedule + lifecycle eligibility + delete.  
**READ FIRST (symbol-level):** `KMAE_Activator::schedule_cron()` → `KMAE_Listings::purge_expired_archived()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `kmae_purge_archived_listings purge_expired_archived`.  
**DO NOT LOAD UNLESS:** frontend.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
