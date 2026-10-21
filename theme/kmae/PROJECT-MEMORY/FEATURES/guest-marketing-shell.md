# FEATURE — GUEST MARKETING SHELL

## PURPOSE
Provide logged-out header/navigation/drawer, guest CSS baseline and footer around landing/auth/public pages.

## READ FIRST
- `header.php` guest branch
- `footer.php`
- `assets/css/simple-mode/landing.css`
- `assets/js/theme.js` guest-nav/drawer/footer sections

## THEN IF NEEDED
- `functions.php::kmae_assets()` and `kmae_page_url()`.

## DO NOT LOAD UNLESS RELEVANT
- logged-in app CSS;
- plugin auth implementation unless form content/processing is the issue.

## ENTRY POINT
Any logged-out request using the theme shell.

## DATA USED
Front-page/page/path state and logical page URLs.

## FLOW
Guest header server-active state → JS active-state helper → page body → collapsible footer.

## DEPENDENCIES
Auth/login/register form markup is plugin-facing, but visual styling is in guest `landing.css`.

## SIDE EFFECTS
None beyond DOM/ARIA UI state.

## CHANGE RISKS
`landing.css` is loaded on all logged-out pages, so a broad selector change can affect auth/contact/browse pages, not only the front landing.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** GUEST MARKETING SHELL  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** guest header/drawer/footer/nav wrong; guest inner page inherits surprising landing style.

**READ FIRST:** `header.php` guest branches + `footer.php` + `functions.php::kmae_assets()` + `assets/js/theme.js` guest/drawer/footer handlers.

**EXPECTED FLOW:** logged-out body → marketing class + landing.css → guest header/nav/drawer → global JS.

**CHECKPOINTS:** auth/body/style correct? server nav active class? client guest-nav/drawer state correct?.

**IF X → GO TO:** wrong links → `kmae_page_url`; visual only → landing/theme CSS; missing drawer behavior → R04.

**SEARCH TERMS:** `kmae-marketing`, `kmae-landing`, `data-kmae-guest-nav`, `$kmae_guest_nav_active`, `kmae-drawer`.

**DO NOT LOAD UNLESS:** logged-in dashboard/app CSS unless auth state is wrong.

**PLUGIN BOUNDARY:** page URL helper may delegate to plugin; guest shell markup/JS itself is theme-owned.

**CHANGE RISKS:** `landing.css` loads for every guest page, not only front page.

**REGRESSION CHECKS:** guest front + guest inner page + drawer + nav active + footer.

**VERIFICATION STATUS:** VERIFIED.
