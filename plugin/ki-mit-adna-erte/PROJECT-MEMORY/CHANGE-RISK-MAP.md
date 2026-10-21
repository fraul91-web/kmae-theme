# CHANGE-RISK MAP

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

## Very high fan-out

### Listing status (`_kmae_status`)
Consumers: public visibility, browse query, contact CTA, archive lifecycle, dashboard, public singular blocking.

Before changing: `FEATURES/LISTINGS.md`, `FEATURES/BROWSE-SEARCH.md`.

### Listing deletion
`force_delete_listing()` spans WP attachments/posts, `private_data`, conversation alerts and participant notifications. Search callers before changing cleanup.

### Vehicle field names
Shared across PHP parser/meta, templates, wizard JS, catalog JS, moderation and browse.

### Photo slot names
Shared between request validation, JS inputs, server upload loop, listing slot meta, gallery order.

## Medium fan-out

- Managed page slugs / shortcodes.
- E-mail verification/profile table fields.
- Company location meta.
- Chat seen/unread markers.
- Launch constants.

## Destructive operations to treat carefully

- `KMAE_Listings::force_delete_listing`
- cron purge
- admin listing deletion
- gallery/slot attachment replacement
- logout everywhere / password changes

Use `SYMBOL-MAP.md` reverse callers plus `FLOWS.md` before edits.
