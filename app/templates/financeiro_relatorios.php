<?php
// app/templates/financeiro_relatorios.php
$page_title = 'Financeiro • Relatórios';
$page_icon  = 'fa-solid fa-chart-pie';

$extra_css = ['/sistema-visa/app/static/css/financeiro.css'];

$content = __DIR__ . '/../modules/financeiro/relatorios.php';
include __DIR__ . '/base_private.php';