(function () {
  var id = document.body.getAttribute("data-plan-id");
  var plan = window.PLANS_DETAIL && window.PLANS_DETAIL[id];
  if (!plan) return;

  document.title = plan.title + " | แผนประกัน";
  var meta = document.querySelector('meta[name="description"]');
  if (meta) meta.setAttribute("content", plan.description);

  var heroInner = document.getElementById("plan-hero-inner");
  if (heroInner) {
    heroInner.innerHTML =
      '<p class="breadcrumb"><a href="../plans.html">แผนประกัน</a> / ' +
      plan.breadcrumb +
      "</p><h1>" +
      plan.title +
      "</h1><p>" +
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

  var faqHtml = plan.faq
    .map(function (item) {
      return (
        "<details><summary>" +
        item.q +
        "</summary><p>" +
        item.a +
        "</p></details>"
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

  var root = document.getElementById("plan-detail-root");
  if (root) {
    root.innerHTML =
      '<div class="plan-detail-layout">' +
      '<aside class="plan-sidebar">' +
      '<nav aria-label="สารบัญ">' +
      '<a href="#overview" class="active">ภาพรวม</a>' +
      '<a href="#benefits">จุดเด่น</a>' +
      '<a href="#specs">ข้อมูลแผน</a>' +
      '<a href="#who">เหมาะกับใคร</a>' +
      '<a href="#faq">คำถามที่พบบ่อย</a>' +
      "</nav>" +
      '<p style="margin-top:1.5rem;font-size:0.875rem"><a href="../plans.html">← กลับรายการแผน</a></p>' +
      "</aside>" +
      '<div class="plan-content">' +
      '<section id="overview">' +
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
  }

  var cta = document.getElementById("plan-cta");
  if (cta) {
    cta.innerHTML =
      "<h2>" +
      plan.ctaTitle +
      "</h2>" +
      (plan.ctaLead ? "<p>" + plan.ctaLead + "</p>" : "") +
      '<div class="cta-actions">' +
      '<a href="../contact.html" class="btn btn-primary">ขอใบเสนอเบี้ย</a>' +
      '<a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>' +
      "</div>";
  }
})();
