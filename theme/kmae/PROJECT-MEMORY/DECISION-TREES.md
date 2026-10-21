# DECISION TREES — THEME V2

**MAP-TYPE:** ON-DEMAND  
**Rule:** use only after `DIAGNOSTIC-ROUTER.md` selects a feature. `SOURCE CODE > PROJECT MEMORY`.

## T1 — Element is missing

```text
ELEMENT NOT VISIBLE
│
├─ Is it present in HTML/DOM?
│  ├─ NO
│  │  ├─ Theme template is supposed to emit it?
│  │  │  ├─ YES → exact template block / branch
│  │  │  └─ NO  → PLUGIN / EXTERNAL MARKUP BOUNDARY
│  │  └─ STOP CSS debugging
│  └─ YES
│     ├─ hidden attribute / display / visibility / opacity?
│     │  └─ YES → exact JS state handler or CSS selector
│     └─ NO → layout/cascade/position/z-index in active stylesheet
│
└─ Do not load backend data code unless missing DOM depends on missing data.
```

**Theme checkpoints:** header drawer IDs; profile dropdown panel; footer panel; plugin shortcode DOM.  
**STATUS:** VERIFIED routing logic against theme ownership.

## T2 — Wrong data/text/count is visible

```text
WRONG VALUE
│
├─ Is the wrong value already present before theme rendering?
│  ├─ YES → identify producer
│  │  ├─ plugin method/shortcode → STOP THEME DEBUGGING
│  │  └─ WordPress/request state → exact theme read/branch
│  └─ NO
│
├─ Is PHP/template output correct in HTML source?
│  ├─ NO  → template/helper path
│  └─ YES
│     ├─ Does theme.js modify this value?
│     │  ├─ YES → exact JS updater
│     │  └─ NO  → CSS cannot create a different business value;
│     │           inspect visibility/duplicate-element issue only
│
└─ Badge special case:
   KMAE_Database/Seller return wrong → plugin boundary immediately.
```

**Negative rule:** correct producer value + correct HTML means do not reopen plugin data generation.  
**STATUS:** VERIFIED.

## T3 — CSS does not apply

```text
STYLE MISSING
│
├─ Expected body feature class present?
│  ├─ NO  → detector → kmae_body_classes()
│  └─ YES
│
├─ Expected stylesheet handle loaded?
│  ├─ NO  → matching enqueue function + dependency/auth gate
│  └─ YES
│
├─ Exact selector matches DOM?
│  ├─ NO  → template/plugin markup ownership
│  └─ YES
│
├─ Declaration appears in computed styles?
│  ├─ NO  → parse/load/cache/syntax
│  └─ YES
│
└─ Overridden at current viewport?
   ├─ YES → later relevant rule / specificity / media query
   └─ NO  → layout property interaction
```

**STOP/SKIP:** once DOM/class is correct, do not inspect PHP data producer.  
**STATUS:** VERIFIED.

## T4 — Mobile good / desktop bad or desktop good / mobile bad

```text
VIEWPORT-SPECIFIC FAILURE
│
├─ Is HTML/class state materially the same at both widths?
│  ├─ YES → affected stylesheet + matching @media only
│  │        SKIP backend / plugin data / unrelated JS
│  └─ NO
│     ├─ difference caused by theme responsive markup visibility?
│     │  └─ header/footer shell + CSS
│     └─ difference caused by externally generated DOM?
│        └─ boundary owner
│
└─ Verify no auth/route difference was mistaken for viewport difference.
```

**STATUS:** VERIFIED.

## T5 — Button does not react

```text
BUTTON NO-OP
│
├─ Is this a known theme-controlled button?
│  ├─ drawer → theme.js::setDrawerOpen()
│  ├─ profile dropdown → openDropdown()/closeDropdown()
│  ├─ footer → setFooterOpen()
│  ├─ video trigger → openVideo() BUT markup owner unverified
│  └─ other
│
└─ Other feature button (wizard/chat/profile form/etc.)
   ├─ Theme has no matching handler/AJAX/REST/fetch path
   └─ confirm shortcode DOM exists → STOP THEME DEBUGGING → plugin source/map
```

**Critical negative route:** do not search the full theme for a plugin business-action handler after theme-wide verification established none exists.  
**STATUS:** VERIFIED.

## T6 — Wrong page wrapper / content formatting

