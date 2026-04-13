-- Sistema Visa
-- Etapa 4 / Parte 4.4
-- Migration 023: adiciona metros aos tipos de controle do item do lote

ALTER TABLE lote_itens
  MODIFY COLUMN tipo_controle_item ENUM('unidade', 'kg', 'metros') NOT NULL DEFAULT 'unidade';
