(function () {
  var AUTO_MS = 5500;
  var instance = null;

  function HeroSlider(root) {
    this.root = root;
    this.viewport = root.querySelector(".hero-slider-viewport");
    this.track = root.querySelector(".hero-slider-track");
    this.controlsHost = root.querySelector("[data-hero-slider-controls]");
    this.label = root.getAttribute("data-slider-name") || "แบนเนอร์";
    this.index = 0;
    this.realCount = 0;
    this.slides = [];
    this.clone = null;
    this.timer = null;
    this.onTransitionEnd = this.handleTransitionEnd.bind(this);
    this.reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    this.dotsWrap = null;
  }

  HeroSlider.prototype.setupInfinite = function () {
    if (!this.track) return;

    var existingClone = this.track.querySelector(".hero-slider-slide--clone");
    if (existingClone) existingClone.remove();

    this.slides = Array.prototype.slice.call(
      this.track.querySelectorAll(".hero-slider-slide:not(.hero-slider-slide--clone)")
    );
    this.realCount = this.slides.length;
    this.clone = null;
    this.root.classList.toggle("hero-slider--single", this.realCount <= 1);

    if (this.realCount <= 1) return;

    this.clone = this.slides[0].cloneNode(true);
    this.clone.classList.add("hero-slider-slide--clone");
    this.clone.setAttribute("aria-hidden", "true");
    var cloneImg = this.clone.querySelector("img");
    if (cloneImg) {
      cloneImg.removeAttribute("fetchpriority");
      cloneImg.loading = "lazy";
    }
    this.track.appendChild(this.clone);
  };

  HeroSlider.prototype.dotIndex = function () {
    if (!this.realCount) return 0;
    return ((this.index % this.realCount) + this.realCount) % this.realCount;
  };

  HeroSlider.prototype.setTransform = function (index, animate) {
    if (!this.track) return;
    this.track.classList.toggle("is-instant", !animate);
    this.track.style.transform = "translateX(-" + index * 100 + "%)";
    if (!animate) {
      void this.track.offsetWidth;
      this.track.classList.remove("is-instant");
    }
  };

  HeroSlider.prototype.syncAria = function () {
    var active = this.dotIndex();
    this.slides.forEach(function (slide, n) {
      slide.setAttribute("aria-hidden", n === active ? "false" : "true");
    });
  };

  HeroSlider.prototype.buildControls = function () {
    if (!this.controlsHost || this.realCount <= 1) {
      if (this.controlsHost) this.controlsHost.hidden = true;
      return;
    }

    this.controlsHost.hidden = false;
    this.controlsHost.innerHTML = "";

    this.dotsWrap = document.createElement("div");
    this.dotsWrap.className = "slider-dots";
    this.dotsWrap.setAttribute("role", "tablist");
    this.dotsWrap.setAttribute("aria-label", "เลือก" + this.label);
    this.controlsHost.appendChild(this.dotsWrap);

    var self = this;
    this.root.addEventListener("mouseenter", function () {
      self.stopAuto();
    });
    this.root.addEventListener("mouseleave", function () {
      self.startAuto();
    });

    this.buildDots();
  };

  HeroSlider.prototype.buildDots = function () {
    if (!this.dotsWrap) return;
    var self = this;
    this.dotsWrap.innerHTML = "";
    this.slides.forEach(function (_, i) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "slider-dot" + (i === self.dotIndex() ? " is-active" : "");
      btn.setAttribute("aria-label", self.label + " รายการที่ " + (i + 1));
      btn.setAttribute("aria-selected", i === self.dotIndex() ? "true" : "false");
      btn.addEventListener("click", function () {
        self.stopAuto();
        self.goToSlide(i);
        self.startAuto();
      });
      self.dotsWrap.appendChild(btn);
    });
  };

  HeroSlider.prototype.updateDots = function () {
    if (!this.dotsWrap) return;
    var active = this.dotIndex();
    var dots = this.dotsWrap.querySelectorAll(".slider-dot");
    dots.forEach(function (dot, n) {
      dot.classList.toggle("is-active", n === active);
      dot.setAttribute("aria-selected", n === active ? "true" : "false");
    });
  };

  HeroSlider.prototype.goToSlide = function (target, animate) {
    if (!this.realCount) return;
    animate = animate !== false && !this.reducedMotion;
    this.index = target;
    this.setTransform(this.index, animate);
    this.syncAria();
    this.updateDots();
  };

  HeroSlider.prototype.handleTransitionEnd = function (e) {
    if (e.target !== this.track || e.propertyName !== "transform") return;
    if (this.index !== this.realCount) return;
    this.index = 0;
    this.setTransform(0, false);
    this.syncAria();
    this.updateDots();
  };

  HeroSlider.prototype.advance = function () {
    if (this.realCount <= 1) return;

    if (this.reducedMotion) {
      this.goToSlide((this.dotIndex() + 1) % this.realCount, false);
      return;
    }

    var next = this.index + 1;
    this.index = next;
    this.setTransform(next, true);
    this.syncAria();
    this.updateDots();
  };

  HeroSlider.prototype.startAuto = function () {
    var self = this;
    this.stopAuto();
    if (this.realCount <= 1) return;
    this.timer = window.setInterval(function () {
      self.advance();
    }, AUTO_MS);
  };

  HeroSlider.prototype.stopAuto = function () {
    if (this.timer) window.clearInterval(this.timer);
    this.timer = null;
  };

  HeroSlider.prototype.init = function () {
    if (!this.track) return;
    this.setupInfinite();
    this.index = 0;
    this.setTransform(0, false);
    this.syncAria();
    this.buildControls();
    this.updateDots();
    this.track.addEventListener("transitionend", this.onTransitionEnd);
    this.startAuto();
    this.root.classList.add("is-active");
  };

  HeroSlider.prototype.destroy = function () {
    this.stopAuto();
    if (this.track) {
      this.track.removeEventListener("transitionend", this.onTransitionEnd);
      this.track.style.transform = "";
      this.track.classList.remove("is-instant");
    }
    if (this.track) {
      var clone = this.track.querySelector(".hero-slider-slide--clone");
      if (clone) clone.remove();
    }
    this.root.classList.remove("is-active");
  };

  function mount() {
    var root = document.querySelector("[data-hero-slider]");
    if (!root) return;
    if (instance) instance.destroy();
    instance = new HeroSlider(root);
    instance.init();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
  } else {
    mount();
  }

  document.addEventListener("hero:updated", mount);
})();
