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

    private function logarUsuario(array $requisicao){
    }
}