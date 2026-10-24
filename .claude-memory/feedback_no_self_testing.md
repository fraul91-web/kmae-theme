---
name: feedback-no-self-testing
description: Ne tesztelgess/szkrínsotolgass munka közben — csináld meg, Raul ellenőrzi; csak hiba esetén vizsgálódj
metadata:
  type: feedback
---

Raul nem kéri, hogy a munkát magamtól ellenőrizzem (headless böngészős renderelés, összehasonlító képek, variánsok gyártása). Csináljam meg a feladatot, csomagoljam be, ő megnézi. Csak akkor nyomozzak és teszteljek, ha ő jelez, hogy baj van.

**Why:** Feleslegesnek és lassúnak tartja a köztes ellenőrzési köröket; ő úgyis ránéz a végeredményre az éles oldalon.

**How to apply:** Kód/CSS elkészítése után verziót emelek, zipelek, és röviden jelentem, mi változott — nem rakok mellé renderelt képeket, nem gyártok A/B variánsokat felkérés nélkül. Kivétel: ha ő maga kér tesztoldalt vagy állítóeszközt. Kapcsolódik: [[feedback-scope-stop-rule]], [[feedback-simplest-fix-first]].
