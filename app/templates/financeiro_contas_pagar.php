<?php
// app/templates/financeiro_contas_pagar.php

$page_title = 'Financeiro • Contas a Pagar';
$page_icon  = 'fa-solid fa-file-invoice-dollar';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/financeiro_contas_pagar.css',
];

// JS do módulo financeiro (ordem obrigatória)
$extra_js = [
  '/sistema-visa/app/static/js/financeiro/data/fin_bootstrap_refs.js',
  '/sistema-visa/app/static/js/financeiro/data/fin_store.js',
  '/sistema-visa/app/static/js/financeiro_contas_pagar.js',
];

$content = __DIR__ . '/../modules/financeiro/contas_pagar.php';
include __DIR__ . '/base_private.php';