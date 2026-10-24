# ALBIRA ARCULAT — design-rendszer (forrás: vendég landing, 2026-10-24)

Ezt töltsd be, ha új oldalt / oldal-réteget gyártasz (telefon vagy „pc-re…”). Nem kell újra felismerni.
Forrás-fájlok, ha pontosítani kell: `theme/kmae/assets/css/landing-home.css` (PC: eleje–1180, TELEFON: 1183–1990),
`simple-mode/landing.css` (űrlap/auth, ~290–900), `simple-mode/header-auth.css` (fejléc gombok), `site-background.css`.

## 0. Hangulat egy mondatban
Világos, jeges **kék + narancs üveg**: félig áttetsző fehér-kékes kártyák a közös háttérképen, vékony színes keret,
a kártya tetején **neon csík**, puha kék-szürke árnyék + belső fehér felső fény. Kék = eladó / fő művelet,
narancs = vevő / ajánlat / kiemelés. Sötét felület, szürke háttér, sötét doboz NINCS.

## 1. Tokenek (másold be az oldal gyökerére, saját előtaggal, pl. `--fq-*`)
```css
--x-blue: #00c7f7;          --x-blue-rgb: 0, 199, 247;
--x-blue-strong: #008fbd;   /* ikon, eyebrow, link, aktív */
--x-blue-light: #82edff;
--x-orange: #ff6e25;        --x-orange-rgb: 255, 110, 37;
--x-orange-strong: #e95e13; /* narancs szöveg, címke */
--x-orange-light: #ffc36b;
--x-ink: #202936;           /* cím, erős szöveg */
--x-ink-2: #34404d;         /* bekezdés, lead */
--x-ink-3: #465463;         /* másodlagos szöveg, leírás */
--x-muted: #5a6b80;         /* hint, „vagy”, placeholder-alap */
--x-navy: #0b3a66;          /* autó-név, ár, adat-érték */
--x-shadow-rgb: 38, 68, 90; /* MINDEN árnyék alapja */
--x-success: #15803d; --x-success-dot: #22c55e;
--x-danger: #e11d48;        /* kedvenc szív, hiba */
--x-grad-text: linear-gradient(100deg, #008fbd 0%, #00c7f7 38%, #e99525 70%, #ff6e25 100%);
--x-card-bg: linear-gradient(160deg, rgba(232,250,255,.9), rgba(255,255,255,.8));      /* telefon kártya */
--x-card-bg-orange: linear-gradient(160deg, rgba(255,241,230,.96), rgba(255,255,255,.92));
```
- Betű: `"Plus Jakarta Sans", "Segoe UI", sans-serif` (functions.php tölti, 400–800; `--kmae-font-body`). A böngészés/v2 kivétel: „Outfit”.
- Telefonon a cím-gradiens (`--x-grad-text`) helyett csak kék: `linear-gradient(100deg,#007fb8 0%,#00c7f7 50%,#007fb8 100%)` (keskeny sorban sárgászöldbe csúszna).

## 2. Háttér
Közös fix kép minden oldalon: `site-background.css` `html::before` (fehér fátyol `rgba(255,255,255,.62→.42)` + `landing-hatter.webp`, alap `#f3f9ff`).
Új oldalon a body / `.kmae-main` legyen **átlátszó**, saját hátteret NE adj (főleg ne `background-attachment: fixed`).
Díszítő „orb”: `border-radius:50%; filter: blur(54px); opacity:.5;` kék `rgba(blue,.45)` / narancs `rgba(orange,.38)`, 260–280 px (telefonon).

## 3. Tipográfia (telefon | PC)
| Elem | Telefon | PC | Stílus |
|---|---|---|---|
| Eyebrow / kicker | 11px | 12px | 800, `letter-spacing:.14em` (PC .17em), UPPERCASE, `--x-blue-strong` |
| Hős-cím | `clamp(28px, 8vw, 40px)` / lh 1.04 | 66px / 1.02 | 800, `letter-spacing:-.045em`, `--x-ink` |
| Szekció-cím | 21px / 1.2 | 44px / 1.08 | 800, `-.025em` (PC -.035em) |
| Lead | 15px / 1.5, 500 | 18–19px / 1.6 | `--x-ink-2`, max-width 420px (PC 540–620) |
| Kártya-cím (strong) | 14.5–15.5px / 1.25 | 17px | 800, `--x-ink` |
| Leírás | 12–13.5px / 1.4–1.45, 500 | 14px / 1.5 | `--x-ink-3` |
| Címke / pirula szöveg | 10.5–11.5px | 12–13px | 800 |

