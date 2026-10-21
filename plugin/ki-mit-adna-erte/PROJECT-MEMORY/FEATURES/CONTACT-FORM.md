# FEATURE — Public contact form

## PURPOSE
Publikus kapcsolatfelvételi űrlap spam-gate, validáció és e-mail küldés.

## READ FIRST
- `public/class-kmae-public.php`
- `templates/contact.php`
- `includes/class-kmae-settings.php`

## THEN IF NEEDED
- `includes/class-kmae-brand.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
`[kmae_contact]` → `templates/contact.php` → admin-post `kmae_contact_form` (also nopriv).

## IMPORTANT SYMBOLS
`KMAE_Public::admin_contact_form`, `KMAE_Settings::contact_form_email`, `KMAE_Brand::send_mail`.

## DATA USED
Posted name/email/topic/message + honeypot/timing inputs; destination option `kmae_contact_form_email` (default `info@albira.hu`; a stored `fraul91@gmail.com` is rewritten to that address on read).

## FLOW
POST → nonce/spam gate/validation → compose message → brand mail → redirect with success/error.

## DEPENDENCIES
WordPress mail stack and configured destination.

## SIDE EFFECTS
Sends external e-mail; no custom DB record found for contact submissions.

## CHANGE RISKS
Keep nopriv path spam controls in place if changing form routing.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-photo-motor.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-listing-drafts.php`
- `includes/class-kmae-listing-wizard.php`
- `includes/class-kmae-moderation.php`
- `includes/class-kmae-meta.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-database.php`
- `includes/class-kmae-live-page.php`
- `includes/class-kmae-icons.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-company.php`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-query.php`
- `includes/class-kmae-seller.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Public contact  
**COMMON SYMPTOMS:** validate form → configured recipient → wp_mail.  
**READ FIRST (symbol-level):** `KMAE_Public::admin_contact_form()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `admin_contact_form contact_form_email`.  
**DO NOT LOAD UNLESS:** chat notifications.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
