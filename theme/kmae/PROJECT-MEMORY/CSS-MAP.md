# CSS MAP — CASCADE + DOM OWNERSHIP

## Load matrix

| CSS | Guest | Logged-in | Condition | Main responsibility |
|---|:---:|:---:|---|---|
| `theme.css` | ✓ | ✓ | always | shared base, shell primitives, generic/legacy components |
| `simple-mode/landing.css` | ✓ | – | all logged-out | guest marketing/auth visual system |
| `header-auth.css` | ✓ | – | all logged-out (enqueue `kmae_assets`, `!is_user_logged_in()`) | fejléc Belépés/Regisztráció üveg-pill gombok, főoldalon és vendég-appos oldalakon egyformán (`body:is(.kmae-marketing, .kmae-guest-app)`) |
| `site-background.css` | ✓ | ✓ | all pages (enqueue priority 999, `kmae_enqueue_site_background`) | közös oldalháttér = landing kép; `!important`-tal felülírja a belépett sötét héjat (`app-shell-dark` ::before/::after kikapcsolva) |
| `landing-home.css` | – | – | NOT enqueued since 2026-10-11 (guest front page = browse) | landing sections `.kmae-lp*` |
| `login-mobile.css` | ✓ | – | `is_page('belepes')`, vendég (`kmae_enqueue_login_mobile`, prio 100, dep `kmae-landing`) | belépés oldal telefonon: álló (≤600px) app-képernyő, fekvő (landscape, max-height 500px) 2 oszlop; `body.kmae-marketing.page-belepes` scope, lábléc rejtve; asztalira nem hat |
| `simple-mode/app.css` | – | ✓ | all logged-in | application/dashboard/hub base |
| `simple-mode/pc-dashboard.css` | – | ✓ | all logged-in | PC dashboard/sidebar/info layout |
| `simple-mode/app-shell-dark.css` | – | ✓ | all logged-in, late | dark app shell overrides |
| `simple-mode/header.css` | – | ✓ | all logged-in, late | logged-in header/topbar |
| `home-cards.css` | – | ✓ | all logged-in | dashboard/home-card visuals |
| `listing-create.css` | ✓ | ✓ | create page | wizard/listing-create presentation (shared base; NO `.is-simple` rules) |
| `listing-simple.css` | ✓ | ✓ | create page in simple mode (`?kmae_edit` not full / `?kmae_draft&kmae_mode=simple`) | simple listing editor — single source |
| `browse.css` | ✓ | ✓ | browse/archive | browse/search presentation |
| `faq.css` | ✓ | ✓ | FAQ page | FAQ page |
| `security.css` | ✓ | ✓ | Biztonság page (`kmae_is_security_page`, PC only) | Biztonság page (`.kmae-sec-*`) |
| `contact.css` | ✓ | ✓ | contact page | contact page |
| `single-listing.css` | –* | ✓* | singular listing inside logged-in shell enqueue | listing body presentation |
| `my-listings.css` | – | ✓ | my-listings | owner listing cards/actions |
| `offer-thread.css` | – | ✓ | uzenetek + `?kmae_conversation` | chat thread UI |
| `sale-contract.css` | – | ✓ | uzenetek + `?kmae_conversation` + `&kmae_contract=1` (instead of offer-thread.css) | adásvételi szerződés aloldal + print |
| `messages.css` | – | ✓ | uzenetek (no thread) | Üzenetek conversation list |
| `listing-unavailable.css` | ✓ | ✓ | singular listing + `KMAE_Post_Types::is_unavailable_for_viewer()` (`kmae_enqueue_listing_unavailable`, prio 101) | lejárt hirdetés „Ez az autó már nem elérhető” + hasonló autók |
| `profile.css` | – | ✓ | profile | profile UI |
| `settings.css` | – | ✓ | settings | settings UI |
| `google-complete.css` | – | ✓ | Google completion | completion/auth override |

`*` In this snapshot `single-listing.css` is enqueued from the logged-in-only app-shell callback, so a logged-out singular listing does not receive that enqueue from the theme.

## Major selector families and markup ownership

### `theme.css`
- Theme-owned: `.kmae-header*`, `.kmae-drawer*`, `.kmae-footer*`, `.kmae-bottom-nav*`, `.kmae-page*`, common button/container classes.
- Mixed/external: `.kmae-auth*`, `.kmae-wizard*`, `.kmae-swipe-*`, notices and several plugin-facing primitives.
- Use as first cascade layer, not feature truth.

