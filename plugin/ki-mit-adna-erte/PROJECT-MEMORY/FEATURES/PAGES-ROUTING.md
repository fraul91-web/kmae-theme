# FEATURE — Pages, routing, shortcodes and templates

## PURPOSE
WordPress oldalak létrehozása, shortcode-router, query varok, template routing, listing archive/single routing.

## READ FIRST
- `includes/class-kmae-pages.php`
- `public/class-kmae-shortcodes.php`
- `includes/class-kmae-post-types.php`

## THEN IF NEEDED
- `includes/class-kmae-taxonomies.php`
- `includes/class-kmae-launch.php`
- `templates/archive-listing.php`
- `templates/single-listing.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
WordPress page requests, `the_content` injection, shortcode parser, CPT template filter. Saved listings live on `/kedvencek/` (`kmae_favorites`), created by `ensure_pages()`.

## IMPORTANT SYMBOLS
`KMAE_Pages::{ensure_pages,inject_page_content,url,conversation_thread_url,messages_page_url,is_messages_page,mark_conversation_seen_on_visit,register_query_vars}`, all `KMAE_Shortcodes` methods, `KMAE_Post_Types::template_loader`.

## DATA USED
WP pages/options/query vars; no separate route table outside source constants/arrays.

## FLOW
Managed WP page slug → page content/shortcode → shortcode method → template require. Chat thread is `uzenetek/?kmae_conversation=<id>` and is marked seen on visit. `[kmae_notifications]` renders empty.

## DEPENDENCIES
Shortcode methods assemble data from domain classes before including templates.

## SIDE EFFECTS
Page ensure may create/update managed pages and front-page settings. Chat visit updates the per-conversation seen marker.

## CHANGE RISKS
Slug, shortcode, page creation and theme links must stay aligned. For a route bug inspect `HOOK-ROUTE-MAP.md` and `TEMPLATE-MAP.md` before templates.


## SOURCE-DETECTED EXTERNAL CLASS FILE DEPENDENCIES

- `includes/class-kmae-database.php`
- `includes/class-kmae-auth.php`
- `includes/class-kmae-listings.php`
- `includes/class-kmae-meta.php`
- `includes/class-kmae-counties.php`
- `includes/class-kmae-query.php`
- `includes/class-kmae-vehicle.php`
- `includes/class-kmae-browse.php`
- `includes/class-kmae-settings.php`
- `includes/class-kmae-dashboard.php`


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Pages/routing  
**COMMON SYMPTOMS:** managed page/url/query/shortcode/template.  
**READ FIRST (symbol-level):** `KMAE_Pages`; matching `KMAE_Shortcodes` method.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `shortcode_for conversation_thread_url template routing`.  
**DO NOT LOAD UNLESS:** business service if route correct but data wrong.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.
