# FEATURE — PAGE / SHORTCODE RENDERING ROUTER

## PURPOSE
Choose wrapper and WordPress content-filter behavior for Albira shortcode pages.

## READ FIRST
- `page.php` entire file.

## THEN IF NEEDED
- `functions.php` matching page detector;
- plugin shortcode map for the target shortcode.

## DO NOT LOAD UNLESS RELEVANT
- unrelated CSS.

## ENTRY POINT
WordPress `page.php` template.

## CLASSIFICATION
- hub: `kmae_hub_sell`, `kmae_hub_buy`, `kmae_hub_stats` or slugs `eladni`, `venni`, `statisztikak`;
- listing create: `kmae_create_listing` or slug `hirdetes-feladasa`;
- my listings: `kmae_my_listings` or slug `sajat-hirdetesek`;
- raw: profile/settings/login/register/favorites shortcode or corresponding slug (`profil`, `beallitasok`, `belepes`, `regisztracio`, `kedvencek`).

## FLOW
First loop pass → classify → `rewind_posts()` → render branch.

## DATA USED
Post content + slug.

## SIDE EFFECTS
None.

## CHANGE RISKS
Direct `do_shortcode(raw post_content)` intentionally bypasses `the_content()` filters for selected pages. Moving a page between branches can change auto-paragraphing and wrapper DOM even if shortcode output is unchanged.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** PAGE SHORTCODE RENDERING  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** extra wrappers/wpautop; wrong page modifier; shortcode not rendered raw; ordinary page loses formatting.

**READ FIRST:** `page.php:10–52` classifier → `54–61` modifier → `64–97` render switch.

**EXPECTED FLOW:** first post content/slug → flags → rewind → one render branch → shortcode/content output.

**CHECKPOINTS:** which flag true? correct exclusive branch? raw vs filtered output? plugin output itself correct?.

**IF X → GO TO:** correct branch but shortcode HTML wrong → plugin; wrong branch → page.php only.

**SEARCH TERMS:** `$is_hub_menu`, `$is_listing_create`, `$is_my_listings`, `$is_raw_shortcode`, `do_shortcode`, `the_content`.

**DO NOT LOAD UNLESS:** CSS before confirming HTML structure; plugin internals before branch is proven correct.

**PLUGIN BOUNDARY:** shortcode handler output is plugin-owned.

**CHANGE RISKS:** classification duplicates some `functions.php` route logic; changes can drift.

**REGRESSION CHECKS:** one page from every branch + ordinary page.

**VERIFICATION STATUS:** VERIFIED.
