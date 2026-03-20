-- Sistema Visa
-- Etapa 3B / Parte 3B.9
-- Migration 011: motoristas vinculados

CREATE TABLE IF NOT EXISTS cadastro_motoristas_vinculados (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cadastro_id INT UNSIGNED NOT NULL,
  nome VARCHAR(190) NOT NULL,
  cpf VARCHAR(20) DEFAULT NULL,
  cnh VARCHAR(30) DEFAULT NULL,
  contato VARCHAR(190) DEFAULT NULL,
  telefone_fixo VARCHAR(20) DEFAULT NULL,
  whatsapp VARCHAR(20) DEFAULT NULL,
  celular VARCHAR(20) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  principal TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cadastro_motoristas_vinculados_cadastro (cadastro_id),
  CONSTRAINT fk_cadastro_motoristas_vinculados_cadastro
    FOREIGN KEY (cadastro_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

