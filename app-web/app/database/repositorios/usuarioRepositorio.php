<?php

class UsuarioRepositorio {
    private Conexao $db_conexao;
    private PDO|null $pdo;
    
    public function __construct(Conexao $conexao){
        $this->db_conexao = $conexao;
    }

    private function iniciarConexao() {
        try {
            $pdo = $this->db_conexao->getConexao();
            $this->pdo = $pdo;
            return RespostaProcesso::resposta(
                mensagem: "Database: Conexão PDO concluida com sucesso", resposta: true
            );
        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Database: " . $erro->getMessage(), resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    public function adicionarUsuario(Usuario $usuario){
        $comando = "
            INSERT INTO 
                tb_usuario (
                nome_usuario, sobrenome_usuario, perfil_usuario, 
                email_usuario, cidade_usuario, estado_usuario,
                orgao_usuario, cargo_usuario, senha_usuario,
                cpf_usuario
            )
            VALUES (
                :nome, :sobrenome, :perfil, 
                :email, :cidade, :estado,
                :orgao, :cargo, :senha,
                :cpf
            )
        ";

        // tenta iniciar uma conexão com o banco 
        try {
            $resposta = $this->iniciarConexao();
            if (!$resposta){
                return $resposta;
            }

            // executa os comandos
            $cursor = $this->pdo->prepare($comando);
            $cursor->bindValue(":nome", $usuario->getNome());
            $cursor->bindValue(":sobrenome", $usuario->getSobrenome());
            $cursor->bindValue(":email", $usuario->getEmail());
            $cursor->bindValue(":cpf", $usuario->getCpf());
            $cursor->bindValue(":perfil", $usuario->getPerfil());
            $cursor->bindValue(":senha", $usuario->obterHashSenha());
            $cursor->bindValue(":estado", $usuario->getEstado());
            $cursor->bindValue(":cidade",  $usuario->getCidade());
            $cursor->bindValue(":orgao", $usuario->getPrefeitura()->getOrgao());
            $cursor->bindValue(":cargo", $usuario->getPrefeitura()->getCargo());
            $cursor->execute();

            return RespostaProcesso::resposta(
                mensagem: "Database: Usuario registrado com sucesso", resposta: true
            );

        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Database: Erro ao registrar usuario", resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        } finally {
            $this->pdo = null;
        }

    }

    public function obterUsuario(string $email){
        try {
            $tabela_usuario = $GLOBALS['usuario'];
            $comando = "
                    SELECT 
                        nome_usuario AS nome, sobrenome_usuario AS sobrenome, perfil_usuario AS perfil,
                        cpf_usuario AS cpf, cidade_usuario AS cidade, estado_usuario AS estado,
                        orgao_usuario AS orgao, cargo_usuario AS cargo
                    FROM 
                        $tabela_usuario
                    WHERE email_usuario = :email 
            ";

            $resposta = $this->iniciarConexao();
            if (!$resposta) return $resposta;

            $cursor = $this->pdo->prepare($comando);
            $cursor->bindValue(":email", $email);
            $cursor->execute();

            $resposta = $cursor->fetch(PDO::FETCH_ASSOC);
            
            return RespostaProcesso::resposta(
                mensagem: "Database: Usuario localizado com sucesso", resposta: true,
                dados: $resposta
            );
        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Database: Erro ao localizar usuario", resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        } finally {
            $this->pdo = null;
        }
    }


}