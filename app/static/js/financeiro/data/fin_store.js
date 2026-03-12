// app/static/js/financeiro/data/fin_store.js
(function () {
  if (!window.SysStore) {
    console.error("SysStore não carregado antes do FinStore.");
    return;
  }

  const KEYS = {
    cpRows: "fin_cp_rows_v1",
    cpTemplates: "fin_cp_templates_v1",
    crRows: "fin_cr_rows_v1",
    reportsFav: "fin_reports_favorites_v1",
    imoveis: "fin_ref_imoveis_v1",
    categorias: "fin_ref_categorias_v1",
    formas: "fin_ref_formas_v1",
    clientes: "fin_ref_clientes_v1",
    version: "fin_store_version_v1",
  };

  const EVT = "fin:change";
  const cache = new Map();
  let initPromise = null;
  let ready = false;
  const REF_KEYS = new Set([KEYS.imoveis, KEYS.categorias, KEYS.formas, KEYS.clientes]);

  function now() {
    return Date.now();
  }

  function normalizeStr(s) {
    return String(s ?? "").trim().replace(/\s+/g, " ");
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

  function normalizeRefName(item) {
    if (typeof item === "string") return normalizeStr(item);
    if (!item || typeof item !== "object") return "";

    const active = (item.active !== false) && (item.ativo !== false);
    if (!active) return "";

    return normalizeStr(item.name ?? item.nome ?? "");
  }

  function getRefNames(key) {
    const arr = getArr(key);
    const out = [];
    const seen = new Set();

    arr.forEach((item) => {
      const name = normalizeRefName(item);
      const sig = name.toLowerCase();
      if (!name || seen.has(sig)) return;
      seen.add(sig);
      out.push(name);
    });

    return out.sort((a, b) => a.localeCompare(b, "pt-BR", { sensitivity: "base" }));
  }

  function emitChange(key) {
    window.dispatchEvent(new CustomEvent(EVT, { detail: { key, at: now() } }));
  }

  async function init() {
    if (ready) return true;
    if (initPromise) return initPromise;
    const keys = Object.values(KEYS);
    initPromise = Promise.all(keys.map(async (key) => {
      const value = await window.SysStore.get(key).catch(() => null);
      cache.set(key, Array.isArray(value) ? value : (value ?? null));
    })).then(() => {
      ready = true;
      return true;
    });
    return initPromise;
  }

  function syncFromSysStore(key, removed) {
    if (!key || !REF_KEYS.has(key)) return;

    const fallback = removed ? null : [];
    const latest = window.SysStore?.getCached?.(key, fallback);
    cache.set(key, Array.isArray(latest) ? latest : (latest ?? null));
    emitChange(key);
  }

  function getArr(key) {
    const arr = cache.get(key);
    return Array.isArray(arr) ? arr : [];
  }

  async function setArr(key, arr) {
    const next = Array.isArray(arr) ? arr : [];
    const ok = await window.SysStore.set(key, next);
    if (!ok) return false;
    cache.set(key, next);
    emitChange(key);
    return true;
  }

  function getById(key, id) {
    return getArr(key).find((x) => String(x?.id) === String(id)) || null;
  }

  async function upsert(key, entity, normalizer) {
    const arr = getArr(key).slice();
    const item = normalizer(entity);
    item.updatedAt = now();

    const idx = arr.findIndex((x) => String(x?.id) === String(item.id));
    if (idx >= 0) arr[idx] = item;
    else arr.unshift(item);

    const ok = await setArr(key, arr);
    return ok ? item : null;
  }

  async function remove(key, id) {
    let arr = getArr(key);
    const before = arr.length;
    arr = arr.filter((x) => String(x?.id) !== String(id));
    const ok = await setArr(key, arr);
    return ok ? (arr.length !== before) : false;
  }

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
    const totalParcelasRaw = Number(r?.totalParcelas || 1);
    const totalParcelas = Number.isFinite(totalParcelasRaw)
      ? Math.min(12, Math.max(1, Math.trunc(totalParcelasRaw)))
      : 1;

    const parcelaAtualRaw = Number(r?.parcelaAtual || 1);
    const parcelaAtual = Number.isFinite(parcelaAtualRaw)
      ? Math.min(totalParcelas, Math.max(1, Math.trunc(parcelaAtualRaw)))
      : 1;

    return {
      id: String(r?.id || uid("CR")),
      cliente: normalizeStr(r?.cliente),
      valor: Number(r?.valor || 0),
      data: String(r?.data || ""),
      forma: normalizeStr(r?.forma),
      processo: normalizeStr(r?.processo),
      obs: normalizeStr(r?.obs),
      totalParcelas,
      parcelaAtual,
      grupoParcelaId: normalizeStr(r?.grupoParcelaId),
      status: r?.status === "done" ? "done" : "open",
      createdAt: Number(r?.createdAt || now()),
      updatedAt: Number(r?.updatedAt || now()),
    };
  }

  const Store = {
    KEYS,
    EVT,
    init,
    isReady: () => ready === true,
    ready: () => init(),

    cp: {
      list: () => getArr(KEYS.cpRows).map(normalizeCP),
      getRows: () => getArr(KEYS.cpRows).map(normalizeCP),
      rowsGet: () => getArr(KEYS.cpRows).map(normalizeCP),
      setRows: async (rows) => setArr(KEYS.cpRows, (Array.isArray(rows) ? rows : []).map(normalizeCP)),
      rowsSet: async (rows) => setArr(KEYS.cpRows, (Array.isArray(rows) ? rows : []).map(normalizeCP)),
      getById: (id) => getById(KEYS.cpRows, id),
      upsert: async (row) => upsert(KEYS.cpRows, row, normalizeCP),
      remove: async (id) => remove(KEYS.cpRows, id),
      getTemplates: () => getArr(KEYS.cpTemplates),
      templatesGet: () => getArr(KEYS.cpTemplates),
      setTemplates: async (rows) => setArr(KEYS.cpTemplates, Array.isArray(rows) ? rows : []),
      templatesSet: async (rows) => setArr(KEYS.cpTemplates, Array.isArray(rows) ? rows : []),
    },

    cr: {
      list: () => getArr(KEYS.crRows).map(normalizeCR),
      getRows: () => getArr(KEYS.crRows).map(normalizeCR),
      rowsGet: () => getArr(KEYS.crRows).map(normalizeCR),
      setRows: async (rows) => setArr(KEYS.crRows, (Array.isArray(rows) ? rows : []).map(normalizeCR)),
      rowsSet: async (rows) => setArr(KEYS.crRows, (Array.isArray(rows) ? rows : []).map(normalizeCR)),
      getById: (id) => getById(KEYS.crRows, id),
      upsert: async (row) => upsert(KEYS.crRows, row, normalizeCR),
      remove: async (id) => remove(KEYS.crRows, id),
    },

    reports: {
      getFavorites: () => getArr(KEYS.reportsFav),
      setFavorites: async (rows) => setArr(KEYS.reportsFav, Array.isArray(rows) ? rows : []),
    },

    tools: {
      getImoveis: () => getRefNames(KEYS.imoveis),
      getCategorias: () => getRefNames(KEYS.categorias),
      getFormas: () => getRefNames(KEYS.formas),
      getClientes: () => getRefNames(KEYS.clientes),
    },
  };

  window.FinStore = window.FinStore || Store;

  window.addEventListener("sys:changed", (e) => {
    syncFromSysStore(e?.detail?.key || "", Boolean(e?.detail?.removed));
  });
})(window);
