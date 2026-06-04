(function () {
  var root = document.querySelector("[data-testimonial-slider]");
  if (!root) return;

  var viewport = root.querySelector(".testimonial-viewport");
  var track = root.querySelector(".testimonial-track");
  var prevBtn = root.querySelector("[data-testimonial-prev]");
  var nextBtn = root.querySelector("[data-testimonial-next]");
  var dotsWrap = root.querySelector("[data-testimonial-dots]");
  var MOBILE_MQ = window.matchMedia("(max-width: 768px)");
  var index = 0;
  var timer;
  var slides = [];
  var originalHtml = "";
  var slideWidth = 0;

  function cacheOriginal() {
    if (!originalHtml) originalHtml = track.innerHTML;
  }

  function refreshSlides() {
    cacheOriginal();
    if (MOBILE_MQ.matches) {
      if (!track.dataset.flattened) {
        var cards = track.querySelectorAll(".testimonial-card");
        var html = "";
        cards.forEach(function (card) {
          html +=
            '<div class="testimonial-slide testimonial-slide--single">' +
            card.outerHTML +
            "</div>";
        });
        track.innerHTML = html;
        track.dataset.flattened = "1";
      }
    } else if (track.dataset.flattened) {
      track.innerHTML = originalHtml;
      delete track.dataset.flattened;
    }
    slides = track.querySelectorAll(".testimonial-slide");
    root.classList.toggle("is-mobile-single", MOBILE_MQ.matches);
  }

  function syncSlideWidth() {
    if (!viewport || !track) return;
    var w = viewport.clientWidth;
    if (!w) return;
    slideWidth = w;
    slides.forEach(function (slide) {
      slide.style.flex = "0 0 " + w + "px";
      slide.style.width = w + "px";
      slide.style.maxWidth = w + "px";
    });
  }

  function clearSlideWidth() {
    slideWidth = 0;
    slides.forEach(function (slide) {
      slide.style.flex = "";
      slide.style.width = "";
      slide.style.maxWidth = "";
    });
  }

  function slideOffset(i) {
    if (MOBILE_MQ.matches && viewport) {
      var w = viewport.clientWidth;
      return w > 0 ? i * w : 0;
    }
    return slideWidth ? i * slideWidth : 0;
  }

  function goTo(i) {
    if (!slides.length) return;
    index = ((i % slides.length) + slides.length) % slides.length;

    if (MOBILE_MQ.matches && viewport) {
      track.style.transform = "none";
      var left = slideOffset(index);
      if (viewport.scrollTo) {
        viewport.scrollTo({ left: left, behavior: "smooth" });
      } else {
        viewport.scrollLeft = left;
      }
    } else if (slideWidth) {
      track.style.transform = "translateX(-" + index * slideWidth + "px)";
    } else {
      track.style.transform = "";
    }

    if (dotsWrap) {
      dotsWrap.querySelectorAll(".testimonial-dot").forEach(function (dot, n) {
        dot.classList.toggle("is-active", n === index);
        dot.setAttribute("aria-selected", n === index ? "true" : "false");
      });
    }
  }

  function handleScroll() {
    if (!MOBILE_MQ.matches || !viewport) return;
    var w = viewport.clientWidth;
    if (!w) return;
    var closest = Math.round(viewport.scrollLeft / w);
    closest = Math.max(0, Math.min(closest, slides.length - 1));
    if (closest !== index) {
      index = closest;
      if (dotsWrap) {
        dotsWrap.querySelectorAll(".testimonial-dot").forEach(function (dot, n) {
          dot.classList.toggle("is-active", n === index);
          dot.setAttribute("aria-selected", n === index ? "true" : "false");
        });
      }
    }
  }

  function buildDots() {
    if (!dotsWrap) return;
    dotsWrap.innerHTML = "";
    for (var i = 0; i < slides.length; i++) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "testimonial-dot" + (i === index ? " is-active" : "");
      btn.setAttribute("aria-label", "รีวิวที่ " + (i + 1));
      btn.setAttribute("aria-selected", i === index ? "true" : "false");
      (function (n) {
        btn.addEventListener("click", function () {
          stopAuto();
          goTo(n);
          startAuto();
        });
      })(i);
      dotsWrap.appendChild(btn);
    }
  }

  function startAuto() {
    stopAuto();
    if (slides.length <= 1) return;
    timer = window.setInterval(function () {
      goTo(index + 1);
    }, 5500);
  }

  function stopAuto() {
    if (timer) window.clearInterval(timer);
  }

  function init() {
    refreshSlides();
    if (index >= slides.length) index = 0;
    track.style.transform = "none";
    syncSlideWidth();
    if (MOBILE_MQ.matches && viewport) viewport.scrollLeft = 0;
    buildDots();
    goTo(index);
    startAuto();
  }

  if (viewport) {
    viewport.addEventListener("scroll", handleScroll, { passive: true });
  }

  var resizeTimer;
  window.addEventListener("resize", function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      if (MOBILE_MQ.matches) {
        goTo(index);
      } else {
        syncSlideWidth();
        goTo(index);
      }
    }, 150);
  });

  if (prevBtn) {
    prevBtn.addEventListener("click", function () {
      stopAuto();
      goTo(index - 1);
      startAuto();
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener("click", function () {
      stopAuto();
      goTo(index + 1);
      startAuto();
    });
  }

  root.addEventListener("mouseenter", stopAuto);
  root.addEventListener("mouseleave", startAuto);

  function onMqChange() {
    var prevIndex = index;
    refreshSlides();
    if (MOBILE_MQ.matches) {
      index = Math.min(prevIndex, Math.max(slides.length - 1, 0));
      if (viewport) viewport.scrollLeft = 0;
    } else {
      index = Math.min(Math.floor(prevIndex / 3), Math.max(slides.length - 1, 0));
    }
    syncSlideWidth();
    buildDots();
    goTo(index);
    startAuto();
  }

  init();
  window.addEventListener("load", init);

  if (MOBILE_MQ.addEventListener) MOBILE_MQ.addEventListener("change", onMqChange);
  else MOBILE_MQ.addListener(onMqChange);
})();
