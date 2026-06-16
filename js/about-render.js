(function () {
  var pages = window.PAGES_DATA;
  if (!pages || !pages.about) return;

  var about = pages.about;
  var hero = document.querySelector('.page-hero-inner');
  if (hero) {
    var h1 = hero.querySelector('h1');
    var lead = hero.querySelector('p:not(.breadcrumb)');
    if (h1 && about.title) h1.textContent = about.title;
    if (lead && about.lead) lead.textContent = about.lead;
  }

  var quote = document.querySelector('.section blockquote');
  if (quote && about.quote) quote.textContent = about.quote;

  var bio = document.querySelector('.section > .section-inner > p');
  if (bio && about.bio && bio.style.maxWidth) bio.textContent = about.bio;
})();
