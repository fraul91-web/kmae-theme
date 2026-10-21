# FILE MAP

> 2026-10-10: the offer / deal / rating system was **deleted**; only the chat remains (see `FEATURES/CHAT.md`). Rows/sections of deleted files were removed.

Inventory: **128 files**. Generated/vendor/cache directories were not present in this ZIP.

## Source files

| File | Kind | Size | Main symbols / role | Related feature |
|---|---:|---:|---|---|
| `admin/class-kmae-admin.php` | PHP | 12,867 B | WordPress admin integration. Symbols: KMAE_Admin, __construct, add_menu, admin_notices, render_settings, render_dashboard, handle_delete_listing +10 | `ADMIN` |
| `admin/views/dashboard.php` | PHP | 6,637 B | Admin dashboard view. | `ADMIN` |
| `admin/views/meta-box-listing.php` | PHP | 7,235 B | Admin meta box view for listing settings. | `ADMIN` |
| `admin/views/reports.php` | PHP | — | Albira → Jelentések: lista (Új/Igaz/Nem igaz/Összes), részletek, döntés form (`kmae_report_update`), a felek beszélgetése (`#kmae-report-chat`), előzmények. | `REPORTS`, `ADMIN` |
| `admin/views/settings.php` | PHP | 4,506 B | Plugin settings admin page. | `ADMIN` |
| `assets/css/admin.css` | CSS | 1,535 B | Frontend/admin stylesheet. | `PAGES-ROUTING` |
| `assets/css/public.css` | CSS | 83,929 B | Frontend/admin stylesheet. | `PAGES-ROUTING` |
| `assets/data/counties-paths.json` | JSON | 128,388 B | Static JSON data used by plugin source. | `BROWSE-SEARCH` |
| `assets/data/vehicle-catalog.json` | JSON | 17,839 B | Static JSON data used by plugin source. | `VEHICLE` |
| `assets/images/.gitkeep` | file | 0 B | Project source/asset file. | `BOOT-ARCHITECTURE` |
| `assets/js/auth-account-type.js` | JS | 1,453 B | Project source/asset file. | `AUTH` |
| `assets/js/browse-wizard.js` | JS | 8,095 B | One handler for the county link, its SVG children and the country button. wizard.addEventListener('click', function (event) { var choice = event.target.closest('[data-county]'); if (!choice \|\| !wizard.contains(choice)) { return; } var slug = choice.getAttribut | `BROWSE-SEARCH` |
| `assets/js/home-greeting.js` | JS | 934 B | PHP handles ordinary visits; a back/forward cache restore skips PHP. window.addEventListener('pageshow', function (event) { if (!event.persisted) { return; } document.querySelectorAll('[data-kmae-home-welcome]').forEach(function (welcome) { welcome.remove(); } | `DASHBOARD-HUBS` |
| `assets/js/listing-field-guards.js` | JS | 3,390 B | Beviteli szabályok a hirdetés űrlapon. 1) Ahol számot várunk, oda ne kerülhessen betű vagy szimbólum; ahol szöveget, | `LISTING-WIZARD-DRAFTS` |
| `assets/js/listing-form-numbers.js` | JS | 7,832 B | Project source/asset file. | `LISTING-WIZARD-DRAFTS` |
| `assets/js/listing-wizard-preview.js` | JS | 8,438 B | A még ki nem töltött mező jele. Mondat helyett gondolatjel: a panel így nyugodt marad, és nem tűnik tele hibaüzenettel. | `LISTING-WIZARD-DRAFTS` |
| `assets/js/listing-wizard.js` | JS | 144,946 B | Step registry. `target` points at markup that already exists in the create form; the engine | `LISTING-WIZARD-DRAFTS` |
| `assets/js/map.js` | JS | 1,674 B | Project source/asset file. | `BROWSE-SEARCH` |
| `assets/js/my-listings.js` | JS | 4,102 B | Albira — Saját hirdetéseim: félbehagyott hirdetés szerkesztési választója. A Szerkesztés gomb fölött ívben kinyíló két kerek opció; máshová kattintva bezárul. | `LISTINGS` |
| `assets/js/nav.js` | JS | 3,107 B | Csak a jobb szélen (Blocksy header) — ne a menülista végére. injectLogoutBlocksy(); } function removeMenuLogoutDuplicates() { document.querySelectorAll('nav ul.menu li.kmae-nav-logout, #header-menu-1 li.kmae-nav-logout').forEach(function (el) { el.remove(); }) | `LIVE-NAV` |
| `assets/js/phone-field.js` | JS | 2,023 B | * Telefonmező: csak számjegyek, magyar tördeléssel (30 111 11 11). * A +36 előtag a mező mellett áll fixen, ide csak a maradék kilenc * számjegy kerül. Bármelyik űrlapon működik, ahol a mezőn ott a * `data-kmae-phone` jelölés. | `AUTH`, `PROFILE-COMPANY-SETTINGS` |
| `assets/js/photo-motor.js` | JS | 25,369 B | Ráhúzás közben a doboz kiemelődik. A fájlt maga a mező kapja meg, mert átlátszóan a doboz fölött fekszik — így nem kell külön kezelni a dobást. | `PHOTO-MOTOR` |
| `assets/js/image-zoom.js` + `assets/css/image-zoom.css` | JS/CSS | 13,8 KB | Képnagyító (csak PC, 2026-10-21): görgő/±/dupla kattintás, húzás, kis térkép a látott terület négyzetével, „Teljes kép”/Esc = vissza 100%-ra (a galéria marad). Csak figyeli a nézeteket: `.kmae-sw2-media:fullscreen`, `.kmae-gallery:fullscreen`, `.kmae-lightbox`, `.kmae-simple-lightbox`, `.kmae-upload-lightbox`; új galéria-nézetnél a `find()`-ba kell felvenni. Enqueue: `KMAE_Public::enqueue`. | `BROWSE-V2`, `PHOTO-MOTOR` |
| `assets/js/public.js` | JS | — | Create-listing price mode, gallery, chat scroll/composer/emoji/attachments/lightbox, live poll + nav badge | `CHAT`, `LIVE-NAV` |
| `assets/js/swipe.js` | JS | 1,923 B | Project source/asset file. | `BROWSE-SEARCH` |
| `assets/js/vehicle-catalog.js` | JS | 16,849 B | A wizardon a népszerű márkák maradnak; a teljes lista a mező jobb alatti linkről nyílik, kívülre kattintás bezárja. | `VEHICLE` |
| `assets/svg/hungary-counties.svg` | SVG | 159,786 B | Static SVG asset used by plugin source. | `BROWSE-SEARCH` |
| `includes/class-kmae-activator.php` | PHP | 6,913 B | Plugin activation: database tables, default categories, rewrite rules. Symbols: KMAE_Activator, activate, schedule_cron, create_tables, migrate_price_modes, maybe_upgrade | `BOOT-ARCHITECTURE`, `CRON-ARCHIVE` |
| `includes/class-kmae-auth.php` | PHP | 26,812 B | User registration, login, and profile storage. Symbols: KMAE_Auth, login_url, register_url, lost_password_url, reset_password_url, init, filter_lostpassword_url, filter_reset_message +32 | `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS` |
| `includes/class-kmae-back.php` | PHP | 618 B | Back URL helpers for inline page navigation. Symbols: KMAE_Back, browse_url, home_url, home_label, listing_back_url | `BOOT-ARCHITECTURE` |
| `includes/class-kmae-brand.php` | PHP | 8,927 B | Official Albira brand assets and HTML mail wrapper. Symbols: KMAE_Brand, init, logo_guest, logo_app, email_logo_url, email_mascot_url, logo_url, html_content_type +4 | `NOTIFICATIONS`, `CONTACT-FORM` |
| `includes/class-kmae-browse.php` | PHP | 7,569 B | Browse personalization: favorites, resurface passes. Symbols: KMAE_Browse, get_passed_listing_ids, record_pass, personalize_listings, merge_with_passed_interval, parse_wizard_params, parse_bodies_param +6 | `BROWSE-SEARCH` |
| `includes/class-kmae-chat-offers.php` | PHP | — | Chat price offers + sale state: submit, retract ([Visszavonom]), on_listing_deleted (hirdetéstörlés: rendszerüzenet + ajánlatok lezárása), accept (Foglalt), cancel_sale (Mégse kelt el), close_sale (Lezárom → eladás), finalize_sales (7 napos lezárás, nem eladás → `lapsed`, cron `kmae_finalize_chat_sales`), sold counter, listing_state, system messages. Symbols: KMAE_Chat_Offers, init, submit, retract, on_listing_deleted, STATUS_RETRACTED, STATUS_LAPSED, accept, cancel_sale, close_sale, finalize_sales, count_sold_for_seller, sold_label, can_offer, get, get_by_message, get_live_for_conversation, get_accepted_for_listing, listing_state, is_system_message, count_live_for_listing, count_label | `CHAT` |
| `includes/class-kmae-reports.php` | PHP | — | Bejelentések (hirdetés + chat): okok, mentés, admin e-mail + push, admin lekérdezések. Symbols: KMAE_Reports, reasons, reason_label, statuses, submit, notify_admins (private), get, get_list, count_by_status, count_new, get_against_user, reporter_stats, update_decision, get_conversation_messages, admin_url | `REPORTS` |
| `includes/class-kmae-user-blocks.php` | PHP | — | Felhasználó tiltása (chat jobb oszlop): user meta `_kmae_blocked_users` (letiltott ID => tiltás DB-ideje); a tiltó elől rejti a letiltott tiltás utáni üzeneteit/ajánlatait (SQL szűrő a `KMAE_Conversations` lekérdezésekben), nincs e-mail/push/jelvény. Symbols: KMAE_User_Blocks, get_blocked, blocked_since, is_blocked, block, unblock, hidden_messages_sql, hides_message | `CHAT` |
| `includes/class-kmae-sale-contract.php` | PHP | — | Sale contract page + on-the-fly DOCX (own ZIP writer, nothing stored) + „Mire figyelj” image URL. Symbols: KMAE_Sale_Contract, fields, tips_image_url, page_url, is_contract_request, get_deal, prefill, render, process_download, build_docx | `CHAT` |
| `includes/class-kmae-company.php` | PHP | 3,917 B | Company / dealership seller profile fields. Symbols: KMAE_Company, get_company_name, get_address, get_county, get_city, has_listing_location, apply_listing_location +2 | `PROFILE-COMPANY-SETTINGS` |
| `includes/class-kmae-counties.php` | PHP | 4,476 B | Hungarian counties (megyék) for location and map browse. Symbols: KMAE_Counties, get_all, is_valid, is_listing_county, get_label, get_map_data, get_map_paths, get_map_label_xy +4 | `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH` |
| `includes/class-kmae-dashboard.php` | PHP | 19,842 B | Logged-in seller dashboard data + view tracking. Symbols: KMAE_Dashboard, greeting_case, take_home_greeting, init, maybe_track_listing_view, increment_views, get_listing_views, get_user_view_stats +9 | `DASHBOARD-HUBS` |
| `includes/class-kmae-database.php` | PHP | 12,805 B | Database table names and helper queries. Symbols: KMAE_Database, table, get_private_data, save_private_data, get_conversation_seen_at, mark_conversation_seen, mark_conversation_thread_seen, listing_target_is_live, get_conversation_alert_state, count_unread_conversations | `DASHBOARD-HUBS`, `LIVE-NAV` |
| `includes/class-kmae-deactivator.php` | PHP | 257 B | Plugin deactivation. Symbols: KMAE_Deactivator, deactivate | `BOOT-ARCHITECTURE`, `CRON-ARCHIVE` |
| `includes/class-kmae-google-auth.php` | PHP | 17,188 B | Belépés Google-fiókkal. A folyamat (OAuth 2.0 „authorization code"): Symbols: KMAE_Google_Auth, init, enforce_completion, admin_user_column, admin_user_column_value, client_id, client_secret, is_configured +13 | `GOOGLE-AUTH` |
| `includes/class-kmae-icons.php` | PHP | 10,823 B | Lucide outline icons (inline SVG). Symbols: KMAE_Icons, render, paths | `BOOT-ARCHITECTURE` |
| `includes/class-kmae-images.php` | PHP | 8,782 B | Upload processing: WebP conversion, resize, orientation meta. Symbols: KMAE_Images, init, supports_webp, orientation_from_dimensions, get_url, responsive_attrs, attachment_url_is_valid, attachment_url_exists +2 | `PHOTO-MOTOR` |
| `includes/class-kmae-launch.php` | PHP | 2,210 B | Launch mode — single-category focus (e.g. cars only at launch). Symbols: KMAE_Launch, mode, category_slug, is_single_category, should_skip_category_wizard, get_launch_category_id, get_available_categories, wizard_total_steps +2 | `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH` |
| `includes/class-kmae-listing-drafts.php` | PHP | 4,829 B | Félbehagyott hirdetések (wizard automatikus mentése). A piszkozat egy nem nyilvános `kmae_listing` bejegyzés (`post_status = draft`, Symbols: KMAE_Listing_Drafts, init, get_user_draft, get_latest_draft, to_client, ajax_save, discard_submitted | `LISTING-WIZARD-DRAFTS` |
| `includes/class-kmae-listing-wizard.php` | PHP | 12,081 B | Guided listing wizard: the single source of the wizard phases. The wizard keeps no server side state — a half-finished listing is simply Symbols: KMAE_Listing_Wizard, description_topics, assistant_intro, assist_inline_badge, assistant_reactions, assistant_publish, assistant_done, greeting_name +2 | `LISTING-WIZARD-DRAFTS` |
| `includes/class-kmae-listings.php` | PHP | 15,937 B | Delete listing and related data. Symbols: KMAE_Listings, EXPIRE_DAYS, init, expiry_clock_start, days_until_expiry, expire_listings, renew_listing, get_archive_lifecycle, is_archive_locked, can_reactivate_listing, can_edit_listing, can_delete_listing +12 | `LISTINGS`, `PHOTO-MOTOR`, `CHAT`, `CRON-ARCHIVE` |
| `includes/class-kmae-live-page.php` | PHP | 2,410 B | Live page fingerprint for auto-refresh polling. Symbols: KMAE_Live_Page, get_fingerprint | `LIVE-NAV` |
| `includes/class-kmae-legal.php` | PHP | – | ÁSZF/Felhasználási Feltételek: version, effective date, operator (impresszum) data, acceptance recording + one-time re-acceptance modal (AJAX `kmae_accept_terms`). Symbols: KMAE_Legal | `AUTH` |
| `assets/js/terms-accept.js` | JS | – | Re-acceptance modal behaviour (KMAE_Legal::render_prompt). | `AUTH` |
| `assets/css/terms-accept.css` | CSS | – | Re-acceptance modal styles. | `AUTH` |
| `includes/class-kmae-geo.php` | PHP | – | Listing coordinates for distance sort (Nominatim via cron, county-seat fallback). Symbols: KMAE_Geo | `BROWSE-SEARCH` |
| `templates/partials/browse-list.php` | PHP | – | Company list view of browse (50/page, pager). | `BROWSE-SEARCH` |
| `templates/partials/browse-distance.php` | PHP | – | Distance sort modal (locate → radius). | `BROWSE-SEARCH` |
| `assets/js/browse-sort.js` | JS | – | Browse sort menu + distance modal. | `BROWSE-SEARCH` |
| `assets/vendor/pdfjs/` | JS | – | Bundled pdf.js 3.11.174 (Apache-2.0) for PDF→image in photo-motor.js. | `PHOTO-MOTOR` |
| `includes/class-kmae-registration-scan.php` | PHP | – | Forgalmi engedély beolvasása: AJAX `kmae_registration_scan`, OpenAI call, mapping to wizard values, settings keys. Symbols: KMAE_Registration_Scan | `LISTING-WIZARD-DRAFTS` |
| `assets/js/registration-scan.js` | JS | – | Forgalmi scan modal: canvas crop, scan animation, review, hand-off to wizard. | `LISTING-WIZARD-DRAFTS` |
| `assets/css/registration-scan.css` | CSS | – | Forgalmi scan modal + wizard sidebar entry styles. | `LISTING-WIZARD-DRAFTS` |
| `templates/partials/registration-scan.php` | PHP | – | Forgalmi scan modal markup (pick / scan / review / error views). | `LISTING-WIZARD-DRAFTS` |
| `assets/js/listing-locate.js` | JS | – | „Jelenlegi helyzetem megadása” on the create page (wizard + simple editor) → `kmae_locate`. | `LISTING-WIZARD-DRAFTS` |
| `includes/class-kmae-perf.php` | PHP | – | Defers `wp_mail` until after the response (`pre_wp_mail` → `shutdown` 110), shared `finish_response()`. Symbols: KMAE_Perf |
| `includes/class-kmae-push.php` | PHP | – | Web Push: VAPID keys, subscriptions, AJAX, encrypted sending, post-registration prompt markup. Symbols: KMAE_Push | `LIVE-NAV` |
| `assets/js/push.js` / `assets/js/push-sw.js` / `assets/css/push.css` | JS/CSS | – | Push client (prompt, settings toggle, subscription) + service worker + prompt styles. | `LIVE-NAV` |
| `includes/class-kmae-live-signal.php` | PHP | – | Per-user change signal files (`uploads/kmae-live/<key>.v`) bumped from plugin events. Symbols: KMAE_Live_Signal | `LIVE-NAV` |
| `includes/class-kmae-loader.php` | PHP | 2,509 B | Plugin bootstrap requires. | `BOOT-ARCHITECTURE` |
| `includes/class-kmae-meta.php` | PHP | 4,794 B | Listing meta fields and save handlers. Symbols: KMAE_Meta, status_labels, is_publicly_visible, __construct, add_meta_boxes, render_meta_box, save_listing_meta, get_status +2 | `LISTINGS`, `ADMIN` |
| `includes/class-kmae-moderation.php` | PHP | 3,631 B | Automated listing moderation (rule-based, no external AI). Symbols: KMAE_Moderation, is_enabled, validate, contains_contact_info, count_uploaded_images, stash_errors_for_user, pull_errors_for_user | `MODERATION` |
| `includes/class-kmae-nav.php` | PHP | 6,565 B | Dynamic nav: login/register when logged out, logout when logged in. Works with classic menus, block navigation, and custom theme headers. Symbols: KMAE_Nav, __construct, enqueue_assets, inline_nav_css, filter_menu_items, filter_navigation_block, is_login_item, is_register_item +4 | `LIVE-NAV` |
| `includes/class-kmae-notifications.php` | PHP | — | Email notifications: chat message, listing active / unfinished / deleted, security mails; mail catalog + prefs. Symbols: KMAE_Notifications, init, mail_catalog, on_conversation_message_created, on_listing_deleted, on_listing_published, remind_unfinished_listings, send_offer_email | `NOTIFICATIONS` |
| `includes/class-kmae-privacy.php` | PHP | ~4 KB | User ID / felhasználónév elrejtése (REST users, author archívum, sitemap, oEmbed). Symbols: KMAE_Privacy | `AUTH` |
| `includes/class-kmae-pages.php` | PHP | 9,943 B | Required frontend pages and content injection fallback. Symbols: KMAE_Pages, __construct, register_query_vars, maybe_ensure_pages, offer_thread_body_class, mark_conversation_seen_on_visit, browse_page_url +10 | `PAGES-ROUTING`, `LIVE-NAV` |
| `includes/class-kmae-photo-motor.php` | PHP | 51,703 B | Listing photo trust motor — required/recommended slots + basic quality gates. Symbols: KMAE_Photo_Motor, required_slots, recommended_slots, recommended_total_count, all_slots, required_count, is_enabled, get_slots +32 | `PHOTO-MOTOR`, `MODERATION` |
| `includes/class-kmae-plugin.php` | PHP | 799 B | Main plugin orchestrator. Symbols: KMAE_Plugin, run | `BOOT-ARCHITECTURE` |
| `includes/class-kmae-post-types.php` | PHP | 2,087 B | Custom post type: kmae_listing (hirdetés). Symbols: KMAE_Post_Types, __construct, register, register_post_type, template_loader, is_unavailable_for_viewer (lejárt hirdetés → listing-unavailable.php) | `PAGES-ROUTING`, `TAXONOMY-LAUNCH` |
| `includes/class-kmae-query.php` | PHP | 4,582 B | Listing query helpers with filters. Symbols: KMAE_Query, get_listings, get_popular_listings, get_similar_listings, get_listing_thumbnail_url, get_listing_thumbnail_id, get_listing_full_image_url, get_gallery_ids | `BROWSE-SEARCH` |
| `includes/class-kmae-seller.php` | PHP | 14,246 B | Private-seller listing rules: price mode, commitments, active-cycle limit. Symbols: KMAE_Seller, price_mode_defs, price_mode_labels, commitment_defs, extra_commitment_defs, sanitize_price_mode, get_price_mode, get_fixed_price +14 | `LISTINGS` |
| `includes/class-kmae-settings.php` | PHP | 4,407 B | Plugin settings and defaults. Symbols: KMAE_Settings, init, register_settings, init_defaults, sanitize_youtube_url, youtube_id_from_url, is_youtube_id, auto_moderation_enabled +5 | `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM` |
| `includes/class-kmae-taxonomies.php` | PHP | 2,996 B | Taxonomies: category + accepted offer types on listing. Symbols: KMAE_Taxonomies, __construct, register, register_taxonomy, maybe_seed_categories, seed_categories, offer_type_labels, default_offer_types | `PAGES-ROUTING`, `TAXONOMY-LAUNCH` |
| `includes/class-kmae-vehicle.php` | PHP | 27,594 B | Vehicle-specific listing fields (car launch). Symbols: KMAE_Vehicle, is_vehicle_launch, fuel_labels, transmission_labels, body_labels, body_types, browse_body_cards, browse_mode_labels +30 | `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH` |
| `ki-mit-adna-erte.php` | PHP | 1,895 B | Plugin Name:       Albira Plugin URI:        https://kimitadnaerte.hu Symbols: kmae_run | `BOOT-ARCHITECTURE`, `TAXONOMY-LAUNCH` |
| `public/class-kmae-public.php` | PHP | 56,977 B | Frontend assets and form handlers. Symbols: KMAE_Public, __construct, enqueue, is_create_listing_page, enqueue_vehicle_catalog, enqueue_listing_wizard, should_enqueue_public_assets, is_register_page +39 | `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `CHAT`, `LIVE-NAV`, `CONTACT-FORM` |
| `public/class-kmae-shortcodes.php` | PHP | 19,960 B | Frontend shortcodes. Symbols: KMAE_Shortcodes, __construct, require_login, login, register, hero, landing_features, landing_search +23 | `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS` |
| `templates/archive-listing.php` | PHP | 328 B | Archive template — redirect to browse wizard on front page. | `PAGES-ROUTING` |
| `templates/browse-swipe.php` | PHP | 6,631 B | Tinder-style swipe browse (after wizard). | `BROWSE-SEARCH` |
| `templates/browse-wizard-body.php` | PHP | 2,847 B | Browse wizard step 2 — vehicle body type (multi-select). | `BROWSE-SEARCH` |
| `templates/browse-wizard-category.php` | PHP | 1,966 B | Browse wizard step 3 — category. | `BOOT-ARCHITECTURE` |
| `templates/browse-wizard-map.php` | PHP | 4,855 B | Browse wizard step 1 — Hungary map (country or county). | `BROWSE-SEARCH` |
| `templates/browse-wizard-offer.php` | PHP | 4,346 B | Browse wizard step 3 — price mode: cash range or open offers. | `BROWSE-SEARCH` |
| `templates/browse-wizard-type.php` | PHP | 1,861 B | Browse wizard step 2 — offer type. | `BOOT-ARCHITECTURE` |
| `templates/browse.php` | PHP | 4,779 B | Grid browse with filters. | `BROWSE-SEARCH` |
| `templates/contact.php` | PHP | 5,870 B | Public contact form. | `CONTACT-FORM` |
| `templates/education.php` | PHP | 6,388 B | Education hub — buyer + seller guides. | `BOOT-ARCHITECTURE` |
| `templates/faq.php` | PHP | 5,077 B | Albira FAQ page. | `BOOT-ARCHITECTURE` |
| `templates/form-create-listing.php` | PHP | 211 B | Create listing — uses shared form. | `LISTINGS` |
| `templates/form-listing.php` | PHP | 5,257 B | Listing create/edit form data preparation. | `LISTINGS` |
| `templates/form-login.php` | PHP | 9,816 B | Login / lost password / reset password forms. | `AUTH` |
| `templates/form-profile.php` | PHP | | Own profile: identity form. | `PROFILE-COMPANY-SETTINGS` |
| `templates/form-register.php` | PHP | 12,222 B | Registration form. | `AUTH` |
| `templates/hero.php` | PHP | 933 B | Landing hero — AutoNext-style automotive launch. | `PAGES-ROUTING` |
| `templates/hub-section.php` | PHP | 2,521 B | Hub submenu — Eladni / Venni / Statisztikák card pages. Cards reuse the logged-in home hub markup (.kmae-hub__*). | `DASHBOARD-HUBS` |
| `templates/landing-features.php` | PHP | 1,194 B | Landing feature strip — AutoNext style. | `PAGES-ROUTING` |
| `templates/landing-popular.php` | PHP | 2,651 B | Landing popular listings grid. | `PAGES-ROUTING` |
| `templates/landing-search.php` | PHP | 2,213 B | Landing quick search — brand / model / year. | `PAGES-ROUTING` |
| `templates/messages.php` | PHP | — | Üzenetek inbox list (conversations, last message, unread dot; deleted listing → saved title + „Törölt hirdetés” tag). | `CHAT` |
| `templates/listing-unavailable.php` | PHP | — | Expired listing for visitors (not owner/admin): „Ez az autó már nem elérhető” + up to 4 similar cars (`KMAE_Query::get_similar_listings`), noindex. Loaded by `KMAE_Post_Types::template_loader`. | `LISTINGS` |
| `templates/sale-contract.php` | PHP | — | Editable sale contract page (seller, accepted offer): inputs `kmae_contract[...]`, Word download (`download_sale_contract`) + print. `/uzenetek/?kmae_conversation=ID&kmae_contract=1`. | `CHAT` |
| `templates/contract/adasveteli/` | XML | — | Unpacked DOCX template of the sale contract; `word/document.xml` has `{{key}}` placeholders filled by `KMAE_Sale_Contract::build_docx`. | `CHAT` |
| `assets/images/mire-figyelj.jpg` | image | — | Buyer „Mire figyelj” tips image (chat deal strip, zoom viewer). | `CHAT` |
| `templates/my-listings.php` | PHP | 18,867 B | My listings page. | `LISTINGS` |
| `templates/offer-thread.php` | PHP | — | Chat thread (one column): listing header, accepted-offer strip (Foglalt + phone + Mégse kelt el + seller „Adásvételi szerződés” link / buyer „Mire figyelj” image), timeline, composer. Rendered on `/uzenetek/?kmae_conversation=`. | `CHAT` |
| `templates/partials/create-listing-guest.php` | PHP | 1,407 B | Guest gate — hirdetés feladása (bejelentkezés szükséges). | `LISTINGS` |
| `templates/partials/home-first-listing.php` | PHP | — | Az első sikeresen feladott hirdetés utáni gratuláló felület a kezdőoldalon (csillagértékelés, tippek, „Most nem”). Dashboard-home includeolja, adat: `KMAE_Dashboard::first_listing_tips`. | `DASHBOARD-HUBS` |
| `templates/partials/home-live.php` | PHP | — | Élő főoldal: a felhasználó hirdetései statisztikával (megtekintés, heti grafikon, érdeklődő, kedvenc, ajánlat, lejárat); adat `KMAE_Dashboard::get_home_state` → `live`. |
| `assets/js/home-live.js` | JS | — | Élő főoldal: számláló-felpörgetés, „+N” jelzés, gratuláló doboz eltűnése a „Köszönöm” után. |
| `templates/partials/dashboard-home.php` | PHP | 5,778 B | Logged-in home hub — greeting and the two main cards (Eladni / Venni). | `DASHBOARD-HUBS` |
| `templates/partials/email-verify-gate.php` | PHP | 4,378 B | Letiltó képernyő addig, amíg az e-mail cím nincs megerősítve. Csak a hirdetésfeladást és az eladónak írást zárja le. | `AUTH` |
| `templates/partials/google-button.php` | PHP | 2,591 B | Google-gomb a belépés és a regisztráció oldalon. Ugyanaz a folyamat mindkét helyen — a rendszer magától eldönti, hogy | `AUTH`, `GOOGLE-AUTH` |
| `templates/partials/google-complete.php` | PHP | 7,514 B | Az első Google-belépés utáni kiegészítő képernyő. A nevet és az e-mail címet a Google adta. Ez a három adat viszont nem | `GOOGLE-AUTH` |
| `templates/partials/listing-form-layout.php` | PHP | 26,845 B | Complete listing create/edit page layout. | `LISTINGS`, `LISTING-WIZARD-DRAFTS` |
| `templates/partials/listing-gallery-manager.php` | PHP | 2,952 B | Edit form — gallery manager (reorder, delete, preview). | `PHOTO-MOTOR` |
| `templates/partials/listing-gallery.php` | PHP | 3,631 B | Listing image gallery. | `PHOTO-MOTOR` |
| `templates/partials/listing-photo-motor.php` | PHP | 10,388 B | Photo motor — separate step: car diagram (13 points) + image uploads. | `PHOTO-MOTOR` |
| `templates/partials/listing-pledge-badges.php` | PHP | 495 B | Commitment / price badges for listing cards. | `LISTINGS` |
| `templates/partials/listing-wizard.php` | PHP | 17,294 B | Guided listing wizard shell (create flow). The stage stays empty in PHP on purpose: the engine moves the real form fields | `LISTING-WIZARD-DRAFTS` |
| `templates/partials/report-form.php` | PHP | — | „Bejelentés” gomb + `<dialog class="kmae-report">` form (`submit_report`); vars `$report_context`, `$report_listing_id`, `$report_conversation_id`. | `REPORTS` |
| `templates/partials/offer-forms.php` | PHP | 4,941 B | Listing contact CTA only (`start_conversation`). | `CHAT` |
| `templates/partials/page-back.php` | PHP | 629 B | Inline back button — bottom of page, centered. | `BOOT-ARCHITECTURE` |
| `templates/partials/vehicle-form-fields.php` | PHP | 15,062 B | Vehicle form fields (create/edit). | `VEHICLE` |
| `templates/partials/vehicle-specs-display.php` | PHP | 1,678 B | Vehicle specs display grid. | `VEHICLE` |
| `templates/privacy.php` | PHP | 654 B | Privacy policy stub. | `BOOT-ARCHITECTURE` |
| `templates/security.php` | PHP | ~11 KB | Biztonság és bizalom oldal (tartalom + saját ikonok: `kmae_sec_icon`). Stílus: theme `security.css`. | `SHORTCODES` (`kmae_security`) |
| `templates/settings.php` | PHP | 15,203 B | Beállítások — értesítések, belépési adatok, munkamenetek. A saját osztálynevek (`kmae-settings-*`) szándékosak: a régi, közös | `PROFILE-COMPANY-SETTINGS` |
| `templates/single-listing.php` | PHP | 11,751 B | Single listing template; side panel: price, „Foglalt” / „Újra elérhető” (`.kmae-single-sale-state`), „X ajánlat érkezett”. | `PAGES-ROUTING` |
| `templates/terms.php` | PHP | 682 B | Terms of use stub. | `BOOT-ARCHITECTURE` |
