<?php
// app/templates/financeiro_contas_pagar.php

$page_title = 'Financeiro • Contas a Pagar';
$page_icon  = 'fa-solid fa-file-invoice-dollar';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/financeiro_contas_pagar.css',
];

$content = __DIR__ . '/../modules/financeiro/contas_pagar.php';
include __DIR__ . '/base_private.php';