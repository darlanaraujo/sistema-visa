<?php
// app/modules/lotes/home.php

$proc = (string)($_GET['processo'] ?? '');
$proc = trim($proc);

?>

<div class="module-page">
  <div class="module-head">
    <h1>Módulo de Lotes</h1>
    <p>Placeholder do módulo. Estrutura pronta para evoluir sem retrabalho.</p>
  </div>

  <?php if ($proc !== ''): ?>
    <div class="module-card">
      <div class="module-card__title">Processo selecionado</div>
      <div class="module-card__value"><?= h($proc) ?></div>
      <div class="module-card__hint">
        * Futuro: aqui exibe dados completos do processo (seguradora, itens, status, histórico e anexos).
      </div>
    </div>
  <?php endif; ?>

  <div class="module-card">
    <div class="module-card__title">Status</div>
    <div class="module-card__value">Tela inicial do módulo de lotes (em construção).</div>
  </div>
</div>