<?php

class TesteController {
    public function index(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/index.html"
        );
    }
}