<?php
// app/templates/financeiro_parceiros.php
$page_title = 'Financeiro • Clientes / Fornecedores';
$page_icon  = 'fa-solid fa-users';

$extra_css = ['/sistema-visa/app/static/css/financeiro.css'];

$content = __DIR__ . '/../modules/financeiro/parceiros.php';
include __DIR__ . '/base_private.php';