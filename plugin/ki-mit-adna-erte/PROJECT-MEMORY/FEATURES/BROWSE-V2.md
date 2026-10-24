# FEATURE — Böngészés v2: index + feed (SZERZŐDÉS)

A 3. (kliens) és 4. feladat ebből dolgozik. Ha a kód és ez eltér: a kód az igazság, javítsd ezt.

## Fájlok
- `includes/class-kmae-browse-index.php` — `KMAE_Browse_Index`: tábla, sor-építés (`columns()`, `card()`), frissítő hookok, teljes újraépítés.
- `includes/class-kmae-browse-feed.php` — `KMAE_Browse_Feed`: kapcsoló, paraméter-értelmezés, `canonical_query()`, `base_query()`, tiszta SQL-építő `build_sql()`, REST végpontok.
- Bekötés: `class-kmae-loader.php`, `class-kmae-plugin.php` (`::init()`), `KMAE_Activator::create_tables()` + `maybe_upgrade()` (`KMAE_Browse_Index::maybe_create()`), `KMAE_Deactivator` (cron), `KMAE_Settings` (opció), `admin/views/settings.php` (checkbox + újraépítés gomb), `KMAE_Chat_Offers::purge_page_cache()` (Foglalt-hook).
- Kliens (3. feladat, kártyás nézet): `templates/browse-swipe-v2.php` + `assets/js/swipe-v2.js` + `assets/css/browse-v2.css` (enqueue: `KMAE_Public` a swipe.js után, `is_browse_request()`), bekötés: `templates/browse-swipe.php` (`$sw2`). Lásd „Kártyás nézet (v2 kliens)” és „Markup a dizájnhoz”.
- Tesztek (WP/MySQL nélkül): `php tests/test-browse-feed.php` (régi vs. új sorrend, minden rendezés × 20 szűrő-kombináció, vendég + belépett; meghívja a `tests/test-browse-feed-sqlite.py`-t), `php tests/test-browse-index-card.php` (kártya JSON), `php tests/test-browse-list.php` (lista nézet: régi vs. index-út HTML-diff, sqlite háttérfolyamat; `KMAE_LIST_DUMP=<mappa>` kiírja a HTML-eket képernyőképhez).

## Kapcsoló
`KMAE_Browse_Feed::is_enabled()` = `get_option('kmae_browse_v2')` VAGY (`manage_options` ÉS `?uj=1`). Admin: Albira → Beállítások → „Böngészés v2”. Kikapcsolva a régi böngészés fut változatlanul; az index és a feed a háttérben ettől függetlenül él (a feed user-független, ezért nem lehet a `?uj=1`-hez kötni).

## Tábla: `{prefix}kmae_browse_index` (hirdetésenként 1 sor)
Létrehozás: saját séma-verzió (`KMAE_Browse_Index::SCHEMA_VERSION`, opció `kmae_browse_index_schema`), a `KMAE_Activator::maybe_upgrade()` minden betöltéskor hívja a `maybe_create()`-et → **KMAE_VERSION emelése nem kell** (az oldal-szinkront és társait nem futtatja). Sikertelen létrehozásnál óránként próbálja újra (`kmae_browse_index_retry` transient). Séma-változásnál: `SCHEMA_VERSION` emelés → dbDelta + teljes újraépítés.

| oszlop | forrás (pontosan ez a régi kód számítása) | régi felhasználó |
|---|---|---|
| `listing_id` PK | post ID | — |
| `author_id` | `post_author` (feedbe NEM kerül ki) | `get_listings` author__not_in → kliens `own` |
| `is_active` | `post_status=publish` ÉS `_kmae_status` meta = `active` (hiányzó meta = 0) | `get_listings` |
| `in_category` | `has_term(KMAE_Launch::category_slug(), 'kmae_category')` | `get_listings` (egykategóriás mód) |
| `is_reserved` | van `accepted` sor a `kmae_chat_offers`-ben (`get_accepted_for_listing`) | Foglalt jelvény |
| `post_date`, `post_date_gmt` | post (0000-… → 1970-01-01) | alap-sorrend, `legujabb` |
| `county` | `sanitize_key(_kmae_county)` | `county_slugs` + detail filter, get_listings egy megye |
| `body` | `KMAE_Vehicle::normalize_body(_kmae_vehicle_body)` (régi slug → `other`) | `listing_matches_wizard` |
| `brand_key`, `model_key` VARBINARY | `strtolower(trim(meta))` (= strcasecmp, ASCII) | márka/modell szűrő, tiltó lista |
| `sort_name` VARBINARY | `remove_accents(mb_strtolower(trim(brand.' '.model)))`, üres → NULL | `nev` rendezés (strcmp) |
| `model_year`, `mileage`, `engine_cc`, `power_kw` | `(int)` meta | tartomány-szűrők, rendezések |
| `power_hp` | `KMAE_Vehicle::kw_to_horsepower(power_kw)` | `le_min/le_max` |
| `fuel`, `gear`, `cond` | `sanitize_key(meta)` | `uzem`, `valto`, `allapot` |
| `price_mode` | `KMAE_Seller::get_price_mode()` | `ajanlat`/`kp` mód, `ar` |
| `has_cash` | `cash ∈ KMAE_Meta::get_offer_types()` | `kp` mód |
| `vehicle_value` | `KMAE_Vehicle::get_vehicle_value()` | `kp` tartomány |
| `price_amount` | `KMAE_Seller::get_public_price()` ha ≥ 1, különben NULL (= `get_public_price_row` amount) | `ar_novekvo/ar_csokkeno` |
| `mot_date` DATE | `_kmae_vehicle_mot`, ha `ÉÉÉÉ-HH-NN` → `DateTimeImmutable` normalizált dátuma, különben NULL | `muszaki` szűrő |
| `lat`, `lon` | `KMAE_Geo::listing_coords()` (hivatalos településlista `KMAE_Settlements` → meta → város-cache → megyeszékhely), nincs → NULL | `tavolsag` |
| `card` | `KMAE_Browse_Index::card()` JSON (csak aktív sorban, inaktívnál `{}`); v3 (séma 2): `seller` {name, type} = cégnév / nyilvános név (`KMAE_Company::seller_display_name`, e-mail soha) + `KMAE_Auth::account_type_label`; v4 (2026-10-20): `extras` [{field: color|mot|owners, value, label}] (üres kimarad; a meglévő sorok csak index-újraépítés / mentés után kapják meg) | kártya |
| `indexed_at` | utolsó építés (UTC) | — |

