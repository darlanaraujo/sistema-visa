<?php
// app/templates/financeiro_categorias.php

$page_title = 'Financeiro • Categorias';
$page_icon  = 'fa-solid fa-tags';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  // Se esta tela reutiliza blocos do dashboard financeiro (atalhos/listas/agenda):
  // '/sistema-visa/app/static/css/financeiro_dashboard.css',
];

$content = __DIR__ . '/../modules/financeiro/categorias.php';
include __DIR__ . '/base_private.php';