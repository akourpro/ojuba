/* ======================================================================
   Default theme — assets/js/main.js
   مستخرج ومدمج من السكربتات المضمّنة في landing.html / landing-en.html /
   blogs.html / blog-details.html. يعمل بأمان على كل صفحات القالب لأن كل
   جزء يتحقق من وجود عناصره قبل ربط أي مستمع.
   (سكربت اختيار الوضع الليلي/النهاري المبكر يبقى inline في header.twig
   عمداً لمنع وميض الوضع الخاطئ قبل تحميل الـ CSS — هذا نمط معياري.)
   ====================================================================== */
document.addEventListener('DOMContentLoaded', function () {
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------- Persist current language across internal links ----------
     بحيث أي رابط بالموقع (نفس النطاق) لو نُسخ أو أُرسل لأي مكان، يفتح بنفس
     لغة الزائر الحالية بدل الاعتماد فقط على الكوكيز (اللي ما تنتقل مع الرابط). */
  (function () {
    var curLang = document.documentElement.lang;
    if (curLang !== 'ar' && curLang !== 'en') return;
    var anchors = document.querySelectorAll('a[href]');
    Array.prototype.forEach.call(anchors, function (a) {
      var raw = a.getAttribute('href');
      if (!raw || raw.charAt(0) === '#' || raw.indexOf('mailto:') === 0 || raw.indexOf('tel:') === 0 || raw.indexOf('javascript:') === 0) return;
      var url;
      try {
        url = new URL(raw, document.baseURI);
      } catch (e) {
        return;
      }
      if (url.origin !== location.origin || url.searchParams.has('lang')) return;
      url.searchParams.set('lang', curLang);
      a.setAttribute('href', url.toString());
    });
  })();

  /* ---------- Mobile nav toggle ---------- */
  var navToggle = document.getElementById('navToggle');
  var mobileMenu = document.getElementById('mobileMenu');
  if (navToggle && mobileMenu) {
    navToggle.addEventListener('click', function () {
      mobileMenu.classList.toggle('open');
    });
    mobileMenu.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () { mobileMenu.classList.remove('open'); });
    });
  }

  /* ---------- Dark / light mode toggle ---------- */
  var root = document.documentElement;
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      var next = current === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('ojubasport-theme', next); } catch (e) {}
    });
  }

  /* ---------- Hide sticky mobile CTA bar once #contact is on screen (home page) ---------- */
  var ctaBar = document.querySelector('.mobile-cta-bar');
  var contact = document.getElementById('contact');
  if (ctaBar && contact && 'IntersectionObserver' in window) {
    var ctaObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        ctaBar.classList.toggle('hide', entry.isIntersecting);
      });
    }, { threshold: 0.3 });
    ctaObserver.observe(contact);
  }

  /* ---------- Blog listing: client-side title/text search ---------- */
  var blogGrid = document.getElementById('blogGrid');
  var searchInput = document.getElementById('blogSearch');
  var noResults = document.getElementById('noResults');
  if (blogGrid && searchInput) {
    var cards = blogGrid.querySelectorAll('.blog-card');
    var applySearch = function () {
      var query = (searchInput.value || '').trim().toLowerCase();
      var visibleCount = 0;
      cards.forEach(function (card) {
        var text = (card.getAttribute('data-title') || '').toLowerCase();
        var visible = query === '' || text.indexOf(query) !== -1;
        card.style.display = visible ? '' : 'none';
        if (visible) { visibleCount++; }
      });
      if (noResults) { noResults.style.display = visibleCount === 0 ? 'block' : 'none'; }
    };
    searchInput.addEventListener('input', applySearch);
  }

  /* ---------- Newsletter form is wired to the real API — see js/subscribe.js ---------- */

  /* ---------- Copy article link (blog detail) ---------- */
  var copyBtn = document.getElementById('copyLinkBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', function () {
      var url = window.location.href;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
          copyBtn.style.borderColor = 'var(--success)';
          setTimeout(function () { copyBtn.style.borderColor = ''; }, 1200);
        });
      }
    });
  }

  if (reduceMotion) { return; }

  /* ---------- Scroll reveal ---------- */
  var revealSelectors = [
    '.section-head', '.pillar', '.feature', '.flow-step', '.why-item', '.sec-card',
    '.app-feature', '.compare', '.phone-wrap', '.contact-form',
    '.info-card', '.social-box', '.video-frame', '.testimonial-card', '.clients-strip',
    '.blog-card', '.news-card', '.featured-post', '.newsletter', '.stat-item', '.branch-card', '.cert-card',
    '.video-card'
  ].join(', ');
  var revealEls = document.querySelectorAll(revealSelectors);

  if (revealEls.length && 'IntersectionObserver' in window) {
    revealEls.forEach(function (el, i) {
      el.classList.add('reveal');
      el.style.transitionDelay = (i % 6) * 70 + 'ms';
    });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('in-view');
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });
    revealEls.forEach(function (el) { io.observe(el); });
  } else if (revealEls.length) {
    revealEls.forEach(function (el) { el.classList.add('in-view'); });
  }

  /* شبكة أمان: أي عنصر بقي بكلاس reveal بدون in-view بعد 3.5 ثانية (مثلاً لأن
     كلاس Twig جديد لم يُضَف بعد لمصفوفة revealSelectors أعلاه) يُظهَر تلقائياً
     بدل أن يبقى مخفياً (opacity:0) للأبد رغم أن رابطه يعمل بصمت. */
  setTimeout(function () {
    document.querySelectorAll('.reveal:not(.in-view)').forEach(function (el) {
      el.classList.add('in-view');
    });
  }, 3500);

  /* ---------- Subtle tilt on phone mockups, pointer devices only ---------- */
  if (window.matchMedia && window.matchMedia('(pointer: fine)').matches) {
    document.querySelectorAll('.phone').forEach(function (el) {
      el.addEventListener('mousemove', function (e) {
        var rect = el.getBoundingClientRect();
        var x = (e.clientX - rect.left) / rect.width - 0.5;
        var y = (e.clientY - rect.top) / rect.height - 0.5;
        el.style.transform = 'perspective(900px) rotateX(' + (y * -6) + 'deg) rotateY(' + (x * 6) + 'deg)';
      });
      el.addEventListener('mouseleave', function () {
        el.style.transform = '';
      });
    });
  }
});
