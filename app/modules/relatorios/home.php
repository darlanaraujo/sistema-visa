<?php
// app/modules/relatorios/home.php

$rel = (string)($_GET['rel'] ?? '');
$rel = trim($rel);

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$nome = $rel !== '' ? $rel : 'Selecione um relatório';
$desc = $rel !== '' ? 'Placeholder de geração/visualização do relatório selecionado.' : 'Escolha um relatório pelo Dashboard ou pelo menu.';
?>

<div class="module-page">
  <div class="module-head">
    <h1>Relatórios</h1>
    <p><?= h($desc) ?></p>
  </div>

  <div class="module-card">
    <div class="module-card__title">Relatório selecionado</div>
    <div class="module-card__value"><?= h($nome) ?></div>
    <div class="module-card__hint">
      * Futuro: aqui entra filtro, período, exportação PDF/Excel e impressão.
    </div>
  </div>
</div>