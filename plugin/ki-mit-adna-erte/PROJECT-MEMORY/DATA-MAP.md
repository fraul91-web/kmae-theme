# DATA MAP

## Custom tables created by `KMAE_Activator::create_tables()`

> 2026-10-10: `kmae_offers`, `kmae_offer_images`, `kmae_counter_offers`, `kmae_contact_unlocks`, `kmae_ratings`, `kmae_official_offers`, `kmae_deals` and the two former post-deal tables are **dropped** by `KMAE_Activator::remove_offer_system()` (once, option `kmae_offer_system_removed`), which also deleted every conversation/message/chat file.

### `${prefix}kmae_private_data`
- One row per `listing_id`; `minimum_cash`, `currency`.
- Hidden seller minimum. Still saved/edited from the listing form and admin meta box; nothing reads it for filtering any more.

### `${prefix}kmae_user_profiles`
- PK `user_id`; `phone`, `account_type`, email/phone verification flags, email preferences (`email_offers` column is legacy, no UI).
- Supplements WP user/usermeta.

### `${prefix}kmae_conversations` / `${prefix}kmae_conversation_messages`
- One conversation per `(listing_id,buyer_id)` with fixed buyer/seller membership, `message_count`, `last_message_at`, `status` (`open`; `closed` after listing delete or 7-day sale), `listing_title` (4.65.0; snapshot written by `withdraw_alerts_for_listing` on listing delete, shown in inbox/thread when the post is gone). `legacy_offer_id` / `legacy_source_key` columns remain but are never written.
- Messages: opaque text, optional `attachment_id` + `image_expires_at` (48 h), unique `(conversation_id,idempotency_key)` retry protection. Idempotency key prefix `system:` = offer system notice (stored with `sender_id` = seller).

### `${prefix}kmae_chat_offers` (`KMAE_Chat_Offers`)
- Buyer price offer sent in the chat: `listing_id`, `conversation_id`, `message_id` (unique; the chat message carrying „Ajánlat: X Ft”), `buyer_id`, `seller_id`, `amount` (whole Ft), `status` (`live` / `replaced` / `accepted` / `closed` / `withdrawn` / `sold` / `retracted` = vevő [Visszavonom]; hirdetéstörlés → `live`/`accepted` → `closed`), `accepted_at` (added 4.62.0). One `live` per `(listing_id,buyer_id)`: a new offer sets the old to `replaced`. At most one `accepted` per listing (= „Foglalt”); accepting closes all other `live` rows of the listing; „Mégse kelt el” sets `accepted` → `withdrawn` (closed rows stay closed). Hourly `kmae_finalize_chat_sales`: `accepted` older than 7 days → `sold`, listing `_kmae_status` → `closed`, its conversations → `closed`. Seller „N eladott autó” = COUNT of `sold` rows by `seller_id` (rows survive listing deletion). Listing sale state is derived from these rows (`KMAE_Chat_Offers::listing_state`), not from post meta. Name deliberately differs from the dropped legacy `kmae_offers`.

### `${prefix}kmae_reports` (`KMAE_Reports`, 4.64.0)
- Bejelentés: `context` (`listing`/`chat`), `listing_id`, `conversation_id` (0 = nincs chat), `reporter_id`, `reported_user_id`, `listing_title` (snapshot), `reason` (key of `KMAE_Reports::reasons`), `details`, `status` (`new`/`valid`/`invalid`), `admin_note`, `resolved_by`, `resolved_at`. Nem törlődik hirdetés-/chat-törléskor. Admin menu badge = COUNT `new`.

### `${prefix}kmae_dealer_leads` (`KMAE_Dealer_Leads`, saját séma `kmae_dealer_leads_schema`)
- Csoportos kereskedői megkeresés: `listing_id`, `seller_id`, `dealer_id`, `purpose` (`sell`/`tradein`), `status` (`pending`/`interested`/`declined`/`expired`/`cancelled`), `token` (32 hex, unique; UI-ban ID helyett), `batch_id` (egy küldés), `distance_km`, `conversation_id` (`interested`-nél), `listing_title` (snapshot), `created_at`/`expires_at` (+7 nap)/`responded_at`/`updated_at` (UTC). UNIQUE (listing_id, dealer_id) = egy cég egy hirdetést soha kétszer. Sorok nem törlődnek.

**Foreign keys are logical, not DB-enforced**: the source creates indexes/unique keys but no SQL `FOREIGN KEY` constraints.

## Literal WordPress data keys found in executable PHP

