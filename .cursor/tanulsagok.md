# Albira — tanulságok (Cursor saját fájlja)

Használat: feladat elején `Grep` a témához tartozó 1–3 kulcsszóra ebben a fájlban (pl. `chat`, `v2 PC`, `adatlap`, `manó`, `cache`), és CSAK a talált sorokat olvasd. Egész fájlt ne olvass be.
Egy sor = egy tény. Sorszám (`~123`) csak tájékoztató: előbb Grep a szelektorra/függvényre. FORRÁS > ez a fájl. Ha egy sor elavult, írd át/töröld ugyanitt.
Rövidítések: P = plugin `plugin/ki-mit-adna-erte/`, T = theme `theme/kmae/`, TC = `T/assets/css/`, SM = `TC/simple-mode/`, PJ = `P/assets/js/`.

## 0. TÖRÖLT / ELVETETT — NE építsd vissza, ne javasold újra
- Régi ajánlat-rendszer (hivatalos/ellen-ajánlat, deal, kontakt-feloldás, csillagos értékelés, `KMAE_History`, eladás utáni kérdéssor) 2026-10-10 törölve; csak `KMAE_Activator::remove_offer_system()` táblalistája említi (marad). A teszt tiltja a `KMAE_Offers` osztálynevet.
- Ajánlatok oldal + menüpont; „Hogyan működik?” oldal (`KMAE_Pages::remove_retired_pages()`); regisztráció utáni „Kezdjük el” üdvözlő; főoldali „Következő lépésed”/teendők/erősség-panel; közösségi (univerzális) statisztika a főoldalon — TILOS.
- Rendszerfigyelő (`KMAE_Cron_Monitor`), Teljesítménynapló (a `KMAE_Perf` háttér-levélküldése + `finish_response()` MARAD), LiteSpeed Cache bővítmény (a kódban maradt `litespeed_*` hívások ártalmatlanok).
- Borítókép-stúdió, API-s (Poof) kivágás, háttér-elmosás/átlátszóság, varázslós borítóválasztás — Raulnak „jobb ötlete” van.
- v2 böngésző fotó: világosítás, autó-kiemelés, háttér-elmosás, tükör, „elmosott felnagyított saját fotó” háttér, sötét színpad — elvetve; csak `saturate(1.1)` marad.
- Fotó-sorrend állítása a feltöltőnek (húzás/fel-le) törölve: csak borítókép + törlés (`photo-motor.js buildCard`, `listing-gallery-manager.php` + `public.js initGalleryManager`).
- Cégek adószáma (`META_COMPANY_TAX_ID`) törölve; a tárolt usermeta marad. SMS-es telefon-ellenőrzés nincs és nem is lesz → szövegben se ígérd.
- GYIK: +jel, csoportcím-ikon, kereső-nagyító tilos. Landing (vendég): manó tilos.
- Manó TÖRÖLVE: élő főoldal, chat ellenőrzőlista/„Mire figyelj”, forgalmi szkennelés sarka, vázlat-ablak, leírás-lépés „Ügyes voltál” (`assistant_done`).

## 1. Élő oldal, cache, telepítés
- Élő = albira.hu (`ssh albira-server`, user `duogohuc`): `~/albira.hu/wp-content/themes/kmae/`, `~/albira.hu/wp-content/plugins/ki-mit-adna-erte/`. `~/public_html` = duogo.hu teszt (csak német teszt, ld. 15.). Sosem `--delete`, sosem `wp-config.php`/`uploads`/`PROJECT-MEMORY`/`tests`.
- „Feltöltöttem, de nem változott”: előbb `curl` + md5 élő vs helyi, és a `ver=` a HTML-ben; a Cloudflare régi CSS-t adhat (`?x=$RANDOM`). HTML DYNAMIC, `.v?t=` jelzőfájl mindig MISS.
- „Szétesett az egész oldal”: a WP visszaváltott `twentytwentyfive`-ra → Raul aktiválja: `wp-admin/themes.php?theme=kmae` (van egy régi `themes/kmae-theme-2/` is „Albira” névvel — azt NE).
- Kép „szürke paca” hard refresh után: a Cloudflare 404-et cache-el a pontos URL-re (`cf-cache-status: HIT`) → Purge by URL / újrafeltöltés. Megelőzés: `KMAE_Images::process_attachment_inner` WebP-név véletlen toldalékkal.
- Plugin asset `ver` = filemtime (`KMAE_Public::asset_version_src`) → plugin JS/CSS-hez verzióemelés nem kell; `KMAE_VERSION`-t NE emeld (DB-upgrade/oldal-szinkron fut rá). Theme CSS/JS cache-bust = `KMAE_THEME_VERSION` (T `functions.php` 12. sor) emelése.
- Élő régi plugin futott („lefagy” bug): `find plugin/ki-mit-adna-erte -newer kmae-plugin.zip`; zip előtt `rm -f ../kmae-plugin.zip.new` (különben a törölt fájlok bent maradnak).
- Párhuzamos munkamenetek ugyanazt a fájlt szerkeszthetik: feltöltés előtt md5 élő vs helyi.
- Háttér/cron: a nyilvános oldal cache-ből jön, látogatás nem indít WP-Cront; szerver-cron `wp-cron.php?kmae_server_cron=1` 5 percenként. Új cron: `KMAE_Activator::schedule_cron()` (minden betöltéskor fut) + Deactivatorba is.
- Állapotfüggő belépett oldal „régi tartalmat mutat”: a `KMAE_Public::nocache_live_pages` listájában van-e (böngészés, feladás, profil, beállítások már benne).

