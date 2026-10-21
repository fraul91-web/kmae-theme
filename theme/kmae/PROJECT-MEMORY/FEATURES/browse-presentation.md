# FEATURE — BROWSE / SEARCH PRESENTATION

## PURPOSE
Identify browse/archive surfaces and apply the browse visual layer.

## READ FIRST
- `functions.php::kmae_is_browse_page()`
- `functions.php::kmae_enqueue_browse()`
- `assets/css/browse.css`

## MOBILE (telefon álló + fekvő, 2026-10-11)
- `assets/css/browse-mobile.css` (`kmae_enqueue_browse_mobile`, `kmae-browse` után): minden szabály `@media (max-width: 1024px)` alatt, fekvő telefon = `(orientation: landscape) and (max-height: 560px)`. Asztalon csak `.kmae-bt` és `.kmae-map-list` rejtése hat.
- Saját fejléc (lebegő üveg, csak `.kmae-header__mobile-top`), alsó menüsor `template-parts/browse-tabbar.php` (`.kmae-bt`, a `footer.php` húzza be böngészés oldalon; vendég/belépett tételek; fekve bal oldali sáv). A régi `.kmae-bottom-nav`, drawer, lábléc itt rejtett.
- Találatok: rács = [Keresés pirula | rendezés + nézetváltó] / kártya; Részletes keresés = alsó lap (fekve jobb panel); lista sor = `display: contents` a `__main`-en.

## V2 KÁRTYÁS NÉZET (2026-10-17 PC, 2026-10-19 telefon)
- `assets/css/browse-v2.css` (`kmae_enqueue_browse_v2`, prio 102, handle `kmae-theme-browse-v2`), feltétel `kmae_is_browse_v2_cards()` = plugin `KMAE_Browse_Feed::use_card_index()`; ugyanez adja a body `kmae-sw2-page` osztályt (`kmae_body_classes`) → a `.kmae-main` 1120 / `.kmae-page-wrap` 960 korlát feloldása `:has()` nélkül.
- PC (2026-10-18, theme 3.25.516, Raul referencia-képe): a fotó a színpad magasságához igazítva, SAJÁT arányával, középen (3.25.517: `.kmae-sw2-open` `--s2-pw` = min(színpad-szél., magasság × `--kmae-ar`), nyújtás/vágás nincs; keskeny fotónál 3.25.523-tól „moziplakát”: a fotó a jobb szélhez simul, bal széle `--s2-fade` maszkkal beleolvad a `.kmae-sw2-open` saját hátterébe (a fotó `--kmae-ambient` színéből kevert sötét színpad + finom fény + szemcse); tükör/elmosás nincs, a `::before/::after` nem használt; 3.25.524: az autó doboza (`--kmae-sx0/sy0/sx1/sy1`, plugin `_kmae_subject`) korlátozza a `--s2-zoom`-ot (fent/lent nem vág az autóba) és a `--s2-fade`-et (csak az autó előtti hátteret olvasztja); a színpadot kitöltő fotó cover, effekt nélkül) ( a régi `hatter.webp` háttér + keretes fotó + tükörkép törölve). `.kmae-sw2-media` = abszolút teljes kártya, `::after` = bal/alsó sötét átmenet; `.kmae-sw2-info` abszolút teljes kártya, `.kmae-sw2-head` `display: contents`, sorrend `order`-rel: logó, cím, műszaki sor (elválasztóval), nagy narancs ár, helyszín, pirulák (eladó típusa + `.kmae-sw2-lead` LE/cm³/karosszéria egymás alatt), adatlap link. Jobb fent: csillag (`.kmae-sw2-like`) + ár-felirat pirula (`.kmae-sw2-price-label`, „Ajánlatot vár”-nál maga a `price-value`, a `<p>` `display: contents`). Lent bélyegkép-sáv (`.kmae-sw2-thumbs`, `--s2-thumbs-h`) + „1 / N”; kép-nyilak/pöttyök rejtve; a két nagy oldalnyíl (`.kmae-sw2-prev/next`) = HIRDETÉS váltás. Alatta `.kmae-sw2-bar` (3.25.616: a színpad szélességű 3 oszlopos grid — bal: előző, jobb: következő hirdetés pirula `.kmae-sw2-peek`, középen a Jelentés); a toolbar (3.25.530) `display: contents`, a rendezés (+ km-gomb) a bal szélen a fix „Keresés” fül fölött, a nézetváltó alatta, 56px-es fülek, hoverre nyílnak (`--s2-tab-c`, `--s2-ktab-h`); a Jelentés `<dialog>` stílusa is itt (`.kmae-sw2-rep*`). Markup: plugin `FEATURES/BROWSE-V2.md` „Markup a dizájnhoz”.

