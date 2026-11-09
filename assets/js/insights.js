/* Private insights dashboard: fetches /insights?data=1 and draws everything client-side (no libraries). */
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const SVG = 'http://www.w3.org/2000/svg';
  const state = { days: 30, group: 'day', measure: 'views', repo: '', data: null, sort: ['views', -1] };
  const fmt = (n) => (n >= 10000 ? (n / 1000).toFixed(1) + 'k' : Math.round(n).toLocaleString());
  const tip = $('#tip');

  // ------------------------------------------------------------------ dates and grouping
  const addDays = (iso, n) => { const d = new Date(iso + 'T00:00:00Z'); d.setUTCDate(d.getUTCDate() + n); return d.toISOString().slice(0, 10); };
  const bucket = (iso, g) => {
    if (g === 'month') return iso.slice(0, 7);
    if (g === 'week') { const d = new Date(iso + 'T00:00:00Z'); const wd = (d.getUTCDay() + 6) % 7; return addDays(iso, -wd); }
    return iso;
  };
  const label = (key, g) => {
    if (g === 'month') return new Date(key + '-01T00:00:00Z').toLocaleDateString(undefined, { month: 'short', year: 'numeric', timeZone: 'UTC' });
    const s = new Date(key + 'T00:00:00Z').toLocaleDateString(undefined, { day: 'numeric', month: 'short', timeZone: 'UTC' });
    return g === 'week' ? 'Week of ' + s : s;
  };
  /** rows: [{date, a, b}] -> grouped [{key, a, b}] in order, including empty buckets. */
  function group(rows, g) {
    const out = new Map();
    for (const r of rows) {
      const k = bucket(r.date, g);
      const cur = out.get(k) || { key: k, a: 0, b: 0 };
      cur.a += r.a; cur.b += r.b; out.set(k, cur);
    }
    const list = [...out.values()];
    // the current week or month is still running: say so, or its smaller total reads as a drop
    if (g !== 'day' && list.length) list[list.length - 1].partial = true;
    return list;
  }
  const dateRange = (end, n) => Array.from({ length: n }, (_, i) => addDays(end, i - n + 1));

  // ------------------------------------------------------------------ line chart with crosshair tooltip
  function lineChart(el, rows, series) {
    el.textContent = '';
    const W = el.clientWidth || 800, H = 260, P = { l: 44, r: 16, t: 14, b: 30 };
    const max = Math.max(1, ...rows.flatMap((r) => series.map((s) => r[s.key])));
    const nice = Math.pow(10, Math.floor(Math.log10(max))); const top = Math.ceil(max / nice) * nice;
    const x = (i) => P.l + (rows.length === 1 ? (W - P.l - P.r) / 2 : (i * (W - P.l - P.r)) / (rows.length - 1));
    const y = (v) => H - P.b - (v / top) * (H - P.t - P.b);
    const svg = document.createElementNS(SVG, 'svg');
    svg.setAttribute('viewBox', `0 0 ${W} ${H}`); svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', series.map((s) => s.name).join(' and ') + ' over time');
    const add = (tag, attrs, parent = svg) => { const n = document.createElementNS(SVG, tag); for (const k in attrs) n.setAttribute(k, attrs[k]); parent.appendChild(n); return n; };
    for (let i = 0; i <= 4; i++) {
      const v = (top * i) / 4;
      add('line', { x1: P.l, x2: W - P.r, y1: y(v), y2: y(v), class: 'grid' });
      add('text', { x: P.l - 8, y: y(v) + 4, class: 'axis', 'text-anchor': 'end' }).textContent = fmt(v);
    }
    const every = Math.max(1, Math.ceil(rows.length / 7));
    rows.forEach((r, i) => {
      const last = i === rows.length - 1;
      if ((i % every === 0 && (last || rows.length - 1 - i >= every / 2)) || last) {
        const anchor = rows.length > 1 && last ? 'end' : i === 0 && rows.length > 1 ? 'start' : 'middle';
        add('text', { x: x(i), y: H - 8, class: 'axis', 'text-anchor': anchor }).textContent = r.label;
      }
    });
    series.forEach((s) => {
      const d = rows.map((r, i) => `${i ? 'L' : 'M'}${x(i).toFixed(1)},${y(r[s.key]).toFixed(1)}`).join('');
      if (s.area) add('path', { d: `${d}L${x(rows.length - 1)},${y(0)}L${x(0)},${y(0)}Z`, class: 'area s' + s.slot });
      add('path', { d, class: 'line s' + s.slot });
    });
    const cross = add('line', { y1: P.t, y2: H - P.b, class: 'cross', visibility: 'hidden' });
    const dots = series.map((s) => add('circle', { r: 4.5, class: 'dot s' + s.slot, visibility: 'hidden' }));
    const hit = add('rect', { x: P.l, y: 0, width: W - P.l - P.r, height: H, class: 'hit' });
    const move = (ev) => {
      const box = svg.getBoundingClientRect();
      const px = ((ev.clientX - box.left) / box.width) * W;
      const i = Math.max(0, Math.min(rows.length - 1, Math.round(((px - P.l) / (W - P.l - P.r)) * (rows.length - 1))));
      const r = rows[i];
      cross.setAttribute('x1', x(i)); cross.setAttribute('x2', x(i)); cross.setAttribute('visibility', 'visible');
      dots.forEach((d, k) => { d.setAttribute('cx', x(i)); d.setAttribute('cy', y(r[series[k].key])); d.setAttribute('visibility', 'visible'); });
      showTip(ev, `<b>${r.label}</b>` + series.map((s) => `<div><i class="sw s${s.slot}"></i>${s.name}<span>${fmt(r[s.key])}</span></div>`).join(''));
    };
    hit.addEventListener('pointermove', move);
    hit.addEventListener('pointerleave', () => { hideTip(); cross.setAttribute('visibility', 'hidden'); dots.forEach((d) => d.setAttribute('visibility', 'hidden')); });
    el.appendChild(svg);
  }

  function legend(el, series) {
    el.textContent = '';
    for (const s of series) {
      const span = document.createElement('span');
      span.innerHTML = `<i class="sw s${s.slot}"></i>`;
      span.append(s.name);
      el.appendChild(span);
    }
  }

  function showTip(ev, html) {
    tip.innerHTML = html; tip.hidden = false;
    const w = tip.offsetWidth, h = tip.offsetHeight;
    let left = ev.clientX + 14, top = ev.clientY + 14;
    if (left + w > innerWidth - 8) left = ev.clientX - w - 14;
    if (top + h > innerHeight - 8) top = ev.clientY - h - 14;
    tip.style.left = left + 'px'; tip.style.top = top + 'px';
  }
  function hideTip() { tip.hidden = true; }

  // ------------------------------------------------------------------ bar lists and tables
  function bars(el, map, limit = 8, unit = '') {
    el.textContent = '';
    const entries = Object.entries(map || {}).sort((a, b) => b[1] - a[1]).slice(0, limit);
    if (!entries.length) { el.innerHTML = '<p class="muted small">No data yet.</p>'; return; }
    const max = entries[0][1];
    const total = Object.values(map).reduce((a, b) => a + b, 0);
    for (const [k, v] of entries) {
      const row = document.createElement('div'); row.className = 'barrow';
      const name = document.createElement('span'); name.className = 'k'; name.textContent = k; name.title = k;
      const track = document.createElement('span'); track.className = 'track';
      const fill = document.createElement('span'); fill.className = 'fill'; fill.style.width = Math.max(2, (v / max) * 100) + '%';
      track.appendChild(fill);
      const val = document.createElement('span'); val.className = 'v'; val.textContent = fmt(v) + unit;
      row.append(name, track, val);
      row.addEventListener('pointermove', (ev) => showTip(ev, `<b>${escapeHtml(k)}</b><div>${fmt(v)}<span>${((v / total) * 100).toFixed(1)}%</span></div>`));
      row.addEventListener('pointerleave', hideTip);
      el.appendChild(row);
    }
  }
  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function table(el, cols, rows, opts = {}) {
    el.textContent = '';
    const t = document.createElement('table');
    const head = t.createTHead().insertRow();
    cols.forEach((c) => {
      const th = document.createElement('th'); th.textContent = c.name; th.className = c.num ? 'num' : '';
      if (opts.sortable) {
        th.tabIndex = 0; th.classList.add('sortable');
        if (state.sort[0] === c.key) th.setAttribute('aria-sort', state.sort[1] > 0 ? 'ascending' : 'descending');
        const go = () => { state.sort = [c.key, state.sort[0] === c.key ? -state.sort[1] : -1]; opts.onSort(); };
        th.addEventListener('click', go); th.addEventListener('keydown', (e) => e.key === 'Enter' && go());
      }
      head.appendChild(th);
    });
    const body = t.createTBody();
    rows.forEach((r) => {
      const tr = body.insertRow();
      cols.forEach((c) => { const td = tr.insertCell(); td.textContent = c.fmt ? c.fmt(r[c.key], r) : r[c.key]; if (c.num) td.className = 'num'; });
      if (opts.onRow) { tr.tabIndex = 0; tr.classList.add('pick'); if (opts.selected === r.name) tr.classList.add('on');
        tr.addEventListener('click', () => opts.onRow(r)); tr.addEventListener('keydown', (e) => e.key === 'Enter' && opts.onRow(r)); }
    });
    el.appendChild(t);
  }

  function tiles(el, items) {
    el.textContent = '';
    for (const it of items) {
      const d = document.createElement('div'); d.className = 'tile';
      const b = document.createElement('b'); b.textContent = it.value;
      const s = document.createElement('span'); s.textContent = it.label;
      d.append(b, s);
      if (it.delta !== undefined && isFinite(it.delta)) {
        if (Math.abs(it.delta) < 0.5) it.delta = 0;
        const c = document.createElement('em');
        c.className = it.delta > 0 ? 'up' : it.delta < 0 ? 'down' : '';
        c.textContent = (it.delta > 0 ? '▲ ' : it.delta < 0 ? '▼ ' : '') + Math.abs(it.delta).toFixed(0) + '% vs previous ' + state.days + ' days';
        d.appendChild(c);
      }
      el.appendChild(d);
    }
  }
  const pct = (cur, prev) => (prev ? ((cur - prev) / prev) * 100 : cur ? Infinity : 0);
  const sum = (rows, k) => rows.reduce((a, r) => a + (r[k] || 0), 0);
  const mergeMaps = (rows, k) => rows.reduce((acc, r) => { for (const [key, v] of Object.entries(r[k] || {})) acc[key] = (acc[key] || 0) + v; return acc; }, {});

  // ------------------------------------------------------------------ portfolio panel
  function renderSite() {
    const all = state.data.site.days;
    const cur = all.slice(-state.days), prev = all.slice(0, -state.days).slice(-state.days);
    const v = sum(cur, 'views'), u = sum(cur, 'visitors'), c = sum(cur, 'clicks');
    const es = sum(cur, 'engaged_s'), en = sum(cur, 'engaged_n');
    const pes = sum(prev, 'engaged_s'), pen = sum(prev, 'engaged_n');
    tiles($('#site-tiles'), [
      { value: fmt(v), label: 'page views', delta: pct(v, sum(prev, 'views')) },
      { value: fmt(u), label: 'unique visitors (daily, summed)', delta: pct(u, sum(prev, 'visitors')) },
      { value: fmt(c), label: 'clicks on links', delta: pct(c, sum(prev, 'clicks')) },
      { value: en ? Math.round(es / en) + ' s' : '–', label: 'average time on a page', delta: en && pen ? pct(es / en, pes / pen) : undefined },
    ]);
    const rows = group(cur.map((d) => ({ date: d.date, a: d.views, b: d.visitors })), state.group)
      .map((r) => ({ label: label(r.key, state.group) + (r.partial ? ' (so far)' : ''), views: r.a, visitors: r.b }));
    const series = [{ key: 'views', name: 'Page views', slot: 1, area: true }, { key: 'visitors', name: 'Unique visitors', slot: 2 }];
    legend($('#site-legend'), series);
    lineChart($('#site-chart'), rows, series);
    bars($('#site-pages'), mergeMaps(cur, 'pages'));
    const refs = mergeMaps(cur, 'referrers'); delete refs['(internal)'];
    bars($('#site-refs'), refs);
    bars($('#site-clicks'), mergeMaps(cur, 'click_targets'));
    const t = state.data.site.totals;
    bars($('#site-devices'), t.devices, 5); bars($('#site-browsers'), t.browsers, 6); bars($('#site-regions'), t.regions, 8);
    table($('#site-table'), [{ key: 'label', name: 'Period' }, { key: 'views', name: 'Views', num: true, fmt }, { key: 'visitors', name: 'Visitors', num: true, fmt }], rows.slice().reverse());
  }

  // ------------------------------------------------------------------ GitHub panel
  function repoRows(repo, measure, dates) {
    const hist = (repo && repo[measure]) || {};
    return dates.map((d) => ({ date: d, a: (hist[d] || [0, 0])[0], b: (hist[d] || [0, 0])[1] }));
  }
  function renderGithub() {
    const gh = state.data.github; const repos = gh.repos || {};
    const note = $('#gh-note');
    const names = Object.keys(repos).sort();
    if (!names.length) {
      note.hidden = false;
      note.textContent = state.data.token ? (gh.error || 'Collecting… refresh in a minute.') : 'Add a GitHub token in Settings to collect repository traffic (views, visitors, clones). GitHub keeps only 14 days, so history builds up from the day the token is saved.';
    } else { note.hidden = !gh.error; note.textContent = gh.error; }
    const sel = $('#gh-repo');
    if (sel.options.length !== names.length + 1) {
      sel.length = 1; names.forEach((n) => sel.add(new Option(n, n)));
    }
    sel.value = state.repo;
    const end = state.data.now;
    const dates = dateRange(end, state.days), prevDates = dateRange(addDays(end, -state.days), state.days);
    const total = (measure, ds, only) => {
      const acc = { a: 0, b: 0 };
      for (const n of names) { if (only && n !== only) continue; for (const r of repoRows(repos[n], measure, ds)) { acc.a += r.a; acc.b += r.b; } }
      return acc;
    };
    const tv = total('views', dates), tc = total('clones', dates), pv = total('views', prevDates), pc = total('clones', prevDates);
    const stars = names.reduce((a, n) => a + (repos[n].stars || 0), 0);
    tiles($('#gh-tiles'), [
      { value: fmt(tv.a), label: 'repository views', delta: pct(tv.a, pv.a) },
      { value: fmt(tv.b), label: 'unique visitors (daily, summed)', delta: pct(tv.b, pv.b) },
      { value: fmt(tc.a), label: 'clones', delta: pct(tc.a, pc.a) },
      { value: fmt(tc.b), label: 'unique cloners (daily, summed)', delta: pct(tc.b, pc.b) },
      { value: fmt(stars), label: 'stars across ' + names.length + ' repositories' },
    ]);
    // chart: one repository or all
    const raw = dates.map((d) => ({ date: d, a: 0, b: 0 }));
    for (const n of names) {
      if (state.repo && n !== state.repo) continue;
      repoRows(repos[n], state.measure, dates).forEach((r, i) => { raw[i].a += r.a; raw[i].b += r.b; });
    }
    const rows = group(raw, state.group).map((r) => ({ label: label(r.key, state.group) + (r.partial ? ' (so far)' : ''), count: r.a, uniques: r.b }));
    const series = state.measure === 'views'
      ? [{ key: 'count', name: 'Views', slot: 1, area: true }, { key: 'uniques', name: 'Unique visitors', slot: 2 }]
      : [{ key: 'count', name: 'Clones', slot: 1, area: true }, { key: 'uniques', name: 'Unique cloners', slot: 2 }];
    legend($('#gh-legend'), series);
    lineChart($('#gh-chart'), rows, series);
    // repository table
    const tableRows = names.map((n) => {
      const v = repoRows(repos[n], 'views', dates), c = repoRows(repos[n], 'clones', dates);
      return { name: n, views: sum(v, 'a'), visitors: sum(v, 'b'), clones: sum(c, 'a'), cloners: sum(c, 'b'), stars: repos[n].stars || 0, forks: repos[n].forks || 0 };
    });
    const [k, dir] = state.sort;
    tableRows.sort((a, b) => (typeof a[k] === 'string' ? a[k].localeCompare(b[k]) : a[k] - b[k]) * dir);
    table($('#gh-table'), [
      { key: 'name', name: 'Repository' }, { key: 'views', name: 'Views', num: true, fmt }, { key: 'visitors', name: 'Visitors', num: true, fmt },
      { key: 'clones', name: 'Clones', num: true, fmt }, { key: 'cloners', name: 'Cloners', num: true, fmt },
      { key: 'stars', name: 'Stars', num: true, fmt }, { key: 'forks', name: 'Forks', num: true, fmt },
    ], tableRows, { sortable: true, onSort: renderGithub, selected: state.repo,
      onRow: (r) => { state.repo = state.repo === r.name ? '' : r.name; renderGithub(); $('#gh-chart').scrollIntoView({ behavior: 'smooth', block: 'center' }); } });
    // referrers and paths (GitHub only gives the last 14 days)
    const refs = {}, paths = {};
    for (const n of names) {
      if (state.repo && n !== state.repo) continue;
      for (const r of repos[n].referrers || []) refs[r.source] = (refs[r.source] || 0) + r.count;
      for (const p of repos[n].paths || []) { const key = p.path.replace('/codeROCK-IZZY/', ''); paths[key] = (paths[key] || 0) + p.count; }
    }
    bars($('#gh-refs'), refs); bars($('#gh-paths'), paths);
  }

  // ------------------------------------------------------------------ wiring
  function render() {
    if (!state.data) return;
    $('#token-hint').textContent = state.data.token || 'none';
    renderSite(); renderGithub();
  }
  async function load(refresh = false) {
    document.body.classList.add('loading');
    try {
      const res = await fetch(`/insights?data=1&days=${Math.min(400, state.days * 2)}${refresh ? '&refresh=1' : ''}`, { credentials: 'same-origin', cache: 'no-store' });
      if (res.status !== 200 || !(res.headers.get('content-type') || '').includes('json')) { location.reload(); return; }
      state.data = await res.json();
      render();
    } finally { document.body.classList.remove('loading'); }
  }
  $$('[data-tab]').forEach((b) => b.addEventListener('click', () => {
    $$('[data-tab]').forEach((x) => x.setAttribute('aria-selected', String(x === b)));
    $$('[data-panel]').forEach((p) => { p.hidden = p.dataset.panel !== b.dataset.tab; });
    history.replaceState(null, '', '#' + b.dataset.tab);
    render();
  }));
  const radio = (attr, key) => $$(`[data-${attr}]`).forEach((b) => b.addEventListener('click', () => {
    $$(`[data-${attr}]`).forEach((x) => x.setAttribute('aria-checked', String(x === b)));
    state[key] = b.dataset[attr]; render();
  }));
  radio('group', 'group'); radio('measure', 'measure');
  $('#range').addEventListener('change', (e) => { state.days = +e.target.value; if (state.days >= 180 && state.group === 'day') $('[data-group="week"]').click(); load(); });
  $('#gh-repo').addEventListener('change', (e) => { state.repo = e.target.value; renderGithub(); });
  $('#refresh').addEventListener('click', () => load(true));
  let rs; addEventListener('resize', () => { clearTimeout(rs); rs = setTimeout(render, 150); });
  const tab = location.hash.slice(1); if (tab) { const b = $(`[data-tab="${tab}"]`); if (b) b.click(); }
  load();
})();
