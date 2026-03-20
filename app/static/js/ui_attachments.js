(function () {
  if (window.AttachmentsUI) return;

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

  function iconFor(item) {
    if (item.isImage) return "fa-regular fa-image";
    if (item.isPdf) return "fa-regular fa-file-pdf";
    return "fa-regular fa-file-lines";
  }

  function typeLabel(item) {
    if (item.isPdf) return "PDF";
    if (item.isImage) {
      const ext = String(item.extension || "").trim().toUpperCase();
      return ext || "Imagem";
    }

    const ext = String(item.extension || "").trim().toUpperCase();
    return ext || "Documento";
  }

  function ensureViewer() {
    let modal = document.getElementById("svAttachmentViewer");
    if (modal) return modal;

    modal = document.createElement("div");
    modal.id = "svAttachmentViewer";
    modal.className = "fin-modal sv-attachment-viewer";
    modal.setAttribute("aria-hidden", "true");
    modal.innerHTML = `
      <div class="fin-modal__card">
        <div class="fin-modal__head">
          <div class="fin-modal__title" id="svAttachmentViewerTitle">Anexo</div>
          <button class="fin-modal__close" type="button" data-sv-attachment-close aria-label="Fechar visualização">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="fin-modal__body sv-attachment-viewer__body">
          <div class="sv-attachment-viewer__stage" id="svAttachmentViewerStage"></div>
          <div class="sv-attachment-viewer__meta">
            <div class="sv-attachment-viewer__name" id="svAttachmentViewerName">Arquivo</div>
            <div class="sv-attachment-viewer__controls">
              <button class="fin-btn fin-btn--ghost" type="button" data-sv-attachment-prev>Anterior</button>
              <button class="fin-btn fin-btn--ghost" type="button" data-sv-attachment-next>Próximo</button>
              <a class="fin-btn" href="#" target="_blank" rel="noopener" id="svAttachmentViewerDownload">Baixar</a>
            </div>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    return modal;
  }

  function openViewer(items, startIndex) {
    const modal = ensureViewer();
    const stage = modal.querySelector("#svAttachmentViewerStage");
    const title = modal.querySelector("#svAttachmentViewerTitle");
    const name = modal.querySelector("#svAttachmentViewerName");
    const download = modal.querySelector("#svAttachmentViewerDownload");
    const prev = modal.querySelector("[data-sv-attachment-prev]");
    const next = modal.querySelector("[data-sv-attachment-next]");
    const closeEls = modal.querySelectorAll("[data-sv-attachment-close]");

    let index = Math.max(0, Math.min(startIndex, items.length - 1));

    function render() {
      const current = items[index];
      if (!current) return;

      title.textContent = current.isImage ? "Imagem" : (current.isPdf ? "PDF" : "Documento");
      name.textContent = current.name;
      download.href = current.downloadUrl || current.previewUrl || "#";
      stage.innerHTML = "";

      if (current.isImage) {
        const img = document.createElement("img");
        img.src = current.previewUrl;
        img.alt = current.name;
        stage.appendChild(img);
      } else if (current.isPdf && current.previewUrl) {
        const frame = document.createElement("iframe");
        frame.src = current.previewUrl;
        frame.title = current.name;
        stage.appendChild(frame);
      } else {
        const box = document.createElement("div");
        box.className = "sv-attachments__empty";
        box.innerHTML = `<i class="${iconFor(current)}" aria-hidden="true"></i> Visualização interna indisponível para este formato. Use o download.`;
        stage.appendChild(box);
      }

      prev.disabled = index <= 0;
      next.disabled = index >= items.length - 1;
    }

    function close() {
      modal.classList.add("is-closing");
      window.setTimeout(() => {
        modal.classList.remove("is-open", "is-closing");
        modal.setAttribute("aria-hidden", "true");
      }, 240);
    }

    closeEls.forEach((button) => {
      button.onclick = close;
    });
    modal.onclick = (event) => {
      if (event.target === modal) close();
    };
    prev.onclick = () => {
      if (index <= 0) return;
      index -= 1;
      render();
    };
    next.onclick = () => {
      if (index >= items.length - 1) return;
      index += 1;
      render();
    };

    render();
    modal.classList.remove("is-closing");
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
  }

  function normalizeExistingItem(item) {
    return {
      id: Number(item?.id || 0),
      relationId: Number(item?.relacaoId || 0),
      name: String(item?.nomeOriginal || item?.name || "Arquivo"),
      info: String(item?.mimeType || ""),
      extension: String(item?.extensao || ""),
      size: formatSize(item?.tamanhoBytes || 0),
      previewUrl: String(item?.previewUrl || ""),
      downloadUrl: String(item?.downloadUrl || ""),
      isImage: Boolean(item?.isImage),
      isPdf: Boolean(item?.isPdf),
      isPreviewable: Boolean(item?.isPreviewable),
      existing: true,
      removed: false,
    };
  }

  function normalizePendingFile(file) {
    return {
      id: `pending-${Math.random().toString(16).slice(2)}`,
      relationId: 0,
      name: file.name,
      info: file.type || "Arquivo local",
      extension: String(file.name || "").split(".").pop() || "",
      size: formatSize(file.size),
      previewUrl: file.type.startsWith("image/") ? URL.createObjectURL(file) : "",
      downloadUrl: "",
      isImage: file.type.startsWith("image/"),
      isPdf: file.type === "application/pdf",
      isPreviewable: file.type.startsWith("image/") || file.type === "application/pdf",
      existing: false,
      removed: false,
      file,
    };
  }

  function pendingSignature(item) {
    const file = item?.file;
    if (!file) return String(item?.id || "");
    return [
      String(file.name || "").toLowerCase(),
      Number(file.size || 0),
      Number(file.lastModified || 0),
      String(file.type || "").toLowerCase(),
    ].join("::");
  }

  function renderRoot(root) {
    const input = root.querySelector("[data-anexos-input]");
    const pick = root.querySelector("[data-anexos-pick]");
    const grid = root.querySelector("[data-anexos-grid]");
    const empty = root.querySelector("[data-anexos-empty]");
    const hidden = root.querySelector("[data-anexos-remove-hidden]");
    if (!grid) return;

    let existing = [];
    let pending = [];

    try {
      existing = JSON.parse(root.getAttribute("data-anexos-existing") || "[]").map(normalizeExistingItem);
    } catch (_) {
      existing = [];
    }

    function syncInputFiles() {
      if (!input) return;
      const dt = new DataTransfer();
      pending.forEach((item) => {
        if (item.file) {
          dt.items.add(item.file);
        }
      });
      input.files = dt.files;
    }

    function syncRemovedInputs() {
      if (!hidden) return;
      hidden.innerHTML = "";
      existing.filter((item) => item.removed && item.relationId > 0).forEach((item) => {
        const field = document.createElement("input");
        field.type = "hidden";
        field.name = "anexos_remover[]";
        field.value = String(item.relationId);
        hidden.appendChild(field);
      });
    }

    function activeItems() {
      return [
        ...existing.filter((item) => !item.removed),
        ...pending,
      ];
    }

    function redraw() {
      const items = activeItems();
      grid.innerHTML = "";
      if (empty) empty.hidden = items.length !== 0;

      items.forEach((item, index) => {
        const card = document.createElement("article");
        card.className = "sv-attachments__item";
        card.innerHTML = `
          <button type="button" class="sv-attachments__thumb" data-anexo-preview>
            ${item.isImage && item.previewUrl
              ? `<img src="${item.previewUrl}" alt="${item.name}">`
              : `<span class="sv-attachments__thumbicon"><i class="${iconFor(item)}" aria-hidden="true"></i></span>`}
          </button>
          <div class="sv-attachments__meta">
            <div class="sv-attachments__name">${item.name}</div>
            <div class="sv-attachments__inforow">
              <span class="sv-attachments__info sv-attachments__infoitem">${typeLabel(item)}</span>
              <span class="sv-attachments__infosep" aria-hidden="true">•</span>
              <span class="sv-attachments__info sv-attachments__infoitem">${item.size}</span>
            </div>
          </div>
          <div class="sv-attachments__foot">
            <span class="sv-attachments__badge">${item.existing ? "Salvo" : "Novo"}</span>
            <button type="button" class="sv-attachments__remove${item.removed ? " is-marked" : ""}" data-anexo-remove aria-label="Remover anexo">
              <i class="fa-solid fa-trash" aria-hidden="true"></i>
            </button>
          </div>
        `;

        card.querySelector("[data-anexo-preview]")?.addEventListener("click", () => {
          openViewer(items, index);
        });

        card.querySelector("[data-anexo-remove]")?.addEventListener("click", () => {
          if (item.existing) {
            item.removed = !item.removed;
            syncRemovedInputs();
          } else {
            pending = pending.filter((pendingItem) => pendingItem.id !== item.id);
            syncInputFiles();
          }
          redraw();
        });

        grid.appendChild(card);
      });
    }

    if (pick && input) {
      pick.addEventListener("click", () => input.click());
    }

    if (input) {
      input.addEventListener("change", () => {
        const selectedFiles = Array.from(input.files || []);
        const files = selectedFiles.map(normalizePendingFile);
        const existingSignatures = new Set(pending.map((item) => pendingSignature(item)));
        files.forEach((item) => {
          const signature = pendingSignature(item);
          if (existingSignatures.has(signature)) return;
          existingSignatures.add(signature);
          pending.push(item);
        });
        syncInputFiles();
        redraw();
      });
    }

    syncRemovedInputs();
    redraw();
  }

  function init(context = document) {
    context.querySelectorAll("[data-anexos-root]").forEach((root) => renderRoot(root));
  }

  window.AttachmentsUI = {
    init,
    openViewer,
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => init(document), { once: true });
  } else {
    init(document);
  }
})();
