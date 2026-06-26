(function () {
  'use strict';

  var data   = JSON.parse(document.getElementById('hxi-data').textContent);
  var panel  = document.getElementById('hxi-panel');
  var panes  = document.getElementById('hxi-panes');
  var tabs   = document.getElementById('hxi-tabs');
  var toggle = document.getElementById('hxi-toggle');
  var meta   = document.getElementById('hxi-meta-bar');
  var open   = false;
  var active = 'overview';

  /* ─── Helpers ─────────────────────────────────── */
  function fmt(ms) {
    if (ms == null || ms === 0) return '—';
    if (ms >= 1000) return (ms / 1000).toFixed(2) + 's';
    if (ms >= 100)  return Math.round(ms) + 'ms';
    return ms.toFixed(1) + 'ms';
  }

  function fmtBytes(b) {
    if (!b) return '—';
    if (b >= 1073741824) return (b / 1073741824).toFixed(2) + 'GB';
    if (b >= 1048576)    return (b / 1048576).toFixed(1) + 'MB';
    return Math.round(b / 1024) + 'KB';
  }

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function parseLimit(s) {
    if (!s || s === '-1') return 0;
    var n = parseFloat(s), u = s.slice(-1).toUpperCase();
    return u === 'G' ? n * 1073741824 : u === 'M' ? n * 1048576 : u === 'K' ? n * 1024 : n;
  }

  function tClass(ms) {
    if (ms == null) return 'b';
    return ms < 200 ? 'g' : ms < 500 ? 'w' : 'e';
  }

  function tAccent(ms) {
    if (ms == null) return 'ac-b';
    return ms < 200 ? 'ac-g' : ms < 500 ? 'ac-w' : 'ac-e';
  }

  function scoreColor(score) {
    return score >= 80 ? 'var(--c-green)' : score >= 50 ? 'var(--c-amber)' : 'var(--c-red)';
  }

  function qBarClass(ms) {
    return ms < 10 ? 'qf-g' : ms < 50 ? 'qf-w' : 'qf-e';
  }

  /* ─── Card helper ──────────────────────────────── */
  function card(label, value, valCls, accentCls, sub) {
    return '<div class="hxi-card ' + (accentCls || '') + '">'
      + '<div class="hxi-cl">' + label + '</div>'
      + '<div class="hxi-cv ' + (valCls || '') + '">' + value + '</div>'
      + (sub ? '<div class="hxi-cs">' + sub + '</div>' : '')
      + '</div>';
  }

  function kv(k, v) {
    return '<tr><td>' + k + '</td><td>' + v + '</td></tr>';
  }

  /* ─── SVG Gauges ───────────────────────────────── */
  function arcGauge(opts) {
    var r   = opts.r || 42;
    var sw  = opts.sw || 8;
    var sz  = opts.sz || 100;
    var cx  = sz / 2, cy = sz / 2;
    var c   = 2 * Math.PI * r;
    var pct = Math.max(0, Math.min(opts.pct || 0, 1));
    var off = c * (1 - pct);
    var col = opts.color || 'var(--c-purple)';

    return '<svg width="' + sz + '" height="' + sz + '" viewBox="0 0 ' + sz + ' ' + sz + '">'
      + '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '"'
      + ' fill="none" stroke="var(--c-borderL)" stroke-width="' + sw + '"/>'
      + '<circle cx="' + cx + '" cy="' + cy + '" r="' + r + '"'
      + ' fill="none" stroke="' + col + '" stroke-width="' + sw + '"'
      + ' class="hxi-arc-fill"'
      + ' stroke-linecap="round"'
      + ' stroke-dasharray="' + c.toFixed(2) + '"'
      + ' stroke-dashoffset="' + c.toFixed(2) + '"'
      + ' data-target="' + off.toFixed(2) + '"'
      + ' transform="rotate(-90 ' + cx + ' ' + cy + ')"/>'
      + (opts.text || '')
      + '</svg>';
  }

  function seoGauge(score) {
    var col = scoreColor(score);
    var r = 52, sz = 130;
    return arcGauge({
      r: r, sw: 9, sz: sz, pct: score / 100, color: col,
      text:
        '<text x="65" y="57" text-anchor="middle" font-size="28" font-weight="800"'
        + ' fill="' + col + '" font-family="SF Mono,Consolas,monospace">' + score + '</text>'
        + '<text x="65" y="74" text-anchor="middle" font-size="10"'
        + ' fill="var(--c-dim)" font-family="SF Mono,Consolas,monospace">/100</text>'
    });
  }

  function memGauge(peak, limit) {
    var pct = limit > 0 ? Math.min(peak / limit, 1) : 0.4;
    var col = pct > 0.8 ? 'var(--c-red)' : pct > 0.5 ? 'var(--c-amber)' : 'var(--c-green)';
    return arcGauge({ r: 32, sw: 6, sz: 78, pct: pct, color: col });
  }

  /* Animate all pending arcs in a container */
  function animateArcs(el) {
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.querySelectorAll('.hxi-arc-fill[data-target]').forEach(function (arc) {
          arc.style.strokeDashoffset = arc.getAttribute('data-target');
        });
      });
    });
  }

  /* ─── Pane: Overview ───────────────────────────── */
  function buildOverview() {
    var m  = data._meta || {};
    var p  = data.performance || {};
    var q  = data.queries || {};
    var s  = data.seo || {};
    var rt = (m.render_time || 0) * 1000;
    var pk = m.peak_memory || p.peak_memory || 0;
    var qt = q.total || 0;
    var sc = s.score != null ? s.score : null;

    var qAcc = qt <= 20 ? 'ac-g' : qt <= 40 ? 'ac-w' : 'ac-e';
    var qCls = qt <= 20 ? 'g'    : qt <= 40 ? 'w'    : 'e';
    var sAcc = sc == null ? '' : sc >= 80 ? 'ac-g' : sc >= 50 ? 'ac-w' : 'ac-e';
    var sCls = sc == null ? '' : sc >= 80 ? 'g'    : sc >= 50 ? 'w'    : 'e';
    var pkW  = pk > 67108864;

    var html = '<div class="hxi-cards g3">'
      + card('Render Time',  fmt(rt), tClass(rt), tAccent(rt), 'PHP ' + esc(p.php_version || '8'))
      + card('Peak Memory',  fmtBytes(pk), pkW ? 'w' : 'g', pkW ? 'ac-w' : 'ac-g', 'limit: ' + esc(p.memory_limit || '—'))
      + card('DB Queries',   qt,       qCls, qAcc, q.total_time ? fmt(q.total_time) + ' total' : 'no query data')
      + '</div>';

    html += '<div class="hxi-cards g3" style="margin-bottom:18px">'
      + card('Duplicates',   q.duplicates || 0, (q.duplicates || 0) > 0 ? 'w' : 'g', (q.duplicates || 0) > 0 ? 'ac-w' : 'ac-g', (q.duplicates || 0) > 0 ? 'same query ran multiple times' : 'no duplicate queries')
      + card('Slow Queries', q.slow || 0,       (q.slow || 0) > 0 ? 'e' : 'g',       (q.slow || 0) > 0 ? 'ac-e' : 'ac-g',       '> 50ms')
      + card('SEO Score',    sc != null ? sc + '<small style="font-size:14px;letter-spacing:0">/100</small>' : '—', sCls, sAcc, sc != null ? (sc >= 80 ? 'Good' : sc >= 50 ? 'Needs attention' : 'Poor') : 'no data')
      + '</div>';

    var v = data.views || {};
    if (v.page_type) {
      var tags = (v.conditionals || []).map(function (c) { return '<span class="hxi-tag">' + esc(c) + '</span>'; }).join(' ');
      html += '<div class="hxi-sec">Page Context</div>';
      html += '<table class="hxi-t hxi-kv"><tbody>'
        + kv('Page type',    '<span class="hxi-tag">' + esc(v.page_type) + '</span>')
        + kv('Post type',    esc(v.post_type || '—'))
        + kv('Template',     v.blade_file ? '<span class="hxi-code">' + esc(v.blade_file) + '</span>' : '—')
        + kv('Conditionals', tags || '—')
        + (v.total_cache != null ? kv('Compiled cache', v.total_cache + ' total templates on disk') : '')
        + '</tbody></table>';
    }

    return html;
  }

  /* ─── Pane: Performance ────────────────────────── */
  function buildPerformance() {
    var p  = data.performance || {};
    var m  = data._meta || {};
    var rt = (m.render_time || 0) * 1000;
    var pk = m.peak_memory || p.peak_memory || 0;
    var lm = parseLimit(p.memory_limit);

    var wpMs  = p.wp_loaded_ms  || 0;
    var tmMs  = p.template_ms   || 0;
    var total = rt || 1;

    var bootMs   = wpMs;
    var initMs   = tmMs > wpMs ? tmMs - wpMs : 0;
    var renderMs = total - (tmMs || wpMs || total * 0.8);
    if (renderMs < 0) renderMs = total * 0.2;

    function tlRow(label, startMs, durMs, cls) {
      var sp = total > 0 ? (startMs / total * 100).toFixed(1) : 0;
      var wp = total > 0 ? Math.max(durMs / total * 100, 0.5).toFixed(1) : 0;
      var showLabel = parseFloat(wp) > 7;
      return '<div class="hxi-tl-row">'
        + '<span class="hxi-tl-lbl">' + label + '</span>'
        + '<div class="hxi-tl-track">'
        + '<div class="hxi-tl-bar ' + cls + '"'
        + ' data-left="' + sp + '%" data-width="' + wp + '%"'
        + ' style="left:' + sp + '%;width:0">'
        + (showLabel ? fmt(durMs) : '')
        + '</div></div>'
        + '<span class="hxi-tl-ms" style="color:' + (durMs < 100 ? 'var(--c-green)' : durMs < 300 ? 'var(--c-amber)' : 'var(--c-red)') + '">' + fmt(durMs) + '</span>'
        + '</div>';
    }

    var rows = '';
    if (bootMs > 0)   rows += tlRow('WP Boot',   0,       bootMs,   'b-boot');
    if (initMs > 0)   rows += tlRow('Init',       bootMs,  initMs,   'b-init');
    rows += tlRow('Theme', tmMs || bootMs, renderMs, 'b-theme');

    var tCol = rt < 200 ? 'var(--c-green)' : rt < 500 ? 'var(--c-amber)' : 'var(--c-red)';

    var timeline = '<div class="hxi-timeline">'
      + '<div class="hxi-tl-rows">' + rows + '</div>'
      + '<div class="hxi-tl-foot">'
      + '<span class="lbl">Total page time</span>'
      + '<span class="val" style="color:' + tCol + '">' + fmt(rt) + '</span>'
      + '</div></div>';

    var memPct  = lm > 0 ? pk / lm : 0;
    var memCol  = memPct > 0.8 ? 'var(--c-red)' : memPct > 0.5 ? 'var(--c-amber)' : 'var(--c-green)';
    var memRing = memGauge(pk, lm);

    var bottom = '<div class="hxi-perf-row">'
      + '<div class="hxi-mem-card">'
      + memRing
      + '<div class="hxi-mem-info">'
      + '<div class="big" style="color:' + memCol + '">' + fmtBytes(pk) + '</div>'
      + '<div class="lbl">Peak Memory</div>'
      + '<div class="sub">limit: ' + esc(p.memory_limit || '—') + '</div>'
      + '</div></div>'
      + '<div style="background:var(--c-card);border:1px solid var(--c-border);border-radius:10px;padding:16px">'
      + '<div class="hxi-sec" style="margin-top:0">System</div>'
      + '<table class="hxi-t hxi-kv"><tbody>'
      + kv('PHP',         esc(p.php_version || '—'))
      + kv('WP loaded',   p.wp_loaded_ms ? fmt(p.wp_loaded_ms) : '—')
      + kv('Template',    p.template_ms  ? fmt(p.template_ms)  : '—')
      + kv('Memory now',  fmtBytes(m.memory || p.memory))
      + '</tbody></table>'
      + '</div></div>';

    return timeline + bottom;
  }

  /* ─── Pane: Queries ────────────────────────────── */
  function buildQueries() {
    var q = data.queries || {};

    if (!q.enabled) {
      return '<div class="hxi-notice">'
        + 'Add <span class="hxi-code">define(\'SAVEQUERIES\', true);</span> to '
        + '<span class="hxi-code">wp-config.php</span> to enable query tracking.'
        + '</div>';
    }

    if (!q.queries || !q.queries.length) {
      return '<div class="hxi-empty">No queries recorded</div>';
    }

    var html = '<div class="hxi-cards g4" style="margin-bottom:16px">'
      + card('Total',       q.total,       'b', 'ac-b')
      + card('Total Time',  fmt(q.total_time || 0), 'p', 'ac-p')
      + card('Duplicates',  q.duplicates || 0, (q.duplicates||0) > 0 ? 'w' : 'g', (q.duplicates||0) > 0 ? 'ac-w' : 'ac-g')
      + card('Slow >50ms',  q.slow || 0,       (q.slow||0) > 0 ? 'e' : 'g',       (q.slow||0) > 0 ? 'ac-e' : 'ac-g')
      + '</div>';

    var maxT = Math.max.apply(null, q.queries.map(function (x) { return x.time; })) || 1;

    var rows = q.queries.map(function (item) {
      var pct    = Math.max(item.time / maxT * 100, 1).toFixed(1);
      var bCls   = qBarClass(item.time);
      var timeEl = '<div style="font-size:10px;color:'
        + (item.time >= 50 ? 'var(--c-red)' : item.time >= 10 ? 'var(--c-amber)' : 'var(--c-dim)')
        + ';margin-top:3px;text-align:center;font-weight:600">' + fmt(item.time) + '</div>';

      return '<tr>'
        + '<td><div class="hxi-sql-text">' + esc(item.sql.substring(0, 130)) + (item.sql.length > 130 ? '…' : '') + '</div></td>'
        + '<td style="width:120px;padding:6px 10px">'
        + '<div class="hxi-qbar"><div class="hxi-qbar-fill ' + bCls + '"'
        + ' data-pct="' + pct + '" style="width:0">' + (parseFloat(pct) > 15 ? fmt(item.time) : '') + '</div></div>'
        + timeEl + '</td>'
        + '<td style="width:150px"><span style="font-size:10px;color:var(--c-dim)">' + esc(item.caller) + '</span></td>'
        + '</tr>';
    }).join('');

    html += '<div class="hxi-sec">All Queries</div>';
    html += '<table class="hxi-t"><thead><tr><th>SQL</th><th>Time</th><th>Caller</th></tr></thead>'
      + '<tbody>' + rows + '</tbody></table>';

    return html;
  }

  /* ─── Pane: Views ──────────────────────────────── */
  function buildViews() {
    var v = data.views || {};
    if (!v.page_type) return '<div class="hxi-empty">No view data</div>';

    var tags = (v.conditionals || []).map(function (c) {
      return '<span class="hxi-tag">' + esc(c) + '</span>';
    }).join(' ');

    var tplRow = v.blade_file
      ? '<span class="hxi-code">' + esc(v.blade_file) + '</span>'
      : '—';

    return '<div class="hxi-sec">Template</div>'
      + '<table class="hxi-t hxi-kv"><tbody>'
      + kv('Blade view',   v.blade_view ? '<span class="hxi-tag">' + esc(v.blade_view) + '</span>' : '—')
      + kv('File',         tplRow)
      + kv('Page type',    '<span class="hxi-tag">' + esc(v.page_type) + '</span>')
      + kv('Post type',    esc(v.post_type || '—'))
      + kv('Conditionals', tags || '—')
      + '</tbody></table>'
      + (v.total_cache != null
        ? '<div class="hxi-sec">Cache</div>'
          + '<table class="hxi-t hxi-kv"><tbody>'
          + kv('Compiled templates', v.total_cache + ' files on disk (all views, not just this page)')
          + '</tbody></table>'
        : '');
  }

  /* ─── Pane: SEO ────────────────────────────────── */
  function buildSeo() {
    var s      = data.seo || {};
    var score  = s.score != null ? s.score : 0;
    var checks = s.checks || {};
    var col    = scoreColor(score);
    var grade  = score >= 90 ? 'Excellent' : score >= 80 ? 'Good' : score >= 50 ? 'Needs Work' : 'Poor';
    var desc   = score >= 80
      ? 'This page meets key SEO requirements.'
      : score >= 50
      ? 'Some improvements would help search visibility.'
      : 'Several critical SEO issues need attention.';

    var hero = '<div class="hxi-seo-hero">'
      + seoGauge(score)
      + '<div class="hxi-gauge-info">'
      + '<div class="hxi-gauge-title">SEO Score</div>'
      + '<div class="hxi-gauge-grade" style="color:' + col + '">' + grade + '</div>'
      + '<div class="hxi-gauge-desc">' + desc + '</div>'
      + '</div></div>';

    var icons = { ok: '✓', warn: '!', error: '✕', info: 'i' };

    var list = '<div class="hxi-sec">Checks</div><div class="hxi-checks">';
    Object.keys(checks).forEach(function (key) {
      var c    = checks[key];
      var icon = icons[c.status] || 'i';
      var lbl  = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, ' ');
      var valH = c.value ? '<div class="hxi-cval">' + esc(String(c.value).substring(0, 100)) + '</div>' : '';
      var len  = c.length ? ' &middot; ' + c.length + ' chars' : '';
      list += '<div class="hxi-check ' + esc(c.status) + '">'
        + '<i class="hxi-cico">' + icon + '</i>'
        + '<span class="hxi-clbl">' + esc(lbl) + '</span>'
        + '<div class="hxi-cbody">' + valH
        + '<div class="hxi-cnote">' + esc(c.note || '') + len + '</div>'
        + '</div></div>';
    });
    list += '</div>';

    return hero + list;
  }

  var builders = { overview: buildOverview, performance: buildPerformance, queries: buildQueries, views: buildViews, seo: buildSeo };

  /* ─── Tab rendering ────────────────────────────── */
  function openTab(name) {
    active = name;

    tabs.querySelectorAll('.hxi-tab').forEach(function (btn) {
      btn.classList.toggle('active', btn.dataset.tab === name);
    });

    var existing = panes.querySelector('[data-pane="' + name + '"]');

    if (!existing) {
      var div = document.createElement('div');
      div.className = 'hxi-pane';
      div.dataset.pane = name;
      div.innerHTML = (builders[name] || function () { return '<div class="hxi-empty">No data</div>'; })();
      panes.appendChild(div);
      existing = div;

      /* Animate timeline bars */
      requestAnimationFrame(function () {
        div.querySelectorAll('.hxi-tl-bar[data-width]').forEach(function (el) {
          requestAnimationFrame(function () {
            el.style.transition = 'width 0.55s cubic-bezier(0.4,0,0.2,1)';
            el.style.width = el.getAttribute('data-width');
          });
        });

        /* Animate query bars */
        div.querySelectorAll('.hxi-qbar-fill[data-pct]').forEach(function (el) {
          requestAnimationFrame(function () {
            el.style.transition = 'width 0.45s cubic-bezier(0.4,0,0.2,1)';
            el.style.width = el.getAttribute('data-pct') + '%';
          });
        });
      });

      animateArcs(div);
    }

    panes.querySelectorAll('.hxi-pane').forEach(function (p) { p.classList.remove('active'); });
    existing.classList.add('active');
  }

  /* ─── Bottom bar decoration ────────────────────── */
  function decorateTabs() {
    var q = data.queries || {};
    tabs.querySelectorAll('.hxi-tab').forEach(function (btn) {
      if (btn.dataset.tab === 'queries' && q.total) {
        var cls = q.slow > 0 ? 't-err' : q.duplicates > 0 ? 't-warn' : '';
        btn.innerHTML = '🗄 Queries <span class="hxi-badge">' + q.total + '</span>';
        if (cls) btn.classList.add(cls);
      }
    });
  }

  function updateMeta() {
    var m  = data._meta || {};
    var p  = data.performance || {};
    var q  = data.queries || {};
    var rt = (m.render_time || 0) * 1000;
    var pk = m.peak_memory || p.peak_memory || 0;
    var cls = rt < 200 ? 'm-g' : rt < 500 ? 'm-w' : 'm-e';

    meta.innerHTML =
      '<span class="' + cls + '"><span class="hxi-pip"></span>' + fmt(rt) + '</span>'
      + '<span>' + fmtBytes(pk) + '</span>'
      + (q.total ? '<span style="color:var(--c-dim)">' + q.total + ' sql</span>' : '');
  }

  /* ─── Events ───────────────────────────────────── */
  toggle.addEventListener('click', function () {
    open = !open;
    panel.classList.toggle('open', open);
    if (open) openTab(active);
  });

  tabs.addEventListener('click', function (e) {
    var btn = e.target.closest ? e.target.closest('.hxi-tab') : null;
    if (!btn) return;
    if (!open) { open = true; panel.classList.add('open'); }
    openTab(btn.dataset.tab);
  });

  /* ─── Init ─────────────────────────────────────── */
  decorateTabs();
  updateMeta();
  openTab('overview');

  var spacer = document.createElement('div');
  spacer.id = 'hxi-spacer';
  document.body.appendChild(spacer);

})();