## 4. Felületek (dobozok)
**Üveg-kártya (telefon, fő szekció-doboz)** — `.kmae-lpm-card` minta:
```css
position: relative; padding: 20px 16px 18px; border-radius: 26px;
background: var(--x-card-bg);
border: 1px solid rgba(var(--x-blue-rgb), .4);
box-shadow: 0 14px 34px rgba(var(--x-shadow-rgb), .12), inset 0 1px 0 #fff;
backdrop-filter: blur(16px) saturate(140%); -webkit-backdrop-filter: blur(16px) saturate(140%);
```
**Neon csík a kártya tetején** (minden fő kártya/panel jele):
```css
.X::before { content:""; position:absolute; top:0; left:8%; width:44%; height:2px; border-radius:2px;
  background: linear-gradient(90deg, transparent, var(--x-blue), transparent);
  box-shadow: 0 0 14px rgba(var(--x-blue-rgb), .9); }
/* narancs változat: right:8%; left:auto; + var(--x-orange) */
```
**Kiemelt (hős) kártya**: radius 28px, keret `rgba(blue,.55)`, árnyék `0 24px 48px rgba(shadow,.2), 0 0 28px rgba(blue,.16), inset 0 1px 0 #fff`.
**PC üvegpanel** (`.kmae-lp-panel--blue/--orange`): padding 56px, radius 22px, `blur(16px) saturate(130%)`, keret `rgba(c,.6)`,
háttér `radial-gradient(circle at 7% 0%, rgba(c,.25), transparent 42%), linear-gradient(135deg, rgba(224,248,255,.94), rgba(255,255,255,.82))`
(narancsnál `93% 0%` + `rgba(255,239,226,.95)`), árnyék `0 18px 48px rgba(shadow,.16), 0 0 28px rgba(c,.1), inset 0 1px 0 #fff`.

**Belső sor / lista-elem** (kártyán belül, `.kmae-lpm-mode`): `padding:13px 14px; border-radius:18px; background: rgba(255,255,255,.9);
border:1px solid rgba(var(--c),.32); box-shadow: 0 6px 16px rgba(var(--c),.1), inset 0 1px 0 #fff;` — narancs változat: `--x-card-bg-orange` + keret `.5`.
Színváltás egy `--c` / `--c-ink` változóval (kék alap, `--open`/narancs módosító átállítja).

**Csempe (2×2 rács)** (`.kmae-lpm-safe__item`): `padding:12px; radius:18px; bg rgba(255,255,255,.9); keret rgba(blue,.28);
árnyék 0 6px 14px rgba(shadow,.06), inset 0 1px 0 #fff;` rács `repeat(2, minmax(0,1fr)); gap:10px` (fekvőben 4 oszlop).

**Info-sáv / kiemelt bekezdés** (`.kmae-lpm-panel__lead`): `padding:12px 14px; radius:16px; bg rgba(orange,.08); keret rgba(orange,.25); 13.5px 600 ink-2`.

**Kártya-rés**: kártyák között `gap:14px`, kártyán belül `gap:10px` (lista), szakasz-tető `padding-top:22px`.

## 5. Gombok
**Fő gomb (pill, telítet)** — `.kmae-lp-btn`:
```css
display:inline-flex; align-items:center; justify-content:center; gap:10px;
min-height:56px (telefonon 48–50px); padding:0 28px; border-radius:999px; border:0;
color:#fff; font-size:16px; font-weight:800; letter-spacing:.01em; text-decoration:none;
background-size:160% 100%; transition: transform .22s, box-shadow .22s, background-position .4s;
/* kék (eladó/fő):  */ background-image: linear-gradient(110deg, #007fb8, #00c7f7, #3fdcff); box-shadow: 0 10px 26px rgba(blue,.34);
/* narancs (vevő): */ background-image: linear-gradient(110deg, #ffad41, #ff6e25, #ff8a48); box-shadow: 0 10px 26px rgba(orange,.32);
/* hover: translateY(-2px), background-position:100% 0, árnyék 0 14px 34px rgba(c,.44–.46), benne az svg translateX(4px) */
```
**Űrlap-küldő gomb** (auth): teljes szélesség, `min-height:50px; radius:12px; 1rem 700`;
kék `linear-gradient(90deg,#0081c0,#00c7f7)` + `0 8px 22px rgba(0,187,243,.28), inset 0 1px 0 rgba(255,255,255,.8)`;
narancs (regisztráció) `linear-gradient(90deg,#ffb020,#ff6e25 52%,#e11d48)` + `0 0 18px rgba(orange,.28)`.

