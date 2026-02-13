// app/static/js/system/sys_company_panel.js
// Integra company.php (server-side) com o modal "Personalização do Sistema" (Ferramentas).
// - Preenche quando o modal abre (MutationObserver) para evitar conflitos com ferramentas.js.
// - Salva no servidor e recarrega a página para refletir em trechos server-side (rodapés/relatórios).

(function () {
  const API = {
    get:   "/sistema-visa/public_php/api/company_get.php",
    save:  "/sistema-visa/public_php/api/company_save.php",
    reset: "/sistema-visa/public_php/api/company_reset.php",
  };

  // IDs REAIS do seu modal (home.php)
  const EL = {
    modal: "#ftSysModal",
    form:  "#ftSysForm",

    // Campos (server-side)
    systemName: "#ftSysSystemName",   // ✅ novo: system_name
    company:    "#ftSysCompanyName",
    cnpj:       "#ftSysCnpj",
    site:       "#ftSysSite",
    tagline:    "#ftSysSlogan",       // mapeia para company.php: tagline

    // Botão "Restaurar padrão"
    btnReset: "#ftSysReset",
  };

  function $(sel) { return document.querySelector(sel); }

  async function apiGet(url) {
    const r = await fetch(url, { credentials: "include" });
    const j = await r.json().catch(() => null);
    if (!r.ok || !j || !j.ok) throw new Error(j?.error || "REQUEST_FAILED");
    return j.data;
  }

  async function apiPost(url, bodyObj) {
    const r = await fetch(url, {
      method: "POST",
      credentials: "include",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(bodyObj || {}),
    });
    const j = await r.json().catch(() => null);
    if (!r.ok || !j || !j.ok) throw new Error(j?.error || "REQUEST_FAILED");
    return j.data;
  }

  function fillForm(corp) {
    const form = $(EL.form);
    if (!form) return;

    const setVal = (sel, v) => {
      const el = $(sel);
      if (!el) return;
      el.value = (v ?? "");
    };

    setVal(EL.systemName, corp.system_name);
    setVal(EL.company, corp.company);
    setVal(EL.cnpj, corp.cnpj);
    setVal(EL.site, corp.site);
    setVal(EL.tagline, corp.tagline);
  }

  function readFormPatch() {
    const systemName = String($(EL.systemName)?.value ?? "").trim();

    // ✅ Como você não tem um input específico para report_footer_note,
    // nós geramos automaticamente baseado no Nome do sistema.
    const footerNote = systemName
      ? `Documento gerado automaticamente pelo ${systemName}.`
      : "";

    return {
      system_name: systemName,
      company: String($(EL.company)?.value ?? "").trim(),
      cnpj: String($(EL.cnpj)?.value ?? "").trim(),
      site: String($(EL.site)?.value ?? "").trim(),
      tagline: String($(EL.tagline)?.value ?? "").trim(),
      report_footer_note: footerNote,
    };
  }

  // Busca e preenche (usado no open do modal)
  async function fetchAndFill() {
    try {
      const corp = await apiGet(API.get);
      fillForm(corp);
      return corp;
    } catch (e) {
      console.error("[sys_company_panel] GET falhou:", e);
      return null;
    }
  }

  // Detecta quando o modal abre e preenche na hora (evita “limpeza” do ferramentas.js)
  function observeModalOpen() {
    const modal = $(EL.modal);
    if (!modal) return;

    const obs = new MutationObserver(() => {
      const hidden = modal.getAttribute("aria-hidden");
      const isOpen = (hidden === "false" || hidden === false);
      if (isOpen) fetchAndFill();
    });

    obs.observe(modal, { attributes: true, attributeFilter: ["aria-hidden", "class"] });
  }

  async function init() {
    const form = $(EL.form);
    if (!form) return;

    // ✅ garante preenchimento ao abrir o modal
    observeModalOpen();

    // (opcional) primeira carga
    await fetchAndFill();

    // Salvar
    form.addEventListener("submit", async (ev) => {
      ev.preventDefault();

      try {
        const patch = readFormPatch();
        const corp = await apiPost(API.save, patch);
        fillForm(corp);

        // ✅ Para refletir em qualquer trecho server-side (base_private, relatórios etc.)
        location.reload();
      } catch (e) {
        console.error("[sys_company_panel] SAVE falhou:", e);
        alert("Falha ao salvar. Verifique permissões em /app/storage.");
      }
    });

    // Reset
    const btnReset = $(EL.btnReset);
    if (btnReset) {
      btnReset.addEventListener("click", async () => {
        try {
          const corp = await apiPost(API.reset, {});
          fillForm(corp);
          location.reload();
        } catch (e) {
          console.error("[sys_company_panel] RESET falhou:", e);
          alert("Falha ao restaurar padrão.");
        }
      });
    }
  }

  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
  else init();
})();