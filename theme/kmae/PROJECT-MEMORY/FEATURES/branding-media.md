# FEATURE — BRANDING + MEDIA RESOLUTION

## PURPOSE
Centralize logo rendering and most landing image URLs while allowing a plugin brand provider to override logo URLs.

## READ FIRST
- `functions.php::kmae_media_url()`
- `functions.php::kmae_logo_url()`
- `functions.php::kmae_logo_img()`

## THEN IF NEEDED
- `header.php` logo call sites;
- `landing.php` media keys;
- `CSS-MAP.md` for additional hardcoded CSS images.

## DO NOT LOAD UNLESS RELEVANT
- plugin DB/feature logic.

## ENTRY POINTS
Header rendering and landing template construction.

## DATA USED
Hardcoded production upload URLs; optional `KMAE_Brand` methods.

## FLOW
Header → logo_img → logo_url → plugin brand provider or PHP fallback. Landing → media_url(key) → remote image URL.

## DEPENDENCIES
Remote `albira.hu/wp-content/uploads` availability; optional plugin brand provider.

## SIDE EFFECTS
None.

## CHANGE RISKS
Not all visual assets are centralized here: CSS files also contain hardcoded production-upload URLs. A domain/media migration must search both PHP and CSS.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** BRANDING MEDIA  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** wrong logo variant; wrong hosted image URL; asset works in one shell but not another.

**READ FIRST:** `functions.php::kmae_media_url()` (`559–586`) → `kmae_logo_url()` (`595–605`) → `kmae_logo_img()` (`612–619`).

**EXPECTED FLOW:** media key / logo variant → optional `KMAE_Brand` provider → fallback hosted URL → emitted `src`.

**CHECKPOINTS:** helper return correct? → emitted `src` correct? → remote asset reachable?.

**IF X → GO TO:** wrong plugin-provider return → `PLUGIN-BOUNDARIES.md` B02; correct URL but visual size/style wrong → exact logo/image CSS selector.

**SEARCH TERMS:** `kmae_media_url`, `kmae_logo_url`, `logo_guest`, `logo_app`, `KMAE_Brand`, exact media key.

**DO NOT LOAD UNLESS:** navigation, dashboard, feature business logic.

**PLUGIN BOUNDARY:** `KMAE_Brand::*` plugin implementation is UNVERIFIED; remote media hosting is external.

**CHANGE RISKS:** central URL table affects multiple landing sections; `logo` is intentionally set to guest logo.

**REGRESSION CHECKS:** all changed media keys; guest/logged-in header logo; plugin-present and fallback logo.

**VERIFICATION STATUS:** VERIFIED theme helpers / UNVERIFIED plugin provider.
