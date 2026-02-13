// app/static/js/system/sys_personalizacao.js
// Fonte da verdade: localStorage["tools_sys_prefs_v2"]
// Aplica: tema, cores (variáveis), logo e favicon (inclui <img> favicon em relatórios/prints)

(function () {
  const SYS_PREFS_KEY = "tools_sys_prefs_v2";

  function lsGet(key) {
    try { return localStorage.getItem(key); } catch (_) { return null; }
  }

  function safeJsonParse(raw, fallback) {
    try {
      const v = JSON.parse(raw);
      return v && typeof v === "object" ? v : fallback;
    } catch (_) {
      return fallback;
    }
  }

  function getPrefs() {
    return safeJsonParse(lsGet(SYS_PREFS_KEY), null) || null;
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

    const primary = String(themeObj.primary || "").trim();
    const accent  = String(themeObj.accent || "").trim();
    const danger  = String(themeObj.danger || "").trim();
    const success = String(themeObj.success || "").trim();

    const accentToUse = isHexColor(accent) ? accent : (isHexColor(primary) ? primary : "");

    if (accentToUse) {
      document.documentElement.style.setProperty("--c-accent", accentToUse);
      document.documentElement.style.setProperty("--fin-blue", accentToUse);
      document.documentElement.style.setProperty("--accent", accentToUse);
    } else {
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
      ...document.querySelectorAll("#brandLogo"),
    ];
    targets.forEach((img) => {
      try { img.setAttribute("src", src); } catch (_) {}
    });
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

      // src do sidebar depende do estado
      try {
        if (isSidebarCollapsed()) {
          if (finalFav) sidebarImg.setAttribute("src", finalFav);
        } else {
          if (finalLogo) sidebarImg.setAttribute("src", finalLogo);
        }
      } catch (_) {}
    }

    // resto do sistema (logo) — sem mexer no sidebarLogo
    if (finalLogo) applyLogoToTargets(finalLogo);

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
      applyTheme("light");
      document.documentElement.style.removeProperty("--c-accent");
      document.documentElement.style.removeProperty("--fin-blue");
      document.documentElement.style.removeProperty("--accent");
      document.documentElement.style.removeProperty("--c-danger");
      document.documentElement.style.removeProperty("--c-success");

      // ✅ re-aplica defaults corretos SEM contaminar favicon pelo head
      const defLogo = getDefaultLogoUrl();
      const defFav  = getDefaultFaviconUrl();

      applyLogoAndFavicon({ logoDataUrl: "", faviconDataUrl: "" }); // garante sidebar state + link/icon
      if (defLogo) applyLogoToTargets(defLogo);
      if (defFav) applyFaviconToTargets(defFav);

      return;
    }

    applyTheme(prefs?.theme?.mode);
    applyColorVars(prefs?.theme);
    applyLogoAndFavicon(prefs?.brand);
    applyIdentity(prefs?.identity);
  }

  function init() {
    applyAll();

    window.addEventListener("storage", (e) => {
      if (e && e.key === SYS_PREFS_KEY) applyAll();
    });

    const evtName = (window.FinStore && window.FinStore.EVT) ? window.FinStore.EVT : "fin:change";
    window.addEventListener(evtName, (e) => {
      const key = e?.detail?.key || "";
      if (key === "tools:sistema.personalizacao") applyAll();
    });

    window.addEventListener("sidebar:toggle", () => applyAll());
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();