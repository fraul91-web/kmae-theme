# FLOW MAP

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

## 1. Plugin boot

`ki-mit-adna-erte.php`
→ constants / loader require
→ activation + deactivation hooks registered
→ `kmae_run()`
→ `new KMAE_Plugin()`
→ `KMAE_Plugin::run()`
→ upgrade check + domain initializers
→ public controller / shortcode / pages / nav registration
→ admin controller only when `is_admin()`.

## 2. Activation / upgrade

Activation
→ `KMAE_Activator::activate()`
→ `create_tables()` (7 custom tables + settings defaults + db version)
→ `migrate_price_modes()`
→ register CPT/taxonomy + seed categories
→ `flush_rewrite_rules()`
→ `KMAE_Pages::ensure_pages()`
→ `KMAE_Auth::ensure_subscriber_caps()`
→ daily archive purge cron scheduled.

Normal boot also runs `KMAE_Activator::maybe_upgrade()`; if stored db version is older than plugin version, it repeats schema/migration/page/cap/cron maintenance.

## 3. Password registration → email verification

`/regisztracio/` → `[kmae_register]` → registration template
→ POST `admin-post.php?action=kmae_register`
→ `KMAE_Public::admin_register()`
→ spam checks / terms / input assembly
→ `KMAE_Auth::register()`
→ validates email, phone, account type, password and company location when applicable
→ `wp_insert_user(role=subscriber)`
→ user meta + optional company meta
→ `kmae_user_profiles` row with `email_verified=0`
→ `generate_email_verify_token()` stores only hash + sent timestamp in user meta
→ `send_verification_email()`
→ user clicks home URL with raw `kmae_email_token` only (no user ID; user found by token hash)
→ `KMAE_Auth::maybe_handle_email_verify_link()` on `init`
→ `verify_email_link()` validates hash and 7-day age
→ `mark_email_verified()` updates custom profile table
→ token meta deleted → redirect to profile/login.

Server-side core-action gate:
`KMAE_Public::handle_forms()` blocks `create_listing` and `start_conversation` when `KMAE_Auth::should_gate_for_verification()` is true.

## 4. Google login

Google button
→ `KMAE_Google_Auth::start_url()` (`?kmae_google=start`)
→ `maybe_handle_request()` on `init`
→ transient state created (15 min)
→ redirect Google authorization endpoint
→ callback `?kmae_google=callback&state=...&code=...`
→ state transient validated/deleted
→ `fetch_profile()` POSTs authorization code to Google token endpoint
→ claims extracted from returned `id_token`
→ `resolve_user()` by stored Google subject, then by email, else creates subscriber
→ WP auth cookie set
→ `KMAE_Auth::mark_email_verified()`
→ if new/incomplete: `kmae_google_pending` forces registration-completion page
→ `handle_complete()` stores phone/account type/terms/company fields and clears pending state.

`template_redirect` → `enforce_completion()` prevents non-admin logged-in users with incomplete Google profiles from using other pages.

## 5. Listing creation — immediate photo path

`/hirdetes-feladasa/` → `[kmae_create_listing]`
→ listing form + wizard assets
→ browser wizard validates stages and can AJAX-save draft
→ final same-page POST `kmae_action=create_listing`
→ `KMAE_Public::handle_forms()`
→ email verification gate
→ `process_create_listing()`
→ nonce + seller eligibility
→ company users obtain listing location from company profile; private users submit valid county/city
→ `KMAE_Vehicle::parse_post_data()`
→ `KMAE_Moderation::validate()` (required vehicle fields, description, photos when required, no contact info)
→ `wp_insert_post(post_type=kmae_listing, post_status=publish)`
→ `KMAE_Listings::save_meta_from_post()`
   → listing condition/category/offer types
   → seller pricing/commitments/private minimum
   → location
   → vehicle meta / launch category
→ photo request validation + `KMAE_Listings::handle_image_uploads()`
   → `KMAE_Photo_Motor::handle_slot_uploads()` when photo motor enabled
   → analyze → optional autocorrect → media upload → `KMAE_Images::process_attachment()` → slot/analysis/hash meta → gallery/thumbnail
→ on failure newly inserted listing is deleted
→ `kmae_listing_published` emitted
→ submitted draft discarded
→ redirect.

## 6. Listing creation — computer/deferred-photo path

`process_create_listing()` receives creation device indicating computer
→ moderation does not require photos at this moment
→ listing created with `_kmae_status=awaiting_photos`
→ redirect to later photo completion/edit path
→ after required slots exist, `KMAE_Listings::activate_if_photos_ready()`
→ status becomes active
→ publish timestamp/state updated
→ `kmae_listing_published` emitted.

