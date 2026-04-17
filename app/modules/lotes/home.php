<?php
// app/modules/lotes/home.php

require_once __DIR__ . '/../../../public_php/src/Repositories/LoteRepository.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/CadastroRepository.php';
require_once __DIR__ . '/../../../public_php/src/Repositories/ArquivoRepository.php';
require_once __DIR__ . '/../cadastros/_anexos_presenter.php';
require_once __DIR__ . '/_public_helpers.php';
require_once __DIR__ . '/_payment_helpers.php';

$loteRepo = new LoteRepository();
$cadastroRepo = new CadastroRepository();
$arquivoRepo = new ArquivoRepository();

function lot_normalize_search(string $value): string {
  $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
  if ($value === '') {
    return '';
  }

  $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
  $base = $ascii !== false ? $ascii : $value;
  return strtolower($base);
}

function lot_normalize_slug(string $value): string {
  $normalized = lot_normalize_search($value);
  if ($normalized === '') {
    return '';
  }

  $slug = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
  $slug = trim($slug, '-');
  return $slug;
}

function lot_ufs(): array {
  return [
    'AC' => 'AC',
    'AL' => 'AL',
    'AP' => 'AP',
    'AM' => 'AM',
    'BA' => 'BA',
    'CE' => 'CE',
    'DF' => 'DF',
    'ES' => 'ES',
    'GO' => 'GO',
    'MA' => 'MA',
    'MT' => 'MT',
    'MS' => 'MS',
    'MG' => 'MG',
    'PA' => 'PA',
    'PB' => 'PB',
    'PR' => 'PR',
    'PE' => 'PE',
    'PI' => 'PI',
    'RJ' => 'RJ',
    'RN' => 'RN',
    'RS' => 'RS',
    'RO' => 'RO',
    'RR' => 'RR',
    'SC' => 'SC',
    'SP' => 'SP',
    'SE' => 'SE',
    'TO' => 'TO',
  ];
}

function lot_normalize_state_uf(string $value): string {
  $text = trim($value);
  if ($text === '') {
    return '';
  }

  $upper = mb_strtoupper($text, 'UTF-8');
  $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $upper);
  $normalizedUpper = trim($ascii !== false ? $ascii : $upper);
  $ufMap = lot_ufs();
  if (isset($ufMap[$normalizedUpper])) {
    return $normalizedUpper;
  }

  $names = [
    'ACRE' => 'AC',
    'ALAGOAS' => 'AL',
    'AMAPA' => 'AP',
    'AMAZONAS' => 'AM',
    'BAHIA' => 'BA',
    'CEARA' => 'CE',
    'DISTRITO FEDERAL' => 'DF',
    'ESPIRITO SANTO' => 'ES',
    'GOIAS' => 'GO',
    'MARANHAO' => 'MA',
    'MATO GROSSO' => 'MT',
    'MATO GROSSO DO SUL' => 'MS',
    'MINAS GERAIS' => 'MG',
    'PARA' => 'PA',
    'PARAIBA' => 'PB',
    'PARANA' => 'PR',
    'PERNAMBUCO' => 'PE',
    'PIAUI' => 'PI',
    'RIO DE JANEIRO' => 'RJ',
    'RIO GRANDE DO NORTE' => 'RN',
    'RIO GRANDE DO SUL' => 'RS',
    'RONDONIA' => 'RO',
    'RORAIMA' => 'RR',
    'SANTA CATARINA' => 'SC',
    'SAO PAULO' => 'SP',
    'SERGIPE' => 'SE',
    'TOCANTINS' => 'TO',
  ];
  $normalized = mb_strtoupper(lot_normalize_search($text), 'UTF-8');
  return $names[$normalized] ?? $normalizedUpper;
}

function lot_money(float $value): string {
  $prefix = $value < 0 ? '-R$ ' : 'R$ ';
  return $prefix . number_format(abs($value), 2, ',', '.');
}

function lot_qty(float $value): string {
  return number_format($value, 3, ',', '.');
}

function lot_date(?string $value): string {
  $text = trim((string)($value ?? ''));
  if ($text === '') {
    return 'Não informada';
  }

  $ts = strtotime($text);
  if ($ts === false) {
    return $text;
  }

  return date('d/m/Y', $ts);
}

function lot_status_label(string $status): string {
  return match ($status) {
    'em_transito' => 'Em trânsito',
    'em_estoque' => 'Em estoque',
    'finalizado' => 'Finalizado',
    'cancelado' => 'Cancelado',
    default => 'Não definido',
  };
}

function lot_etapa_label(string $etapa): string {
  return match ($etapa) {
    'compra' => 'Compra',
    'autorizacao_coleta' => 'Autorização de coleta',
    'liberacao_coleta' => 'Liberação de coleta',
    'coleta' => 'Coleta',
    'entrega' => 'Entrega',
    'finalizado' => 'Finalizado',
    default => 'Etapa não definida',
  };
}

function lot_status_chip_class(string $status): string {
  return match ($status) {
    'em_transito' => 'is-transit',
    'em_estoque' => 'is-stock',
    'finalizado' => 'is-done',
    'cancelado' => 'is-danger',
    default => 'is-muted',
  };
}

function lot_priority_label(array $lote): string {
  $status = (string)($lote['statusMacro'] ?? '');
  $dataCompra = (string)($lote['dataCompra'] ?? '');
  $days = null;
  if ($dataCompra !== '') {
    $ts = strtotime($dataCompra);
    if ($ts !== false) {
      $days = (int)floor((time() - $ts) / 86400);
    }
  }

  if ($status === 'em_transito' && $days !== null && $days >= 15) {
    return 'Atenção';
  }
  if ($status === 'finalizado') {
    return 'Resolvido';
  }
  if ($status === 'cancelado') {
    return 'Interrompido';
  }
  if ($status === 'em_estoque') {
    return 'Disponível';
  }

  return 'Em andamento';
}

function lot_text_or_default(?string $value, string $fallback = 'Não informado'): string {
  $text = trim((string)($value ?? ''));
  return $text !== '' ? $text : $fallback;
}

function lot_transport_label(string $tipo): string {
  return match ($tipo) {
    'motorista_autonomo' => 'Motorista autônomo',
    'transportadora' => 'Transportadora',
    'transporte_proprio' => 'Transporte próprio',
    'sem_frete' => 'Sem frete',
    'retirada_cliente' => 'Retirada pelo cliente',
    default => 'Não definido',
  };
}

function lot_timeline_requires_freight_confirmation(array $lote): bool {
  $tipo = trim((string)($lote['tipoTransporte'] ?? ''));
  $motoristaId = (int)($lote['motoristaId'] ?? 0);
  $transportadoraId = (int)($lote['transportadoraId'] ?? 0);

  if (in_array($tipo, ['sem_frete', 'transporte_proprio', 'retirada_cliente'], true)) {
    return false;
  }

  if ($tipo === 'motorista_autonomo') {
    return $motoristaId <= 0;
  }

  if ($tipo === 'transportadora') {
    return $transportadoraId <= 0;
  }

  return true;
}

function lot_control_label(string $tipo): string {
  return match ($tipo) {
    'kg' => 'Kg',
    'metros' => 'Metros',
    default => 'Und',
  };
}

function lot_attachment_groups(): array {
  return [
    'processo' => [
      'key' => 'processo',
      'entity' => 'lotes_processo',
      'title' => 'Anexos do processo',
      'description' => 'E-mails, autorizações, inventário e documentos gerais do processo.',
      'icon' => 'fa-solid fa-folder-open',
      'empty' => 'Nenhum documento do processo enviado ainda.',
    ],
    'produtos' => [
      'key' => 'produtos',
      'entity' => 'lotes_produtos',
      'title' => 'Anexos dos produtos',
      'description' => 'Fotos dos lotes, imagens dos itens e registros visuais da carga.',
      'icon' => 'fa-solid fa-camera-retro',
      'empty' => 'Nenhuma foto ou imagem dos produtos enviada ainda.',
    ],
    'frete' => [
      'key' => 'frete',
      'entity' => 'lotes_frete',
      'title' => 'Documentos do transporte',
      'description' => 'NF, manifesto, CTE, comprovantes fiscais e demais documentos operacionais do transporte deste lote.',
      'icon' => 'fa-solid fa-truck-fast',
      'empty' => 'Nenhum documento do transporte foi enviado ainda.',
    ],
    'cancelamento' => [
      'key' => 'cancelamento',
      'entity' => 'lotes_cancelamento',
      'title' => 'Documentos da ocorrência',
      'description' => 'Comprovantes de estorno, mensagens, termos e demais arquivos que justificam cancelamentos totais ou devoluções parciais do lote.',
      'icon' => 'fa-solid fa-ban',
      'empty' => 'Nenhum documento da ocorrência foi enviado ainda.',
    ],
  ];
}

function lot_present_anexo(array $anexo): array {
  $arquivoId = (int)($anexo['id'] ?? 0);
  if ($arquivoId <= 0) {
    return $anexo;
  }

  $previewUrl = app_url('/app/templates/arquivo.php?' . http_build_query([
    'id' => $arquivoId,
  ]));
  $downloadUrl = app_url('/app/templates/arquivo.php?' . http_build_query([
    'id' => $arquivoId,
    'download' => '1',
  ]));

  $anexo['previewUrl'] = $previewUrl;
  $anexo['downloadUrl'] = $downloadUrl;
  $anexo['displayName'] = trim((string)($anexo['nomeOriginal'] ?? '')) !== '' ? (string)$anexo['nomeOriginal'] : 'Arquivo';
  $anexo['thumbUrl'] = !empty($anexo['isImage']) ? $previewUrl : '';

  return $anexo;
}

function lot_present_anexos(array $anexos): array {
  return array_values(array_filter(array_map(
    static fn ($item) => is_array($item) ? lot_present_anexo($item) : null,
    $anexos
  )));
}

function lot_present_image_anexos(array $anexos): array {
  return array_values(array_filter(
    lot_present_anexos($anexos),
    static fn (array $item): bool => !empty($item['isImage'])
  ));
}

function lot_validate_image_uploads(array $files): void {
  if (!isset($files['name'])) {
    return;
  }

  $tmpNames = (array)($files['tmp_name'] ?? []);
  $errors = (array)($files['error'] ?? []);
  foreach ($tmpNames as $index => $tmpName) {
    if ((int)($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      continue;
    }
    if (!is_string($tmpName) || trim($tmpName) === '' || !is_file($tmpName)) {
      continue;
    }

    $mimeType = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmpName);
    if ($mimeType === '' || !str_starts_with($mimeType, 'image/')) {
      throw new RuntimeException('As fotos do produto aceitam apenas arquivos de imagem.');
    }
  }
}

function lot_module_url(array $params = []): string {
  $query = http_build_query($params);
  return app_url('/app/templates/lotes.php' . ($query !== '' ? '?' . $query : ''));
}

function lot_redirect_global(string $kind, string $message, array $params = []): never {
  $params['timeline_kind'] = $kind;
  $params['timeline_msg'] = $message;
  header('Location: ' . lot_module_url($params));
  exit;
}

function lot_redirect_with_flash(int $loteId, string $kind, string $message, string $anchor = '', string $openModal = ''): never {
  $params = ['lote' => $loteId];
  $params['timeline_kind'] = $kind;
  $params['timeline_msg'] = $message;
  if ($openModal !== '') {
    $_SESSION['lot_open_modal'] = $openModal;
  }
  header('Location: ' . lot_module_url($params) . ($anchor !== '' ? '#' . $anchor : ''));
  exit;
}

function lot_add_months_iso(string $isoDate, int $months): string {
  $isoDate = trim($isoDate);
  $ts = strtotime($isoDate . ' 00:00:00');
  if ($ts === false) {
    $ts = time();
  }
  return date('Y-m-d', strtotime('+' . $months . ' month', $ts));
}

function lot_payment_is_term(string $forma, int $parcelas = 1): bool {
  $forma = strtolower(trim($forma));
  if ($parcelas > 1) {
    return true;
  }
  return in_array($forma, ['a prazo', 'boleto', 'crediario', 'cheque'], true);
}

function lot_decimal_input(mixed $value, int $scale = 3): string {
  $raw = preg_replace('/[^0-9,.\-]/', '', (string)($value ?? '')) ?? '';
  $normalized = $raw;
  if (str_contains($raw, ',') && str_contains($raw, '.')) {
    $lastComma = strrpos($raw, ',');
    $lastDot = strrpos($raw, '.');
    if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
      $normalized = str_replace('.', '', $raw);
      $normalized = str_replace(',', '.', $normalized);
    } else {
      $normalized = str_replace(',', '', $raw);
    }
  } elseif (str_contains($raw, ',')) {
    $normalized = str_replace(',', '.', $raw);
  }
  $number = is_numeric($normalized) ? (float)$normalized : 0.0;
  return number_format($number, $scale, '.', '');
}

function lot_extract_labeled_line(string $text, string $label): string {
  $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line !== '' && stripos($line, $label) === 0) {
      return trim(substr($line, strlen($label)));
    }
  }
  return '';
}

function lot_strip_labeled_lines(string $text, array $labels): string {
  $labels = array_values(array_filter(array_map(static fn ($label): string => trim((string)$label), $labels)));
  $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
  $kept = [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '') {
      continue;
    }
    $skip = false;
    foreach ($labels as $label) {
      if ($label !== '' && stripos($line, $label) === 0) {
        $skip = true;
        break;
      }
    }
    if (!$skip) {
      $kept[] = $line;
    }
  }
  return implode("\n", $kept);
}

function lot_is_structured_local_line(string $line): bool {
  $line = trim($line);
  if ($line === '') {
    return false;
  }

  if (preg_match('/^CPF\/CNPJ local:\s+.+$/u', $line)) {
    return true;
  }
  if (preg_match('/^Telefone 2:\s+.+$/u', $line)) {
    return true;
  }
  if (preg_match('/^Armazenagem:\s+[-R$\s0-9.,]+$/u', $line)) {
    return true;
  }
  if (preg_match('/^Carregamento:\s+[-R$\s0-9.,]+$/u', $line)) {
    return true;
  }
  if (preg_match('/^SOS:\s+[-R$\s0-9.,]+$/u', $line)) {
    return true;
  }
  if (preg_match('/^Outros locais:\s+[-R$\s0-9.,]+$/u', $line)) {
    return true;
  }

  return false;
}

function lot_strip_structured_local_lines(string $text): string {
  $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
  $kept = [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '' || lot_is_structured_local_line($line)) {
      continue;
    }
    $kept[] = $line;
  }
  return implode("\n", $kept);
}

function lot_build_general_notes(string $sinistro, string $observacoesGerais): string {
  $parts = [];
  $sinistro = trim($sinistro);
  $observacoesGerais = trim($observacoesGerais);
  if ($sinistro !== '') {
    $parts[] = 'Sinistro: ' . $sinistro;
  }
  if ($observacoesGerais !== '') {
    $parts[] = $observacoesGerais;
  }
  return implode("\n", $parts);
}

function lot_build_local_notes(
  string $cpfCnpjLocal,
  string $telefoneDois,
  float $custoArmazenagem,
  float $custoCarregamento,
  float $custoSos,
  float $outrosLocais,
  string $observacoesLocal
): string {
  $parts = [];
  $cpfCnpjLocal = preg_replace('/\s+/', ' ', trim(str_replace(["\r", "\n"], ' ', $cpfCnpjLocal))) ?? '';
  $telefoneDois = preg_replace('/\s+/', ' ', trim(str_replace(["\r", "\n"], ' ', $telefoneDois))) ?? '';
  $observacoesLocal = lot_strip_structured_local_lines(trim($observacoesLocal));

  if ($cpfCnpjLocal !== '') {
    $parts[] = 'CPF/CNPJ local: ' . $cpfCnpjLocal;
  }
  if ($telefoneDois !== '') {
    $parts[] = 'Telefone 2: ' . $telefoneDois;
  }
  if ($custoArmazenagem > 0) {
    $parts[] = 'Armazenagem: ' . lot_money($custoArmazenagem);
  }
  if ($custoCarregamento > 0) {
    $parts[] = 'Carregamento: ' . lot_money($custoCarregamento);
  }
  if ($custoSos > 0) {
    $parts[] = 'SOS: ' . lot_money($custoSos);
  }
  if ($outrosLocais > 0) {
    $parts[] = 'Outros locais: ' . lot_money($outrosLocais);
  }
  if ($observacoesLocal !== '') {
    $parts[] = $observacoesLocal;
  }

  return implode("\n", $parts);
}

function lot_is_structured_logistic_line(string $line): bool {
  $line = trim($line);
  if ($line === '') {
    return false;
  }

  if (preg_match('/^Impostos frete:\s+[-R$\s0-9.,]+$/u', $line)) {
    return true;
  }
  if (preg_match('/^Outros frete:\s+[-R$\s0-9.,]+$/u', $line)) {
    return true;
  }
  if (preg_match('/^Tag frete:\s+.+$/u', $line)) {
    return true;
  }

  return false;
}

function lot_extract_freight_tags(string $text): array {
  $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
  $items = [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '') {
      continue;
    }
    if (!preg_match('/^Tag frete:\s+(.+)$/u', $line, $matches)) {
      continue;
    }
    $name = lot_upper_text(trim((string)($matches[1] ?? '')));
    if ($name === '') {
      continue;
    }
    $slug = lot_normalize_slug($name);
    if ($slug === '') {
      continue;
    }
    $items[$slug] = [
      'nome' => $name,
      'slug' => $slug,
    ];
  }
  return array_values($items);
}

function lot_strip_structured_logistic_lines(string $text): string {
  $lines = preg_split('/\r\n|\r|\n/', trim($text)) ?: [];
  $kept = [];
  foreach ($lines as $line) {
    $line = trim((string)$line);
    if ($line === '' || lot_is_structured_logistic_line($line)) {
      continue;
    }
    $kept[] = $line;
  }
  return implode("\n", $kept);
}

function lot_build_logistic_notes(float $impostosFrete, float $outrosFrete, array $freightTags, string $observacoes): string {
  $parts = [];
  $observacoes = lot_strip_structured_logistic_lines(trim($observacoes));
  $normalizedTags = [];
  foreach ($freightTags as $tag) {
    $name = lot_upper_text(is_array($tag) ? (string)($tag['nome'] ?? '') : (string)$tag);
    if ($name === '') {
      continue;
    }
    $slug = lot_normalize_slug($name);
    if ($slug === '') {
      continue;
    }
    $normalizedTags[$slug] = $name;
  }

  if ($impostosFrete > 0) {
    $parts[] = 'Impostos frete: ' . lot_money($impostosFrete);
  }
  if ($outrosFrete > 0) {
    $parts[] = 'Outros frete: ' . lot_money($outrosFrete);
  }
  foreach ($normalizedTags as $tagName) {
    $parts[] = 'Tag frete: ' . $tagName;
  }
  if ($observacoes !== '') {
    $parts[] = $observacoes;
  }

  return implode("\n", $parts);
}

function lot_upper_text(string $value): string {
  return mb_strtoupper(trim($value), 'UTF-8');
}

function lot_build_update_payload(array $loadedLote): array {
  return [
    'fornecedor_id' => (int)($loadedLote['fornecedorId'] ?? 0),
    'numero_processo' => (string)($loadedLote['numeroProcesso'] ?? ''),
    'titulo_lote' => (string)($loadedLote['tituloLote'] ?? ''),
    'descricao_resumida' => (string)($loadedLote['descricaoResumida'] ?? ''),
    'descricao_operacional' => (string)($loadedLote['descricaoOperacional'] ?? ''),
    'tipo_macro_lote' => (string)($loadedLote['tipoMacroLote'] ?? ''),
    'data_compra' => (string)($loadedLote['dataCompra'] ?? ''),
    'status_macro' => (string)($loadedLote['statusMacro'] ?? 'em_transito'),
    'etapa_timeline' => (string)($loadedLote['etapaTimeline'] ?? 'compra'),
    'observacoes_gerais' => (string)($loadedLote['observacoesGerais'] ?? ''),
    'valor_original_lote' => (float)($loadedLote['valorOriginalLote'] ?? 0),
    'valor_depreciado' => (float)($loadedLote['valorDepreciado'] ?? 0),
    'valor_pago_compra' => (float)($loadedLote['valorPagoCompra'] ?? 0),
    'despesas_local' => (float)($loadedLote['despesasLocal'] ?? 0),
    'valor_frete' => (float)($loadedLote['valorFrete'] ?? 0),
    'valor_documento_transporte' => (float)($loadedLote['valorDocumentoTransporte'] ?? 0),
    'outros_custos' => (float)($loadedLote['outrosCustos'] ?? 0),
    'custo_total' => (float)($loadedLote['custoTotal'] ?? 0),
    'nome_local' => (string)($loadedLote['nomeLocal'] ?? ''),
    'nome_contato' => (string)($loadedLote['nomeContato'] ?? ''),
    'telefone' => (string)($loadedLote['telefone'] ?? ''),
    'email' => (string)($loadedLote['email'] ?? ''),
    'endereco' => (string)($loadedLote['endereco'] ?? ''),
    'cidade' => (string)($loadedLote['cidade'] ?? ''),
    'estado' => (string)($loadedLote['estado'] ?? ''),
    'observacoes_local' => (string)($loadedLote['observacoesLocal'] ?? ''),
    'tipo_transporte' => (string)($loadedLote['tipoTransporte'] ?? 'sem_frete'),
    'motorista_id' => (int)($loadedLote['motoristaId'] ?? 0),
    'transportadora_id' => (int)($loadedLote['transportadoraId'] ?? 0),
    'veiculo_referencia' => (string)($loadedLote['veiculoReferencia'] ?? ''),
    'agenciador' => (string)($loadedLote['agenciador'] ?? ''),
    'documento_transporte' => (string)($loadedLote['documentoTransporte'] ?? ''),
    'data_contratacao' => (string)($loadedLote['dataContratacao'] ?? ''),
    'data_agendamento' => (string)($loadedLote['dataAgendamento'] ?? ''),
    'data_coleta' => (string)($loadedLote['dataColeta'] ?? ''),
    'data_entrega' => (string)($loadedLote['dataEntrega'] ?? ''),
    'observacoes_logisticas' => (string)($loadedLote['observacoesLogisticas'] ?? ''),
  ];
}

function lot_item_status_label(array $item): string {
  $disponivel = (float)($item['quantidadeDisponivel'] ?? $item['quantidade_disponivel'] ?? 0);
  $baixada = (float)($item['quantidadeBaixada'] ?? $item['quantidade_baixada'] ?? 0);
  $vendida = (float)($item['quantidadeVendida'] ?? $item['quantidade_vendida'] ?? 0);

  if ($disponivel <= 0) {
    return $vendida > 0 ? 'Vendido / encerrado' : 'Baixado / encerrado';
  }

  if ($baixada > 0 || $vendida > 0) {
    return 'Parcial';
  }

  return 'Ativo';
}

function lot_item_status_class(array $item): string {
  $disponivel = (float)($item['quantidadeDisponivel'] ?? $item['quantidade_disponivel'] ?? 0);
  $baixada = (float)($item['quantidadeBaixada'] ?? $item['quantidade_baixada'] ?? 0);
  $vendida = (float)($item['quantidadeVendida'] ?? $item['quantidade_vendida'] ?? 0);

  if ($disponivel <= 0) {
    return 'is-resolved';
  }

  if ($baixada > 0 || $vendida > 0) {
    return 'is-partial';
  }

  return 'is-active';
}

function lot_item_disponivel_total(array $item): float {
  return max(0, (float)($item['quantidadeDisponivel'] ?? 0));
}

function lot_sale_reference(array $movimentacao, array $payload): string {
  $saleRef = trim((string)($payload['sale_id'] ?? ''));
  if ($saleRef !== '') {
    return $saleRef;
  }

  $movimentacaoId = (int)($movimentacao['id'] ?? 0);
  return $movimentacaoId > 0 ? 'mov:' . $movimentacaoId : '';
}

function lot_sale_delta_value(array $movimentacao): float {
  if (!is_array($movimentacao)) {
    return 0.0;
  }

  $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
  return match ((string)($movimentacao['tipoEvento'] ?? '')) {
    'item_venda' => (float)($payload['valor_total_vendido'] ?? 0),
    'item_venda_devolucao' => -1 * (float)($payload['valor_total_devolvido'] ?? 0),
    default => 0.0,
  };
}

function lot_qty_compact(float $value): string {
  $formatted = number_format($value, 3, ',', '.');
  $formatted = rtrim(rtrim($formatted, '0'), ',');
  return $formatted !== '' ? $formatted : '0';
}

function lot_datetime_activity(?string $eventDate, ?string $createdAt = null): string {
  $eventDate = trim((string)($eventDate ?? ''));
  $createdAt = trim((string)($createdAt ?? ''));

  $datePart = $eventDate !== '' ? substr($eventDate, 0, 10) : substr($createdAt, 0, 10);
  $timeSource = $createdAt !== '' ? $createdAt : $eventDate;
  $timeTs = $timeSource !== '' ? strtotime($timeSource) : false;
  $dateTs = $datePart !== '' ? strtotime($datePart . ' 00:00:00') : false;

  if ($dateTs === false && $timeTs === false) {
    return 'Data não informada';
  }

  $dateLabel = $dateTs !== false ? date('d/m/Y', $dateTs) : date('d/m/Y', $timeTs ?: time());
  $timeLabel = $timeTs !== false ? date('H:i', $timeTs) : '--:--';
  return $dateLabel . ' • ' . $timeLabel;
}

function lot_movement_summary(array $movimentacao): string {
  $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
  $descricao = lot_text_or_default((string)($movimentacao['descricaoEvento'] ?? ''), 'Evento sem descrição');
  $item = trim((string)($payload['descricao_item'] ?? ''));
  $tipo = trim((string)($payload['tipo_controle_item'] ?? ''));
  $qtyLabel = $tipo !== '' ? $tipo : 'Und';

  return match ((string)($movimentacao['tipoEvento'] ?? '')) {
    'item_cadastrado' => 'Item cadastrado: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_total'] ?? 0)),
    'item_editado' => 'Item editado: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_total'] ?? 0)),
    'item_baixa_manual' => 'Baixa manual: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_baixada'] ?? 0)),
    'item_baixa_revertida' => 'Reversão de baixa: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_revertida'] ?? 0)),
    'item_venda' => 'Venda: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_vendida'] ?? 0)),
    'item_venda_devolucao' => 'Devolução: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_devolvida'] ?? 0)),
    'lote_devolucao_parcial' => 'Devolução parcial do lote: ' . lot_money((float)($payload['cancelamento_estorno'] ?? 0)),
    'lote_cancelado' => 'Cancelamento do lote: ' . lot_text_or_default((string)($payload['cancelamento_motivo'] ?? ''), 'Motivo não informado'),
    'lote_baixa_total_item' => 'Baixa total: ' . ($item !== '' ? $item : 'Item') . ' • ' . $qtyLabel . ' ' . lot_qty_compact((float)($payload['quantidade_baixada'] ?? 0)),
    'anexo_adicionado' => 'Anexo adicionado: ' . lot_text_or_default((string)($payload['grupo_titulo'] ?? ''), 'Anexos') . ' • ' . lot_qty_compact((float)($payload['quantidade_arquivos'] ?? 0)) . ' arquivo(s)',
    'anexo_removido' => 'Anexo removido: ' . lot_text_or_default((string)($payload['nome_arquivo'] ?? ''), 'Arquivo'),
    default => $descricao,
  };
}

function lot_occurrence_report_payload(array $lote, array $fornecedor, array $movimentacao): array {
  $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
  $tipo = (string)($movimentacao['tipoEvento'] ?? '') === 'lote_cancelado' ? 'Cancelamento total' : 'Devolução parcial';
  $titulo = trim((string)($lote['tituloLote'] ?? ''));
  $resumo = trim((string)($lote['descricaoResumida'] ?? ''));
  $processo = lot_text_or_default((string)($lote['numeroProcesso'] ?? ''), '-');
  $sinistro = trim((string)($lote['numeroSinistro'] ?? ''));
  if ($sinistro === '') {
    $sinistro = lot_extract_labeled_line((string)($lote['observacoesGerais'] ?? ''), 'Sinistro:');
  }

  return [
    'title' => $tipo,
    'metaTitle' => $titulo !== '' ? $titulo : 'Lote sem título',
    'metaHint' => 'Para salvar: Cmd+P (Mac) / Ctrl+P (Windows) → Destino: Salvar como PDF',
    'brandSub' => 'Relatório da ocorrência do lote',
    'reportTitle' => $tipo,
    'sections' => [
      [
        'title' => 'Identificação do lote',
        'items' => [
          ['label' => 'Lote', 'value' => $resumo !== '' ? $resumo : 'Resumo não informado'],
          ['label' => 'Seguradora', 'value' => (string)($fornecedor['nome'] ?? $fornecedor['razaoSocial'] ?? 'Fornecedor não identificado')],
          ['label' => 'Processo', 'value' => $processo],
          ['label' => 'Sinistro', 'value' => $sinistro !== '' ? $sinistro : 'Não informado'],
        ],
      ],
      [
        'title' => 'Dados da ocorrência',
        'items' => [
          ['label' => 'Tipo', 'value' => $tipo],
          ...(((string)($movimentacao['tipoEvento'] ?? '') === 'lote_cancelado') ? [[
            'label' => 'Status do cancelamento',
            'value' => lot_cancel_status_label(lot_cancel_status_from_payload($payload, 'lote_cancelado')),
          ]] : []),
          ['label' => 'Data da ocorrência', 'value' => lot_date((string)($payload['cancelamento_data'] ?? ($movimentacao['dataEvento'] ?? '')))],
          ['label' => 'Motivo', 'value' => lot_text_or_default((string)($payload['cancelamento_motivo'] ?? ''), 'Não informado')],
          ['label' => 'Valor da devolução', 'value' => lot_money((float)($payload['cancelamento_estorno'] ?? 0))],
          ['label' => 'Previsão de recebimento', 'value' => trim((string)($payload['cancelamento_refund_due_date'] ?? '')) !== '' ? lot_date((string)$payload['cancelamento_refund_due_date']) : 'Não definida'],
        ],
      ],
      [
        'title' => 'Relato e financeiro',
        'items' => [
          ['label' => 'Relato', 'value' => lot_text_or_default((string)($payload['cancelamento_relato'] ?? ''), 'Não informado')],
          ['label' => 'Observação financeira', 'value' => lot_text_or_default((string)($payload['cancelamento_financeiro'] ?? ''), 'Não informado')],
        ],
      ],
    ],
    'footnote' => 'Documento gerado automaticamente pelo Sistema Visa Remoções.',
  ];
}

function lot_refund_breakdown(array $movimentacoes): array {
  $total = 0.0;
  $partial = 0.0;
  $cancel = 0.0;

  foreach ($movimentacoes as $movimentacao) {
    if (!is_array($movimentacao)) {
      continue;
    }
    $tipoEvento = (string)($movimentacao['tipoEvento'] ?? '');
    if (!in_array($tipoEvento, ['lote_cancelado', 'lote_devolucao_parcial'], true)) {
      continue;
    }
    $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
    $valor = (float)($payload['cancelamento_estorno'] ?? 0);
    $total += $valor;
    if ($tipoEvento === 'lote_cancelado') {
      $cancel += $valor;
    } else {
      $partial += $valor;
    }
  }

  return [
    'total' => $total,
    'partial' => $partial,
    'cancel' => $cancel,
  ];
}

function lot_cancel_status_options(): array {
  return [
    'cancelado_sem_pagamento' => 'Sem pagamento',
    'cancelado_aguardando_estorno' => 'Aguardando estorno',
    'cancelado_estornado' => 'Estornado',
  ];
}

function lot_cancel_status_label(string $status): string {
  $options = lot_cancel_status_options();
  return $options[$status] ?? 'Status não definido';
}

function lot_cancel_status_rank(string $status): int {
  return match ($status) {
    'cancelado_sem_pagamento' => 1,
    'cancelado_aguardando_estorno' => 2,
    'cancelado_estornado' => 3,
    default => 99,
  };
}

function lot_cancel_status_from_payload(array $payload, string $tipoEvento = 'lote_cancelado'): string {
  if ($tipoEvento !== 'lote_cancelado') {
    return '';
  }

  $posted = trim((string)($payload['cancelamento_status'] ?? ''));
  if ($posted !== '' && array_key_exists($posted, lot_cancel_status_options())) {
    return $posted;
  }

  $valorEstorno = (float)($payload['cancelamento_estorno'] ?? 0);
  if ($valorEstorno <= 0) {
    return 'cancelado_sem_pagamento';
  }

  return 'cancelado_aguardando_estorno';
}

function lot_cancel_summary_from_movimentacoes(array $movimentacoes, string $statusMacro = ''): array {
  if ($statusMacro !== 'cancelado') {
    return [
      'key' => '',
      'label' => '',
      'rank' => 99,
      'date' => '',
      'estorno' => 0.0,
    ];
  }

  $selected = null;
  foreach ($movimentacoes as $movimentacao) {
    if (!is_array($movimentacao)) {
      continue;
    }
    if ((string)($movimentacao['tipoEvento'] ?? '') !== 'lote_cancelado') {
      continue;
    }
    $selected = $movimentacao;
  }

  if (!is_array($selected)) {
    return [
      'key' => 'cancelado_sem_pagamento',
      'label' => lot_cancel_status_label('cancelado_sem_pagamento'),
      'rank' => lot_cancel_status_rank('cancelado_sem_pagamento'),
      'date' => '',
      'estorno' => 0.0,
    ];
  }

  $payload = is_array($selected['payloadEstrutural'] ?? null) ? (array)$selected['payloadEstrutural'] : [];
  $status = lot_cancel_status_from_payload($payload, 'lote_cancelado');

  return [
    'key' => $status,
    'label' => lot_cancel_status_label($status),
    'rank' => lot_cancel_status_rank($status),
    'date' => trim((string)($payload['cancelamento_data'] ?? ($selected['dataEvento'] ?? ''))),
    'estorno' => (float)($payload['cancelamento_estorno'] ?? 0),
  ];
}

function lot_timeline_stages(): array {
  return [
    ['key' => 'compra', 'label' => 'Compra', 'icon' => 'fa-solid fa-file-invoice-dollar'],
    ['key' => 'autorizacao_coleta', 'label' => 'Autorização de coleta', 'icon' => 'fa-solid fa-file-signature'],
    ['key' => 'liberacao_coleta', 'label' => 'Liberação de coleta', 'icon' => 'fa-solid fa-unlock-keyhole'],
    ['key' => 'coleta', 'label' => 'Coleta', 'icon' => 'fa-solid fa-hand-holding-hand'],
    ['key' => 'entrega', 'label' => 'Entrega', 'icon' => 'fa-solid fa-truck-ramp-box'],
    ['key' => 'finalizado', 'label' => 'Finalizado', 'icon' => 'fa-solid fa-circle-check'],
  ];
}

function lot_timeline_stage_form_config(string $stageKey): array {
  return match ($stageKey) {
    'autorizacao_coleta' => [
      'title' => 'Autorização de coleta',
      'hint' => 'Registre as cobranças e retornos da seguradora até o recebimento do documento de autorização.',
      'date_label' => 'Data do contato',
      'contact_label' => 'Contato',
      'contact_required' => true,
      'status_label' => 'Status',
      'statuses' => [
        ['value' => 'aguardando', 'label' => 'Aguardando'],
        ['value' => 'autorizado', 'label' => 'Autorizado'],
      ],
      'final_status' => 'autorizado',
      'final_status_label' => 'Autorizado',
      'report_label' => 'Relato',
      'report_placeholder' => 'Descreva a cobrança feita, o retorno recebido ou qualquer observação desta autorização.',
      'force_without_freight' => false,
    ],
    'liberacao_coleta' => [
      'title' => 'Liberação de coleta',
      'hint' => 'Registre as tratativas com o local de armazenagem até a confirmação da liberação da retirada.',
      'date_label' => 'Data do contato',
      'contact_label' => 'Contato',
      'contact_required' => true,
      'status_label' => 'Status',
      'statuses' => [
        ['value' => 'aguardando', 'label' => 'Aguardando'],
        ['value' => 'liberado', 'label' => 'Liberado'],
      ],
      'final_status' => 'liberado',
      'final_status_label' => 'Liberado',
      'report_label' => 'Relato',
      'report_placeholder' => 'Descreva o contato com o local, a tratativa em andamento ou a confirmação da liberação.',
      'force_without_freight' => false,
    ],
    'coleta' => [
      'title' => 'Coleta',
      'hint' => 'Registre a busca por frete, as observações operacionais e a confirmação da coleta.',
      'date_label' => 'Data do registro',
      'contact_label' => '',
      'contact_required' => false,
      'status_label' => 'Status',
      'statuses' => [
        ['value' => 'aguardando', 'label' => 'Aguardando'],
        ['value' => 'coletado', 'label' => 'Coletado'],
      ],
      'final_status' => 'coletado',
      'final_status_label' => 'Coletado',
      'report_label' => 'Relato',
      'report_placeholder' => 'Descreva a busca por frete, a dificuldade encontrada ou a confirmação da coleta.',
      'force_without_freight' => true,
    ],
    'entrega' => [
      'title' => 'Entrega',
      'hint' => 'Registre ocorrências de transporte, atrasos ou a confirmação da entrega do lote.',
      'date_label' => 'Data do registro',
      'contact_label' => '',
      'contact_required' => false,
      'status_label' => 'Status',
      'statuses' => [
        ['value' => 'aguardando', 'label' => 'Aguardando'],
        ['value' => 'entregue', 'label' => 'Entregue'],
      ],
      'final_status' => 'entregue',
      'final_status_label' => 'Entregue',
      'report_label' => 'Relato',
      'report_placeholder' => 'Descreva o andamento da entrega, atrasos ou a confirmação do recebimento.',
      'force_without_freight' => false,
    ],
    default => [
      'title' => lot_etapa_label($stageKey),
      'hint' => 'Registre o andamento operacional desta etapa.',
      'date_label' => 'Data do registro',
      'contact_label' => 'Contato',
      'contact_required' => false,
      'status_label' => 'Status',
      'statuses' => [
        ['value' => 'aguardando', 'label' => 'Aguardando'],
      ],
      'final_status' => '',
      'final_status_label' => '',
      'report_label' => 'Relato',
      'report_placeholder' => 'Descreva o andamento desta etapa.',
      'force_without_freight' => false,
    ],
  };
}

function lot_timeline_previous_stage(string $stageKey): ?string {
  return match ($stageKey) {
    'autorizacao_coleta' => 'compra',
    'liberacao_coleta' => 'autorizacao_coleta',
    'coleta' => 'liberacao_coleta',
    'entrega' => 'coleta',
    'finalizado' => 'entrega',
    default => null,
  };
}

function lot_timeline_stage_records(array $lote, string $stageKey, int $currentUserId = 0, string $currentUserName = ''): array {
  $movimentacoes = is_array($lote['movimentacoes'] ?? null) ? (array)$lote['movimentacoes'] : [];
  $records = [];

  foreach (array_reverse($movimentacoes) as $movimentacao) {
    if (!is_array($movimentacao)) {
      continue;
    }
    $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
    if ((string)($payload['timeline_stage'] ?? '') !== $stageKey) {
      continue;
    }
    if ((string)($payload['timeline_action'] ?? '') !== 'registro') {
      continue;
    }

    $ownerId = (int)($payload['timeline_owner_id'] ?? 0);
    $ownerName = trim((string)($payload['timeline_owner_name'] ?? $movimentacao['responsavel'] ?? ''));
    $canEdit = ($ownerId > 0 && $currentUserId > 0 && $ownerId === $currentUserId)
      || ($ownerId <= 0 && $currentUserName !== '' && strcasecmp($ownerName, $currentUserName) === 0);

    $records[] = [
      'id' => (int)($movimentacao['id'] ?? 0),
      'date' => lot_date((string)($payload['timeline_date'] ?? $movimentacao['dataEvento'] ?? '')),
      'contact' => trim((string)($payload['timeline_contact'] ?? '')),
      'report' => trim((string)($payload['timeline_report'] ?? $movimentacao['descricaoEvento'] ?? '')),
      'responsavel' => trim((string)($movimentacao['responsavel'] ?? '')),
      'rawDate' => trim((string)($payload['timeline_date'] ?? '')),
      'rawStatus' => trim((string)($payload['timeline_status'] ?? '')),
      'rawContact' => trim((string)($payload['timeline_contact'] ?? '')),
      'rawReport' => trim((string)($payload['timeline_report'] ?? '')),
      'expectedDelivery' => trim((string)($payload['timeline_expected_delivery'] ?? '')),
      'canEdit' => $canEdit,
    ];
  }

  return $records;
}

function lot_timeline_stage_reference_ts(array $lote, string $stageKey): ?int {
  if ($stageKey === 'entrega') {
    $plannedDelivery = trim((string)($lote['dataEntrega'] ?? ''));
    if ($plannedDelivery !== '') {
      $ts = strtotime($plannedDelivery);
      if ($ts !== false) {
        return $ts;
      }
    }
  }

  $referenceStage = lot_timeline_previous_stage($stageKey);
  $movimentacoes = is_array($lote['movimentacoes'] ?? null) ? (array)$lote['movimentacoes'] : [];
  foreach (array_reverse($movimentacoes) as $movimentacao) {
    if (!is_array($movimentacao)) {
      continue;
    }
    $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
    if ((string)($payload['timeline_stage'] ?? '') !== (string)$referenceStage) {
      continue;
    }
    if ((string)($payload['timeline_action'] ?? '') !== 'conclusao') {
      continue;
    }
    $eventDate = trim((string)($movimentacao['dataEvento'] ?? ''));
    if ($eventDate !== '') {
      $ts = strtotime($eventDate);
      if ($ts !== false) {
        return $ts;
      }
    }
  }

  if ($referenceStage === 'compra' || $referenceStage === null) {
    $fallback = trim((string)($lote['dataCompra'] ?? ''));
    if ($fallback !== '') {
      $ts = strtotime($fallback);
      if ($ts !== false) {
        return $ts;
      }
    }
  }

  return null;
}

function lot_timeline_delay_state(array $lote, string $stageKey): string {
  if ($stageKey === 'finalizado' || (string)($lote['statusMacro'] ?? '') === 'finalizado') {
    return 'normal';
  }

  $referenceTs = lot_timeline_stage_reference_ts($lote, $stageKey);
  if ($referenceTs === null) {
    return 'normal';
  }

  $days = (int)floor((time() - $referenceTs) / 86400);
  if ($days >= 3) {
    return 'critical';
  }
  if ($days >= 1) {
    return 'attention';
  }

  return 'normal';
}

function lot_primary_phone(array $cadastro): string {
  foreach (['whatsapp', 'celular', 'telefone', 'telefoneFixo', 'telefoneSecundario'] as $field) {
    $value = trim((string)($cadastro[$field] ?? ''));
    if ($value !== '') {
      return $value;
    }
  }
  return 'Não informado';
}

function lot_cadastro_documento(array $cadastro): string {
  $documento = trim((string)($cadastro['documento'] ?? ''));
  if ($documento !== '') {
    return $documento;
  }

  $motoristaDetalhes = is_array($cadastro['motoristaDetalhes'] ?? null) ? (array)$cadastro['motoristaDetalhes'] : [];
  foreach (['cpf', 'cnpj'] as $field) {
    $value = trim((string)($motoristaDetalhes[$field] ?? ''));
    if ($value !== '') {
      return $value;
    }
  }

  return '';
}

function lot_cadastro_tipo_slugs(array $cadastro): array {
  $tipos = is_array($cadastro['tipos'] ?? null) ? (array)$cadastro['tipos'] : [];
  $slugs = [];
  foreach ($tipos as $tipo) {
    if (!is_array($tipo)) {
      continue;
    }
    $slug = trim((string)($tipo['slug'] ?? ''));
    if ($slug !== '') {
      $slugs[] = strtolower($slug);
    }
  }
  return array_values(array_unique($slugs));
}

function lot_cadastro_tipo_labels(array $cadastro): array {
  $tipos = is_array($cadastro['tipos'] ?? null) ? (array)$cadastro['tipos'] : [];
  $labels = [];
  foreach ($tipos as $tipo) {
    if (!is_array($tipo)) {
      continue;
    }
    $nome = trim((string)($tipo['nome'] ?? ''));
    if ($nome !== '') {
      $labels[] = $nome;
    }
  }
  return array_values(array_unique($labels));
}

function lot_is_freight_cadastro(array $cadastro): bool {
  $slugs = lot_cadastro_tipo_slugs($cadastro);
  return in_array('motorista', $slugs, true) || in_array('transportadora', $slugs, true);
}

function lot_resolve_freight_kind(array $cadastro): string {
  $slugs = lot_cadastro_tipo_slugs($cadastro);
  if (in_array('transportadora', $slugs, true)) {
    return 'transportadora';
  }
  return 'motorista';
}

function lot_cadastro_display_name(array $cadastro): string {
  $nome = trim((string)($cadastro['nome'] ?? ''));
  if ($nome !== '') {
    return $nome;
  }
  $razao = trim((string)($cadastro['razaoSocial'] ?? ''));
  return $razao !== '' ? $razao : 'Cadastro sem nome';
}

function lot_find_freight_suggestions(array $lote, array $cadastros, CadastroRepository $cadastroRepo, int $companyId = 1): array {
  $loteCidade = lot_normalize_search((string)($lote['cidade'] ?? ''));
  $loteEstado = lot_normalize_state_uf((string)($lote['estado'] ?? ''));
  $freightTags = lot_extract_freight_tags((string)($lote['observacoesLogisticas'] ?? ''));
  $freightTagNames = array_values(array_filter(array_map(
    static fn (array $tag): string => lot_normalize_search((string)($tag['nome'] ?? '')),
    $freightTags
  )));
  $matches = [];

  foreach ($cadastros as $cadastro) {
    if (!is_array($cadastro) || !lot_is_freight_cadastro($cadastro)) {
      continue;
    }

    $cadastroId = (int)($cadastro['id'] ?? 0);
    if ($cadastroId <= 0) {
      continue;
    }

    $cadastroTags = $cadastroRepo->getTags($cadastroId, $companyId);
    $cadCidade = lot_normalize_search((string)($cadastro['cidade'] ?? ''));
    $cadEstado = lot_normalize_state_uf((string)($cadastro['estado'] ?? ''));
    $cadastroTagNames = [];
    foreach ($cadastroTags as $tag) {
      $tagName = '';
      if (is_array($tag)) {
        $tagName = (string)($tag['nome'] ?? $tag['name'] ?? '');
      } else {
        $tagName = (string)$tag;
      }
      $tagName = trim($tagName);
      if ($tagName === '') {
        continue;
      }
      $cadastroTagNames[] = $tagName;
    }

    $locationScore = 0;
    foreach ($cadastroTagNames as $tagName) {
      $tagSearch = lot_normalize_search($tagName);
      $tagState = lot_normalize_state_uf($tagName);
      if ($loteCidade !== '' && $tagSearch !== '' && $tagSearch === $loteCidade) {
        $locationScore = max($locationScore, 5);
      }
      if ($loteEstado !== '' && $tagState !== '' && $tagState === $loteEstado) {
        $locationScore = max($locationScore, 3);
      }
    }

    $veiculos = is_array($cadastro['veiculos'] ?? null) ? (array)$cadastro['veiculos'] : [];
    $modeloScore = 0;
    $carroceriaScore = 0;
    foreach ($veiculos as $veiculo) {
      if (!is_array($veiculo)) {
        continue;
      }
      $modelo = lot_normalize_search((string)($veiculo['modelo'] ?? ''));
      $carroceria = lot_normalize_search((string)($veiculo['tipoCarroceria'] ?? $veiculo['tipo_carroceria'] ?? ''));
      if ($modelo !== '' && in_array($modelo, $freightTagNames, true)) {
        $modeloScore = max($modeloScore, 3);
      }
      if ($carroceria !== '' && in_array($carroceria, $freightTagNames, true)) {
        $carroceriaScore = max($carroceriaScore, 3);
      }
    }

    $score = 0;
    $hasPrimaryLocationMatch = $locationScore > 0;

    if ($loteCidade !== '' && $cadCidade !== '' && $loteCidade === $cadCidade) {
      $score += 2;
      $hasPrimaryLocationMatch = true;
    }
    if ($loteEstado !== '' && $cadEstado !== '' && $loteEstado === $cadEstado) {
      $score += 1;
      $hasPrimaryLocationMatch = true;
    }
    $score += $locationScore + $modeloScore + $carroceriaScore;

    if (!$hasPrimaryLocationMatch || $score <= 0) {
      continue;
    }

    $matches[] = [
      'id' => $cadastroId,
      'nome' => lot_cadastro_display_name($cadastro),
      'tipo' => implode(' / ', lot_cadastro_tipo_labels($cadastro)),
      'telefone' => lot_primary_phone($cadastro),
      'cidade' => lot_text_or_default((string)($cadastro['cidade'] ?? ''), 'Não informada'),
      'estado' => trim((string)($cadastro['estado'] ?? '')),
      'score' => $score,
      'kind' => lot_resolve_freight_kind($cadastro),
    ];
  }

  usort($matches, static function (array $left, array $right): int {
    $scoreCompare = ($right['score'] ?? 0) <=> ($left['score'] ?? 0);
    if ($scoreCompare !== 0) {
      return $scoreCompare;
    }
    return strcasecmp((string)($left['nome'] ?? ''), (string)($right['nome'] ?? ''));
  });

  return array_slice($matches, 0, 8);
}

function lot_freight_card_meta(?array $cadastro): array {
  if (!is_array($cadastro)) {
    return [];
  }

  $tipo = lot_resolve_freight_kind($cadastro);
  $motoristaDetalhes = is_array($cadastro['motoristaDetalhes'] ?? null) ? (array)$cadastro['motoristaDetalhes'] : [];
  $veiculos = is_array($cadastro['veiculos'] ?? null) ? (array)$cadastro['veiculos'] : [];
  $veiculo = is_array($veiculos[0] ?? null) ? (array)$veiculos[0] : [];
  $cidade = trim((string)($cadastro['cidade'] ?? ''));
  $estado = trim((string)($cadastro['estado'] ?? ''));
  $documento = lot_cadastro_documento($cadastro);
  $veiculoNome = trim(implode(' ', array_filter([
    trim((string)($veiculo['marca'] ?? '')),
    trim((string)($veiculo['modelo'] ?? '')),
  ])));
  if ($veiculoNome === '') {
    $veiculoNome = trim((string)($veiculo['modelo'] ?? ''));
  }

  return [
    'tipo' => $tipo === 'transportadora' ? 'Transportadora' : 'Motorista',
    'nome' => lot_cadastro_display_name($cadastro),
    'telefone' => lot_primary_phone($cadastro),
    'documento' => $documento,
    'documentoLabel' => $tipo === 'transportadora' ? 'CNPJ' : 'CPF',
    'cidadeEstado' => trim($cidade . ($estado !== '' ? ' / ' . $estado : '')),
    'cnh' => trim((string)($motoristaDetalhes['cnh'] ?? '')),
    'veiculo' => $veiculoNome,
    'placa' => trim((string)($veiculo['placa'] ?? '')),
    'kind' => $tipo,
    'id' => (int)($cadastro['id'] ?? 0),
  ];
}

function lot_find_compatible_clients(array $loteTags, array $clientes, CadastroRepository $cadastroRepo, int $companyId = 1): array {
  if ($loteTags === []) {
    return [];
  }

  $matches = [];
  foreach ($clientes as $cliente) {
    if (!is_array($cliente)) {
      continue;
    }

    $clienteId = (int)($cliente['id'] ?? 0);
    if ($clienteId <= 0) {
      continue;
    }

    $clienteTags = $cadastroRepo->getTags($clienteId, $companyId);
    $tagMatches = $cadastroRepo->findMatchingTags($loteTags, $clienteTags);
    if ($tagMatches === []) {
      continue;
    }

    $tipos = $cadastroRepo->getTipos($clienteId, $companyId);
    $tipoLabels = array_values(array_filter(array_map(static function ($tipo): string {
      if (!is_array($tipo)) {
        return '';
      }
      return trim((string)($tipo['nome'] ?? ''));
    }, $tipos)));

    $nome = trim((string)($cliente['nome'] ?? $cliente['razaoSocial'] ?? ''));
    $matches[] = [
      'id' => $clienteId,
      'nome' => $nome !== '' ? $nome : 'Cadastro sem nome',
      'telefone' => lot_primary_phone($cliente),
      'cidade' => lot_text_or_default((string)($cliente['cidade'] ?? ''), 'Não informada'),
      'tipo' => $tipoLabels !== [] ? implode(' / ', $tipoLabels) : 'Sem tipo',
      'matchCount' => count($tagMatches),
    ];
  }

  usort($matches, static function (array $left, array $right): int {
    $countCompare = ($right['matchCount'] ?? 0) <=> ($left['matchCount'] ?? 0);
    if ($countCompare !== 0) {
      return $countCompare;
    }
    return strcasecmp((string)($left['nome'] ?? ''), (string)($right['nome'] ?? ''));
  });

  return $matches;
}

function lot_render_dashboard_card(array $lote): void {
  $desc = trim((string)($lote['descricaoResumida'] ?? ''));
  if ($desc === '') {
    $desc = trim((string)($lote['descricaoOperacional'] ?? ''));
  }
  if ($desc === '') {
    $desc = 'Sem descrição resumida';
  }

  $searchSource = implode(' ', array_filter([
    (string)($lote['numeroProcesso'] ?? ''),
    (string)($lote['tituloLote'] ?? ''),
    (string)($lote['descricaoResumida'] ?? ''),
    (string)($lote['descricaoOperacional'] ?? ''),
    (string)($lote['fornecedorNome'] ?? ''),
    (string)($lote['cidadeEstado'] ?? ''),
  ]));
  ?>
  <article class="lot-board-card<?= ((string)($lote['cancelamentoStatus'] ?? '') === 'cancelado_estornado') ? ' is-settled-cancel' : '' ?>" data-lot-card data-lot-search="<?= h(lot_normalize_search($searchSource)) ?>">
    <div class="lot-board-card__top">
      <div class="lot-board-card__chips">
        <span class="lot-status-chip <?= h(lot_status_chip_class((string)($lote['statusMacro'] ?? ''))) ?>"><?= h((string)($lote['statusLabel'] ?? '')) ?></span>
        <span class="lot-priority-chip"><?= h((string)($lote['priorityLabel'] ?? '')) ?></span>
        <span class="lot-priority-chip"><?= h((string)($lote['purchasePaymentLabel'] ?? 'Pagamento pendente')) ?></span>
        <?php if (trim((string)($lote['cancelamentoStatusLabel'] ?? '')) !== ''): ?>
          <span class="lot-priority-chip"><?= h((string)($lote['cancelamentoStatusLabel'] ?? '')) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="lot-board-card__identity">
      <div class="lot-board-card__avatar">
        <img src="<?= h(app_url('/app/static/img/avatar-fornecedor.png')) ?>" alt="Fornecedor">
      </div>
      <div class="lot-board-card__summary">
        <div class="lot-board-card__processo"><?= h((string)($lote['numeroProcesso'] ?? '-')) ?></div>
        <h3><?= h((string)($lote['tituloLote'] ?? 'Lote sem título')) ?></h3>
        <p class="lot-board-card__supplier"><?= h((string)($lote['fornecedorNome'] ?? 'Fornecedor não identificado')) ?></p>
        <p class="lot-board-card__desc"><?= h($desc) ?></p>
      </div>
    </div>

    <div class="lot-board-card__meta" data-lot-card-extra>
      <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= h((string)($lote['cidadeEstado'] !== '' ? $lote['cidadeEstado'] : 'Local não informado')) ?></span>
      <span><i class="fa-solid fa-route" aria-hidden="true"></i><?= h((string)($lote['etapaLabel'] ?? 'Etapa não definida')) ?></span>
      <span><i class="fa-solid fa-calendar-day" aria-hidden="true"></i><?= h(lot_date((string)($lote['dataCompra'] ?? ''))) ?></span>
      <span><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i><?= h((string)($lote['purchasePaymentLabel'] ?? 'Pagamento pendente')) ?></span>
      <?php if (trim((string)($lote['cancelamentoStatusLabel'] ?? '')) !== ''): ?>
        <span><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i><?= h((string)($lote['cancelamentoStatusLabel'] ?? '')) ?></span>
      <?php endif; ?>
    </div>

    <div class="lot-board-card__numbers is-vertical" data-lot-card-extra>
      <div class="lot-board-card__number">
        <span><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>Custo total</span>
        <strong><?= h(lot_money((float)($lote['custoTotal'] ?? 0))) ?></strong>
      </div>

      <div class="lot-board-card__number">
        <span><i class="fa-solid fa-cash-register" aria-hidden="true"></i>Valor vendido atual</span>
        <?php if ((float)($lote['valorVendidoAtual'] ?? 0) > 0): ?>
          <strong><?= h(lot_money((float)($lote['valorVendidoAtual'] ?? 0))) ?></strong>
        <?php else: ?>
          <strong class="is-muted">Sem vendas registradas</strong>
        <?php endif; ?>
      </div>

      <div class="lot-board-card__number">
        <span><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>Resultado parcial</span>
        <strong class="<?= ((float)($lote['resultadoParcial'] ?? 0) < 0) ? 'is-negative' : 'is-positive' ?>">
          <?= h(lot_money((float)($lote['resultadoParcial'] ?? 0))) ?>
        </strong>
      </div>

      <div class="lot-board-card__number">
        <span><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i>Pagamento da compra</span>
        <?php if ((float)($lote['purchasePaymentOpenAmount'] ?? 0) > 0): ?>
          <strong class="is-negative"><?= h(lot_money((float)($lote['purchasePaymentOpenAmount'] ?? 0))) ?></strong>
        <?php else: ?>
          <strong><?= h((string)($lote['purchasePaymentLabel'] ?? 'Compra paga')) ?></strong>
        <?php endif; ?>
      </div>
    </div>

    <div class="lot-board-card__foot">
      <a class="fin-btn lot-board-card__open"
         href="<?= h(app_url('/app/templates/lotes.php?lote=' . (int)($lote['id'] ?? 0))) ?>"
         data-lot-toast="Abrindo o processo do lote."
         data-lot-toast-kind="info">
        <span>Abrir lote</span>
      </a>
      <button class="fin-icon-btn fin-icon-btn--sm lot-card-toggle"
              type="button"
              data-lot-card-toggle
              aria-expanded="false"
              title="Expandir dados do lote">
        <i class="fa-solid fa-chevron-up" aria-hidden="true"></i>
      </button>
    </div>
  </article>
  <?php
}

$viewLoteId = (int)($_GET['lote'] ?? 0);
$createMode = $viewLoteId <= 0 && isset($_GET['novo']) && $_GET['novo'] === '1';
$timelineFlashMessage = trim((string)($_GET['timeline_msg'] ?? ''));
$timelineFlashKind = trim((string)($_GET['timeline_kind'] ?? ''));
$selectedOpenModal = trim((string)($_SESSION['lot_open_modal'] ?? ''));
$lotCreateOldInput = $_SESSION['lot_create_old_input'] ?? [];
$lotTimelineOldInput = $_SESSION['lot_timeline_old_input'] ?? [];
unset($_SESSION['lot_open_modal']);
unset($_SESSION['lot_create_old_input']);
unset($_SESSION['lot_timeline_old_input']);

if (!is_array($lotCreateOldInput)) {
  $lotCreateOldInput = [];
}
if (!is_array($lotTimelineOldInput)) {
  $lotTimelineOldInput = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_create_submit'] ?? '')) === '1') {
  $fornecedorId = (int)($_POST['fornecedor_id'] ?? 0);
  $numeroProcesso = lot_upper_text((string)($_POST['numero_processo'] ?? ''));
  $numeroSinistro = lot_upper_text((string)($_POST['numero_sinistro'] ?? ''));
  $tituloLote = lot_upper_text((string)($_POST['titulo_lote'] ?? ''));
  $descricaoResumida = lot_upper_text((string)($_POST['descricao_resumida'] ?? ''));
  $dataCompra = trim((string)($_POST['data_compra'] ?? ''));
  $valorSalvado = (float)lot_decimal_input($_POST['valor_salvado'] ?? 0, 2);
  $valorPagoCompra = (float)lot_decimal_input($_POST['valor_pago_compra'] ?? 0, 2);
  $statusPagamentoCompra = lot_purchase_payment_normalize_status((string)($_POST['status_pagamento_compra'] ?? 'pendente'));
  $dataPagamentoCompra = trim((string)($_POST['data_pagamento_compra'] ?? ''));
  $nomeLocal = lot_upper_text((string)($_POST['nome_local'] ?? ''));
  $endereco = lot_upper_text((string)($_POST['endereco'] ?? ''));
  $cidade = lot_upper_text((string)($_POST['cidade'] ?? ''));
  $estado = lot_normalize_state_uf((string)($_POST['estado'] ?? ''));
  $nomeContato = lot_upper_text((string)($_POST['nome_contato'] ?? ''));
  $cpfCnpjLocal = trim((string)($_POST['cpf_cnpj_local'] ?? ''));
  $telefone = trim((string)($_POST['telefone'] ?? ''));
  $telefoneDois = trim((string)($_POST['telefone_2'] ?? ''));
  $email = trim((string)($_POST['email'] ?? ''));
  $custoArmazenagem = (float)lot_decimal_input($_POST['custo_armazenagem'] ?? 0, 2);
  $custoCarregamento = (float)lot_decimal_input($_POST['custo_carregamento'] ?? 0, 2);
  $custoSos = (float)lot_decimal_input($_POST['custo_sos'] ?? 0, 2);
  $outrosLocais = (float)lot_decimal_input($_POST['outros_custos'] ?? 0, 2);
  $observacoesGerais = lot_upper_text((string)($_POST['observacoes_gerais'] ?? ''));
  $despesasLocal = $custoArmazenagem + $custoCarregamento + $custoSos;

  $observacoesLocalExtras = [];
  if ($cpfCnpjLocal !== '') {
    $observacoesLocalExtras[] = 'CPF/CNPJ local: ' . $cpfCnpjLocal;
  }
  if ($telefoneDois !== '') {
    $observacoesLocalExtras[] = 'Telefone 2: ' . $telefoneDois;
  }
  if ($custoArmazenagem > 0) {
    $observacoesLocalExtras[] = 'Armazenagem: ' . lot_money($custoArmazenagem);
  }
  if ($custoCarregamento > 0) {
    $observacoesLocalExtras[] = 'Carregamento: ' . lot_money($custoCarregamento);
  }
  if ($custoSos > 0) {
    $observacoesLocalExtras[] = 'SOS: ' . lot_money($custoSos);
  }
  if ($outrosLocais > 0) {
    $observacoesLocalExtras[] = 'Outros locais: ' . lot_money($outrosLocais);
  }

  if ($numeroSinistro !== '') {
    $observacoesGerais = trim("Sinistro: {$numeroSinistro}\n" . $observacoesGerais);
  }

  $observacoesLocal = implode("\n", $observacoesLocalExtras);

  $createRedirect = static function (string $kind, string $message, array $params = []): never {
    $_SESSION['lot_open_modal'] = 'create';
    $_SESSION['lot_create_old_input'] = $_POST;
    lot_redirect_global($kind, $message, $params);
  };
  if ($fornecedorId <= 0) {
    $createRedirect('warning', 'Selecione a seguradora/fornecedor para criar o lote.');
  }
  if ($tituloLote === '') {
    $createRedirect('warning', 'Informe o título do lote para continuar.');
  }
  if ($dataCompra === '') {
    $createRedirect('warning', 'Informe a data de compra do lote.');
  }
  if ($statusPagamentoCompra === 'pago' && $dataPagamentoCompra === '') {
    $dataPagamentoCompra = $dataCompra !== '' ? $dataCompra : date('Y-m-d');
  }

  try {
    $created = $loteRepo->create([
      'fornecedor_id' => $fornecedorId,
      'numero_processo' => $numeroProcesso,
      'titulo_lote' => $tituloLote,
      'descricao_resumida' => $descricaoResumida !== '' ? $descricaoResumida : $tituloLote,
      'data_compra' => $dataCompra,
      'status_macro' => 'em_transito',
      'etapa_timeline' => 'autorizacao_coleta',
      'observacoes_gerais' => $observacoesGerais,
      'valor_original_lote' => $valorSalvado,
      'valor_pago_compra' => $valorPagoCompra,
      'custo_total' => $valorPagoCompra,
      'despesas_local' => $despesasLocal,
      'outros_custos' => $outrosLocais,
      'nome_local' => $nomeLocal,
      'nome_contato' => $nomeContato,
      'telefone' => $telefone,
      'email' => $email,
      'endereco' => $endereco,
      'cidade' => $cidade,
      'estado' => $estado,
      'observacoes_local' => $observacoesLocal,
      'tipo_transporte' => '',
    ], 1);

    $novoId = (int)($created['id'] ?? 0);
    if ($novoId <= 0) {
      throw new RuntimeException('Lote não retornou id após o cadastro.');
    }

    lot_purchase_payment_save_config($novoId, [
      'status' => $statusPagamentoCompra,
      'paidAt' => $dataPagamentoCompra,
    ], 1);

    $loteRepo->addMovimentacao($novoId, [
      'tipo_evento' => 'timeline_compra_conclusao',
      'descricao_evento' => 'Compra concluída no cadastro inicial do lote.',
      'payload_estrutural' => [
        'timeline_stage' => 'compra',
        'timeline_action' => 'conclusao',
        'timeline_date' => $dataCompra,
        'next_stage' => 'autorizacao_coleta',
      ],
      'data_evento' => $dataCompra . ' 12:00:00',
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);

    $loteRepo->addMovimentacao($novoId, [
      'tipo_evento' => 'lote_cadastrado',
      'descricao_evento' => 'Cadastro inicial do lote',
      'payload_estrutural' => [
        'descricao_item' => $tituloLote,
        'tipo_controle_item' => 'Und',
      ],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);

    lot_redirect_with_flash($novoId, 'success', 'Lote criado com sucesso. Continue a operação pela ficha do processo.');
  } catch (Throwable $e) {
    $message = $e instanceof InvalidArgumentException
      ? trim($e->getMessage())
      : 'Não foi possível criar o novo lote.';
    $createRedirect('danger', $message !== '' ? $message : 'Não foi possível criar o novo lote.');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_process_update_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar os dados do processo.', '', 'detail-edit');
  }

  $generalNotes = lot_strip_labeled_lines((string)($loadedLote['observacoesGerais'] ?? ''), ['Sinistro:']);
  $payload = lot_build_update_payload($loadedLote);
  $payload['numero_processo'] = lot_upper_text((string)($_POST['numero_processo'] ?? ''));
  $payload['titulo_lote'] = lot_upper_text((string)($_POST['titulo_lote'] ?? ''));
  $payload['descricao_resumida'] = lot_upper_text((string)($_POST['descricao_resumida'] ?? ''));
  $payload['data_compra'] = trim((string)($_POST['data_compra'] ?? ''));
  $payload['valor_original_lote'] = (float)lot_decimal_input($_POST['valor_salvado'] ?? 0, 2);
  $payload['valor_pago_compra'] = (float)lot_decimal_input($_POST['valor_pago_compra'] ?? 0, 2);
  $paymentStatus = lot_purchase_payment_normalize_status((string)($_POST['status_pagamento_compra'] ?? 'pendente'));
  $paymentPaidAt = trim((string)($_POST['data_pagamento_compra'] ?? ''));
  $payload['observacoes_gerais'] = lot_build_general_notes(lot_upper_text((string)($_POST['numero_sinistro'] ?? '')), lot_upper_text($generalNotes));

  if (trim((string)$payload['numero_processo']) === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe o número do processo para continuar.', '', 'detail-edit');
  }
  if (trim((string)$payload['titulo_lote']) === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe o título do lote para continuar.', '', 'detail-edit');
  }
  if ($paymentStatus === 'pago' && $paymentPaidAt === '') {
    $paymentPaidAt = trim((string)$payload['data_compra']) !== '' ? trim((string)$payload['data_compra']) : date('Y-m-d');
  }

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    lot_purchase_payment_save_config($postedLoteId, [
      'status' => $paymentStatus,
      'paidAt' => $paymentPaidAt,
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Identificação do processo e valores iniciais atualizados com sucesso.');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível atualizar os dados principais do lote.', '', 'detail-edit');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_storage_update_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar o local de armazenagem.', '', 'detail-edit');
  }

  $localNotes = lot_strip_structured_local_lines((string)($loadedLote['observacoesLocal'] ?? ''));
  $payload = lot_build_update_payload($loadedLote);
  $custoArmazenagem = (float)lot_decimal_input($_POST['custo_armazenagem'] ?? 0, 2);
  $custoCarregamento = (float)lot_decimal_input($_POST['custo_carregamento'] ?? 0, 2);
  $custoSos = (float)lot_decimal_input($_POST['custo_sos'] ?? 0, 2);
  $outrosLocais = (float)lot_decimal_input($_POST['outros_custos'] ?? 0, 2);
  $freightImpostos = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Impostos frete:'), 2);
  $freightOutros = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Outros frete:'), 2);

  $payload['nome_local'] = lot_upper_text((string)($_POST['nome_local'] ?? ''));
  $payload['endereco'] = lot_upper_text((string)($_POST['endereco'] ?? ''));
  $payload['cidade'] = lot_upper_text((string)($_POST['cidade'] ?? ''));
  $payload['estado'] = lot_normalize_state_uf((string)($_POST['estado'] ?? ''));
  $payload['nome_contato'] = lot_upper_text((string)($_POST['nome_contato'] ?? ''));
  $payload['telefone'] = trim((string)($_POST['telefone'] ?? ''));
  $payload['email'] = trim((string)($_POST['email'] ?? ''));
  $payload['despesas_local'] = $custoArmazenagem + $custoCarregamento + $custoSos;
  $payload['outros_custos'] = $outrosLocais + $freightImpostos + $freightOutros;
  $payload['observacoes_local'] = lot_build_local_notes(
    trim((string)($_POST['cpf_cnpj_local'] ?? '')),
    trim((string)($_POST['telefone_2'] ?? '')),
    $custoArmazenagem,
    $custoCarregamento,
    $custoSos,
    $outrosLocais,
    lot_upper_text((string)($_POST['observacoes_local_livre'] ?? $localNotes))
  );

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Local de armazenagem e custos locais atualizados com sucesso.');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível atualizar o local de armazenagem deste lote.', '', 'detail-edit');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_freight_link_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $cadastroId = (int)($_POST['freight_cadastro_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para vincular o frete.', 'lotFreightAnchor');
  }

  if ($cadastroId <= 0) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Selecione um motorista ou transportadora para continuar.', 'lotFreightAnchor');
  }

  $cadastroFrete = $cadastroRepo->findById($cadastroId, 1);
  if (!is_array($cadastroFrete) || !lot_is_freight_cadastro($cadastroFrete)) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'O cadastro selecionado não pode ser vinculado como frete deste lote.', 'lotFreightAnchor');
  }

  $freightKind = lot_resolve_freight_kind($cadastroFrete);
  $veiculos = is_array($cadastroFrete['veiculos'] ?? null) ? (array)$cadastroFrete['veiculos'] : [];
  $veiculo = is_array($veiculos[0] ?? null) ? (array)$veiculos[0] : [];
  $vehicleLabel = trim(implode(' • ', array_filter([
    trim((string)($veiculo['modelo'] ?? '')),
    trim((string)($veiculo['placa'] ?? '')),
  ])));

  $payload = lot_build_update_payload($loadedLote);
  $payload['tipo_transporte'] = $freightKind === 'transportadora' ? 'transportadora' : 'motorista_autonomo';
  $payload['motorista_id'] = $freightKind === 'motorista' ? $cadastroId : 0;
  $payload['transportadora_id'] = $freightKind === 'transportadora' ? $cadastroId : 0;
  $payload['veiculo_referencia'] = $freightKind === 'motorista' ? $vehicleLabel : '';
  $payload['data_contratacao'] = trim((string)($loadedLote['dataContratacao'] ?? '')) !== ''
    ? (string)$loadedLote['dataContratacao']
    : date('Y-m-d');

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'frete_vinculado',
      'descricao_evento' => 'Frete vinculado ao lote: ' . lot_cadastro_display_name($cadastroFrete),
      'payload_estrutural' => [
        'frete_cadastro_id' => $cadastroId,
        'frete_tipo' => $freightKind,
        'frete_nome' => lot_cadastro_display_name($cadastroFrete),
      ],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Frete vinculado ao lote com sucesso.', 'lotFreightAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível vincular o frete deste lote.', 'lotFreightAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_freight_unlink_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para cancelar o frete.', 'lotFreightAnchor');
  }

  $payload = lot_build_update_payload($loadedLote);
  $payload['tipo_transporte'] = 'sem_frete';
  $payload['motorista_id'] = 0;
  $payload['transportadora_id'] = 0;
  $payload['veiculo_referencia'] = '';
  $payload['agenciador'] = '';
  $payload['documento_transporte'] = '';
  $payload['data_contratacao'] = '';
  $payload['data_agendamento'] = '';

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'frete_cancelado',
      'descricao_evento' => 'Vínculo de frete cancelado para permitir nova contratação.',
      'payload_estrutural' => [
        'timeline_report' => 'Frete desvinculado manualmente.',
      ],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Vínculo de frete cancelado com sucesso.', 'lotFreightAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível cancelar o vínculo de frete deste lote.', 'lotFreightAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_freight_update_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar os dados do frete.', 'lotFreightAnchor');
  }

  $payload = lot_build_update_payload($loadedLote);
  $logisticNotes = lot_strip_structured_logistic_lines((string)($loadedLote['observacoesLogisticas'] ?? ''));
  $localOutros = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLocal'] ?? ''), 'Outros locais:'), 2);
  $valorFrete = (float)lot_decimal_input($_POST['valor_frete'] ?? 0, 2);
  $valorDocumento = (float)lot_decimal_input($_POST['valor_documentacao'] ?? 0, 2);
  $valorImpostos = (float)lot_decimal_input($_POST['valor_impostos'] ?? 0, 2);
  $valorOutrosFrete = (float)lot_decimal_input($_POST['valor_outros_frete'] ?? 0, 2);
  $freightCadastroId = (int)($_POST['freight_cadastro_id'] ?? 0);

  $payload['valor_frete'] = $valorFrete;
  $payload['valor_documento_transporte'] = $valorDocumento;
  $payload['outros_custos'] = $localOutros + $valorImpostos + $valorOutrosFrete;
  $payload['observacoes_logisticas'] = lot_build_logistic_notes(
    $valorImpostos,
    $valorOutrosFrete,
    lot_extract_freight_tags((string)($loadedLote['observacoesLogisticas'] ?? '')),
    lot_upper_text((string)($_POST['observacoes_logisticas'] ?? $logisticNotes))
  );
  $payload['data_contratacao'] = trim((string)($_POST['data_contratacao'] ?? ($loadedLote['dataContratacao'] ?? '')));

  if ($freightCadastroId > 0) {
    $cadastroFrete = $cadastroRepo->findById($freightCadastroId, 1);
    if (!is_array($cadastroFrete) || !lot_is_freight_cadastro($cadastroFrete)) {
      lot_redirect_with_flash($postedLoteId, 'warning', 'Selecione um motorista ou transportadora válida para salvar o frete.', 'lotFreightAnchor');
    }

    $freightKind = lot_resolve_freight_kind($cadastroFrete);
    $veiculos = is_array($cadastroFrete['veiculos'] ?? null) ? (array)$cadastroFrete['veiculos'] : [];
    $veiculo = is_array($veiculos[0] ?? null) ? (array)$veiculos[0] : [];
    $vehicleLabel = trim(implode(' • ', array_filter([
      trim((string)($veiculo['modelo'] ?? '')),
      trim((string)($veiculo['placa'] ?? '')),
    ], static fn ($value): bool => $value !== '')));

    $payload['tipo_transporte'] = $freightKind === 'transportadora' ? 'transportadora' : 'motorista_autonomo';
    $payload['transportadora_id'] = $freightKind === 'transportadora' ? $freightCadastroId : 0;
    $payload['motorista_id'] = $freightKind === 'motorista' ? $freightCadastroId : 0;
    $payload['veiculo_referencia'] = $freightKind === 'motorista' ? $vehicleLabel : '';
    if (trim((string)$payload['data_contratacao']) === '') {
      $payload['data_contratacao'] = date('Y-m-d');
    }
  }

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Dados do frete atualizados com sucesso.', 'lotFreightAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível atualizar os dados do frete deste lote.', 'lotFreightAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_cancel_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para cancelar.', 'lotPanelAnchor');
  }

  $cancelRecordId = (int)($_POST['cancel_record_id'] ?? 0);
  $cancelKind = trim((string)($_POST['cancel_kind'] ?? 'total'));
  if (!in_array($cancelKind, ['total', 'parcial'], true)) {
    $cancelKind = 'total';
  }

  $existingCancelRecord = null;
  if ($cancelRecordId > 0) {
    foreach ((array)($loadedLote['movimentacoes'] ?? []) as $movimentacao) {
      if ((int)($movimentacao['id'] ?? 0) !== $cancelRecordId) {
        continue;
      }
      if (!in_array((string)($movimentacao['tipoEvento'] ?? ''), ['lote_cancelado', 'lote_devolucao_parcial'], true)) {
        continue;
      }
      $existingCancelRecord = $movimentacao;
      break;
    }
    if (!is_array($existingCancelRecord)) {
      lot_redirect_with_flash($postedLoteId, 'warning', 'Não foi possível localizar a ocorrência selecionada.', 'lotPanelAnchor');
    }
    $cancelKind = (string)($existingCancelRecord['tipoEvento'] ?? '') === 'lote_cancelado' ? 'total' : 'parcial';
  }

  if ($cancelRecordId <= 0 && $cancelKind === 'total' && (string)($loadedLote['statusMacro'] ?? '') === 'cancelado') {
    lot_redirect_with_flash($postedLoteId, 'info', 'Este lote já está cancelado.', 'lotPanelAnchor');
  }

  $motivo = lot_upper_text((string)($_POST['cancel_motivo'] ?? ''));
  $relato = lot_upper_text((string)($_POST['cancel_relato'] ?? ''));
  $observacaoFinanceira = lot_upper_text((string)($_POST['cancel_financeiro'] ?? ''));
  $valorEstorno = (float)lot_decimal_input($_POST['cancel_estorno'] ?? 0, 2);
  $refundDueDate = trim((string)($_POST['cancel_refund_due_date'] ?? ''));
  $cancelStatusPosted = trim((string)($_POST['cancel_status'] ?? ''));
  $dataCancelamento = trim((string)($_POST['cancel_data'] ?? date('Y-m-d')));

  if ($motivo === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe o motivo do cancelamento do lote.', 'lotPanelAnchor');
  }
  if ($cancelKind === 'parcial' && $valorEstorno <= 0) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe o valor da devolução parcial para registrar esta ocorrência.', 'lotPanelAnchor');
  }
  if ($cancelKind === 'total' && $cancelStatusPosted === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Selecione o status do cancelamento para continuar.', 'lotPanelAnchor');
  }
  if ($cancelKind === 'total' && $cancelStatusPosted === 'cancelado_sem_pagamento' && $valorEstorno > 0) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Cancelamentos sem pagamento não podem ter valor de estorno informado.', 'lotPanelAnchor');
  }
  if ($cancelKind === 'total' && in_array($cancelStatusPosted, ['cancelado_aguardando_estorno', 'cancelado_estornado'], true) && $valorEstorno <= 0) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe o valor do estorno para usar este status de cancelamento.', 'lotPanelAnchor');
  }

  $cancelStatus = $cancelKind === 'total'
    ? lot_cancel_status_from_payload([
      'cancelamento_status' => $cancelStatusPosted,
      'cancelamento_estorno' => $valorEstorno,
      'cancelamento_refund_due_date' => $refundDueDate,
    ], 'lote_cancelado')
    : '';

  $payload = lot_build_update_payload($loadedLote);
  if ($cancelKind === 'total') {
    $payload['status_macro'] = 'cancelado';
  }

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    $uploadedCancelFiles = [];
    if (!empty((array)($_FILES['cancel_attachment_files'] ?? []))) {
      try {
        $arquivoRepo->validateUploads((array)($_FILES['cancel_attachment_files'] ?? []));
        $uploadedCancelFiles = $arquivoRepo->attachUploadedFiles('lotes_cancelamento', $postedLoteId, (array)($_FILES['cancel_attachment_files'] ?? []), 1);
      } catch (Throwable $uploadError) {
        // Mantemos o cancelamento e apenas ignoramos a falha de anexo neste primeiro momento.
      }
    }
    $movementPayload = [
      'tipo_evento' => $cancelKind === 'total' ? 'lote_cancelado' : 'lote_devolucao_parcial',
      'descricao_evento' => ($cancelKind === 'total' ? 'Lote cancelado: ' : 'Devolução parcial registrada: ') . $motivo,
      'payload_estrutural' => [
        'cancelamento_tipo' => $cancelKind,
        'cancelamento_data' => $dataCancelamento,
        'cancelamento_motivo' => $motivo,
        'cancelamento_relato' => $relato,
        'cancelamento_financeiro' => $observacaoFinanceira,
        'cancelamento_estorno' => $valorEstorno,
        'cancelamento_refund_due_date' => $refundDueDate,
        'cancelamento_status' => $cancelStatus,
        'cancelamento_anexos' => count($uploadedCancelFiles),
        'timeline_report' => $relato !== '' ? $relato : (($cancelKind === 'total' ? 'Cancelamento do lote: ' : 'Devolução parcial do lote: ') . $motivo),
      ],
      'data_evento' => $dataCancelamento !== '' ? ($dataCancelamento . ' 12:00:00') : date('Y-m-d H:i:s'),
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ];

    if ($cancelRecordId > 0) {
      $payloadAtual = is_array($existingCancelRecord['payloadEstrutural'] ?? null) ? (array)$existingCancelRecord['payloadEstrutural'] : [];
      $movementPayload['payload_estrutural']['cancelamento_anexos'] = ((int)($payloadAtual['cancelamento_anexos'] ?? 0)) + count($uploadedCancelFiles);
      $movementPayload['responsavel'] = trim((string)($existingCancelRecord['responsavel'] ?? $movementPayload['responsavel']));
      $loteRepo->updateMovimentacao($cancelRecordId, $movementPayload, 1);
      lot_redirect_with_flash($postedLoteId, 'success', 'Ocorrência atualizada com sucesso.', 'lotPanelAnchor');
    }

    $loteRepo->addMovimentacao($postedLoteId, $movementPayload, 1);
    lot_redirect_with_flash($postedLoteId, 'success', $cancelKind === 'total' ? 'Lote cancelado com sucesso.' : 'Devolução parcial registrada com sucesso.', 'lotPanelAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', $cancelRecordId > 0 ? 'Não foi possível atualizar esta ocorrência.' : ($cancelKind === 'total' ? 'Não foi possível cancelar este lote.' : 'Não foi possível registrar a devolução parcial deste lote.'), 'lotPanelAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_notes_update_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar as observações.', 'lotNotesAnchor');
  }

  $sinistroAtual = lot_extract_labeled_line((string)($loadedLote['observacoesGerais'] ?? ''), 'Sinistro:');
  $payload = lot_build_update_payload($loadedLote);
  $impostosFreteAtual = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Impostos frete:'), 2);
  $outrosFreteAtual = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Outros frete:'), 2);
  $payload['observacoes_gerais'] = lot_build_general_notes($sinistroAtual, lot_upper_text((string)($_POST['observacoes_gerais'] ?? '')));
  $payload['observacoes_logisticas'] = lot_build_logistic_notes(
    $impostosFreteAtual,
    $outrosFreteAtual,
    lot_extract_freight_tags((string)($loadedLote['observacoesLogisticas'] ?? '')),
    lot_upper_text((string)($_POST['observacoes_logisticas'] ?? ''))
  );

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Observações do processo atualizadas com sucesso.', 'lotNotesAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível atualizar as observações deste lote.', 'lotNotesAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_public_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  $publicAction = trim((string)($_POST['public_action'] ?? 'publish'));

  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar a ficha pública.', 'lotPanelAnchor');
  }

  try {
    if ($publicAction === 'disable') {
      lot_public_save_config($postedLoteId, ['published' => false], 1);
      lot_redirect_with_flash($postedLoteId, 'success', 'Ficha pública desativada com sucesso.', 'lotPanelAnchor');
    }

    $config = lot_public_save_config($postedLoteId, ['published' => true], 1);
    if (trim((string)($config['token'] ?? '')) === '') {
      throw new RuntimeException('Não foi possível gerar o link público do lote.');
    }
    lot_redirect_with_flash($postedLoteId, 'success', 'Ficha pública ativada com sucesso.', 'lotPanelAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível atualizar a ficha pública deste lote.', 'lotPanelAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_attachment_upload_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $attachmentGroupKey = trim((string)($_POST['attachment_group'] ?? ''));
  $attachmentGroups = lot_attachment_groups();
  $attachmentMeta = $attachmentGroups[$attachmentGroupKey] ?? null;
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  $attachmentRedirect = static function (string $kind, string $message) use ($postedLoteId, $attachmentGroupKey): never {
    $_SESSION['lot_open_modal'] = $attachmentGroupKey !== '' ? 'attachments:' . $attachmentGroupKey : 'attachments';
    lot_redirect_with_flash($postedLoteId, $kind, $message, 'lotNotesAnchor');
  };

  if (!is_array($loadedLote)) {
    $attachmentRedirect('danger', 'Não foi possível localizar o lote para enviar os anexos.');
  }
  if (!is_array($attachmentMeta)) {
    $attachmentRedirect('warning', 'O grupo de anexos selecionado é inválido.');
  }

  try {
    $arquivoRepo->validateUploads((array)($_FILES['attachment_files'] ?? []));
    $uploaded = $arquivoRepo->attachUploadedFiles((string)$attachmentMeta['entity'], $postedLoteId, (array)($_FILES['attachment_files'] ?? []), 1);
    if ($uploaded === []) {
      $attachmentRedirect('warning', 'Selecione ao menos um arquivo para continuar.');
    }

    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'anexo_adicionado',
      'descricao_evento' => 'Anexo adicionado em ' . (string)($attachmentMeta['title'] ?? 'Anexos'),
      'payload_estrutural' => [
        'grupo_anexo' => $attachmentGroupKey,
        'grupo_titulo' => (string)($attachmentMeta['title'] ?? 'Anexos'),
        'quantidade_arquivos' => count($uploaded),
      ],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);

    $attachmentRedirect('success', count($uploaded) === 1 ? 'Anexo enviado com sucesso.' : 'Anexos enviados com sucesso.');
  } catch (Throwable $e) {
    $attachmentRedirect('danger', 'Não foi possível enviar os anexos deste lote.');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_attachment_remove_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $attachmentGroupKey = trim((string)($_POST['attachment_group'] ?? ''));
  $relationId = (int)($_POST['attachment_relation_id'] ?? 0);
  $attachmentName = trim((string)($_POST['attachment_name'] ?? ''));
  $attachmentGroups = lot_attachment_groups();
  $attachmentMeta = $attachmentGroups[$attachmentGroupKey] ?? null;
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  $attachmentRedirect = static function (string $kind, string $message) use ($postedLoteId, $attachmentGroupKey): never {
    $_SESSION['lot_open_modal'] = $attachmentGroupKey !== '' ? 'attachments:' . $attachmentGroupKey : 'attachments';
    lot_redirect_with_flash($postedLoteId, $kind, $message, 'lotNotesAnchor');
  };

  if (!is_array($loadedLote)) {
    $attachmentRedirect('danger', 'Não foi possível localizar o lote para remover o anexo.');
  }
  if (!is_array($attachmentMeta) || $relationId <= 0) {
    $attachmentRedirect('warning', 'O anexo selecionado é inválido para remoção.');
  }

  try {
    $arquivoRepo->removeRelations((string)$attachmentMeta['entity'], $postedLoteId, [$relationId], 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'anexo_removido',
      'descricao_evento' => 'Anexo removido de ' . (string)($attachmentMeta['title'] ?? 'Anexos'),
      'payload_estrutural' => [
        'grupo_anexo' => $attachmentGroupKey,
        'grupo_titulo' => (string)($attachmentMeta['title'] ?? 'Anexos'),
        'nome_arquivo' => $attachmentName,
      ],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);

    $attachmentRedirect('success', 'Anexo removido com sucesso.');
  } catch (Throwable $e) {
    $attachmentRedirect('danger', 'Não foi possível remover o anexo selecionado.');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_timeline_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $timelineStageKey = trim((string)($_POST['timeline_stage'] ?? ''));
  $timelineSubmitMode = trim((string)($_POST['timeline_submit_mode'] ?? 'save'));
  $timelineRecordId = (int)($_POST['timeline_record_id'] ?? 0);
  $timelineDate = trim((string)($_POST['data_evento'] ?? ''));
  $timelineContact = lot_upper_text((string)($_POST['timeline_contact'] ?? ''));
  $timelineExpectedDelivery = trim((string)($_POST['timeline_expected_delivery'] ?? ''));
  $timelineDescription = lot_upper_text((string)($_POST['descricao_evento'] ?? ''));
  $timelineResponsavel = trim((string)($_SESSION['auth_user']['name'] ?? 'Operação'));
  $timelineUserId = (int)($_SESSION['auth_user']['id'] ?? 0);

  $redirectParams = ['lote' => $postedLoteId];
  $timelineStagesMap = lot_timeline_stages();
  $timelineStageKeys = array_values(array_map(static fn (array $stage): string => (string)$stage['key'], $timelineStagesMap));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;

  $redirectWithTimelineFlash = static function (string $kind, string $message) use ($redirectParams): never {
    $params = $redirectParams;
    $params['timeline_kind'] = $kind;
    $params['timeline_msg'] = $message;
    header('Location: ' . lot_module_url($params) . '#lotTimelineAnchor');
    exit;
  };
  $redirectWithTimelineModal = static function (string $kind, string $message, array $oldInput = []) use ($redirectParams, $timelineStageKey): never {
    $_SESSION['lot_open_modal'] = $timelineStageKey !== '' ? 'timeline:' . $timelineStageKey : 'timeline';
    $_SESSION['lot_timeline_old_input'] = $oldInput;
    $params = $redirectParams;
    $params['timeline_kind'] = $kind;
    $params['timeline_msg'] = $message;
    header('Location: ' . lot_module_url($params) . '#lotTimelineAnchor');
    exit;
  };

  if (!is_array($loadedLote)) {
    $redirectWithTimelineFlash('danger', 'Não foi possível localizar o lote para atualizar a timeline.');
  }

  $currentStageKey = trim((string)($loadedLote['etapaTimeline'] ?? 'compra'));
  if ($currentStageKey === '') {
    $currentStageKey = 'compra';
  }
  if ($currentStageKey === 'compra' && (string)($loadedLote['statusMacro'] ?? '') === 'em_transito') {
    $currentStageKey = 'autorizacao_coleta';
  }

  if ($currentStageKey === 'finalizado' || ($currentStageKey === 'entrega' && (string)($loadedLote['statusMacro'] ?? '') === 'em_estoque')) {
    $redirectWithTimelineFlash('info', 'A timeline operacional deste lote já foi concluída.');
  }

  $actionableStageKey = $currentStageKey;
  $currentStageIndex = array_search($actionableStageKey, $timelineStageKeys, true);
  $currentStageIndex = $currentStageIndex === false ? 0 : (int)$currentStageIndex;
  $currentStageMeta = $timelineStagesMap[$currentStageIndex] ?? $timelineStagesMap[0];
  $stageConfig = lot_timeline_stage_form_config($timelineStageKey);
  $isCurrentStage = $timelineStageKey === (string)($currentStageMeta['key'] ?? '');
  $isPreviousStage = in_array($timelineStageKey, array_slice($timelineStageKeys, 0, $currentStageIndex), true);
  $timelineUpdatePayload = lot_build_update_payload($loadedLote);

  if (!in_array($timelineSubmitMode, ['save', 'finalize', 'reopen', 'update', 'delete'], true)) {
    $redirectWithTimelineModal('warning', 'A ação da timeline é inválida.', $_POST);
  }

  if (($timelineSubmitMode === 'save' || $timelineSubmitMode === 'finalize') && !$isCurrentStage) {
    $redirectWithTimelineModal('warning', 'A timeline só permite agir sobre a etapa atual do processo.', $_POST);
  }

  if ($timelineSubmitMode === 'reopen' && !$isPreviousStage) {
    $redirectWithTimelineModal('warning', 'Só é possível reabrir uma etapa já concluída.', $_POST);
  }

  if (($timelineSubmitMode === 'update' || $timelineSubmitMode === 'delete') && $timelineRecordId <= 0) {
    $redirectWithTimelineModal('warning', 'Selecione um registro válido para editar ou excluir.', $_POST);
  }

  if ($timelineDescription === '') {
    if (!in_array($timelineSubmitMode, ['delete', 'reopen'], true)) {
      $redirectWithTimelineModal('warning', 'Descreva o registro da etapa antes de salvar.', $_POST);
    }
  }

  if ($timelineDate === '') {
    $timelineDate = date('Y-m-d');
  }

  if (!in_array($timelineSubmitMode, ['delete', 'reopen'], true) && !empty($stageConfig['contact_required']) && $timelineContact === '') {
    $redirectWithTimelineModal('warning', 'Informe com quem foi feito o contato nesta etapa.', $_POST);
  }

  if ($timelineStageKey === 'coleta' && $timelineExpectedDelivery === '') {
    if (!in_array($timelineSubmitMode, ['delete', 'reopen'], true)) {
      $redirectWithTimelineModal('warning', 'Informe o prazo previsto de entrega para continuar com a coleta.', $_POST);
    }
  }

  $forceWithoutFreight = trim((string)($_POST['timeline_force_without_freight'] ?? '')) === '1';
  $shouldConclude = $timelineSubmitMode === 'finalize';
  if (
    $shouldConclude
    && $timelineStageKey === 'coleta'
    && lot_timeline_requires_freight_confirmation($loadedLote)
    && !$forceWithoutFreight
  ) {
    $redirectWithTimelineModal('warning', 'Defina o frete/motorista antes da coleta ou confirme a coleta própria sem frete.', $_POST);
  }

  $timelineDateTime = $timelineDate . ' 12:00:00';
  $stageLabel = (string)($currentStageMeta['label'] ?? lot_etapa_label($timelineStageKey));
  $timelineStatus = $timelineSubmitMode === 'finalize' ? trim((string)($stageConfig['final_status'] ?? '')) : '';
  $statusLabel = $timelineSubmitMode === 'finalize' ? trim((string)($stageConfig['final_status_label'] ?? '')) : '';
  $recordDescription = $stageLabel;
  if ($timelineContact !== '') {
    $recordDescription .= ' • ' . $timelineContact;
  }
  $recordDescription .= ': ' . $timelineDescription;

  try {
    if ($timelineSubmitMode === 'reopen') {
      $loteRepo->update($postedLoteId, array_merge($timelineUpdatePayload, [
        'etapa_timeline' => $timelineStageKey,
        'status_macro' => 'em_transito',
      ]), 1);
      $loteRepo->addMovimentacao($postedLoteId, [
        'tipo_evento' => 'timeline_' . $timelineStageKey . '_reaberta',
        'descricao_evento' => $stageLabel . ' reativada para nova tratativa.',
        'payload_estrutural' => [
          'timeline_stage' => $timelineStageKey,
          'timeline_action' => 'reabertura',
          'timeline_date' => $timelineDate,
          'timeline_report' => $timelineDescription !== '' ? $timelineDescription : 'Etapa reativada manualmente.',
        ],
        'data_evento' => $timelineDateTime,
        'responsavel' => $timelineResponsavel !== '' ? $timelineResponsavel : 'Operação',
      ], 1);
      $redirectWithTimelineFlash('success', 'Etapa reativada com sucesso. As posteriores voltaram para pendentes, mas os registros foram preservados.');
    }

    if ($timelineSubmitMode === 'update' || $timelineSubmitMode === 'delete') {
      $movimentacoes = is_array($loadedLote['movimentacoes'] ?? null) ? (array)$loadedLote['movimentacoes'] : [];
      $selectedRecord = null;
      foreach ($movimentacoes as $movimentacao) {
        if ((int)($movimentacao['id'] ?? 0) === $timelineRecordId) {
          $selectedRecord = $movimentacao;
          break;
        }
      }
      if (!is_array($selectedRecord)) {
        $redirectWithTimelineModal('warning', 'Não foi possível localizar o registro selecionado.', $_POST);
      }
      $payloadAtual = is_array($selectedRecord['payloadEstrutural'] ?? null) ? (array)$selectedRecord['payloadEstrutural'] : [];
      $ownerId = (int)($payloadAtual['timeline_owner_id'] ?? 0);
      $ownerName = trim((string)($payloadAtual['timeline_owner_name'] ?? $selectedRecord['responsavel'] ?? ''));
      $isOwner = ($ownerId > 0 && $timelineUserId > 0 && $ownerId === $timelineUserId)
        || ($ownerId <= 0 && $timelineResponsavel !== '' && strcasecmp($ownerName, $timelineResponsavel) === 0);
      if (!$isOwner) {
        $redirectWithTimelineModal('warning', 'Você só pode editar ou excluir registros lançados por você.', $_POST);
      }

      if ($timelineSubmitMode === 'delete') {
        $loteRepo->deleteMovimentacao($timelineRecordId, 1);
        $redirectWithTimelineFlash('success', 'Registro da etapa removido com sucesso.');
      }

      $loteRepo->updateMovimentacao($timelineRecordId, [
        'descricao_evento' => $recordDescription,
        'payload_estrutural' => [
          'timeline_stage' => $timelineStageKey,
          'timeline_action' => 'registro',
          'timeline_date' => $timelineDate,
          'timeline_contact' => $timelineContact,
          'timeline_status' => $timelineStatus,
          'timeline_status_label' => $statusLabel,
          'timeline_report' => $timelineDescription,
          'timeline_expected_delivery' => $timelineExpectedDelivery,
          'timeline_owner_id' => $ownerId > 0 ? $ownerId : $timelineUserId,
          'timeline_owner_name' => $ownerName !== '' ? $ownerName : $timelineResponsavel,
        ],
        'data_evento' => $timelineDateTime,
        'responsavel' => $ownerName !== '' ? $ownerName : ($timelineResponsavel !== '' ? $timelineResponsavel : 'Operação'),
      ], 1);
      if ($timelineStageKey === 'coleta' && $timelineExpectedDelivery !== '') {
        $loteRepo->update($postedLoteId, array_merge($timelineUpdatePayload, [
          'data_entrega' => $timelineExpectedDelivery,
        ]), 1);
      }
      $redirectWithTimelineFlash('success', 'Registro da etapa atualizado com sucesso.');
    }

    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'timeline_' . $timelineStageKey . '_registro',
      'descricao_evento' => $recordDescription,
      'payload_estrutural' => [
        'timeline_stage' => $timelineStageKey,
        'timeline_action' => 'registro',
        'timeline_date' => $timelineDate,
        'timeline_contact' => $timelineContact,
        'timeline_status' => $timelineStatus,
        'timeline_status_label' => $statusLabel,
        'timeline_report' => $timelineDescription,
        'timeline_expected_delivery' => $timelineExpectedDelivery,
        'timeline_owner_id' => $timelineUserId,
        'timeline_owner_name' => $timelineResponsavel,
      ],
      'data_evento' => $timelineDateTime,
      'responsavel' => $timelineResponsavel !== '' ? $timelineResponsavel : 'Operação',
    ], 1);

    if ($timelineStageKey === 'coleta' && $timelineExpectedDelivery !== '') {
      $loteRepo->update($postedLoteId, array_merge($timelineUpdatePayload, [
        'data_entrega' => $timelineExpectedDelivery,
      ]), 1);
    }

    if (!$shouldConclude) {
      $redirectWithTimelineModal('success', $timelineSubmitMode === 'update' ? 'Registro da etapa atualizado com sucesso.' : 'Registro da etapa salvo com sucesso.', [
        'timeline_stage' => $timelineStageKey,
      ]);
    }

    $nextStageMeta = $timelineStagesMap[$currentStageIndex + 1] ?? null;
    $updatePayload = [];
    if (is_array($nextStageMeta) && (string)($nextStageMeta['key'] ?? '') !== 'finalizado') {
      $updatePayload['etapa_timeline'] = (string)$nextStageMeta['key'];
      $updatePayload['status_macro'] = 'em_transito';
    } elseif ($timelineStageKey === 'entrega') {
      $updatePayload['etapa_timeline'] = 'entrega';
      $updatePayload['status_macro'] = 'em_estoque';
    } else {
      $updatePayload['etapa_timeline'] = $timelineStageKey;
      $updatePayload['status_macro'] = (string)($loadedLote['statusMacro'] ?? 'em_transito');
    }

    $loteRepo->update($postedLoteId, array_merge($timelineUpdatePayload, $updatePayload), 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'timeline_' . $timelineStageKey . '_conclusao',
      'descricao_evento' => $stageLabel . ' concluída: ' . $statusLabel . ' • ' . $timelineDescription,
      'payload_estrutural' => [
        'timeline_stage' => $timelineStageKey,
        'timeline_action' => 'conclusao',
        'timeline_date' => $timelineDate,
        'timeline_contact' => $timelineContact,
        'timeline_status' => $timelineStatus,
        'timeline_status_label' => $statusLabel,
        'timeline_report' => $timelineDescription,
        'timeline_expected_delivery' => $timelineExpectedDelivery,
        'next_stage' => (string)($nextStageMeta['key'] ?? $timelineStageKey),
        'forced_without_freight' => $forceWithoutFreight,
      ],
      'data_evento' => $timelineDateTime,
      'responsavel' => $timelineResponsavel !== '' ? $timelineResponsavel : 'Operação',
    ], 1);

    $redirectWithTimelineFlash('success', 'Etapa concluída e timeline atualizada com sucesso.');
  } catch (Throwable $e) {
    $redirectWithTimelineModal('danger', 'Não foi possível salvar a atualização da timeline.', $_POST);
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_item_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $itemId = (int)($_POST['item_id'] ?? 0);
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para salvar o item.', '', 'item-manage');
  }

  $descricao = trim((string)($_POST['descricao_item'] ?? ''));
  if ($descricao === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe a descrição do item antes de salvar.', '', 'item-manage');
  }

  $tipoControle = trim((string)($_POST['tipo_controle_item'] ?? 'unidade'));
  $quantidadeTotal = (float)lot_decimal_input($_POST['quantidade_total'] ?? 0, 3);
  $custoUnitario = (float)lot_decimal_input($_POST['custo_unitario_referencia'] ?? 0, 2);
  $valorVendaUnitario = (float)lot_decimal_input($_POST['valor_venda_unitario_sugerido'] ?? 0, 2);
  $observacoes = trim((string)($_POST['observacoes_item'] ?? ''));
  $itemImageRemovals = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['item_remove_attachment_ids'] ?? [])), static fn (int $value): bool => $value > 0)));

  if ($quantidadeTotal <= 0) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe uma quantidade válida para o item.', '', 'item-manage');
  }

  try {
    $arquivoRepo->validateUploads((array)($_FILES['item_image_files'] ?? []));
    lot_validate_image_uploads((array)($_FILES['item_image_files'] ?? []));
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'warning', $e->getMessage() !== '' ? $e->getMessage() : 'Não foi possível validar as imagens do produto.', '', 'item-manage');
  }

  $items = is_array($loadedLote['itens'] ?? null) ? (array)$loadedLote['itens'] : [];
  $existing = null;
  foreach ($items as $candidate) {
    if (is_array($candidate) && (int)($candidate['id'] ?? 0) === $itemId) {
      $existing = $candidate;
      break;
    }
  }

  $quantidadeBaixada = (float)($existing['quantidadeBaixada'] ?? 0);
  $quantidadeVendida = (float)($existing['quantidadeVendida'] ?? 0);
  $quantidadeDisponivel = max(0, $quantidadeTotal - $quantidadeBaixada - $quantidadeVendida);
  $itemPayload = [
    'descricaoItem' => $descricao,
    'tipoControleItem' => in_array($tipoControle, ['kg', 'unidade', 'metros'], true) ? $tipoControle : 'unidade',
    'quantidadeTotal' => $quantidadeTotal,
    'quantidadeDisponivel' => $quantidadeDisponivel,
    'quantidadeBaixada' => $quantidadeBaixada,
    'quantidadeVendida' => $quantidadeVendida,
    'custoUnitarioReferencia' => $custoUnitario,
    'custoTotalReferencia' => $quantidadeTotal * $custoUnitario,
    'valorVendaUnitarioSugerido' => $valorVendaUnitario,
    'valorVendaTotalSugerido' => $quantidadeTotal * $valorVendaUnitario,
    'observacoesItem' => $observacoes,
    'statusItem' => $quantidadeDisponivel > 0 ? (($quantidadeBaixada > 0 || $quantidadeVendida > 0) ? 'parcial' : 'ativo') : 'encerrado',
  ];
  if ($itemId > 0) {
    $itemPayload['id'] = $itemId;
  }

  $nextItems = [];
  $updated = false;
  $targetItemIndex = null;
  foreach ($items as $candidate) {
    if (!is_array($candidate)) {
      continue;
    }
    if ((int)($candidate['id'] ?? 0) === $itemId && $itemId > 0) {
      $nextItems[] = $itemPayload;
      $targetItemIndex = count($nextItems) - 1;
      $updated = true;
      continue;
    }
    $nextItems[] = $candidate;
  }
  if (!$updated) {
    $nextItems[] = $itemPayload;
    $targetItemIndex = count($nextItems) - 1;
  }

  try {
    $savedItems = $loteRepo->replaceItens($postedLoteId, $nextItems, 1);
    $movementItemId = 0;
    if ($targetItemIndex !== null && isset($savedItems[$targetItemIndex]) && is_array($savedItems[$targetItemIndex])) {
      $movementItemId = (int)($savedItems[$targetItemIndex]['id'] ?? 0);
    }
    if ($movementItemId <= 0) {
      foreach (array_reverse($savedItems) as $savedItem) {
        if (!is_array($savedItem)) {
          continue;
        }
        if ((string)($savedItem['descricaoItem'] ?? '') === $descricao) {
          $movementItemId = (int)($savedItem['id'] ?? 0);
          break;
        }
      }
    }

    if ($movementItemId > 0 && $itemImageRemovals !== []) {
      $arquivoRepo->removeRelations('lote_item', $movementItemId, $itemImageRemovals, 1);
    }

    $uploadedItemImages = [];
    if ($movementItemId > 0) {
      $uploadedItemImages = $arquivoRepo->attachUploadedFiles('lote_item', $movementItemId, (array)($_FILES['item_image_files'] ?? []), 1);
    }

    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => $updated ? 'item_editado' : 'item_cadastrado',
      'descricao_evento' => ($updated ? 'Item atualizado: ' : 'Item cadastrado: ') . $descricao,
      'payload_estrutural' => [
        'item_id' => $movementItemId,
        'descricao_item' => $descricao,
        'tipo_controle_item' => $itemPayload['tipoControleItem'],
        'quantidade_total' => $itemPayload['quantidadeTotal'],
        'valor_venda_unitario_sugerido' => $itemPayload['valorVendaUnitarioSugerido'],
        'imagens_adicionadas' => count($uploadedItemImages),
        'imagens_removidas' => count($itemImageRemovals),
      ],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', $updated ? 'Item atualizado com sucesso.' : 'Item adicionado com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível salvar o item do lote.', 'lotOpsAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_item_baixa_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $itemId = (int)($_POST['baixa_item_id'] ?? 0);
  $quantidadeBaixa = (float)lot_decimal_input($_POST['baixa_quantidade'] ?? 0, 3);
  $observacaoBaixa = trim((string)($_POST['baixa_observacao'] ?? ''));
  $dataBaixa = trim((string)($_POST['baixa_data'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para registrar a baixa.', 'lotOpsAnchor');
  }

  $items = is_array($loadedLote['itens'] ?? null) ? (array)$loadedLote['itens'] : [];
  $target = null;
  foreach ($items as $candidate) {
    if (is_array($candidate) && (int)($candidate['id'] ?? 0) === $itemId) {
      $target = $candidate;
      break;
    }
  }
  if (!is_array($target)) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'O item selecionado para baixa não foi encontrado.', 'lotOpsAnchor');
  }

  $disponivelAtual = (float)($target['quantidadeDisponivel'] ?? 0);
  if ($quantidadeBaixa <= 0 || $quantidadeBaixa > $disponivelAtual) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe uma quantidade de baixa compatível com o disponível do item.', 'lotOpsAnchor');
  }

  $nextItems = [];
  foreach ($items as $candidate) {
    if (!is_array($candidate)) {
      continue;
    }

    if ((int)($candidate['id'] ?? 0) !== $itemId) {
      $nextItems[] = $candidate;
      continue;
    }

    $quantidadeTotal = (float)($candidate['quantidadeTotal'] ?? 0);
    $quantidadeVendida = (float)($candidate['quantidadeVendida'] ?? 0);
    $novoBaixado = (float)($candidate['quantidadeBaixada'] ?? 0) + $quantidadeBaixa;
    $novoDisponivel = max(0, $quantidadeTotal - $novoBaixado - $quantidadeVendida);

    $candidate['quantidadeBaixada'] = $novoBaixado;
    $candidate['quantidadeDisponivel'] = $novoDisponivel;
    $candidate['statusItem'] = $novoDisponivel > 0 ? 'parcial' : 'encerrado';
    $nextItems[] = $candidate;
  }

  try {
    $loteRepo->replaceItens($postedLoteId, $nextItems, 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'item_baixa_manual',
      'descricao_evento' => 'Baixa manual do item: ' . (string)($target['descricaoItem'] ?? 'Item') . ' (' . lot_qty($quantidadeBaixa) . ')',
      'payload_estrutural' => [
        'item_id' => $itemId,
        'descricao_item' => (string)($target['descricaoItem'] ?? ''),
        'tipo_controle_item' => lot_control_label((string)($target['tipoControleItem'] ?? '')),
        'quantidade_baixada' => $quantidadeBaixa,
        'observacao' => $observacaoBaixa,
      ],
      'data_evento' => ($dataBaixa !== '' ? $dataBaixa : date('Y-m-d')) . ' 12:00:00',
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Baixa manual registrada com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível registrar a baixa manual do item.', 'lotOpsAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_item_baixa_revert_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $itemId = (int)($_POST['revert_item_id'] ?? 0);
  $quantidadeRevert = (float)lot_decimal_input($_POST['revert_quantidade'] ?? 0, 3);
  $observacaoRevert = trim((string)($_POST['revert_observacao'] ?? ''));
  $dataRevert = trim((string)($_POST['revert_data'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para reverter a baixa.', 'lotOpsAnchor');
  }

  $items = is_array($loadedLote['itens'] ?? null) ? (array)$loadedLote['itens'] : [];
  $target = null;
  foreach ($items as $candidate) {
    if (is_array($candidate) && (int)($candidate['id'] ?? 0) === $itemId) {
      $target = $candidate;
      break;
    }
  }
  if (!is_array($target)) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'O item selecionado para reversão não foi encontrado.', 'lotOpsAnchor');
  }

  $baixadaAtual = (float)($target['quantidadeBaixada'] ?? 0);
  if ($quantidadeRevert <= 0 || $quantidadeRevert > $baixadaAtual) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Informe uma quantidade válida para reverter a baixa.', 'lotOpsAnchor');
  }

  $nextItems = [];
  foreach ($items as $candidate) {
    if (!is_array($candidate)) {
      continue;
    }

    if ((int)($candidate['id'] ?? 0) !== $itemId) {
      $nextItems[] = $candidate;
      continue;
    }

    $quantidadeTotal = (float)($candidate['quantidadeTotal'] ?? 0);
    $quantidadeVendida = (float)($candidate['quantidadeVendida'] ?? 0);
    $novoBaixado = max(0, (float)($candidate['quantidadeBaixada'] ?? 0) - $quantidadeRevert);
    $novoDisponivel = max(0, $quantidadeTotal - $novoBaixado - $quantidadeVendida);

    $candidate['quantidadeBaixada'] = $novoBaixado;
    $candidate['quantidadeDisponivel'] = $novoDisponivel;
    $candidate['statusItem'] = $novoDisponivel > 0
      ? (($novoBaixado > 0 || $quantidadeVendida > 0) ? 'parcial' : 'ativo')
      : 'encerrado';
    $nextItems[] = $candidate;
  }

  try {
    $loteRepo->replaceItens($postedLoteId, $nextItems, 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'item_baixa_revertida',
      'descricao_evento' => 'Reversão de baixa do item: ' . (string)($target['descricaoItem'] ?? 'Item') . ' (' . lot_qty($quantidadeRevert) . ')',
      'payload_estrutural' => [
        'item_id' => $itemId,
        'descricao_item' => (string)($target['descricaoItem'] ?? ''),
        'tipo_controle_item' => lot_control_label((string)($target['tipoControleItem'] ?? '')),
        'quantidade_revertida' => $quantidadeRevert,
        'observacao' => $observacaoRevert,
      ],
      'data_evento' => ($dataRevert !== '' ? $dataRevert : date('Y-m-d')) . ' 12:00:00',
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Baixa revertida com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível reverter a baixa do item.', 'lotOpsAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_item_sell_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $saleMode = trim((string)($_POST['venda_modo'] ?? 'item'));
  $saleItemId = (int)($_POST['venda_item_id'] ?? 0);
  $saleClientId = (int)($_POST['cliente_id'] ?? 0);
  $saleQty = (float)lot_decimal_input($_POST['venda_quantidade'] ?? 0, 3);
  $saleUnit = (float)lot_decimal_input($_POST['venda_valor_unitario'] ?? 0, 2);
  $saleDate = trim((string)($_POST['venda_data'] ?? ''));
  $saleObs = trim((string)($_POST['venda_observacao'] ?? ''));
  $saleForma = trim((string)($_POST['venda_forma_pagamento'] ?? ''));
  $saleParcelas = max(1, min(12, (int)($_POST['venda_parcelas'] ?? 1)));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  $saleRedirect = static function (string $kind, string $message) use ($postedLoteId): never {
    $_SESSION['lot_open_modal'] = 'venda';
    lot_redirect_with_flash($postedLoteId, $kind, $message, 'lotOpsAnchor');
  };
  if (!is_array($loadedLote)) {
    $saleRedirect('danger', 'Não foi possível localizar o lote para registrar a venda.');
  }
  if ($saleClientId <= 0) {
    $saleRedirect('warning', 'Selecione o cliente da venda antes de concluir a operação.');
  }
  if ($saleForma === '') {
    $saleRedirect('warning', 'Selecione a forma de pagamento da venda.');
  }

  $saleClient = $cadastroRepo->findById($saleClientId, 1);
  if (!is_array($saleClient)) {
    $saleRedirect('warning', 'Não foi possível localizar o cadastro selecionado para a venda.');
  }

  $items = is_array($loadedLote['itens'] ?? null) ? (array)$loadedLote['itens'] : [];
  $nextItems = [];
  $movements = [];
  $hasSale = false;

  foreach ($items as $candidate) {
    if (!is_array($candidate)) {
      continue;
    }

    $itemId = (int)($candidate['id'] ?? 0);
    $descricao = (string)($candidate['descricaoItem'] ?? 'Item');
    $disponivel = (float)($candidate['quantidadeDisponivel'] ?? 0);
    $quantidadeTotal = (float)($candidate['quantidadeTotal'] ?? 0);
    $quantidadeBaixada = (float)($candidate['quantidadeBaixada'] ?? 0);
    $quantidadeVendida = (float)($candidate['quantidadeVendida'] ?? 0);
    $shouldApply = $saleMode === 'lote_total' ? $disponivel > 0 : ($itemId === $saleItemId);

    if (!$shouldApply) {
      $nextItems[] = $candidate;
      continue;
    }

    $targetQty = $saleMode === 'lote_total' ? $disponivel : $saleQty;
    if ($targetQty <= 0 || $targetQty > $disponivel) {
      $saleRedirect('warning', 'Informe uma quantidade de venda compatível com o disponível do item.');
    }

    $novoVendido = $quantidadeVendida + $targetQty;
    $novoDisponivel = max(0, $quantidadeTotal - $quantidadeBaixada - $novoVendido);
    $candidate['quantidadeVendida'] = $novoVendido;
    $candidate['quantidadeDisponivel'] = $novoDisponivel;
    $candidate['statusItem'] = $novoDisponivel > 0 ? 'parcial' : 'encerrado';
    $nextItems[] = $candidate;
    $hasSale = true;

    $unitPrice = $saleMode === 'lote_total'
      ? (float)($candidate['valorVendaUnitarioSugerido'] ?? 0)
      : $saleUnit;
    $saleId = 'LOTSALE-' . $postedLoteId . '-' . time() . '-' . $itemId . '-' . random_int(100, 999);
    $movements[] = [
      'sale_id' => $saleId,
      'tipo_evento' => 'item_venda',
      'descricao_evento' => 'Venda do item: ' . $descricao . ' (' . lot_qty($targetQty) . ')',
      'payload_estrutural' => [
        'sale_id' => $saleId,
        'item_id' => $itemId,
        'cliente_id' => $saleClientId,
        'cliente_nome' => (string)($saleClient['nome'] ?? $saleClient['razaoSocial'] ?? 'Cliente'),
        'cliente_documento' => (string)($saleClient['documento'] ?? ''),
        'descricao_item' => $descricao,
        'tipo_controle_item' => lot_control_label((string)($candidate['tipoControleItem'] ?? '')),
        'quantidade_vendida' => $targetQty,
        'valor_unitario_vendido' => $unitPrice,
        'valor_total_vendido' => $targetQty * $unitPrice,
        'forma_pagamento' => $saleForma,
        'parcelas' => $saleParcelas,
        'modo' => $saleMode,
        'observacao' => $saleObs,
      ],
      'data_evento' => ($saleDate !== '' ? $saleDate : date('Y-m-d')) . ' 12:00:00',
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ];
  }

  if (!$hasSale) {
    $saleRedirect('warning', 'Nenhum item disponível foi encontrado para registrar a venda.');
  }

  try {
    $loteRepo->replaceItens($postedLoteId, $nextItems, 1);
    $crRows = [];
    foreach ($movements as $movement) {
      $loteRepo->addMovimentacao($postedLoteId, $movement, 1);
      $payload = (array)($movement['payload_estrutural'] ?? []);
      $isTerm = lot_payment_is_term($saleForma, $saleParcelas);
      $baseValue = (float)($payload['valor_total_vendido'] ?? 0);
      $groupId = $saleParcelas > 1 ? ('LOTSALE-GRP-' . md5((string)($movement['sale_id'] ?? ''))) : '';
      $parcelValue = $saleParcelas > 0 ? round($baseValue / $saleParcelas, 2) : $baseValue;
      for ($parcelIndex = 1; $parcelIndex <= $saleParcelas; $parcelIndex++) {
        $processoRef = trim((string)($loadedLote['numeroProcesso'] ?? ''));
        if ($processoRef === '') {
          $processoRef = trim((string)($loadedLote['numeroSinistro'] ?? ''));
        }
        $crRows[] = [
          'id' => (string)($movement['sale_id'] ?? 'LOTSALE') . '-P' . $parcelIndex,
          'loteId' => $postedLoteId,
          'cadastroId' => $saleClientId,
          'cliente' => (string)($saleClient['nome'] ?? $saleClient['razaoSocial'] ?? 'Cliente'),
          'clienteDocumento' => (string)($saleClient['documento'] ?? ''),
          'produto' => (string)($payload['descricao_item'] ?? ''),
          'valor' => $parcelValue,
          'data' => lot_add_months_iso($saleDate !== '' ? $saleDate : date('Y-m-d'), $parcelIndex - 1),
          'forma' => $saleForma,
          'processo' => $processoRef,
          'obs' => $saleObs !== '' ? $saleObs : ('Venda do lote ' . (string)($loadedLote['tituloLote'] ?? '')),
          'totalParcelas' => $saleParcelas,
          'parcelaAtual' => $parcelIndex,
          'grupoParcelaId' => $groupId,
          'status' => $isTerm ? 'open' : 'done',
        ];
      }
    }
    $_SESSION['lot_cr_sync_rows'] = $crRows;
    $_SESSION['lot_open_modal'] = 'venda';
    lot_redirect_with_flash($postedLoteId, 'success', 'Venda registrada com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    $saleRedirect('danger', 'Não foi possível registrar a venda do item.');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_item_sale_return_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $saleRef = trim((string)($_POST['devolucao_sale_ref'] ?? ''));
  $returnQty = (float)lot_decimal_input($_POST['devolucao_quantidade'] ?? 0, 3);
  $returnDate = trim((string)($_POST['devolucao_data'] ?? ''));
  $returnObs = trim((string)($_POST['devolucao_observacao'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  $returnRedirect = static function (string $kind, string $message) use ($postedLoteId): never {
    $_SESSION['lot_open_modal'] = 'venda';
    lot_redirect_with_flash($postedLoteId, $kind, $message, 'lotOpsAnchor');
  };

  if (!is_array($loadedLote)) {
    $returnRedirect('danger', 'Não foi possível localizar o lote para registrar a devolução.');
  }
  if ($saleRef === '') {
    $returnRedirect('warning', 'Não foi possível localizar a venda selecionada para devolução.');
  }

  $movimentacoes = is_array($loadedLote['movimentacoes'] ?? null) ? (array)$loadedLote['movimentacoes'] : [];
  $saleMovement = null;
  $salePayload = [];
  $alreadyReturnedQty = 0.0;

  foreach ($movimentacoes as $movimentacao) {
    if (!is_array($movimentacao)) {
      continue;
    }
    $payload = is_array($movimentacao['payloadEstrutural'] ?? null) ? (array)$movimentacao['payloadEstrutural'] : [];
    $movementRef = lot_sale_reference($movimentacao, $payload);
    if ((string)($movimentacao['tipoEvento'] ?? '') === 'item_venda' && $movementRef === $saleRef) {
      $saleMovement = $movimentacao;
      $salePayload = $payload;
      continue;
    }
    if ((string)($movimentacao['tipoEvento'] ?? '') === 'item_venda_devolucao' && trim((string)($payload['sale_ref'] ?? '')) === $saleRef) {
      $alreadyReturnedQty += (float)($payload['quantidade_devolvida'] ?? 0);
    }
  }

  if (!is_array($saleMovement) || $salePayload === []) {
    $returnRedirect('warning', 'A venda selecionada não foi encontrada para registrar a devolução.');
  }

  $itemId = (int)($salePayload['item_id'] ?? 0);
  $saleDescricao = trim((string)($salePayload['descricao_item'] ?? ''));
  $saleTipoControle = trim((string)($salePayload['tipo_controle_item'] ?? ''));
  $soldQty = (float)($salePayload['quantidade_vendida'] ?? 0);
  $unitValue = (float)($salePayload['valor_unitario_vendido'] ?? 0);
  $saldoQty = max(0.0, $soldQty - $alreadyReturnedQty);
  if ($returnQty <= 0 || $returnQty > $saldoQty) {
    $returnRedirect('warning', 'Informe uma quantidade de devolução compatível com o saldo disponível desta venda.');
  }

  $items = is_array($loadedLote['itens'] ?? null) ? (array)$loadedLote['itens'] : [];
  $nextItems = [];
  $foundItem = false;
  foreach ($items as $candidate) {
    if (!is_array($candidate)) {
      continue;
    }
    $candidateDescricao = trim((string)($candidate['descricaoItem'] ?? ''));
    $candidateTipo = lot_control_label((string)($candidate['tipoControleItem'] ?? ''));
    $sameItemById = $itemId > 0 && (int)($candidate['id'] ?? 0) === $itemId;
    $sameItemByIdentity = $saleDescricao !== ''
      && strcasecmp($candidateDescricao, $saleDescricao) === 0
      && ($saleTipoControle === '' || strcasecmp($candidateTipo, $saleTipoControle) === 0);

    if (!$sameItemById && !$sameItemByIdentity) {
      $nextItems[] = $candidate;
      continue;
    }
    $foundItem = true;
    $quantidadeTotal = (float)($candidate['quantidadeTotal'] ?? 0);
    $quantidadeBaixada = (float)($candidate['quantidadeBaixada'] ?? 0);
    $quantidadeVendida = max(0.0, (float)($candidate['quantidadeVendida'] ?? 0) - $returnQty);
    $quantidadeDisponivel = max(0.0, $quantidadeTotal - $quantidadeBaixada - $quantidadeVendida);
    $candidate['quantidadeVendida'] = $quantidadeVendida;
    $candidate['quantidadeDisponivel'] = $quantidadeDisponivel;
    $candidate['statusItem'] = $quantidadeDisponivel > 0
      ? (($quantidadeBaixada > 0 || $quantidadeVendida > 0) ? 'parcial' : 'ativo')
      : 'encerrado';
    $nextItems[] = $candidate;
  }

  if (!$foundItem) {
    $returnRedirect('warning', 'O item vinculado a esta venda não foi encontrado no lote.');
  }

  $returnValue = round($returnQty * $unitValue, 2);

  try {
    $loteRepo->replaceItens($postedLoteId, $nextItems, 1);
    $returnMovement = $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'item_venda_devolucao',
      'descricao_evento' => 'Devolução da venda: ' . (string)($salePayload['descricao_item'] ?? 'Item') . ' (' . lot_qty($returnQty) . ')',
      'payload_estrutural' => [
        'sale_ref' => $saleRef,
        'sale_id' => (string)($salePayload['sale_id'] ?? ''),
        'item_id' => $itemId,
        'descricao_item' => (string)($salePayload['descricao_item'] ?? ''),
        'tipo_controle_item' => (string)($salePayload['tipo_controle_item'] ?? ''),
        'cliente_id' => (int)($salePayload['cliente_id'] ?? 0),
        'cliente_nome' => (string)($salePayload['cliente_nome'] ?? ''),
        'cliente_documento' => (string)($salePayload['cliente_documento'] ?? ''),
        'quantidade_devolvida' => $returnQty,
        'valor_unitario_devolvido' => $unitValue,
        'valor_total_devolvido' => $returnValue,
        'saldo_quantidade_venda' => max(0.0, $saldoQty - $returnQty),
        'observacao' => $returnObs,
      ],
      'data_evento' => ($returnDate !== '' ? $returnDate : date('Y-m-d')) . ' 12:00:00',
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    $processoRef = trim((string)($loadedLote['numeroProcesso'] ?? ''));
    if ($processoRef === '') {
      $processoRef = trim((string)($loadedLote['numeroSinistro'] ?? ''));
    }
    $_SESSION['lot_finance_sync'] = [
      'type' => 'sale_return',
      'movementId' => (int)($returnMovement['id'] ?? 0),
      'saleRef' => $saleRef,
      'loteId' => $postedLoteId,
      'processo' => $processoRef,
      'itemDescription' => (string)($salePayload['descricao_item'] ?? ''),
      'clientId' => (int)($salePayload['cliente_id'] ?? 0),
      'clientName' => (string)($salePayload['cliente_nome'] ?? ''),
      'clientDocument' => (string)($salePayload['cliente_documento'] ?? ''),
      'returnValue' => $returnValue,
      'returnDate' => ($returnDate !== '' ? $returnDate : date('Y-m-d')),
      'observacao' => $returnObs,
    ];
    $_SESSION['lot_open_modal'] = 'venda';
    lot_redirect_with_flash($postedLoteId, 'success', 'Devolução registrada com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    $returnRedirect('danger', 'Não foi possível registrar a devolução desta venda.');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_baixa_total_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $baixaDate = trim((string)($_POST['baixa_total_data'] ?? ''));
  $baixaObs = trim((string)($_POST['baixa_total_observacao'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para registrar a baixa total.', 'lotOpsAnchor');
  }

  $items = is_array($loadedLote['itens'] ?? null) ? (array)$loadedLote['itens'] : [];
  $nextItems = [];
  $hasBaixa = false;

  foreach ($items as $candidate) {
    if (!is_array($candidate)) {
      continue;
    }
    $disponivel = (float)($candidate['quantidadeDisponivel'] ?? 0);
    if ($disponivel <= 0) {
      $nextItems[] = $candidate;
      continue;
    }
    $candidate['quantidadeBaixada'] = (float)($candidate['quantidadeBaixada'] ?? 0) + $disponivel;
    $candidate['quantidadeDisponivel'] = 0.0;
    $candidate['statusItem'] = 'encerrado';
    $nextItems[] = $candidate;
    $hasBaixa = true;
  }

  if (!$hasBaixa) {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Não existem itens disponíveis para baixa total neste lote.', 'lotOpsAnchor');
  }

  try {
    $loteRepo->replaceItens($postedLoteId, $nextItems, 1);
    foreach ($nextItems as $candidate) {
      if (!is_array($candidate)) {
        continue;
      }
      $payloadQty = 0.0;
      $original = null;
      foreach ($items as $oldCandidate) {
        if (is_array($oldCandidate) && (int)($oldCandidate['id'] ?? 0) === (int)($candidate['id'] ?? 0)) {
          $original = $oldCandidate;
          break;
        }
      }
      if (!is_array($original)) {
        continue;
      }
      $payloadQty = (float)($original['quantidadeDisponivel'] ?? 0);
      if ($payloadQty <= 0) {
        continue;
      }
      $loteRepo->addMovimentacao($postedLoteId, [
        'tipo_evento' => 'lote_baixa_total_item',
        'descricao_evento' => 'Baixa total do lote no item: ' . (string)($original['descricaoItem'] ?? 'Item') . ' (' . lot_qty($payloadQty) . ')',
        'payload_estrutural' => [
          'item_id' => (int)($original['id'] ?? 0),
          'descricao_item' => (string)($original['descricaoItem'] ?? ''),
          'tipo_controle_item' => lot_control_label((string)($original['tipoControleItem'] ?? '')),
          'quantidade_baixada' => $payloadQty,
          'observacao' => $baixaObs,
          'modo' => 'lote_total',
        ],
        'data_evento' => ($baixaDate !== '' ? $baixaDate : date('Y-m-d')) . ' 12:00:00',
        'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
      ], 1);
    }
    lot_redirect_with_flash($postedLoteId, 'success', 'Baixa total do lote registrada com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível registrar a baixa total do lote.', 'lotOpsAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_tag_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $tagNome = trim((string)($_POST['tag_nome'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar as tags.', 'lotOpsAnchor');
  }
  if ($tagNome === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Digite uma tag antes de adicionar.', 'lotOpsAnchor');
  }

  $tags = array_map(
    static fn (array $tag): string => (string)($tag['nome'] ?? ''),
    array_values(array_filter((array)($loadedLote['tags'] ?? []), 'is_array'))
  );
  $tags[] = $tagNome;

  try {
    $loteRepo->replaceTags($postedLoteId, $tags, 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'tag_lote_adicionada',
      'descricao_evento' => 'Tag adicionada ao lote: ' . $tagNome,
      'payload_estrutural' => ['tag' => $tagNome],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Tag adicionada com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível adicionar a tag do lote.', 'lotOpsAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_tag_remove_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $tagSlug = trim((string)($_POST['tag_slug'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para remover a tag.', 'lotOpsAnchor');
  }

  $filteredTags = [];
  $removedLabel = '';
  foreach ((array)($loadedLote['tags'] ?? []) as $tag) {
    if (!is_array($tag)) {
      continue;
    }
    if ((string)($tag['slug'] ?? '') === $tagSlug) {
      $removedLabel = (string)($tag['nome'] ?? '');
      continue;
    }
    $filteredTags[] = (string)($tag['nome'] ?? '');
  }

  try {
    $loteRepo->replaceTags($postedLoteId, $filteredTags, 1);
    if ($removedLabel !== '') {
      $loteRepo->addMovimentacao($postedLoteId, [
        'tipo_evento' => 'tag_lote_removida',
        'descricao_evento' => 'Tag removida do lote: ' . $removedLabel,
        'payload_estrutural' => ['tag' => $removedLabel],
        'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
      ], 1);
    }
    lot_redirect_with_flash($postedLoteId, 'success', 'Tag removida com sucesso.', 'lotOpsAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível remover a tag do lote.', 'lotOpsAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_freight_tag_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $tagNome = lot_upper_text((string)($_POST['tag_nome'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para atualizar as tags do frete.', 'lotFreightAnchor');
  }
  if ($tagNome === '') {
    lot_redirect_with_flash($postedLoteId, 'warning', 'Digite uma tag de frete antes de adicionar.', 'lotFreightAnchor');
  }

  $payload = lot_build_update_payload($loadedLote);
  $freightTags = lot_extract_freight_tags((string)($loadedLote['observacoesLogisticas'] ?? ''));
  $freightTags[] = ['nome' => $tagNome, 'slug' => lot_normalize_slug($tagNome)];
  $valorImpostos = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Impostos frete:'), 2);
  $valorOutrosFrete = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Outros frete:'), 2);
  $payload['observacoes_logisticas'] = lot_build_logistic_notes(
    $valorImpostos,
    $valorOutrosFrete,
    $freightTags,
    lot_strip_structured_logistic_lines((string)($loadedLote['observacoesLogisticas'] ?? ''))
  );

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    $loteRepo->addMovimentacao($postedLoteId, [
      'tipo_evento' => 'tag_frete_adicionada',
      'descricao_evento' => 'Tag do frete adicionada: ' . $tagNome,
      'payload_estrutural' => ['tag_frete' => $tagNome],
      'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
    ], 1);
    lot_redirect_with_flash($postedLoteId, 'success', 'Tag do frete adicionada com sucesso.', 'lotFreightAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível adicionar a tag do frete.', 'lotFreightAnchor');
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && trim((string)($_POST['lot_freight_tag_remove_submit'] ?? '')) === '1') {
  $postedLoteId = (int)($_POST['lote_id'] ?? 0);
  $tagSlug = trim((string)($_POST['tag_slug'] ?? ''));
  $loadedLote = $postedLoteId > 0 ? $loteRepo->findById($postedLoteId, 1, true) : null;
  if (!is_array($loadedLote)) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível localizar o lote para remover a tag do frete.', 'lotFreightAnchor');
  }

  $freightTags = lot_extract_freight_tags((string)($loadedLote['observacoesLogisticas'] ?? ''));
  $filteredTags = [];
  $removedLabel = '';
  foreach ($freightTags as $tag) {
    if (!is_array($tag)) {
      continue;
    }
    if ((string)($tag['slug'] ?? '') === $tagSlug) {
      $removedLabel = (string)($tag['nome'] ?? '');
      continue;
    }
    $filteredTags[] = (string)($tag['nome'] ?? '');
  }

  $payload = lot_build_update_payload($loadedLote);
  $valorImpostos = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Impostos frete:'), 2);
  $valorOutrosFrete = (float)lot_decimal_input(lot_extract_labeled_line((string)($loadedLote['observacoesLogisticas'] ?? ''), 'Outros frete:'), 2);
  $payload['observacoes_logisticas'] = lot_build_logistic_notes(
    $valorImpostos,
    $valorOutrosFrete,
    $filteredTags,
    lot_strip_structured_logistic_lines((string)($loadedLote['observacoesLogisticas'] ?? ''))
  );

  try {
    $loteRepo->update($postedLoteId, $payload, 1);
    if ($removedLabel !== '') {
      $loteRepo->addMovimentacao($postedLoteId, [
        'tipo_evento' => 'tag_frete_removida',
        'descricao_evento' => 'Tag do frete removida: ' . $removedLabel,
        'payload_estrutural' => ['tag_frete' => $removedLabel],
        'responsavel' => trim((string)($_SESSION['auth_user']['name'] ?? 'Operação')),
      ], 1);
    }
    lot_redirect_with_flash($postedLoteId, 'success', 'Tag do frete removida com sucesso.', 'lotFreightAnchor');
  } catch (Throwable $e) {
    lot_redirect_with_flash($postedLoteId, 'danger', 'Não foi possível remover a tag do frete.', 'lotFreightAnchor');
  }
}

if ($createMode) {
  $fornecedoresCadastro = $cadastroRepo->list(['status' => 'ativo', 'limit' => 400], 1);
  ?>
  <div
    class="module-page lot-page"
    <?= $timelineFlashMessage !== '' ? 'data-lot-page-flash="' . h($timelineFlashMessage) . '" data-lot-page-flash-kind="' . h($timelineFlashKind !== '' ? $timelineFlashKind : 'info') . '"' : '' ?>
  >
    <div class="module-head lot-page__head">
      <div class="lot-head__topline">
        <div class="lot-page__eyebrow">Módulo Lotes</div>

        <nav class="lot-crumbs" aria-label="Navegação do módulo Lotes">
          <a
            class="lot-crumbs__back"
            href="<?= h(app_url('/app/templates/lotes.php')) ?>"
            data-tip="Voltar"
          >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </a>

          <div class="lot-crumbs__trail">
            <a href="<?= h(app_url('/app/templates/lotes.php')) ?>">Lotes</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span>Novo lote</span>
          </div>
        </nav>
      </div>
      <h1>Novo lote</h1>
      <p>Cadastro inicial do processo para acompanhar o lote desde o nascimento e seguir a operação pela ficha interna.</p>
    </div>

    <section class="admin-block lot-detail__section">
      <div class="admin-block-head">
        <h2 class="admin-block-title"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Cadastro inicial do lote</span></h2>
      </div>
      <div class="admin-block-body">
        <form class="lot-item-form" method="post" action="<?= h(lot_module_url(['novo' => 1])) ?>">
          <input type="hidden" name="lot_create_submit" value="1">

          <div class="lot-item-form__grid">
            <label class="lot-field lot-item-form__field lot-item-form__field--wide">
              <span>Seguradora</span>
              <select name="fornecedor_id" required>
                <option value="">Selecione</option>
                <?php foreach ($fornecedoresCadastro as $fornecedorOption): ?>
                  <?php if (!is_array($fornecedorOption)) { continue; } ?>
                  <?php $labelFornecedor = trim((string)($fornecedorOption['nome'] ?? $fornecedorOption['razaoSocial'] ?? '')); ?>
                  <?php if ($labelFornecedor === '') { continue; } ?>
                  <option value="<?= h((string)($fornecedorOption['id'] ?? 0)) ?>"><?= h($labelFornecedor) ?></option>
                <?php endforeach; ?>
              </select>
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Número do processo</span>
              <input type="text" name="numero_processo" maxlength="80" placeholder="Ex.: LT-2026-001">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Data da compra</span>
              <input type="date" name="data_compra" value="<?= h(date('Y-m-d')) ?>" required>
            </label>

            <label class="lot-field lot-item-form__field lot-item-form__field--wide">
              <span>Título do lote</span>
              <input type="text" name="titulo_lote" maxlength="160" placeholder="Ex.: lote de ferragens industriais" required>
            </label>

            <label class="lot-field lot-item-form__field lot-item-form__field--wide">
              <span>Descrição resumida</span>
              <input type="text" name="descricao_resumida" maxlength="180" placeholder="Resumo curto para identificar rapidamente o processo.">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Valor do salvado</span>
              <input type="text" name="valor_salvado" data-lot-money inputmode="decimal" placeholder="R$ 0,00">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Valor da compra</span>
              <input type="text" name="valor_pago_compra" data-lot-money inputmode="decimal" placeholder="R$ 0,00">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Pagamento da compra</span>
              <select name="status_pagamento_compra">
                <option value="pendente" selected>Pendente</option>
                <option value="pago">Pago</option>
              </select>
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Data do pagamento</span>
              <input type="date" name="data_pagamento_compra">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Local de armazenagem</span>
              <input type="text" name="nome_local" maxlength="120" placeholder="Ex.: pátio central">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Cidade</span>
              <input type="text" name="cidade" maxlength="80" placeholder="Cidade de coleta / armazenagem">
            </label>

            <label class="lot-field lot-item-form__field">
              <span>Estado</span>
              <select name="estado">
                <option value="">Selecione</option>
                <?php foreach (lot_ufs() as $uf => $ufLabel): ?>
                  <option value="<?= h($uf) ?>"><?= h($uf) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>

          <label class="lot-field">
            <span>Observações iniciais</span>
            <textarea class="lot-timeline-form__textarea" name="observacoes_gerais" rows="4" placeholder="Use este campo para registrar o contexto inicial do processo."></textarea>
          </label>

          <div class="lot-item-form__actions">
            <a class="fin-btn fin-btn--ghost" href="<?= h(lot_module_url()) ?>">Cancelar</a>
            <button class="fin-btn" type="submit">Criar lote</button>
          </div>
        </form>
      </div>
    </section>
  </div>
  <?php
  return;
}

if ($viewLoteId > 0) {
  $selectedLote = $loteRepo->findById($viewLoteId, 1, true);
  $selectedFornecedor = is_array($selectedLote)
    ? $cadastroRepo->findById((int)($selectedLote['fornecedorId'] ?? 0), 1)
    : null;
  $selectedMotorista = is_array($selectedLote) && (int)($selectedLote['motoristaId'] ?? 0) > 0
    ? $cadastroRepo->findById((int)$selectedLote['motoristaId'], 1)
    : null;
  $selectedTransportadora = is_array($selectedLote) && (int)($selectedLote['transportadoraId'] ?? 0) > 0
    ? $cadastroRepo->findById((int)$selectedLote['transportadoraId'], 1)
    : null;
  $cadastroModalAvatarMap = [
    'cliente' => app_url('/app/static/img/avatar-cliente.png'),
    'fornecedor' => app_url('/app/static/img/avatar-fornecedor.png'),
    'motorista' => app_url('/app/static/img/avatar-motorista.png'),
    'transportadora' => app_url('/app/static/img/avatar-transportadora.png'),
  ];
  $selectedCadastrosLookup = $cadastroRepo->list(['status' => 'ativo', 'limit' => 400], 1);
  $cadastroModalItens = array_values(array_filter(array_map(
    static function (array $cadastro) use ($cadastroRepo, $arquivoRepo): ?array {
      $id = (int)($cadastro['id'] ?? 0);
      if ($id <= 0) {
        return null;
      }

      $detalhado = $cadastroRepo->findById($id, 1);
      if (!is_array($detalhado)) {
        return null;
      }

      $detalhado['anexos'] = cad_present_anexos($arquivoRepo->listByEntity('cadastros', $id, 1));
      return $detalhado;
    },
    $selectedCadastrosLookup
  ), 'is_array'));
  $selectedFreightCadastros = array_values(array_filter(
    $cadastroModalItens,
    static fn ($cadastro): bool => is_array($cadastro) && lot_is_freight_cadastro($cadastro)
  ));
  $cadastroModalItensJson = json_encode($cadastroModalItens, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  $cadastroModalAvatarJson = json_encode($cadastroModalAvatarMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  $selectedItens = is_array($selectedLote) ? (array)($selectedLote['itens'] ?? []) : [];
  $selectedTags = is_array($selectedLote) ? (array)($selectedLote['tags'] ?? []) : [];
  $selectedMovimentacoes = is_array($selectedLote) ? (array)($selectedLote['movimentacoes'] ?? []) : [];
  $selectedPurchasePayment = is_array($selectedLote) ? lot_purchase_payment_fetch_config($viewLoteId, 1) : ['status' => 'pendente', 'paidAt' => '', 'updatedAt' => ''];
  $selectedPurchasePaymentLabel = lot_purchase_payment_label((string)($selectedPurchasePayment['status'] ?? 'pendente'));
  $selectedPurchaseOpenAmount = is_array($selectedLote) ? lot_purchase_payment_open_amount($selectedLote, $selectedPurchasePayment) : 0.0;
  $selectedPublicConfig = is_array($selectedLote) ? lot_public_fetch_config($viewLoteId, 1) : ['published' => false, 'token' => '', 'updatedAt' => ''];
  $selectedPublicToken = trim((string)($selectedPublicConfig['token'] ?? ''));
  $selectedPublicUrl = $selectedPublicToken !== '' ? lot_public_url($viewLoteId, $selectedPublicToken) : '';
  $selectedPublicPrintUrl = $selectedPublicToken !== '' ? lot_public_print_url($viewLoteId, $selectedPublicToken) : '';
  $selectedClientes = $cadastroRepo->list(['tipo' => 'cliente', 'status' => 'ativo', 'limit' => 300], 1);
  $selectedClientesCompatíveis = lot_find_compatible_clients(
    $selectedTags,
    $cadastroRepo->list(['status' => 'ativo', 'limit' => 400], 1),
    $cadastroRepo,
    1
  );
  $selectedFreightSuggestions = lot_find_freight_suggestions($selectedLote, $selectedFreightCadastros, $cadastroRepo, 1);
  $selectedFreightLinked = is_array($selectedTransportadora) ? $selectedTransportadora : $selectedMotorista;
  $selectedFreightLinkedMeta = lot_freight_card_meta($selectedFreightLinked);
  $selectedCrSyncRows = (array)($_SESSION['lot_cr_sync_rows'] ?? []);
  unset($_SESSION['lot_cr_sync_rows']);
  $selectedFinanceSync = $_SESSION['lot_finance_sync'] ?? null;
  unset($_SESSION['lot_finance_sync']);
  $attachmentGroupsMeta = lot_attachment_groups();
  $selectedAttachmentGroups = [];
  foreach ($attachmentGroupsMeta as $attachmentGroupKey => $attachmentMeta) {
    $selectedAttachmentGroups[$attachmentGroupKey] = [
      'key' => $attachmentGroupKey,
      'title' => (string)($attachmentMeta['title'] ?? 'Anexos'),
      'description' => (string)($attachmentMeta['description'] ?? ''),
      'icon' => (string)($attachmentMeta['icon'] ?? 'fa-solid fa-paperclip'),
      'empty' => (string)($attachmentMeta['empty'] ?? 'Nenhum anexo disponível.'),
      'items' => lot_present_anexos($arquivoRepo->listByEntity((string)($attachmentMeta['entity'] ?? ''), $viewLoteId, 1)),
    ];
  }
  usort($selectedMovimentacoes, static function (array $a, array $b): int {
    $createdA = strtotime((string)($a['createdAt'] ?? '')) ?: 0;
    $createdB = strtotime((string)($b['createdAt'] ?? '')) ?: 0;
    if ($createdA !== $createdB) {
      return $createdB <=> $createdA;
    }

    $timeA = strtotime((string)($a['dataEvento'] ?? '')) ?: 0;
    $timeB = strtotime((string)($b['dataEvento'] ?? '')) ?: 0;
    if ($timeA !== $timeB) {
      return $timeB <=> $timeA;
    }
    return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
  });
  $selectedMovimentacoesRecentes = array_slice($selectedMovimentacoes, 0, 8);
  $selectedItensAtivos = 0;
  $selectedItensValorTotal = 0.0;
  $selectedItemHistoryMap = [];
  $selectedItemHistoryByDescription = [];
  $selectedItemSoldQtyMap = [];
  $selectedItemReturnedQtyMap = [];
  $selectedVendas = [];
  $selectedVendasIndex = [];
  foreach ($selectedItens as $selectedItem) {
    if (!is_array($selectedItem)) {
      continue;
    }

    if ((float)($selectedItem['quantidadeDisponivel'] ?? 0) > 0) {
      $selectedItensAtivos++;
    }
    $selectedItensValorTotal += (float)($selectedItem['valorVendaTotalSugerido'] ?? 0);
  }

  foreach (array_reverse($selectedMovimentacoes) as $selectedMovimentacao) {
    if (!is_array($selectedMovimentacao)) {
      continue;
    }
    $payload = is_array($selectedMovimentacao['payloadEstrutural'] ?? null) ? (array)$selectedMovimentacao['payloadEstrutural'] : [];
    $historyItemId = (int)($payload['item_id'] ?? 0);
    $historyDescription = trim((string)($payload['descricao_item'] ?? ''));
    if ($historyItemId <= 0) {
      if ($historyDescription === '') {
        continue;
      }
      $selectedItemHistoryByDescription[$historyDescription] ??= [];
      $selectedItemHistoryByDescription[$historyDescription][] = $selectedMovimentacao;
      continue;
    }
    $selectedItemHistoryMap[$historyItemId] ??= [];
    $selectedItemHistoryMap[$historyItemId][] = $selectedMovimentacao;
    if ($historyDescription !== '') {
      $selectedItemHistoryByDescription[$historyDescription] ??= [];
      $selectedItemHistoryByDescription[$historyDescription][] = $selectedMovimentacao;
    }
    $saleRef = lot_sale_reference($selectedMovimentacao, $payload);
    if ((string)($selectedMovimentacao['tipoEvento'] ?? '') === 'item_venda') {
      $saleItemId = (int)($payload['item_id'] ?? 0);
      if ($saleItemId > 0) {
        $selectedItemSoldQtyMap[$saleItemId] = ($selectedItemSoldQtyMap[$saleItemId] ?? 0.0) + (float)($payload['quantidade_vendida'] ?? 0);
      }
      $selectedVendasIndex[$saleRef] = count($selectedVendas);
      $selectedVendas[] = [
        'saleRef' => $saleRef,
        'descricaoItem' => $historyDescription !== '' ? $historyDescription : 'Item',
        'tipoControleItem' => (string)($payload['tipo_controle_item'] ?? ''),
        'itemId' => (int)($payload['item_id'] ?? 0),
        'quantidadeVendida' => (float)($payload['quantidade_vendida'] ?? 0),
        'quantidadeDevolvida' => 0.0,
        'saldoDevolvivel' => (float)($payload['quantidade_vendida'] ?? 0),
        'valorUnitario' => (float)($payload['valor_unitario_vendido'] ?? 0),
        'valorTotal' => (float)($payload['valor_total_vendido'] ?? 0),
        'valorDevolvido' => 0.0,
        'valorLiquido' => (float)($payload['valor_total_vendido'] ?? 0),
        'formaPagamento' => (string)($payload['forma_pagamento'] ?? ''),
        'parcelas' => (int)($payload['parcelas'] ?? 1),
        'clienteNome' => (string)($payload['cliente_nome'] ?? ''),
        'clienteDocumento' => (string)($payload['cliente_documento'] ?? ''),
        'dataEvento' => (string)($selectedMovimentacao['dataEvento'] ?? ''),
      ];
      continue;
    }

    if ((string)($selectedMovimentacao['tipoEvento'] ?? '') === 'item_venda_devolucao') {
      $returnRef = trim((string)($payload['sale_ref'] ?? ''));
      $returnItemId = (int)($payload['item_id'] ?? 0);
      if ($returnItemId > 0) {
        $selectedItemReturnedQtyMap[$returnItemId] = ($selectedItemReturnedQtyMap[$returnItemId] ?? 0.0) + (float)($payload['quantidade_devolvida'] ?? 0);
      }
      if ($returnRef === '' || !array_key_exists($returnRef, $selectedVendasIndex)) {
        continue;
      }
      $saleIndex = $selectedVendasIndex[$returnRef];
      $selectedVendas[$saleIndex]['quantidadeDevolvida'] += (float)($payload['quantidade_devolvida'] ?? 0);
      $selectedVendas[$saleIndex]['valorDevolvido'] += (float)($payload['valor_total_devolvido'] ?? 0);
    }
  }

  foreach ($selectedVendas as &$selectedVenda) {
    $selectedVenda['saldoDevolvivel'] = max(0.0, (float)$selectedVenda['quantidadeVendida'] - (float)$selectedVenda['quantidadeDevolvida']);
    $selectedVenda['valorLiquido'] = max(0.0, (float)$selectedVenda['valorTotal'] - (float)$selectedVenda['valorDevolvido']);
    $selectedVenda['devolucaoStatus'] = (float)$selectedVenda['quantidadeDevolvida'] <= 0
      ? 'Sem devolução'
      : ((float)$selectedVenda['saldoDevolvivel'] > 0 ? 'Devolução parcial' : 'Devolução total');
  }
  unset($selectedVenda);

  $selectedItemSalesByItem = [];
  $selectedItemSalesByDescription = [];
  foreach ($selectedVendas as $selectedVenda) {
    if (!is_array($selectedVenda)) {
      continue;
    }
    $saleItemId = (int)($selectedVenda['itemId'] ?? 0);
    $saleDescription = trim((string)($selectedVenda['descricaoItem'] ?? ''));
    if ($saleItemId <= 0) {
      if ($saleDescription === '') {
        continue;
      }
    } else {
      $selectedItemSalesByItem[$saleItemId] ??= [
        'gross' => 0.0,
        'returned' => 0.0,
      ];
      $selectedItemSalesByItem[$saleItemId]['gross'] += (float)($selectedVenda['quantidadeVendida'] ?? 0);
      $selectedItemSalesByItem[$saleItemId]['returned'] += (float)($selectedVenda['quantidadeDevolvida'] ?? 0);
    }
    if ($saleDescription === '') {
      continue;
    }
    $selectedItemSalesByDescription[$saleDescription] ??= [
      'gross' => 0.0,
      'returned' => 0.0,
    ];
    $selectedItemSalesByDescription[$saleDescription]['gross'] += (float)($selectedVenda['quantidadeVendida'] ?? 0);
    $selectedItemSalesByDescription[$saleDescription]['returned'] += (float)($selectedVenda['quantidadeDevolvida'] ?? 0);
  }

  $selectedCidadeEstado = '';
  if (is_array($selectedLote)) {
    $selectedCidadeEstado = trim(
      trim((string)($selectedLote['cidade'] ?? '')) . ' / ' . trim((string)($selectedLote['estado'] ?? '')),
      ' /'
    );
  }

  $selectedResumo = is_array($selectedLote)
    ? trim((string)($selectedLote['descricaoResumida'] ?? ''))
    : '';
  if ($selectedResumo === '' && is_array($selectedLote)) {
    $selectedResumo = trim((string)($selectedLote['descricaoOperacional'] ?? ''));
  }
  $selectedValorVendidoAtual = 0.0;
  foreach ($selectedMovimentacoes as $selectedMovimentacao) {
    $selectedValorVendidoAtual += lot_sale_delta_value($selectedMovimentacao);
  }
  $selectedValorBrutoVendido = array_reduce($selectedVendas, static function (float $carry, array $selectedVenda): float {
    return $carry + (float)($selectedVenda['valorTotal'] ?? 0);
  }, 0.0);
  $selectedValorDevolvido = array_reduce($selectedVendas, static function (float $carry, array $selectedVenda): float {
    return $carry + (float)($selectedVenda['valorDevolvido'] ?? 0);
  }, 0.0);
  $selectedResultadoParcial = $selectedValorVendidoAtual - (float)($selectedLote['custoTotal'] ?? 0);
  $selectedTitulo = trim((string)($selectedLote['tituloLote'] ?? ''));
  $selectedNumeroSinistro = trim((string)($selectedLote['numeroSinistro'] ?? ''));
  if ($selectedNumeroSinistro === '') {
    $selectedNumeroSinistro = lot_extract_labeled_line((string)($selectedLote['observacoesGerais'] ?? ''), 'Sinistro:');
  }
  $selectedFornecedorNome = (string)($selectedFornecedor['nome'] ?? $selectedFornecedor['razaoSocial'] ?? 'Fornecedor não identificado');
  $selectedProcessoNumero = lot_text_or_default((string)($selectedLote['numeroProcesso'] ?? ''), '-');
  $selectedObservacoesGeraisLivres = lot_strip_labeled_lines((string)($selectedLote['observacoesGerais'] ?? ''), ['Sinistro:']);
  $selectedCpfCnpjLocal = lot_extract_labeled_line((string)($selectedLote['observacoesLocal'] ?? ''), 'CPF/CNPJ local:');
  $selectedTelefoneDois = lot_extract_labeled_line((string)($selectedLote['observacoesLocal'] ?? ''), 'Telefone 2:');
  $selectedCustoArmazenagem = (float)lot_decimal_input(lot_extract_labeled_line((string)($selectedLote['observacoesLocal'] ?? ''), 'Armazenagem:'), 2);
  $selectedCustoCarregamento = (float)lot_decimal_input(lot_extract_labeled_line((string)($selectedLote['observacoesLocal'] ?? ''), 'Carregamento:'), 2);
  $selectedCustoSos = (float)lot_decimal_input(lot_extract_labeled_line((string)($selectedLote['observacoesLocal'] ?? ''), 'SOS:'), 2);
  $selectedOutrosLocais = (float)lot_decimal_input(lot_extract_labeled_line((string)($selectedLote['observacoesLocal'] ?? ''), 'Outros locais:'), 2);
  $selectedObservacoesLocalLivres = lot_strip_structured_local_lines((string)($selectedLote['observacoesLocal'] ?? ''));
  $selectedCustosLocaisTotal = $selectedCustoArmazenagem + $selectedCustoCarregamento + $selectedCustoSos + $selectedOutrosLocais;
  $selectedFreightImpostos = (float)lot_decimal_input(lot_extract_labeled_line((string)($selectedLote['observacoesLogisticas'] ?? ''), 'Impostos frete:'), 2);
  $selectedFreightOutros = (float)lot_decimal_input(lot_extract_labeled_line((string)($selectedLote['observacoesLogisticas'] ?? ''), 'Outros frete:'), 2);
  $selectedFreightTags = lot_extract_freight_tags((string)($selectedLote['observacoesLogisticas'] ?? ''));
  $selectedObservacoesLogisticasLivres = lot_strip_structured_logistic_lines((string)($selectedLote['observacoesLogisticas'] ?? ''));
  $selectedCancelamento = null;
  $selectedExceptionalEvents = [];
  foreach ($selectedMovimentacoes as $selectedMovimentacao) {
    if (!is_array($selectedMovimentacao)) {
      continue;
    }
    $tipoEventoExcepcional = (string)($selectedMovimentacao['tipoEvento'] ?? '');
    if (!in_array($tipoEventoExcepcional, ['lote_cancelado', 'lote_devolucao_parcial'], true)) {
      continue;
    }
    $payloadExcepcional = is_array($selectedMovimentacao['payloadEstrutural'] ?? null) ? (array)$selectedMovimentacao['payloadEstrutural'] : [];
    if ($tipoEventoExcepcional === 'lote_cancelado') {
      $selectedCancelamento = $selectedMovimentacao;
    }
    $selectedExceptionalEvents[] = [
      'id' => (int)($selectedMovimentacao['id'] ?? 0),
      'tipo' => $tipoEventoExcepcional === 'lote_cancelado' ? 'Cancelamento total' : 'Devolução parcial',
      'tipoKey' => $tipoEventoExcepcional === 'lote_cancelado' ? 'total' : 'parcial',
      'statusKey' => $tipoEventoExcepcional === 'lote_cancelado' ? lot_cancel_status_from_payload($payloadExcepcional, 'lote_cancelado') : '',
      'statusLabel' => $tipoEventoExcepcional === 'lote_cancelado' ? lot_cancel_status_label(lot_cancel_status_from_payload($payloadExcepcional, 'lote_cancelado')) : '',
      'data' => lot_date((string)($payloadExcepcional['cancelamento_data'] ?? ($selectedMovimentacao['dataEvento'] ?? ''))),
      'dataIso' => trim((string)($payloadExcepcional['cancelamento_data'] ?? '')),
      'motivo' => lot_text_or_default((string)($payloadExcepcional['cancelamento_motivo'] ?? '')),
      'motivoRaw' => trim((string)($payloadExcepcional['cancelamento_motivo'] ?? '')),
      'relato' => lot_text_or_default((string)($payloadExcepcional['cancelamento_relato'] ?? '')),
      'relatoRaw' => trim((string)($payloadExcepcional['cancelamento_relato'] ?? '')),
      'financeiro' => lot_text_or_default((string)($payloadExcepcional['cancelamento_financeiro'] ?? '')),
      'financeiroRaw' => trim((string)($payloadExcepcional['cancelamento_financeiro'] ?? '')),
      'estorno' => lot_money((float)($payloadExcepcional['cancelamento_estorno'] ?? 0)),
      'estornoRaw' => (float)($payloadExcepcional['cancelamento_estorno'] ?? 0),
      'vencimento' => trim((string)($payloadExcepcional['cancelamento_refund_due_date'] ?? '')) !== '' ? lot_date((string)$payloadExcepcional['cancelamento_refund_due_date']) : 'Não definido',
      'vencimentoIso' => trim((string)($payloadExcepcional['cancelamento_refund_due_date'] ?? '')),
    ];
  }
  $selectedCancelamentoPayload = is_array($selectedCancelamento['payloadEstrutural'] ?? null) ? (array)$selectedCancelamento['payloadEstrutural'] : [];
  $selectedCancelamentoAnexos = is_array($selectedAttachmentGroups['cancelamento']['items'] ?? null) ? (array)$selectedAttachmentGroups['cancelamento']['items'] : [];
  if ($selectedOutrosLocais <= 0 && $selectedFreightImpostos <= 0 && $selectedFreightOutros <= 0) {
    $selectedOutrosLocais = (float)($selectedLote['outrosCustos'] ?? 0);
    $selectedCustosLocaisTotal = $selectedCustoArmazenagem + $selectedCustoCarregamento + $selectedCustoSos + $selectedOutrosLocais;
  }
  $selectedFreightTotal = (float)($selectedLote['valorFrete'] ?? 0) + (float)($selectedLote['valorDocumentoTransporte'] ?? 0) + $selectedFreightImpostos + $selectedFreightOutros;
  $selectedHeroLocal = trim(implode(' • ', array_filter([
    trim((string)($selectedLote['nomeLocal'] ?? '')),
    trim((string)($selectedLote['cidade'] ?? '')) . (((string)($selectedLote['estado'] ?? '')) !== '' ? ' / ' . (string)($selectedLote['estado'] ?? '') : ''),
  ])));
  $selectedHeroLocal = $selectedHeroLocal !== '' ? $selectedHeroLocal : 'Local de coleta / armazenagem ainda não preenchido';
  $selectedHeroContato = trim(implode(' • ', array_filter([
    trim((string)($selectedLote['nomeContato'] ?? '')),
    trim((string)($selectedLote['telefone'] ?? '')),
  ])));
  $selectedHeroContato = $selectedHeroContato !== '' ? $selectedHeroContato : 'Contato local ainda não informado';
  $selectedHeroFreight = $selectedFreightLinkedMeta !== []
    ? trim(implode(' • ', array_filter([
      trim((string)($selectedFreightLinkedMeta['nome'] ?? '')),
      trim((string)($selectedFreightLinkedMeta['cidadeEstado'] ?? '')),
    ])))
    : trim(implode(' • ', array_filter([
      (int)($selectedLote['transportadoraId'] ?? 0) > 0 ? 'Transportadora vinculada' : '',
      (int)($selectedLote['motoristaId'] ?? 0) > 0 ? 'Motorista vinculado' : '',
      trim((string)($selectedLote['tipoTransporte'] ?? '')) !== '' ? lot_transport_label((string)($selectedLote['tipoTransporte'] ?? '')) : '',
    ])));
  $selectedHeroFreight = $selectedHeroFreight !== '' ? $selectedHeroFreight : 'Frete ainda não vinculado';
  $selectedHeroFreightMeta = trim(implode(' • ', array_filter([
    $selectedFreightTags !== [] ? implode(', ', array_values(array_filter(array_map(static function ($tag): string {
      if (is_array($tag)) {
        return trim((string)($tag['nome'] ?? $tag['slug'] ?? ''));
      }
      return trim((string)$tag);
    }, $selectedFreightTags)))) : '',
    $selectedFreightTotal > 0 ? lot_money($selectedFreightTotal) : '',
  ])));
  $selectedHeroFreightMeta = $selectedHeroFreightMeta !== '' ? $selectedHeroFreightMeta : 'Custos e perfil do frete ainda não preenchidos';

  $lotPrintMetaRows = [
    ['label' => 'Lote', 'value' => $selectedTitulo !== '' ? $selectedTitulo : ($selectedResumo !== '' ? $selectedResumo : 'Processo sem resumo')],
    ['label' => 'Seguradora', 'value' => $selectedFornecedorNome],
    ['label' => 'Processo', 'value' => $selectedProcessoNumero],
    ['label' => 'Sinistro', 'value' => $selectedNumeroSinistro !== '' ? $selectedNumeroSinistro : 'Não informado'],
    ['label' => 'Gerado em', 'value' => date('d/m/Y H:i')],
  ];

  $lotItemsPrintRows = [];
  foreach ($selectedItens as $selectedItem) {
    if (!is_array($selectedItem)) {
      continue;
    }
    $itemId = (int)($selectedItem['id'] ?? 0);
    $itemDescription = trim((string)($selectedItem['descricaoItem'] ?? ''));
    $itemSales = is_array($selectedItemSalesByItem[$itemId] ?? null) ? $selectedItemSalesByItem[$itemId] : null;
    if (!is_array($itemSales) && $itemDescription !== '') {
      $itemSales = is_array($selectedItemSalesByDescription[$itemDescription] ?? null) ? $selectedItemSalesByDescription[$itemDescription] : null;
    }
    if (!is_array($itemSales)) {
      $itemSales = ['gross' => 0.0, 'returned' => 0.0];
    }
    $itemSoldGrossQty = (float)($itemSales['gross'] ?? 0);
    $itemReturnedQty = (float)($itemSales['returned'] ?? 0);
    $itemSoldNetQty = max(0.0, $itemSoldGrossQty - $itemReturnedQty);
    $lotItemsPrintRows[] = [
      lot_text_or_default((string)($selectedItem['descricaoItem'] ?? ''), 'Item sem descrição'),
      lot_control_label((string)($selectedItem['tipoControleItem'] ?? '')),
      lot_qty_compact((float)($selectedItem['quantidadeTotal'] ?? 0)),
      lot_qty_compact($itemSoldGrossQty),
      lot_qty_compact($itemReturnedQty),
      lot_qty_compact($itemSoldNetQty),
      lot_qty_compact((float)($selectedItem['quantidadeDisponivel'] ?? 0)),
      lot_money((float)($selectedItem['custoUnitarioReferencia'] ?? 0)),
      lot_money((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0)),
      lot_money((float)($selectedItem['valorVendaTotalSugerido'] ?? 0)),
    ];
  }

  $lotItemsPrintPayload = [
    'title' => 'Itens do processo',
    'metaTitle' => $selectedResumo !== '' ? $selectedResumo : 'Processo sem resumo',
    'metaHint' => 'Para salvar: Cmd+P (Mac) / Ctrl+P (Windows) → Destino: Salvar como PDF',
    'brandSub' => 'Lista de itens do lote',
    'reportTitle' => 'Itens do processo',
    'metaRows' => $lotPrintMetaRows,
    'summaryTitle' => 'Resumo do processo',
    'summary' => [
      ['label' => 'Itens registrados', 'value' => (string)count($selectedItens)],
      ['label' => 'Total geral sugerido', 'value' => lot_money($selectedItensValorTotal)],
    ],
    'sectionTitle' => 'Lista de itens',
    'table' => [
      'head' => ['Produto', 'Tipo', 'Quantidade', 'Vendida', 'Devolvida', 'Vendida líquida', 'Disponível', 'Valor base', 'Valor venda', 'Total'],
      'rows' => $lotItemsPrintRows,
      'total' => ['label' => 'Total geral sugerido', 'value' => lot_money($selectedItensValorTotal), 'colspan' => 9],
    ],
    'footnote' => 'Documento gerado automaticamente pelo Sistema Visa Remoções.',
  ];

  $lotSalesPrintRows = [];
  $lotSalesPrintValorTotal = 0.0;
  foreach ($selectedVendas as $selectedVenda) {
    $valorVenda = (float)($selectedVenda['valorLiquido'] ?? 0);
    $lotSalesPrintValorTotal += $valorVenda;
    $lotSalesPrintRows[] = [
      (string)($selectedVenda['descricaoItem'] ?? 'Item'),
      (string)($selectedVenda['tipoControleItem'] ?? 'Und'),
      lot_qty_compact((float)($selectedVenda['quantidadeVendida'] ?? 0)),
      lot_qty_compact((float)($selectedVenda['quantidadeDevolvida'] ?? 0)),
      lot_money((float)($selectedVenda['valorTotal'] ?? 0)),
      lot_money((float)($selectedVenda['valorDevolvido'] ?? 0)),
      lot_money($valorVenda),
      (string)($selectedVenda['devolucaoStatus'] ?? 'Sem devolução'),
      (string)($selectedVenda['formaPagamento'] ?? '') . ((int)($selectedVenda['parcelas'] ?? 1) > 1 ? ' • ' . (string)($selectedVenda['parcelas'] ?? 1) . 'x' : ''),
      (string)($selectedVenda['clienteNome'] ?? ''),
      lot_date((string)($selectedVenda['dataEvento'] ?? '')),
    ];
  }

  $lotSalesPrintPayload = [
    'title' => 'Vendas do processo',
    'metaTitle' => $selectedResumo !== '' ? $selectedResumo : 'Processo sem resumo',
    'metaHint' => 'Para salvar: Cmd+P (Mac) / Ctrl+P (Windows) → Destino: Salvar como PDF',
    'brandSub' => 'Relatório de vendas do lote',
    'reportTitle' => 'Vendas do processo',
    'metaRows' => $lotPrintMetaRows,
    'summaryTitle' => 'Resumo comercial',
    'summary' => [
      ['label' => 'Vendas registradas', 'value' => (string)count($selectedVendas)],
      ['label' => 'Valor total vendido', 'value' => lot_money($lotSalesPrintValorTotal)],
    ],
    'sectionTitle' => 'Vendas registradas',
    'table' => [
      'head' => ['Produto', 'Tipo', 'Qtd. vendida', 'Qtd. devolvida', 'Valor bruto', 'Valor devolvido', 'Valor líquido', 'Status', 'Forma', 'Cliente', 'Data'],
      'rows' => $lotSalesPrintRows,
      'total' => ['label' => 'Total líquido vendido', 'value' => lot_money($lotSalesPrintValorTotal), 'colspan' => 10],
    ],
    'footnote' => 'Documento gerado automaticamente pelo Sistema Visa Remoções.',
  ];
  $lotReportPrintPayload = [
    'title' => 'Relatório do lote',
    'metaTitle' => $selectedTitulo !== '' ? $selectedTitulo : ($selectedResumo !== '' ? $selectedResumo : 'Processo sem resumo'),
    'metaHint' => 'Para salvar: Cmd+P (Mac) / Ctrl+P (Windows) → Destino: Salvar como PDF',
    'brandSub' => 'Relatório consolidado do lote',
    'reportTitle' => 'Relatório do lote',
    'metaRows' => $lotPrintMetaRows,
    'summaryTitle' => 'Resumo operacional',
    'summary' => [
      ['label' => 'Itens registrados', 'value' => (string)count($selectedItens)],
      ['label' => 'Vendas registradas', 'value' => (string)count($selectedVendas)],
      ['label' => 'Custo total', 'value' => lot_money((float)($selectedLote['custoTotal'] ?? 0))],
      ['label' => 'Valor vendido', 'value' => lot_money($selectedValorVendidoAtual)],
    ],
    'sections' => [
      [
        'title' => 'Identificação do lote',
        'layout' => 'two',
        'items' => [
          ['label' => 'Status do lote', 'value' => lot_status_label((string)($selectedLote['statusMacro'] ?? ''))],
          ['label' => 'Etapa atual', 'value' => lot_etapa_label((string)($selectedLote['etapaTimeline'] ?? ''))],
          ['label' => 'Data da compra', 'value' => lot_date((string)($selectedLote['dataCompra'] ?? ''))],
          ['label' => 'Seguradora', 'value' => $selectedFornecedorNome],
          ['label' => 'Processo', 'value' => $selectedProcessoNumero !== '' ? $selectedProcessoNumero : 'Não informado'],
          ['label' => 'Sinistro', 'value' => $selectedNumeroSinistro !== '' ? $selectedNumeroSinistro : 'Não informado'],
          ['label' => 'Título do lote', 'value' => lot_text_or_default($selectedTitulo)],
          ['label' => 'Resumo', 'value' => $selectedResumo !== '' ? $selectedResumo : 'Não informado', 'wide' => true],
        ],
      ],
      [
        'title' => 'Custos do lote',
        'table' => [
          'head' => ['Grupo', 'Descrição', 'Valor'],
          'rows' => [
            ['Lote', 'Valor original do lote', lot_money((float)($selectedLote['valorOriginalLote'] ?? 0))],
            ['Lote', 'Valor depreciado informado', lot_money((float)($selectedLote['valorDepreciadoInformado'] ?? 0))],
            ['Lote', 'Valor da compra', lot_money((float)($selectedLote['valorPagoCompra'] ?? 0))],
            ['Armazenagem', 'Custo de armazenagem', lot_money($selectedCustoArmazenagem)],
            ['Armazenagem', 'Carregamento', lot_money($selectedCustoCarregamento)],
            ['Armazenagem', 'SOS', lot_money($selectedCustoSos)],
            ['Armazenagem', 'Outros custos locais', lot_money($selectedOutrosLocais)],
            ['Frete', 'Valor do frete', lot_money((float)($selectedLote['valorFrete'] ?? 0))],
            ['Frete', 'Documentação de transporte', lot_money((float)($selectedLote['valorDocumentoTransporte'] ?? 0))],
            ['Frete', 'Impostos do frete', lot_money($selectedFreightImpostos)],
            ['Frete', 'Outros custos do frete', lot_money($selectedFreightOutros)],
          ],
          'total' => ['label' => 'Custo total do lote', 'value' => lot_money((float)($selectedLote['custoTotal'] ?? 0)), 'colspan' => 2],
        ],
      ],
      [
        'title' => 'Resultado econômico',
        'layout' => 'two',
        'items' => [
          ['label' => 'Custo total', 'value' => lot_money((float)($selectedLote['custoTotal'] ?? 0))],
          ['label' => 'Valor bruto vendido', 'value' => lot_money($selectedValorBrutoVendido)],
          ['label' => 'Valor devolvido', 'value' => lot_money($selectedValorDevolvido)],
          ['label' => 'Valor líquido vendido', 'value' => lot_money($selectedValorVendidoAtual)],
          ['label' => 'Saldo do lote', 'value' => lot_money($selectedValorVendidoAtual - (float)($selectedLote['custoTotal'] ?? 0)), 'wide' => true, 'highlight' => true],
        ],
      ],
      [
        'title' => 'Local de armazenagem',
        'layout' => 'two',
        'items' => [
          ['label' => 'Local', 'value' => lot_text_or_default((string)($selectedLote['nomeLocal'] ?? ''))],
          ['label' => 'Contato', 'value' => lot_text_or_default((string)($selectedLote['nomeContato'] ?? ''))],
          ['label' => 'Telefone', 'value' => lot_text_or_default((string)($selectedLote['telefone'] ?? ''))],
          ['label' => 'Telefone 2', 'value' => lot_text_or_default($selectedTelefoneDois)],
          ['label' => 'CPF/CNPJ local', 'value' => lot_text_or_default($selectedCpfCnpjLocal)],
          ['label' => 'Cidade / Estado', 'value' => trim((string)($selectedLote['cidade'] ?? '') . (((string)($selectedLote['estado'] ?? '')) !== '' ? ' / ' . (string)($selectedLote['estado'] ?? '') : '')) ?: 'Não informado'],
          ['label' => 'Endereço', 'value' => lot_text_or_default((string)($selectedLote['endereco'] ?? '')), 'wide' => true],
          ['label' => 'Observações do local', 'value' => lot_text_or_default($selectedObservacoesLocalLivres), 'wide' => true],
        ],
      ],
      [
        'title' => 'Frete',
        'layout' => 'two',
        'items' => [
          ['label' => 'Frete vinculado', 'value' => $selectedFreightLinkedMeta !== [] ? (string)($selectedFreightLinkedMeta['nome'] ?? 'Frete') : 'Não vinculado'],
          ['label' => 'Tipo', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['tipo'] ?? ''))],
          ['label' => 'Telefone', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['telefone'] ?? ''))],
          ['label' => 'Documento', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['documento'] ?? ''))],
          ['label' => 'CNH', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['cnh'] ?? ''))],
          ['label' => 'Cidade / Estado', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['cidadeEstado'] ?? ''))],
          ['label' => 'Veículo', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['veiculo'] ?? ''))],
          ['label' => 'Placa', 'value' => lot_text_or_default((string)($selectedFreightLinkedMeta['placa'] ?? ''))],
          ['label' => 'Tags do frete', 'value' => $selectedFreightTags !== [] ? implode(', ', array_values(array_filter(array_map(static function ($tag): string {
            if (is_array($tag)) {
              return trim((string)($tag['nome'] ?? $tag['slug'] ?? ''));
            }
            return trim((string)$tag);
          }, $selectedFreightTags)))) : 'Não informado', 'wide' => true],
          ['label' => 'Observações logísticas', 'value' => lot_text_or_default($selectedObservacoesLogisticasLivres), 'wide' => true],
        ],
      ],
      [
        'title' => 'Itens do lote',
        'table' => [
          'head' => ['Produto', 'Tipo', 'Quantidade', 'Vendida', 'Devolvida', 'Vendida líquida', 'Disponível', 'Valor base', 'Valor venda', 'Total sugerido'],
          'rows' => $lotItemsPrintRows,
          'total' => ['label' => 'Total geral sugerido', 'value' => lot_money($selectedItensValorTotal), 'colspan' => 9],
        ],
      ],
      [
        'title' => 'Vendas do lote',
        'table' => [
          'head' => ['Produto', 'Tipo', 'Qtd. vendida', 'Qtd. devolvida', 'Valor bruto', 'Valor devolvido', 'Valor líquido', 'Status', 'Forma', 'Cliente', 'Data'],
          'rows' => $lotSalesPrintRows,
          'total' => ['label' => 'Total líquido vendido', 'value' => lot_money($lotSalesPrintValorTotal), 'colspan' => 10],
        ],
      ],
      ...($selectedExceptionalEvents !== [] ? [[
        'title' => 'Ocorrências excepcionais',
        'table' => [
          'head' => ['Tipo', 'Data', 'Motivo', 'Estorno', 'Recebimento previsto'],
          'rows' => array_map(static function (array $event): array {
            return [
              (string)($event['tipo'] ?? ''),
              (string)($event['data'] ?? ''),
              (string)($event['motivo'] ?? ''),
              (string)($event['estorno'] ?? ''),
              (string)($event['vencimento'] ?? ''),
            ];
          }, $selectedExceptionalEvents),
          'total' => ['label' => 'Total em devoluções / estornos', 'value' => lot_money(array_reduce($selectedExceptionalEvents, static fn (float $carry, array $event): float => $carry + (float)($event['estornoRaw'] ?? 0), 0.0)), 'colspan' => 4],
        ],
      ]] : []),
      ...(((string)($selectedLote['statusMacro'] ?? '') === 'cancelado' && $selectedCancelamento !== null) ? [[
        'title' => 'Cancelamento',
        'layout' => 'two',
        'items' => [
          ['label' => 'Data', 'value' => lot_date((string)($selectedCancelamentoPayload['cancelamento_data'] ?? ($selectedCancelamento['dataEvento'] ?? '')))],
          ['label' => 'Status do cancelamento', 'value' => lot_cancel_status_label(lot_cancel_status_from_payload($selectedCancelamentoPayload, 'lote_cancelado'))],
          ['label' => 'Motivo', 'value' => lot_text_or_default((string)($selectedCancelamentoPayload['cancelamento_motivo'] ?? ''))],
          ['label' => 'Estorno', 'value' => lot_money((float)($selectedCancelamentoPayload['cancelamento_estorno'] ?? 0))],
          ['label' => 'Anexos', 'value' => (string)count($selectedCancelamentoAnexos)],
          ['label' => 'Relato', 'value' => lot_text_or_default((string)($selectedCancelamentoPayload['cancelamento_relato'] ?? '')), 'wide' => true],
          ['label' => 'Observação financeira', 'value' => lot_text_or_default((string)($selectedCancelamentoPayload['cancelamento_financeiro'] ?? '')), 'wide' => true],
        ],
      ]] : []),
    ],
    'sectionTitle' => 'Dados principais do lote',
    'table' => [],
    'footnote' => 'Documento gerado automaticamente pelo Sistema Visa Remoções.',
  ];
  $lotOccurrenceReportsMap = [];
  foreach ($selectedMovimentacoes as $selectedMovimentacao) {
    if (!is_array($selectedMovimentacao)) {
      continue;
    }
    if (!in_array((string)($selectedMovimentacao['tipoEvento'] ?? ''), ['lote_cancelado', 'lote_devolucao_parcial'], true)) {
      continue;
    }
    $occurrenceId = (int)($selectedMovimentacao['id'] ?? 0);
    if ($occurrenceId <= 0) {
      continue;
    }
    $lotOccurrenceReportsMap[$occurrenceId] = lot_occurrence_report_payload($selectedLote, is_array($selectedFornecedor) ? $selectedFornecedor : [], $selectedMovimentacao);
  }
  $selectedVendasValorTotal = 0.0;
  foreach ($selectedVendas as $selectedVenda) {
    if (!is_array($selectedVenda)) {
      continue;
    }
    $selectedVendasValorTotal += (float)($selectedVenda['valorLiquido'] ?? 0);
  }
  $timelineCurrentUser = trim((string)($_SESSION['auth_user']['name'] ?? 'Operação'));
  $timelineCurrentUserId = (int)($_SESSION['auth_user']['id'] ?? 0);
  $timelineStages = lot_timeline_stages();
  $selectedTimelineKey = (string)($selectedLote['statusMacro'] ?? '') === 'finalizado'
    ? 'finalizado'
    : trim((string)($selectedLote['etapaTimeline'] ?? ''));
  if ($selectedTimelineKey === '') {
    $selectedTimelineKey = 'compra';
  }
  if ($selectedTimelineKey === 'compra' && (string)($selectedLote['statusMacro'] ?? '') === 'em_transito') {
    $selectedTimelineKey = 'autorizacao_coleta';
  }
  $timelineReachedEnd = $selectedTimelineKey === 'finalizado';
  $selectedTimelineDisplayLabel = lot_etapa_label($selectedTimelineKey);
  $selectedTimelineIndex = 0;
  foreach ($timelineStages as $timelineIndex => $timelineStage) {
    if ((string)($timelineStage['key'] ?? '') === $selectedTimelineKey) {
      $selectedTimelineIndex = $timelineIndex;
      break;
    }
  }
  $timelineNeedsFreightAlert = lot_timeline_requires_freight_confirmation($selectedLote);
  $timelineDelayState = lot_timeline_delay_state($selectedLote, $selectedTimelineKey);
  $timelineStageRecordsMap = [];
  foreach ($timelineStages as $timelineStage) {
    $timelineStageRecordsMap[(string)($timelineStage['key'] ?? '')] = lot_timeline_stage_records($selectedLote, (string)($timelineStage['key'] ?? ''), $timelineCurrentUserId, $timelineCurrentUser);
  }
  $timelineInterruptionSummary = null;
  if ((string)($selectedLote['statusMacro'] ?? '') === 'cancelado' && is_array($selectedCancelamento)) {
    $timelineInterruptionSummary = [
      'status' => lot_cancel_status_label(lot_cancel_status_from_payload($selectedCancelamentoPayload, 'lote_cancelado')),
      'data' => lot_date((string)($selectedCancelamentoPayload['cancelamento_data'] ?? ($selectedCancelamento['dataEvento'] ?? ''))),
      'motivo' => lot_text_or_default((string)($selectedCancelamentoPayload['cancelamento_motivo'] ?? '')),
      'estorno' => lot_money((float)($selectedCancelamentoPayload['cancelamento_estorno'] ?? 0)),
      'vencimento' => trim((string)($selectedCancelamentoPayload['cancelamento_refund_due_date'] ?? '')) !== '' ? lot_date((string)$selectedCancelamentoPayload['cancelamento_refund_due_date']) : 'Não definido',
    ];
  }
  $selectedAttachmentOpenGroup = '';
  if (str_starts_with($selectedOpenModal, 'attachments:')) {
    $candidateOpenGroup = trim(substr($selectedOpenModal, strlen('attachments:')));
    if ($candidateOpenGroup !== '' && isset($selectedAttachmentGroups[$candidateOpenGroup])) {
      $selectedAttachmentOpenGroup = $candidateOpenGroup;
    }
  }
  ?>
  <div
    class="module-page lot-page"
    <?= $timelineFlashMessage !== '' ? 'data-lot-page-flash="' . h($timelineFlashMessage) . '" data-lot-page-flash-kind="' . h($timelineFlashKind !== '' ? $timelineFlashKind : 'info') . '"' : '' ?>
    <?= $selectedOpenModal !== '' ? ' data-lot-open-modal="' . h($selectedOpenModal) . '"' : '' ?>
    <?= $selectedAttachmentOpenGroup !== '' ? ' data-lot-open-attachment-modal="' . h($selectedAttachmentOpenGroup) . '"' : '' ?>
    <?= $selectedCrSyncRows !== [] ? ' data-lot-cr-sync="' . h(json_encode($selectedCrSyncRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"' : '' ?>
    <?= $selectedFinanceSync ? ' data-lot-finance-sync="' . h(json_encode($selectedFinanceSync, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"' : '' ?>
    <?= $selectedAttachmentGroups !== [] ? ' data-lot-attachment-groups="' . h(json_encode($selectedAttachmentGroups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . '"' : '' ?>
    <?= $timelineNeedsFreightAlert ? ' data-lot-timeline-needs-freight="1"' : '' ?>
  >
    <div class="module-head lot-page__head">
      <div class="lot-head__topline">
        <div class="lot-page__eyebrow">Módulo Lotes</div>

        <nav class="lot-crumbs" aria-label="Navegação do módulo Lotes">
          <a
            class="lot-crumbs__back"
            href="<?= h(app_url('/app/templates/lotes.php')) ?>"
            data-tip="Voltar"
          >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </a>

          <div class="lot-crumbs__trail">
            <a href="<?= h(app_url('/app/templates/lotes.php')) ?>">Lotes</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span>Ficha do lote</span>
          </div>
        </nav>
      </div>
      <h1>Página interna do lote</h1>
      <p>Leitura estrutural do processo individual, mantendo o dashboard como entrada do módulo e preparando a evolução operacional das próximas partes.</p>
    </div>

    <?php if (!is_array($selectedLote)): ?>
      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>Lote não encontrado</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="lot-empty-state">
            <strong>O lote solicitado não foi localizado.</strong>
            <p>O ID informado não existe na base atual ou não pertence ao contexto ativo do módulo.</p>
          </div>
        </div>
      </section>
    <?php else: ?>
      <section class="admin-block lot-detail__hero">
        <div class="admin-block-body">
          <div class="lot-detail__hero-grid">
            <div class="lot-detail__hero-main">
              <div class="lot-board-card__avatar lot-detail__avatar">
                <img src="<?= h(app_url('/app/static/img/avatar-fornecedor.png')) ?>" alt="Fornecedor">
              </div>

              <div class="lot-detail__hero-copy">
                <div class="lot-detail__chips">
                  <span class="lot-status-chip <?= h(lot_status_chip_class((string)($selectedLote['statusMacro'] ?? ''))) ?>">
                    <?= h(lot_status_label((string)($selectedLote['statusMacro'] ?? ''))) ?>
                  </span>
                  <span class="lot-priority-chip"><?= h(lot_priority_label($selectedLote)) ?></span>
                  <span class="lot-detail__process-chip"><?= h($selectedTimelineDisplayLabel) ?></span>
                </div>

                <h2><?= h(lot_text_or_default((string)($selectedLote['tituloLote'] ?? ''), 'Lote sem título')) ?></h2>
                <div class="lot-hero-meta">
                  <section class="lot-hero-meta__group lot-hero-meta__group--wide">
                    <div class="lot-hero-meta__head">
                      <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                      <span>Processo</span>
                    </div>
                    <div class="lot-detail__headline lot-detail__headline--grouped">
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Resumo:</span>
                        <strong><?= h($selectedResumo !== '' ? $selectedResumo : 'Resumo imediato ainda não preenchido para este lote.') ?></strong>
                      </div>
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Seguradora:</span>
                        <strong><?= h((string)($selectedFornecedor['nome'] ?? $selectedFornecedor['razaoSocial'] ?? 'Fornecedor não identificado')) ?></strong>
                      </div>
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Nº processo / Nº sinistro:</span>
                        <strong><?= h(lot_text_or_default((string)($selectedLote['numeroProcesso'] ?? ''), '-')) ?> / <?= h($selectedNumeroSinistro !== '' ? $selectedNumeroSinistro : 'Não informado') ?></strong>
                      </div>
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Pagamento da compra:</span>
                        <strong><?= h($selectedPurchasePaymentLabel) ?><?php if ($selectedPurchaseOpenAmount > 0): ?> • Em aberto: <?= h(lot_money($selectedPurchaseOpenAmount)) ?><?php elseif ((string)($selectedPurchasePayment['paidAt'] ?? '') !== ''): ?> • Pago em <?= h(lot_date((string)($selectedPurchasePayment['paidAt'] ?? ''))) ?><?php endif; ?></strong>
                      </div>
                    </div>
                  </section>

                  <section class="lot-hero-meta__group">
                    <div class="lot-hero-meta__head">
                      <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                      <span>Local de coleta / armazenagem</span>
                    </div>
                    <div class="lot-detail__headline lot-detail__headline--grouped">
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Local:</span>
                        <strong><?= h($selectedHeroLocal) ?></strong>
                      </div>
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Contato:</span>
                        <strong><?= h($selectedHeroContato) ?></strong>
                      </div>
                    </div>
                  </section>

                  <section class="lot-hero-meta__group">
                    <div class="lot-hero-meta__head">
                      <i class="fa-solid fa-truck-fast" aria-hidden="true"></i>
                      <span>Frete</span>
                    </div>
                    <div class="lot-detail__headline lot-detail__headline--grouped">
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Vinculado:</span>
                        <strong><?= h($selectedHeroFreight) ?></strong>
                      </div>
                      <div class="lot-detail__headline-row">
                        <span class="lot-detail__headline-label">Perfil:</span>
                        <strong><?= h($selectedHeroFreightMeta) ?></strong>
                      </div>
                    </div>
                  </section>
                </div>
              </div>
            </div>

            <div class="lot-detail__hero-side">
              <div class="lot-kpi-card">
                <div class="lot-kpi-card__icon is-money"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
                <div class="lot-kpi-card__body">
                  <span class="lot-kpi-card__label">Custo total</span>
                  <strong class="lot-kpi-card__value"><?= h(lot_money((float)($selectedLote['custoTotal'] ?? 0))) ?></strong>
                </div>
              </div>
              <div class="lot-kpi-card">
                <div class="lot-kpi-card__icon is-sales"><i class="fa-solid fa-cash-register" aria-hidden="true"></i></div>
                <div class="lot-kpi-card__body">
                  <span class="lot-kpi-card__label">Total vendido</span>
                  <strong class="lot-kpi-card__value"><?= h(lot_money($selectedValorVendidoAtual)) ?></strong>
                </div>
              </div>
              <div class="lot-kpi-card">
                <div class="lot-kpi-card__icon <?= $selectedResultadoParcial < 0 ? 'is-negative' : 'is-positive' ?>"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
                <div class="lot-kpi-card__body">
                  <span class="lot-kpi-card__label">Lucro / prejuízo</span>
                  <strong class="lot-kpi-card__value <?= $selectedResultadoParcial < 0 ? 'is-negative' : 'is-positive' ?>"><?= h(lot_money($selectedResultadoParcial)) ?></strong>
                </div>
              </div>
            </div>
          </div>
          <div class="lot-detail__hero-footer">
            <div class="lot-detail__hero-footer-group lot-detail__hero-footer-group--reports">
              <button class="fin-btn fin-btn--ghost" type="button" data-lot-print-main>
                <i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Relatório do lote</span>
              </button>
              <button class="fin-btn fin-btn--ghost" type="button" data-lot-print-items>
                <i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Imprimir lista</span>
              </button>
              <button class="fin-btn fin-btn--ghost" type="button" data-lot-print-sales>
                <i class="fa-solid fa-receipt" aria-hidden="true"></i><span>Imprimir vendas</span>
              </button>
            </div>
            <div class="lot-detail__hero-footer-group lot-detail__hero-footer-group--actions">
              <?php if ((string)($selectedLote['statusMacro'] ?? '') === 'cancelado'): ?>
                <button class="fin-btn fin-btn--ghost" type="button" disabled>
                  <i class="fa-solid fa-ban" aria-hidden="true"></i><span>Lote já cancelado</span>
                </button>
              <?php else: ?>
                <button class="fin-btn fin-btn--danger" type="button" data-lot-cancel-open>
                  <i class="fa-solid fa-ban" aria-hidden="true"></i><span>Cancelar lote</span>
                </button>
              <?php endif; ?>
              <button class="fin-btn" type="button" id="lotDetailEditOpen" data-lot-detail-edit-open>
                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Editar lote</span>
              </button>
            </div>
          </div>
        </div>
      </section>

      <section class="admin-block lot-detail__timeline" id="lotTimelineAnchor">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-road" aria-hidden="true"></i><span>Timeline operacional</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="lot-process-timeline" aria-label="Andamento operacional do lote">
            <?php foreach ($timelineStages as $timelineIndex => $timelineStage): ?>
              <?php
              $timelineState = 'future';
              if ($timelineReachedEnd) {
                $timelineState = 'done';
              } elseif ($timelineIndex < $selectedTimelineIndex) {
                $timelineState = 'done';
              } elseif ($timelineIndex === $selectedTimelineIndex) {
                $timelineState = 'current';
              }
              $timelineSeverityClass = '';
              if ($timelineState === 'current') {
                $timelineSeverityClass = ' is-' . $timelineDelayState;
              }
              $isTimelineInteractive = $timelineState === 'current'
                && !$timelineReachedEnd
                && (string)($timelineStage['key'] ?? '') !== 'finalizado'
                && (string)($selectedLote['statusMacro'] ?? '') === 'em_transito';
              $isTimelineReviewable = $timelineState === 'done'
                && (string)($timelineStage['key'] ?? '') !== 'finalizado';
              $canOpenTimelineStage = $isTimelineInteractive || $isTimelineReviewable;
              ?>
              <div class="lot-process-timeline__step is-<?= h($timelineState) ?><?= h($timelineSeverityClass) ?>" <?= $timelineState === 'current' ? 'data-lot-timeline-current' : '' ?>>
                <div class="lot-process-timeline__rail" aria-hidden="true"></div>
                <?php if ($canOpenTimelineStage): ?>
                  <button
                    class="lot-process-timeline__drop lot-process-timeline__drop--button"
                    type="button"
                    data-lot-timeline-trigger
                    data-stage-key="<?= h((string)$timelineStage['key']) ?>"
                    data-stage-label="<?= h((string)$timelineStage['label']) ?>"
                    data-stage-index="<?= h((string)$timelineIndex) ?>"
                    data-stage-icon="<?= h((string)$timelineStage['icon']) ?>"
                    data-stage-mode="<?= h($isTimelineInteractive ? 'active' : 'review') ?>"
                    data-stage-history="<?= h(json_encode($timelineStageRecordsMap[(string)($timelineStage['key'] ?? '')] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                    data-stage-expected-delivery="<?= h((string)($selectedLote['dataEntrega'] ?? '')) ?>"
                    data-lot-toast="Abrindo a etapa atual da timeline."
                    data-lot-toast-kind="info"
                  >
                    <i class="<?= h((string)$timelineStage['icon']) ?>" aria-hidden="true"></i>
                  </button>
                <?php else: ?>
                  <div class="lot-process-timeline__drop" aria-hidden="true">
                    <i class="<?= h((string)$timelineStage['icon']) ?>" aria-hidden="true"></i>
                  </div>
                <?php endif; ?>
                <div class="lot-process-timeline__copy">
                  <strong><?= h((string)$timelineStage['label']) ?></strong>
                  <span><?= h(lot_etapa_label((string)$timelineStage['key'])) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <?php if (is_array($timelineInterruptionSummary)): ?>
            <div class="lot-timeline-break">
              <div class="lot-timeline-break__icon"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></div>
              <div class="lot-timeline-break__copy">
                <strong>Interrupção do processo</strong>
                <span><?= h((string)($timelineInterruptionSummary['data'] ?? '')) ?> • <?= h((string)($timelineInterruptionSummary['status'] ?? '')) ?> • <?= h((string)($timelineInterruptionSummary['estorno'] ?? 'R$ 0,00')) ?></span>
                <p><?= h((string)($timelineInterruptionSummary['motivo'] ?? '')) ?><?php if ((string)($timelineInterruptionSummary['vencimento'] ?? 'Não definido') !== 'Não definido'): ?> • Recebimento previsto em <?= h((string)($timelineInterruptionSummary['vencimento'] ?? '')) ?><?php endif; ?></p>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <div class="fin-modal" id="lotTimelineModal" aria-hidden="true">
        <div class="fin-modal__card lot-timeline-modal__card" style="max-width: 640px;">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotTimelineModalTitle">Atualizar etapa</strong>
                <span class="lot-detail-modal__subhead">Registre a próxima etapa do processo mantendo a timeline do lote sempre atualizada.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotTimelineModalClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body lot-timeline-modal__body">
            <form class="lot-timeline-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotTimelineAnchor') ?>" id="lotTimelineForm" data-lot-timeline-old="<?= h(json_encode($lotTimelineOldInput, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
              <input type="hidden" name="lot_timeline_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="timeline_stage" id="lotTimelineStageInput" value="">
              <input type="hidden" name="timeline_submit_mode" id="lotTimelineSubmitMode" value="save">
              <input type="hidden" name="timeline_record_id" id="lotTimelineRecordId" value="0">

              <div class="lot-timeline-form__intro">
                <div class="lot-timeline-form__badge" id="lotTimelineStageBadge">
                  <i class="fa-solid fa-road" aria-hidden="true"></i>
                  <span>Etapa</span>
                </div>
                <p id="lotTimelineModalHint">Registre o andamento da etapa atual ou confirme a conclusão para liberar a próxima.</p>
              </div>

              <section class="lot-timeline-form__section">
                <div class="lot-timeline-form__section-head">
                  <h3><i class="fa-solid fa-pen-nib" aria-hidden="true"></i><span>Novo registro</span></h3>
                  <p>Documente o andamento desta etapa com os campos principais da tratativa.</p>
                </div>

                <div class="lot-timeline-form__grid">
                  <label class="lot-field">
                    <span id="lotTimelineDateLabel">Data do registro</span>
                    <input type="date" name="data_evento" id="lotTimelineDateInput" value="<?= h((string)($lotTimelineOldInput['data_evento'] ?? date('Y-m-d'))) ?>" required>
                  </label>

                  <label class="lot-field" id="lotTimelineContactField">
                    <span id="lotTimelineContactLabel">Contato</span>
                    <input type="text" name="timeline_contact" id="lotTimelineContactInput" placeholder="Com quem você falou nesta etapa?" value="<?= h((string)($lotTimelineOldInput['timeline_contact'] ?? '')) ?>">
                  </label>
                </div>

                <div class="lot-field">
                  <span>Responsável</span>
                  <div class="lot-timeline-form__readonly"><?= h($timelineCurrentUser !== '' ? $timelineCurrentUser : 'Operação') ?></div>
                </div>

                <label class="lot-field" id="lotTimelineExpectedDeliveryField" hidden>
                  <span>Prazo de entrega</span>
                  <input type="date" name="timeline_expected_delivery" id="lotTimelineExpectedDeliveryInput" value="<?= h((string)($lotTimelineOldInput['timeline_expected_delivery'] ?? ($selectedLote['dataEntrega'] ?? ''))) ?>">
                </label>

                <div class="cad-form-alert cad-form-alert--info" id="lotTimelineFreightAlert" hidden>
                  A coleta está sem frete vinculado. Se este lote for sair por coleta própria ou sem contratação formal, confirme essa exceção antes de concluir a etapa.
                </div>

                <label class="lot-field" id="lotTimelineFreightForceField" hidden>
                  <span>Confirmação da exceção</span>
                  <label class="lot-check">
                    <input type="checkbox" name="timeline_force_without_freight" id="lotTimelineForceWithoutFreight" value="1">
                    <span>Forçar coleta sem frete/motorista vinculado</span>
                  </label>
                </label>

                <label class="lot-field">
                  <span id="lotTimelineReportLabel">Relato</span>
                  <textarea class="lot-timeline-form__textarea" name="descricao_evento" id="lotTimelineDescription" placeholder="Descreva o contato realizado, a confirmação recebida ou a observação operacional desta etapa." required><?= h((string)($lotTimelineOldInput['descricao_evento'] ?? '')) ?></textarea>
                </label>

                <div class="lot-timeline-form__record-action">
                  <button class="fin-btn fin-btn--ghost" type="button" id="lotTimelineSubmitButton">Salvar registro</button>
                </div>
              </section>

              <section class="lot-timeline-form__history">
                <div class="lot-timeline-form__history-head">
                  <h3><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Registros desta etapa</span></h3>
                  <p>Acompanhe o histórico mais recente desta fase do processo.</p>
                </div>
                <div class="lot-inline-empty lot-inline-empty--compact" id="lotTimelineHistoryEmpty">
                  Nenhum registro lançado para esta etapa até o momento.
                </div>
                <div class="lot-timeline-list" id="lotTimelineHistoryList" hidden></div>
              </section>

              <div class="lot-timeline-form__actions">
                <button class="fin-btn fin-btn--ghost" id="lotTimelineModalCancel" type="button">Cancelar</button>
                <button class="fin-btn fin-btn--ghost" type="button" id="lotTimelineReopenButton" hidden>Reativar etapa</button>
                <button class="fin-btn" type="button" id="lotTimelineFinalizeButton">Finalizar etapa</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <section class="admin-block lot-detail__section" id="lotOpsAnchor">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i><span>Itens e tags do lote</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="lot-ops-stack">
            <article class="lot-ops-surface lot-ops-surface--items lot-ops-surface--full">
              <div class="lot-detail__section-head">
                <h3><i class="fa-solid fa-box-open" aria-hidden="true"></i><span>Itens do lote</span></h3>
                <p>Consulte os produtos do processo, edite quando necessário e mantenha a lista operacional sempre limpa.</p>
              </div>

              <div class="lot-item-list">
                <div class="lot-item-list__scroll">
                  <?php if ($selectedItens === []): ?>
                    <div class="lot-inline-empty">Nenhum item foi cadastrado para este processo até o momento.</div>
                  <?php else: ?>
                    <div class="fin-table-wrap lot-item-table-wrap is-scroll-y">
                    <table class="fin-table lot-item-table">
                      <thead>
                        <tr>
                          <th>Produto</th>
                          <th>Tipo</th>
                          <th>Quantidade</th>
                          <th>Disponível</th>
                          <th>Valor base</th>
                          <th>Valor venda</th>
                          <th>Total</th>
                          <th>Status</th>
                          <th>Ação</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($selectedItens as $selectedItem): ?>
                          <?php if (!is_array($selectedItem)) { continue; } ?>
                          <?php
                          $itemStatusLabel = lot_item_status_label($selectedItem);
                          $itemStatusClass = lot_item_status_class($selectedItem);
                          $itemHistory = (array)($selectedItemHistoryMap[(int)($selectedItem['id'] ?? 0)] ?? []);
                          $itemHistoryByDesc = (array)($selectedItemHistoryByDescription[(string)($selectedItem['descricaoItem'] ?? '')] ?? []);
                          $itemImages = lot_present_image_anexos((array)($selectedItem['imagensItem'] ?? []));
                          if ($itemHistory === [] && $itemHistoryByDesc !== []) {
                            $itemHistory = $itemHistoryByDesc;
                          }
                          $itemHistory = array_slice(array_reverse($itemHistory), 0, 30);
                          ?>
                          <tr class="lot-item-table__row <?= h($itemStatusClass) ?>">
                            <td>
                              <div class="lot-item-table__main">
                                <strong><?= h(lot_text_or_default((string)($selectedItem['descricaoItem'] ?? ''), 'Item sem descrição')) ?></strong>
                                <?php $obsItem = trim((string)($selectedItem['observacoesItem'] ?? '')); ?>
                                <?php if ($obsItem !== ''): ?>
                                  <span><?= h($obsItem) ?></span>
                                <?php endif; ?>
                              </div>
                            </td>
                            <td><?= h(lot_control_label((string)($selectedItem['tipoControleItem'] ?? ''))) ?></td>
                            <td><?= h(lot_qty_compact((float)($selectedItem['quantidadeTotal'] ?? 0))) ?></td>
                            <td><?= h(lot_qty_compact((float)($selectedItem['quantidadeDisponivel'] ?? 0))) ?></td>
                            <td><?= h(lot_money((float)($selectedItem['custoUnitarioReferencia'] ?? 0))) ?></td>
                            <td><?= h(lot_money((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0))) ?></td>
                            <td><?= h(lot_money((float)($selectedItem['valorVendaTotalSugerido'] ?? 0))) ?></td>
                            <td>
                              <span class="lot-item-status-dot <?= h($itemStatusClass) ?>" data-tip="<?= h($itemStatusLabel) ?>" data-tip-pos="left" aria-label="<?= h($itemStatusLabel) ?>"></span>
                            </td>
                            <td>
                              <div class="lot-item-table__actions">
                                <button
                                  class="fin-icon-btn fin-icon-btn--sm"
                                  type="button"
                                  data-lot-item-view
                                  data-tip="Ver produto"
                                  data-item-nome="<?= h((string)($selectedItem['descricaoItem'] ?? '')) ?>"
                                  data-item-tipo-label="<?= h(lot_control_label((string)($selectedItem['tipoControleItem'] ?? ''))) ?>"
                                  data-item-status-label="<?= h($itemStatusLabel) ?>"
                                  data-item-quantidade-total-label="<?= h(lot_qty_compact((float)($selectedItem['quantidadeTotal'] ?? 0))) ?>"
                                  data-item-quantidade-disponivel-label="<?= h(lot_qty_compact((float)($selectedItem['quantidadeDisponivel'] ?? 0))) ?>"
                                  data-item-quantidade-vendida-label="<?= h(lot_qty_compact((float)($selectedItem['quantidadeVendida'] ?? 0))) ?>"
                                  data-item-quantidade-baixada-label="<?= h(lot_qty_compact((float)($selectedItem['quantidadeBaixada'] ?? 0))) ?>"
                                  data-item-base-label="<?= h(lot_money((float)($selectedItem['custoUnitarioReferencia'] ?? 0))) ?>"
                                  data-item-venda-label="<?= h(lot_money((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0))) ?>"
                                  data-item-total-label="<?= h(lot_money((float)($selectedItem['valorVendaTotalSugerido'] ?? 0))) ?>"
                                  data-item-observacoes="<?= h((string)($selectedItem['observacoesItem'] ?? '')) ?>"
                                  data-item-images='<?= h(json_encode($itemImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                  data-item-history='<?= h(json_encode($itemHistory, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                >
                                  <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                </button>
                                <button
                                  class="fin-icon-btn fin-icon-btn--sm"
                                  type="button"
                                  data-lot-item-edit
                                  data-tip="Editar item"
                                  data-item-id="<?= h((string)($selectedItem['id'] ?? 0)) ?>"
                                  data-item-descricao="<?= h((string)($selectedItem['descricaoItem'] ?? '')) ?>"
                                  data-item-tipo="<?= h((string)($selectedItem['tipoControleItem'] ?? 'unidade')) ?>"
                                  data-item-quantidade="<?= h(number_format((float)($selectedItem['quantidadeTotal'] ?? 0), 3, '.', '')) ?>"
                                  data-item-base="<?= h(number_format((float)($selectedItem['custoUnitarioReferencia'] ?? 0), 2, '.', '')) ?>"
                                  data-item-venda="<?= h(number_format((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0), 2, '.', '')) ?>"
                                  data-item-observacoes="<?= h((string)($selectedItem['observacoesItem'] ?? '')) ?>"
                                  data-item-images='<?= h(json_encode($itemImages, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
                                >
                                  <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                </button>
                                <button
                                  class="fin-icon-btn fin-icon-btn--sm"
                                  type="button"
                                  data-lot-item-baixa
                                  data-tip="Baixa manual"
                                  data-item-id="<?= h((string)($selectedItem['id'] ?? 0)) ?>"
                                  data-item-descricao="<?= h((string)($selectedItem['descricaoItem'] ?? '')) ?>"
                                  data-item-disponivel="<?= h(number_format((float)($selectedItem['quantidadeDisponivel'] ?? 0), 3, '.', '')) ?>"
                                >
                                  <i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i>
                                </button>
                                <?php if ((float)($selectedItem['quantidadeBaixada'] ?? 0) > 0): ?>
                                  <button
                                    class="fin-icon-btn fin-icon-btn--sm"
                                    type="button"
                                    data-lot-item-revert
                                    data-tip="Reverter baixa"
                                    data-item-id="<?= h((string)($selectedItem['id'] ?? 0)) ?>"
                                    data-item-descricao="<?= h((string)($selectedItem['descricaoItem'] ?? '')) ?>"
                                    data-item-baixado="<?= h(number_format((float)($selectedItem['quantidadeBaixada'] ?? 0), 3, '.', '')) ?>"
                                  >
                                    <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                  </button>
                                <?php endif; ?>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="lot-item-list__footer">
                  <div class="lot-item-list__totals">
                    <span>Total de itens: <strong><?= h((string)count($selectedItens)) ?></strong></span>
                    <span>Total geral sugerido: <strong><?= h(lot_money($selectedItensValorTotal)) ?></strong></span>
                  </div>
                  <div class="lot-item-list__footer-actions">
                  <button class="fin-btn fin-btn--ghost" type="button" id="lotPrintListButton">
                      <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir lista</span>
                    </button>
                  <button class="fin-btn fin-btn--ghost" type="button" id="lotLoteBaixaTotalOpen">
                      <i class="fa-solid fa-arrow-down-short-wide" aria-hidden="true"></i><span>Baixa total do lote</span>
                    </button>
                    <button class="fin-btn" type="button" id="lotItemManageOpenSection">
                      <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Adicionar item</span>
                    </button>
                    <button class="fin-btn lot-item-list__sell-btn" type="button" id="lotItemVendaOpen">
                      <i class="fa-solid fa-cart-plus" aria-hidden="true"></i><span>Venda</span>
                    </button>
                  </div>
                </div>
              </div>
            </article>

            <div class="lot-ops-workspace">
              <article class="lot-ops-surface lot-ops-surface--tags">
                <div class="lot-detail__section-head">
                  <h3><i class="fa-solid fa-tags" aria-hidden="true"></i><span>Tags do lote</span></h3>
                  <p>Use tags livres para classificar o processo em nível macro e cruzar com interesses comerciais sem depender da lista de itens.</p>
                </div>

                <div class="cad-tag-editor lot-tag-editor">
                  <form method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>">
                    <input type="hidden" name="lot_tag_submit" value="1">
                    <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                    <div class="cad-tag-editor__inputrow">
                      <input type="text" name="tag_nome" maxlength="80" placeholder="Digite uma tag e clique em adicionar">
                      <button type="submit" class="fin-btn fin-btn--ghost">Adicionar tag</button>
                    </div>
                  </form>
                  <div class="cad-tag-editor__chips lot-tag-editor__chips">
                    <?php if ($selectedTags === []): ?>
                      <div class="lot-inline-empty lot-inline-empty--compact">Nenhuma tag foi associada a este lote até o momento.</div>
                    <?php else: ?>
                      <?php foreach ($selectedTags as $selectedTag): ?>
                        <?php if (!is_array($selectedTag)) { continue; } ?>
                        <form method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>" class="lot-tag-chip-form">
                          <input type="hidden" name="lot_tag_remove_submit" value="1">
                          <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                          <input type="hidden" name="tag_slug" value="<?= h((string)($selectedTag['slug'] ?? '')) ?>">
                          <button type="submit" class="cad-tag-chip" data-tip="Excluir tag">
                            <span><?= h((string)($selectedTag['nome'] ?? '')) ?></span>
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                          </button>
                        </form>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </article>

              <article class="lot-ops-surface lot-ops-surface--clients" id="lotCompatibleClientsAnchor">
                <div class="lot-detail__section-head">
                  <h3><i class="fa-solid fa-users-viewfinder" aria-hidden="true"></i><span>Clientes compatíveis</span></h3>
                  <p>Lista automática de cadastros com aderência às tags deste lote. Se precisar, force uma nova leitura manual.</p>
                </div>
                <div class="lot-compatible">
                  <div class="lot-compatible__toolbar">
                    <div class="lot-compatible__count">
                      <strong><?= h((string)count($selectedClientesCompatíveis)) ?></strong>
                      <span><?= h(count($selectedClientesCompatíveis) === 1 ? 'cadastro compatível encontrado' : 'cadastros compatíveis encontrados') ?></span>
                    </div>
                    <button
                      class="fin-btn fin-btn--ghost"
                      type="button"
                      data-lot-compatible-refresh
                      data-refresh-href="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotCompatibleClientsAnchor') ?>"
                    >
                      <i class="fa-solid fa-rotate" aria-hidden="true"></i><span>Atualizar lista</span>
                    </button>
                  </div>

                  <?php if ($selectedTags === []): ?>
                    <div class="lot-inline-empty lot-inline-empty--compact">Adicione tags ao lote para o sistema começar a sugerir cadastros compatíveis automaticamente.</div>
                  <?php elseif ($selectedClientesCompatíveis === []): ?>
                    <div class="lot-inline-empty lot-inline-empty--compact">Nenhum cadastro compatível foi encontrado com base nas tags atuais deste lote.</div>
                  <?php else: ?>
                    <div class="fin-table-wrap lot-compatible__tablewrap">
                      <table class="fin-table lot-compatible__table">
                        <thead>
                          <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($selectedClientesCompatíveis as $clienteCompativel): ?>
                            <tr
                              class="lot-compatible__row"
                              data-lot-compatible-open="<?= h((string)($clienteCompativel['id'] ?? 0)) ?>"
                              data-lot-compatible-name="<?= h((string)($clienteCompativel['nome'] ?? 'Cliente')) ?>"
                              role="button"
                              tabindex="0"
                              aria-label="Abrir ficha do cliente <?= h((string)($clienteCompativel['nome'] ?? '')) ?>"
                            >
                              <td>
                                <div class="lot-compatible__name"><?= h((string)($clienteCompativel['nome'] ?? '')) ?></div>
                              </td>
                              <td><?= h((string)($clienteCompativel['tipo'] ?? 'Sem tipo')) ?></td>
                              <td><?= h((string)($clienteCompativel['telefone'] ?? 'Não informado')) ?></td>
                              <td><?= h((string)($clienteCompativel['cidade'] ?? 'Não informada')) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <div class="fin-modal" id="lotItemBaixaModal" aria-hidden="true">
        <div class="fin-modal__card" style="max-width: 560px;">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotItemBaixaTitle">Baixa manual do item</strong>
                <span class="lot-detail-modal__subhead">Registre uma baixa operacional no produto quando ele sair do lote sem venda vinculada.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotItemBaixaClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <form class="lot-baixa-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>" id="lotItemBaixaForm">
              <input type="hidden" name="lot_item_baixa_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="baixa_item_id" id="lotBaixaItemId" value="">

              <div class="lot-timeline-form__intro">
                <div class="lot-timeline-form__badge">
                  <i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i>
                  <span id="lotBaixaItemNome">Item</span>
                </div>
                <p id="lotBaixaHint">Informe a quantidade da baixa manual. Use esta ação quando a saída não estiver ligada à venda.</p>
              </div>

              <div class="lot-timeline-form__grid">
                <label class="lot-field">
                  <span>Quantidade da baixa</span>
                  <input type="number" name="baixa_quantidade" id="lotBaixaQuantidadeInput" min="0.001" step="0.001" inputmode="decimal" required>
                </label>
                <label class="lot-field">
                  <span>Data</span>
                  <input type="date" name="baixa_data" value="<?= h(date('Y-m-d')) ?>" required>
                </label>
              </div>

              <div class="lot-field">
                <span>Disponível atual</span>
                <div class="lot-timeline-form__readonly" id="lotBaixaDisponivelPreview">0,000</div>
              </div>

              <label class="lot-field">
                <span>Observação da baixa</span>
                <textarea class="lot-timeline-form__textarea" name="baixa_observacao" rows="4" placeholder="Descreva o motivo da baixa manual ou qualquer detalhe relevante desta saída."></textarea>
              </label>

              <div class="lot-timeline-form__actions">
                <button class="fin-btn fin-btn--ghost" id="lotItemBaixaCancel" type="button">Cancelar</button>
                <button class="fin-btn" type="submit">Confirmar baixa</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotItemVendaModal" aria-hidden="true">
        <div class="fin-modal__card lot-sale-modal__card">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline">Venda de Lote</strong>
                <span class="lot-detail-modal__subhead">Selecione o comprador, escolha o item e registre a venda sem sair do lote.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotItemVendaClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body lot-sale-modal__body">
            <form class="lot-baixa-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>" id="lotItemVendaForm">
              <input type="hidden" name="lot_item_sell_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="cliente_id" id="lotVendaClienteId" value="">

              <div class="lot-sale-shell">
                <div class="lot-sale-shell__head">
                  <div class="lot-detail__section-head">
                    <h3><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i><span>Lançamento de venda</span></h3>
                    <p>Selecione o comprador, escolha o item e registre a venda sem sair do lote.</p>
                  </div>
                  <?php if ($selectedOpenModal === 'venda' && $timelineFlashMessage !== ''): ?>
                    <div class="cad-form-alert <?= $timelineFlashKind === 'warning' || $timelineFlashKind === 'info' ? 'cad-form-alert--info' : '' ?>">
                      <?= h($timelineFlashMessage) ?>
                    </div>
                  <?php endif; ?>

                  <div class="lot-sale-shell__customer">
                    <label class="lot-field lot-sale-shell__customer-field">
                      <span>Comprador</span>
                      <div class="lot-quick-search lot-sale-customer-lookup" id="lotVendaClienteLookup" data-lot-cadastro-source='<?= h(json_encode(array_values(array_map(static function (array $cadastro): array {
                  return [
                    'id' => (int)($cadastro['id'] ?? 0),
                    'nome' => (string)($cadastro['nome'] ?? $cadastro['razaoSocial'] ?? ''),
                    'documento' => (string)($cadastro['documento'] ?? ''),
                    'celular' => (string)($cadastro['celular'] ?? $cadastro['whatsapp'] ?? $cadastro['telefone'] ?? ''),
                    'searchIndex' => lot_normalize_search(implode(' ', [
                      (string)($cadastro['nome'] ?? ''),
                      (string)($cadastro['razaoSocial'] ?? ''),
                      (string)($cadastro['documento'] ?? ''),
                      (string)($cadastro['celular'] ?? ''),
                      (string)($cadastro['whatsapp'] ?? ''),
                      (string)($cadastro['telefone'] ?? ''),
                    ])),
                  ];
                }, array_values(array_filter($selectedCadastrosLookup, 'is_array')))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                        <div class="lot-quick-search__stack">
                          <div class="lot-quick-search__inputrow">
                            <label class="lot-quick-search__field">
                              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                              <input type="search" id="lotVendaClienteSearch" placeholder="Busque por nome, CPF ou CNPJ" autocomplete="off" required>
                            </label>
                          </div>
                          <div class="lot-search-suggest" id="lotVendaClienteResults" hidden></div>
                        </div>
                      </div>
                    </label>

                    <div class="lot-sale-shell__customer-side">
                      <div class="lot-sale-client-meta" id="lotVendaClienteMeta" hidden>
                        <strong id="lotVendaClienteMetaName">—</strong>
                        <span id="lotVendaClienteMetaDoc">—</span>
                      </div>
                    </div>
                  </div>

                </div>

                <div class="lot-sale-workspace">
                  <aside class="lot-sale-workspace__catalog" id="lotVendaItemField">
                    <div class="lot-detail__section-head">
                      <h3><i class="fa-solid fa-box-open" aria-hidden="true"></i><span>Produtos do lote</span></h3>
                      <p>Role a lista se necessário e clique na linha do item que será vendido. No mobile, use o seletor simplificado abaixo.</p>
                    </div>

                    <div class="fin-table-wrap lot-sale-item-picker" id="lotVendaItemPicker">
                      <table class="fin-table lot-sale-item-picker__table">
                        <thead>
                          <tr>
                            <th>Produto</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($selectedItens as $selectedItem): ?>
                            <?php if (!is_array($selectedItem) || (float)($selectedItem['quantidadeDisponivel'] ?? 0) <= 0) { continue; } ?>
                            <?php
                            $selectedItemId = (int)($selectedItem['id'] ?? 0);
                            $selectedItemTipo = lot_control_label((string)($selectedItem['tipoControleItem'] ?? ''));
                            $selectedItemDisponivel = lot_qty_compact((float)($selectedItem['quantidadeDisponivel'] ?? 0));
                            ?>
                            <tr
                              data-lot-venda-pick
                              data-item-id="<?= h((string)$selectedItemId) ?>"
                              data-item-label="<?= h((string)($selectedItem['descricaoItem'] ?? 'Item')) ?>"
                              data-item-type="<?= h($selectedItemTipo) ?>"
                              data-item-available="<?= h(number_format((float)($selectedItem['quantidadeDisponivel'] ?? 0), 3, '.', '')) ?>"
                              data-item-available-label="<?= h($selectedItemDisponivel) ?>"
                              data-item-price="<?= h(number_format((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0), 2, '.', '')) ?>"
                              data-item-price-label="<?= h(lot_money((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0))) ?>"
                              tabindex="0"
                              role="button"
                              aria-label="Selecionar item <?= h((string)($selectedItem['descricaoItem'] ?? 'Item')) ?>"
                            >
                              <td><?= h((string)($selectedItem['descricaoItem'] ?? 'Item')) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>

                    <label class="lot-field lot-sale-item-picker__mobile">
                      <span>Produto</span>
                      <select name="venda_item_id" id="lotVendaItemSelect">
                        <?php foreach ($selectedItens as $selectedItem): ?>
                          <?php if (!is_array($selectedItem) || (float)($selectedItem['quantidadeDisponivel'] ?? 0) <= 0) { continue; } ?>
                          <option value="<?= h((string)($selectedItem['id'] ?? 0)) ?>" data-max="<?= h(number_format((float)($selectedItem['quantidadeDisponivel'] ?? 0), 3, '.', '')) ?>" data-price="<?= h(number_format((float)($selectedItem['valorVendaUnitarioSugerido'] ?? 0), 2, '.', '')) ?>" data-type="<?= h(lot_control_label((string)($selectedItem['tipoControleItem'] ?? ''))) ?>" data-available-label="<?= h(lot_qty_compact((float)($selectedItem['quantidadeDisponivel'] ?? 0))) ?>">
                            <?= h((string)($selectedItem['descricaoItem'] ?? 'Item')) ?> • <?= h(lot_control_label((string)($selectedItem['tipoControleItem'] ?? ''))) ?> • disponível <?= h(lot_qty_compact((float)($selectedItem['quantidadeDisponivel'] ?? 0))) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </label>
                  </aside>

                  <div class="lot-sale-workspace__main">
                    <div class="lot-sale-summary">
                      <div class="lot-sale-summary__label">Item selecionado</div>
                      <strong id="lotVendaSelectedName">Selecione um produto na lista</strong>
                      <span id="lotVendaSelectedMeta">Tipo e disponibilidade aparecerão aqui.</span>
                    </div>

                    <div class="lot-timeline-form__grid">
                      <label class="lot-field">
                        <span>Modo da venda</span>
                        <select name="venda_modo" id="lotVendaModoSelect">
                          <option value="item">Produto específico</option>
                          <option value="lote_total">Venda total do lote</option>
                        </select>
                      </label>
                      <label class="lot-field">
                        <span id="lotVendaQuantidadeLabel">Quantidade</span>
                        <input type="number" name="venda_quantidade" id="lotVendaQuantidadeInput" min="0.001" step="0.001" inputmode="decimal">
                      </label>
                    </div>

                    <div class="lot-timeline-form__grid" id="lotVendaItemGrid">
                      <label class="lot-field">
                        <span>Valor unitário</span>
                        <input type="text" name="venda_valor_unitario" id="lotVendaValorInput" inputmode="decimal" placeholder="R$ 0,00">
                      </label>
                      <label class="lot-field">
                        <span>Total estimado</span>
                        <input type="text" id="lotVendaTotalPreview" value="<?= h(lot_money(0)) ?>" readonly>
                      </label>
                    </div>

                    <div class="lot-timeline-form__grid lot-sale-payment-grid">
                      <label class="lot-field">
                        <span>Data</span>
                        <input type="date" name="venda_data" value="<?= h(date('Y-m-d')) ?>" required>
                      </label>
                      <label class="lot-field">
                        <span>Forma de pagamento</span>
                        <select name="venda_forma_pagamento" id="lotVendaFormaSelect" required>
                          <option value="">Selecione</option>
                          <option value="Dinheiro">Dinheiro</option>
                          <option value="PIX">PIX</option>
                          <option value="Transferência">Transferência</option>
                          <option value="Cartão de débito">Cartão de débito</option>
                          <option value="Cartão de crédito">Cartão de crédito</option>
                          <option value="Boleto">Boleto</option>
                          <option value="A prazo">A prazo</option>
                          <option value="Cheque">Cheque</option>
                          <option value="Outro">Outro</option>
                        </select>
                      </label>
                      <label class="lot-field" id="lotVendaParcelasField" hidden>
                        <span>Parcelas</span>
                        <select name="venda_parcelas" id="lotVendaParcelasSelect">
                          <?php for ($parcela = 1; $parcela <= 12; $parcela++): ?>
                            <option value="<?= h((string)$parcela) ?>"><?= h((string)$parcela) ?>x</option>
                          <?php endfor; ?>
                        </select>
                      </label>
                    </div>

                    <label class="lot-field">
                      <span>Observação da venda</span>
                      <textarea class="lot-timeline-form__textarea" name="venda_observacao" rows="4" placeholder="Use este campo para registrar observações importantes da venda."></textarea>
                    </label>

                    <div class="lot-timeline-form__actions">
                      <button class="fin-btn fin-btn--ghost" id="lotItemVendaCancel" type="button">Cancelar</button>
                      <button class="fin-btn" type="submit">Lançar venda</button>
                    </div>
                  </div>
                </div>
              </div>
            </form>

            <div class="lot-sale-list">
              <div class="lot-detail__section-head">
                <h3><i class="fa-solid fa-receipt" aria-hidden="true"></i><span>Vendas lançadas neste lote</span></h3>
                <p>O modal permanece aberto para lançar novas vendas enquanto houver itens disponíveis no lote.</p>
              </div>
              <div class="lot-item-list">
                <div class="lot-item-list__scroll">
                  <?php if ($selectedVendas === []): ?>
                    <div class="lot-inline-empty">Nenhuma venda foi registrada para este lote até o momento.</div>
                  <?php else: ?>
                    <div class="fin-table-wrap lot-item-table-wrap is-scroll-y">
                      <table class="fin-table lot-item-table">
                        <thead>
                          <tr>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Qtd. vendida</th>
                            <th>Qtd. devolvida</th>
                            <th>Valor líquido</th>
                            <th>Forma</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ação</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($selectedVendas as $selectedVenda): ?>
                            <tr>
                              <td><?= h((string)($selectedVenda['descricaoItem'] ?? 'Item')) ?></td>
                              <td><?= h((string)($selectedVenda['tipoControleItem'] ?? '')) ?></td>
                              <td><?= h(lot_qty_compact((float)($selectedVenda['quantidadeVendida'] ?? 0))) ?></td>
                              <td><?= h(lot_qty_compact((float)($selectedVenda['quantidadeDevolvida'] ?? 0))) ?></td>
                              <td><?= h(lot_money((float)($selectedVenda['valorLiquido'] ?? 0))) ?></td>
                              <td><?= h((string)($selectedVenda['formaPagamento'] ?? '')) ?><?= (int)($selectedVenda['parcelas'] ?? 1) > 1 ? ' • ' . h((string)($selectedVenda['parcelas'] ?? 1)) . 'x' : '' ?></td>
                              <td><?= h((string)($selectedVenda['clienteNome'] ?? '')) ?></td>
                              <td><?= h(lot_date((string)($selectedVenda['dataEvento'] ?? ''))) ?></td>
                              <td><?= h((string)($selectedVenda['devolucaoStatus'] ?? 'Sem devolução')) ?></td>
                              <td>
                                <div class="lot-item-table__actions">
                                  <?php if ((float)($selectedVenda['saldoDevolvivel'] ?? 0) > 0): ?>
                                    <button
                                      class="fin-icon-btn fin-icon-btn--sm"
                                      type="button"
                                      data-lot-sale-return
                                      data-tip="Registrar devolução"
                                      data-sale-ref="<?= h((string)($selectedVenda['saleRef'] ?? '')) ?>"
                                      data-sale-item="<?= h((string)($selectedVenda['descricaoItem'] ?? 'Item')) ?>"
                                      data-sale-cliente="<?= h((string)($selectedVenda['clienteNome'] ?? 'Cliente')) ?>"
                                      data-sale-tipo="<?= h((string)($selectedVenda['tipoControleItem'] ?? '')) ?>"
                                      data-sale-balance="<?= h(number_format((float)($selectedVenda['saldoDevolvivel'] ?? 0), 3, '.', '')) ?>"
                                      data-sale-unit="<?= h(number_format((float)($selectedVenda['valorUnitario'] ?? 0), 2, '.', '')) ?>"
                                    >
                                      <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                    </button>
                                  <?php else: ?>
                                    <span class="lot-item-table__muted">Sem saldo</span>
                                  <?php endif; ?>
                                </div>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="lot-item-list__footer">
                  <div class="lot-item-list__totals">
                    <span>Vendas registradas: <strong><?= h((string)count($selectedVendas)) ?></strong></span>
                    <span>Total vendido registrado: <strong><?= h(lot_money($selectedVendasValorTotal)) ?></strong></span>
                  </div>
                  <div class="lot-item-list__footer-actions">
                    <button class="fin-btn fin-btn--ghost" type="button" id="lotPrintSalesButton">
                      <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir vendas</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotCadastroInlineModal" aria-hidden="true">
        <div class="fin-modal__card" style="max-width: 1320px; width: min(1320px, calc(100vw - 40px));">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotCadastroInlineTitle">Novo cadastro</strong>
                <span class="lot-detail-modal__subhead">Cadastre um novo contato sem sair do fluxo do lote para continuar a operação com agilidade.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotCadastroInlineClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <iframe class="lot-inline-frame" id="lotCadastroInlineFrame" title="Cadastro inline"></iframe>
          </div>
        </div>
      </div>

      <script>
        window.__CADASTROS_LIST__ = <?= $cadastroModalItensJson ?: '[]' ?>;
        window.__CADASTROS_AVATARS__ = <?= $cadastroModalAvatarJson ?: '{}' ?>;
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

      <div class="fin-modal" id="lotSaleReturnModal" aria-hidden="true">
        <div class="fin-modal__card" style="max-width: 580px;">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotSaleReturnTitle">Devolução da venda</strong>
                <span class="lot-detail-modal__subhead">Registre o retorno parcial ou total de uma venda para devolver o saldo ao estoque do lote.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotSaleReturnClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <form class="lot-baixa-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>" id="lotSaleReturnForm">
              <input type="hidden" name="lot_item_sale_return_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="devolucao_sale_ref" id="lotSaleReturnRef" value="">

              <div class="lot-timeline-form__intro">
                <div class="lot-timeline-form__badge">
                  <i class="fa-solid fa-box-open" aria-hidden="true"></i>
                  <span id="lotSaleReturnBadge">Venda selecionada</span>
                </div>
                <p id="lotSaleReturnHint">Informe a quantidade devolvida desta venda. O sistema vai devolver a quantidade para o estoque disponível do lote.</p>
              </div>

              <div class="lot-timeline-form__grid">
                <label class="lot-field">
                  <span>Quantidade devolvida</span>
                  <input type="number" name="devolucao_quantidade" id="lotSaleReturnQty" min="0.001" step="0.001" inputmode="decimal" required>
                </label>
                <label class="lot-field">
                  <span>Data</span>
                  <input type="date" name="devolucao_data" value="<?= h(date('Y-m-d')) ?>" required>
                </label>
              </div>

              <div class="lot-timeline-form__grid">
                <label class="lot-field">
                  <span>Saldo devolvível</span>
                  <div class="lot-timeline-form__readonly" id="lotSaleReturnSaldo">0,000</div>
                </label>
                <label class="lot-field">
                  <span>Valor proporcional</span>
                  <div class="lot-timeline-form__readonly" id="lotSaleReturnTotal">R$ 0,00</div>
                </label>
              </div>

              <label class="lot-field">
                <span>Observação da devolução</span>
                <textarea class="lot-timeline-form__textarea" name="devolucao_observacao" rows="4" placeholder="Descreva o motivo da devolução ou qualquer detalhe importante deste retorno."></textarea>
              </label>

              <div class="lot-timeline-form__actions">
                <button class="fin-btn fin-btn--ghost" id="lotSaleReturnCancel" type="button">Cancelar</button>
                <button class="fin-btn" type="submit">Confirmar devolução</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotBaixaTotalModal" aria-hidden="true">
        <div class="fin-modal__card" style="max-width: 580px;">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline">Baixa total do lote</strong>
                <span class="lot-detail-modal__subhead">Finalize de uma vez a disponibilidade dos itens restantes quando o lote precisar ser baixado integralmente.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotBaixaTotalClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <form class="lot-baixa-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>" id="lotBaixaTotalForm">
              <input type="hidden" name="lot_baixa_total_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">

              <div class="lot-timeline-form__intro">
                <div class="lot-timeline-form__badge">
                  <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
                  <span>Baixa de todos os itens</span>
                </div>
                <p>Essa ação vai zerar a disponibilidade de todos os produtos ainda ativos no lote e registrar uma movimentação individual para cada item.</p>
              </div>

              <div class="lot-field">
                <span>Itens ativos no momento</span>
                <div class="lot-timeline-form__readonly"><?= h((string)$selectedItensAtivos) ?></div>
              </div>

              <div class="lot-timeline-form__grid">
                <label class="lot-field">
                  <span>Data</span>
                  <input type="date" name="baixa_total_data" value="<?= h(date('Y-m-d')) ?>" required>
                </label>
              </div>

              <label class="lot-field">
                <span>Observação da baixa total</span>
                <textarea class="lot-timeline-form__textarea" name="baixa_total_observacao" rows="4" placeholder="Descreva o motivo da baixa total do lote."></textarea>
              </label>

              <div class="lot-timeline-form__actions">
                <button class="fin-btn fin-btn--ghost" id="lotBaixaTotalCancel" type="button">Cancelar</button>
                <button class="fin-btn" type="submit">Confirmar baixa total</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotItemRevertModal" aria-hidden="true">
        <div class="fin-modal__card" style="max-width: 560px;">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotItemRevertTitle">Reverter baixa do item</strong>
                <span class="lot-detail-modal__subhead">Devolva o saldo ao item quando uma baixa operacional precisar ser corrigida.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotItemRevertClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <form class="lot-baixa-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotOpsAnchor') ?>" id="lotItemRevertForm">
              <input type="hidden" name="lot_item_baixa_revert_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="revert_item_id" id="lotRevertItemId" value="">

              <div class="lot-timeline-form__intro">
                <div class="lot-timeline-form__badge">
                  <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                  <span id="lotRevertItemNome">Item</span>
                </div>
                <p>Informe a quantidade que deve retornar para o disponível do item.</p>
              </div>

              <div class="lot-timeline-form__grid">
                <label class="lot-field">
                  <span>Quantidade a reverter</span>
                  <input type="number" name="revert_quantidade" id="lotRevertQuantidadeInput" min="0.001" step="0.001" inputmode="decimal" required>
                </label>
                <label class="lot-field">
                  <span>Data</span>
                  <input type="date" name="revert_data" value="<?= h(date('Y-m-d')) ?>" required>
                </label>
              </div>

              <div class="lot-field">
                <span>Baixado no momento</span>
                <div class="lot-timeline-form__readonly" id="lotRevertBaixadoPreview">0,000</div>
              </div>

              <label class="lot-field">
                <span>Observação da reversão</span>
                <textarea class="lot-timeline-form__textarea" name="revert_observacao" rows="4" placeholder="Descreva o motivo da reversão da baixa."></textarea>
              </label>

              <div class="lot-timeline-form__actions">
                <button class="fin-btn fin-btn--ghost" id="lotItemRevertCancel" type="button">Cancelar</button>
                <button class="fin-btn" type="submit">Confirmar reversão</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotItemHistoryModal" aria-hidden="true">
        <div class="fin-modal__card lot-item-ficha-modal__card">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotItemHistoryTitle">Ficha do produto</strong>
                <span class="lot-detail-modal__subhead">Visualize fotos, dados operacionais e movimentações do item em um único painel.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotItemHistoryClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <div class="lot-item-ficha">
              <div class="lot-create-hero lot-item-ficha__hero">
                <div class="lot-item-ficha__media" id="lotItemHistoryMedia">
                  <div class="lot-item-ficha__media-empty" id="lotItemHistoryMediaEmpty">
                    <i class="fa-solid fa-camera-retro" aria-hidden="true"></i>
                    <span>Sem fotos deste produto por enquanto.</span>
                  </div>
                  <img id="lotItemHistoryMainImage" alt="" hidden>
                </div>
                <div class="lot-create-hero__copy lot-item-ficha__copy">
                  <span class="lot-create-hero__eyebrow">Ficha do produto</span>
                  <h3 id="lotItemHistoryHeroName">Produto do lote</h3>
                  <p id="lotItemHistoryHeroText">Dados operacionais do item, galeria visual e movimentações recentes reunidos em uma única ficha.</p>
                  <div class="lot-create-hero__summary">
                    <div class="lot-create-hero__summary-item">
                      <span>Tipo</span>
                      <strong id="lotItemHistorySummaryType">Und</strong>
                    </div>
                    <div class="lot-create-hero__summary-item">
                      <span>Status</span>
                      <strong id="lotItemHistorySummaryStatus">Ativo</strong>
                    </div>
                    <div class="lot-create-hero__summary-item">
                      <span>Disponível</span>
                      <strong id="lotItemHistorySummaryAvailable">0,000</strong>
                    </div>
                  </div>
                </div>
              </div>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-box-open" aria-hidden="true"></i><span>Dados do produto</span></h3>
                  <p>Leitura operacional do item com quantidades, referência financeira e observações já registradas no lote.</p>
                </div>

                <div class="lot-item-ficha__facts">
                  <div class="lot-item-ficha__fact">
                    <span>Quantidade total</span>
                    <strong id="lotItemHistoryQtyTotal">0,000</strong>
                  </div>
                  <div class="lot-item-ficha__fact">
                    <span>Quantidade disponível</span>
                    <strong id="lotItemHistoryQtyAvailable">0,000</strong>
                  </div>
                  <div class="lot-item-ficha__fact">
                    <span>Quantidade vendida</span>
                    <strong id="lotItemHistoryQtySold">0,000</strong>
                  </div>
                  <div class="lot-item-ficha__fact">
                    <span>Quantidade baixada</span>
                    <strong id="lotItemHistoryQtyLow">0,000</strong>
                  </div>
                  <div class="lot-item-ficha__fact">
                    <span>Valor do salvado</span>
                    <strong id="lotItemHistoryBaseValue">R$ 0,00</strong>
                  </div>
                  <div class="lot-item-ficha__fact">
                    <span>Valor de venda sugerido</span>
                    <strong id="lotItemHistorySaleValue">R$ 0,00</strong>
                  </div>
                  <div class="lot-item-ficha__fact lot-item-ficha__fact--wide">
                    <span>Total sugerido</span>
                    <strong id="lotItemHistoryTotalValue">R$ 0,00</strong>
                  </div>
                </div>

                <div class="lot-item-ficha__notes" id="lotItemHistoryNotes">Sem observações adicionais para este produto.</div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-images" aria-hidden="true"></i><span>Fotos do produto</span></h3>
                  <p>Galeria visual do item para facilitar o reconhecimento rápido da mercadoria durante a operação.</p>
                </div>

                <div class="lot-item-ficha__thumbs sv-attachments__grid" id="lotItemHistoryThumbs">
                  <div class="lot-inline-empty lot-inline-empty--compact">Ainda não há imagens enviadas para este produto.</div>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Movimentações do item</span></h3>
                  <p>Histórico consolidado das ações já registradas para este produto dentro do lote.</p>
                </div>

                <div class="lot-ops-history__scroll lot-item-ficha__history-scroll">
                  <div class="lot-timeline-list" id="lotItemHistoryList"></div>
                </div>
              </section>
            </div>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotDetailEditModal" aria-hidden="true">
        <div class="fin-modal__card lot-detail-modal__card">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline">Editar lote</strong>
                <span class="lot-detail-modal__subhead">Atualize dados do processo, local de coleta e custos sem poluir a ficha principal.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotDetailEditClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body lot-detail-modal__body">
            <div class="lot-detail-modal__stack">
              <form class="lot-detail-modal__form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId])) ?>" id="lotProcessDataForm">
                <input type="hidden" name="lot_process_update_submit" value="1">
                <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">

                <section class="lot-create-section">
                  <div class="lot-create-section__head">
                    <h3><i class="fa-solid fa-address-card" aria-hidden="true"></i><span>Identificação do processo</span></h3>
                    <p>Atualize os dados centrais do lote sem deixar a ficha mais pesada no uso diário.</p>
                  </div>

                  <div class="lot-item-form__grid lot-detail-editor__process-grid">
                    <label class="lot-field lot-item-form__field lot-detail-editor__field--supplier">
                      <span>Seguradora</span>
                      <input type="text" value="<?= h($selectedFornecedorNome) ?>" readonly>
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--process">
                      <span>N Processo</span>
                      <input type="text" name="numero_processo" maxlength="60" value="<?= h((string)($selectedLote['numeroProcesso'] ?? '')) ?>" placeholder="Ex.: PROC-2026-0001">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--sinistro">
                      <span>N Sinistro</span>
                      <input type="text" name="numero_sinistro" maxlength="60" value="<?= h($selectedNumeroSinistro) ?>" placeholder="Ex.: SIN-2026-1010">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--title">
                      <span>Título</span>
                      <input type="text" name="titulo_lote" maxlength="120" value="<?= h((string)($selectedLote['tituloLote'] ?? '')) ?>" placeholder="Ex.: Lote de ferragens industriais">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--summary">
                      <span>Descrição</span>
                      <textarea name="descricao_resumida" rows="3" placeholder="Resumo do processo e da composição principal do lote."><?= h((string)($selectedLote['descricaoResumida'] ?? '')) ?></textarea>
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--date">
                      <span>Data</span>
                      <input type="date" name="data_compra" value="<?= h((string)($selectedLote['dataCompra'] ?? '')) ?>">
                    </label>
                  </div>
                </section>

                <section class="lot-create-section">
                  <div class="lot-create-section__head">
                    <h3><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i><span>Valores iniciais</span></h3>
                    <p>Base financeira do processo desde o nascimento do lote.</p>
                  </div>

                  <div class="lot-item-form__grid lot-detail-editor__mini-grid">
                    <label class="lot-field lot-item-form__field">
                      <span>Valor do salvado</span>
                      <input type="text" name="valor_salvado" data-lot-money inputmode="decimal" value="<?= h(lot_money((float)($selectedLote['valorOriginalLote'] ?? 0))) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Valor da compra</span>
                      <input type="text" name="valor_pago_compra" data-lot-money inputmode="decimal" value="<?= h(lot_money((float)($selectedLote['valorPagoCompra'] ?? 0))) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Pagamento da compra</span>
                      <select name="status_pagamento_compra">
                        <option value="pendente" <?= (string)($selectedPurchasePayment['status'] ?? 'pendente') === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="pago" <?= (string)($selectedPurchasePayment['status'] ?? 'pendente') === 'pago' ? 'selected' : '' ?>>Pago</option>
                      </select>
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Data do pagamento</span>
                      <input type="date" name="data_pagamento_compra" value="<?= h((string)($selectedPurchasePayment['paidAt'] ?? '')) ?>">
                    </label>
                  </div>
                </section>

                <div class="lot-item-form__actions lot-item-form__actions--inline lot-detail-modal__actions">
                  <button class="fin-btn" type="submit">Salvar dados do processo</button>
                </div>
              </form>

              <form class="lot-detail-modal__form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId])) ?>" id="lotStorageDataForm">
                <input type="hidden" name="lot_storage_update_submit" value="1">
                <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">

                <section class="lot-create-section">
                  <div class="lot-create-section__head">
                    <h3><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span>Local de armazenagem</span></h3>
                    <p>Atualize o local de coleta / armazenagem e os custos locais no mesmo padrão já existente do módulo.</p>
                  </div>

                  <div class="lot-item-form__grid lot-detail-editor__storage-grid">
                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-name">
                      <span>Local armazenagem</span>
                      <input type="text" name="nome_local" maxlength="120" value="<?= h((string)($selectedLote['nomeLocal'] ?? '')) ?>" placeholder="Ex.: Pátio principal da operação">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-address">
                      <span>End</span>
                      <input type="text" name="endereco" maxlength="160" value="<?= h((string)($selectedLote['endereco'] ?? '')) ?>" placeholder="Rua, número e complemento">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-city">
                      <span>Cidade</span>
                      <input type="text" name="cidade" maxlength="80" value="<?= h((string)($selectedLote['cidade'] ?? '')) ?>" placeholder="Cidade">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-state">
                      <span>ES</span>
                      <select name="estado">
                        <option value="">Selecione</option>
                        <?php foreach (lot_ufs() as $uf => $ufLabel): ?>
                          <option value="<?= h($uf) ?>" <?= lot_normalize_state_uf((string)($selectedLote['estado'] ?? '')) === $uf ? 'selected' : '' ?>><?= h($uf) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-contact">
                      <span>Contato no local</span>
                      <input type="text" name="nome_contato" maxlength="120" value="<?= h((string)($selectedLote['nomeContato'] ?? '')) ?>" placeholder="Responsável ou ponto focal">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-doc">
                      <span>CPF/CNPJ</span>
                      <input type="text" name="cpf_cnpj_local" maxlength="20" data-lot-mask="documento" value="<?= h($selectedCpfCnpjLocal) ?>" placeholder="Documento do local">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-phone">
                      <span>Telefone 1</span>
                      <input type="text" name="telefone" maxlength="20" data-lot-mask="telefone" value="<?= h((string)($selectedLote['telefone'] ?? '')) ?>" placeholder="(00) 00000-0000">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-phone-alt">
                      <span>Telefone 2</span>
                      <input type="text" name="telefone_2" maxlength="20" data-lot-mask="telefone" value="<?= h($selectedTelefoneDois) ?>" placeholder="Telefone adicional">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-email">
                      <span>Email</span>
                      <input type="email" name="email" maxlength="120" value="<?= h((string)($selectedLote['email'] ?? '')) ?>" placeholder="contato@empresa.com">
                    </label>

                    <label class="lot-field lot-item-form__field lot-detail-editor__field--storage-notes">
                      <span>Observações do local</span>
                      <textarea name="observacoes_local_livre" rows="3" placeholder="Detalhes adicionais do local, acesso, restrições ou recados operacionais."><?= h($selectedObservacoesLocalLivres) ?></textarea>
                    </label>
                  </div>
                </section>

                <section class="lot-create-section">
                  <div class="lot-create-section__head">
                    <h3><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i><span>Custos locais</span></h3>
                    <p>Leitura financeira do armazenamento e das despesas locais do lote, com total consolidado.</p>
                  </div>

                  <div class="lot-item-form__grid lot-detail-editor__cost-grid">
                    <label class="lot-field lot-item-form__field">
                      <span>Custo Armazenagem</span>
                      <input type="text" name="custo_armazenagem" id="lotStorageCustoArmazenagem" data-lot-money inputmode="decimal" value="<?= h(lot_money($selectedCustoArmazenagem)) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Custo Carregamento</span>
                      <input type="text" name="custo_carregamento" id="lotStorageCustoCarregamento" data-lot-money inputmode="decimal" value="<?= h(lot_money($selectedCustoCarregamento)) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Custo SOS</span>
                      <input type="text" name="custo_sos" id="lotStorageCustoSos" data-lot-money inputmode="decimal" value="<?= h(lot_money($selectedCustoSos)) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Outros</span>
                      <input type="text" name="outros_custos" id="lotStorageOutrosCustos" data-lot-money inputmode="decimal" value="<?= h(lot_money($selectedOutrosLocais)) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field">
                      <span>Total dos custos</span>
                      <input type="text" id="lotStorageCustosTotal" value="<?= h(lot_money($selectedCustosLocaisTotal)) ?>" readonly>
                    </label>
                  </div>
                </section>

                <div class="lot-item-form__actions lot-item-form__actions--inline lot-detail-modal__actions">
                  <button class="fin-btn" type="submit">Salvar local e custos</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotItemManageModal" aria-hidden="true">
        <div class="fin-modal__card lot-detail-modal__card lot-detail-modal__card--item">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotItemManageTitle">Cadastro de item</strong>
                <span class="lot-detail-modal__subhead">Cadastre ou edite os produtos do lote com fotos, valores e observações operacionais.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotItemManageClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body lot-detail-modal__body">
            <form class="lot-item-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId])) ?>" id="lotItemForm" enctype="multipart/form-data">
              <input type="hidden" name="lot_item_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="item_id" id="lotItemIdInput" value="">

              <div class="lot-item-form__grid">
                <label class="lot-field lot-item-form__field lot-item-form__field--wide">
                  <span>Descrição do produto</span>
                  <input type="text" name="descricao_item" id="lotItemDescricaoInput" maxlength="160" placeholder="Ex.: notebooks, ferragens, autopeças..." required>
                </label>

                <label class="lot-field lot-item-form__field">
                  <span>Tipo</span>
                  <select name="tipo_controle_item" id="lotItemTipoInput">
                    <option value="unidade">Unidade</option>
                    <option value="kg">Kg</option>
                    <option value="metros">Metros</option>
                  </select>
                </label>

                <label class="lot-field lot-item-form__field">
                  <span>Quantidade</span>
                  <input type="number" name="quantidade_total" id="lotItemQuantidadeInput" min="0.001" step="0.001" inputmode="decimal" required>
                </label>

                <label class="lot-field lot-item-form__field">
                  <span>Valor do salvado</span>
                  <input type="text" name="custo_unitario_referencia" id="lotItemBaseInput" data-lot-money inputmode="decimal" placeholder="R$ 0,00">
                </label>

                <label class="lot-field lot-item-form__field">
                  <span>Valor de venda sugerido</span>
                  <input type="text" name="valor_venda_unitario_sugerido" id="lotItemVendaInput" data-lot-money inputmode="decimal" placeholder="R$ 0,00">
                </label>

                <label class="lot-field lot-item-form__field">
                  <span>Valor total</span>
                  <input type="text" id="lotItemTotalPreview" value="<?= h(lot_money(0)) ?>" readonly>
                </label>
              </div>

              <label class="lot-field">
                <span>Observações do item</span>
                <textarea name="observacoes_item" id="lotItemObservacoesInput" rows="3" placeholder="Use este campo se precisar registrar detalhes importantes do produto."></textarea>
              </label>

              <div class="lot-create-section lot-item-media-editor">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-camera-retro" aria-hidden="true"></i><span>Fotos do produto</span></h3>
                  <p>Inclua imagens para facilitar a identificação visual do item na operação e na ficha do produto.</p>
                </div>

                <div class="lot-item-media-editor__layout">
                  <div class="lot-item-media-editor__panel">
                    <div class="lot-item-media-editor__label">Imagens atuais</div>
                    <div class="lot-item-media-editor__grid" id="lotItemCurrentImages">
                      <div class="lot-inline-empty lot-inline-empty--compact">Salve o item para começar a montar a galeria de fotos.</div>
                    </div>
                    <div id="lotItemRemoveRelations"></div>
                  </div>

                  <div class="lot-item-media-editor__panel">
                    <label class="lot-field">
                      <span>Adicionar imagens</span>
                      <input type="file" name="item_image_files[]" id="lotItemImagesInput" accept="image/*" multiple>
                    </label>
                    <div class="lot-item-media-editor__label">Novos arquivos</div>
                    <div class="lot-item-media-editor__selected" id="lotItemSelectedImages">
                      Nenhuma nova imagem foi selecionada ainda.
                    </div>
                  </div>
                </div>
              </div>

              <div class="lot-item-form__actions">
                <button class="fin-btn fin-btn--ghost" type="button" id="lotItemCancelEdit">Limpar formulário</button>
                <button class="fin-btn" type="submit" id="lotItemSubmitButton">Adicionar item</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <script type="application/json" id="lotPrintPayload"><?= json_encode($lotReportPrintPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
      <script type="application/json" id="lotPrintListPayload"><?= json_encode($lotItemsPrintPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
      <script type="application/json" id="lotPrintSalesPayload"><?= json_encode($lotSalesPrintPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
      <script type="application/json" id="lotOccurrenceReportsPayload"><?= json_encode($lotOccurrenceReportsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
      <script type="application/json" id="lotCancelAttachmentsPayload"><?= json_encode($selectedCancelamentoAnexos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

      <section class="admin-block lot-detail__section" id="lotFreightAnchor">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><span>Frete e motorista</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="lot-ops-workspace lot-ops-workspace--freight">
            <article class="lot-ops-surface lot-ops-surface--freight-side">
              <section class="lot-freight-side__tags">
                <div class="lot-detail__section-head">
                  <h3><i class="fa-solid fa-tags" aria-hidden="true"></i><span>Tags do frete</span></h3>
                  <p>Defina o perfil logístico desta carga com palavras como veículo, carroceria e outras referências do transporte.</p>
                </div>

                <div class="cad-tag-editor lot-tag-editor lot-tag-editor--freight">
                  <form method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotFreightAnchor') ?>">
                    <input type="hidden" name="lot_freight_tag_submit" value="1">
                    <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                    <div class="cad-tag-editor__inputrow">
                      <input type="text" name="tag_nome" maxlength="80" placeholder="Ex.: BAÚ, TRUCK, PRANCHA">
                      <button type="submit" class="fin-btn fin-btn--ghost">Adicionar tag</button>
                    </div>
                  </form>
                  <div class="cad-tag-editor__chips lot-tag-editor__chips lot-tag-editor__chips--freight">
                    <?php if ($selectedFreightTags === []): ?>
                      <div class="lot-inline-empty lot-inline-empty--compact">Nenhuma tag de frete foi definida para este lote até o momento.</div>
                    <?php else: ?>
                      <?php foreach ($selectedFreightTags as $selectedFreightTag): ?>
                        <?php if (!is_array($selectedFreightTag)) { continue; } ?>
                        <form method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotFreightAnchor') ?>" class="lot-tag-chip-form">
                          <input type="hidden" name="lot_freight_tag_remove_submit" value="1">
                          <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                          <input type="hidden" name="tag_slug" value="<?= h((string)($selectedFreightTag['slug'] ?? '')) ?>">
                          <button type="submit" class="cad-tag-chip" data-tip="Excluir tag do frete">
                            <span><?= h((string)($selectedFreightTag['nome'] ?? '')) ?></span>
                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                          </button>
                        </form>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </section>

              <section class="lot-freight-side__suggestions">
                <div class="lot-detail__section-head">
                  <h3><i class="fa-solid fa-route" aria-hidden="true"></i><span>Sugestões de frete</span></h3>
                  <p>Cadastros com maior aderência à localização e ao perfil de frete atual do lote para acelerar a contratação do transporte.</p>
                </div>

                <?php if ($selectedFreightSuggestions === []): ?>
                  <div class="lot-inline-empty lot-inline-empty--compact">Nenhuma sugestão automática foi encontrada com base na localização e no perfil atual de frete deste lote.</div>
                <?php else: ?>
                  <div class="fin-table-wrap lot-compatible__tablewrap lot-freight-suggestions--scroll">
                    <table class="fin-table lot-compatible__table">
                      <thead>
                        <tr>
                          <th>Nome</th>
                          <th>Tipo</th>
                          <th>Telefone</th>
                          <th>Cidade</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($selectedFreightSuggestions as $suggestion): ?>
                          <?php $suggestionCityState = trim((string)($suggestion['cidade'] ?? '') . ((string)($suggestion['estado'] ?? '') !== '' ? ' / ' . (string)($suggestion['estado'] ?? '') : '')); ?>
                          <tr
                            class="lot-compatible__row"
                            tabindex="0"
                            role="button"
                            data-lot-freight-open="<?= h((string)($suggestion['id'] ?? 0)) ?>"
                            data-lot-freight-name="<?= h((string)($suggestion['nome'] ?? 'Cadastro')) ?>"
                          >
                            <td><div class="lot-compatible__name"><?= h((string)($suggestion['nome'] ?? '')) ?></div></td>
                            <td><?= h((string)($suggestion['tipo'] ?? 'Sem tipo')) ?></td>
                            <td><?= h((string)($suggestion['telefone'] ?? 'Não informado')) ?></td>
                            <td><?= h($suggestionCityState !== '' ? $suggestionCityState : 'Não informada') ?></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </section>
            </article>

            <article class="lot-ops-surface">
              <div class="lot-detail__section-head">
                <h3><i class="fa-solid fa-id-card-clip" aria-hidden="true"></i><span>Frete vinculado</span></h3>
                <p>Selecione o motorista ou a transportadora do lote. Se não encontrar no sistema, cadastre um novo vínculo sem sair desta tela.</p>
              </div>

              <form method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotFreightAnchor') ?>" id="lotFreightLinkForm">
                <input type="hidden" name="lot_freight_link_submit" value="1">
                <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                <input type="hidden" name="freight_cadastro_id" id="lotFreightCadastroId" value="">
              </form>
              <form method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotFreightAnchor') ?>" id="lotFreightUnlinkForm">
                <input type="hidden" name="lot_freight_unlink_submit" value="1">
                <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              </form>

              <form class="lot-freight-finance<?= $selectedFreightLinkedMeta !== [] ? ' lot-detail-editor' : '' ?>" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotFreightAnchor') ?>" id="lotFreightDataForm" <?= $selectedFreightLinkedMeta !== [] ? 'data-lot-editor-form' : '' ?>>
                <input type="hidden" name="lot_freight_update_submit" value="1">
                <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">

                <?php if ($selectedFreightLinkedMeta === []): ?>
                  <div class="lot-inline-empty lot-inline-empty--compact" id="lotFreightEmpty">Nenhum motorista ou transportadora foi vinculado a este lote até o momento.</div>
                <?php else: ?>
                  <article class="lot-freight-card" id="lotFreightCard">
                    <div class="lot-freight-card__visual">
                      <i id="lotFreightCardIcon" class="<?= h(($selectedFreightLinkedMeta['kind'] ?? '') === 'transportadora' ? 'fa-solid fa-building-user' : 'fa-solid fa-id-card') ?>" aria-hidden="true"></i>
                    </div>
                    <div class="lot-freight-card__body">
                      <span class="lot-freight-card__eyebrow" id="lotFreightCardEyebrow"><?= h((string)($selectedFreightLinkedMeta['tipo'] ?? 'Frete')) ?></span>
                      <strong id="lotFreightCardName"><?= h((string)($selectedFreightLinkedMeta['nome'] ?? '')) ?></strong>
                      <div class="lot-freight-card__meta">
                        <span id="lotFreightCardPhone"><?= h((string)($selectedFreightLinkedMeta['telefone'] ?? 'Não informado')) ?></span>
                        <?php if (trim((string)($selectedFreightLinkedMeta['documento'] ?? '')) !== ''): ?>
                          <span id="lotFreightCardCpf"><?= h((string)($selectedFreightLinkedMeta['documentoLabel'] ?? 'CPF')) ?> <?= h((string)$selectedFreightLinkedMeta['documento']) ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($selectedFreightLinkedMeta['cidadeEstado'] ?? '')) !== ''): ?>
                          <span id="lotFreightCardCityState"><?= h((string)$selectedFreightLinkedMeta['cidadeEstado']) ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($selectedFreightLinkedMeta['cnh'] ?? '')) !== ''): ?>
                          <span id="lotFreightCardCnh">CNH <?= h((string)$selectedFreightLinkedMeta['cnh']) ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($selectedFreightLinkedMeta['veiculo'] ?? '')) !== ''): ?>
                          <span id="lotFreightCardVeiculo"><?= h((string)$selectedFreightLinkedMeta['veiculo']) ?></span>
                        <?php endif; ?>
                        <?php if (trim((string)($selectedFreightLinkedMeta['placa'] ?? '')) !== ''): ?>
                          <span id="lotFreightCardPlaca">Placa <?= h((string)$selectedFreightLinkedMeta['placa']) ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="lot-freight-card__actions">
                      <button
                        class="fin-btn fin-btn--ghost"
                        type="button"
                        id="lotFreightOpenBtn"
                        data-lot-freight-open="<?= h((string)($selectedFreightLinkedMeta['id'] ?? 0)) ?>"
                        data-lot-freight-name="<?= h((string)($selectedFreightLinkedMeta['nome'] ?? 'Cadastro')) ?>"
                      >
                        Ver ficha
                      </button>
                      <button class="fin-btn fin-btn--ghost" type="button" data-lot-freight-focus-search data-lot-editor-edit-only hidden>Trocar</button>
                      <button class="fin-btn" type="submit" form="lotFreightUnlinkForm" data-lot-editor-edit-only hidden>Cancelar vínculo</button>
                    </div>
                  </article>
                <?php endif; ?>

                <fieldset class="lot-detail-editor__fieldset" data-lot-editor-fields <?= $selectedFreightLinkedMeta !== [] ? 'disabled' : '' ?>>
                  <input type="hidden" name="freight_cadastro_id" id="lotFreightSelectedCadastroId" value="<?= h((string)($selectedFreightLinkedMeta['id'] ?? 0)) ?>">
                  <div id="lotFreightSelectionBlock"<?= $selectedFreightLinkedMeta !== [] ? ' data-lot-editor-edit-only hidden' : '' ?>>
                    <div class="lot-detail__section-head">
                      <h3><i class="fa-solid fa-link" aria-hidden="true"></i><span>Seleção do frete</span></h3>
                      <p>Busque o motorista ou a transportadora e vincule ao lote antes de fechar os custos desta contratação.</p>
                    </div>

                    <div class="lot-freight-finance__lookup">
                      <div class="lot-field">
                        <span>Buscar motorista ou transportadora</span>
                        <div class="lot-quick-search lot-sale-customer-lookup lot-freight-lookup" id="lotFreightLookup" data-lot-freight-source='<?= h(json_encode(array_values(array_map(static function (array $cadastro): array {
                          $freightMeta = lot_freight_card_meta($cadastro);
                          return [
                            'id' => (int)($cadastro['id'] ?? 0),
                            'nome' => lot_cadastro_display_name($cadastro),
                            'tipo' => implode(' / ', lot_cadastro_tipo_labels($cadastro)),
                            'telefone' => lot_primary_phone($cadastro),
                            'documento' => (string)($freightMeta['documento'] ?? ''),
                            'documentoLabel' => (string)($freightMeta['documentoLabel'] ?? 'CPF'),
                            'cidade' => (string)($cadastro['cidade'] ?? ''),
                            'estado' => (string)($cadastro['estado'] ?? ''),
                            'kind' => (string)($freightMeta['kind'] ?? ''),
                            'cnh' => (string)($freightMeta['cnh'] ?? ''),
                            'veiculo' => (string)($freightMeta['veiculo'] ?? ''),
                            'placa' => (string)($freightMeta['placa'] ?? ''),
                            'searchIndex' => lot_normalize_search(implode(' ', [
                              lot_cadastro_display_name($cadastro),
                              implode(' ', lot_cadastro_tipo_labels($cadastro)),
                              lot_primary_phone($cadastro),
                              (string)($freightMeta['documento'] ?? ''),
                              (string)($cadastro['cidade'] ?? ''),
                              (string)($cadastro['estado'] ?? ''),
                            ])),
                          ];
                        }, $selectedFreightCadastros)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                          <div class="lot-quick-search__stack">
                            <div class="lot-quick-search__inputrow">
                              <label class="lot-quick-search__field">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input type="search" id="lotFreightSearch" placeholder="Busque por nome, tipo, telefone ou cidade" autocomplete="off">
                              </label>
                            </div>
                            <div class="lot-search-suggest" id="lotFreightResults" hidden></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="lot-detail__section-head">
                    <h3><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i><span>Custos do frete</span></h3>
                    <p>Registre os custos da contratação do frete. O total desta seção compõe o custo do lote.</p>
                  </div>

                  <div class="lot-item-form__grid">
                    <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                      <span>Data da contratação</span>
                      <input type="date" name="data_contratacao" value="<?= h((string)($selectedLote['dataContratacao'] ?? '')) ?>">
                    </label>

                    <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                      <span>Valor frete</span>
                      <input type="text" name="valor_frete" id="lotFreightValorFrete" data-lot-money inputmode="decimal" value="<?= h(lot_money((float)($selectedLote['valorFrete'] ?? 0))) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                      <span>Valor Doc.</span>
                      <input type="text" name="valor_documentacao" id="lotFreightValorDocumentacao" data-lot-money inputmode="decimal" value="<?= h(lot_money((float)($selectedLote['valorDocumentoTransporte'] ?? 0))) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                      <span>Valor Impos.</span>
                      <input type="text" name="valor_impostos" id="lotFreightValorImpostos" data-lot-money inputmode="decimal" value="<?= h(lot_money($selectedFreightImpostos)) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                      <span>Outros</span>
                      <input type="text" name="valor_outros_frete" id="lotFreightValorOutros" data-lot-money inputmode="decimal" value="<?= h(lot_money($selectedFreightOutros)) ?>" placeholder="R$ 0,00">
                    </label>

                    <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                      <span>Total do frete</span>
                      <input type="text" id="lotFreightValorTotal" value="<?= h(lot_money($selectedFreightTotal)) ?>" readonly>
                    </label>
                  </div>
                </fieldset>

                <?php if ($selectedFreightLinkedMeta !== []): ?>
                  <div class="lot-detail-editor__actions">
                    <button class="fin-btn fin-btn--ghost lot-detail-editor__btn" type="button" data-lot-editor-toggle>Editar</button>
                    <button class="fin-btn fin-btn--ghost lot-detail-editor__btn" type="button" data-lot-editor-cancel hidden>Cancelar</button>
                    <button class="fin-btn lot-detail-editor__btn" type="submit" data-lot-editor-save hidden>Salvar</button>
                  </div>
                <?php else: ?>
                  <div class="lot-item-form__actions lot-item-form__actions--inline">
                    <button class="fin-btn" type="submit">Salvar custos do frete</button>
                  </div>
                <?php endif; ?>
              </form>
            </article>
          </div>
        </div>
      </section>

      <section class="admin-block lot-detail__section" id="lotNotesAnchor">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>Anexos e observações</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="lot-detail-editor lot-detail-editor__end">
            <section class="lot-create-section">
              <div class="lot-create-section__head">
                <h3><i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>Anexos do lote</span></h3>
                <p>Os anexos do processo ficam separados por contexto para facilitar leitura, conferência e navegação rápida entre documentos, fotos e arquivos do frete.</p>
              </div>

              <div class="lot-attachments-grid">
                <?php foreach ($selectedAttachmentGroups as $attachmentGroup): ?>
                  <?php if ((string)($attachmentGroup['key'] ?? '') === 'cancelamento') { continue; } ?>
                  <?php
                  $attachmentItems = is_array($attachmentGroup['items'] ?? null) ? (array)$attachmentGroup['items'] : [];
                  $attachmentCount = count($attachmentItems);
                  $attachmentCover = $attachmentItems[0] ?? null;
                  $attachmentSubtitle = $attachmentCount > 0
                    ? (($attachmentCover['displayName'] ?? 'Arquivo') . ($attachmentCount > 1 ? ' + ' . ($attachmentCount - 1) . ' arquivo(s)' : ''))
                    : (string)($attachmentGroup['empty'] ?? 'Nenhum anexo disponível.');
                  ?>
                  <button
                    class="lot-attachments-card"
                    type="button"
                    data-lot-attachments-open="<?= h((string)($attachmentGroup['key'] ?? '')) ?>"
                  >
                    <span class="lot-attachments-card__visual">
                      <i class="<?= h((string)($attachmentGroup['icon'] ?? 'fa-solid fa-paperclip')) ?>" aria-hidden="true"></i>
                    </span>
                    <span class="lot-attachments-card__body">
                      <span class="lot-attachments-card__title"><?= h((string)($attachmentGroup['title'] ?? 'Anexos')) ?></span>
                      <span class="lot-attachments-card__desc"><?= h((string)($attachmentGroup['description'] ?? '')) ?></span>
                      <span class="lot-attachments-card__meta">
                        <strong><?= h((string)$attachmentCount) ?></strong>
                        <span><?= h($attachmentCount === 1 ? 'arquivo' : 'arquivos') ?></span>
                      </span>
                      <span class="lot-attachments-card__hint"><?= h($attachmentSubtitle) ?></span>
                    </span>
                    <span class="lot-attachments-card__cta">
                      <span>Abrir galeria</span>
                      <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </span>
                  </button>
                <?php endforeach; ?>
              </div>
            </section>

            <div class="lot-detail-editor__end-grid" id="lotPanelAnchor">
              <article class="lot-ops-surface lot-ops-surface--panel">
                <div class="lot-detail__section-head">
                  <h3><i class="fa-solid fa-rectangle-list" aria-hidden="true"></i><span>Painel do lote</span></h3>
                  <p>Centralize os relatórios principais do processo e as ações excepcionais de gestão do lote em um único painel.</p>
                </div>

                <div class="rep-grid lot-panel-grid">
                  <div class="rep-card lot-report-card" id="lotPrintButton" role="button" tabindex="0">
                    <div class="rep-card__icon"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></div>
                    <div class="rep-card__title">Relatório do lote</div>
                    <div class="rep-card__desc">Resumo consolidado com dados centrais, custos, frete, itens e status atual do processo.</div>
                    <div class="rep-card__cta"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Abrir</span></div>
                  </div>

                  <div class="rep-card lot-report-card" id="lotPrintListButtonPanel" role="button" tabindex="0">
                    <div class="rep-card__icon"><i class="fa-solid fa-boxes-stacked" aria-hidden="true"></i></div>
                    <div class="rep-card__title">Relatório de produtos</div>
                    <div class="rep-card__desc">Lista dos itens do lote com tipo, disponibilidade e referência financeira sugerida.</div>
                    <div class="rep-card__cta"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Abrir</span></div>
                  </div>

                  <div class="rep-card lot-report-card" id="lotPrintSalesButtonPanel" role="button" tabindex="0">
                    <div class="rep-card__icon"><i class="fa-solid fa-cart-flatbed-suitcase" aria-hidden="true"></i></div>
                    <div class="rep-card__title">Relatório de vendas</div>
                    <div class="rep-card__desc">Visão consolidada das vendas registradas, clientes, formas de pagamento e valores.</div>
                    <div class="rep-card__cta"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Abrir</span></div>
                  </div>
                </div>

                <div class="lot-panel-public lot-panel-payment">
                  <div class="lot-detail__section-head">
                    <h3><i class="fa-solid fa-money-check-dollar" aria-hidden="true"></i><span>Pagamento da compra</span></h3>
                    <p>Controle simples para saber se a compra deste lote já foi quitada ou ainda segue em aberto.</p>
                  </div>
                  <div class="lot-panel-public__body">
                    <div class="lot-panel-public__status">
                      <strong><?= h($selectedPurchasePaymentLabel) ?></strong>
                      <span>
                        <?php if ($selectedPurchaseOpenAmount > 0): ?>
                          Valor em aberto de <?= h(lot_money($selectedPurchaseOpenAmount)) ?> para este lote.
                        <?php elseif ((string)($selectedPurchasePayment['paidAt'] ?? '') !== ''): ?>
                          Compra quitada em <?= h(lot_date((string)($selectedPurchasePayment['paidAt'] ?? ''))) ?>.
                        <?php else: ?>
                          Nenhum valor em aberto para esta compra no momento.
                        <?php endif; ?>
                      </span>
                    </div>
                  </div>
                </div>

                <div class="lot-panel-public">
                  <div class="lot-detail__section-head">
                    <h3><i class="fa-solid fa-globe" aria-hidden="true"></i><span>Ficha pública do lote</span></h3>
                    <p>Publique este lote para acesso externo sem login, exibindo apenas dados comerciais, itens disponíveis e imagens de venda.</p>
                  </div>
                  <div class="lot-panel-public__body">
                    <div class="lot-panel-public__status">
                      <strong><?= !empty($selectedPublicConfig['published']) ? 'Ficha pública ativa' : 'Ficha pública desativada' ?></strong>
                      <span>
                        <?php if (!empty($selectedPublicConfig['published']) && $selectedPublicUrl !== ''): ?>
                          Link pronto para uso comercial em tempo real.
                        <?php else: ?>
                          Ative a publicação para gerar um link público deste lote.
                        <?php endif; ?>
                      </span>
                    </div>
                    <?php if (!empty($selectedPublicConfig['published']) && $selectedPublicUrl !== ''): ?>
                      <div class="lot-panel-public__links">
                        <a class="fin-btn fin-btn--ghost" href="<?= h($selectedPublicUrl) ?>" target="_blank" rel="noopener noreferrer">
                          <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>Abrir ficha pública</span>
                        </a>
                        <button class="fin-btn fin-btn--ghost" type="button" data-lot-public-share="<?= h($selectedPublicUrl) ?>">
                          <i class="fa-solid fa-share-nodes" aria-hidden="true"></i><span>Compartilhar ficha</span>
                        </button>
                        <a class="fin-btn fin-btn--ghost" href="<?= h($selectedPublicPrintUrl) ?>" target="_blank" rel="noopener noreferrer">
                          <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir lista pública</span>
                        </a>
                      </div>
                      <div class="lot-panel-public__url">
                        <span>URL pública</span>
                        <input type="text" value="<?= h($selectedPublicUrl) ?>" readonly>
                      </div>
                    <?php endif; ?>
                    <form class="lot-panel-public__form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotPanelAnchor') ?>">
                      <input type="hidden" name="lot_public_submit" value="1">
                      <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                      <input type="hidden" name="public_action" value="<?= !empty($selectedPublicConfig['published']) ? 'disable' : 'publish' ?>">
                      <button class="fin-btn <?= !empty($selectedPublicConfig['published']) ? 'fin-btn--ghost' : '' ?>" type="submit">
                        <i class="fa-solid <?= !empty($selectedPublicConfig['published']) ? 'fa-eye-slash' : 'fa-globe' ?>" aria-hidden="true"></i>
                        <span><?= !empty($selectedPublicConfig['published']) ? 'Desativar ficha pública' : 'Ativar ficha pública' ?></span>
                      </button>
                    </form>
                  </div>
                </div>

                <?php if ($selectedExceptionalEvents !== []): ?>
                  <div class="lot-panel-cancel__record">
                    <div class="lot-detail__section-head">
                      <h3><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>Ocorrências excepcionais</span></h3>
                      <p>Histórico das ocorrências que afetaram o lote, incluindo cancelamentos totais e devoluções parciais, com seus reflexos e documentos.</p>
                    </div>
                    <div class="lot-timeline-list">
                      <?php foreach ($selectedExceptionalEvents as $exceptionalEvent): ?>
                        <div class="lot-timeline-list__item">
                          <div class="lot-timeline-list__dot" aria-hidden="true"></div>
                          <div class="lot-timeline-list__content">
                            <strong><?= h((string)($exceptionalEvent['tipo'] ?? 'Ocorrência')) ?> • <?= h((string)($exceptionalEvent['motivo'] ?? '')) ?></strong>
                            <span><?= h((string)($exceptionalEvent['data'] ?? '')) ?><?php if (trim((string)($exceptionalEvent['statusLabel'] ?? '')) !== ''): ?> • <?= h((string)($exceptionalEvent['statusLabel'] ?? '')) ?><?php endif; ?> • <?= h((string)($exceptionalEvent['estorno'] ?? 'R$ 0,00')) ?><?php if ((string)($exceptionalEvent['vencimento'] ?? '') !== 'Não definido'): ?> • Recebimento previsto em <?= h((string)($exceptionalEvent['vencimento'] ?? '')) ?><?php endif; ?></span>
                            <span><?= h((string)($exceptionalEvent['relato'] ?? '')) ?></span>
                            <?php if (trim((string)($exceptionalEvent['financeiro'] ?? '')) !== '' && (string)($exceptionalEvent['financeiro'] ?? '') !== 'Não informado'): ?>
                              <span><?= h((string)($exceptionalEvent['financeiro'] ?? '')) ?></span>
                            <?php endif; ?>
                            <div class="lot-timeline-list__actions">
                              <button
                                class="lot-timeline-list__actionlink"
                                type="button"
                                data-tip="Editar ocorrência"
                                data-lot-cancel-edit
                                data-cancel-id="<?= h((string)($exceptionalEvent['id'] ?? 0)) ?>"
                                data-cancel-kind="<?= h((string)($exceptionalEvent['tipoKey'] ?? 'total')) ?>"
                                data-cancel-date="<?= h((string)($exceptionalEvent['dataIso'] ?? '')) ?>"
                                data-cancel-reason="<?= h((string)($exceptionalEvent['motivoRaw'] ?? '')) ?>"
                                data-cancel-report="<?= h((string)($exceptionalEvent['relatoRaw'] ?? '')) ?>"
                                data-cancel-amount="<?= h(number_format((float)($exceptionalEvent['estornoRaw'] ?? 0), 2, '.', '')) ?>"
                                data-cancel-due-date="<?= h((string)($exceptionalEvent['vencimentoIso'] ?? '')) ?>"
                                data-cancel-finance="<?= h((string)($exceptionalEvent['financeiroRaw'] ?? '')) ?>"
                                data-cancel-status="<?= h((string)($exceptionalEvent['statusKey'] ?? '')) ?>"
                              >
                                <i class="fa-solid fa-pen" aria-hidden="true"></i><span>Editar</span>
                              </button>
                              <?php if ((int)($exceptionalEvent['id'] ?? 0) > 0): ?>
                                <button
                                  class="lot-timeline-list__actionlink"
                                  type="button"
                                  data-tip="Relatório da ocorrência"
                                  data-lot-occurrence-print
                                  data-occurrence-id="<?= h((string)($exceptionalEvent['id'] ?? 0)) ?>"
                                >
                                  <i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Relatório</span>
                                </button>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                    <div class="lot-panel-cancel__attachments">
                      <div class="lot-panel-cancel__attachments-copy">
                        <strong>Documentos da ocorrência</strong>
                        <span><?= h(count($selectedCancelamentoAnexos) > 0 ? count($selectedCancelamentoAnexos) . ' arquivo(s) vinculado(s).' : 'Nenhum documento vinculado às ocorrências até o momento.') ?></span>
                      </div>
                      <button class="fin-btn fin-btn--ghost" type="button" data-lot-attachments-open="cancelamento">
                        <i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>Abrir documentos</span>
                      </button>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="lot-panel-cancel">
                  <div class="lot-panel-cancel__copy">
                    <strong>Status excepcional</strong>
                    <span>Use esta ação apenas quando o fluxo do lote precisar ser interrompido por desistência, falta de itens ou outro impedimento operacional real.</span>
                  </div>
                  <?php if ((string)($selectedLote['statusMacro'] ?? '') === 'cancelado'): ?>
                    <button class="fin-btn fin-btn--ghost" type="button" disabled>Lote já cancelado</button>
                  <?php else: ?>
                    <button class="fin-btn fin-btn--danger" type="button" id="lotCancelButton">
                      <i class="fa-solid fa-ban" aria-hidden="true"></i><span>Cancelar lote</span>
                    </button>
                  <?php endif; ?>
                </div>
              </article>

              <article class="lot-ops-surface lot-ops-surface--history">
                <div class="lot-detail__section-head">
                  <h3><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span>Movimentações recentes</span></h3>
                  <p>Histórico vivo do lote, registrando o andamento do processo e as ações feitas dentro desta ficha.</p>
                </div>
                <div class="lot-ops-history__scroll">
                  <?php if ($selectedMovimentacoesRecentes === []): ?>
                    <div class="lot-inline-empty lot-inline-empty--compact">Ainda não há movimentações registradas para este lote.</div>
                  <?php else: ?>
                    <div class="lot-timeline-list">
                      <?php foreach ($selectedMovimentacoesRecentes as $movimentacao): ?>
                        <?php if (!is_array($movimentacao)) { continue; } ?>
                        <div class="lot-timeline-list__item">
                          <div class="lot-timeline-list__dot" aria-hidden="true"></div>
                          <div class="lot-timeline-list__content">
                            <strong><?= h(lot_movement_summary($movimentacao)) ?></strong>
                            <span>
                              <?= h(lot_datetime_activity((string)($movimentacao['dataEvento'] ?? ''), (string)($movimentacao['createdAt'] ?? ''))) ?>
                              <?php $responsavelRecente = trim((string)($movimentacao['responsavel'] ?? '')); ?>
                              <?php if ($responsavelRecente !== ''): ?>
                                • <?= h($responsavelRecente) ?>
                              <?php endif; ?>
                            </span>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <div class="fin-modal" id="lotAttachmentsModal" aria-hidden="true">
        <div class="fin-modal__card lot-attachments-modal__card">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head lot-attachments-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotAttachmentsModalTitle">Anexos do lote</strong>
                <span class="lot-detail-modal__subhead">Organize documentos, fotos e arquivos do processo por grupo para consulta rápida.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotAttachmentsModalClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body lot-attachments-modal__body">
            <div class="sv-attachments">
              <div class="sv-attachments__drop lot-attachments-modal__drop">
                <div class="sv-attachments__drophead">
                  <div class="lot-attachments-modal__group">
                    <span class="lot-attachments-modal__eyebrow">Grupo</span>
                    <strong id="lotAttachmentsModalGroup">Anexos do processo</strong>
                    <p id="lotAttachmentsModalDescription">Documentos, imagens e arquivos organizados para consulta rápida.</p>
                  </div>

                  <div class="sv-attachments__actions">
                    <form
                      class="lot-attachments-modal__upload"
                      method="post"
                      action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotNotesAnchor') ?>"
                      enctype="multipart/form-data"
                    >
                      <input type="hidden" name="lot_attachment_upload_submit" value="1">
                      <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                      <input type="hidden" name="attachment_group" id="lotAttachmentUploadGroup" value="">
                      <label class="fin-btn fin-btn--ghost lot-attachments-modal__upload-btn">
                        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                        <span>Adicionar arquivos</span>
                        <input type="file" name="attachment_files[]" id="lotAttachmentUploadInput" multiple hidden>
                      </label>
                    </form>
                  </div>
                </div>

                <div class="sv-attachments__empty" id="lotAttachmentsEmpty">Nenhum arquivo neste grupo.</div>
                <div class="sv-attachments__grid" id="lotAttachmentsThumbs"></div>

                <form
                  method="post"
                  action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotNotesAnchor') ?>"
                  id="lotAttachmentRemoveForm"
                >
                  <input type="hidden" name="lot_attachment_remove_submit" value="1">
                  <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
                  <input type="hidden" name="attachment_group" id="lotAttachmentRemoveGroup" value="">
                  <input type="hidden" name="attachment_relation_id" id="lotAttachmentRemoveRelation" value="">
                  <input type="hidden" name="attachment_name" id="lotAttachmentRemoveName" value="">
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotCancelModal" aria-hidden="true">
        <div class="fin-modal__card lot-cancel-modal__card">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotCancelModalTitle">Ocorrência do lote</strong>
                <span class="lot-detail-modal__subhead">Registre cancelamentos totais ou devoluções parciais com motivo, relato, valor e documentos de apoio.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotCancelModalClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <form class="lot-baixa-form" method="post" action="<?= h(lot_module_url(['lote' => $viewLoteId]) . '#lotPanelAnchor') ?>" id="lotCancelForm" enctype="multipart/form-data">
              <input type="hidden" name="lot_cancel_submit" value="1">
              <input type="hidden" name="lote_id" value="<?= h((string)$viewLoteId) ?>">
              <input type="hidden" name="cancel_record_id" id="lotCancelRecordId" value="0">
              <input type="hidden" name="cancel_kind" id="lotCancelKindInput" value="total">

              <section class="lot-detail__hero lot-cancel-modal__hero">
                <div class="admin-block-body">
                  <div class="lot-detail__hero-grid">
                    <div class="lot-detail__hero-main">
                      <div class="lot-board-card__avatar lot-detail__avatar">
                        <img src="<?= h(app_url('/app/static/img/avatar-fornecedor.png')) ?>" alt="Lote">
                      </div>

                      <div class="lot-detail__hero-copy">
                        <div class="lot-detail__chips">
                          <span class="lot-status-chip is-danger" id="lotCancelHeroEyebrow">Ocorrência excepcional</span>
                          <span class="lot-priority-chip">Painel de ajuste</span>
                          <span class="lot-detail__process-chip"><?= h($selectedTimelineDisplayLabel) ?></span>
                        </div>

                        <h2 id="lotCancelHeroTitle"><?= h(lot_text_or_default((string)($selectedLote['tituloLote'] ?? ''), 'Lote sem título')) ?></h2>
                        <div class="lot-detail__headline">
                          <div class="lot-detail__headline-row">
                            <span class="lot-detail__headline-label">Lote:</span>
                            <strong id="lotCancelHeroText"><?= h($selectedResumo !== '' ? $selectedResumo : 'Resumo imediato ainda não preenchido para este lote.') ?></strong>
                          </div>
                          <div class="lot-detail__headline-row">
                            <span class="lot-detail__headline-label">Seguradora:</span>
                            <strong><?= h($selectedFornecedorNome) ?></strong>
                          </div>
                          <div class="lot-detail__headline-row">
                            <span class="lot-detail__headline-label">Nº processo / Nº sinistro:</span>
                            <strong><?= h($selectedProcessoNumero) ?> / <?= h($selectedNumeroSinistro !== '' ? $selectedNumeroSinistro : 'Não informado') ?></strong>
                          </div>
                        </div>
                      </div>
                    </div>

                    <div class="lot-detail__hero-side">
                      <div class="lot-kpi-card">
                        <div class="lot-kpi-card__icon is-money"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
                        <div class="lot-kpi-card__body">
                          <span class="lot-kpi-card__label">Custo total</span>
                          <strong class="lot-kpi-card__value"><?= h(lot_money((float)($selectedLote['custoTotal'] ?? 0))) ?></strong>
                        </div>
                      </div>
                      <div class="lot-kpi-card">
                        <div class="lot-kpi-card__icon is-sales"><i class="fa-solid fa-cash-register" aria-hidden="true"></i></div>
                        <div class="lot-kpi-card__body">
                          <span class="lot-kpi-card__label">Total vendido</span>
                          <strong class="lot-kpi-card__value"><?= h(lot_money($selectedValorVendidoAtual)) ?></strong>
                        </div>
                      </div>
                      <div class="lot-kpi-card">
                        <div class="lot-kpi-card__icon <?= $selectedResultadoParcial < 0 ? 'is-negative' : 'is-positive' ?>"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
                        <div class="lot-kpi-card__body">
                          <span class="lot-kpi-card__label">Lucro / prejuízo</span>
                          <strong class="lot-kpi-card__value <?= $selectedResultadoParcial < 0 ? 'is-negative' : 'is-positive' ?>"><?= h(lot_money($selectedResultadoParcial)) ?></strong>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-sliders" aria-hidden="true"></i><span>Tipo de ocorrência</span></h3>
                  <p>Defina se esta ação interrompe totalmente o lote ou apenas registra uma devolução parcial mantendo o processo ativo.</p>
                </div>

                <div class="lot-cancel-mode">
                  <label class="lot-cancel-mode__option is-active" data-lot-cancel-kind-option="total">
                    <input type="radio" name="cancel_kind_choice" value="total" checked>
                    <div class="lot-cancel-mode__icon"><i class="fa-solid fa-ban" aria-hidden="true"></i></div>
                    <strong>Cancelamento total</strong>
                    <span>Interrompe o lote e muda o status para cancelado.</span>
                  </label>
                  <label class="lot-cancel-mode__option" data-lot-cancel-kind-option="parcial">
                    <input type="radio" name="cancel_kind_choice" value="parcial">
                    <div class="lot-cancel-mode__icon"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i></div>
                    <strong>Devolução parcial</strong>
                    <span>Mantém o lote ativo e registra uma devolução parcial do investimento.</span>
                  </label>
                </div>
              </section>

              <div class="lot-timeline-form__grid">
                <label class="lot-field">
                  <span>Data da ocorrência</span>
                  <input type="date" name="cancel_data" value="<?= h(date('Y-m-d')) ?>">
                </label>

                <label class="lot-field">
                  <span>Motivo</span>
                  <input type="text" name="cancel_motivo" maxlength="140" placeholder="Ex.: DESISTÊNCIA DO SEGURADO" required>
                </label>

                <label class="lot-field lot-field--wide">
                  <span>Relato</span>
                  <textarea class="lot-timeline-form__textarea" name="cancel_relato" rows="4" placeholder="Descreva o que motivou esta ocorrência no lote."></textarea>
                </label>

                <label class="lot-field">
                  <span>Valor da devolução</span>
                  <input type="text" name="cancel_estorno" data-lot-money inputmode="decimal" placeholder="R$ 0,00">
                </label>

                <label class="lot-field">
                  <span>Data prevista de recebimento</span>
                  <input type="date" name="cancel_refund_due_date" id="lotCancelRefundDueDate">
                </label>

                <label class="lot-field lot-field--wide" id="lotCancelStatusField">
                  <span>Status do cancelamento</span>
                  <select name="cancel_status" id="lotCancelStatusSelect">
                    <?php foreach (lot_cancel_status_options() as $cancelStatusKey => $cancelStatusLabel): ?>
                      <option value="<?= h($cancelStatusKey) ?>"><?= h($cancelStatusLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <small class="lot-field__hint">Sem pagamento: cancelado sem devolução. Aguardando estorno: valor previsto, mas ainda não recebido. Estornado: devolução já concluída.</small>
                </label>

                <label class="lot-field lot-field--wide">
                  <span>Observação financeira</span>
                  <textarea class="lot-timeline-form__textarea" name="cancel_financeiro" rows="3" placeholder="Use este campo para registrar estorno, tratativa financeira ou outra repercussão econômica desta ocorrência."></textarea>
                </label>
              </div>

              <section class="lot-create-section lot-cancel-attachments">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-paperclip" aria-hidden="true"></i><span>Documentos da ocorrência</span></h3>
                  <p>Os arquivos enviados aqui entram no grupo de documentos da ocorrência e ficam acessíveis depois no bloco de ocorrências excepcionais do lote.</p>
                </div>

                <div class="lot-cancel-attachments__grid">
                  <div class="lot-cancel-attachments__upload">
                    <label class="lot-field">
                      <span>Anexar arquivos</span>
                      <input type="file" name="cancel_attachment_files[]" id="lotCancelAttachmentsInput" multiple>
                      <small class="lot-field__hint">Anexe comprovantes de estorno, mensagens, termos ou outros documentos que sustentem esta ocorrência.</small>
                    </label>
                  </div>

                  <div class="lot-cancel-attachments__gallery">
                    <div class="lot-cancel-attachments__selected" id="lotCancelSelectedFiles" hidden>
                      <strong>Arquivos selecionados agora</strong>
                      <div class="sv-attachments__grid" id="lotCancelSelectedFilesGrid"></div>
                    </div>

                    <?php if ($selectedCancelamentoAnexos === []): ?>
                      <div class="lot-inline-empty" id="lotCancelSavedFilesEmpty">Nenhum documento da ocorrência foi enviado ainda.</div>
                      <div class="sv-attachments__grid" id="lotCancelSavedFilesGrid" hidden></div>
                    <?php else: ?>
                      <strong class="lot-cancel-attachments__label">Documentos já vinculados</strong>
                      <div class="sv-attachments__grid" id="lotCancelSavedFilesGrid">
                        <?php foreach (array_slice($selectedCancelamentoAnexos, 0, 6) as $cancelAttachmentIndex => $cancelAttachment): ?>
                          <?php if (!is_array($cancelAttachment)) { continue; } ?>
                          <article class="sv-attachments__item lot-cancel-attachments__item">
                            <button class="sv-attachments__thumb" type="button" data-lot-cancel-saved-preview="<?= h((string)$cancelAttachmentIndex) ?>">
                              <?php if (!empty($cancelAttachment['thumbUrl'])): ?>
                                <img src="<?= h((string)$cancelAttachment['thumbUrl']) ?>" alt="<?= h((string)($cancelAttachment['displayName'] ?? 'Arquivo')) ?>">
                              <?php else: ?>
                                <i class="<?= h(!empty($cancelAttachment['isPdf']) ? 'fa-solid fa-file-pdf sv-attachments__thumbicon' : 'fa-solid fa-file-lines sv-attachments__thumbicon') ?>" aria-hidden="true"></i>
                              <?php endif; ?>
                            </button>
                            <div class="sv-attachments__meta">
                              <div class="sv-attachments__name"><?= h((string)($cancelAttachment['displayName'] ?? 'Arquivo')) ?></div>
                              <div class="sv-attachments__inforow">
                                <span class="sv-attachments__info sv-attachments__infoitem"><?= h(!empty($cancelAttachment['isImage']) ? 'Imagem' : (!empty($cancelAttachment['isPdf']) ? 'PDF' : 'Documento')) ?></span>
                              </div>
                            </div>
                          </article>
                        <?php endforeach; ?>
                      </div>
                      <div class="lot-inline-empty" id="lotCancelSavedFilesEmpty" hidden>Nenhum documento da ocorrência foi enviado ainda.</div>
                    <?php endif; ?>
                    <?php if ($selectedCancelamentoAnexos !== []): ?>
                      <button class="fin-btn fin-btn--ghost lot-cancel-attachments__open" type="button" data-lot-attachments-open="cancelamento">
                        <i class="fa-solid fa-images" aria-hidden="true"></i><span>Abrir documentos da ocorrência</span>
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </section>

              <div class="fin-modal__actions">
                <button class="fin-btn fin-btn--ghost" type="button" id="lotCancelModalDismiss">Voltar</button>
                <button class="fin-btn fin-btn--danger" type="submit" id="lotCancelSubmitButton">Confirmar cancelamento</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <?php
  return;
}

$filters = [
  'busca' => trim((string)($_GET['busca'] ?? '')),
  'estado' => trim((string)($_GET['estado'] ?? '')),
  'cidade' => trim((string)($_GET['cidade'] ?? '')),
  'status' => trim((string)($_GET['status'] ?? '')),
  'filtro_geral' => trim((string)($_GET['filtro_geral'] ?? '')),
  'periodo_de' => trim((string)($_GET['periodo_de'] ?? '')),
  'periodo_ate' => trim((string)($_GET['periodo_ate'] ?? '')),
  'sem_frete' => isset($_GET['sem_frete']) && $_GET['sem_frete'] === '1',
  'finalizado' => isset($_GET['finalizado']) && $_GET['finalizado'] === '1',
];

$viewMode = trim((string)($_GET['visao'] ?? 'ativos'));
if ($viewMode === 'encerrados') {
  $viewMode = 'finalizados';
}
if (!in_array($viewMode, ['ativos', 'estoque', 'finalizados', 'cancelados'], true)) {
  $viewMode = 'ativos';
}
if ($viewMode === 'ativos' && ($filters['status'] === 'finalizado' || $filters['finalizado'])) {
  $viewMode = 'finalizados';
}

$rawLotes = $loteRepo->list(['limit' => 300], 1, true);
$purchasePaymentMap = lot_purchase_payment_fetch_map(array_map(static fn(array $lote): int => (int)($lote['id'] ?? 0), array_values(array_filter($rawLotes, 'is_array'))), 1);
$fornecedorCache = [];
$states = [];
$cities = [];
$lotes = [];

foreach ($rawLotes as $lote) {
  if (!is_array($lote)) {
    continue;
  }

  $fornecedorId = (int)($lote['fornecedorId'] ?? 0);
  if ($fornecedorId > 0 && !array_key_exists($fornecedorId, $fornecedorCache)) {
    $fornecedorCache[$fornecedorId] = $cadastroRepo->findById($fornecedorId, 1);
  }

  $fornecedor = $fornecedorCache[$fornecedorId] ?? null;
  $fornecedorNome = '';
  if (is_array($fornecedor)) {
    $fornecedorNome = trim((string)($fornecedor['nome'] ?? ''));
    if ($fornecedorNome === '') {
      $fornecedorNome = trim((string)($fornecedor['razaoSocial'] ?? ''));
    }
  }

  $estado = trim((string)($lote['estado'] ?? ''));
  $cidade = trim((string)($lote['cidade'] ?? ''));
  if ($estado !== '') {
    $states[$estado] = $estado;
  }
  if ($cidade !== '') {
    $cities[$cidade] = $cidade;
  }

  $valorVendidoAtual = 0.0;
  $movimentacoesLote = is_array($lote['movimentacoes'] ?? null) ? (array)$lote['movimentacoes'] : [];
  foreach ($movimentacoesLote as $movimentacaoLote) {
    $valorVendidoAtual += lot_sale_delta_value($movimentacaoLote);
  }
  $resultadoParcial = $valorVendidoAtual - (float)($lote['custoTotal'] ?? 0);
  $statusMacro = (string)($lote['statusMacro'] ?? '');

  $lote['fornecedorNome'] = $fornecedorNome !== '' ? $fornecedorNome : 'Fornecedor não identificado';
  $lote['valorVendidoAtual'] = $valorVendidoAtual;
  $lote['resultadoParcial'] = $resultadoParcial;
  $lote['statusLabel'] = lot_status_label((string)($lote['statusMacro'] ?? ''));
  $lote['etapaLabel'] = lot_etapa_label((string)($lote['etapaTimeline'] ?? ''));
  $lote['priorityLabel'] = lot_priority_label($lote);
  $lote['cidadeEstado'] = trim($cidade . ' / ' . $estado, ' /');
  $cancelSummary = lot_cancel_summary_from_movimentacoes($movimentacoesLote, $statusMacro);
  $lote['cancelamentoStatus'] = (string)($cancelSummary['key'] ?? '');
  $lote['cancelamentoStatusLabel'] = (string)($cancelSummary['label'] ?? '');
  $lote['cancelamentoStatusRank'] = (int)($cancelSummary['rank'] ?? 99);
  $lote['cancelamentoData'] = (string)($cancelSummary['date'] ?? '');
  $lote['cancelamentoEstorno'] = (float)($cancelSummary['estorno'] ?? 0);
  $paymentConfig = $purchasePaymentMap[(int)($lote['id'] ?? 0)] ?? ['status' => 'pendente', 'paidAt' => '', 'updatedAt' => ''];
  $lote['purchasePaymentStatus'] = (string)($paymentConfig['status'] ?? 'pendente');
  $lote['purchasePaymentLabel'] = lot_purchase_payment_label((string)($paymentConfig['status'] ?? 'pendente'));
  $lote['purchasePaymentPaidAt'] = (string)($paymentConfig['paidAt'] ?? '');
  $lote['purchasePaymentOpenAmount'] = lot_purchase_payment_open_amount($lote, $paymentConfig);
  $lote['searchIndex'] = lot_normalize_search(implode(' ', [
    (string)($lote['numeroProcesso'] ?? ''),
    (string)($lote['tituloLote'] ?? ''),
    (string)($lote['descricaoResumida'] ?? ''),
    (string)($lote['descricaoOperacional'] ?? ''),
    $lote['fornecedorNome'],
    $lote['cidadeEstado'],
  ]));
  $lotes[] = $lote;
}

sort($states);
sort($cities);

$filteredLotes = array_values(array_filter($lotes, static function (array $lote) use ($filters): bool {
  if ($filters['busca'] !== '') {
    $needle = lot_normalize_search($filters['busca']);
    if ($needle !== '' && !str_contains((string)($lote['searchIndex'] ?? ''), $needle)) {
      return false;
    }
  }

  if ($filters['estado'] !== '' && strcasecmp((string)($lote['estado'] ?? ''), $filters['estado']) !== 0) {
    return false;
  }

  if ($filters['cidade'] !== '' && strcasecmp((string)($lote['cidade'] ?? ''), $filters['cidade']) !== 0) {
    return false;
  }

  if ($filters['status'] !== '' && (string)($lote['statusMacro'] ?? '') !== $filters['status']) {
    return false;
  }

  if ($filters['periodo_de'] !== '') {
    $lotDate = strtotime((string)($lote['dataCompra'] ?? ''));
    $fromDate = strtotime($filters['periodo_de']);
    if ($lotDate !== false && $fromDate !== false && $lotDate < $fromDate) {
      return false;
    }
  }

  if ($filters['periodo_ate'] !== '') {
    $lotDate = strtotime((string)($lote['dataCompra'] ?? ''));
    $toDate = strtotime($filters['periodo_ate'] . ' 23:59:59');
    if ($lotDate !== false && $toDate !== false && $lotDate > $toDate) {
      return false;
    }
  }

  if ($filters['sem_frete'] || $filters['filtro_geral'] === 'sem_frete') {
    $tipoTransporte = (string)($lote['tipoTransporte'] ?? '');
    $valorFrete = (float)($lote['valorFrete'] ?? 0);
    if (!($tipoTransporte === '' || $tipoTransporte === 'sem_frete' || $valorFrete <= 0)) {
      return false;
    }
  }

  if ($filters['filtro_geral'] === 'pagamento_pendente' && (string)($lote['purchasePaymentStatus'] ?? 'pendente') !== 'pendente') {
    return false;
  }

  if ($filters['filtro_geral'] === 'compra_paga' && (string)($lote['purchasePaymentStatus'] ?? 'pendente') !== 'pago') {
    return false;
  }

  if ($filters['filtro_geral'] === 'sem_vendas' && (float)($lote['valorVendidoAtual'] ?? 0) > 0) {
    return false;
  }

  if ($filters['filtro_geral'] === 'com_vendas' && (float)($lote['valorVendidoAtual'] ?? 0) <= 0) {
    return false;
  }

  if ($filters['filtro_geral'] === 'ocorrencia_excepcional') {
    $hasExceptionalOccurrence = false;
    foreach ((array)($lote['movimentacoes'] ?? []) as $movimentacaoFiltro) {
      if (!is_array($movimentacaoFiltro)) {
        continue;
      }
      if (in_array((string)($movimentacaoFiltro['tipoEvento'] ?? ''), ['lote_cancelado', 'lote_devolucao_parcial'], true)) {
        $hasExceptionalOccurrence = true;
        break;
      }
    }
    if (!$hasExceptionalOccurrence) {
      return false;
    }
  }

  if ($filters['finalizado'] && (string)($lote['statusMacro'] ?? '') !== 'finalizado') {
    return false;
  }

  return true;
}));

$statusGroups = [
  'em_transito' => [],
  'em_estoque' => [],
  'finalizado' => [],
  'cancelado' => [],
];

$stageCounts = [];
$totalInvestidoAbertos = 0.0;
$totalVendidoPeriodo = 0.0;
$pendingPurchaseCount = 0;
$paidPurchaseCount = 0;
$pendingPurchaseAmount = 0.0;

foreach ($filteredLotes as $lote) {
  $statusMacro = (string)($lote['statusMacro'] ?? '');
  if (!array_key_exists($statusMacro, $statusGroups)) {
    $statusMacro = 'em_transito';
  }

  $statusGroups[$statusMacro][] = $lote;
  if (in_array($statusMacro, ['em_transito', 'em_estoque'], true)) {
    $totalVendidoPeriodo += (float)($lote['valorVendidoAtual'] ?? 0);
    $totalInvestidoAbertos += (float)($lote['custoTotal'] ?? 0);
    if ((string)($lote['purchasePaymentStatus'] ?? 'pendente') === 'pago') {
      $paidPurchaseCount++;
    } else {
      $pendingPurchaseCount++;
      $pendingPurchaseAmount += (float)($lote['purchasePaymentOpenAmount'] ?? 0);
    }
  }

  $etapa = (string)($lote['etapaTimeline'] ?? '');
  $stageCounts[$etapa] = ($stageCounts[$etapa] ?? 0) + 1;
}

$saldoParcialAbertos = $totalVendidoPeriodo - $totalInvestidoAbertos;
$countEmTransito = count($statusGroups['em_transito']);
$countEmEstoque = count($statusGroups['em_estoque']);
$countFinalizado = count($statusGroups['finalizado']);
$countCancelado = count($statusGroups['cancelado']);
$totalLotes = count($filteredLotes);
$stageSummary = $totalLotes > 0
  ? sprintf(
    '%d trânsito • %d estoque • %d encerrados • %d cancelados',
    $countEmTransito,
    $countEmEstoque,
    $countFinalizado,
    $countCancelado
  )
  : 'Sem lotes no recorte atual';

$clearHref = lot_module_url();

$widgetShortcuts = [
  ['label' => 'Cadastros', 'icon' => 'fa-solid fa-id-card', 'href' => app_url('/app/templates/cadastros.php')],
  ['label' => 'Financeiro', 'icon' => 'fa-solid fa-coins', 'href' => app_url('/app/templates/financeiro.php')],
  ['label' => 'Relatórios', 'icon' => 'fa-solid fa-chart-line', 'href' => app_url('/app/templates/relatorios.php')],
  ['label' => 'Ferramentas', 'icon' => 'fa-solid fa-screwdriver-wrench', 'href' => app_url('/app/templates/ferramentas.php')],
];

$widgetActivitiesTitle = match ($viewMode) {
  'estoque' => 'Últimas movimentações do estoque',
  'finalizados' => 'Últimas movimentações dos finalizados',
  'cancelados' => 'Últimas movimentações dos cancelados',
  default => 'Últimas movimentações',
};
$widgetShortcutsTitle = 'Atalhos do sistema';
$widgetCalendarTitle = 'Calendário';
$widgetCalendarNote = match ($viewMode) {
  'estoque' => 'Recorte: estoque',
  'finalizados' => 'Recorte: finalizados',
  'cancelados' => 'Recorte: cancelados',
  default => 'Recorte: lotes ativos',
};
$fornecedoresCadastro = $cadastroRepo->list(['status' => 'ativo', 'limit' => 400], 1);

$quickActions = [
  [
    'label' => 'Novo lote',
    'icon' => 'fa-solid fa-plus',
    'href' => null,
    'meta' => 'Cadastrar um novo processo sem sair do dashboard.',
    'variant' => 'primary',
    'toast' => 'Abrindo o cadastro inicial do novo lote.',
    'toastKind' => 'info',
    'action' => 'open-create-modal',
  ],
  [
    'label' => 'Estoque completo',
    'icon' => 'fa-solid fa-layer-group',
    'href' => lot_module_url(['visao' => 'estoque']),
    'meta' => sprintf('%d lotes organizados por data de entrega', $countEmEstoque),
    'variant' => 'wide',
    'toast' => 'Abrindo a visão completa dos lotes em estoque.',
    'toastKind' => 'info',
  ],
  [
    'label' => 'Lotes finalizados',
    'icon' => 'fa-solid fa-circle-check',
    'href' => lot_module_url(['visao' => 'finalizados']),
    'meta' => sprintf('%d processos concluídos por venda / baixa', $countFinalizado),
    'variant' => 'wide',
    'toast' => 'Abrindo a visão de lotes finalizados.',
    'toastKind' => 'info',
  ],
  [
    'label' => 'Lotes cancelados',
    'icon' => 'fa-solid fa-folder-open',
    'href' => lot_module_url(['visao' => 'cancelados']),
    'meta' => sprintf('%d processos cancelados com histórico preservado', $countCancelado),
    'variant' => 'wide',
    'toast' => 'Abrindo a visão de lotes cancelados.',
    'toastKind' => 'info',
  ],
];

$transitoItems = $statusGroups['em_transito'] ?? [];
$estoqueItems = $statusGroups['em_estoque'] ?? [];
$finalizadoItems = $statusGroups['finalizado'] ?? [];
$canceladoItems = $statusGroups['cancelado'] ?? [];
usort($canceladoItems, static function (array $left, array $right): int {
  $statusCompare = ((int)($left['cancelamentoStatusRank'] ?? 99)) <=> ((int)($right['cancelamentoStatusRank'] ?? 99));
  if ($statusCompare !== 0) {
    return $statusCompare;
  }

  $leftDate = strtotime((string)($left['cancelamentoData'] ?? ($left['dataCompra'] ?? ''))) ?: 0;
  $rightDate = strtotime((string)($right['cancelamentoData'] ?? ($right['dataCompra'] ?? ''))) ?: 0;
  return $rightDate <=> $leftDate;
});
$estoquePreview = $viewMode === 'ativos' ? array_slice($estoqueItems, 0, 9) : $estoqueItems;
$estoqueHiddenCount = max(0, count($estoqueItems) - count($estoquePreview));
$viewScopedLotes = match ($viewMode) {
  'estoque' => $estoqueItems,
  'finalizados' => $finalizadoItems,
  'cancelados' => $canceladoItems,
  default => array_values(array_merge($transitoItems, $estoqueItems)),
};
$viewScopePendingPurchaseCount = 0;
$viewScopePaidPurchaseCount = 0;
$viewScopePendingPurchaseAmount = 0.0;
foreach ($viewScopedLotes as $viewScopeLote) {
  if (!is_array($viewScopeLote) || (string)($viewScopeLote['statusMacro'] ?? '') === 'cancelado') {
    continue;
  }
  if ((string)($viewScopeLote['purchasePaymentStatus'] ?? 'pendente') === 'pago') {
    $viewScopePaidPurchaseCount++;
    continue;
  }
  $viewScopePendingPurchaseCount++;
  $viewScopePendingPurchaseAmount += (float)($viewScopeLote['purchasePaymentOpenAmount'] ?? 0);
}
$viewScopeInvestimento = 0.0;
$viewScopeVendas = 0.0;
$viewScopeDevolucoes = 0.0;
$viewScopeRefundAwaiting = 0.0;
$viewScopeRefundConfirmed = 0.0;
$viewScopeStageCounts = [];
$viewScopeCancelCounts = [];
foreach ($viewScopedLotes as $scopeLote) {
  if (!is_array($scopeLote)) {
    continue;
  }
  $viewScopeInvestimento += (float)($scopeLote['custoTotal'] ?? 0);
  $viewScopeVendas += (float)($scopeLote['valorVendidoAtual'] ?? 0);
  $viewScopeDevolucoes += lot_refund_breakdown((array)($scopeLote['movimentacoes'] ?? []))['total'];
  $scopeEtapa = (string)($scopeLote['etapaTimeline'] ?? '');
  $viewScopeStageCounts[$scopeEtapa] = ($viewScopeStageCounts[$scopeEtapa] ?? 0) + 1;
  $cancelStatusKey = trim((string)($scopeLote['cancelamentoStatus'] ?? ''));
  if ($cancelStatusKey !== '') {
    $viewScopeCancelCounts[$cancelStatusKey] = ($viewScopeCancelCounts[$cancelStatusKey] ?? 0) + 1;
    $cancelEstorno = (float)($scopeLote['cancelamentoEstorno'] ?? 0);
    if ($cancelStatusKey === 'cancelado_aguardando_estorno') {
      $viewScopeRefundAwaiting += $cancelEstorno;
    }
    if ($cancelStatusKey === 'cancelado_estornado') {
      $viewScopeRefundConfirmed += $cancelEstorno;
    }
  }
}
$viewScopeSaldo = $viewScopeVendas - $viewScopeInvestimento;
$viewScopeCancelPending = max(0.0, $viewScopeInvestimento - $viewScopeRefundConfirmed);
$viewScopeStageSummary = count($viewScopedLotes) > 0
  ? implode(' • ', array_map(
    static fn (string $etapa, int $count): string => $count . ' ' . lot_etapa_label($etapa),
    array_keys($viewScopeStageCounts),
    array_values($viewScopeStageCounts)
  ))
  : 'Sem lotes no recorte atual';
$viewScopeCancelSummary = $viewScopeCancelCounts !== []
  ? implode(' • ', array_map(
    static fn (string $status, int $count): string => $count . ' ' . lot_cancel_status_label($status),
    array_keys($viewScopeCancelCounts),
    array_values($viewScopeCancelCounts)
  ))
  : 'Sem cancelamentos classificados';
$viewTitle = match ($viewMode) {
  'estoque' => 'Lotes em estoque',
  'finalizados' => 'Lotes finalizados',
  'cancelados' => 'Lotes cancelados',
  default => 'Mural operacional',
};
$viewHeadEyebrow = $viewMode === 'ativos' ? 'Centro operacional dos processos' : 'Navegação interna do módulo';
$viewHeadDescription = match ($viewMode) {
  'estoque' => 'Visão dedicada dos lotes em estoque, organizada para consulta completa do que já chegou ao pátio e segue disponível para operação.',
  'finalizados' => 'Visão dedicada dos lotes finalizados, com foco em processos concluídos e leitura consolidada do resultado do recorte.',
  'cancelados' => 'Visão dedicada dos lotes cancelados, preservando histórico, documentos e leitura do impacto financeiro das interrupções.',
  default => 'Entrada visual do módulo, organizada por status macro e conectada exclusivamente à persistência real implantada na Parte 4.2.',
};
$viewAnalyticsTitle = match ($viewMode) {
  'estoque' => 'Análise econômica dos lotes em estoque',
  'finalizados' => 'Análise econômica dos lotes finalizados',
  'cancelados' => 'Análise econômica dos lotes cancelados',
  default => 'Análise econômica dos lotes ativos',
};
$viewAnalyticsText = match ($viewMode) {
  'estoque' => 'Os gráficos abaixo usam apenas os lotes em estoque para mostrar investimento, composição de custos e distribuição do recorte atual.',
  'finalizados' => 'Os gráficos abaixo usam apenas os lotes finalizados para cruzar investimento, receitas, devoluções e resultado do recorte atual.',
  'cancelados' => 'Os gráficos abaixo usam apenas os lotes cancelados para mostrar investimento, devoluções, perdas e concentração do recorte atual.',
  default => 'Os gráficos abaixo cruzam investimentos, despesas, receitas e fornecedores dos lotes ativos sem tirar o foco operacional do mural.',
};
$viewEmptyTitle = match ($viewMode) {
  'estoque' => 'Nenhum lote em estoque encontrado.',
  'finalizados' => 'Nenhum lote finalizado encontrado.',
  'cancelados' => 'Nenhum lote cancelado encontrado.',
  default => 'Nenhum lote ativo encontrado.',
};
$viewEmptyText = match ($viewMode) {
  'estoque' => 'O recorte atual não retornou processos em estoque para consulta.',
  'finalizados' => 'O recorte atual não retornou processos finalizados para consulta.',
  'cancelados' => 'O recorte atual não retornou processos cancelados para consulta.',
  default => 'O recorte atual não retornou processos em trânsito ou em estoque para o mural principal.',
};
$viewSectionTitle = match ($viewMode) {
  'estoque' => 'Em estoque',
  'finalizados' => 'Finalizados',
  'cancelados' => 'Cancelados',
  default => '',
};
$viewSectionIcon = match ($viewMode) {
  'estoque' => 'fa-solid fa-warehouse',
  'finalizados' => 'fa-solid fa-circle-check',
  'cancelados' => 'fa-solid fa-ban',
  default => '',
};
$viewBackCta = $viewMode === 'ativos'
  ? null
  : ['label' => 'Voltar ao dashboard', 'href' => lot_module_url()];
$viewKpiOneLabel = match ($viewMode) {
  'estoque' => 'Total investido em estoque',
  'finalizados' => 'Investimento dos lotes finalizados',
  'cancelados' => 'Investimento dos lotes cancelados',
  default => 'Total investido em lotes abertos',
};
$viewKpiTwoLabel = match ($viewMode) {
  'estoque' => 'Compras pendentes no estoque',
  'finalizados' => 'Compras pendentes nos finalizados',
  'cancelados' => 'Aguardando estorno',
  default => 'Compras pendentes',
};
$viewKpiTwoValue = match ($viewMode) {
  'estoque' => (string)$viewScopePendingPurchaseCount,
  'finalizados' => (string)$viewScopePendingPurchaseCount,
  'cancelados' => lot_money($viewScopeRefundAwaiting),
  default => (string)$pendingPurchaseCount,
};
$viewKpiThreeLabel = match ($viewMode) {
  'estoque' => 'Valor em aberto do estoque',
  'finalizados' => 'Valor em aberto dos finalizados',
  'cancelados' => 'Estorno confirmado',
  default => 'Valor em aberto',
};
$viewKpiThreeValue = match ($viewMode) {
  'estoque' => lot_money($viewScopePendingPurchaseAmount),
  'finalizados' => lot_money($viewScopePendingPurchaseAmount),
  'cancelados' => lot_money($viewScopeRefundConfirmed),
  default => lot_money($pendingPurchaseAmount),
};
$viewKpiFourLabel = match ($viewMode) {
  'estoque' => 'Saldo do estoque',
  'finalizados' => 'Saldo dos finalizados',
  'cancelados' => 'Saldo pendente',
  default => 'Total vendido no período',
};
$viewKpiFourValue = match ($viewMode) {
  'ativos' => lot_money($totalVendidoPeriodo),
  'cancelados' => lot_money($viewScopeCancelPending),
  default => lot_money($viewScopeSaldo),
};
$viewKpiFiveLabel = match ($viewMode) {
  'cancelados' => 'Estágios de estorno',
  'finalizados' => 'Valor vendido no recorte',
  'estoque' => 'Valor vendido no recorte',
  default => 'Saldo parcial dos lotes em aberto',
};
$viewKpiFiveValue = match ($viewMode) {
  'ativos' => lot_money($saldoParcialAbertos),
  'cancelados' => $viewScopeCancelSummary,
  'estoque' => lot_money($viewScopeVendas),
  'finalizados' => lot_money($viewScopeVendas),
  default => lot_money($saldoParcialAbertos),
};
$viewKpiFiveIsMoney = $viewMode !== 'cancelados';
$viewKpiSixLabel = match ($viewMode) {
  'cancelados' => 'Resumo do recorte',
  'finalizados' => 'Compras pagas no recorte',
  'estoque' => 'Compras pagas no recorte',
  default => 'Compras pagas',
};
$viewKpiSixValue = match ($viewMode) {
  'cancelados' => $viewScopeStageSummary,
  'estoque' => (string)$viewScopePaidPurchaseCount,
  'finalizados' => (string)$viewScopePaidPurchaseCount,
  default => (string)$paidPurchaseCount,
};
$widgetActivities = [];
foreach ($viewScopedLotes as $lote) {
  $movimentacoes = $loteRepo->getMovimentacoes((int)($lote['id'] ?? 0), 1);
  foreach ($movimentacoes as $movimentacao) {
    if (!is_array($movimentacao)) {
      continue;
    }
    $widgetActivities[] = [
      'when' => (string)($movimentacao['createdAt'] ?? $movimentacao['dataEvento'] ?? ''),
      'title' => sprintf(
        '%s • %s',
        (string)($lote['numeroProcesso'] ?? 'Processo'),
        lot_movement_summary($movimentacao)
      ),
      'meta' => trim(implode(' • ', array_filter([
        lot_datetime_activity((string)($movimentacao['dataEvento'] ?? ''), (string)($movimentacao['createdAt'] ?? '')),
        (string)($lote['fornecedorNome'] ?? ''),
      ]))),
    ];
  }
}

usort($widgetActivities, static function (array $left, array $right): int {
  return strcmp((string)($right['when'] ?? ''), (string)($left['when'] ?? ''));
});
$widgetActivities = array_values(array_map(
  static fn (array $item): array => [
    'title' => (string)($item['title'] ?? ''),
    'meta' => (string)($item['meta'] ?? ''),
  ],
  array_slice($widgetActivities, 0, 4)
));
$principalSearchIndex = array_values(array_map(static function (array $lote): array {
  return [
    'id' => (int)($lote['id'] ?? 0),
    'numeroProcesso' => (string)($lote['numeroProcesso'] ?? ''),
    'tituloLote' => (string)($lote['tituloLote'] ?? ''),
    'fornecedorNome' => (string)($lote['fornecedorNome'] ?? ''),
    'statusLabel' => (string)($lote['statusLabel'] ?? ''),
    'href' => app_url('/app/templates/lotes.php?lote=' . (int)($lote['id'] ?? 0)),
    'searchIndex' => (string)($lote['searchIndex'] ?? ''),
  ];
}, $lotes));
$widgetCollapsible = true;
$lotAnalyticsRows = [];
$lotAnalyticsEvents = [];
foreach ($viewScopedLotes as $analyticsLote) {
  if (!is_array($analyticsLote)) {
    continue;
  }
  $analyticsMovements = is_array($analyticsLote['movimentacoes'] ?? null) ? (array)$analyticsLote['movimentacoes'] : [];
  $analyticsRefunds = lot_refund_breakdown($analyticsMovements);
  $analyticsCompraDate = substr((string)($analyticsLote['dataCompra'] ?? ''), 0, 10);
  $analyticsCompra = (float)($analyticsLote['valorPagoCompra'] ?? 0);
  $analyticsStorage = (float)lot_decimal_input(lot_extract_labeled_line((string)($analyticsLote['observacoesLocal'] ?? ''), 'Armazenagem:'), 2);
  $analyticsLoading = (float)lot_decimal_input(lot_extract_labeled_line((string)($analyticsLote['observacoesLocal'] ?? ''), 'Carregamento:'), 2);
  $analyticsSos = (float)lot_decimal_input(lot_extract_labeled_line((string)($analyticsLote['observacoesLocal'] ?? ''), 'SOS:'), 2);
  $analyticsOtherLocal = (float)lot_decimal_input(lot_extract_labeled_line((string)($analyticsLote['observacoesLocal'] ?? ''), 'Outros locais:'), 2);
  $analyticsCustosLocais = $analyticsStorage + $analyticsLoading + $analyticsSos + $analyticsOtherLocal;
  if ($analyticsCustosLocais <= 0) {
    $analyticsCustosLocais = (float)($analyticsLote['despesasLocal'] ?? 0);
  }
  $analyticsFrete = (float)($analyticsLote['valorFrete'] ?? 0);
  $analyticsDocumento = (float)($analyticsLote['valorDocumentoTransporte'] ?? 0);
  $analyticsFreightTax = (float)lot_decimal_input(lot_extract_labeled_line((string)($analyticsLote['observacoesLogisticas'] ?? ''), 'Impostos frete:'), 2);
  $analyticsFreightOther = (float)lot_decimal_input(lot_extract_labeled_line((string)($analyticsLote['observacoesLogisticas'] ?? ''), 'Outros frete:'), 2);
  $analyticsOutros = (float)($analyticsLote['outrosCustos'] ?? 0);
  $analyticsVendas = (float)($analyticsLote['valorVendidoAtual'] ?? 0);
  $analyticsInvestimento = (float)($analyticsLote['custoTotal'] ?? 0);
  $analyticsResultado = $analyticsVendas + $analyticsRefunds['total'] - $analyticsInvestimento;

  $lotAnalyticsRows[] = [
    'id' => (int)($analyticsLote['id'] ?? 0),
    'numeroProcesso' => (string)($analyticsLote['numeroProcesso'] ?? ''),
    'titulo' => (string)($analyticsLote['tituloLote'] ?? ''),
    'fornecedor' => (string)($analyticsLote['fornecedorNome'] ?? ''),
    'estado' => (string)($analyticsLote['estado'] ?? ''),
    'dataCompra' => $analyticsCompraDate,
    'compra' => $analyticsCompra,
    'armazenagem' => $analyticsStorage,
    'carregamento' => $analyticsLoading,
    'sos' => $analyticsSos,
    'outrosLocais' => $analyticsOtherLocal,
    'custosLocais' => $analyticsCustosLocais,
    'frete' => $analyticsFrete,
    'documentacao' => $analyticsDocumento,
    'impostosFrete' => $analyticsFreightTax,
    'outrosFrete' => $analyticsFreightOther,
    'outrosCustos' => $analyticsOutros,
    'investimento' => $analyticsInvestimento,
    'vendas' => $analyticsVendas,
    'refundTotal' => $analyticsRefunds['total'],
    'refundPartial' => $analyticsRefunds['partial'],
    'refundCancel' => $analyticsRefunds['cancel'],
    'resultado' => $analyticsResultado,
  ];

  if ($analyticsCompraDate !== '') {
    foreach ([
      ['kind' => 'investimento', 'value' => $analyticsInvestimento],
      ['kind' => 'frete', 'value' => $analyticsFrete + $analyticsDocumento + $analyticsFreightTax + $analyticsFreightOther],
      ['kind' => 'custos_locais', 'value' => $analyticsCustosLocais + $analyticsOutros],
    ] as $analyticsEvent) {
      if ((float)($analyticsEvent['value'] ?? 0) <= 0) {
        continue;
      }
      $lotAnalyticsEvents[] = [
        'date' => $analyticsCompraDate,
        'kind' => (string)$analyticsEvent['kind'],
        'value' => (float)$analyticsEvent['value'],
        'lotId' => (int)($analyticsLote['id'] ?? 0),
        'lotLabel' => trim((string)($analyticsLote['numeroProcesso'] ?? '') . ' • ' . (string)($analyticsLote['tituloLote'] ?? '')),
        'fornecedor' => (string)($analyticsLote['fornecedorNome'] ?? ''),
      ];
    }
  }

  foreach ($analyticsMovements as $analyticsMovement) {
    if (!is_array($analyticsMovement)) {
      continue;
    }
    $analyticsPayload = is_array($analyticsMovement['payloadEstrutural'] ?? null) ? (array)$analyticsMovement['payloadEstrutural'] : [];
    $analyticsEventDate = substr((string)($analyticsMovement['dataEvento'] ?? ''), 0, 10);
    if ($analyticsEventDate === '') {
      continue;
    }
    if ((string)($analyticsMovement['tipoEvento'] ?? '') === 'item_venda') {
      $analyticsValue = (float)($analyticsPayload['valor_total_vendido'] ?? 0);
      if ($analyticsValue > 0) {
        $lotAnalyticsEvents[] = [
          'date' => $analyticsEventDate,
          'kind' => 'vendas',
          'value' => $analyticsValue,
          'lotId' => (int)($analyticsLote['id'] ?? 0),
          'lotLabel' => trim((string)($analyticsLote['numeroProcesso'] ?? '') . ' • ' . (string)($analyticsLote['tituloLote'] ?? '')),
          'fornecedor' => (string)($analyticsLote['fornecedorNome'] ?? ''),
        ];
      }
      continue;
    }
    if (in_array((string)($analyticsMovement['tipoEvento'] ?? ''), ['lote_cancelado', 'lote_devolucao_parcial'], true)) {
      $analyticsValue = (float)($analyticsPayload['cancelamento_estorno'] ?? 0);
      if ($analyticsValue > 0) {
        $lotAnalyticsEvents[] = [
          'date' => $analyticsEventDate,
          'kind' => 'devolucoes',
          'value' => $analyticsValue,
          'lotId' => (int)($analyticsLote['id'] ?? 0),
          'lotLabel' => trim((string)($analyticsLote['numeroProcesso'] ?? '') . ' • ' . (string)($analyticsLote['tituloLote'] ?? '')),
          'fornecedor' => (string)($analyticsLote['fornecedorNome'] ?? ''),
        ];
      }
    }
  }
}
$lotAnalyticsPayload = [
  'viewMode' => $viewMode,
  'rows' => $lotAnalyticsRows,
  'events' => $lotAnalyticsEvents,
];
?>

<div class="module-page lot-page"
  <?= $timelineFlashMessage !== '' ? 'data-lot-page-flash="' . h($timelineFlashMessage) . '" data-lot-page-flash-kind="' . h($timelineFlashKind !== '' ? $timelineFlashKind : 'info') . '"' : '' ?>
  <?= $selectedOpenModal !== '' ? 'data-lot-open-modal="' . h($selectedOpenModal) . '"' : '' ?>>
  <div class="module-head lot-page__head">
    <div class="lot-head__topline">
      <div class="lot-page__eyebrow"><?= h($viewMode === 'ativos' ? 'Centro operacional dos processos' : 'Módulo Lotes') ?></div>
      <?php if ($viewMode !== 'ativos'): ?>
        <nav class="lot-crumbs" aria-label="Navegação do módulo Lotes">
          <a
            class="lot-crumbs__back"
            href="<?= h(lot_module_url()) ?>"
            data-tip="Voltar"
            data-lot-toast="Voltando ao dashboard principal."
            data-lot-toast-kind="info"
          >
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          </a>

          <div class="lot-crumbs__trail">
            <a href="<?= h(lot_module_url()) ?>">Lotes</a>
            <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            <span><?= h($viewTitle) ?></span>
          </div>
        </nav>
      <?php endif; ?>
    </div>
    <h1><?= h($viewMode === 'ativos' ? 'Dashboard de Lotes' : $viewTitle) ?></h1>
    <p><?= h($viewHeadDescription) ?></p>
  </div>

  <section class="lot-kpis">
    <article class="lot-kpi-card">
      <div class="lot-kpi-card__icon is-money"><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i></div>
      <div class="lot-kpi-card__body">
        <span class="lot-kpi-card__label"><?= h($viewKpiOneLabel) ?></span>
        <strong class="lot-kpi-card__value"><?= h(lot_money($viewMode === 'ativos' ? $totalInvestidoAbertos : $viewScopeInvestimento)) ?></strong>
      </div>
    </article>

    <article class="lot-kpi-card">
      <div class="lot-kpi-card__icon is-transit"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i></div>
      <div class="lot-kpi-card__body">
        <span class="lot-kpi-card__label"><?= h($viewKpiTwoLabel) ?></span>
        <strong class="lot-kpi-card__value"><?= h($viewKpiTwoValue) ?></strong>
      </div>
    </article>

    <article class="lot-kpi-card">
      <div class="lot-kpi-card__icon is-stock"><i class="fa-solid fa-warehouse" aria-hidden="true"></i></div>
      <div class="lot-kpi-card__body">
        <span class="lot-kpi-card__label"><?= h($viewKpiThreeLabel) ?></span>
        <strong class="lot-kpi-card__value"><?= h($viewKpiThreeValue) ?></strong>
      </div>
    </article>

    <article class="lot-kpi-card">
      <div class="lot-kpi-card__icon is-sales"><i class="fa-solid fa-cash-register" aria-hidden="true"></i></div>
      <div class="lot-kpi-card__body">
        <span class="lot-kpi-card__label"><?= h($viewKpiFourLabel) ?></span>
        <strong class="lot-kpi-card__value"><?= h($viewKpiFourValue) ?></strong>
      </div>
    </article>

    <article class="lot-kpi-card">
      <div class="lot-kpi-card__icon <?= ($viewMode === 'ativos' ? $saldoParcialAbertos : $viewScopeSaldo) < 0 ? 'is-negative' : 'is-positive' ?>"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i></div>
      <div class="lot-kpi-card__body">
        <span class="lot-kpi-card__label"><?= h($viewKpiFiveLabel) ?></span>
        <strong class="lot-kpi-card__value <?= ($viewMode === 'ativos' ? $saldoParcialAbertos : $viewScopeSaldo) < 0 ? 'is-negative' : 'is-positive' ?><?= $viewKpiFiveIsMoney ? '' : ' lot-kpi-card__value--small' ?>"><?= h($viewKpiFiveValue) ?></strong>
      </div>
    </article>

    <article class="lot-kpi-card">
      <div class="lot-kpi-card__icon is-stage"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></div>
      <div class="lot-kpi-card__body">
        <span class="lot-kpi-card__label"><?= h($viewKpiSixLabel) ?></span>
        <strong class="lot-kpi-card__value lot-kpi-card__value--small"><?= h($viewKpiSixValue) ?></strong>
      </div>
    </article>
  </section>

  <div class="lot-dashboard-top">
    <section class="lot-dashboard-top__main">
      <section class="admin-block lot-mobile-collapsible" id="lotPrimarySearchBlock">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Busca principal por processo</span></h2>
          <button class="fin-icon-btn fin-icon-btn--sm lot-mobile-toggle" type="button" data-lot-mobile-toggle aria-expanded="true" title="Alternar seção">
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
          </button>
        </div>
        <div class="admin-block-body">
          <div class="lot-quick-search lot-quick-search--primary" id="lotPrimarySearch" data-lot-search-source='<?= h(json_encode($principalSearchIndex, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
            <div class="lot-quick-search__head">
              <h3><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Busca principal por processo</span></h3>
              <p>Use esta busca para localizar rapidamente um processo e abrir a página interna do lote a partir da lista exibida durante a digitação.</p>
            </div>
            <div class="lot-quick-search__stack">
              <div class="lot-quick-search__inputrow">
                <label class="lot-quick-search__field">
                  <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                  <input type="search" id="lotPrimarySearchInput" placeholder="Digite o número do processo, título ou fornecedor" autocomplete="off">
                </label>
              </div>
              <div class="lot-search-suggest" id="lotPrimarySearchResults" hidden></div>
            </div>
          </div>
        </div>
      </section>

      <?php
      $quickActionsTop = array_values(array_filter($quickActions, static fn(array $item): bool => (string)($item['variant'] ?? '') === 'wide'));
      $quickActionPrimary = null;
      $quickActionFeatured = $quickActionsTop[0] ?? null;
      $quickActionSecondary = array_slice($quickActionsTop, 1);
      foreach ($quickActions as $actionItem) {
        if ((string)($actionItem['variant'] ?? '') === 'primary') {
          $quickActionPrimary = $actionItem;
          break;
        }
      }
      ?>
      <section class="admin-block">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-bolt" aria-hidden="true"></i><span>Ações do módulo</span></h2>
        </div>
        <div class="admin-block-body">
          <div class="lot-action-showcase">
            <?php if (is_array($quickActionPrimary)): ?>
              <?php $action = $quickActionPrimary; ?>
              <button
                class="lot-action-hero"
                type="button"
                data-lot-create-open
                data-lot-toast="<?= h((string)($action['toast'] ?? 'Abrindo ação do módulo.')) ?>"
                data-lot-toast-kind="<?= h((string)($action['toastKind'] ?? 'info')) ?>"
                title="<?= h((string)($action['meta'] ?? '')) ?>"
              >
                <span class="lot-action-hero__media">
                  <img src="<?= h(app_url('/app/static/img/caixa-visa.png')) ?>" alt="Novo lote">
                </span>
                <span class="lot-action-hero__body">
                  <span class="lot-action-hero__eyebrow">Cadastro inicial</span>
                  <strong>Cadastrar novo lote</strong>
                  <span><?= h((string)($action['meta'] ?? 'Inicie um novo processo sem sair do dashboard.')) ?></span>
                </span>
                <span class="lot-action-hero__toggle">
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </span>
              </button>
            <?php endif; ?>

            <div class="lot-action-stack">
              <?php if (is_array($quickActionFeatured)): ?>
                <?php $action = $quickActionFeatured; ?>
                <?php if (is_string($action['href'] ?? null) && trim((string)$action['href']) !== ''): ?>
                  <a class="lot-action-card lot-action-card--featured"
                     href="<?= h((string)$action['href']) ?>"
                     data-lot-toast="<?= h((string)($action['toast'] ?? 'Abrindo ação do módulo.')) ?>"
                     data-lot-toast-kind="<?= h((string)($action['toastKind'] ?? 'info')) ?>"
                     title="<?= h((string)($action['meta'] ?? '')) ?>">
                    <span class="lot-action-card__icon"><i class="<?= h((string)$action['icon'] ?? 'fa-solid fa-circle') ?>" aria-hidden="true"></i></span>
                    <span class="lot-action-card__body">
                      <strong><?= h((string)($action['label'] ?? 'Ação')) ?></strong>
                      <span><?= h((string)($action['meta'] ?? '')) ?></span>
                    </span>
                    <span class="lot-action-card__toggle"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                  </a>
                <?php endif; ?>
              <?php endif; ?>

              <?php if ($quickActionSecondary !== []): ?>
                <div class="lot-action-stack__grid">
                  <?php foreach ($quickActionSecondary as $secondaryIndex => $action): ?>
                    <?php if (is_string($action['href'] ?? null) && trim((string)$action['href']) !== ''): ?>
                      <a class="lot-action-card lot-action-card--compact"
                         href="<?= h((string)$action['href']) ?>"
                         data-lot-toast="<?= h((string)($action['toast'] ?? 'Abrindo ação do módulo.')) ?>"
                         data-lot-toast-kind="<?= h((string)($action['toastKind'] ?? 'info')) ?>"
                         title="<?= h((string)($action['meta'] ?? '')) ?>">
                        <span class="lot-action-card__icon"><i class="<?= h((string)$action['icon'] ?? 'fa-solid fa-circle') ?>" aria-hidden="true"></i></span>
                        <span class="lot-action-card__body">
                          <strong><?= h((string)($action['label'] ?? 'Ação')) ?></strong>
                          <span><?= h((string)($action['meta'] ?? '')) ?></span>
                        </span>
                        <span class="lot-action-card__toggle"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                      </a>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </section>

      <div class="fin-modal" id="lotCreateModal" aria-hidden="true">
        <div class="fin-modal__card lot-create-modal__card">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline">Novo lote</strong>
                <span class="lot-detail-modal__subhead">Abra um novo processo com a leitura inicial do lote e complemente o restante depois na ficha interna.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotCreateModalClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <form class="lot-item-form" method="post" action="<?= h(lot_module_url()) ?>" id="lotCreateForm" data-lot-create-defaults='<?= h(json_encode([
              'fornecedor_search' => '',
              'fornecedor_id' => '',
              'numero_processo' => '',
              'numero_sinistro' => '',
              'titulo_lote' => '',
              'descricao_resumida' => '',
              'data_compra' => date('Y-m-d'),
              'valor_salvado' => '',
              'valor_pago_compra' => '',
              'status_pagamento_compra' => 'pendente',
              'data_pagamento_compra' => '',
              'nome_local' => '',
              'endereco' => '',
              'cidade' => '',
              'estado' => '',
              'nome_contato' => '',
              'cpf_cnpj_local' => '',
              'telefone' => '',
              'telefone_2' => '',
              'email' => '',
              'custo_armazenagem' => '',
              'custo_carregamento' => '',
              'custo_sos' => '',
              'outros_custos' => '',
              'observacoes_gerais' => '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
              <input type="hidden" name="lot_create_submit" value="1">
              <?php if ($selectedOpenModal === 'create' && $timelineFlashMessage !== ''): ?>
                <div class="cad-form-alert <?= $timelineFlashKind === 'danger' ? 'cad-form-alert--danger' : 'cad-form-alert--info' ?> lot-create-modal__alert" aria-live="polite">
                  <?= h($timelineFlashMessage) ?>
                </div>
              <?php endif; ?>

              <section class="lot-create-hero">
                <div class="lot-create-hero__media">
                  <img src="<?= h(app_url('/app/static/img/caixa-visa.png')) ?>" alt="Cadastro de lotes">
                </div>
                <div class="lot-create-hero__copy">
                  <span class="lot-create-hero__eyebrow">Operação inicial do processo</span>
                  <h3>Cadastro de lotes</h3>
                  <p>Abra um novo processo com leitura rápida desde a compra. Os dados podem ser complementados depois na ficha interna.</p>
                  <div class="lot-create-hero__summary">
                    <div class="lot-create-hero__summary-item">
                      <span>Lote</span>
                      <strong id="lotCreatePreviewTitle">Novo lote sem título</strong>
                    </div>
                    <div class="lot-create-hero__summary-item">
                      <span>Seguradora</span>
                      <strong id="lotCreatePreviewSupplier">Selecione um fornecedor</strong>
                    </div>
                    <div class="lot-create-hero__summary-item">
                      <span>Referência</span>
                      <strong id="lotCreatePreviewProcess">Sem processo definido</strong>
                    </div>
                  </div>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-fingerprint" aria-hidden="true"></i><span>Identificação do processo</span></h3>
                  <p>Defina a seguradora e os dados centrais que identificam o lote desde o início.</p>
                </div>
                <div class="lot-item-form__grid">
                  <div class="lot-field lot-item-form__field lot-item-form__field--span-6">
                    <span>Seguradora</span>
                    <div class="lot-create-lookup">
                      <div class="lot-quick-search lot-sale-customer-lookup" id="lotCreateFornecedorLookup" data-lot-cadastro-source='<?= h(json_encode(array_values(array_map(static function (array $cadastro): array {
                        return [
                          'id' => (int)($cadastro['id'] ?? 0),
                          'nome' => (string)($cadastro['nome'] ?? $cadastro['razaoSocial'] ?? ''),
                          'documento' => (string)($cadastro['documento'] ?? ''),
                          'celular' => (string)($cadastro['celular'] ?? $cadastro['whatsapp'] ?? $cadastro['telefone'] ?? ''),
                          'searchIndex' => lot_normalize_search(implode(' ', [
                            (string)($cadastro['nome'] ?? ''),
                            (string)($cadastro['razaoSocial'] ?? ''),
                            (string)($cadastro['documento'] ?? ''),
                            (string)($cadastro['celular'] ?? ''),
                            (string)($cadastro['whatsapp'] ?? ''),
                            (string)($cadastro['telefone'] ?? ''),
                          ])),
                        ];
                      }, array_values(array_filter($fornecedoresCadastro, 'is_array')))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                        <div class="lot-quick-search__stack">
                          <div class="lot-quick-search__inputrow">
                            <label class="lot-quick-search__field">
                              <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                              <input type="search" id="lotCreateFornecedorSearch" name="fornecedor_search" placeholder="Busque por nome, CPF ou CNPJ" autocomplete="off" required value="<?= h((string)($lotCreateOldInput['fornecedor_search'] ?? '')) ?>">
                            </label>
                          </div>
                          <div class="lot-search-suggest" id="lotCreateFornecedorResults" hidden></div>
                        </div>
                      </div>
                    </div>
                    <input type="hidden" name="fornecedor_id" id="lotCreateFornecedorId" value="<?= h((string)($lotCreateOldInput['fornecedor_id'] ?? '')) ?>">
                  </div>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-3">
                    <span>Número do processo</span>
                    <input type="text" name="numero_processo" id="lotCreateNumeroProcesso" maxlength="80" placeholder="Ex.: LT-2026-001" required value="<?= h((string)($lotCreateOldInput['numero_processo'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-3">
                    <span>Número do sinistro</span>
                    <input type="text" name="numero_sinistro" id="lotCreateNumeroSinistro" maxlength="80" placeholder="Ex.: SIN-2026-001" value="<?= h((string)($lotCreateOldInput['numero_sinistro'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Título</span>
                    <input type="text" name="titulo_lote" id="lotCreateTitulo" maxlength="160" placeholder="Ex.: lote de ferragens industriais" required value="<?= h((string)($lotCreateOldInput['titulo_lote'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-4">
                    <span>Descrição</span>
                    <input type="text" name="descricao_resumida" maxlength="180" placeholder="Resumo curto para identificar rapidamente o processo." value="<?= h((string)($lotCreateOldInput['descricao_resumida'] ?? '')) ?>">
                  </label>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-sack-dollar" aria-hidden="true"></i><span>Valores iniciais</span></h3>
                  <p>Registre os dados financeiros básicos da compra para abrir o lote já com referência econômica.</p>
                </div>
                <div class="lot-item-form__grid">
                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Data da compra</span>
                    <input type="date" name="data_compra" value="<?= h((string)($lotCreateOldInput['data_compra'] ?? date('Y-m-d'))) ?>" required>
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Valor do salvado</span>
                    <input type="text" name="valor_salvado" data-lot-money inputmode="decimal" placeholder="R$ 0,00" value="<?= h((string)($lotCreateOldInput['valor_salvado'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Valor da compra</span>
                    <input type="text" name="valor_pago_compra" data-lot-money inputmode="decimal" placeholder="R$ 0,00" value="<?= h((string)($lotCreateOldInput['valor_pago_compra'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Pagamento da compra</span>
                    <select name="status_pagamento_compra">
                      <option value="pendente" <?= lot_purchase_payment_normalize_status((string)($lotCreateOldInput['status_pagamento_compra'] ?? 'pendente')) === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                      <option value="pago" <?= lot_purchase_payment_normalize_status((string)($lotCreateOldInput['status_pagamento_compra'] ?? 'pendente')) === 'pago' ? 'selected' : '' ?>>Pago</option>
                    </select>
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Data do pagamento</span>
                    <input type="date" name="data_pagamento_compra" value="<?= h((string)($lotCreateOldInput['data_pagamento_compra'] ?? '')) ?>">
                  </label>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-warehouse" aria-hidden="true"></i><span>Local de armazenagem</span></h3>
                  <p>Defina a base operacional do lote e o contato principal do local.</p>
                </div>
                <div class="lot-item-form__grid">
                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Local de armazenagem</span>
                    <input type="text" name="nome_local" maxlength="120" placeholder="Ex.: pátio central" value="<?= h((string)($lotCreateOldInput['nome_local'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Endereço</span>
                    <input type="text" name="endereco" maxlength="180" placeholder="Rua, número e complemento" value="<?= h((string)($lotCreateOldInput['endereco'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field">
                    <span>Cidade</span>
                    <input type="text" name="cidade" maxlength="80" placeholder="Cidade de coleta / armazenagem" value="<?= h((string)($lotCreateOldInput['cidade'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field">
                    <span>ES</span>
                    <select name="estado">
                      <option value="">Selecione</option>
                      <?php foreach (lot_ufs() as $uf => $ufLabel): ?>
                        <option value="<?= h($uf) ?>" <?= lot_normalize_state_uf((string)($lotCreateOldInput['estado'] ?? '')) === $uf ? 'selected' : '' ?>><?= h($uf) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-4">
                    <span>Contato no local</span>
                    <input type="text" name="nome_contato" maxlength="120" placeholder="Nome do responsável no local" value="<?= h((string)($lotCreateOldInput['nome_contato'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>CPF/CNPJ</span>
                    <input type="text" name="cpf_cnpj_local" maxlength="20" data-lot-mask="documento" placeholder="Opcional no cadastro inicial" value="<?= h((string)($lotCreateOldInput['cpf_cnpj_local'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Telefone 1</span>
                    <input type="text" name="telefone" maxlength="20" data-lot-mask="telefone" placeholder="Telefone principal" value="<?= h((string)($lotCreateOldInput['telefone'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Telefone 2</span>
                    <input type="text" name="telefone_2" maxlength="20" data-lot-mask="telefone" placeholder="Telefone adicional" value="<?= h((string)($lotCreateOldInput['telefone_2'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>E-mail</span>
                    <input type="email" name="email" maxlength="120" placeholder="contato@empresa.com" value="<?= h((string)($lotCreateOldInput['email'] ?? '')) ?>">
                  </label>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i><span>Custos locais</span></h3>
                  <p>Esses valores podem ser completados depois, mas já ajudam a abrir o processo com uma base de custo.</p>
                </div>
                <div class="lot-item-form__grid">
                  <label class="lot-field lot-item-form__field">
                    <span>Custo armazenagem</span>
                    <input type="text" name="custo_armazenagem" id="lotCreateCustoArmazenagem" data-lot-money inputmode="decimal" placeholder="R$ 0,00" value="<?= h((string)($lotCreateOldInput['custo_armazenagem'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field">
                    <span>Custo carregamento</span>
                    <input type="text" name="custo_carregamento" id="lotCreateCustoCarregamento" data-lot-money inputmode="decimal" placeholder="R$ 0,00" value="<?= h((string)($lotCreateOldInput['custo_carregamento'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field">
                    <span>Custo SOS</span>
                    <input type="text" name="custo_sos" id="lotCreateCustoSos" data-lot-money inputmode="decimal" placeholder="R$ 0,00" value="<?= h((string)($lotCreateOldInput['custo_sos'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field">
                    <span>Outros</span>
                    <input type="text" name="outros_custos" id="lotCreateOutrosCustos" data-lot-money inputmode="decimal" placeholder="R$ 0,00" value="<?= h((string)($lotCreateOldInput['outros_custos'] ?? '')) ?>">
                  </label>

                  <label class="lot-field lot-item-form__field lot-item-form__field--span-2">
                    <span>Total dos custos</span>
                    <input type="text" id="lotCreateCustosTotal" value="R$ 0,00" readonly>
                  </label>
                </div>
              </section>

              <section class="lot-create-section">
                <div class="lot-create-section__head">
                  <h3><i class="fa-solid fa-note-sticky" aria-hidden="true"></i><span>Observações</span></h3>
                  <p>Use este espaço para registrar o contexto inicial do processo, pendências ou detalhes importantes.</p>
                </div>
                <label class="lot-field">
                  <span>Observações iniciais</span>
                  <textarea class="lot-timeline-form__textarea" name="observacoes_gerais" rows="4" placeholder="Use este campo para registrar o contexto inicial do processo."><?= h((string)($lotCreateOldInput['observacoes_gerais'] ?? '')) ?></textarea>
                </label>
              </section>

              <div class="lot-item-form__actions">
                <button class="fin-btn fin-btn--ghost" type="button" id="lotCreateModalCancel">Cancelar</button>
                <button class="fin-btn" type="submit">Criar lote</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="fin-modal" id="lotCadastroInlineModal" aria-hidden="true">
        <div class="fin-modal__card" style="max-width: 1320px; width: min(1320px, calc(100vw - 40px));">
          <div class="fin-modal__head lot-sale-modal__head lot-detail-modal__head">
            <div class="lot-sale-modal__brand lot-detail-modal__brand">
              <img
                class="lot-sale-modal__brand-logo lot-detail-modal__brand-logo"
                src="<?= h($corp['report_logo'] ?? $corp['favicon'] ?? app_url('/app/static/img/favicon.png')) ?>"
                alt="<?= h($corp['company'] ?? 'Visa Remoções') ?>"
              >
              <div class="lot-sale-modal__brand-copy lot-detail-modal__brand-copy">
                <span class="lot-sale-modal__brand-name lot-detail-modal__brand-name"><?= h($corp['company'] ?? 'Visa Remoções') ?></span>
                <strong class="lot-sale-modal__headline lot-detail-modal__headline" id="lotCadastroInlineTitle">Novo cadastro</strong>
                <span class="lot-detail-modal__subhead">Cadastre um novo contato sem sair do fluxo do lote para continuar a operação com agilidade.</span>
              </div>
            </div>
            <button class="fin-modal__close" id="lotCadastroInlineClose" type="button" aria-label="Fechar">
              <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
          </div>
          <div class="fin-modal__body">
            <iframe class="lot-inline-frame" id="lotCadastroInlineFrame" title="Cadastro inline"></iframe>
          </div>
        </div>
      </div>

      <section class="admin-block" id="lotFilterBlock">
        <div class="admin-block-head">
          <h2 class="admin-block-title"><i class="fa-solid fa-filter" aria-hidden="true"></i><span>Busca de filtro por processo</span></h2>
        </div>
        <div class="admin-block-body">
          <form class="lot-filter-panel" method="get" action="<?= h(lot_module_url() . '#lotBoardAnchor') ?>" data-lot-filter-form>
            <?php if ($viewMode !== 'ativos'): ?>
              <input type="hidden" name="visao" value="<?= h($viewMode) ?>">
            <?php endif; ?>

            <div class="lot-filter-advanced" id="lotFilterAdvanced">
              <div class="lot-filter-grid">
              <label class="lot-field">
                <span>Estado</span>
                <select name="estado" <?= $states === [] ? 'disabled' : '' ?>>
                  <option value=""><?= $states === [] ? 'Sem estados cadastrados' : 'Todos' ?></option>
                  <?php foreach ($states as $state): ?>
                    <option value="<?= h($state) ?>" <?= $filters['estado'] === $state ? 'selected' : '' ?>><?= h($state) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label class="lot-field">
                <span>Cidade</span>
                <select name="cidade" <?= $cities === [] ? 'disabled' : '' ?>>
                  <option value=""><?= $cities === [] ? 'Sem cidades cadastradas' : 'Todas' ?></option>
                  <?php foreach ($cities as $city): ?>
                    <option value="<?= h($city) ?>" <?= $filters['cidade'] === $city ? 'selected' : '' ?>><?= h($city) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>

              <label class="lot-field">
                <span>Status macro</span>
                <select name="status">
                  <option value="">Todos</option>
                  <option value="em_transito" <?= $filters['status'] === 'em_transito' ? 'selected' : '' ?>>Em trânsito</option>
                  <option value="em_estoque" <?= $filters['status'] === 'em_estoque' ? 'selected' : '' ?>>Em estoque</option>
                  <option value="finalizado" <?= $filters['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                  <option value="cancelado" <?= $filters['status'] === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
              </label>

              <label class="lot-field">
                <span>Filtro geral</span>
                <select name="filtro_geral">
                  <option value="">Sem filtro adicional</option>
                  <option value="sem_frete" <?= $filters['filtro_geral'] === 'sem_frete' || $filters['sem_frete'] ? 'selected' : '' ?>>Lotes sem frete</option>
                  <option value="pagamento_pendente" <?= $filters['filtro_geral'] === 'pagamento_pendente' ? 'selected' : '' ?>>Pagamento pendente</option>
                  <option value="compra_paga" <?= $filters['filtro_geral'] === 'compra_paga' ? 'selected' : '' ?>>Compra paga</option>
                  <option value="sem_vendas" <?= $filters['filtro_geral'] === 'sem_vendas' ? 'selected' : '' ?>>Sem vendas</option>
                  <option value="com_vendas" <?= $filters['filtro_geral'] === 'com_vendas' ? 'selected' : '' ?>>Com vendas</option>
                  <option value="ocorrencia_excepcional" <?= $filters['filtro_geral'] === 'ocorrencia_excepcional' ? 'selected' : '' ?>>Com ocorrência excepcional</option>
                </select>
              </label>

              <label class="lot-field">
                <span>Período de compra</span>
                <input type="date" name="periodo_de" value="<?= h($filters['periodo_de']) ?>">
              </label>

              <label class="lot-field">
                <span>Até</span>
                <input type="date" name="periodo_ate" value="<?= h($filters['periodo_ate']) ?>">
              </label>
              </div>
            </div>

            <div class="lot-quick-search lot-quick-search--filter">
              <div class="lot-quick-search__head">
                <h3><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Busca de filtro por processo</span></h3>
                <p>Use esta busca para compor o recorte do mural atual. Depois de preencher o termo, clique em aplicar filtros para atualizar os lotes exibidos.</p>
              </div>
              <div class="lot-quick-search__inputrow">
                <label class="lot-quick-search__field">
                  <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                  <input type="search" id="lotBoardFilterSearch" name="busca" value="<?= h($filters['busca']) ?>" placeholder="Buscar por processo, fornecedor, título, descrição ou cidade" autocomplete="off">
                </label>
              </div>
              <div class="lot-search-suggest lot-search-suggest--feedback" id="lotBoardFilterFeedback" hidden></div>
            </div>

            <div class="lot-filter-actions">
              <button class="fin-btn lot-filter-btn" type="submit" data-lot-toast="Aplicando filtros do mural." data-lot-toast-kind="info">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Aplicar filtros</span>
              </button>
              <a class="fin-btn fin-btn--ghost lot-filter-btn" href="<?= h($clearHref) ?>" data-lot-toast="Limpando filtros do mural." data-lot-toast-kind="warning">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Limpar</span>
              </a>
            </div>

            <p class="lot-filter-note">
              <?php if ($states === [] || $cities === []): ?>
                Alguns filtros territoriais ainda não exibem opções porque esses dados ainda não estão preenchidos em todos os lotes cadastrados.
              <?php else: ?>
                Os campos abaixo refinam o recorte do mural. O filtro de venda parcial será ativado quando a venda operacional do lote entrar na interface na Parte 4.6.
              <?php endif; ?>
            </p>
          </form>
        </div>
      </section>
    </section>

    <aside class="admin-main-widgets lot-dashboard-top__aside">
      <?php require __DIR__ . '/../../templates/partials/admin_main_widgets.php'; ?>
    </aside>
  </div>

  <section class="admin-block" id="lotBoardAnchor">
    <div class="admin-block-head">
      <h2 class="admin-block-title"><i class="fa-solid fa-table-cells-large" aria-hidden="true"></i><span><?= h($viewTitle) ?></span></h2>
    </div>
    <div class="admin-block-body">
      <?php
      $sections = match ($viewMode) {
        'estoque' => [[
          'title' => $viewSectionTitle,
          'icon' => $viewSectionIcon,
          'items' => $estoqueItems,
          'cta' => $viewBackCta,
        ]],
        'finalizados' => [[
          'title' => $viewSectionTitle,
          'icon' => $viewSectionIcon,
          'items' => $finalizadoItems,
          'cta' => $viewBackCta,
        ]],
        'cancelados' => [[
          'title' => $viewSectionTitle,
          'icon' => $viewSectionIcon,
          'items' => $canceladoItems,
          'cta' => $viewBackCta,
        ]],
        default => [
          [
            'title' => 'Em trânsito',
            'icon' => 'fa-solid fa-truck-fast',
            'items' => $transitoItems,
            'cta' => null,
          ],
          [
            'title' => 'Em estoque',
            'icon' => 'fa-solid fa-warehouse',
            'items' => $estoquePreview,
            'count' => $countEmEstoque,
            'cta' => null,
          ],
        ],
      };
      ?>
      <?php if ($viewScopedLotes === []): ?>
          <div class="lot-empty-state">
            <strong><?= h($viewEmptyTitle) ?></strong>
            <p><?= h($viewEmptyText) ?></p>
          </div>
      <?php else: ?>
        <div class="lot-board">
          <?php foreach ($sections as $section): ?>
            <section class="lot-board-section">
              <div class="lot-board__head">
                <div class="lot-board__title">
                  <i class="<?= h((string)$section['icon']) ?>" aria-hidden="true"></i>
                  <span><?= h((string)$section['title']) ?></span>
                </div>
                <div class="lot-board__head-actions">
                  <?php $sectionCount = (int)($section['count'] ?? count((array)$section['items'])); ?>
                  <?php if ($viewMode === 'ativos' && (string)($section['title'] ?? '') === 'Em estoque'): ?>
                    <a class="lot-board__count lot-board__count--link"
                       href="<?= h(lot_module_url(['visao' => 'estoque'])) ?>"
                       data-lot-toast="Abrindo todos os lotes em estoque."
                       data-lot-toast-kind="info"
                       data-tip="Ver todos"
                       data-tip-place="left">
                      <span><?= h((string)$sectionCount) ?></span>
                    </a>
                  <?php else: ?>
                    <span class="lot-board__count"><?= h((string)$sectionCount) ?></span>
                  <?php endif; ?>
                  <?php if (is_array($section['cta'] ?? null)): ?>
                    <a class="fin-btn fin-btn--ghost lot-board__action-btn"
                       href="<?= h((string)$section['cta']['href']) ?>"
                       data-lot-toast="Voltando ao dashboard principal."
                       data-lot-toast-kind="info">
                      <i class="fa-solid fa-arrow-left" aria-hidden="true"></i><span><?= h((string)$section['cta']['label']) ?></span>
                    </a>
                  <?php endif; ?>
                </div>
              </div>

              <div class="lot-board__stack" data-lot-board-stack>
                <?php if ((array)$section['items'] === []): ?>
                  <div class="lot-board__empty">Nenhum lote neste estágio.</div>
                <?php else: ?>
                  <?php foreach ((array)$section['items'] as $lote): ?>
                    <?php lot_render_dashboard_card($lote); ?>
                  <?php endforeach; ?>
                  <div class="lot-board__empty lot-board__empty--js" data-lot-board-empty hidden>Nenhum lote corresponde à busca atual.</div>
                <?php endif; ?>
              </div>
            </section>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="admin-block lot-analytics" id="lotAnalyticsAnchor">
    <div class="admin-block-head">
      <h2 class="admin-block-title"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i><span>Painel analítico</span></h2>
    </div>
    <div class="admin-block-body">
      <div class="lot-analytics__intro">
        <strong><?= h($viewAnalyticsTitle) ?></strong>
        <p><?= h($viewAnalyticsText) ?></p>
      </div>

      <div class="lot-analytics__pairs" id="lotAnalyticsGrid">
        <article class="lot-analytics-card lot-analytics-card--wide lot-analytics-card--annual">
          <div class="lot-analytics-card__layout lot-analytics-card__layout--annual">
            <div class="lot-analytics-card__head">
              <div class="lot-analytics-card__title">
                <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                <span>Raio X anual de receitas x despesas</span>
              </div>
              <div class="lot-analytics-card__filters">
                <label class="lot-analytics-card__filter">
                  <span>Ano</span>
                  <select id="lotAnalyticsAnnualYear"></select>
                </label>
              </div>
            </div>
            <div class="lot-analytics-card__content lot-analytics-card__content--annual">
              <div class="lot-analytics-card__chart">
                <canvas id="lotAnalyticsAnnualChart" aria-label="Gráfico anual de receitas e despesas"></canvas>
              </div>

              <article class="lot-analytics-report-card lot-analytics-report-card--embedded">
                <div class="lot-analytics-report-card__head">
                  <h3><i class="fa-solid fa-file-waveform" aria-hidden="true"></i><span>Relatório anual</span></h3>
                </div>
                <div class="lot-analytics-report-card__body">
                  <div class="lot-analytics-report" id="lotAnalyticsAnnualReport"></div>
                </div>
                <div class="lot-analytics-report-card__foot">
                  <button class="fin-btn fin-btn--ghost" type="button" id="lotAnalyticsAnnualPrint">
                    <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir relatório</span>
                  </button>
                </div>
              </article>
            </div>
          </div>
        </article>

        <div class="lot-analytics__grid">
          <article class="lot-analytics-card lot-analytics-card--stacked">
            <div class="lot-analytics-card__head">
              <div class="lot-analytics-card__title">
                <i class="fa-solid fa-coins" aria-hidden="true"></i>
                <span>Despesas do lote</span>
              </div>
              <label class="lot-analytics-card__filter">
                <span>Mês</span>
                <select id="lotAnalyticsExpenseMonth"></select>
              </label>
            </div>
            <div class="lot-analytics-card__chart lot-analytics-card__chart--donut">
              <canvas id="lotAnalyticsExpenseChart" aria-label="Gráfico de despesas do lote"></canvas>
            </div>
            <article class="lot-analytics-report-card lot-analytics-report-card--embedded">
              <div class="lot-analytics-report-card__head">
                <h3><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i><span>Relatório de despesas</span></h3>
              </div>
              <div class="lot-analytics-report-card__body">
                <div class="lot-analytics-report" id="lotAnalyticsExpenseReport"></div>
              </div>
              <div class="lot-analytics-report-card__foot">
                <button class="fin-btn fin-btn--ghost" type="button" id="lotAnalyticsExpensePrint">
                  <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir relatório</span>
                </button>
              </div>
            </article>
          </article>

          <article class="lot-analytics-card lot-analytics-card--stacked">
            <div class="lot-analytics-card__head">
              <div class="lot-analytics-card__title">
                <i class="fa-solid fa-sack-dollar" aria-hidden="true"></i>
                <span id="lotAnalyticsRevenueTitle">Receitas por lote</span>
              </div>
              <label class="lot-analytics-card__filter">
                <span>Mês</span>
                <select id="lotAnalyticsRevenueMonth"></select>
              </label>
            </div>
            <div class="lot-analytics-card__chart lot-analytics-card__chart--donut">
              <canvas id="lotAnalyticsRevenueChart" aria-label="Gráfico de receitas por lote"></canvas>
            </div>
            <article class="lot-analytics-report-card lot-analytics-report-card--embedded">
              <div class="lot-analytics-report-card__head">
                <h3><i class="fa-solid fa-file-signature" aria-hidden="true"></i><span id="lotAnalyticsRevenueReportTitle">Relatório de receitas</span></h3>
              </div>
              <div class="lot-analytics-report-card__body">
                <div class="lot-analytics-report" id="lotAnalyticsRevenueReport"></div>
              </div>
              <div class="lot-analytics-report-card__foot">
                <button class="fin-btn fin-btn--ghost" type="button" id="lotAnalyticsRevenuePrint">
                  <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir relatório</span>
                </button>
              </div>
            </article>
          </article>

          <article class="lot-analytics-card lot-analytics-card--stacked">
            <div class="lot-analytics-card__head">
              <div class="lot-analytics-card__title">
                <i class="fa-solid fa-building" aria-hidden="true"></i>
                <span>Compras por fornecedor</span>
              </div>
              <label class="lot-analytics-card__filter">
                <span>Mês</span>
                <select id="lotAnalyticsSupplierMonth"></select>
              </label>
            </div>
            <div class="lot-analytics-card__chart lot-analytics-card__chart--donut">
              <canvas id="lotAnalyticsSupplierChart" aria-label="Gráfico de compras por fornecedor"></canvas>
            </div>
            <article class="lot-analytics-report-card lot-analytics-report-card--embedded">
              <div class="lot-analytics-report-card__head">
                <h3><i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Relatório por fornecedor</span></h3>
              </div>
              <div class="lot-analytics-report-card__body">
                <div class="lot-analytics-report" id="lotAnalyticsSupplierReport"></div>
              </div>
              <div class="lot-analytics-report-card__foot">
                <button class="fin-btn fin-btn--ghost" type="button" id="lotAnalyticsSupplierPrint">
                  <i class="fa-solid fa-print" aria-hidden="true"></i><span>Imprimir relatório</span>
                </button>
              </div>
            </article>
          </article>
        </div>
      </div>
    </div>
  </section>

  <script type="application/json" id="lotAnalyticsPayload"><?= json_encode($lotAnalyticsPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</div>
