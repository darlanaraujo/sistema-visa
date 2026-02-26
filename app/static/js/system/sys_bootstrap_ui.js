// app/static/js/system/sys_bootstrap_ui.js
// Bootstrap visual de primeiro paint para páginas privadas.

(function () {
  var SYS_PREFS_KEY = "tools_sys_prefs_v2";
  var SIDEBAR_COLLAPSE_KEY = "sv_sidebar_collapsed";

  function lsGet(key) {
    try { return localStorage.getItem(key); } catch (_) { return null; }
  }

  function safeJsonParse(raw, fallback) {
    try {
      var v = JSON.parse(raw);
      return v && typeof v === "object" ? v : fallback;
    } catch (_) {
      return fallback;
    }
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
    var mode = String(modeRaw || "auto").toLowerCase().trim();
    if (mode === "dark" || mode === "light") return mode;
    try {
      return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches
        ? "dark"
        : "light";
    } catch (_) {
      return "light";
    }
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

  function readState() {
    var prefs = safeJsonParse(lsGet(SYS_PREFS_KEY), {}) || {};
    var theme = prefs.theme || {};
    var brand = prefs.brand || {};

    var primary = String(theme.primary || "").trim();
    var accent = String(theme.accent || "").trim();
    var danger = String(theme.danger || "").trim();
    var success = String(theme.success || "").trim();

    var accentToUse = isHex(accent) ? accent : (isHex(primary) ? primary : "");
    var sidebarCollapsedSaved = lsGet(SIDEBAR_COLLAPSE_KEY) === "1";
    var isMobile = isMobileViewport();

    return {
      prefs: prefs,
      themeMode: resolveMode(theme.mode),
      brandLogo: String(brand.logoDataUrl || "").trim(),
      brandFav: String(brand.faviconDataUrl || "").trim(),
      accentToUse: accentToUse,
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
      root.style.setProperty("--c-accent", state.accentToUse);
      root.style.setProperty("--fin-blue", state.accentToUse);
      root.style.setProperty("--accent", state.accentToUse);
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
    var img = document.getElementById("sidebarLogo");
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

    if (state.sidebarShouldCollapse && fav) img.setAttribute("src", fav);
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
      var hasLogo = Boolean(document.getElementById("sidebarLogo"));
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
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      refresh();
      if (obs) obs.disconnect();
    }, { once: true });
  }
})();
