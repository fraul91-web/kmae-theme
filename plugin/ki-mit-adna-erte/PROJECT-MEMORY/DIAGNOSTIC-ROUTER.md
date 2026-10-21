# PLUGIN DIAGNOSTIC ROUTER — V2

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

MAP-TYPE: ROUTER

**Authority:** SOURCE CODE > PROJECT MEMORY. `VERIFIED` below means the route was checked against the uploaded plugin source snapshot.

## Context budget
Default debug context: **this router + 1 matching FEATURE map + 1–5 source symbols**. Do not load the full repository, full SYMBOL-MAP, all feature maps, all JS/CSS, or the theme unless a checkpoint proves escalation is needed.

### Chat: üzenet nem megy / nem jelenik meg / rossz beszélgetés nyílik
**SYMPTOM:** Chat (Üzenetek) hiba — indítás, küldés, csatolmány, olvasatlan jelvény  
**FEATURE:** CHAT  
**START HERE:** `FEATURES/CHAT.md` → `includes/class-kmae-conversations.php`  
**EXPECTED:** `start_conversation` → `KMAE_Conversations::create()`; `send_conversation_message` → `save_message()` → `kmae_conversation_message_created`.  
**NEXT IF TRUE:** `templates/offer-thread.php` / `templates/partials/conversation-free-chat.php` (render), `assets/js/public.js` (composer, poll).  
**NEXT IF FALSE:** `public/class-kmae-public.php` form branch + `KMAE_Auth::should_gate_for_verification`.  
**SEARCH TERMS:** `start_conversation send_conversation_message save_message conversation_thread_url count_unread_conversations`  
**PLUGIN BOUNDARY:** chat styling → theme `offer-thread.css` / `messages.css`.  
**VERIFY:** VERIFIED against source 2026-10-10.

### Chat ajánlat: ajánlat / elfogadás / Foglalt / Mégse kelt el / telefonszám / 7 napos eladott / eladott számláló / visszavonás / hirdetéstörlés
**SYMPTOM:** nincs [Visszavonom] / nem vonja vissza; törlés után eltűnt a chat a listából / nincs „Az eladó törölte a hirdetést.” sáv / nem kapott rendszerüzenetet a Foglalt vevő / a törölt hirdetés eladásnak számított; „Ajánlatot teszek” nem látszik vagy nem tűnik el; [Elfogadom] hiányzik; „Foglalt” / „Újra elérhető” rossz; nem jön rendszerüzenet; nem látszik a telefonszám; [Mégse kelt el] nem megy; 7 nap után sem lett eladott / nem zárult le a chat; rossz az „N eladott autó”  
**FEATURE:** CHAT  
**START HERE:** `FEATURES/CHAT.md` → `includes/class-kmae-chat-offers.php`  
**EXPECTED:** `can_offer` = nyitott chat + aktív hirdetés + nincs `accepted` sor; `accept()` → `accepted` + többi `live` → `closed` + `system:` üzenetek; `cancel_sale()` → `withdrawn`; hourly `kmae_finalize_chat_sales` → `finalize_sales()`: 7 napos `accepted` → `sold` + hirdetés `closed` + `KMAE_Conversations::close_for_listing`; `listing_state()` → `sold` / `reserved` / `reopened`; számláló = `count_sold_for_seller` (`sold` sorok); `retract()` `live` → `retracted` (`retract_chat_offer`); törlés: `KMAE_Listings::force_delete_listing` → `KMAE_Chat_Offers::on_listing_deleted` → `KMAE_Conversations::withdraw_alerts_for_listing($id, $keep_unread)` (cím-pillanatkép `kmae_conversations.listing_title`, 4.65.0).  
**NEXT IF TRUE:** render: `templates/partials/conversation-timeline.php` (buborék, [Elfogadom], rendszerüzenet), `templates/offer-thread.php` (`.kmae-chat-deal` sáv, telefonszám, [Mégse kelt el]), `templates/single-listing.php` (`.kmae-single-sale-state`).  
**NEXT IF FALSE:** `public/class-kmae-public.php` `process_chat_offer_decision` / `process_chat_offer`; `kmae_chat_offers.accepted_at` oszlop megvan-e (4.62.0 upgrade).  
**SEARCH TERMS:** `retract_chat_offer kmae-chat-offer-retract on_listing_deleted listing_title listing_gone kmae-chat-deleted kmae-inbox__gone accept_chat_offer cancel_chat_sale send_chat_offer get_accepted_for_listing listing_state is_system_message kmae-chat-deal kmae-single-sale-state finalize_sales kmae_finalize_chat_sales count_sold_for_seller kmae-listing-seller__sold`  
**PLUGIN BOUNDARY:** kinézet → theme `offer-thread.css` (buborék `.is-{status}`, `.kmae-chat-offer-accept`, `.kmae-chat-system`, `.kmae-chat-deal`), `single-listing.css` (`.kmae-single-sale-state`).  
**VERIFY:** VERIFIED against source 2026-10-10.

