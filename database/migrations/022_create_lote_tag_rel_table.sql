-- Sistema Visa
-- Etapa 4 / Parte 4.4
-- Migration 022: relacao entre lote e tags globais

CREATE TABLE IF NOT EXISTS lote_tag_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lote_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lote_tag_rel_lote_tag (lote_id, tag_id),
  KEY idx_lote_tag_rel_tag (tag_id),
  CONSTRAINT fk_lote_tag_rel_lote
    FOREIGN KEY (lote_id) REFERENCES lotes(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_lote_tag_rel_tag
    FOREIGN KEY (tag_id) REFERENCES cadastro_tags(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
