/**
 * Lucide icon helper — https://lucide.dev/
 * Requires js/vendor/lucide.min.js (UMD global `lucide`).
 */
(function (global) {
  var ALIASES = {
    shield: "shield-check",
    article: "file-text",
    help: "circle-help",
    grid: "layout-grid",
    share: "share-2",
    backup: "download",
    news: "newspaper",
    layout: "panel-left",
    file: "file-text",
    dup: "copy",
    del: "trash-2",
    edit: "pencil",
    drag: "grip-vertical",
    chevron: "chevron-down",
    "chevron-up": "chevron-up",
    "chevron-down": "chevron-down",
    brochure: "file-text",
    overview: "clipboard-list",
    benefits: "star",
    specs: "table",
    who: "users",
    faq: "circle-help",
    text: "type",
    heading: "heading",
    image: "image",
    video: "video",
    "brochure-image": "image-plus",
    "list-item": "list",
    "spec-row": "table-rows-split",
    "who-block": "layout-list",
    "faq-item": "messages-square",
    quote: "file-text",
    line: "message-circle",
    imageText: "columns-2",
    ctaButton: "mouse-pointer-click",
    cardGrid3: "layout-grid",
    gallery: "images",
    team: "users",
    review: "star",
    customHtml: "code-2",
    facebook: "facebook",
    youtube: "youtube",
    instagram: "instagram",
    globe: "globe",
    phone: "phone",
    close: "x",
    chat: "message-circle",
    menu: "menu",
    eye: "eye",
    eyeOff: "eye-off",
    check: "circle-check",
    clock: "clock",
    mail: "mail",
    email: "mail",
  };

  var BRAND_ICONS = {
    facebook:
      '<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="currentColor" class="{class}" aria-hidden="true"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
    line:
      '<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="currentColor" class="{class}" aria-hidden="true"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>',
    youtube:
      '<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="currentColor" class="{class}" aria-hidden="true"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>',
    instagram:
      '<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="currentColor" class="{class}" aria-hidden="true"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 1 0 0 12.324 6.162 6.162 0 0 0 0-12.324zM12 16a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm6.406-11.845a1.44 1.44 0 1 0 0 2.881 1.44 1.44 0 0 0 0-2.881z"/></svg>',
    mail:
      '<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="currentColor" class="{class}" aria-hidden="true"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>',
    globe:
      '<svg xmlns="http://www.w3.org/2000/svg" width="{size}" height="{size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="{class}" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>',
  };

  function toPascal(kebab) {
    return kebab
      .split("-")
      .map(function (part) {
        return part ? part.charAt(0).toUpperCase() + part.slice(1) : "";
      })
      .join("");
  }

  function resolveName(key) {
    if (!key) return "circle";
    if (ALIASES[key]) return ALIASES[key];
    var lower = String(key).toLowerCase();
    if (ALIASES[lower]) return ALIASES[lower];
    var aliasKey = Object.keys(ALIASES).find(function (k) {
      return k.toLowerCase() === lower;
    });
    if (aliasKey) return ALIASES[aliasKey];
    return lower;
  }

  function brandIcon(key, opts) {
    opts = opts || {};
    var tpl = BRAND_ICONS[String(key || "").toLowerCase()];
    if (!tpl) return null;
    var size = opts.size || 24;
    var cls = opts.className || opts.class || "";
    return tpl.replace(/\{size\}/g, String(size)).replace(/\{class\}/g, cls);
  }

  function iconTupleToSvg(IconDef, svgOpts) {
    if (!IconDef) return null;

    if (typeof IconDef.toSvg === "function") {
      return IconDef.toSvg(svgOpts);
    }

    if (global.lucide && typeof global.lucide.createElement === "function" && Array.isArray(IconDef)) {
      var el = global.lucide.createElement(IconDef);
      if (svgOpts && el && el.setAttribute) {
        if (svgOpts.width != null) el.setAttribute("width", String(svgOpts.width));
        if (svgOpts.height != null) el.setAttribute("height", String(svgOpts.height));
        if (svgOpts["stroke-width"] != null) {
          el.setAttribute("stroke-width", String(svgOpts["stroke-width"]));
        }
        if (svgOpts.class) el.setAttribute("class", svgOpts.class);
        if (svgOpts["aria-hidden"] != null) {
          el.setAttribute("aria-hidden", String(svgOpts["aria-hidden"]));
        }
        if (svgOpts.fill) el.setAttribute("fill", svgOpts.fill);
      }
      return el.outerHTML || "";
    }

    return null;
  }

  function placeholderIcon(lucideName, extraClass, size) {
    return (
      '<i data-lucide="' +
      lucideName +
      '" class="' +
      extraClass +
      '" style="width:' +
      size +
      "px;height:" +
      size +
      'px" aria-hidden="true"></i>'
    );
  }

  function iconClassName(opts) {
    var className = opts.className || opts.class || "";
    return "lucide-icon" + (className ? " " + className : "");
  }

  function icon(key, opts) {
    opts = opts || {};
    if (opts.brand !== false) {
      var branded = brandIcon(key, opts);
      if (branded) return branded;
    }

    var lucideName = resolveName(key);
    var strokeWidth =
      opts.strokeWidth != null
        ? opts.strokeWidth
        : opts.stroke != null
          ? opts.stroke
          : 1.75;
    var size = opts.size || 24;
    var extraClass = iconClassName(opts);

    if (!global.lucide || !global.lucide.icons) {
      return placeholderIcon(lucideName, extraClass, size);
    }

    var IconDef = global.lucide.icons[toPascal(lucideName)];
    if (!IconDef) {
      return placeholderIcon(lucideName, extraClass, size);
    }

    var svgOpts = {
      width: size,
      height: size,
      "stroke-width": strokeWidth,
      class: extraClass,
      "aria-hidden": "true",
    };
    if (opts.fill) svgOpts.fill = opts.fill;

    var svg = iconTupleToSvg(IconDef, svgOpts);
    if (svg) return svg;

    return placeholderIcon(lucideName, extraClass, size);
  }

  function deferIcon(key, opts) {
    return icon(key, opts);
  }

  function elementIconSize(el) {
    var style = el.getAttribute("style") || "";
    var m = style.match(/width:\s*(\d+(?:\.\d+)?)px/);
    if (m) return parseFloat(m[1]);
    if (global.getComputedStyle) {
      var w = parseFloat(global.getComputedStyle(el).width);
      if (w && !isNaN(w)) return w;
    }
    return 24;
  }

  function prepIcons(root) {
    var nodes;
    if (root && root.querySelectorAll && root !== document) {
      nodes = root.querySelectorAll("[data-lucide]");
    } else {
      nodes = document.querySelectorAll("[data-lucide]");
    }

    nodes.forEach(function (el) {
      if (el.tagName !== "I") return;
      var name = el.getAttribute("data-lucide");
      if (!name) return;

      var branded = brandIcon(name, {
        size: elementIconSize(el),
        className: el.className || "",
      });
      if (branded) {
        el.outerHTML = branded;
        return;
      }

      var resolved = resolveName(name);
      if (resolved !== name) el.setAttribute("data-lucide", resolved);
    });
  }

  function refresh(root) {
    prepIcons(root);
    if (!global.lucide || !global.lucide.createIcons) return;
    var options = {};
    if (root && root.querySelectorAll) options.root = root;
    global.lucide.createIcons(options);
  }

  function editorIcon(key, size) {
    return icon(key, {
      size: size || 18,
      strokeWidth: key.indexOf("chevron") >= 0 ? 2.5 : 2,
    });
  }

  global.LucideIcons = {
    name: resolveName,
    icon: icon,
    brand: brandIcon,
    defer: deferIcon,
    editor: editorIcon,
    refresh: refresh,
  };

  function bootRefresh() {
    refresh();
  }

  if (global.document) {
    global.document.addEventListener("landing:rendered", function () {
      refresh(global.document.getElementById("landing-root"));
      refresh(global.document.getElementById("landing-cta-root"));
    });

    if (global.document.readyState === "loading") {
      global.document.addEventListener("DOMContentLoaded", bootRefresh);
    } else {
      bootRefresh();
    }
  }
})(typeof window !== "undefined" ? window : this);
