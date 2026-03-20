<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_api.php';
require_once __DIR__ . '/../src/Support/HttpClient.php';

$request = api_request_data();
$cnpj = preg_replace('/\D+/', '', (string)($_GET['cnpj'] ?? $request['cnpj'] ?? ''));
if (strlen($cnpj) !== 14) {
  api_error('Informe um CNPJ válido com 14 dígitos.', 422);
}

$result = HttpClient::getJson('https://brasilapi.com.br/api/cnpj/v1/' . $cnpj, 5);
if (!$result['ok'] || !is_array($result['data'])) {
  api_error('Não foi possível consultar o CNPJ no momento.', 502);
}

$payload = $result['data'];
api_success([
  'source' => 'brasilapi',
  'message' => 'Dados cadastrais localizados com sucesso.',
  'data' => [
    'cnpj' => $cnpj,
    'razao_social' => trim((string)($payload['razao_social'] ?? '')),
    'nome_fantasia' => trim((string)($payload['nome_fantasia'] ?? '')),
    'inscricao_estadual' => trim((string)($payload['inscricao_estadual'] ?? $payload['inscricaoEstadual'] ?? '')),
  ],
]);