## 2. Általános CSS / JS csapdák
- Telefon/PC határ: `max-width:1024px` / `min-width:1025px`. Headless Chrome min. ~500 px széles → telefon-képhez 390 px-es iframe-keret.
- `filter` hoverre → az elem vonatkoztatási elem lesz az abszolút ::before/::after-nek → vibrálás; helyette `background-image`/`box-shadow` (`home-cards.css .kmae-first__cta:hover`).
- `.kmae-create-notice a` (0,2,1) felülírja az osztályos linkeket → `a.osztály` szelektor kell (`listing-create.css` ~636).
- Mozgó elemre ne tegyél animációt / `:has()` / `var()`-os háttér-gradientet (újrafestés). `:root:has(body)` minden stílus-frissítéskor bejárja a DOM-ot → `:root:not(body)`.
- `backdrop-filter` vagy `container-type: size` a szülőn bezárja a benne lévő `position:fixed` elemet (lightbox, gombok).
- Belépett elemek görgetősávja rejtett (`pc-dashboard.css` ~679 `!important`) → saját sávhoz `::-webkit-scrollbar{display:block!important;height:…!important}` + `scrollbar-width:auto` (nem `thin`). Minta: `single-listing-pc.css .kmae-gallery-thumbs`.
- Belépve a fejléc a folyamban áll (`SM/header.css` ~119) → „fejléc alatt X px” = `padding-top: Xpx`, NEM `calc(var(--kmae-header-h)+X)`. Vendégnél a fejléc fix.
- Belépett oldal középre szorul (bal üres sáv) = `pc-dashboard.css .kmae-main max-width:1120px`; oldalpadding (0,4,1): `body.X.kmae-app .kmae-app-layout.is-panels-closed .kmae-main`.
- `.kmae-page__content` alapból 720 px (`theme.css` ~1319) → saját szélességű oldalnál oldal-specifikusan `max-width:none`.
- Ikonos input szövege az ikon alá fut: a `padding-left`-et a teljes `.kmae-profile-field .kmae-profile-input input` előtaggal írd.
- Sötét kártya-tokenek `body.kmae-app.kmae-theme-dark` (0,3,1) alatt élnek → ott tulajdonságot írj felül, ne tokent.
- Színcserénél az eredeti szabályt írd át a helyén, ne tegyél felülíró blokkot a fájl végére (Raul kérése).
- wpautop: a `the_content()`-en át renderelt shortcode-ba `<br>`/üres `<p>` kerül (Kapcsolat, Biztonság) → telefonon CSS-sel semlegesítve (`br{display:none}`, `p:empty`, `display:contents`); a forrásjavítás PC-t is érintené.
- Sablon-div egyensúly hibánál a `</div>` számolás gyorsabb, mint a vizuális hibakeresés.

