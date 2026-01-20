<?php
$title = 'Login | Sistema Visa';

$error = $_GET['error'] ?? '';
$error = is_string($error) ? $error : '';

$contentFile = __DIR__ . '/login_content.php';
require __DIR__ . '/base_public.php';
