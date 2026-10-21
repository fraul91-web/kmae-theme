# EXTERNAL INTEGRATIONS

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

## Google OAuth
Source: `includes/class-kmae-google-auth.php`
- Authorization endpoint: Google accounts authorization endpoint constant.
- Token exchange: Google OAuth token endpoint via WordPress `wp_remote_post`.
- Config: `kmae_google_client_id`, `kmae_google_client_secret` options.
- State: transient with 15-minute expiry.
- Redirect URI: home URL query `kmae_google=callback`.

Runtime availability and Google Console configuration are **UNVERIFIED**.

## OpenAI Responses API (forgalmi engedély beolvasása)
Source: `includes/class-kmae-registration-scan.php` :: `KMAE_Registration_Scan::ajax_scan()`.
- Wizard only (offer modal when `kmae:wizard-ready` detail.offer: fresh start, or resumed draft still in phase `auto` with <3 vehicle fields filled; admins see `.kmae-regscan-admin-note` in the wizard aside when disabled/no key; sidebar button `[data-kmae-regscan-open]` while `data-phase="auto"`). Client `assets/js/registration-scan.js` crops the green document in canvas (port of `teszt/forgalmi-olvaso/server.mjs`), uploads 1–2 JPEG blobs as `kmae_scan_images[]` to AJAX `kmae_registration_scan` (nonce `kmae_registration_scan`).
- Server → `https://api.openai.com/v1/responses` (`store:false`, strict json_schema; no name/address/birth data. `ID_FIELDS` rendszam/alvazszam/forgalmi_szam are read too, cleaned by `clean_id()`, returned as `ids` — NOT shown in the review step, not part of the fallback decision; `registration-scan.js collect()` passes them to `applyVehicleData` → `listing-wizard.js setRegistrationIds()` hidden `kmae_registration_ids[...]` + draft payload `vehicleIds` → `KMAE_Listings` save → `save_ids_from_post()` → post meta `_kmae_registration_ids` (only non-empty values write) → read by `KMAE_Sale_Contract::prefill()` via `get_ids()`). Primary model option `kmae_scan_model` (default gpt-4.1-mini), fallback `kmae_scan_fallback_model` (gpt-4.1) only when a field is missing or ≥2 uncertain. Timeout 45 s/call. If 2 images and the MOT date is still empty → targeted `extract_mot()` call (MOT-only prompt/schema, roman-numeral months via `normalize_mot()`). Client crop only when the green paper covers ≥30% of the photo. Review view shows remaining MOT validity vs. today (`paintMotLeft`).
- Key: wp-config `KMAE_OPENAI_API_KEY` or option `kmae_openai_api_key` (Albira → Beállítások; empty submit keeps the saved key). Toggle `kmae_scan_enabled`. Limit 10 scans/user/day (transient `kmae_scan_u<user>_<Ymd>`, UTC day; admins exempt; OpenAI failures refunded). Images never stored.
- Result mapped to Albira values (`to_albira`: catalog brand/model incl. Mercedes „C 220 D”→C-Class, BMW „320D”→3 Series; fuel slug; gear code 0→manual else automatic; kW→LE; MOT date → mot_has/yes+date or no). Accepted data → `window.kmaeWizard.applyVehicleData()` (listing-wizard.js) marks steps completed (`state.prefilled`, skipped on Tovább) and jumps to the first missing step.

Runtime availability / model names / costs: **UNVERIFIED**.

## OpenStreetMap Nominatim (reverse geocoding)
Source: `includes/class-kmae-counties.php` :: `KMAE_Counties::ajax_locate()` / `match_county()`.
- Wizard „Jelenlegi helyzetem megadása” button (`listing-form-layout.php`, `[data-kmae-locate]`) → browser `navigator.geolocation` → AJAX `kmae_locate` (nonce `KMAE_Counties::LOCATE_NONCE`, localized as `kmaeListingLocate.locateNonce`; script `assets/js/listing-locate.js`, enqueued for every create page — wizard AND simple editor; markup rendered for all non-company users).
- Server calls `https://nominatim.openstreetmap.org/reverse` via `wp_remote_get` (User-Agent: Albira + home URL); result `{county slug, city}`.
- Cache: transient `kmae_locate_<md5(lat3,lon3)>` 30 days; throttle: transient `kmae_locate_u<user>` 3 s. Rejects coordinates outside Hungary's bounding box.

Runtime availability / rate limits of Nominatim: **UNVERIFIED**.

## Web Push (browser vendor push services)
Source: `includes/class-kmae-push.php`.
- Outbound `wp_remote_post` to the subscription endpoint (FCM / Mozilla autopush / Apple) with VAPID `Authorization` and `aes128gcm` body. Free; no account. Runs after the response is closed.
- iOS: only for home-screen web apps (16.4+). Runtime delivery **UNVERIFIED**.

## E-mail
Source: `includes/class-kmae-brand.php`, `includes/class-kmae-notifications.php`, `includes/class-kmae-auth.php`, public contact handler.
- Delivery through WordPress `wp_mail`.
- `KMAE_Brand` wraps branded HTML and filters mail headers to HTML.
- User e-mails go through `KMAE_Notifications::send_offer_email()` (historical name) and respect `allows_mail()`.

Actual SMTP/mail provider is outside this ZIP: **UNVERIFIED**.

## YouTube
Source: `includes/class-kmae-settings.php` and education rendering path.
- Seller/buyer URLs are WP options.
- Sanitizer accepts YouTube/youtu.be/embed/short/live patterns and extracts video ID.

## Remote Albira assets
`KMAE_Vehicle` contains a remote logo base on `albira.hu`; branded e-mail code also references site-hosted image URLs. Network availability is **UNVERIFIED**.

## Local static data
- `assets/data/vehicle-catalog.json`
- `assets/data/counties-paths.json`
- `assets/svg/hungary-counties.svg`
No external catalog API is required by the inspected source for these datasets.
