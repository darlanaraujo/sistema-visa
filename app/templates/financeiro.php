<?php
// app/templates/financeiro.php

require_once __DIR__ . '/../core/url.php';

$page_title = 'Financeiro';
$page_icon  = 'fa-solid fa-coins';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/financeiro_dashboard.css'),
];

// JS do módulo financeiro (ordem obrigatória)
$extra_js = [
  // Chart.js (para gráficos no relatório e depois no print/PDF)
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',

  app_url('/app/static/js/financeiro/data/fin_store.js'),
  app_url('/app/static/js/financeiro/data/fin_bootstrap_refs.js'),
  app_url('/app/static/js/financeiro/financeiro_dashboard.js'),
];

$content = __DIR__ . '/../modules/financeiro/home.php';
include __DIR__ . '/base_private.php';
