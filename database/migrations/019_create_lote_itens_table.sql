-- Sistema Visa
-- Etapa 4 / Parte 4.2
-- Migration 019: itens internos do lote

CREATE TABLE IF NOT EXISTS lote_itens (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  lote_id INT UNSIGNED NOT NULL,
  descricao_item VARCHAR(190) NOT NULL,
  tipo_controle_item ENUM('unidade', 'kg') NOT NULL DEFAULT 'unidade',
  quantidade_total DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  quantidade_disponivel DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  quantidade_baixada DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  quantidade_vendida DECIMAL(14,3) NOT NULL DEFAULT 0.000,
  custo_unitario_referencia DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  custo_total_referencia DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_venda_unitario_sugerido DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_venda_total_sugerido DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  observacoes_item LONGTEXT DEFAULT NULL,
  status_item VARCHAR(30) NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lote_itens_company_lote (company_id, lote_id),
  KEY idx_lote_itens_company_status (company_id, status_item),
  KEY idx_lote_itens_lote_disponibilidade (lote_id, quantidade_disponivel),
  CONSTRAINT fk_lote_itens_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lote_itens_lote
    FOREIGN KEY (lote_id) REFERENCES lotes(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
