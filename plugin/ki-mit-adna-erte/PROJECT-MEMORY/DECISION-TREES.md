# DECISION TREES — PLUGIN V2

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

MAP-TYPE: ON-DEMAND

All branches are routing rules; source remains authoritative.

## 1. Email did not arrive
```text
Domain mutation happened?
├─ NO → feature producer/guard
└─ YES → expected do_action emitted?
   ├─ NO → producer event branch
   └─ YES → notification listener invoked?
      ├─ NO → hook registration/init
      └─ YES → recipient + `allows_mail` pref correct?
         ├─ NO → Notifications/Auth preference
         └─ YES → KMAE_Brand::send_mail → wp_mail boundary
```
**STOP:** once `wp_mail` is called with correct recipient/subject/body, plugin routing ends.

## 2. Listing not published
```text
process_create_listing failed validation?
├─ YES → failing validator only
└─ NO → inserted status = awaiting_photos?
   ├─ YES → Photo_Motor readiness → activate_if_photos_ready
   └─ NO → moderation/status/lifecycle branch
```

## 3. Wizard autosave/restore
```text
AJAX request sent?
├─ NO → listing-wizard.js
└─ YES → ajax_save response error?
   ├─ YES → nonce/ownership/payload in KMAE_Listing_Drafts::ajax_save
   └─ NO → saved payload correct?
      ├─ NO → server serialization
      └─ YES → client restore/state
```

## 4. Photo mismatch client vs server
```text
Client rejects before upload? → photo-motor.js/client_config
Server rejects? → KMAE_Photo_Motor::validate_request / KMAE_Images
Server accepts but listing stays awaiting_photos? → activate_if_photos_ready
```
**Rule:** never weaken only client validation to bypass authoritative server rejection.

## 5. Browse result wrong
```text
Base query IDs wrong? → KMAE_Query
Base IDs right, personalized IDs wrong? → KMAE_Browse
IDs right, UI wrong? → template/JS
```

## 6. Live count/badge wrong
```text
AJAX poll response count wrong? → ajax_poll_page/KMAE_Live_Page
Response right, badge wrong? → public.js/nav.js + rendered selector
```

## 7. Auth-gated action blocked
```text
User logged in?
├─ NO → auth
└─ YES → verification required and incomplete?
   ├─ YES → verification flow
   └─ NO → action-specific guard (listing/start_conversation/etc.)
```

## 8. Cron archive purge wrong
```text
Event scheduled?
├─ NO → KMAE_Activator::schedule_cron
└─ YES → hook fires?
   ├─ NO → WordPress cron runtime boundary
   └─ YES → KMAE_Listings::purge_expired_archived eligibility/delete
```

## 9. Page route wrong
```text
Correct managed page/query var? → KMAE_Pages
Correct shortcode chosen? → KMAE_Shortcodes
Correct shortcode but wrong markup? → plugin template
Correct plugin markup but layout/style wrong? → THEME BOUNDARY
```

## 10. Plugin boot failure
```text
kmae_run reached? → KMAE_Plugin::run
Class unavailable? → loader/require list
Class available but hooks absent? → feature init registration
```
