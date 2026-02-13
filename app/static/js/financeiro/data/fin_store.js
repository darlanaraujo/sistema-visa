// app/static/js/financeiro/data/fin_store.js
// Camada de dados do módulo Financeiro (LocalStorage).
// Objetivo: centralizar CRUD, normalização, ordenação e eventos.
// NÃO altera layout. Apenas fornece API para as páginas.
// Futuro: trocar a implementação interna por chamadas ao BD mantendo a mesma API.

(function () {
  // ---------- Keys ----------
  const KEYS = {
    // módulos (já existentes)
    cpRows: "fin_cp_rows_v1",
    crRows: "fin_cr_rows_v1",

    // referências (criadas na Etapa 1.2)
    imoveis: "fin_ref_imoveis_v1",
    categorias: "fin_ref_categorias_v1",
    formas: "fin_ref_formas_v1",
    clientes: "fin_ref_clientes_v1",

    // meta
    version: "fin_store_version_v1",
  };

  // ---------- Tools (Ferramentas) ----------
  // Ferramentas grava em: tools_ns_<namespace>_v1
  const TOOLS = {
    prefix: "tools_ns_",
    version: "v1",
  };

  function toolsKey(ns) {
    return `${TOOLS.prefix}${ns}_${TOOLS.version}`;
  }

  // ---------- Utils ----------
  function now() {
    return Date.now();
  }

  function normalizeStr(s) {
    return String(s ?? "")
      .trim()
      .replace(/\s+/g, " ");
  }

  function safeParse(json, fallback) {
    try {
      return JSON.parse(json);
    } catch (_) {
      return fallback;
    }
  }

  function lsGetRaw(key) {
    try {
      return localStorage.getItem(key);
    } catch (_) {
      return null;
    }
  }

  function lsSetRaw(key, val) {
    try {
      localStorage.setItem(key, String(val));
    } catch (_) {}
  }

  function lsGetArr(key) {
    const raw = lsGetRaw(key);
    if (!raw) return [];
    const arr = safeParse(raw, []);
    return Array.isArray(arr) ? arr : [];
  }

  function lsSetArr(key, arr) {
    try {
      localStorage.setItem(key, JSON.stringify(Array.isArray(arr) ? arr : []));
    } catch (_) {}
  }

  function uid(prefix = "ID") {
    return `${prefix}-${now()}-${Math.floor(Math.random() * 1000)}`;
  }

  function dateKey(iso) {
    const t = Date.parse(String(iso || "") + "T00:00:00");
    return Number.isFinite(t) ? t : 9e15;
  }

  function todayAtMidnight() {
    const d = new Date();
    return new Date(d.getFullYear(), d.getMonth(), d.getDate());
  }

  function isOverdue(iso, status) {
    if (status === "done") return false;
    const d = new Date(String(iso || "") + "T00:00:00");
    if (Number.isNaN(d.getTime())) return false;
    return d < todayAtMidnight();
  }

  function sortByPriority(list, getText) {
    if (!Array.isArray(list)) return list;

    list.sort((a, b) => {
      const ao = a?.status === "open";
      const bo = b?.status === "open";

      const aOver = ao && isOverdue(a?.data, a?.status);
      const bOver = bo && isOverdue(b?.data, b?.status);
      if (aOver !== bOver) return aOver ? -1 : 1;

      const da = dateKey(a?.data);
      const db = dateKey(b?.data);
      if (da !== db) return da - db;

      const sa = ao ? 0 : 1;
      const sb = bo ? 0 : 1;
      if (sa !== sb) return sa - sb;

      const ta = normalizeStr(getText ? getText(a) : "");
      const tb = normalizeStr(getText ? getText(b) : "");
      return ta.localeCompare(tb, "pt-BR", { sensitivity: "base" });
    });

    return list;
  }

  // ---------- Normalizers ----------
  function normalizeCP(r) {
    return {
      id: String(r?.id || uid("CP")),
      conta: normalizeStr(r?.conta),
      valor: Number(r?.valor || 0),
      imovel: normalizeStr(r?.imovel),
      categoria: normalizeStr(r?.categoria),
      data: String(r?.data || ""),
      fixa: Boolean(r?.fixa),
      status: r?.status === "done" ? "done" : "open",

      templateId: r?.templateId || null,
      instanceMonth: r?.instanceMonth || (String(r?.data || "").slice(0, 7) || ""),
      createdAt: Number(r?.createdAt || now()),
      updatedAt: Number(r?.updatedAt || now()),
    };
  }

  function normalizeCR(r) {
    return {
      id: String(r?.id || uid("CR")),
      cliente: normalizeStr(r?.cliente),
      valor: Number(r?.valor || 0),
      data: String(r?.data || ""),
      forma: normalizeStr(r?.forma),
      processo: normalizeStr(r?.processo),
      obs: normalizeStr(r?.obs),
      status: r?.status === "done" ? "done" : "open",
      createdAt: Number(r?.createdAt || now()),
      updatedAt: Number(r?.updatedAt || now()),
    };
  }

  // ---------- CRUD base ----------
  function list(key) {
    return lsGetArr(key);
  }

  function getById(key, id) {
    const arr = lsGetArr(key);
    return arr.find((x) => String(x?.id) === String(id)) || null;
  }

  function upsert(key, entity, normalizer) {
    const arr = lsGetArr(key);
    const item = normalizer ? normalizer(entity) : entity;

    item.updatedAt = now();

    const idx = arr.findIndex((x) => String(x?.id) === String(item.id));
    if (idx >= 0) arr[idx] = item;
    else arr.unshift(item);

    lsSetArr(key, arr);
    emitChange(key);
    return item;
  }

  function remove(key, id) {
    let arr = lsGetArr(key);
    const before = arr.length;
    arr = arr.filter((x) => String(x?.id) !== String(id));
    lsSetArr(key, arr);
    if (arr.length !== before) emitChange(key);
    return arr.length !== before;
  }

  // ---------- Events ----------
  const EVT = "fin:change";

  function emitChange(key) {
    try {
      window.dispatchEvent(new CustomEvent(EVT, { detail: { key, at: now() } }));
    } catch (_) {}
  }

  // ---------- Tools reader ----------
  function toolsList(ns) {
    const key = toolsKey(ns);
    const arr = lsGetArr(key);

    // normaliza formato vindo do Ferramentas
    const out = (Array.isArray(arr) ? arr : []).map((x) => ({
      id: String(x?.id ?? ""),
      name: normalizeStr(x?.name ?? ""),
      active: Boolean(x?.active ?? true),
      createdAt: Number(x?.createdAt || 0),
      updatedAt: Number(x?.updatedAt || 0),
    }));

    // ordena por nome (pt-BR)
    out.sort((a, b) => a.name.localeCompare(b.name, "pt-BR", { sensitivity: "base" }));
    return out;
  }

  function toolsActiveNames(ns) {
    return toolsList(ns)
      .filter((x) => x.active && x.name)
      .map((x) => x.name);
  }

  // ---------- Public API ----------
  const Store = {
    KEYS,
    EVT,

    // util
    now,
    uid,
    normalizeStr,
    dateKey,
    isOverdue,
    sortByPriority,

    // refs (legado do financeiro)
    refs: {
      listImoveis: () => list(KEYS.imoveis),
      listCategorias: () => list(KEYS.categorias),
      listFormas: () => list(KEYS.formas),
      listClientes: () => list(KEYS.clientes),
    },

    // tools (Ferramentas)
    tools: {
      listNs: (ns) => toolsList(ns),
      activeNames: (ns) => toolsActiveNames(ns),

      // atalhos do seu fluxo atual
      getImoveis: () => toolsActiveNames("financeiro.imoveis"),
      getCategorias: () => toolsActiveNames("financeiro.categorias"),
      getFormas: () => toolsActiveNames("financeiro.formas"),
    },

    // contas a pagar
    cp: {
      list: () => list(KEYS.cpRows).map(normalizeCP),
      rawList: () => list(KEYS.cpRows),
      getById: (id) => getById(KEYS.cpRows, id),
      upsert: (row) => upsert(KEYS.cpRows, row, normalizeCP),
      remove: (id) => remove(KEYS.cpRows, id),
      normalize: normalizeCP,
      sort: (arr) => sortByPriority(arr, (x) => x?.conta),
    },

    // contas a receber
    cr: {
      list: () => list(KEYS.crRows).map(normalizeCR),
      rawList: () => list(KEYS.crRows),
      getById: (id) => getById(KEYS.crRows, id),
      upsert: (row) => upsert(KEYS.crRows, row, normalizeCR),
      remove: (id) => remove(KEYS.crRows, id),
      normalize: normalizeCR,
      sort: (arr) => sortByPriority(arr, (x) => x?.cliente),
    },
  };

  window.FinStore = window.FinStore || Store;

  if (!lsGetRaw(KEYS.version)) {
    lsSetRaw(KEYS.version, "1");
  }
})();