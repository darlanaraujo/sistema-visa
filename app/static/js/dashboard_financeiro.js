// app/static/js/dashboard_financeiro.js
(function () {
  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    const pagarListEl = document.getElementById("dashPagarList");
    const receberListEl = document.getElementById("dashReceberList");
    if (!pagarListEl && !receberListEl) return; // dashboard antigo / sem hooks

    const LS_CP = "fin_cp_rows_v1";
    const LS_CR = "fin_cr_rows_v1";

    const MAX_ITEMS = 6; // quantidade mostrada no dashboard

    // ---------- helpers ----------
    function moneyBR(v) {
      const n = Number(v || 0);
      return n.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function toBRDate(iso) {
      if (!iso) return "";
      const [y, m, d] = String(iso).split("-");
      if (!y || !m || !d) return "";
      return `${d}/${m}/${y}`;
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

    function isOverdueIso(iso) {
      const d = new Date(String(iso || "") + "T00:00:00");
      if (Number.isNaN(d.getTime())) return false;
      return d < todayAtMidnight();
    }

    function dateKey(iso) {
      const t = Date.parse(String(iso || "") + "T00:00:00");
      return Number.isFinite(t) ? t : 9e15;
    }

    // prioridade: vencidas em aberto primeiro, depois data, depois status(open antes), depois nome
    function sortDashboard(a, b, getName) {
      const ao = a.status === "open";
      const bo = b.status === "open";

      const aOver = ao && isOverdueIso(a.data);
      const bOver = bo && isOverdueIso(b.data);
      if (aOver !== bOver) return aOver ? -1 : 1;

      const da = dateKey(a.data);
      const db = dateKey(b.data);
      if (da !== db) return da - db;

      // em empate na mesma data, pendente primeiro
      const sa = ao ? 0 : 1;
      const sb = bo ? 0 : 1;
      if (sa !== sb) return sa - sb;

      const na = String(getName(a) || "");
      const nb = String(getName(b) || "");
      return na.localeCompare(nb, "pt-BR", { sensitivity: "base" });
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

    // ---------- render pagar ----------
    function renderPagar() {
      if (!pagarListEl) return;

      const rows = loadArr(LS_CP);

      // dashboard: só open
      const openRows = rows.filter((r) => r && r.status === "open" && r.data);

      openRows.sort((a, b) =>
        sortDashboard(a, b, (x) => x.conta)
      );

      const slice = openRows.slice(0, MAX_ITEMS);

      if (!slice.length) {
        pagarListEl.innerHTML = `<div class="dash2__hint" style="margin:0;">Nenhuma conta em aberto.</div>`;
        return;
      }

      pagarListEl.innerHTML = slice
        .map((c) => {
          const overdue = isOverdueIso(c.data);
          const badge = overdue
            ? `<div class="fin-badge" style="background:rgba(220,38,38,.10); border-color:rgba(220,38,38,.25); color:#b91c1c; font-weight:900;">Vencida</div>`
            : ``;

          return `
            <div class="mini-card">
              <div class="mini-table">
                <div class="mini-row mini-row--head">
                  <div>Conta</div><div>Valor</div>
                </div>
                <div class="mini-row mini-row--val">
                  <div>
                    ${escapeHtml(c.conta || "")}
                    ${badge}
                  </div>
                  <div class="t-right">${escapeHtml(moneyBR(c.valor))}</div>
                </div>

                <div class="mini-row mini-row--head">
                  <div>Imóvel</div><div>Data</div>
                </div>
                <div class="mini-row mini-row--val">
                  <div>${escapeHtml(c.imovel || "")}</div>
                  <div class="t-right">${escapeHtml(toBRDate(c.data))}</div>
                </div>
              </div>
            </div>
          `;
        })
        .join("");
    }

    // ---------- render receber ----------
    function renderReceber() {
      if (!receberListEl) return;

      const rows = loadArr(LS_CR);

      const openRows = rows.filter((r) => r && r.status === "open" && r.data);

      openRows.sort((a, b) =>
        sortDashboard(a, b, (x) => x.cliente)
      );

      const slice = openRows.slice(0, MAX_ITEMS);

      if (!slice.length) {
        receberListEl.innerHTML = `<div class="dash2__hint" style="margin:0;">Nenhum recebível em aberto.</div>`;
        return;
      }

      receberListEl.innerHTML = slice
        .map((r) => {
          const overdue = isOverdueIso(r.data);
          const badge = overdue
            ? `<div class="fin-badge" style="background:rgba(16,185,129,.10); border-color:rgba(16,185,129,.25); color:#047857; font-weight:900;">Atrasado</div>`
            : ``;

          return `
            <div class="mini-card">
              <div class="mini-table">
                <div class="mini-row mini-row--head">
                  <div>Nome</div><div>Valor</div>
                </div>
                <div class="mini-row mini-row--val">
                  <div>
                    ${escapeHtml(r.cliente || "")}
                    ${badge}
                  </div>
                  <div class="t-right">${escapeHtml(moneyBR(r.valor))}</div>
                </div>

                <div class="mini-row mini-row--head">
                  <div>Cobrança</div><div>Data</div>
                </div>
                <div class="mini-row mini-row--val">
                  <div>${escapeHtml(r.forma || "")}</div>
                  <div class="t-right">${escapeHtml(toBRDate(r.data))}</div>
                </div>

                <div class="mini-row mini-row--head">
                  <div>Status</div><div></div>
                </div>
                <div class="mini-row mini-row--val">
                  <div>A receber</div><div></div>
                </div>
              </div>
            </div>
          `;
        })
        .join("");
    }

    // ---------- indicadores ----------
    function renderIndicators() {
      const pctPagarEl = document.getElementById("dashPctPagar");
      const hintPagarEl = document.getElementById("dashHintPagar");
      const pctReceberEl = document.getElementById("dashPctReceber");
      const hintReceberEl = document.getElementById("dashHintReceber");

      if (!pctPagarEl && !hintPagarEl && !pctReceberEl && !hintReceberEl) return;

      const pagar = loadArr(LS_CP).filter((r) => r && r.data);
      const receber = loadArr(LS_CR).filter((r) => r && r.data);

      const pagarDone = pagar.filter((r) => r.status === "done").length;
      const pagarTotal = pagar.length || 0;
      const receberDone = receber.filter((r) => r.status === "done").length;
      const receberTotal = receber.length || 0;

      const pct = (a, t) => (t > 0 ? Math.max(0, Math.min(100, Math.round((a / t) * 100))) : 0);

      const pP = pct(pagarDone, pagarTotal);
      const pR = pct(receberDone, receberTotal);

      if (pctPagarEl) pctPagarEl.style.width = `${pP}%`;
      if (hintPagarEl) hintPagarEl.textContent = `${pagarDone}/${pagarTotal} pagas`;

      if (pctReceberEl) pctReceberEl.style.width = `${pR}%`;
      if (hintReceberEl) hintReceberEl.textContent = `${receberDone}/${receberTotal} recebidas`;
    }

    // ---------- init ----------
    renderPagar();
    renderReceber();
    renderIndicators();

    // Se quiser atualização imediata ao salvar nas páginas (mesmo tab):
    window.addEventListener("storage", (e) => {
      if (e.key === LS_CP) {
        renderPagar();
        renderIndicators();
      }
      if (e.key === LS_CR) {
        renderReceber();
        renderIndicators();
      }
    });
  });
})();