# FEATURE — CHAT / ÜZENETEK PRESENTATION

## PURPOSE
Style the Üzenetek list and the chat thread; show the unread-chat badge in navigation. The Ajánlatok page and menu item were removed (offer system deleted 2026-10-10).

## READ FIRST
- `functions.php::kmae_is_messages_page()` + app-shell enqueue branch (`messages.css` or `offer-thread.css`)
- `assets/css/offer-thread.css` (chat thread, only with `?kmae_conversation`)
- `assets/css/messages.css` (list)
- `header.php` (PC nav „Üzenetek” item with `data-kmae-unseen` badge, mobile chat-icon dot)

## THEN IF NEEDED
- `kmae_app_menu_groups()` („Üzenetek” badge)
- `page.php` (`uzenetek` is a raw-shortcode page)
- plugin `FEATURES/CHAT.md`

## ENTRY POINT
Slug `uzenetek` / shortcode `kmae_messages`; `?kmae_conversation=ID` opens the thread. Body classes `kmae-messages-page` (+ plugin `kmae-offer-thread-active` on a thread).

## DATA USED
Route state and plugin `KMAE_Database::count_unread_conversations()`.

## FLOW
Route → body class/CSS → plugin chat DOM. Thread = one column (max 980px): listing header (photo, title, seller, type, city, „Ellenőrzött eladó”), status pill, timeline, composer. Bubbles buyer-left/blue, seller-right/orange. List = glass panel with rows (photo, car, role pill, other party, preview, time, unread dot).

## CHANGE RISKS
Do not treat `.kmae-chat-*` CSS as proof of behavior; send/upload logic is plugin-side. Badge count is reused in header and drawer.
