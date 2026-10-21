# TEMPLATE MAP

> 2026-10-10: the offer / deal / rating system was **deleted**; only the chat remains (see `FEATURES/CHAT.md`). Rows/sections of deleted files were removed.

Templates are passive views unless they contain form/action wiring. This map records source references and action values visible in templates.

## `admin/views/dashboard.php`
- REFERENCED/INCLUDED BY: `admin/class-kmae-admin.php`, `includes/class-kmae-loader.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Admin::admin_action_url`, `KMAE_Admin::status_badge`, `KMAE_Launch::site_tagline`, `KMAE_Meta::get_status`, `KMAE_Vehicle::get_swipe_subtitle`, `KMAE_Vehicle::is_vehicle_launch`
- RELATED FEATURE: `ADMIN`

## `admin/views/meta-box-listing.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-meta.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Counties::get_all`, `KMAE_Vehicle::body_labels`, `KMAE_Vehicle::condition_labels`, `KMAE_Vehicle::fuel_labels`, `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::is_vehicle_launch`, `KMAE_Vehicle::transmission_labels`
- RELATED FEATURE: `ADMIN`

## `admin/views/settings.php`
- REFERENCED/INCLUDED BY: `admin/class-kmae-admin.php`, `includes/class-kmae-loader.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Google_Auth::redirect_uri`, `KMAE_Settings::auto_moderation_enabled`, `KMAE_Settings::buyer_video_url`, `KMAE_Settings::contact_form_email`, `KMAE_Settings::seller_video_url`
- RELATED FEATURE: `ADMIN`

## `templates/archive-listing.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-post-types.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: —
- RELATED FEATURE: `PAGES-ROUTING`

## `templates/browse-swipe.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Browse::bodies_label`, `KMAE_Counties::format_listing_location`, `KMAE_Counties::get_label`, `KMAE_Icons::render`, `KMAE_Meta::get_offer_types`, `KMAE_Query::get_listing_thumbnail_url`, `KMAE_Seller::PRICE_GUIDE`, `KMAE_Seller::get_public_price_row`, `KMAE_Taxonomies::offer_type_labels`, `KMAE_Vehicle::browse_mode_labels`, `KMAE_Vehicle::format_value`, `KMAE_Vehicle::get_swipe_subtitle`, `KMAE_Vehicle::get_vehicle_value`, `KMAE_Vehicle::is_vehicle_launch`
- RELATED FEATURE: `BROWSE-SEARCH`

## `templates/browse-wizard-body.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Counties::get_label`, `KMAE_Icons::render`, `KMAE_Launch::wizard_step_label`, `KMAE_Vehicle::browse_body_cards`
- RELATED FEATURE: `BROWSE-SEARCH`

## `templates/browse-wizard-category.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Counties::get_label`, `KMAE_Taxonomies::offer_type_labels`, `KMAE_Taxonomies::seed_categories`
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `templates/browse-wizard-map.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Counties::ORSZAG`, `KMAE_Counties::get_label`, `KMAE_Counties::get_map_data`, `KMAE_Counties::get_map_label_short`, `KMAE_Launch::wizard_step_label`, `KMAE_Pages::browse_page_url`
- RELATED FEATURE: `BROWSE-SEARCH`

## `templates/browse-wizard-offer.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Browse::bodies_label`, `KMAE_Browse::bodies_to_query_value`, `KMAE_Counties::get_label`, `KMAE_Icons::render`, `KMAE_Launch::wizard_step_label`, `KMAE_Vehicle::BROWSE_AJANLAT`, `KMAE_Vehicle::BROWSE_KP`
- RELATED FEATURE: `BROWSE-SEARCH`

## `templates/browse-wizard-type.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Counties::get_label`, `KMAE_Launch::wizard_step_label`, `KMAE_Taxonomies::offer_type_labels`, `KMAE_Vehicle::is_vehicle_launch`
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `templates/browse.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-loader.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Counties::format_listing_location`, `KMAE_Images::responsive_attrs`, `KMAE_Meta::get_offer_types`, `KMAE_Query::get_listing_thumbnail_id`, `KMAE_Query::get_listing_thumbnail_url`, `KMAE_Seller::PRICE_GUIDE`, `KMAE_Seller::get_public_price_row`, `KMAE_Taxonomies::offer_type_labels`
- RELATED FEATURE: `BROWSE-SEARCH`

