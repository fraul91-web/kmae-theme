# FLOW MAP — THEME

## Flow 1 — Any WordPress request: theme bootstrap and asset selection

```text
WordPress loads theme/functions.php
  ↓
after_setup_theme
  → kmae_setup()
  ↓
body_class filter
  → kmae_body_classes()
     → auth class
     → route detectors
     → feature body classes
  ↓
wp_enqueue_scripts
  → kmae_assets() [default]
     → fonts
     → theme.css
     → logged-in? app.css → pc-dashboard.css
       else landing.css
     → theme.js
  ↓
wp_enqueue_scripts priority 99
  → kmae_enqueue_app_shell()
     → logged-in only late shell/header/home-card CSS
     → selected logged-in feature CSS
  ↓
priority 100 feature enqueues
  → browse/how/faq/contact/listing-create when detector matches
  ↓
priority 101
  → Google-complete CSS when completion detector matches
```

No theme-side AJAX/REST/backend transaction occurs in this flow.

## Flow 2 — Guest front page

```text
front-page.php
  ↓
get_header()
  → header.php guest path
     → logo helper
     → guest desktop nav + mobile drawer button/login icon
  ↓
is_user_logged_in() == false
  ↓
get_template_part(simple-mode/landing)
  → kmae_media_url() remote imagery
  → kmae_page_url(hirdetesek)
  → kmae_page_url(hirdetes-feladasa)
  → landing sections + CTA links
  ↓
get_footer()
  → collapsible footer (no logged-in bottom nav)
  ↓
theme.js
  → header scroll state
  → drawer
  → guest-nav active state
  → footer collapse
```

## Flow 3 — Logged-in front page / dashboard bridge

```text
front-page.php
  ↓
header.php logged-in path
  ↓
get_template_part(simple-mode/app-home)
  ↓
KMAE_Dashboard exists?
  ├─ no → local fallback greeting + “update plugin” message
  └─ yes
      ↓
      KMAE_Dashboard::get_data(current_user_id) [PLUGIN]
      ↓
      $dash variable prepared
      ↓
      include KMAE_PLUGIN_DIR/templates/partials/dashboard-home.php [PLUGIN]
  ↓
footer.php closes app layout + bottom nav
```

Plugin dashboard data/template internals are `UNVERIFIED` in this phase.

## Flow 4 — Normal WordPress page with Albira shortcode

```text
WordPress selects page.php
  ↓
get_header()
  ↓
first loop pass reads post_content + post_name
  ↓
classify page
  ├─ hub menu
  ├─ listing create
  ├─ my listings
  └─ raw-shortcode page (profile/settings/login/register)
  ↓
rewind_posts()
  ↓
render path
  ├─ hub → the_content() inside .kmae-hub-menu-shell
  ├─ listing-create → do_shortcode(raw post_content)
  ├─ my-listings → the_content()
  └─ generic
      ├─ title + content wrapper
      ├─ raw-shortcode → do_shortcode(raw post_content)
      └─ otherwise → the_content()
  ↓
get_footer()
```

The shortcode implementation itself is plugin-owned.

## Flow 5 — Listing-create theme path

```text
WP page request
  ↓
kmae_is_listing_create_page()
  ├─ slug == hirdetes-feladasa
  └─ OR content contains shortcode kmae_create_listing
  ↓
body class: kmae-listing-create-page
  ↓
priority-100 enqueue: listing-create.css
  ↓
page.php independently recognizes same slug/shortcode
  ↓
direct do_shortcode(raw content)
  ↓
PLUGIN listing-create markup/logic [UNVERIFIED]
  ↓
theme CSS styles expected .kmae-create-*/.kmae-wizard-* DOM
```

Theme does not submit, validate, upload, save, or AJAX-handle the listing in this snapshot.

## Flow 6 — Browse/search theme path

```text
request
  ↓
kmae_is_browse_page()
  ├─ post type archive kmae_listing
  ├─ page slug hirdetesek
  └─ shortcode kmae_browse
  ↓
body class: kmae-browse-page
  ↓
browse.css enqueue
  ↓
rendered browse markup comes from page/plugin/archive layer [PLUGIN side UNVERIFIED]
```

Theme source comment says plugin archive template runs `[kmae_browse]`; plugin implementation is not verified here.

## Flow 7 — Logged-in navigation badge

Two independent theme call sites use plugin unseen activity.

### Desktop/mobile header

```text
header.php
  ↓
logged-in && class_exists(KMAE_Database)
  ↓
KMAE_Database::count_unread_conversations(current_user_id) [PLUGIN]
  ↓
$kmae_unseen
  ├─ mobile notification dot when >0
  └─ desktop “Üzenetek” badge value
```

### Drawer menu model

```text
app-menu-panels.php
  ↓
kmae_app_menu_groups()
  ├─ KMAE_Seller::count_active_cycle_listings() → Hirdetéseim badge
  └─ KMAE_Database::count_unread_conversations() → Üzenetek badge
  ↓
$render(item)
  ↓
.kmae-sheet__badge
```

Exact plugin semantics/data source are unverified.

## Flow 8 — Own listing active navigation

