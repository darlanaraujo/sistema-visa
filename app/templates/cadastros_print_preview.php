<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

if (!isset($_SESSION['auth_user'])) {
  require_once __DIR__ . '/../core/url.php';
  header('Location: ' . app_url('/app/templates/login.php'));
  exit;
}

require_once __DIR__ . '/../../public_php/src/Support/helpers.php';
require_once __DIR__ . '/../core/url.php';
require_once __DIR__ . '/../core/company.php';

function cad_print_text(mixed $value, string $fallback = 'Não informado'): string {
  $out = trim((string)($value ?? ''));
  return $out !== '' ? $out : $fallback;
}

function cad_print_tipo_pessoa_label(string $value): string {
  return strtoupper(trim($value)) === 'PJ' ? 'Pessoa jurídica' : 'Pessoa física';
}

function cad_print_current_type(array $item): string {
  $tipos = is_array($item['tipos'] ?? null) ? $item['tipos'] : [];
  $slugs = [];
  foreach ($tipos as $tipo) {
    $slug = strtolower(trim((string)($tipo['slug'] ?? '')));
    if ($slug !== '') {
      $slugs[] = $slug;
    }
  }

  foreach (['motorista', 'transportadora', 'cliente', 'fornecedor'] as $candidate) {
    if (in_array($candidate, $slugs, true)) {
      return $candidate;
    }
  }

  return 'cliente';
}

function cad_print_type_label(string $slug): string {
  return match (strtolower(trim($slug))) {
    'cliente' => 'Cliente',
    'fornecedor' => 'Fornecedor',
    'motorista' => 'Motorista',
    'transportadora' => 'Transportadora',
    default => 'Cadastro',
  };
}

function cad_print_report_title(string $slug): string {
  return 'Ficha de ' . cad_print_type_label($slug);
}

$raw = $_POST['payload'] ?? '';
$data = null;

if (is_string($raw) && $raw !== '') {
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) {
    $data = $decoded;
  }
}

if (!$data) {
  http_response_code(400);
  echo '<p style="font-family:Inter,system-ui; padding:16px;">Payload inválido.</p>';
  exit;
}

$item = is_array($data['item'] ?? null) ? $data['item'] : null;
if (!$item) {
  http_response_code(400);
  echo '<p style="font-family:Inter,system-ui; padding:16px;">Cadastro inválido para impressão.</p>';
  exit;
}

$corp = company_get();
$contextType = strtolower(trim((string)($data['contextType'] ?? '')));
$tipo = $contextType !== '' ? $contextType : cad_print_current_type($item);
$isPj = strtoupper(trim((string)($item['tipoPessoa'] ?? ''))) === 'PJ';
$displayName = $isPj
  ? cad_print_text($item['razaoSocial'] ?? $item['nome'] ?? '', 'Cadastro')
  : cad_print_text($item['nome'] ?? $item['razaoSocial'] ?? '', 'Cadastro');
$title = 'Ficha de ' . $displayName;
$cidadeEstado = trim(
  cad_print_text($item['cidade'] ?? '', '') .
  (($item['estado'] ?? '') !== '' ? ' / ' . trim((string)$item['estado']) : '')
);
$tipos = is_array($item['tipos'] ?? null) ? $item['tipos'] : [];
$tiposAssociados = [];
$tiposAssociadosLabels = [];
foreach ($tipos as $tipoItem) {
  $nome = trim((string)($tipoItem['nome'] ?? ''));
  if ($nome !== '') {
    $tiposAssociados[] = $nome;
    $tiposAssociadosLabels[] = $nome;
  }
}

