/**
 * Shared landing page block renderer (preview + live site)
 */
window.PageBlockRender = (function () {
  var TEXT_WRAP = "white-space:pre-wrap;overflow-wrap:anywhere;word-wrap:break-word;word-break:break-word;line-break:anywhere;max-width:100%";

  function esc(s) {
    var d = document.createElement("div");
    d.textContent = s == null ? "" : String(s);
    return d.innerHTML;
  }

  function imgSrc(path, base) {
    if (!path) return "";
    base = base || "";
    if (/^https?:\/\//i.test(path)) return path;
    return base + path;
  }

  function isVisible(sec) {
    if (sec.isVisible === false || sec.visible === false) return false;
    return true;
  }

  function sectionWrap(sec, inner, alt) {
    alt = alt != null ? alt : sec.alt;
    var anchor = sec.anchor || "";
    var idAttr = anchor ? ' id="' + esc(anchor) + '"' : "";
    return (
      '<section class="section lp-block lp-block--' + esc(sec.type || "") + (alt ? " section-alt" : "") + '"' + idAttr + ">" +
      '<div class="section-inner reveal">' + inner + "</div></section>"
    );
  }

  function sectionTitle(sec) {
    return String((sec && (sec.title || sec.heading)) || "").trim();
  }

  function blockHeading(sec, fallbackIcon) {
    var title = sectionTitle(sec);
    var subtitle = String(sec.subtitle || "").trim();
    if (!title && !subtitle) return "";
    var iconKey = "";
    if (sec.showIcon !== false) {
      iconKey = sec.icon || fallbackIcon || "";
    }
    var iconAttr = iconKey ? ' data-icon="' + esc(iconKey) + '"' : "";
    return (
      '<header class="section-header"' + iconAttr + ">" +
      (title ? "<h2>" + esc(title) + "</h2>" : "") +
      (subtitle ? "<p>" + esc(subtitle) + "</p>" : "") +
      "</header>"
    );
  }

  function btnHtml(text, href, base, cls) {
    if (!text) return "";
    href = href || "#";
    var external = /^https?:\/\//i.test(href) || /^tel:/i.test(href) || /^mailto:/i.test(href);
    var url = external ? href : base + String(href).replace(/^\//, "");
    return '<a href="' + esc(url) + '" class="' + (cls || "btn btn-primary") + '"' +
      (external && !/^tel:/i.test(href) && !/^mailto:/i.test(href) ? ' target="_blank" rel="noopener"' : "") + ">" + esc(text) + "</a>";
  }

  function imageHtml(img, base, cls) {
    if (!img || !img.src) return "";
    return '<figure class="lp-block-image' + (cls ? " " + cls : "") + '"><img src="' + imgSrc(img.src, base) + '" alt="' + esc(img.alt || "") + '" loading="lazy" decoding="async"></figure>';
  }

  function proseParagraphs(text) {
    var raw = (text || "").trim();
    if (!raw) return "";
    return raw.split(/\n{2,}/).map(function (part) {
      return '<p lang="th" style="color:var(--muted);margin:0 0 1.5rem;line-height:1.75;' + TEXT_WRAP + '">' + esc(part.replace(/\n/g, " ").trim()) + "</p>";
    }).join("");
  }

  function planHtmlBody(text) {
    var raw = (text || "").trim();
    if (!raw) return "";
    if (/<[a-z][\s\S]*>/i.test(raw)) {
      return '<div class="plan-overview-body">' + raw + "</div>";
    }
    return proseParagraphs(raw);
  }

  function cleanSubtitle(sub) {
    sub = String(sub || "").trim();
    return sub === "คำอธิบายย่อย" ? "" : sub;
  }

  function planBlockWrap(sec, inner) {
    if (!inner) return "";
    var anchor = sec.anchor || "";
    var cls = sec.type === "faq" ? ' class="plan-faq"' : "";
    if (anchor) {
      return '<section id="' + esc(anchor) + '"' + cls + ">" + inner + "</section>";
    }
    return '<div class="plan-block plan-block--' + esc(sec.type || "block") + '">' + inner + "</div>";
  }

  function planFaqChevron() {
    if (window.LucideIcons) {
      return '<span class="faq-item__icon" aria-hidden="true">' + LucideIcons.icon("chevron-down", { size: 20, strokeWidth: 2.25 }) + "</span>";
    }
    return '<span class="faq-item__icon" aria-hidden="true"><i data-lucide="chevron-down" aria-hidden="true"></i></span>';
  }

  function renderPlanBlock(sec, ctx) {
    ctx = ctx || {};
    var base = ctx.base || "";
    var type = sec.type || "text";
    var title = sectionTitle(sec);
    var subtitle = cleanSubtitle(sec.subtitle);

    switch (type) {
      case "heading": {
        var inner = (title ? "<h2>" + esc(title) + "</h2>" : "") + (subtitle ? "<p>" + esc(subtitle) + "</p>" : "");
        return planBlockWrap(sec, inner);
      }
      case "text":
        return planBlockWrap(sec, planHtmlBody(sec.description));
      case "image": {
        var src = ctx.forceImageSrc || (sec.image && sec.image.src) || "";
        if (!src) return "";
        return planBlockWrap(
          sec,
          '<figure class="plan-section-cover"><img src="' +
            imgSrc(src, base) +
            '" alt="' +
            esc(sec.image && sec.image.alt ? sec.image.alt : "") +
            '" width="960" height="540" loading="lazy" decoding="async"></figure>'
        );
      }
      case "customHtml":
        return planBlockWrap(sec, sec.customHtml || "");
      case "specTable": {
        var specRows = (sec.items || [])
          .filter(function (it) { return it.isVisible !== false; })
          .map(function (item) {
            return "<tr><th>" + esc(item.title || "") + "</th><td>" + esc(item.description || "") + "</td></tr>";
          })
          .join("");
        return planBlockWrap(sec, '<table class="plan-spec-table">' + specRows + "</table>");
      }
      case "bulletList": {
        var bullets = (sec.items || [])
          .filter(function (it) { return it.isVisible !== false; })
          .map(function (item) {
            var inner = "";
            if (item.title) inner += "<strong>" + esc(item.title) + "</strong>";
            if (item.title && item.description) inner += " — ";
            if (item.description) inner += esc(item.description);
            return inner ? "<li>" + inner + "</li>" : "";
          })
          .join("");
        return planBlockWrap(sec, bullets ? "<ul>" + bullets + "</ul>" : "");
      }
      case "infoBlocks": {
        var blocks = (sec.items || [])
          .filter(function (it) { return it.isVisible !== false; })
          .map(function (item) {
            return (
              '<div class="info-block"><h4>' +
              esc(item.title || "") +
              '</h4><div class="info-block__text">' +
              (item.description || item.text || "") +
              "</div></div>"
            );
          })
          .join("");
        var head = title ? "<h2>" + esc(title) + "</h2>" : "";
        return planBlockWrap(sec, head + '<div class="two-col-blocks">' + blocks + "</div>");
      }
      case "faq": {
        var qs = (sec.items || [])
          .filter(function (it) { return it.isVisible !== false; })
          .map(function (it) {
            return (
              '<details class="faq-item">' +
              '<summary class="faq-item__summary">' +
              '<div class="faq-item__question">' +
              esc(it.title || "คำถาม") +
              "</div>" +
              planFaqChevron() +
              "</summary>" +
              '<div class="faq-item__answer"><div class="faq-item__body">' +
              planHtmlBody(it.description) +
              "</div></div></details>"
            );
          })
          .join("");
        var faqHead = title ? "<h2>" + esc(title) + "</h2>" : "";
        return planBlockWrap(sec, faqHead + qs);
      }
      default:
        return renderBlock(sec, ctx);
    }
  }

  function renderPlanSections(sections, ctx) {
    ctx = ctx || {};
    var usedCardImage = false;
    return (sections || [])
      .filter(isVisible)
      .map(function (sec) {
        var blockCtx = ctx;
        if (!usedCardImage && sec.type === "image" && ctx.cardImage) {
          blockCtx = Object.assign({}, ctx, { forceImageSrc: ctx.cardImage });
          usedCardImage = true;
        }
        return renderPlanBlock(sec, blockCtx);
      })
      .join("");
  }

  function planNavEntries(sections) {
    var navMap = { overview: "ภาพรวม", benefits: "จุดเด่น", specs: "ข้อมูลแผน", who: "เหมาะกับใคร", faq: "คำถามที่พบบ่อย" };
    return (sections || [])
      .filter(isVisible)
      .filter(function (sec) {
        if (sec.anchor) return true;
        if ((sec.type === "heading" || sec.type === "faq" || sec.type === "infoBlocks") && sectionTitle(sec)) return true;
        return false;
      })
      .map(function (sec, index) {
        var anchor = sec.anchor || sec.id || ("sec-" + index);
        var label = sec.navLabel || navMap[sec.anchor] || sectionTitle(sec) || "";
        return { anchor: anchor, label: label };
      });
  }

  function videoPlaceholder() {
    return (
      '<div class="lp-video-wrap lp-video-wrap--placeholder">' +
      '<div class="lp-video-placeholder">' +
      '<span class="lp-video-placeholder-icon" aria-hidden="true">▶</span>' +
      '<span class="lp-video-placeholder-text">วิดีโอ — อัปโหลดไฟล์หรือวาง URL</span>' +
      "</div></div>"
    );
  }

  function videoHtml(sec, ctx) {
    ctx = ctx || {};
    var base = ctx.base || "";
    var fileSrc = String(sec.videoSrc || "").trim();
    var url = String(sec.videoUrl || "").trim();
    if (fileSrc) {
      return (
        '<div class="lp-video-wrap lp-video-wrap--file">' +
        '<video src="' + esc(imgSrc(fileSrc, base)) + '" controls playsinline preload="metadata"></video></div>'
      );
    }
    if (!url) {
      return ctx.preview ? videoPlaceholder() : "";
    }
    if (/\.(mp4|webm|ogg|mov)(\?|#|$)/i.test(url)) {
      return (
        '<div class="lp-video-wrap lp-video-wrap--file">' +
        '<video src="' + esc(/^https?:\/\//i.test(url) ? url : imgSrc(url, base)) + '" controls playsinline preload="metadata"></video></div>'
      );
    }
    var m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]+)/i);
    if (m) {
      return '<div class="lp-video-wrap"><iframe src="https://www.youtube.com/embed/' + esc(m[1]) + '" title="วิดีโอ" allowfullscreen loading="lazy"></iframe></div>';
    }
    var vm = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i);
    if (vm) {
      return '<div class="lp-video-wrap"><iframe src="https://player.vimeo.com/video/' + esc(vm[1]) + '" title="วิดีโอ" allowfullscreen loading="lazy"></iframe></div>';
    }
    if (/^https?:\/\//i.test(url)) {
      return '<div class="lp-video-wrap"><iframe src="' + esc(url) + '" title="วิดีโอ" allowfullscreen loading="lazy"></iframe></div>';
    }
    return "";
  }

  function videoEmbed(url) {
    return videoHtml({ videoUrl: url }, {});
  }

  function socialIconHtml(iconKey) {
    var key = String(iconKey || "globe").toLowerCase();
    if (window.LucideIcons && LucideIcons.brand) {
      var branded = LucideIcons.brand(key, { size: 22, className: "lp-social-icon-svg" });
      if (branded) return branded;
    }
    if (window.LucideIcons) {
      return LucideIcons.icon(key, { size: 22, strokeWidth: 2, className: "lp-social-icon-svg", brand: false });
    }
    return (
      '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="lp-social-icon-svg" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/></svg>'
    );
  }

  function guessSocialIcon(item) {
    if (item.icon) return item.icon;
    var t = String((item.title || "") + " " + (item.subtitle || item.description || "")).toLowerCase();
    if (t.indexOf("facebook") >= 0 || t.indexOf("fb") >= 0) return "facebook";
    if (t.indexOf("line") >= 0) return "line";
    if (t.indexOf("youtube") >= 0) return "youtube";
    if (t.indexOf("instagram") >= 0) return "instagram";
    if (t.indexOf("mail") >= 0 || t.indexOf("email") >= 0) return "mail";
    return "globe";
  }

  function socialLabelParts(item) {
    var title = String(item.title || "").trim();
    var sub = String(item.subtitle || item.description || "").trim();
    if (!sub && title.indexOf(" ") > 0) {
      var sp = title.indexOf(" ");
      sub = title.slice(sp + 1).trim();
      title = title.slice(0, sp).trim();
    }
    return { title: title, sub: sub };
  }

  function socialLinksHtml(sec, base) {
    base = base || "";
    var items = (sec.items || []).filter(function (it) {
      return it.isVisible !== false;
    });
    var cards = items
      .map(function (item) {
        var icon = guessSocialIcon(item);
        var parts = socialLabelParts(item);
        var href = item.buttonLink || item.href || item.link || "#";
        var external = /^https?:\/\//i.test(href) || /^mailto:/i.test(href);
        var url = external ? href : base + String(href).replace(/^\//, "");
        return (
          '<li class="lp-social-item">' +
          '<a href="' +
          esc(url) +
          '" class="lp-social-card lp-social-card--' +
          esc(icon) +
          '"' +
          (external && !/^mailto:/i.test(href) ? ' target="_blank" rel="noopener"' : "") +
          ">" +
          '<span class="lp-social-icon" aria-hidden="true">' +
          socialIconHtml(icon) +
          "</span>" +
          '<span class="lp-social-text">' +
          (parts.title ? "<strong>" + esc(parts.title) + "</strong>" : "") +
          (parts.sub ? "<span>" + esc(parts.sub) + "</span>" : "") +
          "</span></a></li>"
        );
      })
      .join("");
    var foot =
      sec.linkHref && sec.linkText
        ? '<p class="lp-social-foot"><a href="' +
          esc(/^https?:\/\//i.test(sec.linkHref) ? sec.linkHref : base + sec.linkHref.replace(/^\//, "")) +
          '" class="lp-social-more"' +
          (/^https?:\/\//i.test(sec.linkHref) ? ' target="_blank" rel="noopener"' : "") +
          ">" +
          esc(sec.linkText) +
          "</a></p>"
        : "";
    return '<ul class="lp-social-list">' + cards + "</ul>" + foot;
  }

  function cardGridHtml(sec, base, cols) {
    cols = cols || sec.columns || 3;
    var items = (sec.items || []).filter(function (it) { return it.isVisible !== false; });
    var cards = items.map(function (item) {
      var img = item.image && item.image.src ? imageHtml(item.image, base, "lp-card-image") : "";
      var link = item.buttonText ? btnHtml(item.buttonText, item.buttonLink, base, "service-card-link") : "";
      return (
        '<article class="lp-card">' + img +
        '<div class="lp-card-body">' +
        (item.subtitle ? '<span class="lp-card-meta">' + esc(item.subtitle) + "</span>" : "") +
        (item.title ? "<h3>" + esc(item.title) + "</h3>" : "") +
        (item.description ? "<p>" + esc(item.description) + "</p>" : "") +
        link + "</div></article>"
      );
    }).join("");
    return blockHeading(sec, "grid") + '<div class="lp-card-grid lp-card-grid--' + cols + '">' + cards + "</div>";
  }

  function profilePanelHtml(agent) {
    agent = agent || {};
    var phone = agent.phoneDisplay && agent.phoneDisplay !== "-"
      ? '<a href="tel:' + esc(agent.phone || "") + '">' + esc(agent.phoneDisplay) + "</a>"
      : esc(agent.phoneDisplay || "-");
    return (
      '<article class="profile-panel"><header class="profile-panel-head"><h2>ข้อมูลตัวแทน</h2><p>' +
      esc(agent.name || "") + " · " + esc(agent.branch || "") +
      '</p></header><dl class="profile-strip">' +
      '<div class="profile-item"><dt>ชื่อ-นามสกุล</dt><dd>' + esc(agent.name || "") + "</dd></div>" +
      '<div class="profile-item"><dt>ตำแหน่ง</dt><dd>' + esc(agent.title || "") + "</dd></div>" +
      '<div class="profile-item"><dt>สาขา</dt><dd>' + esc(agent.branch || "") + "</dd></div>" +
      '<div class="profile-item"><dt>โทรศัพท์</dt><dd>' + phone + "</dd></div>" +
      '<div class="profile-item"><dt>ใบอนุญาตตัวแทน เลขที่</dt><dd>' + esc(agent.license || "") + "</dd></div>" +
      "</dl></article>"
    );
  }

  function teamUsesItemGrid(sec) {
    var items = sec.items || [];
    if (!items.length) return false;
    return items.some(function (it) {
      if (!it || it.isVisible === false) return false;
      if (it.image && it.image.src) return true;
      var t = String(it.title || "").trim();
      var s = String(it.subtitle || it.description || "").trim();
      if (t && t !== "ชื่อ") return true;
      if (s && s !== "ตำแหน่ง") return true;
      return false;
    });
  }

  function renderLegacyTeam(sec, base) {
    if (teamUsesItemGrid(sec)) {
      var members = sec.items.filter(function (it) { return it.isVisible !== false; }).map(function (it) {
        var av = it.image && it.image.src
          ? '<img class="lp-team-photo" src="' + imgSrc(it.image.src, base) + '" alt="' + esc(it.image.alt || it.title || "") + '">'
          : '<span class="team-avatar">' + esc((it.title || "?").charAt(0)) + "</span>";
        return '<article class="lp-team-member">' + av + "<h3>" + esc(it.title || "") + "</h3><p>" + esc(it.subtitle || it.description || "") + "</p></article>";
      }).join("");
      return sectionWrap(sec, blockHeading(sec, "users") + '<div class="lp-team-grid">' + members + "</div>", sec.alt);
    }
    var avatars = (sec.avatars || []).map(function (a) {
      return '<span class="team-avatar' + (String(a).indexOf("+") === 0 ? " more" : "") + '">' + esc(a) + "</span>";
    }).join("");
    return sectionWrap(
      sec,
      blockHeading(sec, "users") +
      '<div class="team-teaser"><div class="team-avatars" aria-hidden="true">' + avatars +
      '</div><div><p>' + esc(sec.text || sec.description || "") + "</p></div></div>",
      sec.alt
    );
  }

  function cardGridColClass(cols) {
    cols = cols || 3;
    if (cols === 4) return "";
    if (cols === 2) return " product-card-grid--2";
    return " product-card-grid--3";
  }

  function cardGridPreviewHtml(sec) {
    var cols = sec.columns || 3;
    var labels = { articles: "บทความ", careers: "แนะนำอาชีพ", news: "ข่าว/กิจกรรม" };
    var label = labels[sec.source] || "รายการ";
    var count = cols === 4 ? 4 : cols === 2 ? 2 : 3;
    var cards = "";
    for (var i = 0; i < count; i++) {
      cards +=
        "<li><article class=\"product-card lp-card-skeleton\">" +
        '<div class="product-card-media lp-card-skeleton-media" aria-hidden="true"></div>' +
        '<div class="product-card-body">' +
        '<p class="product-card-meta">' + esc(label) + "</p>" +
        '<h3><span class="lp-card-skeleton-line"></span></h3>' +
        '<p class="product-card-excerpt"><span class="lp-card-skeleton-line lp-card-skeleton-line--short"></span></p>' +
        "</div></article></li>";
    }
    return (
      '<ul class="product-card-grid' + cardGridColClass(cols) + ' lp-card-grid-preview" aria-hidden="true">' +
      cards +
      "</ul>" +
      '<p class="lp-dynamic-note">ดึงรายการ' +
      esc(label) +
      "จากระบบอัตโนมัติ — แก้รายการที่เมนูเนื้อหา</p>"
    );
  }

  function featuredHtml(sec, base) {
    var slug = String(sec.slug || "").trim();
    var link = sec.buttonLink || (slug ? "careers/" + slug + ".html" : "#");
    var btnText = sec.buttonText || "อ่านรายละเอียด →";
    var meta = String(sec.featureMeta || "").trim();
    var featTitle = String(sec.featureTitle || "").trim();
    var desc = String(sec.description || "").trim();
    var bullets = (sec.bullets || [])
      .map(function (b) {
        return String(b || "").trim();
      })
      .filter(Boolean);
    if (!bullets.length && sec.items && sec.items.length) {
      bullets = sec.items
        .filter(function (it) {
          return it && it.isVisible !== false;
        })
        .map(function (it) {
          return it.title;
        })
        .filter(Boolean);
    }

    var linkUrl = /^https?:\/\//i.test(link) ? link : base + String(link).replace(/^\//, "");

    var imageBlock = "";
    if (sec.image && sec.image.src) {
      imageBlock =
        '<a href="' +
        esc(linkUrl) +
        '" class="career-featured-media">' +
        '<img src="' +
        esc(imgSrc(sec.image.src, base)) +
        '" alt="' +
        esc(sec.image.alt || featTitle) +
        '" loading="lazy" decoding="async" width="640" height="427"></a>';
    } else {
      imageBlock = '<div class="career-featured-media lp-card-skeleton-media" aria-hidden="true"></div>';
    }

    var h2Inner = featTitle ? esc(featTitle) : "";
    if (featTitle && link && link !== "#") {
      h2Inner = '<a href="' + esc(linkUrl) + '">' + esc(featTitle) + "</a>";
    }

    var bulletsHtml = bullets
      .map(function (b) {
        return "<li>" + esc(b) + "</li>";
      })
      .join("");

    return (
      '<div class="career-featured-layout">' +
      imageBlock +
      '<div class="career-featured-body">' +
      (meta ? '<p class="product-card-meta">' + esc(meta) + "</p>" : "") +
      (h2Inner ? "<h2>" + h2Inner + "</h2>" : "") +
      (desc ? "<p>" + esc(desc) + "</p>" : "") +
      (bulletsHtml ? '<ul class="career-featured-list">' + bulletsHtml + "</ul>" : "") +
      btnHtml(btnText, link, base, "btn btn-primary") +
      "</div></div>"
    );
  }

  function renderBlock(sec, ctx) {
    if (isPreview) {
      return (
        '<div class="claim-review-slider-wrap lp-claim-preview">' +
        '<article class="claim-review-card">' +
        '<div class="claim-review-card-media lp-card-skeleton-media" aria-hidden="true"></div>' +
        '<div class="claim-review-card-body">' +
        '<p class="claim-review-category">รีวิวเคลม</p>' +
        "<h3>ตัวอย่างการ์ดรีวิว</h3>" +
        '<p class="claim-review-summary">ดึงรายการรีวิวจากเมนูเนื้อหา — บนเว็บจริงแสดงเป็นสไลด์และแกลเลอรี่ด้านล่าง</p>' +
        "</div></article>" +
        '<div class="claim-gallery lp-claim-gallery-preview" aria-hidden="true">' +
        '<div class="lp-claim-thumb"></div><div class="lp-claim-thumb"></div><div class="lp-claim-thumb"></div>' +
        "</div></div>"
      );
    }
    return (
      '<div class="claim-review-slider-wrap">' +
      '<div class="content-slider claim-review-slider" data-claim-slider data-content-slider data-slider-always data-slider-name="รีวิวเคลม">' +
      '<div class="content-slider-viewport">' +
      '<ul class="content-slider-track" id="claim-review-slider-track"></ul>' +
      "</div>" +
      '<div data-slider-controls></div>' +
      "</div>" +
      '<div class="claim-gallery" id="claim-gallery"></div>' +
      '<div class="claim-gallery claim-gallery--more" id="claim-gallery-more" hidden></div>' +
      '<div class="claim-gallery-actions">' +
      '<button type="button" class="btn btn-primary" id="claim-gallery-more-btn" aria-expanded="false" hidden>ดูเพิ่มเติม</button>' +
      "</div></div>"
    );
  }

  function renderBlock(sec, ctx) {
    ctx = ctx || {};
    var base = ctx.base || "";
    var agent = ctx.agent || {};
    var type = sec.type || "text";

    switch (type) {
      case "heroBanner":
        return (
          '<header class="page-hero lp-hero-banner">' +
          (sec.image && sec.image.src ? '<div class="lp-hero-banner-bg" style="background-image:url(' + esc(imgSrc(sec.image.src, base)) + ')"></div>' : "") +
          '<div class="page-hero-inner">' +
          (sec.subtitle ? '<p class="breadcrumb">' + esc(sec.subtitle) + "</p>" : "") +
          (sec.title ? "<h1>" + esc(sec.title) + "</h1>" : "") +
          (sec.description ? "<p>" + esc(sec.description) + "</p>" : "") +
          (sec.buttonText ? '<div class="cta-actions">' + btnHtml(sec.buttonText, sec.buttonLink, base) + "</div>" : "") +
          "</div></header>"
        );
      case "heading":
        return sectionWrap(sec, blockHeading(sec), sec.alt);
      case "text":
        return sectionWrap(sec, blockHeading(sec) + proseParagraphs(sec.description), sec.alt);
      case "image":
        return sectionWrap(sec, imageHtml(sec.image, base) || '<p class="lp-image-placeholder">ยังไม่มีรูปภาพ</p>', sec.alt);
      case "imageText":
        return sectionWrap(
          sec,
          blockHeading(sec) +
          '<div class="lp-split">' + imageHtml(sec.image, base, "lp-split-image") +
          '<div class="lp-split-body">' + proseParagraphs(sec.description) +
          (sec.buttonText ? '<div class="cta-actions">' + btnHtml(sec.buttonText, sec.buttonLink, base) + "</div>" : "") +
          "</div></div>",
          sec.alt
        );
      case "cardGrid2": return sectionWrap(sec, cardGridHtml(sec, base, 2), sec.alt);
      case "cardGrid3": return sectionWrap(sec, cardGridHtml(sec, base, 3), sec.alt);
      case "cardGrid4": return sectionWrap(sec, cardGridHtml(sec, base, 4), sec.alt);
      case "ctaButton":
        return sectionWrap(
          sec,
          '<div class="lp-cta-block">' +
          (sectionTitle(sec) ? "<h2>" + esc(sectionTitle(sec)) + "</h2>" : "") +
          (sec.description ? "<p>" + esc(sec.description) + "</p>" : "") +
          (sec.buttonText ? '<div class="cta-actions">' + btnHtml(sec.buttonText, sec.buttonLink, base) + "</div>" : "") +
          "</div>",
          sec.alt
        );
      case "gallery": {
        var imgs = (sec.items || []).filter(function (it) { return it.image && it.image.src; }).map(function (it) {
          return '<figure class="lp-gallery-item">' + imageHtml(it.image, base) + (it.title ? "<figcaption>" + esc(it.title) + "</figcaption>" : "") + "</figure>";
        }).join("");
        return sectionWrap(sec, blockHeading(sec) + '<div class="lp-gallery">' + imgs + "</div>", sec.alt);
      }
      case "faq": {
        var qs = (sec.items || []).map(function (it, i) {
          return (
            '<details class="lp-faq-item"' + (i === 0 ? " open" : "") + ">" +
            "<summary>" + esc(it.title || "คำถาม") + "</summary>" +
            '<div class="lp-faq-answer">' + proseParagraphs(it.description) + "</div></details>"
          );
        }).join("");
        return sectionWrap(sec, blockHeading(sec, "help") + '<div class="lp-faq">' + qs + "</div>", sec.alt);
      }
      case "team":
        return renderLegacyTeam(sec, base);
      case "review": {
        var reviews = (sec.items || []).map(function (it) {
          return (
            '<blockquote class="lp-review">' +
            '<p>"' + esc(it.description || "") + '"</p>' +
            "<footer>" + esc(it.title || "") + (it.subtitle ? " · " + esc(it.subtitle) : "") + "</footer></blockquote>"
          );
        }).join("");
        return sectionWrap(sec, blockHeading(sec, "star") + '<div class="lp-review-grid">' + reviews + "</div>", sec.alt);
      }
      case "contactInfo":
        return sectionWrap(
          sec,
          blockHeading(sec, "phone") +
          '<div class="lp-contact-info">' + proseParagraphs(sec.description) +
          (sec.buttonText ? btnHtml(sec.buttonText, sec.buttonLink, base, "btn btn-outline") : "") + "</div>",
          sec.alt
        );
      case "video": {
        var videoInner = videoHtml(sec, ctx);
        if (!videoInner) return "";
        return sectionWrap(sec, videoInner, sec.alt);
      }
      case "customHtml":
        return sectionWrap(sec, blockHeading(sec) + '<div class="lp-custom-html">' + (sec.customHtml || "") + "</div>", sec.alt);
      case "prose": {
        var html = (sec.blocks || []).map(function (block) {
          if (block.type === "quote") {
            return '<blockquote style="font-size:1.25rem;line-height:1.75;border-left:4px solid var(--tl-gold);padding-left:1.5rem;margin-bottom:2rem;' + TEXT_WRAP + '">' + esc(block.html) + "</blockquote>";
          }
          if (block.type === "image" && block.src) {
            return imageHtml({ src: block.src, alt: block.alt }, base);
          }
          return proseParagraphs(block.html);
        }).join("");
        if (sec.includeProfile) html += profilePanelHtml(agent);
        return sectionWrap(sec, html, sec.alt);
      }
      case "profile":
        return sectionWrap(sec, profilePanelHtml(agent), sec.alt);
      case "achievements": {
        var tags = (sec.tags || []).length ? sec.tags : (sec.items || []).map(function (t) { return typeof t === "string" ? t : t.title; });
        var tagHtml = tags.map(function (t) {
          return t ? '<span class="achievement-tag">' + esc(t) + "</span>" : "";
        }).join("");
        return sectionWrap(
          sec,
          blockHeading(sec, "trophy") + '<div class="achievements">' + tagHtml + "</div>" +
          (sec.footer || sec.description ? '<p style="margin-top:2rem;color:var(--muted);' + TEXT_WRAP + '">' + esc(sec.footer || sec.description) + "</p>" : ""),
          sec.alt
        );
      }
      case "infoBlocks": {
        var blocks = (sec.items || []).map(function (item) {
          var href = item.buttonLink || item.href || "";
          var title = href
            ? '<h4><a href="' + esc(base + href.replace(/^\//, "")) + '">' + esc(item.title) + "</a></h4>"
            : "<h4>" + esc(item.title) + "</h4>";
          return '<div class="info-block">' + title + "<p>" + esc(item.description || item.text || "") + "</p></div>";
        }).join("");
        return sectionWrap(sec, blockHeading(sec, sec.icon || "grid") + '<div class="two-col-blocks">' + blocks + "</div>", sec.alt);
      }
      case "serviceCards": {
        var cards = (sec.items || []).map(function (item) {
          return (
            "<li><article class=\"service-card\"><div class=\"service-card-body\">" +
            (item.meta || item.subtitle ? '<span class="service-card-meta">' + esc(item.meta || item.subtitle) + "</span>" : "") +
            "<h3>" + esc(item.title || "") + "</h3><p>" + esc(item.text || item.description || "") + "</p>" +
            btnHtml(item.linkText || item.buttonText || "ดูเพิ่ม →", item.href || item.buttonLink, base, "service-card-link") +
            "</div></article></li>"
          );
        }).join("");
        return sectionWrap(sec, blockHeading(sec, sec.icon || "heart") + '<ul class="service-card-grid">' + cards + "</ul>", sec.alt);
      }
      case "cardGrid": {
        var ids = { articles: "articles-card-grid", careers: "career-card-grid", news: "news-card-grid" };
        var cols = sec.columns || 3;
        var colClass = cardGridColClass(cols);
        var inner = ctx.preview
          ? cardGridPreviewHtml(sec)
          : '<ul class="product-card-grid' + colClass + '" id="' + (ids[sec.source] || "articles-card-grid") + '"></ul>';
        return sectionWrap(sec, inner, sec.alt);
      }
      case "featured":
        return sectionWrap(sec, blockHeading(sec, "monitor") + featuredHtml(sec, base), sec.alt);
      case "socialLinks":
        return sectionWrap(sec, blockHeading(sec, sec.icon || "share") + socialLinksHtml(sec, base), sec.alt);
      case "claimWidget":
        return sectionWrap(sec, claimWidgetHtml(!!ctx.preview), sec.alt);
      default:
        return sectionWrap(sec, proseParagraphs(sec.description || ""), sec.alt);
    }
  }

  function renderHero(hero, ctx) {
    ctx = ctx || {};
    if (!isVisible(hero)) return "";
    var meta = ctx.meta || {};
    var bc = hero.breadcrumb || meta.breadcrumb || "";
    return (
      '<header class="page-hero"><div class="page-hero-inner">' +
      '<p class="breadcrumb">' + esc(bc) + "</p>" +
      "<h1>" + esc(hero.title || "") + "</h1>" +
      "<p>" + esc(hero.lead || hero.description || "") + "</p></div></header>"
    );
  }

  function btnClass(variant) {
    if (variant === "outline") return "btn btn-outline";
    if (variant === "white") return "btn btn-white";
    return "btn btn-primary";
  }

  function renderCtaButtons(cta, base, ctx) {
    var buttons = (cta.buttons || []).map(function (btn) {
      return btnHtml(
        btn.label || btn.buttonText,
        btn.href || btn.buttonLink,
        base,
        btnClass(btn.variant)
      );
    }).join("");
    if (!buttons && cta.buttonText) {
      buttons = btnHtml(cta.buttonText, cta.buttonLink, base);
    }
    if (!buttons && ctx && ctx.isPlan) {
      buttons =
        btnHtml("ขอใบเสนอเบี้ย", "../contact.html", base, "btn btn-white") +
        btnHtml("โทร 085-292-5320", "tel:0852925320", base, "btn btn-outline");
    }
    return buttons;
  }

  function renderCta(cta, ctx) {
    ctx = ctx || {};
    if (!isVisible(cta)) return "";
    var base = ctx.base || "";
    var buttons = renderCtaButtons(cta, base, ctx);
    return (
      '<section class="cta-band reveal"><h2>' + esc(cta.title || "") + "</h2>" +
      (cta.lead || cta.description ? "<p>" + esc(cta.lead || cta.description) + "</p>" : "") +
      (buttons ? '<div class="cta-actions">' + buttons + "</div>" : "") +
      "</section>"
    );
  }

  function renderSections(sections, ctx) {
    return (sections || []).filter(isVisible).map(function (sec) {
      return renderBlock(sec, ctx);
    }).join("");
  }

  return {
    esc: esc,
    imgSrc: imgSrc,
    isVisible: isVisible,
    renderBlock: renderBlock,
    renderPlanBlock: renderPlanBlock,
    renderHero: renderHero,
    renderCta: renderCta,
    renderSections: renderSections,
    renderPlanSections: renderPlanSections,
    planNavEntries: planNavEntries,
    TEXT_WRAP: TEXT_WRAP,
  };
})();
