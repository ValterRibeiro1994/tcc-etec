<?php

class Senha {
    private string $senha;

    public function __construct(string $senha){
        $this->senha = $senha;
    }

    public function validarSenha() {
        $senha = $this->senha;
        $n = strlen($senha);
        if ($n < 8) throw new Exception("Cliente: Senha invalida");
        $maiuscula = false;
        $minuscula = false;
        $numero = false;
        $caracteres = false;
        // enquanto as condições forem falsas e tiver letras para percorrer
        while (!($maiuscula && $minuscula && $numero && $caracteres) && ($n >= 0)){
            $x = ($n - 1); // se a senha tenho 8 digitos o array tem espaços de 0 a 7
            $letra = $senha[$x];
            if (ctype_digit($letra)) {
                $numero = true;
            }
            else if (ctype_lower($letra)) {
                $minuscula = true;
            }
            else if (ctype_upper($letra)) {
                $maiuscula = true;
            }
            else if (ctype_punct($letra)){
                $caracteres = true;
            } else {
                throw new Exception("Cliente: Senha inválida");
            }
            $n -= 1;
        }
        return ($maiuscula && $minuscula && $numero && $caracteres);
    }

    public function setSenha(string $senha = ""){
        $this->senha = $senha;
    }

    public function getSenha() {
        return $this->senha;
    }

    public function hashSenha(){
        // valida a senha antes de realizar o hash
        if (!$this->validarSenha()){
            throw new Exception("Cliente: Senha invalida");
        }
        // fator de custo menor para um processamento mais rapido
        $options = ['cost' => 10];
        return password_hash($this->senha, PASSWORD_BCRYPT, $options);
    }

}