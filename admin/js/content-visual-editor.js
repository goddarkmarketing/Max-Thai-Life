(function () {
  var boot = window.CONTENT_VISUAL_DATA;
  if (!boot) return;

  var state = {
    type: boot.type,
    slug: boot.slug,
    csrf: boot.csrf,
    item: JSON.parse(JSON.stringify(boot.item)),
  };

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s || "";
    return d.innerHTML;
  }

  function imgSrc(path) {
    if (!path) return "";
    return "../" + path.split("/").map(function (seg) {
      if (!seg) return seg;
      try { seg = decodeURIComponent(seg); } catch (e) {}
      return encodeURIComponent(seg);
    }).join("/");
  }

  function moveIcon(dir) {
    return window.LucideIcons ? LucideIcons.editor("chevron-" + dir, 14) : "";
  }

  function blockActions(opts) {
    opts = opts || {};
    var move = "";
    if (opts.movable) {
      if (opts.canMoveUp !== false) move += '<button type="button" class="pe-block-btn pe-block-btn--move pe-block-btn--up" title="เลื่อนขึ้น">' + moveIcon("up") + "</button>";
      if (opts.canMoveDown !== false) move += '<button type="button" class="pe-block-btn pe-block-btn--move pe-block-btn--down" title="เลื่อนลง">' + moveIcon("down") + "</button>";
    }
    return (
      '<div class="pe-block-actions">' +
      move +
      '<button type="button" class="pe-block-btn pe-block-btn--edit">แก้ไข</button>' +
      (opts.addable ? '<button type="button" class="pe-block-btn pe-block-btn--add">เพิ่ม</button>' : "") +
      (opts.deletable ? '<button type="button" class="pe-block-btn pe-block-btn--delete">ลบ</button>' : "") +
      "</div>"
    );
  }

  function itemActions(i, total, opts) {
    return blockActions({
      deletable: opts.deletable !== false,
      addable: !!opts.addable,
      movable: total > 1,
      canMoveUp: i > 0,
      canMoveDown: i < total - 1,
    });
  }

  function editable(html, placeholder, cls) {
    return (
      '<div class="pe-editable pe-rich' +
      (cls ? " " + cls : "") +
      '" contenteditable="false" spellcheck="false" data-placeholder="' +
      esc(placeholder) +
      '">' +
      (html || "") +
      "</div>"
    );
  }

  function renderHero() {
    var p = state.item;
    document.getElementById("content-hero-inner").innerHTML =
      '<p class="breadcrumb"><a href="../index.html">หน้าหลัก</a> / <a href="' +
      esc(boot.listUrl) +
      '">' +
      esc(boot.listLabel) +
      '</a> / <span class="pe-block-wrap pe-block-wrap--inline" data-pe-block="field">' +
      blockActions({ deletable: false, addable: false }) +
      '<span class="pe-field pe-field--inline">' +
      editable(p.category || "", "หมวด", "pe-editable--inline") +
      "</span></span></p>" +
      '<div class="pe-block-wrap" data-pe-block="field">' +
      blockActions({ deletable: false, addable: false }) +
      '<div class="pe-field">' +
      editable(p.title || "", "หัวข้อ", "") +
      "</div></div>" +
      '<div class="pe-block-wrap" data-pe-block="field">' +
      blockActions({ deletable: false, addable: false }) +
      '<div class="pe-field">' +
      editable(p.description || "", "คำอธิบาย", "") +
      "</div></div>";
  }

  function renderCover() {
    var src = state.item.image || "";
    return (
      '<figure class="article-cover pe-media pe-block-wrap pe-block-wrap--media-actions" data-pe-block="media" data-pe-media="cover">' +
      blockActions({ deletable: !!src, addable: false, edit: true }) +
      (src
        ? '<img src="' + imgSrc(src) + '" alt="">'
        : '<div class="pe-media-placeholder">ยังไม่มีภาพปก</div>') +
      "</figure>"
    );
  }

  function getListRow(wrap) {
    if (wrap && wrap.classList.contains("pe-section-block")) return wrap;
    return wrap ? wrap.closest(".pe-item-row") : null;
  }

  function renderSection(sec, i, total) {
    var paras = (sec.paragraphs || []).map(function (p, pi) {
      return (
        '<div class="pe-block-wrap pe-item-row" data-list="paragraphs" data-index="' +
        pi +
        '">' +
        itemActions(pi, (sec.paragraphs || []).length, { addable: true }) +
        '<div class="pe-field">' +
        editable(p, "ย่อหน้า") +
        "</div></div>"
      );
    }).join("");
    var list = (sec.list || []).map(function (li, lii) {
      return (
        '<li class="pe-block-wrap pe-item-row" data-list="list" data-index="' +
        lii +
        '">' +
        itemActions(lii, (sec.list || []).length, { addable: true }) +
        '<div class="pe-field">' +
        editable(li, "รายการ") +
        "</div></li>"
      );
    }).join("");
    return (
      '<section class="article-prose-section pe-section-block pe-block-wrap" data-list="sections" data-index="' +
      i +
      '">' +
      itemActions(i, total, { addable: true }) +
      '<div class="pe-section-inner">' +
      '<div class="pe-field pe-section-heading-wrap">' +
      editable(sec.heading || "", "หัวข้อส่วน", "article-section-heading") +
      "</div>" +
      '<div class="pe-section-paragraphs" data-pe-list="paragraphs">' +
      paras +
      "</div>" +
      (list ? '<ul class="pe-section-list" data-pe-list="list">' + list + "</ul>" : '<ul class="pe-section-list" data-pe-list="list"></ul>') +
      "</div></section>"
    );
  }

  function renderBody() {
    var sections = state.item.sections || [];
    if (!sections.length) sections = [{ heading: "", paragraphs: [""], list: [] }];
    var html =
      renderCover() +
      sections.map(function (s, i) {
        return renderSection(s, i, sections.length);
      }).join("") +
      '<p class="pe-add-section"><button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" id="pe-add-section">+ เพิ่มส่วนเนื้อหา</button></p>';
    document.getElementById("content-visual-root").innerHTML =
      '<article class="article-detail article-detail-main"><div class="article-prose">' + html + "</div></article>";
  }

  function stopEditing() {
    document.querySelectorAll(".pe-block-wrap.is-editing").forEach(function (w) {
      w.classList.remove("is-editing");
      w.querySelectorAll(".pe-editable").forEach(function (el) {
        el.setAttribute("contenteditable", "false");
      });
    });
  }

  function startEdit(wrap) {
    if (!wrap || wrap.getAttribute("data-pe-block") === "media") return;
    stopEditing();
    wrap.classList.add("is-editing");
    wrap.querySelectorAll(".pe-editable").forEach(function (el) {
      el.setAttribute("contenteditable", "true");
    });
    var first = wrap.querySelector(".pe-editable");
    if (first) first.focus();
  }

  function cleanHtml(el) {
    if (window.PlanRichEditor && PlanRichEditor.getCleanHtml) return PlanRichEditor.getCleanHtml(el);
    return el ? el.innerHTML.trim() : "";
  }

  function plainText(el) {
    return el ? (el.textContent || "").trim() : "";
  }

  function collectFromDom() {
    var hero = document.getElementById("content-hero-inner");
    state.item.category = plainText(hero.querySelector(".breadcrumb .pe-editable"));
    state.item.title = plainText(hero.querySelectorAll(".pe-field .pe-editable")[0]);
    state.item.description = plainText(hero.querySelectorAll(".pe-field .pe-editable")[1]);

    var root = document.getElementById("content-visual-root");
    state.item.sections = [];
    root.querySelectorAll(".pe-section-block").forEach(function (sec) {
      var heading = sec.querySelector(".article-section-heading");
      var section = { heading: heading ? plainText(heading) : "", paragraphs: [], list: [] };
      sec.querySelectorAll('[data-pe-list="paragraphs"] .pe-item-row').forEach(function (row) {
        var ed = row.querySelector(".pe-editable");
        var t = ed ? plainText(ed) : "";
        if (t) section.paragraphs.push(t);
      });
      sec.querySelectorAll('[data-pe-list="list"] .pe-item-row').forEach(function (row) {
        var ed = row.querySelector(".pe-editable");
        var t = ed ? plainText(ed) : "";
        if (t) section.list.push(t);
      });
      if (section.heading || section.paragraphs.length || section.list.length) {
        state.item.sections.push(section);
      }
    });
  }

  function swapInArray(arr, idx, dir) {
    var target = dir === "up" ? idx - 1 : idx + 1;
    if (target < 0 || target >= arr.length) return;
    var t = arr[idx];
    arr[idx] = arr[target];
    arr[target] = t;
  }

  function handleMove(wrap, dir) {
    collectFromDom();
    var row = getListRow(wrap);
    if (!row) return;
    var list = row.getAttribute("data-list");
    var idx = parseInt(row.getAttribute("data-index"), 10);
    if (list === "sections") {
      swapInArray(state.item.sections, idx, dir);
    } else if (list === "paragraphs" || list === "list") {
      var secRow = row.closest(".pe-section-block");
      var si = parseInt(secRow.getAttribute("data-index"), 10);
      var sec = state.item.sections[si];
      if (!sec) return;
      var arr = list === "paragraphs" ? sec.paragraphs : sec.list;
      swapInArray(arr, idx, dir);
    }
    renderAll();
  }

  function handleAdd(wrap) {
    collectFromDom();
    var row = getListRow(wrap);
    if (!row) return;
    var list = row.getAttribute("data-list");
    var idx = parseInt(row.getAttribute("data-index"), 10);
    if (list === "sections") {
      state.item.sections.splice(idx + 1, 0, { heading: "หัวข้อใหม่", paragraphs: ["เนื้อหาใหม่"], list: [] });
    } else if (list === "paragraphs") {
      var si = parseInt(row.closest(".pe-section-block").getAttribute("data-index"), 10);
      state.item.sections[si].paragraphs.splice(idx + 1, 0, "ย่อหน้าใหม่");
    } else if (list === "list") {
      var si2 = parseInt(row.closest(".pe-section-block").getAttribute("data-index"), 10);
      if (!state.item.sections[si2].list) state.item.sections[si2].list = [];
      state.item.sections[si2].list.splice(idx + 1, 0, "รายการใหม่");
    }
    renderAll();
  }

  function handleDelete(wrap) {
    collectFromDom();
    var row = getListRow(wrap);
    if (!row) return;
    var list = row.getAttribute("data-list");
    var idx = parseInt(row.getAttribute("data-index"), 10);
    if (list === "sections") {
      state.item.sections.splice(idx, 1);
    } else if (list === "paragraphs") {
      var si = parseInt(row.closest(".pe-section-block").getAttribute("data-index"), 10);
      state.item.sections[si].paragraphs.splice(idx, 1);
    } else if (list === "list") {
      var si2 = parseInt(row.closest(".pe-section-block").getAttribute("data-index"), 10);
      state.item.sections[si2].list.splice(idx, 1);
    }
    renderAll();
  }

  function uploadImage(spec, cb) {
    var input = document.getElementById("pe-file-input");
    input.onchange = function () {
      var file = input.files && input.files[0];
      input.value = "";
      if (!file) return;
      var fd = new FormData();
      fd.append("file", file);
      fd.append("spec", spec);
      fd.append("csrf", state.csrf);
      fetch("api/upload.php", { method: "POST", body: fd })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (!data.ok) throw new Error(data.error || "อัปโหลดไม่สำเร็จ");
          cb(data.path);
        })
        .catch(function (e) { setStatus(e.message, "error"); });
    };
    input.click();
  }

  function setStatus(msg, type) {
    var el = document.getElementById("pe-status");
    el.textContent = msg;
    el.className = "pe-status" + (type ? " is-" + type : "");
  }

  function save(publish) {
    collectFromDom();
    setStatus("กำลังบันทึก...", "");
    fetch("api/content-save.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        type: state.type,
        slug: state.slug,
        csrf: state.csrf,
        item: state.item,
        publish: !!publish,
      }),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || "บันทึกไม่สำเร็จ");
        setStatus(publish ? "เผยแพร่แล้ว ✓" : "บันทึกแล้ว ✓", "ok");
      })
      .catch(function (e) { setStatus(e.message, "error"); });
  }

  function renderAll() {
    renderHero();
    renderBody();
    bindEvents();
    if (window.LucideIcons) LucideIcons.refresh(document.getElementById("pe-canvas-wrap"));
  }

  var bound = false;
  function bindEvents() {
    if (bound) return;
    bound = true;
    var canvas = document.getElementById("pe-canvas-wrap");
    canvas.addEventListener("click", function (e) {
      if (e.target.closest(".pe-block-btn--up")) {
        e.preventDefault();
        handleMove(e.target.closest(".pe-block-wrap"), "up");
        return;
      }
      if (e.target.closest(".pe-block-btn--down")) {
        e.preventDefault();
        handleMove(e.target.closest(".pe-block-wrap"), "down");
        return;
      }
      if (e.target.closest(".pe-block-btn--edit")) {
        e.preventDefault();
        var w = e.target.closest(".pe-block-wrap");
        if (w.getAttribute("data-pe-media") === "cover") {
          uploadImage(boot.coverSpec || "article_cover", function (path) {
            state.item.image = path;
            renderAll();
          });
          return;
        }
        startEdit(w);
        return;
      }
      if (e.target.closest(".pe-block-btn--add")) {
        e.preventDefault();
        handleAdd(e.target.closest(".pe-block-wrap"));
        return;
      }
      if (e.target.closest(".pe-block-btn--delete")) {
        e.preventDefault();
        if (e.target.closest("[data-pe-media=cover]")) {
          state.item.image = "";
          renderAll();
          return;
        }
        if (window.confirm("ลบส่วนนี้?")) handleDelete(e.target.closest(".pe-block-wrap"));
      }
    });
    document.addEventListener("click", function (e) {
      if (e.target.closest(".pe-block-btn") || e.target.closest(".pe-rich-toolbar")) return;
      if (e.target.closest(".pe-block-wrap.is-editing")) return;
      stopEditing();
    });
    document.getElementById("pe-save").addEventListener("click", function () { save(false); });
    document.getElementById("pe-publish").addEventListener("click", function () { save(true); });
    canvas.addEventListener("click", function (e) {
      if (e.target.closest("#pe-add-section")) {
        e.preventDefault();
        collectFromDom();
        state.item.sections.push({ heading: "หัวข้อใหม่", paragraphs: ["เนื้อหาใหม่"], list: [] });
        renderAll();
      }
    });
    if (window.PlanRichEditor) {
      PlanRichEditor.init({ container: document, uploadImage: uploadImage });
    }
  }

  renderAll();
})();