## 3. Héj: fejléc, lábléc, háttér, menü, vendég vs belépett
- Vendég = belépett kinézet a böngészésen + adatlapon: T `functions.php kmae_is_guest_app_view()` / `kmae_uses_app_shell()` (body `kmae-app kmae-theme-dark kmae-guest-app`). Vendég főoldal = landing.
- Vendég fejléc Belépés/Regisztráció gombok: `SM/header-auth.css` (`body:is(.kmae-marketing,.kmae-guest-app)`), a vendég-appos oldal NEM tölti a `landing.css`-t. Telefonon logó 22px + gomb 34px = landing (header-auth `!important`); oldal-CSS-ben ne írd felül a méretet. Fix fejléc alatt a tartalom/sticky: `padding-top` / `top: var(--kmae-header-h,52px)` (ne `top:0`). Vendég alsó menüsor (Kezdő/Böngészés/Eladás/GYIK/Kapcsolat): `template-parts/guest-tabbar.php` + `guest-tabbar.css`; böngészés vendég sávja ugyanez a `browse-tabbar.php`-ban, body `kmae-guest-tabbar`, `kmae_show_guest_tabbar()` (főoldal+GYIK+Biztonság+Kapcsolat+ÁSZF+Adatvédelem) — ne a landing.php-be írd vissza. Vendég logó fehér halo: `header-auth.css` vége; belépett kék halo `SM/header.css` ~662 (ne nyúlj).
- Vendég infó-oldal fejléc 390 px-en: a `landing.css` `1fr auto 1fr` rácsában a gombok a logóra csúsznak → oldal-specifikusan `auto minmax(0,1fr)` + `grid-column:2` vagy `flex` (minta `landing-home.css body.home.kmae-marketing`). A logó beljebb = ugyanott a `--land-gutter` 32px `!important` felülírja a `header-auth` 12px-et; a főoldal 12px-e csak `body.home`-on él, más oldalon ugyanaz a 12px kell `!important`-tal.
- Az app-héj fejlécének/menüjének telefonon nincs közös stílusa → belépett oldal mobilosításánál oldal-specifikus fejléc + alsó menüsor (minta: böngészés `browse-mobile.css` + `template-parts/browse-tabbar.php`). Fekvő telefon (`max-width:1024` + `landscape` + `max-height:520`): kivéve `body.home.kmae-marketing` → fix `position:fixed` teljes szélességű 38px sáv, logó flex középen (ne `left:50%` absolute); böngészés `browse-mobile.css` vége (`top/left:0`, ne lebegő sáv).
- Háttér minden oldalon: `TC/site-background.css` `html::before` fix réteg (PC + telefon ugyanaz a kép), body átlátszó; új oldal-háttérnél ezt a réteget írd felül, ne `fixed` hátteret. Kép URL két helyen: `landing.css` + `site-background.css`.
- Lábléc nyíl: színe/glow `theme.css` „Generic page / footer” (`.kmae-footer__toggle-icon`), de `SM/app.css`, `app-shell-dark.css`, `landing.css` is írja a gombot. Telefonon világos üveg `theme.css` ~1900. Levágás: `html:root body{overflow-x:clip}` (`scrollbar-ghost.css`); landing `zoom:.9` → ellen-zoom.
- Fix alsó sáv eltakarja a lábléc nyilát: `theme.js` végén `body.kmae-at-bottom` → a sáv lecsúszik (`landing-home.css` + `theme.css` ~1890).
- PC szellem görgetősáv: `TC/scrollbar-ghost.css` + `T/assets/js/scrollbar-ghost.js` (`html.kmae-sb-near`); `html{overflow-x:hidden}` kell a 100vw-s vásznak miatt.
- Kedvenc ikon MINDENHOL piros szív `#e11d48` (fejléc `header.php`, app-menü `functions.php kmae_app_menu_groups`, `browse-tabbar.php`, `SM/header.css` vége `svg.kmae-icon-heart`, v1 `.kmae-swipe-like`, v2 CSS-maszk `--s2-i-star`/`--m-i-star` (név maradt, tartalom szív), chat `--fav`, `landing-popular.php`, `favorites.css`, `home-cards.css :has(> .kmae-icon-heart)`). A borító/„Ritkább modell”/értékelés csillagai nem kedvencek.

## 4. Főoldal (belépve)
- `dashboard-home.php` + `KMAE_Dashboard::get_home_state` → `start` (Step 0) / `first` (gratuláló) / `live` (élő statisztika: `partials/home-live.php` + `home-live.js`). CSS: `home-cards.css`.
- Két főkártya (Hirdetést feladok / Autót keresek): `dashboard-home.php` `if ( ! $kmae_home_first && ! $kmae_home_live )`; élő főoldalon nincs.
- Step 0: „Első lépéseid” doboz + két `.kmae-home-card`; CSS `home-cards.css` „Step 0” szakasz (980px; a telefonos szabályai a szakaszon BELÜL saját `max-width:1024px` blokkban — a fájl eleji telefon-blokk alulmaradna).
- Első hirdetés: `KMAE_Dashboard::first_listing_tips()` (csak 1 publikált hirdetésnél; „Most nem” = `_kmae_first_tips_dismissed`), `partials/home-first-listing.php`, CSS `home-cards.css` vége `.kmae-first` (fejléc 3 oszlop, gyűrű `--kf-pct`, tipp-szín `--kf-c`, ikon a tömb `icon` kulcsa). Manója áll (`animation:none`).
- „Hirdetés leírásának bővítése” → `?kmae_edit=` + `#kmae-section-ad`; görgetés-javítás `listing-field-guards.js` (simple-ready, `load` után, `behavior:instant`).
- Leírás-karakterszám: a főoldali „Jelenleg” és a varázsló 80-as minimuma ugyanúgy számol (teljes szöveg, vázakkal) — egyezzen.
- Élő kártyák: `home-live.php .kmae-live__cards` (`__pulse` SVG görbe, `.kmae-live-engage`), üresen radar/szellem-görbe/`.is-zero`; CSS `home-cards.css` „Hirdetés-kártya”. Élő frissítés: `KMAE_Dashboard::increment_views` → `KMAE_Live_Signal::bump` az eladónak.

## 5. Vendég landing (főoldal)
- Sablon `T/template-parts/simple-mode/landing.php` (`.kmae-lp-*`), CSS CSAK `landing-home.css` + `landing-home.js`; `SM/landing.css` = közös vendég-héj (háttér, fejléc, lábléc, auth). `.kmae-landing` gyökér a desktop `zoom:.9` miatt marad.
- Telefon: `landing-home.css` vége `@media (max-width:1024px)`; saját markup `.kmae-lpm-live` + `section.kmae-lpm` (3 kártya: módok, Eladóként/Vevőként váltó `[data-lpm-tabs]`, Biztonság 2×2); a PC szekciók mobilon rejtve; alsó sáv `nav.kmae-lp-tabbar` a képernyő aljához simul, 46px, a középső gomb nem lóg ki (ugyanígy böngészés `.kmae-bt` a `browse-mobile.css`-ben, belépett többi oldal `.kmae-bottom-nav` 46px a `theme.css` 1024px blokkjában); fekvő = `(orientation: landscape)` 2 oszlop. Max 2 képernyő, szövegben nincs gomb.

