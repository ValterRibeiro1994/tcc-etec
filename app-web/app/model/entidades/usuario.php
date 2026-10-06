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
        $perfil_novo = strtolower($perfil_novo);
        $perfis = ['municipe', 'representante', 'visitante'];
        $encontrado = false;

        $n = count($perfis);
        for($x = 0; $x < $n; $x++){
            if ($perfil_novo == $perfis[$x]){
                $encontrado = true;
                break;
            }
        }

        if (!$encontrado) throw new Exception("Perfil de usuário inválido para o sistema");
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
    public function setNome(Nome|string $nome): void {
        if (is_string($nome)){
            $nome = new Nome($nome);
        }
        if ($this->dados_usuario === null) {
            $this->dados_usuario = new DadosPessoais(nome: $nome);
            return;
        };
        $this->dados_usuario->setNome($nome);
    }

    #[Override]
    public function setSobrenome(Sobrenome|string $sobrenome): void {
        if (is_string($sobrenome)){
            $sobrenome = new Sobrenome($sobrenome);
        }

        if ($this->dados_usuario === null) {
            $this->dados_usuario = new DadosPessoais(sobrenome: $sobrenome);
            return;
        }

        $this->dados_usuario->setSobrenome($sobrenome);
    }

    #[Override]
    public function setEmail(Email|string $email): void {
        if (is_string($email)){
            $email = new Email($email);
        }

        if ($this->dados_usuario === null) {
            $this->dados_usuario = new DadosPessoais(email: $email);
            return;
        }

        $this->dados_usuario->setEmail($email);
    }

    #[Override]
    public function setCpf(Cpf|string $cpf): void {
        if (is_string($cpf)){
            $cpf = new Cpf($cpf);
        }

        if ($this->dados_usuario === null){
            $this->dados_usuario = new DadosPessoais(cpf: $cpf);
            return;
        }

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
        
        if ($this->endereco === null) {
            $this->endereco = new Endereco(cidade: $nome_cidade);
            return;
        };
        $this->endereco->setCidade($nome_cidade);
    }

    #[Override]
    public function setEstado(string $estado): void {
        if ($this->endereco === null) {
            $this->endereco = new Endereco(estado: $estado);
            return;
        };
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
        if ($this->perfil != "representante") throw new Exception("ERRO: Apenas representantes podem acessar a essa função.");
        
        if ($this->prefeitura === null){
            $this->prefeitura = new Prefeitura(cargo: $novo_cargo);
            return;
        }
        $this->prefeitura->setCargo($novo_cargo);
    }

    // setter para o orgão do representante
    #[Override]
    public function setOrgao(string $novo_orgao): void {
        if ($this->perfil != "representante") throw new Exception("ERRO: Apenas representantes podem acessar a essa função.");
        if ($this->prefeitura === null){
            $this->prefeitura = new Prefeitura(orgao: $novo_orgao);
            return;
        };

        $this->prefeitura->setOrgao($novo_orgao);
    }

    private function executarFuncoes(array $funcoes, array $parametros, Usuario $usuario){
        try {
            // a chave na função deve ser a mesma em parametros
            $chaves = array_keys($funcoes);
            $n = count($chaves);
            for ($x = 0; $x < $n; $x++){
                try {
                    $chave = $chaves[$x];
                    if (!array_key_exists($chave, $parametros)) return RespostaProcesso::resposta("Chave $chave não enviada como parametros", false, $parametros);
                    $funcao = $funcoes[$chave];
                    //code...
                    $resposta = $usuario->$funcao($parametros[$chave]);
                    if (!$resposta['resposta']) return $resposta;
                    continue;

                } catch (Exception $erro) {
                    return RespostaProcesso::resposta("Erro:a" . $erro->getMessage(), false, RespostaProcesso::salvarErro($erro));
                }
            }
    
            return RespostaProcesso::resposta("Todas as funções foram executadas para esse usuario", true);

        } catch (Exception $erro){
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Falha na execução das funções", false, $dados);
        }

    }

    public function converterArrayParaUsuario(array $dados) {
        try {
            // checa se o perfil de usuario foi enviado
            if (!array_key_exists("perfil", $dados)) return RespostaProcesso::resposta("Perfil de usuario não enviado", false, $dados);
            $usuario = new Usuario();
            $usuario->setDadosPessoais(new DadosPessoais());

            // vetor de funções para criação do usuario
            $funcoes = [
                "nome"=> 'setNome',
                'sobrenome'=> "setSobrenome",
                'email' => "setEmail",
                "perfil"=>'setPerfil',
            ];
            
            if ($dados['perfil'] == "visitante"){
            } else if ($dados['perfil'] == "municipe"){
                $usuario->setEndereco(new Endereco());
                $funcoes['cidade'] = "setCidade";
                $funcoes['estado'] = "setEstado";
            } else if ($dados['perfil'] == "representante"){
                $usuario->setEndereco(new Endereco());
                $funcoes['cidade'] = "setCidade";
                $funcoes['estado'] = "setEstado";

                $usuario->setPrefeitura(new Prefeitura());
                $funcoes['orgao'] = 'setOrgao';
                $funcoes['cidade'] = "setCidade";
            } else {
                return RespostaProcesso::resposta("Perfil desconhecido para o sistema", dados:$dados);
            }

            $resposta = $this->executarFuncoes($funcoes, $dados, $usuario);
            if (is_array($resposta)){
                return $resposta;
            }

            return $usuario;
        } catch (Exception $erro){
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Não foi possivel converter o array em um usuario", false, $dados);
        }
    }


    public function getArray() {
        try {
            $dados = [
                "perfil" => $this->getPerfil(),
                "nome" => $this->getNome(),
                "sobrenome" => $this->getSobrenome(),
                "email" => $this->getEmail()
            ];
    
            if ($this->perfil == "visitante"){
                return RespostaProcesso::resposta("Visitante convertido para array", true, $dados);
            }
    
            $dados['cidade'] = $this->getCidade();
            $dados['estado'] = $this->getEstado();
    
            if ($this->perfil == "municipe") {
                return RespostaProcesso::resposta("Municipe convertido para array", true, $dados);
            }
    
            if ($this->perfil == "representante"){
                $dados['orgao'] = $this->getOrgao();
                $dados['cargo'] = $this->getCargo();
                return RespostaProcesso::resposta("Representante convertido para array", true, $dados);
            }
    
            return RespostaProcesso::resposta("Perfil invalido para usuário", false, $dados);
        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Não foi possivel converter o usuario para array", false, $dados);
        }
    }

    // getter para o ID
    #[Override]
    public function getId(PDO $conexao = null)
    {
        // checa se o ID já foi salvo
        if ($this->id !== null){ // se foi salvo
            return $this->id;
        }

        $email = $this->getEmail();
        if ($email == null) return RespostaProcesso::resposta("ERRO: Email não enviado");
        if (empty($email)) return RespostaProcesso::resposta("ERRO: Email não enviado");

        
        try {
            if ($conexao === null) {
                $conexao = new Conexao();
                $conexao = $conexao->getConexao();
            };

            $comando = "SELECT id_usuario FROM  tb_usuario WHERE email_usuario = :email";
            $sql = $conexao->prepare($comando);
            $sql->bindValue(":email", $email);
            $sql->execute();
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if ($sql->rowCount() == 0 || !$resposta) return RespostaProcesso::resposta("ERRO: Email não cadastrado no sistema");
            $id_banco = (int) $resposta['id_usuario'];
            $this->setId($id_banco);
            return RespostaProcesso::resposta($id_banco, true);
        } catch (Exception $erro){
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Erro back-end: ", false, $dados);
        } finally {
            $conexao = null;
            $sql = null;
        }
    }

    // getters para os dados pessoais
    #[Override]
    public function getDadosPessoais() : DadosPessoais {
        if ($this->dados_usuario === null) {
            $this->dados_usuario = new DadosPessoais();
        };
        
        return $this->dados_usuario;
    }

    #[Override]
    public function getNome(): string {
        if ($this->dados_usuario === null) $this->getDadosPessoais();
        return $this->dados_usuario->getNome();
    }

    #[Override]
    public function getSobrenome(): string|null {
        if ($this->dados_usuario === null) $this->getDadosPessoais();
        return $this->dados_usuario->getSobrenome();
    }

    #[Override]
    public function getEmail(): string
    {
        if ($this->dados_usuario === null) $this->getDadosPessoais();
        return $this->dados_usuario->getEmail();
    }

    #[Override]
    public function getCpf(): string
    {
        if ($this->dados_usuario === null) $this->getDadosPessoais();
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
        if ($this->perfil === null) throw new Exception("Perfil de usuario desconhecido");
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