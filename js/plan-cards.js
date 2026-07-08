(function () {
  var script = document.currentScript;
  var base = (script && script.getAttribute("data-base")) || "";
  var limit = parseInt((script && script.getAttribute("data-limit")) || "0", 10) || 0;

  function u(path) {
    return (base + path)
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

  function cardHtml(plan) {
    var features = (plan.features || [])
      .slice(0, 3)
      .map(function (f) {
        return "<li>" + f + "</li>";
      })
      .join("");
    var slug = window.mtlPlanSlugFromHref ? window.mtlPlanSlugFromHref(plan.href) : "";
    var stats =
      window.mtlViewCountBadge && slug ? window.mtlViewCountBadge("plans", slug, "overlay") : "";

    return (
      '<article class="plan-card" data-category="' +
      plan.category +
      '">' +
      '<div class="plan-card-media plan-card-media--' +
      plan.theme +
      '">' +
      stats +
      '<img src="' +
      u(plan.image) +
      '" alt="' +
      plan.title +
      '" class="plan-card-img" loading="lazy" decoding="async">' +
      '<span class="plan-card-tag">' +
      plan.tag +
      "</span>" +
      "</div>" +
      '<div class="plan-card-body">' +
      "<h3>" +
      plan.title +
      "</h3>" +
      "<p>" +
      plan.desc +
      "</p>" +
      '<ul class="plan-card-features">' +
      features +
      "</ul>" +
      '<a href="' +
      u(plan.href) +
      '" class="btn btn-plan-detail">ดูรายละเอียด</a>' +
      "</div>" +
      "</article>"
    );
  }

  window.renderPlanCards = function (categoryFilter) {
    var grids = document.querySelectorAll("[data-plan-grid]");
    var plans = (window.PLANS_DATA || []).slice();

    if (!grids.length || !plans.length) return;

    if (limit > 0 && (!categoryFilter || categoryFilter === "all")) {
      plans = plans.slice(0, limit);
    }

    if (categoryFilter && categoryFilter !== "all") {
      plans = plans.filter(function (plan) {
        return plan.category === categoryFilter;
      });
    }

    grids.forEach(function (grid) {
      grid.innerHTML = plans.map(cardHtml).join("");
      grid.classList.toggle(
        "plan-grid--category",
        !!(categoryFilter && categoryFilter !== "all")
      );
    });

    document.dispatchEvent(new CustomEvent("plans:rendered"));
    if (window.mtlScheduleFillViewCounts) window.mtlScheduleFillViewCounts();
  };

  var categoryFilter = null;
  var fromQuery = new URLSearchParams(location.search).get("category");
  if (fromQuery && fromQuery !== "all") {
    categoryFilter = fromQuery;
  }

  window.renderPlanCards(categoryFilter);

  if (window.initPlanCategoryView) {
    window.initPlanCategoryView();
  }
})();
