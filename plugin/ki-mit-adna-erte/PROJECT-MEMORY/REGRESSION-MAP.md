# REGRESSION ROUTING — PLUGIN V2

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

MAP-TYPE: ON-DEMAND

## CHANGED: `KMAE_Notifications::send_offer_email`
**VERIFY:** preference ON/OFF; chat message / listing published / unfinished listing mails; HTML wrapper; recipient identity.

## CHANGED: `KMAE_Brand::send_mail / wrap_email`
**VERIFY:** auth mail, chat/listing mail, contact form output; HTML headers; logo/mascot URLs.

## CHANGED: `KMAE_Public::handle_forms`
**VERIFY:** create listing; start conversation / send message; verification gate; nonce/error redirects.

## CHANGED: `KMAE_Public::process_create_listing`
**VERIFY:** private and company seller; manual/catalog vehicle; moderation pass/fail; photos ready/not ready; draft discard; publish event.

## CHANGED: `KMAE_Listings::save_meta_from_post`
**VERIFY:** create + edit; company location override; seller price/commitments; vehicle fields.

## CHANGED: `KMAE_Photo_Motor validation`
**VERIFY:** required 7 slots; recommended slots; MIME/size/quality paths; client_config parity; awaiting_photos activation.

## CHANGED: `KMAE_Listing_Drafts::ajax_save/to_client`
**VERIFY:** new draft; update same user draft; ownership; autosave payload; reload restore; final submit discard.

## CHANGED: `KMAE_Browse::personalize_listings`
**VERIFY:** passed list; reinsertion interval; wizard filters; anonymous/logged-in behavior.

## CHANGED: `KMAE_Auth verification`
**VERIFY:** registration; login; verify link; resend; create-listing gate; start-conversation gate.

## CHANGED: `KMAE_Pages routing`
**VERIFY:** managed URLs; `uzenetek` chat thread query + seen state; browse URL; legacy redirect; shortcode mapping.

## CHANGED: `KMAE_Plugin::run`
**VERIFY:** front end; wp-admin; activation-upgrade dependent services; every feature init/hook still registered.

