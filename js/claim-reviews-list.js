(function () {
  if (!window.CLAIM_REVIEWS_DETAIL || !window.CLAIM_REVIEWS_LIST) return;

  var base = document.body.getAttribute("data-base") || "";
  var modal = document.getElementById("claim-review-modal");
  var modalContent = document.getElementById("claim-review-modal-content");
  var uiReady = false;

  var entries = window.CLAIM_REVIEWS_LIST.map(function (slug) {
    return window.CLAIM_REVIEWS_DETAIL[slug];
  }).filter(Boolean);

  function imgSrc(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return base + src;
  }

  function formatDate(iso) {
    try {
      return new Date(iso).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric",
      });
    } catch (e) {
      return iso;
    }
  }

  function renderSections(sections) {
    if (!sections || !sections.length) return "";
    if (window.ContentSectionsRender && ContentSectionsRender.isBlockFormat(sections)) {
      return ContentSectionsRender.render(sections, {
        base: base,
        preview: false,
        imgSrc: function (p) {
          if (window.PageBlockRender) return PageBlockRender.imgSrc(p, base);
          return base + p;
        },
      });
    }
    return sections
      .map(function (section) {
        var html = '<div class="claim-review-section">';
        if (section.heading) html += "<h4>" + section.heading + "</h4>";
        if (section.paragraphs) {
          section.paragraphs.forEach(function (p) {
            html += "<p>" + p + "</p>";
          });
        }
        if (section.list && section.list.length) {
          html +=
            "<ul>" +
            section.list
              .map(function (li) {
                return "<li>" + li + "</li>";
              })
              .join("") +
            "</ul>";
        }
        html += "</div>";
        return html;
      })
      .join("");
  }

  function reviewDetailHtml(entry, opts) {
    opts = opts || {};
    var titleId = opts.titleId ? ' id="' + opts.titleId + '"' : "";
    return (
      '<article class="claim-review-card claim-review-card--modal">' +
      '<div class="claim-review-card-media">' +
      '<img src="' +
      imgSrc(entry.image) +
      '" alt="' +
      entry.title.replace(/"/g, "&quot;") +
      '" width="480" height="480" loading="lazy" decoding="async">' +
      "</div>" +
      '<div class="claim-review-card-body">' +
      '<p class="claim-review-category">' +
      entry.category +
      "</p>" +
      "<h3" +
      titleId +
      ">" +
      entry.title +
      "</h3>" +
      (entry.result ? '<p class="claim-review-result">' + entry.result + "</p>" : "") +
      '<blockquote class="claim-review-quote">' +
      "<p>" +
      entry.quote +
      "</p>" +
      (entry.author ? '<footer class="claim-review-author">— ' + entry.author + "</footer>" : "") +
      "</blockquote>" +
      '<p class="claim-review-summary">' +
      entry.description +
      "</p>" +
      renderSections(entry.sections) +
      '<time class="claim-review-date" datetime="' +
      entry.datePublished +
      '">' +
      formatDate(entry.datePublished) +
      "</time>" +
      "</div></article>"
    );
  }

  function cardHtml(entry, index) {
    var linkBtn =
      '<button type="button" class="product-card-link" data-claim-open="' +
      index +
      '">อ่านต่อ →</button>';
    var footer =
      '<div class="product-card-footer">' + linkBtn + "</div>";

    return (
      "<li>" +
      '<article class="product-card" data-claim-index="' +
      index +
      '">' +
      '<button type="button" class="product-card-media" data-claim-open="' +
      index +
      '" tabindex="-1" aria-hidden="true">' +
      '<img src="' +
      imgSrc(entry.image) +
      '" alt="' +
      entry.title.replace(/"/g, "&quot;") +
      '" loading="lazy" decoding="async">' +
      "</button>" +
      '<div class="product-card-body">' +
      '<p class="product-card-meta">' +
      entry.category +
      "</p>" +
      '<h3><button type="button" class="product-card-title-btn" data-claim-open="' +
      index +
      '">' +
      entry.title +
      "</button></h3>" +
      '<p class="product-card-excerpt">' +
      entry.description +
      "</p>" +
      footer +
      "</div></article></li>"
    );
  }

  function openModal(index) {
    var entry = entries[index];
    if (!entry || !modal || !modalContent) return;

    modalContent.innerHTML = reviewDetailHtml(entry, { titleId: "claim-modal-title" });
    modal.hidden = false;
    document.body.classList.add("claim-modal-open");

    var closeBtn = modal.querySelector(".claim-review-modal-close");
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    if (!modal) return;
    modal.hidden = true;
    document.body.classList.remove("claim-modal-open");
    modalContent.innerHTML = "";
  }

  function bindUiOnce() {
    if (uiReady) return;
    uiReady = true;

    if (modal) {
      modal.querySelectorAll("[data-claim-modal-close]").forEach(function (el) {
        el.addEventListener("click", closeModal);
      });

      document.addEventListener("keydown", function (e) {
        if (e.key === "Escape" && modal && !modal.hidden) {
          closeModal();
        }
      });
    }

    document.addEventListener("click", function (e) {
      var btn = e.target.closest("[data-claim-open]");
      if (!btn) return;
      e.preventDefault();
      var index = parseInt(btn.getAttribute("data-claim-open"), 10);
      if (!isNaN(index)) openModal(index);
    });
  }

  function mount() {
    var grid = document.getElementById("claim-card-grid");
    if (!grid) return;

    bindUiOnce();
    grid.innerHTML = entries.map(cardHtml).join("");
    document.dispatchEvent(new CustomEvent("claim-reviews:updated"));
  }

  mount();
  document.addEventListener("landing:rendered", mount);
  window.addEventListener("load", mount);
})();
