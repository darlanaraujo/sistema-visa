<?php
$publicCoverUrl = is_array($cover) ? (string)($cover['url'] ?? '') : '';
$publicCoverName = is_array($cover) ? (string)($cover['name'] ?? 'Imagem do lote') : 'Imagem do lote';
$publicLocation = trim((string)($lote['cidade'] ?? '') . (((string)($lote['estado'] ?? '')) !== '' ? ' / ' . (string)($lote['estado'] ?? '') : ''));
$lotPublicMode = (string)($lotPublicMode ?? 'public');
$lotPublicUrl = lot_public_url($loteId, $token);
$lotPublicListPrintUrl = $selectedPublicListPrintUrl ?? lot_public_print_url($loteId, $token);
$publicGalleryPayload = json_encode(array_values(array_map(static function (array $image): array {
  return [
    'name' => (string)($image['name'] ?? 'Imagem do lote'),
    'previewUrl' => (string)($image['url'] ?? ''),
    'downloadUrl' => (string)($image['url'] ?? ''),
    'isImage' => true,
    'isPdf' => false,
    'isPreviewable' => true,
    'extension' => pathinfo((string)($image['url'] ?? ''), PATHINFO_EXTENSION),
  ];
}, $gallery)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<main class="lot-public-page">
  <div class="lot-public-shell">
    <?php if ($lotPublicMode === 'public'): ?>
      <div class="lot-public-topbar">
        <div class="lot-public-brand">
          <img class="lot-public-brand__logo" src="<?= h(app_url('/app/static/img/favicon.png')) ?>" alt="Visa Remoções">
          <div class="lot-public-brand__copy">
            <span class="lot-public-brand__eyebrow">Lote disponível para venda</span>
            <strong class="lot-public-brand__title">Visa Remoções</strong>
            <span class="lot-public-brand__sub">Ficha comercial pública com atualização em tempo real.</span>
          </div>
        </div>
        <div class="lot-public-actions">
          <button class="fin-btn fin-btn--ghost" type="button" data-lot-public-share="<?= h($lotPublicUrl) ?>">
            <i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>Compartilhar ficha</span>
          </button>
          <a class="fin-btn" href="<?= h($lotPublicListPrintUrl) ?>" target="_blank" rel="noopener noreferrer">
            <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir lista</span>
          </a>
        </div>
      </div>
    <?php endif; ?>

    <section class="lot-public-hero">
      <div class="lot-public-hero__copy">
        <div class="lot-public-chips">
          <span class="lot-public-chip"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span><?= h(lot_public_available_items_label(count($itensDisponiveis))) ?></span></span>
          <span class="lot-public-chip"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span>Processo <?= h(lot_public_text($lote['numeroProcesso'] ?? '', '-')) ?></span></span>
          <?php if ($sinistro !== ''): ?>
            <span class="lot-public-chip"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Sinistro <?= h($sinistro) ?></span></span>
          <?php endif; ?>
        </div>

        <div class="lot-public-hero__headline">
          <h1><?= h(lot_public_text($lote['tituloLote'] ?? '', 'Lote disponível')) ?></h1>
          <p class="lot-public-hero__summary"><?= h($descricao !== '' ? $descricao : 'Consulte abaixo os itens disponíveis, quantidades e valores de venda deste lote.') ?></p>
        </div>

        <div class="lot-public-facts">
          <div class="lot-public-facts__item">
            <span>Processo</span>
            <strong><?= h(lot_public_text($lote['numeroProcesso'] ?? '', '-')) ?></strong>
          </div>
          <div class="lot-public-facts__item">
            <span>Sinistro</span>
            <strong><?= h($sinistro !== '' ? $sinistro : 'Não informado') ?></strong>
          </div>
          <div class="lot-public-facts__item">
            <span>Localidade</span>
            <strong><?= h($publicLocation !== '' ? $publicLocation : 'Não informado') ?></strong>
          </div>
          <div class="lot-public-facts__item">
            <span>Atualizado em</span>
            <strong><?= h(date('d/m/Y H:i')) ?></strong>
          </div>
        </div>
      </div>

      <div class="lot-public-hero__media">
        <button
          class="lot-public-hero__cover<?= $publicCoverUrl === '' ? ' is-empty' : '' ?>"
          type="button"
          <?= $publicCoverUrl !== '' ? 'data-lot-public-gallery-open="0"' : 'disabled' ?>
        >
          <?php if ($publicCoverUrl !== ''): ?>
            <img src="<?= h($publicCoverUrl) ?>" alt="<?= h($publicCoverName) ?>">
          <?php else: ?>
            <div class="lot-public-hero__cover-empty">
              <i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
              <span>Imagens deste lote ainda não foram publicadas.</span>
            </div>
          <?php endif; ?>
        </button>

        <?php if ($gallery !== []): ?>
          <div class="lot-public-gallery" id="lotPublicGalleryGrid">
            <?php foreach (array_slice($gallery, 0, 6) as $index => $image): ?>
              <button class="lot-public-gallery__item" type="button" data-lot-public-gallery-open="<?= h((string)$index) ?>">
                <img src="<?= h((string)($image['url'] ?? '')) ?>" alt="<?= h((string)($image['name'] ?? 'Imagem do lote')) ?>">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <?php if ($tags !== []): ?>
      <section class="lot-public-section">
        <div class="lot-public-section__head">
          <h2><i class="fa-solid fa-tags" aria-hidden="true"></i><span>Características do lote</span></h2>
          <p>Marcadores rápidos para ajudar na identificação comercial dos itens disponíveis.</p>
        </div>
        <div class="lot-public-tags">
          <?php foreach ($tags as $tag): ?>
            <span class="lot-public-tags__item"><?= h($tag) ?></span>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="lot-public-section">
      <div class="lot-public-section__head">
        <h2><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Lista de itens</span></h2>
        <p>Itens disponíveis neste lote com quantidades atualizadas e valores de venda no mesmo formato operacional do sistema.</p>
      </div>

      <?php if ($itensDisponiveis === []): ?>
        <div class="lot-public-empty">Nenhum item disponível para venda neste lote no momento.</div>
      <?php else: ?>
        <div class="fin-table-wrap lot-public-table-wrap">
          <table class="fin-table lot-public-table">
            <thead>
              <tr>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Quantidade disponível</th>
                <th>Valor unitário</th>
                <th>Total sugerido</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($itensDisponiveis as $item): ?>
                <tr>
                  <td>
                    <div class="lot-public-table__product">
                      <strong><?= h(lot_public_text($item['descricaoItem'] ?? '', 'Item')) ?></strong>
                      <?php if (trim((string)($item['observacoesItem'] ?? '')) !== ''): ?>
                        <span><?= h((string)$item['observacoesItem']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td><?= h(lot_public_control_label((string)($item['tipoControleItem'] ?? ''))) ?></td>
                  <td><?= h(lot_public_qty((float)($item['quantidadeDisponivel'] ?? 0))) ?></td>
                  <td><?= h(lot_public_money((float)($item['valorVendaUnitarioSugerido'] ?? 0))) ?></td>
                  <td><?= h(lot_public_money((float)($item['valorVendaTotalSugerido'] ?? 0))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <div class="lot-public-footer">
      Documento comercial público gerado pelo Sistema Visa Remoções. As disponibilidades desta ficha são atualizadas em tempo real.
    </div>
  </div>

  <script type="application/json" id="lotPublicGalleryPayload"><?= $publicGalleryPayload ?: '[]' ?></script>
  <div class="fin-modal lot-public-viewer-modal" id="lotPublicImageViewer" aria-hidden="true">
    <div class="fin-modal__card lot-attachment-viewer__card">
      <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
        <div class="lot-sale-modal__brand lot-detail-modal__brand">
          <img
            class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
            src="<?= h(app_url('/app/static/img/favicon.png')) ?>"
            alt="Visa Remoções"
          >
          <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
            <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name">Visa Remoções</span>
            <strong class="lot-sale-modal__headline lot-detail-modal__headline">Galeria do lote • <?= h(lot_public_text($lote['tituloLote'] ?? '', 'Lote disponível')) ?></strong>
            <span class="lot-detail-modal__subhead">Visualize as imagens comerciais publicadas deste lote em um painel único.</span>
          </div>
        </div>
        <button class="fin-modal__close" type="button" id="lotPublicImageViewerClose" aria-label="Fechar">
          <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
      </div>
      <div class="fin-modal__body lot-attachment-viewer__body">
        <div class="lot-attachment-viewer__stage" id="lotPublicImageViewerStage"></div>
        <div class="sv-attachment-viewer__meta">
          <div class="sv-attachment-viewer__name" id="lotPublicImageViewerName">Imagem</div>
          <div class="lot-attachment-viewer__actions">
            <button class="fin-btn fin-btn--ghost" type="button" id="lotPublicImageViewerPrev">Anterior</button>
            <button class="fin-btn fin-btn--ghost" type="button" id="lotPublicImageViewerNext">Próximo</button>
            <a class="fin-btn" href="#" target="_blank" rel="noopener noreferrer" id="lotPublicImageViewerDownload">Baixar</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
