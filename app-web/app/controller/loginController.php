<?php

class LoginController {
    public function index(array $requisicao) {
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            // A página principal do site deve ser chamada
            $dados = [
                "pagina" => "app/view/paginas/login.html",
                "tipo-resposta"=>"text/html"
            ];

            $mensagem = "
            O js deve guardar um token de login caso seja efetuado, debe ter um metodo post para tal requisição
            ";
            return RespostaProcesso::respostaProcesso($mensagem, true, $dados);
        }
    }
}