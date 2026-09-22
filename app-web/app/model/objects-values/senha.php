<?php

class Senha {
    private string $hash;
    public function __construct(string $senha){
        if (!$this->validarSenha($senha)) throw new Exception("Senha invalida");
        $this->hash = $this->hashSenha($senha);
    }

    private function validarSenha(string $senha) {
        $n = strlen($senha);
        if ($n < 8) throw new Exception("ERRO SENHA: Caracteres insuficiente");
        $maiuscula = false;
        $minuscula = false;
        $numero = false;
        $caracteres = false;
        for ($x = 0; $x < $n; $x++){
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
                throw new Exception("ERRO SENHA: Espaços em branco não permitidas");
            }
        }
        return ($maiuscula && $minuscula && $numero && $caracteres);
    }

    public function getSenha() {
        return $this->hash;
    }

    private function hashSenha(string $senha){
        // fator de custo menor para um processamento mais rapido
        $options = ['cost' => 3];
        return password_hash($senha, PASSWORD_BCRYPT, $options);
    }

}