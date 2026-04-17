<div class="lot-public-sheetbar">
  <div>
    <div class="lot-public-sheetbar__title">Impressão da ficha pública do lote</div>
    <div class="lot-public-sheetbar__hint">Versão comercial completa com capa, galeria, identificação e lista dos itens disponíveis.</div>
  </div>
  <div class="lot-public-actions">
    <button class="fin-btn" type="button" onclick="window.print()">
      <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir ficha</span>
    </button>
    <a class="fin-btn fin-btn--ghost" href="<?= h(lot_public_url($loteId, $token)) ?>" target="_blank" rel="noopener noreferrer">
      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>Abrir ficha pública</span>
    </a>
  </div>
</div>

<?php require __DIR__ . '/lote_publico_content.php'; ?>
