---
name: project-phone-lapozo
description: "Telefonos böngészés „Lapozó” mód — Raul döntései (2026-10-24), tervezési fázis, élő kódhoz még nem nyúltunk"
metadata:
  node_type: memory
  type: project
  originSessionId: 26e032cd-08f8-4c66-986c-ded84d83227e
  modified: 2026-10-24T13:17:32.468Z
---

Telefonon csak ÁLLÓ nézetet gyártunk (a fekvőt a `browse-rotate.php` szándékosan tiltja, nem kell vele foglalkozni).
Első feladat: böngészés telefonon. Raul döntései 2026-10-24:
- Külön, teljes képernyős álló „Lapozó” mód (fejléc/menüsor/szűrő/nézetváltó nélkül, bal felül ✕ ki, ugyanoda vissza).
- Belépés: „▶ Lapozás” gomb a böngészés oldalon + autóra koppintva annál az autónál nyílik.
- Mozdulat: TikTok-szerű — fel/le húzás = következő/előző autó, balra/jobbra = az autó fotói, dupla koppintás = kedvenc.
- Adatlap: felcsúszó lap (ⓘ gombra; felhúzás foglalt), lehúzva bezár — a meglévő `swipe-v2.js` `openSheet()` alapja újrahasznosítható.
- Fotó: teljes, vágás nélkül, nagyban (nincs cover-crop).
- Szín: sötét fotónéző (mélykék, fehér betű, narancs ár, kék/narancs díszítés).
- Gombok: ♥ Kedvenc · ⓘ Részletek · 💬 Üzenet (nincs „Nem érdekel”).
- Adatok a fotó alatt: kevés, nagy betű — név, ár, év·km·üzemanyag·váltó, város.
Keresés, lista/kártya váltó csak a Lapozón kívül. 2026-10-24: ELKÉSZÜLT és élőn van (plugin `browse-lapozo.js`, theme `browse-lapozo.css`, theme 3.25.658); Raul a mintát nem kérte, utólag foltozunk.

**Why:** a mostani telefonos böngészés zsúfolt, „nem tinderes”, csúnya; Raul kép-fókuszú, appszerű élményt akar.
**How to apply:** a Lapozó munkánál ezekből indulj, ne kérdezd újra; PC-hez nem nyúlunk ([[feedback-pc-only]]).
