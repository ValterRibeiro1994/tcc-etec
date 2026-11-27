SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS tb_usuario (
    id_usuario INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    perfil_usuario ENUM('municipe', 'representante', 'administrador') NOT NULL,
    nome_usuario VARCHAR(20) NOT NULL,
    sobrenome_usuario VARCHAR(60) NOT NULL,
    email_usuario VARCHAR(120) NOT NULL UNIQUE,
    cpf_usuario CHAR(11) NOT NULL UNIQUE,
    senha_usuario VARCHAR(60) NOT NULL,
    cidade_usuario VARCHAR(50) NOT NULL,
    estado_usuario CHAR(2) NOT NULL,
    orgao_usuario VARCHAR(120) DEFAULT NULL,
    cargo_usuario VARCHAR(120) DEFAULT NULL,
    area_atuacao_usuario VARCHAR(255) DEFAULT NULL,
    avisos_email TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_categoria (
    id_categoria INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nome_categoria VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_denuncia (
    id_denuncia INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    titulo_denuncia VARCHAR(60) NOT NULL,
    data_denuncia DATETIME NOT NULL,
    descricao_denuncia TEXT NOT NULL,
    imagem_denuncia LONGBLOB,
    nome_categoria VARCHAR(30) NOT NULL,
    cidade_denuncia VARCHAR(50) NOT NULL,
    estado_denuncia CHAR(2) NOT NULL,
    logradouro_denuncia VARCHAR(150) NOT NULL,
    numero_denuncia VARCHAR(30) DEFAULT NULL,
    bairro_denuncia VARCHAR(80) NOT NULL,
    cep_denuncia CHAR(8) NOT NULL,
    status_denuncia ENUM(
        'em andamento',
        'concluida',
        'em analise',
        'pendente'
    ) NOT NULL DEFAULT 'pendente',
    perfil_usuario ENUM(
        'municipe',
        'representante'
    ) NOT NULL,
    complemento_denuncia VARCHAR(100) DEFAULT NULL,
    anonima_denuncia TINYINT(1) NOT NULL DEFAULT 0,

    CONSTRAINT fk_denuncia_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario(id_usuario),
    CONSTRAINT fk_denuncia_categoria FOREIGN KEY (nome_categoria)
        REFERENCES tb_categoria(nome_categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_resposta (
    id_resposta INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_denuncia INT NOT NULL,
    descricao_resposta TEXT NOT NULL,
    criada_em DATETIME NOT NULL,

    CONSTRAINT fk_resposta_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario(id_usuario),
    CONSTRAINT fk_resposta_denuncia FOREIGN KEY (id_denuncia)
        REFERENCES tb_denuncia(id_denuncia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_conversa (
    id_conversa INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_denuncia INT NOT NULL UNIQUE,
    id_representante INT NOT NULL,
    id_denunciante INT NOT NULL,
    status_conversa ENUM('aberta', 'encerrada') NOT NULL DEFAULT 'aberta',
    criada_em DATETIME NOT NULL,
    encerrada_em DATETIME DEFAULT NULL,

    CONSTRAINT fk_conversa_denuncia FOREIGN KEY (id_denuncia)
        REFERENCES tb_denuncia(id_denuncia),
    CONSTRAINT fk_conversa_representante FOREIGN KEY (id_representante)
        REFERENCES tb_usuario(id_usuario),
    CONSTRAINT fk_conversa_denunciante FOREIGN KEY (id_denunciante)
        REFERENCES tb_usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_mensagem (
    id_mensagem INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_conversa INT NOT NULL,
    id_autor INT NOT NULL,
    texto_mensagem VARCHAR(1000) NOT NULL,
    enviada_em DATETIME NOT NULL,
    lida_em DATETIME DEFAULT NULL,

    INDEX idx_mensagem_conversa (id_conversa, id_mensagem),

    CONSTRAINT fk_mensagem_conversa FOREIGN KEY (id_conversa)
        REFERENCES tb_conversa(id_conversa),
    CONSTRAINT fk_mensagem_autor FOREIGN KEY (id_autor)
        REFERENCES tb_usuario(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_representante_aprovado (
    id_usuario INT NOT NULL PRIMARY KEY,
    aprovado_em DATETIME NOT NULL,
    CONSTRAINT fk_representante_aprovado_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tb_admin_auditoria (
    id_auditoria INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_administrador INT DEFAULT NULL,
    id_representante INT DEFAULT NULL,
    acao VARCHAR(30) NOT NULL,
    efetuado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_auditoria_data (id_auditoria),
    CONSTRAINT fk_admin_auditoria_administrador FOREIGN KEY (id_administrador)
        REFERENCES tb_usuario (id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_admin_auditoria_representante FOREIGN KEY (id_representante)
        REFERENCES tb_usuario (id_usuario) ON DELETE SET NULL
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
    CONSTRAINT fk_notificacao_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_notificacao_denuncia FOREIGN KEY (id_denuncia)
        REFERENCES tb_denuncia (id_denuncia) ON DELETE SET NULL
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
    CONSTRAINT fk_historico_denuncia FOREIGN KEY (id_denuncia)
        REFERENCES tb_denuncia (id_denuncia) ON DELETE CASCADE,
    CONSTRAINT fk_historico_representante FOREIGN KEY (id_representante)
        REFERENCES tb_usuario (id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Categorias de Zeladoria Urbana
INSERT IGNORE INTO `tb_categoria` (`nome_categoria`) VALUES
('Iluminação Pública'),
('Buracos e Pavimentação'),
('Coleta de Lixo e Entulho'),
('Poda de Árvores e Mato'),
('Limpeza de Bueiros e Bocas'),
('Sinalização e Trânsito'),
('Vazamento de Água e Esgoto'),
('Calçadas e Acessibilidade'),
('Manutenção de Praças'),
('Infraestrutura'),
('Saneamento'),
('Outros Serviços Urbanos');
