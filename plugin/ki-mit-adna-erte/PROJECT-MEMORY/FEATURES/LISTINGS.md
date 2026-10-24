# FEATURE — Listing creation, editing and lifecycle

## PURPOSE
Hirdetés létrehozás/szerkesztés/törlés/reaktiválás, listing meta, állapotok és tulajdonosi jogosultság.

## READ FIRST
- `public/class-kmae-public.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-meta.php`
- `includes/class-kmae-seller.php`

## THEN IF NEEDED
- `templates/form-create-listing.php`
- `templates/form-listing.php`
- `templates/partials/listing-form-layout.php`
- `templates/my-listings.php`
- `assets/js/my-listings.js`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-deactivator.php`
- `public/class-kmae-shortcodes.php`


## ENTRY POINTS
- `[kmae_create_listing]`, `[kmae_my_listings]`
- same-page `create_listing`
- admin-post update/delete/reactivate
- single/archive CPT routing.

## IMPORTANT SYMBOLS
- `KMAE_Public::{process_create_listing,admin_update_listing,admin_delete_listing,admin_reactivate_listing,admin_renew_listing}`
- `KMAE_Listings::{save_meta_from_post,ensure_launch_category,activate_if_photos_ready,delete_listing,force_delete_listing,reactivate_listing,reset_listing,get_archive_lifecycle,expire_listings,due_for_expiry,renew_listing,expiry_clock_start,days_until_expiry}`, const `EXPIRE_DAYS = 60`
- `KMAE_Meta::{get_status,set_status,get_offer_types}` (`get_offer_types` always `cash`)
- `KMAE_Seller::{save_from_post,sync_vehicle_value_meta}`

## DATA USED
WP `kmae_listing` post + taxonomy + listing postmeta + private minimum custom table + photo attachments.

## FLOW
See `../FLOWS.md` sections 5–8, 18–19.

## DEPENDENCIES
Auth verification; seller quota; county/company; vehicle; moderation; photo motor/images; notifications; cron.

## SIDE EFFECTS
Creates/updates/deletes posts and attachments, emits publication events, forced deletion first ends the listing's chat offers (`KMAE_Chat_Offers::on_listing_deleted`: Foglalt vevő rendszerüzenet, `live`/`accepted` → `closed`), then closes the conversations keeping them readable (`withdraw_alerts_for_listing`, title snapshot) and deletes its private_data row.

## CHANGE RISKS
Listing status is consumed by browse, chat (deleted/unpublished listing closes the composer), dashboard and public-access blocking. Automatic transitions: `closed` only via `KMAE_Chat_Offers::finalize_sales` (7 napos eladott); `expired` via `KMAE_Listings::expire_listings` (60 nap). Never alter status transitions without checking those features.


## LEJÁRAT ÉS TÖRLÉS (7. kör, 2026-10-10)
- Törlés: `can_delete_listing()` = bármely létező `kmae_listing` (tulajdon-ellenőrzés `delete_listing()`-ben) → a Hirdetéseim kártyán mindig van Törlés.
- Lejárat: óránkénti cron `kmae_expire_listings` → `expire_listings()`: `active` + óra ≥ 60 nap + nincs `accepted` chat-ajánlat (Foglalt) → `_kmae_status=expired` (`KMAE_Meta::STATUS_EXPIRED`), `_kmae_expired_at`, levél `KMAE_Notifications::notify_listing_expired()` (nem kikapcsolható, gomb → Hirdetéseim).
- Kereskedői megkeresések (DEALER-LEADS, 6. fázis): a hirdetés lejárata/törlése/lezárása (`expire_listings`, `force_delete_listing`, `KMAE_Chat_Offers::close_listing`) a nyitott megkereséseket `cancelled`-re állítja (`KMAE_Dealer_Leads::on_listing_ended`); `reset_listing` törli a sorokat (`delete_for_listing`).
- Óra: `expiry_clock_start()` = legkésőbbi `_kmae_published_at` / `_kmae_reactivated_at` / `_kmae_renewed_at` (fallback `post_date`). `_kmae_renewed_at`-et írja: `renew_listing()`, `KMAE_Chat_Offers::cancel_sale()` (Mégse kelt el), `KMAE_Meta::save_listing_meta` (admin expired→active). Admin gyors-státusz active → `_kmae_published_at` frissül.
- Hosszabbítás: `admin_post_kmae_renew_listing` → `KMAE_Public::admin_renew_listing` → `renew_listing()` (csak `expired`, tulaj) → `active` + 60 nap újra; nem küld „kint van” levelet.
- Lejárt hirdetés linkje (8. kör): `KMAE_Post_Types::template_loader` → ha `KMAE_Post_Types::is_unavailable_for_viewer()` (status `expired` és nem tulaj/admin) → `templates/listing-unavailable.php`: „Ez az autó már nem elérhető” + autó neve + „Autók böngészése” + „Hasonló autók” (`KMAE_Query::get_similar_listings`: azonos karosszéria, majd legújabbak, max. 4), `noindex`. A hirdetés nem törlődik; a böngészőből a `get_listings` aktív-szűrője veszi ki. Tulaj/admin a rendes adatlapot látja. Kinézet: theme `listing-unavailable.css` (külön fájl, mert a `single-listing.css` csak belépve töltődik; enqueue `kmae_enqueue_listing_unavailable`).
- Törlés Foglalt hirdetésnél (8. kör): Hirdetéseim törlő-form `data-kmae-delete-reserved="1"` (`KMAE_Chat_Offers::get_accepted_for_listing`) → `my-listings.js` ablakcím „Erre az autóra elfogadott ajánlatod van. Biztosan törlöd?”. Törléskor a chat-következmények: `CHAT.md` (rendszerüzenet, chatek olvashatók, nem eladás).
- UI (`templates/my-listings.php`): aktív kártyán második `.kmae-my-listing-card__stats` sor „Lejár N nap múlva” (`days_until_expiry`, Foglaltnál rejtve); lejárt kártya: státusz „Lejárt” (`--archived` szín), `__notice` + „Hosszabbítás” gomb.

## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-database.php`
- `includes/class-kmae-taxonomies.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-photo-motor.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-images.php`
- `includes/class-kmae-notifications.php`
- `includes/class-kmae-listing-drafts.php`
- `includes/class-kmae-listing-wizard.php`
- `includes/class-kmae-moderation.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-live-page.php`
- `includes/class-kmae-icons.php`
- `includes/class-kmae-pages.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Listing lifecycle  
**COMMON SYMPTOMS:** create/edit/delete/reactivate/publish/archive.  
**READ FIRST (symbol-level):** `KMAE_Public::process_create_listing()`; `KMAE_Listings` lifecycle methods.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `process_create_listing save_meta_from_post can_reactivate`.  
**DO NOT LOAD UNLESS:** chat.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## Image file collisions (2026-10-03)
Bug: upload `image.jpg` → converted to `image.webp`, original deleted → next same-name upload got `image.jpg` again and its WebP overwrote the earlier listing's `image.webp` (two attachments, one file; deleting one removed the other's image). Fix: `KMAE_Images::process_attachment_inner` names the WebP with `wp_unique_filename`; every attachment deletion goes through `KMAE_Images::delete_attachment()` which keeps the file if another attachment shares `_wp_attached_file` (`file_is_shared`). There is no image-based duplicate/spam filter on the server (only a client-side same-selection check in photo-motor.js).

## Photos ↔ status (2026-10-03)
`KMAE_Listings::sync_status_with_photos()` (called from `KMAE_Public::admin_update_listing` generic-uploader branch): active listing whose last photo was removed → `awaiting_photos` + meta `_kmae_photos_removed` (hidden from browse/search/public page, card says „Nincs fotó, ezért nem látszik”); uploading a photo again → `active` (publish email only if `_kmae_published_at` was never set). Photo-motor mode keeps its own slot rules.
