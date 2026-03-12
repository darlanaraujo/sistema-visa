// app/static/js/ferramentas.js
// Domínio "Ferramentas" — NÃO conhece localStorage.
// Usa SysStore como infraestrutura.
// Futuro: SysStore migra para BD sem refazer páginas.

(function () {
  async function waitPrivateAreaBoot() {
    try {
      if (window.__SV_PRIVATE_BOOT__ && typeof window.__SV_PRIVATE_BOOT__.ready === "function") {
        await window.__SV_PRIVATE_BOOT__.ready();
      }
    } catch (_) {}
  }

  function ready(fn) {
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", fn);
    else fn();
  }

  ready(async function () {
    await waitPrivateAreaBoot();
    // ✅ obrigatoriedade: FerStore deve existir
    if (!window.FerStore) {
      console.error("[ferramentas] FerStore não carregado.");
      return;
    }

    // limites para evitar estourar localStorage (no futuro: validação server-side)
    const MAX_IMG_BYTES = 220 * 1024;

    const el = (id) => document.getElementById(id);

    const els = {
      page: el("ftPage"),

      // CRUD list
      modal: el("ftModal"),
      modalTitle: el("ftModalTitle"),
      modalClose: el("ftModalClose"),
      modalHint: el("ftModalHint"),
      tbody: el("ftTbody"),
      empty: el("ftEmpty"),
      newBtn: el("ftNew"),

      // CRUD form
      formModal: el("ftFormModal"),
      formTitle: el("ftFormTitle"),
      formClose: el("ftFormClose"),
      form: el("ftForm"),
      cancel: el("ftCancel"),
      id: el("ftId"),
      name: el("ftName"),
      active: el("ftActive"),
      nsView: el("ftNsView"),

      // delete
      delModal: el("ftDelModal"),
      delClose: el("ftDelClose"),
      delCancel: el("ftDelCancel"),
      delConfirm: el("ftDelConfirm"),

      // System prefs modal
      sysModal: el("ftSysModal"),
      sysClose: el("ftSysClose"),
      sysCancel: el("ftSysCancel"),
      sysReset: el("ftSysReset"),
      sysForm: el("ftSysForm"),

      sysSystemName: el("ftSysSystemName"),
      sysCompanyName: el("ftSysCompanyName"),
      sysCnpj: el("ftSysCnpj"),
      sysRazao: el("ftSysRazao"),
      sysSlogan: el("ftSysSlogan"),
      sysNotes: el("ftSysNotes"),

      sysSite: el("ftSysSite"),
      sysEmail: el("ftSysEmail"),
      sysPhone: el("ftSysPhone"),
      sysWhats: el("ftSysWhats"),

      sysLogoFile: el("ftSysLogoFile"),
      sysLogoPreview: el("ftSysLogoPreview"),
      sysLogoRemove: el("ftSysLogoRemove"),

      sysFaviconFile: el("ftSysFaviconFile"),
      sysFaviconPreview: el("ftSysFaviconPreview"),
      sysFaviconRemove: el("ftSysFaviconRemove"),

      sysCurrency: el("ftSysCurrency"),
      sysTimezone: el("ftSysTimezone"),
      sysCompact: el("ftSysCompact"),

      // Tema (novo formato)
      sysThemeMode: el("ftSysThemeMode"),

      // preset do acento
      sysAccentPreset: el("ftSysAccentPreset"),

      sysColorAccent: el("ftSysColorAccent"),
      sysColorAccentHex: el("ftSysColorAccentHex"),
      sysColorDanger: el("ftSysColorDanger"),
      sysColorDangerHex: el("ftSysColorDangerHex"),
      sysColorSuccess: el("ftSysColorSuccess"),
      sysColorSuccessHex: el("ftSysColorSuccessHex"),
      sysColorsReset: el("ftSysColorsReset"),
    };

    if (!els.page) return;

    // Toast helpers
    function toast(kind, msg) {
      try {
        if (window.Toast && typeof window.Toast[kind] === "function") window.Toast[kind](msg);
        else if (window.Toast && typeof window.Toast.show === "function") window.Toast.show(msg);
      } catch (_) {}
    }
    const tSuccess = (m) => toast("success", m);
    const tDanger = (m) => toast("danger", m);
    const tWarning = (m) => toast("warning", m);
    const tShow = (m) => toast("show", m);

    // -----------------------------
    // util
    // -----------------------------
    function uid() {
      return Date.now() + Math.floor(Math.random() * 1000);
    }

    function escapeHtml(s) {
      return String(s ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    function normalizeSpaces(s) {
      return String(s || "").replace(/\s+/g, " ").trim();
    }

    const LOWER_WORDS = new Set(["de", "da", "do", "das", "dos", "e", "em", "para", "por", "com", "a", "o"]);
    function titleCasePT(s) {
      const raw = normalizeSpaces(s);
      if (!raw) return "";
      const parts = raw.toLowerCase().split(" ");
      return parts
        .map((w, i) => {
          if (!w) return "";
          if (i > 0 && LOWER_WORDS.has(w)) return w;
          return w.charAt(0).toUpperCase() + w.slice(1);
        })
        .join(" ");
    }

    // -----------------------------
    // FerStore bridge (infra)
    // -----------------------------
    async function load(ns) {
      return window.FerStore?.tools?.list ? window.FerStore.tools.list(ns) : [];
    }

    async function save(ns, list) {
      if (window.FerStore?.tools?.save) {
        return window.FerStore.tools.save(ns, list);
      }
      return false;
    }

    function isHexColor(v) {
      return /^#[0-9a-f]{6}$/i.test(String(v || "").trim());
    }

    function ensureHex(v, fallback) {
      const s = String(v || "").trim();
      return isHexColor(s) ? s.toLowerCase() : (fallback || "");
    }

    function normalizeThemeMode(v) {
      const m = String(v || "").toLowerCase();
      return (m === "dark") ? "dark" : "light";
    }

    // -----------------------------
    // DEFAULTS do sistema (fonte: base_private/login)
    // -----------------------------
    function getSidebarLogoEl() {
      return document.getElementById("sidebarLogo") || document.querySelector('img[data-brand="logo"]');
    }

    function getDefaultLogoUrl() {
      const img = getSidebarLogoEl();
      if (!img) return "";
      const ds = img.dataset || {};
      return (
        ds.logoDefault ||
        img.getAttribute("data-logo-default") ||
        ds.logo ||
        img.getAttribute("data-logo") ||
        img.getAttribute("src") ||
        ""
      );
    }

    function getDefaultFaviconUrl() {
      const img = getSidebarLogoEl();
      const ds = img?.dataset || {};

      const fromSidebar =
        ds?.faviconDefault ||
        img?.getAttribute("data-favicon-default") ||
        ds?.favicon ||
        img?.getAttribute("data-favicon") ||
        "";

      const link =
        document.querySelector('link[rel="icon"]') ||
        document.querySelector('link[rel="shortcut icon"]');

      const fromHead = link?.getAttribute("href") || "";
      return fromSidebar || fromHead || "";
    }

    // -----------------------------
    // PERSONALIZAÇÃO (SPECIAL)
    // -----------------------------
    async function loadSysPrefs() {
      return window.FerStore?.prefs?.get ? window.FerStore.prefs.get() : null;
    }

    async function saveSysPrefs(prefs) {
      try {
        if (window.FerStore?.prefs?.set) return await window.FerStore.prefs.set(prefs || {});
      } catch (_) {
        tDanger("Não foi possível salvar. O armazenamento do navegador pode estar cheio.");
      }
      return false;
    }

    async function removeSysPrefs() {
      try {
        if (window.FerStore?.prefs?.remove) return await window.FerStore.prefs.remove();
      } catch (_) {}
      return false;
    }

    function normalizeCnpj(v) {
      return String(v || "").replace(/[^\d]/g, "").slice(0, 14);
    }

    function prettyCnpj(digits) {
      const s = normalizeCnpj(digits);
      if (s.length !== 14) return digits || "";
      return `${s.slice(0,2)}.${s.slice(2,5)}.${s.slice(5,8)}/${s.slice(8,12)}-${s.slice(12,14)}`;
    }

    function readFileAsDataURL(file) {
      return new Promise((resolve, reject) => {
        try {
          const fr = new FileReader();
          fr.onload = () => resolve(String(fr.result || ""));
          fr.onerror = () => reject(new Error("read_failed"));
          fr.readAsDataURL(file);
        } catch (e) {
          reject(e);
        }
      });
    }

    function setImgPreview(imgEl, src) {
      if (!imgEl) return;
      if (src) imgEl.setAttribute("src", src);
      else imgEl.removeAttribute("src");
    }

    // estado temporário dentro do modal
    let sysLogoDataUrl = "";
    let sysFavDataUrl = "";

    const DEFAULT_THEME = {
      accent: "#a42d2d",
      danger: "#a42d2d",
      success: "#2f6b4f",
    };

    const ACCENT_PRESETS = {
      visa: "#a42d2d",
      blue: "#2563eb",
      green: "#16a34a",
      purple: "#7c3aed",
      orange: "#f97316",
      slate: "#334155",
    };

    function syncColorPair(colorEl, hexEl, fallback) {
      if (!colorEl || !hexEl) return;
      const v = ensureHex(colorEl.value, fallback);
      if (v) {
        colorEl.value = v;
        hexEl.value = v;
      }
    }

    function setAccentCustomEnabled(on) {
      const isOn = Boolean(on);
      if (els.sysColorAccent) els.sysColorAccent.disabled = !isOn;
      if (els.sysColorAccentHex) els.sysColorAccentHex.disabled = !isOn;
    }

    function fillSysForm(p) {
      const cur = p || {};

      if (els.sysSystemName) els.sysSystemName.value = cur?.identity?.systemName ?? "";
      if (els.sysCompanyName) els.sysCompanyName.value = cur?.identity?.companyName ?? "";
      if (els.sysCnpj) els.sysCnpj.value = prettyCnpj(cur?.identity?.cnpj ?? "");
      if (els.sysRazao) els.sysRazao.value = cur?.identity?.razao ?? "";
      if (els.sysSlogan) els.sysSlogan.value = cur?.identity?.slogan ?? "";
      if (els.sysNotes) els.sysNotes.value = cur?.identity?.notes ?? "";

      if (els.sysSite) els.sysSite.value = cur?.contact?.site ?? "";
      if (els.sysEmail) els.sysEmail.value = cur?.contact?.email ?? "";
      if (els.sysPhone) els.sysPhone.value = cur?.contact?.phone ?? "";
      if (els.sysWhats) els.sysWhats.value = cur?.contact?.whats ?? "";

      if (els.sysCurrency) els.sysCurrency.value = cur?.prefs?.currency ?? "BRL";
      if (els.sysTimezone) els.sysTimezone.value = cur?.prefs?.timezone ?? "America/Sao_Paulo";
      if (els.sysCompact) els.sysCompact.value = String(Number(Boolean(cur?.prefs?.compactTables ?? false)));

      const mode = normalizeThemeMode(cur?.theme?.mode || "light");
      if (els.sysThemeMode) els.sysThemeMode.value = mode;

      const savedAccentPreset = String(cur?.theme?.accentPreset || "").trim().toLowerCase();
      const savedCustomAccent = String(cur?.theme?.accent || "").trim();
      const hasCustomAccent = isHexColor(savedCustomAccent);

      const safePreset = ACCENT_PRESETS[savedAccentPreset] ? savedAccentPreset : "visa";

      if (els.sysAccentPreset) {
        els.sysAccentPreset.value = hasCustomAccent ? "custom" : safePreset;
      }

      const accentPreview = hasCustomAccent
        ? ensureHex(savedCustomAccent, DEFAULT_THEME.accent)
        : (ACCENT_PRESETS[safePreset] || DEFAULT_THEME.accent);

      if (els.sysColorAccent) els.sysColorAccent.value = accentPreview;
      if (els.sysColorAccentHex) els.sysColorAccentHex.value = accentPreview;

      setAccentCustomEnabled(hasCustomAccent);

      const danger = ensureHex(cur?.theme?.danger, DEFAULT_THEME.danger);
      const success = ensureHex(cur?.theme?.success, DEFAULT_THEME.success);

      if (els.sysColorDanger) els.sysColorDanger.value = danger;
      if (els.sysColorDangerHex) els.sysColorDangerHex.value = danger;

      if (els.sysColorSuccess) els.sysColorSuccess.value = success;
      if (els.sysColorSuccessHex) els.sysColorSuccessHex.value = success;

      sysLogoDataUrl = cur?.brand?.logoDataUrl ?? "";
      sysFavDataUrl  = cur?.brand?.faviconDataUrl ?? "";

      const dLogo = getDefaultLogoUrl();
      const dFav  = getDefaultFaviconUrl();

      setImgPreview(els.sysLogoPreview, sysLogoDataUrl || dLogo);
      setImgPreview(els.sysFaviconPreview, sysFavDataUrl || dFav);

      if (els.sysLogoFile) els.sysLogoFile.value = "";
      if (els.sysFaviconFile) els.sysFaviconFile.value = "";
    }

    async function openSysModal() {
      if (!els.sysModal) return;
      const cur = await loadSysPrefs() || {};
      fillSysForm(cur);

      openModalAnimated(els.sysModal);

      setTimeout(() => {
        try { els.sysSystemName?.focus(); } catch (_) {}
      }, 50);
    }

    function closeSysModal() {
      if (!els.sysModal) return;
      closeModalAnimated(els.sysModal);
    }

    async function onPickImage(file, kind) {
      if (!file) return;
      if (file.size > MAX_IMG_BYTES) {
        tDanger(`Arquivo muito grande (${Math.round(file.size/1024)} KB). Use no máximo ~${Math.round(MAX_IMG_BYTES/1024)} KB.`);
        return;
      }
      try {
        const dataUrl = await readFileAsDataURL(file);
        if (!dataUrl.startsWith("data:image/")) {
          tDanger("Formato inválido de imagem.");
          return;
        }

        if (kind === "logo") {
          sysLogoDataUrl = dataUrl;
          setImgPreview(els.sysLogoPreview, sysLogoDataUrl);
          tSuccess("Logo carregada (ainda não salva).");
        } else {
          sysFavDataUrl = dataUrl;
          setImgPreview(els.sysFaviconPreview, sysFavDataUrl);
          tSuccess("Favicon carregado (ainda não salva).");
        }
      } catch (_) {
        tDanger("Falha ao ler a imagem.");
      }
    }

    // -----------------------------
    // CRUD list state
    // -----------------------------
    let currentNs = "";
    let currentTitle = "";
    let items = [];
    let pendingDeleteId = null;
    const MODAL_CLOSE_MS = 360;

    // Abre modal com frame de entrada para garantir transição perceptível.
    function openModalAnimated(modalEl) {
      if (!modalEl) return;
      modalEl.classList.remove("is-closing");
      modalEl.classList.add("is-open");
      modalEl.setAttribute("aria-hidden", "false");
      requestAnimationFrame(() => requestAnimationFrame(() => {
        modalEl.classList.remove("is-closing");
      }));
    }

    // Fecha modal com transição de saída antes de remover o estado aberto.
    function closeModalAnimated(modalEl) {
      if (!modalEl) return;
      if (!modalEl.classList.contains("is-open")) {
        modalEl.setAttribute("aria-hidden", "true");
        return;
      }
      modalEl.classList.add("is-closing");
      window.setTimeout(() => {
        modalEl.classList.remove("is-open", "is-closing");
        modalEl.setAttribute("aria-hidden", "true");
      }, MODAL_CLOSE_MS);
    }

    function sortItems(list) {
      return (Array.isArray(list) ? list : []).slice().sort((a, b) => {
        const aa = String(a?.name || "").toLowerCase();
        const bb = String(b?.name || "").toLowerCase();
        return aa.localeCompare(bb, "pt-BR", { sensitivity: "base" });
      });
    }

    function openModal() {
      if (!els.modal) return;
      openModalAnimated(els.modal);
    }

    function closeModal() {
      if (!els.modal) return;
      closeModalAnimated(els.modal);
    }

    function openFormModal(mode, item) {
      if (!els.formModal) return;

      const isEdit = mode === "edit";
      if (els.formTitle) els.formTitle.textContent = isEdit ? "Editar item" : "Novo item";

      if (els.id) els.id.value = item?.id ?? "";
      if (els.name) els.name.value = item?.name ?? "";
      if (els.active) els.active.value = String(Number(Boolean(item?.active ?? true)));
      if (els.nsView) els.nsView.value = currentNs;

      openModalAnimated(els.formModal);

      setTimeout(() => {
        try { els.name?.focus(); } catch (_) {}
      }, 50);
    }

    function closeFormModal() {
      if (!els.formModal) return;
      closeModalAnimated(els.formModal);
    }

    function openDelModal(id) {
      pendingDeleteId = id;
      if (!els.delModal) return;
      openModalAnimated(els.delModal);
    }

    function closeDelModal() {
      pendingDeleteId = null;
      if (!els.delModal) return;
      closeModalAnimated(els.delModal);
    }

    function render() {
      items = sortItems(items);

      if (els.empty) els.empty.style.display = items.length ? "none" : "block";
      if (!els.tbody) return;

      els.tbody.innerHTML = items
        .map((it) => {
          const activeBadge = it.active
            ? `<span class="fin-badge fin-badge--pt">Sim</span>`
            : `<span class="fin-badge fin-badge--pt" style="opacity:.65;">Não</span>`;

          return `
            <tr data-id="${escapeHtml(it.id)}">
              <td class="t-left">${escapeHtml(it.name)}</td>
              <td class="t-center">${activeBadge}</td>
              <td class="t-center">
                <div class="fin-actions-row">
                  <button class="fin-action-ico" data-act="edit" data-tip="Editar" type="button">
                    <i class="fa-solid fa-pen"></i>
                  </button>
                  <button class="fin-action-ico" data-act="toggle" data-tip="Ativar/Desativar" type="button">
                    <i class="fa-solid fa-power-off"></i>
                  </button>
                  <button class="fin-action-ico" data-act="del" data-tip="Excluir" type="button">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        })
        .join("");
    }

    async function upsert(item) {
      const idx = items.findIndex((x) => String(x.id) === String(item.id));
      if (idx >= 0) items[idx] = item;
      else items.unshift(item);

      return save(currentNs, items);
    }

    async function removeById(id) {
      items = items.filter((x) => String(x.id) !== String(id));
      return save(currentNs, items);
    }

    // -----------------------------
    // abrir por card
    // -----------------------------
    document.querySelectorAll("[data-ft-open]").forEach((btn) => {
      btn.addEventListener("click", async () => {
        currentNs = btn.getAttribute("data-ft-open") || "";
        currentTitle = btn.getAttribute("data-ft-title") || "Cadastro";

        if (currentNs === "sistema.personalizacao") {
          await openSysModal();
          return;
        }

        items = await load(currentNs);

        if (els.modalTitle) els.modalTitle.textContent = currentTitle;
        if (els.modalHint) els.modalHint.textContent = `Namespace: ${currentNs} • Armazenamento: SysStore`;

        render();
        openModal();
      });
    });

    // modal list
    els.modalClose?.addEventListener("click", closeModal);
    els.newBtn?.addEventListener("click", () => openFormModal("new", null));

    // tabela actions
    els.tbody?.addEventListener("click", async (e) => {
      const btn = e.target.closest("button[data-act]");
      if (!btn) return;

      const tr = e.target.closest("tr[data-id]");
      if (!tr) return;

      const id = tr.getAttribute("data-id");
      const act = btn.getAttribute("data-act");
      const item = items.find((x) => String(x.id) === String(id));
      if (!item) return;

      if (act === "edit") {
        openFormModal("edit", item);
        return;
      }

      if (act === "toggle") {
        item.active = !item.active;
        await upsert(item);
        render();
        item.active ? tSuccess("Item ativado.") : tWarning("Item desativado.");
        return;
      }

      if (act === "del") {
        openDelModal(id);
        return;
      }
    });

    // form modal
    els.formClose?.addEventListener("click", closeFormModal);
    els.cancel?.addEventListener("click", closeFormModal);

    els.form?.addEventListener("submit", async (e) => {
      try { e.preventDefault(); } catch (_) {}

      const isEdit = Boolean(els.id?.value);
      const id = isEdit ? String(els.id.value) : String(uid());

      const nameRaw = titleCasePT(els.name?.value || "");
      const active = (els.active?.value || "1") === "1";

      if (!nameRaw) {
        tDanger("Informe um nome.");
        try { els.name?.focus(); } catch (_) {}
        return;
      }

      const nameKey = nameRaw.toLowerCase();
      const dup = items.some((x) => String(x.id) !== id && String(x.name || "").toLowerCase() === nameKey);
      if (dup) {
        tDanger("Já existe um item com este nome neste cadastro.");
        return;
      }

      const payload = {
        id,
        name: nameRaw,
        active,
        createdAt: isEdit ? (items.find((x) => String(x.id) === id)?.createdAt || Date.now()) : Date.now(),
        updatedAt: Date.now(),
      };

      const ok = await upsert(payload);
      if (!ok) {
        tDanger("Não foi possível salvar o item.");
        return;
      }
      render();
      closeFormModal();

      tSuccess(isEdit ? "Alterações salvas." : "Item cadastrado.");
    });

    // delete modal
    els.delClose?.addEventListener("click", closeDelModal);
    els.delCancel?.addEventListener("click", closeDelModal);
    els.delConfirm?.addEventListener("click", async () => {
      if (!pendingDeleteId) return;
      const ok = await removeById(pendingDeleteId);
      if (!ok) {
        tDanger("Não foi possível excluir o item.");
        return;
      }
      closeDelModal();
      render();
      tSuccess("Item excluído.");
    });

    // -----------------------------
    // PERSONALIZAÇÃO events
    // -----------------------------
    els.sysClose?.addEventListener("click", closeSysModal);
    els.sysCancel?.addEventListener("click", closeSysModal);

    els.sysCnpj?.addEventListener("blur", () => {
      els.sysCnpj.value = prettyCnpj(els.sysCnpj.value);
    });

    function bindColorPair(colorEl, hexEl, fallback) {
      if (!colorEl || !hexEl) return;

      syncColorPair(colorEl, hexEl, fallback);

      colorEl.addEventListener("input", () => {
        const v = ensureHex(colorEl.value, fallback);
        if (v) hexEl.value = v;
      });

      hexEl.addEventListener("input", () => {
        const v = ensureHex(hexEl.value, "");
        if (v) colorEl.value = v;

        if (hexEl === els.sysColorAccentHex && els.sysAccentPreset && isHexColor(v)) {
          els.sysAccentPreset.value = "custom";
          setAccentCustomEnabled(true);
        }
      });
    }

    bindColorPair(els.sysColorAccent, els.sysColorAccentHex, DEFAULT_THEME.accent);
    bindColorPair(els.sysColorDanger, els.sysColorDangerHex, DEFAULT_THEME.danger);
    bindColorPair(els.sysColorSuccess, els.sysColorSuccessHex, DEFAULT_THEME.success);

    els.sysAccentPreset?.addEventListener("change", () => {
      const v = String(els.sysAccentPreset.value || "visa").toLowerCase();
      const isCustom = v === "custom";

      setAccentCustomEnabled(isCustom);

      if (!isCustom) {
        const hex = ACCENT_PRESETS[v] || ACCENT_PRESETS.visa || DEFAULT_THEME.accent;
        if (els.sysColorAccent) els.sysColorAccent.value = hex;
        if (els.sysColorAccentHex) els.sysColorAccentHex.value = hex;
      } else {
        const cur = ensureHex(els.sysColorAccentHex?.value || els.sysColorAccent?.value, DEFAULT_THEME.accent);
        if (els.sysColorAccent) els.sysColorAccent.value = cur;
        if (els.sysColorAccentHex) els.sysColorAccentHex.value = cur;
      }
    });

    // uploads
    els.sysLogoFile?.addEventListener("change", async () => {
      const f = els.sysLogoFile?.files?.[0];
      await onPickImage(f, "logo");
    });

    els.sysFaviconFile?.addEventListener("change", async () => {
      const f = els.sysFaviconFile?.files?.[0];
      await onPickImage(f, "fav");
    });

    // reset imagens
    els.sysLogoRemove?.addEventListener("click", () => {
      sysLogoDataUrl = "";
      const dLogo = getDefaultLogoUrl();
      setImgPreview(els.sysLogoPreview, dLogo);
      if (els.sysLogoFile) els.sysLogoFile.value = "";
      tWarning("Logo resetada para o padrão (ainda não salva).");
    });

    els.sysFaviconRemove?.addEventListener("click", () => {
      sysFavDataUrl = "";
      const dFav = getDefaultFaviconUrl();
      setImgPreview(els.sysFaviconPreview, dFav);
      if (els.sysFaviconFile) els.sysFaviconFile.value = "";
      tWarning("Favicon resetado para o padrão (ainda não salva).");
    });

    // reset cores
    els.sysColorsReset?.addEventListener("click", () => {
      if (els.sysAccentPreset) els.sysAccentPreset.value = "visa";
      setAccentCustomEnabled(false);

      const hexVisa = ACCENT_PRESETS.visa || DEFAULT_THEME.accent;
      if (els.sysColorAccent) els.sysColorAccent.value = hexVisa;
      if (els.sysColorAccentHex) els.sysColorAccentHex.value = hexVisa;

      if (els.sysColorDanger) els.sysColorDanger.value = DEFAULT_THEME.danger;
      if (els.sysColorDangerHex) els.sysColorDangerHex.value = DEFAULT_THEME.danger;

      if (els.sysColorSuccess) els.sysColorSuccess.value = DEFAULT_THEME.success;
      if (els.sysColorSuccessHex) els.sysColorSuccessHex.value = DEFAULT_THEME.success;

      tWarning("Cores resetadas (ainda não salva).");
    });

    // restaurar padrão geral
    els.sysReset?.addEventListener("click", async () => {
      const ok = await removeSysPrefs();
      if (!ok) {
        tDanger("Não foi possível restaurar a personalização.");
        return;
      }
      fillSysForm({});
      tSuccess("Personalização restaurada para o padrão.");
    });

    // submit salva e aplica
    els.sysForm?.addEventListener("submit", async (e) => {
      try { e.preventDefault(); } catch (_) {}

      const systemName = titleCasePT(els.sysSystemName?.value || "");
      const companyName = titleCasePT(els.sysCompanyName?.value || "");

      if (!systemName && !companyName) {
        tDanger("Informe pelo menos o Nome do sistema ou o Nome da empresa.");
        try { els.sysSystemName?.focus(); } catch (_) {}
        return;
      }

      const themeMode = normalizeThemeMode(els.sysThemeMode?.value || "light");
      const presetSel = String(els.sysAccentPreset?.value || "visa").toLowerCase();

      let accentPreset = "";
      let accent = "";

      if (presetSel === "custom") {
        accent = ensureHex(
          els.sysColorAccentHex?.value || els.sysColorAccent?.value,
          DEFAULT_THEME.accent
        );
        accentPreset = "";
      } else {
        accentPreset = ACCENT_PRESETS[presetSel] ? presetSel : "visa";
        accent = "";
      }

      const danger  = ensureHex(els.sysColorDangerHex?.value || els.sysColorDanger?.value, DEFAULT_THEME.danger);
      const success = ensureHex(els.sysColorSuccessHex?.value || els.sysColorSuccess?.value, DEFAULT_THEME.success);

      const confirmColors = window.confirm("Alterar cores pode afetar o padrão visual do sistema. Deseja aplicar?");
      if (!confirmColors) return;

      const payload = {
        identity: {
          systemName,
          companyName,
          cnpj: normalizeCnpj(els.sysCnpj?.value || ""),
          razao: titleCasePT(els.sysRazao?.value || ""),
          slogan: normalizeSpaces(els.sysSlogan?.value || ""),
          notes: normalizeSpaces(els.sysNotes?.value || ""),
        },
        contact: {
          site: normalizeSpaces(els.sysSite?.value || ""),
          email: normalizeSpaces(els.sysEmail?.value || ""),
          phone: normalizeSpaces(els.sysPhone?.value || ""),
          whats: normalizeSpaces(els.sysWhats?.value || ""),
        },
        brand: {
          logoDataUrl: sysLogoDataUrl || "",
          faviconDataUrl: sysFavDataUrl || "",
        },
        prefs: {
          currency: (els.sysCurrency?.value || "BRL").trim(),
          timezone: normalizeSpaces(els.sysTimezone?.value || "America/Sao_Paulo"),
          compactTables: (els.sysCompact?.value || "0") === "1",
        },
        theme: {
          mode: themeMode,
          accentPreset,
          accent,
          danger,
          success,
        },
        updatedAt: Date.now(),
        schema: "sys_prefs_v2",
      };

      const ok = await saveSysPrefs(payload);
      if (!ok) {
        tDanger("Não foi possível salvar a personalização.");
        return;
      }

      closeSysModal();
      tSuccess("Personalização salva.");
    });

    // fechar modal clicando fora
    [els.modal, els.formModal, els.delModal, els.sysModal].forEach((m) => {
      if (!m) return;
      m.addEventListener("click", (e) => {
        if (e.target === m) {
          if (m === els.modal) closeModal();
          else if (m === els.formModal) closeFormModal();
          else if (m === els.delModal) closeDelModal();
          else closeSysModal();
        }
      });
    });

    tShow("Ferramentas pronto (CRUD via SysStore).");
  });
})();
