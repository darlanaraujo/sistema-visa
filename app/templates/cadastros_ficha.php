<?php
// app/templates/cadastros_ficha.php

require_once __DIR__ . '/../core/url.php';

$modo = trim((string)($_GET['modo'] ?? ''));

$page_title = $modo === 'cadastro' ? 'Cadastros • Cadastro' : 'Cadastros • Ficha';
$page_icon  = 'fa-solid fa-id-card-clip';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/cadastros.css'),
  app_url('/app/static/css/ui_attachments.css'),
];

$extra_js = [
  app_url('/app/static/js/cadastros/cadastros_listagem.js'),
  app_url('/app/static/js/ui_attachments.js'),
];

if ($modo === 'cadastro') {
  $extra_js[] = app_url('/app/static/js/cadastros/cadastros_form.js');
}

$content = __DIR__ . '/../modules/cadastros/ficha.php';

include __DIR__ . '/base_private.php';
