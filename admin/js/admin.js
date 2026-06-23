(function () {
  function initImageFields(root) {
    (root || document).querySelectorAll('[data-image-field]').forEach(function (field) {
      if (field.dataset.imageBound) return;
      field.dataset.imageBound = '1';

      var input = field.querySelector('[data-image-input]');
      var preview = field.querySelector('[data-image-preview]');
      var fileInput = field.querySelector('[data-image-upload]');
      var trigger = field.querySelector('[data-image-trigger]');
      var clearBtn = field.querySelector('[data-image-clear]');
      var spec = fileInput ? fileInput.getAttribute('data-spec') : '';

      if (trigger && fileInput) {
        trigger.addEventListener('click', function () {
          fileInput.click();
        });
      }

      if (fileInput) {
        fileInput.addEventListener('change', function () {
          var file = fileInput.files && fileInput.files[0];
          if (!file) return;

          var formData = new FormData();
          formData.append('file', file);
          formData.append('spec', spec);
          formData.append('csrf', document.querySelector('input[name="csrf"]')?.value || '');

          trigger.disabled = true;
          trigger.textContent = 'กำลังอัปโหลด...';

          fetch('api/upload.php', { method: 'POST', body: formData })
            .then(function (res) {
              return res.json();
            })
            .then(function (data) {
              if (!data.ok) throw new Error(data.error || 'อัปโหลดไม่สำเร็จ');
              input.value = data.path;
              preview.innerHTML = '<img src="../' + data.path + '" alt="">';
              input.dispatchEvent(new Event('input', { bubbles: true }));
              document.dispatchEvent(new CustomEvent('imageUploaded'));
            })
            .catch(function (err) {
              alert(err.message || 'อัปโหลดไม่สำเร็จ');
            })
            .finally(function () {
              trigger.disabled = false;
              trigger.textContent = 'เลือกรูป';
              fileInput.value = '';
            });
        });
      }

      if (clearBtn) {
        clearBtn.addEventListener('click', function () {
          input.value = '';
          preview.innerHTML = '<span class="admin-image-empty">ยังไม่มีรูป</span>';
          input.dispatchEvent(new Event('input', { bubbles: true }));
          document.dispatchEvent(new CustomEvent('imageUploaded'));
        });
      }
    });
  }

  function reindexFieldName(name, index) {
    if (name.indexOf('__INDEX__') !== -1) {
      return name.replace(/__INDEX__/g, String(index));
    }
    return name.replace(/\[\d+\]/, '[' + index + ']');
  }

  document.querySelectorAll('[data-repeater]').forEach(function (repeater) {
    if (repeater.dataset.repeaterBound) return;
    repeater.dataset.repeaterBound = '1';

    var list = repeater.querySelector('[data-repeater-list]');
    var template = repeater.querySelector('[data-repeater-template]');
    var addBtn = repeater.querySelector('[data-repeater-add]');
    var min = parseInt(repeater.getAttribute('data-repeater-min') || '0', 10);

    function reindex() {
      list.querySelectorAll('[data-repeater-item]').forEach(function (item, index) {
        var label = item.querySelector('[data-repeater-label]');
        if (label) {
          var prefix = label.getAttribute('data-label-prefix') || '';
          label.textContent = prefix ? prefix + ' ' + (index + 1) : 'รายการ ' + (index + 1);
        }
        item.querySelectorAll('[name]').forEach(function (el) {
          el.name = reindexFieldName(el.name, index);
          if (el.id) {
            el.id = reindexFieldName(el.id, index);
          }
        });
        item.querySelectorAll('label[for]').forEach(function (lab) {
          if (lab.htmlFor) {
            lab.htmlFor = reindexFieldName(lab.htmlFor, index);
          }
        });
      });
    }

    if (addBtn && template) {
      addBtn.addEventListener('click', function () {
        var html = template.innerHTML.replace(/__INDEX__/g, String(list.children.length));
        var wrap = document.createElement('div');
        wrap.innerHTML = html.trim();
        var node = wrap.firstElementChild;
        list.appendChild(node);
        reindex();
        initImageFields(node);
      });
    }

    repeater.addEventListener('click', function (e) {
      var btn = e.target.closest('[data-repeater-remove]');
      if (!btn) return;
      var item = btn.closest('[data-repeater-item]');
      if (item && list.children.length > min) {
        item.remove();
        reindex();
      }
    });
  });

  initImageFields(document);

  document.querySelectorAll('[data-table-search]').forEach(function (input) {
    var table = input.closest('.admin-card-body, .admin-card, main')?.querySelector('[data-searchable]');
    if (!table) return;
    input.addEventListener('input', function () {
      var q = input.value.trim().toLowerCase();
      table.querySelectorAll('tbody tr[data-search-text]').forEach(function (row) {
        var text = row.getAttribute('data-search-text') || '';
        row.style.display = !q || text.indexOf(q) !== -1 ? '' : 'none';
      });
    });
  });
})();
