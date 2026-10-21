(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    /* Create listing: sale mode + other extra */
    var priceAmountFields = document.querySelectorAll('[data-kmae-price-field]');
    var minimumCashField = document.querySelector('[data-kmae-minimum-field]');
    var extraOtherField = document.querySelector('.kmae-extra-other-field');
    var otherCommit = document.querySelector('input[data-kmae-commit="other"]');
    var priceModeRadios = document.querySelectorAll('input[name="kmae_price_mode"]');

    function togglePriceModeFields() {
      var mode = '';
      priceModeRadios.forEach(function (el) {
        if (el.checked) mode = el.value;
      });

      priceAmountFields.forEach(function (field) {
        var active = field.getAttribute('data-kmae-price-field') === mode;
        field.style.display = active ? 'block' : 'none';
        var input = field.querySelector('input');
        if (input) {
          input.required = active;
          input.disabled = !active;
          if (!active) {
            input.value = '';
            input.setCustomValidity('');
            if (input.dataset.kmaeFormatted === '1') {
              input.dataset.kmaeRawNumber = '';
            }
          }
        }
      });

      if (minimumCashField) {
        /* Fix árnál nincs rejtett minimum: a vevő pontosan a kiírt árat ajánlja. */
        var minimumActive = mode === 'guide' || mode === 'open';
        minimumCashField.style.display = minimumActive ? 'block' : 'none';
        var minimumInput = minimumCashField.querySelector('input');
        if (minimumInput) {
          minimumInput.disabled = !minimumActive;
          if (!minimumActive) {
            minimumInput.value = '';
            minimumInput.setCustomValidity('');
            if (minimumInput.dataset.kmaeFormatted === '1') {
              minimumInput.dataset.kmaeRawNumber = '';
            }
          }
        }
        minimumCashField.querySelectorAll('[data-kmae-minimum-hint]').forEach(function (hint) {
          hint.hidden = hint.getAttribute('data-kmae-minimum-hint') !== mode;
        });
      }

      if (window.kmaeListingFormNumbers) {
        var form = document.getElementById('kmae-listing-form');
        if (form) {
          window.kmaeListingFormNumbers.enhanceVisiblePriceField(form);
        }
      }
    }

    function toggleCreateFields() {
      if (extraOtherField && otherCommit) {
        extraOtherField.style.display = otherCommit.checked ? 'block' : 'none';
        /* Egyéb extránál kötelező a leírás; kipipálva a wizard nem enged tovább üresen. */
        var extraOtherInput = extraOtherField.querySelector('input');
        if (extraOtherInput) {
          extraOtherInput.required = otherCommit.checked;
        }
      }
    }

    priceModeRadios.forEach(function (el) {
      el.addEventListener('change', togglePriceModeFields);
    });
    if (otherCommit) {
      otherCommit.addEventListener('change', toggleCreateFields);
    }
    togglePriceModeFields();
    toggleCreateFields();

    function parseGalleryUrls(gallery) {
      if (!gallery) return [];
      try {
        var raw = gallery.getAttribute('data-full-urls');
        var urls = raw ? JSON.parse(raw) : [];
        return Array.isArray(urls) ? urls.filter(Boolean) : [];
      } catch (e) {
        return [];
      }
    }

    function getGalleryLightbox(gallery) {
      if (!gallery) return document.querySelector('.kmae-lightbox');
      var next = gallery.nextElementSibling;
      if (next && next.classList.contains('kmae-lightbox')) return next;
      return gallery.parentElement ? gallery.parentElement.querySelector('.kmae-lightbox') : null;
    }

    function getGalleryIndex(gallery) {
      var active = gallery.querySelector('.kmae-gallery-slide.active');
      if (!active) return 0;
      return parseInt(active.getAttribute('data-index'), 10) || 0;
    }

    function setGalleryIndex(gallery, idx) {
      var slides = gallery.querySelectorAll('.kmae-gallery-slide');
      var dots = gallery.querySelectorAll('.kmae-gallery-dot, .kmae-gallery-thumb');
      var counter = gallery.querySelector('.kmae-gallery-counter');
      if (!slides.length) return;

      if (idx < 0) idx = slides.length - 1;
      if (idx >= slides.length) idx = 0;

      slides.forEach(function (slide, i) {
        slide.classList.toggle('active', i === idx);
      });
      dots.forEach(function (dot) {
        var dotIndex = parseInt(dot.getAttribute('data-index'), 10);
        dot.classList.toggle('active', dotIndex === idx);
      });
      if (counter) {
        counter.textContent = (idx + 1) + ' / ' + slides.length;
      }
    }

    function navigateGallery(gallery, delta) {
      var slides = gallery.querySelectorAll('.kmae-gallery-slide');
      if (slides.length <= 1) return;
      setGalleryIndex(gallery, getGalleryIndex(gallery) + delta);
    }

    var activeLightboxGallery = null;
    var activeLightboxUrls = [];
    var activeLightboxIndex = 0;

    function updateLightboxImage(box) {
      if (!box || !activeLightboxUrls.length) return;
      var img = box.querySelector('.kmae-lightbox-img');
      if (!img) return;
      img.src = activeLightboxUrls[activeLightboxIndex] || '';
      box.setAttribute('data-index', String(activeLightboxIndex));

      var prev = box.querySelector('.kmae-lightbox-prev');
      var next = box.querySelector('.kmae-lightbox-next');
      var multi = activeLightboxUrls.length > 1;
      if (prev) prev.hidden = !multi;
      if (next) next.hidden = !multi;
    }

    function openLightbox(src, gallery, index) {
      var box = getGalleryLightbox(gallery);
      if (!box) return;

      activeLightboxGallery = gallery || null;
      activeLightboxUrls = gallery ? parseGalleryUrls(gallery) : (src ? [src] : []);
      if (!activeLightboxUrls.length && src) activeLightboxUrls = [src];

      activeLightboxIndex = typeof index === 'number' ? index : (gallery ? getGalleryIndex(gallery) : 0);
      if (activeLightboxIndex >= activeLightboxUrls.length) activeLightboxIndex = 0;

      updateLightboxImage(box);
      box.hidden = false;
      box.setAttribute('aria-hidden', 'false');
      document.body.classList.add('kmae-lightbox-open');
    }

    function closeLightbox() {
      document.querySelectorAll('.kmae-lightbox').forEach(function (box) {
        box.hidden = true;
        box.setAttribute('aria-hidden', 'true');
        var img = box.querySelector('.kmae-lightbox-img');
        if (img) img.src = '';
      });
      document.body.classList.remove('kmae-lightbox-open');
      activeLightboxGallery = null;
      activeLightboxUrls = [];
      activeLightboxIndex = 0;
    }

    function navigateLightbox(delta) {
      if (!activeLightboxUrls.length) return;
      var box = activeLightboxGallery ? getGalleryLightbox(activeLightboxGallery) : document.querySelector('.kmae-lightbox:not([hidden])');
      if (!box) return;
      activeLightboxIndex = (activeLightboxIndex + delta + activeLightboxUrls.length) % activeLightboxUrls.length;
      updateLightboxImage(box);
      if (activeLightboxGallery) {
        setGalleryIndex(activeLightboxGallery, activeLightboxIndex);
      }
    }

    document.querySelectorAll('.kmae-lightbox-close, .kmae-lightbox-backdrop').forEach(function (el) {
      el.addEventListener('click', closeLightbox);
    });

    document.querySelectorAll('.kmae-lightbox-prev').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        navigateLightbox(-1);
      });
    });

    document.querySelectorAll('.kmae-lightbox-next').forEach(function (btn) {
      btn.addEventListener('click', function (e) {
        e.stopPropagation();
        navigateLightbox(1);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closeLightbox();
        return;
      }
      var openBox = document.querySelector('.kmae-lightbox:not([hidden])');
      if (!openBox) return;
      if (e.key === 'ArrowLeft') navigateLightbox(-1);
      if (e.key === 'ArrowRight') navigateLightbox(1);
    });

    /* Adatlapon a Backspace visszalép a böngészéshez. Szövegmezőben törlés marad. */
    if (document.querySelector('.kmae-single-listing')) {
      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Backspace' || event.repeat || event.altKey || event.ctrlKey || event.metaKey) return;
        var target = event.target;
        if (target && target.closest && target.closest('input, textarea, select, [contenteditable="true"]')) return;
        event.preventDefault();
        if (window.history.length > 1) {
          window.history.back();
          return;
        }
        var back = document.querySelector('.kmae-single-listing .kmae-page-back a');
        if (back && back.href) window.location.href = back.href;
      });
    }

    /* PC: az adatlap képsorrendje = a böngészőé (Raul, 2026-10-20) — swipe-v2.js kind()/sortPhotos():
       téglalap (~2:1) / közép (3:2) / álló (3:4) a legközelebbi alak szerint, a borító típusa adja a
       sorrendet, típuson belül az eredeti; a borító marad az első. Csak a megjelenítés, a tárolt sorrend nem. */
    (function sortGalleryLikeBrowse() {
      if (!window.matchMedia || !window.matchMedia('(min-width: 1025px)').matches) return;
      var FRAMES = [2, 1.5, 0.75];
      var RANK = [[0, 1, 2], [2, 0, 1], [2, 1, 0]];
      function kind(w, h) {
        if (!w || !h) return 0;
        var a = Math.log(w / h);
        var k = 0;
        FRAMES.forEach(function (f, j) { if (Math.abs(a - Math.log(f)) < Math.abs(a - Math.log(FRAMES[k]))) k = j; });
        return k;
      }
      document.querySelectorAll('.kmae-gallery').forEach(function (gallery) {
        var stage = gallery.querySelector('.kmae-gallery-stage');
        var slides = Array.prototype.slice.call(gallery.querySelectorAll('.kmae-gallery-slide'));
        if (!stage || slides.length < 3) return;
        var kinds = slides.map(function (s) {
          return kind(parseInt(s.getAttribute('data-w'), 10) || 0, parseInt(s.getAttribute('data-h'), 10) || 0);
        });
        var r = RANK[kinds[0]];
        var order = slides.map(function (s, i) { return i; }).sort(function (a, b) { return r[kinds[a]] - r[kinds[b]] || a - b; });
        if (order.every(function (o, i) { return o === i; })) return;

        /* régi data-index → új sorszám (a pöttyök/bélyegképek data-index-szel kötődnek) */
        var map = {};
        order.forEach(function (o, i) { map[slides[o].getAttribute('data-index')] = i; });
        var anchor = stage.querySelector('.kmae-gallery-counter');
        order.forEach(function (o, i) {
          var s = slides[o];
          s.setAttribute('data-index', i);
          s.classList.toggle('active', i === 0);
          stage.insertBefore(s, anchor);
        });
        var urls = parseGalleryUrls(gallery);
        if (urls.length === slides.length) {
          gallery.setAttribute('data-full-urls', JSON.stringify(order.map(function (o) { return urls[o]; })));
        }
        gallery.querySelectorAll('.kmae-gallery-dots, .kmae-gallery-thumbs').forEach(function (box) {
          Array.prototype.slice.call(box.children).map(function (el) {
            var n = map[el.getAttribute('data-index')];
            return { el: el, n: n == null ? 1e6 : n };
          }).sort(function (a, b) { return a.n - b.n; }).forEach(function (x) {
            if (x.n !== 1e6) x.el.setAttribute('data-index', x.n);
            x.el.classList.toggle('active', x.n === 0);
            box.appendChild(x.el);
          });
        });
      });
    })();

    /* Gallery slides + dots + arrows + lightbox */
    document.querySelectorAll('.kmae-gallery').forEach(function (gallery) {
      var dots = gallery.querySelectorAll('.kmae-gallery-dot, .kmae-gallery-thumb');
      dots.forEach(function (dot) {
        dot.addEventListener('click', function (e) {
          e.stopPropagation();
          var idx = parseInt(dot.getAttribute('data-index'), 10);
          setGalleryIndex(gallery, idx);
        });
      });

      var prev = gallery.querySelector('.kmae-gallery-prev');
      var next = gallery.querySelector('.kmae-gallery-next');
      if (prev) {
        prev.addEventListener('click', function (e) {
          e.stopPropagation();
          navigateGallery(gallery, -1);
        });
      }
      if (next) {
        next.addEventListener('click', function (e) {
          e.stopPropagation();
          navigateGallery(gallery, 1);
        });
      }

      /* Nagyító gomb: PC-n az adatlapon a böngészés (swipe-v2.js full()) teljes képernyős
         galériája — a .kmae-gallery maga megy teljes képernyőre, a kinézet a theme
         single-listing-pc.css „Teljes képernyős galéria” blokkja; újra megnyomva / Esc kilép.
         Máshol (és ahol nincs Fullscreen API) a lightbox nyílik az éppen látott képpel. */
      var zoom = gallery.querySelector('.kmae-gallery-zoom');
      if (zoom) {
        zoom.addEventListener('click', function (e) {
          e.stopPropagation();
          var d = document;
          var go = gallery.requestFullscreen || gallery.webkitRequestFullscreen;
          var pc = window.matchMedia && window.matchMedia('(min-width: 1025px)').matches;
          if (pc && go && gallery.closest('.kmae-single-listing')) {
            if (d.fullscreenElement || d.webkitFullscreenElement) {
              (d.exitFullscreen || d.webkitExitFullscreen).call(d);
              return;
            }
            var h1 = d.querySelector('.kmae-single-listing .kmae-listing-title__name') || d.querySelector('.kmae-single-listing .kmae-listing-title');
            gallery.setAttribute('data-fs-title', h1 ? h1.textContent.replace(/\s+/g, ' ').trim() : '');
            /* A fotó a saját arányában: --kmae-ar diánként, és teljes szélességű fájl. */
            gallery.querySelectorAll('.kmae-gallery-slide').forEach(function (slide) {
              var img = slide.querySelector('.kmae-gallery-img');
              var w = parseInt(slide.getAttribute('data-w'), 10) || (img && img.naturalWidth) || 0;
              var h = parseInt(slide.getAttribute('data-h'), 10) || (img && img.naturalHeight) || 0;
              if (w && h) slide.style.setProperty('--kmae-ar', String(w / h));
              if (img && img.getAttribute('srcset')) img.setAttribute('sizes', '100vw');
            });
            var r = go.call(gallery);
            if (r && r.catch) r.catch(function () {});
            return;
          }
          openLightbox(null, gallery, getGalleryIndex(gallery));
        });
      }

      /* Teljes képernyőn a nyilakkal is lehet lapozni. */
      document.addEventListener('keydown', function (e) {
        if ((document.fullscreenElement || document.webkitFullscreenElement) !== gallery) return;
        if (e.key === 'ArrowLeft') navigateGallery(gallery, -1);
        if (e.key === 'ArrowRight') navigateGallery(gallery, 1);
      });

    });

    /* Card / thumb / form preview lightbox */
    document.querySelectorAll('.kmae-zoomable').forEach(function (el) {
      el.addEventListener('click', function (e) {
        var full = el.getAttribute('data-full') || el.getAttribute('src');
        if (full) {
          e.preventDefault();
          e.stopPropagation();
          openLightbox(full, null, 0);
        }
      });
    });

    /* Edit form — gallery manager */
    (function initGalleryManager() {
      var list = document.getElementById('kmae-gallery-manager');
      if (!list) return;

      var removedWrap = document.getElementById('kmae-gallery-removed-inputs');

      function visibleItems() {
        return Array.prototype.slice.call(list.querySelectorAll('.kmae-gallery-manager-item:not(.is-removed)'));
      }

      /* Ha egy új (még nem mentett) kép lett a borító, a meglévők közül
         egyik sem az (photo-motor.js → kmae_cover_new). */
      function newIsCover() {
        var flag = document.querySelector('input[name="kmae_cover_new"]');
        return !!flag && flag.value === '1';
      }
      function updateBadges() {
        list.querySelectorAll('.kmae-gallery-manager-badge').forEach(function (badge) {
          badge.remove();
        });
        var items = visibleItems();
        var first = newIsCover() ? null : items[0];

        /* A borítókép-gomb az elsőn kikapcsolva mutatja, hogy már az a borító. */
        items.forEach(function (item) {
          var cover = item.querySelector('.kmae-gallery-manager-cover');
          if (!cover) return;
          var isFirst = item === first;
          var label = cover.querySelector('span');
          cover.disabled = isFirst;
          if (label) {
            label.textContent = isFirst ? 'Ez a borítókép' : 'Borítókép';
          }
        });

        if (first) {
          var badge = document.createElement('span');
          badge.className = 'kmae-gallery-manager-badge';
          badge.textContent = 'Főkép';
          first.appendChild(badge);
        }
        document.dispatchEvent(new CustomEvent('kmae:gallery-changed'));
      }
      document.addEventListener('kmae:cover-new', updateBadges);
      document.addEventListener('kmae:cover-new-cleared', updateBadges);

      list.querySelectorAll('.kmae-gallery-manager-item').forEach(function (item) {
        var remove = item.querySelector('.kmae-gallery-manager-remove');
        var cover = item.querySelector('.kmae-gallery-manager-cover');

        if (cover) {
          cover.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var first = visibleItems()[0];
            if (!first) return;
            /* Meglévő kép lett a borító: az új kép borító-jelölése törlődik. */
            document.dispatchEvent(new CustomEvent('kmae:cover-existing'));
            var flag = document.querySelector('input[name="kmae_cover_new"]');
            if (flag) {
              flag.value = '0';
            }
            if (first !== item) {
              list.insertBefore(item, first);
            }
            updateBadges();
          });
        }

        if (remove) {
          remove.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var doRemove = function () {
              var id = item.getAttribute('data-id');
              var orderInput = item.querySelector('input[name="kmae_gallery_order[]"]');
              if (orderInput) orderInput.disabled = true;
              if (removedWrap && id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'kmae_remove_images[]';
                input.value = id;
                removedWrap.appendChild(input);
              }
              item.classList.add('is-removed');
              item.hidden = true;
              updateBadges();
            };

            /* Utolsó kép? (a mentett képek közül ez az egyetlen, és új kép sincs kiválasztva) */
            var left = list.querySelectorAll('.kmae-gallery-manager-item:not(.is-removed)').length;
            var fresh = document.querySelectorAll('#kmae-upload-preview .kmae-shot').length;
            var isLast = left <= 1 && fresh === 0;
            var thumbImg = item.querySelector('img');

            if (typeof window.kmaeConfirm !== 'function') {
              if (window.confirm('Biztosan törlöd ezt a képet?')) doRemove();
              return;
            }
            window.kmaeConfirm({
              eyebrow: 'Kép törlése',
              title: isLast ? 'Ez az utolsó fotó — törlöd?' : 'Biztosan törlöd ezt a képet?',
              thumb: thumbImg ? thumbImg.src : '',
              name: 'Fotó a hirdetésből',
              sub: 'A kép a hirdetés mentésekor törlődik.',
              notes: isLast
                ? ['Kép nélkül a hirdetés nem látszik a böngészésben.', 'Amint új fotót töltesz fel, újra megjelenik.']
                : [],
              warning: 'Ez nem vonható vissza.',
              okLabel: 'Igen, törlöm',
              cancelLabel: 'Mégse, megtartom'
            }).then(function (yes) {
              if (yes) doRemove();
            });
          });
        }
      });
    })();

    /* Chat: scroll to latest message */
    var chatThread = document.getElementById('kmae-chat-thread');
    if (chatThread) {
      chatThread.scrollTop = chatThread.scrollHeight;
    }

    /*
     * Élő frissítés — EGY ütemezett kérés (kmae_poll_page) frissíti a menü
     * „Üzenetek” számlálóját és figyeli az élő oldalakat (Üzenetek,
     * csevegés, hirdetés): változáskor újratölt.
     * - Betöltéskor nem kérdez azonnal: az oldal épp friss adattal jött.
     * - Aktív felhasználónál `interval` (4 mp), 1 perc tétlenség után
     *   `idleInterval` (25 mp); mozdulásra / fülre visszatéréskor azonnal kérdez.
     * - Rejtett fülön nem kérdez.
     * - Könnyűsúlyú jelző (sima .v fájl, se PHP, se WordPress): a körönkénti kérdés
     *   csak ezt olvassa; a teljes lekérdezés változáskor, 2 percenként
     *   (biztonsági háló), vagy a jelző 3 hibája után (régi működés) fut.
     */
    (function () {
      if (typeof kmaePublic === 'undefined' || !kmaePublic.livePoll) {
        return;
      }
      var root = document.querySelector('[data-kmae-live-page]');
      var badge = document.querySelector('[data-kmae-unseen]');
      if (!root && !badge) {
        return;
      }

      var cfgPoll = kmaePublic.livePoll;
      var context = root ? root.getAttribute('data-kmae-live-page') : 'nav_badge';
      var initialFingerprint = root ? (root.getAttribute('data-kmae-live-fingerprint') || '') : '';
      var activeInterval = cfgPoll.interval || 4000;
      var idleInterval = cfgPoll.idleInterval || 25000;
      var idleAfter = cfgPoll.idleAfter || 60000;
      /* Bekapcsolt böngészőértesítésnél (KMAE_Push) a push szól azonnal —
         a háttér-ellenőrzés csak tartalék, ezért sokkal ritkább. */
      var pushOn = !!(window.kmaePush && window.kmaePush.enabled && window.Notification && window.Notification.permission === 'granted');
      if (pushOn) {
        activeInterval = 20000;
        idleInterval = 60000;
      }
      var lastActivity = Date.now();
      var timer = null;
      var inFlight = false;
      var lastSignal = cfgPoll.signalVersion || '';
      var signalFails = 0;
      var lastFull = Date.now();
      var fullEvery = pushOn ? 300000 : (cfgPoll.fullEvery || 120000);

      function composing() {
        var active = document.activeElement;
        if (!active) {
          return false;
        }
        var tag = active.tagName;
        if (tag !== 'TEXTAREA' && tag !== 'INPUT') {
          return false;
        }
        return String(active.value || '').length > 0;
      }

      /* Nyitott megerősítő / bejelentő ablak közben nem töltünk újra
         (a következő kör pótolja, ha bezárta). */
      function modalOpen() {
        return Array.prototype.some.call(
          document.querySelectorAll('dialog[open], [aria-modal="true"]'),
          function (el) {
            return el.getClientRects().length > 0;
          }
        );
      }

      function updateBadge(count) {
        if (!badge) {
          return;
        }
        if (count > 0) {
          badge.textContent = String(count);
          badge.removeAttribute('hidden');
        } else {
          badge.setAttribute('hidden', '');
        }
      }

      function isIdle() {
        return Date.now() - lastActivity > idleAfter;
      }

      function schedule() {
        window.clearTimeout(timer);
        if (document.hidden) {
          return;
        }
        timer = window.setTimeout(tick, isIdle() ? idleInterval : activeInterval);
      }

      function useSignal() {
        return !!cfgPoll.signalUrl && signalFails < 3;
      }

      /* Egy kör: előbb csak a könnyű jelzőt kérdezzük; a teljes lekérdezés
         akkor fut, ha az változott (vagy letelt a biztonsági háló). */
      function tick() {
        window.clearTimeout(timer);
        if (document.hidden || inFlight) {
          return;
        }
        if (!useSignal() || Date.now() - lastFull >= fullEvery) {
          fullPoll();
          return;
        }
        inFlight = true;

        fetch(cfgPoll.signalUrl + '?t=' + Date.now(), {
          method: 'GET',
          credentials: 'omit',
          cache: 'no-store',
        })
          .then(function (response) {
            if (!response.ok) {
              throw new Error('signal');
            }
            return response.text();
          })
          .then(function (text) {
            var value = String(text || '').trim();
            if (!/^[0-9]+$/.test(value)) {
              throw new Error('signal');
            }
            signalFails = 0;
            inFlight = false;
            if (value !== lastSignal) {
              lastSignal = value;
              fullPoll();
              return;
            }
            schedule();
          })
          .catch(function () {
            signalFails++;
            inFlight = false;
            fullPoll();
          });
      }

      function fullPoll() {
        window.clearTimeout(timer);
        if (document.hidden || inFlight) {
          return;
        }
        inFlight = true;
        lastFull = Date.now();

        var body = new FormData();
        body.append('action', 'kmae_poll_page');
        body.append('nonce', cfgPoll.nonce);
        body.append('context', context);

        if (context === 'conversation_thread') {
          body.append('conversation_id', root.getAttribute('data-kmae-conversation-id') || '');
        } else if (context === 'single_listing') {
          body.append('listing_id', root.getAttribute('data-kmae-listing-id') || '');
        }

        fetch(cfgPoll.ajaxUrl, {
          method: 'POST',
          body: body,
          credentials: 'same-origin',
          cache: 'no-store',
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (data) {
            if (!data || !data.success || !data.data) {
              /* Sikertelen kör: a következő kör újra teljes lekérdezést futtat. */
              lastSignal = '';
              return;
            }
            if (typeof data.data.unseen !== 'undefined') {
              updateBadge(parseInt(data.data.unseen, 10) || 0);
            }
            if (root && data.data.fingerprint && data.data.fingerprint !== initialFingerprint) {
              if (!composing() && !modalOpen()) {
                window.location.reload();
              } else {
                /* Most nem frissíthetünk: a következő kör újra teljes lekérdezést futtat. */
                lastSignal = '';
              }
            }
          })
          .catch(function () {
            lastSignal = '';
          })
          .then(function () {
            inFlight = false;
            schedule();
          });
      }

      /* Tétlenségből visszatérve azonnal kérdezünk, hogy ne kelljen a
         hosszabb várakozást kivárni. */
      function markActive() {
        var wasIdle = isIdle();
        lastActivity = Date.now();
        if (wasIdle && !inFlight && !document.hidden) {
          tick();
        }
      }
      ['mousemove', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (type) {
        window.addEventListener(type, markActive, { passive: true });
      });

      document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
          window.clearTimeout(timer);
          return;
        }
        lastActivity = Date.now();
        tick();
      });

      /* Push érkezett (push-sw.js): azonnal frissítünk, nem várjuk a kört. */
      function onPush(event) {
        if (event && event.data && event.data.type === 'kmae-push') {
          if (inFlight) {
            return;
          }
          fullPoll();
        }
      }
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', onPush);
      }
      if ('BroadcastChannel' in window) {
        try {
          new BroadcastChannel('kmae-push').addEventListener('message', onPush);
        } catch (e) {}
      }

      /* Az oldal (a fejléc számlálójával együtt) gyorsítótárból is jöhet régi
         értékkel — betöltés után egyszer azonnal teljes lekérdezés javítja. */
      lastFull = 0;
      timer = window.setTimeout(tick, 500);
    })();

    document.querySelectorAll('[data-kmae-chat-compose]').forEach(function (form) {
      var fileInputs = form.querySelectorAll('[data-kmae-chat-file-input]');
      var fileName = form.querySelector('[data-kmae-chat-file-name]');
      var text = form.querySelector('[data-kmae-chat-text]');
      var emojiBox = form.querySelector('[data-kmae-chat-emoji]');
      var emojiToggle = form.querySelector('[data-kmae-chat-emoji-toggle]');
      var maxBytes = 3 * 1024 * 1024;

      function selectedFile() {
        var found = null;
        fileInputs.forEach(function (input) {
          if (input.files && input.files[0]) {
            found = input.files[0];
          }
        });
        return found;
      }

      form.querySelectorAll('[data-kmae-chat-pick]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var kind = btn.getAttribute('data-kmae-chat-pick');
          var input = form.querySelector('[data-kmae-chat-file-input="' + kind + '"]');
          if (input) {
            input.click();
          }
        });
      });

      fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
          var file = input.files && input.files[0] ? input.files[0] : null;
          fileInputs.forEach(function (other) {
            if (other !== input) {
              other.value = '';
            }
          });
          if (file && file.size > maxBytes) {
            input.value = '';
            if (fileName) {
              fileName.textContent = 'A fájl legfeljebb 3 MB lehet.';
              fileName.classList.add('is-error');
              fileName.hidden = false;
            }
            return;
          }
          if (fileName) {
            fileName.classList.remove('is-error');
            fileName.textContent = '';
            if (file) {
              var kind = input.getAttribute('data-kmae-chat-file-input') === 'image' ? 'Kép' : 'Fájl';
              var label = document.createElement('span');
              label.textContent = kind + ': ' + file.name;
              var ttl = document.createElement('span');
              ttl.className = 'kmae-free-chat__file-ttl';
              ttl.textContent = '48 óra múlva törlődik';
              fileName.appendChild(label);
              fileName.appendChild(ttl);
            }
            fileName.hidden = !file;
          }
        });
      });

      if (emojiToggle && emojiBox) {
        emojiToggle.addEventListener('click', function () {
          var open = emojiBox.hasAttribute('hidden');
          if (open) {
            emojiBox.removeAttribute('hidden');
          } else {
            emojiBox.setAttribute('hidden', '');
          }
          emojiToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
      }

      form.querySelectorAll('[data-kmae-chat-emoji-insert]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!text) {
            return;
          }
          text.value += btn.getAttribute('data-kmae-chat-emoji-insert') || '';
          text.focus();
        });
      });

      form.addEventListener('submit', function (event) {
        var hasText = text && text.value.trim() !== '';
        var hasFile = !!selectedFile();
        if (!hasText && !hasFile) {
          event.preventDefault();
          if (text) {
            text.focus();
          }
        }
      });
    });

    document.addEventListener('click', function (event) {
      var opener = event.target.closest('[data-kmae-chat-image]');
      if (!opener) {
        var lightbox = event.target.closest('.kmae-chat-lightbox');
        if (lightbox && event.target === lightbox) {
          lightbox.remove();
        }
        return;
      }
      var src = opener.getAttribute('data-kmae-chat-image');
      if (!src) {
        return;
      }
      var overlay = document.createElement('div');
      overlay.className = 'kmae-chat-lightbox';
      overlay.innerHTML = '<img alt="" src="' + src.replace(/"/g, '&quot;') + '" />';
      overlay.addEventListener('click', function () {
        overlay.remove();
      });
      document.body.appendChild(overlay);
    });

    /* Bejelentés ablak (hirdetés + chat): natív <dialog>, így semmilyen
       szülő elem (üveg, transform) nem vágja le. Háttérre kattintva bezárul. */
    document.addEventListener('click', function (event) {
      var opener = event.target.closest('[data-kmae-report-open]');
      if (opener) {
        var dialog = document.getElementById(opener.getAttribute('data-kmae-report-open'));
        if (dialog && typeof dialog.showModal === 'function') {
          event.preventDefault();
          dialog.showModal();
        }
        return;
      }
      var closer = event.target.closest('[data-kmae-report-close]');
      if (closer) {
        var parent = closer.closest('dialog');
        if (parent) {
          event.preventDefault();
          parent.close();
        }
        return;
      }
      if (event.target.matches && event.target.matches('dialog.kmae-report')) {
        event.target.close();
      }
    });
    /* Chat jobb oszlop: kedvencekhez adás / kivétel (ugyanaz az AJAX, mint a böngészésben). */
    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-kmae-chat-favorite]');
      if (!button || button.disabled) {
        return;
      }
      var wasSaved = button.classList.contains('is-saved');
      var label = button.querySelector('[data-kmae-chat-favorite-label]');
      var setSaved = function (saved) {
        button.classList.toggle('is-saved', saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        if (label) {
          label.textContent = button.getAttribute(saved ? 'data-label-saved' : 'data-label-add') || '';
        }
      };
      var body = new FormData();
      body.append('action', wasSaved ? 'kmae_favorite_remove' : 'kmae_favorite_add');
      body.append('nonce', button.getAttribute('data-kmae-nonce') || '');
      body.append('listing_id', button.getAttribute('data-kmae-chat-favorite') || '');
      setSaved(!wasSaved);
      button.disabled = true;
      fetch(button.getAttribute('data-kmae-ajax') || '', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (!data || !data.success) {
            setSaved(wasSaved);
            return;
          }
          if (data.data && typeof data.data.unseen !== 'undefined') {
            var badge = document.querySelector('[data-kmae-favorites-unseen]');
            var count = parseInt(data.data.unseen, 10) || 0;
            if (badge) {
              badge.textContent = String(count);
              if (count > 0) badge.removeAttribute('hidden');
              else badge.setAttribute('hidden', '');
            }
          }
        })
        .catch(function () { setSaved(wasSaved); })
        .then(function () { button.disabled = false; });
    });
    /* Ajánlatot teszek panel: az X gombra, vagy a panelon kívülre kattintva bezárul. */
    document.addEventListener('click', function (event) {
      document.querySelectorAll('details.kmae-chat-offer[open]').forEach(function (panel) {
        var onClose = panel.contains(event.target) && !!event.target.closest('[data-kmae-offer-close]');
        if (onClose || !panel.contains(event.target)) {
          panel.removeAttribute('open');
        }
      });
    });
    /* Üzenetek lista piros X: „Biztosan törlöd?” ablak (élő ajánlatnál
       figyelmeztetés a visszavonásról), csak a megerősítés után küld. */
    document.addEventListener('click', function (event) {
      var dialog = document.getElementById('kmae-inbox-confirm');
      if (!dialog || typeof dialog.showModal !== 'function') {
        return;
      }
      var opener = event.target.closest('[data-kmae-inbox-delete]');
      if (opener) {
        event.preventDefault();
        var note = opener.getAttribute('data-kmae-inbox-delete-note') || '';
        var noteEl = dialog.querySelector('[data-kmae-inbox-confirm-note]');
        dialog.querySelector('[data-kmae-inbox-confirm-id]').value = opener.getAttribute('data-kmae-inbox-delete');
        if (noteEl) {
          noteEl.textContent = note;
          noteEl.hidden = note === '';
        }
        dialog.showModal();
        return;
      }
      if (event.target.closest('[data-kmae-inbox-confirm-cancel]') || event.target === dialog) {
        dialog.close();
      }
    });
    /* „Más” okhoz kötelező a leírás. */
    document.addEventListener('change', function (event) {
      if (!event.target.matches || !event.target.matches('.kmae-report input[name="kmae_report_reason"]')) {
        return;
      }
      var details = event.target.form && event.target.form.querySelector('textarea[name="kmae_report_details"]');
      if (details) {
        details.required = event.target.value === 'other';
      }
    });

    /* „Mire figyelj” kép (vevő, elfogadott ajánlat után): nagyító ráközelítéssel.
       Kattintás a képre: nagyítás / vissza; görgő: fokozatos nagyítás;
       nagyítva húzással mozgatható. */
    document.addEventListener('click', function (event) {
      var opener = event.target.closest('[data-kmae-tips-image]');
      if (!opener) {
        return;
      }
      event.preventDefault();
      var src = opener.getAttribute('data-kmae-tips-image');
      if (!src) {
        return;
      }
      var overlay = document.createElement('div');
      overlay.className = 'kmae-tips-viewer';
      overlay.setAttribute('role', 'dialog');
      overlay.setAttribute('aria-modal', 'true');
      overlay.innerHTML =
        '<div class="kmae-tips-viewer__bar">' +
        '<span class="kmae-tips-viewer__title"></span>' +
        '<a class="kmae-tips-viewer__btn kmae-tips-viewer__save" download="mire-figyelj.jpg" aria-label="Kép mentése" title="Kép mentése">' +
        '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11"/><path d="M7 10l5 5 5-5"/><path d="M5 20h14"/></svg>' +
        '</a>' +
        '<button type="button" class="kmae-tips-viewer__btn" data-zoom="-1" aria-label="Kicsinyítés">&minus;</button>' +
        '<button type="button" class="kmae-tips-viewer__btn" data-zoom="1" aria-label="Nagyítás">+</button>' +
        '<button type="button" class="kmae-tips-viewer__btn kmae-tips-viewer__close" aria-label="Bezárás">&times;</button>' +
        '</div><div class="kmae-tips-viewer__stage"><img alt="" /></div>';
      var title = opener.getAttribute('data-kmae-tips-title') || '';
      overlay.querySelector('.kmae-tips-viewer__title').textContent = title;
      overlay.setAttribute('aria-label', title);
      var stage = overlay.querySelector('.kmae-tips-viewer__stage');
      var img = stage.querySelector('img');
      img.src = src;
      overlay.querySelector('.kmae-tips-viewer__save').href = src;
      var scale = 1;

      function fitWidth() {
        var ratio = img.naturalWidth && img.naturalHeight ? img.naturalWidth / img.naturalHeight : 1;
        return Math.min(stage.clientWidth - 32, (stage.clientHeight - 32) * ratio);
      }

      function setScale(next, x, y) {
        next = Math.max(1, Math.min(4, next));
        var rect = img.getBoundingClientRect();
        var rx = x == null || !rect.width ? 0.5 : (x - rect.left) / rect.width;
        var ry = y == null || !rect.height ? 0.5 : (y - rect.top) / rect.height;
        scale = next;
        overlay.classList.toggle('is-zoomed', scale > 1);
        img.style.width = scale > 1 ? Math.round(fitWidth() * scale) + 'px' : '';
        if (scale > 1) {
          stage.scrollLeft = img.offsetLeft + rx * img.offsetWidth - stage.clientWidth / 2;
          stage.scrollTop = img.offsetTop + ry * img.offsetHeight - stage.clientHeight / 2;
        }
      }

      function close() {
        overlay.remove();
        document.removeEventListener('keydown', onKey);
      }

      function onKey(e) {
        if (e.key === 'Escape') {
          close();
        }
      }

      /* Nagyítva a kép húzással mozgatható (fel-le, oldalra); a húzás végén
         érkező kattintás nem vált nagyítást. */
      var drag = null;
      var suppressClick = false;
      img.draggable = false;

      function endDrag() {
        if (!drag) {
          return;
        }
        suppressClick = drag.moved;
        drag = null;
        overlay.classList.remove('is-dragging');
      }

      img.addEventListener('pointerdown', function (e) {
        suppressClick = false;
        if (scale <= 1 || (e.pointerType === 'mouse' && e.button !== 0)) {
          return;
        }
        drag = { x: e.clientX, y: e.clientY, left: stage.scrollLeft, top: stage.scrollTop, moved: false };
        img.setPointerCapture(e.pointerId);
        e.preventDefault();
      });
      img.addEventListener('pointermove', function (e) {
        if (!drag) {
          return;
        }
        var dx = e.clientX - drag.x;
        var dy = e.clientY - drag.y;
        if (!drag.moved) {
          if (Math.abs(dx) + Math.abs(dy) < 4) {
            return;
          }
          drag.moved = true;
          overlay.classList.add('is-dragging');
        }
        stage.scrollLeft = drag.left - dx;
        stage.scrollTop = drag.top - dy;
      });
      img.addEventListener('pointerup', endDrag);
      img.addEventListener('pointercancel', endDrag);

      overlay.addEventListener('click', function (e) {
        var zoom = e.target.closest('[data-zoom]');
        if (zoom) {
          setScale(scale + Number(zoom.getAttribute('data-zoom')));
        } else if (e.target.closest('.kmae-tips-viewer__close') || e.target === stage) {
          close();
        } else if (e.target === img) {
          if (suppressClick) {
            suppressClick = false;
            return;
          }
          setScale(scale > 1 ? 1 : 2.5, e.clientX, e.clientY);
        }
      });
      stage.addEventListener('wheel', function (e) {
        e.preventDefault();
        setScale(scale + (e.deltaY < 0 ? 0.5 : -0.5), e.clientX, e.clientY);
      }, { passive: false });
      document.addEventListener('keydown', onKey);
      document.body.appendChild(overlay);
    });

    /* Adásvételi szerződés aloldal: nyomtatás + a megjegyzés mező nő a szöveggel. */
    document.querySelectorAll('[data-kmae-print]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        window.print();
      });
    });
    document.querySelectorAll('[data-kmae-autogrow]').forEach(function (area) {
      var grow = function () {
        area.style.height = 'auto';
        area.style.height = area.scrollHeight + 'px';
      };
      area.addEventListener('input', grow);
      grow();
    });

  });
})();
