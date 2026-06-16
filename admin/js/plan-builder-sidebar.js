(function () {
  var api = null;
  var dragPayload = null;
  var suppressClick = false;

  var SVG_ATTR =
    'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"';

  var ICONS = {
    brochure:
      "<svg " +
      SVG_ATTR +
      '><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="9" y1="17" x2="13" y2="17"/></svg>',
    overview:
      "<svg " +
      SVG_ATTR +
      '><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>',
    benefits:
      "<svg " +
      SVG_ATTR +
      '><path d="m12 3-1.9 3.8-4.2.6 3 3-0.7 4.2L12 13l3.8 2-0.7-4.2 3-3-4.2-.6L12 3z"/></svg>',
    specs:
      "<svg " +
      SVG_ATTR +
      '><rect x="3" y="4" width="18" height="16" rx="2"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="9" y1="4" x2="9" y2="20"/></svg>',
    who:
      "<svg " +
      SVG_ATTR +
      '><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    faq:
      "<svg " +
      SVG_ATTR +
      '><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    text:
      "<svg " +
      SVG_ATTR +
      '><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    heading:
      "<svg " +
      SVG_ATTR +
      '><path d="M4 12h8"/><path d="M4 18V6"/><path d="M12 18V6"/><path d="M17 10v8"/><path d="M21 10v8"/><path d="M17 14h4"/></svg>',
    image:
      "<svg " +
      SVG_ATTR +
      '><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
    video:
      "<svg " +
      SVG_ATTR +
      '><rect x="2" y="5" width="20" height="14" rx="2"/><polygon points="10 9 16 12 10 15 10 9" fill="currentColor" stroke="none"/></svg>',
    "brochure-image":
      "<svg " +
      SVG_ATTR +
      '><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
    "list-item":
      "<svg " +
      SVG_ATTR +
      '><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="4" cy="18" r="1" fill="currentColor" stroke="none"/></svg>',
    "spec-row":
      "<svg " +
      SVG_ATTR +
      '><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/><line x1="9" y1="6" x2="9" y2="18"/></svg>',
    "who-block":
      "<svg " +
      SVG_ATTR +
      '><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="7" y1="8" x2="17" y2="8"/><line x1="7" y1="12" x2="17" y2="12"/><line x1="7" y1="16" x2="13" y2="16"/></svg>',
    "faq-item":
      "<svg " +
      SVG_ATTR +
      '><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><path d="M9.5 9a2.5 2.5 0 1 1 4.5 1.5c0 1.5-2 2-2 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    drag:
      "<svg " +
      SVG_ATTR +
      '><circle cx="9" cy="5" r="1" fill="currentColor" stroke="none"/><circle cx="9" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="9" cy="19" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="5" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1" fill="currentColor" stroke="none"/><circle cx="15" cy="19" r="1" fill="currentColor" stroke="none"/></svg>',
  };

  var BLOCK_GROUPS = [
    {
      title: "ส่วนหลัก",
      subtitle: "เพิ่มส่วนบนกระดาษ — ลากหรือคลิก",
      blocks: [
        { type: "section", section: "brochure", icon: "brochure", accent: "blue", label: "โบรชัวร์" },
        { type: "section", section: "overview", icon: "overview", accent: "teal", label: "ภาพรวม" },
        { type: "section", section: "benefits", icon: "benefits", accent: "amber", label: "จุดเด่น" },
        { type: "section", section: "specs", icon: "specs", accent: "slate", label: "ข้อมูลแผน" },
        { type: "section", section: "who", icon: "who", accent: "violet", label: "เหมาะกับใคร" },
        { type: "section", section: "faq", icon: "faq", accent: "rose", label: "FAQ" },
      ],
    },
    {
      title: "เพิ่มเนื้อหา",
      subtitle: "ใช้หลังสร้างส่วนหลักแล้ว — ภาพปกและจุดขายแก้ในส่วนภาพรวมโดยตรง",
      subgroups: [
        {
          label: "ในภาพรวม",
          blocks: [
            { type: "text", target: "overview", icon: "text", accent: "teal", label: "ข้อความ" },
            { type: "heading", target: "overview", icon: "heading", accent: "indigo", label: "หัวข้อ H3" },
            { type: "image", target: "overview", icon: "image", accent: "teal", label: "รูปในเนื้อหา" },
            { type: "video", target: "overview", icon: "video", accent: "red", label: "วิดีโอ" },
          ],
        },
        {
          label: "ในโบรชัวร์",
          blocks: [
            { type: "brochure-image", target: "brochure", icon: "brochure-image", accent: "blue", label: "เพิ่มภาพหน้า" },
          ],
        },
        {
          label: "ในจุดเด่น",
          blocks: [
            { type: "list-item", target: "benefits", icon: "list-item", accent: "amber", label: "เพิ่มรายการ" },
          ],
        },
        {
          label: "ในข้อมูลแผน",
          blocks: [
            { type: "spec-row", target: "specs", icon: "spec-row", accent: "slate", label: "เพิ่มแถว" },
          ],
        },
        {
          label: "ในเหมาะกับใคร",
          blocks: [
            { type: "who-block", target: "who", icon: "who-block", accent: "violet", label: "เพิ่มบล็อก" },
          ],
        },
        {
          label: "ใน FAQ",
          blocks: [
            { type: "faq-item", target: "faq", icon: "faq-item", accent: "rose", label: "เพิ่มคำถาม" },
          ],
        },
      ],
    },
  ];

  function blockChipHtml(b) {
    return (
      '<button type="button" class="pe-block-chip pe-block-chip--' +
      b.accent +
      '" draggable="true" data-block-type="' +
      b.type +
      '"' +
      (b.section ? ' data-block-section="' + b.section + '"' : "") +
      (b.target ? ' data-block-target="' + b.target + '"' : "") +
      '><span class="pe-block-chip-icon" aria-hidden="true">' +
      iconHtml(b.icon) +
      '</span><span class="pe-block-chip-body"><span class="pe-block-chip-label">' +
      b.label +
      '</span><span class="pe-block-chip-meta">ลากวาง</span></span></button>'
    );
  }

  function iconHtml(name) {
    return ICONS[name] || ICONS.text;
  }

  function buildBlocksHtml() {
    return BLOCK_GROUPS.map(function (group) {
      var body = "";

      if (group.blocks) {
        body =
          '<div class="pe-block-grid">' +
          group.blocks.map(blockChipHtml).join("") +
          "</div>";
      } else if (group.subgroups) {
        body = group.subgroups
          .map(function (sub) {
            return (
              '<div class="pe-block-subgroup">' +
              '<div class="pe-block-subgroup-label">' +
              sub.label +
              "</div>" +
              '<div class="pe-block-grid">' +
              sub.blocks.map(blockChipHtml).join("") +
              "</div></div>"
            );
          })
          .join("");
      }

      return (
        '<section class="pe-block-group">' +
        '<header class="pe-block-group-head">' +
        '<h3 class="pe-block-group-title">' +
        group.title +
        "</h3>" +
        (group.subtitle ? '<p class="pe-block-group-sub">' + group.subtitle + "</p>" : "") +
        "</header>" +
        body +
        "</section>"
      );
    }).join("");
  }

  function render() {
    var blocksEl = document.getElementById("pe-builder-blocks");
    if (!blocksEl || !api) return;
    blocksEl.innerHTML =
      '<div class="pe-builder-hint">' +
      '<span class="pe-builder-hint-icon">' +
      iconHtml("drag") +
      "</span>" +
      "<p>ลากบล็อกไปวางบนกระดาษด้านขวา หรือคลิกเพื่อเพิ่มทันที</p></div>" +
      buildBlocksHtml();
  }

  function youtubeEmbed(url) {
    var id = "";
    var m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]{11})/);
    if (m) id = m[1];
    if (!id) return null;
    return (
      '<div class="plan-video-embed"><iframe src="https://www.youtube.com/embed/' +
      id +
      '" title="วิดีโอ" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe></div>'
    );
  }

  function ensureSection(sectionId) {
    var detail = api.getState().detail;
    if (!detail.sectionOrder) detail.sectionOrder = api.DEFAULT_SECTIONS.slice();
    if (detail.sectionOrder.indexOf(sectionId) < 0) {
      detail.sectionOrder.push(sectionId);
    }
  }

  function appendOverview(html) {
    var detail = api.getState().detail;
    detail.overview = (detail.overview || "") + html;
  }

  function applyBlock(blockType, targetSection) {
    var detail = api.getState().detail;

    if (blockType === "section") {
      ensureSection(targetSection);
      api.renderAll();
      api.scrollToLayer("section-" + targetSection);
      return;
    }

    ensureSection(targetSection);

    if (blockType === "text") {
      appendOverview("<p>ข้อความใหม่ — คลิกเพื่อแก้ไข</p>");
    } else if (blockType === "heading") {
      appendOverview("<h3>หัวข้อใหม่</h3>");
    } else if (blockType === "list-item") {
      if (!detail.benefits) detail.benefits = [];
      detail.benefits.push("ข้อใหม่ — คลิกเพื่อแก้ไข");
    } else if (blockType === "spec-row") {
      if (!detail.specs) detail.specs = [];
      detail.specs.push(["หัวข้อ", "รายละเอียด"]);
    } else if (blockType === "who-block") {
      if (!detail.whoBlocks) detail.whoBlocks = [];
      detail.whoBlocks.push({ title: "หัวข้อ", text: "เนื้อหา" });
    } else if (blockType === "faq-item") {
      if (!detail.faq) detail.faq = [];
      detail.faq.push({ q: "คำถามใหม่", a: "คำตอบ" });
    } else if (blockType === "brochure-image") {
      api.uploadImage("plan_brochure", function (path) {
        if (!detail.brochureImages) detail.brochureImages = [];
        detail.brochureImages.push(path);
        api.renderAll();
      });
      return;
    } else if (blockType === "image") {
      api.uploadImage("plan_content", function (path) {
        appendOverview(
          '<figure class="plan-inline-figure"><img src="../' +
            api.encodePath(path) +
            '" alt="" loading="lazy"></figure>'
        );
        api.renderAll();
      });
      return;
    } else if (blockType === "video") {
      var url = window.prompt("URL วิดีโอ YouTube:", "https://www.youtube.com/watch?v=");
      if (!url) return;
      var embed = youtubeEmbed(url);
      if (!embed) {
        window.alert("ไม่รองรับ URL นี้ — ใช้ลิงก์ YouTube");
        return;
      }
      appendOverview(embed);
    }

    api.renderAll();
    if (targetSection) api.scrollToLayer("section-" + targetSection);
  }

  function setStatus(msg, type) {
    var el = document.getElementById("pe-status");
    if (!el) return;
    el.textContent = msg;
    el.className = "pe-status" + (type ? " is-" + type : "");
    if (type === "error") {
      window.setTimeout(function () {
        if (el.classList.contains("is-error")) el.textContent = "";
      }, 3000);
    }
  }

  function bindSidebarEvents() {
    var sidebar = document.getElementById("pe-builder-sidebar");
    if (!sidebar || sidebar.dataset.eventsBound) return;
    sidebar.dataset.eventsBound = "1";

    sidebar.addEventListener("dragstart", function (e) {
      var chip = e.target.closest(".pe-block-chip");
      if (!chip) return;
      dragPayload = {
        type: chip.getAttribute("data-block-type"),
        section: chip.getAttribute("data-block-section"),
        target: chip.getAttribute("data-block-target"),
      };
      e.dataTransfer.effectAllowed = "copy";
      e.dataTransfer.setData("text/plain", dragPayload.type);
      chip.classList.add("is-dragging");
      suppressClick = false;
    });

    sidebar.addEventListener("drag", function () {
      suppressClick = true;
    });

    sidebar.addEventListener("dragend", function () {
      dragPayload = null;
      sidebar.querySelectorAll(".is-dragging").forEach(function (el) {
        el.classList.remove("is-dragging");
      });
      document.querySelectorAll(".pe-drop-active").forEach(function (el) {
        el.classList.remove("pe-drop-active");
      });
      window.setTimeout(function () {
        suppressClick = false;
      }, 80);
    });

    sidebar.addEventListener("click", function (e) {
      if (suppressClick) return;
      var chip = e.target.closest(".pe-block-chip");
      if (!chip) return;
      var payload = {
        type: chip.getAttribute("data-block-type"),
        section: chip.getAttribute("data-block-section"),
        target: chip.getAttribute("data-block-target"),
      };
      if (payload.type === "section") {
        applyBlock("section", payload.section);
      } else {
        applyBlock(payload.type, payload.target);
      }
    });
  }

  function bindCanvasDrop() {
    var canvas = document.getElementById("pe-canvas-wrap");
    if (!canvas || canvas.dataset.dropBound) return;
    canvas.dataset.dropBound = "1";

    canvas.addEventListener("dragover", function (e) {
      if (!dragPayload) return;
      e.preventDefault();
      var zone = e.target.closest(".pe-drop-zone, .pe-section[data-section-id]");
      if (zone) zone.classList.add("pe-drop-active");
    });

    canvas.addEventListener("dragleave", function (e) {
      var zone = e.target.closest(".pe-drop-zone, .pe-section");
      if (zone) zone.classList.remove("pe-drop-active");
    });

    canvas.addEventListener("drop", function (e) {
      if (!dragPayload) return;
      var zone = e.target.closest(".pe-drop-zone, .pe-section[data-section-id]");
      if (!zone) return;
      e.preventDefault();
      zone.classList.remove("pe-drop-active");

      var targetSec = zone.getAttribute("data-drop-section") || zone.getAttribute("data-section-id");
      if (dragPayload.type === "section") {
        applyBlock("section", dragPayload.section);
      } else {
        var expected = dragPayload.target;
        if (targetSec && expected && targetSec !== expected) {
          setStatus("วางบล็อกนี้ในส่วน「" + (api.SECTION_LABELS[expected] || expected) + "」", "error");
          return;
        }
        applyBlock(dragPayload.type, expected || targetSec);
      }
      dragPayload = null;
    });
  }

  function init(planApi) {
    api = planApi;
    bindSidebarEvents();
    bindCanvasDrop();
    render();
  }

  window.PlanBuilderSidebar = {
    init: init,
    render: render,
    applyBlock: applyBlock,
  };
})();
