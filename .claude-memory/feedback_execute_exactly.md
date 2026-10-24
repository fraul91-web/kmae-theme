---
name: feedback_execute_exactly
description: "Csak azt hajtsd végre, amit Raul kért; ha bármi más átrendeződne, előbb állj meg és kérdezz"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 9c1285e0-772e-4b9b-9a92-04b0740247ee
  modified: 2026-09-17T05:46:22.408Z
---

Raul szabálya, szó szerint: „mindig ezt csináld és csak azt, amit mondok vagy
kérek. Ha valami más emiatt átrendeződik, megkérdezed, megállsz, átgondolod és
megkérdezed. Ha semmi nem rendeződik vagy semmi mást nem érint, végrehajtod
csak azt, amit kérek."

**2026-09-17: „Első számú szabály" — tilos a dominóeffektus.** Raul szó szerint:
„arra kérlek figyelj a dominó effektusra. ne az legyen hogy megírunk vagy
átírunk itt valamit egy oldalon, és máshol valami elszaródik. direkt
specifikusan csináljunk mindent, tartalomról tartalomra, oldalról oldalra,
hogy csak azt módosítjuk, ne univerzálisan, mert akkor a saját farkunk után
futunk." Előzmény: egy hosszú munkameneten át közös fájlokba (landing.css,
theme.css-szerű megosztott stíluslapok) írtam bele oldalanként sok apró
módosítást — színcserét, gomb-átszínezést, fejléc-hézagot —, és a felhasználó
szerint emiatt „szétbarmolódott" az egész oldal: dolgok, amiket korábban külön
beállítottunk, összekuszálódtak.

**How to apply (CSS/HTML munkánál):** mielőtt bármihez hozzányúlok, nézzem
meg, az adott osztály/szelektor **kizárólag** az érintett oldalhoz tartozik-e
(pl. `grep` az egész repóban a class névre). Ha egy komponens/szín/szabály
több oldalon is megjelenik egy közös fájlban (landing.css a legveszélyesebb:
hero, roles, paths, value, howto, trust, final CTA, auth login/register mind
ugyanabban a fájlban élnek), akkor a módosítást **szűkebb, oldal-specifikus
szelektorral** kell megírni (pl. `body.kmae-browse-page .kmae-page-wrap`, nem
a megosztott `.kmae-page` vagy egy scope nélküli class), még ha ez explicit
duplikációt jelent is más oldalak hasonló szabályaihoz képest. Sosem szabad
egy közös/megosztott fájlban lévő szabályt „csak azért mert ott van" globálisan
átírni — az minden oldalra kihat, amit az a fájl kiszolgál, nem csak arra,
amit éppen kértek.

**Méretre vonatkozó kérésnél:** ha a keretről beszél és nem tér ki a tartalomra,
akkor a keretet kell méretezni — a tartalomhoz hozzá se nyúlj (ne rendezd át, ne
tedd más sorba, ne rövidítsd a szövegeket). Ha azt mondja „pont akkora", akkor a
keret 100%-ban egyezzen, nem hasonló.

**Why:** 2026-09-16-án a beállítások és a profil oldalon magasság-egyeztetést
kért. Fejből becsültem px-értékeket a CSS-ből (nem tudom megmérni, nincs
böngészőm az élő, bejelentkezést igénylő oldalhoz), és közben átrendeztem a
tartalmat is — kapcsolókat két oszlopba, jelszómezőket egy sorba, profilon
név+telefon egy sorba. Az eredmény rosszabb lett, mint előtte, és mindent vissza
kellett állítani. Két hiba egyszerre: vak becslés és kéretlen hatókör-bővítés.

**How to apply:** ha nem tudom megmérni, ne számoljak fejből — kérdezzem meg a
pontos px-értéket, vagy kérjek képernyőképet. Az élő albira.hu belépést igényel,
oda nincs hozzáférésem, ezt mondjam meg ahelyett, hogy „megnézem". Kapcsolódó:
[[feedback_scope_stop_rule]], [[feedback_ask_when_ambiguous]],
[[feedback_simplest_fix_first]].
