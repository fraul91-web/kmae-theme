---
name: feedback-simplest-fix-first
description: "For visual/CSS tweaks, try the smallest single-value change first — do not restructure markup or layering before that is proven insufficient"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: 8592d376-1e5d-4afe-b5e9-6a739ff37e2b
  modified: 2026-09-12T13:47:40.467Z
---

When Raul reports a visual problem (size, spacing, overlap, glow, background), start with the **smallest possible change** — usually one CSS value — and show him the result. Only move to restructuring (new wrapper elements, moving a background to a different element, pseudo-element layering, changing stacking contexts) if the simple change has actually been tried and proven insufficient.

**Why:** During the header background work Raul said plainly: *"egyszerű megoldások is tökéletesek..nem kell túlbonyolítani mindent."* Before that, three successive restructures of the header (background moved between `.kmae-header`, `.kmae-header__inner`, and a `::after` overlay; natural aspect vs. squashed sizing; a proposed move into the body background) each made things worse and one of them hid page content behind the overlay. The actual fix that satisfied him was changing a single `height` value to a fixed, narrow number. He also explicitly interrupted a larger restructure mid-edit with *"nem össze vissza állítgatni mindent."*

**How to apply:** Applies to this project's frontend work generally, not just the header. In practice: change one number or one property, rebuild the zip, let him look. If he gives a concrete instruction with a concrete value ("fix magasságot, kb. 8 mm"), implement exactly that and nothing else — do not also "improve" the surrounding layering while there. Restructures are not forbidden, but they need to be a deliberate, explained step after the simple route failed, and they interact with [[feedback-scope-stop-rule]].
