---
name: project-performance-work
description: "Albira gyorsítás/terhelés állapota (2026-10-02): poll → statikus jelzőfájl + Web Push, Cloudflare aktiválás folyamatban, RackForest belépő csomag"
metadata:
  node_type: memory
  type: project
  originSessionId: 8273c5f5-7b9f-4093-b6cb-95a16d41664c
  modified: 2026-10-08T15:38:11.447Z
---

2026-10-02-én lezárt gyorsítási kör (kód kész, zipelve):
- Élő frissítés: `KMAE_Live_Signal` statikus `uploads/kmae-live/<key>.v` jelzőfájl + `KMAE_Push` (Web Push, VAPID, saját titkosítás). Részletek a plugin PROJECT-MEMORY `FEATURES/LIVE-NAV.md`-ben.
- Fájlverzió filemtime-ból (téma + plugin `*_loader_src` szűrő), halott képhivatkozások törölve, app háttér CSS-ből.
- Cloudflare **AKTÍV** (2026-10-02, NS adrian/ivan.ns.cloudflare.com): brotli él, `/wp-content/` statikus fájlok edge+böngésző cache 31 nap (HIT), HTML DYNAMIC; mail/ftp/cpanel A DNS only, MX → mail.albira.hu. A `.htaccess` gyorsítás blokk a tárhelyen nem hat (modul nincs) — a Cloudflare váltja ki.
- Tárhely: RackForest „Webhosting SSD Fifteen” (belépő osztott csomag, elsődleges domain duogo.hu). Becsült plafon ~2–4 ezer regisztrált / ~200 egyidejű aktív felhasználó.

- 2026-10-14: Raul TÖRÖLTE a LiteSpeed Cache bővítményt (élőn ellenőrizve: nincs `x-litespeed-cache` fejléc). Mostantól nélküle dolgozunk; ne javasold vissza, csak ha a forgalom tényleg indokolja, és akkor is csak vendég-oldalakra.
- (RÉGI, 2026-10-08) A szerveren LiteSpeed oldal-cache is fut (`x-litespeed-cache: hit`, 2026-10-08): a cache-elt HTML a régi `?ver=` CSS/JS URL-t adja, ezért zip-feltöltés után a változás csak részben/nem látszik. Élő ellenőrzés: `curl -s 'https://albira.hu/<oldal>/?x=1'` (cache-kerülő) vs. sima URL `ver` értéke, és a CSS URL `curl`-lel.

**Why:** Raul lassúságot jelzett belépéskor; több ezer felhasználóra készülünk.
**How to apply:** Azonos nevű kép cseréje után Cloudflare Purge kell. Ha a forgalom nő: vendég-oldal cache → Redis → nagyobb csomag. Lásd [[feedback-execute-exactly]].