**Üveg-pill gomb (másodlagos, fejléc)** — `header-auth.css`: `min-height:40px (telefon 38px); padding:0 18px (telefon 14px); radius:999px; 700;`
kék: `bg: linear-gradient(180deg, rgba(255,255,255,.7) 0%, transparent 55%), linear-gradient(135deg, rgba(0,199,247,.34), rgba(120,222,255,.2));
border:1px solid rgba(0,170,220,.6); color:#0a5f87;` árnyék `inset 0 1px 0 rgba(255,255,255,.95), inset 0 -1px 0 rgba(0,143,189,.2), 0 0 16px rgba(0,199,247,.28), 0 6px 16px rgba(shadow,.12)`, `blur(14px) saturate(160%)`.
narancs: ugyanez `rgba(255,110,37,.32)/rgba(255,170,110,.2)`, keret `rgba(255,110,37,.6)`, szöveg `#b8450c`.

**Fehér üveg gomb** (Google-gomb minta, semleges): `min-height:44px; radius:11px; keret rgba(blue,.3); bg linear-gradient(135deg, rgba(247,253,255,.97), rgba(255,255,255,.95));
árnyék 0 3px 12px rgba(shadow,.09), inset 0 1px 0 #fff; .92rem 700 #34404d;` hover: fehér, keret `.55`.

**Szegmens-váltó (2 fül, csúszó pirula)** — `.kmae-lpm-switch`: konténer `padding:4px; radius:999px; bg rgba(blue,.1); keret rgba(blue,.28)`;
`::before` pirula `linear-gradient(145deg,#3fdcff,#00a9dc 55%,#007fb8)` + `0 6px 16px rgba(blue,.4)`, 2. fülnél `translateX(100%)` + narancs `linear-gradient(145deg,#ffad41,#ff7a2e 55%,#e95e13)`;
fül `min-height:40px; 14px 800; color ink-3`, aktív `#fff`.

Érintés: `-webkit-tap-highlight-color: transparent;` + `:active { transform: scale(.92–.97) }`. Telefonon hover-emelés helyett :active.

## 6. Pirulák, jelvények, ikonok
- **Pill-címke (felső „státusz”)** `.kmae-lpm-pill`: `padding:6px 12px 6px 10px; radius:999px; bg rgba(255,255,255,.8); keret rgba(blue,.45);
  árnyék 0 4px 14px rgba(blue,.16), inset 0 1px 0 #fff; 10.5px 800 .12em UPPERCASE blue-strong;` + `::before` 7px pötty `var(--x-blue)` + `0 0 8px rgba(blue,.9)`.
- **Chip / spec** (`.kmae-lp-hero__chips li`, `__specs li`): `padding:4–8px 10–14px; radius:999px; bg rgba(255,255,255,.78–.85); keret rgba(blue,.3–.35); 12–13px 700 ink-2/ink-3`.
- **Narancs jelvény** („Ajánlatot vár”, szalag): `padding:5–6px 12px; radius:999px; bg linear-gradient(110deg,#ffad41,#ff6e25); #fff 11–12px 800; árnyék 0 6px 16px rgba(orange,.35)`.
- **Mini címke soron belül** (`__head em`): `padding:2px 8px; radius:999px; bg rgba(c,.12); 11px 800; color c-ink; font-style:normal`.
- **„Élő” jelvény**: fehér pill `rgba(255,255,255,.9)`, szöveg `#15803d`, 7px zöld pötty `#22c55e` + `kmae-lp-ping` animáció.
- **Ikon-kör** (`.kmae-lp-icon`): 48px (telefon 38px) kör, `bg rgba(255,255,255,.86); keret rgba(blue,.55); color blue-strong; árnyék 0 0 18px rgba(blue,.2), inset 0 1px 0 #fff`.
- **Ikon-négyzet** (`__icon`): 34–40px, `radius:11–13px; bg rgba(c,.13–.14); color c-ink` (keret nélkül).
- Ikonok: vonalas SVG (`KMAE_Icons`), szín = blue-strong, kiemelt/utolsó = orange-strong.

## 7. Speciális minták
- **Függőleges idővonal** (lépések): lista `::before` 2px vonal `linear-gradient(180deg, var(--x-blue), #7fdcf5 60%, var(--x-orange))` + `0 0 10px rgba(blue,.45)`,
  csomópont 38px fehér kör (ikon-kör stílus), az utolsó narancs keretű. Szöveg: strong 14.5px + leírás 13px ink-3.
- **Ajánlat-buborék**: `radius:16px 16px 4px 16px; bg #fff; keret rgba(orange,.45); árnyék 0 10px 22px rgba(orange,.2)`; címke 10px 800 UPPERCASE orange-strong, összeg 16px 800 navy.
- **Fotó-tér kártyában**: `linear-gradient(180deg,#c9f1ff 0%,#effaff 72%,#e3f0f7 100%)` + alul `radial-gradient(ellipse at 50% 90%, rgba(32,41,54,.24), transparent 52%)` árnyék-folt.
- **Elválasztó vonal**: `height:1px; background: rgba(blue,.2)` („vagy” sorban két oldalt).