```text
STRUCTURE / EXTRA PARAGRAPHS / BROKEN GRID
│
├─ WordPress Page using page.php?
│  ├─ YES → page.php classification 10–52
│  │        ↓
│  │      render branch 64–97
│  │        ├─ listing-create → raw do_shortcode()
│  │        ├─ profile/settings/login/register → raw do_shortcode()
│  │        ├─ hub/my-listings → the_content()
│  │        └─ ordinary → title/container + the_content()
│  └─ NO → front-page.php / index.php / external single/archive template
│
└─ If correct branch emits already-broken shortcode HTML → plugin boundary.
```

**STATUS:** VERIFIED.

## T7 — Navigation active state wrong

```text
ACTIVE NAV WRONG
│
├─ Desktop logged-in header?
│  └─ header.php::$kmae_nav_active + $kmae_own_listing + desktop nav array
│
├─ Logged-in drawer?
│  └─ functions.php::kmae_app_menu_groups()
│     NOTE: separate model from desktop header
│
├─ Logged-in bottom nav?
│  └─ theme.js exact pathname comparison
│
└─ Guest desktop nav?
   └─ header.php::$kmae_guest_nav_active
      + theme.js guest click/hash state
```

**STOP/SKIP:** do not assume a fix in one nav model automatically fixes the others.  
**STATUS:** VERIFIED.

## T8 — Badge / counter wrong

```text
COUNTER WRONG
│
├─ Header unseen?
│  └─ header.php → KMAE_Database::count_unread_conversations()
│
├─ Drawer unseen?
│  └─ kmae_app_menu_groups() → same plugin method
│
└─ Drawer active listings?
   └─ kmae_app_menu_groups() → KMAE_Seller::count_active_cycle_listings()
       ↓
Is plugin-returned integer wrong?
  ├─ YES → STOP THEME DEBUGGING
  └─ NO  → theme render/hide/badge class only
```

**STATUS:** VERIFIED theme consumers / UNVERIFIED plugin producer implementations.

## T9 — Logged-in home dashboard failure

```text
FRONT PAGE LOGGED IN
│
front-page.php auth branch
│
└─ app-home.php
   ├─ KMAE_Dashboard class missing
   │  └─ expected theme fallback message
   └─ class exists
      ├─ get_data(uid)
      └─ include KMAE_PLUGIN_DIR/templates/partials/dashboard-home.php
          ↓
      bad data/markup here → STOP THEME DEBUGGING
```

**STATUS:** VERIFIED theme bridge / UNVERIFIED plugin internals.

## T10 — Plugin/theme boundary decision

```text
SYMPTOM
│
├─ Theme owns route/body class/enqueue/template shell/visual CSS/global UI JS?
│  ├─ YES → stay in theme until that checkpoint is correct
│  └─ NO
│
├─ Theme only consumes plugin method / shortcode / included plugin template?
│  ├─ YES → validate input/output at boundary
│  │        ├─ input/output wrong at producer → PLUGIN
│  │        └─ producer output correct → THEME render/presentation
│  └─ NO
│
└─ Unknown owner?
   └─ search exact UI text/class/data-attribute first;
      never load both repositories wholesale.
```

**STATUS:** VERIFIED ownership pattern; plugin implementation targets remain UNVERIFIED unless explicitly named by theme.

## T11 — How-it-works video no-op

```text
VIDEO CLICK NO-OP
│
├─ #kmae-video-lightbox exists?
├─ #kmae-video-iframe exists?
└─ clicked element has [data-kmae-video-id]?
    │
    ├─ any NO → external/plugin/page markup producer; STOP theme.js debugging
    └─ all YES → theme.js::openVideo() / delegated click / console error
```

Theme project-wide verification found no producer for these hooks.  
**STATUS:** VERIFIED JS expectation / UNVERIFIED markup owner.

## T12 — Guest single-listing styling missing

```text
SINGLE LISTING VISUAL FAILURE
│
├─ body has kmae-single-listing-page?
│  └─ kmae_body_classes()
│
├─ visitor logged in?
│  ├─ YES → kmae_enqueue_app_shell() should enqueue single-listing.css
│  └─ NO  → function returns before single-listing.css enqueue
│           → this gate itself explains guest-only missing style
│
└─ Only if HTML content is wrong → external/plugin single-template boundary.
```

**STATUS:** VERIFIED.
