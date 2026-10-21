# FEATURE — Dashboard, hubs and statistics

## PURPOSE
Dashboard/hub aggregáció, listing view tracking, listing activity rows és kezdőoldali user summary. Ajánlat-kártyák, ajánlat-statisztika és bizalmi pont törölve (2026-10-10).

## READ FIRST
- `includes/class-kmae-dashboard.php`
- `templates/partials/dashboard-home.php`
- `templates/hub-section.php`
- `public/class-kmae-shortcodes.php`

## THEN IF NEEDED
- `assets/js/home-greeting.js`
- `includes/class-kmae-database.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
Hub shortcodes (`kmae_hub_sell`, `kmae_hub_buy`, `kmae_hub_stats`), dashboard partial, listing-view `template_redirect` tracking. A regisztráció utáni üdvözlő-képernyő („Kezdjük el”) 2026-10-11-én véglegesen törölve; az új fiók közvetlenül a főoldalra érkezik.

## IMPORTANT SYMBOLS
`KMAE_Dashboard::{get_data,maybe_track_listing_view,increment_views,get_user_view_stats,format_listing_row}`.

Kezdőoldal állapota (2026-10-11, végleges): `KMAE_Dashboard::get_home_state($uid,$urls)` → `start` (hirdetés és eladás nélküli új user „Első lépéseid” doboza, title/text), `first` (gratuláló felület), `live` (`home_live()`: max `HOME_LIVE_MAX`=6 aktív/fotóra váró/lejárt hirdetés kártyája: megtekintés összes + heti 8 hét `META_VIEWS_WEEK`-ből, érdeklődő = seller-oldali beszélgetés `message_count>0`, olvasatlan = `get_conversation_alert_state` 'good', kedvenc = `favorite_counts()` (usermeta `_kmae_favorite_listings` LIKE + unserialize, csak darabszám), élő ajánlat, lejárat; `say` = a manó buboréka `home_live_say()`), `sold`. A régi „Következő lépésed” / teendők / Hirdetés-erősség / Hirdetéseim-panel (`home_next_step`, `listing_strength`) TÖRÖLVE — a főoldalon nincs több lépés. Sablon `partials/dashboard-home.php` → `partials/home-live.php` (gyökér `data-kmae-live-page="dashboard_home"`, fingerprint `KMAE_Live_Page` `dashboard_home`: státusz+megtekintés+ajánlat+üzenetszám, seen-állapot nélkül); a belépett főoldal nocache (`KMAE_Public::nocache_live_pages` `is_front_page`). JS `assets/js/home-live.js` (számláló-felpörgetés, „+N” az előző látogatáshoz képest localStorage-ból, gratuláló doboz eltűnése). CSS theme `home-cards.css` vége „Élő főoldal” (`.kmae-live*`). A két főkártya (Hirdetést feladok / Autót keresek) csak `start` állapotban és állapot nélkül látszik (`dashboard-home.php` `if ( ! $kmae_home_first && ! $kmae_home_live )`); az élő főoldalon 2026-10-11-től nincs.

Első hirdetés utáni felület (2026-10-11): ha a felhasználónak pontosan 1 publikált hirdetése van (`get_home_state` → `first`), és az aktív, nincs elrejtve, és van rá tipp → `KMAE_Dashboard::first_listing_tips()` adja a `first` mezőt, a sablon csak a `partials/home-first-listing.php`-t mutatja (köszöntő, élő rész, főkártyák nélkül). Tippek: fotó < 5 (`STRENGTH_MIN_PHOTOS`), leírás < 200 karakter (`FIRST_TIP_MIN_DESC`; a szóköz/írásjel is számít); sávok: minimum (piros) fotó 1 / 80 karakter (`FIRST_TIP_FLOOR_*`), elfogadható (narancs) 3 / 120 (`FIRST_TIP_BASE_*`), ideális (zöld) 5+ / 200+; a tipp `metrics` (`tier_metrics()`: `scale` + `current`) adja a skálát és a sávszínű „Jelenleg” sort (`partials/home-first-listing.php`). Ha mindkettő ideális: nincs tipp, a `first['praise']` dicsérő kártya jön (a „Köszönöm” gomb ugyanúgy elrejti). Csillag = 1 + fotó(0–2) + leírás(0–2). „Most nem” = POST `kmae_action=dismiss_first_listing_tips` (`KMAE_Public::process_dismiss_first_listing_tips`) → user meta `_kmae_first_tips_dismissed` = hirdetés ID, a doboz elhalványul (`home-live.js`, `.kmae-first.is-leaving`), majd átirányítás a főoldalra, ahol már az élő rész (`live`) jön. A leírás-javaslatok nem szúrhatók be kattintásra a főoldalról (a varázslóban nincs hozzá mélylinkes kezelő), csak listaként jelennek meg.

## DATA USED
User listings and statuses; listing view meta `_kmae_view_count` / weekly data; visitor cookie prefix `kmae_v_`.

## FLOW
Request dashboard/hub → aggregate current-user domain data → format rows → render template. Singular listing request can increment view stats with duplicate-view guard.

## DEPENDENCIES
Reads most business domains but should not own their state transitions.

## SIDE EFFECTS
View tracking writes listing meta/cookie; dashboard reads otherwise aggregate state.

## CHANGE RISKS
Because this feature is an aggregator, field/schema changes elsewhere often surface here even if dashboard code is not the root cause.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-meta.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-pages.php`
- `includes/class-kmae-images.php`
- `includes/class-kmae-query.php`
- `includes/class-kmae-seller.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-launch.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-settings.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Dashboard/hubs  
**COMMON SYMPTOMS:** aggregated listing stats.  
**READ FIRST (symbol-level):** `KMAE_Dashboard::get_data()` + formatter matching row.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `get_data format_listing_row activity`.  
**DO NOT LOAD UNLESS:** mutation feature if raw persisted state already wrong.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
