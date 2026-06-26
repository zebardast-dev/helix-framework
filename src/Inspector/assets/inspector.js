(function () {
  'use strict';

  var data = JSON.parse(document.getElementById('hxi-data').textContent);
  var panel = document.getElementById('hxi-panel');
  var panesEl = document.getElementById('hxi-panes');
  var tabsEl = document.getElementById('hxi-tabs');
  var toggleBtn = document.getElementById('hxi-toggle');
  var metaBar = document.getElementById('hxi-meta-bar');
  var collapsed = true;
  var activeTab = 'overview';

  /* ── helpers ── */
  function fmt(ms) {
    if (ms == null) return '—';
    return ms >= 1000 ? (ms / 1000).toFixed(2) + 's' : ms.toFixed(1) + 'ms';
  }

  function fmtBytes(b) {
    if (!b) return '—';
    if (b >= 1073741824) return (b / 1073741824).toFixed(2) + 'GB';
    if (b >= 1048576)    return (b / 1048576).toFixed(1) + 'MB';
    return (b / 1024).toFixed(0) + 'KB';
  }

  function escape(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function timeClass(ms) {
    if (ms == null) return '';
    if (ms < 200) return 'hxi-ok';
    if (ms < 500) return 'hxi-warn';
    return 'hxi-err';
  }

  /* ── pane builders ── */
  function buildOverview() {
    var meta = data._meta || {};
    var perf = data.performance || {};
    var q    = data.queries || {};
    var seo  = data.seo || {};
    var rt   = (meta.render_time || 0) * 1000;
    var peak = meta.peak_memory || perf.peak_memory || 0;

    function card(label, value, cls, sub) {
      return '<div class="hxi-card">'
        + '<div class="hxi-card-label">' + label + '</div>'
        + '<div class="hxi-card-value ' + (cls || '') + '">' + value + '</div>'
        + (sub ? '<div class="hxi-card-sub">' + sub + '</div>' : '')
        + '</div>';
    }

    var rtCls  = timeClass(rt);
    var qTotal = q.total || 0;
    var qCls   = qTotal > 30 ? 'hxi-warn' : (qTotal > 50 ? 'hxi-err' : 'hxi-ok');
    var score  = seo.score != null ? seo.score : null;
    var scoreCls = score == null ? '' : (score >= 80 ? 'hxi-ok' : score >= 50 ? 'hxi-warn' : 'hxi-err');

    return '<div class="hxi-grid">'
      + card('Render Time', fmt(rt), rtCls, perf.php_version ? 'PHP ' + escape(perf.php_version) : '')
      + card('Peak Memory', fmtBytes(peak), peak > 67108864 ? 'hxi-warn' : 'hxi-ok', 'limit: ' + escape(perf.memory_limit || '—'))
      + card('DB Queries', qTotal, qCls, q.total_time ? fmt(q.total_time) + ' total' : '')
      + card('Duplicates', q.duplicates || 0, (q.duplicates || 0) > 0 ? 'hxi-warn' : 'hxi-ok')
      + card('Slow Queries', q.slow || 0, (q.slow || 0) > 0 ? 'hxi-err' : 'hxi-ok', '>50ms')
      + card('SEO Score', score != null ? score + '/100' : '—', scoreCls)
      + '</div>'
      + buildEnvRow();
  }

  function buildEnvRow() {
    var v = data.views || {};
    if (!v.page_type) return '';

    var tags = (v.conditionals || []).map(function (c) {
      return '<span class="hxi-tag">' + escape(c) + '</span>';
    }).join('');

    return '<div class="hxi-section">Page Context</div>'
      + '<table class="hxi-table"><tbody>'
      + row('Page type', escape(v.page_type))
      + row('Post type', escape(v.post_type || '—'))
      + row('Template', escape(v.template || '—'))
      + row('Conditionals', tags || '—')
      + row('Blade cache', (v.cache_files || 0) + ' compiled files')
      + '</tbody></table>';
  }

  function row(k, v) {
    return '<tr><td class="hxi-key">' + k + '</td><td class="hxi-val">' + v + '</td></tr>';
  }

  function buildPerformance() {
    var p    = data.performance || {};
    var meta = data._meta || {};
    var rt   = (meta.render_time || 0) * 1000;

    return '<div class="hxi-section">Timing</div>'
      + '<table class="hxi-table"><tbody>'
      + row('Total page time',  '<span class="' + timeClass(rt) + '">' + fmt(rt) + '</span>')
      + row('WP loaded',        p.wp_loaded_ms != null ? fmt(p.wp_loaded_ms) : '—')
      + row('Template loaded',  p.template_ms  != null ? fmt(p.template_ms)  : '—')
      + '</tbody></table>'
      + '<div class="hxi-section" style="margin-top:16px">Memory</div>'
      + '<table class="hxi-table"><tbody>'
      + row('Current', fmtBytes(meta.memory || p.memory))
      + row('Peak',    fmtBytes(meta.peak_memory || p.peak_memory))
      + row('Limit',   escape(p.memory_limit || '—'))
      + '</tbody></table>'
      + '<div class="hxi-section" style="margin-top:16px">Environment</div>'
      + '<table class="hxi-table"><tbody>'
      + row('PHP',       escape(p.php_version || '—'))
      + row('WordPress', escape(typeof wpVersion !== 'undefined' ? wpVersion : (window.wp && window.wp.blocks ? 'detected' : '—')))
      + '</tbody></table>';
  }

  function buildQueries() {
    var q = data.queries || {};

    if (!q.enabled) {
      return '<div class="hxi-notice">'
        + 'Add <span class="hxi-code">define(\'SAVEQUERIES\', true);</span> to <span class="hxi-code">wp-config.php</span> to enable query tracking.'
        + '</div>';
    }

    if (!q.queries || !q.queries.length) {
      return '<div class="hxi-empty">No queries recorded</div>';
    }

    var summary = '<div class="hxi-grid" style="margin-bottom:16px">'
      + '<div class="hxi-card"><div class="hxi-card-label">Total</div><div class="hxi-card-value">' + q.total + '</div></div>'
      + '<div class="hxi-card"><div class="hxi-card-label">Total time</div><div class="hxi-card-value">' + fmt(q.total_time) + '</div></div>'
      + '<div class="hxi-card"><div class="hxi-card-label">Duplicates</div><div class="hxi-card-value ' + (q.duplicates > 0 ? 'hxi-warn' : 'hxi-ok') + '">' + q.duplicates + '</div></div>'
      + '<div class="hxi-card"><div class="hxi-card-label">Slow (&gt;50ms)</div><div class="hxi-card-value ' + (q.slow > 0 ? 'hxi-err' : 'hxi-ok') + '">' + q.slow + '</div></div>'
      + '</div>';

    var rows = q.queries.map(function (item) {
      var slow = item.time > 50;
      return '<tr>'
        + '<td class="hxi-sql">' + escape(item.sql) + '</td>'
        + '<td class="hxi-time' + (slow ? ' slow' : '') + '">' + fmt(item.time) + '</td>'
        + '<td class="hxi-caller">' + escape(item.caller) + '</td>'
        + '</tr>';
    }).join('');

    return summary
      + '<div class="hxi-section">All Queries</div>'
      + '<table class="hxi-table"><thead><tr><th>SQL</th><th>Time</th><th>Caller</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table>';
  }

  function buildViews() {
    var v = data.views || {};

    if (!v.page_type) {
      return '<div class="hxi-empty">No view data collected</div>';
    }

    var tags = (v.conditionals || []).map(function (c) {
      return '<span class="hxi-tag">' + escape(c) + '</span>';
    }).join('');

    return '<div class="hxi-section">Page Info</div>'
      + '<table class="hxi-table"><tbody>'
      + row('Page type',    escape(v.page_type))
      + row('Post type',    escape(v.post_type || '—'))
      + row('Template',     escape(v.template  || '—'))
      + row('Conditionals', tags || '—')
      + '</tbody></table>'
      + '<div class="hxi-section">Blade Cache</div>'
      + '<table class="hxi-table"><tbody>'
      + row('Compiled files', v.cache_files || 0)
      + row('Cache dir', v.cache_dir ? '<span class="hxi-code" style="font-size:10px">' + escape(v.cache_dir) + '</span>' : '—')
      + '</tbody></table>';
  }

  function buildSeo() {
    var seo    = data.seo || {};
    var score  = seo.score != null ? seo.score : 0;
    var checks = seo.checks || {};
    var sCls   = score >= 80 ? 'good' : score >= 50 ? 'mid' : 'bad';
    var sLabel = score >= 80 ? 'Good' : score >= 50 ? 'Needs attention' : 'Poor';

    var scoreHtml = '<div class="hxi-score-wrap">'
      + '<div class="hxi-score-num ' + sCls + '">' + score + '</div>'
      + '<div class="hxi-score-info"><div class="label">SEO Score</div><div class="status">' + sLabel + ' — /100</div></div>'
      + '</div>';

    var checksHtml = Object.keys(checks).map(function (key) {
      var c = checks[key];
      var valHtml = c.value
        ? '<div class="hxi-check-value">' + escape(String(c.value).substring(0, 120)) + '</div>'
        : '';
      var lenHtml = c.length
        ? ' <span style="color:#4e5566">(' + c.length + ' chars)</span>'
        : '';
      return '<div class="hxi-check ' + escape(c.status) + '">'
        + '<i class="hxi-check-icon"></i>'
        + '<span class="hxi-check-label">' + escape(key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ')) + '</span>'
        + '<div class="hxi-check-body">'
        + valHtml
        + '<div class="hxi-check-note">' + escape(c.note || '') + lenHtml + '</div>'
        + '</div></div>';
    }).join('');

    return scoreHtml + '<div class="hxi-section">Checks</div>' + checksHtml;
  }

  var builders = {
    overview:    buildOverview,
    performance: buildPerformance,
    queries:     buildQueries,
    views:       buildViews,
    seo:         buildSeo,
  };

  /* ── render ── */
  function renderPane(name) {
    var fn = builders[name];
    return fn ? fn() : '<div class="hxi-empty">No data</div>';
  }

  function openTab(name) {
    activeTab = name;

    tabsEl.querySelectorAll('.hxi-tab').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.tab === name);
    });

    var existing = panesEl.querySelector('[data-pane="' + name + '"]');
    if (!existing) {
      var div = document.createElement('div');
      div.className = 'hxi-pane';
      div.dataset.pane = name;
      div.innerHTML = renderPane(name);
      panesEl.appendChild(div);
      existing = div;
    }

    panesEl.querySelectorAll('.hxi-pane').forEach(function (p) {
      p.classList.remove('active');
    });
    existing.classList.add('active');
  }

  function decorateTabs() {
    var q = data.queries || {};

    tabsEl.querySelectorAll('.hxi-tab').forEach(function (btn) {
      var name = btn.dataset.tab;
      if (name === 'queries' && q.total) {
        var cls = q.slow > 0 ? 'hxi-err' : q.duplicates > 0 ? 'hxi-warn' : '';
        btn.innerHTML = '🗄 Queries <span class="hxi-badge">' + q.total + '</span>';
        if (cls) btn.classList.add(cls);
      }
    });
  }

  function updateMetaBar() {
    var meta = data._meta || {};
    var perf = data.performance || {};
    var q    = data.queries || {};
    var rt   = (meta.render_time || 0) * 1000;
    var peak = meta.peak_memory || perf.peak_memory || 0;

    metaBar.innerHTML =
      '<span class="' + timeClass(rt) + '">' + fmt(rt) + '</span>'
      + '<span>' + fmtBytes(peak) + '</span>'
      + (q.total ? '<span>' + q.total + ' queries</span>' : '');
  }

  /* ── events ── */
  toggleBtn.addEventListener('click', function () {
    collapsed = !collapsed;
    panel.classList.toggle('collapsed', collapsed);
    if (!collapsed) openTab(activeTab);
  });

  tabsEl.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.hxi-tab') : null;
    if (!btn) return;
    if (collapsed) {
      collapsed = false;
      panel.classList.remove('collapsed');
    }
    openTab(btn.dataset.tab);
  });

  /* ── init ── */
  decorateTabs();
  updateMetaBar();
  openTab('overview');

  /* ── body padding to prevent overlap ── */
  var spacer = document.createElement('div');
  spacer.className = 'hxi-bar-spacer';
  document.body.appendChild(spacer);

})();
