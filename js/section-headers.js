(function () {
  function iconSvg(name) {
    if (window.LucideIcons) {
      return LucideIcons.defer(name, { size: 24, strokeWidth: 1.75 });
    }
    var resolved = name;
    return '<i data-lucide="' + resolved + '" aria-hidden="true"></i>';
  }

  function initSectionHeaders(root) {
    (root || document).querySelectorAll(".section-header").forEach(function (header) {
      if (header.querySelector(".section-header-icon")) return;

      var iconName = header.getAttribute("data-icon");
      var text = document.createElement("div");
      text.className = "section-header-text";
      while (header.firstChild) {
        text.appendChild(header.firstChild);
      }

      if (iconName) {
        var icon = document.createElement("div");
        icon.className = "section-header-icon";
        icon.setAttribute("aria-hidden", "true");
        icon.innerHTML = iconSvg(iconName);
        header.appendChild(icon);
      }
      header.appendChild(text);
    });

    if (window.LucideIcons) {
      LucideIcons.refresh(root || document);
    }
  }

  window.initSectionHeaders = initSectionHeaders;
  initSectionHeaders();

  document.addEventListener("landing:rendered", function (e) {
    var root = document.getElementById("landing-root");
    if (root) initSectionHeaders(root);
  });
})();
