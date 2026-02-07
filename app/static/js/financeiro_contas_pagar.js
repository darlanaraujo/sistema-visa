// app/static/js/financeiro_contas_pagar.js
(function () {
  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    const STORAGE_ROWS_KEY = "fin_cp_rows_v1";
    const STORAGE_TPL_KEY = "fin_cp_templates_v1";
    const FIXED_LIMIT = 12; // mês atual + próximos 11

    const fallback = Array.isArray(window.__CP_MOCK__) ? window.__CP_MOCK__ : [];

    let rows = [];
    let templates = []; // modelos de contas fixas
    let viewMonth = new Date();
    let pendingDeleteId = null;

    const el = (id) => document.getElementById(id);
    const els = {
      monthLabel: el("cpMonthLabel"),
      prev: el("cpPrev"),
      next: el("cpNext"),

      totalOpen: el("cpTotalOpen"),
      totalDone: el("cpTotalDone"),
      count: el("cpCount"),

      filterImovel: el("cpFilterImovel"),
      filterStatus: el("cpFilterStatus"),
      filterCategoria: el("cpFilterCategoria"),
      filterFixa: el("cpFilterFixa"),
      filterSearch: el("cpFilterSearch"),
      clear: el("cpClear"),

      tbody: el("cpTbody"),

      newBtn: el("cpNew"),

      modal: el("cpModal"),
      modalTitle: el("cpModalTitle"),
      modalClose: el("cpModalClose"),
      cancel: el("cpCancel"),
      form: el("cpForm"),

      id: el("cpId"),
      conta: el("cpConta"),
      valor: el("cpValor"),
      imovel: el("cpImovel"),
      categoria: el("cpCategoria"),
      data: el("cpData"),
      fixa: el("cpFixa"),

      delModal: el("cpDelModal"),
      delClose: el("cpDelClose"),
      delCancel: el("cpDelCancel"),
      delConfirm: el("cpDelConfirm"),

      filtersWrap: el("cpFiltersWrap"),
      filtersToggle: el("cpFiltersToggle"),
      filtersIcon: el("cpFiltersIcon"),
    };

    if (!els.tbody || !els.prev || !els.next || !els.monthLabel) return;

    // ---------------------------
    // Toast (global)
    // ---------------------------
    function toast(kind, msg) {
      try {
        if (window.Toast && typeof window.Toast[kind] === "function") window.Toast[kind](msg);
        else if (window.Toast && typeof window.Toast.show === "function") window.Toast.show(msg);
      } catch (_) {}
    }
    const tSuccess = (m) => toast("success", m);
    const tDanger = (m) => toast("danger", m);
    const tWarning = (m) => toast("warning", m);
    const tShow = (m) => toast("show", m);

    // ---------------------------
    // Data access (FinStore -> fallback localStorage)
    // ---------------------------
    function finStore() {
      return window.FinStore && typeof window.FinStore === "object" ? window.FinStore : null;
    }

    function storeGetRows() {
      const fs = finStore();
      try {
        if (fs?.cp && typeof fs.cp.getRows === "function") {
          const v = fs.cp.getRows();
          return Array.isArray(v) ? v : [];
        }
        if (fs?.cp && typeof fs.cp.rowsGet === "function") {
          const v = fs.cp.rowsGet();
          return Array.isArray(v) ? v : [];
        }
      } catch (_) {}

      try {
        const raw = localStorage.getItem(STORAGE_ROWS_KEY);
        if (!raw) return [];
        const v = JSON.parse(raw);
        return Array.isArray(v) ? v : [];
      } catch (_) {
        return [];
      }
    }

    function storeSetRows(nextRows) {
      const fs = finStore();
      try {
        if (fs?.cp && typeof fs.cp.setRows === "function") {
          fs.cp.setRows(nextRows);
          return;
        }
        if (fs?.cp && typeof fs.cp.rowsSet === "function") {
          fs.cp.rowsSet(nextRows);
          return;
        }
      } catch (_) {}

      try {
        localStorage.setItem(STORAGE_ROWS_KEY, JSON.stringify(nextRows));
      } catch (_) {}
    }

    function storeGetTemplates() {
      const fs = finStore();
      try {
        if (fs?.cp && typeof fs.cp.getTemplates === "function") {
          const v = fs.cp.getTemplates();
          return Array.isArray(v) ? v : [];
        }
        if (fs?.cp && typeof fs.cp.templatesGet === "function") {
          const v = fs.cp.templatesGet();
          return Array.isArray(v) ? v : [];
        }
      } catch (_) {}

      try {
        const raw = localStorage.getItem(STORAGE_TPL_KEY);
        if (!raw) return [];
        const v = JSON.parse(raw);
        return Array.isArray(v) ? v : [];
      } catch (_) {
        return [];
      }
    }

    function storeSetTemplates(nextTemplates) {
      const fs = finStore();
      try {
        if (fs?.cp && typeof fs.cp.setTemplates === "function") {
          fs.cp.setTemplates(nextTemplates);
          return;
        }
        if (fs?.cp && typeof fs.cp.templatesSet === "function") {
          fs.cp.templatesSet(nextTemplates);
          return;
        }
      } catch (_) {}

      try {
        localStorage.setItem(STORAGE_TPL_KEY, JSON.stringify(nextTemplates));
      } catch (_) {}
    }

    function saveStore() {
      storeSetRows(rows);
      storeSetTemplates(templates);
    }

    // ---------------------------
    // Normalização (texto + valor)
    // ---------------------------
    const LOWER_WORDS = new Set(["de", "da", "do", "das", "dos", "e", "em", "para", "por", "com", "a", "o"]);

    function normalizeSpaces(s) {
      return String(s || "").replace(/\s+/g, " ").trim();
    }

    function titleCasePT(s) {
      const raw = normalizeSpaces(s);
      if (!raw) return "";
      const parts = raw.toLowerCase().split(" ");
      return parts
        .map((w, i) => {
          if (!w) return "";
          if (i > 0 && LOWER_WORDS.has(w)) return w;
          return w.charAt(0).toUpperCase() + w.slice(1);
        })
        .join(" ");
    }

    function normalizeConta(s) {
      return titleCasePT(s);
    }

    // Valor: permitir só números, vírgula, ponto e espaço (em digitação/colagem)
    function sanitizeMoneyTextInput(raw) {
      let v = String(raw || "");
      v = v.replace(/[^\d.,\s]/g, "");
      v = v.replace(/\s+/g, " ");
      return v;
    }

    function attachMoneyGuards(inputEl) {
      if (!inputEl) return;

      inputEl.addEventListener("keydown", (e) => {
        const k = e.key;

        // navegação/atalhos
        if (
          k === "Backspace" ||
          k === "Delete" ||
          k === "Tab" ||
          k === "Enter" ||
          k === "ArrowLeft" ||
          k === "ArrowRight" ||
          k === "ArrowUp" ||
          k === "ArrowDown" ||
          k === "Home" ||
          k === "End" ||
          e.ctrlKey ||
          e.metaKey
        )
          return;

        if (/^\d$/.test(k)) return;
        if (k === "," || k === "." || k === " ") return;

        e.preventDefault();
      });

      inputEl.addEventListener("input", () => {
        const prev = inputEl.value;
        const next = sanitizeMoneyTextInput(prev);
        if (next !== prev) inputEl.value = next;
      });

      inputEl.addEventListener("paste", (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData("text") || "";
        const cleaned = sanitizeMoneyTextInput(text);

        const start = inputEl.selectionStart ?? inputEl.value.length;
        const end = inputEl.selectionEnd ?? inputEl.value.length;

        const before = inputEl.value.slice(0, start);
        const after = inputEl.value.slice(end);

        inputEl.value = before + cleaned + after;

        const pos = (before + cleaned).length;
        try {
          inputEl.setSelectionRange(pos, pos);
        } catch (_) {}
      });

      inputEl.addEventListener("blur", () => {
        inputEl.value = sanitizeMoneyTextInput(inputEl.value).trim();
      });
    }

    function attachTextNormalization(inputEl, fn) {
      if (!inputEl) return;
      inputEl.addEventListener("blur", () => {
        inputEl.value = fn(inputEl.value);
      });
    }

    // ---------------------------
    // Helpers
    // ---------------------------
    function moneyBR(v) {
      const n = Number(v || 0);
      return n.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function parseMoneyInput(v) {
      const raw = String(v || "").trim();
      if (!raw) return 0;
      // aceita "1.234,56" e "1234.56"
      const cleaned = raw.replace(/\s/g, "").replace(/\./g, "").replace(",", ".");
      const n = Number(cleaned);
      return Number.isFinite(n) ? n : 0;
    }

    function formatMonthLabel(dt) {
      const m = dt.toLocaleString("pt-BR", { month: "long" });
      const y = dt.getFullYear();
      const mm = m.charAt(0).toUpperCase() + m.slice(1);
      return `${mm} / ${y}`;
    }

    function monthKeyFromDate(dt) {
      const y = dt.getFullYear();
      const m = String(dt.getMonth() + 1).padStart(2, "0");
      return `${y}-${m}`; // yyyy-mm
    }

    function monthKeyFromIso(iso) {
      if (!iso || iso.length < 7) return "";
      return iso.slice(0, 7);
    }

    function sameMonth(dateStr, base) {
      const d = new Date(dateStr + "T00:00:00");
      return d.getMonth() === base.getMonth() && d.getFullYear() === base.getFullYear();
    }

    function toBRDate(iso) {
      if (!iso) return "";
      const [y, m, d] = iso.split("-");
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

    function isOverdue(item) {
      if (!item || item.status === "done") return false;
      const d = new Date(String(item.data || "") + "T00:00:00");
      if (Number.isNaN(d.getTime())) return false;
      return d < todayAtMidnight();
    }

    function uid() {
      return Date.now() + Math.floor(Math.random() * 1000);
    }

    function normalizeRowTextFields(r) {
      return {
        ...r,
        conta: normalizeConta(r.conta),
      };
    }

    // ---------------------------
    // Storage (via FinStore)
    // ---------------------------
    function normalizeFallback(list) {
      return (Array.isArray(list) ? list : []).map((r) =>
        normalizeRowTextFields({
          id: r.id ?? uid(),
          conta: r.conta ?? "",
          valor: Number(r.valor || 0),
          imovel: r.imovel ?? "",
          categoria: r.categoria ?? "",
          data: r.data ?? "",
          fixa: Boolean(r.fixa),
          status: r.status === "done" ? "done" : "open",
          templateId: r.templateId || null,
          instanceMonth: r.instanceMonth || monthKeyFromIso(r.data),
          createdAt: r.createdAt || Date.now(),
        })
      );
    }

    function loadStorage() {
      try {
        rows = storeGetRows();
        templates = storeGetTemplates();

        if (!Array.isArray(rows) || !rows.length) rows = normalizeFallback(fallback);
        if (!Array.isArray(templates)) templates = [];
      } catch (_) {
        rows = normalizeFallback(fallback);
        templates = [];
      }

      // normaliza retroativo (sem quebrar nada)
      rows = rows.map(normalizeRowTextFields);

      saveStore();
    }

    // ---------------------------
    // Fixas (templates + instâncias)
    // ---------------------------
    function findTemplate(tid) {
      return templates.find((t) => String(t.id) === String(tid));
    }

    function ensureTemplateFromFixedRow(row) {
      const baseIso = row.data || "";
      const day = baseIso ? Number(baseIso.slice(8, 10)) : 1;
      let tpl = row.templateId ? findTemplate(row.templateId) : null;

      row.conta = normalizeConta(row.conta);

      if (!tpl) {
        const tplId = "TPL-" + uid();
        tpl = {
          id: tplId,
          conta: row.conta || "",
          imovel: row.imovel || "",
          categoria: row.categoria || "",
          day: day >= 1 && day <= 28 ? day : Math.min(Math.max(day, 1), 28),
          defaultValor: Number(row.valor || 0),
          startMonth: monthKeyFromIso(row.data) || monthKeyFromDate(new Date()),
          limit: FIXED_LIMIT,
          createdAt: Date.now(),
        };
        templates.unshift(tpl);
        row.templateId = tplId;
      } else {
        tpl.conta = row.conta || tpl.conta;
        tpl.imovel = row.imovel || tpl.imovel;
        tpl.categoria = row.categoria || tpl.categoria;
        tpl.day = day >= 1 && day <= 28 ? day : tpl.day;
        tpl.defaultValor = Number(row.valor || 0);
      }

      return tpl;
    }

    function instanceIdFor(tplId, monthKey) {
      return `${tplId}::${monthKey}`;
    }

    function hasInstance(tplId, monthKey) {
      const iid = instanceIdFor(tplId, monthKey);
      return rows.some((r) => String(r.id) === iid);
    }

    function createInstanceIfMissing(tpl, monthKey) {
      if (!tpl || !monthKey) return;
      if (hasInstance(tpl.id, monthKey)) return;

      const y = Number(monthKey.slice(0, 4));
      const m = Number(monthKey.slice(5, 7));
      const d = String(tpl.day).padStart(2, "0");
      const iso = `${y}-${String(m).padStart(2, "0")}-${d}`;

      rows.unshift(
        normalizeRowTextFields({
          id: instanceIdFor(tpl.id, monthKey),
          conta: tpl.conta,
          valor: Number(tpl.defaultValor || 0),
          imovel: tpl.imovel,
          categoria: tpl.categoria,
          data: iso,
          fixa: true,
          status: "open",
          templateId: tpl.id,
          instanceMonth: monthKey,
          createdAt: Date.now(),
        })
      );
    }

    function monthAdd(baseMonthKey, add) {
      const y = Number(baseMonthKey.slice(0, 4));
      const m = Number(baseMonthKey.slice(5, 7));
      const dt = new Date(y, m - 1 + add, 1);
      return monthKeyFromDate(dt);
    }

    function ensureFixedInstancesForMonth(targetMonthKey) {
      for (const tpl of templates) {
        const start = tpl.startMonth || targetMonthKey;
        const lim = Number(tpl.limit || FIXED_LIMIT);

        let inRange = false;
        for (let i = 0; i < lim; i++) {
          if (monthAdd(start, i) === targetMonthKey) {
            inRange = true;
            break;
          }
        }
        if (!inRange) continue;

        createInstanceIfMissing(tpl, targetMonthKey);
      }
    }

    function ensureFixedInstancesHorizonFrom(monthKey) {
      for (const tpl of templates) {
        const start = tpl.startMonth || monthKey;
        const lim = Number(tpl.limit || FIXED_LIMIT);
        for (let i = 0; i < lim; i++) {
          createInstanceIfMissing(tpl, monthAdd(start, i));
        }
      }
    }

    // ---------------------------
    // CRUD helpers
    // ---------------------------
    function getById(id) {
      return rows.find((r) => String(r.id) === String(id));
    }

    function upsert(item) {
      const it = normalizeRowTextFields(item);
      const idx = rows.findIndex((r) => String(r.id) === String(it.id));
      if (idx >= 0) rows[idx] = it;
      else rows.unshift(it);
      saveStore();
    }

    function removeById(id) {
      rows = rows.filter((r) => String(r.id) !== String(id));
      saveStore();
    }

    // ---------------------------
    // Filters / totals / render
    // ---------------------------
    function applyFilters(list) {
      const imovel = (els.filterImovel?.value || "").trim();
      const status = (els.filterStatus?.value || "").trim();
      const categoria = (els.filterCategoria?.value || "").trim();
      const fixa = (els.filterFixa?.value || "").trim();
      const search = (els.filterSearch?.value || "").trim().toLowerCase();

      return list
        .filter((r) => sameMonth(r.data, viewMonth))
        .filter((r) => !imovel || r.imovel === imovel)
        .filter((r) => !status || r.status === status)
        .filter((r) => (fixa === "" ? true : String(Number(Boolean(r.fixa))) === fixa))
        .filter((r) => !categoria || r.categoria === categoria)
        .filter((r) => !search || String(r.conta || "").toLowerCase().includes(search));
    }

    function calcTotals(list) {
      let open = 0;
      let done = 0;
      for (const r of list) {
        if (r.status === "done") done += Number(r.valor || 0);
        else open += Number(r.valor || 0);
      }
      if (els.totalOpen) els.totalOpen.textContent = moneyBR(open);
      if (els.totalDone) els.totalDone.textContent = moneyBR(done);
    }

    function render() {
      const mk = monthKeyFromDate(viewMonth);
      ensureFixedInstancesForMonth(mk);

      els.monthLabel.textContent = formatMonthLabel(viewMonth);

      const filtered = applyFilters(rows);

      function dateKey(iso) {
        const t = Date.parse(String(iso || "") + "T00:00:00");
        return Number.isFinite(t) ? t : 9e15; // inválido vai pro fim
      }

      function textKey(v) {
        return String(v || "").trim();
      }

      filtered.sort((a, b) => {
        // 1) Vencidas em aberto primeiro
        const ao = a.status !== "done" && isOverdue(a);
        const bo = b.status !== "done" && isOverdue(b);
        if (ao !== bo) return ao ? -1 : 1;

        // 2) Data de vencimento (asc)
        const da = dateKey(a.data);
        const db = dateKey(b.data);
        if (da !== db) return da - db;

        // 3) Na mesma data: open antes de done
        const sa = a.status === "open" ? 0 : 1;
        const sb = b.status === "open" ? 0 : 1;
        if (sa !== sb) return sa - sb;

        // 4) Desempate: createdAt (mais antigo primeiro)
        const ca = Number(a.createdAt || 0);
        const cb = Number(b.createdAt || 0);
        if (ca !== cb) return ca - cb;

        // 5) Desempate textual (conta)
        return textKey(a.conta).localeCompare(textKey(b.conta), "pt-BR", { sensitivity: "base" });
      });

      if (els.count) els.count.textContent = `${filtered.length} itens`;

      calcTotals(filtered);

      els.tbody.innerHTML = filtered
        .map((r) => {
          const fixaTxt = r.fixa ? "Sim" : "Não";
          const overdue = isOverdue(r);

          const statusIcon =
            r.status === "done"
              ? '<span class="fin-status is-done" title="Pago"><i class="fa-solid fa-circle-check"></i></span>'
              : '<span class="fin-status is-open" title="A pagar"><i class="fa-solid fa-circle-dot"></i></span>';

          const toggleTip = r.status === "done" ? "Reabrir" : "Baixar";
          const toggleIcon = r.status === "done" ? "fa-rotate-left" : "fa-check";

          const trClass = [overdue ? "is-overdue" : "", r.status === "done" ? "is-done" : "is-open", r.fixa ? "is-fixed" : ""]
            .filter(Boolean)
            .join(" ");

          return `
            <tr data-id="${escapeHtml(r.id)}" class="${trClass}">
              <td class="t-left">${escapeHtml(r.conta)}</td>
              <td class="t-right">${moneyBR(r.valor)}</td>
              <td class="t-center">${escapeHtml(r.imovel)}</td>
              <td class="t-center">${escapeHtml(toBRDate(r.data))}</td>
              <td class="t-center">${escapeHtml(r.categoria)}</td>
              <td class="t-center">${fixaTxt}</td>
              <td class="t-center">${statusIcon}</td>
              <td class="t-center">
                <div class="fin-actions-row">
                  <button class="fin-action-ico" data-act="edit" data-tip="Editar" type="button">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <button class="fin-action-ico" data-act="toggle" data-tip="${escapeHtml(toggleTip)}" type="button">
                    <i class="fa-solid ${escapeHtml(toggleIcon)}"></i>
                  </button>
                  <button class="fin-action-ico" data-act="del" data-tip="Excluir" type="button">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        })
        .join("");
    }

    // ---------------------------
    // Modal
    // ---------------------------
    function openModal(mode, item) {
      if (!els.modal) return;

      if (els.modalTitle) els.modalTitle.textContent = mode === "edit" ? "Editar lançamento" : "Novo lançamento";

      if (els.id) els.id.value = item?.id ?? "";
      if (els.conta) els.conta.value = item?.conta ?? "";
      if (els.valor) els.valor.value = item?.valor ?? "";
      if (els.imovel) els.imovel.value = item?.imovel ?? "";
      if (els.categoria) els.categoria.value = item?.categoria ?? "";
      if (els.data) els.data.value = item?.data ?? "";
      if (els.fixa) els.fixa.value = String(Number(Boolean(item?.fixa ?? true)));

      els.modal.classList.add("is-open");
      els.modal.setAttribute("aria-hidden", "false");

      setTimeout(() => {
        if (els.conta) els.conta.focus();
      }, 50);
    }

    function closeModal() {
      if (!els.modal) return;
      els.modal.classList.remove("is-open");
      els.modal.setAttribute("aria-hidden", "true");
    }

    // ---------------------------
    // Delete modal
    // ---------------------------
    function openDelModal(id) {
      pendingDeleteId = id;
      if (!els.delModal) return;
      els.delModal.classList.add("is-open");
      els.delModal.setAttribute("aria-hidden", "false");
    }

    function closeDelModal() {
      pendingDeleteId = null;
      if (!els.delModal) return;
      els.delModal.classList.remove("is-open");
      els.delModal.setAttribute("aria-hidden", "true");
    }

    // ---------------------------
    // Events
    // ---------------------------
    els.prev.addEventListener("click", () => {
      viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() - 1, 1);
      render();
    });

    els.next.addEventListener("click", () => {
      viewMonth = new Date(viewMonth.getFullYear(), viewMonth.getMonth() + 1, 1);
      render();
    });

    if (els.newBtn) els.newBtn.addEventListener("click", () => openModal("new", null));

    if (els.modalClose) els.modalClose.addEventListener("click", closeModal);
    if (els.cancel) els.cancel.addEventListener("click", closeModal);

    ["filterImovel", "filterStatus", "filterCategoria", "filterFixa"].forEach((k) => {
      if (els[k]) els[k].addEventListener("change", render);
    });
    if (els.filterSearch) els.filterSearch.addEventListener("input", render);

    if (els.clear)
      els.clear.addEventListener("click", () => {
        if (els.filterImovel) els.filterImovel.value = "";
        if (els.filterStatus) els.filterStatus.value = "";
        if (els.filterCategoria) els.filterCategoria.value = "";
        if (els.filterFixa) els.filterFixa.value = "";
        if (els.filterSearch) els.filterSearch.value = "";
        tShow("Filtros limpos.");
        render();
      });

    if (els.filtersToggle && els.filtersWrap) {
      els.filtersToggle.addEventListener("click", () => {
        const open = els.filtersWrap.classList.toggle("is-open");
        if (els.filtersIcon) {
          els.filtersIcon.classList.toggle("fa-chevron-down", !open);
          els.filtersIcon.classList.toggle("fa-chevron-up", open);
        }
      });
    }

    els.tbody.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-act]");
      if (!btn) return;

      const tr = e.target.closest("tr[data-id]");
      if (!tr) return;

      const id = tr.getAttribute("data-id");
      const act = btn.getAttribute("data-act");
      const item = getById(id);
      if (!item) return;

      if (act === "edit") {
        openModal("edit", item);
        return;
      }

      if (act === "toggle") {
        const wasDone = item.status === "done";
        item.status = wasDone ? "open" : "done";
        upsert(item);
        render();
        if (wasDone) tWarning("Conta reaberta.");
        else tSuccess("Pagamento confirmado.");
        return;
      }

      if (act === "del") {
        openDelModal(id);
        return;
      }
    });

    if (els.form) {
      els.form.addEventListener("submit", (e) => {
        // garante que não recarrega a página caso algum HTML esteja com action
        try {
          e.preventDefault();
        } catch (_) {}

        const isEdit = Boolean(els.id && els.id.value);
        const existing = isEdit ? getById(els.id.value) : null;

        const id = isEdit ? String(els.id.value) : String(uid());

        const payload = {
          id,
          conta: normalizeConta((els.conta?.value || "").trim()),
          valor: parseMoneyInput(els.valor?.value || ""),
          imovel: els.imovel?.value || "",
          categoria: els.categoria?.value || "",
          data: els.data?.value || "",
          fixa: (els.fixa?.value || "1") === "1",
          status: isEdit ? existing?.status || "open" : "open",

          templateId: existing?.templateId || null,
          instanceMonth: monthKeyFromIso(els.data?.value || ""),
          createdAt: existing?.createdAt || Date.now(),
        };

        if (!payload.conta || !payload.imovel || !payload.categoria || !payload.data) {
          tDanger("Preencha todos os campos obrigatórios.");
          return;
        }

        if (!Number.isFinite(payload.valor) || payload.valor <= 0) {
          tDanger("Informe um valor válido (somente números).");
          if (els.valor) els.valor.focus();
          return;
        }

        if (payload.fixa) {
          const tpl = ensureTemplateFromFixedRow(payload);

          const mk = monthKeyFromIso(payload.data) || monthKeyFromDate(new Date());
          const detId = instanceIdFor(tpl.id, mk);

          if (String(payload.id) !== String(detId)) {
            if (isEdit && existing) removeById(existing.id);
            payload.id = detId;
          }

          payload.templateId = tpl.id;
          payload.instanceMonth = mk;

          upsert(payload);

          ensureFixedInstancesHorizonFrom(tpl.startMonth || mk);
          saveStore();

          closeModal();
          render();

          if (isEdit) tSuccess("Conta fixa atualizada.");
          else tSuccess("Conta fixa cadastrada (replicada para os próximos meses).");
          return;
        }

        if (!payload.fixa && existing && existing.templateId) {
          payload.templateId = null;
          payload.instanceMonth = monthKeyFromIso(payload.data) || existing.instanceMonth || monthKeyFromDate(new Date());
          upsert(payload);
          closeModal();
          render();
          tWarning("Conta salva sem vínculo de fixa (somente este mês).");
          return;
        }

        upsert(payload);
        closeModal();
        render();
        tSuccess(isEdit ? "Alterações salvas." : "Conta cadastrada.");
      });
    }

    if (els.delClose) els.delClose.addEventListener("click", closeDelModal);
    if (els.delCancel) els.delCancel.addEventListener("click", closeDelModal);

    if (els.delConfirm) {
      els.delConfirm.addEventListener("click", () => {
        if (pendingDeleteId == null) return;

        const item = getById(pendingDeleteId);

        removeById(pendingDeleteId);

        closeDelModal();
        render();

        if (item && item.templateId) tWarning("Excluído somente neste mês (conta fixa permanece nos demais).");
        else tSuccess("Conta excluída.");
      });
    }

    // ---------------------------
    // Init
    // ---------------------------
    attachMoneyGuards(els.valor);
    attachTextNormalization(els.conta, normalizeConta);

    loadStorage();
    viewMonth = new Date();

    ensureFixedInstancesHorizonFrom(monthKeyFromDate(new Date()));
    saveStore();

    render();
  });
})();