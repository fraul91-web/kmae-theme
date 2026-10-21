# HOOK / ROUTE / ENTRY-POINT MAP

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

## WordPress hooks and filters

| Kind | Hook | Callback | Registered at |
|---|---|---|---|
| action | `add_meta_boxes` | `KMAE_Meta::add_meta_boxes` | `includes/class-kmae-meta.php:45` |
| action | `admin_enqueue_scripts` | `KMAE_Admin::enqueue` | `admin/class-kmae-admin.php:16` |
| action | `admin_init` | `KMAE_Settings::register_settings` | `includes/class-kmae-settings.php:15` |
| action | `admin_menu` | `KMAE_Admin::add_menu` | `admin/class-kmae-admin.php:15` |
| action | `admin_notices` | `KMAE_Admin::admin_notices` | `admin/class-kmae-admin.php` |
| action | `admin_post_kmae_admin_delete_listing` | `KMAE_Admin::handle_delete_listing` | `admin/class-kmae-admin.php:17` |
| action | `admin_post_kmae_admin_set_status` | `KMAE_Admin::handle_set_status` | `admin/class-kmae-admin.php:19` |
| action | `admin_post_kmae_report_update` | `KMAE_Admin::handle_report_update` | `admin/class-kmae-admin.php` |
| action | `admin_post_kmae_browse_index_rebuild` | `KMAE_Browse_Index::handle_admin_rebuild` | `includes/class-kmae-browse-index.php` |
| action | `kmae_browse_index_rebuild` (cron) / `kmae_listing_reserved_changed` / post+meta hookok | `KMAE_Browse_Index` (lásd `FEATURES/BROWSE-V2.md`) | `includes/class-kmae-browse-index.php` |
| action | `rest_api_init` (`kmae/v1/feed`, `kmae/v1/me/browse`) | `KMAE_Browse_Feed::register_routes` | `includes/class-kmae-browse-feed.php` |
| action | `admin_post_kmae_change_email` | `KMAE_Public::admin_change_email` | `public/class-kmae-public.php:38` |
| action | `admin_post_kmae_change_password` | `KMAE_Public::admin_change_password` | `public/class-kmae-public.php:39` |
| action | `admin_post_kmae_contact_form` | `KMAE_Public::admin_contact_form` | `public/class-kmae-public.php:33` |
| action | `admin_post_kmae_continue_shopping` | `KMAE_Public::admin_continue_shopping` | `public/class-kmae-public.php:36` |
| action | `admin_post_kmae_delete_listing` | `KMAE_Public::admin_delete_listing` | `public/class-kmae-public.php:22` |
| action | `admin_post_kmae_google_complete` | `KMAE_Google_Auth::handle_complete` | `includes/class-kmae-google-auth.php:38` |
| action | `admin_post_kmae_login` | `KMAE_Public::admin_login` | `public/class-kmae-public.php:25` |
| action | `admin_post_kmae_logout_everywhere` | `KMAE_Public::admin_logout_everywhere` | `public/class-kmae-public.php:40` |
| action | `admin_post_kmae_lost_password` | `KMAE_Public::admin_lost_password` | `public/class-kmae-public.php:29` |
| action | `admin_post_kmae_reactivate_listing` | `KMAE_Public::admin_reactivate_listing` | `public/class-kmae-public.php:23` |
| action | `admin_post_kmae_renew_listing` | `KMAE_Public::admin_renew_listing` | `public/class-kmae-public.php` |
| action | `admin_post_kmae_register` | `KMAE_Public::admin_register` | `public/class-kmae-public.php:27` |
| action | `admin_post_kmae_resend_verification` | `KMAE_Auth::handle_resend_verification` | `includes/class-kmae-auth.php:62` |
| action | `admin_post_kmae_reset_password` | `KMAE_Public::admin_reset_password` | `public/class-kmae-public.php:31` |
| action | `admin_post_kmae_save_profile` | `KMAE_Public::admin_save_profile` | `public/class-kmae-public.php:20` |
| action | `admin_post_kmae_save_settings` | `KMAE_Public::admin_save_settings` | `public/class-kmae-public.php:37` |
| action | `admin_post_kmae_save_mail_prefs` | `KMAE_Public::admin_save_mail_prefs` | `public/class-kmae-public.php` |
| action | `kmae_listing_published` | `KMAE_Notifications::on_listing_published` | `includes/class-kmae-notifications.php` |
| action | `kmae_remind_unfinished_listings` | `KMAE_Notifications::remind_unfinished_listings` | `includes/class-kmae-notifications.php` |
| action | `kmae_finalize_chat_sales` | `KMAE_Chat_Offers::finalize_sales` | `includes/class-kmae-chat-offers.php` |
| action | `kmae_expire_listings` | `KMAE_Listings::expire_listings` | `includes/class-kmae-listings.php` |
| action | `admin_post_kmae_update_listing` | `KMAE_Public::admin_update_listing` | `public/class-kmae-public.php:21` |
| action | `admin_post_nopriv_kmae_contact_form` | `KMAE_Public::admin_contact_form` | `public/class-kmae-public.php:34` |
| action | `admin_post_nopriv_kmae_login` | `KMAE_Public::admin_login` | `public/class-kmae-public.php:26` |
| action | `admin_post_nopriv_kmae_lost_password` | `KMAE_Public::admin_lost_password` | `public/class-kmae-public.php:30` |
| action | `admin_post_nopriv_kmae_register` | `KMAE_Public::admin_register` | `public/class-kmae-public.php:28` |
| action | `admin_post_nopriv_kmae_reset_password` | `KMAE_Public::admin_reset_password` | `public/class-kmae-public.php:32` |
| filter | `big_image_size_threshold` | `__return_false` | `includes/class-kmae-images.php:21` |
| filter | `style_loader_src` / `script_loader_src` | `kmae_plugin_asset_version_src` (plugin asset `ver` = filemtime; cache-busting) | `ki-mit-adna-erte.php` |
| filter | `body_class` | `KMAE_Pages::offer_thread_body_class` | `includes/class-kmae-pages.php:110` |
| filter | `bulk_actions-edit-kmae_listing` | `KMAE_Admin::listing_bulk_actions` | `admin/class-kmae-admin.php:23` |
| filter | `handle_bulk_actions-edit-kmae_listing` | `KMAE_Admin::handle_listing_bulk_actions` | `admin/class-kmae-admin.php:24` |
| action | `init` | `KMAE_Auth::maybe_handle_email_verify_link` | `includes/class-kmae-auth.php:61` |
| action | `init` | `KMAE_Google_Auth::maybe_handle_request` | `includes/class-kmae-google-auth.php:37` |
| action | `init` | `KMAE_Pages::maybe_ensure_pages` | `includes/class-kmae-pages.php:111` |
| action | `init` | `KMAE_Post_Types::register` | `includes/class-kmae-post-types.php:15` |
| action | `init` | `KMAE_Taxonomies::register` | `includes/class-kmae-taxonomies.php:15` |
| action | `init` | `KMAE_Taxonomies::maybe_seed_categories` | `includes/class-kmae-taxonomies.php:16` |
| action | `init` | `KMAE_Public::handle_forms` | `public/class-kmae-public.php:16` |
| action | `kmae_purge_archived_listings` | `KMAE_Listings::purge_expired_archived` | `includes/class-kmae-listings.php:16` |
| action | `kmae_conversation_message_created` | `KMAE_Notifications::on_conversation_message_created` | `includes/class-kmae-notifications.php` |
| action | `login_init` | `KMAE_Auth::redirect_wp_password_screens` | `includes/class-kmae-auth.php:60` |
| filter | `lostpassword_url` | `KMAE_Auth::filter_lostpassword_url` | `includes/class-kmae-auth.php:58` |
| filter | `manage_kmae_listing_posts_columns` | `KMAE_Admin::listing_columns` | `admin/class-kmae-admin.php:20` |
| action | `manage_kmae_listing_posts_custom_column` | `KMAE_Admin::listing_column_content` | `admin/class-kmae-admin.php:21` |
| filter | `manage_users_columns` | `KMAE_Google_Auth::admin_user_column` | `includes/class-kmae-google-auth.php:40` |
| filter | `manage_users_custom_column` | `KMAE_Google_Auth::admin_user_column_value` | `includes/class-kmae-google-auth.php:41` |
| filter | `map_meta_cap` | `KMAE_Auth::map_listing_caps` | `includes/class-kmae-auth.php:57` |
| filter | `post_row_actions` | `KMAE_Admin::listing_row_actions` | `admin/class-kmae-admin.php:22` |
| filter | `query_vars` | `KMAE_Pages::register_query_vars` | `includes/class-kmae-pages.php:107` |
| filter | `render_block` | `KMAE_Nav::filter_navigation_block` | `includes/class-kmae-nav.php:18` |
| filter | `retrieve_password_message` | `KMAE_Auth::filter_reset_message` | `includes/class-kmae-auth.php:59` |
| action | `save_post_kmae_listing` | `KMAE_Meta::save_listing_meta` | `includes/class-kmae-meta.php:46` |
| filter | `template_include` | `KMAE_Post_Types::template_loader` | `includes/class-kmae-post-types.php:16` |
| action | `template_redirect` | `KMAE_Dashboard::maybe_track_listing_view` | `includes/class-kmae-dashboard.php:70` |
| action | `template_redirect` | `KMAE_Google_Auth::enforce_completion` | `includes/class-kmae-google-auth.php:39` |
| action | `template_redirect` | `KMAE_Pages::mark_conversation_seen_on_visit` | `includes/class-kmae-pages.php:108` |
| action | `template_redirect` | `KMAE_Public::block_nonpublic_listings` | `public/class-kmae-public.php:24` |
| filter | `the_content` | `KMAE_Pages::inject_page_content` | `includes/class-kmae-pages.php:106` |
| action | `wp_ajax_kmae_browse_pass` | `KMAE_Public::ajax_browse_pass` | `public/class-kmae-public.php:17` |
| action | `wp_ajax_kmae_poll_page` | `KMAE_Public::ajax_poll_page` | `public/class-kmae-public.php:18` |
| action | `wp_ajax_kmae_save_listing_draft` | `KMAE_Listing_Drafts::ajax_save` | `includes/class-kmae-listing-drafts.php:24` |
| action | `wp_ajax_kmae_push_subscribe` / `wp_ajax_kmae_push_unsubscribe` / `wp_ajax_kmae_push_prompted` | `KMAE_Push::ajax_*` | `includes/class-kmae-push.php` (`KMAE_Push::init`) |
| action | `wp_ajax_kmae_locate` | `KMAE_Counties::ajax_locate` | `includes/class-kmae-counties.php` (`KMAE_Counties::init`) |
| action | `wp_enqueue_scripts` | `KMAE_Nav::enqueue_assets` | `includes/class-kmae-nav.php:19` |
| action | `wp_enqueue_scripts` | `KMAE_Public::enqueue` | `public/class-kmae-public.php:15` |
| action | `wp_footer` | `KMAE_Public::render_rounds_modal` | `public/class-kmae-public.php:19` |
| filter | `wp_get_nav_menu_items` | `KMAE_Nav::filter_menu_items` | `includes/class-kmae-nav.php:17` |
| action | `wp_head` | `KMAE_Nav::inline_nav_css` | `includes/class-kmae-nav.php:20` |
| filter | `wp_mail` | `KMAE_Brand::ensure_html_headers` | `includes/class-kmae-brand.php:30` |
| action | `phpmailer_init` | `KMAE_Brand::embed_email_images` | `includes/class-kmae-brand.php:31` |
| filter | `wp_nav_menu_objects` | `KMAE_Nav::filter_menu_items` | `includes/class-kmae-nav.php:16` |

