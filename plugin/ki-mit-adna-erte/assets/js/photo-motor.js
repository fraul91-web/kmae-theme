(function () {
  'use strict';
  function init() {
    var root = document.getElementById('kmae-photo-motor');
    var genericInput = document.getElementById('kmae_images');
    var genericPreview = document.getElementById('kmae-upload-preview');
    var uploader = document.getElementById('kmae-uploader');
    var uploadStatus = document.getElementById('kmae-upload-status');

    /* Ráhúzás közben a doboz kiemelődik. A fájlt maga a mező kapja meg, mert
       átlátszóan a doboz fölött fekszik — így nem kell külön kezelni a dobást. */
    if (uploader && genericInput) {
      ['dragenter', 'dragover'].forEach(function (type) {
        uploader.addEventListener(type, function (event) {
          event.preventDefault();
          uploader.classList.add('is-dragover');
        });
      });
      ['dragleave', 'dragend', 'drop'].forEach(function (type) {
        uploader.addEventListener(type, function () {
          uploader.classList.remove('is-dragover');
        });
      });
      genericInput.addEventListener('focus', function () {
        uploader.classList.add('is-focused');
      });
      genericInput.addEventListener('blur', function () {
        uploader.classList.remove('is-focused');
      });
    }

    /* A kiválasztott képek EGY listában gyűlnek. A böngésző fájlmezője
       minden új tallózásnál lecseréli a korábbi kijelölést, ezért a képeket
       magunk tartjuk nyilván, és a mezőbe mindig a teljes listát írjuk vissza.
       Így több körben, egyesével is fel lehet tölteni őket. */
    var MAX_IMAGES = 12;
    var picked = [];

    /* Borító: alapból a meglévő galéria első képe marad. Egy új kép csak
       akkor lesz borító, ha nincs meglévő kép, vagy a ★-gal kijelölik
       (kmae_cover_new = 1 → mentéskor az első új kép kerül előre). */
    var newCover = false;
    var coverInput = null;
    if (genericPreview) {
      coverInput = document.createElement('input');
      coverInput.type = 'hidden';
      coverInput.name = 'kmae_cover_new';
      coverInput.value = '0';
      genericPreview.parentNode.insertBefore(coverInput, genericPreview);
    }

    /* Autó-felismerés (photo-subject.js): a képek sorrendjében az autó doboza
       megy a szerverre (kmae_photo_subjects), a böngésző kártyája ebből tudja,
       hogy nagyításkor/olvasztáskor hol van az autó. Ha még nem készült el a
       beküldésig, az a kép egyszerűen doboz nélkül megy fel. */
    var subjectInput = null;
    if (genericPreview) {
      subjectInput = document.createElement('input');
      subjectInput.type = 'hidden';
      subjectInput.name = 'kmae_photo_subjects';
      subjectInput.value = '[]';
      genericPreview.parentNode.insertBefore(subjectInput, genericPreview);
    }
    function syncSubjects() {
      if (subjectInput) {
        subjectInput.value = JSON.stringify(picked.map(function (file) {
          return (file.kmaeSource || file).kmaeSubject || null;
        }));
      }
    }
    function detectSubject(file) {
      if (!window.kmaePhotoSubject || isDocument(file)) {
        return;
      }
      window.kmaePhotoSubject.detect(file).then(function (box) {
        file.kmaeSubject = box;
        syncSubjects();
      });
    }
    function existingCount() {
      return document.querySelectorAll('#kmae-gallery-manager .kmae-gallery-manager-item:not(.is-removed)').length;
    }
    function setNewCover(on) {
      newCover = !!on && picked.length > 0;
      if (coverInput) {
        coverInput.value = newCover ? '1' : '0';
      }
      document.dispatchEvent(new CustomEvent(newCover ? 'kmae:cover-new' : 'kmae:cover-new-cleared'));
    }
    document.addEventListener('kmae:cover-existing', function () {
      newCover = false;
      if (coverInput) {
        coverInput.value = '0';
      }
      if (genericPreview) {
        renderPreview();
      }
    });
    document.addEventListener('kmae:gallery-changed', function () {
      if (genericPreview) {
        renderPreview();
      }
    });

    function isImage(file) {
      return !!file && typeof file.type === 'string' && file.type.indexOf('image/') === 0;
    }

    function isPdf(file) {
      return !!file && (file.type === 'application/pdf' || /\.pdf$/i.test(file.name || ''));
    }

    /* PDF-ből készült oldal (a szerver is erről ismeri fel: nem „javítja” fotóként). */
    var DOC_PREFIX = 'albira-dokumentum-';
    function isDocument(file) {
      return !!file && String(file.name || '').indexOf(DOC_PREFIX) === 0;
    }

    /* ---- PDF → kép (pdf.js, csak PDF választásakor töltődik be) ---- */
    var PDF_MAX_PAGES = 6;
    var PDF_TARGET_W = 1700; /* px — a szerver úgyis 1920-ra korlátoz és WebP-be tömörít */
    var pdfLibPromise = null;

    function loadPdfLib() {
      if (window.pdfjsLib) {
        return Promise.resolve(window.pdfjsLib);
      }
      if (pdfLibPromise) {
        return pdfLibPromise;
      }
      var paths = (window.kmaePhotoMotor && window.kmaePhotoMotor.pdf) || {};
      pdfLibPromise = new Promise(function (resolve, reject) {
        if (!paths.lib) {
          reject(new Error('pdf'));
          return;
        }
        var script = document.createElement('script');
        script.src = paths.lib;
        script.async = true;
        script.onload = function () {
          if (!window.pdfjsLib) {
            reject(new Error('pdf'));
            return;
          }
          window.pdfjsLib.GlobalWorkerOptions.workerSrc = paths.worker;
          resolve(window.pdfjsLib);
        };
        script.onerror = function () {
          pdfLibPromise = null;
          reject(new Error('pdf'));
        };
        document.head.appendChild(script);
      });
      return pdfLibPromise;
    }

    function canvasToFile(canvas, name, quality) {
      return new Promise(function (resolve, reject) {
        canvas.toBlob(function (blob) {
          if (!blob) {
            reject(new Error('blob'));
            return;
          }
          resolve(new File([blob], name, { type: 'image/jpeg', lastModified: Date.now() }));
        }, 'image/jpeg', quality || 0.92);
      });
    }

    /* A kicsinyített fotó JPEG-minősége: 0,95 (a szerver ebből készíti a 90-es WebP-t,
       két tömörítés között minél kevesebb a veszteség, a sötét/zajos fotónál ez látszik).
       A zajos fotó 0,95-ön akár 3 MB is lehet, 12 kép pedig együtt a post_max_size-ba
       fér csak bele — ezért ha egy kép a SHRINK_TARGET_BYTES fölé nőne, a következő
       (kisebb) fokozatot használjuk: 12 × 1,8 MB ≈ 22 MB a legrosszabb eset. */
    var SHRINK_QUALITIES = [0.95, 0.92, 0.88];
    var SHRINK_TARGET_BYTES = 1.8 * 1024 * 1024;
    function canvasToFileFitting(canvas, name, step) {
      var index = step || 0;
      return canvasToFile(canvas, name, SHRINK_QUALITIES[index]).then(function (file) {
        if (file.size > SHRINK_TARGET_BYTES && index < SHRINK_QUALITIES.length - 1) {
          return canvasToFileFitting(canvas, name, index + 1);
        }
        return file;
      });
    }

    /* A telefonos fotó (5–10 MB/db) eredetiben túl nagy: 12 kép együtt
       túllépi a tárhely beküldési korlátját (post_max_size), a PHP ilyenkor
       az egész űrlapot eldobja, és a feladás csendben újraindul. A szerver
       úgyis 1920 px-re kicsinyít (KMAE_Images::MAX_EDGE), ezért már itt
       ekkorára vesszük — minőségvesztés nélkül. */
    var SHRINK_EDGE = 1920;
    var SHRINK_MIN_BYTES = 1024 * 1024;
    function shrinkImage(file) {
      if (!/^image\/(jpe?g|png|webp)$/i.test(file.type || '') || file.size <= SHRINK_MIN_BYTES || !window.URL) {
        return Promise.resolve(file);
      }
      return new Promise(function (resolve) {
        var url = URL.createObjectURL(file);
        var img = new Image();
        img.onload = function () {
          URL.revokeObjectURL(url);
          var w = img.naturalWidth;
          var h = img.naturalHeight;
          var scale = Math.min(1, SHRINK_EDGE / Math.max(w, h));
          var canvas = document.createElement('canvas');
          canvas.width = Math.max(1, Math.round(w * scale));
          canvas.height = Math.max(1, Math.round(h * scale));
          var ctx = canvas.getContext('2d');
          if (!ctx) {
            resolve(file);
            return;
          }
          ctx.fillStyle = '#fff';
          ctx.fillRect(0, 0, canvas.width, canvas.height);
          ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
          var name = String(file.name || 'foto').replace(/\.[a-z0-9]+$/i, '') + '.jpg';
          canvasToFileFitting(canvas, name).then(function (small) {
            if (small.size >= file.size) {
              resolve(file);
              return;
            }
            small.kmaeSource = file;
            resolve(small);
          }, function () {
            resolve(file);
          });
        };
        img.onerror = function () {
          URL.revokeObjectURL(url);
          resolve(file);
        };
        img.src = url;
      });
    }

    /* A PDF minden oldala egy JPEG lesz (fehér háttérrel); legfeljebb `limit` oldal. */
    function pdfToImages(file, limit) {
      var base = String(file.name || 'dokumentum').replace(/\.pdf$/i, '')
        .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 40) || 'dokumentum';
      return loadPdfLib().then(function (lib) {
        return file.arrayBuffer().then(function (data) {
          return lib.getDocument({ data: new Uint8Array(data) }).promise;
        });
      }).then(function (pdf) {
        var count = Math.min(pdf.numPages, limit, PDF_MAX_PAGES);
        var pages = [];
        var chain = Promise.resolve();
        for (var n = 1; n <= count; n++) {
          (function (pageNo) {
            chain = chain.then(function () {
              return pdf.getPage(pageNo);
            }).then(function (page) {
              var unit = page.getViewport({ scale: 1 });
              var viewport = page.getViewport({ scale: Math.min(3, PDF_TARGET_W / unit.width) });
              var canvas = document.createElement('canvas');
              canvas.width = Math.round(viewport.width);
              canvas.height = Math.round(viewport.height);
              var ctx = canvas.getContext('2d');
              ctx.fillStyle = '#ffffff';
              ctx.fillRect(0, 0, canvas.width, canvas.height);
              return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
                return canvasToFile(canvas, DOC_PREFIX + base + '-' + pageNo + '.jpg');
              });
            }).then(function (image) {
              pages.push(image);
            });
          })(n);
        }
        return chain.then(function () {
          if (typeof pdf.destroy === 'function') {
            pdf.destroy();
          }
          return { pages: pages, total: pdf.numPages };
        });
      });
    }

    /* Ugyanaz a kép ne kerüljön be kétszer, ha véletlenül újra kiválasztod. */
    function alreadyPicked(file) {
      return picked.some(function (item) {
        item = item.kmaeSource || item;
        return item.name === file.name &&
          item.size === file.size &&
          item.lastModified === file.lastModified;
      });
    }

    /* A saját listánkat visszaírjuk a mezőbe, mert a beküldéskor az számít. */
    function syncInputFiles() {
      if (!window.DataTransfer) {
        return;
      }
      var transfer = new DataTransfer();
      picked.forEach(function (file) {
        transfer.items.add(file);
      });
      genericInput.files = transfer.files;
      syncSubjects();
    }

    function syncUploadStatus() {
      if (!uploadStatus) {
        return;
      }
      var count = picked.length;
      uploadStatus.textContent = count
        ? (count === 1 ? '1 kép kiválasztva' : count + ' kép kiválasztva')
        : '';
      uploadStatus.hidden = !count;
      if (uploader) {
        uploader.classList.toggle('is-filled', count > 0);
      }
    }

    function openLightbox(src) {
      var box = document.createElement('div');
      box.className = 'kmae-upload-lightbox';
      box.innerHTML = '<button type="button" aria-label="Bezárás">×</button><img alt="">';
      box.querySelector('img').src = src;
      box.querySelector('button').onclick = function () {
        box.remove();
      };
      document.body.appendChild(box);
    }

    function moveImage(from, to) {
      if (from === to || from < 0 || to < 0 || from >= picked.length) {
        return;
      }
      var moved = picked.splice(from, 1)[0];
      picked.splice(to, 0, moved);
      syncInputFiles();
      renderPreview();
    }

    /*
     * Ugyanaz a mérce, mint a szerveren (KMAE_Photo_Motor).
     *
     * A küszöbök nincsenek itt megismételve: a PHP küldi át őket, mert az az
     * egyetlen igazságforrás. Ha ott változik egy határ, itt magától követi.
     */
    var BRIDGE = window.kmaePhotoMotor || {};
    var CFG = BRIDGE.config || {};
    var T = BRIDGE.i18n || {};

    function cfg(key, fallback) {
      return (typeof CFG[key] === 'number') ? CFG[key] : fallback;
    }

    function say(key, fallback) {
      return T[key] || fallback;
    }

    /*
     * Egy képterület hisztogramja és a belőle számolt mérőszámok.
     * Ugyanaz, amit a PHP oldal is számol.
     */
    function regionStats(luma, sw, sh, x0, y0, rw, rh) {
      var hist = new Uint32Array(256);
      var total = 0;
      var yMax = Math.min(sh, y0 + rh);
      var xMax = Math.min(sw, x0 + rw);
      var x;
      var y;

      for (y = Math.max(0, y0); y < yMax; y++) {
        var row = y * sw;
        for (x = Math.max(0, x0); x < xMax; x++) {
          hist[luma[row + x]]++;
          total++;
        }
      }

      if (!total) {
        return null;
      }

      function percentile(q) {
        var target = q * total;
        var acc = 0;
        for (var v = 0; v <= 255; v++) {
          acc += hist[v];
          if (acc >= target) {
            return v;
          }
        }
        return 255;
      }

      var shadowLuma = cfg('shadowClipLuma', 6);
      var deepLuma = cfg('deepShadowLuma', 24);
      var highLuma = cfg('highlightClipLuma', 249);

      var shadow = 0;
      var deep = 0;
      var high = 0;
      for (var i = 0; i <= 255; i++) {
        var count = hist[i];
        if (!count) {
          continue;
        }
        if (i <= shadowLuma) {
          shadow += count;
        }
        if (i <= deepLuma) {
          deep += count;
        }
        if (i >= highLuma) {
          high += count;
        }
      }

      var p05 = percentile(0.05);
      var p95 = percentile(0.95);

      return {
        p05: p05,
        p10: percentile(0.10),
        p50: percentile(0.50),
        p90: percentile(0.90),
        p95: p95,
        shadowClip: shadow / total,
        deepShadow: deep / total,
        highlightClip: high / total,
        dynamicRange: p95 - p05
      };
    }

    /*
     * Expozíció a 192 képpontos példányról (ott a középső terület is
     * értelmesen elkülönül), élesség a 64x64-es mintáról — így a homályossági
     * küszöbök kalibrációja érvényben marad.
     */
    function sampleStats(image) {
      var maxEdge = cfg('analysisMaxEdge', 192);
      var w = image.naturalWidth;
      var h = image.naturalHeight;
      if (!w || !h) {
        return null;
      }

      var scale = Math.min(1, maxEdge / Math.max(w, h));
      var sw = Math.max(1, Math.round(w * scale));
      var sh = Math.max(1, Math.round(h * scale));

      var canvas = document.createElement('canvas');
      canvas.width = sw;
      canvas.height = sh;
      var ctx = canvas.getContext('2d');
      if (!ctx) {
        return null;
      }

      var data;
      try {
        ctx.drawImage(image, 0, 0, sw, sh);
        data = ctx.getImageData(0, 0, sw, sh).data;
      } catch (err) {
        /* Régebbi böngészőben a blob-kép is szennyezheti a vásznat. */
        return null;
      }

      var luma = new Uint8Array(sw * sh);
      for (var i = 0; i < sw * sh; i++) {
        luma[i] = Math.round(
          (0.299 * data[i * 4]) + (0.587 * data[i * 4 + 1]) + (0.114 * data[i * 4 + 2])
        );
      }

      var ratio = (typeof CFG.centerRatio === 'number') ? CFG.centerRatio : 0.70;
      var cw = Math.max(1, Math.round(sw * ratio));
      var ch = Math.max(1, Math.round(sh * ratio));

      var global = regionStats(luma, sw, sh, 0, 0, sw, sh);
      var center = regionStats(
        luma, sw, sh,
        Math.floor((sw - cw) / 2), Math.floor((sh - ch) / 2),
        cw, ch
      );
      if (!global || !center) {
        return null;
      }

      var fine = edgeStats(image);
      if (!fine) {
        return null;
      }

      return {
        global: global,
        center: center,
        edge: fine.edge,
        variance: fine.variance
      };
    }

    /* Élesség és szórás a régi 64x64-es mintáról. */
    function edgeStats(image) {
      var size = 64;
      var canvas = document.createElement('canvas');
      canvas.width = size;
      canvas.height = size;
      var ctx = canvas.getContext('2d');
      if (!ctx) {
        return null;
      }

      var data;
      try {
        ctx.drawImage(image, 0, 0, size, size);
        data = ctx.getImageData(0, 0, size, size).data;
      } catch (err) {
        return null;
      }

      var gray = new Float32Array(size * size);
      var sum = 0;
      var sumSq = 0;
      for (var i = 0; i < size * size; i++) {
        var v = (0.299 * data[i * 4]) + (0.587 * data[i * 4 + 1]) + (0.114 * data[i * 4 + 2]);
        gray[i] = v;
        sum += v;
        sumSq += v * v;
      }

      var n = size * size;
      var mean = sum / n;

      var edge = 0;
      var count = 0;
      for (var y = 1; y < size - 1; y++) {
        for (var x = 1; x < size - 1; x++) {
          var idx = (y * size) + x;
          edge += Math.abs(
            (4 * gray[idx]) - gray[idx - 1] - gray[idx + 1] - gray[idx - size] - gray[idx + size]
          );
          count++;
        }
      }

      return {
        edge: count ? edge / count : 0,
        variance: (sumSq / n) - (mean * mean)
      };
    }

    /*
     * Az ítélet négy szintű:
     *   ok   — nem nyúlunk hozzá,
     *   fix  — biztosan javítható, mi korrigáljuk,
     *   warn — van gond, de automatikusan nem piszkáljuk (valódi clipping),
     *   stop — használhatatlan.
     *
     * Egy kép egyszerre több bajban is lehet, ezért a rendszer mindet
     * összegyűjti; a kártyán csak a legfontosabb jelenik meg.
     */
    function judgeImage(image) {
      var w = image.naturalWidth;
      var h = image.naturalHeight;
      if (!w || !h) {
        return null;
      }

      var size = w + '×' + h + ' képpont';
      var minShort = cfg('minShortEdge', 240);
      var minLong = cfg('minLongEdge', 320);

      if (Math.min(w, h) < minShort || Math.max(w, h) < minLong) {
        return {
          level: 'stop',
          label: say('tooSmallLabel', 'Túl kicsi a kép'),
          hint: 'Ez a kép túl kicsi, ezért rosszul mutatna a hirdetésben. Válassz egy nagyobb méretű fotót.',
          issues: ['too_small'],
          fixes: []
        };
      }

      var stats = sampleStats(image);
      if (!stats) {
        /* Ha a vásznat nem tudjuk kiolvasni, inkább nem ígérünk semmit. */
        return null;
      }

      var c = stats.center;
      var g = stats.global;
      var issues = [];
      var fixes = [];

      /* Nagyon sötét képen természetesen kevés az élkontraszt, ezért ott az
         éles fotót is homályosnak hinnénk. Élességet csak normális
         expozíciós tartományban ítélünk meg. */
      var blurTestable = c.p50 >= cfg('blurTestMinMedian', 45)
        && c.p50 <= cfg('blurTestMaxMedian', 210);

      if (blurTestable && stats.edge < cfg('blurHard', 3.0) && stats.variance < 180) {
        return {
          level: 'stop',
          label: say('blurryLabel', 'Nem elég éles'),
          hint: 'Ez a fotó nem elég éles. Készíts másikat, és várd meg, amíg a kamera élesre áll.',
          issues: ['blur'],
          fixes: []
        };
      }

      var shadowClipped = c.p50 < cfg('shadowClippedMedian', 35)
        && c.shadowClip > cfg('shadowClippedFrac', 0.18);
      var darkRecoverable = c.p50 < cfg('darkCenterMedian', 60)
        && c.p90 < cfg('darkCenterP90', 170)
        && g.p50 < cfg('darkGlobalMedian', 90);

      if (shadowClipped) {
        issues.push('shadow_clipped');
      } else if (darkRecoverable) {
        issues.push('dark');
        fixes.push('tone_dark');
      }

      var highlightClipped = c.p50 > cfg('highlightClippedMed', 215)
        && c.highlightClip > cfg('highlightClippedFrac', 0.18);
      var brightRecoverable = c.p50 > cfg('brightCenterMedian', 198)
        && c.p10 > cfg('brightCenterP10', 85)
        && g.p50 > cfg('brightGlobalMedian', 180);

      if (highlightClipped) {
        issues.push('highlight_clipped');
      } else if (brightRecoverable && fixes.indexOf('tone_dark') === -1) {
        issues.push('bright');
        fixes.push('tone_bright');
      }

      var clipSafe = cfg('clipSafeFraction', 0.03);
      if (g.dynamicRange < cfg('lowContrastRange', 70)
        && g.shadowClip < clipSafe && g.highlightClip < clipSafe) {
        issues.push('low_contrast');
        fixes.push('contrast');
      }

      if (blurTestable && stats.edge < cfg('blurSoft', 7.0)) {
        issues.push('soft');
        fixes.push('soft');
      }

      if (Math.min(w, h) < cfg('smallHintShortEdge', 720)) {
        issues.push('small');
      }

      /* Figyelmeztetés esetén semmilyen automatikus javítást nem ígérünk —
         ugyanaz a szabály, mint a szerveren. */
      if (issues.indexOf('shadow_clipped') !== -1 || issues.indexOf('highlight_clipped') !== -1) {
        fixes = [];
      }

      return decorate(issues, fixes, size, stats);
    }

    /* A kártyán a legsúlyosabb gond jelenik meg, de mind ismert marad. */
    function decorate(issues, fixes, size, stats) {
      var base = {
        issues: issues,
        fixes: fixes,
        stats: stats
      };

      function withVerdict(level, label, hint) {
        base.level = level;
        base.label = label;
        base.hint = hint;
        return base;
      }

      if (issues.indexOf('shadow_clipped') !== -1) {
        return withVerdict('stop',
          say('shadowClippedLabel', 'Túl sötét lett'),
          say('shadowClipped', 'Ez a fotó túl sötét, ezért nem engedjük feltölteni. Készíts helyette egy újat világosabb helyen.'));
      }
      if (issues.indexOf('highlight_clipped') !== -1) {
        return withVerdict('stop',
          say('highlightClippedLabel', 'Túl világos lett'),
          say('highlightClipped', 'Ez a fotó túl világos, ezért nem engedjük feltölteni. Készíts helyette egy újat árnyékosabb helyen.'));
      }
      if (fixes.indexOf('tone_dark') !== -1) {
        return withVerdict('fix',
          say('darkFixLabel', 'Kivilágosítjuk'),
          say('darkFix', 'A fotó egy kicsit sötét, de feltöltéskor kivilágosítjuk. Nincs vele teendőd.'));
      }
      if (fixes.indexOf('tone_bright') !== -1) {
        return withVerdict('fix',
          say('brightFixLabel', 'Javítunk a fényén'),
          say('brightFix', 'A fotó egy kicsit túl világos, de feltöltéskor javítunk rajta. Nincs vele teendőd.'));
      }
      if (fixes.indexOf('contrast') !== -1) {
        return withVerdict('fix',
          say('lowContrastLabel', 'Szebbé tesszük'),
          say('lowContrastFix', 'A fotó kissé fakó, de feltöltéskor szebbé tesszük. Nincs vele teendőd.'));
      }
      if (fixes.indexOf('soft') !== -1) {
        return withVerdict('fix',
          say('softLabel', 'Élesítünk rajta'),
          say('softFix', 'A fotó egy kicsit életlen, de feltöltéskor javítunk rajta. Nincs vele teendőd.'));
      }
      if (issues.indexOf('small') !== -1) {
        return withVerdict('ok',
          say('smallLabel', 'Kisebb kép'),
          say('smallHint', 'A fotó használható, de egy nagyobb méretű képen szebben látszana az autó.'));
      }

      return withVerdict('ok',
        say('okLabel', 'Rendben'),
        'Ez a fotó megfelelő, feltöltheted.');
    }

    /* Az ítéletek a kártyák sorrendjében, hogy az összegzés tudja, mi a helyzet. */
    var verdicts = [];
    var summaryEl = null;
    /* Állítva fotózott képek (a kártyák sorrendjében): ezekhez egy kedves tipp jár
       (fordítsd el a telefont), de feltölteni így is lehet. */
    var upright = [];
    var tipEl = null;

    function ensureTip() {
      var box = ensureSummary();
      if (tipEl || !box) {
        return tipEl;
      }
      tipEl = document.createElement('p');
      tipEl.className = 'kmae-shots__tip';
      tipEl.hidden = true;
      box.parentNode.insertBefore(tipEl, box.nextSibling);
      return tipEl;
    }

    function updateTip() {
      var tip = ensureTip();
      if (!tip) {
        return;
      }
      var count = 0;
      for (var i = 0; i < picked.length; i++) {
        if (upright[i]) {
          count++;
        }
      }
      tip.textContent = count
        ? (count === 1 ? '1 fotó' : count + ' fotó') + ' állítva készült. Tipp: fordítsd el a telefont, és fektetve fotózd az autót. Így az egész autó belefér, és a hirdetésed telefonon és gépen is szebben mutat. Így is feltöltheted.'
        : '';
      tip.hidden = !count;
    }

    function ensureSummary() {
      if (summaryEl || !genericPreview || !genericPreview.parentNode) {
        return summaryEl;
      }
      summaryEl = document.createElement('p');
      summaryEl.className = 'kmae-shots__summary';
      summaryEl.hidden = true;
      genericPreview.parentNode.insertBefore(summaryEl, genericPreview.nextSibling);
      return summaryEl;
    }

    /* Egyetlen mondat arról, hogy van-e dolga a feltöltőnek — ez a lényeg,
       nem az egyes kártyák címkéi. */
    function updateSummary() {
      var box = ensureSummary();
      if (!box) {
        return;
      }
      updateTip();
      if (!picked.length) {
        box.hidden = true;
        return;
      }

      var stop = 0;
      var warn = 0;
      var fix = 0;
      for (var i = 0; i < picked.length; i++) {
        var v = verdicts[i];
        if (!v) {
          continue;
        }
        if (v.level === 'stop') {
          stop++;
        } else if (v.level === 'warn') {
          warn++;
        } else if (v.level === 'fix') {
          fix++;
        }
      }

      var level;
      var text;
      if (stop) {
        level = 'stop';
        text = stop === 1
          ? '1 fotót ki kell cserélned. A kép alatti üzenet megmutatja, melyiket.'
          : stop + ' fotót ki kell cserélned. A képek alatti üzenetek megmutatják, melyeket.';
      } else if (warn) {
        level = 'stop';
        text = (warn === 1 ? '1 fotót ki kell cserélned.' : warn + ' fotót ki kell cserélned.')
          + ' Ezeket nem engedjük feltölteni.';
      } else if (fix) {
        level = 'fix';
        text = (fix === 1 ? '1 képet' : fix + ' képet')
          + ' feltöltéskor automatikusan javítunk. Nincs vele teendőd.';
      } else {
        level = 'ok';
        text = 'Minden fotó megfelelő, feltöltheted őket.';
      }

      box.textContent = text;
      box.className = 'kmae-shots__summary is-' + level;
      box.hidden = false;
    }

    function buildCard(file, index) {
      var card = document.createElement('div');
      card.className = 'kmae-shot';

      var frame = document.createElement('div');
      frame.className = 'kmae-shot__frame';

      var image = document.createElement('img');
      image.src = URL.createObjectURL(file);
      image.alt = '';
      frame.appendChild(image);

      var isCover = index === 0 && (existingCount() === 0 || newCover);
      var isDoc = isDocument(file);
      if (isDoc) {
        card.classList.add('is-document');
      }
      var badge = document.createElement('span');
      badge.className = isCover ? 'kmae-shot__cover' : 'kmae-shot__cover kmae-shot__cover--new';
      badge.textContent = isCover ? 'Borítókép' : (isDoc ? 'Dokumentum' : 'Új');
      frame.appendChild(badge);

      var controls = document.createElement('div');
      controls.className = 'kmae-shot__tools';

      var cover = document.createElement('button');
      cover.type = 'button';
      cover.className = 'kmae-shot__tool';
      cover.textContent = '\u2605';
      cover.title = 'Legyen ez a borítókép';
      cover.setAttribute('aria-label', 'Legyen ez a borítókép');
      cover.onclick = function (event) {
        event.stopPropagation();
        if (existingCount() > 0) {
          setNewCover(true);
        }
        if (index === 0) {
          renderPreview();
        } else {
          moveImage(index, 0);
        }
      };
      controls.appendChild(cover);

      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'kmae-shot__tool kmae-shot__tool--remove';
      remove.textContent = '\u00d7';
      remove.title = 'Kép eltávolítása';
      remove.setAttribute('aria-label', 'Kép eltávolítása');
      remove.onclick = function (event) {
        event.stopPropagation();
        picked.splice(index, 1);
        verdicts.splice(index, 1);
        if (!picked.length && newCover) {
          setNewCover(false);
        }
        syncInputFiles();
        renderPreview();
      };
      controls.appendChild(remove);

      frame.appendChild(controls);
      card.appendChild(frame);

      /* A minőségi jelzés a kép ALATT ül, nem rajta: így a fotó látszik,
         a szöveg pedig olvasható marad. */
      var state = document.createElement('p');
      state.className = 'kmae-shot__state';
      state.hidden = true;
      card.appendChild(state);

      image.addEventListener('load', function () {
        /* Dokumentumoldal: nem fotó, a fény/élesség-mérce nem vonatkozik rá. */
        var verdict = isDoc ? null : judgeImage(image);
        verdicts[index] = verdict;
        upright[index] = !isDoc && image.naturalHeight > image.naturalWidth * 1.1;
        if (!verdict) {
          updateSummary();
          return;
        }
        state.className = 'kmae-shot__state is-' + verdict.level;
        state.textContent = verdict.label;
        state.title = verdict.hint;
        card.title = verdict.hint;
        state.hidden = false;
        card.classList.toggle('is-stop', verdict.level === 'stop');
        updateSummary();
      });

      frame.addEventListener('click', function () {
        openLightbox(image.src);
      });

      return card;
    }

    function renderPreview() {
      genericPreview.innerHTML = '';
      verdicts.length = picked.length;
      upright.length = 0;
      picked.forEach(function (file, index) {
        genericPreview.appendChild(buildCard(file, index));
      });
      syncUploadStatus();
      updateSummary();
    }

    if (genericInput && genericPreview) {
      var converting = 0;

      function setStatus(text) {
        if (uploadStatus) {
          uploadStatus.textContent = text;
          uploadStatus.hidden = !text;
        }
      }

      genericInput.addEventListener('change', function () {
        var incoming = Array.prototype.slice.call(genericInput.files || []);
        var full = false;
        var pdfs = [];

        incoming.forEach(function (file) {
          if (isPdf(file)) {
            pdfs.push(file);
            return;
          }
          if (!isImage(file) || alreadyPicked(file)) {
            return;
          }
          if (picked.length >= MAX_IMAGES) {
            full = true;
            return;
          }
          picked.push(file);
          detectSubject(file);
          shrinkImage(file).then(function (small) {
            var at = picked.indexOf(file);
            if (small === file || at === -1) {
              return;
            }
            picked[at] = small;
            syncInputFiles();
            renderPreview();
          });
        });

        /* A PDF-ek nem kerülnek a mezőbe: a helyükre az oldalak képei jönnek. */
        syncInputFiles();
        renderPreview();

        if (full) {
          setStatus('Legfeljebb ' + MAX_IMAGES + ' képet választhatsz. A további képek nem kerültek be.');
        }

        pdfs.forEach(function (pdfFile) {
          var room = MAX_IMAGES - picked.length;
          if (room <= 0) {
            setStatus('Legfeljebb ' + MAX_IMAGES + ' képet választhatsz. A PDF már nem fért be.');
            return;
          }
          converting++;
          if (uploader) {
            uploader.classList.add('is-converting');
          }
          setStatus('PDF átalakítása képpé…');
          pdfToImages(pdfFile, room).then(function (result) {
            var space = MAX_IMAGES - picked.length;
            var added = result.pages.slice(0, Math.max(0, space));
            added.forEach(function (page) {
              picked.push(page);
            });
            syncInputFiles();
            renderPreview();
            if (result.total > added.length) {
              setStatus('A PDF-ből ' + added.length + ' oldal került be (összesen ' + result.total + ' oldal; legfeljebb ' + MAX_IMAGES + ' kép lehet).');
            }
          }).catch(function () {
            setStatus('Ezt a PDF-et nem sikerült megnyitni. Lehet, hogy jelszóval védett vagy sérült.');
          }).then(function () {
            converting--;
            if (!converting && uploader) {
              uploader.classList.remove('is-converting');
            }
          });
        });
      });

      syncUploadStatus();
    }

    var listingForm = document.getElementById('kmae-listing-form');
    if (listingForm && genericInput) {
      listingForm.addEventListener('submit', function (event) {
        var blocked = verdicts.some(function (verdict) {
          return verdict && verdict.level === 'stop';
        });
        if (!blocked) {
          return;
        }
        event.preventDefault();
        var box = ensureSummary();
        if (box) {
          box.textContent = 'A pirossal jelölt fotókat ki kell cserélned. Ezeket sajnos nem engedjük feltölteni.';
          box.className = 'kmae-shots__summary is-stop';
          box.hidden = false;
          box.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    }

    if (!root) return;
    var step = document.getElementById('kmae-photo-step');
    var gateButton = document.getElementById('kmae-open-photo-step');
    var closeButton = document.getElementById('kmae-close-photo-step');
    if (gateButton && step) gateButton.addEventListener('click', function () {
      step.hidden = false;
      step.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
    if (closeButton && step) closeButton.addEventListener('click', function () {
      step.hidden = true;
    });
    var doneButton = document.getElementById('kmae-photo-done-btn');
    if (doneButton && step) doneButton.addEventListener('click', function () {
      if (!doneButton.disabled) step.hidden = true;
    });
    root.querySelectorAll('[data-kmae-photo-slot]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = root.querySelector('[data-kmae-slot-input="' + button.dataset.kmaePhotoSlot + '"]');
        if (input) input.click();
      });
    });
    root.querySelectorAll('[data-kmae-slot-input]').forEach(function (input) {
      input.addEventListener('change', function () {
        var slot = root.querySelector('[data-slot="' + input.dataset.kmaeSlotInput + '"]');
        var file = input.files && input.files[0];
        if (!slot || !file) return;
        var error = slot.querySelector('.kmae-photo-slot__error');
        if (!file.type || file.type.indexOf('image/') !== 0) {
          input.value = '';
          if (error) { error.textContent = 'Ezt a fájlt nem tudjuk fotóként megnyitni. Válassz egy másik képet.'; error.hidden = false; }
          return;
        }
        if (error) error.hidden = true;
        var preview = slot.querySelector('.kmae-photo-slot__preview');
        var url = URL.createObjectURL(file);
        if (preview) {
          preview.innerHTML = '';
          var image = document.createElement('img');
          image.src = url; image.alt = '';
          preview.appendChild(image);
        }
        slot.classList.add('is-filled');
        var label = slot.querySelector('.kmae-photo-slot__select-label');
        if (label) label.textContent = 'Fotó cseréje';
        var required = root.querySelectorAll('.kmae-photo-slot[data-required="1"] input[type="file"]');
        var ready = Array.prototype.every.call(required, function (field) { return field.files && field.files.length; });
        var done = document.getElementById('kmae-photo-done-btn');
        var notice = document.getElementById('kmae-photo-required-done');
        var hint = document.getElementById('kmae-photo-done-hint');
        if (done) done.disabled = !ready;
        if (notice) notice.hidden = !ready;
        if (hint) hint.hidden = ready;
      });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
}());