## 6. Belépés, regisztráció, Google, e-mail megerősítés, adatvédelem-biztonság
- Belépés telefon: `login-mobile.css`, scope `body.kmae-login-page` (a WP NEM ad `page-belepes`-t). Regisztráció: `register-mobile.css`, `body.kmae-register-page`; a telefon `padding-left` a `landing.css`-ben `!important`, ne írd felül. Álló = alulról kitöltő lap, fekvő = 2 oszlop.
- Google-gomb: `partials/google-button.php`, csak ha `KMAE_Google_Auth::is_configured()` (opció oldalanként külön). „Nincs ott”: `curl` az élő HTML-re (`kmae-google-btn`) + a mobil CSS 200-as-e.
- Redirect `kmae_success=<kulcs>` csak akkor látszik, ha a fogadó sablon `$success_messages` tömbjében van (pl. `form-login.php`, `offer-thread.php`).
- E-mail megerősítés kapuja: `KMAE_Auth::should_gate_for_verification` (`handle_forms` blokkolja a `create_listing`/`start_conversation`-t); `verify_email_link` bumpol.
- Profil törlése: `KMAE_Auth::delete_account()` — új felhasználóhoz kötött táblát ide is fel kell venni.
- User ID TILOS URL-ben/HTML-ben/JS-adatban (IDOR): nyilvános profil `?kmae_p=` (`KMAE_Pages::public_profile_key`), megerősítés `kmae_email_token`, minta `KMAE_Live_Signal::user_key`; WP szivárgások zárva (`KMAE_Privacy`). Ellenőrzés: `curl https://albira.hu/wp-json/wp/v2/users` → 401/404.

## 7. Infó oldalak (GYIK, Biztonság, ÁSZF, Adatvédelem, Kapcsolat)
- GYIK: kérdések P `templates/faq.php` `$faq_groups` (csoport-`id` egyedi), kereső inline; PC `faq.css` (világos kék+narancs, `--faq-blue*`/`--faq-orange*`, előtag `body.kmae-faq-page.kmae-faq-page:is(.kmae-marketing,.kmae-app)`), telefon `faq-mobile.css` (2026-10-24 arculat: üvegkártya, kék/narancs csoport, kereső tapad). Fekvőben NINCS bal/jobb oszlop: cím + kereső felül, tartalom középen alá (Raul: a kétoszlopos fekvő ronda). Minden kérdés alapból zárt; +jel és kereső-nagyító tilos; háttérkép látszik. A `functions.php` feltöltése a privacy-enqueue-t is viszi — ha a szerveren még az info-mobile adja az adatvédelmet, a functions.php-t ne írd felül.
- Biztonság: tartalom P `templates/security.php` (`kmae_sec_icon`), PC `security.css`, telefon `info-mobile.css` (rejti a wpautop `<br>`/`p:empty`-t); felismerés `kmae_is_security_page`. A `page.php $is_raw_shortcode`-hoz NE add (PC-t érintené).
- ÁSZF (`feltetelek`): `.kmae-legal` a `.kmae-page__content` 720 px-es oszlopában; PC középre `theme.css` „ÁSZF” (`:has(.kmae-legal)`, az Adatvédelemre is hat); telefon `info-mobile.css` VÉGE (`body.kmae-terms-page`: álló tapadó fejezet-sáv, fekvő bal tartalomjegyzék). Magas fejléc = `landing.css` `--kmae-header-h:72px` (header-auth után tölt) → oldal-CSS-ben kell `--kmae-header-h:52px` (mint `landing-home` / `faq-mobile`).
- Adatvédelem: `body.kmae-privacy-page`; telefon `privacy-mobile.css`; PC fejléc-hézag `theme.css` `.kmae-legal__admin` előtt (vendég: gombok aljától 18px, belépve `padding-top:18px`).
- Kapcsolat: `body.kmae-contact-page-view`, PC `contact.css`, telefon `contact-mobile.css` (wpautop semlegesítés).

## 8. Böngészés v1, varázsló, lista
- Új szűrő query kulcs: három listába — `class-kmae-shortcodes.php $wizard_keys`, `browse-swipe.php $restart_keys`, `browse_scan_size() $keys`.
- Saját hirdetés szándékosan kimarad a böngészésből (`KMAE_Query::get_listings author__not_in`, v2 `personalize own`) → „nem jelenik meg” = vendégként/másik fiókkal nézd; aztán `_kmae_status=active`, megye/kivitel szűrő.
- Varázsló 1. lépés (térkép): `browse-wizard-map.php`, `browse-wizard.js initMapPicker`; telefonon koppintás csak kijelöl (`pickCounty`, „Tovább” `.kmae-map-next`), PC-n azonnal navigál (tervezett teljes oldalbetöltés). Megye-státusz doboz telefonon: `browse-mobile.css` 1. szakasz (`.kmae-map-status`, a P `public.css` sötét tokenje ellen).
- Karosszéria: „Mindegy” azonnal továbblép (`initBodyPicker → goNext`).
- v1 kártya/lista/panel stílus: `browse.css` „Böngészés — „élő” felület” (`--bx-*`, ~985-től); lista sor `.kmae-browse-row__main/__titleline/__facts/__loc`. Telefon: `browse-mobile.css` + `browse-tabbar.php` (footer.php).
- Gyorsítás (mérve): `browse.css` vége „Gyorsítás” blokk; kártya keret fix conic-gradient; `swipe.js preloadAround`, `SLIDE_MS 180`; kártyakép `kmae_gallery` (1400).
- Tiltó lista a Részletes keresés panel alján; PC elrendezés `.kmae-swipe-browse` 3 oszlopos grid (`browse.css` ~545).