### `landing.css`
- Landing sections moved out (2026-10-11) to `landing-home.css` (`.kmae-lp*`); here only the `.kmae-landing` desktop zoom hook remains.
- Plugin-facing auth markup: `.kmae-auth*`, `.kmae-form*`, `.kmae-account-type*`, `.kmae-phone-input*`, `.kmae-google-btn*`.
- Remote page background is encoded in CSS.

### `app.css`
- Core logged-in structure: `.kmae-app*`, `.kmae-app-layout`, `.kmae-app-content`.
- Many `.kmae-dash*`, `.kmae-hub*` selectors style plugin dashboard/hub markup.
- Contains responsive dashboard/hub behavior and reduced-motion adjustments.

### `pc-dashboard.css`
- Strongly plugin-markup coupled: `.kmae-pc*`, `.kmae-app-sidebar*`, `.kmae-app-info*`, `.kmae-tip*`.
- If dashboard markup changes, inspect plugin dashboard partial before editing CSS.

### `app-shell-dark.css`
- Logged-in page background is pure CSS (3.25.450): `body.kmae-app.kmae-theme-dark::before` (gradients: blue corners, orange/white arcs, SVG dot patches) + `::after` (SVG hex grid masked to top-left/bottom-right corners). No image, no animation.
- Late shell overrides anchored on `body.kmae-app.kmae-theme-dark` and `.kmae-hub*`.

### `header.css`
- Strongly coupled to `header.php` classes.
- Desktop threshold 1025px; mobile/tablet <=1024.
- No header background image (removed; header is transparent over the page background).

### `home-cards.css`
- `.kmae-home-card*` and `.kmae-home-greeting*`.
- Most markup not emitted by theme itself; expected in plugin dashboard partial.
- No card background images (`--hc-bg` / `::after` image layers removed).

### `listing-simple.css`
- Simple editor ONLY (`.kmae-create-page.is-simple`, tokens `--ls-*`), loaded after `kmae-listing-create`. Layout: header card, vehicle board grid (col 1: specs grid + emblem, body cards 5×2; col 2: photo column spanning both rows — in-flow, never overlaps; shows a viewer: 16:10 stage with arrows, ★/trash tools, „Fotók megtekintése”, 5-up thumb strip, low upload button; the gallery manager/previews are hidden sources; `.kmae-simple-lightbox` is unscoped (body)), traits 5-col, ad row (title|county|city), sale (3 mode cards + price/minimum column), extras 3×2, save bar (not sticky). Brand picker = logo tile grid (`.kmae-catalog-list--logos`), models = vertical list. Hints forced visible (`.kmae-hint` white-trap override).
- `listing-create.css` keeps only 4 unscoped `kmae-simple-*` neutralizers used by the wizard (`-top`, `-specs` display:contents; `-bodies`, `-count` display:none).

### `browse.css` additions (2026-10-03)
- End of file: browse toolbar (sort menu, radius chip, company view switch), company list view rows + pager, `.kmae-dist*` distance modal (unscoped class, appended to body).

### `single-listing.css` / `profile.css` additions
- `single-listing.css` (fájl vége): „Hirdetés bejelentése” `.kmae-report-entry` (jobbra igazítva az oldalpanel alján) + `.kmae-report-open` (halk szöveggomb, hover narancs) + `dialog.kmae-report` ablak (prefix `.kmae-single-listing.kmae-listing-sheet`).
- `single-listing.css`: `.kmae-single-sale-state.is-reserved` („Foglalt”, narancs) / `.is-sold` („Eladva”, a Foglalt stílusát kapja) / `.is-reopened` („Újra elérhető”, kék) + `.kmae-listing-seller__sold` / `__sold-icon` („N eladott autó” kék üveg pirula a profilnév mellett) + `.kmae-single-offer-count` (oldalpanel, ár alatt).
- `.kmae-public-profile__intro` in `profile.css` (company intro card on the public profile, orange seller accent); `.kmae-profile-intro` textarea + counter.

### `listing-create.css`
- Very large selector contract for plugin wizard/listing form markup.
- Dominant roots include `.kmae-create-page`, `.kmae-wizard-choice`, `.kmae-wizard-stage`, `.kmae-wizard-preview`, `.kmae-wizard-main`, `.kmae-wizard-assist`, `.kmae-create-section`, `.kmae-listing-guest`, `.kmae-year`, `.kmae-commitment-option`, `.kmae-price-mode`, `.kmae-uploader`, `.kmae-shot`, `.kmae-catalog-*`, `.kmae-mileage`.
- Responsive breakpoints found: 1280, 1024, 1000, 860, 720, 640, 560, 520 and desktop min 1025.
- Contains multiple animation keyframes and reduced-motion handling.
- Theme provides styling only; wizard JS/PHP/data flow are outside this snapshot.

