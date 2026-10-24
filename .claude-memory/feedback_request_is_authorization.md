---
name: feedback-request-is-authorization
description: "Raul's request IS the authorization — never ask him to confirm or approve a step he already asked for"
metadata: 
  node_type: memory
  type: feedback
  originSessionId: c12d9e36-c65d-4904-b805-416d70a90b9e
  modified: 2026-09-16T09:09:22.106Z
---

When Raul asks for something, treat it as full authorization to carry it out end to end. Do not stop midway to have him confirm, approve, or accept anything. His words: "ne kérdezzen rá ezekre az akármikre amiket el kell fogadnom..csak csináld amit kértem... ha megkérlek rá..gondolom felhatalmazlak is egyben".

**Why:** Raul is a non-programmer ([[user_profile]]) and the approval dialogs are meaningless noise to him — he cannot judge a shell command, and being asked repeatedly makes the work feel slow and stalled. He wants finished, zipped output, not a running conversation about permissions.

**How to apply:** Keep the project's Bash allowlist covering the routine loop (zip/rm of the build zips, php -l, node --check, sed/grep/cat) so the harness stops prompting; add to it when a new routine command starts prompting. This does NOT override [[feedback_ask_when_ambiguous]] — a genuinely unclear *request* still gets one short question up front, before any edit. The difference: ask about **what to build**, never about **permission to build it**. Also still name destructive or outward-facing actions he did not ask for, per [[feedback_scope_stop_rule]].
