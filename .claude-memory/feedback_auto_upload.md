---
name: auto-upload-albira
description: Raul 2026-10-21: minden kész módosítást magadtól feltölteni az élő albira.hu-ra SSH-n; jelentés "ZIP: Uploaded/Unuploaded"
metadata:
  type: feedback
---

Minden elkészült módosítást kérés nélkül töltsd fel az élő albira.hu-ra (`ssh albira-server`), a duogo.hu-hoz soha ne nyúlj. A jelentés első sora: `ZIP: Uploaded` vagy `ZIP: Unuploaded`.

**Why:** Raul nem akar kézzel ZIP-et feltöltögetni; „így dolgozunk már”.
**How to apply:** részletek a CLAUDE.md 10. pontjában (cél mappák, tiltott `--delete`, ellenőrzés). Lásd [[feedback_minimal_report]].
