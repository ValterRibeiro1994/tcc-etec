# Tabelas salvas diretamente no infinity
## por isso não a definição para o BD 

create table if not exists tb_usuario (
	id_usuario int not null primary key auto_increment,
    nome_usuario varchar(20) not null,
    sobrenome_usuario varchar(60) not null,
    email_usuario varchar(120) not null unique,
    cpf_usuario char(11) not null unique,
    senha_usuario varchar(60) not null
);


create table if not exists tb_categoria (
	id_categoria int not null primary key auto_increment,
    nome_categoria varchar(30) unique
);


create table if not exists tb_municipe (
	id_usuario int primary key,
    cidade_municipe varchar(50) not null,
    estado_municipe char(2) not null,
    foreign key (id_usuario) references tb_usuario (id_usuario)
);

create table if not exists tb_representante (
	id_usuario int primary key,
    cidade_representante varchar(50) not null,
    estado_representante char(2) not null,
    orgao_representante varchar(120) not null,
    cargo_representante varchar(120) not null,
    foreign key (id_usuario) references tb_usuario (id_usuario)
);

create table if not exists tb_denuncia (
	id_denuncia int not null primary key auto_increment,
    id_usuario int not null,
    nome_categoria varchar(30) not null,
    titulo_denuncia varchar(60) not null,
    descricao_denuncia text not null,
    imagem_denuncia blob not null,
    criada_em datetime not null,
    
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