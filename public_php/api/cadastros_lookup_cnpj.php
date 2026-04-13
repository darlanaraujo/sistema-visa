<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_api.php';
require_once __DIR__ . '/../src/Support/HttpClient.php';

function cad_lookup_first_non_empty(mixed ...$values): string {
  foreach ($values as $value) {
    $text = trim((string)$value);
    if ($text !== '') {
      return $text;
    }
  }

  return '';
}

function cad_lookup_join_address(array $parts): string {
  $clean = [];
  foreach ($parts as $part) {
    $text = trim((string)$part);
    if ($text !== '') {
      $clean[] = $text;
    }
  }

  return implode(', ', $clean);
}

function cad_lookup_normalize_brasilapi(string $cnpj, array $payload): array {
  return [
    'source' => 'brasilapi',
    'cnpj' => $cnpj,
    'razao_social' => trim((string)($payload['razao_social'] ?? '')),
    'nome_fantasia' => trim((string)($payload['nome_fantasia'] ?? '')),
    'inscricao_estadual' => trim((string)($payload['inscricao_estadual'] ?? $payload['inscricaoEstadual'] ?? '')),
    'cep' => preg_replace('/\D+/', '', (string)($payload['cep'] ?? '')),
    'endereco' => cad_lookup_join_address([
      $payload['logradouro'] ?? $payload['endereco'] ?? '',
      $payload['numero'] ?? '',
      $payload['complemento'] ?? '',
    ]),
    'bairro' => trim((string)($payload['bairro'] ?? '')),
    'cidade' => trim((string)($payload['municipio'] ?? $payload['cidade'] ?? $payload['localidade'] ?? '')),
    'estado' => strtoupper(trim((string)($payload['uf'] ?? $payload['estado'] ?? ''))),
  ];
}

function cad_lookup_normalize_cnpjws(string $cnpj, array $payload): array {
  $estabelecimento = is_array($payload['estabelecimento'] ?? null) ? $payload['estabelecimento'] : [];
  $cidade = is_array($estabelecimento['cidade'] ?? null) ? $estabelecimento['cidade'] : [];
  $estado = is_array($estabelecimento['estado'] ?? null) ? $estabelecimento['estado'] : [];
  $inscricoes = is_array($estabelecimento['inscricoes_estaduais'] ?? null) ? $estabelecimento['inscricoes_estaduais'] : [];
  $inscricaoEstadual = '';
  foreach ($inscricoes as $item) {
    if (!is_array($item)) {
      continue;
    }
    $candidate = trim((string)($item['inscricao_estadual'] ?? $item['inscricaoEstadual'] ?? ''));
    if ($candidate !== '') {
      $inscricaoEstadual = $candidate;
      break;
    }
  }

  return [
    'source' => 'cnpjws',
    'cnpj' => $cnpj,
    'razao_social' => trim((string)($payload['razao_social'] ?? '')),
    'nome_fantasia' => trim((string)($estabelecimento['nome_fantasia'] ?? $payload['nome_fantasia'] ?? '')),
    'inscricao_estadual' => $inscricaoEstadual,
    'cep' => preg_replace('/\D+/', '', (string)($estabelecimento['cep'] ?? '')),
    'endereco' => cad_lookup_join_address([
      $estabelecimento['tipo_logradouro'] ?? '',
      $estabelecimento['logradouro'] ?? '',
      $estabelecimento['numero'] ?? '',
      $estabelecimento['complemento'] ?? '',
    ]),
    'bairro' => trim((string)($estabelecimento['bairro'] ?? '')),
    'cidade' => cad_lookup_first_non_empty($cidade['nome'] ?? '', $cidade['nome_cidade'] ?? ''),
    'estado' => strtoupper(cad_lookup_first_non_empty($estado['sigla'] ?? '', $estabelecimento['estado'] ?? '')),
  ];
}

function cad_lookup_cnpj(string $cnpj): ?array {
  $providers = [
    [
      'url' => 'https://brasilapi.com.br/api/cnpj/v1/' . $cnpj,
      'normalize' => static fn (array $payload): array => cad_lookup_normalize_brasilapi($cnpj, $payload),
    ],
    [
      'url' => 'https://publica.cnpj.ws/cnpj/' . $cnpj,
      'normalize' => static fn (array $payload): array => cad_lookup_normalize_cnpjws($cnpj, $payload),
    ],
  ];

  foreach ($providers as $provider) {
    $result = HttpClient::getJson((string)$provider['url'], 5);
    if (!$result['ok'] || !is_array($result['data'])) {
      continue;
    }

    $normalized = $provider['normalize']((array)$result['data']);
    if (trim((string)($normalized['razao_social'] ?? '')) === '') {
      continue;
    }

    return $normalized;
  }

  return null;
}

$request = api_request_data();
$cnpj = preg_replace('/\D+/', '', (string)($_GET['cnpj'] ?? $request['cnpj'] ?? ''));
if (strlen($cnpj) !== 14) {
  api_error('Informe um CNPJ válido com 14 dígitos.', 422);
}

$payload = cad_lookup_cnpj($cnpj);
if (!is_array($payload)) {
  api_error('Não foi possível consultar o CNPJ no momento.', 502);
}

api_success([
  'source' => (string)($payload['source'] ?? 'cnpj'),
  'message' => 'Dados cadastrais localizados com sucesso.',
  'data' => [
    'cnpj' => (string)($payload['cnpj'] ?? $cnpj),
    'razao_social' => (string)($payload['razao_social'] ?? ''),
    'nome_fantasia' => (string)($payload['nome_fantasia'] ?? ''),
    'inscricao_estadual' => (string)($payload['inscricao_estadual'] ?? ''),
    'cep' => (string)($payload['cep'] ?? ''),
    'endereco' => (string)($payload['endereco'] ?? ''),
    'bairro' => (string)($payload['bairro'] ?? ''),
    'cidade' => (string)($payload['cidade'] ?? ''),
    'estado' => (string)($payload['estado'] ?? ''),
  ],
]);