## `templates/contact.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-loader.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_contact_form`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Pages::url`
- RELATED FEATURE: `CONTACT-FORM`

## `templates/education.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Back::home_label`, `KMAE_Back::home_url`, `KMAE_Settings::buyer_video_id`, `KMAE_Settings::seller_video_id`
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `templates/faq.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Pages::url`
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `templates/form-create-listing.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: —
- RELATED FEATURE: `LISTINGS`

## `templates/form-listing.php`
- REFERENCED/INCLUDED BY: `templates/form-create-listing.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::is_company`, `KMAE_Company::get_city`, `KMAE_Company::get_county`, `KMAE_Counties::get_label`, `KMAE_Database::get_private_data`, `KMAE_Launch::get_available_categories`, `KMAE_Launch::get_launch_category_id`, `KMAE_Listing_Drafts::get_user_draft`, `KMAE_Meta::STATUS_AWAITING_PHOTOS`, `KMAE_Meta::get_status`, `KMAE_Moderation::MIN_IMAGES`, `KMAE_Moderation::is_enabled`, `KMAE_Moderation::pull_errors_for_user`, `KMAE_Photo_Motor::take_autofix_notice`, `KMAE_Query::get_gallery_ids`, `KMAE_Seller::can_create_listing`, `KMAE_Seller::get_commitments`, `KMAE_Seller::get_extra_other`, `KMAE_Seller::get_fixed_price`, `KMAE_Seller::get_guide_price` +2 more
- RELATED FEATURE: `LISTINGS`

## `templates/form-login.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-pages.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_login`, `kmae_lost_password`, `kmae_reset_password`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::login_url`, `KMAE_Auth::lost_password_url`, `KMAE_Auth::register_url`, `KMAE_Dashboard::greeting_case`
- RELATED FEATURE: `AUTH`

## `templates/form-profile.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_resend_verification`, `kmae_save_profile`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::account_type_label`, `KMAE_Auth::get_profile`, `KMAE_Auth::is_company`, `KMAE_Company::INTRO_MAX`, `KMAE_Company::META_COMPANY_INTRO`, `KMAE_Company::get_address`, `KMAE_Company::get_city`, `KMAE_Company::get_company_name`, `KMAE_Company::get_county`, `KMAE_Counties::get_all`, `KMAE_Icons::render`
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `templates/form-register.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-pages.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_register`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::ACCOUNT_COMPANY`, `KMAE_Auth::ACCOUNT_PRIVATE`, `KMAE_Auth::login_url`, `KMAE_Counties::get_all`, `KMAE_Google_Auth::needs_completion`, `KMAE_Pages::url`
- RELATED FEATURE: `AUTH`

## `templates/hero.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Pages::browse_page_url`
- RELATED FEATURE: `PAGES-ROUTING`

## `templates/hub-section.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`
- RELATED FEATURE: `DASHBOARD-HUBS`

## `templates/landing-features.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`
- RELATED FEATURE: `PAGES-ROUTING`

## `templates/landing-popular.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`, `KMAE_Pages::browse_page_url`, `KMAE_Query::get_listing_thumbnail_url`, `KMAE_Query::get_popular_listings`, `KMAE_Vehicle::format_value`, `KMAE_Vehicle::get_display_title`, `KMAE_Vehicle::get_vehicle_value`
- RELATED FEATURE: `PAGES-ROUTING`

## `templates/landing-search.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Pages::browse_page_url`, `KMAE_Vehicle::brands`
- RELATED FEATURE: `PAGES-ROUTING`

## `templates/listing-unavailable.php`
- PURPOSE: Lejárt hirdetés a látogatónak: „Ez az autó már nem elérhető” + „Autók böngészése” + „Hasonló autók” (max. 4 kártya), `wp_robots_no_robots`.
- LOADED BY: `KMAE_Post_Types::template_loader` when `is_unavailable_for_viewer()` (status `expired`, not owner/admin).
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Query::get_similar_listings`, `KMAE_Query::get_listing_thumbnail_url`, `KMAE_Vehicle::get_display_title`, `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::format_mileage`, `KMAE_Seller::get_public_price_row`, `KMAE_Pages::browse_page_url`, `KMAE_Icons::render` (`hourglass`, `car`)
- CSS: theme `listing-unavailable.css` (saját `--sl-*` változók; vendégnek is betölt: `kmae_enqueue_listing_unavailable`)

