/* Albira — Saját hirdetéseim: félbehagyott hirdetés szerkesztési választója.
   A Szerkesztés gomb fölött ívben kinyíló két kerek opció; máshová kattintva bezárul. */
(function () {
  var menu = document.getElementById('kmae-draft-radial');
  if (!menu) {
    return;
  }

  /* A kártya levágja a kilógó tartalmat, ezért a menü a <body> alatt, fix
     pozícióban lebeg a gomb fölött. */
  document.body.appendChild(menu);

  var simple = menu.querySelector('[data-kmae-draft-link="simple"]');
  var wizard = menu.querySelector('[data-kmae-draft-link="wizard"]');
  var current = null;

  function place(trigger) {
    var rect = trigger.getBoundingClientRect();
    menu.style.left = (rect.left + rect.width / 2) + 'px';
    menu.style.top = (rect.top + rect.height / 2) + 'px';
  }

  function open(trigger) {
    current = trigger;
    simple.href = trigger.getAttribute('data-kmae-draft-simple');
    wizard.href = trigger.getAttribute('data-kmae-draft-wizard');
    place(trigger);
    menu.hidden = false;
    trigger.classList.add('is-radial-open');
    trigger.setAttribute('aria-expanded', 'true');
    /* Egy képkockával később kapja meg az osztályt, hogy az animáció lefusson. */
    window.requestAnimationFrame(function () {
      window.requestAnimationFrame(function () {
        menu.classList.add('is-open');
      });
    });
  }

  function close() {
    if (!current) {
      return;
    }
    current.classList.remove('is-radial-open');
    current.setAttribute('aria-expanded', 'false');
    current = null;
    menu.classList.remove('is-open');
    menu.hidden = true;
  }

  document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-kmae-draft-edit]');
    if (trigger) {
      event.preventDefault();
      if (current === trigger) {
        close();
      } else {
        close();
        open(trigger);
      }
      return;
    }
    if (current && !menu.contains(event.target)) {
      close();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      close();
    }
  });

  window.addEventListener('scroll', function () {
    if (current) {
      place(current);
    }
  }, { passive: true });

  window.addEventListener('resize', function () {
    if (current) {
      place(current);
    }
  });
})();

/* Törlés megerősítése oldalon belüli ablakban (a böngésző confirm() helyett).
   A törlendő hirdetés adatai a form data-kmae-delete-* attribútumaiból jönnek. */
(function () {
  var dialog = document.getElementById('kmae-delete-confirm');
  if (!dialog) {
    return;
  }

  document.body.appendChild(dialog);

  function q(sel) {
    return dialog.querySelector(sel);
  }

  var okBtn = q('[data-kmae-delete-ok]');
  var okLabel = q('[data-kmae-delete-ok-label]');
  var thumb = q('[data-kmae-delete-thumb]');
  var thumbBox = q('[data-kmae-delete-thumb-box]');
  var pendingForm = null;
  var lastFocus = null;

  function show(sel, on) {
    var el = q(sel);
    if (el) {
      el.hidden = !on;
    }
  }

  function open(form) {
    var data = form.dataset;
    var isDraft = data.kmaeDeleteKind === 'draft';

    pendingForm = form;
    lastFocus = document.activeElement;

    q('[data-kmae-delete-eyebrow]').textContent = isDraft ? 'Félbehagyott hirdetés törlése' : 'Hirdetés törlése';
    q('[data-kmae-delete-heading]').textContent = isDraft
      ? 'Elveted a félbehagyott hirdetést?'
      : (data.kmaeDeleteReserved === '1' ? 'Erre az autóra elfogadott ajánlatod van. Biztosan törlöd?' : 'Biztosan törlöd a hirdetést?');
    q('[data-kmae-delete-name]').textContent = data.kmaeDeleteTitle || '';
    q('[data-kmae-delete-sub]').textContent = data.kmaeDeleteSub || '';
    q('[data-kmae-delete-sub]').hidden = !data.kmaeDeleteSub;
    q('[data-kmae-delete-status]').textContent = data.kmaeDeleteStatus || '';
    q('[data-kmae-delete-status]').hidden = !data.kmaeDeleteStatus;

    if (data.kmaeDeleteThumb) {
      thumb.src = data.kmaeDeleteThumb;
      thumb.hidden = false;
      thumbBox.classList.add('has-photo');
    } else {
      thumb.removeAttribute('src');
      thumb.hidden = true;
      thumbBox.classList.remove('has-photo');
    }

    show('[data-kmae-delete-for="listing"]', !isDraft);
    show('[data-kmae-delete-for="draft"]', isDraft);

    okLabel.textContent = isDraft ? 'Igen, elvetem' : 'Igen, törlöm';
    okBtn.disabled = false;
    okBtn.classList.remove('is-busy');

    dialog.hidden = false;
    document.body.classList.add('kmae-delete-confirm-open');
    window.requestAnimationFrame(function () {
      dialog.classList.add('is-open');
      q('.kmae-delete-confirm__btn--cancel').focus();
    });
  }

  function close() {
    dialog.classList.remove('is-open');
    document.body.classList.remove('kmae-delete-confirm-open');
    dialog.hidden = true;
    pendingForm = null;
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.matches || !form.matches('[data-kmae-delete-confirm]') || form.dataset.kmaeConfirmed === '1') {
      return;
    }
    event.preventDefault();
    open(form);
  });

  okBtn.addEventListener('click', function () {
    if (!pendingForm) {
      return;
    }
    var form = pendingForm;
    form.dataset.kmaeConfirmed = '1';
    okBtn.disabled = true;
    okBtn.classList.add('is-busy');
    okLabel.textContent = 'Törlés…';
    form.submit();
  });

  dialog.addEventListener('click', function (event) {
    if (event.target.closest('[data-kmae-delete-cancel]')) {
      close();
    }
  });

  document.addEventListener('keydown', function (event) {
    if (dialog.hidden) {
      return;
    }
    if (event.key === 'Escape') {
      close();
      return;
    }
    /* A fókusz az ablakban marad. */
    if (event.key === 'Tab') {
      var items = dialog.querySelectorAll('button:not([disabled])');
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
  });
})();
