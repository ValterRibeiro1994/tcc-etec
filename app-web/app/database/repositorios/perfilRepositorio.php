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

        $comando = "SELECT u.nome_usuario, u.sobrenome_usuario, u.email_usuario, u.perfil_usuario,
                           COALESCE(m.cidade_municipe, r.cidade_representante) AS cidade,
                           COALESCE(m.estado_municipe, r.estado_representante) AS estado,
                           r.orgao_representante AS orgao,
                           r.cargo_representante AS cargo
                    FROM $tabela_usuario u
                    LEFT JOIN $tabela_municipe m ON m.id_usuario = u.id_usuario
                    LEFT JOIN $tabela_representante r ON r.id_usuario = u.id_usuario
                    WHERE u.email_usuario = :email";

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
}
