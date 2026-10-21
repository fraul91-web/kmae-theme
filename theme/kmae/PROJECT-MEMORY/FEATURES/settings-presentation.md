# FEATURE — SETTINGS PRESENTATION

## PURPOSE
Detect settings route, render shortcode without normal content filters, and apply settings page presentation.

## READ FIRST
- `functions.php::kmae_is_settings_page()`
- `page.php` raw-shortcode classification
- `assets/css/settings.css`

## THEN IF NEEDED
- plugin map for `kmae_settings`.

## DO NOT LOAD UNLESS RELEVANT
- profile implementation.

## ENTRY POINT
Slug `beallitasok` or shortcode `kmae_settings`.

## FLOW
Detector → body class + settings.css → direct shortcode execution → plugin DOM.

## DATA USED
No settings values are read/written by theme.

## SIDE EFFECTS
None.

## CHANGE RISKS
All save/toggle/security logic is plugin-owned; theme only renders wrapper/style.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** SETTINGS PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** settings layout broken; save behavior fails; raw wrapper issue.

**READ FIRST:** `kmae_is_settings_page()` (`223–238`) → app-shell settings CSS branch `443–450`; `page.php` raw-shortcode branch.

**EXPECTED FLOW:** settings slug/shortcode → body/style → raw shortcode output → plugin settings DOM/action.

**CHECKPOINTS:** route? raw branch? body/style? visual vs save/data?.

**IF X → GO TO:** save/data → plugin; visual → settings.css; wrapper → page.php.

**SEARCH TERMS:** `beallitasok`, `kmae_settings`, `kmae-settings-page`, `kmae-settings`, `$is_raw_shortcode`.

**DO NOT LOAD UNLESS:** chat/listing code; DB until plugin handoff.

**PLUGIN BOUNDARY:** settings shortcode/save UNVERIFIED PLUGIN TARGET.

**CHANGE RISKS:** desktop-focused settings CSS can create viewport-specific failures.

**REGRESSION CHECKS:** settings page render + relevant desktop breakpoint + raw auth/profile pages if shared branch changed.

**VERIFICATION STATUS:** VERIFIED theme rendering.
