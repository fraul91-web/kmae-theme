/**
 * Forgalmi engedély beolvasása a hirdetés-wizardhoz.
 *
 * Folyamat: ajánlat → EGY mezőben 1–2 fotó (egyszerre kijelölve vagy ráhúzva;
 * két képnél a kiolvasás magától indul) → „szkennelés” → összesítő (javítható)
 * → „Igen, megfelel” → window.kmaeWizard.applyVehicleData() kitölti a wizardot,
 * és az első hiányzó lépésre ugrik.
 *
 * A képet a böngésző készíti elő (kivágás a zöld papírra, kicsinyítés, enyhe
 * kontraszt) — így kisebb a feltöltés, és a szerveren nincs képfeldolgozás.
 * Szerver: KMAE_Registration_Scan::ajax_scan (AJAX kmae_registration_scan).
 * Sablon: templates/partials/registration-scan.php.
 */
(function () {
  'use strict';

  var cfg = window.kmaeRegistrationScan || null;
  if (!cfg) {
    return;
  }

  var MAX_IMAGES = cfg.maxImages || 2;
  var OUTPUT_MAX = 2000;     /* px, a hosszabb oldal */
  var ANALYSIS_W = 300;      /* a zöld papír kereséséhez */
  var AUTO_START_MS = 1400;  /* két kép után ennyi idő múlva indul magától */
  var CHECK_STEP_MS = 1300;  /* a „szkennelés” lépései */
  var SCAN_MIN_MS = 3600;    /* ennél rövidebb beolvasás nem hihető */
  var HOW_STEP = { pick: 0, scan: 1, review: 2, error: 0 };

  var root = null;
  var views = {};
  var els = {};
  var shots = [];            /* { blob, url, loading } — sorrendben */
  var result = null;
  var request = null;
  var autoTimer = null;
  var lastFocus = null;
  var offered = false;
  var scanned = false;     /* az adatok már a forgalmiból jöttek — a belépő gomb nem kell */
  var ready = false;
  var pendingOffer = false;

  /* A wizard induláskor jelez (detail.offer: felajánljuk-e). A figyelő azonnal
     él, mert a wizard a DOMContentLoaded-ben indul, ez a fájl pedig előtte fut. */
  document.addEventListener('kmae:wizard-ready', function (event) {
    var detail = event.detail || {};
    scanned = !!detail.scanned;
    var entryBtn = document.querySelector('[data-kmae-regscan-open]');
    if (entryBtn && ready) {
      entryBtn.hidden = scanned;
    }
    /* `offer` hiányában (régebbi wizard) a friss indulás számít. */
    var wanted = typeof detail.offer === 'boolean' ? detail.offer : !!detail.fresh;
    if (offered || !wanted) {
      return;
    }
    offered = true;
    if (ready) {
      window.setTimeout(open, 650);
    } else {
      pendingOffer = true;
    }
  });

  /* ---------------------------------------------------------------- */
  /* Képelőkészítés                                                   */
  /* ---------------------------------------------------------------- */

  function loadImage(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        URL.revokeObjectURL(url);
        resolve(img);
      };
      img.onerror = function () {
        URL.revokeObjectURL(url);
        reject(new Error('image'));
      };
      img.src = url;
    });
  }

  /* EXIF szerinti forgatással (a mai böngészők az <img>-nél is így rajzolnak). */
  function loadSource(file) {
    if (typeof window.createImageBitmap === 'function') {
      return window.createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () {
        return loadImage(file);
      });
    }
    return loadImage(file);
  }

  function sourceSize(src) {
    return {
      w: src.naturalWidth || src.width,
      h: src.naturalHeight || src.height
    };
  }

  /**
   * A magyar forgalmi halványzöld papír: a „zöldesség” (G − (R+B)/2) jól
   * elválasztja a szürke/fehér háttértől. Gyanús eredménynél nem vágunk.
   */
  function findGreenDocument(src, W, H) {
    var w = ANALYSIS_W;
    var h = Math.max(1, Math.round(H * (w / W)));
    var canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    var ctx = canvas.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(src, 0, 0, w, h);
    var data = ctx.getImageData(0, 0, w, h).data;

    function greenAt(x, y) {
      var i = (y * w + x) * 4;
      return data[i + 1] - (data[i] + data[i + 2]) / 2;
    }

    var border = [];
    var m = Math.max(2, Math.floor(w * 0.04));
    var x;
    var y;
    for (y = 0; y < h; y += 2) {
      for (x = 0; x < m; x++) {
        border.push(greenAt(x, y));
        border.push(greenAt(w - 1 - x, y));
      }
    }
    for (x = 0; x < w; x += 2) {
      for (y = 0; y < m; y++) {
        border.push(greenAt(x, y));
        border.push(greenAt(x, h - 1 - y));
      }
    }
    border.sort(function (a, b) { return a - b; });
    var bgGreen = border[Math.floor(border.length * 0.75)] || 0;
    var threshold = Math.max(5, bgGreen + 5);

    var rowHits = new Array(h);
    var colHits = new Array(w);
    for (y = 0; y < h; y++) { rowHits[y] = 0; }
    for (x = 0; x < w; x++) { colHits[x] = 0; }

    for (y = 0; y < h; y++) {
      for (x = 0; x < w; x++) {
        var i = (y * w + x) * 4;
        var r = data[i];
        var g = data[i + 1];
        var b = data[i + 2];
        if (g - (r + b) / 2 > threshold && (r + g + b) / 3 > 90) {
          rowHits[y]++;
          colHits[x]++;
        }
      }
    }

    var rowMin = w * 0.12;
    var colMin = h * 0.12;
    var top = -1;
    var bottom = -1;
    var left = -1;
    var right = -1;
    for (y = 0; y < h; y++) { if (rowHits[y] >= rowMin) { top = y; break; } }
    for (y = h - 1; y >= 0; y--) { if (rowHits[y] >= rowMin) { bottom = y; break; } }
    for (x = 0; x < w; x++) { if (colHits[x] >= colMin) { left = x; break; } }
    for (x = w - 1; x >= 0; x--) { if (colHits[x] >= colMin) { right = x; break; } }

    if (top < 0 || left < 0 || bottom <= top || right <= left) {
      return null;
    }
    var area = ((bottom - top) * (right - left)) / (w * h);
    /* Csak akkor vágunk, ha a papír a kép nagy része: egy részleges találat
       (pl. sárgás fény, összehajtott forgalmi) levágná a műszaki keretét. */
    if (area < 0.3 || area > 0.92) {
      return null;
    }

    var scale = W / w;
    var padX = (right - left) * 0.07;
    var padY = (bottom - top) * 0.06;
    var L = Math.max(0, Math.floor((left - padX) * scale));
    var T = Math.max(0, Math.floor((top - padY) * scale));
    var R = Math.min(W, Math.ceil((right + 1 + padX) * scale));
    var B = Math.min(H, Math.ceil((bottom + 1 + padY) * scale));
    return { x: L, y: T, w: R - L, h: B - T };
  }

  /* Enyhe kontraszt: a világosság 1–99. percentilisét húzzuk szét (a szín marad). */
  function normalise(ctx, w, h) {
    var image = ctx.getImageData(0, 0, w, h);
    var d = image.data;
    var hist = new Array(256);
    var i;
    for (i = 0; i < 256; i++) { hist[i] = 0; }
    for (i = 0; i < d.length; i += 4) {
      hist[Math.round(0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2])]++;
    }
    var total = d.length / 4;
    var low = 0;
    var high = 255;
    var sum = 0;
    for (i = 0; i < 256; i++) {
      sum += hist[i];
      if (sum >= total * 0.01) { low = i; break; }
    }
    sum = 0;
    for (i = 255; i >= 0; i--) {
      sum += hist[i];
      if (sum >= total * 0.01) { high = i; break; }
    }
    if (high - low < 24 || (low < 4 && high > 251)) {
      return;
    }
    var lut = new Uint8ClampedArray(256);
    for (i = 0; i < 256; i++) {
      lut[i] = ((i - low) * 255) / (high - low);
    }
    for (i = 0; i < d.length; i += 4) {
      d[i] = lut[d[i]];
      d[i + 1] = lut[d[i + 1]];
      d[i + 2] = lut[d[i + 2]];
    }
    ctx.putImageData(image, 0, 0);
  }

  function canvasToBlob(canvas) {
    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (blob) {
        if (blob) {
          resolve(blob);
        } else {
          reject(new Error('blob'));
        }
      }, 'image/jpeg', 0.9);
    });
  }

  function prepareImage(file) {
    return loadSource(file).then(function (src) {
      var size = sourceSize(src);
      var box = null;
      try {
        box = findGreenDocument(src, size.w, size.h);
      } catch (e) {
        box = null;
      }
      if (!box) {
        box = { x: 0, y: 0, w: size.w, h: size.h };
      }
      var scale = Math.min(1, OUTPUT_MAX / Math.max(box.w, box.h));
      var cw = Math.max(1, Math.round(box.w * scale));
      var ch = Math.max(1, Math.round(box.h * scale));
      var canvas = document.createElement('canvas');
      canvas.width = cw;
      canvas.height = ch;
      var ctx = canvas.getContext('2d', { willReadFrequently: true });
      ctx.imageSmoothingQuality = 'high';
      ctx.drawImage(src, box.x, box.y, box.w, box.h, 0, 0, cw, ch);
      if (typeof src.close === 'function') {
        src.close();
      }
      try {
        normalise(ctx, cw, ch);
      } catch (e) { /* kontraszt nélkül is jó */ }
      return canvasToBlob(canvas);
    });
  }


  /* ---------------------------------------------------------------- */
  /* Ablak                                                            */
  /* ---------------------------------------------------------------- */

  function q(selector, scope) {
    return (scope || root).querySelector(selector);
  }

  function qa(selector, scope) {
    return Array.prototype.slice.call((scope || root).querySelectorAll(selector));
  }

  function setView(name) {
    Object.keys(views).forEach(function (key) {
      views[key].hidden = key !== name;
    });
    root.setAttribute('data-view', name);

    var current = HOW_STEP[name] || 0;
    qa('[data-kmae-regscan-how]').forEach(function (li) {
      var index = parseInt(li.getAttribute('data-kmae-regscan-how'), 10);
      li.classList.toggle('is-done', index < current);
      li.classList.toggle('is-active', index === current);
    });

    var focusEl = q('[data-kmae-regscan-view="' + name + '"] .kmae-regscan__btn--ok, [data-kmae-regscan-view="' + name + '"] .kmae-regscan__btn--primary:not([disabled])');
    (focusEl || els.card).focus({ preventScroll: true });
  }

  function open() {
    if (!root || !root.hidden) {
      return;
    }
    lastFocus = document.activeElement;
    resetPick();
    root.hidden = false;
    document.documentElement.classList.add('kmae-regscan-open');
    setView('pick');
    window.requestAnimationFrame(function () {
      root.classList.add('is-open');
    });
  }

  function close() {
    if (!root || root.hidden) {
      return;
    }
    cancelAuto();
    if (request) {
      request.abort();
      request = null;
    }
    stopChecks();
    root.classList.remove('is-open');
    document.documentElement.classList.remove('kmae-regscan-open');
    root.hidden = true;
    resetPick();
    result = null;
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus({ preventScroll: true });
    }
  }

  /* ---------------------------------------------------------------- */
  /* 1. Képek — egy mező, egyszerre legfeljebb 2                      */
  /* ---------------------------------------------------------------- */

  var ICON_CLOSE = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>';
  var ICON_PLUS = '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>';

  function readyShots() {
    return shots.filter(function (shot) { return !shot.loading && shot.blob; });
  }

  function anyLoading() {
    return shots.some(function (shot) { return shot.loading; });
  }

  function pickError(message) {
    els.pickError.textContent = message || '';
    els.pickError.hidden = !message;
  }

  function resetPick() {
    cancelAuto();
    shots.forEach(function (shot) {
      if (shot.url) {
        URL.revokeObjectURL(shot.url);
      }
    });
    shots = [];
    els.file.value = '';
    pickError('');
    renderShots();
  }

  function cancelAuto() {
    if (autoTimer) {
      window.clearTimeout(autoTimer);
      autoTimer = null;
    }
    if (els.auto) {
      els.auto.hidden = true;
    }
  }

  /* Két kész kép: azonnal indul. Egy kép: rövid várakozás, hogy a második oldal még becsatolható legyen. */
  function maybeAutoStart() {
    cancelAuto();
    if (!readyShots().length || anyLoading()) {
      return;
    }
    if (readyShots().length >= MAX_IMAGES) {
      startScan();
      return;
    }
    els.auto.hidden = false;
    autoTimer = window.setTimeout(function () {
      autoTimer = null;
      startScan();
    }, AUTO_START_MS);
  }

  function renderShots() {
    var box = els.picked;
    box.innerHTML = '';
    box.hidden = !shots.length;
    els.drop.hidden = !!shots.length;

    shots.forEach(function (shot, index) {
      var item = document.createElement('figure');
      item.className = 'kmae-regscan__shot' + (shot.loading ? ' is-loading' : '');
      if (shot.loading) {
        item.innerHTML = '<span class="kmae-regscan__shot-spin" aria-hidden="true"></span><figcaption>Előkészítés…</figcaption>';
      } else {
        var img = document.createElement('img');
        img.src = shot.url;
        img.alt = '';
        item.appendChild(img);
        var cap = document.createElement('figcaption');
        cap.textContent = (index + 1) + '. fotó';
        item.appendChild(cap);
        var remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'kmae-regscan__shot-remove';
        remove.setAttribute('aria-label', 'Fotó eltávolítása');
        remove.innerHTML = ICON_CLOSE;
        remove.addEventListener('click', function () {
          removeShot(shot);
        });
        item.appendChild(remove);
      }
      box.appendChild(item);
    });

    if (shots.length && shots.length < MAX_IMAGES) {
      var add = document.createElement('button');
      add.type = 'button';
      add.className = 'kmae-regscan__add';
      add.innerHTML = '<span class="kmae-regscan__add-icon">' + ICON_PLUS + '</span>'
        + '<strong>Másik oldal</strong><small>Nem kötelező — így a műszaki is meglesz</small>';
      add.addEventListener('click', function () {
        els.file.multiple = false;
        els.file.click();
      });
      box.appendChild(add);
    }
  }

  function removeShot(shot) {
    var index = shots.indexOf(shot);
    if (index === -1) {
      return;
    }
    if (shot.url) {
      URL.revokeObjectURL(shot.url);
    }
    shots.splice(index, 1);
    pickError('');
    cancelAuto();
    renderShots();
  }

  function addFiles(list) {
    var files = Array.prototype.slice.call(list || []).filter(Boolean);
    if (!files.length) {
      return;
    }
    var free = MAX_IMAGES - shots.length;
    if (free <= 0) {
      pickError('Már megvan a két fotó. Ha cserélnéd, előbb törölj egyet.');
      return;
    }
    /* A böngésző fájlablaka nem tud darabszámot korlátozni, ezért az első
       kettőt (illetve a még szabad helyre férőket) vesszük át, a többit nem —
       és ezt ki is írjuk. */
    var extra = files.length - free;
    if (extra > 0) {
      files = files.slice(0, free);
      pickError('Legfeljebb 2 fotó lehet (a forgalmi két oldala) — az első ' + (free === 1 ? 'képet' : free + ' képet') + ' használtuk, a többit ('
        + extra + ' db) nem.');
      els.pick.classList.remove('is-shake');
      void els.pick.offsetWidth;
      els.pick.classList.add('is-shake');
    } else {
      pickError('');
    }

    files.forEach(function (file) {
      if (!/^image\//.test(file.type || '') && !/\.(jpe?g|png|webp|heic|heif)$/i.test(file.name || '')) {
        pickError('Ezt a fájlt nem tudjuk képként megnyitni. Fotót válassz.');
        return;
      }
      var shot = { blob: null, url: '', loading: true };
      shots.push(shot);
      prepareImage(file).catch(function () {
        /* Ha a böngésző nem tudja feldolgozni, az eredeti képpel próbálkozunk. */
        if (file.size <= 6 * 1024 * 1024 && /^image\/(jpeg|png|webp)$/.test(file.type || '')) {
          return file;
        }
        return null;
      }).then(function (blob) {
        if (shots.indexOf(shot) === -1) {
          return; /* közben törölték vagy bezárták */
        }
        if (!blob) {
          shots.splice(shots.indexOf(shot), 1);
          pickError('Ezt a képet nem tudtuk megnyitni. Próbáld egy másik fotóval (JPG vagy PNG).');
        } else {
          shot.blob = blob;
          shot.url = URL.createObjectURL(blob);
          shot.loading = false;
        }
        renderShots();
        maybeAutoStart();
      });
    });
    cancelAuto();
    renderShots();
  }

  function bindPick() {
    els.file.addEventListener('change', function () {
      var list = els.file.files;
      addFiles(list);
      els.file.value = '';
      els.file.multiple = true;
    });

    /* Ráhúzás: az egész képterület fogadja (üresen és egy kép után is). */
    var zone = els.pick;
    var depth = 0;
    zone.addEventListener('dragenter', function (event) {
      event.preventDefault();
      depth++;
      zone.classList.add('is-dragover');
    });
    zone.addEventListener('dragover', function (event) {
      event.preventDefault();
    });
    zone.addEventListener('dragleave', function () {
      depth = Math.max(0, depth - 1);
      if (!depth) {
        zone.classList.remove('is-dragover');
      }
    });
    zone.addEventListener('drop', function (event) {
      event.preventDefault();
      depth = 0;
      zone.classList.remove('is-dragover');
      addFiles(event.dataTransfer ? event.dataTransfer.files : []);
    });
  }

  /* ---------------------------------------------------------------- */
  /* 2. „Szkennelés”                                                  */
  /* ---------------------------------------------------------------- */

  var checkTimer = null;
  var checkIndex = 0;

  function checkItems() {
    return qa('[data-kmae-regscan-checks] li:not([hidden])');
  }

  function paintChecks(done) {
    var items = checkItems();
    items.forEach(function (li, index) {
      li.classList.toggle('is-done', index < done);
      li.classList.toggle('is-active', index === done);
    });
    els.bar.style.width = Math.min(100, Math.round(((done + 0.5) / items.length) * 100)) + '%';
  }

  /* A személyes adatok kitakarása külön blokk: rövid várakozás után zöld pipát kap. */
  var guardTimer = null;
  function startGuard() {
    var guard = q('[data-kmae-regscan-guard]');
    var title = q('[data-kmae-regscan-guard-title]');
    guard.classList.remove('is-done');
    title.textContent = 'Személyes adatok kitakarása…';
    window.clearTimeout(guardTimer);
    guardTimer = window.setTimeout(function () {
      guard.classList.add('is-done');
      title.textContent = 'Személyes adatok kitakarva';
    }, 1200);
  }

  function startChecks(withBack) {
    startGuard();
    q('[data-kmae-regscan-check-mot]').hidden = !withBack;
    checkIndex = 0;
    paintChecks(0);
    stopChecks();
    checkTimer = window.setInterval(function () {
      /* Az utolsó lépés a válaszra vár. */
      if (checkIndex < checkItems().length - 1) {
        checkIndex++;
        paintChecks(checkIndex);
      }
    }, CHECK_STEP_MS);
  }

  function stopChecks() {
    if (checkTimer) {
      window.clearInterval(checkTimer);
      checkTimer = null;
    }
  }

  function finishChecks(then) {
    stopChecks();
    var total = checkItems().length;
    var tick = function () {
      if (checkIndex < total) {
        checkIndex++;
        paintChecks(checkIndex);
        window.setTimeout(tick, 160);
      } else {
        els.bar.style.width = '100%';
        window.setTimeout(then, 420);
      }
    };
    tick();
  }

  function paintPhotos(list) {
    qa('img', els.photos).forEach(function (img) { img.parentNode.removeChild(img); });
    list.forEach(function (shot) {
      var img = document.createElement('img');
      img.src = shot.url;
      img.alt = '';
      els.photos.appendChild(img);
    });
    els.photos.classList.toggle('is-double', list.length > 1);
  }

  /* A szerver státusza alapján: fotótanács csak akkor van, ha a fotón múlhat.
     Napi keret / kikapcsolt funkció / lejárt munkamenet esetén az új fotó nem segít. */
  function scanError(message, status) {
    var blocked = status === 429 || status === 503 || status === 403;
    q('[data-kmae-regscan-error]').textContent = message || 'Most nem sikerült beolvasni. Próbáld újra, vagy add meg az adatokat kézzel.';
    q('[data-kmae-regscan-error-a]').textContent = status === 429 ? 'Mára elfogyott' : (blocked ? 'Most nem érhető' : 'Most nem tudtuk');
    q('[data-kmae-regscan-error-b]').textContent = status === 429 ? 'a kiolvasási keret' : (blocked ? 'el a kiolvasás' : 'kiolvasni');
    q('[data-kmae-regscan-tips]').hidden = blocked;
    q('[data-kmae-regscan-retry]').hidden = blocked;
    setView('error');
  }

  function startScan() {
    cancelAuto();
    var list = readyShots();
    if (!list.length || anyLoading()) {
      return;
    }

    paintPhotos(list);
    setView('scan');
    startChecks(list.length > 1);
    var started = Date.now();

    var body = new FormData();
    body.append('action', 'kmae_registration_scan');
    body.append('nonce', cfg.nonce || '');
    list.forEach(function (shot, index) {
      body.append('kmae_scan_images[]', shot.blob, 'forgalmi-' + (index + 1) + '.jpg');
    });

    request = typeof window.AbortController === 'function' ? new window.AbortController() : null;
    fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body,
      signal: request ? request.signal : undefined
    })
      .then(function (res) {
        return res.json().catch(function () { return null; }).then(function (json) {
          return { status: res.status, json: json };
        });
      })
      .then(function (reply) {
        var json = reply.json;
        request = null;
        if (root.hidden) {
          return;
        }
        if (!json || !json.success) {
          stopChecks();
          scanError(json && json.data && json.data.message, reply.status);
          return;
        }
        result = json.data || {};
        window.setTimeout(function () {
          finishChecks(showReview);
        }, Math.max(0, SCAN_MIN_MS - (Date.now() - started)));
      })
      .catch(function (err) {
        request = null;
        if (err && err.name === 'AbortError') {
          return;
        }
        stopChecks();
        scanError('Megszakadt a kapcsolat. Ellenőrizd az internetet, és próbáld újra.');
      });
  }

  /* ---------------------------------------------------------------- */
  /* 3. Összesítő                                                     */
  /* ---------------------------------------------------------------- */

  function catalog() {
    return window.kmaeVehicleCatalog || {};
  }

  function findIgnoreCase(list, value) {
    var needle = String(value || '').trim().toLowerCase();
    if (!needle || !list) {
      return '';
    }
    for (var i = 0; i < list.length; i++) {
      if (String(list[i]).toLowerCase() === needle) {
        return list[i];
      }
    }
    return '';
  }

  function modelsFor(brand) {
    var data = catalog();
    var key = findIgnoreCase(data.brands || [], brand);
    return key && data.models && data.models[key] ? data.models[key] : [];
  }

  function fillDatalist(id, items) {
    var list = document.getElementById(id);
    if (!list) {
      return;
    }
    list.innerHTML = '';
    (items || []).forEach(function (item) {
      var option = document.createElement('option');
      option.value = item;
      list.appendChild(option);
    });
  }

  function input(name) {
    return q('[data-kmae-regscan-input="' + name + '"]');
  }

  var MONTHS = ['január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'];

  /* A beolvasott nap (ha az év és a hónap nem változott), különben a hónap utolsó napja
     — ugyanígy számol a szerver is, ha a nap üres. */
  function motDate() {
    var y = parseInt(input('mot_year').value, 10);
    var m = parseInt(input('mot_month').value, 10);
    if (!y || !m) {
      return null;
    }
    var d = 0;
    if (result && String(result.mot_year) === String(y) && String(result.mot_month) === String(m)) {
      d = parseInt(result.mot_day, 10) || 0;
    }
    var last = new Date(y, m, 0).getDate();
    return new Date(y, m - 1, d > 0 && d <= last ? d : last);
  }

  /* „Még 1 év 7 hónapig érvényes” — naptári különbség a mai naphoz képest. */
  function paintMotLeft() {
    var out = q('[data-kmae-regscan-mot-left]');
    var has = input('mot_has').value;
    out.className = 'kmae-regscan__mot-left';
    if (has === 'no') {
      out.textContent = 'Nincs érvényes műszaki — a hirdetésben így jelenik meg.';
      out.classList.add('is-warn');
      out.hidden = false;
      return;
    }
    var end = has === 'yes' ? motDate() : null;
    if (!end) {
      out.hidden = true;
      return;
    }
    var today = new Date();
    today.setHours(0, 0, 0, 0);
    var label = end.getFullYear() + '. ' + MONTHS[end.getMonth()] + ' ' + end.getDate() + '.';
    if (end < today) {
      out.textContent = 'Ez a dátum már elmúlt (' + label + ') — nézd meg újra a forgalmiban.';
      out.classList.add('is-warn');
      out.hidden = false;
      return;
    }
    var years = end.getFullYear() - today.getFullYear();
    var months = end.getMonth() - today.getMonth();
    var days = end.getDate() - today.getDate();
    if (days < 0) {
      months -= 1;
      days += new Date(end.getFullYear(), end.getMonth(), 0).getDate();
    }
    if (months < 0) {
      years -= 1;
      months += 12;
    }
    var parts = [];
    if (years > 0) {
      parts.push(years + ' év');
    }
    if (months > 0) {
      parts.push(months + ' hónap');
    }
    if (!years && !months) {
      parts.push(days + ' nap');
    } else if (!years && months < 2 && days > 0) {
      parts.push(days + ' nap');
    }
    var soon = (end - today) / 86400000 < 60;
    out.textContent = (soon ? 'Hamarosan lejár: még ' : 'Még ') + parts.join(' ') + 'ig érvényes (' + label + ')';
    out.classList.add(soon ? 'is-soon' : 'is-ok');
    out.hidden = false;
  }

  function syncMotInputs() {
    var on = input('mot_has').value === 'yes';
    input('mot_year').disabled = !on;
    input('mot_month').disabled = !on;
    paintMotLeft();
  }

  function syncField(field) {
    var controls = qa('input, select', field).filter(function (el) { return !el.disabled; });
    var empty = controls.some(function (el) { return String(el.value || '').trim() === ''; });
    field.classList.toggle('is-empty', empty);
  }

  function syncLegend() {
    var show = qa('.kmae-regscan__field').some(function (field) {
      return field.classList.contains('is-uncertain') || field.classList.contains('is-empty');
    });
    q('[data-kmae-regscan-legend]').hidden = !show;
  }

  function showReview() {
    var data = result || {};
    var uncertain = data.uncertain || [];

    fillDatalist('kmae-regscan-brands', catalog().brands || []);
    fillDatalist('kmae-regscan-models', modelsFor(data.brand));

    ['brand', 'model', 'year', 'power_hp', 'engine_cc', 'fuel', 'transmission', 'mot_has', 'mot_year', 'mot_month'].forEach(function (name) {
      var el = input(name);
      if (el) {
        el.value = data[name] ? String(data[name]) : '';
        if (el.tagName === 'INPUT') {
          el.placeholder = 'Add meg';
        }
      }
    });
    syncMotInputs();

    qa('.kmae-regscan__field').forEach(function (field) {
      field.classList.toggle('is-uncertain', uncertain.indexOf(field.getAttribute('data-field')) !== -1);
      syncField(field);
    });
    syncLegend();
    setView('review');
  }

  function bindReview() {
    var review = q('[data-kmae-regscan-review]');
    var touch = function (event) {
      var field = event.target.closest('.kmae-regscan__field');
      if (field) {
        field.classList.remove('is-uncertain');
        syncField(field);
        syncLegend();
      }
    };
    review.addEventListener('input', touch);
    review.addEventListener('change', function (event) {
      if (event.target === input('mot_has') || event.target === input('mot_year') || event.target === input('mot_month')) {
        syncMotInputs();
      }
      if (event.target === input('brand')) {
        fillDatalist('kmae-regscan-models', modelsFor(input('brand').value));
      }
      touch(event);
    });
  }

  function collect() {
    var brandRaw = input('brand').value.trim();
    var brand = findIgnoreCase(catalog().brands || [], brandRaw);
    var modelRaw = input('model').value.trim();
    var model = brand ? findIgnoreCase(modelsFor(brand), modelRaw) : '';
    var motHas = input('mot_has').value;
    var sameDate = result && String(result.mot_year) === input('mot_year').value && String(result.mot_month) === input('mot_month').value;

    return {
      brand: brand || brandRaw,
      brandCustom: !brand && brandRaw !== '',
      model: model || modelRaw,
      modelCustom: !model && modelRaw !== '',
      year: input('year').value.trim(),
      power_hp: input('power_hp').value.trim(),
      engine_cc: input('engine_cc').value.trim(),
      fuel: input('fuel').value,
      transmission: input('transmission').value,
      mot_has: motHas,
      mot_year: motHas === 'yes' ? input('mot_year').value : '',
      mot_month: motHas === 'yes' ? input('mot_month').value : '',
      mot_day: motHas === 'yes' && sameDate ? String(result.mot_day || '') : '',
      /* Rendszám, alvázszám, forgalmi szám: nem mutatjuk, csak a szerződéshez megy tovább. */
      ids: result && result.ids ? result.ids : null
    };
  }

  function accept() {
    var data = collect();
    close();
    if (window.kmaeWizard && typeof window.kmaeWizard.applyVehicleData === 'function') {
      window.kmaeWizard.applyVehicleData(data);
      /* Betöltöttük az adatokat: a „Kitöltés a forgalmiból” gomb már csak zavarna.
         Ha az ablakot elvetik vagy kihagyják, a gomb marad (kézi kitöltés). */
      scanned = true;
      var entryBtn = document.querySelector('[data-kmae-regscan-open]');
      if (entryBtn) {
        entryBtn.hidden = true;
      }
    }
  }

  /* ---------------------------------------------------------------- */
  /* Indítás                                                          */
  /* ---------------------------------------------------------------- */

  function trapFocus(event) {
    var view = views[root.getAttribute('data-view')];
    var items = qa('button:not([disabled]), input:not([disabled]):not([type="file"]), select:not([disabled]), a[href], label.kmae-regscan__drop:not([hidden])', view)
      .concat([q('.kmae-regscan__x')]);
    if (!items.length) {
      return;
    }
    var first = items[0];
    var last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  }

  function init() {
    root = document.querySelector('[data-kmae-regscan]');
    if (!root) {
      return;
    }
    /* A <body>-ba: a wizard transzformált konténerei ne torzítsák a fix pozíciót. */
    document.body.appendChild(root);

    qa('[data-kmae-regscan-view]').forEach(function (view) {
      views[view.getAttribute('data-kmae-regscan-view')] = view;
    });
    els.card = q('.kmae-regscan__card');
    els.pick = q('[data-kmae-regscan-pick]');
    els.drop = q('[data-kmae-regscan-drop]');
    els.file = q('[data-kmae-regscan-file]');
    els.picked = q('[data-kmae-regscan-picked]');
    els.pickError = q('[data-kmae-regscan-pick-error]');
    els.auto = q('[data-kmae-regscan-auto]');
    els.photos = q('[data-kmae-regscan-photos]');
    els.bar = q('[data-kmae-regscan-bar]');

    /* A rejtett fájlmező helyett a feltöltőmező kap fókuszt és Entert. */
    els.drop.setAttribute('tabindex', '0');
    els.drop.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        els.file.click();
      }
    });

    bindPick();
    bindReview();

    qa('[data-kmae-regscan-close]').forEach(function (el) {
      el.addEventListener('click', close);
    });
    q('[data-kmae-regscan-discard]').addEventListener('click', close);
    q('[data-kmae-regscan-accept]').addEventListener('click', accept);
    q('[data-kmae-regscan-retry]').addEventListener('click', function () {
      resetPick();
      setView('pick');
    });

    document.addEventListener('keydown', function (event) {
      if (root.hidden) {
        return;
      }
      if (event.key === 'Escape' || event.key === 'Esc') {
        close();
      } else if (event.key === 'Tab') {
        trapFocus(event);
      }
    });

    var entry = document.querySelector('[data-kmae-regscan-open]');
    if (entry) {
      entry.hidden = scanned;
      entry.addEventListener('click', open);
    }

    ready = true;
    if (pendingOffer) {
      pendingOffer = false;
      window.setTimeout(open, 650);
    }
  }

  if (document.readyState !== 'loading') {
    init();
  } else {
    document.addEventListener('DOMContentLoaded', init);
  }
}());
