(function () {
  'use strict';

  var root = document.querySelector('[data-plan-card-edit]');
  if (!root) return;

  var form = root.closest('form');
  if (!form) return;

  var preview = root.querySelector('[data-plan-card-preview]');
  if (!preview) return;

  var el = {
    media: preview.querySelector('[data-preview-media]'),
    img: preview.querySelector('[data-preview-image]'),
    tag: preview.querySelector('[data-preview-tag]'),
    title: preview.querySelector('[data-preview-title]'),
    desc: preview.querySelector('[data-preview-desc]'),
    features: preview.querySelector('[data-preview-features]'),
    link: preview.querySelector('[data-preview-link]'),
  };

  function field(name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function featureValues() {
    return Array.prototype.map
      .call(form.querySelectorAll('input[name^="features["]'), function (input) {
        return (input.value || '').trim();
      })
      .filter(Boolean)
      .slice(0, 3);
  }

  function imageUrl(path) {
    path = (path || '').trim();
    if (!path) return '../images/plan-cards/card-savings.png';
    if (/^https?:\/\//i.test(path)) return path;
    return (
      '../' +
      path
        .replace(/^\/+/, '')
        .split('/')
        .map(function (seg) {
          if (!seg) return seg;
          try {
            seg = decodeURIComponent(seg);
          } catch (err) {}
          return encodeURIComponent(seg);
        })
        .join('/')
    );
  }

  var CATEGORY_TAGS = {
    savings: 'ออมทรัพย์',
    protect: 'คุ้มครองชีวิต',
    health: 'ประกันสุขภาพ',
    rider: 'สัญญาเพิ่มเติม',
    pension: 'บำนาญ/เกษียณ',
    invest: 'ลงทุน/Life Verse',
  };

  function slugFromForm() {
    var oldSlug = field('old_slug');
    if (oldSlug && oldSlug.value.trim()) return oldSlug.value.trim();
    var title = field('title');
    var slug = (title && title.value ? title.value : 'example')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
    return slug || 'plan';
  }

  function renderFeatures() {
    if (!el.features) return;
    var items = featureValues();
    if (!items.length) {
      el.features.innerHTML = '<li>จุดเด่น 1</li>';
      return;
    }
    el.features.innerHTML = items.map(function (text) {
      return '<li>' + escapeHtml(text) + '</li>';
    }).join('');
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function syncPreview() {
    var titleInput = field('title');
    var descInput = field('desc');
    var categoryInput = field('category');
    var imageInput = field('image');

    var title = titleInput && titleInput.value ? titleInput.value.trim() : 'ชื่อแผนประกัน';
    var desc = descInput && descInput.value ? descInput.value.trim() : 'คำอธิบายสั้นของแผนประกัน';
    var category = categoryInput && categoryInput.value ? categoryInput.value.trim() : 'savings';
    var tag = CATEGORY_TAGS[category] || category;

    preview.setAttribute('data-category', category);

    if (el.img) {
      el.img.src = imageUrl(imageInput ? imageInput.value : '');
      el.img.alt = title;
    }
    if (el.tag) el.tag.textContent = tag;
    if (el.title) el.title.textContent = title;
    if (el.desc) el.desc.textContent = desc;
    if (el.link) el.link.setAttribute('href', '../plans/' + slugFromForm() + '.html');

    renderFeatures();
  }

  form.addEventListener('input', syncPreview);
  form.addEventListener('change', syncPreview);

  form.addEventListener('click', function (e) {
    if (e.target.closest('[data-repeater-add], [data-repeater-remove], [data-image-clear]')) {
      window.setTimeout(syncPreview, 0);
    }
  });

  var imageInput = field('image');
  if (imageInput) {
    var observer = new MutationObserver(syncPreview);
    observer.observe(imageInput, { attributes: true, attributeFilter: ['value'] });
  }

  document.addEventListener('imageUploaded', syncPreview);

  syncPreview();
})();
