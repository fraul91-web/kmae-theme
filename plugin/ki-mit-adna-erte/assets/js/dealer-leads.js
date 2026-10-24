/* Kereskedők megkeresése: az eladó küldő ablaka (templates/partials/dealer-leads-dialog.php).
   Végpontok: kmae_dealer_leads_preview / kmae_dealer_leads_send (admin-ajax, nonce a configban). */
(function () {
  'use strict';

  var dialog = document.querySelector('[data-kmae-dl]');
  if (!dialog) { return; }

  var configs = {};
  try {
    configs = JSON.parse(dialog.querySelector('[data-kmae-dl-config]').textContent) || {};
  } catch (e) { configs = {}; }

  function $(sel) { return dialog.querySelector(sel); }
  var el = {
    car: $('[data-kmae-dl-car]'),
    body: $('[data-kmae-dl-body]'),
    unavailable: $('[data-kmae-dl-unavailable]'),
    stepPurpose: $('[data-kmae-dl-step="purpose"]'),
    stepPrice: $('[data-kmae-dl-step="price"]'),
    radii: $('[data-kmae-dl-radii]'),
    purposes: $('[data-kmae-dl-purposes]'),
    radiusResult: $('[data-kmae-dl-radius-result]'),
    purposeResult: $('[data-kmae-dl-purpose-result]'),
    contacted: $('[data-kmae-dl-contacted]'),
    priceLabel: $('[data-kmae-dl-price-label]'),
    price: $('[data-kmae-dl-price]'),
    priceMsg: $('[data-kmae-dl-price-msg]'),
    error: $('[data-kmae-dl-error]'),
    success: $('[data-kmae-dl-success]'),
    successMsg: $('[data-kmae-dl-success-msg]'),
    foot: $('[data-kmae-dl-foot]'),
    send: $('[data-kmae-dl-send]')
  };
  var notificationsUrl = dialog.getAttribute('data-notifications-url') || '';
  var DEFAULT_SEND = 'Elküldöm';

  var cfg = null;
  var state = { radius: null, picked: { sell: false, tradein: false }, purpose: null, touched: false, preview: null, sending: false, req: 0, ctrl: null, timer: 0 };

  function show(node, on) { if (node) { node.hidden = !on; } }
  function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); }
  function digits(v) { return String(v || '').replace(/\D+/g, ''); }
  function priceValue() { var d = digits(el.price.value); return d ? parseInt(d, 10) : 0; }

  /* Két független kapcsoló (sell / tradein) → a szerverre menő cél: sell | tradein | both | null (semmi). */
  function combined() {
    if (state.picked.sell && state.picked.tradein) { return 'both'; }
    if (state.picked.sell) { return 'sell'; }
    if (state.picked.tradein) { return 'tradein'; }
    return null;
  }

  function priceLabelFor(purpose) {
    if (purpose === 'both') { return 'Kereskedői ár (eladásnál és beszámításnál is)'; }
    return purpose === 'tradein' ? 'Ennyiért számítanám be' : 'Ennyiért adnám el kereskedőnek';
  }

  function dealersText(n) { return n + ' kereskedés'; }

  function buildChoices() {
    el.radii.innerHTML = '';
    (cfg.radii || []).forEach(function (r) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'kmae-dl__chip';
      b.setAttribute('role', 'radio');
      b.setAttribute('aria-checked', 'false');
      b.setAttribute('data-value', r.value);
      b.textContent = r.label;
      el.radii.appendChild(b);
    });
    el.purposes.innerHTML = '';
    (cfg.purposes || []).forEach(function (p) {
      var b = document.createElement('button');
      b.type = 'button';
      b.className = 'kmae-dl__card kmae-dl__card--' + p.value;
      b.setAttribute('role', 'checkbox');
      b.setAttribute('aria-checked', 'false');
      b.setAttribute('data-value', p.value);
      var t = document.createElement('span');
      t.className = 'kmae-dl__card-text';
      t.textContent = p.label;
      b.appendChild(t);
      el.purposes.appendChild(b);
    });
  }

  function mark(group, value) {
    Array.prototype.forEach.call(group.children, function (b) {
      var on = String(b.getAttribute('data-value')) === String(value);
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
  }

  function markPurposes() {
    Array.prototype.forEach.call(el.purposes.children, function (b) {
      var on = !!state.picked[b.getAttribute('data-value')];
      b.classList.toggle('is-active', on);
      b.setAttribute('aria-checked', on ? 'true' : 'false');
    });
  }

  function reset() {
    state.radius = null; state.purpose = null; state.touched = false; state.picked = { sell: false, tradein: false }; state.preview = null; state.sending = false;
    if (state.ctrl) { state.ctrl.abort(); }
    clearTimeout(state.timer);
    mark(el.radii, null); markPurposes();
    [el.radiusResult, el.purposeResult, el.contacted, el.stepPurpose, el.stepPrice, el.error, el.priceMsg, el.success, el.unavailable].forEach(function (n) { show(n, false); });
    show(el.body, true); show(el.foot, true);
    el.price.value = '';
    el.radiusResult.className = 'kmae-dl__result';
    el.purposeResult.className = 'kmae-dl__result';
    updateSend();
  }

  function post(action, data, signal) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('nonce', cfg.nonce);
    fd.append('listing_id', cfg.listingId);
    Object.keys(data).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin', signal: signal })
      .then(function (r) { return r.json(); });
  }

  function errMsg(res) {
    var d = res && res.data;
    if (d && d.message) { return d.message; }
    if (d && d.error && d.error.message) { return d.error.message; }
    return 'Valami nem sikerült. Próbáld újra egy kicsit később.';
  }

  function showError(msg) { el.error.textContent = msg || ''; show(el.error, !!msg); }

  function runPreview() {
    if (state.radius === null) { return; }
    clearTimeout(state.timer);
    state.timer = setTimeout(doPreview, 120);
  }

  function doPreview() {
    if (state.ctrl) { state.ctrl.abort(); }
    var ctrl = typeof AbortController === 'function' ? new AbortController() : null;
    state.ctrl = ctrl;
    var id = ++state.req;
    var data = { radius: state.radius };
    if (state.purpose) { data.purpose = state.purpose; }
    state.preview = null;
    updateSend();
    showError('');
    post(cfg.actions.preview, data, ctrl ? ctrl.signal : undefined).then(function (res) {
      if (id !== state.req) { return; }
      if (!res || !res.success) { showError(errMsg(res)); return; }
      state.preview = res.data;
      render();
    }).catch(function (e) {
      if (id !== state.req || (e && e.name === 'AbortError')) { return; }
      showError('Nem sikerült elérni a szervert. Próbáld újra.');
    });
  }

  function render() {
    var p = state.preview;
    if (!p) { return; }
    if (!state.purpose) {
      show(el.stepPurpose, true);
      show(el.contacted, false);
      show(el.stepPrice, false);
      if (state.touched) {
        el.purposeResult.className = 'kmae-dl__result is-empty';
        el.purposeResult.textContent = 'Válassz legalább egyet.';
        show(el.purposeResult, true);
      } else {
        show(el.purposeResult, false);
      }
      el.radiusResult.className = 'kmae-dl__result' + (p.in_radius > 0 ? ' is-ok' : ' is-empty');
      el.radiusResult.textContent = p.in_radius > 0
        ? dealersText(p.in_radius) + ' található ennyi km-en belül.'
        : 'Ebben a körzetben most nincs kereskedés, próbálj nagyobb távolságot.';
      show(el.radiusResult, true);
      if (p.in_radius === 0) { show(el.stepPurpose, false); show(el.stepPrice, false); }
    } else {
      var all = p.matching > 0 && p.new === 0;
      el.purposeResult.className = 'kmae-dl__result' + (p.matching > 0 ? (all ? ' is-empty' : ' is-ok') : ' is-empty');
      el.purposeResult.textContent = p.matching > 0
        ? dealersText(p.matching) + ' felel meg.'
        : 'Ebben a körzetben ehhez most nincs megfelelő kereskedés. Próbálj nagyobb távolságot vagy másik célt.';
      show(el.purposeResult, true);
      if (p.contacted > 0 && !all) {
        el.contacted.textContent = 'Ebből ' + p.contacted + '-nak már elküldted korábban, nekik nem küldjük újra.';
        show(el.contacted, true);
      } else if (all) {
        el.contacted.textContent = 'Az összes megfelelő kereskedésnek elküldted már ezt a hirdetést.';
        show(el.contacted, true);
      } else {
        show(el.contacted, false);
      }
      show(el.stepPrice, p.new > 0);
    }
    updateSend();
  }

  function priceState() {
    var v = priceValue();
    var min = cfg.priceMin || 50000, max = cfg.priceMax || 500000000;
    if (!v) { return { ok: false, msg: '', kind: '' }; }
    if (v < min) { return { ok: false, msg: 'Legalább ' + fmt(min) + ' Ft-ot adj meg.', kind: 'err' }; }
    if (v > max) { return { ok: false, msg: 'Legfeljebb ' + fmt(max) + ' Ft-ot adhatsz meg.', kind: 'err' }; }
    if (cfg.listingPrice && v > cfg.listingPrice) { return { ok: true, msg: 'Ez magasabb, mint a hirdetésed ára.', kind: 'warn' }; }
    return { ok: true, msg: '', kind: '' };
  }

  function updateSend() {
    var p = state.preview;
    var ready = !!(p && state.purpose && p.new > 0 && priceState().ok && !state.sending);
    el.send.disabled = !ready;
    el.send.textContent = p && state.purpose && p.new > 0 ? 'Elküldöm ' + p.new + ' kereskedésnek' : DEFAULT_SEND;
  }

  function onPrice() {
    var d = digits(el.price.value);
    if (d.length > 10) { d = d.slice(0, 10); }
    el.price.value = d ? fmt(parseInt(d, 10)) : '';
    var s = priceState();
    el.priceMsg.textContent = s.msg;
    el.priceMsg.className = 'kmae-dl__warn' + (s.kind === 'err' ? ' is-error' : '');
    show(el.priceMsg, !!s.msg);
    updateSend();
  }

  function send() {
    var p = state.preview;
    if (state.sending || el.send.disabled || !p) { return; }
    state.sending = true;
    el.send.disabled = true;
    el.send.textContent = 'Küldés…';
    showError('');
    post(cfg.actions.send, { radius: state.radius, purpose: state.purpose, price: priceValue() }).then(function (res) {
      state.sending = false;
      if (!res || !res.success) { showError(errMsg(res)); updateSend(); return; }
      el.successMsg.textContent = (res.data && res.data.message) || 'Elküldtük a megkeresést.';
      show(el.body, false); show(el.foot, false); show(el.success, true);
      var go = el.success.querySelector('a');
      if (go && notificationsUrl) { go.setAttribute('href', notificationsUrl); }
      document.dispatchEvent(new CustomEvent('kmae:dealer-leads-sent', { detail: { listingId: cfg.listingId, result: res.data } }));
    }).catch(function () {
      state.sending = false;
      showError('Nem sikerült elérni a szervert. Próbáld újra.');
      updateSend();
    });
  }

  function open(listingId) {
    cfg = configs[listingId];
    if (!cfg) { return; }
    reset();
    el.car.textContent = cfg.listingTitle || '';
    buildChoices();
    if (!cfg.available) {
      el.unavailable.textContent = cfg.reason || 'Ehhez a hirdetéshez most nem tudsz megkeresést küldeni.';
      show(el.unavailable, true);
      dialog.querySelector('[data-kmae-dl-step="radius"]').hidden = true;
      show(el.send, false);
    } else {
      dialog.querySelector('[data-kmae-dl-step="radius"]').hidden = false;
      show(el.send, true);
    }
    if (typeof dialog.showModal === 'function') { if (!dialog.open) { dialog.showModal(); } } else { dialog.setAttribute('open', ''); }
    document.documentElement.classList.add('kmae-dl-open');
  }

  function close() {
    if (state.ctrl) { state.ctrl.abort(); }
    if (typeof dialog.close === 'function' && dialog.open) { dialog.close(); } else { dialog.removeAttribute('open'); }
  }

  dialog.addEventListener('close', function () { document.documentElement.classList.remove('kmae-dl-open'); });

  document.addEventListener('click', function (e) {
    var opener = e.target.closest('[data-kmae-dealer-leads-open]');
    if (opener) {
      e.preventDefault();
      open(opener.getAttribute('data-kmae-dealer-leads-open'));
    }
  });

  dialog.addEventListener('click', function (e) {
    if (e.target === dialog || e.target.closest('[data-kmae-dl-close]')) { close(); return; }
    var chip = e.target.closest('.kmae-dl__chip');
    if (chip) { state.radius = chip.getAttribute('data-value'); mark(el.radii, state.radius); runPreview(); return; }
    var card = e.target.closest('.kmae-dl__card');
    if (card) {
      var key = card.getAttribute('data-value');
      state.picked[key] = !state.picked[key];
      state.touched = true;
      state.purpose = combined();
      markPurposes();
      state.preview = null; /* a régi szám ne maradjon látszani az új választáshoz */
      ++state.req;
      show(el.purposeResult, false); show(el.contacted, false); show(el.stepPrice, false);
      if (state.purpose) { el.priceLabel.textContent = priceLabelFor(state.purpose); }
      updateSend();
      runPreview();
      return;
    }
    if (e.target.closest('[data-kmae-dl-send]')) { send(); }
  });

  el.price.addEventListener('input', onPrice);
})();
