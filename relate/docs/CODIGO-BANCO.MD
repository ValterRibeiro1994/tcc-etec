# BANCO
CREATE DATABASE IF NOT EXISTS relate_sbc
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE relate_sbc;

# TABELA USUARIO
CREATE TABLE tb_usuario (

    <!-- DADOS DA INDENTIDADE DO USUARIO -->
    cpf_usuario CHAR(11) NOT NULL PRIMARY KEY,
    nome_usuario VARCHAR(30) NOT NULL,
    sobrenome_usuario VARCHAR(50) NOT NULL,
    perfil_usuario ENUM('MUNICIPE', 'REPRESENTANTE') NOT NULL,

    <!-- DADOS DE CONTATO DO USUARIO -->
    email_usuario VARCHAR(120) NOT NULL UNIQUE,

    <!-- DADOS DA LOCALIZAÇÃO DO USUARIO -->
    cidade_usuario VARCHAR(50) NOT NULL,
    estado_usuario CHAR(2) NOT NULL,

    <!-- DADOS DE SEGURANÇA DO USUARIO -->
    hash_usuario VARCHAR(60) NOT NULL,
);

# TABELA CATEGORIA
CREATE TABLE tb_categoria (
    id_categoria INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(30) NOT NULL,
);

# TABELA DENUNCIA
CREATE TABLE tb_denuncia (
    <!-- DADOS DO USUARIO DA DENUNCIA -->
    id_denuncia INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(30) NOT NULL,
    email_usuario VARCHAR(120) NOT NULL,

    <!-- DADOS DO LOCAL DA DENUNCIA -->
    cidade_denuncia VARCHAR(50) NOT NULL,
    estado_denuncia CHAR(2) NOT NULL,
    logradouro_denuncia VARCHAR(100) NOT NULL,
    numero_denuncia VARCHAR(5) NULL,
    cep_denuncia CHAR(8) NULL,

    <!-- DADOS DA DENUNCIA EM SI -->
    titulo_denuncia VARCHAR(50) NOT NULL,
    descricao_denuncia TEXT NOT NULL,
    data_hora_denuncia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    imagem_denuncia BLOB NULL,


    <!-- DADOS DO RESULTADO DA DENUNCIA -->
    status_denuncia ENUM('SEM RESPOSTA','ANDAMENTO', 'RESOLVIDA') NOT NULL DEFAULT 'SEM RESPOSTA',

    <!-- CHAVES ESTRANGEIRAS  -->
    FOREIGN KEY (nome_categoria) REFERENCES categoria(nome_categoria),

    FOREIGN KEY (email_usuario) REFERENCES usuario(email_usuario)

    ON DELETE CASCADE
);

# TABELA RESPOSTA
CREATE TABLE tb_resposta (
    <!-- DADOS DA RESPOSTA  -->
    id_resposta INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    descricao_resposta TEXT NOT NULL,
    data_hora_resposta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    <!-- DADOS DA DENUNCIA  -->
    id_denuncia INT NOT NULL,

    <!-- DADOS DO REPRESENTANTE -->
    email_usuario VARCHAR(120) NOT NULL,
    
    <!-- CHAVES ESTRANGEIRAS -->
    FOREIGN KEY (id_denuncia)
    REFERENCES denuncia(id_denuncia),

    FOREIGN KEY (email_usuario)
    REFERENCES usuario(email_usuario)
);


# CÓDIGO PURO QUE USEI NO PHPADMIN
CREATE DATABASE IF NOT EXISTS relate_sbc
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE relate_sbc;

CREATE TABLE tb_usuario (
    cpf_usuario CHAR(11) NOT NULL PRIMARY KEY,
    nome_usuario VARCHAR(30) NOT NULL,
    sobrenome_usuario VARCHAR(50) NOT NULL,
    perfil_usuario ENUM('MUNICIPE', 'REPRESENTANTE') NOT NULL,
    email_usuario VARCHAR(120) NOT NULL UNIQUE,
    cidade_usuario VARCHAR(50) NOT NULL,
    estado_usuario CHAR(2) NOT NULL,
    hash_usuario VARCHAR(60) NOT NULL
);

CREATE TABLE tb_categoria (
    id_categoria INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE tb_denuncia (
    id_denuncia INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(30) NOT NULL,
    email_usuario VARCHAR(120) NOT NULL,
    cidade_denuncia VARCHAR(50) NOT NULL,
    estado_denuncia CHAR(2) NOT NULL,
    logradouro_denuncia VARCHAR(100) NOT NULL,
    numero_denuncia VARCHAR(5) NULL,
    cep_denuncia CHAR(8) NULL,
    titulo_denuncia VARCHAR(50) NOT NULL,
    descricao_denuncia TEXT NOT NULL,
    data_hora_denuncia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    imagem_denuncia BLOB NULL,
    status_denuncia ENUM('SEM RESPOSTA','ANDAMENTO', 'RESOLVIDA') NOT NULL DEFAULT 'SEM RESPOSTA',
    FOREIGN KEY (nome_categoria) REFERENCES tb_categoria(nome_categoria),
    FOREIGN KEY (email_usuario) REFERENCES tb_usuario(email_usuario) ON DELETE CASCADE
);

CREATE TABLE tb_resposta (
    id_resposta INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    descricao_resposta TEXT NOT NULL,
    data_hora_resposta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_denuncia INT NOT NULL,
    email_usuario VARCHAR(120) NOT NULL,
    FOREIGN KEY (id_denuncia) REFERENCES tb_denuncia(id_denuncia),
    FOREIGN KEY (email_usuario) REFERENCES tb_usuario(email_usuario)
);