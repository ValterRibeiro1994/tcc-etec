<?php


class UsuarioRepositorio {


    public function cadastrarUsuario(Usuario $usuario): array {
        try {
            if ($this->emailExiste($usuario->getEmail())['resposta']) throw new Exception("Email: Email já cadastrado");

            $conexao = new Conexao();
            $conexao = $conexao->getConexao();
            $this->salvarUsuario($usuario, $conexao);
            if ($usuario->getPerfil() === "representante"){
                $this->salvarRepresentante($usuario, $conexao);
                $msg = "Cadastro para representante efetuado com sucesso";
            } else if ($usuario->getPerfil() === "municipe"){
                $this->salvarMunicipe($usuario, $conexao);
                $msg = "Cadastro para municipe efetuado com sucesso";
            } else {
                throw new Exception("Perfil: Perfil de usuário inválido");
            }
            return RespostaProcesso::respostaProcesso($msg, true);
            
        } catch(Exception $erro){
            return RespostaProcesso::respostaProcesso($erro->getMessage());
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
        $email = $email->getEmail();
        $resposta = $this->emailExiste($email);
        if (!$resposta['resposta']) return $resposta;

        $comando = "SELECT senha_usuario FROM tb_usuario WHERE email_usuario = :email;";
        try {
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":email", $email);
            $sql->execute();

            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if (!$resposta) return RespostaProcesso::respostaProcesso("ERRO: Senha não localizada");

            return RespostaProcesso::respostaProcesso($resposta['senha_usuario'], true);
        } catch (Exception $erro){
            $msg = $erro->getMessage();
            return RespostaProcesso::respostaProcesso($msg);
        } catch (PDOException $erro){
            $msg = $erro->getMessage();
            return RespostaProcesso::respostaProcesso("ERRO BANCO: $msg");
        } finally {
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
            if ($resposta != false  && count($resposta) > 0){
                return RespostaProcesso::respostaProcesso("Email cadastrado", true);
            }
            return RespostaProcesso::respostaProcesso("Email não existe no sistema", false);

        } catch (Exception $erro){
            return RespostaProcesso::respostaProcesso($erro->getMessage());
        } finally {
            $sql = null;
            $conexao = null;
        }
    }

    public function obterUsuario(Email $email){
        $resposta = $this->emailExiste($email->getEmail());
        if (!$resposta['resposta']) return $resposta;

        // continuar aqaui....
    }

}