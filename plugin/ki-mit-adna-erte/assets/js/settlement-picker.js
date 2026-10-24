/**
 * Település-választó: irányítószám -> megye + település, és a város mező
 * listából választós (a megye településeire szűrt) automatikus kiegészítése.
 *
 * Jelölés (progresszív bővítés, a mezők neve/azonosítója nem változik):
 *   <input data-kmae-city-picker="#megye-select-azonosito">   a város mező
 *   <input data-kmae-zip data-county="#megye" data-city="#varos">  az irányítószám (nincs name -> nem megy el a szerverre)
 *   <div data-kmae-zip-result>                                 a találat / hiba helye az irányítószám mellett
 * Konfiguráció: window.kmaeSettlementPicker { dataUrl, i18n }.
 *
 * Az adat (assets/data/settlements.json) lustán, egyszer töltődik be: a három mező
 * valamelyikének első fókuszakor (vagy azonnal, ha a város mező már ki van töltve,
 * hogy a régi, hibás érték rögtön látsszon). Ha a betöltés nem sikerül, a mezők
 * szabad szövegként használhatók; a szerver ilyenkor is ellenőriz.
 *
 * A város mezőre `setCustomValidity` kerül, így a böngésző és a hirdetés-varázsló
 * (`checkValidity`) is megállítja a továbblépést, amíg a település nem a megyéé.
 */