## 9. Böngészés v2 (kártyás) — közös
- Motor: tábla `kmae_browse_index` (`KMAE_Browse_Index`, saját séma-verzió), REST `GET kmae/v1/feed` / `me/browse` (`KMAE_Browse_Feed`); szerződés + markup: P `PROJECT-MEMORY/FEATURES/BROWSE-V2.md`. Kapcsoló `kmae_browse_v2` opció (Albira → Beállítások) vagy admin `&uj=1`; élő HTML-ben `kmae-sw2-browse`/`kmae-sw2-page`.
- Markup két helyen, együtt módosítsd: `templates/browse-swipe-v2.php $sw2_card` + `PJ/swipe-v2.js card()`. Lista nézet: `partials/browse-list.php` két ága (WP_Post / index-kártya) ugyanazt a markupot adja; új mező a kártya-JSON-ba (`KMAE_Browse_Index::card`); új `extras` csak index-újraépítés után.
- Tesztek (WP nélkül): `php P/tests/test-browse-feed.php`, `test-browse-list.php`, `test-browse-index-card.php`, `test-image-cards.php`. Régi szűrés/rendezés módosítása után kötelező. Helyi PHP: nincs mbstring/GD/pdo_sqlite (ld. 16.).
- Theme CSS: `TC/browse-v2.css` (csak v2, body `kmae-sw2-page`), + P `assets/css/browse-v2.css` (a `max-width:1024px` blokkja rejti a telefonon még nem tervezett elemeket).
- Billentyűk: `swipe-v2.js` saját keydown (←/→/Enter/Space); a régi `swipe.js` kezelője v2-ben nem fut. „Nem érdekel” gomb küld passt.
- Képsorrend csak kliens-oldalon (`sortPhotos`/`kind`), a `_kmae_gallery` nem változik; Jelentés az eredeti sorszámot (`p._o`) küldi. Az adatlap galériája ugyanígy (`public.js sortGalleryLikeBrowse`) — együtt módosítsd.
- Hirdetés-váltó „nincs”: `layout()` rejti, ha nincs több hirdetés (élő `kmae-sw2-data` `"total"`). „Új hirdetés balra került” = sessionStorage visszaugrás (`returning()`).
- Autó-felismerés feltöltéskor: `photo-subject.js` (COCO-SSD) → `_kmae_subject` → `--kmae-sx0..sy1`; régi fotókhoz admin Albira → Kártyaképek. Kártyaképek: `KMAE_Image_Cards` + `KMAE_Image_Ambient` (P `FEATURES/PHOTO-MOTOR.md`).

## 10. Böngészés v2 — PC (érvényes állapot; a 3.25.523–536 moziplakát/galéria/„doboz nélkül” leírások ELAVULTAK)
- Világoskék jeges kártya (szöveg `#0b3a66`, ikon `#008fbd`), értékek a helyükön átírva (stack::before, kártya, `.kmae-sw2-media` háttér, csempék a „Fekvő fotó” blokkban).
- Keretes elrendezés: bal adat-panel + jobb keretes fotó + alsó bélyegkép-sáv („Fekvő fotó: keretes elrendezés” + „Nem elég széles / álló fotó” blokk). HÁROM keret: téglalap ~2:1 / közép 3:2 / álló 3:4 — `swipe-v2.js FRAMES/kind()` = theme `--n-r`, együtt; narrow/gallery a mező közepén, két oldalt világos jeges elmosott másolat. Rossz helyen a kép → élő `kmae-sw2-data` JSON fotó `w`/`h`.
- Adat-panel csempék a cím alatt fix 26px (`align-self:start`); új elemet ne `center`/`1fr` sorral igazíts (ugrál). Fact-sor `.kmae-sw2-facts` `flex-wrap:wrap` + `.kmae-sw2-fact flex:0 0 auto`; P `browse-v2.css` `flex-direction:column`-t ad → felülírásnál `row`.
- „Ajánlatot vár” pirula: `.kmae-sw2-price[data-mode="open"]` a PC blokkban (a `body.kmae-browse-page` ~1753 utáni szabályok TELEFONOSAK).
- Hirdetés-nyilak a NAGY kártya keretvonalán (fele kint), jeges kapszula; a gombon ne legyen `display` (különben a `hidden` nem rejt). Kép-nyilak (`photo-prev/next`) a bélyegkép-sáv végein; `.kmae-sw2-peek` pirulák a Jelentés két oldalán.
- Adatlapra CSAK „Hirdetés megtekintése” (`.kmae-sw2-msg`) + Enter; fotó-kattintás PC-n semmi (`cursor:default`). Teljes képernyő: jobb alsó gomb → `full()` (natív fullscreen a `.kmae-sw2-media`-n, `data-fs-title`), CSS „Teljes képernyős galéria” blokk (`container-type:size` + `min(100cqw, 100cqh*var(--kmae-ar))`).
- Rendezés/nézetváltó: `.kmae-browse-toolbar display:contents`, bal szélen fix fül a „Keresés” fül fölött/alatt (`--s2-tab-c`, `--s2-ktab-h` — a Keresés-fül magasságával együtt). Nézetváltó-fül felirat-javítás kész; a rendezés-fülé nem (Raul döntése).
- Alsó sor (billentyűsúgó + Jelentés): `.kmae-sw2-bar`. Telítés: `.kmae-sw2-img saturate(1.1)`.

