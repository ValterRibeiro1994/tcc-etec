CREATE DATABASE IF NOT EXISTS relate_sbc
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE relate_sbc;

CREATE TABLE IF NOT EXISTS tb_usuario (
    id_usuario INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    perfil_usuario ENUM('municipe', 'representante') NOT NULL,
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