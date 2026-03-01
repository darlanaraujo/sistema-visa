<?php
// app/templates/dashboard.php

$page_title = 'Dashboard';
$page_icon  = 'fa-solid fa-gauge-high';

$extra_css = [
    '/sistema-visa/app/static/css/theme.css',
    '/sistema-visa/app/static/css/global.css',
    '/sistema-visa/app/static/css/base_private.css',
    '/sistema-visa/app/static/css/financeiro_dashboard.css',
    '/sistema-visa/app/static/css/financeiro.css',
    '/sistema-visa/app/static/css/dashboard.css',
];

$content = __DIR__ . '/../modules/dashboard/home.php';

include __DIR__ . '/base_private.php';
