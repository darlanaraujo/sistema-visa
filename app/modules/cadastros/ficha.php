<?php
// app/modules/cadastros/ficha.php

require_once __DIR__ . '/../../../public_php/src/Repositories/CadastroRepository.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/ArquivoRepository.php';
require_once __DIR__ . '/../../../public_php/src/Support/Database.php';
require_once __DIR__ . '/_anexos_presenter.php';
require_once __DIR__ . '/_lotes_relacionados.php';

$repo = new CadastroRepository();
$arquivoRepo = new ArquivoRepository();

$id = (int)($_GET['id'] ?? 0);
$tipo = trim((string)($_GET['tipo'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$busca = trim((string)($_GET['busca'] ?? ''));
$modo = trim((string)($_GET['modo'] ?? ''));
$saved = trim((string)($_GET['saved'] ?? ''));
$isEmbedMode = trim((string)($_GET['embed'] ?? '')) === '1';
$isCadastroMode = $modo === 'cadastro';

$cadastro = $id > 0 ? $repo->findById($id, 1) : null;
$tiposDisponiveis = $repo->listTipos(true);
$cadRelatedPurchases = [];
$cadRelatedSales = [];
$cadRelatedFreights = [];

if (is_array($cadastro) && $id > 0) {
  $cadLotRelationships = cad_load_lot_relationships($id, 1);
  $cadRelatedPurchases = is_array($cadLotRelationships['compras'] ?? null) ? $cadLotRelationships['compras'] : [];
  $cadRelatedSales = is_array($cadLotRelationships['vendas'] ?? null) ? $cadLotRelationships['vendas'] : [];
  $cadRelatedFreights = is_array($cadLotRelationships['fretes'] ?? null) ? $cadLotRelationships['fretes'] : [];
}

$tipoMap = [
  'clientes' => ['title' => 'Clientes', 'slug' => 'cliente'],
  'fornecedores' => ['title' => 'Fornecedores', 'slug' => 'fornecedor'],
  'motoristas' => ['title' => 'Motoristas', 'slug' => 'motorista'],
  'transportadoras' => ['title' => 'Transportadoras', 'slug' => 'transportadora'],
  'cliente' => ['title' => 'Clientes', 'slug' => 'cliente'],
  'fornecedor' => ['title' => 'Fornecedores', 'slug' => 'fornecedor'],
  'motorista' => ['title' => 'Motoristas', 'slug' => 'motorista'],
  'transportadora' => ['title' => 'Transportadoras', 'slug' => 'transportadora'],
];
$tipoRouteMap = [
  'cliente' => 'clientes',
  'fornecedor' => 'fornecedores',
  'motorista' => 'motoristas',
  'transportadora' => 'transportadoras',
];
$screenTypeMap = [
  'cliente' => [
    'title' => 'Cliente',
    'titlePlural' => 'Clientes',
    'description' => 'Cadastro base de cliente com foco em identificação, contato, endereço, classificação e tags de interesse.',
    'defaultTipoPessoa' => 'PF',
  ],
  'fornecedor' => [
    'title' => 'Fornecedor',
    'titlePlural' => 'Fornecedores',
    'description' => 'Cadastro base de fornecedor com preferência jurídica, mantendo a base comum e tags estruturadas.',
    'defaultTipoPessoa' => 'PJ',
  ],
  'motorista' => [
    'title' => 'Motorista',
    'titlePlural' => 'Motoristas',
    'description' => 'Cadastro operacional de motorista com dados específicos, vinculados e veículos, sustentado pela base comum.',
    'defaultTipoPessoa' => 'PF',
  ],
  'transportadora' => [
    'title' => 'Transportadora',
    'titlePlural' => 'Transportadoras',
    'description' => 'Cadastro operacional de transportadora com base jurídica, motoristas vinculados, veículos e rotas.',
    'defaultTipoPessoa' => 'PJ',
  ],
];
$ufs = [
  'AC' => 'Acre',
  'AL' => 'Alagoas',
  'AP' => 'Amapá',
  'AM' => 'Amazonas',
  'BA' => 'Bahia',
  'CE' => 'Ceara',
  'DF' => 'Distrito Federal',
  'ES' => 'Espírito Santo',
  'GO' => 'Goiás',
  'MA' => 'Maranhão',
  'MT' => 'Mato Grosso',
  'MS' => 'Mato Grosso do Sul',
  'MG' => 'Minas Gerais',
  'PA' => 'Pará',
  'PB' => 'Paraíba',
  'PR' => 'Paraná',
  'PE' => 'Pernambuco',
  'PI' => 'Piauí',
  'RJ' => 'Rio de Janeiro',
  'RN' => 'Rio Grande do Norte',
  'RS' => 'Rio Grande do Sul',
  'RO' => 'Rondônia',
  'RR' => 'Roraima',
  'SC' => 'Santa Catarina',
  'SP' => 'Sao Paulo',
  'SE' => 'Sergipe',
  'TO' => 'Tocantins',
];

$veiculoModelos = [
  '3/4',
  'FIORINO',
  'TOCO',
  'VLC',
  'BITRUCK',
  'TRUCK',
  'BITREM',
  'CARRETA',
  'CARRETA LS',
  'RODOTREM',
  'VANDERLEIA',
];

$veiculoCarrocerias = [
  'BAÚ',
  'FRIGORIFICO',
  'REFRIGERADO',
  'SIDER',
  'CAÇAMBA',
  'GRADE BAIXA',
  'GRANELEIRO',
  'PLATAFORMA',
  'PRANCHA',
  'CEGONHEIRO',
  'GAIOLA',
  'MUNCK',
  'TANQUE',
];

$tipoTitulo = $tipoMap[$tipo]['title'] ?? 'Listagem';
$tipoSlugContexto = $tipoMap[$tipo]['slug'] ?? '';
$backQuery = array_filter([
  'tipo' => $tipo !== '' ? $tipo : null,
  'status' => $status !== '' ? $status : null,
  'busca' => $busca !== '' ? $busca : null,
], static fn ($value) => $value !== null && $value !== '');
$backHref = app_url('/app/templates/cadastros_listagem.php' . ($backQuery !== [] ? '?' . http_build_query($backQuery) : ''));
$formActionQuery = array_filter([
  'modo' => 'cadastro',
  'id' => $id > 0 ? $id : null,
  'tipo' => $tipo !== '' ? $tipo : null,
  'embed' => $isEmbedMode ? '1' : null,
], static fn ($value) => $value !== null && $value !== '');
$formTemplatePath = $isEmbedMode ? '/app/templates/cadastros_ficha_embed.php' : '/app/templates/cadastros_ficha.php';
$formAction = app_url($formTemplatePath . ($formActionQuery !== [] ? '?' . http_build_query($formActionQuery) : ''));

function cad_ficha_field(?string $value, string $fallback = 'Não informado'): string {
  $text = trim((string)($value ?? ''));
  return $text !== '' ? $text : $fallback;
}

function cad_ficha_tipo_pessoa(?string $value): string {
  return strtoupper(trim((string)$value)) === 'PJ' ? 'Pessoa jurídica' : 'Pessoa física';
}

function cad_ficha_status(?string $value): string {
  return strtolower(trim((string)$value)) === 'inativo' ? 'Inativo' : 'Ativo';
}

function cad_form_selected_tipo_ids(array $tipos): array {
  $items = [];
  foreach ($tipos as $tipoItem) {
    $id = (int)($tipoItem['id'] ?? 0);
    if ($id <= 0) {
      continue;
    }
    $items[$id] = $id;
  }

  return array_values($items);
}

function cad_form_selected_slugs(array $selectedTipoIds, array $tiposDisponiveis): array {
  $slugs = [];
  foreach ($tiposDisponiveis as $tipoItem) {
    $id = (int)($tipoItem['id'] ?? 0);
    if (!in_array($id, $selectedTipoIds, true)) {
      continue;
    }
    $slug = trim((string)($tipoItem['slug'] ?? ''));
    if ($slug === '') {
      continue;
    }
    $slugs[$slug] = $slug;
  }

  return array_values($slugs);
}

function cad_form_next_type_slug(array $selectedSlugs, string $currentTypeSlug): string {
  foreach ($selectedSlugs as $slug) {
    if ($slug !== '' && $slug !== $currentTypeSlug) {
      return $slug;
    }
  }

  return '';
}

function cad_form_tipo_id_by_slug(array $tiposDisponiveis, string $slug): int {
  foreach ($tiposDisponiveis as $tipoItem) {
    if (trim((string)($tipoItem['slug'] ?? '')) !== $slug) {
      continue;
    }
    $id = (int)($tipoItem['id'] ?? 0);
    if ($id > 0) {
      return $id;
    }
  }

  return 0;
}

function cad_form_avatar_src(array $selectedSlugs): string {
  $avatarMap = [
    'cliente' => app_url('/app/static/img/avatar-cliente.png'),
    'fornecedor' => app_url('/app/static/img/avatar-fornecedor.png'),
    'motorista' => app_url('/app/static/img/avatar-motorista.png'),
    'transportadora' => app_url('/app/static/img/avatar-transportadora.png'),
  ];

  foreach ($selectedSlugs as $slug) {
    if (isset($avatarMap[$slug])) {
      return $avatarMap[$slug];
    }
  }

  return $avatarMap['cliente'];
}

function cad_form_error_message(Throwable $e): string {
  $message = strtolower(trim($e->getMessage()));
  if (str_contains($message, 'duplicate') || str_contains($message, 'uq_cadastros_company_documento')) {
    return 'Já existe um cadastro com este documento.';
  }

  if (
    str_contains($message, 'arquivo') ||
    str_contains($message, 'anexo') ||
    str_contains($message, 'storage') ||
    str_contains($message, 'diretório') ||
    str_contains($message, 'permiss')
  ) {
    return $e->getMessage();
  }

  return 'Não foi possível salvar o cadastro no momento.';
}

function cad_form_tag_names(array $tags): array {
  $items = [];
  foreach ($tags as $tag) {
    if (!is_array($tag)) {
      continue;
    }
    $nome = trim((string)($tag['nome'] ?? ''));
    if ($nome === '') {
      continue;
    }
    $items[$nome] = $nome;
  }

  return array_values($items);
}

function cad_form_tags_note(string $currentTypeSlug): string {
  return match ($currentTypeSlug) {
    'cliente' => 'TAGS - Áreas de interesse',
    'fornecedor' => 'TAGS - Produtos e Serviços oferecidos',
    'motorista', 'transportadora' => 'TAGS - Rotas atendidas',
    default => 'TAGS - Classificação do cadastro',
  };
}

function cad_form_section_head(string $icon, string $eyebrow, string $title, string $description = ''): string {
  ob_start();
  ?>
  <div class="cad-form-section-head">
    <div class="cad-form-section-head__icon">
      <i class="<?= h($icon) ?>" aria-hidden="true"></i>
    </div>
    <div class="cad-form-section-head__copy">
      <div class="cad-ficha-card__eyebrow"><?= h($eyebrow) ?></div>
      <h3><?= h($title) ?></h3>
      <?php if (trim($description) !== ''): ?>
        <p><?= h($description) ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php
  return (string)ob_get_clean();
}

function cad_view_section_head(string $icon, string $eyebrow, string $title, string $description = ''): string {
  ob_start();
  ?>
  <div class="cad-ficha-section-head">
    <div class="cad-ficha-section-head__icon">
      <i class="<?= h($icon) ?>" aria-hidden="true"></i>
    </div>
    <div class="cad-ficha-section-head__copy">
      <div class="cad-ficha-card__eyebrow"><?= h($eyebrow) ?></div>
      <h3><?= h($title) ?></h3>
      <?php if (trim($description) !== ''): ?>
        <p><?= h($description) ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php
  return (string)ob_get_clean();
}

function cad_form_select_options(array $options, string $selected = '', string $placeholder = 'Selecione'): string {
  $html = '<option value="">' . h($placeholder) . '</option>';
  foreach ($options as $option) {
    $value = (string)$option;
    $isSelected = $selected !== '' && $selected === $value ? ' selected' : '';
    $html .= '<option value="' . h($value) . '"' . $isSelected . '>' . h($value) . '</option>';
  }
  return $html;
}

function cad_datetime_activity(string $createdAt): string {
  $time = trim($createdAt);
  if ($time === '') {
    return 'Data não informada • --:--';
  }

  try {
    $dt = new DateTimeImmutable($time);
    $dt = $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
  } catch (Throwable $e) {
    return 'Data não informada • --:--';
  }

  return $dt->format('d/m/Y') . ' • ' . $dt->format('H:i');
}

function cad_movement_summary(array $movimentacao): string {
  $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? $movimentacao['payloadEstrutural'] : [];
  $nome = trim((string)($payload['nome'] ?? ''));
  $tipo = trim((string)($payload['tipo_principal'] ?? ''));
  $fallback = cad_ficha_field((string)($movimentacao['descricaoEvento'] ?? ''), 'Evento sem descrição');

  return match ((string)($movimentacao['tipoEvento'] ?? '')) {
    'cadastro_criado' => 'Cadastro criado' . ($nome !== '' ? ': ' . $nome : ''),
    'cadastro_atualizado' => 'Cadastro atualizado' . ($nome !== '' ? ': ' . $nome : ''),
    default => $fallback . ($tipo !== '' ? ' • ' . $tipo : ''),
  };
}

function cad_widget_activity(array $movimentacao): array {
  $meta = cad_datetime_activity((string)($movimentacao['createdAt'] ?? ''));
  $responsavel = trim((string)($movimentacao['responsavel'] ?? ''));
  if ($responsavel !== '') {
    $meta .= ' • ' . $responsavel;
  }

  return [
    'title' => cad_movement_summary($movimentacao),
    'meta' => $meta,
  ];
}

function cad_lot_date(?string $value, string $fallback = 'Nao informado'): string {
  $text = trim((string)($value ?? ''));
  if ($text === '') {
    return $fallback;
  }

  try {
    return (new DateTimeImmutable($text))->format('d/m/Y');
  } catch (Throwable $e) {
    return $fallback;
  }
}

function cad_lot_money(float $value): string {
  return 'R$ ' . number_format($value, 2, ',', '.');
}

function cad_lot_status_label(?string $status): string {
  return match (trim((string)$status)) {
    'em_estoque' => 'Em estoque',
    'finalizado' => 'Finalizado',
    'cancelado' => 'Cancelado',
    default => 'Em transito',
  };
}

function cad_lot_transport_label(?string $tipo): string {
  return match (trim((string)$tipo)) {
    'motorista_autonomo' => 'Motorista autonomo',
    'transportadora' => 'Transportadora',
    'transporte_proprio' => 'Transporte proprio',
    'retirada_cliente' => 'Retirada pelo cliente',
    default => 'Sem frete',
  };
}

function cad_lot_sale_reference(array $row, array $payload): string {
  $saleId = trim((string)($payload['sale_id'] ?? ''));
  if ($saleId !== '') {
    return $saleId;
  }

  $saleRef = trim((string)($payload['sale_ref'] ?? ''));
  if ($saleRef !== '') {
    return $saleRef;
  }

  $movementId = (int)($row['id'] ?? 0);
  return $movementId > 0 ? 'mov:' . $movementId : '';
}

function cad_related_empty(string $text): string {
  return '<div class="cad-empty cad-empty--compact"><i class="fa-solid fa-folder-open" aria-hidden="true"></i><span>' . h($text) . '</span></div>';
}

function cad_related_lot_table(array $columns, array $rows, string $emptyText): string {
  ob_start();
  if ($rows === []) {
    echo cad_related_empty($emptyText);
    return (string)ob_get_clean();
  }
  ?>
  <div class="fin-table-wrap cad-table-wrap cad-related-table-wrap">
    <table class="fin-table cad-table cad-related-table">
      <thead>
        <tr>
          <?php foreach ($columns as $column): ?>
            <th><?= h($column) ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($row as $cell): ?>
              <td><?= $cell ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php
  return (string)ob_get_clean();
}

function cad_form_defaults(?array $cadastro, string $screenTypeSlug): array {
  $tags = is_array($cadastro['tags'] ?? null) ? cad_form_tag_names($cadastro['tags']) : [];
  $motoristaDetalhes = is_array($cadastro['motoristaDetalhes'] ?? null) ? $cadastro['motoristaDetalhes'] : [];
  $motoristasVinculados = is_array($cadastro['motoristasVinculados'] ?? null) ? $cadastro['motoristasVinculados'] : [];
  $veiculos = is_array($cadastro['veiculos'] ?? null) ? $cadastro['veiculos'] : [];
  $anexos = is_array($cadastro['anexos'] ?? null) ? $cadastro['anexos'] : [];
  $defaultTipoPessoa = in_array($screenTypeSlug, ['fornecedor', 'transportadora'], true) ? 'PJ' : 'PF';

  return [
    'tipo_pessoa' => (string)($cadastro['tipoPessoa'] ?? $defaultTipoPessoa),
    'nome' => (string)($cadastro['nome'] ?? ''),
    'razao_social' => (string)($cadastro['razaoSocial'] ?? ''),
    'nome_fantasia' => (string)($cadastro['nomeFantasia'] ?? ''),
    'documento' => (string)($cadastro['documento'] ?? ''),
    'inscricao_estadual' => (string)($cadastro['inscricaoEstadual'] ?? ''),
    'contato' => (string)($cadastro['contato'] ?? ''),
    'telefone_fixo' => (string)($cadastro['telefoneFixo'] ?? ''),
    'whatsapp' => (string)($cadastro['whatsapp'] ?? ''),
    'celular' => (string)($cadastro['celular'] ?? $cadastro['telefone'] ?? ''),
    'email' => (string)($cadastro['email'] ?? ''),
    'cep' => (string)($cadastro['cep'] ?? ''),
    'endereco' => (string)($cadastro['endereco'] ?? ''),
    'numero' => (string)($cadastro['numero'] ?? ''),
    'complemento' => (string)($cadastro['complemento'] ?? ''),
    'bairro' => (string)($cadastro['bairro'] ?? ''),
    'cidade' => (string)($cadastro['cidade'] ?? ''),
    'estado' => (string)($cadastro['estado'] ?? ''),
    'observacoes' => (string)($cadastro['observacoes'] ?? ''),
    'status' => (string)($cadastro['status'] ?? 'ativo'),
    'motorista_cpf' => (string)($motoristaDetalhes['cpf'] ?? ''),
    'cnh' => (string)($motoristaDetalhes['cnh'] ?? ''),
    'tags' => $tags,
    'motoristas_vinculados' => array_values(array_map(static function (array $item): array {
      return [
        'nome' => (string)($item['nome'] ?? ''),
        'cpf' => (string)($item['cpf'] ?? ''),
        'cnh' => (string)($item['cnh'] ?? ''),
        'contato' => (string)($item['contato'] ?? ''),
        'telefone_fixo' => (string)($item['telefoneFixo'] ?? ''),
        'whatsapp' => (string)($item['whatsapp'] ?? ''),
        'celular' => (string)($item['celular'] ?? ''),
        'email' => (string)($item['email'] ?? ''),
        'principal' => !empty($item['principal']),
      ];
    }, $motoristasVinculados)),
    'veiculos' => array_values(array_map(static function (array $item): array {
      return [
        'modelo' => (string)($item['modelo'] ?? ''),
        'placa' => (string)($item['placa'] ?? ''),
        'placa_adicional' => (string)($item['placaAdicional'] ?? ''),
        'tipo_carroceria' => (string)($item['tipoCarroceria'] ?? ''),
        'metragem' => (string)($item['metragem'] ?? ''),
        'peso_carga' => (string)($item['pesoCarga'] ?? ''),
      ];
    }, $veiculos)),
    'anexos' => $anexos,
    'anexos_remover' => [],
  ];
}

function cad_form_normalize_motoristas_post(array $items): array {
  $normalized = [];
  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }
    $normalized[] = [
      'nome' => (string)($item['nome'] ?? ''),
      'cpf' => (string)($item['cpf'] ?? ''),
      'cnh' => (string)($item['cnh'] ?? ''),
      'contato' => (string)($item['contato'] ?? ''),
      'telefone_fixo' => (string)($item['telefone_fixo'] ?? ''),
      'whatsapp' => (string)($item['whatsapp'] ?? ''),
      'celular' => (string)($item['celular'] ?? ''),
      'email' => (string)($item['email'] ?? ''),
      'principal' => !empty($item['principal']),
    ];
  }

  return $normalized;
}

function cad_form_normalize_veiculos_post(array $items): array {
  $normalized = [];
  foreach ($items as $item) {
    if (!is_array($item)) {
      continue;
    }
    $normalized[] = [
      'modelo' => (string)($item['modelo'] ?? ''),
      'placa' => (string)($item['placa'] ?? ''),
      'placa_adicional' => (string)($item['placa_adicional'] ?? ''),
      'tipo_carroceria' => (string)($item['tipo_carroceria'] ?? ''),
      'metragem' => (string)($item['metragem'] ?? ''),
      'peso_carga' => (string)($item['peso_carga'] ?? ''),
    ];
  }

  return $normalized;
}

$errors = [];
$formData = cad_form_defaults(is_array($cadastro) ? $cadastro : null, $tipoSlugContexto);
$selectedTipoIds = is_array($cadastro) ? cad_form_selected_tipo_ids(is_array($cadastro['tipos'] ?? null) ? $cadastro['tipos'] : []) : [];
$cadastroMovimentacoes = $id > 0 ? $repo->getMovimentacoes($id, 1, 10) : [];

if (!$isCadastroMode && $cadastro === null && $id <= 0) {
  $errors['view'] = 'Cadastro não encontrado';
}

if ($isCadastroMode && $cadastro === null && $id > 0) {
  $errors['form'] = 'Cadastro não encontrado para edição.';
}

if ($isCadastroMode && $cadastro === null && $selectedTipoIds === [] && $tipoSlugContexto !== '') {
  foreach ($tiposDisponiveis as $tipoItem) {
    if ((string)($tipoItem['slug'] ?? '') !== $tipoSlugContexto) {
      continue;
    }
    $selectedTipoIds[] = (int)($tipoItem['id'] ?? 0);
    break;
  }
}

if ($isEmbedMode && $tipoSlugContexto !== '') {
  $lockedTipoId = cad_form_tipo_id_by_slug($tiposDisponiveis, $tipoSlugContexto);
  if ($lockedTipoId > 0) {
    $selectedTipoIds = [$lockedTipoId];
  }
}

$selectedSlugs = cad_form_selected_slugs($selectedTipoIds, $tiposDisponiveis);
$initialSelectedSlugs = $selectedSlugs;
$pendingTipoSlug = trim((string)($_GET['pending_tipo'] ?? ''));
if ($pendingTipoSlug !== '' && !in_array($pendingTipoSlug, $selectedSlugs, true)) {
  foreach ($tiposDisponiveis as $tipoItem) {
    if (trim((string)($tipoItem['slug'] ?? '')) !== $pendingTipoSlug) {
      continue;
    }
    $selectedTipoIds[] = (int)($tipoItem['id'] ?? 0);
    $selectedTipoIds = array_values(array_unique(array_map('intval', $selectedTipoIds)));
    $selectedSlugs = cad_form_selected_slugs($selectedTipoIds, $tiposDisponiveis);
    break;
  }
}
$currentTypeSlug = $tipoSlugContexto;
if ($currentTypeSlug === '') {
  foreach (['cliente', 'fornecedor', 'motorista', 'transportadora'] as $candidate) {
    if (in_array($candidate, $selectedSlugs, true)) {
      $currentTypeSlug = $candidate;
      break;
    }
  }
}
if ($currentTypeSlug === '') {
  $currentTypeSlug = 'cliente';
}
$currentTypeRoute = $tipoRouteMap[$currentTypeSlug] ?? 'clientes';
$currentTypeMeta = $screenTypeMap[$currentTypeSlug] ?? $screenTypeMap['cliente'];
$returnTipoRoute = $tipo !== '' ? $tipo : $currentTypeRoute;
$conversionOriginSlug = trim((string)($_GET['origem_tipo'] ?? ''));
$conversionOriginMeta = $screenTypeMap[$conversionOriginSlug] ?? null;
$conversionOriginTitle = is_array($conversionOriginMeta) ? (string)$conversionOriginMeta['title'] : '';
$anexosExistentes = is_array($cadastro) && $id > 0 ? cad_present_anexos($arquivoRepo->listByEntity('cadastros', $id, 1)) : [];
$formData['anexos'] = $anexosExistentes;

if ($isCadastroMode && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $postedId = (int)($_POST['id'] ?? 0);
  $postedReturnTipo = trim((string)($_POST['return_tipo'] ?? ''));
  $postedNextTipo = trim((string)($_POST['next_tipo'] ?? ''));
  $postedConversionOrigin = trim((string)($_POST['conversion_origin'] ?? ''));
  $postedPendingTipo = trim((string)($_POST['pending_tipo'] ?? ''));
  $formData = [
    'tipo_pessoa' => (string)($_POST['tipo_pessoa'] ?? 'PF'),
    'nome' => (string)($_POST['nome'] ?? ''),
    'razao_social' => (string)($_POST['razao_social'] ?? ''),
    'nome_fantasia' => (string)($_POST['nome_fantasia'] ?? ''),
    'documento' => (string)($_POST['documento'] ?? ''),
    'inscricao_estadual' => (string)($_POST['inscricao_estadual'] ?? ''),
    'contato' => (string)($_POST['contato'] ?? ''),
    'telefone_fixo' => (string)($_POST['telefone_fixo'] ?? ''),
    'whatsapp' => (string)($_POST['whatsapp'] ?? ''),
    'celular' => (string)($_POST['celular'] ?? ''),
    'email' => (string)($_POST['email'] ?? ''),
    'cep' => (string)($_POST['cep'] ?? ''),
    'endereco' => (string)($_POST['endereco'] ?? ''),
    'numero' => (string)($_POST['numero'] ?? ''),
    'complemento' => (string)($_POST['complemento'] ?? ''),
    'bairro' => (string)($_POST['bairro'] ?? ''),
    'cidade' => (string)($_POST['cidade'] ?? ''),
    'estado' => (string)($_POST['estado'] ?? ''),
    'observacoes' => (string)($_POST['observacoes'] ?? ''),
    'status' => (string)($_POST['status'] ?? 'ativo'),
    'motorista_cpf' => (string)($_POST['motorista_cpf'] ?? ''),
    'cnh' => (string)($_POST['cnh'] ?? ''),
    'tags' => array_values(array_filter(array_map('trim', (array)($_POST['tags'] ?? [])), static fn ($tag) => $tag !== '')),
    'motoristas_vinculados' => cad_form_normalize_motoristas_post((array)($_POST['motoristas_vinculados'] ?? [])),
    'veiculos' => cad_form_normalize_veiculos_post((array)($_POST['veiculos'] ?? [])),
    'anexos' => $postedId > 0 ? cad_present_anexos($arquivoRepo->listByEntity('cadastros', $postedId, 1)) : [],
    'anexos_remover' => array_values(array_filter(array_map('intval', (array)($_POST['anexos_remover'] ?? [])), static fn (int $value) => $value > 0)),
  ];

  if ($formData['anexos_remover'] !== []) {
    $formData['anexos'] = array_values(array_filter(
      $formData['anexos'],
      static fn (array $item): bool => !in_array((int)($item['relacaoId'] ?? 0), $formData['anexos_remover'], true)
    ));
  }

  $selectedTipoIds = [];
  foreach ((array)($_POST['tipo_ids'] ?? []) as $tipoId) {
    $idValue = (int)$tipoId;
    if ($idValue <= 0) {
      continue;
    }
    $selectedTipoIds[$idValue] = $idValue;
  }
  $selectedTipoIds = array_values($selectedTipoIds);
  if ($isEmbedMode) {
    $postedNextTipo = '';
    $postedConversionOrigin = '';
    $postedPendingTipo = '';
    $lockedTipoId = cad_form_tipo_id_by_slug($tiposDisponiveis, $currentTypeSlug);
    $selectedTipoIds = $lockedTipoId > 0 ? [$lockedTipoId] : [];
  }
  $selectedSlugs = cad_form_selected_slugs($selectedTipoIds, $tiposDisponiveis);
  $nextTypeSlug = cad_form_next_type_slug($selectedSlugs, $currentTypeSlug);
  $addedSlugs = array_values(array_diff($selectedSlugs, $initialSelectedSlugs));
  $removedSlugs = array_values(array_diff($initialSelectedSlugs, $selectedSlugs));
  $persistTipoIds = $selectedTipoIds;
  $pendingConversionSlug = $postedPendingTipo !== '' ? $postedPendingTipo : $postedNextTipo;
  $isPendingConversionFlow = $postedConversionOrigin !== '' && $postedConversionOrigin !== $currentTypeSlug;
  $isCurrentTypeSelected = in_array($currentTypeSlug, $selectedSlugs, true);
  $isRemovingCurrentType = in_array($currentTypeSlug, $removedSlugs, true)
    || ($isPendingConversionFlow && !$isCurrentTypeSelected);

  $isMotorista = $currentTypeSlug === 'motorista';
  $isTransportadora = $currentTypeSlug === 'transportadora';

  if ($isTransportadora) {
    $formData['tipo_pessoa'] = 'PJ';
  }

  if (strtoupper($formData['tipo_pessoa']) === 'PJ') {
    if (trim($formData['razao_social']) === '') {
        $errors['razao_social'] = 'Informe a razão social.';
    }
  } elseif (trim($formData['nome']) === '') {
    $errors['nome'] = 'Informe o nome.';
  }

  if (
    trim($formData['celular']) === '' &&
    trim($formData['whatsapp']) === '' &&
    trim($formData['telefone_fixo']) === ''
  ) {
    $errors['celular'] = 'Informe ao menos um telefone para contato.';
  }

  if ($selectedTipoIds === []) {
    $errors['tipo_ids'] = 'Selecione ao menos um tipo de cadastro.';
  }

  if (count($addedSlugs) > 1 || count($removedSlugs) > 1 || ($addedSlugs !== [] && $removedSlugs !== [])) {
    $errors['tipo_ids'] = 'Conclua uma conversão por vez. Marque ou desmarque apenas um tipo adicional antes de salvar.';
  }

  if ($isMotorista && $isTransportadora) {
    $errors['tipo_ids'] = 'Conclua um tipo estrutural por vez. Motorista e transportadora não podem ser finalizados juntos nesta etapa.';
  }

  if ($isMotorista && !$isRemovingCurrentType) {
    if (trim($formData['cnh']) === '') {
      $errors['cnh'] = 'Informe a CNH do motorista.';
    }
    if (strtoupper($formData['tipo_pessoa']) === 'PJ' && trim($formData['motorista_cpf']) === '') {
      $errors['motorista_cpf'] = 'Informe o CPF do motorista principal.';
    }

    $hasVeiculoPrincipal = false;
    foreach ($formData['veiculos'] as $veiculoItem) {
      if (trim((string)($veiculoItem['modelo'] ?? '')) !== '' && trim((string)($veiculoItem['placa'] ?? '')) !== '') {
        $hasVeiculoPrincipal = true;
        break;
      }
    }

    if (!$hasVeiculoPrincipal) {
      $errors['veiculos'] = 'Adicione ao menos um veículo principal para o motorista.';
    }
  }

  if ($errors === []) {
    try {
      $arquivoRepo->validateUploads((array)($_FILES['anexos'] ?? []));
    } catch (Throwable $e) {
      $errors['form'] = $e->getMessage();
    }
  }

  if ($errors === []) {
    $savePayload = [
      'tipo_pessoa' => $formData['tipo_pessoa'],
      'nome' => $formData['nome'],
      'razao_social' => $formData['razao_social'],
      'nome_fantasia' => $formData['nome_fantasia'],
      'documento' => $formData['documento'],
      'inscricao_estadual' => $formData['inscricao_estadual'],
      'contato' => $formData['contato'],
      'telefone_fixo' => $formData['telefone_fixo'],
      'whatsapp' => $formData['whatsapp'],
      'celular' => $formData['celular'],
      'email' => $formData['email'],
      'cep' => $formData['cep'],
      'endereco' => $formData['endereco'],
      'numero' => $formData['numero'],
      'complemento' => $formData['complemento'],
      'bairro' => $formData['bairro'],
      'cidade' => $formData['cidade'],
      'estado' => $formData['estado'],
      'observacoes' => $formData['observacoes'],
      'status' => $formData['status'],
      'tags' => $formData['tags'],
    ];

    if ($isMotorista) {
      $savePayload['motorista_detalhes'] = [
        'cpf' => $formData['motorista_cpf'],
        'cnh' => $formData['cnh'],
      ];
      $savePayload['motoristas_vinculados'] = $formData['motoristas_vinculados'];
      $savePayload['veiculos'] = $formData['veiculos'];
    } elseif ($isTransportadora) {
      $savePayload['motoristas_vinculados'] = $formData['motoristas_vinculados'];
      $savePayload['veiculos'] = $formData['veiculos'];
    }

    try {
      $pdo = Database::connection();
      $ownsTransaction = !$pdo->inTransaction();
      if ($ownsTransaction) {
        $pdo->beginTransaction();
      }

      if (!$isPendingConversionFlow && $pendingConversionSlug !== '' && $pendingConversionSlug !== $currentTypeSlug) {
        foreach ($tiposDisponiveis as $tipoItem) {
          $tipoItemSlug = trim((string)($tipoItem['slug'] ?? ''));
          $tipoItemId = (int)($tipoItem['id'] ?? 0);
          if ($tipoItemSlug !== $pendingConversionSlug || $tipoItemId <= 0) {
            continue;
          }
          $persistTipoIds = array_values(array_filter(
            $persistTipoIds,
            static fn (int $value): bool => $value !== $tipoItemId
          ));
          break;
        }
      }

      if ($postedId > 0) {
        $savedCadastro = $repo->update($postedId, $savePayload, 1);
        if ($savedCadastro === null) {
          throw new RuntimeException('Cadastro não encontrado para atualização.');
        }
        $repo->replaceTipos($postedId, $persistTipoIds, 1);
        $arquivoRepo->removeRelations('cadastros', $postedId, $formData['anexos_remover'], 1);
        $arquivoRepo->attachUploadedFiles('cadastros', $postedId, (array)($_FILES['anexos'] ?? []), 1);
        $targetId = $postedId;
        $savedFlag = 'updated';
      } else {
        $savedCadastro = $repo->create($savePayload, 1);
        $targetId = (int)($savedCadastro['id'] ?? 0);
        $repo->replaceTipos($targetId, $persistTipoIds, 1);
        $arquivoRepo->attachUploadedFiles('cadastros', $targetId, (array)($_FILES['anexos'] ?? []), 1);
        $savedFlag = 'created';
      }

      $repo->addMovimentacao($targetId, [
        'tipoEvento' => $savedFlag === 'created' ? 'cadastro_criado' : 'cadastro_atualizado',
        'descricaoEvento' => $savedFlag === 'created' ? 'Cadastro criado.' : 'Cadastro atualizado.',
        'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
        'payloadEstrutural' => [
          'nome' => (string)($savedCadastro['razaoSocial'] ?? $savedCadastro['nome'] ?? ''),
          'tipo_principal' => $currentTypeMeta['title'] ?? '',
          'status' => (string)($savedCadastro['status'] ?? ''),
        ],
      ], 1);

      if ($ownsTransaction && $pdo->inTransaction()) {
        $pdo->commit();
      }

      $listRouteTipo = $isPendingConversionFlow
        ? $currentTypeRoute
        : ($postedReturnTipo !== '' ? $postedReturnTipo : ($tipo !== '' ? $tipo : $currentTypeRoute));
      if ($isEmbedMode) {
        $redirectQuery = array_filter([
          'modo' => 'cadastro',
          'embed' => '1',
          'id' => $targetId,
          'tipo' => $listRouteTipo,
          'saved' => $savedFlag,
        ], static fn ($value) => $value !== null && $value !== '');
        header('Location: ' . app_url('/app/templates/cadastros_ficha_embed.php?' . http_build_query($redirectQuery)));
        exit;
      }
      $targetConversionSlug = '';
      if ($isPendingConversionFlow) {
        $targetConversionSlug = '';
      } elseif ($postedNextTipo !== '' && in_array($postedNextTipo, $selectedSlugs, true) && $postedNextTipo !== $currentTypeSlug) {
        $targetConversionSlug = $postedNextTipo;
      } elseif ($addedSlugs !== []) {
        $targetConversionSlug = (string)($addedSlugs[0] ?? '');
      }

      if ($isRemovingCurrentType) {
        $removedRouteTipo = $isPendingConversionFlow
          ? ($postedReturnTipo !== '' ? $postedReturnTipo : $returnTipoRoute)
          : $listRouteTipo;
        $listQuery = array_filter([
          'tipo' => $removedRouteTipo,
          'saved' => 'type_removed',
          'removed_tipo' => $currentTypeSlug,
        ], static fn ($value) => $value !== null && $value !== '');
        header('Location: ' . app_url('/app/templates/cadastros_listagem.php?' . http_build_query($listQuery)));
        exit;
      }

      if ($targetConversionSlug !== '' && $targetConversionSlug !== $currentTypeSlug) {
        $redirectQuery = [
          'id' => $targetId,
          'modo' => 'cadastro',
          'saved' => 'conversion_pending',
          'tipo' => $tipoRouteMap[$targetConversionSlug] ?? $listRouteTipo,
          'origem_tipo' => $currentTypeSlug,
          'pending_tipo' => $targetConversionSlug,
        ];
        header('Location: ' . app_url('/app/templates/cadastros_ficha.php?' . http_build_query($redirectQuery)));
        exit;
      }

      $listQuery = array_filter([
        'tipo' => $listRouteTipo,
        'saved' => $savedFlag,
      ], static fn ($value) => $value !== null && $value !== '');
      header('Location: ' . app_url('/app/templates/cadastros_listagem.php?' . http_build_query($listQuery)));
      exit;
    } catch (Throwable $e) {
      if (isset($pdo, $ownsTransaction) && $ownsTransaction && $pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $errors['form'] = cad_form_error_message($e);
    }
  }
}

$selectedSlugs = cad_form_selected_slugs($selectedTipoIds, $tiposDisponiveis);
$avatarSrc = cad_form_avatar_src([$currentTypeSlug]);
$heroNomeSource = strtoupper($formData['tipo_pessoa']) === 'PJ'
  ? (trim($formData['razao_social']) !== '' ? trim($formData['razao_social']) : trim($formData['nome']))
  : trim($formData['nome']);
$heroNome = $heroNomeSource !== '' ? $heroNomeSource : 'Novo cadastro';
$heroTipoPessoa = cad_ficha_tipo_pessoa($formData['tipo_pessoa']);
$heroStatus = cad_ficha_status($formData['status']);
$heroDocumento = trim($formData['documento']) !== '' ? trim($formData['documento']) : 'Documento não informado';
$tagsJson = json_encode($formData['tags'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$motoristasJson = json_encode($formData['motoristas_vinculados'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$veiculosJson = json_encode($formData['veiculos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$anexosJson = json_encode($formData['anexos'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$selectedSlugsJson = json_encode($initialSelectedSlugs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$inlineCadastroPayload = json_encode([
  'id' => $id,
  'tipo' => $currentTypeSlug,
  'nome' => $heroNome,
  'documento' => trim($formData['documento']) !== '' ? trim($formData['documento']) : (trim($formData['celular']) !== '' ? trim($formData['celular']) : 'Telefone não informado'),
  'celular' => trim($formData['celular']) !== '' ? trim($formData['celular']) : trim($formData['whatsapp']),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$cadastroMovimentacoesRecentes = array_values(array_slice($cadastroMovimentacoes, 0, 8));
$widgetActivities = array_values(array_map(
  static fn (array $movimentacao): array => cad_widget_activity($movimentacao),
  array_filter($cadastroMovimentacoesRecentes, static fn ($item): bool => is_array($item))
));
$widgetActivitiesTitle = 'Movimentações recentes';
?>

<div class="module-page cad-page cad-view-page<?= $isEmbedMode ? ' cad-embed-page' : '' ?>">
  <div class="<?= $isEmbedMode ? 'cad-embed-layout' : 'admin-main-layout' ?>">
    <section class="<?= $isEmbedMode ? 'cad-embed-content' : 'admin-main-content' ?>">
      <?php if (!$isEmbedMode): ?>
      <div class="module-head cad-head cad-view-head">
        <div class="cad-head__topline">
          <div class="cad-head__eyebrow"><?= h($isCadastroMode ? ($id > 0 ? 'Edição de cadastro' : 'Novo cadastro') : 'Visualização detalhada') ?></div>

          <nav class="cad-crumbs" aria-label="Navegacao do modulo Cadastros">
            <a
              class="cad-crumbs__back"
              href="<?= h($backHref) ?>"
              data-tip="Voltar"
              data-cad-toast="Retornando para a listagem"
              data-cad-toast-kind="info"
            >
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>

            <div class="cad-crumbs__trail">
              <a href="<?= h(app_url('/app/templates/cadastros.php')) ?>" data-cad-toast="Abrindo a home de Cadastros" data-cad-toast-kind="info">Cadastros</a>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              <a href="<?= h($backHref) ?>" data-cad-toast="Retornando para a listagem" data-cad-toast-kind="info"><?= h($tipoTitulo) ?></a>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              <span><?= h($isCadastroMode ? 'Cadastro' : 'Ficha') ?></span>
            </div>
          </nav>
        </div>

        <h1><?= h($isCadastroMode ? (($id > 0 ? 'Editar ' : 'Novo ') . $currentTypeMeta['title']) : ($cadastro['nome'] ?? 'Ficha do cadastro')) ?></h1>
        <p><?= h($isCadastroMode ? $currentTypeMeta['description'] : 'Visualização completa da entidade administrativa selecionada, sem edição nesta etapa.') ?></p>
      </div>
      <?php endif; ?>

      <?php if (!$isCadastroMode && (!is_array($cadastro) || !$cadastro)): ?>
        <section class="admin-block">
          <div class="admin-block-head">
            <h2 class="admin-block-title"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><span>Cadastro não encontrado</span></h2>
          </div>
          <div class="admin-block-body">
            <div class="cad-empty">
              <div class="cad-empty__icon"><i class="fa-solid fa-user-slash"></i></div>
              <h3>Registro indisponível</h3>
              <p>O cadastro solicitado não foi encontrado ou não está mais disponível na base atual.</p>
            </div>
          </div>
        </section>
      <?php elseif ($isCadastroMode): ?>
        <form
          class="cad-form-page"
          method="post"
          enctype="multipart/form-data"
          action="<?= h($formAction) ?>"
          data-cad-form
          data-cad-form-saved="<?= h($saved) ?>"
          data-cad-current-type="<?= h($currentTypeSlug) ?>"
          data-cad-current-type-title="<?= h($currentTypeMeta['title']) ?>"
          data-cad-embed="<?= $isEmbedMode ? '1' : '0' ?>"
          data-cad-inline-payload='<?= h($inlineCadastroPayload ?: '{}') ?>'
          data-cad-initial-slugs='<?= h($selectedSlugsJson ?: '[]') ?>'
          data-cad-conversion-origin-title="<?= h($conversionOriginTitle) ?>"
          data-cad-tags='<?= h($tagsJson ?: '[]') ?>'
          data-cad-motoristas='<?= h($motoristasJson ?: '[]') ?>'
          data-cad-veiculos='<?= h($veiculosJson ?: '[]') ?>'
          data-cad-anexos='<?= h($anexosJson ?: '[]') ?>'
        >
          <?php if ($id > 0): ?>
            <input type="hidden" name="id" value="<?= h((string)$id) ?>">
          <?php endif; ?>
          <input type="hidden" name="return_tipo" value="<?= h($returnTipoRoute) ?>">
          <input type="hidden" name="conversion_origin" value="<?= h($conversionOriginSlug) ?>">
          <input type="hidden" name="pending_tipo" value="<?= h($pendingTipoSlug) ?>">
          <input type="hidden" name="next_tipo" value="" data-cad-next-tipo>
          <?php if (isset($errors['form'])): ?>
            <div class="cad-form-alert cad-form-alert--error"><?= h($errors['form']) ?></div>
          <?php endif; ?>

          <div class="cad-sheet__hero-row">
            <aside class="cad-sheet__avatar-col">
              <div class="cad-sheet__avatar cad-form-avatar" id="cadFormAvatar" aria-hidden="true">
                <img src="<?= h($avatarSrc) ?>" alt="Avatar do cadastro">
              </div>
            </aside>

            <div class="cad-modal__hero cad-sheet__hero-card">
              <div class="cad-modal__eyebrow">Cadastro central</div>
              <h3 id="cadFormHeroTitle"><?= h($heroNome) ?></h3>
              <p id="cadFormHeroSubtitle"><?= h($heroTipoPessoa) ?></p>

              <div class="cad-ficha-pillrow">
                <span class="cad-status cad-status--<?= h(strtolower(trim($formData['status'])) === 'inativo' ? 'inativo' : 'ativo') ?>" id="cadFormHeroStatus"><?= h($heroStatus) ?></span>
                <span class="cad-ficha-pill" id="cadFormHeroDocumento"><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i><?= h($heroDocumento) ?></span>
              </div>
            </div>
          </div>

          <div class="cad-ficha-grid cad-sheet__sections">
            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
              <?= cad_form_section_head('fa-solid fa-compass-drafting', 'Contexto do cadastro', 'Base estrutural do registro', 'Defina o tipo, a natureza da pessoa e o status do cadastro para orientar corretamente o restante do formulário.') ?>
              <div class="cad-form-grid cad-form-grid--two">
                <div class="cad-form-stack">
                  <span class="cad-form-stack__label">Tipo do cadastro</span>
                  <?php if ($isEmbedMode): ?>
                    <?php $embedTipoId = cad_form_tipo_id_by_slug($tiposDisponiveis, $currentTypeSlug); ?>
                    <div class="cad-embed-type">
                      <span class="cad-embed-type__pill"><?= h($currentTypeMeta['title']) ?></span>
                      <small>Cadastro inline com tipo travado para manter o vínculo correto no módulo de origem.</small>
                    </div>
                    <?php if ($embedTipoId > 0): ?>
                      <input type="hidden" name="tipo_ids[]" value="<?= h((string)$embedTipoId) ?>">
                    <?php endif; ?>
                  <?php else: ?>
                    <div class="cad-form-checkgrid">
                      <?php foreach ($tiposDisponiveis as $tipoItem): ?>
                        <?php $tipoId = (int)($tipoItem['id'] ?? 0); ?>
                        <label class="cad-form-check">
                          <input type="checkbox" name="tipo_ids[]" value="<?= h((string)$tipoId) ?>" <?= in_array($tipoId, $selectedTipoIds, true) ? 'checked' : '' ?> data-cad-type-input data-cad-type-slug="<?= h((string)($tipoItem['slug'] ?? '')) ?>">
                          <span><?= h((string)($tipoItem['nome'] ?? 'Tipo')) ?></span>
                        </label>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                  <?php if (isset($errors['tipo_ids'])): ?><small class="cad-form-error"><?= h($errors['tipo_ids']) ?></small><?php endif; ?>
                </div>

                <div class="cad-form-stack">
                  <div class="cad-form-grid cad-form-grid--two">
                    <label class="cad-form-field">
                      <span>Tipo de pessoa</span>
                      <select name="tipo_pessoa" data-cad-live-tipo-pessoa>
                        <option value="PF" <?= strtoupper($formData['tipo_pessoa']) === 'PF' ? 'selected' : '' ?>>Pessoa física</option>
                        <option value="PJ" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'selected' : '' ?>>Pessoa jurídica</option>
                      </select>
                    </label>

                    <label class="cad-form-field">
                      <span>Status</span>
                      <select name="status" data-cad-live-status>
                        <option value="ativo" <?= strtolower($formData['status']) !== 'inativo' ? 'selected' : '' ?>>Ativo</option>
                        <option value="inativo" <?= strtolower($formData['status']) === 'inativo' ? 'selected' : '' ?>>Inativo</option>
                      </select>
                    </label>
                  </div>

                  <div class="cad-form-alert cad-form-alert--info" data-cad-conversion-alert hidden></div>
                  <div class="cad-form-note" data-cad-conversion-note>Conclua primeiro o tipo atual. Se outro tipo estrutural for associado, os dados complementares deverão ser finalizados na tela correspondente.</div>
                </div>
              </div>
            </section>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
              <?= cad_form_section_head('fa-solid fa-id-card-clip', 'Identificação', 'Dados principais do cadastro', 'Preencha o núcleo de identificação da pessoa ou empresa. Quando aplicável, o sistema pode complementar automaticamente os dados a partir do documento.') ?>
              <?php if (in_array($currentTypeSlug, ['cliente', 'fornecedor'], true)): ?>
                <div class="cad-form-grid cad-form-grid--three" data-cad-ident-section="pf" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'hidden' : '' ?>>
                  <label class="cad-form-field cad-field-span-2">
                    <span>Nome</span>
                    <input type="text" name="nome" value="<?= h($formData['nome']) ?>" data-cad-live-name <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                    <?php if (isset($errors['nome'])): ?><small class="cad-form-error"><?= h($errors['nome']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-field-span-1" data-cad-document-field="pf">
                    <span>CPF</span>
                    <input type="text" name="documento" value="<?= h($formData['documento']) ?>" inputmode="numeric" autocomplete="off" data-cad-live-documento data-cad-mask="documento" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                    <?php if (isset($errors['documento'])): ?><small class="cad-form-error"><?= h($errors['documento']) ?></small><?php endif; ?>
                  </label>
                </div>

                <div class="cad-form-grid cad-form-grid--three" data-cad-ident-section="pj" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'hidden' ?>>
                  <label class="cad-form-field cad-field-span-2">
                    <span>Razão social</span>
                    <input type="text" name="razao_social" value="<?= h($formData['razao_social']) ?>" data-cad-live-razao <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                    <?php if (isset($errors['razao_social'])): ?><small class="cad-form-error"><?= h($errors['razao_social']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-form-field--special cad-field-span-1 <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'is-active' : '' ?>" data-cad-document-field="pj" data-cad-lookup-field="cnpj">
                    <span>CNPJ</span>
                    <input type="text" name="documento" value="<?= h($formData['documento']) ?>" inputmode="numeric" autocomplete="off" placeholder="Use para auto preenchimento" data-cad-live-documento data-cad-mask="documento" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                    <small class="cad-form-lookup-feedback" data-cad-lookup-feedback="cnpj" hidden></small>
                    <?php if (isset($errors['documento'])): ?><small class="cad-form-error"><?= h($errors['documento']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-field-span-1">
                    <span>Nome fantasia</span>
                    <input type="text" name="nome_fantasia" value="<?= h($formData['nome_fantasia']) ?>" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                  </label>

                  <label class="cad-form-field cad-field-span-1">
                    <span>Inscrição estadual</span>
                    <input type="text" name="inscricao_estadual" value="<?= h($formData['inscricao_estadual']) ?>" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                  </label>
                </div>
              <?php elseif (in_array($currentTypeSlug, ['motorista', 'transportadora'], true)): ?>
                <div class="cad-form-note">
                  <?= h($currentTypeSlug === 'transportadora'
                    ? 'Transportadora inicia com os dados jurídicos da empresa e mantém motoristas vinculados e veículos como núcleo operacional do cadastro.'
                    : 'Se o motorista for pessoa jurídica, os dados da empresa aparecem nesta seção e os dados do motorista principal continuam obrigatórios na seção seguinte.') ?>
                </div>

                <div class="cad-form-grid cad-form-grid--three" data-cad-motorista-pj-section <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'hidden' ?>>
                  <label class="cad-form-field cad-field-span-2">
                    <span>Razão social</span>
                    <input type="text" name="razao_social" value="<?= h($formData['razao_social']) ?>" data-cad-live-razao <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                    <?php if (isset($errors['razao_social'])): ?><small class="cad-form-error"><?= h($errors['razao_social']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-form-field--special cad-field-span-1 <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'is-active' : '' ?>" data-cad-document-field="pj" data-cad-lookup-field="cnpj">
                    <span>CNPJ</span>
                    <input type="text" name="documento" value="<?= h($formData['documento']) ?>" inputmode="numeric" autocomplete="off" placeholder="Use para auto preenchimento" data-cad-live-documento data-cad-mask="documento" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                    <small class="cad-form-lookup-feedback" data-cad-lookup-feedback="cnpj" hidden></small>
                    <?php if (isset($errors['documento'])): ?><small class="cad-form-error"><?= h($errors['documento']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-field-span-1">
                    <span>Nome fantasia</span>
                    <input type="text" name="nome_fantasia" value="<?= h($formData['nome_fantasia']) ?>" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                  </label>

                  <label class="cad-form-field cad-field-span-1">
                    <span>Inscrição estadual</span>
                    <input type="text" name="inscricao_estadual" value="<?= h($formData['inscricao_estadual']) ?>" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'disabled' ?>>
                  </label>
                </div>
              <?php else: ?>
                <div class="cad-form-grid cad-form-grid--three">
                  <label class="cad-form-field cad-field-span-2" data-cad-pf-exclusive>
                    <span>Nome</span>
                    <input type="text" name="nome" value="<?= h($formData['nome']) ?>" data-cad-live-name>
                    <?php if (isset($errors['nome'])): ?><small class="cad-form-error"><?= h($errors['nome']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-form-field--special cad-field-span-1 <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'is-active' : '' ?>" data-cad-document-field data-cad-lookup-field="cnpj">
                    <span><?= h(strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'CNPJ' : 'CPF') ?></span>
                    <input type="text" name="documento" value="<?= h($formData['documento']) ?>" inputmode="numeric" autocomplete="off" placeholder="<?= h(strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'Use para auto preenchimento' : '') ?>" data-cad-live-documento data-cad-mask="documento">
                    <small class="cad-form-lookup-feedback" data-cad-lookup-feedback="cnpj" hidden></small>
                    <?php if (isset($errors['documento'])): ?><small class="cad-form-error"><?= h($errors['documento']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-form-field--pj cad-field-span-2" data-cad-pj-field <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'hidden' ?>>
                    <span>Razão social</span>
                    <input type="text" name="razao_social" value="<?= h($formData['razao_social']) ?>" data-cad-live-razao>
                    <?php if (isset($errors['razao_social'])): ?><small class="cad-form-error"><?= h($errors['razao_social']) ?></small><?php endif; ?>
                  </label>

                  <label class="cad-form-field cad-form-field--pj cad-field-span-1" data-cad-pj-field <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'hidden' ?>>
                    <span>Nome fantasia</span>
                    <input type="text" name="nome_fantasia" value="<?= h($formData['nome_fantasia']) ?>">
                  </label>

                  <label class="cad-form-field cad-form-field--pj cad-field-span-1" data-cad-pj-field <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? '' : 'hidden' ?>>
                    <span>Inscrição estadual</span>
                    <input type="text" name="inscricao_estadual" value="<?= h($formData['inscricao_estadual']) ?>">
                  </label>
                </div>
              <?php endif; ?>
            </section>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" <?= in_array($currentTypeSlug, ['motorista', 'transportadora'], true) && strtoupper($formData['tipo_pessoa']) !== 'PJ' ? 'hidden data-cad-motorista-company-contact' : (in_array($currentTypeSlug, ['motorista', 'transportadora'], true) ? 'data-cad-motorista-company-contact' : '') ?>>
              <?= cad_form_section_head('fa-solid fa-address-book', 'Contato', 'Canais de comunicação', 'Centralize aqui os meios de contato mais usados pela operação, mantendo telefone, WhatsApp e e-mail organizados.') ?>
              <div class="cad-form-grid cad-form-grid--three">
                <label class="cad-form-field">
                  <span>Contato</span>
                  <input type="text" name="contato" value="<?= h($formData['contato']) ?>">
                </label>
                <label class="cad-form-field">
                  <span>Telefone fixo</span>
                  <input type="text" name="telefone_fixo" value="<?= h($formData['telefone_fixo']) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                </label>
                <label class="cad-form-field">
                  <span>WhatsApp</span>
                  <input type="text" name="whatsapp" value="<?= h($formData['whatsapp']) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                  <?php if (isset($errors['celular'])): ?><small class="cad-form-error"><?= h($errors['celular']) ?></small><?php endif; ?>
                </label>
                <label class="cad-form-field">
                  <span>Celular</span>
                  <input type="text" name="celular" value="<?= h($formData['celular']) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                  <?php if (isset($errors['celular'])): ?><small class="cad-form-error"><?= h($errors['celular']) ?></small><?php endif; ?>
                </label>
                <label class="cad-form-field">
                  <span>E-mail</span>
                  <input type="email" name="email" value="<?= h($formData['email']) ?>">
                </label>
              </div>
            </section>

            <?php if (in_array($currentTypeSlug, ['motorista', 'transportadora'], true)): ?>
              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <?= cad_form_section_head('fa-solid fa-id-badge', $currentTypeSlug === 'motorista' ? 'Motorista principal' : 'Motorista principal vinculado', $currentTypeSlug === 'motorista' ? 'Responsável principal da operação' : 'Responsável principal da transportadora', $currentTypeSlug === 'motorista'
                  ? 'Mesmo em cadastro simples, o motorista principal precisa estar bem identificado para manter o fluxo operacional consistente.'
                  : 'Toda transportadora precisa ter um motorista principal claramente definido para sustentar o vínculo operacional.') ?>
                <div class="cad-form-note">
                  <?= h($currentTypeSlug === 'motorista'
                    ? 'Os dados do motorista principal são obrigatórios em qualquer cenário. Em pessoa física, esta seção já concentra também o contato principal.'
                    : 'Toda transportadora precisa ter ao menos um motorista vinculado principal para operar corretamente.') ?>
                </div>
                <div class="cad-form-grid cad-form-grid--three">
                  <label class="cad-form-field cad-field-span-2">
                    <span>Nome do motorista principal</span>
                    <?php if ($currentTypeSlug === 'motorista'): ?>
                      <input type="text" name="nome" value="<?= h($formData['nome']) ?>" data-cad-live-name>
                      <?php if (isset($errors['nome'])): ?><small class="cad-form-error"><?= h($errors['nome']) ?></small><?php endif; ?>
                    <?php else: ?>
                      <input type="text" name="motoristas_vinculados[0][nome]" value="<?= h((string)($formData['motoristas_vinculados'][0]['nome'] ?? '')) ?>">
                    <?php endif; ?>
                  </label>

                  <?php if ($currentTypeSlug === 'motorista' && strtoupper($formData['tipo_pessoa']) === 'PJ'): ?>
                    <label class="cad-form-field cad-field-span-1">
                      <span>CPF do motorista principal</span>
                      <input type="text" name="motorista_cpf" value="<?= h($formData['motorista_cpf']) ?>" inputmode="numeric" autocomplete="off" data-cad-mask="documento">
                      <?php if (isset($errors['motorista_cpf'])): ?><small class="cad-form-error"><?= h($errors['motorista_cpf']) ?></small><?php endif; ?>
                    </label>
                  <?php elseif ($currentTypeSlug === 'motorista'): ?>
                    <label class="cad-form-field cad-field-span-1" data-cad-document-field="pf">
                      <span>CPF</span>
                      <input type="text" name="documento" value="<?= h($formData['documento']) ?>" inputmode="numeric" autocomplete="off" data-cad-live-documento data-cad-mask="documento">
                      <?php if (isset($errors['documento'])): ?><small class="cad-form-error"><?= h($errors['documento']) ?></small><?php endif; ?>
                    </label>
                  <?php else: ?>
                    <label class="cad-form-field cad-field-span-1">
                      <span>CPF</span>
                      <input type="text" name="motoristas_vinculados[0][cpf]" value="<?= h((string)($formData['motoristas_vinculados'][0]['cpf'] ?? '')) ?>" inputmode="numeric" autocomplete="off" data-cad-mask="documento">
                    </label>
                  <?php endif; ?>

                  <label class="cad-form-field cad-field-span-1">
                    <span>CNH</span>
                    <?php if ($currentTypeSlug === 'motorista'): ?>
                      <input type="text" name="cnh" value="<?= h($formData['cnh']) ?>" inputmode="numeric" autocomplete="off" data-cad-mask="cnh">
                      <?php if (isset($errors['cnh'])): ?><small class="cad-form-error"><?= h($errors['cnh']) ?></small><?php endif; ?>
                    <?php else: ?>
                      <input type="text" name="motoristas_vinculados[0][cnh]" value="<?= h((string)($formData['motoristas_vinculados'][0]['cnh'] ?? '')) ?>" inputmode="numeric" autocomplete="off" data-cad-mask="cnh">
                    <?php endif; ?>
                  </label>

                  <?php if ($currentTypeSlug === 'motorista'): ?>
                    <label class="cad-form-field cad-field-span-1" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'hidden' : '' ?> data-cad-motorista-pf-contact>
                      <span>Contato</span>
                      <input type="text" name="contato" value="<?= h($formData['contato']) ?>" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                    </label>
                    <label class="cad-form-field cad-field-span-1" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'hidden' : '' ?> data-cad-motorista-pf-contact>
                      <span>Telefone fixo</span>
                      <input type="text" name="telefone_fixo" value="<?= h($formData['telefone_fixo']) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                    </label>
                    <label class="cad-form-field cad-field-span-1" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'hidden' : '' ?> data-cad-motorista-pf-contact>
                      <span>WhatsApp</span>
                      <input type="text" name="whatsapp" value="<?= h($formData['whatsapp']) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                      <?php if (isset($errors['celular'])): ?><small class="cad-form-error"><?= h($errors['celular']) ?></small><?php endif; ?>
                    </label>
                    <label class="cad-form-field cad-field-span-1" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'hidden' : '' ?> data-cad-motorista-pf-contact>
                      <span>Celular</span>
                      <input type="text" name="celular" value="<?= h($formData['celular']) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                      <?php if (isset($errors['celular'])): ?><small class="cad-form-error"><?= h($errors['celular']) ?></small><?php endif; ?>
                    </label>
                    <label class="cad-form-field cad-field-span-1" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'hidden' : '' ?> data-cad-motorista-pf-contact>
                      <span>E-mail</span>
                      <input type="email" name="email" value="<?= h($formData['email']) ?>" <?= strtoupper($formData['tipo_pessoa']) === 'PJ' ? 'disabled' : '' ?>>
                    </label>
                  <?php else: ?>
                    <label class="cad-form-field cad-field-span-1">
                      <span>Contato</span>
                      <input type="text" name="motoristas_vinculados[0][contato]" value="<?= h((string)($formData['motoristas_vinculados'][0]['contato'] ?? '')) ?>">
                    </label>
                    <label class="cad-form-field cad-field-span-1">
                      <span>Telefone fixo</span>
                      <input type="text" name="motoristas_vinculados[0][telefone_fixo]" value="<?= h((string)($formData['motoristas_vinculados'][0]['telefone_fixo'] ?? '')) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                    </label>
                    <label class="cad-form-field cad-field-span-1">
                      <span>WhatsApp</span>
                      <input type="text" name="motoristas_vinculados[0][whatsapp]" value="<?= h((string)($formData['motoristas_vinculados'][0]['whatsapp'] ?? '')) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                    </label>
                    <label class="cad-form-field cad-field-span-1">
                      <span>Celular</span>
                      <input type="text" name="motoristas_vinculados[0][celular]" value="<?= h((string)($formData['motoristas_vinculados'][0]['celular'] ?? '')) ?>" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                    </label>
                    <label class="cad-form-field cad-field-span-1">
                      <span>E-mail</span>
                      <input type="email" name="motoristas_vinculados[0][email]" value="<?= h((string)($formData['motoristas_vinculados'][0]['email'] ?? '')) ?>">
                    </label>
                  <?php endif; ?>
                </div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <?= cad_form_section_head('fa-solid fa-truck-front', $currentTypeSlug === 'motorista' ? 'Veículo principal' : 'Veículo principal vinculado', 'Base veicular da operação', 'Registre o veículo principal com os dados mínimos para leitura rápida, logística e identificação do ativo.') ?>
                <div class="cad-form-note">
                  <?= h($currentTypeSlug === 'motorista'
                    ? 'Todo cadastro de motorista precisa ter ao menos um veículo principal.'
                    : 'Toda transportadora precisa ter ao menos um veículo principal vinculado.') ?>
                </div>
                <div class="cad-form-grid cad-form-grid--three">
                  <label class="cad-form-field">
                    <span>Modelo</span>
                    <select name="veiculos[0][modelo]">
                      <?= cad_form_select_options($veiculoModelos, (string)($formData['veiculos'][0]['modelo'] ?? ''), 'Selecione o modelo') ?>
                    </select>
                  </label>
                  <label class="cad-form-field">
                    <span>Placa</span>
                    <input type="text" name="veiculos[0][placa]" value="<?= h((string)($formData['veiculos'][0]['placa'] ?? '')) ?>" maxlength="8" autocomplete="off" data-cad-mask="placa">
                  </label>
                  <label class="cad-form-field">
                    <span>Placa adicional</span>
                    <input type="text" name="veiculos[0][placa_adicional]" value="<?= h((string)($formData['veiculos'][0]['placa_adicional'] ?? '')) ?>" maxlength="8" autocomplete="off" data-cad-mask="placa">
                  </label>
                  <label class="cad-form-field">
                    <span>Tipo de carroceria</span>
                    <select name="veiculos[0][tipo_carroceria]">
                      <?= cad_form_select_options($veiculoCarrocerias, (string)($formData['veiculos'][0]['tipo_carroceria'] ?? ''), 'Selecione a carroceria') ?>
                    </select>
                  </label>
                  <label class="cad-form-field">
                    <span>Metragem</span>
                    <input type="text" name="veiculos[0][metragem]" value="<?= h((string)($formData['veiculos'][0]['metragem'] ?? '')) ?>" inputmode="decimal" autocomplete="off" data-cad-mask="decimal">
                  </label>
                  <label class="cad-form-field">
                    <span>Peso de carga</span>
                    <input type="text" name="veiculos[0][peso_carga]" value="<?= h((string)($formData['veiculos'][0]['peso_carga'] ?? '')) ?>" inputmode="decimal" autocomplete="off" data-cad-mask="decimal">
                  </label>
                </div>
                <?php if (isset($errors['veiculos'])): ?><small class="cad-form-error"><?= h($errors['veiculos']) ?></small><?php endif; ?>
              </section>
            <?php endif; ?>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
              <?= cad_form_section_head('fa-solid fa-location-dot', 'Endereço', 'Localização e referência', 'Use CEP para autopreenchimento quando disponível e mantenha o endereço completo pronto para operação, documentação e contato.') ?>
              <div class="cad-form-grid cad-form-grid--address">
                <label class="cad-form-field cad-form-field--special cad-field-md is-active" data-cad-lookup-field="cep">
                  <span>CEP</span>
                  <input type="text" name="cep" value="<?= h($formData['cep']) ?>" inputmode="numeric" autocomplete="off" placeholder="Use para auto preenchimento" data-cad-mask="cep">
                  <small class="cad-form-lookup-feedback" data-cad-lookup-feedback="cep" hidden></small>
                </label>
                <label class="cad-form-field cad-field-xl">
                  <span>Endereço</span>
                  <input type="text" name="endereco" value="<?= h($formData['endereco']) ?>">
                </label>
                <label class="cad-form-field cad-field-sm">
                  <span>Numero</span>
                  <input type="text" name="numero" value="<?= h($formData['numero']) ?>" autocomplete="off">
                </label>
                <label class="cad-form-field cad-field-lg">
                  <span>Complemento</span>
                  <input type="text" name="complemento" value="<?= h($formData['complemento']) ?>">
                </label>
                <label class="cad-form-field cad-field-lg">
                  <span>Bairro</span>
                  <input type="text" name="bairro" value="<?= h($formData['bairro']) ?>">
                </label>
                <label class="cad-form-field cad-field-lg">
                  <span>Cidade</span>
                  <input type="text" name="cidade" value="<?= h($formData['cidade']) ?>">
                </label>
                <label class="cad-form-field cad-field-sm">
                  <span>Estado</span>
                  <select name="estado">
                    <option value="">Selecione</option>
                    <?php foreach ($ufs as $uf => $ufLabel): ?>
                      <option value="<?= h($uf) ?>" <?= strtoupper($formData['estado']) === $uf ? 'selected' : '' ?>><?= h($uf) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </div>
            </section>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" data-cad-shared-structural <?= in_array($currentTypeSlug, ['motorista', 'transportadora'], true) ? '' : 'hidden' ?>>
              <?= cad_form_section_head('fa-solid fa-users-gear', $currentTypeSlug === 'motorista' ? 'Motorista secundário' : 'Motorista 2 e adicionais', 'Equipe complementar vinculada', 'Amplie a base operacional com motoristas adicionais mantendo o mesmo padrão de leitura e organização do cadastro principal.') ?>
              <div class="cad-repeater" data-cad-repeater="motoristas">
                <div class="cad-repeater__list" data-cad-motoristas-list></div>
                <div class="cad-repeater__actions">
                  <button type="button" class="fin-btn fin-btn--ghost" data-cad-add-motorista data-cad-motorista-mode="<?= h($currentTypeSlug) ?>">
                    <i class="fa-solid fa-plus"></i><span><?= h($currentTypeSlug === 'motorista' ? 'Adicionar motorista secundário' : 'Adicionar motorista 2') ?></span>
                  </button>
                </div>
                <?php if (isset($errors['motoristas_vinculados'])): ?><small class="cad-form-error"><?= h($errors['motoristas_vinculados']) ?></small><?php endif; ?>
              </div>
            </section>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" data-cad-shared-structural <?= in_array($currentTypeSlug, ['motorista', 'transportadora'], true) ? '' : 'hidden' ?>>
              <?= cad_form_section_head('fa-solid fa-trailer', 'Veículo 2 e adicionais', 'Frota complementar', 'Use este bloco para ampliar a frota vinculada sem perder a leitura clara dos veículos operacionais do cadastro.') ?>
              <div class="cad-repeater" data-cad-repeater="veiculos">
                <div class="cad-repeater__list" data-cad-veiculos-list></div>
                <div class="cad-repeater__actions">
                  <button type="button" class="fin-btn fin-btn--ghost" data-cad-add-veiculo>
                    <i class="fa-solid fa-plus"></i><span>Adicionar veículo 2</span>
                  </button>
                </div>
                <?php if ($currentTypeSlug !== 'motorista' && isset($errors['veiculos'])): ?><small class="cad-form-error"><?= h($errors['veiculos']) ?></small><?php endif; ?>
              </div>
            </section>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
              <?= cad_form_section_head('fa-solid fa-paperclip', 'Anexos', 'Documentação do cadastro', 'Associe imagens, PDFs e documentos ao registro para manter o histórico documental centralizado e pronto para consulta.') ?>
              <div
                class="sv-attachments"
                data-anexos-root
                data-anexos-existing='<?= h($anexosJson ?: '[]') ?>'
              >
                <div class="sv-attachments__drop">
                  <div class="sv-attachments__drophead">
                    <div>
                      <div class="sv-attachments__title">Arquivos do cadastro</div>
                      <p class="sv-attachments__hint">Envie imagens, PDF e documentos. Este componente é reutilizável para outros módulos.</p>
                    </div>
                    <div class="sv-attachments__actions">
                      <input
                        class="sv-attachments__input"
                        type="file"
                        id="cadAnexosInput"
                        name="anexos[]"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                        data-anexos-input
                      >
                      <button type="button" class="fin-btn fin-btn--ghost" data-anexos-pick>
                        <i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>Adicionar anexos</span>
                      </button>
                    </div>
                  </div>

                  <div class="sv-attachments__empty" data-anexos-empty <?= $formData['anexos'] !== [] ? 'hidden' : '' ?>>
                    Nenhum anexo foi associado a este cadastro até o momento.
                  </div>
                  <div class="sv-attachments__grid" data-anexos-grid></div>
                  <div data-anexos-remove-hidden></div>
                </div>
              </div>
            </section>

            <section class="cad-ficha-card cad-sheet__card <?= in_array($currentTypeSlug, ['cliente', 'fornecedor', 'motorista', 'transportadora'], true) ? 'cad-sheet__card--wide cad-sheet__section-wide' : 'cad-sheet__card--half' ?>">
              <?= cad_form_section_head('fa-solid fa-tags', 'Tags estruturadas', 'Classificação inteligente do cadastro', 'Use tags para classificar interesses, rotas ou especialidades e melhorar cruzamentos futuros no sistema.') ?>
              <div class="cad-tag-editor" data-cad-tags-editor>
                <div class="cad-tag-editor__inputrow">
                  <input type="text" data-cad-tags-input placeholder="Digite uma tag e clique em adicionar">
                  <button type="button" class="fin-btn fin-btn--ghost" data-cad-tags-add>Adicionar tag</button>
                </div>
                <div class="cad-tag-editor__chips" data-cad-tags-list></div>
                <div class="cad-form-note" data-cad-tags-note><?= h(cad_form_tags_note($currentTypeSlug)) ?></div>
                <div data-cad-tags-hidden></div>
              </div>
            </section>

            <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
              <?= cad_form_section_head('fa-solid fa-note-sticky', 'Informações adicionais', 'Observações complementares', 'Registre contexto livre, exceções e recados operacionais que não se encaixam nos campos estruturados.') ?>
              <label class="cad-form-field">
                <span>Observações</span>
                <textarea name="observacoes" rows="6"><?= h($formData['observacoes']) ?></textarea>
              </label>
            </section>

          </div>

          <template id="cadTemplateMotoristaVinculado">
            <article class="cad-repeater__item" data-cad-repeat-item="motorista">
              <div class="cad-repeater__item-head">
                <strong><?= h($currentTypeSlug === 'motorista' ? 'Motorista 2' : 'Motorista adicional') ?></strong>
                <button type="button" class="fin-action-ico" data-cad-remove-item data-tip="Remover"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div class="cad-form-grid cad-form-grid--three">
                <label class="cad-form-field">
                  <span>Nome</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][nome]" value="">
                </label>
                <label class="cad-form-field">
                  <span>CPF</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][cpf]" value="" inputmode="numeric" autocomplete="off" data-cad-mask="documento">
                </label>
                <label class="cad-form-field">
                  <span>CNH</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][cnh]" value="">
                </label>
                <label class="cad-form-field">
                  <span>Contato</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][contato]" value="">
                </label>
                <label class="cad-form-field">
                  <span>Telefone fixo</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][telefone_fixo]" value="" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                </label>
                <label class="cad-form-field">
                  <span>WhatsApp</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][whatsapp]" value="" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                </label>
                <label class="cad-form-field">
                  <span>Celular</span>
                  <input type="text" name="motoristas_vinculados[__INDEX__][celular]" value="" inputmode="tel" autocomplete="off" data-cad-mask="telefone">
                </label>
                <label class="cad-form-field">
                  <span>E-mail</span>
                  <input type="email" name="motoristas_vinculados[__INDEX__][email]" value="">
                </label>
                <label class="cad-form-check cad-form-check--inline">
                  <input type="checkbox" name="motoristas_vinculados[__INDEX__][principal]" value="1">
                  <span>Principal</span>
                </label>
              </div>
            </article>
          </template>

          <template id="cadTemplateVeiculo">
            <article class="cad-repeater__item" data-cad-repeat-item="veiculo">
              <div class="cad-repeater__item-head">
                <strong>Veículo adicional</strong>
                <button type="button" class="fin-action-ico" data-cad-remove-item data-tip="Remover"><i class="fa-solid fa-trash"></i></button>
              </div>
              <div class="cad-form-grid cad-form-grid--three">
                <label class="cad-form-field">
                  <span>Modelo</span>
                  <select name="veiculos[__INDEX__][modelo]">
                    <?= cad_form_select_options($veiculoModelos, '', 'Selecione o modelo') ?>
                  </select>
                </label>
                <label class="cad-form-field">
                  <span>Placa</span>
                  <input type="text" name="veiculos[__INDEX__][placa]" value="" maxlength="8" autocomplete="off" data-cad-mask="placa">
                </label>
                <label class="cad-form-field">
                  <span>Placa adicional</span>
                  <input type="text" name="veiculos[__INDEX__][placa_adicional]" value="" maxlength="8" autocomplete="off" data-cad-mask="placa">
                </label>
                <label class="cad-form-field">
                  <span>Tipo de carroceria</span>
                  <select name="veiculos[__INDEX__][tipo_carroceria]">
                    <?= cad_form_select_options($veiculoCarrocerias, '', 'Selecione a carroceria') ?>
                  </select>
                </label>
                <label class="cad-form-field">
                  <span>Metragem</span>
                  <input type="text" name="veiculos[__INDEX__][metragem]" value="">
                </label>
                <label class="cad-form-field">
                  <span>Peso de carga</span>
                  <input type="text" name="veiculos[__INDEX__][peso_carga]" value="">
                </label>
              </div>
            </article>
          </template>

          <div class="cad-form-actions">
            <a class="fin-btn fin-btn--ghost" href="<?= h($backHref) ?>" data-cad-toast="Retornando para a listagem" data-cad-toast-kind="info">Cancelar</a>
            <button class="fin-btn cad-btn-primary" type="submit">
              <i class="fa-solid fa-floppy-disk"></i><span><?= h($id > 0 ? 'Salvar alterações' : 'Criar cadastro') ?></span>
            </button>
          </div>
        </form>
      <?php else: ?>
        <section class="admin-block">
          <div class="admin-block-head">
            <h2 class="admin-block-title"><i class="fa-solid fa-id-card" aria-hidden="true"></i><span>Identificação</span></h2>
            <span class="admin-card-meta cad-list-count"><span><i class="fa-solid fa-badge-check" aria-hidden="true"></i><?= h(cad_ficha_status((string)($cadastro['status'] ?? 'ativo'))) ?></span></span>
          </div>
          <div class="admin-block-body">
            <div class="cad-sheet__hero-row cad-view-hero-row">
              <aside class="cad-sheet__avatar-col">
                <div class="cad-sheet__avatar cad-view-hero__avatar" aria-hidden="true">
                  <img src="<?= h($avatarSrc) ?>" alt="Avatar do cadastro">
                </div>
              </aside>

              <article class="cad-ficha-card cad-ficha-card--hero cad-view-hero">
                <div class="cad-modal__eyebrow">Cadastro central</div>
                <h3><?= h((string)($cadastro['razaoSocial'] ?? $cadastro['nome'] ?? '')) ?></h3>
                <p><?= h(cad_ficha_tipo_pessoa((string)($cadastro['tipoPessoa'] ?? 'PF'))) ?></p>

                <div class="cad-ficha-pillrow">
                  <span class="cad-status cad-status--<?= h(strtolower(trim((string)($cadastro['status'] ?? 'ativo'))) === 'inativo' ? 'inativo' : 'ativo') ?>">
                    <?= h(cad_ficha_status((string)($cadastro['status'] ?? 'ativo'))) ?>
                  </span>
                  <span class="cad-ficha-pill"><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i><?= h(cad_ficha_field((string)($cadastro['documento'] ?? ''), 'Documento não informado')) ?></span>
                </div>

                <div class="cad-view-hero__metrics">
                  <div class="cad-view-hero__metric">
                    <span><i class="fa-solid fa-user-tag" aria-hidden="true"></i>Tipo principal</span>
                    <strong><?= h($currentTypeMeta['title']) ?></strong>
                  </div>
                  <div class="cad-view-hero__metric">
                    <span><i class="fa-solid fa-phone" aria-hidden="true"></i>Contato rápido</span>
                    <strong><?= h(cad_ficha_field((string)($cadastro['celular'] ?? $cadastro['whatsapp'] ?? ''), 'Não informado')) ?></strong>
                  </div>
                  <div class="cad-view-hero__metric">
                    <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>Cidade</span>
                    <strong><?= h(cad_ficha_field((string)($cadastro['cidade'] ?? ''), 'Não informada')) ?></strong>
                  </div>
                </div>
              </article>
            </div>

            <div class="cad-ficha-grid cad-ficha-grid--intro">
              <article class="cad-ficha-card cad-ficha-card--soft cad-sheet__section-wide">
                <?= cad_view_section_head('fa-solid fa-building-user', 'Identificação', 'Núcleo principal do cadastro', 'Leitura resumida dos dados mais importantes da pessoa ou empresa, mantendo a identificação central acessível logo na abertura da ficha.') ?>
                <div class="cad-ficha-kv cad-ficha-kv--two">
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Nome</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['nome'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Razao social</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['razaoSocial'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Nome fantasia</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['nomeFantasia'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Documento</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['documento'] ?? ''))) ?></strong>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>

        <section class="admin-block">
          <div class="admin-block-head">
            <h2 class="admin-block-title"><i class="fa-solid fa-address-book" aria-hidden="true"></i><span>Contato</span></h2>
          </div>
          <div class="admin-block-body">
            <div class="cad-ficha-grid">
              <article class="cad-ficha-card cad-ficha-card--soft">
                <?= cad_view_section_head('fa-solid fa-phone-volume', 'Contato', 'Canais de comunicação do cadastro', 'Consolida os principais meios de contato para operação, tratativas comerciais e relacionamento com o cadastro.') ?>
                <div class="cad-ficha-kv cad-ficha-kv--two">
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Contato</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['contato'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Telefone fixo</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['telefoneFixo'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">WhatsApp</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['whatsapp'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Celular</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['celular'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">E-mail</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['email'] ?? ''))) ?></strong>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>

        <section class="admin-block">
          <div class="admin-block-head">
            <h2 class="admin-block-title"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>Endereço</span></h2>
          </div>
          <div class="admin-block-body">
            <div class="cad-ficha-grid">
              <article class="cad-ficha-card cad-ficha-card--soft">
                <?= cad_view_section_head('fa-solid fa-map-location-dot', 'Endereço', 'Localização e referência', 'Apresenta o endereço cadastral com leitura rápida para operação, documentação e logística.') ?>
                <div class="cad-ficha-kv cad-ficha-kv--two">
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">CEP</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['cep'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Endereço</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['endereco'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Numero</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['numero'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Complemento</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['complemento'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Bairro</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['bairro'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Cidade</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['cidade'] ?? ''))) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Estado</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['estado'] ?? ''))) ?></strong>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>

        <section class="admin-block">
          <div class="admin-block-head">
            <h2 class="admin-block-title"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Classificacao</span></h2>
          </div>
          <div class="admin-block-body">
            <div class="cad-ficha-grid">
              <article class="cad-ficha-card cad-ficha-card--soft">
                <?= cad_view_section_head('fa-solid fa-tags', 'Classificação', 'Tipos e agrupamentos do cadastro', 'Mostra como este registro está classificado no sistema, facilitando a leitura do papel operacional e dos vínculos existentes.') ?>
                <div class="cad-ficha-pillrow">
                  <?php $tipos = is_array($cadastro['tipos'] ?? null) ? $cadastro['tipos'] : []; ?>
                  <?php if ($tipos === []): ?>
                    <span class="cad-ficha-pill">Sem tipos associados</span>
                  <?php else: ?>
                    <?php foreach ($tipos as $tipoItem): ?>
                      <span class="cad-ficha-pill"><i class="fa-solid fa-tag" aria-hidden="true"></i><?= h((string)($tipoItem['nome'] ?? 'Tipo')) ?></span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </article>
            </div>
          </div>
        </section>

        <?php
          $purchaseRows = array_map(static function (array $row): array {
            $loteHref = app_url('/app/templates/lotes.php?lote=' . (int)($row['loteId'] ?? 0));
            return [
              '<a class="cad-related-link" href="' . h($loteHref) . '">' . h(cad_ficha_field((string)($row['processo'] ?? ''), 'Sem processo')) . '</a>',
              h(cad_ficha_field((string)($row['titulo'] ?? ''), 'Lote sem titulo')),
              h(cad_lot_date((string)($row['data'] ?? ''))),
              h(cad_lot_money((float)($row['custoTotal'] ?? 0))),
              h(cad_lot_money((float)($row['compra'] ?? 0))),
            ];
          }, $cadRelatedPurchases);

          $salesRows = array_map(static function (array $row): array {
            $loteHref = app_url('/app/templates/lotes.php?lote=' . (int)($row['loteId'] ?? 0));
            $valorLiquido = (float)($row['valorBruto'] ?? 0) - (float)($row['valorDevolvido'] ?? 0);
            return [
              '<a class="cad-related-link" href="' . h($loteHref) . '">' . h(cad_ficha_field((string)($row['processo'] ?? ''), 'Sem processo')) . '</a>',
              h(cad_ficha_field((string)($row['produto'] ?? ''), 'Produto nao informado')),
              h(cad_lot_date((string)($row['data'] ?? ''))),
              h(cad_lot_money($valorLiquido)),
              h(cad_ficha_field((string)($row['forma'] ?? ''), 'Nao informada')),
            ];
          }, $cadRelatedSales);

          $freightRows = array_map(static function (array $row): array {
            $loteHref = app_url('/app/templates/lotes.php?lote=' . (int)($row['loteId'] ?? 0));
            $localidade = trim((string)($row['cidade'] ?? ''));
            $estado = trim((string)($row['estado'] ?? ''));
            if ($localidade !== '' && $estado !== '') {
              $localidade .= ' / ' . $estado;
            } elseif ($localidade === '' && $estado !== '') {
              $localidade = $estado;
            }
            return [
              '<a class="cad-related-link" href="' . h($loteHref) . '">' . h(cad_ficha_field((string)($row['processo'] ?? ''), 'Sem processo')) . '</a>',
              h(cad_ficha_field((string)($row['titulo'] ?? ''), 'Lote sem titulo')),
              h(cad_lot_date((string)($row['data'] ?? ''))),
              h(cad_ficha_field($localidade, 'Nao informada')),
              h(cad_lot_money((float)($row['totalFrete'] ?? 0))),
            ];
          }, $cadRelatedFreights);
        ?>

        <section class="admin-block">
          <div class="admin-block-head">
            <h2 class="admin-block-title"><i class="fa-solid fa-box-archive" aria-hidden="true"></i><span>Relacionamentos com lotes</span></h2>
          </div>
          <div class="admin-block-body">
            <div class="cad-ficha-grid">
              <article class="cad-ficha-card cad-ficha-card--soft cad-sheet__card--wide cad-sheet__section-wide">
                <?= cad_view_section_head('fa-solid fa-cart-flatbed', 'Compras em lotes', 'Lotes adquiridos com este cadastro', 'Mostra os processos em que este cadastro apareceu como origem da compra, com link direto para a ficha do lote.') ?>
                <?= cad_related_lot_table(
                  ['Processo', 'Lote', 'Data', 'Valor pago', 'Custo total'],
                  $purchaseRows,
                  'Nenhuma compra em lotes foi encontrada para este cadastro.'
                ) ?>
              </article>
            </div>

            <div class="cad-ficha-grid">
              <article class="cad-ficha-card cad-ficha-card--soft cad-sheet__card--wide cad-sheet__section-wide">
                <?= cad_view_section_head('fa-solid fa-bag-shopping', 'Vendas em lotes', 'Vendas vinculadas a este cadastro', 'Lista as vendas oriundas dos lotes, trazendo processo, produto, valor liquido e link direto para o processo relacionado.') ?>
                <?= cad_related_lot_table(
                  ['Processo', 'Produto', 'Data', 'Valor liquido', 'Forma'],
                  $salesRows,
                  'Nenhuma venda em lotes foi encontrada para este cadastro.'
                ) ?>
              </article>
            </div>

            <?php if (in_array($currentTypeSlug, ['motorista', 'transportadora'], true) || $freightRows !== []): ?>
              <div class="cad-ficha-grid">
                <article class="cad-ficha-card cad-ficha-card--soft cad-sheet__card--wide cad-sheet__section-wide">
                  <?= cad_view_section_head('fa-solid fa-truck-fast', 'Fretes em lotes', 'Fretes relacionados a este cadastro', 'Apresenta os lotes em que este cadastro atuou como motorista ou transportadora, com acesso rapido ao processo.') ?>
                  <?= cad_related_lot_table(
                    ['Processo', 'Lote', 'Data', 'Cidade da coleta', 'Total frete'],
                    $freightRows,
                    'Nenhum frete em lotes foi encontrado para este cadastro.'
                  ) ?>
                </article>
              </div>
            <?php endif; ?>
          </div>
        </section>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Informações adicionais</span></h2>
        </div>
          <div class="admin-block-body">
            <div class="cad-ficha-grid">
              <article class="cad-ficha-card cad-ficha-card--soft">
                <?= cad_view_section_head('fa-solid fa-note-sticky', 'Informações adicionais', 'Observações e histórico do registro', 'Reúne observações livres e referências de criação/atualização para manter o contexto completo do cadastro.') ?>
                <div class="cad-ficha-kv">
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Observacoes</span>
                    <strong class="cad-ficha-kv__value cad-ficha-kv__value--long"><?= h(cad_ficha_field((string)($cadastro['observacoes'] ?? ''), 'Nenhuma observação registrada')) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Criado em</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['createdAt'] ?? ''), 'Não informado')) ?></strong>
                  </div>
                  <div class="cad-ficha-kv__item">
                    <span class="cad-ficha-kv__label">Atualizado em</span>
                    <strong class="cad-ficha-kv__value"><?= h(cad_ficha_field((string)($cadastro['updatedAt'] ?? ''), 'Não informado')) ?></strong>
                  </div>
                </div>
              </article>
            </div>
          </div>
        </section>

      <?php endif; ?>
    </section>

    <?php if (!$isEmbedMode): ?>
      <aside class="admin-main-widgets">
        <?php require __DIR__ . '/../../templates/partials/admin_main_widgets.php'; ?>
      </aside>
    <?php endif; ?>
  </div>
</div>
