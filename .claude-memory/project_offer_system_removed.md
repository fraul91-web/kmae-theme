---
name: project-offer-system-removed
description: "2026-10-10 — the whole offer/deal/rating system was deleted on Raul's request; only chat remains; which related parts he chose to KEEP"
metadata:
  node_type: memory
  type: project
  originSessionId: 777bc244-1385-4a23-a70a-7ca8c468abc0
  modified: 2026-10-10T08:16:46.694Z
---

2026-10-10: Raul had the entire "ajánlatok" system deleted (official/cash/counter offers, accept/reject/withdraw, deals, closure, 7/8-day reminders, vehicle feedback, contact unlock, star ratings, Albira history stats, offer emails, hub cards). DB tables dropped and all test conversations wiped (plugin 4.60.0, `KMAE_Activator::remove_offer_system`).

**Kept on purpose (his answers):** price modes Fix ár / Irányár / Ki mit adna érte AND the hidden minimum field (now does nothing); "Áll az alku" (closed) listings stay closed; chat-message, listing, security emails and Web Push stay; Értesítések menu item stays with empty page; Ajánlatok menu item + page REMOVED later the same day (2026-10-10, all references gone); button text "Üzenet küldése".

Later on 2026-10-10 the dead leftovers (unused offer CSS/PHP helpers) and all offer/deal/rating descriptions in PROJECT-MEMORY were cleaned too, because Raul plans to **rebuild the offer system from scratch** on top of the chat. The chat itself and the kept items above stayed (he confirmed again).

**Why:** he wants a pure chat (text, emoji, files kept 48 h) between buyer and seller.

**How to apply:** don't reintroduce offer mechanics or texts unless asked; chat lives on `/uzenetek/` with a conversation list there; badge counts unread chat only and shows on "Üzenetek" (PC top nav got an Üzenetek item). Related: [[feedback-execute-exactly]], [[project-design-system]].
