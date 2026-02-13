<?php
// app/templates/financeiro.php

$page_title = 'Financeiro';
$page_icon  = 'fa-solid fa-coins';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/financeiro_dashboard.css',
];

// JS do módulo financeiro (ordem obrigatória)
$extra_js = [
  // Chart.js (para gráficos no relatório e depois no print/PDF)
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',

  '/sistema-visa/app/static/js/financeiro/data/fin_bootstrap_refs.js',
  '/sistema-visa/app/static/js/financeiro/data/fin_store.js',
  '/sistema-visa/app/static/js/financeiro_relatorios_print.js',
  '/sistema-visa/app/static/js/financeiro_dashboard.js',
];

$content = __DIR__ . '/../modules/financeiro/home.php';
include __DIR__ . '/base_private.php';

