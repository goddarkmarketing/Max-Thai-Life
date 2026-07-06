(function () {
  function siteBase() {
    var p = location.pathname;
    if (/\/(articles|plans|news|careers)\//.test(p)) return "../";
    return "";
  }

  function apiUrl(path) {
    return siteBase() + "api/" + path;
  }

  function currentPageName() {
    var path = location.pathname.split("/").pop() || "index.html";
    return path.split("?")[0];
  }

  function detectPage() {
    var body = document.body;
    if (!body) return null;

    var articleId = body.getAttribute("data-article-id");
    if (articleId) return { type: "articles", id: articleId };

    var newsId = body.getAttribute("data-news-id");
    if (newsId) return { type: "news", id: newsId };

    var careerId = body.getAttribute("data-career-id");
    if (careerId) return { type: "careers", id: careerId };

    var planId = body.getAttribute("data-plan-id");
    if (planId) return { type: "plans", id: planId };

    return { type: "site", id: currentPageName() };
  }

  function formatViews(n) {
    return Number(n || 0).toLocaleString("th-TH") + " ครั้ง";
  }

  function renderViewHtml(n) {
    return (
      '<span class="card-view-count__pill">' +
      '<i data-lucide="eye" class="card-view-count__icon" aria-hidden="true"></i>' +
      '<span class="card-view-count__num">' +
      formatViews(n) +
      "</span></span>"
    );
  }

  function viewWrap(el) {
    return el.closest(
      ".card-view-count, .product-card-stats, .plan-card-stats, .article-stats, .article-detail-meta, .news-card-stats"
    );
  }

  function applyViewElement(el, views) {
    if (!views) return;
    el.innerHTML = renderViewHtml(views);
    el.hidden = false;
    el.removeAttribute("hidden");
    var wrap = viewWrap(el);
    if (wrap) wrap.classList.add("has-analytics-views");
    if (window.LucideIcons) LucideIcons.refresh(el);
  }

  function trackView(page) {
    if (!page || !page.type || !page.id) return;

    var storageKey = "mtl_view_" + page.type + "_" + page.id;
    try {
      if (sessionStorage.getItem(storageKey) === "1") return;
    } catch (e) {}

    fetch(apiUrl("track-view.php"), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      keepalive: true,
      body: JSON.stringify({ type: page.type, id: page.id }),
    })
      .then(function (res) {
        return res.json();
      })
      .then(function (data) {
        if (!data || !data.ok) return;
        try {
          sessionStorage.setItem(storageKey, "1");
        } catch (e) {}
        if (typeof data.views === "number") {
          updateViewElements(page.type, page.id, data.views);
        }
      })
      .catch(function () {});
  }

  function updateViewElements(type, id, views) {
    if (!views) return;
    document.querySelectorAll('[data-analytics-views][data-content-type="' + type + '"]').forEach(function (el) {
      var elId = el.getAttribute("data-content-id");
      if (elId && elId !== id) return;
      applyViewElement(el, views);
    });
  }

  function fillViewCounts() {
    var types = {};
    document.querySelectorAll("[data-analytics-views]").forEach(function (el) {
      var type = el.getAttribute("data-content-type");
      if (type) types[type] = true;
    });

    Object.keys(types).forEach(function (type) {
      fetch(apiUrl("view-counts.php?type=" + encodeURIComponent(type)))
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (!data || !data.ok || !data.views) return;
          document.querySelectorAll('[data-analytics-views][data-content-type="' + type + '"]').forEach(function (el) {
            var elId = el.getAttribute("data-content-id");
            var count = data.views[elId];
            if (!count) return;
            applyViewElement(el, count);
          });
        })
        .catch(function () {});
    });
  }

  window.mtlFillViewCounts = fillViewCounts;

  var page = detectPage();
  trackView(page);
  fillViewCounts();

  document.addEventListener("landing:rendered", fillViewCounts);
  document.addEventListener("news:updated", fillViewCounts);
  document.addEventListener("plans:rendered", fillViewCounts);
})();
