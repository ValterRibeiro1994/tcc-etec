<?php

abstract class UsuarioInterface {
    // getters
    abstract function getId(PDO $conexao = null);
    abstract function getPerfil(): string;
    abstract function getSenha(): string;

    abstract function getDadosPessoais(): DadosPessoais;
    abstract function getNome(): string;
    abstract function getSobrenome(): string;
    abstract function getEmail(): string;
    abstract function getCpf(): string;

    abstract function getEndereco(): Endereco;
    abstract function getEstado(): string;
    abstract function getCidade(): string;

    abstract function getPrefeitura(): Prefeitura;
    abstract function getCargo(): string;
    abstract function getOrgao(): string;
    
    // setters
    abstract function setId(int $id): void;
    abstract function setPerfil(string $perfil_novo): void;
    abstract function setSenha(Senha $senha, bool $limpar): void;
    
    abstract function setDadosPessoais(DadosPessoais $dados_pessoais): void;
    abstract function setNome(Nome $nome): void;
    abstract function setSobrenome(Sobrenome $sobrenome): void;
    abstract function setEmail(Email $email): void;
    abstract function setCpf(Cpf $cpf): void;

    abstract function setEndereco(Endereco $endereco): void;
    abstract function setCidade(string $cidade): void;
    abstract function setEstado(string $estado): void;
    
    abstract function setPrefeitura(Prefeitura $prefeitura): void;
    abstract function setCargo(string $cargo): void;
    abstract function setOrgao(string $orgao): void;

}