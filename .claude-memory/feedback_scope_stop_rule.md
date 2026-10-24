---
name: feedback-scope-stop-rule
description: Must stop and ask before touching anything outside the explicit request or changing existing working behavior
metadata: 
  node_type: memory
  type: feedback
  originSessionId: ac48ec3f-ca9c-47ee-bb77-4ee0f8658fec
  modified: 2026-09-03T15:49:15.044Z
---

Before making a code change that (a) touches a file/feature Raul did not explicitly ask about, or (b) would alter existing behavior/appearance that currently works, stop before making that specific change and explain in plain language: what the obstacle/situation is, what the options are, and what each option's consequence is — then let Raul decide. Do not silently modify or delete things outside the request "just to reach the goal."

**Why:** Prior AI tools Raul used did not act as partners — they silently reached into unrelated code/CSS to hit a goal, even though touching other things was implicitly or explicitly off-limits. This eroded trust and made the codebase (this WordPress project, [[project_kmae_overview]]) harder to maintain, per the layered-CSS-override problem already documented in the repo's own AGENTS.md.

**How to apply:** Don't stop for trivial, obviously-in-scope work (e.g., a couple of clearly-implied lines in the same file the request is about) — only pause for real scope creep, cross-file/cross-feature side effects, or anything that would make previously-working behavior different than before. This rule is written into the project's own `AGENTS.md` (section "Megállási szabály — scope-védelem"), which is loaded automatically each session in this repo, so it should already be in effect without needing to be restated — but keep honoring it even if that file is ever missing or summarized away.
