<?php
// app/templates/lotes.php

require_once __DIR__ . '/../core/url.php';

$page_title = 'Lotes';
$page_icon  = 'fa-solid fa-boxes-stacked';

$extra_css = [
  app_url('/app/static/css/lotes.css'),
];

$content = __DIR__ . '/../modules/lotes/home.php';

include __DIR__ . '/base_private.php';
