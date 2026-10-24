# SYMBOL MAP

> 2026-10-10: the offer / official offer / counter / deal / contact-unlock / trust-rating / history system was **deleted**. Only the chat remains (see `FEATURES/CHAT.md`). Sections for removed symbols were pruned.

This is the broad symbol index. For feature work, open the matching `FEATURES/*.md` first and only use this file to locate exact symbols.

Format per symbol: FILE / SYMBOL / PURPOSE / CALLED BY / CALLS / READS / WRITES / RELATED FEATURE.

## `KMAE_Admin::__construct`
- FILE: `admin/class-kmae-admin.php:14-26`
- SYMBOL: `KMAE_Admin::__construct`
- PURPOSE: WordPress admin integration.
- CALLED BY: —
- CALLS: `KMAE_Settings::init`
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::add_menu`
- FILE: `admin/class-kmae-admin.php:29-65`
- SYMBOL: `KMAE_Admin::add_menu`
- PURPOSE: WordPress admin integration. Albira menü + Áttekintés / Jelentések (`kmae-reports`, piros `awaiting-mod` buborék) / Beállítások.
- CALLED BY: `WP action `admin_menu``
- CALLS: `KMAE_Reports::count_new`
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::admin_notices`
- FILE: `admin/class-kmae-admin.php:68-83`
- SYMBOL: `KMAE_Admin::admin_notices`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_notices``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`
- GET KEYS: `kmae_notice`

## `KMAE_Admin::render_settings`
- FILE: `admin/class-kmae-admin.php:86-87`
- SYMBOL: `KMAE_Admin::render_settings`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::render_dashboard`
- FILE: `admin/class-kmae-admin.php:108-113`
- SYMBOL: `KMAE_Admin::render_dashboard`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: `wpdb:get_var`
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::handle_delete_listing`
- FILE: `admin/class-kmae-admin.php:133-147`
- SYMBOL: `KMAE_Admin::handle_delete_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_admin_delete_listing``
- CALLS: `KMAE_Listings::force_delete_listing`
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`
- GET KEYS: `listing_id`, `redirect`

## `KMAE_Admin::handle_set_status`
- FILE: `admin/class-kmae-admin.php:165-193`
- SYMBOL: `KMAE_Admin::handle_set_status`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_admin_set_status``
- CALLS: —
- READS: —
- WRITES: `postmeta:_kmae_status`, `postmeta:_kmae_published_at`
- RELATED FEATURE: `ADMIN`
- GET KEYS: `listing_id`, `redirect`, `status`

## `KMAE_Admin::listing_columns`
- FILE: `admin/class-kmae-admin.php:196-213`
- SYMBOL: `KMAE_Admin::listing_columns`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `manage_kmae_listing_posts_columns``
- CALLS: `KMAE_Vehicle::is_vehicle_launch`
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::listing_column_content`
- FILE: `admin/class-kmae-admin.php:216-246`
- SYMBOL: `KMAE_Admin::listing_column_content`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `manage_kmae_listing_posts_custom_column``
- CALLS: `KMAE_Vehicle::get_swipe_subtitle`, `KMAE_Meta::get_status`, `KMAE_Meta::get_offer_types`, `KMAE_Taxonomies::offer_type_labels`, `KMAE_Admin::status_badge`
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::listing_row_actions`
- FILE: `admin/class-kmae-admin.php:249-318`
- SYMBOL: `KMAE_Admin::listing_row_actions`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `post_row_actions``
- CALLS: `KMAE_Meta::get_status`
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::listing_bulk_actions`
- FILE: `admin/class-kmae-admin.php:321-325`
- SYMBOL: `KMAE_Admin::listing_bulk_actions`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `bulk_actions-edit-kmae_listing``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::handle_listing_bulk_actions`
- FILE: `admin/class-kmae-admin.php:328-352`
- SYMBOL: `KMAE_Admin::handle_listing_bulk_actions`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `handle_bulk_actions-edit-kmae_listing``
- CALLS: `KMAE_Listings::force_delete_listing`
- READS: —
- WRITES: `postmeta:_kmae_status`
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::status_badge`
- FILE: `admin/class-kmae-admin.php:355-363`
- SYMBOL: `KMAE_Admin::status_badge`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Admin::listing_column_content`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::admin_action_url`
- FILE: `admin/class-kmae-admin.php:366-373`
- SYMBOL: `KMAE_Admin::admin_action_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Admin::enqueue`
- FILE: `admin/class-kmae-admin.php:376-379`
- SYMBOL: `KMAE_Admin::enqueue`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_enqueue_scripts``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `ADMIN`

## `KMAE_Activator::activate`
- FILE: `includes/class-kmae-activator.php:14-26`
- SYMBOL: `KMAE_Activator::activate`
- PURPOSE: Plugin activation: database tables, default categories, rewrite rules.
- CALLED BY: —
- CALLS: `KMAE_Post_Types::register_post_type`, `KMAE_Taxonomies::register_taxonomy`, `KMAE_Taxonomies::seed_categories`, `KMAE_Pages::ensure_pages`, `KMAE_Auth::ensure_subscriber_caps`, `KMAE_Activator::create_tables`, `KMAE_Activator::migrate_price_modes`, `KMAE_Activator::schedule_cron`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CRON-ARCHIVE`

## `KMAE_Activator::schedule_cron`
- FILE: `includes/class-kmae-activator.php:29-32`
- SYMBOL: `KMAE_Activator::schedule_cron`
- PURPOSE: Plugin activation: database tables, default categories, rewrite rules.
- CALLED BY: `KMAE_Listings::init`, `KMAE_Activator::activate`, `KMAE_Activator::maybe_upgrade`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CRON-ARCHIVE`

## `KMAE_Activator::create_tables`
- FILE: `includes/class-kmae-activator.php:35-42`
- SYMBOL: `KMAE_Activator::create_tables`
- PURPOSE: Plugin activation: database tables, default categories, rewrite rules.
- CALLED BY: `KMAE_Activator::activate`, `KMAE_Activator::maybe_upgrade`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CRON-ARCHIVE`

## `KMAE_Activator::migrate_price_modes`
- FILE: `includes/class-kmae-activator.php:168-185`
- SYMBOL: `KMAE_Activator::migrate_price_modes`
- PURPOSE: Pre-3.18 listings stored a single "offers" mode. It now means "Ki mit adna érte" (open).
- CALLED BY: `KMAE_Activator::activate`, `KMAE_Activator::maybe_upgrade`
- CALLS: —
- READS: `option:kmae_price_modes_migrated`
- WRITES: `option:kmae_price_modes_migrated`, `wpdb:update`
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CRON-ARCHIVE`

## `KMAE_Activator::maybe_upgrade`
- FILE: `includes/class-kmae-activator.php:188-195`
- SYMBOL: `KMAE_Activator::maybe_upgrade`
- PURPOSE: Pre-3.18 listings stored a single "offers" mode. It now means "Ki mit adna érte" (open).
- CALLED BY: `KMAE_Plugin::run`
- CALLS: `KMAE_Pages::ensure_pages`, `KMAE_Auth::ensure_subscriber_caps`, `KMAE_Activator::create_tables`, `KMAE_Activator::migrate_price_modes`, `KMAE_Activator::schedule_cron`
- READS: `option:kmae_db_version`
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CRON-ARCHIVE`

## `KMAE_Auth::login_url`
- FILE: `includes/class-kmae-auth.php:17-22`
- SYMBOL: `KMAE_Auth::login_url`
- PURPOSE: User registration, login, and profile storage.
- CALLED BY: `KMAE_Auth::lost_password_url`, `KMAE_Auth::reset_password_url`, `KMAE_Auth::maybe_handle_email_verify_link`, `KMAE_Auth::handle_resend_verification`, `KMAE_Public::admin_login`, `KMAE_Public::admin_register`, `KMAE_Public::admin_lost_password`, `KMAE_Public::admin_reset_password`, `KMAE_Shortcodes::require_login`
- CALLS: `KMAE_Pages::url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::register_url`
- FILE: `includes/class-kmae-auth.php:25-26`
- SYMBOL: `KMAE_Auth::register_url`
- PURPOSE: User registration, login, and profile storage.
- CALLED BY: `KMAE_Public::admin_register`, `KMAE_Shortcodes::require_login`
- CALLS: `KMAE_Pages::url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::lost_password_url`
- FILE: `includes/class-kmae-auth.php:34-35`
- SYMBOL: `KMAE_Auth::lost_password_url`
- PURPOSE: Branded lost-password URL.
- CALLED BY: `KMAE_Auth::filter_lostpassword_url`, `KMAE_Auth::redirect_wp_password_screens`, `KMAE_Public::admin_lost_password`, `KMAE_Public::admin_reset_password`
- CALLS: `KMAE_Auth::login_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::reset_password_url`
- FILE: `includes/class-kmae-auth.php:45-53`
- SYMBOL: `KMAE_Auth::reset_password_url`
- PURPOSE: Branded password-reset URL (from email link).
- CALLED BY: `KMAE_Auth::filter_reset_message`, `KMAE_Auth::redirect_wp_password_screens`, `KMAE_Auth::request_password_reset`, `KMAE_Public::admin_reset_password`
- CALLS: `KMAE_Auth::login_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::init`
- FILE: `includes/class-kmae-auth.php:56-62`
- SYMBOL: `KMAE_Auth::init`
- PURPOSE: Branded password-reset URL (from email link).
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::filter_lostpassword_url`
- FILE: `includes/class-kmae-auth.php:71-72`
- SYMBOL: `KMAE_Auth::filter_lostpassword_url`
- PURPOSE: Point WP lost-password links to the branded login page.
- CALLED BY: `WP filter `lostpassword_url``
- CALLS: `KMAE_Auth::lost_password_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::filter_reset_message`
- FILE: `includes/class-kmae-auth.php:84-86`
- SYMBOL: `KMAE_Auth::filter_reset_message`
- PURPOSE: Replace default wp-login reset link in the email.
- CALLED BY: `WP filter `retrieve_password_message``
- CALLS: `KMAE_Auth::password_reset_email_html`, `KMAE_Auth::reset_password_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::password_reset_email_html`
- FILE: `includes/class-kmae-auth.php:98-123`
- SYMBOL: `KMAE_Auth::password_reset_email_html`
- PURPOSE: A jelszó-visszaállító levél egyetlen forrása — ugyanez fut le akkor is, ha a WordPress saját folyamata küldi (fenti szűrő), és akkor is, ha a mi „Elfelejtett jelszó" űrlapunk (lásd lent).
- CALLED BY: `KMAE_Auth::filter_reset_message`, `KMAE_Auth::request_password_reset`
- CALLS: `KMAE_Brand::wrap_email`
- READS: `option:blogname`
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::redirect_wp_password_screens`
- FILE: `includes/class-kmae-auth.php:129-150`
- SYMBOL: `KMAE_Auth::redirect_wp_password_screens`
- PURPOSE: Keep users on branded screens instead of wp-login.php password flows.
- CALLED BY: `WP action `login_init``
- CALLS: `KMAE_Auth::lost_password_url`, `KMAE_Auth::reset_password_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`
- GET KEYS: `key`, `login`

## `KMAE_Auth::request_password_reset`
- FILE: `includes/class-kmae-auth.php:159-185`
- SYMBOL: `KMAE_Auth::request_password_reset`
- PURPOSE: Send password reset email for an address.
- CALLED BY: `KMAE_Public::admin_lost_password`
- CALLS: `KMAE_Auth::password_reset_email_html`, `KMAE_Auth::reset_password_url`
- READS: `option:blogname`
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::reset_password_with_key`
- FILE: `includes/class-kmae-auth.php:197-219`
- SYMBOL: `KMAE_Auth::reset_password_with_key`
- PURPOSE: Set a new password using WP reset key.
- CALLED BY: `KMAE_Public::admin_reset_password`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::account_types`
- FILE: `includes/class-kmae-auth.php:227-231`
- SYMBOL: `KMAE_Auth::account_types`
- PURPOSE: Allowed account types (magánszemély / cég).
- CALLED BY: `KMAE_Google_Auth::handle_complete`, `KMAE_Auth::sanitize_account_type`, `KMAE_Auth::account_type_label`, `KMAE_Auth::register`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::sanitize_account_type`
- FILE: `includes/class-kmae-auth.php:238-241`
- SYMBOL: `KMAE_Auth::sanitize_account_type`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Auth::get_account_type`, `KMAE_Auth::mark_email_verified`, `KMAE_Auth::get_profile`, `KMAE_Auth::save_email_prefs`, `KMAE_Auth::sync_phone`
- CALLS: `KMAE_Auth::account_types`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::get_account_type`
- FILE: `includes/class-kmae-auth.php:248-254`
- SYMBOL: `KMAE_Auth::get_account_type`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Auth::is_company`, `KMAE_Auth::is_private_person`, `KMAE_Auth::account_type_label`
- CALLS: `KMAE_Auth::get_profile`, `KMAE_Auth::sanitize_account_type`
- READS: `usermeta:kmae_account_type`
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::is_company`
- FILE: `includes/class-kmae-auth.php:257-258`
- SYMBOL: `KMAE_Auth::is_company`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::save_meta_from_post`, `KMAE_Company::seller_display_name`, `KMAE_Company::save_profile_fields`, `KMAE_Public::admin_save_profile`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Auth::get_account_type`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::is_private_person`
- FILE: `includes/class-kmae-auth.php:261-262`
- SYMBOL: `KMAE_Auth::is_private_person`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Auth::get_account_type`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::account_type_label`
- FILE: `includes/class-kmae-auth.php:271-274`
- SYMBOL: `KMAE_Auth::account_type_label`
- PURPOSE: Human-readable account type label.
- CALLED BY: —
- CALLS: `KMAE_Auth::get_account_type`, `KMAE_Auth::account_types`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::ensure_subscriber_caps`
- FILE: `includes/class-kmae-auth.php:280-284`
- SYMBOL: `KMAE_Auth::ensure_subscriber_caps`
- PURPOSE: Subscribers need upload_files for listing photos.
- CALLED BY: `KMAE_Google_Auth::resolve_user`, `KMAE_Activator::activate`, `KMAE_Activator::maybe_upgrade`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::map_listing_caps`
- FILE: `includes/class-kmae-auth.php:293-308`
- SYMBOL: `KMAE_Auth::map_listing_caps`
- PURPOSE: Authors may edit/delete their own listings without edit_posts cap.
- CALLED BY: `WP filter `map_meta_cap``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::register`
- FILE: `includes/class-kmae-auth.php:311-422`
- SYMBOL: `KMAE_Auth::register`
- PURPOSE: Authors may edit/delete their own listings without edit_posts cap.
- CALLED BY: `KMAE_Public::admin_register`
- CALLS: `KMAE_Counties::validate_listing_location`, `KMAE_Company::save_profile_fields`, `KMAE_Database::table`, `KMAE_Auth::normalize_phone`, `KMAE_Auth::account_types`, `KMAE_Auth::generate_username`, `KMAE_Auth::generate_email_verify_token`, `KMAE_Auth::send_verification_email`
- READS: `table:user_profiles`
- WRITES: `usermeta:kmae_phone`, `usermeta:kmae_account_type`, `wpdb:replace`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::mark_email_verified`
- FILE: `includes/class-kmae-auth.php:436-452`
- SYMBOL: `KMAE_Auth::mark_email_verified`
- PURPOSE: A profil sorát „megerősítve" állapotba teszi, a többi mezőt érintetlenül hagyva. Ugyanezt hívja a Google-belépés is, hiszen ott a Google már igazolta a címet.
- CALLED BY: `KMAE_Google_Auth::handle_callback`, `KMAE_Google_Auth::handle_complete`, `KMAE_Auth::verify_email_link`
- CALLS: `KMAE_Database::table`, `KMAE_Auth::get_profile`, `KMAE_Auth::sanitize_account_type`
- READS: `table:user_profiles`
- WRITES: `wpdb:replace`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::generate_email_verify_token`
- FILE: `includes/class-kmae-auth.php:462-466`
- SYMBOL: `KMAE_Auth::generate_email_verify_token`
- PURPOSE: Új megerősítő kulcs. A nyers érték megy a linkbe, az adatbázisba csak a lenyomata kerül — ahogy a jelszó-visszaállításnál is.
- CALLED BY: `KMAE_Auth::register`, `KMAE_Auth::handle_resend_verification`
- CALLS: —
- READS: —
- WRITES: `usermeta:kmae_email_verify_token`, `usermeta:kmae_email_verify_sent`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::send_verification_email`
- FILE: `includes/class-kmae-auth.php:474-512`
- SYMBOL: `KMAE_Auth::send_verification_email`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Auth::register`, `KMAE_Auth::handle_resend_verification`
- CALLS: `KMAE_Brand::wrap_email`, `KMAE_Brand::email_mascot_url`, `KMAE_Auth::first_name`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::verify_email_link`
- FILE: `includes/class-kmae-auth.php:520-535`
- SYMBOL: `KMAE_Auth::verify_email_link`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Auth::maybe_handle_email_verify_link`
- CALLS: `KMAE_Auth::mark_email_verified`
- READS: `usermeta:kmae_email_verify_token`, `usermeta:kmae_email_verify_sent`
- WRITES: `usermeta:kmae_email_verify_token`, `usermeta:kmae_email_verify_sent`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::maybe_handle_email_verify_link`
- FILE: `includes/class-kmae-auth.php:541-559`
- SYMBOL: `KMAE_Auth::maybe_handle_email_verify_link`
- PURPOSE: A levélben kapott linkre kattintva ide fut be a látogató, bárhol jár.
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Pages::url`, `KMAE_Auth::verify_email_link`, `KMAE_Auth::login_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`
- GET KEYS: `kmae_email_token` (2026-10-14 óta csak ez; a user a token lenyomatából jön: `KMAE_Auth::user_by_email_token`, a régi `kmae_email_verify` ID-t figyelmen kívül hagyja)

## `KMAE_Auth::handle_resend_verification`
- FILE: `includes/class-kmae-auth.php:565-591`
- SYMBOL: `KMAE_Auth::handle_resend_verification`
- PURPOSE: „Küldd újra" gomb a profilon és a letiltó képernyőkön.
- CALLED BY: `WP action `admin_post_kmae_resend_verification``
- CALLS: `KMAE_Auth::login_url`, `KMAE_Auth::is_email_verified`, `KMAE_Auth::generate_email_verify_token`, `KMAE_Auth::send_verification_email`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`
- POST KEYS: `kmae_redirect`, `kmae_resend_nonce`

## `KMAE_Auth::login`
- FILE: `includes/class-kmae-auth.php:594-619`
- SYMBOL: `KMAE_Auth::login`
- PURPOSE: „Küldd újra" gomb a profilon és a letiltó képernyőkön.
- CALLED BY: `KMAE_Public::admin_login`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::get_profile`
- FILE: `includes/class-kmae-auth.php:622-649`
- SYMBOL: `KMAE_Auth::get_profile`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Auth::get_account_type`, `KMAE_Auth::mark_email_verified`, `KMAE_Auth::wants_news_emails`, `KMAE_Auth::save_email_prefs`, `KMAE_Auth::is_email_verified`, `KMAE_Auth::is_phone_verified`, `KMAE_Auth::sync_phone`
- CALLS: `KMAE_Database::table`, `KMAE_Auth::sanitize_account_type`
- READS: `usermeta:kmae_phone`, `usermeta:kmae_account_type`, `table:user_profiles`, `wpdb:get_row`
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::wants_news_emails`
- FILE: `includes/class-kmae-auth.php:657-659`
- SYMBOL: `KMAE_Auth::wants_news_emails`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Auth::get_profile`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::save_email_prefs`
- FILE: `includes/class-kmae-auth.php:670-686`
- SYMBOL: `KMAE_Auth::save_email_prefs`
- PURPOSE: Értesítési preferenciák mentése a beállítások oldalról. A profil sorának többi mezőjét érintetlenül hagyja.
- CALLED BY: `KMAE_Google_Auth::handle_complete`, `KMAE_Public::admin_save_settings`
- CALLS: `KMAE_Database::table`, `KMAE_Auth::get_profile`, `KMAE_Auth::sanitize_account_type`
- READS: `table:user_profiles`
- WRITES: `wpdb:replace`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::is_email_verified`
- FILE: `includes/class-kmae-auth.php:689-691`
- SYMBOL: `KMAE_Auth::is_email_verified`
- PURPOSE: Értesítési preferenciák mentése a beállítások oldalról. A profil sorának többi mezőjét érintetlenül hagyja.
- CALLED BY: `KMAE_Auth::handle_resend_verification`, `KMAE_Auth::should_gate_for_verification`, `KMAE_Auth::is_fully_verified`
- CALLS: `KMAE_Auth::get_profile`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::should_gate_for_verification`
- FILE: `includes/class-kmae-auth.php:701-705`
- SYMBOL: `KMAE_Auth::should_gate_for_verification`
- PURPOSE: Hirdetésfeladás és ajánlattétel csak megerősített e-mail címmel megy — a rendszergazda tesztelés közben nem akad el ezen.
- CALLED BY: `KMAE_Public::handle_forms`, `KMAE_Shortcodes::create_listing`
- CALLS: `KMAE_Auth::is_email_verified`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::is_phone_verified`
- FILE: `includes/class-kmae-auth.php:708-710`
- SYMBOL: `KMAE_Auth::is_phone_verified`
- PURPOSE: Hirdetésfeladás és ajánlattétel csak megerősített e-mail címmel megy — a rendszergazda tesztelés közben nem akad el ezen.
- CALLED BY: `KMAE_Auth::is_fully_verified`
- CALLS: `KMAE_Auth::get_profile`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::is_fully_verified`
- FILE: `includes/class-kmae-auth.php:713-714`
- SYMBOL: `KMAE_Auth::is_fully_verified`
- PURPOSE: Hirdetésfeladás és ajánlattétel csak megerősített e-mail címmel megy — a rendszergazda tesztelés közben nem akad el ezen.
- CALLED BY: —
- CALLS: `KMAE_Auth::is_email_verified`, `KMAE_Auth::is_phone_verified`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::first_name`
- FILE: `includes/class-kmae-auth.php:727-739`
- SYMBOL: `KMAE_Auth::first_name`
- PURPOSE: A köszönéshez való keresztnév. Google-belépésnél a keresztnevet külön megkapjuk és eltároljuk; minden más esetben a megjelenített név első szava. Üres, ha nincs használható nevünk (például e-mail cím áll a név helyén).
- CALLED BY: `KMAE_Dashboard::get_data`, `KMAE_Auth::send_verification_email`, `KMAE_Notifications::send_offer_email`
- CALLS: —
- READS: `usermeta:kmae_given_name`
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::normalize_phone`
- FILE: `includes/class-kmae-auth.php:749-768`
- SYMBOL: `KMAE_Auth::normalize_phone`
- PURPOSE: Magyar telefonszám egységes alakra hozása. A böngészőben is szűrünk, de az megkerülhető — itt dől el, mi kerül az adatbázisba.
- CALLED BY: `KMAE_Google_Auth::handle_complete`, `KMAE_Auth::register`, `KMAE_Public::admin_save_profile`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::sync_phone`
- FILE: `includes/class-kmae-auth.php:771-789`
- SYMBOL: `KMAE_Auth::sync_phone`
- PURPOSE: Magyar telefonszám egységes alakra hozása. A böngészőben is szűrünk, de az megkerülhető — itt dől el, mi kerül az adatbázisba.
- CALLED BY: `KMAE_Google_Auth::handle_complete`, `KMAE_Public::admin_save_profile`
- CALLS: `KMAE_Database::table`, `KMAE_Auth::get_profile`, `KMAE_Auth::sanitize_account_type`
- READS: `table:user_profiles`
- WRITES: `usermeta:kmae_phone`, `usermeta:kmae_account_type`, `wpdb:replace`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Auth::generate_username`
- FILE: `includes/class-kmae-auth.php:792-803`
- SYMBOL: `KMAE_Auth::generate_username`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Auth::register`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `NOTIFICATIONS`

