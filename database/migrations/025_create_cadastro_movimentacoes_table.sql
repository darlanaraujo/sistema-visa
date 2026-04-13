-- Sistema Visa
-- Etapa 7 / Parte 7.2
-- Migration 025: historico oficial de movimentacoes do cadastro

CREATE TABLE IF NOT EXISTS cadastro_movimentacoes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  cadastro_id INT UNSIGNED NOT NULL,
  tipo_evento VARCHAR(60) NOT NULL,
  descricao_evento TEXT NOT NULL,
  payload_estrutural JSON DEFAULT NULL,
  data_evento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  responsavel VARCHAR(190) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cadastro_movimentacoes_company_cadastro (company_id, cadastro_id),
  KEY idx_cadastro_movimentacoes_cadastro_data (cadastro_id, data_evento),
  KEY idx_cadastro_movimentacoes_company_tipo (company_id, tipo_evento),
  CONSTRAINT fk_cadastro_movimentacoes_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_cadastro_movimentacoes_cadastro
    FOREIGN KEY (cadastro_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
