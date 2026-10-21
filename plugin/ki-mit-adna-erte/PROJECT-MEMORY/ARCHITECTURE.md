# ARCHITECTURE MAP

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

## Runtime layers

1. **Bootstrap** — `ki-mit-adna-erte.php`
2. **Class loader** — `includes/class-kmae-loader.php`
3. **Coordinator** — `includes/class-kmae-plugin.php`
4. **Domain/services** — `includes/class-kmae-*.php`
5. **Public controllers + shortcodes** — `public/class-kmae-public.php`, `public/class-kmae-shortcodes.php`
6. **Views/templates** — `templates/**`
7. **Frontend behavior** — `assets/js/**`, `assets/css/public.css`
8. **WP admin** — `admin/**`, `assets/css/admin.css`

## Persistence split

- WordPress `posts`/`postmeta`: listings, listing state, vehicle data, photo slots/gallery, view counts.
- WordPress `users`/`usermeta`: identity adjuncts, company profile, browse passes, verification tokens, chat seen map, Google link state.
- WordPress `options`: settings, schema/page migration markers.
- WordPress `transients`: one-time moderation/photo notices, Google OAuth state, dashboard greeting.
- Custom tables: conversations, conversation messages, private listing minimums, user profiles.

## Request families

- **Shortcode/page render**: WordPress page → KMAE page mapping → shortcode → template.
- **Same-page POST**: `KMAE_Public::handle_forms()` on `init` dispatches `kmae_action` business actions.
- **admin-post**: profile/auth/settings/contact/listing management handlers.
- **WP AJAX (logged in)**: draft save, browse pass, live poll.
- **Custom domain hooks**: chat message / listing lifecycle events → notifications, live signal.
- **Cron**: daily archived-listing purge.
- **REST**: no plugin-defined `register_rest_route`; CPT/taxonomy expose native WP REST.

## Launch constraints

Source constants in `ki-mit-adna-erte.php` set single-category launch and category `jarmu`. `KMAE_Launch` and `KMAE_Vehicle::is_vehicle_launch()` branch multiple flows from these values.