## `KMAE_Back::browse_url`
- FILE: `includes/class-kmae-back.php:14-15`
- SYMBOL: `KMAE_Back::browse_url`
- PURPOSE: Back URL helpers for inline page navigation.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Back::home_url`
- FILE: `includes/class-kmae-back.php:21-22`
- SYMBOL: `KMAE_Back::home_url`
- PURPOSE: Back to logged-in home / guest landing.
- CALLED BY: `KMAE_Back::listing_back_url`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Back::home_label`
- FILE: `includes/class-kmae-back.php:25-26`
- SYMBOL: `KMAE_Back::home_label`
- PURPOSE: Back to logged-in home / guest landing.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Back::listing_back_url`
- FILE: `includes/class-kmae-back.php:29-35`
- SYMBOL: `KMAE_Back::listing_back_url`
- PURPOSE: Back to logged-in home / guest landing.
- CALLED BY: —
- CALLS: `KMAE_Back::home_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Brand::init`
- FILE: `includes/class-kmae-brand.php:21-22`
- SYMBOL: `KMAE_Brand::init`
- PURPOSE: Official Albira brand assets and HTML mail wrapper.
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::logo_guest`
- FILE: `includes/class-kmae-brand.php:25-26`
- SYMBOL: `KMAE_Brand::logo_guest`
- PURPOSE: Official Albira brand assets and HTML mail wrapper.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::logo_app`
- FILE: `includes/class-kmae-brand.php:29-30`
- SYMBOL: `KMAE_Brand::logo_app`
- PURPOSE: Official Albira brand assets and HTML mail wrapper.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::email_logo_url`
- FILE: `includes/class-kmae-brand.php:41-42`
- SYMBOL: `KMAE_Brand::email_logo_url`
- PURPOSE: E-mailben a logó eredeti fájlja átlátszó — több levelezőkliens (pl. a Gmail képproxyja) nem kompozitálja helyesen a webp-alfacsatornát, és fekete dobozként rajzolja ki. Ezért a levelekhez egy fehér alapra
- CALLED BY: `KMAE_Brand::wrap_email`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::email_mascot_url`
- FILE: `includes/class-kmae-brand.php:51-52`
- SYMBOL: `KMAE_Brand::email_mascot_url`
- PURPOSE: A levelek közepén az info1.webp manó.
- CALLED BY: `KMAE_Auth::send_verification_email`, `KMAE_Notifications::on_buyer_revision`, `KMAE_Notifications::notify_buyer_other_sellers_hold`, `KMAE_Notifications::notify_buyer_other_sellers_resume`, `KMAE_Notifications::on_listing_deleted`, `KMAE_Notifications::listing_closed`, `KMAE_Notifications::buyer_found_other_car`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::logo_url`
- FILE: `includes/class-kmae-brand.php:61-63`
- SYMBOL: `KMAE_Brand::logo_url`
- PURPOSE: A logó mindenhol ugyanaz; a paraméter csak a régi hívások miatt maradt.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::html_content_type`
- FILE: `includes/class-kmae-brand.php:66-67`
- SYMBOL: `KMAE_Brand::html_content_type`
- PURPOSE: A logó mindenhol ugyanaz; a paraméter csak a régi hívások miatt maradt.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::send_mail`
- FILE: `includes/class-kmae-brand.php:79-92`
- SYMBOL: `KMAE_Brand::send_mail`
- PURPOSE: Send a branded HTML email with the official (logged-in) logo.
- CALLED BY: `KMAE_Notifications::send_offer_email`, `KMAE_Public::admin_contact_form`
- CALLS: `KMAE_Brand::wrap_email`
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::wrap_email`
- FILE: `includes/class-kmae-brand.php:112-189`
- SYMBOL: `KMAE_Brand::wrap_email`
- PURPOSE: Az összes kimenő levél egyetlen közös héja — az oldal világos, üveges arculatában: kék-narancs csík a tetején, kerek kártya, és egy igazi gomb a linknek, nem egy nyers URL beillesztve.
- CALLED BY: `KMAE_Brand::send_mail`, `KMAE_Auth::password_reset_email_html`, `KMAE_Auth::send_verification_email`
- CALLS: `KMAE_Brand::email_logo_url`, `KMAE_Brand::format_email_body`
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::ensure_html_headers`
- FILE: `includes/class-kmae-brand.php:198-230`
- SYMBOL: `KMAE_Brand::ensure_html_headers`
- PURPOSE: If the body already contains the official logo, force HTML content-type.
- CALLED BY: `WP filter `wp_mail``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Brand::format_email_body`
- FILE: `includes/class-kmae-brand.php:237-245`
- SYMBOL: `KMAE_Brand::format_email_body`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Brand::wrap_email`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`, `CONTACT-FORM`

## `KMAE_Browse::get_passed_listing_ids`
- FILE: `includes/class-kmae-browse.php:46-52`
- SYMBOL: `KMAE_Browse::get_passed_listing_ids`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Browse::record_pass`, `KMAE_Browse::personalize_listings`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::record_pass`
- FILE: `includes/class-kmae-browse.php:55-67`
- SYMBOL: `KMAE_Browse::record_pass`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::ajax_browse_pass`
- CALLS: `KMAE_Browse::get_passed_listing_ids`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::personalize_listings`
- FILE: `includes/class-kmae-browse.php:76-107`
- SYMBOL: `KMAE_Browse::personalize_listings`
- PURPOSE: Fresh listings first; previously passed ones every Nth slot.
- CALLED BY: `KMAE_Query::get_listings`
- CALLS: `KMAE_Browse::get_passed_listing_ids`, `KMAE_Browse::merge_with_passed_interval`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::merge_with_passed_interval`
- FILE: `includes/class-kmae-browse.php:115-129`
- SYMBOL: `KMAE_Browse::merge_with_passed_interval`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Browse::personalize_listings`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::parse_wizard_params`
- FILE: `includes/class-kmae-browse.php:137-146`
- SYMBOL: `KMAE_Browse::parse_wizard_params`
- PURPOSE: Parse browse wizard params from request.
- CALLED BY: `KMAE_Shortcodes::browse_vehicle_wizard`
- CALLS: `KMAE_Browse::parse_bodies_param`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::parse_bodies_param`
- FILE: `includes/class-kmae-browse.php:153-178`
- SYMBOL: `KMAE_Browse::parse_bodies_param`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Browse::parse_wizard_params`
- CALLS: `KMAE_Vehicle::body_types`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::bodies_to_query_value`
- FILE: `includes/class-kmae-browse.php:184-191`
- SYMBOL: `KMAE_Browse::bodies_to_query_value`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::browse_vehicle_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::bodies_label`
- FILE: `includes/class-kmae-browse.php:197-213`
- SYMBOL: `KMAE_Browse::bodies_label`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Browse::wizard_summary_label`
- CALLS: `KMAE_Vehicle::body_types`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::is_wizard_complete`
- FILE: `includes/class-kmae-browse.php:216-234`
- SYMBOL: `KMAE_Browse::is_wizard_complete`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::browse_vehicle_wizard`
- CALLS: `KMAE_Counties::is_valid`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::filter_by_wizard`
- FILE: `includes/class-kmae-browse.php:244-258`
- SYMBOL: `KMAE_Browse::filter_by_wizard`
- PURPOSE: Filter listings by vehicle browse wizard rules.
- CALLED BY: `KMAE_Shortcodes::browse_vehicle_wizard`
- CALLS: `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Browse::listing_matches_wizard`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::listing_matches_wizard`
- FILE: `includes/class-kmae-browse.php:261-289`
- SYMBOL: `KMAE_Browse::listing_matches_wizard`
- PURPOSE: Filter listings by vehicle browse wizard rules.
- CALLED BY: `KMAE_Browse::filter_by_wizard`
- CALLS: `KMAE_Seller::is_open_price`, `KMAE_Meta::get_offer_types`, `KMAE_Vehicle::get_vehicle_value`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Browse::wizard_summary_label`
- FILE: `includes/class-kmae-browse.php:292-308`
- SYMBOL: `KMAE_Browse::wizard_summary_label`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Counties::get_label`, `KMAE_Vehicle::browse_mode_labels`, `KMAE_Browse::bodies_label`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Company::get_company_name`
- FILE: `includes/class-kmae-company.php:24-30`
- SYMBOL: `KMAE_Company::get_company_name`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Company::seller_display_name`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::get_address`
- FILE: `includes/class-kmae-company.php:45-46`
- SYMBOL: `KMAE_Company::get_address`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::get_county`
- FILE: `includes/class-kmae-company.php:49-50`
- SYMBOL: `KMAE_Company::get_county`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Company::has_listing_location`, `KMAE_Company::apply_listing_location`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::get_city`
- FILE: `includes/class-kmae-company.php:53-54`
- SYMBOL: `KMAE_Company::get_city`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Company::has_listing_location`, `KMAE_Company::apply_listing_location`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::has_listing_location`
- FILE: `includes/class-kmae-company.php:57-58`
- SYMBOL: `KMAE_Company::has_listing_location`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Company::apply_listing_location`
- CALLS: `KMAE_Counties::validate_listing_location`, `KMAE_Company::get_county`, `KMAE_Company::get_city`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::apply_listing_location`
- FILE: `includes/class-kmae-company.php:61-67`
- SYMBOL: `KMAE_Company::apply_listing_location`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::save_meta_from_post`
- CALLS: `KMAE_Counties::save_listing_location`, `KMAE_Company::has_listing_location`, `KMAE_Company::get_county`, `KMAE_Company::get_city`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::seller_display_name`
- FILE: `includes/class-kmae-company.php:76-81`
- SYMBOL: `KMAE_Company::seller_display_name`
- PURPOSE: Public seller label (company name or personal display name).
- CALLED BY: —
- CALLS: `KMAE_Auth::is_company`, `KMAE_Company::get_company_name`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `KMAE_Company::save_profile_fields`
- FILE: `includes/class-kmae-company.php:91-122`
- SYMBOL: `KMAE_Company::save_profile_fields`
- PURPOSE: Save company fields from POST (register / profile).
- CALLED BY: `KMAE_Google_Auth::handle_complete`, `KMAE_Auth::register`, `KMAE_Public::admin_save_profile`
- CALLS: `KMAE_Auth::is_company`, `KMAE_Counties::validate_listing_location`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`



## `KMAE_Counties::get_all`
- FILE: `includes/class-kmae-counties.php:19-41`
- SYMBOL: `KMAE_Counties::get_all`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Counties::is_valid`, `KMAE_Counties::is_listing_county`, `KMAE_Counties::get_label`, `KMAE_Counties::get_map_label_short`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::is_valid`
- FILE: `includes/class-kmae-counties.php:44-45`
- SYMBOL: `KMAE_Counties::is_valid`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Browse::is_wizard_complete`, `KMAE_Shortcodes::browse`, `KMAE_Shortcodes::browse_vehicle_wizard`
- CALLS: `KMAE_Counties::get_all`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::is_listing_county`
- FILE: `includes/class-kmae-counties.php:48-49`
- SYMBOL: `KMAE_Counties::is_listing_county`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Counties::validate_listing_location`
- CALLS: `KMAE_Counties::get_all`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::get_label`
- FILE: `includes/class-kmae-counties.php:52-56`
- SYMBOL: `KMAE_Counties::get_label`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Counties::format_listing_location`, `KMAE_Counties::save_listing_location`, `KMAE_Browse::wizard_summary_label`
- CALLS: `KMAE_Counties::get_all`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::get_map_data`
- FILE: `includes/class-kmae-counties.php:64-87`
- SYMBOL: `KMAE_Counties::get_map_data`
- PURPOSE: Real county paths from Wikipedia HU_counties_blank.svg (public domain).
- CALLED BY: `KMAE_Counties::get_map_paths`, `KMAE_Counties::get_map_label_xy`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::get_map_paths`
- FILE: `includes/class-kmae-counties.php:93-99`
- SYMBOL: `KMAE_Counties::get_map_paths`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Counties::get_map_data`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::get_map_label_xy`
- FILE: `includes/class-kmae-counties.php:103-109`
- SYMBOL: `KMAE_Counties::get_map_label_xy`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Counties::get_map_data`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::format_listing_location`
- FILE: `includes/class-kmae-counties.php:112-120`
- SYMBOL: `KMAE_Counties::format_listing_location`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Counties::get_label`
- READS: `postmeta:_kmae_county`, `postmeta:_kmae_city`, `postmeta:_kmae_location`
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::validate_listing_location`
- FILE: `includes/class-kmae-counties.php:123-127`
- SYMBOL: `KMAE_Counties::validate_listing_location`
- PURPOSE: County valid AND city is an official settlement of that county (`KMAE_Counties::location_error` → ''/'required'/'mismatch', via `KMAE_Settlements::find`). Callers map 'mismatch' to `kmae_error=location_mismatch` (listing) / `company_location_mismatch` (company: register, profile, google-complete, listing of a company).
- CALLED BY: `KMAE_Google_Auth::handle_complete`, `KMAE_Listings::save_meta_from_post`, `KMAE_Auth::register`, `KMAE_Company::has_listing_location`, `KMAE_Company::save_profile_fields`, `KMAE_Meta::save_listing_meta`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Counties::is_listing_county`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::save_listing_location`
- FILE: `includes/class-kmae-counties.php:130-136`
- SYMBOL: `KMAE_Counties::save_listing_location`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::save_meta_from_post`, `KMAE_Company::apply_listing_location`, `KMAE_Meta::save_listing_meta`
- CALLS: `KMAE_Counties::get_label`
- READS: —
- WRITES: `postmeta:_kmae_county`, `postmeta:_kmae_city`, `postmeta:_kmae_location`
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Counties::get_map_label_short`
- FILE: `includes/class-kmae-counties.php:139-155`
- SYMBOL: `KMAE_Counties::get_map_label_short`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Counties::get_all`
- READS: —
- WRITES: —
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`, `BROWSE-SEARCH`

## `KMAE_Dashboard::greeting_case`
- FILE: `includes/class-kmae-dashboard.php:26-47`
- SYMBOL: `KMAE_Dashboard::greeting_case`
- PURPOSE: Megszólításra szánt név: az első betű mindig nagy, akkor is, ha a felhasználó csupa kisbetűvel regisztrált. Ékezetes kezdőbetűvel is helyesen működik (á → Á), a név többi része érintetlen marad.
- CALLED BY: `KMAE_Dashboard::get_data`, `KMAE_Dashboard::format_message_row`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::take_home_greeting`
- FILE: `includes/class-kmae-dashboard.php:51-66`
- SYMBOL: `KMAE_Dashboard::take_home_greeting`
- PURPOSE: Show the personal home greeting once per authenticated WordPress session.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::init`
- FILE: `includes/class-kmae-dashboard.php:69-70`
- SYMBOL: `KMAE_Dashboard::init`
- PURPOSE: Show the personal home greeting once per authenticated WordPress session.
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::maybe_track_listing_view`
- FILE: `includes/class-kmae-dashboard.php:76-103`
- SYMBOL: `KMAE_Dashboard::maybe_track_listing_view`
- PURPOSE: Count a listing view once per visitor session (skip owner / bots).
- CALLED BY: `WP action `template_redirect``
- CALLS: `KMAE_Meta::get_status`, `KMAE_Dashboard::increment_views`
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::increment_views`
- FILE: `includes/class-kmae-dashboard.php:109-125`
- SYMBOL: `KMAE_Dashboard::increment_views`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Dashboard::maybe_track_listing_view`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::get_listing_views`
- FILE: `includes/class-kmae-dashboard.php:132-133`
- SYMBOL: `KMAE_Dashboard::get_listing_views`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Dashboard::get_user_view_stats`, `KMAE_Dashboard::format_listing_row`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::get_user_view_stats`
- FILE: `includes/class-kmae-dashboard.php:142-177`
- SYMBOL: `KMAE_Dashboard::get_user_view_stats`
- PURPOSE: Sum views for all listings of a user.
- CALLED BY: `KMAE_Dashboard::get_data`
- CALLS: `KMAE_Dashboard::get_listing_views`
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::get_data`
- FILE: `includes/class-kmae-dashboard.php:186-414`
- SYMBOL: `KMAE_Dashboard::get_data`
- PURPOSE: Full dashboard payload for the logged-in home.
- CALLED BY: `KMAE_Shortcodes::render_hub_section`
- CALLS: `KMAE_Meta::get_status`, `KMAE_Auth::first_name`, `KMAE_Pages::url`, `KMAE_Dashboard::greeting_case`, `KMAE_Dashboard::format_listing_row`, `KMAE_Dashboard::format_message_row`, `KMAE_Dashboard::relative_time`, `KMAE_Dashboard::get_user_view_stats`, `KMAE_Dashboard::initials`, `KMAE_Dashboard::format_view_delta`
- READS: `postmeta:_kmae_archived_at`
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::format_listing_row`
- FILE: `includes/class-kmae-dashboard.php:421-450`
- SYMBOL: `KMAE_Dashboard::format_listing_row`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Dashboard::get_data`
- CALLS: `KMAE_Meta::get_status`, `KMAE_Meta::status_labels`, `KMAE_Query::get_gallery_ids`, `KMAE_Images::get_url`, `KMAE_Seller::get_public_price_row`, `KMAE_Vehicle::get_swipe_subtitle`, `KMAE_Dashboard::get_listing_views`
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::format_view_delta`
- FILE: `includes/class-kmae-dashboard.php:563-578`
- SYMBOL: `KMAE_Dashboard::format_view_delta`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Dashboard::get_data`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::initials`
- FILE: `includes/class-kmae-dashboard.php:585-592`
- SYMBOL: `KMAE_Dashboard::initials`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Dashboard::get_data`, `KMAE_Dashboard::format_message_row`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Dashboard::relative_time`
- FILE: `includes/class-kmae-dashboard.php:599-607`
- SYMBOL: `KMAE_Dashboard::relative_time`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Dashboard::get_data`, `KMAE_Dashboard::format_message_row`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`

## `KMAE_Database::table`
- FILE: `includes/class-kmae-database.php:14-16`
- SYMBOL: `KMAE_Database::table`
- PURPOSE: Database table names and helper queries.
- CALLED BY: `KMAE_Listings::collect_listing_attachment_ids`, `KMAE_Listings::force_delete_listing`, `KMAE_Auth::register`, `KMAE_Auth::mark_email_verified`, `KMAE_Auth::get_profile`, `KMAE_Auth::save_email_prefs`, `KMAE_Auth::sync_phone`, `KMAE_Database::get_private_data`, `KMAE_Database::save_private_data`, `KMAE_Database::get_offer`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`, `LIVE-NAV`

## `KMAE_Database::get_private_data`
- FILE: `includes/class-kmae-database.php:19-26`
- SYMBOL: `KMAE_Database::get_private_data`
- PURPOSE: Database table names and helper queries.
- CALLED BY: `KMAE_Database::save_private_data`, `KMAE_Meta::render_meta_box`
- CALLS: `KMAE_Database::table`
- READS: `wpdb:get_row`
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`, `LIVE-NAV`

## `KMAE_Database::save_private_data`
- FILE: `includes/class-kmae-database.php:29-59`
- SYMBOL: `KMAE_Database::save_private_data`
- PURPOSE: Database table names and helper queries.
- CALLED BY: `KMAE_Seller::save_from_post`, `KMAE_Meta::save_listing_meta`
- CALLS: `KMAE_Database::table`, `KMAE_Database::get_private_data`
- READS: —
- WRITES: `wpdb:insert`, `wpdb:update`, `wpdb:delete`
- RELATED FEATURE: `DASHBOARD-HUBS`, `LIVE-NAV`

## `KMAE_Database::db_now`
- FILE: `includes/class-kmae-database.php:207-209`
- SYMBOL: `KMAE_Database::db_now`
- PURPOSE: Az „olvasva” időpontokat az adatbázis órája szerint tároljuk: az ajánlat/ellenajánlat időbélyegek is a MySQL CURRENT_TIMESTAMP-jéből jönnek, így a két időskála garantáltan egyezik, akkor is, ha a
- CALLED BY: —
- CALLS: —
- READS: `wpdb:get_var`
- WRITES: —
- RELATED FEATURE: `DASHBOARD-HUBS`, `LIVE-NAV`

## `KMAE_Deactivator::deactivate`
- FILE: `includes/class-kmae-deactivator.php:14-16`
- SYMBOL: `KMAE_Deactivator::deactivate`
- PURPOSE: Plugin deactivation.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CRON-ARCHIVE`

## `KMAE_Google_Auth::init`
- FILE: `includes/class-kmae-google-auth.php:36-41`
- SYMBOL: `KMAE_Google_Auth::init`
- PURPOSE: Igaz, amíg a kötelező adatok hiányoznak az első belépés után.
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::enforce_completion`
- FILE: `includes/class-kmae-google-auth.php:54-74`
- SYMBOL: `KMAE_Google_Auth::enforce_completion`
- PURPOSE: Aki a kiegészítő képernyőt elhagyja, félkész fiókkal maradna: nincs telefonszáma (a kapcsolatfeloldás alapja), nincs fióktípusa, és nem fogadta el a feltételeket. Ilyen fiókkal nem engedjük használni az
- CALLED BY: `WP action `template_redirect``
- CALLS: `KMAE_Pages::url`, `KMAE_Google_Auth::needs_completion`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`
- GET KEYS: `kmae_complete`