Szöveg-oszlopok szélessége: `KMAE_Browse_Index::WIDTHS` (bájt-vágás, a feed a keresett értéket ugyanígy vágja).

### Indexek és indoklás
- `active_date (is_active, post_date, listing_id)` — minden lekérdezés `is_active=1`; az alap („Ajánlott”) sorrend ezen fut, `LIMIT 12`-nél filesort nélkül, korai leállással.
- `active_county_date (is_active, county, post_date)` — a leggyakoribb belépő: megye-lépés (egy/több megye `IN`), dátum-sorrenddel.
- `active_body (is_active, body)` — kivitel-lépés (`IN`).
- `brand_model (brand_key, model_key)` — márka / márka+modell egyenlőség (bájt-pontos, VARBINARY).
- `price_value (price_mode, vehicle_value)` — `kp` mód (`price_mode<>open` + érték-tartomány), `ajanlat` mód és `ar` lista.
- `model_year`, `mileage`, `power_hp`, `engine_cc`, `price_amount`, `mot_date` — tartomány-szűrők (szelektív esetben ezeket választja az optimalizáló) és a megfelelő rendezések.
- `author_id` — a `me/browse` saját-lista lekérdezéséhez (nem a feedhez).
- Nem indexelt: `fuel/gear/cond` (alacsony kardinalitás, mindig más szelektív feltétel mellett), `lat/lon` (a távolság számított kifejezés, ≤ 8000 soron teljes scan + filesort is olcsó; a feed 60 mp-ig a Cloudflare-ben van).

## Frissen tartás (hookok)
Minden a `KMAE_Browse_Index::queue()`-ba megy, a sor a kérés végén (`shutdown`, prio 5) egyszer épül (`sync()`); építés közben a saját meta-írások nem kerülnek újra sorba.
- Mentés: `save_post_kmae_listing`, `transition_post_status` (kmae_listing).
- Mező-/státuszváltás: `added/updated/deleted_post_meta` a `WATCHED_META` kulcsokra (márka…ár, `_kmae_status`, `_kmae_gallery`, `_thumbnail_id`, `_kmae_geo` stb.). Ez fedi: lejárat cron (`KMAE_Listings::expire_listings` → `_kmae_status`), hosszabbítás (`renew_listing`), újraaktiválás, `reset_listing`, `close_listing` (Foglalt lezárás), geokódolás (`_kmae_geo`). A megtekintés-számláló NINCS a listán.
- Képcsere: ugyanezek a hookok a hirdetés képén (`post_parent` = hirdetés) a `WATCHED_ATTACHMENT_META` kulcsokra (`_wp_attachment_metadata`, `_kmae_ambient/_tone/_lqip` — a kártyakép-köteg is ezt írja).
- Törlés: `deleted_post` → sor törlése azonnal; kuka/`auto-draft` → a `sync()` törli.
- Foglalt: `do_action('kmae_listing_reserved_changed', $listing_id)` a `KMAE_Chat_Offers::purge_page_cache()`-ben (accept, cancel_sale, close_sale/finalize → close_listing). Hirdetéstörlésnél a sor amúgy is törlődik.
- Nem fedett (teljes újraépítés kell): permalink-szerkezet csere, márka-logó lista / megye-címke / fordítás változása, `kmae_geo_cities` cache bővülése olyan hirdetésnél, amelynek nincs `_kmae_geo` metája.

## Teljes újraépítés
`start_rebuild()` (opció `kmae_browse_index_rebuild` = kurzor) → `run_batch()`: ID szerint 200 hirdetés vagy 20 mp, zár `kmae_browse_index_lock` (5 perc); a végén a már nem létező hirdetések sorai törlődnek. Folytatás: cron `kmae_browse_index_rebuild` (egyszeri esemény 60 mp-enként, amíg fut) + admin gomb (Albira → Beállítások, `admin_post_kmae_browse_index_rebuild`: ha nem fut, újat indít, és egy adagot azonnal lefuttat). Újrafuttatható: a sor-írás `REPLACE`. Új tábla / séma-csere automatikusan indítja.

