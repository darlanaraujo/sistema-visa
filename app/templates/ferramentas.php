<?php
// app/templates/ferramentas.php

$page_title = 'Ferramentas';
$page_icon  = 'fa-solid fa-screwdriver-wrench';

$content = __DIR__ . '/../modules/ferramentas/home.php';

$extra_css = [
    '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/ferramentas.css',
];

$extra_js = [
  '/sistema-visa/app/static/js/ferramentas/data/fer_store.js',
  '/sistema-visa/app/static/js/ferramentas/ferramentas.js',
  '/sistema-visa/app/static/js/system/sys_company_panel.js'
];

include __DIR__ . '/base_private.php';
