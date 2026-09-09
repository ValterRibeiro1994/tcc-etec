<?php

class UserController {
    public function index(array $requisicao){
        /** Verificar se existe um token vindo do cliente
         * if (metoodo == get && array_key_exists(get[token])):
         *      if validarToken();
         *          exibirPaginaHome
         *     
         * 
         */
        return RespostaProcesso::respostaProcesso("app/view/paginas/selecionar-cadastro.html", true, formato: "text/html");
    }

    public function representante(array $requisicao){
        return RespostaProcesso::respostaProcesso("app/view/paginas/cadastro-denunciante.html", true, formato: "text/html");
    }
}