## Feed: `GET /wp-json/kmae/v1/feed`
Paraméterek: a böngészés URL-jének teljes `KMAE_Browse::parse_wizard_params` listája (`megye, kivitel, mod, kp_min, kp_max, marka, modell, tilt[], ccm_min, ccm_max, le_min, le_max, ev_min, ev_max, km_min, km_max, uzem, valto, muszaki, mot_min, mot_max, mot_lejart, allapot, ar`) + `osszes`, `rendez`, `lat`, `lon`, `km` + `page` (1-től). 12 tétel/oldal; szűrés, rendezés, lapozás SQL-ben.
- **Kanonikus sorrend:** `KMAE_Browse_Feed::canonical_query($params)` — fix kulcs-sorrend (`osszes, megye, kivitel, mod, kp_min, kp_max, marka, modell, tilt[], ccm_min…km_max, uzem, valto, muszaki, mot_min, mot_max, mot_lejart, allapot, ar, rendez, lat, lon, km, page`), csak hatásos érték, rendezett listák (megye, kivitel, tilt, allapot, ar), márka/modell/tilt kisbetűs, lat/lon 2 tizedes, `kp_*` csak `mod=kp`-nél, `mot_*` csak `muszaki`-val, `page` csak >1. **A kliens mindig ezt az URL-t kéri** (a válasz `query` mezője is ez). PHP-ban: `KMAE_Browse_Feed::canonical_query( $_GET )`.
- `osszes=1`: minden szűrő-paraméter hatástalan (mint a régi „Összes hirdetés”), csak rendezés + oldal.
- Hiányos varázsló (`KMAE_Browse::is_wizard_complete` hamis): `complete:false`, üres lista (a régi oldal itt varázsló-lépést mutat).
- Rendezés: = `KMAE_Browse::current_sort` (érvénytelen → `ajanlott`; `mod=ajanlat`-nál nincs ár-rendezés; `tavolsag` csak érvényes magyarországi lat/lon-nal, `km` max. 1000, `km>0` = sugár-szűrés). Ismeretlen érték (NULL / 0) a végére, egyenlőségnél `post_date DESC, listing_id DESC`.
- Fejléc (belépve is): `Cache-Control: public, max-age=0, s-maxage=60`. A WP REST belépett usernek küldött no-cache ki van kapcsolva erre az útvonalra (`rest_send_nocache_headers` szűrő), és kiszolgálás előtt a `Set-Cookie`, `X-WP-Nonce`, `Expires`, `Pragma` törlődik (`rest_pre_serve_request`, prio 99). A callback `wp_set_current_user(0)`-val fut: semmi nem függ a látogatótól.
- **A kliens NE küldjön nonce-ot / sütit a feedre** (`fetch(url, {credentials: 'omit'})`): nem kell, és a nonce-os kérésre a WP egy friss nonce-ot tenne a válaszba (mi kivesszük, de felesleges).

### Válasz (példa, `?megye=pest&kivitel=mind&mod=mind&rendez=km`)
```json
{
  "v": 1,
  "query": "megye=pest&kivitel=mind&mod=mind&rendez=km",
  "base": "megye=pest&kivitel=mind&mod=mind",
  "complete": true,
  "sort": "km",
  "page": 1,
  "per_page": 12,
  "total": 37,
  "pages": 4,
  "ids": [512, 498, 377, "… mind a 37, a feed sorrendjében (csak page=1; max 5000, efölött ids_truncated:true)"],
  "ranks": [0, 1, 1, "… azonos rang = azonos rendezési érték (csak page=1 és nem ajanlott rendezésnél)"],
  "items": [
    {
      "id": 512,
      "reserved": false,
      "distance_km": null,
      "card": {
        "v": 1,
        "id": 512,
        "url": "https://albira.hu/hirdetes/bmw-320d/",
        "title": { "brand": "BMW", "model": "320d Touring", "text": "BMW 320d Touring", "list": "BMW 320d Touring" },
        "subtitle": "2016 · 187 000 km · Dízel",
        "lead": { "hp": 190, "cc": 1995, "liters": "2,0" },
        "price": { "mode": "guide", "label": "Irányár", "amount": 5490000, "formatted": "5 490 000 Ft" },
        "value": { "amount": 5490000, "formatted": "5 490 000 Ft" },
        "offer_types": [ { "slug": "cash", "label": "Készpénz", "icon": "banknote" } ],
        "facts": [
          { "field": "year", "code": "", "value": "2016", "label": "Évjárat" },
          { "field": "mileage", "code": "", "value": "187 000 km", "label": "Futott km" },
          { "field": "fuel", "code": "diesel", "value": "Dízel", "label": "Üzemanyag" },
          { "field": "transmission", "code": "automatic", "value": "Automata", "label": "Váltó" }
        ],
        "location": "Pest, Budaörs",
        "brand_logo": "https://albira.hu/wp-content/uploads/2026/09/….webp",
        "photos": [
          { "s": "…-480w.webp", "m": "…-800w.webp", "l": "…-1200w.webp", "w": 1600, "h": 1200, "ambient": "#203040", "tone": "cool", "lqip": "data:image/webp;base64,…" }
        ],
        "photo_count": 1
      }
    }
  ]
}
```
Kártya v2 (CARD_VERSION 2, 2026-10-17): a `cutout` kulcs 2026-10-17-én törölve (Borítókép-stúdió megszűnt; a színpadon mindig az 1. fotó); `lead.kw` + `body` {code,label} (a kártyás nézet „Teljesítmény”/„Karosszéria” csempéjéhez; `other`/üres → üres label). A `facts` szándékosan nem bővült (a lista nézet is kirakja). A régi index-sorokban ez nincs, amíg nincs „Index teljes újraépítése”.

Kártya-szabályok (= `browse-swipe.php` / `browse-list.php`): `title.text` = márka+modell (≤3 betű → NAGY, csupa nagybetű → Title Case), üresen a `post_title` „Eladó” nélkül, az első ` – ` előtti rész; `title.list` = ugyanez darabolás nélkül (listanézet). `price` null → `value` (ha van) → „Ajánlatot vár”. `brand_logo` üres „Egyéb/egyeb/other” márkánál. `photos` = `KMAE_Images::card_sources()` a galéria sorrendjében (üres `s` kimarad; nincs galéria → bélyegkép). `distance_km` csak `tavolsag`-nál (a régi chip: `~max(1, round(km)) km`). `reserved` = „Foglalt” jelvény (a Foglalt hirdetés NEM marad ki).

