<?php
// app/modules/ferramentas/home.php

require_once __DIR__ . '/../../../public_php/src/Support/Database.php';

function ft_activity_datetime(string $createdAt): string {
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

function ft_movement_summary(array $entry): string {
  $payload = is_array($entry['payloadEstrutural'] ?? null) ? $entry['payloadEstrutural'] : [];
  $scope = trim((string)($entry['scope'] ?? 'Ferramentas'));
  $name = trim((string)($payload['name'] ?? $payload['nome'] ?? ''));
  $fallback = trim((string)($entry['descricaoEvento'] ?? 'Movimentação registrada'));

  return match ((string)($entry['tipoEvento'] ?? '')) {
    'item_criado' => 'Item criado em ' . $scope . ': ' . ($name !== '' ? $name : 'Registro sem nome'),
    'item_editado' => 'Item editado em ' . $scope . ': ' . ($name !== '' ? $name : 'Registro sem nome'),
    'item_status' => 'Status atualizado em ' . $scope . ': ' . ($name !== '' ? $name : 'Registro sem nome'),
    'item_excluido' => 'Item excluído em ' . $scope . ': ' . ($name !== '' ? $name : 'Registro sem nome'),
    'personalizacao_salva' => 'Personalização do sistema atualizada',
    'personalizacao_restaurada' => 'Personalização do sistema restaurada para o padrão',
    default => $fallback,
  };
}

function ft_load_recent_movements(int $companyId = 1, int $limit = 8): array {
  try {
    $stmt = Database::connection()->prepare(
      'SELECT value_json
         FROM store
        WHERE company_id = :company_id
          AND store_key = :store_key
        LIMIT 1'
    );
    $stmt->execute([
      ':company_id' => $companyId,
      ':store_key' => 'tools_movements_v1',
    ]);
    $row = $stmt->fetch();
    if (!is_array($row) || !isset($row['value_json'])) {
      return [];
    }

    $decoded = json_decode((string)$row['value_json'], true);
    if (!is_array($decoded)) {
      return [];
    }

    return array_values(array_slice(array_filter($decoded, static fn ($item): bool => is_array($item)), 0, $limit));
  } catch (Throwable $e) {
    return [];
  }
}

$groups = [
  [
    'key'   => 'financeiro',
    'icon'  => 'fa-solid fa-coins',
    'title' => 'Financeiro',
    'desc'  => 'Cadastros usados em contas a pagar/receber e relatórios.',
    'quick_desc' => 'Apoio ao contas e relatórios',
    'items' => [
      ['ns'=>'financeiro.imoveis',    'title'=>'Imóveis',    'desc'=>'Centros de custo (ex: Galpão A).', 'icon' => 'fa-solid fa-warehouse'],
      ['ns'=>'financeiro.categorias', 'title'=>'Categorias', 'desc'=>'Categorias de despesas/receitas.', 'icon' => 'fa-solid fa-tags'],
      ['ns'=>'financeiro.formas',     'title'=>'Meios de Pagamento',     'desc'=>'Meios de pagamento.', 'icon' => 'fa-solid fa-credit-card'],
    ],
  ],
  [
    'key'   => 'lotes',
    'icon'  => 'fa-solid fa-boxes-stacked',
    'title' => 'Lotes',
    'desc'  => 'Cadastros do módulo de lotes (separados do financeiro).',
    'quick_desc' => 'Status e apoio operacional',
    'items' => [
      ['ns'=>'lotes.status', 'title'=>'Status (Lotes)', 'desc'=>'Status do fluxo de lotes (não mistura com financeiro).', 'icon' => 'fa-solid fa-list-check'],
    ],
  ],
  [
    'key'   => 'sistema',
    'icon'  => 'fa-solid fa-sliders',
    'title' => 'Sistema',
    'desc'  => 'Preferências gerais e personalização do ambiente administrativo.',
    'quick_desc' => 'Tema, marca e preferências',
    'items' => [
      ['ns'=>'sistema.personalizacao', 'title'=>'Personalização', 'desc'=>'Nome do sistema, empresa, tema e preferências básicas.', 'icon' => 'fa-solid fa-palette'],
    ],
  ],
];

$groupCount = count($groups);
$entryCount = 0;
foreach ($groups as $group) {
  $entryCount += count($group['items'] ?? []);
}

$ftRecentMovements = ft_load_recent_movements(1, 8);
$ftRecentMovementsJson = json_encode($ftRecentMovements, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$widgetActivities = array_values(array_map(static function (array $entry): array {
  $responsavel = trim((string)($entry['responsavel'] ?? ''));
  $meta = ft_activity_datetime((string)($entry['createdAt'] ?? ''));
  if ($responsavel !== '') {
    $meta .= ' • ' . $responsavel;
  }

  return [
    'title' => ft_movement_summary($entry),
    'meta' => $meta,
  ];
}, array_filter($ftRecentMovements, static fn ($item): bool => is_array($item))));
$widgetActivitiesTitle = 'Movimentações recentes';
?>

<div class="fin-page ft-page" id="ftPage">
  <div class="admin-main-layout">
    <section class="admin-main-content">
      <section class="ft-hero">
        <div class="ft-hero__media" aria-hidden="true">
          <img src="<?= h(app_url('/app/static/img/img-ferramentas.png')) ?>" alt="" class="ft-hero__img">
        </div>
        <div class="ft-hero__copy">
          <span class="ft-hero__eyebrow">Centro administrativo</span>
          <h1>Ferramentas</h1>
          <p>Cadastros auxiliares e personalização do ambiente organizados por módulo, com o mesmo padrão visual das áreas mais novas do sistema.</p>
          <div class="ft-hero__stats" aria-label="Resumo das ferramentas">
            <div class="ft-hero__stat">
              <span>Grupos ativos</span>
              <strong><?= h((string)$groupCount) ?></strong>
            </div>
            <div class="ft-hero__stat">
              <span>Entradas disponíveis</span>
              <strong><?= h((string)$entryCount) ?></strong>
            </div>
            <div class="ft-hero__stat">
              <span>Status</span>
              <strong>Fluxo preservado</strong>
            </div>
          </div>
        </div>
      </section>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Painel do módulo</span></h2>
        </div>
        <div class="admin-block-body">
          <p>Esta área centraliza os cadastros auxiliares que sustentam fluxos operacionais do sistema, preservando o comportamento atual do módulo.</p>

          <div class="admin-card-meta" aria-label="Resumo do módulo" style="margin-top:14px;">
            <span><i class="fa-solid fa-cubes" aria-hidden="true"></i><?= h((string)$groupCount) ?> grupos ativos</span>
            <span><i class="fa-solid fa-list-check" aria-hidden="true"></i><?= h((string)$entryCount) ?> entradas disponíveis</span>
            <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Fluxo preservado</span>
          </div>

          <div class="admin-actions-grid" style="margin-top:16px;">
            <?php foreach ($groups as $g): ?>
              <?php $primary = $g['items'][0] ?? null; ?>
              <?php if (!$primary) continue; ?>
              <button
                class="admin-btn admin-btn--tile"
                type="button"
                data-ft-open="<?= h($primary['ns']) ?>"
                data-ft-title="<?= h($primary['title']) ?>"
              >
                <span class="admin-btn-icon"><i class="<?= h($g['icon']) ?>"></i></span>
                <span class="admin-btn-copy">
                  <span class="admin-btn-label"><?= h($g['title']) ?></span>
                  <span class="admin-btn-desc"><?= h($g['quick_desc'] ?? $g['desc']) ?></span>
                </span>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-screwdriver-wrench" aria-hidden="true"></i><span>Grupos disponíveis</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="ft-grid">
            <?php foreach($groups as $g): ?>
              <section class="admin-card ft-card-shell">
                <span class="admin-card-icon" aria-hidden="true"><i class="<?= h($g['icon']) ?>"></i></span>

                <div class="admin-card-body">
                  <h3 class="admin-card-title"><?= h($g['title']) ?></h3>
                  <p class="admin-card-desc"><?= h($g['desc']) ?></p>

                  <div class="admin-card-meta">
                    <span><i class="fa-solid fa-folder-tree" aria-hidden="true"></i><?= h((string)count($g['items'])) ?> entradas</span>
                    <span><i class="fa-solid fa-code-branch" aria-hidden="true"></i>Namespace por módulo</span>
                  </div>

                  <div class="ft-card__items">
                    <?php foreach($g['items'] as $it): ?>
                      <button class="ft-item" type="button"
                              data-ft-open="<?= h($it['ns']) ?>"
                              data-ft-title="<?= h($it['title']) ?>">
                        <span class="ft-item__icon" aria-hidden="true"><i class="<?= h($it['icon'] ?? 'fa-solid fa-circle') ?>"></i></span>
                        <div class="ft-item__main">
                          <div class="ft-item__title"><?= h($it['title']) ?></div>
                          <div class="ft-item__desc"><?= h($it['desc']) ?></div>
                        </div>
                      </button>
                    <?php endforeach; ?>
                  </div>
                </div>
              </section>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </section>

    <aside class="admin-main-widgets">
      <?php require __DIR__ . '/../../templates/partials/admin_main_widgets.php'; ?>
    </aside>
  </div>

  <!-- MODAL CRUD (LISTA) -->
  <div class="fin-modal" id="ftModal" aria-hidden="true">
    <div class="fin-modal__card ft-modal-card">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="ftModalTitle">Cadastro</div>
        <button class="fin-modal__close" id="ftModalClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body ft-modal-body">
        <div class="ft-modal-top">
          <div class="ft-modal-hint" id="ftModalHint"></div>
          <button class="fin-btn" id="ftNew" type="button">
            <i class="fa-solid fa-plus"></i><span>Novo</span>
          </button>
        </div>

        <div class="fin-table-wrap ft-table-wrap">
          <table class="fin-table ft-table">
            <thead>
              <tr>
                <th class="t-left">Nome</th>
                <th class="t-center" style="width:140px;">Ativo</th>
                <th class="t-center" style="width:170px;">Ações</th>
              </tr>
            </thead>
            <tbody id="ftTbody"></tbody>
          </table>
        </div>

        <div class="ft-empty" id="ftEmpty" style="display:none;">
          Nenhum item cadastrado.
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL FORM (NOVO/EDITAR) -->
  <div class="fin-modal" id="ftFormModal" aria-hidden="true">
    <div class="fin-modal__card ft-modal-card ft-modal-card--form">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="ftFormTitle">Novo item</div>
        <button class="fin-modal__close" id="ftFormClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body ft-modal-body">
        <form class="fin-form" id="ftForm">
          <input type="hidden" id="ftId" value="">

          <section class="ft-modal-hero ft-modal-hero--compact">
            <div class="ft-modal-hero__icon" aria-hidden="true"><i class="fa-solid fa-pen-ruler"></i></div>
            <div class="ft-modal-hero__copy">
              <span class="ft-modal-hero__eyebrow">Cadastro auxiliar</span>
              <h3>Registro rápido de ferramenta</h3>
              <p>Cadastre ou ajuste itens de apoio sem sair do módulo, preservando o namespace técnico usado pelo sistema.</p>
            </div>
          </section>

          <section class="ft-form-card">
            <div class="ft-form-card__head">
              <h3><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Dados principais</span></h3>
              <p>Defina o nome de exibição, o status e confira o namespace técnico que será mantido pelo sistema.</p>
            </div>

            <div class="ft-form-grid">
              <div class="fin-field ft-form-grid__wide">
                <label for="ftName">Nome</label>
                <input id="ftName" type="text" placeholder="Ex: Galpão A" autocomplete="off" />
              </div>

              <div class="fin-field">
                <label for="ftActive">Ativo</label>
                <select id="ftActive">
                  <option value="1">Sim</option>
                  <option value="0">Não</option>
                </select>
              </div>

              <div class="fin-field">
                <label>Namespace</label>
                <input id="ftNsView" type="text" disabled />
              </div>
            </div>
          </section>

          <div class="fin-modal__actions">
            <button class="fin-btn fin-btn--ghost" id="ftCancel" type="button">Cancelar</button>
            <button class="fin-btn" type="submit"><i class="fa-solid fa-floppy-disk"></i><span>Salvar</span></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- MODAL DELETE -->
  <div class="fin-modal" id="ftDelModal" aria-hidden="true">
    <div class="fin-modal__card ft-modal-card" style="max-width:520px;">
      <div class="fin-modal__head">
        <div class="fin-modal__title">Excluir item</div>
        <button class="fin-modal__close" id="ftDelClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body ft-modal-body">
        <section class="ft-modal-hero ft-modal-hero--danger">
          <div class="ft-modal-hero__icon" aria-hidden="true"><i class="fa-solid fa-trash"></i></div>
          <div class="ft-modal-hero__copy">
            <span class="ft-modal-hero__eyebrow">Ação irreversível</span>
            <h3>Excluir item</h3>
            <p>Tem certeza que deseja excluir este item? Essa ação remove o cadastro auxiliar da lista atual.</p>
          </div>
        </section>

        <div class="fin-modal__actions">
          <button class="fin-btn fin-btn--ghost" id="ftDelCancel" type="button">Cancelar</button>
          <button class="fin-btn" id="ftDelConfirm" type="button">
            <i class="fa-solid fa-trash"></i><span>Excluir</span>
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: PERSONALIZAÇÃO DO SISTEMA -->
  <div class="fin-modal" id="ftSysModal" aria-hidden="true">
    <div class="fin-modal__card ft-modal-card ft-modal-card--sys" style="max-width:980px;">
      <div class="fin-modal__head">
        <div class="fin-modal__title">
          <i class="fa-solid fa-sliders" style="margin-right:8px;"></i>Personalização do Sistema
        </div>
        <button class="fin-modal__close" id="ftSysClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body ft-modal-body">
        <form class="fin-form" id="ftSysForm" action="javascript:void(0)">

          <section class="ft-modal-hero">
            <div class="ft-modal-hero__icon" aria-hidden="true"><i class="fa-solid fa-sliders"></i></div>
            <div class="ft-modal-hero__copy">
              <span class="ft-modal-hero__eyebrow">Configuração do ambiente</span>
              <h3>Personalização do sistema</h3>
              <p>Ajuste identidade, marca, contato e tema do ambiente administrativo sem afetar a lógica operacional do projeto.</p>
            </div>
          </section>

          <!-- Identidade -->
          <div class="fin-panel ft-panel">
            <div class="fin-panel__head ft-panel__head">
              <div class="fin-panel__title ft-panel__title"><i class="fa-solid fa-id-card"></i><span>Identidade</span></div>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <p class="ft-panel__intro">Os dados abaixo alimentam a identidade textual do sistema e a forma como a empresa aparece em relatórios e telas internas.</p>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label for="ftSysSystemName">Nome do sistema</label>
                  <input id="ftSysSystemName" type="text" placeholder="Ex: Sistema Visa" autocomplete="off" />
                </div>
                <div class="fin-field">
                  <label for="ftSysCompanyName">Nome da empresa</label>
                  <input id="ftSysCompanyName" type="text" placeholder="Ex: Visa Remoções" autocomplete="off" />
                </div>
              </div>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label for="ftSysCnpj">CNPJ</label>
                  <input id="ftSysCnpj" type="text" placeholder="Ex: 00.000.000/0001-00" inputmode="numeric" />
                </div>
                <div class="fin-field">
                  <label for="ftSysRazao">Razão social (opcional)</label>
                  <input id="ftSysRazao" type="text" placeholder="Ex: Visa Remoções LTDA" autocomplete="off" />
                </div>
              </div>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field" style="min-width:260px;">
                  <label for="ftSysSlogan">Slogan (opcional)</label>
                  <input id="ftSysSlogan" type="text" placeholder="Ex: Operação ágil e segura" autocomplete="off" />
                </div>
                <div class="fin-field">
                  <label for="ftSysNotes">Observação interna (opcional)</label>
                  <input id="ftSysNotes" type="text" placeholder="Ex: ambiente de testes" />
                </div>
              </div>
            </div>
          </div>

          <!-- Contato -->
          <div class="fin-panel ft-panel">
            <div class="fin-panel__head ft-panel__head">
              <div class="fin-panel__title ft-panel__title"><i class="fa-solid fa-phone"></i><span>Contato</span></div>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <p class="ft-panel__intro">Mantenha os canais institucionais organizados para uso futuro em cabeçalhos, relatórios e comunicações administrativas.</p>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label for="ftSysSite">Site</label>
                  <input id="ftSysSite" type="text" placeholder="Ex: https://visaremocoes.com.br" autocomplete="off" />
                </div>
                <div class="fin-field">
                  <label for="ftSysEmail">E-mail</label>
                  <input id="ftSysEmail" type="email" placeholder="Ex: contato@empresa.com.br" autocomplete="off" />
                </div>
              </div>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label for="ftSysPhone">Telefone</label>
                  <input id="ftSysPhone" type="text" placeholder="Ex: (62) 99999-9999" inputmode="tel" />
                </div>
                <div class="fin-field">
                  <label for="ftSysWhats">WhatsApp</label>
                  <input id="ftSysWhats" type="text" placeholder="Ex: +55 62 99999-9999" inputmode="tel" />
                </div>
              </div>
            </div>
          </div>

          <!-- Marca -->
          <div class="fin-panel ft-panel">
            <div class="fin-panel__head ft-panel__head">
              <div class="fin-panel__title ft-panel__title"><i class="fa-solid fa-image"></i><span>Marca</span></div>
              <span class="fin-badge fin-badge--pt">local (por navegador)</span>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <p class="ft-panel__intro">Os arquivos de marca ficam salvos no navegador atual e ajudam a personalizar relatórios, cabeçalhos e identificação visual do ambiente.</p>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label>Logo (PNG/JPG)</label>
                  <input id="ftSysLogoFile" type="file" accept="image/png,image/jpeg,image/webp" />

                  <div class="ft-brand-row">
                    <img id="ftSysLogoPreview" alt="Preview logo">
                    <button class="fin-btn fin-btn--ghost" id="ftSysLogoRemove" type="button">
                      <i class="fa-solid fa-rotate-left"></i><span>Resetar (imagem)</span>
                    </button>
                  </div>

                  <div class="ft-brand-tip">
                    Dica: mantenha o arquivo leve (ideal &lt; 200 KB) para não estourar o limite do navegador.
                  </div>
                </div>

                <div class="fin-field">
                  <label>Favicon (PNG/ICO)</label>
                  <input id="ftSysFaviconFile" type="file" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/webp" />

                  <div class="ft-brand-row">
                    <img id="ftSysFaviconPreview" alt="Preview favicon">
                    <button class="fin-btn fin-btn--ghost" id="ftSysFaviconRemove" type="button">
                      <i class="fa-solid fa-rotate-left"></i><span>Resetar (imagem)</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tema -->
          <div class="fin-panel ft-panel">
            <div class="fin-panel__head ft-panel__head">
              <div class="fin-panel__title ft-panel__title"><i class="fa-solid fa-palette"></i><span>Tema</span></div>
              <span class="fin-badge fin-badge--pt" style="opacity:.85;">aplica no navegador</span>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <p class="ft-panel__intro">Use esta área para controlar aparência, compactação das tabelas e paleta principal do ambiente administrativo.</p>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label for="ftSysThemeMode">Modo</label>
                  <select id="ftSysThemeMode">
                    <option value="light">Claro</option>
                    <option value="dark">Escuro</option>
                  </select>
                </div>

                <div class="fin-field">
                  <label for="ftSysCompact">Modo compacto (tabelas)</label>
                  <select id="ftSysCompact">
                    <option value="0">Não</option>
                    <option value="1">Sim</option>
                  </select>
                </div>
              </div>

              <div class="ft-theme-warning" id="ftSysThemeWarn">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Alterar cores pode afetar o padrão visual do sistema.
              </div>


              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label for="ftSysAccentPreset">Cor do sistema (preset)</label>
                  <select id="ftSysAccentPreset">
                    <option value="visa">Visa (padrão)</option>
                    <option value="blue">Azul</option>
                    <option value="green">Verde</option>
                    <option value="purple">Roxo</option>
                    <option value="orange">Laranja</option>
                    <option value="slate">Grafite</option>
                    <option value="custom">Personalizada</option>
                  </select>
                </div>

                <div class="fin-field">
                  <label>Cor personalizada (HEX)</label>
                  <div class="ft-colorline">
                    <input id="ftSysColorAccent" type="color" value="#a42d2d" />
                    <input id="ftSysColorAccentHex" type="text" placeholder="#a42d2d" />
                  </div>
                </div>
              </div>

              <div class="fin-form__row ft-form-grid ft-form-grid--two">
                <div class="fin-field">
                  <label>Cor de Pagamentos</label>
                  <div class="ft-colorline">
                    <input id="ftSysColorDanger" type="color" value="#a42d2d" />
                    <input id="ftSysColorDangerHex" type="text" placeholder="#a42d2d" />
                  </div>
                </div>

                <div class="fin-field">
                  <label>Cor de Recebiveis</label>
                  <div class="ft-colorline">
                    <input id="ftSysColorSuccess" type="color" value="#2f6b4f" />
                    <input id="ftSysColorSuccessHex" type="text" placeholder="#2f6b4f" />
                  </div>
                </div>

              </div>

              <div class="fin-form__row ft-form-grid ft-form-grid--three">
                <div class="fin-field">
                  <label for="ftSysCurrency">Moeda</label>
                  <select id="ftSysCurrency">
                    <option value="BRL">BRL (R$)</option>
                    <option value="USD">USD (US$)</option>
                    <option value="EUR">EUR (€)</option>
                  </select>
                </div>

                <div class="fin-field">
                  <label for="ftSysTimezone">Fuso (display)</label>
                  <input id="ftSysTimezone" type="text" placeholder="Ex: America/Sao_Paulo" />
                </div>
                <div class="fin-field">
                  <label>&nbsp;</label>
                  <div class="ft-theme-actions">
                    <button class="fin-btn fin-btn--ghost" id="ftSysColorsReset" type="button">
                      <i class="fa-solid fa-rotate-left"></i><span>Resetar cores</span>
                    </button>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div class="fin-modal__actions">
            <button class="fin-btn fin-btn--ghost" id="ftSysReset" type="button" title="Apaga a personalização salva">
              <i class="fa-solid fa-rotate-left"></i><span>Restaurar padrão</span>
            </button>

            <button class="fin-btn fin-btn--ghost" id="ftSysCancel" type="button">Cancelar</button>

            <button class="fin-btn" type="submit">
              <i class="fa-solid fa-floppy-disk"></i><span>Salvar</span>
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>

<script>
  window.__FT_RECENT_MOVEMENTS__ = <?= $ftRecentMovementsJson ?: '[]' ?>;
</script>
