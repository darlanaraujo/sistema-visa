<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_api.php';
require_once __DIR__ . '/../src/Support/HttpClient.php';

$request = api_request_data();
$cep = preg_replace('/\D+/', '', (string)($_GET['cep'] ?? $request['cep'] ?? ''));
if (strlen($cep) !== 8) {
  api_error('Informe um CEP válido com 8 dígitos.', 422);
}

$result = HttpClient::getJson('https://viacep.com.br/ws/' . $cep . '/json/', 4);
if (!$result['ok'] || !is_array($result['data'])) {
  api_error('Não foi possível consultar o CEP no momento.', 502);
}

$payload = $result['data'];
if (!empty($payload['erro'])) {
  api_error('CEP não encontrado.', 404);
}

api_success([
  'source' => 'viacep',
  'message' => 'Endereço localizado com sucesso.',
  'data' => [
    'cep' => $cep,
    'endereco' => trim((string)($payload['logradouro'] ?? '')),
    'bairro' => trim((string)($payload['bairro'] ?? '')),
    'cidade' => trim((string)($payload['localidade'] ?? '')),
    'estado' => strtoupper(trim((string)($payload['uf'] ?? ''))),
  ],
]);
