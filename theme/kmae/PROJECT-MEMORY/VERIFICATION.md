# CROSS-FILE VERIFICATION — THEME PASS

## Verification scope

The first-pass architecture was re-checked with project-wide symbol/text searches across all theme PHP, JS and CSS files. The plugin archive was not opened in this phase.

## Verified connections

### A. Front-page split
- `front-page.php` calls `template-parts/simple-mode/app-home.php` only when logged in.
- guest path calls `template-parts/simple-mode/landing.php`.
- both use shared header/footer.

### B. Dashboard bridge
- `app-home.php` checks `class_exists('KMAE_Dashboard')`.
- when present it calls `KMAE_Dashboard::get_data()`.
- it directly includes `KMAE_PLUGIN_DIR . 'templates/partials/dashboard-home.php'`.

### C. Listing-create A → B → C
- detector: `kmae_is_listing_create_page()`.
- body class: `kmae_body_classes()` adds `kmae-listing-create-page`.
- enqueue: `kmae_enqueue_listing_create_style()` adds `listing-create.css`.
- render mode: `page.php` independently detects same slug/shortcode and uses direct `do_shortcode()`.

### D. Browse A → B → C
- detector accepts `kmae_listing` archive OR `hirdetesek` page OR `kmae_browse` shortcode.
- body class `kmae-browse-page` is added.
- `browse.css` is enqueued at priority 100.

### E. Profile/settings raw rendering
- `functions.php` detects each route for body class + CSS.
- `page.php` classifies profile/settings as raw-shortcode pages and uses direct `do_shortcode()`.

### F. Navigation model
- desktop header builds its own navigation array in `header.php`.
- mobile logged-in drawer obtains a second model from `kmae_app_menu_groups()` through `app-menu-panels.php`.
- both use `kmae_page_url()` and plugin icon/badge APIs where available.

### G. JavaScript hook ownership
Verified theme markup exists for:
- site header;
- drawer/backdrop/open/close;
- profile dropdown;
- guest nav;
- footer toggle/panel;
- bottom navigation.

No theme markup was found for:
- `[data-kmae-sheet]` tab root;
- YouTube lightbox root/iframe/triggers.

Those are correctly marked external/legacy/`UNVERIFIED` rather than assumed.

### H. No hidden theme backend path
Project-wide search found no:
- `wp_ajax*` registration;
- `admin-ajax.php` reference;
- `register_rest_route`;
- `$wpdb`;
- SQL INSERT/UPDATE/DELETE;
- cron/schedule call;
- `fetch()`;
- `XMLHttpRequest`;
- jQuery AJAX;
- browser persistent storage.

### I. Include boundary
Only one explicit PHP `include/require` was found in the theme: the plugin dashboard partial include in `app-home.php`.

### J. Hook registry
All `add_action`/`add_filter` occurrences are documented in `SYMBOL-MAP.md`; no `do_action` or `apply_filters` occurs in theme source.

## Unverified boundaries retained deliberately

- plugin shortcode implementations;
- plugin archive/single template selection;
- plugin DB tables and exact queries;
- AJAX/REST handlers used by plugin-rendered forms;
- plugin JavaScript behind listing-create, my-listings, chat, profile/settings forms;
- definition of plugin style handle `kmae-public`;
- exact semantics behind the unread chat count (`count_unread_conversations`);
- actual markup source for theme.js sheet tabs and video lightbox.

## Snapshot integrity

See `SNAPSHOT.md` for file hashes. Source archive was read/extracted only; map generation wrote only under `/PROJECT-MEMORY-THEME/PROJECT-MEMORY/`.

## V2 SOURCE VERIFICATION

### Exact source snapshot

Re-verified against theme archive:

`kmae-theme(20260918-104056).zip`

SHA-256:

`1c7119a191094d9a40816a28e0d142349599006d63050bdcba50b65163d78fdf`

This exactly matches the archive SHA already declared in `SCOPE.md` / `SNAPSHOT.md`.

### Correction made to existing memory

`SNAPSHOT.md` contained one malformed/wrong file hash for:

`template-parts/app-menu-panels.php`

Correct source SHA-256:

`ee66e39d04de10786a431096e32b824b25a12f4f303830e924a1695e1b751cbc`

The snapshot line was corrected from source. No source file was modified.

### V2 source-level checks repeated

Verified directly from source:

- all `functions.php` named function start/end ranges used by the router;
- route detectors → body-class branches → style-enqueue branches;
- `page.php` classification and raw-vs-filtered render paths;
- front-page auth split;
- dashboard plugin bridge;
- desktop header active-state logic and own-listing exception;
- drawer/menu model and count consumers;
- header/footer app-layout cross-file tag pairing;
- drawer/dropdown/footer/bottom-nav/guest-nav JS hooks;
- no theme producer for `[data-kmae-sheet]`;
- no theme producer for video lightbox/iframe/trigger hooks;
- plugin consumers (`KMAE_Pages`, `KMAE_Brand`, `KMAE_Seller`, `KMAE_Database`, `KMAE_Icons`, `KMAE_Dashboard`, `KMAE_Google_Auth`);
- no theme-owned AJAX/REST/fetch/XHR/jQuery-AJAX/database-write path;
- guest single-listing caveat: body class exists for guests, but `single-listing.css` enqueue is inside logged-in `kmae_enqueue_app_shell()` and therefore is not reached for guests.

### Verification status policy

- **VERIFIED** only for relations confirmed in this exact theme source snapshot.
- **UNVERIFIED PLUGIN TARGET** for plugin implementation details not inspected in this theme-only pass.
- **UNVERIFIED EXTERNAL MARKUP** for runtime hooks whose producer is absent from the theme source.

### Routing quality gate

`ROUTING-TESTS.md` contains 15 simulated symptom routes:
- 15 PASS;
- 0 FAIL;
- initial source target count stays within 1–4 theme files;
- no case requires a default full repository, full symbol map, all CSS, all JS, or full plugin load.

See:
- `DIAGNOSTIC-ROUTER.md`
- `DECISION-TREES.md`
- `PLUGIN-BOUNDARIES.md`
- `HOTSPOTS.md`
- `REGRESSION-MAP.md`
- `SEARCH-ALIASES.md`
- `ROUTING-TESTS.md`

## V2 FINAL QA GATE

Automated final checks on the unchanged source snapshot and updated map:

- source snapshot rows re-hashed: **32 / 32 match**;
- PHP syntax: **9 / 9 files pass `php -l`**;
- JavaScript syntax: **`assets/js/theme.js` passes `node --check`**;
- original memory files present: **31 / 31**;
- original useful content preservation check: **31 / 31 pass** (existing file content retained as prefix; `SNAPSHOT.md` differs only by the source-verified hash correction before V2 additions);
- feature maps with V2 diagnostic block: **19 / 19**;
- diagnostic router entries: **25**;
- decision trees: **12**;
- hotspot entries: **12**;
- plugin/theme boundary entries: **12**;
- simulated routing tests: **15 / 15 PASS**;
- maximum initial theme source files required by simulated routes: **4**;
- project source files modified by this task: **0**.

The quality gate is satisfied for the THEME V2 navigation layer. Plugin-side implementation details remain deliberately unverified in this theme-only pass.
