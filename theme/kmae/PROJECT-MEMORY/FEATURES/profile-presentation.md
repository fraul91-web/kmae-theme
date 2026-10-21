# FEATURE — PROFILE PRESENTATION

## PURPOSE
Detect profile route, bypass `the_content()` auto-formatting, and style plugin profile markup.

## READ FIRST
- `functions.php::kmae_is_profile_page()`
- `page.php` raw-shortcode classification
- `assets/css/profile.css`

## THEN IF NEEDED
- plugin map for `kmae_profile`.

## DO NOT LOAD UNLESS RELEVANT
- settings CSS despite visual similarity.

## ENTRY POINT
Slug `profil` or shortcode `kmae_profile`.

## FLOW
Detector → body class + profile.css → page.php direct `do_shortcode(raw content)` → plugin profile DOM.

## DATA USED
No profile data read directly by theme.

## DEPENDENCIES
Plugin markup/processing.

## SIDE EFFECTS
None in theme.

## CHANGE RISKS
`page.php` explicitly avoids `wpautop` because grid/glass markup can be disrupted; replacing direct shortcode execution with normal `the_content()` is a structural change.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** PROFILE PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** profile layout wrong; raw shortcode wrapper issue; Save/data behavior wrong.

**READ FIRST:** `kmae_is_profile_page()` (`180–195`) → app-shell profile CSS branch `434–441`; `page.php` raw-shortcode branch.

**EXPECTED FLOW:** profile slug/shortcode → body/style → raw shortcode output → plugin form DOM → theme CSS.

**CHECKPOINTS:** raw branch? body/style? DOM correct? save/data vs visual?.

**IF X → GO TO:** save/data → plugin boundary; visual → profile.css; wrapper issue → page.php.

**SEARCH TERMS:** `profil`, `kmae_profile`, `kmae-profile-page`, `kmae-profile`, `$is_raw_shortcode`.

**DO NOT LOAD UNLESS:** global theme JS for server-side save unless exact event ownership found.

**PLUGIN BOUNDARY:** profile shortcode/form/save UNVERIFIED PLUGIN TARGET.

**CHANGE RISKS:** raw render avoids `wpautop`; changing to `the_content()` can break grid children.

**REGRESSION CHECKS:** profile raw output + CSS at supported viewport + ordinary page raw behavior unchanged.

**VERIFICATION STATUS:** VERIFIED theme rendering.
