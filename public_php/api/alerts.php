<?php
// public_php/api/alerts.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// (Opcional) bootstrap/autoload do projeto
// require __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * ✅ AUTH REAL DO SISTEMA
 * Pelo debug, a sessão guarda "auth_user" (não "user_id").
 */
$auth = $_SESSION['auth_user'] ?? null;
if (empty($auth)) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'unauthorized']);
  exit;
}

// Se um dia precisar do user_id:
$userId = null;
if (is_array($auth) && isset($auth['id'])) $userId = $auth['id'];
else if (is_object($auth) && isset($auth->id)) $userId = $auth->id;
// (por enquanto não usamos)

/**
 * Helper de data em ISO (UTC)
 */
function nowIso() {
  return gmdate('c');
}

$module = (string)($_GET['module'] ?? '');
$module = trim(strtolower($module));

/**
 * ---------------------------------------------------------
 * PLACEHOLDERS por módulo
 * - Mantemos compatibilidade com a lista "alerts" (plana)
 * - E adicionamos o "blocks" (modelo-regra) quando existir
 * ---------------------------------------------------------
 */
$alerts = [];
$blocks = null;

if ($module === 'financeiro') {

  // ✅ MODELO-REGRA (placeholder) — já simula a experiência futura
  $blocks = [
    [
      'key'   => 'cp',
      'title' => 'Contas a Pagar',
      'href'  => '/sistema-visa/app/templates/financeiro_contas_pagar.php',
      'summary' => [
        'success' => 8,
        'info'    => 2,
        'warning' => 3,
        'danger'  => 1,
      ],
      'items' => [
        [
          'id' => 'fin-cp-101',
          'type' => 'danger', // info | warning | danger | success
          'title' => 'Vencido hoje',
          'message' => '1 título vencido — exige ação imediata (placeholder).',
          'href' => '/sistema-visa/app/templates/financeiro_contas_pagar.php',
          'ts' => nowIso(),
        ],
        [
          'id' => 'fin-cp-102',
          'type' => 'warning',
          'title' => 'Próximas 48h',
          'message' => '3 títulos vencem em até 48h (placeholder).',
          'href' => '/sistema-visa/app/templates/financeiro_contas_pagar.php',
          'ts' => nowIso(),
        ],
        [
          'id' => 'fin-cp-103',
          'type' => 'info',
          'title' => 'Conferência',
          'message' => '2 pagamentos aguardando validação manual (placeholder).',
          'href' => '/sistema-visa/app/templates/financeiro_contas_pagar.php',
          'ts' => nowIso(),
        ],
      ],
    ],
    [
      'key'   => 'cr',
      'title' => 'Contas a Receber',
      'href'  => '/sistema-visa/app/templates/financeiro_contas_receber.php',
      'summary' => [
        'success' => 5,
        'info'    => 4,
        'warning' => 1,
        'danger'  => 0,
      ],
      'items' => [
        [
          'id' => 'fin-cr-201',
          'type' => 'info',
          'title' => 'Confirmação',
          'message' => '4 recebíveis em aberto para conciliação (placeholder).',
          'href' => '/sistema-visa/app/templates/financeiro_contas_receber.php',
          'ts' => nowIso(),
        ],
        [
          'id' => 'fin-cr-202',
          'type' => 'warning',
          'title' => 'Atenção',
          'message' => '1 recebível com risco de atraso (placeholder).',
          'href' => '/sistema-visa/app/templates/financeiro_contas_receber.php',
          'ts' => nowIso(),
        ],
      ],
    ],
  ];

  // ✅ Compatibilidade: lista plana "alerts"
  // (o front novo usa blocks; se algum lugar antigo usar alerts, continua ok)
  $alerts = [];
  foreach ($blocks as $b) {
    foreach (($b['items'] ?? []) as $it) $alerts[] = $it;
  }

} elseif ($module === 'lotes') {

  // Mantém o placeholder simples (sem blocks)
  $alerts = [
    [
      'id' => 'lot-001',
      'type' => 'info',
      'title' => 'Lotes',
      'message' => 'Existe 1 lote aguardando vinculação de processo (placeholder).',
      'href' => '/sistema-visa/app/templates/lotes.php',
      'ts' => nowIso(),
    ],
  ];

} else {
  $alerts = [];
}

$out = [
  'ok' => true,
  'module' => $module,
  'alerts' => $alerts,
];

if (is_array($blocks)) {
  $out['blocks'] = $blocks;
}

echo json_encode($out);