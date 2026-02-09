// app/static/js/financeiro_relatorios.js
(function(){
  const root = document.getElementById('frPage');
  if(!root) return;

  const mock = window.__FR_MOCK__ || {};
  const DEFAULT_FAV_LIMIT = 4;
  const FAV_LIMIT = Number(mock.fav_limit || DEFAULT_FAV_LIMIT);
  const LS_KEY = 'fin_reports_favorites_v1';

  // Elements
  const periodEl = document.getElementById('frPeriod');
  const typeEl   = document.getElementById('frType');
  const searchEl = document.getElementById('frSearch');
  const btnClear = document.getElementById('frClear');
  const btnRun   = document.getElementById('frRun');

  const favGrid  = document.getElementById('frFavGrid');
  const favEmpty = document.getElementById('frFavEmpty');
  const toastEl  = document.getElementById('frToast');

  // Quick charts containers
  const quickTop = document.getElementById('frQuickTop');
  const quickMid = document.getElementById('frQuickMid');
  const quickBottom = document.getElementById('frQuickBottom');

  // Modal
  const modal    = document.getElementById('frModal');
  const modalClose = document.getElementById('frModalClose');
  const modalTitle = document.getElementById('frModalTitle');
  const modalRun = document.getElementById('frModalRun');
  const modalExport = document.getElementById('frModalGhost');
  const modalPrint = document.getElementById('frModalPrint');

  // Print/preview elements
  const printGeneratedAt = document.getElementById('frPrintGeneratedAt');
  const printPeriod = document.getElementById('frPrintPeriod');
  const printType = document.getElementById('frPrintType');
  const printTitle = document.getElementById('frPrintTitle');
  const printDesc = document.getElementById('frPrintDesc');
  const printKpis = document.getElementById('frPrintKpis');

  const chartSection = document.getElementById('frPrintChartSection');
  const chartCanvas = document.getElementById('frPrintChart');
  const chartImg = document.getElementById('frPrintChartImg');
  const chartHint = document.getElementById('frPrintChartHint');

  const chartSumEl = document.getElementById('frPrintChartSum');
  const chartTotalEl = document.getElementById('frPrintChartTotal');

  const theadEl = document.getElementById('frPrintThead');
  const tbodyEl = document.getElementById('frPrintTbody');
  const footnoteEl = document.getElementById('frPrintFootnote');

  // Filters wrap (mobile accordion)
  const wrap = document.getElementById('frFiltersWrap');
  const toggle = document.getElementById('frFiltersToggle');
  const icon = document.getElementById('frFiltersIcon');

  // State
  let favorites = loadFavorites();
  let lastOpened = null;
  let lastExec = null;

  // -----------------------------
  // Helpers
  // -----------------------------
  function showToast(msg, kind){
    try{
      if(window.Toast){
        if(kind && typeof window.Toast[kind] === 'function'){
          window.Toast[kind](msg);
          return;
        }
        if(typeof window.Toast.show === 'function'){
          window.Toast.show(msg);
          return;
        }
      }
    }catch(_){}

    if(!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add('is-on');
    clearTimeout(showToast._t);
    showToast._t = setTimeout(() => toastEl.classList.remove('is-on'), 2600);
  }

  function loadFavorites(){
    try{
      const raw = localStorage.getItem(LS_KEY);
      if(raw){
        const arr = JSON.parse(raw);
        if(Array.isArray(arr)) return arr.slice(0, FAV_LIMIT);
      }
    }catch(e){}
    return Array.isArray(mock.favorites) ? mock.favorites.slice(0, FAV_LIMIT) : [];
  }

  function saveFavorites(){
    try{
      localStorage.setItem(LS_KEY, JSON.stringify(favorites.slice(0, FAV_LIMIT)));
    }catch(e){}
  }

  function uniq(arr){
    const out = [];
    const set = new Set();
    (arr||[]).forEach(x => {
      const k = String(x||'').trim();
      if(!k) return;
      if(set.has(k)) return;
      set.add(k);
      out.push(k);
    });
    return out;
  }

  function cleanupFavorites(){
    const valid = new Set(Array.from(root.querySelectorAll('.fin-rep-card[data-report-id]'))
      .map(c => c.getAttribute('data-report-id'))
      .filter(Boolean)
    );
    favorites = uniq(favorites).filter(id => valid.has(id)).slice(0, FAV_LIMIT);
    saveFavorites();
  }

  function isFav(id){ return favorites.indexOf(id) !== -1; }

  function setFav(id, on){
    id = String(id||'').trim();
    if(!id) return false;

    cleanupFavorites();

    if(on){
      if(isFav(id)) return true;
      if(favorites.length >= FAV_LIMIT){
        showToast(`Limite de favoritos atingido (${FAV_LIMIT}). Remova um favorito para adicionar outro.`, 'warning');
        return false;
      }
      favorites.push(id);
      favorites = uniq(favorites).slice(0, FAV_LIMIT);
      saveFavorites();
      showToast('Adicionado aos favoritos.', 'success');
      return true;
    }else{
      const idx = favorites.indexOf(id);
      if(idx !== -1){
        favorites.splice(idx, 1);
        saveFavorites();
        showToast('Removido dos favoritos.', 'warning');
      }
      return true;
    }
  }

  function normalizeStr(s){ return String(s || '').toLowerCase().trim(); }
  function pad2(n){ return String(n).padStart(2, '0'); }

  function toBRddmmyyyy(iso){
    if(!iso || typeof iso !== 'string') return '';
    if(/^\d{2}\/\d{2}\/\d{4}$/.test(iso)) return iso;
    if(iso.length >= 10 && iso[4] === '-' && iso[7] === '-') return iso.slice(8,10) + '/' + iso.slice(5,7) + '/' + iso.slice(0,4);
    return '';
  }

  function moneyBR(v){
    const n = Number(v || 0);
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function parseIsoToDate(iso){
    const s = String(iso || '').trim();
    if(!s) return null;

    if(/^\d{2}\/\d{2}\/\d{4}$/.test(s)){
      const [dd, mm, yyyy] = s.split('/').map(Number);
      const d = new Date(yyyy, (mm||1)-1, dd||1);
      return Number.isNaN(d.getTime()) ? null : d;
    }
    if(s.length >= 10 && s[4] === '-' && s[7] === '-'){
      const d = new Date(s.slice(0,10) + 'T00:00:00');
      return Number.isNaN(d.getTime()) ? null : d;
    }

    const d2 = new Date(s);
    return Number.isNaN(d2.getTime()) ? null : d2;
  }

  function todayAtMidnight(){
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
  }

  function safeTime(iso){
    const d = parseIsoToDate(iso);
    return d ? d.getTime() : Number.POSITIVE_INFINITY;
  }

  function loadArr(key){
    try{
      const raw = localStorage.getItem(key);
      if(!raw) return [];
      const arr = JSON.parse(raw);
      return Array.isArray(arr) ? arr : [];
    }catch(_){
      return [];
    }
  }

  function getPeriodLabel(){
    if(!periodEl) return '—';
    const opt = periodEl.options[periodEl.selectedIndex];
    return opt ? opt.text : '—';
  }

  function getTypeLabel(){
    if(!typeEl) return 'Todos';
    const opt = typeEl.options[typeEl.selectedIndex];
    return opt && opt.text ? opt.text : 'Todos';
  }

  function getPeriodRange(periodKey){
    const today = todayAtMidnight();
    const y = today.getFullYear();
    const m = today.getMonth();

    if(periodKey === 'last_30'){
      const start = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 29);
      const end = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
      return { start, end, mode: 'range' };
    }

    if(periodKey === 'last_month'){
      const start = new Date(y, m - 1, 1);
      const end = new Date(y, m, 1);
      return { start, end, mode: 'range' };
    }

    if(periodKey === 'custom'){
      const start = new Date(y, m, 1);
      const end = new Date(y, m + 1, 1);
      return { start, end, mode: 'range', warned: true };
    }

    const start = new Date(y, m, 1);
    const end = new Date(y, m + 1, 1);
    return { start, end, mode: 'range' };
  }

  function inRange(iso, start, end){
    const d = parseIsoToDate(iso);
    if(!d) return false;
    return d >= start && d < end;
  }

  function escapeCsv(v){
    const s = String(v ?? '');
    const needs = /["\n\r,;]/.test(s);
    const out = s.replace(/"/g, '""');
    return needs ? '"' + out + '"' : out;
  }

  function escapeHtml(s){
    return String(s ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function getFilterState(){
    return {
      period: periodEl ? periodEl.value : 'this_month',
      type: typeEl ? typeEl.value : '',
      search: normalizeStr(searchEl ? searchEl.value : ''),
    };
  }

  function matchesFilters(card, st){
    const group = (card.getAttribute('data-group') || '').toLowerCase();
    const name  = normalizeStr(card.getAttribute('data-name'));
    const desc  = normalizeStr(card.getAttribute('data-desc'));
    const tags  = normalizeStr(card.getAttribute('data-tags'));

    if(st.type && group !== st.type) return false;

    if(st.search){
      const hay = `${name} ${desc} ${tags} ${group}`;
      if(hay.indexOf(st.search) === -1) return false;
    }
    return true;
  }

  function applyFilters(){
    const st = getFilterState();
    const cards = root.querySelectorAll('.fin-rep-card[data-report-id]');
    let shown = 0;
    cards.forEach(card => {
      const ok = matchesFilters(card, st);
      card.style.display = ok ? '' : 'none';
      if(ok) shown++;
    });
    return shown;
  }

  function updateStarButtons(){
    const buttons = root.querySelectorAll('.fin-rep-card__fav');
    buttons.forEach(btn => {
      const card = btn.closest('.fin-rep-card');
      if(!card) return;
      const id = card.getAttribute('data-report-id');
      const on = isFav(id);
      btn.classList.toggle('is-on', on);
      btn.setAttribute('aria-label', on ? 'Remover dos favoritos' : 'Adicionar aos favoritos');
      btn.setAttribute('title', on ? 'Favorito' : 'Favoritar');
    });
  }

  function renderFavorites(){
    if(!favGrid) return;

    cleanupFavorites();

    favGrid.innerHTML = '';

    const allCards = Array.from(root.querySelectorAll('.fin-rep-card[data-report-id]'));
    const byId = new Map();
    allCards.forEach(c => byId.set(c.getAttribute('data-report-id'), c));

    favorites.slice(0, FAV_LIMIT).forEach(id => {
      const src = byId.get(id);
      if(!src) return;

      const clone = src.cloneNode(true);
      clone.style.display = '';

      const favBtn = clone.querySelector('.fin-rep-card__fav');
      if(favBtn){
        favBtn.classList.add('is-on');
        favBtn.setAttribute('aria-label', 'Remover dos favoritos');
        favBtn.setAttribute('title', 'Favorito');
      }
      favGrid.appendChild(clone);
    });

    if(favEmpty){
      favEmpty.hidden = favorites.length !== 0;
    }
  }

  // -----------------------------
  // Data (localStorage) - NORMALIZAÇÃO
  // -----------------------------
  const LS_CP = 'fin_cp_rows_v1';
  const LS_CR = 'fin_cr_rows_v1';

  function normalizeRowDate(r){
    if(!r || typeof r !== 'object') return null;
    const data = r.data ?? r.vencimento ?? r.date ?? r.dt ?? null;
    if(!data) return null;
    const out = Object.assign({}, r);
    out.data = String(data);
    return out;
  }

  function readData(){
    const cp = loadArr(LS_CP).map(normalizeRowDate).filter(r => r && r.data);
    const cr = loadArr(LS_CR).map(normalizeRowDate).filter(r => r && r.data);
    return { cp, cr };
  }

  function sumBy(list, keyFn, valFn){
    const out = new Map();
    list.forEach(it => {
      const k = String(keyFn(it) || '—').trim() || '—';
      const v = Number(valFn(it) || 0);
      out.set(k, (out.get(k) || 0) + v);
    });
    return out;
  }

  function sortTop(map, limit){
    const arr = Array.from(map.entries()).map(([k,v]) => ({ label:k, value:Number(v||0) }));
    arr.sort((a,b) => (b.value||0) - (a.value||0));
    if(limit && arr.length > limit) return arr.slice(0, limit);
    return arr;
  }

  // -----------------------------
  // Limites de exibição (padrão)
  // - aplica SOMENTE aos dados do gráfico (legend/summary)
  // - a tabela do relatório permanece completa (sem "Outros")
  // -----------------------------
  const CHART_TOP_N = 8;

  function limitSeriesWithOthers(series, maxItems, othersBaseLabel = 'Outros'){
    const arr = Array.isArray(series) ? series.slice() : [];
    const max = Math.max(1, Number(maxItems || 0));

    if(arr.length <= max) return arr;

    // 1 slot reservado para "Outros"
    const keep = Math.max(1, max - 1);

    const head = arr.slice(0, keep);
    const tail = arr.slice(keep);

    const restSum = tail.reduce((a,b) => a + Number(b?.value || 0), 0);
    const restCount = tail.length;

    const label = `${othersBaseLabel} (${restCount})`;
    head.push({ label, value: restSum });

    return head;
  }

  const PAL = [
    'rgba(59,130,246,.75)',
    'rgba(16,185,129,.75)',
    'rgba(245,158,11,.75)',
    'rgba(239,68,68,.75)',
    'rgba(168,85,247,.75)',
    'rgba(14,165,233,.75)',
    'rgba(234,88,12,.75)',
    'rgba(20,184,166,.75)',
    'rgba(100,116,139,.75)',
    'rgba(236,72,153,.70)',
  ];

  function colors(n){
    const out = [];
    for(let i=0;i<n;i++) out.push(PAL[i % PAL.length]);
    return out;
  }

  function pctOf(value, total){
    const v = Number(value||0);
    const t = Number(total||0);
    if(t <= 0) return 0;
    return (v / t) * 100;
  }

  function applyTooltipMoneyAndPct(cfg){
    if(!cfg || !cfg.options) cfg.options = {};
    if(!cfg.options.plugins) cfg.options.plugins = {};
    if(!cfg.options.plugins.tooltip) cfg.options.plugins.tooltip = {};

    cfg.options.plugins.tooltip.callbacks = cfg.options.plugins.tooltip.callbacks || {};
    cfg.options.plugins.tooltip.callbacks.label = function(ctx){
      const label = ctx.label || '';
      const raw = (ctx.raw != null) ? Number(ctx.raw) : Number(ctx.parsed || 0);
      const ds = ctx.dataset || {};
      const data = Array.isArray(ds.data) ? ds.data : [];
      const total = data.reduce((a,b) => a + Number(b||0), 0);
      const pct = pctOf(raw, total);
      const pctTxt = total > 0 ? ` (${pct.toFixed(1).replace('.', ',')}%)` : '';
      return `${label}: ${moneyBR(raw)}${pctTxt}`;
    };

    return cfg;
  }

  function clearNode(n){
    if(!n) return;
    n.innerHTML = '';
  }

  // -----------------------------
  // Chart card (página)
  // -----------------------------
  function renderChartCard(container, opt){
    if(!container) return;

    const id = opt.id;
    const wide = !!opt.wide;
    const stack = !!opt.stack;

    const card = document.createElement('div');
    card.className = 'ui-chart-card' + (wide ? ' is-wide' : '') + (stack ? ' is-stack' : '');
    card.innerHTML = `
      <div class="ui-chart-head">
        <div class="ui-chart-title">${escapeHtml(opt.title || 'Gráfico')}</div>
        <div class="ui-chart-sub">${escapeHtml(opt.sub || '')}</div>
      </div>

      <div class="ui-chart-body">
        <div class="ui-chart-plot ${opt.compact ? 'is-compact' : ''}">
          <canvas id="${escapeHtml(id)}_cv" aria-label="${escapeHtml(opt.title||'Gráfico')}"></canvas>
        </div>
        <div class="ui-chart-sum">
          <div class="ui-chart-sumtitle">Dados</div>
          <div class="ui-chart-grid" id="${escapeHtml(id)}_sum"></div>
          <div class="ui-chart-hint" id="${escapeHtml(id)}_hint" style="display:none;">Chart.js não disponível nesta etapa.</div>
        </div>
      </div>
    `;
    container.appendChild(card);

    const cv = card.querySelector(`#${CSS.escape(id)}_cv`);
    const sumEl = card.querySelector(`#${CSS.escape(id)}_sum`);
    const hintEl = card.querySelector(`#${CSS.escape(id)}_hint`);

    const series = Array.isArray(opt.series) ? opt.series : [];
    const total = series.reduce((a,b)=>a+Number(b.value||0),0);

    sumEl.innerHTML = series.slice(0, opt.maxRows || CHART_TOP_N).map(r => {
      const pctTxt = total > 0 ? (pctOf(r.value, total)).toFixed(1).replace('.', ',') + '%' : '0,0%';
      return `
        <div class="ui-chart-row">
          <div class="ui-chart-label">${escapeHtml(r.label)}</div>
          <div class="ui-chart-val">${escapeHtml(moneyBR(r.value))}</div>
          <div class="ui-chart-pct">${escapeHtml(pctTxt)}</div>
        </div>
      `;
    }).join('');

    if(!window.Chart || !cv){
      if(hintEl) hintEl.style.display = 'block';
      return;
    }

    const ctx = cv.getContext('2d');

    try{
      if(renderChartCard._instances && renderChartCard._instances[id]){
        renderChartCard._instances[id].destroy();
      }
    }catch(_){}

    try{
      renderChartCard._instances = renderChartCard._instances || {};
      renderChartCard._instances[id] = new window.Chart(ctx, opt.chartCfg);
    }catch(_){}
  }

  // -----------------------------
  // Visão rápida (página)
  // -----------------------------
  function last12MonthKeys(){
    const today = todayAtMidnight();
    const out = [];
    for(let i=11;i>=0;i--){
      const d = new Date(today.getFullYear(), today.getMonth()-i, 1);
      const mk = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0');
      out.push(mk);
    }
    return out;
  }

  function buildQuickCharts(){
    const st = getFilterState();
    const { start, end, warned } = getPeriodRange(st.period);
    const { cp, cr } = readData();

    if(warned){
      showToast('Período personalizado ainda não está disponível; usando "Este mês".', 'warning');
    }

    const cpIn = cp.filter(r => inRange(r.data, start, end));
    const crIn = cr.filter(r => inRange(r.data, start, end));
    const asVal = (x) => Number(x && x.valor != null ? x.valor : 0);

    // TOP: imóveis + categorias
    const byImovel = sumBy(cpIn, r => (r.imovel || '—'), asVal);
    let imSeriesAll = sortTop(byImovel);
    let imSeries = limitSeriesWithOthers(imSeriesAll, CHART_TOP_N, 'Outros');
    const imCfg = applyTooltipMoneyAndPct({
      type: 'doughnut',
      data: {
        labels: imSeries.map(x => x.label),
        datasets: [{ data: imSeries.map(x => x.value), backgroundColor: colors(imSeries.length), borderWidth: 0 }],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } }, cutout: '58%' },
    });

    const byCat = sumBy(cpIn, r => (r.categoria || '—'), asVal);
    let catSeriesAll = sortTop(byCat);
    let catSeries = limitSeriesWithOthers(catSeriesAll, CHART_TOP_N, 'Outros');
    const catCfg = applyTooltipMoneyAndPct({
      type: 'doughnut',
      data: {
        labels: catSeries.map(x => x.label),
        datasets: [{ data: catSeries.map(x => x.value), backgroundColor: colors(catSeries.length), borderWidth: 0 }],
      },
      options: { responsive: true, plugins: { legend: { position: 'bottom' } }, cutout: '58%' },
    });

    if(quickTop){
      clearNode(quickTop);
      renderChartCard(quickTop, {
        id: 'fr_q_imoveis',
        title: 'Despesas por Imóvel',
        sub: `Período: ${getPeriodLabel()}`,
        compact: true,
        wide: false,
        series: imSeries,
        chartCfg: imCfg,
        maxRows: CHART_TOP_N,
      });
      renderChartCard(quickTop, {
        id: 'fr_q_categorias',
        title: 'Despesas por Categoria',
        sub: `Período: ${getPeriodLabel()}`,
        compact: true,
        wide: false,
        series: catSeries,
        chartCfg: catCfg,
        maxRows: CHART_TOP_N,
      });
    }

    // MID: 12m + status
    if(quickMid){
      clearNode(quickMid);

      const keys12 = last12MonthKeys();
      const bucket12 = {};
      keys12.forEach(k => bucket12[k] = { in:0, out:0 });

      const addMonth = (iso, kind, val) => {
        const d = parseIsoToDate(iso);
        if(!d) return;
        const mk = d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0');
        if(!bucket12[mk]) return;
        if(kind === 'in') bucket12[mk].in += val;
        else bucket12[mk].out += val;
      };

      cp.forEach(p => addMonth(p.data, 'out', asVal(p)));
      cr.forEach(r => addMonth(r.data, 'in', asVal(r)));

      const in12 = keys12.map(k => bucket12[k].in);
      const out12 = keys12.map(k => bucket12[k].out);

      const histSeries = [
        { label:'Entradas (12m)', value: in12.reduce((a,b)=>a+Number(b||0),0) },
        { label:'Saídas (12m)', value: out12.reduce((a,b)=>a+Number(b||0),0) },
      ];

      const histCfg = {
        type:'bar',
        data:{
          labels: keys12,
          datasets:[
            { label:'Entradas', data: in12, backgroundColor:'rgba(16,185,129,.70)' },
            { label:'Saídas', data: out12, backgroundColor:'rgba(239,68,68,.65)' },
          ]
        },
        options:{
          responsive:true,
          plugins:{
            legend:{ position:'bottom' },
            tooltip:{ callbacks:{ label:(ctx)=> `${ctx.dataset.label}: ${moneyBR(ctx.parsed.y)}` } }
          }
        }
      };

      renderChartCard(quickMid, {
        id: 'fr_q_hist12',
        title: 'Entradas x Saídas — últimos 12 meses',
        sub: 'Histórico mensal (12m)',
        compact: false,
        wide: true,
        stack: true,
        series: histSeries,
        chartCfg: histCfg,
        maxRows: CHART_TOP_N,
      });

      const sumStatus = (list, stt) => list.reduce((acc, r) => acc + (r.status === stt ? asVal(r) : 0), 0);
      const pagarOpen = sumStatus(cpIn, 'open');
      const pagarDone = sumStatus(cpIn, 'done');
      const recOpen = sumStatus(crIn, 'open');
      const recDone = sumStatus(crIn, 'done');

      const statusSeries = [
        { label:'Pagar • Pendente', value: pagarOpen },
        { label:'Pagar • Concluído', value: pagarDone },
        { label:'Receber • Pendente', value: recOpen },
        { label:'Receber • Concluído', value: recDone },
      ];

      const statusCfg = {
        type: 'bar',
        data: {
          labels: ['Pagar', 'Receber'],
          datasets: [
            { label: 'Pendente', data: [pagarOpen, recOpen], backgroundColor: 'rgba(245,158,11,.70)' },
            { label: 'Concluído', data: [pagarDone, recDone], backgroundColor: 'rgba(59,130,246,.70)' },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${moneyBR(ctx.parsed.y)}` } }
          },
          scales: { x: { stacked: true }, y: { stacked: true } },
        },
      };

      renderChartCard(quickMid, {
        id: 'fr_q_status',
        title: 'Status (Pendente x Concluído)',
        sub: `Totais no período: ${getPeriodLabel()}`,
        compact: false,
        wide: false,
        stack: false,
        series: statusSeries,
        chartCfg: statusCfg,
        maxRows: CHART_TOP_N,
      });
    }

    // BOTTOM: vencimentos + fixas
    if(quickBottom){
      clearNode(quickBottom);

      const openEvents = [];
      cpIn.forEach(p => { if(p.status === 'open') openEvents.push({ kind:'Pagar', title:p.conta||'—', data:p.data, valor:asVal(p) }); });
      crIn.forEach(r => { if(r.status === 'open') openEvents.push({ kind:'Receber', title:(r.cliente||'—'), data:r.data, valor:asVal(r) }); });
      openEvents.sort((a,b) => safeTime(a.data) - safeTime(b.data));
      const topEv = openEvents.slice(0, 10);

      const evSeriesFull = topEv.map(x => ({
        label: `${x.kind}: ${toBRddmmyyyy(x.data)} • ${x.title}`,
        value: x.valor
      }));

      const evSeries = limitSeriesWithOthers(evSeriesFull, CHART_TOP_N, 'Outros');

      const vencLabels = topEv.map(x => `${x.kind} ${toBRddmmyyyy(x.data)}`);
      const vencVals = topEv.map(x => x.valor);

      const vencCfg = {
        type: 'bar',
        data: { labels: vencLabels, datasets: [{ label:'Valor', data: vencVals, backgroundColor: colors(vencVals.length || 1) }] },
        options: { responsive:true, plugins:{ legend:{ position:'bottom' }, tooltip:{ callbacks:{ label:(ctx)=> `${moneyBR(ctx.parsed.y)}` } } } }
      };

      renderChartCard(quickBottom, {
        id: 'fr_q_venc',
        title: 'Próximos vencimentos (em aberto)',
        sub: 'Top 10 por data (pagar + receber)',
        compact: false,
        wide: true,
        stack: true,
        series: evSeries.length ? evSeries : [{ label:'Sem vencimentos em aberto no período', value: 0 }],
        chartCfg: vencCfg,
        maxRows: CHART_TOP_N,
      });

      const fixas = cpIn.filter(p => (String(p.fixa) === '1' || p.fixa === true));
      const fixSeriesAll = sortTop(sumBy(fixas, p => (p.conta || '—'), asVal));
      const fixSeries = limitSeriesWithOthers(fixSeriesAll, CHART_TOP_N, 'Outros');
      const fixCfg = applyTooltipMoneyAndPct({
        type: 'doughnut',
        data: {
          labels: fixSeries.map(x => x.label),
          datasets: [{ data: fixSeries.map(x => x.value), backgroundColor: colors(Math.max(fixSeries.length,1)), borderWidth:0 }],
        },
        options: { responsive:true, plugins:{ legend:{ position:'bottom' } }, cutout:'58%' }
      });

      renderChartCard(quickBottom, {
        id: 'fr_q_fixas',
        title: 'Fixas (recorrentes) — principais contas',
        sub: 'Top 10 por valor no período',
        compact: true,
        wide: false,
        stack: false,
        series: fixSeries.length ? fixSeries : [{ label:'Sem contas fixas no período', value: 0 }],
        chartCfg: fixCfg,
        maxRows: CHART_TOP_N,
      });
    }
  }

  // -----------------------------
  // Modal
  // -----------------------------
  function openModalFromCard(card){
    if(!card || !modal) return;

    const rid = card.getAttribute('data-report-id') || '—';
    const name = card.getAttribute('data-name') || 'Relatório';

    lastOpened = { rid, name, desc: card.getAttribute('data-desc') || '' };

    if(modalTitle) modalTitle.textContent = name;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');

    syncModalActionsForViewport();

    try{ renderExecResult(execReport(rid)); }catch(_){
      showToast('Falha ao executar o relatório.', 'danger');
    }
  }

  function closeModal(){
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  function getCardMetaById(rid){
    try{
      const sel = `.fin-rep-card[data-report-id="${CSS.escape(String(rid))}"]`;
      const card = root.querySelector(sel);
      if(!card) return { name: 'Relatório', desc: '' };
      return {
        name: card.getAttribute('data-name') || 'Relatório',
        desc: card.getAttribute('data-desc') || '',
      };
    }catch(_){
      return { name: 'Relatório', desc: '' };
    }
  }

  function buildKpiCards(kpis){
    if(!printKpis) return;
    if(!Array.isArray(kpis) || !kpis.length){
      printKpis.innerHTML = '';
      return;
    }
    printKpis.innerHTML = kpis.map(k => {
      return `<div class="fr-kpi"><div class="fr-kpi__k">${escapeHtml(k.label)}</div><div class="fr-kpi__v">${escapeHtml(k.value)}</div></div>`;
    }).join('');
  }

  function renderTable(columns, rows, totalRow){
    if(!theadEl || !tbodyEl) return;
    const cols = Array.isArray(columns) ? columns : [];
    const rs = Array.isArray(rows) ? rows : [];

    theadEl.innerHTML = cols.length ? `<tr>${cols.map(c => `<th>${escapeHtml(c.label || '')}</th>`).join('')}</tr>` : '';

    const bodyRows = rs.map(r => {
      return `<tr>${cols.map(c => {
        const v = typeof c.value === 'function' ? c.value(r) : (r[c.key] ?? '');
        const cls = c.align === 'right' ? 't-right' : (c.align === 'center' ? 't-center' : '');
        return `<td class="${cls}">${escapeHtml(v)}</td>`;
      }).join('')}</tr>`;
    }).join('');

    const emptyRow = `<tr><td colspan="${Math.max(cols.length,1)}">Nenhum dado no período.</td></tr>`;

    const totalHtml = totalRow ? `<tr class="is-total">${cols.map((c, idx) => {
      const v = (idx === 0) ? (totalRow.label || 'Total') : (typeof totalRow.value === 'function' ? totalRow.value(c, idx) : '');
      const cls = (idx === 0) ? '' : (c.align === 'right' ? 't-right' : (c.align === 'center' ? 't-center' : ''));
      return `<td class="${cls}">${escapeHtml(v)}</td>`;
    }).join('')}</tr>` : '';

    tbodyEl.innerHTML = (rs.length ? bodyRows : emptyRow) + totalHtml;
  }

  let _chartInstance = null;
  function destroyChart(){
    try{ if(_chartInstance){ _chartInstance.destroy(); } }catch(_){ }
    _chartInstance = null;
  }

  function renderChart(chartCfg, sumRows){
    if(!chartSection || !chartCanvas) return;

    destroyChart();
    if(chartImg){ chartImg.removeAttribute('src'); }
    if(chartSumEl) chartSumEl.innerHTML = '';
    if(chartTotalEl) chartTotalEl.innerHTML = '';

    if(!chartCfg){
      chartSection.hidden = true;
      if(chartHint) chartHint.hidden = true;
      return;
    }

    chartSection.hidden = false;

    if(!window.Chart){
      if(chartHint) chartHint.hidden = false;
      return;
    }
    if(chartHint) chartHint.hidden = true;

    const ctx = chartCanvas.getContext('2d');
    _chartInstance = new window.Chart(ctx, chartCfg);

    try{
      const dataUrl = chartCanvas.toDataURL('image/png', 1.0);
      if(chartImg) chartImg.src = dataUrl;
    }catch(_){}

    if(chartSumEl && Array.isArray(sumRows) && sumRows.length){
      chartSumEl.innerHTML = sumRows.slice(0, CHART_TOP_N).map(r => {
        return `
          <div class="fr-print__sumrow">
            <div class="fr-print__sumcell fr-print__sumlabel">${escapeHtml(r.label)}</div>
            <div class="fr-print__sumcell fr-print__sumval">${escapeHtml(r.value)}</div>
            <div class="fr-print__sumcell fr-print__sumpct">${escapeHtml(r.pct)}</div>
          </div>
        `;
      }).join('');
    }

    if(chartTotalEl){
      chartTotalEl.innerHTML = '';
    }
  }

  function buildCsv(columns, rows){
    const cols = Array.isArray(columns) ? columns : [];
    const rs = Array.isArray(rows) ? rows : [];

    const header = cols.map(c => escapeCsv(c.label || '')).join(';');
    const lines = rs.map(r => cols.map(c => {
      const v = typeof c.value === 'function' ? c.value(r) : (r[c.key] ?? '');
      return escapeCsv(v);
    }).join(';'));

    return [header].concat(lines).join('\n');
  }

  function downloadTextFile(filename, text){
    try{
      const blob = new Blob([text], { type: 'text/csv;charset=utf-8;' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }catch(_){
      showToast('Falha ao gerar o arquivo.', 'danger');
    }
  }

  // -----------------------------
  // Execução de relatórios
  // -----------------------------
  function execReport(rid){
    const st = getFilterState();
    const { start, end, warned } = getPeriodRange(st.period);
    const { cp, cr } = readData();

    if(warned){
      showToast('Período personalizado ainda não está disponível; usando "Este mês".', 'warning');
    }

    const meta = getCardMetaById(rid);

    const cpIn = cp.filter(r => inRange(r.data, start, end));
    const crIn = cr.filter(r => inRange(r.data, start, end));

    const hasData = cp.length || cr.length;
    const asVal = (x) => Number(x && x.valor != null ? x.valor : 0);

    const sumStatus = (list, stt) => list.reduce((acc, r) => acc + (r.status === stt ? asVal(r) : 0), 0);

    let columns = [];
    let rows = [];
    let kpis = [];
    let chart = null;
    let chartSum = null;
    let totalRow = null;
    let footnote = '';

    if(rid === 'rep_resumo_mes'){
      const pagarOpen = sumStatus(cpIn, 'open');
      const pagarDone = sumStatus(cpIn, 'done');
      const recOpen = sumStatus(crIn, 'open');
      const recDone = sumStatus(crIn, 'done');

      const entradas = recDone + recOpen;
      const saidas = pagarDone + pagarOpen;
      const saldo = entradas - saidas;

      kpis = [
        { label: 'Entradas', value: moneyBR(entradas) },
        { label: 'Saídas', value: moneyBR(saidas) },
        { label: 'Saldo', value: moneyBR(saldo) },
        { label: 'Pendências', value: String(cpIn.filter(x => x.status==='open').length + crIn.filter(x => x.status==='open').length) },
      ];

      rows = [
        { k:'Entradas', v: entradas },
        { k:'Saídas', v: saidas },
        { k:'Saldo', v: saldo },
      ];

      columns = [
        { label:'Indicador', key:'k' },
        { label:'Valor', value:r => moneyBR(r.v), align:'right' },
      ];

      chart = window.Chart ? applyTooltipMoneyAndPct({
        type: 'doughnut',
        data: {
          labels: ['Entradas', 'Saídas'],
          datasets: [{ data: [entradas, saidas], backgroundColor: colors(2), borderWidth:0 }],
        },
        options: { responsive:true, plugins:{ legend:{ position:'bottom' } }, cutout:'55%' },
      }) : null;

      const total = entradas + saidas;
      chartSum = [
        { label:'Entradas', value: moneyBR(entradas), pct: total>0 ? (pctOf(entradas,total)).toFixed(1).replace('.',',')+'%' : '0,0%' },
        { label:'Saídas', value: moneyBR(saidas), pct: total>0 ? (pctOf(saidas,total)).toFixed(1).replace('.',',')+'%' : '0,0%' },
      ];

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_status'){
      const pagarOpen = sumStatus(cpIn, 'open');
      const pagarDone = sumStatus(cpIn, 'done');
      const recOpen = sumStatus(crIn, 'open');
      const recDone = sumStatus(crIn, 'done');

      kpis = [
        { label: 'Pagar (pendente)', value: moneyBR(pagarOpen) },
        { label: 'Pagar (concluído)', value: moneyBR(pagarDone) },
        { label: 'Receber (pendente)', value: moneyBR(recOpen) },
        { label: 'Receber (concluído)', value: moneyBR(recDone) },
      ];

      rows = [
        { kind: 'Contas a pagar', open: pagarOpen, done: pagarDone },
        { kind: 'Contas a receber', open: recOpen, done: recDone },
      ];

      columns = [
        { label: 'Grupo', key: 'kind' },
        { label: 'Pendente', value: r => moneyBR(r.open), align: 'right' },
        { label: 'Concluído', value: r => moneyBR(r.done), align: 'right' },
      ];

      chart = window.Chart ? {
        type: 'bar',
        data: {
          labels: ['Pagar', 'Receber'],
          datasets: [
            { label:'Pendente', data:[pagarOpen, recOpen], backgroundColor:'rgba(245,158,11,.70)' },
            { label:'Concluído', data:[pagarDone, recDone], backgroundColor:'rgba(59,130,246,.70)' },
          ],
        },
        options: {
          responsive:true,
          plugins:{
            legend:{ position:'bottom' },
            tooltip:{ callbacks:{ label:(ctx)=> `${ctx.dataset.label}: ${moneyBR(ctx.parsed.y)}` } }
          },
          scales:{ x:{ stacked:true }, y:{ stacked:true } }
        },
      } : null;

      const total = pagarOpen + pagarDone + recOpen + recDone;
      chartSum = [
        { label:'Pagar (pend.)', value: moneyBR(pagarOpen), pct: total>0 ? (pctOf(pagarOpen,total)).toFixed(1).replace('.',',')+'%' : '0,0%' },
        { label:'Pagar (conc.)', value: moneyBR(pagarDone), pct: total>0 ? (pctOf(pagarDone,total)).toFixed(1).replace('.',',')+'%' : '0,0%' },
        { label:'Receber (pend.)', value: moneyBR(recOpen), pct: total>0 ? (pctOf(recOpen,total)).toFixed(1).replace('.',',')+'%' : '0,0%' },
        { label:'Receber (conc.)', value: moneyBR(recDone), pct: total>0 ? (pctOf(recDone,total)).toFixed(1).replace('.',',')+'%' : '0,0%' },
      ];

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_imoveis'){
      const map = sumBy(cpIn, r => (r.imovel || '—'), asVal);
      const seriesAll = sortTop(map);

      const total = seriesAll.reduce((a,b)=>a+Number(b.value||0),0);
      const series = limitSeriesWithOthers(seriesAll, CHART_TOP_N, 'Outros');

      kpis = [
        { label:'Imóveis', value: String(map.size) },
        { label:'Total despesas', value: moneyBR(cpIn.reduce((a,b)=>a+asVal(b),0)) },
      ];

      // ✅ tabela completa (sem "Outros")
      rows = seriesAll.map(s => ({ imovel:s.label, valor:s.value, pct: pctOf(s.value,total) }));
      columns = [
        { label:'Imóvel', key:'imovel' },
        { label:'Valor', value:r => moneyBR(r.valor), align:'right' },
        { label:'%', value:r => (r.pct||0).toFixed(1).replace('.',',')+'%', align:'right' },
      ];
      totalRow = {
        label: 'Total',
        value: (col, idx) => idx === 1 ? moneyBR(total) : (idx === 2 ? '100,0%' : '')
      };

      chart = window.Chart ? applyTooltipMoneyAndPct({
        type:'doughnut',
        data:{
          labels: series.map(x=>x.label),
          datasets:[{ data: series.map(x=>x.value), backgroundColor: colors(series.length), borderWidth:0 }]
        },
        options:{ responsive:true, plugins:{ legend:{ position:'bottom' } }, cutout:'58%' }
      }) : null;

      // ✅ chartSum limitado (Top N + Outros)
      chartSum = series.map(s => ({
        label: s.label,
        value: moneyBR(s.value),
        pct: total > 0 ? (pctOf(s.value, total)).toFixed(1).replace('.', ',') + '%' : '0,0%'
      }));

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_categorias'){
      const map = sumBy(cpIn, r => (r.categoria || '—'), asVal);
      const seriesAll = sortTop(map);

      const total = seriesAll.reduce((a,b)=>a+Number(b.value||0),0);
      const series = limitSeriesWithOthers(seriesAll, CHART_TOP_N, 'Outros');

      kpis = [
        { label:'Categorias', value: String(map.size) },
        { label:'Total despesas', value: moneyBR(cpIn.reduce((a,b)=>a+asVal(b),0)) },
      ];

      // ✅ tabela completa (sem "Outros")
      rows = seriesAll.map(s => ({ categoria:s.label, valor:s.value, pct: pctOf(s.value,total) }));
      columns = [
        { label:'Categoria', key:'categoria' },
        { label:'Valor', value:r => moneyBR(r.valor), align:'right' },
        { label:'%', value:r => (r.pct||0).toFixed(1).replace('.',',')+'%', align:'right' },
      ];
      totalRow = {
        label: 'Total',
        value: (col, idx) => idx === 1 ? moneyBR(total) : (idx === 2 ? '100,0%' : '')
      };

      chart = window.Chart ? applyTooltipMoneyAndPct({
        type:'doughnut',
        data:{
          labels: series.map(x=>x.label),
          datasets:[{ data: series.map(x=>x.value), backgroundColor: colors(series.length), borderWidth:0 }]
        },
        options:{ responsive:true, plugins:{ legend:{ position:'bottom' } }, cutout:'58%' }
      }) : null;

      // ✅ chartSum limitado (Top N + Outros)
      chartSum = series.map(s => ({
        label: s.label,
        value: moneyBR(s.value),
        pct: total > 0 ? (pctOf(s.value, total)).toFixed(1).replace('.', ',') + '%' : '0,0%'
      }));

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_fluxo'){
      const days = [];
      const cur = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      while(cur < end){
        const k = `${cur.getFullYear()}-${pad2(cur.getMonth()+1)}-${pad2(cur.getDate())}`;
        days.push(k);
        cur.setDate(cur.getDate() + 1);
      }

      const bucket = {};
      days.forEach(d => bucket[d] = { in:0, out:0 });

      const addDay = (iso, kind, val) => {
        const d = parseIsoToDate(iso);
        if(!d) return;
        const k = `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
        if(!bucket[k]) return;
        if(kind === 'in') bucket[k].in += val;
        else bucket[k].out += val;
      };

      crIn.forEach(r => addDay(r.data, 'in', asVal(r)));
      cpIn.forEach(p => addDay(p.data, 'out', asVal(p)));

      const inArr = days.map(k => bucket[k].in);
      const outArr = days.map(k => bucket[k].out);

      const totalIn = inArr.reduce((a,b)=>a+Number(b||0),0);
      const totalOut = outArr.reduce((a,b)=>a+Number(b||0),0);

      kpis = [
        { label:'Entradas', value: moneyBR(totalIn) },
        { label:'Saídas', value: moneyBR(totalOut) },
        { label:'Saldo', value: moneyBR(totalIn - totalOut) },
      ];

      rows = days.map(k => ({ dia: toBRddmmyyyy(k), in: bucket[k].in, out: bucket[k].out }));
      columns = [
        { label:'Dia', key:'dia', align:'center' },
        { label:'Entradas', value:r => moneyBR(r.in), align:'right' },
        { label:'Saídas', value:r => moneyBR(r.out), align:'right' },
      ];
      totalRow = { label:'Total', value:(col, idx)=> idx===1 ? moneyBR(totalIn) : (idx===2 ? moneyBR(totalOut) : '') };

      chart = window.Chart ? {
        type:'bar',
        data:{
          labels: days.map(d => d.slice(8,10)+'/'+d.slice(5,7)),
          datasets:[
            { label:'Entradas', data: inArr, backgroundColor:'rgba(16,185,129,.70)' },
            { label:'Saídas', data: outArr, backgroundColor:'rgba(239,68,68,.65)' },
          ]
        },
        options:{
          responsive:true,
          plugins:{
            legend:{ position:'bottom' },
            tooltip:{ callbacks:{ label:(ctx)=> `${ctx.dataset.label}: ${moneyBR(ctx.parsed.y)}` } }
          }
        }
      } : null;

      const tot = totalIn + totalOut;
      chartSum = [
        { label:'Entradas', value: moneyBR(totalIn), pct: tot>0 ? (pctOf(totalIn,tot)).toFixed(1).replace('.',',')+'%' : '0,0%' },
        { label:'Saídas', value: moneyBR(totalOut), pct: tot>0 ? (pctOf(totalOut,tot)).toFixed(1).replace('.',',')+'%' : '0,0%' },
      ];

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_vencimentos'){
      const list = [];
      cpIn.forEach(p => { if(p.status === 'open') list.push({ tipo:'Pagar', titulo:p.conta||'—', meta:p.imovel||'—', data:p.data, valor:asVal(p) }); });
      crIn.forEach(r => { if(r.status === 'open') list.push({ tipo:'Receber', titulo:r.cliente||'—', meta:r.processo||'—', data:r.data, valor:asVal(r) }); });

      list.sort((a,b)=> safeTime(a.data) - safeTime(b.data));

      columns = [
        { label:'Tipo', key:'tipo', align:'center' },
        { label:'Título', key:'titulo' },
        { label:'Meta', key:'meta' },
        { label:'Venc.', value:r => toBRddmmyyyy(r.data), align:'center' },
        { label:'Valor', value:r => moneyBR(r.valor), align:'right' },
      ];
      rows = list;

      const total = list.reduce((a,b)=>a+Number(b.valor||0),0);
      kpis = [
        { label:'Eventos', value: String(list.length) },
        { label:'Total em aberto', value: moneyBR(total) },
      ];
      totalRow = { label:'Total', value:(col, idx)=> idx===4 ? moneyBR(total) : '' };

      const totalPagar = list.filter(x => x.tipo === 'Pagar').reduce((a,b)=>a+Number(b.valor||0),0);
      const totalReceber = list.filter(x => x.tipo === 'Receber').reduce((a,b)=>a+Number(b.valor||0),0);
      const totalChart = totalPagar + totalReceber;

      chart = window.Chart ? applyTooltipMoneyAndPct({
        type: 'doughnut',
        data: {
          labels: ['A pagar', 'A receber'],
          datasets: [{
            data: [totalPagar, totalReceber],
            backgroundColor: colors(2),
            borderWidth: 0,
          }],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          cutout: '58%',
        },
      }) : null;

      chartSum = [
        { label: 'A pagar', value: moneyBR(totalPagar), pct: totalChart > 0 ? (pctOf(totalPagar, totalChart)).toFixed(1).replace('.', ',') + '%' : '0,0%' },
        { label: 'A receber', value: moneyBR(totalReceber), pct: totalChart > 0 ? (pctOf(totalReceber, totalChart)).toFixed(1).replace('.', ',') + '%' : '0,0%' },
      ];

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_recorrentes'){
      const fixas = cpIn.filter(p => (String(p.fixa) === '1' || p.fixa === true));
      fixas.sort((a,b)=> safeTime(a.data) - safeTime(b.data));

      columns = [
        { label:'Conta', key:'conta' },
        { label:'Imóvel', key:'imovel' },
        { label:'Venc.', value:r => toBRddmmyyyy(r.data), align:'center' },
        { label:'Categoria', key:'categoria' },
        { label:'Valor', value:r => moneyBR(r.valor), align:'right' },
      ];
      rows = fixas;

      const total = fixas.reduce((a,b)=>a+asVal(b),0);
      kpis = [
        { label:'Fixas', value: String(fixas.length) },
        { label:'Total fixas', value: moneyBR(total) },
      ];
      totalRow = { label:'Total', value:(col, idx)=> idx===4 ? moneyBR(total) : '' };

      const map = sumBy(fixas, r => (r.conta || '—'), asVal);
      const seriesAll = sortTop(map);
      const series = limitSeriesWithOthers(seriesAll, CHART_TOP_N, 'Outros');
      const totalAgg = seriesAll.reduce((a,b)=>a+Number(b.value||0),0);

      chart = window.Chart ? applyTooltipMoneyAndPct({
        type:'doughnut',
        data:{
          labels: series.map(x=>x.label),
          datasets:[{ data: series.map(x=>x.value), backgroundColor: colors(series.length), borderWidth:0 }]
        },
        options:{ responsive:true, plugins:{ legend:{ position:'bottom' } }, cutout:'58%' }
      }) : null;

      chartSum = series.map(s => ({
        label: s.label,
        value: moneyBR(s.value),
        pct: totalAgg>0 ? (pctOf(s.value,totalAgg)).toFixed(1).replace('.',',')+'%' : '0,0%'
      }));

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else if(rid === 'rep_lancamentos'){
      const list = [];
      cpIn.forEach(p => list.push({
        tipo:'Pagar',
        titulo: p.conta || '—',
        meta: p.imovel || '—',
        data: p.data,
        status: p.status || 'open',
        valor: asVal(p),
      }));
      crIn.forEach(r => list.push({
        tipo:'Receber',
        titulo: r.cliente || '—',
        meta: r.processo || '—',
        data: r.data,
        status: r.status || 'open',
        valor: asVal(r),
      }));

      list.sort((a,b)=> safeTime(a.data) - safeTime(b.data));

      columns = [
        { label:'Tipo', key:'tipo', align:'center' },
        { label:'Título', key:'titulo' },
        { label:'Meta', key:'meta' },
        { label:'Data', value:r => toBRddmmyyyy(r.data), align:'center' },
        { label:'Status', value:r => r.status === 'done' ? 'Concluído' : 'Aberto', align:'center' },
        { label:'Valor', value:r => moneyBR(r.valor), align:'right' },
      ];
      rows = list;

      const total = list.reduce((a,b)=>a+Number(b.valor||0),0);
      kpis = [
        { label:'Itens', value: String(list.length) },
        { label:'Total', value: moneyBR(total) },
      ];
      totalRow = { label:'Total', value:(col, idx)=> idx===5 ? moneyBR(total) : '' };

      const totalCP = list.filter(x => x.tipo === 'Pagar').reduce((a,b)=>a+Number(b.valor||0),0);
      const totalCR = list.filter(x => x.tipo === 'Receber').reduce((a,b)=>a+Number(b.valor||0),0);
      const totalChart = totalCP + totalCR;

      chart = window.Chart ? applyTooltipMoneyAndPct({
        type: 'doughnut',
        data: {
          labels: ['Contas a pagar', 'Contas a receber'],
          datasets: [{
            data: [totalCP, totalCR],
            backgroundColor: colors(2),
            borderWidth: 0,
          }],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          cutout: '58%',
        },
      }) : null;

      chartSum = [
        { label: 'Contas a pagar', value: moneyBR(totalCP), pct: totalChart > 0 ? (pctOf(totalCP, totalChart)).toFixed(1).replace('.', ',') + '%' : '0,0%' },
        { label: 'Contas a receber', value: moneyBR(totalCR), pct: totalChart > 0 ? (pctOf(totalCR, totalChart)).toFixed(1).replace('.', ',') + '%' : '0,0%' },
      ];

      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }

    else{
      footnote = 'Relatório não encontrado (id inválido).';
    }

    return {
      rid,
      title: meta.name,
      desc: meta.desc,
      periodLabel: getPeriodLabel(),
      typeLabel: getTypeLabel(),
      kpis,
      columns,
      rows,
      chart,
      chartSum,
      totalRow,
      footnote,
      periodKey: st.period,
    };
  }

  function renderExecResult(res){
    if(!res) return;

    lastExec = res;

    if(printGeneratedAt){
      const now = new Date();
      printGeneratedAt.textContent = `${pad2(now.getDate())}/${pad2(now.getMonth()+1)}/${now.getFullYear()} ${pad2(now.getHours())}:${pad2(now.getMinutes())}`;
    }
    if(printPeriod) printPeriod.textContent = res.periodLabel || '—';
    if(printType) printType.textContent = res.typeLabel || 'Todos';

    if(printTitle) printTitle.textContent = res.title || 'Relatório';
    if(printDesc) printDesc.textContent = res.desc || '';

    buildKpiCards(res.kpis);
    renderTable(res.columns, res.rows, res.totalRow);

    if(footnoteEl) footnoteEl.textContent = res.footnote || '';

    renderChart(res.chart, res.chartSum || null);

    if(chartTotalEl && Array.isArray(res.chartSum) && res.chartSum.length){
      chartTotalEl.innerHTML = '';
    }
  }

  function isMobile(){ return window.matchMedia('(max-width: 700px)').matches; }

  function syncModalActionsForViewport(){
    if(!modalExport) return;
    const hide = isMobile();
    modalExport.style.display = hide ? 'none' : '';
    modalExport.setAttribute('aria-hidden', hide ? 'true' : 'false');
  }

  function applyFilterAccordion(){
    if(!wrap || !toggle) return;
    if(isMobile()){
      wrap.classList.add('is-open');
    }else{
      wrap.classList.remove('is-open');
    }
    syncModalActionsForViewport();
  }

  // -----------------------------
  // Events
  // -----------------------------
  root.addEventListener('click', (e) => {
    const favBtn = e.target.closest('.fin-rep-card__fav');
    if(favBtn){
      e.preventDefault();
      e.stopPropagation();

      const card = favBtn.closest('.fin-rep-card');
      if(!card) return;
      const id = card.getAttribute('data-report-id');
      const turnOn = !isFav(id);

      const ok = setFav(id, turnOn);
      if(!ok) return;

      updateStarButtons();
      renderFavorites();
      return;
    }

    const card = e.target.closest('.fin-rep-card');
    if(card){
      openModalFromCard(card);
      return;
    }
  });

  root.addEventListener('keydown', (e) => {
    const card = e.target.closest && e.target.closest('.fin-rep-card');
    if(!card) return;
    if(e.key === 'Enter' || e.key === ' '){
      e.preventDefault();
      openModalFromCard(card);
    }
  });

  if(btnClear){
    btnClear.addEventListener('click', () => {
      if(periodEl) periodEl.value = 'this_month';
      if(typeEl) typeEl.value = '';
      if(searchEl) searchEl.value = '';
      applyFilters();
      buildQuickCharts();
      showToast('Filtros limpos.', 'warning');
    });
  }

  if(btnRun){
    btnRun.addEventListener('click', () => {
      const shown = applyFilters();
      buildQuickCharts();
      showToast(`Executado: ${shown} relatórios encontrados pelo filtro.`, 'success');
    });
  }

  if(searchEl) searchEl.addEventListener('input', applyFilters);

  if(typeEl) typeEl.addEventListener('change', () => {
    applyFilters();
  });

  if(periodEl) periodEl.addEventListener('change', () => {
    buildQuickCharts();
    showToast('Período atualizado.', 'success');
  });

  if(modalClose) modalClose.addEventListener('click', closeModal);

  if(modal){
    modal.addEventListener('click', (e) => {
      if(e.target === modal) closeModal();
    });
  }

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') closeModal();
  });

  if(modalRun){
    modalRun.addEventListener('click', () => {
      if(!lastOpened) return;
      const res = execReport(lastOpened.rid);
      renderExecResult(res);
      showToast('Relatório atualizado.', 'success');
    });
  }

  if(modalExport){
    modalExport.addEventListener('click', () => {
      if(!lastOpened) return;

      if(!lastExec || lastExec.rid !== lastOpened.rid){
        try{ renderExecResult(execReport(lastOpened.rid)); }catch(_){ }
      }
      if(!lastExec){
        showToast('Nada para exportar ainda.', 'warning');
        return;
      }

      const csv = buildCsv(lastExec.columns, lastExec.rows);
      const safeName = (lastExec.title || 'relatorio').toLowerCase().replace(/[^a-z0-9\-]+/gi, '_');
      downloadTextFile(`${safeName}.csv`, csv);
      showToast('CSV gerado.', 'success');
    });
  }

  if(modalPrint){
    modalPrint.addEventListener('click', () => {
      if(!lastOpened) return;

      if(!lastExec || lastExec.rid !== lastOpened.rid){
        try{ renderExecResult(execReport(lastOpened.rid)); }catch(_){ }
      }

      if(!lastExec){
        showToast('Nada para imprimir ainda.', 'warning');
        return;
      }

      let chartDataUrl = '';
      try{
        if(chartCanvas && window.Chart){
          chartDataUrl = chartCanvas.toDataURL('image/png', 1.0);
        }
      }catch(_){
        chartDataUrl = '';
      }

      const cols = Array.isArray(lastExec.columns) ? lastExec.columns : [];
      const head = cols.map(c => String(c.label || ''));

      const rows = (Array.isArray(lastExec.rows) ? lastExec.rows : []).map(r => {
        return cols.map(c => {
          try{
            const v = (typeof c.value === 'function') ? c.value(r) : (r[c.key] ?? '');
            return String(v ?? '');
          }catch(_){
            return '';
          }
        });
      });

      let total = null;
      if(lastExec.totalRow && cols.length){
        total = cols.map((c, idx) => {
          try{
            if(idx === 0) return String(lastExec.totalRow.label || 'Total');
            if(typeof lastExec.totalRow.value === 'function') return String(lastExec.totalRow.value(c, idx) ?? '');
            return '';
          }catch(_){
            return '';
          }
        });
      }

      const corp = (window.__FR_MOCK__ && window.__FR_MOCK__.corp) ? window.__FR_MOCK__.corp : {};

      const payload = {
        corp,
        meta: {
          generatedAt: (printGeneratedAt ? (printGeneratedAt.textContent || '') : '').trim() || '—',
          period: lastExec.periodLabel || '—',
          type: lastExec.typeLabel || '—',
        },
        title: lastExec.title || 'Relatório',
        desc: lastExec.desc || '',
        kpis: Array.isArray(lastExec.kpis) ? lastExec.kpis : [],
        chart: {
          img: chartDataUrl || '',
          sum: Array.isArray(lastExec.chartSum) ? lastExec.chartSum : [],
        },
        table: {
          head,
          rows,
          total,
        },
        footnote: lastExec.footnote || '',
      };

      const form = document.createElement('form');
      form.method = 'POST';
      form.target = '_blank';
      form.action = '/sistema-visa/app/templates/financeiro_relatorios_print_preview.php';

      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'payload';
      input.value = JSON.stringify(payload);
      form.appendChild(input);

      document.body.appendChild(form);
      form.submit();
      form.remove();
    });
  }

  if(toggle){
    toggle.addEventListener('click', () => {
      if(!isMobile()) return;
      wrap.classList.toggle('is-open');
      if(icon){
        icon.style.transform = wrap.classList.contains('is-open') ? 'rotate(180deg)' : 'rotate(0deg)';
      }
    });
  }

  window.addEventListener('resize', () => {
    applyFilterAccordion();
    buildQuickCharts();
  });

  // -----------------------------
  // Init
  // -----------------------------
  cleanupFavorites();
  updateStarButtons();
  renderFavorites();
  applyFilters();
  applyFilterAccordion();
  syncModalActionsForViewport();
  buildQuickCharts();

  try {
    const sp = new URLSearchParams(window.location.search || "");
    const rid = (sp.get("rid") || "").trim();
    if (rid) {
      const card = root.querySelector(`.fin-rep-card[data-report-id="${CSS.escape(rid)}"]`);
      if (card) openModalFromCard(card);
    }
  } catch (_) {}

  window.__FR_API__ = window.__FR_API__ || {};
  window.__FR_API__.execReport = execReport;
  window.__FR_API__.renderExecResult = renderExecResult;

  window.__FR_API__.getState = function(){
    return { lastOpened, lastExec };
  };

  window.__FR_API__.ensureRendered = function(rid){
    const st = window.__FR_API__.getState();
    if(!st.lastExec || st.lastExec.rid !== rid){
      const res = execReport(rid);
      renderExecResult(res);
      return res;
    }
    return st.lastExec;
  };

})();