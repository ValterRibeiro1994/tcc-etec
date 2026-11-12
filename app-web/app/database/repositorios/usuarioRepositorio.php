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
            $this->pdo = null;
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
                cpf_usuario, area_atuacao_usuario
            )
            VALUES (
                :nome, :sobrenome, :perfil, 
                :email, :cidade, :estado,
                :orgao, :cargo, :senha,
                :cpf, :area
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
            $cursor->bindValue(":perfil", $usuario->getPerfil());
            $cursor->bindValue(":email", $usuario->getEmail());
            $cursor->bindValue(":cidade",  $usuario->getCidade());
            $cursor->bindValue(":estado", $usuario->getEstado());
            $cursor->bindValue(":orgao", $usuario->getPrefeitura()->getOrgao());
            $cursor->bindValue(":cargo", $usuario->getPrefeitura()->getCargo());
            $cursor->bindValue(":senha", $usuario->obterHashSenha());
            $cursor->bindValue(":cpf", $usuario->getCpf());
            // área de atuação (categoria) do representante; NULL para munícipe
            $cursor->bindValue(":area", $usuario->getPrefeitura()->getArea());
            $cursor->execute();

            return RespostaProcesso::resposta(
                mensagem: "Database: Usuario registrado com sucesso", resposta: true
            );

        } catch (Exception $erro) {
            if ($erro->getCode() == "23000"){
                $chaves_unicas = ['email', 'cpf'];
                $msg_erro = mb_strtolower($erro->getMessage(), "utf-8");
                foreach ($chaves_unicas as $chave) {
                    if (str_contains($msg_erro, "duplicate") && str_contains($msg_erro, $chave)) {
                        return RespostaProcesso::resposta(
                            mensagem: "Cliente: Não é possivel registrar o " . mb_strtoupper($chave, 'utf-8') . ", ele já existe no sistema.",
                            resposta: false, dados: RespostaProcesso::salvarErro($erro)

                        );
                    }
                }
                return RespostaProcesso::resposta($msg_erro, dados: RespostaProcesso::salvarErro($erro));
            }
            return RespostaProcesso::resposta(
                mensagem: "Database: Erro ao registrar usuario", resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        } finally {
            $this->pdo = null;
        }

    }

        // Devolve o nome da categoria como está em tb_categoria (ou null se não existir)
    public function obterNomeCategoria(string $nome): ?string {
        try {
            $tabela = $GLOBALS['categorias'];
            $conexao = (new Conexao())->getConexao();
            $cursor = $conexao->prepare("SELECT nome_categoria FROM $tabela WHERE nome_categoria = :nome LIMIT 1");
            $cursor->bindValue(":nome", $nome);
            $cursor->execute();
            $achado = $cursor->fetchColumn();
            return $achado === false ? null : (string) $achado;
        } catch (Exception $erro) {
            return null;
        }
    }

    public function obterUsuario(string $email){
        try {
            $tabela_usuario = $GLOBALS['usuario'];
            $comando = "
                    SELECT 
                        nome_usuario AS nome, sobrenome_usuario AS sobrenome, perfil_usuario AS perfil,
                        cpf_usuario AS cpf, cidade_usuario AS cidade, estado_usuario AS estado,
                        orgao_usuario AS orgao, cargo_usuario AS cargo, senha_usuario AS senha,
                        id_usuario AS id
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
            if ($cursor->rowCount() == 0 || !$resposta){
                return RespostaProcesso::resposta(
                    mensagem: "Database: Usuario não encontrado", resposta: false,
                );
            }
            
            // adiciona o email na resposta
            $resposta['email'] = $email;
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