## Custom domain action emitters / listeners

### `kmae_conversation_message_created`
- EMITTED BY: `KMAE_Conversations::save_message`
- LISTENERS: `KMAE_Notifications::on_conversation_message_created`
### `kmae_listing_published`
- EMITTED BY: `KMAE_Listings::reactivate_listing`, `KMAE_Listings::activate_if_photos_ready`, `KMAE_Public::process_create_listing`
- LISTENERS: `KMAE_Notifications::on_listing_published` (active listing mail, catalog key `listing_active`)
### `kmae_remind_unfinished_listings`
- EMITTED BY: WP-Cron hourly (`KMAE_Activator::schedule_cron`)
- LISTENERS: `KMAE_Notifications::remind_unfinished_listings` (draft or awaiting photos, 24h since `post_modified_gmt`, catalog key `listing_unfinished`)
### `kmae_finalize_chat_sales`
- EMITTED BY: WP-Cron hourly (`KMAE_Activator::schedule_cron`; cleared in `KMAE_Deactivator`)
- LISTENERS: `KMAE_Chat_Offers::finalize_sales` (7 napos `accepted` → `sold`, hirdetés `closed`, chatek `closed`)
### `kmae_expire_listings`
- EMITTED BY: WP-Cron hourly (`KMAE_Activator::schedule_cron`; cleared in `KMAE_Deactivator`)
- LISTENERS: `KMAE_Listings::expire_listings` (60 nap `active` → `expired`, Foglalt kivétel, levél `notify_listing_expired`)
### `kmae_purge_archived_listings`
- EMITTED BY: —
- LISTENERS: `KMAE_Listings::purge_expired_archived`
## AJAX

