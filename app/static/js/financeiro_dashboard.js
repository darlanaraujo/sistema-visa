// app/static/js/financeiro_dashboard.js
(function () {
  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    // Detecta se é o Dashboard Financeiro pela presença dos blocos característicos
    const root = document.querySelector(".fin-page");
    const kpiWrap = document.querySelector(".fin-toolbar.fin-dash-kpis");
    const listsWrap = document.querySelector(".fin-dash-lists");
    const agendaWrap = document.querySelector(".fin-dash-acc--agenda");

    if (!root || (!kpiWrap && !listsWrap && !agendaWrap)) return;

    const LS_CP = "fin_cp_rows_v1";
    const LS_CR = "fin_cr_rows_v1";

    // ---------- helpers ----------
    function moneyBR(v) {
      const n = Number(v || 0);
      // Alguns browsers inserem NBSP/narrow-NBSP entre "R$" e o número.
      return n
        .toLocaleString("pt-BR", { style: "currency", currency: "BRL" })
        .replace(/\u00A0/g, " ")
        .replace(/\u202F/g, " ");
    }

    function pad2(n) {
      return String(n).padStart(2, "0");
    }

    function toBRddmm(iso) {
      if (!iso || typeof iso !== "string" || iso.length < 10) return "";
      return iso.slice(8, 10) + "/" + iso.slice(5, 7);
    }

    function toBRddmmyyyy(iso) {
      if (!iso || typeof iso !== "string" || iso.length < 10) return "";
      return iso.slice(8, 10) + "/" + iso.slice(5, 7) + "/" + iso.slice(0, 4);
    }

    function escapeHtml(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function todayAtMidnight() {
      const now = new Date();
      return new Date(now.getFullYear(), now.getMonth(), now.getDate());
    }

    function parseIsoToDate(iso) {
      const d = new Date(String(iso || "") + "T00:00:00");
      return Number.isNaN(d.getTime()) ? null : d;
    }

    function isSameDay(a, b) {
      return (
        a &&
        b &&
        a.getFullYear() === b.getFullYear() &&
        a.getMonth() === b.getMonth() &&
        a.getDate() === b.getDate()
      );
    }

    function isOverdueOpen(iso, status) {
      if (status === "done") return false;
      const d = parseIsoToDate(iso);
      if (!d) return false;
      return d < todayAtMidnight();
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
      return dt.getFullYear() + "-" + pad2(dt.getMonth() + 1);
    }

    function isoMonthKey(iso) {
      return typeof iso === "string" && iso.length >= 7 ? iso.slice(0, 7) : "";
    }

    function safeTime(iso) {
      const d = parseIsoToDate(iso);
      return d ? d.getTime() : Number.POSITIVE_INFINITY;
    }

    // ---------- ler dados ----------
    function readData() {
      const cp = loadArr(LS_CP).filter((r) => r && r.data);
      const cr = loadArr(LS_CR).filter((r) => r && r.data);
      return { cp, cr };
    }

   // =========================================================
  // CHARTS (dashboard) — cards com head padrão + canvas only
  // =========================================================
  const PAL = [
    "rgba(59,130,246,.75)",
    "rgba(16,185,129,.75)",
    "rgba(245,158,11,.75)",
    "rgba(239,68,68,.75)",
    "rgba(168,85,247,.75)",
    "rgba(14,165,233,.75)",
    "rgba(234,88,12,.75)",
    "rgba(20,184,166,.75)",
    "rgba(100,116,139,.75)",
    "rgba(236,72,153,.70)",
  ];

  function colors(n) {
    const out = [];
    for (let i = 0; i < n; i++) out.push(PAL[i % PAL.length]);
    return out;
  }

  function pctOf(value, total) {
    const v = Number(value || 0);
    const t = Number(total || 0);
    if (t <= 0) return 0;
    return (v / t) * 100;
  }

  function sumBy(list, keyFn, valFn) {
    const out = new Map();
    list.forEach((it) => {
      const k = String(keyFn(it) || "—").trim() || "—";
      const v = Number(valFn(it) || 0);
      out.set(k, (out.get(k) || 0) + v);
    });
    return out;
  }

  function sortTop(map, limit) {
    const arr = Array.from(map.entries()).map(([k, v]) => ({ label: k, value: Number(v || 0) }));
    arr.sort((a, b) => (b.value || 0) - (a.value || 0));
    return limit && arr.length > limit ? arr.slice(0, limit) : arr;
  }

  function applyTooltipMoneyAndPct(cfg) {
    if (!cfg || !cfg.options) cfg.options = {};
    if (!cfg.options.plugins) cfg.options.plugins = {};
    if (!cfg.options.plugins.tooltip) cfg.options.plugins.tooltip = {};
    cfg.options.plugins.tooltip.callbacks = cfg.options.plugins.tooltip.callbacks || {};

    cfg.options.plugins.tooltip.callbacks.label = function (ctx) {
      const label = ctx.label || ctx.dataset?.label || "";
      const raw = ctx.raw != null ? Number(ctx.raw) : Number(ctx.parsed?.y || ctx.parsed || 0);

      const ds = ctx.dataset || {};
      const data = Array.isArray(ds.data) ? ds.data : [];
      const total = data.reduce((a, b) => a + Number(b || 0), 0);
      const pct = pctOf(raw, total);
      const pctTxt = total > 0 ? ` (${pct.toFixed(1).replace(".", ",")}%)` : "";

      // Em barras (histórico), o % pode confundir. Mantém só em pizza.
      const isPie = (ctx.chart?.config?.type === "doughnut" || ctx.chart?.config?.type === "pie");
      return isPie ? `${label}: ${moneyBR(raw)}${pctTxt}` : `${label}: ${moneyBR(raw)}`;
    };

    return cfg;
  }

  // container
  function ensureChartsContainer() {
    let grid = document.querySelector(".fin-dash-grid--charts");
    if (grid) return grid;

    grid = document.createElement("div");
    grid.className = "fin-dash-grid fin-dash-grid--charts";
    grid.setAttribute("data-auto", "1");

    const anchor = document.querySelector(".fin-dash-grid--agenda") || agendaWrap?.closest(".fin-panel") || null;
    if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(grid, anchor);
    else root.appendChild(grid);

    return grid;
  }

  function clearNode(n) {
    if (!n) return;
    n.innerHTML = "";
  }

  function last12MonthKeys() {
    const today = todayAtMidnight();
    const out = [];
    for (let i = 11; i >= 0; i--) {
      const d = new Date(today.getFullYear(), today.getMonth() - i, 1);
      const mk = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
      out.push(mk);
    }
    return out;
  }

  // renderer (head padrão + canvas)
  function renderDashChart(container, opt) {
    if (!container) return;

    const card = document.createElement("div");
    card.className = "fin-dash-chart" + (opt.wide ? " is-wide" : "");

    // head: ícone + título + badge link
    card.innerHTML = `
      <div class="fin-dash-sechead">
        <div class="fin-dash-sechead__left">
          <i class="${escapeHtml(opt.icon || "fa-solid fa-chart-column")}"></i>
          <span>${escapeHtml(opt.title || "Gráfico")}</span>
        </div>
        ${
          opt.badgeHref
            ? `<a class="fin-badge fin-badge--link fin-badge--pt" href="${escapeHtml(opt.badgeHref)}">${escapeHtml(
                opt.badgeText || "Ver relatório"
              )}</a>`
            : `<span class="fin-badge fin-badge--pt">${escapeHtml(opt.badgeText || "Resumo")}</span>`
        }
      </div>

      <div class="fin-dash-chart__body">
        <canvas class="fin-dash-cv" id="${escapeHtml(opt.id)}_cv"></canvas>
        <div class="fin-dash-charthint" style="display:none;">Chart.js não disponível.</div>
      </div>
    `;

    container.appendChild(card);

    const cv = card.querySelector(`#${CSS.escape(opt.id)}_cv`);
    const hint = card.querySelector(".fin-dash-charthint");

    if (!window.Chart || !cv) {
      if (hint) hint.style.display = "block";
      return;
    }

    try {
      renderDashChart._instances = renderDashChart._instances || {};
      if (renderDashChart._instances[opt.id]) renderDashChart._instances[opt.id].destroy();
    } catch (_) {}

    try {
      const ctx = cv.getContext("2d");
      renderDashChart._instances[opt.id] = new window.Chart(ctx, opt.chartCfg);
    } catch (_) {
      if (hint) hint.style.display = "block";
    }
  }

  function renderDashboardCharts(cp, cr) {
    const grid = ensureChartsContainer();
    if (!grid) return;

    clearNode(grid);

    const now = new Date();
    const mk = monthKey(now);

    const asVal = (x) => Number(x && x.valor != null ? x.valor : 0);

    // 1) Entradas x Saídas — últimos 12 meses
    const keys12 = last12MonthKeys();
    const bucket12 = {};
    keys12.forEach((k) => (bucket12[k] = { in: 0, out: 0 }));

    const addMonth = (iso, kind, val) => {
      const d = parseIsoToDate(iso);
      if (!d) return;
      const key = d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
      if (!bucket12[key]) return;
      if (kind === "in") bucket12[key].in += val;
      else bucket12[key].out += val;
    };

    cp.forEach((p) => addMonth(p.data, "out", asVal(p)));
    cr.forEach((r) => addMonth(r.data, "in", asVal(r)));

    const in12 = keys12.map((k) => bucket12[k].in);
    const out12 = keys12.map((k) => bucket12[k].out);

    const histCfg = {
      type: "bar",
      data: {
        labels: keys12.map((k) => k.slice(5, 7) + "/" + k.slice(0, 4)),
        datasets: [
          { label: "Entradas", data: in12, backgroundColor: "rgba(16,185,129,.70)" },
          { label: "Saídas", data: out12, backgroundColor: "rgba(239,68,68,.65)" },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "bottom" },
          tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${moneyBR(ctx.parsed.y)}` } },
        },
      },
    };

    renderDashChart(grid, {
      id: "fd_hist12",
      wide: true,
      icon: "fa-solid fa-chart-column",
      title: "Entradas x Saídas (12 meses)",
      badgeText: "Período anual",
      badgeHref: "/sistema-visa/app/templates/financeiro_relatorios.php?rid=rep_fluxo",
      chartCfg: histCfg,
    });

    // 2) Despesas por Imóveis (mês atual)
    const cpMonth = cp.filter((r) => isoMonthKey(r.data) === mk);
    const byImovel = sumBy(cpMonth, (r) => r.imovel || "—", asVal);
    let imSeries = sortTop(byImovel, 9);
    if (imSeries.length > 8) {
      const top8 = imSeries.slice(0, 8);
      const rest = imSeries.slice(8).reduce((a, b) => a + Number(b.value || 0), 0);
      imSeries = top8.concat([{ label: "Outros", value: rest }]);
    }

    const imCfg = applyTooltipMoneyAndPct({
      type: "doughnut",
      data: {
        labels: imSeries.map((x) => x.label),
        datasets: [{ data: imSeries.map((x) => x.value), backgroundColor: colors(imSeries.length || 1), borderWidth: 0 }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } }, cutout: "58%" },
    });

    renderDashChart(grid, {
      id: "fd_imoveis",
      wide: false,
      icon: "fa-solid fa-house",
      title: "Despesas por Imóveis",
      badgeText: `Mês: ${mk}`,
      badgeHref: "/sistema-visa/app/templates/financeiro_relatorios.php?rid=rep_imoveis",
      chartCfg: imCfg,
    });

    // 3) Despesas por Categorias (mês atual)
    const byCat = sumBy(cpMonth, (r) => r.categoria || "—", asVal);
    let catSeries = sortTop(byCat, 9);
    if (catSeries.length > 8) {
      const top8 = catSeries.slice(0, 8);
      const rest = catSeries.slice(8).reduce((a, b) => a + Number(b.value || 0), 0);
      catSeries = top8.concat([{ label: "Outros", value: rest }]);
    }

    const catCfg = applyTooltipMoneyAndPct({
      type: "doughnut",
      data: {
        labels: catSeries.map((x) => x.label),
        datasets: [{ data: catSeries.map((x) => x.value), backgroundColor: colors(catSeries.length || 1), borderWidth: 0 }],
      },
      options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: "bottom" } }, cutout: "58%" },
    });

    renderDashChart(grid, {
      id: "fd_categorias",
      wide: false,
      icon: "fa-solid fa-tags",
      title: "Despesas por Categorias",
      badgeText: `Mês: ${mk}`,
      badgeHref: "/sistema-visa/app/templates/financeiro_relatorios.php?rid=rep_categorias",
      chartCfg: catCfg,
    });
  }

    // ---------- KPIs (mês atual) ----------
    function renderKPIs(cp, cr) {
      if (!kpiWrap) return;

      const values = kpiWrap.querySelectorAll(".fin-dash-kpi .fin-kpi__value");
      if (!values || values.length < 4) return;

      const now = new Date();
      const mk = monthKey(now);

      const cpMonth = cp.filter((r) => isoMonthKey(r.data) === mk);
      const crMonth = cr.filter((r) => isoMonthKey(r.data) === mk);

      const sumByStatus = (list, st) =>
        list.reduce((acc, r) => acc + (r.status === st ? Number(r.valor || 0) : 0), 0);

      const pagarOpen = sumByStatus(cpMonth, "open");
      const pagarDone = sumByStatus(cpMonth, "done");
      const receberOpen = sumByStatus(crMonth, "open");
      const receberDone = sumByStatus(crMonth, "done");

      values[0].textContent = moneyBR(pagarOpen);
      values[1].textContent = moneyBR(pagarDone);
      values[2].textContent = moneyBR(receberOpen);
      values[3].textContent = moneyBR(receberDone);
    }

    // ---------- Pendências ----------
    function renderPendencias(cp, cr) {
      if (!listsWrap) return;

      const pagarBox = listsWrap.querySelectorAll(".fin-dash-listbox")[0];
      const receberBox = listsWrap.querySelectorAll(".fin-dash-listbox")[1];

      const pagarMini = pagarBox ? pagarBox.querySelector(".fin-dash-mini") : null;
      const receberMini = receberBox ? receberBox.querySelector(".fin-dash-mini") : null;

      if (!pagarMini && !receberMini) return;

      const LIMIT = 5;

      const pagar = cp
        .filter((r) => r && r.status === "open" && r.data)
        .sort((a, b) => {
          const ao = isOverdueOpen(a.data, a.status);
          const bo = isOverdueOpen(b.data, b.status);
          if (ao !== bo) return ao ? -1 : 1;
          const da = safeTime(a.data);
          const db = safeTime(b.data);
          if (da !== db) return da - db;
          return String(a.conta || "").localeCompare(String(b.conta || ""), "pt-BR", { sensitivity: "base" });
        })
        .slice(0, LIMIT);

      const receber = cr
        .filter((r) => r && r.status === "open" && r.data)
        .sort((a, b) => {
          const ao = isOverdueOpen(a.data, a.status);
          const bo = isOverdueOpen(b.data, b.status);
          if (ao !== bo) return ao ? -1 : 1;
          const da = safeTime(a.data);
          const db = safeTime(b.data);
          if (da !== db) return da - db;
          return String(a.cliente || "").localeCompare(String(b.cliente || ""), "pt-BR", { sensitivity: "base" });
        })
        .slice(0, LIMIT);

      if (pagarMini) {
        if (!pagar.length) {
          pagarMini.innerHTML = `
            <div class="fin-dash-mini__row is-pay">
              <div class="fin-dash-mini__main">
                <div class="fin-dash-mini__title">Nenhuma conta em aberto.</div>
                <div class="fin-dash-mini__meta">—</div>
              </div>
              <div class="fin-dash-mini__right">
                <div class="fin-dash-mini__amt">${escapeHtml(moneyBR(0))}</div>
              </div>
            </div>
          `;
        } else {
          pagarMini.innerHTML = pagar.map((c) => {
            const overdue = isOverdueOpen(c.data, c.status);
            const cls = `fin-dash-mini__row is-pay ${overdue ? "is-overdue" : ""}`.trim();
            return `
              <div class="${cls}">
                <div class="fin-dash-mini__main">
                  <div class="fin-dash-mini__title">${escapeHtml(c.conta || "")}</div>
                  <div class="fin-dash-mini__meta">${escapeHtml(c.imovel || "")} • Venc: ${escapeHtml(toBRddmm(c.data))}</div>
                </div>
                <div class="fin-dash-mini__right">
                  <div class="fin-dash-mini__amt">${escapeHtml(moneyBR(c.valor))}</div>
                </div>
              </div>
            `;
          }).join("");
        }
      }

      if (receberMini) {
        if (!receber.length) {
          receberMini.innerHTML = `
            <div class="fin-dash-mini__row is-rec">
              <div class="fin-dash-mini__main">
                <div class="fin-dash-mini__title">Nenhum recebível em aberto.</div>
                <div class="fin-dash-mini__meta">—</div>
              </div>
              <div class="fin-dash-mini__right">
                <div class="fin-dash-mini__amt">${escapeHtml(moneyBR(0))}</div>
              </div>
            </div>
          `;
        } else {
          receberMini.innerHTML = receber.map((r) => {
            const overdue = isOverdueOpen(r.data, r.status);
            const cls = `fin-dash-mini__row is-rec ${overdue ? "is-overdue" : ""}`.trim();
            return `
              <div class="${cls}">
                <div class="fin-dash-mini__main">
                  <div class="fin-dash-mini__title">${escapeHtml(r.cliente || "")} • ${escapeHtml(r.forma || "")}</div>
                  <div class="fin-dash-mini__meta">Venc: ${escapeHtml(toBRddmm(r.data))}</div>
                </div>
                <div class="fin-dash-mini__right">
                  <div class="fin-dash-mini__amt">${escapeHtml(moneyBR(r.valor))}</div>
                </div>
              </div>
            `;
          }).join("");
        }
      }
    }

    // ---------- Agenda (14 dias) ----------
    function renderAgenda(cp, cr) {
      if (!agendaWrap) return;

      const today = todayAtMidnight();
      const tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
      const next14 = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 14);

      const payOpen = cp.filter((r) => r && r.status === "open" && r.data && parseIsoToDate(r.data));
      const recOpen = cr.filter((r) => r && r.status === "open" && r.data && parseIsoToDate(r.data));

      const items = [];

      for (const p of payOpen) {
        const d = parseIsoToDate(p.data);
        items.push({
          kind: "pagar",
          date: d,
          iso: p.data,
          title: p.conta || "",
          amount: moneyBR(p.valor),
          meta: (p.imovel ? p.imovel + " • " : "") + "Venc: " + toBRddmmyyyy(p.data),
        });
      }

      for (const r of recOpen) {
        const d = parseIsoToDate(r.data);
        items.push({
          kind: "receber",
          date: d,
          iso: r.data,
          title: (r.cliente || "") + (r.forma ? " • " + r.forma : ""),
          amount: moneyBR(r.valor),
          meta: (r.processo ? "Proc: " + r.processo + " • " : "") + "Venc: " + toBRddmmyyyy(r.data),
        });
      }

      const overdue = items.filter((it) => it.date < today);
      const todayList = items.filter((it) => isSameDay(it.date, today));
      const tomorrowList = items.filter((it) => isSameDay(it.date, tomorrow));
      const next14List = items.filter((it) => it.date > tomorrow && it.date <= next14);

      const sortAgenda = (a, b) => {
        const da = a.date.getTime();
        const db = b.date.getTime();
        if (da !== db) return da - db;
        return String(a.title || "").localeCompare(String(b.title || ""), "pt-BR", { sensitivity: "base" });
      };

      overdue.sort(sortAgenda);
      todayList.sort(sortAgenda);
      tomorrowList.sort(sortAgenda);
      next14List.sort(sortAgenda);

      function renderDetail(label, open, list, scroll) {
        const bodyCls = `fin-dash-acc__body ${scroll ? "is-scroll" : ""}`.trim();

        const rowsHtml = list.length
          ? list.map((it) => {
              const isPay = it.kind === "pagar";
              return `
                <div class="fin-dash-agenda-row ${isPay ? "is-pay" : "is-rec"}">
                  <div class="fin-dash-agenda-row__left">
                    <i class="${isPay ? "fa-solid fa-receipt" : "fa-solid fa-file-invoice-dollar"}"></i>
                    <div>
                      <div class="fin-dash-agenda-row__title">${escapeHtml(it.title)}</div>
                      <div class="fin-dash-agenda-row__meta">${escapeHtml(it.meta)}</div>
                    </div>
                  </div>
                  <div class="fin-dash-agenda-row__amt">${escapeHtml(it.amount)}</div>
                </div>
              `;
            }).join("")
          : `
            <div class="fin-dash-agenda-row">
              <div class="fin-dash-agenda-row__left">
                <i class="fa-solid fa-circle-info"></i>
                <div>
                  <div class="fin-dash-agenda-row__title">Nenhum evento.</div>
                  <div class="fin-dash-agenda-row__meta">—</div>
                </div>
              </div>
              <div class="fin-dash-agenda-row__amt">—</div>
            </div>
          `;

        return `
          <details class="fin-dash-acc__item" ${open ? "open" : ""}>
            <summary class="fin-dash-acc__sum">
              <span>${escapeHtml(label)}</span>
              <i class="fa-solid fa-chevron-down"></i>
            </summary>
            <div class="${bodyCls}">
              ${rowsHtml}
            </div>
          </details>
        `;
      }

      const html =
        renderDetail(`Vencidas (${overdue.length})`, true, overdue, false) +
        renderDetail(`Hoje (${todayList.length})`, true, todayList, false) +
        renderDetail(`Amanhã (${tomorrowList.length})`, false, tomorrowList, false) +
        renderDetail(`Próximos 14 dias (${next14List.length})`, false, next14List, true);

      agendaWrap.innerHTML = html;
    }

    // ---------- init ----------
    function run() {
      const { cp, cr } = readData();

      // Se não houver nada ainda no LS, mantém os mocks do PHP sem alterar
      if (!cp.length && !cr.length) return;

      renderKPIs(cp, cr);
      renderDashboardCharts(cp, cr); // ✅ NOVO
      renderPendencias(cp, cr);
      renderAgenda(cp, cr);
    }

    run();

    // Atualiza em tempo real quando salvar em outra aba
    window.addEventListener("storage", (e) => {
      if (e.key === LS_CP || e.key === LS_CR) run();
    });
  });
})();