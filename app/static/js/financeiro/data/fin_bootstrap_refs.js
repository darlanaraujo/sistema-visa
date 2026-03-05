// app/static/js/financeiro/data/fin_bootstrap_refs.js
// Bootstrap idempotente das tabelas de referência do Financeiro.
// Objetivo: garantir que selects (imóveis/categorias/formas/clientes) tenham base mínima
// sem depender de mocks hardcoded em cada página.
// NÃO altera layout. Apenas prepara dados.

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

  function storeGetArr(key) {
    if (window.SysStore && typeof window.SysStore.get === "function") {
      const v = window.SysStore.get(key);
      return Array.isArray(v) ? v : [];
    }
    return [];
  }

  function storeSetArr(key, arr) {
    if (window.SysStore && typeof window.SysStore.set === "function") {
      window.SysStore.set(key, arr);
      return true;
    }
    return false;
  }

  function normalizeStr(s) {
    return String(s ?? "")
      .trim()
      .replace(/\s+/g, " ");
  }

  function idFor(prefix) {
    return `${prefix}-${now()}-${Math.floor(Math.random() * 1000)}`;
  }

  function makeRef(prefix, nome) {
    const t = now();
    return {
      id: idFor(prefix),
      nome: normalizeStr(nome),
      ativo: true,
      createdAt: t,
      updatedAt: t,
    };
  }

  function ensureStore(key, defaults) {
    const cur = storeGetArr(key);

    // Se já existe e tem conteúdo, NÃO mexe.
    if (Array.isArray(cur) && cur.length) return cur;

    const fresh = (defaults || []).map((d) => ({
      id: d.id || makeRef(d.prefix || "REF", d.nome).id,
      nome: normalizeStr(d.nome),
      ativo: d.ativo !== false,
      createdAt: d.createdAt || now(),
      updatedAt: d.updatedAt || now(),
    }));

    storeSetArr(key, fresh);
    return fresh;
  }

  // Defaults mínimos (podem ser ampliados depois no módulo Configurações/Clientes)
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

  // API pública para ser usada pelos próximos arquivos (adapter/data-layer)
  function ensureAll() {
    const imoveis = ensureStore(KEYS.imoveis, DEFAULTS.imoveis);
    const categorias = ensureStore(KEYS.categorias, DEFAULTS.categorias);
    const formas = ensureStore(KEYS.formas, DEFAULTS.formas);
    const clientes = ensureStore(KEYS.clientes, DEFAULTS.clientes);
    return { imoveis, categorias, formas, clientes };
  }

  // helpers úteis p/ migração (Etapa 3)
  function findByNome(key, nome) {
    const n = normalizeStr(nome).toLowerCase();
    if (!n) return null;
    const arr = storeGetArr(key);
    return arr.find((x) => normalizeStr(x?.nome).toLowerCase() === n) || null;
  }

  function findOrCreateByNome(key, prefix, nome) {
    const existing = findByNome(key, nome);
    if (existing) return existing;

    const arr = storeGetArr(key);
    const item = makeRef(prefix, nome);
    arr.push(item);
    storeSetArr(key, arr);
    return item;
  }

  // expõe no window (não conflita com nada existente)
  window.FinRefs = window.FinRefs || {};
  window.FinRefs.KEYS = KEYS;
  window.FinRefs.ensureAll = ensureAll;
  window.FinRefs.getAll = (kind) => storeGetArr(KEYS[kind] || "");
  window.FinRefs.findByNome = (kind, nome) => findByNome(KEYS[kind] || "", nome);
  window.FinRefs.findOrCreateByNome = (kind, nome) => {
    if (kind === "imoveis") return findOrCreateByNome(KEYS.imoveis, "IMV", nome);
    if (kind === "categorias") return findOrCreateByNome(KEYS.categorias, "CAT", nome);
    if (kind === "formas") return findOrCreateByNome(KEYS.formas, "FOR", nome);
    if (kind === "clientes") return findOrCreateByNome(KEYS.clientes, "CLI", nome);
    return null;
  };

  // roda bootstrap imediatamente (sem depender de DOM)
  ensureAll();
})();
