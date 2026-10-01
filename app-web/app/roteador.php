<?php

class Roteador {
    private array $resposta;
    public function __construct(){
        
        $metodo = $_SERVER['REQUEST_METHOD'];

        // verifica o método requisitado
        if ($metodo == "GET"){
            
            // avisa o controller o método sendo chamado
            $_GET['metodo'] = "GET";

            // chama o processo GET 
            $this->resposta = $this->getProcess($_GET);
        } else if ($metodo == "POST"){
            $this->resposta = $this->postProcesso($_POST);
        } else {
            throw new Exception("Método $metodo inválido");
        }
    } 

    private function getProcess(array $dados_get): array {
        $classe = "home";
        $metodo = "index";

        if (empty($dados_get)) return $this->chamarController(requisicao: $dados_get);
        if (!array_key_exists("url", $dados_get)) return $this->chamarController(requisicao: $dados_get);

        $partes_url = explode("/", $dados_get['url']);

        // se não for requisitada nada depois do dominio, considere chamar a HomeController
        if (count($partes_url) == 0) return $this->chamarController(requisicao: $dados_get);

        // se uma classe especifica foi definida, salva ela
        $classe = $partes_url[0];

        // remove a classe do array
        array_shift($partes_url);

        // verifica se foi especificado algum método
        if (count($partes_url) == 0) return $this->chamarController($classe, requisicao: $dados_get);

        // se um método foi especificado, salve o método
        $metodo = $partes_url[0];

        // chame o controller especificado
        return $this->chamarController($classe, $metodo, $dados_get);
        
    }

    private function postProcesso(array $dados_post){
        $partes_uri = explode("/", $_SERVER['REQUEST_URI']);
        
        // o primeiro espaço vem vazio, tanto em localhost quanto no infinity free
        array_shift($partes_uri);

        // esse trecho é útil apenas para localhost
        if ($partes_uri[0] == "app-web"){
            array_shift($partes_uri);
        }

        // checa se a classe foi enviada
        if (empty($partes_uri[0])){
            return $this->chamarController(requisicao: $dados_post);
        }

        // captura a classe enviada
        $classe = $partes_uri[0];

        // checa se o método foi enviado
        array_shift($partes_uri);
        if (count($partes_uri) == 0){
            return $this->chamarController($classe, requisicao: $dados_post);
        }

        if (empty($partes_uri[0])){
            return $this->chamarController($classe, requisicao: $dados_post);
        }

        $metodo = $partes_uri[0];
        return $this->chamarController($classe, $metodo, $dados_post);
    }

    private function chamarController(string $classe = "home", string $metodo = "index", array $requisicao = []): array {
        $classe = ucfirst($classe); // primeira letra maiuscula para chamar classe
        $classe .= "Controller";

        if (!class_exists($classe)) throw new Exception("Classe '$classe' não indentificada no roteador");

        // instancia o controller solicitado
        $controller = new $classe();

        if (!method_exists($controller, $metodo)) throw new Exception("Metodo '$metodo' não indentificado para a Classe $classe no roteador");
        if (!array_key_exists("metodo", $requisicao)) throw new Exception("Chave para método não enviado para roteador");
        return $controller->$metodo($requisicao);

    }

    public function getResposta(): array {
        return $this->resposta;
    }
}