| Key | Readers | Writers |
|---|---|---|
| `option:blogname` | `KMAE_Auth::password_reset_email_html`, `KMAE_Auth::request_password_reset` | — |
| `option:kmae_auto_moderation` | `KMAE_Settings::auto_moderation_enabled`, `KMAE_Moderation::is_enabled` | `KMAE_Settings::init_defaults` |
| `option:kmae_buyer_video_url` | `KMAE_Settings::buyer_video_url` | `KMAE_Settings::init_defaults` |
| `option:kmae_contact_form_email` | `KMAE_Settings::contact_form_email` | `KMAE_Settings::init_defaults` |
| `option:kmae_core_schema_version` | `KMAE_Activator::maybe_upgrade` | `KMAE_Activator::create_tables` |
| `option:kmae_db_version` | `KMAE_Activator::maybe_upgrade` | — |
| `option:kmae_google_client_id` | `KMAE_Google_Auth::client_id` | — |
| `option:kmae_google_client_secret` | `KMAE_Google_Auth::client_secret` | — |
| `option:kmae_pages_version` | `KMAE_Pages::maybe_ensure_pages` | `KMAE_Pages::maybe_ensure_pages` |
| `option:kmae_price_modes_migrated` | `KMAE_Activator::migrate_price_modes` | `KMAE_Activator::migrate_price_modes` |
| `option:kmae_seller_video_url` | `KMAE_Settings::seller_video_url` | `KMAE_Settings::init_defaults` |
| `option:page_on_front` | `KMAE_Pages::ensure_front_page` | `KMAE_Pages::ensure_front_page` |
| `option:show_on_front` | — | `KMAE_Pages::ensure_front_page` |
| `postmeta:_kmae_archived_at` | `KMAE_Listings::get_archive_lifecycle`, `KMAE_Dashboard::get_data` | `KMAE_Listings::reactivate_listing`,  |
| `postmeta:_kmae_city` | `KMAE_Counties::format_listing_location`, `KMAE_Meta::render_meta_box` | `KMAE_Counties::save_listing_location` |
| `postmeta:_kmae_condition` | `KMAE_Vehicle::get_specs`, `KMAE_Meta::render_meta_box` | `KMAE_Listings::save_meta_from_post`, `KMAE_Vehicle::save_from_post`, `KMAE_Meta::save_listing_meta` |
| `postmeta:_kmae_condition_note` | `KMAE_Vehicle::get_specs` | `KMAE_Vehicle::save_from_post` |
| `postmeta:_kmae_registration_ids` (array rendszam/alvazszam/forgalmi_szam; forgalmi beolvasásból, csak a szerződéshez, sehol nem jelenik meg) | `KMAE_Registration_Scan::get_ids` → `KMAE_Sale_Contract::prefill` | `KMAE_Registration_Scan::save_ids_from_post` (a `KMAE_Listings::save_meta_from_post`-ból) |
| `postmeta:_kmae_county` | `KMAE_Counties::format_listing_location`, `KMAE_Meta::render_meta_box` | `KMAE_Counties::save_listing_location` |
| `postmeta:_kmae_create_device` | — | `KMAE_Public::process_create_listing` |
| `postmeta:_kmae_gallery` | `KMAE_Listings::collect_listing_attachment_ids`, `KMAE_Query::get_gallery_ids` | `KMAE_Listings::handle_image_uploads`, `KMAE_Listings::apply_gallery_edits`, `KMAE_Photo_Motor::handle_slot_uploads` |
| `postmeta:_kmae_location` | `KMAE_Counties::format_listing_location`, `KMAE_Meta::render_meta_box` | `KMAE_Counties::save_listing_location`, `KMAE_Meta::save_listing_meta` |
| `postmeta:_kmae_offer_types` | — | `KMAE_Seller::save_from_post`, `KMAE_Listings::save_meta_from_post`, `KMAE_Meta::save_listing_meta` |
| `postmeta:_kmae_orientation` | `KMAE_Images::get_orientation` | `KMAE_Images::process_attachment` |
| `postmeta:_kmae_published_at` | `KMAE_Listings::activate_if_photos_ready`, `KMAE_Meta::save_listing_meta` | `KMAE_Admin::handle_set_status`, `KMAE_Listings::activate_if_photos_ready`, `KMAE_Listings::save_meta_from_post`, `KMAE_Meta::save_listing_meta` |
| `postmeta:_kmae_reactivated_at` | `KMAE_Listings::expiry_clock_start` | `KMAE_Listings::reactivate_listing` |
| `postmeta:_kmae_renewed_at` | `KMAE_Listings::expiry_clock_start` | `KMAE_Listings::renew_listing`, `KMAE_Chat_Offers::cancel_sale`, `KMAE_Meta::save_listing_meta` |
| `postmeta:_kmae_expired_at` | — | `KMAE_Listings::expire_listings` (delete: `renew_listing`, `KMAE_Meta::save_listing_meta`) |
| `postmeta:_kmae_status` | `KMAE_Listing_Drafts::get_user_draft`, `KMAE_Meta::render_meta_box`, `KMAE_Meta::get_status` | `KMAE_Admin::handle_set_status`, `KMAE_Admin::handle_listing_bulk_actions`, `KMAE_Listings::reactivate_listing`, `KMAE_Listings::activate_if_photos_ready`, `KMAE_Listings::save_meta_from_post` +5 more |
| `postmeta:_kmae_trade_in_categories` | — | `KMAE_Listings::save_meta_from_post`, `KMAE_Meta::save_listing_meta` |
| `transient:kmae_google_` | `KMAE_Google_Auth::handle_callback` | `KMAE_Google_Auth::redirect_to_google`, `KMAE_Google_Auth::handle_callback` |
| `transient:kmae_mod_errors_` | — | `KMAE_Moderation::stash_errors_for_user` |
| `usermeta:kmae_account_type` | `KMAE_Auth::get_account_type`, `KMAE_Auth::get_profile` | `KMAE_Google_Auth::handle_complete`, `KMAE_Auth::register`, `KMAE_Auth::sync_phone` |
| `usermeta:kmae_email_verify_sent` | `KMAE_Auth::verify_email_link` | `KMAE_Auth::generate_email_verify_token`, `KMAE_Auth::verify_email_link` |
| `usermeta:kmae_email_verify_token` | `KMAE_Auth::verify_email_link` | `KMAE_Auth::generate_email_verify_token`, `KMAE_Auth::verify_email_link` |
| `usermeta:kmae_given_name` | `KMAE_Auth::first_name` | — |
| `usermeta:kmae_phone` | `KMAE_Auth::get_profile` | `KMAE_Google_Auth::handle_complete`, `KMAE_Auth::register`, `KMAE_Auth::sync_phone`, `KMAE_Public::admin_save_profile` |

