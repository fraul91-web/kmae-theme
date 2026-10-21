# FEATURE — Live polling, navigation and badges

## PURPOSE
Oldal fingerprint polling, olvasatlan chat count, navigation filterezés és badge frissítési frontend.

## READ FIRST
- `includes/class-kmae-live-page.php`
- `includes/class-kmae-live-signal.php` (lightweight change signal served as a static file, no PHP/WP)
- `includes/class-kmae-push.php` + `assets/js/push.js` + `assets/js/push-sw.js` + `assets/css/push.css` (Web Push browser notifications)
- `includes/class-kmae-nav.php`
- `public/class-kmae-public.php`
- `assets/js/public.js`

## THEN IF NEEDED
- `assets/js/nav.js`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-database.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
Public asset enqueue, AJAX `kmae_poll_page`, lightweight static GET `uploads/kmae-live/<key>.v` (URL from `KMAE_Live_Signal::signal_url()`; dir `.htaccess` blocks PHP, sets no-store), WP menu/block filters, nav assets.

Signal producers: `KMAE_Live_Signal::init()` listens only to `kmae_conversation_message_created`, collects buyer+seller and writes their `.v` file on `shutdown`. `KMAE_Chat_Offers::retract` posts a buyer-authored system message, so it goes through the same hook. Events without a `do_action` (e.g. seen marks) are covered only by the client's 2-minute full-poll safety net — add `KMAE_Live_Signal::bump()` there if instant delivery is needed.

Web Push (`KMAE_Push`): free standard push (RFC 8291 aes128gcm + RFC 8292 VAPID, pure PHP/openssl, no library). VAPID keypair in option `kmae_push_vapid` (autoload). Per-user meta: `kmae_push_subs` (endpoint/p256dh/auth), `kmae_push_enabled`, `kmae_push_prompted`. AJAX: `kmae_push_subscribe`, `kmae_push_unsubscribe` (account-level off), `kmae_push_prompted` (nonce `kmae_push`). Prompt dialog (`wp_footer`, shown once until decided) after registration; settings toggle `[data-kmae-push-toggle]` in `templates/settings.php`. Sending: `KMAE_Live_Signal::flush()` (shutdown 100) writes signal files, closes the response (`fastcgi_finish_request` / `litespeed_finish_request`), then `KMAE_Push::send_to_user()` for every affected user EXCEPT the acting user; 404/410 subscriptions are pruned. SW (`push-sw.js`, scope = plugin `assets/js/`) notifies open tabs (postMessage + BroadcastChannel `kmae-push`) → `public.js` runs an immediate full poll; shows a notification unless the focused tab is that same conversation (`samePage`: path + `kmae_conversation`), so browsing elsewhere on the site still pops it. With push active the scheduler relaxes to 20s/60s and a 5-min safety net.

## IMPORTANT SYMBOLS
`KMAE_Public::ajax_poll_page`, `KMAE_Live_Page::get_fingerprint`, `KMAE_Live_Signal::{init,bump,flush,user_key,version,signal_url,is_available}`, `KMAE_Database::count_unread_conversations`, `KMAE_Database::get_conversation_alert_state`, `KMAE_Database::mark_conversation_thread_seen`, `KMAE_Nav` filter methods.

## DATA USED
Current context (`conversation_thread`, `messages_inbox`, `single_listing`, `my_listings`, `nav_badge`), conversation message_count/last_message_at/status, `single_listing`: listing status + `KMAE_Chat_Offers::listing_state` + `count_live_for_listing`, `my_listings` (root on `templates/my-listings.php` section): `KMAE_Chat_Offers::seller_signature`. Client skips reload while typing or while a visible `dialog[open]`/`[aria-modal]` is open, then retries next tick (`lastSignal = ''`), per-conversation seen map, navigation tree.

## FLOW
See `../FLOWS.md` section 17.

## DEPENDENCIES
Thread seen marker (`mark_conversation_thread_seen`, on `template_redirect` of `/uzenetek/?kmae_conversation=`) feeds the unread count. Public JS consumes poll response (about 4s, first tick immediate). Badge is shown on the theme „Üzenetek” items only (`data-kmae-unseen`).

## SIDE EFFECTS
Page cache: `KMAE_Public::nocache_live_pages` (template_redirect 0) — logged-in + (`kmae_listing` single, or page with `[kmae_messages]` / `[kmae_my_listings]` / `[kmae_browse]` / `[kmae_favorites]` / `[kmae_create_listing]` / `[kmae_profile]` / `[kmae_settings]`) → `nocache_headers()` + `do_action('litespeed_control_set_nocache')` + `DONOTCACHEPAGE`. Without the LiteSpeed call the reload after a fingerprint change got the cached old HTML (new chat appeared only after ~5 min).
Poll is read-oriented. Badge/unseen counts only open conversations with a live listing; listing delete runs `withdraw_alerts_for_listing` (close + mark seen).

## CHANGE RISKS
Badge count semantics and seen-marker semantics must change together; otherwise polling can continually re-surface already viewed activity.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-meta.php`
- `includes/class-kmae-photo-motor.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-listing-drafts.php`
- `includes/class-kmae-listing-wizard.php`
- `includes/class-kmae-moderation.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-icons.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-brand.php`
- `includes/class-kmae-settings.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-query.php`
- `includes/class-kmae-seller.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Live/nav  
**COMMON SYMPTOMS:** poll fingerprint/count + DOM nav.  
**READ FIRST (symbol-level):** `KMAE_Public::ajax_poll_page()`; `KMAE_Live_Page`; `assets/js/public.js/nav.js`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `kmae_poll_page ajax_poll_page fingerprint unseen`.  
**DO NOT LOAD UNLESS:** notifications email.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

Context `email_verify` (`KMAE_Live_Page`): verified/pending bit; root = `partials/email-verify-gate.php` `.kmae-verify-gate` and `form-profile.php` `.kmae-profile` (only while unverified). `KMAE_Auth::verify_email_link` bumps the user → the open page reloads after the link is clicked elsewhere.
