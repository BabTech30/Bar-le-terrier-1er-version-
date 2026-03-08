/* ============================================================
   LE TERRIER — Shared JavaScript
   v2.0 — Audit perf/a11y/UX (fév. 2026)
   Lightweight, vanilla, performant
   ============================================================ */

(function () {
  'use strict';

  /* --- REDUCED MOTION CHECK --- */
  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* --- ANNOUNCEMENT BANNER --- */
  var announce = document.querySelector('.announce');
  if (announce) {
    if (sessionStorage.getItem('lt-announce-closed')) {
      announce.classList.add('hidden');
      document.body.classList.remove('has-announce');
    } else {
      document.body.classList.add('has-announce');
      var closeBtn = announce.querySelector('.announce__close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          announce.classList.add('hidden');
          document.body.classList.remove('has-announce');
          sessionStorage.setItem('lt-announce-closed', '1');
        });
      }
    }
  }

  /* --- BURGER MENU (with aria-expanded) --- */
  var burger = document.querySelector('.burger');
  var nav = document.querySelector('.nav');
  if (burger && nav) {
    burger.setAttribute('aria-expanded', 'false');
    burger.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('open');
      burger.classList.toggle('open');
      burger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      document.body.style.overflow = isOpen ? 'hidden' : '';
    });
    nav.querySelectorAll('.nav__link').forEach(function (link) {
      link.addEventListener('click', function () {
        burger.classList.remove('open');
        nav.classList.remove('open');
        burger.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
      });
    });
  }

  /* --- FLOATING CTA --- */
  var floatCta = document.querySelector('.float-cta');

  /* --- CONSOLIDATED SCROLL HANDLER (perf: 1 listener instead of 3) --- */
  var header = document.querySelector('.header');
  var menuNav = document.querySelector('.menu-nav');
  var menuSections = [];

  if (menuNav) {
    var menuLinks = menuNav.querySelectorAll('.menu-nav__link');
    menuLinks.forEach(function (link) {
      var id = link.getAttribute('href');
      if (id && id.startsWith('#')) {
        var section = document.querySelector(id);
        if (section) menuSections.push({ link: link, section: section });
      }
    });
  }

  function onScroll() {
    var y = window.scrollY;

    // Sticky header
    if (header) {
      header.classList.toggle('scrolled', y > 40);
    }

    // Floating CTA
    if (floatCta) {
      floatCta.classList.toggle('visible', y > 400);
    }

    // Menu nav highlight (carte page only)
    if (menuSections.length) {
      var scrollY = y + 120;
      var current = menuSections[0];
      for (var i = 0; i < menuSections.length; i++) {
        if (menuSections[i].section.offsetTop <= scrollY) {
          current = menuSections[i];
        }
      }
      menuLinks.forEach(function (l) { l.classList.remove('active'); });
      if (current) current.link.classList.add('active');
    }
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* --- SCROLL REVEAL --- */
  var reveals = document.querySelectorAll('.reveal');
  if (reveals.length && 'IntersectionObserver' in window && !prefersReducedMotion) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) {
          e.target.classList.add('visible');
          io.unobserve(e.target);
        }
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('visible'); });
  }

  /* --- FAQ ACCORDION (with aria-expanded) --- */
  document.querySelectorAll('.faq__q').forEach(function (btn) {
    btn.setAttribute('aria-expanded', 'false');
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq__item');
      var wasOpen = item.classList.contains('open');
      // Close all
      document.querySelectorAll('.faq__item.open').forEach(function (el) {
        el.classList.remove('open');
        var b = el.querySelector('.faq__q');
        if (b) b.setAttribute('aria-expanded', 'false');
      });
      // Toggle current
      if (!wasOpen) {
        item.classList.add('open');
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  /* --- LIGHTBOX (gallery) --- */
  var lightbox = document.querySelector('.lightbox');
  if (lightbox) {
    var lbImg = lightbox.querySelector('.lightbox__img');
    var lbCap = lightbox.querySelector('.lightbox__cap');
    var lbClose = lightbox.querySelector('.lightbox__close');

    document.querySelectorAll('.gallery-item[data-src]').forEach(function (item) {
      item.addEventListener('click', function () {
        var src = item.getAttribute('data-src');
        var cap = item.getAttribute('data-caption') || '';
        if (lbImg) lbImg.src = src;
        if (lbImg) lbImg.alt = cap || 'Photo Le Terrier';
        if (lbCap) lbCap.textContent = cap;
        lightbox.classList.add('open');
        lightbox.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (lbClose) lbClose.focus();
      });
    });

    function closeLightbox() {
      lightbox.classList.remove('open');
      lightbox.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
      if (lbImg) lbImg.src = '';
    }
    if (lbClose) lbClose.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', function (e) {
      if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && lightbox.classList.contains('open')) closeLightbox();
    });
  }

  /* --- CONTACT FORM (demo) --- */
  var form = document.querySelector('.form[data-form]');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      form.style.display = 'none';
      var success = document.querySelector('.form__success');
      if (success) success.classList.add('visible');
    });
  }

  /* --- SMOOTH ANCHOR SCROLL (skip href="#" placeholders) --- */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var href = a.getAttribute('href');
      // Skip bare "#" links (placeholder URLs)
      if (href === '#') return;
      var target = document.querySelector(href);
      if (target) {
        e.preventDefault();
        var offset = document.querySelector('.menu-nav') ? 120 : 70;
        window.scrollTo({
          top: target.offsetTop - offset,
          behavior: prefersReducedMotion ? 'auto' : 'smooth'
        });
      }
    });
  });

  /* --- COOKIE CONSENT --- */
  var cookieBanner = document.getElementById('cookie-banner');
  if (cookieBanner) {
    var consent = localStorage.getItem('lt-cookie-consent');
    if (!consent) {
      // Show banner after a short delay
      setTimeout(function () {
        cookieBanner.classList.add('visible');
      }, 1500);
    } else {
      cookieBanner.classList.add('hidden');
    }

    var acceptBtn = document.getElementById('cookie-accept');
    var refuseBtn = document.getElementById('cookie-refuse');

    function closeCookieBanner(choice) {
      localStorage.setItem('lt-cookie-consent', choice);
      cookieBanner.classList.remove('visible');
      setTimeout(function () {
        cookieBanner.classList.add('hidden');
      }, 400);
      // If accepted, load analytics
      if (choice === 'accepted') {
        loadGA4();
      }
    }

    // GA4 loader — REMPLACER GA_MEASUREMENT_ID par votre ID Google Analytics (ex: G-XXXXXXXXXX)
    function loadGA4() {
      var GA_ID = 'GA_MEASUREMENT_ID';
      if (GA_ID === 'GA_MEASUREMENT_ID') return; // Pas encore configuré
      if (document.getElementById('ga4-script')) return; // Déjà chargé
      var s = document.createElement('script');
      s.id = 'ga4-script';
      s.async = true;
      s.src = 'https://www.googletagmanager.com/gtag/js?id=' + GA_ID;
      document.head.appendChild(s);
      window.dataLayer = window.dataLayer || [];
      function gtag(){window.dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', GA_ID, { anonymize_ip: true });
    }

    // Auto-load GA4 if consent already given
    if (consent === 'accepted') { loadGA4(); }

    if (acceptBtn) acceptBtn.addEventListener('click', function () { closeCookieBanner('accepted'); });
    if (refuseBtn) refuseBtn.addEventListener('click', function () { closeCookieBanner('refused'); });
  }

  /* --- FORM SECURITY (basic XSS/injection prevention) --- */
  document.querySelectorAll('.form__input, .form__textarea, .newsletter__input').forEach(function (input) {
    input.addEventListener('input', function () {
      // Strip potential script injection
      var val = input.value;
      if (val.indexOf('<') !== -1 || val.indexOf('>') !== -1) {
        input.value = val.replace(/[<>]/g, '');
      }
    });
  });

  /* --- ATMOSPHERE CANVAS (subtle golden particles) --- */
  var canvas = document.getElementById('atmosphere');
  if (canvas && !prefersReducedMotion) {
    var ctx = canvas.getContext('2d');
    var w, h;
    var particles = [];
    var COUNT = 25;
    var resizeTimer;

    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
    }
    resize();
    // Debounced resize
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(resize, 150);
    });

    for (var i = 0; i < COUNT; i++) {
      particles.push({
        x: Math.random() * (w || 1000),
        y: Math.random() * (h || 800),
        r: Math.random() * 1.2 + 0.3,
        dx: (Math.random() - 0.5) * 0.15,
        dy: (Math.random() - 0.5) * 0.1,
        a: Math.random() * 0.3 + 0.05,
        phase: Math.random() * Math.PI * 2
      });
    }

    var raf;
    function draw() {
      ctx.clearRect(0, 0, w, h);
      var t = Date.now() * 0.001;
      for (var i = 0; i < particles.length; i++) {
        var p = particles[i];
        p.x += p.dx;
        p.y += p.dy;
        if (p.x < -5) p.x = w + 5;
        if (p.x > w + 5) p.x = -5;
        if (p.y < -5) p.y = h + 5;
        if (p.y > h + 5) p.y = -5;
        var alpha = p.a * (0.6 + 0.4 * Math.sin(t + p.phase));
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(200,164,92,' + alpha + ')';
        ctx.fill();
      }
      raf = requestAnimationFrame(draw);
    }
    draw();

    // Pause when tab hidden
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        cancelAnimationFrame(raf);
      } else {
        draw();
      }
    });
  }


  /* --- SCROLL TO TOP BUTTON --- */
  var scrollTopBtn = document.querySelector('.scroll-top');
  if (scrollTopBtn) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 500) {
        scrollTopBtn.classList.add('visible');
      } else {
        scrollTopBtn.classList.remove('visible');
      }
    }, { passive: true });
    scrollTopBtn.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ============================================================
     PREMIUM LAYER — Haut de gamme interactions
     ============================================================ */

  /* --- PRELOADER --- */
  var preloader = document.querySelector('.preloader');
  if (preloader) {
    window.addEventListener('load', function () {
      setTimeout(function () {
        preloader.classList.add('loaded');
        setTimeout(function () { preloader.remove(); }, 500);
      }, 400);
    });
    // Safety: force remove after 2s even if load stalls
    setTimeout(function () {
      if (preloader && preloader.parentNode) {
        preloader.classList.add('loaded');
        setTimeout(function () { preloader.remove(); }, 700);
      }
    }, 3000);
  }

  /* --- SCROLL PROGRESS BAR --- */
  var progressBar = document.querySelector('.scroll-progress');
  if (progressBar) {
    window.addEventListener('scroll', function () {
      var scrollTop = window.scrollY;
      var docHeight = document.documentElement.scrollHeight - window.innerHeight;
      progressBar.style.width = (scrollTop / docHeight * 100) + '%';
    }, { passive: true });
  }

  /* --- PAGE TRANSITIONS --- */
  var transitionOverlay = document.querySelector('.page-transition');
  if (transitionOverlay && !prefersReducedMotion) {
    document.querySelectorAll('a[href$=".html"]').forEach(function (link) {
      // Skip external links & same page
      if (link.hostname !== window.location.hostname) return;
      if (link.getAttribute('href') === window.location.pathname.split('/').pop()) return;
      
      link.addEventListener('click', function (e) {
        var href = link.getAttribute('href');
        e.preventDefault();
        transitionOverlay.classList.add('active');
        setTimeout(function () { window.location.href = href; }, 500);
      });
    });
  }

  /* --- STAGGERED REVEALS (menu items, formules, vin-cards) --- */
  if (!prefersReducedMotion) {
    var staggerTargets = document.querySelectorAll('.menu-item, .formule, .vin-card, .menu-cat__head');
    if (staggerTargets.length > 0 && 'IntersectionObserver' in window) {
      var staggerIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            // Find siblings and stagger them
            var parent = entry.target.parentElement;
            var siblings = parent ? parent.querySelectorAll('.menu-item, .formule, .vin-card') : [];
            if (siblings.length > 0) {
              siblings.forEach(function (sib, i) {
                setTimeout(function () { sib.classList.add('visible'); }, i * 80);
              });
            }
            entry.target.classList.add('visible');
            staggerIO.unobserve(entry.target);
          }
        });
      }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });

      staggerTargets.forEach(function (el) { staggerIO.observe(el); });
    } else {
      // Fallback: show all
      staggerTargets.forEach(function (el) { el.classList.add('visible'); });
    }
  } else {
    document.querySelectorAll('.menu-item, .formule, .vin-card, .menu-cat__head').forEach(function (el) {
      el.classList.add('visible');
    });
  }

  /* --- PARALLAX HERO (subtle) --- */
  if (!prefersReducedMotion) {
    var heroContainer = document.querySelector('.page-hero .container');
    if (heroContainer) {
      window.addEventListener('scroll', function () {
        var scrollY = window.scrollY;
        if (scrollY < 600) {
          heroContainer.style.transform = 'translateY(' + (scrollY * 0.15) + 'px)';
          heroContainer.style.opacity = 1 - (scrollY / 500);
        }
      }, { passive: true });
    }
  }

  /* --- LAZY LOADING IMAGES --- */
  document.querySelectorAll('img[data-src]').forEach(function (img) {
    if ('IntersectionObserver' in window) {
      var lazyIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
            lazyIO.unobserve(img);
          }
        });
      }, { rootMargin: '200px' });
      lazyIO.observe(img);
    } else {
      img.src = img.dataset.src;
    }
  });

  /* --- ARDOISE DU TERRIER (Dynamic announcements on homepage) --- */
  var ardoiseSection = document.getElementById('ardoise-section');
  var ardoiseContent = document.getElementById('ardoise-content');
  if (ardoiseSection && ardoiseContent) {
    fetch('/api.php?action=public-announcements')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (!d.data || !d.data.length) return;
        ardoiseSection.style.display = '';
        var types = {info:'Info',event:'Événement',promo:'Promotion',urgent:'Important',horaires:'Horaires'};
        var html = '';
        d.data.forEach(function(a) {
          html += '<div class="ardoise__item">';
          html += '<p class="ardoise__type">' + (types[a.type] || a.type) + '</p>';
          html += '<p class="ardoise__title">' + (a.title || '') + '</p>';
          if (a.content) html += '<p class="ardoise__text">' + a.content + '</p>';
          if (a.link && a.link_text && !/^javascript:/i.test(a.link)) html += '<a href="' + a.link + '" class="ardoise__link">' + a.link_text + '</a>';
          html += '</div>';
        });
        ardoiseContent.innerHTML = html;
      })
      .catch(function() { /* silently fail — no announcements */ });
  }

  /* --- DYNAMIC REVIEWS (Avis Google on homepage) --- */
  var reviewsGrid = document.getElementById('reviews-grid');
  if (reviewsGrid) {
    fetch('/api.php?action=public-reviews')
      .then(function(r) { return r.json(); })
      .then(function(d) {
        if (!d.data || !d.data.length) {
          document.getElementById('reviews-count').textContent = 'Aucun avis pour le moment';
          return;
        }
        // Update score
        var scoreEl = document.getElementById('reviews-score');
        var starsEl = document.getElementById('reviews-stars');
        var countEl = document.getElementById('reviews-count');
        if (scoreEl) scoreEl.textContent = d.average + ' / 5';
        if (starsEl) {
          var full = Math.floor(d.average);
          var stars = '';
          for (var i = 0; i < full; i++) stars += '★';
          for (var j = full; j < 5; j++) stars += '☆';
          starsEl.textContent = stars;
        }
        if (countEl) countEl.textContent = 'Basé sur ' + d.count + ' avis';
        // Render review cards
        var html = '';
        var delays = ['reveal-d1', 'reveal-d2', 'reveal-d3'];
        d.data.forEach(function(r, i) {
          var delay = delays[i % delays.length] || '';
          var text = r.comment || '';
          // Escape HTML
          var div = document.createElement('div');
          div.textContent = text;
          var safeText = div.innerHTML;
          div.textContent = r.client || '';
          var safeName = div.innerHTML;
          html += '<div class="review reveal ' + delay + '">';
          html += '<p class="review__text">« ' + safeText + ' »</p>';
          html += '<p class="review__author">— ' + safeName + '</p>';
          html += '</div>';
        });
        reviewsGrid.innerHTML = html;
        // Re-init reveal observer for new elements
        if (typeof IntersectionObserver !== 'undefined') {
          var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
              if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                obs.unobserve(entry.target);
              }
            });
          }, { threshold: 0.15 });
          reviewsGrid.querySelectorAll('.reveal').forEach(function(el) { obs.observe(el); });
        }
      })
      .catch(function() {
        var countEl = document.getElementById('reviews-count');
        if (countEl) countEl.textContent = 'Basé sur les premiers avis';
      });
  }

  /* --- NEWSLETTER FORM (Brevo integration) --- */
  var nlForm = document.querySelector('.newsletter__form');
  if (nlForm) {
    nlForm.addEventListener('submit', function (e) {
      var action = nlForm.getAttribute('action');
      // If Brevo URL not yet configured, show feedback
      if (!action || action === '#BREVO_FORM_URL' || action === '#') {
        e.preventDefault();
        var emailInput = nlForm.querySelector('.newsletter__input');
        if (emailInput && emailInput.value) {
          nlForm.innerHTML = '<p class="newsletter__text" style="color:var(--or);margin:0;">Merci ! Nous vous tiendrons informé.</p>';
        }
      }
      // If Brevo URL is set, form submits normally via POST
    });
  }

})();
