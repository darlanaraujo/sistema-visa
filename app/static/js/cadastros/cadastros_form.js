// app/static/js/cadastros/cadastros_form.js
(function () {
  const AVATAR_MAP = {
    cliente: null,
    fornecedor: null,
    motorista: null,
    transportadora: null,
  };

  function toast(kind, message) {
    const run = () => {
      try {
        if (window.Toast && typeof window.Toast[kind] === "function") {
          window.Toast[kind](message);
          return true;
        }
        if (window.Toast && typeof window.Toast.show === "function") {
          window.Toast.show(message);
          return true;
        }
      } catch (_) {}
      return false;
    };

    if (run()) return;
    window.setTimeout(run, 60);
  }

  function appBase() {
    const path = String(window.location.pathname || "");
    const idx = path.indexOf("/app/templates/");
    return idx >= 0 ? path.slice(0, idx) : "";
  }

  function canonicalCadastroUrl() {
    const current = new URL(window.location.href);
    const modo = current.searchParams.get("modo");
    const id = current.searchParams.get("id");
    const tipo = current.searchParams.get("tipo");
    const embed = current.searchParams.get("embed");
    const returnTipo = document.querySelector("[data-cad-form] input[name='return_tipo']")?.value || "";
    if (modo !== "cadastro") return null;

    const next = new URL(current.pathname, current.origin);
    next.searchParams.set("modo", "cadastro");
    if (id) next.searchParams.set("id", id);
    if (tipo || returnTipo) next.searchParams.set("tipo", tipo || returnTipo);
    if (embed === "1") next.searchParams.set("embed", "1");
    return next.pathname + next.search;
  }

  function initials(value) {
    return String(value || "")
      .trim()
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((chunk) => chunk.charAt(0).toUpperCase())
      .join("") || "CD";
  }

  function safeJson(raw, fallback) {
    try {
      return JSON.parse(String(raw || ""));
    } catch (_) {
      return fallback;
    }
  }

  function digits(value) {
    return String(value || "").replace(/\D+/g, "");
  }

  function apiUrl(path) {
    return `${appBase()}${path}`;
  }

  Object.keys(AVATAR_MAP).forEach((tipo) => {
    const path = `/app/static/img/avatar-${tipo}.png`;
    AVATAR_MAP[tipo] = (typeof window.appUrl === "function") ? window.appUrl(path) : apiUrl(path);
  });

  function postInlineState(payload) {
    if (window.parent && window.parent !== window) {
      try {
        window.parent.postMessage(payload, window.location.origin);
      } catch (_) {}
    }
  }

  function formatDocumento(value, tipoPessoa = "PF") {
    const isPj = String(tipoPessoa || "PF").toUpperCase() === "PJ";
    const raw = digits(value).slice(0, isPj ? 14 : 11);
    if (!isPj) {
      return raw
        .replace(/^(\d{3})(\d)/, "$1.$2")
        .replace(/^(\d{3})\.(\d{3})(\d)/, "$1.$2.$3")
        .replace(/\.(\d{3})(\d)/, ".$1-$2")
        .slice(0, 14);
    }

    return raw
      .replace(/^(\d{2})(\d)/, "$1.$2")
      .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")
      .replace(/\.(\d{3})(\d)/, ".$1/$2")
      .replace(/(\d{4})(\d)/, "$1-$2")
      .slice(0, 18);
  }

  function formatCep(value) {
    return digits(value)
      .slice(0, 8)
      .replace(/^(\d{5})(\d)/, "$1-$2");
  }

  function formatTelefone(value) {
    const raw = digits(value).slice(0, 11);
    if (raw.length <= 10) {
      return raw
        .replace(/^(\d{2})(\d)/, "($1) $2")
        .replace(/(\d{4})(\d)/, "$1-$2")
        .slice(0, 14);
    }

    return raw
      .replace(/^(\d{2})(\d)/, "($1) $2")
      .replace(/(\d{5})(\d)/, "$1-$2")
      .slice(0, 15);
  }

  function formatEstado(value) {
    return String(value || "")
      .replace(/[^a-z]/gi, "")
      .toUpperCase()
      .slice(0, 2);
  }

  function formatCnh(value) {
    return digits(value).slice(0, 11);
  }

  function formatPlaca(value) {
    const raw = String(value || "").replace(/[^a-z0-9]/gi, "").toUpperCase().slice(0, 7);
    if (raw.length <= 3) return raw;
    return `${raw.slice(0, 3)}-${raw.slice(3)}`.slice(0, 8);
  }

  function formatDecimal(value) {
    return String(value || "")
      .replace(/[^0-9.,]/g, "")
      .replace(",", ".");
  }

  function normalizeUpperTextValue(value) {
    return String(value || "").toLocaleUpperCase("pt-BR");
  }

  function applyMask(input) {
    const kind = String(input.getAttribute("data-cad-mask") || "");
    if (kind === "documento") {
      const tipoPessoa = input.form?.querySelector("[data-cad-live-tipo-pessoa]")?.value || "PF";
      input.value = formatDocumento(input.value, tipoPessoa);
    } else if (kind === "cep") {
      input.value = formatCep(input.value);
    } else if (kind === "telefone") {
      input.value = formatTelefone(input.value);
    } else if (kind === "estado") {
      input.value = formatEstado(input.value);
    } else if (kind === "cnh") {
      input.value = formatCnh(input.value);
    } else if (kind === "placa") {
      input.value = formatPlaca(input.value);
    } else if (kind === "decimal") {
      input.value = formatDecimal(input.value);
    }
  }

  function shouldForceUppercase(field) {
    if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) {
      return false;
    }

    const type = String(field.getAttribute("type") || "text").toLowerCase();
    if (["email", "hidden", "search", "password", "date", "number"].includes(type)) {
      return false;
    }

    if (field.hasAttribute("data-cad-mask")) {
      return false;
    }

    return true;
  }

  function resolveAvatar(selectedSlugs) {
    const base = appBase();
    for (const slug of selectedSlugs) {
      if (AVATAR_MAP[slug]) {
        return `${base}${AVATAR_MAP[slug]}`;
      }
    }
    return `${base}${AVATAR_MAP.cliente}`;
  }

  function initForm() {
    const form = document.querySelector("[data-cad-form]");
    if (!form) return;

    const nameInputs = Array.from(form.querySelectorAll("[data-cad-live-name]"));
    const razaoInputs = Array.from(form.querySelectorAll("[data-cad-live-razao]"));
    const tipoPessoaSelect = form.querySelector("[data-cad-live-tipo-pessoa]");
    const documentoInputs = Array.from(form.querySelectorAll("[data-cad-live-documento]"));
    const statusSelect = form.querySelector("[data-cad-live-status]");
    const heroTitle = document.getElementById("cadFormHeroTitle");
    const heroSubtitle = document.getElementById("cadFormHeroSubtitle");
    const heroStatus = document.getElementById("cadFormHeroStatus");
    const heroDocumento = document.getElementById("cadFormHeroDocumento");
    const avatar = document.getElementById("cadFormAvatar");
    const conversionNote = form.querySelector("[data-cad-conversion-note]");
    const conversionAlert = form.querySelector("[data-cad-conversion-alert]");
    const documentFields = Array.from(form.querySelectorAll("[data-cad-document-field]"));
    const pfSections = Array.from(form.querySelectorAll("[data-cad-ident-section=\"pf\"]"));
    const pjSections = Array.from(form.querySelectorAll("[data-cad-ident-section=\"pj\"]"));
    const motoristaPjSections = Array.from(form.querySelectorAll("[data-cad-motorista-pj-section]"));
    const motoristaPfContactFields = Array.from(form.querySelectorAll("[data-cad-motorista-pf-contact]"));
    const pfExclusiveFields = Array.from(form.querySelectorAll("[data-cad-pf-exclusive]"));
    const typeInputs = Array.from(form.querySelectorAll("[data-cad-type-input]"));
    const typeBlocks = Array.from(form.querySelectorAll("[data-cad-type-block]"));
    const pjFields = Array.from(form.querySelectorAll("[data-cad-pj-field]"));
    const structuralBlocks = Array.from(form.querySelectorAll("[data-cad-shared-structural]"));
    const maskedInputs = Array.from(form.querySelectorAll("[data-cad-mask]"));
    const upperFields = Array.from(form.querySelectorAll("input, textarea")).filter((field) => shouldForceUppercase(field));
    const emailFields = Array.from(form.querySelectorAll('input[type="email"], input[name="email"], input[name$="[email]"]'));
    const saved = String(form.getAttribute("data-cad-form-saved") || "");
    const isEmbed = String(form.getAttribute("data-cad-embed") || "0") === "1";
    const inlinePayload = safeJson(form.getAttribute("data-cad-inline-payload"), {});
    const tagList = form.querySelector("[data-cad-tags-list]");
    const tagInput = form.querySelector("[data-cad-tags-input]");
    const tagAddButton = form.querySelector("[data-cad-tags-add]");
    const tagHiddenHost = form.querySelector("[data-cad-tags-hidden]");
    const motoristaList = form.querySelector("[data-cad-motoristas-list]");
    const veiculoList = form.querySelector("[data-cad-veiculos-list]");
    const motoristaTemplate = document.getElementById("cadTemplateMotoristaVinculado");
    const veiculoTemplate = document.getElementById("cadTemplateVeiculo");
    const addMotoristaButton = form.querySelector("[data-cad-add-motorista]");
    const addVeiculoButton = form.querySelector("[data-cad-add-veiculo]");
    const motoristaCompanyContact = Array.from(form.querySelectorAll("[data-cad-motorista-company-contact]"));
    const currentType = String(form.getAttribute("data-cad-current-type") || "cliente");
    const currentTypeTitle = String(form.getAttribute("data-cad-current-type-title") || currentType);
    const initialSlugs = safeJson(form.getAttribute("data-cad-initial-slugs"), []);
    const conversionOriginTitle = String(form.getAttribute("data-cad-conversion-origin-title") || "");
    const nextTipoInput = form.querySelector("[data-cad-next-tipo]");
    const dirtyMessage = "Há alterações não salvas. Se continuar, você perderá as informações preenchidas.";

    let tags = safeJson(form.getAttribute("data-cad-tags"), []);
    let motoristas = safeJson(form.getAttribute("data-cad-motoristas"), []);
    let veiculos = safeJson(form.getAttribute("data-cad-veiculos"), []);
    let isDirty = false;
    let isSubmitting = false;
    let isApplyingLookup = false;
    const lookupState = {
      cep: { timer: null, lastAutoValue: "", sequence: 0 },
      cnpj: { timer: null, lastAutoValue: "", sequence: 0 },
    };
    const cepInput = form.querySelector('input[name="cep"]');
    const enderecoInput = form.querySelector('input[name="endereco"]');
    const bairroInput = form.querySelector('input[name="bairro"]');
    const cidadeInput = form.querySelector('input[name="cidade"]');
    const estadoSelect = form.querySelector('select[name="estado"]');
    const whatsappInput = form.querySelector('input[name="whatsapp"]');
    const celularInput = form.querySelector('input[name="celular"]');
    const razaoSocialInputs = Array.from(form.querySelectorAll('input[name="razao_social"]'));
    const nomeFantasiaInputs = Array.from(form.querySelectorAll('input[name="nome_fantasia"]'));
    const inscricaoEstadualInputs = Array.from(form.querySelectorAll('input[name="inscricao_estadual"]'));

    function fieldWrapperByName(name) {
      const field = form.querySelector(`[name="${name}"]`);
      return field ? field.closest(".cad-form-field") : null;
    }

    function setFieldRequired(name, active) {
      const wrapper = fieldWrapperByName(name);
      if (!wrapper) return;
      wrapper.classList.toggle("is-required", Boolean(active));
    }

    function clearRequiredHighlights() {
      form.querySelectorAll(".cad-form-field.is-required").forEach((field) => {
        field.classList.remove("is-required");
      });
    }

    function hasAnyFormError() {
      return form.querySelector(".cad-form-error") !== null;
    }

    function showValidationFeedback(message = "") {
      const text = message || `Não foi possível salvar. Revise os campos obrigatórios de ${currentTypeTitle.toLowerCase()} destacados abaixo.`;
      toast("error", text);
      if (conversionAlert) {
        conversionAlert.hidden = false;
        conversionAlert.textContent = text;
      }
    }

    function markDirty() {
      if (isSubmitting || isApplyingLookup) return;
      isDirty = true;
      if (isEmbed) {
        postInlineState({ type: "sv:inline-cadastro-state", dirty: true });
      }
    }

    function clearDirty() {
      isDirty = false;
      if (isEmbed) {
        postInlineState({ type: "sv:inline-cadastro-state", dirty: false });
      }
    }

    function activeLookupField(name) {
      return Array.from(form.querySelectorAll(`[name="${name}"]`)).find((field) => !field.disabled) || null;
    }

    function lookupFeedbackElements(kind) {
      return Array.from(form.querySelectorAll(`[data-cad-lookup-feedback="${kind}"]`));
    }

    function lookupWrappers(kind) {
      return Array.from(form.querySelectorAll(`[data-cad-lookup-field="${kind}"]`));
    }

    function setLookupFeedback(kind, message = "", status = "") {
      lookupFeedbackElements(kind).forEach((node) => {
        node.hidden = message === "";
        node.textContent = message;
        if (status) {
          node.dataset.status = status;
        } else {
          delete node.dataset.status;
        }
      });

      lookupWrappers(kind).forEach((node) => {
        node.classList.toggle("is-loading", status === "loading");
      });
    }

    function bindManualEditProtection(fields) {
      fields.forEach((field) => {
        if (!field) return;
        const markManual = () => {
          if (isApplyingLookup) return;
          field.dataset.cadManualEdited = "1";
        };
        field.addEventListener("input", markManual);
        field.addEventListener("change", markManual);
      });
    }

    function shouldApplyLookup(field) {
      if (!field || field.disabled) return false;
      const current = String(field.value || "").trim();
      if (current === "") return true;
      return field.dataset.cadManualEdited !== "1";
    }

    function applyLookupValue(field, value) {
      if (!field || field.disabled) return false;
      const nextValue = String(value || "").trim();
      if (nextValue === "" || !shouldApplyLookup(field)) return false;
      if (String(field.value || "").trim() === nextValue) return false;

      isApplyingLookup = true;
      field.value = nextValue;
      if (field.hasAttribute("data-cad-mask")) {
        applyMask(field);
      }
      field.dataset.cadManualEdited = "0";
      field.dispatchEvent(new Event("input", { bubbles: true }));
      field.dispatchEvent(new Event("change", { bubbles: true }));
      isApplyingLookup = false;
      return true;
    }

    function syncPhoneMirror(source, target, sourceName) {
      if (!source || !target || target.disabled) return;
      const sourceValue = String(source.value || "").trim();
      const targetValue = String(target.value || "").trim();
      const mirroredFrom = String(target.dataset.cadMirroredFrom || "");

      if (sourceValue === "") {
        if (mirroredFrom === sourceName) {
          target.value = "";
          target.dataset.cadMirroredFrom = "";
          target.dispatchEvent(new Event("input", { bubbles: true }));
          target.dispatchEvent(new Event("change", { bubbles: true }));
        }
        return;
      }

      if (targetValue === "" || mirroredFrom === sourceName) {
        target.value = sourceValue;
        target.dataset.cadMirroredFrom = sourceName;
        if (target.hasAttribute("data-cad-mask")) {
          applyMask(target);
        }
        target.dispatchEvent(new Event("input", { bubbles: true }));
        target.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }

    async function fetchLookup(path, queryKey, value) {
      const url = new URL(apiUrl(path), window.location.origin);
      url.searchParams.set(queryKey, value);

      const controller = typeof AbortController === "function" ? new AbortController() : null;
      const timeoutId = controller ? window.setTimeout(() => controller.abort(), 6500) : null;

      try {
        const response = await fetch(url.toString(), {
          credentials: "same-origin",
          headers: { Accept: "application/json" },
          signal: controller ? controller.signal : undefined,
        });
        const payload = await response.json().catch(() => null);
        if (!response.ok || !payload || payload.success !== true || !payload.data) {
          throw new Error(payload?.error || "Não foi possível concluir a consulta.");
        }
        return payload.data;
      } finally {
        if (timeoutId) {
          window.clearTimeout(timeoutId);
        }
      }
    }

    async function runCepLookup(rawCep, force = false) {
      const cep = digits(rawCep).slice(0, 8);
      if (cep.length !== 8) {
        setLookupFeedback("cep");
        return;
      }

      if (!force && lookupState.cep.lastAutoValue === cep) {
        return;
      }

      const sequence = ++lookupState.cep.sequence;
      setLookupFeedback("cep", "Consultando CEP...", "loading");

      try {
        const payload = await fetchLookup("/public_php/api/cadastros_lookup_cep.php", "cep", cep);
        if (sequence !== lookupState.cep.sequence) return;

        let applied = 0;
        applied += applyLookupValue(enderecoInput, payload?.data?.endereco) ? 1 : 0;
        applied += applyLookupValue(bairroInput, payload?.data?.bairro) ? 1 : 0;
        applied += applyLookupValue(cidadeInput, payload?.data?.cidade) ? 1 : 0;
        applied += applyLookupValue(estadoSelect, payload?.data?.estado) ? 1 : 0;
        lookupState.cep.lastAutoValue = cep;

        if (applied > 0) {
          markDirty();
          setLookupFeedback("cep", "Endereço preenchido automaticamente.", "success");
          toast("success", "CEP consultado com sucesso.");
        } else {
          setLookupFeedback("cep", "CEP consultado. Os campos manuais foram preservados.", "success");
          toast("info", "CEP consultado. Os campos preenchidos manualmente foram mantidos.");
        }
      } catch (error) {
        if (sequence !== lookupState.cep.sequence) return;
        const message = error instanceof Error ? error.message : "Não foi possível consultar o CEP.";
        setLookupFeedback("cep", message, "error");
        toast("warning", message);
      }
    }

    async function runCnpjLookup(rawDocumento, force = false) {
      if (String(tipoPessoaSelect?.value || "PF").toUpperCase() !== "PJ") {
        setLookupFeedback("cnpj");
        return;
      }

      const cnpj = digits(rawDocumento).slice(0, 14);
      if (cnpj.length !== 14) {
        setLookupFeedback("cnpj");
        return;
      }

      if (!force && lookupState.cnpj.lastAutoValue === cnpj) {
        return;
      }

      const sequence = ++lookupState.cnpj.sequence;
      setLookupFeedback("cnpj", "Consultando CNPJ...", "loading");

      try {
        const payload = await fetchLookup("/public_php/api/cadastros_lookup_cnpj.php", "cnpj", cnpj);
        if (sequence !== lookupState.cnpj.sequence) return;

        let applied = 0;
        applied += applyLookupValue(activeLookupField("razao_social"), payload?.data?.razao_social) ? 1 : 0;
        applied += applyLookupValue(activeLookupField("nome_fantasia"), payload?.data?.nome_fantasia) ? 1 : 0;
        applied += applyLookupValue(activeLookupField("inscricao_estadual"), payload?.data?.inscricao_estadual) ? 1 : 0;
        applied += applyLookupValue(cepInput, payload?.data?.cep ? formatCep(payload.data.cep) : "") ? 1 : 0;
        applied += applyLookupValue(enderecoInput, payload?.data?.endereco) ? 1 : 0;
        applied += applyLookupValue(bairroInput, payload?.data?.bairro) ? 1 : 0;
        applied += applyLookupValue(cidadeInput, payload?.data?.cidade) ? 1 : 0;
        applied += applyLookupValue(estadoSelect, payload?.data?.estado) ? 1 : 0;
        lookupState.cnpj.lastAutoValue = cnpj;

        if (applied > 0) {
          markDirty();
          updateHero();
          setLookupFeedback("cnpj", "Dados do CNPJ preenchidos automaticamente.", "success");
          toast("success", "CNPJ consultado com sucesso.");
        } else {
          setLookupFeedback("cnpj", "CNPJ consultado. Os campos manuais foram preservados.", "success");
          toast("info", "CNPJ consultado. Os campos preenchidos manualmente foram mantidos.");
        }
      } catch (error) {
        if (sequence !== lookupState.cnpj.sequence) return;
        const message = error instanceof Error ? error.message : "Não foi possível consultar o CNPJ.";
        setLookupFeedback("cnpj", message, "error");
        toast("warning", message);
      }
    }

    function scheduleLookup(kind, rawValue, force = false) {
      const state = lookupState[kind];
      if (!state) return;
      if (state.timer) {
        window.clearTimeout(state.timer);
      }

      const requiredDigits = kind === "cep" ? 8 : 14;
      if (digits(rawValue).length !== requiredDigits) {
        if (force) {
          setLookupFeedback(kind);
        }
        return;
      }

      state.timer = window.setTimeout(() => {
        if (kind === "cep") {
          runCepLookup(rawValue, force);
        } else {
          runCnpjLookup(rawValue, force);
        }
      }, force ? 0 : 420);
    }

    async function confirmDiscard() {
      if (isSubmitting) return true;
      if (requiresStructuralCompletion()) {
        if (window.UIComponents && typeof window.UIComponents.confirm === "function") {
          return window.UIComponents.confirm({
            eyebrow: "Conclusão obrigatória",
            title: `${currentTypeTitle} ainda não foi concluído`,
            message: `Preencha os dados obrigatórios de ${currentTypeTitle.toLowerCase()} antes de sair desta tela.`,
            confirmLabel: "Descartar cadastro",
            cancelLabel: "Permanecer aqui",
          });
        }
        return false;
      }
      if (!isDirty) return true;
      if (window.UIComponents && typeof window.UIComponents.confirm === "function") {
        return window.UIComponents.confirm({
          eyebrow: "Alterações pendentes",
          title: "Sair sem salvar?",
          message: dirtyMessage,
          confirmLabel: "Sair sem salvar",
          cancelLabel: "Continuar editando",
        });
      }
      return false;
    }

    function activeField(inputs) {
      return inputs.find((input) => !input.disabled) || inputs[0] || null;
    }

    function toggleSectionState(section, hidden) {
      section.hidden = hidden;
      section.setAttribute("aria-hidden", hidden ? "true" : "false");
      section.querySelectorAll("input, select, textarea, button").forEach((field) => {
        field.disabled = hidden;
      });
    }

    function selectedSlugs() {
      return typeInputs
        .filter((input) => input.checked)
        .map((input) => String(input.getAttribute("data-cad-type-slug") || "").trim())
        .filter(Boolean);
    }

    function isStructuralSlug(slug) {
      return slug === "motorista" || slug === "transportadora";
    }

    function selectionDiff() {
      const current = selectedSlugs();
      return {
        current,
        added: current.filter((slug) => !initialSlugs.includes(slug)),
        removed: initialSlugs.filter((slug) => !current.includes(slug)),
      };
    }

    function typeTitle(slug) {
      return {
        cliente: "Cliente",
        fornecedor: "Fornecedor",
        motorista: "Motorista",
        transportadora: "Transportadora",
      }[slug] || slug;
    }

    function setAvatar() {
      if (!avatar) return;

      const currentSlugs = selectedSlugs();
      const slugs = [currentType, ...currentSlugs.filter((slug) => slug !== currentType)];
      const nameInput = activeField(nameInputs);
      const razaoInput = activeField(razaoInputs);
      const label = (String(tipoPessoaSelect?.value || "PF").toUpperCase() === "PJ"
        ? razaoInput?.value?.trim() || nameInput?.value?.trim()
        : nameInput?.value?.trim()) || "Cadastro";
      const src = resolveAvatar(slugs);
      avatar.innerHTML = "";

      const img = document.createElement("img");
      img.src = src;
      img.alt = `Avatar de ${label}`;
      img.loading = "eager";
      img.decoding = "async";
      img.addEventListener("error", () => {
        avatar.textContent = initials(label);
      }, { once: true });
      avatar.appendChild(img);
    }

    function updateHero() {
      const tipoPessoa = String(tipoPessoaSelect?.value || "PF").toUpperCase();
      const nameInput = activeField(nameInputs);
      const razaoInput = activeField(razaoInputs);
      const documentoInput = activeField(documentoInputs);
      const nome = tipoPessoa === "PJ"
        ? (razaoInput?.value?.trim() || nameInput?.value?.trim() || "Novo cadastro")
        : (nameInput?.value?.trim() || "Novo cadastro");
      const tipoPessoaLabel = tipoPessoa === "PJ" ? "Pessoa jurídica" : "Pessoa física";
      const status = String(statusSelect?.value || "ativo").toLowerCase() === "inativo" ? "Inativo" : "Ativo";
      const documento = documentoInput?.value?.trim() || "Documento não informado";

      if (heroTitle) heroTitle.textContent = nome;
      if (heroSubtitle) heroSubtitle.textContent = tipoPessoaLabel;
      if (heroStatus) {
        heroStatus.textContent = status;
        heroStatus.classList.toggle("cad-status--inativo", status === "Inativo");
        heroStatus.classList.toggle("cad-status--ativo", status !== "Inativo");
      }
      if (heroDocumento) {
        heroDocumento.innerHTML = `<i class="fa-solid fa-id-card-clip" aria-hidden="true"></i>${documento}`;
      }
      setAvatar();
    }

    function updateDocumentoField() {
      const isPj = String(tipoPessoaSelect?.value || "PF").toUpperCase() === "PJ";
      documentFields.forEach((field) => {
        const mode = String(field.getAttribute("data-cad-document-field") || "");
        const active = mode === "" || (mode === "pj" && isPj) || (mode === "pf" && !isPj);
        field.classList.toggle("is-active", active && isPj);
      });

      documentoInputs.forEach((input) => {
        input.placeholder = !input.disabled && isPj ? "Use para auto preenchimento" : "";
      });
    }

    function renderTags() {
      if (!tagList || !tagHiddenHost) return;
      tagList.innerHTML = "";
      tagHiddenHost.innerHTML = "";

      tags.forEach((tag, index) => {
        const chip = document.createElement("button");
        chip.type = "button";
        chip.className = "cad-tag-chip";
        chip.innerHTML = `<span>${tag}</span><i class="fa-solid fa-xmark" aria-hidden="true"></i>`;
        chip.addEventListener("click", () => {
          tags.splice(index, 1);
          renderTags();
        });
        tagList.appendChild(chip);

        const hidden = document.createElement("input");
        hidden.type = "hidden";
        hidden.name = "tags[]";
        hidden.value = tag;
        tagHiddenHost.appendChild(hidden);
      });
    }

    function addTag(value) {
      const raw = String(value || "").trim();
      if (!raw) return;

      const parts = raw
        .split(",")
        .map((chunk) => chunk.trim())
        .filter(Boolean);

      if (parts.length === 0) return;

      let added = 0;
      let duplicated = 0;

      parts.forEach((tag) => {
        if (tags.includes(tag)) {
          duplicated += 1;
          return;
        }
        tags.push(tag);
        added += 1;
      });

      renderTags();
      markDirty();
      if (tagInput) tagInput.value = "";

      if (added > 1) {
        toast("success", `${added} tags adicionadas.`);
      } else if (added === 1 && duplicated > 0) {
        toast("info", "Tag adicionada. Itens repetidos foram ignorados.");
      } else if (added === 0 && duplicated > 0) {
        toast("info", "As tags informadas já estavam adicionadas.");
      }
    }

    function applyMasksInside(container) {
      container.querySelectorAll("[data-cad-mask]").forEach((input) => {
        applyMask(input);
        input.addEventListener("input", () => applyMask(input));
        input.addEventListener("blur", () => applyMask(input));
      });
    }

    function renderMotoristas() {
      if (!motoristaList || !motoristaTemplate) return;
      motoristaList.innerHTML = "";

      motoristas.forEach((item, index) => {
        const html = motoristaTemplate.innerHTML.replaceAll("__INDEX__", String(index));
        const wrapper = document.createElement("div");
        wrapper.innerHTML = html.trim();
        const element = wrapper.firstElementChild;
        if (!element) return;

        const fill = {
          nome: item.nome || "",
          cpf: item.cpf || "",
          cnh: item.cnh || "",
          contato: item.contato || "",
          telefone_fixo: item.telefone_fixo || item.telefoneFixo || "",
          whatsapp: item.whatsapp || "",
          celular: item.celular || "",
          email: item.email || "",
          principal: !!item.principal,
        };

        Object.entries(fill).forEach(([key, value]) => {
          const field = element.querySelector(`[name="motoristas_vinculados[${index}][${key}]"]`);
          if (!field) return;
          if (field.type === "checkbox") {
            field.checked = Boolean(value);
          } else {
            field.value = String(value);
          }
        });

        const remove = element.querySelector("[data-cad-remove-item]");
        if (remove) {
          remove.addEventListener("click", () => {
            motoristas.splice(index, 1);
            renderMotoristas();
            markDirty();
          });
        }

        motoristaList.appendChild(element);
      });

      applyMasksInside(motoristaList);
      syncMotoristaButton();
    }

    function syncMotoristaButton() {
      if (!addMotoristaButton) return;
      const isMotoristaScreen = currentType === "motorista";
      const hasSecondary = motoristas.length >= 1;
      addMotoristaButton.disabled = isMotoristaScreen && hasSecondary;
    }

    function renderVeiculos() {
      if (!veiculoList || !veiculoTemplate) return;
      veiculoList.innerHTML = "";
      const startIndex = currentType === "motorista" || currentType === "transportadora" ? 1 : 0;
      veiculos.slice(startIndex).forEach((item, renderIndex) => {
        const index = renderIndex + startIndex;
        const html = veiculoTemplate.innerHTML.replaceAll("__INDEX__", String(index));
        const wrapper = document.createElement("div");
        wrapper.innerHTML = html.trim();
        const element = wrapper.firstElementChild;
        if (!element) return;

        const fill = {
          modelo: item.modelo || "",
          placa: item.placa || "",
          placa_adicional: item.placa_adicional || item.placaAdicional || "",
          tipo_carroceria: item.tipo_carroceria || item.tipoCarroceria || "",
          metragem: item.metragem || "",
          peso_carga: item.peso_carga || item.pesoCarga || "",
        };

        Object.entries(fill).forEach(([key, value]) => {
          const field = element.querySelector(`[name="veiculos[${index}][${key}]"]`);
          if (field) {
            field.value = String(value);
          }
        });

        const remove = element.querySelector("[data-cad-remove-item]");
        if (remove) {
          remove.addEventListener("click", () => {
            veiculos.splice(index, 1);
            renderVeiculos();
            markDirty();
          });
        }

        veiculoList.appendChild(element);
      });
    }

    function hasFieldPair(prefix, firstField, secondField) {
      const firstInputs = Array.from(form.querySelectorAll(`input[name^="${prefix}["][name$="[${firstField}]"]`));
      return firstInputs.some((firstInput) => {
        const match = firstInput.name.match(/\[(\d+)\]\[[^\]]+\]$/);
        if (!match) return false;
        const index = match[1];
        const secondInput = form.querySelector(`input[name="${prefix}[${index}][${secondField}]"]`);
        return String(firstInput.value || "").trim() !== "" && String(secondInput?.value || "").trim() !== "";
      });
    }

    function hasVeiculoPrincipal() {
      return hasFieldPair("veiculos", "modelo", "placa");
    }

    function hasMotoristaVinculado() {
      const nomeInputs = Array.from(form.querySelectorAll('input[name^="motoristas_vinculados["][name$="[nome]"]'));
      return nomeInputs.some((input) => String(input.value || "").trim() !== "");
    }

    function highlightRequiredFields() {
      clearRequiredHighlights();

      if (currentType === "motorista") {
        setFieldRequired("cnh", String(form.querySelector('input[name="cnh"]')?.value || "").trim() === "");
        setFieldRequired(
          "motorista_cpf",
          String(tipoPessoaSelect?.value || "PF").toUpperCase() === "PJ"
            && String(form.querySelector('input[name="motorista_cpf"]')?.value || "").trim() === ""
        );
        setFieldRequired("veiculos[0][modelo]", String(form.querySelector('input[name="veiculos[0][modelo]"]')?.value || "").trim() === "");
        setFieldRequired("veiculos[0][placa]", String(form.querySelector('input[name="veiculos[0][placa]"]')?.value || "").trim() === "");
      }

      if (currentType === "transportadora") {
        return;
      }
    }

    function requiresStructuralCompletion() {
      const current = selectedSlugs();
      if (!current.includes(currentType)) return false;

      if (currentType === "motorista") {
        const cnhInput = form.querySelector('input[name="cnh"]');
        const cnh = String(cnhInput?.value || "").trim();
        return cnh === "" || !hasVeiculoPrincipal();
      }

      if (currentType === "transportadora") {
        return false;
      }

      return false;
    }

    function updateConversionMessaging() {
      if (!conversionNote && !conversionAlert) return;

      const diff = selectionDiff();
      const nextType = diff.added[0] || "";
      const removedType = diff.removed[0] || "";
      let alertMessage = "";
      let noteMessage = "";

      if (conversionOriginTitle) {
        alertMessage = `Cadastro base salvo. Agora conclua os dados obrigatórios de ${currentTypeTitle} para finalizar a conversão a partir de ${conversionOriginTitle}.`;
      } else if (nextType) {
        alertMessage = `Você marcou ${typeTitle(nextType)}. Ao salvar, esta ficha será concluída como ${currentTypeTitle} e o sistema abrirá a tela de ${typeTitle(nextType)} para finalizar o novo tipo.`;
      } else if (removedType) {
        if (removedType === currentType) {
          alertMessage = `Você está removendo ${currentTypeTitle} deste cadastro. Ao confirmar o salvamento, este registro deixará de existir nesta lista e você será redirecionado para outro contexto.`;
        } else {
          alertMessage = `${typeTitle(removedType)} será removido deste cadastro ao salvar. Os vínculos históricos permanecem preservados, mas o tipo deixará de ficar ativo para esta ficha.`;
        }
      }

      if (nextType) {
        noteMessage = `Conclua primeiro ${currentTypeTitle}. Depois do salvamento, a edição seguirá em ${typeTitle(nextType)}.`;
      } else if (removedType) {
        noteMessage = removedType === currentType
          ? `Você está retirando o tipo atual desta ficha. Ao salvar, a navegação sairá desta tela.`
          : `A remoção do tipo selecionado será aplicada após o salvamento desta ficha.`;
      } else {
        noteMessage = (currentType === "motorista" || currentType === "transportadora")
          ? "Esta tela conclui o tipo atual. Associar outro tipo estrutural não significa preencher os dados extras aqui; a conclusão ocorre na tela correspondente."
          : "Esta tela conclui o tipo atual. Se outro tipo estrutural for associado, a conclusão dos dados complementares acontecerá na tela correspondente.";
      }

      if (conversionAlert) {
        conversionAlert.hidden = alertMessage === "";
        conversionAlert.textContent = alertMessage;
      }

      if (conversionNote) {
        conversionNote.textContent = noteMessage;
      }
    }

    function updateTypeBlocks(changedInput = null) {
      const slugs = selectedSlugs();
      const hasMotorista = currentType === "motorista";
      const hasTransportadora = currentType === "transportadora";
      const finalSlugs = slugs;
      const finalHasTransportadora = finalSlugs.includes("transportadora");
      const nextType = selectionDiff().added[0] || "";

      if (nextTipoInput) {
        nextTipoInput.value = nextType;
      }

      if (finalHasTransportadora && tipoPessoaSelect && tipoPessoaSelect.value !== "PJ") {
        tipoPessoaSelect.value = "PJ";
        toast("info", "Transportadora foi ajustada para pessoa jurídica.");
      }

      typeBlocks.forEach((block) => {
        const slug = String(block.getAttribute("data-cad-type-block") || "");
        block.hidden = slug !== currentType;
      });

      structuralBlocks.forEach((block) => {
        block.hidden = !(hasMotorista || hasTransportadora);
      });

      updateConversionMessaging();
      highlightRequiredFields();

      setAvatar();
    }

    function updatePessoaFields() {
      const isPj = String(tipoPessoaSelect?.value || "PF").toUpperCase() === "PJ";
      const shouldExclusiveSwitch = currentType === "cliente" || currentType === "fornecedor";
      const shouldComplementMotorista = currentType === "motorista";

      if (shouldExclusiveSwitch) {
        pfSections.forEach((section) => toggleSectionState(section, isPj));
        pjSections.forEach((section) => toggleSectionState(section, !isPj));
      }

      if (shouldComplementMotorista) {
        motoristaPjSections.forEach((section) => toggleSectionState(section, !isPj));
        motoristaCompanyContact.forEach((section) => toggleSectionState(section, !isPj));
        motoristaPfContactFields.forEach((field) => {
          field.hidden = isPj;
          field.querySelectorAll("input, select, textarea").forEach((input) => {
            input.disabled = isPj;
          });
        });
      }

      pjFields.forEach((field) => {
        field.hidden = shouldExclusiveSwitch ? field.hidden : !isPj;
      });
      pfExclusiveFields.forEach((field) => {
        field.hidden = shouldExclusiveSwitch ? isPj : false;
      });
      updateDocumentoField();
      updateTypeBlocks();
      updateHero();
    }

    [...nameInputs, ...razaoInputs, ...documentoInputs, tipoPessoaSelect, statusSelect].forEach((input) => {
      if (!input) return;
      input.addEventListener("input", updateHero);
      input.addEventListener("change", updateHero);
    });

    form.querySelectorAll("input, select, textarea").forEach((field) => {
      if (field.type === "hidden") return;
      if (field.hasAttribute("data-cad-type-input")) return;
      field.addEventListener("input", markDirty);
      field.addEventListener("change", markDirty);
      field.addEventListener("input", highlightRequiredFields);
      field.addEventListener("change", highlightRequiredFields);
    });

    if (tipoPessoaSelect) {
      tipoPessoaSelect.addEventListener("change", updatePessoaFields);
      tipoPessoaSelect.addEventListener("change", () => {
        const documentoAtivo = activeLookupField("documento");
        scheduleLookup("cnpj", documentoAtivo?.value || "", true);
      });
    }

    typeInputs.forEach((input) => {
      input.addEventListener("change", async () => {
        const slug = String(input.getAttribute("data-cad-type-slug") || "").trim();
        const diff = selectionDiff();
        const isAdding = input.checked;

        if (selectedSlugs().length === 0) {
          input.checked = true;
          updateTypeBlocks();
          if (window.UIComponents && typeof window.UIComponents.confirm === "function") {
            await window.UIComponents.confirm({
              eyebrow: "Tipo obrigatório",
              title: "Ao menos um tipo deve permanecer ativo",
              message: "Todo cadastro precisa continuar classificado em pelo menos um tipo.",
              confirmLabel: "Entendi",
              cancelLabel: "Fechar",
            });
          }
          return;
        }

        if (diff.added.length > 1 || diff.removed.length > 1 || (diff.added.length > 0 && diff.removed.length > 0)) {
          input.checked = !input.checked;
          updateTypeBlocks();
          if (window.UIComponents && typeof window.UIComponents.confirm === "function") {
            await window.UIComponents.confirm({
              eyebrow: "Conversão guiada",
              title: "Conclua uma alteração por vez",
              message: "Primeiro salve a conversão atual e finalize o novo tipo. Depois disso, você poderá marcar ou desmarcar outro tipo.",
              confirmLabel: "Entendi",
              cancelLabel: "Fechar",
            });
          }
          return;
        }

        if (!(window.UIComponents && typeof window.UIComponents.confirm === "function")) {
          markDirty();
          updateTypeBlocks(input);
          return;
        }

        const confirmed = await window.UIComponents.confirm({
          eyebrow: isAdding ? "Novo tipo selecionado" : "Remoção de tipo",
          title: isAdding ? `Associar ${typeTitle(slug)} a este cadastro?` : `Remover ${typeTitle(slug)} deste cadastro?`,
          message: isAdding
            ? `Ao salvar, o cadastro será mantido em ${currentTypeTitle} e depois seguirá para ${typeTitle(slug)} para concluir os dados obrigatórios desse novo tipo.`
            : slug === currentType
              ? `Ao salvar, este cadastro deixará de existir como ${currentTypeTitle} nesta tela. Os vínculos históricos da pessoa serão preservados, mas a navegação sairá deste contexto.`
              : `${typeTitle(slug)} deixará de ficar ativo neste cadastro após o salvamento. A pessoa continuará encontrável pelos módulos a partir da entidade base.`,
          confirmLabel: isAdding ? "Confirmar seleção" : "Confirmar remoção",
          cancelLabel: "Manter como está",
        });

        if (!confirmed) {
          input.checked = !input.checked;
          updateTypeBlocks();
          return;
        }

        markDirty();
        updateTypeBlocks(input);
      });
    });

    maskedInputs.forEach((input) => {
      applyMask(input);
      input.addEventListener("input", () => applyMask(input));
      input.addEventListener("blur", () => applyMask(input));
    });

    upperFields.forEach((field) => {
      field.addEventListener("input", () => {
        const start = field.selectionStart;
        const end = field.selectionEnd;
        const nextValue = normalizeUpperTextValue(field.value);
        if (field.value === nextValue) return;
        field.value = nextValue;
        if (typeof start === "number" && typeof end === "number") {
          field.setSelectionRange(start, end);
        }
      });
      field.value = normalizeUpperTextValue(field.value);
    });

    emailFields.forEach((field) => {
      field.addEventListener("input", () => {
        const start = field.selectionStart;
        const end = field.selectionEnd;
        const nextValue = String(field.value || "").toLowerCase();
        if (field.value === nextValue) return;
        field.value = nextValue;
        if (typeof start === "number" && typeof end === "number") {
          field.setSelectionRange(start, end);
        }
      });
      field.value = String(field.value || "").toLowerCase();
    });

    if (whatsappInput && celularInput) {
      whatsappInput.addEventListener("input", () => syncPhoneMirror(whatsappInput, celularInput, "whatsapp"));
      celularInput.addEventListener("input", () => syncPhoneMirror(celularInput, whatsappInput, "celular"));
      whatsappInput.addEventListener("change", () => syncPhoneMirror(whatsappInput, celularInput, "whatsapp"));
      celularInput.addEventListener("change", () => syncPhoneMirror(celularInput, whatsappInput, "celular"));
      whatsappInput.addEventListener("input", () => {
        if (String(celularInput.dataset.cadMirroredFrom || "") !== "whatsapp") return;
        celularInput.dataset.cadMirroredFrom = "whatsapp";
      });
      celularInput.addEventListener("input", () => {
        if (String(whatsappInput.dataset.cadMirroredFrom || "") !== "celular") return;
        whatsappInput.dataset.cadMirroredFrom = "celular";
      });
      whatsappInput.addEventListener("blur", () => {
        if (String(celularInput.value || "").trim() !== "" && String(celularInput.value || "").trim() !== String(whatsappInput.value || "").trim()) {
          celularInput.dataset.cadMirroredFrom = "";
        }
      });
      celularInput.addEventListener("blur", () => {
        if (String(whatsappInput.value || "").trim() !== "" && String(whatsappInput.value || "").trim() !== String(celularInput.value || "").trim()) {
          whatsappInput.dataset.cadMirroredFrom = "";
        }
      });
    }

    bindManualEditProtection([
      enderecoInput,
      bairroInput,
      cidadeInput,
      estadoSelect,
      ...razaoSocialInputs,
      ...nomeFantasiaInputs,
      ...inscricaoEstadualInputs,
    ]);

    if (cepInput) {
      cepInput.addEventListener("input", () => scheduleLookup("cep", cepInput.value));
      cepInput.addEventListener("blur", () => scheduleLookup("cep", cepInput.value, true));
    }

    documentoInputs.forEach((input) => {
      input.addEventListener("input", () => {
        if (input.disabled) return;
        scheduleLookup("cnpj", input.value);
      });
      input.addEventListener("blur", () => {
        if (input.disabled) return;
        scheduleLookup("cnpj", input.value, true);
      });
    });

    if (tagAddButton) {
      tagAddButton.addEventListener("click", () => addTag(tagInput?.value || ""));
    }

    if (tagInput) {
      tagInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
          event.preventDefault();
          addTag(tagInput.value);
        }
      });
    }

    form.addEventListener("submit", () => {
      isSubmitting = true;
      clearDirty();
      if (tagInput && tagInput.value.trim() !== "") {
        addTag(tagInput.value);
      }
      renderTags();
    });

    if (addMotoristaButton) {
      addMotoristaButton.addEventListener("click", () => {
        if (currentType === "motorista" && motoristas.length >= 1) {
          toast("info", "O cadastro de motorista permite apenas um motorista secundário nesta etapa.");
          return;
        }
        motoristas.push({
          nome: "",
          cpf: "",
          cnh: "",
          contato: "",
          telefone_fixo: "",
          whatsapp: "",
          celular: "",
          email: "",
          principal: false,
        });
        renderMotoristas();
        markDirty();
      });
    }

      if (addVeiculoButton) {
      addVeiculoButton.addEventListener("click", () => {
        veiculos.push({
          modelo: "",
          placa: "",
          placa_adicional: "",
          tipo_carroceria: "",
          metragem: "",
          peso_carga: "",
        });
        renderVeiculos();
        markDirty();
      });
    }

    document.addEventListener("click", async (event) => {
      const link = event.target instanceof Element ? event.target.closest("a[href]") : null;
      if (!link) return;
      if (link.target === "_blank" || link.hasAttribute("download")) return;
      const href = link.getAttribute("href") || "";
      if (!href || href.startsWith("#") || href.startsWith("javascript:")) return;
      if (!isEmbed && (!isDirty || isSubmitting)) return;

      event.preventDefault();
      event.stopPropagation();

      const allowed = await confirmDiscard();
      if (allowed) {
        clearDirty();
        if (isEmbed) {
          postInlineState({ type: "sv:inline-cadastro-request-close" });
          return;
        }
        window.location.href = link.href;
      }
    }, true);

    renderTags();
    renderMotoristas();
    renderVeiculos();
    syncMotoristaButton();
    updateHero();
    updatePessoaFields();
    clearDirty();

    if (saved === "conversion_pending") {
      clearDirty();
      toast("info", `${currentTypeTitle} precisa ser concluído antes de seguir para outra conversão.`);
      highlightRequiredFields();
    } else if (hasAnyFormError()) {
      showValidationFeedback();
      highlightRequiredFields();
    } else if (saved === "created") {
      clearDirty();
      toast("success", "Cadastro criado com sucesso.");
    } else if (saved === "updated") {
      clearDirty();
      toast("success", "Cadastro atualizado com sucesso.");
    }

    if (saved) {
      const canonical = canonicalCadastroUrl();
      if (canonical) {
        window.history.replaceState({}, document.title, canonical);
      }
    }

    if (isEmbed && (saved === "created" || saved === "updated") && window.parent && window.parent !== window) {
      try {
        const payload = {
          id: Number(inlinePayload?.id || 0),
          tipo: String(inlinePayload?.tipo || currentType || ""),
          nome: String(inlinePayload?.nome || heroTitle?.textContent || ""),
          documento: String(inlinePayload?.documento || heroDocumento?.textContent || ""),
          celular: String(inlinePayload?.celular || form.querySelector('input[name="celular"]')?.value || form.querySelector('input[name="whatsapp"]')?.value || ""),
        };
        window.parent.postMessage({
          type: "sv:inline-cadastro-saved",
          cadastro: payload,
          saved,
        }, window.location.origin);
      } catch (_) {}
    }
  }

  initForm();
})();
