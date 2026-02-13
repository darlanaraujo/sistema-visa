<?php
// app/templates/financeiro_contas_receber.php
$page_title = 'Financeiro • Contas a Receber';
$page_icon  = 'fa-solid fa-hand-holding-dollar';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/financeiro_contas_receber.css',
];

$extra_js = [
  '/sistema-visa/app/static/js/financeiro/data/fin_bootstrap_refs.js',
  '/sistema-visa/app/static/js/financeiro/data/fin_store.js',
  '/sistema-visa/app/static/js/financeiro/data/fin_refs_bridge.js', // <<< ADICIONA
  '/sistema-visa/app/static/js/financeiro_contas_receber.js',
];

$content = __DIR__ . '/../modules/financeiro/contas_receber.php';
include __DIR__ . '/base_private.php';