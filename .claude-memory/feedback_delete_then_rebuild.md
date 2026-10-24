---
name: feedback-delete-then-rebuild
description: "Átdolgozásnál a régi kódot/CSS-t TÖRÖLNI kell, nem felülírni rá újabb réteggel — tiszta lap, utána jön az új"
metadata:
  node_type: memory
  type: feedback
  originSessionId: 8273c5f5-7b9f-4093-b6cb-95a16d41664c
  modified: 2026-10-02T17:00:29.909Z
---

Ha Raul átdolgozást (UI boost, újratervezés) kér, a korábbi, lecserélt kódrészleteket és CSS-szabályokat törölni kell, nem újabb felülíró réteget tenni rájuk. Elavult/halott kódot is takarítani kell. „Így szeretném mindig… így friss üres lap lesz.” (2026-10-02)

**Why:** a my-offers.css és társai egymást felülíró rétegektől lettek kaotikusak (pl. a kérdőív 3 ellentmondó stílusrétege okozta a szétcsúszást).
**How to apply:** előbb leltár (mely szabályok/sablonok tartoznak a részhez), törlés, majd egyetlen új, saját blokk/fájl. A fix színszabály: vevő mindig kék, eladó mindig narancs. Lásd [[feedback-execute-exactly]] — a törlés is csak a kért részre vonatkozik.
