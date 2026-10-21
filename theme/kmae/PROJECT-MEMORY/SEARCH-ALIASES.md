# SEARCH ALIASES — THEME V2

**MAP-TYPE:** ON-DEMAND  
Use the smallest alias set that matches the user's wording. Search exact technical hooks before broad conceptual terms.

| USER / UI WORDING | TECHNICAL ALIASES / SEARCH TERMS | FIRST ROUTE |
|---|---|---|
| “hamburger”, “mobil menü” | `#kmae-drawer-open`, `#kmae-drawer`, `#kmae-drawer-backdrop`, `setDrawerOpen`, `body.kmae-drawer-open` | R04 |
| “profil lenyíló”, “avatar menü” | `data-kmae-dropdown`, `.kmae-header__panel`, `openDropdown`, `closeDropdown` | R05 |
| “lábléc nyitó”, “footer lenyíló” | `#kmae-footer-toggle`, `#kmae-footer-panel`, `setFooterOpen` | R06 |
| “alsó menü”, “bottom nav aktív” | `.kmae-bottom-nav__item`, `itemPath === path` | R07 |
| “felső menü rosszat jelöl” | `$kmae_nav_active`, `$kmae_own_listing`, `.kmae-header__nav-item` | R08 |
| “mobil menü rosszat jelöl” | `kmae_app_menu_groups`, `$is`, `.kmae-sheet__item`, `is-active` | R09 |
| “Üzenetek mellett kis számláló”, “piros/kék badge” | `.kmae-pc-badge`, `data-kmae-unseen`, `$kmae_unseen`, `.kmae-sheet__badge`, `count_unread_conversations` | R10 |
| “értesítési pötty” | `.kmae-phone-header__dot`, `$kmae_unseen` | R10 |
| “hirdetéseim számláló” | `count_active_cycle_listings`, `$listings`, `.kmae-sheet__badge` | R09/B04 |
| “dashboard”, “főoldal belépve” | `front-page.php`, `app-home.php`, `KMAE_Dashboard`, `dashboard-home.php` | R11 |
| “frissítsd a bővítményt” | `KMAE_Dashboard` class gate, `.kmae-app-home__lead` | R11 |
| “landing”, “kezdőlap kijelentkezve” | `front-page.php` (vendégnek = böngészés, app-héj: `kmae_is_guest_app_view()`) | R12 |
| “rossz logó” | `kmae_logo_url`, `kmae_logo_img`, `kmae_media_url`, `KMAE_Brand`, `logo_guest` | R24 |
| “rossz hero/kép” | exact media key + `kmae_media_url` | R12/R24 |
| “böngészés”, “keresés”, `/hirdetesek/` | `kmae_is_browse_page`, `kmae_browse`, `kmae-browse-page`, `browse.css` | R13 |
| “hirdetésfeladás”, “wizard kinézet” | `kmae_is_listing_create_page`, `kmae_create_listing`, `kmae-listing-create-page`, `.kmae-create-page`, `.kmae-wizard-*`, `listing-create.css` | R14 |
| “Tovább gomb nem megy”, “wizard validáció” | `kmae_create_listing`, `$is_listing_create`; **theme has no matching behavior engine** | R15 |
| “ajánlat buborék”, “Elfogadom gomb”, “Foglalt felirat”, “Mégse kelt el”, “telefonszám sáv” | `.kmae-chat-offer-bubble`, `.kmae-chat-offer-accept`, `.kmae-chat-deal`, `.kmae-chat-system` (offer-thread.css), `.kmae-single-sale-state` (single-listing.css) | R16 |
| “Visszavonom gomb”, “visszavont ajánlat” | `.kmae-chat-offer-retract`, `.kmae-chat-offer-bubble.is-retracted` (offer-thread.css); logika: plugin `KMAE_Chat_Offers::retract` | R16 |
| “az eladó törölte a hirdetést sáv”, “törölt hirdetés címke az üzenetekben” | `.kmae-chat-deleted` (offer-thread.css), `.kmae-inbox__gone`, `.is-listing-gone` (messages.css); logika: plugin `KMAE_Chat_Offers::on_listing_deleted` | R16 |
| “ez az autó már nem elérhető”, “lejárt hirdetés oldala”, “hasonló autók” | `listing-unavailable.css`, `kmae_enqueue_listing_unavailable`, `.kmae-listing-unavailable*`; markup plugin `templates/listing-unavailable.php` | R23 |
| “N eladott autó”, “eladó számlálója”, “Eladva felirat” | `.kmae-listing-seller__sold` (single-listing.css, profilnév mellett), `.kmae-single-sale-state.is-sold`, `.kmae-chat-offer-bubble.is-sold` (offer-thread.css); logika: plugin `KMAE_Chat_Offers::count_sold_for_seller` | R16 |
| “adásvételi szerződés oldal”, “szerződés nyomtatás”, “Letöltés Word” | `sale-contract.css`, `kmae-sale-contract-active`, `.kmae-contract__*`, `kmae_contract` (functions.php enqueue), `a.kmae-chat-deal__contract` (offer-thread.css) | R16 |
| “Mire figyelj kép”, “nagyító”, “ránagyítás”, “jobb oszlop”, “ellenőrzőlista” | `.kmae-chat-tips`, `.kmae-chat-side`, `.kmae-chat-checklist`, `.kmae-tips-viewer` (offer-thread.css), JS plugin `public.js` `data-kmae-tips-image` | R16 |
| “bejelentés gomb”, “jelentés ablak”, “csaló bejelentése” | `.kmae-report-open`, `dialog.kmae-report`, `.kmae-report__reason` (offer-thread.css + single-listing.css vége); markup plugin `partials/report-form.php`, logika `KMAE_Reports` | R16 |
| “üzenetek oldal”, “chat kinézet” | `kmae_is_messages_page`, `kmae_messages`, `kmae-messages-page`, `messages.css`, `offer-thread.css`, `kmae_conversation` | R16 |
| “profil” | `kmae_is_profile_page`, `kmae_profile`, `profil`, `profile.css`, `$is_raw_shortcode` | R17 |
| “beállítások” | `kmae_is_settings_page`, `kmae_settings`, `beallitasok`, `settings.css`, `$is_raw_shortcode` | R17 |
| “belépés/regisztráció tördelése” | `kmae_login`, `kmae_register`, `$is_raw_shortcode`, `do_shortcode` | R03 |
| “Google belépés utáni befejezés” | `kmae_complete`, `KMAE_Google_Auth`, `kmae-complete-page`, `google-complete.css`, `kmae-public` | R20 |
| “hogyan működik videó” | `data-kmae-video-id`, `#kmae-video-lightbox`, `#kmae-video-iframe`, `openVideo`, `closeVideo` | R21 |
| “sheet tab” | `data-kmae-sheet`, `data-sheet-tab`, `data-sheet-panel`, `initSheetTabs` | R22 |
| “egy hirdetés adatlap” | `is_singular('kmae_listing')`, `kmae-single-listing-page`, `single-listing.css` | R23 |
| “mobilon rossz, PC-n jó” | exact affected class + `@media`, body feature class | R18 |
| “PC-n rossz, mobilon jó” | exact affected class + `@media`, body feature class | R18 |
| “CSS nem érvényesül” | exact selector + body scope + style handle + dependency | R19 |
| “minden JS meghalt” | handle `kmae-theme`, `/assets/js/theme.js`, `kmae_assets`, `wp_footer` | R25 |
| “rossz URL / menüpont rossz helyre visz” | `kmae_page_url`, `KMAE_Pages::url`, exact logical slug | B01 |
| “saját hirdetésnél Browse aktív” | `$kmae_own_listing`, `post_author`, `sajat-hirdetesek`, `hirdetesek` | R08 |
| “oldal túl sok wrapperrel” | `page.php`, `$is_raw_shortcode`, `$page_mod`, `the_content`, `do_shortcode` | R03 |

## Route / shortcode aliases

| Logical feature | Route/slug | Shortcode |
|---|---|---|
| listing-create | `hirdetes-feladasa` | `kmae_create_listing` |
| browse | `hirdetesek` / `kmae_listing` archive | `kmae_browse` |
| my listings | `sajat-hirdetesek` | `kmae_my_listings` |
| FAQ | `gyik` | `kmae_faq` |
| contact | `kapcsolat` | `kmae_contact` |
| profile | `profil` | `kmae_profile` |
| settings | `beallitasok` | `kmae_settings` |
| registration | `regisztracio` | `kmae_register` |
| login | `belepes` | `kmae_login` |
| sell hub | `eladni` | `kmae_hub_sell` |
| buy hub | `venni` | `kmae_hub_buy` |
| stats hub | `statisztikak` | `kmae_hub_stats` |
