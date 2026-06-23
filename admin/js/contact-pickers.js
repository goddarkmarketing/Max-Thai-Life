(function () {
  'use strict';

  function clamp(n, min, max) {
    return Math.min(max, Math.max(min, n));
  }

  function hsvToRgb(h, s, v) {
    var c = v * s;
    var x = c * (1 - Math.abs(((h / 60) % 2) - 1));
    var m = v - c;
    var rgb;
    if (h < 60) rgb = [c, x, 0];
    else if (h < 120) rgb = [x, c, 0];
    else if (h < 180) rgb = [0, c, x];
    else if (h < 240) rgb = [0, x, c];
    else if (h < 300) rgb = [x, 0, c];
    else rgb = [c, 0, x];
    return {
      r: Math.round((rgb[0] + m) * 255),
      g: Math.round((rgb[1] + m) * 255),
      b: Math.round((rgb[2] + m) * 255),
    };
  }

  function rgbToHex(r, g, b) {
    function hex(n) {
      return ('0' + n.toString(16)).slice(-2);
    }
    return '#' + hex(r) + hex(g) + hex(b);
  }

  function hexToRgb(hex) {
    var m = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
    if (!m) return null;
    var n = parseInt(m[1], 16);
    return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
  }

  function rgbToHsv(r, g, b) {
    r /= 255;
    g /= 255;
    b /= 255;
    var max = Math.max(r, g, b);
    var min = Math.min(r, g, b);
    var d = max - min;
    var h = 0;
    if (d !== 0) {
      if (max === r) h = ((g - b) / d) % 6;
      else if (max === g) h = (b - r) / d + 2;
      else h = (r - g) / d + 4;
      h *= 60;
      if (h < 0) h += 360;
    }
    var s = max === 0 ? 0 : d / max;
    return { h: h, s: s, v: max };
  }

  function normalizeHex(hex) {
    hex = (hex || '').trim();
    if (/^[0-9a-f]{6}$/i.test(hex)) return '#' + hex.toLowerCase();
    if (/^#[0-9a-f]{6}$/i.test(hex)) return hex.toLowerCase();
    return null;
  }

  function initIconPicker(root) {
    if (!root || root.dataset.iconBound) return;
    root.dataset.iconBound = '1';
    var input = root.querySelector('[data-icon-input]');
    if (!input) return;

    root.querySelectorAll('[data-icon]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var icon = btn.getAttribute('data-icon') || '';
        input.value = icon;
        root.querySelectorAll('.admin-icon-picker__btn').forEach(function (el) {
          var on = el === btn;
          el.classList.toggle('is-selected', on);
          el.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
      });
    });
  }

  function initColorPicker(root) {
    if (!root || root.dataset.colorBound) return;
    root.dataset.colorBound = '1';

    var sat = root.querySelector('[data-color-sat]');
    var satCursor = root.querySelector('[data-color-sat-cursor]');
    var hueInput = root.querySelector('[data-color-hue]');
    var hexInput = root.querySelector('[data-color-hex]');
    var swatch = root.querySelector('[data-color-swatch]');
    var copyBtn = root.querySelector('[data-color-copy]');
    if (!sat || !satCursor || !hueInput || !hexInput) return;

    var state = { h: 0, s: 1, v: 1 };

    function applyUi() {
      var rgb = hsvToRgb(state.h, state.s, state.v);
      var hex = rgbToHex(rgb.r, rgb.g, rgb.b);
      sat.style.backgroundColor = 'hsl(' + state.h + ', 100%, 50%)';
      satCursor.style.left = state.s * 100 + '%';
      satCursor.style.top = (1 - state.v) * 100 + '%';
      hexInput.value = hex;
      if (swatch) swatch.style.background = hex;
    }

    function setFromHex(hex) {
      var norm = normalizeHex(hex);
      if (!norm) return;
      var rgb = hexToRgb(norm);
      if (!rgb) return;
      var hsv = rgbToHsv(rgb.r, rgb.g, rgb.b);
      state.h = hsv.h;
      state.s = hsv.s;
      state.v = hsv.v;
      hueInput.value = String(Math.round(state.h));
      applyUi();
    }

    function setFromSatEvent(e) {
      var rect = sat.getBoundingClientRect();
      state.s = clamp((e.clientX - rect.left) / rect.width, 0, 1);
      state.v = clamp(1 - (e.clientY - rect.top) / rect.height, 0, 1);
      applyUi();
    }

    hueInput.addEventListener('input', function () {
      state.h = Number(hueInput.value) || 0;
      applyUi();
    });

    hexInput.addEventListener('change', function () {
      setFromHex(hexInput.value);
    });

    hexInput.addEventListener('blur', function () {
      var norm = normalizeHex(hexInput.value);
      if (norm) setFromHex(norm);
    });

    sat.addEventListener('mousedown', function (e) {
      e.preventDefault();
      setFromSatEvent(e);
      function move(ev) {
        setFromSatEvent(ev);
      }
      function up() {
        document.removeEventListener('mousemove', move);
        document.removeEventListener('mouseup', up);
      }
      document.addEventListener('mousemove', move);
      document.addEventListener('mouseup', up);
    });

    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        var text = hexInput.value || '';
        if (!text) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text);
        } else {
          hexInput.select();
          document.execCommand('copy');
        }
      });
    }

    setFromHex(root.getAttribute('data-initial') || hexInput.value || '#015fd9');
  }

  function initPickers(scope) {
    (scope || document).querySelectorAll('[data-icon-picker]').forEach(initIconPicker);
    (scope || document).querySelectorAll('[data-color-picker]').forEach(initColorPicker);
    if (window.LucideIcons) {
      LucideIcons.refresh(scope || document);
    }
  }

  window.AdminContactPickers = { init: initPickers };

  document.addEventListener('DOMContentLoaded', function () {
    initPickers(document);
  });
})();
