<?php
declare(strict_types=1);

final class HttpClient {
  public static function getJson(string $url, int $timeoutSeconds = 5, array $headers = []): array {
    $response = self::get($url, $timeoutSeconds, $headers);
    $decoded = json_decode($response['body'] ?? '', true);

    return [
      'ok' => $response['ok'] && is_array($decoded),
      'status' => (int)($response['status'] ?? 0),
      'data' => is_array($decoded) ? $decoded : null,
      'error' => $response['ok']
        ? (is_array($decoded) ? '' : 'Resposta JSON inválida.')
        : (string)($response['error'] ?? 'Falha na consulta externa.'),
    ];
  }

  public static function get(string $url, int $timeoutSeconds = 5, array $headers = []): array {
    if (function_exists('curl_init')) {
      return self::curlGet($url, $timeoutSeconds, $headers);
    }

    return self::streamGet($url, $timeoutSeconds, $headers);
  }

  private static function curlGet(string $url, int $timeoutSeconds, array $headers): array {
    $ch = curl_init($url);
    if ($ch === false) {
      return ['ok' => false, 'status' => 0, 'body' => '', 'error' => 'Não foi possível iniciar a consulta externa.'];
    }

    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_CONNECTTIMEOUT => $timeoutSeconds,
      CURLOPT_TIMEOUT => $timeoutSeconds,
      CURLOPT_HTTPHEADER => $headers,
      CURLOPT_USERAGENT => 'AuraLabs-SistemaVisa/1.0',
    ]);

    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
      'ok' => $body !== false && $status >= 200 && $status < 300,
      'status' => $status,
      'body' => $body !== false ? (string)$body : '',
      'error' => $error !== '' ? $error : 'Falha na consulta externa.',
    ];
  }

  private static function streamGet(string $url, int $timeoutSeconds, array $headers): array {
    $context = stream_context_create([
      'http' => [
        'method' => 'GET',
        'timeout' => $timeoutSeconds,
        'ignore_errors' => true,
        'header' => implode("\r\n", array_merge([
          'Accept: application/json',
          'User-Agent: AuraLabs-SistemaVisa/1.0',
        ], $headers)),
      ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $responseHeaders = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : [];
    $status = 0;

    foreach ($responseHeaders as $headerLine) {
      if (preg_match('/HTTP\/\S+\s+(\d{3})/', (string)$headerLine, $matches)) {
        $status = (int)$matches[1];
        break;
      }
    }

    return [
      'ok' => $body !== false && $status >= 200 && $status < 300,
      'status' => $status,
      'body' => $body !== false ? (string)$body : '',
      'error' => $body === false ? 'Falha na consulta externa.' : '',
    ];
  }
}
