-- Sistema Visa
-- Etapa 2 / Parte 2.1
-- Migration 004: seed inicial
--
-- Uso local:
-- Usuario admin inicial: admin@sistema.local
-- Senha inicial de desenvolvimento: admin123
-- Hash bcrypt gerado para essa senha: $2y$10$XYDnS1ssUX7MFOAWi.zshObPzzUC9DRIwAbsBLcup1nJAiuGINUvq

INSERT INTO companies (id, name)
VALUES (1, 'Visa Remoções')
ON DUPLICATE KEY UPDATE
  name = VALUES(name);

INSERT INTO users (id, company_id, name, email, password_hash, role)
VALUES (
  1,
  1,
  'Administrador',
  'admin@sistema.local',
  '$2y$10$XYDnS1ssUX7MFOAWi.zshObPzzUC9DRIwAbsBLcup1nJAiuGINUvq',
  'admin'
)
ON DUPLICATE KEY UPDATE
  company_id = VALUES(company_id),
  name = VALUES(name),
  password_hash = VALUES(password_hash),
  role = VALUES(role);
