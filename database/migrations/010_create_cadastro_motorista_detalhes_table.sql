-- Sistema Visa
-- Etapa 3B / Parte 3B.9
-- Migration 010: detalhes especificos de motorista

CREATE TABLE IF NOT EXISTS cadastro_motorista_detalhes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cadastro_id INT UNSIGNED NOT NULL,
  cpf VARCHAR(20) DEFAULT NULL,
  cnh VARCHAR(30) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cadastro_motorista_detalhes_cadastro (cadastro_id),
  CONSTRAINT fk_cadastro_motorista_detalhes_cadastro
    FOREIGN KEY (cadastro_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
