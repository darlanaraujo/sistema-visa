<?php
// app/templates/lotes.php

require_once __DIR__ . '/../core/url.php';

$page_title = 'Lotes';
$page_icon  = 'fa-solid fa-boxes-stacked';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/cadastros.css'),
  app_url('/app/static/css/ui_attachments.css'),
  app_url('/app/static/css/lotes_shared.css'),
  app_url('/app/static/css/lotes.css'),
];

$extra_js = [
  'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
  app_url('/app/static/js/financeiro/data/fin_store.js'),
  app_url('/app/static/js/ui_attachments.js'),
  app_url('/app/static/js/cadastros/cadastros_listagem.js'),
  app_url('/app/static/js/lotes/lotes_dashboard.js'),
];

$content = __DIR__ . '/../modules/lotes/home.php';

include __DIR__ . '/base_private.php';
