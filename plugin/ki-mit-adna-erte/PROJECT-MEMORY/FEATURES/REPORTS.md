# FEATURE — Bejelentések (report: hirdetés + chat → admin)

## PURPOSE
A felhasználó bejelent egy hirdetést (oldalpanel „Hirdetés bejelentése”) vagy a chat-partnerét (chat fejléc „Bejelentés”): okot választ (rádiógomb, utolsó a „Más”) + „Miért jelented be?” szöveg (a „Más”-nál kötelező). Az admin Albira → Jelentések oldalon nézi meg (piros számláló), megnézi a köztük lévő beszélgetést és az előzményeket, és dönt: Új / Igaz / Nem igaz + saját jegyzet. Automatikus szankció NINCS (kész 6. kör, 2026-10-10).

## READ FIRST
- `includes/class-kmae-reports.php` (`KMAE_Reports`)
- `templates/partials/report-form.php` (gomb + `<dialog>`)
- `admin/views/reports.php` (lista + részletek + chat + előzmények)

## THEN IF NEEDED
- `public/class-kmae-public.php` `process_report` (`kmae_action=submit_report`)
- `admin/class-kmae-admin.php` `add_menu` (buborék), `render_reports`, `handle_report_update`
- `assets/js/public.js` (`data-kmae-report-open` / `data-kmae-report-close`, „Más” → textarea `required`)
- theme `single-listing.css` / `offer-thread.css` vége (`.kmae-report*`), plugin `assets/css/admin.css` vége (`.kmae-reports*`)

## ENTRY POINTS
- Hirdetés: `single-listing.php` oldalpanel, a flash üzenetek alatt, csak `! $is_owner`; vendégnél login link. `$report_context = KMAE_Reports::CONTEXT_LISTING`.
- Chat: `offer-thread.php` fejléc, a státusz `<aside>` után (vevő és eladó is). `CONTEXT_CHAT`.
- Böngészés v2 (PC, 2026-10-18): alsó sáv „Jelentés” → saját `<dialog class="kmae-sw2-rep">` a `browse-swipe-v2.php`-ban (JS: `swipe-v2.js`), AJAX `kmae_report_listing` → `KMAE_Public::ajax_report_listing` → `submit()` `CONTEXT_LISTING`. Okok: `photo_reasons()` (képre: `photo_other_car/stock/offensive/personal`) + `reasons(listing)`. Képes oknál a `submit` a leírás végére írja: „Érintett kép: N. / össz. — URL” (`photo_note`, a böngésző-kártya képsorrendje), ezért nincs külön oszlop.
- POST `submit_report` (nonce `kmae_report_nonce`) → `KMAE_Public::process_report` → `KMAE_Reports::submit()` → redirect `kmae_success=report_sent` / `kmae_error=report_*` (üzenettérkép mindkét sablonban).
- Admin: `admin.php?page=kmae-reports` (`status=new|valid|invalid|all`, alap `new`); részletek `&report=ID` (`#kmae-report-chat` = beszélgetés). Döntés: admin-post `kmae_report_update` (nonce `kmae_report_update_ID`) → `KMAE_Reports::update_decision`.

## STATE / DATA
- Tábla `kmae_reports` (4.64.0): `context` listing/chat, `listing_id`, `conversation_id` (hirdetés-bejelentésnél a bejelentő meglévő chatje az eladóval, ha van; különben 0), `reporter_id`, `reported_user_id` (hirdetésnél a szerző, chatnél a másik fél), `listing_title` (pillanatkép, törlés után is), `reason` (kulcs, `KMAE_Reports::reasons($context)`), `details`, `status` new/valid/invalid, `admin_note`, `resolved_by`, `resolved_at`.
- Duplikátum-védelem: ugyanaz a bejelentő + context + listing + conversation, amíg `new` → `report_duplicate`.
- A beszélgetést az admin közvetlenül a `kmae_conversation_messages`-ből olvassa (`get_conversation_messages`, tagság-ellenőrzés nélkül); a chat hirdetéstörléskor sem törlődik.

## SIDE EFFECTS
Értesítés (nincs poll): `notify_admins()` → 1) e-mail minden `manage_options` user címére (egyedi címek, `KMAE_Brand::send_mail`, tárgy „Új bejelentés: OK”, törzs: ok, hol, hirdetés, bejelentő, bejelentett, leírás + EU-s figyelmeztetés, gomb „Megnézem az adminban” → `admin_url(ID)`; nem kikapcsolható, 8. kör); 2) `KMAE_Live_Signal::bump(manage_options userek, note)` → shutdown-kor Web Push (`KMAE_Push::send_to_user`, csak ha az admin bekapcsolta a pusht az oldalon) a `?page=kmae-reports&report=ID` URL-lel. Menü-számláló: `KMAE_Reports::count_new()` oldalbetöltéskor, WP `awaiting-mod` buborék az „Albira” és a „Jelentések” menüponton.

## CHANGE RISKS
Moderation/jogosultság zóna: a bejelentett fél nem kap értesítést és nem látja, ki jelentette. A `<dialog>` a top layerben nyílik (üveg/transform szülő nem vágja le); háttérre kattintás bezár (ehhez a dialógus padding 0, a belső `form` kapja a paddinget). Új okot csak a `reasons()` tömbbe vegyél fel (a `reason_label` mindkét listából keres).
