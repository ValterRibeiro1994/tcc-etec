<?php

abstract class UsuarioInterface {
    // getters
    abstract function getId(PDO $conexao = null);
    abstract function getPerfil(): string|null;
    abstract function getSenha(): string|null;

    abstract function getDadosPessoais(): DadosPessoais;
    abstract function getNome(): string|null;
    abstract function getSobrenome(): string|null;
    abstract function getEmail(): string|null;
    abstract function getCpf(): string|null;

    abstract function getEndereco(): Endereco;
    abstract function getEstado(): string|null;
    abstract function getCidade(): string|null;

    abstract function getPrefeitura(): Prefeitura;
    abstract function getCargo(): string|null;
    abstract function getOrgao(): string|null;
    
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