<?php

class AutenticarService {
    public static function autenticar(Usuario $usuario){
        // variavel de controle para tentativas de login
        $tentativas = SessaoService::obterTentativasLogin();
        $email = $usuario->getEmail();
        $senha = $usuario->obterSenha();
        $nome = "''";
        $sobrenome = "''"; 
        $perfil = "''";

        // limite de tentativas de login 
        if ($tentativas > 10){
            // limite de tentativas expirou 
            SessaoService::destruirSessao();
            return RespostaProcesso::resposta(
                mensagem: "Limite de tentativas de login excedido", resposta: false
            );
        }

        $repositorio = 
    }
}