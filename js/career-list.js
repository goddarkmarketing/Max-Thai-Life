(function () {
  function mount() {
    var grid = document.getElementById("career-card-grid");
    var featured = document.getElementById("career-featured");
    if (!window.CAREERS_DETAIL) return;

    var base = document.body.getAttribute("data-base") || "";

    function imgSrc(src) {
      if (/^https?:\/\//i.test(src)) return src;
      return base + src;
    }

    function cardFooterHtml(type, slug, linkHtml) {
      if (window.mtlCardFooter) {
        return window.mtlCardFooter(type, slug, linkHtml);
      }
      var badge = window.mtlViewCountBadge
        ? window.mtlViewCountBadge(type, slug)
        : (
          '<div class="card-view-count">' +
          '<span data-analytics-views data-content-type="' + type + '" data-content-id="' + slug + '" hidden></span>' +
          "</div>"
        );
      return '<div class="product-card-footer">' + badge + linkHtml + "</div>";
    }

    function cardHtml(item, linkLabel) {
      var pageHref = base + "careers/" + item.slug + ".html";
      var linkHtml =
        '<a href="' + pageHref + '" class="product-card-link">' + (linkLabel || "อ่านต่อ →") + "</a>";
      var footer = cardFooterHtml("careers", item.slug, linkHtml);

      return (
        "<li>" +
        '<article class="product-card">' +
        '<a href="' + pageHref + '" class="product-card-media" tabindex="-1" aria-hidden="true">' +
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
        "<h3><a href=\"" + pageHref + '">' +
        item.title +
        "</a></h3>" +
        '<p class="product-card-excerpt">' +
        item.description +
        "</p>" +
        footer +
        "</div></article></li>"
      );
    }

    if (grid && window.CAREERS_LIST) {
      grid.innerHTML = window.CAREERS_LIST.map(function (slug) {
        return cardHtml(window.CAREERS_DETAIL[slug]);
      }).join("");
    }

    if (featured) {
      // เนื้อหา featured render จาก page-block-render.js แล้ว — ไม่ mount ซ้ำ
      if (!featured.querySelector(".career-featured-layout")) {
        var slug = featured.getAttribute("data-featured-slug") || "digital-agent-system";
        var feat = window.CAREERS_DETAIL[slug];
        var tpl = document.getElementById("career-featured-bullets");
        var bulletsHtml = tpl ? tpl.innerHTML : "";
        if (feat) {
          var featStats = window.mtlViewCountBadge
            ? window.mtlViewCountBadge("careers", feat.slug, "overlay")
            : "";
          featured.innerHTML =
            '<div class="career-featured-layout">' +
            '<a href="' + base + "careers/" + feat.slug + '.html" class="career-featured-media">' +
            featStats +
            '<img src="' + imgSrc(feat.image) + '" alt="' + feat.title.replace(/"/g, "&quot;") + '" loading="lazy" decoding="async" width="640" height="427"></a>' +
            '<div class="career-featured-body"><p class="product-card-meta">' + feat.category + "</p>" +
            "<h2><a href=\"" + base + "careers/" + feat.slug + '.html">' + feat.title + "</a></h2><p>" + feat.description + "</p>" +
            '<ul class="career-featured-list">' + bulletsHtml + "</ul>" +
            '<a href="' + base + "careers/" + feat.slug + '.html" class="btn btn-primary">อ่านรายละเอียด →</a></div></div>';
        }
      }
    }

    if (window.mtlScheduleFillViewCounts) window.mtlScheduleFillViewCounts();
    document.dispatchEvent(new CustomEvent("careers:updated"));
  }

  mount();
  document.addEventListener("landing:rendered", mount);
  document.addEventListener("mtl:helpers-ready", mount);
})();