## Személyes végpont: `GET /wp-json/kmae/v1/me/browse`
Csak belépve (WP cookie + `X-WP-Nonce: wp_rest` nonce; nélküle 401). `Cache-Control: no-store, no-cache, must-revalidate, private, max-age=0`.
```json
{ "v": 1, "favorites": [12, 7], "passed": [44, 18, 90], "contacted": [31], "own": [5, 6], "passed_interval": 20, "view": "kartya" }
```
`favorites` = `get_favorite_listing_ids` (csillag állapot), `passed` = `get_passed_listing_ids` (a sorrendje nem számít), `contacted` = `get_contacted_listing_ids`, `own` = a user összes (nem kukás) hirdetése, `view` = mentett nézet (`lista`/`kartya`, a váltást továbbra is a `?nezet=` menti).

## Személyes adat a kliensen — PONTOS szabály (= a mostani eredmény)
A régi út: `get_listings` (csak egy-megye + kivitel szűrés, dátum szerint, saját + megkeresett kizárva) → `personalize_listings` (beszúrás) → `filter_by_wizard` → `apply_sort` (stabil: egyenlő értéknél a beszúrt sorrend). Ezt így kell visszaadni:
1. `S` = a kért feed `ids` (+ `ranks`, ha nem `ajanlott`). Vendégnél az eredmény `S`, kész.
2. `D` = a válasz `base` query-jével kért feed `ids`-e (ha `base == query`, akkor `D = S`). Ez a régi `get_listings` adag, dátum-sorrendben.
3. Kizárás: `D`-ből és `S`-ből ki minden `own ∪ contacted`.
4. `fresh` = `D` elemei, amelyek nincsenek a `passed`-ban; `passedD` = `D` elemei, amelyek a `passed`-ban vannak (mindkettő `D` sorrendjében).
5. Ha `fresh` üres → `P = passedD`; ha `passedD` üres → `P = fresh`; különben: `fresh`-en végig `i = 0..`: `P.push(fresh[i])`; ha `(i+1) % passed_interval == 0` → `P.push(passedD[k % passedD.length]); k++`. (Ugyanaz az átlépett többször is jöhet; 20-nál kevesebb friss találatnál átlépett nem jelenik meg — mint most.)
6. `P` = csak az `S`-ben is szereplő elemek, `P` sorrendjében (ez a régi szűrés + távolság-sugár).
7. Ha a rendezés nem `ajanlott`: `P` stabil rendezése `rank[id]` szerint (azonos rangnál a 6. lépés sorrendje marad).
8. Kártya egy ID-hoz: a feed `page = floor(S-beli index / 12) + 1` oldalán van (a személytelen sorrend szerint) — azt az oldalt kell lekérni (gyorsítótárazható).
Teszt: `tests/test-browse-feed-sqlite.py` `client_personalize()` = ez a szabály szó szerint; 220/220 eset bájtra egyezik a régi úttal (belépett user, átlépett + megkeresett + saját hirdetésekkel).

### Szándékos eltérés a régitől
- Nincs 24 / 500 / 1000-es adag-korlát: a régi alapnézet csak a 24 legújabbat vizsgálta; a v2 mindent lapoz (max. 5000 ID a `ids`-ben).
- Listanézet: a régi 50/oldal, a feed 12/oldal — a kliens rakja össze.
- A `marka`/`modell` bájt-pontos, kis/nagybetű-független (ASCII) egyezés, ahogy a `strcasecmp`; a régi a márkánál a nem trimmelt metát vetette össze (a mentés `sanitize_text_field`-del trimmel, ezért a gyakorlatban azonos).

### MySQL-specifikus részek (a teszt sqlite-ban fut)
- Távolság: `RADIANS/SIN/COS/ATAN2/SQRT/POW` haversine a PHP `KMAE_Geo::distance_km` műveleti sorrendjével; MySQL-ben beépített, sqlite-ban Python-függvény. A MySQL `RADIANS` (`x·π/180`) és a PHP `deg2rad` (`x/180·π`) az utolsó biten eltérhet: sorrendet csak ~1e-12 km-en belüli távolságoknál, a sugár-határt csak pontosan a határon befolyásolhatja; azonos koordinátájú hirdetések távolsága mindig azonos (tiebreak dönt).
- `VARBINARY` oszlopok: MySQL-ben bájt-összevetés/-rendezés (sqlite TEXT alapból BINARY).
- Műszaki: a hátralévő hónapokat a PHP számolja dátumhatárrá (`mot_first_date`, a `months_left` a régi `mot_months_left` másolata, „ma” paraméterrel); az SQL csak dátumot hasonlít — dialektus-független, napra pontos (a teszt 731 napon × 9 határon ellenőrzi).

