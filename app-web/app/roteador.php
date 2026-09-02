<?php

class Roteador {
    private array $resposta_servidor;

    public function __construct(array $requisicao)
    {
        $this->resposta_servidor = $this->chamarController($requisicao);
    }

    public function getResposta(){
        return $this->resposta_servidor;
    }

    private function chamarController(array $requisicao){
        // valida as chaves da requisição
        $validar_requisicao = $this->validarRequisicao($requisicao);
        if (!$validar_requisicao['resposta']){
            return $validar_requisicao;
        }

        // inicia as variaveis de iniciação
        $classe = "home";
        $metodo = "index";

        if ($requisicao['metodo'] == "GET"){
            
            // captura os dados da requisição
            $dados = $requisicao['dados'];
            $url = $dados['url'] ?? "";
            
            // define o controller e o método a ser chamado
            if (!empty($url)){
                $partes_url = explode("/", $url);
                // captura a classe
                $classe = $partes_url[0];
                
                // verifica se o metodo foi enviado
                array_shift($partes_url); // remove o primeiro elemento do array
                if (count($partes_url) > 0){
                    // verifica se foi passado classe e parametros sem o método
                    if (!str_contains("?", $partes_url[0])){
                        $metodo = $partes_url[0];
                    } 
                }
            }
        }
        
        // chama o controller para obter o serviço solicitado
        $classe = ucfirst($classe);
        $classe .= "Controller";
        if (!class_exists($classe)) {
            return ['resposta'=>false, 'mensagem'=>"Classe '$classe' não indentificada"];
        }

        // instancia o controller solicitado
        $controller = new $classe();
        if (!method_exists($controller, $metodo)){    
            return ['resposta'=>false, 'mensagem'=>"Metodo '$metodo' não indentificado"];
        }

        // chama o método solicitado
        return $controller->$metodo($dados);
        }
    
        private function validarRequisicao(array $requisicao){
        // contar o numero de chaves recebidas 
        $chaves_requisicao = count($requisicao);

        // chaves permitidas 
        $chaves_permitidas = ['uri', 'metodo', 'dados'];

        // se os numeros de chave recebido for diferente do numero de chaves registrado no sistema bloqueia a operação
        if ($chaves_requisicao != count($chaves_permitidas)){
            return ['resposta'=>false, 'mensagem'=>"Erro: Chaves invalidas"];
        }

        // compare as chaves registradas com as chaves recebidas
        foreach ($chaves_permitidas as $chave ) {
            // verifica se existe as chaves determinadas no sistema
            if (!array_key_exists($chave, $requisicao)){
                return ['resposta'=>false, 'mensagem'=> "Erro: Chave '$chave' não existe"];
            }

            //captura a chave recebida do cliente
            $chave_cliente = $requisicao[$chave];
            
            // dados é a unica variavel que pode vir vazia
            if ($chave == "dados"){
                continue;
            }

            // verifica se a chave não esta vazia
            if (empty($chave_cliente)){
                return ['resposta'=>false, 'mensagem'=>"Erro: Chave '$chave' Vazia"];
            }

        }

        return ['resposta'=>true, 'mensagem'=>"Requisição validada"];
    }
}