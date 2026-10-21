# FRONTEND MAP

> **2026-10-10:** the offer / official offer / counter / deal / contact-unlock / trust-rating / history system and its tables were **deleted**. Only the chat remains → `FEATURES/CHAT.md`. Entries describing it were pruned; the source is the truth.

## JavaScript ownership and server bridges

### `assets/js/auth-account-type.js`
- PURPOSE: Project source/asset file.
- NAMED FUNCTIONS: `sync`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `change`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: —
- RELATED FEATURE: `AUTH`

### `assets/js/browse-wizard.js`
- PURPOSE: One handler for the county link, its SVG children and the country button. wizard.addEventListener('click', function (event) { var choice = event.target.closest('[data-county]'); if (!choice || !wizard.contains(choice)) { return; } var slug = choice.getAttribut
- NAMED FUNCTIONS: `parseCurrency`, `formatCurrency`, `getCurrencyValue`, `bindCurrencyInput`, `buildWizardUrl`, `initMapPicker`, `initBodyPicker`, `syncCards`, `initOfferForm`, `msg`, `clearFeedback`, `showFeedback`, `collectIssues`, `syncSearchBtn`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `blur`, `click`, `input`, `pageshow`, `submit`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: —
- RELATED FEATURE: `BROWSE-SEARCH`

### `assets/js/home-greeting.js`
- PURPOSE: PHP handles ordinary visits; a back/forward cache restore skips PHP. window.addEventListener('pageshow', function (event) { if (!event.persisted) { return; } document.querySelectorAll('[data-kmae-home-welcome]').forEach(function (welcome) { welcome.remove(); }
- NAMED FUNCTIONS: —
- AJAX/ACTION VALUES: —
- DOM EVENTS: `pageshow`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: —
- RELATED FEATURE: `DASHBOARD-HUBS`

### `assets/js/listing-field-guards.js`
- PURPOSE: Beviteli szabályok a hirdetés űrlapon. 1) Ahol számot várunk, oda ne kerülhessen betű vagy szimbólum; ahol szöveget,
- NAMED FUNCTIONS: `guard`, `onlyDigits`, `stripNonDigits`, `noDigits`, `stripDigits`, `ready`, `syncMotDate`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `beforeinput`, `change`, `input`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: —
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

### `assets/js/listing-form-numbers.js`
- PURPOSE: Project source/asset file.
- NAMED FUNCTIONS: `digitsOnly`, `formatGroupedNumber`, `caretAfterDigits`, `applyFormattedValue`, `rawNumberValue`, `defaultInvalidMessage`, `syncFormattedValidity`, `onFormattedNumberInput`, `onFormattedNumberKeydown`, `onFormattedNumberBlur`, `enhanceFormattedNumber`, `restoreFormattedNumber`, `normalizeFormattedNumbersForSubmit`, `priceField`, `enhancePriceFields`, `enhanceVisiblePriceField`, `bindPriceModeChange`, `init`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `blur`, `change`, `input`, `keydown`, `submit`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeFormatted`, `kmaeListingFormNumbers`, `kmaeListingWizard`, `kmaeMax`, `kmaeMin`, `kmaePriceNumbersBound`, `kmaeRawNumber`
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

### `assets/js/listing-wizard-preview.js`
- PURPOSE: A még ki nem töltött mező jele. Mondat helyett gondolatjel: a panel így nyugodt marad, és nem tűnik tele hibaüzenettel.
- NAMED FUNCTIONS: `byId`, `ready`, `brandLogoUrl`, `restoreBrandIcon`, `syncBrandIcon`, `fieldValue`, `formatNumber`, `update`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `change`, `input`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeVehicleCatalog`
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

### `assets/js/listing-wizard.js`
- PURPOSE: Step registry. `target` points at markup that already exists in the create form; the engine
- NAMED FUNCTIONS: `purgeLegacyDraft`, `draftFieldType`, `isDraftField`, `isCatalogCustom`, `serializeDraft`, `draftWorthSaving`, `draftTitle`, `draftRequestBody`, `saveDraftNow`, `scheduleDraftSave`, `flushDraftOnLeave`, `restoreDraftValues`, `resumeDraft`, `startSimpleDraft`, `showDraftPrompt`, `close`, `leave`, `onKey`, `byId`, `stepByIndex`, `isStepActive`, `nextActiveIndex`, `prevActiveIndex`, `resolveTarget`, `primaryControl`, `rawNumberValue`, `formatGroupedNumber`, `syncFormattedValidity`, `enhanceFormattedNumber`, `restoreFormattedNumber`, `normalizeFormattedNumbersForSubmit`, `enhanceSalePriceFields`, `bindSaleModeAdvance`, `readValue`, `stepValueLabel` +88 more
- AJAX/ACTION VALUES: `kmae_save_listing_draft`
- DOM EVENTS: `DOMContentLoaded`, `change`, `click`, `input`, `keydown`, `kmae:catalog-pick`, `kmae:device-selected`, `pagehide`, `resize`, `submit`, `visibilitychange`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeAdvanceBound`, `kmaeBound`, `kmaeCaret`, `kmaeFormatted`, `kmaeInsert`, `kmaeListingFormNumbers`, `kmaeListingWizard`, `kmaeListingWizardDraft`, `kmaeMarker`, `kmaeMax`, `kmaeRawNumber`, `kmaeSteps`, `kmaeVehicleCatalog`, `kmaeWizardSource`
- RELATED FEATURE: `LISTING-WIZARD-DRAFTS`

### `assets/js/map.js`
- PURPOSE: Project source/asset file.
- NAMED FUNCTIONS: `setCounty`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `focus`, `focusout`, `mouseenter`, `mouseleave`, `touchstart`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: —
- RELATED FEATURE: `BROWSE-SEARCH`

### `assets/js/my-listings.js`
- PURPOSE: Albira — Saját hirdetéseim: félbehagyott hirdetés szerkesztési választója. A Szerkesztés gomb fölött ívben kinyíló két kerek opció; máshová kattintva bezárul.
- NAMED FUNCTIONS: `place`, `open`, `close`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `click`, `keydown`, `resize`, `scroll`, `submit`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeConfirmed`
- RELATED FEATURE: `LISTINGS`

### `assets/js/nav.js`
- PURPOSE: Csak a jobb szélen (Blocksy header) — ne a menülista végére. injectLogoutBlocksy(); } function removeMenuLogoutDuplicates() { document.querySelectorAll('nav ul.menu li.kmae-nav-logout, #header-menu-1 li.kmae-nav-logout').forEach(function (el) { el.remove(); })
- NAMED FUNCTIONS: `isLoggedIn`, `isInPageContent`, `hrefIsAuth`, `hideLoginRegisterInNav`, `logoutExists`, `createLogoutLink`, `appendLogoutToList`, `injectLogoutBlocksy`, `ensureLogoutInNav`, `removeMenuLogoutDuplicates`, `init`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `load`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeNav`
- RELATED FEATURE: `LIVE-NAV`

### `assets/js/phone-field.js`
- PURPOSE: * Telefonmező: csak számjegyek, magyar tördeléssel (30 111 11 11). * A +36 előtag a mező mellett áll fixen, ide csak a maradék kilenc * számjegy kerül. Bármelyik űrlapon működik, ahol a mezőn ott a * `data-kmae-phone` jelölés.
- NAMED FUNCTIONS: `groupDigits`, `attach`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `blur`, `input`, `keypress`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaePhoneReady`
- RELATED FEATURE: `AUTH`, `PROFILE-COMPANY-SETTINGS`

### `assets/js/photo-motor.js`
- PURPOSE: Ráhúzás közben a doboz kiemelődik. A fájlt maga a mező kapja meg, mert átlátszóan a doboz fölött fekszik — így nem kell külön kezelni a dobást.
- NAMED FUNCTIONS: `init`, `isImage`, `alreadyPicked`, `syncInputFiles`, `syncUploadStatus`, `openLightbox`, `moveImage`, `cfg`, `say`, `regionStats`, `percentile`, `sampleStats`, `edgeStats`, `judgeImage`, `decorate`, `withVerdict`, `ensureSummary`, `updateSummary`, `buildCard`, `renderPreview`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `blur`, `change`, `click`, `dragend`, `dragover`, `dragstart`, `drop`, `focus`, `load`, `submit`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaePhotoMotor`, `kmaePhotoSlot`, `kmaeSlotInput`
- RELATED FEATURE: `PHOTO-MOTOR`

### `assets/js/public.js`
- PURPOSE: Listing form toggles, gallery/lightbox, gallery manager, chat composer helpers, cash formatting, live poll (including `conversation_id`).
- NAMED FUNCTIONS: `togglePriceModeFields`, `toggleCreateFields`, `parseGalleryUrls`, `getGalleryLightbox`, `getGalleryIndex`, `setGalleryIndex`, `navigateGallery`, `updateLightboxImage`, `openLightbox`, `closeLightbox`, `navigateLightbox`, `initGalleryManager`, `visibleItems`, `updateBadges`, `moveItem`, `clearDropMarks`, `warnOnce`, `stripCashDigits`, `formatCashDigits`, live-poll scheduler (`poll`, `schedule`, `markActive`, `updateBadge`)
- AJAX/ACTION VALUES: `kmae_poll_page`
- DOM EVENTS: `DOMContentLoaded`, `change`, `click`, `dragend`, `dragleave`, `dragover`, `dragstart`, `drop`, `focusin`, `input`, `keydown`, `submit`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeFormatted`, `kmaeListingFormNumbers`, `kmaePublic`, `kmaeRawNumber`
- RELATED FEATURE: `CHAT`, `LIVE-NAV`

### `assets/js/swipe.js`
- PURPOSE: Project source/asset file.
- NAMED FUNCTIONS: `topCard`, `recordPass`, `swipeAway`
- AJAX/ACTION VALUES: `kmae_browse_pass`
- DOM EVENTS: `DOMContentLoaded`, `click`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeSwipe`
- RELATED FEATURE: `BROWSE-SEARCH`

### `assets/js/vehicle-catalog.js`
- PURPOSE: A wizardon a népszerű márkák maradnak; a teljes lista a mező jobb alatti linkről nyílik, kívülre kattintás bezárja.
- NAMED FUNCTIONS: `norm`, `findIgnoreCase`, `knownBrand`, `modelsFor`, `matches`, `closeAll`, `createPicker`, `isCustom`, `syncShowAllButton`, `setCustom`, `options`, `syncForcedCustom`, `syncValidity`, `buildOption`, `hasCatalogHits`, `render`, `setActive`, `open`, `close`, `pick`, `acceptTyped`, `onExternalChange`
- AJAX/ACTION VALUES: —
- DOM EVENTS: `DOMContentLoaded`, `blur`, `change`, `click`, `input`, `keydown`, `kmae:catalog-force-custom`, `kmae:catalog-show-list`, `mousedown`, `mouseleave`, `mouseover`
- LOCALIZED/GLOBAL KMAE OBJECTS REFERENCED: `kmaeVehicleCatalog`
- RELATED FEATURE: `VEHICLE`

## Verified frontend → backend bridges

- `assets/js/listing-wizard.js` uses localized AJAX URL with action `kmae_save_listing_draft` → `KMAE_Listing_Drafts::ajax_save`.
- `assets/js/listing-locate.js` (wizard + simple editor, config `kmaeListingLocate`) → action `kmae_locate` → `KMAE_Counties::ajax_locate` (fills `#kmae_county` / `#kmae_city`).
- `assets/js/registration-scan.js` → action `kmae_registration_scan` → `KMAE_Registration_Scan::ajax_scan` (OpenAI); result → `window.kmaeWizard.applyVehicleData` in `listing-wizard.js`.
- `assets/js/terms-accept.js` (only when `KMAE_Legal::needs_acceptance`) → action `kmae_accept_terms` → `KMAE_Legal::ajax_accept`.
- `assets/js/swipe.js` uses `kmae_browse_pass` → `KMAE_Public::ajax_browse_pass` → `KMAE_Browse::record_pass`.
- `assets/js/public.js` uses `kmae_poll_page` → `KMAE_Public::ajax_poll_page` → `KMAE_Live_Page::get_fingerprint` plus unseen activity count.
- `KMAE_Public::enqueue()` localizes photo quality thresholds from `KMAE_Photo_Motor::client_config()`, making PHP the threshold source of truth.
- `KMAE_Public::enqueue_vehicle_catalog()` localizes `KMAE_Vehicle::catalog_payload()` for `assets/js/vehicle-catalog.js`.

## kmaeConfirm
`window.kmaeConfirm(opts)` (assets/js/confirm.js + css/confirm.css, enqueued with public.js, dependency of kmae-public) returns Promise<bool> — use it instead of `window.confirm()`. Used by the gallery manager image delete (public.js initGalleryManager).
