# FEATURE — LOGGED-IN DASHBOARD HOME BRIDGE

## PURPOSE
Connect theme front page to plugin-provided dashboard data and dashboard partial.

## READ FIRST
- `template-parts/simple-mode/app-home.php`
- `front-page.php`

## THEN IF NEEDED
- `assets/css/simple-mode/app.css`
- `assets/css/simple-mode/pc-dashboard.css`
- `assets/css/home-cards.css`
- plugin map for `KMAE_Dashboard` + dashboard partial.

## DO NOT LOAD UNLESS RELEVANT
- landing page.

## ENTRY POINT
Logged-in branch of `front-page.php`.

## DATA USED
Current user object for fallback; plugin dashboard data for normal path.

## FLOW
Class availability check → fallback OR `get_data()` → direct plugin partial include.

## DEPENDENCIES
Hard filesystem dependency on `KMAE_PLUGIN_DIR/templates/partials/dashboard-home.php`.

## SIDE EFFECTS
None in theme.

## CHANGE RISKS
This is the tightest theme↔plugin template coupling. A variable-name/path/DOM change in the plugin can break dashboard rendering/styling without any theme PHP error elsewhere.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** DASHBOARD HOME BRIDGE  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** logged-in home blank; fallback “frissítsd a bővítményt”; dashboard data/card content wrong.

**READ FIRST:** `front-page.php:8–16` → `template-parts/simple-mode/app-home.php:12–34`.

**EXPECTED FLOW:** front-page auth split → class gate → `KMAE_Dashboard::get_data(uid)` → plugin dashboard partial.

**CHECKPOINTS:** logged-in branch selected? class exists? `$dash` correct? include path/output correct?.

**IF X → GO TO:** bad `$dash` or plugin partial output → plugin boundary; bridge correct but cards visual wrong → dashboard/home CSS.

**SEARCH TERMS:** `KMAE_Dashboard`, `KMAE_PLUGIN_DIR`, `dashboard-home.php`, `kmae-app-home`.

**DO NOT LOAD UNLESS:** landing internals for logged-in-only bug; unrelated feature CSS.

**PLUGIN BOUNDARY:** data and included dashboard template are UNVERIFIED PLUGIN TARGETS.

**CHANGE RISKS:** direct include is theme’s only explicit PHP include boundary.

**REGRESSION CHECKS:** logged-out landing; logged-in fallback; logged-in plugin dashboard; shell wrappers.

**VERIFICATION STATUS:** VERIFIED bridge / UNVERIFIED plugin internals.
