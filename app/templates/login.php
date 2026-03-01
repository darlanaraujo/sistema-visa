<?php
// app/templates/login.php

$title = 'Login | Sistema Visa';

$error = $_GET['error'] ?? '';
$error = is_string($error) ? $error : '';

// ✅ CSS do login + tokens do sistema (se base_public já inclui theme.css/global.css, pode remover daqui)
$extra_css = [
  '/sistema-visa/app/static/css/theme.css',
  '/sistema-visa/app/static/css/login.css',
];

// ✅ JS de primeiro paint (SEM defer) — evita flash (tema/cor/sidebar state)
$extra_js_head = [
  '/sistema-visa/app/static/js/system/sys_bootstrap_ui.js',
];

// ✅ JS normal (defer) — aplica preferências completas (logo/favicon/cores/tema)
$extra_js = [
  '/sistema-visa/app/static/js/system/sys_personalizacao.js',
];

$contentFile = __DIR__ . '/login_content.php';
require __DIR__ . '/base_public.php';