(function () {
  var fillTimer = null;
  var fillGen = 0;
  var pageShowRefetchReady = false;

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

  function isContentDetailPage(page) {
    return !!(page && page.type && page.id && page.type !== "site");
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
      ".card-view-count, .product-card-footer, .product-card-stats, .plan-card-stats, .article-stats, .article-detail-meta, .news-card-stats"
    );
  }

  function cacheKey(type, id) {
    return "mtl_vc_" + type + "_" + id;
  }

  function cacheViewCount(type, id, views) {
    if (!type || !id || !views) return;
    try {
      sessionStorage.setItem(cacheKey(type, id), String(views));
    } catch (e) {}
  }

  function readCachedViewCount(type, id) {
    try {
      var raw = sessionStorage.getItem(cacheKey(type, id));
      if (!raw) return 0;
      var n = parseInt(raw, 10);
      return isNaN(n) ? 0 : n;
    } catch (e) {
      return 0;
    }
  }

  function applyViewElement(el, views) {
    if (!views) return;
    el.innerHTML = renderViewHtml(views);
    el.hidden = false;
    el.removeAttribute("hidden");
    el.setAttribute("data-views-filled", "1");
    var wrap = viewWrap(el);
    if (wrap) wrap.classList.add("has-analytics-views");
    if (window.LucideIcons) LucideIcons.refresh(el);
  }

  function updateViewElements(type, id, views) {
    if (!views) return;
    cacheViewCount(type, id, views);
    document.querySelectorAll('[data-analytics-views][data-content-type="' + type + '"]').forEach(function (el) {
      var elId = el.getAttribute("data-content-id");
      if (elId && elId !== id) return;
      applyViewElement(el, views);
    });
  }

  function applyCachedViewCounts() {
    document.querySelectorAll("[data-analytics-views]").forEach(function (el) {
      var type = el.getAttribute("data-content-type");
      var id = el.getAttribute("data-content-id");
      if (!type || !id) return;
      var cached = readCachedViewCount(type, id);
      if (cached > 0) applyViewElement(el, cached);
    });
  }

  /** Static fallback (GitHub Pages / no PHP): use views embedded in content data JS. */
  function staticViewsMap(type) {
    var map = null;
    if (type === "careers" && window.CAREERS_DETAIL) map = window.CAREERS_DETAIL;
    else if (type === "articles" && window.ARTICLES_DETAIL) map = window.ARTICLES_DETAIL;
    else if (type === "news" && window.NEWS_DETAIL) map = window.NEWS_DETAIL;
    else if (type === "plans" && window.PLANS_DETAIL) map = window.PLANS_DETAIL;
    return map;
  }

  function staticViewCount(type, id) {
    var map = staticViewsMap(type);
    if (!map || !id) return 0;
    var item = map[id];
    if (!item) return 0;
    var n = parseInt(item.views, 10);
    return isNaN(n) ? 0 : n;
  }

  function applyStaticFallbackViews(type) {
    var map = staticViewsMap(type);
    if (!map) return;
    document.querySelectorAll('[data-analytics-views][data-content-type="' + type + '"]').forEach(function (el) {
      if (el.getAttribute("data-views-filled") === "1") return;
      var elId = el.getAttribute("data-content-id");
      var count = staticViewCount(type, elId);
      if (!count) return;
      applyViewElement(el, count);
      cacheViewCount(type, elId, count);
    });
  }

  function fetchSingleViewCount(type, id, opts) {
    opts = opts || {};
    if (!type || !id) return Promise.resolve();

    var url =
      apiUrl("view-counts.php?type=" + encodeURIComponent(type) + "&id=" + encodeURIComponent(id) + "&_=" + Date.now());

    return fetch(url, {
      cache: "no-store",
      keepalive: !!opts.keepalive,
    })
      .then(function (res) {
        if (!res.ok) throw new Error("api");
        return res.json();
      })
      .then(function (data) {
        if (data && data.ok && typeof data.views === "number") {
          updateViewElements(type, id, data.views);
          return;
        }
        throw new Error("api");
      })
      .catch(function () {
        var fallback = staticViewCount(type, id);
        if (fallback > 0) updateViewElements(type, id, fallback);
      });
  }

  function trackView(page) {
    if (!isContentDetailPage(page)) return;

    var storageKey = "mtl_view_" + page.type + "_" + page.id;
    var alreadyTracked = false;
    try {
      alreadyTracked = sessionStorage.getItem(storageKey) === "1";
    } catch (e) {}

    if (alreadyTracked) {
      fetchSingleViewCount(page.type, page.id);
      return;
    }

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

  function fillViewCounts() {
    var types = {};
    document.querySelectorAll("[data-analytics-views]").forEach(function (el) {
      var type = el.getAttribute("data-content-type");
      if (type) types[type] = true;
    });

    if (!Object.keys(types).length) return;

    var gen = ++fillGen;

    Object.keys(types).forEach(function (type) {
      fetch(apiUrl("view-counts.php?type=" + encodeURIComponent(type) + "&_=" + Date.now()), {
        cache: "no-store",
      })
        .then(function (res) {
          if (!res.ok) throw new Error("api");
          return res.json();
        })
        .then(function (data) {
          if (gen !== fillGen) return;
          if (!data || !data.ok || !data.views) throw new Error("api");
          document.querySelectorAll('[data-analytics-views][data-content-type="' + type + '"]').forEach(function (el) {
            var elId = el.getAttribute("data-content-id");
            var count = data.views[elId];
            if (!count) return;
            applyViewElement(el, count);
            cacheViewCount(type, elId, count);
          });
        })
        .catch(function () {
          if (gen !== fillGen) return;
          applyStaticFallbackViews(type);
        });
    });
  }

  function scheduleFillViewCounts() {
    if (fillTimer) clearTimeout(fillTimer);
    fillTimer = setTimeout(function () {
      fillTimer = null;
      applyCachedViewCounts();
      fillViewCounts();
    }, 0);
  }

  function cacheLatestDetailViewBeforeLeave() {
    var page = detectPage();
    if (!isContentDetailPage(page)) return;
    fetchSingleViewCount(page.type, page.id, { keepalive: true });
  }

  window.mtlFillViewCounts = fillViewCounts;
  window.mtlScheduleFillViewCounts = scheduleFillViewCounts;
  if (window.mtlFlushPendingViewCounts) window.mtlFlushPendingViewCounts();

  var page = detectPage();
  trackView(page);
  scheduleFillViewCounts();

  document.addEventListener("landing:rendered", scheduleFillViewCounts);
  document.addEventListener("news:updated", scheduleFillViewCounts);
  document.addEventListener("articles:updated", scheduleFillViewCounts);
  document.addEventListener("careers:updated", scheduleFillViewCounts);
  document.addEventListener("plans:rendered", scheduleFillViewCounts);

  window.addEventListener("pagehide", cacheLatestDetailViewBeforeLeave);

  window.addEventListener("pageshow", function () {
    if (!pageShowRefetchReady) return;
    applyCachedViewCounts();
    scheduleFillViewCounts();
  });

  document.addEventListener("visibilitychange", function () {
    if (document.visibilityState === "visible") {
      applyCachedViewCounts();
      scheduleFillViewCounts();
    }
  });

  window.addEventListener("focus", scheduleFillViewCounts);

  requestAnimationFrame(function () {
    requestAnimationFrame(function () {
      pageShowRefetchReady = true;
    });
  });
})();
