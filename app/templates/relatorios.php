<?php
// app/templates/relatorios.php

$page_title = 'Relatórios';
$page_icon  = 'fa-solid fa-chart-line';

$extra_css = [
  '/sistema-visa/app/static/css/relatorios.css',
];

$content = __DIR__ . '/../modules/relatorios/home.php';

include __DIR__ . '/base_private.php';