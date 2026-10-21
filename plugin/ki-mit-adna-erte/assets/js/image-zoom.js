/*
 * Képnagyító (csak PC, min-width: 1025px) a megnyitott galériákban.
 *
 * Görgő = nagyítás / kicsinyítés a kurzor felé, +/− gomb (és a + / − billentyű), dupla kattintás
 * = nagyít / vissza; nagyítva a bal egérgombot nyomva tartva a kép húzással mozgatható. Nagyítva a
 * kép jobb alsó sarkában kis térkép: a teljes kép + a látott terület négyzete (rá kattintva / húzva
 * oda ugrik). „Teljes kép” gomb / Esc = vissza 100%-ra, a galéria nyitva marad (teljes képernyőn a
 * Chrome Keyboard Lock miatt az Esc először csak a nagyításból lép ki).
 *
 * A nézetek (a meglévő kódhoz nem nyúl, csak figyeli őket):
 *   böngészés    .kmae-sw2-media:fullscreen  (swipe-v2.js full())
 *   adatlap      .kmae-gallery:fullscreen    (public.js nagyító gomb)
 *   szerkesztő   .kmae-lightbox              (public.js, meglévő képek .kmae-zoomable)
 *                .kmae-simple-lightbox       (listing-field-guards.js „Fotók megtekintése”)
 *   varázsló     .kmae-upload-lightbox       (photo-motor.js, új képek)
 * A nagyított kép egy saját réteg PONTOSAN az eredeti kép fölött (a nézet gyökerében, így teljes
 * képernyőn is látszik); képváltáskor / bezáráskor magától visszaáll. Stílus: assets/css/image-zoom.css.
 */
