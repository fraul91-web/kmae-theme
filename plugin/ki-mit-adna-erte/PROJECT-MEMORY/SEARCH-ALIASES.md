# SEARCH ALIASES — PLUGIN V2

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

MAP-TYPE: INDEX

| User wording / UI concept | Technical aliases to search |
|---|---|
| kapcsolat az eladóval | `start_conversation`, `KMAE_Conversations::create`, `conversation_thread_url`, `offer-forms.php` |
| üzenet küldése (hirdetésről) | listing CTA `Üzenet küldése`; `start_conversation`, `KMAE_Conversations::create`, `conversation_thread_url` |
| chat / beszélgetés | `[kmae_messages]`, `offer-thread.php`, `conversation-free-chat.php`, `conversation-timeline.php`, `get_thread_events` |
| ajánlatot teszek / ajánlat buborék | `send_chat_offer`, `KMAE_Chat_Offers::submit`, `can_offer`, `.kmae-chat-offer`, `.kmae-chat-offer-bubble` |
| elfogadom / ajánlat elfogadása | `accept_chat_offer`, `KMAE_Chat_Offers::accept`, `$can_accept`, `.kmae-chat-offer-accept` |
| foglalt / újra elérhető | `KMAE_Chat_Offers::listing_state`, `get_accepted_for_listing`, `.kmae-single-sale-state`, `.kmae-chat-listing-status.is-reserved` |
| mégse kelt el | `cancel_chat_sale`, `KMAE_Chat_Offers::cancel_sale`, `withdrawn`, `.kmae-chat-deal__cancel` |
| „az eladó egy másik ajánlatot fogadott el” / rendszerüzenet | `post_system_message`, `is_system_message`, `system:` idempotency key, `.kmae-chat-system` |
| telefonszám a chatben / egymás telefonszáma | `offer-thread.php` `$deal_phone`, `KMAE_Auth::get_profile`, `.kmae-chat-deal__phone` |
| eladott / 7 nap / hirdetés lezárul eladás után / chat csak olvasható | `kmae_finalize_chat_sales`, `KMAE_Chat_Offers::finalize_sales`, `SOLD_AFTER_DAYS`, `sold`, `KMAE_Conversations::close_for_listing`, `KMAE_Meta::STATUS_CLOSED` |
| adásvételi szerződés / szerződés letöltés / Word / nyomtatás | `KMAE_Sale_Contract`, `download_sale_contract`, `kmae_contract`, `templates/sale-contract.php`, `templates/contract/adasveteli/`, `.kmae-chat-deal__contract`, `data-kmae-print` |
| ellenőrzőlista / Excel / xlsx / megfelelt | `download_buyer_checklist`, `KMAE_Sale_Contract::build_checklist_xlsx`, `checklist_items`, `.kmae-chat-checklist` |
| mire figyelj / tanácsok a megtekintéshez / nagyító | `KMAE_Sale_Contract::tips_image_url`, `assets/images/mire-figyelj.jpg`, `.kmae-chat-tips__btn`, `data-kmae-tips-image`, `.kmae-tips-viewer` (`public.js`) |
| N eladott autó / eladó számlálója | `KMAE_Chat_Offers::count_sold_for_seller`, `sold_label`, `single-listing.php` `.kmae-listing-seller__sold` |
| bejelentés / jelentés / report / csaló bejelentése | `KMAE_Reports`, `submit_report`, `templates/partials/report-form.php`, `.kmae-report`, `data-kmae-report-open` |
| jelentések admin / piros szám az Albira menüben | `kmae-reports`, `KMAE_Admin::render_reports`, `KMAE_Reports::count_new`, `awaiting-mod`, `admin/views/reports.php` |
| bejelentés oka / Más / miért jelented be | `KMAE_Reports::reasons`, `REASON_OTHER`, `kmae_report_reason`, `kmae_report_details`, `report_details_missing` |
| bejelentés igaz / nem igaz / admin döntés | `kmae_report_update`, `KMAE_Reports::update_decision`, `STATUS_VALID`, `STATUS_INVALID`, `admin_note` |
| admin látja a chatet / beszélgetés a jelentésnél | `KMAE_Reports::get_conversation_messages`, `#kmae-report-chat`, `.kmae-reports-chat` |
| lejárat / 60 nap / lejárt hirdetés / hosszabbítás / lejár N nap múlva | `kmae_expire_listings`, `KMAE_Listings::expire_listings`, `EXPIRE_DAYS`, `expiry_clock_start`, `days_until_expiry`, `renew_listing`, `kmae_renew_listing`, `STATUS_EXPIRED`, `notify_listing_expired`, `my-listings.php` |
| visszavonom / ajánlat visszavonása | `retract_chat_offer`, `KMAE_Chat_Offers::retract`, `STATUS_RETRACTED`, `retracted`, `.kmae-chat-offer-retract` |
| az eladó törölte a hirdetést / törölt hirdetés chatje / chat eltűnt törlés után | `KMAE_Chat_Offers::on_listing_deleted`, `KMAE_Conversations::withdraw_alerts_for_listing`, `kmae_conversations.listing_title`, `listing_gone`, `.kmae-chat-deleted`, `.kmae-inbox__gone`, `system:listing_deleted:` |
| foglalt hirdetés törlése / elfogadott ajánlatod van, biztosan törlöd | `data-kmae-delete-reserved`, `my-listings.js` `kmaeDeleteReserved`, `KMAE_Chat_Offers::get_accepted_for_listing` |
| ez az autó már nem elérhető / lejárt hirdetés linkje / hasonló autók | `templates/listing-unavailable.php`, `KMAE_Post_Types::is_unavailable_for_viewer`, `KMAE_Query::get_similar_listings`, `.kmae-listing-unavailable` |
| bejelentés e-mail / admin levél bejelentésről | `KMAE_Reports::notify_admins`, `KMAE_Brand::send_mail` |
| hirdetés törlése bármikor / nem törölhető | `KMAE_Listings::can_delete_listing`, `delete_listing`, `admin_delete_listing`, `$show_delete` |
| X ajánlat érkezett | `count_live_for_listing`, `count_label`, `.kmae-single-offer-count`, `my-listings.php` |
| olvasatlan üzenet jelvény | `ajax_poll_page`, `KMAE_Live_Page`, unseen, `kmae_poll_page`, `get_conversation_alert_state` |
| wizard mentés | `kmae_save_listing_draft`, `ajax_save`, `_kmae_wizard_draft` |
| wizard folytatás | `get_user_draft`, `get_latest_draft`, `to_client`, restore |
| fotó 7/7 | `required_slots`, `required_count`, `count_required_filled`, photo-motor |
| awaiting photos | `awaiting_photos`, `activate_if_photos_ready` |
| márka/modell | `vehicle-catalog.js`, `kmaeVehicleCatalog`, `vehicle_brand`, `vehicle_model` |
| hirdetés publikálás | `process_create_listing`, `kmae_listing_published`, `activate_if_photos_ready` |
| céges telephely | `KMAE_Company`, `apply_listing_location` |
| kereső / browse | `KMAE_Query`, `KMAE_Browse`, `filter_by_wizard`, `personalize_listings` |
| pass / kihagyás | `kmae_browse_pass`, `ajax_browse_pass`, `record_pass`, `_kmae_browse_passed` |
| login | `admin_post_kmae_login`, `admin_login`, `KMAE_Auth` |
| regisztráció | `admin_post_kmae_register`, `admin_register`, account type |
| email megerősítés | `verify`, `resend_verification`, `maybe_handle_email_verify_link` |
| Google login | `KMAE_Google_Auth`, `handle_callback`, `redirect_to_google` |
| profil | `admin_post_kmae_save_profile`, `admin_save_profile`, `KMAE_Company` |
| archív / törlés cron | `kmae_purge_archived_listings`, `purge_expired_archived`, `force_delete_listing` |
| kapcsolat űrlap | `admin_contact_form`, `contact_form_email`, `wp_mail` |
| shortcode / oldal | `KMAE_Pages`, `KMAE_Shortcodes`, `shortcode_for`, managed page |
| plugin nem indul | `kmae_run`, `KMAE_Plugin::run`, `KMAE_Loader` |

Search exact hook/action strings before broad text when a symptom names a concrete interaction.
