# PAGE / SHORTCODE ROUTE MAP

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

- `/belepes/` → `[kmae_login]`
- `/regisztracio/` → `[kmae_register]`
- `/hirdetes-feladasa/` → `[kmae_create_listing]`
- `/sajat-hirdetesek/` → `[kmae_my_listings]`
- `/hirdetesek/` → `[kmae_browse]`
- `/kezdolap/` → `[kmae_landing]`
- `/profil/` → `[kmae_profile]`
- `/oktatas/` → `[kmae_education]`
- `/beallitasok/` → `[kmae_settings]`
- `/biztonsag/` → `[kmae_security]`
- `/gyik/` → `[kmae_faq]`
- `/kapcsolat/` → `[kmae_contact]`
- `/feltetelek/` → `[kmae_terms]`
- `/adatvedelem/` → `[kmae_privacy]`
- `/ertesitesek/` → `[kmae_notifications]`
- `/uzenetek/` → `[kmae_messages]`
- `/eladni/` → `[kmae_hub_sell]`
- `/venni/` → `[kmae_hub_buy]`
- `/statisztikak/` → `[kmae_hub_stats]`

## REST (böngészés v2, `KMAE_Browse_Feed`, szerződés: `FEATURES/BROWSE-V2.md`)

- `GET /wp-json/kmae/v1/feed` → `KMAE_Browse_Feed::rest_feed` (nyilvános, user-független, `s-maxage=60`)
- `GET /wp-json/kmae/v1/me/browse` → `KMAE_Browse_Feed::rest_me` (csak belépve, nonce, no-store)

## Query vars registered by `KMAE_Pages`

`megye`, `kivitel`, `mod`, `kp_min`, `kp_max`, `step`, `tipus`, `kategoria`.

## Chat thread

`/uzenetek/?kmae_conversation=<conversation_id>` → chat thread in `[kmae_messages]`; visit hook marks the conversation seen; body class `kmae-offer-thread-active` marks thread chrome.
