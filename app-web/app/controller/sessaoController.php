<?php

class SessaoController {

    private static function iniciarSessao() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function salvarUsuario(Usuario $usuario, bool $logado, bool $lembrar){
        self::iniciarSessao();
        $_SESSION['nome'] = $usuario->getNome();
        $_SESSION['sobrenome'] = $usuario->getSobrenome();
        $_SESSION['logado'] = $logado;
        $_SESSION['lembrar'] = $lembrar;
        if ($lembrar){
            $_SESSION['data-hora-entrada'] = new DateTime()->format("d/m/Y H:i");
        }
    }

    public static function armazenarToken(string $token){
        $_SESSION['token'] = $token;
    } 
}
