---
name: pc-only
description: "Alapnézet TELEFON (2026-10-14-től); PC-hez soha nem nyúlni, kivéve ha Raul 'pc-re…'-vel jelöli"
metadata:
  node_type: memory
  type: feedback
  modified: 2026-10-14T16:27:44.031Z
  originSessionId: 3507e49c-fc39-44bb-ba52-9e2388e67906
---

Mostantól telefonra fejlesztünk. Ha a kérés nem mondja, hogy PC, minden telefonos nézetre vonatkozik.

**PC-hez soha ne nyúlj** (asztali CSS/elrendezés nem változik), csak ha Raul kifejezetten „pc-re…” kezdettel kéri.

**Why:** Raul 2026-10-14-én ezt a CLAUDE.md 0. pontjába rögzítette. Ez felülírja a korábbi „csak PC” szabályt és a mobil-kivételeket.
**How to apply:** Telefonos módosítás külön `*-mobile.css` fájlba vagy `max-width` media query alá kerül, oldal-specifikus szelektorral. Részletek: CLAUDE.md 0. pont.
