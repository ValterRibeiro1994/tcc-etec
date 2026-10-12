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

    public static function obterUsuario() {
        $resposta = SessaoController::estaConectado();
        // se não tem sessão aberta não tem usuario
        if (!$resposta['resposta']) return $resposta;
        
        // tenta encontrar o perfil do usuario
        $resposta = SessaoController::obterAtributoUsuario("perfil");
        if (!$resposta['resposta']) return $resposta;
        $perfil = $resposta['mensagem'];
        
        // cria o usuario
        $usuario = new Usuario();
        $usuario->setDadosPessoais(new DadosPessoais());
        if ($perfil == "visitante"){
            $usuario->setNome(new Nome("visitante"));
            $usuario->setSobrenome(new Sobrenome("visitante"));
            $usuario->setEmail(new Email("visitante@gmail.com"));
            $usuario->setPerfil("visitante");
        } else {
            $usuario->setPerfil($_SESSION['user']['perfil']);
            $usuario->setNome(new Nome($_SESSION['user']['nome']));
            $usuario->setSobrenome(new Sobrenome($_SESSION['user']['sobrenome']));
            $usuario->setEmail(new Email($_SESSION['user']['email']));
            }
            
            if ($usuario->getPerfil() == "visitante"){
                return RespostaProcesso::resposta("Visitante criado com sucesso", true, $usuario->getArray());
            }
                
            $usuario->setEndereco(new Endereco(
                cidade: $_SESSION['user']['cidade'],
                estado: $_SESSION['user']['estado']
            ));
        if ($usuario->getPerfil() == "municipe"){
            // ele só é um municipe depois do login
            $resposta = self::estaConectado();
            if (!$resposta['resposta']){
                self::encerrarSessao();
                return RespostaProcesso::resposta("Usuario desconectado", dados: $resposta);
                }
        return RespostaProcesso::resposta("Municipe criado com sucesso", true, $usuario->getArray());
        }

        if ($usuario->getPerfil() == "representante"){
            $usuario->setPrefeitura(new Prefeitura(
                $_SESSION['user']['orgao'],
                $_SESSION['user']['cargo'] 
            ));
            return RespostaProcesso::resposta("Representante criado com sucesso", true, $usuario->getArray());
        }
        
        self::encerrarSessao();
        return RespostaProcesso::resposta("Perfil inválido: Usuario desconectado", false);
    } 


    public static function registrarSessao(Usuario $usuario, bool $lembrar){
        try { 
            $_SESSION['user']['email'] = $usuario->getEmail(); 
            $_SESSION['user']['nome'] = $usuario->getNome(); 
            $_SESSION['user']['sobrenome'] = $usuario->getSobrenome(); 
            $_SESSION['user']['perfil'] = $usuario->getPerfil();
    
            $_SESSION['user']['lembrar'] = $lembrar; 
            $_SESSION['user']['ultimo_acesso'] = date("Y-m-d H:i:s"); 
            if ($_SESSION['user']['perfil'] == "visitante") return RespostaProcesso::resposta("Visitante registrado", true);
            
            $_SESSION['user']['estado'] = $usuario->getEstado(); 
            $_SESSION['user']['cidade'] = $usuario->getCidade();
            if ($_SESSION['user']['perfil'] == "municipe") return RespostaProcesso::resposta("Municipe registrado", true);
            
            if ($_SESSION['user']['perfil'] == "representante") {
                $_SESSION['user']['orgao'] = $usuario->getOrgao(); 
                $_SESSION['user']['cargo'] = $usuario->getCargo();
                return RespostaProcesso::resposta("Representante registrado", true);
            }
    
            self::encerrarSessao();
            return RespostaProcesso::resposta("Perfil invalido");

        } catch (Exception $erro) {
            self::encerrarSessao();
            $dados = RespostaProcesso::salvarErro($erro);
            return RespostaProcesso::resposta(mensagem: $erro->getMessage(), dados: $dados);
        }
        
    }

    public static function encerrarSessao(): void { 
        self::iniciarSessao(); 
        session_unset(); 
        session_destroy(); 
    } 

    public static function estaConectado() { 
        self::iniciarSessao(); 

        // Verifica se a sessão existe
        if (!array_key_exists("user", $_SESSION)) {
            return RespostaProcesso::resposta("Usuario desconectado"); 
        }

        $data_acesso = new DateTime($_SESSION['user']['ultimo_acesso']); 
        $data_atual = new DateTime(); 
        
        // se marcar lembrar a sessão se mantem por 1 hora, se não 10 minutos
        $tempo_expiracao = $_SESSION['user']['lembrar'] ? '+1 day' : '+10 minutes';
        
        $data_expira = clone $data_acesso; 
        $data_expira->modify($tempo_expiracao); 

        if ($data_atual > $data_expira) { 
            self::encerrarSessao(); 
            return RespostaProcesso::resposta("Usuario desconectado"); 
        } 

        // renova o horario
        $_SESSION['user']['ultimo_acesso'] = date("Y-m-d H:i:s");
        return RespostaProcesso::resposta("Usuario conectado", true); 
    } 

    public static function obterAtributoUsuario(string $atributo) {
        if (!array_key_exists($atributo, $_SESSION['user'])) 
            return RespostaProcesso::resposta("Atributo $atributo não armazenado em sessão");
        return RespostaProcesso::resposta($_SESSION['user'][$atributo], true);
    }
}