## Important symbolic meta constants

- Listing status: `_kmae_status`; values `active`, `awaiting_photos`, `closed`, `archived`, `draft` (`KMAE_Meta`).
- Offer types: `_kmae_offer_types`; launch active type is `cash`.
- Seller pricing: `_kmae_price_mode`, `_kmae_fixed_price`, `_kmae_guide_price`, `_kmae_commitments`, `_kmae_commit_extra_other`.
- Vehicle: `_kmae_vehicle_brand/model/year/mileage/fuel/transmission/body/color/owners/power_kw/engine_cc/mot/value/traits`.
- Location: `_kmae_county`, `_kmae_city`.
- Photo motor listing meta: `_kmae_photo_slots`, `_kmae_gallery`; attachment meta: `_kmae_photo_slot`, `_kmae_photo_hash`, `_kmae_photo_autofixed`, `_kmae_photo_analysis_v2`.
- Expiry (60 nap): `_kmae_status=expired`, `_kmae_expired_at`, `_kmae_renewed_at`; óra = `KMAE_Listings::expiry_clock_start`.
- Archive lifecycle: `_kmae_archived_at`, `_kmae_reactivated_at` (deal meta `_kmae_deal_phase`, `_kmae_accepted_offer_id`, `_kmae_deal_locked_at` deleted 2026-10-10).
- Draft: WP post type `kmae_listing` with `post_status=draft`, `_kmae_status=draft`, payload `_kmae_wizard_draft`.
- Browse user meta: `_kmae_browse_passed`; chat seen map `_kmae_conversations_seen_map` is maintained in `KMAE_Database`.
- Google user meta: `kmae_google_sub`, `kmae_given_name`, `kmae_google_pending`.
- Company user meta: `kmae_company_name`, `kmae_company_address`, `kmae_company_county`, `kmae_company_city`.
- Verification user meta: `kmae_email_verify_token`, `kmae_email_verify_sent`; stored token is hashed with `wp_hash()`.

## Options / migration markers

- `kmae_db_version` — source schema version marker.
- `kmae_offer_system_removed` — set after the one-time offer/deal table drop and conversation wipe (`kmae_core_schema_version` / `kmae_core_legacy_backfill_version` deleted then).
- `kmae_price_modes_migrated` — legacy `offers` → `open` migration marker.
- `kmae_auto_moderation`, seller/buyer YouTube URLs, Google OAuth client ID/secret, contact form recipient.
- Page creation/front-page migration options are owned by `KMAE_Pages` (see symbol map for exact keys).

## Runtime-state caveat
The map describes schema and data contracts declared in source. The actual deployed WordPress database contents/schema version are **UNVERIFIED** in this ZIP-only analysis.
