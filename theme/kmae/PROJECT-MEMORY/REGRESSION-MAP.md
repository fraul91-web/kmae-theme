# REGRESSION ROUTING — THEME V2

**MAP-TYPE:** ON-DEMAND  
Use this after a source change has been localized. Checks are deliberately concrete.

| CHANGED | VERIFY AFTER CHANGE | WHY |
|---|---|---|
| `functions.php::kmae_page_url()` | guest header links; logged-in desktop links; drawer links; footer links; bottom-nav hrefs; landing CTAs | shared URL fan-out |
| any `kmae_is_*_page()` detector | detector result; matching body class; matching CSS enqueue; any `page.php` branch that duplicates the same slug/shortcode logic | detector logic is duplicated across routing/rendering in places |
| `kmae_body_classes()` | auth base classes + exact changed feature class on positive and negative route | CSS scopes depend on body state |
| `kmae_assets()` | guest: fonts/theme/landing; logged-in: fonts/theme/app/pc-dashboard; global `theme.js`; dependency order | all requests |
| `kmae_enqueue_app_shell()` | logged-in base app shell; single-listing; my-listings; offers; profile; settings; confirm guest early return remains | broad late cascade |
| `kmae_enqueue_google_complete()` | completion route has `kmae-public` + `kmae-header` dependency satisfied; ordinary registration route does not load it | special priority-101 dependency |
| `page.php` classifier | `eladni` hub; listing-create; my-listings; profile; settings; login/register; ordinary page | mutually exclusive wrapper/render branches |
| `page.php` raw shortcode branch | profile/settings/login/register DOM has no unwanted `wpautop` wrappers; ordinary page still uses `the_content()` | direct/raw vs filtered rendering |
| `front-page.php` auth split | logged-out `[kmae_browse]` on app shell; logged-in app-home; shared header/footer both states | auth-based template fork |
| `app-home.php` plugin bridge | class-missing fallback; class-present `get_data`; direct plugin partial include | plugin contract |
| `header.php::$kmae_nav_active` or nav array | Browse; Üzenetek; Hirdetéseim; own listing; link URLs | desktop active-state model |
| `header.php::$kmae_own_listing` | own singular listing → Hirdetéseim active/Browse inactive; other user's listing → no false ownership | ownership exception |
| `$kmae_unseen` render in `header.php` | zero hides desktop badge/mobile dot; positive shows both; compare drawer unseen badge | same producer rendered in multiple surfaces |
| `kmae_app_menu_groups()` | every drawer URL; path active-state; active listing count; unread chat count on Üzenetek; info group | shared drawer model |
| `app-menu-panels.php::$render` | active class, icon, label, empty/nonempty badge for both `nav` and `info` groups | renderer reused for all drawer items |
| `header.php` app-layout opening tags | logged-in DOM nesting through footer; guest has no app wrapper | cross-file tag pair |
| `footer.php` app-layout closing tags | same as above + `<main>` exactly closed once | cross-file tag pair |
| `theme.js::setDrawerOpen()` | open button; close button; backdrop; Escape; `hidden`; `aria-hidden`; `aria-expanded`; focus | one handler controls all drawer state |
| `theme.js` dropdown functions | open; toggle-close; outside click; Escape; ARIA + hidden + `.is-open` | shared dropdown state |
| `theme.js::setFooterOpen()` | click open/close; Escape; panel hidden; expanded state; dynamic aria-label | accessibility + visibility state |
| bottom-nav block `theme.js:162–177` | `/`; `/eladni/`; `/venni/`; `/statisztikak/`; a nested/nonexact path | exact-path behavior is intentional |
| guest-nav block `theme.js:179–211` | guest home no hash; guest internal page; click state; hash removal resets Home | server+client active-state combination |
| lightbox block `theme.js:214–270` | page without lightbox does not affect earlier UI handlers; page with required hooks: open/close/Escape/src clearing/focus | guarded optional feature |
| `kmae_media_url()` | every changed media key; `logo` remains mapped to guest logo; remote URL output | centralized hosted media table |
| `kmae_logo_url()` / `kmae_logo_img()` | guest header, logged-in header, fallback without `KMAE_Brand`, plugin-provided logo path | plugin/fallback contract |
| `assets/css/theme.css` broad rule | guest + logged-in, mobile + desktop, exact global component changed | global CSS |
| `landing.css` broad rule | landing + guest inner page because file loads for all guests | guest-wide CSS |
| feature CSS selector | same feature at failing viewport + one adjacent breakpoint; confirm another feature sharing generic class is unaffected | targeted presentation risk |

## Change-risk stop rule

If a change touches a **plugin-produced value or plugin-produced DOM**, the theme regression list only covers theme consumption. Do not claim plugin behavior is regression-safe without plugin source/tests.
