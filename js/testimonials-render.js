(function () {
  var data = window.HOME_DATA && window.HOME_DATA.testimonialsSection;
  if (!data) return;

  var slider = document.querySelector("[data-testimonial-slider]");
  if (!slider) return;

  var track = slider.querySelector(".testimonial-track");
  var section = slider.closest("section");
  var header = section && section.querySelector(".section-header");

  function esc(s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  }

  if (header) {
    var h2 = header.querySelector("h2");
    var p = header.querySelector("p");
    if (h2 && data.title) h2.textContent = data.title;
    if (p && data.subtitle) p.textContent = data.subtitle;
  }

  var slides = data.slides;
  // รองรับทั้งแบบจัดกลุ่มแล้ว (array ของ array) และแบบ flat
  if (slides && slides.length && !Array.isArray(slides[0])) {
    var grouped = [];
    for (var i = 0; i < slides.length; i += 3) {
      grouped.push(slides.slice(i, i + 3));
    }
    slides = grouped;
  }

  if (!track || !slides || !slides.length) return;

  function cardHtml(c) {
    if (!c) return "";
    return (
      '<article class="testimonial-card">' +
      '<div class="testimonial-stars" aria-hidden="true">★★★★★</div>' +
      '<p class="testimonial-quote">' +
      esc(c.quote) +
      "</p>" +
      (c.author
        ? '<p class="testimonial-author">' + esc(c.author) + "</p>"
        : "") +
      "</article>"
    );
  }

  track.innerHTML = slides
    .map(function (slide) {
      return (
        '<div class="testimonial-slide">' +
        (slide || []).map(cardHtml).join("") +
        "</div>"
      );
    })
    .join("");
})();
