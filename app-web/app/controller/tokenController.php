<?php

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;

class TokenController {
    private string $jwt;
    private string $chave = "projeto-relate-tcc-etec-lauro-gomes";
    private string $algoritmo = "HS256";

    public function gerarToken(Usuario $usuario, int $expira){
        $configuracao = [
            "iss" => "localhost",
            "aud" => "localhost",
            "iat" => time(),
            "exp" => time() + $expira,
            "id" => $usuario->getId(),
            'nome' => $usuario->getNome(),
            "email" => $usuario->getEmail()
        ];
        $this->jwt = JWT::encode($configuracao, $this->chave, $this->algoritmo);
        return $this->jwt;
    }

    public function validarToken(){
        try {
            $headers = getallheaders();
            if (!isset($headers['Authorization'])){
                throw new Exception("Token não enviado");
            }

            $token = str_replace("Bearer ", "", $headers['Authorization']);
            $decodificar = JWT::decode($token, new Key($this->chave, $this->algoritmo));
            return RespostaProcesso::respostaProcesso("Token valido", true, dados: get_object_vars($decodificar));
        } catch (ExpiredException $erro){
            throw new Exception("Token expirado");
        } catch (SignatureInvalidException $erro){
            throw new Exception("Assinatura inválida");
        } catch (BeforeValidException $erro){
            throw new Exception("Token está sendo usado antes do permitido");
        } catch (Exception $erro){
            throw $erro;
        } finally {
            // qualquer erro no token é um erro de sessão
            SessaoController::encerrarSessao();
        }

    }
}