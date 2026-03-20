<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap_api.php';
require_once __DIR__ . '/../src/Repositories/CadastroRepository.php';

$companyId = api_company_id([]);
$term = trim((string)($_GET['term'] ?? ''));
$tipo = trim((string)($_GET['tipo'] ?? ''));
$limitRaw = (int)($_GET['limit'] ?? 50);
$limit = $limitRaw > 0 ? min($limitRaw, 100) : 50;

$repo = new CadastroRepository();
$items = $repo->list([
  'status' => 'ativo',
  'term' => $term,
  'tipo' => $tipo !== '' ? $tipo : null,
  'limit' => $limit,
  'offset' => 0,
], $companyId);

$payload = array_values(array_filter(array_map(
  static function ($item): ?array {
    if (!is_array($item)) {
      return null;
    }

    $tipoPessoa = strtoupper((string)($item['tipoPessoa'] ?? 'PF'));
    $nome = trim((string)($item['nome'] ?? ''));
    $razaoSocial = trim((string)($item['razaoSocial'] ?? ''));
    $documento = trim((string)($item['documento'] ?? ''));
    $displayName = $tipoPessoa === 'PJ'
      ? ($razaoSocial !== '' ? $razaoSocial : $nome)
      : ($nome !== '' ? $nome : $razaoSocial);

    if ($displayName === '') {
      return null;
    }

    $tipos = array_values(array_filter(array_map(
      static fn($tipoItem) => is_array($tipoItem) ? trim((string)($tipoItem['slug'] ?? '')) : '',
      (array)($item['tipos'] ?? [])
    )));

    return [
      'id' => (int)($item['id'] ?? 0),
      'label' => $displayName,
      'searchLabel' => $documento !== '' ? ($displayName . ' • ' . $documento) : $displayName,
      'documento' => $documento,
      'tipoPessoa' => $tipoPessoa,
      'tipos' => $tipos,
      'status' => (string)($item['status'] ?? ''),
    ];
  },
  $items
)));

api_success([
  'items' => $payload,
  'count' => count($payload),
]);
