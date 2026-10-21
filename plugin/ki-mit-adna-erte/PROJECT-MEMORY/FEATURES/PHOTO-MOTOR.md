# FEATURE — Photo motor and image processing

## PURPOSE
Kötelező/ajánlott fotóslotok, kliens+szerver minőségellenőrzés, automatikus képjavítás, galéria és WebP feldolgozás.

## READ FIRST
- `includes/class-kmae-photo-motor.php`
- `includes/class-kmae-images.php`
- `assets/js/photo-motor.js`
- `templates/partials/listing-photo-motor.php`

## THEN IF NEEDED
- `templates/partials/listing-gallery.php`
- `templates/partials/listing-gallery-manager.php` (also rendered in the simple editor photo column, `vehicle-form-fields.php`)
- Cover sync between existing and new photos: hidden `kmae_cover_new` (photo-motor.js) → `KMAE_Listings::handle_image_uploads` puts the first new image first; JS events `kmae:cover-new`, `kmae:cover-new-cleared`, `kmae:cover-existing`, `kmae:gallery-changed` (photo-motor.js ↔ public.js initGalleryManager).
- `includes/class-kmae-listings.php`

## DO NOT LOAD UNLESS RELEVANT
- `ki-mit-adna-erte.php`
- `includes/class-kmae-loader.php`
- `includes/class-kmae-plugin.php`
- `includes/class-kmae-activator.php`
- `includes/class-kmae-deactivator.php`


## ENTRY POINTS
Listing create/edit forms, `assets/js/photo-motor.js`, `KMAE_Listings::handle_image_uploads()`.

## IMPORTANT SYMBOLS
- `KMAE_Photo_Motor::{required_slots,recommended_slots,validate_request,handle_slot_uploads,analyze_file,autocorrect_file,client_config}`
- `KMAE_Images::process_attachment`

## DATA USED
7 required slots: `front_left`, `front_right`, `rear_left`, `rear_right`, `side`, `interior`, `odometer`; 6 recommended slots: engine bay, trunk, seats, wheels, windshield, damage. Listing slot/gallery meta + attachment slot/hash/autofix/analysis meta.

## FLOW
See `../FLOWS.md` sections 5–6.

## DEPENDENCIES
PHP thresholds are localized to browser via `client_config()`. WordPress media APIs store attachments; image processor creates normalized output.

## SIDE EFFECTS
Can rewrite an uploaded temporary image only when correction passes re-analysis; stores analysis/calibration record; replaces old slot attachment; rebuilds gallery and featured image.

## CHANGE RISKS
Keep browser thresholds and server thresholds sourced from PHP. Duplicate hashing, slot replacement and attachment deletion are linked; inspect all three before changing upload behavior.


## V2 DIAGNOSTIC ROUTING

**FEATURE:** Photo motor  
**COMMON SYMPTOMS:** slot/readiness/validation.  
**READ FIRST (symbol-level):** `KMAE_Photo_Motor::validate_request()`; `assets/js/photo-motor.js`.  
**EXPECTED FLOW:** entry/producer → validation/state mutation → domain event or persistence → render/response. Use `../DIAGNOSTIC-ROUTER.md` for the symptom-specific chain.  
**CHECKPOINTS:** (1) did the entry point execute; (2) is authoritative persisted/server state correct; (3) was the expected hook/response emitted; (4) if server state is correct, continue only into render/client/boundary.  
**IF SERVER STATE WRONG → GO TO:** the first producer/guard listed above and its immediate dependency only.  
**IF SERVER STATE CORRECT → GO TO:** next consumer from `../DIAGNOSTIC-ROUTER.md`; do not re-read upstream persistence.  
**SEARCH TERMS:** `required_slots validate_request photo-motor`.  
**DO NOT LOAD UNLESS:** browse/chat.  
**PLUGIN BOUNDARY:** see `../PLUGIN-BOUNDARIES.md` when flow reaches theme, `wp_mail`, WordPress cron/session, or external services.  
**CHANGE RISKS:** preserve event order, persisted status/meta/table semantics, authorization and reverse callers documented in existing map.  
**REGRESSION CHECKS:** use `../REGRESSION-MAP.md` for shared hotspots; re-run the exact entry state plus reload/downstream consumer.  
**VERIFICATION STATUS:** VERIFIED against the uploaded plugin source snapshot for plugin-owned routing; external runtime remains UNVERIFIED.

