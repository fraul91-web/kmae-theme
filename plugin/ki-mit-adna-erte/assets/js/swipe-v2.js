/* Böngészés v2 kártyák. Markup: PROJECT-MEMORY/FEATURES/BROWSE-V2.md; card() = browse-swipe-v2.php $sw2_card;
   a húzás / gombok / billentyűk / AJAX jelentése = swipe.js. A footerben fut (a DOM kész). */
(function () {
  'use strict';

  const SLIDE_MS = 180; /* = browse-v2.css .is-anim */
  const AHEAD = 5; /* ≤ 4 hátralévő betöltött kártyánál jön a következő oldal */
  const STORE = 'kmaeBrowseListing';
  const SIZES = '100vw'; /* = browse-swipe-v2.php $sw2_sizes (PC-n a fotó kitölti a színpadot) */
  /* PC: a teljes színpados kártya nem fordul el, csak elúszik és elhalványul. */
  const PC = window.matchMedia('(min-width: 1025px)');
  /* Álló telefon: a fotó nem nyit adatlapot, a „Részletek” a hirdetés oldalára visz (mint PC-n). */
  const PORTRAIT = window.matchMedia('(max-width: 1024px) and (orientation: portrait)');

  const root = document.getElementById('kmae-sw2');
  const dataEl = document.getElementById('kmae-sw2-data');
  if (!root || !dataEl) return;
  const data = JSON.parse(dataEl.textContent);
  const { cfg, feed, me } = data;
  const t = cfg.t;
  const S = feed.ids || [];
  const order = me ? personalize(S, feed.ranks || null, data.base_ids || S, me) : S.slice();
  const sIndex = {}, byId = {}, loaded = { 1: 1 }, pending = {}, fav = {}, warm = {}, edges = {};
  let edgeCtx = null;
  S.forEach((id, i) => { sIndex[id] = i; });
  if (me) me.favorites.forEach((id) => { fav[id] = 1; });
  add(feed.items);
  Object.keys(data.pages || {}).forEach((p) => { loaded[p] = 1; add(data.pages[p]); });

  const $ = (sel, el) => (el || root).querySelector(sel);
  const stack = $('[data-kmae-sw2-stack]');
  const slots = Array.from(stack.querySelectorAll('[data-kmae-sw2-card]'));
  const endEl = $('[data-kmae-sw2-end]');
  const prevBtn = $('[data-kmae-sw2-prev]');
  const nextBtn = $('[data-kmae-sw2-next]');
  const passBtn = $('[data-kmae-sw2-pass]');
  const peeks = Array.from(root.querySelectorAll('[data-kmae-sw2-peek]'));
  const sheet = $('[data-kmae-sw2-sheet]');
  const sheetBody = sheet && $('[data-kmae-sw2-sheet-body]', sheet);
  let sheetId = null, sheetTok = 0;
  let cur = 0, busy = false, drag = null, mute = false, raf = 0;

  slots.forEach((el) => { el._pos = -9; }); /* -9 = üres hely (a -1 az „előző” pozíció) */
  /* A szerver kártyája marad, ha a kliens sorrendjében is ez az első. */
  if (slots[0].getAttribute('data-id') === String(order[0])) slots[0]._pos = 0;

  function add(items) { (items || []).forEach((it) => { if (it && it.card) byId[it.id] = it; }); }

  const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => '&#' + c.charCodeAt(0) + ';');
  const el = (tag, cls, inner, attrs) => '<' + tag + ' class="kmae-sw2-' + cls + '"' + (attrs || '') + '>' + inner + '</' + tag + '>';
  const photos = (it) => (it && it.card.photos) || [];
  const num = (v) => String(v).replace(/\B(?=(\d{3})+(?!\d))/g, ' '); /* = PHP number_format( $v, 0, ',', ' ' ) */

  const srcset = (p) => [['s', 480], ['m', 800], ['l', 1200]].filter((x) => p[x[0]]).map((x) => p[x[0]] + ' ' + x[1] + 'w').join(', ');

  function card(it, pos) {
    const c = it.card;
    const ph = photos(it);
    const n = ph.length;
    const p = ph[0];
    const ld = c.lead || {};
    let m = n
      ? '<img class="kmae-sw2-img" src="' + esc(p.m || p.s) + '" srcset="' + esc(srcset(p)) + '" sizes="' + SIZES + '"' +
        (p.w ? ' width="' + p.w + '" height="' + p.h + '"' : '') + ' alt="" decoding="async" draggable="false"' + (pos === cur ? ' fetchpriority="high"' : '') + '>'
      : el('span', 'nophoto', esc(t.noPhoto));
    m = el('a', 'open', m, ' href="' + esc(c.url) + '" draggable="false"');
    if (n > 1) {
      m += el('button', 'photo-prev', '‹', ' type="button" data-kmae-sw2-photo="-1" aria-label="' + esc(t.photoPrev) + '" hidden') +
        el('button', 'photo-next', '›', ' type="button" data-kmae-sw2-photo="1" aria-label="' + esc(t.photoNext) + '"');
    }
    if (n) {
      m += el('span', 'photo-count', '1 / ' + n, ' aria-hidden="true"') +
        el('button', 'full', '', ' type="button" data-kmae-sw2-full aria-label="' + esc(t.full) + '" title="' + esc(t.full) + '"');
    }
    if (n) m += el('span', 'dots', ph.map((x, i) => el('span', 'dot', '', i ? '' : ' data-current')).join(''), ' aria-hidden="true"');
    /* Bélyegkép-sáv: üres, a thumbs() tölti ki, amikor a kártya az aktuális (a szomszédok képei ne töltődjenek). */
    if (n > 1) m += el('div', 'thumbs', '', ' data-kmae-sw2-thumbs');
    if (it.reserved) m += el('span', 'reserved', esc(t.reserved), ' title="' + esc(t.reservedTitle) + '"');
    m += el('button', 'like', esc(t.save), ' type="button" data-kmae-sw2-like title="' + esc(t.saveTitle) + '" aria-pressed="false"');

    /* Fejléc (PC-n: logó bal fent, cím + eladó + helyszín felül középen): márka-logó, cím, LE/cm³, eladó, helyszín. */
    let h = (c.brand_logo ? el('span', 'brand', '<img src="' + esc(c.brand_logo) + '" alt="" width="44" height="44" decoding="async">') : '') +
      el('h3', 'title', esc(c.title.text));
    const bd = c.body && c.body.label ? c.body : null;
    if (ld.hp || ld.liters || bd) {
      h += el('p', 'lead', (ld.hp ? el('span', 'lead-hp', num(ld.hp) + ' LE') : '') +
        (ld.liters ? el('span', 'lead-cc', esc(ld.liters) + ' cm³') : '') +
        (bd ? el('span', 'lead-body', esc(bd.label), ' data-code="' + esc(bd.code) + '"') : ''));
    }
    const sl = c.seller || {};
    if (sl.name) h += el('p', 'seller', el('span', 'seller-name', esc(sl.name)) + (sl.type ? el('span', 'seller-type', esc(sl.type)) : ''));
    if (c.location) {
      h += el('p', 'place', el('span', 'loc', esc(c.location) + (it.distance_km != null
        ? el('em', 'km', '~' + num(Math.max(1, Math.round(it.distance_km))) + ' km') : '')));
    }
    let i = el('div', 'head', h);
    const pr = c.price;
    const val = (s) => el('span', 'price-value', esc(s));
    i += pr ? el('p', 'price', (pr.label ? el('span', 'price-label', esc(pr.label)) : '') + val(pr.formatted), ' data-mode="' + esc(pr.mode) + '"')
      : c.value ? el('p', 'price', val(c.value.formatted), ' data-mode="value"')
        : el('p', 'price', val(t.open), ' data-mode="open"');
    /* A lista nézet tényei + a kártyás nézet saját csempéi (teljesítmény, karosszéria) — = PHP $all_facts. */
    const facts = (c.facts || []).slice();
    if (ld.hp) facts.push({ field: 'power', code: '', value: num(ld.hp) + ' LE', label: t.power });
    if (c.body && c.body.label) facts.push({ field: 'body', code: c.body.code, value: c.body.label, label: t.body });
    if (facts.length) {
      i += el('ul', 'facts', facts.map((f) => el('li', 'fact', '<strong>' + esc(f.value) + '</strong><small>' + esc(f.label) + '</small>',
        ' data-field="' + esc(f.field) + '" data-code="' + esc(f.code) + '"')).join(''));
    }
    /* Kiegészítő sor (PC fekvő fotónál) — = PHP $extras. */
    const ex = (c.extras || []).slice();
    if (c.body && c.body.label) ex.unshift({ field: 'body', value: c.body.label, label: t.body });
    if (ex.length) {
      i += el('ul', 'extras', ex.map((f) => el('li', 'extra', '<strong>' + esc(f.value) + '</strong><small>' + esc(f.label) + '</small>',
        ' data-field="' + esc(f.field) + '"')).join(''));
    }
    /* PC: „Hirdetés megtekintése” — CSAK ez visz a hirdetés adatlapjára (rendes link, a kattintást nem fogjuk meg). */
    i += el('a', 'msg', esc(t.message), ' href="' + esc(c.url) + '"');
    i += el('a', 'cta', esc(t.details), ' href="' + esc(c.url) + '"');
    return el('div', 'media', m) + el('div', 'info', i);
  }

  function vars(node, p) {
    node.style.setProperty('--kmae-ambient', (p && p.ambient) || 'transparent');
    node.style.setProperty('--kmae-lqip', p && p.lqip ? 'url("' + p.lqip + '")' : 'none');
    /* A fotó 800w mérete (= a kártya képe, már betöltve): PC-n ebből az elmosott oldal-másolatok (browse-v2.css). */
    node.style.setProperty('--kmae-bg', p && (p.m || p.s) ? 'url("' + (p.m || p.s) + '")' : 'none');
    /* Az autó helye a fotón (0–1): a dizájn ennyit nagyít/olvaszt, hogy az autót ne vágja le. */
    ['sx0', 'sy0', 'sx1', 'sy1'].forEach((k, n) => {
      if (p && p.subject && p.subject.length === 4) node.style.setProperty('--kmae-' + k, String(p.subject[n]));
      else node.style.removeProperty('--kmae-' + k);
    });
    /* A fotó aránya (szél./mag.): a dizájn ebből méretezi a keretet már betöltés előtt. */
    if (p && p.w && p.h) node.style.setProperty('--kmae-ar', (p.w / p.h).toFixed(4));
    else node.style.removeProperty('--kmae-ar');
    node.setAttribute('data-tone', (p && p.tone) || '');
    /* A fotó bal szélének színe (edge()); ha ez a fotó már mintázva van, azonnal, villanás nélkül. */
    const e = p && edges[p.s];
    if (e) { node.style.setProperty('--kmae-edge', e.c); node.style.setProperty('--kmae-edge-bg', e.bg); }
    else { node.style.removeProperty('--kmae-edge'); node.style.removeProperty('--kmae-edge-bg'); }
  }

  /* PC „moziplakát”: a fotó melletti sáv a fotó LEGGYAKORIBB színét viszi tovább (browse-v2.css
     --kmae-edge-bg / --kmae-edge). Dekódolás után kis vásznon a teljes fotót színcsoportokba
     sorolja, és a legtöbb képpontot adó csoport átlagát használja (nem a fotó szélét),
     fotónként egyszer; túl világos színt sötétít (fehér feliratok).
     Ha a kép nem olvasható, marad az ambient. */
  function edge(node) {
    const img = $('.kmae-sw2-img', node);
    if (!img || !PC.matches) return;
    const p = photos(byId[node.getAttribute('data-id')])[parseInt(node.getAttribute('data-photo-index'), 10) || 0];
    if (!p || edges[p.s] !== undefined) return;
    const run = () => {
      if (!img.isConnected || !img.naturalWidth || edges[p.s] !== undefined) return;
      edges[p.s] = sampleEdge(img);
      const cur = photos(byId[node.getAttribute('data-id')])[parseInt(node.getAttribute('data-photo-index'), 10) || 0];
      if (cur === p) vars(node, p);
    };
    img.decode().then(run, () => {});
  }

  function sampleEdge(img) {
    const W = 40, H = 40, Q = 64;
    try {
      if (!edgeCtx) {
        const c = document.createElement('canvas');
        c.width = W; c.height = H;
        edgeCtx = c.getContext('2d', { willReadFrequently: true });
      }
      edgeCtx.drawImage(img, 0, 0, W, H);
      const d = edgeCtx.getImageData(0, 0, W, H).data;
      /* Színcsoport = csatornánként 4 szint (64 csoport); a győztes csoport képpontjainak átlaga. */
      const bins = {};
      let best = null;
      for (let k = 0; k < d.length; k += 4) {
        const key = ((d[k] / Q) | 0) * 16 + ((d[k + 1] / Q) | 0) * 4 + ((d[k + 2] / Q) | 0);
        const b = bins[key] || (bins[key] = [0, 0, 0, 0]);
        b[0] += d[k]; b[1] += d[k + 1]; b[2] += d[k + 2]; b[3]++;
        if (!best || b[3] > best[3]) best = b;
      }
      const c = [best[0] / best[3], best[1] / best[3], best[2] / best[3]];
      const l = (Math.max(c[0], c[1], c[2]) + Math.min(c[0], c[1], c[2])) / 2 / 255;
      const f = l > 0.42 ? 0.42 / l : 1;
      const col = (m) => 'rgb(' + c.map((v) => Math.round(v * f * m)).join(' ') + ')';
      return { c: col(1), bg: 'linear-gradient(180deg, ' + col(1.08) + ', ' + col(1) + ' 50%, ' + col(0.82) + ')' };
    } catch (err) {
      return null;
    }
  }

  function fill(node, pos) {
    const id = order[pos];
    const it = byId[id];
    node._pos = pos;
    node.setAttribute('data-id', id);
    if (!it) { node.innerHTML = ''; node.setAttribute('data-loading', ''); return; }
    node.removeAttribute('data-loading');
    node.setAttribute('data-photo-index', '0');
    node.setAttribute('data-photo-count', photos(it).length);
    node.setAttribute('data-reserved', it.reserved ? '1' : '0');
    vars(node, photos(it)[0]);
    node.innerHTML = card(it, pos);
    fit(node);
    syncFav(node);
    /* Dekódolás után látszik; addig az lqip. */
    const img = $('.kmae-sw2-img', node);
    if (img) {
      const done = () => img.setAttribute('data-ready', '');
      img.decode().then(done, done);
      edge(node);
    }
  }

  /* Az autó köré igazított kivágás (cover): R = a keret aránya (szél./mag.), a fotó a keretet
     kitölti, az autó doboza (p.subject, 0–1, feltöltéskor felismerve) a keret közepére kerül
     (x/y = object-position %). fits = az egész autó látszik (oldalt 2%, fent/lent 5% ráhagyás).
     Doboz nélkül null. */
  function crop(p, R) {
    const b = p.subject;
    if (!b || b.length !== 4) return null;
    const a = p.w / p.h, vx = Math.min(1, R / a), vy = Math.min(1, a / R);
    const at = (c, v) => (v >= 1 ? 50 : Math.max(0, Math.min(100, (c - v / 2) / (1 - v) * 100)));
    return { fits: b[2] - b[0] <= vx * 0.96 && b[3] - b[1] <= vy * 0.9, x: at((b[0] + b[2]) / 2, vx), y: at((b[1] + b[3]) / 2, vy) };
  }

  /* PC: három egységes keret (Raul, 2026-10-20), szél./mag.: téglalap (a teljes fotó-mező, kb. 2:1),
     közép (3:2), álló (3:4) — = browse-v2.css --n-r. A kép abba kerül, amelyikhez az ALAKJA a legközelebb
     áll (logaritmikus távolság: határ ≈ 1,73 és ≈ 1,06, így a 16:9 téglalap, a 4:3 / 3:2 közép, a
     négyzet álló), és vágással (cover, torzítás nélkül) kitölti. 0 = téglalap, 1 = közép, 2 = álló. */
  const FRAMES = [2, 1.5, 0.75];
  function kind(p) {
    if (!p || !p.w || !p.h) return 0;
    const a = Math.log(p.w / p.h);
    let k = 0;
    FRAMES.forEach((f, j) => { if (Math.abs(a - Math.log(f)) < Math.abs(a - Math.log(FRAMES[k]))) k = j; });
    return k;
  }

  /* Képsorrend a borítókép (1. fotó) típusa szerint (Raul, 2026-10-20): álló borító → álló, közép,
     téglalap; közép → közép, álló, téglalap; téglalap → téglalap, közép, álló. Típuson belül az
     eredeti sorrend (a borító marad az első). RANK[borító típusa][kép típusa]. PC-n, hirdetésenként
     egyszer (az első fit()-nél); p._o = az eredeti sorszám (a Jelentés ezt küldi). */
  const RANK = [[0, 1, 2], [2, 0, 1], [2, 1, 0]];
  function sortPhotos(it) {
    const ph = photos(it);
    if (it._sorted) return false;
    it._sorted = true;
    ph.forEach((p, i) => { p._o = i; });
    if (ph.length < 3) return false;
    const r = RANK[kind(ph[0])];
    const sorted = ph.slice().sort((x, y) => r[kind(x)] - r[kind(y)]);
    if (sorted.every((p, i) => p === ph[i])) return false;
    it.card.photos = sorted;
    return true;
  }

  /* A fotó elhelyezése (data-fit). PC: kind() szerint a három egységes keret egyike, a fotó mindig
     cover (az autó — ha ismert a doboza — a keret közepén, --kmae-cx/cy):
     - nincs: téglalap = a teljes fotó-mező (browse-v2.css „Fekvő fotó: keretes elrendezés”).
     - „narrow”: közép = 3:2 keret a mező bal szélén, mögötte elmosva.
     - „gallery”: álló = 3:4 keret a mező közepén, két oldalt elmosva.
     Telefon: „crop”, ha van autó-doboz és az autó belefér, vagy a fotó fekvő; „gallery” az álló
     fotó, amelyből az autó nem vágható ki (két oldalt az előző / következő kép, kattintásra vált);
     különben középre vágott cover. */
  function fit(node) {
    const ph = photos(byId[node.getAttribute('data-id')]);
    const n = ph.length;
    const i = parseInt(node.getAttribute('data-photo-index'), 10) || 0;
    const p = ph[i];
    const media = $('.kmae-sw2-media', node);
    let mode = '', c = null;
    if (media && p && p.w && p.h) {
      const a = p.w / p.h;
      /* PC: a kitöltő keret mérete (data-fit nélkül a .kmae-sw2-open = a teljes fotó-mező). */
      if (PC.matches) node.removeAttribute('data-fit');
      const o = PC.matches && $('.kmae-sw2-open', node);
      const w = o ? o.offsetWidth : media.offsetWidth;
      const h = o ? o.offsetHeight : media.offsetHeight;
      if (w && h && PC.matches) {
        /* Első megjelenéskor (a borító látszik) a képsorrend; a már kirakott bélyegképek újra. */
        if (i === 0 && sortPhotos(byId[node.getAttribute('data-id')])) {
          const box = $('[data-kmae-sw2-thumbs]', node);
          if (box && box.firstChild) { box.innerHTML = ''; thumbs(node); }
        }
        const k = kind(p);
        mode = ['', 'narrow', 'gallery'][k];
        c = crop(p, k ? FRAMES[k] : w / h) || { x: 50, y: 50 };
      } else if (w && h) {
        c = crop(p, w / h);
        mode = c && c.fits ? 'crop' : (a < 0.98 ? 'gallery' : (c ? 'crop' : ''));
      }
    }
    if (mode === 'crop' || (c && PC.matches)) {
      node.style.setProperty('--kmae-cx', c.x.toFixed(1) + '%');
      node.style.setProperty('--kmae-cy', c.y.toFixed(1) + '%');
    } else {
      node.style.removeProperty('--kmae-cx');
      node.style.removeProperty('--kmae-cy');
    }
    if (mode) node.setAttribute('data-fit', mode); else node.removeAttribute('data-fit');
    /* Az előző / következő kép két oldalt csak a telefonos galériában van (PC-n elmosott háttér). */
    if (mode !== 'gallery' || PC.matches) return;
    [-1, 1].forEach((d) => {
      let b = $('[data-kmae-sw2-side="' + d + '"]', media);
      if (!b) {
        media.insertAdjacentHTML('afterbegin', el('button', 'side', '<img alt="" decoding="async" draggable="false">',
          ' type="button" data-kmae-sw2-side="' + d + '" tabindex="-1"'));
        b = media.firstElementChild;
      }
      const q = ph[(i + d + n) % n];
      const img = b.firstElementChild;
      b.setAttribute('aria-label', n > 1 ? (d < 0 ? t.photoPrev : t.photoNext) : '');
      b.toggleAttribute('data-single', n < 2);
      if (img._src !== q.s) {
        img._src = q.s;
        img.sizes = '30vw';
        img.srcset = srcset(q);
        img.src = q.m || q.s;
      }
    });
  }

  /* Teljes képernyő (PC, fotó-keret jobb alsó gombja): a fotó + bélyegkép-sáv; újra megnyomva / Esc kilép. */
  function full(node) {
    const d = document;
    if (d.fullscreenElement || d.webkitFullscreenElement) {
      (d.exitFullscreen || d.webkitExitFullscreen).call(d);
      return;
    }
    const m = $('.kmae-sw2-media', node);
    /* A teljes képernyős nézet fejlécének címe (browse-v2.css ::before, attr(data-fs-title)). */
    const it = byId[node.getAttribute('data-id')];
    if (m && it && it.card && it.card.title) m.setAttribute('data-fs-title', it.card.title.text || '');
    const go = m && (m.requestFullscreen || m.webkitRequestFullscreen);
    const r = go && go.call(m);
    if (r && r.catch) r.catch(() => {});
  }

  function syncFav(node) {
    const on = !!fav[node.getAttribute('data-id')];
    node.setAttribute('data-favorite', on ? '1' : '0');
    const b = $('[data-kmae-sw2-like]', node);
    if (b) b.setAttribute('aria-pressed', String(on));
  }

  const slotAt = (pos) => slots.find((s) => s._pos === pos);

  /* Bélyegképek az aktuális kártyán (egyszer kártyánként; a 480w kép, lusta betöltéssel). */
  function thumbs(node) {
    const box = node && $('[data-kmae-sw2-thumbs]', node);
    if (!box || box.firstChild) return;
    const at = parseInt(node.getAttribute('data-photo-index'), 10) || 0;
    box.innerHTML = photos(byId[node.getAttribute('data-id')]).map((p, i) => el('button', 'thumb',
      '<img src="' + esc(p.s) + '" alt="" loading="lazy" decoding="async" draggable="false">',
      ' type="button" data-kmae-sw2-thumb="' + i + '" aria-label="' + esc(t.photoN.replace('%d', i + 1)) + '"' + (i === at ? ' aria-current="true"' : ''))).join('');
  }

  function layout() {
    const want = [cur - 1, cur, cur + 1];
    const free = slots.filter((s) => want.indexOf(s._pos) < 0);
    want.forEach((pos) => {
      if (pos < 0 || pos >= order.length) return;
      const s = slotAt(pos);
      if (!s) fill(free.shift(), pos);
      else if (s.hasAttribute('data-loading') && byId[order[pos]]) fill(s, pos);
    });
    slots.forEach((s) => {
      const role = ['prev', 'current', 'next'][s._pos - cur + 1] || 'idle';
      if (s.getAttribute('data-role') !== role) s.setAttribute('data-role', role);
    });
    stack.removeAttribute('data-under');
    /* Lapozás: a nyitott adatlap bezárul (a következő és a visszalapozott hirdetés is zárva jelenik meg). */
    if (sheetId != null && String(sheetId) !== String(order[cur])) closeSheet(false);
    thumbs(slotAt(cur));
    endEl.hidden = cur < order.length;
    prevBtn.hidden = cur <= 0;
    nextBtn.hidden = cur >= order.length - 1;
    if (passBtn) passBtn.hidden = cur >= order.length;
    peek();
    remember();
    ensure();
    preload();
  }

  /* Alsó sor: előző / következő hirdetés pirula (kis kép + cím); a még be nem töltött oldalnál
     cím nélkül, a load() utáni layout() pótolja. */
  function peek() {
    peeks.forEach((b) => {
      const pos = cur + Number(b.getAttribute('data-kmae-sw2-peek'));
      b.hidden = pos < 0 || pos >= order.length;
      if (b.hidden) return;
      const it = byId[order[pos]];
      const title = it ? it.card.title.text : '';
      const tEl = b.querySelector('[data-kmae-sw2-peek-title]');
      if (tEl.textContent !== title) tEl.textContent = title;
      const p = photos(it)[0];
      const src = p ? (p.s || p.m) : '';
      const img = b.querySelector('img');
      if ((img.getAttribute('src') || '') !== src) { if (src) img.src = src; else img.removeAttribute('src'); }
      img.hidden = !src;
    });
  }

  function remember() { try { if (order[cur] != null) sessionStorage.setItem(STORE, order[cur]); } catch (e) {} }

  function ensure() {
    for (let pos = Math.max(0, cur - 1); pos <= cur + AHEAD && pos < order.length; pos++) {
      const id = order[pos];
      const p = Math.floor(sIndex[id] / feed.per_page) + 1;
      if (!byId[id] && !loaded[p] && !pending[p]) load(p);
    }
  }

  function load(p) {
    pending[p] = 1;
    fetch(cfg.feedUrl + (cfg.feedUrl.indexOf('?') < 0 ? '?' : '&') + (feed.query ? feed.query + '&' : '') + 'page=' + p, { credentials: 'omit' })
      .then((r) => r.json())
      .then((d) => { loaded[p] = 1; add(d.items); })
      .catch(() => {})
      .then(() => { delete pending[p]; if (!busy && !drag) layout(); });
  }

  function warmPhoto(p) {
    if (!p) return null;
    if (warm[p.s]) return warm[p.s];
    const img = warm[p.s] = new Image();
    img.sizes = SIZES;
    img.srcset = srcset(p);
    img.src = p.m || p.s;
    img.decode().catch(() => {});
    return img;
  }

  function preload() {
    /* Az első kártya képe ne versenyezzen az előtöltéssel. */
    if (document.readyState !== 'complete') return window.addEventListener('load', preload, { once: true });
    warmPhoto(photos(byId[order[cur + 1]])[0]);
    warmPhoto(photos(byId[order[cur + 2]])[0]);
    warmPhoto(photos(byId[order[cur]])[1]);
  }

  function settle(node, after) {
    busy = true;
    node.classList.add('is-anim');
    setTimeout(() => {
      node.classList.remove('is-anim');
      node.style.transform = node.style.opacity = node.style.willChange = '';
      after();
      busy = false;
      layout();
    }, SLIDE_MS);
  }

  /* dx: a húzás eddigi elmozdulása (PC-n innen úszik tovább, nem ugrik vissza). */
  function fly(node, transform, delta, dx) {
    settle(node, () => { cur += delta; });
    node.style.transform = PC.matches
      ? 'translateX(' + (delta > 0 ? -1 : 1) * Math.max(Math.abs(dx || 0) + 40, stack.offsetWidth * 0.06) + 'px)' : transform;
    node.style.opacity = '0';
  }

  function step(delta, transform) {
    const s = slotAt(cur);
    if (busy || drag || !s) return false;
    stack.setAttribute('data-under', delta > 0 ? 'next' : 'prev');
    fly(s, transform, delta);
    return true;
  }

  const stepPrev = () => cur > 0 && step(-1, 'translateX(-120%) rotate(-12deg)');
  const stepNext = () => cur < order.length - 1 && step(1, 'translateX(120%) rotate(12deg)');

  function pass() {
    const id = order[cur];
    if (step(1, 'translateX(-120%) rotate(-12deg)') && cfg.loggedIn) post({ action: 'kmae_browse_pass', nonce: cfg.nonce, listing_id: id });
  }

  function post(fields) {
    const body = new FormData();
    Object.keys(fields).forEach((k) => body.append(k, fields[k]));
    return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' });
  }

  /* Adatlap a színpad alatt: fekvő telefonon a fotó / „Részletek” / Enter ide tölt.
     Álló telefonon és PC-n nincs (2026-10-24): a fotó nem csinál semmit, a „Részletek” / Enter
     a hirdetés oldalára visz. A panel a hirdetés .kmae-single-listing részét tölti be, odagörget.
     Alul a „felfelé” gomb zárja; lapozáskor bezárul (layout()). Ha nem tölthető be, az oldalára visz. */
  function openSheet() {
    const node = slotAt(cur);
    const it = node && byId[node.getAttribute('data-id')];
    if (!it) return;
    const url = it.card.url;
    if (!sheet || !window.fetch || !window.DOMParser) { window.location.href = url; return; }
    if (sheetId === it.id && !sheet.hidden) { toSheet(); return; }
    const tok = ++sheetTok;
    sheetId = it.id;
    sheet.hidden = false;
    sheet.setAttribute('data-loading', '');
    sheetBody.innerHTML = el('p', 'sheet__wait', esc(t.sheetLoading));
    toSheet();
    fetch(url, { credentials: 'same-origin' })
      .then((r) => { if (!r.ok) throw new Error(r.status); return r.text(); })
      .then((html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const art = doc.querySelector('.kmae-single-listing');
        if (!art) throw new Error('missing');
        /* Az élő frissítés ne a böngészés oldalát töltse újra; a „Vissza” gomb itt nem kell;
           a cím nélküli űrlapok (üzenet, bejelentés) a hirdetés oldalára küldjenek. */
        [art].concat(Array.from(art.querySelectorAll('[data-kmae-live-page]'))).forEach((x) => {
          x.removeAttribute('data-kmae-live-page');
          x.removeAttribute('data-kmae-live-fingerprint');
        });
        art.querySelectorAll('.kmae-page-back, script').forEach((x) => x.remove());
        art.querySelectorAll('form').forEach((f) => { if (!f.getAttribute('action')) f.setAttribute('action', url); });
        return sheetCss(doc).then(() => art);
      })
      .then((art) => {
        if (tok !== sheetTok) return;
        sheetBody.replaceChildren(document.importNode(art, true));
        sheet.removeAttribute('data-loading');
      })
      .catch(() => { if (tok === sheetTok) window.location.href = url; });
  }

  /* Az adatlap stílusa (theme single-listing.css) csak az első megnyitáskor töltődik, a hirdetés oldalán lévő pontos URL-lel. */
  function sheetCss(doc) {
    const src = doc.getElementById('kmae-single-listing-css');
    if (!src || document.getElementById('kmae-single-listing-css')) return Promise.resolve();
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.id = src.id;
    link.href = src.getAttribute('href');
    return new Promise((done) => { link.onload = link.onerror = done; document.head.appendChild(link); });
  }

  function toSheet() {
    sheet.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  /* up: a gombbal zárva előbb felgörget, és csak fent tűnik el (nincs ugrás). */
  function closeSheet(up) {
    const tok = ++sheetTok;
    const hide = () => {
      if (tok !== sheetTok) return;
      sheetId = null;
      sheet.hidden = true;
      sheet.removeAttribute('data-loading');
      sheetBody.replaceChildren();
    };
    if (!up || window.scrollY <= 0) { hide(); if (window.scrollY > 0) window.scrollTo({ top: 0, behavior: 'smooth' }); return; }
    let timer = 0;
    const done = () => { window.removeEventListener('scrollend', done); clearTimeout(timer); hide(); };
    window.addEventListener('scrollend', done);
    timer = setTimeout(done, 900);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function setFav(id, on) {
    if (on) fav[id] = 1; else delete fav[id];
    slots.forEach((s) => { if (s.getAttribute('data-id') === String(id)) syncFav(s); });
  }

  function saveCurrent() {
    const id = order[cur];
    if (id == null) return;
    if (!cfg.loggedIn) { if (cfg.loginUrl) window.location.href = cfg.loginUrl; return; }
    const was = !!fav[id];
    /* A telefonos Lapozó (browse-lapozo.js) is tudjon róla. */
    const tell = (on) => document.dispatchEvent(new CustomEvent('kmae:favorite', { detail: { id: id, on: on, source: 'sw2' } }));
    setFav(id, !was);
    tell(!was);
    post({ action: was ? 'kmae_favorite_remove' : 'kmae_favorite_add', nonce: cfg.favoriteNonce, listing_id: id })
      .then((r) => r.json())
      .then((d) => {
        if (!d || !d.success) { setFav(id, was); tell(was); return; }
        const badge = document.querySelector('[data-kmae-favorites-unseen]');
        if (badge && d.data && typeof d.data.unseen !== 'undefined') {
          const count = parseInt(d.data.unseen, 10) || 0;
          badge.textContent = count;
          badge.toggleAttribute('hidden', count <= 0);
        }
      })
      .catch(() => { setFav(id, was); tell(was); });
  }

  /* Képváltás: a számláló / bélyegkép azonnal vált, a nagy kép akkor cserélődik, amikor
     már dekódolva van (nincs üres villanás); a gyors egymás utáni kattintásból az utolsó nyer. */
  function photo(node, dir) {
    showPhoto(node, (parseInt(node.getAttribute('data-photo-index'), 10) || 0) + dir);
  }

  function showPhoto(node, i) {
    const ph = photos(byId[node.getAttribute('data-id')]);
    if (i < 0 || i >= ph.length || i === (parseInt(node.getAttribute('data-photo-index'), 10) || 0)) return;
    const p = ph[i];
    const img = $('.kmae-sw2-img', node);
    node.setAttribute('data-photo-index', i);
    $('.kmae-sw2-photo-count', node).textContent = (i + 1) + ' / ' + ph.length;
    node.querySelectorAll('.kmae-sw2-dot').forEach((d, j) => d.toggleAttribute('data-current', j === i));
    node.querySelectorAll('[data-kmae-sw2-thumb]').forEach((b, j) => {
      if (j === i) { b.setAttribute('aria-current', 'true'); reveal(b); } else b.removeAttribute('aria-current');
    });
    $('.kmae-sw2-photo-prev', node).hidden = i <= 0;
    $('.kmae-sw2-photo-next', node).hidden = i >= ph.length - 1;
    const tok = node._photoTok = (node._photoTok || 0) + 1;
    const pre = warmPhoto(p);
    const swap = () => {
      if (node._photoTok !== tok || !img.isConnected) return;
      img.srcset = srcset(p);
      img.src = p.m || p.s;
      vars(node, p);
      fit(node);
      edge(node);
    };
    pre.decode().then(swap, swap);
    warmPhoto(ph[i + 1]);
  }

  /* A kijelölt bélyegkép a sáv látható részébe görget (csak a sávot, az oldalt nem). */
  function reveal(b) {
    const box = b.parentNode;
    const l = b.offsetLeft, r = l + b.offsetWidth;
    if (l < box.scrollLeft) box.scrollTo({ left: l - 12, behavior: 'smooth' });
    else if (r > box.scrollLeft + box.clientWidth) box.scrollTo({ left: r - box.clientWidth + 12, behavior: 'smooth' });
  }

  stack.addEventListener('click', (e) => {
    /* Húzás után ne nyíljon meg a link. */
    if (mute) { e.preventDefault(); e.stopPropagation(); return; }
    const node = e.target.closest('[data-kmae-sw2-card]');
    if (!node || node._pos !== cur) return;
    /* Fotó: PC-n és álló telefonon a kattintás nem nyit adatlapot. A „Részletek” állóban a hirdetés
       oldalára visz (a link megy), fekvő telefonon a lenyíló adatlapot nyitja. Új lapon nyitás marad. */
    const sheetLink = e.target.closest('.kmae-sw2-open, .kmae-sw2-cta');
    if (sheetLink && !e.target.closest('button') && !(e.ctrlKey || e.metaKey || e.shiftKey || e.button > 0)) {
      if (PORTRAIT.matches && sheetLink.closest('.kmae-sw2-cta')) return;
      e.preventDefault();
      if (!PC.matches && !PORTRAIT.matches) openSheet();
      return;
    }
    const nav = e.target.closest('[data-kmae-sw2-photo]');
    const th = e.target.closest('[data-kmae-sw2-thumb]');
    const side = e.target.closest('[data-kmae-sw2-side]');
    if (side) {
      e.preventDefault();
      const n = parseInt(node.getAttribute('data-photo-count'), 10) || 0;
      if (n > 1) showPhoto(node, ((parseInt(node.getAttribute('data-photo-index'), 10) || 0) + +side.getAttribute('data-kmae-sw2-side') + n) % n);
    } else if (nav) {
      e.preventDefault();
      photo(node, +nav.getAttribute('data-kmae-sw2-photo'));
    } else if (th) {
      e.preventDefault();
      showPhoto(node, +th.getAttribute('data-kmae-sw2-thumb'));
    } else if (e.target.closest('[data-kmae-sw2-like]')) {
      e.preventDefault();
      saveCurrent();
    } else if (e.target.closest('[data-kmae-sw2-full]')) {
      e.preventDefault();
      full(node);
    }
  }, true);

  stack.addEventListener('dragstart', (e) => e.preventDefault());

  /* Egér a bélyegkép fölött: a nagy kép már előre töltődik, a kattintásra azonnal ott van. */
  stack.addEventListener('pointerover', (e) => {
    const th = e.target.closest && e.target.closest('[data-kmae-sw2-thumb]');
    const node = th && th.closest('[data-kmae-sw2-card]');
    if (node) warmPhoto(photos(byId[node.getAttribute('data-id')])[+th.getAttribute('data-kmae-sw2-thumb')]);
  });

  /* Görgő a sáv fölött: vízszintes görgetés (ha a sáv túlcsordul). */
  stack.addEventListener('wheel', (e) => {
    const box = e.target.closest && e.target.closest('[data-kmae-sw2-thumbs]');
    if (!box || box.scrollWidth <= box.clientWidth || Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
    e.preventDefault();
    box.scrollLeft += e.deltaY;
  }, { passive: false });

  /* Húzás (ujj, egér): balra = következő, jobbra = előző. Függőlegesen görget (pan-y). */
  function paint() {
    raf = 0;
    if (!drag || !drag.on) return;
    const dx = drag.dx;
    const shown = (dx < 0 && cur >= order.length - 1) || (dx > 0 && cur <= 0) ? dx * 0.25 : dx;
    drag.node.style.transform = 'translateX(' + shown + 'px)' + (PC.matches ? '' : ' rotate(' + (shown / 22) + 'deg)');
  }

  stack.addEventListener('pointerdown', (e) => {
    if (busy || drag || !e.isPrimary || e.button > 0 || document.fullscreenElement || document.webkitFullscreenElement) return;
    const node = e.target.closest('[data-kmae-sw2-card]');
    if (!node || node._pos !== cur || e.target.closest('[data-kmae-sw2-like], [data-kmae-sw2-photo], [data-kmae-sw2-thumbs], [data-kmae-sw2-full], .kmae-sw2-msg, .kmae-sw2-cta')) return;
    if (e.pointerType === 'mouse') e.preventDefault();
    drag = { node: node, id: e.pointerId, x: e.clientX, y: e.clientY, dx: 0, on: false, under: '' };
  });

  stack.addEventListener('pointermove', (e) => {
    if (!drag || e.pointerId !== drag.id) return;
    const dx = e.clientX - drag.x;
    const dy = e.clientY - drag.y;
    if (!drag.on) {
      if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
      if (Math.abs(dy) > Math.abs(dx)) { drag = null; return; }
      drag.on = true;
      drag.w = stack.offsetWidth || 1;
      drag.node.style.willChange = 'transform';
      try { stack.setPointerCapture(e.pointerId); } catch (err) {}
    }
    drag.dx = dx;
    const under = dx < 0 ? 'next' : 'prev';
    if (under !== drag.under) stack.setAttribute('data-under', drag.under = under);
    if (!raf) raf = requestAnimationFrame(paint);
  });

  function finish(e) {
    if (!drag || e.pointerId !== drag.id) return;
    const d = drag;
    drag = null;
    if (!d.on) return;
    mute = true;
    setTimeout(() => { mute = false; }, 350);
    const dir = d.dx < 0 ? -1 : 1;
    const can = dir < 0 ? cur < order.length - 1 : cur > 0;
    if (e.type === 'pointercancel' || !can || Math.abs(d.dx) < Math.min(110, d.w * 0.28)) {
      settle(d.node, () => {});
      d.node.style.transform = '';
    } else {
      fly(d.node, 'translateX(' + (dir * 130) + '%) rotate(' + (dir * 14) + 'deg)', -dir, d.dx);
    }
  }

  stack.addEventListener('pointerup', finish);
  stack.addEventListener('pointercancel', finish);
  prevBtn.addEventListener('click', stepPrev);
  nextBtn.addEventListener('click', stepNext);
  peeks.forEach((b) => b.addEventListener('click', () => (Number(b.getAttribute('data-kmae-sw2-peek')) < 0 ? stepPrev() : stepNext())));
  if (passBtn) passBtn.addEventListener('click', pass);

  /* Billentyűk (= a régi swipe.js): ← / → hirdetés, Enter = adatlap (PC és álló telefon: oldalbetöltés,
     onnan a Backspace visz vissza — public.js; fekvő telefon: lenyíló adatlap), Space = kedvenc.
     Teljes képernyőn a nyilak a képet váltják. */
  document.addEventListener('keydown', (e) => {
    if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || !root.isConnected) return;
    /* Nyitott ablakban, szövegmezőben, a rendezés / km menüben és a kártyán kívüli gombon/linken a billentyű az övék;
       a kártya / lapozó gombjain (kattintás után ott a fókusz) viszont itt is ez a kiosztás él. */
    const tg = e.target && e.target.closest ? e.target : null;
    if (document.querySelector('dialog[open]')) return;
    if (tg && tg.closest('input, textarea, select, [contenteditable="true"], [data-kmae-sort], [data-kmae-dist]')) return;
    if (tg && tg.closest('button, a') && !tg.closest('[data-kmae-sw2-stack], .kmae-sw2-nav, .kmae-sw2-bar')) return;
    const fs = document.fullscreenElement || document.webkitFullscreenElement;
    const node = slotAt(cur);
    const k = e.key;
    if (k === 'ArrowLeft' || k === 'ArrowRight') {
      e.preventDefault();
      if (fs) { if (node && fs.closest('[data-kmae-sw2-card]') === node) photo(node, k === 'ArrowLeft' ? -1 : 1); return; }
      if (k === 'ArrowLeft') stepPrev(); else stepNext();
    } else if (fs) {
      return;
    } else if (k === 'Enter') {
      const it = node && byId[node.getAttribute('data-id')];
      if (!it || e.repeat) return;
      e.preventDefault();
      if (PC.matches || PORTRAIT.matches) { remember(); window.location.href = it.card.url; } else openSheet();
    } else if (k === ' ' || e.code === 'Space') {
      e.preventDefault();
      if (!e.repeat) saveCurrent();
    }
  });

  /* Jelentés (csak belépve; vendégnél a gomb a belépésre visz): az aktuális hirdetés, és ha
     képre vonatkozó okot választ, az éppen látott kép sorszáma (KMAE_Reports::photo_reasons). */
  const rep = document.querySelector('[data-kmae-sw2-rep]');
  const repBtn = root.querySelector('button[data-kmae-sw2-report]');
  if (rep && repBtn) {
    const form = rep.querySelector('[data-kmae-sw2-rep-form]');
    const msg = rep.querySelector('[data-kmae-sw2-rep-msg]');
    const photoBox = rep.querySelector('[data-kmae-sw2-rep-photo]');
    const details = form.elements.details;
    let target = null;
    const say = (text, ok) => { msg.textContent = text; msg.hidden = !text; msg.toggleAttribute('data-ok', !!ok); };
    repBtn.addEventListener('click', () => {
      const node = slotAt(cur);
      const it = node && byId[node.getAttribute('data-id')];
      if (!it) return;
      const n = photos(it).length;
      const pi = parseInt(node.getAttribute('data-photo-index'), 10) || 0;
      const pq = photos(it)[pi];
      /* show = a böngészőben látott sorszám, photo = az eredeti (a hirdetés képsorrendje, sortPhotos). */
      target = { id: it.id, show: pi + 1, photo: (pq && pq._o != null ? pq._o : pi) + 1 };
      form.reset();
      details.required = false;
      say('');
      form.querySelector('[type="submit"]').disabled = false;
      rep.querySelector('[data-kmae-sw2-rep-car]').textContent = it.card.title.text;
      photoBox.hidden = !n;
      photoBox.querySelectorAll('input').forEach((x) => { x.disabled = !n; });
      rep.querySelector('[data-kmae-sw2-rep-photo-n]').textContent = n ? t.reportPhoto.replace('%1$d', target.show).replace('%2$d', n) : '';
      rep.showModal();
    });
    rep.addEventListener('click', (e) => {
      /* X / Mégse, vagy kattintás az ablakon kívülre (a dialog maga). */
      if (e.target === rep || e.target.closest('[data-kmae-sw2-rep-close]')) rep.close();
    });
    form.addEventListener('change', (e) => {
      if (e.target.name === 'reason') details.required = e.target.value === 'other';
    });
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      if (!target) return;
      const send = form.querySelector('[type="submit"]');
      send.disabled = true;
      say('');
      post({ action: 'kmae_report_listing', nonce: cfg.reportNonce, listing_id: target.id, photo: target.photo,
        reason: form.elements.reason.value, details: details.value })
        .then((r) => r.json())
        .then((d) => {
          if (!d || !d.success) throw new Error((d && d.data && d.data.message) || t.reportFail);
          say(t.reportSent, true);
          setTimeout(() => { if (rep.open) rep.close(); }, 1800);
        })
        .catch((err) => { say(err && err.message ? err.message : t.reportFail); send.disabled = false; });
    });
  }

  if (sheet) {
    sheet.querySelector('[data-kmae-sw2-sheet-close]').addEventListener('click', () => closeSheet(true));
    /* Az adatlap galériája (public.js a betöltéskor köt, a később betöltött adatlapra nem): nyilak, pöttyök. */
    sheetBody.addEventListener('click', (e) => {
      const b = e.target.closest('.kmae-gallery-prev, .kmae-gallery-next, .kmae-gallery-dot, .kmae-gallery-thumb');
      const g = b && b.closest('.kmae-gallery');
      if (!g) return;
      const slides = Array.from(g.querySelectorAll('.kmae-gallery-slide'));
      const n = slides.length;
      if (!n) return;
      let i = slides.findIndex((x) => x.classList.contains('active'));
      i = b.classList.contains('kmae-gallery-prev') ? i - 1 : b.classList.contains('kmae-gallery-next') ? i + 1 : parseInt(b.getAttribute('data-index'), 10) || 0;
      i = (i + n) % n;
      slides.forEach((x, j) => x.classList.toggle('active', j === i));
      g.querySelectorAll('.kmae-gallery-dot, .kmae-gallery-thumb').forEach((d) => d.classList.toggle('active', parseInt(d.getAttribute('data-index'), 10) === i));
      const c = g.querySelector('.kmae-gallery-counter');
      if (c) c.textContent = (i + 1) + ' / ' + n;
    });
  }

  /* A legutóbb nézett hirdetéshez CSAK visszatéréskor ugrunk (böngésző Vissza, vagy az adatlapról
     jön: /hirdetesek/<slug>/ → /hirdetesek/). Friss betöltésnél az elejéről indul, különben a
     közben feladott (elöl rangsorolt) új hirdetés „mögénk”, balra kerülne. */
  const returning = () => {
    try {
      const nav = performance.getEntriesByType('navigation')[0];
      if (nav && nav.type === 'back_forward') return true;
      const r = new URL(document.referrer);
      return r.origin === location.origin && r.pathname !== location.pathname && r.pathname.indexOf(location.pathname) === 0;
    } catch (e) { return false; }
  };
  if (returning()) { try { cur = Math.max(0, order.indexOf(+sessionStorage.getItem(STORE))); } catch (e) {} }
  if (slots[0]._pos === 0) { syncFav(slots[0]); fit(slots[0]); edge(slots[0]); }
  layout();

  /* Ablakméret / PC↔telefon váltás: a galéria-döntés a színpad arányától függ. */
  let fitRaf = 0;
  const refit = () => {
    if (fitRaf) return;
    fitRaf = requestAnimationFrame(() => { fitRaf = 0; slots.forEach((s) => { if (s._pos >= 0 && byId[s.getAttribute('data-id')]) fit(s); }); });
  };
  window.addEventListener('resize', refit);
  if (PC.addEventListener) PC.addEventListener('change', refit);

  /* Telefonos Lapozó (browse-lapozo.js): kedvenc-szinkron, és kilépéskor a kártya arra az autóra áll,
     ahol ott abbahagyta. Ezeket csak a Lapozó küldi (PC-n nem fut). */
  document.addEventListener('kmae:favorite', (e) => {
    const d = e.detail || {};
    if (d.source !== 'sw2' && d.id != null) setFav(d.id, !!d.on);
  });
  document.addEventListener('kmae:lpz-goto', (e) => {
    const id = e.detail && e.detail.id;
    const i = order.findIndex((x) => String(x) === String(id));
    if (i >= 0) { cur = i; layout(); }
  });

  /* BROWSE-V2.md „Személyes adat a kliensen” (= KMAE_Browse_Feed::personalize). */
  function personalize(S, R, D, me) {
    const ex = new Set(me.own.concat(me.contacted)), ps = new Set(me.passed), fresh = [], pd = [], rank = {};
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
