(function () {
  var pages = window.PAGES_DATA;
  if (!pages || !pages.plans) return;

  var plans = pages.plans;
  var hero = document.querySelector('.page-hero-inner');
  if (hero) {
    var h1 = hero.querySelector('h1');
    var lead = hero.querySelector('p:not(.breadcrumb)');
    if (h1 && plans.title) h1.textContent = plans.title;
    if (lead && plans.lead) lead.textContent = plans.lead;
  }

  var cats = document.querySelector('.plan-categories');
  if (cats && plans.categories && plans.categories.length) {
    cats.innerHTML = plans.categories
      .map(function (cat, i) {
        return (
          '<button type="button" class="plan-cat-btn' +
          (i === 0 ? ' active' : '') +
          '" data-filter="' +
          cat.filter +
          '">' +
          cat.label +
          '</button>'
        );
      })
      .join('');
  }
})();
