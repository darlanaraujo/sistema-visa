// app/static/js/cadastros/cadastros_listagem.js
(function () {
  const FLASH_KEY = "cadastros:listagem:toast";
  const AUTO_SEARCH_DELAY_MS = 420;
  const MODAL_CLOSE_MS = 320;

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

    window.setTimeout(() => {
      waitAndToast(kind, message, attempts + 1);
    }, 120);
  }

  function writeFlash(kind, message) {
    try {
      sessionStorage.setItem(FLASH_KEY, JSON.stringify({
        kind: String(kind || "show"),
        message: String(message || ""),
      }));
    } catch (_) {}
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

  function consumeQueryToast() {
    const current = new URL(window.location.href);
    const saved = String(current.searchParams.get("saved") || "");
    const removedTipo = String(current.searchParams.get("removed_tipo") || "");
    let kind = "";
    let message = "";

    if (saved === "created") {
      kind = "success";
      message = "Cadastro criado com sucesso.";
    } else if (saved === "updated") {
      kind = "success";
      message = "Cadastro atualizado com sucesso.";
    } else if (saved === "type_removed") {
      kind = "info";
      const label = {
        cliente: "cliente",
        fornecedor: "fornecedor",
        motorista: "motorista",
        transportadora: "transportadora",
      }[removedTipo] || "tipo selecionado";
      message = `O cadastro deixou de ficar ativo como ${label}.`;
    }

    if (!message) return;

    waitAndToast(kind, message);
    current.searchParams.delete("saved");
    current.searchParams.delete("removed_tipo");
    window.history.replaceState({}, document.title, current.pathname + current.search);
  }

  function bindToastLinks() {
    document.querySelectorAll("[data-cad-toast]").forEach((el) => {
      el.addEventListener("click", () => {
        const message = el.getAttribute("data-cad-toast") || "";
        const kind = el.getAttribute("data-cad-toast-kind") || "show";
        if (!message) return;
        writeFlash(kind, message);
      });
    });
  }

  function bindFilterForm() {
    const form = document.querySelector("[data-cad-filter-form]");
    if (!form) return;

    const searchInput = form.querySelector("[data-cad-auto-search]");
    const statusSelect = form.querySelector("[data-cad-auto-status]");
    const clearButton = form.querySelector("[data-cad-clear-filters]");
    const countEl = document.querySelector(".cad-list-count");
    const emptyEl = document.querySelector("[data-cad-empty]");
    const tableWrap = document.querySelector("[data-cad-table-wrap]");
    const rows = Array.from(document.querySelectorAll("[data-cad-row]"));
    let searchTimer = null;

    function normalize(value) {
      return String(value || "")
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
    }

    function renderCount(total) {
      if (!countEl) return;
      countEl.innerHTML = `<span><i class="fa-solid fa-list-check" aria-hidden="true"></i>${total} registros</span>`;
    }

    function applyClientFilters(reason) {
      const term = normalize(searchInput?.value || "");
      const status = normalize(statusSelect?.value || "");
      let visible = 0;

      rows.forEach((row) => {
        const haystack = normalize(row.getAttribute("data-cad-search") || "");
        const rowStatus = normalize(row.getAttribute("data-cad-status") || "");
        const matchesTerm = term === "" || haystack.includes(term);
        const matchesStatus = status === "" || rowStatus === status;
        const shouldShow = matchesTerm && matchesStatus;

        row.hidden = !shouldShow;
        if (shouldShow) visible += 1;
      });

      if (tableWrap) tableWrap.hidden = visible === 0;
      if (emptyEl) emptyEl.hidden = visible !== 0;

      renderCount(visible);

      if (reason === "manual") {
        toast("success", "Filtros aplicados.");
      } else if (reason === "clear") {
        toast("warning", "Filtros limpos.");
      } else if (reason === "status") {
        toast("info", "Filtro de status atualizado.");
      }
    }

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      applyClientFilters("manual");
    });

    if (searchInput) {
      searchInput.addEventListener("input", () => {
        if (searchTimer) {
          window.clearTimeout(searchTimer);
        }

        searchTimer = window.setTimeout(() => {
          applyClientFilters("search");
        }, AUTO_SEARCH_DELAY_MS);
      });
    }

    if (statusSelect) {
      statusSelect.addEventListener("change", () => {
        applyClientFilters("status");
      });
    }

    if (clearButton) {
      clearButton.addEventListener("click", (event) => {
        event.preventDefault();
        if (searchTimer) {
          window.clearTimeout(searchTimer);
        }
        if (searchInput) searchInput.value = "";
        if (statusSelect) statusSelect.value = "";
        applyClientFilters("clear");
      });
    }

    renderCount(rows.filter((row) => !row.hidden).length);
  }

  function bindInlineActions() {
    const data = Array.isArray(window.__CADASTROS_LIST__) ? window.__CADASTROS_LIST__ : [];
    const avatarMap = window.__CADASTROS_AVATARS__ && typeof window.__CADASTROS_AVATARS__ === "object"
      ? window.__CADASTROS_AVATARS__
      : {};
    const byId = new Map(data.map((item) => [String(item?.id ?? ""), item]));
    const modal = document.getElementById("cadViewModal");
    const modalClose = document.getElementById("cadViewModalClose");
    const modalCloseFoot = document.getElementById("cadViewModalCloseFoot");
    const modalEditLink = document.getElementById("cadModalEditLink");
    const modalPrintButton = document.getElementById("cadModalPrintBtn");
    const modalAvatar = document.getElementById("cadModalAvatar");
    let currentModalItem = null;

    function text(value, fallback = "Não informado") {
      const out = String(value || "").trim();
      return out || fallback;
    }

    function tipoPessoaLabel(value) {
      return String(value || "").trim().toUpperCase() === "PJ" ? "Pessoa jurídica" : "Pessoa física";
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

    function escapeHtml(value) {
      return String(value || "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
    }

    function formatSize(bytes) {
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

    function resolveAvatar(item) {
      const contextualType = currentType(item);
      if (contextualType && avatarMap[contextualType]) {
        return String(avatarMap[contextualType]);
      }

      const tipos = Array.isArray(item?.tipos) ? item.tipos : [];
      const candidates = tipos.flatMap((tipo) => [
        String(tipo?.slug || "").trim().toLowerCase(),
        String(tipo?.nome || "").trim().toLowerCase(),
      ]);

      const normalized = candidates.find((value) => value && (
        value === "cliente" ||
        value === "fornecedor" ||
        value === "motorista" ||
        value === "transportadora"
      ));

      if (normalized && avatarMap[normalized]) {
        return String(avatarMap[normalized]);
      }

      return String(avatarMap.cliente || "");
    }

    function itemTypeSlugs(item) {
      const tipos = Array.isArray(item?.tipos) ? item.tipos : [];
      return tipos
        .map((tipo) => String(tipo?.slug || "").trim().toLowerCase())
        .filter(Boolean);
    }

    const routeTipoMap = {
      clientes: "cliente",
      fornecedores: "fornecedor",
      motoristas: "motorista",
      transportadoras: "transportadora",
    };

    function routeContextType() {
      try {
        const tipo = new URL(window.location.href).searchParams.get("tipo") || "";
        return routeTipoMap[String(tipo).trim().toLowerCase()] || "";
      } catch (_) {
        return "";
      }
    }

    function currentType(item) {
      const routeType = routeContextType();
      if (routeType) return routeType;

      const slugs = itemTypeSlugs(item);
      for (const candidate of ["motorista", "transportadora", "cliente", "fornecedor"]) {
        if (slugs.includes(candidate)) return candidate;
      }
      return "cliente";
    }

    function displayName(item) {
      const isPj = String(item?.tipoPessoa || "").trim().toUpperCase() === "PJ";
      const razao = text(item?.razaoSocial, "");
      const nome = text(item?.nome, "");
      return isPj ? (razao || nome || "Cadastro") : (nome || razao || "Cadastro");
    }

    function rowHtml(label, value, long = false, raw = false) {
      return `
        <div class="cad-sheet__row${long ? " cad-sheet__row--long" : ""}">
          <dt>${escapeHtml(label)}</dt>
          <dd>${raw ? String(value || "") : escapeHtml(value)}</dd>
        </div>
      `;
    }

    function attachmentTypeLabel(item) {
      if (item?.isPdf) return "PDF";
      if (item?.isImage) {
        const ext = String(item?.extensao || "").trim().toUpperCase();
        return ext || "Imagem";
      }

      const ext = String(item?.extensao || "").trim().toUpperCase();
      return ext || "Documento";
    }

    function whatsappHref(value) {
      const digits = String(value || "").replace(/\D+/g, "");
      return digits !== "" ? `https://wa.me/${digits}` : "";
    }

    function emailHref(value) {
      const email = String(value || "").trim();
      return email !== "" ? `mailto:${email}` : "";
    }

    function linkValueHtml(label, value, href) {
      return rowHtml(
        label,
        `<a class="cad-modal-link" href="${escapeHtml(href)}" target="_blank" rel="noopener">${escapeHtml(value)}</a>`,
        false,
        true
      );
    }

    function printModalItem(item) {
      if (!item || !modal) return;

      const form = document.createElement("form");
      form.method = "POST";
      form.target = "_blank";
      form.action = (typeof window.appUrl === "function")
        ? window.appUrl("/app/templates/cadastros_print_preview.php")
        : "/app/templates/cadastros_print_preview.php";

      const payload = document.createElement("input");
      payload.type = "hidden";
      payload.name = "payload";
      payload.value = JSON.stringify({
        title: `Ficha de ${displayName(item)}`,
        item,
        contextType: routeContextType() || currentType(item),
      });

      form.appendChild(payload);
      document.body.appendChild(form);
      form.submit();
      form.remove();
    }

    function renderRows(targetId, rows) {
      const el = document.getElementById(targetId);
      if (!el) return;
      el.innerHTML = rows.join("");
    }

    function money(value) {
      try {
        return Number(value || 0).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
      } catch (_) {
        return String(value || "R$ 0,00");
      }
    }

    function lotStatusLabel(value) {
      const status = String(value || "").trim();
      if (status === "em_estoque") return "Em estoque";
      if (status === "finalizado") return "Finalizado";
      if (status === "cancelado") return "Cancelado";
      return "Em transito";
    }

    function lotDate(value) {
      const raw = String(value || "").trim();
      if (!raw) return "Nao informado";
      try {
        const date = new Date(raw.length <= 10 ? `${raw}T12:00:00` : raw);
        if (Number.isNaN(date.getTime())) return "Nao informado";
        return date.toLocaleDateString("pt-BR");
      } catch (_) {
        return "Nao informado";
      }
    }

    function transportLabel(value) {
      const type = String(value || "").trim();
      if (type === "motorista_autonomo") return "Motorista autonomo";
      if (type === "transportadora") return "Transportadora";
      if (type === "transporte_proprio") return "Transporte proprio";
      if (type === "retirada_cliente") return "Retirada pelo cliente";
      return "Sem frete";
    }

    function lotHref(loteId) {
      if (typeof window.appUrl === "function") {
        return window.appUrl(`/app/templates/lotes.php?lote=${encodeURIComponent(String(loteId || ""))}`);
      }
      return `/app/templates/lotes.php?lote=${encodeURIComponent(String(loteId || ""))}`;
    }

    function renderLotRelationships(item) {
      const card = document.getElementById("cadModalLotesCard");
      const host = document.getElementById("cadModalLotesRows");
      if (!card || !host) return;

      const rel = item?.lotesRelacionados && typeof item.lotesRelacionados === "object"
        ? item.lotesRelacionados
        : {};
      const compras = Array.isArray(rel.compras) ? rel.compras : [];
      const vendas = Array.isArray(rel.vendas) ? rel.vendas : [];
      const fretes = Array.isArray(rel.fretes) ? rel.fretes : [];

      function table(title, emptyText, columns, rows) {
        if (!rows.length) {
          return `
            <section class="cad-modal-block">
              <div class="cad-modal-block__title">${escapeHtml(title)}</div>
              <div class="cad-modal-empty">${escapeHtml(emptyText)}</div>
            </section>
          `;
        }

        return `
          <section class="cad-modal-block">
            <div class="cad-modal-block__title">${escapeHtml(title)}</div>
            <div class="fin-table-wrap cad-table-wrap cad-related-table-wrap">
              <table class="fin-table cad-table cad-related-table">
                <thead>
                  <tr>${columns.map((column) => `<th>${escapeHtml(column)}</th>`).join("")}</tr>
                </thead>
                <tbody>
                  ${rows.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join("")}</tr>`).join("")}
                </tbody>
              </table>
            </div>
          </section>
        `;
      }

      const purchaseRows = compras.map((row) => ([
        `<a class="cad-related-link" href="${escapeHtml(lotHref(row?.loteId || 0))}" target="_blank" rel="noopener">${escapeHtml(text(row?.processo, "Sem processo"))}</a>`,
        escapeHtml(text(row?.titulo, "Lote sem titulo")),
        escapeHtml(lotDate(row?.data)),
        escapeHtml(money(row?.custoTotal || row?.compra || 0)),
        escapeHtml(money(row?.compra || 0)),
      ]));

      const salesRows = vendas.map((row) => {
        const liquido = Number(row?.valorBruto || 0) - Number(row?.valorDevolvido || 0);
        return [
          `<a class="cad-related-link" href="${escapeHtml(lotHref(row?.loteId || 0))}" target="_blank" rel="noopener">${escapeHtml(text(row?.processo, "Sem processo"))}</a>`,
          escapeHtml(text(row?.produto, "Produto nao informado")),
          escapeHtml(lotDate(row?.data)),
          escapeHtml(text(row?.forma, "Nao informada")),
          escapeHtml(money(liquido)),
        ];
      });

      const freightRows = fretes.map((row) => ([
        `<a class="cad-related-link" href="${escapeHtml(lotHref(row?.loteId || 0))}" target="_blank" rel="noopener">${escapeHtml(text(row?.processo, "Sem processo"))}</a>`,
        escapeHtml(text(row?.titulo, "Lote sem titulo")),
        escapeHtml(lotDate(row?.data)),
        escapeHtml(
          [text(row?.cidade, ""), text(row?.estado, "")]
            .filter(Boolean)
            .join(" / ") || "Nao informada"
        ),
        escapeHtml(money(row?.totalFrete || 0)),
      ]));

      host.innerHTML = [
        table("Compras em lotes", "Nenhuma compra em lotes foi encontrada para este cadastro.", ["Processo", "Lote", "Data", "Custo total", "Valor pago"], purchaseRows),
        table("Vendas em lotes", "Nenhuma venda em lotes foi encontrada para este cadastro.", ["Processo", "Produto", "Data", "Forma", "Valor liquido"], salesRows),
        table("Fretes em lotes", "Nenhum frete em lotes foi encontrado para este cadastro.", ["Processo", "Lote", "Data", "Cidade da coleta", "Total frete"], freightRows),
      ].join("");

      card.hidden = false;
    }

    function renderTags(item) {
      const tagCard = document.getElementById("cadModalTagsCard");
      const host = document.getElementById("cadModalTagsRows");
      if (!tagCard || !host) return;

      const tags = Array.isArray(item?.tags) ? item.tags : [];
      if (tags.length === 0) {
        tagCard.hidden = false;
        host.innerHTML = `<div class="cad-modal-empty">Nenhuma tag estruturada registrada.</div>`;
        return;
      }

      host.innerHTML = tags
        .map((tag) => `<span class="cad-modal-tag">${escapeHtml(text(tag?.nome, ""))}</span>`)
        .join("");
      tagCard.hidden = false;
    }

    function renderAttachments(item) {
      const card = document.getElementById("cadModalAnexosCard");
      const empty = document.getElementById("cadModalAnexosEmpty");
      const host = document.getElementById("cadModalAnexosRows");
      if (!card || !empty || !host) return;

      const anexos = Array.isArray(item?.anexos) ? item.anexos : [];
      host.innerHTML = "";

      if (anexos.length === 0) {
        empty.hidden = false;
        card.hidden = false;
        return;
      }

      empty.hidden = true;
      host.innerHTML = anexos.map((anexo) => `
        <article class="sv-attachments__item">
          <button type="button" class="sv-attachments__thumb" data-cad-anexo-preview="${escapeHtml(String(anexo?.id || ""))}">
            ${anexo?.isImage && anexo?.previewUrl
              ? `<img src="${escapeHtml(String(anexo.previewUrl))}" alt="${escapeHtml(text(anexo?.nomeOriginal, "Anexo"))}">`
              : `<span class="sv-attachments__thumbicon"><i class="${escapeHtml(anexo?.isPdf ? "fa-regular fa-file-pdf" : "fa-regular fa-file-lines")}" aria-hidden="true"></i></span>`}
          </button>
          <div class="sv-attachments__meta">
            <div class="sv-attachments__name">${escapeHtml(text(anexo?.nomeOriginal, "Anexo"))}</div>
            <div class="sv-attachments__inforow">
              <span class="sv-attachments__info sv-attachments__infoitem">${escapeHtml(attachmentTypeLabel(anexo))}</span>
              <span class="sv-attachments__infosep" aria-hidden="true">•</span>
              <span class="sv-attachments__info sv-attachments__infoitem">${escapeHtml(formatSize(anexo?.tamanhoBytes || 0))}</span>
            </div>
          </div>
          <div class="sv-attachments__foot">
            <span class="sv-attachments__badge">${anexo?.isImage ? "Imagem" : (anexo?.isPdf ? "PDF" : "Documento")}</span>
            <a class="fin-btn fin-btn--ghost" href="${escapeHtml(String(anexo?.downloadUrl || "#"))}" target="_blank" rel="noopener">Baixar</a>
          </div>
        </article>
      `).join("");

      host.querySelectorAll("[data-cad-anexo-preview]").forEach((button, index) => {
        button.addEventListener("click", () => {
          if (window.AttachmentsUI && typeof window.AttachmentsUI.openViewer === "function") {
            const viewerItems = anexos.map((anexoItem) => ({
              name: String(anexoItem?.nomeOriginal || "Anexo"),
              previewUrl: String(anexoItem?.previewUrl || ""),
              downloadUrl: String(anexoItem?.downloadUrl || ""),
              isImage: Boolean(anexoItem?.isImage),
              isPdf: Boolean(anexoItem?.isPdf),
            }));
            window.AttachmentsUI.openViewer(viewerItems, index);
          }
        });
      });

      card.hidden = false;
    }

    function renderOperationalStructure(item, type) {
      const card = document.getElementById("cadModalEstruturaCard");
      const title = document.getElementById("cadModalEstruturaTitle");
      const host = document.getElementById("cadModalEstruturaRows");
      if (!card || !title || !host) return;

      const motoristaDetalhes = item?.motoristaDetalhes || {};
      const motoristas = Array.isArray(item?.motoristasVinculados) ? item.motoristasVinculados : [];
      const rows = [];

      if (type === "motorista") {
        const isPj = String(item?.tipoPessoa || "").trim().toUpperCase() === "PJ";
        title.textContent = "Estrutura operacional";
        rows.push(`
          <section class="cad-modal-block">
            <div class="cad-modal-block__title">Motorista principal</div>
            <dl class="cad-sheet__grid cad-sheet__grid--two">
              ${rowHtml("Nome", text(item?.nome))}
              ${rowHtml(isPj ? "CPF do motorista principal" : "CPF", text(isPj ? motoristaDetalhes?.cpf : item?.documento))}
              ${rowHtml("CNH", text(motoristaDetalhes?.cnh))}
            </dl>
          </section>
        `);

        if (motoristas.length > 0) {
          rows.push(`
            <section class="cad-modal-block">
              <div class="cad-modal-block__title">Motorista 2</div>
              ${motoristas.map((motorista) => `
                <dl class="cad-sheet__grid cad-sheet__grid--two">
                  ${rowHtml("Nome", text(motorista?.nome))}
                  ${rowHtml("CPF", text(motorista?.cpf))}
                  ${rowHtml("CNH", text(motorista?.cnh))}
                  ${rowHtml("Contato", text(motorista?.contato))}
                </dl>
              `).join("")}
            </section>
          `);
        }
      } else if (type === "transportadora") {
        title.textContent = "Estrutura operacional";
        const principal = motoristas[0] || null;
        if (principal) {
          rows.push(`
            <section class="cad-modal-block">
              <div class="cad-modal-block__title">Motorista principal vinculado</div>
              <dl class="cad-sheet__grid cad-sheet__grid--two">
                ${rowHtml("Nome", text(principal?.nome))}
                ${rowHtml("CPF", text(principal?.cpf))}
                ${rowHtml("CNH", text(principal?.cnh))}
                ${rowHtml("Contato", text(principal?.contato))}
              </dl>
            </section>
          `);
        }

        if (motoristas.length > 1) {
          rows.push(`
            <section class="cad-modal-block">
              <div class="cad-modal-block__title">Motorista 2 e adicionais</div>
              ${motoristas.slice(1).map((motorista) => `
                <dl class="cad-sheet__grid cad-sheet__grid--two">
                  ${rowHtml("Nome", text(motorista?.nome))}
                  ${rowHtml("CPF", text(motorista?.cpf))}
                  ${rowHtml("CNH", text(motorista?.cnh))}
                  ${rowHtml("Contato", text(motorista?.contato))}
                </dl>
              `).join("")}
            </section>
          `);
        }
      }

      host.innerHTML = rows.join("");
      card.hidden = rows.length === 0;
    }

    function renderVehicles(item, type) {
      const card = document.getElementById("cadModalVeiculosCard");
      const host = document.getElementById("cadModalVeiculosRows");
      if (!card || !host) return;

      const veiculos = Array.isArray(item?.veiculos) ? item.veiculos : [];
      if (veiculos.length === 0) {
        card.hidden = true;
        host.innerHTML = "";
        return;
      }

      host.innerHTML = veiculos.map((veiculo, index) => `
        <section class="cad-modal-block">
          <div class="cad-modal-block__title">${
            type === "transportadora"
              ? (index === 0 ? "Veículo principal vinculado" : `Veículo ${index + 1}`)
              : (index === 0 ? "Veículo principal" : `Veículo ${index + 1}`)
          }</div>
          <dl class="cad-sheet__grid cad-sheet__grid--two">
            ${rowHtml("Modelo", text(veiculo?.modelo))}
            ${rowHtml("Placa", text(veiculo?.placa))}
            ${rowHtml("Placa adicional", text(veiculo?.placaAdicional))}
            ${rowHtml("Tipo de carroceria", text(veiculo?.tipoCarroceria))}
            ${rowHtml("Metragem", text(veiculo?.metragem))}
            ${rowHtml("Peso de carga", text(veiculo?.pesoCarga))}
          </dl>
        </section>
      `).join("");
      card.hidden = false;
    }

    function closeModal(withToast) {
      if (!modal) return;
      modal.classList.add("is-closing");
      window.setTimeout(() => {
        modal.classList.remove("is-open", "is-closing");
        modal.setAttribute("aria-hidden", "true");
      }, MODAL_CLOSE_MS);

      if (withToast) {
        toast("info", "Ficha fechada.");
      }
    }

    function openModal(item) {
      if (!modal || !item) return;
      try {
        currentModalItem = item;
        const tipos = Array.isArray(item.tipos) ? item.tipos : [];
        const type = currentType(item);
        const pills = document.getElementById("cadModalPills");
        const cidadeEstado = [text(item.cidade, ""), text(item.estado, "")]
          .filter(Boolean)
          .join(" / ");
        const heroTitle = document.getElementById("cadModalHeroTitle");
        const heroSubtitle = document.getElementById("cadModalHeroSubtitle");
        const avatarSrc = resolveAvatar(item);
        const isPj = String(item.tipoPessoa || "").trim().toUpperCase() === "PJ";
        const contatoRows = [];
        const enderecoRows = [];
        const classificacaoRows = [];
        const identificacaoRows = [];
        const modalTitle = document.getElementById("cadViewModalTitle");
        const observacoesEl = document.getElementById("cadModalObservacoes");
        const metricTipo = document.getElementById("cadModalMetricTipo");
        const metricContato = document.getElementById("cadModalMetricContato");
        const metricCidade = document.getElementById("cadModalMetricCidade");

        if (modalTitle) {
          modalTitle.textContent = `Ficha de ${displayName(item)}`;
        }
        if (heroTitle) heroTitle.textContent = displayName(item);
        if (heroSubtitle) {
          heroSubtitle.textContent = type === "cliente" || type === "fornecedor"
            ? `Cadastro em modo de visualização (${tipoPessoaLabel(item.tipoPessoa)}).`
            : `Cadastro operacional em modo de visualização (${tipoPessoaLabel(item.tipoPessoa)}).`;
        }
        if (metricTipo) {
          metricTipo.textContent = tipos.length
            ? tipos.map((tipoItem) => String(tipoItem?.nome || "")).filter(Boolean).join(" • ")
            : "Sem tipo associado";
        }
        if (metricContato) {
          metricContato.textContent = text(item.celular || item.whatsapp || item.telefoneFixo, "Não informado");
        }
        if (metricCidade) {
          metricCidade.textContent = cidadeEstado || "Não informado";
        }

        if (type === "cliente" || type === "fornecedor") {
          if (isPj) {
            identificacaoRows.push(rowHtml("Razão social", text(item.razaoSocial)));
            identificacaoRows.push(rowHtml("CNPJ", text(item.documento)));
            identificacaoRows.push(rowHtml("Nome fantasia", text(item.nomeFantasia)));
            identificacaoRows.push(rowHtml("Inscrição estadual", text(item.inscricaoEstadual)));
          } else {
            identificacaoRows.push(rowHtml("Nome", text(item.nome)));
            identificacaoRows.push(rowHtml("CPF", text(item.documento)));
          }
        } else if (type === "motorista") {
          if (isPj) {
            identificacaoRows.push(rowHtml("Razão social", text(item.razaoSocial)));
            identificacaoRows.push(rowHtml("CNPJ", text(item.documento)));
            identificacaoRows.push(rowHtml("Nome fantasia", text(item.nomeFantasia)));
            identificacaoRows.push(rowHtml("Inscrição estadual", text(item.inscricaoEstadual)));
          } else {
            identificacaoRows.push(rowHtml("Nome", text(item.nome)));
            identificacaoRows.push(rowHtml("CPF", text(item.documento)));
          }
        } else {
          identificacaoRows.push(rowHtml("Razão social", text(item.razaoSocial)));
          identificacaoRows.push(rowHtml("CNPJ", text(item.documento)));
          identificacaoRows.push(rowHtml("Nome fantasia", text(item.nomeFantasia)));
          identificacaoRows.push(rowHtml("Inscrição estadual", text(item.inscricaoEstadual)));
        }

        if (text(item.contato, "") !== "") contatoRows.push(rowHtml("Contato", text(item.contato)));
        if (text(item.telefoneFixo, "") !== "") contatoRows.push(rowHtml("Telefone fixo", text(item.telefoneFixo)));
        if (text(item.whatsapp, "") !== "") contatoRows.push(linkValueHtml("WhatsApp", text(item.whatsapp), whatsappHref(item.whatsapp)));
        if (text(item.celular, "") !== "") contatoRows.push(rowHtml("Celular", text(item.celular)));
        if (text(item.email, "") !== "") contatoRows.push(linkValueHtml("E-mail", text(item.email), emailHref(item.email)));
        if (contatoRows.length === 0) {
          contatoRows.push(rowHtml("Contato", "Não informado"));
        }

        enderecoRows.push(rowHtml("CEP", text(item.cep)));
        enderecoRows.push(rowHtml("Endereço", text(item.endereco)));
        enderecoRows.push(rowHtml("Número", text(item.numero)));
        enderecoRows.push(rowHtml("Complemento", text(item.complemento)));
        enderecoRows.push(rowHtml("Bairro", text(item.bairro)));
        enderecoRows.push(rowHtml("Cidade / Estado", cidadeEstado || "Não informado"));

        classificacaoRows.push(rowHtml("Tipo de pessoa", tipoPessoaLabel(item.tipoPessoa)));
        classificacaoRows.push(rowHtml("Documento principal", text(item.documento)));
        classificacaoRows.push(rowHtml("Tipos associados", tipos.length
          ? tipos.map((tipoItem) => String(tipoItem?.nome || "")).filter(Boolean).join(" • ")
          : "Sem tipos associados"));
        if (observacoesEl) {
          observacoesEl.textContent = text(item.observacoes, "Nenhuma observação registrada");
        }

        renderRows("cadModalIdentificacaoRows", identificacaoRows);
        renderRows("cadModalContatoRows", contatoRows);
        renderRows("cadModalEnderecoRows", enderecoRows);
        renderRows("cadModalClassificacaoRows", classificacaoRows);
        renderOperationalStructure(item, type);
        renderVehicles(item, type);
        renderAttachments(item);
        renderTags(item);
        renderLotRelationships(item);

        if (modalAvatar) {
          const label = displayName(item);
          modalAvatar.innerHTML = "";

          if (avatarSrc) {
            const img = document.createElement("img");
            img.src = avatarSrc;
            img.alt = `Avatar de ${label}`;
            img.loading = "eager";
            img.decoding = "async";
            img.addEventListener("error", () => {
              modalAvatar.textContent = initials(label);
            }, { once: true });
            modalAvatar.appendChild(img);
          } else {
            modalAvatar.textContent = initials(label);
          }
        }

        if (pills) {
          const statusKind = String(item.status || "").trim().toLowerCase() === "inativo" ? "inativo" : "ativo";
          const statusLabel = statusKind === "inativo" ? "Inativo" : "Ativo";
          pills.innerHTML = `
            <span class="cad-status cad-status--${statusKind}">${statusLabel}</span>
            <span class="cad-ficha-pill"><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i>${escapeHtml(text(item.documento, "Documento não informado"))}</span>
          `;
        }

        if (modalEditLink) {
          const url = new URL(modalEditLink.getAttribute("href") || window.location.href, window.location.origin);
          url.searchParams.set("id", String(item.id || ""));
          url.searchParams.set("modo", "cadastro");
          const currentTipo = new URL(window.location.href).searchParams.get("tipo");
          if (currentTipo) {
            url.searchParams.set("tipo", currentTipo);
          }
          modalEditLink.setAttribute("href", url.pathname + url.search);
        }

        modal.classList.remove("is-closing");
        modal.classList.add("is-open");
        modal.setAttribute("aria-hidden", "false");
        toast("info", "Ficha aberta.");
      } catch (error) {
        console.error("Falha ao abrir ficha do cadastro:", error);
        toast("danger", "Não foi possível abrir a ficha do cadastro.");
      }
    }

    function openModalById(id) {
      const item = byId.get(String(id || ""));
      if (!item) {
        toast("danger", "Cadastro não encontrado para visualização.");
        return false;
      }
      openModal(item);
      return true;
    }

    window.CadastrosListagem = window.CadastrosListagem || {};
    window.CadastrosListagem.openModalById = openModalById;

    document.querySelectorAll("[data-cad-action='new']").forEach((button) => {
      button.addEventListener("click", () => {
        toast("warning", "Novo cadastro entra na próxima parte.");
      });
    });

    document.querySelectorAll("[data-cad-action='view']").forEach((button) => {
      button.addEventListener("click", () => {
        openModalById(button.getAttribute("data-cad-id") || "");
      });
    });

    document.querySelectorAll("[data-cad-action='toggle']").forEach((button) => {
      button.addEventListener("click", () => {
        toast("warning", "Ativar e desativar entra na proxima parte.");
      });
    });

    document.querySelectorAll("[data-cad-action='delete']").forEach((button) => {
      button.addEventListener("click", () => {
        toast("danger", "Exclusao possui barreiras e entra na proxima parte.");
      });
    });

    [modalClose, modalCloseFoot].forEach((button) => {
      if (!button) return;
      button.addEventListener("click", () => closeModal(true));
    });

    if (modalPrintButton) {
      modalPrintButton.addEventListener("click", () => {
        printModalItem(currentModalItem);
      });
    }

    if (modal) {
      modal.addEventListener("click", (event) => {
        if (event.target === modal) {
          closeModal(true);
        }
      });
    }
  }

  const flash = readFlash();
  if (flash && flash.message) {
    waitAndToast(flash.kind || "show", flash.message);
  }
  consumeQueryToast();

  bindToastLinks();
  bindFilterForm();
  bindInlineActions();
})();