All source-registered AJAX actions listed above are authenticated `wp_ajax_*`; no `wp_ajax_nopriv_*` counterpart is registered for them.

- `kmae_save_listing_draft` → `KMAE_Listing_Drafts::ajax_save` (`./includes/class-kmae-listing-drafts.php`)
- `kmae_locate` → `KMAE_Counties::ajax_locate` (`./includes/class-kmae-counties.php`)
- `kmae_browse_pass` → `KMAE_Public::ajax_browse_pass` (`./public/class-kmae-public.php`)
- `kmae_poll_page` → `KMAE_Public::ajax_poll_page` (`./public/class-kmae-public.php`)

## Same-page POST dispatcher (`kmae_action`)

- `create_listing` → `KMAE_Public::process_create_listing`
- `send_conversation_message` → `KMAE_Public::process_conversation_message`
- `send_chat_offer` → `KMAE_Public::process_chat_offer` → `KMAE_Chat_Offers::submit`
- `accept_chat_offer` → `KMAE_Public::process_chat_offer_decision` → `KMAE_Chat_Offers::accept` (nonce `kmae_chat_offer_decision_nonce`)
- `cancel_chat_sale` → `KMAE_Public::process_chat_offer_decision` → `KMAE_Chat_Offers::cancel_sale` (nonce `kmae_chat_offer_decision_nonce`)
- `retract_chat_offer` → `KMAE_Public::process_chat_offer_decision` → `KMAE_Chat_Offers::retract` (vevő [Visszavonom], nonce `kmae_chat_offer_decision_nonce`)
- `download_sale_contract` → `KMAE_Sale_Contract::process_download` (nonce `kmae_sale_contract_nonce`; DOCX stream, nothing stored)
- `submit_report` → `KMAE_Public::process_report` → `KMAE_Reports::submit` (nonce `kmae_report_nonce`; admin Web Push via `KMAE_Live_Signal::bump`)
- `start_conversation` → `KMAE_Public::process_start_conversation`
- `conversation_thinking` → `KMAE_Public::process_conversation_thinking`

