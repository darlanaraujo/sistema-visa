<?php
// app/modules/ferramentas/home.php

$groups = [
  [
    'key'   => 'financeiro',
    'icon'  => 'fa-solid fa-coins',
    'title' => 'Financeiro',
    'desc'  => 'Cadastros usados em contas a pagar/receber e relatórios.',
    'items' => [
      ['ns'=>'financeiro.imoveis',    'title'=>'Imóveis',    'desc'=>'Centros de custo (ex: Galpão A).'],
      ['ns'=>'financeiro.categorias', 'title'=>'Categorias', 'desc'=>'Categorias de despesas/receitas.'],
      ['ns'=>'financeiro.formas',     'title'=>'Meios de Pagamento',     'desc'=>'Meios de pagamento.'],
    ],
  ],
  [
    'key'   => 'lotes',
    'icon'  => 'fa-solid fa-boxes-stacked',
    'title' => 'Lotes',
    'desc'  => 'Cadastros do módulo de lotes (separados do financeiro).',
    'items' => [
      ['ns'=>'lotes.status', 'title'=>'Status (Lotes)', 'desc'=>'Status do fluxo de lotes (não mistura com financeiro).'],
    ],
  ],
  [
    'key'   => 'sistema',
    'icon'  => 'fa-solid fa-sliders',
    'title' => 'Sistema',
    'desc'  => 'Preferências gerais e personalização (localStorage nesta fase).',
    'items' => [
      ['ns'=>'sistema.personalizacao', 'title'=>'Personalização', 'desc'=>'Nome do sistema, empresa, tema e preferências básicas.'],
    ],
  ],
];
?>

