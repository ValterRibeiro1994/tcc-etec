<?php

class TesteController {
    public function index(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/index.html"
        );
    }

    public function cadastro(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/selecionar-cadastro.html"
        );
    }
    
    public function cadastromunicipe(array $requisicao){
        if ($requisicao['metodo'] ==  "GET"){
            return RespostaProcesso::resposta(
                mensagem: "Get ojk", resposta: true,
                formato: "html",
                pagina: "app/view/paginas/cadastro-denunciante.html"
            );
        }

        if ($requisicao['metodo'] == "POST"){
            return RespostaProcesso::resposta(
                mensagem: "POST teste", resposta: true,
                formato: "json",
                pagina: "app/view/paginas/home-mural.html"
            );
        }

        return RespostaProcesso::resposta(
            "requisicao invalida", false, dados: $requisicao
        );
    }

    public function homemural(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/home-mural.html"
        );
    }

    public function login(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/login.html"
        );
    }

    public function perfil(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/perfil.html"
        );
    }

    public function criardenuncia(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/criar-denuncia.html"
        );
    }
}