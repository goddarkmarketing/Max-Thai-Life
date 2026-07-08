(function () {
  function readPlanCategory(consumeSession) {
    var fromQuery = new URLSearchParams(location.search).get("category");
    if (!fromQuery || fromQuery === "all") {
      try {
        sessionStorage.removeItem("planCategory");
      } catch (e) {}
      return null;
    }
    if (consumeSession) {
      try {
        sessionStorage.removeItem("planCategory");
      } catch (e) {}
    }
    return fromQuery;
  }

  window.getPlanCategoryFromUrl = function () {
    return readPlanCategory(true);
  };

  window.peekPlanCategory = function () {
    return readPlanCategory(false);
  };

  function findCategoryMeta(category) {
    var pages = window.PAGES_DATA && window.PAGES_DATA.plans;
    if (!pages || !category) return null;
    var categories = pages.categories || [];
    for (var i = 0; i < categories.length; i++) {
      if (categories[i].filter === category) return categories[i];
    }
    return null;
  }

  window.findCategoryMeta = findCategoryMeta;

  window.setPlanPageLayout = function (category) {
    var pages = window.PAGES_DATA && window.PAGES_DATA.plans;
    var activeCategory = category ? findCategoryMeta(category) : null;
    var isCategoryPage = !!activeCategory;

    document.body.classList.toggle("plan-category-page", isCategoryPage);

    if (isCategoryPage) {
      document.body.setAttribute("data-plan-category", category);
    } else {
      document.body.removeAttribute("data-plan-category");
    }

    document.querySelectorAll(".plans-overview-only").forEach(function (el) {
      el.hidden = isCategoryPage;
    });

    document.querySelectorAll(".plans-category-only").forEach(function (el) {
      el.hidden = !isCategoryPage;
    });

    var cats = document.querySelector(".plan-categories");
    if (cats) {
      if (isCategoryPage) {
        cats.innerHTML = "";
        cats.hidden = true;
        cats.setAttribute("aria-hidden", "true");
      } else {
        cats.hidden = false;
        cats.removeAttribute("aria-hidden");
        if (!cats.querySelector(".plan-cat-btn") && pages) {
          var categories = pages.categories || [];
          cats.innerHTML = categories
            .map(function (cat) {
              return (
                '<button type="button" class="plan-cat-btn' +
                (cat.filter === "all" ? " active" : "") +
                '" data-filter="' +
                cat.filter +
                '">' +
                cat.label +
                "</button>"
              );
            })
            .join("");
        }
      }
    }

    var hero = document.querySelector(".page-hero-inner");
    if (!hero || !pages) return;

    var h1 = hero.querySelector("h1");
    var lead = hero.querySelector("p:not(.breadcrumb)");
    var breadcrumb = hero.querySelector(".breadcrumb");

    if (isCategoryPage && activeCategory) {
      if (h1) h1.textContent = activeCategory.label;
      if (lead) {
        lead.textContent =
          "แผนประกันในหมวด" +
          activeCategory.label +
          " — เลือกดูรายละเอียดและขอใบเสนอเบี้ยฟรี";
      }
      if (breadcrumb) {
        breadcrumb.innerHTML =
          '<a href="index.html">หน้าหลัก</a> / <a href="plans.html">แผนประกัน</a> / ' +
          activeCategory.label;
      }
      document.title =
        activeCategory.label + " | แผนประกัน | Wealth Agent TL";

      var cta = document.querySelector(".cta-band.plans-category-only");
      if (cta) {
        var ctaH = cta.querySelector("h2");
        var ctaP = cta.querySelector("p");
        if (ctaH) ctaH.textContent = "สนใจแผน" + activeCategory.label + "?";
        if (ctaP) {
          ctaP.textContent =
            "ปรึกษาฟรี วางแผนเบี้ยและความคุ้มครองในหมวด" + activeCategory.label;
        }
      }
      return;
    }

    if (h1 && pages.title) h1.textContent = pages.title;
    if (lead && pages.lead) lead.textContent = pages.lead;
    if (breadcrumb) {
      breadcrumb.innerHTML =
        '<a href="index.html">หน้าหลัก</a> / แผนประกัน';
    }
    document.title = "แผนประกัน | Wealth Agent TL";
  };

  window.initPlanCategoryView = function () {
    var category = readPlanCategory(false);
    var grid = document.getElementById("plan-grid");

    if (category && findCategoryMeta(category)) {
      window.setPlanPageLayout(category);
      if (window.renderPlanCards) {
        window.renderPlanCards(category);
      }
      if (grid) grid.classList.add("plan-grid--category");
      return true;
    }

    window.setPlanPageLayout(null);

    function bindCategoryButtons() {
      var buttons = document.querySelectorAll(".plan-cat-btn");
      if (!buttons.length) return false;

      function setActiveButton(activeBtn) {
        buttons.forEach(function (btn) {
          btn.classList.toggle("active", btn === activeBtn);
        });
      }

      function applyCategory(filter, activeBtn) {
        var isAll = !filter || filter === "all";
        setActiveButton(activeBtn);
        if (window.renderPlanCards) {
          window.renderPlanCards(isAll ? null : filter);
        }
        if (grid) {
          grid.classList.toggle("plan-grid--category", !isAll);
        }
      }

      buttons.forEach(function (btn) {
        if (btn.dataset.bound === "1") return;
        btn.dataset.bound = "1";
        btn.addEventListener("click", function () {
          applyCategory(btn.getAttribute("data-filter") || "all", btn);
        });
      });

      var defaultBtn = Array.from(buttons).find(function (btn) {
        return btn.getAttribute("data-filter") === "all";
      });
      if (defaultBtn) applyCategory("all", defaultBtn);
      return true;
    }

    return bindCategoryButtons();
  };
})();
