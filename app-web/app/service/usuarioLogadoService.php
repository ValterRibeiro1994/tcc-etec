<?php

// Descobre quem é o usuário logado a partir do token enviado no cabeçalho "Authorization".
// Usado pelas rotas que exigem login: denuncia/minhas, denuncia/criar e user/dados.
class UsuarioLogadoService {

    public static function obter(): array {
        $token = self::lerCabecalhoAuthorization();
        if ($token === null) {
            return RespostaProcesso::resposta("Faça login para continuar (o servidor não recebeu o token)");
        }

        $resposta = TokenService::validarToken($token);
        if (!$resposta['resposta']) {
            return RespostaProcesso::resposta("Sessão inválida ou expirada. Faça login novamente.");
        }

        $dados = $resposta['dados'];
        $perfil = $dados['perfil'] ?? "visitante";
        if ($perfil === "visitante" || empty($dados['email'])) {
            return RespostaProcesso::resposta("Faça login para continuar (token sem os dados do usuário)");
        }

        return RespostaProcesso::resposta(
            mensagem: "Usuário autenticado",
            resposta: true,
            dados: [
                "perfil"    => $perfil,
                "nome"      => $dados['nome'] ?? "",
                "sobrenome" => $dados['sobrenome'] ?? "",
                "email"     => $dados['email']
            ]
        );
    }

    private static function lerCabecalhoAuthorization(): string|null {
        $cabecalhos = function_exists('getallheaders') ? getallheaders() : [];
        if (is_array($cabecalhos)) {
            foreach ($cabecalhos as $nome => $valor) {
                if (strtolower((string) $nome) === "authorization" && trim((string) $valor) !== "") {
                    return trim((string) $valor);
                }
            }
        }

        // alguns servidores entregam o cabeçalho nesta variável
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) return trim($_SERVER['HTTP_AUTHORIZATION']);

        // quando o .htaccess redireciona a requisição (RewriteRule), o nome ganha o prefixo REDIRECT_
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        return null;
    }
}