<?php

class SessaoController {

    private static function iniciarSessao() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function usuarioAtivo(){
        self::iniciarSessao();
        if (empty($_SESSION['token'])){
            return false;
        }
        return true;
    }

    public static function salvarToken(string $token) {
        self::iniciarSessao();
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = $token;
        }
    }

    public static function removerToken() {
        self::iniciarSessao();
        unset($_SESSION['token']); 
    }

    public static function salvarDenuncia(Denuncia $denuncia) {
        self::iniciarSessao();
        $_SESSION['denuncias'][] = $denuncia;
    }

    public static function obterDenuncias(): array {
        self::iniciarSessao();
        return $_SESSION['denuncias'] ?? null;
    }
}
