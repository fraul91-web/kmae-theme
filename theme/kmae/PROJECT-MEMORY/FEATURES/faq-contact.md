# FEATURE — FAQ + CONTACT PRESENTATION

## PURPOSE
Detect public/info pages and apply dedicated desktop presentation.

## READ FIRST
- `functions.php::kmae_is_faq_page()` / `kmae_enqueue_faq()`
- `functions.php::kmae_is_contact_page()` / `kmae_enqueue_contact()`
- `assets/css/faq.css`
- `assets/css/contact.css`

## THEN IF NEEDED
- `page.php`
- plugin map for `kmae_faq` / `kmae_contact` markup and contact submission.

## DO NOT LOAD UNLESS RELEVANT
- offers/profile/settings.

## ENTRY POINTS
FAQ: slug `gyik` or shortcode `kmae_faq`.
Contact: slug `kapcsolat` or shortcode `kmae_contact`.

## FLOW
Detector → body class → feature CSS → normal page rendering/plugin shortcode.

## DATA USED
No FAQ/contact application data handled by theme.

## SIDE EFFECTS
No theme-side form submission/AJAX.

## CHANGE RISKS
Both feature styles depend on markup not emitted in theme source. A form/accordion logic issue belongs to plugin map, not CSS map.

## V2 DIAGNOSTIC ROUTING

**FEATURE:** FAQ CONTACT  
**PURPOSE:** Keep diagnosis inside the smallest theme-owned path before crossing any plugin boundary.

**COMMON SYMPTOMS:** FAQ/contact style missing; body class absent; accordion/form submission fails.

**READ FIRST:** FAQ: `kmae_is_faq_page()` + `kmae_enqueue_faq()`; Contact: `kmae_is_contact_page()` + `kmae_enqueue_contact()`.

**EXPECTED FLOW:** route detector → body class → feature CSS → externally/shortcode-produced feature DOM.

**CHECKPOINTS:** detector/class/handle correct? DOM correct? symptom visual or interactive/business?.

**IF X → GO TO:** visual → feature CSS; accordion/form behavior/data → plugin/shortcode boundary.

**SEARCH TERMS:** `gyik`, `kmae_faq`, `kmae-faq-page`, `kapcsolat`, `kmae_contact`, `kmae-contact-page-view`.

**DO NOT LOAD UNLESS:** global plugin database or unrelated theme JS unless exact handler is found.

**PLUGIN BOUNDARY:** FAQ/contact shortcode behavior/form processing UNVERIFIED PLUGIN TARGET.

**CHANGE RISKS:** both pages can be guest or logged-in and load after different parent handles.

**REGRESSION CHECKS:** guest + logged-in target page; detector negative on ordinary page.

**VERIFICATION STATUS:** VERIFIED theme route/style boundary.

## GYIK TARTALOM ÉS KERESÉS (2026-10-11)
- Tartalom: plugin `templates/faq.php` `$faq_groups` tömbje (4 csoport: `id`, `theme` blue/orange, `questions` = `question` / `answer` / `keywords`). Új kérdés = új tömbelem ugyanitt.
- Kereső: ugyanebben a sablonban, inline `<script>` (`#kmae-faq-search`, `[data-kmae-faq-item]`, `[data-kmae-faq-group]`). Kérdés + válasz + `keywords` szövegében keres, ékezet nélkül, több szó = ÉS; a találatok kinyílnak, üres kereséskor visszaáll az eredeti nyitott állapot.
- Stílus: theme `assets/css/faq.css` vége (`.kmae-faq__search*`, `.kmae-faq__empty`, `.kmae-faq [hidden]`).
