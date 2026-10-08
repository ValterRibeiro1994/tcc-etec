<?php

class User {
    private DadosPessoais $dados_usuario;
    private Senha $senha;
    private Cpf $cpf;
    private Endereco $endereco;
    private Prefeitura $prefeitura;

    /**
     * OS DADOS PESSOAIS OBRIGATORIOS 
     * SÃO APENAS OS QUE PODEM SER REPASSADO PARA O CLIENTE
     * O PERFIL É OBRIGATORIO SER PASSADO EM TODAS AS ETAPAS
     * ELE VAI SER USADO PARA CONTROLAR AS PERMISSÕES DE CADA USUARIO
     */
    public function __construct(DadosPessoais $dados_usuario)
    {
        $this->dados_usuario = $dados_usuario;
    }

    /**
     * PERFIL CONTROLA NIVEL DE ACESSO E PERMISSÕES
     * ELE É OBRIGATORIO
     */
    public function setPerfil(Perfil|string $perfil){
        $this->dados_usuario->setPerfil($perfil);
    }

    public function getPerfil(){
        return $this->dados_usuario->getPerfil();
    }

    /**
     * NOME, SOBRENOME E EMAIL TERÁ SEUS DADOS RECEBIDO PELO CLIENTE
     * SERÃO ARMAZENADOS NO BANCO E ENQUANTO NÃO FIZEREM A AUTENTICAÇÃO
     * TERÃO SEUS VALORES PADRONIZADOS ASSIM COMO O EMAIL 
     */
    public function setNome(Nome|string $nome){
        $this->dados_usuario->getNome();
    }

    public function getNome(): string {
        return $this->dados_usuario->getNome();
    }


    public function setSobrenome(Sobrenome|string $sobrenome){
        $this->dados_usuario->setSobrenome($sobrenome);
    }

    public function getSobrenome(): string {
        return $this->dados_usuario->getSobrenome();
    }

    public function getEmail(): string {
        return $this->dados_usuario->getEmail();
    }

    public function setEmail(Email|string $email){
        $this->dados_usuario->setEmail($email);
    }

    /**
     * 
     * SENHAS SÃO PASSADAS APENAS POR SETTERS 
     * ELAS DEVEM SER COLETADAS APENAS NAS ETAPAS DE LOGIN E CADASTRO
     * NUNCA DEVEM SER REENVIADAS PARA O CLIENTE
     *      
     **/
    public function setSenha(Senha|string $senha){
        if (is_string($senha)){
            $senha = new Senha($senha);
        }
        $this->senha = $senha;
    }

    public function obterSenha(): string {
        if ($this->senha == null) return "Senha não informada";
        return $this->senha->getSenha();
    }

    public function obterHashSenha(): string {
        if ($this->senha == null) return "Hash da senha não informada";
        return $this->senha->getSenhaHash();
    }

    /**
     * AMBOS O MUNICIPE E O REPRESENTANTE POSSUEM ENDEREÇOS,
     * OUTROS PERFIS NÃO POSSUEM ESSA NECESSIDADE,
     * PARA OS USUARIOS APENAS CIDADE E ESTADO SÃO NECESSARIOS
    */
    public function setEndereco(Endereco $endereco = null, string $cidade = null, string $estado = null){
        $perfis_com_endereco = ['municipe', 'representante'];
        if (!$this->validarPerfil($perfis_com_endereco)) {
            $perfil = $this->getPerfil();
            throw new Exception("$perfil não tem acesso a essa funcionalidade");
        }

        // o endereço foi criado?
        if ($endereco != null){// sim
            // o estado foi enviado?
            if ($cidade != null){
                $endereco->setCidade($cidade);
            }

            // a cidade foi enviada
            if ($estado != null) {
                $endereco->setEstado($estado);
            }

            // salva o endereço
            $this->endereco = $endereco;
        } else {// não
            // ja temos um endereço salvo?
            if (!$this->endereco == null){ // não
                // cria um novo endereço
                $endereco = new Endereco();

                // a cidade foi enviada
                if ($cidade != null){
                    $endereco->setCidade($cidade);
                }

                // o estado foi enviado?
                if ($estado != null){
                    $endereco->setEstado($estado);
                }

                // salva o endereço
                $this->endereco = $endereco;
            } else {// sim
                // a cidade foi enviada
                if ($cidade != null){
                    $this->endereco->setCidade($cidade);
                }

                // o estado foi enviado?
                if ($estado != null){
                    $this->endereco->setEstado($estado);
                }
            }
        }


    }

    public function getEndereco(): Endereco {
        $perfis_com_endereco = ['municipe', 'representante'];
        if (!$this->validarPerfil($perfis_com_endereco)) throw new Exception($this->getPerfil() . " Não tem acesso a essa funcionalidade");
        return $this->endereco;
    }

    public function setCidade(string $cidade){
        if ($this->endereco == null){
            $this->endereco = new Endereco(cidade: $cidade);
            return;
        }

        $this->endereco->setCidade($cidade);
    }

    public function setEstado(string $estado){
        if ($this->endereco == null){
            $this->endereco = new Endereco(estado: $estado);
            return;
        }
        $this->endereco->setEstado($estado);
    }


    /**
     * funções auxiliares
     */

    private function validarPerfil(array $perfis_permitido){
        foreach($perfis_permitido as $perfil){
            if ($perfil == $this->getPerfil()){
                return true;
            }
        }
        return false;


    }

}