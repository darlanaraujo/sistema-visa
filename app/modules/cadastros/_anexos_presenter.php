<?php
declare(strict_types=1);

if (!function_exists('cad_present_anexo')) {
  function cad_present_anexo(array $anexo): array {
    $arquivoId = (int)($anexo['id'] ?? 0);
    if ($arquivoId <= 0) {
      return $anexo;
    }

    $previewUrl = app_url('/app/templates/arquivo.php?' . http_build_query([
      'id' => $arquivoId,
    ]));
    $downloadUrl = app_url('/app/templates/arquivo.php?' . http_build_query([
      'id' => $arquivoId,
      'download' => '1',
    ]));

    $anexo['previewUrl'] = $previewUrl;
    $anexo['downloadUrl'] = $downloadUrl;

    return $anexo;
  }
}

if (!function_exists('cad_present_anexos')) {
  function cad_present_anexos(array $anexos): array {
    return array_values(array_filter(array_map(
      static fn ($item) => is_array($item) ? cad_present_anexo($item) : null,
      $anexos
    )));
  }
}
