// app/static/js/lotes/lotes_dashboard.js
(function () {
  const FLASH_KEY = "lotes:dashboard:toast";
  const SCROLL_KEY = "lotes:dashboard:scroll:mural";
  const MOBILE_BREAKPOINT = 700;

  function appUrl(path) {
    return (typeof window.appUrl === "function") ? window.appUrl(path) : path;
  }

  function postPrintPreview(path, payload) {
    if (!payload) return;
    const form = document.createElement("form");
    form.method = "POST";
    form.target = "_blank";
    form.action = (typeof window.appUrl === "function")
      ? window.appUrl(path)
      : path;

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "payload";
    input.value = JSON.stringify(payload);
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    form.remove();
  }

  function parseJsonScript(id) {
    const node = document.getElementById(id);
    if (!node) return null;
    try {
      return JSON.parse(node.textContent || "");
    } catch (_error) {
      return null;
    }
  }

  function toast(kind, message) {
    try {
      if (window.Toast && typeof window.Toast[kind] === "function") {
        window.Toast[kind](message);
        return;
      }
      if (window.Toast && typeof window.Toast.show === "function") {
        window.Toast.show(message);
      }
    } catch (_) {}
  }

  function waitAndToast(kind, message, attempts = 0) {
    if (!message) return;

    const hasToast =
      (window.Toast && typeof window.Toast[kind] === "function") ||
      (window.Toast && typeof window.Toast.show === "function");

    if (hasToast) {
      toast(kind, message);
      return;
    }

    if (attempts >= 20) return;
    window.setTimeout(() => waitAndToast(kind, message, attempts + 1), 120);
  }

  function writeFlash(kind, message) {
    try {
      sessionStorage.setItem(FLASH_KEY, JSON.stringify({
        kind: String(kind || "show"),
        message: String(message || ""),
      }));
    } catch (_) {}
  }

  function markScrollToBoard() {
    try {
      sessionStorage.setItem(SCROLL_KEY, "1");
    } catch (_) {}
  }

  function consumeScrollToBoard() {
    try {
      const raw = sessionStorage.getItem(SCROLL_KEY);
      if (raw !== "1") return false;
      sessionStorage.removeItem(SCROLL_KEY);
      return true;
    } catch (_) {
      return false;
    }
  }

  function readFlash() {
    try {
      const raw = sessionStorage.getItem(FLASH_KEY);
      if (!raw) return null;
      sessionStorage.removeItem(FLASH_KEY);
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  }

  function normalize(value) {
    return String(value || "")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase()
      .trim();
  }

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function decimalInput(value) {
    const raw = String(value || "").trim();
    if (!raw) return 0;
    const cleaned = raw.replace(/[^\d,.-]/g, "");
    let normalized = cleaned;
    if (cleaned.includes(",") && cleaned.includes(".")) {
      normalized = cleaned.replace(/\./g, "").replace(",", ".");
    } else if (cleaned.includes(",")) {
      normalized = cleaned.replace(",", ".");
    }
    const number = Number(normalized);
    return Number.isFinite(number) ? number : 0;
  }

  function moneyBR(value) {
    const number = Number(value || 0);
    const safe = Number.isFinite(number) ? number : 0;
    return safe
      .toLocaleString("pt-BR", { style: "currency", currency: "BRL" })
      .replace(/\u00a0/g, " ");
  }

  function normalizeUpperTextValue(value) {
    return String(value || "").toLocaleUpperCase("pt-BR");
  }

  function onlyDigits(value) {
    return String(value || "").replace(/\D/g, "");
  }

  function formatTelefone(value) {
    const digits = onlyDigits(value).slice(0, 11);
    if (digits.length <= 2) return digits;
    if (digits.length <= 6) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
    if (digits.length <= 10) return `(${digits.slice(0, 2)}) ${digits.slice(2, 6)}-${digits.slice(6)}`;
    return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
  }

  function formatCpfCnpj(value) {
    const digits = onlyDigits(value);
    if (digits.length <= 11) {
      return digits
        .replace(/^(\d{3})(\d)/, "$1.$2")
        .replace(/^(\d{3})\.(\d{3})(\d)/, "$1.$2.$3")
        .replace(/\.(\d{3})(\d)/, ".$1-$2")
        .slice(0, 14);
    }
    return digits
      .slice(0, 14)
      .replace(/^(\d{2})(\d)/, "$1.$2")
      .replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3")
      .replace(/\.(\d{3})(\d)/, ".$1/$2")
      .replace(/(\d{4})(\d)/, "$1-$2");
  }

  function formatEstado(value) {
    return normalizeUpperTextValue(value).replace(/[^A-Z]/g, "").slice(0, 2);
  }

  function applyLotMask(input) {
    const kind = String(input.getAttribute("data-lot-mask") || "");
    if (kind === "telefone") {
      input.value = formatTelefone(input.value);
    } else if (kind === "documento") {
      input.value = formatCpfCnpj(input.value);
    } else if (kind === "estado") {
      input.value = formatEstado(input.value);
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

    if (field.hasAttribute("data-lot-mask")) {
      return false;
    }

    return true;
  }

  function bindUppercaseInputs(scope) {
    Array.from((scope || document).querySelectorAll("input, textarea")).forEach((field) => {
      if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement)) return;
      if (!shouldForceUppercase(field) || field.dataset.lotUpperBound === "1") return;
      field.dataset.lotUpperBound = "1";
      const transform = () => {
        field.value = normalizeUpperTextValue(field.value);
      };
      field.addEventListener("input", transform);
      field.addEventListener("blur", transform);
    });
  }

  function bindMaskedInputs(scope) {
    Array.from((scope || document).querySelectorAll("[data-lot-mask]")).forEach((field) => {
      if (!(field instanceof HTMLInputElement) || field.dataset.lotMaskBound === "1") return;
      field.dataset.lotMaskBound = "1";
      const sync = () => applyLotMask(field);
      sync();
      field.addEventListener("input", sync);
      field.addEventListener("blur", sync);
    });
  }

  function bindMoneyInputs(scope) {
    Array.from((scope || document).querySelectorAll("[data-lot-money]")).forEach((field) => {
      if (!(field instanceof HTMLInputElement) || field.dataset.lotMoneyBound === "1") return;
      field.dataset.lotMoneyBound = "1";

      field.addEventListener("focus", () => {
        const current = decimalInput(field.value);
        field.value = current > 0
          ? current.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
          : "";
      });

      field.addEventListener("blur", () => {
        field.value = moneyBR(decimalInput(field.value));
        field.dispatchEvent(new CustomEvent("lot:money-change"));
      });
    });
  }

  function qtyCompact(value) {
    const number = Number(value || 0);
    if (!Number.isFinite(number)) return "0";
    const formatted = number.toLocaleString("pt-BR", { minimumFractionDigits: 3, maximumFractionDigits: 3 });
    return formatted
      .replace(/,000$/, "")
      .replace(/(\,\d*[1-9])0+$/, "$1");
  }

  function activityDateTime(eventDate, createdAt) {
    const eventText = String(eventDate || "").trim();
    const createdText = String(createdAt || "").trim();
    const dateBase = eventText ? `${eventText.slice(0, 10)}T00:00:00` : createdText;
    const timeBase = createdText || eventText;
    const dateObj = dateBase ? new Date(dateBase) : null;
    const timeObj = timeBase ? new Date(timeBase) : null;
    const dateLabel = dateObj && !Number.isNaN(dateObj.getTime())
      ? dateObj.toLocaleDateString("pt-BR")
      : "Data não informada";
    const timeLabel = timeObj && !Number.isNaN(timeObj.getTime())
      ? timeObj.toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" })
      : "--:--";
    return `${dateLabel} • ${timeLabel}`;
  }

  function movementSummary(entry) {
    const payload = (entry && typeof entry === "object" && entry.payloadEstrutural && typeof entry.payloadEstrutural === "object")
      ? entry.payloadEstrutural
      : {};
    const item = String(payload.descricao_item || "").trim() || "Item";
    const fallback = String(entry?.descricaoEvento || "Evento sem descrição");
    const qtyLabel = String(payload.tipo_controle_item || "").trim() || "Und";

    switch (String(entry?.tipoEvento || "")) {
      case "item_cadastrado":
        return `Item cadastrado: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_total)}`;
      case "item_editado":
        return `Item editado: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_total)}`;
      case "item_baixa_manual":
        return `Baixa manual: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_baixada)}`;
      case "item_baixa_revertida":
        return `Reversão de baixa: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_revertida)}`;
      case "item_venda":
        return `Venda: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_vendida)}`;
      case "item_venda_devolucao":
        return `Devolução: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_devolvida)}`;
      case "lote_baixa_total_item":
        return `Baixa total: ${item} • ${qtyLabel} ${qtyCompact(payload.quantidade_baixada)}`;
      default:
        return fallback;
    }
  }

  function parseJsonAttr(node, attr, fallback = []) {
    if (!node) return fallback;
    try {
      const raw = node.getAttribute(attr) || "";
      return raw ? JSON.parse(raw) : fallback;
    } catch (_) {
      return fallback;
    }
  }

  function bindToasts() {
    document.querySelectorAll("[data-lot-toast]").forEach((el) => {
      if (el.dataset.lotToastBound === "1") return;
      el.dataset.lotToastBound = "1";

      el.addEventListener("click", () => {
        const message = el.getAttribute("data-lot-toast") || "";
        const kind = el.getAttribute("data-lot-toast-kind") || "info";
        if (!message) return;

        if (el.tagName === "A" && el.getAttribute("href")) {
          writeFlash(kind, message);
          if (el.classList.contains("lot-board__count--link") || el.classList.contains("lot-board__action-btn")) {
            markScrollToBoard();
          }
          return;
        }

        if (
          el.tagName === "BUTTON" &&
          String(el.getAttribute("type") || "").toLowerCase() === "submit"
        ) {
          writeFlash(kind, message);
          markScrollToBoard();
          return;
        }

        waitAndToast(kind, message);
      });
    });
  }

  function bindPrimarySearch() {
    const root = document.getElementById("lotPrimarySearch");
    const input = document.getElementById("lotPrimarySearchInput");
    const results = document.getElementById("lotPrimarySearchResults");
    if (!root || !input || !results) return;

    let source = [];
    try {
      source = JSON.parse(root.getAttribute("data-lot-search-source") || "[]");
    } catch (_) {
      source = [];
    }

    function clearResults() {
      results.innerHTML = "";
      results.hidden = true;
    }

    function renderResults(items) {
      if (!items.length) {
        results.innerHTML = `
          <div class="lot-search-suggest__item lot-search-suggest__item--empty" aria-live="polite">
            <span class="lot-search-suggest__icon"><i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i></span>
            <span class="lot-search-suggest__body">
              <strong>Nenhum lote encontrado.</strong>
              <span>Continue digitando ou revise o processo, título ou fornecedor informado.</span>
            </span>
          </div>
        `;
        results.hidden = false;
        return;
      }

      results.innerHTML = items.map((item) => `
        <a class="lot-search-suggest__item"
           href="${item.href}"
           data-lot-toast="Abrindo o processo ${item.numeroProcesso}."
           data-lot-toast-kind="info">
          <span class="lot-search-suggest__icon"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></span>
          <span class="lot-search-suggest__body">
            <strong>${item.numeroProcesso} • ${item.tituloLote}</strong>
            <span>${item.fornecedorNome} • ${item.statusLabel}</span>
          </span>
        </a>
      `).join("");
      results.hidden = false;
      bindToasts();
    }

    input.addEventListener("input", () => {
      const term = normalize(input.value);
      if (!term) {
        clearResults();
        return;
      }

      const filtered = source.filter((item) => String(item.searchIndex || "").includes(term)).slice(0, 6);
      renderResults(filtered);
    });

    input.addEventListener("blur", () => {
      window.setTimeout(clearResults, 160);
    });
  }

  function bindBoardFilter() {
    const input = document.getElementById("lotBoardFilterSearch");
    if (!input) return;
    const feedback = document.getElementById("lotBoardFilterFeedback");
    const cards = Array.from(document.querySelectorAll("[data-lot-card]"));

    function renderFeedback(term, hasMatch) {
      if (!feedback) return;

      if (!term || hasMatch) {
        feedback.innerHTML = "";
        feedback.hidden = true;
        return;
      }

      feedback.innerHTML = `
        <div class="lot-search-suggest__item lot-search-suggest__item--empty" aria-live="polite">
          <span class="lot-search-suggest__icon"><i class="fa-solid fa-filter-circle-xmark" aria-hidden="true"></i></span>
          <span class="lot-search-suggest__body">
            <strong>Nenhum lote encontrado.</strong>
            <span>Não localizamos esse termo nos lotes exibidos atualmente no mural.</span>
          </span>
        </div>
      `;
      feedback.hidden = false;
    }

    input.addEventListener("input", () => {
      const term = normalize(input.value);
      const hasMatch = !term || cards.some((card) => {
        const haystack = normalize(card.getAttribute("data-lot-search") || "");
        return haystack.includes(term);
      });

      renderFeedback(term, hasMatch);
      input.setCustomValidity("");
    });

    input.addEventListener("search", () => {
      const term = normalize(input.value);
      const hasMatch = !term || cards.some((card) => {
        const haystack = normalize(card.getAttribute("data-lot-search") || "");
        return haystack.includes(term);
      });

      renderFeedback(term, hasMatch);
    });
  }

  function bindAdvancedFilters() {
    const panel = document.getElementById("lotFilterAdvanced");
    const form = document.querySelector("[data-lot-filter-form]");
    if (!panel || !form) return;

    function syncPanelState() {
      const isMobile = window.innerWidth <= MOBILE_BREAKPOINT;
      if (isMobile) {
        panel.setAttribute("hidden", "");
      } else {
        panel.removeAttribute("hidden");
      }
    }

    syncPanelState();

    form.addEventListener("submit", () => {
      markScrollToBoard();
    });

    window.addEventListener("resize", syncPanelState);
  }

  function applyMobileCollapseState() {
    const isMobile = window.innerWidth <= MOBILE_BREAKPOINT;

    document.querySelectorAll(".lot-mobile-collapsible").forEach((section) => {
      if (isMobile) {
        section.classList.add("is-collapsed-mobile");
      } else {
        section.classList.remove("is-collapsed-mobile");
      }
      const toggle = section.querySelector("[data-lot-mobile-toggle]");
      if (toggle) {
        toggle.setAttribute("aria-expanded", String(!section.classList.contains("is-collapsed-mobile")));
      }
    });

    document.querySelectorAll("[data-lot-card]").forEach((card) => {
      if (isMobile) {
        card.classList.add("is-collapsed-mobile");
      } else {
        card.classList.remove("is-collapsed-mobile");
      }
      const toggle = card.querySelector("[data-lot-card-toggle]");
      if (toggle) {
        toggle.setAttribute("aria-expanded", String(!card.classList.contains("is-collapsed-mobile")));
      }
    });
  }

  function bindMobileToggles() {
    document.querySelectorAll("[data-lot-mobile-toggle]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const section = btn.closest(".lot-mobile-collapsible");
        if (!section) return;
        section.classList.toggle("is-collapsed-mobile");
        btn.setAttribute("aria-expanded", String(!section.classList.contains("is-collapsed-mobile")));
      });
    });

    document.querySelectorAll("[data-lot-card-toggle]").forEach((btn) => {
      btn.addEventListener("click", () => {
        const card = btn.closest("[data-lot-card]");
        if (!card) return;
        card.classList.toggle("is-collapsed-mobile");
        btn.setAttribute("aria-expanded", String(!card.classList.contains("is-collapsed-mobile")));
      });
    });
  }

  function bindTimelineModal() {
    const modal = document.getElementById("lotTimelineModal");
    const title = document.getElementById("lotTimelineModalTitle");
    const hint = document.getElementById("lotTimelineModalHint");
    const badge = document.getElementById("lotTimelineStageBadge");
    const form = document.getElementById("lotTimelineForm");
    const stageInput = document.getElementById("lotTimelineStageInput");
    const submitModeInput = document.getElementById("lotTimelineSubmitMode");
    const recordIdInput = document.getElementById("lotTimelineRecordId");
    const dateInput = document.getElementById("lotTimelineDateInput");
    const dateLabel = document.getElementById("lotTimelineDateLabel");
    const contactField = document.getElementById("lotTimelineContactField");
    const contactLabel = document.getElementById("lotTimelineContactLabel");
    const contactInput = document.getElementById("lotTimelineContactInput");
    const expectedDeliveryField = document.getElementById("lotTimelineExpectedDeliveryField");
    const expectedDeliveryInput = document.getElementById("lotTimelineExpectedDeliveryInput");
    const reportLabel = document.getElementById("lotTimelineReportLabel");
    const description = document.getElementById("lotTimelineDescription");
    const freightAlert = document.getElementById("lotTimelineFreightAlert");
    const freightForceField = document.getElementById("lotTimelineFreightForceField");
    const freightForceInput = document.getElementById("lotTimelineForceWithoutFreight");
    const historyList = document.getElementById("lotTimelineHistoryList");
    const historyEmpty = document.getElementById("lotTimelineHistoryEmpty");
    const submitButton = document.getElementById("lotTimelineSubmitButton");
    const finalizeButton = document.getElementById("lotTimelineFinalizeButton");
    const reopenButton = document.getElementById("lotTimelineReopenButton");
    const closeBtn = document.getElementById("lotTimelineModalClose");
    const cancelBtn = document.getElementById("lotTimelineModalCancel");
    const triggers = Array.from(document.querySelectorAll("[data-lot-timeline-trigger]"));
    const page = document.querySelector(".lot-page");
    const needsFreightAlert = page?.getAttribute("data-lot-timeline-needs-freight") === "1";
    if (!modal || !title || !hint || !badge || !form || !stageInput || !submitModeInput || !recordIdInput || !dateInput || !dateLabel || !contactField || !contactLabel || !contactInput || !expectedDeliveryField || !expectedDeliveryInput || !reportLabel || !description || !historyList || !historyEmpty || !submitButton || !finalizeButton || !reopenButton || !closeBtn || !cancelBtn || !triggers.length) {
      return;
    }
    bindUppercaseInputs(modal);

    const stageConfigs = {
      autorizacao_coleta: {
        title: "Autorização de coleta",
        hint: "Registre as cobranças e retornos da seguradora até o recebimento do documento de autorização.",
        dateLabel: "Data do contato",
        contactLabel: "Contato",
        contactRequired: true,
        finalStatus: "autorizado",
        reportLabel: "Relato",
        reportPlaceholder: "Descreva a cobrança feita, o retorno recebido ou qualquer observação desta autorização.",
      },
      liberacao_coleta: {
        title: "Liberação de coleta",
        hint: "Registre as tratativas com o local de armazenagem até a confirmação da liberação da retirada.",
        dateLabel: "Data do contato",
        contactLabel: "Contato",
        contactRequired: true,
        finalStatus: "liberado",
        reportLabel: "Relato",
        reportPlaceholder: "Descreva o contato com o local, a tratativa em andamento ou a confirmação da liberação.",
      },
      coleta: {
        title: "Coleta",
        hint: "Registre a busca por frete, as observações operacionais e a confirmação da coleta.",
        dateLabel: "Data do registro",
        contactLabel: "",
        contactRequired: false,
        finalStatus: "coletado",
        reportLabel: "Relato",
        reportPlaceholder: "Descreva a busca por frete, a dificuldade encontrada ou a confirmação da coleta.",
      },
      entrega: {
        title: "Entrega",
        hint: "Registre ocorrências de transporte, atrasos ou a confirmação da entrega do lote.",
        dateLabel: "Data do registro",
        contactLabel: "",
        contactRequired: false,
        finalStatus: "entregue",
        reportLabel: "Relato",
        reportPlaceholder: "Descreva o andamento da entrega, atrasos ou a confirmação do recebimento.",
      },
    };

    const formInputs = [dateInput, contactInput, description, freightForceInput].filter(Boolean);
    let currentMode = "active";

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
      submitModeInput.value = "save";
      recordIdInput.value = "0";
      reopenButton.hidden = true;
    }

    function todayLocalIso() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, "0");
      const day = String(now.getDate()).padStart(2, "0");
      return `${year}-${month}-${day}`;
    }

    function parseHistory(trigger) {
      try {
        const parsed = JSON.parse(trigger?.getAttribute("data-stage-history") || "[]");
        return Array.isArray(parsed) ? parsed : [];
      } catch (_) {
        return [];
      }
    }

    function renderHistory(items) {
      if (!items.length) {
        historyList.innerHTML = "";
        historyList.hidden = true;
        historyEmpty.hidden = false;
        return;
      }

      historyList.innerHTML = items.map((item) => {
        const meta = [item.date || "", item.contact || "", item.responsavel || ""]
          .filter(Boolean)
          .map((value) => escapeHtml(value))
          .join(" • ");
        return `
          <article class="lot-timeline-list__item">
            <div class="lot-timeline-list__dot" aria-hidden="true"></div>
            <div class="lot-timeline-list__content">
              <div class="lot-timeline-list__row">
                <div class="lot-timeline-list__summary">
                  <strong>${escapeHtml(item.report || "Registro da etapa")}</strong>
                  <span>${meta}</span>
                </div>
                ${item.canEdit ? `
                  <div class="lot-timeline-list__actions">
                    <button class="fin-icon-btn fin-icon-btn--sm lot-timeline-list__icon" type="button" data-lot-timeline-edit='${escapeHtml(JSON.stringify(item))}' aria-label="Editar registro" data-tip="Editar registro">
                      <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    </button>
                    <button class="fin-icon-btn fin-icon-btn--sm lot-timeline-list__icon" type="button" data-lot-timeline-delete='${escapeHtml(JSON.stringify(item))}' aria-label="Excluir registro" data-tip="Excluir registro">
                      <i class="fa-solid fa-trash" aria-hidden="true"></i>
                    </button>
                  </div>
                ` : ""}
              </div>
            </div>
          </article>
        `;
      }).join("");
      historyList.hidden = false;
      historyEmpty.hidden = true;
    }

    function syncStageForm(mode = "active") {
      const stageKey = stageInput.value;
      const config = stageConfigs[stageKey] || stageConfigs.autorizacao_coleta;
      const isReadOnly = mode !== "active";
      const isColetaStage = stageKey === "coleta";
      const showFreightWarning = Boolean(!isReadOnly && submitModeInput.value === "finalize" && stageKey === "coleta" && needsFreightAlert);

      title.textContent = config.title;
      hint.textContent = config.hint;
      dateLabel.textContent = config.dateLabel;
      contactLabel.textContent = config.contactLabel || "Contato";
      contactField.hidden = !config.contactRequired && config.contactLabel === "";
      contactInput.required = Boolean(config.contactRequired);
      if (contactField.hidden) {
        contactInput.value = "";
      }
      expectedDeliveryField.hidden = !isColetaStage;
      expectedDeliveryField.style.display = isColetaStage ? "" : "none";
      expectedDeliveryInput.required = isColetaStage && !isReadOnly;
      expectedDeliveryInput.disabled = !isColetaStage || isReadOnly;
      expectedDeliveryInput.readOnly = !isColetaStage || isReadOnly;
      if (!isColetaStage) {
        expectedDeliveryInput.value = "";
      }
      reportLabel.textContent = config.reportLabel;
      description.placeholder = config.reportPlaceholder;
      if (freightAlert) freightAlert.hidden = !showFreightWarning;
      if (freightForceField) freightForceField.hidden = !showFreightWarning;
      if (freightAlert) freightAlert.style.display = showFreightWarning ? "" : "none";
      if (freightForceField) freightForceField.style.display = showFreightWarning ? "" : "none";
      if (!showFreightWarning && freightForceInput) {
        freightForceInput.checked = false;
      }
      formInputs.forEach((input) => {
        if (!input) return;
        input.disabled = isReadOnly;
      });
      description.readOnly = isReadOnly;
      contactInput.readOnly = isReadOnly;
      expectedDeliveryInput.disabled = !isColetaStage || isReadOnly;
      expectedDeliveryInput.readOnly = !isColetaStage || isReadOnly;
      submitButton.hidden = isReadOnly;
      finalizeButton.hidden = isReadOnly;
      reopenButton.hidden = mode !== "review";
      cancelBtn.textContent = isReadOnly ? "Fechar" : "Cancelar";
      submitButton.textContent = submitModeInput.value === "update" ? "Salvar edição" : "Salvar registro";
      finalizeButton.textContent = "Finalizar etapa";
    }

    function resetEditableState() {
      submitModeInput.value = "save";
      recordIdInput.value = "0";
    }

    const timelineOldState = parseJsonAttr(form, "data-lot-timeline-old", {}) || {};

    function fillRecordForEdit(item) {
      if (!item) return;
      currentMode = "active";
      submitModeInput.value = "update";
      recordIdInput.value = String(item.id || 0);
      dateInput.value = item.rawDate || todayLocalIso();
      contactInput.value = item.rawContact || "";
      expectedDeliveryInput.value = item.expectedDelivery || "";
      description.value = item.rawReport || "";
      syncStageForm(currentMode);
    }

    function submitWithMode(mode) {
      submitModeInput.value = mode;
      if (mode === "delete" || mode === "reopen") {
        form.submit();
        return;
      }
      if (mode === "save" || mode === "update") {
        form.submit();
        return;
      }
      if (mode === "finalize") {
        if (typeof form.reportValidity === "function" && !form.reportValidity()) {
          return;
        }
        form.submit();
        return;
      }
      form.requestSubmit();
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const stageKey = trigger.getAttribute("data-stage-key") || "";
        const stageLabel = trigger.getAttribute("data-stage-label") || "Etapa";
        const stageIcon = trigger.getAttribute("data-stage-icon") || "fa-solid fa-road";
        const mode = trigger.getAttribute("data-stage-mode") || "active";
        const config = stageConfigs[stageKey] || stageConfigs.autorizacao_coleta;
        const historyItems = parseHistory(trigger);
        const expectedDelivery = trigger.getAttribute("data-stage-expected-delivery") || "";
        const hasOldStateForStage = stageKey === String(timelineOldState.timeline_stage || "") && String(timelineOldState.timeline_submit_mode || "") !== "";
        currentMode = hasOldStateForStage ? "active" : mode;

        stageInput.value = stageKey;
        badge.innerHTML = `<i class="${stageIcon}" aria-hidden="true"></i><span>${stageLabel}</span>`;
        resetEditableState();
        dateInput.value = stageKey === String(timelineOldState.timeline_stage || "") ? String(timelineOldState.data_evento || todayLocalIso()) : todayLocalIso();
        contactInput.value = stageKey === String(timelineOldState.timeline_stage || "") ? String(timelineOldState.timeline_contact || "") : "";
        expectedDeliveryInput.value = stageKey === String(timelineOldState.timeline_stage || "") ? String(timelineOldState.timeline_expected_delivery || expectedDelivery) : expectedDelivery;
        description.value = stageKey === String(timelineOldState.timeline_stage || "") ? String(timelineOldState.descricao_evento || "") : "";
        if (stageKey === String(timelineOldState.timeline_stage || "") && timelineOldState.timeline_record_id) {
          submitModeInput.value = String(timelineOldState.timeline_submit_mode || "save");
          recordIdInput.value = String(timelineOldState.timeline_record_id || "0");
        }
        if (freightForceInput) freightForceInput.checked = false;
        renderHistory(historyItems);
        syncStageForm(currentMode);

        modal.setAttribute("aria-hidden", "false");
        modal.classList.add("is-open");
      });
    });

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    submitButton.addEventListener("click", () => {
      submitWithMode(submitModeInput.value === "update" ? "update" : "save");
    });
    finalizeButton.addEventListener("click", async () => {
      if (currentMode !== "active") return;
      if (window.UIComponents?.confirm) {
        const confirmed = await window.UIComponents.confirm({
          eyebrow: "Finalizar etapa",
          title: "Confirmar a conclusão desta etapa?",
          message: "A etapa será concluída e a próxima será liberada no fluxo operacional.",
          confirmLabel: "Finalizar etapa",
          cancelLabel: "Revisar",
        });
        if (!confirmed) return;
      }
      submitWithMode("finalize");
    });
    reopenButton.addEventListener("click", async () => {
      if (window.UIComponents?.confirm) {
        const confirmed = await window.UIComponents.confirm({
          eyebrow: "Reativar etapa",
          title: "Voltar o lote para esta etapa?",
          message: "As etapas posteriores voltarão para pendentes, mas todos os registros já lançados serão preservados.",
          confirmLabel: "Reativar etapa",
          cancelLabel: "Cancelar",
        });
        if (!confirmed) return;
      }
      submitWithMode("reopen");
    });
    historyList.addEventListener("click", (event) => {
      const editBtn = event.target instanceof Element ? event.target.closest("[data-lot-timeline-edit]") : null;
      const deleteBtnLocal = event.target instanceof Element ? event.target.closest("[data-lot-timeline-delete]") : null;
      if (editBtn) {
        try {
          fillRecordForEdit(JSON.parse(editBtn.getAttribute("data-lot-timeline-edit") || "{}"));
        } catch (_) {}
      }
      if (deleteBtnLocal) {
        (async () => {
          try {
            const item = JSON.parse(deleteBtnLocal.getAttribute("data-lot-timeline-delete") || "{}");
            fillRecordForEdit(item);
            if (window.UIComponents?.confirm) {
              const confirmed = await window.UIComponents.confirm({
                eyebrow: "Excluir registro",
                title: "Remover este lançamento da etapa?",
                message: "Esse registro será excluído da lista desta etapa.",
                confirmLabel: "Excluir registro",
                cancelLabel: "Cancelar",
              });
              if (!confirmed) return;
            }
            submitWithMode("delete");
          } catch (_) {}
        })();
      }
    });
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    return {
      openStage(stageKey, mode = "active") {
        const trigger = triggers.find((item) => item.getAttribute("data-stage-key") === stageKey && item.getAttribute("data-stage-mode") === mode)
          || triggers.find((item) => item.getAttribute("data-stage-key") === stageKey);
        trigger?.click();
      },
    };
  }

  function bindLotItemForm() {
    const form = document.getElementById("lotItemForm");
    if (!form) return;
    const modal = document.getElementById("lotItemManageModal");
    const modalTitle = document.getElementById("lotItemManageTitle");
    const openButtons = Array.from(document.querySelectorAll("#lotItemManageOpenSection"));
    const closeButton = document.getElementById("lotItemManageClose");

    const idInput = document.getElementById("lotItemIdInput");
    const descricaoInput = document.getElementById("lotItemDescricaoInput");
    const tipoInput = document.getElementById("lotItemTipoInput");
    const quantidadeInput = document.getElementById("lotItemQuantidadeInput");
    const baseInput = document.getElementById("lotItemBaseInput");
    const vendaInput = document.getElementById("lotItemVendaInput");
    const observacoesInput = document.getElementById("lotItemObservacoesInput");
    const imagesInput = document.getElementById("lotItemImagesInput");
    const currentImages = document.getElementById("lotItemCurrentImages");
    const selectedImages = document.getElementById("lotItemSelectedImages");
    const removeRelations = document.getElementById("lotItemRemoveRelations");
    const totalPreview = document.getElementById("lotItemTotalPreview");
    const submitButton = document.getElementById("lotItemSubmitButton");
    const cancelButton = document.getElementById("lotItemCancelEdit");
    const editButtons = Array.from(document.querySelectorAll("[data-lot-item-edit]"));
    const printButton = document.getElementById("lotPrintListButton");
    let removedRelations = new Set();

    function closeModal() {
      if (!modal) return;
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    function openModal(titleText = "Cadastro de item") {
      if (modalTitle) modalTitle.textContent = titleText;
      if (!modal) return;
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
    }

    function parseImages(button) {
      try {
        const value = button?.getAttribute("data-item-images") || "[]";
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed : [];
      } catch (_) {
        return [];
      }
    }

    function syncRemoveInputs() {
      if (!removeRelations) return;
      removeRelations.innerHTML = Array.from(removedRelations).map((relationId) => (
        `<input type="hidden" name="item_remove_attachment_ids[]" value="${relationId}">`
      )).join("");
    }

    function renderCurrentImages(images) {
      if (!currentImages) return;
      const visibleImages = images.filter((image) => !removedRelations.has(Number(image.relacaoId || 0)));
      if (!visibleImages.length) {
        currentImages.innerHTML = `
          <div class="lot-inline-empty lot-inline-empty--compact">
            Nenhuma imagem vinculada a este item no momento.
          </div>
        `;
        syncRemoveInputs();
        return;
      }

      currentImages.innerHTML = visibleImages.map((image) => {
        const relationId = Number(image.relacaoId || 0);
        const name = String(image.displayName || image.nomeOriginal || "Imagem do produto");
        const thumb = String(image.thumbUrl || image.previewUrl || "");
        return `
          <article class="lot-item-media-editor__card">
            <div class="lot-item-media-editor__thumb">
              ${thumb !== "" ? `<img src="${thumb}" alt="${name}">` : `<span>${name}</span>`}
              <button class="sv-attachments__remove lot-item-media-editor__remove" type="button" data-remove-relation="${relationId}" aria-label="Remover imagem">
                <i class="fa-solid fa-trash" aria-hidden="true"></i>
              </button>
            </div>
          </article>
        `;
      }).join("");

      currentImages.querySelectorAll("[data-remove-relation]").forEach((button) => {
        button.addEventListener("click", () => {
          const relationId = Number(button.getAttribute("data-remove-relation") || 0);
          if (relationId <= 0) return;
          removedRelations.add(relationId);
          renderCurrentImages(images);
        });
      });

      syncRemoveInputs();
    }

    function renderSelectedFiles() {
      if (!selectedImages) return;
      const files = Array.from(imagesInput?.files || []);
      if (!files.length) {
        selectedImages.textContent = "Nenhuma nova imagem foi selecionada ainda.";
        return;
      }

      selectedImages.innerHTML = files.map((file) => (
        `<span class="lot-item-media-editor__file">${file.name}</span>`
      )).join("");
    }

    function syncTotal() {
      if (!totalPreview) return;
      const quantidade = Number(String(quantidadeInput?.value || "0").replace(",", ".")) || 0;
      const venda = decimalInput(vendaInput?.value || "0");
      totalPreview.value = moneyBR(quantidade * venda);
    }

    function resetForm() {
      if (idInput) idInput.value = "";
      if (descricaoInput) descricaoInput.value = "";
      if (tipoInput) tipoInput.value = "unidade";
      if (quantidadeInput) quantidadeInput.value = "";
      if (baseInput) baseInput.value = "";
      if (vendaInput) vendaInput.value = "";
      if (observacoesInput) observacoesInput.value = "";
      if (imagesInput) imagesInput.value = "";
      removedRelations = new Set();
      renderCurrentImages([]);
      renderSelectedFiles();
      if (submitButton) submitButton.textContent = "Adicionar item";
      if (modalTitle) modalTitle.textContent = "Cadastro de item";
      syncTotal();
    }

    [quantidadeInput, vendaInput].forEach((field) => {
      field?.addEventListener("input", syncTotal);
    });

    bindMoneyInputs(form);
    bindUppercaseInputs(form);
    [baseInput, vendaInput].forEach((field) => {
      field?.addEventListener("lot:money-change", syncTotal);
    });

    editButtons.forEach((button) => {
      button.addEventListener("click", () => {
        if (idInput) idInput.value = button.getAttribute("data-item-id") || "";
        if (descricaoInput) descricaoInput.value = button.getAttribute("data-item-descricao") || "";
        if (tipoInput) tipoInput.value = button.getAttribute("data-item-tipo") || "unidade";
        if (quantidadeInput) quantidadeInput.value = button.getAttribute("data-item-quantidade") || "";
        if (baseInput) baseInput.value = moneyBR(button.getAttribute("data-item-base") || 0);
        if (vendaInput) vendaInput.value = moneyBR(button.getAttribute("data-item-venda") || 0);
        if (observacoesInput) observacoesInput.value = button.getAttribute("data-item-observacoes") || "";
        if (imagesInput) imagesInput.value = "";
        removedRelations = new Set();
        renderCurrentImages(parseImages(button));
        renderSelectedFiles();
        if (submitButton) submitButton.textContent = "Salvar edição";
        if (modalTitle) {
          modalTitle.textContent = `Editar item • ${button.getAttribute("data-item-descricao") || "Produto"}`;
        }
        syncTotal();
        openModal(modalTitle?.textContent || "Editar item");
      });
    });

    openButtons.forEach((button) => {
      button.addEventListener("click", () => {
        resetForm();
        openModal("Cadastro de item");
      });
    });

    imagesInput?.addEventListener("change", renderSelectedFiles);
    cancelButton?.addEventListener("click", resetForm);
    closeButton?.addEventListener("click", closeModal);
    modal?.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
    printButton?.addEventListener("click", () => {
      const payload = parseJsonScript("lotPrintListPayload");
      postPrintPreview("/app/templates/lotes_print_preview.php", payload);
    });
    renderCurrentImages([]);
    renderSelectedFiles();
    syncTotal();

    return {
      openModal,
      closeModal,
      resetForm,
    };
  }

  function bindLotDetailEditModal() {
    const modal = document.getElementById("lotDetailEditModal");
    const openButtons = Array.from(document.querySelectorAll("[data-lot-detail-edit-open]"));
    const closeButton = document.getElementById("lotDetailEditClose");
    if (!modal || !closeButton || !openButtons.length) return null;

    function openModal() {
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    openButtons.forEach((button) => {
      button.addEventListener("click", openModal);
    });
    closeButton.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    return { openModal, closeModal };
  }

  function bindLotPanel() {
    const printButtons = Array.from(document.querySelectorAll("#lotPrintButton, [data-lot-print-main]"));
    const printListButtons = Array.from(document.querySelectorAll("#lotPrintListButtonPanel, [data-lot-print-items]"));
    const printSalesButtons = Array.from(document.querySelectorAll("#lotPrintSalesButtonPanel, [data-lot-print-sales]"));
    const cancelButtons = Array.from(document.querySelectorAll("#lotCancelButton, [data-lot-cancel-open]"));
    const cancelModal = document.getElementById("lotCancelModal");
    const cancelClose = document.getElementById("lotCancelModalClose");
    const cancelDismiss = document.getElementById("lotCancelModalDismiss");
    const cancelForm = document.getElementById("lotCancelForm");
    const cancelKindInput = document.getElementById("lotCancelKindInput");
    const cancelRecordId = document.getElementById("lotCancelRecordId");
    const cancelKindOptions = Array.from(document.querySelectorAll("[data-lot-cancel-kind-option]"));
    const cancelKindRadios = Array.from(document.querySelectorAll("input[name=\"cancel_kind_choice\"]"));
    const cancelRefundDueDate = document.getElementById("lotCancelRefundDueDate");
    const cancelTitle = document.getElementById("lotCancelModalTitle");
    const cancelHeroEyebrow = document.getElementById("lotCancelHeroEyebrow");
    const cancelHeroText = document.getElementById("lotCancelHeroText");
    const cancelSubmitButton = document.getElementById("lotCancelSubmitButton");
    const cancelReason = cancelForm?.querySelector("input[name=\"cancel_motivo\"]");
    const cancelAmount = cancelForm?.querySelector("input[name=\"cancel_estorno\"]");
    const cancelStatusField = document.getElementById("lotCancelStatusField");
    const cancelStatusSelect = document.getElementById("lotCancelStatusSelect");
    const cancelFinance = cancelForm?.querySelector("textarea[name=\"cancel_financeiro\"]");
    const cancelDate = cancelForm?.querySelector("input[name=\"cancel_data\"]");
    const cancelReport = cancelForm?.querySelector("textarea[name=\"cancel_relato\"]");
    const cancelEditButtons = Array.from(document.querySelectorAll("[data-lot-cancel-edit]"));
    const occurrenceReports = parseJsonScript("lotOccurrenceReportsPayload");
    const occurrencePrintButtons = Array.from(document.querySelectorAll("[data-lot-occurrence-print]"));
    const cancelAttachmentsInput = document.getElementById("lotCancelAttachmentsInput");
    const cancelSelectedFilesWrap = document.getElementById("lotCancelSelectedFiles");
    const cancelSelectedFilesGrid = document.getElementById("lotCancelSelectedFilesGrid");
    const cancelSavedFilesGrid = document.getElementById("lotCancelSavedFilesGrid");
    const cancelSavedAttachments = parseJsonScript("lotCancelAttachmentsPayload") || [];

    bindMoneyInputs(cancelForm);
    bindUppercaseInputs(cancelForm);

    function syncCancelKind(kind) {
      const nextKind = kind === "parcial" ? "parcial" : "total";

      if (cancelKindInput instanceof HTMLInputElement) {
        cancelKindInput.value = nextKind;
      }

      cancelKindOptions.forEach((option) => {
        if (!(option instanceof HTMLElement)) return;
        option.classList.toggle("is-active", option.getAttribute("data-lot-cancel-kind-option") === nextKind);
      });

      cancelKindRadios.forEach((radio) => {
        if (!(radio instanceof HTMLInputElement)) return;
        radio.checked = radio.value === nextKind;
      });

      if (cancelTitle instanceof HTMLElement) {
        cancelTitle.textContent = nextKind === "parcial" ? "Devolução parcial do lote" : "Cancelamento do lote";
      }
      if (cancelHeroEyebrow instanceof HTMLElement) {
        cancelHeroEyebrow.textContent = nextKind === "parcial" ? "Ocorrência parcial" : "Interrupção total do lote";
      }
      if (cancelHeroText instanceof HTMLElement) {
        cancelHeroText.textContent = nextKind === "parcial"
          ? "Registre a devolução parcial ligada a este lote, mantendo o processo ativo e documentando o valor que deverá retornar depois."
          : "Use este painel para registrar a interrupção definitiva deste lote, preservando motivo, relato, devolução e documentos de apoio.";
      }
      if (cancelSubmitButton instanceof HTMLElement) {
        const isEditing = cancelRecordId instanceof HTMLInputElement && cancelRecordId.value !== "0";
        cancelSubmitButton.textContent = isEditing
          ? "Salvar ocorrência"
          : (nextKind === "parcial" ? "Registrar devolução parcial" : "Confirmar cancelamento");
      }
      if (cancelReason instanceof HTMLInputElement) {
        cancelReason.placeholder = nextKind === "parcial" ? "Ex.: FALTA DE ITENS NO PÁTIO" : "Ex.: DESISTÊNCIA DO SEGURADO";
      }
      if (cancelAmount instanceof HTMLInputElement) {
        cancelAmount.required = nextKind === "parcial";
        cancelAmount.placeholder = nextKind === "parcial" ? "R$ 0,00" : "R$ 0,00";
      }
      if (cancelStatusField instanceof HTMLElement) {
        cancelStatusField.hidden = nextKind !== "total";
      }
      if (cancelStatusSelect instanceof HTMLSelectElement) {
        cancelStatusSelect.disabled = nextKind !== "total";
        if (nextKind !== "total") {
          cancelStatusSelect.value = "";
        } else if (!cancelStatusSelect.value) {
          cancelStatusSelect.value = "cancelado_sem_pagamento";
        }
      }
      if (cancelFinance instanceof HTMLTextAreaElement) {
        cancelFinance.placeholder = nextKind === "parcial"
          ? "Use este campo para registrar como essa devolução parcial será tratada financeiramente."
          : "Use este campo para registrar estorno, tratativa financeira ou outra repercussão econômica deste cancelamento.";
      }
    }

    function resetCancelForm() {
      if (!(cancelForm instanceof HTMLFormElement)) return;
      cancelForm.reset();
      if (cancelRecordId instanceof HTMLInputElement) cancelRecordId.value = "0";
      if (cancelDate instanceof HTMLInputElement && !cancelDate.value) {
        cancelDate.value = new Date().toISOString().slice(0, 10);
      }
      if (cancelStatusSelect instanceof HTMLSelectElement) {
        cancelStatusSelect.value = "cancelado_sem_pagamento";
      }
      renderCancelSelectedFiles();
      syncCancelKind("total");
    }

    function normalizeViewerItem(item) {
      return {
        name: String(item?.displayName || item?.name || "Arquivo"),
        previewUrl: String(item?.previewUrl || ""),
        downloadUrl: String(item?.downloadUrl || ""),
        isImage: Boolean(item?.isImage),
        isPdf: Boolean(item?.isPdf),
        extension: String(item?.extensao || item?.extension || ""),
      };
    }

    function fileThumbMarkup(file, index) {
      const isImage = file.type.startsWith("image/");
      const isPdf = file.type === "application/pdf";
      const thumb = (isImage || isPdf) ? URL.createObjectURL(file) : "";
      return `
        <article class="sv-attachments__item lot-cancel-attachments__item">
          <button type="button" class="sv-attachments__thumb" data-lot-cancel-selected-preview="${index}">
            ${isImage
              ? `<img src="${escapeHtml(thumb)}" alt="${escapeHtml(file.name)}">`
              : `<span class="sv-attachments__thumbicon"><i class="${isPdf ? "fa-regular fa-file-pdf" : "fa-regular fa-file-lines"}" aria-hidden="true"></i></span>`}
          </button>
          <div class="sv-attachments__meta">
            <div class="sv-attachments__name">${escapeHtml(file.name)}</div>
            <div class="sv-attachments__inforow">
              <span class="sv-attachments__info sv-attachments__infoitem">${escapeHtml(isImage ? "Imagem" : (isPdf ? "PDF" : "Documento"))}</span>
            </div>
          </div>
        </article>
      `;
    }

    function renderCancelSelectedFiles() {
      if (!(cancelAttachmentsInput instanceof HTMLInputElement) || !(cancelSelectedFilesWrap instanceof HTMLElement) || !(cancelSelectedFilesGrid instanceof HTMLElement)) {
        return;
      }
      const files = Array.from(cancelAttachmentsInput.files || []);
      if (files.length === 0) {
        cancelSelectedFilesGrid.innerHTML = "";
        cancelSelectedFilesWrap.hidden = true;
        return;
      }
      cancelSelectedFilesGrid.innerHTML = files.map((file, index) => fileThumbMarkup(file, index)).join("");
      cancelSelectedFilesWrap.hidden = false;
    }

    function openCancelSelectedPreview(index) {
      if (!window.AttachmentsUI || typeof window.AttachmentsUI.openViewer !== "function" || !(cancelAttachmentsInput instanceof HTMLInputElement)) return;
      const files = Array.from(cancelAttachmentsInput.files || []);
      const items = files.map((file) => ({
        name: String(file.name || "Arquivo"),
        previewUrl: (file.type.startsWith("image/") || file.type === "application/pdf") ? URL.createObjectURL(file) : "",
        downloadUrl: "",
        isImage: file.type.startsWith("image/"),
        isPdf: file.type === "application/pdf",
        extension: String(file.name || "").split(".").pop() || "",
      }));
      if (!items[index]) return;
      window.AttachmentsUI.openViewer(items, index);
    }

    function openCancelSavedPreview(index) {
      if (!window.AttachmentsUI || typeof window.AttachmentsUI.openViewer !== "function") return;
      const items = Array.isArray(cancelSavedAttachments) ? cancelSavedAttachments.map(normalizeViewerItem) : [];
      if (!items[index]) return;
      window.AttachmentsUI.openViewer(items, index);
    }

    function openCancelModal() {
      if (!(cancelModal instanceof HTMLElement)) return;
      syncCancelKind(cancelKindInput instanceof HTMLInputElement ? cancelKindInput.value : "total");
      cancelModal.classList.add("is-open");
      cancelModal.setAttribute("aria-hidden", "false");
      document.body.classList.add("modal-open");
    }

    function closeCancelModal() {
      if (!(cancelModal instanceof HTMLElement)) return;
      cancelModal.classList.remove("is-open");
      cancelModal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("modal-open");
    }

    function bindCardLikeAction(element, handler) {
      if (!(element instanceof HTMLElement) || typeof handler !== "function") return;
      element.addEventListener("click", handler);
      element.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          handler();
        }
      });
    }

    printButtons.forEach((button) => {
      bindCardLikeAction(button, () => {
        const payload = parseJsonScript("lotPrintPayload");
        postPrintPreview("/app/templates/lotes_print_preview.php", payload);
      });
    });
    printListButtons.forEach((button) => {
      bindCardLikeAction(button, () => {
        const payload = parseJsonScript("lotPrintListPayload");
        postPrintPreview("/app/templates/lotes_print_preview.php", payload);
      });
    });
    printSalesButtons.forEach((button) => {
      bindCardLikeAction(button, () => {
        const payload = parseJsonScript("lotPrintSalesPayload");
        postPrintPreview("/app/templates/lotes_print_preview.php", payload);
      });
    });
    cancelButtons.forEach((button) => {
      button.addEventListener("click", () => {
        resetCancelForm();
        openCancelModal();
      });
    });
    cancelClose?.addEventListener("click", closeCancelModal);
    cancelDismiss?.addEventListener("click", closeCancelModal);
    cancelKindOptions.forEach((option) => {
      if (!(option instanceof HTMLElement)) return;
      option.addEventListener("click", () => {
        syncCancelKind(option.getAttribute("data-lot-cancel-kind-option") || "total");
      });
    });
    cancelKindRadios.forEach((radio) => {
      if (!(radio instanceof HTMLInputElement)) return;
      radio.addEventListener("change", () => {
        if (radio.checked) syncCancelKind(radio.value);
      });
    });
    cancelEditButtons.forEach((button) => {
      if (!(button instanceof HTMLElement)) return;
      button.addEventListener("click", () => {
        if (cancelRecordId instanceof HTMLInputElement) {
          cancelRecordId.value = button.getAttribute("data-cancel-id") || "0";
        }
        if (cancelDate instanceof HTMLInputElement) {
          cancelDate.value = button.getAttribute("data-cancel-date") || "";
        }
        if (cancelReason instanceof HTMLInputElement) {
          cancelReason.value = button.getAttribute("data-cancel-reason") || "";
        }
        if (cancelReport instanceof HTMLTextAreaElement) {
          cancelReport.value = button.getAttribute("data-cancel-report") || "";
        }
        if (cancelAmount instanceof HTMLInputElement) {
          cancelAmount.value = moneyBR(button.getAttribute("data-cancel-amount") || 0);
        }
        if (cancelStatusSelect instanceof HTMLSelectElement) {
          cancelStatusSelect.value = button.getAttribute("data-cancel-status") || "";
        }
        if (cancelRefundDueDate instanceof HTMLInputElement) {
          cancelRefundDueDate.value = button.getAttribute("data-cancel-due-date") || "";
        }
        if (cancelFinance instanceof HTMLTextAreaElement) {
          cancelFinance.value = button.getAttribute("data-cancel-finance") || "";
        }
        if (cancelAttachmentsInput instanceof HTMLInputElement) {
          cancelAttachmentsInput.value = "";
        }
        renderCancelSelectedFiles();
        syncCancelKind(button.getAttribute("data-cancel-kind") || "total");
        openCancelModal();
      });
    });
    occurrencePrintButtons.forEach((button) => {
      if (!(button instanceof HTMLElement)) return;
      button.addEventListener("click", () => {
        const occurrenceId = String(button.getAttribute("data-occurrence-id") || "");
        const payload = occurrenceId !== "" && occurrenceReports && typeof occurrenceReports === "object"
          ? occurrenceReports[occurrenceId]
          : null;
        if (!payload) return;
        postPrintPreview("/app/templates/lotes_print_preview.php", payload);
      });
    });
    cancelSelectedFilesGrid?.addEventListener("click", (event) => {
      const trigger = event.target instanceof Element ? event.target.closest("[data-lot-cancel-selected-preview]") : null;
      if (!(trigger instanceof HTMLElement)) return;
      openCancelSelectedPreview(Number(trigger.getAttribute("data-lot-cancel-selected-preview") || 0));
    });
    cancelSavedFilesGrid?.addEventListener("click", (event) => {
      const trigger = event.target instanceof Element ? event.target.closest("[data-lot-cancel-saved-preview]") : null;
      if (!(trigger instanceof HTMLElement)) return;
      openCancelSavedPreview(Number(trigger.getAttribute("data-lot-cancel-saved-preview") || 0));
    });
    cancelModal?.addEventListener("click", (event) => {
      if (event.target === cancelModal) {
        closeCancelModal();
      }
    });
    cancelAttachmentsInput?.addEventListener("change", renderCancelSelectedFiles);
    resetCancelForm();
  }

  function bindLotAnalyticsDashboard() {
    const payload = parseJsonScript("lotAnalyticsPayload");
    const viewMode = String(payload?.viewMode || "ativos");
    const rows = Array.isArray(payload?.rows) ? payload.rows : [];
    const events = Array.isArray(payload?.events) ? payload.events : [];
    const annualYear = document.getElementById("lotAnalyticsAnnualYear");
    const expenseMonth = document.getElementById("lotAnalyticsExpenseMonth");
    const revenueMonth = document.getElementById("lotAnalyticsRevenueMonth");
    const supplierMonth = document.getElementById("lotAnalyticsSupplierMonth");
    const annualReport = document.getElementById("lotAnalyticsAnnualReport");
    const expenseReport = document.getElementById("lotAnalyticsExpenseReport");
    const revenueReport = document.getElementById("lotAnalyticsRevenueReport");
    const supplierReport = document.getElementById("lotAnalyticsSupplierReport");
    const annualPrintButton = document.getElementById("lotAnalyticsAnnualPrint");
    const expensePrintButton = document.getElementById("lotAnalyticsExpensePrint");
    const revenuePrintButton = document.getElementById("lotAnalyticsRevenuePrint");
    const supplierPrintButton = document.getElementById("lotAnalyticsSupplierPrint");
    const annualCanvas = document.getElementById("lotAnalyticsAnnualChart");
    const expenseCanvas = document.getElementById("lotAnalyticsExpenseChart");
    const revenueCanvas = document.getElementById("lotAnalyticsRevenueChart");
    const supplierCanvas = document.getElementById("lotAnalyticsSupplierChart");
    const revenueTitle = document.getElementById("lotAnalyticsRevenueTitle");
    const revenueReportTitle = document.getElementById("lotAnalyticsRevenueReportTitle");

    if (!rows.length || !annualCanvas || !expenseCanvas || !revenueCanvas || !supplierCanvas) return;

    const chartState = {};
    const printState = {};
    const chartColors = [
      "rgba(37,99,235,.82)",
      "rgba(16,185,129,.78)",
      "rgba(245,158,11,.78)",
      "rgba(239,68,68,.74)",
      "rgba(168,85,247,.72)",
      "rgba(14,165,233,.72)",
      "rgba(99,102,241,.72)",
      "rgba(34,197,94,.72)",
    ];

    function ensureChart(key, canvas, cfg) {
      if (!window.Chart || !(canvas instanceof HTMLCanvasElement)) return;
      try {
        if (chartState[key]) chartState[key].destroy();
      } catch (_) {}
      chartState[key] = new window.Chart(canvas.getContext("2d"), cfg);
    }

    function money(value) {
      return moneyBR(Number(value || 0));
    }

    function percentLabel(value, total) {
      const amount = Number(value) || 0;
      const base = Number(total) || 0;
      if (base <= 0 || amount <= 0) return "0%";
      return `${((amount / base) * 100).toLocaleString("pt-BR", { minimumFractionDigits: 1, maximumFractionDigits: 1 })}%`;
    }

    function renderReport(container, title, rowsData, totalLabel = "Total") {
      if (!(container instanceof HTMLElement)) return;
      const total = rowsData.reduce((acc, row) => acc + (Number(row.value) || 0), 0);
      container.innerHTML = `
        <div class="lot-analytics-report__head">
          <strong>${escapeHtml(title)}</strong>
          <span>${escapeHtml(totalLabel)}: ${escapeHtml(money(total))}</span>
        </div>
        <table class="lot-analytics-report__table">
          <thead>
            <tr>
              <th>Indicador</th>
              <th>Valor</th>
            </tr>
          </thead>
          <tbody>
            ${rowsData.map((row) => `
              <tr>
                <td>${escapeHtml(row.label)}</td>
                <td>${escapeHtml(money(row.value))}</td>
              </tr>
            `).join("")}
          </tbody>
        </table>
      `;
    }

    function setPrintState(key, title, filterLabel, rowsData, totalLabel) {
      printState[key] = { title, filterLabel, rowsData, totalLabel };
    }

    function createDonutPrintImage(key, rowsData) {
      if (!window.Chart || !Array.isArray(rowsData) || !rowsData.length) {
        return chartState[key] && typeof chartState[key].toBase64Image === "function"
          ? chartState[key].toBase64Image()
          : "";
      }

      const tempCanvas = document.createElement("canvas");
      tempCanvas.width = 720;
      tempCanvas.height = 420;

      const tempChart = new window.Chart(tempCanvas.getContext("2d"), {
        type: "doughnut",
        data: {
          labels: rowsData.map((item) => item.label),
          datasets: [{
            data: rowsData.map((item) => item.value),
            backgroundColor: chartColors.slice(0, Math.max(1, rowsData.length)),
            borderWidth: 0,
            hoverOffset: 0,
          }],
        },
        options: {
          responsive: false,
          animation: false,
          maintainAspectRatio: false,
          layout: {
            padding: {
              top: 44,
              right: 28,
              bottom: 18,
              left: 28,
            },
          },
          plugins: {
            legend: { position: "bottom" },
            tooltip: { enabled: false },
          },
          cutout: "58%",
        },
      });

      tempChart.update("none");
      const image = tempChart.toBase64Image();
      tempChart.destroy();
      return image;
    }

    function openAnalyticsPrint(key) {
      const state = printState[key];
      if (!state) return;
      const chartImage = key === "annual"
        ? (chartState[key] && typeof chartState[key].toBase64Image === "function" ? chartState[key].toBase64Image() : "")
        : createDonutPrintImage(key, state.rowsData);
      const chartSummaryRows = key === "annual"
        ? []
        : state.rowsData.map((row) => ({
            label: String(row.label || ""),
            percent: percentLabel(row.value, state.rowsData.reduce((acc, item) => acc + (Number(item.value) || 0), 0)),
          }));
      postPrintPreview("/app/templates/lotes_print_preview.php", {
        title: state.title,
        metaTitle: "Dashboard de Lotes",
        metaHint: "Para salvar: Cmd+P (Mac) / Ctrl+P (Windows) → Destino: Salvar como PDF",
        brandSub: "Relatório analítico do módulo de lotes",
        reportTitle: state.title,
        chartTitle: state.title,
        chartImage,
        chartType: key === "annual" ? "annual" : "donut",
        chartSummaryRows,
        metaRows: [
          { label: "Filtro aplicado", value: state.filterLabel },
          { label: "Gerado em", value: new Date().toLocaleString("pt-BR") },
        ],
        sectionTitle: state.title,
        table: {
          head: ["Indicador", "Valor"],
          rows: state.rowsData.map((row) => [String(row.label || ""), money(row.value)]),
          total: {
            label: state.totalLabel || "Total",
            value: money(state.rowsData.reduce((acc, row) => acc + (Number(row.value) || 0), 0)),
            colspan: 1,
          },
        },
        footnote: "Documento gerado automaticamente pelo Sistema Visa Remoções.",
      });
    }

    function monthKey(value) {
      const date = new Date(`${String(value || "")}T00:00:00`);
      if (Number.isNaN(date.getTime())) return "";
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}`;
    }

    function yearKey(value) {
      const date = new Date(`${String(value || "")}T00:00:00`);
      if (Number.isNaN(date.getTime())) return "";
      return String(date.getFullYear());
    }

    function monthLabel(key) {
      if (!/^\d{4}-\d{2}$/.test(String(key || ""))) return String(key || "");
      return `${String(key).slice(5, 7)}/${String(key).slice(0, 4)}`;
    }

    function ensureOptions(select, values, formatter) {
      if (!(select instanceof HTMLSelectElement)) return;
      const current = select.value;
      select.innerHTML = values.map((value) => `<option value="${escapeHtml(value)}">${escapeHtml(formatter(value))}</option>`).join("");
      if (values.includes(current)) select.value = current;
    }

    function availableMonths() {
      const set = new Set();
      rows.forEach((row) => {
        const key = monthKey(row.dataCompra);
        if (key) set.add(key);
      });
      events.forEach((event) => {
        const key = monthKey(event.date);
        if (key) set.add(key);
      });
      return Array.from(set).sort();
    }

    function availableYears() {
      const set = new Set();
      availableMonths().forEach((value) => {
        const year = String(value).slice(0, 4);
        if (year) set.add(year);
      });
      return Array.from(set).sort();
    }

    function sumRows(list, accessor) {
      return list.reduce((acc, item) => acc + (Number(accessor(item)) || 0), 0);
    }

    function rowsForMonth(key) {
      return rows.filter((row) => monthKey(row.dataCompra) === key);
    }

    function eventsForMonth(key, kind) {
      return events.filter((event) => monthKey(event.date) === key && String(event.kind || "") === kind);
    }

    function monthlyExpenseRows(key) {
      const selectedRows = rowsForMonth(key);
      return [
        { label: "Compra do lote", value: sumRows(selectedRows, (row) => row.compra) },
        { label: "Frete", value: sumRows(selectedRows, (row) => row.frete) },
        { label: "Armazenagem", value: sumRows(selectedRows, (row) => row.armazenagem) },
        { label: "Carregamento", value: sumRows(selectedRows, (row) => row.carregamento) },
        { label: "SOS", value: sumRows(selectedRows, (row) => row.sos) },
        { label: "Documentação", value: sumRows(selectedRows, (row) => row.documentacao) },
        { label: "Impostos frete", value: sumRows(selectedRows, (row) => row.impostosFrete) },
        { label: "Outros frete", value: sumRows(selectedRows, (row) => row.outrosFrete) },
        { label: "Outros custos", value: sumRows(selectedRows, (row) => row.outrosCustos + row.outrosLocais) },
      ].filter((item) => item.value > 0);
    }

    function monthlyRevenueRows(key) {
      if (viewMode === "cancelados") {
        const byLot = new Map();
        eventsForMonth(key, "devolucoes").forEach((event) => {
          const label = String(event.lotLabel || `Lote ${event.lotId || ""}`);
          byLot.set(label, (byLot.get(label) || 0) + (Number(event.value) || 0));
        });
        return Array.from(byLot.entries())
          .map(([label, value]) => ({ label, value }))
          .sort((a, b) => Number(b.value) - Number(a.value))
          .slice(0, 10);
      }

      const byLot = new Map();
      eventsForMonth(key, "vendas").forEach((event) => {
        const label = String(event.lotLabel || `Lote ${event.lotId || ""}`);
        byLot.set(label, (byLot.get(label) || 0) + (Number(event.value) || 0));
      });
      return Array.from(byLot.entries())
        .map(([label, value]) => ({ label, value }))
        .sort((a, b) => Number(b.value) - Number(a.value))
        .slice(0, 10);
    }

    function monthlySupplierRows(key) {
      const selectedRows = rowsForMonth(key);
      const bySupplier = new Map();
      selectedRows.forEach((row) => {
        const label = String(row.fornecedor || "Fornecedor não informado");
        bySupplier.set(label, (bySupplier.get(label) || 0) + (Number(row.investimento) || 0));
      });
      return Array.from(bySupplier.entries())
        .map(([label, value]) => ({ label, value }))
        .sort((a, b) => Number(b.value) - Number(a.value))
        .slice(0, 10);
    }

    function annualRows(year) {
      const months = Array.from({ length: 12 }, (_, index) => `${year}-${String(index + 1).padStart(2, "0")}`);
      return months.map((key) => {
        const expenseValue = sumRows(rowsForMonth(key), (row) => row.investimento);
        const revenueValue = sumRows(eventsForMonth(key, "vendas"), (event) => event.value) + sumRows(eventsForMonth(key, "devolucoes"), (event) => event.value);
        return {
          label: monthLabel(key),
          receita: revenueValue,
          despesa: expenseValue,
        };
      });
    }

    function renderAnnual() {
      const year = annualYear instanceof HTMLSelectElement ? annualYear.value : availableYears()[availableYears().length - 1];
      const rowsData = annualRows(year);
      ensureChart("annual", annualCanvas, {
        type: "bar",
        data: {
          labels: rowsData.map((item) => item.label),
          datasets: [
            {
              label: "Receitas",
              data: rowsData.map((item) => item.receita),
              backgroundColor: "rgba(16,185,129,.72)",
              borderRadius: 8,
            },
            {
              label: "Despesas",
              data: rowsData.map((item) => item.despesa),
              backgroundColor: "rgba(239,68,68,.68)",
              borderRadius: 8,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${money(ctx.parsed.y)}` } },
          },
        },
      });
      renderReport(
        annualReport,
        `Relatório anual ${year}`,
        rowsData.map((item) => ({ label: item.label, value: item.receita - item.despesa })),
        "Saldo anual"
      );
      setPrintState(
        "annual",
        `Raio X anual de receitas x despesas • ${year}`,
        `Ano: ${year}`,
        rowsData.map((item) => ({ label: `${item.label} • Receita`, value: item.receita }))
          .concat(rowsData.map((item) => ({ label: `${item.label} • Despesa`, value: item.despesa }))),
        "Movimento anual"
      );
    }

    function renderExpense() {
      const key = expenseMonth instanceof HTMLSelectElement ? expenseMonth.value : availableMonths()[availableMonths().length - 1];
      const rowsData = monthlyExpenseRows(key);
      const total = rowsData.reduce((acc, row) => acc + (Number(row.value) || 0), 0);
      ensureChart("expense", expenseCanvas, {
        type: "doughnut",
        data: {
          labels: rowsData.map((item) => item.label),
          datasets: [{
            data: rowsData.map((item) => item.value),
            backgroundColor: chartColors.slice(0, Math.max(1, rowsData.length)),
            borderWidth: 0,
            hoverOffset: 16,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.parsed)} • ${percentLabel(ctx.parsed, total)}` } },
          },
          cutout: "58%",
        },
      });
      renderReport(expenseReport, `Relatório de despesas • ${monthLabel(key)}`, rowsData, "Despesa total");
      setPrintState("expense", "Despesas do lote", `Mês: ${monthLabel(key)}`, rowsData, "Despesa total");
    }

    function renderRevenue() {
      const key = revenueMonth instanceof HTMLSelectElement ? revenueMonth.value : availableMonths()[availableMonths().length - 1];
      const rowsData = monthlyRevenueRows(key);
      const total = rowsData.reduce((acc, row) => acc + (Number(row.value) || 0), 0);
      const chartTitle = viewMode === "cancelados" ? "Estornos por lote" : "Receitas por lote";
      const reportTitle = viewMode === "cancelados" ? "Relatório de estornos" : "Relatório de receitas";
      const totalLabel = viewMode === "cancelados" ? "Estorno total" : "Receita total";
      if (revenueTitle instanceof HTMLElement) revenueTitle.textContent = chartTitle;
      if (revenueReportTitle instanceof HTMLElement) revenueReportTitle.textContent = reportTitle;
      ensureChart("revenue", revenueCanvas, {
        type: "doughnut",
        data: {
          labels: rowsData.map((item) => item.label),
          datasets: [{
            data: rowsData.map((item) => item.value),
            backgroundColor: chartColors.slice(0, Math.max(1, rowsData.length)),
            borderWidth: 0,
            hoverOffset: 16,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.parsed)} • ${percentLabel(ctx.parsed, total)}` } },
          },
          cutout: "58%",
        },
      });
      renderReport(revenueReport, `${reportTitle} • ${monthLabel(key)}`, rowsData, totalLabel);
      setPrintState("revenue", chartTitle, `Mês: ${monthLabel(key)}`, rowsData, totalLabel);
    }

    function renderSupplier() {
      const key = supplierMonth instanceof HTMLSelectElement ? supplierMonth.value : availableMonths()[availableMonths().length - 1];
      const rowsData = monthlySupplierRows(key);
      const total = rowsData.reduce((acc, row) => acc + (Number(row.value) || 0), 0);
      ensureChart("supplier", supplierCanvas, {
        type: "doughnut",
        data: {
          labels: rowsData.map((item) => item.label),
          datasets: [{
            data: rowsData.map((item) => item.value),
            backgroundColor: chartColors.slice(0, Math.max(1, rowsData.length)),
            borderWidth: 0,
            hoverOffset: 16,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: "bottom" },
            tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${money(ctx.parsed)} • ${percentLabel(ctx.parsed, total)}` } },
          },
          cutout: "58%",
        },
      });
      renderReport(supplierReport, `Relatório por fornecedor • ${monthLabel(key)}`, rowsData, "Compras totais");
      setPrintState("supplier", "Compras por fornecedor", `Mês: ${monthLabel(key)}`, rowsData, "Compras totais");
    }

    const months = availableMonths();
    const years = availableYears();
    if (!months.length || !years.length) return;

    ensureOptions(expenseMonth, months, monthLabel);
    ensureOptions(revenueMonth, months, monthLabel);
    ensureOptions(supplierMonth, months, monthLabel);
    ensureOptions(annualYear, years, (value) => value);

    expenseMonth?.addEventListener("change", renderExpense);
    revenueMonth?.addEventListener("change", renderRevenue);
    supplierMonth?.addEventListener("change", renderSupplier);
    annualYear?.addEventListener("change", renderAnnual);
    annualPrintButton?.addEventListener("click", () => openAnalyticsPrint("annual"));
    expensePrintButton?.addEventListener("click", () => openAnalyticsPrint("expense"));
    revenuePrintButton?.addEventListener("click", () => openAnalyticsPrint("revenue"));
    supplierPrintButton?.addEventListener("click", () => openAnalyticsPrint("supplier"));

    renderAnnual();
    renderExpense();
    renderRevenue();
    renderSupplier();
  }

  function bindLotDetailEditors() {
    const processForm = document.getElementById("lotProcessDataForm");
    bindMoneyInputs(processForm);
    bindUppercaseInputs(processForm);
    const storageForm = document.getElementById("lotStorageDataForm");
    bindMoneyInputs(storageForm);
    bindMaskedInputs(storageForm);
    bindUppercaseInputs(storageForm);
    bindUppercaseInputs(document.getElementById("lotNotesForm"));
    const editorForms = Array.from(document.querySelectorAll("[data-lot-editor-form]"));

    function formHasPendingChanges(form) {
      if (!(form instanceof HTMLFormElement)) return false;

      const snapshot = form.__lotEditorSnapshot instanceof Map ? form.__lotEditorSnapshot : null;
      if (!snapshot) return false;

      const fields = form.querySelectorAll("[data-lot-editor-fields] input, [data-lot-editor-fields] select, [data-lot-editor-fields] textarea");
      return Array.from(fields).some((field) => {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return false;
        return String(snapshot.get(field) ?? "") !== String(field.value ?? "");
      });
    }

    function initEditorForm(form) {
      const fieldset = form.querySelector("[data-lot-editor-fields]");
      const toggle = form.querySelector("[data-lot-editor-toggle]");
      const cancel = form.querySelector("[data-lot-editor-cancel]");
      const save = form.querySelector("[data-lot-editor-save]");
      const editOnly = Array.from(form.querySelectorAll("[data-lot-editor-edit-only]"));
      if (!fieldset || !toggle || !save) return;
      if (form.dataset.lotEditorBound === "1") return;
      form.dataset.lotEditorBound = "1";

      const snapshot = new Map();
      Array.from(fieldset.querySelectorAll("input, select, textarea")).forEach((field) => {
        if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
        snapshot.set(field, field.value);
      });

      const setEditingState = (editing) => {
        form.dataset.lotEditorEditing = editing ? "1" : "0";

        if (editing) {
          fieldset.disabled = false;
          fieldset.removeAttribute("disabled");
        } else {
          fieldset.disabled = true;
          fieldset.setAttribute("disabled", "disabled");
        }

        Array.from(fieldset.querySelectorAll("input, select, textarea")).forEach((field) => {
          if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) return;
          if (field.hasAttribute("readonly")) return;
          field.disabled = !editing;
        });

        toggle.hidden = editing;
        if (cancel) cancel.hidden = !editing;
        save.hidden = !editing;
        editOnly.forEach((node) => {
          if (!(node instanceof HTMLElement)) return;
          node.hidden = !editing;
        });
        form.dataset.lotEditorDirty = formHasPendingChanges(form) ? "1" : "0";
      };

      form.__lotEditorSetState = setEditingState;
      form.__lotEditorSnapshot = snapshot;

      setEditingState(false);

      toggle.addEventListener("click", () => {
        setEditingState(true);
        const firstEditable = Array.from(fieldset.querySelectorAll("input, select, textarea")).find((field) => {
          return (
            (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) &&
            !field.disabled &&
            !field.hasAttribute("readonly") &&
            field.type !== "hidden"
          );
        });
        firstEditable?.focus();
      });

      cancel?.addEventListener("click", () => {
        snapshot.forEach((value, field) => {
          field.value = value;
          field.dispatchEvent(new Event("input", { bubbles: true }));
          field.dispatchEvent(new CustomEvent("lot:money-change"));
        });
        setEditingState(false);
      });

      fieldset.addEventListener("input", () => {
        form.dataset.lotEditorDirty = formHasPendingChanges(form) ? "1" : "0";
      });
      fieldset.addEventListener("change", () => {
        form.dataset.lotEditorDirty = formHasPendingChanges(form) ? "1" : "0";
      });
    }

    editorForms.forEach(initEditorForm);

    document.addEventListener("click", (event) => {
      const toggle = event.target instanceof Element ? event.target.closest("[data-lot-editor-toggle]") : null;
      const cancel = event.target instanceof Element ? event.target.closest("[data-lot-editor-cancel]") : null;

      if (!toggle && !cancel) return;

      const form = (toggle || cancel)?.closest("[data-lot-editor-form]");
      if (!(form instanceof HTMLFormElement)) return;

      initEditorForm(form);

      if (toggle && typeof form.__lotEditorSetState === "function") {
        event.preventDefault();
        form.__lotEditorSetState(true);
        const firstEditable = Array.from(form.querySelectorAll("[data-lot-editor-fields] input, [data-lot-editor-fields] select, [data-lot-editor-fields] textarea")).find((field) => {
          return (
            (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) &&
            !field.disabled &&
            !field.hasAttribute("readonly") &&
            field.type !== "hidden"
          );
        });
        firstEditable?.focus();
        form.dataset.lotEditorDirty = formHasPendingChanges(form) ? "1" : "0";
        return;
      }

      if (cancel && typeof form.__lotEditorSetState === "function" && form.__lotEditorSnapshot instanceof Map) {
        event.preventDefault();
        form.__lotEditorSnapshot.forEach((value, field) => {
          field.value = value;
          field.dispatchEvent(new Event("input", { bubbles: true }));
          field.dispatchEvent(new CustomEvent("lot:money-change"));
        });
        form.__lotEditorSetState(false);
        form.dataset.lotEditorDirty = "0";
      }
    });

    window.__lotHasPendingEditorChanges = () => editorForms.some((form) => formHasPendingChanges(form));
    window.__lotGetPendingEditorForms = () => editorForms.filter((form) => formHasPendingChanges(form));

    if (!storageForm) return;

    const costInputs = [
      document.getElementById("lotStorageCustoArmazenagem"),
      document.getElementById("lotStorageCustoCarregamento"),
      document.getElementById("lotStorageCustoSos"),
      document.getElementById("lotStorageOutrosCustos"),
    ].filter(Boolean);
    const totalInput = document.getElementById("lotStorageCustosTotal");
    if (!totalInput) return;

    const syncCosts = () => {
      const total = costInputs.reduce((sum, field) => sum + decimalInput(field.value), 0);
      totalInput.value = moneyBR(total);
    };

    costInputs.forEach((field) => {
      field.addEventListener("input", syncCosts);
      field.addEventListener("lot:money-change", syncCosts);
    });

    syncCosts();
  }

  function bindLotBaixaModal() {
    const modal = document.getElementById("lotItemBaixaModal");
    if (!modal) return;
    const title = document.getElementById("lotItemBaixaTitle");
    const itemName = document.getElementById("lotBaixaItemNome");
    const itemIdInput = document.getElementById("lotBaixaItemId");
    const quantidadeInput = document.getElementById("lotBaixaQuantidadeInput");
    const disponivelPreview = document.getElementById("lotBaixaDisponivelPreview");
    const closeBtn = document.getElementById("lotItemBaixaClose");
    const cancelBtn = document.getElementById("lotItemBaixaCancel");
    const triggers = Array.from(document.querySelectorAll("[data-lot-item-baixa]"));
    if (!title || !itemName || !itemIdInput || !quantidadeInput || !disponivelPreview || !closeBtn || !cancelBtn || !triggers.length) {
      return;
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const itemId = trigger.getAttribute("data-item-id") || "";
        const itemDescricao = trigger.getAttribute("data-item-descricao") || "Item";
        const itemDisponivel = trigger.getAttribute("data-item-disponivel") || "0";

        itemIdInput.value = itemId;
        itemName.textContent = itemDescricao;
        title.textContent = `Baixa manual • ${itemDescricao}`;
        quantidadeInput.value = "";
        quantidadeInput.setAttribute("max", itemDisponivel);
        disponivelPreview.textContent = Number(itemDisponivel || "0").toLocaleString("pt-BR", { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        modal.setAttribute("aria-hidden", "false");
        modal.classList.add("is-open");
      });
    });

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function bindLotVendaModal() {
    const modal = document.getElementById("lotItemVendaModal");
    if (!modal) return;
    const openBtn = document.getElementById("lotItemVendaOpen");
    const closeBtn = document.getElementById("lotItemVendaClose");
    const cancelBtn = document.getElementById("lotItemVendaCancel");
    const modeSelect = document.getElementById("lotVendaModoSelect");
    const itemField = document.getElementById("lotVendaItemField");
    const itemGrid = document.getElementById("lotVendaItemGrid");
    const itemSelect = document.getElementById("lotVendaItemSelect");
    const qtyInput = document.getElementById("lotVendaQuantidadeInput");
    const unitInput = document.getElementById("lotVendaValorInput");
    const totalPreview = document.getElementById("lotVendaTotalPreview");
    const formaSelect = document.getElementById("lotVendaFormaSelect");
    const parcelasField = document.getElementById("lotVendaParcelasField");
    const parcelasSelect = document.getElementById("lotVendaParcelasSelect");
    const clienteLookup = document.getElementById("lotVendaClienteLookup");
    const clienteSearch = document.getElementById("lotVendaClienteSearch");
    const clienteResults = document.getElementById("lotVendaClienteResults");
    const clienteIdInput = document.getElementById("lotVendaClienteId");
    const clienteMeta = document.getElementById("lotVendaClienteMeta");
    const clienteMetaName = document.getElementById("lotVendaClienteMetaName");
    const clienteMetaDoc = document.getElementById("lotVendaClienteMetaDoc");
    const printSalesButton = document.getElementById("lotPrintSalesButton");
    const newClientBtn = document.getElementById("lotVendaNovoClienteOpen");
    const itemPicker = document.getElementById("lotVendaItemPicker");
    const selectedName = document.getElementById("lotVendaSelectedName");
    const selectedMeta = document.getElementById("lotVendaSelectedMeta");
    const qtyLabel = document.getElementById("lotVendaQuantidadeLabel");
    const qtyField = qtyInput?.closest(".lot-field");
    if (!openBtn || !closeBtn || !cancelBtn || !modeSelect || !itemField || !itemGrid || !itemSelect || !qtyInput || !unitInput || !totalPreview || !formaSelect || !parcelasField || !parcelasSelect || !clienteLookup || !clienteSearch || !clienteResults || !clienteIdInput || !clienteMeta || !clienteMetaName || !clienteMetaDoc || !selectedName || !selectedMeta || !qtyLabel) {
      return;
    }
    const cadastroSource = parseJsonAttr(clienteLookup, "data-lot-cadastro-source", []);

    function openInlineCadastro(tipo = "cliente", title = "Novo cadastro") {
      const inlineModal = document.getElementById("lotCadastroInlineModal");
      const inlineFrame = document.getElementById("lotCadastroInlineFrame");
      const inlineTitle = document.getElementById("lotCadastroInlineTitle");
      if (!inlineModal || !inlineFrame || !inlineTitle) return;

      inlineTitle.textContent = title;
      inlineFrame.setAttribute("src", appUrl(`/app/templates/cadastros_ficha_embed.php?modo=cadastro&tipo=${encodeURIComponent(tipo)}&embed=1`));
      inlineModal.setAttribute("aria-hidden", "false");
      inlineModal.classList.add("is-open");
    }

    function money(value) {
      const number = Number(value || 0);
      const safe = Number.isFinite(number) ? number : 0;
      return safe.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function decimal(value) {
      const raw = String(value || "").replace(/[^\d,.-]/g, "");
      let normalized = raw;
      if (raw.includes(",") && raw.includes(".")) {
        normalized = raw.replace(/\./g, "").replace(",", ".");
      } else if (raw.includes(",")) {
        normalized = raw.replace(",", ".");
      }
      const number = Number(normalized);
      return Number.isFinite(number) ? number : 0;
    }

    function formatMoneyInput(value) {
      return money(decimal(value));
    }

    function selectedOptionById(id) {
      return Array.from(itemSelect.options).find((option) => String(option.value || "") === String(id || "")) || null;
    }

    function syncSelectedItem(option) {
      const typeLabel = String(option?.getAttribute("data-type") || "Und");
      const availableLabel = String(option?.getAttribute("data-available-label") || "0");
      const itemLabel = option ? option.textContent.split(" • ")[0].trim() : "Selecione um produto na lista";
      const priceValue = option?.getAttribute("data-price") || "0";

      qtyLabel.textContent = `Quantidade (${typeLabel})`;
      selectedName.textContent = itemLabel;
      selectedMeta.textContent = `Tipo ${typeLabel} • disponível ${availableLabel} • valor sugerido ${money(priceValue)}`;
      qtyInput.max = option?.getAttribute("data-max") || "";

      if (!unitInput.dataset.userEdited || unitInput.dataset.userEdited === "0") {
        unitInput.value = formatMoneyInput(priceValue);
      }

      itemPicker?.querySelectorAll("[data-lot-venda-pick]").forEach((button) => {
        const isSelected = String(button.getAttribute("data-item-id") || "") === String(option?.value || "");
        button.classList.toggle("is-selected", isSelected);
        button.closest("tr")?.classList.toggle("is-selected", isSelected);
      });
    }

    function syncMode() {
      const isTotal = modeSelect.value === "lote_total";
      itemField.hidden = isTotal;
      itemGrid.hidden = isTotal;
      if (qtyField) qtyField.hidden = isTotal;
      if (!isTotal) {
        const option = itemSelect.selectedOptions[0];
        if (option) {
          syncSelectedItem(option);
        }
      } else {
        selectedName.textContent = "Venda total do lote";
        selectedMeta.textContent = "O sistema vai consumir o disponível de todos os itens ativos do processo.";
      }
      syncTotal();
    }

    function syncPaymentMode() {
      parcelasField.hidden = false;
    }

    function syncTotal() {
      if (modeSelect.value === "lote_total") {
        totalPreview.value = "Calculado por item disponível";
        return;
      }
      const qty = Number(String(qtyInput.value || "0").replace(",", ".")) || 0;
      const unit = decimal(unitInput.value || "0");
      totalPreview.value = money(qty * unit);
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    function openModal() {
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
      syncMode();
      syncPaymentMode();
    }

    function setSelectedCadastro(item) {
      if (!item) {
        clienteIdInput.value = "";
        clienteSearch.value = "";
        clienteMeta.hidden = true;
        clienteResults.hidden = true;
        clienteResults.innerHTML = "";
        return;
      }

      clienteIdInput.value = String(item.id || "");
      clienteSearch.value = String(item.nome || "");
      clienteMeta.hidden = false;
      clienteMetaName.textContent = String(item.nome || "");
      clienteMetaDoc.textContent = String(item.documento || item.celular || "Telefone não informado");
      clienteResults.hidden = true;
      clienteResults.innerHTML = "";
    }

    function renderCadastroResults(items) {
      if (!items.length) {
        clienteResults.innerHTML = `
          <button type="button" class="lot-search-suggest__item" data-lot-create-client aria-live="polite">
            <span class="lot-search-suggest__icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
            <span class="lot-search-suggest__body">
              <strong>Cliente não encontrado</strong>
              <span>Clique aqui para cadastrar um novo cliente sem sair da venda.</span>
            </span>
          </button>
        `;
        clienteResults.hidden = false;
        return;
      }

      clienteResults.innerHTML = items.map((item) => `
        <button type="button" class="lot-search-suggest__item" data-lot-cadastro-pick="${String(item.id || "")}">
          <span class="lot-search-suggest__icon"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span>
          <span class="lot-search-suggest__body">
            <strong>${String(item.nome || "")}</strong>
            <span>${String(item.documento || item.celular || "Telefone não informado")}</span>
          </span>
        </button>
      `).join("");
      clienteResults.hidden = false;

      clienteResults.querySelectorAll("[data-lot-cadastro-pick]").forEach((button) => {
        button.addEventListener("click", () => {
          const id = String(button.getAttribute("data-lot-cadastro-pick") || "");
          const item = cadastroSource.find((entry) => String(entry.id || "") === id);
          setSelectedCadastro(item || null);
        });
      });
    }

    openBtn.addEventListener("click", openModal);
    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
    modeSelect.addEventListener("change", syncMode);
    formaSelect.addEventListener("change", syncPaymentMode);
    parcelasSelect.addEventListener("change", syncPaymentMode);
    itemSelect.addEventListener("change", () => {
      const option = itemSelect.selectedOptions[0];
      if (option) {
        unitInput.dataset.userEdited = "0";
        syncSelectedItem(option);
      }
      syncTotal();
    });
    qtyInput.addEventListener("input", syncTotal);
    unitInput.addEventListener("input", () => {
      unitInput.dataset.userEdited = "1";
      syncTotal();
    });
    unitInput.addEventListener("focus", () => {
      unitInput.value = decimal(unitInput.value || "0").toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    });
    unitInput.addEventListener("blur", () => {
      unitInput.value = formatMoneyInput(unitInput.value || "0");
      syncTotal();
    });
    clienteSearch.addEventListener("input", () => {
      const term = normalize(clienteSearch.value);
      if (!term) {
        clienteResults.hidden = true;
        clienteResults.innerHTML = "";
        if (!clienteIdInput.value) {
          clienteMeta.hidden = true;
        }
        return;
      }
      const items = cadastroSource.filter((item) => String(item.searchIndex || "").includes(term)).slice(0, 8);
      renderCadastroResults(items);
    });
    clienteSearch.addEventListener("blur", () => {
      window.setTimeout(() => {
        clienteResults.hidden = true;
      }, 120);
    });
    clienteResults.addEventListener("pointerdown", (event) => {
      const trigger = event.target instanceof Element ? event.target.closest("[data-lot-create-client], [data-lot-cadastro-pick]") : null;
      if (!trigger) return;
      event.preventDefault();

      if (trigger.hasAttribute("data-lot-create-client")) {
        openInlineCadastro("cliente", "Novo cliente");
        return;
      }

      const id = String(trigger.getAttribute("data-lot-cadastro-pick") || "");
      const item = cadastroSource.find((entry) => String(entry.id || "") === id);
      setSelectedCadastro(item || null);
    });
    itemPicker?.querySelectorAll("[data-lot-venda-pick]").forEach((button) => {
      button.addEventListener("click", () => {
        const option = selectedOptionById(button.getAttribute("data-item-id") || "");
        if (!option) return;
        itemSelect.value = option.value;
        unitInput.dataset.userEdited = "0";
        syncSelectedItem(option);
        syncTotal();
      });
    });
    printSalesButton?.addEventListener("click", () => {
      const payload = parseJsonScript("lotPrintSalesPayload");
      postPrintPreview("/app/templates/lotes_print_preview.php", payload);
    });
    newClientBtn?.addEventListener("click", () => {
      openInlineCadastro("cliente", "Novo cliente");
    });

    window.addEventListener("lot:inline-cadastro-saved", (event) => {
      const item = event.detail || {};
      const id = String(item.id || "");
      if (!id) return;

      const nextEntry = {
        id: Number(item.id || 0),
        nome: String(item.nome || ""),
        documento: String(item.documento || ""),
        celular: String(item.celular || ""),
        searchIndex: normalize([item.nome, item.documento, item.celular].join(" ")),
      };
      const existingIndex = cadastroSource.findIndex((entry) => String(entry.id || "") === id);
      if (existingIndex >= 0) {
        cadastroSource.splice(existingIndex, 1, nextEntry);
      } else {
        cadastroSource.unshift(nextEntry);
      }
      setSelectedCadastro(nextEntry);
    });

    unitInput.dataset.userEdited = "0";
    const firstOption = itemSelect.selectedOptions[0];
    if (firstOption) {
      syncSelectedItem(firstOption);
      unitInput.value = formatMoneyInput(firstOption.getAttribute("data-price") || "0");
    }

    return { openModal };
  }

  function bindLotBaixaTotalModal() {
    const modal = document.getElementById("lotBaixaTotalModal");
    const openBtn = document.getElementById("lotLoteBaixaTotalOpen");
    const closeBtn = document.getElementById("lotBaixaTotalClose");
    const cancelBtn = document.getElementById("lotBaixaTotalCancel");
    if (!modal || !openBtn || !closeBtn || !cancelBtn) return;

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    openBtn.addEventListener("click", () => {
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
    });
    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function bindLotRevertModal() {
    const modal = document.getElementById("lotItemRevertModal");
    const title = document.getElementById("lotItemRevertTitle");
    const itemName = document.getElementById("lotRevertItemNome");
    const itemIdInput = document.getElementById("lotRevertItemId");
    const quantidadeInput = document.getElementById("lotRevertQuantidadeInput");
    const baixadoPreview = document.getElementById("lotRevertBaixadoPreview");
    const closeBtn = document.getElementById("lotItemRevertClose");
    const cancelBtn = document.getElementById("lotItemRevertCancel");
    const triggers = Array.from(document.querySelectorAll("[data-lot-item-revert]"));
    if (!modal || !title || !itemName || !itemIdInput || !quantidadeInput || !baixadoPreview || !closeBtn || !cancelBtn || !triggers.length) {
      return;
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const itemId = trigger.getAttribute("data-item-id") || "";
        const itemDescricao = trigger.getAttribute("data-item-descricao") || "Item";
        const itemBaixado = trigger.getAttribute("data-item-baixado") || "0";

        itemIdInput.value = itemId;
        itemName.textContent = itemDescricao;
        title.textContent = `Reverter baixa • ${itemDescricao}`;
        quantidadeInput.value = "";
        quantidadeInput.setAttribute("max", itemBaixado);
        baixadoPreview.textContent = Number(itemBaixado || "0").toLocaleString("pt-BR", { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        modal.setAttribute("aria-hidden", "false");
        modal.classList.add("is-open");
      });
    });

    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function bindLotItemHistoryModal() {
    const modal = document.getElementById("lotItemHistoryModal");
    const title = document.getElementById("lotItemHistoryTitle");
    const list = document.getElementById("lotItemHistoryList");
    const heroName = document.getElementById("lotItemHistoryHeroName");
    const heroText = document.getElementById("lotItemHistoryHeroText");
    const summaryType = document.getElementById("lotItemHistorySummaryType");
    const summaryStatus = document.getElementById("lotItemHistorySummaryStatus");
    const summaryAvailable = document.getElementById("lotItemHistorySummaryAvailable");
    const qtyTotal = document.getElementById("lotItemHistoryQtyTotal");
    const qtyAvailable = document.getElementById("lotItemHistoryQtyAvailable");
    const qtySold = document.getElementById("lotItemHistoryQtySold");
    const qtyLow = document.getElementById("lotItemHistoryQtyLow");
    const baseValue = document.getElementById("lotItemHistoryBaseValue");
    const saleValue = document.getElementById("lotItemHistorySaleValue");
    const totalValue = document.getElementById("lotItemHistoryTotalValue");
    const notes = document.getElementById("lotItemHistoryNotes");
    const mainImage = document.getElementById("lotItemHistoryMainImage");
    const mediaEmpty = document.getElementById("lotItemHistoryMediaEmpty");
    const thumbs = document.getElementById("lotItemHistoryThumbs");
    const closeBtn = document.getElementById("lotItemHistoryClose");
    const triggers = Array.from(document.querySelectorAll("[data-lot-item-view]"));
    if (!modal || !title || !list || !closeBtn || !triggers.length) return;

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    function renderMainImage(image) {
      if (!mainImage || !mediaEmpty) return;
      const imageUrl = String(image?.previewUrl || image?.thumbUrl || "");
      const imageName = String(image?.displayName || image?.nomeOriginal || "Imagem do produto");
      if (imageUrl === "") {
        mainImage.hidden = true;
        mainImage.style.display = "none";
        mainImage.removeAttribute("src");
        mainImage.alt = "";
        mediaEmpty.hidden = false;
        mediaEmpty.style.display = "";
        return;
      }

      mainImage.src = imageUrl;
      mainImage.alt = imageName;
      mainImage.hidden = false;
      mainImage.style.display = "block";
      mediaEmpty.hidden = true;
      mediaEmpty.style.display = "none";
    }

    function renderThumbs(images) {
      if (!thumbs) return;
      if (!images.length) {
        thumbs.innerHTML = `
          <div class="lot-inline-empty lot-inline-empty--compact">
            Ainda não há imagens enviadas para este produto.
          </div>
        `;
        renderMainImage(null);
        return;
      }

      renderMainImage(images[0]);
      thumbs.innerHTML = images.map((image, index) => {
        const imageUrl = String(image.thumbUrl || image.previewUrl || "");
        const imageName = String(image.displayName || image.nomeOriginal || "Imagem do produto");
        const extension = String(image.extensao || image.extension || "").trim().toUpperCase();
        return `
          <article class="sv-attachments__item">
            <button type="button" class="sv-attachments__thumb" data-thumb-index="${index}">
              <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(imageName)}">
            </button>
            <div class="sv-attachments__meta">
              <div class="sv-attachments__name">${escapeHtml(imageName)}</div>
              <div class="sv-attachments__inforow">
                <span class="sv-attachments__info sv-attachments__infoitem">${escapeHtml(extension || "IMAGEM")}</span>
              </div>
            </div>
            <div class="sv-attachments__foot">
              <span class="sv-attachments__badge">Imagem</span>
            </div>
          </article>
        `;
      }).join("");

      thumbs.querySelectorAll("[data-thumb-index]").forEach((button) => {
        button.addEventListener("click", () => {
          const index = Number(button.getAttribute("data-thumb-index") || 0);
          renderMainImage(images[index] || null);
          if (window.AttachmentsUI && typeof window.AttachmentsUI.openViewer === "function") {
            window.AttachmentsUI.openViewer(images.map((imageItem) => ({
              name: String(imageItem.displayName || imageItem.nomeOriginal || "Imagem do produto"),
              previewUrl: String(imageItem.previewUrl || imageItem.thumbUrl || ""),
              downloadUrl: String(imageItem.downloadUrl || imageItem.previewUrl || imageItem.thumbUrl || ""),
              isImage: true,
              isPdf: false,
              extension: String(imageItem.extensao || imageItem.extension || ""),
            })), index);
          }
        });
      });
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const itemName = trigger.getAttribute("data-item-nome") || "Produto";
        const itemType = trigger.getAttribute("data-item-tipo-label") || "Und";
        const itemStatus = trigger.getAttribute("data-item-status-label") || "Ativo";
        const itemQtyTotal = trigger.getAttribute("data-item-quantidade-total-label") || "0,000";
        const itemQtyAvailable = trigger.getAttribute("data-item-quantidade-disponivel-label") || "0,000";
        const itemQtySold = trigger.getAttribute("data-item-quantidade-vendida-label") || "0,000";
        const itemQtyLow = trigger.getAttribute("data-item-quantidade-baixada-label") || "0,000";
        const itemBase = trigger.getAttribute("data-item-base-label") || "R$ 0,00";
        const itemSale = trigger.getAttribute("data-item-venda-label") || "R$ 0,00";
        const itemTotal = trigger.getAttribute("data-item-total-label") || "R$ 0,00";
        const itemNotes = trigger.getAttribute("data-item-observacoes") || "";
        let history = [];
        let images = [];
        try {
          history = JSON.parse(trigger.getAttribute("data-item-history") || "[]");
        } catch (_) {
          history = [];
        }
        try {
          images = JSON.parse(trigger.getAttribute("data-item-images") || "[]");
          if (!Array.isArray(images)) images = [];
        } catch (_) {
          images = [];
        }

        title.textContent = `Histórico do produto • ${itemName}`;
        if (heroName) heroName.textContent = itemName;
        if (heroText) heroText.textContent = `Status ${itemStatus.toLowerCase()} • tipo ${itemType.toLowerCase()} • reconhecimento visual e histórico operacional deste item do lote.`;
        if (summaryType) summaryType.textContent = itemType;
        if (summaryStatus) summaryStatus.textContent = itemStatus;
        if (summaryAvailable) summaryAvailable.textContent = itemQtyAvailable;
        if (qtyTotal) qtyTotal.textContent = itemQtyTotal;
        if (qtyAvailable) qtyAvailable.textContent = itemQtyAvailable;
        if (qtySold) qtySold.textContent = itemQtySold;
        if (qtyLow) qtyLow.textContent = itemQtyLow;
        if (baseValue) baseValue.textContent = itemBase;
        if (saleValue) saleValue.textContent = itemSale;
        if (totalValue) totalValue.textContent = itemTotal;
        if (notes) notes.textContent = itemNotes.trim() !== "" ? itemNotes : "Sem observações adicionais para este produto.";
        renderThumbs(images);

        if (!history.length) {
          list.innerHTML = `
            <div class="lot-inline-empty lot-inline-empty--compact">
              Ainda não há registros individualizados para este produto.
            </div>
          `;
        } else {
          list.innerHTML = history.map((entry) => {
            const desc = movementSummary(entry);
            const date = activityDateTime(entry.dataEvento || "", entry.createdAt || "");
            const resp = String(entry.responsavel || "");
            return `
              <div class="lot-timeline-list__item">
                <div class="lot-timeline-list__dot" aria-hidden="true"></div>
                <div class="lot-timeline-list__content">
                  <strong>${desc}</strong>
                  <span>${date}${resp ? ` • ${resp}` : ""}</span>
                </div>
              </div>
            `;
          }).join("");
        }

        modal.setAttribute("aria-hidden", "false");
        modal.classList.add("is-open");
      });
    });

    closeBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function bindLotSaleReturnModal() {
    const modal = document.getElementById("lotSaleReturnModal");
    const title = document.getElementById("lotSaleReturnTitle");
    const badge = document.getElementById("lotSaleReturnBadge");
    const refInput = document.getElementById("lotSaleReturnRef");
    const qtyInput = document.getElementById("lotSaleReturnQty");
    const saldoPreview = document.getElementById("lotSaleReturnSaldo");
    const totalPreview = document.getElementById("lotSaleReturnTotal");
    const closeBtn = document.getElementById("lotSaleReturnClose");
    const cancelBtn = document.getElementById("lotSaleReturnCancel");
    const triggers = Array.from(document.querySelectorAll("[data-lot-sale-return]"));
    if (!modal || !title || !badge || !refInput || !qtyInput || !saldoPreview || !totalPreview || !closeBtn || !cancelBtn || !triggers.length) {
      return;
    }

    let currentUnit = 0;

    function money(value) {
      return Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    function syncTotal() {
      const qty = Number(String(qtyInput.value || "0").replace(",", ".")) || 0;
      totalPreview.textContent = money(qty * currentUnit);
    }

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", () => {
        const saleRef = trigger.getAttribute("data-sale-ref") || "";
        const itemName = trigger.getAttribute("data-sale-item") || "Item";
        const clientName = trigger.getAttribute("data-sale-cliente") || "Cliente";
        const typeLabel = trigger.getAttribute("data-sale-tipo") || "Und";
        const balance = trigger.getAttribute("data-sale-balance") || "0";
        currentUnit = Number(trigger.getAttribute("data-sale-unit") || "0") || 0;

        refInput.value = saleRef;
        title.textContent = `Devolução • ${itemName}`;
        badge.textContent = `${itemName} • ${clientName}`;
        qtyInput.value = "";
        qtyInput.max = balance;
        qtyInput.placeholder = `Quantidade (${typeLabel})`;
        saldoPreview.textContent = Number(balance || "0").toLocaleString("pt-BR", { minimumFractionDigits: 3, maximumFractionDigits: 3 });
        totalPreview.textContent = money(0);

        modal.setAttribute("aria-hidden", "false");
        modal.classList.add("is-open");
      });
    });

    qtyInput.addEventListener("input", syncTotal);
    closeBtn.addEventListener("click", closeModal);
    cancelBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });
  }

  function bindLotCreateModal() {
    const modal = document.getElementById("lotCreateModal");
    const openButtons = Array.from(document.querySelectorAll("[data-lot-create-open]"));
    const closeBtn = document.getElementById("lotCreateModalClose");
    const cancelBtn = document.getElementById("lotCreateModalCancel");
    const form = document.getElementById("lotCreateForm");
    const results = document.getElementById("lotCreateFornecedorResults");
    const search = document.getElementById("lotCreateFornecedorSearch");
    const supplierIdInput = document.getElementById("lotCreateFornecedorId");
    const previewTitle = document.getElementById("lotCreatePreviewTitle");
    const previewSupplier = document.getElementById("lotCreatePreviewSupplier");
    const previewProcess = document.getElementById("lotCreatePreviewProcess");
    const titleInput = document.getElementById("lotCreateTitulo");
    const processInput = document.getElementById("lotCreateNumeroProcesso");
    const sinistroInput = document.getElementById("lotCreateNumeroSinistro");
    const totalCostsInput = document.getElementById("lotCreateCustosTotal");
    if (!modal || !openButtons.length || !closeBtn || !cancelBtn || !form) return;
    const inlineAlert = form.querySelector(".lot-create-modal__alert");

    const formDefaults = parseJsonAttr(form, "data-lot-create-defaults", {}) || {};

    function syncPreview() {
      const titleValue = String(titleInput?.value || "").trim();
      const processoValue = String(processInput?.value || "").trim();
      const sinistroValue = String(sinistroInput?.value || "").trim();
      if (previewTitle) previewTitle.textContent = titleValue || "Novo lote sem título";
      if (previewSupplier) {
        previewSupplier.textContent = String(search?.value || "").trim() || "Selecione um fornecedor";
      }
      if (previewProcess) {
        if (processoValue && sinistroValue) previewProcess.textContent = `${processoValue} / ${sinistroValue}`;
        else if (processoValue) previewProcess.textContent = processoValue;
        else if (sinistroValue) previewProcess.textContent = `Sinistro ${sinistroValue}`;
        else previewProcess.textContent = "Sem processo definido";
      }
    }

    function syncCostsTotal() {
      if (!totalCostsInput) return;
      const names = ["custo_armazenagem", "custo_carregamento", "custo_sos", "outros_custos"];
      const total = names.reduce((sum, name) => sum + decimalInput(form.elements.namedItem(name)?.value || ""), 0);
      totalCostsInput.value = moneyBR(total);
    }

    function resetFormState() {
      Object.entries(formDefaults).forEach(([name, value]) => {
        const field = form.elements.namedItem(name);
        if (field && "value" in field) {
          field.value = String(value ?? "");
        }
      });
      if (search) search.value = String(formDefaults.fornecedor_search || "");
      if (supplierIdInput) supplierIdInput.value = String(formDefaults.fornecedor_id || "");
      if (results) {
        results.hidden = true;
        results.innerHTML = "";
      }
      bindMoneyInputs(form);
      bindMaskedInputs(form);
      bindUppercaseInputs(form);
      syncPreview();
      syncCostsTotal();
    }

    function hasUnsavedData() {
      if (!modal.classList.contains("is-open")) {
        return false;
      }
      return Object.entries(formDefaults).some(([name, value]) => {
        const field = form.elements.namedItem(name);
        const current = field && "value" in field ? String(field.value || "").trim() : "";
        return current !== String(value ?? "").trim();
      });
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
      if (inlineAlert) inlineAlert.remove();
      resetFormState();
    }

    function openModal() {
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
    }

    async function requestClose() {
      if (hasUnsavedData() && window.UIComponents && typeof window.UIComponents.confirm === "function") {
        const confirmed = await window.UIComponents.confirm({
          eyebrow: "Alterações pendentes",
          title: "Fechar cadastro de lote sem salvar?",
          message: "Há dados preenchidos neste lote. Se continuar, as informações digitadas serão descartadas.",
          confirmLabel: "Fechar sem salvar",
          cancelLabel: "Continuar editando",
        });
        if (!confirmed) return;
      }
      closeModal();
    }

    openButtons.forEach((button) => {
      button.addEventListener("click", () => {
        openModal();
      });
    });

    closeBtn.addEventListener("click", () => {
      requestClose().catch(() => {});
    });
    cancelBtn.addEventListener("click", () => {
      requestClose().catch(() => {});
    });
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        requestClose().catch(() => {});
      }
    });

    window.__lotCreateModalHasUnsavedData = hasUnsavedData;

    return { openModal };
  }

  function bindLotCreateFornecedorLookup() {
    const form = document.getElementById("lotCreateForm");
    const lookup = document.getElementById("lotCreateFornecedorLookup");
    const search = document.getElementById("lotCreateFornecedorSearch");
    const results = document.getElementById("lotCreateFornecedorResults");
    const supplierIdInput = document.getElementById("lotCreateFornecedorId");
    const titleInput = document.getElementById("lotCreateTitulo");
    const processInput = document.getElementById("lotCreateNumeroProcesso");
    const sinistroInput = document.getElementById("lotCreateNumeroSinistro");
    const previewTitle = document.getElementById("lotCreatePreviewTitle");
    const previewSupplier = document.getElementById("lotCreatePreviewSupplier");
    const previewProcess = document.getElementById("lotCreatePreviewProcess");
    const allMoneyInputs = Array.from(document.querySelectorAll("#lotCreateForm [data-lot-money]"));
    const costMoneyInputs = [
      document.getElementById("lotCreateCustoArmazenagem"),
      document.getElementById("lotCreateCustoCarregamento"),
      document.getElementById("lotCreateCustoSos"),
      document.getElementById("lotCreateOutrosCustos"),
    ].filter(Boolean);
    const totalCostsInput = document.getElementById("lotCreateCustosTotal");
    if (!form || !lookup || !search || !results || !supplierIdInput) {
      return;
    }

    const cadastroSource = parseJsonAttr(lookup, "data-lot-cadastro-source", []);

    function openInlineCadastro(tipo = "fornecedor", title = "Novo fornecedor") {
      const inlineModal = document.getElementById("lotCadastroInlineModal");
      const inlineFrame = document.getElementById("lotCadastroInlineFrame");
      const inlineTitle = document.getElementById("lotCadastroInlineTitle");
      if (!inlineModal || !inlineFrame || !inlineTitle) return;

      inlineTitle.textContent = title;
      inlineFrame.setAttribute("src", appUrl(`/app/templates/cadastros_ficha_embed.php?modo=cadastro&tipo=${encodeURIComponent(tipo)}&embed=1`));
      inlineModal.setAttribute("aria-hidden", "false");
      inlineModal.classList.add("is-open");
    }

    function setSelectedSupplier(item) {
      if (!item) {
        supplierIdInput.value = "";
        search.value = "";
        results.hidden = true;
        results.innerHTML = "";
        if (previewSupplier) previewSupplier.textContent = "Selecione um fornecedor";
        return;
      }

      supplierIdInput.value = String(item.id || "");
      search.value = String(item.nome || "");
      results.hidden = true;
      results.innerHTML = "";
      if (previewSupplier) previewSupplier.textContent = String(item.nome || "Selecione um fornecedor");
    }

    function syncPreview() {
      const titleValue = String(titleInput?.value || "").trim();
      const processoValue = String(processInput?.value || "").trim();
      const sinistroValue = String(sinistroInput?.value || "").trim();
      if (previewTitle) {
        previewTitle.textContent = titleValue || "Novo lote sem título";
      }
      if (previewProcess) {
        if (processoValue && sinistroValue) previewProcess.textContent = `${processoValue} / ${sinistroValue}`;
        else if (processoValue) previewProcess.textContent = processoValue;
        else if (sinistroValue) previewProcess.textContent = `Sinistro ${sinistroValue}`;
        else previewProcess.textContent = "Sem processo definido";
      }
      if (previewSupplier && !supplierIdInput.value) {
        previewSupplier.textContent = String(search.value || "").trim() || "Selecione um fornecedor";
      }
    }

    function syncSupplierFromPersistedState() {
      const persistedId = String(supplierIdInput.value || "").trim();
      if (!persistedId) {
        syncPreview();
        return;
      }
      const matched = cadastroSource.find((item) => String(item.id || "") === persistedId);
      if (matched) {
        setSelectedSupplier(matched);
        return;
      }
      syncPreview();
    }

    function resolveSupplierFromSearch() {
      const rawTerm = String(search.value || "").trim();
      const term = normalize(rawTerm);
      if (!term) return null;

      const exact = cadastroSource.find((item) => normalize(item.nome || "") === term || normalize(item.documento || "") === term);
      if (exact) return exact;

      const startsWith = cadastroSource.filter((item) => normalize(item.nome || "").startsWith(term) || String(item.searchIndex || "").startsWith(term));
      if (startsWith.length === 1) return startsWith[0];

      const includes = cadastroSource.filter((item) => String(item.searchIndex || "").includes(term));
      if (includes.length === 1) return includes[0];

      return null;
    }

    function renderResults(items) {
      if (!items.length) {
        results.innerHTML = `
          <button type="button" class="lot-search-suggest__item" data-lot-create-supplier aria-live="polite">
            <span class="lot-search-suggest__icon"><i class="fa-solid fa-building-circle-check" aria-hidden="true"></i></span>
            <span class="lot-search-suggest__body">
              <strong>Fornecedor não encontrado</strong>
              <span>Clique aqui para cadastrar um novo fornecedor sem sair do lançamento.</span>
            </span>
          </button>
        `;
        results.hidden = false;
        return;
      }

      results.innerHTML = items.map((item) => `
        <button type="button" class="lot-search-suggest__item" data-lot-fornecedor-pick="${String(item.id || "")}">
          <span class="lot-search-suggest__icon"><i class="fa-solid fa-id-card" aria-hidden="true"></i></span>
          <span class="lot-search-suggest__body">
            <strong>${String(item.nome || "")}</strong>
            <span>${String(item.documento || item.celular || "Telefone não informado")}</span>
          </span>
        </button>
      `).join("");
      results.hidden = false;
    }

    search.addEventListener("input", () => {
      const term = normalize(search.value);
      if (!term) {
        results.hidden = true;
        results.innerHTML = "";
        syncPreview();
        return;
      }
      supplierIdInput.value = "";
      const items = cadastroSource.filter((item) => String(item.searchIndex || "").includes(term)).slice(0, 8);
      renderResults(items);
      syncPreview();
    });

    search.addEventListener("blur", () => {
      window.setTimeout(() => {
        results.hidden = true;
      }, 120);
    });

    results.addEventListener("pointerdown", (event) => {
      const trigger = event.target instanceof Element ? event.target.closest("[data-lot-create-supplier], [data-lot-fornecedor-pick]") : null;
      if (!trigger) return;
      event.preventDefault();

      if (trigger.hasAttribute("data-lot-create-supplier")) {
        openInlineCadastro("fornecedor", "Novo fornecedor");
        return;
      }

      const id = String(trigger.getAttribute("data-lot-fornecedor-pick") || "");
      const item = cadastroSource.find((entry) => String(entry.id || "") === id);
      setSelectedSupplier(item || null);
    });

    results.addEventListener("click", (event) => {
      const trigger = event.target instanceof Element ? event.target.closest("[data-lot-create-supplier], [data-lot-fornecedor-pick]") : null;
      if (!trigger) return;

      if (trigger.hasAttribute("data-lot-create-supplier")) {
        openInlineCadastro("fornecedor", "Novo fornecedor");
      }
    });

    window.addEventListener("lot:inline-cadastro-saved", (event) => {
      const item = event.detail || {};
      const id = String(item.id || "");
      if (!id) return;

      const nextEntry = {
        id: Number(item.id || 0),
        nome: String(item.nome || ""),
        documento: String(item.documento || ""),
        celular: String(item.celular || ""),
        searchIndex: normalize([item.nome, item.documento, item.celular].join(" ")),
      };
      const existingIndex = cadastroSource.findIndex((entry) => String(entry.id || "") === id);
      if (existingIndex >= 0) {
        cadastroSource.splice(existingIndex, 1, nextEntry);
      } else {
        cadastroSource.unshift(nextEntry);
      }
      setSelectedSupplier(nextEntry);
    });

    titleInput?.addEventListener("input", syncPreview);
    processInput?.addEventListener("input", syncPreview);
    sinistroInput?.addEventListener("input", syncPreview);
    form.addEventListener("submit", (event) => {
      if (String(supplierIdInput.value || "").trim() !== "") {
        return;
      }

      const matchedSupplier = resolveSupplierFromSearch();
      if (matchedSupplier) {
        setSelectedSupplier(matchedSupplier);
        return;
      }

      event.preventDefault();
      renderResults([]);
      search.focus();
      waitAndToast("warning", "Selecione a seguradora/fornecedor na busca antes de criar o lote.");
    });

    function syncCostsTotal() {
      if (!totalCostsInput) return;
      const total = costMoneyInputs.reduce((sum, input) => sum + decimalInput(input.value), 0);
      totalCostsInput.value = moneyBR(total);
    }

    allMoneyInputs.forEach((input) => {
      input.addEventListener("focus", () => {
        const current = decimalInput(input.value);
        input.value = current > 0 ? current.toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : "";
      });
      input.addEventListener("blur", () => {
        input.value = moneyBR(decimalInput(input.value));
        syncCostsTotal();
      });
      input.addEventListener("input", syncCostsTotal);
    });

    syncSupplierFromPersistedState();
    syncCostsTotal();
  }

  function bindLotAttachmentsModal() {
    const modal = document.getElementById("lotAttachmentsModal");
    const page = document.querySelector(".lot-page");
    const openButtons = Array.from(document.querySelectorAll("[data-lot-attachments-open]"));
    const closeBtn = document.getElementById("lotAttachmentsModalClose");
    const title = document.getElementById("lotAttachmentsModalTitle");
    const groupLabel = document.getElementById("lotAttachmentsModalGroup");
    const description = document.getElementById("lotAttachmentsModalDescription");
    const empty = document.getElementById("lotAttachmentsEmpty");
    const thumbs = document.getElementById("lotAttachmentsThumbs");
    const uploadGroup = document.getElementById("lotAttachmentUploadGroup");
    const uploadInput = document.getElementById("lotAttachmentUploadInput");
    const removeGroup = document.getElementById("lotAttachmentRemoveGroup");
    const removeRelation = document.getElementById("lotAttachmentRemoveRelation");
    const removeName = document.getElementById("lotAttachmentRemoveName");
    const removeForm = document.getElementById("lotAttachmentRemoveForm");
    if (!modal || !page || !openButtons.length || !closeBtn || !title || !groupLabel || !description || !empty || !thumbs || !uploadGroup || !uploadInput || !removeGroup || !removeRelation || !removeName || !removeForm) {
      return;
    }

    const groups = parseJsonAttr(page, "data-lot-attachment-groups", {});
    let currentGroupKey = "";

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
    }

    function currentGroup() {
      if (groups && typeof groups === "object" && groups[currentGroupKey]) {
        return groups[currentGroupKey];
      }
      return null;
    }

    function currentItems() {
      const group = currentGroup();
      return Array.isArray(group?.items) ? group.items : [];
    }

    function renderStage() {
      const group = currentGroup();
      const items = currentItems();

      title.textContent = group?.title || "Anexos do lote";
      groupLabel.textContent = group?.title || "Anexos";
      description.textContent = group?.description || "Documentos, imagens e arquivos organizados para consulta rápida.";
      uploadGroup.value = currentGroupKey;
      removeGroup.value = currentGroupKey;

      if (!Array.isArray(items) || items.length === 0) {
        removeRelation.value = "";
        removeName.value = "";
        empty.hidden = false;
        empty.textContent = group?.empty || "Adicione anexos para começar a galeria desta seção.";
        thumbs.innerHTML = "";
        return;
      }

      empty.hidden = true;

      thumbs.innerHTML = items.map((attachment, index) => {
        const typeLabel = attachment.isImage ? "Imagem" : (attachment.isPdf ? "PDF" : "Documento");
        const sizeLabel = formatBytes(attachment.tamanhoBytes || attachment.sizeBytes || 0);
        return `
        <article class="sv-attachments__item">
          <button type="button" class="sv-attachments__thumb" data-lot-attachment-preview="${index}">
            ${attachment.isImage && attachment.thumbUrl
              ? `<img src="${escapeHtml(attachment.thumbUrl)}" alt="${escapeHtml(attachment.displayName || "Arquivo")}">`
              : `<span class="sv-attachments__thumbicon"><i class="${attachment.isPdf ? "fa-regular fa-file-pdf" : "fa-regular fa-file-lines"}" aria-hidden="true"></i></span>`}
          </button>
          <div class="sv-attachments__meta">
            <div class="sv-attachments__name">${escapeHtml(attachment.displayName || "Arquivo")}</div>
            <div class="sv-attachments__inforow">
              <span class="sv-attachments__info sv-attachments__infoitem">${escapeHtml(typeLabel)}</span>
              <span class="sv-attachments__infosep" aria-hidden="true">•</span>
              <span class="sv-attachments__info sv-attachments__infoitem">${escapeHtml(sizeLabel)}</span>
            </div>
          </div>
          <div class="sv-attachments__foot">
            <span class="sv-attachments__badge">${escapeHtml(typeLabel)}</span>
            <button type="button" class="sv-attachments__remove" data-lot-attachment-remove="${index}" aria-label="Remover anexo">
              <i class="fa-solid fa-trash" aria-hidden="true"></i>
            </button>
          </div>
        </article>
      `;
      }).join("");
    }

    function formatBytes(bytes) {
      const value = Number(bytes || 0);
      if (value <= 0) return "0 B";
      const units = ["B", "KB", "MB", "GB"];
      let size = value;
      let unitIndex = 0;
      while (size >= 1024 && unitIndex < units.length - 1) {
        size /= 1024;
        unitIndex += 1;
      }
      return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
    }

    function viewerItems() {
      return currentItems().map((attachment) => ({
        name: String(attachment.displayName || "Arquivo"),
        previewUrl: String(attachment.previewUrl || ""),
        downloadUrl: String(attachment.downloadUrl || ""),
        isImage: Boolean(attachment.isImage),
        isPdf: Boolean(attachment.isPdf),
        extension: String(attachment.extensao || attachment.extension || ""),
      }));
    }

    function submitRemove(index) {
      const items = currentItems();
      const item = items[index] || null;
      if (!item) return;

      removeRelation.value = String(item.relacaoId || "");
      removeName.value = String(item.displayName || "");

      const proceed = () => removeForm.submit();
      if (!window.UIComponents || typeof window.UIComponents.confirm !== "function") {
        proceed();
        return;
      }

      window.UIComponents.confirm({
        eyebrow: "Remover anexo",
        title: "Excluir o arquivo selecionado?",
        message: "O anexo será removido deste grupo do lote. Essa ação pode ser refeita apenas com novo envio.",
        confirmLabel: "Remover arquivo",
        cancelLabel: "Cancelar",
      }).then((confirmed) => {
        if (confirmed) proceed();
      });
    }

    function openViewer(index) {
      if (!window.AttachmentsUI || typeof window.AttachmentsUI.openViewer !== "function") return;
      const items = viewerItems();
      if (!items[index]) return;
      window.AttachmentsUI.openViewer(items, index);
    }

    function openModal(groupKey) {
      currentGroupKey = groupKey;
      renderStage();
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
    }

    openButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const groupKey = button.getAttribute("data-lot-attachments-open") || "";
        openModal(groupKey);
      });
    });

    thumbs.addEventListener("click", (event) => {
      const previewTrigger = event.target instanceof Element ? event.target.closest("[data-lot-attachment-preview]") : null;
      if (previewTrigger) {
        const nextIndex = Number(previewTrigger.getAttribute("data-lot-attachment-preview") || "0");
        if (Number.isFinite(nextIndex)) {
          openViewer(nextIndex);
        }
        return;
      }

      const removeTrigger = event.target instanceof Element ? event.target.closest("[data-lot-attachment-remove]") : null;
      if (removeTrigger) {
        const nextIndex = Number(removeTrigger.getAttribute("data-lot-attachment-remove") || "0");
        if (Number.isFinite(nextIndex)) {
          submitRemove(nextIndex);
        }
      }
    });

    uploadInput.addEventListener("change", () => {
      if (!uploadInput.files || uploadInput.files.length === 0) return;
      uploadInput.form?.requestSubmit();
    });

    closeBtn.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        closeModal();
      }
    });

    return { openModal };
  }

  function bindInlineCadastroModal() {
    const modal = document.getElementById("lotCadastroInlineModal");
    const title = document.getElementById("lotCadastroInlineTitle");
    const closeBtn = document.getElementById("lotCadastroInlineClose");
    const inlineFrame = document.getElementById("lotCadastroInlineFrame");
    if (!modal || !closeBtn || !inlineFrame) return;
    let hasDirtyChanges = false;

    function openCadastro(url, heading = "Ficha do cadastro") {
      if (title) title.textContent = heading;
      inlineFrame.setAttribute("src", url);
      modal.setAttribute("aria-hidden", "false");
      modal.classList.add("is-open");
      hasDirtyChanges = false;
    }

    function closeModal() {
      modal.setAttribute("aria-hidden", "true");
      modal.classList.remove("is-open");
      inlineFrame.setAttribute("src", "about:blank");
      hasDirtyChanges = false;
    }

    async function requestClose() {
      if (hasDirtyChanges && window.UIComponents && typeof window.UIComponents.confirm === "function") {
        const confirmed = await window.UIComponents.confirm({
          eyebrow: "Alterações pendentes",
          title: "Fechar cadastro sem salvar?",
          message: "Há alterações não salvas neste cadastro. Se continuar, os dados preenchidos serão descartados.",
          confirmLabel: "Fechar sem salvar",
          cancelLabel: "Continuar editando",
        });
        if (!confirmed) return;
      }
      closeModal();
    }

    closeBtn.addEventListener("click", () => {
      requestClose().catch(() => {});
    });
    modal.addEventListener("click", (event) => {
      if (event.target === modal) {
        requestClose().catch(() => {});
      }
    });

    window.addEventListener("message", (event) => {
      if (event.origin !== window.location.origin) return;
      const payload = event.data || null;
      if (!payload || typeof payload.type !== "string") return;

      if (payload.type === "sv:inline-cadastro-state") {
        hasDirtyChanges = Boolean(payload.dirty);
        return;
      }

      if (payload.type === "sv:inline-cadastro-request-close") {
        closeModal();
        return;
      }

      if (payload.type !== "sv:inline-cadastro-saved") return;

      window.dispatchEvent(new CustomEvent("lot:inline-cadastro-saved", {
        detail: payload.cadastro || {},
      }));
      waitAndToast("success", payload.saved === "updated" ? "Cadastro atualizado com sucesso." : "Cadastro criado com sucesso.");
      closeModal();
    });

    return { openCadastro };
  }

  function bindCompatibleClients(api) {
    const triggers = Array.from(document.querySelectorAll("[data-lot-compatible-open]"));
    const refreshButtons = Array.from(document.querySelectorAll("[data-lot-compatible-refresh]"));

    triggers.forEach((trigger) => {
      const open = () => {
        const id = String(trigger.getAttribute("data-lot-compatible-open") || "");
        if (!id) return;
        if (window.CadastrosListagem && typeof window.CadastrosListagem.openModalById === "function") {
          window.CadastrosListagem.openModalById(id);
          return;
        }
        if (!api || typeof api.openCadastro !== "function") return;
        const name = String(trigger.getAttribute("data-lot-compatible-name") || "Cliente");
        api.openCadastro(
          appUrl(`/app/templates/cadastros_ficha_embed.php?id=${encodeURIComponent(id)}&embed=1`),
          `Ficha do cliente • ${name}`
        );
      };

      trigger.addEventListener("click", open);
      trigger.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          event.preventDefault();
          open();
        }
      });
    });

    refreshButtons.forEach((button) => {
      button.addEventListener("click", () => {
        const href = String(button.getAttribute("data-refresh-href") || "");
        waitAndToast("info", "Atualizando a leitura de clientes compatíveis...");
        window.location.href = href || window.location.href;
      });
    });
  }

  function bindLotFreightLookup(api) {
    const lookup = document.getElementById("lotFreightLookup");
    const search = document.getElementById("lotFreightSearch");
    const results = document.getElementById("lotFreightResults");
    const selectionBlock = document.getElementById("lotFreightSelectionBlock");
    const cadastroIdInput = document.getElementById("lotFreightCadastroId");
    const linkForm = document.getElementById("lotFreightLinkForm");
    const saveForm = document.getElementById("lotFreightDataForm");
    const selectedCadastroInput = document.getElementById("lotFreightSelectedCadastroId");
    if (!lookup || !search || !results || !cadastroIdInput || !linkForm || !saveForm || !selectedCadastroInput) return;

    const source = parseJsonAttr(lookup, "data-lot-freight-source", []);

    function ensureFreightCard() {
      let card = document.getElementById("lotFreightCard");
      if (card) return card;

      card = document.createElement("article");
      card.className = "lot-freight-card";
      card.id = "lotFreightCard";
      card.innerHTML = `
        <div class="lot-freight-card__visual">
          <i id="lotFreightCardIcon" class="fa-solid fa-id-card" aria-hidden="true"></i>
        </div>
        <div class="lot-freight-card__body">
          <span class="lot-freight-card__eyebrow" id="lotFreightCardEyebrow">Frete</span>
          <strong id="lotFreightCardName">Cadastro</strong>
          <div class="lot-freight-card__meta">
            <span id="lotFreightCardPhone">Não informado</span>
            <span id="lotFreightCardCpf" hidden></span>
            <span id="lotFreightCardCityState" hidden></span>
            <span id="lotFreightCardCnh" hidden></span>
            <span id="lotFreightCardVeiculo" hidden></span>
            <span id="lotFreightCardPlaca" hidden></span>
          </div>
        </div>
        <div class="lot-freight-card__actions">
          <button class="fin-btn fin-btn--ghost" type="button" id="lotFreightOpenBtn" data-lot-freight-open="0" data-lot-freight-name="Cadastro">Ver ficha</button>
          <button class="fin-btn fin-btn--ghost" type="button" data-lot-freight-focus-search data-lot-editor-edit-only hidden>Trocar</button>
          <button class="fin-btn" type="submit" form="lotFreightUnlinkForm" data-lot-editor-edit-only hidden>Cancelar vínculo</button>
        </div>
      `;

      const empty = document.getElementById("lotFreightEmpty");
      const fieldset = saveForm.querySelector("[data-lot-editor-fields]");
      if (empty && empty.parentNode) {
        empty.parentNode.insertBefore(card, empty.nextSibling);
      } else if (fieldset && fieldset.parentNode) {
        fieldset.parentNode.insertBefore(card, fieldset);
      }

      const openBtn = card.querySelector("#lotFreightOpenBtn");
      if (openBtn instanceof HTMLElement) {
        openBtn.addEventListener("click", () => {
          const id = String(openBtn.getAttribute("data-lot-freight-open") || "");
          if (!id) return;
          if (window.CadastrosListagem && typeof window.CadastrosListagem.openModalById === "function") {
            window.CadastrosListagem.openModalById(id);
            return;
          }
          if (!api || typeof api.openCadastro !== "function") return;
          const name = String(openBtn.getAttribute("data-lot-freight-name") || "Cadastro");
          api.openCadastro(
            appUrl(`/app/templates/cadastros_ficha_embed.php?id=${encodeURIComponent(id)}&embed=1`),
            `Ficha do frete • ${name}`
          );
        });
      }

      const focusBtn = card.querySelector("[data-lot-freight-focus-search]");
      if (focusBtn instanceof HTMLElement) {
        focusBtn.addEventListener("click", () => {
          search.focus();
          search.scrollIntoView({ behavior: "smooth", block: "center" });
        });
      }

      return card;
    }

    function setMetaText(id, value, prefix = "") {
      const node = document.getElementById(id);
      if (!(node instanceof HTMLElement)) return;
      const text = String(value || "").trim();
      node.hidden = text === "";
      if (text !== "") {
        node.textContent = prefix ? `${prefix}${text}` : text;
      }
    }

    function renderSelectedFreightCard(item) {
      const empty = document.getElementById("lotFreightEmpty");
      const card = ensureFreightCard();
      if (!(card instanceof HTMLElement)) return;

      if (!item) {
        card.hidden = true;
        if (empty instanceof HTMLElement) empty.hidden = false;
        if (selectionBlock instanceof HTMLElement) selectionBlock.hidden = false;
        return;
      }

      if (empty instanceof HTMLElement) empty.hidden = true;
      card.hidden = false;
      if (selectionBlock instanceof HTMLElement) selectionBlock.hidden = true;

      const icon = document.getElementById("lotFreightCardIcon");
      if (icon instanceof HTMLElement) {
        icon.className = String(item.kind || "") === "transportadora" ? "fa-solid fa-building-user" : "fa-solid fa-id-card";
      }
      const eyebrow = document.getElementById("lotFreightCardEyebrow");
      if (eyebrow instanceof HTMLElement) eyebrow.textContent = String(item.tipo || "Frete");
      const name = document.getElementById("lotFreightCardName");
      if (name instanceof HTMLElement) name.textContent = String(item.nome || "Cadastro");
      const phone = document.getElementById("lotFreightCardPhone");
      if (phone instanceof HTMLElement) phone.textContent = String(item.telefone || "Não informado");
      setMetaText("lotFreightCardCpf", item.documento, `${String(item.documentoLabel || "CPF")} `);
      setMetaText("lotFreightCardCityState", [item.cidade, item.estado].filter(Boolean).join(" / "));
      setMetaText("lotFreightCardCnh", item.cnh, "CNH ");
      setMetaText("lotFreightCardVeiculo", item.veiculo);
      setMetaText("lotFreightCardPlaca", item.placa, "Placa ");

      const openBtn = document.getElementById("lotFreightOpenBtn");
      if (openBtn instanceof HTMLElement) {
        openBtn.setAttribute("data-lot-freight-open", String(item.id || ""));
        openBtn.setAttribute("data-lot-freight-name", String(item.nome || "Cadastro"));
      }
    }

    function syncFreightTotal() {
      const totalInput = document.getElementById("lotFreightValorTotal");
      if (!totalInput) return;
      const names = ["valor_frete", "valor_documentacao", "valor_impostos", "valor_outros_frete"];
      const total = names.reduce((sum, name) => sum + decimalInput(saveForm.ownerDocument.querySelector(`[name="${name}"]`)?.value || ""), 0);
      totalInput.value = moneyBR(total);
    }

    function render(items) {
      if (!items.length) {
        results.innerHTML = `
          <button type="button" class="lot-search-suggest__item" data-lot-freight-create="motorista">
            <span class="lot-search-suggest__icon"><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
            <span class="lot-search-suggest__body">
              <strong>Cadastrar motorista</strong>
              <span>Crie um novo motorista sem sair do lote.</span>
            </span>
          </button>
          <button type="button" class="lot-search-suggest__item" data-lot-freight-create="transportadora">
            <span class="lot-search-suggest__icon"><i class="fa-solid fa-building-circle-arrow-right" aria-hidden="true"></i></span>
            <span class="lot-search-suggest__body">
              <strong>Cadastrar transportadora</strong>
              <span>Crie uma nova transportadora sem sair da página.</span>
            </span>
          </button>
        `;
        results.hidden = false;
        return;
      }

      results.innerHTML = items.map((item) => `
        <button type="button" class="lot-search-suggest__item" data-lot-freight-pick="${escapeHtml(String(item.id || ""))}">
          <span class="lot-search-suggest__icon"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></span>
          <span class="lot-search-suggest__body">
            <strong>${escapeHtml(String(item.nome || ""))}</strong>
            <span>${escapeHtml(String(item.tipo || "Sem tipo"))} • ${escapeHtml(String(item.telefone || "Não informado"))}${String(item.cidade || "").trim() ? ` • ${escapeHtml(String(item.cidade || ""))}${String(item.estado || "").trim() ? ` / ${escapeHtml(String(item.estado || ""))}` : ""}` : ""}</span>
          </span>
        </button>
      `).join("");
      results.hidden = false;
    }

    function openInlineCadastro(tipo = "motorista") {
      if (!api || typeof api.openCadastro !== "function") return;
      lookup.dataset.pendingCreateType = tipo;
      api.openCadastro(
        appUrl(`/app/templates/cadastros_ficha_embed.php?modo=cadastro&tipo=${encodeURIComponent(tipo)}&embed=1`),
        tipo === "transportadora" ? "Nova transportadora" : "Novo motorista"
      );
    }

    search.addEventListener("input", () => {
      const term = normalize(search.value);
      if (!term) {
        results.hidden = true;
        results.innerHTML = "";
        cadastroIdInput.value = "";
        return;
      }
      const items = source.filter((item) => String(item.searchIndex || "").includes(term)).slice(0, 8);
      render(items);
    });

    search.addEventListener("blur", () => {
      window.setTimeout(() => {
        results.hidden = true;
      }, 120);
    });

    results.addEventListener("pointerdown", (event) => {
      const createBtn = event.target instanceof Element ? event.target.closest("[data-lot-freight-create]") : null;
      const pickBtn = event.target instanceof Element ? event.target.closest("[data-lot-freight-pick]") : null;
      if (!createBtn && !pickBtn) return;
      event.preventDefault();

      if (createBtn) {
        openInlineCadastro(String(createBtn.getAttribute("data-lot-freight-create") || "motorista"));
        return;
      }

      const id = String(pickBtn?.getAttribute("data-lot-freight-pick") || "");
      const item = source.find((entry) => String(entry.id || "") === id);
      if (!item) return;
      cadastroIdInput.value = id;
      selectedCadastroInput.value = id;
      search.value = String(item.nome || "");
      results.hidden = true;
      results.innerHTML = "";
      renderSelectedFreightCard(item);
    });

    document.querySelectorAll("[data-lot-freight-open]").forEach((trigger) => {
      const open = () => {
        const id = String(trigger.getAttribute("data-lot-freight-open") || "");
        if (!id) return;
        if (window.CadastrosListagem && typeof window.CadastrosListagem.openModalById === "function") {
          window.CadastrosListagem.openModalById(id);
          return;
        }
        if (!api || typeof api.openCadastro !== "function") return;
        const name = String(trigger.getAttribute("data-lot-freight-name") || "Cadastro");
        api.openCadastro(
          appUrl(`/app/templates/cadastros_ficha_embed.php?id=${encodeURIComponent(id)}&embed=1`),
          `Ficha do frete • ${name}`
        );
      };
      trigger.addEventListener("click", open);
    });

    document.querySelectorAll("[data-lot-freight-focus-search]").forEach((button) => {
      button.addEventListener("click", () => {
        if (selectionBlock instanceof HTMLElement) selectionBlock.hidden = false;
        search.focus();
        search.scrollIntoView({ behavior: "smooth", block: "center" });
      });
    });

    window.addEventListener("lot:inline-cadastro-saved", (event) => {
      const pendingType = String(lookup.dataset.pendingCreateType || "").trim();
      if (!pendingType) return;

      const item = event.detail || {};
      const id = String(item.id || "");
      if (!id) return;

      const nextEntry = {
        id: Number(item.id || 0),
        nome: String(item.nome || item.razaoSocial || ""),
        tipo: pendingType === "transportadora" ? "Transportadora" : "Motorista",
        telefone: String(item.whatsapp || item.telefone || item.celular || ""),
        documento: String(item.documento || ""),
        documentoLabel: pendingType === "transportadora" ? "CNPJ" : "CPF",
        cidade: String(item.cidade || ""),
        estado: String(item.estado || ""),
        cnh: String(item.cnh || ""),
        veiculo: String(item.veiculo || ""),
        placa: String(item.placa || ""),
        searchIndex: normalize([
          item.nome,
          item.razaoSocial,
          pendingType === "transportadora" ? "Transportadora" : "Motorista",
          item.documento,
          item.telefone,
          item.celular,
          item.whatsapp,
          item.cidade,
          item.estado,
        ].join(" ")),
      };

      const existingIndex = source.findIndex((entry) => String(entry.id || "") === id);
      if (existingIndex >= 0) {
        source.splice(existingIndex, 1, nextEntry);
      } else {
        source.unshift(nextEntry);
      }

      cadastroIdInput.value = id;
      selectedCadastroInput.value = id;
      search.value = String(nextEntry.nome || "");
      results.hidden = true;
      results.innerHTML = "";
      lookup.dataset.pendingCreateType = "";
      renderSelectedFreightCard(nextEntry);
    });

    selectedCadastroInput.addEventListener("input", () => {
      const id = String(selectedCadastroInput.value || "").trim();
      if (!id) {
        renderSelectedFreightCard(null);
        return;
      }
      const item = source.find((entry) => String(entry.id || "") === id);
      if (item) renderSelectedFreightCard(item);
    });

    const initialId = String(selectedCadastroInput.value || "").trim();
    if (initialId) {
      const initialItem = source.find((entry) => String(entry.id || "") === initialId);
      if (initialItem) renderSelectedFreightCard(initialItem);
    }

    ["valor_frete", "valor_documentacao", "valor_impostos", "valor_outros_frete"].forEach((name) => {
      const field = document.querySelector(`[name="${name}"]`);
      if (!(field instanceof HTMLInputElement)) return;
      field.addEventListener("input", syncFreightTotal);
      field.addEventListener("lot:money-change", syncFreightTotal);
    });

    syncFreightTotal();
  }

  async function syncCrRowsFromPage() {
    const page = document.querySelector(".lot-page[data-lot-cr-sync]");
    if (!page) return;
    const rows = parseJsonAttr(page, "data-lot-cr-sync", []);
    if (!rows.length || !window.FinStore?.cr?.setRows) return;
    try {
      await (window.FinStore?.ready?.() ?? window.FinStore?.init?.() ?? true);
    } catch (_) {}
    try {
      const currentRows = Array.isArray(window.FinStore.cr.getRows?.()) ? window.FinStore.cr.getRows() : [];
      const byId = new Map();
      currentRows.forEach((row) => {
        const id = String(row?.id || "");
        if (!id) return;
        byId.set(id, row);
      });
      rows.forEach((row) => {
        const id = String(row?.id || "");
        if (!id) return;
        byId.set(id, row);
      });
      await window.FinStore.cr.setRows(Array.from(byId.values()));
    } catch (_) {}
  }

  function centerTimelineOnMobile() {
    if (window.innerWidth > MOBILE_BREAKPOINT) return;

    const timeline = document.querySelector(".lot-process-timeline");
    const current = timeline?.querySelector("[data-lot-timeline-current]");
    if (!timeline || !current) return;

    const targetLeft = current.offsetLeft - ((timeline.clientWidth - current.clientWidth) / 2);
    timeline.scrollLeft = Math.max(0, targetLeft);
  }

  function hasPendingLotChanges() {
    const editorDirty = typeof window.__lotHasPendingEditorChanges === "function"
      ? Boolean(window.__lotHasPendingEditorChanges())
      : Array.from(document.querySelectorAll("[data-lot-editor-form]")).some((form) => (
        form instanceof HTMLFormElement && form.dataset.lotEditorDirty === "1"
      ));

    const createDirty = typeof window.__lotCreateModalHasUnsavedData === "function"
      ? Boolean(window.__lotCreateModalHasUnsavedData())
      : false;

    return editorDirty || createDirty;
  }

  let lotNavigationAllowed = false;

  function ensurePendingLeaveModal() {
    let modal = document.getElementById("lotPendingLeaveModal");
    if (modal) return modal;

    modal = document.createElement("div");
    modal.id = "lotPendingLeaveModal";
    modal.className = "sv-confirm";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = `
      <div class="sv-confirm__overlay" data-lot-pending-close></div>
      <section class="sv-confirm__card" role="dialog" aria-modal="true" aria-labelledby="lotPendingLeaveTitle" aria-describedby="lotPendingLeaveMessage">
        <header class="sv-confirm__head">
          <div>
            <div class="sv-confirm__eyebrow" id="lotPendingLeaveEyebrow">Alterações pendentes</div>
            <h2 class="sv-confirm__title" id="lotPendingLeaveTitle">Sair sem salvar?</h2>
          </div>
          <button class="sv-confirm__close" type="button" aria-label="Fechar" data-lot-pending-close>
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
          </button>
        </header>
        <div class="sv-confirm__body">
          <p class="sv-confirm__message" id="lotPendingLeaveMessage">Existem alterações não salvas nesta página.</p>
        </div>
        <footer class="sv-confirm__foot">
          <button class="fin-btn fin-btn--ghost" type="button" data-lot-pending-stay>Continuar editando</button>
          <button class="fin-btn fin-btn--ghost" type="button" data-lot-pending-leave>Sair da página</button>
          <button class="fin-btn" type="button" data-lot-pending-save>Salvar tudo</button>
        </footer>
      </section>
    `;
    document.body.appendChild(modal);
    return modal;
  }

  function requestPendingNavigation(options = {}) {
    const modal = ensurePendingLeaveModal();
    const eyebrowEl = modal.querySelector("#lotPendingLeaveEyebrow");
    const titleEl = modal.querySelector("#lotPendingLeaveTitle");
    const messageEl = modal.querySelector("#lotPendingLeaveMessage");
    const stayBtn = modal.querySelector("[data-lot-pending-stay]");
    const saveBtn = modal.querySelector("[data-lot-pending-save]");
    const leaveBtn = modal.querySelector("[data-lot-pending-leave]");
    const closeEls = modal.querySelectorAll("[data-lot-pending-close]");

    if (eyebrowEl) eyebrowEl.textContent = String(options.eyebrow || "Alterações pendentes");
    if (titleEl) titleEl.textContent = String(options.title || "Sair sem salvar?");
    if (messageEl) messageEl.textContent = String(options.message || "Existem alterações não salvas nesta página.");
    if (saveBtn instanceof HTMLElement) {
      saveBtn.hidden = options.allowSaveAll === false;
    }

    return new Promise((resolve) => {
      let closed = false;

      function finish(result) {
        if (closed) return;
        closed = true;
        modal.classList.add("is-closing");
        window.setTimeout(() => {
          modal.classList.remove("is-open", "is-closing");
          modal.setAttribute("aria-hidden", "true");
          document.removeEventListener("keydown", onKeydown);
          closeEls.forEach((el) => el.removeEventListener("click", onStay));
          stayBtn?.removeEventListener("click", onStay);
          saveBtn?.removeEventListener("click", onSave);
          leaveBtn?.removeEventListener("click", onLeave);
          resolve(result);
        }, 180);
      }

      function onStay() {
        finish("stay");
      }

      function onSave() {
        finish("save");
      }

      function onLeave() {
        finish("leave");
      }

      function onKeydown(event) {
        if (event.key === "Escape") {
          finish("stay");
        }
      }

      closeEls.forEach((el) => el.addEventListener("click", onStay));
      stayBtn?.addEventListener("click", onStay);
      saveBtn?.addEventListener("click", onSave);
      leaveBtn?.addEventListener("click", onLeave);
      document.addEventListener("keydown", onKeydown);

      modal.classList.remove("is-closing");
      modal.classList.add("is-open");
      modal.setAttribute("aria-hidden", "false");
    });
  }

  async function submitPendingEditorForm(form) {
    if (!(form instanceof HTMLFormElement)) return;
    const action = String(form.getAttribute("action") || window.location.href);
    const method = String(form.getAttribute("method") || "post").toUpperCase();
    const response = await fetch(action, {
      method,
      body: new FormData(form),
      credentials: "include",
    });
    if (!response.ok) {
      throw new Error("save_failed");
    }
  }

  async function saveAllPendingLotForms() {
    const forms = typeof window.__lotGetPendingEditorForms === "function"
      ? window.__lotGetPendingEditorForms()
      : [];
    for (const form of forms) {
      await submitPendingEditorForm(form);
    }
  }

  function bindInternalNavigationGuard() {
    document.addEventListener("click", async (event) => {
      if (event.defaultPrevented) return;
      if (event.button !== 0) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

      const link = event.target instanceof Element ? event.target.closest("a[href]") : null;
      if (!(link instanceof HTMLAnchorElement)) return;
      if (link.hasAttribute("download")) return;
      if (String(link.getAttribute("target") || "").trim() === "_blank") return;

      const rawHref = String(link.getAttribute("href") || "").trim();
      if (!rawHref || rawHref.startsWith("#") || rawHref.startsWith("javascript:")) return;
      if (!hasPendingLotChanges()) return;

      const destination = new URL(link.href, window.location.href);
      const current = new URL(window.location.href);
      if (destination.href === current.href) return;

      event.preventDefault();
      const canSaveAll = typeof window.__lotGetPendingEditorForms === "function" && window.__lotGetPendingEditorForms().length > 0;
      const choice = await requestPendingNavigation({
        eyebrow: "Alterações pendentes",
        title: "Sair da página?",
        message: "Existem alterações não salvas nesta página. Você pode continuar editando, sair sem salvar ou salvar tudo antes de sair.",
        allowSaveAll: canSaveAll,
      });

      if (choice === "stay") return;

      if (choice === "save") {
        try {
          await saveAllPendingLotForms();
          lotNavigationAllowed = true;
          window.location.href = destination.href;
        } catch (_) {
          waitAndToast("danger", "Não foi possível salvar todas as alterações pendentes.");
        }
        return;
      }

      lotNavigationAllowed = true;
      window.location.href = destination.href;
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    const flash = readFlash();
    if (flash && flash.message) {
      waitAndToast(flash.kind || "info", flash.message);
    }

    const pageFlashHost = document.querySelector(".lot-page[data-lot-page-flash]");
    if (pageFlashHost) {
      const message = pageFlashHost.getAttribute("data-lot-page-flash") || "";
      const kind = pageFlashHost.getAttribute("data-lot-page-flash-kind") || "info";
      if (message) {
        waitAndToast(kind, message);
    }
  }

  async function syncFinanceFromPage() {
    const page = document.querySelector(".lot-page[data-lot-finance-sync]");
    if (!page || !window.FinStore) return;
    const payload = parseJsonAttr(page, "data-lot-finance-sync", null);
    if (!payload || payload.type !== "sale_return") return;

    try {
      await (window.FinStore?.ready?.() ?? window.FinStore?.init?.() ?? true);
    } catch (_) {}

    const saleRef = String(payload.saleRef || "");
    if (!saleRef) return;

    const crRows = Array.isArray(window.FinStore?.cr?.getRows?.()) ? window.FinStore.cr.getRows() : [];
    const cpRows = Array.isArray(window.FinStore?.cp?.getRows?.()) ? window.FinStore.cp.getRows() : [];
    const linkedRows = crRows
      .filter((row) => String(row?.id || "").startsWith(`${saleRef}-P`))
      .sort((a, b) => Number(b?.parcelaAtual || 0) - Number(a?.parcelaAtual || 0));

    const hasDone = linkedRows.some((row) => String(row?.status || "") === "done");
    const returnValue = Number(payload.returnValue || 0) || 0;
    if (returnValue <= 0) return;

    if (!hasDone && linkedRows.length && window.FinStore?.cr?.setRows) {
      let remaining = returnValue;
      const survivors = [];

      linkedRows.forEach((row) => {
        const nextRow = { ...row };
        const rowValue = Number(nextRow.valor || 0) || 0;
        if (remaining <= 0) {
          survivors.push(nextRow);
          return;
        }
        if (remaining >= rowValue) {
          remaining -= rowValue;
          return;
        }
        nextRow.valor = Math.max(0, Number((rowValue - remaining).toFixed(2)));
        nextRow.updatedAt = Date.now();
        remaining = 0;
        survivors.push(nextRow);
      });

      const linkedIds = new Set(linkedRows.map((row) => String(row?.id || "")));
      const nextCrRows = crRows
        .filter((row) => !linkedIds.has(String(row?.id || "")))
        .concat(survivors.reverse().filter((row) => Number(row?.valor || 0) > 0));
      await window.FinStore.cr.setRows(nextCrRows);
      return;
    }

    if (!window.FinStore?.cp?.upsert) return;
    const cpId = `CP-LOTRET-${String(payload.movementId || saleRef)}`;
    await window.FinStore.cp.upsert({
      id: cpId,
      cadastroId: Number(payload.clientId || 0) || null,
      cadastro: String(payload.clientName || ""),
      cadastroDocumento: String(payload.clientDocument || ""),
      conta: `Devolução de venda • ${String(payload.itemDescription || "Item do lote")}`,
      valor: returnValue,
      data: String(payload.returnDate || ""),
      fixa: false,
      status: "open",
    });
  }

  function bindPublicShareActions() {
    const triggers = Array.from(document.querySelectorAll("[data-lot-public-share]"));
    if (!triggers.length) return;

    triggers.forEach((trigger) => {
      trigger.addEventListener("click", async () => {
        const url = String(trigger.getAttribute("data-lot-public-share") || "").trim();
        if (!url) return;

        try {
          if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
            await navigator.clipboard.writeText(url);
            toast("success", "Link da ficha copiado para compartilhamento.");
            return;
          }

          const field = document.createElement("textarea");
          field.value = url;
          field.setAttribute("readonly", "readonly");
          field.style.position = "fixed";
          field.style.opacity = "0";
          field.style.pointerEvents = "none";
          document.body.appendChild(field);
          field.select();
          const copied = document.execCommand("copy");
          document.body.removeChild(field);
          if (copied) {
            toast("success", "Link da ficha copiado para compartilhamento.");
            return;
          }
          toast("warning", "Não foi possível copiar o link automaticamente.");
          return;
        } catch (_) {
          toast("danger", "Não foi possível compartilhar a ficha.");
          return;
        }
      });
    });
  }

    bindToasts();
    bindPrimarySearch();
    bindBoardFilter();
    bindAdvancedFilters();
    bindMobileToggles();
    const timelineApi = bindTimelineModal();
    const itemManageApi = bindLotItemForm();
    bindLotBaixaModal();
    const vendaApi = bindLotVendaModal();
    bindLotBaixaTotalModal();
    bindLotRevertModal();
    bindLotItemHistoryModal();
    bindLotSaleReturnModal();
    bindLotPanel();
    bindPublicShareActions();
    bindLotAnalyticsDashboard();
    const attachmentsApi = bindLotAttachmentsModal();
    const detailEditApi = bindLotDetailEditModal();
    const inlineCadastroApi = bindInlineCadastroModal();
    bindCompatibleClients(inlineCadastroApi);
    bindLotFreightLookup(inlineCadastroApi);
    bindInternalNavigationGuard();

    let lotSubmitting = false;
    document.addEventListener("submit", () => {
      lotSubmitting = true;
    });

    window.addEventListener("beforeunload", (event) => {
      if (lotNavigationAllowed) return;
      if (lotSubmitting) return;
      if (!hasPendingLotChanges()) return;

      event.preventDefault();
      event.returnValue = "";
    });
    const createApi = bindLotCreateModal();
    bindLotCreateFornecedorLookup();
    bindLotDetailEditors();
    applyMobileCollapseState();
    centerTimelineOnMobile();
    window.addEventListener("resize", applyMobileCollapseState);
    window.addEventListener("resize", centerTimelineOnMobile);

    if (consumeScrollToBoard()) {
      const board = document.querySelector(".lot-board") || document.querySelector(".lot-board-section");
      if (board && typeof board.scrollIntoView === "function") {
        window.setTimeout(() => {
          board.scrollIntoView({ behavior: "smooth", block: "start" });
        }, 80);
      }
    }

    syncCrRowsFromPage().catch(() => {});
    syncFinanceFromPage().catch(() => {});

    const page = document.querySelector(".lot-page[data-lot-open-modal]");
    if (page && page.getAttribute("data-lot-open-modal") === "venda" && vendaApi && typeof vendaApi.openModal === "function") {
      window.setTimeout(() => vendaApi.openModal(), 120);
    }
    if (page && page.getAttribute("data-lot-open-modal") === "create" && createApi && typeof createApi.openModal === "function") {
      window.setTimeout(() => createApi.openModal(), 120);
    }
    if (page && page.getAttribute("data-lot-open-modal") === "detail-edit" && detailEditApi && typeof detailEditApi.openModal === "function") {
      window.setTimeout(() => detailEditApi.openModal(), 120);
    }
    if (page && page.getAttribute("data-lot-open-modal") === "item-manage" && itemManageApi && typeof itemManageApi.openModal === "function") {
      window.setTimeout(() => itemManageApi.openModal("Cadastro de item"), 120);
    }
    if (page && String(page.getAttribute("data-lot-open-modal") || "").startsWith("timeline:") && timelineApi && typeof timelineApi.openStage === "function") {
      const parts = String(page.getAttribute("data-lot-open-modal") || "").split(":");
      const stageKey = parts[1] || "";
      if (stageKey) {
        window.setTimeout(() => timelineApi.openStage(stageKey), 120);
      }
    }
    if (page && attachmentsApi && typeof attachmentsApi.openModal === "function") {
      const attachmentGroup = page.getAttribute("data-lot-open-attachment-modal") || "";
      if (attachmentGroup) {
        window.setTimeout(() => attachmentsApi.openModal(attachmentGroup), 120);
      }
    }
  });
})();
