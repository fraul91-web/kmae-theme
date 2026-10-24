/* Telefonos „Lapozó” (álló telefon, teljes képernyős, TikTok-szerű autónézegető a böngészés oldalon).
   Szerződés (markup, osztályok): a Lapozó feladatleírása; kinézet: theme assets/css/browse-lapozo.css.
   Adat: #kmae-sw2-data (kártyás nézet) vagy #kmae-lpz-data (lista nézet), = swipe-v2.js.
   PC-n (min-width:1025px betöltéskor) semmit nem csinál, DOM-ot sem szúr be. */
(function () {
  'use strict';

  const PHONE = window.matchMedia('(max-width: 1024px)');
  const PORTRAIT = window.matchMedia('(max-width: 1024px) and (orientation: portrait)');
  if (!PHONE.matches) return;

  const dataEl = document.getElementById('kmae-sw2-data') || document.getElementById('kmae-lpz-data');
  if (!dataEl) return;
  let data;
  try { data = JSON.parse(dataEl.textContent); } catch (e) { return; }
  if (!data || !data.feed) return;

  const cfg = data.cfg || {};
  const feed = data.feed;
  const me = data.me || null;
  const S = feed.ids || [];
  const order = me ? personalize(S, feed.ranks || null, data.base_ids || S, me) : S.slice();
  if (!order.length) return;

  const STORE = 'kmaeBrowseListing'; /* = swipe-v2.js remember() */
  const HINT = 'kmaeLpzHint';
  const AHEAD = 5; /* ennyi következő autó adatát töltjük előre */
  const PER = feed.per_page || 12;
  const MS = 260;
  const EASE = 'transform ' + MS + 'ms cubic-bezier(.2,.8,.2,1)';
  const TXT = {
    open: 'Ajánlatot vár',
    reserved: 'Foglalt',
    fav: 'Kedvenc',
    info: 'Részletek',
    msg: 'Üzenet',
    start: 'Lapozás',
    cars: 'autó',
    photo: 'fotó'
  };

  const sIndex = {}, byId = {}, loaded = { 1: 1 }, pending = {}, fav = {}, warm = {}, docs = {};
  S.forEach((id, i) => { sIndex[id] = i; });
  if (me && me.favorites) me.favorites.forEach((id) => { fav[id] = 1; });
  add(feed.items);
  Object.keys(data.pages || {}).forEach((p) => { loaded[p] = 1; add(data.pages[p]); });

  function add(items) { (items || []).forEach((it) => { if (it && it.card) byId[it.id] = it; }); }

  const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => '&#' + c.charCodeAt(0) + ';');
  const photos = (it) => (it && it.card && it.card.photos) || [];
  const srcset = (p) => [['s', 480], ['m', 800], ['l', 1200]].filter((x) => p[x[0]]).map((x) => p[x[0]] + ' ' + x[1] + 'w').join(', ');
  const indexOf = (id) => order.findIndex((x) => String(x) === String(id));
  const store = (fn) => { try { return fn(); } catch (e) { return null; } };

  /* Lucide-stílusú ikonok (24×24, stroke currentColor). */
  const ICON = {
    x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
    play: '<polygon points="6 3 20 12 6 21 6 3"/>',
    heart: '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>',
    info: '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
    msg: '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
    pin: '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    down: '<path d="m6 9 6 6 6-6"/>'
  };
  const svg = (k) => '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' + ICON[k] + '</svg>';

  /* ---------- Belépő gomb ---------- */
  const wrap = document.querySelector('.kmae-swipe-browse');
  if (wrap) {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'kmae-lpz-start';
    b.setAttribute('data-kmae-lpz-start', '');
    b.innerHTML = '<span class="kmae-lpz-start__icon" aria-hidden="true">' + svg('play') + '</span>' +
      '<span class="kmae-lpz-start__label">' + esc(TXT.start) + '</span>' +
      '<span class="kmae-lpz-start__count">' + esc(order.length + ' ' + TXT.cars) + '</span>';
    wrap.insertBefore(b, wrap.firstChild);
    b.addEventListener('click', () => {
      /* Kártya nézetben a kinti aktuális kártyától, különben az elejéről. */
      const c = document.querySelector('[data-kmae-sw2-card][data-role="current"]');
      const i = c ? indexOf(c.getAttribute('data-id')) : 0;
      openAt(Math.max(0, i), b);
    });
  }

  /* ---------- Belépés autóra koppintva (csak álló telefonon) ---------- */
  let down = null;
  document.addEventListener('pointerdown', (e) => { down = { x: e.clientX, y: e.clientY }; }, true);
  document.addEventListener('click', (e) => {
    if (isOpen || !PORTRAIT.matches || e.defaultPrevented) return;
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button > 0) return;
    const t = e.target;
    if (!t || !t.closest || (lpz && lpz.contains(t))) return;
    /* Húzás utáni kattintás nem megnyitás. */
    if (e.detail !== 0 && down && Math.hypot(e.clientX - down.x, e.clientY - down.y) > 10) return;
    let id = null, from = null;
    const card = t.closest('[data-kmae-sw2-card]');
    if (card) {
      const a = t.closest('.kmae-sw2-open, .kmae-sw2-cta');
      if (!a) return;
      id = card.getAttribute('data-id');
      from = a;
    } else {
      const row = t.closest('article.kmae-browse-row[data-id]');
      if (!row) return;
      /* A sor saját gombjai (pl. kedvenc) maradnak a sajátjuk. */
      if (t.closest('button, input, select, textarea, label, summary')) return;
      id = row.getAttribute('data-id');
      from = t.closest('a') || row;
    }
    const i = indexOf(id);
    if (i < 0) return;
    e.preventDefault();
    e.stopPropagation();
    openAt(i, from);
  }, true);

  /* ---------- Állapot ---------- */
  let lpz = null, track = null, slots = [], endEl = null, burstEl = null, hintEl = null, countEl = null, closeBtn = null;
  let sheet = null, sheetBody = null, sheetLoading = null;
  let isOpen = false, opener = null, cur = 0, busy = false, needRefresh = false, trackH = 0, sizeRaf = 0;
  let drag = null, press = null, lastTap = null, raf = 0, mute = false;
  let zoom = null, zoomHold = false, zoomRaf = 0;
  let sheetOpen = false, sheetFor = null, sheetTok = 0, sheetHideT = 0, sd = null, sdRaf = 0;
  let burstT = 0, settleT = 0;

  const slotAt = (pos) => slots.find((s) => s._pos === pos);

  /* ---------- Overlay (egyszer épül) ---------- */
  function build() {
    if (lpz) return;
    lpz = document.createElement('div');
    lpz.className = 'kmae-lpz';
    lpz.setAttribute('data-kmae-lpz', '');
    lpz.setAttribute('role', 'dialog');
    lpz.setAttribute('aria-modal', 'true');
    lpz.setAttribute('aria-label', 'Lapozó');
    lpz.hidden = true;
    const sec = (role) => '<section class="kmae-lpz-card" data-kmae-lpz-card data-role="' + role + '" style="touch-action:pan-x" hidden></section>';
    lpz.innerHTML =
      '<div class="kmae-lpz__top" style="touch-action:none">' +
        '<button type="button" class="kmae-lpz__close" data-kmae-lpz-close aria-label="Bezárás">' + svg('x') + '</button>' +
        '<span class="kmae-lpz__count" data-kmae-lpz-count></span>' +
      '</div>' +
      '<div class="kmae-lpz__track" data-kmae-lpz-track style="touch-action:pan-x">' + sec('prev') + sec('current') + sec('next') + '</div>' +
      '<div class="kmae-lpz__burst" data-kmae-lpz-burst aria-hidden="true">' + svg('heart') + '</div>' +
      '<div class="kmae-lpz__hint" data-kmae-lpz-hint hidden>' +
        '<div class="kmae-lpz__hint-row" data-gesture="up"><i></i><span>Húzd fel: következő autó</span></div>' +
        '<div class="kmae-lpz__hint-row" data-gesture="side"><i></i><span>Húzd oldalra: a fotók</span></div>' +
        '<div class="kmae-lpz__hint-row" data-gesture="tap2"><i></i><span>Dupla koppintás: kedvenc</span></div>' +
        '<button type="button" class="kmae-lpz__hint-ok" data-kmae-lpz-hint-ok>Értem</button>' +
      '</div>' +
      '<div class="kmae-lpz__end" data-kmae-lpz-end style="touch-action:none" hidden>' +
        '<p class="kmae-lpz__end-title">Elfogyott a találat</p>' +
        '<p class="kmae-lpz__end-text">Minden autót megnéztél ezzel a kereséssel.</p>' +
        '<button type="button" class="kmae-lpz__end-btn" data-kmae-lpz-close>Keresés módosítása</button>' +
      '</div>' +
      '<section class="kmae-lpz-sheet" data-kmae-lpz-sheet aria-label="Hirdetés adatlapja" hidden>' +
        '<div class="kmae-lpz-sheet__head" data-kmae-lpz-sheet-grab style="touch-action:none"><i class="kmae-lpz-sheet__grab"></i>' +
          '<button type="button" class="kmae-lpz-sheet__close" data-kmae-lpz-sheet-close aria-label="Adatlap bezárása">' + svg('down') + '</button></div>' +
        '<div class="kmae-lpz-sheet__body" data-kmae-lpz-sheet-body></div>' +
        '<div class="kmae-lpz-sheet__loading" data-kmae-lpz-sheet-loading hidden></div>' +
      '</section>';
    document.body.appendChild(lpz);
    const q = (s) => lpz.querySelector(s);
    track = q('[data-kmae-lpz-track]');
    slots = Array.from(track.querySelectorAll('[data-kmae-lpz-card]'));
    slots.forEach((s) => { s._pos = -9; s._ok = false; s._pi = 0; });
    endEl = q('[data-kmae-lpz-end]');
    burstEl = q('[data-kmae-lpz-burst]');
    hintEl = q('[data-kmae-lpz-hint]');
    countEl = q('[data-kmae-lpz-count]');
    closeBtn = q('.kmae-lpz__close');
    sheet = q('[data-kmae-lpz-sheet]');
    sheetBody = q('[data-kmae-lpz-sheet-body]');
    sheetLoading = q('[data-kmae-lpz-sheet-loading]');
    bind();
  }

  /* ---------- Kártya ---------- */
  function cardHtml(it, pos) {
    const actions = '<div class="kmae-lpz-card__actions">' +
      '<button type="button" class="kmae-lpz-btn kmae-lpz-btn--fav" data-kmae-lpz-fav aria-pressed="false">' + svg('heart') + '<span>' + TXT.fav + '</span></button>' +
      '<button type="button" class="kmae-lpz-btn kmae-lpz-btn--info" data-kmae-lpz-info>' + svg('info') + '<span>' + TXT.info + '</span></button>' +
      '<button type="button" class="kmae-lpz-btn kmae-lpz-btn--msg" data-kmae-lpz-msg>' + svg('msg') + '<span>' + TXT.msg + '</span></button>' +
      '</div>';
    const stage = (inner) => '<div class="kmae-lpz-card__stage" data-kmae-lpz-stage style="touch-action:pan-x">' + inner + '</div>';
    const strip = (figs) => '<div class="kmae-lpz-card__photos" data-kmae-lpz-photos style="touch-action:pan-x;overscroll-behavior-x:contain">' + figs + '</div>';
    const empty = '<figure class="kmae-lpz-card__photo"></figure>';
    /* Még nem töltött adat: csontváz (üres színpad). */
    if (!it) return stage(strip(empty)) + '<div class="kmae-lpz-card__info"></div>' + actions;

    const c = it.card;
    const ph = photos(it);
    const n = ph.length;
    const title = (c.title && c.title.text) || '';
    let st = '';
    if (n) {
      st += '<div class="kmae-lpz-card__bars" data-kmae-lpz-bars>' + ph.map((p, i) => (i ? '<i></i>' : '<i class="is-on"></i>')).join('') + '</div>';
      st += strip(ph.map((p, i) => '<figure class="kmae-lpz-card__photo"><img src="' + esc(p.m || p.s) + '" srcset="' + esc(srcset(p)) + '" sizes="100vw"' +
        ' alt="' + esc(title + ' – ' + (i + 1) + '. ' + TXT.photo) + '"' + (p.w && p.h ? ' width="' + (p.w | 0) + '" height="' + (p.h | 0) + '"' : '') +
        ' decoding="async" draggable="false" loading="' + (i === 0 && pos === cur ? 'eager' : 'lazy') + '"></figure>').join(''));
    } else {
      st += strip(empty);
    }
    if (it.reserved) st += '<span class="kmae-lpz-card__reserved">' + TXT.reserved + '</span>';
    if (n) st += '<span class="kmae-lpz-card__pcount">1 / ' + n + '</span>';

    let info = '<h2 class="kmae-lpz-card__title">' + esc(title) + '</h2>';
    const pr = c.price;
    if (pr && pr.formatted) {
      info += '<p class="kmae-lpz-card__price" data-mode="' + esc(pr.mode) + '"><strong>' + esc(pr.formatted) + '</strong>' + (pr.label ? '<small>' + esc(pr.label) + '</small>' : '') + '</p>';
    } else if (c.value && c.value.formatted) {
      info += '<p class="kmae-lpz-card__price" data-mode="value"><strong>' + esc(c.value.formatted) + '</strong></p>';
    } else {
      info += '<p class="kmae-lpz-card__price" data-mode="open"><strong>' + TXT.open + '</strong></p>';
    }
    const all = c.facts || [];
    const KEYS = ['year', 'mileage', 'fuel', 'transmission'];
    let facts = all.filter((f) => KEYS.indexOf(f.field) >= 0);
    if (!facts.length) facts = all.slice(0, 4);
    if (facts.length) info += '<p class="kmae-lpz-card__facts">' + facts.map((f) => '<span>' + esc(f.value) + '</span>').join('') + '</p>';
    /* Város: a „Megye, Település” utolsó része. */
    const city = String(c.location || '').split(',').pop().trim();
    if (city) info += '<p class="kmae-lpz-card__loc">' + svg('pin') + '<span>' + esc(city) + '</span></p>';

    return stage(st) + '<div class="kmae-lpz-card__info">' + info + '</div>' + actions;
  }

  function fill(s, pos, fresh) {
    const id = order[pos];
    const it = byId[id] || null;
    s._pos = pos;
    s._ok = !!it;
    s._pi = 0;
    s._fresh = !!fresh;
    s._lazy = false;
    s.hidden = false;
    s.setAttribute('data-id', String(id));
    s.setAttribute('data-reserved', it && it.reserved ? '1' : '0');
    const p = photos(it)[0];
    s.style.setProperty('--kmae-ambient', (p && p.ambient) || 'transparent');
    if (it) s.removeAttribute('data-loading'); else s.setAttribute('data-loading', '');
    s.innerHTML = cardHtml(it, pos);
    syncFav(s);
  }

  /* A cur körüli 3 hely (prev/current/next) kártyái; a felszabadult kártya újrahasznosul.
     defer: autóváltáskor az új, képen kívüli kártya csak az animáció után épül fel (settle),
     hogy az elengedés pillanatában ne legyen hosszú fő-szál munka. */
  function assign(defer) {
    const want = [];
    for (let p = cur - 1; p <= cur + 1; p++) if (p >= 0 && p < order.length) want.push(p);
    const free = slots.filter((s) => want.indexOf(s._pos) < 0);
    want.forEach((p) => {
      const s = slotAt(p);
      if (!s) {
        const f = free.shift();
        if (defer && p !== cur) { f._pos = p; f._ok = false; f._fresh = true; f._lazy = true; f.hidden = true; }
        else fill(f, p, true);
      } else if (s._lazy ? !defer : (!s._ok && byId[order[p]])) fill(s, p, false);
    });
    free.forEach((s) => { s._pos = -9; s._lazy = false; s.hidden = true; });
    slots.forEach((s) => {
      if (s._pos < 0) return;
      const role = ['prev', 'current', 'next'][s._pos - cur + 1];
      if (s.getAttribute('data-role') !== role) s.setAttribute('data-role', role);
      if (role === 'current') s.removeAttribute('aria-hidden'); else s.setAttribute('aria-hidden', 'true');
    });
  }

  const tf = (k, off) => 'translate3d(0,' + (off ? 'calc(' + k + '% + ' + off + 'px)' : k + '%') + ',0)';

  /* Elhelyezés: off = ujj-eltolás px-ben; tr = transition (undefined: nem nyúl hozzá). Csak írás, nincs layout-olvasás. */
  function place(off, tr) {
    slots.forEach((s) => {
      if (s._pos < 0) return;
      if (tr !== undefined) s.style.transition = s._fresh ? '' : tr;
      s._fresh = false;
      s.style.transform = tf((s._pos - cur) * 100, off);
    });
    const k = order.length - cur; /* a „végképernyő” a order.length-edik hely */
    const show = k <= 1;
    if (endEl.hidden === show) { endEl.hidden = !show; endEl._fresh = show; }
    if (show) {
      if (tr !== undefined) endEl.style.transition = endEl._fresh ? '' : tr;
      endEl._fresh = false;
      endEl.style.transform = 'translate3d(0,' + Math.round(k * trackH + (off || 0)) + 'px,0)';
    }
  }

  function syncFav(s) {
    const on = !!fav[s.getAttribute('data-id')];
    s.setAttribute('data-favorite', on ? '1' : '0');
    const b = s.querySelector('[data-kmae-lpz-fav]');
    if (b) b.setAttribute('aria-pressed', String(on));
  }

  /* Autóváltás után: számláló, emlékezés, előtöltés. */
  function changed() {
    const n = order.length;
    countEl.textContent = Math.min(cur + 1, n) + ' / ' + n;
    remember();
    ensure();
    preload();
    const s = slotAt(cur);
    const img = s && s.querySelector('img');
    if (img && img.getAttribute('loading') !== 'eager') img.setAttribute('loading', 'eager');
  }

  function remember() {
    const id = order[Math.min(cur, order.length - 1)];
    store(() => sessionStorage.setItem(STORE, id));
  }

  /* ---------- Adat (= swipe-v2.js ensure()/load()) ---------- */
  function ensure() {
    for (let pos = Math.max(0, cur - 1); pos <= cur + AHEAD && pos < order.length; pos++) {
      const id = order[pos];
      if (byId[id] || sIndex[id] == null) continue;
      const p = Math.floor(sIndex[id] / PER) + 1;
      if (!loaded[p] && !pending[p]) load(p);
    }
  }

  function load(p) {
    pending[p] = 1;
    fetch(cfg.feedUrl + (cfg.feedUrl.indexOf('?') < 0 ? '?' : '&') + (feed.query ? feed.query + '&' : '') + 'page=' + p, { credentials: 'omit' })
      .then((r) => r.json())
      .then((d) => { loaded[p] = 1; add(d.items); })
      .catch(() => {})
      .then(() => { delete pending[p]; refresh(); });
  }

  function refresh() {
    if (!isOpen) return;
    if (busy || (drag && drag.on)) { needRefresh = true; return; }
    needRefresh = false;
    assign();
    preload();
  }

  function warmPhoto(p) {
    if (!p || !p.s || warm[p.s]) return;
    const img = warm[p.s] = new Image();
    img.sizes = '100vw';
    img.srcset = srcset(p);
    img.src = p.m || p.s;
  }

  function preload() {
    warmPhoto(photos(byId[order[cur + 1]])[0]);
    warmPhoto(photos(byId[order[cur - 1]])[0]);
    const s = slotAt(cur);
    warmPhoto(photos(byId[order[cur]])[(s ? s._pi : 0) + 1]);
  }

  /* ---------- Nyitás / zárás ---------- */
  function openAt(i, from) {
    if (isOpen || !PORTRAIT.matches) return;
    build();
    isOpen = true;
    opener = from || document.activeElement;
    cur = Math.max(0, Math.min(order.length - 1, i));
    busy = false; drag = null; press = null; lastTap = null;
    lpz.classList.remove('is-dragging', 'is-sheet');
    assign();
    slots.forEach((s) => { s._fresh = true; });
    endEl._fresh = true;
    lpz.hidden = false;
    document.documentElement.classList.add('kmae-lpz-open');
    trackH = track.clientHeight || window.innerHeight; /* egyszeri olvasás nyitáskor (és átméretezéskor) */
    place(0, '');
    changed();
    if (store(() => localStorage.getItem(HINT)) == null) hintEl.hidden = false;
    try { history.pushState({ kmaeLpz: 1 }, ''); } catch (e) {}
    try { closeBtn.focus({ preventScroll: true }); } catch (e) {}
  }

  function closeLpz() {
    if (!isOpen) return;
    isOpen = false;
    closeSheet(true);
    drag = null; press = null;
    endZoom(true);
    clearTimeout(settleT); busy = false;
    lpz.hidden = true;
    lpz.classList.remove('is-dragging', 'is-sheet');
    document.documentElement.classList.remove('kmae-lpz-open');
    remember();
    const id = order[Math.min(cur, order.length - 1)];
    /* A kinti kártyás nézet (swipe-v2.js) erre az autóra áll. */
    document.dispatchEvent(new CustomEvent('kmae:lpz-goto', { detail: { id: id } }));
    const row = document.querySelector('article.kmae-browse-row[data-id="' + String(id).replace(/"/g, '') + '"]');
    if (row) row.scrollIntoView({ block: 'center', behavior: 'instant' });
    const o = opener;
    opener = null;
    if (o && o.isConnected && o.focus) { try { o.focus({ preventScroll: true }); } catch (e) {} }
  }

  const hstate = () => (history.state && history.state.kmaeLpz) || 0;

  /* Forgatás / átméretezés: nyitva marad, csak a végképernyő eltolását számoljuk újra. */
  window.addEventListener('resize', () => {
    if (!isOpen || sizeRaf) return;
    sizeRaf = requestAnimationFrame(() => {
      sizeRaf = 0;
      trackH = track.clientHeight || window.innerHeight;
      if (!busy && !(drag && drag.on)) place(0);
    });
  });

  /* ✕ / „Keresés módosítása”: a Vissza gombbal azonos út (popstate zár). */
  function requestClose() {
    const s = hstate();
    if (s === 2) history.go(-2);
    else if (s === 1) history.back();
    else closeLpz();
  }

  function requestCloseSheet() {
    if (hstate() === 2) history.back();
    else closeSheet(false);
  }

  window.addEventListener('popstate', (e) => {
    if (!isOpen) return;
    const s = (e.state && e.state.kmaeLpz) || 0;
    if (sheetOpen && s < 2) closeSheet(false);
    if (s < 1) closeLpz();
  });

  /* Billentyűk: Escape = zárás (lap → Lapozó); ↑/↓ = autóváltás. A kinti swipe-v2.js ne kapja meg. */
  window.addEventListener('keydown', (e) => {
    if (!isOpen) return;
    if (document.querySelector('dialog[open]')) return;
    const tg = e.target;
    if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      if (sheetOpen) requestCloseSheet(); else requestClose();
      return;
    }
    if (tg && tg.closest && tg.closest('input, textarea, select, [contenteditable="true"]')) return;
    const k = e.key;
    if (k === 'ArrowLeft' || k === 'ArrowRight' || k === 'ArrowUp' || k === 'ArrowDown' || k === 'Enter' || k === ' ' || e.code === 'Space') {
      e.stopPropagation();
      if (!sheetOpen && !busy && (k === 'ArrowUp' || k === 'ArrowDown')) {
        e.preventDefault();
        const d = k === 'ArrowDown' ? 1 : -1;
        if (cur + d >= 0 && cur + d <= order.length) go(d);
      }
    }
  }, true);

  /* ---------- Autóváltás ---------- */
  function go(delta) {
    cur += delta;
    closeSheet(true);
    assign(true);
    lpz.classList.remove('is-dragging');
    place(0, EASE);
    changed();
    settle();
  }

  function snapBack() {
    lpz.classList.remove('is-dragging');
    place(0, EASE);
    settle();
  }

  function settle() {
    busy = true;
    clearTimeout(settleT);
    settleT = setTimeout(() => {
      busy = false;
      slots.forEach((s) => { s.style.transition = ''; });
      endEl.style.transition = '';
      slots.forEach((s) => { if (s._lazy) fill(s, s._pos, false); });
      if (needRefresh) refresh();
    }, MS + 20);
  }

  function paint() {
    raf = 0;
    if (!drag || !drag.on) return;
    const dy = drag.dy;
    const rubber = (dy > 0 && cur === 0) || (dy < 0 && cur >= order.length);
    place(Math.round(rubber ? dy * 0.25 : dy));
  }

  const speed = (s) => {
    if (s.length < 2) return 0;
    const a = s[0], b = s[s.length - 1];
    return b.t > a.t ? (b.y - a.y) / (b.t - a.t) : 0;
  };
  const sample = (arr, y, t) => {
    arr.push({ y: y, t: t });
    while (arr.length > 2 && t - arr[0].t > 100) arr.shift();
  };

  /* ---------- Súgó ---------- */
  function dismissHint() {
    if (!hintEl || hintEl.hidden) return;
    hintEl.hidden = true;
    store(() => localStorage.setItem(HINT, '1'));
  }

  /* ---------- Kedvenc ---------- */
  function goLogin() { if (cfg.loginUrl) window.location.href = cfg.loginUrl; }

  function setFav(id, on) {
    if (on) fav[id] = 1; else delete fav[id];
    slots.forEach((s) => { if (s.getAttribute('data-id') === String(id)) syncFav(s); });
  }

  const tell = (id, on) => document.dispatchEvent(new CustomEvent('kmae:favorite', { detail: { id: id, on: on, source: 'lpz' } }));

  function post(fields) {
    const body = new FormData();
    Object.keys(fields).forEach((k) => body.append(k, fields[k]));
    return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' });
  }

  function saveFav(id, on) {
    if (!cfg.loggedIn) { goLogin(); return; }
    const was = !!fav[id];
    if (was === on) return;
    setFav(id, on);
    tell(id, on);
    const back = () => { setFav(id, was); tell(id, was); };
    post({ action: on ? 'kmae_favorite_add' : 'kmae_favorite_remove', nonce: cfg.favoriteNonce, listing_id: id })
      .then((r) => r.json())
      .then((d) => {
        if (!d || !d.success) return back();
        const badge = document.querySelector('[data-kmae-favorites-unseen]');
        if (badge && d.data && typeof d.data.unseen !== 'undefined') {
          const count = parseInt(d.data.unseen, 10) || 0;
          badge.textContent = count;
          badge.toggleAttribute('hidden', count <= 0);
        }
      })
      .catch(back);
  }

  /* A kinti kártyás nézet (swipe-v2.js) kedvenc-váltása. */
  document.addEventListener('kmae:favorite', (e) => {
    const d = e.detail || {};
    if (d.source === 'lpz' || d.id == null) return;
    if (d.on) fav[d.id] = 1; else delete fav[d.id];
    if (lpz) setFav(d.id, !!d.on);
  });

  function burst(x, y) {
    const r = lpz.getBoundingClientRect();
    burstEl.style.setProperty('--x', Math.round(x - r.left) + 'px');
    burstEl.style.setProperty('--y', Math.round(y - r.top) + 'px');
    burstEl.classList.remove('is-on');
    void burstEl.offsetWidth; /* az animáció újraindul */
    burstEl.classList.add('is-on');
    clearTimeout(burstT);
    burstT = setTimeout(() => burstEl.classList.remove('is-on'), 700);
  }

  function tap(e, p) {
    const now = e.timeStamp;
    if (lastTap && now - lastTap.t < 300 && Math.hypot(e.clientX - lastTap.x, e.clientY - lastTap.y) < 30) {
      lastTap = null;
      const s = p.stage.closest('[data-kmae-lpz-card]');
      if (!s || s._pos !== cur || !s._ok) return;
      if (!cfg.loggedIn) { goLogin(); return; }
      burst(e.clientX, e.clientY);
      saveFav(order[cur], true); /* már kedvenc → marad */
    } else {
      lastTap = { t: now, x: e.clientX, y: e.clientY };
    }
  }

  /* ---------- Fotók ---------- */
  function photoIndex(box) {
    const s = box.closest('[data-kmae-lpz-card]');
    if (!s) return;
    const n = box.children.length;
    const i = Math.max(0, Math.min(n - 1, Math.round(box.scrollLeft / (box.clientWidth || 1))));
    if (i === s._pi) return;
    s._pi = i;
    const bars = s.querySelectorAll('[data-kmae-lpz-bars] > i');
    bars.forEach((b, j) => { if (b.classList.contains('is-on') !== (j === i)) b.classList.toggle('is-on', j === i); });
    const pc = s.querySelector('.kmae-lpz-card__pcount');
    if (pc) pc.textContent = (i + 1) + ' / ' + n;
    if (s._pos === cur) warmPhoto(photos(byId[order[cur]])[i + 1]);
  }

  /* Csípés-nagyítás (2 ujj): touch eseményekkel, hogy a natív görgetést meg lehessen fogni. */
  function startZoom(e) {
    const a = e.touches[0], b = e.touches[1];
    const fa = a.target.closest && a.target.closest('.kmae-lpz-card__photo');
    const fb = b.target.closest && b.target.closest('.kmae-lpz-card__photo');
    const img = fa && fa === fb && fa.querySelector('img');
    if (!img) return;
    const s = fa.closest('[data-kmae-lpz-card]');
    if (!s || s._pos !== cur) return;
    drag = null; press = null; lastTap = null;
    const mx = (a.clientX + b.clientX) / 2, my = (a.clientY + b.clientY) / 2;
    const r = img.getBoundingClientRect();
    img.style.transition = '';
    img.style.transformOrigin = Math.round(mx - r.left) + 'px ' + Math.round(my - r.top) + 'px';
    fa.classList.add('is-zoom');
    zoom = { img: img, fig: fa, d0: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY) || 1, mx: mx, my: my, s: 1, x: 0, y: 0 };
    zoomHold = true;
  }

  function paintZoom() {
    zoomRaf = 0;
    if (zoom) zoom.img.style.transform = 'translate3d(' + zoom.x + 'px,' + zoom.y + 'px,0) scale(' + zoom.s.toFixed(3) + ')';
  }

  function endZoom(now) {
    if (zoomRaf) { cancelAnimationFrame(zoomRaf); zoomRaf = 0; }
    const z = zoom;
    zoom = null;
    if (!z) return;
    const done = () => {
      z.fig.classList.remove('is-zoom');
      z.img.style.transition = '';
      z.img.style.transformOrigin = '';
    };
    if (now) { z.img.style.transform = ''; done(); return; }
    z.img.style.transition = 'transform 200ms ease-out';
    z.img.style.transform = '';
    setTimeout(done, 210);
  }

  /* ---------- Részletek lap ---------- */
  function sheetCss(doc) {
    const src = doc.getElementById('kmae-single-listing-css');
    if (!src || document.getElementById('kmae-single-listing-css')) return Promise.resolve();
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.id = src.id;
    link.href = src.getAttribute('href');
    return new Promise((done) => { link.onload = link.onerror = done; document.head.appendChild(link); });
  }

  /* A hirdetés .kmae-single-listing része (= swipe-v2.js openSheet() tisztítása), id szerint gyorsítótárazva. */
  function sheetDoc(id, url) {
    if (docs[id]) return docs[id];
    docs[id] = fetch(url, { credentials: 'same-origin' })
      .then((r) => { if (!r.ok) throw new Error(r.status); return r.text(); })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const art = doc.querySelector('.kmae-single-listing');
        if (!art) throw new Error('missing');
        [art].concat(Array.from(art.querySelectorAll('[data-kmae-live-page]'))).forEach((x) => {
          x.removeAttribute('data-kmae-live-page');
          x.removeAttribute('data-kmae-live-fingerprint');
        });
        art.querySelectorAll('.kmae-page-back, script').forEach((x) => x.remove());
        art.querySelectorAll('form').forEach((f) => { if (!f.getAttribute('action')) f.setAttribute('action', url); });
        return sheetCss(doc).then(() => art);
      });
    docs[id].catch(() => { delete docs[id]; });
    return docs[id];
  }

  function openSheet(id, toOffer) {
    const it = byId[id];
    if (!it) return;
    const url = it.card.url;
    if (!window.fetch || !window.DOMParser) { window.location.href = url; return; }
    dismissHint();
    const tok = ++sheetTok;
    const was = sheetOpen;
    sheetOpen = true;
    clearTimeout(sheetHideT);
    sheet.hidden = false;
    sheet.style.transition = '';
    sheet.style.transform = '';
    lpz.classList.add('is-sheet');
    if (!was) void sheet.offsetHeight; /* a felcsúszás a rejtett állapotból induljon */
    sheet.style.transition = EASE;
    sheet.classList.add('is-open');
    if (!was) { try { history.pushState({ kmaeLpz: 2 }, ''); } catch (e) {} }
    if (sheetFor === id && sheetBody.firstChild) { sheetScroll(toOffer); return; }
    sheetFor = null;
    sheetBody.replaceChildren();
    sheetBody.scrollTop = 0;
    sheetLoading.hidden = false;
    sheetDoc(id, url)
      .then((art) => {
        if (tok !== sheetTok) return;
        sheetBody.replaceChildren(document.importNode(art, true));
        sheetFor = id;
        sheetLoading.hidden = true;
        sheetBody.scrollTop = 0;
        if (toOffer) requestAnimationFrame(() => sheetScroll(true));
      })
      .catch(() => { if (tok === sheetTok) window.location.href = url; });
  }

  function sheetScroll(toOffer) {
    if (!toOffer) { sheetBody.scrollTop = 0; return; }
    const tg = sheetBody.querySelector('#kmae-offer');
    if (!tg) return;
    const top = tg.getBoundingClientRect().top - sheetBody.getBoundingClientRect().top + sheetBody.scrollTop;
    sheetBody.scrollTo({ top: Math.max(0, top - 12), behavior: 'smooth' });
  }

  function closeSheet(now) {
    if (!sheetOpen) return;
    sheetOpen = false;
    ++sheetTok;
    sd = null;
    if (sdRaf) { cancelAnimationFrame(sdRaf); sdRaf = 0; }
    lpz.classList.remove('is-sheet');
    sheet.style.transition = now ? 'none' : EASE;
    sheet.style.transform = '';
    sheet.classList.remove('is-open');
    sheetLoading.hidden = true;
    clearTimeout(sheetHideT);
    const done = () => { sheet.hidden = true; sheet.style.transition = ''; };
    if (now) done(); else sheetHideT = setTimeout(done, MS + 20);
  }

  /* ---------- Események ---------- */
  function bind() {
    /* Húzás utáni kattintás ne nyomjon gombot (a lapon belül nem fogjuk meg). */
    lpz.addEventListener('click', (e) => {
      if (mute && !(e.target.closest && e.target.closest('[data-kmae-lpz-sheet]'))) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    /* Érintéses koppintás a saját gombokon (és a lap mögötti fátylon) már a pointerup-on működik:
       gyors függőleges húzás után a Chrome a következő koppintást „lendület-megállításnak” veszi,
       és nem küld click-et (a gomb halottnak tűnne). Az utána mégis érkező click-et elnyeljük. */
    let tb = null, ate = null;
    lpz.addEventListener('pointerdown', (e) => {
      tb = null;
      const t = e.target;
      if (e.pointerType !== 'touch' || !e.isPrimary || !t || !t.closest || t.closest('[data-kmae-lpz-sheet-body]')) return;
      const el = t === lpz ? (sheetOpen ? lpz : null) : t.closest('button');
      if (el) tb = { el: el, t: t, id: e.pointerId, x: e.clientX, y: e.clientY };
    }, true);
    lpz.addEventListener('pointerup', (e) => {
      const p = tb;
      tb = null;
      if (!p || p.id !== e.pointerId || mute || Math.hypot(e.clientX - p.x, e.clientY - p.y) > 10) return;
      ate = { t: e.timeStamp, x: e.clientX, y: e.clientY };
      act(p.t, e);
    }, true);
    lpz.addEventListener('pointercancel', () => { tb = null; }, true);
    /* A window-on: ha a gomb közben bezárta a Lapozót, a click már a kinti oldalra esne. */
    window.addEventListener('click', (e) => {
      const a = ate;
      ate = null;
      if (a && e.timeStamp - a.t < 800 && Math.hypot(e.clientX - a.x, e.clientY - a.y) < 30) { e.preventDefault(); e.stopPropagation(); }
    }, true);

    lpz.addEventListener('click', (e) => { act(e.target, e); });

    function act(t, e) {
      if (!t || !t.closest) return;
      if (t.closest('[data-kmae-lpz-sheet-body]')) return;
      /* Koppintás a lap mögötti fátyolra (.kmae-lpz::after) = a lap bezárása. */
      if (t === lpz) { if (sheetOpen) requestCloseSheet(); return; }
      if (t.closest('[data-kmae-lpz-hint-ok]')) { dismissHint(); return; }
      if (t.closest('[data-kmae-lpz-sheet-close]')) { e.preventDefault(); requestCloseSheet(); return; }
      if (t.closest('[data-kmae-lpz-close]')) { e.preventDefault(); requestClose(); return; }
      const b = t.closest('[data-kmae-lpz-fav], [data-kmae-lpz-info], [data-kmae-lpz-msg]');
      if (!b) return;
      const s = b.closest('[data-kmae-lpz-card]');
      if (!s || s._pos !== cur || !s._ok) return;
      const id = order[cur];
      if (b.hasAttribute('data-kmae-lpz-fav')) {
        if (!cfg.loggedIn) goLogin(); else saveFav(id, !fav[id]);
      } else if (b.hasAttribute('data-kmae-lpz-info')) {
        openSheet(id, false);
      } else if (!cfg.loggedIn) {
        goLogin();
      } else {
        openSheet(id, true);
      }
    }

    /* Függőleges húzás: FEL = következő autó, LE = előző. Vízszintesen a natív fotó-sáv dolgozik. */
    lpz.addEventListener('pointerdown', (e) => {
      dismissHint();
      if (zoom || zoomHold) return;
      if (!e.isPrimary) { press = null; if (drag && !drag.on) drag = null; return; }
      if (e.button > 0) return;
      const t = e.target;
      if (!t.closest || t.closest('[data-kmae-lpz-sheet], .kmae-lpz__top')) return;
      if (sheetOpen || busy) return;
      const stage = t.closest('[data-kmae-lpz-stage]');
      press = { id: e.pointerId, x: e.clientX, y: e.clientY, moved: false, stage: stage && !t.closest('button, a') ? stage : null };
      drag = { id: e.pointerId, x: e.clientX, y: e.clientY, on: false, dy: 0, h: 0, s: [{ y: e.clientY, t: e.timeStamp }] };
    });

    lpz.addEventListener('pointermove', (e) => {
      if (press && e.pointerId === press.id && !press.moved && Math.hypot(e.clientX - press.x, e.clientY - press.y) > 10) press.moved = true;
      if (!drag || e.pointerId !== drag.id || zoom) return;
      const dx = e.clientX - drag.x;
      const dy = e.clientY - drag.y;
      if (!drag.on) {
        if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
        if (Math.abs(dx) >= Math.abs(dy)) { drag = null; return; }
        drag.on = true;
        drag.h = track.clientHeight || window.innerHeight; /* egyszeri olvasás a zárás pillanatában */
        lpz.classList.add('is-dragging');
        slots.forEach((s) => { s.style.transition = ''; });
        endEl.style.transition = '';
        try { lpz.setPointerCapture(e.pointerId); } catch (err) {}
      }
      drag.dy = dy;
      sample(drag.s, e.clientY, e.timeStamp);
      if (!raf) raf = requestAnimationFrame(paint);
    });

    const up = (e) => {
      if (press && e.pointerId === press.id) {
        const p = press;
        press = null;
        if (e.type === 'pointerup' && !p.moved && p.stage && !zoom && !zoomHold) tap(e, p);
      }
      if (!drag || e.pointerId !== drag.id) return;
      const d = drag;
      drag = null;
      if (raf) { cancelAnimationFrame(raf); raf = 0; }
      if (!d.on) return;
      mute = true;
      setTimeout(() => { mute = false; }, 350);
      sample(d.s, e.clientY, e.timeStamp);
      const v = speed(d.s);
      let delta = 0;
      if (e.type !== 'pointercancel') {
        if (Math.abs(v) > 0.5) delta = v < 0 ? 1 : -1;
        else if (Math.abs(d.dy) > d.h * 0.18) delta = d.dy < 0 ? 1 : -1;
      }
      if (delta && cur + delta >= 0 && cur + delta <= order.length) go(delta);
      else snapBack();
    };
    lpz.addEventListener('pointerup', up);
    lpz.addEventListener('pointercancel', up);
    lpz.addEventListener('dragstart', (e) => e.preventDefault());

    /* Fotó-sáv görgetése → csíkok + számláló (a scroll nem buborékol: capture). */
    track.addEventListener('scroll', (e) => {
      const box = e.target;
      if (!box || !box.hasAttribute || !box.hasAttribute('data-kmae-lpz-photos') || box._raf) return;
      box._raf = requestAnimationFrame(() => { box._raf = 0; photoIndex(box); });
    }, { capture: true, passive: true });

    track.addEventListener('touchstart', (e) => {
      if (e.touches.length === 2 && !zoom && !(drag && drag.on) && !busy && !sheetOpen) startZoom(e);
    }, { passive: true });
    track.addEventListener('touchmove', (e) => {
      if (!zoom) return;
      if (e.cancelable) e.preventDefault();
      if (e.touches.length < 2) return;
      const a = e.touches[0], b = e.touches[1];
      const d = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
      zoom.s = Math.max(1, Math.min(4, d / zoom.d0));
      zoom.x = Math.round((a.clientX + b.clientX) / 2 - zoom.mx);
      zoom.y = Math.round((a.clientY + b.clientY) / 2 - zoom.my);
      if (!zoomRaf) zoomRaf = requestAnimationFrame(paintZoom);
    }, { passive: false });
    const tend = (e) => {
      if (zoom && e.touches.length < 2) endZoom(false);
      if (!e.touches.length) zoomHold = false;
    };
    track.addEventListener('touchend', tend);
    track.addEventListener('touchcancel', tend);

    /* Lap lehúzása: a fejlécen, vagy a tartalmon, ha az a tetején van és lefelé húz. */
    sheet.addEventListener('touchstart', (e) => {
      sd = null;
      if (!sheetOpen || e.touches.length !== 1) return;
      const t = e.target;
      const head = t.closest && t.closest('[data-kmae-lpz-sheet-grab]');
      const body = !head && t.closest && t.closest('[data-kmae-lpz-sheet-body]');
      if (!head && !body) return;
      const p = e.touches[0];
      sd = { x: p.clientX, y: p.clientY, body: !!body, on: false, dy: 0, s: [{ y: p.clientY, t: e.timeStamp }] };
    }, { passive: true });
    sheet.addEventListener('touchmove', (e) => {
      if (!sd) return;
      const p = e.touches[0];
      const dx = p.clientX - sd.x, dy = p.clientY - sd.y;
      if (!sd.on) {
        /* A tartalomnál az első mozdulat dönt: felfelé / görgetett tartalom = natív görgetés. */
        if (sd.body && (sheetBody.scrollTop > 0 || dy <= 0)) { sd = null; return; }
        if (Math.abs(dx) > 8 && Math.abs(dx) > Math.abs(dy)) { sd = null; return; }
        if (dy <= 0) return;
        sd.on = true;
        sheet.style.transition = 'none';
      }
      if (e.cancelable) e.preventDefault();
      sd.dy = Math.max(0, dy);
      sample(sd.s, p.clientY, e.timeStamp);
      if (!sdRaf) {
        sdRaf = requestAnimationFrame(() => {
          sdRaf = 0;
          if (sd && sd.on) sheet.style.transform = 'translate3d(0,' + Math.round(sd.dy) + 'px,0)';
        });
      }
    }, { passive: false });
    const send = (e) => {
      const d = sd;
      sd = null;
      if (!d || !d.on) return;
      if (sdRaf) { cancelAnimationFrame(sdRaf); sdRaf = 0; }
      if (e.type !== 'touchcancel' && (d.dy > 120 || speed(d.s) > 0.5)) {
        requestCloseSheet();
      } else {
        sheet.style.transition = EASE;
        sheet.style.transform = '';
      }
    };
    sheet.addEventListener('touchend', send);
    sheet.addEventListener('touchcancel', send);
  }

  /* BROWSE-V2.md „Személyes adat a kliensen” (= swipe-v2.js personalize()). */
  function personalize(S, R, D, me) {
    const ex = new Set((me.own || []).concat(me.contacted || [])), ps = new Set(me.passed || []), fresh = [], pd = [], rank = {};
    D.forEach((i) => { if (!ex.has(i)) (ps.has(i) ? pd : fresh).push(i); });
    let m = fresh.length ? fresh : pd, k = 0;
    if (fresh.length && pd.length) {
      const n = Math.max(1, me.passed_interval | 0);
      m = [];
      fresh.forEach((id, i) => { m.push(id); if ((i + 1) % n === 0) m.push(pd[k++ % pd.length]); });
    }
    S.forEach((id, i) => { rank[id] = R ? R[i] : 0; });
    const kept = m.filter((id) => id in rank);
    return R ? kept.map((id, i) => [rank[id], i, id]).sort((a, b) => a[0] - b[0] || a[1] - b[1]).map((a) => a[2]) : kept;
  }
})();