## PDF pages and simple-editor viewer
- `photo-motor.js` accepts PDF (`accept="image/*,application/pdf,.pdf"`): pdf.js (bundled `assets/vendor/pdfjs/`, lazy-loaded, paths in `kmaePhotoMotor.pdf`) renders up to 6 pages → JPEG files named `albira-dokumentum-*`; the PDF itself is never uploaded. Such files skip the client quality verdict and the server photo autocorrect/analysis (`KMAE_Listings::handle_image_uploads` `$is_document`); then the normal `KMAE_Images::process_attachment` WebP conversion.
- Simple editor photo column (`vehicle-form-fields.php` `[data-kmae-photos]`): visible viewer (stage + arrows + thumbs + „Fotók megtekintése” lightbox) in `listing-field-guards.js`; the gallery manager + `#kmae-upload-preview` live hidden in `[data-kmae-photos-source]` and stay the single source (viewer clicks their ★ / remove buttons).

## KÁRTYAKÉPEK (böngészés v2, 2026-10-17)
- Fájlok: `includes/class-kmae-image-cards.php` (`KMAE_Image_Cards`: méretek + elemzés + köteg + admin), `includes/class-kmae-image-ambient.php` (`KMAE_Image_Ambient::analyze`, tiszta számolás, WP nélkül futtatható), `KMAE_Images::card_sources($id)` → `s/m/l` URL (hiányzónál a meglévő legjobb: nagyobb kártya → kmae_gallery → full → kisebb), `w/h` (mester), `ambient`, `tone`, `lqip`. Teszt: `tests/test-image-cards.php`.
- Méretek `kmae_card_s/m/l` = 480/800/1200 px, NEM `add_image_size` (az minden feltöltésre legyártaná); a `KMAE_Images::process_attachment_inner` a már betöltött szerkesztőből lánc-kicsinyít (egy tömörítés/fájl), az eredeti törlése ELŐTT, csak ha `post_parent > 0` (chat-képnek nincs). A `metadata['sizes']`-be kerülnek; a `responsive_attrs()` kiszűri őket a srcset-ből (régi böngészés/galéria változatlan). WebP minőség 90 (`WEBP_QUALITY`), a WP-méretek is 90-en (`wp_editor_set_quality` szűrő a metaadat-generálás alatt).
- Attachment meta: `_kmae_ambient` (#rrggbb, háttérnek igazított: L 0,14–0,38, S ≤ 0,75), `_kmae_tone` (warm|cool), `_kmae_lqip` (data URI egészében ≤ 600 karakter), `_kmae_cards_v` (kész-jelző = `KMAE_Image_Cards::VERSION`), `_kmae_cards_fail` (sikertelen próbák, 3 után kihagyva).
- Utólagos köteg: `kmae_cards_backfill` (óránként + 2 perces utánkövető egyszeri esemény, ha maradt), 20 kép/futás, 20 mp-es időkeret, `kmae_cards_lock` transient; admin: Albira → Kártyaképek (`kmae-cards`, kész/összes + gomb). Hatókör SQL: `KMAE_Image_Cards::sql()` (`_kmae_orientation` + post_parent>0 + webp/jpeg/png).
- Autó-doboz (2026-10-18): `assets/js/photo-subject.js` (`window.kmaePhotoSubject.detect`, COCO-SSD/TF.js CDN-ről, Apache-2.0, első képnél töltődik) → `photo-motor.js` `detectSubject` → rejtett `kmae_photo_subjects` (JSON, a `picked` sorrendjében) → `KMAE_Listings::handle_image_uploads` `$subjects[$i]` → `KMAE_Images::save_subject` → attachment meta `_kmae_subject` („x0,y0,x1,y1” 0–1, vagy `none` = nincs autó) → `card_sources['subject']` (index figyeli: `WATCHED_ATTACHMENT_META`). Régi fotók: Albira → Kártyaképek „Autók felismerése most” (admin böngészője; AJAX `kmae_subject_pending` / `kmae_subject_save`).
- Kliens: `photo-motor.js` `canvasToFileFitting` — 0,95 → 0,92 → 0,88 JPEG, a 1,8 MB fölötti képnél lejjebb (12 kép a post_max_size-ba férjen).

## BORÍTÓKÉP-STÚDIÓ — TÖRÖLVE (2026-10-17)
- Az API-s (Poof) kivágás teljesen megszűnt (Raul döntése): nincs `KMAE_Cover_Studio`, `cover-studio.js/.css`, `kmaeCoverGate`, `kmae:photos-changed`, `kmae_cutout_api_key`. Az élő adatot (`_kmae_cover_cutout(_src)` meta + kivágott csatolmány + opció) egyszer a `KMAE_Activator::remove_cover_studio()` takarítja (opció `kmae_cover_studio_removed`). Ne építsd vissza.
