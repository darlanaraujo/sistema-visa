-- Sistema Visa
-- Etapa 4 / Parte 4.2
-- Migration 018: entidade central de lotes

CREATE TABLE IF NOT EXISTS lotes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  fornecedor_id INT UNSIGNED NOT NULL,
  numero_processo VARCHAR(120) NOT NULL,
  titulo_lote VARCHAR(190) NOT NULL,
  descricao_resumida TEXT DEFAULT NULL,
  descricao_operacional LONGTEXT DEFAULT NULL,
  tipo_macro_lote VARCHAR(120) DEFAULT NULL,
  data_compra DATE DEFAULT NULL,
  status_macro ENUM('em_transito', 'em_estoque', 'finalizado') NOT NULL DEFAULT 'em_transito',
  etapa_timeline ENUM('compra_confirmada', 'liberacao', 'coleta', 'transporte', 'recebido', 'venda', 'encerrado') NOT NULL DEFAULT 'compra_confirmada',
  observacoes_gerais LONGTEXT DEFAULT NULL,
  valor_original_lote DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_depreciado DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_pago_compra DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  despesas_local DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_frete DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  valor_documento_transporte DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  outros_custos DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  custo_total DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  nome_local VARCHAR(190) DEFAULT NULL,
  nome_contato VARCHAR(190) DEFAULT NULL,
  telefone VARCHAR(20) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  endereco VARCHAR(190) DEFAULT NULL,
  cidade VARCHAR(100) DEFAULT NULL,
  estado CHAR(2) DEFAULT NULL,
  observacoes_local LONGTEXT DEFAULT NULL,
  tipo_transporte ENUM('motorista_autonomo', 'transportadora', 'transporte_proprio', 'sem_frete', 'retirada_cliente') DEFAULT NULL,
  motorista_id INT UNSIGNED DEFAULT NULL,
  transportadora_id INT UNSIGNED DEFAULT NULL,
  veiculo_referencia VARCHAR(190) DEFAULT NULL,
  agenciador VARCHAR(190) DEFAULT NULL,
  documento_transporte VARCHAR(190) DEFAULT NULL,
  data_contratacao DATE DEFAULT NULL,
  data_agendamento DATE DEFAULT NULL,
  data_coleta DATE DEFAULT NULL,
  data_entrega DATE DEFAULT NULL,
  observacoes_logisticas LONGTEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lotes_company_numero_processo (company_id, numero_processo),
  KEY idx_lotes_company_status_macro (company_id, status_macro),
  KEY idx_lotes_company_etapa_timeline (company_id, etapa_timeline),
  KEY idx_lotes_company_data_compra (company_id, data_compra),
  KEY idx_lotes_company_fornecedor (company_id, fornecedor_id),
  KEY idx_lotes_company_motorista (company_id, motorista_id),
  KEY idx_lotes_company_transportadora (company_id, transportadora_id),
  CONSTRAINT fk_lotes_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lotes_fornecedor
    FOREIGN KEY (fornecedor_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT,
  CONSTRAINT fk_lotes_motorista
    FOREIGN KEY (motorista_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL,
  CONSTRAINT fk_lotes_transportadora
    FOREIGN KEY (transportadora_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