```text
header.php
  ↓
is_singular(kmae_listing)
AND logged-in
AND post_author == current user
  ↓
$kmae_own_listing = true
  ↓
Browse active condition excludes own listing
Hirdetéseim active condition includes own listing
```

No ownership mutation; read-only comparison.

## Flow 9 — Mobile drawer

```text
header.php emits drawer/open/close/backdrop IDs
  ↓
theme.js setDrawerOpen(open)
  ↓
body.kmae-drawer-open toggle
hidden + aria-hidden + aria-expanded updates
focus moves to close button when opened
  ↓
close by close button / backdrop / Escape
```

Logged-in drawer content comes from `app-menu-panels.php`; guest drawer contains three links directly.

## Flow 10 — Desktop profile dropdown

```text
header.php [data-kmae-dropdown]
  ↓
theme.js button click
  ↓
openDropdown(wrap)
  → closes other dropdowns
  → aria-expanded=true
  → panel.hidden=false
  → .is-open
  ↓
outside click or Escape
  → closeAllDropdowns()
```

## Flow 11 — Footer collapse

```text
footer.php emits toggle + hidden panel
  ↓
theme.js setFooterOpen(open)
  ↓
footer .is-open + panel.hidden + aria-expanded + dynamic aria-label
```

## Flow 12 — Guest navigation active state

Server establishes initial active classes with `$kmae_guest_nav_active`.

Client then:

```text
[data-kmae-guest-nav]
  ↓
reads body[data-home] + pathname
  ↓
on home with no hash → Home active
click [data-nav] → clicked key active
after hashchange → only empty hash explicitly resets Home
```

## Flow 13 — Google account completion styling gate

```text
logged-in?
  ↓
?kmae_complete present?
  ↓
KMAE_Google_Auth exists and needs_completion(uid)? [PLUGIN]
  ↓
current page is registration slug/shortcode?
  ↓
body class kmae-complete-page
  ↓
priority-101 google-complete.css
```

Form processing itself is not in the theme.

## Flow 14 — How-it-works YouTube lightbox

```text
how-it-works route detector
  ↓
how-it-works.css
  ↓
PLUGIN/page markup may emit [data-kmae-video-id], #kmae-video-lightbox, #kmae-video-iframe
  ↓
global theme.js delegated click
  ↓
openVideo()
  → youtube-nocookie iframe src
  → lightbox.hidden=false
  → body.kmae-video-open
  ↓
close button / Escape
  → iframe src cleared
  → hidden=true
  → focus restored
```

Markup source is not in theme archive: `UNVERIFIED`.

## Flow 15 — Logo resolution

```text
header.php
  ↓
kmae_logo_img(class)
  ↓
kmae_logo_url(auto)
  ├─ KMAE_Brand exists → KMAE_Brand::logo_guest() [PLUGIN]
  └─ otherwise → kmae_media_url(logo_guest)
                    ↓
               hardcoded albira.hu upload URL
```

Explicit `guest` or `app` variant can call `KMAE_Brand::logo_url(variant)`, but the normal header path uses auto.

## V2 FLOW CHECKPOINTS + STOP/SKIP ROUTING

### Checkpoint A — route gate

1. Does the exact detector return true?
2. Does `kmae_body_classes()` emit the expected body class?
3. Does the expected style handle enqueue?

**If any NO:** remain in `functions.php`; do not load plugin backend or feature CSS wholesale.  
**If all YES:** route/enqueue is not the problem; continue to render/DOM/CSS.

### Checkpoint B — PHP/template vs client presentation

1. Is the correct value/element already present in HTML source?
   - **NO:** PHP/template/plugin producer path.
   - **YES:** do not reopen producer by default.
2. Is DOM state then changed by known theme JS?
   - **YES:** exact named handler.
   - **NO:** CSS/render/cascade.

### Checkpoint C — theme vs plugin owner

A theme flow **stops** when:
- a plugin method has already returned an incorrect business value;
- a shortcode branch is selected correctly but shortcode output/business action is wrong;
- listing wizard behavior/submit/data is broken after theme successfully renders shortcode output;
- dashboard bridge reaches `KMAE_Dashboard::get_data()` / plugin partial and the returned data/markup is wrong.

A theme flow **continues** when:
- body class or style handle is missing;
- wrapper/render branch is wrong;
- theme-owned markup ID/data attribute is missing;
- theme JS state/ARIA/hidden/class update is wrong;
- correct plugin data is displayed incorrectly by theme.

### Checkpoint D — responsive-only symptom

If HTML/classes are equivalent and the failure exists only under/over a breakpoint:
- start with the one active feature stylesheet;
- inspect exact selector + media branch + later overrides;
- **skip data/backend/plugin investigation** until DOM difference proves it necessary.

### Checkpoint E — badge flow

```text
plugin count return
  ↓
theme cast/local variable
  ↓
theme menu model (drawer only)
  ↓
theme markup
  ↓
CSS visibility
```

First wrong checkpoint owns the next search. If plugin return is wrong, stop theme debugging.

### Checkpoint F — optional JS hook flow

`theme.js` sheet-tabs and video-lightbox handlers are only actionable if exact required DOM hooks exist. Theme-wide source search found no theme producer for those hook sets. Missing hooks route outward instead of triggering more theme search.
