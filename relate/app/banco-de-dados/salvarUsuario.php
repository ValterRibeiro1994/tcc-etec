<?php

class SalvarUsuario {
    private PDO|null $conexao;
    public function salvar(ConexaoPDO $conexao, Usuario $usuario) {
        $this->conexao = $conexao->getConexao();
        if (empty($usuario->getPerfil())){
            throw new InvalidArgumentException("Perfil não indentificado");
        }

        try {
            if ($usuario->getPerfil() == "municipe"){
                return $this->salvarMunicipe($usuario);
            }

            return Response::response("Cadastro para reseprentante não desenvolvido", false, "html");
        } catch (\Throwable $th) {
            return Response::response($th->getMessage(), false, "html");
        } finally {
            $this->conexao = null;
        }

        
    }

    private function salvarMunicipe(Usuario $usuario){
        $nome = $usuario->getNome();
        $sobrenome = $usuario->getSobrenome();
        $email = $usuario->getEmail();
        $cpf = $usuario->getCpf();
        $senha = $usuario->getSenha();
        $cidade = $usuario->getCidade();
        $estado = $usuario->getEstado();
        $perfil = $usuario->getPerfil();

        try {
            $tabela_usuario = $GLOBALS['tb_usuario'];
            $comando_sql = "
                insert into $tabela_usuario (
                    NOME_USUARIO, SOBRENOME_USUARIO, 
                    EMAIL_USUARIO, CPF_USUARIO, 
                    SENHA_USUARIO, CIDADE_USUARIO,
                    ESTADO_USUARIO, PERFIL_USUARIO
                ) VALUES (
                    :NOME_USUARIO, :SOBRENOME_USUARIO, 
                    :EMAIL_USUARIO, :CPF_USUARIO, 
                    :SENHA_USUARIO, :CIDADE_USUARIO,
                    :ESTADO_USUARIO, :PERFIL_USUARIO
                )
            ";

            $sql = $this->conexao->prepare($comando_sql);
            $sql->bindValue(":NOME_USUARIO", $nome);
            $sql->bindValue(":SOBRENOME_USUARIO", $sobrenome);
            $sql->bindValue(":EMAIL_USUARIO", $email);
            $sql->bindValue(":CPF_USUARIO", $cpf);
            $sql->bindValue(":SENHA_USUARIO", $senha);
            $sql->bindValue(":CIDADE_USUARIO", $cidade);
            $sql->bindValue(":ESTADO_USUARIO", $estado);
            $sql->bindValue(":PERFIL_USUARIO", $perfil);

            $sql->execute();
            return Response::response("Municipe registrado com sucesso", true);           
        } catch (\Throwable $th) {
            return Response::response($th->getMessage(), false, "html");
        }
    }
}