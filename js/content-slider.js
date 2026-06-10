(function () {
  var MOBILE_MQ = window.matchMedia("(max-width: 768px)");
  var instances = [];

  function arrowSvg(dir) {
    var path = dir === "prev" ? "M15 18l-6-6 6-6" : "M9 18l6-6-6-6";
    return (
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="' +
      path +
      '"/></svg>'
    );
  }

  function ContentSlider(root) {
    this.root = root;
    this.viewport = root.querySelector(".content-slider-viewport");
    this.track = root.querySelector(".content-slider-track");
    this.controlsHost = root.querySelector("[data-slider-controls]");
    this.label = root.getAttribute("data-slider-name") || "รายการ";
    this.index = 0;
    this.timer = null;
    this.controlsBuilt = false;
    this.prevBtn = null;
    this.nextBtn = null;
    this.dotsWrap = null;
    this.onScroll = this.handleScroll.bind(this);
  }

  ContentSlider.prototype.getItems = function () {
    return this.track ? Array.prototype.slice.call(this.track.children) : [];
  };

  ContentSlider.prototype.syncSlideWidth = function () {
    if (!this.viewport) return 0;
    var w = this.viewport.clientWidth;
    if (!w) return 0;
    this.getItems().forEach(function (li) {
      li.style.flex = "0 0 " + w + "px";
      li.style.width = w + "px";
      li.style.maxWidth = w + "px";
    });
    return w;
  };

  ContentSlider.prototype.buildControls = function () {
    if (!this.controlsHost || this.controlsBuilt) return;
    this.controlsHost.innerHTML = "";
    this.controlsHost.className = "slider-controls";

    this.prevBtn = document.createElement("button");
    this.prevBtn.type = "button";
    this.prevBtn.className = "slider-arrow";
    this.prevBtn.setAttribute("aria-label", this.label + " ก่อนหน้า");
    this.prevBtn.innerHTML = arrowSvg("prev");

    this.dotsWrap = document.createElement("div");
    this.dotsWrap.className = "slider-dots";
    this.dotsWrap.setAttribute("role", "tablist");
    this.dotsWrap.setAttribute("aria-label", "เลือก" + this.label);

    this.nextBtn = document.createElement("button");
    this.nextBtn.type = "button";
    this.nextBtn.className = "slider-arrow";
    this.nextBtn.setAttribute("aria-label", this.label + " ถัดไป");
    this.nextBtn.innerHTML = arrowSvg("next");

    this.controlsHost.appendChild(this.prevBtn);
    this.controlsHost.appendChild(this.dotsWrap);
    this.controlsHost.appendChild(this.nextBtn);

    var self = this;
    this.prevBtn.addEventListener("click", function () {
      self.stopAuto();
      self.goTo(self.index - 1);
      self.startAuto();
    });
    this.nextBtn.addEventListener("click", function () {
      self.stopAuto();
      self.goTo(self.index + 1);
      self.startAuto();
    });

    this.root.addEventListener("mouseenter", function () {
      self.stopAuto();
    });
    this.root.addEventListener("mouseleave", function () {
      if (MOBILE_MQ.matches) self.startAuto();
    });

    this.controlsBuilt = true;
  };

  ContentSlider.prototype.buildDots = function () {
    if (!this.dotsWrap) return;
    var items = this.getItems();
    var self = this;
    this.dotsWrap.innerHTML = "";
    items.forEach(function (_, i) {
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "slider-dot" + (i === self.index ? " is-active" : "");
      btn.setAttribute("aria-label", self.label + " รายการที่ " + (i + 1));
      btn.setAttribute("aria-selected", i === self.index ? "true" : "false");
      btn.addEventListener("click", function () {
        self.stopAuto();
        self.goTo(i);
        self.startAuto();
      });
      self.dotsWrap.appendChild(btn);
    });
  };

  ContentSlider.prototype.updateDots = function () {
    if (!this.dotsWrap) return;
    var dots = this.dotsWrap.querySelectorAll(".slider-dot");
    dots.forEach(function (dot, n) {
      dot.classList.toggle("is-active", n === this.index);
      dot.setAttribute("aria-selected", n === this.index ? "true" : "false");
    }, this);
  };

  ContentSlider.prototype.slideOffset = function (i) {
    var w = this.viewport ? this.viewport.clientWidth : 0;
    return w > 0 ? i * w : 0;
  };

  ContentSlider.prototype.goTo = function (i) {
    var items = this.getItems();
    if (!items.length || !this.viewport) return;
    this.index = ((i % items.length) + items.length) % items.length;
    var left = this.slideOffset(this.index);

    if (this.viewport.scrollTo) {
      this.viewport.scrollTo({ left: left, behavior: "smooth" });
    } else {
      this.viewport.scrollLeft = left;
    }
    this.updateDots();
  };

  ContentSlider.prototype.isAlwaysOn = function () {
    return this.root.hasAttribute("data-slider-always");
  };

  ContentSlider.prototype.handleScroll = function () {
    if (!this.viewport || (!MOBILE_MQ.matches && !this.isAlwaysOn())) return;
    var w = this.viewport.clientWidth;
    if (!w) return;
    var closest = Math.round(this.viewport.scrollLeft / w);
    var items = this.getItems();
    closest = Math.max(0, Math.min(closest, items.length - 1));
    if (closest !== this.index) {
      this.index = closest;
      this.updateDots();
    }
  };

  ContentSlider.prototype.startAuto = function () {
    var self = this;
    this.stopAuto();
    var items = this.getItems();
    if ((!MOBILE_MQ.matches && !this.isAlwaysOn()) || items.length <= 1) return;
    this.timer = window.setInterval(function () {
      self.goTo(self.index + 1);
    }, 5500);
  };

  ContentSlider.prototype.stopAuto = function () {
    if (this.timer) window.clearInterval(this.timer);
    this.timer = null;
  };

  ContentSlider.prototype.enable = function () {
    if (!this.track || !this.viewport) return;
    var self = this;
    var attempts = 0;
    this.buildControls();
    this.root.classList.add("is-active");
    if (this.controlsHost) this.controlsHost.hidden = false;
    this.track.style.transform = "";
    this.viewport.addEventListener("scroll", this.onScroll, { passive: true });

    function apply() {
      attempts += 1;
      var w = self.syncSlideWidth();
      self.buildDots();
      if (!w && attempts < 20) {
        requestAnimationFrame(apply);
        return;
      }
      self.goTo(self.index);
      self.startAuto();
    }

    apply();
  };

  ContentSlider.prototype.disable = function () {
    this.stopAuto();
    this.root.classList.remove("is-active");
    if (this.viewport) {
      this.viewport.removeEventListener("scroll", this.onScroll);
      this.viewport.scrollLeft = 0;
    }
    if (this.track) this.track.style.transform = "";
    this.getItems().forEach(function (li) {
      li.style.flex = "";
      li.style.width = "";
      li.style.maxWidth = "";
    });
    if (this.controlsHost) this.controlsHost.hidden = true;
  };

  ContentSlider.prototype.update = function () {
    if (MOBILE_MQ.matches || this.isAlwaysOn()) this.enable();
    else this.disable();
  };

  ContentSlider.prototype.refresh = function () {
    if (
      (!MOBILE_MQ.matches && !this.isAlwaysOn()) ||
      !this.root.classList.contains("is-active")
    )
      return;
    this.syncSlideWidth();
    this.goTo(this.index);
  };

  function boot() {
    document.querySelectorAll("[data-content-slider]").forEach(function (root) {
      if (root._contentSlider) return;
      var slider = new ContentSlider(root);
      root._contentSlider = slider;
      instances.push(slider);
      slider.update();
    });
  }

  function onMqChange() {
    instances.forEach(function (s) {
      s.update();
    });
  }

  var resizeTimer;
  window.addEventListener("resize", function () {
    window.clearTimeout(resizeTimer);
    resizeTimer = window.setTimeout(function () {
      instances.forEach(function (s) {
        s.refresh();
      });
    }, 150);
  });

  if (MOBILE_MQ.addEventListener) MOBILE_MQ.addEventListener("change", onMqChange);
  else MOBILE_MQ.addListener(onMqChange);

  boot();
  window.addEventListener("load", boot);

  document.querySelectorAll(".section-inner.reveal").forEach(function (el) {
    if (!("IntersectionObserver" in window)) return;
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          instances.forEach(function (s) {
            s.refresh();
          });
        }
      });
    });
    observer.observe(el);
  });
})();
