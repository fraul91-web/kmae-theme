---
name: project_legacy_white_hint
description: A plugin public.css globálisan fehérre festi a .kmae-hint-et !important-tal — világos oldalon saját osztálynév kell helyette
metadata: 
  node_type: memory
  type: project
  originSessionId: 9c1285e0-772e-4b9b-9a92-04b0740247ee
  modified: 2026-09-16T11:07:33.550Z
---

A `plugin/ki-mit-adna-erte/assets/css/public.css` kb. 103. sorában:
`.kmae-hint, .kmae-wizard-panel-hint, .kmae-wizard-mode-desc, .kmae-map-hint { color: #ffffff !important; }`

Ez a régi sötét felület öröksége. Minden világos, üveges oldalon (profil, GYIK,
kapcsolat, hirdetés adatlap) olvashatatlanná teszi a segédszövegeket.

**Miért:** emiatt születtek a korábbi oldal-CSS-ekben az `!important`-áradatok —
a szabályt csak `!important`-tal lehet legyőzni, ami pont az a rétegződés, amit
[[feedback_scope_stop_rule]] és az AGENTS.md tilt.

**How to apply:** világos oldalon NE használd a `.kmae-hint` / `.kmae-subtitle`
osztályt. Ha a sablon a miénk, adj neki saját nevet (pl. `.kmae-profile-note`,
`.kmae-profile-lead`) — így a CSS `!important` nélkül tiszta marad. A globális
`!important` eltávolítása kockázatos: sok sötét felület támaszkodik rá, csak
külön kérésre nyúlj hozzá. Ugyanez a minta a `.kmae-badge` és `.kmae-btn`
osztályokkal is: a plugin sötétre stilizálja őket.

Így készült a profil oldal 2026-09-16-án: teljesen saját `kmae-profile-*`
osztálykészlet, nulla `!important`. Lásd [[project_design_system.md]].
