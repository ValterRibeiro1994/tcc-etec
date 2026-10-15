<?php

class SessaoService {

    private static function iniciarSessao() { 
        if (session_status() === PHP_SESSION_NONE) { 
            ini_set('session.cookie_lifetime', (int) $GLOBALS['tempo-sessao']); 
            ini_set('session.cookie_httponly', 1); 
            ini_set('session.cookie_secure', 0); // Mude para 1 em produção (HTTPS)
            ini_set('session.use_only_cookies', 1); 
            ini_set('session.cookie_samesite', 'Lax'); 
            session_start(); 
        } 
    }

    
    public static function estaOnline() {
        self::iniciarSessao();
        
        // Verifica se o usuário está logado
        if (!isset($_SESSION['user']) || !isset($_SESSION['user']['perfil'])){
            return false;
        }

        if ($_SESSION['user']['perfil'] === "visitante"){
            return false;
        }

        $ultimo_acesso = $_SESSION['user']['ultimo_acesso'];
        $expira = $_SESSION['user']['expira_em'] ?? 0; // tempo em segundos ou minutos dependendo da sua regra

        if (!$ultimo_acesso) {
            return false;
        }

        $data_acesso = new DateTime($ultimo_acesso);
        $data_atual = new DateTime();
        
        // Clona para não corromper o objeto original do último acesso ao usar modify
        $data_expira = clone $data_acesso;
        $data_expira->modify("+{$expira} seconds"); // Ajuste conforme sua regra de segundos/minutos

        if ($data_atual > $data_expira){
            self::destruirSessao();
            return false; // Expirou
        }
        
        return true;
    }

    public static function registrarUsuario(Usuario $usuario, bool $lembrar) {
        try {
            self::iniciarSessao();
            $perfis_autorizados = ["visitante", "municipe", "representante"];
            
            if (!UtilService::validarPerfil($perfis_autorizados, $usuario->getPerfil())){
                return RespostaProcesso::resposta(
                    mensagem: "Erro Perfil de usuario não permitido", resposta: false,
                    dados: $usuario->getArray()['dados'] ?? []
                );
            }

            $resposta = $usuario->getArray();
            if (!$resposta['resposta']){
                return RespostaProcesso::resposta(
                    mensagem: "Erro ao registrar sessão: " . $resposta['mensagem'], resposta: false,
                    dados: $resposta['dados']
                );
            }

            $dados_usuario = $resposta['dados'];
            $_SESSION['user'] = $dados_usuario;

            if ($lembrar){
                $expira = 86400; // um dia
            } {
                $expira = (int) $GLOBALS['tempo-sessao'];
            }

            $_SESSION['user']['expira_em'] = $expira;
            $_SESSION['user']['ultimo_acesso'] = (new DateTime())->format($GLOBALS['formato-data-hora']);
            $_SESSION['user']['contar-login'] = 0;

            return RespostaProcesso::resposta(
                mensagem: "Sessão registrada para " . ($dados_usuario['nome'] ?? 'Usuário'), resposta: true,
                dados: $dados_usuario
            );

        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta(
                mensagem: "Erro criar Token: " . ($dados['mensagem'] ?? $erro->getMessage()), resposta: false,
                dados: $dados
            );
        }
    }

    public static function destruirSessao(): void { 
        self::iniciarSessao(); 
        session_unset(); 
        session_destroy(); 
    } 

    public static function obterUsuario() {
        self::iniciarSessao();
        try {
            if (!isset($_SESSION['user'])) {
                return RespostaProcesso::resposta(mensagem: "Nenhum usuário na sessão", resposta: false, dados: []);
            }

            $dados = [
                "nome" => $_SESSION['user']['nome'] ?? '',
                "sobrenome" => $_SESSION['user']['sobrenome'] ?? '',
                "email" => $_SESSION['user']['email'] ?? '',
                "perfil" => $_SESSION['user']['perfil'] ?? '',
            ];

            $perfil = $_SESSION['user']['perfil'] ?? '';

            if ($perfil === "municipe" || $perfil === "representante"){
                $dados['cidade'] = $_SESSION['user']['cidade'] ?? '';
                $dados['estado'] = $_SESSION['user']['estado'] ?? '';
            }

            if ($perfil === "representante"){
                $dados['cargo'] = $_SESSION['user']['cargo'] ?? '';
                $dados['orgao'] = $_SESSION['user']['orgao'] ?? '';
            }

            return RespostaProcesso::resposta(
                mensagem: "Usuario recuperado com sucesso", resposta: true,
                dados: $dados
            );

        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao recuperar sessão de usuario ativo: " . $erro->getMessage(), resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }

    public static function obterTentativasLogin() {
        self::iniciarSessao();
        
        if (!isset($_SESSION['user'])) {
            $_SESSION['user'] = [];
        }

        if (!array_key_exists("contar-login", $_SESSION['user'])){
            $_SESSION['user']['contar-login'] = 0;
        }

        return $_SESSION['user']['contar-login'];
    }

    public static function resetarTentativasLogin(){
        self::iniciarSessao();
        if (isset($_SESSION['user'])) {
            $_SESSION['user']['contar-login'] = 0;
        }
    }

    public static function editarTempoEspera(int $tempo_minutos){
        self::iniciarSessao();
        $_SESSION['espera'] = $tempo_minutos;
    }

    public static function espera() {
        self::iniciarSessao();

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
        self::iniciarSessao();
        $_SESSION['inicio'] = "null";
    }

    public static function obterAtributoUsuario(string $atributo){
        if (!array_key_exists("user", $_SESSION)) return false;
        if (!array_key_exists($atributo, $_SESSION['user'])) return false;
        return $_SESSION['user'][$atributo];
    }

    public static function rastrearUsuario(array $resposta){
        self::iniciarSessao();
        $arquivo = $_SERVER['SCRIPT_FILENAME'];
        $metodo = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $_SESSION['seg']['acoes'][] = [
            "usuario"=> $_SESSION['user'] ?? "Primeiro Acesso - não registrado",
            "arquivo"=> $arquivo,
            "metodo"=> $metodo,
            "uri"=> $uri,
            "mensagem"=>$resposta['mensagem'],
            "status-processo"=>$resposta['resposta'],
            "dados"=> $resposta['dados'],
            "data-hora" => (new DateTime("now"))->format($GLOBALS['formato-data-hora']),
        ];
    }
}