# CROSS-FILE VERIFICATION

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

This pass re-checked the highest-impact A → B → C chains against direct source references after the first map was assembled.

## VERIFIED chains

### Bootstrap
`ki-mit-adna-erte.php::kmae_run`
→ `KMAE_Plugin::run`
→ domain init/instances.

Result: **VERIFIED**.

### Draft autosave
`assets/js/listing-wizard.js` action `kmae_save_listing_draft`
→ hook `wp_ajax_kmae_save_listing_draft`
→ `KMAE_Listing_Drafts::ajax_save`.

Result: **VERIFIED**.

### Browse pass
`assets/js/swipe.js` action `kmae_browse_pass`
→ `KMAE_Public::ajax_browse_pass`
→ `KMAE_Browse::record_pass`
→ `_kmae_browse_passed` user meta.

Result: **VERIFIED**.

### Live poll
`assets/js/public.js` action `kmae_poll_page`
→ `KMAE_Public::ajax_poll_page`
→ `KMAE_Live_Page::get_fingerprint` + unread chat count (`count_unread_conversations`).

Result: **VERIFIED**.

### Create listing
`KMAE_Public::handle_forms(create_listing)`
→ `process_create_listing`
→ seller/location/vehicle/moderation validation
→ WP listing insert
→ `KMAE_Listings::save_meta_from_post`
→ photo processing
→ publish event / draft discard.

Result: **VERIFIED**.

### Archive cron
`KMAE_Activator::schedule_cron`
→ daily `kmae_purge_archived_listings`
→ `KMAE_Listings::purge_expired_archived`
→ `force_delete_listing`.

Result: **VERIFIED**.

### Photo thresholds
`KMAE_Photo_Motor` constants
→ `client_config()`
→ localized by `KMAE_Public::enqueue()`
→ consumed by frontend photo motor.

Result: **VERIFIED** at source-reference level.

### Custom REST
Search for `register_rest_route` returned none; CPT and taxonomy registration set `show_in_rest` true.

Result: **VERIFIED**: no plugin-defined custom REST route in this ZIP.

## Explicitly UNVERIFIED boundaries

- Theme implementation of `kmae_is_guest_simple_marketing_shell()` — plugin conditionally calls it, but theme is outside this plugin-only pass.
- Live accessibility/behavior of Google, YouTube or `albira.hu` remote assets — only source configuration/calls were inspected.
- Actual production DB rows/schema/version/options — source declarations only.
- Runtime behavior contributed by other WordPress plugins/mu-plugins — not in this ZIP.


# V2 ROUTING VERIFICATION

- `DIAGNOSTIC-ROUTER.md`: symptom routes use concrete source symbols/hook names and explicit STOP/SKIP rules.
- `DECISION-TREES.md`: 14 problem trees.
- `HOTSPOTS.md`: shared multi-feature symbols only; not based on file size.
- `PLUGIN-BOUNDARIES.md`: WordPress mail/cron/session, theme and external service stop points.
- `REGRESSION-MAP.md`: targeted post-change checks for high-risk symbols.
- `SEARCH-ALIASES.md`: user/UI wording → hooks/classes/actions/data.
- `ROUTING-TESTS.md`: 15 simulated routes, all constrained to small initial source sets.
- Every existing `FEATURES/*.md` has a V2 diagnostic routing block.
- VERIFIED means checked against this uploaded plugin source snapshot; external runtime claims remain UNVERIFIED.