(function () {
  'use strict';

  var cfg = window.kmaeSettlementPicker || {};
  var i18n = cfg.i18n || {};

  function t(key, fallback) {
    return i18n[key] || fallback;
  }

  /* ------------------------------------------------------------------ */
  /* Normalizálás (a PHP KMAE_Settlements::lower_key / ascii_key párja)   */
  /* ------------------------------------------------------------------ */

  function lowerKey(text) {
    var s = String(text || '').trim().toLowerCase();
    s = s.replace(/[õô]/g, 'ő').replace(/û/g, 'ű');
    s = s.replace(/\s*-\s*/g, '-').replace(/\s+/g, ' ');
    return s.replace(/^[\s.,;]+|[\s.,;]+$/g, '');
  }

  function asciiKey(text) {
    var s = lowerKey(text);
    if (s.normalize) {
      s = s.normalize('NFD').replace(/[̀-ͯ]/g, '');
    }
    return s;
  }

  /* ------------------------------------------------------------------ */
  /* Adat                                                                */
  /* ------------------------------------------------------------------ */

  var store = { state: 'idle', idx: null, waiting: [] };

  function buildIndex(data) {
    var idx = { counties: {}, national: [], zip: data.zip || {}, bp: String(data.budapestZipPrefix || '') };
    Object.keys(data.counties || {}).forEach(function (slug) {
      var entry = { items: [], lower: {}, ascii: {} };
      (data.counties[slug] || []).forEach(function (row) {
        var item = { n: row[0], c: slug, l: lowerKey(row[0]), a: asciiKey(row[0]) };
        entry.items.push(item);
        entry.lower[item.l] = item;
        (entry.ascii[item.a] = entry.ascii[item.a] || []).push(item);
        idx.national.push(item);
      });
      idx.counties[slug] = entry;
    });
    return idx;
  }

  function finish(ok) {
    var list = store.waiting;
    store.waiting = [];
    store.state = ok ? 'ready' : 'idle'; /* sikertelen betöltést a következő fókusz újrapróbálja */
    list.forEach(function (cb) {
      cb(ok);
    });
  }

  function load(cb) {
    cb = cb || function () {};
    if (store.state === 'ready') {
      cb(true);
      return;
    }
    store.waiting.push(cb);
    if (store.state === 'loading') {
      return;
    }
    store.state = 'loading';
    if (!cfg.dataUrl || !window.fetch) {
      finish(false);
      return;
    }
    window.fetch(cfg.dataUrl, { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) {
          throw new Error('http');
        }
        return res.json();
      })
      .then(function (data) {
        store.idx = buildIndex(data);
        finish(true);
      })
      .catch(function () {
        finish(false);
      });
  }

  function ready() {
    return store.state === 'ready' && !!store.idx;
  }

  /** A megye adott települése -> találat-elem (kis/nagybetű, ékezet, „6720 Szeged” tolerálva), vagy null. */
  function resolve(slug, text) {
    var entry = ready() ? store.idx.counties[slug] : null;
    if (!entry) {
      return null;
    }
    var raw = String(text || '').replace(/^\s*\d{4}\s+|\s+\d{4}\s*$/g, '');
    var lower = lowerKey(raw);
    if (!lower) {
      return null;
    }
    if (entry.lower[lower]) {
      return entry.lower[lower];
    }
    var ascii = asciiKey(raw);
    if (entry.ascii[ascii] && entry.ascii[ascii].length === 1) {
      return entry.ascii[ascii][0];
    }
    if (slug === 'budapest' && /^(budapest|bp)\b/.test(ascii) && entry.lower.budapest) {
      return entry.lower.budapest;
    }
    return null;
  }

  /** Megyétől független, egyértelmű találat (a település neve országosan egyedi). */
  function resolveNational(text) {
    if (!ready()) {
      return null;
    }
    var raw = String(text || '').replace(/^\s*\d{4}\s+|\s+\d{4}\s*$/g, '');
    var lower = lowerKey(raw);
    var ascii = asciiKey(raw);
    var found = null;
    var count = 0;
    if (!lower) {
      return null;
    }
    store.idx.national.some(function (item) {
      if (item.l === lower) {
        found = item;
        count = 1;
        return true;
      }
      if (item.a === ascii) {
        found = item;
        count += 1;
      }
      return false;
    });
    return count === 1 ? found : null;
  }

  /** Javaslatok a beírt szövegre: előbb az elején egyezők, aztán szó elején, aztán bárhol. */
  function suggest(slug, text) {
    if (!ready()) {
      return [];
    }
    var q = asciiKey(text);
    var pool;
    if (slug && store.idx.counties[slug]) {
      pool = store.idx.counties[slug].items;
    } else if (q.length >= 2) {
      pool = store.idx.national;
    } else {
      return [];
    }
    if (!q) {
      return pool.slice(0, 400);
    }
    var tiers = [[], [], []];
    pool.forEach(function (item) {
      var pos = item.a.indexOf(q);
      if (pos === 0) {
        tiers[0].push(item);
      } else if (pos > 0) {
        tiers[/[\s-]/.test(item.a.charAt(pos - 1)) ? 1 : 2].push(item);
      }
    });
    return tiers[0].concat(tiers[1], tiers[2]).slice(0, 60);
  }

  /* ------------------------------------------------------------------ */
  /* Közös segédek                                                       */
  /* ------------------------------------------------------------------ */

  function fire(el, types) {
    types.forEach(function (type) {
      el.dispatchEvent(new Event(type, { bubbles: true }));
    });
  }

  function byQuery(selector) {
    try {
      return selector ? document.querySelector(selector) : null;
    } catch (e) {
      return null;
    }
  }

  function countyLabel(select, slug) {
    if (select && select.options) {
      for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === slug) {
          return select.options[i].textContent.trim();
        }
      }
    }
    return slug;
  }

  function hasCounty(select, slug) {
    return !!select && !!select.querySelector('option[value="' + slug + '"]');
  }

  /* ------------------------------------------------------------------ */
  /* Város mező: választós automatikus kiegészítés + ellenőrzés          */
  /* ------------------------------------------------------------------ */

  var uid = 0;

  function CityPicker(city, county) {
    var self = this;
    this.city = city;
    this.county = county;
    this.busy = false;
    this.active = -1;
    this.items = [];
    this.focused = false;
    this.id = 'kmae-sp-' + (++uid);

    city.setAttribute('autocomplete', 'off');
    city.setAttribute('role', 'combobox');
    city.setAttribute('aria-autocomplete', 'list');
    city.setAttribute('aria-expanded', 'false');
    city.setAttribute('aria-controls', this.id + '-list');

    this.host = city.parentNode;
    this.host.classList.add('kmae-sp-host');

    this.list = document.createElement('ul');
    this.list.className = 'kmae-sp-list';
    this.list.id = this.id + '-list';
    this.list.setAttribute('role', 'listbox');
    this.list.hidden = true;
    this.host.appendChild(this.list);

    this.error = document.createElement('p');
    this.error.className = 'kmae-sp-error';
    this.error.id = this.id + '-error';
    this.error.setAttribute('role', 'alert');
    this.error.hidden = true;
    city.insertAdjacentElement('afterend', this.error);

    /* A varázsló a lépés érvényességét `checkValidity()`-vel kérdezi: előtte frissítünk
       (a program által beírt érték — pl. piszkozat-visszaállítás — se maradjon ellenőrizetlen). */
    var nativeCheck = city.checkValidity.bind(city);
    city.checkValidity = function () {
      self.check(true);
      return nativeCheck();
    };

    city.addEventListener('focus', function () {
      self.focused = true;
      load(function () {
        self.check(false);
        if (document.activeElement === city) {
          self.open();
        }
      });
    });
    city.addEventListener('blur', function () {
      self.focused = false;
      self.close();
      self.check(true);
    });
    city.addEventListener('input', function () {
      if (self.busy) {
        return;
      }
      self.check(false);
      self.open();
    });
    city.addEventListener('change', function () {
      if (self.busy) {
        return;
      }
      load(function () {
        self.snap();
        self.check(self.focused ? false : true);
      });
    });
    city.addEventListener('keydown', function (event) {
      self.onKey(event);
    });

    this.list.addEventListener('mousedown', function (event) {
      event.preventDefault(); /* a mező megtartja a fókuszt, így a blur nem zárja be előbb */
    });
    this.list.addEventListener('click', function (event) {
      var li = event.target.closest ? event.target.closest('.kmae-sp-option') : null;
      if (li && self.list.contains(li)) {
        self.pick(self.items[Number(li.getAttribute('data-i'))]);
      }
    });

    if (county) {
      if (county.parentNode) {
        county.parentNode.classList.add('kmae-sp-host'); /* a szomszéd hibaüzenete ne tolja szét ezt a mezőt */
      }
      county.addEventListener('focus', function () {
        load();
      });
      county.addEventListener('change', function () {
        self.onCountyChange();
      });
    }

    /* Régi, hibás érték: rögtön betöltjük az adatot, hogy az üzenet azonnal látsszon. */
    if (String(city.value || '').trim() !== '') {
      load(function () {
        self.check(true);
      });
    }
  }

  CityPicker.prototype.slug = function () {
    return this.county ? this.county.value : '';
  };

  /** Megyeváltás: ha a város nem az új megyéé, kiürítjük. */
  CityPicker.prototype.onCountyChange = function () {
    var self = this;
    load(function (ok) {
      if (ok && self.city.value.trim() !== '' && self.slug() && !resolve(self.slug(), self.city.value)) {
        self.setCity('');
      }
      self.check(false);
    });
  };

  CityPicker.prototype.setCity = function (value) {
    this.busy = true;
    this.city.value = value;
    fire(this.city, ['input', 'change']);
    this.busy = false;
    this.check(false);
  };

  /** Egyértelmű, de más írásmódú találat -> a hivatalos név (csak befejezett szerkesztésnél). */
  CityPicker.prototype.snap = function () {
    var value = this.city.value.trim();
    if (value === '' || !ready()) {
      return;
    }
    var slug = this.slug();
    var hit = slug ? resolve(slug, value) : resolveNational(value);
    if (!hit) {
      return;
    }
    if (!slug && this.county && hasCounty(this.county, hit.c)) {
      this.county.value = hit.c;
      fire(this.county, ['input', 'change']);
    }
    if (hit.n !== this.city.value) {
      this.busy = true;
      this.city.value = hit.n;
      fire(this.city, ['input']);
      this.busy = false;
    }
  };

  /** Érvényesség + hibaüzenet. `force`: a hibát akkor is mutatjuk, ha a mező épp fókuszban van. */
  CityPicker.prototype.check = function (force) {
    var value = this.city.value.trim();
    var slug = this.slug();
    var message = '';
    /* Rejtett (pl. nem céges regisztrációnál összecsukott) mezőre nem teszünk érvénytelenséget:
       a böngésző a nem fókuszálható hibás mezővel szó nélkül megállítaná a beküldést. */
    if (ready() && value !== '' && slug && !this.city.closest('[hidden]') && !resolve(slug, value)) {
      message = t('mismatch', 'Ez a település nem ebben a megyében van — válassz a listából.');
    }
    this.city.setCustomValidity(message);
    var show = message !== '' && (force || !this.focused || this.items.length === 0);
    this.error.textContent = show ? message : '';
    if (this.error.hidden === show) {
      this.error.hidden = !show;
    }
    this.city.classList.toggle('kmae-sp-invalid', show);
    if (show) {
      this.city.setAttribute('aria-invalid', 'true');
      this.city.setAttribute('aria-describedby', this.error.id);
    } else {
      this.city.removeAttribute('aria-invalid');
      this.city.removeAttribute('aria-describedby');
    }
    return message === '';
  };

  CityPicker.prototype.open = function () {
    if (!ready()) {
      return;
    }
    this.items = suggest(this.slug(), this.city.value);
    this.render();
    this.check(false);
  };

  CityPicker.prototype.render = function () {
    var self = this;
    var national = !this.slug();
    this.list.textContent = '';
    this.active = -1;
    if (!this.items.length) {
      this.close(true);
      return;
    }
    this.items.forEach(function (item, i) {
      var li = document.createElement('li');
      li.className = 'kmae-sp-option';
      li.id = self.id + '-o' + i;
      li.setAttribute('role', 'option');
      li.setAttribute('aria-selected', 'false');
      li.setAttribute('data-i', String(i));
      var name = document.createElement('span');
      name.className = 'kmae-sp-option__name';
      name.textContent = item.n;
      li.appendChild(name);
      if (national) {
        var where = document.createElement('span');
        where.className = 'kmae-sp-option__county';
        where.textContent = countyLabel(self.county, item.c);
        li.appendChild(where);
      }
      self.list.appendChild(li);
    });
    this.place();
    this.list.hidden = false;
    this.city.setAttribute('aria-expanded', 'true');
  };

  CityPicker.prototype.place = function () {
    this.list.style.top = (this.city.offsetTop + this.city.offsetHeight + 4) + 'px';
    this.list.style.left = this.city.offsetLeft + 'px';
    this.list.style.width = this.city.offsetWidth + 'px';
  };

  CityPicker.prototype.close = function (keepItems) {
    this.list.hidden = true;
    this.active = -1;
    if (!keepItems) {
      this.items = [];
    }
    this.city.setAttribute('aria-expanded', 'false');
    this.city.removeAttribute('aria-activedescendant');
  };

  CityPicker.prototype.isOpen = function () {
    return !this.list.hidden;
  };

  CityPicker.prototype.move = function (delta) {
    var count = this.items.length;
    if (!count) {
      return;
    }
    var next = this.active + delta;
    if (next < 0) {
      next = count - 1;
    } else if (next >= count) {
      next = 0;
    }
    this.setActive(next);
  };

  CityPicker.prototype.setActive = function (index) {
    var options = this.list.children;
    for (var i = 0; i < options.length; i++) {
      var on = i === index;
      options[i].classList.toggle('is-active', on);
      options[i].setAttribute('aria-selected', on ? 'true' : 'false');
    }
    this.active = index;
    if (index >= 0 && options[index]) {
      this.city.setAttribute('aria-activedescendant', options[index].id);
      var el = options[index];
      if (el.offsetTop < this.list.scrollTop) {
        this.list.scrollTop = el.offsetTop;
      } else if (el.offsetTop + el.offsetHeight > this.list.scrollTop + this.list.clientHeight) {
        this.list.scrollTop = el.offsetTop + el.offsetHeight - this.list.clientHeight;
      }
    }
  };

  CityPicker.prototype.onKey = function (event) {
    var key = event.key;
    if (key === 'ArrowDown' || key === 'ArrowUp') {
      if (!ready()) {
        load();
        return;
      }
      event.preventDefault();
      if (!this.isOpen()) {
        this.open();
        return;
      }
      this.move(key === 'ArrowDown' ? 1 : -1);
    } else if (key === 'Enter') {
      if (this.isOpen() && this.active >= 0) {
        event.preventDefault();
        event.stopPropagation();
        this.pick(this.items[this.active]);
      } else if (ready()) {
        this.snap();
        this.check(true);
        this.close();
      }
    } else if (key === 'Escape') {
      if (this.isOpen()) {
        event.preventDefault();
        event.stopPropagation();
        this.close();
      }
    } else if (key === 'Tab') {
      this.close();
    }
  };

  /** Választás a listából: a megye is beállítódik (ha még nincs / más), majd a város. */
  CityPicker.prototype.pick = function (item) {
    if (!item) {
      return;
    }
    if (this.county && this.county.value !== item.c && hasCounty(this.county, item.c)) {
      this.county.value = item.c;
      fire(this.county, ['input', 'change']);
    }
    this.setCity(item.n);
    this.close();
  };

  /* ------------------------------------------------------------------ */
  /* Irányítószám                                                        */
  /* ------------------------------------------------------------------ */

  function ZipHelper(zip, county, city, cityPicker) {
    var self = this;
    this.zip = zip;
    this.county = county;
    this.city = city;
    this.picker = cityPicker;
    this.result = zip.parentNode.querySelector('[data-kmae-zip-result]');
    this.busy = false;

    zip.setAttribute('autocomplete', 'off');
    zip.addEventListener('focus', function () {
      load();
    });
    zip.addEventListener('input', function () {
      var digits = zip.value.replace(/\D/g, '').slice(0, 4);
      if (digits !== zip.value) {
        zip.value = digits;
      }
      self.lookup(digits);
    });
    zip.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault(); /* nincs külön művelete: ne léptesse tovább a varázslót */
      }
    });

    /* Ha a megyét / várost kézzel átírják, az irányítószám-üzenet már nem igaz. */
    [county, city].forEach(function (el) {
      if (el) {
        el.addEventListener('change', function () {
          if (!self.busy) {
            self.say('', '');
          }
        });
      }
    });
  }

  ZipHelper.prototype.say = function (text, kind, choices) {
    var self = this;
    var box = this.result;
    if (!box) {
      return;
    }
    box.textContent = '';
    box.className = 'kmae-sp-zip-result' + (kind ? ' is-' + kind : '');
    if (!text) {
      return;
    }
    var line = document.createElement('span');
    line.className = 'kmae-sp-zip-result__text';
    line.textContent = text;
    box.appendChild(line);
    if (choices && choices.length) {
      var wrap = document.createElement('span');
      wrap.className = 'kmae-sp-zip-choices';
      choices.forEach(function (pair) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'kmae-sp-chip';
        btn.textContent = pair[1];
        btn.addEventListener('click', function () {
          self.fill(pair);
        });
        wrap.appendChild(btn);
      });
      box.appendChild(wrap);
    }
  };

  ZipHelper.prototype.lookup = function (digits) {
    var self = this;
    if (digits.length < 4) {
      this.say('', '');
      return;
    }
    load(function (ok) {
      if (self.zip.value.replace(/\D/g, '') !== digits) {
        return; /* közben tovább gépelt */
      }
      if (!ok) {
        self.say(t('zipUnavailable', 'Az irányítószám-keresés most nem érhető el — válaszd ki a megyét és a települést.'), 'error');
        return;
      }
      var idx = store.idx;
      var pairs = [];
      if (idx.bp && digits.indexOf(idx.bp) === 0) {
        pairs = [['budapest', 'Budapest']];
      } else if (idx.zip[digits]) {
        pairs = idx.zip[digits];
      }
      if (!pairs.length) {
        self.say(t('zipUnknown', 'Ilyen irányítószámot nem találunk — válaszd ki a megyét és a települést.'), 'error');
      } else if (pairs.length === 1) {
        self.fill(pairs[0]);
      } else {
        self.say(t('zipChoose', 'Ez az irányítószám több településhez tartozik — válaszd ki a tiédet:'), 'choose', pairs);
      }
    });
  };

  ZipHelper.prototype.fill = function (pair) {
    var slug = pair[0];
    var name = pair[1];
    if (!this.county || !hasCounty(this.county, slug)) {
      return;
    }
    this.busy = true;
    if (this.county.value !== slug) {
      this.county.value = slug;
      fire(this.county, ['input', 'change']);
    }
    if (this.picker) {
      this.picker.setCity(name);
    } else if (this.city) {
      this.city.value = name;
      fire(this.city, ['input', 'change']);
    }
    this.busy = false;
    var label = countyLabel(this.county, slug);
    var zip = this.zip.value.replace(/\D/g, '');
    this.say(zip + ' → ' + name + (label && label !== name ? ', ' + label : ''), 'ok');
  };

  /* ------------------------------------------------------------------ */
  /* Indítás                                                             */
  /* ------------------------------------------------------------------ */

  function init() {
    var pickers = new Map();
    Array.prototype.forEach.call(document.querySelectorAll('[data-kmae-city-picker]'), function (city) {
      if (city.dataset.kmaeSpReady === '1') {
        return;
      }
      city.dataset.kmaeSpReady = '1';
      pickers.set(city, new CityPicker(city, byQuery(city.getAttribute('data-kmae-city-picker'))));
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-kmae-zip]'), function (zip) {
      if (zip.dataset.kmaeSpReady === '1') {
        return;
      }
      zip.dataset.kmaeSpReady = '1';
      var city = byQuery(zip.getAttribute('data-city'));
      new ZipHelper(zip, byQuery(zip.getAttribute('data-county')), city, city ? pickers.get(city) : null);
    });

    /* Beküldés előtti biztonsági háló (programmal kitöltött, ellenőrizetlen érték). */
    pickers.forEach(function (picker) {
      var form = picker.city.form;
      if (!form || form.dataset.kmaeSpForm === '1') {
        return;
      }
      form.dataset.kmaeSpForm = '1';
      if (window.MutationObserver) {
        /* Egy blokk elrejtése / megjelenítése (céges mezők) után újraellenőrzünk. */
        new MutationObserver(function (records) {
          var relevant = records.some(function (r) {
            return !/\bkmae-sp-(error|list)\b/.test(r.target.className || '');
          });
          if (!relevant) {
            return;
          }
          pickers.forEach(function (p) {
            if (p.city.form === form) {
              p.check(false);
            }
          });
        }).observe(form, { attributes: true, subtree: true, attributeFilter: ['hidden'] });
      }
      form.addEventListener('submit', function (event) {
        var bad = null;
        pickers.forEach(function (p) {
          if (p.city.form === form && p.city.offsetParent !== null && !p.check(true) && !bad) {
            bad = p;
          }
        });
        if (bad) {
          event.preventDefault();
          bad.city.focus();
        }
      }, true);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
