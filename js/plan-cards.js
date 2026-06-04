(function () {
  var script = document.currentScript;
  var base = (script && script.getAttribute("data-base")) || "";
  var limit = parseInt((script && script.getAttribute("data-limit")) || "0", 10) || 0;
  var grids = document.querySelectorAll("[data-plan-grid]");
  var plans = window.PLANS_DATA || [];

  if (!grids.length || !plans.length) return;

  if (limit > 0) plans = plans.slice(0, limit);

  function u(path) {
    return (base + path).replace(/ /g, "%20");
  }

  function cardHtml(plan) {
    var features = (plan.features || [])
      .slice(0, 3)
      .map(function (f) {
        return "<li>" + f + "</li>";
      })
      .join("");

    return (
      '<article class="plan-card" data-category="' +
      plan.category +
      '">' +
      '<div class="plan-card-media plan-card-media--' +
      plan.theme +
      '">' +
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

  grids.forEach(function (grid) {
    grid.innerHTML = plans.map(cardHtml).join("");
  });
})();