(function () {
  'use strict';

  const PC = window.matchMedia ? window.matchMedia('(min-width: 1025px)') : { matches: true };
  const MAX = 6;
  const STEP = 1.5;
  const VIEWS = [
    ['.kmae-lightbox:not([hidden])', '.kmae-lightbox-img'],
    ['.kmae-simple-lightbox', '.kmae-simple-lightbox__img'],
    ['.kmae-upload-lightbox', 'img'],
  ];

  let raf = 0;
  let v = null;
  let ui = null;
  let key = '';
  let edge = null;
  let hiLoaded = false;
  let s = 1;
  let tx = 0;
  let ty = 0;
  let W = 0;
  let H = 0;
  let X = 0;
  let Y = 0;
  let sig = '';
  let drag = null;
  let locked = false;

  const fsEl = () => document.fullscreenElement || document.webkitFullscreenElement || null;

  function find() {
    if (!PC.matches) return null;
    const fs = fsEl();
    if (fs) {
      let img = null;
      if (fs.matches('.kmae-sw2-media')) img = fs.querySelector('.kmae-sw2-open .kmae-sw2-img') || fs.querySelector('.kmae-sw2-img');
      else if (fs.matches('.kmae-gallery')) img = fs.querySelector('.kmae-gallery-slide.active .kmae-gallery-img');
      return img ? { host: fs, img: img } : null;
    }
    for (let i = 0; i < VIEWS.length; i++) {
      const host = document.querySelector(VIEWS[i][0]);
      const img = host && host.querySelector(VIEWS[i][1]);
      if (img && img.getAttribute('src')) return { host: host, img: img };
    }
    return null;
  }

  /* A legnagyobb felbontású forrás (srcset legszélesebb eleme), különben ami látszik. */
  function bigSrc(img) {
    let best = img.currentSrc || img.src;
    let bw = 0;
    const set = img.getAttribute('srcset');
    if (set) {
      set.split(',').forEach((part) => {
        const m = part.trim().match(/^(\S+)\s+(\d+)w$/);
        if (m && +m[2] > bw) { bw = +m[2]; best = m[1]; }
      });
    }
    return best;
  }

  function build() {
    const wrap = document.createElement('div');
    wrap.className = 'kmae-iz';
    wrap.innerHTML =
      '<div class="kmae-iz__view" hidden><img class="kmae-iz__img" alt="" draggable="false"></div>' +
      '<div class="kmae-iz__bar" role="toolbar" aria-label="Nagyítás">' +
        '<button type="button" class="kmae-iz__btn" data-iz="out" aria-label="Kicsinyítés" title="Kicsinyítés (− vagy görgő)">' +
          '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h12"/></svg></button>' +
        '<span class="kmae-iz__pct" aria-live="polite">100%</span>' +
        '<button type="button" class="kmae-iz__btn" data-iz="in" aria-label="Nagyítás" title="Nagyítás (+ vagy görgő)">' +
          '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 12h12M12 6v12"/></svg></button>' +
        '<button type="button" class="kmae-iz__reset" data-iz="reset" hidden title="Vissza a teljes képhez (Esc)">' +
          '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 4H4v5M15 4h5v5M9 20H4v-5M15 20h5v-5"/></svg>Teljes kép</button>' +
      '</div>' +
      '<div class="kmae-iz__map" hidden aria-hidden="true"><img alt="" draggable="false"><span class="kmae-iz__frame"></span></div>';
    const q = (sel) => wrap.querySelector(sel);
    const o = {
      wrap: wrap,
      view: q('.kmae-iz__view'),
      img: q('.kmae-iz__img'),
      bar: q('.kmae-iz__bar'),
      pct: q('.kmae-iz__pct'),
      out: q('[data-iz="out"]'),
      inn: q('[data-iz="in"]'),
      reset: q('[data-iz="reset"]'),
      map: q('.kmae-iz__map'),
      mapImg: q('.kmae-iz__map img'),
      frame: q('.kmae-iz__frame'),
    };
    bind(o);
    return o;
  }

  function clamp() {
    tx = Math.min(0, Math.max(W - W * s, tx));
    ty = Math.min(0, Math.max(H - H * s, ty));
  }

  function lock(on) {
    const kb = navigator.keyboard;
    if (!kb || !kb.lock) return;
    on = on && !!fsEl();
    if (on === locked) return;
    locked = on;
    if (on) {
      const r = kb.lock(['Escape']);
      if (r && r.catch) r.catch(() => {});
    } else {
      kb.unlock();
    }
  }

  function render() {
    if (!ui) return;
    const z = s > 1;
    ui.view.hidden = !z;
    ui.map.hidden = !z;
    ui.reset.hidden = !z;
    ui.out.disabled = !z;
    ui.inn.disabled = s >= MAX - 0.001;
    ui.pct.textContent = Math.round(s * 100) + '%';
    ui.wrap.classList.toggle('is-zoomed', z);
    if (z) {
      ui.img.style.width = (W * s) + 'px';
      ui.img.style.height = (H * s) + 'px';
      ui.img.style.transform = 'translate(' + tx + 'px, ' + ty + 'px)';
      const f = ui.frame.style;
      f.left = (-tx / (W * s) * 100) + '%';
      f.top = (-ty / (H * s) * 100) + '%';
      f.width = (100 / s) + '%';
      f.height = (100 / s) + '%';
    }
    lock(z);
  }

  function resetZoom() {
    s = 1;
    tx = 0;
    ty = 0;
    drag = null;
    if (ui) ui.wrap.classList.remove('is-dragging');
    render();
  }

  function zoomAt(ns, cx, cy) {
    if (!v || !W || !H) return;
    ns = Math.min(MAX, Math.max(1, ns));
    if (ns <= 1.001) { resetZoom(); return; }
    if (!hiLoaded) loadHi();
    tx = cx - (cx - tx) * ns / s;
    ty = cy - (cy - ty) * ns / s;
    s = ns;
    clamp();
    render();
  }

  function loadHi() {
    hiLoaded = true;
    const k = key;
    const best = bigSrc(v.img);
    if (!best || best === ui.img.getAttribute('src')) return;
    const pre = new Image();
    pre.onload = () => { if (ui && key === k) ui.img.src = best; };
    pre.src = best;
  }

  function setSources() {
    const src = v.img.currentSrc || v.img.src;
    ui.img.src = src;
    ui.mapImg.src = src;
    hiLoaded = false;
    const cs = getComputedStyle(v.img);
    const n = (p) => parseFloat(cs[p]) || 0;
    edge = {
      l: n('borderLeftWidth'), t: n('borderTopWidth'), r: n('borderRightWidth'), b: n('borderBottomWidth'),
      rad: Math.max(0, n('borderTopLeftRadius') - n('borderLeftWidth')),
      pos: cs.objectPosition || '50% 50%',
    };
    ui.img.style.objectPosition = edge.pos;
    ui.mapImg.style.objectPosition = edge.pos;
  }

  /* A réteg az eredeti kép tartalom-doboza fölé; a sáv bal lent, a térkép jobb lent. */
  function place() {
    const r = v.img.getBoundingClientRect();
    const bx = r.left + edge.l;
    const by = r.top + edge.t;
    const bw = r.width - edge.l - edge.r;
    const bh = r.height - edge.t - edge.b;
    if (bw < 60 || bh < 60) { ui.wrap.hidden = true; return; }
    ui.wrap.hidden = false;
    if (Math.abs(bw - W) > 0.5 || Math.abs(bh - H) > 0.5) {
      if (W && H && s > 1) { tx *= bw / W; ty *= bh / H; }
      W = bw;
      H = bh;
      clamp();
      render();
    }
    X = bx;
    Y = by;
    const o = ui.wrap.getBoundingClientRect();
    const lx = X - o.left;
    const ly = Y - o.top;
    const next = [lx, ly, W, H, edge.rad].map((x) => Math.round(x * 10)).join(',');
    if (next === sig) return;
    sig = next;
    const pad = 14;
    const a = W / H;
    let mw = 180;
    let mh = 130;
    if (a >= mw / mh) mh = mw / a; else mw = mh * a;
    const fit = Math.min(1, (W * 0.42) / mw, (H * 0.42) / mh);
    mw *= fit;
    mh *= fit;
    const vs = ui.view.style;
    vs.left = lx + 'px';
    vs.top = ly + 'px';
    vs.width = W + 'px';
    vs.height = H + 'px';
    vs.borderRadius = edge.rad + 'px';
    const bs = ui.bar.style;
    bs.left = (lx + pad) + 'px';
    bs.top = (ly + H - pad) + 'px';
    const narrow = W < mw + 330;
    const ms = ui.map.style;
    ms.width = mw + 'px';
    ms.height = mh + 'px';
    ms.left = (lx + W - pad - mw) + 'px';
    ms.top = (ly + H - pad - mh - (narrow ? ui.bar.offsetHeight + 10 : 0)) + 'px';
  }

  function teardown() {
    lock(false);
    if (ui) ui.wrap.remove();
    v = null;
    key = '';
    sig = '';
    s = 1;
    tx = 0;
    ty = 0;
    W = 0;
    H = 0;
    drag = null;
  }

  function tick() {
    raf = 0;
    const nv = find();
    if (!nv) { if (v) teardown(); return; }
    if (!ui) ui = build();
    if (ui.wrap.parentNode !== nv.host) { nv.host.appendChild(ui.wrap); sig = ''; }
    const k = nv.img.currentSrc || nv.img.src;
    const changed = !v || nv.img !== v.img || k !== key;
    v = nv;
    if (changed) {
      key = k;
      sig = '';
      W = 0;
      H = 0;
      setSources();
      resetZoom();
    }
    place();
    raf = requestAnimationFrame(tick);
  }

  function wake() {
    if (!raf && PC.matches) raf = requestAnimationFrame(tick);
  }

  const inside = (e) => v && ui && v.img.isConnected && !ui.wrap.hidden &&
    e.clientX >= X && e.clientX <= X + W && e.clientY >= Y && e.clientY <= Y + H;

  function bind(o) {
    ['pointerdown', 'mousedown', 'click', 'dblclick'].forEach((type) => {
      [o.bar, o.map, o.view].forEach((el) => el.addEventListener(type, (e) => e.stopPropagation()));
    });

    o.bar.addEventListener('click', (e) => {
      const b = e.target.closest('[data-iz]');
      if (!b) return;
      e.preventDefault();
      const what = b.getAttribute('data-iz');
      if (what === 'reset') resetZoom();
      else zoomAt(what === 'in' ? s * STEP : s / STEP, W / 2, H / 2);
    });

    /* Húzás: nagyítva a bal egérgombbal mozgatható. */
    o.view.addEventListener('pointerdown', (e) => {
      if (s <= 1 || e.button !== 0) return;
      e.preventDefault();
      drag = { id: e.pointerId, x: e.clientX, y: e.clientY, tx: tx, ty: ty };
      o.wrap.classList.add('is-dragging');
      try { o.view.setPointerCapture(e.pointerId); } catch (err) {}
    });
    o.view.addEventListener('pointermove', (e) => {
      if (!drag || e.pointerId !== drag.id) return;
      tx = drag.tx + e.clientX - drag.x;
      ty = drag.ty + e.clientY - drag.y;
      clamp();
      render();
    });
    const end = (e) => {
      if (!drag || e.pointerId !== drag.id) return;
      drag = null;
      o.wrap.classList.remove('is-dragging');
    };
    o.view.addEventListener('pointerup', end);
    o.view.addEventListener('pointercancel', end);

    /* Térkép: kattintás / húzás = oda ugrik a nézet. */
    let mapDrag = null;
    const jump = (e) => {
      const r = o.map.getBoundingClientRect();
      const fx = Math.min(1, Math.max(0, (e.clientX - r.left) / r.width));
      const fy = Math.min(1, Math.max(0, (e.clientY - r.top) / r.height));
      tx = W / 2 - fx * W * s;
      ty = H / 2 - fy * H * s;
      clamp();
      render();
    };
    o.map.addEventListener('pointerdown', (e) => {
      if (s <= 1 || e.button !== 0) return;
      e.preventDefault();
      mapDrag = e.pointerId;
      try { o.map.setPointerCapture(e.pointerId); } catch (err) {}
      jump(e);
    });
    o.map.addEventListener('pointermove', (e) => { if (mapDrag === e.pointerId) jump(e); });
    o.map.addEventListener('pointerup', () => { mapDrag = null; });
    o.map.addEventListener('pointercancel', () => { mapDrag = null; });
  }

  /* Görgő a kép fölött: nagyítás a kurzor felé (a kép alatti oldal nem görget). */
  window.addEventListener('wheel', (e) => {
    if (!inside(e) || e.ctrlKey) return;
    e.preventDefault();
    e.stopPropagation();
    const dy = e.deltaY * (e.deltaMode === 1 ? 33 : e.deltaMode === 2 ? 400 : 1);
    const f = Math.min(1.6, Math.max(0.6, Math.exp(-dy * 0.0022)));
    zoomAt(s * f, e.clientX - X, e.clientY - Y);
  }, { capture: true, passive: false });

  /* Dupla kattintás: nagyít a pontra / vissza a teljes képhez. */
  window.addEventListener('dblclick', (e) => {
    if (!inside(e) || (e.target.closest && e.target.closest('.kmae-iz__bar, .kmae-iz__map'))) return;
    e.preventDefault();
    e.stopPropagation();
    if (s > 1) resetZoom();
    else zoomAt(2.5, e.clientX - X, e.clientY - Y);
  }, true);

  /* Esc nagyítva: csak a nagyításból lép ki (a galéria nyitva marad); + / − / 0 billentyű. */
  window.addEventListener('keydown', (e) => {
    setTimeout(wake, 0);
    if (!v || !ui || e.altKey || e.ctrlKey || e.metaKey) return;
    const t = e.target;
    if (t && t.closest && t.closest('input, textarea, select, [contenteditable="true"]')) return;
    let hit = true;
    if (e.key === 'Escape' && s > 1) resetZoom();
    else if (e.key === '+' || e.key === '=') zoomAt(s * STEP, W / 2, H / 2);
    else if (e.key === '-') zoomAt(s / STEP, W / 2, H / 2);
    else if (e.key === '0' && s > 1) resetZoom();
    else hit = false;
    if (hit) {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true);

  /* A nézetek kattintásra / teljes képernyőre nyílnak: ilyenkor indul a követés. */
  document.addEventListener('click', () => setTimeout(wake, 0), true);
  document.addEventListener('fullscreenchange', wake);
  document.addEventListener('webkitfullscreenchange', wake);
})();
