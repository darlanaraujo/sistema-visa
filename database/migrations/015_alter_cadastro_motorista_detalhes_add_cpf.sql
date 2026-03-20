-- Sistema Visa
-- Etapa 3B / Parte 3B.10
-- Migration 015: adicionar CPF ao detalhe de motorista

ALTER TABLE cadastro_motorista_detalhes
  ADD COLUMN IF NOT EXISTS cpf VARCHAR(20) DEFAULT NULL AFTER cadastro_id;
