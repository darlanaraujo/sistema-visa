-- Sistema Visa
-- Etapa 3B / Parte 3B.1
-- Migration 008: seed inicial de cadastro_tipos

INSERT INTO cadastro_tipos (id, slug, nome, status)
VALUES
  (1, 'cliente', 'Cliente', 'ativo'),
  (2, 'fornecedor', 'Fornecedor', 'ativo'),
  (3, 'motorista', 'Motorista', 'ativo'),
  (4, 'transportadora', 'Transportadora', 'ativo')
ON DUPLICATE KEY UPDATE
  nome = VALUES(nome),
  status = VALUES(status);
