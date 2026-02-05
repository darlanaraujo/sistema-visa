<?php
// app/templates/financeiro_imoveis.php
$page_title = 'Financeiro • Imóveis';
$page_icon  = 'fa-solid fa-warehouse';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  // Se esta tela reutiliza blocos do dashboard financeiro (atalhos/listas/agenda):
  // '/sistema-visa/app/static/css/financeiro_dashboard.css',
];

$content = __DIR__ . '/../modules/financeiro/imoveis.php';
include __DIR__ . '/base_private.php';