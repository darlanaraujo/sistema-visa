<?php
// app/modules/cadastros/listagem.php

require_once __DIR__ . '/../../../public_php/src/Repositories/CadastroRepository.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/ArquivoRepository.php';
require_once __DIR__ . '/_anexos_presenter.php';
require_once __DIR__ . '/_lotes_relacionados.php';

$repo = new CadastroRepository();
$arquivoRepo = new ArquivoRepository();

$tipo = trim((string)($_GET['tipo'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));
$busca = trim((string)($_GET['busca'] ?? ''));

$tipoMap = [
  'clientes' => ['slug' => 'cliente', 'title' => 'Clientes'],
  'fornecedores' => ['slug' => 'fornecedor', 'title' => 'Fornecedores'],
  'motoristas' => ['slug' => 'motorista', 'title' => 'Motoristas'],
  'transportadoras' => ['slug' => 'transportadora', 'title' => 'Transportadoras'],
];

$tipoConfig = $tipoMap[$tipo] ?? null;
$tipoSlug = is_array($tipoConfig) ? (string)$tipoConfig['slug'] : '';
$tituloListagem = is_array($tipoConfig) ? (string)$tipoConfig['title'] : 'Todos os cadastros';
$avatarMap = [
  'cliente' => app_url('/app/static/img/avatar-cliente.png'),
  'fornecedor' => app_url('/app/static/img/avatar-fornecedor.png'),
  'motorista' => app_url('/app/static/img/avatar-motorista.png'),
  'transportadora' => app_url('/app/static/img/avatar-transportadora.png'),
];
$resultados = $repo->list([
  'term' => $busca,
  'status' => $status,
  'tipo' => $tipoSlug,
  'limit' => 100,
  'offset' => 0,
], 1);
$resultadosDetalhados = array_values(array_map(
  static function (array $cadastro) use ($repo, $arquivoRepo): array {
    $id = (int)($cadastro['id'] ?? 0);
    if ($id <= 0) {
      return $cadastro;
    }

    $detalhado = $repo->findById($id, 1);
    if (!is_array($detalhado)) {
      return $cadastro;
    }

    $detalhado['anexos'] = cad_present_anexos($arquivoRepo->listByEntity('cadastros', $id, 1));
    $detalhado['lotesRelacionados'] = cad_load_lot_relationships($id, 1);
    return $detalhado;
  },
  $resultados
));

function cad_label_status(string $status): string {
  $value = strtolower(trim($status));
  return $value === 'inativo' ? 'Inativo' : 'Ativo';
}

function cad_tipo_badges(array $tipos): string {
  if ($tipos === []) {
    return 'Sem tipo';
  }

  $labels = [];
  foreach ($tipos as $tipo) {
    $nome = trim((string)($tipo['nome'] ?? ''));
    if ($nome === '') {
      continue;
    }
    $labels[] = $nome;
  }

  return $labels !== [] ? implode(' • ', $labels) : 'Sem tipo';
}

$cadastroJson = json_encode($resultadosDetalhados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$avatarJson = json_encode($avatarMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$cadastroRecentMovements = $repo->listRecentMovimentacoes(1, 8);
$widgetActivities = array_values(array_map(static function (array $movimentacao): array {
  $cadastroNome = trim((string)($movimentacao['cadastroNome'] ?? ''));
  $descricao = trim((string)($movimentacao['descricaoEvento'] ?? ''));
  $title = $descricao !== '' ? $descricao : 'Movimentação registrada';
  if ($cadastroNome !== '') {
    $title .= ' • ' . $cadastroNome;
  }

  $createdAt = trim((string)($movimentacao['createdAt'] ?? ''));
  $responsavel = trim((string)($movimentacao['responsavel'] ?? ''));
  if ($createdAt === '') {
    $meta = 'Data não informada • --:--';
  } else {
    try {
      $dt = new DateTimeImmutable($createdAt);
      $dt = $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
      $meta = $dt->format('d/m/Y') . ' • ' . $dt->format('H:i');
    } catch (Throwable $e) {
      $meta = 'Data não informada • --:--';
    }
  }
  if ($responsavel !== '') {
    $meta .= ' • ' . $responsavel;
  }

  return [
    'title' => $title,
    'meta' => $meta,
  ];
}, array_filter($cadastroRecentMovements, static fn ($item): bool => is_array($item))));
$widgetActivitiesTitle = 'Movimentações recentes';
?>

<div class="module-page cad-page cad-list-page">
  <div class="admin-main-layout">
    <section class="admin-main-content">
      <div class="module-head cad-head">
        <div class="cad-head__topline">
          <div class="cad-head__eyebrow">Listagem operacional</div>

          <nav class="cad-crumbs" aria-label="Navegação do módulo Cadastros">
            <a
              class="cad-crumbs__back"
              href="<?= h(app_url('/app/templates/cadastros.php')) ?>"
              data-tip="Voltar"
              data-cad-toast="Retornando para a home de Cadastros"
              data-cad-toast-kind="info"
            >
              <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            </a>

            <div class="cad-crumbs__trail">
              <a href="<?= h(app_url('/app/templates/cadastros.php')) ?>" data-cad-toast="Abrindo a home de Cadastros" data-cad-toast-kind="info">Cadastros</a>
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
              <span><?= h($tituloListagem) ?></span>
            </div>
          </nav>
        </div>

        <h1><?= h($tituloListagem) ?></h1>
        <p>Consulta administrativa dos registros já existentes no módulo Cadastros, com busca livre e filtro de status para navegação operacional inicial.</p>
      </div>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Filtros da listagem</span></h2>
        </div>
        <div class="admin-block-body">
          <form class="cad-filters" method="get" action="<?= h(app_url('/app/templates/cadastros_listagem.php')) ?>" data-cad-filter-form>
            <?php if ($tipo !== ''): ?>
              <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
            <?php endif; ?>

            <div class="cad-filter">
              <label for="cadBusca">Buscar</label>
              <input
                id="cadBusca"
                name="busca"
                type="text"
                value="<?= h($busca) ?>"
                placeholder="Nome, razão social, CPF ou CNPJ"
                data-cad-auto-search
              >
            </div>

            <div class="cad-filter cad-filter--status">
              <label for="cadStatus">Status</label>
              <select id="cadStatus" name="status" data-cad-auto-status>
                <option value="">Todos</option>
                <option value="ativo" <?= $status === 'ativo' ? 'selected' : '' ?>>Ativo</option>
                <option value="inativo" <?= $status === 'inativo' ? 'selected' : '' ?>>Inativo</option>
              </select>
            </div>

            <div class="cad-filter cad-filter--actions">
              <label>&nbsp;</label>
              <div class="cad-filter-actions">
                <button class="fin-btn cad-filter-btn" type="submit" data-cad-submit-action="manual">
                  <i class="fa-solid fa-filter"></i><span>Aplicar filtros</span>
                </button>

                <a
                  class="fin-btn fin-btn--ghost cad-filter-btn cad-btn-link"
                  href="<?= h(app_url('/app/templates/cadastros_listagem.php' . ($tipo !== '' ? '?tipo=' . urlencode($tipo) : ''))) ?>"
                  data-cad-clear-filters
                >
                  <i class="fa-solid fa-eraser"></i><span>Limpar</span>
                </a>
              </div>
            </div>
          </form>
        </div>
      </section>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-table-list" aria-hidden="true"></i><span>Resultados</span></h2>

          <div class="cad-list-head-actions">
            <span class="admin-card-meta cad-list-count"><span><i class="fa-solid fa-list-check" aria-hidden="true"></i><?= h((string)count($resultados)) ?> registros</span></span>
            <?php
              $novoQuery = array_filter([
                'modo' => 'cadastro',
                'tipo' => $tipo !== '' ? $tipo : null,
              ], static fn ($value) => $value !== null && $value !== '');
            ?>
            <a class="fin-btn cad-btn-primary" href="<?= h(app_url('/app/templates/cadastros_ficha.php?' . http_build_query($novoQuery))) ?>" data-cad-toast="Abrindo formulario de cadastro" data-cad-toast-kind="info">
              <i class="fa-solid fa-plus"></i><span>Novo cadastro</span>
            </a>
          </div>
        </div>

        <div class="admin-block-body">
          <div class="cad-empty" data-cad-empty <?= $resultados === [] ? '' : 'hidden' ?>>
              <div class="cad-empty__icon"><i class="fa-solid fa-folder-open"></i></div>
              <h3>Nenhum cadastro encontrado</h3>
              <p>Ajuste os filtros ou avance para outra categoria do módulo para consultar novos registros.</p>
          </div>

          <div class="fin-table-wrap cad-table-wrap" data-cad-table-wrap <?= $resultados === [] ? 'hidden' : '' ?>>
            <table class="fin-table cad-table">
              <thead>
                <tr>
                  <th class="t-left">Nome / Razão social</th>
                  <th>Documento</th>
                  <th>Telefone</th>
                  <th>Cidade / UF</th>
                  <th>Status</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody data-cad-results-body>
                <?php foreach ($resultados as $cadastro): ?>
                  <?php
                    $cidade = trim((string)($cadastro['cidade'] ?? ''));
                    $estado = trim((string)($cadastro['estado'] ?? ''));
                    $localidade = trim($cidade . ($estado !== '' ? ' / ' . $estado : ''));
                    $statusValue = strtolower(trim((string)($cadastro['status'] ?? 'ativo')));
                    $tiposValue = cad_tipo_badges(is_array($cadastro['tipos'] ?? null) ? $cadastro['tipos'] : []);
                    $searchValue = strtolower(trim((string)($cadastro['nome'] ?? '')) . ' ' . trim((string)($cadastro['documento'] ?? '')) . ' ' . preg_replace('/\D+/', '', (string)($cadastro['documento'] ?? '')) . ' ' . $tiposValue);
                  ?>
                  <tr data-cad-row data-cad-search="<?= h($searchValue) ?>" data-cad-status="<?= h($statusValue) ?>">
                    <td class="cad-col-main">
                      <div class="cad-row-title"><?= h((string)($cadastro['nome'] ?? '')) ?></div>
                      <div class="cad-row-meta"><?= h($tiposValue) ?></div>
                    </td>
                    <td><?= h((string)($cadastro['documento'] ?? '')) ?></td>
                    <td><?= h((string)($cadastro['telefone'] ?? '—')) ?></td>
                    <td><?= h($localidade !== '' ? $localidade : '—') ?></td>
                    <td>
                      <?php if ($statusValue === 'inativo'): ?>
                        <span class="fin-status is-open" data-tip="Inativo"><i class="fa-solid fa-circle-dot"></i></span>
                      <?php else: ?>
                        <span class="fin-status is-done" data-tip="Ativo"><i class="fa-solid fa-circle-check"></i></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div class="fin-actions-row">
                          <?php
                            $editQuery = array_filter([
                              'id' => (int)($cadastro['id'] ?? 0),
                              'modo' => 'cadastro',
                              'tipo' => $tipo !== '' ? $tipo : null,
                            ], static fn ($value) => $value !== null && $value !== '');
                          ?>
                        <button class="fin-action-ico is-highlight" type="button" data-tip="Visualizar" aria-label="Visualizar cadastro" data-cad-action="view" data-cad-id="<?= h((string)($cadastro['id'] ?? 0)) ?>">
                          <i class="fa-solid fa-eye"></i>
                        </button>
                        <a class="fin-action-ico" href="<?= h(app_url('/app/templates/cadastros_ficha.php?' . http_build_query($editQuery))) ?>" data-tip="Editar" aria-label="Editar cadastro" data-cad-toast="Abrindo pagina do cadastro" data-cad-toast-kind="info">
                          <i class="fa-solid fa-pen"></i>
                        </a>
                        <button class="fin-action-ico" type="button" data-tip="<?= h($statusValue === 'inativo' ? 'Ativar' : 'Desativar') ?>" aria-label="Ativar ou desativar cadastro" data-cad-action="toggle" data-cad-id="<?= h((string)($cadastro['id'] ?? 0)) ?>">
                          <i class="fa-solid <?= h($statusValue === 'inativo' ? 'fa-rotate-left' : 'fa-ban') ?>"></i>
                        </button>
                        <button class="fin-action-ico" type="button" data-tip="Excluir" aria-label="Excluir cadastro" data-cad-action="delete" data-cad-id="<?= h((string)($cadastro['id'] ?? 0)) ?>">
                          <i class="fa-solid fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <script>
        window.__CADASTROS_LIST__ = <?= $cadastroJson ?: '[]' ?>;
        window.__CADASTROS_AVATARS__ = <?= $avatarJson ?: '{}' ?>;
      </script>

      <div class="fin-modal" id="cadViewModal" aria-hidden="true">
        <div class="fin-modal__card cad-modal__card cad-sheet">
          <div class="fin-modal__head cad-sheet__head">
            <div class="fin-modal__title cad-sheet__title" id="cadViewModalTitle">Ficha do cadastro</div>
            <button class="fin-modal__close cad-sheet__close" id="cadViewModalClose" type="button" aria-label="Fechar ficha">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <div class="fin-modal__body cad-modal__body cad-sheet__body">
            <div class="cad-sheet__hero-row">
              <aside class="cad-sheet__avatar-col">
                <div class="cad-sheet__avatar" id="cadModalAvatar" aria-hidden="true">CD</div>
              </aside>

              <div class="cad-modal__hero cad-sheet__hero-card">
                <div class="cad-modal__eyebrow">Cadastro central</div>
                <h3 id="cadModalHeroTitle">Cadastro</h3>
                <p id="cadModalHeroSubtitle">Visualização detalhada do cadastro selecionado.</p>
                <div class="cad-ficha-pillrow" id="cadModalPills"></div>
                <div class="cad-view-hero__metrics cad-view-hero__metrics--modal">
                  <div class="cad-view-hero__metric">
                    <span><i class="fa-solid fa-user-tag" aria-hidden="true"></i>Tipo principal</span>
                    <strong id="cadModalMetricTipo">-</strong>
                  </div>
                  <div class="cad-view-hero__metric">
                    <span><i class="fa-solid fa-phone" aria-hidden="true"></i>Contato rápido</span>
                    <strong id="cadModalMetricContato">-</strong>
                  </div>
                  <div class="cad-view-hero__metric">
                    <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i>Cidade</span>
                    <strong id="cadModalMetricCidade">-</strong>
                  </div>
                </div>
              </div>
            </div>

            <div class="cad-ficha-grid cad-sheet__sections">
              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Identificação</div>
                    <h3>Dados centrais do cadastro</h3>
                    <p>Leitura rápida dos dados principais da pessoa ou empresa selecionada.</p>
                  </div>
                </div>
                <dl class="cad-sheet__grid cad-sheet__grid--two" id="cadModalIdentificacaoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-address-book" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Contato</div>
                    <h3>Canais de comunicação</h3>
                    <p>Telefone, WhatsApp, celular e e-mail organizados em uma leitura operacional.</p>
                  </div>
                </div>
                <dl class="cad-sheet__grid cad-sheet__grid--two" id="cadModalContatoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Endereço</div>
                    <h3>Localização e referência</h3>
                    <p>Endereço cadastral completo para leitura rápida e apoio operacional.</p>
                  </div>
                </div>
                <dl class="cad-sheet__grid cad-sheet__grid--two" id="cadModalEnderecoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-tags" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Classificação</div>
                    <h3>Tipos e agrupamentos</h3>
                    <p>Mostra como o cadastro está classificado e associado dentro do sistema.</p>
                  </div>
                </div>
                <dl class="cad-sheet__grid" id="cadModalClassificacaoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalEstruturaCard" hidden>
                <div class="cad-ficha-card__eyebrow" id="cadModalEstruturaTitle">Estrutura operacional</div>
                <div class="cad-modal-stack" id="cadModalEstruturaRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalVeiculosCard" hidden>
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-truck-front" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Veículos</div>
                    <h3>Base veicular</h3>
                    <p>Estrutura de veículos vinculados ao cadastro operacional.</p>
                  </div>
                </div>
                <div class="cad-modal-stack" id="cadModalVeiculosRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalAnexosCard">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-paperclip" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Anexos</div>
                    <h3>Documentação vinculada</h3>
                    <p>Arquivos, imagens e documentos relacionados ao cadastro.</p>
                  </div>
                </div>
                <div class="sv-attachments__empty" id="cadModalAnexosEmpty">Nenhum anexo vinculado a este cadastro.</div>
                <div class="sv-attachments__grid" id="cadModalAnexosRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalTagsCard">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Tags estruturadas</div>
                    <h3>Classificação inteligente</h3>
                    <p>Tags e agrupamentos que ajudam a cruzar o cadastro com outros módulos.</p>
                  </div>
                </div>
                <div class="cad-modal-tags" id="cadModalTagsRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalLotesCard" hidden>
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-box-archive" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Relacionamentos com lotes</div>
                    <h3>Compras, vendas e fretes vinculados</h3>
                    <p>Leitura resumida dos processos em que este cadastro aparece ligado ao módulo de lotes.</p>
                  </div>
                </div>
                <div class="cad-modal-stack" id="cadModalLotesRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-section-head">
                  <div class="cad-ficha-section-head__icon"><i class="fa-solid fa-note-sticky" aria-hidden="true"></i></div>
                  <div class="cad-ficha-section-head__copy">
                    <div class="cad-ficha-card__eyebrow">Informações adicionais</div>
                    <h3>Observações complementares</h3>
                    <p>Resumo livre do contexto e observações relevantes do cadastro.</p>
                  </div>
                </div>
                <dl class="cad-sheet__grid">
                  <div class="cad-sheet__row cad-sheet__row--long">
                    <dt>Observações</dt>
                    <dd id="cadModalObservacoes">-</dd>
                  </div>
                </dl>
              </section>
            </div>
          </div>

          <div class="fin-modal__actions cad-modal__actions cad-sheet__foot">
            <button class="fin-btn fin-btn--ghost" id="cadViewModalCloseFoot" type="button">Fechar</button>
            <button class="fin-btn fin-btn--ghost" id="cadModalPrintBtn" type="button">
              <i class="fa-solid fa-print"></i><span>Imprimir</span>
            </button>
            <a class="fin-btn cad-btn-primary" id="cadModalEditLink" href="<?= h(app_url('/app/templates/cadastros_ficha.php')) ?>" data-cad-toast="Abrindo pagina do cadastro" data-cad-toast-kind="info">
              <i class="fa-solid fa-pen"></i><span>Editar</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <aside class="admin-main-widgets">
      <?php require __DIR__ . '/../../templates/partials/admin_main_widgets.php'; ?>
    </aside>
  </div>
</div>
