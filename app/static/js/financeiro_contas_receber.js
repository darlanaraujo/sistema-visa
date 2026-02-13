// app/static/js/financeiro_contas_receber.js
(function () {
  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(function () {
    const STORAGE_ROWS_KEY = "fin_cr_rows_v1";

    const fallback = Array.isArray(window.__CR_MOCK__) ? window.__CR_MOCK__ : [];
    let rows = [];
    let viewMonth = new Date();
    let pendingDeleteId = null;

    const el = (id) => document.getElementById(id);

    const els = {
      monthLabel: el("crMonthLabel"),
      prev: el("crPrev"),
      next: el("crNext"),

      totalOpen: el("crTotalOpen"),
      totalDone: el("crTotalDone"),
      count: el("crCount"),

      filterCliente: el("crFilterCliente"),
      filterStatus: el("crFilterStatus"),
      filterForma: el("crFilterForma"),
      filterProcesso: el("crFilterProcesso"),
      filterSearch: el("crFilterSearch"),
      clear: el("crClear"),

      tbody: el("crTbody"),

      newBtn: el("crNew"),

      modal: el("crModal"),
      modalTitle: el("crModalTitle"),
      modalClose: el("crModalClose"),
      cancel: el("crCancel"),
      form: el("crForm"),

      id: el("crId"),
      cliente: el("crCliente"),
      valor: el("crValor"),
      data: el("crData"),
      forma: el("crForma"),
      processo: el("crProcesso"),
      obs: el("crObs"),

      delModal: el("crDelModal"),
      delClose: el("crDelClose"),
      delCancel: el("crDelCancel"),
      delConfirm: el("crDelConfirm"),

      filtersWrap: el("crFiltersWrap"),
      filtersToggle: el("crFiltersToggle"),
      filtersIcon: el("crFiltersIcon"),
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
    // Tools -> Selects (Ferramentas)
    // ---------------------------
    function getTools() {
      return window.FinStore && window.FinStore.tools ? window.FinStore.tools : null;
    }

    function ensureRemovedOption(selectEl, value) {
      if (!selectEl || !value) return;
      const exists = Array.from(selectEl.options).some((o) => o.value === value);
      if (exists) return;

      const opt = document.createElement("option");
      opt.value = value;
      opt.textContent = `(Removido) ${value}`;
      opt.disabled = true;
      selectEl.appendChild(opt);
    }

    function fillSelectFromList(selectEl, list, placeholderLabel) {
      if (!selectEl) return;

      const current = String(selectEl.value || "");

      const first = selectEl.querySelector("option[value='']") || selectEl.options[0] || null;
      const firstText = first ? first.textContent : (placeholderLabel || "Selecione");

      selectEl.innerHTML = "";
      const opt0 = document.createElement("option");
      opt0.value = "";
      opt0.textContent = firstText;
      selectEl.appendChild(opt0);

      (Array.isArray(list) ? list : []).forEach((name) => {
        const n = String(name || "").trim();
        if (!n) return;
        const opt = document.createElement("option");
        opt.value = n;
        opt.textContent = n;
        selectEl.appendChild(opt);
      });

      if (current) {
        ensureRemovedOption(selectEl, current);
        selectEl.value = current;
      }
    }

    function syncCatalogsFromTools() {
      const tools = getTools();
      if (!tools) return;

      // CR: Formas vêm do Ferramentas (financeiro.formas)
      const formas = tools.getFormas ? tools.getFormas() : [];
      fillSelectFromList(els.filterForma, formas, "Todas");
      fillSelectFromList(els.forma, formas, "Selecione");
    }

    window.addEventListener((window.FinStore && window.FinStore.EVT) ? window.FinStore.EVT : "fin:change", (e) => {
      const k = e?.detail?.key || "";
      if (String(k).startsWith("tools:")) {
        syncCatalogsFromTools();
      }
    });

    // ---------------------------
    // Normalização (texto + valor) — igual padrão do pagar
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

    function normalizeCliente(s) {
      return titleCasePT(s);
    }

    function normalizeForma(s) {
      return titleCasePT(s);
    }

    function normalizeProcesso(s) {
      const str = normalizeSpaces(s);
      return str ? str.toUpperCase() : "";
    }

    function normalizeObs(s) {
      return normalizeSpaces(s);
    }

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

    // ---------------------------
    // Normalização de registro
    // ---------------------------
    function normalizeRow(r) {
      const base = r || {};
      return {
        id: base.id ?? uid(),
        cliente: normalizeCliente(base.cliente ?? ""),
        valor: Number(base.valor || 0),
        data: String(base.data || ""),
        forma: normalizeForma(base.forma ?? ""),
        processo: normalizeProcesso(base.processo ?? ""),
        obs: normalizeObs(base.obs ?? ""),
        status: base.status === "done" ? "done" : "open",
        createdAt: base.createdAt || Date.now(),
      };
    }

    // ---------------------------
    // Storage
    // ---------------------------
    function normalizeFallback(list) {
      return (Array.isArray(list) ? list : []).map(normalizeRow);
    }

    function loadStorage() {
      try {
        const rs = localStorage.getItem(STORAGE_ROWS_KEY);
        rows = rs ? JSON.parse(rs) : [];
        if (!Array.isArray(rows) || !rows.length) rows = normalizeFallback(fallback);
        else rows = rows.map(normalizeRow);
      } catch (_) {
        rows = normalizeFallback(fallback);
      }

      saveStorage();
    }

    function saveStorage() {
      try {
        localStorage.setItem(STORAGE_ROWS_KEY, JSON.stringify(rows));
      } catch (_) {}
    }

    // ---------------------------
    // Filters / totals / render
    // ---------------------------
    function applyFilters(list) {
      const cliente = (els.filterCliente?.value || "").trim();
      const status = (els.filterStatus?.value || "").trim();
      const forma = (els.filterForma?.value || "").trim();
      const processo = (els.filterProcesso?.value || "").trim().toLowerCase();
      const search = (els.filterSearch?.value || "").trim().toLowerCase();

      return list
        .filter((r) => sameMonth(r.data, viewMonth))
        .filter((r) => !cliente || r.cliente === cliente)
        .filter((r) => !status || r.status === status)
        .filter((r) => !forma || r.forma === forma)
        .filter((r) => !processo || String(r.processo || "").toLowerCase().includes(processo))
        .filter((r) => {
          if (!search) return true;
          const a = String(r.cliente || "").toLowerCase();
          const b = String(r.obs || "").toLowerCase();
          return a.includes(search) || b.includes(search);
        });
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
      els.monthLabel.textContent = formatMonthLabel(viewMonth);

      const filtered = applyFilters(rows);

      function dateKey(iso) {
        const t = Date.parse(String(iso || "") + "T00:00:00");
        return Number.isFinite(t) ? t : 9e15;
      }

      function textKey(v) {
        return String(v || "").trim();
      }

      filtered.sort((a, b) => {
        const ao = a.status !== "done" && isOverdue(a);
        const bo = b.status !== "done" && isOverdue(b);
        if (ao !== bo) return ao ? -1 : 1;

        const da = dateKey(a.data);
        const db = dateKey(b.data);
        if (da !== db) return da - db;

        const sa = a.status === "open" ? 0 : 1;
        const sb = b.status === "open" ? 0 : 1;
        if (sa !== sb) return sa - sb;

        const ca = Number(a.createdAt || 0);
        const cb = Number(b.createdAt || 0);
        if (ca !== cb) return ca - cb;

        return textKey(a.cliente).localeCompare(textKey(b.cliente), "pt-BR", { sensitivity: "base" });
      });

      if (els.count) els.count.textContent = `${filtered.length} itens`;

      calcTotals(filtered);

      els.tbody.innerHTML = filtered
        .map((r) => {
          const overdue = isOverdue(r);

          const statusIcon =
            r.status === "done"
              ? '<span class="fin-status is-done" title="Recebido"><i class="fa-solid fa-circle-check"></i></span>'
              : '<span class="fin-status is-open" title="A receber"><i class="fa-solid fa-circle-dot"></i></span>';

          const toggleTip = r.status === "done" ? "Reabrir" : "Baixar";
          const toggleIcon = r.status === "done" ? "fa-rotate-left" : "fa-check";

          const trClass = [overdue ? "is-overdue" : "", r.status === "done" ? "is-done" : "is-open"]
            .filter(Boolean)
            .join(" ");

          return `
            <tr data-id="${escapeHtml(r.id)}" class="${trClass}">
              <td class="t-left">${escapeHtml(r.cliente)}</td>
              <td class="t-right">${moneyBR(r.valor)}</td>
              <td class="t-center">${escapeHtml(toBRDate(r.data))}</td>
              <td class="t-center">${escapeHtml(r.forma)}</td>
              <td class="t-center">${escapeHtml(r.processo || "")}</td>
              <td class="t-center">${statusIcon}</td>
              <td class="t-center">
                <div class="fin-actions-row">
                  <button class="fin-action-ico" data-act="edit" data-tip="Editar" type="button"><i class="fa-solid fa-pen"></i></button>
                  <button class="fin-action-ico" data-act="toggle" data-tip="${escapeHtml(toggleTip)}" type="button"><i class="fa-solid ${escapeHtml(toggleIcon)}"></i></button>
                  <button class="fin-action-ico" data-act="del" data-tip="Excluir" type="button"><i class="fa-solid fa-trash"></i></button>
                </div>
              </td>
            </tr>
          `;
        })
        .join("");
    }

    // ---------------------------
    // CRUD helpers
    // ---------------------------
    function getById(id) {
      return rows.find((r) => String(r.id) === String(id));
    }

    function upsert(item) {
      const normalized = normalizeRow(item);

      const idx = rows.findIndex((r) => String(r.id) === String(normalized.id));
      if (idx >= 0) rows[idx] = normalized;
      else rows.unshift(normalized);

      saveStorage();
    }

    function removeById(id) {
      rows = rows.filter((r) => String(r.id) !== String(id));
      saveStorage();
    }

    // ---------------------------
    // Modal
    // ---------------------------
    function openModal(mode, item) {
      if (!els.modal) return;

      // atualiza catálogo de formas ao abrir modal
      syncCatalogsFromTools();

      if (els.modalTitle) els.modalTitle.textContent = mode === "edit" ? "Editar lançamento" : "Novo lançamento";

      if (els.id) els.id.value = item?.id ?? "";
      if (els.cliente) els.cliente.value = item?.cliente ?? "";
      if (els.valor) els.valor.value = item?.valor ?? "";
      if (els.data) els.data.value = item?.data ?? "";

      if (els.forma) {
        const v = item?.forma ?? "";
        if (v) ensureRemovedOption(els.forma, v);
        els.forma.value = v;
      }

      if (els.processo) els.processo.value = item?.processo ?? "";
      if (els.obs) els.obs.value = item?.obs ?? "";

      els.modal.classList.add("is-open");
      els.modal.setAttribute("aria-hidden", "false");

      setTimeout(() => {
        if (els.cliente) els.cliente.focus();
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

    ["filterCliente", "filterStatus", "filterForma"].forEach((k) => {
      if (els[k]) els[k].addEventListener("change", render);
    });
    if (els.filterProcesso) els.filterProcesso.addEventListener("input", render);
    if (els.filterSearch) els.filterSearch.addEventListener("input", render);

    if (els.clear)
      els.clear.addEventListener("click", () => {
        if (els.filterCliente) els.filterCliente.value = "";
        if (els.filterStatus) els.filterStatus.value = "";
        if (els.filterForma) els.filterForma.value = "";
        if (els.filterProcesso) els.filterProcesso.value = "";
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
        if (wasDone) tWarning("Recebimento reaberto.");
        else tSuccess("Recebimento confirmado.");
        return;
      }

      if (act === "del") {
        openDelModal(id);
        return;
      }
    });

    if (els.form) {
      els.form.addEventListener("submit", () => {
        const isEdit = Boolean(els.id && els.id.value);
        const existing = isEdit ? getById(els.id.value) : null;

        const id = isEdit ? String(els.id.value) : String(uid());

        const payload = {
          id,
          cliente: normalizeCliente((els.cliente?.value || "").trim()),
          valor: parseMoneyInput(els.valor?.value || ""),
          data: els.data?.value || "",
          forma: normalizeForma(els.forma?.value || ""),
          processo: normalizeProcesso(els.processo?.value || ""),
          obs: normalizeObs(els.obs?.value || ""),
          status: isEdit ? existing?.status || "open" : "open",
          createdAt: existing?.createdAt || Date.now(),
        };

        if (!payload.cliente || !payload.data || !payload.forma) {
          tDanger("Preencha os campos obrigatórios.");
          return;
        }

        const rawValor = String(els.valor?.value || "").trim();
        if (!rawValor || payload.valor <= 0) {
          tDanger("Informe um valor válido (somente números).");
          if (els.valor) els.valor.focus();
          return;
        }

        upsert(payload);
        closeModal();
        render();
        tSuccess(isEdit ? "Alterações salvas." : "Recebível cadastrado.");
      });
    }

    if (els.delClose) els.delClose.addEventListener("click", closeDelModal);
    if (els.delCancel) els.delCancel.addEventListener("click", closeDelModal);

    if (els.delConfirm) {
      els.delConfirm.addEventListener("click", () => {
        if (pendingDeleteId == null) return;

        removeById(pendingDeleteId);
        closeDelModal();
        render();
        tSuccess("Recebível excluído.");
      });
    }

    function adjustCompactButtons() {
      const w = window.innerWidth || 1024;
      const compact = w <= 420;
      if (els.newBtn) els.newBtn.classList.toggle("is-icon", compact);
      if (els.clear) els.clear.classList.toggle("is-icon", compact);
    }
    window.addEventListener("resize", adjustCompactButtons);

    // ---------------------------
    // Init
    // ---------------------------
    attachMoneyGuards(els.valor);
    attachTextNormalization(els.cliente, normalizeCliente);
    attachTextNormalization(els.forma, normalizeForma);
    attachTextNormalization(els.processo, normalizeProcesso);
    attachTextNormalization(els.obs, normalizeObs);

    loadStorage();
    viewMonth = new Date();

    // liga Ferramentas -> formas logo na entrada
    syncCatalogsFromTools();

    adjustCompactButtons();
    render();
  });
})();