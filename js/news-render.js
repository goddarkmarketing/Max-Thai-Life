(function () {
  var id = document.body.getAttribute("data-news-id");
  var item = window.NEWS_DETAIL && window.NEWS_DETAIL[id];
  if (!item) return;

  var base = document.body.getAttribute("data-base") || "";
  var pageUrl = window.location.href.split("#")[0].split("?")[0];

  function resolveImageUrl(src) {
    if (/^https?:\/\//i.test(src)) return src;
    return new URL(base + src, pageUrl).href;
  }

  var imageUrl = resolveImageUrl(item.image);

  document.title = item.title + " | ข่าว/กิจกรรม | Max Thai Life";

  var metaDesc = document.querySelector('meta[name="description"]');
  if (metaDesc) metaDesc.setAttribute("content", item.description);

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
  upsertMeta("property", "og:title", item.title);
  upsertMeta("property", "og:description", item.description);
  upsertMeta("property", "og:image", imageUrl);
  upsertMeta("property", "og:url", pageUrl);
  upsertMeta("name", "twitter:card", "summary_large_image");
  upsertMeta("name", "twitter:title", item.title);
  upsertMeta("name", "twitter:description", item.description);

  var jsonLd = {
    "@context": "https://schema.org",
    "@type": "NewsArticle",
    headline: item.title,
    description: item.description,
    image: [imageUrl],
    datePublished: item.datePublished,
    dateModified: item.dateModified || item.datePublished,
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
    articleSection: item.category
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
    var pool = Object.keys(window.NEWS_DETAIL)
      .map(function (key) {
        return window.NEWS_DETAIL[key];
      })
      .filter(function (entry) {
        return entry.slug !== currentId;
      });

    pool.sort(function (a, b) {
      var aSame = a.category === item.category ? 1 : 0;
      var bSame = b.category === item.category ? 1 : 0;
      if (bSame !== aSame) return bSame - aSame;
      return (b.views || 0) - (a.views || 0);
    });

    return pool.slice(0, limit);
  }

  function renderSidebar(recommended) {
    if (!recommended.length) return "";

    var items = recommended
      .map(function (entry) {
        var shortTitle =
          entry.title.length > 72 ? entry.title.slice(0, 72) + "…" : entry.title;
        return (
          '<li class="article-sidebar-item">' +
          '<a href="' +
          base +
          "news/" +
          entry.slug +
          '.html" class="article-sidebar-link">' +
          '<span class="article-sidebar-thumb">' +
          '<img src="' +
          resolveImageUrl(entry.image) +
          '" alt="" width="72" height="54" loading="lazy" decoding="async">' +
          "</span>" +
          '<span class="article-sidebar-text">' +
          '<span class="article-sidebar-meta">' +
          entry.category +
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
      '<aside class="article-sidebar" aria-label="ข่าวแนะนำ">' +
      "<h2>ข่าวแนะนำ</h2>" +
      '<ul class="article-sidebar-list">' +
      items +
      "</ul>" +
      '<a href="' +
      base +
      'news.html" class="article-sidebar-more">ดูข่าวทั้งหมด →</a>' +
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

  var heroInner = document.getElementById("news-hero-inner");
  if (heroInner) {
    heroInner.innerHTML =
      '<p class="breadcrumb"><a href="' +
      base +
      'index.html">หน้าหลัก</a> / <a href="' +
      base +
      'news.html">ข่าว/กิจกรรม</a> / ' +
      item.category +
      "</p><h1>" +
      item.title +
      "</h1><p>" +
      item.description +
      "</p>";
  }

  var root = document.getElementById("news-root");
  if (root) {
    var relatedHtml = "";
    if (item.relatedPlan) {
      relatedHtml =
        '<aside class="article-related-plan">' +
        "<h2>ข้อมูลที่เกี่ยวข้อง</h2>" +
        "<p>อ่านรายละเอียดแผนประกันที่เกี่ยวข้องกับข่าวนี้</p>" +
        '<a href="' +
        base +
        item.relatedPlan +
        '" class="btn btn-primary">' +
        item.relatedPlanLabel +
        "</a></aside>";
    }

    var recommended = getRecommended(id, 5);

    root.innerHTML =
      '<div class="article-detail-layout">' +
      '<article class="article-detail article-detail-main" itemscope itemtype="https://schema.org/NewsArticle">' +
      '<meta itemprop="headline" content="' +
      item.title.replace(/"/g, "&quot;") +
      '">' +
      '<meta itemprop="datePublished" content="' +
      item.datePublished +
      '">' +
      '<meta itemprop="dateModified" content="' +
      (item.dateModified || item.datePublished) +
      '">' +
      '<header class="article-detail-header">' +
      '<div class="article-detail-cover">' +
      '<img src="' +
      resolveImageUrl(item.image) +
      '" alt="' +
      item.title.replace(/"/g, "&quot;") +
      '" width="1200" height="675" loading="eager" decoding="async" itemprop="image">' +
      "</div>" +
      '<div class="article-detail-meta">' +
      '<span class="article-detail-category">' +
      item.category +
      "</span>" +
      '<time class="article-detail-date" datetime="' +
      item.datePublished +
      '" itemprop="datePublished">' +
      formatDate(item.datePublished) +
      "</time>" +
      (item.views
        ? '<span class="article-detail-stats">' +
          item.views.toLocaleString("th-TH") +
          " views" +
          (item.shares ? " · " + item.shares + " shares" : "") +
          "</span>"
        : "") +
      "</div>" +
      "</header>" +
      '<div class="article-prose" itemprop="articleBody">' +
      renderSections(item.sections) +
      "</div>" +
      relatedHtml +
      '<footer class="article-detail-footer">' +
      '<a href="' +
      base +
      'news.html" class="article-back-link">← กลับหน้าข่าวทั้งหมด</a>' +
      "</footer>" +
      "</article>" +
      renderSidebar(recommended) +
      "</div>";
  }

  var cta = document.getElementById("news-cta");
  if (cta) {
    cta.innerHTML =
      "<h2>สอบถามข่าวหรือกิจกรรม</h2>" +
      "<p>ติดต่อทีมงานเพื่อรับข้อมูลกิจกรรมในพื้นที่นครปฐม</p>" +
      '<div class="cta-actions">' +
      '<a href="' +
      base +
      'contact.html" class="btn btn-primary">ติดต่อสอบถาม</a>' +
      '<a href="tel:0852925320" class="btn btn-outline">โทร 085-292-5320</a>' +
      "</div>";
  }
})();