## admin-post routes

- `admin_post_kmae_admin_delete_listing` → `KMAE_Admin::handle_delete_listing`
- `admin_post_kmae_admin_set_status` → `KMAE_Admin::handle_set_status`
- `admin_post_kmae_report_update` → `KMAE_Admin::handle_report_update` → `KMAE_Reports::update_decision` (nonce `kmae_report_update_{id}`)
- `admin_post_kmae_change_email` → `KMAE_Public::admin_change_email`
- `admin_post_kmae_change_password` → `KMAE_Public::admin_change_password`
- `admin_post_kmae_contact_form` → `KMAE_Public::admin_contact_form`
- `admin_post_kmae_continue_shopping` → `KMAE_Public::admin_continue_shopping`
- `admin_post_kmae_delete_listing` → `KMAE_Public::admin_delete_listing`
- `admin_post_kmae_google_complete` → `KMAE_Google_Auth::handle_complete`
- `admin_post_kmae_login` → `KMAE_Public::admin_login`
- `admin_post_kmae_logout_everywhere` → `KMAE_Public::admin_logout_everywhere`
- `admin_post_kmae_lost_password` → `KMAE_Public::admin_lost_password`
- `admin_post_kmae_reactivate_listing` → `KMAE_Public::admin_reactivate_listing`
- `admin_post_kmae_renew_listing` → `KMAE_Public::admin_renew_listing`
- `admin_post_kmae_register` → `KMAE_Public::admin_register`
- `admin_post_kmae_resend_verification` → `KMAE_Auth::handle_resend_verification`
- `admin_post_kmae_reset_password` → `KMAE_Public::admin_reset_password`
- `admin_post_kmae_save_profile` → `KMAE_Public::admin_save_profile`
- `admin_post_kmae_save_settings` → `KMAE_Public::admin_save_settings`
- `admin_post_kmae_save_mail_prefs` → `KMAE_Public::admin_save_mail_prefs`
- `admin_post_kmae_update_listing` → `KMAE_Public::admin_update_listing`
- `admin_post_nopriv_kmae_contact_form` → `KMAE_Public::admin_contact_form` (guest-capable route)
- `admin_post_nopriv_kmae_login` → `KMAE_Public::admin_login` (guest-capable route)
- `admin_post_nopriv_kmae_lost_password` → `KMAE_Public::admin_lost_password` (guest-capable route)
- `admin_post_nopriv_kmae_register` → `KMAE_Public::admin_register` (guest-capable route)
- `admin_post_nopriv_kmae_reset_password` → `KMAE_Public::admin_reset_password` (guest-capable route)

