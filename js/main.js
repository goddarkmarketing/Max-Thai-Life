window.mtlViewCountBadge = function (type, id, variant) {
  if (!type || !id) return "";
  var t = String(type).replace(/"/g, "&quot;");
  var s = String(id).replace(/"/g, "&quot;");
  var extra = variant === "overlay" ? " card-view-count--overlay" : "";
  return (
    '<div class="card-view-count' + extra + '">' +
    '<span data-analytics-views data-content-type="' + t + '" data-content-id="' + s + '" hidden></span>' +
    "</div>"
  );
};

window.mtlCardFooter = function (type, id, linkHtml) {
  var stats = window.mtlViewCountBadge(type, id);
  return '<div class="product-card-footer">' + stats + linkHtml + "</div>";
};

document.dispatchEvent(new CustomEvent("mtl:helpers-ready"));

window.mtlPlanSlugFromHref = function (href) {
  return String(href || "")
    .split("?")[0]
    .split("#")[0]
    .replace(/^\.?\//, "")
    .replace(/^plans\//i, "")
    .replace(/\.html$/i, "");
};

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

  function currentPlanCategory() {
    var q = new URLSearchParams(location.search).get("category");
    if (q) return q;
    var hash = (location.hash || "").replace(/^#/, "");
    if (hash && hash !== "all") return hash;
    return null;
  }

  function isNavActive(href, opts) {
    opts = opts || {};
    var target = (href || "").split("?")[0].split("#")[0];
    var current = currentPageName();
    var category = currentPlanCategory();

    if ("childCategory" in opts) {
      if (!opts.childCategory) {
        return current === "plans.html" && !category;
      }
      return category === opts.childCategory;
    }

    if (target === "plans.html" && current === "plans.html") {
      if (opts.parentPlans) {
        return true;
      }
      return !category;
    }

    if (target === current) return true;
    if (current === "index.html" && target === "index.html") return true;
    var subMatch = location.pathname.match(/\/(articles|plans|news|careers)\//);
    if (subMatch && target === subMatch[1] + ".html") return false;
    return false;
  }

  function planCategoryFromHref(href) {
    var match = (href || "").match(/[?&]category=([^&#]+)/);
    return match ? decodeURIComponent(match[1]) : "";
  }

  var navChevron =
    '<span class="nav-chevron" aria-hidden="true">' +
    (window.LucideIcons
      ? LucideIcons.icon("chevron-down", { size: 16, strokeWidth: 2.25 })
      : '<i data-lucide="chevron-down" aria-hidden="true"></i>') +
    "</span>";

  var planCategoryIcons = {
    savings: "piggy-bank",
    protect: "shield-check",
    health: "heart-pulse",
    rider: "file-plus-2",
    pension: "armchair",
    invest: "trending-up",
  };

  function navSubmenuIcon(child) {
    var icon =
      child.icon ||
      planCategoryIcons[child.category || ""] ||
      (child.href && /plans\.html/.test(child.href) && !/[?&]category=/.test(child.href)
        ? "layout-grid"
        : "circle");
    if (window.LucideIcons) {
      return (
        '<span class="nav-submenu-icon" aria-hidden="true">' +
        LucideIcons.icon(icon, { size: 16, strokeWidth: 2 }) +
        "</span>"
      );
    }
    return (
      '<span class="nav-submenu-icon" aria-hidden="true"><i data-lucide="' +
      icon +
      '" aria-hidden="true"></i></span>'
    );
  }

  function renderNavItem(item, base) {
    var href = base + (item.href || "#");
    var children = (item.children || []).filter(function (child) {
      return child.visible !== false;
    });

    if (!children.length) {
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
    }

    var parentCls = "nav-parent";
    if (isNavActive(item.href || "", { parentPlans: true })) {
      parentCls += " active";
    }

    var submenu = children
      .map(function (child) {
        var childHref = base + (child.href || "#");
        var childCat = child.category || planCategoryFromHref(child.href);
        var childCls = "";
        if (isNavActive(child.href || "", { childCategory: childCat })) {
          childCls = " active";
        }
        return (
          '<a href="' +
          childHref +
          '" class="nav-submenu-link' +
          childCls +
          '"' +
          (childCat ? ' data-plan-category="' + childCat + '"' : "") +
          ">" +
          navSubmenuIcon(child) +
          '<span class="nav-submenu-label">' +
          (child.label || "") +
          "</span></a>"
        );
      })
      .join("");

    return (
      '<div class="nav-item nav-item--has-submenu">' +
      '<div class="nav-item-row">' +
      '<a href="' +
      href +
      '" class="' +
      parentCls +
      '">' +
      (item.label || "") +
      navChevron +
      "</a>" +
      '<button type="button" class="nav-submenu-toggle" aria-label="เปิดเมนูย่อย ' +
      (item.label || "") +
      '" aria-expanded="false">' +
      navChevron +
      "</button>" +
      "</div>" +
      '<div class="nav-submenu" role="menu">' +
      submenu +
      "</div>" +
      "</div>"
    );
  }

  function bindNavSubmenus() {
    if (!nav) return;

    var hoverFine = window.matchMedia("(hover: hover) and (pointer: fine)").matches;

    nav.querySelectorAll(".nav-item--has-submenu").forEach(function (item) {
      var closeTimer;

      if (hoverFine) {
        item.addEventListener("mouseenter", function () {
          window.clearTimeout(closeTimer);
          item.classList.add("is-open");
        });
        item.addEventListener("mouseleave", function () {
          closeTimer = window.setTimeout(function () {
            item.classList.remove("is-open");
          }, 150);
        });
      }

      var toggle = item.querySelector(".nav-submenu-toggle");
      if (toggle) {
        toggle.addEventListener("click", function (e) {
          e.preventDefault();
          e.stopPropagation();
          var open = item.classList.toggle("is-open");
          toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
      }
    });

    nav.querySelectorAll(".nav-submenu-link").forEach(function (link) {
      link.addEventListener("click", function () {
        var href = link.getAttribute("href") || "";
        var cat = link.getAttribute("data-plan-category") || planCategoryFromHref(href);
        try {
          if (cat) {
            sessionStorage.setItem("planCategory", cat);
          } else if (/plans\.html/.test(href) && !/[?&]category=/.test(href)) {
            sessionStorage.removeItem("planCategory");
          }
        } catch (err) {}
      });
    });

    nav.querySelectorAll('a.nav-parent[href*="plans.html"]').forEach(function (link) {
      link.addEventListener("click", function () {
        try {
          sessionStorage.removeItem("planCategory");
        } catch (err) {}
      });
    });
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
        return renderNavItem(item, base);
      })
      .join("");

    nav.innerHTML = html;
    bindNavToggle();
    bindNavSubmenus();
    if (window.LucideIcons) LucideIcons.refresh(nav);
  }

  function upsertMeta(attr, key, value) {
    if (!value) return;
    var sel = "meta[" + attr + '="' + key + '"]';
    var el = document.querySelector(sel);
    if (!el) {
      el = document.createElement("meta");
      el.setAttribute(attr, key);
      document.head.appendChild(el);
    }
    el.setAttribute("content", value);
  }

  function upsertLink(rel, href) {
    if (!href) return;
    var sel = 'link[rel="' + rel + '"]';
    var el = document.querySelector(sel);
    if (!el) {
      el = document.createElement("link");
      el.setAttribute("rel", rel);
      document.head.appendChild(el);
    }
    el.setAttribute("href", href);
  }

  function absoluteUrl(path) {
    var base = (window.SITE_DATA && window.SITE_DATA.meta && window.SITE_DATA.meta.siteUrl) || "";
    base = base.replace(/\/$/, "");
    if (!base) return "";
    path = (path || "").replace(/^\//, "");
    if (path === "" || path === "index.html") return base + "/";
    return base + "/" + path;
  }

  function injectSeo() {
    var site = window.SITE_DATA || {};
    var meta = site.meta || {};
    var brand = site.brand || {};
    var agent = site.agent || {};
    var pageKey = currentPageName();
    var pageSeo = (meta.pages && meta.pages[pageKey]) || null;
    var isStaticPage = !!(pageSeo && meta.pages && Object.prototype.hasOwnProperty.call(meta.pages, pageKey));

    var description = (isStaticPage && pageSeo.description) || meta.description || "";
    var title = (isStaticPage && pageSeo.title) || document.title;
    var ogTitle = meta.ogTitle || title;
    var ogDescription = meta.ogDescription || description;
    var ogImage = meta.ogImage ? siteBase() + meta.ogImage : "";

    if (isStaticPage && pageSeo.title) {
      document.title = pageSeo.title;
      title = pageSeo.title;
      ogTitle = pageSeo.title;
    }

    if (description) upsertMeta("name", "description", description);
    if (isStaticPage && pageSeo.indexable === false) {
      upsertMeta("name", "robots", "noindex, nofollow");
    }

    upsertMeta("property", "og:type", "website");
    upsertMeta("property", "og:title", ogTitle);
    upsertMeta("property", "og:description", ogDescription);
    if (ogImage) upsertMeta("property", "og:image", ogImage);

    upsertMeta("name", "twitter:card", ogImage ? "summary_large_image" : "summary");
    upsertMeta("name", "twitter:title", ogTitle);
    upsertMeta("name", "twitter:description", ogDescription);

    if (meta.googleSiteVerification) {
      upsertMeta("name", "google-site-verification", meta.googleSiteVerification);
    }

    var canonicalPath = pageKey === "index.html" ? "" : pageKey;
    var canonical = absoluteUrl(canonicalPath);
    if (canonical && isStaticPage) {
      upsertLink("canonical", canonical);
      upsertMeta("property", "og:url", canonical);
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
      document.querySelectorAll(".brand-logo").forEach(function (logo) {
        logo.setAttribute("src", siteBase() + brand.logo);
      });
    }

    if (pageKey === "index.html" && meta.localBusiness && meta.localBusiness.enabled !== false) {
      var local = meta.localBusiness || {};
      var schema = {
        "@context": "https://schema.org",
        "@type": "InsuranceAgency",
        name: agent.name || brand.name || "Wealth Agent TL",
        description: description,
        url: absoluteUrl(""),
        telephone: agent.phoneDisplay || agent.phone || "",
        address: {
          "@type": "PostalAddress",
          streetAddress: agent.address || local.address || "",
          addressLocality: agent.branch || "",
          addressRegion: local.areaServed || "",
          addressCountry: "TH",
        },
        areaServed: local.areaServed || agent.branch || "",
      };
      if (local.googleBusinessUrl) {
        schema.sameAs = [local.googleBusinessUrl];
      }
      var schemaEl = document.getElementById("local-business-schema");
      if (!schemaEl) {
        schemaEl = document.createElement("script");
        schemaEl.id = "local-business-schema";
        schemaEl.type = "application/ld+json";
        document.head.appendChild(schemaEl);
      }
      schemaEl.textContent = JSON.stringify(schema);
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

  function initReveals() {
    const reveals = document.querySelectorAll(".reveal:not(.visible)");
    if (!reveals.length) return;

    function show(el) {
      el.classList.add("visible");
    }

    function showInViewport() {
      reveals.forEach(function (el) {
        if (el.classList.contains("visible")) return;
        var rect = el.getBoundingClientRect();
        if (rect.top < window.innerHeight && rect.bottom > 0) {
          show(el);
        }
      });
    }

    if ("IntersectionObserver" in window) {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((e) => {
            if (e.isIntersecting) {
              show(e.target);
              io.unobserve(e.target);
            }
          });
        },
        { threshold: 0.08, rootMargin: "0px 0px -20px 0px" }
      );
      reveals.forEach((el) => io.observe(el));
      showInViewport();
      requestAnimationFrame(showInViewport);
    } else {
      reveals.forEach(show);
    }
  }

  initReveals();
  document.addEventListener("landing:rendered", initReveals);

  window.addEventListener("load", function () {
    document.querySelectorAll(".reveal:not(.visible)").forEach(function (el) {
      el.classList.add("visible");
    });
  });

  if (!document.querySelector(".contact-dock")) {
    var site = window.SITE_DATA || {};
    var agent = site.agent || {};
    var dockCfg = site.contactDock || {};
    if (dockCfg.enabled === false) {
      // skip rendering
    } else {
    var phoneRaw = agent.phone || "0852925320";
    var quoteHref = document.getElementById("inquiry")
      ? "#inquiry"
      : siteBase() + "contact.html?topic=insurance";

    var dockItems = (dockCfg.items || []).filter(function (item) {
      return item && item.label && item.href && item.visible !== false;
    });
    if (!dockItems.length) {
      dockItems = [
        { label: "โทร", href: "tel:" + phoneRaw, icon: "phone", color: "#015fd9" },
        { label: "แอดไลน์", href: siteBase() + "contact.html", icon: "message-circle", color: "#06c755" },
        { label: "ใบเสนอเบี้ย", href: quoteHref, icon: "file-text", color: "#38bdf8" },
      ];
    }

    function dockColor(item) {
      if (item.color) return item.color;
      var presets = { phone: "#015fd9", line: "#06c755", quote: "#38bdf8", blue: "#015fd9" };
      return presets[item.style] || "#015fd9";
    }

    function dockHref(href) {
      if (!href) return "#";
      if (/^(https?:|tel:|mailto:|#)/i.test(href)) return href;
      return siteBase() + href.replace(/^\//, "");
    }

    function dockIcon(name, cls) {
      return window.LucideIcons
        ? LucideIcons.defer(name, { size: 24, strokeWidth: 2, className: cls || "contact-dock-icon" })
        : '<i data-lucide="' + name + '" class="' + (cls || "contact-dock-icon") + '" aria-hidden="true"></i>';
    }

    var actionsHtml = dockItems
      .map(function (item, i) {
        var href = item.href === "contact.html?topic=insurance" && document.getElementById("inquiry")
          ? "#inquiry"
          : dockHref(item.href);
        var delay = dockItems.length - i;
        var bg = dockColor(item);
        return (
          '<a href="' +
          href +
          '" class="contact-dock-action" style="background:' +
          bg +
          ";--dock-i:" +
          delay +
          '" role="menuitem">' +
          dockIcon(item.icon || "message-circle") +
          '<span class="contact-dock-label">' +
          (item.label || "") +
          "</span></a>"
        );
      })
      .join("");

    var dock = document.createElement("nav");
    dock.className = "contact-dock";
    dock.setAttribute("aria-label", "ติดต่อด่วน");
    dock.innerHTML =
      '<div class="contact-dock-menu" id="contact-dock-menu" role="menu" aria-hidden="true">' +
      actionsHtml +
      "</div>" +
      '<button type="button" class="contact-dock-toggle" aria-expanded="false" aria-controls="contact-dock-menu" aria-label="เปิดเมนูติดต่อ">' +
      '<span class="contact-dock-toggle-icon contact-dock-toggle-icon--chat" aria-hidden="true">' +
      dockIcon("message-circle", "") +
      "</span>" +
      '<span class="contact-dock-toggle-icon contact-dock-toggle-icon--close" aria-hidden="true">' +
      dockIcon("x", "") +
      "</span>" +
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

    if (window.LucideIcons) LucideIcons.refresh(dock);
    }
  }

  if (window.LucideIcons) LucideIcons.refresh();

  (function loadAnalytics() {
    var pendingFill = false;
    window.mtlScheduleFillViewCounts = function () {
      pendingFill = true;
      if (typeof window.mtlFillViewCounts === "function" && window.mtlFlushPendingViewCounts) {
        window.mtlFlushPendingViewCounts();
      }
    };
    window.mtlFlushPendingViewCounts = function () {
      if (!pendingFill || typeof window.mtlScheduleFillViewCounts !== "function") return;
      if (typeof window.mtlFillViewCounts !== "function") return;
      pendingFill = false;
      window.mtlScheduleFillViewCounts();
    };

    var s = document.createElement("script");
    s.src = siteBase() + "js/analytics.js";
    s.onload = function () {
      if (window.mtlFlushPendingViewCounts) window.mtlFlushPendingViewCounts();
    };
    document.body.appendChild(s);
  })();
})();
