// app/static/js/financeiro/data/fin_refs_bridge.js
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
  };

  function storageKey(ns) {
    return `${STORAGE_PREFIX}${ns}_${VERSION}`;
  }

  function normalizeName(s) {
    return String(s ?? "").trim().replace(/\s+/g, " ");
  }

  function isActive(item) {
    return Boolean(item?.active ?? item?.ativo);
  }

  async function exportNsToFinRef(ns) {
    const finKey = MAP[ns];
    if (!finKey) return;

    const arr = await window.SysStore.get(storageKey(ns)).catch(() => []);
    const list = Array.isArray(arr) ? arr : [];

    const onlyActive = list
      .filter((x) => isActive(x))
      .map((x) => normalizeName(x?.name))
      .filter(Boolean);

    await window.SysStore.set(finKey, onlyActive);
  }

  async function exportAll() {
    await Promise.all(Object.keys(MAP).map(exportNsToFinRef));
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
    exportNsToFinRef(ns).catch(() => {});
  });

  window.FinRefsBridge = {
    exportNsToFinRef,
    exportAll,
  };
})();
