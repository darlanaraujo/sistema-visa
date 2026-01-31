<?php
// app/modules/financeiro/categorias.php
$cats = [
  ['cat' => 'Aluguel', 'sub' => 'Galpão A / Galpão B'],
  ['cat' => 'Energia', 'sub' => 'Galpões / Escritório'],
  ['cat' => 'Transporte', 'sub' => 'Fretes / Motoristas'],
  ['cat' => 'Manutenção', 'sub' => 'Equipamentos / Estrutura'],
];
?>

<div class="fin-page">
  <div class="fin-head">
    <h1>Categorias e Subcategorias</h1>
    <p>Estrutura visual para classificação financeira (mock).</p>
  </div>

  <?php include __DIR__ . '/_nav.php'; ?>

  <div class="fin-grid fin-grid--two">
    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Categorias</div>
        <span class="fin-badge">mock</span>
      </div>

      <div class="fin-list">
        <?php foreach ($cats as $c): ?>
          <div class="fin-list__item">
            <div class="fin-list__title"><?= htmlspecialchars($c['cat']) ?></div>
            <div class="fin-list__hint">Sub: <?= htmlspecialchars($c['sub']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="fin-panel">
      <div class="fin-panel__head">
        <div class="fin-panel__title">Nova categoria</div>
        <span class="fin-badge">form</span>
      </div>

      <form class="fin-form" method="POST" action="javascript:void(0)">
        <div class="fin-form__row">
          <div class="fin-field">
            <label>Categoria</label>
            <input type="text" placeholder="Ex: Serviços" />
          </div>
          <div class="fin-field">
            <label>Subcategoria</label>
            <input type="text" placeholder="Ex: Telefonia" />
          </div>
        </div>

        <div class="fin-form__actions">
          <button class="fin-btn" type="button">Salvar (mock)</button>
        </div>
      </form>
    </div>
  </div>
</div>