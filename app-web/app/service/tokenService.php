<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class TokenService {

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
    }

    public static function criarToken(Usuario $usuario, int $expira = 86400){
        try {
            $dados = [
                "perfil" => $usuario->getPerfil(),
                'nome' => $usuario->getNome(),
                'sobrenome' => $usuario->getSobrenome(),
                "email" => $usuario->getEmail()
            ];

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

            if ($usuario->getPerfil() != "visitante"){
                $configuracao['cidade'] = $usuario->getCidade();
                $dados['cidade'] = $usuario->getCidade();
                $configuracao['estado'] = $usuario->getEstado();
                $dados['estado'] = $usuario->getEstado();
            }

            if ($usuario->getPerfil() == "representante"){
                $configuracao['orgao'] = $usuario->getPrefeitura()->getOrgao();
                $dados['orgao'] = $usuario->getPrefeitura()->getOrgao();
                $configuracao['cargo'] = $usuario->getPrefeitura()->getCargo();
                $dados['cargo'] = $usuario->getPrefeitura()->getCargo();
            }

            $jwt = JWT::encode($configuracao, self::obterChave(), self::obterAlgoritmo());
            $dados['token'] = $jwt;
            return RespostaProcesso::resposta(
                mensagem: "Token criar com sucesso para " . $usuario->getPerfil(), 
                resposta: true,
                dados: $dados
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