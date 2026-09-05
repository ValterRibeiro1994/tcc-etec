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
                "pagina" => "/app/view/paginas/denunciante.html"
            ];
            return RespostaProcesso::respostaProcesso("Criar página principal para o denunciante", true, $dados);
        }
''
        return RespostaProcesso::respostaProcesso("POST não esperado para essa função ", dados: $requisicao);
    }

    public function cadastrar(array $requisicao){
        if (!array_key_exists("metodo", $requisicao)){
            return RespostaProcesso::respostaProcesso("Método não enviado", dados: $requisicao);
        }

        return RespostaProcesso::respostaProcesso("Desenvolver pagina para cadastrar denunciante", dados: $requisicao);
    }
}