## `KMAE_Google_Auth::admin_user_column`
- FILE: `includes/class-kmae-google-auth.php:83-85`
- SYMBOL: `KMAE_Google_Auth::admin_user_column`
- PURPOSE: A felhasználólistában lássuk, melyik fiók maradt félbe.
- CALLED BY: `WP filter `manage_users_columns``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::admin_user_column_value`
- FILE: `includes/class-kmae-google-auth.php:94-109`
- SYMBOL: `KMAE_Google_Auth::admin_user_column_value`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `manage_users_custom_column``
- CALLS: `KMAE_Google_Auth::needs_completion`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::client_id`
- FILE: `includes/class-kmae-google-auth.php:116-117`
- SYMBOL: `KMAE_Google_Auth::client_id`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Google_Auth::is_configured`, `KMAE_Google_Auth::redirect_to_google`, `KMAE_Google_Auth::fetch_profile`
- CALLS: —
- READS: `option:kmae_google_client_id`
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::client_secret`
- FILE: `includes/class-kmae-google-auth.php:120-121`
- SYMBOL: `KMAE_Google_Auth::client_secret`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Google_Auth::is_configured`, `KMAE_Google_Auth::fetch_profile`
- CALLS: —
- READS: `option:kmae_google_client_secret`
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::is_configured`
- FILE: `includes/class-kmae-google-auth.php:125-126`
- SYMBOL: `KMAE_Google_Auth::is_configured`
- PURPOSE: Addig nem mutatjuk a gombot, amíg nincsenek meg a kulcsok.
- CALLED BY: `KMAE_Google_Auth::maybe_handle_request`
- CALLS: `KMAE_Google_Auth::client_id`, `KMAE_Google_Auth::client_secret`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::redirect_uri`
- FILE: `includes/class-kmae-google-auth.php:130-131`
- SYMBOL: `KMAE_Google_Auth::redirect_uri`
- PURPOSE: Ezt a címet kell beírni a Google Console „Redirect URI" mezőjébe.
- CALLED BY: `KMAE_Google_Auth::redirect_to_google`, `KMAE_Google_Auth::fetch_profile`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::start_url`
- FILE: `includes/class-kmae-google-auth.php:144-151`
- SYMBOL: `KMAE_Google_Auth::start_url`
- PURPOSE: A gomb célcíme: innen megy a látogató a Google-höz.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::maybe_handle_request`
- FILE: `includes/class-kmae-google-auth.php:154-164`
- SYMBOL: `KMAE_Google_Auth::maybe_handle_request`
- PURPOSE: A gomb célcíme: innen megy a látogató a Google-höz.
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Google_Auth::is_configured`, `KMAE_Google_Auth::redirect_to_google`, `KMAE_Google_Auth::handle_callback`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`
- GET KEYS: `kmae_google`

## `KMAE_Google_Auth::redirect_to_google`
- FILE: `includes/class-kmae-google-auth.php:167-188`
- SYMBOL: `KMAE_Google_Auth::redirect_to_google`
- PURPOSE: A gomb célcíme: innen megy a látogató a Google-höz.
- CALLED BY: `KMAE_Google_Auth::maybe_handle_request`
- CALLS: `KMAE_Google_Auth::client_id`, `KMAE_Google_Auth::redirect_uri`
- READS: —
- WRITES: `transient:kmae_google_`
- RELATED FEATURE: `GOOGLE-AUTH`
- GET KEYS: `redirect_to`

## `KMAE_Google_Auth::handle_callback`
- FILE: `includes/class-kmae-google-auth.php:195-235`
- SYMBOL: `KMAE_Google_Auth::handle_callback`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Google_Auth::maybe_handle_request`
- CALLS: `KMAE_Auth::mark_email_verified`, `KMAE_Pages::url`, `KMAE_Google_Auth::fail`, `KMAE_Google_Auth::fetch_profile`, `KMAE_Google_Auth::resolve_user`
- READS: `transient:kmae_google_`
- WRITES: `transient:kmae_google_`
- RELATED FEATURE: `GOOGLE-AUTH`
- GET KEYS: `code`, `state`

## `KMAE_Google_Auth::fetch_profile`
- FILE: `includes/class-kmae-google-auth.php:244-285`
- SYMBOL: `KMAE_Google_Auth::fetch_profile`
- PURPOSE: A kódot kicseréljük a Google-nél, és kiolvassuk a fiók adatait.
- CALLED BY: `KMAE_Google_Auth::handle_callback`
- CALLS: `KMAE_Google_Auth::client_id`, `KMAE_Google_Auth::client_secret`, `KMAE_Google_Auth::redirect_uri`, `KMAE_Google_Auth::decode_id_token`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::decode_id_token`
- FILE: `includes/class-kmae-google-auth.php:296-305`
- SYMBOL: `KMAE_Google_Auth::decode_id_token`
- PURPOSE: Az azonosító jegy középső szelete hordozza az adatokat. Aláírást nem ellenőrzünk: a jegyet közvetlenül a Google-től kaptuk, HTTPS-en, a saját titkunkkal hitelesítve — nem a böngészőn keresztül érkezett.
- CALLED BY: `KMAE_Google_Auth::fetch_profile`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::resolve_user`
- FILE: `includes/class-kmae-google-auth.php:314-362`
- SYMBOL: `KMAE_Google_Auth::resolve_user`
- PURPOSE: Meglévő fiók megkeresése, vagy új létrehozása.
- CALLED BY: `KMAE_Google_Auth::handle_callback`
- CALLS: `KMAE_Auth::ensure_subscriber_caps`, `KMAE_Google_Auth::store_given_name`, `KMAE_Google_Auth::unique_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::store_given_name`
- FILE: `includes/class-kmae-google-auth.php:372-382`
- SYMBOL: `KMAE_Google_Auth::store_given_name`
- PURPOSE: Keresztnév eltárolása. Ha a Google nem adott, a teljes név első szava marad — az még mindig jobb, mint a teljes név vagy az e-mail cím.
- CALLED BY: `KMAE_Google_Auth::resolve_user`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::unique_login`
- FILE: `includes/class-kmae-google-auth.php:385-398`
- SYMBOL: `KMAE_Google_Auth::unique_login`
- PURPOSE: Keresztnév eltárolása. Ha a Google nem adott, a teljes név első szava marad — az még mindig jobb, mint a teljes név vagy az e-mail cím.
- CALLED BY: `KMAE_Google_Auth::resolve_user`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::needs_completion`
- FILE: `includes/class-kmae-google-auth.php:405-406`
- SYMBOL: `KMAE_Google_Auth::needs_completion`
- PURPOSE: Keresztnév eltárolása. Ha a Google nem adott, a teljes név első szava marad — az még mindig jobb, mint a teljes név vagy az e-mail cím.
- CALLED BY: `KMAE_Google_Auth::enforce_completion`, `KMAE_Google_Auth::admin_user_column_value`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Google_Auth::handle_complete`
- FILE: `includes/class-kmae-google-auth.php:409-496`
- SYMBOL: `KMAE_Google_Auth::handle_complete`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_google_complete``
- CALLS: `KMAE_Pages::url`, `KMAE_Auth::normalize_phone`, `KMAE_Auth::account_types`, `KMAE_Counties::validate_listing_location`, `KMAE_Company::save_profile_fields`, `KMAE_Auth::sync_phone`, `KMAE_Auth::save_email_prefs`, `KMAE_Auth::mark_email_verified`
- READS: —
- WRITES: `usermeta:kmae_account_type`, `usermeta:kmae_phone`
- RELATED FEATURE: `GOOGLE-AUTH`
- POST KEYS: `kmae_account_type`, `kmae_company_address`, `kmae_company_city`, `kmae_company_county`, `kmae_company_name`, `kmae_google_nonce`, `kmae_phone`, `kmae_terms`

## `KMAE_Google_Auth::fail`
- FILE: `includes/class-kmae-google-auth.php:499-501`
- SYMBOL: `KMAE_Google_Auth::fail`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Google_Auth::handle_callback`
- CALLS: `KMAE_Pages::url`
- READS: —
- WRITES: —
- RELATED FEATURE: `GOOGLE-AUTH`

## `KMAE_Icons::render`
- FILE: `includes/class-kmae-icons.php:18-42`
- SYMBOL: `KMAE_Icons::render`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::render_rounds_modal`
- CALLS: `KMAE_Icons::paths`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Icons::paths`
- FILE: `includes/class-kmae-icons.php:48-108`
- SYMBOL: `KMAE_Icons::paths`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Icons::render`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Images::init`
- FILE: `includes/class-kmae-images.php:20-27`
- SYMBOL: `KMAE_Images::init`
- PURPOSE: Feliratkozás a feltöltési folyamatra.
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::supports_webp`
- FILE: `includes/class-kmae-images.php:41-49`
- SYMBOL: `KMAE_Images::supports_webp`
- PURPOSE: A WordPress alapból minden nagy feltöltésből készít egy `-scaled` munkapéldányt, és MEGTARTJA mellette az érintetlen eredetit. Ezt a viselkedést kikapcsoljuk: így eredeti példány létre sem jön, nem csak
- CALLED BY: `KMAE_Images::process_attachment`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::orientation_from_dimensions`
- FILE: `includes/class-kmae-images.php:52-65`
- SYMBOL: `KMAE_Images::orientation_from_dimensions`
- PURPOSE: A WordPress alapból minden nagy feltöltésből készít egy `-scaled` munkapéldányt, és MEGTARTJA mellette az érintetlen eredetit. Ezt a viselkedést kikapcsoljuk: így eredeti példány létre sem jön, nem csak
- CALLED BY: `KMAE_Images::get_orientation`, `KMAE_Images::process_attachment`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::get_url`
- FILE: `includes/class-kmae-images.php:71-100`
- SYMBOL: `KMAE_Images::get_url`
- PURPOSE: Attachment URL with fallbacks when intermediate sizes are missing.
- CALLED BY: `KMAE_Dashboard::format_listing_row`, `KMAE_Query::get_listing_thumbnail_url`
- CALLS: `KMAE_Images::attachment_url_is_valid`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::responsive_attrs`
- FILE: `includes/class-kmae-images.php:117-131`
- SYMBOL: `KMAE_Images::responsive_attrs`
- PURPOSE: A reszponzív `srcset`/`sizes` attribútumok egy képhez.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::attachment_url_is_valid`
- FILE: `includes/class-kmae-images.php:134-149`
- SYMBOL: `KMAE_Images::attachment_url_is_valid`
- PURPOSE: A reszponzív `srcset`/`sizes` attribútumok egy képhez.
- CALLED BY: `KMAE_Images::get_url`
- CALLS: `KMAE_Images::attachment_url_exists`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::attachment_url_exists`
- FILE: `includes/class-kmae-images.php:152-161`
- SYMBOL: `KMAE_Images::attachment_url_exists`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Images::attachment_url_is_valid`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::get_orientation`
- FILE: `includes/class-kmae-images.php:164-175`
- SYMBOL: `KMAE_Images::get_orientation`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Images::orientation_from_dimensions`
- READS: `postmeta:_kmae_orientation`
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Images::process_attachment`
- FILE: `includes/class-kmae-images.php:181-288`
- SYMBOL: `KMAE_Images::process_attachment`
- PURPOSE: Optimize uploaded image: resize, WebP, orientation meta.
- CALLED BY: `KMAE_Listings::handle_image_uploads`, `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: `KMAE_Images::orientation_from_dimensions`, `KMAE_Images::supports_webp`
- READS: —
- WRITES: `postmeta:_kmae_orientation`
- RELATED FEATURE: `PHOTO-MOTOR`

## `KMAE_Launch::mode`
- FILE: `includes/class-kmae-launch.php:18-19`
- SYMBOL: `KMAE_Launch::mode`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Launch::is_single_category`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::category_slug`
- FILE: `includes/class-kmae-launch.php:22-23`
- SYMBOL: `KMAE_Launch::category_slug`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Launch::get_launch_category_id`, `KMAE_Launch::get_available_categories`, `KMAE_Launch::site_tagline`, `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Query::get_listings`, `KMAE_Shortcodes::browse`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::is_single_category`
- FILE: `includes/class-kmae-launch.php:26-27`
- SYMBOL: `KMAE_Launch::is_single_category`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Launch::should_skip_category_wizard`, `KMAE_Launch::get_available_categories`, `KMAE_Launch::site_tagline`, `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Query::get_listings`
- CALLS: `KMAE_Launch::mode`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::should_skip_category_wizard`
- FILE: `includes/class-kmae-launch.php:30-31`
- SYMBOL: `KMAE_Launch::should_skip_category_wizard`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Launch::wizard_total_steps`, `KMAE_Shortcodes::browse`
- CALLS: `KMAE_Launch::is_single_category`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::get_launch_category_id`
- FILE: `includes/class-kmae-launch.php:34-36`
- SYMBOL: `KMAE_Launch::get_launch_category_id`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Vehicle::save_from_post`
- CALLS: `KMAE_Launch::category_slug`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::get_available_categories`
- FILE: `includes/class-kmae-launch.php:44-60`
- SYMBOL: `KMAE_Launch::get_available_categories`
- PURPOSE: Categories visible in forms and browse (filtered in single mode).
- CALLED BY: —
- CALLS: `KMAE_Taxonomies::seed_categories`, `KMAE_Launch::is_single_category`, `KMAE_Launch::category_slug`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::wizard_total_steps`
- FILE: `includes/class-kmae-launch.php:63-67`
- SYMBOL: `KMAE_Launch::wizard_total_steps`
- PURPOSE: Categories visible in forms and browse (filtered in single mode).
- CALLED BY: `KMAE_Launch::wizard_step_label`
- CALLS: `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Launch::should_skip_category_wizard`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::wizard_step_label`
- FILE: `includes/class-kmae-launch.php:70-76`
- SYMBOL: `KMAE_Launch::wizard_step_label`
- PURPOSE: Categories visible in forms and browse (filtered in single mode).
- CALLED BY: —
- CALLS: `KMAE_Launch::wizard_total_steps`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Launch::site_tagline`
- FILE: `includes/class-kmae-launch.php:79-83`
- SYMBOL: `KMAE_Launch::site_tagline`
- PURPOSE: Categories visible in forms and browse (filtered in single mode).
- CALLED BY: —
- CALLS: `KMAE_Launch::is_single_category`, `KMAE_Launch::category_slug`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `VEHICLE`, `TAXONOMY-LAUNCH`

## `KMAE_Listing_Drafts::init`
- FILE: `includes/class-kmae-listing-drafts.php:23-24`
- SYMBOL: `KMAE_Listing_Drafts::init`
- PURPOSE: Félbehagyott hirdetések (wizard automatikus mentése).
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Drafts::get_user_draft`
- FILE: `includes/class-kmae-listing-drafts.php:34-51`
- SYMBOL: `KMAE_Listing_Drafts::get_user_draft`
- PURPOSE: A felhasználó egy saját piszkozata, vagy null.
- CALLED BY: `KMAE_Listing_Drafts::ajax_save`, `KMAE_Listing_Drafts::discard_submitted`, `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: `postmeta:_kmae_status`
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Drafts::get_latest_draft`
- FILE: `includes/class-kmae-listing-drafts.php:60-79`
- SYMBOL: `KMAE_Listing_Drafts::get_latest_draft`
- PURPOSE: A legutóbb módosított piszkozat.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Drafts::to_client`
- FILE: `includes/class-kmae-listing-drafts.php:88-101`
- SYMBOL: `KMAE_Listing_Drafts::to_client`
- PURPOSE: A wizardnak átadott adatcsomag.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Drafts::ajax_save`
- FILE: `includes/class-kmae-listing-drafts.php:108-159`
- SYMBOL: `KMAE_Listing_Drafts::ajax_save`
- PURPOSE: Mentés AJAX-on (a wizard lépésváltáskor, mezőváltozáskor és az oldal elhagyásakor hívja).
- CALLED BY: `WP action `wp_ajax_kmae_save_listing_draft``
- CALLS: `KMAE_Listing_Drafts::get_user_draft`
- READS: —
- WRITES: `postmeta:_kmae_status`
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`
- POST KEYS: `draft_id`, `payload`, `title`

## `KMAE_Listing_Drafts::discard_submitted`
- FILE: `includes/class-kmae-listing-drafts.php:167-172`
- SYMBOL: `KMAE_Listing_Drafts::discard_submitted`
- PURPOSE: Sikeres feladás után a piszkozat törlődik, hogy ne legyen dupla hirdetés.
- CALLED BY: `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Listing_Drafts::get_user_draft`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`
- POST KEYS: `kmae_draft_id`

## `KMAE_Listing_Wizard::description_topics`
- FILE: `includes/class-kmae-listing-wizard.php:54-125`
- SYMBOL: `KMAE_Listing_Wizard::description_topics`
- PURPOSE: Az asszisztens szempontjai a hirdetés szövegéhez.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Wizard::assistant_intro`
- FILE: `includes/class-kmae-listing-wizard.php:137-166`
- SYMBOL: `KMAE_Listing_Wizard::assistant_intro`
- PURPOSE: Az asszisztens bemutatkozása a szövegíró lépésen.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: `KMAE_Listing_Wizard::assist_inline_badge`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Wizard::assist_inline_badge`
- FILE: `includes/class-kmae-listing-wizard.php:177-187`
- SYMBOL: `KMAE_Listing_Wizard::assist_inline_badge`
- PURPOSE: Az asszisztens szövegébe ágyazott kis jelvény — pontosan úgy fest, mint a lenti hozzáadó/eltávolító gomb, hogy a mondat ne csak leírja, hanem meg is mutassa, melyik gombra gondol.
- CALLED BY: `KMAE_Listing_Wizard::assistant_intro`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Wizard::assistant_reactions`
- FILE: `includes/class-kmae-listing-wizard.php:197-209`
- SYMBOL: `KMAE_Listing_Wizard::assistant_reactions`
- PURPOSE: Az asszisztens reakciói a hozzáadásokra, sorszám szerint: az első hozzáadásnál az 1-es, a másodiknál a 2-es szólal meg. Amelyik sorszámhoz nincs bejegyzés, ott nem szól bele — nem dicsér minden kattintásnál.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Wizard::assistant_publish`
- FILE: `includes/class-kmae-listing-wizard.php:225-229`
- SYMBOL: `KMAE_Listing_Wizard::assistant_publish`
- PURPOSE: A feladás pillanatához tartozó dicséret. A fotók lépésén a „Tovább" helyett „Hirdetés feladása" áll — megnyomás után ez jelenik meg, és csak utána megy el a mentés, hogy a visszajelzés ne villanjon el.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Wizard::description_progress_messages`
- FILE: `includes/class-kmae-listing-wizard.php:271-278`
- SYMBOL: `KMAE_Listing_Wizard::description_progress_messages`
- PURPOSE: Bátorító mondat a kitöltöttség arányához. A cél nem a 100%, hanem hogy az eladó lássa: minden kitöltött szempont növeli az eladás esélyét.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listing_Wizard::phases`
- FILE: `includes/class-kmae-listing-wizard.php:281-313`
- SYMBOL: `KMAE_Listing_Wizard::phases`
- PURPOSE: Bátorító mondat a kitöltöttség arányához. A cél nem a 100%, hanem hogy az eladó lássa: minden kitöltött szempont növeli az eladás esélyét.
- CALLED BY: `KMAE_Public::enqueue_listing_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `KMAE_Listings::init`
- FILE: `includes/class-kmae-listings.php:14-17`
- SYMBOL: `KMAE_Listings::init`
- PURPOSE: Delete listing and related data.
- CALLED BY: `KMAE_Plugin::run`
- CALLS: `KMAE_Activator::schedule_cron`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::get_archive_lifecycle`
- FILE: `includes/class-kmae-listings.php:32-69`
- SYMBOL: `KMAE_Listings::get_archive_lifecycle`
- PURPOSE: Lifecycle info for archived listings (null if not archived).
- CALLED BY: `KMAE_Listings::is_archive_locked`, `KMAE_Listings::can_reactivate_listing`
- CALLS: `KMAE_Meta::get_status`
- READS: `postmeta:_kmae_archived_at`
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::is_archive_locked`
- FILE: `includes/class-kmae-listings.php:72-74`
- SYMBOL: `KMAE_Listings::is_archive_locked`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::can_edit_listing`
- CALLS: `KMAE_Listings::get_archive_lifecycle`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::can_reactivate_listing`
- FILE: `includes/class-kmae-listings.php:77-79`
- SYMBOL: `KMAE_Listings::can_reactivate_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::can_edit_listing`, `KMAE_Listings::can_delete_listing`, `KMAE_Listings::reactivate_listing`
- CALLS: `KMAE_Listings::get_archive_lifecycle`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::can_edit_listing`
- FILE: `includes/class-kmae-listings.php:82-84`
- SYMBOL: `KMAE_Listings::can_edit_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Shortcodes::create_listing`
- CALLS: `KMAE_Meta::get_status`, `KMAE_Listings::is_archive_locked`, `KMAE_Listings::can_reactivate_listing`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::can_delete_listing`
- FILE: `includes/class-kmae-listings.php:87-91`
- SYMBOL: `KMAE_Listings::can_delete_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- PURPOSE (2026-10-10): always true for an existing `kmae_listing` (seller may delete anytime); ownership in `delete_listing()`.
- CALLED BY: `KMAE_Public::admin_delete_listing`, `templates/my-listings.php`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::expiry_clock_start` / `days_until_expiry`
- FILE: `includes/class-kmae-listings.php`
- PURPOSE: 60 napos lejárati óra indulása (legkésőbbi published/reactivated/renewed, fallback post_date); hátralévő napok aktív hirdetésnél.
- CALLED BY: `KMAE_Listings::due_for_expiry`, `templates/my-listings.php`
- READS: `postmeta:_kmae_published_at`, `postmeta:_kmae_reactivated_at`, `postmeta:_kmae_renewed_at`
- RELATED FEATURE: `LISTINGS`, `CRON-ARCHIVE`

## `KMAE_Listings::expire_listings`
- FILE: `includes/class-kmae-listings.php`
- PURPOSE: hourly cron; active + ≥ `EXPIRE_DAYS` + no accepted chat offer → `expired`.
- CALLED BY: WP action `kmae_expire_listings`
- CALLS: `KMAE_Listings::due_for_expiry`, `KMAE_Notifications::notify_listing_expired`
- WRITES: `postmeta:_kmae_status`, `postmeta:_kmae_expired_at`
- RELATED FEATURE: `LISTINGS`, `CRON-ARCHIVE`, `NOTIFICATIONS`

## `KMAE_Listings::due_for_expiry`
- FILE: `includes/class-kmae-listings.php`
- PURPOSE: lejártatandó hirdetések ID-i (active + óra ≥ `EXPIRE_DAYS` + nincs accepted chat-ajánlat); nem módosít.
- CALLED BY: `KMAE_Listings::expire_listings`
- CALLS: `KMAE_Listings::expiry_clock_start`, `KMAE_Chat_Offers::get_accepted_for_listing`
- RELATED FEATURE: `LISTINGS`, `CRON-ARCHIVE`

## `KMAE_Listings::renew_listing`
- FILE: `includes/class-kmae-listings.php`
- PURPOSE: lejárt hirdetés hosszabbítása (tulaj, csak `expired`) → `active`, óra újraindul.
- CALLED BY: `KMAE_Public::admin_renew_listing`
- CALLS: `KMAE_Listings::user_owns_listing`, `KMAE_Meta::get_status`
- WRITES: `postmeta:_kmae_status`, `postmeta:_kmae_renewed_at`, `postmeta:_kmae_expired_at` (delete)
- RELATED FEATURE: `LISTINGS`

## `KMAE_Public::admin_renew_listing`
- FILE: `public/class-kmae-public.php`
- CALLED BY: WP action `admin_post_kmae_renew_listing` (nonce `kmae_renew_listing` / `kmae_renew_listing_nonce`)
- CALLS: `KMAE_Listings::renew_listing`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::get_listings_redirect_url`
- RELATED FEATURE: `LISTINGS`

