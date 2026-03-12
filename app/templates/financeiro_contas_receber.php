<?php
// app/templates/financeiro_contas_receber.php
require_once __DIR__ . '/../core/url.php';

$page_title = 'Financeiro • Contas a Receber';
$page_icon  = 'fa-solid fa-hand-holding-dollar';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/financeiro_contas_receber.css'),
];

$extra_js = [
  
  app_url('/app/static/js/financeiro/data/fin_store.js'),
  app_url('/app/static/js/financeiro/data/fin_bootstrap_refs.js'),
  app_url('/app/static/js/financeiro/data/fin_refs_bridge.js'), // <<< ADICIONA
  app_url('/app/static/js/financeiro/financeiro_contas_receber.js'),
];

$content = __DIR__ . '/../modules/financeiro/contas_receber.php';
include __DIR__ . '/base_private.php';
