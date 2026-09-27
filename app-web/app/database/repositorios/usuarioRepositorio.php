<?php


class UsuarioRepositorio {

    public function salvarMunicipe(Municipe $municipe){
        $nome = $municipe->getNome();
        $sobrenome = $municipe->getSobrenome();
        $email = $municipe->getEmail();
        $cpf = $municipe->getCpf();
        $senha = $municipe->getSenha();
        
        try {
            $comando = "INSERT INTO tb_usuario(nome_usuario, sobrenome_usuario, email_usuario, cpf_usuario, perfil_usuario, senha_usuario) 
            VALUES (:nome, :sobrenome, :email, :cpf, :perfil, :senha)";

            $conexao = new Conexao();
            $conexao = $conexao->getConexao();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":nome", $nome);
            $sql->bindValue(":sobrenome", $sobrenome);
            $sql->bindValue(":email", $email);
            $sql->bindValue(":cpf", $cpf);
            $sql->bindValue(":perfil", "municipe");
            $sql->bindValue(":senha", $senha);
            $sql->execute();

            $comando = "INSERT INTO tb_municipe(id_usuario, cidade_municipe, estado_municipe)
            VALUES (:id, :cidade, :estado)";

            $id = $municipe->getId($conexao);
            $estado = $municipe->getEstado();
            $cidade = $municipe->getCidade();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":id", $id);
            $sql->bindValue(":estado", $estado);
            $sql->bindValue(":cidade", $cidade);
            $sql->execute();

            return RespostaProcesso::respostaProcesso("Cadastro realizado com Sucesso", true);
        } catch (Exception $erro) {
            return RespostaProcesso::respostaProcesso($erro->getMessage());
        } finally {
            $conexao = null;
        }

    }

    public function salvarRepresentante(Representante $representante){
        $nome = $representante->getNome();
        $sobrenome = $representante->getSobrenome();
        $email = $representante->getEmail();
        $cpf = $representante->getCpf();
        $estado = $representante->getEstado();
        $cidade = $representante->getCidade();
        $cargo = $representante->getCargo();
        $prefeitura = $representante->getPrefeitura();
        $senha = $representante->getSenha();

        $comando = "INSERT INTO tb_usuario(nome_usuario, sobrenome_usuario, email_usuario, cpf_usuario, perfil_usuario, senha_usuario) 
        VALUES (:nome, :sobrenome, :email, :cpf, :perfil, :senha)";

        try {
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":nome", $nome);
            $sql->bindValue(":sobrenome", $sobrenome);
            $sql->bindValue(":email", $email);
            $sql->bindValue(":cpf", $cpf);
            $sql->bindValue(":perfil", "representante");
            $sql->bindValue(":senha", $senha);
            $sql->execute();

            $id = $representante->getId($conexao);
            $comando = "INSERT INTO tb_representante(id_usuario, cidade_representante, estado_representante, orgao_representante, cargo_representante)
            VALUES (:id, :cidade, :estado, :orgao, :cargo)";
            $sql = $conexao->prepare($comando);
            $sql->bindValue(":id", $id);
            $sql->bindValue(":cidade", $cidade);
            $sql->bindValue(":estado", $estado);
            $sql->bindValue(":orgao", $prefeitura);
            $sql->bindValue(":cargo", $cargo);
            $sql->execute();

            return RespostaProcesso::respostaProcesso("Cadastro realizado com sucesso", true);

        } catch (Exception $erro){
            $msg = "ERRO CÓDIGO: " . $erro->getMessage();
            return RespostaProcesso::respostaProcesso($msg);
        } catch (PDOException $erro){
            $msg = "ERRO BANCO: " . $erro->getMessage();
            return RespostaProcesso::respostaProcesso($msg);
        } finally {
            $conexao = null;
        }
    }

    public function obterSenha(Email $email){
        $email = $email->getEmail();
        $comando = "SELECT senha_usuario FROM tb_usuario WHERE email_usuario = :email;";
        try {
            $conexao = new Conexao();
            $conexao = $conexao->getConexao();

            $sql = $conexao->prepare($comando);
            $sql->bindValue(":email", $email);
            $sql->execute();

            $resposta = $sql->fetch(PDO::FETCH_ASSOC);
            if ($sql->rowCount() == 0 || !$resposta) throw new Exception("ERRO: Email não cadastrado");
            return $resposta['senha_usuario'];
        } catch (Exception $erro){
            $msg = $erro->getMessage();
            return RespostaProcesso::respostaProcesso("ERRO CÓDIGO: $msg");
        } catch (PDOException $erro){
            $msg = $erro->getMessage();
            return RespostaProcesso::respostaProcesso("ERRO BANCO: $msg");
        } finally {
            $conexao = null;
        }
    }


}