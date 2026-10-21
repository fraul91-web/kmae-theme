# ROUTING TESTS — PLUGIN V2

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

MAP-TYPE: VERIFICATION

These are simulated localization tests; they do **not** modify source. Pass criterion: reach the likely source area from minimal context without repository-wide discovery.

## TEST 1 — PASS
- **USER SYMPTOM:** Chatben nem megy el az üzenet
- **ROUTE:** ROUTER → CHAT
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Public send_conversation_message branch; KMAE_Conversations::save_message
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** server persistence before UI.

## TEST 2 — PASS
- **USER SYMPTOM:** Autosave nem ment
- **ROUTE:** ROUTER → WIZARD-DRAFTS
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** listing-wizard.js kmae_save_listing_draft; KMAE_Listing_Drafts::ajax_save
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** request vs persistence split.

## TEST 3 — PASS
- **USER SYMPTOM:** Fotók után awaiting_photos marad
- **ROUTE:** ROUTER → PHOTO-MOTOR → LISTINGS
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Photo_Motor required/readiness; KMAE_Listings::activate_if_photos_ready
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** avoids full listing flow.

## TEST 4 — PASS
- **USER SYMPTOM:** Browse rossz sorrendben adja az autókat
- **ROUTE:** ROUTER → BROWSE
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Browse::personalize_listings
- **EXPECTED SOURCE FILE COUNT:** 1–2 files
- **WHY:** targets personalization not template.

## TEST 5 — PASS
- **USER SYMPTOM:** Pass után újra azonnal megjelenik autó
- **ROUTE:** ROUTER → BROWSE
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Public::ajax_browse_pass; KMAE_Browse::record_pass; KMAE_Browse::personalize_listings
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** persistence then consumption.

## TEST 6 — PASS
- **USER SYMPTOM:** Olvasatlan üzenet jelvény nem frissül
- **ROUTE:** ROUTER → LIVE-NAV
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Public::ajax_poll_page; KMAE_Live_Page fingerprint/count; public.js poll consumer
- **EXPECTED SOURCE FILE COUNT:** 2–3 files
- **WHY:** JSON checkpoint separates backend/UI.

## TEST 7 — PASS
- **USER SYMPTOM:** Céges listing rossz települést kap
- **ROUTE:** ROUTER → PROFILE/COMPANY → LISTINGS
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Company::apply_listing_location; KMAE_Listings::save_meta_from_post
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** targets override path.

## TEST 8 — PASS
- **USER SYMPTOM:** Archivált listing nem purge-olódik
- **ROUTE:** ROUTER → CRON-ARCHIVE
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Activator::schedule_cron; KMAE_Listings::purge_expired_archived
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** scheduler/runtime vs deletion eligibility.

## TEST 9 — PASS
- **USER SYMPTOM:** Google callback loopol
- **ROUTE:** ROUTER → GOOGLE-AUTH
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Google_Auth::handle_callback; KMAE_Google_Auth::enforce_completion
- **EXPECTED SOURCE FILE COUNT:** 1 file
- **WHY:** keeps password auth out unless linking issue.

## TEST 10 — PASS
- **USER SYMPTOM:** Contact form elküldve, email nincs
- **ROUTE:** ROUTER → CONTACT-FORM
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** KMAE_Public::admin_contact_form; KMAE_Settings::contact_form_email; wp_mail boundary
- **EXPECTED SOURCE FILE COUNT:** 2 files
- **WHY:** stops at mail transport if args correct.

## TEST 11 — PASS
- **USER SYMPTOM:** Plugin feature hook egyáltalán nem fut
- **ROUTE:** ROUTER → BOOT
- **MAPS READ:** `DIAGNOSTIC-ROUTER.md` + one primary feature map (secondary only at proven boundary)
- **SOURCE TARGETS:** kmae_run; KMAE_Plugin::run; feature init method
- **EXPECTED SOURCE FILE COUNT:** 2–3 files
- **WHY:** loads boot only when registration actually missing.

