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
      // Isso pode quebrar alinhamentos/overflow dependendo do CSS.
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

    // ---------- KPIs (mês atual) ----------
    function renderKPIs(cp, cr) {
      if (!kpiWrap) return;

      // Os 4 KPIs são os 4 .fin-kpi__value (na ordem atual do layout)
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

      // Mantém layout: só troca o texto
      values[0].textContent = moneyBR(pagarOpen);
      values[1].textContent = moneyBR(pagarDone);
      values[2].textContent = moneyBR(receberOpen);
      values[3].textContent = moneyBR(receberDone);
    }

    // ---------- Pendências (somente OPEN; prioriza vencidas e próximas) ----------
    function renderPendencias(cp, cr) {
      if (!listsWrap) return;

      const pagarBox = listsWrap.querySelectorAll(".fin-dash-listbox")[0];
      const receberBox = listsWrap.querySelectorAll(".fin-dash-listbox")[1];

      const pagarMini = pagarBox ? pagarBox.querySelector(".fin-dash-mini") : null;
      const receberMini = receberBox ? receberBox.querySelector(".fin-dash-mini") : null;

      if (!pagarMini && !receberMini) return;

      // Mantém a experiência do mock:
      // - mostra SOMENTE pendentes (open)
      // - destaca vencidas
      // - não restringe por mês (porque o mock mistura datas)
      // - limita para ficar “preview” (5 itens por lista)
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
          // Placeholder mantendo o mesmo “formato” da lista
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
          pagarMini.innerHTML = pagar
            .map((c) => {
              const overdue = isOverdueOpen(c.data, c.status);
              const cls = `fin-dash-mini__row is-pay ${overdue ? "is-overdue" : ""}`.trim();

              // Replica exatamente a estrutura do mock (home.php)
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
            })
            .join("");
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
          receberMini.innerHTML = receber
            .map((r) => {
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
            })
            .join("");
        }
      }
    }

    // ---------- Agenda (14 dias) ----------
    function renderAgenda(cp, cr) {
      if (!agendaWrap) return;

      const today = todayAtMidnight();
      const tomorrow = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 1);
      const next14 = new Date(today.getFullYear(), today.getMonth(), today.getDate() + 14);

      // Base: somente OPEN e com data válida
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

      // buckets
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

      function renderDetail(key, label, open, list, scroll) {
        const bodyCls = `fin-dash-acc__body ${scroll ? "is-scroll" : ""}`.trim();

        const rowsHtml = list.length
          ? list
              .map((it) => {
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
              })
              .join("")
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
        renderDetail("overdue", `Vencidas (${overdue.length})`, true, overdue, false) +
        renderDetail("today", `Hoje (${todayList.length})`, true, todayList, false) +
        renderDetail("tomorrow", `Amanhã (${tomorrowList.length})`, false, tomorrowList, false) +
        renderDetail("next14", `Próximos 14 dias (${next14List.length})`, false, next14List, true);

      // Mantém layout: só substitui o conteúdo da agenda, preservando classes do container
      agendaWrap.innerHTML = html;
    }

    // ---------- init ----------
    function run() {
      const { cp, cr } = readData();

      // Se não houver nada ainda no LS, mantém os mocks do PHP sem alterar
      if (!cp.length && !cr.length) return;

      renderKPIs(cp, cr);
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