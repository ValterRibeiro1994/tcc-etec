<?php

// Consultas do perfil do usuário logado.
// ADICIONADO como arquivo separado (e não dentro do UsuarioRepositorio) para não depender
// do UsuarioRepositorio, que está sendo reformulado (agora exige Conexao no construtor).
class PerfilRepositorio {

    // Dados do perfil do usuário logado, buscados no banco pelo e-mail do token.
    // Junta tb_usuario com tb_municipe/tb_representante (LEFT JOIN, pois o usuário é de um tipo só).
    // Devolve apenas campos seguros: nunca CPF nem senha.
    public function obterDadosPerfil(string $email): array {
        $tabela_usuario = $GLOBALS['usuario'];
        $tabela_municipe = $GLOBALS['municipe'];
        $tabela_representante = $GLOBALS['representante'];

        $comando = "
                    SELECT nome_usuario, sobrenome_usuario, email_usuario, perfil_usuario,
                        cidade_usuario AS cidade, estado_usuario AS estado,
                        orgao_usuario AS orgao, cargo_usuario AS cargo
                    FROM tb_usuario
                    WHERE email_usuario = :email";

        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue(':email', $email);
            $sql->execute();
            $linha = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$linha) return RespostaProcesso::resposta("Usuário não localizado");

            // os nomes são salvos com htmlspecialchars no cadastro; aqui voltam ao texto normal
            $decodificar = function ($texto) {
                return $texto === null ? null : html_entity_decode((string) $texto, ENT_QUOTES, 'UTF-8');
            };

            return RespostaProcesso::resposta(
                mensagem: "Dados do perfil obtidos",
                resposta: true,
                dados: [
                    "nome"      => $decodificar($linha['nome_usuario']),
                    "sobrenome" => $decodificar($linha['sobrenome_usuario']),
                    "email"     => $linha['email_usuario'],
                    "perfil"    => $linha['perfil_usuario'],
                    "cidade"    => $decodificar($linha['cidade']),
                    "estado"    => $linha['estado'],
                    "orgao"     => $decodificar($linha['orgao']),
                    "cargo"     => $decodificar($linha['cargo'])
                ]
            );
        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao buscar os dados do perfil",
                resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    public function buscarConta(string $email): array|false {
        $conexao = (new Conexao())->getConexao();
        $sql = $conexao->prepare(
            'SELECT id_usuario AS id, senha_usuario AS senha, avisos_email FROM tb_usuario WHERE email_usuario = :email'
        );
        $sql->execute([':email' => $email]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    // O nome da coluna só pode vir desta lista fixa, nunca direto do navegador.
    public function alterarCampo(int $id, string $campo, string|int $valor): void {
        $conexao = (new Conexao())->getConexao();
        if (!in_array($campo, ['avisos_email', 'email_usuario', 'senha_usuario'], true))
            throw new RuntimeException('Campo não permitido.');
        $sql = $conexao->prepare("UPDATE tb_usuario SET $campo = :valor WHERE id_usuario = :id");
        $sql->execute([':valor' => $valor, ':id' => $id]);
    }

    public function alterarLocal(int $id, string $estado, string $cidade): void {
        $conexao = (new Conexao())->getConexao();
        $sql = $conexao->prepare(
            'UPDATE tb_usuario SET estado_usuario = :estado, cidade_usuario = :cidade WHERE id_usuario = :id'
        );
        $sql->execute([':estado' => $estado, ':cidade' => $cidade, ':id' => $id]);
    }

    public function excluirConta(int $id): void {
        $conexao = (new Conexao())->getConexao();
        // Primeiro removemos as respostas ligadas às denúncias deste usuário.
        $conexao->beginTransaction();
        try {
            $sql = $conexao->prepare(
                'DELETE FROM tb_resposta WHERE id_denuncia IN
                 (SELECT id_denuncia FROM tb_denuncia WHERE id_usuario = :id)'
            );
            $sql->execute([':id' => $id]);

            // Depois as respostas escritas por ele em denúncias de outras pessoas.
            $sql = $conexao->prepare('DELETE FROM tb_resposta WHERE id_usuario = :id');
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare('DELETE FROM tb_denuncia WHERE id_usuario = :id');
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare('DELETE FROM tb_usuario WHERE id_usuario = :id');
            $sql->execute([':id' => $id]);
            $conexao->commit();
        } catch (Throwable $erro) {
            $conexao->rollBack();
            throw $erro;
        }
    }
}