### Adásvételi szerződés / „Mire figyelj” kép (elfogadott ajánlat után)
**SYMPTOM:** nincs „Adásvételi szerződés” gomb / „Mire figyelj” kép a Foglalt sávban; a szerződés oldal hibát ír; a Word letöltés üres / rossz mezőbe ír; a nyomtatás a fejlécet is nyomtatja; a nagyító nem nagyít  
**FEATURE:** CHAT  
**START HERE:** `FEATURES/CHAT.md` → `includes/class-kmae-sale-contract.php`  
**EXPECTED:** gomb csak `$deal_offer` mellett (`offer-thread.php`); oldal `?kmae_contract=1` → `KMAE_Sale_Contract::render` (`get_deal`); POST `download_sale_contract` → `process_download` → `build_docx` (`templates/contract/adasveteli/word/document.xml` `{{kulcs}}`).  
**NEXT IF TRUE:** mezőhely a Wordben → a sablon `document.xml` helyőrzői + `fields()` kulcsai; kinézet → theme `sale-contract.css`.  
**NEXT IF FALSE:** `public/class-kmae-shortcodes.php` `messages()` ág, `public/class-kmae-public.php` `handle_forms` dispatch.  
**SEARCH TERMS:** `KMAE_Sale_Contract download_sale_contract kmae_contract kmae-sale-contract-active kmae-chat-deal__contract kmae-chat-tips__btn kmae-chat-side download_buyer_checklist data-kmae-tips-image kmae-tips-viewer data-kmae-print`  
**PLUGIN BOUNDARY:** theme `sale-contract.css` (oldal + print), `offer-thread.css` (gomb, kép, `.kmae-tips-viewer`), `functions.php` enqueue.  
**VERIFY:** VERIFIED against source 2026-10-10.

### Bejelentés: gomb / ablak / admin Jelentések / piros számláló / push
**SYMPTOM:** nincs „Bejelentés” gomb a hirdetésen vagy a chatben; nem nyílik az ablak; beküldés után hiba; az admin menüben nincs piros szám; nem jön push / e-mail; az admin nem látja a beszélgetést  
**FEATURE:** REPORTS  
**START HERE:** `FEATURES/REPORTS.md` → `includes/class-kmae-reports.php`  
**EXPECTED:** gomb = `templates/partials/report-form.php` (hirdetésen `! $is_owner`, chat fejlécben mindig); ablak `<dialog>` + `public.js` `data-kmae-report-open`; POST `submit_report` → `process_report` → `KMAE_Reports::submit` → `kmae_reports` sor + `notify_admins` (e-mail minden `manage_options` usernek `KMAE_Brand::send_mail` + `KMAE_Live_Signal::bump` → Web Push); admin buborék `count_new()` (`KMAE_Admin::add_menu`).  
**NEXT IF TRUE:** admin nézet `admin/views/reports.php` (`get_conversation_messages`, `get_against_user`, `reporter_stats`); kinézet theme `single-listing.css` / `offer-thread.css` `.kmae-report*`, admin `assets/css/admin.css` `.kmae-reports*`.  
**NEXT IF FALSE:** tábla létezik-e (4.64.0 upgrade, `KMAE_Activator::create_tables`); `handle_forms` dispatch; push: az adminnak be kell kapcsolnia a pusht az oldalon (`KMAE_Push::is_enabled`).  
**SEARCH TERMS:** `KMAE_Reports submit_report kmae_report_nonce report-form kmae-report data-kmae-report-open kmae-reports kmae_report_update count_new notify_admins`  
**PLUGIN BOUNDARY:** theme `single-listing.css`, `offer-thread.css` (csak a `.kmae-report*` blokk a fájlok végén).  
**VERIFY:** VERIFIED against source 2026-10-10.