- Adat-panel (3.25.535, Raul: a feliratok ne lógjanak a fotóba): MINDEN fotó-módban (kitöltő/moziplakát/galéria) a `.kmae-sw2-info` önálló sötét üveg panel bal fent (`--s2-ip-x` = a kártya bal széle, `--s2-pad`, 3.25.540-től; `--s2-ip-w`, `--s2-ip-max` = a bélyegkép-sáv fölött ér véget), grid-area-kkal: logó | eladó típusa / cím / LE·cm³·karosszéria pirulák / ár-doboz (Fix ár / Irányár felirat + összeg; „Ajánlatot vár” homokóra-koronggal) / műszaki 2×2 csempe (címkével) / helyszín / „Részletes adatlap” narancs gomb (PC-n is látszik). Csillag egyedül jobb fent, az „1 / N” jobbra lent. Alacsony ablak (`max-height: 800px`): logó a cím mellett, eladó a helyszín sorában, csempe-címkék rejtve. A galéria `--g-l` a panel jobb széléhez igazodik, `--g-bot: 0`. Az alábbi „L” alakú / bal oldali feliratos leírások elavultak.
- Moziplakát sáv színe (3.25.529, Raul: „ne mindig szürke”): a fotó melletti sáv a fotó BAL SZÉLÉNEK színeit viszi tovább — plugin `swipe-v2.js` `edge()`/`sampleEdge()` (dekódolás után vásznon a bal 2/24 oszlop 6 sávban, fotónként egyszer, cache `edges[p.s]`, túl világos szín sötétítve) → `--kmae-edge-bg` (függőleges átmenet, a `.kmae-sw2-open` hátterének alsó rétege) + `--kmae-edge` (fény + a `::after` bal sötétítés árnyalata `--s2-tint`); minta nélkül a régi ambient-es sötét színpad.
- [ELAVULT PC-n, 3.25.568 óta lásd „Nem elég széles / álló” lent] Álló fotó (3.25.526): `data-fit="gallery"` (plugin `swipe-v2.js` `fit()`) → a fotó saját arányával a feliratok melletti sáv közepén (`--g-x/--g-w/--g-h`, a `.kmae-sw2-open` maga a fotó doboza, z 2), két oldalt `.kmae-sw2-side` előző/következő kép (rotateY, `--g-peek` sáv látszik); a széles/kicsit szélesítendő fotó marad a moziplakát. Blokk: `browse-v2.css` „Álló (keskeny) fotó: galéria”. 3.25.532: a feliratok „L” alakban (`.kmae-sw2-info` grid: bal oszlop `--g-col` = logó/cím/ár/helyszín, alsó sor `--g-bot` = műszaki sor/eladó/LE-cm³-karosszéria), a fotócsoport a `--g-l`…`--g-r` mezőben középen; „1 / N” jobbra; keret = statikus átmenetes `border` (padding-box/border-box), animáció nélkül.
- Autó köré vágás (3.25.534, PC + telefon): `data-fit="crop"` (plugin `fit()`/`crop()`) → `object-position: var(--kmae-cx) var(--kmae-cy)`, PC-n maszk nélkül teljes szélesség (a moziplakát/galéria csak akkor, ha az autó nem vágható ki).
- Telefon (3.25.534, `browse-v2.css` vége „TELEFON”, max-width 1024): világos üveg kártya, felül 4:3 fotó (logó, csillag, nyilak, pöttyök, „1 / N”), alatta cím, LE·cm³, ár + „Részletek ›” (a `.kmae-sw2-cta` szövege CSS-ből), helyszín | eladó típusa (`grid-auto-flow: dense`), 3×2 műszaki csempe (`--m-i-*` ikonok); galéria = középen a fotó, két oldalt a `.kmae-sw2-side`; fekvő telefon = plugin 2 oszlop. Tokenek `--m-*`.
- PC fekvő fotó = KERETES elrendezés (3.25.566, Raul referencia-képe; felülírja a fenti kitöltő/moziplakát/crop PC-képet): `browse-v2.css` „Fekvő fotó: keretes elrendezés” blokk, szelektor `.kmae-sw2-card[data-kmae-sw2-card]` (minden kártya), méretek a `.kmae-sw2` gyökéren (`--f-w` panel, `--f-x` keret bal széle, `--f-bot`, `--f-c1`). Bal panel: logó (2 sor magas) + cím, LE·cm³, 2×2 csempe, `.kmae-sw2-extras`, eladó+helyszín+`.kmae-sw2-cta` nyíl, alul `.kmae-sw2-like` („Kedvencekhez” CSS `content`) + ár. Jobbra átmenetes keretű fotó (cover, `--kmae-cx/cy` a KERET arányára, `swipe-v2.js fit()`), benne „1 / N”, `.kmae-sw2-full`, a hirdetés-nyilak (`.kmae-sw2 .kmae-sw2-prev/next`). Alul `media::after` = sáv-panel, a két végén a `photo-prev/next`. (3.25.567: csillag/ár/„1 / N” a keret sarkaiban, Kedvencekhez gomb nincs.)
- PC nem elég széles (`data-fit="narrow"`) / álló (`gallery`) fotó (3.25.568, Raul 2 referencia-képe): ugyanaz a keretes alap + „Nem elég széles / álló fotó” blokk (`:is([data-fit="narrow"], [data-fit="gallery"])`): FIX arányú keret teljes magasságban (3.25.573: `--n-r` 3:2 közép / 3:4 álló = plugin `swipe-v2.js` `FRAMES`; `--n-w`, `--n-l`: narrow bal szél, gallery középen; a kép cover), a mező többi része `media::before` = elmosott lqip + közép (3.25.577): jobbra egy elmosott másolat a fotóból; álló (3.25.574): két felismerhető, elmosott másolat ugyanabból a fotóból a keret két oldalán (`--kmae-bg` = fotó 480w, plugin `vars()` + PHP `$sw2_style`, `--g-o` átfedés); panel: cím, LE·cm³, csempék, lent a „Hirdetés megtekintése” gomb (3.25.584: a régi `info::before` vonal helyén, mindhárom típusnál fix 22px-re az eladó sor fölött), eladó + helyszín (extras/cta/full rejtve; a sáv kép-nyilai 3.25.572 óta itt is látszanak); ár és csillag (3.25.569, Raul: mindhárom típusnál ugyanúgy) a KERET bal / jobb felső sarkában (`--n-l`/`--n-w`-ből), „1 / N” a keret bal alsó sarkában (3.25.571, mindhárom típusnál). A régi PC galéria (`--g-*`, `.kmae-sw2-side`) törölve.
- Fotó-szín (3.25.580, PC): csak `.kmae-sw2-img` `saturate(1.1)` a teljes képen; fény, háttér-elmosás, autó-kiemelés visszavonva (Raul).
- „Hirdetés megtekintése” (3.25.581, PC, mindhárom típus): kék `.kmae-sw2-msg` gomb a csempék és az eladó/helyszín sor között (grid-area `msg`), az adatlap-nyíl (`.kmae-sw2-cta`) PC-n rejtve; PC-n a lenyíló adatlap nem nyílik (plugin JS).
- Lenyíló adatlap (3.25.533, PC + telefon): `browse-v2.css` vége „Lenyíló adatlap” (`.kmae-sw2-sheet*`, media-guard nélkül + telefonos `max-width:1024px` blokk: a felirat ott mindig látszik). A betöltött tartalom stílusa a `single-listing.css` (a plugin JS az első megnyitáskor a `<head>`-be teszi). Működés: plugin `BROWSE-V2.md` „Lenyíló adatlap”.