## `KMAE_Notifications::notify_listing_expired`
- FILE: `includes/class-kmae-notifications.php`
- PURPOSE: lejárati levél az eladónak („Hosszabbítás” gomb → Hirdetéseim); nincs mail_key, nem kikapcsolható.
- CALLED BY: `KMAE_Listings::expire_listings`
- CALLS: `KMAE_Notifications::send_offer_email`
- RELATED FEATURE: `NOTIFICATIONS`, `LISTINGS`

## `KMAE_Listings::reactivate_listing`
- FILE: `includes/class-kmae-listings.php:94-110`
- SYMBOL: `KMAE_Listings::reactivate_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_reactivate_listing`
- CALLS: `KMAE_Listings::user_owns_listing`, `KMAE_Listings::can_reactivate_listing`
- READS: —
- WRITES: `postmeta:_kmae_status`, `postmeta:_kmae_archived_at`, `postmeta:_kmae_reactivated_at`
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`
- EMITS: `kmae_listing_published`

## `KMAE_Listings::purge_expired_archived`
- FILE: `includes/class-kmae-listings.php:116-147`
- SYMBOL: `KMAE_Listings::purge_expired_archived`
- PURPOSE: Daily cron: delete archived listings past the reactivation window.
- CALLED BY: `WP action `kmae_purge_archived_listings``
- CALLS: `KMAE_Listings::delete_listing`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::user_owns_listing`
- FILE: `includes/class-kmae-listings.php:150-152`
- SYMBOL: `KMAE_Listings::user_owns_listing`
- PURPOSE: Daily cron: delete archived listings past the reactivation window.
- CALLED BY: `KMAE_Listings::reactivate_listing`, `KMAE_Listings::delete_listing`, `KMAE_Public::admin_update_listing`, `KMAE_Shortcodes::create_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::activate_if_photos_ready`
- FILE: `includes/class-kmae-listings.php:161-179`
- SYMBOL: `KMAE_Listings::activate_if_photos_ready`
- PURPOSE: Activate listing when required vehicle photos are complete.
- CALLED BY: `KMAE_Public::admin_update_listing`
- CALLS: `KMAE_Photo_Motor::is_enabled`, `KMAE_Photo_Motor::count_required_filled`, `KMAE_Photo_Motor::required_count`, `KMAE_Meta::get_status`
- READS: `postmeta:_kmae_published_at`
- WRITES: `postmeta:_kmae_status`, `postmeta:_kmae_published_at`
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`
- EMITS: `kmae_listing_published`

## `KMAE_Listings::save_meta_from_post`
- FILE: `includes/class-kmae-listings.php:182-223`
- SYMBOL: `KMAE_Listings::save_meta_from_post`
- PURPOSE: Activate listing when required vehicle photos are complete.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Auth::is_company`, `KMAE_Company::apply_listing_location`, `KMAE_Counties::validate_listing_location`, `KMAE_Counties::save_listing_location`, `KMAE_Vehicle::save_from_post`, `KMAE_Taxonomies::default_offer_types`, `KMAE_Seller::save_from_post`
- READS: —
- WRITES: `postmeta:_kmae_condition`, `postmeta:_kmae_status`, `postmeta:_kmae_published_at`, `postmeta:_kmae_offer_types`, `postmeta:_kmae_trade_in_categories`
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`
- POST KEYS: `kmae_category`, `kmae_city`, `kmae_condition`, `kmae_county`

## `KMAE_Listings::handle_image_uploads`
- FILE: `includes/class-kmae-listings.php:226-327`
- SYMBOL: `KMAE_Listings::handle_image_uploads`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Photo_Motor::is_enabled`, `KMAE_Photo_Motor::handle_slot_uploads`, `KMAE_Photo_Motor::analyze_file`, `KMAE_Photo_Motor::autocorrect_file`, `KMAE_Photo_Motor::analysis_record`, `KMAE_Images::process_attachment`
- READS: —
- WRITES: `postmeta:_kmae_gallery`
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::apply_gallery_edits`
- FILE: `includes/class-kmae-listings.php:333-373`
- SYMBOL: `KMAE_Listings::apply_gallery_edits`
- PURPOSE: Apply reorder / delete from edit form before new uploads.
- CALLED BY: `KMAE_Public::admin_update_listing`
- CALLS: `KMAE_Listings::attachment_belongs_to_listing`
- READS: —
- WRITES: `postmeta:_kmae_gallery`
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`
- POST KEYS: `kmae_gallery_order`, `kmae_remove_images`

## `KMAE_Listings::attachment_belongs_to_listing`
- FILE: `includes/class-kmae-listings.php:376-380`
- SYMBOL: `KMAE_Listings::attachment_belongs_to_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::apply_gallery_edits`, `KMAE_Listings::delete_listing_attachments`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::collect_listing_attachment_ids`
- FILE: `includes/class-kmae-listings.php:383-430`
- SYMBOL: `KMAE_Listings::collect_listing_attachment_ids`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::delete_listing_attachments`
- CALLS: `KMAE_Database::table`
- READS: `postmeta:_kmae_gallery`, `wpdb:get_col`
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::delete_listing_attachments`
- FILE: `includes/class-kmae-listings.php:433-438`
- SYMBOL: `KMAE_Listings::delete_listing_attachments`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::force_delete_listing`
- CALLS: `KMAE_Listings::collect_listing_attachment_ids`, `KMAE_Listings::attachment_belongs_to_listing`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::delete_listing`
- FILE: `includes/class-kmae-listings.php:441-446`
- SYMBOL: `KMAE_Listings::delete_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::purge_expired_archived`, `KMAE_Public::admin_delete_listing`
- CALLS: `KMAE_Listings::user_owns_listing`, `KMAE_Listings::force_delete_listing`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Listings::force_delete_listing`
- FILE: `includes/class-kmae-listings.php:452-478`
- SYMBOL: `KMAE_Listings::force_delete_listing`
- PURPOSE: Delete listing and related data (admin or owner after permission check).
- CALLED BY: `KMAE_Admin::handle_delete_listing`, `KMAE_Admin::handle_listing_bulk_actions`, `KMAE_Listings::delete_listing`
- CALLS: `KMAE_Notifications::on_listing_deleted`, `KMAE_Database::table`, `KMAE_Listings::delete_listing_attachments`
- READS: `table:offers`, `table:private_data`
- WRITES: `wpdb:delete`
- RELATED FEATURE: `LISTINGS`, `PHOTO-MOTOR`, `CRON-ARCHIVE`

## `KMAE_Live_Page::get_fingerprint`
- FILE: `includes/class-kmae-live-page.php:17-78`
- SYMBOL: `KMAE_Live_Page::get_fingerprint`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::ajax_poll_page`
- CALLS: `KMAE_Database::get_offer`, `KMAE_Meta::get_status`
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Meta::status_labels`
- FILE: `includes/class-kmae-meta.php:25-31`
- SYMBOL: `KMAE_Meta::status_labels`
- PURPOSE: User-facing status labels (seller UI).
- CALLED BY: `KMAE_Dashboard::format_listing_row`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Meta::is_publicly_visible`
- FILE: `includes/class-kmae-meta.php:40-41`
- SYMBOL: `KMAE_Meta::is_publicly_visible`
- PURPOSE: Whether buyers can see the listing in browse / public single.
- CALLED BY: —
- CALLS: `KMAE_Meta::get_status`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Meta::__construct`
- FILE: `includes/class-kmae-meta.php:44-46`
- SYMBOL: `KMAE_Meta::__construct`
- PURPOSE: Whether buyers can see the listing in browse / public single.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Meta::add_meta_boxes`
- FILE: `includes/class-kmae-meta.php:49-57`
- SYMBOL: `KMAE_Meta::add_meta_boxes`
- PURPOSE: Whether buyers can see the listing in browse / public single.
- CALLED BY: `WP action `add_meta_boxes``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Meta::render_meta_box`
- FILE: `includes/class-kmae-meta.php:60-73`
- SYMBOL: `KMAE_Meta::render_meta_box`
- PURPOSE: Whether buyers can see the listing in browse / public single.
- CALLED BY: —
- CALLS: `KMAE_Database::get_private_data`, `KMAE_Taxonomies::offer_type_labels`, `KMAE_Meta::get_offer_types`
- READS: `postmeta:_kmae_county`, `postmeta:_kmae_city`, `postmeta:_kmae_location`, `postmeta:_kmae_condition`, `postmeta:_kmae_status`
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Meta::save_listing_meta`
- FILE: `includes/class-kmae-meta.php:76-118`
- SYMBOL: `KMAE_Meta::save_listing_meta`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `save_post_kmae_listing``
- CALLS: `KMAE_Counties::validate_listing_location`, `KMAE_Counties::save_listing_location`, `KMAE_Taxonomies::default_offer_types`, `KMAE_Seller::get_price_mode`, `KMAE_Database::save_private_data`, `KMAE_Vehicle::save_from_post`
- READS: `postmeta:_kmae_published_at`
- WRITES: `postmeta:_kmae_location`, `postmeta:_kmae_condition`, `postmeta:_kmae_status`, `postmeta:_kmae_offer_types`, `postmeta:_kmae_trade_in_categories`, `postmeta:_kmae_published_at`
- RELATED FEATURE: `LISTINGS`, `ADMIN`
- POST KEYS: `kmae_city`, `kmae_condition`, `kmae_county`, `kmae_listing_nonce`, `kmae_location`, `kmae_minimum_cash`, `kmae_status`

## `KMAE_Meta::get_status`
- FILE: `includes/class-kmae-meta.php:121-122`
- SYMBOL: `KMAE_Meta::get_status`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Admin::listing_column_content`, `KMAE_Admin::listing_row_actions`, `KMAE_Listings::get_archive_lifecycle`, `KMAE_Listings::can_edit_listing`, `KMAE_Listings::can_delete_listing`, `KMAE_Listings::activate_if_photos_ready`, `KMAE_Dashboard::maybe_track_listing_view`, `KMAE_Dashboard::get_data`, `KMAE_Dashboard::format_listing_row`, `KMAE_Meta::is_publicly_visible`, `KMAE_Live_Page::get_fingerprint`
- CALLS: —
- READS: `postmeta:_kmae_status`
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Meta::get_offer_types`
- FILE: `includes/class-kmae-meta.php:125-127`
- SYMBOL: `KMAE_Meta::get_offer_types`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Admin::listing_column_content`, `KMAE_Seller::save_from_post`, `KMAE_Meta::render_meta_box`, `KMAE_Browse::listing_matches_wizard`
- CALLS: `KMAE_Taxonomies::default_offer_types`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`, `ADMIN`

## `KMAE_Moderation::is_enabled`
- FILE: `includes/class-kmae-moderation.php:17-21`
- SYMBOL: `KMAE_Moderation::is_enabled`
- PURPOSE: Automated listing moderation (rule-based, no external AI).
- CALLED BY: `KMAE_Moderation::validate`, `KMAE_Public::enqueue_listing_wizard`, `KMAE_Public::admin_update_listing`
- CALLS: —
- READS: `option:kmae_auto_moderation`
- WRITES: —
- RELATED FEATURE: `MODERATION`

## `KMAE_Moderation::validate`
- FILE: `includes/class-kmae-moderation.php:32-81`
- SYMBOL: `KMAE_Moderation::validate`
- PURPOSE: Validate listing before publish. Returns WP_Error on failure.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Vehicle::get_missing_required`, `KMAE_Photo_Motor::is_enabled`, `KMAE_Photo_Motor::required_count`, `KMAE_Moderation::is_enabled`, `KMAE_Moderation::contains_contact_info`
- READS: —
- WRITES: —
- RELATED FEATURE: `MODERATION`

## `KMAE_Moderation::contains_contact_info`
- FILE: `includes/class-kmae-moderation.php:84-95`
- SYMBOL: `KMAE_Moderation::contains_contact_info`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Moderation::validate`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `MODERATION`

## `KMAE_Moderation::count_uploaded_images`
- FILE: `includes/class-kmae-moderation.php:98-115`
- SYMBOL: `KMAE_Moderation::count_uploaded_images`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Photo_Motor::is_enabled`, `KMAE_Photo_Motor::count_required_uploads`
- READS: —
- WRITES: —
- RELATED FEATURE: `MODERATION`

## `KMAE_Moderation::stash_errors_for_user`
- FILE: `includes/class-kmae-moderation.php:121-122`
- SYMBOL: `KMAE_Moderation::stash_errors_for_user`
- PURPOSE: Store rejection reasons in transient for display on form.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: —
- READS: —
- WRITES: `transient:kmae_mod_errors_`
- RELATED FEATURE: `MODERATION`

## `KMAE_Moderation::pull_errors_for_user`
- FILE: `includes/class-kmae-moderation.php:125-129`
- SYMBOL: `KMAE_Moderation::pull_errors_for_user`
- PURPOSE: Store rejection reasons in transient for display on form.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `MODERATION`

## `KMAE_Nav::__construct`
- FILE: `includes/class-kmae-nav.php:15-20`
- SYMBOL: `KMAE_Nav::__construct`
- PURPOSE: Dynamic nav: login/register when logged out, logout when logged in.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::enqueue_assets`
- FILE: `includes/class-kmae-nav.php:23-39`
- SYMBOL: `KMAE_Nav::enqueue_assets`
- PURPOSE: Dynamic nav: login/register when logged out, logout when logged in.
- CALLED BY: `WP action `wp_enqueue_scripts``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::inline_nav_css`
- FILE: `includes/class-kmae-nav.php:42-88`
- SYMBOL: `KMAE_Nav::inline_nav_css`
- PURPOSE: Dynamic nav: login/register when logged out, logout when logged in.
- CALLED BY: `WP action `wp_head``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::filter_menu_items`
- FILE: `includes/class-kmae-nav.php:90-124`
- SYMBOL: `KMAE_Nav::filter_menu_items`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `wp_nav_menu_objects``, `WP filter `wp_get_nav_menu_items``
- CALLS: `KMAE_Nav::is_login_item`, `KMAE_Nav::is_register_item`, `KMAE_Nav::is_logout_item`
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::filter_navigation_block`
- FILE: `includes/class-kmae-nav.php:127-145`
- SYMBOL: `KMAE_Nav::filter_navigation_block`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `render_block``
- CALLS: `KMAE_Nav::url_is_auth`, `KMAE_Nav::label_is_auth`
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::is_login_item`
- FILE: `includes/class-kmae-nav.php:148-151`
- SYMBOL: `KMAE_Nav::is_login_item`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Nav::filter_menu_items`
- CALLS: `KMAE_Nav::url_is_auth`, `KMAE_Nav::label_is_auth`
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::is_register_item`
- FILE: `includes/class-kmae-nav.php:154-157`
- SYMBOL: `KMAE_Nav::is_register_item`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Nav::filter_menu_items`
- CALLS: `KMAE_Nav::url_is_auth`, `KMAE_Nav::label_is_auth`
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::is_logout_item`
- FILE: `includes/class-kmae-nav.php:160-163`
- SYMBOL: `KMAE_Nav::is_logout_item`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Nav::filter_menu_items`
- CALLS: `KMAE_Nav::label_is_auth`
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::url_is_auth`
- FILE: `includes/class-kmae-nav.php:166-174`
- SYMBOL: `KMAE_Nav::url_is_auth`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Nav::filter_navigation_block`, `KMAE_Nav::is_login_item`, `KMAE_Nav::is_register_item`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::label_is_auth`
- FILE: `includes/class-kmae-nav.php:177-189`
- SYMBOL: `KMAE_Nav::label_is_auth`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Nav::filter_navigation_block`, `KMAE_Nav::is_login_item`, `KMAE_Nav::is_register_item`, `KMAE_Nav::is_logout_item`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Nav::create_logout_item`
- FILE: `includes/class-kmae-nav.php:192-215`
- SYMBOL: `KMAE_Nav::create_logout_item`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LIVE-NAV`

## `KMAE_Notifications::init`
- FILE: `includes/class-kmae-notifications.php:14-23`
- SYMBOL: `KMAE_Notifications::init`
- PURPOSE: Email notifications.
- CALLED BY: `KMAE_Plugin::run`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`

## `KMAE_Notifications::on_listing_deleted`
- FILE: `includes/class-kmae-notifications.php:498-536`
- SYMBOL: `KMAE_Notifications::on_listing_deleted`
- PURPOSE: Notify everyone who sent an offer before the listing is removed.
- CALLED BY: `KMAE_Listings::force_delete_listing`
- CALLS: `KMAE_Brand::email_mascot_url`, `KMAE_Notifications::send_offer_email`
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`

## `KMAE_Notifications::send_offer_email`
- FILE: `includes/class-kmae-notifications.php:674-691`
- SYMBOL: `KMAE_Notifications::send_offer_email`
- PURPOSE: Buyer bought another car — notify seller on withdrawn talks.
- CALLED BY: `KMAE_Notifications::notify_dealer_lead` (mail key `dealer_lead`), `KMAE_Notifications::on_buyer_revision`, `KMAE_Notifications::notify_buyer_other_sellers_hold`, `KMAE_Notifications::notify_buyer_other_sellers_resume`, `KMAE_Notifications::on_listing_deleted`, `KMAE_Notifications::listing_closed`, `KMAE_Notifications::buyer_found_other_car`
- CALLS: `KMAE_Auth::first_name`, `KMAE_Brand::send_mail`
- READS: —
- WRITES: —
- RELATED FEATURE: `NOTIFICATIONS`

## `KMAE_Pages::__construct`
- FILE: `includes/class-kmae-pages.php:105-111`
- SYMBOL: `KMAE_Pages::__construct`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::register_query_vars`
- FILE: `includes/class-kmae-pages.php:120-133`
- SYMBOL: `KMAE_Pages::register_query_vars`
- PURPOSE: Keep browse wizard filters on canonical page URLs.
- CALLED BY: `WP filter `query_vars``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::maybe_ensure_pages`
- FILE: `includes/class-kmae-pages.php:139-145`
- SYMBOL: `KMAE_Pages::maybe_ensure_pages`
- PURPOSE: Create/update shortcode pages after plugin updates (without re-activation).
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Pages::ensure_pages`
- READS: `option:kmae_pages_version`
- WRITES: `option:kmae_pages_version`
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::offer_thread_body_class`
- FILE: `includes/class-kmae-pages.php:151-161`
- SYMBOL: `KMAE_Pages::offer_thread_body_class`
- PURPOSE: Full-page chat room styling when viewing a single offer thread.
- CALLED BY: `WP filter `body_class``
- CALLS: `KMAE_Pages::is_messages_page`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`
- GET KEYS: `kmae_offer`

## `KMAE_Pages::mark_conversation_seen_on_visit`
- FILE: `includes/class-kmae-pages.php:167-182`
- SYMBOL: `KMAE_Pages::mark_conversation_seen_on_visit`
- PURPOSE: Mark a chat conversation as read when its thread is opened.
- CALLED BY: `WP action `template_redirect``
- CALLS: `KMAE_Conversations::get`, `KMAE_Database::mark_conversation_thread_seen`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`
- GET KEYS: `kmae_offer`

## `KMAE_Pages::browse_page_url`
- FILE: `includes/class-kmae-pages.php:204-205`
- SYMBOL: `KMAE_Pages::browse_page_url`
- PURPOSE: Whether the current request is the offers hub page.
- CALLED BY: `KMAE_Shortcodes::get_browse_base_url`
- CALLS: `KMAE_Pages::url`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::url`
- FILE: `includes/class-kmae-pages.php:223-237`
- SYMBOL: `KMAE_Pages::url`
- PURPOSE: Permalink for a registered shortcode page (by slug key in PAGES).
- CALLED BY: `KMAE_Google_Auth::enforce_completion`, `KMAE_Google_Auth::handle_callback`, `KMAE_Google_Auth::handle_complete`, `KMAE_Google_Auth::fail`, `KMAE_Dashboard::get_data`, `KMAE_Auth::login_url`, `KMAE_Auth::register_url`, `KMAE_Auth::maybe_handle_email_verify_link`, `KMAE_Pages::browse_page_url`, `KMAE_Public::get_settings_redirect_url`, `KMAE_Public::admin_contact_form`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::shortcode_for`
- FILE: `includes/class-kmae-pages.php:246-247`
- SYMBOL: `KMAE_Pages::shortcode_for`
- PURPOSE: Shortcode tag for a registered page slug, or empty string.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::ensure_pages`
- FILE: `includes/class-kmae-pages.php:265-270`
- SYMBOL: `KMAE_Pages::ensure_pages`
- PURPOSE: 301 from old slug after rename.
- CALLED BY: `KMAE_Pages::maybe_ensure_pages`, `KMAE_Activator::activate`, `KMAE_Activator::maybe_upgrade`
- CALLS: `KMAE_Pages::ensure_page`, `KMAE_Pages::ensure_front_page`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::ensure_front_page`
- FILE: `includes/class-kmae-pages.php:301-315`
- SYMBOL: `KMAE_Pages::ensure_front_page`
- PURPOSE: Set dedicated landing page as site front page; browse lives at /hirdetesek/.
- CALLED BY: `KMAE_Pages::ensure_pages`
- CALLS: `KMAE_Pages::ensure_page`
- READS: `option:page_on_front`
- WRITES: `option:show_on_front`, `option:page_on_front`
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::ensure_page`
- FILE: `includes/class-kmae-pages.php:318-351`
- SYMBOL: `KMAE_Pages::ensure_page`
- PURPOSE: Set dedicated landing page as site front page; browse lives at /hirdetesek/.
- CALLED BY: `KMAE_Pages::ensure_pages`, `KMAE_Pages::ensure_front_page`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Pages::inject_page_content`
- FILE: `includes/class-kmae-pages.php:354-389`
- SYMBOL: `KMAE_Pages::inject_page_content`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `the_content``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `LIVE-NAV`

