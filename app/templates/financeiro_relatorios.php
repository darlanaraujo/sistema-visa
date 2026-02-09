<?php
// app/templates/financeiro_relatorios.php
$page_title = 'Financeiro • Relatórios';
$page_icon  = 'fa-solid fa-chart-pie';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/financeiro_relatorios.css',
  '/sistema-visa/app/static/css/financeiro_relatorios_print.css',
];

$extra_js = [
  // Chart.js (para gráficos no relatório e depois no print/PDF)
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',

  '/sistema-visa/app/static/js/financeiro/data/fin_bootstrap_refs.js',
  '/sistema-visa/app/static/js/financeiro/data/fin_store.js',
  '/sistema-visa/app/static/js/financeiro_relatorios.js',
  // '/sistema-visa/app/static/js/financeiro_relatorios_print.js',
];

$content = __DIR__ . '/../modules/financeiro/relatorios.php';
include __DIR__ . '/base_private.php';