## `templates/messages.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php` (`kmae_messages`, no `?kmae_conversation`)
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Live_Page::get_fingerprint` (`messages_inbox`), `KMAE_Vehicle::get_display_title`, `KMAE_Query::get_listing_thumbnail_url`
- DATA: `$inbox` from `KMAE_Conversations::get_inbox_for_user()` (törölt hirdetés: `listing_gone` → `conversation->listing_title` + `.kmae-inbox__gone`); theme style `assets/css/messages.css`
- RELATED FEATURE: `CHAT`

## `templates/my-listings.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_delete_listing`, `kmae_reactivate_listing`, `kmae_renew_listing`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`, `KMAE_Images::get_url`, `KMAE_Listings::can_delete_listing`, `KMAE_Listings::can_edit_listing`, `KMAE_Listings::can_reactivate_listing`, `KMAE_Listings::get_archive_lifecycle`, `KMAE_Meta::STATUS_ACTIVE`, `KMAE_Meta::STATUS_AWAITING_PHOTOS`, `KMAE_Meta::STATUS_CLOSED`, `KMAE_Meta::STATUS_DRAFT`, `KMAE_Meta::get_status`, `KMAE_Query::get_gallery_ids`, `KMAE_Vehicle::format_mileage`, `KMAE_Vehicle::fuel_labels`, `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::transmission_labels`
- RELATED FEATURE: `LISTINGS`

## `templates/offer-thread.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php` (`kmae_messages` with `?kmae_conversation=`)
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Conversations::get_thread_events`, `KMAE_Conversations::listing_is_deleted`, `KMAE_Live_Page::get_fingerprint` (`conversation_thread`), `KMAE_Query::get_listing_thumbnail_url`, `KMAE_Vehicle::get_display_title`, `KMAE_Auth::is_company`, `KMAE_Auth::is_fully_verified`, `KMAE_Company::get_company_name`, `KMAE_Sale_Contract::page_url`, `KMAE_Sale_Contract::tips_image_url`, `KMAE_Reports::CONTEXT_CHAT` (includes `partials/report-form.php` in the header)
- RELATED FEATURE: `CHAT`, `REPORTS`
- RENDER ORDER: one column chat shell (listing header [+ Bejelentés] → flash → timeline → composer); theme style `offer-thread.css`

## `templates/sale-contract.php`
- REFERENCED/INCLUDED BY: `KMAE_Sale_Contract::render` (`kmae_messages` with `?kmae_conversation=&kmae_contract=1`)
- `kmae_action` VALUES: `download_sale_contract`
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Sale_Contract::NONCE`
- RELATED FEATURE: `CHAT`
- RENDER ORDER: toolbar (vissza, Letöltés (Word), Nyomtatás) → paper form (1–7. pont); theme style `sale-contract.css`

## `templates/partials/conversation-timeline.php`
- REFERENCED/INCLUDED BY: `templates/offer-thread.php`
- `kmae_action` VALUES: `accept_chat_offer` (eladó [Elfogadom]), `retract_chat_offer` (vevő [Visszavonom] a saját élő ajánlata alatt)
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`
- RENDERS: date separators + message bubbles (image lightbox / file download / 48 h expired note)
- RELATED FEATURE: `CHAT`

## `templates/partials/conversation-free-chat.php`
- REFERENCED/INCLUDED BY: `templates/offer-thread.php`
- `kmae_action` VALUES: `send_conversation_message`
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Conversations::is_member`
- RELATED FEATURE: `CHAT`

