<?php
// app/templates/cadastros_listagem.php

require_once __DIR__ . '/../core/url.php';

$tipo = trim((string)($_GET['tipo'] ?? ''));

$titleMap = [
  'clientes' => 'Cadastros • Clientes',
  'fornecedores' => 'Cadastros • Fornecedores',
  'motoristas' => 'Cadastros • Motoristas',
  'transportadoras' => 'Cadastros • Transportadoras',
];

$page_title = $titleMap[$tipo] ?? 'Cadastros • Listagem';
$page_icon  = 'fa-solid fa-address-book';

$extra_css = [
  app_url('/app/static/css/financeiro.css'),
  app_url('/app/static/css/cadastros.css'),
  app_url('/app/static/css/ui_attachments.css'),
];

$extra_js = [
  app_url('/app/static/js/cadastros/cadastros_listagem.js'),
  app_url('/app/static/js/ui_attachments.js'),
];

$content = __DIR__ . '/../modules/cadastros/listagem.php';

include __DIR__ . '/base_private.php';
