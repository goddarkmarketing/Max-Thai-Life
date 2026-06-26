(function () {

  var pages = window.PAGES_DATA;

  if (!pages) return;



  var pageKey = document.body.getAttribute("data-landing-page");

  if (!pageKey || !pages[pageKey]) return;



  var page = pages[pageKey];

  var base = document.body.getAttribute("data-base") || "";

  var site = window.SITE_DATA || {};

  var R = window.PageBlockRender;



  if (!R) {

    console.warn("[page-landing-render] PageBlockRender not loaded");

    return;

  }



  var ctx = { base: base, agent: site.agent || {}, meta: { breadcrumb: page.hero?.breadcrumb } };



  function renderHero() {

    var inner = document.getElementById("page-hero-inner") || document.querySelector(".page-hero-inner");

    var heroWrap = document.querySelector(".page-hero");

    if (!inner) return;

    if (!R.isVisible(page.hero || {})) {

      inner.innerHTML = "";

      if (heroWrap) heroWrap.hidden = true;

      return;

    }

    if (heroWrap) heroWrap.hidden = false;

    var hero = page.hero || {};

    var bc = hero.breadcrumb || pageKey;

    var leadClass = hero.leadClass || (pageKey === "claimReviews" ? "page-hero-lead" : "");

    var leadTag = leadClass ? '<p class="' + leadClass + '">' : "<p>";

    inner.innerHTML =

      '<p class="breadcrumb"><a href="' + base + 'index.html">หน้าหลัก</a> / ' + R.esc(bc) + "</p>" +

      "<h1>" + R.esc(hero.title || page.title || "") + "</h1>" +

      leadTag + R.esc(hero.lead || page.lead || "") + "</p>";

  }



  function renderCta() {

    var root = document.getElementById("landing-cta-root");

    if (!root) return;

    root.innerHTML = R.renderCta(page.cta || {}, ctx);

  }



  function renderRichText() {

    var heroWrap = document.querySelector(".page-hero");

    if (heroWrap) heroWrap.hidden = true;

    var heroInner = document.getElementById("page-hero-inner");

    if (heroInner) heroInner.innerHTML = "";

    var root = document.getElementById("landing-root");

    if (root) {

      root.innerHTML =
        '<section class="section lp-rich-section"><div class="section-inner reveal visible">' +
        '<div class="lp-rich ql-content">' + (page.bodyHtml || "") + "</div>" +
        "</div></section>";

    }

    var ctaRoot = document.getElementById("landing-cta-root");

    if (ctaRoot) ctaRoot.innerHTML = "";

    if (window.LucideIcons) LucideIcons.refresh(root);

    document.dispatchEvent(new CustomEvent("landing:rendered", { detail: { page: pageKey } }));

  }

  function renderAll() {

    if (page.editor === "richtext") {

      renderRichText();

      return;

    }

    renderHero();

    var root = document.getElementById("landing-root");

    if (root) {

      root.innerHTML = R.renderSections(page.sections || [], ctx);

    }

    renderCta();

    document.querySelectorAll("#landing-root .reveal, #landing-cta-root .reveal").forEach(function (el) {

      el.classList.add("visible");

    });

    if (window.initSectionHeaders) {

      var landingRoot = document.getElementById("landing-root");

      if (landingRoot) initSectionHeaders(landingRoot);

    }

    if (window.LucideIcons) {

      LucideIcons.refresh(document.getElementById("landing-root"));

      LucideIcons.refresh(document.getElementById("landing-cta-root"));

    }

    document.dispatchEvent(new CustomEvent("landing:rendered", { detail: { page: pageKey } }));

  }



  renderAll();

})();

