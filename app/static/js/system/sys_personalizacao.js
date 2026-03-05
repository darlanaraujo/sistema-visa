// app/static/js/system/sys_personalizacao.js
// Fonte da verdade: BaseStore.prefs (camada data -> SysStore)
// Em páginas sem BaseStore (ex.: públicas/login), aplica apenas defaults.
// Aplica: tema, cores (variáveis), logo e favicon (inclui <img> favicon em relatórios/prints)

(function () {
  function getPrefs() {
    // Caminho oficial: BaseStore (UI não toca persistência diretamente).
    try {
      if (window.BaseStore?.prefs && typeof window.BaseStore.prefs.get === "function") {
        const prefs = window.BaseStore.prefs.get();
        return prefs && typeof prefs === "object" ? prefs : null;
      }
    } catch (_) {}
    // Público/login: usa estado já bootstrapado sem tocar persistência aqui.
    try {
      const prefs = window.__SYS_BOOTSTRAP__?.prefs;
      return prefs && typeof prefs === "object" ? prefs : null;
    } catch (_) {}
    return null;
  }

  function isHexColor(v) {
    return /^#[0-9a-f]{6}$/i.test(String(v || "").trim());
  }

  function normalizeMode(v) {
    const s = String(v || "").toLowerCase().trim();
    if (s === "dark") return "dark";
    if (s === "light") return "light";
    return "auto";
  }

  function resolveAutoMode() {
    try {
      return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
    } catch (_) {
      return "light";
    }
  }

  // ---------------------------------------------------------
  // Base: sidebar logo element
  // ---------------------------------------------------------
  function getSidebarLogoEl() {
    return document.getElementById("sidebarLogo") || document.querySelector('img[data-brand="logo"]');
  }

  // Defaults IMUTÁVEIS (preferir data-*-default do HTML)
  function getDefaultLogoUrl() {
    const img = getSidebarLogoEl();
    if (!img) return "";

    const ds = img.dataset || {};
    const def = ds.logoDefault || img.getAttribute("data-logo-default") || "";
    const active = ds.logo || img.getAttribute("data-logo") || "";
    const src = img.getAttribute("src") || "";

    return def || active || src || "";
  }

  // ✅ FIX: default favicon não pode “aprender” do <link rel=icon> se o HTML já define data-favicon
  function getDefaultFaviconUrl() {
    const img = getSidebarLogoEl();
    const ds = img?.dataset || {};

    const def = ds?.faviconDefault || img?.getAttribute("data-favicon-default") || "";
    const active = ds?.favicon || img?.getAttribute("data-favicon") || "";

    // Se o HTML já tem default/active, isso é a verdade.
    if (def || active) return def || active;

    // Só se não houver nada no HTML, aí sim usa o <head>
    const link =
      document.querySelector('link[rel="icon"]') ||
      document.querySelector('link[rel="shortcut icon"]') ||
      null;

    return link?.getAttribute("href") || "";
  }

  function ensureFaviconLink() {
    let link =
      document.querySelector('link[rel="icon"]') ||
      document.querySelector('link[rel="shortcut icon"]');

    if (!link) {
      link = document.createElement("link");
      link.setAttribute("rel", "icon");
      document.head.appendChild(link);
    }
    return link;
  }

  function isSidebarCollapsed() {
    try {
      const sidebar = document.getElementById("sidebar");
      const layout = document.getElementById("privateLayout");
      return Boolean(
        sidebar?.classList?.contains("is-collapsed") ||
        layout?.classList?.contains("sidebar-collapsed")
      );
    } catch (_) {
      return false;
    }
  }

  function isMobileViewport() {
    try {
      return Boolean(window.matchMedia && window.matchMedia("(max-width: 980px)").matches);
    } catch (_) {
      return false;
    }
  }

  // ---------------------------------------------------------
  // APPLY: theme + colors
  // ---------------------------------------------------------
  function applyTheme(mode) {
    const m = normalizeMode(mode);
    const finalMode = (m === "auto") ? resolveAutoMode() : m;

    document.documentElement.setAttribute("data-theme", finalMode);
    document.documentElement.classList.toggle("theme-dark", finalMode === "dark");
    document.documentElement.classList.toggle("theme-light", finalMode !== "dark");
  }

  function applyColorVars(themeObj) {
    if (!themeObj || typeof themeObj !== "object") return;

    // ✅ NOVO: preset do acento (data-accent)
    const preset = String(themeObj.accentPreset || themeObj.accent_preset || "").trim().toLowerCase();

    const primary = String(themeObj.primary || "").trim();
    const accent  = String(themeObj.accent || "").trim();
    const danger  = String(themeObj.danger || "").trim();
    const success = String(themeObj.success || "").trim();

    const hasCustom = isHexColor(accent) || isHexColor(primary);
    const customHex = isHexColor(accent) ? accent : (isHexColor(primary) ? primary : "");

    if (hasCustom && customHex) {
      // custom tem prioridade: remove preset e aplica variável
      document.documentElement.removeAttribute("data-accent");

      document.documentElement.style.setProperty("--c-accent", customHex);
      document.documentElement.style.setProperty("--fin-blue", customHex);
      document.documentElement.style.setProperty("--accent", customHex);
    } else if (preset) {
      // preset: seta data-accent e remove overrides
      document.documentElement.setAttribute("data-accent", preset);

      document.documentElement.style.removeProperty("--c-accent");
      document.documentElement.style.removeProperty("--fin-blue");
      document.documentElement.style.removeProperty("--accent");
    } else {
      // nada: volta ao default do CSS
      document.documentElement.removeAttribute("data-accent");

      document.documentElement.style.removeProperty("--c-accent");
      document.documentElement.style.removeProperty("--fin-blue");
      document.documentElement.style.removeProperty("--accent");
    }

    if (isHexColor(danger)) document.documentElement.style.setProperty("--c-danger", danger);
    else document.documentElement.style.removeProperty("--c-danger");

    if (isHexColor(success)) document.documentElement.style.setProperty("--c-success", success);
    else document.documentElement.style.removeProperty("--c-success");
  }

  // ---------------------------------------------------------
  // APPLY: logo + favicon
  // ---------------------------------------------------------

  // ✅ FIX: NÃO inclui #sidebarLogo aqui. O sidebarLogo é controlado somente pelo estado (colapsado/expandido)
  function applyLogoToTargets(src) {
    if (!src) return;

    const targets = [
      ...document.querySelectorAll(".fin-kpi-logo img"),
      ...document.querySelectorAll('img[data-brand="logo"]'),
      ...document.querySelectorAll("#brandLogo")
    ];

    targets.forEach((img) => {
      try { img.setAttribute("src", src); } catch (_) {}
    });
  }

  // Topbar tem regra própria:
  // - mobile: favicon
  // - desktop: logo
  function applyTopbarBrand(logoSrc, favSrc) {
    const topbarImg = document.getElementById("topbarLogo");
    if (!topbarImg) return;
    const mobile = isMobileViewport();
    const chosen = (mobile && favSrc) ? favSrc : (logoSrc || "");
    if (!chosen) return;
    try { topbarImg.setAttribute("src", chosen); } catch (_) {}
  }

  function applyFaviconToTargets(src) {
    if (!src) return;

    // 1) Aba (link rel=icon)
    try {
      const link = ensureFaviconLink();
      link.setAttribute("href", src);
    } catch (_) {}

    // 2) Imagens de favicon (NUNCA mexer no #sidebarLogo)
    const sidebarLogo = getSidebarLogoEl();

    const defaultFav = getDefaultFaviconUrl();
    const d = String(defaultFav || "").toLowerCase();

    const looksLikeFav = (s0) => {
      const s = String(s0 || "").toLowerCase();
      if (!s) return false;
      return (
        s.endsWith("/favicon.png") ||
        s.includes("/img/favicon") ||
        s.includes("favicon") ||
        s.endsWith(".ico")
      );
    };

    document.querySelectorAll("img").forEach((img) => {
      if (sidebarLogo && img === sidebarLogo) return;

      const isMarked = img.matches('img[data-brand="favicon"]');

      const cur = img.getAttribute("src") || "";
      const curL = String(cur).toLowerCase();
      const isDefaultMatch = d && curL === d;

      if (isMarked || isDefaultMatch || looksLikeFav(cur)) {
        try { img.setAttribute("src", src); } catch (_) {}
      }
    });
  }

  function applyLogoAndFavicon(brandObj) {
    const logoOv = String(brandObj?.logoDataUrl || "").trim();
    const favOv  = String(brandObj?.faviconDataUrl || "").trim();

    const defaultLogo = getDefaultLogoUrl();
    const defaultFav  = getDefaultFaviconUrl();

    const finalLogo = logoOv || defaultLogo || "";
    const finalFav  = favOv  || defaultFav  || "";

    const sidebarImg = getSidebarLogoEl();
    if (sidebarImg) {
      // dashboard.js usa isso para trocar logo<->favicon quando colapsa
      try { sidebarImg.dataset.logo = finalLogo || ""; } catch (_) {}
      try { sidebarImg.dataset.favicon = finalFav || ""; } catch (_) {}

      // ✅ Atualiza topbar logo dataset também
      const topbarImg = document.getElementById("topbarLogo");
      if (topbarImg) {
        try { topbarImg.dataset.logo = finalLogo || ""; } catch (_) {}
        try { topbarImg.dataset.favicon = finalFav || ""; } catch (_) {}
      }

      // src do alvo principal:
      // - Se o alvo for o topbar (fallback da Etapa 6), mobile deve SEMPRE usar favicon.
      // - Se existir sidebarLogo real, mantém regra de colapsado/expandido.
      try {
        const isTopbarFallback = sidebarImg.id === "topbarLogo";
        if (isTopbarFallback && isMobileViewport()) {
          if (finalFav) sidebarImg.setAttribute("src", finalFav);
        } else {
          if (isSidebarCollapsed()) {
            if (finalFav) sidebarImg.setAttribute("src", finalFav);
          } else {
            if (finalLogo) sidebarImg.setAttribute("src", finalLogo);
          }
        }
      } catch (_) {}
    }

    // resto do sistema (logo) — sem mexer no sidebarLogo
    if (finalLogo) applyLogoToTargets(finalLogo);
    applyTopbarBrand(finalLogo, finalFav);

    // favicon (aba + imgs)
    if (finalFav) applyFaviconToTargets(finalFav);
  }

  // ---------------------------------------------------------
  // Identity (opcional)
  // ---------------------------------------------------------
  function applyIdentity(identityObj) {
    const systemName = String(identityObj?.systemName || "").trim();
    const companyName = String(identityObj?.companyName || "").trim();

    if (systemName) {
      try {
        const cur = document.title || "";
        const parts = cur.split("•").map((x) => x.trim()).filter(Boolean);
        const pagePart = parts.length ? parts[0] : cur;
        document.title = pagePart ? `${pagePart} • ${systemName}` : systemName;
      } catch (_) {}
    }

    window.SysPrefs = window.SysPrefs || {};
    window.SysPrefs.identity = { systemName, companyName };
  }

  // ---------------------------------------------------------
  // Apply all
  // ---------------------------------------------------------
  function applyAll() {
    const prefs = getPrefs();

    if (!prefs) {
      // Sem prefs disponíveis, preserva estado já renderizado (server/bootstrap).
      return;
    }

    applyTheme(prefs?.theme?.mode);
    applyColorVars(prefs?.theme);
    applyLogoAndFavicon(prefs?.brand);
    applyIdentity(prefs?.identity);
  }

  function init() {
    applyAll();

    // Evento padrão da camada BaseStore
    window.addEventListener("base:prefs:changed", () => applyAll());
    window.addEventListener("base:changed", (e) => {
      if (e?.detail?.kind === "prefs") applyAll();
    });

    // Compatibilidade com eventos do Financeiro em versões antigas
    const evtName = (window.FinStore && window.FinStore.EVT) ? window.FinStore.EVT : "fin:change";
    window.addEventListener(evtName, (e) => {
      const key = e?.detail?.key || "";
      if (key === "tools:sistema.personalizacao") applyAll();
    });

    window.addEventListener("sidebar:toggle", () => applyAll());
    window.addEventListener("resize", () => applyAll());
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();
