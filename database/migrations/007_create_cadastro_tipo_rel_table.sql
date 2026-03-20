-- Sistema Visa
-- Etapa 3B / Parte 3B.1
-- Migration 007: cadastro_tipo_rel

CREATE TABLE IF NOT EXISTS cadastro_tipo_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cadastro_id INT UNSIGNED NOT NULL,
  tipo_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cadastro_tipo_rel (cadastro_id, tipo_id),
  KEY idx_cadastro_tipo_rel_tipo_id (tipo_id),
  CONSTRAINT fk_cadastro_tipo_rel_cadastro
    FOREIGN KEY (cadastro_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_cadastro_tipo_rel_tipo
    FOREIGN KEY (tipo_id) REFERENCES cadastro_tipos(id)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
