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

    public function obterSenha(Email $email){
        
    }
        
}