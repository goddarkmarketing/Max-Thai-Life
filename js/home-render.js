(function () {
  var site = window.SITE_DATA;
  var home = window.HOME_DATA;
  if (!site && !home) return;

  function h(text) {
    var d = document.createElement('div');
    d.textContent = text || '';
    return d.innerHTML;
  }

  function u(path) {
    return path || '';
  }

  if (site && site.meta && site.meta.description) {
    var meta = document.querySelector('meta[name="description"]');
    if (meta) meta.setAttribute('content', site.meta.description);
  }

  if (site && site.brand) {
    document.querySelectorAll('.brand-name').forEach(function (el) {
      el.textContent = site.brand.name || el.textContent;
    });
    document.querySelectorAll('.brand-sub').forEach(function (el) {
      el.textContent = site.brand.sub || el.textContent;
    });
    document.querySelectorAll('.brand-logo').forEach(function (img) {
      if (site.brand.logo) img.src = u(site.brand.logo);
    });
  }

  if (!home) return;

  var hero = home.hero || {};
  var heroImg = document.querySelector('.home-hero-img');
  if (heroImg && hero.image) {
    heroImg.src = u(hero.image);
    if (hero.alt) heroImg.alt = hero.alt;
  }

  var heroLead = document.querySelector('.hero-cta-lead');
  if (heroLead && hero.lead) heroLead.textContent = hero.lead;

  var heroAvatar = document.querySelector('.hero-cta-avatar');
  if (heroAvatar && hero.avatar) heroAvatar.src = u(hero.avatar);

  var ctaBar = document.querySelector('.hero-cta-actions');
  if (ctaBar && hero.ctaPrimary) {
    var links = ctaBar.querySelectorAll('a');
    if (links[0] && hero.ctaPrimary) {
      links[0].textContent = hero.ctaPrimary.label;
      links[0].href = hero.ctaPrimary.href;
    }
    if (links[1] && hero.ctaPhone) {
      links[1].textContent = hero.ctaPhone.label;
      links[1].href = hero.ctaPhone.href;
    }
    if (links[2] && hero.ctaContact) {
      links[2].textContent = hero.ctaContact.label;
      links[2].href = hero.ctaContact.href;
    }
  }

  var profile = home.profile;
  if (profile) {
    var head = document.querySelector('.profile-panel-head');
    if (head) {
      var h2 = head.querySelector('h2');
      var p = head.querySelector('p');
      if (h2 && profile.title) h2.textContent = profile.title;
      if (p && profile.subtitle) p.textContent = profile.subtitle;
    }
    var strip = document.querySelector('.profile-strip');
    if (strip && profile.fields && profile.fields.length) {
      strip.innerHTML = profile.fields
        .map(function (f) {
          var val = f.link
            ? '<a href="' + h(f.link) + '">' + h(f.value) + '</a>'
            : h(f.value);
          return (
            '<div class="profile-item"><dt>' +
            h(f.label) +
            '</dt><dd>' +
            val +
            '</dd></div>'
          );
        })
        .join('');
    }
  }

  var plansSec = home.plansSection;
  if (plansSec) {
    var sec = document.querySelector('.goal-chips')?.closest('.section-inner');
    if (sec) {
      var header = sec.querySelector('.section-header');
      if (header) {
        var ht = header.querySelector('h2');
        var hp = header.querySelector('p');
        if (ht && plansSec.title) ht.textContent = plansSec.title;
        if (hp && plansSec.subtitle) hp.textContent = plansSec.subtitle;
      }
    }
  }

  var testSec = home.testimonialsSection;
  if (testSec && testSec.slides && testSec.slides.length) {
    var testHeader = document.querySelector('[data-testimonial-slider]')
      ?.closest('.section-inner')
      ?.querySelector('.section-header');
    if (testHeader) {
      var th = testHeader.querySelector('h2');
      var tp = testHeader.querySelector('p');
      if (th && testSec.title) th.textContent = testSec.title;
      if (tp && testSec.subtitle) tp.textContent = testSec.subtitle;
    }
    var track = document.querySelector('.testimonial-track');
    if (track) {
      track.innerHTML = testSec.slides
        .map(function (slide) {
          var cards = slide
            .map(function (t) {
              return (
                '<article class="testimonial-card">' +
                '<div class="testimonial-stars" aria-hidden="true">★★★★★</div>' +
                '<p class="testimonial-quote">' +
                h(t.quote) +
                '</p>' +
                '<p class="testimonial-author">' +
                h(t.author) +
                '</p></article>'
              );
            })
            .join('');
          return '<div class="testimonial-slide">' + cards + '</div>';
        })
        .join('');
      document.dispatchEvent(new CustomEvent('testimonials:updated'));
    }
  }

  var inquiry = home.inquiry;
  if (inquiry) {
    var inqSec = document.getElementById('inquiry');
    if (inqSec) {
      var ih = inqSec.querySelector('.home-inquiry-header h2');
      var ip = inqSec.querySelector('.home-inquiry-header p');
      if (ih && inquiry.title) ih.textContent = inquiry.title;
      if (ip && inquiry.subtitle) ip.textContent = inquiry.subtitle;
      var points = inqSec.querySelector('.home-inquiry-points');
      if (points && inquiry.points) {
        points.innerHTML = inquiry.points
          .map(function (pt) {
            return '<li><span class="home-inquiry-point-icon" aria-hidden="true">✓</span>' + h(pt) + '</li>';
          })
          .join('');
      }
      var note = inqSec.querySelector('.form-note');
      if (note && inquiry.formNote) note.textContent = inquiry.formNote;
    }
  }

  var cta = home.ctaBanner;
  if (cta) {
    var banner = document.querySelector('.cta-band--banner');
    if (banner) {
      var link = banner.querySelector('.cta-band-link') || banner;
      var img = banner.querySelector('img');
      if (link && cta.href) link.href = cta.href;
      if (img) {
        if (cta.image) img.src = u(cta.image);
        if (cta.alt) img.alt = cta.alt;
      }
    }
  }
})();
