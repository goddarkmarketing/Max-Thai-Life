(function () {
  var pages = window.PAGES_DATA;
  if (!pages || !pages.plans) return;

  var category = new URLSearchParams(location.search).get("category");
  if (!category || category === "all") category = null;

  var categories = pages.plans.categories || [];
  var activeCategory = category
    ? categories.find(function (cat) {
        return cat.filter === category;
      })
    : null;

  if (window.setPlanPageLayout) {
    window.setPlanPageLayout(activeCategory ? category : null);
  }

  if (activeCategory) return;

  var cats = document.querySelector(".plan-categories");
  if (cats && categories.length) {
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
})();
