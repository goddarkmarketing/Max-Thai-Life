(function () {
  'use strict';

  function templateId(section, col) {
    if (section === 'link') {
      return 'footer-edit-template-link-' + col;
    }
    return 'footer-edit-template-' + section;
  }

  function initTbody(tbody) {
    if (tbody.dataset.footerBound) return;
    tbody.dataset.footerBound = '1';

    var csrf = tbody.getAttribute('data-csrf') || '';
    var section = tbody.getAttribute('data-footer-section') || '';
    var col = tbody.getAttribute('data-footer-col') || '0';
    var reorderUrl = tbody.getAttribute('data-reorder-url') || 'footer-reorder.php';
    var dragState = null;

    function closeOpenEdits() {
      tbody.querySelectorAll('tr.footer-row--edit:not([hidden])').forEach(function (row) {
        if (row.dataset.isNew === '1') {
          row.remove();
          return;
        }
        row.hidden = true;
        var form = row.querySelector('form');
        if (form) form.reset();
        var viewRow = findViewRow(row);
        if (viewRow) viewRow.hidden = false;
      });
    }

    function findViewRow(editRow) {
      var index = editRow.dataset.footerIndex;
      return tbody.querySelector(
        'tr[data-footer-view][data-footer-index="' + index + '"]'
      );
    }

    function openEditRow(viewRow) {
      closeOpenEdits();
      var editRow = viewRow.nextElementSibling;
      if (!editRow || !editRow.matches('[data-footer-edit-row]')) return;
      viewRow.hidden = true;
      editRow.hidden = false;
      var input = editRow.querySelector('input[name="label"]');
      if (window.AdminContactPickers) {
        window.AdminContactPickers.init(editRow);
      }
      if (input) {
        input.focus();
        input.select();
      }
      editRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function rowPair(viewRow) {
      var rows = [viewRow];
      var editRow = viewRow.nextElementSibling;
      if (editRow && editRow.matches('[data-footer-edit-row]')) {
        rows.push(editRow);
      }
      return rows;
    }

    function moveRowPair(viewRow, beforeViewRow) {
      if (beforeViewRow === viewRow) return;
      rowPair(viewRow).forEach(function (row) {
        if (beforeViewRow) {
          tbody.insertBefore(row, beforeViewRow);
        } else {
          tbody.appendChild(row);
        }
      });
    }

    function orderFromDom() {
      return Array.prototype.map.call(
        tbody.querySelectorAll('tr.footer-row--view:not(.footer-row--more)'),
        function (row) {
          return row.getAttribute('data-footer-index');
        }
      );
    }

    function renumberRows() {
      tbody.querySelectorAll('tr.footer-row--view:not(.footer-row--more)').forEach(function (row, i) {
        var num = row.querySelector('[data-footer-order-num]');
        if (num) num.textContent = String(i + 1);
      });
    }

    function postReorder(order) {
      var body = new URLSearchParams();
      body.append('csrf', csrf);
      body.append('section', section);
      body.append('col', col);
      order.forEach(function (idx) {
        body.append('order[]', idx);
      });

      return fetch(reorderUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: body,
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (!data.ok) throw new Error(data.message || 'จัดลำดับไม่สำเร็จ');
          location.reload();
        })
        .catch(function (err) {
          alert(err.message || 'จัดลำดับไม่สำเร็จ');
          location.reload();
        });
    }

    tbody.addEventListener('click', function (event) {
      var editTrigger = event.target.closest('[data-footer-edit]');
      if (editTrigger) {
        var viewRow = editTrigger.closest('tr[data-footer-view]');
        if (viewRow) openEditRow(viewRow);
        return;
      }

      var cancelBtn = event.target.closest('[data-footer-cancel]');
      if (!cancelBtn) return;

      var editRow = cancelBtn.closest('tr[data-footer-edit-row]');
      if (!editRow) return;

      if (editRow.dataset.isNew === '1') {
        editRow.remove();
        return;
      }

      editRow.hidden = true;
      var form = editRow.querySelector('form');
      if (form) form.reset();
      var viewRow = findViewRow(editRow);
      if (viewRow) viewRow.hidden = false;
    });

    tbody.querySelectorAll('[data-footer-drag]').forEach(function (handle) {
      var viewRow = handle.closest('tr.footer-row--view');
      if (!viewRow || viewRow.classList.contains('footer-row--more') || handle.dataset.bound) return;
      handle.dataset.bound = '1';

      handle.addEventListener('mousedown', function () {
        viewRow.draggable = true;
      });
      viewRow.addEventListener('dragend', function () {
        viewRow.draggable = false;
        viewRow.classList.remove('is-dragging');
        tbody.querySelectorAll('.is-drag-over').forEach(function (el) {
          el.classList.remove('is-drag-over');
        });
      });
      viewRow.addEventListener('dragstart', function (e) {
        dragState = { row: viewRow };
        viewRow.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', viewRow.dataset.footerIndex || '');
      });
    });

    tbody.addEventListener('dragover', function (e) {
      if (!dragState) return;
      e.preventDefault();
      var target = e.target.closest('tr.footer-row--view:not(.footer-row--more)');
      if (!target || target.classList.contains('is-dragging')) return;
      tbody.querySelectorAll('tr.footer-row--view.is-drag-over').forEach(function (el) {
        el.classList.remove('is-drag-over');
      });
      target.classList.add('is-drag-over');
    });

    tbody.addEventListener('drop', function (e) {
      if (!dragState) return;
      e.preventDefault();
      var target = e.target.closest('tr.footer-row--view:not(.footer-row--more)');
      if (!target || target === dragState.row) return;
      moveRowPair(dragState.row, target);
      renumberRows();
      postReorder(orderFromDom());
    });
  }

  document.querySelectorAll('[data-footer-tbody]').forEach(initTbody);

  document.querySelectorAll('[data-footer-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var section = btn.dataset.footerSection;
      var col = btn.dataset.footerCol || '0';
      var tbody = document.querySelector(
        'tbody[data-footer-tbody][data-footer-section="' + section + '"][data-footer-col="' + col + '"]'
      );
      var tpl = document.getElementById(templateId(section, col));
      if (!tbody || !tpl) return;

      tbody.querySelectorAll('tr.footer-row--edit:not([hidden])').forEach(function (row) {
        if (row.dataset.isNew === '1') row.remove();
        else {
          row.hidden = true;
          var viewRow = tbody.querySelector('tr[data-footer-view][data-footer-index="' + row.dataset.footerIndex + '"]');
          if (viewRow) viewRow.hidden = false;
        }
      });

      var fragment = tpl.content.cloneNode(true);
      var editRow = fragment.querySelector('tr[data-footer-edit-row]');
      if (!editRow) return;

      var moreRow = tbody.querySelector('tr.footer-row--more');
      if (moreRow) {
        tbody.insertBefore(fragment, moreRow);
      } else {
        tbody.appendChild(fragment);
      }

      editRow.hidden = false;
      editRow.dataset.isNew = '1';

      if (window.AdminContactPickers) {
        window.AdminContactPickers.init(editRow);
      }

      var labelInput = editRow.querySelector('input[name="label"]');
      if (labelInput) {
        labelInput.focus();
        editRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  });
})();
