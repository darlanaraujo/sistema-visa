<?php
// app/templates/dashboard.php

require_once __DIR__ . '/../core/url.php';

$page_title = 'Dashboard';
$page_icon  = 'fa-solid fa-gauge-high';

$extra_css = [
    app_url('/app/static/css/theme.css'),
    app_url('/app/static/css/global.css'),
    app_url('/app/static/css/base_private.css'),
    app_url('/app/static/css/financeiro_dashboard.css'),
    app_url('/app/static/css/financeiro.css'),
    app_url('/app/static/css/dashboard.css'),
];

$content = __DIR__ . '/../modules/dashboard/home.php';

include __DIR__ . '/base_private.php';
