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
}
