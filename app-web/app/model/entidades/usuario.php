<?php

class Usuario extends UsuarioInterface {
    private DadosPessoais|null $dados_usuario;
    private Endereco|null $endereco;
    private Senha|null $senha;
    private Prefeitura|null $prefeitura;
    private int|null $id;
    private string|null $perfil;

    public function __construct(DadosPessoais $dados_usuario = null, Endereco $endereco = null, Senha $senha = null, Prefeitura $prefeitura = null)
    {
        $this->dados_usuario = $dados_usuario;
        $this->endereco = $endereco;
        $this->senha = $senha;
        $this->prefeitura = $prefeitura;
        $this->id = null;
        $this->perfil = null;
    }
    
    // set Id
    #[Override]
    public function setId(int $id): void {
        if (is_int($id)){
            $this->id = (int) $id;
            return;
        }

        throw new Exception("ERRO: ID recebido é inválido");
    }

    // setter senha
    #[Override]
    public function setSenha(Senha $senha = null, bool $limpar = false): void{
        if ($limpar){
            $this->senha = null;
            return;
        }
        $this->senha = $senha;
    }

    // setter para o perfil do usuario
    #[Override]
    public function setPerfil(string $perfil_novo): void
    {
        if (!($perfil_novo === "municipe" || $perfil_novo === "representante")) throw new Exception("ERRO: Perfil inválido");
        $this->perfil = $perfil_novo;
    }


    // setters dados pessoais 
    #[Override]
    public function setDadosPessoais(DadosPessoais $dados_pessoais): void
    {
        if ($dados_pessoais === null) throw new Exception("ERRO: Dados pessoais devem ser enviados");
        $this->dados_usuario = $dados_pessoais;
    }

    #[Override]
    public function setNome(Nome $nome): void {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        $this->dados_usuario->setNome($nome);
    }

    #[Override]
    public function setSobrenome(Sobrenome $sobrenome): void {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        $this->dados_usuario->setSobrenome($sobrenome);
    }

    #[Override]
    public function setEmail(Email $email): void {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados Pessoais deve ser criado antes");
        $this->dados_usuario->setEmail($email);
    }

    #[Override]
    public function setCpf(Cpf $cpf): void {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados Pessoais deve ser criado antes");
        $this->dados_usuario->setCpf($cpf);
    }

    // setter endereços
    #[Override]
    public function setEndereco(Endereco $endereco): void
    {
        if ($endereco === null) throw new Exception("ERRO: Endereço não enviado");
        $this->endereco = $endereco;
    }

    #[Override]
    public function setCidade(string $nome_cidade): void {
        if ($this->endereco === null) throw new Exception("ERRO: Endereço deve ser criado antes.");
        $this->endereco->setCidade($nome_cidade);
    }

    #[Override]
    public function setEstado(string $estado): void {
        if ($this->endereco === null) throw new Exception("ERRO: Endereço deve ser criado antes");
        $this->endereco->setEstado($estado);
    }


    #[Override]
    public function setPrefeitura(Prefeitura $prefeitura): void
    {
        if ($this->perfil === "municipe") throw new Exception("ERRO: Apenas Representantes podem ter prefeitura cadastrada");
        $this->prefeitura = $prefeitura;
    }

    // setter para o nome do orgão
    #[Override]
    public function setCargo(string $novo_cargo): void {
        if ($this->perfil != "representante") throw new Exception("ERRO: Apenas representantes podem visualizar essa página.");
        if ($this->prefeitura === null) throw new Exception("ERRO: Prefeitura não enviada");
        $this->prefeitura->setCargo($novo_cargo);
    }

    // setter para o orgão do representante
    #[Override]
    public function setOrgao(string $novo_orgao): void {
        if ($this->perfil != "representante") throw new Exception("ERRO: Apenas representantes podem visualizar essa página.");
        if ($this->prefeitura === null) throw new Exception("ERRO: Prefeitura não enviada");
        $this->prefeitura->setOrgao($novo_orgao);
    }

