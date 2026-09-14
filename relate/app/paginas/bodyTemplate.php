<?php

class BodyTemplate {
    private string $template;

    public function __construct(string $header, string $main, string $footer, array $scripts){
        $script = $this->adicionarScript($scripts);
        $this->template = $this->criarBody($header, $main, $footer, $script);
    }
    
    public function getTemplate(){
        return $this->template;
    }

    private function criarBody(string $header, string $main, string $footer, string $script){
        return "
            <body class='container-fluid'>
                $header
                $main
                $footer
                $script
            </body>
        ";
    }

    private function adicionarScript(array $scripts){
        $script = "";
        $n = count($scripts);
        for ($i=0; $i < $n; $i++) { 
            $script .= "\n <script src='$scripts[$i]'></script>";
        }
        return $script;
    }

}