<?php
// app/templates/lotes.php

$page_title = 'Lotes';
$page_icon  = 'fa-solid fa-boxes-stacked';

$extra_css = [
  '/sistema-visa/app/static/css/lotes.css',
];

$content = __DIR__ . '/../modules/lotes/home.php';

include __DIR__ . '/base_private.php';