## `KMAE_Photo_Motor::required_slots`
- FILE: `includes/class-kmae-photo-motor.php:124-175`
- SYMBOL: `KMAE_Photo_Motor::required_slots`
- PURPOSE: 7 kötelező nézet (diagram pontokkal).
- CALLED BY: `KMAE_Photo_Motor::all_slots`, `KMAE_Photo_Motor::required_count`, `KMAE_Photo_Motor::count_required_filled`, `KMAE_Photo_Motor::count_required_uploads`, `KMAE_Photo_Motor::validate_request`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::recommended_slots`
- FILE: `includes/class-kmae-photo-motor.php:183-227`
- SYMBOL: `KMAE_Photo_Motor::recommended_slots`
- PURPOSE: 6 ajánlott — összesen 13 fotópont.
- CALLED BY: `KMAE_Photo_Motor::all_slots`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::recommended_total_count`
- FILE: `includes/class-kmae-photo-motor.php:233-234`
- SYMBOL: `KMAE_Photo_Motor::recommended_total_count`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Photo_Motor::all_slots`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::all_slots`
- FILE: `includes/class-kmae-photo-motor.php:240-241`
- SYMBOL: `KMAE_Photo_Motor::all_slots`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Photo_Motor::recommended_total_count`, `KMAE_Photo_Motor::get_slots`, `KMAE_Photo_Motor::has_any_upload`, `KMAE_Photo_Motor::handle_slot_uploads`, `KMAE_Photo_Motor::slots_to_gallery`
- CALLS: `KMAE_Photo_Motor::required_slots`, `KMAE_Photo_Motor::recommended_slots`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::required_count`
- FILE: `includes/class-kmae-photo-motor.php:244-245`
- SYMBOL: `KMAE_Photo_Motor::required_count`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::activate_if_photos_ready`, `KMAE_Moderation::validate`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Photo_Motor::required_slots`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::is_enabled`
- FILE: `includes/class-kmae-photo-motor.php:248-250`
- SYMBOL: `KMAE_Photo_Motor::is_enabled`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::activate_if_photos_ready`, `KMAE_Listings::handle_image_uploads`, `KMAE_Moderation::validate`, `KMAE_Moderation::count_uploaded_images`, `KMAE_Photo_Motor::validate_request`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::get_slots`
- FILE: `includes/class-kmae-photo-motor.php:257-271`
- SYMBOL: `KMAE_Photo_Motor::get_slots`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Photo_Motor::count_required_filled`, `KMAE_Photo_Motor::validate_request`, `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: `KMAE_Photo_Motor::all_slots`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::count_required_filled`
- FILE: `includes/class-kmae-photo-motor.php:278-286`
- SYMBOL: `KMAE_Photo_Motor::count_required_filled`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::activate_if_photos_ready`, `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Photo_Motor::get_slots`, `KMAE_Photo_Motor::required_slots`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::count_required_uploads`
- FILE: `includes/class-kmae-photo-motor.php:294-301`
- SYMBOL: `KMAE_Photo_Motor::count_required_uploads`
- PURPOSE: Count required slot files in current request.
- CALLED BY: `KMAE_Moderation::count_uploaded_images`
- CALLS: `KMAE_Photo_Motor::required_slots`, `KMAE_Photo_Motor::has_upload_for_slot`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::has_upload_for_slot`
- FILE: `includes/class-kmae-photo-motor.php:308-313`
- SYMBOL: `KMAE_Photo_Motor::has_upload_for_slot`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Photo_Motor::count_required_uploads`, `KMAE_Photo_Motor::has_any_upload`, `KMAE_Photo_Motor::validate_request`, `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::has_any_upload`
- FILE: `includes/class-kmae-photo-motor.php:321-327`
- SYMBOL: `KMAE_Photo_Motor::has_any_upload`
- PURPOSE: Whether the current request includes any slot photo upload.
- CALLED BY: `KMAE_Public::admin_update_listing`
- CALLS: `KMAE_Photo_Motor::all_slots`, `KMAE_Photo_Motor::has_upload_for_slot`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::validate_request`
- FILE: `includes/class-kmae-photo-motor.php:337-366`
- SYMBOL: `KMAE_Photo_Motor::validate_request`
- PURPOSE: Validate create/update photo requirements.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Photo_Motor::is_enabled`, `KMAE_Photo_Motor::get_slots`, `KMAE_Photo_Motor::required_slots`, `KMAE_Photo_Motor::has_upload_for_slot`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::handle_slot_uploads`
- FILE: `includes/class-kmae-photo-motor.php:376-508`
- SYMBOL: `KMAE_Photo_Motor::handle_slot_uploads`
- PURPOSE: Process slot uploads, rebuild gallery order.
- CALLED BY: `KMAE_Listings::handle_image_uploads`
- CALLS: `KMAE_Images::process_attachment`, `KMAE_Photo_Motor::get_slots`, `KMAE_Photo_Motor::all_slots`, `KMAE_Photo_Motor::has_upload_for_slot`, `KMAE_Photo_Motor::analyze_file`, `KMAE_Photo_Motor::fix_advice`, `KMAE_Photo_Motor::autocorrect_file`, `KMAE_Photo_Motor::fix_labels`, `KMAE_Photo_Motor::analysis_record`, `KMAE_Photo_Motor::attachment_belongs_to_listing`, `KMAE_Photo_Motor::slots_to_gallery`, `KMAE_Photo_Motor::autofix_notice_key`
- READS: —
- WRITES: `postmeta:_kmae_gallery`
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::autofix_notice_key`
- FILE: `includes/class-kmae-photo-motor.php:516-517`
- SYMBOL: `KMAE_Photo_Motor::autofix_notice_key`
- PURPOSE: A gépi javításokról szóló értesítés kulcsa — felhasználónként.
- CALLED BY: `KMAE_Photo_Motor::handle_slot_uploads`, `KMAE_Photo_Motor::take_autofix_notice`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::take_autofix_notice`
- FILE: `includes/class-kmae-photo-motor.php:525-532`
- SYMBOL: `KMAE_Photo_Motor::take_autofix_notice`
- PURPOSE: Egyszer olvasható értesítés az utolsó mentés gépi javításairól.
- CALLED BY: —
- CALLS: `KMAE_Photo_Motor::autofix_notice_key`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::fix_advice`
- FILE: `includes/class-kmae-photo-motor.php:541-552`
- SYMBOL: `KMAE_Photo_Motor::fix_advice`
- PURPOSE: Gyakorlati tanács a hibakódhoz — általános biztatás helyett konkrétum.
- CALLED BY: `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::slots_to_gallery`
- FILE: `includes/class-kmae-photo-motor.php:559-566`
- SYMBOL: `KMAE_Photo_Motor::slots_to_gallery`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: `KMAE_Photo_Motor::all_slots`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::analyze_file`
- FILE: `includes/class-kmae-photo-motor.php:587-641`
- SYMBOL: `KMAE_Photo_Motor::analyze_file`
- PURPOSE: Képminőség-elemzés (V2). Nem átlagfényerőt néz, hanem luminancia-hisztogramot: percentiliseket, bebukott árnyék- és kiégett csúcsfény-arányt, dinamikatartományt —
- CALLED BY: `KMAE_Listings::handle_image_uploads`, `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: `KMAE_Photo_Motor::measure`, `KMAE_Photo_Motor::classify`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::measure`
- FILE: `includes/class-kmae-photo-motor.php:655-702`
- SYMBOL: `KMAE_Photo_Motor::measure`
- PURPOSE: Egy megnyitott képről minden mérés egy helyen.
- CALLED BY: `KMAE_Photo_Motor::analyze_file`, `KMAE_Photo_Motor::autocorrect_file`, `KMAE_Photo_Motor::try_sharpen_step`, `KMAE_Photo_Motor::try_step`
- CALLS: `KMAE_Photo_Motor::edge_and_hash`, `KMAE_Photo_Motor::region_stats`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::region_stats`
- FILE: `includes/class-kmae-photo-motor.php:717-793`
- SYMBOL: `KMAE_Photo_Motor::region_stats`
- PURPOSE: Egy képterület hisztogramja és az abból számolt mérőszámok.
- CALLED BY: `KMAE_Photo_Motor::measure`
- CALLS: `KMAE_Photo_Motor::percentile`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::percentile`
- FILE: `includes/class-kmae-photo-motor.php:802-811`
- SYMBOL: `KMAE_Photo_Motor::percentile`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Photo_Motor::region_stats`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::edge_and_hash`
- FILE: `includes/class-kmae-photo-motor.php:820-881`
- SYMBOL: `KMAE_Photo_Motor::edge_and_hash`
- PURPOSE: Élesség, szórás és képujjlenyomat a régi 64×64-es mintáról.
- CALLED BY: `KMAE_Photo_Motor::measure`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::classify`
- FILE: `includes/class-kmae-photo-motor.php:893-991`
- SYMBOL: `KMAE_Photo_Motor::classify`
- PURPOSE: A mérésekből ítélet: mi a baj, mit javítunk, és mit üzenünk.
- CALLED BY: `KMAE_Photo_Motor::analyze_file`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::autocorrect_file`
- FILE: `includes/class-kmae-photo-motor.php:1005-1205`
- SYMBOL: `KMAE_Photo_Motor::autocorrect_file`
- PURPOSE: Gépi képjavítás a feltöltés ideiglenes fájlján (V2).
- CALLED BY: `KMAE_Listings::handle_image_uploads`, `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: `KMAE_Photo_Motor::measure`, `KMAE_Photo_Motor::try_step`, `KMAE_Photo_Motor::build_dark_lut`, `KMAE_Photo_Motor::build_bright_lut`, `KMAE_Photo_Motor::build_s_curve_lut`, `KMAE_Photo_Motor::sharpen_allowed`, `KMAE_Photo_Motor::try_sharpen_step`, `KMAE_Photo_Motor::compact_stats`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::try_sharpen_step`
- FILE: `includes/class-kmae-photo-motor.php:1216-1258`
- SYMBOL: `KMAE_Photo_Motor::try_sharpen_step`
- PURPOSE: Élesítés másolaton, méréssel: csak akkor marad, ha tényleg élesebb lett és közben nem gyártott új kiégést vagy bebukást.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`
- CALLS: `KMAE_Photo_Motor::clone_image`, `KMAE_Photo_Motor::measure`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::try_step`
- FILE: `includes/class-kmae-photo-motor.php:1270-1294`
- SYMBOL: `KMAE_Photo_Motor::try_step`
- PURPOSE: Egy korrekciós lépés másolaton, méréssel és döntéssel.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`
- CALLS: `KMAE_Photo_Motor::clone_image`, `KMAE_Photo_Motor::apply_luma_lut`, `KMAE_Photo_Motor::measure`, `KMAE_Photo_Motor::step_improved`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::step_improved`
- FILE: `includes/class-kmae-photo-motor.php:1305-1322`
- SYMBOL: `KMAE_Photo_Motor::step_improved`
- PURPOSE: A korrekció csak akkor marad, ha a számok szerint tényleg javított.
- CALLED BY: `KMAE_Photo_Motor::try_step`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::sharpen_allowed`
- FILE: `includes/class-kmae-photo-motor.php:1332-1345`
- SYMBOL: `KMAE_Photo_Motor::sharpen_allowed`
- PURPOSE: Élesítés csak ott, ahol nem a zajt emeli ki.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::build_dark_lut`
- FILE: `includes/class-kmae-photo-motor.php:1355-1365`
- SYMBOL: `KMAE_Photo_Motor::build_dark_lut`
- PURPOSE: Sötét kép tónusgörbéje: az árnyékokat és a középtónusokat emeli, a csúcsfényeket pedig egyre kevésbé bántja — így az ég nem ég ki.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`
- CALLS: `KMAE_Photo_Motor::highlight_guard`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::build_bright_lut`
- FILE: `includes/class-kmae-photo-motor.php:1375-1386`
- SYMBOL: `KMAE_Photo_Motor::build_bright_lut`
- PURPOSE: Túl világos kép: a közép- és világos tónusokat tömöríti, az árnyékokat lehetőleg érintetlenül hagyja.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::build_s_curve_lut`
- FILE: `includes/class-kmae-photo-motor.php:1395-1403`
- SYMBOL: `KMAE_Photo_Motor::build_s_curve_lut`
- PURPOSE: Enyhe S-görbe a fakó képhez: középen ad egy kis tartást, a két végén fokozatosan elhal, hogy ne gyártson új bebukást vagy kiégést.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::highlight_guard`
- FILE: `includes/class-kmae-photo-motor.php:1412-1420`
- SYMBOL: `KMAE_Photo_Motor::highlight_guard`
- PURPOSE: A világosítás ereje 170 fölött fokozatosan elhal, 245 közelében nulla.
- CALLED BY: `KMAE_Photo_Motor::build_dark_lut`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::apply_luma_lut`
- FILE: `includes/class-kmae-photo-motor.php:1432-1474`
- SYMBOL: `KMAE_Photo_Motor::apply_luma_lut`
- PURPOSE: A tónusgörbét a fényességre alkalmazzuk, és a különbséget adjuk a csatornákhoz — így a színek nem tolódnak el. A lépést a rendelkezésre álló fejtérhez igazítjuk, hogy ne keletkezzen új kiégés vagy bebukás.
- CALLED BY: `KMAE_Photo_Motor::try_step`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::clone_image`
- FILE: `includes/class-kmae-photo-motor.php:1481-1497`
- SYMBOL: `KMAE_Photo_Motor::clone_image`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Photo_Motor::try_sharpen_step`, `KMAE_Photo_Motor::try_step`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::compact_stats`
- FILE: `includes/class-kmae-photo-motor.php:1506-1528`
- SYMBOL: `KMAE_Photo_Motor::compact_stats`
- PURPOSE: A metába csak a lényeg kerül — a 256 rekeszes hisztogram nem.
- CALLED BY: `KMAE_Photo_Motor::autocorrect_file`, `KMAE_Photo_Motor::analysis_record`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::analysis_record`
- FILE: `includes/class-kmae-photo-motor.php:1539-1558`
- SYMBOL: `KMAE_Photo_Motor::analysis_record`
- PURPOSE: A képhez mentett mérési napló. Ha később az derül ki, hogy egy kép megítélése rossz volt, nem találgatunk: itt vannak a számok.
- CALLED BY: `KMAE_Listings::handle_image_uploads`, `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: `KMAE_Photo_Motor::compact_stats`
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::client_config`
- FILE: `includes/class-kmae-photo-motor.php:1567-1594`
- SYMBOL: `KMAE_Photo_Motor::client_config`
- PURPOSE: A küszöbök egyetlen forrása a PHP — a böngésző innen kapja őket, hogy a két oldal soha ne mondhasson mást ugyanarról a képről.
- CALLED BY: `KMAE_Public::enqueue`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::fix_labels`
- FILE: `includes/class-kmae-photo-motor.php:1603-1617`
- SYMBOL: `KMAE_Photo_Motor::fix_labels`
- PURPOSE: A javítások emberi nyelvű megfogalmazása a visszajelzéshez.
- CALLED BY: `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Photo_Motor::attachment_belongs_to_listing`
- FILE: `includes/class-kmae-photo-motor.php:1620-1624`
- SYMBOL: `KMAE_Photo_Motor::attachment_belongs_to_listing`
- PURPOSE: A javítások emberi nyelvű megfogalmazása a visszajelzéshez.
- CALLED BY: `KMAE_Photo_Motor::handle_slot_uploads`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PHOTO-MOTOR`, `MODERATION`

## `KMAE_Plugin::run`
- FILE: `includes/class-kmae-plugin.php:14-39`
- SYMBOL: `KMAE_Plugin::run`
- PURPOSE: Main plugin orchestrator.
- CALLED BY: —
- CALLS: `KMAE_Activator::maybe_upgrade`, `KMAE_Brand::init`, `KMAE_Auth::init`, `KMAE_Google_Auth::init`, `KMAE_Notifications::init`, `KMAE_Listings::init`, `KMAE_Listing_Drafts::init`, `KMAE_Images::init`, `KMAE_Dashboard::init`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Post_Types::__construct`
- FILE: `includes/class-kmae-post-types.php:14-16`
- SYMBOL: `KMAE_Post_Types::__construct`
- PURPOSE: Custom post type: kmae_listing (hirdetés).
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Post_Types::register`
- FILE: `includes/class-kmae-post-types.php:19-20`
- SYMBOL: `KMAE_Post_Types::register`
- PURPOSE: Custom post type: kmae_listing (hirdetés).
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Post_Types::register_post_type`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Post_Types::register_post_type`
- FILE: `includes/class-kmae-post-types.php:23-51`
- SYMBOL: `KMAE_Post_Types::register_post_type`
- PURPOSE: Custom post type: kmae_listing (hirdetés).
- CALLED BY: `KMAE_Post_Types::register`, `KMAE_Activator::activate`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Post_Types::template_loader`
- FILE: `includes/class-kmae-post-types.php:54-67`
- SYMBOL: `KMAE_Post_Types::template_loader`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP filter `template_include``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Query::get_listings`
- FILE: `includes/class-kmae-query.php:14-107`
- SYMBOL: `KMAE_Query::get_listings`
- PURPOSE: Listing query helpers with filters.
- CALLED BY: `KMAE_Query::get_popular_listings`, `KMAE_Shortcodes::browse`, `KMAE_Shortcodes::browse_vehicle_wizard`
- CALLS: `KMAE_Launch::is_single_category`, `KMAE_Launch::category_slug`, `KMAE_Browse::personalize_listings`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Query::get_popular_listings`
- FILE: `includes/class-kmae-query.php:116-122`
- SYMBOL: `KMAE_Query::get_popular_listings`
- PURPOSE: Popular listings for landing page (newest first).
- CALLED BY: —
- CALLS: `KMAE_Query::get_listings`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Query::get_listing_thumbnail_url`
- FILE: `includes/class-kmae-query.php:128-145`
- SYMBOL: `KMAE_Query::get_listing_thumbnail_url`
- PURPOSE: Featured image URL, or first gallery image as fallback.
- CALLED BY: `KMAE_Query::get_listing_full_image_url`
- CALLS: `KMAE_Images::get_url`, `KMAE_Query::get_gallery_ids`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Query::get_listing_thumbnail_id`
- FILE: `includes/class-kmae-query.php:157-164`
- SYMBOL: `KMAE_Query::get_listing_thumbnail_id`
- PURPOSE: A kártyaképhez tartozó attachment azonosítója — ugyanaz a visszaesési sorrend, mint a `get_listing_thumbnail_url()`-nél. A reszponzív `srcset` előállításához kell, ahhoz ugyanis nem az URL, hanem az azonosító szükséges.
- CALLED BY: —
- CALLS: `KMAE_Query::get_gallery_ids`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Query::get_listing_full_image_url`
- FILE: `includes/class-kmae-query.php:170-171`
- SYMBOL: `KMAE_Query::get_listing_full_image_url`
- PURPOSE: Full-size URL for lightbox.
- CALLED BY: —
- CALLS: `KMAE_Query::get_listing_thumbnail_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Query::get_gallery_ids`
- FILE: `includes/class-kmae-query.php:174-180`
- SYMBOL: `KMAE_Query::get_gallery_ids`
- PURPOSE: Full-size URL for lightbox.
- CALLED BY: `KMAE_Dashboard::format_listing_row`, `KMAE_Query::get_listing_thumbnail_url`, `KMAE_Query::get_listing_thumbnail_id`, `KMAE_Public::admin_update_listing`
- CALLS: —
- READS: `postmeta:_kmae_gallery`
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`

