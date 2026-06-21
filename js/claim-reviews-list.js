(function () {
  if (!window.CLAIM_REVIEWS_DETAIL || !window.CLAIM_REVIEWS_LIST) return;

  var base = document.body.getAttribute("data-base") || "";
  var modal = document.getElementById("claim-review-modal");
  var modalContent = document.getElementById("claim-review-modal-content");
  var uiReady = false;

  var entries = window.CLAIM_REVIEWS_LIST.map(function (slug) {
    return window.CLAIM_REVIEWS_DETAIL[slug];
  });

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
    return sections
      .map(function (section) {
        var html = '<div class="claim-review-section">';
        if (section.heading) html += "<h4>" + section.heading + "</h4>";
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

  function reviewCardHtml(entry, opts) {
    opts = opts || {};
    var titleId = opts.titleId ? ' id="' + opts.titleId + '"' : "";
    return (
      '<article class="claim-review-card">' +
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

  function cardHtml(entry) {
    return "<li>" + reviewCardHtml(entry) + "</li>";
  }

  function galleryHtml(entry, index) {
    var active = index === 0 ? " is-active" : "";
    var slide = typeof entry.slide === "number" ? entry.slide : index;
    return (
      '<button type="button" class="claim-gallery-item' +
      active +
      '" data-slide="' +
      slide +
      '" aria-label="' +
      entry.title.replace(/"/g, "&quot;") +
      '">' +
      '<img src="' +
      imgSrc(entry.image) +
      '" alt="" width="120" height="120" loading="lazy" decoding="async">' +
      '<span class="claim-gallery-caption">' +
      entry.title +
      "</span>" +
      "</button>"
    );
  }

  function openModal(slideIndex) {
    var entry = entries[slideIndex];
    if (!entry || !modal || !modalContent) return;

    modalContent.innerHTML = reviewCardHtml(entry, { titleId: "claim-modal-title" });
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
  }

  function bindGallery() {
    var track = document.getElementById("claim-review-slider-track");
    var gallery = document.getElementById("claim-gallery");
    var galleryMore = document.getElementById("claim-gallery-more");
    var sliderRoot = document.querySelector("[data-claim-slider]");
    if (!sliderRoot || !gallery) return;

    var slider = sliderRoot._contentSlider;

    function allGalleryItems() {
      var items = gallery.querySelectorAll(".claim-gallery-item");
      var extra = galleryMore ? galleryMore.querySelectorAll(".claim-gallery-item") : [];
      return Array.prototype.slice.call(items).concat(Array.prototype.slice.call(extra));
    }

    function setActive(i) {
      allGalleryItems().forEach(function (btn) {
        var slide = parseInt(btn.getAttribute("data-slide"), 10);
        btn.classList.toggle("is-active", slide === i);
      });
    }

    allGalleryItems().forEach(function (btn) {
      btn.addEventListener("click", function () {
        var i = parseInt(btn.getAttribute("data-slide"), 10);
        if (slider) {
          slider.stopAuto();
          slider.goTo(i);
          slider.startAuto();
        }
        setActive(i);
        openModal(i);
      });
    });

    if (slider && slider.viewport) {
      slider.viewport.addEventListener(
        "scroll",
        function () {
          if (!slider.viewport) return;
          var w = slider.viewport.clientWidth;
          if (!w) return;
          var i = Math.round(slider.viewport.scrollLeft / w);
          setActive(i);
        },
        { passive: true }
      );
    }

    if (track && slider) {
      slider.refresh();
    }
  }

  function mount() {
    var track = document.getElementById("claim-review-slider-track");
    var gallery = document.getElementById("claim-gallery");
    var galleryMore = document.getElementById("claim-gallery-more");
    var moreBtn = document.getElementById("claim-gallery-more-btn");
    if (!track) return;

    bindUiOnce();

    track.innerHTML = entries.map(cardHtml).join("");

    if (gallery) {
      gallery.innerHTML = entries.map(galleryHtml).join("");
    }

    if (galleryMore && window.CLAIM_GALLERY_MORE && window.CLAIM_GALLERY_MORE.length) {
      galleryMore.innerHTML = window.CLAIM_GALLERY_MORE.map(function (item, i) {
        return galleryHtml(item, entries.length + i);
      }).join("");
      if (moreBtn) moreBtn.hidden = false;
    } else if (moreBtn) {
      moreBtn.hidden = true;
    }

    if (moreBtn && galleryMore && !moreBtn.dataset.bound) {
      moreBtn.dataset.bound = "1";
      moreBtn.addEventListener("click", function () {
        var expanded = moreBtn.getAttribute("aria-expanded") === "true";
        galleryMore.hidden = expanded;
        moreBtn.setAttribute("aria-expanded", expanded ? "false" : "true");
        moreBtn.textContent = expanded ? "ดูเพิ่มเติม" : "แสดงน้อยลง";
        if (!expanded) {
          galleryMore.scrollIntoView({ behavior: "smooth", block: "nearest" });
        }
      });
    }

    bindGallery();
    document.dispatchEvent(new CustomEvent("claim-reviews:updated"));
  }

  mount();
  document.addEventListener("landing:rendered", mount);
  window.addEventListener("load", mount);
})();
