# FEATURE — Google OAuth login

## PURPOSE
Google authorization-code login, user resolution/linking, incomplete account completion gate.

## READ FIRST
- `includes/class-kmae-google-auth.php`
- `templates/partials/google-button.php`
- `templates/partials/google-complete.php`

## THEN IF NEEDED
- `includes/class-kmae-auth.php`
- `includes/class-kmae-settings.php`
- `public/class-kmae-public.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
- `?kmae_google=start`
- callback `?kmae_google=callback`
- admin-post `kmae_google_complete`
- `template_redirect` completion enforcement

## IMPORTANT SYMBOLS
`KMAE_Google_Auth::{start_url,maybe_handle_request,redirect_to_google,handle_callback,fetch_profile,resolve_user,handle_complete,enforce_completion}`.

## DATA USED
Options `kmae_google_client_id`, `kmae_google_client_secret`; transient `kmae_google_<state>`; user meta `kmae_google_sub`, `kmae_given_name`, `kmae_google_pending`, `kmae_public_name_set`; custom profile table. Google persists/displays only `given_name` (fallback: first word of full name) into `kmae_given_name` + `display_name` while `kmae_public_name_set` is empty — never stores family/full name; company completion keeps company name. Profile save sets that flag and the displayed name wins on listings and greetings.

## FLOW
See `../FLOWS.md` section 4.

## DEPENDENCIES
Google endpoints + WordPress HTTP API + `KMAE_Auth::mark_email_verified()` + company/profile completion.

## SIDE EFFECTS
May link Google identity to existing e-mail account or create a subscriber; sets auth cookie; marks e-mail verified.

## CHANGE RISKS
OAuth state, callback URI, pending-profile enforcement and account linking are one flow; changing only one branch can strand users.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-pages.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-counties.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Google OAuth  
**COMMON SYMPTOMS:** OAuth start/callback/account completion.  
**READ FIRST (symbol-level):** `includes/class-kmae-google-auth.php :: maybe_handle_request()/handle_callback()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `google callback redirect_uri enforce_completion`.  
**DO NOT LOAD UNLESS:** password auth unless linking conflict.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## Account type on Google signup (2026-10-03)
`KMAE_Auth::set_account_type()` writes the profile row AND user meta. Google login (`mark_email_verified`) creates a `private` profile row before completion; `get_account_type()` reads the row first, so `handle_complete` must call `set_account_type` (after validation, before `KMAE_Company::save_profile_fields`) — otherwise a company chosen in the completion form stays `private` and company data is dropped.
