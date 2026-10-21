/**
 * Albira megerősítő ablak — a böngésző confirm() helyett.
 *
 *   kmaeConfirm({
 *     eyebrow: 'Kép törlése',
 *     title: 'Biztosan törlöd ezt a képet?',
 *     thumb: 'https://…/kep.webp',        // opcionális: mini kártya képe
 *     name: 'Fotó',  sub: '…',            // opcionális: mini kártya szövege
 *     notes: ['Mi történik…', ...],       // opcionális: felsorolás
 *     warning: 'Ez nem vonható vissza.',  // opcionális: kiemelt sor
 *     okLabel: 'Igen, törlöm', cancelLabel: 'Mégse, megtartom',
 *     tone: 'danger' | 'default'
 *   }).then(function (ok) { … });
 *
 * Stílus: assets/css/confirm.css (.kmae-cf*).
 */
(function () {
  'use strict';

  var MASCOT = 'https://albira.hu/wp-content/uploads/2026/10/szomoru3.webp';

  function el(tag, cls, text) {
    var node = document.createElement(tag);
    if (cls) {
      node.className = cls;
    }
    if (text) {
      node.textContent = text;
    }
    return node;
  }

  window.kmaeConfirm = function (opts) {
    opts = opts || {};

    return new Promise(function (resolve) {
      var lastFocus = document.activeElement;
      var danger = opts.tone !== 'default';

      var root = el('div', 'kmae-cf' + (danger ? ' is-danger' : ''));
      root.setAttribute('role', 'alertdialog');
      root.setAttribute('aria-modal', 'true');
      root.setAttribute('aria-labelledby', 'kmae-cf-title');

      var backdrop = el('div', 'kmae-cf__backdrop');
      var card = el('div', 'kmae-cf__card');

      var mascot = el('img', 'kmae-cf__mascot');
      mascot.src = MASCOT;
      mascot.alt = '';
      mascot.setAttribute('aria-hidden', 'true');
      mascot.width = 220;
      mascot.height = 250;
      card.appendChild(mascot);

      if (opts.eyebrow) {
        card.appendChild(el('p', 'kmae-cf__eyebrow', opts.eyebrow));
      }
      var title = el('h2', 'kmae-cf__title', opts.title || 'Biztosan?');
      title.id = 'kmae-cf-title';
      card.appendChild(title);

      if (opts.thumb || opts.name) {
        var item = el('div', 'kmae-cf__item');
        var thumb = el('span', 'kmae-cf__thumb');
        if (opts.thumb) {
          var img = el('img');
          img.src = opts.thumb;
          img.alt = '';
          thumb.appendChild(img);
        }
        item.appendChild(thumb);
        if (opts.name || opts.sub) {
          var text = el('span', 'kmae-cf__item-text');
          if (opts.name) {
            text.appendChild(el('strong', '', opts.name));
          }
          if (opts.sub) {
            text.appendChild(el('small', '', opts.sub));
          }
          item.appendChild(text);
        }
        card.appendChild(item);
      }

      if (opts.notes && opts.notes.length) {
        var list = el('ul', 'kmae-cf__list');
        opts.notes.forEach(function (note) {
          list.appendChild(el('li', '', note));
        });
        card.appendChild(list);
      }
      if (opts.warning) {
        card.appendChild(el('p', 'kmae-cf__warning', opts.warning));
      }

      var actions = el('div', 'kmae-cf__actions');
      var cancel = el('button', 'kmae-cf__btn kmae-cf__btn--cancel', opts.cancelLabel || 'Mégse');
      cancel.type = 'button';
      var ok = el('button', 'kmae-cf__btn kmae-cf__btn--ok', opts.okLabel || 'Igen');
      ok.type = 'button';
      actions.appendChild(cancel);
      actions.appendChild(ok);
      card.appendChild(actions);

      root.appendChild(backdrop);
      root.appendChild(card);
      document.body.appendChild(root);
      document.documentElement.classList.add('kmae-cf-open');

      window.requestAnimationFrame(function () {
        root.classList.add('is-open');
        cancel.focus();
      });

      function done(result) {
        document.removeEventListener('keydown', onKey, true);
        root.classList.remove('is-open');
        document.documentElement.classList.remove('kmae-cf-open');
        window.setTimeout(function () {
          if (root.parentNode) {
            root.parentNode.removeChild(root);
          }
        }, 200);
        if (lastFocus && typeof lastFocus.focus === 'function') {
          lastFocus.focus({ preventScroll: true });
        }
        resolve(result);
      }

      function onKey(event) {
        if (event.key === 'Escape') {
          event.preventDefault();
          event.stopPropagation();
          done(false);
        } else if (event.key === 'Tab') {
          var first = cancel;
          var last = ok;
          if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
          } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
          }
        }
      }

      document.addEventListener('keydown', onKey, true);
      backdrop.addEventListener('click', function () { done(false); });
      cancel.addEventListener('click', function () { done(false); });
      ok.addEventListener('click', function () { done(true); });
    });
  };
}());
