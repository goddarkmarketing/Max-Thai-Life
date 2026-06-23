(function () {
  'use strict';

  var root = document.querySelector('[data-content-card-edit]');
  if (!root) return;

  var form = root.closest('form');
  if (!form) return;

  var contentType = root.getAttribute('data-content-type') || 'articles';
  var preview = root.querySelector('[data-content-card-preview]');
  if (!preview) return;

  var el = {
    media: preview.querySelector('[data-preview-media]'),
    img: preview.querySelector('[data-preview-image]'),
    category: preview.querySelector('[data-preview-category]'),
    title: preview.querySelector('[data-preview-title]'),
    desc: preview.querySelector('[data-preview-desc]'),
    stats: preview.querySelector('[data-preview-stats]'),
    link: preview.querySelector('[data-preview-link]'),
  };

  function field(name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function escapeHtml(text) {
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function imageUrl(path) {
    path = (path || '').trim();
    if (!path) {
      return '../images/cover%20cart/istockphoto-1350164916-612x612.jpg';
    }
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

  function slugFromForm() {
    var slugInput = field('slug');
    var slug = slugInput && slugInput.value ? slugInput.value.trim() : '';
    if (slug) return slug;
    var title = field('title');
    return (title && title.value ? title.value : 'example')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');
  }

  function dirForType() {
    if (contentType === 'news') return 'news';
    if (contentType === 'careers') return 'careers';
    return 'articles';
  }

  function renderStats() {
    if (!el.stats) return;
    var views = parseInt((field('views') && field('views').value) || '0', 10) || 0;
    var shares = parseInt((field('shares') && field('shares').value) || '0', 10) || 0;
    if (!views) {
      el.stats.textContent = '';
      el.stats.hidden = true;
      return;
    }
    var text = views.toLocaleString('th-TH') + ' views';
    if (shares) text += ' · ' + shares + ' shares';
    el.stats.textContent = text;
    el.stats.hidden = false;
  }

  function sync() {
    var title = field('title');
    var desc = field('description');
    var category = field('category');
    var image = field('image');

    if (el.title) el.title.innerHTML = escapeHtml(title && title.value ? title.value : 'หัวข้อ');
    if (el.desc) el.desc.innerHTML = escapeHtml(desc && desc.value ? desc.value : 'คำอธิบายสั้น');
    if (el.category) {
      var cat = category && category.value ? category.value : '';
      el.category.innerHTML = escapeHtml(contentType === 'claims' && !cat ? 'รีวิวเคลม' : cat);
    }
    if (el.img) {
      el.img.src = imageUrl(image && image.value ? image.value : '');
      el.img.alt = title && title.value ? title.value : '';
    }
  }

  function bindMediaHref() {
    if (!el.media || contentType === 'claims') return;
    var slug = slugFromForm();
    var href = '../' + dirForType() + '/' + slug + '.html';
    if (el.media.tagName === 'A') {
      el.media.setAttribute('href', href);
    }
  }

  form.addEventListener('input', function () {
    sync();
    renderStats();
    bindMediaHref();
  });

  form.addEventListener('change', function () {
    sync();
    renderStats();
    bindMediaHref();
  });

  sync();
  renderStats();
  bindMediaHref();
})();
