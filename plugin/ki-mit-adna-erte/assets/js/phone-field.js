/*
 * Telefonmező: csak számjegyek, magyar tördeléssel (30 111 11 11).
 * A +36 előtag a mező mellett áll fixen, ide csak a maradék kilenc
 * számjegy kerül. Bármelyik űrlapon működik, ahol a mezőn ott a
 * `data-kmae-phone` jelölés.
 */
(function () {
  function groupDigits(value) {
    var digits = String(value).replace(/\D/g, '');

    /* Ha a látogató a teljes számot másolja be (+36 vagy 06 előtaggal),
       levágjuk az elejét, hogy ne duplázódjon. */
    if (digits.indexOf('36') === 0 && digits.length > 9) {
      digits = digits.slice(2);
    } else if (digits.indexOf('06') === 0) {
      digits = digits.slice(2);
    }

    digits = digits.slice(0, 9);

    var out = digits.slice(0, 2);
    if (digits.length > 2) { out += ' ' + digits.slice(2, 5); }
    if (digits.length > 5) { out += ' ' + digits.slice(5, 7); }
    if (digits.length > 7) { out += ' ' + digits.slice(7, 9); }

    return out;
  }

  function attach(input) {
    if (input.dataset.kmaePhoneReady) {
      return;
    }
    input.dataset.kmaePhoneReady = '1';

    input.addEventListener('input', function () {
      var atEnd = input.selectionStart === input.value.length;
      var formatted = groupDigits(input.value);

      if (formatted !== input.value) {
        input.value = formatted;
        /* Ha a szám végén gépel, a kurzor is ott maradjon. */
        if (atEnd) {
          input.setSelectionRange(formatted.length, formatted.length);
        }
      }
    });

    /* Betűt és jelet meg se lehessen ütni. */
    input.addEventListener('keypress', function (event) {
      if (event.key && event.key.length === 1 && !/[0-9]/.test(event.key)) {
        event.preventDefault();
      }
    });

    input.addEventListener('blur', function () {
      input.value = groupDigits(input.value);
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var fields = document.querySelectorAll('[data-kmae-phone]');
    Array.prototype.forEach.call(fields, attach);
  });
})();
