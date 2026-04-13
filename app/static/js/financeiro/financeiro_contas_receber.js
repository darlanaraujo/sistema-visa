// app/static/js/financeiro_contas_receber.js
(function () {
  async function waitPrivateAreaBoot() {
    try {
      if (window.__SV_PRIVATE_BOOT__ && typeof window.__SV_PRIVATE_BOOT__.ready === "function") {
        await window.__SV_PRIVATE_BOOT__.ready();
      }
    } catch (_) {}
    try {
      await (window.FinStore?.ready?.() ?? window.FinStore?.init?.() ?? true);
    } catch (_) {}
  }

  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(async function () {
    await waitPrivateAreaBoot();
    try { await window.FinRefs?.ensureAll?.(); } catch (_) {}
    try { await window.FinRefsBridge?.exportAll?.(); } catch (_) {}
    let rows = [];
    let viewMonth = new Date();
    let pendingDeleteId = null;
    const cadastroLookupSeq = { form: 0 };
    let cadastroCatalog = [];
    const cadastroLookupTimers = { form: null };

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
      cadastroId: el("crCadastroId"),
      clienteMenu: el("crClienteMenu"),
      clienteDocumento: el("crClienteDocumento"),
      valor: el("crValor"),
      data: el("crData"),
      forma: el("crForma"),
      processo: el("crProcesso"),
      produto: el("crProduto"),
      parcelas: el("crParcelas"),

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

    function appBase() {
      const path = String(window.location.pathname || "");
      const idx = path.indexOf("/app/templates/");
      return idx >= 0 ? path.slice(0, idx) : "";
    }

    function apiUrl(path) {
      return `${appBase()}${path}`;
    }

    function normalizeDigits(value) {
      return String(value || "").replace(/\D+/g, "");
    }

    function normalizeLookupText(value) {
      return normalizeSpaces(String(value || "")).toLowerCase();
    }

    function cadastroDisplayName(item) {
      return normalizeCliente(item?.label || "");
    }

    function cadastroSearchLabel(item) {
      return normalizeSpaces(String(item?.searchLabel || item?.label || ""));
    }

    function cadastroDocument(item) {
      return normalizeSpaces(String(item?.documento || ""));
    }

    function cadastroPhone(item) {
      return normalizeSpaces(String(item?.telefone || ""));
    }

    function cadastroMeta(item) {
      return cadastroDocument(item) || cadastroPhone(item);
    }

    function lookupMenu(target) {
      return els.clienteMenu;
    }

    function lookupInput(target) {
      return target === "filter" ? els.filterCliente : els.cliente;
    }

    function closeCadastroMenu(target) {
      const menu = lookupMenu(target);
      if (!menu) return;
      menu.hidden = true;
      menu.innerHTML = "";
    }

    function setSelectedCadastro(target, item) {
      if (els.cadastroId) els.cadastroId.value = item ? String(item.id || "") : "";
      if (els.cliente) els.cliente.value = item ? cadastroDisplayName(item) : "";
      if (els.clienteDocumento) els.clienteDocumento.value = item ? cadastroMeta(item) : "";
    }

    function clearSelectedCadastroState(target) {
      if (els.cadastroId) els.cadastroId.value = "";
      if (els.clienteDocumento) els.clienteDocumento.value = "";
    }

    function filterCadastroItems(items, term) {
      const text = normalizeLookupText(term);
      const digits = normalizeDigits(term);
      if (!text && !digits) return [];

      return (Array.isArray(items) ? items : []).filter((item) => {
        const searchLabel = normalizeLookupText(cadastroSearchLabel(item));
        const displayName = normalizeLookupText(cadastroDisplayName(item));
        const documento = normalizeDigits(item?.documento || "");
        const telefone = normalizeDigits(item?.telefone || "");
        return searchLabel.includes(text) || displayName.includes(text) || (digits !== "" && (documento.includes(digits) || telefone.includes(digits)));
      });
    }

    function renderCadastroMenu(target, items, term = "") {
      const menu = lookupMenu(target);
      if (!menu) return;

      const cleanedTerm = normalizeSpaces(term);
      if (!cleanedTerm) {
        closeCadastroMenu(target);
        return;
      }

      const list = filterCadastroItems(items, cleanedTerm).slice(0, 8);
      if (!list.length) {
        if (target === "form") {
          menu.innerHTML = `
            <button class="fin-cad-lookup__item fin-cad-lookup__create" type="button" data-cr-create-cadastro="cliente">
              <span class="fin-cad-lookup__item-icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
              <span class="fin-cad-lookup__item-body">
                <strong class="fin-cad-lookup__name">Cadastro não encontrado</strong>
                <span class="fin-cad-lookup__meta">Clique aqui para cadastrar um novo cliente sem sair do Financeiro.</span>
              </span>
            </button>
          `;
          menu.hidden = false;
          const createBtn = menu.querySelector("[data-cr-create-cadastro]");
          createBtn?.addEventListener("click", () => {
            window.FinCadastroInline?.open?.("cliente", "Novo cliente");
          });
          return;
        }
        menu.innerHTML = '<div class="fin-cad-lookup__empty">Nenhum cadastro encontrado.</div>';
        menu.hidden = false;
        return;
      }

      menu.innerHTML = list
        .map((item, index) => `
          <button class="fin-cad-lookup__item" type="button" data-cr-option="${escapeHtml(String(index))}">
            <span class="fin-cad-lookup__item-icon"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span>
            <span class="fin-cad-lookup__item-body">
              <strong class="fin-cad-lookup__name">${escapeHtml(cadastroDisplayName(item))}</strong>
              <span class="fin-cad-lookup__meta">${escapeHtml(cadastroMeta(item) || "Telefone não informado")}</span>
            </span>
          </button>
        `)
        .join("");
      menu.hidden = false;

      Array.from(menu.querySelectorAll("[data-cr-option]")).forEach((node) => {
        node.addEventListener("mousedown", (event) => {
          event.preventDefault();
        });
        node.addEventListener("click", () => {
          const idx = Number(node.getAttribute("data-cr-option") || -1);
          const selected = list[idx];
          if (!selected) return;
          setSelectedCadastro(target, selected);
          closeCadastroMenu(target);
          if (target === "filter") render();
        });
      });
    }

    function findCadastroByText(items, rawValue) {
      const text = normalizeLookupText(rawValue);
      const digits = normalizeDigits(rawValue);
      if (!text && !digits) return null;
      return (Array.isArray(items) ? items : []).find((item) => {
        const searchLabel = normalizeLookupText(cadastroSearchLabel(item));
        const displayName = normalizeLookupText(cadastroDisplayName(item));
        const documento = normalizeDigits(item?.documento || "");
        const telefone = normalizeDigits(item?.telefone || "");
        return searchLabel === text || displayName === text || (digits !== "" && (documento === digits || telefone === digits));
      }) || null;
    }

    async function fetchCadastros(target, term = "") {
      const seqKey = "form";
      const sequence = ++cadastroLookupSeq[seqKey];
      const url = new URL(apiUrl("/public_php/api/financeiro_cadastros_lookup.php"), window.location.origin);
      if (String(term || "").trim() !== "") {
        url.searchParams.set("term", String(term || "").trim());
      }
      url.searchParams.set("limit", "60");

      const response = await fetch(url.toString(), {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      });
      const payload = await response.json().catch(() => null);
      if (!response.ok || !payload || payload.success !== true || !payload.data) {
        throw new Error(payload?.error || "Não foi possível consultar os cadastros.");
      }
      if (sequence !== cadastroLookupSeq[seqKey]) {
        return [];
      }
      return Array.isArray(payload.data.items) ? payload.data.items : [];
    }

    async function refreshCadastroCatalog(target, term = "") {
      const items = await fetchCadastros(target, term);
      cadastroCatalog = items;
      renderCadastroMenu(target, items, term);
      return items;
    }

    function scheduleCadastroCatalogRefresh(target, term = "", immediate = false) {
      const timerKey = "form";
      if (cadastroLookupTimers[timerKey]) {
        window.clearTimeout(cadastroLookupTimers[timerKey]);
      }
      cadastroLookupTimers[timerKey] = window.setTimeout(() => {
        refreshCadastroCatalog(target, term).catch(() => {});
      }, immediate ? 0 : 220);
    }

    async function resolveCadastroSelection(inputEl, hiddenEl, target) {
      if (!inputEl || !hiddenEl) return null;
      const rawValue = String(inputEl.value || "").trim();
      if (!rawValue) {
        setSelectedCadastro(target, null);
        closeCadastroMenu(target);
        return null;
      }

      const currentCatalog = cadastroCatalog;
      let selected = findCadastroByText(currentCatalog, rawValue);
      if (!selected) {
        try {
          const items = await refreshCadastroCatalog(target, rawValue);
          selected = findCadastroByText(items, rawValue);
        } catch (_) {
          return null;
        }
      }

      if (!selected) {
      if (els.clienteDocumento) {
        els.clienteDocumento.value = "";
      }
      return null;
      }

      setSelectedCadastro(target, selected);
      closeCadastroMenu(target);
      return selected;
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
    function normalizeSpaces(s) {
      return String(s || "").replace(/\s+/g, " ").trim();
    }

    function upperPT(s) {
      return normalizeSpaces(s).toLocaleUpperCase("pt-BR");
    }

    function normalizeCliente(s) {
      return upperPT(s);
    }

    function normalizeForma(s) {
      return upperPT(s);
    }

    function normalizeProcesso(s) {
      return upperPT(s);
    }

    function normalizeObs(s) {
      return upperPT(s);
    }

    function clampParcelas(n) {
      const v = Number(n || 1);
      if (!Number.isFinite(v)) return 1;
      return Math.min(12, Math.max(1, Math.trunc(v)));
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

    function uidGroup() {
      return `CRG-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
    }

    function addMonthsIso(iso, plusMonths) {
      const base = new Date(String(iso || "") + "T00:00:00");
      if (Number.isNaN(base.getTime())) return String(iso || "");
      const d = new Date(base.getFullYear(), base.getMonth() + Number(plusMonths || 0), base.getDate());
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, "0");
      const day = String(d.getDate()).padStart(2, "0");
      return `${y}-${m}-${day}`;
    }

    function parcelaInfo(r) {
      const total = clampParcelas(r?.totalParcelas || 1);
      const atual = Math.min(total, clampParcelas(r?.parcelaAtual || 1));
      if (total <= 1) return "";
      return `${atual}/${total}`;
    }

    function processoCellText(r) {
      const proc = String(r?.processo || "").trim();
      const parc = parcelaInfo(r);
      if (proc && parc) return `${proc} • ${parc}`;
      if (proc) return proc;
      return parc;
    }

    function processoCellHtml(r) {
      const text = processoCellText(r);
      if (!text) return "";

      const loteId = Number(r?.loteId || 0);
      if (!Number.isFinite(loteId) || loteId <= 0) {
        return escapeHtml(text);
      }

      const href = `${appBase()}/app/templates/lotes.php?lote=${encodeURIComponent(String(loteId))}`;
      return `<a class="cr-process-link" href="${escapeHtml(href)}">${escapeHtml(text)}</a>`;
    }

    // ---------------------------
    // Normalização de registro
    // ---------------------------
    function normalizeRow(r) {
      const base = r || {};
      const fromObs = String(base.obs || "").match(/(\d{1,2})\s*\/\s*(\d{1,2})/);
      const totalParcelas = clampParcelas(base.totalParcelas || fromObs?.[2] || 1);
      const parcelaAtual = Math.min(totalParcelas, clampParcelas(base.parcelaAtual || fromObs?.[1] || 1));
      const cadastroId = Number(base.cadastroId ?? base.cadastro_id ?? 0);
      return {
        id: base.id ?? uid(),
        cliente: normalizeCliente(base.cliente ?? ""),
        loteId: Number(base.loteId ?? base.lote_id ?? 0) || null,
        cadastroId: Number.isFinite(cadastroId) && cadastroId > 0 ? cadastroId : null,
        clienteDocumento: upperPT(base.clienteDocumento ?? base.cliente_documento ?? ""),
        valor: Number(base.valor || 0),
        data: String(base.data || ""),
        forma: normalizeForma(base.forma ?? ""),
        processo: normalizeProcesso(base.processo ?? ""),
        produto: upperPT(base.produto ?? ""),
        obs: normalizeObs(base.obs ?? (totalParcelas > 1 ? `Parcela ${parcelaAtual}/${totalParcelas}` : "")),
        totalParcelas,
        parcelaAtual,
        grupoParcelaId: String(base.grupoParcelaId || ""),
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

    function finStore() {
      return window.FinStore && typeof window.FinStore === "object" ? window.FinStore : null;
    }

    function storeGetRows() {
      const fs = finStore();
      try {
        if (fs?.cr && typeof fs.cr.getRows === "function") {
          const v = fs.cr.getRows();
          return Array.isArray(v) ? v : [];
        }
        if (fs?.cr && typeof fs.cr.rowsGet === "function") {
          const v = fs.cr.rowsGet();
          return Array.isArray(v) ? v : [];
        }
      } catch (_) {}

      return [];
    }

    async function storeSetRows(nextRows) {
      const fs = finStore();
      try {
        if (fs?.cr && typeof fs.cr.setRows === "function") {
          return fs.cr.setRows(nextRows);
        }
        if (fs?.cr && typeof fs.cr.rowsSet === "function") {
          return fs.cr.rowsSet(nextRows);
        }
      } catch (_) {}
      return false;
    }

    async function loadStorage() {
      try {
        const storedRows = storeGetRows();
        if (Array.isArray(storedRows) && storedRows.length) {
          rows = storedRows.map(normalizeRow);
        } else {
          rows = [];
        }
      } catch (_) {
        rows = [];
      }
    }

    async function saveStorage() {
      try { await storeSetRows(rows); } catch (_) {}
    }

    function alignInitialViewMonth() {
      if (!Array.isArray(rows) || !rows.length) return;

      const hasCurrentMonth = rows.some((r) => sameMonth(r?.data, viewMonth));
      if (hasCurrentMonth) return;

      let latest = null;
      for (const r of rows) {
        const d = new Date(String(r?.data || "") + "T00:00:00");
        if (Number.isNaN(d.getTime())) continue;
        if (!latest || d > latest) latest = d;
      }

      if (!latest) return;
      if (latest.getFullYear() < viewMonth.getFullYear()) return;
      if (latest.getFullYear() === viewMonth.getFullYear() && latest.getMonth() <= viewMonth.getMonth()) return;
      viewMonth = new Date(latest.getFullYear(), latest.getMonth(), 1);
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
        .filter((r) => {
          if (!cliente) return true;
          const byName = String(r.cliente || "").toLowerCase();
          const byDoc = normalizeDigits(r.clienteDocumento || "");
          return byName.includes(cliente.toLowerCase()) || (normalizeDigits(cliente) !== "" && byDoc.includes(normalizeDigits(cliente)));
        })
        .filter((r) => !status || r.status === status)
        .filter((r) => !forma || r.forma === forma)
        .filter((r) => !processo || String(r.processo || "").toLowerCase().includes(processo))
        .filter((r) => {
          if (!search) return true;
          const a = String(r.cliente || "").toLowerCase();
          const b = String(r.obs || "").toLowerCase();
          const c = normalizeDigits(r.clienteDocumento || "");
          const termDigits = normalizeDigits(search);
          return a.includes(search) || b.includes(search) || (termDigits !== "" && c.includes(termDigits));
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
              <td class="t-center">${processoCellHtml(r)}</td>
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

    async function upsert(item) {
      const normalized = normalizeRow(item);

      const idx = rows.findIndex((r) => String(r.id) === String(normalized.id));
      if (idx >= 0) rows[idx] = normalized;
      else rows.unshift(normalized);

      await saveStorage();
    }

    async function removeById(id) {
      rows = rows.filter((r) => String(r.id) !== String(id));
      await saveStorage();
    }

    // ---------------------------
    // Modal
    // ---------------------------
    // Fecha modal com transição de saída antes de remover o estado aberto.
    const MODAL_CLOSE_MS = 360;
    function openModalAnimated(modalEl) {
      if (!modalEl) return;
      modalEl.classList.remove("is-closing");
      modalEl.classList.add("is-open");
      modalEl.setAttribute("aria-hidden", "false");
      // Força frame de entrada para garantir transição perceptível.
      requestAnimationFrame(() => requestAnimationFrame(() => {
        modalEl.classList.remove("is-closing");
      }));
    }

    function closeModalAnimated(modalEl) {
      if (!modalEl) return;
      if (!modalEl.classList.contains("is-open")) {
        modalEl.setAttribute("aria-hidden", "true");
        return;
      }
      modalEl.classList.add("is-closing");
      window.setTimeout(() => {
        modalEl.classList.remove("is-open", "is-closing");
        modalEl.setAttribute("aria-hidden", "true");
      }, MODAL_CLOSE_MS);
    }

    function openModal(mode, item) {
      if (!els.modal) return;

      // atualiza catálogo de formas ao abrir modal
      syncCatalogsFromTools();

      if (els.modalTitle) els.modalTitle.textContent = mode === "edit" ? "Editar lançamento" : "Novo lançamento";

      if (els.id) els.id.value = item?.id ?? "";
      setSelectedCadastro("form", item ? {
        id: item.cadastroId,
        label: item.cliente,
        documento: item.clienteDocumento,
        telefone: item.clienteDocumento,
      } : null);
      if (els.valor) els.valor.value = item?.valor ?? "";
      if (els.data) els.data.value = item?.data ?? "";

      if (els.forma) {
        const v = item?.forma ?? "";
        if (v) ensureRemovedOption(els.forma, v);
        els.forma.value = v;
      }

      if (els.processo) els.processo.value = item?.processo ?? "";
      if (els.produto) els.produto.value = item?.produto ?? "";
      if (els.parcelas) {
        const total = clampParcelas(item?.totalParcelas || 1);
        els.parcelas.value = String(total);
        els.parcelas.disabled = mode === "edit";
      }

      closeCadastroMenu("form");

      openModalAnimated(els.modal);

      setTimeout(() => {
        if (els.cliente) els.cliente.focus();
      }, 50);
    }

    function closeModal() {
      if (!els.modal) return;
      closeModalAnimated(els.modal);
    }

    // ---------------------------
    // Delete modal
    // ---------------------------
    function openDelModal(id) {
      pendingDeleteId = id;
      if (!els.delModal) return;
      openModalAnimated(els.delModal);
    }

    function closeDelModal() {
      pendingDeleteId = null;
      if (!els.delModal) return;
      closeModalAnimated(els.delModal);
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

    ["filterStatus", "filterForma"].forEach((k) => {
      if (els[k]) els[k].addEventListener("change", render);
    });
    if (els.filterCliente) {
      els.filterCliente.addEventListener("input", render);
    }
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

    if (els.cliente) {
      els.cliente.addEventListener("focus", () => {
        closeCadastroMenu("form");
      });
      els.cliente.addEventListener("input", () => {
        clearSelectedCadastroState("form");
        const term = String(els.cliente.value || "").trim();
        if (!term) {
          closeCadastroMenu("form");
        } else {
          scheduleCadastroCatalogRefresh("form", term);
        }
      });
      els.cliente.addEventListener("change", () => {
        resolveCadastroSelection(els.cliente, els.cadastroId, "form").catch(() => {});
      });
      els.cliente.addEventListener("blur", () => {
        window.setTimeout(() => {
          resolveCadastroSelection(els.cliente, els.cadastroId, "form").catch(() => {});
          closeCadastroMenu("form");
        }, 120);
      });
      els.cliente.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeCadastroMenu("form");
      });
    }

    document.addEventListener("click", (e) => {
      const target = e.target;
      if (!(target instanceof Element)) return;
      if (!target.closest('[data-cr-lookup="form"]')) {
        closeCadastroMenu("form");
      }
    });

    els.tbody.addEventListener("click", async (e) => {
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
        await upsert(item);
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
      els.form.addEventListener("submit", async (e) => {
        try { e.preventDefault(); } catch (_) {}

        const isEdit = Boolean(els.id && els.id.value);
        const existing = isEdit ? getById(els.id.value) : null;

        const id = isEdit ? String(els.id.value) : String(uid());

        const totalParcelas = clampParcelas(els.parcelas?.value || existing?.totalParcelas || 1);
        const selectedCadastro = await resolveCadastroSelection(els.cliente, els.cadastroId, "form");

        const payload = {
          id,
          cadastroId: Number(els.cadastroId?.value || existing?.cadastroId || 0) || null,
          cliente: cadastroDisplayName(selectedCadastro) || normalizeCliente(els.cliente?.value || "") || existing?.cliente || "",
          clienteDocumento: normalizeSpaces(cadastroMeta(selectedCadastro) || existing?.clienteDocumento || ""),
          valor: parseMoneyInput(els.valor?.value || ""),
          data: els.data?.value || "",
          forma: normalizeForma(els.forma?.value || ""),
          processo: normalizeProcesso(els.processo?.value || ""),
          produto: normalizeSpaces(els.produto?.value || ""),
          status: isEdit ? existing?.status || "open" : "open",
          createdAt: existing?.createdAt || Date.now(),
          totalParcelas,
          parcelaAtual: isEdit ? clampParcelas(existing?.parcelaAtual || 1) : 1,
          grupoParcelaId: isEdit ? String(existing?.grupoParcelaId || "") : "",
          obs: "",
        };

        payload.obs = payload.totalParcelas > 1 ? `Parcela ${payload.parcelaAtual}/${payload.totalParcelas}` : "";

        if (!payload.data || !payload.forma) {
          tDanger("Preencha os campos obrigatórios.");
          if (!payload.data && els.data) els.data.focus();
          return;
        }

        const rawValor = String(els.valor?.value || "").trim();
        if (!rawValor || payload.valor <= 0) {
          tDanger("Informe um valor válido (somente números).");
          if (els.valor) els.valor.focus();
          return;
        }

        if (isEdit) {
          await upsert(payload);
        } else if (payload.totalParcelas > 1) {
          const groupId = uidGroup();
          for (let i = 1; i <= payload.totalParcelas; i += 1) {
            await upsert({
              ...payload,
              id: String(uid()),
              data: addMonthsIso(payload.data, i - 1),
              parcelaAtual: i,
              grupoParcelaId: groupId,
              obs: `Parcela ${i}/${payload.totalParcelas}`,
            });
          }
        } else {
          await upsert(payload);
        }
        closeModal();
        render();
        tSuccess(isEdit ? "Alterações salvas." : (payload.totalParcelas > 1 ? "Parcelas cadastradas." : "Recebível cadastrado."));
      });
    }

    if (els.delClose) els.delClose.addEventListener("click", closeDelModal);
    if (els.delCancel) els.delCancel.addEventListener("click", closeDelModal);

    if (els.delConfirm) {
      els.delConfirm.addEventListener("click", async () => {
        if (pendingDeleteId == null) return;

        await removeById(pendingDeleteId);
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
    attachTextNormalization(els.clienteDocumento, upperPT);
    attachTextNormalization(els.forma, normalizeForma);
    attachTextNormalization(els.processo, normalizeProcesso);
    attachTextNormalization(els.produto, upperPT);
    attachTextNormalization(els.obs, normalizeObs);

    window.addEventListener("fin:inline-cadastro-saved", (event) => {
      const item = event.detail || {};
      const id = String(item.id || "");
      if (!id) return;

      const nextEntry = {
        id: Number(item.id || 0),
        label: String(item.nome || item.razaoSocial || ""),
        searchLabel: [item.nome || item.razaoSocial || "", item.documento || "", item.celular || item.whatsapp || item.telefone || ""].filter(Boolean).join(" • "),
        documento: String(item.documento || ""),
        telefone: String(item.celular || item.whatsapp || item.telefone || ""),
      };

      const existingIndex = cadastroCatalog.findIndex((entry) => String(entry.id || "") === id);
      if (existingIndex >= 0) cadastroCatalog.splice(existingIndex, 1, nextEntry);
      else cadastroCatalog.unshift(nextEntry);

      setSelectedCadastro("form", nextEntry);
    });

    await loadStorage();
    viewMonth = new Date();
    alignInitialViewMonth();

    // liga Ferramentas -> formas logo na entrada
    syncCatalogsFromTools();

    adjustCompactButtons();
    render();
  });
})();
