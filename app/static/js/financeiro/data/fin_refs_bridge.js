// app/static/js/financeiro/data/fin_refs_bridge.js
// Ponte: Ferramentas (tools_ns_) -> FinStore refs (fin_ref_*)
// Agora via SysStore (não localStorage direto)
(function () {
  if (!window.SysStore) return;

  const FS = window.FinStore;
  if (!FS || !FS.KEYS) return;

  const STORAGE_PREFIX = "tools_ns_";
  const VERSION = "v1";

  const MAP = {
    "financeiro.imoveis": FS.KEYS.imoveis,
    "financeiro.categorias": FS.KEYS.categorias,
    "financeiro.formas": FS.KEYS.formas,
    // "financeiro.clientes": FS.KEYS.clientes,
  };

  function storageKey(ns) {
    return `${STORAGE_PREFIX}${ns}_${VERSION}`;
  }

  function normalizeName(s) {
    return String(s ?? "").trim().replace(/\s+/g, " ");
  }

  function isActive(item) {
    // compat: modelos antigos usam "ativo", novos usam "active"
    return Boolean(item?.active ?? item?.ativo);
  }

  function emitFinChange(key) {
    try {
      window.dispatchEvent(new CustomEvent(FS.EVT, { detail: { key, at: Date.now() } }));
    } catch (_) {}
  }

  function exportNsToFinRef(ns) {
    const finKey = MAP[ns];
    if (!finKey) return;

    // ✅ lê do SysStore (fonte única)
    const arr = SysStore.get(storageKey(ns));
    const list = Array.isArray(arr) ? arr : [];

    const onlyActive = list
      .filter((x) => isActive(x))
      .map((x) => normalizeName(x?.name))
      .filter(Boolean);

    // Evita apagar refs existentes quando Ferramentas ainda não possui dados.
    if (!onlyActive.length) return;

    // ✅ grava no SysStore também (FinStore lê via SysStore)
    SysStore.set(finKey, onlyActive);

    // evento para atualizar selects na mesma aba
    emitFinChange(finKey);
  }

  function exportAll() {
    Object.keys(MAP).forEach(exportNsToFinRef);
  }

  function nsFromStorageKey(rawKey) {
    const key = String(rawKey || "").trim();
    if (!key.startsWith(STORAGE_PREFIX) || !key.endsWith(`_${VERSION}`)) return "";
    return key.slice(STORAGE_PREFIX.length, -(VERSION.length + 1));
  }

  window.addEventListener("sys:changed", (e) => {
    const key = e?.detail?.key || "";
    const ns = nsFromStorageKey(key);
    if (!ns || !MAP[ns]) return;
    exportNsToFinRef(ns);
  });

  window.FinRefsBridge = {
    exportNsToFinRef,
    exportAll,
  };

  // Garante sync no carregamento da página
  exportAll();
})();
