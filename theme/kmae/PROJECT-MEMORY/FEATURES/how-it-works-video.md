> MEGSZŰNT (2026-10-11): a „Hogyan működik?” oldal (slug `hogyan-mukodik`, `kmae_how_it_works`, `how-it-works.css`, plugin `templates/how-it-works.php`) véglegesen törölve; a fejléc/lábléc/menü linkjei is. A lenti leírás történeti, a forrásban nincs meg.
# FEATURE — HOW IT WORKS + VIDEO LIGHTBOX

## PURPOSE
Apply how-it-works presentation and provide global YouTube lightbox behavior for matching DOM hooks.

## READ FIRST
- `functions.php::kmae_is_how_it_works_page()`
- `assets/css/how-it-works.css`
- `assets/js/theme.js` video section

## THEN IF NEEDED
- plugin map for `kmae_how_it_works` output.

## DO NOT LOAD UNLESS RELEVANT
- landing how-to section; it is a separate template feature.

## ENTRY POINT
Slug `hogyan-mukodik` or shortcode `kmae_how_it_works`.

## FLOW
Route → body class/CSS → expected trigger DOM → delegated JS → YouTube no-cookie iframe lightbox.

## DEPENDENCIES
Expected trigger/lightbox markup is not in theme PHP, so source is `UNVERIFIED` until plugin mapping.

## SIDE EFFECTS
Sets iframe `src`, body class, hidden state and focus only.

## CHANGE RISKS
If video stops opening, first verify the plugin markup still has exact IDs/data attributes before editing JS.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** HOW IT WORKS VIDEO  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** page styling missing; video trigger no-op; lightbox cannot close.

**READ FIRST:** route/style: `kmae_is_how_it_works_page()` + `kmae_enqueue_how_it_works()`; video: `theme.js:214–270`.

**EXPECTED FLOW:** route → CSS; runtime DOM hooks → delegated video click → iframe → lightbox state.

**CHECKPOINTS:** route/class/style correct? required lightbox/iframe/trigger hooks exist? then JS handler.

**IF X → GO TO:** hooks absent → external/plugin markup boundary; hooks present but event fails → exact JS block.

**SEARCH TERMS:** `hogyan-mukodik`, `kmae_how_it_works`, `data-kmae-video-id`, `kmae-video-lightbox`, `openVideo`.

**DO NOT LOAD UNLESS:** full theme templates after exact hook absence is established.

**PLUGIN BOUNDARY:** lightbox/trigger markup owner is UNVERIFIED; no producer exists in theme snapshot.

**CHANGE RISKS:** global JS returns from lightbox section when hooks absent, but earlier handlers are already registered.

**REGRESSION CHECKS:** how-it-works CSS; video open/close/Escape only where hooks exist; unrelated drawer/dropdown/footer still work.

**VERIFICATION STATUS:** VERIFIED JS/route; UNVERIFIED markup owner.
