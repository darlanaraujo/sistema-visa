<?php
declare(strict_types=1);

if (!function_exists('app_base_path')) {
  function app_base_path(): string {
    static $base = null;

    if ($base !== null) {
      return $base;
    }

    $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? (string)$_SERVER['DOCUMENT_ROOT'] : '';
    $projectRoot = dirname(__DIR__, 2);

    $documentRootReal = $documentRoot !== '' ? realpath($documentRoot) : false;
    $projectRootReal = realpath($projectRoot);

    if (!is_string($documentRootReal) || !is_string($projectRootReal) || $documentRootReal === '') {
      $base = '';
      return $base;
    }

    $doc = rtrim(str_replace('\\', '/', $documentRootReal), '/');
    $project = rtrim(str_replace('\\', '/', $projectRootReal), '/');

    if ($project === $doc) {
      $base = '';
      return $base;
    }

    if (strpos($project, $doc . '/') !== 0) {
      $base = '';
      return $base;
    }

    $base = substr($project, strlen($doc));
    return $base ?: '';
  }
}

if (!function_exists('app_url')) {
  function app_url(string $path = '/'): string {
    $trimmed = trim($path);

    if ($trimmed === '') {
      return app_base_path() ?: '/';
    }

    if (preg_match('#^(?:https?:)?//#i', $trimmed)) {
      return $trimmed;
    }

    $normalized = '/' . ltrim($trimmed, '/');
    $base = app_base_path();

    return ($base !== '' ? $base : '') . $normalized;
  }
}
