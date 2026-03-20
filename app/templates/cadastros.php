<?php
// app/templates/cadastros.php

require_once __DIR__ . '/../core/url.php';

$page_title = 'Cadastros';
$page_icon  = 'fa-solid fa-id-card';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/cadastros.css'),
  app_url('/app/static/css/ui_attachments.css'),
];

$extra_js = [
  app_url('/app/static/js/cadastros/cadastros_listagem.js'),
  app_url('/app/static/js/cadastros/cadastros_dashboard.js'),
  app_url('/app/static/js/ui_attachments.js'),
];

$content = __DIR__ . '/../modules/cadastros/home.php';

include __DIR__ . '/base_private.php';