## Shortcodes

- `[kmae_hero]` → `KMAE_Shortcodes::hero` (`public/class-kmae-shortcodes.php:15`)
- `[kmae_landing_search]` → `KMAE_Shortcodes::landing_search` (`public/class-kmae-shortcodes.php:16`)
- `[kmae_landing_popular]` → `KMAE_Shortcodes::landing_popular` (`public/class-kmae-shortcodes.php:17`)
- `[kmae_landing_features]` → `KMAE_Shortcodes::landing_features` (`public/class-kmae-shortcodes.php:18`)
- `[kmae_landing]` → `KMAE_Shortcodes::landing` (`public/class-kmae-shortcodes.php:19`)
- `[kmae_login]` → `KMAE_Shortcodes::login` (`public/class-kmae-shortcodes.php:20`)
- `[kmae_register]` → `KMAE_Shortcodes::register` (`public/class-kmae-shortcodes.php:21`)
- `[kmae_create_listing]` → `KMAE_Shortcodes::create_listing` (`public/class-kmae-shortcodes.php:22`)
- `[kmae_my_listings]` → `KMAE_Shortcodes::my_listings` (`public/class-kmae-shortcodes.php:23`)
- `[kmae_browse]` → `KMAE_Shortcodes::browse` (`public/class-kmae-shortcodes.php:24`)
- `[kmae_profile]` → `KMAE_Shortcodes::profile` (`public/class-kmae-shortcodes.php:26`)
- `[kmae_education]` → `KMAE_Shortcodes::education` (`public/class-kmae-shortcodes.php:28`)
- `[kmae_settings]` → `KMAE_Shortcodes::settings` (`public/class-kmae-shortcodes.php:29`)
- `[kmae_security]` → `KMAE_Shortcodes::security` (`public/class-kmae-shortcodes.php:30`)
- `[kmae_faq]` → `KMAE_Shortcodes::faq` (`public/class-kmae-shortcodes.php:31`)
- `[kmae_contact]` → `KMAE_Shortcodes::contact` (`public/class-kmae-shortcodes.php:32`)
- `[kmae_terms]` → `KMAE_Shortcodes::terms` (`public/class-kmae-shortcodes.php:33`)
- `[kmae_privacy]` → `KMAE_Shortcodes::privacy` (`public/class-kmae-shortcodes.php:34`)
- `[kmae_notifications]` → `KMAE_Shortcodes::notifications` (`public/class-kmae-shortcodes.php:35`)
- `[kmae_messages]` → `KMAE_Shortcodes::messages` (`public/class-kmae-shortcodes.php:36`)
- `[kmae_hub_sell]` → `KMAE_Shortcodes::hub_sell` (`public/class-kmae-shortcodes.php:37`)
- `[kmae_hub_buy]` → `KMAE_Shortcodes::hub_buy` (`public/class-kmae-shortcodes.php:38`)
- `[kmae_hub_stats]` → `KMAE_Shortcodes::hub_stats` (`public/class-kmae-shortcodes.php:39`)

## REST

- Custom `register_rest_route`: **none found in plugin source**.
- `kmae_listing` CPT: `show_in_rest => true` (native WP REST exposure).
- `kmae_category` taxonomy: `show_in_rest => true` (native WP REST exposure).
