<?php

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class TokenController {
    private string $chave = "projeto-relate-tcc-etec-lauro-gomes";
    private string $algoritmo = "HS256";

    public function gerarToken(Usuario $usuario = null, int $expira = 86400){
        if ($usuario == null){
            $perfil = "visitante";
            $nome = "visitante";
            $sobrenome = "visitante";
            $email = "visitante@gmail.com";
            $expira = 600; // 10 minutos  
        } else {
            $perfil = $usuario->getPerfil();
            $nome = $usuario->getNome();
            $sobrenome = $usuario->getSobrenome();
            $email = $usuario->getEmail();
        }
        try {
            $configuracao = [
                "iss" => "localhost",
                "aud" => "localhost",
                "iat" => time(),
                "exp" => time() + $expira,
                "perfil" => $perfil,
                'nome' => $nome,
                'sobrenome' => $sobrenome,
                "email" => $email
            ];

            $jwt = JWT::encode($configuracao, $this->chave, $this->algoritmo);
            $_SESSION['user']['seg']['token'] = $jwt;
            return RespostaProcesso::resposta($jwt, true);
        } catch(Exception $erro) {
            // qualquer erro o processo de login deve ser encerrado
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta($erro->getMessage(), false, $dados);
        }
    }

    public function validarToken(){
        try {
            $headers = getallheaders();
            if (isset($headers['Authorization'])){
                $token_recebido = $headers['Authorization'];
            } else if (isset($_SESSION['user']['seg']['token'])){
                $token_recebido = $_SESSION['user']['seg']['token'];
            } else {
                return RespostaProcesso::resposta("Erro front-end: Token não foi enviado");
            }

            $token = str_replace("Bearer ", "", $token_recebido);
            $decodificar = JWT::decode($token, new Key($this->chave, $this->algoritmo));
            return RespostaProcesso::resposta("Token valido", true, dados: get_object_vars($decodificar));
        } catch (ExpiredException $erro){
            SessaoController::encerrarSessao();
            return RespostaProcesso::resposta("Token expirado", dados:RespostaProcesso::salvarErro($erro));
        } catch (SignatureInvalidException $erro){
            return RespostaProcesso::resposta("Assinatura inválida", dados:RespostaProcesso::salvarErro($erro));
        } catch (BeforeValidException $erro){
            return RespostaProcesso::resposta("Token está sendo usado antes do permitido", dados:RespostaProcesso::salvarErro($erro));
        } catch (Exception $erro){
            return RespostaProcesso::resposta("Erro desconhecido", dados:RespostaProcesso::salvarErro($erro));
        }

    }
}