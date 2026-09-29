<?php

class SessaoController {
    private static function iniciarSessao() {
        // 1. Configurações de segurança da sessão (devem vir ANTES do session_start)
        ini_set('session.cookie_lifetime', 86400);        // O cookie expira quando o navegador fecha, 86400 é o tempo de 1 dia em segundos
        ini_set('session.cookie_httponly', 1);       // o JS não pode conhecer o id da sessão
        ini_set('session.cookie_secure', 0);         // APENAS se seu site usa HTTPS (mude para 0 se for localhost HTTP)
        ini_set('session.use_only_cookies', 1);      // Força a sessão a usar apenas cookies (evita ID na URL)
        ini_set('session.cookie_samesite', 'Lax');   // Protege contra ataques de CSRF

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function salvarUsuario(Usuario $usuario, bool $lembrar): void {
        self::iniciarSessao();
        $_SESSION['user']['id'] = $usuario->getId();
        $_SESSION['user']['nome'] = $usuario->getNome();
        $_SESSION['user']['sobrenome'] = $usuario->getSobrenome();
        $_SESSION['user']['perfil'] = $usuario->getPerfil();
        $_SESSION['user']['estado'] = $usuario->getEstado();
        $_SESSION['user']['cidade'] = $usuario->getCidade();
        $_SESSION['user']['orgao'] = $usuario->getOrgao();
        $_SESSION['user']['cargo'] = $usuario->getCargo();
        $_SESSION['user']['lembrar'] = $lembrar;
        $_SESSION['user']['criado_em'] = date("Y-m-d H:i:s");

        // $_SESSION['user']['token'] = $token;
    }

    public static function encerrarSessao(): void {
        self::iniciarSessao();
        session_unset();
        session_destroy();
    }

    public static function estaConectado(): bool {
        self::iniciarSessao();

        // Verifica se o usuário possui uma sessão ativa
        if (!array_key_exists("user", $_SESSION) || !isset($_SESSION['user']['criado_em'])) return false;

        // verifica se ele escolheu manter a sessão
        if (!$_SESSION['lembrar']){
            self::encerrarSessao();
            return false;
        }

        // Cria os objetos de data para a comparação
        // $_SESSION['user']['criado_em'] foi salvo no formato "Y-m-d H:i:s"
        $data_login = new DateTime($_SESSION['user']['criado_em']);
        $data_atual = new DateTime(); // Pega a data e hora exata de agora

        // Modifica a data de login somando 1 dia
        $data_expira = clone $data_login;
        $data_expira->modify('+1 day');

        //Compara os objetos diretamente (o PHP sabe qual data é maior/menor)
        if ($data_atual > $data_expira) {
            self::encerrarSessao();
            return false;
        }

        return true;
    }




}