# FEATURE — GOOGLE AUTH COMPLETION PRESENTATION

## PURPOSE
Recognize the special logged-in registration-completion state after Google auth and apply a focused visual override.

## READ FIRST
- `functions.php::kmae_is_google_complete_page()`
- `functions.php::kmae_enqueue_google_complete()`
- `assets/css/google-complete.css`

## THEN IF NEEDED
- `page.php` registration raw-shortcode rendering;
- plugin map for `KMAE_Google_Auth` and register shortcode.

## DO NOT LOAD UNLESS RELEVANT
- Google provider implementation until gate behavior is wrong.

## ENTRY CONDITIONS
All must pass: logged in; `kmae_complete` query parameter present; plugin class exists; `needs_completion(uid)` true; current page is registration slug/shortcode.

## FLOW
Gate → body `kmae-complete-page` → CSS priority 101.

## DEPENDENCIES
`KMAE_Google_Auth` and plugin CSS handle `kmae-public`.

## SIDE EFFECTS
Theme does not complete or save account data.

## CHANGE RISKS
This CSS loads after app shell specifically to win the cascade. Changing priority/dependencies can expose normal app chrome or plugin styles.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** GOOGLE COMPLETE PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** completion screen absent/extra; style missing; wrong post-Google gate.

**READ FIRST:** `functions.php::kmae_is_google_complete_page()` (`201–221`) → `kmae_enqueue_google_complete()` (`459–472`).

**EXPECTED FLOW:** logged-in + query flag + plugin needs-completion + registration route → body class → priority-101 CSS.

**CHECKPOINTS:** each gate predicate true? dependency handles registered? CSS selector matches?.

**IF X → GO TO:** plugin boolean wrong → boundary B07; theme gates true but CSS missing → enqueue/dependency.

**SEARCH TERMS:** `kmae_complete`, `KMAE_Google_Auth`, `kmae-complete-page`, `kmae-google-complete`, `kmae-public`.

**DO NOT LOAD UNLESS:** chat/listing/dashboard code.

**PLUGIN BOUNDARY:** needs-completion and `kmae-public` handle definition are UNVERIFIED PLUGIN TARGETS.

**CHANGE RISKS:** style depends on both plugin handle and theme header handle.

**REGRESSION CHECKS:** completion positive + normal registration negative + logged-out negative.

**VERIFICATION STATUS:** VERIFIED theme gate.