## 11. Böngészés v2 — telefon
- Telefonos kártya: theme `browse-v2.css` vége „TELEFON”; kép-elhelyezés `swipe-v2.js fit()/crop()` (`data-fit`); lenyíló adatlap CSAK fekvő telefonon: `openSheet()` fetch → `.kmae-sw2-sheet` (+ `#kmae-single-listing-css`), a `public.js` kötései ott nem élnek → delegált kezelő. Álló (2026-10-24, `PORTRAIT`): fotó-kattintás semmit nem csinál, a „Részletek” (`.kmae-sw2-cta`) és az Enter a hirdetés oldalára visz, mint PC-n — a panelt ne nyisd vissza. Álló kártya/fejléc (2026-10-24, 3.25.628): külön `(orientation: portrait)` blokk a fájl végén és `browse-mobile.css` végén — a fekvő blokkot ne írd felül. Fejléc állóban a landing üvegsáv (`rgba(244,251,255,.72)`, logó 26px, Belépés = header-auth üveg-pill). A szív `::before` legyen `position:absolute; translate(-50%,-50%)`: a gomb „Mentés” szövege külön rácscella, a `place-items` feljebb tolja. Telefon fekvő (2026-10-24, minden oldal): nem lehet használni — ábra `template-parts/browse-rotate.php` (footer, mindig), stílus `site-background.css` vége (`max-width:1024` + `landscape`). A régi fekvő CSS marad alatta. A `functions.php`-t ne töltsd fel, ha a verzión kívül más is változott benne.
- Az élő fotók nagy részén nincs `subject` → crop csak a Kártyaképek újrafuttatása után. Előnézet: élő HTML + `kmae-sw2-data` JSON átírása + a szerver-kártya `data-id` cseréje.

## 12. Hirdetés-adatlap
- CSS kettéválasztva: PC = `TC/single-listing-pc.css`, telefon = `TC/single-listing.css` (`max-width:1024px`, PC `--lp-*` kinézet egy oszlopban: fotó→cím→ár→adatok→kapcsolat→szöveg, `display:contents`+`order`). Belépés nélkül is betöltődik (app-héj).
- PC: fotó-blokk jeges kék kártya, tömör fehér lapok, minden írás fekete `#0a0d14`, szürke TILOS; jobb oszlop sorrendje CSS-sel (`.kmae-listing-side-panel{display:contents}` + `order`: cím → ár → adatok → bejelentés). A fotókártyán (`__photo`) tilos `backdrop-filter` (lightbox bezáródik).
- PC cím: EGY sor, háttér nélkül; motor (`__spec--engine`) és lóerő (`__spec--power`) rejtve; Teljesítmény csempe lóerőben (`KMAE_Vehicle::get_display_rows`, kW tárolva). Eladó-kártya: `.kmae-listing-facts` flex-oszlop, eladó `order:3` a helyszín (`order:4`) előtt, egy jeges kártya két fele.
- Galéria: `partials/listing-gallery.php` (csak itt), számláló bal alul, `.kmae-gallery-zoom` jobb alul → `.kmae-gallery.requestFullscreen()` (stílus `single-listing-pc.css` „2b”; a böngészés fullscreen-blokkjával együtt igazítsd). Képnagyító közös modul: P `image-zoom.js/.css` (új galéria-nézetet a `find()`-ba vedd fel).
- Sablon `single-listing.php`: cím darabolása ~128–140; „Kapcsolat az eladóval” ~94–125; eladó név + „N eladott autó” ~199–208 (csak itt, a Hirdetéseimen NINCS); céges bemutatkozás `KMAE_Company::get_intro` a `.kmae-listing-person` végén; oldalsáv ár-kártya `.kmae-listing-side-panel` csak ár mellett; fejléc-jelvény `kmae-listing-sheet__badge` külön elem.
- Jellemzők pirulák egy szín `#0b3a66`; 14 kód = `KMAE_Vehicle::traits()` 10 + `KMAE_Seller::get_badge_rows` 4. Címkés műszaki csempék = `vehicle-specs-display.php .kmae-vehicle-spec*` (a `traits` pirulákról leválasztva). Kék csak alfa-értékkel halványítható (`--sl-blue-rgb` globális).
- Hirdetés-adatlapon kedvenc-gomb nincs. Lejárt hirdetés: `templates/listing-unavailable.php` + `listing-unavailable.css` (`kmae_enqueue_listing_unavailable`).

