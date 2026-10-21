(function () {
  'use strict';

  var cfg = window.kmaeListingWizard || null;
  var num = window.kmaeListingFormNumbers || null;

  /* Ikonos kártyák az üzemanyag-választó lépéshez. */
  var FUEL_CARD_ICONS = (cfg && cfg.icons && cfg.icons.fuel) || {};

  /* Ikonok a mértékegységes mezők (teljesítmény, hengerűrtartalom) kártyás
     formázásához. */
  var UNIT_CARD_ICONS = (cfg && cfg.icons && cfg.icons.unit) || {};

  /* Ikonok a váltó lépéshez. */
  var TRANSMISSION_ICONS = (cfg && cfg.icons && cfg.icons.transmission) || {};

  /* Autófotók a karosszéria-választó kártyáihoz: háttérként ülnek a
     kártyán, a felirat rájuk kerül. */
  var BODY_CARD_PHOTOS = {
    sedan: 'https://albira.hu/wp-content/uploads/2026/09/sedan.webp',
    hatchback: 'https://albira.hu/wp-content/uploads/2026/09/csapott.webp',
    estate: 'https://albira.hu/wp-content/uploads/2026/09/kombi.webp',
    suv: 'https://albira.hu/wp-content/uploads/2026/09/suv.webp',
    coupe: 'https://albira.hu/wp-content/uploads/2026/09/coupe.webp',
    van: 'https://albira.hu/wp-content/uploads/2026/09/3-5es.webp',
    cabrio: 'https://albira.hu/wp-content/uploads/2026/09/caprio.webp',
    mpv: 'https://albira.hu/wp-content/uploads/2026/09/egyteru.webp',
    pickup: 'https://albira.hu/wp-content/uploads/2026/09/pickup.webp',
    offroad: 'https://albira.hu/wp-content/uploads/2026/09/terepjaro.webp',
    other: 'https://albira.hu/wp-content/uploads/2026/09/egyeb.webp'
  };

  /* Ikonok a műszaki érvényessége lépéshez. */
  var MOT_CARD_ICONS = (cfg && cfg.icons && cfg.icons.mot) || {};


  /* Ikonok az állapot-választó lépéshez. */
  var CONDITION_CARD_ICONS = (cfg && cfg.icons && cfg.icons.condition) || {};

  /**
   * Step registry.
   *
   * `target` points at markup that already exists in the create form; the engine
   * moves that element into the stage instead of rendering a second copy of the
   * field. `scope: "group"` steps are still coarse — they cover a whole form
   * section and get split into single questions in the next phase.
   */
  var STEPS = [
    {
      id: 'vehicle_brand',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_brand',
      prompt: 'Az autód márkája?',
      hint: 'Kezdd el beírni, és válassz a katalógusból.',
      assistant: 'Kezdjük az alapokkal!',
      assistantText: '',
      tip: 'Ha nincs a listán, add meg kézzel a „Nincs a listán” gombbal.',
      success: 'Rendben, {value}.',
      clearOnBack: true
    },
    {
      id: 'vehicle_model',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_model',
      prompt: 'Melyik modell?',
      eyebrow: 'Modell kiválasztása',
      hint: 'Válaszd ki a pontos modellt a kiválasztott márkához tartozó listából.',
      assistant: 'Most jöhet a modell.',
      assistantText: '',
      tip: 'Ha nincs a listán, add meg kézzel a „Nincs a listán” gombbal.',
      success: 'Megvan: {value}.',
      resetOnChangeOf: 'vehicle_brand'
    },
    {
      id: 'vehicle_year',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_year',
      inputType: 'year',
      prompt: 'Évjárat?',
      hint: 'Add meg a gyártási évet, például 2018.',
      assistant: 'Évjárat következik.',
      assistantText: 'Az évjárat az egyik legfontosabb adat a vevők számára.',
      tip: 'Az évjáratot a forgalmi engedélyben találod.',
      success: '{value} — rendben.'
    },
    {
      id: 'vehicle_transmission',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_transmission',
      prompt: 'Váltó?',
      sectionIntro: 'Nézzük az autó további adatait.',
      hint: '',
      tip: 'Ha nem vagy biztos benne, a forgalmi engedélyben is szerepel.',
      success: 'Váltó: {value}.',
      inputType: 'cards',
      autoAdvance: true,
      cardOptions: [
        { value: 'manual', label: 'Manuális', subtitle: 'Kézi váltó', icon: TRANSMISSION_ICONS.manual, iconStyle: 'circle' },
        { value: 'automatic', label: 'Automata', subtitle: 'Automata váltó', icon: TRANSMISSION_ICONS.automatic, iconStyle: 'circle' }
      ]
    },
    {
      id: 'vehicle_power_hp',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_power_hp',
      prompt: 'Teljesítmény?',
      hint: 'A teljesítményt lóerőben add meg.',
      tip: 'A motor teljesítményét lóerőben (LE) add meg.',
      success: '{value} LE — rendben.',
      inputType: 'unit',
      unit: 'LE',
      unitLabel: 'Teljesítmény',
      unitIcon: UNIT_CARD_ICONS.power,
      unitMax: 400
    },
    {
      id: 'vehicle_engine_cc',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_engine_cc',
      prompt: 'Hány cm³ a motor?',
      hint: 'A hengerűrtartalmat köbcentiméterben add meg.',
      tip: 'A cm³-értéket a forgalmi engedély P.1 mezőjében találod.',
      unitLabel: 'Hengerűrtartalom',
      unitIcon: UNIT_CARD_ICONS.engineCc,
      unitMax: 3000,
      success: '{value} cm³ — rendben.',
      inputType: 'unit',
      unit: 'cm³'
    },
    {
      id: 'vehicle_fuel',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_fuel',
      prompt: 'Üzemanyag?',
      hint: 'Válaszd ki a pontos típust.',
      tip: 'A forgalmi engedély P.3 mezőjében találod az üzemanyag típusát.',
      success: 'Üzemanyag: {value}.',
      inputType: 'cards',
      autoAdvance: true,
      cardOptions: [
        { value: 'benzin', label: 'Benzin', icon: FUEL_CARD_ICONS.benzin },
        { value: 'diesel', label: 'Dízel', icon: FUEL_CARD_ICONS.diesel },
        { value: 'hybrid', label: 'Hibrid', icon: FUEL_CARD_ICONS.hybrid },
        { value: 'electric', label: 'Elektromos', icon: FUEL_CARD_ICONS.electric },
        { value: 'lpg', label: 'LPG / Gáz', icon: FUEL_CARD_ICONS.lpg },
        { value: 'other', label: 'Egyéb', icon: FUEL_CARD_ICONS.other }
      ]
    },
    {
      id: 'vehicle_mot_has',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_mot_has',
      prompt: 'Van rajta érvényes műszaki?',
      hint: '',
      tip: 'A műszaki érvényességét a forgalmi engedélyben találod.',
      success: 'Műszaki: {value}.',
      inputType: 'cards',
      autoAdvance: true,
      cardOptions: [
        { value: 'yes', label: 'Van', subtitle: 'Az autón van érvényes műszaki vizsga.', icon: MOT_CARD_ICONS.yes, iconStyle: 'circle' },
        { value: 'no', label: 'Nincs', subtitle: 'Az autón nincs érvényes műszaki vizsga, vagy már lejárt.', icon: MOT_CARD_ICONS.no, iconStyle: 'circle' }
      ]
    },
    {
      id: 'vehicle_mot_date',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_mot_year',
      prompt: 'Meddig érvényes a műszaki?',
      hint: 'Válaszd ki az évet és a hónapot.',
      tip: 'A pontos dátumot a forgalmi engedélyben találod.',
      success: 'Rendben, {value}.',
      requires: { field: 'kmae_vehicle_mot_has', value: 'yes' }
    },
    {
      id: 'vehicle_mileage',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_mileage',
      prompt: 'Km állás?',
      hint: 'Add meg a műszerfalon látható aktuális kilométeróra-állást.',
      tip: 'A műszerfalon látható aktuális kilométeróra-állást add meg.',
      success: '{value} km — rendben.',
      inputType: 'mileage',
      unit: 'km'
    },
    {
      id: 'vehicle_body',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_vehicle_body',
      prompt: 'Karosszéria?',
      hint: '',
      tip: 'Válaszd a legközelebbi kivitelt — a vevők így találnak rád.',
      success: 'Kivitel: {value}.',
      inputType: 'cards',
      autoAdvance: true,
      cardOptions: [
        { value: 'sedan', label: 'Szedán', photo: BODY_CARD_PHOTOS.sedan },
        { value: 'estate', label: 'Kombi', photo: BODY_CARD_PHOTOS.estate },
        { value: 'hatchback', label: 'Ferdehátú', photo: BODY_CARD_PHOTOS.hatchback },
        { value: 'suv', label: 'SUV', photo: BODY_CARD_PHOTOS.suv },
        { value: 'coupe', label: 'Kupé', photo: BODY_CARD_PHOTOS.coupe },
        { value: 'van', label: 'Kisbusz/Van', photo: BODY_CARD_PHOTOS.van },
        { value: 'cabrio', label: 'Cabrio', photo: BODY_CARD_PHOTOS.cabrio },
        { value: 'mpv', label: 'Egyterű', photo: BODY_CARD_PHOTOS.mpv },
        { value: 'pickup', label: 'Pickup', photo: BODY_CARD_PHOTOS.pickup },
        { value: 'offroad', label: 'Terepjáró', photo: BODY_CARD_PHOTOS.offroad },
        { value: 'other', label: 'Egyéb', photo: BODY_CARD_PHOTOS.other }
      ]
    },
    {
      id: 'vehicle_condition',
      phase: 'auto',
      scope: 'field',
      target: '#kmae_condition',
      prompt: 'Milyen állapotban van?',
      hint: '',
      tip: 'Őszinte válasz kevesebb kellemetlen meglepetést okoz később.',
      success: 'Állapot: {value}.',
      inputType: 'cards',
      autoAdvance: true,
      cardOptions: [
        { value: 'excellent', label: 'Kiváló', subtitle: 'Megkímélt, hibátlan állapotban', icon: CONDITION_CARD_ICONS.excellent },
        { value: 'good', label: 'Jó', subtitle: 'Normál használatból eredő nyomokkal', icon: CONDITION_CARD_ICONS.good },
        { value: 'fair', label: 'Átlagos', subtitle: 'Használható állapot, kisebb hibákkal', icon: CONDITION_CARD_ICONS.fair },
        { value: 'project', label: 'Javítandó', subtitle: 'Műszaki hibával, felújításra szorul', icon: CONDITION_CARD_ICONS.project, variant: 'warning' }
      ]
    },
    {
      /* Legfeljebb három kiemelés az autóról. Nem kötelező — aki nem akar
         választani, továbbléphet. A kártyák az űrlap jelölőnégyzeteit
         kapcsolgatják, tehát a mentés a szokásos módon történik. */
      id: 'vehicle_traits',
      phase: 'auto',
      scope: 'field',
      target: '#kmae-vehicle-traits',
      prompt: 'Mi jellemzi leginkább az autót?',
      hint: 'Legfeljebb hármat válassz — amelyik leginkább illik rá.',
      assistant: 'Ez segít kitűnni.',
      assistantText: 'A vevők ezekre a kiemelésekre kapják fel a fejüket a listában.',
      tip: 'Nem kötelező — ha egyik sem illik, nyugodtan lépj tovább.',
      success: 'Jellemzők rögzítve.',
      inputType: 'trait-cards',
      optional: true,
      skipLabel: 'Most kihagyom'
    },
    {
      /* Nincs többé választás „megírom én" és „az Albira megírja" között: az
         autó adatai után rögtön a hirdetés szövege jön, és azt az eladó írja.
         A segítség később, magánál a szövegmezőnél fog megjelenni. */
      id: 'listing_content',
      phase: 'listing',
      scope: 'group',
      target: '#kmae-section-ad-body',
      prompt: 'Mutasd be az autót a vevőknek.',
      hint: 'Adj meg címet, leírást és a megtekintés helyét.',
      assistant: 'Itt a hirdetés szövege.',
      assistantText: 'Az őszinte, részletes leírás komolyabb érdeklődőket hoz.',
      tip: 'A címet a megadott adatokból már kitöltöttük — szabadon átírhatod.',
      success: 'A hirdetés tartalma kész.'
    },
    {
      id: 'extras',
      phase: 'extras',
      scope: 'group',
      target: '#kmae-section-trust-body',
      prompt: 'Tennél még valamit, hogy vonzóbb legyen a hirdetésed?',
      hint: 'Válaszd ki, amit vállalni tudsz — vagy hagyd ki.',
      assistant: 'Ez a rész teljesen opcionális.',
      assistantText: 'A vállalásokkal több bizalmat adhatsz a vevőknek, de egyik sem kötelező.',
      tip: 'Bármikor tovább mehetsz a „Most kihagyom” gombbal.',
      success: 'Rendben, mehetünk tovább.',
      optional: true,
      skipLabel: 'Most kihagyom'
    },
    {
      id: 'sale_mode',
      phase: 'sale',
      scope: 'group',
      target: '#kmae-section-sale-body',
      prompt: 'Hogyan szeretnéd értékesíteni az autót?',
      hint: 'Válassz a fix ár, az irányár és a „Ki mit adna érte” közül.',
      assistant: 'Ez az utolsó adat.',
      assistantText: 'Fix ár, irányár, vagy nem adsz meg árat és megvárod, ki mit adna érte.',
      tip: 'A „Ki mit adna érte” módban nem kell árat megadnod.',
      success: 'Az értékesítés módja rögzítve.'
    },
    {
      id: 'photos',
      phase: 'photos',
      scope: 'group',
      target: '#kmae-section-photos-body',
      prompt: 'Készítsd el a kötelező autófotókat.',
      hint: 'A fotózás a hirdetésfeladás utolsó lépése.',
      assistant: 'Jöhetnek a fotók.',
      assistantText: 'A kötelező fotókat feltöltheted kamerából vagy az eszközöd galériájából.',
      tip: 'Jó fényben, tiszta autóval készítsd vagy válaszd ki a képeket.',
      success: 'A fotók készen vannak.',
      /* Itt már nincs következő kérdés — a gomb ezt mondja ki. */
      nextLabel: 'Hirdetés feladása',
    },
    {
      id: 'finish',
      phase: 'done',
      scope: 'group',
      target: '#kmae-section-finish-body',
      prompt: 'Minden készen áll — ellenőrizd, és mentsd a hirdetést.',
      hint: '',
      assistant: 'Utolsó ellenőrzés.',
      assistantText: 'Nézd át az adatokat, majd mentsd a hirdetést.',
      tip: '',
      success: ''
    }
  ];

  var els = {};
  var state = {
    device: '',
    index: -1,
    completed: [],
    homes: {},
    ui: null,
    draftId: 0,
    draftSaving: false,
    draftDirty: false,
    restoring: false,
    submitting: false,
    prefilled: {},
    scanned: false,
    /* Rendszám / alvázszám / forgalmi szám a beolvasásból — csak a szerződéshez,
       sehol nem jelenik meg (rejtett mezőben megy a mentéssel). */
    vehicleIds: null
  };

  function purgeLegacyDraft() {
    try {
      for (var i = localStorage.length - 1; i >= 0; i--) {
        var lsKey = localStorage.key(i);
        if (lsKey && lsKey.indexOf('kmaeListingWizardDraft:') === 0) {
          localStorage.removeItem(lsKey);
        }
      }
    } catch (err) {
      /* ignore quota errors */
    }
  }

  /* ---------------------------------------------------------------- */
  /* Félbehagyott hirdetés — automatikus mentés és folytatás          */
  /* ---------------------------------------------------------------- */

  var DRAFT_SKIP = ['action', 'kmae_action', 'kmae_listing_id', 'kmae_redirect', 'kmae_create_device', 'kmae_draft_id', '_wp_http_referer'];
  var draftTimer = null;

  function draftFieldType(el) {
    return String(el.type || '').toLowerCase();
  }

  function isDraftField(el) {
    if (!el || !el.name || DRAFT_SKIP.indexOf(el.name) !== -1 || /nonce/i.test(el.name)) {
      return false;
    }
    var type = draftFieldType(el);
    return ['file', 'submit', 'button', 'reset', 'hidden', 'image'].indexOf(type) === -1;
  }

  function isCatalogCustom(id) {
    var input = byId(id);
    var root = input ? input.closest('.kmae-catalog-field') : null;
    return !!(root && root.classList.contains('is-custom'));
  }

  function serializeDraft() {
    var values = {};
    var checks = {};

    Array.prototype.forEach.call(els.form.elements, function (el) {
      if (!isDraftField(el)) {
        return;
      }
      var type = draftFieldType(el);
      if (type === 'checkbox' || type === 'radio') {
        checks[el.name] = checks[el.name] || [];
        if (el.checked) {
          checks[el.name].push(el.value);
        }
        return;
      }
      var value = el.dataset.kmaeFormatted === '1'
        ? (el.dataset.kmaeRawNumber || String(el.value || '').replace(/\D/g, ''))
        : String(el.value || '');
      values[el.name] = values[el.name] || [];
      values[el.name].push(value);
    });

    var step = stepByIndex(state.index);
    var title = byId('kmae_title');

    return {
      v: 1,
      /* Egyszerű szerkesztésnél nincs aktív lépés: a wizard pozíciója megmarad. */
      step: step ? step.id : (state.resumeStep || ''),
      completed: state.completed.slice(),
      /* A forgalmiból már betöltöttük az adatokat — folytatáskor a „Kitöltés a
         forgalmiból” gomb nem jelenik meg újra. */
      scanned: !!state.scanned,
      vehicleIds: state.vehicleIds || null,
      values: values,
      checks: checks,
      custom: {
        brand: isCatalogCustom('kmae_vehicle_brand'),
        model: isCatalogCustom('kmae_vehicle_model')
      },
      titleEdited: !!(title && title.dataset.userEdited === '1')
    };
  }

  /* Csak akkor mentünk, ha már van mit: legalább a márka meg van adva. */
  function draftWorthSaving() {
    var brand = byId('kmae_vehicle_brand');
    return !!(state.device && brand && String(brand.value || '').trim() !== '');
  }

  function draftTitle() {
    var title = byId('kmae_title');
    if (title && String(title.value || '').trim() !== '') {
      return title.value.trim();
    }
    return ['Eladó', readValue('kmae_vehicle_brand'), readValue('kmae_vehicle_model')].filter(Boolean).join(' ');
  }

  function draftRequestBody() {
    var body = new FormData();
    body.append('action', 'kmae_save_listing_draft');
    body.append('nonce', cfg.draftNonce || '');
    body.append('draft_id', state.draftId ? String(state.draftId) : '0');
    body.append('title', draftTitle());
    body.append('payload', JSON.stringify(serializeDraft()));
    return body;
  }

  function saveDraftNow() {
    if (draftTimer) {
      window.clearTimeout(draftTimer);
      draftTimer = null;
    }
    if (state.restoring || state.submitting || !cfg.ajaxUrl || !draftWorthSaving()) {
      return;
    }
    /* Egyszerre egy kérés megy; ha közben változott valami, utána újra mentünk. */
    if (state.draftSaving) {
      state.draftDirty = true;
      return;
    }
    state.draftSaving = true;
    state.draftDirty = false;

    fetch(cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: draftRequestBody() })
      .then(function (response) {
        return response.json();
      })
      .then(function (json) {
        if (json && json.success && json.data && json.data.draftId) {
          state.draftId = json.data.draftId;
          var hidden = byId('kmae_draft_id');
          if (hidden) {
            hidden.value = String(state.draftId);
          }
        }
      })
      .catch(function () {
        /* A következő változásnál újra próbáljuk. */
      })
      .then(function () {
        state.draftSaving = false;
        if (state.draftDirty) {
          scheduleDraftSave();
        }
      });
  }

  function scheduleDraftSave() {
    if (state.restoring || state.submitting) {
      return;
    }
    if (draftTimer) {
      window.clearTimeout(draftTimer);
    }
    draftTimer = window.setTimeout(saveDraftNow, 900);
  }

  /* Frissítés / oldal elhagyása: a böngésző a lap bezárása után is elküldi. */
  function flushDraftOnLeave() {
    if (state.restoring || state.submitting || !cfg.ajaxUrl || !draftWorthSaving()) {
      return;
    }
    /* Új piszkozatot csak rendes kéréssel hozunk létre, különben az azonosító
       nem érkezne vissza, és a következő mentés másodpéldányt csinálna. */
    if (!state.draftId) {
      return;
    }
    if (navigator.sendBeacon) {
      navigator.sendBeacon(cfg.ajaxUrl, draftRequestBody());
    }
  }

  function restoreDraftValues(payload) {
    var values = payload.values || {};
    var checks = payload.checks || {};
    var custom = payload.custom || {};
    var has = function (obj, key) {
      return Object.prototype.hasOwnProperty.call(obj, key);
    };

    /* Márka előbb, modell utána: a márka változása kiüríti a modellt. */
    [['kmae_vehicle_brand', 'brand'], ['kmae_vehicle_model', 'model']].forEach(function (pair) {
      var input = byId(pair[0]);
      if (!input || !has(values, pair[0])) {
        return;
      }
      input.value = values[pair[0]][0] || '';
      if (custom[pair[1]] && input.value) {
        input.dispatchEvent(new CustomEvent('kmae:catalog-force-custom', { bubbles: true }));
      }
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });

    var seen = {};
    Array.prototype.forEach.call(els.form.elements, function (el) {
      if (!isDraftField(el) || el.name === 'kmae_vehicle_brand' || el.name === 'kmae_vehicle_model') {
        return;
      }
      var type = draftFieldType(el);
      if (type === 'checkbox' || type === 'radio') {
        if (!has(checks, el.name)) {
          return;
        }
        var want = checks[el.name].indexOf(el.value) !== -1;
        if (el.checked !== want) {
          el.checked = want;
          el.dispatchEvent(new Event('change', { bubbles: true }));
        }
        return;
      }
      if (!has(values, el.name)) {
        return;
      }
      var position = seen[el.name] || 0;
      seen[el.name] = position + 1;
      var value = values[el.name][position];
      if (value === undefined || el.value === value) {
        return;
      }
      el.value = value;
      el.dispatchEvent(new Event('input', { bubbles: true }));
      el.dispatchEvent(new Event('change', { bubbles: true }));
    });

    var title = byId('kmae_title');
    if (title && payload.titleEdited) {
      title.dataset.userEdited = '1';
    }
  }

  function resumeDraft(draft, device) {
    var payload = draft.payload || {};
    state.draftId = draft.id;
    var hidden = byId('kmae_draft_id');
    if (hidden) {
      hidden.value = String(draft.id);
    }

    state.restoring = true;
    try {
      restoreDraftValues(payload);
    } finally {
      state.restoring = false;
    }

    activate(device, payload);
  }

  /**
   * Félbehagyott hirdetés egyszerű szerkesztése: a klasszikus űrlap látszik,
   * wizard nélkül. Az adatok visszatöltődnek, és a mentés ugyanúgy megy.
   */
  function startSimpleDraft(draft) {
    var payload = draft.payload || {};
    state.device = 'computer';
    state.draftId = draft.id;
    state.resumeStep = payload.step || '';
    state.completed = payload.completed || [];

    var hidden = byId('kmae_draft_id');
    if (hidden) {
      hidden.value = String(draft.id);
    }

    state.restoring = true;
    try {
      restoreDraftValues(payload);
    } finally {
      state.restoring = false;
    }

    els.form.addEventListener('submit', function () {
      if (draftTimer) {
        window.clearTimeout(draftTimer);
        draftTimer = null;
      }
      state.submitting = true;
    });
    els.form.addEventListener('input', scheduleDraftSave);
    els.form.addEventListener('change', scheduleDraftSave);
    window.addEventListener('pagehide', flushDraftOnLeave);
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') {
        flushDraftOnLeave();
      }
    });
  }

  function showDraftPrompt(draft, device) {
    var overlay = document.createElement('div');
    overlay.className = 'kmae-draft-prompt';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', 'kmae-draft-prompt-title');

    var card = document.createElement('div');
    card.className = 'kmae-draft-prompt__card';

    var line = document.createElement('span');
    line.className = 'kmae-draft-prompt__topline';
    line.setAttribute('aria-hidden', 'true');

    /* Kilépés az ablakból: a párbeszéd nélküle csapda lenne. */
    var dismiss = document.createElement('button');
    dismiss.type = 'button';
    dismiss.className = 'kmae-draft-prompt__close';
    dismiss.setAttribute('aria-label', 'Bezárás');
    dismiss.innerHTML = '<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false">'
      + '<path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"/></svg>';

    var eyebrow = document.createElement('p');
    eyebrow.className = 'kmae-draft-prompt__eyebrow';
    eyebrow.textContent = 'Félbehagyott hirdetés';

    var heading = document.createElement('h2');
    heading.className = 'kmae-draft-prompt__title';
    heading.id = 'kmae-draft-prompt-title';
    heading.textContent = 'Már van félbehagyott hirdetésed';

    var name = document.createElement('p');
    name.className = 'kmae-draft-prompt__name';
    name.textContent = draft.title || 'Félbehagyott hirdetés';

    var meta = document.createElement('p');
    meta.className = 'kmae-draft-prompt__meta';
    meta.textContent = draft.savedAt ? 'Utoljára mentve: ' + draft.savedAt : '';

    var lead = document.createElement('p');
    lead.className = 'kmae-draft-prompt__lead';
    lead.textContent = 'Folytatod ott, ahol abbahagytad, vagy új hirdetést kezdesz? Ha újat kezdesz, a félbehagyott megmarad a hirdetéseid között.';

    var actions = document.createElement('div');
    actions.className = 'kmae-draft-prompt__actions';

    var fresh = document.createElement('button');
    fresh.type = 'button';
    fresh.className = 'kmae-draft-prompt__btn kmae-draft-prompt__btn--new';
    fresh.textContent = 'Új hirdetést kezdek';

    var resume = document.createElement('button');
    resume.type = 'button';
    resume.className = 'kmae-draft-prompt__btn kmae-draft-prompt__btn--resume';
    resume.textContent = 'Folytatom';

    actions.appendChild(fresh);
    actions.appendChild(resume);
    card.appendChild(line);
    card.appendChild(dismiss);
    card.appendChild(eyebrow);
    card.appendChild(heading);
    card.appendChild(name);
    if (meta.textContent) {
      card.appendChild(meta);
    }
    card.appendChild(lead);
    card.appendChild(actions);
    overlay.appendChild(card);
    document.body.appendChild(overlay);
    document.body.classList.add('kmae-draft-prompt-open');

    function close() {
      document.body.classList.remove('kmae-draft-prompt-open');
      document.removeEventListener('keydown', onKey);
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }

    /* Bezárásnál nem indul varázsló, ezért visszalépünk az előző oldalra. */
    function leave() {
      close();
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = '/';
      }
    }

    function onKey(event) {
      if (event.key === 'Escape' || event.key === 'Esc') {
        leave();
      }
    }

    document.addEventListener('keydown', onKey);
    dismiss.addEventListener('click', leave);

    resume.addEventListener('click', function () {
      close();
      resumeDraft(draft, device);
    });

    fresh.addEventListener('click', function () {
      close();
      state.draftId = 0;
      activate(device);
    });

    resume.focus();
  }

  function byId(id) {
    return document.getElementById(id);
  }

  function stepByIndex(index) {
    return STEPS[index] || null;
  }

  function isStepActive(step) {
    if (step.devices && step.devices.indexOf(state.device) === -1) {
      return false;
    }

    // Feltételes lépés: csak akkor kérdezünk rá, ha egy korábbi válasz
    // megkívánja (pl. a műszaki dátuma csak "Igen" válasz után).
    if (step.requires) {
      var source = document.getElementsByName(step.requires.field)[0];
      if (!source || source.value !== step.requires.value) {
        return false;
      }
    }

    return true;
  }

  function nextActiveIndex(from) {
    for (var i = from; i < STEPS.length; i++) {
      if (isStepActive(STEPS[i])) {
        return i;
      }
    }
    return -1;
  }

  function prevActiveIndex(from) {
    for (var i = from; i >= 0; i--) {
      if (isStepActive(STEPS[i])) {
        return i;
      }
    }
    return -1;
  }

  function resolveTarget(step) {
    var el = document.querySelector(step.target);
    if (!el) {
      return null;
    }
    if (step.scope === 'field') {
      return el.closest('.kmae-field') || el;
    }
    return el;
  }

  function primaryControl(step) {
    if (step.scope === 'field') {
      return document.querySelector(step.target);
    }
    var host = resolveTarget(step);
    return host ? host.querySelector('input, select, textarea') : null;
  }

  function rawNumberValue(control) {
    return num ? num.rawNumberValue(control) : String(control && control.value ? control.value : '').replace(/\D/g, '');
  }

  function formatGroupedNumber(digits) {
    return num ? num.formatGroupedNumber(digits) : digits;
  }

  function syncFormattedValidity(control) {
    if (num) {
      num.syncFormattedValidity(control, cfg && cfg.i18n ? cfg.i18n.invalid : '');
    }
  }

  function enhanceFormattedNumber(control) {
    if (num) {
      num.enhance(control);
    }
  }

  function restoreFormattedNumber(control) {
    if (num) {
      num.restore(control);
    }
  }

  function normalizeFormattedNumbersForSubmit() {
    if (num && els.form) {
      num.normalizeForSubmit(els.form);
    }
  }

  function enhanceSalePriceFields() {
    if (num && els.form) {
      num.enhanceVisiblePriceField(els.form);
    }
  }

  /**
   * Értékesítés módja: választás után a kurzor a megjelenő mezőre ugrik.
   *
   * Minden módnál jelenik meg mező (fix ár; irányár + rejtett minimum;
   * „Ki mit adna érte" + rejtett minimum), ezért automatikusan nem lépünk
   * tovább — az átugraná a lényeget.
   */
  function bindSaleModeAdvance() {
    var radios = els.form.querySelectorAll('[data-kmae-price-mode]');

    Array.prototype.forEach.call(radios, function (radio) {
      if (radio.dataset.kmaeAdvanceBound === '1') {
        return;
      }
      radio.dataset.kmaeAdvanceBound = '1';

      radio.addEventListener('change', function () {
        if (!radio.checked) {
          return;
        }

        /* Az ár / rejtett minimum mezője a választásra jelenik meg — adjunk
           neki egy pillanatot, mielőtt ráállunk. A „Ki mit adna érte” módnál
           is itt maradunk, mert a rejtett minimum ott is megadható. */
        window.setTimeout(function () {
          var price = els.form.querySelector('#kmae-section-sale-body input[type="text"]:not([hidden]):not(:disabled), #kmae-section-sale-body input[type="number"]:not([hidden]):not(:disabled)');
          if (price && typeof price.focus === 'function') {
            price.focus({ preventScroll: true });
          }
        }, 220);
      });
    });
  }

  function readValue(name) {
    var el = els.form.querySelector('[name="' + name + '"]');
    if (!el) {
      return '';
    }
    if (el.dataset.kmaeFormatted === '1') {
      return rawNumberValue(el);
    }
    if (el.tagName === 'SELECT') {
      return el.options[el.selectedIndex] ? el.options[el.selectedIndex].text : '';
    }
    return String(el.value || '').trim();
  }

  function stepValueLabel(step) {
    var control = primaryControl(step);
    if (!control) {
      return '';
    }
    if (step.scope !== 'field') {
      return '';
    }
    if (step.inputType === 'owner-cards') {
      if (isOwnersUnknown(control)) {
        return 'Nem tudom';
      }
      var owners = parseInt(String(control.value || ''), 10);
      if (owners >= 4) {
        return '4+';
      }
      return owners ? owners + '. tulajdonos' : '';
    }
    if (control.tagName === 'SELECT') {
      var cards = control.parentNode ? control.parentNode.querySelector('.kmae-wizard-choices') : null;
      if (cards) {
        var active = cards.querySelector('.kmae-wizard-choice.is-selected');
        if (active) {
          return active.textContent.trim();
        }
      }
      return control.options[control.selectedIndex] ? control.options[control.selectedIndex].text : '';
    }
    if (step.inputType === 'unit' && step.unit) {
      var raw = rawNumberValue(control);
      return raw ? formatGroupedNumber(raw) + ' ' + step.unit : '';
    }
    if (control.dataset.kmaeFormatted === '1') {
      var formatted = rawNumberValue(control);
      return formatted ? formatGroupedNumber(formatted) : '';
    }
    return String(control.value || '').trim();
  }

  function cleanupStepUI() {
    if (!state.ui) {
      return;
    }
    if (state.ui.choices && state.ui.choices.parentNode) {
      state.ui.choices.parentNode.removeChild(state.ui.choices);
    }
    if (state.ui.unitWrap && state.ui.unitWrap.parentNode) {
      /* Kártyás változatnál a mező a kártyán belül van: a legkülső elem
         mellé kell visszatenni, különben az insertBefore hibát dob. */
      var unitRoot = state.ui.unitCard || state.ui.unitWrap;
      var field = unitRoot.closest('.kmae-field');
      if (field && state.ui.control) {
        field.insertBefore(state.ui.control, unitRoot);
      }
      if (unitRoot.parentNode) {
        unitRoot.parentNode.removeChild(unitRoot);
      }
    }
    if (state.ui.traitWrap) {
      /* A kártyák eltűnnek, az eredeti jelölőnégyzetek visszakapcsolnak — így
         a mező szerkesztés módban is használható marad. */
      if (state.ui.traitWrap.parentNode) {
        state.ui.traitWrap.parentNode.removeChild(state.ui.traitWrap);
      }
      if (state.ui.traitOptions) {
        state.ui.traitOptions.hidden = false;
      }
      if (state.ui.traitLegend) {
        state.ui.traitLegend.hidden = false;
      }
    }
    if (state.ui.brandCard && state.ui.brandCard.parentNode) {
      state.ui.brandCard.parentNode.removeChild(state.ui.brandCard);
    }
    if (state.ui.motSummary && state.ui.motSummary.parentNode) {
      state.ui.motSummary.parentNode.removeChild(state.ui.motSummary);
    }
    if (state.ui.noteWrap && state.ui.noteWrap.parentNode) {
      state.ui.noteWrap.parentNode.removeChild(state.ui.noteWrap);
    }
    if (state.ui.yearWrap) {
      if (state.ui.control && state.ui.yearSync) {
        state.ui.control.removeEventListener('input', state.ui.yearSync);
      }
      if (state.ui.yearWrap.parentNode) {
        state.ui.yearWrap.parentNode.removeChild(state.ui.yearWrap);
      }
    }
    if (state.ui.mileageWrap) {
      if (state.ui.control && state.ui.mileageSync) {
        state.ui.control.removeEventListener('input', state.ui.mileageSync);
      }
      if (state.ui.mileageWrap.parentNode) {
        state.ui.mileageWrap.parentNode.removeChild(state.ui.mileageWrap);
      }
    }
    if (state.ui.clearBtn) {
      if (state.ui.clearControl && state.ui.clearSync) {
        state.ui.clearControl.removeEventListener('input', state.ui.clearSync);
      }
      if (state.ui.clearHost) {
        state.ui.clearHost.classList.remove('has-wizard-clear');
      }
      if (state.ui.clearBtn.parentNode) {
        state.ui.clearBtn.parentNode.removeChild(state.ui.clearBtn);
      }
    }
    if (state.ui.control) {
      restoreFormattedNumber(state.ui.control);
      state.ui.control.hidden = false;
      state.ui.control.classList.remove('is-wizard-enhanced');
    }
    if (state.ui.fieldHint && state.ui.fieldHint.hidden !== undefined) {
      state.ui.fieldHint.hidden = state.ui.fieldHintWasHidden;
    }
    state.ui = null;
  }

  function cardOptionsForStep(step, control) {
    if (step.cardOptions && step.cardOptions.length) {
      return step.cardOptions.slice();
    }
    if (!control || control.tagName !== 'SELECT') {
      return [];
    }
    var options = [];
    Array.prototype.forEach.call(control.options, function (opt) {
      if (opt.value) {
        options.push({ value: opt.value, label: opt.textContent.trim() });
      }
    });
    return options;
  }

  /**
   * Jellemző-kártyák. A forrás maga az űrlap: minden jelölőnégyzethez egy
   * kártya tartozik, és a kattintás a jelölőnégyzetet kapcsolja — így a mentés
   * változatlan marad, és szkript nélkül is működőképes az űrlap.
   * Legfeljebb `data-kmae-max` darab jelölhető be egyszerre.
   */
  function buildTraitCards(step, ui) {
    var field = byId('kmae-vehicle-traits');
    if (!field) {
      return;
    }

    var boxes = Array.prototype.slice.call(field.querySelectorAll('input[type="checkbox"]'));
    var max = parseInt(field.dataset.kmaeMax, 10) || 3;
    if (!boxes.length) {
      return;
    }

    var options = field.querySelector('.kmae-traits-options');
    var legend = field.querySelector('.kmae-field__legend');
    if (legend) {
      legend.hidden = true;
    }

    var wrap = document.createElement('div');
    wrap.className = 'kmae-wizard-choices kmae-wizard-choices--icons kmae-wizard-choices--traits';
    wrap.setAttribute('role', 'listbox');
    wrap.setAttribute('aria-multiselectable', 'true');
    wrap.setAttribute('aria-label', step.prompt || '');

    var cards = [];

    function selectedCount() {
      return boxes.filter(function (box) { return box.checked; }).length;
    }

    /* Ha megvan a három, a többi kártya elhalványul — de a bejelöltek mindig
       visszakattinthatók. */
    function syncCards() {
      var full = selectedCount() >= max;
      cards.forEach(function (card, index) {
        var on = boxes[index].checked;
        card.classList.toggle('is-selected', on);
        card.setAttribute('aria-selected', on ? 'true' : 'false');
        card.classList.toggle('is-muted', full && !on);
      });
    }

    boxes.forEach(function (box, index) {
      var source = box.closest('.kmae-trait-option');
      var iconHtml = source ? source.querySelector('.kmae-trait-option__icon').innerHTML : '';
      var text = source ? source.querySelector('.kmae-trait-option__label').textContent : box.value;

      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'kmae-wizard-choice kmae-wizard-choice--icon';
      btn.setAttribute('role', 'option');
      btn.setAttribute('data-value', box.value);

      var glassLight = document.createElement('span');
      glassLight.className = 'kmae-wizard-choice__glass';
      glassLight.setAttribute('aria-hidden', 'true');
      btn.appendChild(glassLight);

      var icon = document.createElement('span');
      icon.className = 'kmae-wizard-choice__icon';
      icon.innerHTML = iconHtml;
      icon.setAttribute('aria-hidden', 'true');
      btn.appendChild(icon);

      var badge = document.createElement('span');
      badge.className = 'kmae-wizard-choice__badge';
      badge.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
      badge.setAttribute('aria-hidden', 'true');
      btn.appendChild(badge);

      var label = document.createElement('span');
      label.className = 'kmae-wizard-choice__label';
      label.textContent = text;
      btn.appendChild(label);

      btn.addEventListener('click', function () {
        if (!box.checked && selectedCount() >= max) {
          showFeedback('Legfeljebb ' + max + ' jellemzőt választhatsz.', 'error');
          return;
        }
        box.checked = !box.checked;
        box.dispatchEvent(new Event('change', { bubbles: true }));
        els.feedback.hidden = true;
        syncCards();
      });

      cards.push(btn);
      wrap.appendChild(btn);
    });

    if (options) {
      options.hidden = true;
    }
    field.appendChild(wrap);
    syncCards();

    state.ui = Object.assign(ui || {}, { traitWrap: wrap, traitOptions: options, traitLegend: legend });
  }

  function buildChoiceCards(step, control, ui) {
    var field = control.closest('.kmae-field');
    if (!field) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'kmae-wizard-choices';
    wrap.setAttribute('role', 'listbox');
    wrap.setAttribute('aria-label', step.prompt || '');

    var options = cardOptionsForStep(step, control);
    if (options.some(function (opt) { return opt.icon || opt.photo; })) {
      wrap.classList.add('kmae-wizard-choices--icons');
      /* Fotós kártyából hatnál több van: négy oszlop, hogy a rács ne nyúljon szét.
         Ikonos lépésnél öt válaszig egy sor, e fölött három oszlop. */
      var photoCount = options.filter(function (opt) { return opt.photo; }).length;
      wrap.style.setProperty('--kmae-choice-cols', String(photoCount > 6 ? 4 : (options.length <= 5 ? options.length : 3)));
    }

    options.forEach(function (opt) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'kmae-wizard-choice';
      btn.setAttribute('role', 'option');
      btn.setAttribute('data-value', opt.value);

      var glassLight = document.createElement('span');
      glassLight.className = 'kmae-wizard-choice__glass';
      glassLight.setAttribute('aria-hidden', 'true');
      btn.appendChild(glassLight);

      if (opt.photo) {
        /* Fotós kártya. A nagyítás a kép KÖRÜLI kereten van, nem magán a
           képen — így a kép rétegét nem kell újrarajzolni. */
        btn.classList.add('kmae-wizard-choice--photo');

        var photoFrame = document.createElement('span');
        photoFrame.className = 'kmae-wizard-choice__frame';

        var photoImg = document.createElement('img');
        photoImg.className = 'kmae-wizard-choice__photo';
        photoImg.src = opt.photo;
        photoImg.alt = '';
        photoImg.setAttribute('aria-hidden', 'true');
        photoImg.setAttribute('decoding', 'async');
        photoFrame.appendChild(photoImg);

        var photoBadge = document.createElement('span');
        photoBadge.className = 'kmae-wizard-choice__badge';
        photoBadge.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        photoBadge.setAttribute('aria-hidden', 'true');
        photoFrame.appendChild(photoBadge);

        var photoLabel = document.createElement('span');
        photoLabel.className = 'kmae-wizard-choice__label';
        photoLabel.textContent = opt.label;

        btn.appendChild(photoFrame);
        btn.appendChild(photoLabel);
      } else if (opt.icon) {
        btn.classList.add('kmae-wizard-choice--icon');
        var icon = document.createElement('span');
        icon.className = 'kmae-wizard-choice__icon';
        icon.innerHTML = opt.icon;
        icon.setAttribute('aria-hidden', 'true');
        var badge = document.createElement('span');
        badge.className = 'kmae-wizard-choice__badge';
        badge.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        badge.setAttribute('aria-hidden', 'true');
        var label = document.createElement('span');
        label.className = 'kmae-wizard-choice__label';
        label.textContent = opt.label;
        btn.appendChild(icon);
        btn.appendChild(badge);
        btn.appendChild(label);
        if (opt.subtitle) {
          btn.classList.add(opt.iconStyle === 'circle' ? 'kmae-wizard-choice--icon-circle' : 'kmae-wizard-choice--detail');
          var subtitle = document.createElement('span');
          subtitle.className = 'kmae-wizard-choice__subtitle';
          subtitle.textContent = opt.subtitle;
          btn.appendChild(subtitle);
        }
        if (opt.variant === 'warning') {
          btn.classList.add('kmae-wizard-choice--warning');
        }
      } else {
        btn.textContent = opt.label;
      }
      if (control.value === opt.value) {
        btn.classList.add('is-selected');
        btn.setAttribute('aria-selected', 'true');
      }
      btn.addEventListener('click', function () {
        control.value = opt.value;
        wrap.querySelectorAll('.kmae-wizard-choice').forEach(function (node) {
          var on = node === btn;
          node.classList.toggle('is-selected', on);
          node.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        control.dispatchEvent(new Event('change', { bubbles: true }));
        showNote(opt.note || null);

        /* Ahol pontosító kérdés jön, ott nem lépünk magától tovább — hagyni
           kell időt a beírásra. */
        if (step.autoAdvance && !opt.note) {
          window.setTimeout(advance, 220);
        }
      });
      wrap.appendChild(btn);
    });

    control.hidden = true;
    control.classList.add('is-wizard-enhanced');
    field.appendChild(wrap);

    /* Pontosító mező a kártyák alatt (pl. „Mit kell javítani?"). A beírt szöveg
       az űrlap rejtett mezőjébe kerül, az az adat egyetlen forrása. */
    var noteWrap = document.createElement('div');
    noteWrap.className = 'kmae-wizard-note';
    noteWrap.hidden = true;

    var noteLabel = document.createElement('label');
    noteLabel.className = 'kmae-wizard-note__label';
    noteWrap.appendChild(noteLabel);

    var noteInput = document.createElement('input');
    noteInput.type = 'text';
    noteInput.className = 'kmae-wizard-note__input';
    noteInput.setAttribute('maxlength', '160');
    noteWrap.appendChild(noteInput);

    var noteTarget = null;

    function showNote(note) {
      if (!note) {
        noteWrap.hidden = true;
        noteTarget = null;
        return;
      }

      noteTarget = document.querySelector(note.target);
      if (!noteTarget) {
        noteWrap.hidden = true;
        return;
      }

      noteLabel.textContent = note.prompt;
      noteInput.placeholder = note.placeholder || '';
      noteInput.value = noteTarget.value || '';
      noteTarget.hidden = true;
      noteWrap.hidden = false;
      noteInput.focus();
    }

    noteInput.addEventListener('input', function () {
      if (noteTarget) {
        noteTarget.value = noteInput.value;
      }
    });

    field.appendChild(noteWrap);

    /* Visszalépéskor is látszódjon, ha korábban ezt választottad. */
    options.forEach(function (opt) {
      if (opt.note && control.value === opt.value) {
        showNote(opt.note);
      }
    });

    state.ui = Object.assign(ui || {}, { control: control, choices: wrap, noteWrap: noteWrap });
  }

  /* Csak a kifejezetten megjelölt „Nem tudom" számít ismeretlennek (0). Az üres
     mező azt jelenti, hogy a felhasználó még nem választott — ilyenkor egyik
     kártya sem lehet kijelölve. */
  function isOwnersUnknown(control) {
    return String(control.value || '').trim() === '0';
  }

  function ownerCardOptions() {
    return [
      { label: 'Első tulajdonos', value: '1', number: '01' },
      { label: 'Második tulajdonos', value: '2', number: '02' },
      { label: 'Harmadik tulajdonos', value: '3', number: '03' },
      { label: 'Negyedik vagy több', value: '4', number: '04+' },
      { label: 'Nem tudom', value: 'unknown', number: '?' }
    ];
  }

  function buildOwnerCards(step, control, ui) {
    var field = control.closest('.kmae-field');
    if (!field) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'kmae-wizard-choices kmae-wizard-choices--owners';
    wrap.setAttribute('role', 'listbox');

    var current = parseInt(String(control.value || ''), 10);
    var unknown = isOwnersUnknown(control);

    ownerCardOptions().forEach(function (opt) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'kmae-wizard-choice kmae-wizard-choice--owner';
      btn.setAttribute('role', 'option');

      var glass = document.createElement('span');
      glass.className = 'kmae-wizard-choice__glass';
      glass.setAttribute('aria-hidden', 'true');
      btn.appendChild(glass);

      var number = document.createElement('span');
      number.className = 'kmae-wizard-choice__number';
      number.textContent = opt.number;
      number.setAttribute('aria-hidden', 'true');
      btn.appendChild(number);

      var label = document.createElement('span');
      label.className = 'kmae-wizard-choice__label';
      label.textContent = opt.label;
      btn.appendChild(label);

      var badge = document.createElement('span');
      badge.className = 'kmae-wizard-choice__badge';
      badge.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
      badge.setAttribute('aria-hidden', 'true');
      btn.appendChild(badge);

      var selected;
      if (opt.value === 'unknown') {
        selected = unknown;
      } else if (opt.value === '4') {
        selected = !unknown && current >= 4;
      } else {
        selected = !unknown && String(current) === opt.value;
      }

      if (selected) {
        btn.classList.add('is-selected');
        btn.setAttribute('aria-selected', 'true');
      }
      btn.addEventListener('click', function () {
        control.value = opt.value === 'unknown' ? '0' : opt.value;
        wrap.querySelectorAll('.kmae-wizard-choice').forEach(function (node) {
          var on = node === btn;
          node.classList.toggle('is-selected', on);
          node.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        control.dispatchEvent(new Event('change', { bubbles: true }));
        if (step.autoAdvance) {
          window.setTimeout(advance, 220);
        }
      });
      wrap.appendChild(btn);
    });

    control.hidden = true;
    control.classList.add('is-wizard-enhanced');
    field.appendChild(wrap);
    state.ui = Object.assign(ui || {}, { control: control, choices: wrap });
  }

  /**
   * Évjárat lépés saját felülete: a mező alatt teljesülő szabályok, gyakori
   * évek gyorsválasztóként, forgalmi engedély segédlet és élő példa.
   * Az adat egyetlen forrása végig maga a mező — a gombok csak beírják.
   */
  /**
   * A katalógusban nagybetűs a márkanév (VOLKSWAGEN). Megjelenítéshez
   * emberibb formára hozzuk, de a rövid és magánhangzó nélküli neveket
   * (BMW, MG, KIA, DFSK) meghagyjuk nagybetűsnek.
   */
  function brandDisplayName(name) {
    return String(name || '').replace(/[^\s\-()\/]+/g, function (word) {
      if (word.length <= 3 || !/[aeiouáéíóöőúüű]/i.test(word)) {
        return word;
      }
      return word.charAt(0) + word.slice(1).toLowerCase();
    });
  }

  /**
   * Modell lépés: a kiválasztott márka emblémája és neve a kérdés-kártya
   * ALATT, kívülre lógva — a CSS ülteti rá a panel alsó keretére.
   */
  function buildBrandBanner(ui) {
    var brandInput = document.getElementById('kmae_vehicle_brand');
    var brand = brandInput ? String(brandInput.value || '').trim() : '';
    /* A márkajelvény a kérdéspanel alsó széléhez csatlakozik, kívül középen. */
    var main = els.wizard.querySelector('.kmae-wizard-main');

    if (!brand || !main) {
      return;
    }

    var catalog = window.kmaeVehicleCatalog || {};
    var logos = catalog.logos || {};
    var logoUrl = '';
    Object.keys(logos).some(function (key) {
      if (key.toLowerCase() === brand.toLowerCase()) {
        logoUrl = logos[key];
        return true;
      }
      return false;
    });

    var card = document.createElement('div');
    card.className = 'kmae-brandcard';

    var head = document.createElement('div');
    head.className = 'kmae-brandcard__head';

    if (logoUrl) {
      var img = document.createElement('img');
      img.className = 'kmae-brandcard__logo';
      img.src = logoUrl;
      img.alt = '';
      img.loading = 'lazy';
      img.decoding = 'async';
      head.appendChild(img);
    }

    var text = document.createElement('div');
    text.className = 'kmae-brandcard__text';

    var label = document.createElement('span');
    label.className = 'kmae-brandcard__label';
    label.textContent = 'Kiválasztott márka';

    var name = document.createElement('strong');
    name.className = 'kmae-brandcard__name';
    name.textContent = brandDisplayName(brand);

    text.appendChild(label);
    text.appendChild(name);
    head.appendChild(text);
    card.appendChild(head);

    main.appendChild(card);
    ui.brandCard = card;
  }

  /**
   * Évjárat lépés: gyorsválasztó a gyakori évekhez (a régebbiek külön nyílnak),
   * és a beírt évből számolt életkor. Szabálylista nincs.
   */
  function buildYearField(step, control, ui) {
    var field = control.closest('.kmae-field');
    if (!field) {
      return;
    }

    var min = parseInt(control.getAttribute('min'), 10) || 1950;
    var max = parseInt(control.getAttribute('max'), 10) || new Date().getFullYear();
    var thisYear = new Date().getFullYear();

    var wrap = document.createElement('div');
    wrap.className = 'kmae-year';

    /* --- Gyakori választások: az öt legutóbbi év, alatta a régebbiek --- */
    var quick = document.createElement('div');
    quick.className = 'kmae-year__quick';

    var quickTitle = document.createElement('p');
    quickTitle.className = 'kmae-year__quick-title';
    quickTitle.textContent = 'Gyakori választások';
    quick.appendChild(quickTitle);

    var chips = document.createElement('div');
    chips.className = 'kmae-year__chips';
    quick.appendChild(chips);

    var older = document.createElement('div');
    older.className = 'kmae-year__older';
    older.hidden = true;
    quick.appendChild(older);

    function addChip(year, host) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'kmae-year__chip';
      btn.textContent = String(year);
      btn.addEventListener('click', function () {
        control.value = String(year);
        control.setCustomValidity('');
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
        older.hidden = true;
        moreBtn.classList.remove('is-open');
        moreBtn.setAttribute('aria-expanded', 'false');
        sync();
        window.setTimeout(advance, 220);
      });
      host.appendChild(btn);
      return btn;
    }

    var newest = Math.min(max, thisYear);
    for (var y = newest; y > newest - 5 && y >= min; y--) {
      addChip(y, chips);
    }

    var moreBtn = document.createElement('button');
    moreBtn.type = 'button';
    moreBtn.className = 'kmae-year__more';
    moreBtn.textContent = 'Régebbi';
    moreBtn.setAttribute('aria-expanded', 'false');
    moreBtn.addEventListener('click', function () {
      var open = older.hidden;
      if (open && !older.childNodes.length) {
        for (var oy = newest - 5; oy >= min; oy--) {
          addChip(oy, older);
        }
      }
      older.hidden = !open;
      moreBtn.classList.toggle('is-open', open);
      moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      sync();
    });
    chips.appendChild(moreBtn);
    wrap.appendChild(quick);

    var bottom = document.createElement('div');
    bottom.className = 'kmae-year__bottom';

    var example = document.createElement('div');
    example.className = 'kmae-year__example';
    var exampleText = document.createElement('div');
    exampleText.className = 'kmae-year__example-text';
    var exampleTitle = document.createElement('strong');
    var exampleNote = document.createElement('span');
    exampleText.appendChild(exampleTitle);
    exampleText.appendChild(exampleNote);
    example.appendChild(exampleText);
    bottom.appendChild(example);
    wrap.appendChild(bottom);

    function ageText(year) {
      var age = thisYear - year;
      if (age <= 0) {
        return 'Ez egy idei autó.';
      }
      return 'Ez egy ' + age + ' éves autó.';
    }

    function sync() {
      var value = String(control.value || '').trim();
      var n = parseInt(value, 10);

      Array.prototype.forEach.call(wrap.querySelectorAll('.kmae-year__chip'), function (btn) {
        btn.classList.toggle('is-selected', btn.textContent === value);
      });

      if (/^\d{4}$/.test(value) && n >= min && n <= max) {
        exampleTitle.textContent = value;
        exampleNote.textContent = ageText(n);
        example.classList.add('is-live');
      } else {
        exampleTitle.textContent = 'Példa: 2018';
        exampleNote.textContent = ageText(2018);
        example.classList.remove('is-live');
      }
    }

    control.addEventListener('input', sync);
    field.appendChild(wrap);
    sync();

    state.ui = Object.assign(ui || {}, { control: control, yearWrap: wrap, yearSync: sync });
  }

  var MOT_MONTH_NAMES = ['január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];

  /**
   * Műszaki érvényesség lépés: az év+hónap kiválasztása alatt egy élő
   * összegző kártya mutatja a pontos lejárati dátumot és a hátralévő időt.
   */
  function buildMotDateSummary(step, control, ui) {
    var field = control.closest('.kmae-field');
    var monthSelect = document.getElementById('kmae_vehicle_mot_month');
    var daySelect = document.getElementById('kmae_vehicle_mot_day');
    if (!field || !monthSelect) {
      return;
    }

    /* A mező a lépések között újrahasznosul, ezért a korábbi összegzőt ki kell
       venni — különben minden újbóli belépéskor egy újabb sor jelenne meg a
       hátralévő idővel. */
    var previous = field.querySelectorAll('.kmae-mot-summary');
    for (var p = 0; p < previous.length; p++) {
      previous[p].parentNode.removeChild(previous[p]);
    }

    var summary = document.createElement('div');
    summary.className = 'kmae-mot-summary';
    summary.hidden = true;

    var icon = document.createElement('span');
    icon.className = 'kmae-mot-summary__icon';
    icon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>';
    icon.setAttribute('aria-hidden', 'true');

    var text = document.createElement('div');
    text.className = 'kmae-mot-summary__text';
    var label = document.createElement('span');
    label.className = 'kmae-mot-summary__label';
    label.textContent = 'Műszaki érvényessége:';
    var dateEl = document.createElement('strong');
    dateEl.className = 'kmae-mot-summary__date';
    text.appendChild(label);
    text.appendChild(dateEl);

    var pill = document.createElement('span');
    pill.className = 'kmae-mot-summary__pill';
    var pillIcon = document.createElement('span');
    pillIcon.className = 'kmae-mot-summary__pill-icon';
    pillIcon.setAttribute('aria-hidden', 'true');
    var pillText = document.createElement('span');
    pill.appendChild(pillIcon);
    pill.appendChild(pillText);

    summary.appendChild(icon);
    summary.appendChild(text);
    summary.appendChild(pill);
    field.appendChild(summary);

    /* Múltbeli dátumot nem lehet választani: a mai naphoz képest korábbi
       hónapok és napok kikerülnek a listából. A napválasztó ezen felül csak az
       adott hónapban létező napokat kínálja. */
    function syncChoices() {
      var today = new Date();
      var year = parseInt(control.value, 10);
      var month = parseInt(monthSelect.value, 10);

      Array.prototype.forEach.call(monthSelect.options, function (opt) {
        var value = parseInt(opt.value, 10);
        var past = !!value && year === today.getFullYear() && value < today.getMonth() + 1;
        opt.hidden = past;
        opt.disabled = past;
      });

      if (year === today.getFullYear() && month && month < today.getMonth() + 1) {
        monthSelect.value = '';
        month = 0;
      }

      if (!daySelect) {
        return;
      }

      var lastDay = year && month ? new Date(year, month, 0).getDate() : 31;
      var minDay = (year === today.getFullYear() && month === today.getMonth() + 1) ? today.getDate() : 1;

      Array.prototype.forEach.call(daySelect.options, function (opt) {
        var day = parseInt(opt.value, 10);
        var bad = !!day && (day > lastDay || day < minDay);
        opt.hidden = bad;
        opt.disabled = bad;
      });

      var picked = parseInt(daySelect.value, 10);
      if (picked && (picked > lastDay || picked < minDay)) {
        daySelect.value = '';
      }
    }

    function sync() {
      syncChoices();

      var year = parseInt(control.value, 10);
      var month = parseInt(monthSelect.value, 10);
      if (!year || !month) {
        summary.hidden = true;
        return;
      }

      var lastDay = new Date(year, month, 0).getDate();
      var day = daySelect ? parseInt(daySelect.value, 10) : 0;
      if (!day || day > lastDay) {
        day = lastDay;
      }

      dateEl.textContent = year + '. ' + MOT_MONTH_NAMES[month - 1] + ' ' + day + '.';

      var expiry = new Date(year, month - 1, day);
      var now = new Date();
      var totalMonths = (expiry.getFullYear() - now.getFullYear()) * 12 + (expiry.getMonth() - now.getMonth());
      if (expiry.getDate() < now.getDate()) {
        totalMonths -= 1;
      }

      if (expiry < now) {
        pill.classList.add('is-expired');
        pill.classList.remove('is-ok');
        pillIcon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';
        pillText.textContent = 'Már lejárt';
      } else {
        pill.classList.add('is-ok');
        pill.classList.remove('is-expired');
        pillIcon.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        var years = Math.floor(totalMonths / 12);
        var months = totalMonths % 12;

        /* A napokat abból számoljuk, mennyi maradt az utolsó teljes hónap
           után — így a visszaszámlálás napra pontos. */
        var marker = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        marker.setMonth(marker.getMonth() + totalMonths);
        var days = Math.max(0, Math.round((expiry - marker) / 86400000));

        var parts = [];
        if (years > 0) {
          parts.push(years + ' év');
        }
        if (months > 0) {
          parts.push(months + ' hónap');
        }
        if (days > 0 || !parts.length) {
          parts.push(days + ' nap');
        }
        pillText.textContent = 'Még ' + parts.join(' ') + ' van hátra';
      }
      summary.hidden = false;
    }

    control.addEventListener('change', sync);
    monthSelect.addEventListener('change', sync);
    if (daySelect) {
      daySelect.addEventListener('change', sync);
    }
    sync();

    state.ui = Object.assign(ui || {}, { control: control, motSummary: summary });
  }

  function wrapUnitField(step, control, ui) {
    var field = control.closest('.kmae-field');
    if (!field || !step.unit) {
      return;
    }

    var wrap = document.createElement('div');
    wrap.className = 'kmae-wizard-unit';

    var unit = document.createElement('span');
    unit.className = 'kmae-wizard-unit__suffix';
    unit.textContent = step.unit;

    var card = null;
    var icon = null;

    if (step.unitLabel) {
      card = document.createElement('div');
      card.className = 'kmae-wizard-unit-card';

      var label = document.createElement('span');
      label.className = 'kmae-wizard-unit-card__label';
      label.textContent = step.unitLabel;
      card.appendChild(label);

      if (step.unitIcon) {
        icon = document.createElement('span');
        icon.className = 'kmae-wizard-unit__icon';
        icon.innerHTML = step.unitIcon;
        icon.setAttribute('aria-hidden', 'true');
      }
    }

    field.insertBefore(card || wrap, control);
    if (card) {
      card.appendChild(wrap);
    }
    if (icon) {
      wrap.appendChild(icon);
    }
    wrap.appendChild(control);
    wrap.appendChild(unit);

    control.classList.add('is-wizard-enhanced');
    enhanceFormattedNumber(control);
    state.ui = Object.assign(ui || {}, {
      control: control,
      unitWrap: wrap,
      unitCard: card
    });
  }

  /**
   * Km óra állás lépés kiegészítése: élő szabálylista (formázás elemei) és
   * gyakori kilométer-minták gyors kiválasztásra. Csak ezen az egy lépésen
   * jelenik meg, a `wrapUnitField` mértékegység-dobozára építve.
   */
  function buildMileageQuick(step, control, ui) {
    var field = control.closest('.kmae-field');
    if (!field) {
      return;
    }

    function rawDigits(value) {
      return num ? num.digitsOnly(value) : String(value || '').replace(/\D/g, '');
    }

    var wrap = document.createElement('div');
    wrap.className = 'kmae-mileage';

    var quick = document.createElement('div');
    quick.className = 'kmae-mileage__quick';

    var quickTitle = document.createElement('p');
    quickTitle.className = 'kmae-mileage__quick-title';
    quickTitle.textContent = 'Gyakori minták';
    quick.appendChild(quickTitle);

    var chips = document.createElement('div');
    chips.className = 'kmae-mileage__chips';
    quick.appendChild(chips);

    var presets = [25000, 50000, 100000, 150000, 200000];
    presets.forEach(function (value, index) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'kmae-mileage__chip';
      var label = num ? num.formatGroupedNumber(String(value)) : String(value);
      btn.textContent = (index === presets.length - 1) ? (label + '+') : label;
      btn.addEventListener('click', function () {
        control.value = String(value);
        control.setCustomValidity('');
        control.dispatchEvent(new Event('input', { bubbles: true }));
        control.dispatchEvent(new Event('change', { bubbles: true }));
        sync();
        window.setTimeout(advance, 220);
      });
      chips.appendChild(btn);
    });
    wrap.appendChild(quick);

    function sync() {
      var value = rawDigits(control.value);
      Array.prototype.forEach.call(chips.querySelectorAll('.kmae-mileage__chip'), function (btn, index) {
        btn.classList.toggle('is-selected', value === String(presets[index]));
      });
    }

    control.addEventListener('input', sync);
    field.appendChild(wrap);
    sync();

    ui.mileageWrap = wrap;
    ui.mileageSync = sync;
  }

  /**
   * Beírós mezőkhöz (szöveg, szám) egy "x" gomb a mező végén: egy kattintással
   * törli a beírtakat, hogy elgépelés vagy visszalépés után könnyű újrakezdeni.
   * Csak akkor látszik, ha van mit törölni.
   */
  function buildClearButton(step, control, ui) {
    if (!control || control.tagName !== 'INPUT') {
      return;
    }
    if (control.type !== 'text' && control.type !== 'number') {
      return;
    }

    var host = control.parentNode;
    if (!host) {
      return;
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'kmae-wizard-clear';
    btn.setAttribute('aria-label', 'Mező törlése');
    btn.innerHTML = '<span aria-hidden="true">&times;</span>';

    /* Mértékegységes mezőnél a gomb a mező DOBOZÁN BELÜLRE kerül: a mezőt egy
       pozicionált burokba tesszük, és a gomb annak a jobb szélére ül. */
    if (host.classList.contains('kmae-wizard-unit')) {
      var box = document.createElement('span');
      box.className = 'kmae-wizard-unit__box';
      host.insertBefore(box, control);
      box.appendChild(control);
      box.appendChild(btn);
    } else {
      host.classList.add('has-wizard-clear');
      host.appendChild(btn);
    }

    function sync() {
      btn.hidden = String(control.value || '') === '';
    }

    btn.addEventListener('click', function () {
      control.value = '';
      control.setCustomValidity('');
      control.dispatchEvent(new Event('input', { bubbles: true }));
      control.dispatchEvent(new Event('change', { bubbles: true }));
      sync();
      control.focus();
    });

    control.addEventListener('input', sync);
    sync();

    ui.clearBtn = btn;
    ui.clearHost = host;
    ui.clearSync = sync;
    ui.clearControl = control;
  }

  function enhanceStepUI(step) {
    cleanupStepUI();
    var control = primaryControl(step);
    if (!control || step.scope !== 'field') {
      return;
    }

    var field = control.closest('.kmae-field');
    var fieldHint = field ? field.querySelector('.kmae-hint') : null;
    var ui = {};

    if (fieldHint) {
      ui.fieldHint = fieldHint;
      ui.fieldHintWasHidden = fieldHint.hidden;
      fieldHint.hidden = true;
    }

    if (step.inputType === 'cards') {
      buildChoiceCards(step, control, ui);
      return;
    }
    if (step.inputType === 'owner-cards') {
      buildOwnerCards(step, control, ui);
      return;
    }
    if (step.inputType === 'trait-cards') {
      buildTraitCards(step, ui);
      return;
    }
    if (step.id === 'vehicle_model') {
      buildBrandBanner(ui);
    }

    if (step.inputType === 'year') {
      buildYearField(step, control, ui);
      buildClearButton(step, control, state.ui);
      return;
    }

    if (step.id === 'vehicle_mot_date') {
      buildMotDateSummary(step, control, ui);
      return;
    }

    if (step.inputType === 'unit') {
      wrapUnitField(step, control, ui);
      buildClearButton(step, control, state.ui);
      return;
    }

    if (step.inputType === 'mileage') {
      wrapUnitField(step, control, ui);
      buildClearButton(step, control, state.ui);
      buildMileageQuick(step, control, state.ui);
      return;
    }

    buildClearButton(step, control, ui);
    state.ui = ui;
  }

  /* ---------------------------------------------------------------- */
  /* Stage handling                                                    */
  /* ---------------------------------------------------------------- */

  function park(step) {
    var el = resolveTarget(step);
    var home = state.homes[step.id];
    if (!el || !home) {
      return;
    }

    if (home.next && home.next.parentNode === home.parent) {
      home.parent.insertBefore(el, home.next);
    } else {
      home.parent.appendChild(el);
    }

    // Answered single questions stay hidden, so the later group step covering the
    // same form section does not ask them again.
    if (step.scope === 'field' && state.completed.indexOf(step.id) !== -1) {
      el.classList.add('is-wizard-answered');
    }
  }

  function mountStage(step) {
    var el = resolveTarget(step);
    if (!el) {
      return false;
    }
    if (!state.homes[step.id]) {
      state.homes[step.id] = { parent: el.parentNode, next: el.nextSibling };
    }
    el.classList.remove('is-wizard-answered');
    els.stage.appendChild(el);
    return true;
  }

  function clearStage() {
    cleanupStepUI();
    var current = stepByIndex(state.index);
    if (current) {
      park(current);
    }
    while (els.stage.firstChild) {
      els.stage.removeChild(els.stage.firstChild);
    }
  }

  /* ---------------------------------------------------------------- */
  /* Assistant + progress                                              */
  /* ---------------------------------------------------------------- */

  function setAssistant(step) {
    var prompt = step.prompt;

    if (step.promptWith) {
      var related = readValue(step.promptWith.field);
      if (related) {
        prompt = step.promptWith.text.replace('{value}', related);
      }
    }

    if (els.sectionIntro) {
      els.sectionIntro.textContent = step.sectionIntro || '';
      els.sectionIntro.hidden = !step.sectionIntro;
    }

    els.prompt.textContent = prompt;
    els.hint.textContent = step.hint || '';
    els.hint.hidden = !step.hint;

    if (els.assistantTitle) {
      els.assistantTitle.textContent = '';
    }
    if (els.assistantText) {
      els.assistantText.textContent = '';
    }

    els.feedback.hidden = true;
    els.feedback.textContent = '';
    els.feedback.classList.remove('is-error', 'is-success');

    els.wizard.classList.toggle('is-single-question', step.scope === 'field');
    updateStepProgress(step);
  }

  /**
   * Hol tartunk a SZAKASZON BELÜL: hányadik aktív lépés ez, és összesen hány
   * van. Ugyanezt használja a lépés-felirat és a felső folyamatsáv is, hogy ne
   * mondhassanak két különbözőt. A kihagyott lépések nem számítanak bele.
   *
   * Két típusú lépés nem számol a folyamatsávon:
   * - Opcionális lépések (vehicle_traits, extras): a felhasználó kihagyhatja,
   *   nem növelik a lépésszámot.
   * - Kodi-cionális pár (pl. mot_has → mot_date): amikor a feltételes lépés
   *   aktív, a pár egyetlen lépésként számol — a segéd-kérdés (mot_has) maga
   *   nem növeli a számlálót, mert nem adatbejegyzés, hanem ágazdás.
   */
  function phasePosition(step) {
    var index = 0;
    var total = 0;

    for (var i = 0; i < STEPS.length; i++) {
      if (STEPS[i].phase !== step.phase || !isStepActive(STEPS[i])) {
        continue;
      }

      /* Opcionális lépések (amiket a felhasználó kihagyhat) nem növelik a
         lépésszámot a folyamatsávon. */
      if (STEPS[i].optional) {
        continue;
      }

      /* Kodi-cionális pár: ha egy lépés után rögtön egy feltételes lépés jön,
         ami ezt a lépést kívánja (pl. mot_has → mot_date), akkor a segéd-
         kérdést nem számoljuk külön — a pár egyetlen lépésként jelenik meg. */
      if (i + 1 < STEPS.length
          && STEPS[i + 1].phase === step.phase
          && STEPS[i + 1].requires
          && STEPS[i + 1].requires.field === STEPS[i].target.replace(/^#/, '')
          && isStepActive(STEPS[i + 1])) {
        continue;
      }

      total++;
      if (STEPS[i].id === step.id) {
        index = total;
      }
    }

    return { index: index, total: total };
  }

  /* A kocsi már a kérdés cseréje előtt elindul, hogy ne legyen holtidő a
     Tovább megnyomása és a mozgás között. */
  function roadTravel(index) {
    var step = STEPS[index];

    if (!step || !els.stepProgress || els.stepProgress.hidden) {
      return;
    }

    var pos = phasePosition(step);
    if (pos.index && pos.total > 1) {
      roadDrive(pos.index, pos.total, step.phase);
    }
  }

  function updateStepProgress(step) {
    if (!els.stepProgress) {
      return;
    }
    var pos = phasePosition(step);

    /* Egyetlen lépésből álló szakasznál a "1 / 1" felesleges. */
    if (!pos.index || pos.total < 2) {
      els.stepProgress.hidden = true;
      road.shown = false;
      return;
    }

    els.stepProgress.hidden = false;
    setStepCount(pos.index, pos.total);
    roadDrive(pos.index, pos.total, step.phase);
  }

  /* --- Az úton guruló autó ---------------------------------------------
     A kocsi egyenletes tempóban megy át a következő állomásra. A kerék pont
     annyit fordul, amennyit gurul (megtett út osztva a kerékkerülettel),
     ezért a gumi nem csúszik az úton.

     A felni külön kép, a gumi tetején. Haladás közben a kettő AZONOS szögben
     forog, ezért egyetlen keréknek látszanak. A fékezés elején a kocsi
     „elengedi" a felnit: az a pillanatnyi sebességét viszi tovább, egyenletesen
     lassul, és pontosan a gumi végállásába érkezik — így megállás után újra
     fedésben van vele. Nincs megállás és újraindulás, és nincs elnyúló farok
     sem a lecsengés végén. */

  var ROAD_SPEED = 36;         /* haladás sebessége, px/mp */
  var ROAD_MIN_MS = 450;       /* rövid ugrásnál se legyen kapkodós */
  var ROAD_MAX_MS = 1500;      /* hosszú ugrásnál (kevés lépés, nagy táv) gyorsít */
  var ROAD_RAMP = 50;          /* indulás-fékezés lágyítása, az út %-ában —
                                  50 = végig gyorsul és fékez, ettől gurul */
  var ROAD_RELEASE = 0.55;     /* a fékezés kezdetén engedi el a felnit, így az
                                  a cirkáló sebességét viszi tovább */
  var ROAD_RIM_SPEED = 0.5;    /* a felni ekkora sebességgel pörög tovább, a
                                  közös gurulás tempójához képest */
  /* Kevés lépésnél (pl. a leírás-segéd öt kérdése) hosszabban pörög a felni,
     mert ott nagyobb utat tesz meg a kocsi; a sok lépéses szakaszon marad a
     rövid kipörgés. */
  var ROAD_TURNS_LONG = { spin: 1, slow: 2 };
  var ROAD_TURNS_SHORT = { spin: 0, slow: 1 };
  var ROAD_FEW_STEPS = 6;
  var ROAD_REVERSE = 1.15;     /* hátrafarolás ennyivel lomhább */
  var ROAD_RIM_ZERO = 1;       /* a felni alapszöge, hogy fedésbe álljon, fok */

  /* A számláló két mezőben, mindig két számjeggyel: 02 · 12 */
  function setStepCount(index, total) {
    if (!els.stepNow || !els.stepAll) {
      els.stepProgressCount.textContent = index + ' / ' + total;
      return;
    }
    els.stepNow.textContent = index < 10 ? '0' + index : String(index);
    els.stepAll.textContent = total < 10 ? '0' + total : String(total);
  }

  var road = { x: 0, tire: 0, rim: 0, raf: 0, index: 0, total: 0, phase: null, busy: false, stops: [], lit: -1, shown: false };
  var roadPending = null;
  var roadPendingTries = 0;

  /* Az állomáspontok az út mentén: annyi, ahány lépés van a szakaszban, és
     mindegyik ott, ahol a kocsi majd megáll. */
  var roadStopsTries = 0;

  function roadStopsRender() {
    if (!els.roadStops || road.total < 2) {
      return;
    }

    var carW = els.roadCar.getBoundingClientRect().width;
    var i;

    /* Rejtett vagy még ki nem mért panelnél a szélesség 0 — ilyenkor az első
       állomás a kocsi mögé esne. Megvárjuk a következő képkockát. */
    if (!carW) {
      if (roadStopsTries < 30) {
        roadStopsTries++;
        requestAnimationFrame(roadStopsRender);
      }
      return;
    }

    roadStopsTries = 0;

    if (els.roadStops.childElementCount !== road.total) {
      while (els.roadStops.firstChild) {
        els.roadStops.removeChild(els.roadStops.firstChild);
      }
      for (i = 0; i < road.total; i++) {
        var dot = document.createElement('span');
        dot.className = 'kmae-wizard-road__stop';
        els.roadStops.appendChild(dot);
      }
    }

    road.stops = [];
    road.lit = -1;
    for (i = 0; i < road.total; i++) {
      var at = roadStop(i + 1, road.total) + carW / 2;
      road.stops.push(at);
      els.roadStops.children[i].style.left = at.toFixed(2) + 'px';
    }
  }

  function roadPaint() {
    var width = els.road.clientWidth;
    var carW = els.roadCar.offsetWidth;
    var i;

    els.roadCar.style.transform = 'translate3d(' + road.x.toFixed(2) + 'px, 0, 0)';

    for (i = 0; i < els.roadTires.length; i++) {
      els.roadTires[i].style.transform = 'rotate(' + road.tire.toFixed(2) + 'deg)';
    }
    /* A felni alapszöge állandó eltolás: a számolás a tiszta szöggel megy, a
       kép ezzel eltolva jelenik meg, így pontosan fedésbe áll a gumival. */
    for (i = 0; i < els.roadRims.length; i++) {
      els.roadRims[i].style.transform = 'rotate(' + (road.rim + ROAD_RIM_ZERO).toFixed(2) + 'deg)';
    }

    /* A megvilágított szakasz az autó közepéig ér — a kocsi „festi" maga mögé. */
    var nose = road.x + carW / 2;

    if (width > 0) {
      var run = Math.max(0, Math.min(width, nose)).toFixed(2) + 'px';
      els.stepProgressFill.style.width = run;
      if (els.roadHaze) {
        els.roadHaze.style.width = run;
      }
      /* A megtett szakasz színátmenete a teljes úthoz méreteződik, így a cél
         közelében magától melegszik narancsba. */
      els.road.style.setProperty('--road-w', width + 'px');
    }

    /* Az állomáspont akkor gyullad ki, amikor a kocsi odaér. Csak változáskor
       nyúlunk a DOM-hoz, különben az érkezés pulzálása minden képkockán
       újraindulna. */
    if (els.roadStops && road.stops.length) {
      var lit = -1;
      for (i = 0; i < road.stops.length; i++) {
        if (nose >= road.stops[i] - 2) {
          lit = i;
        }
      }

      if (lit !== road.lit) {
        road.lit = lit;
        for (i = 0; i < road.stops.length; i++) {
          var dot = els.roadStops.children[i];
          dot.classList.toggle('is-done', i <= lit);
          dot.classList.toggle('is-current', i === lit);
        }
      }
    }
  }

  var ROAD_FINISH_GAP = 20;    /* a kocsi orra a célzászló tövéig gurul */

  function roadStop(index, total) {
    var span = els.road.clientWidth - els.roadCar.offsetWidth - ROAD_FINISH_GAP;
    if (span <= 0 || total < 2) {
      return 0;
    }
    return span * ((index - 1) / (total - 1));
  }

  function roadCanMeasure() {
    if (!els.road || !els.roadCar) {
      return false;
    }
    return els.road.clientWidth - els.roadCar.offsetWidth - ROAD_FINISH_GAP > 0;
  }

  function roadFlushPending() {
    if (!roadPending) {
      return;
    }

    if (!roadCanMeasure()) {
      if (roadPendingTries < 30) {
        roadPendingTries++;
        requestAnimationFrame(roadFlushPending);
        return;
      }
      roadPending = null;
      roadPendingTries = 0;
      return;
    }

    var pending = roadPending;
    roadPending = null;
    roadPendingTries = 0;
    roadDrive(pending.index, pending.total, pending.phase);
  }

  function roadQueueDrive(index, total, phase) {
    roadPending = { index: index, total: total, phase: phase };
    roadPendingTries = 0;
    requestAnimationFrame(roadFlushPending);
  }

  /* A betöltő rejtette a pályát: a lépés után azonnal a helyére ugrik. */
  function roadResyncAfterInit() {
    requestAnimationFrame(function () {
      var step = stepByIndex(state.index);
      if (!step) {
        return;
      }

      var pos = phasePosition(step);
      if (!pos.index || pos.total < 2) {
        return;
      }

      cancelAnimationFrame(road.raf);
      road.busy = false;
      road.shown = false;
      roadDrive(pos.index, pos.total, step.phase);
    });
  }

  /* Mozgásprofil: rövid lágy indítás, végig egyenletes tempó, rövid fékezés.
     ROAD_RAMP = 0 esetén tiszta egyenletes mozgás. */
  function roadVel(p) {
    var a = ROAD_RAMP / 100;
    if (a <= 0) {
      return 1;
    }
    var vmax = 1 / (1 - a);
    if (p < a) {
      return vmax * (1 - Math.cos(Math.PI * p / a)) / 2;
    }
    if (p > 1 - a) {
      return vmax * (1 - Math.cos(Math.PI * (1 - p) / a)) / 2;
    }
    return vmax;
  }

  function roadProg(p) {
    var a = ROAD_RAMP / 100;
    if (a <= 0) {
      return p;
    }
    var vmax = 1 / (1 - a);
    if (p < a) {
      return vmax * (p / 2 - a * Math.sin(Math.PI * p / a) / (2 * Math.PI));
    }
    if (p <= 1 - a) {
      return vmax * a / 2 + vmax * (p - a);
    }
    var q = 1 - p;
    return 1 - vmax * (q / 2 - a * Math.sin(Math.PI * q / a) / (2 * Math.PI));
  }

  /**
   * A kocsit a megadott állomásra viszi. A `done` akkor fut le, amikor a
   * kocsi MEGÉRKEZETT — a felni kipörgése már nem várat magára.
   */
  function roadDrive(index, total, phase) {
    if (!els.road || !els.roadCar) {
      return;
    }

    /* A betöltő css még rejtette a sávot: 0 szélességnél ne jelöljük késznek
       a kocsit, különben folytatáskor a következő lépés óriási ugrást csinál. */
    if (total >= 2 && !roadCanMeasure()) {
      roadQueueDrive(index, total, phase);
      return;
    }

    /* Ha már úton van ugyanoda, hagyjuk békén: különben a mozgás félbeszakadna
       és nulláról indulna újra (ez okozta az „elindul-megáll-elindul" hatást). */
    if (road.busy && index === road.index && total === road.total && phase === road.phase) {
      return;
    }

    /* Új sorozat elején a kocsi az út elejére kerül — ezt nem gurulva tesszük
       meg, mert végigtolatni az egész úton értelmetlen lenne. Ugyanez áll arra,
       amikor más hosszúságú szakaszra váltunk (pl. a leírás-segéd kérdéseire). */
    /* Ha a sáv közben rejtve volt, a kocsi korábbi helye már nem érvényes —
       ilyenkor ugrunk, nem gurulunk vissza a pálya végéről. */
    var jump = !road.shown
      || road.phase === null
      || phase !== road.phase
      || index < road.index - 1
      /* A lépésszám menet közben is változhat (pl. a műszaki dátuma csak akkor
         kerül be, ha van érvényes vizsga). Ilyenkor egy lépést előre még
         guruljon, csak a nagy ugrásokat vegyük ugrásnak. */
      || (total !== road.total && Math.abs(index - road.index) > 1);

    road.shown = true;

    road.index = index;
    road.total = total;
    road.phase = phase;
    roadStopsRender();

    var from = road.x;
    var to = roadStop(index, total);
    var dist = to - from;

    /* Nincs mit megtenni: a futó kipörgést szándékosan NEM szakítjuk félbe —
       ez fut le akkor is, amikor a megérkezés után frissül a lépéssáv. */
    if (!dist) {
      roadPaint();
      return;
    }

    cancelAnimationFrame(road.raf);

    var wheelPx = els.roadTires.length ? els.roadTires[0].offsetWidth : 0;
    var spin = wheelPx > 0 ? dist / (Math.PI * wheelPx) * 360 : 0;

    if (jump || prefersReducedMotion() || !spin) {
      road.x = to;
      road.tire += spin;
      road.rim = road.tire;
      road.busy = false;
      roadPaint();
      return;
    }

    var startTire = road.tire;
    var finalTire = startTire + spin;
    /* Az idő korlátos: kevés lépésnél egy-egy szakasz sokkal hosszabb, és a
       fix sebesség miatt kínosan lassú lenne — ilyenkor a kocsi gyorsít. */
    var dur = Math.abs(dist) / ROAD_SPEED * 1000;
    dur = Math.max(ROAD_MIN_MS, Math.min(ROAD_MAX_MS, dur));
    if (dist < 0) {
      dur *= ROAD_REVERSE;
    }

    /* Az elválás pillanata: a felni a gumi akkori sebességét veszi át, ezért
       nincs ugrás — a mozgás folyamatos marad. */
    var relT = ROAD_RELEASE * dur;
    var relAngle = startTire + spin * roadProg(ROAD_RELEASE);
    var omega = spin * roadVel(ROAD_RELEASE) / dur * ROAD_RIM_SPEED;

    /* A kipörgés teljes fordulatokban: a hátralévő szög (hogy a gumi
       végállásába érjen), plusz a megadott egész körök. Így a felni mindig
       pontosan alaphelyzetbe, vagyis fedésbe áll vissza. */
    var sign = spin < 0 ? -1 : 1;
    var remain = Math.abs(finalTire - relAngle);
    var turns = total <= ROAD_FEW_STEPS ? ROAD_TURNS_LONG : ROAD_TURNS_SHORT;
    var slowArc = turns.slow * 360;
    var evenArc = remain + turns.spin * 360;
    var delta = sign * (evenArc + slowArc);

    /* Először végig egyenletes tempóban pörög (ugyanazzal a sebességgel, amivel
       elvált a gumitól), és csak az utolsó körökben lassul le nullára. */
    var evenDur = Math.abs(omega) ? evenArc / Math.abs(omega) : 0;
    var slowDur = Math.abs(omega) ? slowArc * 2 / Math.abs(omega) : 0;
    var coastDur = evenDur + slowDur;

    var t0 = now();
    road.busy = true;

    (function frame(stamp) {
      var t = stamp - t0;
      var p = Math.min(1, t / dur);
      var ct = t - relT;
      var u = coastDur ? Math.min(1, ct / coastDur) : 1;

      road.x = from + dist * roadProg(p);
      road.tire = startTire + spin * roadProg(p);

      if (t <= relT || !coastDur) {
        road.rim = road.tire;
      } else if (ct <= evenDur) {
        /* Egyenletes tempó, ugyanaz a sebesség, amivel elvált. */
        road.rim = relAngle + sign * Math.abs(omega) * ct;
      } else {
        /* Az utolsó körök: egyenletes lassulás pontosan nulláig. */
        var v = Math.min(1, (ct - evenDur) / slowDur);
        road.rim = relAngle + sign * (evenArc + slowArc * (1 - Math.pow(1 - v, 2)));
      }

      roadPaint();

      if (p < 1 || u < 1) {
        road.raf = requestAnimationFrame(frame);
        return;
      }

      road.tire = finalTire;
      road.rim = relAngle + delta;
      road.busy = false;
      roadPaint();
    })(t0);
  }

  function prefersReducedMotion() {
    return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
  }

  function now() {
    return window.performance && performance.now ? performance.now() : Date.now();
  }

  /* Átméretezéskor az állomások helye változik: ugrás animáció nélkül. */
  window.addEventListener('resize', function () {
    if (!els.road || !road.total) {
      return;
    }
    cancelAnimationFrame(road.raf);
    road.busy = false;
    road.x = roadStop(road.index, road.total);
    roadStopsRender();
    roadPaint();
  });

  function showFeedback(message, kind) {
    els.feedback.textContent = message;
    els.feedback.hidden = !message;
    els.feedback.classList.toggle('is-error', kind === 'error');
    els.feedback.classList.toggle('is-success', kind === 'success');
  }

  /**
   * "Válassz a listából, vagy add meg kézzel." — a második fele kattintható:
   * odavisz, ahova a "Nincs a listán" gomb vitt volna. Kattintásra, nem magától.
   */
  function showCatalogFeedback(step, control) {
    els.feedback.textContent = '';
    els.feedback.hidden = false;
    els.feedback.classList.remove('is-success');
    els.feedback.classList.add('is-error');

    /* Mindkét kiút kattintható: a lista megnyitása és a kézi megadás is. */
    var listLink = document.createElement('button');
    listLink.type = 'button';
    listLink.className = 'kmae-wizard-feedback__link';
    listLink.textContent = 'Válassz a listából';
    listLink.addEventListener('click', function () {
      control.dispatchEvent(new CustomEvent('kmae:catalog-show-list', { bubbles: true }));
      els.feedback.hidden = true;
      els.feedback.textContent = '';
      els.feedback.classList.remove('is-error');
      updateActions(step);
    });

    els.feedback.appendChild(listLink);
    els.feedback.appendChild(document.createTextNode(', vagy '));

    var link = document.createElement('button');
    link.type = 'button';
    link.className = 'kmae-wizard-feedback__link';
    link.textContent = 'add meg kézzel';
    link.addEventListener('click', function () {
      control.dispatchEvent(new CustomEvent('kmae:catalog-force-custom', { bubbles: true }));
      els.feedback.hidden = true;
      els.feedback.textContent = '';
      els.feedback.classList.remove('is-error');
      updateActions(step);
    });

    els.feedback.appendChild(link);
    els.feedback.appendChild(document.createTextNode('.'));

    control.focus();
  }

  function updateProgress(step) {
    var phases = cfg.phases.map(function (phase) { return phase.code; });
    var currentPhase = phases.indexOf(step.phase);

    if (els.eyebrow) {
      /* Két külön számolás van a felületen:
         - a bal sáv a szakaszok között mutatja, hol tartunk a hirdetésben,
         - ez a felirat a SZAKASZON BELÜL számol (a modell pl. a 2. lépés).
         A kihagyott lépések (pl. műszaki dátuma "Nem" válasznál) nem számítanak. */
      var label = step.eyebrow || (cfg.phaseLabels && cfg.phaseLabels[step.phase]) || '';
      var pos = phasePosition(step);
      var eyebrowText = pos.index > 0 && label ? pos.index + '. lépés – ' + label : '';
      els.eyebrow.textContent = eyebrowText;
      els.eyebrow.hidden = !eyebrowText;
    }

    els.progressItems.forEach(function (item) {
      var index = phases.indexOf(item.getAttribute('data-kmae-phase'));
      item.classList.toggle('is-current', index === currentPhase);
      item.classList.toggle('is-done', index > -1 && index < currentPhase);
      item.classList.toggle('is-skipped', !phaseHasActiveStep(item.getAttribute('data-kmae-phase')));
    });
  }

  function phaseHasActiveStep(phase) {
    return STEPS.some(function (step) {
      return step.phase === phase && isStepActive(step);
    });
  }

  function updateActions(step) {
    els.back.hidden = prevActiveIndex(state.index - 1) === -1;

    var manualCatalog = null;
    if (step.id === 'vehicle_brand' || step.id === 'vehicle_model') {
      manualCatalog = resolveTarget(step);
      if (!manualCatalog || !manualCatalog.classList.contains('is-custom')) {
        manualCatalog = null;
      }
    }

    if (manualCatalog) {
      els.nextLabel.textContent = step.id === 'vehicle_brand'
        ? 'Igen, biztosan ez a márka'
        : 'Igen, biztosan ez a modell';
      var manualInput = primaryControl(step);
      els.next.disabled = !manualInput || !String(manualInput.value || '').trim();
    } else if (step.optional && step.skipLabel) {
      els.nextLabel.textContent = hasAnyValue(step) ? cfg.i18n.next : step.skipLabel;
      els.next.disabled = false;
    } else if (step.nextLabel) {
      els.nextLabel.textContent = step.nextLabel;
      els.next.disabled = false;
    } else {
      els.nextLabel.textContent = cfg.i18n.next;
      els.next.disabled = false;
    }

    /* Két állapot: van válasz → erős narancs, nincs → halkabb, várakozó. */
    var ready = !els.next.disabled && ( hasAnyValue(step) || !!(step.optional && step.skipLabel) );
    els.next.classList.toggle('is-ready', ready);

    els.next.hidden = step.phase === 'done';
  }

  function hasAnyValue(step) {
    if (step.inputType === 'owner-cards') {
      var ownerCtrl = primaryControl(step);
      if (!ownerCtrl) {
        return false;
      }
      return isOwnersUnknown(ownerCtrl) || parseInt(String(ownerCtrl.value || ''), 10) >= 1;
    }

    var host = resolveTarget(step);
    if (!host) {
      return false;
    }
    return Array.prototype.some.call(host.querySelectorAll('input, select, textarea'), function (el) {
      if (el.dataset.kmaeFormatted === '1') {
        return rawNumberValue(el) !== '';
      }
      if (el.type === 'checkbox' || el.type === 'radio') {
        return el.checked;
      }
      return String(el.value || '').trim() !== '';
    });
  }

  /* ---------------------------------------------------------------- */
  /* Validation                                                        */
  /* ---------------------------------------------------------------- */

  function isVisible(el) {
    return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
  }

  function validate(step) {
    var host = resolveTarget(step);
    if (!host) {
      return { ok: true };
    }

    if (step.optional) {
      return { ok: true };
    }

    var controls = host.querySelectorAll('input, select, textarea');
    var missing = [];

    for (var i = 0; i < controls.length; i++) {
      var control = controls[i];

      // Controls the user cannot see (conditional price field, already answered
      // question, collapsed block) must not block the step.
      if (control.disabled) {
        continue;
      }

      if (!isVisible(control) && !control.classList.contains('is-wizard-enhanced')) {
        continue;
      }

      if (control.dataset.kmaeFormatted === '1') {
        syncFormattedValidity(control);
      }

      if (control.name === 'kmae_description' && cfg.minDescription > 0) {
        if (String(control.value || '').trim().length < cfg.minDescription) {
          return { ok: false, message: cfg.i18n.descriptionShort, focus: control };
        }
      }

      if (step.inputType === 'owner-cards' && step.allowUnknown && control.name === 'kmae_vehicle_owners' && isOwnersUnknown(control)) {
        continue;
      }

      // A `required` a csupa szóközt még értéknek tekinti — üres mezővel ne
      // lehessen továbblépni. A hiányzó mezőket nevesítjük, hogy látszódjon,
      // pontosan mi maradt ki (pl. "év" vagy "hónap").
      if (control.required && typeof control.value === 'string' && control.value.trim() === '') {
        missing.push({ control: control, name: controlLabel(control) });
        continue;
      }

      if (!control.checkValidity()) {
        return { ok: false, message: control.validationMessage || cfg.i18n.invalid, focus: control };
      }
    }

    if (missing.length) {
      var names = missing.map(function (item) { return item.name; });
      return {
        ok: false,
        message: names.length > 1
          ? 'Ezeket még ki kell töltened: ' + names.join(', ') + '.'
          : 'Ezt még ki kell töltened: ' + names[0] + '.',
        focus: missing[0].control
      };
    }

    return { ok: true };
  }

  /**
   * A mező emberi neve a hibaüzenethez: elsőként az aria-label (pl. „Év”,
   * „Hónap”), utána a hozzá tartozó címke szövege.
   */
  function controlLabel(control) {
    var aria = control.getAttribute('aria-label');
    if (aria) {
      return aria.trim().toLowerCase();
    }

    var label = control.id ? document.querySelector('label[for="' + control.id + '"]') : null;
    if (label) {
      return label.textContent.replace('*', '').trim().toLowerCase();
    }

    return 'ez a mező';
  }

  /* ---------------------------------------------------------------- */
  /* Navigation                                                        */
  /* ---------------------------------------------------------------- */

  /* ---------------------------------------------------------------- */
  /* Szövegíró asszisztens                                             */
  /* ---------------------------------------------------------------- */

  /**
   * Az asszisztens nem elemzi a beírt szöveget, és nem keres benne szavakat.
   * Azt figyeli, amit MI illesztettünk be: minden szemponthoz tartozik egy fix
   * mondatkezdet, és annak a jelenléte dönti el a gomb állapotát.
   *
   *   +  — még nincs bent               → beszúrja, a kurzor a helyére ugrik
   *   −  — bent van, de üresen          → kiveszi
   *   ✓  — bent van és írt is hozzá     → kész, nincs teendő
   *
   * Ettől nincs szükség szótárra, szótövezésre és elgépelés-tűrésre: a
   * felismerés mindig pontos.
   */
  function assistItems() {
    return Array.prototype.slice.call(
      document.querySelectorAll('#kmae-wizard-assist .kmae-wizard-assist__item')
    );
  }

  function assistField() {
    return byId('kmae_description');
  }

  /* A szempont blokkja: a jelölőtől a következő üres sorig tart. */
  function assistBlock(value, marker) {
    var at = value.indexOf(marker);
    if (at === -1) {
      return null;
    }
    var rest = value.slice(at);
    var endsAt = rest.indexOf('\n\n');
    return {
      start: at,
      text: endsAt === -1 ? rest : rest.slice(0, endsAt)
    };
  }

  function assistState(item, value) {
    var marker = item.dataset.kmaeMarker || '';
    var insert = item.dataset.kmaeInsert || '';
    var block = assistBlock(value, marker);

    if (!block) {
      return 'add';
    }
    /* Ha a blokk hosszabb, mint amit beszúrtunk, akkor írt is hozzá. */
    return block.text.trim().length > insert.trim().length ? 'done' : 'remove';
  }

  function assistInsert(item) {
    var field = assistField();
    if (!field) {
      return;
    }

    var insert = item.dataset.kmaeInsert || '';
    var caret = parseInt(item.dataset.kmaeCaret, 10);
    var current = field.value.replace(/\s+$/, '');
    /* Üres sor választja el az előző résztől — így tagolt marad a szöveg. */
    var prefix = current ? current + '\n\n' : '';

    field.value = prefix + insert;
    field.focus();

    var at = prefix.length + (isNaN(caret) ? insert.length : caret);
    field.setSelectionRange(at, at);
    field.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function assistRemove(item) {
    var field = assistField();
    if (!field) {
      return;
    }

    var block = assistBlock(field.value, item.dataset.kmaeMarker || '');
    if (!block) {
      return;
    }

    var before = field.value.slice(0, block.start).replace(/\s+$/, '');
    var after = field.value.slice(block.start + block.text.length).replace(/^\s+/, '');

    field.value = [before, after].filter(Boolean).join('\n\n');
    field.focus();
    field.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function assistNudge(percent) {
    var list = (cfg && cfg.assistNudges) || [];
    for (var i = 0; i < list.length; i += 1) {
      if (percent >= list[i].min) {
        return list[i].text;
      }
    }
    return '';
  }

  function syncAssist() {
    var field = assistField();
    var items = assistItems();
    if (!field || !items.length) {
      return;
    }

    var value = field.value;
    var done = 0;

    items.forEach(function (item) {
      var state = assistState(item, value);
      var btn = item.querySelector('[data-kmae-assist-btn]');
      var glyph = item.querySelector('.kmae-wizard-assist__btn-glyph');

      item.classList.toggle('is-added', state === 'remove');
      item.classList.toggle('is-done', state === 'done');

      if (glyph) {
        glyph.textContent = state === 'add' ? '+' : (state === 'remove' ? '−' : '✓');
      }
      if (btn) {
        btn.disabled = state === 'done';
      }
      if (state === 'done') {
        done += 1;
      }
    });

    var total = items.length;
    var percent = total ? Math.round((done / total) * 100) : 0;

    var doneEl = byId('kmae-wizard-assist-done');
    var percentEl = byId('kmae-wizard-assist-percent');
    var barEl = byId('kmae-wizard-assist-bar');
    var nudgeEl = byId('kmae-wizard-assist-nudge');

    if (doneEl) {
      doneEl.textContent = String(done);
    }
    if (percentEl) {
      percentEl.textContent = percent + '%';
    }
    if (barEl) {
      barEl.style.width = percent + '%';
    }
    if (nudgeEl) {
      nudgeEl.textContent = assistNudge(percent);
    }
  }

  /**
   * Az asszisztens bemutatkozása: a sorok egyesével jelennek meg a buborékban,
   * és közben a kép is vált. Szándékosan lassú tempó: legyen idő elolvasni,
   * és észrevenni, hogy a figura is más lett.
   *
   * Csak egyszer fut le: ha oda-vissza lépkedsz, nem kezdi újra.
   */
  var assistIntroTimers = [];
  var assistIntroDone = false;
  var assistAddCount = 0;

  function stopAssistIntro() {
    assistIntroTimers.forEach(window.clearTimeout);
    assistIntroTimers = [];
  }

  /* Az ÖSSZES „+" gomb felvillan — így látszik, hogy bármelyikre lehet nyomni. */
  function flashAssistButtons() {
    var items = assistItems();
    if (!items.length) {
      return;
    }
    items.forEach(function (item) {
      item.classList.add('is-hint');
    });
    assistIntroTimers.push(window.setTimeout(function () {
      items.forEach(function (item) {
        item.classList.remove('is-hint');
      });
    }, 3400));
  }

  function showAssistLine(line) {
    var bubble = byId('kmae-wizard-assist-bubble');
    var mascot = document.querySelector('.kmae-wizard-assist__mascot');

    if (bubble) {
      /* A buborék flex-konténer (függőlegesen középre igazít), ezért a
         szöveget egyetlen burkolatba tesszük: e nélkül a beágyazott "+"/"−"
         jelvény külön flex-elemmé válna, és szétszakítaná a mondatot. */
      bubble.innerHTML = '<span class="kmae-wizard-assist__bubble-text">' + line.text + '</span>';
      /* Ahol jelvény van a mondatban, ott szellősebb a sortáv — csak ott,
         a többi üzenet tömör marad. */
      bubble.classList.toggle('has-badge', String(line.text).indexOf('kmae-assist-inline-badge') !== -1);
      bubble.classList.remove('is-typing');
      /* Az animáció újraindításához ki kell kényszeríteni az újrarajzolást. */
      void bubble.offsetWidth;
      bubble.classList.add('is-typing');
    }
    /* A felvillanás akkor is kell, ha a kép ugyanaz, mint ami már a helyén
       van. Az első sor („Szia …") képe a sablonból eleve be van töltve, ezért
       egy src-egyezés vizsgálat pont a legfontosabb pillanatot hagyná ki: a
       megszólítást senki nem venné észre. */
    if (mascot && line.image) {
      if (mascot.getAttribute('src') !== line.image) {
        mascot.src = line.image;
      }
      mascot.classList.remove('is-swap');
      void mascot.offsetWidth;
      mascot.classList.add('is-swap');
    }
    if (line.highlight) {
      flashAssistButtons();
    }
  }

  function playAssistIntro() {
    var lines = (cfg && cfg.assistIntro) || [];
    if (!lines.length) {
      return;
    }

    /* Másodszorra ne játssza le újra — csak az utolsó állapot maradjon. */
    if (assistIntroDone) {
      showAssistLine(lines[lines.length - 1]);
      return;
    }

    stopAssistIntro();

    var at = 0;
    lines.forEach(function (line, index) {
      var show = function () {
        showAssistLine(line);
        if (index === lines.length - 1) {
          assistIntroDone = true;
        }
      };

      if (index === 0) {
        show();
      } else {
        assistIntroTimers.push(window.setTimeout(show, at));
      }

      /* A hosszabb mondat több időt kap. Lassú annyira, hogy legyen idő
         elolvasni és észrevenni a figuraváltást — de nem vontatott.
         Minden érték a korábbi kétharmada, vagyis egyharmaddal gyorsabb. */
      at += Math.min(6000, 2600 + String(line.text || '').length * 52);
    });
  }

  function bindAssist() {
    var assist = byId('kmae-wizard-assist');
    if (!assist || assist.dataset.kmaeBound === '1') {
      return;
    }
    assist.dataset.kmaeBound = '1';

    assist.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-kmae-assist-btn]');
      if (!btn) {
        return;
      }
      var item = btn.closest('.kmae-wizard-assist__item');
      var field = assistField();
      if (!item || !field) {
        return;
      }

      if (assistState(item, field.value) === 'add') {
        assistInsert(item);

        /* Az Albira az első néhány hozzáadásra visszajelez. A bemutatkozást
           ilyenkor leállítjuk, különben a következő sora felülírná a reakciót. */
        assistAddCount += 1;
        var reaction = (cfg && cfg.assistReactions) ? cfg.assistReactions[assistAddCount] : null;
        if (reaction) {
          stopAssistIntro();
          assistIntroDone = true;
          showAssistLine(reaction);
        }
      } else {
        assistRemove(item);
      }
      syncAssist();
    });

    /* Kézi szerkesztésre is frissül: ha kitörli a beszúrt részt, a gomb
       magától visszaáll „+"-ra. */
    var field = assistField();
    if (field) {
      field.addEventListener('input', syncAssist);
    }
  }

  /**
   * A jobb oldali sáv a szövegíró lépésen átadja a helyét az asszisztensnek.
   * Az adatok összegzése addigra megtette a dolgát — onnantól a segítség a
   * hasznos. A váltást egy osztály végzi, hogy a két tartalom ne keveredjen.
   */
  /**
   * Az összegző panelen kiemeljük azt az adatblokkot, amelyik éppen kitöltés
   * alatt áll — így a panel láthatóan reagál arra, hol tart a folyamat.
   */
  function markActivePreviewRow(step) {
    var rows = document.querySelectorAll('#kmae-wizard-preview .kmae-wizard-preview__row');

    /* Amin már túlment: minden korábbi lépés azonosítója. A pipa csak ezeknél
       gyulladhat ki — amíg a kérdésen állunk, a válasz még alakulhat, ott a
       „kész" jel korai lenne. Visszalépésnél a pipa magától eltűnik. */
    var passed = {};
    for (var i = 0; i < state.index && i < STEPS.length; i++) {
      passed[STEPS[i].id] = true;
    }

    Array.prototype.forEach.call(rows, function (row) {
      var steps = (row.dataset.kmaeSteps || '').split(/\s+/);
      var isActive = false;
      var isPassed = false;

      steps.forEach(function (id) {
        if (!id) {
          return;
        }
        if (id === step.id) {
          isActive = true;
        }
        if (passed[id]) {
          isPassed = true;
        }
      });

      row.classList.toggle('is-active', isActive);
      row.classList.toggle('is-passed', isPassed && !isActive);
    });
  }

  function syncSidePanel(step) {
    var preview = byId('kmae-wizard-preview');
    var assist = byId('kmae-wizard-assist');
    if (!preview || !assist) {
      return;
    }

    markActivePreviewRow(step);

    var on = step.id === 'listing_content';
    preview.classList.toggle('is-assist', on);
    assist.hidden = !on;

    if (!on) {
      stopAssistIntro();
      return;
    }

    bindAssist();
    syncAssist();
    playAssistIntro();
  }

  function restoreStepView(step) {
    hideCheer();
    els.wizard.dataset.phase = step.phase;
    els.wizard.dataset.step = step.id;
    els.stage.dataset.scope = step.scope;

    syncSidePanel(step);
    setAssistant(step);
    updateProgress(step);
    updateActions(step);
    enhanceStepUI(step);

    if (step.id === 'sale_mode') {
      enhanceSalePriceFields();
      bindSaleModeAdvance();
    }

    var control = primaryControl(step);
    if (control && control.type !== 'file' && typeof control.focus === 'function') {
      control.focus({ preventScroll: true });
    }
  }

  function goTo(index) {
    if (index < 0 || index >= STEPS.length) {
      return;
    }

    var previousIndex = state.index;
    clearStage();

    state.index = index;
    var step = STEPS[index];

    if (!mountStage(step)) {
      state.index = previousIndex;
      if (previousIndex >= 0) {
        var previousStep = stepByIndex(previousIndex);
        if (previousStep && mountStage(previousStep)) {
          restoreStepView(previousStep);
        }
      }
      showFeedback('Nem sikerült betölteni a következő lépést. Próbáld újra.', 'error');
      return;
    }

    restoreStepView(step);
    scheduleDraftSave();

    els.wizard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /**
   * A záró dicséret a gombsor közepén. Csak akkor jelenik meg, ha a „Tovább"
   * tényleg átengedett — tehát minden kötelező mező megvan.
   *
   * @return bool Igaz, ha sikerült megjeleníteni.
   */
  function showCheer(done) {
    var box = byId('kmae-wizard-cheer');
    var text = byId('kmae-wizard-cheer-text');
    var img = byId('kmae-wizard-cheer-img');

    if (!box || !text || !img || !done) {
      return false;
    }

    text.textContent = done.text;
    img.src = done.image;
    box.hidden = false;
    box.classList.add('is-on');

    return true;
  }

  function hideCheer() {
    var box = byId('kmae-wizard-cheer');
    if (box) {
      box.hidden = true;
      box.classList.remove('is-on');
    }
  }

  function restorePublishStep(message, invalidControl) {
    state.submitting = false;
    state.publishCheerShown = false;
    hideCheer();

    if (invalidControl) {
      for (var i = 0; i < STEPS.length; i++) {
        var host = resolveTarget(STEPS[i]);
        if (host && (host === invalidControl || host.contains(invalidControl))) {
          goTo(i);
          break;
        }
      }
    }

    updateActions(stepByIndex(state.index));
    showFeedback(message, 'error');
    if (invalidControl && typeof invalidControl.focus === 'function') {
      window.setTimeout(function () {
        invalidControl.focus();
      }, 0);
    }
  }

  function submitListing(submit) {
    if (!els.form || !submit) {
      restorePublishStep('Nem sikerült elindítani a mentést. Próbáld meg újra.');
      return;
    }

    var invalidControl = els.form.querySelector('input:invalid, select:invalid, textarea:invalid');
    if (invalidControl) {
      restorePublishStep(controlLabel(invalidControl) + ': ' + invalidControl.validationMessage, invalidControl);
      return;
    }

    var submitEvent = null;
    var watchSubmit = function (event) {
      submitEvent = event;
      window.setTimeout(function () {
        if (event.defaultPrevented) {
          restorePublishStep('A pirossal jelölt fotót ki kell cserélned a hirdetés feladása előtt.');
        }
      }, 0);
    };

    els.form.addEventListener('submit', watchSubmit, { once: true });
    try {
      if (typeof els.form.requestSubmit === 'function') {
        els.form.requestSubmit(submit);
      } else {
        submit.click();
      }
    } catch (error) {
      els.form.removeEventListener('submit', watchSubmit);
      restorePublishStep('Nem sikerült elindítani a mentést. Próbáld meg újra.');
      return;
    }

    if (!submitEvent) {
      els.form.removeEventListener('submit', watchSubmit);
      restorePublishStep('Nem sikerült elindítani a mentést. Próbáld meg újra.');
    }
  }

  function advance() {
    var step = stepByIndex(state.index);
    if (!step) {
      return;
    }

    if (step.id === 'photos') {
      var photoInput = document.getElementById('kmae_images');
      if (photoInput && (!photoInput.files || !photoInput.files.length)) {
        showFeedback('Legalább 1 fotó feltöltése kötelező.', 'error');
        photoInput.focus();
        return;
      }
    }

    var result = validate(step);
    if (!result.ok) {
      /* Márka/modell: ha a beírt név nincs a katalógusban, a figyelmeztetés
         kattintható "add meg kézzel" résszel jelenik meg. Szándékosan NEM
         váltunk magától: egy elgépelt név (pl. "pasat") így csendben egyedi
         értékként rögzülne. */
      if (
        result.focus &&
        (step.id === 'vehicle_brand' || step.id === 'vehicle_model') &&
        String(result.focus.value || '').trim() !== ''
      ) {
        var catalogField = result.focus.closest('.kmae-catalog-field');
        if (catalogField && !catalogField.classList.contains('is-custom')) {
          showCatalogFeedback(step, result.focus);
          return;
        }
      }

      showFeedback(result.message, 'error');
      if (result.focus) {
        result.focus.focus();
      }
      return;
    }

    markCompleted(step);
    suggestTitle();

    /* A fotók a folyamat vége: itt már nem kérdés következik, hanem a mentés.
       Előbb az Albira megdicséri az eladót, és csak utána megy el az űrlap —
       különben a visszajelzés elvillanna az átirányítás alatt. */
    if (step.id === 'photos' && !state.publishCheerShown) {
      var submit = document.getElementById('kmae-listing-submit');
      if (submit && !submit.disabled) {
        state.publishCheerShown = true;
        showCheer(cfg && cfg.assistPublish);
        els.next.disabled = true;
        window.setTimeout(function () {
          submitListing(submit);
        }, 2200);
        return;
      }
    }

    var successText = step.success ? step.success.replace('{value}', stepValueLabel(step)) : '';
    if (successText) {
      showFeedback('✓ ' + successText, 'success');
    }

    var next = nextActiveIndex(state.index + 1);
    /* A forgalmiból kitöltött lépéseket átugorjuk — a „Vissza” gombbal elérhetők. */
    while (next !== -1 && state.prefilled[STEPS[next].id] && state.completed.indexOf(STEPS[next].id) !== -1) {
      next = nextActiveIndex(next + 1);
    }
    if (next === -1) {
      return;
    }

    /* A kocsi azonnal indul, a kérdés pedig a maga ütemében vált — egyik sem
       várja meg a másikat. */
    roadTravel(next);

    window.setTimeout(function () {
      goTo(next);
    }, successText ? 480 : 0);
  }

  function goBack() {
    var step = stepByIndex(state.index);
    var prev = prevActiveIndex(state.index - 1);
    if (prev !== -1) {
      roadTravel(prev);
      clearStepValue(stepByIndex(prev));
      goTo(prev);
    }
  }

  /**
   * Visszalépéskor csak azok a lépések állnak alapállapotba, amelyek `clearOnBack`
   * jelölést kaptak (a márka). A többi mező megtartja a beírt értéket — ha
   * mégis módosítani kell, ott a mező végi "x". Így öt lépéssel korábbi hibát is
   * lehet javítani anélkül, hogy a jó válaszokat újra meg kellene adni.
   */
  function clearStepValue(step) {
    if (!step || step.scope !== 'field' || !step.clearOnBack) {
      return;
    }

    var host = resolveTarget(step);
    if (!host) {
      return;
    }

    Array.prototype.forEach.call(host.querySelectorAll('input, select, textarea'), function (control) {
      if (control.type === 'checkbox' || control.type === 'radio') {
        control.checked = false;
      } else {
        control.value = '';
      }
      control.setCustomValidity('');
      control.dispatchEvent(new Event('change', { bubbles: true }));
    });

    var done = state.completed.indexOf(step.id);
    if (done !== -1) {
      state.completed.splice(done, 1);
    }
  }

  function markCompleted(step) {
    if (state.completed.indexOf(step.id) === -1) {
      state.completed.push(step.id);
    }

    STEPS.forEach(function (candidate) {
      if (candidate.resetOnChangeOf !== step.id) {
        return;
      }
      var control = primaryControl(candidate);
      if (control && control.dataset.kmaeWizardSource && control.dataset.kmaeWizardSource !== stepValueLabel(step)) {
        control.value = '';
        control.dispatchEvent(new Event('change', { bubbles: true }));
        state.completed = state.completed.filter(function (id) { return id !== candidate.id; });
      }
      if (control) {
        control.dataset.kmaeWizardSource = stepValueLabel(step);
      }
    });
  }

  /* A mezők nyers értéke (nem a legördülő felirata) — a műszaki dátumhoz kell. */
  function rawValue(name) {
    var el = els.form.querySelector('[name="' + name + '"]');
    return el ? String(el.value || '').trim() : '';
  }

  function digitsOf(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function pad2(value) {
    var text = String(value);
    return text.length < 2 ? '0' + text : text;
  }

  /* Hengerűrtartalom literben: 1598 cm³ → „1.6". */
  function engineLitresText() {
    var cc = parseInt(digitsOf(readValue('kmae_vehicle_engine_cc')), 10);
    if (!cc) {
      return '';
    }
    return (cc / 1000).toFixed(1);
  }

  /* Műszaki érvényesség „2028.02.02" alakban. Ha nincs érvényes műszaki, üres.
     Ha a nap nincs megadva, a hónap utolsó napját vesszük — ahogy a mentés is. */
  function motDateText() {
    if (rawValue('kmae_vehicle_mot_has') !== 'yes') {
      return '';
    }
    var year = parseInt(digitsOf(rawValue('kmae_vehicle_mot_year')), 10);
    var month = parseInt(digitsOf(rawValue('kmae_vehicle_mot_month')), 10);
    if (!year || !month) {
      return '';
    }
    var day = parseInt(digitsOf(rawValue('kmae_vehicle_mot_day')), 10);
    if (!day) {
      day = new Date(year, month, 0).getDate();
    }
    return year + '.' + pad2(month) + '.' + pad2(day);
  }

  /**
   * Az autó adatai egy csomagban — ezt kapja meg a szöveggenerátor, és ezekből
   * épülnek fel a sablonok helykitöltői ({MÁRKA}, {MOTOR}, {KILOMÉTER}, …).
   */
  function vehicleFacts() {
    var litres = engineLitresText();
    var fuel = rawValue('kmae_vehicle_fuel') ? readValue('kmae_vehicle_fuel') : '';
    var power = digitsOf(readValue('kmae_vehicle_power_hp'));
    var mileage = digitsOf(readValue('kmae_vehicle_mileage'));
    var owners = digitsOf(readValue('kmae_vehicle_owners'));
    var county = rawValue('kmae_county') ? readValue('kmae_county') : '';
    var city = readValue('kmae_city');

    return {
      brand: readValue('kmae_vehicle_brand'),
      model: readValue('kmae_vehicle_model'),
      year: readValue('kmae_vehicle_year'),
      engine: [litres, fuel].filter(Boolean).join(' '),
      power: power ? power + ' LE' : '',
      mileage: mileage ? formatGroupedNumber(mileage) : '',
      mot: motDateText(),
      condition: rawValue('kmae_condition') ? readValue('kmae_condition') : '',
      owners: owners ? owners + '. tulajdonostól' : '',
      location: [city, county].filter(Boolean).join(', ')
    };
  }

  /**
   * A hirdetés címe mindig ugyanabból az öt adatból áll össze:
   *   „Eladó {márka} {modell} – {liter} {üzemanyag} | {LE} LE | Műszaki {dátum}-ig"
   * Amelyik adat még hiányzik, az a részlet egyszerűen kimarad, így a cím a
   * lépések haladtával épül fel. Ha a felhasználó átírta, nem nyúlunk hozzá.
   */
  /**
   * A generált leírás végén a „Helyileg:" sor a megye/város lépés UTÁN kap
   * értéket, mert a szöveg korábban készül el. Csak ezt az egy sort írjuk át,
   * a leírás többi részéhez nem nyúlunk.
   */
  function syncDescriptionLocation() {
    var desc = byId('kmae_description');
    if (!desc || !desc.value) {
      return;
    }

    var location = vehicleFacts().location;
    if (!location) {
      return;
    }

    var next = desc.value.replace(/^Helyileg:.*$/m, 'Helyileg: ' + location);
    if (next !== desc.value) {
      desc.value = next;
    }
  }

  function suggestedTitle() {
    var facts = vehicleFacts();
    if (!facts.brand && !facts.model) {
      return '';
    }

    var head = ['Eladó', facts.brand, facts.model].filter(Boolean).join(' ');
    var tail = [];

    if (facts.engine) {
      tail.push(facts.engine);
    }
    if (facts.power) {
      tail.push(facts.power);
    }
    if (facts.mot) {
      tail.push('Műszaki ' + facts.mot + '-ig');
    }

    return tail.length ? head + ' – ' + tail.join(' | ') : head;
  }

  function suggestTitle() {
    var title = byId('kmae_title');
    if (!title || title.dataset.userEdited === '1') {
      return;
    }

    var text = suggestedTitle();
    if (text) {
      title.value = text;
    }
  }

  /* ---------------------------------------------------------------- */
  /* Külső kitöltés: forgalmi beolvasása (registration-scan.js)         */
  /* ---------------------------------------------------------------- */

  /* Lépés → a mezői; a lépés akkor kész, ha mindegyik ki van töltve. */
  var PREFILL_STEPS = {
    vehicle_brand: ['kmae_vehicle_brand'],
    vehicle_model: ['kmae_vehicle_model'],
    vehicle_year: ['kmae_vehicle_year'],
    vehicle_power_hp: ['kmae_vehicle_power_hp'],
    vehicle_engine_cc: ['kmae_vehicle_engine_cc'],
    vehicle_fuel: ['kmae_vehicle_fuel'],
    vehicle_transmission: ['kmae_vehicle_transmission'],
    vehicle_mot_has: ['kmae_vehicle_mot_has'],
    vehicle_mot_date: ['kmae_vehicle_mot_year', 'kmae_vehicle_mot_month']
  };

  function prefillControl(id, value) {
    var el = byId(id);
    value = value === undefined || value === null ? '' : String(value);
    if (!el || value === '') {
      return;
    }
    if (el.tagName === 'SELECT') {
      /* Pontos egyezés, különben számként („5” ↔ „05” — a hónaplista kétjegyű). */
      var match = Array.prototype.filter.call(el.options, function (option) {
        return option.value === value;
      })[0] || Array.prototype.filter.call(el.options, function (option) {
        return option.value !== '' && /^\d+$/.test(value) && parseInt(option.value, 10) === parseInt(value, 10);
      })[0];
      if (!match) {
        return;
      }
      value = match.value;
    }
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function prefillCatalog(id, value, custom) {
    var el = byId(id);
    if (!el || !value) {
      return;
    }
    el.value = value;
    if (custom) {
      el.dispatchEvent(new CustomEvent('kmae:catalog-force-custom', { bubbles: true }));
    }
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /**
   * A beolvasott (és a felhasználó által jóváhagyott) adatok betöltése:
   * a kitöltött lépések késznek számítanak, és az első hiányzó lépésre ugrunk.
   *
   * @param {Object} data { brand, brandCustom, model, modelCustom, year, power_hp,
   *   engine_cc, fuel, transmission, mot_has, mot_year, mot_month, mot_day }
   */
  function applyVehicleData(data) {
    if (!data || !state.device) {
      return;
    }

    state.restoring = true;
    try {
      /* Márka előbb, modell utána: a márka változása kiüríti a modellt. */
      prefillCatalog('kmae_vehicle_brand', data.brand, data.brandCustom);
      prefillCatalog('kmae_vehicle_model', data.model, data.modelCustom);
      prefillControl('kmae_vehicle_year', data.year);
      prefillControl('kmae_vehicle_power_hp', data.power_hp);
      prefillControl('kmae_vehicle_engine_cc', data.engine_cc);
      prefillControl('kmae_vehicle_fuel', data.fuel);
      prefillControl('kmae_vehicle_transmission', data.transmission);
      prefillControl('kmae_vehicle_mot_has', data.mot_has);
      if (data.mot_has === 'yes') {
        prefillControl('kmae_vehicle_mot_year', data.mot_year);
        prefillControl('kmae_vehicle_mot_month', data.mot_month);
        prefillControl('kmae_vehicle_mot_day', data.mot_day);
      }
    } finally {
      state.restoring = false;
    }

    /* A márka „kész” jelölése ne ürítse ki az épp betöltött modellt. */
    var modelInput = byId('kmae_vehicle_model');
    if (modelInput) {
      delete modelInput.dataset.kmaeWizardSource;
    }

    STEPS.forEach(function (step) {
      var names = PREFILL_STEPS[step.id];
      if (!names || !isStepActive(step)) {
        return;
      }
      var filled = names.every(function (id) {
        var el = byId(id);
        return el && String(el.value || '').trim() !== '';
      });
      if (filled) {
        markCompleted(step);
        state.prefilled[step.id] = true;
      }
    });
    suggestTitle();
    state.scanned = true;
    if (data.ids && typeof data.ids === 'object') {
      state.vehicleIds = data.ids;
      setRegistrationIds(data.ids);
    }

    var target = -1;
    for (var i = 0; i < STEPS.length; i++) {
      if (isStepActive(STEPS[i]) && state.completed.indexOf(STEPS[i].id) === -1) {
        target = i;
        break;
      }
    }
    if (target === -1) {
      target = nextActiveIndex(0);
    }

    /* A kocsi ne guruljon az út elejéről a célállomásra — ugrjon rögtön
       a hiányzó lépésre, mert a forgalmi betöltés után a 9. lépés az első. */
    road.shown = false;
    roadTravel(target);
    goTo(target);
    showFeedback('✓ Betöltöttük a forgalmi adatait — már csak a hiányzókat kérdezzük.', 'success');
    scheduleDraftSave();
  }

  /* ---------------------------------------------------------------- */
  /* Boot                                                              */
  /* ---------------------------------------------------------------- */

  /* A beolvasott azonosítók rejtett mezői (kmae_registration_ids[...]). */
  function setRegistrationIds(ids) {
    ['rendszam', 'alvazszam', 'forgalmi_szam'].forEach(function (key) {
      var name = 'kmae_registration_ids[' + key + ']';
      var field = els.form.querySelector('input[name="' + name + '"]');
      var value = ids && typeof ids[key] === 'string' ? ids[key] : '';
      if (!field) {
        field = document.createElement('input');
        field.type = 'hidden';
        field.name = name;
        els.form.appendChild(field);
      }
      field.value = value;
    });
  }

  function activate(device, resume) {
    state.device = device;
    state.completed = [];
    state.prefilled = {};
    state.scanned = !!(resume && resume.scanned);
    state.vehicleIds = resume && resume.vehicleIds && typeof resume.vehicleIds === 'object' ? resume.vehicleIds : null;
    setRegistrationIds(state.vehicleIds);
    purgeLegacyDraft();

    /* Folytatásnál a már teljesített lépések és a félbehagyott lépés visszajön. */
    var startIndex = -1;
    if (resume && resume.step) {
      state.completed = (resume.completed || []).filter(function (id) {
        return STEPS.some(function (item) { return item.id === id; });
      });
      for (var i = 0; i < STEPS.length; i++) {
        if (STEPS[i].id === resume.step && isStepActive(STEPS[i])) {
          startIndex = i;
          break;
        }
      }
    }

    els.fields.classList.add('is-wizard-active');
    els.wizard.hidden = false;
    if (els.page) {
      els.page.classList.add('is-wizard-live');
    }
    if (els.progress) {
      els.progress.hidden = false;
    }

    goTo(startIndex !== -1 ? startIndex : nextActiveIndex(0));
    els.wizard.classList.remove('is-initializing');
    els.wizard.removeAttribute('aria-busy');
    roadResyncAfterInit();

    /* A forgalmi beolvasása felajánlja magát: új hirdetésnél mindig, folytatásnál
       akkor, ha még az autó adatainál tart, és alig van kitöltve valami. */
    var startStep = stepByIndex(state.index);
    var filledCount = ['kmae_vehicle_brand', 'kmae_vehicle_model', 'kmae_vehicle_year', 'kmae_vehicle_power_hp', 'kmae_vehicle_engine_cc', 'kmae_vehicle_fuel', 'kmae_vehicle_transmission']
      .filter(function (id) {
        var el = byId(id);
        return el && String(el.value || '').trim() !== '';
      }).length;
    document.dispatchEvent(new CustomEvent('kmae:wizard-ready', {
      detail: {
        fresh: !resume,
        scanned: state.scanned,
        offer: !state.scanned && (!resume || (!!startStep && startStep.phase === 'auto' && filledCount < 3))
      }
    }));
  }

  function bind() {
    els.next.addEventListener('click', advance);
    els.back.addEventListener('click', goBack);

    /* A helyszín a leírás elkészülte után derül ki — ilyenkor pótoljuk. */
    ['input', 'change'].forEach(function (type) {
      els.form.addEventListener(type, function (event) {
        var field = event.target;
        if (field && (field.name === 'kmae_county' || field.name === 'kmae_city')) {
          syncDescriptionLocation();
        }
      });
    });

    els.stage.addEventListener('change', function (event) {
      var step = stepByIndex(state.index);
      if (!step) {
        return;
      }

      /* Legördülő kiválasztása csak akkor léptet, ha a lépésben EGYETLEN mező
         van. A műszaki dátumánál (év + hónap) az év kiválasztása után még a
         hónap hátravan — ott nem szabad továbblépni. */
      if (step.scope === 'field' && event.target.tagName === 'SELECT' && !step.autoAdvance) {
        var host = resolveTarget(step);
        var controls = host ? host.querySelectorAll('input, select, textarea') : [];
        if (controls.length <= 1) {
          advance();
          return;
        }
      }

      updateActions(step);
    });

    els.stage.addEventListener('input', function () {
      var step = stepByIndex(state.index);
      if (step) {
        updateActions(step);
      }
    });

    // Katalógus-csempére kattintva (márka, modell) magától lép tovább — ahogy
    // a kártyás lépéseknél is. A "Tovább" gomb közben végig működik.
    els.stage.addEventListener('kmae:catalog-pick', function () {
      var step = stepByIndex(state.index);
      if (!step || step.scope !== 'field') {
        return;
      }
      window.setTimeout(advance, 220);
    });


    if (els.form) {
      els.form.addEventListener('submit', function () {
        /* Beküldéskor nem mentünk: a szerver a sikeres feladás után törli a
           piszkozatot, egy közben induló mentés újra létrehozná. */
        if (draftTimer) {
          window.clearTimeout(draftTimer);
          draftTimer = null;
        }
        state.submitting = true;
        var hidden = byId('kmae_draft_id');
        if (hidden && state.draftId) {
          hidden.value = String(state.draftId);
        }
        normalizeFormattedNumbersForSubmit();
        purgeLegacyDraft();
      });

      /* Minden beírás/választás után (kis késleltetéssel) mentünk. */
      els.form.addEventListener('input', scheduleDraftSave);
      els.form.addEventListener('change', scheduleDraftSave);
      window.addEventListener('pagehide', flushDraftOnLeave);
      document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
          flushDraftOnLeave();
        }
      });
    }

    // Enter = Tovább, minden lépésben. Kivétel: a szövegdobozban sortörés
    // marad, a gomboknak saját műveletük van, és ha a katalógus már lekezelte
    // az Entert (kiválasztott egy elemet), akkor a léptetést az onnan érkező
    // kmae:catalog-pick végzi — így nem lép kettőt.
    els.stage.addEventListener('keydown', function (event) {
      if (event.key !== 'Enter' || event.defaultPrevented || event.shiftKey) {
        return;
      }
      var step = stepByIndex(state.index);
      if (!step) {
        return;
      }
      var tag = event.target.tagName;
      if (tag === 'TEXTAREA' || tag === 'BUTTON' || tag === 'A') {
        return;
      }
      event.preventDefault();
      advance();
    });

    var title = byId('kmae_title');
    if (title) {
      title.addEventListener('input', function () {
        title.dataset.userEdited = '1';
      });
    }

    document.addEventListener('kmae:device-selected', function (event) {
      if (state.device) {
        return;
      }
      activate(event.detail && event.detail.device ? event.detail.device : 'phone');
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!cfg) {
      return;
    }

    els.wizard = byId('kmae-listing-wizard');
    els.form = byId('kmae-listing-form');
    els.fields = byId('kmae-listing-fields');
    els.progress = byId('kmae-wizard-progress');
    els.page = els.form ? els.form.closest('.kmae-create-page') : null;

    if (cfg.draftSimple && cfg.draft && cfg.draft.id && els.form) {
      startSimpleDraft(cfg.draft);
      return;
    }

    if (!els.wizard || !els.form || !els.fields) {
      return;
    }

    els.stage = byId('kmae-wizard-stage');
    els.prompt = byId('kmae-wizard-prompt');
    els.hint = byId('kmae-wizard-hint');
    els.sectionIntro = byId('kmae-wizard-section-intro');
    els.eyebrow = byId('kmae-wizard-eyebrow');
    els.stepProgress = byId('kmae-wizard-step-progress');
    els.stepProgressFill = byId('kmae-wizard-step-progress-fill');
    els.stepProgressCount = byId('kmae-wizard-step-progress-count');
    els.road = byId('kmae-wizard-road');
    els.roadCar = byId('kmae-wizard-roadcar');
    els.roadStops = byId('kmae-wizard-road-stops');
    els.roadHaze = byId('kmae-wizard-road-haze');
    els.stepNow = byId('kmae-wizard-step-progress-now');
    els.stepAll = byId('kmae-wizard-step-progress-all');
    els.roadTires = els.roadCar ? els.roadCar.querySelectorAll('.kmae-wizard-roadcar__tire') : [];
    els.roadRims = els.roadCar ? els.roadCar.querySelectorAll('.kmae-wizard-roadcar__rim') : [];
    els.assistantTitle = byId('kmae-wizard-assistant-title');
    els.assistantText = byId('kmae-wizard-assistant-text');
    els.feedback = byId('kmae-wizard-feedback');
    els.next = els.wizard.querySelector('[data-kmae-wizard="next"]');
    els.nextLabel = els.next.querySelector('span');
    els.back = els.wizard.querySelector('[data-kmae-wizard="back"]');
    els.progressItems = Array.prototype.slice.call(
      (els.progress || els.wizard).querySelectorAll('[data-kmae-phase]')
    );

    purgeLegacyDraft();
    bind();
    window.kmaeWizard = { applyVehicleData: applyVehicleData };

    var deviceInput = byId('kmae_create_device');
    var device = deviceInput && deviceInput.value ? deviceInput.value : 'computer';

    if (cfg.draft && cfg.draft.id) {
      if (cfg.draftRequested) {
        resumeDraft(cfg.draft, device);
      } else {
        showDraftPrompt(cfg.draft, device);
      }
      return;
    }

    activate(device);
  });
})();