    // getter para o ID
    #[Override]
    public function getId(PDO $conexao = null): int
    {
        if ($this->id !== null && is_int($this->id)){
            return $this->id;
        }

        if ($conexao === null) throw new Exception("ERRO: Conexão não enviada");

        $tabela_usuario = $GLOBALS['usuario'];
        $email = $this->getEmail();
        if ($email == null) throw new Exception("ERRO: Email não enviado");

        if (empty($email)) throw new InvalidArgumentException("ERRO: Email não enviado");

        $comando = "SELECT id_usuario FROM  $tabela_usuario WHERE email_usuario = :email";
        $sql = $conexao->prepare($comando);
        $sql->bindValue(":email", $email);
        $sql->execute();
        $resposta = $sql->fetch(PDO::FETCH_ASSOC);
        if ($sql->rowCount() == 0 || !$resposta) throw new  InvalidArgumentException("ERRO: Email não cadastrado no sistema");

        $id_banco = (int) $resposta['id_usuario'];
        $this->setId($id_banco);
        
        // encerra o cursor do sql
        $sql = null;

        return $this->id;
    }

    // getters para os dados pessoais
    #[Override]
    public function getDadosPessoais() : DadosPessoais {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        return $this->dados_usuario;
    }

    #[Override]
    public function getNome(): string {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        return $this->dados_usuario->getNome();
    }

    #[Override]
    public function getSobrenome(): string {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        return $this->dados_usuario->getSobrenome();
    }

    #[Override]
    public function getEmail(): string
    {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        return $this->dados_usuario->getEmail();
    }

    #[Override]
    public function getCpf(): string
    {
        if ($this->dados_usuario === null) throw new Exception("ERRO: Dados pessoais deve ser criado antes");
        return $this->dados_usuario->getCpf();
    }

    // getters para endereço
    #[Override]
    public function getEndereco(): Endereco {
        if ($this->endereco === null) throw new Exception("ERRO: Endereço deve ser criado antes");
        return $this->endereco;
    }

    #[Override]
    public function getEstado(): string {
        if ($this->endereco === null) throw new Exception("ERRO: Endereço deve ser criado antes");
        return $this->endereco->getEstado();
    }

    #[Override]
    public function getCidade(): string
    {
        if ($this->endereco === null) throw new Exception("ERRO: Endereço deve ser criado antes");
        return $this->endereco->getCidade();
    }

    // getter para a senha
    #[Override]
    public function getSenha(): string {
        if ($this->senha === null) return "";
        return $this->senha->getSenha();
    }

    
    public function getSenhaHash(): string {
        if ($this->senha === null) return "";
        return $this->senha->getSenhaHash();
    }

    // getter para o perfil
    #[Override]
    public function getPerfil(): string
    {
        if ($this->perfil === null) throw new Exception("ERRO: Perfil deve ser definido antes");
        return $this->perfil;
    }

    // getter para o representante
    #[Override]
    public function getPrefeitura(): Prefeitura {
        if  ($this->prefeitura === null) throw new Exception("ERRO: Prefeitura deve ser enviada antes");
        if ($this->perfil === null) throw new Exception("ERRO: Perfil deve ser enviado antes");
        if ($this->perfil !== "representante") throw new Exception("ERRO: Apenas Representantes podem acessar essa página");
        return $this->prefeitura;
    }

    #[Override]
    public function getOrgao(): string
    {
        if  ($this->prefeitura === null) throw new Exception("ERRO: Prefeitura deve ser enviada antes");
        return $this->prefeitura->getOrgao();
    }

    #[Override]
    public function getCargo(): string
    {
        if  ($this->prefeitura === null) throw new Exception("ERRO: Prefeitura deve ser enviada antes");
        return $this->prefeitura->getCargo();
    }

}