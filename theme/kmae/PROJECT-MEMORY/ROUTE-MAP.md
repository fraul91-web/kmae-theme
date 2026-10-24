# ROUTE MAP — THEME-KNOWN ALBIRA ROUTES

This file maps only routes that are explicitly known by theme source. It does not assert that every linked page exists or how the plugin implements it.

| Logical slug / route | Theme recognizes as feature? | Shortcode contract | Body class / CSS | `page.php` render mode | Navigation use |
|---|---|---|---|---|---|
| `hirdetes-feladasa` | yes | `kmae_create_listing` | `kmae-listing-create-page` + `listing-create.css` | direct `do_shortcode(raw content)` | desktop header “Hirdetés feladása”; own-listings menu active also treats create route as active |
| `hirdetesek` | yes | `kmae_browse` | `kmae-browse-page` + `browse.css` | generic page unless plugin/archive owns template | guest + logged-in browse/search |
| `kmae_listing` archive | yes | theme comment expects browse shortcode via plugin archive | `kmae-browse-page` + `browse.css` | not `page.php` | browse active |
| singular `kmae_listing` | yes | n/a | `kmae-single-listing-page`; logged-in `single-listing.css` | not `page.php` | Browse or My Listings active depending on author ownership |
| `sajat-hirdetesek` | yes | `kmae_my_listings` | `kmae-my-listings-page` + `my-listings.css` | raw `do_shortcode()` (no wpautop) | logged-in header/drawer |
| `uzenetek` | yes | `kmae_messages` | `kmae-messages-page` + `messages.css` / `offer-thread.css` (thread) / `sale-contract.css` (thread + `kmae_contract=1`) | raw shortcode path | PC nav + drawer „Üzenetek” with unread badge |
| `ertesitesek` | `kmae_is_notifications_page` | `kmae_notifications` | `kmae-notifications-page` + `notifications.css` | raw shortcode path (`page.php` `$is_raw_shortcode`) | PC header bell (only when `KMAE_Dealer_Leads::is_enabled()`) + logged-in mobile header notification link |
| `profil` | yes | `kmae_profile` | `kmae-profile-page` + `profile.css` | direct `do_shortcode(raw content)` | profile dropdown, mobile avatar, drawer |
| `beallitasok` | yes | `kmae_settings` | `kmae-settings-page` + `settings.css` | direct `do_shortcode(raw content)` | profile dropdown + drawer |
| `gyik` | yes | `kmae_faq` | `kmae-faq-page` + `faq.css` | generic | drawer/info + footer |
| `kapcsolat` | yes | `kmae_contact` | `kmae-contact-page-view` + `contact.css` | generic | guest nav, drawer/info, footer |
| `regisztracio` | special completion recognition | `kmae_register` | optional `kmae-complete-page` + `google-complete.css` | direct `do_shortcode(raw content)` | guest register CTA |
| `belepes` | no feature detector, but raw-render recognized | `kmae_login` | guest `landing.css` auth styling | direct `do_shortcode(raw content)` | guest login CTA / mobile icon |
| `eladni` | hub route in `page.php` | `kmae_hub_sell` | general app/hub CSS | `.kmae-hub-menu-shell` + `the_content()` | bottom nav “Eladni” |
| `venni` | hub route in `page.php` | `kmae_hub_buy` | general app/hub CSS | `.kmae-hub-menu-shell` + `the_content()` | bottom nav “Venni”; desktop browse active logic also tests `/venni` |
| `statisztikak` | hub route in `page.php` | `kmae_hub_stats` | general app/hub CSS | `.kmae-hub-menu-shell` + `the_content()` | drawer + bottom nav |
| `biztonsag` | yes (`kmae_is_security_page`) | `kmae_security` | `kmae-security-page` + `security.css` (PC only) | generic page (`.kmae-page__title` rejtve) | drawer/info + footer |
| `feltetelek` | no detector | none known | generic page CSS | generic | drawer/info + footer |
| `adatvedelem` | no detector | none known | generic page CSS | generic | drawer/info + footer |

## Active-state logic caveats

- Desktop logged-in header active state is mostly `REQUEST_URI` substring matching.
- Guest header active state mixes WordPress page checks with substring matching.
- Bottom navigation active state is client-side exact pathname equality.
- Own singular listing deliberately suppresses Browse active state and activates My Listings.
- Drawer `kmae_app_menu_groups()` has its own active-state implementation, separate from `header.php`.

## V2 SYMPTOM-FIRST ROUTING

Do not start from this full route matrix for ordinary bugs. Start from `DIAGNOSTIC-ROUTER.md`, which resolves the symptom to one route/feature/symbol set.

Use this route map only when the first checkpoint says the **request was classified incorrectly**.