$tags = is_array($item['tags'] ?? null) ? $item['tags'] : [];
$veiculos = is_array($item['veiculos'] ?? null) ? $item['veiculos'] : [];
$motoristasVinculados = is_array($item['motoristasVinculados'] ?? null) ? $item['motoristasVinculados'] : [];
$motoristaDetalhes = is_array($item['motoristaDetalhes'] ?? null) ? $item['motoristaDetalhes'] : [];
$reportTypeTitle = cad_print_report_title($tipo);
?>
<!doctype html>
<html lang="pt-br" data-theme="light" class="theme-light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($title) ?> • Preview</title>

  <link rel="icon" type="image/png" sizes="32x32" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= h($corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/theme.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/financeiro.css')) ?>">
  <link rel="stylesheet" href="<?= h(app_url('/app/static/css/cadastros_print_preview.css')) ?>">
</head>
<body>
  <div class="cad-prevbar" role="region" aria-label="Ações do preview">
    <div class="cad-prevbar__left">
      <div class="cad-prevbar__title"><?= h($title) ?></div>
      <div class="cad-prevbar__hint">
        Para salvar: <strong>Cmd+P</strong> (Mac) / <strong>Ctrl+P</strong> (Windows) → Destino: <strong>Salvar como PDF</strong>
      </div>
    </div>

    <div class="cad-prevbar__actions">
      <button type="button" class="fin-btn fin-btn--ghost" onclick="window.close()">
        <i class="fa-solid fa-xmark"></i><span>Fechar</span>
      </button>
      <button type="button" class="fin-btn" onclick="window.print()">
        <i class="fa-solid fa-print"></i><span>Imprimir / Salvar PDF</span>
      </button>
    </div>
  </div>

  <div class="cad-print-page">
    <article class="cad-print-doc">
      <header class="cad-print-doc__head">
        <div class="cad-print-head-main">
          <div class="cad-print-brand">
            <img
              class="cad-print-brand__logo"
              src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
              alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
            >
            <div>
              <div class="cad-print-brand__title"><?= h($corp['company'] ?? 'Visa Remoções') ?></div>
              <div class="cad-print-brand__sub"><?= h($corp['report_footer_note'] ?? 'Documento gerado automaticamente pelo Sistema Visa Remoções.') ?></div>
            </div>
          </div>

          <div class="cad-print-meta">
            <div><span>Cadastro:</span> <strong><?= h($displayName) ?></strong></div>
            <div><span>Tipo da impressão:</span> <strong><?= h(cad_print_type_label($tipo)) ?></strong></div>
            <div><span>Tipos associados:</span> <strong><?= h($tiposAssociadosLabels !== [] ? implode(' • ', $tiposAssociadosLabels) : 'Sem tipos associados') ?></strong></div>
            <div><span>Pessoa:</span> <strong><?= h(cad_print_tipo_pessoa_label((string)($item['tipoPessoa'] ?? 'PF'))) ?></strong></div>
            <div><span>Status:</span> <strong><?= h(cad_print_text($item['status'] ?? '', 'Ativo')) ?></strong></div>
          </div>
        </div>

        <div class="cad-print-report-title-row">
          <div class="cad-print-report-title"><?= h($reportTypeTitle) ?></div>
        </div>
      </header>

      <section class="cad-print-section">
        <h2>Identificação</h2>
        <div class="cad-print-grid cad-print-grid--two">
          <?php if ($tipo === 'cliente' || $tipo === 'fornecedor'): ?>
            <?php if ($isPj): ?>
              <div class="cad-print-kv"><span>Razão social</span><strong><?= h(cad_print_text($item['razaoSocial'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>CNPJ</span><strong><?= h(cad_print_text($item['documento'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>Nome fantasia</span><strong><?= h(cad_print_text($item['nomeFantasia'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>Inscrição estadual</span><strong><?= h(cad_print_text($item['inscricaoEstadual'] ?? '')) ?></strong></div>
            <?php else: ?>
              <div class="cad-print-kv"><span>Nome</span><strong><?= h(cad_print_text($item['nome'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>CPF</span><strong><?= h(cad_print_text($item['documento'] ?? '')) ?></strong></div>
            <?php endif; ?>
          <?php elseif ($tipo === 'motorista'): ?>
            <?php if ($isPj): ?>
              <div class="cad-print-kv"><span>Razão social</span><strong><?= h(cad_print_text($item['razaoSocial'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>CNPJ</span><strong><?= h(cad_print_text($item['documento'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>Nome fantasia</span><strong><?= h(cad_print_text($item['nomeFantasia'] ?? '')) ?></strong></div>
              <div class="cad-print-kv"><span>Inscrição estadual</span><strong><?= h(cad_print_text($item['inscricaoEstadual'] ?? '')) ?></strong></div>
            <?php endif; ?>
            <div class="cad-print-kv"><span>Motorista principal</span><strong><?= h(cad_print_text($item['nome'] ?? '')) ?></strong></div>
            <div class="cad-print-kv"><span><?= $isPj ? 'CPF do motorista principal' : 'CPF' ?></span><strong><?= h(cad_print_text($isPj ? ($motoristaDetalhes['cpf'] ?? '') : ($item['documento'] ?? ''))) ?></strong></div>
            <div class="cad-print-kv"><span>CNH</span><strong><?= h(cad_print_text($motoristaDetalhes['cnh'] ?? '')) ?></strong></div>
          <?php else: ?>
            <div class="cad-print-kv"><span>Razão social</span><strong><?= h(cad_print_text($item['razaoSocial'] ?? '')) ?></strong></div>
            <div class="cad-print-kv"><span>CNPJ</span><strong><?= h(cad_print_text($item['documento'] ?? '')) ?></strong></div>
            <div class="cad-print-kv"><span>Nome fantasia</span><strong><?= h(cad_print_text($item['nomeFantasia'] ?? '')) ?></strong></div>
            <div class="cad-print-kv"><span>Inscrição estadual</span><strong><?= h(cad_print_text($item['inscricaoEstadual'] ?? '')) ?></strong></div>
          <?php endif; ?>
        </div>
      </section>

      <section class="cad-print-section">
        <h2>Contato</h2>
        <div class="cad-print-grid cad-print-grid--two">
          <div class="cad-print-kv"><span>Contato</span><strong><?= h(cad_print_text($item['contato'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Telefone fixo</span><strong><?= h(cad_print_text($item['telefoneFixo'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>WhatsApp</span><strong><?= h(cad_print_text($item['whatsapp'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Celular</span><strong><?= h(cad_print_text($item['celular'] ?? '')) ?></strong></div>
          <div class="cad-print-kv cad-print-kv--wide"><span>E-mail</span><strong><?= h(cad_print_text($item['email'] ?? '')) ?></strong></div>
        </div>
      </section>

      <section class="cad-print-section">
        <h2>Endereço</h2>
        <div class="cad-print-grid cad-print-grid--two">
          <div class="cad-print-kv"><span>CEP</span><strong><?= h(cad_print_text($item['cep'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Número</span><strong><?= h(cad_print_text($item['numero'] ?? '')) ?></strong></div>
          <div class="cad-print-kv cad-print-kv--wide"><span>Endereço</span><strong><?= h(cad_print_text($item['endereco'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Complemento</span><strong><?= h(cad_print_text($item['complemento'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Bairro</span><strong><?= h(cad_print_text($item['bairro'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Cidade / Estado</span><strong><?= h($cidadeEstado !== '' ? $cidadeEstado : 'Não informado') ?></strong></div>
        </div>
      </section>

      <section class="cad-print-section cad-print-section--compact">
        <h2>Classificação</h2>
        <div class="cad-print-grid cad-print-grid--three">
          <div class="cad-print-kv"><span>Tipo de pessoa</span><strong><?= h(cad_print_tipo_pessoa_label((string)($item['tipoPessoa'] ?? 'PF'))) ?></strong></div>
          <div class="cad-print-kv"><span>Status</span><strong><?= h(cad_print_text($item['status'] ?? '')) ?></strong></div>
          <div class="cad-print-kv"><span>Tipos associados</span><strong><?= h($tiposAssociados !== [] ? implode(' • ', $tiposAssociados) : 'Sem tipos associados') ?></strong></div>
        </div>
      </section>

      <?php if ($tipo === 'motorista' || $tipo === 'transportadora'): ?>
        <section class="cad-print-section">
          <h2>Estrutura operacional</h2>

          <?php if ($tipo === 'transportadora' && $motoristasVinculados !== []): ?>
            <div class="cad-print-subtitle">Motoristas vinculados</div>
            <div class="cad-print-stack">
              <?php foreach ($motoristasVinculados as $index => $motorista): ?>
                <div class="cad-print-box">
                  <div class="cad-print-box__title"><?= h($index === 0 ? 'Motorista principal vinculado' : 'Motorista adicional ' . ($index + 1)) ?></div>
                  <div class="cad-print-grid cad-print-grid--three">
                    <div class="cad-print-kv"><span>Nome</span><strong><?= h(cad_print_text($motorista['nome'] ?? '')) ?></strong></div>
                    <div class="cad-print-kv"><span>CPF</span><strong><?= h(cad_print_text($motorista['cpf'] ?? '')) ?></strong></div>
                    <div class="cad-print-kv"><span>CNH</span><strong><?= h(cad_print_text($motorista['cnh'] ?? '')) ?></strong></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php elseif ($tipo === 'motorista' && $motoristasVinculados !== []): ?>
            <div class="cad-print-subtitle">Motorista secundário</div>
            <div class="cad-print-stack">
              <?php foreach ($motoristasVinculados as $index => $motorista): ?>
                <div class="cad-print-box">
                  <div class="cad-print-box__title"><?= h($index === 0 ? 'Motorista 2' : 'Motorista adicional ' . ($index + 1)) ?></div>
                  <div class="cad-print-grid cad-print-grid--three">
                    <div class="cad-print-kv"><span>Nome</span><strong><?= h(cad_print_text($motorista['nome'] ?? '')) ?></strong></div>
                    <div class="cad-print-kv"><span>CPF</span><strong><?= h(cad_print_text($motorista['cpf'] ?? '')) ?></strong></div>
                    <div class="cad-print-kv"><span>CNH</span><strong><?= h(cad_print_text($motorista['cnh'] ?? '')) ?></strong></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if ($veiculos !== []): ?>
        <section class="cad-print-section">
          <h2>Veículos</h2>
          <div class="cad-print-tablewrap">
            <table class="cad-print-table">
              <thead>
                <tr>
                  <th>Modelo</th>
                  <th>Placa</th>
                  <th>Placa adicional</th>
                  <th>Carroceria</th>
                  <th>Metragem</th>
                  <th>Peso</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($veiculos as $veiculo): ?>
                  <tr>
                    <td><?= h(cad_print_text($veiculo['modelo'] ?? '')) ?></td>
                    <td><?= h(cad_print_text($veiculo['placa'] ?? '')) ?></td>
                    <td><?= h(cad_print_text($veiculo['placaAdicional'] ?? '')) ?></td>
                    <td><?= h(cad_print_text($veiculo['tipoCarroceria'] ?? '')) ?></td>
                    <td><?= h(cad_print_text($veiculo['metragem'] ?? '')) ?></td>
                    <td><?= h(cad_print_text($veiculo['pesoCarga'] ?? '')) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($tags !== []): ?>
        <section class="cad-print-section cad-print-section--compact">
          <h2>
            <?php if ($tipo === 'cliente'): ?>
              TAGS - Áreas de interesse
            <?php elseif ($tipo === 'fornecedor'): ?>
              TAGS - Produtos e Serviços oferecidos
            <?php else: ?>
              TAGS - Rotas atendidas
            <?php endif; ?>
          </h2>
          <div class="cad-print-tags">
            <?php foreach ($tags as $tag): ?>
              <span class="cad-print-tag"><?= h(cad_print_text($tag['nome'] ?? '', '')) ?></span>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <section class="cad-print-section cad-print-section--compact">
        <h2>Observações</h2>
        <div class="cad-print-note"><?= nl2br(h(cad_print_text($item['observacoes'] ?? '', 'Nenhuma observação registrada.'))) ?></div>
      </section>

      <footer class="cad-print-foot">
        <div><?= h($corp['report_footer_note'] ?? 'Documento gerado automaticamente pelo Sistema Visa Remoções.') ?></div>
      </footer>
    </article>
  </div>
</body>
</html>
