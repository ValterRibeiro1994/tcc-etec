<?php


class UsuarioRepositorio {


    public function cadastrarUsuario(Usuario $usuario) {
        try {
           if ($this->emailExiste($usuario->getEmail())) throw new Exception("Email cadastrado");

            // armazena o perfil do usuario
            $perfil = $usuario->getPerfil();
            if (!($perfil == "representante" || $perfil == "municipe")) throw new Exception("Perfil inválido");

            // inicia a conexão com o banco
            $conexao_obj = new Conexao();

            // captura a conexão -> PDO
            $conexao = $conexao_obj->getConexao();

            // salva os atributos em comum no banco
            $this->salvarUsuario($usuario, $conexao);

            // Encaminha o usuario para sua categoria (Representante, municipe)
            if ($perfil === "representante"){ // se for representante

                // salve os dados que falta para completar o perfil
                $this->salvarRepresentante($usuario, $conexao); // salvando representante            

            } else if ($perfil === "municipe"){ // se for municipe

                // salve os dados que falta para completar o perfil
                $this->salvarMunicipe($usuario, $conexao); // salvando municipe
            
            } else {

                // lança um erro acaso o perfil não tenha sido definido antes
                throw new Exception("Perfil inválido");
            }
            
            return RespostaProcesso::respostaProcesso("Cadastro realizado com sucesso", true);
        } catch(Exception $erro){
            throw $erro;
        } finally {
            $conexao = null;
        }
    }

    private function salvarUsuario(Usuario $usuario, PDO $conexao): void {
        $comando = "
            INSERT INTO tb_usuario(nome_usuario, sobrenome_usuario, email_usuario, cpf_usuario, perfil_usuario, senha_usuario) 
            VALUES (:nome, :sobrenome, :email, :cpf, :perfil, :senha)
        ";

        $sql = $conexao->prepare($comando);
        $sql->bindValue(":nome", $usuario->getNome());
        $sql->bindValue(":sobrenome", $usuario->getSobrenome());
        $sql->bindValue(":email", $usuario->getEmail());
        $sql->bindValue(":cpf", $usuario->getCpf());
        $sql->bindValue(":perfil", $usuario->getPerfil());
        $sql->bindValue(":senha", $usuario->getSenhaHash());
        $sql->execute();
        $sql = null;
    }