## 13. Hirdetésfeladás, varázsló, fotók, forgalmi, lejárat
- Feladás = sima POST `/hirdetes-feladasa/` (`KMAE_Public::handle_forms → process_create_listing`); üres `$_POST` (post_max_size) vagy rossz nonce → csendes újrarender → draft-prompt; túl nagy POST → `kmae_error=upload_too_large`. Kliens kicsinyítés `photo-motor.js shrinkImage` (1920). Fotómotor (`KMAE_Photo_Motor::is_enabled`) KI → slotos ágak halottak.
- Varázsló továbblépés: `listing-wizard.js validateStep` (csak látható/required/checkValidity mezők). „Egyéb extra” required-je `public.js toggleCreateFields`-ben (pipához kötve); szerver-minimum `KMAE_Seller::EXTRA_OTHER_MIN_LENGTH`. Szerkesztés (`is_edit`) = klasszikus űrlap, nincs varázsló.
- Forgalmi-beolvasás: `partials/registration-scan.php` + `registration-scan.js/.css`; két fotónál azonnal indul (`maybeAutoStart`), egynél `AUTO_START_MS`. ID-k: `KMAE_Registration_Scan::ID_FIELDS` → `_kmae_registration_ids` → `KMAE_Sale_Contract::prefill`. Álló fotó tipp: `photo-motor.js .kmae-shots__tip`.
- Lejárat: óránkénti `kmae_expire_listings` → `KMAE_Listings::expire_listings()` (60 nap, `STATUS_EXPIRED`, Foglalt kivétel, `expiry_clock_start()`); hosszabbítás `kmae_renew_listing`; törlés mindig engedett.
- Hirdetéshez kötött adat (takarítás/törlés): `kmae_conversations`(+`_messages`, chatkép), `kmae_chat_offers`, `kmae_reports`, `kmae_private_data`; usermeta `_kmae_favorite_listings`, `_kmae_favorite_unseen`, `_kmae_browse_passed`, `_kmae_browse_contacted`. Minta: `KMAE_Listings::reset_listing()`.
- Hirdetéseim: `my-listings.php` (fejléc `minmax(0,1fr) auto auto` grid — gomb törlésekor igazítsd; 4 csempe `$ml_stats`; üres állapot), CSS `my-listings.css` (kártya hover = csak árnyék; `engineCc` SVG-hez `fill:currentColor`; üres állapot mozgás nélkül). Kártya-chipek = ikon+érték (címke tooltip), NEM azonos az adatlap címkés csempéivel → kétértelműségnél kérdezz.

## 14. Chat, Üzenetek, ajánlat, szerződés, bejelentés, tiltás
- Chat = `/uzenetek/?kmae_conversation=` (`[kmae_messages]`); fájlok történeti okból `offer-thread.*`; `send_offer_email()` általános levélküldő. Térkép: P `FEATURES/CHAT.md`. Body: `kmae-offer-thread-active` (szerződésnél `kmae-sale-contract-active`). Az `offer-thread.css`-ben nincs telefonos @media.
- Ajánlat: `KMAE_Chat_Offers` + tábla `kmae_chat_offers` (NEM `kmae_offers`); ajánlat chat-üzenetként is mentődik. Eladás CSAK az eladó [Lezárom]-ja (`close_sale` → `sold`); 7 napos cron `kmae_finalize_chat_sales` → `lapsed` (hirdetés `closed`, nem eladás; `archived`-ot NE, azt a purge törli). Visszavonás `retract` (`retracted`). Foglalt = `reserved_listing_ids()` (1 lekérdezés/oldal, ne kártyánként).
- Esemény → értesítés: ami nem `kmae_conversation_message_created`, az nem ad e-mailt/jelvényt → rendszerüzenetként mentsd (`post_system_message`, `system:` idempotency kulcs, cselekvő fél nevében); szöveg típusonként `message_kind()`.
- Élő frissítés: fingerprint a render ELŐTT, seen-jelölés UTÁNA → seen-függő érték nem kerülhet a `messages_inbox`/`conversation_thread` fingerprintbe (`KMAE_Live_Page::get_fingerprint`); kell `data-kmae-live-page` gyökér; `KMAE_Live_Signal::bump` csak a két félnek. Seen-bump csak valódi új olvasáskor. Térkép P `FEATURES/LIVE-NAV.md` + `public.js` ~800–990.
- Elrendezés: `offer-thread.php` `header.kmae-chat-top` (Vissza + állapot + hívás), jobb oszlop `aside.kmae-chat-side` mindig (`.kmae-chat-car` kártya — új adat = `$car_rows` `<li>`; Kedvenc/Bejelentés/Tiltás sorok csak betű+ikon szín). Elfogadás után a `.kmae-chat-deal` a jobb oszlop gyermeke, 2 oszlop `:has(> .kmae-chat-deal)` (634px fix, csevegő `minmax(440px,1fr)`); vevő-specifikus `:has(> .kmae-chat-checklist)`. Szűkség oka: `.kmae-container` 80px padding + `.kmae-page` felső padding → `body.kmae-offer-thread-active` alatt javítsd (`--ot-chat-h`).
- „Ajánlatot teszek” = natív `<details class="kmae-chat-offer">` (`partials/conversation-free-chat.php`), zárás `public.js` „Ajánlatot teszek panel” (`[data-kmae-offer-close]`). Olvasási pipa súgó: `partials/conversation-timeline.php` ~83.
- Szerződés: `KMAE_Sale_Contract`, DOCX kicsomagolva `templates/contract/adasveteli/` (`{{kulcs}}`), saját ZIP-író (helyben nincs ZipArchive); ellenőrzőlista `.xlsx` `build_checklist_xlsx`, szempontok `checklist_items()`; „Mire figyelj” nagyító `[data-kmae-tips-image]` (`public.js` ~959).
- Üzenetek lista: `messages.php` (`.kmae-inbox__hero`, kártya `--buyer/--seller` = a MÁSIK fél szerepe), CSS `messages.css` (panelen nincs `overflow:hidden` a Foglalt-X súgó miatt; telefon-könnyítés a fájl végén). X = per-user rejtés `hide_for_user` (`_kmae_conversations_hidden`); „törölted” sáv 3 mp CSS-animáció.
- Tiltás: `KMAE_User_Blocks` (árnyék-tiltás); új üzenet-lekérdezéshez a `hidden_messages_sql()` kell, különben kiszivárog.
- Bejelentés: `KMAE_Reports` + `kmae_reports`, admin Albira → Jelentések (`kmae-reports`, buborék `awaiting-mod`), admin-értesítés `KMAE_Live_Signal::bump(admin ID-k)` → Web Push; modális = natív `<dialog>` + `data-kmae-report-open`. Térkép P `FEATURES/REPORTS.md`.

