-- Schema do backend do Dashboard de Banca
-- Rode este arquivo no seu banco MySQL (phpMyAdmin, Adminer, ou linha de comando)

CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL UNIQUE,
  email VARCHAR(160) NULL,
  senha_hash VARCHAR(255) NOT NULL,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS banca_config (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  mes_ano CHAR(7) NOT NULL,
  banca_inicial DECIMAL(12,2) NOT NULL DEFAULT 50,
  meta_pct DECIMAL(6,2) NOT NULL DEFAULT 20,
  stoploss_pct DECIMAL(6,2) NOT NULL DEFAULT 20,
  UNIQUE KEY uniq_user_mes (usuario_id, mes_ano),
  CONSTRAINT fk_config_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS banca_dias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NOT NULL,
  mes_ano CHAR(7) NOT NULL,
  dia DATE NOT NULL,
  banca_fim DECIMAL(12,2) NULL,
  atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_dia (usuario_id, dia),
  CONSTRAINT fk_dias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log de cliques no link de afiliado (opcional, útil pra saber quantos cliques o botão gerou)
CREATE TABLE IF NOT EXISTS afiliado_cliques (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ip VARCHAR(64) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
