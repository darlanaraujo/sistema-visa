<?php
// app/core/company.php
// Fonte única de dados da empresa (defaults + override persistido).
// Regra: sistema nasce com defaults. Se usuário personalizar no painel, salva override.
// Reset remove override e volta 100% ao padrão.

declare(strict_types=1);

function company_storage_path(): string {
  // Você pode mover para fora do webroot se quiser (ideal).
  return __DIR__ . '/../storage/company_override.json';
}

function company_defaults(): array {
  // ✅ PADRÃO (imutável): ajuste aqui quando quiser mudar o padrão do sistema.
  return [
    'system_name' => 'Sistema Visa Remoções',
    'company' => 'Visa Remoções',
    'cnpj'    => '17.686.570/0001-80',
    'tagline' => 'Sistema Financeiro • Relatórios',
    'site'    => 'visaremocoes.com.br',

    // Logo principal do sistema (sidebar, headers gerais etc.)
    'logo'    => '/sistema-visa/app/static/img/logo.png',

    // Ícone do sistema (aba do navegador, etc.)
    'favicon' => '/sistema-visa/app/static/img/favicon.png',

    // ✅ Logo pequena usada em relatórios/prints/modais
    // Por padrão, queremos o favicon (fica mais “corporate” e encaixa no layout de impressão).
    'report_logo' => '/sistema-visa/app/static/img/favicon.png',

    // Texto padrão de rodapé de relatórios/prints
    'report_footer_note' => 'Documento gerado automaticamente pelo Sistema Visa Remoções.',
  ];
}

function company_load_override(): ?array {
  $path = company_storage_path();

  if (!is_file($path)) return null;

  $raw = @file_get_contents($path);
  if (!is_string($raw) || trim($raw) === '') return null;

  $data = json_decode($raw, true);
  return is_array($data) ? $data : null;
}

function company_write_override(array $override): bool {
  $path = company_storage_path();
  $dir  = dirname($path);

  if (!is_dir($dir)) {
    // Cria pasta de storage se não existir
    @mkdir($dir, 0775, true);
  }

  $json = json_encode($override, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  if (!is_string($json)) return false;

  // LOCK para evitar corrupção em escrita concorrente
  return @file_put_contents($path, $json, LOCK_EX) !== false;
}

function company_reset_override(): bool {
  $path = company_storage_path();
  if (!is_file($path)) return true;
  return @unlink($path);
}

function company_merge(array $base, array $ov): array {
  // Merge simples (override substitui apenas chaves existentes/permitidas)
  foreach ($ov as $k => $v) {
    if (!array_key_exists($k, $base)) continue;
    if (is_string($v)) $base[$k] = trim($v);
  }
  return $base;
}

function company_get(): array {
  $def = company_defaults();
  $ov  = company_load_override();

  if (!$ov) return $def;
  return company_merge($def, $ov);
}

/**
 * Salva um PATCH de override (somente chaves permitidas).
 * - Se valor vier vazio, ele é salvo vazio (painel pode limpar), mas o reset é quem volta ao padrão.
 */
function company_save_patch(array $patch): array {
  $def = company_defaults();
  $currentOv = company_load_override() ?: [];

  // Sanitiza e permite apenas chaves que existem no default
  $clean = [];
  foreach ($def as $k => $_) {
    if (!array_key_exists($k, $patch)) continue;
    $v = $patch[$k];
    if (is_string($v)) $clean[$k] = trim($v);
  }

  // Junta com override atual e persiste
  $nextOv = array_merge($currentOv, $clean);
  company_write_override($nextOv);

  // Retorna o "resultado final" (defaults + override)
  return company_merge($def, $nextOv);
}