<?php

class CadastroPagina {
    private string $template;

    public function __construct(){
        $this->template = $this->criarForm();
    }

    public function getTemplate(){
        return $this->template;
    }

    private function criarForm(){
        return "<main class='text-dark'><hr><br><br><h1 class='display-6 text-center fw-4'> TESTE rapido pagina cadastro </h1><br><br><hr></main>";
    }

    private function criarLabel(){

    }
}