## Kártyás nézet (v2 kliens)
- Mikor: `browse-swipe.php` → `$sw2` = `KMAE_Browse_Feed::use_card_index()` (nem lista nézet ÉS `is_vehicle_launch()` ÉS `is_enabled()` ÉS `KMAE_Browse_Index::ready()`). Ekkor a `$listings` = a személyre szabott ID-sorrend (üres → a régi üres állapot), és a stack helyett a `browse-swipe-v2.php` jön; a wrapper kap egy `kmae-sw2-browse` osztályt (a PC-rács ezen van, `:has()` nélkül). Szűrő, rendezés, Keresés panel, nézetváltó, lista nézet, régi kártyák: változatlan. A shortcode ilyenkor a régi lekérdezést (24 / 500 / 1000) nem futtatja (`use_card_index()`, mint a lista nézetnél).
- Szerver: `KMAE_Browse_Feed::client_state( wp_unslash($_GET), user )` → első feed-oldal (`build()`, ugyanaz, mint a REST, de `wp_set_current_user(0)` nélkül), belépve `me_payload()` (= /me/browse) + a `base` feed ids-e, a PHP-s `personalize()` (= JS = Python `client_personalize`, 400 véletlen esetre egyezik) és az első kártya (ha az nem az 1. oldalon van, annak oldala is beágyazva, `pages`). Inline: `<script type="application/json" id="kmae-sw2-data">` = `{cfg:{feedUrl, ajaxUrl, loggedIn, nonce, favoriteNonce, loginUrl, t:{szövegek}}, feed, pages, me, base_ids}` → az első megjelenéshez nincs kérés; a /me/browse-t így nem kéri külön a JS.
- JS: 3 újrahasznosított `<article>` (előző / aktuális / következő), a többi oldal `fetch(feedUrl + query + '&page=N', {credentials:'omit'})`, amikor az aktuális utáni 5 pozíció valamelyikének nincs adata (12-es oldalon a 8. kártyánál). Visszalépés: `sessionStorage.kmaeBrowseListing` (mint a swipe.js).
- Billentyűk (2026-10-21): SAJÁT `document` keydown a `swipe-v2.js`-ben (a `swipe.js` kezelője v2-ben nem fut, `#kmae-swipe-stack`-hez kötött): ←/→ = hirdetés (teljes képernyőn a kép), Enter = PC-n és álló telefonon adatlap oldalbetöltés (`remember()` + `card.url`), fekvő telefonon `openSheet()`, Space = `saveCurrent()`; nyitott `dialog`, szövegmező, rendezés/km menü alatt nem él. Adatlapról vissza: Backspace = `public.js` (`.kmae-single-listing`, `history.back()`).
- Jelentések = `swipe.js`: húzás/←→ = lapozás (pass NEM megy), Enter = megnyitás, Space = kedvenc (`kmae_favorite_add/remove`, nonce `kmae_favorite_add`; vendégnél belépés URL), fotó-gombok a kártyán. `kmae_browse_pass` csak a „Nem érdekel” gombtól megy (a swipe.js `#kmae-swipe-pass` párja — a régi sablonban ez a gomb nem volt kirakva, így régen pass sosem ment el). A pass a sorrendet csak újratöltéskor változtatja (a szabály szerint, `passed_interval`).
- Mérve (helyi stub, 390×844 DPR3, CPU 4×, 30 húzás): húzás közben 0 layout, képkockánként 1 elem style-recalc (az inline transform), fő szál munka/képkocka átlag 2,5 ms, p95 7,4 ms; rAF-intervallum p95 16,7 ms (nincs eldobott képkocka); DOM-ban mindig 3 kártya-elem.

## Képváltás + Jelentés (PC, 2026-10-18)
- Képváltás (`showPhoto`): számláló / bélyegkép azonnal vált, a nagy `<img>` csak a `warmPhoto()` kép `decode()`-ja után cserél (nincs üres villanás; token: a gyors kattintásból az utolsó nyer). Egér a bélyegkép fölött = előtöltés (`pointerover`). Szerver-terhelés 0: a fotók a feed-JSON-ban már benne vannak (s/m/l), CDN-ről jönnek. Görgő a sáv fölött = vízszintes görgetés.
- Előző/következő hirdetés pirula (PC, 2026-10-21): `[data-kmae-sw2-peek="-1|1"]` a `.kmae-sw2-bar`-ban; `peek()` (a `layout()` hívja) írja a szomszéd hirdetés címét + 1. fotó `s` képét, szélen `hidden`; kattintás = `stepPrev`/`stepNext`. Stílus: theme `browse-v2.css` „Előző / következő HIRDETÉS pirula” (a bar 3 oszlopos grid, Jelentés középen).
- Pozíció-visszaállítás: a `sessionStorage` `kmaeBrowseListing` (utolsó nézett) CSAK visszatéréskor él (`returning()`: navigáció `back_forward`, vagy a referrer egy `/hirdetesek/<slug>/` adatlap); friss betöltés / újratöltés az elejéről indul (2026-10-21).
- PC-n a lapozás nem forgat: `fly()` csak elúszik + elhalványul (`PC = matchMedia(min-width:1025px)`), húzásból a húzás irányában tovább.
- Jelentés: `[data-kmae-sw2-report]` → `<dialog data-kmae-sw2-rep>` (okok: „A képpel kapcsolatos” = `KMAE_Reports::photo_reasons()`, „A hirdetéssel kapcsolatos” = `reasons(listing)`), AJAX `kmae_report_listing` (nonce `kmae_report_nonce`, `cfg.reportNonce`) → `KMAE_Public::ajax_report_listing` → `KMAE_Reports::submit` (`photo` = a látott kép 1-től). Térkép: `FEATURES/REPORTS.md`.

