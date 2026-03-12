<?php
// app/templates/financeiro_relatorios.php
require_once __DIR__ . '/../core/url.php';

$page_title = 'Financeiro • Relatórios';
$page_icon  = 'fa-solid fa-chart-pie';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/financeiro_relatorios.css'),
  app_url('/app/static/css/financeiro_relatorios_print.css'),
];

$extra_js = [
  // Chart.js (para gráficos no relatório e depois no print/PDF)
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',

  app_url('/app/static/js/financeiro/data/fin_store.js'),
  app_url('/app/static/js/financeiro/data/fin_bootstrap_refs.js'),
  app_url('/app/static/js/financeiro/financeiro_relatorios.js'),
];

$content = __DIR__ . '/../modules/financeiro/relatorios.php';
include __DIR__ . '/base_private.php';
