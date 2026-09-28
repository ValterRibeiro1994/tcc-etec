<?php

class SessaoController {
    private static function iniciarSessao() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function salvarUsuario(Usuario $usuario): void {
        self::iniciarSessao();
        // $_SESSION['user']['nome'] = $nome;
        // $_SESSION['user']['sobrenome'] = $sobrenome;
        // $_SESSION['user']['token'] = $token;
    }

    public static function estaConectado(): bool {
        self::iniciarSessao();
        return array_key_exists("user", $_SESSION);
    }



}