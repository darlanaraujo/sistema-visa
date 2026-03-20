<?php
// app/modules/cadastros/home.php

require_once __DIR__ . '/../../../public_php/src/Repositories/CadastroRepository.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/ArquivoRepository.php';
require_once __DIR__ . '/_anexos_presenter.php';

$cadastroRepo = new CadastroRepository();
$arquivoRepo = new ArquivoRepository();
$avatarMap = [
  'cliente' => app_url('/app/static/img/avatar-cliente.png'),
  'fornecedor' => app_url('/app/static/img/avatar-fornecedor.png'),
  'motorista' => app_url('/app/static/img/avatar-motorista.png'),
  'transportadora' => app_url('/app/static/img/avatar-transportadora.png'),
];
$cadastroQuickSearchSource = $cadastroRepo->list(['limit' => 300], 1);
$cadastroQuickSearchDetailed = array_values(array_map(
  static function (array $cadastro) use ($cadastroRepo, $arquivoRepo): array {
    $id = (int)($cadastro['id'] ?? 0);
    if ($id <= 0) {
      return $cadastro;
    }

    $detalhado = $cadastroRepo->findById($id, 1);
    if (!is_array($detalhado)) {
      return $cadastro;
    }

    $detalhado['anexos'] = cad_present_anexos($arquivoRepo->listByEntity('cadastros', $id, 1));
    return $detalhado;
  },
  $cadastroQuickSearchSource
));

