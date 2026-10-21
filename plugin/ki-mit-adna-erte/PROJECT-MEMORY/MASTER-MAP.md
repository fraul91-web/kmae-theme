# PROJECT MEMORY — PLUGIN MASTER MAP

Scope: **plugin only** (`ki-mit-adna-erte`, source version `4.60.0`). Theme implementation is outside this map.

## Architecture in 60 seconds

`ki-mit-adna-erte.php` defines runtime constants, loads `KMAE_Loader`, registers activation/deactivation hooks, then `kmae_run()` creates `KMAE_Plugin` and calls `run()`. `KMAE_Plugin::run()` initializes core services (upgrade, brand, auth, Google auth, notifications, listings, drafts, images, dashboard), registers CPT/taxonomy/meta, then loads the public controller, shortcodes, pages and navigation. Admin code is instantiated only in wp-admin.

Persistence is split across WordPress posts/postmeta/usermeta/options/transients and four custom tables: `kmae_user_profiles`, `kmae_private_data` (hidden minimum price, kept), `kmae_conversations`, `kmae_conversation_messages`. The public interaction layer is mostly normal POST/admin-post plus authenticated WP AJAX actions. The source registers **no custom REST route**; `kmae_listing` and `kmae_category` opt into WordPress REST through `show_in_rest`.

## Business core (2026-10-10)

The whole offer engine (cash / official / counter offers, accept / reject / withdraw, deals, 7/8-day reminders, contact unlock, legacy star ratings, `KMAE_History` profile statistics) was **deleted** — classes, templates, CSS, JS, emails, cron, and its DB tables (`KMAE_Activator::remove_offer_system()`, option `kmae_offer_system_removed`, which also wiped every test conversation). Only the **chat** remains: see [`FEATURES/CHAT.md`](FEATURES/CHAT.md).

- Listing CTA „Üzenet küldése” → `start_conversation` → `/uzenetek/?kmae_conversation=ID`.
- `/uzenetek/` = conversation list; `/ertesitesek/` page renders empty (menu item kept).
- Price modes (Fix ár / Irányár / Ki mit adna érte) and the hidden minimum field stay as listing data only; nothing filters by them any more.
- Listing status `closed` („Áll az alku”) / `archived` is no longer set automatically; only admin status change or owner delete.

## Feature routing

| Problem area | Read feature map | First source files |
|---|---|---|
| Bootstrapping, lifecycle and architecture | [`FEATURES/BOOT-ARCHITECTURE.md`](FEATURES/BOOT-ARCHITECTURE.md) | `ki-mit-adna-erte.php`, `includes/class-kmae-loader.php`, `includes/class-kmae-plugin.php` |
| Pages, routing, shortcodes and templates | [`FEATURES/PAGES-ROUTING.md`](FEATURES/PAGES-ROUTING.md) | `includes/class-kmae-pages.php`, `public/class-kmae-shortcodes.php`, `includes/class-kmae-post-types.php` |
| Password auth, registration and email verification | [`FEATURES/AUTH.md`](FEATURES/AUTH.md) | `includes/class-kmae-auth.php`, `public/class-kmae-public.php`, `templates/form-login.php` |
| Google OAuth login | [`FEATURES/GOOGLE-AUTH.md`](FEATURES/GOOGLE-AUTH.md) | `includes/class-kmae-google-auth.php`, `templates/partials/google-button.php`, `templates/partials/google-complete.php` |
| Profile, company accounts and user settings | [`FEATURES/PROFILE-COMPANY-SETTINGS.md`](FEATURES/PROFILE-COMPANY-SETTINGS.md) | `includes/class-kmae-company.php`, `templates/form-profile.php` |
| Listing creation, editing and lifecycle | [`FEATURES/LISTINGS.md`](FEATURES/LISTINGS.md) | `public/class-kmae-public.php`, `includes/class-kmae-listings.php`, `includes/class-kmae-meta.php` |
| Listing wizard and autosaved drafts | [`FEATURES/LISTING-WIZARD-DRAFTS.md`](FEATURES/LISTING-WIZARD-DRAFTS.md) | `assets/js/listing-wizard.js`, `includes/class-kmae-listing-wizard.php`, `includes/class-kmae-listing-drafts.php` |
| Vehicle-specific data and catalog | [`FEATURES/VEHICLE.md`](FEATURES/VEHICLE.md) | `includes/class-kmae-vehicle.php`, `assets/data/vehicle-catalog.json`, `assets/js/vehicle-catalog.js` |
| Photo motor and image processing | [`FEATURES/PHOTO-MOTOR.md`](FEATURES/PHOTO-MOTOR.md) | `includes/class-kmae-photo-motor.php`, `includes/class-kmae-images.php`, `assets/js/photo-motor.js` |
| Automated listing moderation | [`FEATURES/MODERATION.md`](FEATURES/MODERATION.md) | `includes/class-kmae-moderation.php`, `public/class-kmae-public.php` |
| Browse, search wizard and personalization | [`FEATURES/BROWSE-SEARCH.md`](FEATURES/BROWSE-SEARCH.md) | `includes/class-kmae-query.php`, `includes/class-kmae-browse.php`, `includes/class-kmae-counties.php` |
| Böngészés v2: index tábla + REST feed (szerződés) + v2 kártyás kliens (markup a dizájnhoz) | [`FEATURES/BROWSE-V2.md`](FEATURES/BROWSE-V2.md) | `includes/class-kmae-browse-index.php`, `includes/class-kmae-browse-feed.php`, `templates/browse-swipe-v2.php`, `assets/js/swipe-v2.js`, `assets/css/browse-v2.css`, `tests/test-browse-feed.php` |
| Chat (conversation, messages, attachments) | [`FEATURES/CHAT.md`](FEATURES/CHAT.md) | `includes/class-kmae-conversations.php`, `includes/class-kmae-chat-offers.php`, `includes/class-kmae-sale-contract.php`, `templates/offer-thread.php`, `templates/messages.php` |
| Event notifications and email | [`FEATURES/NOTIFICATIONS.md`](FEATURES/NOTIFICATIONS.md) | `includes/class-kmae-notifications.php`, `includes/class-kmae-brand.php` |
| Dashboard, hubs and statistics | [`FEATURES/DASHBOARD-HUBS.md`](FEATURES/DASHBOARD-HUBS.md) | `includes/class-kmae-dashboard.php`, `templates/partials/dashboard-home.php`, `templates/hub-section.php` |
| Live polling, navigation and badges | [`FEATURES/LIVE-NAV.md`](FEATURES/LIVE-NAV.md) | `includes/class-kmae-live-page.php`, `includes/class-kmae-nav.php`, `public/class-kmae-public.php` |
| Bejelentések (hirdetés + chat → admin Jelentések) | [`FEATURES/REPORTS.md`](FEATURES/REPORTS.md) | `includes/class-kmae-reports.php`, `templates/partials/report-form.php`, `admin/views/reports.php` |
| WordPress admin integration | [`FEATURES/ADMIN.md`](FEATURES/ADMIN.md) | `admin/class-kmae-admin.php`, `admin/views/dashboard.php`, `admin/views/meta-box-listing.php` |
| Public contact form | [`FEATURES/CONTACT-FORM.md`](FEATURES/CONTACT-FORM.md) | `public/class-kmae-public.php`, `templates/contact.php`, `includes/class-kmae-settings.php` |
| Archive lifecycle and scheduled purge | [`FEATURES/CRON-ARCHIVE.md`](FEATURES/CRON-ARCHIVE.md) | `includes/class-kmae-listings.php`, `includes/class-kmae-activator.php`, `includes/class-kmae-deactivator.php` |
| Post type, taxonomy and launch mode | [`FEATURES/TAXONOMY-LAUNCH.md`](FEATURES/TAXONOMY-LAUNCH.md) | `includes/class-kmae-post-types.php`, `includes/class-kmae-taxonomies.php`, `includes/class-kmae-launch.php` |