## 8. Űrlapmezők (auth minta, `landing.css` ~600–720)
```css
input, select, textarea { width:100%; min-height:42px (telefonon 46–48px); padding:9px 13px; border-radius:11px;
  border:1px solid rgba(0,199,247,.35); background: rgba(255,255,255,.92); box-shadow: inset 0 1px 0 #fff;
  color:#202936; font: inherit; font-size:.95rem (telefonon min. 16px, különben iOS ráközelít); }
:focus { outline:none; border-color: rgba(0,200,245,.55); box-shadow: 0 0 0 3px rgba(0,200,245,.15); }
::placeholder { color: rgba(90,107,128,.75); }
label { display:block; margin-bottom:6px; .88rem 600 #34404d; }
.hint { .8rem / 1.4 #5a6b80; }
select: appearance none + két 6px-es gradient-háromszög nyíl #008fbd, padding-right:36px.
```
Auth-doboz: `radius:20px; padding:24px 26px 26px; max-width:430px` (reg. 540px), háttér `radial(6% 0%, rgba(blue,.2)) + linear(135deg, rgba(240,252,255,.9), rgba(255,255,255,.82))`, `blur(20px) saturate(120%)`, + neon csík.

## 9. Telefonos keret (layout)
- Media: `@media (max-width: 1024px)`; fekvő: `@media (max-width:1024px) and (orientation: landscape)` → 2 oszlop (`minmax(0,1fr) minmax(0,380px)` vagy `repeat(2,minmax(0,1fr))`), csempék 4 oszlop.
- Gutter **16px** (info-oldalakon 14px), tartalom `max-width: 560px; margin-inline:auto`. Egy oszlop, központozott hős (`text-align:center`).
- Alul hely az alsó sávnak: `padding-bottom: calc(70px + 30px + env(safe-area-inset-bottom,0px))`. `[id] { scroll-margin-top: 80px }`.
- **Fejléc telefonon** (vendég): `position:fixed; background: rgba(244,251,255,.72); blur(20px) saturate(170%); border-bottom:1px solid rgba(blue,.28); box-shadow:0 8px 24px rgba(shadow,.1)`;
  rács `grid-template-columns: auto minmax(0,1fr)` + gombok `grid-column:2` (a `landing.css` `1fr auto 1fr` rácsa 390px-en a logóra tolja a gombokat — oldal-specifikusan írd felül). Belépett app-héj fejlécének telefonon NINCS saját stílusa → oldalanként kell.
- **Alsó app-menüsor** (`.kmae-lp-tabbar`, belépve `browse-tabbar.php` minta): `position:fixed; left/right:12px; bottom: calc(10px + safe-area); height:70px; max-width:520px;
  radius:26px; bg rgba(255,255,255,.78); keret rgba(255,255,255,.95); blur(24px) saturate(180%); árnyék 0 18px 44px rgba(shadow,.28), 0 0 0 1px rgba(blue,.22), inset 0 1px 0 #fff`;
  5 fül, 10.5px 800 ink-3; aktív: blue-strong + ikon-pill `rgba(blue,.16)` + `inset 0 0 0 1px rgba(blue,.35)`;
  középső FAB 58px kör `linear-gradient(145deg,#3fdcff,#00c7f7 45%,#007fb8)`, 4px fehér keret, `margin-top:-26px`, pulzáló gyűrű.
  Az oldal alján elrejtve: `body.kmae-at-bottom .X { opacity:0; transform: translateY(140%); pointer-events:none }` (theme.js adja).

## 10. Mozgás
- Belépő: `kmae-lp-rise` (.9s `cubic-bezier(.2,.8,.2,1)`), görgetésre `[data-lp-reveal]` + `.is-in` (landing-home.js).
- Folyamatos: csak apró dísz (lebegés `kmae-lpm-bob` 6s, élő-pötty ping, FAB pulse). Telefonon mozgó elemre ne tegyél `backdrop-filter`-t, végtelen `box-shadow` animációt, `:has()`-t (lagg — lásd Üzenetek/Böngészés tanulság).
- Hover-ugrás: `translateY(-2px)` gomb, `-4/-6px` kártya (csak PC).

## 11. Tilos / Raul döntései
- Sötét háttér/doboz, szürke felület, bordó; manó a vendég landingen (manó csak belépve él).
- Hover-re `filter` (vibrál), `background-attachment: fixed`, globális szelektor — mindig oldal-specifikus body-osztály alatt.
- Kedvenc mindig piros szív `#e11d48`.
