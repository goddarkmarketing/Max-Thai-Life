(function () {
  var id = document.body.getAttribute("data-plan-id");
  var plan = window.PLANS_DETAIL && window.PLANS_DETAIL[id];
  if (!plan) return;

  var isRich = plan.editor === "richtext" && typeof plan.bodyHtml === "string";

  var cardImage = "";

  (window.PLANS_DATA || []).forEach(function (card) {
    var slug = (card.href || "").replace(/^plans\//, "").replace(/\.html$/, "");
    if (slug === id && card.image) {
      cardImage = card.image;
      if (!(plan.sections && plan.sections.length)) {
        plan.image = card.image;
      }
    }
  });

  function encodePlanPath(path) {
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

  function plainText(html) {
    var d = document.createElement("div");
    d.innerHTML = html || "";
    return (d.textContent || "").trim();
  }

  document.title = plainText(plan.title) + " | แผนประกัน";
  var meta = document.querySelector('meta[name="description"]');
  if (meta) meta.setAttribute("content", plainText(plan.description || plan.heroLead));

  var hero = document.querySelector("header.page-hero");
  if (hero) hero.classList.add("page-hero--plan");

  var heroInner = document.getElementById("plan-hero-inner");
  if (heroInner) {
    heroInner.innerHTML =
      '<div class="breadcrumb"><a href="../plans.html">แผนประกัน</a> / ' +
      plan.breadcrumb +
      '</div><span class="page-hero-eyebrow">แผนประกันไทยประกันชีวิต</span><h1>' +
      plan.title +
      '</h1><p class="page-hero-lead">' +
      plan.heroLead +
      "</p>";
  }

  var benefitsListHtml = (plan.benefits || [])
    .map(function (b) {
      return "<li>" + b + "</li>";
    })
    .join("");

  var specsListHtml = (plan.specs || [])
    .map(function (row) {
      return "<tr><th>" + row[0] + "</th><td>" + row[1] + "</td></tr>";
    })
    .join("");

  var faqChevron =
    '<span class="faq-item__icon" aria-hidden="true">' +
    (window.LucideIcons
      ? LucideIcons.icon("chevron-down", { size: 20, strokeWidth: 2.25 })
      : '<i data-lucide="chevron-down" aria-hidden="true"></i>') +
    "</span>";

  var faqListHtml = (plan.faq || [])
    .map(function (item) {
      return (
        '<details class="faq-item">' +
        '<summary class="faq-item__summary">' +
        '<div class="faq-item__question">' +
        item.q +
        "</div>" +
        faqChevron +
        "</summary>" +
        '<div class="faq-item__answer"><div class="faq-item__body">' +
        item.a +
        "</div></div></details>"
      );
    })
    .join("");

  var whoHtml = "";
  if (plan.whoBlocks) {
    whoHtml =
      '<div class="two-col-blocks">' +
      plan.whoBlocks
        .map(function (block) {
          return (
            '<div class="info-block"><h4>' +
            block.title +
            "</h4><div class=\"info-block__text\">" +
            block.text +
            "</div></div>"
          );
        })
        .join("") +
      "</div>";
  } else if (plan.whoText) {
    whoHtml = "<p>" + plan.whoText + "</p>";
  }

  function buildGalleryHtml() {
    if (!plan.brochureImages || !plan.brochureImages.length) return "";
    var title = plan.title.replace(/"/g, "&quot;");
    var items = plan.brochureImages
      .map(function (src, index) {
        return (
          '<figure class="plan-gallery-item">' +
          '<img src="../' +
          encodePlanPath(src) +
          '" alt="' +
          title +
          " รูป " +
          (index + 1) +
          '" loading="lazy" decoding="async">' +
          "</figure>"
        );
      })
      .join("");
    return '<div class="plan-image-gallery">' + items + "</div>";
  }

  function buildOverviewMediaHtml() {
    var blocks = plan.overviewBlocks;
    if (blocks && blocks.length) {
      var title = plan.title.replace(/"/g, "&quot;");
      return blocks
        .map(function (block) {
          if (block.type === "text" && block.html) {
            return '<div class="plan-overview-inline-text">' + block.html + "</div>";
          }
          if (block.type === "image" && block.src) {
            if (block.cover) {
              return (
                '<figure class="plan-section-cover">' +
                '<img src="../' +
                encodePlanPath(block.src) +
                '" alt="' +
                title +
                '" width="960" height="540" loading="lazy" decoding="async">' +
                "</figure>"
              );
            }
            return (
              '<figure class="plan-gallery-item">' +
              '<img src="../' +
              encodePlanPath(block.src) +
              '" alt="' +
              title +
              " รูป" +
              '" loading="lazy" decoding="async">' +
              "</figure>"
            );
          }
          return "";
        })
        .join("");
    }
    return buildGalleryHtml();
  }

  function buildOverviewSection() {
    var mediaHtml = buildOverviewMediaHtml();
    var hasOrderedMedia = plan.overviewBlocks && plan.overviewBlocks.length;
    var isHomeCardImage = plan.image && plan.image.indexOf("images/plan-cards/") === 0;
    var coverHtml = "";
    if (!hasOrderedMedia && plan.image) {
      coverHtml =
        '<figure class="plan-section-cover">' +
        '<img src="../' +
        encodePlanPath(plan.image) +
        '" alt="' +
        plan.title.replace(/"/g, "&quot;") +
        '" width="960" height="540" loading="lazy" decoding="async">' +
        "</figure>";
    }
    var galleryWrap = "";
    if (!hasOrderedMedia && mediaHtml && !isHomeCardImage) {
      galleryWrap = '<div class="plan-image-gallery">' + mediaHtml + "</div>";
    } else if (hasOrderedMedia) {
      galleryWrap = mediaHtml ? '<div class="plan-image-gallery">' + mediaHtml + "</div>" : "";
    }

    var bodyHtml =
      "<h2>ภาพรวมแผน</h2>" +
      '<div class="plan-overview-body">' +
      plan.overview +
      "</div>" +
      (plan.highlight
        ? '<div class="plan-highlight-box"><strong>จุดขายหลัก:</strong> ' +
          plan.highlight +
          "</div>"
        : "");

    var mediaBlock = coverHtml + galleryWrap;
    var singleItem =
      (hasOrderedMedia && plan.overviewBlocks.length === 1) || (!hasOrderedMedia && !!plan.image);
    var mediaAfter = !!plan.overviewMediaAfterContent && singleItem;

    return (
      '<section id="overview">' +
      (mediaAfter ? bodyHtml + mediaBlock : mediaBlock + bodyHtml) +
      "</section>"
    );
  }

  function buildBenefitsSection() {
    return (
      '<section id="benefits">' +
      "<h2>จุดเด่นและผลประโยชน์</h2>" +
      "<ul>" +
      benefitsListHtml +
      "</ul></section>"
    );
  }

  function buildSpecsSection() {
    return (
      '<section id="specs">' +
      "<h2>ข้อมูลแผน (ภาพรวม)</h2>" +
      '<table class="plan-spec-table">' +
      specsListHtml +
      "</table></section>"
    );
  }

  function buildWhoSection() {
    return '<section id="who"><h2>เหมาะกับใคร</h2>' + whoHtml + "</section>";
  }

  function buildFaqSection() {
    return (
      '<section id="faq" class="plan-faq">' +
      "<h2>คำถามที่พบบ่อย</h2>" +
      faqListHtml +
      "</section>"
    );
  }

  var DEFAULT_SECTION_ORDER = ["overview", "benefits", "specs", "who", "faq"];
  var NAV_LABELS = {
    overview: "ภาพรวม",
    benefits: "จุดเด่น",
    specs: "ข้อมูลแผน",
    who: "เหมาะกับใคร",
    faq: "คำถามที่พบบ่อย",
  };

  var sectionBuilders = {
    overview: buildOverviewSection,
    benefits: buildBenefitsSection,
    specs: buildSpecsSection,
    who: buildWhoSection,
    faq: buildFaqSection,
  };

  var contentHtml = "";
  var navLinks = "";

  if (plan.sections && plan.sections.length && window.PageBlockRender) {
    var R = PageBlockRender;
    var ctx = {
      base: "../",
      agent: (window.SITE_DATA && window.SITE_DATA.agent) || {},
      meta: {},
      cardImage: cardImage,
    };
    contentHtml = R.renderPlanSections(plan.sections, ctx);
    navLinks = R.planNavEntries(plan.sections)
      .map(function (entry, index) {
        return (
          '<a href="#' +
          entry.anchor +
          '"' +
          (index === 0 ? ' class="active"' : "") +
          ">" +
          entry.label +
          "</a>"
        );
      })
      .join("");
  } else {
    var sectionOrder = (plan.sectionOrder || DEFAULT_SECTION_ORDER.slice()).filter(function (id) {
      return id !== "brochure";
    });
    contentHtml = sectionOrder
      .map(function (id) {
        var build = sectionBuilders[id];
        return build ? build() : "";
      })
      .filter(function (html) {
        return html !== "";
      })
      .join("");
    navLinks = sectionOrder
      .map(function (id, index) {
        return (
          '<a href="#' +
          id +
          '"' +
          (index === 0 ? ' class="active"' : "") +
          ">" +
          NAV_LABELS[id] +
          "</a>"
        );
      })
      .join("");
  }

  var root = document.getElementById("plan-detail-root");
  if (root) {
    if (isRich) {
      root.innerHTML =
        '<div class="lp-rich ql-content plan-rich-detail">' +
        plan.bodyHtml +
        "</div>";
      if (window.LucideIcons) LucideIcons.refresh(root);
    } else {
      root.innerHTML =
        '<div class="plan-detail-layout">' +
        '<aside class="plan-sidebar">' +
        '<nav aria-label="สารบัญ">' +
        navLinks +
        "</nav>" +
        '<p style="margin-top:1.5rem;font-size:0.875rem"><a href="../plans.html">← กลับรายการแผน</a></p>' +
        "</aside>" +
        '<div class="plan-content">' +
        contentHtml +
        '<div class="plan-disclaimer">' +
        plan.disclaimer +
        "</div>" +
        "</div></div>";

      initPlanSidebarNav(root);
      if (window.LucideIcons) LucideIcons.refresh(root);
    }
  }

  function initPlanSidebarNav(layoutRoot) {
    var nav = layoutRoot.querySelector(".plan-sidebar nav");
    if (!nav) return;

    var links = nav.querySelectorAll('a[href^="#"]');
    var sections = [];

    links.forEach(function (link) {
      var id = link.getAttribute("href").slice(1);
      var section = document.getElementById(id);
      if (section) sections.push({ id: id, el: section });
    });

    function setActive(id) {
      links.forEach(function (link) {
        var isActive = link.getAttribute("href") === "#" + id;
        link.classList.toggle("active", isActive);
        if (isActive) link.setAttribute("aria-current", "true");
        else link.removeAttribute("aria-current");
      });
    }

    function headerOffset() {
      var h = getComputedStyle(document.documentElement).getPropertyValue("--header-h");
      return (parseInt(h, 10) || 72) + 20;
    }

    links.forEach(function (link) {
      link.addEventListener("click", function (e) {
        var id = link.getAttribute("href").slice(1);
        var target = document.getElementById(id);
        if (!target) return;
        e.preventDefault();
        setActive(id);
        var top = target.getBoundingClientRect().top + window.scrollY - headerOffset();
        window.scrollTo({ top: top, behavior: "smooth" });
      });
    });

    function updateActiveFromScroll() {
      if (!sections.length) return;
      var offset = headerOffset();
      var scrollY = window.scrollY + offset;
      var current = sections[0].id;

      sections.forEach(function (section) {
        if (section.el.offsetTop <= scrollY + 8) current = section.id;
      });

      setActive(current);
    }

    var scrollTimer;
    window.addEventListener(
      "scroll",
      function () {
        window.clearTimeout(scrollTimer);
        scrollTimer = window.setTimeout(updateActiveFromScroll, 60);
      },
      { passive: true }
    );

    updateActiveFromScroll();
  }

  var cta = document.getElementById("plan-cta");
  if (cta) {
    var ctaButtons = plan.ctaButtons;
    var actionsHtml = "";
    if (ctaButtons && ctaButtons.length) {
      actionsHtml = ctaButtons
        .map(function (btn) {
          var label = btn.label || btn.buttonText || "";
          var href = btn.href || btn.buttonLink || "../contact.html";
          var cls =
            btn.variant === "outline"
              ? "btn btn-outline"
              : btn.variant === "white"
                ? "btn btn-white"
                : "btn btn-primary";
          return '<a href="' + href + '" class="' + cls + '">' + label + "</a>";
        })
        .join("");
    } else {
      actionsHtml =
        '<a href="../contact.html" class="btn btn-white">ขอใบเสนอเบี้ย</a>' +
        '<a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>';
    }
    cta.innerHTML =
      "<h2>" +
      plan.ctaTitle +
      "</h2>" +
      (plan.ctaLead ? "<p>" + plan.ctaLead + "</p>" : "") +
      '<div class="cta-actions">' +
      actionsHtml +
      "</div>";
  }
})();
