(function () {
  'use strict';

  if (typeof kmaeNav === 'undefined') {
    return;
  }

  var AUTH_PATHS = ['/belepes', '/regisztracio'];

  var MENU_LIST_SELECTORS = [
    '#header-menu-1 ul.menu',
    'header nav ul.menu',
    'header nav ul',
    '#masthead nav ul',
    '.header-menu-1 ul',
    '.mobile-menu ul',
    '.colibri-menu',
    '.main-navigation ul',
    '.wp-block-navigation__container'
  ];

  function isLoggedIn() {
    return document.body.classList.contains('logged-in');
  }

  function isInPageContent(el) {
    return el.closest('.kmae-auth, .kmae-form-wrap, .entry-content, main article, .kmae-single-listing');
  }

  function hrefIsAuth(href) {
    if (!href) return false;
    var lower = href.toLowerCase();
    return AUTH_PATHS.some(function (path) {
      return lower.indexOf(path) !== -1;
    });
  }

  function hideLoginRegisterInNav() {
    if (!isLoggedIn()) return;

    document.querySelectorAll('header a[href], .mobile-menu a[href], nav a[href]').forEach(function (a) {
      if (!hrefIsAuth(a.getAttribute('href'))) return;
      if (isInPageContent(a)) return;

      var item = a.closest('li') || a.closest('.wp-block-navigation-item');
      if (item) {
        item.classList.add('kmae-hide-when-logged-in');
        item.style.display = 'none';
      }
    });
  }

  function logoutExists() {
    return document.querySelector('.kmae-nav-logout, .kmae-header-logout-wrap, a.kmae-nav-logout-link[href*="action=logout"]');
  }

  function createLogoutLink() {
    var link = document.createElement('a');
    link.href = kmaeNav.logoutUrl;
    link.textContent = kmaeNav.logoutLabel;
    link.className = 'ct-menu-link kmae-nav-logout-link';
    return link;
  }

  function appendLogoutToList(list) {
    var item = document.createElement('li');
    item.className = 'menu-item page_item kmae-nav-logout';
    item.appendChild(createLogoutLink());
    list.appendChild(item);
  }

  function injectLogoutBlocksy() {
    var containers = document.querySelectorAll(
      '#header [data-column="end"] [data-items="primary"], header.ct-header [data-column="end"] [data-items="primary"]'
    );

    containers.forEach(function (container) {
      if (container.querySelector('.kmae-header-logout-wrap')) return;

      var wrap = document.createElement('div');
      wrap.className = 'kmae-header-logout-wrap';
      wrap.appendChild(createLogoutLink());
      container.appendChild(wrap);
    });
  }

  function ensureLogoutInNav() {
    if (!isLoggedIn() || logoutExists()) return;

    // Csak a jobb szélen (Blocksy header) — ne a menülista végére.
    injectLogoutBlocksy();
  }

  function removeMenuLogoutDuplicates() {
    document.querySelectorAll('nav ul.menu li.kmae-nav-logout, #header-menu-1 li.kmae-nav-logout').forEach(function (el) {
      el.remove();
    });
  }

  function init() {
    if (!isLoggedIn()) return;
    hideLoginRegisterInNav();
    removeMenuLogoutDuplicates();
    ensureLogoutInNav();
  }

  document.addEventListener('DOMContentLoaded', init);
  window.addEventListener('load', init);
  setTimeout(init, 500);
})();
