---
name: adatlap-jellemzo-szinek
description: Hirdetés-adatlap „Jellemzők” pirulák — EGYSÉGES szín (#0b3a66, mint a hirdetés címe), nincs kódonkénti szín
metadata:
  type: feedback
---

A hirdetés-adatlap „Jellemzők” pirulái mind ugyanazt a színt kapják: `#0b3a66` (a hirdetés címének színe). Nincs kódonkénti szín, nincs `--feat` változó.

**Why:** Raul 2026-10-11-én kérte; a korábbi, 14 kódonkénti színes verzió (sárga/fekete/fehér kizárva, telt színek) helyett egységes szín kell.

**How to apply:** a szín a `theme/kmae/assets/css/single-listing.css` ~1568, `.kmae-single-listing.kmae-listing-sheet .kmae-listing-feature` szabályában van. Új jellemző/vállalás a pirulát automatikusan ugyanabban a színben kapja; ne vezess be kódonkénti színeket. Kapcsolódó: [[project_design_system]].
