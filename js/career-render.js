(function () {
  var id = document.body.getAttribute("data-career-id");
  var career = window.CAREERS_DETAIL && window.CAREERS_DETAIL[id];
  if (!career) return;

  var base = document.body.getAttribute("data-base") || "";
  var pageUrl = window.location.href.split("#")[0].split("?")[0];

  function resolveImageUrl(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return new URL(base + src, pageUrl).href;
  }

  var imageUrl = resolveImageUrl(career.image);

  document.title = career.title + " | แนะนำอาชีพ | Max Thai Life";

  var metaDesc = document.querySelector('meta[name="description"]');
  if (metaDesc) metaDesc.setAttribute("content", career.description);

  function upsertMeta(attr, key, value) {
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
    var el = document.querySelector('link[rel="' + rel + '"]');
    if (!el) {
      el = document.createElement("link");
      el.setAttribute("rel", rel);
      document.head.appendChild(el);
    }
    el.setAttribute("href", href);
  }

  upsertLink("canonical", pageUrl);
  upsertMeta("property", "og:type", "article");
  upsertMeta("property", "og:title", career.title);
  upsertMeta("property", "og:description", career.description);
  upsertMeta("property", "og:image", imageUrl);
  upsertMeta("property", "og:url", pageUrl);
  upsertMeta("name", "twitter:card", "summary_large_image");
  upsertMeta("name", "twitter:title", career.title);
  upsertMeta("name", "twitter:description", career.description);

  var jsonLd = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: career.title,
    description: career.description,
    image: [imageUrl],
    datePublished: career.datePublished,
    dateModified: career.dateModified || career.datePublished,
    author: { "@type": "Organization", name: "Max Thai Life" },
    publisher: {
      "@type": "Organization",
      name: "Max Thai Life",
      logo: {
        "@type": "ImageObject",
        url: new URL(base + "images/logo/LOGO-THAILIFE.png", pageUrl).href
      }
    },
    mainEntityOfPage: { "@type": "WebPage", "@id": pageUrl },
    articleSection: career.category
  };

  var ldScript = document.createElement("script");
  ldScript.type = "application/ld+json";
  ldScript.textContent = JSON.stringify(jsonLd);
  document.head.appendChild(ldScript);

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

  function getRecommended(currentId, limit) {
    var pool = Object.keys(window.CAREERS_DETAIL)
      .map(function (key) {
        return window.CAREERS_DETAIL[key];
      })
      .filter(function (item) {
        return item.slug !== currentId;
      });

    pool.sort(function (a, b) {
      var aSame = a.category === career.category ? 1 : 0;
      var bSame = b.category === career.category ? 1 : 0;
      if (bSame !== aSame) return bSame - aSame;
      return (b.views || 0) - (a.views || 0);
    });

    return pool.slice(0, limit);
  }

  function renderSidebar(recommended) {
    if (!recommended.length) return "";

    var items = recommended
      .map(function (item) {
        var shortTitle =
          item.title.length > 72 ? item.title.slice(0, 72) + "…" : item.title;
        return (
          '<li class="article-sidebar-item">' +
          '<a href="' +
          base +
          "careers/" +
          item.slug +
          '.html" class="article-sidebar-link">' +
          '<span class="article-sidebar-thumb">' +
          '<img src="' +
          resolveImageUrl(item.image) +
          '" alt="" width="72" height="54" loading="lazy" decoding="async">' +
          "</span>" +
          '<span class="article-sidebar-text">' +
          '<span class="article-sidebar-meta">' +
          item.category +
          "</span>" +
          '<span class="article-sidebar-title">' +
          shortTitle +
          "</span>" +
          "</span>" +
          "</a></li>"
        );
      })
      .join("");

    return (
      '<aside class="article-sidebar" aria-label="บทความแนะนำอาชีพ">' +
      "<h2>บทความแนะนำ</h2>" +
      '<ul class="article-sidebar-list">' +
      items +
      "</ul>" +
      '<a href="' +
      base +
      'career.html" class="article-sidebar-more">ดูทั้งหมด →</a>' +
      "</aside>"
    );
  }

  function renderSections(sections) {
    return sections
      .map(function (section) {
        var html = '<section class="article-prose-section">';
        if (section.heading) html += "<h2>" + section.heading + "</h2>";
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
        html += "</section>";
        return html;
      })
      .join("");
  }

  var heroInner = document.getElementById("career-hero-inner");
  if (heroInner) {
    heroInner.innerHTML =
      '<p class="breadcrumb"><a href="' +
      base +
      'index.html">หน้าหลัก</a> / <a href="' +
      base +
      'career.html">แนะนำอาชีพ</a> / ' +
      career.category +
      "</p><h1>" +
      career.title +
      "</h1><p>" +
      career.description +
      "</p>";
  }

  var root = document.getElementById("career-root");
  if (root) {
    var creditHtml = career.imageCredit
      ? '<p class="article-image-credit">ภาพประกอบ: ' +
        career.imageCredit +
        " / istockphoto.com</p>"
      : "";

    var recommended = getRecommended(id, 5);

    root.innerHTML =
      '<div class="article-detail-layout">' +
      '<article class="article-detail article-detail-main" itemscope itemtype="https://schema.org/Article">' +
      '<meta itemprop="headline" content="' +
      career.title.replace(/"/g, "&quot;") +
      '">' +
      '<meta itemprop="datePublished" content="' +
      career.datePublished +
      '">' +
      '<meta itemprop="dateModified" content="' +
      (career.dateModified || career.datePublished) +
      '">' +
      '<header class="article-detail-header">' +
      '<div class="article-detail-cover">' +
      '<img src="' +
      resolveImageUrl(career.image) +
      '" alt="' +
      career.title.replace(/"/g, "&quot;") +
      '" width="1200" height="675" loading="eager" decoding="async" itemprop="image">' +
      creditHtml +
      "</div>" +
      '<div class="article-detail-meta">' +
      '<span class="article-detail-category">' +
      career.category +
      "</span>" +
      '<time class="article-detail-date" datetime="' +
      career.datePublished +
      '" itemprop="datePublished">' +
      formatDate(career.datePublished) +
      "</time>" +
      (career.views
        ? '<span class="article-detail-stats">' +
          career.views.toLocaleString("th-TH") +
          " views" +
          (career.shares ? " · " + career.shares + " shares" : "") +
          "</span>"
        : "") +
      "</div>" +
      "</header>" +
      '<div class="article-prose" itemprop="articleBody">' +
      renderSections(career.sections) +
      "</div>" +
      '<aside class="article-related-plan">' +
      "<h2>สนใจสมัครเป็นตัวแทน?</h2>" +
      "<p>ติดต่อทีม Max Thai Life เพื่อสอบถามรายละเอียด เอกสาร และตารางอบรมล่าสุด</p>" +
      '<a href="' +
      base +
      'contact.html?topic=agent" class="btn btn-primary">สนใจสมัครตัวแทน</a>' +
      "</aside>" +
      '<footer class="article-detail-footer">' +
      '<a href="' +
      base +
      'career.html" class="article-back-link">← กลับหน้าแนะนำอาชีพ</a>' +
      "</footer>" +
      "</article>" +
      renderSidebar(recommended) +
      "</div>";
  }

  var cta = document.getElementById("career-cta");
  if (cta) {
    cta.innerHTML =
      "<h2>พร้อมเริ่มอาชีพตัวแทน?</h2>" +
      "<p>ปรึกษาฟรี ไม่มีค่าใช้จ่ายในการสอบถามเบื้องต้น — ศูนย์นครปฐม</p>" +
      '<div class="cta-actions">' +
      '<a href="' +
      base +
      'contact.html?topic=agent" class="btn btn-primary">สนใจเป็นตัวแทน</a>' +
      '<a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>' +
      "</div>";
  }
})();
