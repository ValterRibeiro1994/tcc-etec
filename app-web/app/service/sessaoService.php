<?php

class SessaoService {

    private static function iniciarSessao(Usuario $usuario = null, int $duracao) { 
        if (session_status() === PHP_SESSION_NONE) { 
            ini_set('session.cookie_lifetime', $duracao * 1.2);
            ini_set('session.cookie_httponly', 1); 
            ini_set('session.cookie_secure', 0); // Mude para 1 em produção (HTTPS)
            ini_set('session.use_only_cookies', 1); 
            ini_set('session.cookie_samesite', 'Lax'); 
            session_start(); 

            $nome = "visitante";
            $sobrenome = "visitante";
            $email = "visitante@gmail.com";
            $perfil = "visitante";
            $cidade = "visitante";
            $estado = "NI";
            $orgao = "visitante";
            $cargo = "visitante";

            if ($usuario !== null) {
                $nome = $usuario->getNome();
                $sobrenome = $usuario->getSobrenome();
                $email = $usuario->getEmail();
                $cidade = $usuario->getCidade();
                $estado = $usuario->getEstado();
                $perfil = $usuario->getPerfil();
                if ($perfil === "representante"){
                    $orgao = $usuario->getPrefeitura()->getOrgao();
                    $cargo = $usuario->getPrefeitura()->getCargo();
                }
            }

            $_SESSION['user']['perfil'] = $perfil;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['nome'] = $nome;
            $_SESSION['user']['sobrenome'] = $sobrenome;
            $_SESSION['user']['cidade'] = $cidade;
            $_SESSION['user']['estado'] = $estado;
            $_SESSION['user']['orgao'] = $orgao;
            $_SESSION['user']['cargo'] = $cargo;
            $_SESSION['user']['criada_em'] = (new DateTime("now"))->format($GLOBALS['formato-data-hora']);
            $_SESSION['user']['duracao'] = $duracao;
            $_SESSION['user']['renovado'] = 1;
        }
    }

    public static function registrarUsuario(Usuario $usuario, bool $lembrar){
        $expira = $lembrar ? 86400 : $GLOBALS['tempo-sessao'];
        self::destruirSessao(); // destroi a sessão atual seja de visitante ou não
        self::iniciarSessao($usuario, $expira);
    }

    public static function estaOnline() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']['perfil']) || $_SESSION['user']['perfil'] === "visitante"){
            return false;
        }

        // captura a data de criação da sessão
        $data_criacao = new DateTime($_SESSION['user']['criada_em']);

        // captura a data atual
        $data_atual = new DateTime();

        // calcula a data de expiração da sessão
        $data_expiracao = clone $data_criacao;
        $duracao = $_SESSION['user']['duracao'];
        $data_expiracao->modify("+{$duracao} seconds");

        // se a data de hoje for menor que a data de expiração
        return $data_atual < $data_expiracao;
    }

    public static function destruirSessao(): void { 
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset(); 
        session_destroy(); 
    } 

    public static function obterUsuario(): Usuario {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return UtilService::criarUsuario($_SESSION['user']);
    }

    public static function obterTentativasLogin() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [];
        }
        if (!array_key_exists("contar-login", $_SESSION['user'])){
            $_SESSION['user']['contar-login'] = 0;
        }
        // Correção: Retorna o valor atual sem sobrescrevê-lo com 0
        return $_SESSION['user']['contar-login'];
    }

    public static function resetarTentativasLogin(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['user']) && array_key_exists("contar-login", $_SESSION['user'])) {
            $_SESSION['user']['contar-login'] = 0;
        }
    }

    public static function editarTempoEspera(int $tempo_minutos){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['espera'] = $tempo_minutos;
    }

    public static function espera() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['espera'])) {
            return false;
        }

        if (!array_key_exists('inicio', $_SESSION) || $_SESSION['inicio'] === "null"){
            $_SESSION['inicio'] = (new DateTime("now"))->format($GLOBALS['formato-data-hora']);
        }

        $tempo_espera = $_SESSION['espera'];
        $hora_inicio = new DateTime($_SESSION['inicio']);
        $hora_atual = new DateTime("now");
        
        $hora_fim = clone $hora_inicio;
        $hora_fim->modify("+{$tempo_espera} minutes");

        if ($hora_fim > $hora_atual){
            // Ainda está no tempo de espera
            return true;
        }
        return false;
    }

    public static function resetarTempoInicio(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['inicio'] = "null";
    }

    public static function obterAtributoUsuario(string $atributo){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!array_key_exists("user", $_SESSION)) return false;
        if (!array_key_exists($atributo, $_SESSION['user'])) return false;
        return $_SESSION['user'][$atributo];
    }

    public static function adicionarTentativa(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [];
        }
        if (!isset($_SESSION['user']['contar-login'])) {
            $_SESSION['user']['contar-login'] = 0;
        }
        $_SESSION['user']['contar-login'] += 1;
    }
}