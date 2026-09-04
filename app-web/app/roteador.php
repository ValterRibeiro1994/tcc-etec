<?php

class Roteador {
    private array $resposta;
    public function __construct(){
        
        $parametros = "";
        if ($_SERVER['REQUEST_METHOD'] == "GET"){
            // 1) Verifica se foi enviada URL
            var_dump($_GET);
            if (count($_GET) > 1){
                echo("TESTE");
            }
            
        }
    }

    private function getProcess(array $dadosGet){
        $classe = "Home";
        $metodo = "index";

        if (empty($dadosGet)){
            try {
                $classe .= "Controller";
                if (!class_exists($classe)){
                     return RespostaProcesso::respostaProcesso("Classe '$classe' não indentificada");
                }

                // instancia o controller solicitado
                $controller = new $classe();
                if (!method_exists($controller, $metodo)){    
                    return RespostaProcesso::respostaProcesso("Metodo '$metodo' não indentificado");
                }
            } catch (Exception $error) {
                //throw $th;
            }
        }

    }
    public function getResposta(): array {
        return [];
    }
}