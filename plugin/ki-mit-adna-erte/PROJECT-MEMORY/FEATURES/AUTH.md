# FEATURE — Password auth, registration and email verification

## PURPOSE
Saját login/regisztráció/jelszó-visszaállítás, account type, e-mail megerősítés és jogosultsági gate.

## READ FIRST
- `includes/class-kmae-auth.php`
- `public/class-kmae-public.php`
- `templates/form-login.php`
- `templates/form-register.php`

## THEN IF NEEDED
- `templates/partials/email-verify-gate.php`
- `templates/partials/google-button.php`
- `assets/js/auth-account-type.js`
- `assets/js/phone-field.js`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
- `[kmae_login]`, `[kmae_register]`
- admin-post login/register/lost/reset routes (guest and logged-in variants where registered)
- Terms consent: ONE checkbox `kmae_terms` (register + Google completion) = „ÁSZF és Felhasználási Feltételek” (single document `templates/terms.php`, page slug `feltetelek`). On success `KMAE_Legal::record_acceptance()` stores user meta `kmae_terms_accepted` {version, at UTC}. Version/effective date + operator (impresszum) data live in `includes/class-kmae-legal.php`; `[…]` placeholders trigger an admin-only notice on the page.
- Re-acceptance: `KMAE_Legal::init()` → logged-in users with missing/old `kmae_terms_accepted.version` get a blocking one-time modal (`render_prompt` in wp_footer, `assets/css/terms-accept.css` + `assets/js/terms-accept.js`, AJAX `kmae_accept_terms`, nonce `kmae_accept_terms`). Skipped during Google completion and on the terms/privacy pages. Bump `TERMS_VERSION` to ask everyone again.
- Privacy notice: `templates/privacy.php` (slug `adatvedelem`, shortcode `kmae_privacy`) — data-processing table; same version/effective date + `operator()` (incl. `mail_provider`). Update it when a new data processing is added.
- `init` e-mail verification link handler
- `map_meta_cap`, password URL/message filters

## IMPORTANT SYMBOLS
- `KMAE_Auth::register`
- `KMAE_Auth::login`
- `KMAE_Auth::request_password_reset`
- `KMAE_Auth::reset_password_with_key`
- `KMAE_Auth::generate_email_verify_token`
- `KMAE_Auth::verify_email_link`
- `KMAE_Auth::mark_email_verified`
- `KMAE_Auth::should_gate_for_verification`
- `KMAE_Public::{admin_register,admin_login,admin_lost_password,admin_reset_password,handle_forms}`

## DATA USED
Adatvédelem: `includes/class-kmae-privacy.php` (`KMAE_Privacy`) adminon kívül lezárja a WP user-ID/felhasználónév szivárgást: `/wp/v2/users*` REST, REST `author` mező, `?author=N`/`/author/` → 404, users sitemap, oEmbed szerző; egyszeri LiteSpeed purge (`kmae_privacy_cache_purged`).

WP user; user meta `kmae_phone`, `kmae_account_type`, verify token/sent; custom `kmae_user_profiles`.

## FLOW
See `../FLOWS.md` section 3.

## DEPENDENCIES
Company registration uses `KMAE_Company` and `KMAE_Counties`. E-mail uses `KMAE_Brand`. Core create-listing / start-conversation gate is in `KMAE_Public::handle_forms()`.

## SIDE EFFECTS
Creates WP users, profile table rows, meta, auth sessions, verification email. Registration no longer asks for email opt-in; `email_offers` and `email_news` are stored as 1. Per-mail preferences are `KMAE_Notifications::allows_mail()` (settings page).

## CHANGE RISKS
Do not change verification semantics in only the UI: server gate lives in `handle_forms()`. Account type exists in both user meta/custom profile compatibility paths, so inspect `get_account_type()` before changing storage.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-pages.php`
- `includes/class-kmae-brand.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-counties.php`
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


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Password auth and verification  
**COMMON SYMPTOMS:** login/register/reset/verification gates.  
**READ FIRST (symbol-level):** `public/class-kmae-public.php :: admin_login()/admin_register()`; `includes/class-kmae-auth.php :: verification/reset helpers`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `login, register, lost password, verify link, resend verification`.  
**DO NOT LOAD UNLESS:** Google OAuth unless Google button/callback involved.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
