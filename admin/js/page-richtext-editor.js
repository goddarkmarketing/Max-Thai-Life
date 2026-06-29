(function () {
  var data = window.PAGE_RICHTEXT_DATA;
  if (!data || typeof Quill === "undefined") return;

  var editorEl = document.getElementById("pe-rich-editor");
  var statusEl = document.querySelector("[data-richtext-status]");
  var saveBtn = document.querySelector("[data-richtext-save]");
  if (!editorEl) return;

  // ไอคอนปุ่มย้อนกลับ/ทำซ้ำ (ต้องลงทะเบียนก่อนสร้าง Quill เพื่อให้ทูลบาร์แสดงไอคอน)
  var quillIcons = Quill.import("ui/icons");
  quillIcons["undo"] =
    '<svg viewbox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10"></polygon><path class="ql-stroke" d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9"></path></svg>';
  quillIcons["redo"] =
    '<svg viewbox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="12 10 14 12 16 10 12 10"></polygon><path class="ql-stroke" d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"></path></svg>';

  var toolbarOptions = [
    ["undo", "redo"],
    [{ header: [1, 2, 3, 4, 5, 6, false] }],
    [{ size: ["small", false, "large", "huge"] }],
    ["bold", "italic", "underline", "strike"],
    [{ color: [] }, { background: [] }],
    [{ script: "sub" }, { script: "super" }],
    [{ list: "ordered" }, { list: "bullet" }],
    [{ indent: "-1" }, { indent: "+1" }],
    [{ align: [] }],
    ["blockquote", "code-block"],
    ["link", "image", "video"],
    ["clean"],
  ];

  var quill = new Quill(editorEl, {
    theme: "snow",
    placeholder: "พิมพ์เนื้อหาหน้านี้…",
    modules: {
      toolbar: {
        container: toolbarOptions,
        handlers: {
          undo: function () {
            this.quill.history.undo();
          },
          redo: function () {
            this.quill.history.redo();
          },
        },
      },
      history: { delay: 800, maxStack: 200, userOnly: true },
    },
  });

  if (data.bodyHtml) {
    quill.clipboard.dangerouslyPasteHTML(data.bodyHtml);
  }
  // โหลดเนื้อหาเริ่มต้นแล้วล้างประวัติ จะได้ไม่ undo ย้อนกลับไปจนเนื้อหาหาย
  quill.history.clear();

  var undoBtn = document.querySelector(".ql-toolbar .ql-undo");
  var redoBtn = document.querySelector(".ql-toolbar .ql-redo");
  if (undoBtn) undoBtn.setAttribute("title", "ย้อนกลับ (Ctrl+Z)");
  if (redoBtn) redoBtn.setAttribute("title", "ทำซ้ำ (Ctrl+Y)");

  // ── Image resize (ย่อ/ขยายภาพด้วยการลากมุม) ───────────────────────────
  (function initImageResize() {
    var container = quill.root.parentNode; // .ql-container (position: relative)
    var activeImg = null;
    var overlay = null;
    var dragging = false;

    function buildOverlay() {
      overlay = document.createElement("div");
      overlay.className = "pe-img-overlay";
      ["nw", "ne", "sw", "se"].forEach(function (pos) {
        var h = document.createElement("span");
        h.className = "pe-img-handle pe-img-handle--" + pos;
        h.setAttribute("data-pos", pos);
        overlay.appendChild(h);
      });
      container.appendChild(overlay);
      overlay.addEventListener("mousedown", onHandleDown);
    }

    function position() {
      if (!activeImg || !overlay) return;
      var cRect = container.getBoundingClientRect();
      var iRect = activeImg.getBoundingClientRect();
      overlay.style.left = iRect.left - cRect.left + container.scrollLeft + "px";
      overlay.style.top = iRect.top - cRect.top + container.scrollTop + "px";
      overlay.style.width = iRect.width + "px";
      overlay.style.height = iRect.height + "px";
    }

    function show(img) {
      activeImg = img;
      if (!overlay) buildOverlay();
      overlay.style.display = "block";
      position();
    }

    function hide() {
      activeImg = null;
      if (overlay) overlay.style.display = "none";
    }

    function onHandleDown(e) {
      if (!activeImg) return;
      e.preventDefault();
      e.stopPropagation();
      dragging = true;
      var startX = e.clientX;
      var rect = activeImg.getBoundingClientRect();
      var startW = rect.width;
      var ratio = rect.height / rect.width || 1;
      var pos = e.target.getAttribute("data-pos") || "se";
      var grow = pos === "se" || pos === "ne" ? 1 : -1;
      var maxW = quill.root.clientWidth - 8;

      function onMove(ev) {
        var delta = (ev.clientX - startX) * grow;
        var w = Math.max(40, Math.min(maxW, Math.round(startW + delta)));
        activeImg.setAttribute("width", w);
        activeImg.removeAttribute("height");
        activeImg.style.width = "";
        activeImg.style.height = "";
        // keep ratio hint (optional, browsers handle via height:auto)
        void ratio;
        position();
      }
      function onUp() {
        dragging = false;
        document.removeEventListener("mousemove", onMove);
        document.removeEventListener("mouseup", onUp);
        // nudge Quill so the change is captured in history/output
        quill.update("user");
        position();
      }
      document.addEventListener("mousemove", onMove);
      document.addEventListener("mouseup", onUp);
    }

    quill.root.addEventListener("click", function (e) {
      if (e.target && e.target.tagName === "IMG") {
        show(e.target);
      } else {
        hide();
      }
    });

    quill.on("text-change", function () {
      if (activeImg && !document.body.contains(activeImg)) hide();
      else if (activeImg) position();
    });

    quill.root.addEventListener("scroll", position);
    window.addEventListener("scroll", function () {
      if (!dragging) position();
    }, true);
    window.addEventListener("resize", position);
  })();

  function setStatus(msg, kind) {
    if (!statusEl) return;
    statusEl.textContent = msg || "";
    statusEl.classList.remove("is-ok", "is-error");
    if (kind) statusEl.classList.add(kind);
  }

  // Quill 2 renders both bullet and ordered lists as <ol><li data-list="...">,
  // relying on Quill's own CSS. Convert to plain <ul>/<ol> so the public page
  // (which has no Quill CSS) renders them correctly.
  function normalizeHtml(html) {
    var tmp = document.createElement("div");
    tmp.innerHTML = html;
    tmp.querySelectorAll("ol").forEach(function (ol) {
      var items = Array.prototype.slice.call(ol.children).filter(function (n) {
        return n.tagName === "LI";
      });
      if (!items.some(function (li) { return li.hasAttribute("data-list"); })) return;
      var frag = document.createDocumentFragment();
      var current = null;
      var currentType = null;
      items.forEach(function (li) {
        var type = li.getAttribute("data-list") === "bullet" ? "ul" : "ol";
        if (type !== currentType) {
          current = document.createElement(type);
          frag.appendChild(current);
          currentType = type;
        }
        li.removeAttribute("data-list");
        var ui = li.querySelector(".ql-ui");
        if (ui) ui.parentNode.removeChild(ui);
        current.appendChild(li);
      });
      ol.parentNode.replaceChild(frag, ol);
    });
    return tmp.innerHTML;
  }

  function save() {
    var html = quill.root.innerHTML;
    if (html === "<p><br></p>") html = "";
    if (html) html = normalizeHtml(html);

    saveBtn.disabled = true;
    setStatus("กำลังบันทึก…", "");

    var payload = {
      page: data.page,
      slug: data.slug,
      csrf: data.csrf,
      bodyHtml: html,
      publish: true,
    };
    var ctaTitleEl = document.querySelector("[data-plan-cta-title]");
    var ctaLeadEl = document.querySelector("[data-plan-cta-lead]");
    if (ctaTitleEl) payload.ctaTitle = ctaTitleEl.value.trim();
    if (ctaLeadEl) payload.ctaLead = ctaLeadEl.value.trim();

    fetch(data.saveUrl || "api/page-richtext-save.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        if (res && res.ok) {
          setStatus("บันทึกและเผยแพร่แล้ว ✓", "is-ok");
        } else {
          setStatus((res && res.error) || "บันทึกไม่สำเร็จ", "is-error");
        }
      })
      .catch(function () {
        setStatus("เกิดข้อผิดพลาดในการเชื่อมต่อ", "is-error");
      })
      .finally(function () {
        saveBtn.disabled = false;
      });
  }

  if (saveBtn) saveBtn.addEventListener("click", save);

  document.addEventListener("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === "s") {
      e.preventDefault();
      save();
    }
  });
})();
