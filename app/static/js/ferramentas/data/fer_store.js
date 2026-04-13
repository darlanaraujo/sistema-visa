// app/static/js/ferramentas/data/fer_store.js
(function () {
  if (!window.SysStore) {
    console.error("[FerStore] SysStore não carregado.");
    return;
  }

  const STORAGE_PREFIX = "tools_ns_";
  const VERSION = "v1";
  const SYS_PREFS_KEY = "tools_sys_prefs_v2";
  const MOVEMENTS_KEY = "tools_movements_v1";
  const EVT = "fin:change";
  const MAX_MOVEMENTS = 80;

  const state = {
    ready: false,
    prefs: null,
    tools: new Map(),
    movements: [],
  };
  let initPromise = null;

  function storageKey(ns) {
    return `${STORAGE_PREFIX}${ns}_${VERSION}`;
  }

  function normalizeUpperText(value) {
    return String(value || "").replace(/\s+/g, " ").trim().toLocaleUpperCase("pt-BR");
  }

  function normalizeToolItem(item) {
    const base = item && typeof item === "object" ? item : {};
    return {
      ...base,
      id: String(base.id || ""),
      name: normalizeUpperText(base.name || base.nome || ""),
      active: base.active !== false,
      createdAt: base.createdAt || Date.now(),
      updatedAt: base.updatedAt || Date.now(),
    };
  }

  function emitToolsChange(ns) {
    try {
      const evtName = (window.FinStore && window.FinStore.EVT) ? window.FinStore.EVT : EVT;
      window.dispatchEvent(new CustomEvent(evtName, { detail: { key: `tools:${ns}`, at: Date.now() } }));
    } catch (_) {}
  }

  async function init() {
    if (state.ready) return true;
    if (initPromise) return initPromise;

    initPromise = (async () => {
      const [prefs, movements] = await Promise.all([
        window.SysStore.get(SYS_PREFS_KEY).catch(() => null),
        window.SysStore.get(MOVEMENTS_KEY).catch(() => []),
      ]);
      state.prefs = prefs && typeof prefs === "object" ? prefs : {};
      state.movements = Array.isArray(movements) ? movements : [];
      state.ready = true;
      return true;
    })();

    return initPromise;
  }

  async function list(ns) {
    const key = storageKey(ns);
    if (state.tools.has(key)) {
      return state.tools.get(key);
    }
    const value = await window.SysStore.get(key).catch(() => []);
    const out = Array.isArray(value) ? value.map(normalizeToolItem) : [];
    state.tools.set(key, out);
    return out;
  }

  async function save(ns, items) {
    const key = storageKey(ns);
    const next = Array.isArray(items) ? items.map(normalizeToolItem) : [];
    const ok = await window.SysStore.set(key, next);
    if (!ok) return false;
    state.tools.set(key, next);
    emitToolsChange(ns);
    return true;
  }

  async function getSysPrefs() {
    await init();
    return state.prefs && typeof state.prefs === "object" ? state.prefs : {};
  }

  async function listMovements(limit = 20) {
    await init();
    const size = Number.isFinite(Number(limit)) ? Math.max(1, Math.min(200, Number(limit))) : 20;
    return state.movements.slice(0, size);
  }

  async function addMovement(entry) {
    await init();
    const payload = entry && typeof entry === "object" ? entry : {};
    const nextEntry = {
      id: String(payload.id || `ft-mov-${Date.now()}-${Math.floor(Math.random() * 1000)}`),
      scope: String(payload.scope || "ferramentas"),
      tipoEvento: String(payload.tipoEvento || "evento"),
      descricaoEvento: String(payload.descricaoEvento || "Movimentação registrada").toLocaleUpperCase("pt-BR"),
      responsavel: String(payload.responsavel || "").toLocaleUpperCase("pt-BR"),
      createdAt: String(payload.createdAt || new Date().toISOString()),
      payloadEstrutural: payload.payloadEstrutural && typeof payload.payloadEstrutural === "object"
        ? payload.payloadEstrutural
        : {},
    };

    const next = [nextEntry, ...state.movements].slice(0, MAX_MOVEMENTS);
    const ok = await window.SysStore.set(MOVEMENTS_KEY, next);
    if (!ok) return false;
    state.movements = next;
    emitToolsChange("movements");
    return true;
  }

  async function setSysPrefs(prefs) {
    const next = prefs && typeof prefs === "object" ? prefs : {};
    const ok = await window.SysStore.set(SYS_PREFS_KEY, next);
    if (!ok) return false;
    state.prefs = next;
    emitToolsChange("sistema.personalizacao");
    return true;
  }

  async function removeSysPrefs() {
    const ok = await window.SysStore.remove(SYS_PREFS_KEY);
    if (!ok) return false;
    state.prefs = {};
    emitToolsChange("sistema.personalizacao");
    return true;
  }

  window.FerStore = {
    EVT,
    init,
    isReady() {
      return state.ready === true;
    },
    ready() {
      return init();
    },
    storageKey,
    tools: {
      list,
      save,
      emitChange: emitToolsChange,
    },
    movements: {
      key: MOVEMENTS_KEY,
      list: listMovements,
      add: addMovement,
    },
    prefs: {
      key: SYS_PREFS_KEY,
      get: getSysPrefs,
      set: setSysPrefs,
      remove: removeSysPrefs,
    },
  };
})();
