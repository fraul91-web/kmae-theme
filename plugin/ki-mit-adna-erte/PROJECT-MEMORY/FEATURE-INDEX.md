# FEATURE INDEX

| Feature | File | Purpose |
|---|---|---|
| Bootstrapping, lifecycle and architecture | [`BOOT-ARCHITECTURE.md`](FEATURES/BOOT-ARCHITECTURE.md) | Plugin indulása, class betöltés, inicializálási sorrend, aktiválás/frissítés/deaktiválás, globális konstansok. |
| Pages, routing, shortcodes and templates | [`PAGES-ROUTING.md`](FEATURES/PAGES-ROUTING.md) | WordPress oldalak létrehozása, shortcode-router, query varok, template routing, listing archive/single routing. |
| Password auth, registration and email verification | [`AUTH.md`](FEATURES/AUTH.md) | Saját login/regisztráció/jelszó-visszaállítás, account type, e-mail megerősítés és jogosultsági gate. |
| Google OAuth login | [`GOOGLE-AUTH.md`](FEATURES/GOOGLE-AUTH.md) | Google authorization-code login, user resolution/linking, incomplete account completion gate. |
| Profile, company accounts and user settings | [`PROFILE-COMPANY-SETTINGS.md`](FEATURES/PROFILE-COMPANY-SETTINGS.md) | Profiladatok, magán/cég fiók, céges telephely, e-mail preferenciák, e-mail/jelszó/security műveletek. |
| Listing creation, editing and lifecycle | [`LISTINGS.md`](FEATURES/LISTINGS.md) | Hirdetés létrehozás/szerkesztés/törlés/reaktiválás, listing meta, állapotok és tulajdonosi jogosultság. |
| Listing wizard and autosaved drafts | [`LISTING-WIZARD-DRAFTS.md`](FEATURES/LISTING-WIZARD-DRAFTS.md) | Többlépéses hirdetés-wizard frontend állapot, validáció, előnézet, autosave draft és folytatás. |
| Vehicle-specific data and catalog | [`VEHICLE.md`](FEATURES/VEHICLE.md) | Autó-specifikus mezők, lokális márka/modell katalógus, címképzés, browse módok és meta mentés. |
| Photo motor and image processing | [`PHOTO-MOTOR.md`](FEATURES/PHOTO-MOTOR.md) | Kötelező/ajánlott fotóslotok, kliens+szerver minőségellenőrzés, automatikus képjavítás, galéria és WebP feldolgozás. |
| Automated listing moderation | [`MODERATION.md`](FEATURES/MODERATION.md) | Kódalapú publish-gate: kötelező mezők, minimum leírás, fotók, kontaktadat tiltás és hiba-visszaadás. |
| Browse, search wizard and personalization | [`BROWSE-SEARCH.md`](FEATURES/BROWSE-SEARCH.md) | Hirdetéslekérdezés, megye/kivitel/mód/ár szűrés, pass kezelés és resurfacing. |
| Chat (conversation, messages, attachments) | [`CHAT.md`](FEATURES/CHAT.md) | Hirdetésből indított vevő–eladó chat: szöveg, emoji, 48 órás csatolmány, Üzenetek lista, olvasatlan badge. Az ajánlat/deal/értékelés rendszer törölve (2026-10-10); új chat-ajánlat: ajánlat → elfogadás (Foglalt) → Mégse kelt el (`KMAE_Chat_Offers`). |
| Csoportos kereskedői megkeresés + Értesítések oldal | [`DEALER-LEADS.md`](FEATURES/DEALER-LEADS.md) | Eladó sugáron belüli kereskedőknek küld megkeresést (ár + cél), cég „Érdekel” → chat / „Köszönöm, nem”, 7 napos lejárat, kapcsoló `kmae_dealer_leads_enabled`. |
| Bejelentések | [`REPORTS.md`](FEATURES/REPORTS.md) | Hirdetés és chat bejelentése okkal + indoklással; admin Albira → Jelentések (piros számláló, Web Push), beszélgetés és előzmények megtekintése, döntés Igaz / Nem igaz. |
| Event notifications and email | [`NOTIFICATIONS.md`](FEATURES/NOTIFICATIONS.md) | Chatüzenet-, hirdetés- és biztonsági e-mailek, mail catalog/prefs és brandelt HTML mail. |
| Dashboard, hubs and statistics | [`DASHBOARD-HUBS.md`](FEATURES/DASHBOARD-HUBS.md) | Dashboard/hub aggregáció, listing view tracking, listing activity rows és kezdőoldali user summary. |
| Live polling, navigation and badges | [`LIVE-NAV.md`](FEATURES/LIVE-NAV.md) | Oldal fingerprint polling, olvasatlan chat count, navigation filterezés és badge frissítési frontend. |
| WordPress admin integration | [`ADMIN.md`](FEATURES/ADMIN.md) | Admin menü, listing oszlopok/bulk action, admin listing műveletek, beállítások és meta box. |
| Public contact form | [`CONTACT-FORM.md`](FEATURES/CONTACT-FORM.md) | Publikus kapcsolatfelvételi űrlap spam-gate, validáció és e-mail küldés. |
| Archive lifecycle and scheduled purge | [`CRON-ARCHIVE.md`](FEATURES/CRON-ARCHIVE.md) | Eladott/archivált hirdetések 5 napos lock + 2 napos reaktiválási ablak és napi törlési cron; óránkénti 7/60 napos cron |
| Post type, taxonomy and launch mode | [`TAXONOMY-LAUNCH.md`](FEATURES/TAXONOMY-LAUNCH.md) | kmae_listing CPT, kmae_category taxonomy, seed kategóriák és single-category jármű launch korlátozás. |
