(function (global) {
  'use strict';

  function digitsOnly(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function formatGroupedNumber(digits) {
    if (!digits) {
      return '';
    }
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
  }

  function caretAfterDigits(value, digitCount) {
    if (digitCount <= 0) {
      return 0;
    }

    var seen = 0;
    for (var i = 0; i < value.length; i++) {
      if (/\d/.test(value.charAt(i))) {
        seen++;
        if (seen === digitCount) {
          return i + 1;
        }
      }
    }
    return value.length;
  }

  function applyFormattedValue(control, digits, digitCaret) {
    var formatted = formatGroupedNumber(digits);
    control.dataset.kmaeRawNumber = digits;
    control.value = formatted;

    if (typeof control.setSelectionRange === 'function') {
      var caret = caretAfterDigits(formatted, digitCaret);
      control.setSelectionRange(caret, caret);
    }
    syncFormattedValidity(control);
  }

  function rawNumberValue(control) {
    if (!control) {
      return '';
    }
    if (control.dataset.kmaeFormatted === '1') {
      return control.dataset.kmaeRawNumber || digitsOnly(control.value);
    }
    return digitsOnly(control.value);
  }

  function defaultInvalidMessage() {
    var wizard = global.kmaeListingWizard;
    return wizard && wizard.i18n && wizard.i18n.invalid
      ? wizard.i18n.invalid
      : 'Ezt a mezőt még ki kell töltened.';
  }

  function syncFormattedValidity(control, invalidMessage) {
    if (!control || control.dataset.kmaeFormatted !== '1') {
      return;
    }

    var invalid = invalidMessage || defaultInvalidMessage();
    var raw = rawNumberValue(control);

    if (!raw) {
      control.setCustomValidity(control.required ? invalid : '');
      return;
    }

    var num = parseInt(raw, 10);
    if (control.dataset.kmaeMin && num < parseInt(control.dataset.kmaeMin, 10)) {
      control.setCustomValidity(invalid);
      return;
    }
    if (control.dataset.kmaeMax && num > parseInt(control.dataset.kmaeMax, 10)) {
      control.setCustomValidity(invalid);
      return;
    }

    control.setCustomValidity('');
  }

  function onFormattedNumberInput(event) {
    var control = event.target;
    var selectionStart = typeof control.selectionStart === 'number'
      ? control.selectionStart
      : control.value.length;
    var digitCaret = digitsOnly(control.value.slice(0, selectionStart)).length;
    var digits = digitsOnly(control.value);

    applyFormattedValue(control, digits, digitCaret);
  }

  function onFormattedNumberKeydown(event) {
    if (event.key !== 'Backspace' && event.key !== 'Delete') {
      return;
    }

    var control = event.target;
    var start = control.selectionStart;
    var end = control.selectionEnd;
    if (typeof start !== 'number' || start !== end) {
      return;
    }

    var separatorIndex = event.key === 'Backspace' ? start - 1 : start;
    if (separatorIndex < 0 || control.value.charAt(separatorIndex) !== ' ') {
      return;
    }

    event.preventDefault();
    var digits = digitsOnly(control.value);
    var digitCaret = digitsOnly(control.value.slice(0, start)).length;
    var removeIndex = event.key === 'Backspace' ? digitCaret - 1 : digitCaret;
    if (removeIndex < 0 || removeIndex >= digits.length) {
      return;
    }

    digits = digits.slice(0, removeIndex) + digits.slice(removeIndex + 1);
    applyFormattedValue(control, digits, removeIndex);
    control.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function onFormattedNumberBlur(event) {
    syncFormattedValidity(event.target);
  }

  function enhanceFormattedNumber(control) {
    if (!control || control.dataset.kmaeFormatted === '1') {
      return;
    }

    var raw = digitsOnly(control.value);
    if (control.getAttribute('min')) {
      control.dataset.kmaeMin = control.getAttribute('min');
    }
    if (control.getAttribute('max')) {
      control.dataset.kmaeMax = control.getAttribute('max');
    }

    control.type = 'text';
    control.inputMode = 'numeric';
    control.autocomplete = 'off';
    control.spellcheck = false;
    control.dataset.kmaeFormatted = '1';
    control.dataset.kmaeRawNumber = raw;
    control.value = formatGroupedNumber(raw);
    control.classList.add('is-wizard-number');

    control.addEventListener('input', onFormattedNumberInput);
    control.addEventListener('keydown', onFormattedNumberKeydown);
    control.addEventListener('blur', onFormattedNumberBlur);
    syncFormattedValidity(control);
  }

  function restoreFormattedNumber(control) {
    if (!control || control.dataset.kmaeFormatted !== '1') {
      return;
    }

    control.removeEventListener('input', onFormattedNumberInput);
    control.removeEventListener('keydown', onFormattedNumberKeydown);
    control.removeEventListener('blur', onFormattedNumberBlur);
    control.value = rawNumberValue(control);
    control.type = 'number';
    control.classList.remove('is-wizard-number');
    delete control.dataset.kmaeFormatted;
    delete control.dataset.kmaeRawNumber;
    delete control.dataset.kmaeMin;
    delete control.dataset.kmaeMax;
    control.setCustomValidity('');
  }

  function normalizeFormattedNumbersForSubmit(form) {
    if (!form) {
      return;
    }

    form.querySelectorAll('[data-kmae-formatted="1"]').forEach(function (control) {
      control.value = rawNumberValue(control);
    });
  }

  var PRICE_FIELD_NAMES = ['kmae_fixed_price', 'kmae_guide_price', 'kmae_minimum_cash'];

  function priceField(form, name) {
    return form ? form.querySelector('[name="' + name + '"]') : null;
  }

  function enhancePriceFields(form) {
    if (!form) {
      return;
    }

    PRICE_FIELD_NAMES.forEach(function (name) {
      enhanceFormattedNumber(priceField(form, name));
    });
  }

  function enhanceVisiblePriceField(form) {
    if (!form) {
      return;
    }

    var mode = '';
    form.querySelectorAll('input[name="kmae_price_mode"]').forEach(function (radio) {
      if (radio.checked) {
        mode = radio.value;
      }
    });

    PRICE_FIELD_NAMES.forEach(function (name) {
      var control = priceField(form, name);
      if (!control) {
        return;
      }

      var field = control.closest('[data-kmae-price-field]');
      var minimumField = control.closest('[data-kmae-minimum-field]');
      var active = (field && field.getAttribute('data-kmae-price-field') === mode)
        || (minimumField && (mode === 'guide' || mode === 'open'));
      if (!active) {
        control.required = false;
        control.disabled = true;
        control.value = '';
        if (control.dataset.kmaeFormatted === '1') {
          control.dataset.kmaeRawNumber = '';
        }
        control.setCustomValidity('');
        return;
      }
      control.disabled = false;
      enhanceFormattedNumber(control);
    });
  }

  function bindMinimumInfo(form) {
    var button = form.querySelector('[data-kmae-minimum-info]');
    var panel = document.getElementById('kmae-minimum-info');
    if (!button || !panel || button.dataset.kmaeMinimumInfoBound === '1') {
      return;
    }

    button.dataset.kmaeMinimumInfoBound = '1';
    button.addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var open = panel.hidden;
      panel.hidden = !open;
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (event) {
      if (panel.hidden || panel.contains(event.target) || button.contains(event.target)) {
        return;
      }
      panel.hidden = true;
      button.setAttribute('aria-expanded', 'false');
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !panel.hidden) {
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function bindPriceModeChange(form) {
    if (!form || form.dataset.kmaePriceNumbersBound === '1') {
      return;
    }

    form.dataset.kmaePriceNumbersBound = '1';
    form.querySelectorAll('input[name="kmae_price_mode"]').forEach(function (radio) {
      radio.addEventListener('change', function () {
        enhanceVisiblePriceField(form);
      });
    });
  }

  function init() {
    var form = document.getElementById('kmae-listing-form');
    if (!form) {
      return;
    }

    enhancePriceFields(form);
    bindPriceModeChange(form);
    bindMinimumInfo(form);

    form.addEventListener('submit', function () {
      normalizeFormattedNumbersForSubmit(form);
    });
  }

  global.kmaeListingFormNumbers = {
    digitsOnly: digitsOnly,
    formatGroupedNumber: formatGroupedNumber,
    rawNumberValue: rawNumberValue,
    syncFormattedValidity: syncFormattedValidity,
    enhance: enhanceFormattedNumber,
    restore: restoreFormattedNumber,
    normalizeForSubmit: normalizeFormattedNumbersForSubmit,
    enhancePriceFields: enhancePriceFields,
    enhanceVisiblePriceField: enhanceVisiblePriceField
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
