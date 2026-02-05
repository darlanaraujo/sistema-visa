<?php
// app/templates/financeiro_parceiros.php
$page_title = 'Financeiro • Clientes / Fornecedores';
$page_icon  = 'fa-solid fa-users';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  // Se esta tela reutiliza blocos do dashboard financeiro (atalhos/listas/agenda):
  // '/sistema-visa/app/static/css/financeiro_dashboard.css',
];

$content = __DIR__ . '/../modules/financeiro/parceiros.php';
include __DIR__ . '/base_private.php';