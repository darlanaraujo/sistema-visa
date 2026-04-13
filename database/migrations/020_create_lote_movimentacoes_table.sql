-- Sistema Visa
-- Etapa 4 / Parte 4.2
-- Migration 020: historico oficial de movimentacoes do lote

CREATE TABLE IF NOT EXISTS lote_movimentacoes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  lote_id INT UNSIGNED NOT NULL,
  tipo_evento VARCHAR(60) NOT NULL,
  descricao_evento TEXT NOT NULL,
  payload_estrutural JSON DEFAULT NULL,
  data_evento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responsavel VARCHAR(190) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_lote_movimentacoes_company_lote (company_id, lote_id),
  KEY idx_lote_movimentacoes_lote_data (lote_id, data_evento),
  KEY idx_lote_movimentacoes_company_tipo (company_id, tipo_evento),
  CONSTRAINT fk_lote_movimentacoes_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lote_movimentacoes_lote
    FOREIGN KEY (lote_id) REFERENCES lotes(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
