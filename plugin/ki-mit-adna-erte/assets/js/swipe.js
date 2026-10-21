(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var stack = document.getElementById('kmae-swipe-stack');
    if (!stack) return;

    var cards = Array.prototype.slice.call(stack.querySelectorAll('.kmae-swipe-card'));
    var current = 0;
    var sliding = false;
    var storageKey = 'kmaeBrowseListing';
    /* A kirepülő kártya ideje — egyezzen a browse.css kártya-`transition`-jével (0.18s). */
    var SLIDE_MS = 180;

    function topCard() {
      return cards[current] || null;
    }

    function remember(card) {
      if (!card) return;
      try {
        sessionStorage.setItem(storageKey, card.getAttribute('data-listing-id') || '');
      } catch (error) {}
    }

    function recordPass(listingId) {
      if (!listingId || typeof kmaeSwipe === 'undefined' || !kmaeSwipe.loggedIn) return;
      var body = new FormData();
      body.append('action', 'kmae_browse_pass');
      body.append('nonce', kmaeSwipe.nonce);
      body.append('listing_id', listingId);
      fetch(kmaeSwipe.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' });
    }

    function syncHeart() {
      var card = topCard();
      if (!card) return;
      var saved = card.getAttribute('data-favorite') === '1';
      card.querySelectorAll('.kmae-swipe-like').forEach(function (likeBtn) {
        likeBtn.classList.toggle('is-saved', saved);
        likeBtn.setAttribute('aria-pressed', saved ? 'true' : 'false');
      });
    }

    function syncArrows() {
      var prevBtn = document.getElementById('kmae-swipe-prev');
      var nextBtn = document.getElementById('kmae-swipe-next');
      if (prevBtn) prevBtn.hidden = current <= 0;
      if (nextBtn) nextBtn.hidden = current >= cards.length - 1;
    }

    /* Előtöltés: a rejtett (display: none) kártyák `loading="lazy"` képe magától
       csak a megjelenéskor indulna — ezért váltáskor üres kártya villant. */
    var preloaded = {};

    function preloadUrl(url) {
      if (!url || preloaded[url]) return;
      var img = new Image();
      img.decoding = 'async';
      img.src = url;
      preloaded[url] = img;
    }

    function preloadAround() {
      /* Az első kártya képe ne versenyezzen az előtöltéssel. */
      if (document.readyState !== 'complete') {
        window.addEventListener('load', preloadAround, { once: true });
        return;
      }
      [current + 1, current + 2, current - 1].forEach(function (i) {
        var img = cards[i] && cards[i].querySelector('img.kmae-card-image');
        if (img) preloadUrl(img.getAttribute('src'));
      });
      var own = cards[current] && cards[current].querySelector('img.kmae-card-image');
      if (own) {
        try {
          var photos = JSON.parse(own.getAttribute('data-photos') || '[]');
          if (Array.isArray(photos) && photos[1]) preloadUrl(photos[1]);
        } catch (error) {}
      }
    }

    function showCurrent() {
      var card = topCard();
      if (!card) return;
      cards.forEach(function (item, i) {
        item.classList.toggle('is-current', i === current);
        if (i !== current) item.style.display = 'none';
      });
      card.style.display = '';
      card.style.opacity = '1';
      card.style.transform = '';
      card.style.zIndex = String(cards.length);
      remember(card);
      syncHeart();
      syncArrows();
      preloadAround();
    }

    function swipeAway(direction) {
      var card = topCard();
      if (!card) return;

      if (direction === 'pass') {
        recordPass(card.getAttribute('data-listing-id'));
      }

      card.style.transform = 'translateX(-120%) rotate(-12deg)';
      card.style.opacity = '0';
      setTimeout(function () {
        card.style.display = 'none';
        current++;
        showCurrent();
      }, SLIDE_MS);
    }

    function stepPrev() {
      if (sliding || current <= 0) return;
      var card = topCard();
      if (!card) return;
      sliding = true;
      card.style.transform = 'translateX(-120%) rotate(-12deg)';
      card.style.opacity = '0';
      setTimeout(function () {
        card.style.display = 'none';
        current--;
        sliding = false;
        showCurrent();
      }, SLIDE_MS);
    }

    function stepNext() {
      if (sliding || current >= cards.length - 1) return;
      var card = topCard();
      if (!card) return;
      sliding = true;
      card.style.transform = 'translateX(120%) rotate(12deg)';
      card.style.opacity = '0';
      setTimeout(function () {
        card.style.display = 'none';
        current++;
        sliding = false;
        showCurrent();
      }, SLIDE_MS);
    }

    function openCurrent() {
      var card = topCard();
      if (!card) return;
      var link = card.querySelector('.kmae-swipe-open') || card.querySelector('.kmae-swipe-cta');
      if (!link) return;
      remember(card);
      window.location.href = link.href;
    }

    function markSaved(card, saved) {
      if (card) card.setAttribute('data-favorite', saved ? '1' : '0');
      syncHeart();
    }

    function saveCurrent() {
      var card = topCard();
      if (!card) return;
      if (typeof kmaeSwipe === 'undefined' || !kmaeSwipe.loggedIn) {
        if (typeof kmaeSwipe !== 'undefined' && kmaeSwipe.loginUrl) {
          window.location.href = kmaeSwipe.loginUrl;
        }
        return;
      }
      var wasSaved = card.getAttribute('data-favorite') === '1';
      var listingId = card.getAttribute('data-listing-id');
      markSaved(card, !wasSaved);
      var body = new FormData();
      body.append('action', wasSaved ? 'kmae_favorite_remove' : 'kmae_favorite_add');
      body.append('nonce', kmaeSwipe.favoriteNonce);
      body.append('listing_id', listingId);
      fetch(kmaeSwipe.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          if (!data || !data.success) {
            markSaved(card, wasSaved);
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
        .catch(function () { markSaved(card, wasSaved); });
    }

    function restore() {
      var savedId = '';
      try {
        savedId = sessionStorage.getItem(storageKey) || '';
      } catch (error) {}
      if (!savedId) return;
      var index = -1;
      cards.forEach(function (card, i) {
        if (card.getAttribute('data-listing-id') === savedId) index = i;
      });
      if (index > 0) {
        for (var i = 0; i < index; i++) {
          cards[i].style.display = 'none';
        }
        current = index;
      }
    }

    function typingTarget(target) {
      if (!target || !target.closest) return false;
      /* A rendezőmenüben és a távolság ablakban a nyilak / Enter / Space az övék. */
      return !!target.closest('input, textarea, select, [contenteditable="true"], [data-kmae-sort], [data-kmae-dist]');
    }

    var passBtn = document.getElementById('kmae-swipe-pass');
    var prevBtn = document.getElementById('kmae-swipe-prev');
    var nextBtn = document.getElementById('kmae-swipe-next');
    if (passBtn) passBtn.addEventListener('click', function () { swipeAway('pass'); });
    if (prevBtn) prevBtn.addEventListener('click', stepPrev);
    if (nextBtn) nextBtn.addEventListener('click', stepNext);
    stack.addEventListener('click', function (event) {
      var photoNav = event.target.closest('.kmae-swipe-photo-nav');
      if (photoNav && stack.contains(photoNav)) {
        event.preventDefault();
        event.stopPropagation();
        var photoCard = photoNav.closest('.kmae-swipe-card');
        var photoImg = photoCard ? photoCard.querySelector('.kmae-card-image') : null;
        if (!photoImg) return;
        var photos = [];
        try {
          photos = JSON.parse(photoImg.getAttribute('data-photos') || '[]');
        } catch (error) {
          photos = [];
        }
        if (!Array.isArray(photos) || photos.length < 2) return;
        var photoIndex = parseInt(photoImg.getAttribute('data-photo-index') || '0', 10) || 0;
        if (photoNav.classList.contains('kmae-swipe-photo-next')) {
          if (photoIndex >= photos.length - 1) return;
          photoIndex++;
        } else if (photoIndex <= 0) {
          return;
        } else {
          photoIndex--;
        }
        photoImg.setAttribute('data-photo-index', String(photoIndex));
        photoImg.src = photos[photoIndex];
        var photoCount = photoCard.querySelector('.kmae-swipe-photo-count');
        if (photoCount) photoCount.textContent = (photoIndex + 1) + ' / ' + photos.length;
        photoCard.querySelectorAll('.kmae-swipe-photo-dot').forEach(function (dot, dotIndex) {
          dot.classList.toggle('is-current', dotIndex === photoIndex);
        });
        var photoPrev = photoCard.querySelector('.kmae-swipe-photo-prev');
        var photoNext = photoCard.querySelector('.kmae-swipe-photo-next');
        if (photoPrev) photoPrev.hidden = photoIndex <= 0;
        if (photoNext) photoNext.hidden = photoIndex >= photos.length - 1;
        return;
      }
      var likeBtn = event.target.closest('.kmae-swipe-like');
      if (!likeBtn || !stack.contains(likeBtn)) return;
      event.preventDefault();
      event.stopPropagation();
      saveCurrent();
    });

    stack.querySelectorAll('.kmae-swipe-open, .kmae-swipe-cta').forEach(function (link) {
      link.addEventListener('click', function () {
        var card = link.closest('.kmae-swipe-card');
        remember(card);
      });
    });

    /* Telefon: ujjal húzás — balra = következő, jobbra = előző autó.
       Függőleges mozdulatnál a görgetés marad (touch-action: pan-y a CSS-ben). */
    var drag = null;
    var suppressClick = false;

    function releaseDrag(card, dx) {
      card.style.transition = '';
      var width = stack.offsetWidth || 1;
      var dir = dx < 0 ? -1 : 1;
      var canMove = dir < 0 ? current < cards.length - 1 : current > 0;
      if (!canMove || Math.abs(dx) < Math.min(110, width * 0.28)) {
        card.style.transform = '';
        return;
      }
      sliding = true;
      card.style.transform = 'translateX(' + (dir * 130) + '%) rotate(' + (dir * 14) + 'deg)';
      card.style.opacity = '0';
      setTimeout(function () {
        card.style.display = 'none';
        current += dir < 0 ? 1 : -1;
        sliding = false;
        showCurrent();
      }, SLIDE_MS);
    }

    function finishTouch() {
      if (!drag) return;
      var ended = drag;
      drag = null;
      if (!ended.active) return;
      suppressClick = true;
      setTimeout(function () { suppressClick = false; }, 350);
      releaseDrag(ended.card, ended.dx);
    }

    stack.addEventListener('touchstart', function (event) {
      if (sliding || event.touches.length !== 1) return;
      var card = topCard();
      if (!card || !card.contains(event.target) || event.target.closest('.kmae-swipe-like, .kmae-swipe-photo-nav')) return;
      drag = { card: card, x: event.touches[0].clientX, y: event.touches[0].clientY, dx: 0, active: false };
    }, { passive: true });

    stack.addEventListener('touchmove', function (event) {
      if (!drag) return;
      var dx = event.touches[0].clientX - drag.x;
      var dy = event.touches[0].clientY - drag.y;
      if (!drag.active) {
        if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
        if (Math.abs(dy) > Math.abs(dx)) {
          drag = null;
          return;
        }
        drag.active = true;
        drag.card.style.transition = 'none';
      }
      event.preventDefault();
      drag.dx = dx;
      /* A sor végén rugalmas ellenállás. */
      var atEdge = (dx < 0 && current >= cards.length - 1) || (dx > 0 && current <= 0);
      var shown = atEdge ? dx * 0.25 : dx;
      drag.card.style.transform = 'translateX(' + shown + 'px) rotate(' + (shown / 22) + 'deg)';
    }, { passive: false });

    stack.addEventListener('touchend', finishTouch);
    stack.addEventListener('touchcancel', finishTouch);

    /* Húzás után a fotó-link ne nyíljon meg. */
    stack.addEventListener('click', function (event) {
      if (!suppressClick) return;
      event.preventDefault();
      event.stopPropagation();
    }, true);

    document.addEventListener('keydown', function (event) {
      if (!document.getElementById('kmae-swipe-stack') || typingTarget(event.target)) return;
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        stepPrev();
      } else if (event.key === 'ArrowRight') {
        event.preventDefault();
        stepNext();
      } else if (event.key === 'Enter') {
        event.preventDefault();
        openCurrent();
      } else if (event.key === ' ' || event.code === 'Space') {
        event.preventDefault();
        saveCurrent();
      }
    });

    cards.forEach(function (card, i) {
      card.style.zIndex = String(cards.length - i);
    });
    restore();
    showCurrent();
  });
})();