### `browse.css`
- Scoped heavily by `.kmae-browse-page`.
- Main external DOM families: wizard/map/county/mode cards, `.kmae-swipe-*`, result cards.
- Desktop-only feature rules begin at min 1025.

### `single-listing.css`
- Main external DOM: `.kmae-single-listing*`, `.kmae-listing-hero*`, `.kmae-gallery-*`, `.kmae-vehicle-*`, `.kmae-offer-section` (contact CTA box), `.kmae-verify-gate*`, `.kmae-pledge-*`.
- Markup not emitted by this theme snapshot.

### `my-listings.css`
- `.kmae-my-listings*`, `.kmae-my-listing-card*`, `.kmae-draft-radial*`, `.kmae-delete-confirm*`.
- Card (2026-10-03 rebuild): status colour via `--mlc` (`.kmae-my-listing-card--active|awaiting|closed|archived`), framed photo with shade + price/status/logo/count overlays, 2×2 spec tiles, views stat (active) + a second `__stats` row „Lejár N nap múlva” (plugin, 2026-10-10, no own CSS), expired card = `--archived` colour + `__notice` + „Hosszabbítás” primary button, primary + edit/delete buttons (delete always shown). Delete dialog: sad mascot peeking, listing mini-card + consequence list filled by `my-listings.js` from the form's `data-kmae-delete-*` (title, sub, thumb, status, kind=draft|listing).
- Comment explicitly names `my-listings.js`; JS is absent from theme.

### `offer-thread.css`
- Chat thread ONLY (enqueued when `kmae_is_messages_page()` + `?kmae_conversation`; body class `kmae-offer-thread-active`). Single source, prefix `body.kmae-offer-thread-active`, tokens `--ot-*`.
- Layout: one column (max 980px) chat shell: listing header + status, flash, scrolling `#kmae-chat-thread`, composer (emoji, file/image, 48 h hint), image lightbox. Offer/deal/rail/modal rules deleted 2026-10-10.
- FIX colour rule: buyer = blue, seller = orange (bubbles left/right by role, send button by viewer role `kmae-chat-shell--{role}`).
- Chat offer (2026-10-10): `.kmae-chat-offer` (vevő panel), `.kmae-chat-offer-bubble.is-{live|replaced|accepted|closed|withdrawn|sold|retracted}` (accepted és sold = zöld, retracted = szürke + áthúzott összeg), `.kmae-chat-offer-accept` ([Elfogadom], narancs), `.kmae-chat-offer-retract` (vevő [Visszavonom], halk pill jobbra), `.kmae-chat-deleted` (piros sáv „Az eladó törölte a hirdetést.”), `.kmae-chat-system` (középre igazított rendszerüzenet), `.kmae-chat-deal` (Foglalt + telefonszám + [Mégse kelt el] sáv).
- Foglalt sáv (5. kör, 2026-10-10): eladó `a.kmae-chat-deal__contract` (narancs pill → szerződés aloldal), vevő `.kmae-chat-tips` (info-manó + bélyegkép + „Mire figyelj?”, a jobb oszlop alján) → `.kmae-tips-viewer` teljes képernyős nagyító (`__bar`, `__btn`, `__stage`, `.is-zoomed`, `.is-dragging`; JS: plugin `public.js`). Elrendezés (2026-10-10): `.kmae-conversation-layout` alapból 1 oszlop, széles (`--ot-chat-h * 2`) középen; `.has-side` (elfogadott ajánlat) = 16:9 chat + `aside.kmae-chat-side` 340px (`.kmae-chat-deal`, `.kmae-chat-checklist`, flash/törölt értesítések); állapot + Bejelentés a fejlécben (`.kmae-chat-listing-header__side`), értesítés oszlop nélkül `.kmae-chat-notices`. Felső hézag: `.kmae-page` `padding-top: 18px` (belépve a fejléc a folyamban áll).
- Bejelentés (6. kör, 2026-10-10, fájl vége): `.kmae-report-entry` + `.kmae-report-open` (fehér pill a fejlécben, hover narancs) + `dialog.kmae-report` ablak (`__form` neon felső csík, `__reason:has(input:checked)` narancs, `__details`, `__submit` narancs, `__cancel` kék). Ugyanez a blokk `single-listing.css` végén is, saját prefixszel. Markup: plugin `templates/partials/report-form.php`; JS: plugin `public.js` `data-kmae-report-open`.

