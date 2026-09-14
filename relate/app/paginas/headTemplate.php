<?php

class HeadTemplate {
    private string $template;

    public function __construct(string $titulo, array $estilos){
        $this->template = $this->criarHead($estilos, $titulo);
    }

    public function getTemplate() {
        return $this->template;
    }

    private function criarHead(array $estilos, string $titulo){
        $estilo = $this->adicionarEstilo($estilos);
        return "
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                $estilo
                <title>$titulo</title>
            </head>
        ";
    }

    private function adicionarEstilo(array $estilos){
        $estilo = "";
        $n = count($estilos);
        for ($i=0; $i < $n; $i++) { 
            $estilo .= "<link rel='stylesheet' href='$estilos[$i]'>";
        }
        return $estilo;
    }
}