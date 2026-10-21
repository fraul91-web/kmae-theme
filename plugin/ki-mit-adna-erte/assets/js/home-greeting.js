(function () {
  'use strict';

  // PHP handles ordinary visits; a back/forward cache restore skips PHP.
  window.addEventListener('pageshow', function (event) {
    if (!event.persisted) {
      return;
    }
    document.querySelectorAll('[data-kmae-home-welcome]').forEach(function (welcome) {
      welcome.remove();
    });
    // A köszönés elmúltával a kérdés nevén szólít.
    document.querySelectorAll('[data-kmae-home-ask]').forEach(function (ask) {
      var back = ask.getAttribute('data-kmae-ask-return');
      if (back) {
        ask.textContent = back;
      }
    });
    // A köszönés elmúltával a visszatérő manó jár.
    document.querySelectorAll('[data-kmae-home-mascot]').forEach(function (mascot) {
      var back = mascot.getAttribute('data-kmae-mascot-return');
      if (back && mascot.getAttribute('src') !== back) {
        mascot.setAttribute('src', back);
      }
    });
  });
})();
