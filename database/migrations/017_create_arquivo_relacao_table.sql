-- Sistema Visa
-- Etapa 3B / Parte 3B.11
-- Migration 017: relação transversal entre arquivo e entidade

CREATE TABLE IF NOT EXISTS arquivo_relacao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL,
  entidade VARCHAR(60) NOT NULL,
  entidade_id INT UNSIGNED NOT NULL,
  arquivo_id INT UNSIGNED NOT NULL,
  ordem INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_arquivo_relacao_entidade_arquivo (entidade, entidade_id, arquivo_id),
  KEY idx_arquivo_relacao_company_entidade (company_id, entidade, entidade_id),
  KEY idx_arquivo_relacao_arquivo (arquivo_id),
  CONSTRAINT fk_arquivo_relacao_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_arquivo_relacao_arquivo
    FOREIGN KEY (arquivo_id) REFERENCES arquivos(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
