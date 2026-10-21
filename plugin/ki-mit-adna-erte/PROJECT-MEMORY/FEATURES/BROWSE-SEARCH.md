# FEATURE — Browse, search wizard and personalization

## PURPOSE
Hirdetéslekérdezés, megye/kivitel/mód/ár szűrés, pass kezelés és resurfacing.

## READ FIRST
- `includes/class-kmae-query.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-counties.php`
- `public/class-kmae-shortcodes.php`

## THEN IF NEEDED
- `assets/js/browse-wizard.js`
- `assets/js/swipe.js`
- `assets/js/map.js`
- `templates/browse.php`
- `templates/browse-swipe.php`
- `templates/partials/browse-filter-rail.php`
- `templates/browse-wizard-map.php`
- `templates/browse-wizard-body.php`
- `templates/browse-wizard-offer.php`
- `theme/kmae/assets/css/browse.css` (left filter rail UI)

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
`[kmae_browse]`; browse query vars; kivitel cards come from `KMAE_Vehicle::browse_body_cards()` (same slugs as the listing wizard). `osszes=1` skips the vehicle wizard and lists every active car. After the county→body→mode wizard (or a fine search), results use a left filter rail (`templates/partials/browse-filter-rail.php` + `initFilterRail` in `browse-wizard.js`): left-edge vivid orange `#kmae-filter-toggle` stacks „KERESÉS” one letter per line beside an arrow (title „Részletes keresés”) and opens a light ice-card panel (wizard palette) inward from the left below the header; brand/model, cc/hp ranges, year/km, fuel/gear/body, műszaki months, condition/county/price checkboxes (each with Mindegy), closes on outside click/Esc, „Új keresés indítása” at the bottom. Extra query keys: `marka`, `modell`, `tilt[]` (tiltó lista: `Márka` vagy `Márka|Modell`, piros doboz a Részletes keresés panel legalján, a `.kmae-filter-rail__body` utolsó eleme (`#kmae-filter-block.kmae-block-panel`), CSS theme `browse.css`); `KMAE_Browse::parse_blocked_param` → `$wizard['blocked']`, szűrés `listing_matches_detail_filters`, JS `browse-wizard.js` `addBlock` + `applyBlocks` (hozzáadás/törlés azonnal újratölt, a többi query marad), az „Új keresés indítása” is viszi; csak az URL-ben él), `ccm_*`, `le_*`, `ev_*`, `km_*`, `uzem`, `valto`, `muszaki`, `mot_*`, `allapot`, `ar`; `mod=mind` means no legacy kp/ajanlat gate (price checkboxes drive filtering). An empty result still lets each active chip open a menu. Swipe pass AJAX; favorite save AJAX `kmae_favorite_add` and remove AJAX `kmae_favorite_remove` into user meta `_kmae_favorite_listings`.

V2 (index tábla + `/wp-json/kmae/v1/feed`, kapcsoló `kmae_browse_v2`): `BROWSE-V2.md`. A régi szűrés/rendezés módosításakor az index oszlopait és a `KMAE_Browse_Feed::build_sql()`-t is igazítani kell (`php tests/test-browse-feed.php`).

## IMPORTANT SYMBOLS
- Sorting (all users): toolbar in `browse-swipe.php` (`.kmae-browse-toolbar`, JS `assets/js/browse-sort.js`). `?rendez=` keys from `KMAE_Browse::sort_options($mod)` (no price sorts when mod=ajanlat); `KMAE_Browse::apply_sort()` after `filter_by_wizard`; any non-default sort or list view uses `needs_full_scan()` → `LIST_SCAN` (1000). Distance: `rendez=tavolsag&lat&lon&km` (browser geolocation, 2 decimals; modal `templates/partials/browse-distance.php`), radius filter + `distance_for()` chips; coords via `KMAE_Geo` (`includes/class-kmae-geo.php`: meta `_kmae_geo`, city cache option `kmae_geo_cities`, Nominatim search in cron only — single event on location save, hourly backfill of 20; county-seat fallback).
- List view: `KMAE_Browse::is_list_view()` (every logged-in user, céges és magánszemély, `?nezet=lista|kartya`, remembered in user meta `kmae_browse_view`) → `templates/partials/browse-list.php` (50/page `?oldal=N`, pager), rows styled after offers rows (theme browse.css „Céges listanézet”). v2 (`KMAE_Browse_Feed::use_list_index()`): lásd `BROWSE-V2.md` „Lista nézet”.
- `KMAE_Query::get_listings`
- `KMAE_Browse::{parse_wizard_params,is_wizard_complete,filter_by_wizard,listing_matches_wizard,county_slugs,counties_are_valid,record_pass,personalize_listings}`
- `KMAE_Counties`

## DATA USED
Active listing post/meta/taxonomy; vehicle body/value (synced from seller public price); seller price mode; user meta `_kmae_browse_passed`. Fresh hearts also sit in user meta `_kmae_favorite_unseen` until the favorites page is opened; that count is the red header badge.

## FLOW
See `../FLOWS.md` section 9.

Telefon (2026-10-11): `swipe.js` ujjal húzás (touchstart/move/end a `#kmae-swipe-stack`-en; balra = következő, jobbra = előző, húzás után a fotó-link kattintása elnyelve). `browse-wizard-map.php` alatt `nav.kmae-map-list` (megye-pirulák, mert telefonon a térkép-feliratok olvashatatlanok); asztalon rejtett, stílus a theme `browse-mobile.css`.

Browse kp mode matches fixed/guide listings when `_kmae_vehicle_value` (or public price fallback) falls within `kp_min`–`kp_max`. Browse ajanlat mode matches only `_kmae_price_mode=open`.

## DEPENDENCIES
Launch/vehicle mode, counties, seller price mode.

## SIDE EFFECTS
Pass AJAX only updates pass user meta. Query itself is read-only.

## CHANGE RISKS
`KMAE_Query::get_listings()` excludes own listings before personalization. Csak a böngészésben (`exclude_contacted` arg, a `KMAE_Shortcodes::browse` mind a 4 hívása): `post__not_in` = `KMAE_Browse::get_contacted_listing_ids()` — user meta `_kmae_browse_contacted` (`CONTACTED_META`), írja `KMAE_Conversations::save_message` ha a küldő a vevő (üzenet és ajánlat is); a régi chatekből (vevő írt) egyszer pótolja. Szándékosan NEM a beszélgetésből számol, így chat-törlés után sem jön vissza; `reset_listing()` takarítja. Changing query exclusions can alter browse and landing popular listings because landing uses same query helper.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-database.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-meta.php`
- `includes/class-kmae-seller.php`
- `includes/class-kmae-launch.php`
- `includes/class-kmae-images.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-settings.php`
- `includes/class-kmae-dashboard.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Browse/search  
**COMMON SYMPTOMS:** query + personalization + pass history.  
**READ FIRST (symbol-level):** `KMAE_Query`; `KMAE_Browse::personalize_listings()/filter_by_wizard()`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `personalize_listings filter_by_wizard record_pass`.  
**DO NOT LOAD UNLESS:** templates until ID/result set verified.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
