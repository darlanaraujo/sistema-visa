-- Sistema Visa
-- Etapa 3B / Parte 3B.11
-- Migration 016: infraestrutura transversal de arquivos

CREATE TABLE IF NOT EXISTS arquivos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  nome_original VARCHAR(255) NOT NULL,
  nome_armazenado VARCHAR(255) NOT NULL,
  caminho VARCHAR(500) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  extensao VARCHAR(20) DEFAULT NULL,
  tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  hash_arquivo CHAR(40) DEFAULT NULL,
  status ENUM('ativo', 'removido') NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_arquivos_company_status (company_id, status),
  KEY idx_arquivos_company_mime (company_id, mime_type),
  KEY idx_arquivos_company_hash (company_id, hash_arquivo),
  CONSTRAINT fk_arquivos_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
