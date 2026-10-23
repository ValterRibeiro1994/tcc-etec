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

    public function sobre(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/sobre-nos.html"
        );
    }

    public function criardenuncia(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/criar-denuncia.html"
        );
    }

    public function informacoes(array $requisicao){
        return RespostaProcesso::resposta(
            mensagem: "nome da pagina", resposta: true, 
            formato: "html", pagina: "app/view/paginas/informacoes-legais.html"
        );
    }
    


    // TEMPORÁRIO: APAGAR antes de publicar o site. Gera token sem exigir senha.
public function gerartoken(array $requisicao){
    // só funciona no seu computador (localhost)
    if (!in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
        return RespostaProcesso::resposta("Disponível apenas em localhost");
    }

    $usuario = new Usuario(new DadosPessoais(
        perfil: new Perfil("municipe"),
        nome: new Nome("Teste"),
        sobrenome: new Sobrenome("Usuario"),
        email: new Email("teste@relatesbc.com")
    ));

    // ADICIONADO: o TokenService agora grava cidade e estado no token dos usuários logados
    $usuario->setEndereco(new Endereco("SAO BERNARDO DO CAMPO", "SP"));

    return TokenService::criarToken($usuario, 3600); // vale por 1 hora
}
}