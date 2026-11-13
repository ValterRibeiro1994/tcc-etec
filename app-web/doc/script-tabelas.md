CREATE DATABASE IF NOT EXISTS relate_sbc
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE relate_sbc;

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
    imagem_denuncia BLOB,
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

    FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario(id_usuario),

    FOREIGN KEY (nome_categoria)
        REFERENCES tb_categoria(nome_categoria)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS tb_resposta (
    id_resposta INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_usuario INT NOT NULL,
    id_denuncia INT NOT NULL,
    descricao_resposta TEXT NOT NULL,
    criada_em DATETIME NOT NULL,

    FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario(id_usuario),

    FOREIGN KEY (id_denuncia)
        REFERENCES tb_denuncia(id_denuncia)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tb_usuario
    ADD COLUMN area_atuacao_usuario VARCHAR(30) DEFAULT NULL AFTER cargo_usuario,
    ADD CONSTRAINT fk_usuario_area_atuacao
        FOREIGN KEY (area_atuacao_usuario) REFERENCES tb_categoria(nome_categoria);


CREATE TABLE IF NOT EXISTS tb_conversa (
    id_conversa INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    id_denuncia INT NOT NULL UNIQUE,
    id_representante INT NOT NULL,
    id_denunciante INT NOT NULL,
    status_conversa ENUM('aberta', 'encerrada') NOT NULL DEFAULT 'aberta',
    criada_em DATETIME NOT NULL,
    encerrada_em DATETIME DEFAULT NULL,

    FOREIGN KEY (id_denuncia)
        REFERENCES tb_denuncia(id_denuncia),

    FOREIGN KEY (id_representante)
        REFERENCES tb_usuario(id_usuario),

    FOREIGN KEY (id_denunciante)
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

    FOREIGN KEY (id_conversa)
        REFERENCES tb_conversa(id_conversa),

    FOREIGN KEY (id_autor)
        REFERENCES tb_usuario(id_usuario)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelas acrescentadas para notificações e painel do representante
-- EXECUTE UMA VEZ na aba SQL do phpMyAdmin, com o banco relate_sbc selecionado.
-- Não apaga usuários, denúncias, comentários ou mensagens existentes.


-- Aprovação administrativa MANUAL: criar conta "representante" no cadastro não concede permissão de gestão.
-- O cadastro só vira autorizado quando seu id existir nesta tabela.
CREATE TABLE IF NOT EXISTS tb_representante_aprovado (
    id_usuario INT NOT NULL PRIMARY KEY,
    aprovado_em DATETIME NOT NULL,
    CONSTRAINT fk_representante_aprovado_usuario FOREIGN KEY (id_usuario)
        REFERENCES tb_usuario (id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- EXEMPLO DE APROVAÇÃO PELO RESPONSÁVEL PELO BANCO (preencha o e-mail real e execute separadamente):
-- INSERT IGNORE INTO tb_representante_aprovado (id_usuario, aprovado_em)
-- SELECT id_usuario, NOW() FROM tb_usuario
-- WHERE email_usuario = 'representante-de-teste@example.com' AND perfil_usuario = 'representante';

CREATE TABLE IF NOT EXISTS tb_notificacao (
    id_notificacao INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_denuncia INT DEFAULT NULL,
    tipo_notificacao ENUM('nova_denuncia', 'status', 'comentario', 'mensagem') NOT NULL,
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
