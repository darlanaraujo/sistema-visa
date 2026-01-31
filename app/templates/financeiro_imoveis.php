<?php
// app/templates/financeiro_imoveis.php
$page_title = 'Financeiro • Imóveis';
$page_icon  = 'fa-solid fa-warehouse';

$extra_css = ['/sistema-visa/app/static/css/financeiro.css'];

$content = __DIR__ . '/../modules/financeiro/imoveis.php';
include __DIR__ . '/base_private.php';