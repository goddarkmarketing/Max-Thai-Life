(function () {
  var grid = document.getElementById("news-card-grid");
  var homeTrack = document.getElementById("news-home-track");
  if (!window.NEWS_DETAIL) return;

  var base = document.body.getAttribute("data-base") || "";

  function imgSrc(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return base + src;
  }

  function cardHtml(entry) {
    var stats = "";
    if (entry.views) {
      stats =
        '<p class="product-card-stats">' +
        entry.views.toLocaleString("th-TH") +
        " views";
      if (entry.shares) stats += " · " + entry.shares + " shares";
      stats += "</p>";
    }

    return (
      "<li>" +
      '<article class="product-card">' +
      '<a href="' +
      base +
      "news/" +
      entry.slug +
      '.html" class="product-card-media" tabindex="-1" aria-hidden="true">' +
      '<img src="' +
      imgSrc(entry.image) +
      '" alt="' +
      entry.title.replace(/"/g, "&quot;") +
      '" loading="lazy" decoding="async">' +
      "</a>" +
      '<div class="product-card-body">' +
      '<p class="product-card-meta">' +
      entry.category +
      "</p>" +
      "<h3><a href=\"" +
      base +
      "news/" +
      entry.slug +
      '.html">' +
      entry.title +
      "</a></h3>" +
      '<p class="product-card-excerpt">' +
      entry.description +
      "</p>" +
      stats +
      '<a href="' +
      base +
      "news/" +
      entry.slug +
      '.html" class="product-card-link">อ่านต่อ →</a>' +
      "</div></article></li>"
    );
  }

  function homeCardHtml(entry) {
    return (
      "<li>" +
      '<a href="' +
      base +
      "news/" +
      entry.slug +
      '.html" class="news-card">' +
      '<div class="news-card-media">' +
      '<img src="' +
      imgSrc(entry.image) +
      '" alt="' +
      entry.title.replace(/"/g, "&quot;") +
      '" loading="lazy" decoding="async">' +
      "</div>" +
      '<div class="news-card-body">' +
      '<span class="news-card-meta">' +
      entry.category +
      "</span>" +
      "<h3>" +
      entry.title +
      "</h3>" +
      "<p>" +
      entry.description +
      "</p>" +
      '<span class="news-card-link">อ่านต่อ →</span>' +
      "</div></a></li>"
    );
  }

  if (grid && window.NEWS_LIST) {
    grid.innerHTML = window.NEWS_LIST.map(function (slug) {
      return cardHtml(window.NEWS_DETAIL[slug]);
    }).join("");
  }

  if (homeTrack && window.NEWS_HOME) {
    homeTrack.innerHTML = window.NEWS_HOME.map(function (slug) {
      return homeCardHtml(window.NEWS_DETAIL[slug]);
    }).join("");
  }
})();
