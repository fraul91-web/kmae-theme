(function () {
  'use strict';

  var WIZARD_CLEAR_PARAMS = ['kivitel', 'mod', 'kp_min', 'kp_max', 'step', 'tipus', 'kategoria'];

  function parseCurrency(value) {
    var digits = String(value || '').replace(/\D/g, '');
    if (!digits) {
      return 0;
    }
    return parseInt(digits, 10) || 0;
  }

  function formatCurrency(num) {
    if (!num || num <= 0) {
      return '';
    }
    return String(num).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function getCurrencyValue(el) {
    return el ? parseCurrency(el.value) : 0;
  }

  function bindCurrencyInput(el, onChange) {
    if (!el) {
      return;
    }

    el.addEventListener('input', function () {
      var raw = parseCurrency(el.value);
      el.value = formatCurrency(raw);
      if (typeof onChange === 'function') {
        onChange();
      }
    });

    el.addEventListener('blur', function () {
      var raw = parseCurrency(el.value);
      el.value = formatCurrency(raw);
    });
  }

  function buildWizardUrl(baseUrl, params) {
    var url = new URL(baseUrl, window.location.origin);
    WIZARD_CLEAR_PARAMS.forEach(function (key) {
      url.searchParams.delete(key);
    });
    Object.keys(params || {}).forEach(function (key) {
      var value = params[key];
      if (value === null || value === undefined || value === '') {
        url.searchParams.delete(key);
      } else {
        url.searchParams.set(key, String(value));
      }
    });
    return url.toString();
  }

  function initMapPicker() {
    var wizard = document.querySelector('.kmae-wizard-map');
    if (!wizard) {
      return;
    }

    var baseUrl = wizard.getAttribute('data-browse-base') || window.location.pathname;
    var navigating = false;
    var phoneMq = window.matchMedia ? window.matchMedia('(max-width: 1024px)') : null;
    var nextBtn = wizard.querySelector('.kmae-map-next');
    var statusBox = wizard.querySelector('.kmae-map-status');
    var statusName = wizard.querySelector('.kmae-map-status-name');
    var pickedSlug = '';

    function go(slug) {
      if (navigating) {
        return;
      }
      navigating = true;
      window.location.assign(buildWizardUrl(baseUrl, { megye: slug }));
    }

    // Phone: tapping a county only marks it (small targets are easy to miss); "Tovább" continues.
    function pickCounty(link, slug) {
      wizard.querySelectorAll('.kmae-county-link.is-active').forEach(function (other) {
        other.classList.remove('is-active');
      });
      link.classList.add('is-active');
      // Last in the SVG = painted on top, so the highlight outline is not hidden by neighbours.
      if (link.parentNode) {
        link.parentNode.appendChild(link);
      }
      pickedSlug = slug;
      var label = link.getAttribute('data-label') || '';
      wizard.setAttribute('data-picked-label', label);
      if (statusName) {
        statusName.textContent = label;
      }
      if (statusBox) {
        statusBox.classList.add('is-active');
      }
      if (nextBtn) {
        nextBtn.disabled = false;
      }
    }

    // One handler for the county link, its SVG children and the country button.
    wizard.addEventListener('click', function (event) {
      var choice = event.target.closest('[data-county]');
      if (!choice || !wizard.contains(choice)) {
        return;
      }
      var slug = choice.getAttribute('data-county');
      if (!slug) {
        return;
      }
      event.preventDefault();
      var link = choice.closest('.kmae-county-link');
      if (link && nextBtn && phoneMq && phoneMq.matches) {
        pickCounty(link, slug);
        return;
      }
      go(slug);
    });

    if (nextBtn) {
      nextBtn.addEventListener('click', function () {
        if (pickedSlug) {
          go(pickedSlug);
        }
      });
    }

    window.addEventListener('pageshow', function () {
      navigating = false;
    });
  }

  function initBodyPicker() {
    var picker = document.getElementById('kmae-wizard-body-picker');
    var nextBtn = document.getElementById('kmae-wizard-body-next');
    if (!picker || !nextBtn) {
      return;
    }

    var baseUrl = picker.getAttribute('data-base-url') || window.location.pathname;
    var cards = picker.querySelectorAll('[data-kmae-body]');
    var selected = new Set();

    cards.forEach(function (card) {
      if (card.classList.contains('is-selected')) {
        selected.add(card.getAttribute('data-kmae-body'));
      }
    });

    function syncCards() {
      cards.forEach(function (card) {
        var slug = card.getAttribute('data-kmae-body');
        var isSelected = selected.has(slug);
        card.classList.toggle('is-selected', isSelected);
        card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });
      nextBtn.disabled = selected.size === 0;
    }

    function goNext() {
      if (selected.size === 0) {
        return;
      }

      var value = selected.has('mind') ? 'mind' : Array.from(selected).join(',');
      window.location.assign(buildWizardUrl(baseUrl, { kivitel: value }));
    }

    cards.forEach(function (card) {
      card.addEventListener('click', function () {
        var slug = card.getAttribute('data-kmae-body');
        if (slug === 'mind') {
          // "Mindegy" is a single choice: go straight to the next step.
          selected.clear();
          selected.add('mind');
          syncCards();
          goNext();
          return;
        }

        selected.delete('mind');
        if (selected.has(slug)) {
          selected.delete(slug);
        } else {
          selected.add(slug);
        }
        syncCards();
      });
    });

    nextBtn.addEventListener('click', goNext);

    syncCards();
  }

  function initOfferForm() {
    var form = document.getElementById('kmae-wizard-offer-form');
    if (!form) {
      return;
    }

    var modInput = document.getElementById('kmae-wizard-mod');
    var searchBtn = document.getElementById('kmae-wizard-search-btn');
    var kpPanel = document.getElementById('kmae-panel-kp');
    var kpMinInput = document.getElementById('kmae-wizard-kp-min');
    var kpMaxInput = document.getElementById('kmae-wizard-kp-max');
    var kpMinVal = document.getElementById('kmae-wizard-kp-min-val');
    var kpMaxVal = document.getElementById('kmae-wizard-kp-max-val');
    var modeCards = form.querySelectorAll('[data-kmae-mode]');
    var activeMode = '';
    var errorBox = document.getElementById('kmae-wizard-errors');

    function msg(key) {
      if (!errorBox) {
        return '';
      }
      return errorBox.getAttribute('data-msg-' + key) || '';
    }

    function clearFeedback() {
      if (!errorBox) {
        return;
      }
      while (errorBox.firstChild) {
        errorBox.removeChild(errorBox.firstChild);
      }
      errorBox.hidden = true;
      errorBox.classList.remove('is-error', 'is-guide');
    }

    function showFeedback(items) {
      if (!errorBox) {
        return;
      }
      clearFeedback();
      var list = (items || []).filter(function (item) {
        return item && item.text;
      });
      if (!list.length) {
        return;
      }

      var hasError = list.some(function (item) {
        return item.kind === 'error';
      });
      errorBox.hidden = false;
      errorBox.classList.add(hasError ? 'is-error' : 'is-guide');
      errorBox.setAttribute('role', hasError ? 'alert' : 'status');

      list.forEach(function (item) {
        var p = document.createElement('p');
        p.className =
          item.kind === 'error' ? 'kmae-wizard-form-error' : 'kmae-wizard-form-guide';
        p.textContent = item.text;
        errorBox.appendChild(p);
      });
    }

    function collectIssues() {
      var items = [];

      if (!activeMode) {
        items.push({ kind: 'guide', text: msg('mode') });
        return items;
      }

      if (activeMode === 'kp') {
        var min = getCurrencyValue(kpMinInput);
        var max = getCurrencyValue(kpMaxInput);
        if (min < 1) {
          items.push({ kind: 'guide', text: msg('kp-min') });
        }
        if (max < 1) {
          items.push({ kind: 'guide', text: msg('kp-max') });
        }
        if (min >= 1 && max >= 1 && max < min) {
          items.push({ kind: 'error', text: msg('kp-range') });
        }
      }

      return items;
    }

    function syncSearchBtn() {
      if (searchBtn) {
        searchBtn.disabled = !activeMode;
      }
    }

    [kpMinInput, kpMaxInput].forEach(function (el) {
      bindCurrencyInput(el, function () {
        clearFeedback();
      });
    });

    modeCards.forEach(function (card) {
      card.addEventListener('click', function () {
        activeMode = card.getAttribute('data-kmae-mode');
        modInput.value = activeMode;
        modeCards.forEach(function (c) {
          c.classList.toggle('is-active', c === card);
        });
        if (kpPanel) {
          kpPanel.hidden = activeMode !== 'kp';
        }
        clearFeedback();
        syncSearchBtn();
        if (activeMode === 'ajanlat') {
          form.requestSubmit();
        }
      });
    });

    form.addEventListener('submit', function (e) {
      var issues = collectIssues();
      if (issues.length) {
        e.preventDefault();
        showFeedback(issues);
        return;
      }

      clearFeedback();

      if (activeMode === 'kp') {
        kpMinVal.value = String(getCurrencyValue(kpMinInput));
        kpMaxVal.value = String(getCurrencyValue(kpMaxInput));
      }
    });

    syncSearchBtn();
  }

  document.addEventListener('DOMContentLoaded', function () {
    initMapPicker();
    initBodyPicker();
    initOfferForm();
    initEmptyFilters();
    initFilterRail();
  });

  function initEmptyFilters() {
    var root = document.getElementById('kmae-empty-filters');
    if (!root) {
      return;
    }

    var origin = {
      county: root.getAttribute('data-county') || '',
      body: root.getAttribute('data-body') || '',
      mod: root.getAttribute('data-mod') || '',
      kpMin: parseInt(root.getAttribute('data-kp-min') || '0', 10) || 0,
      kpMax: parseInt(root.getAttribute('data-kp-max') || '0', 10) || 0
    };
    var state = {
      county: origin.county,
      body: origin.body,
      mod: origin.mod,
      kpMin: origin.kpMin,
      kpMax: origin.kpMax
    };
    var go = document.getElementById('kmae-empty-search');
    var cash = document.getElementById('kmae-empty-cash');
    var kpMinInput = document.getElementById('kmae-empty-kp-min');
    var kpMaxInput = document.getElementById('kmae-empty-kp-max');
    var picks = root.querySelectorAll('.kmae-swipe-empty__pick');

    function closeMenus(except) {
      picks.forEach(function (pick) {
        if (pick === except) {
          return;
        }
        var menu = pick.querySelector('.kmae-swipe-empty__menu');
        var btn = pick.querySelector('.kmae-swipe-empty__chip');
        if (menu) {
          menu.hidden = true;
        }
        if (btn) {
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }

    function readCash() {
      state.kpMin = getCurrencyValue(kpMinInput);
      state.kpMax = getCurrencyValue(kpMaxInput);
    }

    function dirty() {
      return state.county !== origin.county || state.body !== origin.body || state.mod !== origin.mod || state.kpMin !== origin.kpMin || state.kpMax !== origin.kpMax;
    }

    function refresh() {
      readCash();
      var changed = dirty();
      if (cash) {
        cash.hidden = state.mod !== 'kp' || origin.mod === 'kp';
      }
      if (!go) {
        return;
      }
      go.hidden = !changed;
      if (!changed) {
        return;
      }
      var url = new URL(root.getAttribute('data-base'), window.location.href);
      url.searchParams.set('megye', state.county);
      url.searchParams.set('kivitel', state.body);
      url.searchParams.set('mod', state.mod);
      url.searchParams.delete('osszes');
      url.searchParams.delete('step');
      if (state.mod === 'kp') {
        url.searchParams.set('kp_min', String(state.kpMin || ''));
        url.searchParams.set('kp_max', String(state.kpMax || ''));
      } else {
        url.searchParams.delete('kp_min');
        url.searchParams.delete('kp_max');
      }
      go.href = url.toString();
    }

    picks.forEach(function (pick) {
      var btn = pick.querySelector('.kmae-swipe-empty__chip');
      var menu = pick.querySelector('.kmae-swipe-empty__menu');
      if (!btn || !menu) {
        return;
      }
      btn.addEventListener('click', function () {
        var open = menu.hidden;
        closeMenus(pick);
        menu.hidden = !open;
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      });
      menu.querySelectorAll('.kmae-swipe-empty__option').forEach(function (option) {
        option.addEventListener('click', function () {
          var key = option.getAttribute('data-kmae-filter');
          var value = option.getAttribute('data-value') || '';
          if (key === 'county') {
            state.county = value;
          } else if (key === 'body') {
            state.body = value;
          } else if (key === 'mod') {
            state.mod = value;
          }
          var label = pick.querySelector('[data-kmae-chip-label]');
          if (label) {
            label.textContent = option.textContent;
          }
          menu.querySelectorAll('.kmae-swipe-empty__option').forEach(function (item) {
            var on = item === option;
            item.classList.toggle('is-current', on);
            item.setAttribute('aria-selected', on ? 'true' : 'false');
          });
          menu.hidden = true;
          btn.setAttribute('aria-expanded', 'false');
          refresh();
        });
      });
    });

    bindCurrencyInput(kpMinInput, refresh);
    bindCurrencyInput(kpMaxInput, refresh);

    if (go) {
      go.addEventListener('click', function (event) {
        readCash();
        if (state.mod === 'kp' && (state.kpMin <= 0 || state.kpMax < state.kpMin)) {
          event.preventDefault();
          if (cash) {
            cash.hidden = false;
          }
          var focus = state.kpMin <= 0 ? kpMinInput : kpMaxInput;
          if (focus) {
            focus.focus();
          }
        }
      });
    }

    document.addEventListener('click', function (event) {
      if (!root.contains(event.target)) {
        closeMenus(null);
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMenus(null);
      }
    });
  }

  function initFilterRail() {
    var rail = document.getElementById('kmae-filter-rail');
    var form = document.getElementById('kmae-filter-form');
    var toggle = document.getElementById('kmae-filter-toggle');
    if (!rail || !form) {
      return;
    }

    function syncToggle(open) {
      if (!toggle) {
        return;
      }
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.classList.toggle('is-open', open);
    }

    function shut() {
      rail.classList.add('is-shut');
      syncToggle(false);
    }

    function open() {
      rail.classList.remove('is-shut');
      syncToggle(true);
    }

    rail.querySelectorAll('[data-kmae-filter-close]').forEach(function (node) {
      node.addEventListener('click', shut);
    });
    if (toggle) {
      var searchPulseSettled = false;
      function settleSearchPulse() {
        if (searchPulseSettled) {
          return;
        }
        searchPulseSettled = true;
        toggle.classList.add('is-search-settled');
      }
      toggle.addEventListener('click', function () {
        settleSearchPulse();
        if (rail.classList.contains('is-shut')) {
          open();
        } else {
          shut();
        }
      });
    }
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        shut();
      }
    });

    var catalog = window.kmaeBrowseCatalog || { brands: [], models: {} };
    var brandSelect = document.getElementById('kmae-filter-brand');
    var modelSelect = document.getElementById('kmae-filter-model');

    function fillModels() {
      if (!brandSelect || !modelSelect) {
        return;
      }
      var brand = brandSelect.value;
      var current = modelSelect.getAttribute('data-current') || '';
      var models = (catalog.models && catalog.models[brand]) || [];
      modelSelect.innerHTML = '';
      var any = document.createElement('option');
      any.value = '';
      any.textContent = 'Mindegy';
      modelSelect.appendChild(any);
      models.forEach(function (name) {
        var option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        if (name === current) {
          option.selected = true;
        }
        modelSelect.appendChild(option);
      });
      modelSelect.disabled = !brand;
      if (!brand) {
        modelSelect.value = '';
      }
    }

    if (brandSelect) {
      brandSelect.addEventListener('change', function () {
        if (modelSelect) {
          modelSelect.setAttribute('data-current', '');
        }
        fillModels();
      });
      fillModels();
    }

    /* Tiltó lista: márka (+ opcionális modell), amit a találatok soha nem mutatnak.
       A lista elemei <li data-brand data-model data-value>; küldéskor tilt[] lesz belőlük. */
    var blockList = document.getElementById('kmae-block-list');
    var blockBrand = document.getElementById('kmae-block-brand');
    var blockModel = document.getElementById('kmae-block-model');
    var blockAdd = document.getElementById('kmae-block-add');

    function syncBlockPicker() {
      if (!blockBrand || !blockModel || !blockAdd) {
        return;
      }
      var brand = blockBrand.value;
      var models = (brand && catalog.models && catalog.models[brand]) || [];
      blockModel.innerHTML = '';
      var all = document.createElement('option');
      all.value = '';
      all.textContent = 'Minden modell tiltva';
      blockModel.appendChild(all);
      models.forEach(function (name) {
        var option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        blockModel.appendChild(option);
      });
      blockModel.hidden = !brand || !models.length;
      blockAdd.hidden = !brand;
    }

    function addBlock() {
      if (!blockList || !blockBrand || !blockBrand.value) {
        return false;
      }
      var brand = blockBrand.value;
      var model = blockModel && !blockModel.hidden ? blockModel.value : '';
      var items = Array.prototype.slice.call(blockList.querySelectorAll('.kmae-filter-block__item'));
      var brandAll = items.some(function (item) {
        return item.getAttribute('data-brand') === brand && !item.getAttribute('data-model');
      });
      if (!brandAll) {
        if (!model) {
          /* Az egész márka tiltása felülírja az ugyanazon márka modelljeit. */
          items.forEach(function (item) {
            if (item.getAttribute('data-brand') === brand) {
              item.remove();
            }
          });
        }
        var exists = items.some(function (item) {
          return item.getAttribute('data-brand') === brand && item.getAttribute('data-model') === model;
        });
        if (!exists) {
          var li = document.createElement('li');
          li.className = 'kmae-filter-block__item';
          li.setAttribute('data-brand', brand);
          li.setAttribute('data-model', model);
          li.setAttribute('data-value', model ? brand + '|' + model : brand);
          var label = document.createElement('span');
          label.textContent = model ? brand + ' ' + model : brand;
          var remove = document.createElement('button');
          remove.type = 'button';
          remove.setAttribute('data-kmae-block-remove', '');
          remove.setAttribute('aria-label', label.textContent + ' tiltásának törlése');
          remove.textContent = '×';
          li.appendChild(label);
          li.appendChild(remove);
          blockList.appendChild(li);
        }
      }
      blockBrand.value = '';
      syncBlockPicker();
      return true;
    }

    /* A Tiltó lista a Részletes keresés legalján él: utólagos szűrés,
       ezért a változás azonnal újratölti a mostani találatokat (a többi szűrő marad). */
    function applyBlocks() {
      var url = new URL(window.location.href);
      Array.from(url.searchParams.keys()).forEach(function (key) {
        if (key === 'tilt' || key.indexOf('tilt[') === 0 || key === 'oldal') {
          url.searchParams.delete(key);
        }
      });
      blockList.querySelectorAll('.kmae-filter-block__item').forEach(function (item) {
        var value = item.getAttribute('data-value');
        if (value) {
          url.searchParams.append('tilt[]', value);
        }
      });
      window.location.href = url.toString();
    }

    if (blockBrand) {
      blockBrand.addEventListener('change', syncBlockPicker);
    }
    if (blockAdd) {
      blockAdd.addEventListener('click', function () {
        if (addBlock()) {
          applyBlocks();
        }
      });
    }
    if (blockList) {
      blockList.addEventListener('click', function (event) {
        var button = event.target.closest ? event.target.closest('[data-kmae-block-remove]') : null;
        if (button && blockList.contains(button)) {
          button.parentNode.remove();
          applyBlocks();
        }
      });
    }

    form.querySelectorAll('input[type="text"]').forEach(function (input) {
      input.addEventListener('input', function () {
        var clean = input.value.replace(/\D+/g, '');
        if (input.value !== clean) {
          input.value = clean;
        }
      });
    });

    form.querySelectorAll('.kmae-filter-range').forEach(bindRange);

    form.querySelectorAll('[data-group-any]').forEach(function (anyBox) {
      var group = anyBox.getAttribute('data-group-any');
      var boxes = form.querySelectorAll('input[name="' + group + '[]"]');
      anyBox.addEventListener('change', function () {
        if (anyBox.checked) {
          boxes.forEach(function (box) {
            box.checked = false;
          });
        }
      });
      boxes.forEach(function (box) {
        box.addEventListener('change', function () {
          if (box.checked) {
            anyBox.checked = false;
          } else if (!form.querySelector('input[name="' + group + '[]"]:checked')) {
            anyBox.checked = true;
          }
        });
      });
    });

    var motAny = form.querySelector('[data-mot-any]');
    var motRange = form.querySelector('.kmae-filter-mot .kmae-filter-range');
    function syncMot() {
      if (!motAny || !motRange) {
        return;
      }
      var off = motAny.checked;
      motRange.querySelectorAll('input').forEach(function (input) {
        input.disabled = off;
      });
      motRange.classList.toggle('is-off', off);
    }
    if (motAny) {
      motAny.addEventListener('change', syncMot);
      syncMot();
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var params = new URLSearchParams();
      params.set('mod', 'mind');

      var brand = val('marka');
      var model = val('modell');
      if (brand) {
        params.set('marka', brand);
      }
      if (brand && model) {
        params.set('modell', model);
      }

      /* A kiválasztott, de még nem hozzáadott tiltást is számoljuk. */
      addBlock();
      if (blockList) {
        blockList.querySelectorAll('.kmae-filter-block__item').forEach(function (item) {
          var value = item.getAttribute('data-value');
          if (value) {
            params.append('tilt[]', value);
          }
        });
      }

      setNum(params, 'ccm_min');
      setNum(params, 'ccm_max');
      setNum(params, 'le_min');
      setNum(params, 'le_max');
      setNum(params, 'ev_min');
      setNum(params, 'ev_max');
      setNum(params, 'km_min');
      setNum(params, 'km_max');

      var fuel = val('uzem');
      var gear = val('valto');
      var body = val('kivitel') || 'mind';
      if (fuel) {
        params.set('uzem', fuel);
      }
      if (gear) {
        params.set('valto', gear);
      }
      params.set('kivitel', body);

      if (motAny && motAny.checked) {
        params.set('muszaki', 'mind');
      } else {
        params.set('muszaki', 'sav');
        setNum(params, 'mot_min');
        setNum(params, 'mot_max');
        if (form.querySelector('input[name="mot_lejart"]') && form.querySelector('input[name="mot_lejart"]').checked) {
          params.set('mot_lejart', '1');
        }
      }

      params.set('megye', checkedList('megye', 'orszag'));
      var prices = checkedList('ar', '');
      if (prices) {
        params.set('ar', prices);
      }
      var cond = checkedList('allapot', '');
      if (cond) {
        params.set('allapot', cond);
      }

      window.location.href = rail.getAttribute('data-browse-url') + '?' + params.toString();
    });

    function val(name) {
      var field = form.elements[name];
      return field ? String(field.value || '').trim() : '';
    }

    function setNum(params, name) {
      var raw = val(name).replace(/\D+/g, '');
      if (raw && parseInt(raw, 10) > 0) {
        params.set(name, String(parseInt(raw, 10)));
      }
    }

    function checkedList(name, anyValue) {
      var any = form.querySelector('input[name="' + name + '_mind"]');
      if (any && any.checked) {
        return anyValue;
      }
      var picked = [];
      form.querySelectorAll('input[name="' + name + '[]"]:checked').forEach(function (box) {
        picked.push(box.value);
      });
      return picked.length ? picked.join(',') : anyValue;
    }
  }

  function bindRange(block) {
    var minInput = block.querySelector('input[type="text"][name$="_min"], input[type="text"][name="mot_min"]');
    var maxInput = block.querySelector('input[type="text"][name$="_max"], input[type="text"][name="mot_max"]');
    var minSlider = block.querySelector('input[type="range"][data-bound="min"]');
    var maxSlider = block.querySelector('input[type="range"][data-bound="max"]');
    var readout = block.querySelector('.kmae-filter-range__readout');
    if (!minSlider || !maxSlider || !readout) {
      return;
    }
    var months = block.getAttribute('data-months') === '1';
    var unit = block.getAttribute('data-unit') || '';
    var floor = parseInt(block.getAttribute('data-min'), 10) || 0;
    var ceil = parseInt(block.getAttribute('data-max'), 10) || 0;

    function clampPair(changed) {
      var lo = parseInt(minSlider.value, 10);
      var hi = parseInt(maxSlider.value, 10);
      if (lo > hi) {
        if (changed === 'min') {
          maxSlider.value = String(lo);
        } else {
          minSlider.value = String(hi);
        }
      }
    }

    function writeNumbers() {
      if (!minInput || !maxInput) {
        return;
      }
      var lo = parseInt(minSlider.value, 10);
      var hi = parseInt(maxSlider.value, 10);
      if (!months && lo <= floor && hi >= ceil) {
        minInput.value = '';
        maxInput.value = '';
        return;
      }
      minInput.value = String(lo);
      maxInput.value = hi >= ceil && !months ? '' : String(hi);
    }

    function paint() {
      var lo = parseInt(minSlider.value, 10);
      var hi = parseInt(maxSlider.value, 10);
      if (months) {
        readout.textContent = 'min. ' + formatSpan(lo) + ' – max. ' + formatSpan(hi);
        return;
      }
      if ((!minInput || minInput.value === '') && (!maxInput || maxInput.value === '')) {
        readout.textContent = readout.getAttribute('data-empty') || '';
        return;
      }
      var left = minInput && minInput.value ? formatNum(minInput.value) : '0';
      var right = maxInput && maxInput.value ? formatNum(maxInput.value) : '';
      readout.textContent = (right ? left + ' – ' + right : left) + (unit ? ' ' + unit : '');
    }

    function fromSlider(which) {
      clampPair(which);
      writeNumbers();
      paint();
    }

    function fromText() {
      var lo = minInput && minInput.value !== '' ? parseInt(minInput.value, 10) : floor;
      var hi = maxInput && maxInput.value !== '' ? parseInt(maxInput.value, 10) : ceil;
      if (isNaN(lo)) {
        lo = floor;
      }
      if (isNaN(hi)) {
        hi = ceil;
      }
      lo = Math.max(floor, Math.min(ceil, lo));
      hi = Math.max(floor, Math.min(ceil, hi));
      if (lo > hi) {
        hi = lo;
      }
      minSlider.value = String(lo);
      maxSlider.value = String(hi);
      paint();
    }

    minSlider.addEventListener('input', function () {
      fromSlider('min');
    });
    maxSlider.addEventListener('input', function () {
      fromSlider('max');
    });
    if (minInput) {
      minInput.addEventListener('input', fromText);
    }
    if (maxInput) {
      maxInput.addEventListener('input', fromText);
    }
    paint();
  }

  function formatNum(value) {
    return String(parseInt(value, 10) || 0).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function formatSpan(months) {
    months = parseInt(months, 10) || 0;
    if (months > 0 && months % 12 === 0) {
      var years = months / 12;
      return years + ' év';
    }
    if (months > 12) {
      return Math.floor(months / 12) + ' év ' + (months % 12) + ' hónap';
    }
    return months + ' hónap';
  }
})();
