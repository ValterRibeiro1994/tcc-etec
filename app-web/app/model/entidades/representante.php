<?php

class Representante extends Usuario {
    private DadosPessoais $dados_usuario;
    private Endereco $endereco;
    private Senha $senha;
    private Prefeitura $prefeitura;
    private int $id;

    public function __construct(DadosPessoais $dados_usuario, Endereco $endereco, Senha $senha, Prefeitura $prefeitura)
    {
        $this->dados_usuario = $dados_usuario;
        $this->endereco = $endereco;
        $this->senha = $senha;
    }

    #[Override]
    public function getId(PDO $conexao): int
    {
        if (!empty($this->id)){
            return $this->id;
        }
        $tabela_usuario = $GLOBALS['usuario'];
        $email = $this->getEmail();
        if (empty($email)){
            throw new InvalidArgumentException("Representante ERRO: Email não informado");
        }

        $comando = "SELECT id_usuario FROM  $tabela_usuario WHERE email_usuario = :email";
        $sql = $conexao->prepare($comando);
        $sql->bindValue(":email", $email);
        $sql->execute();
        $resposta = $sql->fetch(PDO::FETCH_ASSOC);
        if ($sql->rowCount() == 0 || !$resposta){
            throw new  InvalidArgumentException("Representante ERRO: Email não cadastrado no sistema");
        }

        $this->id = (int) $resposta['id_usuario'];
        return $this->id;
    }

    #[Override]
    public function getCpf(): string
    {
       return $this->dados_usuario->getCpf();
    }

    #[Override]
    public function getEmail(): string
    {
        return $this->dados_usuario->getEmail();
    }

    #[Override]
    public function getNome(): string
    {
        return $this->dados_usuario->getNome();
    }

    #[Override]
    public function getSobrenome(): string
    {
        return $this->dados_usuario->getSobrenome();
    }

    #[Override]
    public function getSenha(): string
    {
        return $this->senha->getSenha();
    }

    public function getEstado(): string 
    {
        return $this->endereco->getEstado();
    }

    public function getCidade(): string 
    {
        return $this->endereco->getCidade();
    }

    public function getPrefeitura(): string {
        return $this->prefeitura->getOrgao();
    }

    public function getCargo(): string {
        return $this->prefeitura->getCargo();
    }
} 