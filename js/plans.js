(function () {
  const buttons = document.querySelectorAll(".plan-cat-btn");
  const cards = document.querySelectorAll(".plan-card[data-category]");

  if (!buttons.length || !cards.length) return;

  buttons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const filter = btn.getAttribute("data-filter");
      buttons.forEach((b) => b.classList.toggle("active", b === btn));
      cards.forEach((card) => {
        const cats = (card.getAttribute("data-category") || "").split(/\s+/);
        const show = filter === "all" || cats.includes(filter);
        card.style.display = show ? "" : "none";
      });
    });
  });
})();
