<?php

class HomeController {
    public function index(array $requisicao) {
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::respostaProcesso("app/view/paginas/index-valter.html", status: true, formato: "text/html");
        }

        return RespostaProcesso::respostaProcesso("Método invalido para home");
    }
}