<div class="fin-page ft-page" id="ftPage">
  <div class="fin-head">
    <h1>Ferramentas</h1>
    <p>Cadastros de apoio por módulo (localStorage nesta fase).</p>
  </div>

  <section class="fin-panel">
    <div class="fin-panel__head">
      <div class="fin-panel__title"><i class="fa-solid fa-layer-group"></i><span>Cadastros</span></div>
      <span class="fin-badge fin-badge--pt">namespace por módulo</span>
    </div>

    <div class="ft-grid">
      <?php foreach($groups as $g): ?>
        <div class="ft-card">
          <div class="ft-card__head">
            <div class="ft-card__title">
              <i class="<?= h($g['icon']) ?>"></i>
              <span><?= h($g['title']) ?></span>
            </div>
            <div class="ft-card__desc"><?= h($g['desc']) ?></div>
          </div>

          <div class="ft-card__items">
            <?php foreach($g['items'] as $it): ?>
              <button class="ft-item" type="button"
                      data-ft-open="<?= h($it['ns']) ?>"
                      data-ft-title="<?= h($it['title']) ?>">
                <div class="ft-item__main">
                  <div class="ft-item__title"><?= h($it['title']) ?></div>
                  <div class="ft-item__desc"><?= h($it['desc']) ?></div>
                </div>
                <i class="fa-solid fa-arrow-right"></i>
              </button>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- MODAL CRUD (LISTA) -->
  <div class="fin-modal" id="ftModal" aria-hidden="true">
    <div class="fin-modal__card">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="ftModalTitle">Cadastro</div>
        <button class="fin-modal__close" id="ftModalClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
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
    <div class="fin-modal__card">
      <div class="fin-modal__head">
        <div class="fin-modal__title" id="ftFormTitle">Novo item</div>
        <button class="fin-modal__close" id="ftFormClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <form class="fin-form" id="ftForm">
          <input type="hidden" id="ftId" value="">

          <div class="fin-field">
            <label for="ftName">Nome</label>
            <input id="ftName" type="text" placeholder="Ex: Galpão A" autocomplete="off" />
          </div>

          <div class="fin-form__row">
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
    <div class="fin-modal__card" style="max-width:520px;">
      <div class="fin-modal__head">
        <div class="fin-modal__title">Excluir item</div>
        <button class="fin-modal__close" id="ftDelClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <p style="margin:0; font-weight:800;">Tem certeza que deseja excluir este item?</p>

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
    <div class="fin-modal__card" style="max-width:980px;">
      <div class="fin-modal__head">
        <div class="fin-modal__title">
          <i class="fa-solid fa-sliders" style="margin-right:8px;"></i>Personalização do Sistema
        </div>
        <button class="fin-modal__close" id="ftSysClose" type="button" aria-label="Fechar">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="fin-modal__body">
        <form class="fin-form" id="ftSysForm" action="javascript:void(0)">

          <!-- Identidade -->
          <div class="fin-panel">
            <div class="fin-panel__head">
              <div class="fin-panel__title"><i class="fa-solid fa-id-card"></i><span>Identidade</span></div>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <div class="fin-form__row">
                <div class="fin-field">
                  <label for="ftSysSystemName">Nome do sistema</label>
                  <input id="ftSysSystemName" type="text" placeholder="Ex: Sistema Visa" autocomplete="off" />
                </div>
                <div class="fin-field">
                  <label for="ftSysCompanyName">Nome da empresa</label>
                  <input id="ftSysCompanyName" type="text" placeholder="Ex: Visa Remoções" autocomplete="off" />
                </div>
              </div>

              <div class="fin-form__row">
                <div class="fin-field">
                  <label for="ftSysCnpj">CNPJ</label>
                  <input id="ftSysCnpj" type="text" placeholder="Ex: 00.000.000/0001-00" inputmode="numeric" />
                </div>
                <div class="fin-field">
                  <label for="ftSysRazao">Razão social (opcional)</label>
                  <input id="ftSysRazao" type="text" placeholder="Ex: Visa Remoções LTDA" autocomplete="off" />
                </div>
              </div>

              <div class="fin-form__row">
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
          <div class="fin-panel">
            <div class="fin-panel__head">
              <div class="fin-panel__title"><i class="fa-solid fa-phone"></i><span>Contato</span></div>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <div class="fin-form__row">
                <div class="fin-field">
                  <label for="ftSysSite">Site</label>
                  <input id="ftSysSite" type="text" placeholder="Ex: https://visaremocoes.com.br" autocomplete="off" />
                </div>
                <div class="fin-field">
                  <label for="ftSysEmail">E-mail</label>
                  <input id="ftSysEmail" type="email" placeholder="Ex: contato@empresa.com.br" autocomplete="off" />
                </div>
              </div>

              <div class="fin-form__row">
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
          <div class="fin-panel">
            <div class="fin-panel__head">
              <div class="fin-panel__title"><i class="fa-solid fa-image"></i><span>Marca</span></div>
              <span class="fin-badge fin-badge--pt">local (por navegador)</span>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <div class="fin-form__row">
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
          <div class="fin-panel">
            <div class="fin-panel__head">
              <div class="fin-panel__title"><i class="fa-solid fa-palette"></i><span>Tema</span></div>
              <span class="fin-badge fin-badge--pt" style="opacity:.85;">aplica no navegador</span>
            </div>

            <div class="fin-panel__body" style="padding:12px;">
              <div class="fin-form__row">
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

              <div class="fin-form__row">
                <div class="fin-field">
                  <label>Cor primária (accent)</label>
                  <div class="ft-colorline">
                    <input id="ftSysColorAccent" type="color" value="#a42d2d" />
                    <input id="ftSysColorAccentHex" type="text" placeholder="#a42d2d" />
                  </div>
                </div>

                <div class="fin-field">
                  <label>Cor perigo</label>
                  <div class="ft-colorline">
                    <input id="ftSysColorDanger" type="color" value="#a42d2d" />
                    <input id="ftSysColorDangerHex" type="text" placeholder="#a42d2d" />
                  </div>
                </div>
              </div>

              <div class="fin-form__row">
                <div class="fin-field">
                  <label>Cor sucesso</label>
                  <div class="ft-colorline">
                    <input id="ftSysColorSuccess" type="color" value="#2f6b4f" />
                    <input id="ftSysColorSuccessHex" type="text" placeholder="#2f6b4f" />
                  </div>
                </div>

                <div class="fin-field">
                  <label for="ftSysCurrency">Moeda</label>
                  <select id="ftSysCurrency">
                    <option value="BRL">BRL (R$)</option>
                    <option value="USD">USD (US$)</option>
                    <option value="EUR">EUR (€)</option>
                  </select>
                </div>
              </div>

              <div class="fin-form__row">
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