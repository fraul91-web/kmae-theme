---
name: project_full_audit
description: "Albira teljes átvizsgálás A–F területen — lezárva 2026-09-25, mit találtunk és mi maradt nyitva"
metadata: 
  node_type: memory
  type: project
  originSessionId: dd8501f4-ebd6-4dc6-9059-bd07fe85162b
  modified: 2026-09-25T14:14:40.654Z
---

2026-09-25: az Albira projekt teljes „mi történik, ha…" átvizsgálása **mind a hat területen lezárva** (A fiók, B hirdetésfeladás, C böngészés, D+E alku és lezárás, F rendszer). Kb. 35 hiba javítva, területenként külön körben.

**A legfontosabb visszatérő hibaminta:** lejárt munkamenetnél a kezelők némán `return`-öltek — se üzenet, se művelet. Összesen ~20 helyen fordult elő. A javítás: területenként egy közös `require_*_nonce()` segéd, ami átirányít érthető üzenettel.

**A legsúlyosabb egyedi lelet:** a megállapodás utáni elérhetőség-átadó levél (a másik fél telefonszáma és e-mail címe) a `send_offer_email()`-en ment át, ami visszafordult, ha a felhasználó nem kérte az opcionális „ajánlat-frissítések" értesítőt — és a felületen ez az adat **sehol** nem jelenik meg. Javítva: az az egy levél kivételt kapott (`$ignore_preference`).

A gépen **nincs helyi WordPress** (nincs Docker/MySQL/webszerver — csak PHP 8.3 és Node), ezért böngészős automata teszt nem futtatható. Az ellenőrzés kódolvasás + `plugin/ki-mit-adna-erte/tests/*.php` (5 fájl, ~154 szerkezeti ellenőrzés, mind zöld) + Raul kézi tesztje területenként kapott lista alapján.

**Nyitva hagyott, üzleti döntést igénylő dolgok:** megerősítetlen fiókok automatikus törlése; hirdetésfeladás gyakorisági korlátja; a „magánszemélynek csak egy hirdetés" korlát (üzenet megvan, korlát kikapcsolva); chat gyakorisági korlát; a kapcsolati szűrő kijátszható a „30 111 11 11" alakkal; szerkesztő űrlap visszatöltése hiba után; fotók megőrzése elutasításkor (feltöltés átépítése kellene).

**Why:** Raul nem programozó, nem tudja egyben letesztelni a mély projektet; a területenkénti haladás tartotta kezelhetően a tokenköltséget is.

**How to apply:** új hibánál először a fenti nyitott listát és a visszatérő mintát nézd. Kapcsolódó: [[feedback_execute_exactly]], [[feedback_no_self_testing]].
