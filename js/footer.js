(function () {
  var script = document.currentScript;
  var base = (script && script.getAttribute("data-base")) || "";
  if (!base && document.body) {
    base = document.body.getAttribute("data-base") || "";
  }
  var mount = document.getElementById("site-footer");
  if (!mount) return;

  function esc(text) {
    var d = document.createElement("div");
    d.textContent = text || "";
    return d.innerHTML;
  }

  function renderFooter() {
    var site = window.SITE_DATA || {};
    var brand = site.brand || {};
    var agent = site.agent || {};
    var social = site.social || {};
    var footer = site.footer || {};

    var brandName = brand.name || "Max Thai Life";
    var brandSub = brand.sub || "สำนักงานตัวแทนแม็ก · ไทยประกันชีวิต";
    var tagline = footer.tagline || "ที่ปรึกษาทางการเงินและประกันชีวิต · สาขานครปฐม";
    var agentName = agent.name || "วรชาติ โตเต็ม";
    var agentTitle = agent.title || "ผู้บริหารศูนย์";
    var agentBranch = agent.branch || "นครปฐม";
    var phoneDisplay = agent.phoneDisplay || "085-292-5320";
    var phoneRaw = agent.phone || "0852925320";
    var license = agent.license || "5701116295";
    var avatar = "images/profile/agent-profile.png";

    function u(path) {
      if (!path) return "#";
      if (/^(https?:|tel:|mailto:)/i.test(path)) return path;
      return base + path;
    }

    function linkVisible(link) {
      return !link || link.visible !== false;
    }

    function linkHtml(link) {
      if (!link || !link.label) return "";
      var href = link.external ? link.href : u(link.href);
      var attrs = link.external ? ' target="_blank" rel="noopener"' : "";
      return '<li><a href="' + esc(href) + '"' + attrs + ">" + esc(link.label) + "</a></li>";
    }

    function linksListHtml(links) {
      return (links || []).filter(linkVisible).map(linkHtml).join("");
    }

    function topCtaHtml() {
      var items = footer.topCta || [
        { label: "ติดต่อสอบถาม", href: "contact.html", variant: "white", visible: true },
        { label: "โทร " + phoneDisplay, href: "tel:" + phoneRaw, variant: "outline", visible: true }
      ];
      return items
        .filter(linkVisible)
        .map(function (btn) {
          var cls = btn.variant === "outline" ? "btn btn-outline" : "btn btn-white";
          return '<a href="' + esc(u(btn.href)) + '" class="' + cls + '">' + esc(btn.label) + "</a>";
        })
        .join("");
    }

    function socialIcon(name) {
      if (window.LucideIcons) {
        return LucideIcons.defer(name, { size: 20, strokeWidth: 2 });
      }
      return '<i data-lucide="' + name + '" aria-hidden="true"></i>';
    }

    function socialColor(link) {
      if (link.color) return link.color;
      var presets = {
        facebook: "#1877f2",
        line: "#06c755",
        email: "#015fd9",
        phone: "#015fd9",
        default: "#015fd9",
      };
      return presets[link.style] || "#015fd9";
    }

    function socialLinksHtml() {
      var links = social.links;
      if (!links || !links.length) {
        links = [];
        if (social.facebook) {
          links.push({ label: "Facebook", href: social.facebook, icon: "facebook", style: "facebook", visible: true });
        }
        if (social.line) {
          links.push({ label: "Line", href: social.line, icon: "message-circle", style: "line", visible: true });
        }
        if (social.email) {
          var em = social.email;
          links.push({
            label: "Email",
            href: /^mailto:/i.test(em) ? em : "mailto:" + em,
            icon: "mail",
            style: "email",
            visible: true,
          });
        }
        if (!links.length) {
          links = [
            { label: "Facebook", href: "#", icon: "facebook", style: "facebook", visible: true },
            { label: "Line", href: "#", icon: "message-circle", style: "line", visible: true },
            { label: "Email", href: "mailto:contact@example.com", icon: "mail", style: "email", visible: true },
          ];
        }
      }
      return links
        .filter(linkVisible)
        .map(function (link) {
          var href = link.href || "#";
          if (link.style === "email" && !/^mailto:/i.test(href)) {
            href = "mailto:" + href;
          }
          href = /^(https?:|tel:|mailto:|#)/i.test(href) ? href : u(href);
          var bg = socialColor(link);
          var external = /^https?:/i.test(href);
          var attrs = external ? ' target="_blank" rel="noopener"' : "";
          return (
            '<a href="' +
            esc(href) +
            '" class="footer-social-link" style="background:' +
            esc(bg) +
            '" aria-label="' +
            esc(link.label || "") +
            '"' +
            attrs +
            ">" +
            socialIcon(link.icon || "link") +
            "</a>"
          );
        })
        .join("");
    }

    function extraContactsHtml() {
      var items = agent.extraContacts || [];
      return items
        .filter(linkVisible)
        .map(function (item) {
          var text = esc(item.text || "");
          if (item.href) {
            var href = /^(https?:|tel:|mailto:)/i.test(item.href) ? item.href : u(item.href);
            return '<li><span class="footer-label">' + esc(item.label || "") + "</span> <a href=\"" + esc(href) + '">' + text + "</a></li>";
          }
          return '<li><span class="footer-label">' + esc(item.label || "") + "</span> " + text + "</li>";
        })
        .join("");
    }

    function agentColumnHtml(col) {
      return (
        '<div class="footer-col footer-col-contact">' +
        "<h4>" + esc(col.title || "ติดต่อตัวแทน") + "</h4>" +
        '<ul class="footer-contact-list">' +
        '<li><span class="footer-label">ชื่อ</span> ' + esc(agentName) + "</li>" +
        '<li><span class="footer-label">ตำแหน่ง</span> ' + esc(agentTitle) + "</li>" +
        '<li><span class="footer-label">สาขา</span> ' + esc(agentBranch) + "</li>" +
        '<li><span class="footer-label">โทร</span> <a href="tel:' + esc(phoneRaw) + '">' + esc(phoneDisplay) + "</a></li>" +
        '<li><span class="footer-label">ใบอนุญาต</span> ' + esc(license) + "</li>" +
        extraContactsHtml() +
        "</ul>" +
        '<div class="footer-social">' +
        socialLinksHtml() +
        "</div>" +
        "</div>"
      );
    }

    function columnHtml(col) {
      if (col.type === "agent") {
        return agentColumnHtml(col);
      }
      var wide = col.wide ? " footer-col-wide" : "";
      var html =
        '<div class="footer-col' + wide + '">' +
        "<h4>" + esc(col.title || "") + "</h4>" +
        "<ul>" +
        linksListHtml(col.links);
      if (col.moreLink && linkVisible(col.moreLink)) {
        html += linkHtml(col.moreLink);
      }
      html += "</ul></div>";
      return html;
    }

    function columnsHtml() {
      var columns = footer.columns;
      if (columns && columns.length) {
        return columns.map(columnHtml).join("");
      }

      var planLinks = footer.planLinks || [];
      var planLinksHtml = planLinks
        .filter(linkVisible)
        .map(function (link) {
          return '<li><a href="' + u(link.href) + '">' + esc(link.label) + "</a></li>";
        })
        .join("");
      if (!planLinksHtml) {
        planLinksHtml =
          '<li><a href="' + u("plans/tax-saving.html") + '">ลดหย่อนภาษี แบบสั้น</a></li>' +
          '<li><a href="' + u("plans/life-wealth-fit-99-99.html") + '">ไลฟ์เวิร์ส เวลท์ ฟิต 99/99</a></li>' +
          '<li><a href="' + u("plans/health-working.html") + '">สุขภาพ วัยทำงาน</a></li>' +
          '<li><a href="' + u("plans/infinite.html") + '">INFINITE</a></li>' +
          '<li><a href="' + u("plans/legacy-fit-retire.html") + '">เลกาซี ฟิต รีไทร์ 99/10</a></li>' +
          '<li><a href="' + u("plans/universal-life.html") + '">ยูนิเวอร์แซลไลฟ์</a></li>';
      }

      return (
        '<div class="footer-col footer-col-wide"><h4>สำนักงานตัวแทน</h4><ul>' +
        '<li><a href="' + u("index.html") + '">หน้าหลัก</a></li>' +
        '<li><a href="' + u("about.html") + '">เกี่ยวกับเรา</a></li>' +
        '<li><a href="' + u("plans.html") + '">แผนประกัน</a></li>' +
        '<li><a href="' + u("products.html") + '">บทความ / ผลิตภัณฑ์</a></li>' +
        '<li><a href="' + u("career.html") + '">แนะนำอาชีพ</a></li>' +
        '<li><a href="' + u("news.html") + '">ข่าวและกิจกรรม</a></li>' +
        '<li><a href="' + u("claim-reviews.html") + '">รีวิวเคลม</a></li>' +
        '<li><a href="' + u("contact.html") + '">ติดต่อสอบถาม</a></li>' +
        "</ul></div>" +
        '<div class="footer-col"><h4>แผนประกันแนะนำ</h4><ul>' +
        planLinksHtml +
        '<li><a href="' + u("plans.html") + '">ดูแผนทั้งหมด →</a></li>' +
        "</ul></div>" +
        agentColumnHtml({ title: "ติดต่อตัวแทน" })
      );
    }

    var bottom = footer.bottom || {};
    var copyright = (bottom.copyright || "สงวนสิทธิ์ © {year} บริษัท ไทยประกันชีวิต จำกัด (มหาชน)").replace(
      "{year}",
      String(new Date().getFullYear())
    );
    var legalItems = (bottom.links || []).filter(linkVisible);
    var legalLinks;
    if (!legalItems.length) {
      legalLinks =
        '<a href="https://www.thailife.com/th/privacy" target="_blank" rel="noopener">นโยบายส่วนบุคคล</a>' +
        ' <span aria-hidden="true">·</span> ' +
        '<a href="https://www.thailife.com" target="_blank" rel="noopener">thailife.com</a>' +
        ' <span aria-hidden="true">·</span> ' +
        '<a href="https://digitaloffices.thailife.com/worachat.tot" target="_blank" rel="noopener">Digital Office</a>';
    } else {
      legalLinks = legalItems
        .map(function (link) {
          var href = link.external ? link.href : u(link.href);
          var attrs = link.external ? ' target="_blank" rel="noopener"' : "";
          return '<a href="' + esc(href) + '"' + attrs + ">" + esc(link.label) + "</a>";
        })
        .join(' <span aria-hidden="true">·</span> ');
    }

    mount.className = "site-footer";
    mount.setAttribute("role", "contentinfo");
    mount.innerHTML =
      '<div class="footer-top">' +
      '  <div class="footer-top-inner">' +
      '    <div class="footer-top-brand">' +
      '      <img src="' + u(avatar) + '" alt="' + esc(agentName) + '" class="footer-top-avatar" width="95" height="95" loading="lazy" decoding="async">' +
      '      <div class="footer-top-brand-text">' +
      '        <p class="footer-logo-title">' + esc(brandName) + "</p>" +
      '        <p class="footer-logo-sub">' + esc(brandSub) + "</p>" +
      '        <p class="footer-tagline">' + esc(tagline) + "</p>" +
      "      </div>" +
      "    </div>" +
      '    <div class="footer-top-cta">' +
      topCtaHtml() +
      "    </div>" +
      "  </div>" +
      "</div>" +
      '<div class="footer-main">' +
      '  <div class="footer-main-inner">' +
      columnsHtml() +
      "  </div>" +
      "</div>" +
      '<div class="footer-bottom">' +
      '  <div class="footer-bottom-inner">' +
      '    <p class="footer-copy">' + esc(copyright) + "</p>" +
      '    <nav class="footer-legal" aria-label="ลิงก์ทางกฎหมาย">' +
      legalLinks +
      "    </nav>" +
      "  </div>" +
      "</div>";

    if (window.LucideIcons) LucideIcons.refresh(mount);
  }

  if (window.SITE_DATA) {
    renderFooter();
  } else {
    var loader = document.createElement("script");
    loader.src = base + "js/site-data.js";
    loader.onload = renderFooter;
    loader.onerror = renderFooter;
    document.head.appendChild(loader);
  }
})();
