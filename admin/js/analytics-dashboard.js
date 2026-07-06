(function () {
  var tabs = document.querySelectorAll("[data-analytics-tab]");
  var panels = document.querySelectorAll("[data-analytics-panel]");
  if (!tabs.length || !panels.length) return;

  function activateTab(key) {
    tabs.forEach(function (tab) {
      var active = tab.getAttribute("data-analytics-tab") === key;
      tab.classList.toggle("is-active", active);
      tab.setAttribute("aria-selected", active ? "true" : "false");
    });
    panels.forEach(function (panel) {
      var active = panel.getAttribute("data-analytics-panel") === key;
      panel.classList.toggle("is-active", active);
      if (active) panel.removeAttribute("hidden");
      else panel.setAttribute("hidden", "hidden");
    });
  }

  tabs.forEach(function (tab) {
    tab.addEventListener("click", function () {
      activateTab(tab.getAttribute("data-analytics-tab") || "");
    });
  });
})();
