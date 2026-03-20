-- Sistema Visa
-- Etapa 3B / Parte 3B.1
-- Migration 005: cadastros

CREATE TABLE IF NOT EXISTS cadastros (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  tipo_pessoa ENUM('PF', 'PJ') NOT NULL,
  nome VARCHAR(190) NOT NULL,
  documento VARCHAR(20) NOT NULL,
  telefone VARCHAR(20) DEFAULT NULL,
  telefone_secundario VARCHAR(20) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  cep VARCHAR(10) DEFAULT NULL,
  endereco VARCHAR(190) DEFAULT NULL,
  numero VARCHAR(20) DEFAULT NULL,
  complemento VARCHAR(100) DEFAULT NULL,
  bairro VARCHAR(100) DEFAULT NULL,
  cidade VARCHAR(100) DEFAULT NULL,
  estado CHAR(2) DEFAULT NULL,
  observacoes TEXT DEFAULT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cadastros_company_documento (company_id, documento),
  KEY idx_cadastros_company_nome (company_id, nome),
  KEY idx_cadastros_company_status (company_id, status),
  CONSTRAINT fk_cadastros_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