## `KMAE_Seller::price_mode_defs`
- FILE: `includes/class-kmae-seller.php:36-75`
- SYMBOL: `KMAE_Seller::price_mode_defs`
- PURPOSE: The three sale modes. Single source for wizard cards, admin and public labels.
- CALLED BY: `KMAE_Seller::price_mode_labels`, `KMAE_Seller::sanitize_price_mode`, `KMAE_Seller::get_public_price_row`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::price_mode_labels`
- FILE: `includes/class-kmae-seller.php:81-82`
- SYMBOL: `KMAE_Seller::price_mode_labels`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Seller::price_mode_defs`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::commitment_defs`
- FILE: `includes/class-kmae-seller.php:90-102`
- SYMBOL: `KMAE_Seller::commitment_defs`
- PURPOSE: Core optional seller pledges (checkboxes).
- CALLED BY: `KMAE_Seller::get_commitments`, `KMAE_Seller::get_badge_rows`, `KMAE_Seller::save_from_post`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::extra_commitment_defs`
- FILE: `includes/class-kmae-seller.php:110-132`
- SYMBOL: `KMAE_Seller::extra_commitment_defs`
- PURPOSE: Extra pledges shown as badges.
- CALLED BY: `KMAE_Seller::get_commitments`, `KMAE_Seller::get_badge_rows`, `KMAE_Seller::save_from_post`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::sanitize_price_mode`
- FILE: `includes/class-kmae-seller.php:139-147`
- SYMBOL: `KMAE_Seller::sanitize_price_mode`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::get_price_mode`, `KMAE_Seller::save_from_post`
- CALLS: `KMAE_Seller::price_mode_defs`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_price_mode`
- FILE: `includes/class-kmae-seller.php:154-155`
- SYMBOL: `KMAE_Seller::get_price_mode`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::is_fixed_price`, `KMAE_Seller::is_guide_price`, `KMAE_Seller::is_open_price`, `KMAE_Seller::get_public_price`, `KMAE_Seller::get_public_price_row`, `KMAE_Meta::save_listing_meta`
- CALLS: `KMAE_Seller::sanitize_price_mode`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_fixed_price`
- FILE: `includes/class-kmae-seller.php:162-163`
- SYMBOL: `KMAE_Seller::get_fixed_price`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::get_public_price`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_guide_price`
- FILE: `includes/class-kmae-seller.php:170-171`
- SYMBOL: `KMAE_Seller::get_guide_price`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::get_public_price`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::is_fixed_price`
- FILE: `includes/class-kmae-seller.php:178-179`
- SYMBOL: `KMAE_Seller::is_fixed_price`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Seller::get_price_mode`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::is_guide_price`
- FILE: `includes/class-kmae-seller.php:186-187`
- SYMBOL: `KMAE_Seller::is_guide_price`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Seller::get_price_mode`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::is_open_price`
- FILE: `includes/class-kmae-seller.php:194-195`
- SYMBOL: `KMAE_Seller::is_open_price`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Browse::listing_matches_wizard`
- CALLS: `KMAE_Seller::get_price_mode`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_public_price`
- FILE: `includes/class-kmae-seller.php:204-213`
- SYMBOL: `KMAE_Seller::get_public_price`
- PURPOSE: Publicly shown price. 0 when the mode has no public price.
- CALLED BY: `KMAE_Seller::get_public_price_row`
- CALLS: `KMAE_Seller::get_price_mode`, `KMAE_Seller::get_fixed_price`, `KMAE_Seller::get_guide_price`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_public_price_row`
- FILE: `includes/class-kmae-seller.php:222-235`
- SYMBOL: `KMAE_Seller::get_public_price_row`
- PURPOSE: Single source for price rendering on cards, swipe and single view.
- CALLED BY: `KMAE_Dashboard::format_listing_row`
- CALLS: `KMAE_Seller::get_price_mode`, `KMAE_Seller::get_public_price`, `KMAE_Seller::price_mode_defs`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_commitments`
- FILE: `includes/class-kmae-seller.php:242-248`
- SYMBOL: `KMAE_Seller::get_commitments`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::has_commitment`
- CALLS: `KMAE_Seller::commitment_defs`, `KMAE_Seller::extra_commitment_defs`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::has_commitment`
- FILE: `includes/class-kmae-seller.php:256-257`
- SYMBOL: `KMAE_Seller::has_commitment`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::get_badge_rows`
- CALLS: `KMAE_Seller::get_commitments`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_extra_other`
- FILE: `includes/class-kmae-seller.php:264-265`
- SYMBOL: `KMAE_Seller::get_extra_other`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Seller::get_badge_rows`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::get_badge_rows`
- FILE: `includes/class-kmae-seller.php:274-311`
- SYMBOL: `KMAE_Seller::get_badge_rows`
- PURPOSE: Public badge rows for listing cards / single view.
- CALLED BY: —
- CALLS: `KMAE_Seller::commitment_defs`, `KMAE_Seller::extra_commitment_defs`, `KMAE_Seller::has_commitment`, `KMAE_Seller::get_extra_other`
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::count_active_cycle_listings`
- FILE: `includes/class-kmae-seller.php:320-344`
- SYMBOL: `KMAE_Seller::count_active_cycle_listings`
- PURPOSE: Active selling cycle = listing with status "active".
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::can_create_listing`
- FILE: `includes/class-kmae-seller.php:353-354`
- SYMBOL: `KMAE_Seller::can_create_listing`
- PURPOSE: Listing creation is unlimited for every account type (private and company).
- CALLED BY: `KMAE_Public::process_create_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::parse_price_from_post`
- FILE: `includes/class-kmae-seller.php:363-369`
- SYMBOL: `KMAE_Seller::parse_price_from_post`
- PURPOSE: Parse a whole-number price from POST (supports formatted input like 3.500.000).
- CALLED BY: `KMAE_Seller::save_from_post`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `LISTINGS`

## `KMAE_Seller::save_from_post`
- FILE: `includes/class-kmae-seller.php:379-424`
- SYMBOL: `KMAE_Seller::save_from_post`
- PURPOSE: Validate + save price mode, defects, commitments from POST.
- CALLED BY: `KMAE_Listings::save_meta_from_post`
- CALLS: `KMAE_Meta::get_offer_types`, `KMAE_Taxonomies::default_offer_types`, `KMAE_Database::save_private_data`, `KMAE_Seller::sanitize_price_mode`, `KMAE_Seller::parse_price_from_post`, `KMAE_Seller::commitment_defs`, `KMAE_Seller::extra_commitment_defs`
- READS: —
- WRITES: `postmeta:_kmae_offer_types`
- RELATED FEATURE: `LISTINGS`
- POST KEYS: `kmae_commit_extra_other`, `kmae_commitments`, `kmae_price_mode`

## `KMAE_Settings::init`
- FILE: `includes/class-kmae-settings.php:14-15`
- SYMBOL: `KMAE_Settings::init`
- PURPOSE: Plugin settings and defaults.
- CALLED BY: `KMAE_Admin::__construct`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::register_settings`
- FILE: `includes/class-kmae-settings.php:18-74`
- SYMBOL: `KMAE_Settings::register_settings`
- PURPOSE: Plugin settings and defaults.
- CALLED BY: `WP action `admin_init``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::init_defaults`
- FILE: `includes/class-kmae-settings.php:77-81`
- SYMBOL: `KMAE_Settings::init_defaults`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: `option:kmae_auto_moderation`, `option:kmae_seller_video_url`, `option:kmae_buyer_video_url`, `option:kmae_contact_form_email`
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::sanitize_youtube_url`
- FILE: `includes/class-kmae-settings.php:90-101`
- SYMBOL: `KMAE_Settings::sanitize_youtube_url`
- PURPOSE: Keep only valid YouTube watch / short / embed / youtu.be URLs.
- CALLED BY: —
- CALLS: `KMAE_Settings::youtube_id_from_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::youtube_id_from_url`
- FILE: `includes/class-kmae-settings.php:110-145`
- SYMBOL: `KMAE_Settings::youtube_id_from_url`
- PURPOSE: Extract a YouTube video ID from common URL formats.
- CALLED BY: `KMAE_Settings::sanitize_youtube_url`, `KMAE_Settings::seller_video_id`, `KMAE_Settings::buyer_video_id`
- CALLS: `KMAE_Settings::is_youtube_id`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::is_youtube_id`
- FILE: `includes/class-kmae-settings.php:151-152`
- SYMBOL: `KMAE_Settings::is_youtube_id`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Settings::youtube_id_from_url`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::auto_moderation_enabled`
- FILE: `includes/class-kmae-settings.php:155-156`
- SYMBOL: `KMAE_Settings::auto_moderation_enabled`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: `option:kmae_auto_moderation`
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::seller_video_url`
- FILE: `includes/class-kmae-settings.php:159-160`
- SYMBOL: `KMAE_Settings::seller_video_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Settings::seller_video_id`
- CALLS: —
- READS: `option:kmae_seller_video_url`
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::buyer_video_url`
- FILE: `includes/class-kmae-settings.php:163-164`
- SYMBOL: `KMAE_Settings::buyer_video_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Settings::buyer_video_id`
- CALLS: —
- READS: `option:kmae_buyer_video_url`
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::contact_form_email`
- FILE: `includes/class-kmae-settings.php:172-177`
- SYMBOL: `KMAE_Settings::contact_form_email`
- PURPOSE: Inbox for the public contact form.
- CALLED BY: `KMAE_Public::admin_contact_form`
- CALLS: —
- READS: `option:kmae_contact_form_email`
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::seller_video_id`
- FILE: `includes/class-kmae-settings.php:180-181`
- SYMBOL: `KMAE_Settings::seller_video_id`
- PURPOSE: Inbox for the public contact form.
- CALLED BY: `KMAE_Shortcodes::how_it_works`
- CALLS: `KMAE_Settings::youtube_id_from_url`, `KMAE_Settings::seller_video_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Settings::buyer_video_id`
- FILE: `includes/class-kmae-settings.php:184-185`
- SYMBOL: `KMAE_Settings::buyer_video_id`
- PURPOSE: Inbox for the public contact form.
- CALLED BY: `KMAE_Shortcodes::how_it_works`
- CALLS: `KMAE_Settings::youtube_id_from_url`, `KMAE_Settings::buyer_video_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `GOOGLE-AUTH`, `ADMIN`, `CONTACT-FORM`

## `KMAE_Taxonomies::__construct`
- FILE: `includes/class-kmae-taxonomies.php:14-16`
- SYMBOL: `KMAE_Taxonomies::__construct`
- PURPOSE: Taxonomies: category + accepted offer types on listing.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Taxonomies::register`
- FILE: `includes/class-kmae-taxonomies.php:19-20`
- SYMBOL: `KMAE_Taxonomies::register`
- PURPOSE: Taxonomies: category + accepted offer types on listing.
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Taxonomies::register_taxonomy`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Taxonomies::register_taxonomy`
- FILE: `includes/class-kmae-taxonomies.php:23-38`
- SYMBOL: `KMAE_Taxonomies::register_taxonomy`
- PURPOSE: Taxonomies: category + accepted offer types on listing.
- CALLED BY: `KMAE_Taxonomies::register`, `KMAE_Activator::activate`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Taxonomies::maybe_seed_categories`
- FILE: `includes/class-kmae-taxonomies.php:44-59`
- SYMBOL: `KMAE_Taxonomies::maybe_seed_categories`
- PURPOSE: Ensure default categories exist (activation + fallback on init).
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Taxonomies::seed_categories`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Taxonomies::seed_categories`
- FILE: `includes/class-kmae-taxonomies.php:62-80`
- SYMBOL: `KMAE_Taxonomies::seed_categories`
- PURPOSE: Ensure default categories exist (activation + fallback on init).
- CALLED BY: `KMAE_Launch::get_available_categories`, `KMAE_Taxonomies::maybe_seed_categories`, `KMAE_Activator::activate`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Taxonomies::offer_type_labels`
- FILE: `includes/class-kmae-taxonomies.php:86-89`
- SYMBOL: `KMAE_Taxonomies::offer_type_labels`
- PURPOSE: Get human-readable offer type labels.
- CALLED BY: `KMAE_Admin::listing_column_content`, `KMAE_Meta::render_meta_box`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Taxonomies::default_offer_types`
- FILE: `includes/class-kmae-taxonomies.php:97-98`
- SYMBOL: `KMAE_Taxonomies::default_offer_types`
- PURPOSE: Default accepted offer types when the seller form no longer collects them.
- CALLED BY: `KMAE_Seller::save_from_post`, `KMAE_Listings::save_meta_from_post`, `KMAE_Meta::save_listing_meta`, `KMAE_Meta::get_offer_types`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::is_vehicle_launch`
- FILE: `includes/class-kmae-vehicle.php:35-36`
- SYMBOL: `KMAE_Vehicle::is_vehicle_launch`
- PURPOSE: Legfeljebb ennyi jellemzőt lehet kiválasztani.
- CALLED BY: `KMAE_Admin::listing_columns`, `KMAE_Launch::wizard_total_steps`, `KMAE_Vehicle::save_from_post`, `KMAE_Vehicle::get_display_rows`, `KMAE_Moderation::validate`, `KMAE_Browse::filter_by_wizard`, `KMAE_Public::enqueue`, `KMAE_Shortcodes::browse`
- CALLS: `KMAE_Launch::is_single_category`, `KMAE_Launch::category_slug`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::fuel_labels`
- FILE: `includes/class-kmae-vehicle.php:39-47`
- SYMBOL: `KMAE_Vehicle::fuel_labels`
- PURPOSE: Legfeljebb ennyi jellemzőt lehet kiválasztani.
- CALLED BY: `KMAE_Vehicle::save_from_post`, `KMAE_Vehicle::get_display_rows`, `KMAE_Vehicle::get_swipe_subtitle`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::transmission_labels`
- FILE: `includes/class-kmae-vehicle.php:50-54`
- SYMBOL: `KMAE_Vehicle::transmission_labels`
- PURPOSE: Legfeljebb ennyi jellemzőt lehet kiválasztani.
- CALLED BY: `KMAE_Vehicle::save_from_post`, `KMAE_Vehicle::get_display_rows`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::body_labels`
- FILE: `includes/class-kmae-vehicle.php:57-62`
- SYMBOL: `KMAE_Vehicle::body_labels`
- PURPOSE: Legfeljebb ennyi jellemzőt lehet kiválasztani.
- CALLED BY: `KMAE_Vehicle::get_display_rows`
- CALLS: `KMAE_Vehicle::body_types`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::body_types`
- FILE: `includes/class-kmae-vehicle.php:70-90`
- SYMBOL: `KMAE_Vehicle::body_types`
- PURPOSE: All body types for browse wizard and forms.
- CALLED BY: `KMAE_Vehicle::body_labels`, `KMAE_Vehicle::save_from_post`, `KMAE_Browse::parse_bodies_param`, `KMAE_Browse::bodies_label`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::browse_body_cards`
- FILE: `includes/class-kmae-vehicle.php:99-107`
- SYMBOL: `KMAE_Vehicle::browse_body_cards`
- PURPOSE: A keresés kivitel-lépésének kártyái: ugyanazok a kiviteltípusok és képek, mint a hirdetésfeladás wizardjában (listing-wizard.js, BODY_CARD_PHOTOS). A van, cabrio, mpv, pickup és offroad slugok a body_types listában is ezek.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::browse_mode_labels`
- FILE: `includes/class-kmae-vehicle.php:110-114`
- SYMBOL: `KMAE_Vehicle::browse_mode_labels`
- PURPOSE: A keresés kivitel-lépésének kártyái: ugyanazok a kiviteltípusok és képek, mint a hirdetésfeladás wizardjában (listing-wizard.js, BODY_CARD_PHOTOS). A van, cabrio, mpv, pickup és offroad slugok a body_types listában is ezek.
- CALLED BY: `KMAE_Browse::wizard_summary_label`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_vehicle_value`
- FILE: `includes/class-kmae-vehicle.php:117-118`
- SYMBOL: `KMAE_Vehicle::get_vehicle_value`
- PURPOSE: A keresés kivitel-lépésének kártyái: ugyanazok a kiviteltípusok és képek, mint a hirdetésfeladás wizardjában (listing-wizard.js, BODY_CARD_PHOTOS). A van, cabrio, mpv, pickup és offroad slugok a body_types listában is ezek.
- CALLED BY: `KMAE_Vehicle::get_specs`, `KMAE_Browse::listing_matches_wizard`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::format_value`
- FILE: `includes/class-kmae-vehicle.php:121-125`
- SYMBOL: `KMAE_Vehicle::format_value`
- PURPOSE: A keresés kivitel-lépésének kártyái: ugyanazok a kiviteltípusok és képek, mint a hirdetésfeladás wizardjában (listing-wizard.js, BODY_CARD_PHOTOS). A van, cabrio, mpv, pickup és offroad slugok a body_types listában is ezek.
- CALLED BY: `KMAE_Vehicle::get_display_rows`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::traits`
- FILE: `includes/class-kmae-vehicle.php:134-146`
- SYMBOL: `KMAE_Vehicle::traits`
- PURPOSE: Az autó jellemzői. Legfeljebb három választható belőlük — ezek nem mérhető adatok, hanem az eladó saját kiemelései.
- CALLED BY: `KMAE_Vehicle::get_traits`, `KMAE_Vehicle::get_trait_labels`, `KMAE_Vehicle::save_from_post`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_traits`
- FILE: `includes/class-kmae-vehicle.php:153-158`
- SYMBOL: `KMAE_Vehicle::get_traits`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Vehicle::get_trait_labels`
- CALLS: `KMAE_Vehicle::traits`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_trait_labels`
- FILE: `includes/class-kmae-vehicle.php:165-171`
- SYMBOL: `KMAE_Vehicle::get_trait_labels`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Vehicle::get_display_rows`
- CALLS: `KMAE_Vehicle::traits`, `KMAE_Vehicle::get_traits`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::format_owners`
- FILE: `includes/class-kmae-vehicle.php:184-192`
- SYMBOL: `KMAE_Vehicle::format_owners`
- PURPOSE: A tulajdonosok számának felirata. A „4" a feladásnál a „Negyedik vagy több" kártyát jelenti, ezért nem írhatjuk ki pontos számként — az mást állítana, mint amit választott.
- CALLED BY: `KMAE_Vehicle::get_display_rows`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::condition_labels`
- FILE: `includes/class-kmae-vehicle.php:195-201`
- SYMBOL: `KMAE_Vehicle::condition_labels`
- PURPOSE: A tulajdonosok számának felirata. A „4" a feladásnál a „Negyedik vagy több" kártyát jelenti, ezért nem írhatjuk ki pontos számként — az mást állítana, mint amit választott.
- CALLED BY: `KMAE_Vehicle::save_from_post`, `KMAE_Vehicle::get_display_rows`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::catalog`
- FILE: `includes/class-kmae-vehicle.php:209-242`
- SYMBOL: `KMAE_Vehicle::catalog`
- PURPOSE: Brand => models map from the bundled catalog.
- CALLED BY: `KMAE_Vehicle::brands`, `KMAE_Vehicle::models_for`, `KMAE_Vehicle::catalog_payload`, `KMAE_Vehicle::resolve_brand_key`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::brands`
- FILE: `includes/class-kmae-vehicle.php:250-253`
- SYMBOL: `KMAE_Vehicle::brands`
- PURPOSE: Sorted brand names from the catalog.
- CALLED BY: `KMAE_Vehicle::catalog_payload`
- CALLS: `KMAE_Vehicle::catalog`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::models_for`
- FILE: `includes/class-kmae-vehicle.php:262-267`
- SYMBOL: `KMAE_Vehicle::models_for`
- PURPOSE: Models for a catalog brand (case-insensitive brand match).
- CALLED BY: `KMAE_Vehicle::is_known_model`
- CALLS: `KMAE_Vehicle::resolve_brand_key`, `KMAE_Vehicle::catalog`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::is_known_brand`
- FILE: `includes/class-kmae-vehicle.php:274-275`
- SYMBOL: `KMAE_Vehicle::is_known_brand`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Vehicle::resolve_brand_key`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::is_known_model`
- FILE: `includes/class-kmae-vehicle.php:283-293`
- SYMBOL: `KMAE_Vehicle::is_known_model`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Vehicle::models_for`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::brand_logo_slugs`
- FILE: `includes/class-kmae-vehicle.php:309-373`
- SYMBOL: `KMAE_Vehicle::brand_logo_slugs`
- PURPOSE: Catalog brand => emblem file slug. The slug is the uploaded file name, so a few entries intentionally differ from the brand name (rolls-royes, daevoo) — those are the real file names.
- CALLED BY: `KMAE_Vehicle::brand_logos`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::brand_logos`
- FILE: `includes/class-kmae-vehicle.php:381-386`
- SYMBOL: `KMAE_Vehicle::brand_logos`
- PURPOSE: Brand => emblem URL, for catalog brands that have one.
- CALLED BY: `KMAE_Vehicle::catalog_payload`
- CALLS: `KMAE_Vehicle::brand_logo_slugs`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::popular_brands`
- FILE: `includes/class-kmae-vehicle.php:394-395`
- SYMBOL: `KMAE_Vehicle::popular_brands`
- PURPOSE: Brands offered before the user types anything.
- CALLED BY: `KMAE_Vehicle::catalog_payload`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::catalog_payload`
- FILE: `includes/class-kmae-vehicle.php:403-409`
- SYMBOL: `KMAE_Vehicle::catalog_payload`
- PURPOSE: Catalog payload for the frontend picker.
- CALLED BY: `KMAE_Public::enqueue_vehicle_catalog`
- CALLS: `KMAE_Vehicle::brands`, `KMAE_Vehicle::catalog`, `KMAE_Vehicle::brand_logos`, `KMAE_Vehicle::popular_brands`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::resolve_brand_key`
- FILE: `includes/class-kmae-vehicle.php:416-426`
- SYMBOL: `KMAE_Vehicle::resolve_brand_key`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Vehicle::models_for`, `KMAE_Vehicle::is_known_brand`
- CALLS: `KMAE_Vehicle::catalog`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_specs`
- FILE: `includes/class-kmae-vehicle.php:429-446`
- SYMBOL: `KMAE_Vehicle::get_specs`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Vehicle::get_display_rows`, `KMAE_Vehicle::get_display_title`, `KMAE_Vehicle::get_swipe_subtitle`
- CALLS: `KMAE_Vehicle::get_vehicle_value`
- READS: `postmeta:_kmae_condition`, `postmeta:_kmae_condition_note`
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::posted_mot`
- FILE: `includes/class-kmae-vehicle.php:454-475`
- SYMBOL: `KMAE_Vehicle::posted_mot`
- PURPOSE: Műszaki érvényessége a beküldött mezőkből: "ÉÉÉÉ-HH", vagy üres.
- CALLED BY: `KMAE_Vehicle::save_from_post`, `KMAE_Vehicle::parse_post_data`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`
- POST KEYS: `kmae_vehicle_mot_day`, `kmae_vehicle_mot_has`, `kmae_vehicle_mot_month`, `kmae_vehicle_mot_year`

## `KMAE_Vehicle::kw_to_horsepower`
- FILE: `includes/class-kmae-vehicle.php:478-479`
- SYMBOL: `KMAE_Vehicle::kw_to_horsepower`
- PURPOSE: Műszaki érvényessége a beküldött mezőkből: "ÉÉÉÉ-HH", vagy üres.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::horsepower_to_kw`
- FILE: `includes/class-kmae-vehicle.php:482-483`
- SYMBOL: `KMAE_Vehicle::horsepower_to_kw`
- PURPOSE: Műszaki érvényessége a beküldött mezőkből: "ÉÉÉÉ-HH", vagy üres.
- CALLED BY: `KMAE_Vehicle::posted_power_kw`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::posted_power_kw`
- FILE: `includes/class-kmae-vehicle.php:486-491`
- SYMBOL: `KMAE_Vehicle::posted_power_kw`
- PURPOSE: Műszaki érvényessége a beküldött mezőkből: "ÉÉÉÉ-HH", vagy üres.
- CALLED BY: `KMAE_Vehicle::save_from_post`, `KMAE_Vehicle::parse_post_data`
- CALLS: `KMAE_Vehicle::horsepower_to_kw`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`
- POST KEYS: `kmae_vehicle_power_hp`, `kmae_vehicle_power_kw`

## `KMAE_Vehicle::save_from_post`
- FILE: `includes/class-kmae-vehicle.php:494-555`
- SYMBOL: `KMAE_Vehicle::save_from_post`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Listings::save_meta_from_post`, `KMAE_Meta::save_listing_meta`
- CALLS: `KMAE_Launch::get_launch_category_id`, `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Vehicle::posted_power_kw`, `KMAE_Vehicle::posted_mot`, `KMAE_Vehicle::fuel_labels`, `KMAE_Vehicle::transmission_labels`, `KMAE_Vehicle::body_types`, `KMAE_Vehicle::traits`, `KMAE_Vehicle::condition_labels`
- READS: —
- WRITES: `postmeta:_kmae_condition`, `postmeta:_kmae_condition_note`
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`
- POST KEYS: `kmae_condition`, `kmae_condition_note`, `kmae_vehicle_body`, `kmae_vehicle_brand`, `kmae_vehicle_color`, `kmae_vehicle_engine_cc`, `kmae_vehicle_fuel`, `kmae_vehicle_mileage`, `kmae_vehicle_model`, `kmae_vehicle_owners`, `kmae_vehicle_traits`, `kmae_vehicle_transmission`, `kmae_vehicle_value`, `kmae_vehicle_year`