### Wizard autosave nem ment
**SYMPTOM:** Wizard autosave nem ment  
**FEATURE:** LISTING-WIZARD-DRAFTS  
**START HERE:** `assets/js/listing-wizard.js :: action `kmae_save_listing_draft``  
**EXPECTED:** authenticated WP AJAX request.  
**NEXT IF TRUE:** `includes/class-kmae-listing-drafts.php :: KMAE_Listing_Drafts::ajax_save()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `kmae_save_listing_draft ajax_save wizard draft`  
**DO NOT LOAD:** Do not load final create-listing flow yet.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Wizard visszatöltéskor rossz adat jelenik meg
**SYMPTOM:** Wizard visszatöltéskor rossz adat jelenik meg  
**FEATURE:** LISTING-WIZARD-DRAFTS  
**START HERE:** `includes/class-kmae-listing-drafts.php :: get_user_draft()/to_client()`  
**EXPECTED:** server serializes owned draft for client.  
**NEXT IF TRUE:** `assets/js/listing-wizard.js :: draft restore/state logic`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `get_user_draft to_client draft restore listing-wizard`  
**DO NOT LOAD:** Do not load moderation/photo processing unless affected field belongs there.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Hirdetés elküldhető, de nem publikálódik
**SYMPTOM:** Hirdetés elküldhető, de nem publikálódik  
**FEATURE:** LISTINGS + MODERATION + PHOTO-MOTOR  
**START HERE:** `public/class-kmae-public.php :: KMAE_Public::process_create_listing()`  
**EXPECTED:** validates seller/location/vehicle/moderation/photo state and inserts listing.  
**NEXT IF TRUE:** `includes/class-kmae-listings.php :: KMAE_Listings::activate_if_photos_ready()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `process_create_listing awaiting_photos activate_if_photos_ready moderation`  
**DO NOT LOAD:** Do not inspect browse/search until publication state is correct.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Fotók megvannak, de awaiting_photos marad
**SYMPTOM:** Fotók megvannak, de awaiting_photos marad  
**FEATURE:** PHOTO-MOTOR + LISTINGS  
**START HERE:** `includes/class-kmae-photo-motor.php :: KMAE_Photo_Motor::validate_request()/required slot checks`  
**EXPECTED:** photo readiness controls activation path.  
**NEXT IF TRUE:** `includes/class-kmae-listings.php :: KMAE_Listings::activate_if_photos_ready()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `required_slots validate_request activate_if_photos_ready awaiting_photos`  
**DO NOT LOAD:** Do not load chat code.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Fotó kliensen elfogadott, szerveren elutasított
**SYMPTOM:** Fotó kliensen elfogadott, szerveren elutasított  
**FEATURE:** PHOTO-MOTOR  
**START HERE:** `assets/js/photo-motor.js :: client validation/upload flow`  
**EXPECTED:** client config mirrors source thresholds but server is authoritative.  
**NEXT IF TRUE:** `includes/class-kmae-photo-motor.php :: validate_request() + includes/class-kmae-images.php`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `photo motor client_config validate_request image mime size blur`  
**DO NOT LOAD:** Do not load listing lifecycle unless upload succeeds.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Márka/modell kereső nem működik
**SYMPTOM:** Márka/modell kereső nem működik  
**FEATURE:** VEHICLE  
**START HERE:** `assets/js/vehicle-catalog.js :: catalog/autocomplete handlers`  
**EXPECTED:** reads localized catalog and form fields.  
**NEXT IF TRUE:** `includes/class-kmae-vehicle.php + public/class-kmae-public.php :: enqueue_vehicle_catalog()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `vehicle-catalog kmaeVehicleCatalog vehicle_brand vehicle_model`  
**DO NOT LOAD:** Do not load chat code.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Browse kártyák rossz autókat adnak
**SYMPTOM:** Browse kártyák rossz autókat adnak  
**FEATURE:** BROWSE-SEARCH  
**START HERE:** `includes/class-kmae-browse.php :: personalize_listings()`  
**EXPECTED:** personalization merges based on pass history.  
**NEXT IF TRUE:** `includes/class-kmae-query.php :: query construction`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `personalize_listings passed filter_by_wizard`  
**DO NOT LOAD:** Do not load templates until ID set is correct.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Swipe pass nem marad meg
**SYMPTOM:** Swipe pass nem marad meg  
**FEATURE:** BROWSE-SEARCH  
**START HERE:** `assets/js/swipe.js :: `kmae_browse_pass``  
**EXPECTED:** WP AJAX to public controller.  
**NEXT IF TRUE:** `public/class-kmae-public.php :: ajax_browse_pass() → KMAE_Browse::record_pass()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `kmae_browse_pass ajax_browse_pass record_pass _kmae_browse_passed`  
**DO NOT LOAD:** Do not load chat persistence.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Live badge/számláló nem frissül
**SYMPTOM:** Live badge/számláló nem frissül  
**FEATURE:** LIVE-NAV  
**START HERE:** `assets/js/public.js :: `kmae_poll_page``  
**EXPECTED:** WP AJAX poll.  
**NEXT IF TRUE:** `public/class-kmae-public.php :: ajax_poll_page() → KMAE_Live_Page`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `kmae_poll_page ajax_poll_page fingerprint unseen count_unread_conversations nav badge`  
**DO NOT LOAD:** Do not load notifications email.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Bejelentkezés/regisztráció nem működik
**SYMPTOM:** Bejelentkezés/regisztráció nem működik  
**FEATURE:** AUTH  
**START HERE:** `public/class-kmae-public.php :: admin_login()/admin_register()`  
**EXPECTED:** admin-post request delegates auth rules.  
**NEXT IF TRUE:** `includes/class-kmae-auth.php :: auth helpers/verification`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `admin_login admin_register email verification wp_signon`  
**DO NOT LOAD:** Do not load Google auth unless Google flow used.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Email megerősítés miatt indokolatlanul tiltott művelet
**SYMPTOM:** Email megerősítés miatt indokolatlanul tiltott művelet  
**FEATURE:** AUTH  
**START HERE:** `includes/class-kmae-auth.php :: verification state/check helpers`  
**EXPECTED:** server gates actions based on verification requirement.  
**NEXT IF TRUE:** `public/class-kmae-public.php :: handle_forms()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `email verify requires verification handle_forms gate`  
**DO NOT LOAD:** Do not bypass server gate in JS.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Google login callback hibás
**SYMPTOM:** Google login callback hibás  
**FEATURE:** GOOGLE-AUTH  
**START HERE:** `includes/class-kmae-google-auth.php :: maybe_handle_request()/handle_callback()`  
**EXPECTED:** authorization callback resolves/links user then completion state.  
**NEXT IF TRUE:** `includes/class-kmae-google-auth.php :: enforce_completion()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `handle_callback redirect_to_google enforce_completion client_id redirect_uri`  
**DO NOT LOAD:** Do not load password login unless account-linking issue.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Céges hirdetés helye rossz
**SYMPTOM:** Céges hirdetés helye rossz  
**FEATURE:** PROFILE-COMPANY-SETTINGS + LISTINGS  
**START HERE:** `includes/class-kmae-company.php :: apply_listing_location()`  
**EXPECTED:** company account location can override listing location save.  
**NEXT IF TRUE:** `includes/class-kmae-listings.php :: save_meta_from_post()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `apply_listing_location company county settlement save_meta_from_post`  
**DO NOT LOAD:** Browse map only after stored listing location verified.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Hirdetés nem törölhető / nem reaktiválható
**SYMPTOM:** Hirdetés nem törölhető / nem reaktiválható  
**FEATURE:** LISTINGS  
**START HERE:** `includes/class-kmae-listings.php :: can_delete_listing()/can_reactivate_listing()`  
**EXPECTED:** delete is always allowed for the owner (2026-10-10, ownership in `delete_listing()`); reactivate is archive-lifecycle gated.  
**NEXT IF TRUE:** `public/class-kmae-public.php :: admin_delete_listing()/admin_reactivate_listing()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `can_delete_listing can_reactivate_listing admin_delete_listing reactivate`  
**DO NOT LOAD:** Do not load admin backend unless wp-admin path.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Hirdetés nem jár le / nem jön lejárati levél / hosszabbítás nem megy / lejárt hirdetés linkje
**SYMPTOM:** 60 nap után is aktív, nem jön e-mail, vagy a Hosszabbítás gomb nem működik; a lejárt hirdetés linkje a teljes adatlapot mutatja „Ez az autó már nem elérhető” helyett / nincsenek hasonló autók  
**LINK:** `KMAE_Post_Types::template_loader` + `is_unavailable_for_viewer` → `templates/listing-unavailable.php` (`KMAE_Query::get_similar_listings`); tulaj/admin szándékosan a rendes adatlapot látja.  
**FEATURE:** LISTINGS + CRON-ARCHIVE  
**START HERE:** `includes/class-kmae-listings.php :: expire_listings()/expiry_clock_start()`  
**EXPECTED:** hourly `kmae_expire_listings`; `active` + clock ≥ `EXPIRE_DAYS` + no `accepted` chat offer → `expired` + `notify_listing_expired()`.  
**NEXT IF TRUE:** `public/class-kmae-public.php :: admin_renew_listing()` → `KMAE_Listings::renew_listing()`; UI `templates/my-listings.php` (`$is_expired`, `$expires_days`).  
**NEXT IF FALSE:** előbb fut-e a WP-Cron (lásd lent), majd `includes/class-kmae-activator.php :: schedule_cron()`, `KMAE_Chat_Offers::get_accepted_for_listing`.  
**SEARCH TERMS:** `listing-unavailable is_unavailable_for_viewer get_similar_listings due_for_expiry kmae_expire_listings expire_listings renew_listing kmae_renew_listing STATUS_EXPIRED _kmae_renewed_at _kmae_expired_at notify_listing_expired EXPIRE_DAYS`  
**DO NOT LOAD:** browse/chat templates.  
**PLUGIN BOUNDARY:** WP-Cron csak látogatáskor fut; `wp_mail`.  
**VERIFY:** VERIFIED against source snapshot.

### Háttérfolyamat nem fut / 7 vagy 60 nap után sem változott semmi
**SYMPTOM:** foglalt hirdetés 7 nap után sem zárult le; 60 nap után sem lejárt  
**FEATURE:** CRON-ARCHIVE  
**START HERE:** `includes/class-kmae-activator.php :: schedule_cron()` (`wp_next_scheduled`)  
**EXPECTED:** a tárhely cronja 5 percenként hívja `wp-cron.php?kmae_server_cron=1`-et; minden kmae feladat be van ütemezve. (A Rendszerfigyelő 2026-10-21 óta törölve.)  
**NEXT IF TRUE:** a feladat logikája: `KMAE_Chat_Offers::finalize_sales()` / `KMAE_Listings::expire_listings()` (`due_for_expiry`).  
**NEXT IF FALSE:** a nyilvános oldalak cache-ből jönnek, ezért látogatás nem indít WP-Cront → tárhely cron kell; `DISABLE_WP_CRON`.  
**SEARCH TERMS:** `schedule_cron wp_next_scheduled kmae_finalize_chat_sales kmae_expire_listings due_for_expiry`  
**DO NOT LOAD:** frontend, templates.  
**PLUGIN BOUNDARY:** WP-Cron + tárhely (RackForest) cron feladat.  
**VERIFY:** VERIFIED against source snapshot.

### Archivált hirdetés nem törlődik cronból
**SYMPTOM:** Archivált hirdetés nem törlődik cronból  
**FEATURE:** CRON-ARCHIVE  
**START HERE:** `includes/class-kmae-activator.php :: schedule_cron()`  
**EXPECTED:** daily `kmae_purge_archived_listings` event.  
**NEXT IF TRUE:** `includes/class-kmae-listings.php :: purge_expired_archived()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `schedule_cron kmae_purge_archived_listings purge_expired_archived force_delete_listing`  
**DO NOT LOAD:** Do not load frontend.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Contact form nem küld
**SYMPTOM:** Contact form nem küld  
**FEATURE:** CONTACT-FORM  
**START HERE:** `public/class-kmae-public.php :: admin_contact_form()`  
**EXPECTED:** validates nonce/input and sends to configured recipient.  
**NEXT IF TRUE:** `includes/class-kmae-settings.php :: contact_form_email()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `admin_contact_form contact_form_email wp_mail`  
**DO NOT LOAD:** Chat notification code unrelated.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Admin listing művelet rossz rekordot érint
**SYMPTOM:** Admin listing művelet rossz rekordot érint  
**FEATURE:** ADMIN  
**START HERE:** `admin/class-kmae-admin.php :: action handler`  
**EXPECTED:** admin handler checks request and delegates DB/listing mutation.  
**NEXT IF TRUE:** `includes/class-kmae-database.php / includes/class-kmae-listings.php :: targeted mutation`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `handle_delete_listing handle_set_status admin`  
**DO NOT LOAD:** Public forms unrelated.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Oldal/shortcode rossz template-et renderel
**SYMPTOM:** Oldal/shortcode rossz template-et renderel  
**FEATURE:** PAGES-ROUTING  
**START HERE:** `includes/class-kmae-pages.php :: shortcode_for()/url()/routing helpers`  
**EXPECTED:** page maps to shortcode/template path.  
**NEXT IF TRUE:** `public/class-kmae-shortcodes.php :: matching shortcode method`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `shortcode_for template_include shortcode KMAE_Pages KMAE_Shortcodes`  
**DO NOT LOAD:** Do not load business services unless data passed into template is wrong.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

### Plugin induláskor class/hook hiányzik
**SYMPTOM:** Plugin induláskor class/hook hiányzik  
**FEATURE:** BOOT-ARCHITECTURE  
**START HERE:** `ki-mit-adna-erte.php :: kmae_run()`  
**EXPECTED:** creates KMAE_Plugin and calls run.  
**NEXT IF TRUE:** `includes/class-kmae-plugin.php :: KMAE_Plugin::run()`  
**NEXT IF FALSE:** stay at the START HERE producer/guard and inspect its immediate dependencies only.  
**SEARCH TERMS:** `kmae_run KMAE_Plugin run loader require init`  
**DO NOT LOAD:** Do not load all features by default.  
**PLUGIN BOUNDARY:** plugin-owned unless the checkpoint explicitly reaches WordPress mail/theme/external service.  
**VERIFY:** VERIFIED against source snapshot.