### `sale-contract.css`
- Adásvételi szerződés aloldal (plugin `templates/sale-contract.php`), body class `kmae-sale-contract-active`, prefix `body.kmae-sale-contract-active`, tokens `--sc-*`. Toolbar (glass + neon csík, Letöltés narancs, Nyomtatás kék) + fehér „papír” űrlap (`.kmae-contract__paper`, `__row`, `__input`, `__textarea`, `__signs`). `@media print`: csak a papír látszik (visibility trükk), placeholder átlátszó.

### `messages.css`
- Üzenetek list (`.kmae-inbox*`), body class `kmae-messages-page`; glass panel + neon line per design system; role pill buyer blue / seller orange; unread = orange border + dot. Törölt hirdetés sora: `.kmae-inbox__item.is-listing-gone` (halvány kép) + `.kmae-inbox__gone` piros „Törölt hirdetés” címke.

### `listing-unavailable.css`
- Lejárt hirdetés a látogatónak (plugin `templates/listing-unavailable.php`): `.kmae-listing-unavailable` (saját `--sl-*` tokenek, mert vendégnél nincs `single-listing.css`), `__panel` (üveg + neon csík), `__browse` (narancs gomb), `__grid` 4 oszlop, `__card*` hasonló autó kártyák.


### `profile.css`
- `.kmae-profile-*` families; desktop rules under min 1025; mobile wrap at max 1024.
- Page body class from theme; content markup plugin-owned.

### `settings.css`
- `.kmae-settings-*` families; desktop rules under min 1025.
- Content markup plugin-owned.

### `google-complete.css`
- Scoped with `.kmae-app.kmae-complete-page` and auth/form selectors.
- Explicitly suppresses common shell distractions at desktop.
- Content markup is register/auth shortcode output: plugin-owned.

### `how-it-works.css`
- `.kmae-how-*` plus `.kmae-video-lightbox*`.
- Theme JS knows lightbox hooks, but PHP markup is not in theme.

### `faq.css`
- `.kmae-faq*` families; content markup outside theme.

### `contact.css`
- `.kmae-contact-*` families; content markup outside theme.

## Responsive breakpoints by file

- `theme.css`: extensive 380/420/520/820/900/960/1024/1100/1600 plus orientation/height cases.
- `landing.css`: desktop min 1025; mobile/tablet default rules.
- `app.css`: 480/720/1025/1366 + reduced motion.
- `header.css`: <=1024, >=1025, 1025–1399.
- `listing-create.css`: 520/560/640/720/860/1000/1024/1280 + >=1025 + reduced motion.
- `home-cards.css`: <=1024, <=480.
- `my-listings.css`: <=1100.
- `single-listing.css`: <=1100 and <=640.
- profile/settings/browse/FAQ/contact/how-it-works/google-complete: major desktop block at >=1025.

## CSS change-risk rule

Before modifying a feature stylesheet, determine whether its target DOM is:
1. emitted by theme PHP (safe to inspect locally), or
2. emitted by plugin shortcode/template (load plugin feature map first).

The second case applies to most application feature CSS files.

## V2 CSS DIAGNOSTIC PROTOCOL

Use this order; stop as soon as the first failed checkpoint is found:

1. **ROUTE:** is the request the route the feature detector recognizes?
2. **BODY:** is the expected `kmae-*-page` / auth body class present?
3. **HANDLE:** is the expected stylesheet actually enqueued with the correct dependency/auth gate?
4. **DOM:** does the exact selector match current markup?
5. **MEDIA:** is the current viewport inside the intended `@media` branch?
6. **CASCADE:** does a later/more-specific rule override it?
7. **LAYOUT:** only after computed declaration is correct, inspect parent/grid/flex/overflow/position interactions.

### Negative routing

- Correct DOM class + correct computed data/text → **do not inspect backend** for a visual-only problem.
- Failure only at one breakpoint + same DOM → **do not load all CSS**; search the affected selector and its media variants.
- Missing selector match → **stop cascade debugging**; identify DOM producer.
- `landing.css` is not landing-only: it loads on every guest request through `kmae_assets()`.
- `single-listing.css` is currently enqueued only from logged-in `kmae_enqueue_app_shell()`; guest-only missing single-listing style can be explained before selector analysis.
