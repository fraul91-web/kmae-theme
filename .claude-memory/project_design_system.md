---
name: project-design-system
description: AZ ÉRVÉNYES arculat (2026-09-15): világoskék + világos narancs, üveges keret, neon fénycsík — a Főoldal és a Hogyan működik a minta; minden átalakítandó oldal ehhez igazodik
metadata:
  type: project
---
2026-09-15-én Raul kimondta: **ez a jelenlegi arculat, mostantól ezt használjuk**, és a még át nem alakított oldalakat is erre hozzuk. A korábbi jegyzetek (sötét téma, "később jön a világos téma", "a wizard aktív lépéskártyája a minta") elavultak, töröltem őket.

**Mintaoldalak (csak ezekből olvass ki értéket, ne böngészd az összes oldalt):**
- Főoldal kártyái: `theme/kmae/assets/css/home-cards.css` (`--hc-*` tokenek, `.kmae-home-card--sell` kék / `--buy` narancs)
- Hogyan működik: `theme/kmae/assets/css/how-it-works.css` (`--how-*` tokenek, `.kmae-how-flow`, `.kmae-how-trust`)

**Színek:** kék `#00c7f7` (rgb 0,199,247), erős kék `#008fbd`, világos kék `#82edff`; narancs `#ff6e25` (rgb 255,110,37), erős narancs `#e95e13`, világos narancs `#ffc36b`; írás `#202936`, másodlagos `#34404d`, halvány `#465463`. Kék = eladó/elsődleges, narancs = vevő/CTA/akcentus.

**Nagy üvegpanel (szekció):** `border: 1px solid rgba(szín,.58–.75)`; `border-radius: 22px`; háttér `radial-gradient(circle at 7% 0%, rgba(szín,.25), transparent 42%)` + `linear-gradient(135deg, rgba(224,248,255,.94), rgba(255,255,255,.82))` (narancsnál `rgba(255,239,226,.95)`; vegyes kék+narancs: két sarok-radial + `rgba(238,251,255,.94)→rgba(255,246,239,.92)`); árnyék `0 18px 48px rgba(38,68,90,.16), 0 0 28px rgba(szín,.1), inset 0 1px 0 #fff`; `backdrop-filter: blur(16px) saturate(130%)`.

**Neon fénycsík a panel tetején:** abszolút, `top:0; left:5%; width:50%; height:2px`, `linear-gradient(90deg, transparent, szín, transparent)`, `box-shadow: 0 0 16px rgba(szín,.9)`. Elválasztó vonal: 1px, `rgba(szín,.95)` középen, `0 0 9px rgba(szín,.5)` fény.

**Kártya (főoldali minta):** `19px` sarok, keret `rgba(szín,.7)`, háttér `linear-gradient(145deg, rgba(222,248,255,.94), rgba(255,255,255,.84))` (narancs: `rgba(255,236,222,.95)`), árnyék `0 0 24px rgba(szín,.16), 0 14px 34px rgba(38,68,90,.14), inset 0 1px 0 #fff`; halvány (opacity .16) háttérkép + hatszög-háló dísz; belső szöveglap fehér üveg `rgba(255,255,255,.76)`, `16px` sarok, `blur(10px)`.

**Belső elemek:** fehér `rgba(255,255,255,.7–.82)`, vékony színes keret `rgba(szín,.3–.9)`, `inset 0 1px 0 #fff`, lágy színes glow `0 0 18–20px rgba(szín,.18–.2)`. Ikonkör: kerek, fehér, színes keret, színes rajz. Kiemelt kör (lejátszás): `linear-gradient(145deg, világos, szín)`, fehér ikon.

**Gombok:** kék `linear-gradient(110deg,#007fb8,#00c7f7)`, narancs `linear-gradient(110deg,#ffad41,#ff6e25)`, fehér írás, 800-as vastagság, `12px` sarok (főoldali CTA: pirula `999px`), árnyék `0 8px 24px rgba(szín,.3)`; hover `translateY(-2px)` + `filter: brightness(1.08)`.

**Tipográfia:** "Outfit", "Space Grotesk", system-ui; nagy cím 800–850, negatív betűköz (-.03…-.045em); színátmenetes kiemelés `#008fbd → #00c7f7 → #e99525 → #ff6e25` (background-clip:text); felirat (eyebrow) 11px, 800, `letter-spacing .17em`, színes.

**Már átalakított oldalak (2026-09-15):** Saját hirdetéseim (`my-listings.css`), belépett fejléc (`simple-mode/header.css`), Keresés/Autók (`browse.css`, body `kmae-browse-page` — vevői út, ezért narancs panel/fő gomb/kijelölés, kék rámutatás; felülírja a plugin `public.css` sötét szabályait és a `theme.css` fehér `!important` írásait).

**How to apply:** Ha Raul egy oldalt átalakíttat, ezekkel az értékekkel dolgozz, az adott oldal saját CSS-fájljában, a meglévő szabályt a helyén módosítva (ne fűzz felülíró blokkot a fájl végére). Csak PC — [[pc-only]]. Kapcsolódik: [[feedback-simplest-fix-first]], [[feedback-scope-stop-rule]].