## System-wide high-risk links

- **Chat lives on the Üzenetek page.** `KMAE_Pages::conversation_thread_url()` points to `uzenetek`; theme loads `offer-thread.css` only there with `?kmae_conversation`.
- **Unread badge = chat only.** `KMAE_Database::count_unread_conversations()`; seen marks are per conversation (`_kmae_conversations_seen_map`).
- **Listing publication depends on vehicle validation + moderation + photo state.** Desktop/computer creation can produce `awaiting_photos`; later `KMAE_Listings::activate_if_photos_ready()` can publish it.
- **Email verification gates core actions.** Server-side `KMAE_Public::handle_forms()` blocks `create_listing` and `start_conversation` for users requiring verification.
- **Company location overrides listing location.** Listing meta save delegates location to `KMAE_Company::apply_listing_location()` for company users.

## Which map to open next

- Unknown feature location → `FILE-MAP.md` then matching `FEATURES/*.md`.
- Known class/method/function → `SYMBOL-MAP.md`.
- Meta/table/user data question → `DATA-MAP.md`.
- Hook/AJAX/admin-post/shortcode entry → `HOOK-ROUTE-MAP.md`.
- End-to-end behavior → `FLOWS.md`.
- Frontend JS ↔ PHP relation → `FRONTEND-MAP.md`.
- Template ownership/render path → `TEMPLATE-MAP.md`.
- Before trusting a cross-file chain → `VERIFICATION.md`.

## Boundary / uncertainty rules

- `THEME-OWNED / UNVERIFIED`: the plugin conditionally calls `kmae_is_guest_simple_marketing_shell()` if a theme exposes it; this map verifies only the plugin side of that boundary.
- External service availability, current production database contents, runtime WordPress configuration and deployed theme behavior are **UNVERIFIED** because they are not contained in this plugin ZIP.
- No behavior is documented as fact solely from filenames; feature flows below cite source symbols/paths.


## V2 diagnostic workflow

For a concrete bug, start with `DIAGNOSTIC-ROUTER.md`, then load exactly one matching `FEATURES/*.md` and the suggested **1–5 source symbols**. Use `DECISION-TREES.md` only when the first checkpoint is ambiguous. Use `HOTSPOTS.md` and `REGRESSION-MAP.md` before/after changing a shared symbol. `SEARCH-ALIASES.md` translates UI/user language into exact hooks/classes/actions. `PLUGIN-BOUNDARIES.md` says when to stop plugin debugging.

**Default context budget:** 1 router + 1 feature map + 1–5 source symbols. Expand only after a failed checkpoint. **SOURCE CODE > PROJECT MEMORY.**
