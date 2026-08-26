<?php

class Roteador {
    private array $requisicao_cliente;
    private array $resposta_servidor;

    public function __construct($requisicao)
    {
        if (!$this->validarRequisicao($requisicao)){
            throw new Exception("Error Processing Request", 1);
        }

        $this->chamarController($requisicao);

    }
    private function chamarController(array $requisicao){
        // quebrar o URI em partes
        $partes_uri = explode("/", $requisicao['uri']);
        var_dump($partes_uri);
    }

    private function validarRequisicao(array $requisicao){
        // contar o numero de chaves recebidas 
        $chaves_requisicao = count($requisicao);

        // chaves permitidas 
        $chaves_permitidas = ['uri', 'metodo', 'dados'];

        // se os numeros de chave recebido for diferente do numero de chaves registrado no sistema bloqueia a operação
        if ($chaves_requisicao != count($chaves_permitidas)){
            echo("Manipularam a requisição !!!");
            exit();
        }

        // compare as chaves registradas com as chaves recebidas
        foreach ($chaves_permitidas as $chave ) {
            if (array_key_exists($chave, $requisicao)){
                $chave_cliente = $requisicao[$chave];
            } else {
                echo("chaves de requisição foram manipuladas");
                exit();
            }

            // dados é a unica variavel que pode vir vazia
            if ($chave == "dados"){
                continue;
            }

            if (empty($chave_cliente)){
                echo("Chave de requisição vazia");
                exit();
            }

        }

        return true;
    }
}