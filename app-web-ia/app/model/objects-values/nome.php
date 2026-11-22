<?php

class Nome {
    private string $nome;
    public function __construct(string $nome = "visitante", int $limite = 20){
        $nome = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
        $n = strlen($nome);
        if ($n > $limite || $n < 3) throw new Exception("Nome Invalido");
        $this->nome = $nome;
    }

    public function getNome(): string {
        return $this->nome;
    }
}

class Sobrenome {
    private string $sobrenome;
    public function __construct(string $sobrenome = "visitante", int $limite = 60){
        $sobrenome = htmlspecialchars($sobrenome, ENT_QUOTES, 'UTF-8');
        $n = strlen($sobrenome);
        if ($n > $limite || $n < 3) throw new Exception("Sobrenome Invalido");
        $this->sobrenome = $sobrenome;
    }

    public function getSobrenome(): string {
        if ($this->sobrenome == null) return "informar sobrenome";
        return $this->sobrenome;
    }
}