## `KMAE_Vehicle::get_missing_required`
- FILE: `includes/class-kmae-vehicle.php:561-604`
- SYMBOL: `KMAE_Vehicle::get_missing_required`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Moderation::validate`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::build_title_from_specs`
- FILE: `includes/class-kmae-vehicle.php:607-615`
- SYMBOL: `KMAE_Vehicle::build_title_from_specs`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::format_mileage`
- FILE: `includes/class-kmae-vehicle.php:618-622`
- SYMBOL: `KMAE_Vehicle::format_mileage`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Vehicle::get_display_rows`, `KMAE_Vehicle::get_swipe_subtitle`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_display_rows`
- FILE: `includes/class-kmae-vehicle.php:630-665`
- SYMBOL: `KMAE_Vehicle::get_display_rows`
- PURPOSE: Display rows for templates.
- CALLED BY: —
- CALLS: `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::format_mileage`, `KMAE_Vehicle::fuel_labels`, `KMAE_Vehicle::transmission_labels`, `KMAE_Vehicle::body_labels`, `KMAE_Vehicle::format_owners`, `KMAE_Vehicle::format_value`, `KMAE_Vehicle::condition_labels`, `KMAE_Vehicle::get_trait_labels`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_display_title`
- FILE: `includes/class-kmae-vehicle.php:668-676`
- SYMBOL: `KMAE_Vehicle::get_display_title`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: —
- CALLS: `KMAE_Vehicle::get_specs`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::get_swipe_subtitle`
- FILE: `includes/class-kmae-vehicle.php:679-688`
- SYMBOL: `KMAE_Vehicle::get_swipe_subtitle`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Admin::listing_column_content`, `KMAE_Dashboard::format_listing_row`
- CALLS: `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::format_mileage`, `KMAE_Vehicle::fuel_labels`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`

## `KMAE_Vehicle::parse_post_data`
- FILE: `includes/class-kmae-vehicle.php:691-710`
- SYMBOL: `KMAE_Vehicle::parse_post_data`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::process_create_listing`
- CALLS: `KMAE_Vehicle::posted_power_kw`, `KMAE_Vehicle::posted_mot`
- READS: —
- WRITES: —
- RELATED FEATURE: `VEHICLE`, `MODERATION`, `TAXONOMY-LAUNCH`
- POST KEYS: `kmae_condition`, `kmae_condition_note`, `kmae_description`, `kmae_title`, `kmae_vehicle_body`, `kmae_vehicle_brand`, `kmae_vehicle_color`, `kmae_vehicle_engine_cc`, `kmae_vehicle_fuel`, `kmae_vehicle_mileage`, `kmae_vehicle_model`, `kmae_vehicle_owners`, `kmae_vehicle_transmission`, `kmae_vehicle_value`, `kmae_vehicle_year`

## `kmae_run`
- FILE: `ki-mit-adna-erte.php:54-56`
- SYMBOL: `kmae_run`
- PURPOSE: Legacy codes kept only so previously stored offers remain readable.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `TAXONOMY-LAUNCH`

## `KMAE_Public::__construct`
- FILE: `public/class-kmae-public.php:14-40`
- SYMBOL: `KMAE_Public::__construct`
- PURPOSE: Frontend assets and form handlers.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::enqueue`
- FILE: `public/class-kmae-public.php:43-171`
- SYMBOL: `KMAE_Public::enqueue`
- PURPOSE: Frontend assets and form handlers.
- CALLED BY: `WP action `wp_enqueue_scripts``
- CALLS: `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Photo_Motor::client_config`, `KMAE_Public::is_register_page`, `KMAE_Public::should_enqueue_public_assets`, `KMAE_Public::is_create_listing_page`, `KMAE_Public::enqueue_vehicle_catalog`, `KMAE_Public::enqueue_listing_wizard`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::is_create_listing_page`
- FILE: `public/class-kmae-public.php:179-185`
- SYMBOL: `KMAE_Public::is_create_listing_page`
- PURPOSE: Create / edit listing form page (logged-in users only).
- CALLED BY: `KMAE_Public::enqueue`, `KMAE_Public::enqueue_vehicle_catalog`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::enqueue_vehicle_catalog`
- FILE: `public/class-kmae-public.php:191-203`
- SYMBOL: `KMAE_Public::enqueue_vehicle_catalog`
- PURPOSE: Brand / model catalog picker for create and edit listing forms.
- CALLED BY: `KMAE_Public::enqueue`
- CALLS: `KMAE_Vehicle::catalog_payload`, `KMAE_Public::is_create_listing_page`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::enqueue_listing_wizard`
- FILE: `public/class-kmae-public.php:209-265`
- SYMBOL: `KMAE_Public::enqueue_listing_wizard`
- PURPOSE: Guided create wizard: engine plus its phase and validation config.
- CALLED BY: `KMAE_Public::enqueue`
- CALLS: `KMAE_Listing_Wizard::phases`, `KMAE_Listing_Drafts::get_user_draft`, `KMAE_Listing_Drafts::get_latest_draft`, `KMAE_Listing_Drafts::to_client`, `KMAE_Moderation::is_enabled`, `KMAE_Listing_Wizard::description_progress_messages`, `KMAE_Listing_Wizard::assistant_intro`, `KMAE_Listing_Wizard::assistant_reactions`, `KMAE_Listing_Wizard::assistant_publish`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- GET KEYS: `kmae_draft`, `kmae_edit`, `kmae_mode`

## `KMAE_Public::should_enqueue_public_assets`
- FILE: `public/class-kmae-public.php:273-282`
- SYMBOL: `KMAE_Public::should_enqueue_public_assets`
- PURPOSE: Skip plugin public assets on Simple Mode guest marketing shell (theme owns layout).
- CALLED BY: `KMAE_Public::enqueue`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::is_register_page`
- FILE: `public/class-kmae-public.php:285-292`
- SYMBOL: `KMAE_Public::is_register_page`
- PURPOSE: Skip plugin public assets on Simple Mode guest marketing shell (theme owns layout).
- CALLED BY: `KMAE_Public::enqueue`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::block_nonpublic_listings`
- FILE: `public/class-kmae-public.php:298-315`
- SYMBOL: `KMAE_Public::block_nonpublic_listings`
- PURPOSE: Hide awaiting-photos listings from non-owners.
- CALLED BY: `WP action `template_redirect``
- CALLS: `KMAE_Meta::get_status`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::ajax_browse_pass`
- FILE: `public/class-kmae-public.php:318-331`
- SYMBOL: `KMAE_Public::ajax_browse_pass`
- PURPOSE: Hide awaiting-photos listings from non-owners.
- CALLED BY: `WP action `wp_ajax_kmae_browse_pass``
- CALLS: `KMAE_Browse::record_pass`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `listing_id`

## `KMAE_Public::ajax_poll_page`
- FILE: `public/class-kmae-public.php:334-358`
- SYMBOL: `KMAE_Public::ajax_poll_page`
- PURPOSE: Hide awaiting-photos listings from non-owners.
- CALLED BY: `WP action `wp_ajax_kmae_poll_page``
- CALLS: `KMAE_Live_Page::get_fingerprint`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `context`, `listing_id`, `offer_id`

## `KMAE_Public::handle_forms`
- FILE: `public/class-kmae-public.php:380-433`
- SYMBOL: `KMAE_Public::handle_forms`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `init``
- CALLS: `KMAE_Auth::should_gate_for_verification`, `KMAE_Public::process_create_listing`, `KMAE_Public::process_start_conversation`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_action`

## `KMAE_Public::verify_nonce`
- FILE: `public/class-kmae-public.php:436-437`
- SYMBOL: `KMAE_Public::verify_nonce`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::process_create_listing`, `KMAE_Public::process_start_conversation`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::verify_nonce_action`
- FILE: `public/class-kmae-public.php:440-441`
- SYMBOL: `KMAE_Public::verify_nonce_action`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_save_profile`, `KMAE_Public::admin_save_settings`, `KMAE_Public::admin_change_email`, `KMAE_Public::admin_change_password`, `KMAE_Public::admin_logout_everywhere`, `KMAE_Public::admin_continue_shopping`, `KMAE_Public::admin_login`, `KMAE_Public::admin_register`, `KMAE_Public::admin_lost_password`, `KMAE_Public::admin_reset_password`, `KMAE_Public::admin_contact_form`, `KMAE_Public::admin_update_listing`, `KMAE_Public::admin_delete_listing`, `KMAE_Public::admin_reactivate_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::get_profile_redirect_url`
- FILE: `public/class-kmae-public.php:444-448`
- SYMBOL: `KMAE_Public::get_profile_redirect_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::redirect_profile`, `KMAE_Public::admin_save_profile`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_redirect`

## `KMAE_Public::redirect_profile`
- FILE: `public/class-kmae-public.php:451-454`
- SYMBOL: `KMAE_Public::redirect_profile`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_save_profile`, `KMAE_Public::admin_continue_shopping`
- CALLS: `KMAE_Public::get_profile_redirect_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::admin_save_profile`
- FILE: `public/class-kmae-public.php:457-508`
- SYMBOL: `KMAE_Public::admin_save_profile`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_save_profile``
- CALLS: `KMAE_Auth::normalize_phone`, `KMAE_Auth::sync_phone`, `KMAE_Auth::is_company`, `KMAE_Company::save_profile_fields`, `KMAE_Public::get_profile_redirect_url`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::redirect_profile`
- READS: —
- WRITES: `usermeta:kmae_phone`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_company_address`, `kmae_company_city`, `kmae_company_county`, `kmae_company_name`, `kmae_display_name`, `kmae_phone`

## `KMAE_Public::get_settings_redirect_url`
- FILE: `public/class-kmae-public.php:515-519`
- SYMBOL: `KMAE_Public::get_settings_redirect_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::redirect_settings`, `KMAE_Public::require_settings_login`
- CALLS: `KMAE_Pages::url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_redirect`

## `KMAE_Public::redirect_settings`
- FILE: `public/class-kmae-public.php:526-529`
- SYMBOL: `KMAE_Public::redirect_settings`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_save_settings`, `KMAE_Public::admin_change_email`, `KMAE_Public::admin_change_password`, `KMAE_Public::admin_logout_everywhere`
- CALLS: `KMAE_Public::get_settings_redirect_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::require_settings_login`
- FILE: `public/class-kmae-public.php:532-536`
- SYMBOL: `KMAE_Public::require_settings_login`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_save_settings`, `KMAE_Public::admin_change_email`, `KMAE_Public::admin_change_password`, `KMAE_Public::admin_logout_everywhere`
- CALLS: `KMAE_Public::get_settings_redirect_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::admin_save_settings`
- FILE: `public/class-kmae-public.php:539-552`
- SYMBOL: `KMAE_Public::admin_save_settings`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_save_settings``
- CALLS: `KMAE_Auth::save_email_prefs`, `KMAE_Public::require_settings_login`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::redirect_settings`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_email_news`, `kmae_email_offers`

## `KMAE_Public::admin_change_email`
- FILE: `public/class-kmae-public.php:555-594`
- SYMBOL: `KMAE_Public::admin_change_email`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_change_email``
- CALLS: `KMAE_Public::require_settings_login`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::redirect_settings`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_current_password`, `kmae_new_email`

## `KMAE_Public::admin_change_password`
- FILE: `public/class-kmae-public.php:597-628`
- SYMBOL: `KMAE_Public::admin_change_password`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_change_password``
- CALLS: `KMAE_Public::require_settings_login`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::redirect_settings`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_current_password`, `kmae_new_password`, `kmae_new_password_repeat`

## `KMAE_Public::admin_logout_everywhere`
- FILE: `public/class-kmae-public.php:631-641`
- SYMBOL: `KMAE_Public::admin_logout_everywhere`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_logout_everywhere``
- CALLS: `KMAE_Public::require_settings_login`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::redirect_settings`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::admin_login`
- FILE: `public/class-kmae-public.php:706-729`
- SYMBOL: `KMAE_Public::admin_login`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_login``, `WP action `admin_post_nopriv_kmae_login``
- CALLS: `KMAE_Auth::login_url`, `KMAE_Auth::login`, `KMAE_Public::verify_nonce_action`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_login_email`, `kmae_login_password`, `kmae_redirect`, `kmae_remember`

## `KMAE_Public::client_ip`
- FILE: `public/class-kmae-public.php:737-739`
- SYMBOL: `KMAE_Public::client_ip`
- PURPOSE: A regisztráló IP-címe. Csak korlátozáshoz használjuk, nem tároljuk.
- CALLED BY: `KMAE_Public::register_spam_check`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::register_spam_check`
- FILE: `public/class-kmae-public.php:750-778`
- SYMBOL: `KMAE_Public::register_spam_check`
- PURPOSE: Botszűrés a regisztrációnál. Három egymástól független ellenőrzés.
- CALLED BY: `KMAE_Public::admin_register`
- CALLS: `KMAE_Public::client_ip`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_form_time`, `kmae_form_token`, `kmae_website`

## `KMAE_Public::admin_register`
- FILE: `public/class-kmae-public.php:781-822`
- SYMBOL: `KMAE_Public::admin_register`
- PURPOSE: Botszűrés a regisztrációnál. Három egymástól független ellenőrzés.
- CALLED BY: `WP action `admin_post_kmae_register``, `WP action `admin_post_nopriv_kmae_register``
- CALLS: `KMAE_Auth::register_url`, `KMAE_Auth::register`, `KMAE_Auth::login_url`, `KMAE_Public::verify_nonce_action`, `KMAE_Public::register_spam_check`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_account_type`, `kmae_company_address`, `kmae_company_city`, `kmae_company_county`, `kmae_company_name`, `kmae_display_name`, `kmae_email`, `kmae_email_news`, `kmae_email_offers`, `kmae_password`, `kmae_password_confirm`, `kmae_phone`, `kmae_terms`

## `KMAE_Public::admin_lost_password`
- FILE: `public/class-kmae-public.php:825-840`
- SYMBOL: `KMAE_Public::admin_lost_password`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_lost_password``, `WP action `admin_post_nopriv_kmae_lost_password``
- CALLS: `KMAE_Auth::lost_password_url`, `KMAE_Auth::request_password_reset`, `KMAE_Auth::login_url`, `KMAE_Public::verify_nonce_action`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_email`

## `KMAE_Public::admin_reset_password`
- FILE: `public/class-kmae-public.php:843-862`
- SYMBOL: `KMAE_Public::admin_reset_password`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_reset_password``, `WP action `admin_post_nopriv_kmae_reset_password``
- CALLS: `KMAE_Auth::lost_password_url`, `KMAE_Auth::reset_password_with_key`, `KMAE_Auth::reset_password_url`, `KMAE_Auth::login_url`, `KMAE_Public::verify_nonce_action`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_password`, `kmae_password_confirm`, `kmae_reset_key`, `kmae_reset_login`

## `KMAE_Public::admin_contact_form`
- FILE: `public/class-kmae-public.php:868-968`
- SYMBOL: `KMAE_Public::admin_contact_form`
- PURPOSE: Public contact form → email inbox.
- CALLED BY: `WP action `admin_post_kmae_contact_form``, `WP action `admin_post_nopriv_kmae_contact_form``
- CALLS: `KMAE_Pages::url`, `KMAE_Settings::contact_form_email`, `KMAE_Brand::send_mail`, `KMAE_Public::verify_nonce_action`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_contact_email`, `kmae_contact_message`, `kmae_contact_name`, `kmae_contact_topic`, `kmae_website`

## `KMAE_Public::get_listings_redirect_url`
- FILE: `public/class-kmae-public.php:971-972`
- SYMBOL: `KMAE_Public::get_listings_redirect_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::admin_update_listing`, `KMAE_Public::admin_delete_listing`, `KMAE_Public::admin_reactivate_listing`
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`

## `KMAE_Public::admin_update_listing`
- FILE: `public/class-kmae-public.php:975-1096`
- SYMBOL: `KMAE_Public::admin_update_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_update_listing``
- CALLS: `KMAE_Listings::user_owns_listing`, `KMAE_Listings::can_edit_listing`, `KMAE_Auth::is_company`, `KMAE_Company::get_county`, `KMAE_Company::get_city`, `KMAE_Counties::validate_listing_location`, `KMAE_Meta::get_status`, `KMAE_Moderation::is_enabled`, `KMAE_Vehicle::parse_post_data`, `KMAE_Query::get_gallery_ids`, `KMAE_Moderation::count_uploaded_images`, `KMAE_Moderation::validate`, `KMAE_Moderation::stash_errors_for_user`, `KMAE_Listings::save_meta_from_post`, `KMAE_Photo_Motor::is_enabled` +9 more
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_city`, `kmae_county`, `kmae_description`, `kmae_listing_id`, `kmae_redirect`, `kmae_title`

## `KMAE_Public::admin_delete_listing`
- FILE: `public/class-kmae-public.php:1099-1124`
- SYMBOL: `KMAE_Public::admin_delete_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_delete_listing``
- CALLS: `KMAE_Listings::can_delete_listing`, `KMAE_Listings::delete_listing`, `KMAE_Public::get_listings_redirect_url`, `KMAE_Public::verify_nonce_action`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_listing_id`

## `KMAE_Public::admin_reactivate_listing`
- FILE: `public/class-kmae-public.php:1127-1147`
- SYMBOL: `KMAE_Public::admin_reactivate_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `WP action `admin_post_kmae_reactivate_listing``
- CALLS: `KMAE_Listings::reactivate_listing`, `KMAE_Public::get_listings_redirect_url`, `KMAE_Public::verify_nonce_action`
- READS: —
- WRITES: —
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_listing_id`

## `KMAE_Public::process_create_listing`
- FILE: `public/class-kmae-public.php:1150-1270`
- SYMBOL: `KMAE_Public::process_create_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Public::handle_forms`
- CALLS: `KMAE_Seller::can_create_listing`, `KMAE_Auth::is_company`, `KMAE_Company::get_county`, `KMAE_Company::get_city`, `KMAE_Counties::validate_listing_location`, `KMAE_Photo_Motor::is_enabled`, `KMAE_Vehicle::parse_post_data`, `KMAE_Moderation::count_uploaded_images`, `KMAE_Moderation::validate`, `KMAE_Moderation::stash_errors_for_user`, `KMAE_Photo_Motor::validate_request`, `KMAE_Listings::save_meta_from_post`, `KMAE_Listing_Drafts::discard_submitted`, `KMAE_Listings::handle_image_uploads`, `KMAE_Photo_Motor::count_required_filled` +2 more
- READS: —
- WRITES: `postmeta:_kmae_create_device`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`, `PROFILE-COMPANY-SETTINGS`, `LISTINGS`, `MODERATION`, `LIVE-NAV`, `CONTACT-FORM`
- POST KEYS: `kmae_city`, `kmae_county`, `kmae_create_device`, `kmae_description`, `kmae_price_mode`, `kmae_title`
- EMITS: `kmae_listing_published`

## `KMAE_Shortcodes::__construct`
- FILE: `public/class-kmae-shortcodes.php:14-39`
- SYMBOL: `KMAE_Shortcodes::__construct`
- PURPOSE: Frontend shortcodes.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::require_login`
- FILE: `public/class-kmae-shortcodes.php:42-57`
- SYMBOL: `KMAE_Shortcodes::require_login`
- PURPOSE: Frontend shortcodes.
- CALLED BY: `KMAE_Shortcodes::my_listings`, `KMAE_Shortcodes::profile`, `KMAE_Shortcodes::settings`, `KMAE_Shortcodes::notifications`, `KMAE_Shortcodes::messages`, `KMAE_Shortcodes::render_hub_section`
- CALLS: `KMAE_Auth::login_url`, `KMAE_Auth::register_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::login`
- FILE: `public/class-kmae-shortcodes.php:60-63`
- SYMBOL: `KMAE_Shortcodes::login`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_login]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::register`
- FILE: `public/class-kmae-shortcodes.php:66-69`
- SYMBOL: `KMAE_Shortcodes::register`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_register]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::hero`
- FILE: `public/class-kmae-shortcodes.php:72-75`
- SYMBOL: `KMAE_Shortcodes::hero`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::landing`, `shortcode `[kmae_hero]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::landing_features`
- FILE: `public/class-kmae-shortcodes.php:78-81`
- SYMBOL: `KMAE_Shortcodes::landing_features`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::landing`, `shortcode `[kmae_landing_features]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::landing_search`
- FILE: `public/class-kmae-shortcodes.php:84-87`
- SYMBOL: `KMAE_Shortcodes::landing_search`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::landing`, `shortcode `[kmae_landing_search]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::landing_popular`
- FILE: `public/class-kmae-shortcodes.php:90-93`
- SYMBOL: `KMAE_Shortcodes::landing_popular`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::landing`, `shortcode `[kmae_landing_popular]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::landing`
- FILE: `public/class-kmae-shortcodes.php:96-102`
- SYMBOL: `KMAE_Shortcodes::landing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_landing]``
- CALLS: `KMAE_Shortcodes::hero`, `KMAE_Shortcodes::landing_search`, `KMAE_Shortcodes::landing_popular`, `KMAE_Shortcodes::landing_features`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::create_listing`
- FILE: `public/class-kmae-shortcodes.php:105-138`
- SYMBOL: `KMAE_Shortcodes::create_listing`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_create_listing]``
- CALLS: `KMAE_Auth::should_gate_for_verification`, `KMAE_Listings::user_owns_listing`, `KMAE_Listings::can_edit_listing`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`
- GET KEYS: `kmae_edit`

## `KMAE_Shortcodes::my_listings`
- FILE: `public/class-kmae-shortcodes.php:141-167`
- SYMBOL: `KMAE_Shortcodes::my_listings`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_my_listings]``
- CALLS: `KMAE_Meta::get_status`, `KMAE_Shortcodes::require_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::browse`
- FILE: `public/class-kmae-shortcodes.php:170-258`
- SYMBOL: `KMAE_Shortcodes::browse`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_browse]``
- CALLS: `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Query::get_listings`, `KMAE_Counties::is_valid`, `KMAE_Launch::should_skip_category_wizard`, `KMAE_Launch::category_slug`, `KMAE_Shortcodes::get_browse_base_url`, `KMAE_Shortcodes::browse_vehicle_wizard`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`
- GET KEYS: `kategoria`, `megye`, `tipus`

## `KMAE_Shortcodes::browse_vehicle_wizard`
- FILE: `public/class-kmae-shortcodes.php:261-314`
- SYMBOL: `KMAE_Shortcodes::browse_vehicle_wizard`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::browse`
- CALLS: `KMAE_Browse::parse_wizard_params`, `KMAE_Counties::is_valid`, `KMAE_Browse::is_wizard_complete`, `KMAE_Browse::bodies_to_query_value`, `KMAE_Query::get_listings`, `KMAE_Browse::filter_by_wizard`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`
- GET KEYS: `step`

