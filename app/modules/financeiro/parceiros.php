<?php
// app/modules/financeiro/parceiros.php

$itens = [
  ['nome' => 'Cliente X', 'tipo' => 'Cliente', 'cidade' => 'Goiânia', 'status' => 'Ativo'],
  ['nome' => 'Fornecedor Y', 'tipo' => 'Fornecedor', 'cidade' => 'Aparecida', 'status' => 'Ativo'],
  ['nome' => 'Seguradora Z', 'tipo' => 'Seguradora', 'cidade' => 'São Paulo', 'status' => 'Ativo'],
];
?>

<div class="fin-page">
  <div class="fin-head">
    <h1>Clientes / Fornecedores</h1>
    <p>Mock temporário. Este cadastro migrará para “Cadastros” global.</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <div class="fin-panel">
    <div class="fin-panel__head">
      <div class="fin-panel__title">Lista (mock)</div>
      <span class="fin-badge">temporário</span>
    </div>

    <div class="fin-list">
      <?php foreach ($itens as $it): ?>
        <div class="fin-list__item">
          <div class="fin-list__title"><?= htmlspecialchars($it['nome']) ?></div>
          <div class="fin-list__hint">Tipo: <?= htmlspecialchars($it['tipo']) ?> • Cidade: <?= htmlspecialchars($it['cidade']) ?> • Status: <?= htmlspecialchars($it['status']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>