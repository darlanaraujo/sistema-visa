// app/static/js/system/sys_bootstrap_ui.js
// Bootstrap visual de primeiro paint para páginas privadas.
// Exceção controlada de governança:
// - Este arquivo roda no <head> ANTES do restante para evitar flash.
// - Por isso lê localStorage diretamente apenas para estado mínimo de paint.
// - Persistência oficial continua no SysStore/BaseStore.

(function () {
  var BOOTSTRAP_CACHE_KEY = "sys_ui_bootstrap_cache_v1";

  function lsGetRaw(key) {
    // Leitura mínima pré-paint (não escrever aqui).
    try { return localStorage.getItem(key); } catch (_) { return null; }
  }

  function lsGet(key) {
    var raw = lsGetRaw(key);
    if (raw == null) return null;
    try { return JSON.parse(raw); } catch (_) { return raw; }
  }

  function isHex(v) {
    return /^#[0-9a-f]{6}$/i.test(String(v || "").trim());
  }

  function isMobileViewport() {
    try {
      return Boolean(window.matchMedia && window.matchMedia("(max-width: 980px)").matches);
    } catch (_) {
      return false;
    }
  }

  function resolveMode(modeRaw) {
    var mode = String(modeRaw || "light").toLowerCase().trim();
    if (mode === "dark" || mode === "light") return mode;
    return "light";
  }

  function ensureFaviconLink() {
    var link =
      document.querySelector('link[rel="icon"]') ||
      document.querySelector('link[rel="shortcut icon"]');

    if (!link) {
      link = document.createElement("link");
      link.setAttribute("rel", "icon");
      document.head.appendChild(link);
    }
    return link;
  }

  function readCache() {
    var cached = lsGet(BOOTSTRAP_CACHE_KEY);
    return cached && typeof cached === "object" ? cached : {};
  }

  function writeCache(next) {
    try {
      localStorage.setItem(BOOTSTRAP_CACHE_KEY, JSON.stringify(next || {}));
    } catch (_) {}
  }

  function mergeCache(patch) {
    var current = readCache();
    var next = Object.assign({}, current, patch || {});
    writeCache(next);
    return next;
  }

  function readState() {
    var prev = window.__SYS_BOOTSTRAP__;
    var cached = readCache();
    var cachedPrefs = (cached && cached.prefs && typeof cached.prefs === "object") ? cached.prefs : null;
    var prevPrefs = (prev && prev.prefs && typeof prev.prefs === "object") ? prev.prefs : null;

    // Cache estritamente técnico de first paint. Fonte de verdade continua fora deste arquivo.
    var prefs = cachedPrefs || prevPrefs;
    if (!prefs || typeof prefs !== "object") prefs = {};

    var theme = prefs.theme || {};
    var brand = prefs.brand || {};

    var primary = String(theme.primary || "").trim();
    var accent = String(theme.accent || "").trim();
    var accentPreset = String(
      theme.accentPreset ||
      theme.accent_preset ||
      prefs.accentPreset ||
      prefs.accent_preset ||
      ""
    ).trim().toLowerCase();
    var danger = String(theme.danger || "").trim();
    var success = String(theme.success || "").trim();

    var accentToUse = isHex(accent) ? accent : (isHex(primary) ? primary : "");
    var sidebarRaw = (cached && typeof cached.sidebarCollapsedSaved !== "undefined")
      ? (cached.sidebarCollapsedSaved ? 1 : 0)
      : ((prev && typeof prev.sidebarCollapsedSaved !== "undefined")
        ? (prev.sidebarCollapsedSaved ? 1 : 0)
        : 0);
    var sidebarCollapsedSaved =
      sidebarRaw === true ||
      sidebarRaw === 1 ||
      String(sidebarRaw || "") === "1";
    var isMobile = isMobileViewport();

    return {
      prefs: prefs,
      themeMode: resolveMode(theme.mode),
      brandLogo: String(brand.logoDataUrl || "").trim(),
      brandFav: String(brand.faviconDataUrl || "").trim(),
      accentToUse: accentToUse,
      accentPresetToUse: accentPreset,
      danger: isHex(danger) ? danger : "",
      success: isHex(success) ? success : "",
      isMobile: isMobile,
      sidebarCollapsedSaved: sidebarCollapsedSaved,
      sidebarShouldCollapse: (!isMobile && sidebarCollapsedSaved),
    };
  }

  function applyRootEarly(state) {
    var root = document.documentElement;
    if (!root) return;

    root.classList.toggle("sv-sidebar-collapsed", Boolean(state.sidebarShouldCollapse));
    root.setAttribute("data-theme", state.themeMode);
    root.classList.toggle("theme-dark", state.themeMode === "dark");
    root.classList.toggle("theme-light", state.themeMode !== "dark");

    if (state.accentToUse) {
      root.removeAttribute("data-accent");
      root.style.setProperty("--c-accent", state.accentToUse);
      root.style.setProperty("--fin-blue", state.accentToUse);
      root.style.setProperty("--accent", state.accentToUse);
    } else if (state.accentPresetToUse) {
      root.setAttribute("data-accent", state.accentPresetToUse);
      root.style.removeProperty("--c-accent");
      root.style.removeProperty("--fin-blue");
      root.style.removeProperty("--accent");
    } else {
      root.removeAttribute("data-accent");
      root.style.removeProperty("--c-accent");
      root.style.removeProperty("--fin-blue");
      root.style.removeProperty("--accent");
    }
    if (state.danger) root.style.setProperty("--c-danger", state.danger);
    if (state.success) root.style.setProperty("--c-success", state.success);

    if (state.brandFav) {
      try {
        ensureFaviconLink().setAttribute("href", state.brandFav);
      } catch (_) {}
    }
  }

  function applySidebarElState(state) {
    var sidebar = document.getElementById("sidebar");
    if (!sidebar) return false;

    if (state.sidebarShouldCollapse) sidebar.classList.add("is-collapsed");
    else sidebar.classList.remove("is-collapsed");
    return true;
  }

  function applySidebarLogoState(state) {
    // Etapa 6: pode não existir #sidebarLogo (usuário ocupa a área da marca na sidebar).
    // Fallback para #topbarLogo garante branding aplicado sem quebrar bootstrap.
    var img = document.getElementById("sidebarLogo") || document.getElementById("topbarLogo");
    if (!img) return false;

    var logo =
      state.brandLogo ||
      img.getAttribute("data-logo") ||
      img.getAttribute("data-logo-default") ||
      img.getAttribute("src") ||
      "";

    var fav =
      state.brandFav ||
      img.getAttribute("data-favicon") ||
      img.getAttribute("data-favicon-default") ||
      "";

    try { img.dataset.logo = logo || ""; } catch (_) {}
    try { img.dataset.favicon = fav || ""; } catch (_) {}

    var isTopbarFallback = img.id === "topbarLogo";

    if (isTopbarFallback && state.isMobile) {
      if (fav) img.setAttribute("src", fav);
      else if (logo) img.setAttribute("src", logo);
    } else if (state.sidebarShouldCollapse && fav) img.setAttribute("src", fav);
    else if (logo) img.setAttribute("src", logo);

    return true;
  }

  function applyEdgeIconState(state) {
    var icon = document.getElementById("edgeToggleIcon");
    if (!icon) return false;

    icon.classList.remove("fa-chevron-left", "fa-chevron-right");
    icon.classList.add(state.sidebarShouldCollapse ? "fa-chevron-right" : "fa-chevron-left");
    return true;
  }

  function applyAllPresent(state) {
    applySidebarElState(state);
    applySidebarLogoState(state);
    applyEdgeIconState(state);
  }

  function installObserver(state) {
    if (!("MutationObserver" in window)) return null;

    var obs = new MutationObserver(function () {
      applyAllPresent(state);
      var hasSidebar = Boolean(document.getElementById("sidebar"));
      var hasLogo = Boolean(document.getElementById("sidebarLogo")) || Boolean(document.getElementById("topbarLogo"));
      var hasEdgeIcon = Boolean(document.getElementById("edgeToggleIcon"));
      if (hasSidebar && hasLogo && hasEdgeIcon) obs.disconnect();
    });

    try {
      obs.observe(document.documentElement, { childList: true, subtree: true });
    } catch (_) {
      return null;
    }
    return obs;
  }

  function refresh() {
    var state = readState();
    window.__SYS_BOOTSTRAP__ = state;
    applyRootEarly(state);
    applyAllPresent(state);
    return state;
  }

  var state = refresh();
  var obs = installObserver(state);

  window.SysUIBootstrap = {
    refresh: refresh,
    applyRootEarly: function () { applyRootEarly(window.__SYS_BOOTSTRAP__ || state); },
    applyAllPresent: function () { applyAllPresent(window.__SYS_BOOTSTRAP__ || state); },
    syncPrefsCache: function (prefs) {
      if (!prefs || typeof prefs !== "object") return readCache();
      return mergeCache({ prefs: prefs });
    },
    syncSidebarCache: function (collapsed) {
      return mergeCache({ sidebarCollapsedSaved: Boolean(collapsed) });
    },
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      refresh();
      if (obs) obs.disconnect();
    }, { once: true });
  }
})();
