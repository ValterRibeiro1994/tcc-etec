<?php

class UserController {
    public function index(array $requisicao){
        return RespostaProcesso::respostaProcesso("app/view/paginas/selecionar-cadastro.html", true, formato: "text/html");
    }

    public function representante(array $requisicao){
        return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", true, formato: "text/html");
    }
}