## THEN IF NEEDED
- header active-state logic;
- plugin map for archive template / `kmae_browse` shortcode.

## DO NOT LOAD UNLESS RELEVANT
- listing-create CSS.

## ENTRY POINTS
`kmae_listing` archive, slug `hirdetesek`, or `kmae_browse` shortcode.

## DATA USED
Theme only uses query/slug/content/auth state.

## FLOW
Route detection → `kmae-browse-page` → browse.css → external/plugin markup.

## DEPENDENCIES
Plugin owns actual filtering/search/result generation.

## SIDE EFFECTS
None in theme.

## CHANGE RISKS
Archive and page share one detector; a change intended for one can affect both.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** BROWSE PRESENTATION  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** browse CSS missing; guest/logged-in browse visual mismatch; search results markup does not match theme selectors.

**READ FIRST:** `functions.php::kmae_is_browse_page()` (`82–102`) → `kmae_body_classes()` → `kmae_enqueue_browse()` (`478–489`) → `assets/css/browse.css`.

**EXPECTED FLOW:** archive/page/shortcode detector → body class → CSS handle → plugin/archive DOM → visual rules.

**CHECKPOINTS:** detector true? body class present? style loaded? DOM matches selector? data itself correct?.

**IF X → GO TO:** DOM/data wrong → plugin/archive boundary; DOM correct but visual wrong → browse CSS only.

**SEARCH TERMS:** `kmae_listing`, `hirdetesek`, `kmae_browse`, `kmae-browse-page`, `kmae-browse`.

**DO NOT LOAD UNLESS:** listing-create CSS, offers/chat, full plugin until DOM/data boundary fails.

**PLUGIN BOUNDARY:** browse/search markup and behavior are plugin/archive-owned; implementation UNVERIFIED.

**CHANGE RISKS:** page is supported for guest and logged-in with different CSS dependency parent.

**REGRESSION CHECKS:** archive + page/shortcode detection; guest + logged-in browse; one mobile/desktop breakpoint.

**VERIFICATION STATUS:** VERIFIED theme routing/presentation.
