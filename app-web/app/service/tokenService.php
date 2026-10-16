<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenService {

<<<<<<< Updated upstream
    public static function validarToken(string $token){
        try {
            $token = str_replace("Bearer ", "", $token);
            $decodificar = JWT::decode($token, new Key(self::obterChave(), self::obterAlgoritmo()));
            return RespostaProcesso::resposta(
                mensagem: "Token válido", resposta: true,
                dados: get_object_vars($decodificar)
            );
        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta(
                mensagem: "Erro criar Token: " . $dados['mensagem'], resposta: false,
                dados: $dados
            );
        }
=======
    public static function validarToken(string $token_cliente){
    try {
        $token = str_replace("Bearer ", "", $token_cliente);
        $decodificar = JWT::decode($token, new Key(self::obterChave(), self::obterAlgoritmo()));

        // transforma o conteúdo do token (perfil, nome, sobrenome, email...) em array
        $dados = (array) $decodificar;
        $dados['status'] = 'valido';

        return RespostaProcesso::resposta(
            mensagem: "Service: Token válido", resposta: true,
            dados: $dados
        );
    } catch (ExpiredException $erro){
        return RespostaProcesso::resposta(
            mensagem: "Service: Token expirado", resposta: false,
            dados: ['status'=> 'expirado']
        );
    } catch (Exception $erro) {
        $dados = RespostaProcesso::salvarErro($erro);
        $dados['status'] = 'invalido';
        return RespostaProcesso::resposta(
            mensagem: "Service: Erro ao validar token", resposta: false,
            dados: $dados
        );
>>>>>>> Stashed changes
    }
}

    public static function criarToken(Usuario $usuario, int $expira = 86400){
        try {
            $configuracao = [
                "iss" => "localhost",
                "aud" => "localhost",
                "iat" => time(),
                "exp" => time() + $expira,
                "perfil" => $usuario->getPerfil(),
                'nome' => $usuario->getNome(),
                'sobrenome' => $usuario->getSobrenome(),
                "email" => $usuario->getEmail()
            ];

            $jwt = JWT::encode($configuracao, self::obterChave(), self::obterAlgoritmo());
            return RespostaProcesso::resposta(
                mensagem: "Token criar com sucesso para " . $usuario->getPerfil(), 
                resposta: true,
                dados: [
                    "token"=>$jwt,
                    "perfil" => $usuario->getPerfil(),
                    'nome' => $usuario->getNome(),
                    'sobrenome' => $usuario->getSobrenome(),
                    "email" => $usuario->getEmail()
                    ]
            );
        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta(
                mensagem: "Erro criar Token: " . $dados['mensagem'], resposta: false,
                dados: $dados
            );
        }
    }

    private static function obterChave(): string {
        return "projeto-relate-tcc-etec-lauro-gomes";
    }

    private static function obterAlgoritmo(): string {
        return "HS256";
    }
}