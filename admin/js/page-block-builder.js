/**
 * Block Builder — schema, defaults, form UI, section rail
 */
window.PageBlockBuilder = (function () {
  var R = window.PageBlockRender;

  var IMAGE_HINTS = {
    heroBanner: "1920×600 px — แบนเนอร์ Hero",
    image: "1200×800 px — รูปเต็มความกว้าง",
    imageText: "800×600 px — รูปคู่ข้อความ",
    gallery: "800×600 px — แกลเลอรี่",
    cardGrid2: "600×400 px — การ์ด",
    cardGrid3: "600×400 px — การ์ด",
    cardGrid4: "480×320 px — การ์ด",
    team: "400×400 px — รูปโปรไฟล์",
    review: "80×80 px — รูปผู้รีวิว",
    default: "1200×800 px — JPG หรือ PNG",
  };

  var BLOCK_GROUPS = [
    { label: "พื้นฐาน", types: ["heading", "text", "image", "imageText", "video", "customHtml"] },
    { label: "เลย์เอาต์", types: ["cardGrid2", "cardGrid3", "cardGrid4", "gallery", "faq", "ctaButton"] },
    { label: "เนื้อหา", types: ["team", "review", "contactInfo"] },
    { label: "ระบบ / เดิม", types: ["prose", "profile", "achievements", "infoBlocks", "serviceCards", "cardGrid", "featured", "socialLinks", "claimWidget"] },
  ];

  var SECTION_ICONS = [
    { id: "shield-check", label: "โล่" },
    { id: "heart", label: "หัวใจ" },
    { id: "users", label: "ทีม" },
    { id: "star", label: "ดาว" },
    { id: "layout-grid", label: "ตาราง" },
    { id: "image", label: "รูปภาพ" },
    { id: "file-text", label: "บทความ" },
    { id: "circle-help", label: "คำถาม" },
    { id: "phone", label: "โทร" },
    { id: "mail", label: "อีเมล" },
    { id: "map-pin", label: "สถานที่" },
    { id: "trophy", label: "รางวัล" },
    { id: "chart-line", label: "กราฟ" },
    { id: "wallet", label: "เงิน" },
    { id: "sparkles", label: "ไฮไลท์" },
    { id: "newspaper", label: "ข่าว" },
    { id: "video", label: "วิดีโอ" },
    { id: "message-circle", label: "แชท" },
    { id: "building-2", label: "สำนักงาน" },
    { id: "share-2", label: "แชร์" },
  ];

  var TOOL_BLOCKS = [
    { type: "heading", label: "หัวข้อ", icon: "heading" },
    { type: "text", label: "ข้อความ", icon: "text" },
    { type: "image", label: "รูปภาพ", icon: "image" },
    { type: "imageText", label: "รูป+ข้อความ", icon: "columns-2" },
    { type: "ctaButton", label: "ปุ่ม CTA", icon: "mouse-pointer-click" },
    { type: "cardGrid3", label: "การ์ดบริการ", icon: "layout-grid" },
    { type: "gallery", label: "แกลเลอรี่", icon: "images" },
    { type: "faq", label: "FAQ", icon: "faq" },
    { type: "team", label: "ทีมงาน", icon: "users" },
    { type: "review", label: "รีวิว", icon: "star" },
    { type: "video", label: "วิดีโอ", icon: "video" },
    { type: "customHtml", label: "HTML", icon: "code-2" },
  ];

  function pickerIcon(key) {
    if (window.LucideIcons) {
      return LucideIcons.icon(key, { size: 18, strokeWidth: 2, className: "pve-icon-pick-svg" });
    }
    return '<i data-lucide="' + esc(key) + '" class="lucide-icon pve-icon-pick-svg" style="width:18px;height:18px" aria-hidden="true"></i>';
  }

  function iconPickerField(draft) {
    var showIcon = draft.showIcon !== false;
    var current = draft.icon || "shield-check";
    var html =
      '<div class="pve-field pve-icon-field">' +
      check("showIcon", "แสดงไอคอนหัวข้อ", showIcon) +
      '<div class="pve-icon-picker"' + (showIcon ? "" : " hidden") + ' data-pve-icon-picker>' +
      '<span class="pve-label">เลือกไอคอน</span>' +
      '<div class="pve-icon-picker-grid" role="listbox" aria-label="เลือกไอคอนหัวข้อ">';
    SECTION_ICONS.forEach(function (ic) {
      var selected = current === ic.id;
      html +=
        '<button type="button" class="pve-icon-pick' + (selected ? " is-selected" : "") + '" data-pve-icon-pick="' + esc(ic.id) + '" title="' + esc(ic.label) + '" role="option" aria-selected="' + (selected ? "true" : "false") + '">' +
        pickerIcon(ic.id) +
        '<span class="pve-icon-pick-label">' + esc(ic.label) + "</span></button>";
    });
    html += '</div><input type="hidden" data-pve-field="icon" value="' + esc(current) + '"></div></div>';
    return html;
  }

  function toolTileIcon(key) {
    var lucideName = window.LucideIcons ? LucideIcons.name(key) : key;
    if (window.LucideIcons) {
      return LucideIcons.icon(key, { size: 26, strokeWidth: 1.75, className: "pve-tool-tile-svg" });
    }
    return '<i data-lucide="' + esc(lucideName) + '" class="lucide-icon pve-tool-tile-svg" style="width:26px;height:26px" aria-hidden="true"></i>';
  }

  function railIcon(key) {
    if (window.LucideIcons) {
      return LucideIcons.icon(key, { size: 14, strokeWidth: 2, className: "pve-rail-btn-svg" });
    }
    return '<i data-lucide="' + esc(key) + '" class="lucide-icon pve-rail-btn-svg" style="width:14px;height:14px" aria-hidden="true"></i>';
  }

  function uid() {
    return "b" + Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
  }

  function defaultItem() {
    return {
      title: "",
      subtitle: "",
      description: "",
      image: { src: "", alt: "" },
      buttonText: "",
      buttonLink: "",
      isVisible: true,
      sortOrder: 0,
    };
  }

  function defaultBlock(type) {
    var base = {
      id: uid(),
      type: type,
      title: "",
      subtitle: "",
      description: "",
      image: { src: "", alt: "" },
      buttonText: "",
      buttonLink: "",
      isVisible: true,
      visible: true,
      sortOrder: 0,
      alt: false,
      icon: "shield-check",
      showIcon: true,
      items: [],
      videoUrl: "",
      videoSrc: "",
      customHtml: "",
      columns: 3,
    };
    var samples = {
      heading: { title: "หัวข้อใหม่", subtitle: "คำอธิบายย่อย" },
      text: { description: "เนื้อหาข้อความ" },
      image: { title: "", subtitle: "", showIcon: false, image: { src: "", alt: "" } },
      imageText: { title: "หัวข้อ", description: "รายละเอียด", showIcon: false },
      ctaButton: { title: "พร้อมเริ่มต้น?", description: "ติดต่อเราวันนี้", buttonText: "ติดต่อสอบถาม", buttonLink: "contact.html" },
      gallery: { title: "", subtitle: "", showIcon: false, items: [defaultItem()] },
      faq: { title: "คำถามที่พบบ่อย", items: [{ title: "คำถาม?", description: "คำตอบ", isVisible: true }] },
      team: { title: "ทีมงาน", subtitle: "", description: "", avatars: ["?", "+1"], items: [] },
      review: { title: "รีวิวลูกค้า", items: [{ title: "ชื่อลูกค้า", description: "ข้อความรีวิว", isVisible: true }] },
      contactInfo: { title: "ติดต่อเรา", description: "โทร 085-292-5320" },
      video: { title: "", subtitle: "", showIcon: false, videoUrl: "", videoSrc: "" },
      customHtml: { customHtml: "<p>เนื้อหา HTML</p>" },
      specTable: { showIcon: false, items: [{ title: "หัวข้อ", description: "รายละเอียด", isVisible: true }] },
      bulletList: { showIcon: false, items: [{ title: "หัวข้อ", description: "รายละเอียด", isVisible: true }] },
      socialLinks: {
        title: "ติดตามไทยประกันชีวิต",
        icon: "share-2",
        linkText: "เยี่ยมชมเว็บไซต์ →",
        linkHref: "https://www.thailife.com",
        items: [
          { title: "Facebook", subtitle: "@thailifepage", icon: "facebook", buttonLink: "https://www.facebook.com/thailifepage", isVisible: true },
          { title: "Line", subtitle: "@thailifeinsurance", icon: "line", buttonLink: "https://line.me/", isVisible: true },
          { title: "YouTube", subtitle: "THAILIFECHANNEL", icon: "youtube", buttonLink: "https://www.youtube.com/", isVisible: true },
        ],
      },
      cardGrid2: { title: "บริการของเรา", columns: 2, items: [defaultItem()] },
      cardGrid3: { title: "บริการของเรา", columns: 3, items: [defaultItem()] },
      cardGrid4: { title: "บริการของเรา", columns: 4, items: [defaultItem()] },
      prose: { blocks: [{ type: "text", html: "เนื้อหาใหม่" }] },
      achievements: { title: "เกียรติประวัติ", subtitle: "", tags: ["แท็กใหม่"], items: [] },
      infoBlocks: { title: "หัวข้อใหม่", items: [{ title: "หัวข้อ", description: "รายละเอียด", buttonLink: "" }] },
      serviceCards: { title: "บริการ", items: [{ title: "หัวข้อ", description: "รายละเอียด", buttonText: "ดูเพิ่ม →", buttonLink: "" }] },
    };
    if (samples[type]) Object.assign(base, JSON.parse(JSON.stringify(samples[type])));
    return base;
  }

  function blockLabel(type, catalog) {
    return (catalog && catalog[type]) || type || "Section";
  }

  function esc(s) {
    return R ? R.esc(s) : String(s || "");
  }

  function field(label, html, hint) {
    return (
      '<div class="pve-field">' +
      '<label class="pve-label">' + esc(label) + "</label>" +
      html +
      (hint ? '<p class="pve-hint">' + esc(hint) + "</p>" : "") +
      "</div>"
    );
  }

  function input(id, value, placeholder) {
    return '<input class="pve-input" data-pve-field="' + esc(id) + '" value="' + esc(value || "") + '" placeholder="' + esc(placeholder || "") + '">';
  }

  function textarea(id, value, rows) {
    return '<textarea class="pve-textarea" rows="' + (rows || 4) + '" data-pve-field="' + esc(id) + '">' + esc(value || "") + "</textarea>";
  }

  function check(id, label, checked) {
    return (
      '<label class="pve-check"><input type="checkbox" data-pve-field="' + esc(id) + '"' + (checked ? " checked" : "") + ">" + esc(label) + "</label>"
    );
  }

  function imageField(prefix, img, hint, ctx) {
    img = img || { src: "", alt: "" };
    ctx = ctx || {};
    var srcKey = prefix + "_src";
    var altKey = prefix + "_alt";
    var uploadSpec = ctx.isPlan ? "plan_content" : "media_library";
    var previewSrc = img.src && ctx.imgSrc ? ctx.imgSrc(img.src) : img.src;
    var preview = previewSrc
      ? '<img src="' + esc(previewSrc) + '" alt="">'
      : '<span class="pve-image-empty">ลากรูปมาวาง หรือกด「+ เลือกรูป」</span>';
    return (
      '<div class="pve-field pve-image" data-pve-image="' + esc(prefix) + '">' +
      '<span class="pve-label">รูปภาพ</span>' +
      '<div class="pve-image-preview" data-pve-image-preview data-pve-image-drop title="ลากรูปมาวาง หรือคลิกเพื่อเลือกไฟล์">' + preview + "</div>" +
      '<input type="hidden" data-pve-field="' + esc(srcKey) + '" value="' + esc(img.src || "") + '">' +
      field("คำอธิบายรูป (alt)", input(altKey, img.alt)) +
      '<p class="pve-hint"><strong>ขนาดแนะนำ:</strong> ' + esc(hint || IMAGE_HINTS.default) + "</p>" +
      '<div class="pve-image-actions">' +
      '<button type="button" class="admin-btn admin-btn--primary admin-btn--sm" data-pve-upload="' + esc(prefix) + '" data-pve-upload-spec="' + esc(uploadSpec) + '">+ เลือกรูป</button>' +
      '<button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-pve-clear-image="' + esc(prefix) + '">ลบรูป</button>' +
      "</div></div>"
    );
  }

  function videoField(draft, ctx) {
    var src = draft.videoSrc || "";
    var previewSrc = src && ctx && ctx.imgSrc ? ctx.imgSrc(src) : src;
    var preview = previewSrc
      ? '<video src="' + esc(previewSrc) + '" controls muted preload="metadata"></video>'
      : '<span class="pve-image-empty">ลากไฟล์มาวาง หรือกด「+ เลือกวิดีโอ」</span>';
    return (
      '<div class="pve-field pve-video" data-pve-video="video">' +
      '<span class="pve-label">ไฟล์วิดีโอ</span>' +
      '<div class="pve-image-preview pve-video-preview" data-pve-video-preview data-pve-video-drop title="ลากไฟล์มาวาง หรือคลิกเพื่อเลือกไฟล์">' + preview + "</div>" +
      '<input type="hidden" data-pve-field="video_src" value="' + esc(src) + '">' +
      '<p class="pve-hint"><strong>รองรับ:</strong> MP4, WEBM, OGG, MOV · ไม่เกิน 50 MB</p>' +
      '<div class="pve-image-actions">' +
      '<button type="button" class="admin-btn admin-btn--primary admin-btn--sm" data-pve-upload="video" data-pve-upload-spec="video_library" data-pve-upload-accept="video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogg,.mov">+ เลือกวิดีโอ</button>' +
      '<button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" data-pve-clear-video="video">ลบไฟล์</button>' +
      "</div></div>" +
      field(
        "ลิงก์วิดีโอ (URL)",
        input("videoUrl", draft.videoUrl, "https://www.youtube.com/watch?v=..."),
        "วาง URL จาก YouTube / Vimeo หรือลิงก์ .mp4 โดยตรง — ใช้เมื่อไม่ได้อัปโหลดไฟล์"
      )
    );
  }

  function metaBar(draft, ctx) {
    if (ctx.target !== "section") return "";
    if (draft.type === "cardGrid") return "";
    return check("alt", "พื้นหลังสลับสี", !!draft.alt);
  }

  function repeaterStart(title) {
    return '<div class="pve-repeater"><div class="pve-repeater-head"><h4 class="pve-repeater-title">' + esc(title) + '</h4><button type="button" class="admin-btn admin-btn--secondary admin-btn--sm" data-pve-repeater-add>+ เพิ่ม</button></div><div class="pve-repeater-list" data-pve-repeater-list>';
  }

  function repeaterEnd() {
    return "</div></div>";
  }

  function repeaterItem(summary, body, index, expanded) {
    var expClass = expanded ? " is-expanded" : "";
    var expAttr = expanded ? "true" : "false";
    return (
      '<article class="pve-repeater-item pve-repeater-item--accordion' + expClass + '" data-pve-repeater-item data-index="' + index + '" draggable="true">' +
      '<header class="pve-repeater-item-head" data-pve-repeater-toggle role="button" tabindex="0" aria-expanded="' + expAttr + '">' +
      '<button type="button" class="pve-repeater-drag" data-pve-repeater-drag title="ลากสลับ" aria-label="ลากสลับ">⠿</button>' +
      '<span class="pve-repeater-summary">' + esc(summary || "รายการ") + "</span>" +
      '<div class="pve-repeater-actions">' +
      '<button type="button" class="pve-repeater-chevron" data-pve-repeater-edit aria-label="เปิด/ปิด"><span aria-hidden="true">›</span></button>' +
      '<button type="button" class="pve-repeater-btn pve-repeater-btn--danger" data-pve-repeater-del title="ลบ" aria-label="ลบ">×</button>' +
      "</div></header>" +
      '<div class="pve-repeater-item-body">' + body + "</div></article>"
    );
  }

  function ensureFeaturedBullets(draft) {
    if (!draft || draft.type !== "featured") return;
    if (!Array.isArray(draft.bullets)) draft.bullets = [];
    if (!draft.bullets.length && draft.items && draft.items.length) {
      draft.bullets = draft.items
        .filter(function (it) {
          return it && it.isVisible !== false;
        })
        .map(function (it) {
          return String(it.title || "").trim();
        })
        .filter(Boolean);
    }
    if (!draft.bullets.length) draft.bullets = [""];
  }

  function syncFeaturedItemsFromBullets(draft) {
    draft.items = (draft.bullets || [])
      .map(function (t) {
        return String(t || "").trim();
      })
      .filter(Boolean)
      .map(function (t, i) {
        return { title: t, isVisible: true, sortOrder: i };
      });
  }

  function buildBulletsRepeater(draft) {
    ensureFeaturedBullets(draft);
    var body = draft.bullets
      .map(function (text, i) {
        var summary = String(text || "").trim() || "รายการ " + (i + 1);
        return repeaterItem(
          summary.slice(0, 48),
          field("ข้อความรายการ", input("bullet_" + i, text)),
          i,
          true
        );
      })
      .join("");
    return (
      repeaterStart("รายการจุดเด่น (bullet)") +
      body +
      repeaterEnd() +
      '<p class="pve-hint">แก้ไขข้อความแต่ละบรรทัดด้านล่าง — กด + เพิ่ม หรือ × ลบ</p>'
    );
  }

  function readBullets(form, draft) {
    var bullets = [];
    var i = 0;
    while (true) {
      var el = form.querySelector('[data-pve-field="bullet_' + i + '"]');
      if (!el) break;
      bullets.push(el.value);
      i++;
    }
    draft.bullets = bullets;
    syncFeaturedItemsFromBullets(draft);
  }

  function standardFields(draft, opts) {
    opts = opts || {};
    var ctx = opts.ctx;
    var html = "";
    if (opts.title !== false) html += field("หัวข้อ (title)", input("title", draft.title || draft.heading));
    if (opts.subtitle !== false) html += field("คำอธิบายย่อย (subtitle)", input("subtitle", draft.subtitle));
    if (opts.icon !== false && (opts.title !== false || opts.subtitle !== false)) html += iconPickerField(draft);
    if (opts.description !== false) html += field("รายละเอียด (description)", textarea("description", draft.description || draft.text || draft.lead, 5), "แยกย่อหน้าด้วยบรรทัดว่าง");
    if (opts.image) html += imageField("main", draft.image, IMAGE_HINTS[opts.image] || IMAGE_HINTS.default, ctx);
    if (opts.button !== false) {
      html += field("ข้อความปุ่ม", input("buttonText", draft.buttonText));
      html += field("ลิงก์ปุ่ม", input("buttonLink", draft.buttonLink || draft.href, "contact.html"));
    }
    return html;
  }

  var SOCIAL_ICONS = [
    { id: "facebook", label: "Facebook" },
    { id: "line", label: "Line" },
    { id: "youtube", label: "YouTube" },
    { id: "instagram", label: "Instagram" },
    { id: "mail", label: "อีเมล" },
    { id: "globe", label: "เว็บไซต์" },
  ];

  function socialIconSelect(prefix, value) {
    var html = '<select class="pve-select" data-pve-field="' + esc(prefix) + 'icon">';
    SOCIAL_ICONS.forEach(function (ic) {
      html += '<option value="' + esc(ic.id) + '"' + (value === ic.id ? " selected" : "") + ">" + esc(ic.label) + "</option>";
    });
    return html + "</select>";
  }

  function itemFields(item, i, opts) {
    opts = opts || {};
    var ctx = opts.ctx;
    var p = "item_" + i + "_";
    if (opts.imageOnly) {
      return (
        imageField("item_" + i, item.image, IMAGE_HINTS[opts.image] || IMAGE_HINTS.default, ctx) +
        check(p + "isVisible", "แสดงรายการนี้", item.isVisible !== false)
      );
    }
    if (opts.social) {
      return (
        field("ไอคอน", socialIconSelect(p, item.icon || "globe")) +
        field("ชื่อแพลตฟอร์ม", input(p + "title", item.title, "Facebook")) +
        field("รายละเอียด / ID", input(p + "subtitle", item.subtitle || item.description, "@username")) +
        field("ลิงก์", input(p + "buttonLink", item.buttonLink || item.href, "https://")) +
        check(p + "isVisible", "แสดงรายการนี้", item.isVisible !== false)
      );
    }
    if (opts.compact) {
      var titleLabel = opts.titleLabel || "หัวข้อ";
      var descLabel = opts.descLabel || "รายละเอียด";
      return (
        field(titleLabel, input(p + "title", item.title)) +
        field(descLabel, textarea(p + "description", item.description, 2)) +
        check(p + "isVisible", "แสดงรายการนี้", item.isVisible !== false)
      );
    }
    var html =
      field("หัวข้อ", input(p + "title", item.title)) +
      field("คำอธิบายย่อย", input(p + "subtitle", item.subtitle || item.meta || "")) +
      field("รายละเอียด", textarea(p + "description", item.description || item.text || "", 3));
    if (opts.image) html += imageField("item_" + i, item.image, IMAGE_HINTS[opts.image] || IMAGE_HINTS.default, ctx);
    html += field("ข้อความปุ่ม", input(p + "buttonText", item.buttonText || item.linkText || ""));
    html += field("ลิงก์ปุ่ม", input(p + "buttonLink", item.buttonLink || item.href || ""));
    html += check(p + "isVisible", "แสดงรายการนี้", item.isVisible !== false);
    return html;
  }

  function buildItemsRepeater(draft, title, opts, ctx) {
    opts = opts || {};
    opts.ctx = ctx;
    var items = draft.items || draft.tags || [];
    if (draft.type === "achievements" && draft.tags && !draft.items.length) {
      items = draft.tags.map(function (t) { return { title: t, isVisible: true }; });
    }
    var body = items.map(function (item, i) {
      var summary;
      if (opts.imageOnly) {
        summary = item.image && item.image.src ? String(item.image.src).split("/").pop() : "รูป " + (i + 1);
      } else {
        summary = item.title || item.description || item.text || ("รายการ " + (i + 1));
      }
      if (typeof item === "string") summary = item;
      return repeaterItem(String(summary).slice(0, 48), itemFields(typeof item === "object" ? item : { title: item }, i, opts), i);
    }).join("");
    return repeaterStart(title) + body + repeaterEnd();
  }

  function buildForm(draft, ctx) {
    ctx = ctx || {};
    var type = draft.type || "text";
    var html = metaBar(draft, ctx);
    if (html) html = '<div class="pve-meta-bar">' + html + "</div>";

    switch (type) {
      case "heading":
        html += standardFields(draft, { description: false, image: false, button: false });
        break;
      case "text":
        html += standardFields(draft, { image: false, button: false, title: true, subtitle: true });
        break;
      case "image":
        html += imageField("main", draft.image, IMAGE_HINTS.image, ctx);
        break;
      case "imageText":
        html += standardFields(draft, { image: "imageText", icon: false, ctx: ctx });
        break;
      case "video":
        html += videoField(draft, ctx);
        break;
      case "customHtml":
        html += field("HTML", textarea("customHtml", draft.customHtml, 8), ctx.isPlan ? "สำหรับผู้ใช้ขั้นสูง — แนะนำใช้「ตารางข้อมูลแผน」หรือ「รายการจุดเด่น」แทน" : "สำหรับผู้ใช้ขั้นสูง — ระวังโค้ดไม่ปลอดภัย");
        break;
      case "specTable":
        html += buildItemsRepeater(draft, "แถวข้อมูล", { compact: true, titleLabel: "ชื่อแถว", descLabel: "ข้อมูล" }, ctx);
        html += '<p class="pve-hint">แก้ไขทีละแถว — ไม่ต้องเขียน HTML</p>';
        break;
      case "bulletList":
        html += buildItemsRepeater(draft, "รายการ", { compact: true, titleLabel: "หัวข้อ (ตัวหนา)", descLabel: "รายละเอียด" }, ctx);
        html += '<p class="pve-hint">แสดงเป็นรายการ bullet — หัวข้อจะเป็นตัวหนา</p>';
        break;
      case "ctaButton":
      case "contactInfo":
        html += standardFields(draft, { image: false });
        break;
      case "cardGrid2":
      case "cardGrid3":
      case "cardGrid4":
        html += standardFields(draft, { image: false, button: false });
        html += buildItemsRepeater(draft, "การ์ด", { image: type }, ctx);
        break;
      case "gallery":
        html += buildItemsRepeater(draft, "รูปภาพ", { image: "gallery", imageOnly: true }, ctx);
        break;
      case "faq":
        html += standardFields(draft, { button: false, image: false });
        html += buildItemsRepeater(draft, "คำถาม-คำตอบ", {}, ctx);
        break;
      case "team":
      case "review":
        html += standardFields(draft, { button: false, image: false });
        html += buildItemsRepeater(draft, type === "team" ? "สมาชิกทีม" : "รีวิว", { image: type === "team" ? "team" : false }, ctx);
        break;
      case "prose":
        html += check("includeProfile", "แสดงข้อมูลตัวแทน", !!draft.includeProfile);
        html += buildProseRepeater(draft, ctx);
        break;
      case "achievements":
        html += standardFields(draft, { button: false, image: false });
        html += field("ข้อความท้ายส่วน", textarea("footer", draft.footer || draft.description, 3));
        html += buildItemsRepeater(draft, "รางวัล / แท็ก", {}, ctx);
        break;
      case "infoBlocks":
      case "serviceCards":
        html += standardFields(draft, { image: false, button: false });
        html += buildItemsRepeater(draft, "รายการ", {}, ctx);
        break;
      case "cardGrid":
        html += check("alt", "พื้นหลังสลับสี", !!draft.alt);
        html += field("แหล่งข้อมูล", '<select class="pve-select" data-pve-field="source"><option value="articles"' + (draft.source === "articles" ? " selected" : "") + '>บทความ</option><option value="careers"' + (draft.source === "careers" ? " selected" : "") + '>แนะนำอาชีพ</option><option value="news"' + (draft.source === "news" ? " selected" : "") + ">ข่าว</option></select>");
        html += '<p class="pve-note">ดึงการ์ดจากรายการเนื้อหาอัตโนมัติ</p>';
        break;
      case "featured": {
        ensureFeaturedBullets(draft);
        html += metaBar(draft, ctx);
        html += field("หัวข้อส่วน (หัวข้อใหญ่ด้านบน)", input("title", draft.title || draft.heading));
        html += field("คำอธิบายย่อยส่วน", input("subtitle", draft.subtitle));
        html += iconPickerField(draft);
        html += '<div class="pve-section-divider">เนื้อหาการ์ดเด่น</div>';
        html += field("หมวด / แท็ก", input("featureMeta", draft.featureMeta || draft.category || "", "Digital Agent"));
        html += field("หัวข้อการ์ด (H2)", input("featureTitle", draft.featureTitle || "", "หัวข้อในการ์ด"));
        html += field("รายละเอียดการ์ด", textarea("description", draft.description, 4));
        html += imageField("main", draft.image, IMAGE_HINTS.default, ctx);
        html += field("Slug บทความ (อ้างอิง)", input("slug", draft.slug || "", "digital-agent-system"), "ใช้สร้างลิงก์อัตโนมัติเมื่อไม่ระบุลิงก์ปุ่ม");
        html += buildBulletsRepeater(draft);
        html += field("ข้อความปุ่ม", input("buttonText", draft.buttonText || "อ่านรายละเอียด →"));
        html += field("ลิงก์ปุ่ม", input("buttonLink", draft.buttonLink || (draft.slug ? "careers/" + draft.slug + ".html" : ""), "careers/digital-agent-system.html"));
        break;
      }
      case "socialLinks":
        html += standardFields(draft, { image: false, button: false, description: false });
        html += field("ข้อความลิงก์ท้ายส่วน", input("linkText", draft.linkText));
        html += field("URL ลิงก์ท้ายส่วน", input("linkHref", draft.linkHref, "https://www.thailife.com"));
        html += buildItemsRepeater(draft, "ช่องทางโซเชียล", { social: true }, ctx);
        break;
      case "profile":
      case "claimWidget":
        html += '<p class="pve-note">ส่วนนี้ดึงข้อมูลอัตโนมัติจากระบบ</p>';
        break;
      default:
        html += standardFields(draft, { image: type === "image" ? type : false });
    }
    return html;
  }

  function buildProseRepeater(draft, ctx) {
    var blocks = draft.blocks || [];
    var body = blocks.map(function (block, i) {
      var typeSelect = field("ประเภท", '<select class="pve-select" data-pve-block-type="' + i + '"><option value="quote"' + (block.type === "quote" ? " selected" : "") + ">คำคม</option><option value=\"text\"" + (block.type === "text" || !block.type ? " selected" : "") + ">ข้อความ</option><option value=\"image\"" + (block.type === "image" ? " selected" : "") + ">รูปภาพ</option></select>");
      var inner = typeSelect;
      if (block.type === "image") inner += imageField("block_" + i, { src: block.src, alt: block.alt }, IMAGE_HINTS.image, ctx);
      else inner += field(block.type === "quote" ? "คำคม" : "เนื้อหา", textarea("block_html_" + i, block.html, 5));
      var summary = block.type === "quote" ? "คำคม" : block.type === "image" ? "รูปภาพ" : (block.html || "").slice(0, 40);
      return repeaterItem(summary, inner, i);
    }).join("");
    return repeaterStart("บล็อกเนื้อหา") + body + repeaterEnd();
  }

  function buildHeroForm(draft, ctx) {
    return (
      metaBar(draft, ctx) +
      field("หัวข้อหลัก", input("title", draft.title)) +
      field("คำอธิบาย", textarea("description", draft.lead || draft.description, 3))
    );
  }

  function buildCtaForm(draft, ctx) {
    ctx = ctx || {};
    var variantOptions = function (btn) {
      var v = btn.variant || "primary";
      var html =
        '<option value="primary"' + (v === "primary" ? " selected" : "") + ">ปุ่มหลัก</option>" +
        '<option value="outline"' + (v === "outline" ? " selected" : "") + ">ปุ่มขอบ</option>";
      if (ctx.isPlan) {
        html += '<option value="white"' + (v === "white" ? " selected" : "") + ">ปุ่มขาว (แผนประกัน)</option>";
      }
      return html;
    };
    var items = (draft.buttons || []).map(function (btn, i) {
      var body =
        field("ข้อความปุ่ม", input("btn_label_" + i, btn.label || btn.buttonText)) +
        field("ลิงก์", input("btn_href_" + i, btn.href || btn.buttonLink, ctx.isPlan ? "../contact.html" : "contact.html")) +
        field("รูปแบบ", '<select class="pve-select" data-pve-field="btn_variant_' + i + '">' + variantOptions(btn) + "</select>");
      return repeaterItem(btn.label || btn.buttonText || "ปุ่ม " + (i + 1), body, i);
    }).join("");
    return (
      metaBar(draft, ctx) +
      field("หัวข้อ", input("title", draft.title)) +
      field("คำอธิบาย", textarea("description", draft.lead || draft.description, 3)) +
      '<div class="pve-section-divider">ปุ่ม</div>' +
      repeaterStart("ปุ่ม CTA") + items + repeaterEnd()
    );
  }

  function readForm(form, draft, selected) {
    if (!form || !draft || !selected) return;
    form.querySelectorAll("[data-pve-field]").forEach(function (el) {
      var key = el.getAttribute("data-pve-field");
      if (!key || key.indexOf("_") >= 0) return;
      if (el.type === "checkbox") {
        draft[key] = el.checked;
        if (key === "isVisible") draft.visible = el.checked;
      } else draft[key] = el.value;
    });

    var altToggle = form.querySelector('[data-pve-field="alt"]');
    if (altToggle && altToggle.type === "checkbox") draft.alt = altToggle.checked;

    var showIconToggle = form.querySelector('[data-pve-field="showIcon"]');
    if (showIconToggle && showIconToggle.type === "checkbox") draft.showIcon = showIconToggle.checked;

    var iconInput = form.querySelector('[data-pve-field="icon"]');
    if (iconInput) draft.icon = iconInput.value;

    if (draft.isVisible != null) draft.visible = !!draft.isVisible;

    if (selected.target === "hero") {
      draft.lead = draft.description || draft.lead;
    }

    if (selected.target === "cta") {
      draft.buttons = draft.buttons || [];
      draft.lead = draft.description || draft.lead;
      draft.buttons.forEach(function (btn, i) {
        var l = form.querySelector('[data-pve-field="btn_label_' + i + '"]');
        var h = form.querySelector('[data-pve-field="btn_href_' + i + '"]');
        var v = form.querySelector('[data-pve-field="btn_variant_' + i + '"]');
        if (l) btn.label = l.value;
        if (h) btn.href = h.value;
        if (v) btn.variant = v.value;
      });
    }

    var srcMain = form.querySelector('[data-pve-field="main_src"]');
    var altMain = form.querySelector('[data-pve-field="main_alt"]');
    if (srcMain) draft.image = draft.image || { src: "", alt: "" };
    if (srcMain) draft.image.src = srcMain.value;
    if (altMain) draft.image.alt = altMain.value;

    var videoSrcEl = form.querySelector('[data-pve-field="video_src"]');
    if (videoSrcEl) draft.videoSrc = videoSrcEl.value;

    if (selected.target === "section") readSectionForm(form, draft);
  }

  function readSectionForm(form, draft) {
    var type = draft.type;

    if (type === "prose") {
      draft.blocks = draft.blocks || [];
      draft.blocks.forEach(function (block, i) {
        var te = form.querySelector('[data-pve-block-type="' + i + '"]');
        if (te) block.type = te.value;
        if (block.type === "image") {
          var s = form.querySelector('[data-pve-field="block_' + i + '_src"]');
          var a = form.querySelector('[data-pve-field="block_' + i + '_alt"]');
          block.src = s ? s.value : "";
          block.alt = a ? a.value : "";
        } else {
          var h = form.querySelector('[data-pve-field="block_html_' + i + '"]');
          block.html = h ? h.value : "";
        }
      });
      return;
    }

    if (type === "achievements") {
      draft.tags = readItemsSimple(form, draft);
      draft.footer = draft.footer || form.querySelector('[data-pve-field="footer"]')?.value || "";
      return;
    }

    if (type === "featured") {
      readBullets(form, draft);
      var metaEl = form.querySelector('[data-pve-field="featureMeta"]');
      var featTitleEl = form.querySelector('[data-pve-field="featureTitle"]');
      var slugEl = form.querySelector('[data-pve-field="slug"]');
      if (metaEl) draft.featureMeta = metaEl.value;
      if (featTitleEl) draft.featureTitle = featTitleEl.value;
      if (slugEl) draft.slug = slugEl.value;
      return;
    }

    if (hasItemsRepeater(type)) {
      draft.items = readItems(form, draft);
      if (type === "achievements") draft.tags = draft.items.map(function (it) { return it.title; });
    }
  }

  function hasItemsRepeater(type) {
    return ["cardGrid2", "cardGrid3", "cardGrid4", "gallery", "faq", "team", "review", "infoBlocks", "serviceCards", "achievements", "specTable", "bulletList", "socialLinks"].indexOf(type) >= 0;
  }

  function readItems(form, draft) {
    var items = draft.items || [];
    return items.map(function (item, i) {
      var p = "item_" + i + "_";
      ["title", "subtitle", "description", "buttonText", "buttonLink"].forEach(function (f) {
        var el = form.querySelector('[data-pve-field="' + p + f + '"]');
        if (el) item[f] = el.value;
      });
      if (item.subtitle) item.meta = item.subtitle;
      if (item.description) item.text = item.description;
      if (item.buttonText) item.linkText = item.buttonText;
      if (item.buttonLink) item.href = item.buttonLink;
      var iconEl = form.querySelector('[data-pve-field="' + p + 'icon"]');
      if (iconEl) item.icon = iconEl.value;
      var vis = form.querySelector('[data-pve-field="' + p + 'isVisible"]');
      if (vis) item.isVisible = vis.checked;
      var src = form.querySelector('[data-pve-field="item_' + i + '_src"]');
      var alt = form.querySelector('[data-pve-field="item_' + i + '_alt"]');
      if (src) {
        item.image = item.image || { src: "", alt: "" };
        item.image.src = src.value;
        item.image.alt = alt ? alt.value : "";
      }
      item.sortOrder = i;
      return item;
    });
  }

  function readItemsSimple(form, draft) {
    var items = draft.items || draft.tags || [];
    return items.map(function (_, i) {
      var t = form.querySelector('[data-pve-field="item_' + i + '_title"]');
      return t ? t.value : "";
    }).filter(Boolean);
  }

  function defaultRepeaterItem(draft, selected) {
    if (selected.target === "cta") return { label: "ปุ่มใหม่", href: "contact.html", variant: "primary" };
    if (draft.type === "prose") return { type: "text", html: "" };
    if (draft.type === "gallery") return defaultItem();
    if (draft.type === "faq") return { title: "คำถาม?", description: "คำตอบ", isVisible: true };
    if (draft.type === "specTable" || draft.type === "bulletList") return { title: "หัวข้อ", description: "รายละเอียด", isVisible: true };
    if (draft.type === "socialLinks") return { title: "ช่องทางใหม่", subtitle: "@id", icon: "globe", buttonLink: "https://", isVisible: true };
    if (draft.type === "featured") return "";
    return defaultItem();
  }

  function getRepeaterArray(draft, selected) {
    if (selected.target === "cta") return draft.buttons;
    if (draft.type === "prose") return draft.blocks;
    if (draft.type === "featured") {
      ensureFeaturedBullets(draft);
      return draft.bullets;
    }
    if (draft.type === "achievements" && draft.tags && !draft.items.length) {
      draft.items = draft.tags.map(function (t) { return { title: t }; });
    }
    return draft.items;
  }

  var PLAN_TOOL_BLOCKS = [
    { type: "bulletList", label: "รายการจุดเด่น", icon: "list" },
    { type: "specTable", label: "ตารางข้อมูลแผน", icon: "table" },
  ];

  function renderToolsPalette(catalog, extraTools) {
    var blocks = (extraTools || []).concat(TOOL_BLOCKS);
    var html =
      '<p class="pve-tools-hint">ลากบล็อกไปวางใน Preview หรือเลเยอร์ · แตะเพื่อเพิ่มท้ายหน้า</p>' +
      '<div class="pve-tools-grid" id="pve-tools-grid">';
    blocks.forEach(function (b) {
      html +=
        '<div class="pve-tool-tile" draggable="true" role="button" tabindex="0" data-pve-block-type="' + esc(b.type) + '" title="' + esc(blockLabel(b.type, catalog)) + '">' +
        '<span class="pve-tool-tile-icon" aria-hidden="true">' + toolTileIcon(b.icon) + "</span>" +
        '<span class="pve-tool-tile-label">' + esc(b.label) + "</span></div>";
    });
    return html + "</div>";
  }

  function railDropZone(index) {
    return (
      '<li class="pe-drop-zone pe-drop-zone--rail" data-pe-drop-index="' + index + '" aria-hidden="true">' +
      '<span class="pe-drop-zone-line"></span></li>'
    );
  }

  function renderSectionRail(pageData, selected, catalog) {
    var html = '<ul class="pve-rail-list" id="pve-rail-list">';
    html += railItem("hero", null, "Hero", pageData.hero, selected, catalog);
    var secs = pageData.sections || [];
    html += railDropZone(0);
    secs.forEach(function (sec, i) {
      var name = blockLabel(sec.type, catalog);
      if (sec.title) name += " · " + String(sec.title).slice(0, 16);
      html += railItem("section", i, name, sec, selected, catalog);
      html += railDropZone(i + 1);
    });
    html += railItem("cta", null, "CTA", pageData.cta, selected, catalog);
    html += "</ul>";
    html += '<p class="pve-rail-hint">ลากจากเครื่องมือมาวางเส้นสีฟ้า · ลากไอคอนซ้ายเพื่อสลับลำดับ</p>';
    return html;
  }

  function railItem(target, index, label, data, selected, catalog) {
    var isSel = selected && selected.target === target && selected.index === index;
    var hidden = data && (data.isVisible === false || data.visible === false);
    var drag = target === "section" ? ' draggable="true" data-pve-rail-drag' : "";
    var dup = target === "section"
      ? '<button type="button" class="pve-rail-btn" data-pve-rail-dup title="คัดลอก" aria-label="คัดลอก">' + railIcon("dup") + "</button>"
      : "";
    var del = target === "section"
      ? '<button type="button" class="pve-rail-btn pve-rail-btn--danger" data-pve-rail-del title="ลบ" aria-label="ลบ">' + railIcon("del") + "</button>"
      : "";
    var vis =
      '<button type="button" class="pve-rail-btn' + (hidden ? " is-off" : "") + '" data-pve-rail-vis title="แสดง/ซ่อน" aria-label="แสดง/ซ่อน">' +
      railIcon(hidden ? "eyeOff" : "eye") + "</button>";
    var handle = target === "section"
      ? '<span class="pve-rail-handle" title="ลากสลับ" aria-hidden="true">' + railIcon("drag") + "</span>"
      : '<span class="pve-rail-handle pve-rail-handle--static"></span>';
    return (
      '<li class="pve-rail-item' + (isSel ? " is-selected" : "") + (hidden ? " is-hidden" : "") + '" data-pe-target="' + esc(target) + '"' +
      (index != null ? ' data-pe-index="' + index + '"' : "") + drag + ">" +
      handle +
      '<button type="button" class="pve-rail-label" data-pve-rail-select>' + esc(label) + "</button>" +
      '<div class="pve-rail-actions">' + vis + dup + del + "</div></li>"
    );
  }

  function renderAddSelectOptions(catalog) {
    var html = "";
    BLOCK_GROUPS.forEach(function (g) {
      html += '<optgroup label="' + esc(g.label) + '">';
      g.types.forEach(function (t) {
        html += '<option value="' + esc(t) + '">' + esc(blockLabel(t, catalog)) + "</option>";
      });
      html += "</optgroup>";
    });
    return html;
  }

  return {
    IMAGE_HINTS: IMAGE_HINTS,
    BLOCK_GROUPS: BLOCK_GROUPS,
    TOOL_BLOCKS: TOOL_BLOCKS,
    PLAN_TOOL_BLOCKS: PLAN_TOOL_BLOCKS,
    defaultBlock: defaultBlock,
    defaultItem: defaultItem,
    blockLabel: blockLabel,
    buildForm: buildForm,
    buildHeroForm: buildHeroForm,
    buildCtaForm: buildCtaForm,
    readForm: readForm,
    ensureFeaturedBullets: ensureFeaturedBullets,
    defaultRepeaterItem: defaultRepeaterItem,
    getRepeaterArray: getRepeaterArray,
    hasItemsRepeater: hasItemsRepeater,
    renderSectionRail: renderSectionRail,
    renderToolsPalette: renderToolsPalette,
    renderAddSelectOptions: renderAddSelectOptions,
    imageField: imageField,
    field: field,
    input: input,
    textarea: textarea,
  };
})();
