<?php
// app/support/helpers.php
// Helpers globais do sistema (fonte única).
// Regras:
// - Sempre protegido por function_exists para evitar redeclare.
// - Usar apenas helpers genéricos e pequenos (sem regra de negócio).

if (!function_exists('h')) {
  /**
   * Escape seguro para HTML (UTF-8).
   * Uso: <?= h($valor) ?>
   */
  function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  }
}