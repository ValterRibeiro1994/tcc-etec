<?php

class HomeController {
    public function index(array $requisicao) {
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
<<<<<<< Updated upstream
            // A página principal do site deve ser chamada
            $dados = [
                "pagina" => "app/view/paginas/index.html",
                "tipo-resposta"=>"text/html"
            ];
=======
            return RespostaProcesso::respostaProcesso("app/view/paginas/perfil.html", status: true, formato: "text/html");
        }
>>>>>>> Stashed changes

            $mensagem = "
            O javascript deve guardar o token caso o usuario ja tenha feito login,
            existem varias questões sobre a home que devem ser conversadas com os front-end
            ";
            return RespostaProcesso::respostaProcesso($mensagem, true, $dados);
        }
    }
}