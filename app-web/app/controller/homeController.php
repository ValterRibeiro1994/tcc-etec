<?php

class HomeController {
    public function index(array $requisicao) {

        if ($requisicao['metodo'] == "GET"){
            return RespostaProcesso::resposta(
                mensagem: "Sucesso", resposta: true,
                formato: "html", pagina: "app/view/paginas/index.html"
            );
        }

        return RespostaProcesso::resposta(
            mensagem: "Controller: Método não permitido para home", resposta: false,
            dados: $requisicao, formato: "json"
        );
    }
}