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

  const modal    = document.getElementById('frModal');
  const modalClose = document.getElementById('frModalClose');
  const modalTitle = document.getElementById('frModalTitle');
  const modalPeriod = document.getElementById('frModalPeriod');
  const modalType = document.getElementById('frModalType');
  const modalReport = document.getElementById('frModalReport');
  const modalRun = document.getElementById('frModalRun');
  const modalExport = document.getElementById('frModalGhost');

  const modalPrint = document.getElementById('frModalPrint');

  // Print/preview elements (padrão ouro)
  const printArea = document.getElementById('frPrintArea');
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

  // -----------------------------
  // Helpers
  // -----------------------------
  function showToast(msg, kind){
    // Preferência: Toast global do sistema
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

    // Fallback: toast local do relatório
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
    // fallback do PHP/mock
    return Array.isArray(mock.favorites) ? mock.favorites.slice(0, FAV_LIMIT) : [];
  }

  function saveFavorites(){
    try{
      localStorage.setItem(LS_KEY, JSON.stringify(favorites.slice(0, FAV_LIMIT)));
    }catch(e){}
  }

  function isFav(id){
    return favorites.indexOf(id) !== -1;
  }

  function setFav(id, on){
    if(on){
      if(isFav(id)) return true;
      if(favorites.length >= FAV_LIMIT){
        showToast(`Limite de favoritos atingido (${FAV_LIMIT}). Remova um favorito para adicionar outro.`, 'warning');
        return false;
      }
      favorites.push(id);
      favorites = favorites.slice(0, FAV_LIMIT);
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

  function normalizeStr(s){
    return String(s || '').toLowerCase().trim();
  }

  function pad2(n){ return String(n).padStart(2, '0'); }

  function toBRddmmyyyy(iso){
    if(!iso || typeof iso !== 'string' || iso.length < 10) return '';
    return iso.slice(8,10) + '/' + iso.slice(5,7) + '/' + iso.slice(0,4);
  }

  function moneyBR(v){
    const n = Number(v || 0);
    return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function parseIsoToDate(iso){
    const d = new Date(String(iso || '') + 'T00:00:00');
    return Number.isNaN(d.getTime()) ? null : d;
  }

  function todayAtMidnight(){
    const now = new Date();
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
  }

  function monthKey(dt){
    return dt.getFullYear() + '-' + pad2(dt.getMonth() + 1);
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
      // Nesta etapa não há inputs de data no UI.
      // Mantemos como "este mês" e avisamos.
      const start = new Date(y, m, 1);
      const end = new Date(y, m + 1, 1);
      return { start, end, mode: 'range', warned: true };
    }

    // default: this_month
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

    // Tipo (grupo)
    if(st.type && group !== st.type) return false;

    // Busca
    if(st.search){
      const hay = `${name} ${desc} ${tags} ${group}`;
      if(hay.indexOf(st.search) === -1) return false;
    }

    // Período no mock não filtra cards (só passa pro modal/execução)
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

  function openModalFromCard(card){
    if(!card || !modal) return;

    const st = getFilterState();

    const rid = card.getAttribute('data-report-id') || '—';
    const name = card.getAttribute('data-name') || 'Relatório';
    const group = card.getAttribute('data-group') || '—';

    lastOpened = { rid, name, group, desc: card.getAttribute('data-desc') || '' };

    if(modalTitle) modalTitle.textContent = name;
    if(modalPeriod) modalPeriod.textContent = periodEl ? periodEl.options[periodEl.selectedIndex].text : st.period;
    if(modalType) modalType.textContent = typeEl ? (typeEl.options[typeEl.selectedIndex].text || 'Todos') : 'Todos';
    if(modalReport) modalReport.textContent = `${name} (${rid})`;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');

    syncModalActionsForViewport();

    // prepara o preview (sem imprimir) assim que abrir
    try{ renderExecResult(execReport(rid)); }catch(_){ }
  }

  function closeModal(){
    if(!modal) return;
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  // -----------------------------
  // Data (localStorage) + Execução
  // -----------------------------
  const LS_CP = 'fin_cp_rows_v1';
  const LS_CR = 'fin_cr_rows_v1';

  function readData(){
    const cp = loadArr(LS_CP).filter(r => r && r.data);
    const cr = loadArr(LS_CR).filter(r => r && r.data);
    return { cp, cr };
  }

  function getCardMetaById(rid){
    const card = root.querySelector(`.fin-rep-card[data-report-id="${rid}"]`);
    if(!card) return { name: 'Relatório', desc: '' };
    return {
      name: card.getAttribute('data-name') || 'Relatório',
      desc: card.getAttribute('data-desc') || '',
      group: card.getAttribute('data-group') || '',
    };
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

  function renderTable(columns, rows){
    if(!theadEl || !tbodyEl) return;
    const cols = Array.isArray(columns) ? columns : [];
    const rs = Array.isArray(rows) ? rows : [];

    theadEl.innerHTML = cols.length ? `<tr>${cols.map(c => `<th>${escapeHtml(c.label || '')}</th>`).join('')}</tr>` : '';

    tbodyEl.innerHTML = rs.length ? rs.map(r => {
      return `<tr>${cols.map(c => {
        const v = typeof c.value === 'function' ? c.value(r) : (r[c.key] ?? '');
        const cls = c.align === 'right' ? 't-right' : (c.align === 'center' ? 't-center' : '');
        return `<td class="${cls}">${escapeHtml(v)}</td>`;
      }).join('')}</tr>`;
    }).join('') : `<tr><td colspan="${Math.max(cols.length,1)}">Nenhum dado no período.</td></tr>`;
  }

  let _chartInstance = null;
  function destroyChart(){
    try{ if(_chartInstance){ _chartInstance.destroy(); } }catch(_){ }
    _chartInstance = null;
  }

  function renderChart(chartCfg){
    if(!chartSection || !chartCanvas) return;

    // reset
    destroyChart();
    if(chartImg){ chartImg.removeAttribute('src'); }

    if(!chartCfg){
      chartSection.hidden = true;
      if(chartHint) chartHint.hidden = true;
      return;
    }

    chartSection.hidden = false;

    // Chart.js disponível?
    if(!window.Chart){
      if(chartHint) chartHint.hidden = false;
      return;
    }

    if(chartHint) chartHint.hidden = true;

    const ctx = chartCanvas.getContext('2d');
    _chartInstance = new window.Chart(ctx, chartCfg);

    // prepara imagem para impressão (substitui canvas no @media print)
    try{
      const dataUrl = chartCanvas.toDataURL('image/png', 1.0);
      if(chartImg){
        chartImg.src = dataUrl;
      }
    }catch(_){ }
  }

  function buildCsv(columns, rows){
    const cols = Array.isArray(columns) ? columns : [];
    const rs = Array.isArray(rows) ? rows : [];

    const header = cols.map(c => escapeCsv(c.label || '')).join(';');
    const lines = rs.map(r => {
      return cols.map(c => {
        const v = typeof c.value === 'function' ? c.value(r) : (r[c.key] ?? '');
        return escapeCsv(v);
      }).join(';');
    });

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

    const sum = (list, stt) => list.reduce((acc, r) => acc + (r.status === stt ? Number(r.valor || 0) : 0), 0);

    // fallback: se não houver dados ainda no LS, usamos o mock/preview sem dados
    const hasData = cp.length || cr.length;

    let columns = [];
    let rows = [];
    let kpis = [];
    let chart = null;
    let footnote = '';

    if(rid === 'rep_pagar_aberto'){
      const list = cpIn.filter(r => r.status === 'open').sort((a,b) => safeTime(a.data) - safeTime(b.data));
      columns = [
        { label: 'Conta', key: 'conta' },
        { label: 'Imóvel', key: 'imovel' },
        { label: 'Venc.', value: r => toBRddmmyyyy(r.data), align: 'center' },
        { label: 'Categoria', key: 'categoria' },
        { label: 'Fixa', value: r => (String(r.fixa) === '1' || r.fixa === true) ? 'Sim' : 'Não', align: 'center' },
        { label: 'Valor', value: r => moneyBR(r.valor), align: 'right' },
      ];
      rows = list;
      kpis = [
        { label: 'Pendentes', value: String(list.length) },
        { label: 'Total pendente', value: moneyBR(sum(cpIn, 'open')) },
      ];
      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }
    else if(rid === 'rep_receber_aberto'){
      const list = crIn.filter(r => r.status === 'open').sort((a,b) => safeTime(a.data) - safeTime(b.data));
      columns = [
        { label: 'Cliente', key: 'cliente' },
        { label: 'Forma', key: 'forma' },
        { label: 'Processo', key: 'processo' },
        { label: 'Venc.', value: r => toBRddmmyyyy(r.data), align: 'center' },
        { label: 'Valor', value: r => moneyBR(r.valor), align: 'right' },
      ];
      rows = list;
      kpis = [
        { label: 'Pendentes', value: String(list.length) },
        { label: 'Total a receber', value: moneyBR(sum(crIn, 'open')) },
      ];
      footnote = hasData ? '' : 'Sem dados no localStorage nesta etapa.';
    }
    else if(rid === 'rep_vencimentos'){
      const list = [];
      cpIn.forEach(p => {
        if(p.status !== 'open') return;
        list.push({
          kind: 'Pagar',
          title: p.conta || '',
          meta: p.imovel || '',
          data: p.data,
          valor: Number(p.valor || 0),
        });
      });
      crIn.forEach(r => {
        if(r.status !== 'open') return;
        list.push({
          kind: 'Receber',
          title: (r.cliente || '') + (r.forma ? ' • ' + r.forma : ''),
          meta: r.processo ? 'Proc: ' + r.processo : '',
          data: r.data,
          valor: Number(r.valor || 0),
        });
      });
      list.sort((a,b) => safeTime(a.data) - safeTime(b.data));

      columns = [
        { label: 'Tipo', key: 'kind', align: 'center' },
        { label: 'Título', key: 'title' },
        { label: 'Meta', key: 'meta' },
        { label: 'Venc.', value: r => toBRddmmyyyy(r.data), align: 'center' },
        { label: 'Valor', value: r => moneyBR(r.valor), align: 'right' },
      ];
      rows = list;

      const pay = list.filter(x => x.kind === 'Pagar').reduce((a,b) => a + b.valor, 0);
      const rec = list.filter(x => x.kind === 'Receber').reduce((a,b) => a + b.valor, 0);
      kpis = [
        { label: 'Eventos', value: String(list.length) },
        { label: 'A pagar', value: moneyBR(pay) },
        { label: 'A receber', value: moneyBR(rec) },
      ];

      chart = window.Chart ? {
        type: 'doughnut',
        data: {
          labels: ['A pagar', 'A receber'],
          datasets: [{ data: [pay, rec] }],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          cutout: '55%',
        },
      } : null;
    }
    else if(rid === 'rep_status'){
      const pagarOpen = sum(cpIn, 'open');
      const pagarDone = sum(cpIn, 'done');
      const recOpen = sum(crIn, 'open');
      const recDone = sum(crIn, 'done');

      kpis = [
        { label: 'Pagar (pendente)', value: moneyBR(pagarOpen) },
        { label: 'Pagar (pago)', value: moneyBR(pagarDone) },
        { label: 'Receber (aberto)', value: moneyBR(recOpen) },
        { label: 'Receber (recebido)', value: moneyBR(recDone) },
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
        type: 'doughnut',
        data: {
          labels: ['Pagar pendente', 'Pagar pago', 'Receber aberto', 'Receber recebido'],
          datasets: [{ data: [pagarOpen, pagarDone, recOpen, recDone] }],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          cutout: '55%',
        },
      } : null;
    }
    else if(rid === 'rep_lancamentos' || rid === 'rep_exportacao'){
      const list = [];
      cpIn.forEach(p => list.push({
        tipo: 'Pagar',
        titulo: p.conta || '',
        meta: p.imovel || '',
        data: p.data,
        status: p.status || 'open',
        valor: Number(p.valor || 0),
      }));
      crIn.forEach(r => list.push({
        tipo: 'Receber',
        titulo: (r.cliente || '') + (r.forma ? ' • ' + r.forma : ''),
        meta: r.processo || '',
        data: r.data,
        status: r.status || 'open',
        valor: Number(r.valor || 0),
      }));

      list.sort((a,b) => safeTime(a.data) - safeTime(b.data));

      columns = [
        { label: 'Tipo', key: 'tipo', align: 'center' },
        { label: 'Título', key: 'titulo' },
        { label: 'Meta', key: 'meta' },
        { label: 'Data', value: r => toBRddmmyyyy(r.data), align: 'center' },
        { label: 'Status', value: r => r.status === 'done' ? 'Concluído' : 'Aberto', align: 'center' },
        { label: 'Valor', value: r => moneyBR(r.valor), align: 'right' },
      ];
      rows = list;

      kpis = [
        { label: 'Itens', value: String(list.length) },
        { label: 'Total aberto', value: moneyBR(list.filter(x => x.status !== 'done').reduce((a,b) => a + b.valor, 0)) },
        { label: 'Total concluído', value: moneyBR(list.filter(x => x.status === 'done').reduce((a,b) => a + b.valor, 0)) },
      ];
    }
    else if(rid === 'rep_resumo_mes'){
      const pagarOpen = sum(cpIn, 'open');
      const pagarDone = sum(cpIn, 'done');
      const recOpen = sum(crIn, 'open');
      const recDone = sum(crIn, 'done');

      const entradas = recDone + recOpen;
      const saidas = pagarDone + pagarOpen;
      const saldo = entradas - saidas;

      kpis = [
        { label: 'Entradas', value: moneyBR(entradas) },
        { label: 'Saídas', value: moneyBR(saidas) },
        { label: 'Saldo', value: moneyBR(saldo) },
        { label: 'Pendências', value: String(cpIn.filter(x => x.status==='open').length + crIn.filter(x => x.status==='open').length) },
      ];

      // Série últimos 6 meses (se houver dados)
      const series = {};
      const addMonth = (iso, kind, val) => {
        const mk = (typeof iso === 'string' && iso.length >= 7) ? iso.slice(0,7) : '';
        if(!mk) return;
        if(!series[mk]) series[mk] = { mk, in:0, out:0 };
        if(kind === 'in') series[mk].in += val;
        else series[mk].out += val;
      };

      cp.forEach(p => addMonth(p.data, 'out', Number(p.valor||0)));
      cr.forEach(r => addMonth(r.data, 'in', Number(r.valor||0)));

      const months = Object.keys(series).sort().slice(-6);
      rows = months.map(mk => ({
        mes: mk,
        entradas: series[mk].in,
        saidas: series[mk].out,
      }));

      columns = [
        { label: 'Mês', key: 'mes', align: 'center' },
        { label: 'Entradas', value: r => moneyBR(r.entradas), align: 'right' },
        { label: 'Saídas', value: r => moneyBR(r.saidas), align: 'right' },
      ];

      chart = window.Chart ? {
        type: 'bar',
        data: {
          labels: months,
          datasets: [
            { label: 'Entradas', data: months.map(m => series[m].in) },
            { label: 'Saídas', data: months.map(m => series[m].out) },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom' } },
          scales: { x: { stacked: true }, y: { stacked: true } },
        },
      } : null;

      footnote = months.length ? '' : 'Sem histórico suficiente para série mensal.';
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
      footnote,
      periodKey: st.period,
    };
  }

  let lastExec = null;
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
    renderTable(res.columns, res.rows);

    if(footnoteEl) footnoteEl.textContent = res.footnote || '';

    renderChart(res.chart);
  }

  function isMobile(){
    return window.matchMedia('(max-width: 700px)').matches;
  }

  function syncModalActionsForViewport(){
    // No mobile, escondemos o botão de CSV para não quebrar o layout dos 3 botões.
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
      showToast('Filtros limpos.', 'warning');
    });
  }

  if(btnRun){
    btnRun.addEventListener('click', () => {
      const shown = applyFilters();
      showToast(`Executado (mock): ${shown} relatórios encontrados pelo filtro.`, 'success');
    });
  }

  if(searchEl){
    searchEl.addEventListener('input', () => {
      applyFilters();
    });
  }
  if(typeEl){
    typeEl.addEventListener('change', () => {
      applyFilters();
    });
  }
  if(periodEl){
    periodEl.addEventListener('change', () => {
      showToast('Período atualizado (mock).', 'success');
    });
  }

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

      // garante que temos um resultado renderizado
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

      // garante que o preview está pronto e que a imagem do gráfico foi gerada
      if(!lastExec || lastExec.rid !== lastOpened.rid){
        try{ renderExecResult(execReport(lastOpened.rid)); }catch(_){ }
      }

      // em alguns browsers o toDataURL precisa de um frame
      setTimeout(() => {
        try{
          if(chartCanvas && chartImg && window.Chart){
            const dataUrl = chartCanvas.toDataURL('image/png', 1.0);
            chartImg.src = dataUrl;
          }
        }catch(_){ }

        window.print();
      }, 50);
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

  window.addEventListener('resize', applyFilterAccordion);
  // garante estado correto no carregamento e em mudanças de viewport
  syncModalActionsForViewport();

  // -----------------------------
  // Init
  // -----------------------------
  favorites = favorites.slice(0, FAV_LIMIT);
  saveFavorites();
  updateStarButtons();
  renderFavorites();
  applyFilters();
  applyFilterAccordion();

  // Se o modal estiver aberto, atualiza preview quando o usuário muda período/tipo
  function refreshIfOpen(){
    if(!modal || !modal.classList.contains('is-open')) return;
    if(!lastOpened) return;
    try{ renderExecResult(execReport(lastOpened.rid)); }catch(_){ }
  }

  if(typeEl) typeEl.addEventListener('change', refreshIfOpen);
  if(periodEl) periodEl.addEventListener('change', refreshIfOpen);
})();