(function () {
  var boot = window.PLAN_VISUAL_DATA;
  if (!boot) return;

  var state = {
    slug: boot.slug,
    csrf: boot.csrf,
    detail: JSON.parse(JSON.stringify(boot.detail)),
    card: boot.card ? JSON.parse(JSON.stringify(boot.card)) : null,
    imageTarget: null,
    showGallerySlot: false,
    insertAt: -1,
  };

  function moveIcon(dir, size) {
    size = size || 14;
    return window.LucideIcons ? LucideIcons.editor("chevron-" + dir, size) : "";
  }

  function gripIcon() {
    return window.LucideIcons ? LucideIcons.editor("drag", 14) : "";
  }

  function ensureOverviewBlocks() {
    if (!state.detail.overviewBlocks) {
      if (state.detail.brochureImages && state.detail.brochureImages.length) {
        state.detail.overviewBlocks = state.detail.brochureImages.map(function (src) {
          return { type: "image", src: src };
        });
      } else {
        state.detail.overviewBlocks = [];
      }
    }
    return state.detail.overviewBlocks;
  }

  function syncBrochureImages() {
    state.detail.brochureImages = ensureOverviewBlocks()
      .filter(function (b) {
        return b.type === "image" && b.src;
      })
      .map(function (b) {
        return b.src;
      });
  }

  function getOverviewMediaList() {
    var blocks = state.detail.overviewBlocks || [];
    var cover = state.detail.image || (state.card && state.card.image) || "";
    if (!blocks.length) return cover ? [{ type: "cover", src: cover }] : [];
    return blocks.map(function (b) {
      if (b.cover || (b.type === "image" && cover && b.src === cover && blocks.filter(function (x) {
        return x.type === "image" && x.src === cover;
      }).length === 1)) {
        return { type: "cover", src: b.src };
      }
      return b;
    });
  }

  function applyOverviewMediaList(items) {
    var coverSrc = "";
    var blocks = [];
    items.forEach(function (item) {
      if (item.type === "cover") {
        coverSrc = item.src || "";
        blocks.push({ type: "image", src: item.src, cover: true });
      } else if (item.type === "image") {
        blocks.push({ type: "image", src: item.src });
      } else {
        blocks.push(item);
      }
    });
    state.detail.image = coverSrc;
    if (!state.detail.image) {
      var firstImg = items.find(function (i) {
        return i.type === "image" || i.type === "cover";
      });
      state.detail.image = firstImg ? firstImg.src : "";
    }
    if (state.card) state.card.image = state.detail.image;
    state.detail.overviewBlocks = blocks;
    syncBrochureImages();
  }

  var DEFAULT_SECTIONS = ["overview", "benefits", "specs", "who", "faq"];

  function normalizeSectionOrder(order) {
    return (order || DEFAULT_SECTIONS.slice()).filter(function (id) {
      return id !== "brochure";
    });
  }

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

  function imgSrc(path) {
    if (!path) return "";
    return "../" + encodePath(path);
  }

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s || "";
    return d.innerHTML;
  }

  function plainText(html) {
    var d = document.createElement("div");
    d.innerHTML = html || "";
    return (d.textContent || "").trim();
  }

  function blockActions(opts) {
    if (typeof opts === "boolean") {
      opts = { deletable: opts, addable: opts };
    }
    opts = opts || {};
    var showEdit = opts.edit !== false;
    var showAdd = !!opts.addable;
    var showDelete = !!opts.deletable;
    var showMove = !!opts.movable;
    var moveHtml = "";
    if (showMove) {
      var upOff = opts.canMoveUp === false;
      var downOff = opts.canMoveDown === false;
      moveHtml +=
        '<button type="button" class="pe-block-btn pe-block-btn--move pe-block-btn--up' +
        (upOff ? " is-disabled" : "") +
        '" title="เลื่อนขึ้น" aria-label="เลื่อนขึ้น"' +
        (upOff ? " disabled" : "") +
        '>' +
        moveIcon("up") +
        "</button>";
      moveHtml +=
        '<button type="button" class="pe-block-btn pe-block-btn--move pe-block-btn--down' +
        (downOff ? " is-disabled" : "") +
        '" title="เลื่อนลง" aria-label="เลื่อนลง"' +
        (downOff ? " disabled" : "") +
        '>' +
        moveIcon("down") +
        "</button>";
    }
    var actionsTag = opts.inline ? "span" : "div";
    return (
      "<" + actionsTag + ' class="pe-block-actions">' +
      moveHtml +
      (showEdit ? '<button type="button" class="pe-block-btn pe-block-btn--edit">แก้ไข</button>' : "") +
      (showAdd ? '<button type="button" class="pe-block-btn pe-block-btn--add">เพิ่ม</button>' : "") +
      (showDelete ? '<button type="button" class="pe-block-btn pe-block-btn--delete">ลบ</button>' : "") +
      "</" + actionsTag + ">"
    );
  }

  function itemActions(index, total, opts) {
    opts = opts || {};
    var movable = opts.movable != null ? opts.movable : total > 1;
    return blockActions({
      deletable: opts.deletable !== false,
      addable: !!opts.addable,
      edit: opts.edit,
      movable: movable,
      canMoveUp: opts.canMoveUp != null ? opts.canMoveUp : index > 0,
      canMoveDown: opts.canMoveDown != null ? opts.canMoveDown : index < total - 1,
    });
  }

  function overviewMediaActions(index, total) {
    if (total === 1) {
      var items = getOverviewMediaList();
      var only = items[0] || {};
      var after = !!state.detail.overviewMediaAfterContent;
      if (only.type === "text") {
        return itemActions(index, total, {
          deletable: true,
          addable: false,
          movable: true,
          canMoveUp: after,
          canMoveDown: !after,
        });
      }
      return itemActions(index, total, {
        deletable: true,
        addable: false,
        movable: true,
        canMoveUp: after,
        canMoveDown: !after,
      });
    }
    return itemActions(index, total, { deletable: true, addable: false, movable: true });
  }

  function ensureOverviewMediaState() {
    var img = state.detail.image || (state.card && state.card.image) || "";
    if (!state.detail.overviewBlocks || !state.detail.overviewBlocks.length) {
      if (img) {
        state.detail.overviewBlocks = [{ type: "image", src: img, cover: true }];
      }
    }
    if (state.detail.overviewMediaAfterContent == null) {
      state.detail.overviewMediaAfterContent = false;
    }
  }

  function editableInner(html, placeholder, extraClass, dataAttrs, tag) {
    tag = tag || "div";
    var cls = "pe-editable" + (extraClass ? " " + extraClass : "");
    return (
      "<" +
      tag +
      ' class="' +
      cls +
      '" contenteditable="false" spellcheck="false" data-placeholder="' +
      esc(placeholder || "คลิกเพื่อพิมพ์...") +
      '" ' +
      (dataAttrs || "") +
      ">" +
      (html || "") +
      "</" +
      tag +
      ">"
    );
  }

  function fieldWrap(content, opts) {
    if (typeof opts === "boolean") {
      opts = { deletable: opts };
    }
    opts = opts || {};
    return (
      '<div class="pe-block-wrap" data-pe-block="field">' +
      blockActions({ deletable: !!opts.deletable, addable: !!opts.addable }) +
      '<div class="pe-field">' +
      content +
      "</div></div>"
    );
  }

  function richField(html, placeholder, extraClass, dataAttrs, deletable) {
    return fieldWrap(
      editableInner(html, placeholder, "pe-rich" + (extraClass ? " " + extraClass : ""), dataAttrs, "div"),
      deletable
    );
  }

  function textField(html, placeholder, extraAttrs, deletable) {
    return fieldWrap(editableInner(html, placeholder, "", extraAttrs, "div"), deletable);
  }

  function inlineField(html, placeholder) {
    return (
      '<span class="pe-block-wrap pe-block-wrap--inline" data-pe-block="field">' +
      blockActions({ deletable: false, addable: false, inline: true }) +
      '<span class="pe-field pe-field--inline">' +
      editableInner(html, placeholder, "pe-editable--inline", "", "span") +
      "</span></span>"
    );
  }

  function renderHero() {
    var p = state.detail;
    document.getElementById("plan-hero-inner").innerHTML =
      '<div class="breadcrumb"><a href="../plans.html">แผนประกัน</a> / ' +
      inlineField(p.breadcrumb || "", "Breadcrumb") +
      '</div>' +
      fieldWrap(
        editableInner(p.title || "", "หัวข้อแผน", "pe-rich", 'data-pe="title" data-layer-id="hero-title"', "h1")
      ) +
      fieldWrap(
        editableInner(
          p.heroLead || "",
          "คำโปรย",
          "pe-rich page-hero-lead",
          'data-pe="heroLead" data-layer-id="hero-lead"',
          "p"
        )
      );
  }

  function renderCoverBlock(item, index, total) {
    return (
      '<figure class="plan-section-cover pe-media pe-item-row pe-block-wrap pe-block-wrap--media-actions" data-pe-block="media" data-pe-media="cover" data-overview-block="1" data-block-type="cover" data-list="overview-media" data-index="' +
      index +
      '" data-block-src="' +
      esc(item.src) +
      '" data-layer-id="overview-cover">' +
      overviewMediaActions(index, total) +
      '<img src="' +
      imgSrc(item.src) +
      '" alt="" width="960" height="540">' +
      "</figure>"
    );
  }

  function renderContentAddBar(insertAfter) {
    return (
      '<div class="pe-content-add-bar" data-insert-after="' +
      insertAfter +
      '">' +
      '<button type="button" class="pe-content-add-btn pe-content-add-btn--image" data-add-type="image">+ เพิ่มรูป</button>' +
      '<button type="button" class="pe-content-add-btn pe-content-add-btn--text" data-add-type="text">+ เพิ่มข้อความ</button>' +
      "</div>"
    );
  }

  function renderOverviewImageBlock(block, index, total) {
    return (
      '<figure class="plan-gallery-item pe-item-row pe-block-wrap pe-block-wrap--media-actions" data-pe-block="media" data-overview-block="1" data-block-type="image" data-list="overview-media" data-index="' +
      index +
      '" data-block-src="' +
      esc(block.src) +
      '" data-layer-id="overview-block-' +
      index +
      '">' +
      overviewMediaActions(index, total) +
      '<img src="' +
      imgSrc(block.src) +
      '" alt="รูป ' +
      (index + 1) +
      '">' +
      "</figure>"
    );
  }

  function renderOverviewTextBlock(block, index, total) {
    return (
      '<div class="plan-overview-inline-text pe-item-row pe-block-wrap pe-block-wrap--media-actions" data-overview-block="1" data-block-type="text" data-list="overview-media" data-index="' +
      index +
      '" data-layer-id="overview-block-' +
      index +
      '">' +
      overviewMediaActions(index, total) +
      '<div class="pe-field">' +
      editableInner(block.html || "", "พิมพ์ข้อความ…", "pe-rich plan-overview-inline-body") +
      "</div></div>"
    );
  }

  function renderOverviewBlock(item, index, total) {
    if (item.type === "cover") return renderCoverBlock(item, index, total);
    if (item.type === "text") return renderOverviewTextBlock(item, index, total);
    return renderOverviewImageBlock(item, index, total);
  }

  function renderOverviewMedia() {
    var items = getOverviewMediaList();
    var total = items.length;
    var parts = [renderContentAddBar(-1)];
    items.forEach(function (block, i) {
      parts.push(renderOverviewBlock(block, i, total));
      parts.push(renderContentAddBar(i));
    });
    if (state.showGallerySlot) parts.push(renderGallerySlot());
    if (!total) {
      parts.push(
        '<figure class="plan-section-cover pe-media pe-block-wrap" data-pe-media="cover" data-pe-block="media" data-layer-id="overview-cover">' +
        blockActions({ deletable: false, addable: false }) +
        '<div class="pe-media-placeholder">ยังไม่มีภาพปก<br><small>' +
        (boot.imageSpec.cover || "") +
        "</small></div></figure>"
      );
    }
    return '<div class="plan-overview-media plan-image-gallery pe-image-gallery" data-pe-list="overview-media">' + parts.join("") + "</div>";
  }

  function renderGallerySlot() {
    return (
      '<div class="pe-gallery-slot pe-gallery-empty pe-block-wrap pe-block-wrap--media-actions" data-pe-block="media" data-gallery-slot="1" data-layer-id="gallery-slot">' +
      blockActions({ deletable: true, addable: false, edit: false }) +
      '<div class="pe-gallery-slot-inner">' +
      '<p class="pe-media-placeholder-text">ลากรูปมาวางที่นี่ หรือ <button type="button" class="pe-gallery-pick-btn">เลือกไฟล์</button></p>' +
      "<small>" +
      (boot.imageSpec.gallery || "") +
      "</small></div></div>"
    );
  }

  function renderOverview() {
    var p = state.detail;
    var media = renderOverviewMedia();
    var body =
      "<h2>ภาพรวมแผน</h2>" +
      richField(p.overview || "", "พิมพ์เนื้อหาภาพรวม…", "plan-overview-body", 'data-pe="overview" data-layer-id="overview-body"') +
      '<div class="plan-highlight-box" data-layer-id="overview-highlight"><strong contenteditable="false">จุดขายหลัก:</strong> ' +
      fieldWrap(editableInner(p.highlight || "", "จุดขายหลัก", "pe-rich"), { deletable: false, addable: false }) +
      "</div>";
    var items = getOverviewMediaList();
    var mediaAfter = items.length === 1 && !!state.detail.overviewMediaAfterContent;
    return (
      '<section id="overview" class="pe-section" data-section-id="overview" data-layer-id="section-overview">' +
      (mediaAfter ? body + media : media + body) +
      "</section>"
    );
  }

  function listRow(html, index, listKey, total) {
    return (
      '<li class="pe-item-row pe-block-wrap" data-pe-block="item" data-list="' +
      listKey +
      '" data-index="' +
      index +
      '" data-layer-id="' +
      listKey +
      "-" +
      index +
      '">' +
      itemActions(index, total, { deletable: true, addable: true }) +
      '<div class="pe-field">' +
      editableInner(html, "พิมพ์ข้อความ…", "pe-rich") +
      "</div></li>"
    );
  }

  function renderBenefits() {
    var benefits = state.detail.benefits || [];
    var items = benefits.map(function (b, i) {
      return listRow(b, i, "benefits", benefits.length);
    }).join("");
    return (
      '<section id="benefits" class="pe-section" data-section-id="benefits" data-layer-id="section-benefits">' +
      "<h2>จุดเด่นและผลประโยชน์</h2>" +
      '<ul data-pe-list="benefits">' +
      items +
      "</ul></section>"
    );
  }

  function renderSpecs() {
    var specs = state.detail.specs || [];
    var rows = specs.map(function (row, i) {
      return (
        '<div class="pe-spec-row pe-item-row pe-block-wrap" data-pe-block="item" data-list="specs" data-index="' +
        i +
        '" data-layer-id="specs-' +
        i +
        '">' +
        itemActions(i, specs.length, { deletable: true, addable: true }) +
        '<div class="pe-spec-label">' +
        editableInner(row[0] || "", "หัวข้อ") +
        "</div>" +
        '<div class="pe-spec-value">' +
        editableInner(row[1] || "", "รายละเอียด", "pe-rich") +
        "</div></div>"
      );
    }).join("");
    var empty =
      !(state.detail.specs || []).length
        ? '<div class="pe-list-empty pe-block-wrap" data-pe-block="list-empty" data-list="specs">' +
          '<div class="pe-block-actions"><button type="button" class="pe-block-btn pe-block-btn--add">เพิ่ม</button></div>' +
          '<p class="pe-list-empty-text">ยังไม่มีรายการ — กดเพิ่ม</p></div>'
        : "";
    return (
      '<section id="specs" class="pe-section" data-section-id="specs" data-layer-id="section-specs">' +
      "<h2>ข้อมูลแผน (ภาพรวม)</h2>" +
      '<div class="plan-spec-table pe-spec-list" data-pe-list="specs">' +
      rows +
      empty +
      "</div></section>"
    );
  }

  function renderWho() {
    var whoBlocks = state.detail.whoBlocks || [];
    var blocks = whoBlocks.map(function (b, i) {
      return (
        '<div class="info-block pe-block pe-item-row pe-block-wrap" data-pe-block="item" data-list="who" data-index="' +
        i +
        '" data-layer-id="who-' +
        i +
        '">' +
        itemActions(i, whoBlocks.length, { deletable: true, addable: true }) +
        '<div style="flex:1">' +
        "<h4>" +
        editableInner(b.title || "", "หัวข้อ") +
        "</h4>" +
        editableInner(b.text || "", "เนื้อหา", "pe-rich") +
        "</div></div>"
      );
    }).join("");
    return (
      '<section id="who" class="pe-section" data-section-id="who" data-layer-id="section-who">' +
      "<h2>เหมาะกับใคร</h2>" +
      '<div class="two-col-blocks" data-pe-list="who">' +
      blocks +
      "</div></section>"
    );
  }

  function renderFaq() {
    var faq = state.detail.faq || [];
    var items = faq.map(function (item, i) {
      return (
        '<div class="pe-faq-wrap pe-item-row pe-block-wrap" data-pe-block="item" data-list="faq" data-index="' +
        i +
        '" data-layer-id="faq-' +
        i +
        '">' +
        itemActions(i, faq.length, { deletable: true, addable: true }) +
        '<div class="faq-item faq-item--edit-open">' +
        '<div class="faq-item__summary">' +
        '<div class="pe-field">' +
        editableInner(item.q || "", "คำถาม", "pe-rich faq-item__question") +
        "</div>" +
        "</div>" +
        '<div class="faq-item__answer">' +
        editableInner(item.a || "", "คำตอบ", "pe-rich") +
        "</div></div></div>"
      );
    }).join("");
    return (
      '<section id="faq" class="plan-faq pe-section" data-section-id="faq" data-layer-id="section-faq">' +
      "<h2>คำถามที่พบบ่อย</h2>" +
      '<div data-pe-list="faq">' +
      items +
      "</div></section>"
    );
  }

  var sectionRenderers = {
    overview: renderOverview,
    benefits: renderBenefits,
    specs: renderSpecs,
    who: renderWho,
    faq: renderFaq,
  };

  var NAV_LABELS = {
    overview: "ภาพรวม",
    benefits: "จุดเด่น",
    specs: "ข้อมูลแผน",
    who: "เหมาะกับใคร",
    faq: "คำถามที่พบบ่อย",
  };

  function syncSectionOrderFromNav() {
    var nav = document.querySelector(".plan-visual-mode .plan-sidebar nav");
    if (!nav) return;
    var visibleIds = [];
    nav.querySelectorAll(".pe-nav-item").forEach(function (el) {
      visibleIds.push(el.getAttribute("data-section-id"));
    });
    var order = normalizeSectionOrder(state.detail.sectionOrder);
    var hidden = order.filter(function (id) {
      return visibleIds.indexOf(id) < 0;
    });
    state.detail.sectionOrder = normalizeSectionOrder(hidden.concat(visibleIds));
  }

  function reorderSectionsInDom() {
    var container = document.getElementById("pe-sections");
    if (!container) return;
    var order = normalizeSectionOrder(state.detail.sectionOrder);
    order.forEach(function (id) {
      var sec = container.querySelector('[data-section-id="' + id + '"]');
      if (sec) container.appendChild(sec);
    });
  }

  function renderDetail() {
    var order = normalizeSectionOrder(state.detail.sectionOrder);
    var sectionsHtml = order
      .map(function (id) {
        var fn = sectionRenderers[id];
        return fn ? fn() : "";
      })
      .join("");

    var nav = order
      .map(function (id, i) {
        return (
          '<a href="#' +
          id +
          '" class="pe-nav-item' +
          (i === 0 ? " active" : "") +
          '" draggable="true" data-section-id="' +
          id +
          '">' +
          '<span class="pe-nav-grip" aria-hidden="true">' +
          gripIcon() +
          "</span>" +
          "<span>" +
          NAV_LABELS[id] +
          "</span></a>"
        );
      })
      .join("");

    document.getElementById("plan-detail-root").innerHTML =
      '<div class="plan-detail-layout">' +
      '<aside class="plan-sidebar"><nav aria-label="สารบัญ">' +
      nav +
      '</nav><p class="pe-nav-hint">ลากรายการเพื่อสลับลำดับ</p></aside>' +
      '<div class="plan-content" id="pe-sections">' +
      sectionsHtml +
      richField(state.detail.disclaimer || "", "ข้อความ disclaimer", "plan-disclaimer", 'data-pe="disclaimer" data-layer-id="disclaimer"') +
      "</div></div>";
  }

  function renderCta() {
    var p = state.detail;
    document.getElementById("plan-cta").innerHTML =
      '<div data-layer-id="cta">' +
      fieldWrap(editableInner(p.ctaTitle || "", "หัวข้อ CTA", "pe-rich pe-cta-title", "", "h2")) +
      fieldWrap(editableInner(p.ctaLead || "", "คำโปรย CTA", "pe-rich pe-cta-lead", "", "p")) +
      '<div class="cta-actions"><a href="#" class="btn btn-white">ขอใบเสนอเบี้ย</a><a href="#" class="btn btn-outline">โทร 085-292-5320</a></div></div>';
  }

  var canvasClickBound = false;
  var editGuardBound = false;
  var navReorderBound = false;
  var navDragEl = null;

  function stopAllEditing() {
    document.querySelectorAll(".pe-block-wrap.is-editing").forEach(function (wrap) {
      wrap.classList.remove("is-editing");
      wrap.querySelectorAll(".pe-editable").forEach(function (el) {
        el.setAttribute("contenteditable", "false");
      });
    });
  }

  function uploadCoverImage(callback) {
    uploadImage("plan_cover", function (path) {
      state.detail.image = path;
      if (state.card) state.card.image = path;
      if (callback) callback(path);
      renderAll();
    });
  }

  function uploadImageFile(file, spec, callback) {
    if (!file || !file.type || file.type.indexOf("image/") !== 0) {
      setStatus("กรุณาเลือกไฟล์รูปภาพ", "error");
      return;
    }
    var fd = new FormData();
    fd.append("file", file);
    fd.append("spec", spec);
    fd.append("csrf", state.csrf);
    setStatus("กำลังอัปโหลด...", "");
    fetch("api/upload.php", { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || "อัปโหลดไม่สำเร็จ");
        callback(data.path);
        setStatus("อัปโหลดแล้ว", "ok");
      })
      .catch(function (err) {
        setStatus(err.message, "error");
      });
  }

  function uploadGalleryImage() {
    uploadImage("plan_content", function (path) {
      var items = getOverviewMediaList();
      var at = (typeof state.insertAt === "number" ? state.insertAt : -1) + 1;
      items.splice(at, 0, { type: "image", src: path });
      applyOverviewMediaList(items);
      state.showGallerySlot = true;
      renderAll();
    });
  }

  function openGallerySlot(insertAfter) {
    collectFromDom();
    state.insertAt = typeof insertAfter === "number" ? insertAfter : -1;
    state.showGallerySlot = true;
    renderAll();
    var slot = document.querySelector("[data-gallery-slot]");
    if (slot) slot.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  function addTextBlock(insertAfter) {
    collectFromDom();
    var items = getOverviewMediaList();
    var at = (typeof insertAfter === "number" ? insertAfter : -1) + 1;
    items.splice(at, 0, { type: "text", html: "<p>พิมพ์ข้อความ…</p>" });
    applyOverviewMediaList(items);
    state.showGallerySlot = false;
    renderAll();
    var block = document.querySelector('[data-list="overview-media"][data-index="' + at + '"][data-block-type="text"]');
    if (block) startEdit(block);
  }

  function startEdit(wrap) {
    if (!wrap) return;
    stopAllEditing();
    if (wrap.getAttribute("data-pe-block") === "media" || wrap.hasAttribute("data-pe-media")) {
      if (wrap.hasAttribute("data-gallery-slot")) {
        uploadGalleryImage();
        return;
      }
      var blockType = wrap.getAttribute("data-block-type");
      var isGalleryImage = blockType === "image";
      var isCover = blockType === "cover" || wrap.getAttribute("data-pe-media") === "cover";
      uploadImage(isGalleryImage ? "plan_content" : "plan_cover", function (path) {
        if (isGalleryImage || isCover) {
          var items = getOverviewMediaList();
          var idx = parseInt(wrap.getAttribute("data-index"), 10);
          if (!isNaN(idx) && items[idx] && (items[idx].type === "image" || items[idx].type === "cover")) {
            items[idx].src = path;
            applyOverviewMediaList(items);
          }
        } else {
          state.detail.image = path;
          if (state.card) state.card.image = path;
        }
        renderAll();
      });
      return;
    }
    wrap.classList.add("is-editing");
    wrap.querySelectorAll(".pe-editable").forEach(function (el) {
      el.setAttribute("contenteditable", "true");
    });
    var first = wrap.querySelector(".pe-editable");
    if (first) first.focus();
  }

  function appendListItem(listName) {
    if (listName === "benefits") state.detail.benefits.push("ข้อใหม่ — คลิกเพื่อแก้ไข");
    if (listName === "specs") state.detail.specs.push(["หัวข้อ", "รายละเอียด"]);
    if (listName === "who") state.detail.whoBlocks.push({ title: "หัวข้อ", text: "เนื้อหา" });
    if (listName === "faq") state.detail.faq.push({ q: "คำถามใหม่", a: "คำตอบ" });
  }

  function insertListItem(listName, idx) {
    if (listName === "benefits") state.detail.benefits.splice(idx + 1, 0, "ข้อใหม่ — คลิกเพื่อแก้ไข");
    if (listName === "specs") state.detail.specs.splice(idx + 1, 0, ["หัวข้อ", "รายละเอียด"]);
    if (listName === "who") state.detail.whoBlocks.splice(idx + 1, 0, { title: "หัวข้อ", text: "เนื้อหา" });
    if (listName === "faq") state.detail.faq.splice(idx + 1, 0, { q: "คำถามใหม่", a: "คำตอบ" });
  }

  function swapListItem(listName, idx, target) {
    if (listName === "overview-media") {
      var items = getOverviewMediaList();
      if (target < 0 || target >= items.length || idx < 0 || idx >= items.length) return false;
      var tmpMedia = items[idx];
      items[idx] = items[target];
      items[target] = tmpMedia;
      applyOverviewMediaList(items);
      return true;
    }
    var arr;
    if (listName === "benefits") arr = state.detail.benefits;
    else if (listName === "specs") arr = state.detail.specs;
    else if (listName === "who") arr = state.detail.whoBlocks;
    else if (listName === "faq") arr = state.detail.faq;
    else return false;
    if (target < 0 || target >= arr.length || idx < 0 || idx >= arr.length) return false;
    var tmp = arr[idx];
    arr[idx] = arr[target];
    arr[target] = tmp;
    return true;
  }

  function handleBlockMove(wrap, direction) {
    collectFromDom();
    stopAllEditing();
    if (!wrap) return;
    var row = wrap.classList.contains("pe-item-row") ? wrap : wrap.closest(".pe-item-row");
    if (!row) return;
    var listName = row.getAttribute("data-list");
    var idx = parseInt(row.getAttribute("data-index"), 10);
    if (!listName || isNaN(idx)) return;

    if (listName === "overview-media") {
      var items = getOverviewMediaList();
      if (items.length === 1) {
        if (direction === "down" && !state.detail.overviewMediaAfterContent) {
          state.detail.overviewMediaAfterContent = true;
          renderAll();
          return;
        }
        if (direction === "up" && state.detail.overviewMediaAfterContent) {
          state.detail.overviewMediaAfterContent = false;
          renderAll();
          return;
        }
      } else if (items.length > 1 && idx === 0 && direction === "up") {
        return;
      }
    }

    var target = direction === "up" ? idx - 1 : idx + 1;
    if (swapListItem(listName, idx, target)) renderAll();
  }

  function handleBlockAdd(wrap) {
    collectFromDom();
    stopAllEditing();
    if (!wrap) return;

    var listEmpty = wrap.getAttribute("data-list");
    if (listEmpty && wrap.getAttribute("data-pe-block") === "list-empty") {
      appendListItem(listEmpty);
      renderAll();
      return;
    }

    var row = wrap.classList.contains("pe-item-row") ? wrap : wrap.closest(".pe-item-row");
    if (!row) return;
    var listName = row.getAttribute("data-list");
    var idx = parseInt(row.getAttribute("data-index"), 10);
    insertListItem(listName, idx);
    renderAll();
  }

  function handleBlockDelete(wrap) {
    collectFromDom();
    if (!wrap) return;
    if (wrap.hasAttribute("data-gallery-slot")) {
      state.showGallerySlot = false;
      renderAll();
      return;
    }

    var row = wrap.classList.contains("pe-item-row") ? wrap : wrap.closest(".pe-item-row");
    if (!row) return;
    var listName = row.getAttribute("data-list");
    var idx = parseInt(row.getAttribute("data-index"), 10);
    if (listName === "benefits") state.detail.benefits.splice(idx, 1);
    if (listName === "specs") state.detail.specs.splice(idx, 1);
    if (listName === "who") state.detail.whoBlocks.splice(idx, 1);
    if (listName === "faq") state.detail.faq.splice(idx, 1);
    if (listName === "overview-media") {
      var items = getOverviewMediaList();
      items.splice(idx, 1);
      applyOverviewMediaList(items);
      if (!items.some(function (b) { return b.type === "image" || b.type === "cover"; })) state.showGallerySlot = false;
    }
    renderAll();
  }

  function renderAll() {
    renderHero();
    renderDetail();
    renderCta();
    bindEvents();
    if (window.LucideIcons) LucideIcons.refresh(document.querySelector(".plan-visual-canvas"));
  }

  function cleanHtml(el) {
    if (window.PlanRichEditor && PlanRichEditor.getCleanHtml) {
      return PlanRichEditor.getCleanHtml(el);
    }
    if (!el) return "";
    var clone = el.cloneNode(true);
    clone.querySelectorAll(".pe-edit-hint, .pe-block-actions").forEach(function (n) {
      n.remove();
    });
    return clone.innerHTML.trim();
  }

  function collectFromDom() {
    var root = document.getElementById("plan-detail-root");
    var hero = document.getElementById("plan-hero-inner");

    state.detail.title = cleanHtml(hero.querySelector("[data-pe=title]"));
    state.detail.breadcrumb = plainText(cleanHtml(hero.querySelector(".breadcrumb .pe-editable")));
    state.detail.heroLead = cleanHtml(hero.querySelector("[data-pe=heroLead]"));
    state.detail.description = plainText(state.detail.heroLead);

    var pe = function (key) {
      var el = root.querySelector("[data-pe=" + key + "]");
      return el ? cleanHtml(el) : "";
    };
    state.detail.overview = pe("overview");
    var hl = root.querySelector(".plan-highlight-box .pe-editable");
    if (hl) {
      state.detail.highlight = cleanHtml(hl);
    }

    state.detail.overviewBlocks = [];
    var mediaItems = [];
    root.querySelectorAll('.plan-overview-media [data-list="overview-media"]').forEach(function (el) {
      var type = el.getAttribute("data-block-type");
      if (type === "cover" || type === "image") {
        var src = el.getAttribute("data-block-src") || "";
        if (src) mediaItems.push({ type: type, src: src });
      } else if (type === "text") {
        var ed = el.querySelector(".pe-editable");
        mediaItems.push({ type: "text", html: ed ? cleanHtml(ed) : "" });
      }
    });
    if (mediaItems.length) applyOverviewMediaList(mediaItems);

    state.detail.disclaimer = pe("disclaimer");

    var cta = document.getElementById("plan-cta");
    var ctaTitleEl = cta.querySelector(".pe-cta-title");
    var ctaLeadEl = cta.querySelector(".pe-cta-lead");
    state.detail.ctaTitle = ctaTitleEl ? cleanHtml(ctaTitleEl) : "";
    state.detail.ctaLead = ctaLeadEl ? cleanHtml(ctaLeadEl) : "";

    state.detail.benefits = [];
    root.querySelectorAll('[data-pe-list="benefits"] .pe-item-row').forEach(function (row) {
      var ed = row.querySelector(".pe-editable");
      if (ed && cleanHtml(ed)) state.detail.benefits.push(cleanHtml(ed));
    });

    state.detail.specs = [];
    root.querySelectorAll('[data-pe-list="specs"] .pe-spec-row').forEach(function (row) {
      var cells = row.querySelectorAll(".pe-editable");
      if (cells.length >= 2) {
        var a = cleanHtml(cells[0]);
        var b = cleanHtml(cells[1]);
        if (a || b) state.detail.specs.push([a, b]);
      }
    });

    state.detail.whoBlocks = [];
    root.querySelectorAll('[data-pe-list="who"] .pe-block').forEach(function (block) {
      var fields = block.querySelectorAll(".pe-editable");
      var title = fields[0] ? cleanHtml(fields[0]) : "";
      var text = fields[1] ? cleanHtml(fields[1]) : "";
      if (title || text) state.detail.whoBlocks.push({ title: title, text: text });
    });

    state.detail.faq = [];
    root.querySelectorAll('[data-pe-list="faq"] .faq-item').forEach(function (item) {
      var q = item.querySelector(".faq-item__question");
      var a = item.querySelector(".faq-item__answer .pe-editable");
      var qHtml = q ? cleanHtml(q) : "";
      if (qHtml) {
        state.detail.faq.push({ q: qHtml, a: a ? cleanHtml(a) : "" });
      }
    });

    state.detail.sectionOrder = [];
    root.querySelectorAll("#pe-sections .pe-section").forEach(function (sec) {
      state.detail.sectionOrder.push(sec.getAttribute("data-section-id"));
    });
    state.detail.sectionOrder = normalizeSectionOrder(state.detail.sectionOrder);

    if (state.card) {
      state.card.title = plainText(state.detail.title);
      state.card.desc = plainText(state.detail.heroLead);
    }
  }

  function setStatus(msg, type) {
    var el = document.getElementById("pe-status");
    el.textContent = msg;
    el.className = "pe-status" + (type ? " is-" + type : "");
  }

  function save(publish) {
    collectFromDom();
    setStatus("กำลังบันทึก...", "");
    fetch("api/plan-save.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        csrf: state.csrf,
        slug: state.slug,
        detail: state.detail,
        card: state.card,
        publish: !!publish,
      }),
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || "บันทึกไม่สำเร็จ");
        setStatus(publish ? "เผยแพร่แล้ว ✓" : "บันทึกแล้ว ✓", "ok");
      })
      .catch(function (err) {
        setStatus(err.message || "ผิดพลาด", "error");
      });
  }

  function uploadImage(spec, callback) {
    var input = document.getElementById("pe-file-input");
    input.onchange = function () {
      var file = input.files && input.files[0];
      input.value = "";
      if (!file) return;
      uploadImageFile(file, spec, callback);
    };
    input.click();
  }

  var galleryDropBound = false;

  function bindGalleryDrop() {
    var canvas = document.getElementById("pe-canvas-wrap");
    if (!canvas || galleryDropBound) return;
    galleryDropBound = true;

    canvas.addEventListener(
      "dragover",
      function (e) {
        var slot = e.target.closest("[data-gallery-slot]");
        if (!slot) return;
        e.preventDefault();
        slot.classList.add("is-dragover");
      },
      true
    );

    canvas.addEventListener(
      "dragleave",
      function (e) {
        var slot = e.target.closest("[data-gallery-slot]");
        if (slot && !slot.contains(e.relatedTarget)) slot.classList.remove("is-dragover");
      },
      true
    );

    canvas.addEventListener(
      "drop",
      function (e) {
        var slot = e.target.closest("[data-gallery-slot]");
        if (!slot) return;
        e.preventDefault();
        e.stopPropagation();
        slot.classList.remove("is-dragover");
        var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (!file) return;
        collectFromDom();
        uploadImageFile(file, "plan_content", function (path) {
          var items = getOverviewMediaList();
          var at = (typeof state.insertAt === "number" ? state.insertAt : -1) + 1;
          items.splice(at, 0, { type: "image", src: path });
          applyOverviewMediaList(items);
          state.showGallerySlot = true;
          renderAll();
        });
      },
      true
    );
  }

  function bindNavReorder() {
    var canvas = document.getElementById("pe-canvas-wrap");
    if (!canvas || navReorderBound) return;
    navReorderBound = true;

    canvas.addEventListener(
      "dragstart",
      function (e) {
        var item = e.target.closest(".pe-nav-item");
        if (!item) return;
        navDragEl = item;
        item.classList.add("is-dragging");
        e.dataTransfer.effectAllowed = "move";
        e.dataTransfer.setData("text/plain", item.getAttribute("data-section-id") || "");
      },
      true
    );

    canvas.addEventListener(
      "dragend",
      function () {
        canvas.querySelectorAll(".pe-nav-item.is-dragging, .pe-nav-item.is-drag-over").forEach(function (el) {
          el.classList.remove("is-dragging", "is-drag-over");
        });
        navDragEl = null;
      },
      true
    );

    canvas.addEventListener(
      "dragover",
      function (e) {
        var item = e.target.closest(".pe-nav-item");
        if (!item || item === navDragEl) return;
        e.preventDefault();
        canvas.querySelectorAll(".pe-nav-item.is-drag-over").forEach(function (el) {
          el.classList.remove("is-drag-over");
        });
        item.classList.add("is-drag-over");
      },
      true
    );

    canvas.addEventListener(
      "dragleave",
      function (e) {
        var item = e.target.closest(".pe-nav-item");
        if (item) item.classList.remove("is-drag-over");
      },
      true
    );

    canvas.addEventListener(
      "drop",
      function (e) {
        var target = e.target.closest(".pe-nav-item");
        if (!target || !navDragEl || target === navDragEl) return;
        e.preventDefault();
        stopAllEditing();
        collectFromDom();

        var rect = target.getBoundingClientRect();
        var after = e.clientY > rect.top + rect.height / 2;
        if (after) {
          target.parentNode.insertBefore(navDragEl, target.nextSibling);
        } else {
          target.parentNode.insertBefore(navDragEl, target);
        }

        syncSectionOrderFromNav();
        reorderSectionsInDom();

        canvas.querySelectorAll(".pe-nav-item.is-drag-over").forEach(function (el) {
          el.classList.remove("is-drag-over");
        });
      },
      true
    );

    canvas.addEventListener(
      "click",
      function (e) {
        var item = e.target.closest(".pe-nav-item");
        if (!item) return;
        e.preventDefault();
      },
      true
    );
  }

  function bindEvents() {
    var canvas = document.getElementById("pe-canvas-wrap");

    bindNavReorder();
    bindGalleryDrop();

    if (!canvasClickBound) {
      canvasClickBound = true;
      canvas.addEventListener("click", function (e) {
        if (e.target.closest(".pe-content-add-btn")) {
          e.preventDefault();
          e.stopPropagation();
          var bar = e.target.closest(".pe-content-add-bar");
          var insertAfter = bar ? parseInt(bar.getAttribute("data-insert-after"), 10) : -1;
          if (e.target.closest('[data-add-type="image"]')) {
            openGallerySlot(insertAfter);
          } else if (e.target.closest('[data-add-type="text"]')) {
            addTextBlock(insertAfter);
          }
          return;
        }
        if (e.target.closest(".pe-gallery-pick-btn")) {
          e.preventDefault();
          e.stopPropagation();
          collectFromDom();
          uploadGalleryImage();
          return;
        }
        if (
          e.target.closest("[data-gallery-slot]") &&
          !e.target.closest(".pe-gallery-pick-btn") &&
          !e.target.closest(".pe-block-actions")
        ) {
          e.preventDefault();
          e.stopPropagation();
          collectFromDom();
          uploadGalleryImage();
          return;
        }
        if (e.target.closest(".pe-block-btn--up")) {
          e.preventDefault();
          e.stopPropagation();
          handleBlockMove(e.target.closest(".pe-block-wrap"), "up");
          return;
        }
        if (e.target.closest(".pe-block-btn--down")) {
          e.preventDefault();
          e.stopPropagation();
          handleBlockMove(e.target.closest(".pe-block-wrap"), "down");
          return;
        }
        if (e.target.closest(".pe-block-btn--edit")) {
          e.preventDefault();
          e.stopPropagation();
          startEdit(e.target.closest(".pe-block-wrap"));
          return;
        }
        if (e.target.closest(".pe-block-btn--add")) {
          e.preventDefault();
          e.stopPropagation();
          handleBlockAdd(e.target.closest(".pe-block-wrap"));
          return;
        }
        if (e.target.closest(".pe-block-btn--delete")) {
          e.preventDefault();
          e.stopPropagation();
          var delWrap = e.target.closest(".pe-block-wrap");
          if (delWrap && delWrap.hasAttribute("data-gallery-slot")) {
            handleBlockDelete(delWrap);
            return;
          }
          if (window.confirm("ลบส่วนนี้?")) {
            handleBlockDelete(delWrap);
          }
        }
      });
    }

    if (!editGuardBound) {
      editGuardBound = true;
      document.addEventListener(
        "mousedown",
        function (e) {
          var ed = e.target.closest(".pe-editable");
          if (ed && ed.getAttribute("contenteditable") !== "true") {
            e.preventDefault();
          }
        },
        true
      );
      document.addEventListener(
        "click",
        function (e) {
          if (e.target.closest(".pe-block-btn") || e.target.closest(".pe-rich-toolbar")) return;
          if (e.target.closest(".pe-block-wrap.is-editing")) return;
          stopAllEditing();
        },
        true
      );
    }

    if (window.PlanRichEditor) {
      PlanRichEditor.init({
        container: document,
        uploadImage: uploadImage,
      });
    }
  }

  document.getElementById("pe-save").addEventListener("click", function () {
    save(false);
  });
  document.getElementById("pe-publish").addEventListener("click", function () {
    save(true);
  });

  document.addEventListener("keydown", function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === "s") {
      e.preventDefault();
      save(false);
    }
  });

  ensureOverviewMediaState();
  renderAll();
})();
