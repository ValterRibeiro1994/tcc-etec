<?php


class DenuncianteController {
    public function index(array $requisicao){
        // garanta que o método tenha sido definido
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            // A página principal do denunciante deve ser chamada
            $dados = [
                "pagina" => "/app/view/paginas/denunciante.html",
                "tipo-resposta"=>"text/html"
            ];
            return RespostaProcesso::respostaProcesso("Criar página principal para o denunciante", true, $dados);
        }

        return RespostaProcesso::respostaProcesso("POST não esperado para essa função ", dados: $requisicao);
    }

    public function cadastrar(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        if ($requisicao['metodo'] == "GET"){
            // a pagina para cadastrar o denunciante deve ser exibida
            $dados = [
                "pagina"=>"app/view/paginas/cadastro-denunciante.html",
                "tipo-resposta"=> "text/html"
            ];

            return RespostaProcesso::respostaProcesso("Chamar cadastro denunciante...", true, $dados);
        } else {
            if (!array_key_exists("_metodo", $requisicao)){
                return RespostaProcesso::respostaProcesso("Chave com método obrigatorio", dados: $requisicao);
            }

            $dados = [
                'tipo-resposta'=>"application/json",
                'resposta-processo' => RespostaProcesso::respostaProcesso("Desenvolver processo para cadastro de denunciante", dados: $requisicao)
            ];
            return RespostaProcesso::respostaProcesso("Em andamento", dados: $dados);
        }

        return RespostaProcesso::respostaProcesso("Desenvolver pagina para cadastrar denunciante", dados: $requisicao);
    }
}