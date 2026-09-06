<?php

class Roteador {
    private array $resposta;
    public function __construct(){
        
        // verifica o método requisitado
        if ($_SERVER['REQUEST_METHOD'] == "GET"){
            
            // avisa o controller o método sendo chamado
            $_GET['metodo'] = "GET";

            // chama o processo GET 
            $this->resposta = $this->getProcess($_GET);
            
        } else if ($_SERVER['REQUEST_METHOD'] == "POST"){
            $this->resposta = $this->postProcesso($_POST);
        } else {
            $this->resposta = RespostaProcesso::respostaProcesso("Requisição Invalida");
        }
    } 

    private function getProcess(array $dados_get): array {
        $classe = "home";
        $metodo = "index";

        if (!empty($dados_get)){
            if (!array_key_exists("url", $dados_get)){
                return $this->chamarController(requisicao: $dados_get);
            }

            $partes_url = explode("/", $dados_get['url']);

            // se não for requisitada nada depois do dominio, considere chamar a HomeController
            if (count($partes_url) == 0){
                return $this->chamarController(requisicao: $dados_get);
            }

            // se uma classe especifica foi definida, salva ela
            $classe = $partes_url[0];

            // remove a classe do array
            array_shift($partes_url);

            // verifica se foi especificado algum método
            if (count($partes_url) == 0){
                // se não foi considere o método index
                return $this->chamarController($classe, requisicao: $dados_get);
            }

            // se um método foi especificado, salve o método
            $metodo = $partes_url[0];

            // chame o controller especificado
            return $this->chamarController($classe, $metodo, $dados_get);

        } else {
            // se não teve nada requisitado, considere chamar homeController
            return $this->chamarController();
        }
        
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
        try {
            $classe = ucfirst($classe); // primeira letra maiuscula para chamar classe
            $classe .= "Controller";
            if (!class_exists($classe)){
                    return RespostaProcesso::respostaProcesso("Classe '$classe' não indentificada");
            }

            // instancia o controller solicitado
            $controller = new $classe();
            if (!method_exists($controller, $metodo)){    
                return RespostaProcesso::respostaProcesso("Metodo '$metodo' não indentificado");
            }

            return $controller->$metodo($requisicao);
        } catch (Exception $error) {
            $mensagem = "Erro: " . $error->getMessage();
            $arquivo = $error->getFile();
            $codigo = $error->getCode();
            $linha = $error->getLine();
            $erro = [
                'arquivo'=>$arquivo,
                "codigo"=>$codigo,
                "linha"=>$linha
            ];

            return RespostaProcesso::respostaProcesso($mensagem, dados: $erro);
        }

    }
    public function getResposta(): array {
        return $this->resposta;
    }
}