## Lenyíló adatlap (PC + telefon, 2026-10-18)
- PC (2026-10-20, Raul) és álló telefon (2026-10-24, `PORTRAIT`): NINCS lenyíló adatlap — a fotó-kattintás semmit nem csinál. PC-n az „Hirdetés megtekintése” (`.kmae-sw2-msg`), állóban a „Részletek” (`.kmae-sw2-cta`, a link megy) és az Enter az adatlap oldalára visz. Az alábbi csak a FEKVŐ telefonra érvényes.
- Fekvő telefon: a fotó (`.kmae-sw2-open`), a `.kmae-sw2-cta` és az Enter NEM navigál: `swipe-v2.js` `openSheet()` → `fetch(card.url)` + DOMParser → a hirdetés oldalának `.kmae-single-listing` része a `<section class="kmae-sw2-sheet" data-kmae-sw2-sheet hidden>` → `[data-kmae-sw2-sheet-body]`-ba (a `.kmae-sw2-bar` után), az oldal odagörget. Tisztítás: `data-kmae-live-page`/fingerprint ki, `.kmae-page-back` és `script` ki, a cím nélküli `<form>`-ok `action` = a hirdetés URL-je. Stílus: a lekért oldal `#kmae-single-listing-css` linkje az első megnyitáskor a `<head>`-be kerül. A galéria nyilai/pöttyei a panelen saját delegált kezelővel mennek (a `public.js` csak betöltéskor köt). Hiba / nincs `.kmae-single-listing` (pl. lejárt) → a régi módon az oldalra visz. Ctrl/Shift-kattintás = új lap (link marad).
- Bezárás: alul `[data-kmae-sw2-sheet-close]` (felfelé nyíl, „Adatlap bezárása”) → felgörget, fent eltűnik (`closeSheet(true)`); lapozáskor `layout()` bezárja (más `order[cur]`). Kinézet: theme `browse-v2.css` vége „Lenyíló adatlap”.

## Fotó elhelyezése: crop / galéria (PC + telefon, 2026-10-19)
- `swipe-v2.js` `fit(node)` → kártya `data-fit`: `crop` (a fotó kitölti a keretet, az autó doboza = `p.subject` köré vágva, `--kmae-cx/--kmae-cy` = object-position %, `crop(p, R)`: `fits` = az egész autó látszik), `narrow`, `gallery`, vagy nincs. PC (2026-10-20, R = a kitöltő fotó-keret mért aránya: előbb `data-fit` le, `.kmae-sw2-open` méret): HÁROM egységes keret (Raul, 2026-10-20): `FRAMES` = [2 téglalap (= a teljes mező), 1.5 közép → `narrow`, 0.75 álló → `gallery`], a kép a log-arányban legközelebbihez (`kind()`, határ ≈ 1,73 / 1,06; négyzet = álló), mindig cover, `--kmae-cx/cy` = `crop(p, keret aránya)`. PC-n `crop` mód NINCS, a `crop` csak a telefoné. CSS párja: theme `browse-v2.css` `--n-r`. `--kmae-bg` = a fotó 800w (`vars()`, PHP `$sw2_style`) — a theme elmosott oldal-másolatai (közép / álló). PC-n nincs `.kmae-sw2-side` (az csak a telefonos galériáé). Képsorrend (2026-10-20, Raul): PC-n az első `fit()`-nél `sortPhotos()` a borító (1. fotó) típusa szerint rendezi a `card.photos`-t (`kind()`: 0 téglalap / 1 közép / 2 álló; `RANK`: álló borító → álló, közép, téglalap; közép → közép, álló, téglalap; téglalap → téglalap, közép, álló), hirdetésenként egyszer; `p._o` = eredeti sorszám → a Jelentés ezt küldi (`photo`), a párbeszéd a látott sorszámot írja. Az adatlap galériája PC-n (min-width 1025) ugyanígy rendez: `public.js` `sortGalleryLikeBrowse` (a `partials/listing-gallery.php` slide `data-w`/`data-h` = mester-méret), a DOM-ot/`data-index`-et/`data-full-urls`-t átrendezi; telefonon az eredeti sorrend. Telefon (R = a 4:3 `.kmae-sw2-media`): autó belefér → crop; álló (`a < 0.98`) → gallery; fekvő autó-dobozzal → crop (az autóra igazítva); doboz nélkül középre vágott cover.
- Gallery: a `.kmae-sw2-media` elejére két `<button class="kmae-sw2-side" data-kmae-sw2-side="-1|1" [data-single]><img></button>` (előző / következő fotó, körbe; 1 fotónál önmaga). Kattintás → `showPhoto`. Hívja: `fill`, `showPhoto` swap, init, `resize` / PC-váltás. Csak JS hozza létre, alapból `display:none` (plugin CSS); kinézet theme `browse-v2.css` („Álló (keskeny) fotó: galéria” PC, „TELEFON” blokk).
- Feltöltéskor álló fotónál tipp (nem tiltás): `photo-motor.js` `upright[]` → `p.kmae-shots__tip` az összegzés alatt (egyszerű módban a `listing-field-guards.js` a bélyegképek alá teszi); stílus theme `listing-create.css` / `listing-simple.css`.

## Lista nézet (v2)
- Mikor: `KMAE_Browse_Feed::use_list_index()` = `is_list_view()` ÉS `is_vehicle_launch()` ÉS `is_enabled()` ÉS `KMAE_Browse_Index::ready()`. Ekkor a shortcode (`browse_vehicle_wizard`) NEM kéri le a `LIST_SCAN` (1000) hirdetést; `browse-swipe.php` `$list_idx` → `$listings = KMAE_Browse_Feed::list_order( $_GET, user )` (személyre szabott ID-sorrend: ids + base ids + `personalize`, mint a kártyás nézetnél, `IDS_MAX` 5000), és `partials/browse-list.php` az oldalhoz tartozó 50 ID-hoz egy lekérdezéssel (`list_rows`: kártya-JSON, `is_reserved`, `lat/lon`) tölti a sorokat. Kikapcsolva minden a régi (WP_Post[]).
- A markup ugyanaz; két különbség: az előnézeti `<img>` `srcset` (`s` 480w, `m` 800w) + `sizes="(max-width: 1024px) 112px, 236px"` (a CSS-beli kép-szélesség), és az `src` változatlanul a `medium_large` URL — ezt `KMAE_Browse_Feed::list_thumbs()` adja (az oldal 50 sorára előtöltött meta/csatolmány-cache-sel; a kártya-JSON-ban nincs `medium_large`). Távolság-chip: `KMAE_Geo::distance_km` az index `lat/lon`-jából, a kért (nem kerekített) `lat/lon`-nal, mint a régi.
- Lapozás ugyanúgy `?oldal=N`, 50/oldal (`LIST_PER_PAGE`), a sorrend a teljes ID-listából szeletelődik.