## `templates/partials/create-listing-guest.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::login_url`, `KMAE_Auth::register_url`
- RELATED FEATURE: `LISTINGS`

## `templates/partials/dashboard-home.php`
- REFERENCED/INCLUDED BY: —
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Dashboard::take_home_greeting`, `KMAE_Icons::render`
- RELATED FEATURE: `DASHBOARD-HUBS`

## `templates/partials/email-verify-gate.php`
- REFERENCED/INCLUDED BY: `templates/single-listing.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_resend_verification`
- DIRECT KMAE SYMBOL REFERENCES: —
- RELATED FEATURE: `AUTH`

## `templates/partials/google-button.php`
- REFERENCED/INCLUDED BY: `templates/form-login.php`, `templates/form-register.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Google_Auth::is_configured`, `KMAE_Google_Auth::start_url`
- RELATED FEATURE: `AUTH`, `GOOGLE-AUTH`

## `templates/partials/google-complete.php`
- REFERENCED/INCLUDED BY: `templates/form-register.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_google_complete`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::ACCOUNT_COMPANY`, `KMAE_Auth::ACCOUNT_PRIVATE`, `KMAE_Auth::first_name`, `KMAE_Counties::get_all`, `KMAE_Dashboard::greeting_case`, `KMAE_Pages::url`
- RELATED FEATURE: `GOOGLE-AUTH`

## `templates/partials/listing-form-layout.php`
- REFERENCED/INCLUDED BY: `templates/form-listing.php`
- `kmae_action` VALUES: `create_listing`
- `action` VALUES: `kmae_update_listing`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Back::browse_url`, `KMAE_Counties::get_all`, `KMAE_Icons::render`, `KMAE_Moderation::MIN_DESC_LENGTH`, `KMAE_Moderation::is_enabled`, `KMAE_Photo_Motor::get_slots`, `KMAE_Photo_Motor::is_enabled`, `KMAE_Photo_Motor::recommended_slots`, `KMAE_Photo_Motor::recommended_total_count`, `KMAE_Photo_Motor::required_count`, `KMAE_Photo_Motor::required_slots`, `KMAE_Seller::COMMIT_OTHER`, `KMAE_Seller::PRICE_FIXED`, `KMAE_Seller::PRICE_GUIDE`, `KMAE_Seller::commitment_defs`, `KMAE_Seller::extra_commitment_defs`, `KMAE_Seller::price_mode_defs`
- RELATED FEATURE: `LISTINGS`, `LISTING-WIZARD-DRAFTS`

## `templates/partials/listing-gallery-manager.php`
- REFERENCED/INCLUDED BY: `templates/partials/listing-form-layout.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`, `KMAE_Images::get_url`
- RELATED FEATURE: `PHOTO-MOTOR`

## `templates/partials/listing-gallery.php`
- REFERENCED/INCLUDED BY: `templates/single-listing.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Images::get_url`, `KMAE_Images::responsive_attrs`, `KMAE_Query::get_gallery_ids`
- RELATED FEATURE: `PHOTO-MOTOR`

## `templates/partials/listing-photo-motor.php`
- REFERENCED/INCLUDED BY: `templates/partials/listing-form-layout.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Images::get_url`, `KMAE_Photo_Motor::all_slots`, `KMAE_Photo_Motor::get_slots`, `KMAE_Photo_Motor::recommended_slots`, `KMAE_Photo_Motor::required_slots`
- RELATED FEATURE: `PHOTO-MOTOR`

## `templates/partials/listing-pledge-badges.php`
- REFERENCED/INCLUDED BY: `templates/browse-swipe.php`, `templates/browse.php`, `templates/single-listing.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Seller::get_badge_rows`
- RELATED FEATURE: `LISTINGS`

## `templates/partials/listing-wizard.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-loader.php`, `templates/partials/listing-form-layout.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`, `KMAE_Listing_Wizard::description_topics`, `KMAE_Listing_Wizard::phases`
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

## `templates/partials/offer-forms.php`
- REFERENCED/INCLUDED BY: `templates/single-listing.php`
- `kmae_action` VALUES: `start_conversation`
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: —
- RELATED FEATURE: `CHAT`

## `templates/partials/report-form.php`
- REFERENCED/INCLUDED BY: `templates/single-listing.php` (side panel, non-owner), `templates/offer-thread.php` (header)
- `kmae_action` VALUES: `submit_report`
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Reports::reasons`, `KMAE_Reports::CONTEXT_CHAT`, `KMAE_Reports::REASON_OTHER`, `KMAE_Reports::DETAILS_MAX_LENGTH`, `KMAE_Auth::login_url`, `KMAE_Icons::render` (`flag`, `x`)
- RELATED FEATURE: `REPORTS`