## 15. Profil, Beállítások, Kedvencek, e-mail, manók, német teszt
- Profil: `form-profile.php` (hero + gyűrű `--pf-pct`, bal „Adataim”, jobb `aside.kmae-profile__side`), CSS `profile.css` eleje (csak `min-width:1025px` media → telefon-réteg még nincs); fájl vége = nyilvános profil `.kmae-public-profile*`.
- Beállítások: `settings.php` (mezőnevek/nonce/action változatlanok, push-kapcsoló `data-kmae-push-*`), CSS `settings.css`.
- Kedvencek: `favorites.php` (`.kmae-favorites__grid`, `.kmae-fav-card`), CSS `favorites.css`; üres állapot `.is-empty`.
- E-mail: minden levél `KMAE_Brand::wrap_email` (tone `sad`/`cheer`/`info`), beágyazott PNG-k `P/assets/images/email/` (`cid:albira-*`), szélesség `KMAE_Brand::EMAIL_IMAGES`.
- Manók: `albira.hu/wp-content/uploads/2026/10/` (`szia3`, `info3`, `ezaz2`, `szomoru3`, `csakigytovabb3`, `ugyesvoltal-3`, `lefelemutat3`, `forgalmi4`; `idokozben1` 2026/09). Csere: `grep -rn "uploads/2026/0[0-9]/"` + `class-kmae-brand.php` ~412 levél-felismerés + email PNG-k. Méret: 200 px MAGAS, `width:auto`, mellé igazított elemekkel együtt `TC/mascot-size.css` (kivétel: szomorú, forgalmi).
- Német TESZT (csak duogo.hu, csak ha Raul kéri): `de-teszt/{kmae,ki-mit-adna-erte}` (gitignore), nyelv `kmae_lang` süti / `?nyelv=de|hu` (`KMAE_I18n`); új PHP-szöveg = `strings-php.json` végére új `n` + `php-<n>.json` (újra-kinyerés TILOS), építés `python3 -I de-teszt/forditas/build.py`. Az albira forráshoz ilyenkor nem nyúlsz.

## 16. Helyi ellenőrzés, előnézet, mérés
- Előnézet WP nélkül: élő HTML `curl` → theme/plugin asset URL-ek `file://`-ra → `google-chrome --headless=new --screenshot` (`--allow-file-access-from-files`; `--window-size` magassága +100). Telefon: iframe 390×844 / fekvő 740×340. Sablon-render: kis PHP-stub.
- Mérés: node 22 + CDP (`--remote-debugging-port`, 390×844 DPR3, `setCPUThrottlingRate 4`), `LayerTree.layerPainted` + trace `RasterTask`; `html scroll-behavior:smooth` → teszt-görgetés `behavior:'instant'`.
- Helyi PHP: nincs mbstring (polyfill), GD (sudo nélkül: `apt-get download php8.3-gd libgd3` + `dpkg-deb -x`, `LD_LIBRARY_PATH=… php -n -d extension=…/gd.so`), pdo_sqlite (python3 sqlite háttérfolyamat a tesztekben), ZipArchive. `DateTime::modify` negatív napnál `sprintf('%+d days')`.
- DOCX/XLSX ellenőrzés: `soffice --headless --convert-to pdf` + `pdftoppm`. JS modell-teszt (COCO-SSD) `python3 -m http.server`-ről (file:// CORS miatt nem megy); a 8765-ös port foglalt lehet.
