<?php

class Nome {
    private string $nome;
    public function __construct(string $nome, int $limite = 20){
        $nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $n = strlen($nome);
        if ($n > $limite || $n < 3){
            throw new Exception("Nome Invalido");
        }
        $this->nome = $nome;
    }
}


class Sobrenome {
    private string $sobrenome;
    public function __construct(string $sobrenome, int $limite = 60){
        $sobrenome = htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8');
        $n = strlen($sobrenome);
        if ($n > $limite || $n < 3){
            throw new Exception("Sobrenome Invalido");
        }
        $this->sobrenome = $sobrenome;
    }
}