// app/static/js/financeiro_relatorios_print.js
(function () {
  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    const MOCK = window.__FR_MOCK__ || {};
    const LS_CP = "fin_cp_rows_v1";
    const LS_CR = "fin_cr_rows_v1";

    const el = (id) => document.getElementById(id);

    const els = {
      modal: el("frModal"),
      modalTitle: el("frModalTitle"),
      close: el("frModalClose"),
      run: el("frModalRun"),
      print: el("frModalPrint"),
      csv: el("frModalGhost"),

      period: el("frPeriod"),
      type: el("frType"),

      // print area
      genAt: el("frPrintGeneratedAt"),
      pPeriod: el("frPrintPeriod"),
      pType: el("frPrintType"),
      pTitle: el("frPrintTitle"),
      pDesc: el("frPrintDesc"),
      kpis: el("frPrintKpis"),

      chartSection: el("frPrintChartSection"),
      chartCanvas: el("frPrintChart"),
      chartImg: el("frPrintChartImg"),
      chartHint: el("frPrintChartHint"),

      thead: el("frPrintThead"),
      tbody: el("frPrintTbody"),
      footnote: el("frPrintFootnote"),
    };

    if (!els.modal || !els.run || !els.print || !els.csv) return;

    let currentReport = null;
    let chartInstance = null;

    let isPrinting = false;

    function isMobileModal() {
      try {
        return window.matchMedia && window.matchMedia('(max-width: 700px)').matches;
      } catch (_) {
        return false;
      }
    }

    function applyMobileModalTweaks() {
      // No mobile, o botão de CSV (Exportar) pode colidir com os demais.
      // Escondemos o botão no modal apenas no mobile.
      if (!els.csv) return;
      els.csv.style.display = isMobileModal() ? 'none' : '';
    }

    function waitImageLoaded(imgEl) {
      return new Promise((resolve) => {
        if (!imgEl) return resolve(false);
        // Se já está carregada
        if (imgEl.complete && imgEl.naturalWidth > 0) return resolve(true);
        const onDone = () => {
          imgEl.removeEventListener('load', onDone);
          imgEl.removeEventListener('error', onErr);
          resolve(true);
        };
        const onErr = () => {
          imgEl.removeEventListener('load', onDone);
          imgEl.removeEventListener('error', onErr);
          resolve(false);
        };
        imgEl.addEventListener('load', onDone, { once: true });
        imgEl.addEventListener('error', onErr, { once: true });
      });
    }

    // aplica no carregamento
    applyMobileModalTweaks();

    // reaplica quando a tela muda (ex.: rotação / resize)
    window.addEventListener('resize', () => {
      applyMobileModalTweaks();
    });

    function raf() {
      return new Promise((res) => requestAnimationFrame(() => res()));
    }

    async function waitChartPaint() {
      // Chart.js draws async; give it a couple frames.
      await raf();
      await raf();
    }

    function buildChartSummaryFromCfg(cfg) {
    try {
        if (!cfg || !cfg.data || !cfg.data.labels || !cfg.data.datasets || !cfg.data.datasets.length) return null;

        const labels = cfg.data.labels.map((lb) => String(lb ?? "—"));
        const datasets = cfg.data.datasets || [];

        // Se houver múltiplos datasets, cria linhas no formato:
        // "<Categoria> • <Série>" (ex.: "Pagar • Pendente")
        const rows = [];
        for (let di = 0; di < datasets.length; di++) {
        const ds = datasets[di] || {};
        const dsLabel = String(ds.label ?? "").trim();
        const vals = Array.isArray(ds.data) ? ds.data : [];

        for (let i = 0; i < labels.length; i++) {
            const v = Number(vals[i] ?? 0);
            const label = dsLabel ? `${labels[i]} • ${dsLabel}` : labels[i];
            rows.push({ label, value: v });
        }
        }

        const total = rows.reduce((a, r) => a + Number(r.value || 0), 0);
        const withPct = rows.map((r) => {
        const v = Number(r.value || 0);
        const pct = total > 0 ? (v / total) * 100 : 0;
        return { ...r, pct };
        });

        withPct.sort((a, b) => (b.value || 0) - (a.value || 0));
        return { total, rows: withPct };
    } catch (_) {
        return null;
    }
    }

    function renderChartSummary(cfg) {
      // Print não tem hover: mostramos valores/percentuais sempre.
      if (!els.chartSection) return;

      let box = els.chartSection.querySelector('#frPrintChartSummary');
      if (!box) {
        box = document.createElement('div');
        box.id = 'frPrintChartSummary';
        box.className = 'fr-print__chartsummary';
        els.chartSection.appendChild(box);
      }

      const sum = buildChartSummaryFromCfg(cfg);
      if (!sum || !sum.rows.length) {
        box.innerHTML = '';
        return;
      }

      // mostra no máximo 10 linhas para não estourar a página
      const top = sum.rows.slice(0, 10);
      box.innerHTML = `
        <div class="fr-print__chartsumtitle">Valores do gráfico</div>
        <div class="fr-print__chartsumgrid">
          ${top.map(r => {
            const pct = (r.pct || 0).toFixed(1).replace('.', ',');
            return `
              <div class="fr-print__chartsumrow">
                <div class="fr-print__chartsumlabel">${escapeHtml(r.label)}</div>
                <div class="fr-print__chartsumval">${escapeHtml(moneyBR(r.value))}</div>
                <div class="fr-print__chartsumpct">${escapeHtml(pct)}%</div>
              </div>
            `;
          }).join('')}
        </div>
      `;
    }

    // ---------------------------
    // helpers
    // ---------------------------
    function escapeHtml(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function moneyBR(v) {
      const n = Number(v || 0);
      return n.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function nowBR() {
      const d = new Date();
      const dd = String(d.getDate()).padStart(2, "0");
      const mm = String(d.getMonth() + 1).padStart(2, "0");
      const yy = d.getFullYear();
      const hh = String(d.getHours()).padStart(2, "0");
      const mi = String(d.getMinutes()).padStart(2, "0");
      return `${dd}/${mm}/${yy} ${hh}:${mi}`;
    }

    function parseIso(iso) {
      const d = new Date(String(iso || "") + "T00:00:00");
      return Number.isNaN(d.getTime()) ? null : d;
    }

    function loadArr(key) {
      try {
        const raw = localStorage.getItem(key);
        if (!raw) return [];
        const arr = JSON.parse(raw);
        return Array.isArray(arr) ? arr : [];
      } catch (_) {
        return [];
      }
    }

    function monthKey(dt) {
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, "0");
      return `${y}-${m}`;
    }

    function isoMonthKey(iso) {
      return typeof iso === "string" && iso.length >= 7 ? iso.slice(0, 7) : "";
    }

    function periodLabel() {
      const opt = els.period?.value || "this_month";
      const map = {
        this_month: "Este mês",
        last_month: "Mês passado",
        last_30: "Últimos 30 dias",
        custom: "Personalizado",
      };
      return map[opt] || "—";
    }

    function typeLabel() {
      const v = els.type?.value || "";
      const map = { "": "Todos", operacional: "Operacional", conferencia: "Conferência" };
      return map[v] || "—";
    }

    function closeModal() {
      els.modal.classList.remove("is-open");
      els.modal.setAttribute("aria-hidden", "true");
    }

    function openModal() {
      els.modal.classList.add("is-open");
      els.modal.setAttribute("aria-hidden", "false");
    }

    function getReportDefById(id) {
      const groups = MOCK.groups || [];
      for (const g of groups) {
        for (const it of (g.items || [])) {
          if (it.id === id) return it;
        }
      }
      return null;
    }

    function computePeriodRange() {
      const opt = els.period?.value || "this_month";
      const today = new Date();
      const start = new Date(today.getFullYear(), today.getMonth(), today.getDate());
      let from = null;
      let to = null;

      if (opt === "this_month") {
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to = new Date(today.getFullYear(), today.getMonth() + 1, 1);
      } else if (opt === "last_month") {
        from = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        to = new Date(today.getFullYear(), today.getMonth(), 1);
      } else if (opt === "last_30") {
        from = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 30);
        to = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
      } else {
        // custom (mock): cai em mês atual por enquanto
        from = new Date(today.getFullYear(), today.getMonth(), 1);
        to = new Date(today.getFullYear(), today.getMonth() + 1, 1);
      }

      return { from, to, today: start };
    }

    function filterByRange(list, from, to) {
      return list.filter((r) => {
        const d = parseIso(r.data);
        if (!d) return false;
        return d >= from && d < to;
      });
    }

    // ---------------------------
    // dataset por relatório
    // (nesta etapa, usa localStorage; se vazio, fica com tabela vazia)
    // ---------------------------
    function buildReportData(reportId) {
      const cp = loadArr(LS_CP).filter((r) => r && r.data);
      const cr = loadArr(LS_CR).filter((r) => r && r.data);

      const { from, to, today } = computePeriodRange();
      const cpR = filterByRange(cp, from, to);
      const crR = filterByRange(cr, from, to);

      // base meta
      const meta = {
        generatedAt: nowBR(),
        period: periodLabel(),
        type: typeLabel(),
      };

      // helpers
      const sum = (arr, pred) => arr.reduce((a, r) => a + (pred(r) ? Number(r.valor || 0) : 0), 0);

      if (reportId === "rep_resumo_mes") {
        const pagarOpen = sum(cpR, (r) => r.status === "open");
        const pagarDone = sum(cpR, (r) => r.status === "done");
        const receberOpen = sum(crR, (r) => r.status === "open");
        const receberDone = sum(crR, (r) => r.status === "done");
        const saldo = receberDone - pagarDone;

        const kpis = [
          { k: "Recebido", v: moneyBR(receberDone) },
          { k: "Pago", v: moneyBR(pagarDone) },
          { k: "A receber", v: moneyBR(receberOpen) },
          { k: "A pagar", v: moneyBR(pagarOpen) },
        ];

        const chart = {
          type: "doughnut",
          data: {
            labels: ["Recebido", "Pago", "A receber", "A pagar"],
            datasets: [
              { data: [receberDone, pagarDone, receberOpen, pagarOpen] }
            ],
          },
          options: {
            responsive: true,
            plugins: { legend: { position: "bottom" } },
            cutout: "60%",
          },
        };

        const rows = [
          ["Recebido", moneyBR(receberDone)],
          ["Pago", moneyBR(pagarDone)],
          ["A receber", moneyBR(receberOpen)],
          ["A pagar", moneyBR(pagarOpen)],
          ["Saldo (recebido - pago)", moneyBR(saldo)],
        ];

        return {
          meta,
          kpis,
          chart,
          table: {
            head: ["Indicador", "Valor"],
            rows,
            alignRightCols: [1],
            highlightLastRow: true,
          },
          footnote: `Período: ${meta.period}. Valores calculados a partir do armazenamento local (etapa sem BD).`,
        };
      }

      if (reportId === "rep_status") {
        const pagarOpen = sum(cpR, (r) => r.status === "open");
        const pagarDone = sum(cpR, (r) => r.status === "done");
        const receberOpen = sum(crR, (r) => r.status === "open");
        const receberDone = sum(crR, (r) => r.status === "done");

        const chart = {
          type: "bar",
          data: {
            labels: ["Pagar", "Receber"],
            datasets: [
              { label: "Pendente", data: [pagarOpen, receberOpen] },
              { label: "Concluído", data: [pagarDone, receberDone] },
            ],
          },
          options: {
            responsive: true,
            plugins: { legend: { position: "bottom" } },
            scales: { x: { stacked: true }, y: { stacked: true } },
          },
        };

        const rows = [
          ["Pagar • Pendente", moneyBR(pagarOpen)],
          ["Pagar • Pago", moneyBR(pagarDone)],
          ["Receber • Pendente", moneyBR(receberOpen)],
          ["Receber • Recebido", moneyBR(receberDone)],
        ];

        return {
          meta,
          kpis: [],
          chart,
          table: { head: ["Status", "Valor"], rows, alignRightCols: [1] },
          footnote: `Comparativo por status no período. (Stacked bar)`,
        };
      }

      if (reportId === "rep_pagar_aberto") {
        const mk = monthKey(new Date());
        const list = cp
          .filter((r) => r.status === "open" && isoMonthKey(r.data) === mk)
          .sort((a, b) => (parseIso(a.data)?.getTime() || 9e15) - (parseIso(b.data)?.getTime() || 9e15))
          .slice(0, 50);

        const total = list.reduce((acc, r) => acc + Number(r.valor || 0), 0);

        const chart = {
          type: "doughnut",
          data: {
            labels: list.map((r) => r.conta || "—").slice(0, 8),
            datasets: [{ data: list.map((r) => Number(r.valor || 0)).slice(0, 8) }],
          },
          options: {
            responsive: true,
            plugins: { legend: { position: "bottom" } },
            cutout: "60%",
          },
        };

        const rows = list.map((r) => [
          r.conta || "",
          r.imovel || "",
          r.categoria || "",
          r.data || "",
          moneyBR(r.valor),
        ]);

        return {
          meta,
          kpis: [{ k: "Total pendente", v: moneyBR(total) }],
          chart,
          table: {
            head: ["Conta", "Imóvel", "Categoria", "Vencimento", "Valor"],
            rows,
            alignRightCols: [4],
          },
          footnote: `Lista de contas a pagar em aberto (mês atual).`,
        };
      }

      if (reportId === "rep_receber_aberto") {
        const mk = monthKey(new Date());
        const list = cr
          .filter((r) => r.status === "open" && isoMonthKey(r.data) === mk)
          .sort((a, b) => (parseIso(a.data)?.getTime() || 9e15) - (parseIso(b.data)?.getTime() || 9e15))
          .slice(0, 50);

        const total = list.reduce((acc, r) => acc + Number(r.valor || 0), 0);

        const chart = {
          type: "doughnut",
          data: {
            labels: list.map((r) => r.cliente || "—").slice(0, 8),
            datasets: [{ data: list.map((r) => Number(r.valor || 0)).slice(0, 8) }],
          },
          options: {
            responsive: true,
            plugins: { legend: { position: "bottom" } },
            cutout: "60%",
          },
        };

        const rows = list.map((r) => [
          r.cliente || "",
          r.forma || "",
          r.processo || "",
          r.data || "",
          moneyBR(r.valor),
        ]);

        return {
          meta,
          kpis: [{ k: "Total a receber", v: moneyBR(total) }],
          chart,
          table: {
            head: ["Cliente", "Forma", "Processo", "Vencimento", "Valor"],
            rows,
            alignRightCols: [4],
          },
          footnote: `Lista de contas a receber em aberto (mês atual).`,
        };
      }

      // fallback genérico
      return {
        meta,
        kpis: [],
        chart: null,
        table: { head: ["Info"], rows: [["Relatório ainda não modelado nesta etapa."]] },
        footnote: "—",
      };
    }

    // ---------------------------
    // render
    // ---------------------------
    function renderKpis(kpis) {
      if (!els.kpis) return;
      if (!kpis || !kpis.length) {
        els.kpis.innerHTML = "";
        return;
      }

      // garante até 4 colunas no print (CSS já ajusta)
      els.kpis.innerHTML = kpis
        .slice(0, 4)
        .map((x) => {
          return `
            <div class="fr-kpi">
              <div class="fr-kpi__k">${escapeHtml(x.k)}</div>
              <div class="fr-kpi__v">${escapeHtml(x.v)}</div>
            </div>
          `;
        })
        .join("");
    }

    function renderTable(tbl) {
      if (!els.thead || !els.tbody) return;

      const head = (tbl && tbl.head) || [];
      const rows = (tbl && tbl.rows) || [];
      const rightCols = new Set((tbl && tbl.alignRightCols) || []);
      const highlightLast = !!(tbl && tbl.highlightLastRow);

      els.thead.innerHTML = head.length
        ? `<tr>${head.map((h, i) => `<th>${escapeHtml(h)}</th>`).join("")}</tr>`
        : "";

      els.tbody.innerHTML = rows.length
        ? rows
            .map((r, ri) => {
                const cols = Array.isArray(r) ? r : [r];
                const isLast = ri === rows.length - 1;
                const trClass = (highlightLast && isLast) ? ' class="is-total"' : '';
                return `<tr${trClass}>${cols
                    .map((c, i) => {
                    const cls = rightCols.has(i) ? "t-right" : "";
                    return `<td class="${cls}">${escapeHtml(c)}</td>`;
                    })
                    .join("")}</tr>`;
            })
            .join("")
        : `<tr><td>Nenhum dado no período selecionado.</td></tr>`;
    }

    function destroyChart() {
      try {
        if (chartInstance && typeof chartInstance.destroy === "function") chartInstance.destroy();
      } catch (_) {}
      chartInstance = null;
    }

    function renderChart(cfg) {
      destroyChart();

      if (!els.chartSection || !els.chartCanvas || !els.chartHint) return;

      if (!cfg) {
        els.chartSection.hidden = true;
        return;
      }

      // Se não tiver Chart.js, imprime sem gráfico
      if (!window.Chart) {
        els.chartSection.hidden = false;
        els.chartHint.hidden = false;
        renderChartSummary(cfg);
        return;
      }

      els.chartHint.hidden = true;
      els.chartSection.hidden = false;

      const ctx = els.chartCanvas.getContext("2d");
      chartInstance = new window.Chart(ctx, cfg);
      renderChartSummary(cfg);
    }

    async function preparePrintImage() {
      // converte chart/canvas => img (para PDF consistente)
      if (!els.chartCanvas || !els.chartImg) return;
      try {
        if (!window.Chart || !chartInstance) return;

        // força o Chart a finalizar o desenho antes do snapshot
        if (typeof chartInstance.resize === 'function') {
          chartInstance.resize();
        }
        if (typeof chartInstance.update === 'function') {
          chartInstance.update();
        }

        // aguarda alguns frames (Safari/Chromium)
        await waitChartPaint();

        // Preferir API do Chart.js quando disponível
        let dataUrl = '';
        if (typeof chartInstance.toBase64Image === 'function') {
          dataUrl = chartInstance.toBase64Image();
        }
        if (!dataUrl) {
          dataUrl = els.chartCanvas.toDataURL('image/png', 1.0);
        }

        if (dataUrl && dataUrl.startsWith('data:image')) {
          els.chartImg.src = dataUrl;
          // Garante que o navegador inicie o carregamento imediatamente
          await raf();
          await waitImageLoaded(els.chartImg);
        }
      } catch (_) {}
    }

    function exportCSV(tbl) {
      const head = (tbl && tbl.head) || [];
      const rows = (tbl && tbl.rows) || [];
      const csv = [
        head.map((s) => `"${String(s).replaceAll('"', '""')}"`).join(";"),
        ...rows.map((r) =>
          (Array.isArray(r) ? r : [r])
            .map((s) => `"${String(s ?? "").replaceAll('"', '""')}"`)
            .join(";")
        ),
      ].join("\n");

      const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `relatorio_${currentReport?.id || "export"}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }

    function renderAll() {
      if (!currentReport) return;

      const def = getReportDefById(currentReport.id);
      if (els.modalTitle) els.modalTitle.textContent = def?.name || "Relatório";

      const data = buildReportData(currentReport.id);

      if (els.genAt) els.genAt.textContent = data.meta.generatedAt || "—";
      if (els.pPeriod) els.pPeriod.textContent = data.meta.period || "—";
      if (els.pType) els.pType.textContent = data.meta.type || "—";

      if (els.pTitle) els.pTitle.textContent = def?.name || "Relatório";
      if (els.pDesc) els.pDesc.textContent = def?.desc || "—";

      if (els.footnote) els.footnote.textContent = data.footnote || "";

      renderKpis(data.kpis);
      renderChart(data.chart);
      renderTable(data.table);

      // captura depois que o gráfico tiver tempo de pintar
      (async () => {
        await waitChartPaint();
        await preparePrintImage();
      })();

      // guarda última tabela pro CSV
      currentReport._lastTable = data.table;
    }

    // ---------------------------
    // events
    // ---------------------------
    // document.addEventListener("click", (e) => {
    //     if (e.target.closest('.fin-rep-card__fav') || e.target.closest('[data-action="fav"]')) return;
    //   const card = e.target.closest(".fin-rep-card[data-report-id]");
    //   if (!card) return;

    //   const id = card.getAttribute("data-report-id");
    //   currentReport = { id };

    //   openModal();
    //   renderAll();
    // });

    if (els.close) els.close.addEventListener("click", closeModal);

    // Executar (re-render com filtros atuais)
    els.run.addEventListener("click", () => {
      if(!window.__FR_API__) return;
      const st = window.__FR_API__.getState();
      if(!st.lastOpened) return;
      window.__FR_API__.ensureRendered(st.lastOpened.rid);
    });

    // Exportar CSV real
    els.csv.addEventListener("click", () => {
      if(!window.__FR_API__) return;
      const st = window.__FR_API__.getState();
      if(!st.lastExec) return;

      // Reaproveita a tabela já renderizada pelo JS principal
      const head = (st.lastExec.columns || []).map(c => c.label || '');
      const rows = (st.lastExec.rows || []).map(r => {
        return (st.lastExec.columns || []).map(c => {
          const v = typeof c.value === 'function' ? c.value(r) : (r[c.key] ?? '');
          return v;
        });
      });

      const csv = [
        head.map((s) => `"${String(s).replaceAll('"', '""')}"`).join(";"),
        ...rows.map((r) =>
          (Array.isArray(r) ? r : [r])
            .map((s) => `"${String(s ?? "").replaceAll('"', '""')}"`)
            .join(";")
        ),
      ].join("\n");

      const blob = new Blob([csv], { type: "text/csv;charset=utf-8" });
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      const safe = String(st.lastExec.title || 'relatorio').toLowerCase().replace(/[^a-z0-9\-]+/gi, '_');
      a.href = url;
      a.download = `${safe}.csv`;
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    });

    // Impressão / PDF
    els.print.addEventListener('click', async () => {
      if(!window.__FR_API__ || isPrinting) return;
      isPrinting = true;

      try{
        const st = window.__FR_API__.getState();
        if(!st.lastOpened) return;

        // garante que o conteúdo do modal está atualizado pelo JS principal
        window.__FR_API__.ensureRendered(st.lastOpened.rid);

        // gera a imagem do gráfico para o @media print
        await waitChartPaint();
        await preparePrintImage();

        // summary do gráfico (usa o cfg do Chart.js já existente)
        try{
          if(chartInstance && chartInstance.config){
            renderChartSummary(chartInstance.config);
          }
        }catch(_){}

        if (els.chartImg && els.chartImg.src) {
          await waitImageLoaded(els.chartImg);
        }

        await raf();
        window.print();

      } finally{
        setTimeout(() => { isPrinting = false; }, 500);
      }
    });

    // Atualiza preview quando mudar filtros (sem precisar clicar executar)
    if (els.period) els.period.addEventListener("change", () => currentReport && renderAll());
    if (els.type) els.type.addEventListener("change", () => currentReport && renderAll());

    // Segurança: ao imprimir, garante a imagem e chart summary
    window.addEventListener('beforeprint', () => {
      try {
        applyMobileModalTweaks();
        renderAll();
        // best-effort (não bloqueia o print aqui)
        preparePrintImage();
      } catch (_) {}
    });
  });
})();

