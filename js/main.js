(function () {
  const header = document.querySelector(".site-header");
  const toggle = document.querySelector(".nav-toggle");
  let nav = document.querySelector(".main-nav");

  function siteBase() {
    var p = location.pathname;
    if (/\/(articles|plans|news|careers)\//.test(p)) return "../";
    return "";
  }

  function apiUrl(path) {
    return siteBase() + "api/" + path;
  }

  function currentPageName() {
    var path = location.pathname.split("/").pop() || "index.html";
    return path.split("?")[0];
  }

  function isNavActive(href) {
    var target = href.split("?")[0].split("#")[0];
    var current = currentPageName();
    if (target === current) return true;
    if (current === "index.html" && target === "index.html") return true;
    var subMatch = location.pathname.match(/\/(articles|plans|news|careers)\//);
    if (subMatch && target === subMatch[1] + ".html") return false;
    return false;
  }

  let navToggleBound = false;

  function bindNavToggle() {
    nav = document.querySelector(".main-nav");
    if (!toggle || !nav || navToggleBound) return;
    navToggleBound = true;

    toggle.addEventListener("click", () => {
      const open = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", open);
      document.body.classList.toggle("nav-open", open);
    });

    nav.querySelectorAll("a").forEach((link) => {
      link.addEventListener("click", () => {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
        document.body.classList.remove("nav-open");
      });
    });
  }

  function renderNav() {
    var site = window.SITE_DATA || {};
    var items = site.navigation || [];
    nav = document.querySelector(".main-nav");
    if (!nav || items.length === 0) return;

    var base = siteBase();
    var html = items
      .filter(function (item) {
        return item.visible !== false;
      })
      .map(function (item) {
        var href = base + (item.href || "#");
        var cls = item.cta ? "nav-cta" : "";
        if (isNavActive(item.href || "")) {
          cls = (cls ? cls + " " : "") + "active";
        }
        return (
          '<a href="' +
          href +
          '"' +
          (cls ? ' class="' + cls + '"' : "") +
          ">" +
          (item.label || "") +
          "</a>"
        );
      })
      .join("");

    nav.innerHTML = html;
    bindNavToggle();
  }

  function injectSeo() {
    var site = window.SITE_DATA || {};
    var meta = site.meta || {};
    var brand = site.brand || {};

    if (meta.description) {
      var desc = document.querySelector('meta[name="description"]');
      if (desc) desc.setAttribute("content", meta.description);
    }

    if (meta.ogImage) {
      var og = document.querySelector('meta[property="og:image"]');
      if (og) og.setAttribute("content", siteBase() + meta.ogImage);
    }

    if (meta.analyticsId && !document.getElementById("ga-script")) {
      var s = document.createElement("script");
      s.id = "ga-script";
      s.async = true;
      s.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(meta.analyticsId);
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      window.gtag = function () {
        window.dataLayer.push(arguments);
      };
      window.gtag("js", new Date());
      window.gtag("config", meta.analyticsId);
    }

    if (brand.logo) {
      var logo = document.querySelector(".brand-logo");
      if (logo) logo.setAttribute("src", siteBase() + brand.logo);
    }
  }

  function initInquiryForms() {
    document.querySelectorAll(".contact-form, .home-inquiry-form").forEach(function (form) {
      if (form.dataset.inquiryBound) return;
      form.dataset.inquiryBound = "1";

      if (!form.querySelector('[name="website"]')) {
        var hp = document.createElement("input");
        hp.type = "text";
        hp.name = "website";
        hp.tabIndex = -1;
        hp.autocomplete = "off";
        hp.style.cssText = "position:absolute;left:-9999px;height:0;width:0;opacity:0";
        form.appendChild(hp);
      }

      form.addEventListener("submit", function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var orig = btn ? btn.textContent : "";
        if (btn) {
          btn.disabled = true;
          btn.textContent = "กำลังส่ง...";
        }

        var source = form.classList.contains("home-inquiry-form") ? "home" : "contact";
        var fd = new FormData(form);
        var payload = {
          topic: fd.get("topic") || "",
          name: fd.get("name") || "",
          phone: fd.get("phone") || "",
          email: fd.get("email") || "",
          message: fd.get("message") || "",
          source: source,
          website: fd.get("website") || "",
        };

        fetch(apiUrl("inquiry-submit.php"), {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        })
          .then(function (r) {
            return r.json();
          })
          .then(function (data) {
            if (!data.ok) throw new Error(data.error || "ส่งไม่สำเร็จ");
            if (btn) btn.textContent = "ส่งแล้ว — ขอบคุณครับ";
            form.reset();
            var note = form.querySelector(".form-note");
            if (note && data.message) note.textContent = data.message;
          })
          .catch(function (err) {
            if (btn) btn.textContent = err.message || "ส่งไม่สำเร็จ";
          })
          .finally(function () {
            setTimeout(function () {
              if (btn) {
                btn.textContent = orig;
                btn.disabled = false;
              }
            }, 3500);
          });
      });
    });

    var params = new URLSearchParams(location.search);
    var topic = params.get("topic");
    if (topic) {
      var sel = document.querySelector('#topic, #home-topic, select[name="topic"]');
      if (sel) sel.value = topic;
    }
  }

  function onSiteReady(cb) {
    if (window.SITE_DATA) {
      cb();
      return;
    }
    var s = document.createElement("script");
    s.src = siteBase() + "js/site-data.js";
    s.onload = cb;
    s.onerror = cb;
    document.head.appendChild(s);
  }

  if (header) {
    window.addEventListener(
      "scroll",
      () => header.classList.toggle("scrolled", window.scrollY > 8),
      { passive: true }
    );
  }

  onSiteReady(function () {
    renderNav();
    injectSeo();
    initInquiryForms();
  });

  if (!window.SITE_DATA || !window.SITE_DATA.navigation) {
    bindNavToggle();
  }

  const reveals = document.querySelectorAll(".reveal");
  if (reveals.length && "IntersectionObserver" in window) {
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) {
            e.target.classList.add("visible");
            io.unobserve(e.target);
          }
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -40px 0px" }
    );
    reveals.forEach((el) => io.observe(el));
  } else {
    reveals.forEach((el) => el.classList.add("visible"));
  }

  if (!document.querySelector(".contact-dock")) {
    var quoteHref = document.getElementById("inquiry")
      ? "#inquiry"
      : siteBase() + "contact.html?topic=insurance";

    var dock = document.createElement("nav");
    dock.className = "contact-dock";
    dock.setAttribute("aria-label", "ติดต่อด่วน");
    dock.innerHTML =
      '<div class="contact-dock-menu" id="contact-dock-menu" role="menu" aria-hidden="true">' +
      '<a href="tel:0852925320" class="contact-dock-action contact-dock-action--phone" role="menuitem">' +
      '<svg class="contact-dock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>' +
      '<span class="contact-dock-label">โทร</span></a>' +
      '<a href="' + siteBase() + 'contact.html" class="contact-dock-action contact-dock-action--line" role="menuitem">' +
      '<svg class="contact-dock-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>' +
      '<span class="contact-dock-label">แอดไลน์</span></a>' +
      '<a href="' +
      quoteHref +
      '" class="contact-dock-action contact-dock-action--quote" role="menuitem">' +
      '<svg class="contact-dock-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>' +
      '<span class="contact-dock-label">ใบเสนอเบี้ย</span></a>' +
      "</div>" +
      '<button type="button" class="contact-dock-toggle" aria-expanded="false" aria-controls="contact-dock-menu" aria-label="เปิดเมนูติดต่อ">' +
      '<span class="contact-dock-toggle-icon contact-dock-toggle-icon--chat" aria-hidden="true">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span>' +
      '<span class="contact-dock-toggle-icon contact-dock-toggle-icon--close" aria-hidden="true">' +
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25"><path d="M18 6L6 18M6 6l12 12"/></svg></span>' +
      "</button>";

    document.body.appendChild(dock);
    document.body.classList.add("has-contact-dock");

    var backdrop = document.createElement("div");
    backdrop.className = "contact-dock-backdrop";
    backdrop.setAttribute("data-dock-backdrop", "");
    backdrop.setAttribute("aria-hidden", "true");
    document.body.appendChild(backdrop);

    var dockToggle = dock.querySelector(".contact-dock-toggle");
    var menu = dock.querySelector(".contact-dock-menu");

    function setDockOpen(open) {
      dock.classList.toggle("is-open", open);
      backdrop.classList.toggle("is-visible", open);
      dockToggle.setAttribute("aria-expanded", open ? "true" : "false");
      dockToggle.setAttribute("aria-label", open ? "ปิดเมนูติดต่อ" : "เปิดเมนูติดต่อ");
      menu.setAttribute("aria-hidden", open ? "false" : "true");
      backdrop.setAttribute("aria-hidden", open ? "false" : "true");
    }

    dockToggle.addEventListener("click", function () {
      setDockOpen(!dock.classList.contains("is-open"));
    });

    backdrop.addEventListener("click", function () {
      setDockOpen(false);
    });

    dock.querySelectorAll(".contact-dock-action").forEach(function (link) {
      link.addEventListener("click", function () {
        setDockOpen(false);
      });
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && dock.classList.contains("is-open")) {
        setDockOpen(false);
      }
    });
  }
})();
