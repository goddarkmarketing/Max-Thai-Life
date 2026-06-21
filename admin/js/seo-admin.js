(function () {
  'use strict';

  function bindCounter(field) {
    if (field.dataset.seoCountBound === '1') return;
    field.dataset.seoCountBound = '1';

    var counter = document.createElement('p');
    counter.className = 'admin-seo-count';
    field.parentElement.appendChild(counter);

    function update() {
      var len = (field.value || '').length;
      counter.textContent = len + ' ตัวอักษร' + (len > 160 ? ' (ยาวเกินไป)' : len >= 120 ? ' (เหมาะสม)' : '');
      counter.classList.toggle('is-warn', len > 160);
      counter.classList.toggle('is-ok', len >= 120 && len <= 160);
    }

    field.addEventListener('input', update);
    update();
  }

  document.querySelectorAll('.admin-seo-form textarea, .admin-seo-page-desc').forEach(bindCounter);
  var globalDesc = document.querySelector('[name="meta_description"]');
  if (globalDesc) bindCounter(globalDesc);
  var ogDesc = document.querySelector('[name="meta_og_description"]');
  if (ogDesc) bindCounter(ogDesc);
})();
