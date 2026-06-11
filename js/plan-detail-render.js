(function () {
  var id = document.body.getAttribute("data-plan-id");
  var plan = window.PLANS_DETAIL && window.PLANS_DETAIL[id];
  if (!plan) return;

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

  document.title = plan.title + " | แผนประกัน";
  var meta = document.querySelector('meta[name="description"]');
  if (meta) meta.setAttribute("content", plan.description);

  var hero = document.querySelector("header.page-hero");
  if (hero) hero.classList.add("page-hero--plan");

  var heroInner = document.getElementById("plan-hero-inner");
  if (heroInner) {
    heroInner.innerHTML =
      '<p class="breadcrumb"><a href="../plans.html">แผนประกัน</a> / ' +
      plan.breadcrumb +
      '</p><span class="page-hero-eyebrow">แผนประกันไทยประกันชีวิต</span><h1>' +
      plan.title +
      '</h1><p class="page-hero-lead">' +
      plan.heroLead +
      "</p>";
  }

  var benefitsHtml = plan.benefits
    .map(function (b) {
      return "<li>" + b + "</li>";
    })
    .join("");

  var specsHtml = plan.specs
    .map(function (row) {
      return "<tr><th>" + row[0] + "</th><td>" + row[1] + "</td></tr>";
    })
    .join("");

  var faqChevron =
    '<span class="faq-item__icon" aria-hidden="true">' +
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M6 9l6 6 6-6"/>' +
    "</svg></span>";

  var faqHtml = plan.faq
    .map(function (item) {
      return (
        '<details class="faq-item">' +
        '<summary class="faq-item__summary">' +
        '<span class="faq-item__question">' +
        item.q +
        "</span>" +
        faqChevron +
        "</summary>" +
        '<div class="faq-item__answer"><p>' +
        item.a +
        "</p></div></details>"
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
            "</h4><p>" +
            block.text +
            "</p></div>"
          );
        })
        .join("") +
      "</div>";
  } else if (plan.whoText) {
    whoHtml = "<p>" + plan.whoText + "</p>";
  }

  function brochureHtml() {
    if (!plan.brochureImages || !plan.brochureImages.length) return "";
    var title = plan.title.replace(/"/g, "&quot;");
    var items = plan.brochureImages
      .map(function (src, index) {
        return (
          '<figure class="plan-brochure-item">' +
          '<img src="../' +
          encodePlanPath(src) +
          '" alt="' +
          title +
          " หน้า " +
          (index + 1) +
          '" loading="lazy" decoding="async">' +
          "</figure>"
        );
      })
      .join("");
    return (
      '<section id="brochure" class="plan-brochure">' +
      '<div class="plan-brochure-gallery">' +
      items +
      "</div></section>"
    );
  }

  var brochureNavLink =
    plan.brochureImages && plan.brochureImages.length
      ? '<a href="#brochure">รายละเอียดโบรชัวร์</a>'
      : "";

  var root = document.getElementById("plan-detail-root");
  if (root) {
    var coverHtml = plan.image
      ? '<figure class="plan-section-cover">' +
        '<img src="../' +
        encodePlanPath(plan.image) +
        '" alt="' +
        plan.title.replace(/"/g, "&quot;") +
        '" width="960" height="540" loading="lazy" decoding="async">' +
        "</figure>"
      : "";

    root.innerHTML =
      '<div class="plan-detail-layout">' +
      '<aside class="plan-sidebar">' +
      '<nav aria-label="สารบัญ">' +
      brochureNavLink +
      '<a href="#overview" class="active">ภาพรวม</a>' +
      '<a href="#benefits">จุดเด่น</a>' +
      '<a href="#specs">ข้อมูลแผน</a>' +
      '<a href="#who">เหมาะกับใคร</a>' +
      '<a href="#faq">คำถามที่พบบ่อย</a>' +
      "</nav>" +
      '<p style="margin-top:1.5rem;font-size:0.875rem"><a href="../plans.html">← กลับรายการแผน</a></p>' +
      "</aside>" +
      '<div class="plan-content">' +
      brochureHtml() +
      '<section id="overview">' +
      coverHtml +
      "<h2>ภาพรวมแผน</h2>" +
      "<p>" +
      plan.overview +
      "</p>" +
      (plan.highlight
        ? '<div class="plan-highlight-box"><strong>จุดขายหลัก:</strong> ' +
          plan.highlight +
          "</div>"
        : "") +
      "</section>" +
      '<section id="benefits">' +
      "<h2>จุดเด่นและผลประโยชน์</h2>" +
      "<ul>" +
      benefitsHtml +
      "</ul>" +
      "</section>" +
      '<section id="specs">' +
      "<h2>ข้อมูลแผน (ภาพรวม)</h2>" +
      '<table class="plan-spec-table">' +
      specsHtml +
      "</table>" +
      "</section>" +
      '<section id="who">' +
      "<h2>เหมาะกับใคร</h2>" +
      whoHtml +
      "</section>" +
      '<section id="faq" class="plan-faq">' +
      "<h2>คำถามที่พบบ่อย</h2>" +
      faqHtml +
      "</section>" +
      '<p class="plan-disclaimer">' +
      plan.disclaimer +
      "</p>" +
      "</div></div>";

    initPlanSidebarNav(root);
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
    cta.innerHTML =
      "<h2>" +
      plan.ctaTitle +
      "</h2>" +
      (plan.ctaLead ? "<p>" + plan.ctaLead + "</p>" : "") +
      '<div class="cta-actions">' +
      '<a href="../contact.html" class="btn btn-white">ขอใบเสนอเบี้ย</a>' +
      '<a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>' +
      "</div>";
  }
})();
