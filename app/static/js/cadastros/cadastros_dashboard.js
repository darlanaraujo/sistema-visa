// app/static/js/cadastros/cadastros_dashboard.js
(function () {
  function appBase() {
    const path = String(window.location.pathname || "");
    const idx = path.indexOf("/app/templates/");
    return idx >= 0 ? path.slice(0, idx) : "";
  }

  function safeJson(raw, fallback) {
    try {
      return JSON.parse(String(raw || ""));
    } catch (_) {
      return fallback;
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
      .replace(/'/g, "&#039;");
  }

  function preferredTipo(item) {
    const tipos = Array.isArray(item?.tipos) ? item.tipos : [];
    const slugs = tipos.map((tipo) => String(tipo?.slug || "").trim().toLowerCase()).filter(Boolean);
    for (const candidate of ["cliente", "fornecedor", "motorista", "transportadora"]) {
      if (slugs.includes(candidate)) return candidate;
    }
    return "clientes";
  }

  function tipoRoute(slug) {
    return {
      cliente: "clientes",
      fornecedor: "fornecedores",
      motorista: "motoristas",
      transportadora: "transportadoras",
    }[slug] || "clientes";
  }

  function tipoPessoaLabel(value) {
    return String(value || "").trim().toUpperCase() === "PJ" ? "Pessoa jurídica" : "Pessoa física";
  }

  function statusLabel(value) {
    return String(value || "").trim().toLowerCase() === "inativo" ? "Inativo" : "Ativo";
  }

  function initQuickSearch() {
    const root = document.querySelector("[data-cad-quick-search]");
    if (!root) return;

    const items = safeJson(root.getAttribute("data-cad-items"), []);
    const input = root.querySelector("[data-cad-quick-input]");
    const results = root.querySelector("[data-cad-quick-results]");
    const empty = root.querySelector("[data-cad-quick-empty]");
    if (!input || !results || !empty) return;

    function itemHaystack(item) {
      const tipos = Array.isArray(item?.tipos) ? item.tipos.map((tipo) => tipo?.nome || tipo?.slug || "") : [];
      return normalize([
        item?.nome,
        item?.razaoSocial,
        item?.documento,
        item?.status,
        ...tipos,
      ].join(" "));
    }

    function render(term) {
      const query = normalize(term);
      if (!query) {
        results.hidden = true;
        empty.hidden = true;
        results.innerHTML = "";
        return;
      }

      const filtered = items.filter((item) => itemHaystack(item).includes(query)).slice(0, 12);
      if (filtered.length === 0) {
        results.hidden = true;
        empty.hidden = false;
        results.innerHTML = "";
        return;
      }

      empty.hidden = true;
      results.hidden = false;
      results.innerHTML = filtered.map((item) => {
        const nome = String(item?.tipoPessoa || "").toUpperCase() === "PJ"
          ? (item?.razaoSocial || item?.nome || "Cadastro")
          : (item?.nome || item?.razaoSocial || "Cadastro");
        const route = tipoRoute(preferredTipo(item));
        const href = `${appBase()}/app/templates/cadastros_ficha.php?modo=cadastro&id=${encodeURIComponent(String(item?.id || ""))}&tipo=${encodeURIComponent(route)}`;
        const telefone = String(item?.telefone || item?.celular || item?.whatsapp || item?.telefoneFixo || "").trim() || "—";

        return `
          <tr>
            <td class="cad-col-main cad-quick-search__name-cell" data-cad-open-modal-id="${escapeHtml(String(item?.id || ""))}" role="button" tabindex="0" aria-label="Visualizar cadastro ${escapeHtml(nome)}">
              <div class="cad-row-title cad-quick-search__cell-main">
                <strong>${escapeHtml(nome)}</strong>
                <span>${escapeHtml(tipoPessoaLabel(item?.tipoPessoa))}</span>
              </div>
            </td>
            <td>${escapeHtml(telefone)}</td>
            <td>
              <div class="fin-actions-row cad-quick-search__actions">
                <button class="fin-action-ico is-highlight" type="button" data-tip="Visualizar" data-cad-open-modal-id="${escapeHtml(String(item?.id || ""))}" aria-label="Visualizar cadastro" title="Visualizar cadastro">
                  <i class="fa-solid fa-eye"></i>
                </button>
                <a class="fin-action-ico" href="${escapeHtml(href)}" data-tip="Editar" aria-label="Editar cadastro" title="Editar cadastro">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </div>
            </td>
          </tr>
        `;
      }).join("");
    }

    input.addEventListener("input", () => render(input.value));
    results.addEventListener("click", (event) => {
      const trigger = event.target.closest("[data-cad-open-modal-id]");
      if (!trigger) return;
      const id = trigger.getAttribute("data-cad-open-modal-id") || "";
      if (window.CadastrosListagem && typeof window.CadastrosListagem.openModalById === "function") {
        window.CadastrosListagem.openModalById(id);
      }
    });
    results.addEventListener("keydown", (event) => {
      const trigger = event.target.closest("[data-cad-open-modal-id]");
      if (!trigger) return;
      if (event.key !== "Enter" && event.key !== " ") return;
      event.preventDefault();
      const id = trigger.getAttribute("data-cad-open-modal-id") || "";
      if (window.CadastrosListagem && typeof window.CadastrosListagem.openModalById === "function") {
        window.CadastrosListagem.openModalById(id);
      }
    });
  }

  initQuickSearch();
})();