    private function salvarMunicipe(Usuario $usuario, PDO $conexao): void{
            $comando = "
                INSERT INTO 
                    tb_municipe(id_usuario, cidade_municipe, estado_municipe)
                VALUES 
                    (:id, :cidade, :estado)
                ";

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":id", $usuario->getId($conexao));
            $sql->bindValue(":estado", $usuario->getEstado());
            $sql->bindValue(":cidade",  $usuario->getCidade());
            $sql->execute();
            $sql = null;
    }

    private function salvarRepresentante(Usuario $representante, PDO $conexao): void{
        
        $comando = "
        INSERT INTO 
            tb_representante(id_usuario, cidade_representante, estado_representante, orgao_representante, cargo_representante)
        VALUES 
            (:id, :cidade, :estado, :orgao, :cargo)
        ";

        $sql = $conexao->prepare($comando);
        $sql->bindValue(":id", $representante->getId($conexao));
        $sql->bindValue(":cidade", $representante->getCidade());
        $sql->bindValue(":estado", $representante->getEstado());
        $sql->bindValue(":orgao", $representante->getOrgao());
        $sql->bindValue(":cargo", $representante->getCargo());
        $sql->execute();
        $sql = null;
    }

    public function obterSenha(Email $email) {

        // busca o email no banco
        $resposta = $this->emailExiste($email->getEmail());

        // se o email não existe, senha tbm não
        if (!$resposta['resposta']){
            return $resposta;
        }

        // comando SQL para recuperar o hash no banco
        $comando = "
                SELECT 
                    senha_usuario 
                FROM 
                    tb_usuario 
                WHERE 
                    email_usuario = :email;
            ";

        // Executa o processo para resgatar o hash da senha
        try {
            // inicia a classe de conexão
            $conexao = new Conexao();

            // cria a conexão com o banco
            $conexao = $conexao->getConexao();

            // prepara o comando a ser executado
            $sql = $conexao->prepare($comando);

            // adicione o parametro email da consulta
            $sql->bindValue(":email", $email);

            // executa o comando
            $sql->execute();

            // armazena a resposta do banco
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if (!$resposta) return RespostaProcesso::resposta("Usuario não localizado", false);

            // envia o hash da senha armazenada no banco
            return RespostaProcesso::resposta($resposta['senha_usuario'], true);

        } catch (Exception $erro){
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Erro Back-end: ", false, dados: $dados);

        } finally {
            $sql = null;
            $conexao = null;
        }
    }

    public function emailExiste(string $email) {
        $comando = "
                SELECT 
                    email_usuario FROM tb_usuario 
                WHERE 
                    email_usuario = :email
        ";
        try {
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue(":email", $email);
            $sql->execute();
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if (!$resposta) return RespostaProcesso::resposta("Email não cadastrado");
            if (empty($resposta)) return RespostaProcesso::resposta("Email não cadastrado");
            return RespostaProcesso::resposta("Email cadastrado", true);

        } catch (Exception $erro){
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta("Erro Back-end", false, $dados);
        } finally {
            $sql = null;
            $conexao = null;
        }
    }

    public function obterUsuario(Email $email = null, int $id = null) {
        

        if ($email != null){
            $resposta = $this->emailExiste($email->getEmail());
            if ($resposta['resposta']){
                // se o email existe considera ele para o sql
                $comando = "
                    SELECT
                        id_usuario, nome_usuario, sobrenome_usuario, 
                        cpf_usuario, perfil_usuario, email_usuario
                    FROM
                        tb_usuario
                    WHERE 
                        email_usuario = :email
                ";
                $parametro = ":email";
                $valor = $email->getEmail();
            } else {
                // se não existe o email o usuario é falso
                SessaoController::encerrarSessao();
                return RespostaProcesso::resposta("Erro back-end: Email não registrado");
            }

        } else if ($id != null) {
            if (ctype_digit($id) && is_integer($id) && (int) $id > 0){
                $comando = "
                    SELECT
                        id_usuario, nome_usuario, sobrenome_usuario, 
                        cpf_usuario, perfil_usuario, email_usuario
                    FROM
                        tb_usuario
                    WHERE 
                        id_usuario = :id
                ";
                $parametro = ":id";
                $valor = $id;
            } else {
                // o id é um inteiro positivo > 0? não
                return RespostaProcesso::resposta("Erro back-end: Id inválido");
            }

        } else {
            // os dois objetos são nulls, erro do back-end
            return RespostaProcesso::resposta("Erro back-end: Deve ser passado pelo menos 1 parametro para a função");
        }    

        try {
            $conexao_obj = new Conexao();
            $conexao = $conexao_obj->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue($parametro, $valor);
            $sql->execute();
            $resposta = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$resposta) return RespostaProcesso::resposta("Usuario não localizado");
            $usuario = new Usuario(
                dados_usuario: new DadosPessoais(
                    nome: new Nome($resposta['nome_usuario']),
                    sobrenome: new Sobrenome($resposta['sobrenome_usuario']),
                    cpf: new Cpf($resposta['cpf_usuario']),
                    email: new Email($resposta['email_usuario'])
                )
            );

            $usuario->setPerfil($resposta['perfil_usuario']);
            $usuario->setId((int)$resposta['id_usuario']);

            if ($usuario->getPerfil() == "municipe"){
                $comando = "
                    SELECT 
                        cidade_municipe AS cidade, 
                        estado_municipe AS estado 
                    FROM 
                        tb_municipe 
                    WHERE 
                        id_usuario = :id
                ";
            } else if ($usuario->getPerfil() == "representante"){
                $comando = "
                    SELECT 
                        cidade_representante AS cidade, 
                        estado_representante AS estado, 
                        orgao_representante AS orgao,
                        cargo_representante AS cargo
                    FROM 
                        tb_representante 
                    WHERE 
                        id_usuario = :id
                ";

            } else {
                return RespostaProcesso::resposta("Perfil não indentificado");
            }

            $conexao = $conexao_obj->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue(":id", $usuario->getId());
            $sql->execute();

            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
 
            if (!$resposta) {
                // se não foi localizado nenhum ID

                return RespostaProcesso::resposta("Id não localizado");}

            if ($usuario->getPerfil() == "representante"){
                $usuario->setPrefeitura(
                    new Prefeitura(
                        $resposta['orgao'], 
                        $resposta['cargo']
                        )
                    );
            }

            $usuario->setEndereco(
                new Endereco(
                    $resposta['cidade'],
                    $resposta['estado']
                )
            );

            return RespostaProcesso::resposta("Usuario criado com sucesso", true, dados: array($usuario));

        } catch (Exception $erro) {
           $dados = RespostaProcesso::salvarErro($erro);
           $msg = $dados['mensagem'];
           return RespostaProcesso::resposta("Erro back-end: $msg", false, dados: $dados);
        } finally {
            $sql = null;
            $conexao = null;
            $conexao_obj = null;
        }
    }

}