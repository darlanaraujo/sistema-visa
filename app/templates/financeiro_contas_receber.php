<?php
// app/templates/financeiro_contas_receber.php
$page_title = 'Financeiro • Contas a Receber';
$page_icon  = 'fa-solid fa-hand-holding-dollar';

$extra_css = ['/sistema-visa/app/static/css/financeiro.css'];

$content = __DIR__ . '/../modules/financeiro/contas_receber.php';
include __DIR__ . '/base_private.php';