<?php
// app/modules/financeiro/imoveis.php

$itens = [
  ['nome' => 'Galpão A', 'status' => 'Ativo', 'cidade' => 'Goiânia'],
  ['nome' => 'Galpão B', 'status' => 'Ativo', 'cidade' => 'Aparecida'],
  ['nome' => 'Depósito Antigo', 'status' => 'Inativo', 'cidade' => 'Goiânia'],
];
?>

<div class="fin-page">
  <div class="fin-head">
    <h1>Imóveis</h1>
    <p>Cadastro de imóveis/centros de custo (mock).</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <div class="fin-grid fin-grid--two">
    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Lista</div>
        <span class="fin-badge">mock</span>
      </div>

      <div class="fin-list">
        <?php foreach ($itens as $it): ?>
          <div class="fin-list__item">
            <div class="fin-list__title"><?= htmlspecialchars($it['nome']) ?></div>
            <div class="fin-list__hint">Status: <?= htmlspecialchars($it['status']) ?> • Cidade: <?= htmlspecialchars($it['cidade']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title>Novo imóvel</div>
        <span class="fin-badge">form</span>
      </div>

      <form class="fin-form" method="POST" action="javascript:void(0)">
        <div class="fin-field">
          <label>Nome</label>
          <input type="text" placeholder="Ex: Galpão A" />
        </div>

        <div class="fin-form__row">
          <div class="fin-field">
            <label>Cidade</label>
            <input type="text" placeholder="Ex: Goiânia" />
          </div>
          <div class="fin-field">
            <label>Status</label>
            <input type="text" placeholder="Ativo / Inativo" />
          </div>
        </div>

        <div class="fin-field">
          <label>Endereço (opcional)</label>
          <input type="text" placeholder="Rua, número, bairro..." />
        </div>

        <div class="fin-form__actions">
          <button class="fin-btn" type="button">Salvar (mock)</button>
        </div>
      </form>
    </div>
  </div>
</div>