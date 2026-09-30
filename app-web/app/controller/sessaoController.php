<?php 

class SessaoController { 
    
    private static function iniciarSessao() { 
        if (session_status() === PHP_SESSION_NONE) { 
            ini_set('session.cookie_lifetime', 86400); 
            ini_set('session.cookie_httponly', 1); 
            ini_set('session.cookie_secure', 0); // Mude para 1 em produção (HTTPS)
            ini_set('session.use_only_cookies', 1); 
            ini_set('session.cookie_samesite', 'Lax'); 
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

        if ($usuario->getPerfil() == "representante"){ 
            $_SESSION['user']['orgao'] = $usuario->getOrgao(); 
            $_SESSION['user']['cargo'] = $usuario->getCargo(); 
        } 

        $_SESSION['user']['lembrar'] = $lembrar; 
        $_SESSION['user']['ultimo_acesso'] = date("Y-m-d H:i:s"); // Mudado para controlar inatividade
    } 

    public static function encerrarSessao(): void { 
        self::iniciarSessao(); 
        session_unset(); 
        session_destroy(); 
    } 

    public static function estaConectado(): bool { 
        self::iniciarSessao(); 

        // Verifica se a sessão existe
        if (!array_key_exists("user", $_SESSION) || !isset($_SESSION['user']['ultimo_acesso'])) {
            return false; 
        }

        $data_acesso = new DateTime($_SESSION['user']['ultimo_acesso']); 
        $data_atual = new DateTime(); 
        
        // Se marcou "lembrar", a sessão dura 1 dia. Se não  expira em 5 minutos.
        $tempo_expiracao = $_SESSION['user']['lembrar'] ? '+1 days' : '+5 minutes';
        
        $data_expira = clone $data_acesso; 
        $data_expira->modify($tempo_expiracao); 

        if ($data_atual > $data_expira) { 
            self::encerrarSessao(); 
            return false; 
        } 

        // Atualiza o último acesso para renovar o tempo de atividade do usuário
        $_SESSION['user']['ultimo_acesso'] = date("Y-m-d H:i:s");
        return true; 
    } 

    public static function obterAtributoUsuario(string $atributo): string {
        if (!array_key_exists($atributo, $_SESSION['user'])) throw new Exception("Atributo $atributo não armazenado em sessão");
        return $_SESSION['user'][$atributo];
    }
}