## Markup a dizájnhoz
Raul CSS-e erre épül. Az osztálynevek stabilak; a JS (`card()`) és a PHP (`$sw2_card`) ugyanezt adja. A `browse-v2.css` csak elrendezés/pozíció/átmenet (felülírható). Mozgás csak `transform`/`opacity` legyen; `:has()`, mozgó elemen `filter`/`backdrop-filter`/animált gradient ne.

```html
<div class="kmae-swipe-browse kmae-sw2-browse">          <!-- a régi wrapper + v2 jelző -->
  …szűrő-sín, .kmae-browse-toolbar (változatlan)…
  <div class="kmae-sw2" id="kmae-sw2" data-kmae-sw2>
    <div class="kmae-sw2-stack" data-kmae-sw2-stack [data-under="next|prev"]>
      <article class="kmae-sw2-card" data-kmae-sw2-card
               data-role="current|next|prev|idle" data-id="512"
               data-favorite="0|1" data-reserved="0|1" data-tone="cool|warm|dark|light|"
               data-photo-index="0" data-photo-count="3" [data-loading]
               style="--kmae-ambient:#203040; --kmae-lqip:url(data:…)"
               [class="… is-anim"]>                      <!-- is-anim = kirepülés/visszaugrás 180 ms -->
        <div class="kmae-sw2-media">                      <!-- háttér: --kmae-ambient + --kmae-lqip -->
          <a class="kmae-sw2-open" href="…">
            <img class="kmae-sw2-img" src srcset="…480w, …800w, …1200w" sizes="100vw"
                 width height alt="" [fetchpriority="high"] [data-ready]>   <!-- data-ready = dekódolva -->
            <!-- VAGY --> <span class="kmae-sw2-nophoto">Nincs kép</span>
          </a>
          <button class="kmae-sw2-photo-prev" data-kmae-sw2-photo="-1" aria-label="Előző kép" [hidden]>‹</button>   <!-- ≥2 fotó -->
          <button class="kmae-sw2-photo-next" data-kmae-sw2-photo="1" aria-label="Következő kép" [hidden]>›</button>
          <span class="kmae-sw2-photo-count">1 / 3</span>                                                     <!-- ≥1 fotó -->
          <button class="kmae-sw2-full" data-kmae-sw2-full aria-label="Teljes képernyő"></button>             <!-- ≥1 fotó; PC fekvő fotó: a .kmae-sw2-media teljes képernyőre (swipe-v2.js full()), ott ←/→ = kép -->
          <span class="kmae-sw2-dots"><span class="kmae-sw2-dot" [data-current]></span>…</span>              <!-- ≥1 fotó (PC-n rejtve) -->
          <div class="kmae-sw2-thumbs" data-kmae-sw2-thumbs>                                                  <!-- ≥2 fotó; a JS csak az AKTUÁLIS kártyán tölti ki (thumbs()), a PHP az 1. kártyán kész -->
            <button class="kmae-sw2-thumb" data-kmae-sw2-thumb="0" [aria-current="true"]><img src="…480w" loading="lazy"></button>…
          </div>
          <span class="kmae-sw2-reserved" title="…">Foglalt</span>                                            <!-- ha foglalt -->
          <button class="kmae-sw2-like" data-kmae-sw2-like aria-pressed="false|true" title="Mentés a kedvencekhez">Mentés</button>
        </div>
        <div class="kmae-sw2-info">
          <div class="kmae-sw2-head">                                <!-- PC: a színpad bal felső sarka -->
            <span class="kmae-sw2-brand"><img src="logo" width="44" height="44" alt=""></span>              <!-- ha van logó -->
            <h3 class="kmae-sw2-title">BMW 320d Touring</h3>
            <p class="kmae-sw2-lead"><span class="kmae-sw2-lead-hp">190 le</span><span class="kmae-sw2-lead-cc">2,0 cm³</span><span class="kmae-sw2-lead-body" data-code="cabrio">Cabrio</span></p>  <!-- ha van (body: card.body.label) -->
            <p class="kmae-sw2-seller"><span class="kmae-sw2-seller-name">Kiss Péter</span><span class="kmae-sw2-seller-type">Magánszemély</span></p>  <!-- card.seller -->
            <p class="kmae-sw2-place"><span class="kmae-sw2-loc">Pest, Budaörs<em class="kmae-sw2-km">~12 km</em></span></p>   <!-- km csak távolság-rendezésnél -->
          </div>
          <p class="kmae-sw2-price" data-mode="fixed|guide|value|open">
            <span class="kmae-sw2-price-label">Fix ár | Irányár</span>          <!-- fixed + guide (price.label) -->
            <span class="kmae-sw2-price-value">5 490 000 Ft | Ajánlatot vár</span></p>
          <ul class="kmae-sw2-facts">
            <li class="kmae-sw2-fact" data-field="year|mileage|fuel|transmission|power|body" data-code="diesel|automatic|…"><strong>2016</strong><small>Évjárat</small></li>
          </ul>
          <ul class="kmae-sw2-extras">                                <!-- karosszéria (card.body) + card.extras; csak PC fekvő fotónál látszik -->
            <li class="kmae-sw2-extra" data-field="body|color|mot|owners"><strong>Piros</strong><small>Szín</small></li>
          </ul>
          <!-- fizetési mód (kmae-sw2-chips / Készpénz) 2026-10-18 óta NINCS a kártyán: csak kp létezik (Raul) -->
          <a class="kmae-sw2-msg" href="…">Hirdetés megtekintése</a>         <!-- PC (2026-10-20): CSAK ez visz az adatlapra (rendes link); telefonon rejtve -->
          <a class="kmae-sw2-cta" href="…">Részletes adatlap megtekintése</a>   <!-- PC-n rejtve -->
        </div>
      </article>
      <!-- összesen 3 article (újrahasznosítva) -->
      <p class="kmae-sw2-end" data-kmae-sw2-end hidden>Elérted a találatok végét.</p>
    </div>
    <div class="kmae-sw2-nav">
      <button class="kmae-sw2-prev" data-kmae-sw2-prev [hidden]>Előző</button>
      <button class="kmae-sw2-pass" data-kmae-sw2-pass [hidden]>Nem érdekel</button>   <!-- csak belépve -->
      <button class="kmae-sw2-next" data-kmae-sw2-next [hidden]>Következő</button>
    </div>
    <div class="kmae-sw2-bar">                                <!-- egyszer az oldalon; telefonon a peek és a Jelentés rejtve -->
      <button class="kmae-sw2-peek kmae-sw2-peek--prev" data-kmae-sw2-peek="-1" hidden>   <!-- PC: előző/következő hirdetés pirula, swipe-v2.js peek() -->
        <span class="kmae-sw2-peek__img"><img hidden></span>
        <span class="kmae-sw2-peek__text"><small>Előző hirdetés</small><strong data-kmae-sw2-peek-title></strong></span>
      </button>
      <button class="kmae-sw2-peek kmae-sw2-peek--next" data-kmae-sw2-peek="1" hidden>…</button>
      <button class="kmae-sw2-report" data-kmae-sw2-report>Jelentés</button>   <!-- vendég: <a class="kmae-sw2-report" href="belépés"> -->
    </div>
    <section class="kmae-sw2-sheet" data-kmae-sw2-sheet [data-loading] hidden>   <!-- lásd „Lenyíló adatlap” -->
      <div class="kmae-sw2-sheet__body" data-kmae-sw2-sheet-body>…a hirdetés .kmae-single-listing része…</div>
      <div class="kmae-sw2-sheet__foot"><button class="kmae-sw2-sheet__close" data-kmae-sw2-sheet-close><svg/><span class="kmae-sw2-sheet__tip">Adatlap bezárása</span></button></div>
    </section>
    <dialog class="kmae-sw2-rep" data-kmae-sw2-rep>…</dialog>   <!-- csak belépve; lásd „Jelentés” -->
    <script type="application/json" id="kmae-sw2-data">…</script>
  </div>
</div>
```
- Állapotok: `data-role` (`current` látszik; `next`/`prev` rejtett, de kész és dekódolt; `idle` = `display:none`), `.kmae-sw2-stack[data-under]` húzás/kirepülés alatt a kártya alatt látszó szomszéd, `.is-anim` a kártyán, `img[data-ready]`, `[data-loading]` (még nincs adat), `aria-pressed` a csillagon, `data-favorite` a kártyán.
- Változók: `--kmae-ambient` (a fotó átlagszíne, `transparent` ha nincs), `--kmae-lqip` (`url(…)` vagy `none`), `--kmae-ar` (a fotó szél./mag. aránya, pl. `1.5000`; nincs, ha a fotónak nincs mérete — `vars()` a JS-ben, `$sw2_style` a sablonban), `--kmae-sw2-bg` (a kártya háttere, alap `#fff`; átlátszó kártyánál a szomszéd átlátszik). Fotólapozáskor a változók és a `data-tone` a látható fotóé.
- Ikonok nincsenek a markupban (díszítés nulla): a dizájn a `data-field`/`data-code`/`data-icon`/`data-key` attribútumokra tehet ikont (pl. `mask-image`).

