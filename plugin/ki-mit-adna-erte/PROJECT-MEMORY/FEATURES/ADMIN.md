# FEATURE — WordPress admin integration

## PURPOSE
Admin menü, listing oszlopok/bulk action, admin listing műveletek, beállítások és meta box.

## READ FIRST
- `admin/class-kmae-admin.php`
- `admin/views/dashboard.php`
- `admin/views/settings.php`
- `admin/views/meta-box-listing.php`

## THEN IF NEEDED
- `includes/class-kmae-settings.php`
- `includes/class-kmae-meta.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
WP admin menu, listing list-table hooks, admin-post management routes, listing meta box.

Admin page Albira → Jelentések (`kmae-reports`, `KMAE_Admin::render_reports` → `admin/views/reports.php`; piros `awaiting-mod` buborék = `KMAE_Reports::count_new()` az „Albira” és „Jelentések” menün; admin-post `kmae_report_update`). Részletek: `REPORTS.md`.

Hirdetéslista sorművelet „Újraindítás tiszta lappal” (nem piszkozatnál): admin-post `kmae_admin_reset_listing` → `KMAE_Admin::handle_reset_listing` → `KMAE_Listings::reset_listing()` (törli a hirdetés chatjeit + chatképeit, `kmae_chat_offers`, `kmae_reports` sorait, a kedvenc/unseen/passed user metákból kiveszi, archív/lejárat órákat törli; `active`, `_kmae_published_at` + `post_date` = most, levél nélkül).


Albira → Teljesítmény admin oldal (perf-napló + diagnosztika) 2026-10-21 TÖRÖLVE; a régi `kmae_perf_log` opciót a `KMAE_Activator::maybe_upgrade` egyszer törli (`kmae_perf_log_removed`).


## IMPORTANT SYMBOLS
`KMAE_Admin` constructor hooks, render/handle methods; `KMAE_Meta` meta box/save methods; `KMAE_Settings::register_settings`.

## DATA USED
Same production listings/settings as public app; no separate admin shadow state.

## FLOW
Admin menu/list table → render views / actions → capability + nonce checks → domain helpers for destructive operations.

## DEPENDENCIES
Deletion routes delegate to listing/database domain methods.

## SIDE EFFECTS
Can delete listing or change listing status; settings writes WP options.

## CHANGE RISKS
Admin actions can bypass normal public UI paths but should still preserve domain cleanup. Compare destructive admin handler with public delete path before changes.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-database.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-taxonomies.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** WP admin  
**COMMON SYMPTOMS:** admin menus, listings/status actions.  
**READ FIRST (symbol-level):** `admin/class-kmae-admin.php` matching handler/render method.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `KMAE_Admin handle_delete handle_set_status`.  
**DO NOT LOAD UNLESS:** public controller.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