## `templates/partials/page-back.php`
- REFERENCED/INCLUDED BY: `templates/messages.php`, `templates/single-listing.php`, `templates/education.php`, `templates/security.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Back::home_label`, `KMAE_Back::home_url`
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `templates/partials/vehicle-form-fields.php`
- REFERENCED/INCLUDED BY: `templates/partials/listing-form-layout.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`, `KMAE_Vehicle::MAX_TRAITS`, `KMAE_Vehicle::body_types`, `KMAE_Vehicle::condition_labels`, `KMAE_Vehicle::fuel_labels`, `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::get_traits`, `KMAE_Vehicle::is_known_brand`, `KMAE_Vehicle::is_known_model`, `KMAE_Vehicle::kw_to_horsepower`, `KMAE_Vehicle::traits`, `KMAE_Vehicle::transmission_labels`
- RELATED FEATURE: `VEHICLE`

## `templates/partials/vehicle-specs-display.php`
- REFERENCED/INCLUDED BY: `templates/single-listing.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Icons::render`, `KMAE_Vehicle::get_display_rows`, `KMAE_Vehicle::get_traits`, `KMAE_Vehicle::traits`
- RELATED FEATURE: `VEHICLE`

## `templates/privacy.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`

## `templates/security.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Back::home_label`, `KMAE_Back::home_url`
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `templates/settings.php`
- REFERENCED/INCLUDED BY: `admin/class-kmae-admin.php`, `includes/class-kmae-loader.php`, `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: `kmae_change_email`, `kmae_change_password`, `kmae_logout_everywhere`, `kmae_save_settings`
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::account_type_label`, `KMAE_Auth::is_company`, `KMAE_Back::home_label`, `KMAE_Back::home_url`, `KMAE_Icons::render`, `KMAE_Notifications::mail_catalog`, `KMAE_Notifications::mail_prefs`, `KMAE_Pages::url`, `KMAE_Push::is_available`, `KMAE_Push::is_enabled`
- RELATED FEATURE: `PROFILE-COMPANY-SETTINGS`

## `templates/single-listing.php`
- REFERENCED/INCLUDED BY: `includes/class-kmae-post-types.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: `KMAE_Auth::account_type_label`, `KMAE_Auth::first_name`, `KMAE_Auth::login_url`, `KMAE_Auth::should_gate_for_verification`, `KMAE_Back::listing_back_url`, `KMAE_Conversations::get_for_listing_buyer`, `KMAE_Counties::format_listing_location`, `KMAE_Icons::render`, `KMAE_Live_Page::get_fingerprint`, `KMAE_Meta::STATUS_ACTIVE`, `KMAE_Meta::get_status`, `KMAE_Pages::conversation_thread_url`, `KMAE_Pages::public_profile_url`, `KMAE_Seller::get_badge_rows`, `KMAE_Seller::get_public_price_row`, `KMAE_Vehicle::brand_logos`, `KMAE_Vehicle::get_specs`, `KMAE_Vehicle::get_traits`, `KMAE_Vehicle::traits`
- RELATED FEATURE: `PAGES-ROUTING`

## `templates/terms.php`
- REFERENCED/INCLUDED BY: `public/class-kmae-shortcodes.php`
- `kmae_action` VALUES: —
- `action` VALUES: —
- DIRECT KMAE SYMBOL REFERENCES: —
- RELATED FEATURE: `BOOT-ARCHITECTURE`