## Telefonos Lapozó (álló telefon, 2026-10-24)
- Teljes képernyős, sötét, TikTok-szerű nézet a böngészés fölött. JS: `assets/js/browse-lapozo.js` (`KMAE_Public` a `kmae-swipe-v2` mellett tölti), CSS: theme `assets/css/browse-lapozo.css` (`kmae_enqueue_browse_lapozo`, mind `max-width:1024px` alatt). PC-n a JS nem épít DOM-ot.
- Adat: `#kmae-sw2-data` (kártya nézet), lista nézetben `#kmae-lpz-data` (`browse-swipe.php`, `KMAE_Browse_Feed::client_state` + `client_cfg()`); a lista-sor `article.kmae-browse-row[data-id]`.
- Belépés: `.kmae-lpz-start` gomb (a `.kmae-swipe-browse` elejére) + álló telefonon koppintás a kinti kártya fotójára / „Részletek”-re / lista sorra (capture click).
- Mozdulat: fel/le = autó (3 újrahasznosított `.kmae-lpz-card`), oldalra = fotók (natív scroll-snap), dupla koppintás = kedvenc, csípés = nagyítás. ⓘ / 💬 = felcsúszó lap (`.kmae-lpz-sheet`, a hirdetés `.kmae-single-listing`-je fetch-csel, mint az `openSheet`; 💬 a `#kmae-offer`-hez görget). History: pushState 1 (Lapozó) / 2 (lap).
- Szinkron a swipe-v2.js-sel: `kmae:favorite` {id,on,source} és `kmae:lpz-goto` {id} (kilépéskor a kinti kártya oda áll).
- Érintésnél a Lapozó gombjai pointerup-ra működnek (gyors húzás után a Chrome elnyeli a click-et).
