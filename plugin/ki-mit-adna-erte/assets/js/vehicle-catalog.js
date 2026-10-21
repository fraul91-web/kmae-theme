(function () {
  'use strict';

  var data = window.kmaeVehicleCatalog || null;
  var i18n = (data && data.i18n) || {};
  var brands = (data && data.brands) || [];
  var modelsByBrand = (data && data.models) || {};
  var brandLogos = (data && data.logos) || {};
  var popularBrands = (data && data.popular) || [];
  var pickers = [];

  function norm(value) {
    return String(value || '').trim().toLowerCase();
  }

  function findIgnoreCase(list, value) {
    var needle = norm(value);
    if (!needle) {
      return '';
    }
    for (var i = 0; i < list.length; i++) {
      if (norm(list[i]) === needle) {
        return list[i];
      }
    }
    return '';
  }

  function knownBrand(value) {
    return findIgnoreCase(brands, value);
  }

  function modelsFor(brand) {
    var key = knownBrand(brand);
    if (!key || !modelsByBrand[key]) {
      return [];
    }
    return modelsByBrand[key].slice();
  }

  function matches(item, query) {
    return query !== '' && norm(item).indexOf(query) === 0;
  }

  function closeAll(except) {
    pickers.forEach(function (picker) {
      if (picker !== except) {
        picker.close();
      }
    });
  }

  function createPicker(root) {
    var kind = root.getAttribute('data-kmae-catalog');
    var input = root.querySelector('input[type="text"]');
    var panel = root.querySelector('.kmae-catalog-panel');
    var list = root.querySelector('.kmae-catalog-list');
    var empty = root.querySelector('.kmae-catalog-empty');
    var orLine = root.querySelector('.kmae-catalog-or');
    var customBtn = root.querySelector('.kmae-catalog-custom');
    var toListBtn = root.querySelector('.kmae-catalog-tolist');
    var headTitle = root.querySelector('[data-kmae-catalog-title]');
    var showAllBtn = root.querySelector('[data-kmae-catalog-showall]');

    if (!input || !panel || !list || !customBtn || !toListBtn) {
      return null;
    }

    var isBrand = kind === 'brand';
    /* Egyszerű szerkesztő: lenyíló lista, nem a varázsló állandó emblémarácsa. */
    var simplePage = !!document.querySelector('.kmae-create-page.is-simple');
    /* A népszerű márkák rácsa a vezetett feladáson eleve látszik. */
    var persistentGrid = isBrand && !!document.querySelector('.kmae-create-page.is-wizard-live') && !simplePage;
    if (simplePage) {
      customBtn.textContent = 'Nincs a listában';
    }
    var label = root.querySelector('label[for="' + input.id + '"]');

    var activeIndex = -1;
    var visible = [];
    var showAll = false;
    var forcedCustom = false;
    var customBtnHome = customBtn.parentNode;
    var customBtnNext = customBtn.nextSibling;
    var originalLabel = label ? label.textContent : '';
    var originalPlaceholder = input.getAttribute('placeholder') || '';

    /* Egyszerű szerkesztő: a kézzel beírt márkát/modellt meg kell erősíteni
       („Igen, biztosan ez a márka”), utána zöld pipa + „Mégis a listából”.
       A megerősítés a beírt szöveghez kötődik — átírás után újra kell. */
    var confirmedValue = '';
    var confirmBox = null;
    var confirmBtn = null;
    if (simplePage) {
      confirmBox = document.createElement('div');
      confirmBox.className = 'kmae-catalog-confirm';
      confirmBox.hidden = true;
      confirmBtn = document.createElement('button');
      confirmBtn.type = 'button';
      confirmBtn.className = 'kmae-catalog-confirm__btn';
      confirmBtn.textContent = isBrand ? 'Igen, biztosan ez a márka' : 'Igen, biztosan ez a modell';
      var confirmDone = document.createElement('span');
      confirmDone.className = 'kmae-catalog-confirm__done';
      confirmDone.textContent = isBrand ? '✓ Saját márka rögzítve' : '✓ Saját modell rögzítve';
      confirmBox.appendChild(confirmBtn);
      confirmBox.appendChild(confirmDone);
      toListBtn.parentNode.insertBefore(confirmBox, toListBtn);
      toListBtn.textContent = 'Mégis a listából';
      confirmBox.appendChild(toListBtn);

      confirmBtn.addEventListener('click', function () {
        if (!input.value.trim()) {
          input.focus();
          return;
        }
        confirmedValue = input.value.trim();
        syncConfirm();
      });
    }

    function isConfirmed() {
      return confirmedValue !== '' && confirmedValue === input.value.trim();
    }

    function syncConfirm() {
      if (!confirmBox) {
        return;
      }
      var custom = isCustom();
      var confirmed = custom && isConfirmed();
      confirmBox.hidden = !custom;
      confirmBox.classList.toggle('is-confirmed', confirmed);
      confirmBtn.hidden = confirmed;
      confirmBtn.disabled = !input.value.trim();
      toListBtn.hidden = !confirmed || forcedCustom;
      if (custom && input.value.trim() && !confirmed) {
        input.setCustomValidity(isBrand
          ? 'Erősítsd meg a márkát: „Igen, biztosan ez a márka”.'
          : 'Erősítsd meg a modellt: „Igen, biztosan ez a modell”.');
      } else if (custom) {
        input.setCustomValidity('');
      }
    }

    function isCustom() {
      return root.classList.contains('is-custom');
    }

    function isOpen() {
      return !panel.hidden;
    }

    function restoreCustomButton() {
      if (customBtn.parentNode !== customBtnHome) {
        customBtnHome.insertBefore(customBtn, customBtnNext);
      }
      customBtn.classList.remove('is-selected');
      customBtn.setAttribute('aria-pressed', 'false');
    }

    function syncShowAllButton() {
      if (!showAllBtn) {
        return;
      }
      if (isCustom() || (!isBrand && !options().length)) {
        showAllBtn.hidden = true;
        return;
      }
      /* A teljes lista nyitva van: a link felesleges. Gépelés közben marad, a szűrés mellett. */
      showAllBtn.hidden = showAll;
    }

    function setCustom(on, clearValue) {
      root.classList.toggle('is-custom', on);
      toListBtn.hidden = !on || forcedCustom;
      if (on) {
        close();
        input.setCustomValidity('');
        if (clearValue) {
          input.value = '';
        }
        /* Kézi módban nincs értelme újra felkínálni a "nincs a listán" gombot:
           épp azt választottuk. Marad a mező és a "Választás a listából" link. */
        restoreCustomButton();
        customBtn.hidden = true;
        if (label && !simplePage) {
          label.textContent = kind === 'brand' ? 'Írd be az autód márkáját' : 'Írd be az autód modelljét';
        }
        input.setAttribute('placeholder', kind === 'brand' ? 'pl. Suzuki' : 'pl. Swift');
      } else {
        restoreCustomButton();
        if (label) {
          label.textContent = originalLabel;
        }
        input.setAttribute('placeholder', originalPlaceholder);
        syncValidity();
      }
      syncShowAllButton();
      syncConfirm();
    }

    function options() {
      if (kind === 'brand') {
        return brands.slice();
      }
      var brandInput = document.getElementById('kmae_vehicle_brand');
      var brand = brandInput ? brandInput.value : '';
      if (!knownBrand(brand)) {
        return [];
      }
      return modelsFor(brand);
    }

    function syncForcedCustom() {
      if (kind !== 'model') {
        forcedCustom = false;
        return;
      }
      var brandInput = document.getElementById('kmae_vehicle_brand');
      var brand = brandInput ? brandInput.value.trim() : '';
      forcedCustom = brand !== '' && !knownBrand(brand);
      if (forcedCustom) {
        setCustom(true);
        toListBtn.hidden = true;
      }
    }

    function syncValidity() {
      if (isCustom() || !input.value.trim()) {
        input.setCustomValidity('');
        syncConfirm();
        return;
      }
      if (findIgnoreCase(options(), input.value)) {
        input.setCustomValidity('');
        return;
      }
      input.setCustomValidity(i18n.pickOrCustom || 'Válassz a listából, vagy add meg kézzel.');
    }

    /* A márka lista embléma-csempéket kap; a modell lista sima szöveg marad.
       A ténylegesen kiválasztott elem mindkettőben kiemelést kap. */
    function buildOption(item, index) {
      var li = document.createElement('li');
      li.className = isBrand ? 'kmae-catalog-option kmae-catalog-tile' : 'kmae-catalog-option';
      li.id = list.id + '-' + index;
      li.setAttribute('role', 'option');
      li.setAttribute('data-index', String(index));

      if (norm(item) === norm(input.value)) {
        li.classList.add('is-selected');
      }

      if (!isBrand) {
        li.textContent = item;
        return li;
      }

      var logo = brandLogos[item];
      if (logo) {
        var img = document.createElement('img');
        img.className = 'kmae-catalog-tile__logo';
        img.src = logo;
        img.alt = '';
        img.loading = 'lazy';
        img.decoding = 'async';
        li.appendChild(img);
      }

      var name = document.createElement('span');
      name.className = 'kmae-catalog-tile__name';
      name.textContent = item;
      li.appendChild(name);
      return li;
    }

    /* Van-e egyáltalán találat a beírt szövegre? Ezzel tudjuk eldönteni, hogy
       kézi megadás közben visszaengedjük-e a listát. */
    function hasCatalogHits(value) {
      var q = norm(value);
      if (!q) {
        return false;
      }
      return options().some(function (item) {
        return matches(item, q);
      });
    }

    function render(query) {
      var q = norm(query);
      var all = options();
      var hits = q ? all.filter(function (item) { return matches(item, q); }) : [];

      /* Egyszerű módban, ha a mezőben már egy kiválasztott (pontos) érték áll,
         a teljes listát mutatjuk a kiválasztott kiemelésével — így könnyű
         másikat választani, és nem egyetlen elem jelenik meg üres panelben. */
      var selectedExact = simplePage && q !== '' && !!findIgnoreCase(all, query);

      if (showAll || selectedExact) {
        /* „Összes megtekintése”: a beírt szűrés ellenére a teljes katalógus. */
        visible = all;
      } else if (isBrand && !q) {
        var pop = popularBrands.filter(function (item) {
          return findIgnoreCase(all, item);
        });
        visible = pop.length ? pop : all;
      } else if (!isBrand && !q) {
        visible = all;
      } else {
        visible = hits;
      }

      list.innerHTML = '';
      visible.forEach(function (item, index) {
        list.appendChild(buildOption(item, index));
      });

      /* Sok találatnál a csempék kisebbre váltanak, hogy elférjenek. */
      if (isBrand) {
        list.classList.toggle('is-dense', visible.length > 12);
        if (headTitle) {
          headTitle.textContent = showAll
            ? 'Összes márka'
            : (q ? 'Találatok' : (popularBrands.length ? 'Népszerű márkák' : 'Összes márka'));
        }
      }

      /* A kézi megadás CSAK akkor jelenik meg, ha a beírt szövegre nincs
         egyetlen találat sem. Ha kiválasztottunk valamit (vagy van találat),
         akkor értelmetlen azt írni, hogy "nincs a listán".
         Egyszerű módban a sor a lista alatt marad, amíg a beírt szöveg nem
         pontos találat — akkor is, ha vannak hasonló javaslatok. */
      var exact = q !== '' && !!findIgnoreCase(all, query);
      var noHits = q !== '' && visible.length === 0;
      var offerCustom = simplePage ? (q !== '' && !exact) : noHits;
      empty.hidden = simplePage ? true : !noHits;
      if (orLine) {
        orLine.hidden = simplePage ? true : !noHits;
      }
      customBtn.hidden = !offerCustom;
      list.hidden = visible.length === 0;
      /* Az embléma-rácsban semmi nincs előre kiemelve: a kiemelés csak
         egérrel vagy nyílbillentyűvel jön létre. */
      var selectedIndex = -1;
      if (selectedExact) {
        visible.forEach(function (item, i) {
          if (norm(item) === q) {
            selectedIndex = i;
          }
        });
      }
      setActive(selectedExact ? selectedIndex : ((isBrand && !simplePage) ? -1 : (visible.length ? 0 : -1)));
      if (selectedExact && selectedIndex > -1) {
        var selectedEl = list.querySelector('[data-index="' + selectedIndex + '"]');
        if (selectedEl && typeof selectedEl.scrollIntoView === 'function') {
          selectedEl.scrollIntoView({ block: 'nearest' });
        }
      }
      syncShowAllButton();
    }

    function setActive(index) {
      activeIndex = index;
      var items = list.querySelectorAll('.kmae-catalog-option');
      items.forEach(function (item, i) {
        var on = i === index;
        item.classList.toggle('is-active', on);
        item.setAttribute('aria-selected', on ? 'true' : 'false');
      });
      if (index >= 0 && items[index]) {
        input.setAttribute('aria-activedescendant', items[index].id);
        items[index].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    }

    function open() {
      /* Üres modellkatalógusnál (egyedi márka) nincs mit mutatni. */
      if (isCustom() || (!isBrand && !options().length)) {
        close();
        return;
      }
      closeAll(api);
      render(input.value);
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
      syncShowAllButton();
    }

    function close() {
      /* A népszerű márkák maradnak. Kívülre kattintás csak a kinyitott teljes listát csukja vissza. */
      if (persistentGrid && !isCustom()) {
        var wasAll = showAll;
        showAll = false;
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
        panel.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        if (wasAll) {
          render(input.value);
        } else {
          syncShowAllButton();
        }
        return;
      }
      panel.hidden = true;
      input.setAttribute('aria-expanded', 'false');
      input.removeAttribute('aria-activedescendant');
      activeIndex = -1;
      showAll = false;
      syncShowAllButton();
    }

    function pick(value) {
      setCustom(false);
      input.value = value;
      input.setCustomValidity('');
      close();
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
      /* Ebből tudja a wizard, hogy kattintással (nem gépeléssel) választottak,
         így magától továbbléphet. Gépelés + elkattintás nem vált ki ilyet. */
      input.dispatchEvent(new CustomEvent('kmae:catalog-pick', { bubbles: true }));
    }

    function acceptTyped() {
      var match = findIgnoreCase(options(), input.value);
      if (match) {
        input.value = match;
        input.setCustomValidity('');
        return true;
      }
      return false;
    }

    input.addEventListener('input', function () {
      if (isCustom()) {
        input.setCustomValidity('');
        /* A „kézzel adom meg" nem zárja ki véglegesen a keresést: ha tovább
           gépelve a szöveg mégis illik a katalógusra, visszaengedjük a listát.
           (Elgépelés után így nem ragad be a kézi mód.) Kivétel, ha a kézi mód
           kényszerített — ott nincs mihez keresni. */
        if (!forcedCustom && hasCatalogHits(input.value)) {
          setCustom(false);
          showAll = isBrand;
          open();
          syncValidity();
        }
        syncConfirm();
        return;
      }
      /* Gépelés szűkít: „X” minden X-szel kezdődőt kidob, márkánál és modellnél is.
         Üres modellmező nem nyitja ki magától a teljes listát — azt a link nyitja. */
      if (norm(input.value)) {
        showAll = false;
        open();
      } else if (isBrand || showAll) {
        open();
      } else {
        close();
      }
      syncValidity();
    });

    input.addEventListener('focus', function () {
      if (simplePage) {
        if (isCustom() || (!isBrand && !options().length)) {
          return;
        }
        open();
        return;
      }
      if (isCustom() || !isBrand || persistentGrid) {
        return;
      }
      open();
    });

    if (showAllBtn) {
      showAllBtn.addEventListener('mousedown', function (event) {
        event.preventDefault();
        if (isCustom() || (!isBrand && !options().length)) {
          return;
        }
        showAll = true;
        open();
        input.focus();
      });
    }

    input.addEventListener('blur', function () {
      if (!isCustom()) {
        acceptTyped();
        syncValidity();
      }
    });

    input.addEventListener('keydown', function (event) {
      if (isCustom()) {
        return;
      }

      if (event.key === 'ArrowDown') {
        if (panel.hidden || !visible.length) {
          return;
        }
        event.preventDefault();
        setActive(activeIndex < visible.length - 1 ? activeIndex + 1 : 0);
        return;
      }

      if (event.key === 'ArrowUp') {
        if (panel.hidden || !visible.length) {
          return;
        }
        event.preventDefault();
        setActive(activeIndex > 0 ? activeIndex - 1 : visible.length - 1);
        return;
      }

      if (event.key === 'Escape') {
        close();
        return;
      }

      if (event.key === 'Enter' && !panel.hidden && activeIndex >= 0 && visible[activeIndex]) {
        event.preventDefault();
        pick(visible[activeIndex]);
      }
    });

    list.addEventListener('mousedown', function (event) {
      var option = event.target.closest('.kmae-catalog-option');
      if (!option) {
        return;
      }
      event.preventDefault();
      pick(visible[parseInt(option.getAttribute('data-index'), 10)]);
    });

    list.addEventListener('mouseover', function (event) {
      var option = event.target.closest('.kmae-catalog-option');
      /* Csempék közti résen állva se maradjon kiemelve az előző elem. */
      setActive(option ? parseInt(option.getAttribute('data-index'), 10) : -1);
    });

    /* Az egér elhagyja a listát: a kiemelés is szűnjön meg, ne ragadjon be. */
    list.addEventListener('mouseleave', function () {
      setActive(-1);
    });

    customBtn.addEventListener('mousedown', function (event) {
      event.preventDefault();
      if (!isCustom()) {
        setCustom(true, false);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
      input.focus();
    });

    toListBtn.addEventListener('click', function () {
      if (forcedCustom) {
        return;
      }
      confirmedValue = '';
      setCustom(false);
      if (!findIgnoreCase(options(), input.value)) {
        input.value = '';
      }
      showAll = true;
      open();
      input.focus();
    });

    function onExternalChange() {
      syncForcedCustom();
      if (!isCustom() && input.value && findIgnoreCase(options(), input.value)) {
        input.value = findIgnoreCase(options(), input.value);
      }
      if (!input.value && !forcedCustom) {
        setCustom(false);
      }
      if (isOpen()) {
        render(input.value);
      }
      syncValidity();
      syncShowAllButton();
    }

    /* A wizard ezzel kéri a kézi megadást, ha a beírt érték nincs a listában —
       így a figyelmeztetés helyett rögtön ott a megoldás. */
    /* A wizard ezzel kéri a lista megnyitását: a beírt (nem található) szöveget
       kiürítjük, így újra a választható elemek látszanak. */
    input.addEventListener('kmae:catalog-show-list', function () {
      if (isCustom()) {
        setCustom(false);
      }
      input.value = '';
      input.dispatchEvent(new Event('input', { bubbles: true }));
      showAll = true;
      open();
      input.focus();
    });

    input.addEventListener('kmae:catalog-force-custom', function () {
      if (isCustom()) {
        return;
      }
      setCustom(true);
      input.focus();
    });

    input.addEventListener('change', onExternalChange);

    if (kind === 'brand') {
      input.addEventListener('change', function () {
        var modelInput = document.getElementById('kmae_vehicle_model');
        if (!modelInput) {
          return;
        }
        modelInput.value = '';
        modelInput.dispatchEvent(new Event('change', { bubbles: true }));
      });
    }

    syncForcedCustom();
    if (!isCustom() && input.value && !findIgnoreCase(options(), input.value) && input.value.trim()) {
      setCustom(true);
    }
    /* Betöltéskor a már elmentett saját érték megerősítettnek számít. */
    if (isCustom() && input.value.trim()) {
      confirmedValue = input.value.trim();
    }
    syncValidity();
    syncShowAllButton();
    syncConfirm();

    if (persistentGrid && !isCustom()) {
      render(input.value);
      panel.hidden = false;
      input.setAttribute('aria-expanded', 'true');
    }

    var api = {
      root: root,
      input: input,
      close: close,
      contains: function (node) {
        return root.contains(node);
      },
      refresh: onExternalChange
    };

    return api;
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (!data || !brands.length) {
      return;
    }

    document.querySelectorAll('.kmae-catalog-field').forEach(function (root) {
      var picker = createPicker(root);
      if (picker) {
        pickers.push(picker);
      }
    });

    document.addEventListener('mousedown', function (event) {
      var inside = pickers.some(function (picker) {
        return picker.contains(event.target);
      });
      if (!inside) {
        closeAll(null);
      }
    });
  });
})();
