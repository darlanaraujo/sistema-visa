-- Sistema Visa
-- Etapa 3B / Parte 3B.9
-- Migration 012: veiculos vinculados

CREATE TABLE IF NOT EXISTS cadastro_veiculos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cadastro_id INT UNSIGNED NOT NULL,
  modelo VARCHAR(190) NOT NULL,
  placa VARCHAR(15) NOT NULL,
  placa_adicional VARCHAR(15) DEFAULT NULL,
  tipo_carroceria VARCHAR(100) DEFAULT NULL,
  metragem VARCHAR(50) DEFAULT NULL,
  peso_carga VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_cadastro_veiculos_cadastro (cadastro_id),
  KEY idx_cadastro_veiculos_placa (placa),
  CONSTRAINT fk_cadastro_veiculos_cadastro
    FOREIGN KEY (cadastro_id) REFERENCES cadastros(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

