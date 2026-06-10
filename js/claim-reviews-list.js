(function () {
  var track = document.getElementById("claim-review-slider-track");
  var gallery = document.getElementById("claim-gallery");
  var galleryMore = document.getElementById("claim-gallery-more");
  var moreBtn = document.getElementById("claim-gallery-more-btn");
  if (!window.CLAIM_REVIEWS_DETAIL || !window.CLAIM_REVIEWS_LIST) return;

  var base = document.body.getAttribute("data-base") || "";

  function imgSrc(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return base + src;
  }

  function formatDate(iso) {
    try {
      return new Date(iso).toLocaleDateString("th-TH", {
        year: "numeric",
        month: "long",
        day: "numeric"
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

  function cardHtml(entry) {
    return (
      "<li>" +
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
      "<h3>" +
      entry.title +
      "</h3>" +
      (entry.result
        ? '<p class="claim-review-result">' + entry.result + "</p>"
        : "") +
      '<blockquote class="claim-review-quote">' +
      "<p>" +
      entry.quote +
      "</p>" +
      (entry.author
        ? '<footer class="claim-review-author">— ' + entry.author + "</footer>"
        : "") +
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
      "</div></article></li>"
    );
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

  var entries = window.CLAIM_REVIEWS_LIST.map(function (slug) {
    return window.CLAIM_REVIEWS_DETAIL[slug];
  });

  if (track) {
    track.innerHTML = entries.map(cardHtml).join("");
  }

  if (gallery) {
    gallery.innerHTML = entries.map(galleryHtml).join("");
  }

  if (galleryMore && window.CLAIM_GALLERY_MORE && window.CLAIM_GALLERY_MORE.length) {
    galleryMore.innerHTML = window.CLAIM_GALLERY_MORE.map(function (item, i) {
      return galleryHtml(item, entries.length + i);
    }).join("");
  } else if (moreBtn) {
    moreBtn.hidden = true;
  }

  function bindGallery() {
    var sliderRoot = document.querySelector("[data-claim-slider]");
    if (!sliderRoot) return;

    var slider = sliderRoot._contentSlider;

    function allGalleryItems() {
      var items = gallery ? gallery.querySelectorAll(".claim-gallery-item") : [];
      var extra = galleryMore
        ? galleryMore.querySelectorAll(".claim-gallery-item")
        : [];
      return Array.prototype.slice.call(items).concat(Array.prototype.slice.call(extra));
    }

    function setActive(i) {
      allGalleryItems().forEach(function (btn) {
        var slide = parseInt(btn.getAttribute("data-slide"), 10);
        btn.classList.toggle("is-active", slide === i);
      });
    }

    function bindItem(btn) {
      btn.addEventListener("click", function () {
        var i = parseInt(btn.getAttribute("data-slide"), 10);
        if (slider) {
          slider.stopAuto();
          slider.goTo(i);
          slider.startAuto();
        }
        setActive(i);
      });
    }

    allGalleryItems().forEach(bindItem);

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
  }

  if (moreBtn && galleryMore) {
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

  window.addEventListener("load", function () {
    bindGallery();
  });
})();
