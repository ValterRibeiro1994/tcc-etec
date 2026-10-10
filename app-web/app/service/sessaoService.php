<?php

class SessaoService {

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
    
    public static function estaOnline() {
        self::iniciarSessao();
        if (!isset($_SESSION['user']['perfil'])){
            return false;
        }

        $ultimo_acesso = $_SESSION['user']['ultimo_acesso'];
        $expira = $_SESSION['user']['expira_em']; // int tempo em segundos

        $data_acesso = new DateTime($ultimo_acesso);
        $data_atual = new DateTime();
        $data_expira = $data_acesso->modify("$expira seconds");
        if ($data_atual > $data_expira){
            return false;
        }
        return true;
    }

    public static function registrarUsuario(Usuario $usuario, bool $lembrar) {
        try {
            $perfis_autorizados = [
                "visitante", "municipe", "representante"
            ];
            
            if (!UtilService::validarPerfil($perfis_autorizados, $usuario->getPerfil())){
                return RespostaProcesso::resposta(
                    mensagem: "Erro Perfil de usuario não permitido", resposta: false,
                    dados: $usuario->getArray()['dados']
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
            } else {
                $expira = 600; // 10 minutos
            }

            $_SESSION['user']['expira_em'] = $expira;
            $_SESSION['user']['ultimo_acesso'] = new DateTime();
            return RespostaProcesso::resposta(
                mensagem: "Sessão registrada para " . $dados_usuario['nome'], resposta: true,
                dados: $dados_usuario
            );

        } catch (Exception $erro) {
            $dados = RespostaProcesso::salvarErro($erro);
                return RespostaProcesso::resposta(
                    mensagem: "Erro criar Token: " . $dados['mensagem'], resposta: false,
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
        try {
            $dados = [
                "nome" => $_SESSION['user']['nome'],
                "sobrenome" => $_SESSION['user']['sobrenome'],
                "email" => $_SESSION['user']['email'],
                "perfil" => $_SESSION['user']['perfil'],
            ];

            if ($_SESSION['user']['perfil'] == "municipe" || $_SESSION['user']['perfil'] == "representante"){
                $dados['cidade'] = $_SESSION['user']['cidade'];
                $dados['estado'] = $_SESSION['user']['estado'];
           
            }

            if ($_SESSION['user']['perfil'] == "representante"){
                $dados['cargo'] = $_SESSION['user']['cargo'];
                $dados['orgao'] = $_SESSION['user']['orgao'];
            }

            return RespostaProcesso::resposta(
                mensagem: "Usuario recuperado com sucesso", resposta: true,
                dados: $dados
                );



        } catch (Exception $erro) {
            return RespostaProcesso::resposta(
                mensagem: "Erro ao recuperar sessão de usuario ativo: " .  $erro->getMessage(), resposta: false,
                dados: RespostaProcesso::salvarErro($erro)
            );
        }
    }
}