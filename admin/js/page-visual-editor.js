(function () {
  var boot = window.PAGE_VISUAL_DATA;
  var R = window.PageBlockRender;
  var B = window.PageBlockBuilder;
  if (!boot || !R || !B) return;

  var isPlan = boot.editorKind === "plan";
  var isContent = boot.editorKind === "content";
  var catalog = boot.sectionCatalog || {};
  var contentItem = boot.item || {};

  function defaultPageData() {
    return {
      hero: { isVisible: true, visible: true, breadcrumb: (boot.meta && boot.meta.breadcrumb) || "", title: (boot.meta && boot.meta.label) || "", lead: "" },
      sections: [],
      cta: { isVisible: true, visible: true, title: "", lead: "", buttons: [] },
    };
  }

  function normalizePageData(raw) {
    if (isContent) {
      var sections = (raw && raw.sections) || [];
      return {
        sections: sections.map(function (sec, i) {
          if (sec.visible !== undefined && sec.isVisible === undefined) sec.isVisible = sec.visible;
          sec.isVisible = sec.isVisible !== false;
          sec.visible = sec.isVisible;
          sec.sortOrder = i;
          return sec;
        }),
      };
    }
    var base = defaultPageData();
    if (!raw || typeof raw !== "object") return base;
    var copy = JSON.parse(JSON.stringify(raw));
    copy.hero = Object.assign({}, base.hero, copy.hero || {});
    copy.hero.isVisible = copy.hero.isVisible !== false && copy.hero.visible !== false;
    copy.hero.visible = copy.hero.isVisible;
    copy.sections = (copy.sections || []).map(function (sec, i) {
      if (sec.visible !== undefined && sec.isVisible === undefined) sec.isVisible = sec.visible;
      sec.isVisible = sec.isVisible !== false;
      sec.visible = sec.isVisible;
      sec.sortOrder = i;
      return sec;
    });
    copy.cta = Object.assign({}, base.cta, copy.cta || {});
    copy.cta.isVisible = copy.cta.isVisible !== false && copy.cta.visible !== false;
    copy.cta.visible = copy.cta.isVisible;
    if (!Array.isArray(copy.cta.buttons)) copy.cta.buttons = [];
    if (copy.disclaimer != null) copy.disclaimer = String(copy.disclaimer);
    else if (raw.disclaimer != null) copy.disclaimer = String(raw.disclaimer);
    return copy;
  }

  var state = {
    page: boot.page,
    slug: boot.slug || boot.page,
    csrf: boot.csrf,
    pageData: normalizePageData(boot.pageData),
    agent: boot.agent || {},
    brand: boot.brand || {},
    card: boot.card || null,
    selected: null,
    draft: null,
    dragSectionIdx: null,
    dragBlockType: null,
    activeTab: "tools",
  };

  var TAB_PANELS = ["tools", "layers", "edit"];

  function switchTab(tab) {
    if (TAB_PANELS.indexOf(tab) < 0) tab = "tools";
    state.activeTab = tab;
    document.querySelectorAll("[data-pe-tab]").forEach(function (btn) {
      var on = btn.getAttribute("data-pe-tab") === tab;
      btn.classList.toggle("is-active", on);
      btn.setAttribute("aria-selected", on ? "true" : "false");
    });
    TAB_PANELS.forEach(function (id) {
      var panel = document.getElementById("pe-tab-" + id);
      if (!panel) return;
      var on = id === tab;
      panel.classList.toggle("is-active", on);
      panel.hidden = !on;
    });
  }

  function endDrag() {
    state.dragBlockType = null;
    state.dragSectionIdx = null;
    document.body.classList.remove("pe-is-dragging-block", "pe-is-dragging-section");
    document.querySelectorAll(".pe-drop-zone.is-over, .pve-rail-item.is-dragging, .pve-rail-item.is-drag-over").forEach(function (el) {
      el.classList.remove("is-over", "is-dragging", "is-drag-over");
    });
  }

  function getDragBlockType(e) {
    return (
      (e && e.dataTransfer && e.dataTransfer.getData("application/x-pe-block-type")) ||
      (e && e.dataTransfer && e.dataTransfer.getData("text/plain")) ||
      state.dragBlockType ||
      ""
    );
  }

  function highlightDropZoneAt(clientY, scope) {
    scope = scope || document;
    var zones = scope.querySelectorAll(".pe-drop-zone");
    var best = null;
    var bestDist = Infinity;
    zones.forEach(function (z) {
      var r = z.getBoundingClientRect();
      if (r.height < 1 && r.width < 1) return;
      var mid = r.top + r.height / 2;
      var dist = Math.abs(clientY - mid);
      if (dist < bestDist) {
        bestDist = dist;
        best = z;
      }
    });
    document.querySelectorAll(".pe-drop-zone.is-over").forEach(function (el) {
      if (el !== best) el.classList.remove("is-over");
    });
    if (best) best.classList.add("is-over");
    return best;
  }

  function resolveDropIndex(e) {
    var zone = e.target.closest(".pe-drop-zone");
    if (!zone) zone = highlightDropZoneAt(e.clientY);
    if (zone) {
      var idx = parseInt(zone.getAttribute("data-pe-drop-index"), 10);
      if (!isNaN(idx)) return idx;
    }
    var block = e.target.closest(".pe-preview-block[data-pe-target=section]");
    if (block) {
      var secIdx = parseInt(block.getAttribute("data-pe-index"), 10);
      if (!isNaN(secIdx)) {
        var rect = block.getBoundingClientRect();
        return e.clientY > rect.top + rect.height / 2 ? secIdx + 1 : secIdx;
      }
    }
    return state.pageData.sections.length;
  }

  function handleBlockDrop(e) {
    var blockType = getDragBlockType(e);
    var fromRaw = e.dataTransfer ? e.dataTransfer.getData("application/x-pe-section-from") : "";
    var fromIdx = fromRaw !== "" ? parseInt(fromRaw, 10) : state.dragSectionIdx;

    if (blockType) {
      var at = insertSectionAt(blockType, resolveDropIndex(e));
      openPanel("section", at, { tab: "edit" });
      endDrag();
      return true;
    }
    if (fromIdx !== null && !isNaN(fromIdx)) {
      var to = resolveDropIndex(e);
      if (fromIdx !== to) reorderSection(fromIdx, to);
      endDrag();
      return true;
    }
    return false;
  }

  function renderCtx() {
    return {
      base: "../",
      agent: state.agent,
      meta: boot.meta || {},
      preview: true,
      isPlan: isPlan,
      imgSrc: function (p) { return R.imgSrc(p, "../"); },
    };
  }

  function planPreviewMod(index) {
    if (!isPlan || index == null) return "";
    var sec = (state.pageData.sections || [])[index];
    if (!sec || !sec.type) return "";
    return " pe-preview-block--plan pe-preview-block--plan-" + sec.type;
  }

  function previewBlockWrap(target, index, label, innerHtml, visible) {
    var sel = state.selected && state.selected.target === target && state.selected.index === index;
    var editIcon = window.LucideIcons ? LucideIcons.editor("edit", 16) : "✎";
    var deleteBtn = "";
    if (target === "section" && (isContent || state.pageData.sections.length > 1)) {
      var delIcon = window.LucideIcons ? LucideIcons.editor("del", 16) : "×";
      deleteBtn = '<button type="button" class="pe-preview-delete-btn" data-pe-delete title="ลบ" aria-label="ลบ">' + delIcon + "</button>";
    }
    var planMod = target === "section" ? planPreviewMod(index) : "";
    return (
      '<div class="pe-preview-block' + planMod + (visible === false ? " is-hidden-preview" : "") + (sel ? " is-selected" : "") + '" data-pe-target="' + R.esc(target) + '"' +
      (index != null ? ' data-pe-index="' + index + '"' : "") + ">" +
      '<div class="pe-preview-block-toolbar"><span class="pe-preview-block-label">' + R.esc(label) + '</span>' +
      '<button type="button" class="pe-preview-edit-btn" data-pe-edit title="แก้ไข" aria-label="แก้ไข">' + editIcon + "</button>" +
      deleteBtn +
      "</div>" +
      '<div class="pe-preview-block-inner">' + innerHtml + "</div></div>"
    );
  }

  function dropZoneHtml(index) {
    return '<div class="pe-drop-zone" data-pe-drop-index="' + index + '" aria-hidden="true"><span class="pe-drop-zone-line"></span></div>';
  }

  function renderContentHero(item) {
    var listUrl = boot.listUrl || "../products.html";
    var listLabel = boot.listLabel || "บทความ";
    return (
      '<header class="page-hero"><div class="page-hero-inner">' +
      '<p class="breadcrumb"><a href="../index.html">หน้าหลัก</a> / <a href="' +
      R.esc(listUrl) +
      '">' +
      R.esc(listLabel) +
      "</a> / " +
      R.esc(item.category || "") +
      "</p>" +
      "<h1>" +
      R.esc(item.title || "") +
      "</h1>" +
      '<p class="page-hero-lead">' +
      R.esc(item.description || "") +
      "</p></div></header>"
    );
  }

  function renderContentArticleHeader(item, ctx) {
    var image = item.image || "";
    var cover =
      image !== ""
        ? '<div class="article-detail-cover"><img src="' +
          R.esc(ctx.imgSrc(image)) +
          '" alt="' +
          R.esc(item.title || "") +
          '" width="1200" height="675" loading="eager" decoding="async"></div>'
        : "";
    var date = item.datePublished || "";
    var stats = "";
    if (item.views) {
      stats =
        '<span class="article-detail-stats">' +
        Number(item.views).toLocaleString("th-TH") +
        " views" +
        (item.shares ? " · " + item.shares + " shares" : "") +
        "</span>";
    }
    return (
      '<header class="article-detail-header">' +
      cover +
      '<div class="article-detail-meta">' +
      '<span class="article-detail-category">' +
      R.esc(item.category || "") +
      "</span>" +
      (date ? '<time class="article-detail-date" datetime="' + R.esc(date) + '">' + R.esc(date) + "</time>" : "") +
      stats +
      "</div></header>"
    );
  }

  function renderPlanHero(hero) {
    var bc = hero.breadcrumb || hero.title || "";
    return (
      '<header class="page-hero page-hero--plan"><div class="page-hero-inner">' +
      '<div class="breadcrumb"><a href="../plans.html">แผนประกัน</a> / ' + R.esc(bc) + "</div>" +
      '<span class="page-hero-eyebrow">แผนประกันไทยประกันชีวิต</span>' +
      "<h1>" + R.esc(hero.title || "") + "</h1>" +
      '<p class="page-hero-lead">' + R.esc(hero.lead || hero.description || "") + "</p></div></header>"
    );
  }

  function renderPreview() {
    var frame = document.getElementById("pe-preview-frame");
    if (!frame) return;
    var data = state.pageData;
    if (!data.hero || typeof data.hero !== "object") {
      data.hero = Object.assign({}, defaultPageData().hero);
    }
    if (!data.cta || typeof data.cta !== "object") {
      data.cta = Object.assign({}, defaultPageData().cta);
    }
    if (!Array.isArray(data.sections)) {
      data.sections = [];
    }
    var ctx = renderCtx();
    var brand = state.brand;

    try {
    var headerHtml =
      '<header class="site-header pe-landing-preview-header" aria-hidden="true"><div class="header-inner"><span class="brand">' +
      '<img src="../' + R.esc(brand.logo || "images/logo/LOGO-THAILIFE.png") + '" alt="" class="brand-logo" width="46" height="46">' +
      '<span class="brand-text"><span class="brand-name">' + R.esc(brand.name || "Max Thai Life") + '</span>' +
      '<span class="brand-sub">' + R.esc(brand.sub || "Digital Agent Office") + "</span></span></span></div></header>";

    var heroInner = isPlan ? renderPlanHero(data.hero) : R.renderHero(data.hero, ctx);
    var sectionsHtml = dropZoneHtml(0);
    (data.sections || []).forEach(function (sec, i) {
      var label = B.blockLabel(sec.type, catalog) + (sec.title ? " — " + String(sec.title).slice(0, 24) : "");
      var blockHtml = isPlan ? R.renderPlanBlock(sec, ctx) : R.renderBlock(sec, ctx);
      sectionsHtml += previewBlockWrap("section", i, label, blockHtml, sec.isVisible !== false);
      sectionsHtml += dropZoneHtml(i + 1);
    });

    var ctaInner = R.renderCta(data.cta, ctx);

    if (isPlan) {
      var planBody =
        '<section class="section pe-plan-preview-body"><div class="section-inner">' +
        '<div class="plan-content plan-content--preview" id="landing-root">' + sectionsHtml +
        (data.disclaimer ? '<div class="plan-disclaimer">' + R.esc(data.disclaimer) + "</div>" : "") +
        "</div></div></section>";
      frame.innerHTML =
        headerHtml +
        previewBlockWrap("hero", null, "Hero แผน", heroInner, data.hero.isVisible !== false) +
        planBody +
        previewBlockWrap("cta", null, "ปุ่ม CTA", ctaInner, data.cta.isVisible !== false);
    } else if (isContent) {
      var articleBody =
        '<section class="section pe-content-preview-body"><div class="section-inner">' +
        '<div class="article-detail-layout">' +
        '<article class="article-detail article-detail-main">' +
        renderContentArticleHeader(contentItem, ctx) +
        '<div class="article-prose pe-content-prose" id="landing-root">' +
        sectionsHtml +
        "</div></article></div></div></section>";
      frame.innerHTML = headerHtml + renderContentHero(contentItem) + articleBody;
    } else {
      frame.innerHTML =
        headerHtml +
        previewBlockWrap("hero", null, "Hero Banner", heroInner, data.hero.isVisible !== false) +
        '<div id="landing-root">' + sectionsHtml + "</div>" +
        previewBlockWrap("cta", null, "ปุ่ม CTA", ctaInner, data.cta.isVisible !== false);
    }

    if (window.initSectionHeaders) initSectionHeaders(frame);
    if (window.LucideIcons) LucideIcons.refresh(frame);
    hydrateClaimWidgetPreview(frame);
    bindDropZones();
    } catch (err) {
      console.error("[page-visual-editor] renderPreview failed", err);
      frame.innerHTML =
        '<div class="pe-preview-error"><p>โหลดตัวอย่างหน้าไม่สำเร็จ</p><p class="pe-preview-error-detail">' +
        R.esc(err && err.message ? err.message : String(err)) +
        "</p></div>";
      setStatus("โหลด Preview ไม่สำเร็จ", "error");
    }
  }

  function hydrateClaimWidgetPreview(frame) {
    if (!frame || !window.CLAIM_REVIEWS_DETAIL || !window.CLAIM_REVIEWS_LIST) return;
    var grid = frame.querySelector("#claim-card-grid-preview, #claim-card-grid");
    if (!grid) return;

    var entries = window.CLAIM_REVIEWS_LIST.map(function (slug) {
      return window.CLAIM_REVIEWS_DETAIL[slug];
    }).filter(Boolean);
    if (!entries.length) return;

    grid.innerHTML = entries
      .map(function (entry) {
        var img = R.imgSrc(entry.image, "../");
        return (
          "<li><article class=\"product-card\">" +
          '<span class="product-card-media" aria-hidden="true">' +
          '<img src="' + R.esc(img) + '" alt="' + R.esc(entry.title || "") + '" loading="lazy" decoding="async">' +
          "</span>" +
          '<div class="product-card-body">' +
          '<p class="product-card-meta">' + R.esc(entry.category || "") + "</p>" +
          "<h3>" + R.esc(entry.title || "") + "</h3>" +
          '<p class="product-card-excerpt">' + R.esc(entry.description || "") + "</p>" +
          '<span class="product-card-link">อ่านต่อ →</span>' +
          "</div></article></li>"
        );
      })
      .join("");
  }

  function renderTools() {
    var el = document.getElementById("pe-tools-palette");
    if (!el) return;
    el.innerHTML = B.renderToolsPalette(catalog, isPlan ? B.PLAN_TOOL_BLOCKS : []);
    if (window.LucideIcons) LucideIcons.refresh(el);
  }

  function renderRail() {
    var rail = document.getElementById("pe-section-rail");
    if (!rail) return;
    rail.innerHTML = B.renderSectionRail(state.pageData, state.selected, catalog);
    if (window.LucideIcons) LucideIcons.refresh(rail);
    bindDropZones();
  }

  function formCtx() {
    var sel = state.selected || {};
    return {
      target: sel.target,
      index: sel.index,
      totalSections: (state.pageData.sections || []).length,
      canDelete: sel.target === "section" && (isContent || state.pageData.sections.length > 1),
      isPlan: isPlan,
      imgSrc: function (p) { return R.imgSrc(p, "../"); },
    };
  }

  function renderPanel() {
    var empty = document.getElementById("pe-edit-panel-empty");
    var form = document.getElementById("pe-edit-form");
    var foot = document.getElementById("pe-edit-panel-foot");
    var formLabel = document.getElementById("pe-edit-form-label");
    var panel = document.getElementById("pe-edit-panel");
    var editTabBtn = document.getElementById("pe-tab-btn-edit");

    panel.classList.add("is-open");

    if (editTabBtn) {
      editTabBtn.classList.toggle("has-selection", !!(state.selected && state.draft));
    }

    if (!state.selected || !state.draft) {
      empty.hidden = false;
      form.hidden = true;
      foot.hidden = true;
      if (formLabel) {
        formLabel.hidden = true;
        formLabel.textContent = "";
      }
      return;
    }

    empty.hidden = true;
    form.hidden = false;
    foot.hidden = false;

    var sel = state.selected;
    var ctx = formCtx();
    var sectionName;
    if (sel.target === "hero") sectionName = isPlan ? "Hero แผน" : "Hero Banner";
    else if (sel.target === "cta") sectionName = "ปุ่ม CTA";
    else sectionName = B.blockLabel(state.draft.type, catalog);

    if (formLabel) {
      formLabel.hidden = false;
      formLabel.textContent = "แก้ไข · " + sectionName;
    }

    if (sel.target === "hero") {
      form.innerHTML = B.buildHeroForm(state.draft, ctx);
    } else if (sel.target === "cta") {
      form.innerHTML = B.buildCtaForm(state.draft, ctx);
    } else {
      form.innerHTML = B.buildForm(state.draft, ctx);
    }
    if (window.LucideIcons) LucideIcons.refresh(form);
  }

  function openPanel(target, index, opts) {
    opts = opts || {};
    state.selected = { target: target, index: index };
    if (target === "hero") state.draft = JSON.parse(JSON.stringify(state.pageData.hero));
    else if (target === "cta") state.draft = JSON.parse(JSON.stringify(state.pageData.cta));
    else state.draft = JSON.parse(JSON.stringify(state.pageData.sections[index]));
    if (state.draft && state.draft.type === "featured" && B.ensureFeaturedBullets) {
      B.ensureFeaturedBullets(state.draft);
    }
    var panel = document.getElementById("pe-edit-panel");
    var bd = document.getElementById("pe-edit-backdrop");
    if (panel) panel.classList.add("is-open");
    if (bd && window.matchMedia("(max-width: 1100px)").matches) {
      bd.hidden = false;
      bd.classList.add("is-visible");
    }
    switchTab(opts.tab != null ? opts.tab : "edit");
    renderRail();
    renderPanel();
    renderPreview();
  }

  function closePanel(revert) {
    if (!revert) applyDraftToState();
    state.selected = null;
    state.draft = null;
    switchTab("layers");
    renderRail();
    renderPanel();
    renderPreview();
  }

  function copyDraftToPageData() {
    if (!state.selected || !state.draft) return;
    var d = JSON.parse(JSON.stringify(state.draft));
    if (state.selected.target === "hero") {
      state.pageData.hero = d;
      state.pageData.title = d.title;
      state.pageData.lead = d.lead || d.description;
    } else if (state.selected.target === "cta") {
      state.pageData.cta = d;
    } else {
      state.pageData.sections[state.selected.index] = d;
    }
  }

  function applyDraftToState() {
    var form = document.getElementById("pe-edit-form");
    if (form && state.draft) B.readForm(form, state.draft, state.selected);
    copyDraftToPageData();
  }

  var previewSyncTimer = null;
  function syncPreviewFromForm() {
    var scrollEl = document.getElementById("pe-preview-scroll");
    var scrollTop = scrollEl ? scrollEl.scrollTop : 0;
    applyDraftToState();
    renderPreview();
    renderRail();
    if (scrollEl) scrollEl.scrollTop = scrollTop;
  }

  function schedulePreviewSync() {
    clearTimeout(previewSyncTimer);
    previewSyncTimer = setTimeout(syncPreviewFromForm, 80);
  }

  function uploadImageFile(file, spec, cb) {
    if (!file) return;
    var fd = new FormData();
    fd.append("file", file);
    fd.append("spec", spec || uploadSpecDefault());
    fd.append("csrf", state.csrf);
    fetch("api/upload.php", { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || "อัปโหลดไม่สำเร็จ");
        cb(data.path);
      })
      .catch(function (e) {
        setStatus(e.message, "error");
      });
  }

  function uploadImage(spec, accept, cb) {
    if (typeof accept === "function") {
      cb = accept;
      accept = null;
      spec = "media_library";
    } else if (typeof spec === "function") {
      cb = spec;
      accept = null;
      spec = "media_library";
    }
    var input = document.getElementById("pe-file-input");
    input.accept =
      accept ||
      (spec === "video_library"
        ? "video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogg,.mov"
        : "image/*");
    input.onchange = function () {
      var file = input.files && input.files[0];
      input.value = "";
      if (!file) return;
      uploadImageFile(file, spec || uploadSpecDefault(), cb);
    };
    input.click();
  }

  function applyUploadedMedia(form, prefix, path) {
    applyDraftToState();
    var srcKey = prefix + "_src";
    var el = form.querySelector('[data-pve-field="' + srcKey + '"]');
    if (el) el.value = path;
    if (prefix === "main") {
      state.draft.image = state.draft.image || { src: "", alt: "" };
      state.draft.image.src = path;
    }
    if (prefix === "video") {
      state.draft.videoSrc = path;
    }
    if (prefix.indexOf("block_") === 0) {
      var bi = parseInt(prefix.replace("block_", ""), 10);
      if (state.draft.blocks && state.draft.blocks[bi]) state.draft.blocks[bi].src = path;
    }
    if (prefix.indexOf("item_") === 0) {
      var ii = parseInt(prefix.replace("item_", ""), 10);
      if (state.draft.items && state.draft.items[ii]) {
        state.draft.items[ii].image = state.draft.items[ii].image || {};
        state.draft.items[ii].image.src = path;
      }
    }
    var preview = form.querySelector('[data-pve-image="' + prefix + '"] [data-pve-image-preview]');
    if (preview) preview.innerHTML = '<img src="' + R.imgSrc(path, "../") + '" alt="">';
    var videoPreview = form.querySelector('[data-pve-video="' + prefix + '"] [data-pve-video-preview]');
    if (videoPreview && prefix === "video") {
      videoPreview.innerHTML = '<video src="' + R.imgSrc(path, "../") + '" controls muted preload="metadata"></video>';
    }
    syncPreviewFromForm();
  }

  function isFileDrag(e) {
    if (!e.dataTransfer || !e.dataTransfer.types) return false;
    return Array.prototype.indexOf.call(e.dataTransfer.types, "Files") >= 0;
  }

  function isImageFile(file) {
    if (!file) return false;
    if (file.type && file.type.indexOf("image/") === 0) return true;
    return /\.(jpe?g|png|gif|webp|svg|bmp|avif)$/i.test(file.name || "");
  }

  function setStatus(msg, type) {
    var el = document.getElementById("pe-status");
    if (!el) return;
    el.textContent = msg;
    el.className = "pe-status" + (type ? " is-" + type : "");
  }

  function refreshViewPageLink() {
    if ((!isPlan && !isContent) || !boot.previewUrl) return;
    var el = document.getElementById("pe-view-page");
    if (!el) return;
    var base = boot.previewUrl.split("?")[0];
    el.href = base + "?v=" + Date.now();
  }

  function uploadSpecDefault() {
    if (isContent) return "media_library";
    if (isPlan) return "plan_content";
    return "media_library";
  }

  function save(publish) {
    if (state.draft) applyDraftToState();
    setStatus("กำลังบันทึก...", "");
    var url = isPlan ? "api/plan-save.php" : isContent ? "api/content-save.php" : "api/page-save.php";
    var body;
    if (isPlan) {
      body = { slug: state.slug, csrf: state.csrf, pageData: state.pageData, card: state.card, publish: !!publish };
    } else if (isContent) {
      body = {
        type: boot.contentType,
        slug: state.slug,
        csrf: state.csrf,
        visual: true,
        pageData: state.pageData,
        publish: !!publish,
      };
    } else {
      body = { page: state.page, csrf: state.csrf, pageData: state.pageData, publish: !!publish };
    }
    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (!data.ok) throw new Error(data.error || "บันทึกไม่สำเร็จ");
        if (isPlan) {
          refreshViewPageLink();
          setStatus(publish ? "เผยแพร่แล้ว — หน้าเว็บอัปเดตแล้ว ✓" : "บันทึกแล้ว — กด「ดูหน้า」เพื่อตรวจสอบ ✓", "ok");
        } else if (isContent) {
          refreshViewPageLink();
          setStatus(publish ? "เผยแพร่แล้ว — หน้าเว็บอัปเดตแล้ว ✓" : "บันทึกแล้ว — กด「ดูหน้า」เพื่อตรวจสอบ ✓", "ok");
        } else {
          setStatus(publish ? "เผยแพร่แล้ว ✓" : "บันทึกแล้ว ✓", "ok");
        }
      })
      .catch(function (e) { setStatus(e.message, "error"); });
  }

  function insertSectionAt(type, index) {
    if (state.draft) applyDraftToState();
    var idx = Math.max(0, Math.min(index, state.pageData.sections.length));
    state.pageData.sections.splice(idx, 0, B.defaultBlock(type));
    return idx;
  }

  function addSectionAtEnd(type) {
    var idx = insertSectionAt(type || "text", state.pageData.sections.length);
    openPanel("section", idx, { tab: "edit" });
  }

  function addSection() {
    addSectionAtEnd(document.getElementById("pe-add-section-type") ? document.getElementById("pe-add-section-type").value : "text");
  }

  function duplicateSection(index) {
    applyDraftToState();
    var copy = JSON.parse(JSON.stringify(state.pageData.sections[index]));
    copy.id = "b" + Date.now().toString(36);
    state.pageData.sections.splice(index + 1, 0, copy);
    openPanel("section", index + 1);
  }

  function deleteSection(index) {
    if (!isContent && state.pageData.sections.length <= 1) return;
    if (!window.confirm("ลบ Section นี้?")) return;
    applyDraftToState();
    state.pageData.sections.splice(index, 1);
    state.selected = null;
    state.draft = null;
    switchTab("layers");
    renderRail();
    renderPanel();
    renderPreview();
  }

  function toggleSectionVisibility(target, index) {
    if (target === "hero") {
      state.pageData.hero.isVisible = !state.pageData.hero.isVisible;
      state.pageData.hero.visible = state.pageData.hero.isVisible;
    } else if (target === "cta") {
      state.pageData.cta.isVisible = !state.pageData.cta.isVisible;
      state.pageData.cta.visible = state.pageData.cta.isVisible;
    } else {
      var sec = state.pageData.sections[index];
      sec.isVisible = !sec.isVisible;
      sec.visible = sec.isVisible;
    }
    if (state.draft && state.selected && state.selected.target === target && state.selected.index === index) {
      state.draft.isVisible = target === "section" ? state.pageData.sections[index].isVisible : state.pageData[target].isVisible;
      state.draft.visible = state.draft.isVisible;
      renderPanel();
    }
    renderRail();
    renderPreview();
  }

  function reorderSection(from, to) {
    if (from === to || from < 0 || to < 0) return;
    applyDraftToState();
    var arr = state.pageData.sections;
    if (to >= arr.length) return;
    var item = arr.splice(from, 1)[0];
    arr.splice(to, 0, item);
    if (state.selected && state.selected.target === "section") {
      if (state.selected.index === from) state.selected.index = to;
      else if (from < state.selected.index && to >= state.selected.index) state.selected.index--;
      else if (from > state.selected.index && to <= state.selected.index) state.selected.index++;
      state.draft = JSON.parse(JSON.stringify(arr[state.selected.index]));
    }
    renderRail();
    renderPanel();
    renderPreview();
  }

  function reorderDraftItems(from, to) {
    applyDraftToState();
    var arr = B.getRepeaterArray(state.draft, state.selected);
    if (!arr || from === to) return;
    var moved = arr.splice(from, 1)[0];
    arr.splice(to, 0, moved);
    copyDraftToPageData();
    renderPanel();
    syncPreviewFromForm();
  }

  function bindFormEvents() {
    var form = document.getElementById("pe-edit-form");
    if (!form || form.dataset.bound) return;
    form.dataset.bound = "1";

    form.addEventListener("input", function (e) {
      if (e.target.closest("[data-pve-field], [data-pve-block-type]")) schedulePreviewSync();
    });

    form.addEventListener("change", function (e) {
      if (e.target.matches("[data-pve-block-type]")) {
        applyDraftToState();
        var idx = parseInt(e.target.getAttribute("data-pve-block-type"), 10);
        if (state.draft.blocks && state.draft.blocks[idx]) {
          state.draft.blocks[idx].type = e.target.value;
        }
        copyDraftToPageData();
        renderPanel();
        renderPreview();
        return;
      }
      if (e.target.matches("[data-pve-field], .pve-select")) {
        if (e.target.matches('[data-pve-field="showIcon"]')) {
          var picker = form.querySelector("[data-pve-icon-picker]");
          if (picker) picker.hidden = !e.target.checked;
        }
        schedulePreviewSync();
      }
    });

    form.addEventListener("click", function (e) {
      if (e.target.closest("[data-pve-icon-pick]")) {
        e.preventDefault();
        var btn = e.target.closest("[data-pve-icon-pick]");
        var icon = btn.getAttribute("data-pve-icon-pick");
        var iconInput = form.querySelector('[data-pve-field="icon"]');
        if (iconInput) iconInput.value = icon;
        form.querySelectorAll("[data-pve-icon-pick]").forEach(function (el) {
          var on = el === btn;
          el.classList.toggle("is-selected", on);
          el.setAttribute("aria-selected", on ? "true" : "false");
        });
        schedulePreviewSync();
        return;
      }
      if (e.target.closest("[data-pve-delete-section]")) {
        e.preventDefault();
        if (state.selected && state.selected.target === "section") deleteSection(state.selected.index);
        return;
      }
      if (e.target.closest("[data-pve-order]")) {
        e.preventDefault();
        var dir = e.target.closest("[data-pve-order]").getAttribute("data-pve-order");
        if (!state.selected || state.selected.target !== "section") return;
        var idx = state.selected.index;
        reorderSection(idx, dir === "up" ? idx - 1 : idx + 1);
        return;
      }
      if (e.target.closest("[data-pve-repeater-add]")) {
        e.preventDefault();
        applyDraftToState();
        var arr = B.getRepeaterArray(state.draft, state.selected);
        if (arr) {
          arr.push(B.defaultRepeaterItem(state.draft, state.selected));
          copyDraftToPageData();
          renderPanel();
          syncPreviewFromForm();
        }
        return;
      }
      if (e.target.closest("[data-pve-repeater-del]")) {
        e.preventDefault();
        var item = e.target.closest("[data-pve-repeater-item]");
        if (!item) return;
        var idx = parseInt(item.getAttribute("data-index"), 10);
        applyDraftToState();
        var arr2 = B.getRepeaterArray(state.draft, state.selected);
        if (arr2 && arr2.length > 1) {
          arr2.splice(idx, 1);
          copyDraftToPageData();
          renderPanel();
          syncPreviewFromForm();
        }
        return;
      }
      if (e.target.closest("[data-pve-repeater-edit], [data-pve-repeater-toggle]")) {
        e.preventDefault();
        var editItem = e.target.closest("[data-pve-repeater-item]");
        if (editItem && !e.target.closest("[data-pve-repeater-del], [data-pve-repeater-drag]")) {
          editItem.classList.toggle("is-expanded");
        }
        return;
      }
      if (e.target.closest("[data-pve-upload]")) {
        e.preventDefault();
        var uploadBtn = e.target.closest("[data-pve-upload]");
        var prefix = uploadBtn.getAttribute("data-pve-upload");
        var spec = uploadBtn.getAttribute("data-pve-upload-spec") || uploadSpecDefault();
        var accept = uploadBtn.getAttribute("data-pve-upload-accept");
        uploadImage(spec, accept, function (path) {
          applyUploadedMedia(form, prefix, path);
        });
        return;
      }
      if (e.target.closest("[data-pve-image-drop], [data-pve-video-drop]")) {
        e.preventDefault();
        var dropZone = e.target.closest("[data-pve-image-drop], [data-pve-video-drop]");
        var pveImage = dropZone.closest("[data-pve-image]");
        var pveVideo = dropZone.closest("[data-pve-video]");
        if (!pveImage && !pveVideo) return;
        var prefix = pveImage
          ? pveImage.getAttribute("data-pve-image")
          : pveVideo.getAttribute("data-pve-video");
        var uploadBtn = (pveImage || pveVideo).querySelector("[data-pve-upload]");
        var spec = (uploadBtn && uploadBtn.getAttribute("data-pve-upload-spec")) || uploadSpecDefault();
        var accept = uploadBtn && uploadBtn.getAttribute("data-pve-upload-accept");
        uploadImage(spec, accept, function (path) {
          applyUploadedMedia(form, prefix, path);
        });
        return;
      }
      if (e.target.closest("[data-pve-clear-video]")) {
        e.preventDefault();
        var clearVideoId = e.target.closest("[data-pve-clear-video]").getAttribute("data-pve-clear-video");
        var videoInput = form.querySelector('[data-pve-field="video_src"]');
        if (videoInput) videoInput.value = "";
        if (clearVideoId === "video") state.draft.videoSrc = "";
        var videoPrev = form.querySelector('[data-pve-video="' + clearVideoId + '"] [data-pve-video-preview]');
        if (videoPrev) videoPrev.innerHTML = '<span class="pve-image-empty">ยังไม่มีไฟล์วิดีโอ — กดเลือกไฟล์เพื่ออัปโหลด</span>';
        syncPreviewFromForm();
        return;
      }
      if (e.target.closest("[data-pve-clear-image]")) {
        e.preventDefault();
        var clearId = e.target.closest("[data-pve-clear-image]").getAttribute("data-pve-clear-image");
        var srcInput = form.querySelector('[data-pve-field="' + clearId + '_src"]');
        if (srcInput) srcInput.value = "";
        if (clearId === "main") {
          state.draft.image = state.draft.image || { src: "", alt: "" };
          state.draft.image.src = "";
        }
        if (clearId.indexOf("item_") === 0) {
          var ci = parseInt(clearId.replace("item_", ""), 10);
          if (state.draft.items && state.draft.items[ci]) {
            state.draft.items[ci].image = state.draft.items[ci].image || {};
            state.draft.items[ci].image.src = "";
          }
        }
        var prev = form.querySelector('[data-pve-image="' + clearId + '"] [data-pve-image-preview]');
        if (prev) prev.innerHTML = '<span class="pve-image-empty">ลากรูปมาวาง หรือกด「+ เลือกรูป」</span>';
        syncPreviewFromForm();
      }
    });

    var dragFrom = null;

    form.addEventListener("dragenter", function (e) {
      if (!isFileDrag(e)) return;
      var zone = e.target.closest("[data-pve-image-drop], [data-pve-video-drop]");
      if (zone) zone.classList.add("is-dragover");
    });

    form.addEventListener("dragover", function (e) {
      if (isFileDrag(e)) {
        var fileZone = e.target.closest("[data-pve-image-drop], [data-pve-video-drop]");
        if (fileZone) {
          e.preventDefault();
          e.stopPropagation();
          e.dataTransfer.dropEffect = "copy";
          fileZone.classList.add("is-dragover");
          return;
        }
      }
      var item = e.target.closest("[data-pve-repeater-item]");
      if (!item || !dragFrom) return;
      e.preventDefault();
      item.classList.add("is-drag-over");
    });

    form.addEventListener("dragleave", function (e) {
      var zone = e.target.closest("[data-pve-image-drop], [data-pve-video-drop]");
      if (zone && !zone.contains(e.relatedTarget)) zone.classList.remove("is-dragover");
    });

    form.addEventListener("drop", function (e) {
      var fileZone = e.target.closest("[data-pve-image-drop]");
      var videoZone = e.target.closest("[data-pve-video-drop]");
      if (fileZone || videoZone) {
        e.preventDefault();
        e.stopPropagation();
        form.querySelectorAll("[data-pve-image-drop].is-dragover, [data-pve-video-drop].is-dragover").forEach(function (el) {
          el.classList.remove("is-dragover");
        });
        var file = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
        if (!file) return;
        if (fileZone) {
          if (!isImageFile(file)) {
            setStatus("กรุณาลากไฟล์รูปภาพ (JPG, PNG, WEBP …) เท่านั้น", "error");
            return;
          }
          var pveImage = fileZone.closest("[data-pve-image]");
          if (!pveImage) return;
          var prefix = pveImage.getAttribute("data-pve-image");
          var uploadBtn = pveImage.querySelector("[data-pve-upload]");
          var spec = (uploadBtn && uploadBtn.getAttribute("data-pve-upload-spec")) || uploadSpecDefault();
          setStatus("กำลังอัปโหลดรูป...", "");
          uploadImageFile(file, spec, function (path) {
            applyUploadedMedia(form, prefix, path);
            setStatus("อัปโหลดรูปแล้ว ✓", "ok");
          });
        } else if (videoZone) {
          var pveVideo = videoZone.closest("[data-pve-video]");
          if (!pveVideo) return;
          var uploadBtnV = pveVideo.querySelector("[data-pve-upload]");
          var specV = (uploadBtnV && uploadBtnV.getAttribute("data-pve-upload-spec")) || "video_library";
          setStatus("กำลังอัปโหลดวิดีโอ...", "");
          uploadImageFile(file, specV, function (path) {
            applyUploadedMedia(form, "video", path);
            setStatus("อัปโหลดวิดีโอแล้ว ✓", "ok");
          });
        }
        return;
      }
      e.preventDefault();
      var toItem = e.target.closest("[data-pve-repeater-item]");
      if (!toItem || !dragFrom || toItem === dragFrom) return;
      reorderDraftItems(parseInt(dragFrom.getAttribute("data-index"), 10), parseInt(toItem.getAttribute("data-index"), 10));
    });

    form.addEventListener("dragstart", function (e) {
      if (!e.target.closest("[data-pve-repeater-drag]")) { e.preventDefault(); return; }
      dragFrom = e.target.closest("[data-pve-repeater-item]");
      if (dragFrom) dragFrom.classList.add("is-dragging");
    });
    form.addEventListener("dragend", function () {
      form.querySelectorAll(".pve-repeater-item.is-dragging, .pve-repeater-item.is-drag-over").forEach(function (el) {
        el.classList.remove("is-dragging", "is-drag-over");
      });
      dragFrom = null;
    });
  }

  function bindEvents() {
    document.querySelectorAll("[data-pe-tab]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        switchTab(btn.getAttribute("data-pe-tab"));
      });
    });

    document.getElementById("pe-panel-close").addEventListener("click", function () {
      closePanel(true);
      if (window.matchMedia("(max-width: 1100px)").matches) {
        document.getElementById("pe-edit-panel").classList.remove("is-open");
        var bd = document.getElementById("pe-edit-backdrop");
        if (bd) { bd.hidden = true; bd.classList.remove("is-visible"); }
      }
    });
    document.getElementById("pe-panel-cancel").addEventListener("click", function () { closePanel(true); });
    document.getElementById("pe-panel-apply").addEventListener("click", function () {
      syncPreviewFromForm();
      setStatus("อัปเดต Preview แล้ว — กดบันทึกด้านบนเพื่อเก็บลงระบบ", "ok");
    });
    document.getElementById("pe-edit-backdrop").addEventListener("click", function () { closePanel(true); });
    document.getElementById("pe-save").addEventListener("click", function () { save(false); });
    document.getElementById("pe-publish").addEventListener("click", function () { save(true); });

    document.getElementById("pe-preview-stage").addEventListener("click", function (e) {
      if (e.target.closest("[data-pe-delete]")) {
        e.preventDefault();
        e.stopPropagation();
        var block = e.target.closest(".pe-preview-block");
        if (!block) return;
        if (block.getAttribute("data-pe-target") !== "section") return;
        var index = block.hasAttribute("data-pe-index") ? parseInt(block.getAttribute("data-pe-index"), 10) : null;
        if (index == null || isNaN(index)) return;
        deleteSection(index);
        return;
      }
      if (!e.target.closest("[data-pe-edit]")) return;
      var block = e.target.closest(".pe-preview-block");
      if (!block) return;
      openPanel(block.getAttribute("data-pe-target"), block.hasAttribute("data-pe-index") ? parseInt(block.getAttribute("data-pe-index"), 10) : null);
    });

    document.getElementById("pe-section-rail").addEventListener("click", function (e) {
      if (e.target.id === "pe-add-section" || e.target.closest("#pe-add-section")) {
        e.preventDefault();
        addSection();
        return;
      }
      var rail = e.target.closest(".pve-rail-item");
      if (!rail) return;
      var target = rail.getAttribute("data-pe-target");
      var index = rail.hasAttribute("data-pe-index") ? parseInt(rail.getAttribute("data-pe-index"), 10) : null;

      if (e.target.closest("[data-pve-rail-vis]")) {
        e.preventDefault();
        toggleSectionVisibility(target, index);
        return;
      }
      if (e.target.closest("[data-pve-rail-dup]")) {
        e.preventDefault();
        duplicateSection(index);
        return;
      }
      if (e.target.closest("[data-pve-rail-del]")) {
        e.preventDefault();
        deleteSection(index);
        return;
      }
      if (e.target.closest("[data-pve-rail-select]") || e.target.closest(".pve-rail-label")) {
        openPanel(target, index);
      }
    });

    bindToolPalette();
    bindWorkspaceDrop();
    bindSectionDrag();
  }

  function handleDropZoneOver(e) {
    if (!state.dragBlockType && state.dragSectionIdx === null) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = state.dragBlockType ? "copy" : "move";
    var zone = e.currentTarget;
    document.querySelectorAll(".pe-drop-zone.is-over").forEach(function (el) {
      if (el !== zone) el.classList.remove("is-over");
    });
    zone.classList.add("is-over");
  }

  function handleDropZoneLeave(e) {
    var zone = e.currentTarget;
    if (!zone.contains(e.relatedTarget)) zone.classList.remove("is-over");
  }

  function handleDropZoneDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    var zone = e.currentTarget;
    zone.classList.remove("is-over");
    handleBlockDrop(e);
  }

  function bindDropZones() {
    document.querySelectorAll(".pe-drop-zone").forEach(function (zone) {
      if (zone.dataset.dropBound) return;
      zone.dataset.dropBound = "1";
      zone.addEventListener("dragover", handleDropZoneOver);
      zone.addEventListener("dragleave", handleDropZoneLeave);
      zone.addEventListener("drop", handleDropZoneDrop);
    });
  }

  function bindWorkspaceDrop() {
    [
      document.getElementById("pe-preview-scroll"),
      document.getElementById("pe-preview-frame"),
      document.getElementById("pe-section-rail"),
    ].forEach(function (root) {
      if (!root || root.dataset.workspaceDropBound) return;
      root.dataset.workspaceDropBound = "1";

      root.addEventListener("dragover", function (e) {
        if (!state.dragBlockType && state.dragSectionIdx === null) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = state.dragBlockType ? "copy" : "move";
        highlightDropZoneAt(e.clientY, root);
      });

      root.addEventListener("drop", function (e) {
        if (!state.dragBlockType && state.dragSectionIdx === null) return;
        e.preventDefault();
        e.stopPropagation();
        handleBlockDrop(e);
      });
    });
  }

  function bindToolPalette() {
    var palette = document.getElementById("pe-tools-palette");
    if (!palette || palette.dataset.toolBound) return;
    palette.dataset.toolBound = "1";

    palette.addEventListener("dragstart", function (e) {
      var tile = e.target.closest("[data-pve-block-type]");
      if (!tile) return;
      palette.dataset.didDrag = "1";
      state.dragBlockType = tile.getAttribute("data-pve-block-type");
      e.dataTransfer.setData("application/x-pe-block-type", state.dragBlockType);
      e.dataTransfer.setData("text/plain", state.dragBlockType);
      e.dataTransfer.effectAllowed = "copy";
      document.body.classList.add("pe-is-dragging-block");
      tile.classList.add("is-dragging");
    });

    palette.addEventListener("dragend", function () {
      palette.querySelectorAll(".pve-tool-tile.is-dragging").forEach(function (el) {
        el.classList.remove("is-dragging");
      });
      setTimeout(function () { delete palette.dataset.didDrag; }, 0);
      endDrag();
    });

    palette.addEventListener("click", function (e) {
      var tile = e.target.closest("[data-pve-block-type]");
      if (!tile || palette.dataset.didDrag) return;
      addSectionAtEnd(tile.getAttribute("data-pve-block-type"));
    });

    palette.addEventListener("keydown", function (e) {
      if (e.key !== "Enter" && e.key !== " ") return;
      var tile = e.target.closest("[data-pve-block-type]");
      if (!tile) return;
      e.preventDefault();
      addSectionAtEnd(tile.getAttribute("data-pve-block-type"));
    });
  }

  function bindSectionDrag() {
    var list = document.getElementById("pve-rail-list");
    if (!list || list.dataset.dragBound) return;
    list.dataset.dragBound = "1";

    list.addEventListener("dragstart", function (e) {
      var item = e.target.closest(".pve-rail-item[data-pe-target=section]");
      if (!item) { e.preventDefault(); return; }
      if (e.target.closest(".pve-rail-btn, .pve-rail-label, [data-pve-rail-select]")) {
        e.preventDefault();
        return;
      }
      state.dragSectionIdx = parseInt(item.getAttribute("data-pe-index"), 10);
      state.dragBlockType = null;
      e.dataTransfer.setData("application/x-pe-section-from", String(state.dragSectionIdx));
      e.dataTransfer.effectAllowed = "move";
      item.classList.add("is-dragging");
      document.body.classList.add("pe-is-dragging-section");
    });

    list.addEventListener("dragend", endDrag);

    list.addEventListener("dragover", function (e) {
      var item = e.target.closest(".pve-rail-item[data-pe-target=section]");
      if (!item || state.dragSectionIdx === null) return;
      e.preventDefault();
      list.querySelectorAll(".is-drag-over").forEach(function (el) {
        if (el !== item) el.classList.remove("is-drag-over");
      });
      item.classList.add("is-drag-over");
    });

    list.addEventListener("drop", function (e) {
      if (e.target.closest(".pe-drop-zone")) return;
      e.preventDefault();
      var item = e.target.closest(".pve-rail-item[data-pe-target=section]");
      if (!item || state.dragSectionIdx === null) return;
      var to = parseInt(item.getAttribute("data-pe-index"), 10);
      reorderSection(state.dragSectionIdx, to);
      endDrag();
    });
  }

  function startEditor() {
    switchTab("tools");
    renderTools();
    renderRail();
    renderPreview();
    renderPanel();
    if (isPlan || isContent) refreshViewPageLink();
    bindEvents();
    bindFormEvents();
    bindDropZones();
    bindWorkspaceDrop();
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", startEditor);
  else startEditor();

  var panelObs = new MutationObserver(function () { bindFormEvents(); bindSectionDrag(); bindDropZones(); });
  var formEl = document.getElementById("pe-edit-form");
  var railEl = document.getElementById("pe-section-rail");
  if (formEl) panelObs.observe(formEl, { childList: true, subtree: true });
  if (railEl) panelObs.observe(railEl, { childList: true, subtree: true });
})();
