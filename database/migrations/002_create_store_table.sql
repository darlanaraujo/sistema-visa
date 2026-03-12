-- Sistema Visa
-- Etapa 2 / Parte 2.1
-- Migration 002: store

CREATE TABLE IF NOT EXISTS store (
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  store_key VARCHAR(190) NOT NULL,
  value_json JSON NOT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (company_id, store_key),
  CONSTRAINT fk_store_company
    FOREIGN KEY (company_id) REFERENCES companies(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
