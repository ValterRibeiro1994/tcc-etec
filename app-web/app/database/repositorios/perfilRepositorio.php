<?php

class PerfilRepositorio {

    public function obterDadosPerfil(string $email): array {
        $tabela_usuario = $GLOBALS['usuario'];

        $comando = "SELECT nome_usuario, sobrenome_usuario, email_usuario, perfil_usuario,
                           cidade_usuario AS cidade,
                           estado_usuario AS estado,
                           orgao_usuario AS orgao,
                           cargo_usuario AS cargo
                    FROM $tabela_usuario
                    WHERE email_usuario = :email";

        try {
            $conexao = (new Conexao())->getConexao();
            $sql = $conexao->prepare($comando);
            $sql->bindValue(':email', $email);
            $sql->execute();
            $linha = $sql->fetch(PDO::FETCH_ASSOC);

            if (!$linha) return RespostaProcesso::resposta("Usuário não localizado");

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