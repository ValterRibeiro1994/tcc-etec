<?php

class HomeController {
    public function index(array $requisicao) {
        if ($requisicao['metodo'] == "GET") return RespostaProcesso::respostaProcesso("app/view/paginas/perfil.html", status: true, formato: "html");
        throw new Exception("Método invalido para Home");
    }
}