## `KMAE_Shortcodes::get_browse_base_url`
- FILE: `public/class-kmae-shortcodes.php:317-318`
- SYMBOL: `KMAE_Shortcodes::get_browse_base_url`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `KMAE_Shortcodes::browse`
- CALLS: `KMAE_Pages::browse_page_url`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::profile`
- FILE: `public/class-kmae-shortcodes.php:356-363`
- SYMBOL: `KMAE_Shortcodes::profile`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_profile]``
- CALLS: `KMAE_Shortcodes::require_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::how_it_works`
- FILE: `public/class-kmae-shortcodes.php:366-371`
- SYMBOL: `KMAE_Shortcodes::how_it_works`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_how_it_works]``
- CALLS: `KMAE_Settings::seller_video_id`, `KMAE_Settings::buyer_video_id`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::education`
- FILE: `public/class-kmae-shortcodes.php:374-377`
- SYMBOL: `KMAE_Shortcodes::education`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_education]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::settings`
- FILE: `public/class-kmae-shortcodes.php:380-387`
- SYMBOL: `KMAE_Shortcodes::settings`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_settings]``
- CALLS: `KMAE_Shortcodes::require_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::security`
- FILE: `public/class-kmae-shortcodes.php:390-393`
- SYMBOL: `KMAE_Shortcodes::security`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_security]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::faq`
- FILE: `public/class-kmae-shortcodes.php:396-399`
- SYMBOL: `KMAE_Shortcodes::faq`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_faq]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::contact`
- FILE: `public/class-kmae-shortcodes.php:402-405`
- SYMBOL: `KMAE_Shortcodes::contact`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_contact]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::terms`
- FILE: `public/class-kmae-shortcodes.php:408-411`
- SYMBOL: `KMAE_Shortcodes::terms`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_terms]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::privacy`
- FILE: `public/class-kmae-shortcodes.php:414-417`
- SYMBOL: `KMAE_Shortcodes::privacy`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_privacy]``
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::notifications`
- FILE: `public/class-kmae-shortcodes.php:420-427`
- SYMBOL: `KMAE_Shortcodes::notifications`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_notifications]``
- CALLS: `KMAE_Shortcodes::require_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::messages`
- FILE: `public/class-kmae-shortcodes.php:430-437`
- SYMBOL: `KMAE_Shortcodes::messages`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_messages]``
- CALLS: `KMAE_Shortcodes::require_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::hub_sell`
- FILE: `public/class-kmae-shortcodes.php:440-441`
- SYMBOL: `KMAE_Shortcodes::hub_sell`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_hub_sell]``
- CALLS: `KMAE_Shortcodes::render_hub_section`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::hub_buy`
- FILE: `public/class-kmae-shortcodes.php:444-445`
- SYMBOL: `KMAE_Shortcodes::hub_buy`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_hub_buy]``
- CALLS: `KMAE_Shortcodes::render_hub_section`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::hub_stats`
- FILE: `public/class-kmae-shortcodes.php:448-449`
- SYMBOL: `KMAE_Shortcodes::hub_stats`
- PURPOSE: No dedicated source docblock; inspect the referenced implementation range.
- CALLED BY: `shortcode `[kmae_hub_stats]``
- CALLS: `KMAE_Shortcodes::render_hub_section`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `KMAE_Shortcodes::render_hub_section`
- FILE: `public/class-kmae-shortcodes.php:458-564`
- SYMBOL: `KMAE_Shortcodes::render_hub_section`
- PURPOSE: Hub submenu pages (Eladni / Venni / Statisztikák).
- CALLED BY: `KMAE_Shortcodes::hub_sell`, `KMAE_Shortcodes::hub_buy`, `KMAE_Shortcodes::hub_stats`
- CALLS: `KMAE_Dashboard::get_data`, `KMAE_Shortcodes::require_login`
- READS: —
- WRITES: —
- RELATED FEATURE: `PAGES-ROUTING`, `BROWSE-SEARCH`, `DASHBOARD-HUBS`

## `kmae_how_icon`
- FILE: `templates/how-it-works.php:102-124`
- SYMBOL: `kmae_how_icon`
- PURPOSE: Inline step / trust icons for the how-it-works page.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `kmae_how_render_flow`
- FILE: `templates/how-it-works.php:140-203`
- SYMBOL: `kmae_how_render_flow`
- PURPOSE: Render a role flow row.
- CALLED BY: —
- CALLS: —
- READS: —
- WRITES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `KMAE_Conversations::get_inbox_for_user`
- FILE: `includes/class-kmae-conversations.php`
- SYMBOL: `KMAE_Conversations::get_inbox_for_user`
- PURPOSE: Üzenetek list rows: conversations of the viewer with at least one message (deleted listing kept: `listing_gone` flag, title from `listing_title` snapshot); last message preview, unread flag, other party name, thread URL.
- CALLED BY: `KMAE_Shortcodes::messages`
- CALLS: `KMAE_Conversations::get_for_user`, `KMAE_Conversations::get_messages`, `KMAE_Database::get_conversation_alert_state`, `KMAE_Pages::conversation_thread_url`
- READS: `table:conversations`, `table:conversation_messages`
- WRITES: —
- RELATED FEATURE: `CHAT`

## `KMAE_Chat_Offers::finalize_sales` / `count_sold_for_seller` / `sold_label`
- FILE: `includes/class-kmae-chat-offers.php`
- SYMBOL: `KMAE_Chat_Offers::init`, `KMAE_Chat_Offers::finalize_sales`, `KMAE_Chat_Offers::close_sale`, `KMAE_Chat_Offers::close_listing` (private), `KMAE_Chat_Offers::count_sold_for_seller`, `KMAE_Chat_Offers::sold_label`, `KMAE_Chat_Offers::STATUS_SOLD`, `KMAE_Chat_Offers::STATUS_LAPSED`, `KMAE_Chat_Offers::SOLD_AFTER_DAYS`
- PURPOSE: [Lezárom] (`close_sale`): `accepted` → `sold`. Hourly cron: `accepted` + `accepted_at` ≥ 7 days → `lapsed` (NOT a sale). Both via `close_listing()`: active listing → `KMAE_Meta::STATUS_CLOSED`, chats read-only. Seller „N eladott autó” counter (`sold` only).
- CALLED BY: `kmae_finalize_chat_sales` (cron, hooked in `init()` ← `KMAE_Plugin::run`); `KMAE_Public::process_chat_offer_decision` (`close_chat_sale` → `close_sale`); `single-listing.php` (`count_sold_for_seller`, `sold_label`)
- CALLS: `KMAE_Conversations::listing_is_deleted`, `KMAE_Meta::get_status`, `KMAE_Conversations::close_for_listing`
- READS: `table:chat_offers`, `meta:_kmae_status`
- WRITES: `table:chat_offers`, `meta:_kmae_status`, `table:conversations`
- RELATED FEATURE: `CHAT`, `CRON-ARCHIVE`

## `KMAE_Conversations::close_for_listing`
- FILE: `includes/class-kmae-conversations.php`
- PURPOSE: Every `open` conversation of a listing → `closed` (read-only), without touching seen marks (unlike `withdraw_alerts_for_listing`).
- CALLED BY: `KMAE_Chat_Offers::close_listing` (← `finalize_sales`, `close_sale`)
- WRITES: `table:conversations`
- RELATED FEATURE: `CHAT`

## `KMAE_Sale_Contract`
- FILE: `includes/class-kmae-sale-contract.php`
- SYMBOL: `KMAE_Sale_Contract::fields`, `tips_image_url`, `page_url`, `is_contract_request`, `get_deal`, `prefill`, `render`, `process_download`, `build_docx`, `xml_text` (private), `zip` (private), `QUERY_VAR`, `TEMPLATE_DIR`, `TIPS_IMAGE`, `NONCE`
- PURPOSE: Seller's editable sale contract page after an accepted offer + DOCX download built on the fly from `templates/contract/adasveteli/` (no storage); buyer's „Mire figyelj” image URL.
- CALLED BY: `KMAE_Shortcodes::messages` (`render`), `KMAE_Public::handle_forms` (`download_sale_contract` → `process_download`), `KMAE_Pages::offer_thread_body_class` (`is_contract_request`), `templates/offer-thread.php` (`page_url`, `tips_image_url`)
- CALLS: `KMAE_Chat_Offers::get_accepted_for_listing`, `KMAE_Vehicle::get_specs`, `KMAE_Auth::is_company`, `KMAE_Company::get_company_name`, `KMAE_Company::get_address`, `KMAE_Conversations::get`
- READS: `table:chat_offers`, `table:conversations`, listing vehicle meta, user meta `first_name`/`last_name`
- WRITES: —
- RELATED FEATURE: `CHAT`

- FILE: `includes/class-kmae-chat-offers.php`
- SYMBOL: `KMAE_Chat_Offers::submit`
- PURPOSE: Buyer sends a price offer in the chat: silent chat message „Ajánlat: X Ft” + `live` row; the buyer's previous `live` row on the listing → `replaced`.
- CALLED BY: `KMAE_Public::process_chat_offer` (`send_chat_offer`)
- CALLS: `KMAE_Chat_Offers::can_offer`, `KMAE_Conversations::save_message`, `do_action('kmae_conversation_message_created')`
- READS: `table:chat_offers`, `table:conversations`
- WRITES: `table:chat_offers`, `table:conversation_messages`
- RELATED FEATURE: `CHAT`

## `KMAE_Chat_Offers::accept` / `KMAE_Chat_Offers::cancel_sale`
- FILE: `includes/class-kmae-chat-offers.php`
- SYMBOL: `KMAE_Chat_Offers::accept`, `KMAE_Chat_Offers::cancel_sale`, `KMAE_Chat_Offers::post_system_message` (public: a `KMAE_Dealer_Leads::answer` is hívja), `KMAE_Chat_Offers::other_conversation_ids` (private)
- PURPOSE: Seller [Elfogadom]: `live` → `accepted` (+`accepted_at`, GET_LOCK per listing), other `live` rows → `closed`, every other open chat of the listing → system message (`listing_reserved`); [Mégse kelt el]: `accepted` → `withdrawn` + system message, other open chats → „A hirdetés újra elérhető.” (`listing_reopened`).
- CALLED BY: `KMAE_Public::process_chat_offer_decision` (`accept_chat_offer`, `cancel_chat_sale`)
- CALLS: `KMAE_Chat_Offers::get`, `KMAE_Chat_Offers::can_offer`, `KMAE_Chat_Offers::get_accepted_for_listing`, `KMAE_Conversations::save_message`, `do_action('kmae_conversation_message_created')`
- READS: `table:chat_offers`, `table:conversations`
- WRITES: `table:chat_offers`, `table:conversation_messages`
- RELATED FEATURE: `CHAT`

## `KMAE_Chat_Offers::retract` / `KMAE_Chat_Offers::on_listing_deleted`
- FILE: `includes/class-kmae-chat-offers.php`
- SYMBOL: `KMAE_Chat_Offers::retract`, `KMAE_Chat_Offers::on_listing_deleted`, `KMAE_Chat_Offers::STATUS_RETRACTED`
- PURPOSE: Buyer [Visszavonom]: own `live` → `retracted` (not after `accepted`), touch + live signal. Listing delete (before the post is removed): `accepted` buyer gets system message `system:listing_deleted:ID`, all `live`/`accepted` → `closed` (deletion never becomes a sale).
- CALLED BY: `KMAE_Public::process_chat_offer_decision` (`retract_chat_offer`); `KMAE_Listings::force_delete_listing` (`on_listing_deleted`)
- CALLS: `KMAE_Conversations::touch`, `KMAE_Live_Signal::bump`, `KMAE_Chat_Offers::get_accepted_for_listing`, `KMAE_Chat_Offers::post_system_message`
- READS: `table:chat_offers`
- WRITES: `table:chat_offers`, `table:conversation_messages`, `table:conversations`
- RELATED FEATURE: `CHAT`, `LISTINGS`

## `KMAE_Conversations::withdraw_alerts_for_listing`
- FILE: `includes/class-kmae-conversations.php`
- SYMBOL: `KMAE_Conversations::withdraw_alerts_for_listing( $listing_id, $keep_unread = array() )`
- PURPOSE: Listing delete: snapshot `listing_title` into every conversation of the listing, `open` → `closed` (readable), mark seen for both parties except `$keep_unread` (the „Foglalt” buyer).
- CALLED BY: `KMAE_Listings::force_delete_listing`
- CALLS: `KMAE_Vehicle::get_display_title`, `KMAE_Database::mark_conversation_thread_seen`
- WRITES: `table:conversations`, user meta `_kmae_conversations_seen_map`
- RELATED FEATURE: `CHAT`, `LISTINGS`

## `KMAE_Query::get_similar_listings` / `KMAE_Post_Types::is_unavailable_for_viewer`
- FILE: `includes/class-kmae-query.php`, `includes/class-kmae-post-types.php`
- PURPOSE: Expired listing visited by a non-owner/non-admin → `template_loader` serves `templates/listing-unavailable.php`; similar = same body type (`KMAE_Vehicle::META_BODY`) active listings, topped up with newest, max 4.
- CALLED BY: `KMAE_Post_Types::template_loader`, `templates/listing-unavailable.php`
- CALLS: `KMAE_Query::get_listings`, `KMAE_Meta::get_status`
- RELATED FEATURE: `LISTINGS`

## `KMAE_Chat_Offers::can_offer` / `get_accepted_for_listing` / `listing_state` / `is_system_message`
- FILE: `includes/class-kmae-chat-offers.php`
- SYMBOL: `KMAE_Chat_Offers::can_offer`, `KMAE_Chat_Offers::get_accepted_for_listing`, `KMAE_Chat_Offers::listing_state`, `KMAE_Chat_Offers::is_system_message`, `KMAE_Chat_Offers::get`, `KMAE_Chat_Offers::get_by_message`, `KMAE_Chat_Offers::count_live_for_listing`, `KMAE_Chat_Offers::seller_signature`
- PURPOSE: Offer gate (open chat + active listing + not „Foglalt”); listing sale state `sold` / `reserved` / `reopened` / ''; `system:` key = chat system notice.
- CALLED BY: `conversation-free-chat.php`, `conversation-timeline.php`, `offer-thread.php`, `single-listing.php`, `my-listings.php`, `KMAE_Live_Page::get_fingerprint` (`single_listing`)
- CALLS: —
- READS: `table:chat_offers`
- WRITES: —
- RELATED FEATURE: `CHAT`

## `KMAE_Database::count_unread_conversations`
- FILE: `includes/class-kmae-database.php`
- SYMBOL: `KMAE_Database::count_unread_conversations`
- PURPOSE: Nav badge: number of conversations with an unread message from the other party.
- CALLED BY: theme `header.php`, theme `kmae_app_menu_groups()`, `KMAE_Public::ajax_poll_page`, `KMAE_Live_Page::get_fingerprint` (`nav_badge`)
- CALLS: `KMAE_Conversations::get_for_user`, `KMAE_Database::get_conversation_alert_state`
- READS: user meta `_kmae_conversations_seen_map`
- WRITES: —
- RELATED FEATURE: `CHAT`, `LIVE-NAV`

## `KMAE_Pages::messages_page_url` / `KMAE_Pages::is_messages_page`
- FILE: `includes/class-kmae-pages.php`
- PURPOSE: Üzenetek page (`uzenetek`) URL / detection. `conversation_thread_url()` builds `/uzenetek/?kmae_conversation=ID`; `mark_conversation_seen_on_visit()` marks the thread seen.
- RELATED FEATURE: `CHAT`, `PAGES-ROUTING`

## `KMAE_Activator::remove_offer_system`
- FILE: `includes/class-kmae-activator.php`
- PURPOSE: One-time (option `kmae_offer_system_removed`) drop of the old offer/deal/rating tables and wipe of all conversations/messages/chat files.
- CALLED BY: `KMAE_Activator::activate`, `KMAE_Activator::maybe_upgrade`
- RELATED FEATURE: `BOOT-ARCHITECTURE`, `CHAT`

## `KMAE_Seller::format_cash`
- FILE: `includes/class-kmae-seller.php`
- PURPOSE: `12 345 Ft` formatting for listing prices.
- CALLED BY: `KMAE_Seller::get_public_price_row`
- RELATED FEATURE: `LISTINGS`

## `KMAE_Reports`
- FILE: `includes/class-kmae-reports.php`
- SYMBOL: `KMAE_Reports::reasons`, `reason_label`, `statuses`, `submit`, `notify_admins` (private), `get`, `get_list`, `count_by_status`, `count_new`, `get_against_user`, `reporter_stats`, `update_decision`, `get_conversation_messages`, `admin_url`, `CONTEXT_LISTING`, `CONTEXT_CHAT`, `STATUS_NEW`, `STATUS_VALID`, `STATUS_INVALID`, `REASON_OTHER`, `DETAILS_MAX_LENGTH`
- PURPOSE: Hirdetés / chat bejelentés mentése (ok + indoklás, duplikátum-védelem), admin e-mail (`KMAE_Brand::send_mail`) + Web Push, admin lista/részletek/döntés lekérdezések.
- CALLED BY: `KMAE_Public::process_report` (`submit_report`), `KMAE_Admin::add_menu` (`count_new`), `KMAE_Admin::render_reports`, `KMAE_Admin::handle_report_update`, `admin/views/reports.php`, `templates/partials/report-form.php`
- CALLS: `KMAE_Conversations::get`, `KMAE_Conversations::is_member`, `KMAE_Conversations::get_for_listing_buyer`, `KMAE_Live_Signal::bump`
- READS: `table:reports`, `table:conversations`, `table:conversation_messages`
- WRITES: `table:reports`
- RELATED FEATURE: `REPORTS`

## `KMAE_Public::process_report`
- FILE: `public/class-kmae-public.php`
- SYMBOL: `KMAE_Public::process_report` (private)
- PURPOSE: `kmae_action=submit_report` (nonce `kmae_report_nonce`) → `KMAE_Reports::submit`; redirect a chatre vagy a hirdetésre `kmae_success=report_sent` / `kmae_error=...`.
- CALLED BY: `KMAE_Public::handle_forms`
- CALLS: `KMAE_Reports::submit`, `KMAE_Pages::conversation_thread_url`
- RELATED FEATURE: `REPORTS`

## `KMAE_Admin::render_reports` / `KMAE_Admin::handle_report_update`
- FILE: `admin/class-kmae-admin.php`
- SYMBOL: `KMAE_Admin::render_reports`, `KMAE_Admin::handle_report_update`
- PURPOSE: Albira → Jelentések oldal (`page=kmae-reports`, `status`, `report`) és a döntés mentése (admin-post `kmae_report_update`, nonce `kmae_report_update_{id}`).
- CALLED BY: `add_submenu_page` callback, WP action `admin_post_kmae_report_update`
- CALLS: `KMAE_Reports::get`, `get_list`, `count_by_status`, `update_decision`, `admin_url`
- RELATED FEATURE: `REPORTS`, `ADMIN`

## `KMAE_Settlements`
- FILE: `includes/class-kmae-settlements.php`
- SYMBOL: `KMAE_Settlements::find`, `canonical_name`, `coords`, `by_zip`, `find_anywhere`, `for_county`, `lower_key`, `ascii_key`, `enqueue_picker`
- PURPOSE: Official HU settlement list from `assets/data/settlements.json` (JSON once per request, per-county index lazily). Case/space-insensitive; accent-insensitive only when unambiguous; county must match in `find`. Budapest: zip prefix `1`, „Budapest XI. ker.”/„Bp.” → Budapest.
- CALLED BY: `KMAE_Counties::location_error`, `KMAE_Counties::canonical_city`, `KMAE_Counties::ajax_locate` (locate_official), `KMAE_Geo::dataset_coords` (listing_coords, location_changed, geocode_listing, user_coords)
- READS: `assets/data/settlements.json`
- WRITES: —
- RELATED FEATURE: `BROWSE-SEARCH`, `PROFILE-COMPANY-SETTINGS`

## `KMAE_Dealer_Leads`
- FILE: `includes/class-kmae-dealer-leads.php`
- SYMBOL: `KMAE_Dealer_Leads::is_enabled`, `maybe_create`, `create_table`, `insert_batch`, `parse_price`, `on_listing_ended`, `cancel_for_dealer`, `delete_for_listing`, `run_expire_cron`, const `PRICE_MIN`/`PRICE_MAX`/`CRON_EXPIRE`, `mark_interested`, `mark_declined`, `set_conversation`, `expire_due`, `cancel_for_listing`, `get`, `get_by_token`, `contacted_dealer_ids`, `counts_for_listing`, `list_for_dealer`, `list_for_seller`, `count_pending_for_dealer`, `new_token`, `is_token`, `can_start`, `match`, `init`, `nonce`, `client_config`, `preview`, `send`, `ajax_preview`, `ajax_send`, `send_queued_notifications`, `notifications_url`, `lead_url`, `radius_options`, `purpose_options`, `answer_form_action`, `answer_form_fields`, `handle_answer`, `handle_answer_nopriv`, `answer`, `answer_error_url`, `can_answer_interested`, `interest_text`
- PURPOSE: Csoportos kereskedői megkeresés adatrétege (1. fázis) + jogosultság/találat-számolás (3. fázis: `can_start`, `match`) + AJAX előnézet/küldés és cég-értesítés (4. fázis: `wp_ajax_kmae_dealer_leads_preview|send`, nonce `kmae_dealer_leads`, GET_LOCK `kmae_dealer_leads_<listing>`; levél `KMAE_Notifications::notify_dealer_lead` shutdown 90-en, egy `KMAE_Live_Signal::bump`) + a cég válasza (5. fázis: `admin_post[_nopriv]_kmae_dealer_lead_answer` → `answer()`: „Érdekel” = `KMAE_Conversations::create` (cég = vevő) + rendszerüzenet `system:dealer_lead:<lead id>` a cég nevében (`KMAE_Chat_Offers::post_system_message`), „nem” = csak állapot + néma `bump`; GET_LOCK `kmae_dealer_lead_<id>`). Nincs UI. Átmenet csak `pending`-ből (`WHERE status='pending'`), időpontok UTC.
- CALLED BY: `KMAE_Plugin::run` (`init`); `KMAE_Activator::maybe_upgrade` (`maybe_create`), `KMAE_Activator::create_tables` (`create_table`); életciklus (6. fázis): `KMAE_Listings::force_delete_listing` + `expire_listings` + `reset_listing` (`delete_for_listing`), `KMAE_Chat_Offers::close_listing` (`on_listing_ended`), `KMAE_Auth::delete_account` (`cancel_for_dealer`), cron `kmae_expire_dealer_leads` (`KMAE_Activator::schedule_cron` / `KMAE_Deactivator`, kezelő `run_expire_cron`); `KMAE_Settings::register_settings` / `admin/views/settings.php` (`OPTION`); `KMAE_Notifications::notify_dealer_lead` (`lead_url`, `MAIL_KEY`); Értesítések sablon (7. fázis: `answer_form_action`, `answer_form_fields`)
- READS: `{prefix}kmae_dealer_leads`, `table:conversation_messages` (`has_interest_message`), option `kmae_dealer_leads_enabled`, `kmae_dealer_leads_schema`
- WRITES: `{prefix}kmae_dealer_leads`, `table:conversations` + `table:conversation_messages` (válasz, a `KMAE_Conversations`/`KMAE_Chat_Offers` API-n át), option `kmae_dealer_leads_schema`, transient `kmae_dealer_leads_retry`
- RELATED FEATURE: `DEALER-LEADS`, `CHAT`
