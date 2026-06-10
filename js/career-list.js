(function () {
  var grid = document.getElementById("career-card-grid");
  var featured = document.getElementById("career-featured");
  if (!window.CAREERS_DETAIL) return;

  var base = document.body.getAttribute("data-base") || "";

  function imgSrc(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return base + src;
  }

  function cardHtml(item, linkLabel) {
    var stats = "";
    if (item.views) {
      stats =
        '<p class="product-card-stats">' +
        item.views.toLocaleString("th-TH") +
        " views";
      if (item.shares) stats += " · " + item.shares + " shares";
      stats += "</p>";
    }

    return (
      "<li>" +
      '<article class="product-card">' +
      '<a href="careers/' +
      item.slug +
      '.html" class="product-card-media" tabindex="-1" aria-hidden="true">' +
      '<img src="' +
      imgSrc(item.image) +
      '" alt="' +
      item.title.replace(/"/g, "&quot;") +
      '" loading="lazy" decoding="async">' +
      "</a>" +
      '<div class="product-card-body">' +
      '<p class="product-card-meta">' +
      item.category +
      "</p>" +
      "<h3><a href=\"careers/" +
      item.slug +
      '.html">' +
      item.title +
      "</a></h3>" +
      '<p class="product-card-excerpt">' +
      item.description +
      "</p>" +
      stats +
      '<a href="careers/' +
      item.slug +
      '.html" class="product-card-link">' +
      (linkLabel || "อ่านต่อ →") +
      "</a>" +
      "</div></article></li>"
    );
  }

  if (grid && window.CAREERS_LIST) {
    grid.innerHTML = window.CAREERS_LIST.map(function (slug) {
      return cardHtml(window.CAREERS_DETAIL[slug]);
    }).join("");
  }

  if (featured) {
    var feat = window.CAREERS_DETAIL["digital-agent-system"];
    if (feat) {
      featured.innerHTML =
        '<div class="career-featured-layout">' +
        '<a href="careers/' +
        feat.slug +
        '.html" class="career-featured-media">' +
        '<img src="' +
        imgSrc(feat.image) +
        '" alt="' +
        feat.title.replace(/"/g, "&quot;") +
        '" loading="lazy" decoding="async" width="640" height="427">' +
        "</a>" +
        '<div class="career-featured-body">' +
        '<p class="product-card-meta">' +
        feat.category +
        "</p>" +
        "<h2><a href=\"careers/" +
        feat.slug +
        '.html">' +
        feat.title +
        "</a></h2>" +
        "<p>" +
        feat.description +
        "</p>" +
        '<ul class="career-featured-list">' +
        "<li>แอป iService และระบบเสนอแผนออนไลน์</li>" +
        "<li>Thai Life Academy — อบรมต่อเนื่อง</li>" +
        "<li>ดูแลโดยผู้บริหารศูนย์นครปฐม</li>" +
        "</ul>" +
        '<a href="careers/' +
        feat.slug +
        '.html" class="btn btn-primary">อ่านรายละเอียด →</a>' +
        "</div></div>";
    }
  }
})();
