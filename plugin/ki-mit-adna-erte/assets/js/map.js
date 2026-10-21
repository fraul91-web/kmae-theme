(function () {
  'use strict';

  var wizard = document.querySelector('.kmae-wizard-map');
  if (!wizard) {
    return;
  }

  var nameEl = wizard.querySelector('.kmae-map-status-name');
  if (!nameEl) {
    return;
  }

  var defaultText = nameEl.getAttribute('data-default') || nameEl.textContent;
  var statusBox = wizard.querySelector('.kmae-map-status');

  var phoneMq = window.matchMedia ? window.matchMedia('(max-width: 1024px)') : null;

  // Phone: the status shows the county picked by tap (browse-wizard.js), not the hovered one.
  function setCounty(name) {
    if (phoneMq && phoneMq.matches) {
      name = wizard.getAttribute('data-picked-label') || '';
    }
    var active = !!name;
    nameEl.textContent = active ? name : defaultText;
    if (statusBox) {
      statusBox.classList.toggle('is-active', active);
    }
  }

  wizard.querySelectorAll('.kmae-county-link').forEach(function (link) {
    var label = link.getAttribute('data-label') || '';

    link.addEventListener('mouseenter', function () {
      setCounty(label);
    });

    link.addEventListener('focus', function () {
      setCounty(label);
    });

    link.addEventListener('touchstart', function () {
      setCounty(label);
    }, { passive: true });
  });

  var orszagBtn = wizard.querySelector('.kmae-wizard-orszag-btn');
  if (orszagBtn) {
    var orszagLabel = orszagBtn.getAttribute('data-label') || orszagBtn.textContent.trim();

    orszagBtn.addEventListener('mouseenter', function () {
      setCounty(orszagLabel);
    });

    orszagBtn.addEventListener('focus', function () {
      setCounty(orszagLabel);
    });

    orszagBtn.addEventListener('touchstart', function () {
      setCounty(orszagLabel);
    }, { passive: true });
  }

  wizard.addEventListener('mouseleave', function () {
    setCounty('');
  });

  wizard.addEventListener('focusout', function (e) {
    if (!wizard.contains(e.relatedTarget)) {
      setCounty('');
    }
  });
})();
