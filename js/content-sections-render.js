(function () {
  function isBlockFormat(sections) {
    if (!sections || !sections.length) return false;
    return !!sections[0].type;
  }

  function renderLegacy(sections) {
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

  function renderBlocks(sections, ctx) {
    var R = window.PageBlockRender;
    if (!R) return renderLegacy(sections);
    ctx = ctx || {};
    return sections
      .filter(function (sec) {
        return sec && sec.isVisible !== false && sec.visible !== false;
      })
      .map(function (sec) {
        return (
          '<section class="article-prose-section article-prose-section--block">' +
          R.renderBlock(sec, ctx) +
          "</section>"
        );
      })
      .join("");
  }

  window.ContentSectionsRender = {
    isBlockFormat: isBlockFormat,
    render: function (sections, ctx) {
      if (!sections || !sections.length) return "";
      if (isBlockFormat(sections)) {
        return renderBlocks(sections, ctx);
      }
      return renderLegacy(sections);
    },
  };
})();
