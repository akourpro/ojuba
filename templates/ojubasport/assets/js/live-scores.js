/* ======================================================================
   OjubaSport — تحديث لوحة النتائج تلقائياً دون إعادة تحميل الصفحة.
   يعمل فقط على الصفحة الرئيسية (حيث يوجد #scoresDashboard). يستطلع
   api/live-scores كل 25 ثانية طالما توجد مباراة "مباشرة" حالياً ضمن آخر
   استجابة (أو لم نتحقق بعد)، ويبطئ الاستطلاع إلى كل 90 ثانية إن لم توجد أي
   مباراة مباشرة — توفيراً للطلبات دون فقدان فائدة التحديث التلقائي.
   ====================================================================== */
(function () {
  var dashboard = document.getElementById('scoresDashboard');
  if (!dashboard) return;

  var lang = dashboard.getAttribute('data-lang') === 'en' ? 'en' : 'ar';
  var listBody = document.getElementById('scoresListBody');
  var lastUpdatedEl = document.getElementById('scoresLastUpdated');
  var refreshBtn = document.getElementById('scoresRefreshBtn');

  // رابط الموقع + رابط مسار "المباريات" الحالي (المخصص أو الافتراضي) — يُقرآن من
  // سمات data- على #scoresDashboard (مضبوطتان بـ home.twig/home_en.twig عبر
  // {{ siteUrl }}/{{ routes.matches }})، ويُحدَّثان أيضاً من استجابة api/live-scores
  // (حقل matches_route) عند كل تحديث تلقائي — حتى تبقى الروابط المبنية هنا متوافقة
  // دائماً مع نظام "روابط المسارات القابلة للتخصيص" حتى لو غيّر صاحب الموقع الرابط
  // بعد تحميل الصفحة (بدون إعادة تحميل كاملة).
  var siteUrlBase = dashboard.getAttribute('data-site-url') || (window.location.origin + '/');
  var matchesRoute = dashboard.getAttribute('data-matches-route') || 'matches';

  function matchUrl(id) {
    return siteUrlBase + matchesRoute + '/' + id;
  }

  var STR = {
    ar: {
      live: 'مباشر الآن',
      finished: 'انتهت',
      vs: 'VS',
      noOther: 'لا توجد مباريات أخرى مجدولة حالياً.'
    },
    en: {
      live: 'Live Now',
      finished: 'Finished',
      vs: 'VS',
      noOther: 'No other matches scheduled right now.'
    }
  };
  var t = STR[lang];

  var VENUE_ICON = '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
  var BROADCAST_ICON = '<svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><polygon points="10 10 15 12.5 10 15"/></svg>';

  function esc(s) {
    return String(s === null || s === undefined ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function badgeHtml(m) {
    if (m.match_status === 'live') return '<span class="match-badge live">' + t.live + '</span>';
    if (m.match_status === 'finished') return '<span class="match-badge finished">' + t.finished + '</span>';
    return '<span class="match-badge upcoming">' + esc(m.date_human) + '</span>';
  }

  function spotlightHtml(m) {
    var center = m.match_status === 'upcoming'
      ? '<span class="spotlight-vs">' + t.vs + '</span><span class="spotlight-time">' + esc(m.date_human) + '</span>'
      : '<span class="spotlight-score">' + esc(m.score_home) + ' - ' + esc(m.score_away) + '</span>';
    var foot = '';
    if (m.venue || m.broadcast_channel) {
      foot = '<div class="match-foot">' +
        (m.venue ? '<span class="match-venue">' + VENUE_ICON + esc(m.venue) + '</span>' : '') +
        (m.broadcast_channel ? '<span class="match-broadcast">' + BROADCAST_ICON + esc(m.broadcast_channel) + '</span>' : '') +
        '</div>';
    }
    return '<a class="spotlight-match match-status-' + esc(m.match_status) + '" data-match-id="' + esc(m.id) + '" href="' + esc(matchUrl(m.id)) + '">' +
      '<div class="match-top">' + (m.competition ? '<span class="match-competition">' + esc(m.competition) + '</span>' : '') + badgeHtml(m) + '</div>' +
      '<div class="spotlight-teams">' +
        '<div class="spotlight-team">' + (m.team_home_logo ? '<img src="' + esc(m.team_home_logo) + '" alt="' + esc(m.team_home) + '" loading="lazy">' : '') + '<span>' + esc(m.team_home) + '</span></div>' +
        '<div class="spotlight-center">' + center + '</div>' +
        '<div class="spotlight-team">' + (m.team_away_logo ? '<img src="' + esc(m.team_away_logo) + '" alt="' + esc(m.team_away) + '" loading="lazy">' : '') + '<span>' + esc(m.team_away) + '</span></div>' +
      '</div>' + foot +
    '</a>';
  }

  function scoreRowHtml(m) {
    var score = m.match_status === 'upcoming' ? t.vs : (esc(m.score_home) + '-' + esc(m.score_away));
    var status = m.match_status === 'live' ? t.live : (m.match_status === 'finished' ? t.finished : esc(m.date_human));
    return '<a class="score-row" data-match-id="' + esc(m.id) + '" href="' + esc(matchUrl(m.id)) + '">' +
      '<span class="score-row-status status-' + esc(m.match_status) + '"></span>' +
      '<div class="score-row-main">' +
        '<div class="score-row-teams"><span>' + esc(m.team_home) + '</span><span class="score-row-score">' + score + '</span><span>' + esc(m.team_away) + '</span></div>' +
        '<div class="score-row-meta">' + (m.competition ? '<span>' + esc(m.competition) + '</span>' : '') + '<span>' + status + '</span></div>' +
      '</div>' +
    '</a>';
  }

  function render(matches) {
    if (!matches || !matches.length) return;

    var spotlight = dashboard.querySelector('.spotlight-match');
    if (spotlight) {
      spotlight.outerHTML = spotlightHtml(matches[0]);
    }

    if (listBody) {
      var rest = matches.slice(1);
      listBody.innerHTML = rest.length
        ? '<div class="scores-list-rows">' + rest.map(scoreRowHtml).join('') + '</div>'
        : '<p class="scores-list-empty">' + t.noOther + '</p>';
    }
  }

  var lastUpdatedAt = Date.now();
  function touchLastUpdated() {
    lastUpdatedAt = Date.now();
    if (lastUpdatedEl) {
      lastUpdatedEl.textContent = lastUpdatedEl.getAttribute('data-label-now') || lastUpdatedEl.textContent;
    }
  }

  setInterval(function () {
    if (!lastUpdatedEl) return;
    var secs = Math.round((Date.now() - lastUpdatedAt) / 1000);
    if (secs < 5) return;
    var tpl = lastUpdatedEl.getAttribute('data-label-ago');
    if (tpl) {
      lastUpdatedEl.textContent = tpl.replace('{s}', secs);
    }
  }, 5000);

  var pollTimer = null;
  var fastInterval = 25000;
  var slowInterval = 90000;
  var fetching = false;

  function poll() {
    if (fetching) return;
    fetching = true;
    fetch('api/live-scores?limit=6', { headers: { 'Accept': 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        fetching = false;
        if (!data || !data.status) return;
        if (data.matches_route) matchesRoute = data.matches_route;
        render(data.matches);
        touchLastUpdated();
        var hasLive = (data.matches || []).some(function (m) { return m.match_status === 'live'; });
        scheduleNext(hasLive ? fastInterval : slowInterval);
      })
      .catch(function () {
        fetching = false;
        scheduleNext(slowInterval);
      });
  }

  function scheduleNext(delay) {
    if (pollTimer) clearTimeout(pollTimer);
    pollTimer = setTimeout(poll, delay);
  }

  // أول استطلاع تلقائي بعد فترة قصيرة (الصفحة أصلاً محمَّلة ببيانات حديثة عبر PHP)
  scheduleNext(fastInterval);

  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      refreshBtn.classList.add('spinning');
      poll();
      setTimeout(function () { refreshBtn.classList.remove('spinning'); }, 600);
    });
  }
})();
