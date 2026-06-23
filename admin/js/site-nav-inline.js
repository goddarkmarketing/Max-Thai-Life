(function () {
  'use strict';

  var tbody = document.getElementById('site-nav-tbody');
  if (!tbody) return;

  var csrf = tbody.getAttribute('data-csrf') || '';
  var dragState = null;

  function closeOpenEdits() {
    tbody.querySelectorAll('tr.nav-row--edit:not([hidden])').forEach(function (row) {
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
    var type = editRow.dataset.navType;
    var index = editRow.dataset.navIndex;
    if (type === 'child') {
      var child = editRow.dataset.navChild;
      return tbody.querySelector(
        'tr.nav-row--child[data-nav-index="' + index + '"][data-nav-child="' + child + '"]'
      );
    }
    return tbody.querySelector('tr.nav-row--main[data-nav-index="' + index + '"]');
  }

  function openEditRow(viewRow) {
    closeOpenEdits();
    var editRow = viewRow.nextElementSibling;
    if (!editRow || !editRow.matches('[data-nav-edit-row]')) return;
    viewRow.hidden = true;
    editRow.hidden = false;
    var input = editRow.querySelector('input[name="label"]');
    if (input) {
      input.focus();
      input.select();
    }
    editRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  tbody.addEventListener('click', function (event) {
    var editTrigger = event.target.closest('[data-nav-edit]');
    if (editTrigger) {
      var viewRow = editTrigger.closest('tr[data-nav-view]');
      if (viewRow) openEditRow(viewRow);
      return;
    }

    var cancelBtn = event.target.closest('[data-nav-cancel]');
    if (cancelBtn) {
      var editRow = cancelBtn.closest('tr[data-nav-edit-row]');
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
      return;
    }

    var addBtn = event.target.closest('[data-nav-add-child]');
    if (addBtn) {
      var parentIndex = addBtn.getAttribute('data-parent-index');
      var tpl = document.getElementById('nav-child-edit-template');
      if (!tpl) return;

      closeOpenEdits();

      var fragment = tpl.content.cloneNode(true);
      var editRow = fragment.querySelector('tr[data-nav-edit-row]');
      if (!editRow) return;

      editRow.dataset.navIndex = parentIndex;
      editRow.dataset.isNew = '1';
      editRow.querySelector('input[name="index"]').value = parentIndex;

      var insertBefore = nextMainRow(parentIndex);
      if (insertBefore) {
        tbody.insertBefore(fragment, insertBefore);
      } else {
        tbody.appendChild(fragment);
      }
      editRow.hidden = false;

      var input = editRow.querySelector('input[name="label"]');
      if (input) {
        input.focus();
        editRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }
  });

  function nextMainRow(parentIndex) {
    var main = tbody.querySelector('tr.nav-row--main[data-nav-index="' + parentIndex + '"]');
    if (!main) return null;
    var node = main.nextElementSibling;
    while (node && !node.classList.contains('nav-row--main')) {
      node = node.nextElementSibling;
    }
    return node;
  }

  function groupRows(mainRow) {
    var rows = [mainRow];
    var next = mainRow.nextElementSibling;
    while (next && !next.classList.contains('nav-row--main')) {
      rows.push(next);
      next = next.nextElementSibling;
    }
    return rows;
  }

  function moveGroup(mainRow, beforeMainRow) {
    var group = groupRows(mainRow);
    if (beforeMainRow === mainRow) return;
    group.forEach(function (row) {
      if (beforeMainRow) {
        tbody.insertBefore(row, beforeMainRow);
      } else {
        tbody.appendChild(row);
      }
    });
  }

  function childBlockRows(childRow) {
    var rows = [childRow];
    var editRow = childRow.nextElementSibling;
    if (
      editRow &&
      editRow.classList.contains('nav-row--child-edit') &&
      editRow.dataset.navChild === childRow.dataset.navChild
    ) {
      rows.push(editRow);
    }
    return rows;
  }

  function moveChildBlock(childRow, beforeChildRow) {
    if (beforeChildRow === childRow) return;
    var block = childBlockRows(childRow);
    block.forEach(function (row) {
      if (beforeChildRow) {
        tbody.insertBefore(row, beforeChildRow);
      } else {
        var insertBefore = nextMainRow(childRow.dataset.navIndex);
        if (insertBefore) tbody.insertBefore(row, insertBefore);
        else tbody.appendChild(row);
      }
    });
  }

  function mainOrderFromDom() {
    return Array.prototype.map.call(
      tbody.querySelectorAll('tr.nav-row--main'),
      function (row) {
        return row.getAttribute('data-nav-index');
      }
    );
  }

  function childOrderFromDom(parentIndex) {
    return Array.prototype.map.call(
      tbody.querySelectorAll('tr.nav-row--child[data-nav-index="' + parentIndex + '"]'),
      function (row) {
        return row.getAttribute('data-nav-child');
      }
    );
  }

  function renumberMainRows() {
    tbody.querySelectorAll('tr.nav-row--main').forEach(function (row, i) {
      var num = row.querySelector('[data-nav-order-num]');
      if (num) num.textContent = String(i + 1);
    });
  }

  function postReorder(kind, order, parentIndex) {
    var body = new URLSearchParams();
    body.append('csrf', csrf);
    body.append('kind', kind);
    order.forEach(function (idx) {
      body.append('order[]', idx);
    });
    if (kind === 'child') {
      body.append('parent_index', parentIndex);
    }

    return fetch('nav-reorder.php', {
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

  function bindDragHandles() {
    tbody.querySelectorAll('[data-nav-drag="main"]').forEach(function (handle) {
      var mainRow = handle.closest('tr.nav-row--main');
      if (!mainRow || handle.dataset.bound) return;
      handle.dataset.bound = '1';

      handle.addEventListener('mousedown', function () {
        mainRow.draggable = true;
      });
      mainRow.addEventListener('dragend', function () {
        mainRow.draggable = false;
        mainRow.classList.remove('is-dragging');
        tbody.querySelectorAll('.is-drag-over').forEach(function (el) {
          el.classList.remove('is-drag-over');
        });
      });

      mainRow.addEventListener('dragstart', function (e) {
        dragState = { type: 'main', row: mainRow };
        mainRow.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', mainRow.dataset.navIndex || '');
      });
    });

    tbody.querySelectorAll('[data-nav-drag="child"]').forEach(function (handle) {
      var childRow = handle.closest('tr.nav-row--child');
      if (!childRow || handle.dataset.bound) return;
      handle.dataset.bound = '1';

      handle.addEventListener('mousedown', function () {
        childRow.draggable = true;
      });
      childRow.addEventListener('dragend', function () {
        childRow.draggable = false;
        childRow.classList.remove('is-dragging');
        tbody.querySelectorAll('.is-drag-over').forEach(function (el) {
          el.classList.remove('is-drag-over');
        });
      });

      childRow.addEventListener('dragstart', function (e) {
        dragState = {
          type: 'child',
          row: childRow,
          parentIndex: childRow.dataset.navIndex,
        };
        childRow.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', childRow.dataset.navChild || '');
      });
    });
  }

  tbody.addEventListener('dragover', function (e) {
    if (!dragState) return;
    e.preventDefault();
    var target = e.target.closest('tr.nav-row--main, tr.nav-row--child');
    if (!target || target.classList.contains('is-dragging')) return;

    if (dragState.type === 'main' && target.classList.contains('nav-row--main')) {
      tbody.querySelectorAll('tr.nav-row--main.is-drag-over').forEach(function (el) {
        el.classList.remove('is-drag-over');
      });
      target.classList.add('is-drag-over');
    }

    if (dragState.type === 'child' && target.classList.contains('nav-row--child')) {
      if (target.dataset.navIndex !== dragState.parentIndex) return;
      tbody.querySelectorAll('tr.nav-row--child.is-drag-over').forEach(function (el) {
        el.classList.remove('is-drag-over');
      });
      target.classList.add('is-drag-over');
    }
  });

  tbody.addEventListener('drop', function (e) {
    if (!dragState) return;
    e.preventDefault();

    if (dragState.type === 'main') {
      var targetMain = e.target.closest('tr.nav-row--main');
      if (!targetMain || targetMain === dragState.row) return;
      moveGroup(dragState.row, targetMain);
      renumberMainRows();
      postReorder('main', mainOrderFromDom());
      return;
    }

    if (dragState.type === 'child') {
      var targetChild = e.target.closest('tr.nav-row--child');
      if (!targetChild || targetChild === dragState.row) return;
      if (targetChild.dataset.navIndex !== dragState.parentIndex) return;
      moveChildBlock(dragState.row, targetChild);
      postReorder('child', childOrderFromDom(dragState.parentIndex), dragState.parentIndex);
    }
  });

  bindDragHandles();

  document.querySelectorAll('[data-table-search]').forEach(function (input) {
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      var visibleParents = {};

      tbody.querySelectorAll('tr[data-search-text]').forEach(function (row) {
        var text = row.getAttribute('data-search-text') || '';
        var match = !q || text.indexOf(q) !== -1;
        if (row.classList.contains('nav-row--main') && match) {
          visibleParents[row.dataset.navIndex] = true;
        }
      });

      tbody.querySelectorAll('tr[data-search-text]').forEach(function (row) {
        var text = row.getAttribute('data-search-text') || '';
        var match = !q || text.indexOf(q) !== -1;
        if (row.classList.contains('nav-row--child') && visibleParents[row.dataset.navIndex]) {
          match = true;
        }
        row.style.display = match ? '' : 'none';
      });

      tbody.querySelectorAll('tr.nav-row--edit').forEach(function (row) {
        var parentIndex = row.dataset.navIndex;
        if (!q) {
          row.style.display = row.hidden && row.dataset.isNew !== '1' ? 'none' : '';
          return;
        }
        row.style.display = visibleParents[parentIndex] ? '' : 'none';
      });
    });
  });
})();
