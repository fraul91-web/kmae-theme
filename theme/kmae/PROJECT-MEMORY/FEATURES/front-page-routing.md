# FEATURE — FRONT PAGE ROUTING

## PURPOSE
Serve two entirely different front-page contents based on login state.

## READ FIRST
- `front-page.php`.

## THEN IF NEEDED
- guest (2026-10-11): `.kmae-page-wrap` + `[kmae_browse]` (same as `/hirdetesek/`); shell = logged-in look via `functions.php::kmae_is_guest_app_view()` / `kmae_uses_app_shell()` (also guest browse + singular listing); `kmae_is_browse_page()` is true for guest front page; `kmae_is_guest_simple_marketing_shell()` no longer includes front page (plugin assets load);
- logged: `template-parts/simple-mode/app-home.php`;
- shared shell: `header.php`, `footer.php`.

## DO NOT LOAD UNLESS RELEVANT
- `page.php` (front page does not use its branch here);
- unrelated feature CSS.

## ENTRY POINT
WordPress front-page template selection.

## FLOW
`get_header()` → auth test → app-home OR `[kmae_browse]` → `get_footer()`.

## DEPENDENCIES
Logged-in path immediately crosses into plugin dashboard API/template.

## SIDE EFFECTS
None.

## CHANGE RISKS
A front-page issue may actually originate in the shared header/footer or plugin dashboard partial; do not assume `front-page.php` contains presentation logic.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** FRONT PAGE ROUTING  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** wrong home for auth state; logged-in sees landing; guest sees app home.

**READ FIRST:** `front-page.php:8–16`.

**EXPECTED FLOW:** `get_header()` → `is_user_logged_in()` → app-home OR landing → `get_footer()`.

**CHECKPOINTS:** correct template selected? then continue only into selected branch.

**IF X → GO TO:** logged-in app branch problem → dashboard feature; guest branch problem → landing feature.

**SEARCH TERMS:** `front-page.php`, `is_user_logged_in`, `app-home`, `landing`.

**DO NOT LOAD UNLESS:** both branch templates at once unless auth split itself is ambiguous.

**PLUGIN BOUNDARY:** app-home later crosses dashboard plugin boundary.

**CHANGE RISKS:** front page is shared entry for two very different shells/content paths.

**REGRESSION CHECKS:** guest home + logged-in home + shared header/footer.

**VERIFICATION STATUS:** VERIFIED.
