(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var radios = document.querySelectorAll('input[name="kmae_account_type"]');
    if (!radios.length) {
      return;
    }

    var companyBlocks = document.querySelectorAll('[data-kmae-company-fields]');
    var privateName = document.querySelector('[data-kmae-private-name]');
    var displayName = document.getElementById('kmae_reg_display_name');
    var companyFields = [];

    companyBlocks.forEach(function (block) {
      block.querySelectorAll('input, select, textarea').forEach(function (field) {
        companyFields.push({
          el: field,
          required: field.hasAttribute('required') || /\*/.test((field.closest('.kmae-field') || {}).innerText || ''),
        });
      });
    });

    function sync() {
      var checked = document.querySelector('input[name="kmae_account_type"]:checked');
      var isCompany = checked && checked.value === 'company';

      companyBlocks.forEach(function (el) {
        el.hidden = !isCompany;
      });

      if (privateName) {
        privateName.hidden = !!isCompany;
      }

      companyFields.forEach(function (item) {
        item.el.required = !!isCompany && item.required;
      });

      if (displayName) {
        displayName.required = !isCompany;
      }
    }

    radios.forEach(function (radio) {
      radio.addEventListener('change', sync);
    });

    sync();
  });
})();
