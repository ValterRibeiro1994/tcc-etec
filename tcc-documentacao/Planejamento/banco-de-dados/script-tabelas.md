
create database if not exists relate_sbc;
use relate_sbc;

create table if not exists tb_usuario (
	id_usuario int not null primary key auto_increment,
    perfil_usuario enum('municipe', 'representante', 'visitante') not null,
    nome_usuario varchar(20) not null,
    sobrenome_usuario varchar(60) not null,
    email_usuario varchar(120) not null unique,
    cpf_usuario char(11) not null unique,
    senha_usuario varchar(60) not null,
    cidade_usuario varchar(50) not null,
    estado_usuario char(2) not null,
    orgao_usuario varchar(120) not null,
    cargo_usuario varchar(120) not null
);

create table if not exists tb_categoria (
	id_categoria int not null primary key auto_increment,
    nome_categoria varchar(30) unique
);

create table if not exists tb_denuncia (
	id_denuncia int not null primary key auto_increment,
    id_usuario int not null,
    titulo_denuncia varchar(60) not null,
    data_denuncia datetime not null,
    descricao_denuncia text not null,
    imagem_denuncia blob,
    nome_categoria varchar(30) not null,
    cidade_denuncia varchar(50) not null,
    estado_denuncia char(2) not null,
    logradouro_denuncia varchar(150) not null,
    numero_denuncia varchar(30),
    bairro_denuncia varchar(80) not null,
    cep_denuncia char(8) not null,
    status_denuncia enum('em andamento','concluida','em analise', 'pendente') default 'pendente' not null,
    perfil_usuario enum('municipe', 'representante') not null,
    complemento_denuncia varchar(100),
    
    
    
    foreign key (id_usuario) references tb_usuario(id_usuario),
    foreign key (nome_categoria) references tb_categoria (nome_categoria)
);

create table if not exists tb_resposta (
	id_resposta int primary key auto_increment,
    id_usuario int not null,
    id_denuncia int not null,
    descricao_resposta text not null,
    criada_em datetime not null,
    
    foreign key (id_usuario) references tb_usuario (id_usuario),
    foreign key (id_denuncia) references tb_denuncia (id_denuncia)
    
);

INSERT IGNORE INTO tb_categoria (nome_categoria)
VALUES ('Infraestrutura'), ('Limpeza Urbana'), ('Iluminação Pública'), ('Saneamento');