## 7. Wizard draft autosave

`assets/js/listing-wizard.js`
→ serializes current wizard state
→ authenticated AJAX action `kmae_save_listing_draft`
→ `KMAE_Listing_Drafts::ajax_save()`
→ creates/updates draft `kmae_listing` post owned by current user
→ `_kmae_status=draft`
→ `_kmae_wizard_draft` stores payload
→ response returns draft ID.

Opening create-listing can read `kmae_draft`/latest draft through `KMAE_Public::enqueue_listing_wizard()` and `KMAE_Listing_Drafts::to_client()`.

## 8. Listing edit

Edit UI
→ admin-post `kmae_update_listing`
→ `KMAE_Public::admin_update_listing()`
→ login/nonce/ownership/`KMAE_Listings::can_edit_listing()`
→ validate fields/location/vehicle/moderation
→ `wp_update_post()` + `KMAE_Listings::save_meta_from_post()`
→ photo motor request validation/upload or legacy gallery edits
→ if previously awaiting photos: `activate_if_photos_ready()` may activate
→ redirect with status.

## 9. Browse/search personalization

`[kmae_browse]`
→ vehicle launch wizard parses `megye`, `kivitel`, `mod`, `kp_min`, `kp_max`
→ `KMAE_Query::get_listings()` queries published listings with `_kmae_status=active`, launch category and relevant meta filters
→ logged-in user: own listings excluded
→ results newest-first
→ `KMAE_Browse::personalize_listings()` separates `_kmae_browse_passed`
→ passed items are reinserted every `PASSED_INTERVAL=20` fresh items.

Swipe/pass:
`assets/js/swipe.js` → AJAX `kmae_browse_pass` → `KMAE_Public::ajax_browse_pass()` → `KMAE_Browse::record_pass()` → `_kmae_browse_passed` user meta.

## 10. Listing contact → chat

All price types (fixed, guide, open) use the same CTA (`Üzenet küldése`):
existing `listing_id + buyer_id` conversation → GET `KMAE_Pages::conversation_thread_url()`, else POST `start_conversation` → `KMAE_Conversations::create()` → `/uzenetek/?kmae_conversation=<id>`.
→ free chat `send_conversation_message` → `save_message` (text, emoji, file kept 48 h)
→ `KMAE_Notifications::on_conversation_message_created()` email + `KMAE_Live_Signal` / Web Push.
Details: `FEATURES/CHAT.md`.

## 11. Live page poll / unread badge

`KMAE_Public::enqueue()` localizes `livePoll` (`ajaxUrl`, nonce, `interval` 4s, `idleInterval` 25s, `idleAfter` 60s, `signalUrl`/`signalVersion` from `KMAE_Live_Signal`, `fullEvery` 120s)
→ `assets/js/public.js` single scheduler (each tick first GETs the static signal file `uploads/kmae-live/<key>.v`; full `kmae_poll_page` only when the signal changed, every `fullEvery` 120s safety net, or after 3 signal failures = legacy mode): ONE request per tick serves both the nav badge and the live page (context = `data-kmae-live-page` or `nav_badge`); no request on page load; 4s while active, 25s after 60s idle; immediate poll on activity-after-idle / tab visible; paused on hidden tab
→ AJAX `kmae_poll_page`
→ `KMAE_Public::ajax_poll_page()`
→ `KMAE_Live_Page::get_fingerprint(...)` + `KMAE_Database::count_unread_conversations()` (`unseen` = unread chat; favorites page uses `KMAE_Browse::count_unseen_favorites()`)
Push path: plugin event → `KMAE_Live_Signal` (signal file + note) → shutdown → `KMAE_Push::send_to_user()` → browser push service → `push-sw.js` → open tabs full-poll immediately / notification if no focused tab.
→ JSON response lets frontend detect relevant state/activity changes.

## 12. Archive lifecycle / cron purge

Listing `archived` + `_kmae_archived_at` is set via `KMAE_Meta::set_status()`.
`KMAE_Listings::get_archive_lifecycle()` applies source constants: 5-day locked period + 2-day reactivation window (7 days total).
Activation/normal listing init ensures daily `kmae_purge_archived_listings` cron.
Cron → `KMAE_Listings::purge_expired_archived()`
→ finds expired archived listings
→ `force_delete_listing()`
→ participant notification + attachment cleanup + `wp_delete_post()`.

## 13. Forced listing deletion

`KMAE_Listings::force_delete_listing()`
→ `KMAE_Notifications::on_listing_deleted()`
→ `KMAE_Conversations::withdraw_alerts_for_listing()`
→ deletes listing-owned attachments + `private_data` row
→ `wp_delete_post(force=true)`.
