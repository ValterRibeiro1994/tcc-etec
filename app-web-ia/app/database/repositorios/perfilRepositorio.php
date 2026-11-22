<?php

// Consultas do perfil do usuário logado, lidas diretamente de tb_usuario.
class PerfilRepositorio {
    private ?PDO $pdo;

    private function tabela(string $chave): string {
        $nome = $GLOBALS[$chave] ?? null;
        if (!is_string($nome) || !preg_match('/\A[a-zA-Z_][a-zA-Z0-9_]*\z/', $nome)) {
            throw new RuntimeException("Nome de tabela inválido na configuração: $chave");
        }
        return $nome;
    }

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
    }

    private function conectar(): PDO {
        return $this->pdo ?? (new Conexao())->getConexao();
    }

    // Dados do perfil do usuário logado, buscados pelo e-mail do token.
    // Devolve apenas campos seguros: nunca CPF nem senha.
    public function obterDadosPerfil(string $email): array {
        $tabela_usuario = $this->tabela('usuario');

        $comando = "
                    SELECT nome_usuario, sobrenome_usuario, email_usuario, perfil_usuario,
                        cidade_usuario AS cidade, estado_usuario AS estado,
                        orgao_usuario AS orgao, cargo_usuario AS cargo
                    FROM $tabela_usuario
                    WHERE email_usuario = :email";

        try {
            $conexao = $this->conectar();
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
        $conexao = $this->conectar();
        $tabela_usuario = $this->tabela('usuario');
        $sql = $conexao->prepare(
            "SELECT id_usuario AS id, senha_usuario AS senha, avisos_email FROM $tabela_usuario WHERE email_usuario = :email"
        );
        $sql->execute([':email' => $email]);
        return $sql->fetch(PDO::FETCH_ASSOC);
    }

    // O nome da coluna só pode vir desta lista fixa, nunca direto do navegador.
    public function alterarCampo(int $id, string $campo, string|int $valor): void {
        $conexao = $this->conectar();
        $tabela_usuario = $this->tabela('usuario');
        if (!in_array($campo, ['avisos_email', 'email_usuario', 'senha_usuario'], true))
            throw new RuntimeException('Campo não permitido.');

        $sql = $conexao->prepare("UPDATE $tabela_usuario SET $campo = :valor WHERE id_usuario = :id");
        $sql->bindValue(":valor", $valor);
        $sql->bindValue(":id", $id, PDO::PARAM_INT);
        $sql->execute();
    }

    public function alterarLocal(int $id, string $estado, string $cidade): void {
        $conexao = $this->conectar();
        $tabela_usuario = $this->tabela('usuario');
        $sql = $conexao->prepare(
            "UPDATE $tabela_usuario SET estado_usuario = :estado, cidade_usuario = :cidade WHERE id_usuario = :id"
        );
        $sql->execute([':estado' => $estado, ':cidade' => $cidade, ':id' => $id]);
    }

    public function excluirConta(int $id): void {
        $conexao = $this->conectar();
        $usuario = $this->tabela('usuario');
        $denuncia = $this->tabela('denuncia');
        $resposta = $this->tabela('resposta_tb');
        $conversa = $this->tabela('conversa');
        $mensagem = $this->tabela('mensagem');
        $notificacao = $this->tabela('notificacao');
        $aprovacao = $this->tabela('representante_aprovado');
        $historico = $this->tabela('historico_status');
        $auditoria = $this->tabela('admin_auditoria');

        $conexao->beginTransaction();
        try {
            $sql = $conexao->prepare(
                "DELETE FROM $mensagem WHERE id_conversa IN
                 (SELECT id_conversa FROM $conversa WHERE id_representante = :representante OR id_denunciante = :denunciante)"
            );
            $sql->execute([':representante' => $id, ':denunciante' => $id]);

            $sql = $conexao->prepare(
                "DELETE FROM $conversa WHERE id_representante = :representante OR id_denunciante = :denunciante"
            );
            $sql->execute([':representante' => $id, ':denunciante' => $id]);

            $sql = $conexao->prepare("DELETE FROM $notificacao WHERE id_usuario = :id");
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare(
                "UPDATE $notificacao SET id_denuncia = NULL WHERE id_denuncia IN
                 (SELECT id_denuncia FROM $denuncia WHERE id_usuario = :id)"
            );
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare(
                "DELETE FROM $historico WHERE id_denuncia IN
                 (SELECT id_denuncia FROM $denuncia WHERE id_usuario = :id)"
            );
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare(
                "UPDATE $historico SET id_representante = NULL WHERE id_representante = :id"
            );
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare(
                "UPDATE $auditoria SET id_administrador = NULL WHERE id_administrador = :id"
            );
            $sql->execute([':id' => $id]);
            $sql = $conexao->prepare(
                "UPDATE $auditoria SET id_representante = NULL WHERE id_representante = :id"
            );
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare("DELETE FROM $aprovacao WHERE id_usuario = :id");
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare(
                "DELETE FROM $resposta WHERE id_denuncia IN
                 (SELECT id_denuncia FROM $denuncia WHERE id_usuario = :id)"
            );
            $sql->execute([':id' => $id]);

            // Depois as respostas escritas por ele em denúncias de outras pessoas.
            $sql = $conexao->prepare("DELETE FROM $resposta WHERE id_usuario = :id");
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare("DELETE FROM $denuncia WHERE id_usuario = :id");
            $sql->execute([':id' => $id]);

            $sql = $conexao->prepare("DELETE FROM $usuario WHERE id_usuario = :id");
            $sql->execute([':id' => $id]);
            $conexao->commit();
        } catch (Throwable $erro) {
            $conexao->rollBack();
            throw $erro;
        }
    }
}
