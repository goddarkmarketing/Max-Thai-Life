(function (global) {
  var toolbar = null;
  var activeEl = null;
  var uploadImageFn = null;
  var savedRange = null;
  var initialized = false;

  function encodePath(path) {
    return path
      .split("/")
      .map(function (seg) {
        if (!seg) return seg;
        try {
          seg = decodeURIComponent(seg);
        } catch (e) {}
        return encodeURIComponent(seg);
      })
      .join("/");
  }

  function saveSelection() {
    var sel = window.getSelection();
    if (!sel.rangeCount || !activeEl) return;
    var range = sel.getRangeAt(0);
    if (activeEl.contains(range.commonAncestorContainer)) {
      savedRange = range.cloneRange();
    }
  }

  function restoreSelection() {
    if (!savedRange || !activeEl) return;
    activeEl.focus();
    var sel = window.getSelection();
    sel.removeAllRanges();
    sel.addRange(savedRange);
  }

  function runCmd(cmd, value) {
    restoreSelection();
    document.execCommand(cmd, false, value || null);
    saveSelection();
    activeEl && activeEl.focus();
  }

  function wrapSelection(tagName, className) {
    restoreSelection();
    var sel = window.getSelection();
    if (!sel.rangeCount || sel.isCollapsed) return;
    var range = sel.getRangeAt(0);
    var el = document.createElement(tagName);
    if (className) el.className = className;
    try {
      el.appendChild(range.extractContents());
      range.insertNode(el);
      range.selectNodeContents(el);
      sel.removeAllRanges();
      sel.addRange(range);
      savedRange = range.cloneRange();
    } catch (e) {
      runCmd("insertHTML", "<" + tagName + (className ? ' class="' + className + '"' : "") + ">" + sel.toString() + "</" + tagName + ">");
    }
    activeEl && activeEl.focus();
  }

  function applyFontSize(size) {
    restoreSelection();
    var sel = window.getSelection();
    if (!sel.rangeCount || sel.isCollapsed) return;
    var range = sel.getRangeAt(0);
    var span = document.createElement("span");
    span.style.fontSize = size;
    try {
      span.appendChild(range.extractContents());
      range.insertNode(span);
    } catch (e) {
      runCmd("insertHTML", '<span style="font-size:' + size + '">' + sel.toString() + "</span>");
    }
    saveSelection();
    activeEl && activeEl.focus();
  }

  function insertLink() {
    restoreSelection();
    var url = window.prompt("URL ลิงก์:", "https://");
    if (!url) return;
    runCmd("createLink", url);
  }

  function insertImage() {
    if (!uploadImageFn || !activeEl) return;
    saveSelection();
    uploadImageFn("plan_content", function (path) {
      restoreSelection();
      var src = "../" + encodePath(path);
      runCmd(
        "insertHTML",
        '<figure class="plan-inline-figure"><img src="' +
          src +
          '" alt="" loading="lazy" decoding="async"></figure><p><br></p>'
      );
    });
  }

  function toolbarBtn(label, title, action) {
    return (
      '<button type="button" class="pe-rich-btn" title="' +
      title +
      '" data-action="' +
      action +
      '">' +
      label +
      "</button>"
    );
  }

  function createToolbar() {
    toolbar = document.createElement("div");
    toolbar.id = "pe-rich-toolbar";
    toolbar.className = "pe-rich-toolbar";
    toolbar.setAttribute("role", "toolbar");
    toolbar.innerHTML =
      '<div class="pe-rich-toolbar-inner">' +
      toolbarBtn("<strong>B</strong>", "ตัวหนา (Ctrl+B)", "bold") +
      toolbarBtn("<em>I</em>", "ตัวเอียง (Ctrl+I)", "italic") +
      toolbarBtn("<u>U</u>", "ขีดเส้นใต้", "underline") +
      '<span class="pe-rich-sep"></span>' +
      toolbarBtn("ไฮไลท์", "ไฮไลท์ข้อความ", "highlight") +
      toolbarBtn("ลิงก์", "แทรกลิงก์", "link") +
      '<span class="pe-rich-sep"></span>' +
      toolbarBtn("H3", "หัวข้อย่อย", "h3") +
      toolbarBtn("•", "รายการ bullet", "ul") +
      toolbarBtn("1.", "รายการลำดับ", "ol") +
      '<span class="pe-rich-sep"></span>' +
      toolbarBtn("A−", "ข้อความเล็ก", "sm") +
      toolbarBtn("A", "ข้อความปกติ", "md") +
      toolbarBtn("A+", "ข้อความใหญ่", "lg") +
      '<span class="pe-rich-sep"></span>' +
      toolbarBtn("🖼 รูป", "แทรกรูปในเนื้อหา", "image") +
      toolbarBtn("ล้าง", "ล้างรูปแบบ", "clear") +
      "</div>";

    toolbar.addEventListener("mousedown", function (e) {
      e.preventDefault();
    });

    toolbar.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-action]");
      if (!btn || !activeEl) return;
      var action = btn.getAttribute("data-action");
      if (action === "bold") runCmd("bold");
      else if (action === "italic") runCmd("italic");
      else if (action === "underline") runCmd("underline");
      else if (action === "highlight") wrapSelection("mark", "pe-mark");
      else if (action === "link") insertLink();
      else if (action === "h3") runCmd("formatBlock", "h3");
      else if (action === "ul") runCmd("insertUnorderedList");
      else if (action === "ol") runCmd("insertOrderedList");
      else if (action === "sm") applyFontSize("0.875rem");
      else if (action === "md") applyFontSize("1rem");
      else if (action === "lg") applyFontSize("1.25rem");
      else if (action === "image") insertImage();
      else if (action === "clear") runCmd("removeFormat");
    });

    document.body.appendChild(toolbar);
  }

  function positionToolbar(el) {
    if (!toolbar || !el) return;
    var rect = el.getBoundingClientRect();
    var barH = toolbar.offsetHeight || 44;
    var top = rect.top + window.scrollY - barH - 10;
    if (rect.top < barH + 70) {
      top = rect.bottom + window.scrollY + 10;
    }
    var left = Math.max(12, Math.min(rect.left + window.scrollX, window.innerWidth - toolbar.offsetWidth - 12));
    toolbar.style.top = top + "px";
    toolbar.style.left = left + "px";
  }

  function showToolbar(el) {
    activeEl = el;
    toolbar.classList.add("is-visible");
    positionToolbar(el);
    saveSelection();
  }

  function hideToolbar() {
    if (!toolbar) return;
    toolbar.classList.remove("is-visible");
    activeEl = null;
    savedRange = null;
  }

  function getCleanHtml(el) {
    if (!el) return "";
    var clone = el.cloneNode(true);
    clone.querySelectorAll(".pe-edit-hint").forEach(function (node) {
      node.remove();
    });
    return clone.innerHTML.trim();
  }

  function init(opts) {
    uploadImageFn = opts.uploadImage;
    if (!toolbar) createToolbar();
    if (initialized) return;
    initialized = true;

    var root = opts.container || document;

    root.addEventListener(
      "focusin",
      function (e) {
        var el = e.target.closest(".pe-rich");
        if (!el || el.getAttribute("contenteditable") !== "true") return;
        showToolbar(el);
      },
      true
    );

    root.addEventListener(
      "focusout",
      function (e) {
        var next = e.relatedTarget;
        if (next && (next.closest(".pe-rich-toolbar") || next.closest(".pe-rich"))) return;
        window.setTimeout(function () {
          var focused = document.activeElement;
          if (focused && (focused.closest(".pe-rich-toolbar") || focused.closest(".pe-rich"))) return;
          hideToolbar();
        }, 120);
      },
      true
    );

    root.addEventListener(
      "keyup",
      function (e) {
        if (e.target.closest(".pe-rich")) saveSelection();
      },
      true
    );

    root.addEventListener(
      "mouseup",
      function (e) {
        if (e.target.closest(".pe-rich")) saveSelection();
      },
      true
    );

    window.addEventListener(
      "scroll",
      function () {
        if (activeEl) positionToolbar(activeEl);
      },
      true
    );

    window.addEventListener("resize", function () {
      if (activeEl) positionToolbar(activeEl);
    });
  }

  global.PlanRichEditor = {
    init: init,
    getCleanHtml: getCleanHtml,
  };
})(window);
