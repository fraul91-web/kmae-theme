# DEPENDENCY MAP — THEME

## 1. Internal PHP dependency graph

```text
functions.php
  ├─ kmae_page_url()
  │    └─ optionally KMAE_Pages::url() [PLUGIN]
  ├─ page detectors
  │    └─ WordPress query/post/shortcode APIs
  ├─ kmae_body_classes()
  │    └─ page detectors
  ├─ enqueue callbacks
  │    └─ page detectors + auth state
  ├─ kmae_media_url()
  │    └─ optionally KMAE_Brand [PLUGIN]
  ├─ kmae_logo_url()
  │    ├─ KMAE_Brand [PLUGIN]
  │    └─ kmae_media_url()
  ├─ kmae_logo_img()
  │    └─ kmae_logo_url()
  └─ kmae_app_menu_groups()
       ├─ KMAE_Seller [PLUGIN]
       ├─ KMAE_Database [PLUGIN]
       └─ kmae_page_url()

front-page.php
  ├─ header.php
  ├─ guest → template-parts/simple-mode/landing.php
  ├─ logged → template-parts/simple-mode/app-home.php
  └─ footer.php

app-home.php
  ├─ KMAE_Dashboard::get_data() [PLUGIN]
  └─ include KMAE_PLUGIN_DIR/templates/partials/dashboard-home.php [PLUGIN]

header.php
  ├─ kmae_logo_img()
  ├─ kmae_page_url()
  ├─ KMAE_Database::count_unread_conversations() [PLUGIN]
  ├─ KMAE_Icons::render() [PLUGIN]
  └─ app-menu-panels.php
       ├─ kmae_app_menu_groups()
       └─ KMAE_Icons::render() [PLUGIN]
```

## 2. Enqueue dependency graph

### Every request

```text
Google Fonts (`kmae-fonts`)
  ↓
assets/css/theme.css (`kmae-theme`)
```

Then auth split:

```text
LOGGED OUT
kmae-theme
  ↓
simple-mode/landing.css (`kmae-landing`)

LOGGED IN
kmae-theme
  ↓
simple-mode/app.css (`kmae-app`)
  ↓
simple-mode/pc-dashboard.css (`kmae-pc-dashboard`)
  ↓
simple-mode/app-shell-dark.css (`kmae-app-shell-dark`, priority 99)
  ↓
simple-mode/header.css (`kmae-header`)
  ↓
assets/css/home-cards.css (`kmae-home-cards`)
```

`assets/js/theme.js` (`kmae-theme`) is enqueued for every request, footer=true.

### Conditional feature styles

- single listing (logged-in shell function): `single-listing.css` depends on `kmae-header`.
- own listings: `my-listings.css` depends on `kmae-header`.
- messages: `messages.css` (list) or `offer-thread.css` (with `?kmae_conversation`) or `sale-contract.css` (with `?kmae_conversation&kmae_contract=1`), all depend on `kmae-header`.
- profile: `profile.css` depends on `kmae-header`.
- settings: `settings.css` depends on `kmae-header`.
- browse: `browse.css` depends on logged-in `kmae-header` or guest `kmae-landing`.
- how it works: same auth-dependent base.
- FAQ: same auth-dependent base.
- contact: same auth-dependent base.
- listing create: same auth-dependent base.
- Google completion: `google-complete.css` depends on `kmae-public` [PLUGIN] and `kmae-header`, at priority 101.

## 3. Body-class → CSS contract

| Body class | Producer | Primary consumer stylesheet |
|---|---|---|
| `kmae-app` | auth in `kmae_body_classes()` | app/theme/page CSS |
| `kmae-theme-dark` | auth in `kmae_body_classes()` | app shell/header/home cards |
| `kmae-marketing` | logged-out auth | theme/landing CSS |
| `kmae-listing-create-page` | listing-create detector | listing-create.css |
| `kmae-single-listing-page` | singular `kmae_listing` | single-listing.css |
| `kmae-my-listings-page` | own-listings detector | my-listings.css |
| `kmae-browse-page` | browse detector | browse.css |
| `kmae-messages-page` | messages detector | messages.css / offer-thread.css |
| `kmae-faq-page` | FAQ detector | faq.css |
| `kmae-contact-page-view` | contact detector | contact.css |
| `kmae-profile-page` | profile detector | profile.css |
| `kmae-settings-page` | settings detector | settings.css |
| `kmae-complete-page` | Google completion detector | google-complete.css |

## 4. DOM → JavaScript contract

| Markup hook | Owner in theme? | JS behavior |
|---|---|---|
| `#site-header` | `header.php` | scroll class |
| `#kmae-drawer`, backdrop, open/close IDs | `header.php` | drawer state/focus |
| `[data-kmae-dropdown]` + `.kmae-header__panel` | `header.php` | profile dropdown |
| `#site-footer`, toggle, panel | `footer.php` | footer collapse |
| `.kmae-bottom-nav__item` | `footer.php` | exact-path active state |
| `[data-kmae-guest-nav] [data-nav]` | `header.php` | active state |
| `[data-kmae-sheet]`, tabs/panels | not found in theme markup | optional tabs, `UNVERIFIED` |
| `[data-kmae-video-id]`, lightbox IDs | not found in theme markup | video lightbox, likely plugin shortcode markup |

## 5. High-risk coupling points

1. `app-home.php` directly includes a plugin filesystem template via `KMAE_PLUGIN_DIR`; a plugin path/name change can break the logged-in home.
2. Page recognition depends on **both slugs and literal shortcode names**. Renaming either can affect body classes, CSS enqueues and wrapper mode.
3. Many feature styles target DOM emitted outside the theme. Plugin markup class changes can break appearance without changing theme PHP.
4. `header.php` and `kmae_app_menu_groups()` independently calculate active navigation/badges; related changes may need both places.
5. `kmae-unseen` = unread chat count (Üzenetek badge, mobile chat dot); plugin-owned (`count_unread_conversations`).
6. Google-complete CSS depends on plugin enqueue handle `kmae-public`; registration/order is external to theme.
7. Remote production-upload URLs are embedded in PHP and CSS; media relocation can affect multiple files.

## V2 BOUNDARY ROUTING

For diagnosis, do not expand a plugin dependency merely because a theme symbol references a plugin class.

Use `PLUGIN-BOUNDARIES.md` first and cross the boundary only when its **BOUNDARY CHECK** fails on the producer side.

High-value boundaries:
- URL → `KMAE_Pages::url`
- logos → `KMAE_Brand::*`
- unseen badge → `KMAE_Database::count_unread_conversations`
- active listings badge → `KMAE_Seller::count_active_cycle_listings`
- menu icons → `KMAE_Icons::render`
- dashboard → `KMAE_Dashboard::get_data` + plugin partial
- Google completion → `KMAE_Google_Auth::needs_completion`
- shortcode markup/business logic → exact shortcode owner

Theme-side calls are VERIFIED against this snapshot; plugin-side implementations are UNVERIFIED in this pass.
