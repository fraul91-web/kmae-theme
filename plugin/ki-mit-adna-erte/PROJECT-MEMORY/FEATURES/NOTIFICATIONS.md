# FEATURE — Event notifications and email

## PURPOSE
Chatüzenet-, hirdetés- és biztonsági e-mailek, mail catalog/prefs és brandelt HTML mail. Az ajánlat/deal/értékelés levelek törölve (2026-10-10).

## READ FIRST
- `includes/class-kmae-notifications.php`
- `includes/class-kmae-brand.php`

## THEN IF NEEDED
- `includes/class-kmae-auth.php`
- `includes/class-kmae-conversations.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
Hooks (`KMAE_Notifications::init`): `kmae_conversation_message_created` → `on_conversation_message_created` (mail key `message`, button → `/uzenetek/?kmae_conversation=`); `kmae_listing_published` → `on_listing_published` (`listing_active`); hourly cron `kmae_remind_unfinished_listings` (`listing_unfinished`). Direct calls: `on_listing_deleted( $listing_id, $listing, $actor_id )` from `KMAE_Listings::force_delete_listing` (recipients = conversation parties via `listing_party_ids`, key `listing_deleted`), `notify_password_changed`, `notify_email_changed` (security, not in catalog), `notify_listing_expired( $listing_id )` from `KMAE_Listings::expire_listings` (60 napos lejárat, nincs mail_key → mindig megy). `notify_dealer_lead( $lead, $facts )` from `KMAE_Dealer_Leads::send_queued_notifications` (shutdown 90, egy levél cégenként, key `dealer_lead`, gomb → `/ertesitesek/?kmae_lead=<token>`; térkép `DEALER-LEADS.md`).

E-mail delivery timing: `KMAE_Perf` (includes/class-kmae-perf.php) queues every `wp_mail` on front-end / AJAX / admin-post / cron via `pre_wp_mail` and sends it on `shutdown` (110) AFTER `KMAE_Perf::finish_response()`; normal wp-admin screens (e.g. WP Mail SMTP test) and mails with attachments send immediately.

## IMPORTANT SYMBOLS
`KMAE_Notifications::{init,mail_catalog,notify_dealer_lead,mail_catalog_keys,mail_prefs,allows_mail,save_mail_prefs,on_conversation_message_created,on_listing_deleted,on_listing_published,remind_unfinished_listings,notify_listing_expired,notify_password_changed,notify_email_changed,silence_offer_pipeline,send_offer_email}`; `KMAE_Brand::{send_mail,wrap_email,ensure_html_headers,embed_email_images}`.

Levélhéj (2026-10-11): egy közös `wrap_email` minden levélhez (fehér háttér, logó, manó, címke, cím, szövegkártya, narancs gomb). Képek beágyazva (`cid:albira-*`): `KMAE_Brand::EMAIL_IMAGES` → `assets/images/email/{logo,info,ezaz,sad}.png`, a `phpmailer_init` → `embed_email_images` csatolja inline-ként. Manó = `tone`: `sad` → szomorú, `cheer`/`welcome`/`good` → ezaz, más → infó (a régi `header_image`/`header_width` argumentum megszűnt).

## DATA USED
Listing/user/conversation data; user meta `kmae_mail_prefs` (catalog key missing or `1` = send, saved `0` = skip); unknown keys always send (security mail). Catalog: `listing_active`, `listing_unfinished`, `message`, `listing_deleted`, `dealer_lead` (csoport `dealer`, `company_only`: `mail_catalog($user_id)` / `mail_catalog_keys($user_id)` / `mail_prefs` csak céges fióknak adja; `save_mail_prefs` a nem látszó kulcs mentett értékét megtartja; `allows_mail` a teljes listát nézi). `templates/settings.php` `mail_catalog( $user->ID )`. Unfinished reminder stamp: post meta `_kmae_unfinished_mail_at`.

## FLOW
Event → listener resolves recipient → `send_offer_email()` (name kept; generic sender, mutable by `silence_offer_pipeline` during listing delete) checks `allows_mail()` → `KMAE_Brand::send_mail` / `wrap_email` → `wp_mail`. Settings popup lists the catalog (`templates/settings.php`).

## DEPENDENCIES
Chat message mail depends on `KMAE_Conversations::save_message()` emitting `kmae_conversation_message_created` (not when `$silent`).

## SIDE EFFECTS
External e-mail delivery through WordPress mail stack.

## CHANGE RISKS
Notification methods can be called both by hooks and directly. Search callers before removing/renaming any method.

## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-database.php`
- `includes/class-kmae-pages.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Notifications/email  
**COMMON SYMPTOMS:** domain event → recipient/body → preference → brand mail.  
**READ FIRST (symbol-level):** `KMAE_Notifications` event handler matching the event; `send_offer_email()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `on_conversation_message_created on_listing_deleted send_offer_email mail_catalog`.  
**DO NOT LOAD UNLESS:** event producer once event payload verified.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## Draft deletion
`KMAE_Listings::force_delete_listing` skips `KMAE_Notifications::on_listing_deleted` for drafts (`_kmae_status=draft` or post_status draft): no „Hirdetés törölve” mail for an abandoned listing.
