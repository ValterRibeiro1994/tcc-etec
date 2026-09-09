<?php

class LoginController {
    public function index(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado");
        }

        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::respostaProcesso("app/view/paginas/login.html", true, formato: "text/html");
        } else if ($requisicao['metodo'] == "POST"){

        }
    }

    private function logar(array $requisicao){
    }

    private function status(array $requisicao){
        if ($requisicao['metodo'] == "GET"){
            if (SessaoController::usuarioAtivo()){
                return RespostaProcesso::respostaProcesso("Sessão ativa", true, ['token'=>$_SESSION['token']]);
            } else {
                return RespostaProcesso::respostaProcesso("Sessão fechada", false);
            }
        }
    }
}