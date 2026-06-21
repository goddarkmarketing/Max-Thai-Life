(function () {
  function mount() {
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
      var stats = "";
      if (entry.views) {
        stats = '<p class="product-card-stats">' + entry.views.toLocaleString("th-TH") + " views";
        if (entry.shares) stats += " · " + entry.shares + " shares";
        stats += "</p>";
      }
      return (
        "<li><article class=\"product-card\">" +
        '<a href="' + base + "articles/" + entry.slug + '.html" class="product-card-media" tabindex="-1" aria-hidden="true">' +
        '<img src="' + imgSrc(entry.image) + '" alt="' + entry.title.replace(/"/g, "&quot;") + '" loading="lazy" decoding="async"></a>' +
        '<div class="product-card-body"><p class="product-card-meta">' + entry.category + "</p>" +
        "<h3><a href=\"" + base + "articles/" + entry.slug + '.html">' + entry.title + "</a></h3>" +
        '<p class="product-card-excerpt">' + entry.description + "</p>" + stats +
        '<a href="' + base + "articles/" + entry.slug + '.html" class="product-card-link">อ่านต่อ →</a></div></article></li>'
      );
    }

    grid.innerHTML = slugs.map(function (slug) {
      return cardHtml(window.ARTICLES_DETAIL[slug]);
    }).join("");
  }

  mount();
  document.addEventListener("landing:rendered", mount);
})();
