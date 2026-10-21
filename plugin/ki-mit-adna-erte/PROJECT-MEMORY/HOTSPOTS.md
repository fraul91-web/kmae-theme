# HOTSPOTS — PLUGIN V2

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

MAP-TYPE: ON-DEMAND

Only source-connected, multi-feature/high-regression targets are listed. File size alone is not a hotspot criterion.

## `public/class-kmae-public.php :: KMAE_Public::handle_forms()`
- **USED BY:** AUTH, LISTINGS, CHAT
- **WHY SENSITIVE:** central POST dispatcher and auth/verification gate.
- **VERIFY AFTER CHANGE:** every form branch touched, nonce/redirect/error behavior.
- **STATUS:** VERIFIED.

## `public/class-kmae-public.php :: KMAE_Public::process_create_listing()`
- **USED BY:** LISTINGS, VEHICLE, COMPANY, MODERATION, PHOTO-MOTOR, DRAFTS
- **WHY SENSITIVE:** listing creation orchestrator across validators and lifecycle.
- **VERIFY AFTER CHANGE:** private/company, vehicle fields, moderation, photo status, draft discard, publish event.
- **STATUS:** VERIFIED.

## `includes/class-kmae-listings.php :: KMAE_Listings::save_meta_from_post()`
- **USED BY:** LISTINGS, VEHICLE, COMPANY, SELLER
- **WHY SENSITIVE:** shared listing metadata write path.
- **VERIFY AFTER CHANGE:** create/edit, company location, vehicle/seller fields.
- **STATUS:** VERIFIED.

## `includes/class-kmae-notifications.php :: KMAE_Notifications::send_offer_email()`
- **USED BY:** chat message, listing published, unfinished listing reminder emails (name is historical)
- **WHY SENSITIVE:** shared preference + mail handoff for user email.
- **VERIFY AFTER CHANGE:** mail preferences (`allows_mail`) and HTML output.
- **STATUS:** VERIFIED.

## `includes/class-kmae-brand.php :: KMAE_Brand::send_mail()/wrap_email()`
- **USED BY:** all branded email
- **WHY SENSITIVE:** common HTML mail transport/wrapper.
- **VERIFY AFTER CHANGE:** all notification/contact/auth branded mail users.
- **STATUS:** VERIFIED.

## `includes/class-kmae-auth.php :: verification + email prefs`
- **USED BY:** AUTH, LISTINGS/CHAT gates, NOTIFICATIONS
- **WHY SENSITIVE:** shared identity/verification/preferences.
- **VERIFY AFTER CHANGE:** login/register/reset, protected actions, mail preference.
- **STATUS:** VERIFIED.

## `includes/class-kmae-photo-motor.php :: validate_request()/slot readiness/client_config()`
- **USED BY:** PHOTO-MOTOR, LISTINGS, WIZARD
- **WHY SENSITIVE:** authoritative photo constraints bridge JS and publication.
- **VERIFY AFTER CHANGE:** client config, upload validation, required slots, awaiting_photos activation.
- **STATUS:** VERIFIED.

## `includes/class-kmae-browse.php :: personalize_listings()`
- **USED BY:** BROWSE
- **WHY SENSITIVE:** uses pass history to mutate search ordering/visibility.
- **VERIFY AFTER CHANGE:** fresh results, passed reinsertion.
- **STATUS:** VERIFIED.

## `includes/class-kmae-plugin.php :: KMAE_Plugin::run()`
- **USED BY:** BOOT + every initialized service
- **WHY SENSITIVE:** global registration/init order.
- **VERIFY AFTER CHANGE:** feature hooks, admin/public instantiation, missing services.
- **STATUS:** VERIFIED.

## `includes/class-kmae-pages.php :: page URL/shortcode/routing methods`
- **USED BY:** PAGES, NAV, CHAT, BROWSE
- **WHY SENSITIVE:** central managed-page routing consumed across UI.
- **VERIFY AFTER CHANGE:** URLs, legacy redirects, shortcode mapping, seen-on-visit.
- **STATUS:** VERIFIED.

