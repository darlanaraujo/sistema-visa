-- Sistema Visa
-- Etapa 3B / Parte 3B.9
-- Migration 013: catalogo de tags de cadastro

CREATE TABLE IF NOT EXISTS cadastro_tags (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  nome VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'ativo',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cadastro_tags_company_slug (company_id, slug),
  KEY idx_cadastro_tags_company_nome (company_id, nome),
  CONSTRAINT fk_cadastro_tags_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

