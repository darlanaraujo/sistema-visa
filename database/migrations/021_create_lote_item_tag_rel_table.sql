-- Sistema Visa
-- Etapa 4 / Parte 4.2
-- Migration 021: relacao entre itens do lote e tags globais

CREATE TABLE IF NOT EXISTS lote_item_tag_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lote_item_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_lote_item_tag_rel_item_tag (lote_item_id, tag_id),
  KEY idx_lote_item_tag_rel_tag (tag_id),
  CONSTRAINT fk_lote_item_tag_rel_item
    FOREIGN KEY (lote_item_id) REFERENCES lote_itens(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_lote_item_tag_rel_tag
    FOREIGN KEY (tag_id) REFERENCES cadastro_tags(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
