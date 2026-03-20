-- Sistema Visa
-- Etapa 3B / Parte 3B.9
-- Migration 014: relacao cadastro x tags

CREATE TABLE IF NOT EXISTS cadastro_tag_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cadastro_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cadastro_tag_rel_cadastro_tag (cadastro_id, tag_id),
  KEY idx_cadastro_tag_rel_tag (tag_id),
  CONSTRAINT fk_cadastro_tag_rel_cadastro
    FOREIGN KEY (cadastro_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_cadastro_tag_rel_tag
    FOREIGN KEY (tag_id) REFERENCES cadastro_tags(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
