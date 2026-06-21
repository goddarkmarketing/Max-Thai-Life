(function () {
  'use strict';

  function templateId(section, col) {
    if (section === 'link') {
      return 'footer-edit-template-link-' + col;
    }
    return 'footer-edit-template-' + section;
  }

  function closeOpenEdits(tbody) {
    tbody.querySelectorAll('tr.footer-row--edit:not([hidden])').forEach(function (row) {
      if (row.dataset.isNew === '1') {
        row.remove();
        return;
      }
      row.hidden = true;
      var form = row.querySelector('form');
      if (form) {
        form.reset();
      }
      var viewRow = tbody.querySelector('tr[data-footer-view][data-index="' + row.dataset.index + '"]');
      if (viewRow) {
        viewRow.hidden = false;
      }
    });
  }

  document.querySelectorAll('[data-footer-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var section = btn.dataset.footerSection;
      var col = btn.dataset.footerCol || '0';
      var tbody = document.querySelector(
        'tbody[data-footer-tbody][data-footer-section="' + section + '"][data-footer-col="' + col + '"]'
      );
      var tpl = document.getElementById(templateId(section, col));
      if (!tbody || !tpl) {
        return;
      }

      closeOpenEdits(tbody);

      var fragment = tpl.content.cloneNode(true);
      tbody.appendChild(fragment);

      var editRow = tbody.querySelector('tr.footer-row--edit:last-child');
      if (!editRow) {
        return;
      }
      editRow.hidden = false;
      editRow.dataset.isNew = '1';

      var labelInput = editRow.querySelector('input[name="label"]');
      if (labelInput) {
        labelInput.focus();
      }

      editRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });
  });

  document.addEventListener('click', function (event) {
    var editBtn = event.target.closest('[data-footer-edit]');
    if (editBtn) {
      var viewRow = editBtn.closest('tr[data-footer-view]');
      if (!viewRow) {
        return;
      }
      var tbody = viewRow.closest('tbody[data-footer-tbody]');
      if (tbody) {
        closeOpenEdits(tbody);
      }
      var editRow = viewRow.nextElementSibling;
      if (!editRow || !editRow.matches('[data-footer-edit-row]')) {
        return;
      }
      viewRow.hidden = true;
      editRow.hidden = false;
      var input = editRow.querySelector('input[name="label"]');
      if (input) {
        input.focus();
        input.select();
      }
      return;
    }

    var cancelBtn = event.target.closest('[data-footer-cancel]');
    if (!cancelBtn) {
      return;
    }

    var editRow = cancelBtn.closest('tr[data-footer-edit-row]');
    if (!editRow) {
      return;
    }

    if (editRow.dataset.isNew === '1') {
      editRow.remove();
      return;
    }

    var form = editRow.querySelector('form');
    if (form) {
      form.reset();
    }
    editRow.hidden = true;

    var viewRow = editRow.previousElementSibling;
    if (viewRow && viewRow.matches('[data-footer-view]')) {
      viewRow.hidden = false;
    }
  });
})();
