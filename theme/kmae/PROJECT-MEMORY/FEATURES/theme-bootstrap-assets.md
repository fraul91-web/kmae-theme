# FEATURE — THEME BOOTSTRAP + ASSET ORCHESTRATION

## PURPOSE
Initialize WordPress theme capability and construct deterministic guest/logged-in/feature CSS layers.

## READ FIRST
- `functions.php`: constants + `kmae_setup()`, `kmae_assets()`, `kmae_enqueue_app_shell()` and feature enqueue functions.

## THEN IF NEEDED
- `CSS-MAP.md`
- the specific feature stylesheet.

## DO NOT LOAD UNLESS RELEVANT
- landing template content;
- plugin shortcode implementations;
- feature CSS unrelated to the reported route.

## ENTRY POINTS
- `after_setup_theme` → `kmae_setup()`.
- `wp_enqueue_scripts` → one global callback plus late callbacks at priorities 99/100/101.

## PRIMARY FILES
- `functions.php`
- `style.css`

## IMPORTANT SYMBOLS
- `kmae_asset_version_src()` — `style_loader_src` / `script_loader_src` filter: theme asset `ver` = filemtime (automatic cache-busting; `KMAE_THEME_VERSION` no longer drives theme asset URLs). Plugin counterpart: `kmae_plugin_asset_version_src()` in `ki-mit-adna-erte.php`.
- constants `KMAE_THEME_VERSION`, `KMAE_VERSION`, `KMAE_DIR`, `KMAE_URI`, `KMAE_CSS_BASE`.
- `kmae_setup()`.
- `kmae_assets()`.
- all `kmae_enqueue_*()` callbacks.

## DATA USED
Auth state, query route detectors, theme URI, hardcoded CSS base.

## FLOW
Global fonts + `theme.css` → auth baseline (`landing.css` or `app.css`+`pc-dashboard.css`) → logged-in shell priority 99 → route CSS priority 100 → Google-complete priority 101.

## DEPENDENCIES
WordPress enqueue system; plugin `kmae-public` handle only for Google-complete.

## SIDE EFFECTS
Enqueues styles/scripts and registers theme supports only.

## CHANGE RISKS
- changing handle names can break dependency ordering;
- changing detector logic affects both body classes and CSS loading;
- runtime theme version is used for cache-busting and differs from `style.css` metadata version.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** THEME BOOTSTRAP ASSETS  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** global styles/scripts absent; wrong auth CSS stack; many interactions fail simultaneously.

**READ FIRST:** `functions.php::kmae_assets()` (`319–371`) then `kmae_enqueue_app_shell()` (`378–452`) if logged in.

**EXPECTED FLOW:** WP enqueue hook → fonts + theme.css → guest landing OR logged-in app/pc → global theme.js → priority feature layers.

**CHECKPOINTS:** handle registered/enqueued? auth branch? dependency order? script loads/executed?.

**IF X → GO TO:** all global JS controls fail → script load/runtime; only one feature visual → its feature router.

**SEARCH TERMS:** `wp_enqueue_scripts`, `kmae-theme`, `kmae-landing`, `kmae-app`, `kmae-pc-dashboard`, `KMAE_THEME_VERSION`.

**DO NOT LOAD UNLESS:** plugin business logic for global asset failure.

**PLUGIN BOUNDARY:** Google-complete additionally depends on plugin style handle `kmae-public`.

**CHANGE RISKS:** global handle/dependency changes fan out to every page; runtime version differs from style.css metadata.

**REGRESSION CHECKS:** guest+logged base stacks; theme.js; one conditional feature; cache/version behavior.

**VERIFICATION STATUS:** VERIFIED.
