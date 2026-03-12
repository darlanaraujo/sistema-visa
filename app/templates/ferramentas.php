<?php
// app/templates/ferramentas.php

require_once __DIR__ . '/../core/url.php';

$page_title = 'Ferramentas';
$page_icon  = 'fa-solid fa-screwdriver-wrench';

$content = __DIR__ . '/../modules/ferramentas/home.php';

$extra_css = [
    app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/ferramentas.css'),
];

$extra_js = [
  app_url('/app/static/js/ferramentas/data/fer_store.js'),
  app_url('/app/static/js/ferramentas/ferramentas.js'),
  app_url('/app/static/js/system/sys_company_panel.js')
];

include __DIR__ . '/base_private.php';
