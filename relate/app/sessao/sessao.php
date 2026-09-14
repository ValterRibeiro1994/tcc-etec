<?php

class Sessao {
    private static function iniciarSessao() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function salvarUsuario(string $nome, string $sobrenome, string $token): void {
        self::iniciarSessao();
        $_SESSION['user']['nome'] = $nome;
        $_SESSION['user']['sobrenome'] = $sobrenome;
        $_SESSION['user']['token'] = $token;
    }

    public static function estaConectado(): bool {
        self::iniciarSessao();
        return array_key_exists("user", $_SESSION);
    }

    public static function carregarEstilos() {
        self::iniciarSessao();
        $_SESSION['config']['estilos'] = [
            "app/recursos/bootstrap-5.3.8-dist/css/bootstrap.min.css",
            "app/paginas/estilo/home.css",
        ];
    }

    public static function carregarScripts() {
        self::iniciarSessao();
        $_SESSION['config']['scripts'] = [
            'app/paginas/scripts/login.js',
            'app/recursos/bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js',
        ];
    }
}