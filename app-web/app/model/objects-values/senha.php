<?php

class Senha {
    private string $senha;
    public function __construct(string $senha){
        if (!$this->validarSenha($senha)){
            throw new Exception("Senha invalida");
        }
    }

    private function validarSenha(string $senha) {
        return true;
    }

    public function getSenha() {
        return $this->senha;
    }
}