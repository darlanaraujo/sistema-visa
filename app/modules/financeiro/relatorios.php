<?php
// app/modules/financeiro/relatorios.php

$relatorios = [
  ['nome' => 'Despesas por imóvel', 'desc' => 'Filtro por período e imóvel (mock)'],
  ['nome' => 'Despesas por categoria', 'desc' => 'Filtro por período e categoria (mock)'],
  ['nome' => 'Pendências a pagar', 'desc' => 'Lista por mês e status (mock)'],
  ['nome' => 'Pendências a receber', 'desc' => 'Lista por mês e status (mock)'],
];
?>

<div class="fin-page">
  <div class="fin-head">
    <h1>Relatórios</h1>
    <p>Estrutura visual para relatórios (mock).</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <div class="fin-panel">
    <div class="fin-panel__head">
      <div class="fin-panel__title">Relatórios disponíveis</div>
      <span class="fin-badge">mock</span>
    </div>

    <div class="fin-list">
      <?php foreach ($relatorios as $r): ?>
        <div class="fin-list__item">
          <div class="fin-list__title"><?= htmlspecialchars($r['nome']) ?></div>
          <div class="fin-list__hint"><?= htmlspecialchars($r['desc']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>