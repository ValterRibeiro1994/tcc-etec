-- Compatibiliza bancos legados com MyISAM nas tabelas centrais.
-- Execute antes de migracao-admin.sql. Não altera nem remove registros existentes.
CREATE TABLE IF NOT EXISTS tb_conversa (
    id_conversa INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_denuncia INT NOT NULL UNIQUE,
    id_representante INT NOT NULL,
    id_denunciante INT NOT NULL,
    status_conversa ENUM('aberta', 'encerrada') NOT NULL DEFAULT 'aberta',
    criada_em DATETIME NOT NULL,
    encerrada_em DATETIME DEFAULT NULL,
    INDEX idx_conversa_representante (id_representante, status_conversa),
    INDEX idx_conversa_denunciante (id_denunciante, status_conversa)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_mensagem (
    id_mensagem INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_conversa INT NOT NULL,
    id_autor INT NOT NULL,
    texto_mensagem VARCHAR(1000) NOT NULL,
    enviada_em DATETIME NOT NULL,
    lida_em DATETIME DEFAULT NULL,
    INDEX idx_mensagem_conversa (id_conversa, id_mensagem),
    INDEX idx_mensagem_autor (id_autor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_notificacao (
    id_notificacao INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_denuncia INT DEFAULT NULL,
    tipo_notificacao ENUM('nova_denuncia', 'status', 'comentario', 'mensagem', 'sistema') NOT NULL,
    titulo_notificacao VARCHAR(100) NOT NULL,
    mensagem_notificacao VARCHAR(300) NOT NULL,
    criada_em DATETIME NOT NULL,
    lida_em DATETIME DEFAULT NULL,
    INDEX idx_notificacao_usuario (id_usuario, id_notificacao),
    INDEX idx_notificacao_pendentes (id_usuario, lida_em),
    INDEX idx_notificacao_denuncia (id_denuncia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_historico_status (
    id_historico INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_denuncia INT NOT NULL,
    id_representante INT DEFAULT NULL,
    status_anterior VARCHAR(30) NOT NULL,
    status_novo VARCHAR(30) NOT NULL,
    justificativa VARCHAR(350) NOT NULL,
    alterado_em DATETIME NOT NULL,
    INDEX idx_historico_denuncia (id_denuncia, alterado_em),
    INDEX idx_historico_representante (id_representante)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
