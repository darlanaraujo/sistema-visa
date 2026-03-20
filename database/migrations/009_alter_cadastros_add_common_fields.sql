-- Sistema Visa
-- Etapa 3B / Parte 3B.9
-- Migration 009: expandir campos comuns em cadastros

ALTER TABLE cadastros
  ADD COLUMN IF NOT EXISTS razao_social VARCHAR(190) DEFAULT NULL AFTER nome,
  ADD COLUMN IF NOT EXISTS nome_fantasia VARCHAR(190) DEFAULT NULL AFTER razao_social,
  ADD COLUMN IF NOT EXISTS inscricao_estadual VARCHAR(30) DEFAULT NULL AFTER documento,
  ADD COLUMN IF NOT EXISTS contato VARCHAR(190) DEFAULT NULL AFTER status,
  ADD COLUMN IF NOT EXISTS telefone_fixo VARCHAR(20) DEFAULT NULL AFTER contato,
  ADD COLUMN IF NOT EXISTS whatsapp VARCHAR(20) DEFAULT NULL AFTER telefone_fixo,
  ADD COLUMN IF NOT EXISTS celular VARCHAR(20) DEFAULT NULL AFTER whatsapp;

ALTER TABLE cadastros
  ADD KEY IF NOT EXISTS idx_cadastros_company_razao_social (company_id, razao_social),
  ADD KEY IF NOT EXISTS idx_cadastros_company_nome_fantasia (company_id, nome_fantasia);

UPDATE cadastros
   SET razao_social = nome
 WHERE tipo_pessoa = 'PJ'
   AND (razao_social IS NULL OR TRIM(razao_social) = '')
   AND nome IS NOT NULL
   AND TRIM(nome) <> '';

UPDATE cadastros
   SET celular = telefone
 WHERE (celular IS NULL OR TRIM(celular) = '')
   AND telefone IS NOT NULL
   AND TRIM(telefone) <> '';

