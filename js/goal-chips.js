(function () {
  var nav = document.querySelector(".goal-chips");
  if (!nav) return;

  var chips =
    (window.HOME_DATA &&
      window.HOME_DATA.plansSection &&
      window.HOME_DATA.plansSection.goalChips) ||
    [];
  var plans = window.PLANS_DATA || [];

  if (!chips.length) return;

  function esc(text) {
    var d = document.createElement("div");
    d.textContent = text || "";
    return d.innerHTML;
  }

  function encodePath(path) {
    return (path || "")
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

  function planForChip(chip) {
    var href = chip.href || "";

    if (chip.category) {
      for (var i = 0; i < plans.length; i++) {
        if (plans[i].category === chip.category) return plans[i];
      }
    }

    var categoryMatch = href.match(/[?&]category=([^&]+)/);
    if (categoryMatch) {
      var category = decodeURIComponent(categoryMatch[1]);
      for (var j = 0; j < plans.length; j++) {
        if (plans[j].category === category) return plans[j];
      }
    }

    var target = href.replace(/^\//, "").split("?")[0];
    for (var k = 0; k < plans.length; k++) {
      if (plans[k].href === target) return plans[k];
    }

    return null;
  }

  var html = chips
    .filter(function (chip) {
      return !chip.all;
    })
    .map(function (chip) {
      var plan = planForChip(chip);
      var img = chip.image || (plan && plan.image);
      var theme = (plan && plan.theme) || "";
      var label = chip.label || (plan && plan.tag) || "";
      var link = plan
        ? "plans.html?category=" +
          encodeURIComponent(plan.category) +
          "#" +
          encodeURIComponent(plan.category)
        : chip.href || "#";
      if (!img) return "";

      return (
        '<a href="' +
        link +
        '" class="goal-chip-tile' +
        (theme ? " goal-chip-tile--" + theme : "") +
        '" data-plan-category="' +
        esc(plan ? plan.category : "") +
        '">' +
        '<span class="goal-chip-tile__media">' +
        '<img src="' +
        encodePath(img) +
        '" alt="' +
        esc(label) +
        '" loading="lazy" decoding="async">' +
        "</span>" +
        '<span class="goal-chip-tile__label">' +
        esc(label) +
        "</span>" +
        "</a>"
      );
    })
    .filter(function (item) {
      return item !== "";
    })
    .join("");

  if (html) {
    nav.innerHTML = html;
    nav.addEventListener("click", function (e) {
      var tile = e.target.closest("[data-plan-category]");
      if (!tile) return;
      var cat = tile.getAttribute("data-plan-category");
      if (!cat) return;
      try {
        sessionStorage.setItem("planCategory", cat);
      } catch (err) {}
    });
  }
})();
