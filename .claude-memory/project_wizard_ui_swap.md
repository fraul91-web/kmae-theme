---
name: project-wizard-ui-swap
description: "Albira wizard UI is being replaced step by step; brand step done 2026-09-03, established the step-scoped CSS convention"
metadata: 
  node_type: memory
  type: project
  originSessionId: ac48ec3f-ca9c-47ee-bb77-4ee0f8658fec
  modified: 2026-09-03T16:23:30.778Z
---

Ongoing work (started 2026-09-03): replacing the Albira listing-creation wizard's **UI only**, one step at a time, while keeping every bit of underlying behaviour (catalog, field names, change events, validation, save path). The classic / non-wizard create+edit form must stay visually and functionally unchanged — it shares `templates/partials/vehicle-form-fields.php` with the wizard, so the split is done in CSS/JS state, never by forking the markup.

**Convention established with the `vehicle_brand` step (first one done):**
- `listing-wizard.js` has one function, `setBrandStepState()`, called from `clearStage()` (removes) and `restoreStepView()` (applies). It toggles two switches: `is-step-vehicle-brand` on `#kmae-listing-wizard`, and `is-brand-wizard-ui` on the brand `.kmae-catalog-field`.
- All new CSS goes in one appended, clearly-headed section, every selector gated by one of those two switches, no `!important`. Because the field is shared, scoping is what protects the classic form.
- The class is deliberately **not** named `is-wizard-active` — the existing `activate()` already puts that name on `#kmae-listing-fields` with a different meaning.
- (2026-09-15: only `theme/kmae/assets/css/simple-mode/listing-create.css` exists now — the non-simple copy and the simple-mode switch are gone; `KMAE_CSS_BASE` is fixed to simple-mode.)

Raul's visual reference for the brand step had not been supplied yet as of 2026-09-03 — what exists is a deliberately plain, safe base UI meant to be restyled later. Still open at that date: on landscape phones the "Tovább" button sits below the fold.

See [[feedback_scope_stop_rule]] — this work is being done in tightly-scoped, explicitly-approved increments, and Raul chooses the decisions at each step.
