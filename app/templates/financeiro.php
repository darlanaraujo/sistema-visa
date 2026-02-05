<?php
// app/templates/financeiro.php

$page_title = 'Financeiro';
$page_icon  = 'fa-solid fa-coins';

$extra_css = [
  '/sistema-visa/app/static/css/financeiro.css',
  '/sistema-visa/app/static/css/financeiro_dashboard.css',
];

$content = __DIR__ . '/../modules/financeiro/home.php';
include __DIR__ . '/base_private.php';