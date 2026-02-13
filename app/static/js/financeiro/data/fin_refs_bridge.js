// app/static/js/financeiro/data/fin_refs_bridge.js
// Ponte: Ferramentas (tools_ns_) -> FinStore refs (fin_ref_*)
(function () {
  const FS = window.FinStore;
  if (!FS || !FS.KEYS) return;

  const STORAGE_PREFIX = "tools_ns_";
  const VERSION = "v1";

  // Mapa: namespace do Ferramentas -> key do FinStore
  const MAP = {
    "financeiro.imoveis": FS.KEYS.imoveis,
    "financeiro.categorias": FS.KEYS.categorias,
    "financeiro.formas": FS.KEYS.formas,

    // se você decidir criar depois no Ferramentas:
    // "financeiro.clientes": FS.KEYS.clientes,
  };

  function storageKey(ns) {
    return `${STORAGE_PREFIX}${ns}_${VERSION}`;
  }

  function safeParse(raw, fb) {
    try { return JSON.parse(raw); } catch (_) { return fb; }
  }

  function normalizeName(s) {
    return String(s ?? "").trim().replace(/\s+/g, " ");
  }

  function exportNsToFinRef(ns) {
    const finKey = MAP[ns];
    if (!finKey) return;

    let list = [];
    try {
      const raw = localStorage.getItem(storageKey(ns));
      const arr = raw ? safeParse(raw, []) : [];
      if (Array.isArray(arr)) list = arr;
    } catch (_) {}

    // refs no FinStore devem ser simples e limpas:
    // aqui eu recomendo guardar SOMENTE ativos, porque select não deve listar desativados
    const onlyActive = list
      .filter((x) => Boolean(x?.active))
      .map((x) => normalizeName(x?.name))
      .filter(Boolean);

    try {
      localStorage.setItem(finKey, JSON.stringify(onlyActive));
    } catch (_) {}

    // dispara evento do store (mesma aba)
    try {
      window.dispatchEvent(new CustomEvent(FS.EVT, { detail: { key: finKey, at: Date.now() } }));
    } catch (_) {}
  }

  // API pública para Ferramentas chamar após salvar/excluir/toggle
  window.FinRefsBridge = {
    exportNsToFinRef,
    exportAll: () => Object.keys(MAP).forEach(exportNsToFinRef),
  };
})();