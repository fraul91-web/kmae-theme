# FEATURE — Profile, company accounts and user settings

## PURPOSE
Profiladatok, magán/cég fiók, céges telephely, e-mail/jelszó/security műveletek. A beállítások oldalon az „E-mail értesítések beállítása” gomb a katalógus popupját nyitja (`kmae_mail_prefs`). A fiókvédelem levelei nem kapcsolhatók.

## READ FIRST
- `templates/form-profile.php`
- `templates/public-profile.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-auth.php`

## THEN IF NEEDED
- `public/class-kmae-public.php`
- `templates/settings.php`
- `templates/security.php`
- `includes/class-kmae-counties.php`
- `assets/js/phone-field.js`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
- `[kmae_profile]`, `[kmae_settings]`, `[kmae_security]`
- `?kmae_p=<24 karakteres véletlen kulcs>` on the profile page opens `templates/public-profile.php` (kulcs: user meta `_kmae_public_key`, `KMAE_Pages::public_profile_key` / `user_by_public_key`). A régi `?kmae_user=<ID>` 2026-10-14 óta semmit nem nyit meg (IDOR-javítás) — user ID soha ne kerüljön URL-be/HTML-be. Listing name and „Részletes profil” use `KMAE_Pages::public_profile_url()`.
- admin-post profile/settings/e-mail/password/logout-everywhere handlers.
- `admin_post_kmae_save_mail_prefs` → `KMAE_Public::admin_save_mail_prefs` → `KMAE_Notifications::save_mail_prefs`.

## IMPORTANT SYMBOLS
- `KMAE_Public::admin_save_profile`
- `KMAE_Company::{save_profile_fields,apply_listing_location}`
- `KMAE_Auth::{sync_phone,save_email_prefs,get_profile,is_email_verified,is_phone_verified}`
- security handlers in `KMAE_Public`.

## DATA USED
WP user/display name/email/password/session tokens; company user meta; `kmae_user_profiles`. No deal history / trust statistics any more (removed 2026-10-10); profile shows verification tags and member-since only. Email ✓ is `email_verified`; phone ✓ only if `phone_verified` is actually set.

## FLOW
Profile POST → nonce/login → user/profile changes → company validation if company → redirect. Company listings source county/city from company profile instead of arbitrary listing POST.

## DEPENDENCIES
`KMAE_Counties` validates location. Listing save depends on company profile completeness.

## SIDE EFFECTS
Changes user identity/profile and may invalidate sessions on logout-everywhere.

## CHANGE RISKS
Company location is reused by listing creation/edit; profile changes therefore affect future listing location.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-pages.php`
- `includes/class-kmae-brand.php`
- `includes/class-kmae-database.php`
- `includes/class-kmae-photo-motor.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-listing-drafts.php`
- `includes/class-kmae-listing-wizard.php`
- `includes/class-kmae-moderation.php`
- `includes/class-kmae-meta.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-live-page.php`
- `includes/class-kmae-icons.php`
- `includes/class-kmae-settings.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-query.php`
- `includes/class-kmae-seller.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Profile/company/settings  
**COMMON SYMPTOMS:** profile save, company fields/location, settings.  
**READ FIRST (symbol-level):** `public/class-kmae-public.php :: admin_save_profile()`; `includes/class-kmae-company.php`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `save_profile company apply_listing_location settings public-profile`.  
**DO NOT LOAD UNLESS:** chat unless symptom crosses user preference.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## Company intro (bemutatkozás)
User meta `kmae_company_intro` (`KMAE_Company::META_COMPANY_INTRO`, max `INTRO_MAX` 1000, `get_intro()`), textarea in `form-profile.php` (company section), saved via `KMAE_Company::save_profile_fields( ['intro'] )`. Shown only on the public profile page (`templates/public-profile.php`, `.kmae-public-profile__intro`, at the top above the history, full text) when non-empty; NOT on `single-listing.php` any more (listing card shows only the trust stats + „Részletes profil” link).

## Profilom törlése (2026-10-11)
`form-profile.php` alján összecsukott `details.kmae-profile-danger` (nem admin) → `admin_post_kmae_delete_account` → `KMAE_Public::admin_delete_account` (nonce `kmae_delete_account`, a „TÖRLÖM” szó beírása kötelező) → `KMAE_Auth::delete_account()`: saját hirdetések `KMAE_Listings::force_delete_listing`, maradék beszélgetések + üzenetek + képek + `kmae_chat_offers`, `kmae_user_profiles` sor, `wp_delete_user` (usermeta vele törlődik). `kmae_reports` bizonyítékként marad; admin fiók tiltott. Siker: login oldal `kmae_success=account_deleted`.