$cadastroQuickSearchItems = array_map(static function (array $item): array {
  $tipos = array_values(array_filter(array_map(static function (array $tipo): array {
    return [
      'slug' => (string)($tipo['slug'] ?? ''),
      'nome' => (string)($tipo['nome'] ?? ''),
    ];
  }, is_array($item['tipos'] ?? null) ? $item['tipos'] : []), static fn (array $tipo): bool => trim((string)($tipo['slug'] ?? '')) !== ''));

  return [
    'id' => (int)($item['id'] ?? 0),
    'nome' => (string)($item['nome'] ?? ''),
    'razaoSocial' => (string)($item['razaoSocial'] ?? ''),
    'documento' => (string)($item['documento'] ?? ''),
    'tipoPessoa' => (string)($item['tipoPessoa'] ?? 'PF'),
    'status' => (string)($item['status'] ?? 'ativo'),
    'telefone' => (string)($item['telefone'] ?? ''),
    'telefoneFixo' => (string)($item['telefoneFixo'] ?? ''),
    'whatsapp' => (string)($item['whatsapp'] ?? ''),
    'celular' => (string)($item['celular'] ?? ''),
    'tipos' => $tipos,
  ];
}, $cadastroQuickSearchDetailed);
$cadastroQuickSearchJson = json_encode($cadastroQuickSearchItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$cadastroModalJson = json_encode($cadastroQuickSearchDetailed, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$avatarJson = json_encode($avatarMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$cadastroAreas = [
  [
    'key' => 'clientes',
    'icon' => 'fa-solid fa-user-group',
    'title' => 'Clientes',
    'desc' => 'Ponto de entrada para a futura listagem de clientes vinculados ao núcleo administrativo do sistema.',
    'meta' => 'Relacionamento comercial e operacional',
    'href' => app_url('/app/templates/cadastros_listagem.php?tipo=clientes'),
  ],
  [
    'key' => 'fornecedores',
    'icon' => 'fa-solid fa-truck-field',
    'title' => 'Fornecedores',
    'desc' => 'Base preparada para fornecedores que apoiarão compras, serviços e integrações administrativas futuras.',
    'meta' => 'Suprimentos, serviços e apoio externo',
    'href' => app_url('/app/templates/cadastros_listagem.php?tipo=fornecedores'),
  ],
  [
    'key' => 'motoristas',
    'icon' => 'fa-solid fa-id-badge',
    'title' => 'Motoristas',
    'desc' => 'Área reservada para os cadastros operacionais de motoristas, com navegação pronta para evolução posterior.',
    'meta' => 'Operação de campo e apoio logístico',
    'href' => app_url('/app/templates/cadastros_listagem.php?tipo=motoristas'),
  ],
  [
    'key' => 'transportadoras',
    'icon' => 'fa-solid fa-truck-front',
    'title' => 'Transportadoras',
    'desc' => 'Entrada institucional para transportadoras, preservando a organização centralizada dos agentes logísticos.',
    'meta' => 'Estrutura logística e parceiros externos',
    'href' => app_url('/app/templates/cadastros_listagem.php?tipo=transportadoras'),
  ],
];

$cadastroCount = count($cadastroAreas);
?>

<div class="module-page cad-page">
  <div class="admin-main-layout">
    <section class="admin-main-content">
      <div class="module-head cad-head">
        <div class="cad-head__eyebrow">Núcleo administrativo central</div>
        <h1>Cadastros</h1>
        <p>Este módulo centraliza a base cadastral do sistema e prepara a navegação para clientes, fornecedores, motoristas e transportadoras sem antecipar os fluxos funcionais das próximas partes.</p>
      </div>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-compass-drafting" aria-hidden="true"></i><span>Painel do módulo</span></h2>
        </div>
        <div class="admin-block-body">
          <p>Cadastros nasce como um núcleo administrativo estrutural, pensado para concentrar a identidade das pessoas e empresas que serão reutilizadas em diferentes áreas do sistema com consistência e expansão controlada.</p>

          <div class="admin-card-meta cad-head__meta" aria-label="Resumo do módulo">
            <span><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i><?= h((string)$cadastroCount) ?> categorias iniciais</span>
            <span><i class="fa-solid fa-diagram-project" aria-hidden="true"></i>Base pronta para listagens futuras</span>
            <span><i class="fa-solid fa-building-shield" aria-hidden="true"></i>Módulo administrativo central</span>
          </div>

          <div class="cad-quick-search" data-cad-quick-search data-cad-items='<?= h($cadastroQuickSearchJson ?: '[]') ?>'>
            <div class="cad-quick-search__head">
              <h3><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Busca rápida</span></h3>
              <p>Consulte qualquer cadastro direto do painel. A lista só aparece quando houver busca.</p>
            </div>

            <div class="cad-quick-search__inputrow">
              <label class="cad-quick-search__field">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="search" placeholder="Busque por nome, razão social ou documento" data-cad-quick-input autocomplete="off">
              </label>
            </div>

            <div class="fin-table-wrap cad-table-wrap">
              <table class="fin-table cad-table cad-quick-search__table">
                <colgroup>
                  <col class="cad-quick-search__col-name">
                  <col class="cad-quick-search__col-phone">
                  <col class="cad-quick-search__col-actions">
                </colgroup>
                <thead>
                  <tr>
                    <th class="t-left">Nome</th>
                    <th>Telefone</th>
                    <th>Ações</th>
                  </tr>
                </thead>
                <tbody data-cad-quick-results hidden></tbody>
              </table>
            </div>
            <div class="cad-quick-search__empty" data-cad-quick-empty hidden>Nenhum cadastro encontrado para esta busca.</div>
          </div>
        </div>
      </section>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span>Entradas do módulo</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="cad-grid">
            <?php foreach ($cadastroAreas as $area): ?>
              <a class="admin-card cad-card" href="<?= h($area['href']) ?>" aria-label="<?= h($area['title']) ?>">
                <span class="admin-card-icon cad-card__icon" aria-hidden="true"><i class="<?= h($area['icon']) ?>"></i></span>

                <div class="admin-card-body">
                  <h3 class="admin-card-title"><?= h($area['title']) ?></h3>
                  <p class="admin-card-desc"><?= h($area['desc']) ?></p>

                  <div class="admin-card-meta">
                    <span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>Entrada preparada</span>
                    <span><i class="fa-solid fa-layer-group" aria-hidden="true"></i><?= h($area['meta']) ?></span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <script>
        window.__CADASTROS_LIST__ = <?= $cadastroModalJson ?: '[]' ?>;
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
              </div>
            </div>

            <div class="cad-ficha-grid cad-sheet__sections">
              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-card__eyebrow">Identificação</div>
                <dl class="cad-sheet__grid cad-sheet__grid--two" id="cadModalIdentificacaoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-card__eyebrow">Contato</div>
                <dl class="cad-sheet__grid cad-sheet__grid--two" id="cadModalContatoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-card__eyebrow">Endereço</div>
                <dl class="cad-sheet__grid cad-sheet__grid--two" id="cadModalEnderecoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-card__eyebrow">Classificação</div>
                <dl class="cad-sheet__grid" id="cadModalClassificacaoRows"></dl>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalEstruturaCard" hidden>
                <div class="cad-ficha-card__eyebrow" id="cadModalEstruturaTitle">Estrutura operacional</div>
                <div class="cad-modal-stack" id="cadModalEstruturaRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalVeiculosCard" hidden>
                <div class="cad-ficha-card__eyebrow">Veículos</div>
                <div class="cad-modal-stack" id="cadModalVeiculosRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalAnexosCard">
                <div class="cad-ficha-card__eyebrow">Anexos</div>
                <div class="sv-attachments__empty" id="cadModalAnexosEmpty">Nenhum anexo vinculado a este cadastro.</div>
                <div class="sv-attachments__grid" id="cadModalAnexosRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide" id="cadModalTagsCard">
                <div class="cad-ficha-card__eyebrow">Tags estruturadas</div>
                <div class="cad-modal-tags" id="cadModalTagsRows"></div>
              </section>

              <section class="cad-ficha-card cad-sheet__card cad-sheet__card--wide cad-sheet__section-wide">
                <div class="cad-ficha-card__eyebrow">Informações adicionais</div>
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
