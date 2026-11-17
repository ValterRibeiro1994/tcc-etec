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
            // todo metodo post deve ter um token de autenticacao
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

    private function postProcesso(array $dados_post): array {
        // A regra .htaccess envia a rota em ?url=... mesmo nos envios POST.
        // Removemos a query string e a pasta-base, sem depender de /app-web/.
        $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim($GLOBALS['raiz-app'], '/');
        if ($base !== '' && str_starts_with($caminho, $base . '/')) {
            $caminho = substr($caminho, strlen($base));
        }
        $caminho = trim($caminho, '/');
        $partes = $caminho === '' ? [] : explode('/', $caminho);
        $classe = $partes[0] ?? 'home';
        $metodo = $partes[1] ?? 'index';
        return $this->chamarController($classe, $metodo, $dados_post);
    }

    private function chamarController(string $classe = "home", string $metodo = "index", array $requisicao = []): array {
        $classe = ucfirst($classe); // primeira letra maiuscula para chamar classe
        $classe .= "Controller";

        if (!class_exists($classe)) throw new Exception("Classe '$classe' não indentificada no roteador");

        // instancia o controller solicitado
        $controller = new $classe();

        if (!method_exists($controller, $metodo)) throw new Exception("Metodo '$metodo' não indentificado para a Classe $classe no roteador");
        if (!array_key_exists("metodo", $requisicao)) {
            return RespostaProcesso::resposta(mensagem: "Roteador: (chave 'metodo') Método de requisição não enviado", resposta: false,
            dados: $requisicao, formato: "json");
        };
        return $controller->$metodo($requisicao);

    }

    public function getResposta(): array {
        return $this->resposta;
    }
}