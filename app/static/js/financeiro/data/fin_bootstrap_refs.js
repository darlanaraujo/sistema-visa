// app/static/js/financeiro/data/fin_bootstrap_refs.js
(function () {
  const KEYS = {
    imoveis: "fin_ref_imoveis_v1",
    categorias: "fin_ref_categorias_v1",
    formas: "fin_ref_formas_v1",
    clientes: "fin_ref_clientes_v1",
  };

  function now() {
    return Date.now();
  }

  async function storeGetArr(key) {
    if (window.SysStore && typeof window.SysStore.get === "function") {
      const v = await window.SysStore.get(key).catch(() => []);
      return Array.isArray(v) ? v : [];
    }
    return [];
  }

  async function storeSetArr(key, arr) {
    if (window.SysStore && typeof window.SysStore.set === "function") {
      return await window.SysStore.set(key, arr);
    }
    return false;
  }

  function normalizeStr(s) {
    return String(s ?? "").trim().replace(/\s+/g, " ");
  }

  function idFor(prefix) {
    return `${prefix}-${now()}-${Math.floor(Math.random() * 1000)}`;
  }

  const DEFAULTS = {
    imoveis: [
      { prefix: "IMV", nome: "Galpão A" },
      { prefix: "IMV", nome: "Galpão B" },
      { prefix: "IMV", nome: "Escritório" },
    ],
    categorias: [
      { prefix: "CAT", nome: "Aluguel" },
      { prefix: "CAT", nome: "Energia" },
      { prefix: "CAT", nome: "Serviços" },
      { prefix: "CAT", nome: "Operacional" },
      { prefix: "CAT", nome: "Fixas" },
      { prefix: "CAT", nome: "Variáveis" },
    ],
    formas: [
      { prefix: "FOR", nome: "PIX" },
      { prefix: "FOR", nome: "Boleto" },
      { prefix: "FOR", nome: "Depósito" },
      { prefix: "FOR", nome: "Dinheiro" },
      { prefix: "FOR", nome: "Cartão" },
    ],
    clientes: [
      { prefix: "CLI", nome: "Cliente X" },
      { prefix: "CLI", nome: "Cliente Y" },
      { prefix: "CLI", nome: "Parceiro Z" },
    ],
  };

  async function ensureStore(key, defaults) {
    const cur = await storeGetArr(key);
    if (Array.isArray(cur) && cur.length) return cur;

    const fresh = (defaults || []).map((d) => ({
      id: d.id || idFor(d.prefix || "REF"),
      nome: normalizeStr(d.nome),
      ativo: d.ativo !== false,
      createdAt: d.createdAt || now(),
      updatedAt: d.updatedAt || now(),
    }));

    await storeSetArr(key, fresh);
    return fresh;
  }

  async function ensureAll() {
    return {
      imoveis: await ensureStore(KEYS.imoveis, DEFAULTS.imoveis),
      categorias: await ensureStore(KEYS.categorias, DEFAULTS.categorias),
      formas: await ensureStore(KEYS.formas, DEFAULTS.formas),
      clientes: await ensureStore(KEYS.clientes, DEFAULTS.clientes),
    };
  }

  async function findByNome(key, nome) {
    const n = normalizeStr(nome).toLowerCase();
    if (!n) return null;
    const arr = await storeGetArr(key);
    return arr.find((x) => normalizeStr(x?.nome).toLowerCase() === n) || null;
  }

  async function findOrCreateByNome(key, prefix, nome) {
    const existing = await findByNome(key, nome);
    if (existing) return existing;
    const arr = await storeGetArr(key);
    const item = {
      id: idFor(prefix),
      nome: normalizeStr(nome),
      ativo: true,
      createdAt: now(),
      updatedAt: now(),
    };
    arr.push(item);
    await storeSetArr(key, arr);
    return item;
  }

  const api = window.FinRefs || {};
  api.KEYS = KEYS;
  api.ensureAll = ensureAll;
  api.getAll = function (kind) { return storeGetArr(KEYS[kind] || ""); };
  api.findByNome = function (kind, nome) { return findByNome(KEYS[kind] || "", nome); };
  api.findOrCreateByNome = function (kind, nome) {
    if (kind === "imoveis") return findOrCreateByNome(KEYS.imoveis, "IMV", nome);
    if (kind === "categorias") return findOrCreateByNome(KEYS.categorias, "CAT", nome);
    if (kind === "formas") return findOrCreateByNome(KEYS.formas, "FOR", nome);
    if (kind === "clientes") return findOrCreateByNome(KEYS.clientes, "CLI", nome);
    return Promise.resolve(null);
  };

  window.FinRefs = api;
})();
