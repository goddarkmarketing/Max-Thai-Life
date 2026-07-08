(function () {
  function base() {
    return document.body.getAttribute("data-base") || "";
  }

  function imgSrc(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return base() + (src || "");
  }

  function esc(s) {
    return String(s == null ? "" : s).replace(/"/g, "&quot;");
  }

  function homeCardHtml(entry) {
    var b = base();
    var href = b + "articles/" + entry.slug + ".html";
    var stats = window.mtlViewCountBadge
      ? window.mtlViewCountBadge("articles", entry.slug, "overlay")
      : "";
    return (
      '<li class="article-item">' +
      '<a href="' + href + '" class="article-thumb" tabindex="-1" aria-hidden="true">' +
      stats +
      '<img src="' + imgSrc(entry.image) + '" alt="" width="88" height="88" loading="lazy" decoding="async"></a>' +
      '<div class="article-body">' +
      '<p class="article-meta">' + esc(entry.category) + "</p>" +
      '<h3><a href="' + href + '">' + esc(entry.title) + "</a></h3>" +
      '<p class="article-excerpt">' + esc(entry.description) + "</p>" +
      '<a href="' + href + '" class="article-read-more">อ่านต่อ</a>' +
      "</div></li>"
    );
  }

  function mountHome() {
    var track = document.getElementById("articles-home-track");
    if (!track || !window.ARTICLES_DETAIL) return;
    var slugs = (window.ARTICLES_HOME || Object.keys(window.ARTICLES_DETAIL)).filter(function (slug) {
      var item = window.ARTICLES_DETAIL[slug];
      return item && item.visible !== false;
    });
    track.innerHTML = slugs
      .map(function (slug) {
        return homeCardHtml(window.ARTICLES_DETAIL[slug]);
      })
      .join("");
    if (window.LucideIcons && window.LucideIcons.refresh) {
      window.LucideIcons.refresh(track);
    }
    if (window.mtlScheduleFillViewCounts) window.mtlScheduleFillViewCounts();
  }

  function mount() {
    mountHome();

    var grid = document.getElementById("articles-card-grid");
    if (!grid || !window.ARTICLES_DETAIL) return;

    var base = document.body.getAttribute("data-base") || "";
    var slugs = Object.keys(window.ARTICLES_DETAIL).filter(function (slug) {
      var item = window.ARTICLES_DETAIL[slug];
      return item && item.visible !== false;
    });

    function imgSrc(src) {
      if (/^https?:\/\//i.test(src)) return src;
      return base + src;
    }

    function cardHtml(entry) {
      var pageHref = base + "articles/" + entry.slug + ".html";
      var footer = window.mtlCardFooter
        ? window.mtlCardFooter(
            "articles",
            entry.slug,
            '<a href="' + pageHref + '" class="product-card-link">อ่านต่อ →</a>'
          )
        : '<a href="' + pageHref + '" class="product-card-link">อ่านต่อ →</a>';
      return (
        "<li><article class=\"product-card\">" +
        '<a href="' + pageHref + '" class="product-card-media" tabindex="-1" aria-hidden="true">' +
        '<img src="' + imgSrc(entry.image) + '" alt="' + entry.title.replace(/"/g, "&quot;") + '" loading="lazy" decoding="async"></a>' +
        '<div class="product-card-body"><p class="product-card-meta">' + entry.category + "</p>" +
        "<h3><a href=\"" + pageHref + '">' + entry.title + "</a></h3>" +
        '<p class="product-card-excerpt">' + entry.description + "</p>" +
        footer +
        "</div></article></li>"
      );
    }

    grid.innerHTML = slugs.map(function (slug) {
      return cardHtml(window.ARTICLES_DETAIL[slug]);
    }).join("");

    if (window.mtlScheduleFillViewCounts) window.mtlScheduleFillViewCounts();
    document.dispatchEvent(new CustomEvent("articles:updated"));
  }

  mount();
  document.addEventListener("landing:rendered", mount);
  document.addEventListener("mtl:helpers-ready", mount);
})();
