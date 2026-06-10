(function () {
  var id = document.body.getAttribute("data-article-id");
  var article = window.ARTICLES_DETAIL && window.ARTICLES_DETAIL[id];
  if (!article) return;

  var base = document.body.getAttribute("data-base") || "";
  var pageUrl = window.location.href.split("#")[0].split("?")[0];
  var imageUrl = new URL(base + article.image, pageUrl).href;

  document.title = article.title + " | บทความ | Max Thai Life";

  var metaDesc = document.querySelector('meta[name="description"]');
  if (metaDesc) metaDesc.setAttribute("content", article.description);

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
  upsertMeta("property", "og:title", article.title);
  upsertMeta("property", "og:description", article.description);
  upsertMeta("property", "og:image", imageUrl);
  upsertMeta("property", "og:url", pageUrl);
  upsertMeta("name", "twitter:card", "summary_large_image");
  upsertMeta("name", "twitter:title", article.title);
  upsertMeta("name", "twitter:description", article.description);

  var jsonLd = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: article.title,
    description: article.description,
    image: [imageUrl],
    datePublished: article.datePublished,
    dateModified: article.dateModified || article.datePublished,
    author: {
      "@type": "Organization",
      name: "Max Thai Life"
    },
    publisher: {
      "@type": "Organization",
      name: "Max Thai Life",
      logo: {
        "@type": "ImageObject",
        url: new URL(base + "images/logo/LOGO-THAILIFE.png", pageUrl).href
      }
    },
    mainEntityOfPage: {
      "@type": "WebPage",
      "@id": pageUrl
    },
    articleSection: article.category
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

  function getRecommendedArticles(currentId, limit) {
    var pool = Object.keys(window.ARTICLES_DETAIL)
      .map(function (key) {
        return window.ARTICLES_DETAIL[key];
      })
      .filter(function (item) {
        return item.slug !== currentId;
      });

    pool.sort(function (a, b) {
      var aSame = a.category === article.category ? 1 : 0;
      var bSame = b.category === article.category ? 1 : 0;
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
          "articles/" +
          item.slug +
          '.html" class="article-sidebar-link">' +
          '<span class="article-sidebar-thumb">' +
          '<img src="' +
          base +
          item.image +
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
      '<aside class="article-sidebar" aria-label="บทความแนะนำ">' +
      "<h2>บทความแนะนำ</h2>" +
      '<ul class="article-sidebar-list">' +
      items +
      "</ul>" +
      '<a href="' +
      base +
      'products.html" class="article-sidebar-more">ดูบทความทั้งหมด →</a>' +
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
          html += "<ul>" + section.list.map(function (li) {
            return "<li>" + li + "</li>";
          }).join("") + "</ul>";
        }
        html += "</section>";
        return html;
      })
      .join("");
  }

  var heroInner = document.getElementById("article-hero-inner");
  if (heroInner) {
    heroInner.innerHTML =
      '<p class="breadcrumb"><a href="' +
      base +
      'index.html">หน้าหลัก</a> / <a href="' +
      base +
      'products.html">บทความ</a> / ' +
      article.category +
      "</p><h1>" +
      article.title +
      "</h1><p>" +
      article.description +
      "</p>";
  }

  var root = document.getElementById("article-root");
  if (root) {
    var relatedHtml = "";
    if (article.relatedPlan) {
      relatedHtml =
        '<aside class="article-related-plan">' +
        "<h2>ดูรายละเอียดแผนที่เกี่ยวข้อง</h2>" +
        "<p>บทความนี้อธิบายแนวคิดเบื้องต้น หากต้องการข้อมูลแผนประกันแบบเต็ม</p>" +
        '<a href="' +
        base +
        article.relatedPlan +
        '" class="btn btn-primary">' +
        article.relatedPlanLabel +
        "</a></aside>";
    }

    var recommended = getRecommendedArticles(id, 5);

    root.innerHTML =
      '<div class="article-detail-layout">' +
      '<article class="article-detail article-detail-main" itemscope itemtype="https://schema.org/Article">' +
      '<meta itemprop="headline" content="' +
      article.title.replace(/"/g, "&quot;") +
      '">' +
      '<meta itemprop="datePublished" content="' +
      article.datePublished +
      '">' +
      '<meta itemprop="dateModified" content="' +
      (article.dateModified || article.datePublished) +
      '">' +
      '<header class="article-detail-header">' +
      '<div class="article-detail-cover">' +
      '<img src="' +
      base +
      article.image +
      '" alt="' +
      article.title.replace(/"/g, "&quot;") +
      '" width="1200" height="675" loading="eager" decoding="async" itemprop="image">' +
      "</div>" +
      '<div class="article-detail-meta">' +
      '<span class="article-detail-category">' +
      article.category +
      "</span>" +
      '<time class="article-detail-date" datetime="' +
      article.datePublished +
      '" itemprop="datePublished">' +
      formatDate(article.datePublished) +
      "</time>" +
      (article.views
        ? '<span class="article-detail-stats">' +
          article.views.toLocaleString("th-TH") +
          " views" +
          (article.shares ? " · " + article.shares + " shares" : "") +
          "</span>"
        : "") +
      "</div>" +
      "</header>" +
      '<div class="article-prose" itemprop="articleBody">' +
      renderSections(article.sections) +
      "</div>" +
      relatedHtml +
      '<footer class="article-detail-footer">' +
      '<a href="' +
      base +
      'products.html" class="article-back-link">← กลับหน้าบทความทั้งหมด</a>' +
      "</footer>" +
      "</article>" +
      renderSidebar(recommended) +
      "</div>";
  }

  var cta = document.getElementById("article-cta");
  if (cta) {
    cta.innerHTML =
      "<h2>สนใจปรึกษาแผนที่เหมาะกับคุณ?</h2>" +
      "<p>ขอใบเสนอเบี้ยฟรี ไม่มีค่าใช้จ่ายในการสอบถามเบื้องต้น</p>" +
      '<div class="cta-actions">' +
      '<a href="' +
      base +
      'contact.html" class="btn btn-primary">ขอใบเสนอเบี้ย</a>' +
      '<a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>' +
      "</div>";
  }
})();
